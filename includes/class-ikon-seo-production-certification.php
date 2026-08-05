<?php

defined( 'ABSPATH' ) || exit;

/**
 * Production certification, support lifecycle and controlled rollout governance.
 *
 * This module records evidence and approvals only. It never downloads, installs,
 * activates, publishes, edits, deletes or rolls back WordPress code or content.
 */
final class Ikon_SEO_Production_Certification {
	const CRON_HOOK = 'ikon_seo_production_certification_daily';
	const MAX_ROLLOUT_SITES = 500;

	private $platform_hardening;
	private $deployment_control;
	private $production_health;
	private $history;
	private $logger;

	public function __construct( Ikon_SEO_Platform_Hardening $platform_hardening, Ikon_SEO_Deployment_Control $deployment_control, Ikon_SEO_Production_Health $production_health, Ikon_SEO_Workspace_History $history, Ikon_SEO_Logger $logger ) {
		$this->platform_hardening = $platform_hardening;
		$this->deployment_control = $deployment_control;
		$this->production_health  = $production_health;
		$this->history            = $history;
		$this->logger             = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_monitor' ) );
	}

	public function contracts_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_support_contracts'; }
	public function certifications_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_production_certifications'; }
	public function checks_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_certification_checks'; }
	public function rollouts_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_rollout_waves'; }
	public function events_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_certification_events'; }

	public function allowed_checks() {
		return array(
			'package_integrity'        => array( 'label' => 'Signed package integrity', 'critical' => true ),
			'database_migration'       => array( 'label' => 'Database migration and upgrade journal', 'critical' => true ),
			'platform_health'          => array( 'label' => 'Platform Health readiness', 'critical' => true ),
			'recovery_restore_drill'   => array( 'label' => 'Configuration recovery restore drill', 'critical' => true ),
			'cron_reliability'         => array( 'label' => 'WP-Cron reliability and backlog', 'critical' => true ),
			'rest_authentication'      => array( 'label' => 'REST authentication, rate limits and replay controls', 'critical' => true ),
			'role_separation'          => array( 'label' => 'Writer, reviewer and approver role separation', 'critical' => true ),
			'tenant_isolation'         => array( 'label' => 'Client portal and managed-site tenant isolation', 'critical' => true ),
			'privacy_retention'        => array( 'label' => 'Privacy, retention and secret-redaction controls', 'critical' => true ),
			'hosting_performance'      => array( 'label' => 'Shared-hosting performance and bounded queues', 'critical' => false ),
			'cache_compatibility'      => array( 'label' => 'Caching and object-cache compatibility', 'critical' => false ),
			'elementor_compatibility'  => array( 'label' => 'Elementor draft compatibility', 'critical' => false ),
			'seo_plugin_compatibility' => array( 'label' => 'Rank Math or Yoast compatibility', 'critical' => false ),
			'multisite_review'         => array( 'label' => 'WordPress multisite compatibility review', 'critical' => false ),
			'runbook_documentation'    => array( 'label' => 'Administrator runbook and incident procedure', 'critical' => true ),
		);
	}

	public function normalize_support_contract( array $input ) {
		$current_version = defined( 'IKON_SEO_VERSION' ) ? IKON_SEO_VERSION : '2.0.0';
		$db_version      = class_exists( 'Ikon_SEO_Plugin' ) ? Ikon_SEO_Plugin::DB_VERSION : '39.0';
		$channels        = array_values( array_unique( array_intersect( array_map( 'sanitize_key', (array) ( $input['channels'] ?? array( 'stable' ) ) ), array( 'stable', 'candidate', 'internal' ) ) ) );
		if ( ! $channels ) { $channels = array( 'stable' ); }
		return array(
			'contract_key'             => substr( sanitize_key( $input['contract_key'] ?? 'ikon-seo-production' ), 0, 64 ),
			'label'                    => substr( sanitize_text_field( $input['label'] ?? 'Ikon SEO Production Support Contract' ), 0, 255 ),
			'product_version'          => preg_replace( '/[^0-9A-Za-z.\-+]/', '', (string) ( $input['product_version'] ?? $current_version ) ),
			'database_version'         => preg_replace( '/[^0-9.]/', '', (string) ( $input['database_version'] ?? $db_version ) ),
			'min_wordpress'            => sanitize_text_field( $input['min_wordpress'] ?? '6.4' ),
			'max_tested_wordpress'     => sanitize_text_field( $input['max_tested_wordpress'] ?? '6.8' ),
			'min_php'                  => sanitize_text_field( $input['min_php'] ?? '7.4' ),
			'max_tested_php'           => sanitize_text_field( $input['max_tested_php'] ?? '8.4' ),
			'supported_upgrade_from'   => preg_replace( '/[^0-9.]/', '', (string) ( $input['supported_upgrade_from'] ?? '1.17.0' ) ),
			'support_window_days'      => max( 90, min( 1095, absint( $input['support_window_days'] ?? 365 ) ) ),
			'recovery_drill_days'      => max( 7, min( 365, absint( $input['recovery_drill_days'] ?? 90 ) ) ),
			'channels'                 => $channels,
			'manual_distribution_only' => true,
			'automatic_installation'   => false,
			'automatic_rollback'       => false,
			'remote_publish_disabled'  => true,
			'client_data_isolated'     => true,
			'notes'                    => sanitize_textarea_field( $input['notes'] ?? '' ),
		);
	}

	public function contract_fingerprint( array $contract ) {
		return hash( 'sha256', $this->canonical_json( $this->normalize_support_contract( $contract ) ) );
	}

	public function normalize_check( $key, array $input ) {
		$allowed = $this->allowed_checks();
		$key     = sanitize_key( $key );
		if ( ! isset( $allowed[ $key ] ) ) {
			return new WP_Error( 'ikon_seo_certification_check', __( 'Unknown production-certification check.', 'ikon-seo' ) );
		}
		$status = sanitize_key( $input['status'] ?? 'pending' );
		if ( ! in_array( $status, array( 'pending', 'passed', 'failed', 'waived' ), true ) ) { $status = 'pending'; }
		if ( $allowed[ $key ]['critical'] && 'waived' === $status ) {
			return new WP_Error( 'ikon_seo_certification_critical_waiver', __( 'Critical production checks cannot be waived.', 'ikon-seo' ) );
		}
		return array(
			'check_key'  => $key,
			'label'      => $allowed[ $key ]['label'],
			'critical'   => (bool) $allowed[ $key ]['critical'],
			'status'     => $status,
			'evidence'   => sanitize_textarea_field( $input['evidence'] ?? '' ),
			'notes'      => sanitize_textarea_field( $input['notes'] ?? '' ),
			'observed_at'=> $this->normalize_datetime( $input['observed_at'] ?? current_time( 'mysql', true ) ),
		);
	}

	public function evidence_fingerprint( array $contract, array $checks, array $platform, array $deployment, array $recovery ) {
		$normalized_checks = array();
		foreach ( $checks as $key => $check ) {
			if ( is_numeric( $key ) && isset( $check['check_key'] ) ) { $key = $check['check_key']; }
			$normalized_checks[ sanitize_key( $key ) ] = array_intersect_key( (array) $check, array_flip( array( 'status', 'critical', 'evidence_hash', 'observed_at' ) ) );
		}
		ksort( $normalized_checks );
		return hash( 'sha256', $this->canonical_json( array(
			'contract'   => $this->contract_fingerprint( $contract ),
			'checks'     => $normalized_checks,
			'platform'   => array_intersect_key( $platform, array_flip( array( 'status', 'fingerprint', 'checked_at' ) ) ),
			'deployment' => array_intersect_key( $deployment, array_flip( array( 'status', 'release_fingerprint', 'verified_at' ) ) ),
			'recovery'   => array_intersect_key( $recovery, array_flip( array( 'id', 'payload_hash', 'tested_at' ) ) ),
		) ) );
	}

	public function certification_gate( array $contract, array $checks, array $platform, array $deployment, array $recovery, $environment = 'production', $now = null ) {
		$now      = null === $now ? time() : (int) $now;
		$blocks   = array();
		$warnings = array();
		$passed   = 0;
		$total    = count( $this->allowed_checks() );
		$check_map = array();
		foreach ( $checks as $key => $check ) {
			if ( is_numeric( $key ) && isset( $check['check_key'] ) ) { $key = $check['check_key']; }
			$check_map[ sanitize_key( $key ) ] = (array) $check;
		}
		foreach ( $this->allowed_checks() as $key => $definition ) {
			$status = sanitize_key( $check_map[ $key ]['status'] ?? 'pending' );
			if ( 'passed' === $status ) { $passed++; continue; }
			if ( $definition['critical'] ) { $blocks[] = $definition['label'] . ' must pass.'; }
			elseif ( 'failed' === $status ) { $warnings[] = $definition['label'] . ' failed and requires a documented production exception.'; }
			elseif ( 'pending' === $status ) { $warnings[] = $definition['label'] . ' remains pending.'; }
		}
		if ( 'approved' !== sanitize_key( $contract['status'] ?? '' ) ) { $blocks[] = 'The production support contract must be approved.'; }
		if ( ( defined( 'IKON_SEO_VERSION' ) ? IKON_SEO_VERSION : '' ) !== ( $contract['product_version'] ?? '' ) ) { $blocks[] = 'The support contract does not match the installed plugin version.'; }
		if ( class_exists( 'Ikon_SEO_Plugin' ) && Ikon_SEO_Plugin::DB_VERSION !== ( $contract['database_version'] ?? '' ) ) { $blocks[] = 'The support contract does not match the installed database version.'; }
		if ( 'ready' !== sanitize_key( $platform['status'] ?? ( $platform['readiness']['status'] ?? '' ) ) ) { $blocks[] = 'Platform Health must be ready.'; }
		if ( ! in_array( sanitize_key( $deployment['status'] ?? '' ), array( 'verified', 'closed', 'ready' ), true ) ) { $blocks[] = 'The installed release must have a verified deployment record.'; }
		if ( empty( $recovery['id'] ) || empty( $recovery['payload_hash'] ) ) { $blocks[] = 'A configuration recovery point is required.'; }
		$tested_at = ! empty( $recovery['tested_at'] ) ? strtotime( $recovery['tested_at'] . ' UTC' ) : 0;
		$max_age   = max( 7, absint( $contract['recovery_drill_days'] ?? 90 ) ) * DAY_IN_SECONDS;
		if ( ! $tested_at || $now - $tested_at > $max_age ) { $blocks[] = 'A successful recovery restore drill is required within the support-contract window.'; }
		if ( 'production' === sanitize_key( $environment ) && ! in_array( 'stable', (array) ( $contract['channels'] ?? array() ), true ) ) { $blocks[] = 'Production certification requires the stable channel.'; }
		$score = $total ? (int) round( 100 * $passed / $total ) : 0;
		return array(
			'status'                    => $blocks ? 'blocked' : 'ready',
			'score'                     => $score,
			'passed_checks'             => $passed,
			'total_checks'              => $total,
			'blocks'                    => array_values( array_unique( $blocks ) ),
			'warnings'                  => array_values( array_unique( $warnings ) ),
			'manual_distribution_only'  => true,
			'automatic_installation'    => false,
			'automatic_rollback'        => false,
			'publishes_automatically'   => false,
			'changes_live_site_content' => false,
		);
	}

	public function create_contract( array $input, $user_id = 0 ) {
		global $wpdb;
		$contract = $this->normalize_support_contract( $input );
		$now      = current_time( 'mysql', true );
		$wpdb->insert( $this->contracts_table(), array(
			'contract_key' => $contract['contract_key'], 'version' => $contract['product_version'], 'status' => 'draft',
			'contract_json' => wp_json_encode( $contract ), 'fingerprint' => $this->contract_fingerprint( $contract ),
			'created_by' => absint( $user_id ), 'approved_by' => 0, 'created_at' => $now, 'updated_at' => $now,
		), array( '%s','%s','%s','%s','%s','%d','%d','%s','%s' ) );
		$id = absint( $wpdb->insert_id );
		$this->event( 'contract_created', 'success', $id, 0, 'Production support contract created.', array(), $user_id );
		return $this->get_contract( $id );
	}

	public function approve_contract( $id, $user_id = 0 ) {
		global $wpdb;
		$contract = $this->get_contract( $id );
		if ( ! $contract ) { return new WP_Error( 'ikon_seo_contract_missing', __( 'Support contract not found.', 'ikon-seo' ) ); }
		if ( absint( $contract['created_by'] ) === absint( $user_id ) ) { return new WP_Error( 'ikon_seo_contract_separation', __( 'A different administrator must approve the support contract.', 'ikon-seo' ) ); }
		$wpdb->update( $this->contracts_table(), array( 'status'=>'approved', 'approved_by'=>absint($user_id), 'approved_at'=>current_time('mysql',true), 'updated_at'=>current_time('mysql',true) ), array( 'id'=>absint($id) ), array('%s','%d','%s','%s'), array('%d') );
		$this->event( 'contract_approved', 'success', $id, 0, 'Production support contract approved.', array(), $user_id );
		return $this->get_contract( $id );
	}

	public function get_contract( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->contracts_table()} WHERE id=%d", absint( $id ) ), ARRAY_A );
		if ( ! $row ) { return array(); }
		$row['contract'] = json_decode( $row['contract_json'], true ) ?: array();
		return $row;
	}

	public function create_certification( $contract_id, array $input, $user_id = 0 ) {
		global $wpdb;
		$contract = $this->get_contract( $contract_id );
		if ( ! $contract || 'approved' !== $contract['status'] ) { return new WP_Error( 'ikon_seo_contract_unapproved', __( 'Choose an approved production support contract.', 'ikon-seo' ) ); }
		$environment = method_exists( $this->deployment_control, 'normalize_environment' ) ? $this->deployment_control->normalize_environment( $input['environment'] ?? 'production' ) : sanitize_key( $input['environment'] ?? 'production' );
		$now = current_time( 'mysql', true );
		$key = hash( 'sha256', $contract_id . '|' . IKON_SEO_VERSION . '|' . $environment . '|' . microtime( true ) );
		$wpdb->insert( $this->certifications_table(), array(
			'certification_key'=>$key, 'contract_id'=>absint($contract_id), 'release_version'=>IKON_SEO_VERSION,
			'database_version'=>Ikon_SEO_Plugin::DB_VERSION, 'environment'=>$environment, 'status'=>'draft', 'score'=>0,
			'blocks_json'=>'[]', 'warnings_json'=>'[]', 'evidence_fingerprint'=>'', 'prepared_by'=>absint($user_id), 'approved_by'=>0,
			'created_at'=>$now, 'updated_at'=>$now,
		), array('%s','%d','%s','%s','%s','%s','%d','%s','%s','%s','%d','%d','%s','%s') );
		$id = absint( $wpdb->insert_id );
		foreach ( $this->allowed_checks() as $check_key => $definition ) {
			$wpdb->insert( $this->checks_table(), array( 'certification_id'=>$id, 'check_key'=>$check_key, 'critical'=>$definition['critical']?1:0, 'status'=>'pending', 'evidence'=>'', 'evidence_hash'=>'', 'notes'=>'', 'recorded_by'=>0, 'observed_at'=>null, 'created_at'=>$now, 'updated_at'=>$now ), array('%d','%s','%d','%s','%s','%s','%s','%d','%s','%s','%s') );
		}
		$this->event( 'certification_created', 'success', $contract_id, $id, 'Production certification created.', array(), $user_id );
		return $this->get_certification( $id );
	}

	public function record_check( $certification_id, $check_key, array $input, $user_id = 0 ) {
		global $wpdb;
		$cert = $this->get_certification( $certification_id );
		if ( ! $cert ) { return new WP_Error( 'ikon_seo_certification_missing', __( 'Production certification not found.', 'ikon-seo' ) ); }
		if ( in_array( $cert['status'], array( 'approved', 'closed' ), true ) ) { return new WP_Error( 'ikon_seo_certification_locked', __( 'Approved certification evidence is locked. Create a new certification run.', 'ikon-seo' ) ); }
		$check = $this->normalize_check( $check_key, $input );
		if ( is_wp_error( $check ) ) { return $check; }
		$evidence_hash = hash( 'sha256', $check['evidence'] . '|' . $check['notes'] . '|' . $check['observed_at'] );
		$wpdb->update( $this->checks_table(), array(
			'status'=>$check['status'], 'evidence'=>$check['evidence'], 'evidence_hash'=>$evidence_hash, 'notes'=>$check['notes'],
			'recorded_by'=>absint($user_id), 'observed_at'=>$check['observed_at'], 'updated_at'=>current_time('mysql',true),
		), array( 'certification_id'=>absint($certification_id), 'check_key'=>$check['check_key'] ), array('%s','%s','%s','%s','%d','%s','%s'), array('%d','%s') );
		$this->refresh_certification( $certification_id );
		$this->event( 'check_recorded', $check['status'], 0, $certification_id, $check['label'] . ' updated.', array( 'check_key'=>$check['check_key'] ), $user_id );
		return $this->get_certification( $certification_id );
	}

	public function refresh_certification( $id ) {
		global $wpdb;
		$cert = $this->get_certification( $id );
		if ( ! $cert ) { return array(); }
		$contract = $this->get_contract( $cert['contract_id'] );
		$checks   = $this->get_checks( $id );
		$platform_report = $this->platform_hardening->report();
		$platform = array(
			'status' => (string) ( $platform_report['readiness']['status'] ?? '' ),
			'fingerprint' => hash( 'sha256', wp_json_encode( array( $platform_report['readiness'] ?? array(), $platform_report['integrity'] ?? array(), $platform_report['compatibility'] ?? array() ) ) ),
			'checked_at' => (string) ( $platform_report['integrity']['created_at'] ?? $platform_report['integrity']['generated_at'] ?? '' ),
		);
		$deployment_report = $this->deployment_control->report();
		$deployment = array( 'status'=>'', 'release_fingerprint'=>'', 'verified_at'=>'' );
		foreach ( (array) ( $deployment_report['deployments'] ?? array() ) as $plan ) {
			if ( (string) ( $plan['target_version'] ?? '' ) === IKON_SEO_VERSION && in_array( (string) ( $plan['status'] ?? '' ), array( 'verified', 'closed' ), true ) ) {
				$deployment = array( 'status'=>(string)$plan['status'], 'release_fingerprint'=>(string)($plan['release_fingerprint']??$plan['deployment_fingerprint']??''), 'verified_at'=>(string)($plan['verified_at']??$plan['closed_at']??'') ); break;
			}
		}
		$recovery = array( 'id'=>0, 'payload_hash'=>'', 'tested_at'=>'' );
		foreach ( (array) ( $platform_report['recovery_archives'] ?? array() ) as $archive ) {
			if ( ! empty( $archive['restored_at'] ) ) { $recovery=array('id'=>absint($archive['id']??0),'payload_hash'=>(string)($archive['payload_hash']??''),'tested_at'=>(string)$archive['restored_at']); break; }
		}
		$gate = $this->certification_gate( array_merge( $contract['contract'], array('status'=>$contract['status']) ), $checks, $platform, $deployment, $recovery, $cert['environment'] );
		$fingerprint = $this->evidence_fingerprint( $contract['contract'], $checks, $platform, $deployment, $recovery );
		$status = 'ready' === $gate['status'] ? 'review_ready' : 'draft';
		$wpdb->update( $this->certifications_table(), array( 'status'=>$status, 'score'=>$gate['score'], 'blocks_json'=>wp_json_encode($gate['blocks']), 'warnings_json'=>wp_json_encode($gate['warnings']), 'evidence_fingerprint'=>$fingerprint, 'approved_by'=>0, 'approved_at'=>null, 'updated_at'=>current_time('mysql',true) ), array('id'=>absint($id)), array('%s','%d','%s','%s','%s','%d','%s','%s'), array('%d') );
		return $gate;
	}

	public function approve_certification( $id, $evidence_fingerprint, $user_id = 0 ) {
		global $wpdb;
		$this->refresh_certification( $id );
		$cert = $this->get_certification( $id );
		if ( ! $cert || 'review_ready' !== $cert['status'] ) { return new WP_Error( 'ikon_seo_certification_not_ready', __( 'The certification is not ready for approval.', 'ikon-seo' ) ); }
		if ( absint( $cert['prepared_by'] ) === absint( $user_id ) ) { return new WP_Error( 'ikon_seo_certification_separation', __( 'A different administrator must approve the production certification.', 'ikon-seo' ) ); }
		if ( ! hash_equals( (string) $cert['evidence_fingerprint'], strtolower( preg_replace('/[^a-f0-9]/i','',(string)$evidence_fingerprint) ) ) ) { return new WP_Error( 'ikon_seo_certification_stale', __( 'Certification evidence changed. Refresh and review it again.', 'ikon-seo' ) ); }
		$wpdb->update( $this->certifications_table(), array( 'status'=>'approved', 'approved_by'=>absint($user_id), 'approved_at'=>current_time('mysql',true), 'updated_at'=>current_time('mysql',true) ), array('id'=>absint($id)), array('%s','%d','%s','%s'), array('%d') );
		$this->event( 'certification_approved', 'success', 0, $id, 'Production certification approved.', array('evidence_fingerprint'=>$cert['evidence_fingerprint']), $user_id );
		return $this->get_certification( $id );
	}

	public function get_checks( $certification_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->checks_table()} WHERE certification_id=%d ORDER BY id ASC", absint($certification_id) ), ARRAY_A );
		$output = array();
		foreach ( (array) $rows as $row ) { $output[ $row['check_key'] ] = $row; }
		return $output;
	}

	public function get_certification( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->certifications_table()} WHERE id=%d", absint($id) ), ARRAY_A );
		if ( ! $row ) { return array(); }
		$row['blocks'] = json_decode( $row['blocks_json'], true ) ?: array();
		$row['warnings'] = json_decode( $row['warnings_json'], true ) ?: array();
		$row['checks'] = $this->get_checks( $id );
		return $row;
	}

	public function create_rollout( $certification_id, array $input, $user_id = 0 ) {
		global $wpdb;
		$cert = $this->get_certification( $certification_id );
		if ( ! $cert || 'approved' !== $cert['status'] ) { return new WP_Error( 'ikon_seo_rollout_certification', __( 'An approved production certification is required.', 'ikon-seo' ) ); }
		$sites = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $input['site_ids'] ?? array() ) ) ) ) );
		$limit = max( 1, min( self::MAX_ROLLOUT_SITES, absint( Ikon_SEO_Plugin::settings()['certification_max_rollout_sites'] ?? 100 ) ) );
		if ( ! $sites || count( $sites ) > $limit ) { return new WP_Error( 'ikon_seo_rollout_sites', __( 'Choose a bounded set of managed website IDs for the rollout.', 'ikon-seo' ) ); }
		$channel = sanitize_key( $input['channel'] ?? 'stable' );
		if ( 'production' === $cert['environment'] && 'stable' !== $channel ) { return new WP_Error( 'ikon_seo_rollout_channel', __( 'Production rollout waves require the stable channel.', 'ikon-seo' ) ); }
		$now = current_time('mysql',true);
		$key = hash('sha256',$certification_id.'|'.implode(',',$sites).'|'.microtime(true));
		$results = array(); foreach ( $sites as $site_id ) { $results[(string)$site_id]=array('status'=>'pending','notes'=>'','recorded_at'=>''); }
		$wpdb->insert( $this->rollouts_table(), array(
			'rollout_key'=>$key, 'certification_id'=>absint($certification_id), 'label'=>substr(sanitize_text_field($input['label']??'Production rollout'),0,255),
			'environment'=>$cert['environment'], 'channel'=>$channel, 'status'=>'draft', 'site_ids_json'=>wp_json_encode($sites), 'results_json'=>wp_json_encode($results),
			'prepared_by'=>absint($user_id), 'approved_by'=>0, 'created_at'=>$now, 'updated_at'=>$now,
		), array('%s','%d','%s','%s','%s','%s','%s','%s','%d','%d','%s','%s') );
		$id=absint($wpdb->insert_id); $this->event('rollout_created','success',0,$certification_id,'Controlled rollout wave created.',array('rollout_id'=>$id,'site_count'=>count($sites)),$user_id);
		return $this->get_rollout($id);
	}

	public function approve_rollout( $id, $user_id = 0 ) {
		global $wpdb; $wave=$this->get_rollout($id);
		if(!$wave){return new WP_Error('ikon_seo_rollout_missing',__('Rollout wave not found.','ikon-seo'));}
		if(absint($wave['prepared_by'])===absint($user_id)){return new WP_Error('ikon_seo_rollout_separation',__('A different administrator must approve the rollout wave.','ikon-seo'));}
		$cert=$this->get_certification($wave['certification_id']); if(!$cert||'approved'!==$cert['status']){return new WP_Error('ikon_seo_rollout_stale',__('The production certification is no longer approved.','ikon-seo'));}
		$wpdb->update($this->rollouts_table(),array('status'=>'approved','approved_by'=>absint($user_id),'approved_at'=>current_time('mysql',true),'updated_at'=>current_time('mysql',true)),array('id'=>absint($id)),array('%s','%d','%s','%s'),array('%d'));
		$this->event('rollout_approved','success',0,$wave['certification_id'],'Controlled rollout wave approved. Manual installation remains required.',array('rollout_id'=>absint($id)),$user_id);
		return $this->get_rollout($id);
	}

	public function record_rollout_result( $id, $site_id, $status, $notes = '', $user_id = 0 ) {
		global $wpdb; $wave=$this->get_rollout($id); if(!$wave){return new WP_Error('ikon_seo_rollout_missing',__('Rollout wave not found.','ikon-seo'));}
		if(!in_array($wave['status'],array('approved','in_progress','paused'),true)){return new WP_Error('ikon_seo_rollout_state',__('Approve the rollout wave before recording manual deployment results.','ikon-seo'));}
		$site_id=absint($site_id); if(!in_array($site_id,$wave['site_ids'],true)){return new WP_Error('ikon_seo_rollout_site',__('The website is not assigned to this rollout wave.','ikon-seo'));}
		$status=sanitize_key($status); if(!in_array($status,array('pending','successful','failed','deferred'),true)){$status='pending';}
		$results=$wave['results']; $results[(string)$site_id]=array('status'=>$status,'notes'=>sanitize_textarea_field($notes),'recorded_at'=>current_time('mysql',true),'recorded_by'=>absint($user_id));
		$wave_status='in_progress'; $states=array_column($results,'status'); if(!in_array('pending',$states,true)&&!in_array('failed',$states,true)){$wave_status='review_ready';} if(in_array('failed',$states,true)){$wave_status='paused';}
		$wpdb->update($this->rollouts_table(),array('status'=>$wave_status,'results_json'=>wp_json_encode($results),'updated_at'=>current_time('mysql',true)),array('id'=>absint($id)),array('%s','%s','%s'),array('%d'));
		$this->event('rollout_result',$status,0,$wave['certification_id'],'Manual deployment result recorded.',array('rollout_id'=>absint($id),'site_id'=>$site_id),$user_id);
		return $this->get_rollout($id);
	}

	public function close_rollout( $id, $user_id = 0 ) {
		global $wpdb; $wave=$this->get_rollout($id); if(!$wave){return new WP_Error('ikon_seo_rollout_missing',__('Rollout wave not found.','ikon-seo'));}
		$states=array_column($wave['results'],'status'); if(in_array('pending',$states,true)||in_array('failed',$states,true)){return new WP_Error('ikon_seo_rollout_incomplete',__('Resolve pending or failed website results before closing the rollout.','ikon-seo'));}
		$wpdb->update($this->rollouts_table(),array('status'=>'closed','closed_by'=>absint($user_id),'closed_at'=>current_time('mysql',true),'updated_at'=>current_time('mysql',true)),array('id'=>absint($id)),array('%s','%d','%s','%s'),array('%d'));
		$this->event('rollout_closed','success',0,$wave['certification_id'],'Controlled rollout wave closed.',array('rollout_id'=>absint($id)),$user_id); return $this->get_rollout($id);
	}

	public function get_rollout( $id ) {
		global $wpdb; $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->rollouts_table()} WHERE id=%d",absint($id)),ARRAY_A); if(!$row){return array();}
		$row['site_ids']=array_map('absint',json_decode($row['site_ids_json'],true)?:array()); $row['results']=json_decode($row['results_json'],true)?:array(); return $row;
	}

	public function report( array $args = array() ) {
		global $wpdb;
		$limit=max(1,min(100,absint($args['limit']??25)));
		$contracts=$wpdb->get_results("SELECT * FROM {$this->contracts_table()} ORDER BY id DESC LIMIT {$limit}",ARRAY_A);
		$certifications=$wpdb->get_results("SELECT * FROM {$this->certifications_table()} ORDER BY id DESC LIMIT {$limit}",ARRAY_A);
		$rollouts=$wpdb->get_results("SELECT * FROM {$this->rollouts_table()} ORDER BY id DESC LIMIT {$limit}",ARRAY_A);
		foreach($contracts as &$row){$row['contract']=json_decode($row['contract_json'],true)?:array(); unset($row['contract_json']);}
		foreach($certifications as &$row){$row['blocks']=json_decode($row['blocks_json'],true)?:array();$row['warnings']=json_decode($row['warnings_json'],true)?:array();unset($row['blocks_json'],$row['warnings_json']);}
		foreach($rollouts as &$row){$row['site_ids']=json_decode($row['site_ids_json'],true)?:array();$row['results']=json_decode($row['results_json'],true)?:array();unset($row['site_ids_json'],$row['results_json']);}
		return array('version'=>IKON_SEO_VERSION,'database_version'=>Ikon_SEO_Plugin::DB_VERSION,'contracts'=>$contracts,'certifications'=>$certifications,'rollouts'=>$rollouts,'safety'=>array('manual_distribution_only'=>true,'automatic_installation'=>false,'automatic_rollback'=>false,'remote_publishing'=>false,'public_site_lockout'=>false));
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command=sanitize_key($payload['command']??'read');
		switch($command){
			case 'read': return $this->report($payload);
			case 'create_contract': return $this->create_contract((array)($payload['contract']??array()),$user_id);
			case 'approve_contract': return $this->approve_contract(absint($payload['contract_id']??0),$user_id);
			case 'create_certification': return $this->create_certification(absint($payload['contract_id']??0),(array)($payload['certification']??array()),$user_id);
			case 'record_check': return $this->record_check(absint($payload['certification_id']??0),$payload['check_key']??'',(array)($payload['check']??array()),$user_id);
			case 'refresh_certification': $this->refresh_certification(absint($payload['certification_id']??0)); return $this->get_certification(absint($payload['certification_id']??0));
			case 'approve_certification': return $this->approve_certification(absint($payload['certification_id']??0),$payload['evidence_fingerprint']??'',$user_id);
			case 'create_rollout': return $this->create_rollout(absint($payload['certification_id']??0),(array)($payload['rollout']??array()),$user_id);
			case 'approve_rollout': return $this->approve_rollout(absint($payload['rollout_id']??0),$user_id);
			case 'record_rollout_result': return $this->record_rollout_result(absint($payload['rollout_id']??0),absint($payload['site_id']??0),$payload['status']??'pending',$payload['notes']??'',$user_id);
			case 'close_rollout': return $this->close_rollout(absint($payload['rollout_id']??0),$user_id);
		}
		return new WP_Error('ikon_seo_certification_command',__('Unknown production-certification command.','ikon-seo'));
	}

	public function scheduled_monitor() {
		global $wpdb;
		$limit=max(1,min(10,absint(Ikon_SEO_Plugin::settings()['certification_monitor_batch']??3)));
		$ids=$wpdb->get_col("SELECT id FROM {$this->certifications_table()} WHERE status IN ('draft','review_ready') ORDER BY updated_at ASC LIMIT {$limit}");
		foreach((array)$ids as $id){$this->refresh_certification(absint($id));}
		$settings=Ikon_SEO_Plugin::settings();$settings['certification_last_monitor']=current_time('mysql',true);update_option(Ikon_SEO_Plugin::OPTION_KEY,$settings,false);
	}

	private function event( $event_type, $status, $contract_id, $certification_id, $message, array $details = array(), $user_id = 0 ) {
		global $wpdb; $wpdb->insert($this->events_table(),array('event_type'=>sanitize_key($event_type),'status'=>sanitize_key($status),'contract_id'=>absint($contract_id),'certification_id'=>absint($certification_id),'message'=>sanitize_text_field($message),'details_json'=>wp_json_encode($details),'created_by'=>absint($user_id),'created_at'=>current_time('mysql',true)),array('%s','%s','%d','%d','%s','%s','%d','%s'));
		if($this->logger){$this->logger->log('production_certification',sanitize_key($status),sanitize_text_field($message),null,null,array());}
	}

	private function normalize_datetime( $value ) {
		$value=sanitize_text_field($value); $time=$value?strtotime($value.' UTC'):false; return $time?gmdate('Y-m-d H:i:s',$time):'';
	}

	private function canonical_json( $value ) {
		if(is_array($value)){if(array_keys($value)!==range(0,count($value)-1)){ksort($value);}foreach($value as $key=>$item){$value[$key]=$this->canonical_value($item);}}
		return wp_json_encode($value,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
	}
	private function canonical_value($value){if(is_array($value)){if(array_keys($value)!==range(0,count($value)-1)){ksort($value);}foreach($value as $key=>$item){$value[$key]=$this->canonical_value($item);}}return $value;}
}
