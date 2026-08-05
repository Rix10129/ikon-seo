<?php

defined( 'ABSPATH' ) || exit;

/**
 * Consolidated agency portfolio oversight and executive analytics.
 *
 * This layer reads stored snapshots, governance records and service-level
 * evidence. It never publishes, edits, deletes, schedules or merges content on
 * any managed website. Human approvals remain inside their originating module.
 */
final class Ikon_SEO_Executive_Command_Centre {
	const CRON_HOOK = 'ikon_seo_executive_command_refresh';
	const MAX_ITEMS = 500;

	private $agency_command;
	private $portfolio_governance;
	private $service_levels;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Agency_Command_Centre $agency_command,
		Ikon_SEO_Portfolio_Governance $portfolio_governance,
		Ikon_SEO_Agency_Service_Levels $service_levels,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->agency_command       = $agency_command;
		$this->portfolio_governance = $portfolio_governance;
		$this->service_levels       = $service_levels;
		$this->history              = $history;
		$this->logger               = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_refresh' ) );
	}

	public function risks_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_command_risks';
	}

	public function notifications_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_command_notifications';
	}

	public function status() {
		global $wpdb;
		$ready = $this->tables_ready();
		return array(
			'enabled'              => true,
			'database_ready'       => $ready,
			'open_risks'           => $ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->risks_table()} WHERE status='open'" ) ) : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'critical_risks'       => $ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->risks_table()} WHERE status='open' AND severity='critical'" ) ) : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'unread_notifications' => $ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->notifications_table()} WHERE status='unread'" ) ) : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'last_refresh'         => sanitize_text_field( get_option( 'ikon_seo_executive_command_last_refresh', '' ) ),
			'live_site_writes'     => false,
			'central_approvals'    => false,
		);
	}

	public function scheduled_refresh() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['agency_command_enabled'] ) ) {
			return;
		}
		$this->refresh( max( 1, min( 50, absint( $settings['agency_command_batch_size'] ?? 10 ) ) ), 0 );
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'refresh_site':
				$result = $this->agency_command->refresh_site( absint( $payload['site_id'] ?? 0 ) );
				if ( ! is_wp_error( $result ) ) { $this->refresh( 200, $user_id ); }
				break;
			case 'refresh_all':
			case 'refresh_portfolio':
				$result = $this->refresh( absint( $payload['limit'] ?? 100 ), $user_id, true );
				break;
			case 'record_usage':
				$result = $this->agency_command->record_usage( absint( $payload['site_id'] ?? 0 ), (array) ( $payload['usage'] ?? array() ), $user_id );
				break;
			case 'resolve_alert':
				$result = $this->agency_command->resolve_alert( absint( $payload['alert_id'] ?? 0 ), $user_id );
				break;
			case 'assign_risk':
				$result = $this->assign_risk( absint( $payload['risk_id'] ?? 0 ), absint( $payload['owner_id'] ?? 0 ), sanitize_text_field( $payload['due_at'] ?? '' ), $user_id );
				break;
			case 'resolve_risk':
				$result = $this->resolve_risk( absint( $payload['risk_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
				break;
			case 'reopen_risk':
				$result = $this->reopen_risk( absint( $payload['risk_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
				break;
			case 'acknowledge_notification':
				$result = $this->update_notification( absint( $payload['notification_id'] ?? 0 ), 'acknowledged', $user_id );
				break;
			case 'dismiss_notification':
				$result = $this->update_notification( absint( $payload['notification_id'] ?? 0 ), 'dismissed', $user_id );
				break;
			case 'client_portal_preview':
				$result = $this->client_portal_preview( absint( $payload['site_id'] ?? 0 ) );
				break;
			case 'read':
			default:
				$result = array( 'read_only' => true );
				break;
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return array(
			'command'        => $command,
			'result'         => $result,
			'command_centre' => $this->report( $this->filters_from_payload( $payload ) ),
		);
	}

	public function refresh( $limit = 100, $user_id = 0, $refresh_remote = false ) {
		$limit = max( 1, min( 200, absint( $limit ) ) );
		if ( $refresh_remote ) {
			$remote = $this->agency_command->refresh_all( min( 50, $limit ) );
			if ( is_wp_error( $remote ) ) {
				return $remote;
			}
		}
		$portfolio = $this->agency_command->summary( $limit );
		if ( empty( $portfolio['ready'] ) ) {
			return new WP_Error( 'ikon_seo_executive_portfolio', __( 'The Agency Command Centre database is not ready.', 'ikon-seo' ) );
		}
		$service    = $this->service_levels->report( array( 'limit' => self::MAX_ITEMS ) );
		$governance = $this->portfolio_governance->report( array( 'limit' => self::MAX_ITEMS ) );
		$approvals  = $this->build_approval_inbox( $portfolio, $governance, $service );
		$wanted     = $this->build_risks( $portfolio, $governance, $service, $approvals );
		$this->persist_risks( $wanted );
		$this->sync_notifications( $wanted, $approvals, $service );
		$now = current_time( 'mysql', true );
		update_option( 'ikon_seo_executive_command_last_refresh', $now, false );
		$this->logger->log( 'executive_command_refresh', 'success', sprintf( 'Executive portfolio analytics refreshed with %d active risks and %d approval items.', count( $wanted ), count( $approvals ) ) );
		$this->record_history( 'Agency portfolio analytics refreshed', sprintf( '%d active risks and %d approval items were consolidated.', count( $wanted ), count( $approvals ) ), array( 'risks' => count( $wanted ), 'approvals' => count( $approvals ) ), $user_id );
		return array( 'refreshed_at' => $now, 'risks' => count( $wanted ), 'approvals' => count( $approvals ) );
	}

	public function report( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'limit'       => 100,
				'site_id'     => 0,
				'severity'    => '',
				'approval_type'=> '',
				'owner_id'    => 0,
				'search'      => '',
			)
		);
		$limit      = max( 1, min( 200, absint( $args['limit'] ) ) );
		$portfolio  = $this->agency_command->summary( $limit );
		$service    = $this->service_levels->report( array( 'limit' => self::MAX_ITEMS ) );
		$governance = $this->portfolio_governance->report( array( 'limit' => self::MAX_ITEMS ) );
		$approvals  = $this->build_approval_inbox( $portfolio, $governance, $service );
		$risks      = $this->risks( 'open', self::MAX_ITEMS );
		$notifications = $this->notifications( 'active', self::MAX_ITEMS );
		$sites      = $this->build_site_scorecards( (array) ( $portfolio['sites'] ?? array() ), $risks, $approvals, $service, $governance );
		$filters    = $this->normalise_filters( $args );
		$sites      = $this->filter_items( $sites, $filters, 'site' );
		$risks      = $this->filter_items( $risks, $filters, 'risk' );
		$approvals  = $this->filter_items( $approvals, $filters, 'approval' );
		$notifications = $this->filter_items( $notifications, $filters, 'notification' );
		$forecast   = $this->forecast_capacity_from_report( $service );
		$metrics    = $this->executive_metrics( $sites, $risks, $approvals, $service, $forecast );

		return array(
			'ready'          => ! empty( $portfolio['ready'] ) && $this->tables_ready(),
			'status'         => $this->status(),
			'metrics'        => $metrics,
			'sites'          => array_slice( $sites, 0, $limit ),
			'approvals'      => array_slice( $approvals, 0, self::MAX_ITEMS ),
			'risks'          => array_slice( $risks, 0, self::MAX_ITEMS ),
			'notifications'  => array_slice( $notifications, 0, self::MAX_ITEMS ),
			'capacity_forecast' => $forecast,
			'portfolio_analytics' => $this->portfolio_analytics( $sites, $service ),
			'filters'        => $filters,
			'generated_at'   => current_time( 'mysql', true ),
			'safety'         => array(
				'remote_monitoring_only' => true,
				'approvals_remain_local' => true,
				'publishes_content'      => false,
				'sends_client_messages'  => false,
				'automatic_reassignment' => false,
			),
		);
	}

	/** Transparent, deterministic portfolio-health calculation. */
	public function calculate_health( array $site, array $risk_counts = array(), array $service = array() ) {
		$snapshot = (array) ( $site['snapshot'] ?? array() );
		$components = array(
			'strategy'   => min( 20, round( 20 * absint( $snapshot['strategy']['readiness'] ?? 0 ) / 100, 1 ) ),
			'evidence'   => 15,
			'technical'  => 15,
			'workflow'   => 15,
			'publishing' => 10,
			'measurement'=> 10,
			'sla'        => 10,
			'connection' => 5,
		);

		if ( ! empty( $site['stale'] ) ) { $components['evidence'] -= 8; }
		if ( empty( $snapshot['connections']['search_console']['connected'] ) ) { $components['evidence'] -= 3; }
		if ( empty( $snapshot['connections']['analytics']['connected'] ) ) { $components['evidence'] -= 2; }
		$components['evidence'] = max( 0, $components['evidence'] );

		$technical_penalty = min( 15, 5 * absint( $snapshot['diagnostics']['blockers']['critical'] ?? 0 ) + 2 * absint( $snapshot['diagnostics']['blockers']['high'] ?? 0 ) + min( 5, absint( $snapshot['technical']['failed_urls'] ?? 0 ) ) );
		$components['technical'] = max( 0, 15 - $technical_penalty );

		$workflow_penalty = min( 15, 3 * absint( $snapshot['workflow']['overdue'] ?? 0 ) + min( 6, absint( $risk_counts['workflow'] ?? 0 ) ) );
		$components['workflow'] = max( 0, 15 - $workflow_penalty );

		$operations = (array) ( $snapshot['operations'] ?? array() );
		$publishing_counts = (array) ( $operations['publishing']['counts'] ?? array() );
		if ( absint( $publishing_counts['issues_found'] ?? 0 ) ) { $components['publishing'] -= 5; }
		if ( absint( $publishing_counts['ready_for_manual_publish'] ?? 0 ) ) { $components['publishing'] -= 2; }
		$components['publishing'] = max( 0, $components['publishing'] );

		$impact_counts = (array) ( $operations['search_impact']['counts'] ?? array() );
		if ( ! array_sum( array_map( 'absint', $impact_counts ) ) ) { $components['measurement'] -= 5; }
		if ( absint( $impact_counts['ready_for_assessment'] ?? 0 ) ) { $components['measurement'] -= 2; }
		$components['measurement'] = max( 0, $components['measurement'] );

		$compliance = isset( $service['compliance_score'] ) ? (float) $service['compliance_score'] : 100;
		$components['sla'] = max( 0, min( 10, round( $compliance / 10, 1 ) ) );

		if ( 'connected' !== ( $site['status'] ?? '' ) || ! empty( $site['last_error'] ) ) { $components['connection'] = 0; }
		elseif ( ! empty( $site['stale'] ) ) { $components['connection'] = 2; }

		foreach ( $components as $key => $value ) { $components[ $key ] = max( 0, round( (float) $value, 1 ) ); }
		$score = (int) round( array_sum( $components ) );
		return array(
			'score'      => max( 0, min( 100, $score ) ),
			'components' => $components,
			'level'      => $score >= 85 ? 'healthy' : ( $score >= 70 ? 'watch' : ( $score >= 50 ? 'attention' : 'critical' ) ),
			'methodology'=> 'Transparent operational health score. It is not a ranking score and does not predict leads or revenue.',
		);
	}

	/** Deterministic capacity forecast used by runtime and release tests. */
	public function forecast_capacity_from_report( array $service_report ) {
		$capacity = (array) ( $service_report['capacity'] ?? array() );
		$work     = (array) ( $service_report['work_items'] ?? array() );
		$people   = array();
		$total_capacity = 0;
		$total_allocated = 0;
		foreach ( $capacity as $row ) {
			$cap = absint( $row['capacity_units'] ?? 0 );
			$allocated = absint( $row['allocated_units'] ?? 0 );
			$total_capacity += $cap;
			$total_allocated += $allocated;
			$people[] = array(
				'user_id' => absint( $row['user_id'] ?? 0 ),
				'display_name' => sanitize_text_field( $row['display_name'] ?? '' ),
				'capacity_units' => $cap,
				'committed_units' => $allocated,
				'remaining_units' => max( 0, $cap - $allocated ),
				'utilisation_percent' => $cap ? round( 100 * $allocated / $cap, 1 ) : 0,
				'over_capacity' => $allocated > $cap,
			);
		}
		$unassigned = 0;
		$overdue = 0;
		$upcoming_units = 0;
		$now = time();
		$next_30 = $now + 30 * DAY_IN_SECONDS;
		foreach ( $work as $item ) {
			if ( ! in_array( sanitize_key( $item['status'] ?? '' ), array( 'planned','in_progress','awaiting_client','awaiting_approval' ), true ) ) { continue; }
			if ( ! absint( $item['owner_id'] ?? 0 ) ) { $unassigned++; }
			$due = strtotime( (string) ( $item['due_at'] ?? '' ) . ' UTC' );
			if ( $due && $due < $now ) { $overdue++; }
			if ( ! $due || $due <= $next_30 ) { $upcoming_units += absint( $item['units'] ?? 0 ); }
		}
		$utilisation = $total_capacity ? round( 100 * $total_allocated / $total_capacity, 1 ) : 0;
		return array(
			'period' => array( 'start' => gmdate( 'Y-m-01' ), 'end' => gmdate( 'Y-m-t' ) ),
			'total_capacity_units' => $total_capacity,
			'committed_units' => $total_allocated,
			'remaining_units' => max( 0, $total_capacity - $total_allocated ),
			'utilisation_percent' => $utilisation,
			'forecast_30_day_units' => $upcoming_units,
			'unassigned_items' => $unassigned,
			'overdue_items' => $overdue,
			'at_risk' => $utilisation >= 90 || $overdue > 0 || $unassigned > 0,
			'people' => $people,
			'automatic_reassignment' => false,
		);
	}

	public function assign_risk( $risk_id, $owner_id, $due_at, $user_id = 0 ) {
		global $wpdb;
		$risk = $this->get_risk( $risk_id );
		if ( ! $risk || 'open' !== $risk['status'] ) { return new WP_Error( 'ikon_seo_command_risk_missing', __( 'The open risk was not found.', 'ikon-seo' ) ); }
		if ( $owner_id && ! get_userdata( $owner_id ) ) { return new WP_Error( 'ikon_seo_command_risk_owner', __( 'Select a valid WordPress user.', 'ikon-seo' ) ); }
		$normalised_due = $this->normalise_datetime( $due_at );
		$wpdb->update( $this->risks_table(), array( 'owner_id' => absint( $owner_id ), 'due_at' => $normalised_due ?: null, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $risk_id ) ) );
		$this->record_history( 'Portfolio risk assigned', 'A portfolio risk received an owner or due date.', array( 'risk_id' => absint( $risk_id ), 'owner_id' => absint( $owner_id ), 'due_at' => $normalised_due ), $user_id );
		return $this->get_risk( $risk_id );
	}

	public function resolve_risk( $risk_id, $notes, $user_id = 0 ) {
		global $wpdb;
		$risk = $this->get_risk( $risk_id );
		if ( ! $risk || 'open' !== $risk['status'] ) { return new WP_Error( 'ikon_seo_command_risk_missing', __( 'The open risk was not found.', 'ikon-seo' ) ); }
		if ( ! trim( (string) $notes ) ) { return new WP_Error( 'ikon_seo_command_risk_notes', __( 'Enter a resolution note.', 'ikon-seo' ) ); }
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->risks_table(), array( 'status' => 'resolved', 'resolution_notes' => sanitize_textarea_field( $notes ), 'resolved_by' => absint( $user_id ), 'resolved_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $risk_id ) ) );
		$this->record_history( 'Portfolio risk resolved', sanitize_textarea_field( $notes ), array( 'risk_id' => absint( $risk_id ) ), $user_id );
		return $this->get_risk( $risk_id );
	}

	public function reopen_risk( $risk_id, $notes, $user_id = 0 ) {
		global $wpdb;
		$risk = $this->get_risk( $risk_id );
		if ( ! $risk || 'resolved' !== $risk['status'] ) { return new WP_Error( 'ikon_seo_command_risk_reopen', __( 'The resolved risk was not found.', 'ikon-seo' ) ); }
		if ( ! trim( (string) $notes ) ) { return new WP_Error( 'ikon_seo_command_risk_notes', __( 'Enter a reopening reason.', 'ikon-seo' ) ); }
		$wpdb->update( $this->risks_table(), array( 'status' => 'open', 'resolution_notes' => sanitize_textarea_field( $notes ), 'resolved_by' => 0, 'resolved_at' => null, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $risk_id ) ) );
		$this->record_history( 'Portfolio risk reopened', sanitize_textarea_field( $notes ), array( 'risk_id' => absint( $risk_id ) ), $user_id );
		return $this->get_risk( $risk_id );
	}

	public function update_notification( $notification_id, $status, $user_id = 0 ) {
		global $wpdb;
		$status = sanitize_key( $status );
		if ( ! in_array( $status, array( 'acknowledged', 'dismissed' ), true ) ) { return new WP_Error( 'ikon_seo_command_notification_status', __( 'The notification decision is invalid.', 'ikon-seo' ) ); }
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->notifications_table()} WHERE id=%d", absint( $notification_id ) ), ARRAY_A );
		if ( ! $row ) { return new WP_Error( 'ikon_seo_command_notification_missing', __( 'The notification was not found.', 'ikon-seo' ) ); }
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->notifications_table(), array( 'status' => $status, 'acknowledged_by' => absint( $user_id ), 'acknowledged_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $notification_id ) ) );
		return array( 'notification_id' => absint( $notification_id ), 'status' => $status );
	}

	public function client_portal_preview( $site_id ) {
		global $wpdb;
		$site_id = absint( $site_id );
		$portfolio = $this->agency_command->summary( 200 );
		$site = null;
		foreach ( (array) ( $portfolio['sites'] ?? array() ) as $candidate ) { if ( absint( $candidate['id'] ?? 0 ) === $site_id ) { $site = $candidate; break; } }
		if ( ! $site ) { return new WP_Error( 'ikon_seo_command_site_missing', __( 'The managed website was not found.', 'ikon-seo' ) ); }
		$assignment = $wpdb->get_row( $wpdb->prepare( "SELECT a.*,p.name plan_name,p.plan_json FROM {$this->service_levels->assignments_table()} a LEFT JOIN {$this->service_levels->plans_table()} p ON p.id=a.plan_id WHERE a.site_id=%d AND a.status IN ('active','paused') ORDER BY a.updated_at DESC LIMIT 1", $site_id ), ARRAY_A );
		$reports = $wpdb->get_results( $wpdb->prepare( "SELECT id,period_start,period_end,status,delivered_at,delivery_method FROM {$this->service_levels->reports_table()} WHERE site_id=%d AND status IN ('approved','delivered') ORDER BY period_end DESC LIMIT 12", $site_id ), ARRAY_A );
		$completed = $wpdb->get_results( $wpdb->prepare( "SELECT id,title,category,completed_at FROM {$this->service_levels->work_items_table()} WHERE site_id=%d AND status='completed' ORDER BY completed_at DESC LIMIT 50", $site_id ), ARRAY_A );
		$planned = $wpdb->get_results( $wpdb->prepare( "SELECT id,title,category,status,due_at FROM {$this->service_levels->work_items_table()} WHERE site_id=%d AND status IN ('planned','in_progress','awaiting_client','awaiting_approval') ORDER BY due_at ASC LIMIT 50", $site_id ), ARRAY_A );
		$plan = $assignment ? json_decode( $assignment['plan_json'], true ) : array();
		return array(
			'site' => array( 'id' => $site_id, 'name' => sanitize_text_field( $site['site_name'] ?? '' ), 'url' => esc_url_raw( $site['site_url'] ?? '' ), 'client_name' => sanitize_text_field( $site['client_name'] ?? '' ) ),
			'service_scope' => array( 'plan_name' => sanitize_text_field( $assignment['plan_name'] ?? '' ), 'status' => sanitize_key( $assignment['status'] ?? '' ), 'included_deliverables' => array_values( array_map( 'sanitize_text_field', (array) ( $plan['included_deliverables'] ?? array() ) ) ), 'excluded_services' => array_values( array_map( 'sanitize_text_field', (array) ( $plan['excluded_services'] ?? array() ) ) ) ),
			'approved_reports' => array_map( array( $this, 'sanitize_public_row' ), $reports ?: array() ),
			'completed_work' => array_map( array( $this, 'sanitize_public_row' ), $completed ?: array() ),
			'planned_work' => array_map( array( $this, 'sanitize_public_row' ), $planned ?: array() ),
			'outstanding_client_approvals' => array_values( array_filter( $this->build_approval_inbox( $portfolio, $this->portfolio_governance->report( array( 'limit' => 500 ) ), $this->service_levels->report( array( 'limit' => 500 ) ) ), function( $item ) use ( $site_id ) { return absint( $item['site_id'] ?? 0 ) === $site_id && in_array( $item['type'] ?? '', array( 'client_report', 'awaiting_client' ), true ); } ) ),
			'publicly_exposed' => false,
			'hides_internal_notes' => true,
			'hides_other_websites' => true,
		);
	}

	private function build_approval_inbox( array $portfolio, array $governance, array $service ) {
		$items = array();
		foreach ( (array) ( $portfolio['sites'] ?? array() ) as $site ) {
			$snapshot = (array) ( $site['snapshot'] ?? array() );
			foreach ( (array) ( $snapshot['operations']['approval_items'] ?? array() ) as $item ) {
				$items[] = $this->normalise_approval( $item, $site );
			}
			foreach ( (array) ( $snapshot['approvals']['review_drafts'] ?? array() ) as $item ) {
				$items[] = $this->normalise_approval( array( 'type' => 'page_review', 'id' => absint( $item['draft_id'] ?? 0 ), 'title' => sanitize_text_field( $item['draft_title'] ?? 'Draft review' ), 'status' => sanitize_key( $item['quality_status'] ?? 'pending' ), 'priority' => absint( $item['quality_score'] ?? 0 ), 'review_url' => esc_url_raw( $item['edit_url'] ?? '' ) ), $site );
			}
			foreach ( (array) ( $snapshot['approvals']['workflow_tasks'] ?? array() ) as $item ) {
				$items[] = $this->normalise_approval( array_merge( $item, array( 'type' => 'workflow_task', 'review_url' => esc_url_raw( $snapshot['site']['admin_url'] ?? '' ) ) ), $site );
			}
		}
		foreach ( (array) ( $governance['inbox'] ?? array() ) as $item ) {
			if ( 'pending_local_approval' !== ( $item['status'] ?? '' ) ) { continue; }
			$items[] = array( 'key' => 'governance:' . absint( $item['id'] ?? 0 ), 'site_id' => 0, 'site_name' => 'This website', 'client_name' => '', 'type' => 'governance_policy', 'title' => sanitize_text_field( $item['policy_name'] ?? 'Governance policy proposal' ), 'status' => 'pending_local_approval', 'priority' => 90, 'due_at' => '', 'owner_id' => 0, 'review_url' => admin_url( 'admin.php?page=ikon-seo&tab=agency-governance' ), 'source_id' => absint( $item['id'] ?? 0 ) );
		}
		foreach ( (array) ( $service['reports'] ?? array() ) as $item ) {
			if ( 'review_ready' !== ( $item['status'] ?? '' ) ) { continue; }
			$items[] = array( 'key' => 'client_report:' . absint( $item['id'] ?? 0 ), 'site_id' => absint( $item['site_id'] ?? 0 ), 'site_name' => sanitize_text_field( $item['site_name'] ?? '' ), 'client_name' => '', 'type' => 'client_report', 'title' => 'Client report for ' . sanitize_text_field( $item['period_end'] ?? '' ), 'status' => 'review_ready', 'priority' => 75, 'due_at' => sanitize_text_field( $item['period_end'] ?? '' ), 'owner_id' => absint( $item['prepared_by'] ?? 0 ), 'review_url' => admin_url( 'admin.php?page=ikon-seo&tab=agency-service-levels' ), 'source_id' => absint( $item['id'] ?? 0 ) );
		}
		foreach ( (array) ( $service['work_items'] ?? array() ) as $item ) {
			if ( ! in_array( $item['status'] ?? '', array( 'awaiting_approval','awaiting_client' ), true ) ) { continue; }
			$items[] = array( 'key' => 'service_work:' . absint( $item['id'] ?? 0 ), 'site_id' => absint( $item['site_id'] ?? 0 ), 'site_name' => sanitize_text_field( $item['site_name'] ?? '' ), 'client_name' => '', 'type' => sanitize_key( $item['status'] ), 'title' => sanitize_text_field( $item['title'] ?? 'Service work item' ), 'status' => sanitize_key( $item['status'] ), 'priority' => 'urgent' === ( $item['priority'] ?? '' ) ? 100 : ( 'high' === ( $item['priority'] ?? '' ) ? 80 : 60 ), 'due_at' => sanitize_text_field( $item['due_at'] ?? '' ), 'owner_id' => absint( $item['owner_id'] ?? 0 ), 'review_url' => admin_url( 'admin.php?page=ikon-seo&tab=agency-service-levels' ), 'source_id' => absint( $item['id'] ?? 0 ) );
		}
		$deduped = array();
		foreach ( $items as $item ) { $deduped[ $item['key'] ] = $item; }
		$items = array_values( $deduped );
		usort( $items, function( $a, $b ) { return ( $b['priority'] <=> $a['priority'] ) ?: strcmp( (string) $a['due_at'], (string) $b['due_at'] ); } );
		return array_slice( $items, 0, self::MAX_ITEMS );
	}

	private function normalise_approval( array $item, array $site ) {
		$type = sanitize_key( $item['type'] ?? 'approval' );
		$id = absint( $item['id'] ?? $item['source_id'] ?? 0 );
		return array(
			'key' => $type . ':' . absint( $site['id'] ?? 0 ) . ':' . $id,
			'site_id' => absint( $site['id'] ?? 0 ),
			'site_name' => sanitize_text_field( $site['site_name'] ?? '' ),
			'client_name' => sanitize_text_field( $site['client_name'] ?? '' ),
			'type' => $type,
			'title' => sanitize_text_field( $item['title'] ?? 'Approval required' ),
			'status' => sanitize_key( $item['status'] ?? 'pending' ),
			'priority' => max( 0, min( 100, absint( $item['priority'] ?? 50 ) ) ),
			'due_at' => sanitize_text_field( $item['due_at'] ?? '' ),
			'owner_id' => absint( $item['owner_id'] ?? 0 ),
			'review_url' => esc_url_raw( $item['review_url'] ?? $item['url'] ?? ( $site['snapshot']['site']['admin_url'] ?? '' ) ),
			'source_id' => $id,
		);
	}

	private function build_risks( array $portfolio, array $governance, array $service, array $approvals ) {
		$wanted = array();
		$approvals_by_site = array();
		foreach ( $approvals as $item ) { $sid = absint( $item['site_id'] ?? 0 ); $approvals_by_site[ $sid ] = ( $approvals_by_site[ $sid ] ?? 0 ) + 1; }
		$service_by_site = $this->service_by_site( $service );
		foreach ( (array) ( $portfolio['sites'] ?? array() ) as $site ) {
			$sid = absint( $site['id'] ?? 0 );
			$snapshot = (array) ( $site['snapshot'] ?? array() );
			if ( 'connected' !== ( $site['status'] ?? '' ) || ! empty( $site['last_error'] ) ) { $this->add_risk( $wanted, $sid, 'connection', 'critical', 'Managed website connection failed', sanitize_text_field( $site['last_error'] ?? 'The latest remote snapshot was unavailable.' ), 'Restore the read-only site connection and refresh the snapshot.', 'agency_snapshot' ); }
			elseif ( ! empty( $site['stale'] ) ) { $this->add_risk( $wanted, $sid, 'evidence_freshness', 'high', 'Managed website snapshot is stale', sprintf( 'The latest stored snapshot is approximately %s hours old.', sanitize_text_field( $site['snapshot_age_hours'] ?? 'unknown' ) ), 'Refresh the website snapshot and investigate cron or connection failures.', 'agency_snapshot' ); }
			$readiness = absint( $snapshot['strategy']['readiness'] ?? 0 );
			if ( $readiness < 70 ) { $this->add_risk( $wanted, $sid, 'strategy', 'high', 'Website strategy is below the operating threshold', sprintf( 'Strategy readiness is %d%%.', $readiness ), 'Complete Fact Review and confirm the Website Strategy.', 'strategy' ); }
			$review = (array) ( $snapshot['operations']['discovery_review'] ?? array() );
			if ( absint( $review['unresolved'] ?? 0 ) ) { $this->add_risk( $wanted, $sid, 'fact_review', 'high', 'Business facts still require review', sprintf( '%d discovery facts or conflicts remain unresolved.', absint( $review['unresolved'] ) ), 'Resolve uncertain, changed or conflicting facts before activating more work.', 'discovery_review' ); }
			$ops = (array) ( $snapshot['operations'] ?? array() );
			if ( absint( $ops['content']['counts']['outdated'] ?? 0 ) ) { $this->add_risk( $wanted, $sid, 'content_evidence', 'high', 'Content briefs or drafts use outdated evidence', sprintf( '%d content records require regeneration or reapproval.', absint( $ops['content']['counts']['outdated'] ) ), 'Regenerate the affected brief and repeat approval before continuing.', 'content_workbench' ); }
			if ( absint( $ops['editorial']['overdue'] ?? 0 ) ) { $this->add_risk( $wanted, $sid, 'editorial', 'high', 'Editorial review is overdue', sprintf( '%d assigned editorial review deadlines have passed.', absint( $ops['editorial']['overdue'] ) ), 'Confirm ownership, request a revised date, or block the review with a reason.', 'editorial_review' ); }
			if ( absint( $ops['publishing']['counts']['issues_found'] ?? 0 ) ) { $this->add_risk( $wanted, $sid, 'publishing', 'critical', 'Published-page verification found issues', sprintf( '%d publishing release records require investigation.', absint( $ops['publishing']['counts']['issues_found'] ) ), 'Review the release verification evidence and record a controlled resolution.', 'publishing_readiness' ); }
			if ( absint( $ops['publishing']['counts']['ready_for_manual_publish'] ?? 0 ) ) { $this->add_risk( $wanted, $sid, 'publishing_action', 'medium', 'Approved releases await manual publication', sprintf( '%d release candidates are ready for a separate WordPress publishing decision.', absint( $ops['publishing']['counts']['ready_for_manual_publish'] ) ), 'Review and publish manually in WordPress, or block the release with a reason.', 'publishing_readiness' ); }
			if ( absint( $ops['search_impact']['counts']['ready_for_assessment'] ?? 0 ) ) { $this->add_risk( $wanted, $sid, 'measurement', 'medium', 'Search Impact evidence awaits assessment', sprintf( '%d studies have sufficient stored evidence for human assessment.', absint( $ops['search_impact']['counts']['ready_for_assessment'] ) ), 'Review confounders and record an associated-signal assessment.', 'search_impact' ); }
			if ( absint( $approvals_by_site[ $sid ] ?? 0 ) ) { $this->add_risk( $wanted, $sid, 'approval', 'medium', 'Human decisions are waiting', sprintf( '%d approval items are waiting across the controlled workflow.', absint( $approvals_by_site[ $sid ] ) ), 'Open the unified approval inbox and process the highest-priority decision.', 'approval_inbox' ); }
			$svc = $service_by_site[ $sid ] ?? array();
			if ( absint( $svc['overdue_items'] ?? 0 ) ) { $this->add_risk( $wanted, $sid, 'service_level', 'high', 'Service-level work is overdue', sprintf( '%d capacity-controlled work items are overdue.', absint( $svc['overdue_items'] ) ), 'Review ownership, scope and due dates without silently increasing capacity.', 'service_levels' ); }
			if ( ! empty( $svc['report_overdue'] ) ) { $this->add_risk( $wanted, $sid, 'client_reporting', 'medium', 'Client report is overdue', 'The active service assignment has no delivered report in its expected reporting window.', 'Generate an evidence-locked report, obtain separate approval, then deliver it manually.', 'service_levels' ); }
		}
		foreach ( (array) ( $governance['assignments'] ?? array() ) as $assignment ) {
			if ( in_array( $assignment['status'] ?? '', array( 'error','pending_local_approval' ), true ) || ! empty( $assignment['last_error'] ) ) {
				$this->add_risk( $wanted, absint( $assignment['site_id'] ?? 0 ), 'governance', 'high', 'Agency governance requires attention', sanitize_text_field( $assignment['last_error'] ?? 'A governance proposal is waiting for local review.' ), 'Review the proposal or connection error. Remote activation remains prohibited.', 'portfolio_governance' );
			}
		}
		return $wanted;
	}

	private function add_risk( array &$wanted, $site_id, $category, $severity, $title, $evidence, $action, $source ) {
		$key = hash( 'sha256', absint( $site_id ) . '|' . sanitize_key( $source ) . '|' . sanitize_key( $category ) . '|' . sanitize_text_field( $title ) );
		$wanted[ $key ] = array(
			'risk_key' => $key, 'site_id' => absint( $site_id ), 'category' => sanitize_key( $category ), 'severity' => sanitize_key( $severity ),
			'title' => sanitize_text_field( $title ), 'evidence' => array( 'summary' => sanitize_textarea_field( $evidence ) ),
			'recommended_action' => sanitize_textarea_field( $action ), 'source' => sanitize_key( $source ),
		);
	}

	private function persist_risks( array $wanted ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return; }
		$now = current_time( 'mysql', true );
		foreach ( $wanted as $key => $risk ) {
			$sql = "INSERT INTO {$this->risks_table()} (site_id,risk_key,category,severity,title,evidence_json,owner_id,due_at,recommended_action,status,source,first_seen_at,last_seen_at,resolution_notes,resolved_by,resolved_at,created_at,updated_at)
			VALUES (%d,%s,%s,%s,%s,%s,0,NULL,%s,'open',%s,%s,%s,'',0,NULL,%s,%s)
			ON DUPLICATE KEY UPDATE category=VALUES(category),severity=VALUES(severity),title=VALUES(title),evidence_json=VALUES(evidence_json),recommended_action=VALUES(recommended_action),status='open',last_seen_at=VALUES(last_seen_at),resolved_by=0,resolved_at=NULL,updated_at=VALUES(updated_at)";
			$wpdb->query( $wpdb->prepare( $sql, $risk['site_id'], $key, $risk['category'], $risk['severity'], $risk['title'], wp_json_encode( $risk['evidence'] ), $risk['recommended_action'], $risk['source'], $now, $now, $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$existing = $wpdb->get_results( "SELECT id,risk_key FROM {$this->risks_table()} WHERE status='open'", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $existing ?: array() as $row ) {
			if ( ! isset( $wanted[ $row['risk_key'] ] ) ) {
				$wpdb->update( $this->risks_table(), array( 'status' => 'resolved', 'resolution_notes' => 'Automatically resolved because the latest stored evidence no longer shows this condition.', 'resolved_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $row['id'] ) ) );
			}
		}
	}

	private function sync_notifications( array $risks, array $approvals, array $service ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return; }
		$wanted = array();
		foreach ( $risks as $risk ) {
			if ( ! in_array( $risk['severity'], array( 'critical','high' ), true ) ) { continue; }
			$key = 'risk:' . $risk['risk_key'];
			$wanted[ $key ] = array( 'site_id' => $risk['site_id'], 'type' => 'risk', 'severity' => $risk['severity'], 'title' => $risk['title'], 'summary' => $risk['evidence']['summary'], 'source_ref' => $risk['risk_key'] );
		}
		$now_ts = time();
		foreach ( $approvals as $approval ) {
			$due = strtotime( (string) $approval['due_at'] . ' UTC' );
			if ( ! $due || $due >= $now_ts ) { continue; }
			$key = 'approval:' . $approval['key'];
			$wanted[ $key ] = array( 'site_id' => absint( $approval['site_id'] ), 'type' => 'approval_overdue', 'severity' => 'high', 'title' => 'Approval is overdue', 'summary' => sanitize_text_field( $approval['title'] ), 'source_ref' => $approval['key'] );
		}
		$forecast = $this->forecast_capacity_from_report( $service );
		if ( ! empty( $forecast['at_risk'] ) ) {
			$key = 'capacity:' . gmdate( 'Y-m' );
			$wanted[ $key ] = array( 'site_id' => 0, 'type' => 'capacity', 'severity' => $forecast['utilisation_percent'] >= 100 ? 'critical' : 'high', 'title' => 'Agency capacity requires attention', 'summary' => sprintf( 'Capacity utilisation is %.1f%% with %d overdue and %d unassigned items.', (float) $forecast['utilisation_percent'], absint( $forecast['overdue_items'] ), absint( $forecast['unassigned_items'] ) ), 'source_ref' => gmdate( 'Y-m' ) );
		}
		$now = current_time( 'mysql', true );
		foreach ( $wanted as $key => $item ) {
			$sql = "INSERT INTO {$this->notifications_table()} (site_id,notification_key,notification_type,severity,title,summary,status,source_ref,acknowledged_by,acknowledged_at,created_at,updated_at)
			VALUES (%d,%s,%s,%s,%s,%s,'unread',%s,0,NULL,%s,%s)
			ON DUPLICATE KEY UPDATE notification_type=VALUES(notification_type),severity=VALUES(severity),title=VALUES(title),summary=VALUES(summary),source_ref=VALUES(source_ref),status=IF(status='dismissed','dismissed',status),updated_at=VALUES(updated_at)";
			$wpdb->query( $wpdb->prepare( $sql, $item['site_id'], $key, $item['type'], $item['severity'], $item['title'], $item['summary'], $item['source_ref'], $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}

	private function build_site_scorecards( array $sites, array $risks, array $approvals, array $service, array $governance ) {
		$risk_counts = array();
		foreach ( $risks as $risk ) { $sid = absint( $risk['site_id'] ); if ( ! isset( $risk_counts[ $sid ] ) ) { $risk_counts[ $sid ] = array( 'total' => 0 ); } $risk_counts[ $sid ]['total']++; $risk_counts[ $sid ][ $risk['category'] ] = ( $risk_counts[ $sid ][ $risk['category'] ] ?? 0 ) + 1; }
		$approval_counts = array(); foreach ( $approvals as $approval ) { $sid = absint( $approval['site_id'] ); $approval_counts[ $sid ] = ( $approval_counts[ $sid ] ?? 0 ) + 1; }
		$service_by_site = $this->service_by_site( $service );
		$governance_by_site = array(); foreach ( (array) ( $governance['assignments'] ?? array() ) as $g ) { $governance_by_site[ absint( $g['site_id'] ?? 0 ) ] = $g; }
		$out = array();
		foreach ( $sites as $site ) {
			$sid = absint( $site['id'] ?? 0 );
			$svc = $service_by_site[ $sid ] ?? array();
			$health = $this->calculate_health( $site, $risk_counts[ $sid ] ?? array(), $svc );
			$snapshot = (array) ( $site['snapshot'] ?? array() );
			$out[] = array(
				'id' => $sid, 'client_name' => sanitize_text_field( $site['client_name'] ?? '' ), 'group_name' => sanitize_text_field( $site['group_name'] ?? '' ), 'site_name' => sanitize_text_field( $site['site_name'] ?? '' ), 'site_url' => esc_url_raw( $site['site_url'] ?? '' ),
				'status' => sanitize_key( $site['status'] ?? '' ), 'stale' => ! empty( $site['stale'] ), 'last_snapshot_at' => sanitize_text_field( $site['last_snapshot_at'] ?? '' ),
				'health' => $health, 'risk_count' => absint( $risk_counts[ $sid ]['total'] ?? 0 ), 'approval_count' => absint( $approval_counts[ $sid ] ?? 0 ),
				'strategy_readiness' => absint( $snapshot['strategy']['readiness'] ?? 0 ), 'opportunities' => (array) ( $snapshot['operations']['opportunities']['counts'] ?? array() ), 'content' => (array) ( $snapshot['operations']['content']['counts'] ?? array() ), 'editorial' => (array) ( $snapshot['operations']['editorial']['counts'] ?? array() ), 'publishing' => (array) ( $snapshot['operations']['publishing']['counts'] ?? array() ), 'search_impact' => (array) ( $snapshot['operations']['search_impact']['counts'] ?? array() ),
				'governance' => array( 'status' => sanitize_key( $governance_by_site[ $sid ]['status'] ?? $site['governance']['status'] ?? 'not_configured' ), 'last_sync_at' => sanitize_text_field( $governance_by_site[ $sid ]['last_sync_at'] ?? $site['governance']['last_sync_at'] ?? '' ) ),
				'service' => $svc,
				'next_action' => $this->next_action_for_site( $health, $risk_counts[ $sid ] ?? array(), absint( $approval_counts[ $sid ] ?? 0 ) ),
			);
		}
		usort( $out, function( $a, $b ) { return ( $a['health']['score'] <=> $b['health']['score'] ) ?: ( $b['risk_count'] <=> $a['risk_count'] ); } );
		return $out;
	}

	private function next_action_for_site( array $health, array $risks, $approvals ) {
		if ( ! empty( $risks['connection'] ) || ! empty( $risks['evidence_freshness'] ) ) { return 'Restore and refresh the managed-site connection.'; }
		if ( ! empty( $risks['fact_review'] ) || ! empty( $risks['strategy'] ) ) { return 'Complete Fact Review and Website Strategy confirmation.'; }
		if ( $approvals ) { return 'Process the highest-priority item in the approval inbox.'; }
		if ( ! empty( $risks['service_level'] ) ) { return 'Resolve overdue service work and confirm ownership.'; }
		if ( $health['score'] < 70 ) { return 'Review the lowest-scoring health component and its evidence.'; }
		return 'Continue the approved Operating Plan and monitoring cadence.';
	}

	private function service_by_site( array $service ) {
		$out = array();
		$assignments = array();
		foreach ( (array) ( $service['assignments'] ?? array() ) as $a ) { if ( in_array( $a['status'] ?? '', array( 'active','paused' ), true ) ) { $assignments[ absint( $a['site_id'] ?? 0 ) ] = $a; } }
		foreach ( $assignments as $sid => $assignment ) {
			$out[ $sid ] = array( 'assignment_id' => absint( $assignment['id'] ?? 0 ), 'plan_name' => sanitize_text_field( $assignment['plan']['name'] ?? $assignment['plan_name'] ?? '' ), 'assignment_status' => sanitize_key( $assignment['status'] ?? '' ), 'capacity_units' => absint( $assignment['capacity_units'] ?? 0 ), 'used_units' => 0, 'open_items' => 0, 'overdue_items' => 0, 'report_overdue' => false, 'compliance_score' => 100 );
		}
		$now = time();
		foreach ( (array) ( $service['work_items'] ?? array() ) as $item ) {
			$sid = absint( $item['site_id'] ?? 0 ); if ( ! isset( $out[ $sid ] ) ) { continue; }
			if ( 'cancelled' !== ( $item['status'] ?? '' ) ) { $out[ $sid ]['used_units'] += absint( $item['units'] ?? 0 ); }
			if ( in_array( $item['status'] ?? '', array( 'planned','in_progress','awaiting_client','awaiting_approval' ), true ) ) { $out[ $sid ]['open_items']++; $due = strtotime( (string) ( $item['due_at'] ?? '' ) . ' UTC' ); if ( $due && $due < $now ) { $out[ $sid ]['overdue_items']++; } }
		}
		$latest_report = array();
		foreach ( (array) ( $service['reports'] ?? array() ) as $r ) { $sid = absint( $r['site_id'] ?? 0 ); if ( ! isset( $latest_report[ $sid ] ) || (string) ( $r['period_end'] ?? '' ) > (string) ( $latest_report[ $sid ]['period_end'] ?? '' ) ) { $latest_report[ $sid ] = $r; } }
		foreach ( $out as $sid => &$row ) {
			$report = $latest_report[ $sid ] ?? array();
			$end = strtotime( (string) ( $report['period_end'] ?? '' ) . ' UTC' );
			$row['report_overdue'] = ! $end || $end < strtotime( '-45 days', $now );
			$capacity_percent = $row['capacity_units'] ? 100 * $row['used_units'] / $row['capacity_units'] : 0;
			$row['capacity_percent'] = round( $capacity_percent, 1 );
			$row['compliance_score'] = max( 0, 100 - min( 60, 15 * $row['overdue_items'] ) - ( $row['report_overdue'] ? 20 : 0 ) - ( $capacity_percent > 100 ? 20 : 0 ) );
		}
		unset( $row );
		return $out;
	}

	private function executive_metrics( array $sites, array $risks, array $approvals, array $service, array $forecast ) {
		$metrics = array( 'websites' => count( $sites ), 'websites_requiring_attention' => 0, 'portfolio_health_average' => 0, 'open_risks' => count( $risks ), 'critical_risks' => 0, 'pending_approvals' => count( $approvals ), 'overdue_approvals' => 0, 'open_work_items' => absint( $service['metrics']['open_items'] ?? 0 ), 'overdue_work_items' => absint( $service['metrics']['overdue_items'] ?? 0 ), 'reports_awaiting_approval' => absint( $service['metrics']['review_ready_reports'] ?? 0 ), 'capacity_utilisation_percent' => (float) ( $forecast['utilisation_percent'] ?? 0 ) );
		$health_sum = 0;
		foreach ( $sites as $site ) { $health_sum += absint( $site['health']['score'] ?? 0 ); if ( absint( $site['health']['score'] ?? 0 ) < 70 || absint( $site['risk_count'] ?? 0 ) ) { $metrics['websites_requiring_attention']++; } }
		$metrics['portfolio_health_average'] = $sites ? round( $health_sum / count( $sites ), 1 ) : 0;
		foreach ( $risks as $risk ) { if ( 'critical' === ( $risk['severity'] ?? '' ) ) { $metrics['critical_risks']++; } }
		$now = time(); foreach ( $approvals as $a ) { $due = strtotime( (string) $a['due_at'] . ' UTC' ); if ( $due && $due < $now ) { $metrics['overdue_approvals']++; } }
		return $metrics;
	}

	private function portfolio_analytics( array $sites, array $service ) {
		$analytics = array( 'opportunities' => array(), 'content' => array(), 'editorial' => array(), 'publishing' => array(), 'search_impact' => array(), 'health_levels' => array( 'healthy' => 0, 'watch' => 0, 'attention' => 0, 'critical' => 0 ), 'client_reporting' => array( 'review_ready' => 0, 'approved' => 0, 'delivered' => 0 ) );
		foreach ( $sites as $site ) {
			$analytics['health_levels'][ $site['health']['level'] ] = ( $analytics['health_levels'][ $site['health']['level'] ] ?? 0 ) + 1;
			foreach ( array( 'opportunities','content','editorial','publishing','search_impact' ) as $group ) { foreach ( (array) ( $site[ $group ] ?? array() ) as $status => $count ) { $analytics[ $group ][ sanitize_key( $status ) ] = ( $analytics[ $group ][ sanitize_key( $status ) ] ?? 0 ) + absint( $count ); } }
		}
		foreach ( (array) ( $service['reports'] ?? array() ) as $report ) { $status = sanitize_key( $report['status'] ?? '' ); if ( isset( $analytics['client_reporting'][ $status ] ) ) { $analytics['client_reporting'][ $status ]++; } }
		return $analytics;
	}

	private function risks( $status = 'open', $limit = 500 ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return array(); }
		$status = sanitize_key( $status ); $limit = max( 1, min( self::MAX_ITEMS, absint( $limit ) ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT r.*,s.site_name,s.client_name,s.site_url FROM {$this->risks_table()} r LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=r.site_id WHERE r.status=%s ORDER BY FIELD(r.severity,'critical','high','medium','low'),r.due_at ASC,r.last_seen_at DESC LIMIT %d", $status, $limit ), ARRAY_A );
		return array_map( array( $this, 'prepare_risk_row' ), $rows ?: array() );
	}

	private function notifications( $mode = 'active', $limit = 500 ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return array(); }
		$where = 'active' === $mode ? "n.status IN ('unread','acknowledged')" : $wpdb->prepare( 'n.status=%s', sanitize_key( $mode ) );
		$limit = max( 1, min( self::MAX_ITEMS, absint( $limit ) ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT n.*,s.site_name,s.client_name FROM {$this->notifications_table()} n LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=n.site_id WHERE {$where} ORDER BY FIELD(n.status,'unread','acknowledged'),FIELD(n.severity,'critical','high','medium','low'),n.updated_at DESC LIMIT %d", $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array_map( array( $this, 'prepare_notification_row' ), $rows ?: array() );
	}

	private function get_risk( $risk_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT r.*,s.site_name,s.client_name,s.site_url FROM {$this->risks_table()} r LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=r.site_id WHERE r.id=%d", absint( $risk_id ) ), ARRAY_A );
		return $row ? $this->prepare_risk_row( $row ) : null;
	}

	private function prepare_risk_row( array $row ) {
		$evidence = json_decode( $row['evidence_json'] ?? '', true );
		return array( 'id' => absint( $row['id'] ), 'site_id' => absint( $row['site_id'] ), 'site_name' => sanitize_text_field( $row['site_name'] ?? '' ), 'client_name' => sanitize_text_field( $row['client_name'] ?? '' ), 'site_url' => esc_url_raw( $row['site_url'] ?? '' ), 'category' => sanitize_key( $row['category'] ), 'severity' => sanitize_key( $row['severity'] ), 'title' => sanitize_text_field( $row['title'] ), 'evidence' => is_array( $evidence ) ? $evidence : array(), 'owner_id' => absint( $row['owner_id'] ), 'due_at' => sanitize_text_field( $row['due_at'] ), 'recommended_action' => sanitize_textarea_field( $row['recommended_action'] ), 'status' => sanitize_key( $row['status'] ), 'source' => sanitize_key( $row['source'] ), 'first_seen_at' => sanitize_text_field( $row['first_seen_at'] ), 'last_seen_at' => sanitize_text_field( $row['last_seen_at'] ), 'resolution_notes' => sanitize_textarea_field( $row['resolution_notes'] ) );
	}

	private function prepare_notification_row( array $row ) {
		return array( 'id' => absint( $row['id'] ), 'site_id' => absint( $row['site_id'] ), 'site_name' => sanitize_text_field( $row['site_name'] ?? '' ), 'client_name' => sanitize_text_field( $row['client_name'] ?? '' ), 'type' => sanitize_key( $row['notification_type'] ), 'severity' => sanitize_key( $row['severity'] ), 'title' => sanitize_text_field( $row['title'] ), 'summary' => sanitize_textarea_field( $row['summary'] ), 'status' => sanitize_key( $row['status'] ), 'source_ref' => sanitize_text_field( $row['source_ref'] ), 'acknowledged_by' => absint( $row['acknowledged_by'] ), 'acknowledged_at' => sanitize_text_field( $row['acknowledged_at'] ), 'created_at' => sanitize_text_field( $row['created_at'] ), 'updated_at' => sanitize_text_field( $row['updated_at'] ) );
	}

	private function normalise_filters( array $args ) {
		return array( 'site_id' => absint( $args['site_id'] ?? 0 ), 'severity' => sanitize_key( $args['severity'] ?? '' ), 'approval_type' => sanitize_key( $args['approval_type'] ?? '' ), 'owner_id' => absint( $args['owner_id'] ?? 0 ), 'search' => strtolower( sanitize_text_field( $args['search'] ?? '' ) ) );
	}

	private function filters_from_payload( array $payload ) {
		return array( 'limit' => absint( $payload['limit'] ?? 100 ), 'site_id' => absint( $payload['site_id'] ?? 0 ), 'severity' => sanitize_key( $payload['severity'] ?? '' ), 'approval_type' => sanitize_key( $payload['approval_type'] ?? '' ), 'owner_id' => absint( $payload['owner_id'] ?? 0 ), 'search' => sanitize_text_field( $payload['search'] ?? '' ) );
	}

	private function filter_items( array $items, array $filters, $kind ) {
		return array_values( array_filter( $items, function( $item ) use ( $filters, $kind ) {
			if ( $filters['site_id'] && absint( $item['site_id'] ?? $item['id'] ?? 0 ) !== $filters['site_id'] ) { return false; }
			if ( $filters['severity'] && isset( $item['severity'] ) && sanitize_key( $item['severity'] ) !== $filters['severity'] ) { return false; }
			if ( 'approval' === $kind && $filters['approval_type'] && sanitize_key( $item['type'] ?? '' ) !== $filters['approval_type'] ) { return false; }
			if ( $filters['owner_id'] && absint( $item['owner_id'] ?? 0 ) !== $filters['owner_id'] ) { return false; }
			if ( $filters['search'] ) { $haystack = strtolower( wp_json_encode( array( $item['site_name'] ?? '', $item['client_name'] ?? '', $item['title'] ?? '', $item['category'] ?? '', $item['type'] ?? '', $item['next_action'] ?? '' ) ) ); if ( false === strpos( $haystack, $filters['search'] ) ) { return false; } }
			return true;
		} ) );
	}

	private function normalise_datetime( $value ) {
		$value = trim( (string) $value ); if ( ! $value ) { return ''; }
		$timestamp = strtotime( $value ); return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : '';
	}

	private function sanitize_public_row( $row ) {
		$out = array(); foreach ( (array) $row as $key => $value ) { if ( is_scalar( $value ) || null === $value ) { $out[ sanitize_key( $key ) ] = is_numeric( $value ) ? $value + 0 : sanitize_text_field( (string) $value ); } }
		return $out;
	}


	private function record_history( $title, $summary, array $details, $user_id ) {
		$this->history->add(
			array(
				'category' => 'system',
				'status' => 'completed',
				'title' => sanitize_text_field( $title ),
				'summary' => sanitize_textarea_field( $summary ),
				'details' => $details,
			),
			'executive_command_centre',
			absint( $user_id )
		);
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}

	private function tables_ready() {
		return $this->table_exists( $this->risks_table() ) && $this->table_exists( $this->notifications_table() );
	}
}
