<?php

defined( 'ABSPATH' ) || exit;

class Ikon_SEO_Auth {
	private $connection;

	public function __construct( Ikon_SEO_Connection $connection ) {
		$this->connection = $connection;
	}

	public function permission( WP_REST_Request $request ) {
		return $this->check( $request, 'read' );
	}

	public function can_read( WP_REST_Request $request ) {
		return $this->check( $request, 'read' );
	}

	public function can_draft( WP_REST_Request $request ) {
		return $this->check( $request, 'draft' );
	}

	public function can_publishing_readiness( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		$approval_commands = array( 'mark_ready', 'record_manual_publication', 'complete_monitoring', 'block', 'unblock' );
		return $this->check( $request, in_array( $command, $approval_commands, true ) ? 'approve' : 'draft' );
	}

	public function can_search_impact( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		$approval_commands = array( 'assess', 'acknowledge', 'block', 'unblock' );
		return $this->check( $request, in_array( $command, $approval_commands, true ) ? 'approve' : 'draft' );
	}

	public function can_pattern_library( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		$approval_commands = array( 'validate', 'limit', 'reject', 'retire', 'restore' );
		return $this->check( $request, in_array( $command, $approval_commands, true ) ? 'approve' : 'draft' );
	}

	public function can_agency_service_levels( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		$approval_commands = array( 'approve_plan', 'retire_plan', 'assign_plan', 'update_assignment', 'approve_report', 'mark_report_delivered' );
		return $this->check( $request, in_array( $command, $approval_commands, true ) ? 'approve' : 'draft' );
	}

	public function can_portfolio_governance( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		$approval_commands = array( 'approve_policy', 'retire_policy', 'accept_proposal', 'reject_proposal' );
		return $this->check( $request, in_array( $command, $approval_commands, true ) ? 'approve' : 'draft' );
	}


	public function can_executive_command( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		$approval_commands = array( 'assign_risk', 'resolve_risk', 'reopen_risk', 'acknowledge_notification', 'dismiss_notification' );
		return $this->check( $request, in_array( $command, $approval_commands, true ) ? 'approve' : 'draft' );
	}



	public function can_client_portal_admin( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		$approval_commands = array( 'read', 'preview_user', 'create_access', 'activate_access', 'revoke_access', 'refresh_snapshot' );
		return $this->check( $request, in_array( $command, $approval_commands, true ) ? 'approve' : 'draft' );
	}

	public function can_platform_hardening( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		$approval_commands = array( 'restore_archive', 'repair_scheduler', 'cleanup' );
		return $this->check( $request, in_array( $command, $approval_commands, true ) ? 'approve' : 'draft' );
	}

	public function can_deployment_control( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		$approval_commands = array( 'create_evaluation', 'import_entitlement', 'revoke_entitlement', 'approve_plan', 'record_manual_deployment', 'close_plan' );
		return $this->check( $request, in_array( $command, $approval_commands, true ) ? 'approve' : 'draft' );
	}


	public function can_production_certification( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		$approval_commands = array( 'approve_contract', 'approve_certification', 'approve_rollout', 'record_rollout_result', 'close_rollout' );
		return $this->check( $request, in_array( $command, $approval_commands, true ) ? 'approve' : 'draft' );
	}

	public function can_staging_validation( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$command = sanitize_key( is_array( $payload ) ? ( $payload['command'] ?? 'read' ) : 'read' );
		return $this->check( $request, 'approve_run' === $command ? 'approve' : 'draft' );
	}

	public function can_approve( WP_REST_Request $request ) {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['remote_merge'] ) ) {
			return new WP_Error(
				'ikon_seo_remote_merge_disabled',
				__( 'Remote merge and rollback actions are disabled. Use the Ikon SEO Review screen in WordPress.', 'ikon-seo' ),
				array( 'status' => 403 )
			);
		}
		return $this->check( $request, 'approve' );
	}

	private function check( WP_REST_Request $request, $scope ) {
		$settings = Ikon_SEO_Plugin::settings();
		$provided = trim( (string) $request->get_header( 'x-ikon-seo-key' ) );

		if ( empty( $settings['remote_actions'] ) ) {
			return new WP_Error(
				'ikon_seo_remote_disabled',
				__( 'All Ikon SEO remote actions are currently disabled.', 'ikon-seo' ),
				array( 'status' => 503 )
			);
		}

		if ( ! $provided ) {
			$authorization = trim( (string) $request->get_header( 'authorization' ) );
			if ( 0 === stripos( $authorization, 'Bearer ' ) ) {
				$provided = trim( substr( $authorization, 7 ) );
			}
		}

		if ( empty( $settings['token_hash'] ) || ! $provided || ! wp_check_password( $provided, $settings['token_hash'] ) ) {
			return new WP_Error(
				'ikon_seo_unauthorized',
				__( 'A valid Ikon SEO connection key is required.', 'ikon-seo' ),
				array( 'status' => 401 )
			);
		}

		$scopes = is_array( $settings['key_scopes'] ) ? array_map( 'sanitize_key', $settings['key_scopes'] ) : array( 'read', 'draft' );
		if ( ! in_array( sanitize_key( $scope ), $scopes, true ) ) {
			return new WP_Error(
				'ikon_seo_scope_denied',
				sprintf( __( 'The current Ikon SEO connection key does not include the %s scope.', 'ikon-seo' ), sanitize_key( $scope ) ),
				array( 'status' => 403 )
			);
		}

		$max_bytes = max( 128, min( 4096, absint( $settings['max_payload_kb'] ) ) ) * KB_IN_BYTES;
		$body_size = strlen( (string) $request->get_body() );
		if ( $body_size > $max_bytes ) {
			return new WP_Error(
				'ikon_seo_payload_too_large',
				__( 'The request payload exceeds the configured Ikon SEO limit.', 'ikon-seo' ),
				array( 'status' => 413 )
			);
		}

		$limit     = max( 10, min( 300, absint( $settings['rate_limit'] ) ) );
		$rate_key  = 'ikon_seo_rate_' . gmdate( 'YmdH' ) . '_' . substr( hash( 'sha256', $provided ), 0, 12 );
		$rate_used = (int) get_transient( $rate_key );

		if ( $rate_used >= $limit ) {
			return new WP_Error(
				'ikon_seo_rate_limited',
				__( 'The hourly Ikon SEO request limit has been reached.', 'ikon-seo' ),
				array( 'status' => 429 )
			);
		}

		set_transient( $rate_key, $rate_used + 1, HOUR_IN_SECONDS + 60 );
		$this->connection->mark_seen();

		return true;
	}
}
