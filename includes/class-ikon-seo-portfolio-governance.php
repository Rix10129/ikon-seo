<?php

defined( 'ABSPATH' ) || exit;

/**
 * Approval-first agency policy synchronisation and local governance.
 *
 * Agency policies can be proposed remotely, but a proposal is never activated
 * until an administrator on the managed WordPress website accepts it. Active
 * policies only tighten Ikon SEO workflow safeguards; they never publish,
 * merge, redirect, delete, noindex or otherwise change public content.
 */
final class Ikon_SEO_Portfolio_Governance {
	const CRON_HOOK            = 'ikon_seo_portfolio_governance_sync';
	const ACTIVE_OPTION         = 'ikon_seo_portfolio_governance_active';
	const AGENT_ENABLED_OPTION  = 'ikon_seo_governance_agent_enabled';
	const AGENT_HASH_OPTION     = 'ikon_seo_governance_agent_token_hash';
	const AGENT_LAST4_OPTION    = 'ikon_seo_governance_agent_token_last4';
	const AGENT_CREATED_OPTION  = 'ikon_seo_governance_agent_token_created';
	const AGENT_ONCE_PREFIX     = 'ikon_seo_governance_agent_once_';
	const ENVELOPE_SCHEMA       = 'ikon-seo-governance-policy-v1';
	const MAX_POLICY_BYTES      = 65536;
	const MAX_SYNC_BATCH        = 10;

	private $agency_command;
	private $crypto;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Agency_Command_Centre $agency_command,
		Ikon_SEO_Crypto $crypto,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->agency_command = $agency_command;
		$this->crypto         = $crypto;
		$this->history        = $history;
		$this->logger         = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_sync' ) );
	}

	public function policies_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_governance_policies';
	}

	public function assignments_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_governance_assignments';
	}

	public function inbox_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_governance_inbox';
	}

	public function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_governance_events';
	}

	public function status() {
		$active = self::active_policy();
		return array(
			'database_ready' => $this->tables_ready(),
			'agent'          => $this->agent_status(),
			'active_policy'  => $active,
			'compliance'     => $this->compliance_report( $active ),
			'inbox_counts'   => $this->inbox_counts(),
			'policy_counts'  => $this->policy_counts(),
			'assignment_counts' => $this->assignment_counts(),
			'safety' => array(
				'remote_activation'    => false,
				'local_approval_needed'=> true,
				'publishes_content'    => false,
				'changes_live_pages'   => false,
				'external_live_writes' => false,
			),
		);
	}

	public static function active_policy() {
		$value = get_option( self::ACTIVE_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function minimum_strategy_readiness( $default = 70 ) {
		$active = self::active_policy();
		$value  = absint( $active['policy']['rules']['minimum_strategy_readiness'] ?? $default );
		return max( 70, min( 100, $value ?: $default ) );
	}

	public static function max_safe_batch( $default = 5 ) {
		$active = self::active_policy();
		$value  = absint( $active['policy']['rules']['max_safe_batch'] ?? $default );
		return max( 1, min( 5, $value ?: $default ) );
	}

	public function agent_status() {
		return array(
			'enabled'        => (bool) get_option( self::AGENT_ENABLED_OPTION, false ),
			'configured'     => (bool) get_option( self::AGENT_HASH_OPTION, '' ),
			'last4'          => sanitize_text_field( get_option( self::AGENT_LAST4_OPTION, '' ) ),
			'created_at'     => sanitize_text_field( get_option( self::AGENT_CREATED_OPTION, '' ) ),
			'endpoint'       => rest_url( Ikon_SEO_REST::NAMESPACE . '/agency-governance-agent' ),
			'authentication' => 'Bearer governance proposal key',
			'proposal_only'  => true,
		);
	}

	public function generate_agent_key( $user_id = 0 ) {
		try {
			$raw = random_bytes( 32 );
		} catch ( Exception $exception ) {
			return new WP_Error( 'ikon_seo_governance_key_random', __( 'A secure governance proposal key could not be generated.', 'ikon-seo' ) );
		}
		$key = 'ikon_governance_' . rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
		update_option( self::AGENT_HASH_OPTION, wp_hash_password( $key ), false );
		update_option( self::AGENT_LAST4_OPTION, substr( $key, -4 ), false );
		update_option( self::AGENT_CREATED_OPTION, current_time( 'mysql', true ), false );
		update_option( self::AGENT_ENABLED_OPTION, 1, false );
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( $user_id ) {
			set_transient( self::AGENT_ONCE_PREFIX . $user_id, $key, 10 * MINUTE_IN_SECONDS );
		}
		$this->event( 0, 0, 'agent_key_generated', 'completed', 'A proposal-only governance key was generated.', array(), $user_id );
		return array( 'key' => $key, 'last4' => substr( $key, -4 ), 'endpoint' => rest_url( Ikon_SEO_REST::NAMESPACE . '/agency-governance-agent' ) );
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

	public function revoke_agent_key( $user_id = 0 ) {
		delete_option( self::AGENT_HASH_OPTION );
		delete_option( self::AGENT_LAST4_OPTION );
		delete_option( self::AGENT_CREATED_OPTION );
		update_option( self::AGENT_ENABLED_OPTION, 0, false );
		$this->event( 0, 0, 'agent_key_revoked', 'completed', 'The governance proposal key was revoked.', array(), $user_id );
		return $this->agent_status();
	}

	public function verify_agent_request( WP_REST_Request $request ) {
		if ( ! get_option( self::AGENT_ENABLED_OPTION, false ) ) {
			return new WP_Error( 'ikon_seo_governance_agent_disabled', __( 'The governance proposal connection is disabled.', 'ikon-seo' ), array( 'status' => 503 ) );
		}
		$hash = (string) get_option( self::AGENT_HASH_OPTION, '' );
		if ( ! $hash ) {
			return new WP_Error( 'ikon_seo_governance_agent_unconfigured', __( 'The governance proposal connection is not configured.', 'ikon-seo' ), array( 'status' => 503 ) );
		}
		$provided = '';
		$authorization = trim( (string) $request->get_header( 'authorization' ) );
		if ( 0 === stripos( $authorization, 'Bearer ' ) ) {
			$provided = trim( substr( $authorization, 7 ) );
		}
		if ( ! $provided ) {
			$provided = trim( (string) $request->get_header( 'x-ikon-governance-key' ) );
		}
		if ( ! $provided || ! wp_check_password( $provided, $hash ) ) {
			return new WP_Error( 'ikon_seo_governance_agent_unauthorized', __( 'A valid governance proposal key is required.', 'ikon-seo' ), array( 'status' => 401 ) );
		}
		if ( strlen( (string) $request->get_body() ) > self::MAX_POLICY_BYTES ) {
			return new WP_Error( 'ikon_seo_governance_payload', __( 'The governance proposal exceeds the allowed payload size.', 'ikon-seo' ), array( 'status' => 413 ) );
		}
		$rate_key = 'ikon_seo_governance_rate_' . gmdate( 'YmdH' ) . '_' . substr( hash( 'sha256', $provided ), 0, 12 );
		$used = (int) get_transient( $rate_key );
		if ( $used >= 20 ) {
			return new WP_Error( 'ikon_seo_governance_rate', __( 'The hourly governance proposal limit has been reached.', 'ikon-seo' ), array( 'status' => 429 ) );
		}
		set_transient( $rate_key, $used + 1, HOUR_IN_SECONDS + 60 );
		return true;
	}

	public function agent_sync( array $payload ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		if ( 'propose_policy' === $command ) {
			return $this->receive_proposal( (array) ( $payload['envelope'] ?? array() ) );
		}
		return array(
			'command'        => 'read',
			'agent'          => $this->agent_status(),
			'active_policy'  => self::active_policy(),
			'inbox_counts'   => $this->inbox_counts(),
			'compliance'     => $this->compliance_report(),
			'proposal_only'  => true,
		);
	}

	public function create_policy( array $input, $user_id = 0 ) {
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_governance_tables', __( 'The Portfolio Governance tables are unavailable. Reactivate Ikon SEO to repair the database.', 'ikon-seo' ) );
		}
		$name = sanitize_text_field( $input['name'] ?? '' );
		if ( '' === $name ) {
			return new WP_Error( 'ikon_seo_governance_policy_name', __( 'Enter a policy name.', 'ikon-seo' ) );
		}
		$key = sanitize_key( $input['policy_key'] ?? sanitize_title( $name ) );
		if ( '' === $key ) {
			return new WP_Error( 'ikon_seo_governance_policy_key', __( 'Enter a valid policy key.', 'ikon-seo' ) );
		}
		global $wpdb;
		$latest  = absint( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(version) FROM {$this->policies_table()} WHERE policy_key=%s", $key ) ) );
		$version = max( 1, absint( $input['version'] ?? ( $latest + 1 ) ) );
		$policy  = $this->normalize_policy( (array) ( $input['policy'] ?? $input ) );
		$fingerprint = $this->policy_fingerprint( $key, $version, $policy );
		$now = current_time( 'mysql', true );
		$result = $wpdb->insert(
			$this->policies_table(),
			array(
				'policy_key'    => $key,
				'name'          => $name,
				'version'       => $version,
				'status'        => 'draft',
				'policy_json'   => wp_json_encode( $policy ),
				'fingerprint'   => $fingerprint,
				'notes'         => sanitize_textarea_field( $input['notes'] ?? '' ),
				'created_by'    => absint( $user_id ),
				'approved_by'   => 0,
				'approved_at'   => null,
				'created_at'    => $now,
				'updated_at'    => $now,
			),
			array( '%s','%s','%d','%s','%s','%s','%s','%d','%d','%s','%s','%s' )
		);
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_governance_policy_store', __( 'The governance policy could not be stored. Use a new version number for an existing policy key.', 'ikon-seo' ) );
		}
		$id = absint( $wpdb->insert_id );
		$this->event( $id, 0, 'policy_created', 'completed', 'A draft portfolio governance policy was created.', array( 'fingerprint' => $fingerprint ), $user_id );
		return $this->get_policy( $id );
	}

	public function approve_policy( $policy_id, $notes, $user_id = 0 ) {
		$policy = $this->get_policy( $policy_id, true );
		if ( ! $policy ) {
			return new WP_Error( 'ikon_seo_governance_policy_missing', __( 'The governance policy was not found.', 'ikon-seo' ) );
		}
		if ( 'draft' !== $policy['status'] ) {
			return new WP_Error( 'ikon_seo_governance_policy_state', __( 'Only a draft policy can be approved.', 'ikon-seo' ) );
		}
		global $wpdb;
		$now = current_time( 'mysql', true );
		$wpdb->update(
			$this->policies_table(),
			array( 'status' => 'approved', 'notes' => sanitize_textarea_field( $notes ), 'approved_by' => absint( $user_id ), 'approved_at' => $now, 'updated_at' => $now ),
			array( 'id' => absint( $policy_id ) )
		);
		$this->event( $policy_id, 0, 'policy_approved', 'completed', 'The portfolio governance policy was approved for proposal.', array(), $user_id );
		return $this->get_policy( $policy_id );
	}

	public function retire_policy( $policy_id, $notes, $user_id = 0 ) {
		$notes = sanitize_textarea_field( $notes );
		if ( '' === $notes ) {
			return new WP_Error( 'ikon_seo_governance_retire_notes', __( 'Explain why the policy is being retired.', 'ikon-seo' ) );
		}
		$policy = $this->get_policy( $policy_id, true );
		if ( ! $policy ) {
			return new WP_Error( 'ikon_seo_governance_policy_missing', __( 'The governance policy was not found.', 'ikon-seo' ) );
		}
		global $wpdb;
		$wpdb->update( $this->policies_table(), array( 'status' => 'retired', 'notes' => $notes, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $policy_id ) ) );
		$wpdb->update( $this->assignments_table(), array( 'status' => 'retired', 'updated_at' => current_time( 'mysql', true ) ), array( 'policy_id' => absint( $policy_id ) ) );
		$this->event( $policy_id, 0, 'policy_retired', 'completed', $notes, array(), $user_id );
		return $this->get_policy( $policy_id );
	}

	public function save_site_governance_key( $site_id, $key, $user_id = 0 ) {
		$site_id = absint( $site_id );
		$key = trim( (string) $key );
		if ( strlen( $key ) < 28 ) {
			return new WP_Error( 'ikon_seo_governance_site_key', __( 'Enter the complete governance proposal key from the managed website.', 'ikon-seo' ) );
		}
		$encrypted = $this->crypto->encrypt( $key );
		if ( is_wp_error( $encrypted ) ) {
			return $encrypted;
		}
		global $wpdb;
		$result = $wpdb->update(
			$this->agency_command->sites_table(),
			array( 'encrypted_governance_key' => $encrypted, 'governance_status' => 'configured', 'governance_last_error' => '', 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $site_id )
		);
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_governance_site_store', __( 'The managed website governance key could not be saved.', 'ikon-seo' ) );
		}
		$this->event( 0, $site_id, 'site_governance_key_saved', 'completed', 'A proposal-only governance key was saved for the managed website.', array(), $user_id );
		return array( 'site_id' => $site_id, 'configured' => true );
	}

	public function assign_policy( $policy_id, $site_id, $user_id = 0 ) {
		$policy = $this->get_policy( $policy_id, true );
		if ( ! $policy || 'approved' !== $policy['status'] ) {
			return new WP_Error( 'ikon_seo_governance_policy_unapproved', __( 'Select an approved governance policy.', 'ikon-seo' ) );
		}
		$site = $this->get_site_row( $site_id );
		if ( ! $site ) {
			return new WP_Error( 'ikon_seo_governance_site_missing', __( 'The managed website was not found.', 'ikon-seo' ) );
		}
		$status = empty( $site['encrypted_governance_key'] ) ? 'awaiting_key' : 'queued';
		global $wpdb;
		$now = current_time( 'mysql', true );
		$sql = "INSERT INTO {$this->assignments_table()} (policy_id,site_id,status,remote_proposal_id,remote_status,last_sync_at,last_seen_fingerprint,last_error,assigned_by,created_at,updated_at)
		VALUES (%d,%d,%s,0,'',NULL,'','',%d,%s,%s)
		ON DUPLICATE KEY UPDATE status=VALUES(status),remote_proposal_id=0,remote_status='',last_sync_at=NULL,last_seen_fingerprint='',last_error='',assigned_by=VALUES(assigned_by),updated_at=VALUES(updated_at)";
		$wpdb->query( $wpdb->prepare( $sql, absint( $policy_id ), absint( $site_id ), $status, absint( $user_id ), $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$assignment_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->assignments_table()} WHERE policy_id=%d AND site_id=%d", absint( $policy_id ), absint( $site_id ) ) ) );
		$this->event( $policy_id, $site_id, 'policy_assigned', 'completed', 'The approved policy was assigned to a managed website.', array( 'assignment_id' => $assignment_id, 'status' => $status ), $user_id );
		return $this->get_assignment( $assignment_id );
	}

	public function sync_assignment( $assignment_id, $user_id = 0 ) {
		$assignment = $this->get_assignment( $assignment_id, true );
		if ( ! $assignment ) {
			return new WP_Error( 'ikon_seo_governance_assignment_missing', __( 'The governance assignment was not found.', 'ikon-seo' ) );
		}
		$policy = $this->get_policy( $assignment['policy_id'], true );
		$site   = $this->get_site_row( $assignment['site_id'] );
		if ( ! $policy || 'approved' !== $policy['status'] ) {
			return new WP_Error( 'ikon_seo_governance_policy_unapproved', __( 'The assigned policy is no longer approved.', 'ikon-seo' ) );
		}
		if ( ! $site || empty( $site['encrypted_governance_key'] ) ) {
			$this->update_assignment_error( $assignment_id, 'A governance proposal key has not been configured for this website.', 'awaiting_key' );
			return new WP_Error( 'ikon_seo_governance_key_missing', __( 'Save the managed website governance proposal key before synchronising.', 'ikon-seo' ) );
		}
		$key = $this->crypto->decrypt( $site['encrypted_governance_key'] );
		if ( is_wp_error( $key ) || ! $key ) {
			$this->update_assignment_error( $assignment_id, 'The stored governance proposal key could not be decrypted.', 'error' );
			return is_wp_error( $key ) ? $key : new WP_Error( 'ikon_seo_governance_key_decrypt', __( 'The stored governance proposal key could not be decrypted.', 'ikon-seo' ) );
		}
		$endpoint = trailingslashit( untrailingslashit( $site['site_url'] ) ) . 'wp-json/' . Ikon_SEO_REST::NAMESPACE . '/agency-governance-agent';
		$envelope = $this->policy_envelope( $policy );
		$response = wp_safe_remote_post(
			$endpoint,
			array(
				'timeout'     => 20,
				'redirection' => 2,
				'headers'     => array( 'Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json' ),
				'body'        => wp_json_encode( array( 'command' => 'propose_policy', 'envelope' => $envelope ) ),
				'data_format' => 'body',
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->update_assignment_error( $assignment_id, $response->get_error_message(), 'error' );
			return $response;
		}
		$code = absint( wp_remote_retrieve_response_code( $response ) );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
			$message = sanitize_text_field( $data['message'] ?? sprintf( 'The managed website returned HTTP %d.', $code ) );
			$this->update_assignment_error( $assignment_id, $message, 'error' );
			return new WP_Error( 'ikon_seo_governance_remote', $message );
		}
		$result = (array) ( $data['result'] ?? $data );
		if ( sanitize_text_field( $result['policy_fingerprint'] ?? '' ) !== $policy['fingerprint'] ) {
			$this->update_assignment_error( $assignment_id, 'The managed website returned a different policy fingerprint.', 'error' );
			return new WP_Error( 'ikon_seo_governance_fingerprint', __( 'The managed website returned a different policy fingerprint.', 'ikon-seo' ) );
		}
		global $wpdb;
		$now = current_time( 'mysql', true );
		$remote_status = sanitize_key( $result['status'] ?? 'pending_local_approval' );
		$wpdb->update(
			$this->assignments_table(),
			array(
				'status'                => in_array( $remote_status, array( 'accepted','rejected' ), true ) ? $remote_status : 'proposed',
				'remote_proposal_id'    => absint( $result['proposal_id'] ?? 0 ),
				'remote_status'         => $remote_status,
				'last_sync_at'          => $now,
				'last_seen_fingerprint' => sanitize_text_field( $result['policy_fingerprint'] ?? '' ),
				'last_error'            => '',
				'updated_at'            => $now,
			),
			array( 'id' => absint( $assignment_id ) )
		);
		$wpdb->update( $this->agency_command->sites_table(), array( 'governance_status' => $remote_status, 'governance_last_sync_at' => $now, 'governance_last_error' => '', 'updated_at' => $now ), array( 'id' => absint( $assignment['site_id'] ) ) );
		$this->event( $policy['id'], $site['id'], 'policy_synchronised', 'completed', 'The governance policy proposal was delivered for local approval.', array( 'remote_status' => $remote_status ), $user_id );
		return $this->get_assignment( $assignment_id );
	}

	public function receive_proposal( array $envelope ) {
		$normalized = $this->normalize_envelope( $envelope );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}
		global $wpdb;
		$now = current_time( 'mysql', true );
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->inbox_table()} WHERE source_fingerprint=%s AND policy_key=%s AND policy_version=%d AND policy_fingerprint=%s LIMIT 1",
				$normalized['source_fingerprint'],
				$normalized['policy_key'],
				$normalized['policy_version'],
				$normalized['policy_fingerprint']
			),
			ARRAY_A
		);
		if ( $existing ) {
			$wpdb->update( $this->inbox_table(), array( 'last_received_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $existing['id'] ) ) );
			return $this->proposal_receipt( array_merge( $existing, array( 'last_received_at' => $now ) ) );
		}
		$result = $wpdb->insert(
			$this->inbox_table(),
			array(
				'source_fingerprint' => $normalized['source_fingerprint'],
				'source_label'       => $normalized['source_label'],
				'policy_key'         => $normalized['policy_key'],
				'policy_name'        => $normalized['policy_name'],
				'policy_version'     => $normalized['policy_version'],
				'policy_fingerprint' => $normalized['policy_fingerprint'],
				'policy_json'        => wp_json_encode( $normalized['policy'] ),
				'status'             => 'pending_local_approval',
				'decision_notes'     => '',
				'received_at'        => $now,
				'last_received_at'   => $now,
				'decided_by'         => 0,
				'decided_at'         => null,
				'created_at'         => $now,
				'updated_at'         => $now,
			),
			array( '%s','%s','%s','%s','%d','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s' )
		);
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_governance_inbox_store', __( 'The governance proposal could not be stored.', 'ikon-seo' ) );
		}
		$id = absint( $wpdb->insert_id );
		$this->event( 0, 0, 'proposal_received', 'pending', 'A governance proposal is waiting for local administrator approval.', array( 'proposal_id' => $id, 'policy_fingerprint' => $normalized['policy_fingerprint'] ), 0 );
		return $this->proposal_receipt( $this->get_inbox_item( $id, true ) );
	}

	public function accept_proposal( $proposal_id, $notes, $user_id = 0 ) {
		$item = $this->get_inbox_item( $proposal_id, true );
		if ( ! $item ) {
			return new WP_Error( 'ikon_seo_governance_proposal_missing', __( 'The governance proposal was not found.', 'ikon-seo' ) );
		}
		if ( 'pending_local_approval' !== $item['status'] ) {
			return new WP_Error( 'ikon_seo_governance_proposal_state', __( 'Only a pending proposal can be accepted.', 'ikon-seo' ) );
		}
		$policy = json_decode( $item['policy_json'], true );
		$policy = $this->normalize_policy( is_array( $policy ) ? $policy : array() );
		$expected = $this->policy_fingerprint( $item['policy_key'], absint( $item['policy_version'] ), $policy );
		if ( ! hash_equals( $expected, (string) $item['policy_fingerprint'] ) ) {
			return new WP_Error( 'ikon_seo_governance_proposal_integrity', __( 'The governance proposal fingerprint no longer matches its stored policy.', 'ikon-seo' ) );
		}
		global $wpdb;
		$now = current_time( 'mysql', true );
		$wpdb->query( $wpdb->prepare( "UPDATE {$this->inbox_table()} SET status=%s,updated_at=%s WHERE status=%s", 'superseded', $now, 'accepted' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update(
			$this->inbox_table(),
			array( 'status' => 'accepted', 'decision_notes' => sanitize_textarea_field( $notes ), 'decided_by' => absint( $user_id ), 'decided_at' => $now, 'updated_at' => $now ),
			array( 'id' => absint( $proposal_id ) )
		);
		$active = array(
			'proposal_id'       => absint( $proposal_id ),
			'source_fingerprint'=> sanitize_text_field( $item['source_fingerprint'] ),
			'source_label'      => sanitize_text_field( $item['source_label'] ),
			'policy_key'        => sanitize_key( $item['policy_key'] ),
			'policy_name'       => sanitize_text_field( $item['policy_name'] ),
			'policy_version'    => absint( $item['policy_version'] ),
			'policy_fingerprint'=> sanitize_text_field( $item['policy_fingerprint'] ),
			'policy'            => $policy,
			'accepted_by'       => absint( $user_id ),
			'accepted_at'       => $now,
			'publishes_automatically' => false,
		);
		update_option( self::ACTIVE_OPTION, $active, false );
		$this->event( 0, 0, 'proposal_accepted', 'completed', 'A local administrator activated the governance policy.', array( 'proposal_id' => absint( $proposal_id ), 'policy_fingerprint' => $item['policy_fingerprint'] ), $user_id );
		return array( 'active_policy' => $active, 'compliance' => $this->compliance_report( $active ) );
	}

	public function reject_proposal( $proposal_id, $notes, $user_id = 0 ) {
		$notes = sanitize_textarea_field( $notes );
		if ( '' === $notes ) {
			return new WP_Error( 'ikon_seo_governance_reject_notes', __( 'Explain why the governance proposal is being rejected.', 'ikon-seo' ) );
		}
		$item = $this->get_inbox_item( $proposal_id, true );
		if ( ! $item || 'pending_local_approval' !== $item['status'] ) {
			return new WP_Error( 'ikon_seo_governance_proposal_state', __( 'Only a pending governance proposal can be rejected.', 'ikon-seo' ) );
		}
		global $wpdb;
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->inbox_table(), array( 'status' => 'rejected', 'decision_notes' => $notes, 'decided_by' => absint( $user_id ), 'decided_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $proposal_id ) ) );
		$this->event( 0, 0, 'proposal_rejected', 'completed', $notes, array( 'proposal_id' => absint( $proposal_id ) ), $user_id );
		return $this->get_inbox_item( $proposal_id );
	}

	public function report( array $args = array() ) {
		$limit = max( 10, min( 200, absint( $args['limit'] ?? 100 ) ) );
		global $wpdb;
		$policies = $this->tables_ready() ? $wpdb->get_results( "SELECT * FROM {$this->policies_table()} ORDER BY updated_at DESC LIMIT {$limit}", ARRAY_A ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$assignments = $this->tables_ready() ? $wpdb->get_results( "SELECT a.*,p.name AS policy_name,p.policy_key,p.version AS policy_version,p.fingerprint,s.site_name,s.site_url,s.client_name FROM {$this->assignments_table()} a LEFT JOIN {$this->policies_table()} p ON p.id=a.policy_id LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=a.site_id ORDER BY a.updated_at DESC LIMIT {$limit}", ARRAY_A ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$inbox = $this->tables_ready() ? $wpdb->get_results( "SELECT * FROM {$this->inbox_table()} ORDER BY updated_at DESC LIMIT {$limit}", ARRAY_A ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array(
			'status'      => $this->status(),
			'policies'    => array_map( array( $this, 'sanitize_policy_row' ), $policies ?: array() ),
			'assignments' => array_map( array( $this, 'sanitize_assignment_row' ), $assignments ?: array() ),
			'inbox'       => array_map( array( $this, 'sanitize_inbox_row' ), $inbox ?: array() ),
			'active_policy'=> self::active_policy(),
			'compliance'  => $this->compliance_report(),
			'generated_at'=> current_time( 'mysql', true ),
			'safety'      => array(
				'proposal_delivery_only' => true,
				'local_activation_only'  => true,
				'no_automatic_publishing'=> true,
				'no_public_site_changes' => true,
			),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'create_policy':
				$result = $this->create_policy( (array) ( $payload['policy'] ?? $payload ), $user_id );
				break;
			case 'approve_policy':
				$result = $this->approve_policy( absint( $payload['policy_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
				break;
			case 'retire_policy':
				$result = $this->retire_policy( absint( $payload['policy_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
				break;
			case 'save_site_key':
				$result = $this->save_site_governance_key( absint( $payload['site_id'] ?? 0 ), (string) ( $payload['governance_key'] ?? '' ), $user_id );
				break;
			case 'assign_policy':
				$result = $this->assign_policy( absint( $payload['policy_id'] ?? 0 ), absint( $payload['site_id'] ?? 0 ), $user_id );
				break;
			case 'sync_assignment':
				$result = $this->sync_assignment( absint( $payload['assignment_id'] ?? 0 ), $user_id );
				break;
			case 'accept_proposal':
				$result = $this->accept_proposal( absint( $payload['proposal_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
				break;
			case 'reject_proposal':
				$result = $this->reject_proposal( absint( $payload['proposal_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
				break;
			case 'read':
			default:
				$result = array( 'read_only' => true );
				break;
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return array( 'command' => $command, 'result' => $result, 'governance' => $this->report( array( 'limit' => absint( $payload['limit'] ?? 100 ) ) ) );
	}

	public function scheduled_sync() {
		if ( ! $this->tables_ready() ) {
			return;
		}
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT id FROM {$this->assignments_table()} WHERE status IN ('queued','proposed','error') ORDER BY updated_at ASC LIMIT " . self::MAX_SYNC_BATCH, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows ?: array() as $row ) {
			$this->sync_assignment( absint( $row['id'] ), 0 );
		}
	}

	public function compliance_report( array $active = array() ) {
		$active = $active ?: self::active_policy();
		if ( empty( $active['policy']['rules'] ) ) {
			return array( 'status' => 'not_configured', 'score' => 0, 'checks' => array(), 'blocking' => 0, 'warnings' => 0 );
		}
		$rules = (array) $active['policy']['rules'];
		$settings = Ikon_SEO_Plugin::settings();
		$checks = array();
		$this->add_check( $checks, 'draft_only', 'Draft-only content workflow', ! empty( $settings['draft_only'] ), 'Ikon SEO must keep new content in WordPress draft status.' );
		$this->add_check( $checks, 'live_updates_disabled', 'Live page updates disabled', empty( $settings['allow_live_updates'] ), 'Direct live-page updates must remain disabled.' );
		$this->add_check( $checks, 'manual_publish_only', 'Manual publishing boundary', ! empty( $rules['manual_publish_only'] ), 'Portfolio policy permanently requires a separate WordPress publishing decision.' );
		$this->add_check( $checks, 'fact_review', 'Fact-level review required', ! empty( $rules['require_fact_review'] ), 'Uncertain business facts must be confirmed locally.' );
		$this->add_check( $checks, 'brief_approval', 'Content brief approval required', ! empty( $rules['require_brief_approval'] ), 'A planned opportunity cannot bypass brief approval.' );
		$this->add_check( $checks, 'editorial_review', 'Editorial review required', ! empty( $rules['require_editorial_review'] ), 'Controlled drafts require a separate reviewer.' );
		$this->add_check( $checks, 'publishing_preflight', 'Publishing preflight required', ! empty( $rules['require_publishing_preflight'] ), 'Signed-off drafts require a release candidate and preflight.' );
		$this->add_check( $checks, 'pattern_advisory', 'Pattern Library remains advisory', 'advisory_only' === ( $rules['pattern_use'] ?? '' ), 'Cross-site patterns cannot be applied automatically.' );
		$this->add_check( $checks, 'portfolio_privacy', 'Portfolio evidence is anonymised', 'anonymised_only' === ( $rules['portfolio_evidence'] ?? '' ), 'Cross-site evidence must omit URLs, names, keywords and content.' );
		$this->add_check( $checks, 'external_writes_disabled', 'External live writes disabled', 'disabled' === ( $rules['external_live_writes'] ?? '' ), 'Governance cannot enable publishing, redirects, profile updates or outreach.' );
		$passed = 0;
		$blocking = 0;
		foreach ( $checks as $check ) {
			if ( 'pass' === $check['status'] ) { $passed++; } else { $blocking++; }
		}
		return array(
			'status'   => 0 === $blocking ? 'compliant' : 'attention_required',
			'score'    => $checks ? (int) round( 100 * $passed / count( $checks ) ) : 0,
			'checks'   => $checks,
			'blocking' => $blocking,
			'warnings' => 0,
			'policy_fingerprint' => sanitize_text_field( $active['policy_fingerprint'] ?? '' ),
			'effective_limits' => array(
				'minimum_strategy_readiness' => self::minimum_strategy_readiness(),
				'max_safe_batch'             => self::max_safe_batch(),
				'data_retention_days'        => absint( $rules['data_retention_days'] ?? 365 ),
			),
		);
	}

	private function normalize_policy( array $input ) {
		// Accept both the editable flat policy payload and an already-normalised policy envelope.
		if ( isset( $input['rules'] ) && is_array( $input['rules'] ) ) {
			$input = $input['rules'];
		}
		$sources = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) ( $input['allowed_evidence_sources'] ?? array( 'search_console','analytics','technical','approved_imports' ) ) ) ) ) );
		$allowed_sources = array_values( array_intersect( $sources, array( 'search_console','analytics','technical','indexation','competitor_research','authority','approved_imports','manual_evidence' ) ) );
		if ( ! $allowed_sources ) {
			$allowed_sources = array( 'search_console','analytics','technical','approved_imports' );
		}
		return array(
			'schema' => self::ENVELOPE_SCHEMA,
			'rules'  => array(
				'minimum_strategy_readiness' => max( 70, min( 100, absint( $input['minimum_strategy_readiness'] ?? 70 ) ) ),
				'max_safe_batch'             => max( 1, min( 5, absint( $input['max_safe_batch'] ?? 3 ) ) ),
				'require_fact_review'        => ! array_key_exists( 'require_fact_review', $input ) || ! empty( $input['require_fact_review'] ),
				'require_guided_launch'      => ! array_key_exists( 'require_guided_launch', $input ) || ! empty( $input['require_guided_launch'] ),
				'require_brief_approval'     => ! array_key_exists( 'require_brief_approval', $input ) || ! empty( $input['require_brief_approval'] ),
				'require_editorial_review'   => ! array_key_exists( 'require_editorial_review', $input ) || ! empty( $input['require_editorial_review'] ),
				'require_publishing_preflight'=> ! array_key_exists( 'require_publishing_preflight', $input ) || ! empty( $input['require_publishing_preflight'] ),
				'require_impact_study'       => ! empty( $input['require_impact_study'] ),
				'data_retention_days'        => max( 90, min( 1095, absint( $input['data_retention_days'] ?? 365 ) ) ),
				'allowed_evidence_sources'   => $allowed_sources,
				'manual_publish_only'        => true,
				'pattern_use'                => 'advisory_only',
				'portfolio_evidence'         => 'anonymised_only',
				'external_live_writes'       => 'disabled',
			),
			'locked_safety_rules' => array( 'manual_publish_only','pattern_use','portfolio_evidence','external_live_writes' ),
		);
	}

	private function normalize_envelope( array $envelope ) {
		if ( self::ENVELOPE_SCHEMA !== sanitize_text_field( $envelope['schema'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_governance_schema', __( 'The governance proposal schema is not supported.', 'ikon-seo' ) );
		}
		$source = strtolower( sanitize_text_field( $envelope['source_fingerprint'] ?? '' ) );
		if ( ! preg_match( '/^[a-f0-9]{64}$/', $source ) ) {
			return new WP_Error( 'ikon_seo_governance_source', __( 'The governance proposal source fingerprint is invalid.', 'ikon-seo' ) );
		}
		$key     = sanitize_key( $envelope['policy_key'] ?? '' );
		$name    = sanitize_text_field( $envelope['policy_name'] ?? '' );
		$version = absint( $envelope['policy_version'] ?? 0 );
		$policy  = $this->normalize_policy( (array) ( $envelope['policy'] ?? array() ) );
		$fingerprint = sanitize_text_field( $envelope['policy_fingerprint'] ?? '' );
		if ( ! $key || ! $name || $version < 1 || ! preg_match( '/^[a-f0-9]{64}$/', $fingerprint ) ) {
			return new WP_Error( 'ikon_seo_governance_envelope', __( 'The governance proposal metadata is incomplete.', 'ikon-seo' ) );
		}
		$expected = $this->policy_fingerprint( $key, $version, $policy );
		if ( ! hash_equals( $expected, $fingerprint ) ) {
			return new WP_Error( 'ikon_seo_governance_integrity', __( 'The governance proposal fingerprint does not match its policy.', 'ikon-seo' ) );
		}
		return array(
			'source_fingerprint' => $source,
			'source_label'       => sanitize_text_field( $envelope['source_label'] ?? 'Ikon SEO Agency' ),
			'policy_key'         => $key,
			'policy_name'        => $name,
			'policy_version'     => $version,
			'policy_fingerprint' => $fingerprint,
			'policy'             => $policy,
		);
	}

	private function policy_envelope( array $policy ) {
		$settings = Ikon_SEO_Plugin::settings();
		return array(
			'schema'             => self::ENVELOPE_SCHEMA,
			'source_fingerprint' => hash( 'sha256', strtolower( untrailingslashit( home_url( '/' ) ) ) . '|ikon-seo-agency-governance' ),
			'source_label'       => sanitize_text_field( $settings['agency_command_brand_name'] ?? 'Ikon SEO Agency' ),
			'policy_key'         => sanitize_key( $policy['policy_key'] ),
			'policy_name'        => sanitize_text_field( $policy['name'] ),
			'policy_version'     => absint( $policy['version'] ),
			'policy_fingerprint' => sanitize_text_field( $policy['fingerprint'] ),
			'policy'             => (array) $policy['policy'],
			'proposed_at'        => current_time( 'mysql', true ),
			'activation'         => 'local_administrator_only',
		);
	}

	private function policy_fingerprint( $key, $version, array $policy ) {
		return hash( 'sha256', sanitize_key( $key ) . '|' . absint( $version ) . '|' . wp_json_encode( $this->ksort_recursive( $policy ) ) );
	}

	private function ksort_recursive( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		foreach ( $value as $key => $item ) {
			$value[ $key ] = $this->ksort_recursive( $item );
		}
		if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
			ksort( $value );
		}
		return $value;
	}

	private function get_policy( $id, $raw = false ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->policies_table()} WHERE id=%d", absint( $id ) ), ARRAY_A );
		return $row ? ( $raw ? $this->hydrate_policy_row( $row ) : $this->sanitize_policy_row( $row ) ) : array();
	}

	private function get_assignment( $id, $raw = false ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT a.*,p.name AS policy_name,p.policy_key,p.version AS policy_version,p.fingerprint,s.site_name,s.site_url,s.client_name FROM {$this->assignments_table()} a LEFT JOIN {$this->policies_table()} p ON p.id=a.policy_id LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=a.site_id WHERE a.id=%d", absint( $id ) ), ARRAY_A );
		return $row ? ( $raw ? $row : $this->sanitize_assignment_row( $row ) ) : array();
	}

	private function get_inbox_item( $id, $raw = false ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->inbox_table()} WHERE id=%d", absint( $id ) ), ARRAY_A );
		return $row ? ( $raw ? $row : $this->sanitize_inbox_row( $row ) ) : array();
	}

	private function get_site_row( $site_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->agency_command->sites_table()} WHERE id=%d", absint( $site_id ) ), ARRAY_A );
	}

	private function sanitize_policy_row( array $row ) {
		$policy = json_decode( (string) ( $row['policy_json'] ?? '' ), true );
		return array(
			'id' => absint( $row['id'] ?? 0 ), 'policy_key' => sanitize_key( $row['policy_key'] ?? '' ), 'name' => sanitize_text_field( $row['name'] ?? '' ),
			'version' => absint( $row['version'] ?? 0 ), 'status' => sanitize_key( $row['status'] ?? '' ), 'policy' => is_array( $policy ) ? $policy : array(),
			'fingerprint' => sanitize_text_field( $row['fingerprint'] ?? '' ), 'notes' => sanitize_textarea_field( $row['notes'] ?? '' ),
			'created_by' => absint( $row['created_by'] ?? 0 ), 'approved_by' => absint( $row['approved_by'] ?? 0 ),
			'approved_at' => sanitize_text_field( $row['approved_at'] ?? '' ), 'created_at' => sanitize_text_field( $row['created_at'] ?? '' ), 'updated_at' => sanitize_text_field( $row['updated_at'] ?? '' ),
		);
	}

	private function hydrate_policy_row( array $row ) {
		$clean = $this->sanitize_policy_row( $row );
		$clean['policy_json'] = (string) ( $row['policy_json'] ?? '' );
		return $clean;
	}

	private function sanitize_assignment_row( array $row ) {
		return array(
			'id' => absint( $row['id'] ?? 0 ), 'policy_id' => absint( $row['policy_id'] ?? 0 ), 'site_id' => absint( $row['site_id'] ?? 0 ),
			'policy_name' => sanitize_text_field( $row['policy_name'] ?? '' ), 'policy_key' => sanitize_key( $row['policy_key'] ?? '' ), 'policy_version' => absint( $row['policy_version'] ?? 0 ),
			'fingerprint' => sanitize_text_field( $row['fingerprint'] ?? '' ), 'site_name' => sanitize_text_field( $row['site_name'] ?? '' ), 'site_url' => esc_url_raw( $row['site_url'] ?? '' ), 'client_name' => sanitize_text_field( $row['client_name'] ?? '' ),
			'status' => sanitize_key( $row['status'] ?? '' ), 'remote_proposal_id' => absint( $row['remote_proposal_id'] ?? 0 ), 'remote_status' => sanitize_key( $row['remote_status'] ?? '' ),
			'last_sync_at' => sanitize_text_field( $row['last_sync_at'] ?? '' ), 'last_seen_fingerprint' => sanitize_text_field( $row['last_seen_fingerprint'] ?? '' ), 'last_error' => sanitize_text_field( $row['last_error'] ?? '' ),
			'assigned_by' => absint( $row['assigned_by'] ?? 0 ), 'created_at' => sanitize_text_field( $row['created_at'] ?? '' ), 'updated_at' => sanitize_text_field( $row['updated_at'] ?? '' ),
		);
	}

	private function sanitize_inbox_row( array $row ) {
		$policy = json_decode( (string) ( $row['policy_json'] ?? '' ), true );
		return array(
			'id' => absint( $row['id'] ?? 0 ), 'source_fingerprint' => sanitize_text_field( $row['source_fingerprint'] ?? '' ), 'source_label' => sanitize_text_field( $row['source_label'] ?? '' ),
			'policy_key' => sanitize_key( $row['policy_key'] ?? '' ), 'policy_name' => sanitize_text_field( $row['policy_name'] ?? '' ), 'policy_version' => absint( $row['policy_version'] ?? 0 ),
			'policy_fingerprint' => sanitize_text_field( $row['policy_fingerprint'] ?? '' ), 'policy' => is_array( $policy ) ? $policy : array(), 'status' => sanitize_key( $row['status'] ?? '' ),
			'decision_notes' => sanitize_textarea_field( $row['decision_notes'] ?? '' ), 'received_at' => sanitize_text_field( $row['received_at'] ?? '' ), 'last_received_at' => sanitize_text_field( $row['last_received_at'] ?? '' ),
			'decided_by' => absint( $row['decided_by'] ?? 0 ), 'decided_at' => sanitize_text_field( $row['decided_at'] ?? '' ), 'created_at' => sanitize_text_field( $row['created_at'] ?? '' ), 'updated_at' => sanitize_text_field( $row['updated_at'] ?? '' ),
		);
	}

	private function proposal_receipt( array $row ) {
		return array(
			'proposal_id'        => absint( $row['id'] ?? 0 ),
			'status'             => sanitize_key( $row['status'] ?? 'pending_local_approval' ),
			'policy_key'         => sanitize_key( $row['policy_key'] ?? '' ),
			'policy_version'     => absint( $row['policy_version'] ?? 0 ),
			'policy_fingerprint' => sanitize_text_field( $row['policy_fingerprint'] ?? '' ),
			'activation'         => 'local_administrator_only',
			'publishes_automatically' => false,
		);
	}

	private function update_assignment_error( $assignment_id, $message, $status = 'error' ) {
		global $wpdb;
		$assignment = $this->get_assignment( $assignment_id, true );
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->assignments_table(), array( 'status' => sanitize_key( $status ), 'last_error' => sanitize_text_field( $message ), 'last_sync_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $assignment_id ) ) );
		if ( $assignment ) {
			$wpdb->update( $this->agency_command->sites_table(), array( 'governance_status' => sanitize_key( $status ), 'governance_last_error' => sanitize_text_field( $message ), 'governance_last_sync_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $assignment['site_id'] ) ) );
		}
	}

	private function inbox_counts() {
		if ( ! $this->tables_ready() ) { return array(); }
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT status,COUNT(*) AS total FROM {$this->inbox_table()} GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out = array(); foreach ( $rows ?: array() as $row ) { $out[ sanitize_key( $row['status'] ) ] = absint( $row['total'] ); }
		return $out;
	}

	private function policy_counts() {
		if ( ! $this->tables_ready() ) { return array(); }
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT status,COUNT(*) AS total FROM {$this->policies_table()} GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out = array(); foreach ( $rows ?: array() as $row ) { $out[ sanitize_key( $row['status'] ) ] = absint( $row['total'] ); }
		return $out;
	}

	private function assignment_counts() {
		if ( ! $this->tables_ready() ) { return array(); }
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT status,COUNT(*) AS total FROM {$this->assignments_table()} GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out = array(); foreach ( $rows ?: array() as $row ) { $out[ sanitize_key( $row['status'] ) ] = absint( $row['total'] ); }
		return $out;
	}

	private function add_check( array &$checks, $key, $label, $passed, $description ) {
		$checks[] = array( 'key' => sanitize_key( $key ), 'label' => sanitize_text_field( $label ), 'status' => $passed ? 'pass' : 'fail', 'description' => sanitize_text_field( $description ) );
	}

	private function event( $policy_id, $site_id, $action, $status, $message, array $details = array(), $user_id = 0 ) {
		if ( ! $this->tables_ready() ) { return; }
		global $wpdb;
		$wpdb->insert(
			$this->events_table(),
			array( 'policy_id' => absint( $policy_id ), 'site_id' => absint( $site_id ), 'action' => sanitize_key( $action ), 'status' => sanitize_key( $status ), 'message' => sanitize_text_field( $message ), 'details_json' => wp_json_encode( $details ), 'user_id' => absint( $user_id ), 'created_at' => current_time( 'mysql', true ) ),
			array( '%d','%d','%s','%s','%s','%s','%d','%s' )
		);
		$this->history->add(
			array( 'category' => 'agency_governance', 'status' => 'completed' === $status ? 'completed' : 'open', 'title' => ucwords( str_replace( '_', ' ', sanitize_key( $action ) ) ), 'summary' => sanitize_text_field( $message ), 'details' => array_merge( array( 'policy_id' => absint( $policy_id ), 'site_id' => absint( $site_id ) ), $details ) ),
			'portfolio_governance',
			absint( $user_id )
		);
		$this->logger->log( 'portfolio_governance', sanitize_key( $status ), sanitize_text_field( $message ) );
	}

	private function tables_ready() {
		global $wpdb;
		foreach ( array( $this->policies_table(), $this->assignments_table(), $this->inbox_table(), $this->events_table(), $this->agency_command->sites_table() ) as $table ) {
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) { return false; }
		}
		return true;
	}
}
