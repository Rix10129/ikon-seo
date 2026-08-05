<?php

defined( 'ABSPATH' ) || exit;

/**
 * Secure, read-only client portal.
 *
 * Client access is granted only to an existing WordPress user, is scoped to a
 * single managed website and reads a sanitised snapshot rather than raw agency
 * records. The module never publishes content, sends messages, changes remote
 * websites or exposes credentials, internal notes, fees, staff capacity or
 * unrelated client records.
 */
final class Ikon_SEO_Client_Portal {
	const CRON_HOOK = 'ikon_seo_client_portal_maintenance';
	const SHORTCODE = 'ikon_seo_client_portal';
	const SNAPSHOT_TTL_HOURS = 12;

	private $agency_command;
	private $service_levels;
	private $executive_command;
	private $search_impact;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Agency_Command_Centre $agency_command,
		Ikon_SEO_Agency_Service_Levels $service_levels,
		Ikon_SEO_Executive_Command_Centre $executive_command,
		Ikon_SEO_Search_Impact $search_impact,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->agency_command   = $agency_command;
		$this->service_levels   = $service_levels;
		$this->executive_command = $executive_command;
		$this->search_impact    = $search_impact;
		$this->history          = $history;
		$this->logger           = $logger;
	}

	public function register_hooks() {
		add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 45 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'admin_post_ikon_seo_client_portal_create', array( $this, 'handle_create' ) );
		add_action( 'admin_post_ikon_seo_client_portal_activate', array( $this, 'handle_activate' ) );
		add_action( 'admin_post_ikon_seo_client_portal_revoke', array( $this, 'handle_revoke' ) );
		add_action( 'admin_post_ikon_seo_client_portal_refresh', array( $this, 'handle_refresh' ) );
		add_action( self::CRON_HOOK, array( $this, 'scheduled_maintenance' ) );
	}

	public function assignments_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_client_portal_access'; }
	public function snapshots_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_client_portal_snapshots'; }
	public function events_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_client_portal_events'; }

	public function status() {
		global $wpdb;
		$ready = $this->tables_ready();
		return array(
			'enabled'              => true,
			'database_ready'       => $ready,
			'active_access_grants' => $ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->assignments_table()} WHERE status='active'" ) ) : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'pending_access_grants'=> $ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->assignments_table()} WHERE status='pending'" ) ) : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'frontend_shortcode'   => '[' . self::SHORTCODE . ']',
			'public_share_links'   => false,
			'live_site_writes'     => false,
			'client_messages_sent' => false,
		);
	}

	/** Deterministic permission normaliser used by runtime and release tests. */
	public function normalize_permissions( $input ) {
		$allowed = array( 'overview', 'service_scope', 'approved_reports', 'completed_work', 'planned_work', 'search_impact', 'client_actions' );
		if ( is_string( $input ) ) {
			$input = preg_split( '/[\s,]+/', $input );
		}
		$out = array();
		foreach ( (array) $input as $permission ) {
			$permission = sanitize_key( $permission );
			if ( in_array( $permission, $allowed, true ) && ! in_array( $permission, $out, true ) ) {
				$out[] = $permission;
			}
		}
		if ( ! $out ) {
			$out = array( 'overview', 'service_scope', 'approved_reports', 'completed_work', 'planned_work', 'search_impact', 'client_actions' );
		}
		sort( $out );
		return $out;
	}

	public function assignment_fingerprint( array $assignment ) {
		$material = array(
			'wp_user_id' => absint( $assignment['wp_user_id'] ?? 0 ),
			'site_id'    => absint( $assignment['site_id'] ?? 0 ),
			'permissions'=> $this->normalize_permissions( $assignment['permissions'] ?? array() ),
			'expires_at' => sanitize_text_field( $assignment['expires_at'] ?? '' ),
		);
		return hash( 'sha256', wp_json_encode( $material ) );
	}

	public function create_assignment( array $input, $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_client_portal_tables', __( 'The client portal database is not ready.', 'ikon-seo' ) );
		}
		$wp_user_id = absint( $input['wp_user_id'] ?? 0 );
		$site_id    = absint( $input['site_id'] ?? 0 );
		$user       = get_userdata( $wp_user_id );
		$site       = $this->get_site( $site_id );
		if ( ! $user ) {
			return new WP_Error( 'ikon_seo_client_portal_user', __( 'Select an existing WordPress user.', 'ikon-seo' ) );
		}
		if ( ! $site ) {
			return new WP_Error( 'ikon_seo_client_portal_site', __( 'Select a managed website.', 'ikon-seo' ) );
		}
		$existing = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->assignments_table()} WHERE wp_user_id=%d AND site_id=%d AND status IN ('pending','active') LIMIT 1", $wp_user_id, $site_id ) ) );
		if ( $existing ) {
			return new WP_Error( 'ikon_seo_client_portal_duplicate', __( 'This user already has pending or active access to the selected website.', 'ikon-seo' ) );
		}
		$expires_at = $this->normalise_datetime( $input['expires_at'] ?? '' );
		if ( $expires_at && strtotime( $expires_at . ' UTC' ) <= time() ) {
			return new WP_Error( 'ikon_seo_client_portal_expiry', __( 'The access expiry must be in the future.', 'ikon-seo' ) );
		}
		$permissions = $this->normalize_permissions( $input['permissions'] ?? array() );
		$record = array(
			'wp_user_id' => $wp_user_id,
			'site_id'    => $site_id,
			'permissions'=> $permissions,
			'expires_at' => $expires_at,
		);
		$fingerprint = $this->assignment_fingerprint( $record );
		$now = current_time( 'mysql', true );
		$ok = $wpdb->insert(
			$this->assignments_table(),
			array(
				'wp_user_id' => $wp_user_id,
				'site_id' => $site_id,
				'label' => substr( sanitize_text_field( $input['label'] ?? $user->display_name ), 0, 190 ),
				'status' => 'pending',
				'permissions_json' => wp_json_encode( $permissions ),
				'access_fingerprint' => $fingerprint,
				'expires_at' => $expires_at ?: null,
				'created_by' => absint( $user_id ),
				'activated_by' => 0,
				'activated_at' => null,
				'revoked_by' => 0,
				'revoked_at' => null,
				'revocation_reason' => '',
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
		if ( false === $ok ) {
			return new WP_Error( 'ikon_seo_client_portal_store', __( 'The client access grant could not be stored.', 'ikon-seo' ) );
		}
		$id = absint( $wpdb->insert_id );
		$this->event( $id, $site_id, $wp_user_id, 'access_created', 'pending', 'A client portal access grant was created and awaits activation.', array( 'fingerprint' => $fingerprint ), $user_id );
		return $this->get_assignment( $id );
	}

	public function activate_assignment( $assignment_id, $fingerprint, $user_id = 0 ) {
		global $wpdb;
		$assignment = $this->get_assignment( $assignment_id );
		if ( ! $assignment || 'pending' !== $assignment['status'] ) {
			return new WP_Error( 'ikon_seo_client_portal_pending', __( 'The pending access grant was not found.', 'ikon-seo' ) );
		}
		$fingerprint = strtolower( trim( (string) $fingerprint ) );
		if ( 64 !== strlen( $fingerprint ) || ! hash_equals( (string) $assignment['access_fingerprint'], $fingerprint ) ) {
			return new WP_Error( 'ikon_seo_client_portal_fingerprint', __( 'The access fingerprint does not match the pending grant.', 'ikon-seo' ) );
		}
		if ( ! get_userdata( $assignment['wp_user_id'] ) || ! $this->get_site( $assignment['site_id'] ) ) {
			return new WP_Error( 'ikon_seo_client_portal_target', __( 'The assigned user or managed website is no longer available.', 'ikon-seo' ) );
		}
		if ( $this->is_expired( $assignment ) ) {
			return new WP_Error( 'ikon_seo_client_portal_expired', __( 'The pending access grant has expired.', 'ikon-seo' ) );
		}
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->assignments_table(), array( 'status' => 'active', 'activated_by' => absint( $user_id ), 'activated_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $assignment_id ) ) );
		$snapshot = $this->refresh_snapshot( $assignment_id, $user_id );
		if ( is_wp_error( $snapshot ) ) {
			$wpdb->update( $this->assignments_table(), array( 'status' => 'pending', 'activated_by' => 0, 'activated_at' => null, 'updated_at' => $now ), array( 'id' => absint( $assignment_id ) ) );
			return $snapshot;
		}
		$this->event( $assignment_id, $assignment['site_id'], $assignment['wp_user_id'], 'access_activated', 'active', 'A local administrator activated client portal access.', array( 'snapshot_id' => absint( $snapshot['id'] ?? 0 ) ), $user_id );
		return $this->get_assignment( $assignment_id );
	}

	public function revoke_assignment( $assignment_id, $reason, $user_id = 0 ) {
		global $wpdb;
		$assignment = $this->get_assignment( $assignment_id );
		if ( ! $assignment || ! in_array( $assignment['status'], array( 'pending', 'active' ), true ) ) {
			return new WP_Error( 'ikon_seo_client_portal_revoke', __( 'The active or pending access grant was not found.', 'ikon-seo' ) );
		}
		$reason = sanitize_textarea_field( $reason );
		if ( ! trim( $reason ) ) {
			return new WP_Error( 'ikon_seo_client_portal_revoke_reason', __( 'Enter a reason for revoking client access.', 'ikon-seo' ) );
		}
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->assignments_table(), array( 'status' => 'revoked', 'revoked_by' => absint( $user_id ), 'revoked_at' => $now, 'revocation_reason' => $reason, 'updated_at' => $now ), array( 'id' => absint( $assignment_id ) ) );
		$this->event( $assignment_id, $assignment['site_id'], $assignment['wp_user_id'], 'access_revoked', 'revoked', $reason, array(), $user_id );
		return $this->get_assignment( $assignment_id );
	}

	public function refresh_snapshot( $assignment_id, $user_id = 0 ) {
		global $wpdb;
		$assignment = $this->get_assignment( $assignment_id );
		if ( ! $assignment || ! in_array( $assignment['status'], array( 'pending', 'active' ), true ) ) {
			return new WP_Error( 'ikon_seo_client_portal_assignment', __( 'The client portal access grant was not found.', 'ikon-seo' ) );
		}
		$raw = $this->build_safe_payload( $assignment );
		if ( is_wp_error( $raw ) ) {
			return $raw;
		}
		$payload = $this->sanitize_snapshot_payload( $raw, $assignment['permissions'] );
		$hash = hash( 'sha256', wp_json_encode( $payload ) );
		$now = current_time( 'mysql', true );
		$settings = Ikon_SEO_Plugin::settings();
		$ttl_hours = max( 1, min( 72, absint( $settings['client_portal_snapshot_hours'] ?? self::SNAPSHOT_TTL_HOURS ) ) );
		$expires = gmdate( 'Y-m-d H:i:s', time() + $ttl_hours * HOUR_IN_SECONDS );
		$wpdb->insert(
			$this->snapshots_table(),
			array(
				'assignment_id' => absint( $assignment_id ),
				'site_id' => absint( $assignment['site_id'] ),
				'payload_hash' => $hash,
				'payload_json' => wp_json_encode( $payload ),
				'source_updated_at' => sanitize_text_field( $payload['source_updated_at'] ?? '' ) ?: null,
				'generated_by' => absint( $user_id ),
				'generated_at' => $now,
				'expires_at' => $expires,
			)
		);
		if ( ! $wpdb->insert_id ) {
			return new WP_Error( 'ikon_seo_client_portal_snapshot_store', __( 'The client-safe snapshot could not be stored.', 'ikon-seo' ) );
		}
		$this->cleanup_snapshots( $assignment_id, 6 );
		return $this->get_snapshot( absint( $wpdb->insert_id ) );
	}

	/** Public allowlist sanitizer used by runtime and release tests. */
	public function sanitize_snapshot_payload( array $raw, array $permissions = array() ) {
		$permissions = $this->normalize_permissions( $permissions );
		$out = array(
			'schema' => 'ikon-seo-client-portal-snapshot-v1',
			'generated_at' => sanitize_text_field( $raw['generated_at'] ?? current_time( 'mysql', true ) ),
			'source_updated_at' => sanitize_text_field( $raw['source_updated_at'] ?? '' ),
			'stale' => ! empty( $raw['stale'] ),
			'safety' => array(
				'read_only' => true,
				'publishes_content' => false,
				'sends_messages' => false,
				'exposes_internal_notes' => false,
				'exposes_credentials' => false,
				'exposes_other_websites' => false,
			),
		);
		if ( in_array( 'overview', $permissions, true ) ) {
			$out['overview'] = array(
				'site_name' => sanitize_text_field( $raw['overview']['site_name'] ?? '' ),
				'site_url' => esc_url_raw( $raw['overview']['site_url'] ?? '' ),
				'client_name' => sanitize_text_field( $raw['overview']['client_name'] ?? '' ),
				'service_status' => sanitize_key( $raw['overview']['service_status'] ?? '' ),
				'latest_report_period' => sanitize_text_field( $raw['overview']['latest_report_period'] ?? '' ),
			);
		}
		if ( in_array( 'service_scope', $permissions, true ) ) {
			$out['service_scope'] = array(
				'plan_name' => sanitize_text_field( $raw['service_scope']['plan_name'] ?? '' ),
				'status' => sanitize_key( $raw['service_scope']['status'] ?? '' ),
				'included_deliverables' => $this->safe_lines( $raw['service_scope']['included_deliverables'] ?? array(), 50 ),
				'excluded_services' => $this->safe_lines( $raw['service_scope']['excluded_services'] ?? array(), 50 ),
			);
		}
		if ( in_array( 'approved_reports', $permissions, true ) ) {
			$out['approved_reports'] = array_slice( array_values( array_filter( array_map( array( $this, 'sanitize_report' ), (array) ( $raw['approved_reports'] ?? array() ) ) ) ), 0, 24 );
		}
		if ( in_array( 'completed_work', $permissions, true ) ) {
			$out['completed_work'] = array_slice( array_values( array_filter( array_map( array( $this, 'sanitize_work_item' ), (array) ( $raw['completed_work'] ?? array() ) ) ) ), 0, 100 );
		}
		if ( in_array( 'planned_work', $permissions, true ) ) {
			$out['planned_work'] = array_slice( array_values( array_filter( array_map( array( $this, 'sanitize_work_item' ), (array) ( $raw['planned_work'] ?? array() ) ) ) ), 0, 100 );
		}
		if ( in_array( 'search_impact', $permissions, true ) ) {
			$out['search_impact'] = array_slice( array_values( array_filter( array_map( array( $this, 'sanitize_impact' ), (array) ( $raw['search_impact'] ?? array() ) ) ) ), 0, 24 );
		}
		if ( in_array( 'client_actions', $permissions, true ) ) {
			$out['client_actions'] = array_slice( array_values( array_filter( array_map( array( $this, 'sanitize_client_action' ), (array) ( $raw['client_actions'] ?? array() ) ) ) ), 0, 50 );
		}
		return $out;
	}

	public function portal_data_for_user( $user_id, $assignment_id = 0 ) {
		$assignments = $this->assignments_for_user( $user_id, 'active' );
		$out = array();
		foreach ( $assignments as $assignment ) {
			if ( $assignment_id && absint( $assignment['id'] ) !== absint( $assignment_id ) ) { continue; }
			if ( $this->is_expired( $assignment ) ) { continue; }
			$snapshot = $this->latest_snapshot( $assignment['id'] );
			if ( ! $snapshot || $this->snapshot_expired( $snapshot ) ) {
				$snapshot = $this->refresh_snapshot( $assignment['id'], 0 );
			}
			if ( is_wp_error( $snapshot ) || ! $snapshot ) { continue; }
			$out[] = array(
				'assignment' => array(
					'id' => absint( $assignment['id'] ),
					'label' => sanitize_text_field( $assignment['label'] ),
					'site_id' => absint( $assignment['site_id'] ),
					'expires_at' => sanitize_text_field( $assignment['expires_at'] ),
				),
				'portal' => $snapshot['payload'],
			);
			$this->event( $assignment['id'], $assignment['site_id'], $user_id, 'portal_viewed', 'success', 'The assigned user viewed a sanitised client portal snapshot.', array( 'snapshot_id' => absint( $snapshot['id'] ) ), $user_id, true );
		}
		return array(
			'assignments' => $out,
			'read_only' => true,
			'generated_at' => current_time( 'mysql', true ),
		);
	}

	public function can_access_request( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) { return false; }
		$assignment_id = absint( $request->get_param( 'assignment_id' ) );
		if ( ! $assignment_id ) { return (bool) $this->assignments_for_user( get_current_user_id(), 'active' ); }
		$assignment = $this->get_assignment( $assignment_id );
		return $assignment && absint( $assignment['wp_user_id'] ) === get_current_user_id() && 'active' === $assignment['status'] && ! $this->is_expired( $assignment );
	}

	public function rest_report( WP_REST_Request $request ) {
		return rest_ensure_response( $this->portal_data_for_user( get_current_user_id(), absint( $request->get_param( 'assignment_id' ) ) ) );
	}

	public function admin_sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'create_access': $result = $this->create_assignment( (array) ( $payload['access'] ?? $payload ), $user_id ); break;
			case 'activate_access': $result = $this->activate_assignment( absint( $payload['assignment_id'] ?? 0 ), (string) ( $payload['fingerprint'] ?? '' ), $user_id ); break;
			case 'revoke_access': $result = $this->revoke_assignment( absint( $payload['assignment_id'] ?? 0 ), (string) ( $payload['reason'] ?? '' ), $user_id ); break;
			case 'refresh_snapshot': $result = $this->refresh_snapshot( absint( $payload['assignment_id'] ?? 0 ), $user_id ); break;
			case 'preview_user': $result = $this->portal_data_for_user( absint( $payload['wp_user_id'] ?? 0 ), absint( $payload['assignment_id'] ?? 0 ) ); break;
			case 'read': default: $result = array( 'read_only' => true ); break;
		}
		if ( is_wp_error( $result ) ) { return $result; }
		return array( 'command' => $command, 'result' => $result, 'client_portal' => $this->admin_report() );
	}

	public function admin_report() {
		return array(
			'status' => $this->status(),
			'assignments' => $this->all_assignments( 250 ),
			'recent_events' => $this->recent_events( 100 ),
			'safety' => array(
				'existing_wordpress_accounts_only' => true,
				'public_magic_links' => false,
				'one_website_per_access_grant' => true,
				'raw_agency_tables_exposed' => false,
				'internal_notes_exposed' => false,
				'credentials_exposed' => false,
				'publishes_or_edits_content' => false,
				'sends_client_messages' => false,
			),
		);
	}

	public function render_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<div class="ikon-client-portal ikon-client-portal-login"><p>' . esc_html__( 'Please sign in to view your SEO client portal.', 'ikon-seo' ) . '</p><p><a class="button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Sign in', 'ikon-seo' ) . '</a></p></div>';
		}
		$data = $this->portal_data_for_user( get_current_user_id() );
		if ( empty( $data['assignments'] ) ) {
			return '<div class="ikon-client-portal"><p>' . esc_html__( 'No active client portal access is assigned to this account.', 'ikon-seo' ) . '</p></div>';
		}
		wp_enqueue_style( 'ikon-seo-client-portal', IKON_SEO_URL . 'assets/client-portal.css', array(), IKON_SEO_VERSION );
		ob_start();
		?><div class="ikon-client-portal"><div class="ikon-client-portal__notice"><?php esc_html_e( 'This portal is read-only. Results are evidence-based observations and do not guarantee rankings, leads or revenue.', 'ikon-seo' ); ?></div><?php
		foreach ( $data['assignments'] as $entry ) {
			$this->render_portal_entry( $entry );
		}
		?></div><?php
		return ob_get_clean();
	}

	public function admin_menu() {
		add_submenu_page( 'ikon-seo', __( 'Client Portal', 'ikon-seo' ), __( 'Client Portal', 'ikon-seo' ), 'manage_options', 'ikon-seo-client-portal', array( $this, 'render_admin_page' ) );
	}

	public function admin_assets( $hook ) {
		if ( 'ikon-seo_page_ikon-seo-client-portal' !== $hook ) { return; }
		wp_enqueue_style( 'ikon-seo-admin', IKON_SEO_URL . 'assets/admin.css', array(), IKON_SEO_VERSION );
		wp_enqueue_style( 'ikon-seo-client-portal', IKON_SEO_URL . 'assets/client-portal.css', array(), IKON_SEO_VERSION );
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$report = $this->admin_report();
		$sites = (array) ( $this->agency_command->summary( 250 )['sites'] ?? array() );
		$users = get_users( array( 'number' => 250, 'orderby' => 'display_name', 'order' => 'ASC' ) );
		?><div class="wrap ikon-seo-wrap"><div class="ikon-seo-header"><div><p class="ikon-seo-kicker">IKON DIGITALS</p><h1><?php esc_html_e( 'Secure Client Portal', 'ikon-seo' ); ?></h1><p><?php esc_html_e( 'Grant one existing WordPress user read-only access to one managed website at a time.', 'ikon-seo' ); ?></p></div><span class="ikon-seo-version">v<?php echo esc_html( IKON_SEO_VERSION ); ?></span></div><?php
		$this->render_admin_notice();
		if ( empty( $report['status']['database_ready'] ) ) { ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'The client portal database is not ready. Deactivate and reactivate Ikon SEO once to run the upgrade.', 'ikon-seo' ); ?></p></div><?php }
		?><div class="ikon-card"><h2><?php esc_html_e( 'Create pending access', 'ikon-seo' ); ?></h2><p><?php esc_html_e( 'The grant remains unusable until a local administrator activates its exact fingerprint.', 'ikon-seo' ); ?></p><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'ikon_seo_client_portal_create' ); ?><input type="hidden" name="action" value="ikon_seo_client_portal_create"><table class="form-table"><tr><th><label for="portal-user"><?php esc_html_e( 'WordPress user', 'ikon-seo' ); ?></label></th><td><select id="portal-user" name="wp_user_id" required><option value=""><?php esc_html_e( 'Select user', 'ikon-seo' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name . ' — ' . $user->user_email ); ?></option><?php endforeach; ?></select></td></tr><tr><th><label for="portal-site"><?php esc_html_e( 'Managed website', 'ikon-seo' ); ?></label></th><td><select id="portal-site" name="site_id" required><option value=""><?php esc_html_e( 'Select website', 'ikon-seo' ); ?></option><?php foreach ( $sites as $site ) : ?><option value="<?php echo esc_attr( absint( $site['id'] ?? 0 ) ); ?>"><?php echo esc_html( ( $site['site_name'] ?? '' ) . ' — ' . ( $site['client_name'] ?? '' ) ); ?></option><?php endforeach; ?></select></td></tr><tr><th><label for="portal-label"><?php esc_html_e( 'Access label', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="portal-label" name="label" type="text"></td></tr><tr><th><?php esc_html_e( 'Visible sections', 'ikon-seo' ); ?></th><td><?php foreach ( array( 'overview'=>'Overview','service_scope'=>'Service scope','approved_reports'=>'Approved reports','completed_work'=>'Completed work','planned_work'=>'Planned work','search_impact'=>'Search impact','client_actions'=>'Client actions' ) as $key=>$label ) : ?><label style="display:block"><input type="checkbox" name="permissions[]" value="<?php echo esc_attr( $key ); ?>" checked> <?php echo esc_html( $label ); ?></label><?php endforeach; ?></td></tr><tr><th><label for="portal-expiry"><?php esc_html_e( 'Optional expiry', 'ikon-seo' ); ?></label></th><td><input id="portal-expiry" name="expires_at" type="datetime-local"></td></tr></table><?php submit_button( __( 'Create pending access', 'ikon-seo' ) ); ?></form></div>
		<div class="ikon-card"><h2><?php esc_html_e( 'Access grants', 'ikon-seo' ); ?></h2><p><code>[ikon_seo_client_portal]</code> <?php esc_html_e( 'can be placed on a private or account page. The shortcode itself does not create a page.', 'ikon-seo' ); ?></p><div class="table-wrap"><table class="widefat striped"><thead><tr><th>ID</th><th><?php esc_html_e( 'User', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Fingerprint / expiry', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Actions', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $report['assignments'] ) ) : ?><tr><td colspan="6"><?php esc_html_e( 'No access grants yet.', 'ikon-seo' ); ?></td></tr><?php else : foreach ( $report['assignments'] as $item ) : ?><tr><td><?php echo esc_html( $item['id'] ); ?></td><td><?php echo esc_html( $item['user']['display_name'] . ' — ' . $item['user']['email'] ); ?></td><td><?php echo esc_html( $item['site']['name'] ); ?></td><td><strong><?php echo esc_html( ucfirst( $item['status'] ) ); ?></strong></td><td><code><?php echo esc_html( $item['access_fingerprint'] ); ?></code><br><?php echo esc_html( $item['expires_at'] ?: 'No expiry' ); ?></td><td><?php if ( 'pending' === $item['status'] ) : $this->admin_action_form( 'ikon_seo_client_portal_activate', $item['id'], array( 'fingerprint' => $item['access_fingerprint'] ), __( 'Activate exact grant', 'ikon-seo' ) ); endif; if ( 'active' === $item['status'] ) : $this->admin_action_form( 'ikon_seo_client_portal_refresh', $item['id'], array(), __( 'Refresh safe snapshot', 'ikon-seo' ) ); endif; if ( in_array( $item['status'], array( 'pending','active' ), true ) ) : $this->admin_action_form( 'ikon_seo_client_portal_revoke', $item['id'], array( 'reason' => 'Access revoked by a local administrator.' ), __( 'Revoke', 'ikon-seo' ) ); endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div>
		<div class="ikon-card"><h2><?php esc_html_e( 'Safety model', 'ikon-seo' ); ?></h2><ul><li><?php esc_html_e( 'Existing WordPress login required; no public magic links.', 'ikon-seo' ); ?></li><li><?php esc_html_e( 'Each access grant is restricted to one managed website.', 'ikon-seo' ); ?></li><li><?php esc_html_e( 'Only approved reports and allowlisted operational fields appear.', 'ikon-seo' ); ?></li><li><?php esc_html_e( 'Internal notes, fees, team capacity, credentials and unrelated websites are excluded.', 'ikon-seo' ); ?></li><li><?php esc_html_e( 'The portal cannot approve, publish, edit, send, redirect, delete or noindex anything.', 'ikon-seo' ); ?></li></ul></div></div><?php
	}

	public function scheduled_maintenance() {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return; }
		$now = current_time( 'mysql', true );
		$wpdb->query( $wpdb->prepare( "UPDATE {$this->assignments_table()} SET status='expired',updated_at=%s WHERE status='active' AND expires_at IS NOT NULL AND expires_at<>'' AND expires_at<%s", $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$retention = max( 30, min( 730, absint( Ikon_SEO_Plugin::settings()['client_portal_event_retention_days'] ?? 180 ) ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $retention * DAY_IN_SECONDS );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->events_table()} WHERE created_at<%s", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->snapshots_table()} WHERE generated_at<%s", gmdate( 'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		update_option( 'ikon_seo_client_portal_last_maintenance', $now, false );
	}

	public function handle_create() { $this->handle_admin_action( 'ikon_seo_client_portal_create', function() { return $this->create_assignment( wp_unslash( $_POST ), get_current_user_id() ); } ); }
	public function handle_activate() { $this->handle_admin_action( 'ikon_seo_client_portal_activate', function() { return $this->activate_assignment( absint( $_POST['assignment_id'] ?? 0 ), sanitize_text_field( wp_unslash( $_POST['fingerprint'] ?? '' ) ), get_current_user_id() ); } ); }
	public function handle_revoke() { $this->handle_admin_action( 'ikon_seo_client_portal_revoke', function() { return $this->revoke_assignment( absint( $_POST['assignment_id'] ?? 0 ), sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ), get_current_user_id() ); } ); }
	public function handle_refresh() { $this->handle_admin_action( 'ikon_seo_client_portal_refresh', function() { return $this->refresh_snapshot( absint( $_POST['assignment_id'] ?? 0 ), get_current_user_id() ); } ); }

	private function build_safe_payload( array $assignment ) {
		global $wpdb;
		$preview = $this->executive_command->client_portal_preview( $assignment['site_id'] );
		if ( is_wp_error( $preview ) ) { return $preview; }
		$reports = array();
		$service = $this->service_levels->report( array( 'limit' => 250 ) );
		foreach ( (array) ( $service['reports'] ?? array() ) as $report ) {
			if ( absint( $report['site_id'] ?? 0 ) !== absint( $assignment['site_id'] ) || ! in_array( $report['status'] ?? '', array( 'approved','delivered' ), true ) ) { continue; }
			$reports[] = $report;
		}
		$site_host = wp_parse_url( $preview['site']['url'] ?? '', PHP_URL_HOST );
		$impact = array();
		$impact_report = $this->search_impact->report( array( 'limit' => 250, 'status' => 'acknowledged' ), false );
		foreach ( (array) ( $impact_report['studies'] ?? array() ) as $study ) {
			$host = wp_parse_url( $study['target_url'] ?? '', PHP_URL_HOST );
			if ( $site_host && $host && strtolower( $site_host ) === strtolower( $host ) && 'acknowledged' === ( $study['status'] ?? '' ) ) { $impact[] = $study; }
		}
		$latest_period = '';
		if ( $reports ) { $latest_period = sanitize_text_field( $reports[0]['period_start'] . ' — ' . $reports[0]['period_end'] ); }
		$site_record = $this->get_site( $assignment['site_id'] );
		$source_updated = sanitize_text_field( $site_record['last_snapshot_at'] ?? '' );
		if ( ! $source_updated ) { $source_updated = current_time( 'mysql', true ); }
		return array(
			'generated_at' => current_time( 'mysql', true ),
			'source_updated_at' => $source_updated,
			'stale' => strtotime( $source_updated . ' UTC' ) && strtotime( $source_updated . ' UTC' ) < strtotime( '-7 days' ),
			'overview' => array( 'site_name' => $preview['site']['name'] ?? '', 'site_url' => $preview['site']['url'] ?? '', 'client_name' => $preview['site']['client_name'] ?? '', 'service_status' => $preview['service_scope']['status'] ?? '', 'latest_report_period' => $latest_period ),
			'service_scope' => $preview['service_scope'] ?? array(),
			'approved_reports' => $reports,
			'completed_work' => $preview['completed_work'] ?? array(),
			'planned_work' => $preview['planned_work'] ?? array(),
			'search_impact' => $impact,
			'client_actions' => $preview['outstanding_client_approvals'] ?? array(),
		);
	}

	private function sanitize_report( $report ) {
		if ( ! is_array( $report ) || ! in_array( sanitize_key( $report['status'] ?? '' ), array( 'approved','delivered' ), true ) ) { return null; }
		$data = (array) ( $report['report'] ?? array() );
		return array(
			'id' => absint( $report['id'] ?? 0 ),
			'period_start' => sanitize_text_field( $report['period_start'] ?? $data['period']['start'] ?? '' ),
			'period_end' => sanitize_text_field( $report['period_end'] ?? $data['period']['end'] ?? '' ),
			'status' => sanitize_key( $report['status'] ?? '' ),
			'client_summary' => sanitize_textarea_field( $data['client_summary'] ?? '' ),
			'next_actions' => $this->safe_lines( $data['next_actions'] ?? array(), 10 ),
			'work_delivered' => array_slice( array_values( array_filter( array_map( array( $this, 'sanitize_work_item' ), (array) ( $data['work_delivered'] ?? array() ) ) ) ), 0, 100 ),
			'service_level' => array(
				'score' => max( 0, min( 100, (float) ( $data['service_level']['score'] ?? 0 ) ) ),
				'completed_items' => absint( $data['service_level']['completed_items'] ?? 0 ),
				'overdue_items' => absint( $data['service_level']['overdue_items'] ?? 0 ),
			),
			'evidence_limitations' => $this->safe_lines( $data['evidence_coverage']['limitations'] ?? array(), 10 ),
			'approved_at' => sanitize_text_field( $report['approved_at'] ?? '' ),
			'delivered_at' => sanitize_text_field( $report['delivered_at'] ?? '' ),
		);
	}

	private function sanitize_work_item( $item ) {
		if ( ! is_array( $item ) ) { return null; }
		$status = sanitize_key( $item['status'] ?? '' );
		if ( ! in_array( $status, array( 'planned','in_progress','awaiting_client','awaiting_approval','completed' ), true ) ) { return null; }
		return array(
			'id' => absint( $item['id'] ?? 0 ),
			'title' => substr( sanitize_text_field( $item['title'] ?? '' ), 0, 255 ),
			'category' => sanitize_key( $item['category'] ?? '' ),
			'status' => $status,
			'due_at' => sanitize_text_field( $item['due_at'] ?? '' ),
			'completed_at' => sanitize_text_field( $item['completed_at'] ?? '' ),
		);
	}

	private function sanitize_impact( $study ) {
		if ( ! is_array( $study ) || 'acknowledged' !== sanitize_key( $study['status'] ?? '' ) ) { return null; }
		$assessment = (array) ( $study['assessment'] ?? array() );
		return array(
			'id' => absint( $study['id'] ?? 0 ),
			'title' => sanitize_text_field( $study['title'] ?? '' ),
			'target_url' => esc_url_raw( $study['target_url'] ?? '' ),
			'primary_metric' => sanitize_key( $study['primary_metric'] ?? '' ),
			'outcome' => sanitize_key( $study['outcome'] ?? '' ),
			'confidence' => sanitize_key( $study['confidence'] ?? '' ),
			'adjusted_change_percent' => is_numeric( $study['adjusted_change_percent'] ?? null ) ? round( (float) $study['adjusted_change_percent'], 2 ) : null,
			'decision' => sanitize_key( $assessment['decision'] ?? '' ),
			'updated_at' => sanitize_text_field( $study['updated_at'] ?? '' ),
			'causal_claim' => false,
		);
	}

	private function sanitize_client_action( $item ) {
		if ( ! is_array( $item ) ) { return null; }
		$type = sanitize_key( $item['type'] ?? '' );
		if ( ! in_array( $type, array( 'client_report','awaiting_client' ), true ) ) { return null; }
		return array( 'type' => $type, 'title' => sanitize_text_field( $item['title'] ?? '' ), 'status' => sanitize_key( $item['status'] ?? '' ), 'due_at' => sanitize_text_field( $item['due_at'] ?? '' ) );
	}

	private function render_portal_entry( array $entry ) {
		$p = (array) ( $entry['portal'] ?? array() );
		$o = (array) ( $p['overview'] ?? array() );
		?><article class="ikon-client-portal__site"><header><p class="ikon-client-portal__eyebrow"><?php echo esc_html( $o['client_name'] ?? '' ); ?></p><h2><?php echo esc_html( $o['site_name'] ?? $entry['assignment']['label'] ); ?></h2><?php if ( ! empty( $o['site_url'] ) ) : ?><p><a href="<?php echo esc_url( $o['site_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $o['site_url'] ); ?></a></p><?php endif; ?><?php if ( ! empty( $p['stale'] ) ) : ?><p class="ikon-client-portal__warning"><?php esc_html_e( 'The latest agency evidence is older than seven days and should be refreshed.', 'ikon-seo' ); ?></p><?php endif; ?></header><?php
		if ( isset( $p['service_scope'] ) ) { ?><section><h3><?php esc_html_e( 'Service scope', 'ikon-seo' ); ?></h3><p><strong><?php echo esc_html( $p['service_scope']['plan_name'] ?? '' ); ?></strong></p><div class="ikon-client-portal__columns"><div><h4><?php esc_html_e( 'Included', 'ikon-seo' ); ?></h4><?php $this->render_lines( $p['service_scope']['included_deliverables'] ?? array() ); ?></div><div><h4><?php esc_html_e( 'Not included', 'ikon-seo' ); ?></h4><?php $this->render_lines( $p['service_scope']['excluded_services'] ?? array() ); ?></div></div></section><?php }
		if ( isset( $p['approved_reports'] ) ) { ?><section><h3><?php esc_html_e( 'Approved reports', 'ikon-seo' ); ?></h3><?php if ( empty( $p['approved_reports'] ) ) : ?><p><?php esc_html_e( 'No approved reports are available yet.', 'ikon-seo' ); ?></p><?php else : foreach ( $p['approved_reports'] as $report ) : ?><details><summary><?php echo esc_html( ( $report['period_start'] ?? '' ) . ' — ' . ( $report['period_end'] ?? '' ) ); ?></summary><p><?php echo nl2br( esc_html( $report['client_summary'] ?? '' ) ); ?></p><h4><?php esc_html_e( 'Next actions', 'ikon-seo' ); ?></h4><?php $this->render_lines( $report['next_actions'] ?? array() ); ?></details><?php endforeach; endif; ?></section><?php }
		if ( isset( $p['planned_work'] ) || isset( $p['completed_work'] ) ) { ?><section><h3><?php esc_html_e( 'Work status', 'ikon-seo' ); ?></h3><div class="ikon-client-portal__columns"><div><h4><?php esc_html_e( 'Planned and active', 'ikon-seo' ); ?></h4><?php $this->render_work( $p['planned_work'] ?? array() ); ?></div><div><h4><?php esc_html_e( 'Completed', 'ikon-seo' ); ?></h4><?php $this->render_work( $p['completed_work'] ?? array() ); ?></div></div></section><?php }
		if ( isset( $p['search_impact'] ) ) { ?><section><h3><?php esc_html_e( 'Search impact observations', 'ikon-seo' ); ?></h3><?php if ( empty( $p['search_impact'] ) ) : ?><p><?php esc_html_e( 'No acknowledged impact studies are available for this website.', 'ikon-seo' ); ?></p><?php else : ?><div class="ikon-client-portal__metrics"><?php foreach ( $p['search_impact'] as $impact ) : ?><div><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $impact['outcome'] ?? '' ) ) ); ?></strong><span><?php echo esc_html( $impact['title'] ?? '' ); ?></span><small><?php echo esc_html( ucfirst( $impact['confidence'] ?? '' ) . ' confidence; association, not proof of causation.' ); ?></small></div><?php endforeach; ?></div><?php endif; ?></section><?php }
		if ( isset( $p['client_actions'] ) ) { ?><section><h3><?php esc_html_e( 'Items needing your attention', 'ikon-seo' ); ?></h3><?php $this->render_work( $p['client_actions'] ); ?><p class="ikon-client-portal__muted"><?php esc_html_e( 'Contact your agency representative to respond. This read-only portal does not submit decisions.', 'ikon-seo' ); ?></p></section><?php }
		?><footer><small><?php echo esc_html( sprintf( __( 'Snapshot generated %s. Access is read-only.', 'ikon-seo' ), $p['generated_at'] ?? '' ) ); ?></small></footer></article><?php
	}

	private function render_lines( $lines ) { if ( ! $lines ) { echo '<p>—</p>'; return; } echo '<ul>'; foreach ( (array) $lines as $line ) { echo '<li>' . esc_html( $line ) . '</li>'; } echo '</ul>'; }
	private function render_work( $items ) { if ( ! $items ) { echo '<p>—</p>'; return; } echo '<ul>'; foreach ( (array) $items as $item ) { echo '<li><strong>' . esc_html( $item['title'] ?? '' ) . '</strong>'; if ( ! empty( $item['status'] ) ) { echo ' — ' . esc_html( ucwords( str_replace( '_', ' ', $item['status'] ) ) ); } echo '</li>'; } echo '</ul>'; }

	private function assignments_for_user( $user_id, $status = 'active' ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return array(); }
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->assignments_table()} WHERE wp_user_id=%d AND status=%s ORDER BY id ASC", absint( $user_id ), sanitize_key( $status ) ), ARRAY_A );
		return array_map( array( $this, 'prepare_assignment' ), $rows ?: array() );
	}

	private function all_assignments( $limit = 250 ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return array(); }
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT a.*,s.site_name,s.client_name,s.site_url FROM {$this->assignments_table()} a LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=a.site_id ORDER BY FIELD(a.status,'pending','active','expired','revoked'),a.updated_at DESC LIMIT %d", max( 1, min( 500, absint( $limit ) ) ) ), ARRAY_A );
		$out = array();
		foreach ( $rows ?: array() as $row ) {
			$item = $this->prepare_assignment( $row );
			$user = get_userdata( $item['wp_user_id'] );
			$item['user'] = array( 'display_name' => $user ? sanitize_text_field( $user->display_name ) : 'User #' . $item['wp_user_id'], 'email' => $user ? sanitize_email( $user->user_email ) : '' );
			$item['site'] = array( 'name' => sanitize_text_field( $row['site_name'] ?? '' ), 'client_name' => sanitize_text_field( $row['client_name'] ?? '' ), 'url' => esc_url_raw( $row['site_url'] ?? '' ) );
			$out[] = $item;
		}
		return $out;
	}

	private function get_assignment( $id ) { global $wpdb; if ( ! $this->tables_ready() ) { return null; } $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->assignments_table()} WHERE id=%d", absint( $id ) ), ARRAY_A ); return $row ? $this->prepare_assignment( $row ) : null; }
	private function prepare_assignment( array $row ) { $permissions = json_decode( (string) ( $row['permissions_json'] ?? '' ), true ); return array( 'id'=>absint( $row['id'] ), 'wp_user_id'=>absint( $row['wp_user_id'] ), 'site_id'=>absint( $row['site_id'] ), 'label'=>sanitize_text_field( $row['label'] ), 'status'=>sanitize_key( $row['status'] ), 'permissions'=>$this->normalize_permissions( is_array( $permissions ) ? $permissions : array() ), 'access_fingerprint'=>sanitize_text_field( $row['access_fingerprint'] ), 'expires_at'=>sanitize_text_field( $row['expires_at'] ), 'created_by'=>absint( $row['created_by'] ), 'activated_by'=>absint( $row['activated_by'] ), 'activated_at'=>sanitize_text_field( $row['activated_at'] ), 'revoked_by'=>absint( $row['revoked_by'] ), 'revoked_at'=>sanitize_text_field( $row['revoked_at'] ), 'revocation_reason'=>sanitize_textarea_field( $row['revocation_reason'] ), 'created_at'=>sanitize_text_field( $row['created_at'] ), 'updated_at'=>sanitize_text_field( $row['updated_at'] ) ); }

	private function latest_snapshot( $assignment_id ) { global $wpdb; if ( ! $this->tables_ready() ) { return null; } $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->snapshots_table()} WHERE assignment_id=%d ORDER BY id DESC LIMIT 1", absint( $assignment_id ) ), ARRAY_A ); return $row ? $this->prepare_snapshot( $row ) : null; }
	private function get_snapshot( $id ) { global $wpdb; $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->snapshots_table()} WHERE id=%d", absint( $id ) ), ARRAY_A ); return $row ? $this->prepare_snapshot( $row ) : null; }
	private function prepare_snapshot( array $row ) { $payload = json_decode( (string) ( $row['payload_json'] ?? '' ), true ); return array( 'id'=>absint( $row['id'] ), 'assignment_id'=>absint( $row['assignment_id'] ), 'site_id'=>absint( $row['site_id'] ), 'payload_hash'=>sanitize_text_field( $row['payload_hash'] ), 'payload'=>is_array( $payload ) ? $payload : array(), 'source_updated_at'=>sanitize_text_field( $row['source_updated_at'] ), 'generated_by'=>absint( $row['generated_by'] ), 'generated_at'=>sanitize_text_field( $row['generated_at'] ), 'expires_at'=>sanitize_text_field( $row['expires_at'] ) ); }
	private function snapshot_expired( array $snapshot ) { $ts = strtotime( (string) $snapshot['expires_at'] . ' UTC' ); return ! $ts || $ts < time(); }
	private function is_expired( array $assignment ) { $ts = strtotime( (string) ( $assignment['expires_at'] ?? '' ) . ' UTC' ); return $ts && $ts < time(); }

	private function get_site( $site_id ) { global $wpdb; if ( ! $site_id ) { return null; } return $wpdb->get_row( $wpdb->prepare( "SELECT id,site_name,client_name,site_url,status,last_snapshot_at FROM {$this->agency_command->sites_table()} WHERE id=%d", absint( $site_id ) ), ARRAY_A ); }

	private function cleanup_snapshots( $assignment_id, $keep = 6 ) { global $wpdb; $ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$this->snapshots_table()} WHERE assignment_id=%d ORDER BY id DESC LIMIT 999 OFFSET %d", absint( $assignment_id ), absint( $keep ) ) ); if ( $ids ) { $ids = array_map( 'absint', $ids ); $wpdb->query( "DELETE FROM {$this->snapshots_table()} WHERE id IN (" . implode( ',', $ids ) . ')' ); } } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	private function recent_events( $limit = 100 ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return array(); }
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT event_type,status,message,assignment_id,site_id,wp_user_id,created_at FROM {$this->events_table()} ORDER BY id DESC LIMIT %d", max( 1, min( 500, absint( $limit ) ) ) ), ARRAY_A );
		return array_map( function( $row ) { return array( 'event_type'=>sanitize_key( $row['event_type'] ), 'status'=>sanitize_key( $row['status'] ), 'message'=>sanitize_textarea_field( $row['message'] ), 'assignment_id'=>absint( $row['assignment_id'] ), 'site_id'=>absint( $row['site_id'] ), 'wp_user_id'=>absint( $row['wp_user_id'] ), 'created_at'=>sanitize_text_field( $row['created_at'] ) ); }, $rows ?: array() );
	}

	private function event( $assignment_id, $site_id, $wp_user_id, $type, $status, $message, array $details, $actor_id = 0, $privacy_hash = false ) {
		global $wpdb;
		if ( $this->table_exists( $this->events_table() ) ) {
			$ip_hash = '';
			$ua_hash = '';
			if ( $privacy_hash ) {
				$ip_hash = hash( 'sha256', wp_salt( 'auth' ) . '|' . sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ) );
				$ua_hash = hash( 'sha256', wp_salt( 'auth' ) . '|' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) );
			}
			$wpdb->insert( $this->events_table(), array( 'assignment_id'=>absint( $assignment_id ), 'site_id'=>absint( $site_id ), 'wp_user_id'=>absint( $wp_user_id ), 'event_type'=>sanitize_key( $type ), 'status'=>sanitize_key( $status ), 'message'=>sanitize_textarea_field( $message ), 'details_json'=>wp_json_encode( $details ), 'actor_id'=>absint( $actor_id ), 'ip_hash'=>$ip_hash, 'user_agent_hash'=>$ua_hash, 'created_at'=>current_time( 'mysql', true ) ) );
		}
		$this->logger->log( 'client_portal_' . sanitize_key( $type ), sanitize_key( $status ), $message, null, absint( $assignment_id ), $details );
		if ( ! $privacy_hash ) { $this->history->add( array( 'category'=>'approval', 'status'=>'completed', 'title'=>ucwords( str_replace( '_',' ', $type ) ), 'summary'=>$message, 'details'=>array_merge( $details, array( 'assignment_id'=>absint( $assignment_id ), 'site_id'=>absint( $site_id ) ) ) ), 'client_portal', $actor_id ); }
	}

	private function safe_lines( $value, $limit = 50 ) { if ( is_string( $value ) ) { $value = preg_split( '/[\r\n]+/', $value ); } $out=array(); foreach ( (array) $value as $line ) { $line=substr( sanitize_text_field( is_scalar( $line ) ? (string) $line : '' ), 0, 500 ); if ( $line && ! in_array( $line, $out, true ) ) { $out[]=$line; } } return array_slice( $out, 0, $limit ); }
	private function normalise_datetime( $value ) { $value=trim( (string) $value ); if ( ! $value ) { return null; } $ts=strtotime( $value . ' UTC' ); return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null; }
	private function tables_ready() { return $this->table_exists( $this->assignments_table() ) && $this->table_exists( $this->snapshots_table() ) && $this->table_exists( $this->events_table() ); }
	private function table_exists( $table ) { global $wpdb; return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table; }

	private function handle_admin_action( $nonce_action, $callback ) {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You do not have permission to manage client portal access.', 'ikon-seo' ) ); }
		check_admin_referer( $nonce_action );
		$result = call_user_func( $callback );
		$url = admin_url( 'admin.php?page=ikon-seo-client-portal' );
		if ( is_wp_error( $result ) ) { $url = add_query_arg( 'ikon-error', rawurlencode( $result->get_error_message() ), $url ); }
		else { $url = add_query_arg( 'client-portal-updated', 1, $url ); }
		wp_safe_redirect( $url ); exit;
	}

	private function render_admin_notice() {
		if ( ! empty( $_GET['client-portal-updated'] ) ) { ?><div class="notice notice-success inline"><p><?php esc_html_e( 'Client portal access was updated. No message was sent and no public content changed.', 'ikon-seo' ); ?></p></div><?php }
		if ( ! empty( $_GET['ikon-error'] ) ) { ?><div class="notice notice-error inline"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['ikon-error'] ) ) ); ?></p></div><?php }
	}

	private function admin_action_form( $action, $assignment_id, array $fields, $label ) {
		?><form style="display:inline-block;margin:2px" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( $action ); ?><input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>"><input type="hidden" name="assignment_id" value="<?php echo esc_attr( $assignment_id ); ?>"><?php foreach ( $fields as $key=>$value ) : ?><input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"><?php endforeach; ?><button class="button button-small" type="submit"><?php echo esc_html( $label ); ?></button></form><?php
	}
}
