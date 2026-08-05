<?php

defined( 'ABSPATH' ) || exit;

/**
 * Deployment, entitlement and managed-update governance.
 *
 * This module verifies metadata and records approval decisions. It never
 * downloads, installs, activates, deactivates, publishes or rolls back code.
 */
final class Ikon_SEO_Deployment_Control {
	const CRON_HOOK = 'ikon_seo_deployment_control_daily';
	const LICENSE_PUBLIC_KEY_PATH = 'release/license-public-key.pem';
	const MAX_EVALUATION_DAYS = 30;

	private $platform_hardening;
	private $production_health;
	private $history;
	private $logger;

	public function __construct( Ikon_SEO_Platform_Hardening $platform_hardening, Ikon_SEO_Production_Health $production_health, Ikon_SEO_Workspace_History $history, Ikon_SEO_Logger $logger ) {
		$this->platform_hardening = $platform_hardening;
		$this->production_health = $production_health;
		$this->history = $history;
		$this->logger = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_monitor' ) );
	}

	public function entitlements_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_license_entitlements'; }
	public function releases_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_release_catalog'; }
	public function plans_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_deployment_plans'; }
	public function events_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_deployment_events'; }

	public function normalize_environment( $environment ) {
		$environment = sanitize_key( $environment );
		$allowed = array( 'production', 'staging', 'development', 'local' );
		if ( ! in_array( $environment, $allowed, true ) ) {
			$environment = function_exists( 'wp_get_environment_type' ) ? sanitize_key( wp_get_environment_type() ) : 'production';
		}
		return in_array( $environment, $allowed, true ) ? $environment : 'production';
	}

	public function normalize_channel( $channel, $environment = '' ) {
		$channel = sanitize_key( $channel );
		if ( ! in_array( $channel, array( 'stable', 'candidate', 'internal' ), true ) ) { $channel = 'stable'; }
		if ( 'production' === $this->normalize_environment( $environment ) ) { return 'stable'; }
		return $channel;
	}

	public function site_fingerprint() {
		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$installation_id = (string) get_option( 'ikon_seo_installation_id', '' );
		return hash( 'sha256', strtolower( (string) $host ) . '|' . $installation_id );
	}

	public function normalize_entitlement_payload( array $input ) {
		$features = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) ( $input['features'] ?? array() ) ) ) ) );
		$allowed_features = array( 'core', 'agency_command', 'portfolio_governance', 'client_portal', 'managed_updates', 'portfolio_learning' );
		$features = array_values( array_intersect( $features, $allowed_features ) );
		if ( ! in_array( 'core', $features, true ) ) { array_unshift( $features, 'core' ); }
		$edition = sanitize_key( $input['edition'] ?? 'core' );
		if ( ! in_array( $edition, array( 'core', 'agency', 'enterprise', 'evaluation' ), true ) ) { $edition = 'core'; }
		$scope = array_values( array_unique( array_filter( array_map( array( $this, 'normalize_environment' ), (array) ( $input['environment_scope'] ?? array( 'production','staging','development','local' ) ) ) ) ) );
		return array(
			'license_id' => substr( sanitize_text_field( $input['license_id'] ?? '' ), 0, 190 ),
			'organisation' => substr( sanitize_text_field( $input['organisation'] ?? '' ), 0, 255 ),
			'edition' => $edition,
			'site_fingerprint' => strtolower( sanitize_text_field( $input['site_fingerprint'] ?? '' ) ),
			'max_sites' => max( 1, min( 10000, absint( $input['max_sites'] ?? 1 ) ) ),
			'features' => $features,
			'environment_scope' => $scope ?: array( 'production' ),
			'issued_at' => $this->normalize_datetime( $input['issued_at'] ?? '' ),
			'not_before' => $this->normalize_datetime( $input['not_before'] ?? ( $input['issued_at'] ?? '' ) ),
			'expires_at' => $this->normalize_datetime( $input['expires_at'] ?? '' ),
			'revoked' => ! empty( $input['revoked'] ),
		);
	}

	public function entitlement_fingerprint( array $payload ) {
		return hash( 'sha256', $this->canonical_json( $payload ) );
	}

	public function verify_entitlement_envelope( array $envelope, $public_key_pem = '' ) {
		$payload = $this->normalize_entitlement_payload( (array) ( $envelope['payload'] ?? array() ) );
		$signature = base64_decode( trim( (string) ( $envelope['signature'] ?? '' ) ), true );
		if ( ! $payload['license_id'] || ! $payload['site_fingerprint'] || false === $signature ) {
			return new WP_Error( 'ikon_seo_license_envelope', __( 'The signed entitlement envelope is incomplete.', 'ikon-seo' ) );
		}
		if ( ! $public_key_pem ) {
			$key_file = trailingslashit( IKON_SEO_DIR ) . self::LICENSE_PUBLIC_KEY_PATH;
			$public_key_pem = is_readable( $key_file ) ? (string) file_get_contents( $key_file ) : '';
		}
		if ( ! $public_key_pem || ! function_exists( 'openssl_verify' ) ) {
			return new WP_Error( 'ikon_seo_license_crypto', __( 'OpenSSL and the Ikon SEO licensing public key are required.', 'ikon-seo' ) );
		}
		$key = openssl_pkey_get_public( $public_key_pem );
		$valid = $key ? openssl_verify( $this->canonical_json( $payload ), $signature, $key, OPENSSL_ALGO_SHA256 ) : -1;
		if ( 1 !== $valid ) { return new WP_Error( 'ikon_seo_license_signature', __( 'The entitlement signature is invalid.', 'ikon-seo' ) ); }
		if ( ! hash_equals( $this->site_fingerprint(), $payload['site_fingerprint'] ) ) {
			return new WP_Error( 'ikon_seo_license_site', __( 'The entitlement was issued for a different WordPress website.', 'ikon-seo' ) );
		}
		return array( 'payload' => $payload, 'fingerprint' => $this->entitlement_fingerprint( $payload ), 'signature_state' => 'verified' );
	}

	public function license_state( array $license, $now = null ) {
		$now = null === $now ? time() : (int) $now;
		if ( ! empty( $license['revoked'] ) || 'revoked' === ( $license['status'] ?? '' ) ) { return 'revoked'; }
		$not_before = ! empty( $license['not_before'] ) ? strtotime( $license['not_before'] . ' UTC' ) : 0;
		$expires = ! empty( $license['expires_at'] ) ? strtotime( $license['expires_at'] . ' UTC' ) : 0;
		if ( $not_before && $now < $not_before ) { return 'not_yet_valid'; }
		if ( $expires && $now >= $expires ) { return 'expired'; }
		$warning_days = max( 1, min( 90, absint( Ikon_SEO_Plugin::settings()['deployment_license_warning_days'] ?? 30 ) ) );
		if ( $expires && $expires - $now <= $warning_days * DAY_IN_SECONDS ) { return 'expiring'; }
		return 'active';
	}

	public function normalize_release_metadata( array $input ) {
		$environment = $this->normalize_environment( $input['environment'] ?? 'production' );
		return array(
			'release_id' => substr( sanitize_text_field( $input['release_id'] ?? '' ), 0, 190 ),
			'version' => preg_replace( '/[^0-9A-Za-z.\-+]/', '', (string) ( $input['version'] ?? '' ) ),
			'database_version' => preg_replace( '/[^0-9.]/', '', (string) ( $input['database_version'] ?? '' ) ),
			'channel' => $this->normalize_channel( $input['channel'] ?? 'stable', $environment ),
			'environment' => $environment,
			'package_sha256' => strtolower( preg_replace( '/[^a-f0-9]/i', '', (string) ( $input['package_sha256'] ?? '' ) ) ),
			'manifest_sha256' => strtolower( preg_replace( '/[^a-f0-9]/i', '', (string) ( $input['manifest_sha256'] ?? '' ) ) ),
			'min_php' => sanitize_text_field( $input['min_php'] ?? '7.4' ),
			'min_wordpress' => sanitize_text_field( $input['min_wordpress'] ?? '6.4' ),
			'published_at' => $this->normalize_datetime( $input['published_at'] ?? '' ),
			'notes' => sanitize_textarea_field( $input['notes'] ?? '' ),
			'manual_install_only' => true,
			'remote_download' => false,
		);
	}

	public function release_fingerprint( array $release ) { return hash( 'sha256', $this->canonical_json( $release ) ); }

	public function deployment_fingerprint( array $plan ) {
		$allowed = array_intersect_key( $plan, array_flip( array( 'release_id','from_version','target_version','target_database_version','environment','channel','release_fingerprint','license_fingerprint','platform_fingerprint','recovery_archive_id' ) ) );
		return hash( 'sha256', $this->canonical_json( $allowed ) );
	}

	public function readiness_gate( array $release, array $license, array $platform, array $recovery, $environment, $current_version = '' ) {
		$blocks = array(); $warnings = array();
		$environment = $this->normalize_environment( $environment );
		$current_version = $current_version ?: ( defined( 'IKON_SEO_VERSION' ) ? IKON_SEO_VERSION : '0.0.0' );
		$license_state = $this->license_state( $license );
		if ( ! in_array( $license_state, array( 'active', 'expiring' ), true ) ) { $blocks[] = 'An active entitlement is required for a new managed deployment.'; }
		if ( 'expiring' === $license_state ) { $warnings[] = 'The entitlement expires soon.'; }
		if ( ! in_array( $environment, (array) ( $license['environment_scope'] ?? array() ), true ) ) { $blocks[] = 'The entitlement does not cover this WordPress environment.'; }
		if ( 'production' === $environment && 'stable' !== ( $release['channel'] ?? '' ) ) { $blocks[] = 'Production deployments require the stable channel.'; }
		if ( 64 !== strlen( (string) ( $release['package_sha256'] ?? '' ) ) || preg_match( '/^0{64}$/', (string) ( $release['package_sha256'] ?? '' ) ) || 64 !== strlen( (string) ( $release['manifest_sha256'] ?? '' ) ) ) { $blocks[] = 'The release metadata does not contain complete package and manifest hashes.'; }
		if ( version_compare( (string) ( $release['version'] ?? '0' ), $current_version, '<' ) ) { $blocks[] = 'A managed deployment cannot silently downgrade the plugin.'; }
		if ( 'ready' !== ( $platform['readiness']['status'] ?? $platform['status'] ?? '' ) ) { $blocks[] = 'Platform Health must be ready before deployment approval.'; }
		if ( empty( $recovery['id'] ) || empty( $recovery['payload_hash'] ) ) { $blocks[] = 'A verified pre-deployment configuration recovery point is required.'; }
		return array(
			'status' => $blocks ? 'blocked' : ( $warnings ? 'review' : 'ready' ),
			'blocks' => $blocks,
			'warnings' => $warnings,
			'manual_wordpress_update_required' => true,
			'automatic_installation' => false,
			'automatic_rollback' => false,
		);
	}

	public function create_evaluation( array $input, $user_id = 0 ) {
		global $wpdb;
		$environment = $this->normalize_environment( function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production' );
		if ( 'production' === $environment ) { return new WP_Error( 'ikon_seo_evaluation_production', __( 'Local evaluation entitlements are not available for production environments.', 'ikon-seo' ) ); }
		$days = max( 1, min( self::MAX_EVALUATION_DAYS, absint( $input['days'] ?? 14 ) ) );
		$now = current_time( 'mysql', true );
		$payload = $this->normalize_entitlement_payload( array(
			'license_id' => 'evaluation-' . substr( hash( 'sha256', home_url( '/' ) . '|' . $now ), 0, 20 ),
			'organisation' => sanitize_text_field( $input['organisation'] ?? get_bloginfo( 'name' ) ),
			'edition' => 'evaluation',
			'site_fingerprint' => $this->site_fingerprint(),
			'max_sites' => 1,
			'features' => array( 'core','agency_command','portfolio_governance','client_portal','managed_updates','portfolio_learning' ),
			'environment_scope' => array( $environment ),
			'issued_at' => $now,
			'not_before' => $now,
			'expires_at' => gmdate( 'Y-m-d H:i:s', time() + $days * DAY_IN_SECONDS ),
		) );
		return $this->store_entitlement( $payload, 'local_evaluation', 'local_admin', '', $user_id );
	}

	public function import_entitlement( array $envelope, $user_id = 0 ) {
		$verified = $this->verify_entitlement_envelope( $envelope );
		if ( is_wp_error( $verified ) ) { return $verified; }
		return $this->store_entitlement( $verified['payload'], 'signed_import', $verified['signature_state'], (string) ( $envelope['signature'] ?? '' ), $user_id );
	}

	private function store_entitlement( array $payload, $source, $signature_state, $signature, $user_id ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$fingerprint = $this->entitlement_fingerprint( $payload );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->entitlements_table()} WHERE entitlement_fingerprint=%s", $fingerprint ) );
		if ( $existing ) { return $this->get_entitlement( absint( $existing ) ); }
		$wpdb->insert( $this->entitlements_table(), array(
			'license_id' => $payload['license_id'], 'organisation' => $payload['organisation'], 'edition' => $payload['edition'],
			'site_fingerprint' => $payload['site_fingerprint'], 'entitlement_fingerprint' => $fingerprint,
			'features_json' => wp_json_encode( $payload['features'] ), 'environment_scope_json' => wp_json_encode( $payload['environment_scope'] ),
			'max_sites' => $payload['max_sites'], 'status' => $this->license_state( $payload ), 'source' => sanitize_key( $source ),
			'signature_state' => sanitize_key( $signature_state ), 'signature' => sanitize_text_field( $signature ),
			'issued_at' => $payload['issued_at'] ?: null, 'not_before' => $payload['not_before'] ?: null, 'expires_at' => $payload['expires_at'] ?: null,
			'created_by' => absint( $user_id ), 'created_at' => $now, 'updated_at' => $now,
		) );
		if ( ! $wpdb->insert_id ) { return new WP_Error( 'ikon_seo_entitlement_store', __( 'The entitlement could not be stored.', 'ikon-seo' ) ); }
		$id = absint( $wpdb->insert_id );
		$this->event( 0, 'entitlement_created', 'completed', 'A deployment entitlement was stored.', array( 'entitlement_id' => $id, 'source' => $source ), $user_id );
		return $this->get_entitlement( $id );
	}

	public function revoke_entitlement( $id, $reason, $user_id = 0 ) {
		global $wpdb;
		$reason = sanitize_textarea_field( $reason );
		if ( ! trim( $reason ) ) { return new WP_Error( 'ikon_seo_entitlement_reason', __( 'Enter a revocation reason.', 'ikon-seo' ) ); }
		$row = $this->get_entitlement( $id );
		if ( ! $row ) { return new WP_Error( 'ikon_seo_entitlement_missing', __( 'The entitlement was not found.', 'ikon-seo' ) ); }
		$wpdb->update( $this->entitlements_table(), array( 'status' => 'revoked', 'revoked_at' => current_time( 'mysql', true ), 'revoked_by' => absint( $user_id ), 'revocation_reason' => $reason, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $id ) ) );
		$this->event( 0, 'entitlement_revoked', 'completed', $reason, array( 'entitlement_id' => absint( $id ) ), $user_id );
		return $this->get_entitlement( $id );
	}

	public function register_installed_release( $user_id = 0 ) {
		$integrity = $this->platform_hardening->verify_release( $user_id, 'deployment_control' );
		if ( 'verified' !== ( $integrity['overall_status'] ?? '' ) ) { return new WP_Error( 'ikon_seo_release_integrity', __( 'Verify the installed package before registering it in the release catalogue.', 'ikon-seo' ) ); }
		$settings = Ikon_SEO_Plugin::settings();
		$environment = $this->normalize_environment( function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production' );
		$release = $this->normalize_release_metadata( array(
			'release_id' => 'ikon-seo-' . IKON_SEO_VERSION,
			'version' => IKON_SEO_VERSION,
			'database_version' => Ikon_SEO_Plugin::DB_VERSION,
			'channel' => $this->normalize_channel( $settings['deployment_channel'] ?? 'stable', $environment ),
			'environment' => $environment,
			'package_sha256' => str_repeat( '0', 64 ),
			'manifest_sha256' => $integrity['manifest_hash'] ?? '',
			'published_at' => current_time( 'mysql', true ),
			'notes' => 'Registered from the currently installed, signed Ikon SEO package. The original ZIP hash is not available inside WordPress.',
		) );
		return $this->store_release( $release, 'installed_verified', 'verified_manifest', $user_id );
	}

	public function import_release( array $envelope, $user_id = 0, $public_key_pem = '' ) {
		$release = $this->normalize_release_metadata( (array) ( $envelope['payload'] ?? array() ) );
		$signature = base64_decode( trim( (string) ( $envelope['signature'] ?? '' ) ), true );
		if ( ! $release['release_id'] || ! $release['version'] || false === $signature ) { return new WP_Error( 'ikon_seo_release_envelope', __( 'The signed release envelope is incomplete.', 'ikon-seo' ) ); }
		if ( ! $public_key_pem ) {
			$file = trailingslashit( IKON_SEO_DIR ) . self::LICENSE_PUBLIC_KEY_PATH;
			$public_key_pem = is_readable( $file ) ? (string) file_get_contents( $file ) : '';
		}
		$key = $public_key_pem && function_exists( 'openssl_verify' ) ? openssl_pkey_get_public( $public_key_pem ) : false;
		$valid = $key ? openssl_verify( $this->canonical_json( $release ), $signature, $key, OPENSSL_ALGO_SHA256 ) : -1;
		if ( 1 !== $valid ) { return new WP_Error( 'ikon_seo_release_signature', __( 'The release metadata signature is invalid.', 'ikon-seo' ) ); }
		return $this->store_release( $release, 'signed_import', 'verified', $user_id );
	}

	private function store_release( array $release, $source, $signature_state, $user_id ) {
		global $wpdb;
		$fingerprint = $this->release_fingerprint( $release );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->releases_table()} WHERE release_fingerprint=%s", $fingerprint ) );
		if ( $existing ) { return $this->get_release( absint( $existing ) ); }
		$now = current_time( 'mysql', true );
		$wpdb->insert( $this->releases_table(), array(
			'release_key' => $release['release_id'], 'version' => $release['version'], 'database_version' => $release['database_version'],
			'channel' => $release['channel'], 'environment' => $release['environment'], 'status' => 'available',
			'package_sha256' => $release['package_sha256'], 'manifest_sha256' => $release['manifest_sha256'], 'release_fingerprint' => $fingerprint,
			'metadata_json' => wp_json_encode( $release ), 'source' => sanitize_key( $source ), 'signature_state' => sanitize_key( $signature_state ),
			'created_by' => absint( $user_id ), 'created_at' => $now, 'updated_at' => $now,
		) );
		if ( ! $wpdb->insert_id ) { return new WP_Error( 'ikon_seo_release_store', __( 'The release metadata could not be stored.', 'ikon-seo' ) ); }
		$id = absint( $wpdb->insert_id );
		$this->event( 0, 'release_registered', 'completed', 'A release was registered for controlled deployment planning.', array( 'release_id' => $id, 'version' => $release['version'] ), $user_id );
		return $this->get_release( $id );
	}

	public function create_plan( $release_id, array $input, $user_id = 0 ) {
		global $wpdb;
		$release_row = $this->get_release( $release_id );
		if ( ! $release_row ) { return new WP_Error( 'ikon_seo_deployment_release', __( 'Select a registered release.', 'ikon-seo' ) ); }
		$license = $this->active_entitlement();
		if ( ! $license ) { return new WP_Error( 'ikon_seo_deployment_license', __( 'An active deployment entitlement is required.', 'ikon-seo' ) ); }
		$release = $release_row['metadata'];
		$platform = $this->platform_hardening->report();
		$recovery = $this->latest_recovery_archive();
		$environment = $this->normalize_environment( function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production' );
		$gate = $this->readiness_gate( $release, $license, $platform, $recovery, $environment, IKON_SEO_VERSION );
		if ( 'blocked' === $gate['status'] ) { return new WP_Error( 'ikon_seo_deployment_blocked', implode( ' ', $gate['blocks'] ) ); }
		$plan = array(
			'release_id' => absint( $release_id ), 'from_version' => IKON_SEO_VERSION, 'target_version' => $release['version'],
			'target_database_version' => $release['database_version'], 'environment' => $environment, 'channel' => $release['channel'],
			'release_fingerprint' => $release_row['release_fingerprint'], 'license_fingerprint' => $license['entitlement_fingerprint'],
			'platform_fingerprint' => hash( 'sha256', wp_json_encode( $platform['readiness'] ?? array() ) ), 'recovery_archive_id' => absint( $recovery['id'] ?? 0 ),
		);
		$fingerprint = $this->deployment_fingerprint( $plan ); $now = current_time( 'mysql', true );
		$wpdb->insert( $this->plans_table(), array(
			'release_id' => absint( $release_id ), 'status' => 'prepared', 'environment' => $environment, 'channel' => $release['channel'],
			'from_version' => IKON_SEO_VERSION, 'target_version' => $release['version'], 'target_database_version' => $release['database_version'],
			'preflight_fingerprint' => $fingerprint, 'preflight_json' => wp_json_encode( array( 'gate' => $gate, 'plan' => $plan ) ),
			'recovery_archive_id' => absint( $recovery['id'] ?? 0 ), 'prepared_by' => absint( $user_id ), 'prepared_at' => $now,
			'notes' => sanitize_textarea_field( $input['notes'] ?? '' ), 'created_at' => $now, 'updated_at' => $now,
		) );
		if ( ! $wpdb->insert_id ) { return new WP_Error( 'ikon_seo_deployment_store', __( 'The deployment plan could not be stored.', 'ikon-seo' ) ); }
		$id = absint( $wpdb->insert_id );
		$this->event( $id, 'deployment_prepared', 'completed', 'A manual deployment plan was prepared.', array( 'target_version' => $release['version'] ), $user_id );
		return $this->get_plan( $id );
	}

	public function approve_plan( $id, $expected_fingerprint, $notes, $user_id = 0 ) {
		global $wpdb; $plan = $this->get_plan( $id );
		if ( ! $plan || 'prepared' !== $plan['status'] ) { return new WP_Error( 'ikon_seo_deployment_approve', __( 'Only a prepared deployment can be approved.', 'ikon-seo' ) ); }
		if ( absint( $plan['prepared_by'] ) === absint( $user_id ) && $user_id ) { return new WP_Error( 'ikon_seo_deployment_separation', __( 'A different administrator must approve the deployment plan.', 'ikon-seo' ) ); }
		if ( ! $expected_fingerprint || ! hash_equals( $plan['preflight_fingerprint'], strtolower( trim( $expected_fingerprint ) ) ) ) { return new WP_Error( 'ikon_seo_deployment_fingerprint', __( 'The deployment preflight fingerprint does not match.', 'ikon-seo' ) ); }
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->plans_table(), array( 'status' => 'approved_manual_install', 'approved_by' => absint( $user_id ), 'approved_at' => $now, 'approval_notes' => sanitize_textarea_field( $notes ), 'updated_at' => $now ), array( 'id' => absint( $id ) ) );
		$this->event( $id, 'deployment_approved', 'completed', 'A separate administrator approved a manual WordPress update.', array(), $user_id );
		return $this->get_plan( $id );
	}

	public function record_manual_deployment( $id, $notes, $user_id = 0 ) {
		global $wpdb; $plan = $this->get_plan( $id );
		if ( ! $plan || 'approved_manual_install' !== $plan['status'] ) { return new WP_Error( 'ikon_seo_deployment_record', __( 'The deployment is not approved for manual installation.', 'ikon-seo' ) ); }
		if ( ! hash_equals( (string) $plan['target_version'], (string) IKON_SEO_VERSION ) || ! hash_equals( (string) $plan['target_database_version'], (string) Ikon_SEO_Plugin::DB_VERSION ) ) {
			return new WP_Error( 'ikon_seo_deployment_not_installed', __( 'WordPress does not yet report the approved target plugin and database versions.', 'ikon-seo' ) );
		}
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->plans_table(), array( 'status' => 'deployed_pending_verification', 'deployed_by' => absint( $user_id ), 'deployed_at' => $now, 'deployment_notes' => sanitize_textarea_field( $notes ), 'updated_at' => $now ), array( 'id' => absint( $id ) ) );
		$this->event( $id, 'manual_deployment_recorded', 'completed', 'The administrator recorded a manual WordPress plugin update.', array(), $user_id );
		return $this->get_plan( $id );
	}

	public function verify_deployment( $id, $user_id = 0 ) {
		global $wpdb; $plan = $this->get_plan( $id );
		if ( ! $plan || ! in_array( $plan['status'], array( 'deployed_pending_verification', 'verification_failed' ), true ) ) { return new WP_Error( 'ikon_seo_deployment_verify', __( 'The deployment is not waiting for verification.', 'ikon-seo' ) ); }
		$report = $this->platform_hardening->run_full_check( $user_id, 'deployment_verification' );
		$passed = 'ready' === ( $report['readiness']['status'] ?? '' ) && 'verified' === ( $report['integrity']['overall_status'] ?? '' );
		$status = $passed ? 'verified' : 'verification_failed'; $now = current_time( 'mysql', true );
		$wpdb->update( $this->plans_table(), array( 'status' => $status, 'verification_json' => wp_json_encode( $report ), 'verified_by' => absint( $user_id ), 'verified_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $id ) ) );
		$this->event( $id, 'deployment_verified', $passed ? 'completed' : 'blocked', $passed ? 'The manually installed release passed post-deployment verification.' : 'The manually installed release requires investigation.', array( 'readiness' => $report['readiness']['status'] ?? '' ), $user_id );
		return $this->get_plan( $id );
	}

	public function close_plan( $id, $notes, $user_id = 0 ) {
		global $wpdb; $plan = $this->get_plan( $id ); $notes = sanitize_textarea_field( $notes );
		if ( ! $plan || 'verified' !== $plan['status'] ) { return new WP_Error( 'ikon_seo_deployment_close', __( 'Only a verified deployment can be closed.', 'ikon-seo' ) ); }
		if ( ! trim( $notes ) ) { return new WP_Error( 'ikon_seo_deployment_close_notes', __( 'Enter closure notes.', 'ikon-seo' ) ); }
		$wpdb->update( $this->plans_table(), array( 'status' => 'closed', 'closure_notes' => $notes, 'closed_by' => absint( $user_id ), 'closed_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $id ) ) );
		$this->event( $id, 'deployment_closed', 'completed', $notes, array(), $user_id );
		return $this->get_plan( $id );
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'create_evaluation': return $this->create_evaluation( (array) ( $payload['evaluation'] ?? $payload ), $user_id );
			case 'import_entitlement': return $this->import_entitlement( (array) ( $payload['envelope'] ?? array() ), $user_id );
			case 'revoke_entitlement': return $this->revoke_entitlement( absint( $payload['entitlement_id'] ?? 0 ), (string) ( $payload['notes'] ?? '' ), $user_id );
			case 'register_installed_release': return $this->register_installed_release( $user_id );
			case 'import_release': return $this->import_release( (array) ( $payload['envelope'] ?? array() ), $user_id );
			case 'create_plan': return $this->create_plan( absint( $payload['release_id'] ?? 0 ), (array) ( $payload['plan'] ?? $payload ), $user_id );
			case 'approve_plan': return $this->approve_plan( absint( $payload['plan_id'] ?? 0 ), (string) ( $payload['expected_fingerprint'] ?? '' ), (string) ( $payload['notes'] ?? '' ), $user_id );
			case 'record_manual_deployment': return $this->record_manual_deployment( absint( $payload['plan_id'] ?? 0 ), (string) ( $payload['notes'] ?? '' ), $user_id );
			case 'verify_deployment': return $this->verify_deployment( absint( $payload['plan_id'] ?? 0 ), $user_id );
			case 'close_plan': return $this->close_plan( absint( $payload['plan_id'] ?? 0 ), (string) ( $payload['notes'] ?? '' ), $user_id );
			case 'read': default: return $this->report();
		}
	}

	public function report() {
		$settings = Ikon_SEO_Plugin::settings();
		return array(
			'environment' => $this->normalize_environment( function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production' ),
			'channel' => $this->normalize_channel( $settings['deployment_channel'] ?? 'stable', function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production' ),
			'active_entitlement' => $this->active_entitlement(),
			'entitlements' => $this->entitlements( 25 ),
			'releases' => $this->releases( 25 ),
			'deployments' => $this->plans( 25 ),
			'safeguards' => array(
				'automatic_plugin_updates' => false,
				'remote_package_download' => false,
				'filesystem_installation' => false,
				'automatic_rollback' => false,
				'license_expiry_disables_public_site' => false,
				'license_expiry_deletes_data' => false,
				'read_export_and_recovery_remain_available' => true,
				'manual_wordpress_update_required' => true,
			),
			'generated_at' => current_time( 'mysql', true ),
		);
	}

	public function scheduled_monitor() {
		global $wpdb;
		foreach ( $this->entitlements( 200 ) as $row ) {
			$state = $this->license_state( $row );
			if ( $state !== $row['status'] && 'revoked' !== $row['status'] ) {
				$wpdb->update( $this->entitlements_table(), array( 'status' => $state, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $row['id'] ) ) );
			}
		}
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
		if ( $this->table_exists( $this->plans_table() ) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$this->plans_table()} SET status='stale',updated_at=%s WHERE status='prepared' AND prepared_at<%s", current_time( 'mysql', true ), $cutoff ) );
		}
		return $this->report();
	}

	public function get_entitlement( $id ) { global $wpdb; $r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->entitlements_table()} WHERE id=%d",absint($id)),ARRAY_A); return $r?$this->hydrate_entitlement($r):null; }
	public function entitlements( $limit=25 ) { global $wpdb; if(!$this->table_exists($this->entitlements_table()))return array(); $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->entitlements_table()} ORDER BY created_at DESC,id DESC LIMIT %d",max(1,min(200,absint($limit)))),ARRAY_A); return array_map(array($this,'hydrate_entitlement'),$rows?:array()); }
	public function active_entitlement() { foreach($this->entitlements(100) as $r){ if(in_array($this->license_state($r),array('active','expiring'),true))return $r; } return null; }
	private function hydrate_entitlement($r){$r['features']=json_decode((string)($r['features_json']??''),true)?:array();$r['environment_scope']=json_decode((string)($r['environment_scope_json']??''),true)?:array();$r['revoked']='revoked'===($r['status']??'');return $r;}
	public function get_release($id){global $wpdb;$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->releases_table()} WHERE id=%d",absint($id)),ARRAY_A);if(!$r)return null;$r['metadata']=json_decode((string)$r['metadata_json'],true)?:array();return $r;}
	public function releases($limit=25){global $wpdb;if(!$this->table_exists($this->releases_table()))return array();$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->releases_table()} ORDER BY created_at DESC,id DESC LIMIT %d",max(1,min(200,absint($limit)))),ARRAY_A);return array_map(function($r){$r['metadata']=json_decode((string)$r['metadata_json'],true)?:array();return $r;},$rows?:array());}
	public function get_plan($id){global $wpdb;$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->plans_table()} WHERE id=%d",absint($id)),ARRAY_A);if(!$r)return null;$r['preflight']=json_decode((string)($r['preflight_json']??''),true)?:array();$r['verification']=json_decode((string)($r['verification_json']??''),true)?:array();return $r;}
	public function plans($limit=25){global $wpdb;if(!$this->table_exists($this->plans_table()))return array();$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->plans_table()} ORDER BY created_at DESC,id DESC LIMIT %d",max(1,min(200,absint($limit)))),ARRAY_A);return array_map(function($r){$r['preflight']=json_decode((string)($r['preflight_json']??''),true)?:array();$r['verification']=json_decode((string)($r['verification_json']??''),true)?:array();return $r;},$rows?:array());}

	private function latest_recovery_archive(){global $wpdb;$table=$wpdb->prefix.'ikon_seo_recovery_archives';if(!$this->table_exists($table))return array();$r=$wpdb->get_row("SELECT id,payload_hash,created_at,status FROM {$table} WHERE status='available' ORDER BY created_at DESC,id DESC LIMIT 1",ARRAY_A);return $r?:array();}
	private function event($plan_id,$type,$status,$message,array $details,$user_id){global $wpdb;if(!$this->table_exists($this->events_table()))return;$wpdb->insert($this->events_table(),array('deployment_id'=>absint($plan_id),'event_type'=>sanitize_key($type),'status'=>sanitize_key($status),'message'=>sanitize_textarea_field($message),'details_json'=>wp_json_encode($details),'actor_id'=>absint($user_id),'created_at'=>current_time('mysql',true)));$this->logger->log('deployment_control',$status,$message);}
	private function normalize_datetime($value){$value=trim((string)$value);if(!$value)return '';$ts=strtotime($value.' UTC');return $ts?gmdate('Y-m-d H:i:s',$ts):'';}
	private function canonical_json($value){return wp_json_encode($this->canonicalize($value),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);}
	private function canonicalize($value){if(!is_array($value))return $value;if(array_keys($value)!==range(0,count($value)-1)){ksort($value);foreach($value as $k=>$v)$value[$k]=$this->canonicalize($v);}else{foreach($value as $k=>$v)$value[$k]=$this->canonicalize($v);}return $value;}
	private function table_exists($table){global $wpdb;return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))===$table;}
}
