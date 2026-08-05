<?php

defined( 'ABSPATH' ) || exit;

/**
 * Platform hardening, release integrity, recovery and upgrade governance.
 *
 * This module never publishes or edits WordPress content. Configuration
 * restores require a local administrator, an exact archive hash and a
 * previewable same-version archive.
 */
class Ikon_SEO_Platform_Hardening {
	const CRON_HOOK = 'ikon_seo_platform_hardening_daily';
	const MANIFEST_PATH = 'release/manifest.json';
	const SIGNATURE_PATH = 'release/manifest.sig';
	const PUBLIC_KEY_PATH = 'release/public-key.pem';

	private $production_health;
	private $crypto;
	private $history;
	private $logger;

	public function __construct( Ikon_SEO_Production_Health $production_health, Ikon_SEO_Crypto $crypto, Ikon_SEO_Workspace_History $history, Ikon_SEO_Logger $logger ) {
		$this->production_health = $production_health;
		$this->crypto            = $crypto;
		$this->history           = $history;
		$this->logger            = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_run' ) );
	}

	public function scheduled_run() {
		$this->run_full_check( 0, 'scheduled' );
		$this->cleanup();
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'run_checks':
				return $this->run_full_check( $user_id, 'workspace' );
			case 'verify_release':
				return $this->verify_release( $user_id, 'workspace' );
			case 'create_archive':
				return $this->create_archive( $user_id, sanitize_text_field( $payload['label'] ?? '' ), sanitize_key( $payload['archive_type'] ?? 'configuration' ) );
			case 'preview_restore':
				return $this->preview_restore( absint( $payload['archive_id'] ?? 0 ) );
			case 'restore_archive':
				return $this->restore_archive( absint( $payload['archive_id'] ?? 0 ), sanitize_text_field( $payload['expected_hash'] ?? '' ), $user_id );
			case 'repair_scheduler':
				return $this->repair_scheduler( $user_id );
			case 'cleanup':
				return $this->cleanup();
			case 'read':
			default:
				return $this->report();
		}
	}

	public function report() {
		$integrity = $this->latest_integrity_run();
		$health    = $this->production_health->report( false );
		$compat    = $this->compatibility_matrix();
		$security  = $this->security_posture();
		$archives  = $this->archives( 10 );
		$upgrades  = $this->upgrade_history( 10 );
		$gate      = $this->readiness_gate( $health, $integrity, $compat, $security, $archives );

		return array(
			'release' => array(
				'plugin_version' => IKON_SEO_VERSION,
				'database_version' => Ikon_SEO_Plugin::DB_VERSION,
				'manifest_path' => self::MANIFEST_PATH,
				'automatic_updates' => false,
				'package_verification' => 'detached_rsa_sha256_manifest',
			),
			'readiness' => $gate,
			'production_health' => $health,
			'integrity' => $integrity,
			'compatibility' => $compat,
			'security' => $security,
			'recovery_archives' => $archives,
			'upgrade_journal' => $upgrades,
			'safeguards' => array(
				'configuration_restore_requires_local_admin' => true,
				'restore_requires_exact_payload_hash' => true,
				'credentials_are_excluded_from_archives' => true,
				'live_content_changes' => false,
				'automatic_rollback' => false,
				'external_diagnostics_delivery' => false,
			),
		);
	}

	public function run_full_check( $user_id = 0, $source = 'manual' ) {
		$health    = $this->production_health->run( $user_id, $source );
		$integrity = $this->verify_release( $user_id, $source );
		$compat    = $this->compatibility_matrix();
		$security  = $this->security_posture();
		$archives  = $this->archives( 10 );
		$gate      = $this->readiness_gate( $health, $integrity, $compat, $security, $archives );
		update_option( 'ikon_seo_platform_hardening_last_run', current_time( 'mysql', true ), false );
		$this->history->add(
			array(
				'category' => 'system',
				'status' => 'completed',
				'title' => 'Platform hardening checks completed',
				'summary' => sprintf( 'Release readiness: %s. Integrity: %s.', $gate['status'], $integrity['file_state'] ?? 'unknown' ),
				'details' => array( 'readiness' => $gate, 'source' => $source ),
			),
			'platform_hardening',
			$user_id
		);
		return $this->report();
	}

	public function verify_release( $user_id = 0, $source = 'manual' ) {
		global $wpdb;
		$root = trailingslashit( IKON_SEO_DIR );
		$manifest_file = $root . self::MANIFEST_PATH;
		$signature_file = $root . self::SIGNATURE_PATH;
		$public_key_file = $root . self::PUBLIC_KEY_PATH;
		$changed = array();
		$missing = array();
		$unexpected = array();
		$signature_state = 'unavailable';
		$manifest_hash = '';
		$manifest = array();

		if ( is_readable( $manifest_file ) ) {
			$manifest_raw = (string) file_get_contents( $manifest_file );
			$manifest_hash = hash( 'sha256', $manifest_raw );
			$manifest = json_decode( $manifest_raw, true );
			$manifest = is_array( $manifest ) ? $manifest : array();
			if ( is_readable( $signature_file ) && is_readable( $public_key_file ) && function_exists( 'openssl_verify' ) ) {
				$signature = base64_decode( trim( (string) file_get_contents( $signature_file ) ), true );
				$public_key = openssl_pkey_get_public( (string) file_get_contents( $public_key_file ) );
				if ( false !== $signature && false !== $public_key ) {
					$verification = openssl_verify( $manifest_raw, $signature, $public_key, OPENSSL_ALGO_SHA256 );
					$signature_state = 1 === $verification ? 'verified' : ( 0 === $verification ? 'failed' : 'invalid_metadata' );
				} else {
					$signature_state = 'invalid_metadata';
				}
			}
		} else {
			$missing[] = self::MANIFEST_PATH;
		}

		$expected = is_array( $manifest['files'] ?? null ) ? $manifest['files'] : array();
		foreach ( $expected as $relative => $expected_hash ) {
			$relative = ltrim( str_replace( '\\', '/', (string) $relative ), '/' );
			if ( false !== strpos( $relative, '../' ) || in_array( $relative, array( self::MANIFEST_PATH, self::SIGNATURE_PATH, self::PUBLIC_KEY_PATH ), true ) ) {
				continue;
			}
			$file = $root . $relative;
			if ( ! is_file( $file ) ) {
				$missing[] = $relative;
				continue;
			}
			$actual = hash_file( 'sha256', $file );
			if ( ! hash_equals( (string) $expected_hash, (string) $actual ) ) {
				$changed[] = $relative;
			}
		}

		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iterator as $file_info ) {
			if ( ! $file_info->isFile() ) {
				continue;
			}
			$relative = ltrim( str_replace( '\\', '/', substr( $file_info->getPathname(), strlen( $root ) ) ), '/' );
			if ( in_array( $relative, array( self::MANIFEST_PATH, self::SIGNATURE_PATH, self::PUBLIC_KEY_PATH ), true ) ) {
				continue;
			}
			if ( preg_match( '/\.(php|js|json)$/i', $relative ) && ! array_key_exists( $relative, $expected ) ) {
				$unexpected[] = $relative;
			}
		}

		$file_state = ( $changed || $missing || $unexpected ) ? 'failed' : ( $expected ? 'verified' : 'unavailable' );
		$overall = ( 'verified' === $file_state && 'verified' === $signature_state ) ? 'verified' : ( 'failed' === $file_state || 'failed' === $signature_state || 'invalid_metadata' === $signature_state ? 'failed' : 'review' );
		$now = current_time( 'mysql', true );
		$table = $this->integrity_table();
		if ( $this->table_exists( $table ) ) {
			$wpdb->insert(
				$table,
				array(
					'run_hash' => hash( 'sha256', $manifest_hash . '|' . $now . '|' . wp_json_encode( array( $changed, $missing, $unexpected ) ) ),
					'release_version' => IKON_SEO_VERSION,
					'manifest_hash' => $manifest_hash,
					'signature_state' => $signature_state,
					'file_state' => $file_state,
					'overall_status' => $overall,
					'changed_files_json' => wp_json_encode( array( 'changed' => $changed, 'missing' => $missing, 'unexpected' => $unexpected ) ),
					'source' => sanitize_key( $source ),
					'created_by' => absint( $user_id ),
					'created_at' => $now,
				)
			);
		}
		$this->logger->log( 'release_integrity', 'verified' === $overall ? 'success' : 'warning', 'Release package integrity was checked.' );
		return array(
			'overall_status' => $overall,
			'signature_state' => $signature_state,
			'file_state' => $file_state,
			'manifest_hash' => $manifest_hash,
			'manifest_release' => sanitize_text_field( $manifest['release'] ?? '' ),
			'manifest_database' => sanitize_text_field( $manifest['database_version'] ?? '' ),
			'expected_files' => count( $expected ),
			'changed' => array_values( array_unique( $changed ) ),
			'missing' => array_values( array_unique( $missing ) ),
			'unexpected' => array_values( array_unique( $unexpected ) ),
			'generated_at' => $now,
			'limitations' => array( 'Bundled verification detects corruption and unexpected executable files; it is not a substitute for trusted update transport or server-level malware monitoring.' ),
		);
	}

	public function compatibility_matrix() {
		global $wpdb;
		$wp_version = get_bloginfo( 'version' );
		$db_version = method_exists( $wpdb, 'db_version' ) ? (string) $wpdb->db_version() : 'unknown';
		$items = array();
		$items[] = $this->matrix_item( 'php', 'PHP', PHP_VERSION, version_compare( PHP_VERSION, '7.4', '>=' ) ? 'pass' : 'critical', 'PHP 7.4 or newer is required.' );
		$items[] = $this->matrix_item( 'wordpress', 'WordPress', $wp_version, version_compare( $wp_version, '6.4', '>=' ) ? 'pass' : 'critical', 'WordPress 6.4 or newer is required.' );
		$db_state = 'unknown' === $db_version ? 'warning' : ( version_compare( preg_replace( '/[^0-9.].*/', '', $db_version ), '5.7', '>=' ) ? 'pass' : 'warning' );
		$items[] = $this->matrix_item( 'database', 'Database server', $db_version, $db_state, 'Use a currently supported MySQL or MariaDB version and test migrations on staging.' );
		foreach ( array( 'json' => 'JSON', 'openssl' => 'OpenSSL', 'mbstring' => 'Multibyte String' ) as $extension => $label ) {
			$items[] = $this->matrix_item( 'ext_' . $extension, $label . ' extension', extension_loaded( $extension ) ? 'Available' : 'Missing', extension_loaded( $extension ) ? 'pass' : ( 'mbstring' === $extension ? 'warning' : 'critical' ), 'Enable the extension in the website PHP environment.' );
		}
		$items[] = $this->matrix_item( 'ext_sodium', 'Sodium extension', extension_loaded( 'sodium' ) ? 'Available' : 'Not detected', extension_loaded( 'sodium' ) ? 'pass' : 'info', 'Sodium is optional for this release; OpenSSL performs package signature verification and credential encryption.' );
		$items[] = $this->matrix_item( 'https', 'HTTPS', is_ssl() ? 'Enabled' : 'Not detected', is_ssl() ? 'pass' : 'critical', 'Use HTTPS before connecting a private workspace.' );
		$items[] = $this->matrix_item( 'multisite', 'WordPress Multisite', is_multisite() ? 'Enabled; site-level review required' : 'Single site', is_multisite() ? 'warning' : 'pass', 'Network-wide activation is not certified; test each site independently.' );
		$items[] = $this->matrix_item( 'object_cache', 'Persistent object cache', wp_using_ext_object_cache() ? 'Enabled' : 'Not detected', 'info', 'Clear object and page caches after approved metadata changes.' );
		$items[] = $this->matrix_item( 'filesystem', 'Plugin directory readability', is_readable( IKON_SEO_DIR ) ? 'Readable' : 'Not readable', is_readable( IKON_SEO_DIR ) ? 'pass' : 'critical', 'Restore correct server ownership and permissions.' );
		$states = $this->matrix_counts( $items );
		return array( 'status' => $states['critical'] ? 'critical' : ( $states['warning'] ? 'review' : 'compatible' ), 'counts' => $states, 'items' => $items );
	}

	public function security_posture() {
		$settings = Ikon_SEO_Plugin::settings();
		$items = array();
		$items[] = $this->matrix_item( 'salts', 'WordPress authentication salts', defined( 'AUTH_KEY' ) && strlen( (string) AUTH_KEY ) >= 32 ? 'Configured' : 'Weak or unavailable', defined( 'AUTH_KEY' ) && strlen( (string) AUTH_KEY ) >= 32 ? 'pass' : 'critical', 'Configure unique WordPress authentication salts.' );
		$items[] = $this->matrix_item( 'file_editor', 'Dashboard file editor', defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ? 'Disabled' : 'Enabled', defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ? 'pass' : 'warning', 'Disable the WordPress theme and plugin file editor on production.' );
		$debug_display = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;
		$items[] = $this->matrix_item( 'debug_display', 'Debug display', $debug_display ? 'Enabled' : 'Disabled', $debug_display ? 'warning' : 'pass', 'Do not display PHP errors publicly on production.' );
		$remote_enabled = ! empty( $settings['remote_actions'] ) && ! empty( $settings['token_hash'] );
		$owner_id = absint( $settings['connection_owner_user_id'] ?? 0 );
		$owner = $owner_id ? get_userdata( $owner_id ) : false;
		$owner_ok = ! $remote_enabled || ( $owner && user_can( $owner, 'manage_options' ) );
		$items[] = $this->matrix_item( 'connection_owner', 'Workspace connection owner', $remote_enabled ? ( $owner_ok ? 'Active administrator' : 'Missing or unauthorized owner' ) : 'Connection disabled', $owner_ok ? 'pass' : 'critical', 'Revoke and regenerate the connection key under an active administrator.' );
		$scopes = array_values( array_filter( array_map( 'sanitize_key', (array) ( $settings['key_scopes'] ?? array() ) ) ) );
		$items[] = $this->matrix_item( 'connection_scopes', 'Connection scopes', $remote_enabled ? implode( ', ', $scopes ) : 'Not active', in_array( 'publish', $scopes, true ) ? 'critical' : 'pass', 'Ikon SEO connection keys must not contain a publish scope.' );
		$verified_at = sanitize_text_field( $settings['connection_verified_at'] ?? '' );
		$age = $verified_at ? time() - strtotime( $verified_at . ' UTC' ) : 0;
		$state = $remote_enabled && $verified_at && $age > 180 * DAY_IN_SECONDS ? 'warning' : 'pass';
		$items[] = $this->matrix_item( 'connection_age', 'Connection review age', $verified_at ?: 'Not connected', $state, 'Review and rotate long-lived connection keys at least every 180 days.' );
		$items[] = $this->matrix_item( 'crypto', 'Credential encryption', $this->crypto->available() ? 'AES-256-GCM available' : 'Unavailable', $this->crypto->available() ? 'pass' : 'critical', 'Enable OpenSSL before storing integration credentials.' );
		$states = $this->matrix_counts( $items );
		return array( 'status' => $states['critical'] ? 'critical' : ( $states['warning'] ? 'review' : 'hardened' ), 'counts' => $states, 'items' => $items );
	}

	public function create_archive( $user_id, $label = '', $archive_type = 'configuration' ) {
		global $wpdb;
		$archive_type = in_array( $archive_type, array( 'configuration', 'support' ), true ) ? $archive_type : 'configuration';
		$payload = 'support' === $archive_type ? $this->support_payload() : $this->configuration_payload();
		$payload_json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES );
		$payload_hash = hash( 'sha256', $payload_json );
		$uuid = wp_generate_uuid4();
		$now = current_time( 'mysql', true );
		$table = $this->archives_table();
		if ( ! $this->table_exists( $table ) ) {
			return new WP_Error( 'ikon_seo_archive_table', __( 'The recovery archive table is not available. Run the database upgrade first.', 'ikon-seo' ) );
		}
		$wpdb->insert(
			$table,
			array(
				'archive_uuid' => $uuid,
				'archive_type' => $archive_type,
				'label' => $label ?: ( 'support' === $archive_type ? 'Support bundle' : 'Configuration recovery point' ),
				'plugin_version' => IKON_SEO_VERSION,
				'db_version' => Ikon_SEO_Plugin::DB_VERSION,
				'payload_hash' => $payload_hash,
				'payload_json' => $payload_json,
				'status' => 'active',
				'created_by' => absint( $user_id ),
				'created_at' => $now,
			)
		);
		$id = absint( $wpdb->insert_id );
		$this->history->add(
			array(
				'category' => 'system',
				'status' => 'completed',
				'title' => 'Platform recovery archive created',
				'summary' => sprintf( '%s archive created without credentials or live content.', ucfirst( $archive_type ) ),
				'details' => array( 'archive_id' => $id, 'payload_hash' => $payload_hash, 'archive_type' => $archive_type ),
			),
			'platform_hardening',
			$user_id
		);
		return array( 'ok' => true, 'archive_id' => $id, 'archive_uuid' => $uuid, 'archive_type' => $archive_type, 'payload_hash' => $payload_hash, 'created_at' => $now, 'credentials_included' => false, 'live_content_included' => false );
	}

	public function preview_restore( $archive_id ) {
		$row = $this->archive_row( $archive_id );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		if ( 'configuration' !== $row['archive_type'] ) {
			return new WP_Error( 'ikon_seo_archive_not_configuration', __( 'Only configuration archives can be restored.', 'ikon-seo' ) );
		}
		$payload = json_decode( (string) $row['payload_json'], true );
		if ( ! is_array( $payload ) || ! isset( $payload['settings'] ) ) {
			return new WP_Error( 'ikon_seo_archive_payload', __( 'The archive payload is invalid.', 'ikon-seo' ) );
		}
		$actual_hash = hash( 'sha256', (string) $row['payload_json'] );
		if ( ! hash_equals( (string) $row['payload_hash'], $actual_hash ) ) {
			return new WP_Error( 'ikon_seo_archive_integrity', __( 'The recovery archive failed its integrity check.', 'ikon-seo' ) );
		}
		$version_ok = (string) $row['plugin_version'] === IKON_SEO_VERSION && (string) $row['db_version'] === Ikon_SEO_Plugin::DB_VERSION;
		$current = $this->safe_settings( Ikon_SEO_Plugin::settings() );
		$stored = $this->safe_settings( (array) $payload['settings'] );
		$changes = array();
		foreach ( array_unique( array_merge( array_keys( $current ), array_keys( $stored ) ) ) as $key ) {
			$before = $current[ $key ] ?? null;
			$after = $stored[ $key ] ?? null;
			if ( wp_json_encode( $before ) !== wp_json_encode( $after ) ) {
				$changes[] = array( 'key' => sanitize_key( $key ), 'current' => $this->display_value( $before ), 'archive' => $this->display_value( $after ) );
			}
		}
		return array(
			'archive_id' => absint( $row['id'] ),
			'payload_hash' => (string) $row['payload_hash'],
			'version_compatible' => $version_ok,
			'changed_keys' => $changes,
			'change_count' => count( $changes ),
			'credentials_restored' => false,
			'live_content_changed' => false,
			'requires_local_administrator' => true,
		);
	}

	public function restore_archive( $archive_id, $expected_hash, $user_id ) {
		global $wpdb;
		$preview = $this->preview_restore( $archive_id );
		if ( is_wp_error( $preview ) ) {
			return $preview;
		}
		if ( empty( $preview['version_compatible'] ) ) {
			return new WP_Error( 'ikon_seo_archive_version', __( 'The archive was created by a different plugin or database version.', 'ikon-seo' ) );
		}
		if ( ! $expected_hash || ! hash_equals( (string) $preview['payload_hash'], (string) $expected_hash ) ) {
			return new WP_Error( 'ikon_seo_archive_confirmation', __( 'The exact archive hash is required before restoring configuration.', 'ikon-seo' ) );
		}
		$row = $this->archive_row( $archive_id );
		$payload = json_decode( (string) $row['payload_json'], true );
		$this->create_archive( $user_id, 'Automatic pre-restore recovery point', 'configuration' );
		$current = Ikon_SEO_Plugin::settings();
		$stored = $this->safe_settings( (array) ( $payload['settings'] ?? array() ) );
		foreach ( $stored as $key => $value ) {
			$current[ $key ] = $value;
		}
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $current, false );
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->archives_table(), array( 'status' => 'restored', 'restored_by' => absint( $user_id ), 'restored_at' => $now ), array( 'id' => absint( $archive_id ) ) );
		$this->history->add(
			array(
				'category' => 'system',
				'status' => 'completed',
				'title' => 'Platform configuration restored',
				'summary' => sprintf( '%d non-secret configuration values were restored from an integrity-checked archive.', count( $stored ) ),
				'details' => array( 'archive_id' => absint( $archive_id ), 'payload_hash' => $expected_hash, 'live_content_changed' => false ),
			),
			'platform_hardening',
			$user_id
		);
		return array( 'ok' => true, 'archive_id' => absint( $archive_id ), 'restored_keys' => count( $stored ), 'credentials_restored' => false, 'live_content_changed' => false, 'restored_at' => $now );
	}

	public function repair_scheduler( $user_id = 0 ) {
		Ikon_SEO_Plugin::activate();
		$result = $this->production_health->run( $user_id, 'scheduler_repair' );
		$this->history->add(
			array(
				'category' => 'system',
				'status' => 'completed',
				'title' => 'Ikon SEO scheduler repaired',
				'summary' => 'Expected scheduled events were recreated without running their workloads.',
				'details' => array( 'health_status' => $result['status'] ?? 'unknown' ),
			),
			'platform_hardening',
			$user_id
		);
		return array( 'ok' => true, 'health' => $result, 'workloads_executed' => false );
	}

	public function archives( $limit = 20 ) {
		global $wpdb;
		$table = $this->archives_table();
		if ( ! $this->table_exists( $table ) ) {
			return array();
		}
		$limit = max( 1, min( 100, absint( $limit ) ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,archive_uuid,archive_type,label,plugin_version,db_version,payload_hash,status,created_by,created_at,restored_by,restored_at FROM {$table} ORDER BY created_at DESC,id DESC LIMIT %d", $limit ), ARRAY_A );
		return array_map( array( $this, 'sanitize_archive_row' ), (array) $rows );
	}

	public function upgrade_history( $limit = 20 ) {
		global $wpdb;
		$table = $this->upgrade_table();
		if ( ! $this->table_exists( $table ) ) {
			return array();
		}
		$limit = max( 1, min( 100, absint( $limit ) ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC,id DESC LIMIT %d", $limit ), ARRAY_A );
		foreach ( $rows as &$row ) {
			$row['id'] = absint( $row['id'] );
			$row['details'] = json_decode( (string) ( $row['details_json'] ?? '' ), true );
			unset( $row['details_json'] );
		}
		return $rows;
	}

	public static function record_upgrade_journal( $from_plugin, $to_plugin, $from_db, $to_db, $status, array $details = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_upgrade_journal';
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			return false;
		}
		return false !== $wpdb->insert(
			$table,
			array(
				'journal_uuid' => wp_generate_uuid4(),
				'from_plugin_version' => sanitize_text_field( $from_plugin ),
				'to_plugin_version' => sanitize_text_field( $to_plugin ),
				'from_db_version' => sanitize_text_field( $from_db ),
				'to_db_version' => sanitize_text_field( $to_db ),
				'status' => sanitize_key( $status ),
				'details_json' => wp_json_encode( $details ),
				'created_at' => current_time( 'mysql', true ),
			)
		);
	}

	public function cleanup() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		$archive_days = max( 30, min( 1095, absint( $settings['platform_archive_retention_days'] ?? 365 ) ) );
		$integrity_days = max( 30, min( 730, absint( $settings['platform_integrity_retention_days'] ?? 180 ) ) );
		$archive_cutoff = gmdate( 'Y-m-d H:i:s', time() - $archive_days * DAY_IN_SECONDS );
		$integrity_cutoff = gmdate( 'Y-m-d H:i:s', time() - $integrity_days * DAY_IN_SECONDS );
		$deleted_archives = $this->table_exists( $this->archives_table() ) ? $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->archives_table()} WHERE created_at < %s AND status <> 'restored'", $archive_cutoff ) ) : 0;
		$deleted_integrity = $this->table_exists( $this->integrity_table() ) ? $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->integrity_table()} WHERE created_at < %s", $integrity_cutoff ) ) : 0;
		return array( 'deleted_archives' => max( 0, (int) $deleted_archives ), 'deleted_integrity_runs' => max( 0, (int) $deleted_integrity ), 'archive_retention_days' => $archive_days, 'integrity_retention_days' => $integrity_days );
	}

	private function configuration_payload() {
		return array(
			'format' => 'ikon-seo-configuration-archive-v1',
			'plugin_version' => IKON_SEO_VERSION,
			'database_version' => Ikon_SEO_Plugin::DB_VERSION,
			'site_fingerprint' => hash( 'sha256', home_url( '/' ) . '|' . wp_salt( 'nonce' ) ),
			'created_at' => current_time( 'mysql', true ),
			'settings' => $this->safe_settings( Ikon_SEO_Plugin::settings() ),
			'credentials_included' => false,
			'live_content_included' => false,
		);
	}

	private function support_payload() {
		$plugins = array();
		if ( function_exists( 'get_plugins' ) ) {
			foreach ( get_plugins() as $file => $data ) {
				if ( in_array( $file, (array) get_option( 'active_plugins', array() ), true ) ) {
					$plugins[] = array( 'slug' => sanitize_text_field( $file ), 'version' => sanitize_text_field( $data['Version'] ?? '' ) );
				}
			}
		}
		$theme = wp_get_theme();
		return array(
			'format' => 'ikon-seo-support-bundle-v1',
			'created_at' => current_time( 'mysql', true ),
			'plugin_version' => IKON_SEO_VERSION,
			'database_version' => Ikon_SEO_Plugin::DB_VERSION,
			'home_host_hash' => hash( 'sha256', (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ),
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version' => PHP_VERSION,
			'database_server' => isset( $GLOBALS['wpdb'] ) && method_exists( $GLOBALS['wpdb'], 'db_version' ) ? $GLOBALS['wpdb']->db_version() : 'unknown',
			'environment_type' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			'theme' => array( 'name' => sanitize_text_field( $theme->get( 'Name' ) ), 'version' => sanitize_text_field( $theme->get( 'Version' ) ) ),
			'active_plugins' => $plugins,
			'compatibility' => $this->compatibility_matrix(),
			'security' => $this->security_posture(),
			'production_health' => $this->production_health->report( false ),
			'integrity' => $this->latest_integrity_run(),
			'settings_keys' => array_keys( $this->safe_settings( Ikon_SEO_Plugin::settings() ) ),
			'credentials_included' => false,
			'personal_data_included' => false,
			'public_urls_included' => false,
		);
	}

	private function safe_settings( array $settings ) {
		$safe = array();
		foreach ( $settings as $key => $value ) {
			$key = sanitize_key( $key );
			if ( $this->sensitive_key( $key ) ) {
				continue;
			}
			$safe[ $key ] = $this->sanitize_archive_value( $value, 0 );
		}
		ksort( $safe );
		return $safe;
	}

	private function sensitive_key( $key ) {
		return (bool) preg_match( '/(?:token_hash|token_hint|secret|password|api_key|refresh_token|access_token|private_key|client_id|connection_owner|connection_verified|connection_last_seen|remote_actions|proposal_key|governance_key|authorization)/i', (string) $key );
	}

	private function sanitize_archive_value( $value, $depth ) {
		if ( $depth > 8 ) {
			return null;
		}
		if ( is_array( $value ) ) {
			$output = array();
			foreach ( $value as $key => $item ) {
				if ( is_string( $key ) && $this->sensitive_key( $key ) ) {
					continue;
				}
				$output[ $key ] = $this->sanitize_archive_value( $item, $depth + 1 );
			}
			return $output;
		}
		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}
		if ( is_scalar( $value ) ) {
			$text = (string) $value;
			if ( strlen( $text ) > 20000 ) {
				$text = substr( $text, 0, 20000 );
			}
			return $text;
		}
		return null;
	}

	private function readiness_gate( array $health, array $integrity, array $compat, array $security, array $archives ) {
		$blocks = array();
		$warnings = array();
		if ( 'critical' === ( $health['status'] ?? '' ) ) {
			$blocks[] = 'Production health contains a critical finding.';
		}
		if ( 'verified' !== ( $integrity['overall_status'] ?? '' ) ) {
			$blocks[] = 'The packaged release manifest has not been fully verified.';
		}
		if ( 'critical' === ( $compat['status'] ?? '' ) ) {
			$blocks[] = 'The server compatibility matrix contains a critical finding.';
		}
		if ( 'critical' === ( $security['status'] ?? '' ) ) {
			$blocks[] = 'The security posture contains a critical finding.';
		}
		if ( empty( $archives ) ) {
			$warnings[] = 'No configuration recovery archive has been created.';
		} else {
			$latest = strtotime( (string) ( $archives[0]['created_at'] ?? '' ) . ' UTC' );
			if ( ! $latest || time() - $latest > 30 * DAY_IN_SECONDS ) {
				$warnings[] = 'The newest platform archive is older than 30 days.';
			}
		}
		$status = $blocks ? 'blocked' : ( $warnings || 'review' === ( $health['status'] ?? '' ) || 'review' === ( $compat['status'] ?? '' ) || 'review' === ( $security['status'] ?? '' ) ? 'review' : 'ready' );
		return array( 'status' => $status, 'blocks' => $blocks, 'warnings' => $warnings, 'manual_live_approval_required' => true, 'automatic_update_allowed' => false );
	}

	private function latest_integrity_run() {
		global $wpdb;
		$table = $this->integrity_table();
		if ( ! $this->table_exists( $table ) ) {
			return array( 'overall_status' => 'not_run', 'file_state' => 'not_run', 'signature_state' => 'not_run' );
		}
		$row = $wpdb->get_row( "SELECT * FROM {$table} ORDER BY created_at DESC,id DESC LIMIT 1", ARRAY_A );
		if ( ! $row ) {
			return array( 'overall_status' => 'not_run', 'file_state' => 'not_run', 'signature_state' => 'not_run' );
		}
		$files = json_decode( (string) $row['changed_files_json'], true );
		return array(
			'overall_status' => sanitize_key( $row['overall_status'] ),
			'signature_state' => sanitize_key( $row['signature_state'] ),
			'file_state' => sanitize_key( $row['file_state'] ),
			'manifest_hash' => sanitize_text_field( $row['manifest_hash'] ),
			'changed' => (array) ( $files['changed'] ?? array() ),
			'missing' => (array) ( $files['missing'] ?? array() ),
			'unexpected' => (array) ( $files['unexpected'] ?? array() ),
			'generated_at' => sanitize_text_field( $row['created_at'] ),
			'source' => sanitize_key( $row['source'] ),
		);
	}

	private function archive_row( $archive_id ) {
		global $wpdb;
		$table = $this->archives_table();
		if ( ! $archive_id || ! $this->table_exists( $table ) ) {
			return new WP_Error( 'ikon_seo_archive_missing', __( 'The recovery archive was not found.', 'ikon-seo' ) );
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", absint( $archive_id ) ), ARRAY_A );
		return $row ?: new WP_Error( 'ikon_seo_archive_missing', __( 'The recovery archive was not found.', 'ikon-seo' ) );
	}

	private function sanitize_archive_row( $row ) {
		return array(
			'id' => absint( $row['id'] ?? 0 ),
			'archive_uuid' => sanitize_text_field( $row['archive_uuid'] ?? '' ),
			'archive_type' => sanitize_key( $row['archive_type'] ?? '' ),
			'label' => sanitize_text_field( $row['label'] ?? '' ),
			'plugin_version' => sanitize_text_field( $row['plugin_version'] ?? '' ),
			'db_version' => sanitize_text_field( $row['db_version'] ?? '' ),
			'payload_hash' => sanitize_text_field( $row['payload_hash'] ?? '' ),
			'status' => sanitize_key( $row['status'] ?? '' ),
			'created_by' => absint( $row['created_by'] ?? 0 ),
			'created_at' => sanitize_text_field( $row['created_at'] ?? '' ),
			'restored_by' => absint( $row['restored_by'] ?? 0 ),
			'restored_at' => sanitize_text_field( $row['restored_at'] ?? '' ),
		);
	}

	private function matrix_item( $code, $label, $detail, $state, $recommendation ) {
		return array( 'code' => sanitize_key( $code ), 'label' => sanitize_text_field( $label ), 'detail' => sanitize_text_field( $detail ), 'state' => in_array( $state, array( 'pass', 'info', 'warning', 'critical' ), true ) ? $state : 'info', 'recommendation' => sanitize_text_field( $recommendation ) );
	}

	private function matrix_counts( array $items ) {
		$counts = array( 'pass' => 0, 'info' => 0, 'warning' => 0, 'critical' => 0 );
		foreach ( $items as $item ) {
			$state = $item['state'] ?? 'info';
			$counts[ $state ] = ( $counts[ $state ] ?? 0 ) + 1;
		}
		return $counts;
	}

	private function display_value( $value ) {
		if ( is_array( $value ) ) {
			return '[array:' . count( $value ) . ']';
		}
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( null === $value ) {
			return '[not set]';
		}
		$text = (string) $value;
		return strlen( $text ) > 160 ? substr( $text, 0, 157 ) . '...' : $text;
	}

	private function integrity_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_release_integrity_runs';
	}

	private function archives_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_recovery_archives';
	}

	private function upgrade_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_upgrade_journal';
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
