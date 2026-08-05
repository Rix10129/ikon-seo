<?php

defined( 'ABSPATH' ) || exit;

/**
 * Non-destructive staging validation and evidence packaging.
 *
 * The runner is designed to execute inside a real WordPress staging site. It
 * writes only Ikon SEO validation records and temporary self-test artefacts,
 * then removes those artefacts after verification. It never publishes or
 * changes public WordPress content.
 */
class Ikon_SEO_Staging_Validation {
	const CRON_HOOK = 'ikon_seo_staging_validation_daily';

	private $platform_hardening;
	private $production_certification;
	private $production_health;
	private $connection;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Platform_Hardening $platform_hardening,
		Ikon_SEO_Production_Certification $production_certification,
		Ikon_SEO_Production_Health $production_health,
		Ikon_SEO_Connection $connection,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->platform_hardening       = $platform_hardening;
		$this->production_certification = $production_certification;
		$this->production_health        = $production_health;
		$this->connection               = $connection;
		$this->history                  = $history;
		$this->logger                   = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_monitor' ) );
	}

	public function runs_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_staging_runs';
	}

	public function checks_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_staging_checks';
	}

	public function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_staging_events';
	}

	/**
	 * Checks are intentionally split between hard certification gates and
	 * advisory compatibility evidence. Critical checks cannot be waived.
	 */
	public function allowed_checks() {
		return array(
			'runtime_compatibility'       => array( 'label' => 'WordPress and PHP runtime compatibility', 'category' => 'runtime', 'critical' => true, 'weight' => 7 ),
			'database_schema'              => array( 'label' => 'Database migration and required tables', 'category' => 'database', 'critical' => true, 'weight' => 7 ),
			'database_crud'                => array( 'label' => 'Ikon SEO database CRUD self-test', 'category' => 'database', 'critical' => true, 'weight' => 6 ),
			'package_integrity'            => array( 'label' => 'Signed package and file integrity', 'category' => 'release', 'critical' => true, 'weight' => 7 ),
			'platform_health'              => array( 'label' => 'Platform Health readiness', 'category' => 'release', 'critical' => true, 'weight' => 7 ),
			'cron_registration'            => array( 'label' => 'Required WP-Cron schedules', 'category' => 'scheduler', 'critical' => true, 'weight' => 5 ),
			'cron_loopback'                => array( 'label' => 'Same-site WP-Cron loopback', 'category' => 'scheduler', 'critical' => true, 'weight' => 5 ),
			'rest_routes'                  => array( 'label' => 'Required REST route registration', 'category' => 'security', 'critical' => true, 'weight' => 5 ),
			'connection_security'          => array( 'label' => 'Connection-key scopes, payload limits and rate limits', 'category' => 'security', 'critical' => true, 'weight' => 6 ),
			'role_separation'              => array( 'label' => 'Two-administrator approval capability', 'category' => 'governance', 'critical' => true, 'weight' => 5 ),
			'tenant_isolation'             => array( 'label' => 'Client and managed-site tenant isolation contract', 'category' => 'security', 'critical' => true, 'weight' => 6 ),
			'privacy_redaction'            => array( 'label' => 'Recovery and support-bundle secret redaction', 'category' => 'privacy', 'critical' => true, 'weight' => 6 ),
			'filesystem_write'             => array( 'label' => 'Temporary recovery-directory write and cleanup', 'category' => 'hosting', 'critical' => true, 'weight' => 4 ),
			'same_site_http'               => array( 'label' => 'Safe same-site HTTP request', 'category' => 'hosting', 'critical' => true, 'weight' => 5 ),
			'no_live_change_contract'      => array( 'label' => 'No automatic publishing or plugin installation primitives', 'category' => 'safety', 'critical' => true, 'weight' => 7 ),
			'object_cache'                 => array( 'label' => 'WordPress object-cache round trip', 'category' => 'cache', 'critical' => false, 'weight' => 2 ),
			'elementor_compatibility'      => array( 'label' => 'Elementor controlled-draft compatibility', 'category' => 'integration', 'critical' => false, 'weight' => 3 ),
			'seo_plugin_compatibility'     => array( 'label' => 'Rank Math or Yoast compatibility', 'category' => 'integration', 'critical' => false, 'weight' => 3 ),
			'cache_plugin_compatibility'   => array( 'label' => 'Caching-plugin compatibility review', 'category' => 'integration', 'critical' => false, 'weight' => 2 ),
			'security_plugin_compatibility'=> array( 'label' => 'Security-plugin compatibility review', 'category' => 'integration', 'critical' => false, 'weight' => 2 ),
			'multisite_review'             => array( 'label' => 'WordPress Multisite review', 'category' => 'integration', 'critical' => false, 'weight' => 2 ),
			'shared_hosting_limits'        => array( 'label' => 'Bounded shared-hosting workloads', 'category' => 'performance', 'critical' => false, 'weight' => 3 ),
		);
	}

	public function start_run( array $input = array(), $user_id = 0 ) {
		global $wpdb, $wp_version;

		$environment = sanitize_key( $input['environment'] ?? ( function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production' ) );
		if ( ! in_array( $environment, array( 'local', 'development', 'staging' ), true ) ) {
			return new WP_Error(
				'ikon_seo_staging_environment',
				__( 'The automated staging runner is restricted to local, development or staging environments. Production evidence must be recorded through a controlled manual certification.', 'ikon-seo' )
			);
		}

		$now        = current_time( 'mysql', true );
		$run_uuid   = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : hash( 'sha256', microtime( true ) . '|' . mt_rand() );
		$site_hash  = $this->site_fingerprint();
		$inserted   = $wpdb->insert(
			$this->runs_table(),
			array(
				'run_uuid'             => $run_uuid,
				'environment'          => $environment,
				'status'               => 'running',
				'plugin_version'       => IKON_SEO_VERSION,
				'database_version'     => Ikon_SEO_Plugin::DB_VERSION,
				'wordpress_version'    => isset( $wp_version ) ? (string) $wp_version : '',
				'php_version'          => PHP_VERSION,
				'site_fingerprint'     => $site_hash,
				'evidence_fingerprint' => '',
				'score'                => 0,
				'blocks_json'          => wp_json_encode( array() ),
				'warnings_json'        => wp_json_encode( array() ),
				'prepared_by'          => absint( $user_id ),
				'approved_by'          => 0,
				'created_at'           => $now,
				'updated_at'           => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'ikon_seo_staging_run_insert', __( 'The staging validation run could not be created.', 'ikon-seo' ) );
		}

		$run_id = absint( $wpdb->insert_id );
		$this->event( 'run_created', 'success', $run_id, 'Staging validation run created.', array( 'environment' => $environment ), $user_id );
		$result = $this->run_checks( $run_id, array(), $user_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return $this->get_run( $run_id, true );
	}

	public function run_checks( $run_id, array $check_keys = array(), $user_id = 0 ) {
		$run = $this->get_run( $run_id, false );
		if ( ! $run ) {
			return new WP_Error( 'ikon_seo_staging_run_missing', __( 'The staging validation run was not found.', 'ikon-seo' ) );
		}
		if ( 'approved' === $run['status'] ) {
			return new WP_Error( 'ikon_seo_staging_run_locked', __( 'Approved staging evidence is immutable. Start a new run to collect updated evidence.', 'ikon-seo' ) );
		}

		$allowed = $this->allowed_checks();
		$keys    = $check_keys ? array_values( array_intersect( array_map( 'sanitize_key', $check_keys ), array_keys( $allowed ) ) ) : array_keys( $allowed );
		if ( ! $keys ) {
			return new WP_Error( 'ikon_seo_staging_checks_empty', __( 'No supported staging checks were selected.', 'ikon-seo' ) );
		}

		foreach ( $keys as $key ) {
			$definition = $allowed[ $key ];
			try {
				$result = $this->execute_check( $key, $user_id );
			} catch ( Throwable $error ) {
				$result = array(
					'status'  => 'failed',
					'message' => sprintf( 'Check raised %s: %s', get_class( $error ), $error->getMessage() ),
					'evidence'=> array( 'exception' => get_class( $error ) ),
				);
			}
			$this->save_check( $run_id, $key, $definition, $result, $user_id );
		}

		$this->refresh_gate( $run_id, $user_id );
		return $this->get_run( $run_id, true );
	}

	public function approve_run( $run_id, $expected_fingerprint, $user_id = 0 ) {
		global $wpdb;
		$run = $this->get_run( $run_id, true );
		if ( ! $run ) {
			return new WP_Error( 'ikon_seo_staging_run_missing', __( 'The staging validation run was not found.', 'ikon-seo' ) );
		}
		if ( 'review_ready' !== $run['status'] ) {
			return new WP_Error( 'ikon_seo_staging_not_ready', __( 'The staging validation must pass every critical check before approval.', 'ikon-seo' ) );
		}
		if ( absint( $run['prepared_by'] ) === absint( $user_id ) ) {
			return new WP_Error( 'ikon_seo_staging_separation', __( 'A different administrator must approve the staging evidence.', 'ikon-seo' ) );
		}
		if ( ! hash_equals( (string) $run['evidence_fingerprint'], sanitize_text_field( $expected_fingerprint ) ) ) {
			return new WP_Error( 'ikon_seo_staging_fingerprint', __( 'The staging evidence changed. Refresh the run before approval.', 'ikon-seo' ) );
		}

		$now = current_time( 'mysql', true );
		$wpdb->update(
			$this->runs_table(),
			array( 'status' => 'approved', 'approved_by' => absint( $user_id ), 'approved_at' => $now, 'updated_at' => $now ),
			array( 'id' => absint( $run_id ) ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
		$this->event( 'run_approved', 'success', $run_id, 'Exact staging evidence fingerprint approved.', array( 'fingerprint' => $run['evidence_fingerprint'] ), $user_id );
		$this->history_add( 'Staging evidence approved', 'A second administrator approved the exact staging-validation evidence fingerprint.', array( 'run_id' => absint( $run_id ), 'fingerprint' => $run['evidence_fingerprint'] ), $user_id );
		return $this->get_run( $run_id, true );
	}

	public function get_run( $run_id, $with_checks = true ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->runs_table()} WHERE id=%d", absint( $run_id ) ), ARRAY_A );
		if ( ! $row ) {
			return array();
		}
		$row['id']          = absint( $row['id'] );
		$row['score']       = absint( $row['score'] );
		$row['prepared_by'] = absint( $row['prepared_by'] );
		$row['approved_by'] = absint( $row['approved_by'] );
		$row['blocks']      = json_decode( $row['blocks_json'], true ) ?: array();
		$row['warnings']    = json_decode( $row['warnings_json'], true ) ?: array();
		unset( $row['blocks_json'], $row['warnings_json'] );
		if ( $with_checks ) {
			$row['checks'] = $this->checks_for_run( $run_id );
		}
		return $row;
	}

	public function checks_for_run( $run_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->checks_table()} WHERE run_id=%d ORDER BY id ASC", absint( $run_id ) ), ARRAY_A );
		foreach ( $rows as &$row ) {
			$row['id']         = absint( $row['id'] );
			$row['run_id']     = absint( $row['run_id'] );
			$row['critical']   = ! empty( $row['critical'] );
			$row['evidence']   = json_decode( $row['evidence_json'], true ) ?: array();
			unset( $row['evidence_json'] );
		}
		return $rows ?: array();
	}

	public function report( array $args = array() ) {
		global $wpdb;
		$limit = max( 1, min( 50, absint( $args['limit'] ?? 20 ) ) );
		$rows  = $wpdb->get_results( "SELECT * FROM {$this->runs_table()} ORDER BY id DESC LIMIT {$limit}", ARRAY_A );
		$runs  = array();
		foreach ( $rows as $row ) {
			$runs[] = $this->get_run( absint( $row['id'] ), ! empty( $args['include_checks'] ) );
		}
		return array(
			'version'          => IKON_SEO_VERSION,
			'database_version' => Ikon_SEO_Plugin::DB_VERSION,
			'environment'      => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			'runs'             => $runs,
			'allowed_checks'   => $this->allowed_checks(),
			'safety'           => array(
				'non_destructive'             => true,
				'publishes_automatically'      => false,
				'installs_plugins'             => false,
				'changes_live_site_content'    => false,
				'production_automation_blocked'=> true,
			),
		);
	}

	public function evidence_pack( $run_id ) {
		$run = $this->get_run( $run_id, true );
		if ( ! $run ) {
			return new WP_Error( 'ikon_seo_staging_run_missing', __( 'The staging validation run was not found.', 'ikon-seo' ) );
		}
		if ( 'approved' !== $run['status'] ) {
			return new WP_Error( 'ikon_seo_staging_pack_unapproved', __( 'Approve the exact staging evidence before generating a certification evidence pack.', 'ikon-seo' ) );
		}

		$checks = array();
		foreach ( $run['checks'] as $check ) {
			$checks[] = array(
				'check_key'     => $check['check_key'],
				'label'         => $check['label'],
				'category'      => $check['category'],
				'critical'      => (bool) $check['critical'],
				'status'        => $check['status'],
				'message'       => $check['message'],
				'evidence_hash' => $check['evidence_hash'],
				'observed_at'   => $check['observed_at'],
			);
		}

		return array(
			'schema'               => 'ikon-seo-staging-evidence/v1',
			'plugin_version'       => $run['plugin_version'],
			'database_version'     => $run['database_version'],
			'wordpress_version'    => $run['wordpress_version'],
			'php_version'          => $run['php_version'],
			'environment'          => $run['environment'],
			'site_fingerprint'     => $run['site_fingerprint'],
			'run_uuid'             => $run['run_uuid'],
			'evidence_fingerprint' => $run['evidence_fingerprint'],
			'score'                => $run['score'],
			'approved_at'          => $run['approved_at'],
			'checks'               => $checks,
			'certification_suggestions' => $this->certification_suggestions( $run['checks'] ),
			'limitations'          => array(
				'Recovery restore drills require a separate manual restore on staging.',
				'Administrator incident runbooks require human review.',
				'Plugin-specific visual and functional checks still require browser testing.',
			),
			'safety'               => array( 'public_urls_included' => false, 'credentials_included' => false, 'automatic_certification' => false ),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'read':
				return $this->report( $payload );
			case 'start_run':
				return $this->start_run( (array) ( $payload['run'] ?? array() ), $user_id );
			case 'run_checks':
				return $this->run_checks( absint( $payload['run_id'] ?? 0 ), (array) ( $payload['check_keys'] ?? array() ), $user_id );
			case 'approve_run':
				return $this->approve_run( absint( $payload['run_id'] ?? 0 ), $payload['evidence_fingerprint'] ?? '', $user_id );
			case 'evidence_pack':
				return $this->evidence_pack( absint( $payload['run_id'] ?? 0 ) );
		}
		return new WP_Error( 'ikon_seo_staging_command', __( 'Unknown staging-validation command.', 'ikon-seo' ) );
	}

	public function scheduled_monitor() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['staging_validation_enabled'] ) ) {
			return;
		}
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		if ( ! in_array( $environment, array( 'local', 'development', 'staging' ), true ) ) {
			return;
		}
		$limit = max( 1, min( 3, absint( $settings['staging_validation_monitor_batch'] ?? 1 ) ) );
		$ids   = $wpdb->get_col( "SELECT id FROM {$this->runs_table()} WHERE status IN ('running','review_ready','blocked') ORDER BY updated_at ASC LIMIT {$limit}" );
		foreach ( (array) $ids as $id ) {
			$run = $this->get_run( absint( $id ), false );
			if ( $run && 'approved' !== $run['status'] ) {
				$this->run_checks( absint( $id ), array( 'cron_registration', 'cron_loopback', 'same_site_http', 'platform_health' ), 0 );
			}
		}
		$settings['staging_validation_last_run'] = current_time( 'mysql', true );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
	}

	/**
	 * Pure gate logic is public so release tests can validate it without a full
	 * WordPress runtime.
	 */
	public function gate( array $checks ) {
		$definitions  = $this->allowed_checks();
		$blocks       = array();
		$warnings     = array();
		$earned       = 0;
		$total_weight = 0;

		$indexed = array();
		foreach ( $checks as $check ) {
			if ( ! empty( $check['check_key'] ) ) {
				$indexed[ $check['check_key'] ] = $check;
			}
		}

		foreach ( $definitions as $key => $definition ) {
			$weight       = max( 1, absint( $definition['weight'] ?? 1 ) );
			$total_weight += $weight;
			$check        = $indexed[ $key ] ?? array( 'status' => 'pending', 'message' => 'Check has not run.' );
			$status       = sanitize_key( $check['status'] ?? 'pending' );
			$message      = sanitize_text_field( $check['message'] ?? $definition['label'] );

			if ( 'passed' === $status ) {
				$earned += $weight;
			} elseif ( ! empty( $definition['critical'] ) ) {
				// A critical check must pass. Warning, skipped, failed and pending
				// states all block certification so missing runtime capabilities cannot
				// be treated as a partial success.
				$blocks[] = array( 'check_key' => $key, 'message' => $message );
			} elseif ( 'warning' === $status || 'skipped' === $status ) {
				$earned += (int) floor( $weight / 2 );
				$warnings[] = array( 'check_key' => $key, 'message' => $message );
			} else {
				$warnings[] = array( 'check_key' => $key, 'message' => $message );
			}
		}

		$score  = $total_weight ? (int) round( 100 * $earned / $total_weight ) : 0;
		$status = $blocks ? 'blocked' : 'review_ready';
		return array(
			'status'                   => $status,
			'score'                    => $score,
			'blocks'                   => $blocks,
			'warnings'                 => $warnings,
			'publishes_automatically'  => false,
			'installs_plugins'         => false,
			'changes_live_site_content'=> false,
		);
	}

	private function execute_check( $key, $user_id ) {
		switch ( $key ) {
			case 'runtime_compatibility': return $this->check_runtime();
			case 'database_schema': return $this->check_database_schema();
			case 'database_crud': return $this->check_database_crud( $user_id );
			case 'package_integrity': return $this->check_package_integrity( $user_id );
			case 'platform_health': return $this->check_platform_health( $user_id );
			case 'cron_registration': return $this->check_cron_registration();
			case 'cron_loopback': return $this->check_cron_loopback();
			case 'rest_routes': return $this->check_rest_routes();
			case 'connection_security': return $this->check_connection_security();
			case 'role_separation': return $this->check_role_separation();
			case 'tenant_isolation': return $this->check_tenant_isolation();
			case 'privacy_redaction': return $this->check_privacy_redaction();
			case 'filesystem_write': return $this->check_filesystem_write();
			case 'same_site_http': return $this->check_same_site_http();
			case 'no_live_change_contract': return $this->check_no_live_change_contract();
			case 'object_cache': return $this->check_object_cache();
			case 'elementor_compatibility': return $this->check_elementor();
			case 'seo_plugin_compatibility': return $this->check_seo_plugin();
			case 'cache_plugin_compatibility': return $this->check_cache_plugin();
			case 'security_plugin_compatibility': return $this->check_security_plugin();
			case 'multisite_review': return $this->check_multisite();
			case 'shared_hosting_limits': return $this->check_shared_hosting_limits();
		}
		return array( 'status' => 'failed', 'message' => 'Unknown check.', 'evidence' => array() );
	}

	private function check_runtime() {
		global $wp_version;
		$wp = isset( $wp_version ) ? (string) $wp_version : '0';
		$ok = version_compare( $wp, '6.4', '>=' ) && version_compare( PHP_VERSION, '7.4', '>=' );
		return array(
			'status'  => $ok ? 'passed' : 'failed',
			'message' => $ok ? 'WordPress and PHP satisfy the declared minimum versions.' : 'The current WordPress or PHP version is below the declared minimum.',
			'evidence'=> array( 'wordpress_version' => $wp, 'php_version' => PHP_VERSION, 'required_wordpress' => '6.4', 'required_php' => '7.4' ),
		);
	}

	private function check_database_schema() {
		global $wpdb;
		$required = array(
			$this->runs_table(),
			$this->checks_table(),
			$this->events_table(),
			$wpdb->prefix . 'ikon_seo_support_contracts',
			$wpdb->prefix . 'ikon_seo_production_certifications',
			$wpdb->prefix . 'ikon_seo_certification_checks',
			$wpdb->prefix . 'ikon_seo_rollout_waves',
			$wpdb->prefix . 'ikon_seo_release_integrity_runs',
			$wpdb->prefix . 'ikon_seo_recovery_archives',
			$wpdb->prefix . 'ikon_seo_upgrade_journal',
		);
		$missing = array();
		foreach ( $required as $table ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
			if ( $found !== $table ) {
				$missing[] = $table;
			}
		}
		$db_option = (string) get_option( 'ikon_seo_db_version', '' );
		$ok        = ! $missing && Ikon_SEO_Plugin::DB_VERSION === $db_option;
		return array(
			'status'  => $ok ? 'passed' : 'failed',
			'message' => $ok ? 'Required production and staging tables are present at the expected database version.' : 'Required database tables or the expected component version are missing.',
			'evidence'=> array( 'expected_db_version' => Ikon_SEO_Plugin::DB_VERSION, 'stored_db_version' => $db_option, 'required_table_count' => count( $required ), 'missing_tables' => array_map( array( $this, 'safe_table_name' ), $missing ) ),
		);
	}

	private function check_database_crud( $user_id ) {
		global $wpdb;
		$token = substr( hash( 'sha256', microtime( true ) . '|' . mt_rand() ), 0, 20 );
		$now   = current_time( 'mysql', true );
		$ok    = false;
		$id    = 0;
		$inserted = $wpdb->insert(
			$this->events_table(),
			array( 'event_type' => 'self_test', 'status' => 'created', 'run_id' => 0, 'message' => 'Temporary staging database self-test.', 'details_json' => wp_json_encode( array( 'token' => $token ) ), 'created_by' => absint( $user_id ), 'created_at' => $now ),
			array( '%s', '%s', '%d', '%s', '%s', '%d', '%s' )
		);
		if ( false !== $inserted ) {
			$id      = absint( $wpdb->insert_id );
			$updated = $wpdb->update( $this->events_table(), array( 'status' => 'updated' ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
			$row     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->events_table()} WHERE id=%d", $id ), ARRAY_A );
			$deleted = $wpdb->delete( $this->events_table(), array( 'id' => $id ), array( '%d' ) );
			$ok      = false !== $updated && is_array( $row ) && 'updated' === ( $row['status'] ?? '' ) && false !== $deleted;
		}
		if ( $id ) {
			$wpdb->delete( $this->events_table(), array( 'id' => $id ), array( '%d' ) );
		}
		return array( 'status' => $ok ? 'passed' : 'failed', 'message' => $ok ? 'Temporary insert, update, read and delete operations succeeded.' : 'The Ikon SEO database CRUD self-test failed.', 'evidence' => array( 'temporary_record_removed' => $ok, 'public_content_changed' => false ) );
	}

	private function check_package_integrity( $user_id ) {
		$result = $this->platform_hardening->verify_release( $user_id, 'staging_validation' );
		if ( is_wp_error( $result ) ) {
			return array( 'status' => 'failed', 'message' => $result->get_error_message(), 'evidence' => array( 'error_code' => $result->get_error_code() ) );
		}
		$ok = 'verified' === ( $result['overall_status'] ?? '' ) && 'verified' === ( $result['signature_state'] ?? '' ) && 'verified' === ( $result['file_state'] ?? '' );
		return array( 'status' => $ok ? 'passed' : 'failed', 'message' => $ok ? 'The signed package manifest and packaged-file hashes were verified.' : 'Signed package verification did not reach verified status.', 'evidence' => array( 'overall_status' => $result['overall_status'] ?? '', 'signature_state' => $result['signature_state'] ?? '', 'file_state' => $result['file_state'] ?? '', 'manifest_hash' => $result['manifest_hash'] ?? '' ) );
	}

	private function check_platform_health( $user_id ) {
		$result = $this->platform_hardening->run_full_check( $user_id, 'staging_validation' );
		if ( is_wp_error( $result ) ) {
			return array( 'status' => 'failed', 'message' => $result->get_error_message(), 'evidence' => array( 'error_code' => $result->get_error_code() ) );
		}
		$readiness = (array) ( $result['readiness'] ?? array() );
		$ok        = 'ready' === ( $readiness['status'] ?? '' );
		return array( 'status' => $ok ? 'passed' : 'failed', 'message' => $ok ? 'Platform Health reached ready state.' : 'Platform Health has unresolved blocking findings.', 'evidence' => array( 'readiness' => $readiness['status'] ?? 'unknown', 'block_count' => count( (array) ( $readiness['blocks'] ?? array() ) ), 'warning_count' => count( (array) ( $readiness['warnings'] ?? array() ) ) ) );
	}

	private function check_cron_registration() {
		$hooks = array(
			Ikon_SEO_Production_Health::CRON_HOOK,
			Ikon_SEO_Production_Health::HEARTBEAT_HOOK,
			Ikon_SEO_Platform_Hardening::CRON_HOOK,
			Ikon_SEO_Deployment_Control::CRON_HOOK,
			Ikon_SEO_Production_Certification::CRON_HOOK,
			self::CRON_HOOK,
		);
		$missing = array();
		$next    = array();
		foreach ( $hooks as $hook ) {
			$time = wp_next_scheduled( $hook );
			$next[ $hook ] = $time ? gmdate( 'c', $time ) : '';
			if ( ! $time ) {
				$missing[] = $hook;
			}
		}
		return array( 'status' => $missing ? 'failed' : 'passed', 'message' => $missing ? 'One or more required WP-Cron hooks are not scheduled.' : 'Required production and staging WP-Cron hooks are registered.', 'evidence' => array( 'scheduled' => $next, 'missing' => $missing ) );
	}

	private function check_cron_loopback() {
		$url = site_url( 'wp-cron.php?doing_wp_cron=' . rawurlencode( sprintf( '%.22F', microtime( true ) ) ) );
		if ( ! $this->is_same_site_url( $url ) ) {
			return array( 'status' => 'failed', 'message' => 'The generated WP-Cron URL is not on the current WordPress host.', 'evidence' => array() );
		}
		$response = wp_safe_remote_post( $url, array( 'timeout' => 12, 'blocking' => true, 'redirection' => 0, 'sslverify' => true, 'headers' => array( 'Cache-Control' => 'no-cache' ) ) );
		if ( is_wp_error( $response ) ) {
			return array( 'status' => 'failed', 'message' => 'WP-Cron loopback failed: ' . $response->get_error_message(), 'evidence' => array( 'error_code' => $response->get_error_code() ) );
		}
		$code = absint( wp_remote_retrieve_response_code( $response ) );
		$ok   = $code >= 200 && $code < 400;
		return array( 'status' => $ok ? 'passed' : 'failed', 'message' => $ok ? 'The same-site WP-Cron loopback request completed.' : 'The WP-Cron loopback returned an unexpected HTTP status.', 'evidence' => array( 'http_status' => $code, 'host_hash' => $this->host_hash( $url ) ) );
	}

	private function check_rest_routes() {
		$routes   = rest_get_server()->get_routes();
		$required = array( '/ikon-seo/v1/health', '/ikon-seo/v1/platform-hardening', '/ikon-seo/v1/deployment-control', '/ikon-seo/v1/production-certification', '/ikon-seo/v1/staging-validation' );
		$missing  = array_values( array_filter( $required, function( $route ) use ( $routes ) { return empty( $routes[ $route ] ); } ) );
		return array( 'status' => $missing ? 'failed' : 'passed', 'message' => $missing ? 'One or more required REST routes are not registered.' : 'Required production and staging REST routes are registered.', 'evidence' => array( 'required_count' => count( $required ), 'missing' => $missing ) );
	}

	private function check_connection_security() {
		$settings = Ikon_SEO_Plugin::settings();
		$scopes   = array_map( 'sanitize_key', (array) ( $settings['key_scopes'] ?? array() ) );
		$missing  = array_values( array_diff( array( 'read', 'draft', 'approve' ), $scopes ) );
		$payload  = absint( $settings['max_payload_kb'] ?? 0 );
		$rate     = absint( $settings['rate_limit'] ?? 0 );
		$status   = $this->connection->status( $settings );
		$ok       = ! empty( $settings['remote_actions'] ) && ! empty( $settings['token_hash'] ) && ! $missing && $payload >= 128 && $payload <= 4096 && $rate >= 10 && $rate <= 300;
		return array( 'status' => $ok ? 'passed' : 'failed', 'message' => $ok ? 'A scoped connection key, payload ceiling and hourly rate limit are configured.' : 'The staging workspace connection is incomplete or its limits are outside the supported bounds.', 'evidence' => array( 'connection_status' => $status, 'scopes' => $scopes, 'missing_scopes' => $missing, 'max_payload_kb' => $payload, 'hourly_rate_limit' => $rate, 'token_present' => ! empty( $settings['token_hash'] ) ) );
	}

	private function check_role_separation() {
		$ids = array_filter( array_map( 'absint', (array) get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 20 ) ) ) );
		$administrator_count = count( array_unique( $ids ) );
		$super_admin_count = is_multisite() && function_exists( 'get_super_admins' ) ? count( array_filter( (array) get_super_admins() ) ) : 0;
		$count = max( $administrator_count, $super_admin_count );
		return array( 'status' => $count >= 2 ? 'passed' : 'failed', 'message' => $count >= 2 ? 'At least two administrator accounts are available for approval separation.' : 'A second administrator account is required for certification approval.', 'evidence' => array( 'administrator_count' => $administrator_count, 'super_admin_count' => $super_admin_count, 'user_ids_exposed' => false ) );
	}

	private function check_tenant_isolation() {
		$source = file_get_contents( IKON_SEO_DIR . 'includes/class-ikon-seo-client-portal.php' );
		$required = array( 'can_access_request', 'portal_data_for_user', 'managed_site_id', 'sanitize_snapshot_payload' );
		$missing = array();
		foreach ( $required as $needle ) {
			if ( false === strpos( $source, $needle ) ) {
				$missing[] = $needle;
			}
		}
		$unsafe_direct_site_param = (bool) preg_match( '/rest_report\s*\([^)]*\).*?get_param\s*\(\s*[\'\"]managed_site_id/s', $source );
		$ok = ! $missing && ! $unsafe_direct_site_param;
		return array( 'status' => $ok ? 'passed' : 'failed', 'message' => $ok ? 'Client portal access remains user-assignment scoped and snapshot-sanitized.' : 'The client-portal tenant-isolation contract could not be verified.', 'evidence' => array( 'required_contract_markers' => count( $required ), 'missing_markers' => $missing, 'direct_cross_site_parameter_detected' => $unsafe_direct_site_param ) );
	}

	private function check_privacy_redaction() {
		$reflection = new ReflectionClass( $this->platform_hardening );
		if ( ! $reflection->hasMethod( 'safe_settings' ) ) {
			return array( 'status' => 'failed', 'message' => 'The platform secret-redaction method is unavailable.', 'evidence' => array() );
		}
		$method = $reflection->getMethod( 'safe_settings' );
		$method->setAccessible( true );
		$safe = $method->invoke( $this->platform_hardening, array( 'token_hash' => 'secret', 'gsc_client_secret' => 'secret', 'pagespeed_api_key' => 'secret', 'mode' => 'safe', 'nested' => array( 'password' => 'secret', 'setting' => 'retained' ) ) );
		$ok = is_array( $safe ) && ! isset( $safe['token_hash'], $safe['gsc_client_secret'], $safe['pagespeed_api_key'], $safe['nested']['password'] ) && 'safe' === ( $safe['mode'] ?? '' ) && 'retained' === ( $safe['nested']['setting'] ?? '' );
		return array( 'status' => $ok ? 'passed' : 'failed', 'message' => $ok ? 'Credential-like values were removed while safe settings were retained.' : 'The secret-redaction self-test failed.', 'evidence' => array( 'secret_fields_removed' => $ok, 'raw_values_included' => false ) );
	}

	private function check_filesystem_write() {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return array( 'status' => 'failed', 'message' => 'The WordPress uploads directory is unavailable for temporary recovery evidence.', 'evidence' => array( 'uploads_error' => sanitize_text_field( $uploads['error'] ?? '' ) ) );
		}
		$dir = trailingslashit( $uploads['basedir'] ) . 'ikon-seo-staging-validation';
		if ( ! wp_mkdir_p( $dir ) ) {
			return array( 'status' => 'failed', 'message' => 'The temporary staging-validation directory could not be created.', 'evidence' => array() );
		}
		$file    = trailingslashit( $dir ) . 'self-test-' . substr( hash( 'sha256', microtime( true ) . mt_rand() ), 0, 12 ) . '.tmp';
		$payload = function_exists( 'wp_generate_password' ) ? wp_generate_password( 48, false, false ) : hash( 'sha256', microtime( true ) );
		$written = file_put_contents( $file, $payload, LOCK_EX );
		$read    = is_file( $file ) ? file_get_contents( $file ) : false;
		$hash_ok = is_string( $read ) && hash_equals( hash( 'sha256', $payload ), hash( 'sha256', $read ) );
		$removed = ! is_file( $file ) || unlink( $file );
		if ( is_dir( $dir ) ) {
			@rmdir( $dir );
		}
		$ok = false !== $written && $hash_ok && $removed;
		return array( 'status' => $ok ? 'passed' : 'failed', 'message' => $ok ? 'A temporary file was written, verified and removed.' : 'The temporary filesystem write-and-cleanup self-test failed.', 'evidence' => array( 'bytes' => false === $written ? 0 : absint( $written ), 'hash_verified' => $hash_ok, 'temporary_file_removed' => $removed, 'path_exposed' => false ) );
	}

	private function check_same_site_http() {
		$url = home_url( '/' );
		if ( ! $this->is_same_site_url( $url ) ) {
			return array( 'status' => 'failed', 'message' => 'The WordPress home URL does not match the current site host.', 'evidence' => array() );
		}
		$response = wp_safe_remote_get( $url, array( 'timeout' => 12, 'redirection' => 0, 'sslverify' => true, 'headers' => array( 'Cache-Control' => 'no-cache' ) ) );
		if ( is_wp_error( $response ) ) {
			return array( 'status' => 'failed', 'message' => 'The safe same-site request failed: ' . $response->get_error_message(), 'evidence' => array( 'error_code' => $response->get_error_code() ) );
		}
		$code = absint( wp_remote_retrieve_response_code( $response ) );
		$ok   = $code >= 200 && $code < 400;
		return array( 'status' => $ok ? 'passed' : 'failed', 'message' => $ok ? 'The public home page responded to a safe same-site request.' : 'The same-site request returned an unexpected status.', 'evidence' => array( 'http_status' => $code, 'host_hash' => $this->host_hash( $url ) ) );
	}

	private function check_no_live_change_contract() {
		$files = array(
			IKON_SEO_DIR . 'includes/class-ikon-seo-staging-validation.php',
			IKON_SEO_DIR . 'includes/class-ikon-seo-production-certification.php',
			IKON_SEO_DIR . 'includes/class-ikon-seo-deployment-control.php',
		);
		$found = array();
		foreach ( $files as $file ) {
			$source = is_file( $file ) ? (string) file_get_contents( $file ) : '';
			foreach ( $this->find_prohibited_executables( $source ) as $primitive ) {
				$found[] = basename( $file ) . ':' . $primitive;
			}
		}
		return array( 'status' => $found ? 'failed' : 'passed', 'message' => $found ? 'A prohibited live-change primitive was detected in a deployment-governance component.' : 'Staging, certification and deployment-governance components contain no live publishing or automatic installation primitives.', 'evidence' => array( 'scanned_file_count' => count( $files ), 'prohibited_matches' => array_values( array_unique( $found ) ), 'changes_live_site_content' => false ) );
	}

	/**
	 * Token inspection avoids false positives from comments, documentation and
	 * the detector's own string constants. Only executable function calls or
	 * class construction are reported.
	 */
	private function find_prohibited_executables( $source ) {
		$blocked_functions = array( 'download_url', 'activate_plugin', 'deactivate_plugins', 'wp_publish_post', 'wp_update_post', 'wp_delete_post', 'wp_mail' );
		$blocked_classes   = array( 'plugin_upgrader', 'wp_upgrader' );
		$tokens            = token_get_all( (string) $source );
		$found             = array();
		$count             = count( $tokens );

		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( ! is_array( $token ) ) {
				continue;
			}

			if ( T_NEW === $token[0] ) {
				for ( $j = $i + 1; $j < $count; $j++ ) {
					$next = $tokens[ $j ];
					if ( is_array( $next ) && in_array( $next[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
						continue;
					}
					$name = is_array( $next ) ? strtolower( ltrim( $next[1], '\\' ) ) : '';
					if ( in_array( $name, $blocked_classes, true ) ) {
						$found[] = 'new ' . $name;
					}
					break;
				}
				continue;
			}

			if ( T_STRING !== $token[0] || ! in_array( strtolower( $token[1] ), $blocked_functions, true ) ) {
				continue;
			}

			$previous = null;
			for ( $j = $i - 1; $j >= 0; $j-- ) {
				if ( is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					continue;
				}
				$previous = $tokens[ $j ];
				break;
			}
			if ( is_array( $previous ) && in_array( $previous[0], array( T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON ), true ) ) {
				continue;
			}

			for ( $j = $i + 1; $j < $count; $j++ ) {
				$next = $tokens[ $j ];
				if ( is_array( $next ) && in_array( $next[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					continue;
				}
				if ( '(' === $next ) {
					$found[] = strtolower( $token[1] ) . '()';
				}
				break;
			}
		}

		return array_values( array_unique( $found ) );
	}

	private function check_object_cache() {
		$key     = 'staging_' . substr( hash( 'sha256', microtime( true ) . mt_rand() ), 0, 12 );
		$value   = substr( hash( 'sha256', $key ), 0, 20 );
		$set     = wp_cache_set( $key, $value, 'ikon-seo-staging', 60 );
		$found   = false;
		$read    = wp_cache_get( $key, 'ikon-seo-staging', false, $found );
		$deleted = wp_cache_delete( $key, 'ikon-seo-staging' );
		$ok      = false !== $set && $found && hash_equals( $value, (string) $read ) && false !== $deleted;
		return array( 'status' => $ok ? 'passed' : 'warning', 'message' => $ok ? 'The WordPress object cache completed a temporary set/get/delete round trip.' : 'The object-cache round trip could not be fully verified.', 'evidence' => array( 'external_object_cache' => function_exists( 'wp_using_ext_object_cache' ) ? (bool) wp_using_ext_object_cache() : false, 'temporary_key_removed' => (bool) $deleted ) );
	}

	private function check_elementor() {
		$active  = defined( 'ELEMENTOR_VERSION' ) || class_exists( '\\Elementor\\Plugin' );
		$version = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '';
		if ( ! $active ) {
			return array( 'status' => 'skipped', 'message' => 'Elementor is not active on this staging website.', 'evidence' => array( 'active' => false ) );
		}
		$ok = class_exists( '\\Elementor\\Plugin' );
		return array( 'status' => $ok ? 'passed' : 'warning', 'message' => $ok ? 'Elementor is active and its primary plugin class is available.' : 'Elementor was detected but its primary plugin class was unavailable.', 'evidence' => array( 'active' => true, 'version' => sanitize_text_field( $version ), 'controlled_drafts_only' => true ) );
	}

	private function check_seo_plugin() {
		$rank_math = defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
		$yoast     = defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' );
		if ( ! $rank_math && ! $yoast ) {
			return array( 'status' => 'skipped', 'message' => 'Neither Rank Math nor Yoast is active on this staging website.', 'evidence' => array( 'rank_math' => false, 'yoast' => false ) );
		}
		return array( 'status' => 'passed', 'message' => 'A supported SEO-plugin integration was detected.', 'evidence' => array( 'rank_math' => $rank_math, 'rank_math_version' => defined( 'RANK_MATH_VERSION' ) ? RANK_MATH_VERSION : '', 'yoast' => $yoast, 'yoast_version' => defined( 'WPSEO_VERSION' ) ? WPSEO_VERSION : '' ) );
	}

	private function check_cache_plugin() {
		$detected = array();
		$markers = array(
			'WP Rocket' => defined( 'WP_ROCKET_VERSION' ),
			'LiteSpeed Cache' => defined( 'LSCWP_V' ) || class_exists( 'LiteSpeed_Cache' ),
			'W3 Total Cache' => defined( 'W3TC_VERSION' ),
			'WP Super Cache' => defined( 'WPCACHEHOME' ),
			'Object Cache Pro' => defined( 'WP_REDIS_VERSION' ) || class_exists( 'Redis' ),
		);
		foreach ( $markers as $label => $active ) {
			if ( $active ) { $detected[] = $label; }
		}
		if ( ! $detected ) {
			return array( 'status' => 'skipped', 'message' => 'No supported caching-plugin marker was detected.', 'evidence' => array( 'detected_count' => 0 ) );
		}
		return array( 'status' => 'warning', 'message' => 'Caching software was detected. Complete a manual admin, REST and client-portal bypass review before production.', 'evidence' => array( 'detected' => $detected, 'manual_browser_review_required' => true ) );
	}

	private function check_security_plugin() {
		$detected = array();
		$markers = array(
			'Wordfence' => defined( 'WORDFENCE_VERSION' ) || class_exists( 'wordfence' ),
			'Solid Security' => defined( 'ITSEC_VERSION' ) || class_exists( 'ITSEC_Core' ),
			'Sucuri' => defined( 'SUCURI_VERSION' ) || class_exists( 'SucuriScan' ),
			'All In One WP Security' => defined( 'AIO_WP_SECURITY_PATH' ),
		);
		foreach ( $markers as $label => $active ) {
			if ( $active ) { $detected[] = $label; }
		}
		if ( ! $detected ) {
			return array( 'status' => 'skipped', 'message' => 'No common security-plugin marker was detected.', 'evidence' => array( 'detected_count' => 0 ) );
		}
		return array( 'status' => 'warning', 'message' => 'Security software was detected. Confirm that REST authentication, cron loopbacks and admin actions are not blocked.', 'evidence' => array( 'detected' => $detected, 'manual_browser_review_required' => true ) );
	}

	private function check_multisite() {
		if ( ! is_multisite() ) {
			return array( 'status' => 'skipped', 'message' => 'This staging website is a single-site WordPress installation.', 'evidence' => array( 'multisite' => false ) );
		}
		return array( 'status' => 'warning', 'message' => 'WordPress Multisite is active. Complete network-activation, per-site table and capability review manually.', 'evidence' => array( 'multisite' => true, 'network_activated' => function_exists( 'is_plugin_active_for_network' ) ? is_plugin_active_for_network( plugin_basename( IKON_SEO_FILE ) ) : false, 'manual_review_required' => true ) );
	}

	private function check_shared_hosting_limits() {
		$settings = Ikon_SEO_Plugin::settings();
		$limits = array(
			'crawler_batch_size' => array( absint( $settings['crawler_batch_size'] ?? 0 ), 1, 25 ),
			'technical_check_batch_size' => array( absint( $settings['technical_check_batch_size'] ?? 0 ), 1, 50 ),
			'workflow_runner_batch' => array( absint( $settings['workflow_runner_batch'] ?? 0 ), 1, 10 ),
			'certification_monitor_batch' => array( absint( $settings['certification_monitor_batch'] ?? 0 ), 1, 10 ),
			'staging_validation_monitor_batch' => array( absint( $settings['staging_validation_monitor_batch'] ?? 0 ), 1, 3 ),
		);
		$out_of_bounds = array();
		$current = array();
		foreach ( $limits as $key => $spec ) {
			$current[ $key ] = $spec[0];
			if ( $spec[0] < $spec[1] || $spec[0] > $spec[2] ) {
				$out_of_bounds[] = $key;
			}
		}
		return array( 'status' => $out_of_bounds ? 'warning' : 'passed', 'message' => $out_of_bounds ? 'One or more workload settings exceed the shared-hosting certification bounds.' : 'High-cost scheduled workloads are configured with bounded batch sizes.', 'evidence' => array( 'batch_settings' => $current, 'out_of_bounds' => $out_of_bounds, 'automatic_capacity_increase' => false ) );
	}

	private function save_check( $run_id, $key, array $definition, array $result, $user_id ) {
		global $wpdb;
		$status = sanitize_key( $result['status'] ?? 'failed' );
		if ( ! in_array( $status, array( 'passed', 'failed', 'warning', 'skipped', 'pending' ), true ) ) {
			$status = 'failed';
		}
		$evidence = $this->sanitize_evidence( (array) ( $result['evidence'] ?? array() ) );
		$hash     = hash( 'sha256', $this->canonical_json( $evidence ) );
		$now      = current_time( 'mysql', true );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->checks_table()} WHERE run_id=%d AND check_key=%s", absint( $run_id ), sanitize_key( $key ) ) );
		$data     = array(
			'run_id'        => absint( $run_id ),
			'check_key'     => sanitize_key( $key ),
			'label'         => sanitize_text_field( $definition['label'] ?? $key ),
			'category'      => sanitize_key( $definition['category'] ?? 'general' ),
			'critical'      => ! empty( $definition['critical'] ) ? 1 : 0,
			'status'        => $status,
			'message'       => substr( sanitize_textarea_field( $result['message'] ?? '' ), 0, 5000 ),
			'evidence_json' => wp_json_encode( $evidence ),
			'evidence_hash' => $hash,
			'observed_at'   => $now,
			'updated_by'    => absint( $user_id ),
			'updated_at'    => $now,
		);
		if ( $existing ) {
			$wpdb->update( $this->checks_table(), $data, array( 'id' => absint( $existing ) ), array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ), array( '%d' ) );
		} else {
			$data['created_at'] = $now;
			$wpdb->insert( $this->checks_table(), $data, array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ) );
		}
	}

	private function refresh_gate( $run_id, $user_id = 0 ) {
		global $wpdb;
		$run    = $this->get_run( $run_id, false );
		$checks = $this->checks_for_run( $run_id );
		$gate   = $this->gate( $checks );
		$fingerprint = $this->run_fingerprint( $run, $checks );
		$now = current_time( 'mysql', true );
		$wpdb->update(
			$this->runs_table(),
			array(
				'status'               => $gate['status'],
				'evidence_fingerprint' => $fingerprint,
				'score'                => absint( $gate['score'] ),
				'blocks_json'          => wp_json_encode( $gate['blocks'] ),
				'warnings_json'        => wp_json_encode( $gate['warnings'] ),
				'approved_by'          => 0,
				'approved_at'          => null,
				'updated_at'           => $now,
			),
			array( 'id' => absint( $run_id ) ),
			array( '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
		$this->event( 'gate_refreshed', $gate['status'], $run_id, 'Staging evidence gate refreshed.', array( 'score' => $gate['score'], 'block_count' => count( $gate['blocks'] ), 'warning_count' => count( $gate['warnings'] ), 'fingerprint' => $fingerprint ), $user_id );
		$settings = Ikon_SEO_Plugin::settings();
		$settings['staging_validation_last_run'] = $now;
		$settings['staging_validation_last_error'] = $gate['blocks'] ? sanitize_text_field( $gate['blocks'][0]['message'] ?? '' ) : '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
	}

	private function run_fingerprint( array $run, array $checks ) {
		$items = array();
		foreach ( $checks as $check ) {
			$items[] = array( 'check_key' => $check['check_key'], 'status' => $check['status'], 'evidence_hash' => $check['evidence_hash'], 'observed_at' => $check['observed_at'] );
		}
		usort( $items, function( $a, $b ) { return strcmp( $a['check_key'], $b['check_key'] ); } );
		return hash( 'sha256', $this->canonical_json( array( 'run_uuid' => $run['run_uuid'] ?? '', 'environment' => $run['environment'] ?? '', 'plugin_version' => $run['plugin_version'] ?? IKON_SEO_VERSION, 'database_version' => $run['database_version'] ?? Ikon_SEO_Plugin::DB_VERSION, 'site_fingerprint' => $run['site_fingerprint'] ?? $this->site_fingerprint(), 'checks' => $items ) ) );
	}

	private function certification_suggestions( array $checks ) {
		$map = array();
		foreach ( $checks as $check ) { $map[ $check['check_key'] ] = $check; }
		$combine = function( array $keys, $label ) use ( $map ) {
			$statuses = array(); $hashes = array(); $messages = array();
			foreach ( $keys as $key ) { if ( isset( $map[ $key ] ) ) { $statuses[] = $map[ $key ]['status']; $hashes[] = $map[ $key ]['evidence_hash']; $messages[] = $map[ $key ]['message']; } }
			$status = $statuses && ! array_diff( $statuses, array( 'passed', 'skipped' ) ) ? 'passed' : ( in_array( 'failed', $statuses, true ) ? 'failed' : 'pending' );
			return array( 'label' => $label, 'suggested_status' => $status, 'evidence_hash' => hash( 'sha256', implode( '|', $hashes ) ), 'summary' => implode( ' ', $messages ), 'requires_human_recording' => true );
		};
		return array(
			'package_integrity'          => $combine( array( 'package_integrity' ), 'Signed package integrity' ),
			'database_migration'         => $combine( array( 'database_schema', 'database_crud' ), 'Database migration and upgrade journal' ),
			'platform_health'            => $combine( array( 'platform_health' ), 'Platform Health readiness' ),
			'cron_reliability'           => $combine( array( 'cron_registration', 'cron_loopback' ), 'WP-Cron reliability and backlog' ),
			'rest_security'              => $combine( array( 'rest_routes', 'connection_security', 'same_site_http' ), 'REST authentication, limits and same-site controls' ),
			'role_separation'            => $combine( array( 'role_separation' ), 'Writer, reviewer and approver separation' ),
			'tenant_isolation'           => $combine( array( 'tenant_isolation' ), 'Client and managed-site tenant isolation' ),
			'privacy_retention'          => $combine( array( 'privacy_redaction', 'filesystem_write' ), 'Privacy, retention and secret redaction' ),
			'shared_hosting_performance' => $combine( array( 'shared_hosting_limits' ), 'Shared-hosting performance and bounded queues' ),
			'cache_compatibility'        => $combine( array( 'object_cache', 'cache_plugin_compatibility' ), 'Caching and object-cache compatibility' ),
			'elementor_compatibility'    => $combine( array( 'elementor_compatibility' ), 'Elementor controlled-draft compatibility' ),
			'seo_plugin_compatibility'   => $combine( array( 'seo_plugin_compatibility' ), 'Rank Math or Yoast compatibility' ),
			'multisite_review'           => $combine( array( 'multisite_review' ), 'WordPress Multisite review' ),
			'recovery_restore'           => array( 'label' => 'Configuration recovery restore drill', 'suggested_status' => 'pending', 'evidence_hash' => '', 'summary' => 'Complete an actual configuration restore drill on staging.', 'requires_human_recording' => true ),
			'admin_runbook'              => array( 'label' => 'Administrator runbook and incident procedure', 'suggested_status' => 'pending', 'evidence_hash' => '', 'summary' => 'Review the incident runbook and record the responsible administrators.', 'requires_human_recording' => true ),
		);
	}

	private function sanitize_evidence( array $evidence ) {
		$secret_keys = array( 'token', 'token_hash', 'api_key', 'password', 'client_secret', 'refresh_token', 'access_token', 'authorization', 'connection_key', 'email', 'phone', 'url', 'site_url', 'home_url', 'path', 'filepath' );
		foreach ( $evidence as $key => $value ) {
			$normalized = strtolower( (string) $key );
			if ( in_array( $normalized, $secret_keys, true ) || preg_match( '/(?:secret|password|token|credential|authorization|api[_-]?key|email|phone|url|path)$/i', $normalized ) ) {
				$evidence[ $key ] = '[redacted]';
			} elseif ( is_array( $value ) ) {
				$evidence[ $key ] = $this->sanitize_evidence( $value );
			} elseif ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
				$evidence[ $key ] = $value;
			} else {
				$evidence[ $key ] = substr( sanitize_text_field( (string) $value ), 0, 1000 );
			}
		}
		return $evidence;
	}

	private function site_fingerprint() {
		$installation = (string) get_option( 'ikon_seo_installation_id', '' );
		$host         = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		return hash( 'sha256', $installation . '|' . $host );
	}

	private function host_hash( $url ) {
		return hash( 'sha256', strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) );
	}

	private function is_same_site_url( $url ) {
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$url_host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return $home_host && $url_host && hash_equals( $home_host, $url_host );
	}

	private function safe_table_name( $table ) {
		global $wpdb;
		return 0 === strpos( $table, $wpdb->prefix ) ? substr( $table, strlen( $wpdb->prefix ) ) : basename( $table );
	}

	private function event( $event_type, $status, $run_id, $message, array $details = array(), $user_id = 0 ) {
		global $wpdb;
		$wpdb->insert(
			$this->events_table(),
			array( 'event_type' => sanitize_key( $event_type ), 'status' => sanitize_key( $status ), 'run_id' => absint( $run_id ), 'message' => substr( sanitize_textarea_field( $message ), 0, 5000 ), 'details_json' => wp_json_encode( $this->sanitize_evidence( $details ) ), 'created_by' => absint( $user_id ), 'created_at' => current_time( 'mysql', true ) ),
			array( '%s', '%s', '%d', '%s', '%s', '%d', '%s' )
		);
		if ( $this->logger ) {
			$this->logger->log( 'staging_validation', sanitize_key( $status ), sanitize_text_field( $message ), null, $run_id, $details );
		}
	}

	private function history_add( $title, $summary, array $details, $user_id ) {
		if ( ! $this->history ) { return; }
		$this->history->add( array( 'category' => 'system', 'status' => 'completed', 'title' => $title, 'summary' => $summary, 'details' => $details ), 'staging_validation', $user_id );
	}

	private function canonical_json( $value ) {
		return wp_json_encode( $this->canonical_value( $value ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function canonical_value( $value ) {
		if ( is_array( $value ) ) {
			if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) { ksort( $value ); }
			foreach ( $value as $key => $item ) { $value[ $key ] = $this->canonical_value( $item ); }
		}
		return $value;
	}
}
