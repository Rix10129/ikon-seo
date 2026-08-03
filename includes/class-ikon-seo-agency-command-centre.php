<?php

defined( 'ABSPATH' ) || exit;

/**
 * Read-only multi-site portfolio oversight for agency teams.
 *
 * Every connected website exposes a bounded, non-secret status snapshot through
 * a dedicated site key. The command centre polls those snapshots and stores
 * local portfolio alerts, approval queues, workload evidence, budget usage and
 * privacy-preserving content signatures. No remote page or public-profile write
 * is performed by this class.
 */
final class Ikon_SEO_Agency_Command_Centre {
	const CRON_HOOK              = 'ikon_seo_agency_command_refresh';
	const AGENT_ENABLED_OPTION   = 'ikon_seo_agency_agent_enabled';
	const AGENT_HASH_OPTION      = 'ikon_seo_agency_agent_token_hash';
	const AGENT_LAST4_OPTION     = 'ikon_seo_agency_agent_token_last4';
	const AGENT_CREATED_OPTION   = 'ikon_seo_agency_agent_token_created';
	const AGENT_ONCE_PREFIX      = 'ikon_seo_agency_agent_once_';
	const SNAPSHOT_SCHEMA        = 'ikon-seo-agency-snapshot-v1';
	const SNAPSHOT_LIMIT_BYTES   = 1048576;
	const MAX_MANAGED_SITES      = 200;
	const MAX_SIGNATURES         = 100;

	private $profile;
	private $strategy;
	private $inventory;
	private $workflow;
	private $diagnostics;
	private $search_intelligence;
	private $technical;
	private $indexation;
	private $production_health;
	private $analytics;
	private $automation;
	private $publisher;
	private $local_growth;
	private $visibility_brand;
	private $closed_loop;
	private $portfolio_quality_guard;
	private $queue;
	private $monitor;
	private $history;
	private $crypto;
	private $logger;

	public function __construct(
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Strategy $strategy,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Workflow $workflow,
		Ikon_SEO_Diagnostics $diagnostics,
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Technical_Intelligence $technical,
		Ikon_SEO_Indexation_Intelligence $indexation,
		Ikon_SEO_Production_Health $production_health,
		Ikon_SEO_Analytics $analytics,
		Ikon_SEO_Automation $automation,
		Ikon_SEO_Publisher_Intelligence $publisher,
		Ikon_SEO_Local_Growth $local_growth,
		Ikon_SEO_Visibility_Brand_Intelligence $visibility_brand,
		Ikon_SEO_Closed_Loop $closed_loop,
		Ikon_SEO_Portfolio_Quality_Guard $portfolio_quality_guard,
		Ikon_SEO_Queue $queue,
		Ikon_SEO_Monitor $monitor,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Crypto $crypto,
		Ikon_SEO_Logger $logger
	) {
		$this->profile             = $profile;
		$this->strategy            = $strategy;
		$this->inventory           = $inventory;
		$this->workflow            = $workflow;
		$this->diagnostics         = $diagnostics;
		$this->search_intelligence = $search_intelligence;
		$this->technical           = $technical;
		$this->indexation          = $indexation;
		$this->production_health   = $production_health;
		$this->analytics           = $analytics;
		$this->automation          = $automation;
		$this->publisher           = $publisher;
		$this->local_growth        = $local_growth;
		$this->visibility_brand    = $visibility_brand;
		$this->closed_loop         = $closed_loop;
		$this->portfolio_quality_guard = $portfolio_quality_guard;
		$this->queue               = $queue;
		$this->monitor             = $monitor;
		$this->history             = $history;
		$this->crypto              = $crypto;
		$this->logger              = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_refresh' ) );
	}

	public function sites_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_agency_sites';
	}

	public function snapshots_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_agency_snapshots';
	}

	public function alerts_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_agency_alerts';
	}

	public function usage_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_agency_usage';
	}

	public function scheduled_refresh() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['agency_command_enabled'] ) ) {
			return;
		}
		$hours = max( 1, min( 168, absint( $settings['agency_command_refresh_hours'] ?? 6 ) ) );
		$last  = strtotime( (string) get_option( 'ikon_seo_agency_command_last_run', '' ) . ' UTC' );
		if ( $last && time() - $last < $hours * HOUR_IN_SECONDS ) {
			return;
		}
		$this->refresh_all( absint( $settings['agency_command_batch_size'] ?? 10 ) );
		update_option( 'ikon_seo_agency_command_last_run', current_time( 'mysql', true ), false );
	}

	public function status() {
		$settings = Ikon_SEO_Plugin::settings();
		return array(
			'enabled'          => ! empty( $settings['agency_command_enabled'] ),
			'database_ready'   => $this->tables_ready(),
			'managed_sites'    => $this->tables_ready() ? $this->site_count() : 0,
			'agent'            => $this->agent_status(),
			'refresh_hours'    => max( 1, min( 168, absint( $settings['agency_command_refresh_hours'] ?? 6 ) ) ),
			'batch_size'       => max( 1, min( 50, absint( $settings['agency_command_batch_size'] ?? 10 ) ) ),
			'default_currency' => sanitize_text_field( $settings['agency_command_currency'] ?? 'USD' ),
			'read_only_remote' => true,
		);
	}

	public function agent_status() {
		return array(
			'enabled'      => (bool) get_option( self::AGENT_ENABLED_OPTION, false ),
			'configured'   => (bool) get_option( self::AGENT_HASH_OPTION, '' ),
			'last4'        => sanitize_text_field( get_option( self::AGENT_LAST4_OPTION, '' ) ),
			'created_at'   => sanitize_text_field( get_option( self::AGENT_CREATED_OPTION, '' ) ),
			'endpoint'     => rest_url( Ikon_SEO_REST::NAMESPACE . '/agency-snapshot' ),
			'authentication' => 'Bearer site key',
			'read_only'    => true,
		);
	}

	public function generate_agent_key( $user_id = 0 ) {
		try {
			$raw = random_bytes( 32 );
		} catch ( Exception $exception ) {
			return new WP_Error( 'ikon_seo_agency_key_random', __( 'A secure site key could not be generated.', 'ikon-seo' ) );
		}
		$key = 'ikon_agency_' . rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
		update_option( self::AGENT_HASH_OPTION, wp_hash_password( $key ), false );
		update_option( self::AGENT_LAST4_OPTION, substr( $key, -4 ), false );
		update_option( self::AGENT_CREATED_OPTION, current_time( 'mysql', true ), false );
		update_option( self::AGENT_ENABLED_OPTION, 1, false );
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( $user_id ) {
			set_transient( self::AGENT_ONCE_PREFIX . $user_id, $key, 10 * MINUTE_IN_SECONDS );
		}
		$this->logger->log( 'agency_agent_key', 'success', 'A new read-only agency site key was generated.' );
		return array( 'key' => $key, 'last4' => substr( $key, -4 ), 'endpoint' => rest_url( Ikon_SEO_REST::NAMESPACE . '/agency-snapshot' ) );
	}

	public function consume_agent_key( $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( ! $user_id ) {
			return '';
		}
		$key = get_transient( self::AGENT_ONCE_PREFIX . $user_id );
		if ( $key ) {
			delete_transient( self::AGENT_ONCE_PREFIX . $user_id );
		}
		return is_string( $key ) ? $key : '';
	}

	public function revoke_agent_key() {
		delete_option( self::AGENT_HASH_OPTION );
		delete_option( self::AGENT_LAST4_OPTION );
		delete_option( self::AGENT_CREATED_OPTION );
		update_option( self::AGENT_ENABLED_OPTION, 0, false );
		$this->logger->log( 'agency_agent_key', 'success', 'The read-only agency site key was revoked.' );
		return $this->agent_status();
	}

	public function set_agent_enabled( $enabled ) {
		if ( $enabled && ! get_option( self::AGENT_HASH_OPTION, '' ) ) {
			return new WP_Error( 'ikon_seo_agency_agent_key', __( 'Generate a site key before enabling the agency connection.', 'ikon-seo' ) );
		}
		update_option( self::AGENT_ENABLED_OPTION, $enabled ? 1 : 0, false );
		return $this->agent_status();
	}

	public function verify_agent_request( WP_REST_Request $request ) {
		if ( ! get_option( self::AGENT_ENABLED_OPTION, false ) ) {
			return new WP_Error( 'ikon_seo_agency_agent_disabled', __( 'The agency site connection is disabled.', 'ikon-seo' ), array( 'status' => 503 ) );
		}
		$hash = (string) get_option( self::AGENT_HASH_OPTION, '' );
		if ( ! $hash ) {
			return new WP_Error( 'ikon_seo_agency_agent_unconfigured', __( 'The agency site connection is not configured.', 'ikon-seo' ), array( 'status' => 503 ) );
		}
		$provided = '';
		$authorization = trim( (string) $request->get_header( 'authorization' ) );
		if ( 0 === stripos( $authorization, 'Bearer ' ) ) {
			$provided = trim( substr( $authorization, 7 ) );
		}
		if ( ! $provided ) {
			$provided = trim( (string) $request->get_header( 'x-ikon-agency-key' ) );
		}
		if ( ! $provided || ! wp_check_password( $provided, $hash ) ) {
			return new WP_Error( 'ikon_seo_agency_agent_unauthorized', __( 'A valid agency site key is required.', 'ikon-seo' ), array( 'status' => 401 ) );
		}
		$rate_key = 'ikon_seo_agency_rate_' . gmdate( 'YmdH' ) . '_' . substr( hash( 'sha256', $provided ), 0, 12 );
		$used = (int) get_transient( $rate_key );
		if ( $used >= 30 ) {
			return new WP_Error( 'ikon_seo_agency_agent_rate', __( 'The hourly agency snapshot limit has been reached.', 'ikon-seo' ), array( 'status' => 429 ) );
		}
		set_transient( $rate_key, $used + 1, HOUR_IN_SECONDS + 60 );
		return true;
	}

	public function snapshot() {
		$profile   = $this->profile->get();
		$strategy  = $this->strategy->get();
		$inventory = $this->inventory->status();
		$technical = $this->technical->status();
		$indexation = $this->indexation->status();
		$production_health = $this->production_health->report( false );
		$search    = $this->search_intelligence->status();
		$analytics = $this->analytics->status();
		$workflow  = $this->automation->summary( 25 );
		$publisher = $this->publisher->status();
		$local     = $this->local_growth->status();
		$visibility = $this->visibility_brand->status();
		$closed_loop = $this->closed_loop->status();
		$portfolio_quality = $this->portfolio_quality_guard->status();
		$reviews   = $this->workflow->reviews( 25 );
		$queue     = $this->queue->counts();
		$monitor   = $this->monitor->summary();
		$diagnostics = $this->stored_diagnostics_summary();
		$signatures = $this->publisher->export_signature_bundle( self::MAX_SIGNATURES );
		$signatures = is_array( $signatures ) ? array_slice( (array) ( $signatures['items'] ?? array() ), 0, self::MAX_SIGNATURES ) : array();

		$approval_tasks = array();
		foreach ( (array) ( $workflow['tasks'] ?? array() ) as $task ) {
			if ( in_array( $task['status'] ?? '', array( 'pending_approval', 'approved' ), true ) ) {
				$approval_tasks[] = array(
					'id'            => absint( $task['id'] ?? 0 ),
					'title'         => sanitize_text_field( $task['title'] ?? '' ),
					'workflow_name' => sanitize_text_field( $task['workflow_name'] ?? '' ),
					'status'        => sanitize_key( $task['status'] ?? '' ),
					'priority'      => absint( $task['priority'] ?? 0 ),
					'due_at'        => sanitize_text_field( $task['due_at'] ?? '' ),
				);
			}
		}

		$snapshot = array(
			'format'       => self::SNAPSHOT_SCHEMA,
			'generated_at' => current_time( 'mysql', true ),
			'site' => array(
				'name'           => sanitize_text_field( get_bloginfo( 'name' ) ),
				'url'            => home_url( '/' ),
				'admin_url'      => admin_url( 'admin.php?page=ikon-seo' ),
				'plugin_version' => IKON_SEO_VERSION,
				'wordpress'      => get_bloginfo( 'version' ),
				'timezone'       => wp_timezone_string(),
				'profile_id'     => $this->profile->fingerprint(),
			),
			'strategy' => array(
				'configured'       => ! empty( $strategy['configured'] ),
				'mode'             => sanitize_key( $strategy['mode'] ?? '' ),
				'mode_label'       => sanitize_text_field( $strategy['mode_label'] ?? '' ),
				'primary_goal'     => sanitize_key( $strategy['primary_goal'] ?? '' ),
				'primary_goal_label'=> sanitize_text_field( $strategy['primary_goal_label'] ?? '' ),
				'readiness'        => absint( $strategy['readiness']['score'] ?? 0 ),
				'quality_threshold'=> absint( $strategy['quality_gate_threshold'] ?? 0 ),
			),
			'connections' => array(
				'search_console' => array( 'connected' => ! empty( $search['connected'] ), 'last_sync' => sanitize_text_field( $search['last_sync'] ?? '' ) ),
				'analytics'      => array( 'connected' => ! empty( $analytics['connected'] ), 'last_sync' => sanitize_text_field( $analytics['last_sync'] ?? '' ) ),
				'business_profile'=> array( 'connected' => ! empty( $local['gbp_connected'] ), 'last_sync' => sanitize_text_field( $local['last_sync'] ?? '' ) ),
			),
			'inventory' => array(
				'scanned'      => ! empty( $inventory['scanned'] ),
				'generated_at' => sanitize_text_field( $inventory['generated_at'] ?? '' ),
				'summary'      => $this->sanitize_numeric_map( (array) ( $inventory['summary'] ?? array() ) ),
			),
			'diagnostics' => $diagnostics,
			'technical' => array(
				'ready'          => ! empty( $technical['ready'] ),
				'total_urls'     => absint( $technical['total_urls'] ?? 0 ),
				'failed_urls'    => absint( $technical['failed_urls'] ?? 0 ),
				'redirects'      => absint( $technical['redirects'] ?? 0 ),
				'orphans'        => absint( $technical['orphans'] ?? 0 ),
				'internal_links' => absint( $technical['internal_links'] ?? 0 ),
				'last_check'     => sanitize_text_field( $technical['last_check'] ?? '' ),
				'last_discovery' => sanitize_text_field( $technical['last_discovery'] ?? '' ),
			),
			'indexation' => array(
				'connected'            => ! empty( $indexation['connected'] ),
				'total_urls'           => absint( $indexation['total_urls'] ?? 0 ),
				'indexed_urls'         => absint( $indexation['indexed_urls'] ?? 0 ),
				'not_indexed'          => absint( $indexation['not_indexed'] ?? 0 ),
				'canonical_mismatches' => absint( $indexation['canonical_mismatches'] ?? 0 ),
				'blocked_or_failed'    => absint( $indexation['blocked_or_failed'] ?? 0 ),
				'queued'               => absint( $indexation['queued'] ?? 0 ),
				'stale'                => absint( $indexation['stale'] ?? 0 ),
				'last_inspection'      => sanitize_text_field( $indexation['last_inspection'] ?? '' ),
			),
			'production_health' => array(
				'status'       => sanitize_key( $production_health['status'] ?? 'not_run' ),
				'counts'       => $this->sanitize_numeric_map( (array) ( $production_health['counts'] ?? array() ) ),
				'generated_at' => sanitize_text_field( $production_health['generated_at'] ?? '' ),
			),
			'search' => array(
				'connected' => ! empty( $search['connected'] ),
				'rows'      => absint( $search['rows'] ?? 0 ),
				'queries'   => absint( $search['queries'] ?? 0 ),
				'pages'     => absint( $search['pages'] ?? 0 ),
				'clusters'  => absint( $search['clusters'] ?? 0 ),
				'last_sync' => sanitize_text_field( $search['last_sync'] ?? '' ),
			),
			'workflow' => array(
				'workflows' => count( (array) ( $workflow['workflows'] ?? array() ) ),
				'counts'    => $this->sanitize_numeric_map( (array) ( $workflow['counts'] ?? array() ) ),
				'overdue'   => absint( $workflow['overdue'] ?? 0 ),
				'approval_tasks' => array_slice( $approval_tasks, 0, 25 ),
			),
			'publisher' => array(
				'enabled' => ! empty( $publisher['enabled'] ),
				'counts'  => $this->sanitize_numeric_map( (array) ( $publisher['counts'] ?? array() ) ),
			),
			'local_growth' => array(
				'enabled'          => ! empty( $local['enabled'] ),
				'locations'        => absint( $local['locations'] ?? 0 ),
				'linked_locations' => absint( $local['linked_locations'] ?? 0 ),
				'last_sync'        => sanitize_text_field( $local['last_sync'] ?? '' ),
			),
			'visibility_brand' => array(
				'enabled'           => ! empty( $visibility['enabled'] ),
				'observations'      => absint( $visibility['observations'] ?? 0 ),
				'brand_mentions'    => absint( $visibility['brand_mentions'] ?? 0 ),
				'unlinked_mentions' => absint( $visibility['unlinked_mentions'] ?? 0 ),
				'competitors'       => absint( $visibility['competitors'] ?? 0 ),
				'last_snapshot_at'  => sanitize_text_field( $visibility['last_snapshot_at'] ?? '' ),
			),
			'closed_loop' => array(
				'enabled'          => ! empty( $closed_loop['enabled'] ),
				'safe_mode'        => ! empty( $closed_loop['safe_mode'] ),
				'counts'           => $this->sanitize_numeric_map( (array) ( $closed_loop['counts'] ?? array() ) ),
				'due_measurements' => absint( $closed_loop['due_measurements'] ?? 0 ),
				'last_plan_refresh'=> sanitize_text_field( $closed_loop['last_plan_refresh'] ?? '' ),
			),
			'portfolio_quality' => array(
				'enabled'           => ! empty( $portfolio_quality['enabled'] ),
				'local_profiles'    => absint( $portfolio_quality['local_profiles'] ?? 0 ),
				'portfolio_sites'   => absint( $portfolio_quality['portfolio_sites'] ?? 0 ),
				'open_findings'     => absint( $portfolio_quality['open_findings'] ?? 0 ),
				'blocking_findings' => absint( $portfolio_quality['blocking_findings'] ?? 0 ),
				'blocked_pipeline'  => absint( $portfolio_quality['blocked_pipeline'] ?? 0 ),
				'last_evaluation'   => sanitize_text_field( $portfolio_quality['last_evaluation'] ?? '' ),
			),
			'approvals' => array(
				'review_drafts' => array_map(
					function( $review ) {
						return array(
							'draft_id'       => absint( $review['draft_id'] ?? 0 ),
							'draft_title'    => sanitize_text_field( $review['draft_title'] ?? '' ),
							'quality_score'  => absint( $review['quality_score'] ?? 0 ),
							'quality_status' => sanitize_key( $review['quality_status'] ?? '' ),
							'edit_url'       => esc_url_raw( $review['draft_edit_url'] ?? '' ),
						);
					},
					array_slice( $reviews, 0, 25 )
				),
				'workflow_tasks' => array_slice( $approval_tasks, 0, 25 ),
			),
			'page_plans' => $this->sanitize_numeric_map( (array) $queue ),
			'monitoring' => array(
				'counts' => $this->sanitize_numeric_map( (array) ( $monitor['counts'] ?? array() ) ),
			),
			'portfolio_signatures' => array_map(
				function( $item ) {
					return array(
						'content_url'   => esc_url_raw( $item['content_url'] ?? '' ),
						'content_title' => sanitize_text_field( $item['content_title'] ?? '' ),
						'content_type'  => sanitize_key( $item['content_type'] ?? '' ),
						'signature'     => array_slice( array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $item['signature'] ?? array() ) ) ) ), 0, 160 ),
						'topics'        => array_slice( array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $item['topics'] ?? array() ) ) ) ), 0, 30 ),
					);
				},
				$signatures
			),
			'safety' => array(
				'remote_writes' => false,
				'secrets_included' => false,
				'full_content_included' => false,
			),
		);
		$json = wp_json_encode( $snapshot );
		if ( strlen( (string) $json ) > self::SNAPSHOT_LIMIT_BYTES ) {
			$snapshot['portfolio_signatures'] = array_slice( $snapshot['portfolio_signatures'], 0, 25 );
			$snapshot['approvals']['review_drafts'] = array_slice( $snapshot['approvals']['review_drafts'], 0, 10 );
			$snapshot['approvals']['workflow_tasks'] = array_slice( $snapshot['approvals']['workflow_tasks'], 0, 10 );
			$snapshot['truncated'] = true;
		}
		return $snapshot;
	}

	public function save_settings( array $input ) {
		$settings = Ikon_SEO_Plugin::settings();
		$settings['agency_command_enabled']       = ! empty( $input['enabled'] ) ? 1 : 0;
		$settings['agency_command_refresh_hours'] = max( 1, min( 168, absint( $input['refresh_hours'] ?? 6 ) ) );
		$settings['agency_command_batch_size']    = max( 1, min( 50, absint( $input['batch_size'] ?? 10 ) ) );
		$settings['agency_command_currency']      = strtoupper( substr( sanitize_text_field( $input['currency'] ?? 'USD' ), 0, 3 ) );
		$settings['agency_command_default_budget']= max( 0, (float) ( $input['default_budget'] ?? 0 ) );
		$settings['agency_command_brand_name']    = sanitize_text_field( $input['brand_name'] ?? 'Ikon SEO' );
		$settings['agency_command_logo_url']      = esc_url_raw( $input['logo_url'] ?? '' );
		$settings['agency_command_client_footer'] = sanitize_textarea_field( $input['client_footer'] ?? '' );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		return $this->status();
	}

	public function add_site( array $input, $user_id = 0 ) {
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_agency_tables', __( 'The Agency Command Centre tables are unavailable. Reactivate Ikon SEO to repair the database.', 'ikon-seo' ) );
		}
		if ( $this->site_count() >= self::MAX_MANAGED_SITES ) {
			return new WP_Error( 'ikon_seo_agency_limit', __( 'The managed website limit has been reached.', 'ikon-seo' ) );
		}
		$site_url = $this->normalize_site_url( $input['site_url'] ?? '' );
		if ( is_wp_error( $site_url ) ) {
			return $site_url;
		}
		$key = trim( (string) ( $input['site_key'] ?? '' ) );
		if ( strlen( $key ) < 24 ) {
			return new WP_Error( 'ikon_seo_agency_site_key', __( 'Enter the complete read-only agency site key.', 'ikon-seo' ) );
		}
		$encrypted = $this->crypto->encrypt( $key );
		if ( is_wp_error( $encrypted ) ) {
			return $encrypted;
		}
		$snapshot = $this->fetch_snapshot( $site_url, $key );
		if ( is_wp_error( $snapshot ) ) {
			return $snapshot;
		}
		global $wpdb;
		$now = current_time( 'mysql', true );
		$site_hash = hash( 'sha256', strtolower( untrailingslashit( $site_url ) ) );
		$exists = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->sites_table()} WHERE site_hash=%s", $site_hash ) ) );
		if ( $exists ) {
			return new WP_Error( 'ikon_seo_agency_site_exists', __( 'This website is already connected to the command centre.', 'ikon-seo' ) );
		}
		$result = $wpdb->insert(
			$this->sites_table(),
			array(
				'client_name'    => sanitize_text_field( $input['client_name'] ?? '' ),
				'group_name'     => sanitize_text_field( $input['group_name'] ?? '' ),
				'site_name'      => sanitize_text_field( $input['site_name'] ?? ( $snapshot['site']['name'] ?? '' ) ),
				'site_url'       => $site_url,
				'site_hash'      => $site_hash,
				'encrypted_key'  => $encrypted,
				'enabled'        => 1,
				'status'         => 'connected',
				'monthly_budget' => max( 0, (float) ( $input['monthly_budget'] ?? Ikon_SEO_Plugin::settings()['agency_command_default_budget'] ?? 0 ) ),
				'currency'       => strtoupper( substr( sanitize_text_field( $input['currency'] ?? Ikon_SEO_Plugin::settings()['agency_command_currency'] ?? 'USD' ), 0, 3 ) ),
				'report_label'   => sanitize_text_field( $input['report_label'] ?? '' ),
				'last_snapshot_at'=> $now,
				'last_error'     => '',
				'created_by'     => absint( $user_id ),
				'created_at'     => $now,
				'updated_at'     => $now,
			),
			array( '%s','%s','%s','%s','%s','%s','%d','%s','%f','%s','%s','%s','%s','%d','%s','%s' )
		);
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_agency_site_store', __( 'The managed website could not be saved.', 'ikon-seo' ) );
		}
		$site_id = absint( $wpdb->insert_id );
		$this->store_snapshot( $site_id, $snapshot );
		$this->sync_alerts( $site_id, $snapshot );
		$this->history->add(
			array(
				'category' => 'agency',
				'status'   => 'completed',
				'title'    => 'Website added to Agency Command Centre',
				'summary'  => sprintf( '%s was connected for read-only portfolio monitoring.', sanitize_text_field( $snapshot['site']['name'] ?? $site_url ) ),
				'details'  => array( 'site_id' => $site_id, 'site_url' => $site_url, 'client_name' => sanitize_text_field( $input['client_name'] ?? '' ) ),
			),
			'agency',
			absint( $user_id )
		);
		return $this->get_site( $site_id );
	}

	public function update_site( $site_id, array $input ) {
		$site = $this->get_site_row( $site_id );
		if ( ! $site ) {
			return new WP_Error( 'ikon_seo_agency_site_missing', __( 'The managed website was not found.', 'ikon-seo' ) );
		}
		$client_name  = sanitize_text_field( $input['client_name'] ?? '' );
		$group_name   = sanitize_text_field( $input['group_name'] ?? '' );
		$site_name    = sanitize_text_field( $input['site_name'] ?? '' );
		$report_label = sanitize_text_field( $input['report_label'] ?? '' );
		$currency     = strtoupper( substr( sanitize_text_field( $input['currency'] ?? '' ), 0, 3 ) );
		$budget_value = isset( $input['monthly_budget'] ) && '' !== (string) $input['monthly_budget']
			? max( 0, (float) $input['monthly_budget'] )
			: (float) $site['monthly_budget'];
		$data = array(
			'client_name'    => '' !== $client_name ? $client_name : $site['client_name'],
			'group_name'     => '' !== $group_name ? $group_name : $site['group_name'],
			'site_name'      => '' !== $site_name ? $site_name : $site['site_name'],
			'enabled'        => ! empty( $input['enabled'] ) ? 1 : 0,
			'monthly_budget' => $budget_value,
			'currency'       => '' !== $currency ? $currency : $site['currency'],
			'report_label'   => '' !== $report_label ? $report_label : $site['report_label'],
			'updated_at'     => current_time( 'mysql', true ),
		);
		if ( ! empty( $input['site_key'] ) ) {
			$encrypted = $this->crypto->encrypt( trim( (string) $input['site_key'] ) );
			if ( is_wp_error( $encrypted ) ) {
				return $encrypted;
			}
			$data['encrypted_key'] = $encrypted;
		}
		global $wpdb;
		$wpdb->update( $this->sites_table(), $data, array( 'id' => absint( $site_id ) ) );
		return $this->get_site( $site_id );
	}

	public function delete_site( $site_id ) {
		global $wpdb;
		$site_id = absint( $site_id );
		$site = $this->get_site_row( $site_id );
		if ( ! $site ) {
			return new WP_Error( 'ikon_seo_agency_site_missing', __( 'The managed website was not found.', 'ikon-seo' ) );
		}
		$wpdb->delete( $this->snapshots_table(), array( 'site_id' => $site_id ), array( '%d' ) );
		$wpdb->delete( $this->alerts_table(), array( 'site_id' => $site_id ), array( '%d' ) );
		$wpdb->delete( $this->usage_table(), array( 'site_id' => $site_id ), array( '%d' ) );
		$wpdb->delete( $this->sites_table(), array( 'id' => $site_id ), array( '%d' ) );
		$this->logger->log( 'agency_site_delete', 'success', 'A managed website was removed from the command centre.' );
		return array( 'deleted' => true, 'site_id' => $site_id );
	}

	public function refresh_site( $site_id ) {
		$site = $this->get_site_row( $site_id );
		if ( ! $site ) {
			return new WP_Error( 'ikon_seo_agency_site_missing', __( 'The managed website was not found.', 'ikon-seo' ) );
		}
		if ( empty( $site['enabled'] ) ) {
			return new WP_Error( 'ikon_seo_agency_site_disabled', __( 'The managed website is paused.', 'ikon-seo' ) );
		}
		$key = $this->crypto->decrypt( $site['encrypted_key'] );
		if ( is_wp_error( $key ) ) {
			$this->record_site_error( $site_id, $key->get_error_message() );
			return $key;
		}
		$snapshot = $this->fetch_snapshot( $site['site_url'], $key );
		if ( is_wp_error( $snapshot ) ) {
			$this->record_site_error( $site_id, $snapshot->get_error_message() );
			$this->sync_connection_alert( $site_id, $snapshot->get_error_message() );
			return $snapshot;
		}
		$this->store_snapshot( $site_id, $snapshot );
		$this->sync_alerts( $site_id, $snapshot );
		global $wpdb;
		$wpdb->update(
			$this->sites_table(),
			array(
				'site_name'       => sanitize_text_field( $snapshot['site']['name'] ?? $site['site_name'] ),
				'status'          => 'connected',
				'last_snapshot_at'=> current_time( 'mysql', true ),
				'last_error'      => '',
				'updated_at'      => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $site_id ) )
		);
		return $this->get_site( $site_id );
	}

	public function refresh_all( $limit = 10 ) {
		global $wpdb;
		$limit = max( 1, min( 50, absint( $limit ) ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT id FROM {$this->sites_table()} WHERE enabled=1 ORDER BY COALESCE(last_snapshot_at,'1970-01-01') ASC, id ASC LIMIT %d", $limit ),
			ARRAY_A
		);
		$result = array( 'attempted' => 0, 'refreshed' => 0, 'failed' => 0, 'errors' => array() );
		foreach ( $rows ?: array() as $row ) {
			$result['attempted']++;
			$refresh = $this->refresh_site( absint( $row['id'] ) );
			if ( is_wp_error( $refresh ) ) {
				$result['failed']++;
				$result['errors'][] = array( 'site_id' => absint( $row['id'] ), 'message' => $refresh->get_error_message() );
			} else {
				$result['refreshed']++;
			}
		}
		return $result;
	}

	public function record_usage( $site_id, array $input, $user_id = 0 ) {
		if ( ! $this->get_site_row( $site_id ) ) {
			return new WP_Error( 'ikon_seo_agency_site_missing', __( 'The managed website was not found.', 'ikon-seo' ) );
		}
		$amount = max( 0, (float) ( $input['amount'] ?? 0 ) );
		$units  = max( 0, (float) ( $input['units'] ?? 0 ) );
		if ( $amount <= 0 && $units <= 0 ) {
			return new WP_Error( 'ikon_seo_agency_usage_amount', __( 'Enter a cost amount or usage quantity.', 'ikon-seo' ) );
		}
		global $wpdb;
		$wpdb->insert(
			$this->usage_table(),
			array(
				'site_id'    => absint( $site_id ),
				'category'   => sanitize_key( $input['category'] ?? 'research' ),
				'amount'     => $amount,
				'currency'   => strtoupper( substr( sanitize_text_field( $input['currency'] ?? 'USD' ), 0, 3 ) ),
				'units'      => $units,
				'note'       => sanitize_textarea_field( $input['note'] ?? '' ),
				'event_date' => sanitize_text_field( $input['event_date'] ?? gmdate( 'Y-m-d' ) ),
				'created_by' => absint( $user_id ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d','%s','%f','%s','%f','%s','%s','%d','%s' )
		);
		$site = $this->get_site( $site_id );
		$this->sync_budget_alert( $site_id, $site );
		return $site;
	}

	public function resolve_alert( $alert_id, $user_id = 0 ) {
		global $wpdb;
		$alert = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->alerts_table()} WHERE id=%d", absint( $alert_id ) ), ARRAY_A );
		if ( ! $alert ) {
			return new WP_Error( 'ikon_seo_agency_alert_missing', __( 'The portfolio alert was not found.', 'ikon-seo' ) );
		}
		$wpdb->update(
			$this->alerts_table(),
			array( 'status' => 'resolved', 'resolved_at' => current_time( 'mysql', true ), 'resolved_by' => absint( $user_id ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => absint( $alert_id ) )
		);
		return array( 'resolved' => true, 'alert_id' => absint( $alert_id ) );
	}

	public function summary( $limit = 100 ) {
		if ( ! $this->tables_ready() ) {
			return array( 'ready' => false, 'status' => $this->status(), 'sites' => array(), 'alerts' => array(), 'approvals' => array(), 'benchmarks' => array(), 'duplication_risks' => array() );
		}
		$sites = $this->managed_sites( $limit );
		$alerts = $this->alerts( 'open', 200 );
		$approvals = $this->approval_queue( $sites );
		$benchmarks = $this->benchmarks( $sites );
		$duplication = $this->duplication_risks( $sites );
		$metrics = array(
			'total_sites'      => count( $sites ),
			'connected'        => 0,
			'attention'        => 0,
			'stale'            => 0,
			'open_alerts'      => count( $alerts ),
			'critical_alerts'  => 0,
			'approvals'        => count( $approvals ),
			'overdue_tasks'    => 0,
			'budget_risk'      => 0,
			'duplication_risk' => count( $duplication ),
		);
		foreach ( $sites as $site ) {
			if ( 'connected' === ( $site['status'] ?? '' ) ) { $metrics['connected']++; }
			if ( in_array( $site['attention'] ?? '', array( 'high', 'critical' ), true ) ) { $metrics['attention']++; }
			if ( ! empty( $site['stale'] ) ) { $metrics['stale']++; }
			$metrics['overdue_tasks'] += absint( $site['snapshot']['workflow']['overdue'] ?? 0 );
			if ( (float) ( $site['budget']['percent'] ?? 0 ) >= 90 ) { $metrics['budget_risk']++; }
		}
		foreach ( $alerts as $alert ) {
			if ( 'critical' === ( $alert['severity'] ?? '' ) ) { $metrics['critical_alerts']++; }
		}
		return array(
			'ready'             => true,
			'status'            => $this->status(),
			'metrics'           => $metrics,
			'sites'             => $sites,
			'alerts'            => $alerts,
			'approvals'         => $approvals,
			'benchmarks'        => $benchmarks,
			'duplication_risks' => $duplication,
			'generated_at'      => current_time( 'mysql', true ),
			'safety'            => array(
				'remote_monitoring_only' => true,
				'central_approval_writes' => false,
				'portfolio_secrets_exposed' => false,
			),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'refresh_site':
				$result = $this->refresh_site( absint( $payload['site_id'] ?? 0 ) );
				break;
			case 'refresh_all':
				$result = $this->refresh_all( absint( $payload['limit'] ?? 10 ) );
				break;
			case 'record_usage':
				$result = $this->record_usage( absint( $payload['site_id'] ?? 0 ), (array) ( $payload['usage'] ?? array() ), $user_id );
				break;
			case 'resolve_alert':
				$result = $this->resolve_alert( absint( $payload['alert_id'] ?? 0 ), $user_id );
				break;
			case 'read':
			default:
				$result = array( 'read_only' => true );
				break;
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return array( 'command' => $command, 'result' => $result, 'command_centre' => $this->summary( absint( $payload['limit'] ?? 100 ) ) );
	}

	public function client_report( $site_id ) {
		$site = $this->get_site( $site_id );
		if ( ! $site ) {
			return new WP_Error( 'ikon_seo_agency_site_missing', __( 'The managed website was not found.', 'ikon-seo' ) );
		}
		$snapshot = (array) ( $site['snapshot'] ?? array() );
		$settings = Ikon_SEO_Plugin::settings();
		return array(
			'brand' => array(
				'name'   => sanitize_text_field( $site['report_label'] ?: ( $settings['agency_command_brand_name'] ?? 'Ikon SEO' ) ),
				'logo'   => esc_url_raw( $settings['agency_command_logo_url'] ?? '' ),
				'footer' => sanitize_textarea_field( $settings['agency_command_client_footer'] ?? '' ),
			),
			'client' => sanitize_text_field( $site['client_name'] ),
			'site'   => array( 'name' => sanitize_text_field( $site['site_name'] ), 'url' => esc_url_raw( $site['site_url'] ) ),
			'period' => array( 'generated_at' => current_time( 'mysql', true ), 'snapshot_at' => sanitize_text_field( $site['last_snapshot_at'] ) ),
			'summary' => array(
				'strategy_mode'      => sanitize_text_field( $snapshot['strategy']['mode_label'] ?? '' ),
				'strategy_readiness' => absint( $snapshot['strategy']['readiness'] ?? 0 ),
				'pages_diagnosed'    => absint( $snapshot['diagnostics']['pages_diagnosed'] ?? 0 ),
				'critical_findings'  => absint( $snapshot['diagnostics']['blockers']['critical'] ?? 0 ),
				'high_findings'      => absint( $snapshot['diagnostics']['blockers']['high'] ?? 0 ),
				'failed_urls'        => absint( $snapshot['technical']['failed_urls'] ?? 0 ),
				'orphan_pages'       => absint( $snapshot['technical']['orphans'] ?? 0 ),
				'pending_approvals'  => count( (array) ( $snapshot['approvals']['review_drafts'] ?? array() ) ) + count( (array) ( $snapshot['approvals']['workflow_tasks'] ?? array() ) ),
				'overdue_tasks'      => absint( $snapshot['workflow']['overdue'] ?? 0 ),
				'brand_mentions'      => absint( $snapshot['visibility_brand']['brand_mentions'] ?? 0 ),
				'unlinked_mentions'   => absint( $snapshot['visibility_brand']['unlinked_mentions'] ?? 0 ),
			),
			'connections' => (array) ( $snapshot['connections'] ?? array() ),
			'top_findings' => array_slice( (array) ( $snapshot['diagnostics']['top_blockers'] ?? array() ), 0, 8, true ),
			'open_alerts'  => array_values( array_filter( $this->alerts( 'open', 100 ), function( $alert ) use ( $site_id ) { return absint( $alert['site_id'] ?? 0 ) === absint( $site_id ); } ) ),
			'budget'       => $site['budget'],
			'safety_note'  => 'This report summarises stored evidence and workflow status. It does not guarantee rankings and does not confirm that every external dataset is complete.',
		);
	}

	public function render_report_html( $site_id ) {
		$report = $this->client_report( $site_id );
		if ( is_wp_error( $report ) ) {
			return $report;
		}
		$summary = $report['summary'];
		$alerts  = $report['open_alerts'];
		$brand   = $report['brand'];
		ob_start();
		?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html( $report['site']['name'] ); ?> SEO Report</title><style>body{font-family:Arial,sans-serif;color:#243b53;background:#f4f7fa;margin:0;padding:32px}.report{max-width:960px;margin:auto;background:#fff;padding:40px;border-radius:16px}.head{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid #e6edf3;padding-bottom:24px}.logo{max-height:54px;max-width:200px}.metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:28px 0}.metric{border:1px solid #d9e2ec;border-radius:10px;padding:18px}.metric strong{display:block;font-size:28px}.metric span{color:#627d98}.alert{border-left:4px solid #d64545;background:#fff7f7;padding:12px 16px;margin:10px 0}.alert.medium{border-color:#d89b1d;background:#fffaf0}.foot{border-top:1px solid #d9e2ec;margin-top:32px;padding-top:18px;color:#627d98;font-size:13px}@media(max-width:700px){.metrics{grid-template-columns:repeat(2,1fr)}.head{display:block}}</style></head><body><main class="report"><header class="head"><div><?php if ( $brand['logo'] ) : ?><img class="logo" src="<?php echo esc_url( $brand['logo'] ); ?>" alt=""><?php endif; ?><h1><?php echo esc_html( $brand['name'] ); ?></h1><p><?php echo esc_html( $report['client'] ); ?></p></div><div><h2><?php echo esc_html( $report['site']['name'] ); ?></h2><p><?php echo esc_html( $report['site']['url'] ); ?><br>Snapshot: <?php echo esc_html( $report['period']['snapshot_at'] ); ?></p></div></header><section class="metrics"><div class="metric"><strong><?php echo esc_html( $summary['strategy_readiness'] ); ?>%</strong><span>Strategy readiness</span></div><div class="metric"><strong><?php echo esc_html( $summary['pages_diagnosed'] ); ?></strong><span>Pages diagnosed</span></div><div class="metric"><strong><?php echo esc_html( $summary['critical_findings'] + $summary['high_findings'] ); ?></strong><span>Priority findings</span></div><div class="metric"><strong><?php echo esc_html( $summary['pending_approvals'] ); ?></strong><span>Pending approvals</span></div></section><h2>Technical and workflow status</h2><ul><li>Failed URLs: <?php echo esc_html( $summary['failed_urls'] ); ?></li><li>Orphan pages: <?php echo esc_html( $summary['orphan_pages'] ); ?></li><li>Overdue tasks: <?php echo esc_html( $summary['overdue_tasks'] ); ?></li><li>Stored brand mentions: <?php echo esc_html( $summary['brand_mentions'] ); ?></li><li>Unlinked mentions requiring review: <?php echo esc_html( $summary['unlinked_mentions'] ); ?></li><li>Monthly tracked budget: <?php echo esc_html( $report['budget']['currency'] . ' ' . number_format_i18n( $report['budget']['used'], 2 ) . ' / ' . number_format_i18n( $report['budget']['limit'], 2 ) ); ?></li></ul><h2>Open attention items</h2><?php if ( ! $alerts ) : ?><p>No open portfolio alerts were stored in the latest snapshot.</p><?php else : ?><?php foreach ( $alerts as $alert ) : ?><div class="alert <?php echo esc_attr( $alert['severity'] ); ?>"><strong><?php echo esc_html( $alert['title'] ); ?></strong><p><?php echo esc_html( $alert['summary'] ); ?></p></div><?php endforeach; ?><?php endif; ?><footer class="foot"><p><?php echo esc_html( $report['safety_note'] ); ?></p><?php if ( $brand['footer'] ) : ?><p><?php echo esc_html( $brand['footer'] ); ?></p><?php endif; ?></footer></main></body></html>
		<?php
		return ob_get_clean();
	}

	public function portfolio_csv() {
		$sites = $this->managed_sites( self::MAX_MANAGED_SITES );
		$rows = array();
		$rows[] = array( 'Client','Group','Website','URL','Status','Snapshot','Mode','Strategy readiness','Critical findings','High findings','Failed URLs','Orphans','Approvals','Overdue tasks','Brand mentions','Unlinked mentions','Budget used','Budget limit','Currency' );
		foreach ( $sites as $site ) {
			$s = (array) ( $site['snapshot'] ?? array() );
			$rows[] = array(
				$site['client_name'], $site['group_name'], $site['site_name'], $site['site_url'], $site['status'], $site['last_snapshot_at'],
				$s['strategy']['mode_label'] ?? '', absint( $s['strategy']['readiness'] ?? 0 ), absint( $s['diagnostics']['blockers']['critical'] ?? 0 ), absint( $s['diagnostics']['blockers']['high'] ?? 0 ),
				absint( $s['technical']['failed_urls'] ?? 0 ), absint( $s['technical']['orphans'] ?? 0 ), count( (array) ( $s['approvals']['review_drafts'] ?? array() ) ) + count( (array) ( $s['approvals']['workflow_tasks'] ?? array() ) ), absint( $s['workflow']['overdue'] ?? 0 ), absint( $s['visibility_brand']['brand_mentions'] ?? 0 ), absint( $s['visibility_brand']['unlinked_mentions'] ?? 0 ),
				$site['budget']['used'], $site['budget']['limit'], $site['budget']['currency'],
			);
		}
		$stream = fopen( 'php://temp', 'w+' );
		foreach ( $rows as $row ) { fputcsv( $stream, $row ); }
		rewind( $stream );
		$csv = stream_get_contents( $stream );
		fclose( $stream );
		return $csv;
	}

	private function managed_sites( $limit ) {
		global $wpdb;
		$limit = max( 1, min( self::MAX_MANAGED_SITES, absint( $limit ) ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->sites_table()} ORDER BY client_name, site_name LIMIT %d", $limit ), ARRAY_A );
		$result = array();
		foreach ( $rows ?: array() as $row ) {
			$result[] = $this->public_site( $row );
		}
		return $result;
	}

	private function get_site( $site_id ) {
		$row = $this->get_site_row( $site_id );
		return $row ? $this->public_site( $row ) : null;
	}

	private function get_site_row( $site_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->sites_table()} WHERE id=%d", absint( $site_id ) ), ARRAY_A );
	}

	private function public_site( array $row ) {
		$snapshot = $this->latest_snapshot( absint( $row['id'] ) );
		$budget   = $this->budget_summary( absint( $row['id'] ), (float) $row['monthly_budget'], sanitize_text_field( $row['currency'] ) );
		$age      = $this->age_hours( $row['last_snapshot_at'] );
		$stale    = null === $age || $age > max( 12, absint( Ikon_SEO_Plugin::settings()['agency_command_refresh_hours'] ?? 6 ) * 3 );
		$attention = $this->site_attention( $row, $snapshot, $budget, $stale );
		return array(
			'id'               => absint( $row['id'] ),
			'client_name'      => sanitize_text_field( $row['client_name'] ),
			'group_name'       => sanitize_text_field( $row['group_name'] ),
			'site_name'        => sanitize_text_field( $row['site_name'] ),
			'site_url'         => esc_url_raw( $row['site_url'] ),
			'enabled'          => (bool) $row['enabled'],
			'status'           => sanitize_key( $row['status'] ),
			'report_label'     => sanitize_text_field( $row['report_label'] ),
			'last_snapshot_at' => sanitize_text_field( $row['last_snapshot_at'] ),
			'last_error'       => sanitize_text_field( $row['last_error'] ),
			'snapshot_age_hours'=> null === $age ? null : round( $age, 1 ),
			'stale'            => $stale,
			'attention'        => $attention,
			'budget'           => $budget,
			'snapshot'         => $snapshot,
			'created_at'       => sanitize_text_field( $row['created_at'] ),
			'updated_at'       => sanitize_text_field( $row['updated_at'] ),
		);
	}

	private function latest_snapshot( $site_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT snapshot_json,captured_at FROM {$this->snapshots_table()} WHERE site_id=%d ORDER BY captured_at DESC,id DESC LIMIT 1", absint( $site_id ) ), ARRAY_A );
		if ( ! $row ) { return array(); }
		$data = json_decode( $row['snapshot_json'], true );
		$data = is_array( $data ) ? $data : array();
		$data['_captured_at'] = sanitize_text_field( $row['captured_at'] );
		return $data;
	}

	private function fetch_snapshot( $site_url, $key ) {
		$endpoint = trailingslashit( $site_url ) . 'wp-json/' . Ikon_SEO_REST::NAMESPACE . '/agency-snapshot';
		$response = wp_safe_remote_get(
			$endpoint,
			array(
				'timeout'     => 25,
				'redirection' => 2,
				'headers'     => array( 'Authorization' => 'Bearer ' . $key, 'Accept' => 'application/json' ),
				'user-agent'  => 'Ikon SEO Agency Command Centre/' . IKON_SEO_VERSION,
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ikon_seo_agency_fetch', sprintf( __( 'The website snapshot could not be retrieved: %s', 'ikon-seo' ), $response->get_error_message() ) );
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( strlen( $body ) > self::SNAPSHOT_LIMIT_BYTES ) {
			return new WP_Error( 'ikon_seo_agency_snapshot_size', __( 'The remote snapshot exceeded the safe response limit.', 'ikon-seo' ) );
		}
		$data = json_decode( $body, true );
		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $data ) ? sanitize_text_field( $data['message'] ?? '' ) : '';
			return new WP_Error( 'ikon_seo_agency_remote_status', $message ?: sprintf( __( 'The website returned HTTP %d.', 'ikon-seo' ), $code ) );
		}
		if ( ! is_array( $data ) || self::SNAPSHOT_SCHEMA !== ( $data['format'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_agency_snapshot_format', __( 'The website returned an unsupported agency snapshot.', 'ikon-seo' ) );
		}
		$expected_host = strtolower( (string) wp_parse_url( $site_url, PHP_URL_HOST ) );
		$actual_host   = strtolower( (string) wp_parse_url( $data['site']['url'] ?? '', PHP_URL_HOST ) );
		if ( ! $actual_host || $expected_host !== $actual_host ) {
			return new WP_Error( 'ikon_seo_agency_snapshot_host', __( 'The returned snapshot did not match the connected website host.', 'ikon-seo' ) );
		}
		return $this->sanitize_snapshot( $data );
	}

	private function store_snapshot( $site_id, array $snapshot ) {
		global $wpdb;
		$json = wp_json_encode( $snapshot );
		$hash = hash( 'sha256', $json );
		$captured = current_time( 'mysql', true );
		$exists = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->snapshots_table()} WHERE site_id=%d AND snapshot_hash=%s", absint( $site_id ), $hash ) ) );
		if ( ! $exists ) {
			$wpdb->insert(
				$this->snapshots_table(),
				array( 'site_id' => absint( $site_id ), 'snapshot_hash' => $hash, 'snapshot_json' => $json, 'captured_at' => $captured ),
				array( '%d','%s','%s','%s' )
			);
		}
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$this->snapshots_table()} WHERE site_id=%d ORDER BY captured_at DESC,id DESC LIMIT 200", absint( $site_id ) ) );
		if ( count( $ids ) > 24 ) {
			$delete = array_slice( array_map( 'absint', $ids ), 24 );
			$placeholders = implode( ',', array_fill( 0, count( $delete ), '%d' ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->snapshots_table()} WHERE id IN ({$placeholders})", $delete ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	private function alerts( $status = 'open', $limit = 100 ) {
		global $wpdb;
		$limit = max( 1, min( 500, absint( $limit ) ) );
		$status = sanitize_key( $status );
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT a.*,s.site_name,s.client_name,s.site_url FROM {$this->alerts_table()} a LEFT JOIN {$this->sites_table()} s ON s.id=a.site_id WHERE a.status=%s ORDER BY FIELD(a.severity,'critical','high','medium','low'),a.last_seen_at DESC LIMIT %d", $status, $limit ),
			ARRAY_A
		);
		return array_map(
			function( $row ) {
				return array(
					'id' => absint( $row['id'] ), 'site_id' => absint( $row['site_id'] ), 'site_name' => sanitize_text_field( $row['site_name'] ), 'client_name' => sanitize_text_field( $row['client_name'] ), 'site_url' => esc_url_raw( $row['site_url'] ),
					'category' => sanitize_key( $row['category'] ), 'severity' => sanitize_key( $row['severity'] ), 'title' => sanitize_text_field( $row['title'] ), 'summary' => sanitize_textarea_field( $row['summary'] ), 'status' => sanitize_key( $row['status'] ), 'source' => sanitize_key( $row['source'] ), 'first_seen_at' => sanitize_text_field( $row['first_seen_at'] ), 'last_seen_at' => sanitize_text_field( $row['last_seen_at'] ),
				);
			},
			$rows ?: array()
		);
	}

	private function approval_queue( array $sites ) {
		$items = array();
		foreach ( $sites as $site ) {
			$snapshot = (array) ( $site['snapshot'] ?? array() );
			foreach ( (array) ( $snapshot['approvals']['review_drafts'] ?? array() ) as $review ) {
				$items[] = array( 'site_id' => $site['id'], 'site_name' => $site['site_name'], 'client_name' => $site['client_name'], 'type' => 'page_review', 'title' => sanitize_text_field( $review['draft_title'] ?? '' ), 'status' => sanitize_key( $review['quality_status'] ?? '' ), 'priority' => absint( $review['quality_score'] ?? 0 ), 'due_at' => '', 'review_url' => esc_url_raw( $review['edit_url'] ?? ( $snapshot['site']['admin_url'] ?? '' ) ) );
			}
			foreach ( (array) ( $snapshot['approvals']['workflow_tasks'] ?? array() ) as $task ) {
				$items[] = array( 'site_id' => $site['id'], 'site_name' => $site['site_name'], 'client_name' => $site['client_name'], 'type' => 'workflow_task', 'title' => sanitize_text_field( $task['title'] ?? '' ), 'status' => sanitize_key( $task['status'] ?? '' ), 'priority' => absint( $task['priority'] ?? 0 ), 'due_at' => sanitize_text_field( $task['due_at'] ?? '' ), 'review_url' => esc_url_raw( $snapshot['site']['admin_url'] ?? '' ) );
			}
		}
		usort( $items, function( $a, $b ) { return ( $b['priority'] <=> $a['priority'] ) ?: strcmp( $a['due_at'], $b['due_at'] ); } );
		return array_slice( $items, 0, 250 );
	}

	private function benchmarks( array $sites ) {
		$rows = array();
		foreach ( $sites as $site ) {
			$s = (array) ( $site['snapshot'] ?? array() );
			$rows[] = array(
				'site_id' => $site['id'], 'site_name' => $site['site_name'], 'client_name' => $site['client_name'],
				'strategy_readiness' => absint( $s['strategy']['readiness'] ?? 0 ),
				'critical_findings' => absint( $s['diagnostics']['blockers']['critical'] ?? 0 ),
				'high_findings' => absint( $s['diagnostics']['blockers']['high'] ?? 0 ),
				'failed_urls' => absint( $s['technical']['failed_urls'] ?? 0 ),
				'orphan_pages' => absint( $s['technical']['orphans'] ?? 0 ),
				'overdue_tasks' => absint( $s['workflow']['overdue'] ?? 0 ),
				'approvals' => count( (array) ( $s['approvals']['review_drafts'] ?? array() ) ) + count( (array) ( $s['approvals']['workflow_tasks'] ?? array() ) ),
				'budget_percent' => (float) ( $site['budget']['percent'] ?? 0 ),
			);
		}
		$averages = array( 'strategy_readiness' => 0, 'critical_findings' => 0, 'high_findings' => 0, 'failed_urls' => 0, 'orphan_pages' => 0, 'overdue_tasks' => 0 );
		if ( $rows ) {
			foreach ( $averages as $key => $unused ) { $averages[ $key ] = round( array_sum( array_column( $rows, $key ) ) / count( $rows ), 1 ); }
		}
		return array( 'portfolio_averages' => $averages, 'sites' => $rows, 'methodology' => 'Benchmarks compare stored operational evidence across connected websites. They are not ranking scores and should not be used to compare unrelated business models without context.' );
	}

	private function duplication_risks( array $sites ) {
		$risks = array();
		$count = count( $sites );
		for ( $i = 0; $i < $count; $i++ ) {
			$a_items = (array) ( $sites[ $i ]['snapshot']['portfolio_signatures'] ?? array() );
			for ( $j = $i + 1; $j < $count; $j++ ) {
				$b_items = (array) ( $sites[ $j ]['snapshot']['portfolio_signatures'] ?? array() );
				$best = array( 'score' => 0 );
				foreach ( array_slice( $a_items, 0, 75 ) as $a ) {
					foreach ( array_slice( $b_items, 0, 75 ) as $b ) {
						$score = $this->jaccard( (array) ( $a['signature'] ?? array() ), (array) ( $b['signature'] ?? array() ) );
						if ( $score > $best['score'] ) {
							$best = array( 'score' => $score, 'a' => $a, 'b' => $b );
						}
					}
				}
				if ( $best['score'] >= 0.62 ) {
					$risks[] = array(
						'score' => round( $best['score'], 3 ),
						'severity' => $best['score'] >= 0.78 ? 'high' : 'medium',
						'site_a' => array( 'site_id' => $sites[ $i ]['id'], 'site_name' => $sites[ $i ]['site_name'], 'title' => sanitize_text_field( $best['a']['content_title'] ?? '' ), 'url' => esc_url_raw( $best['a']['content_url'] ?? '' ) ),
						'site_b' => array( 'site_id' => $sites[ $j ]['id'], 'site_name' => $sites[ $j ]['site_name'], 'title' => sanitize_text_field( $best['b']['content_title'] ?? '' ), 'url' => esc_url_raw( $best['b']['content_url'] ?? '' ) ),
						'note' => 'Privacy-preserving content signatures overlap. Review intent, sources, examples and original value before publishing or refreshing either page.',
					);
				}
			}
		}
		usort( $risks, function( $a, $b ) { return $b['score'] <=> $a['score']; } );
		return array_slice( $risks, 0, 100 );
	}

	private function sync_alerts( $site_id, array $snapshot ) {
		$wanted = array();
		$this->add_wanted_alert( $wanted, $site_id, 'connection', 'low', 'Connection restored', 'The latest read-only snapshot was received successfully.', 'connection', false );
		$critical = absint( $snapshot['diagnostics']['blockers']['critical'] ?? 0 );
		$high = absint( $snapshot['diagnostics']['blockers']['high'] ?? 0 );
		$failed = absint( $snapshot['technical']['failed_urls'] ?? 0 );
		$not_indexed = absint( $snapshot['indexation']['not_indexed'] ?? 0 );
		$canonical_mismatches = absint( $snapshot['indexation']['canonical_mismatches'] ?? 0 );
		$blocked_or_failed = absint( $snapshot['indexation']['blocked_or_failed'] ?? 0 );
		$health_status = sanitize_key( $snapshot['production_health']['status'] ?? 'not_run' );
		$orphans = absint( $snapshot['technical']['orphans'] ?? 0 );
		$overdue = absint( $snapshot['workflow']['overdue'] ?? 0 );
		$approvals = count( (array) ( $snapshot['approvals']['review_drafts'] ?? array() ) ) + count( (array) ( $snapshot['approvals']['workflow_tasks'] ?? array() ) );
		$readiness = absint( $snapshot['strategy']['readiness'] ?? 0 );
		$unlinked_mentions = absint( $snapshot['visibility_brand']['unlinked_mentions'] ?? 0 );
		$portfolio_blocks = absint( $snapshot['portfolio_quality']['blocking_findings'] ?? 0 );
		if ( $critical ) { $this->add_wanted_alert( $wanted, $site_id, 'diagnostics', 'critical', 'Critical page evidence requires review', sprintf( '%d critical findings are stored in Page Diagnostics.', $critical ), 'snapshot' ); }
		if ( $high ) { $this->add_wanted_alert( $wanted, $site_id, 'diagnostics', 'high', 'High-priority page findings', sprintf( '%d high-priority findings are stored in Page Diagnostics.', $high ), 'snapshot' ); }
		if ( $failed ) { $this->add_wanted_alert( $wanted, $site_id, 'technical', 'high', 'Failed website URLs detected', sprintf( '%d discovered URLs currently have failed responses or unresolved checks.', $failed ), 'snapshot' ); }
		if ( $blocked_or_failed ) { $this->add_wanted_alert( $wanted, $site_id, 'indexation', 'high', 'Indexing blocks need review', sprintf( '%d inspected URLs have robots, noindex or fetch-failure evidence.', $blocked_or_failed ), 'snapshot' ); }
		if ( $canonical_mismatches ) { $this->add_wanted_alert( $wanted, $site_id, 'indexation', 'medium', 'Google canonical differences need review', sprintf( '%d inspected URLs have a stored Google-selected canonical difference.', $canonical_mismatches ), 'snapshot' ); }
		if ( $not_indexed && ! $blocked_or_failed ) { $this->add_wanted_alert( $wanted, $site_id, 'indexation', 'medium', 'Important URLs are not indexed', sprintf( '%d tracked URLs currently have not-indexed evidence.', $not_indexed ), 'snapshot' ); }
		if ( 'critical' === $health_status ) { $this->add_wanted_alert( $wanted, $site_id, 'system_health', 'critical', 'Production health checks failed', 'The managed website has at least one critical production-readiness or compatibility check.', 'snapshot' ); }
		if ( $orphans ) { $this->add_wanted_alert( $wanted, $site_id, 'internal_links', 'medium', 'Orphan-page evidence detected', sprintf( '%d WordPress pages are outside the stored internal-link graph.', $orphans ), 'snapshot' ); }
		if ( $overdue ) { $this->add_wanted_alert( $wanted, $site_id, 'workflow', 'high', 'SEO workflow tasks are overdue', sprintf( '%d workflow tasks are overdue.', $overdue ), 'snapshot' ); }
		if ( $approvals ) { $this->add_wanted_alert( $wanted, $site_id, 'approval', 'medium', 'SEO work awaits approval', sprintf( '%d draft or workflow items are waiting for human review.', $approvals ), 'snapshot' ); }
		if ( $readiness < 60 ) { $this->add_wanted_alert( $wanted, $site_id, 'strategy', 'medium', 'Website Strategy is incomplete', sprintf( 'Strategy readiness is %d%%. Complete the missing strategic decisions before scaling execution.', $readiness ), 'snapshot' ); }
		if ( $unlinked_mentions ) { $this->add_wanted_alert( $wanted, $site_id, 'visibility', 'medium', 'Unlinked brand mentions need review', sprintf( '%d stored brand mentions do not currently link to the website.', $unlinked_mentions ), 'snapshot' ); }
		if ( $portfolio_blocks ) { $this->add_wanted_alert( $wanted, $site_id, 'portfolio_quality', 'high', 'Portfolio quality gate is blocking content', sprintf( '%d portfolio quality findings currently block review-ready status.', $portfolio_blocks ), 'snapshot' ); }
		$this->persist_alerts( $site_id, $wanted, 'snapshot' );
		$this->persist_alerts( $site_id, array(), 'connection' );
		$this->sync_budget_alert( $site_id, $this->get_site( $site_id ) );
	}

	private function sync_connection_alert( $site_id, $message ) {
		$wanted = array();
		$this->add_wanted_alert( $wanted, $site_id, 'connection', 'critical', 'Website snapshot connection failed', sanitize_text_field( $message ), 'connection' );
		$this->persist_alerts( $site_id, $wanted, 'connection' );
	}

	private function sync_budget_alert( $site_id, $site ) {
		if ( ! $site ) { return; }
		$percent = (float) ( $site['budget']['percent'] ?? 0 );
		$wanted = array();
		if ( $percent >= 100 ) {
			$this->add_wanted_alert( $wanted, $site_id, 'budget', 'critical', 'Monthly research budget exceeded', sprintf( 'Tracked usage is %.1f%% of the configured monthly budget.', $percent ), 'budget' );
		} elseif ( $percent >= 90 ) {
			$this->add_wanted_alert( $wanted, $site_id, 'budget', 'high', 'Monthly research budget near limit', sprintf( 'Tracked usage is %.1f%% of the configured monthly budget.', $percent ), 'budget' );
		} elseif ( $percent >= 75 ) {
			$this->add_wanted_alert( $wanted, $site_id, 'budget', 'medium', 'Monthly research budget watch', sprintf( 'Tracked usage is %.1f%% of the configured monthly budget.', $percent ), 'budget' );
		}
		$this->persist_alerts( $site_id, $wanted, 'budget' );
	}

	private function add_wanted_alert( array &$wanted, $site_id, $category, $severity, $title, $summary, $source, $active = true ) {
		if ( ! $active ) { return; }
		$key = hash( 'sha256', absint( $site_id ) . '|' . sanitize_key( $source ) . '|' . sanitize_key( $category ) . '|' . sanitize_text_field( $title ) );
		$wanted[ $key ] = array( 'alert_key' => $key, 'category' => sanitize_key( $category ), 'severity' => sanitize_key( $severity ), 'title' => sanitize_text_field( $title ), 'summary' => sanitize_textarea_field( $summary ), 'source' => sanitize_key( $source ) );
	}

	private function persist_alerts( $site_id, array $wanted, $source ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		foreach ( $wanted as $key => $alert ) {
			$sql = "INSERT INTO {$this->alerts_table()} (site_id,alert_key,category,severity,title,summary,status,source,first_seen_at,last_seen_at,resolved_at,resolved_by,created_at,updated_at)
			VALUES (%d,%s,%s,%s,%s,%s,'open',%s,%s,%s,NULL,0,%s,%s)
			ON DUPLICATE KEY UPDATE category=VALUES(category),severity=VALUES(severity),title=VALUES(title),summary=VALUES(summary),status='open',last_seen_at=VALUES(last_seen_at),resolved_at=NULL,resolved_by=0,updated_at=VALUES(updated_at)";
			$wpdb->query( $wpdb->prepare( $sql, absint( $site_id ), $key, $alert['category'], $alert['severity'], $alert['title'], $alert['summary'], $alert['source'], $now, $now, $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$existing = $wpdb->get_results( $wpdb->prepare( "SELECT id,alert_key FROM {$this->alerts_table()} WHERE site_id=%d AND source=%s AND status='open'", absint( $site_id ), sanitize_key( $source ) ), ARRAY_A );
		foreach ( $existing ?: array() as $row ) {
			if ( ! isset( $wanted[ $row['alert_key'] ] ) ) {
				$wpdb->update( $this->alerts_table(), array( 'status' => 'resolved', 'resolved_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $row['id'] ) ) );
			}
		}
	}

	private function stored_diagnostics_summary() {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_evidence';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
			return array( 'pages_diagnosed' => 0, 'blockers' => array( 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0 ), 'top_blockers' => array(), 'max_work_priority' => 0, 'last_updated' => '' );
		}
		$rows = $wpdb->get_results( "SELECT diagnostics_json,updated_at FROM {$table} WHERE diagnostics_json IS NOT NULL AND diagnostics_json<>'' ORDER BY updated_at DESC LIMIT 1000", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$counts = array( 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0 );
		$codes = array();
		$max = 0;
		$last = '';
		$pages = 0;
		foreach ( $rows ?: array() as $row ) {
			$report = json_decode( $row['diagnostics_json'], true );
			if ( ! is_array( $report ) ) { continue; }
			$pages++;
			$max = max( $max, absint( $report['fix_priority'] ?? $report['work_priority'] ?? 0 ) );
			if ( ! $last ) { $last = sanitize_text_field( $row['updated_at'] ); }
			foreach ( (array) ( $report['blockers'] ?? array() ) as $blocker ) {
				$impact = sanitize_key( $blocker['impact'] ?? 'low' );
				if ( isset( $counts[ $impact ] ) ) { $counts[ $impact ]++; }
				$code = sanitize_key( $blocker['code'] ?? 'other' );
				$codes[ $code ] = ( $codes[ $code ] ?? 0 ) + 1;
			}
		}
		arsort( $codes );
		return array( 'pages_diagnosed' => $pages, 'blockers' => $counts, 'top_blockers' => array_slice( $codes, 0, 10, true ), 'max_work_priority' => $max, 'last_updated' => $last );
	}

	private function budget_summary( $site_id, $limit, $currency ) {
		global $wpdb;
		$start = gmdate( 'Y-m-01' );
		$end   = gmdate( 'Y-m-t' );
		$used = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$this->usage_table()} WHERE site_id=%d AND event_date BETWEEN %s AND %s", absint( $site_id ), $start, $end ) );
		$percent = $limit > 0 ? min( 999.9, round( 100 * $used / $limit, 1 ) ) : 0;
		return array( 'used' => round( $used, 2 ), 'limit' => round( max( 0, $limit ), 2 ), 'remaining' => round( max( 0, $limit - $used ), 2 ), 'percent' => $percent, 'currency' => $currency ?: 'USD', 'period_start' => $start, 'period_end' => $end );
	}

	private function site_attention( array $row, array $snapshot, array $budget, $stale ) {
		if ( 'error' === $row['status'] || $stale ) { return 'critical'; }
		if ( absint( $snapshot['diagnostics']['blockers']['critical'] ?? 0 ) || absint( $snapshot['technical']['failed_urls'] ?? 0 ) || absint( $snapshot['workflow']['overdue'] ?? 0 ) || (float) $budget['percent'] >= 90 ) { return 'high'; }
		if ( absint( $snapshot['diagnostics']['blockers']['high'] ?? 0 ) || count( (array) ( $snapshot['approvals']['review_drafts'] ?? array() ) ) || count( (array) ( $snapshot['approvals']['workflow_tasks'] ?? array() ) ) || absint( $snapshot['visibility_brand']['unlinked_mentions'] ?? 0 ) || absint( $snapshot['strategy']['readiness'] ?? 0 ) < 60 ) { return 'medium'; }
		return 'low';
	}

	private function record_site_error( $site_id, $message ) {
		global $wpdb;
		$wpdb->update( $this->sites_table(), array( 'status' => 'error', 'last_error' => sanitize_text_field( $message ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $site_id ) ) );
	}

	private function site_count() {
		global $wpdb;
		return absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->sites_table()}" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private function tables_ready() {
		global $wpdb;
		foreach ( array( $this->sites_table(), $this->snapshots_table(), $this->alerts_table(), $this->usage_table() ) as $table ) {
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) { return false; }
		}
		return true;
	}

	private function normalize_site_url( $value ) {
		$url = esc_url_raw( trim( (string) $value ) );
		if ( ! $url || ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'ikon_seo_agency_site_url', __( 'Enter a valid public HTTPS website URL.', 'ikon-seo' ) );
		}
		if ( 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return new WP_Error( 'ikon_seo_agency_site_https', __( 'Managed websites must use HTTPS so the site key is protected in transit.', 'ikon-seo' ) );
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( ! $host || 'localhost' === $host || preg_match( '/^(?:127\.|10\.|192\.168\.|172\.(?:1[6-9]|2\d|3[01])\.)/', $host ) ) {
			return new WP_Error( 'ikon_seo_agency_site_public', __( 'Managed websites must use a public hostname.', 'ikon-seo' ) );
		}
		return trailingslashit( $url );
	}

	private function sanitize_snapshot( array $data ) {
		$allowed = array( 'format','generated_at','site','strategy','connections','inventory','diagnostics','technical','indexation','production_health','search','workflow','publisher','local_growth','visibility_brand','portfolio_quality','approvals','page_plans','monitoring','portfolio_signatures','safety','truncated' );
		$result = array();
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $data ) ) { $result[ $key ] = $this->sanitize_recursive( $data[ $key ] ); }
		}
		$result['format'] = self::SNAPSHOT_SCHEMA;
		return $result;
	}

	private function sanitize_recursive( $value, $depth = 0 ) {
		if ( $depth > 8 ) { return null; }
		if ( is_array( $value ) ) {
			$result = array();
			foreach ( array_slice( $value, 0, 500, true ) as $key => $item ) {
				$clean_key = is_int( $key ) ? $key : sanitize_key( $key );
				$result[ $clean_key ] = $this->sanitize_recursive( $item, $depth + 1 );
			}
			return $result;
		}
		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) { return $value; }
		return substr( sanitize_textarea_field( (string) $value ), 0, 5000 );
	}

	private function sanitize_numeric_map( array $value ) {
		$result = array();
		foreach ( array_slice( $value, 0, 100, true ) as $key => $number ) {
			if ( is_numeric( $number ) ) { $result[ sanitize_key( $key ) ] = 0 + $number; }
		}
		return $result;
	}

	private function age_hours( $datetime ) {
		$timestamp = $datetime ? strtotime( $datetime . ' UTC' ) : false;
		return $timestamp ? max( 0, ( time() - $timestamp ) / HOUR_IN_SECONDS ) : null;
	}

	private function jaccard( array $a, array $b ) {
		$a = array_values( array_unique( array_filter( array_map( 'strval', $a ) ) ) );
		$b = array_values( array_unique( array_filter( array_map( 'strval', $b ) ) ) );
		if ( ! $a || ! $b ) { return 0; }
		$union = array_unique( array_merge( $a, $b ) );
		return count( $union ) ? count( array_intersect( $a, $b ) ) / count( $union ) : 0;
	}
}
