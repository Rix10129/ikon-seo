<?php

defined( 'ABSPATH' ) || exit;

/**
 * Agency service-level, workload-capacity and client-report governance.
 *
 * This class stores operational agreements and reporting evidence. It never
 * publishes content, sends client communication or changes a managed website.
 */
final class Ikon_SEO_Agency_Service_Levels {
	const CRON_HOOK = 'ikon_seo_agency_service_level_monitor';
	const MAX_REPORT_ITEMS = 100;

	private $agency_command;
	private $portfolio_governance;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Agency_Command_Centre $agency_command,
		Ikon_SEO_Portfolio_Governance $portfolio_governance,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->agency_command       = $agency_command;
		$this->portfolio_governance = $portfolio_governance;
		$this->history              = $history;
		$this->logger               = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_monitor' ) );
	}

	public function plans_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_service_plans'; }
	public function assignments_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_service_assignments'; }
	public function capacity_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_team_capacity'; }
	public function work_items_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_service_work_items'; }
	public function reports_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_client_reports'; }
	public function events_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_service_events'; }

	public function status() {
		global $wpdb;
		$ready = $this->tables_ready();
		return array(
			'enabled'          => true,
			'database_ready'   => $ready,
			'approved_plans'   => $ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->plans_table()} WHERE status='approved'" ) ) : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'active_sites'     => $ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->assignments_table()} WHERE status='active'" ) ) : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'open_work_items'  => $ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->work_items_table()} WHERE status IN ('planned','in_progress','awaiting_client','awaiting_approval')" ) ) : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'reports_awaiting_approval' => $ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->reports_table()} WHERE status='review_ready'" ) ) : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'client_delivery'  => 'manual_only',
			'live_site_writes' => false,
		);
	}

	/** Public, deterministic normaliser used by runtime and release tests. */
	public function normalize_plan( array $input ) {
		$cadences = array( 'weekly', 'fortnightly', 'monthly', 'quarterly' );
		$report_cadence = sanitize_key( $input['report_cadence'] ?? 'monthly' );
		$review_cadence = sanitize_key( $input['review_cadence'] ?? 'monthly' );
		if ( ! in_array( $report_cadence, $cadences, true ) ) { $report_cadence = 'monthly'; }
		if ( ! in_array( $review_cadence, $cadences, true ) ) { $review_cadence = 'monthly'; }

		$deliverables = $this->normalise_lines( $input['included_deliverables'] ?? array() );
		$excluded     = $this->normalise_lines( $input['excluded_services'] ?? array() );
		$evidence     = $this->normalise_lines( $input['report_evidence'] ?? array() );
		if ( ! $evidence ) {
			$evidence = array( 'Stored WordPress evidence', 'Agency Command Centre snapshot', 'Approved workflow decisions' );
		}

		$plan = array(
			'name'                    => substr( sanitize_text_field( $input['name'] ?? '' ), 0, 255 ),
			'plan_key'                => sanitize_key( $input['plan_key'] ?? ( $input['name'] ?? '' ) ),
			'currency'                => strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) ( $input['currency'] ?? 'USD' ) ), 0, 3 ) ),
			'monthly_fee'             => max( 0, round( (float) ( $input['monthly_fee'] ?? 0 ), 2 ) ),
			'monthly_capacity_units'  => max( 1, min( 1000, absint( $input['monthly_capacity_units'] ?? 20 ) ) ),
			'max_concurrent_items'    => max( 1, min( 100, absint( $input['max_concurrent_items'] ?? 5 ) ) ),
			'response_target_hours'   => max( 1, min( 720, absint( $input['response_target_hours'] ?? 48 ) ) ),
			'report_cadence'          => $report_cadence,
			'review_cadence'          => $review_cadence,
			'included_deliverables'   => $deliverables,
			'excluded_services'       => $excluded,
			'report_evidence'         => $evidence,
			'client_approval_required'=> true,
			'manual_delivery_only'    => true,
			'live_site_writes'        => false,
			'ranking_guarantee'       => false,
		);
		if ( ! $plan['plan_key'] ) { $plan['plan_key'] = 'service-plan'; }
		if ( ! $plan['currency'] || 3 !== strlen( $plan['currency'] ) ) { $plan['currency'] = 'USD'; }
		return $plan;
	}

	public function plan_fingerprint( array $plan ) {
		ksort( $plan );
		return hash( 'sha256', wp_json_encode( $plan ) );
	}

	public function create_plan( array $input, $user_id = 0 ) {
		global $wpdb;
		$plan = $this->normalize_plan( $input );
		if ( ! $plan['name'] ) {
			return new WP_Error( 'ikon_seo_service_plan_name', __( 'Enter a service-plan name.', 'ikon-seo' ) );
		}
		$version = 1 + absint( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(version) FROM {$this->plans_table()} WHERE plan_key=%s", $plan['plan_key'] ) ) );
		$now = current_time( 'mysql', true );
		$fingerprint = $this->plan_fingerprint( $plan );
		$inserted = $wpdb->insert( $this->plans_table(), array(
			'plan_key' => $plan['plan_key'], 'name' => $plan['name'], 'version' => $version,
			'status' => 'draft', 'plan_json' => wp_json_encode( $plan ), 'fingerprint' => $fingerprint,
			'notes' => sanitize_textarea_field( $input['notes'] ?? '' ), 'created_by' => absint( $user_id ),
			'approved_by' => 0, 'approved_at' => null, 'created_at' => $now, 'updated_at' => $now,
		) );
		if ( false === $inserted ) { return new WP_Error( 'ikon_seo_service_plan_store', __( 'The service plan could not be stored.', 'ikon-seo' ) ); }
		$id = absint( $wpdb->insert_id );
		$this->record_event( 'plan_created', 'completed', 'A draft agency service plan was created.', array( 'plan_id' => $id, 'version' => $version ), $user_id );
		return $this->get_plan( $id );
	}

	public function approve_plan( $plan_id, $notes, $user_id = 0 ) {
		global $wpdb;
		$plan = $this->get_plan( $plan_id );
		if ( ! $plan ) { return new WP_Error( 'ikon_seo_service_plan_missing', __( 'The service plan was not found.', 'ikon-seo' ) ); }
		if ( 'draft' !== $plan['status'] ) { return new WP_Error( 'ikon_seo_service_plan_status', __( 'Only a draft plan can be approved.', 'ikon-seo' ) ); }
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->plans_table(), array( 'status' => 'approved', 'approved_by' => absint( $user_id ), 'approved_at' => $now, 'notes' => sanitize_textarea_field( $notes ), 'updated_at' => $now ), array( 'id' => absint( $plan_id ) ) );
		$this->record_event( 'plan_approved', 'completed', 'An agency service plan was approved.', array( 'plan_id' => absint( $plan_id ) ), $user_id );
		return $this->get_plan( $plan_id );
	}

	public function retire_plan( $plan_id, $notes, $user_id = 0 ) {
		global $wpdb;
		$plan = $this->get_plan( $plan_id );
		if ( ! $plan || 'approved' !== $plan['status'] ) { return new WP_Error( 'ikon_seo_service_plan_retire', __( 'Only an approved service plan can be retired.', 'ikon-seo' ) ); }
		if ( ! trim( (string) $notes ) ) { return new WP_Error( 'ikon_seo_service_plan_notes', __( 'A retirement reason is required.', 'ikon-seo' ) ); }
		$wpdb->update( $this->plans_table(), array( 'status' => 'retired', 'notes' => sanitize_textarea_field( $notes ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $plan_id ) ) );
		$this->record_event( 'plan_retired', 'completed', 'An agency service plan was retired. Existing site assignments were not silently changed.', array( 'plan_id' => absint( $plan_id ) ), $user_id );
		return $this->get_plan( $plan_id );
	}

	public function assign_plan( $plan_id, $site_id, array $input, $user_id = 0 ) {
		global $wpdb;
		$plan = $this->get_plan( $plan_id );
		if ( ! $plan || 'approved' !== $plan['status'] ) { return new WP_Error( 'ikon_seo_service_plan_unapproved', __( 'Select an approved service plan.', 'ikon-seo' ) ); }
		$site = $wpdb->get_row( $wpdb->prepare( "SELECT id,site_name,site_url FROM {$this->agency_command->sites_table()} WHERE id=%d", absint( $site_id ) ), ARRAY_A );
		if ( ! $site ) { return new WP_Error( 'ikon_seo_service_site_missing', __( 'The managed website was not found.', 'ikon-seo' ) ); }
		$now = current_time( 'mysql', true );
		$start = $this->normalise_date( $input['start_date'] ?? gmdate( 'Y-m-d' ) );
		$renewal = $this->normalise_date( $input['renewal_date'] ?? '' );
		$capacity = max( 0, min( 1000, absint( $input['capacity_override_units'] ?? 0 ) ) );
		$existing = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->assignments_table()} WHERE site_id=%d AND status IN ('active','paused')", absint( $site_id ) ) ) );
		if ( $existing ) { return new WP_Error( 'ikon_seo_service_assignment_exists', __( 'This website already has an active or paused service assignment.', 'ikon-seo' ) ); }
		$wpdb->insert( $this->assignments_table(), array(
			'plan_id' => absint( $plan_id ), 'site_id' => absint( $site_id ), 'status' => 'active',
			'start_date' => $start, 'renewal_date' => $renewal ?: null, 'capacity_override_units' => $capacity,
			'client_reporting_enabled' => ! array_key_exists( 'client_reporting_enabled', $input ) || ! empty( $input['client_reporting_enabled'] ) ? 1 : 0,
			'notes' => sanitize_textarea_field( $input['notes'] ?? '' ), 'assigned_by' => absint( $user_id ),
			'created_at' => $now, 'updated_at' => $now,
		) );
		$id = absint( $wpdb->insert_id );
		$this->record_event( 'plan_assigned', 'completed', 'A service plan was assigned to a managed website.', array( 'assignment_id' => $id, 'site_id' => absint( $site_id ), 'plan_id' => absint( $plan_id ) ), $user_id );
		return $this->get_assignment( $id );
	}

	public function update_assignment_status( $assignment_id, $status, $notes, $user_id = 0 ) {
		global $wpdb;
		$status = sanitize_key( $status );
		if ( ! in_array( $status, array( 'active', 'paused', 'ended' ), true ) ) { return new WP_Error( 'ikon_seo_service_assignment_status', __( 'The requested assignment status is invalid.', 'ikon-seo' ) ); }
		$assignment = $this->get_assignment( $assignment_id );
		if ( ! $assignment ) { return new WP_Error( 'ikon_seo_service_assignment_missing', __( 'The service assignment was not found.', 'ikon-seo' ) ); }
		if ( in_array( $status, array( 'paused', 'ended' ), true ) && ! trim( (string) $notes ) ) { return new WP_Error( 'ikon_seo_service_assignment_notes', __( 'A reason is required when pausing or ending service.', 'ikon-seo' ) ); }
		$wpdb->update( $this->assignments_table(), array( 'status' => $status, 'notes' => sanitize_textarea_field( $notes ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $assignment_id ) ) );
		$this->record_event( 'assignment_' . $status, 'completed', 'A service assignment status was updated.', array( 'assignment_id' => absint( $assignment_id ) ), $user_id );
		return $this->get_assignment( $assignment_id );
	}

	public function set_capacity( array $input, $user_id = 0 ) {
		global $wpdb;
		$owner_id = absint( $input['user_id'] ?? 0 );
		if ( ! $owner_id || ! get_userdata( $owner_id ) ) { return new WP_Error( 'ikon_seo_capacity_user', __( 'Select a valid WordPress user.', 'ikon-seo' ) ); }
		$period_start = $this->normalise_date( $input['period_start'] ?? gmdate( 'Y-m-01' ) );
		$period_end = $this->normalise_date( $input['period_end'] ?? gmdate( 'Y-m-t' ) );
		if ( ! $period_start || ! $period_end || $period_end < $period_start ) { return new WP_Error( 'ikon_seo_capacity_period', __( 'Enter a valid capacity period.', 'ikon-seo' ) ); }
		$capacity = max( 1, min( 2000, absint( $input['capacity_units'] ?? 80 ) ) );
		$now = current_time( 'mysql', true );
		$sql = "INSERT INTO {$this->capacity_table()} (user_id,period_start,period_end,capacity_units,notes,created_by,created_at,updated_at) VALUES (%d,%s,%s,%d,%s,%d,%s,%s) ON DUPLICATE KEY UPDATE period_end=VALUES(period_end),capacity_units=VALUES(capacity_units),notes=VALUES(notes),updated_at=VALUES(updated_at)";
		$wpdb->query( $wpdb->prepare( $sql, $owner_id, $period_start, $period_end, $capacity, sanitize_textarea_field( $input['notes'] ?? '' ), absint( $user_id ), $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->record_event( 'capacity_set', 'completed', 'Team capacity was updated.', array( 'user_id' => $owner_id, 'period_start' => $period_start, 'capacity_units' => $capacity ), $user_id );
		return $this->capacity_for_user( $owner_id, $period_start, $period_end );
	}

	public function create_work_item( array $input, $user_id = 0 ) {
		global $wpdb;
		$assignment = $this->get_assignment( absint( $input['assignment_id'] ?? 0 ) );
		if ( ! $assignment || 'active' !== $assignment['status'] ) { return new WP_Error( 'ikon_seo_service_assignment_inactive', __( 'Select an active service assignment.', 'ikon-seo' ) ); }
		$title = substr( sanitize_text_field( $input['title'] ?? '' ), 0, 255 );
		if ( ! $title ) { return new WP_Error( 'ikon_seo_service_work_title', __( 'Enter a work-item title.', 'ikon-seo' ) ); }
		$units = max( 1, min( 1000, absint( $input['units'] ?? 1 ) ) );
		$open_count = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->work_items_table()} WHERE assignment_id=%d AND status IN ('planned','in_progress','awaiting_client','awaiting_approval')", $assignment['id'] ) ) );
		if ( $open_count >= absint( $assignment['plan']['max_concurrent_items'] ?? 5 ) ) { return new WP_Error( 'ikon_seo_service_concurrency', __( 'The service plan’s concurrent-work limit has been reached.', 'ikon-seo' ) ); }
		$period = $this->month_period( $input['due_date'] ?? gmdate( 'Y-m-d' ) );
		$used = $this->assignment_units( $assignment['id'], $period['start'], $period['end'] );
		$limit = absint( $assignment['capacity_units'] );
		if ( $used + $units > $limit ) { return new WP_Error( 'ikon_seo_service_capacity', __( 'This item would exceed the assigned monthly service capacity.', 'ikon-seo' ) ); }
		$priority = sanitize_key( $input['priority'] ?? 'normal' );
		if ( ! in_array( $priority, array( 'low', 'normal', 'high', 'urgent' ), true ) ) { $priority = 'normal'; }
		$now = current_time( 'mysql', true );
		$wpdb->insert( $this->work_items_table(), array(
			'assignment_id' => $assignment['id'], 'site_id' => $assignment['site_id'], 'owner_id' => absint( $input['owner_id'] ?? 0 ),
			'source_type' => sanitize_key( $input['source_type'] ?? 'manual' ), 'source_id' => absint( $input['source_id'] ?? 0 ),
			'category' => sanitize_key( $input['category'] ?? 'seo_operations' ), 'title' => $title,
			'description' => sanitize_textarea_field( $input['description'] ?? '' ), 'priority' => $priority,
			'units' => $units, 'status' => 'planned', 'due_at' => $this->normalise_datetime( $input['due_at'] ?? '' ),
			'first_action_at' => null, 'completed_at' => null, 'created_by' => absint( $user_id ), 'created_at' => $now, 'updated_at' => $now,
		) );
		$id = absint( $wpdb->insert_id );
		$this->record_event( 'work_item_created', 'completed', 'A capacity-controlled service work item was created.', array( 'work_item_id' => $id, 'assignment_id' => $assignment['id'], 'units' => $units ), $user_id );
		return $this->get_work_item( $id );
	}

	public function update_work_item( $item_id, array $input, $user_id = 0 ) {
		global $wpdb;
		$item = $this->get_work_item( $item_id );
		if ( ! $item ) { return new WP_Error( 'ikon_seo_service_work_missing', __( 'The work item was not found.', 'ikon-seo' ) ); }
		$status = sanitize_key( $input['status'] ?? $item['status'] );
		$allowed = array(
			'planned' => array( 'in_progress', 'cancelled' ),
			'in_progress' => array( 'awaiting_client', 'awaiting_approval', 'completed', 'cancelled' ),
			'awaiting_client' => array( 'in_progress', 'completed', 'cancelled' ),
			'awaiting_approval' => array( 'in_progress', 'completed', 'cancelled' ),
			'completed' => array(), 'cancelled' => array(),
		);
		if ( $status !== $item['status'] && ! in_array( $status, $allowed[ $item['status'] ] ?? array(), true ) ) { return new WP_Error( 'ikon_seo_service_work_transition', __( 'The requested work-item transition is not allowed.', 'ikon-seo' ) ); }
		$now = current_time( 'mysql', true );
		$data = array( 'status' => $status, 'updated_at' => $now );
		if ( 'in_progress' === $status && empty( $item['first_action_at'] ) ) { $data['first_action_at'] = $now; }
		if ( 'completed' === $status ) { $data['completed_at'] = $now; }
		if ( isset( $input['owner_id'] ) ) { $data['owner_id'] = absint( $input['owner_id'] ); }
		if ( isset( $input['due_at'] ) ) { $data['due_at'] = $this->normalise_datetime( $input['due_at'] ); }
		$wpdb->update( $this->work_items_table(), $data, array( 'id' => absint( $item_id ) ) );
		$this->record_event( 'work_item_updated', 'completed', 'A service work item was updated.', array( 'work_item_id' => absint( $item_id ), 'status' => $status ), $user_id );
		return $this->get_work_item( $item_id );
	}

	public function calculate_compliance( array $assignment, array $items, array $reports, $as_of = '' ) {
		$as_of_ts = $as_of ? strtotime( $as_of . ' UTC' ) : time();
		$target_seconds = max( 1, absint( $assignment['plan']['response_target_hours'] ?? 48 ) ) * HOUR_IN_SECONDS;
		$open = 0; $overdue = 0; $response_eligible = 0; $response_breaches = 0; $used = 0; $completed = 0;
		foreach ( $items as $item ) {
			$used += absint( $item['units'] ?? 0 );
			$status = sanitize_key( $item['status'] ?? '' );
			if ( 'completed' === $status ) { $completed++; }
			if ( in_array( $status, array( 'planned','in_progress','awaiting_client','awaiting_approval' ), true ) ) {
				$open++;
				$due_value = trim( (string) ( $item['due_at'] ?? '' ) );
				$due = '' !== $due_value ? strtotime( $due_value . ' UTC' ) : false;
				if ( $due && $due < $as_of_ts ) { $overdue++; }
			}
			$created_value = trim( (string) ( $item['created_at'] ?? '' ) );
			$first_value = trim( (string) ( $item['first_action_at'] ?? '' ) );
			$created = '' !== $created_value ? strtotime( $created_value . ' UTC' ) : false;
			$first = '' !== $first_value ? strtotime( $first_value . ' UTC' ) : false;
			if ( $created && $first ) { $response_eligible++; if ( $first - $created > $target_seconds ) { $response_breaches++; } }
		}
		$latest_report = $reports ? $reports[0] : array();
		$report_status = sanitize_key( $latest_report['status'] ?? 'not_generated' );
		$capacity = max( 1, absint( $assignment['capacity_units'] ?? 1 ) );
		$score = 100;
		$score -= min( 40, $overdue * 10 );
		$score -= $response_eligible ? min( 30, (int) round( 30 * $response_breaches / $response_eligible ) ) : 0;
		$score -= $used > $capacity ? 20 : 0;
		$score -= in_array( $report_status, array( 'not_generated','draft' ), true ) ? 10 : 0;
		$score = max( 0, min( 100, $score ) );
		return array(
			'score' => $score,
			'status' => $score >= 90 ? 'on_track' : ( $score >= 70 ? 'watch' : 'attention_required' ),
			'capacity_units' => $capacity, 'used_units' => $used, 'remaining_units' => max( 0, $capacity - $used ),
			'open_items' => $open, 'completed_items' => $completed, 'overdue_items' => $overdue,
			'response_eligible_items' => $response_eligible, 'response_breaches' => $response_breaches,
			'latest_report_status' => $report_status,
			'note' => 'This is an internal operational service-level score based on stored records. It is not a ranking guarantee or legal SLA certification.',
		);
	}

	public function generate_report( $assignment_id, array $input, $user_id = 0 ) {
		global $wpdb;
		$assignment = $this->get_assignment( $assignment_id );
		if ( ! $assignment ) { return new WP_Error( 'ikon_seo_service_assignment_missing', __( 'The service assignment was not found.', 'ikon-seo' ) ); }
		if ( empty( $assignment['client_reporting_enabled'] ) ) { return new WP_Error( 'ikon_seo_client_reporting_disabled', __( 'Client reporting is disabled for this assignment.', 'ikon-seo' ) ); }
		$period_start = $this->normalise_date( $input['period_start'] ?? gmdate( 'Y-m-01' ) );
		$period_end = $this->normalise_date( $input['period_end'] ?? gmdate( 'Y-m-t' ) );
		if ( ! $period_start || ! $period_end || $period_end < $period_start ) { return new WP_Error( 'ikon_seo_client_report_period', __( 'Enter a valid report period.', 'ikon-seo' ) ); }
		$client = $this->agency_command->client_report( $assignment['site_id'] );
		if ( is_wp_error( $client ) ) { return $client; }
		$items = $this->work_items_for_assignment( $assignment['id'], $period_start, $period_end, self::MAX_REPORT_ITEMS );
		$reports = $this->reports_for_assignment( $assignment['id'], 10 );
		$compliance = $this->calculate_compliance( $assignment, $items, $reports );
		$governance = $this->portfolio_governance->report( array( 'limit' => 20 ) );
		$payload = array(
			'schema' => 'ikon-seo-client-service-report-v1',
			'client' => $client['client'], 'site' => $client['site'], 'brand' => $client['brand'],
			'period' => array( 'start' => $period_start, 'end' => $period_end, 'generated_at' => current_time( 'mysql', true ) ),
			'service_plan' => array( 'name' => $assignment['plan']['name'], 'version' => $assignment['plan']['version'], 'included_deliverables' => $assignment['plan']['included_deliverables'], 'excluded_services' => $assignment['plan']['excluded_services'] ),
			'website_evidence' => array( 'summary' => $client['summary'], 'connections' => $client['connections'], 'top_findings' => $client['top_findings'], 'snapshot_at' => $client['period']['snapshot_at'] ),
			'work_delivered' => array_values( array_map( function( $item ) { return array( 'title' => $item['title'], 'category' => $item['category'], 'status' => $item['status'], 'units' => $item['units'], 'completed_at' => $item['completed_at'] ); }, $items ) ),
			'service_level' => $compliance,
			'governance' => array( 'available' => ! empty( $governance['ready'] ), 'safety' => array( 'manual_publish_only' => true, 'client_delivery_manual_only' => true ) ),
			'evidence_coverage' => array( 'agency_snapshot' => ! empty( $client['period']['snapshot_at'] ), 'work_items' => count( $items ), 'connected_sources' => count( array_filter( (array) $client['connections'] ) ), 'limitations' => array( 'External platforms may be delayed or incomplete.', 'Results are associated observations, not guaranteed causal outcomes.' ) ),
			'client_summary' => sanitize_textarea_field( $input['client_summary'] ?? '' ),
			'next_actions' => array_slice( $this->normalise_lines( $input['next_actions'] ?? array() ), 0, 10 ),
			'safety_note' => 'This draft report contains stored operational evidence only. It does not guarantee rankings, leads or revenue and has not been delivered to the client.',
		);
		$fingerprint = $this->evidence_fingerprint_for_assignment( $assignment, $period_start, $period_end, $client, $items );
		$payload['evidence_fingerprint'] = $fingerprint;
		$existing = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->reports_table()} WHERE assignment_id=%d AND period_start=%s AND period_end=%s AND status IN ('draft','review_ready','approved')", $assignment['id'], $period_start, $period_end ) ) );
		if ( $existing ) { return new WP_Error( 'ikon_seo_client_report_exists', __( 'An active report already exists for this assignment and period.', 'ikon-seo' ) ); }
		$now = current_time( 'mysql', true );
		$wpdb->insert( $this->reports_table(), array(
			'assignment_id' => $assignment['id'], 'site_id' => $assignment['site_id'], 'period_start' => $period_start, 'period_end' => $period_end,
			'status' => 'review_ready', 'report_json' => wp_json_encode( $payload ), 'evidence_fingerprint' => $fingerprint,
			'prepared_by' => absint( $user_id ), 'approved_by' => 0, 'approved_at' => null, 'delivered_by' => 0, 'delivered_at' => null,
			'delivery_method' => '', 'decision_notes' => '', 'created_at' => $now, 'updated_at' => $now,
		) );
		$id = absint( $wpdb->insert_id );
		$this->record_event( 'client_report_generated', 'completed', 'A client report was generated for review. It was not delivered.', array( 'report_id' => $id, 'assignment_id' => $assignment['id'], 'fingerprint' => $fingerprint ), $user_id );
		return $this->get_report( $id );
	}

	public function approve_report( $report_id, $notes, $user_id = 0 ) {
		global $wpdb;
		$report = $this->get_report( $report_id );
		if ( ! $report || 'review_ready' !== $report['status'] ) { return new WP_Error( 'ikon_seo_client_report_status', __( 'Only a review-ready report can be approved.', 'ikon-seo' ) ); }
		if ( absint( $report['prepared_by'] ) === absint( $user_id ) ) { return new WP_Error( 'ikon_seo_client_report_separation', __( 'The report approver must be different from its preparer.', 'ikon-seo' ) ); }
		$assignment = $this->get_assignment( $report['assignment_id'] );
		$current_fingerprint = $assignment ? $this->evidence_fingerprint_for_assignment( $assignment, $report['period_start'], $report['period_end'] ) : '';
		if ( ! $current_fingerprint || ! hash_equals( (string) $report['evidence_fingerprint'], $current_fingerprint ) ) {
			return new WP_Error( 'ikon_seo_client_report_stale', __( 'The managed-site or work evidence changed after this report was generated. Generate a new report before approval.', 'ikon-seo' ) );
		}
		if ( ! trim( (string) $notes ) ) { return new WP_Error( 'ikon_seo_client_report_notes', __( 'An approval note is required.', 'ikon-seo' ) ); }
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->reports_table(), array( 'status' => 'approved', 'approved_by' => absint( $user_id ), 'approved_at' => $now, 'decision_notes' => sanitize_textarea_field( $notes ), 'updated_at' => $now ), array( 'id' => absint( $report_id ) ) );
		$this->record_event( 'client_report_approved', 'completed', 'A client report was approved for manual delivery.', array( 'report_id' => absint( $report_id ) ), $user_id );
		return $this->get_report( $report_id );
	}

	public function mark_report_delivered( $report_id, $method, $notes, $user_id = 0 ) {
		global $wpdb;
		$report = $this->get_report( $report_id );
		if ( ! $report || 'approved' !== $report['status'] ) { return new WP_Error( 'ikon_seo_client_report_delivery_status', __( 'Only an approved report can be marked delivered.', 'ikon-seo' ) ); }
		$method = sanitize_key( $method );
		if ( ! in_array( $method, array( 'manual_email', 'manual_portal', 'manual_meeting', 'manual_download' ), true ) ) { return new WP_Error( 'ikon_seo_client_report_delivery_method', __( 'Select a supported manual delivery method.', 'ikon-seo' ) ); }
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->reports_table(), array( 'status' => 'delivered', 'delivered_by' => absint( $user_id ), 'delivered_at' => $now, 'delivery_method' => $method, 'decision_notes' => sanitize_textarea_field( $notes ), 'updated_at' => $now ), array( 'id' => absint( $report_id ) ) );
		$this->record_event( 'client_report_delivered', 'completed', 'An administrator recorded that an approved client report was delivered manually.', array( 'report_id' => absint( $report_id ), 'method' => $method, 'sent_by_plugin' => false ), $user_id );
		return $this->get_report( $report_id );
	}

	public function render_report_html( $report_id ) {
		$report = $this->get_report( $report_id );
		if ( ! $report ) { return new WP_Error( 'ikon_seo_client_report_missing', __( 'The client report was not found.', 'ikon-seo' ) ); }
		$data = $report['report'];
		ob_start();
		?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html( $data['site']['name'] ?? 'SEO Service Report' ); ?></title><style>body{font-family:Arial,sans-serif;background:#f3f7fa;color:#243b53;margin:0;padding:32px}.r{max-width:980px;margin:auto;background:white;padding:40px;border-radius:16px}.h{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid #d9e2ec;padding-bottom:20px}.g{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:24px 0}.m{border:1px solid #d9e2ec;border-radius:10px;padding:16px}.m strong{font-size:26px;display:block}.tag{display:inline-block;padding:4px 9px;background:#eaf4ff;border-radius:999px}.foot{margin-top:32px;border-top:1px solid #d9e2ec;padding-top:16px;color:#627d98;font-size:13px}@media(max-width:720px){.g{grid-template-columns:repeat(2,1fr)}.h{display:block}}</style></head><body><main class="r"><header class="h"><div><h1><?php echo esc_html( $data['brand']['name'] ?? 'Ikon SEO' ); ?></h1><p><?php echo esc_html( $data['client'] ?? '' ); ?></p></div><div><h2><?php echo esc_html( $data['site']['name'] ?? '' ); ?></h2><p><?php echo esc_html( ( $data['period']['start'] ?? '' ) . ' — ' . ( $data['period']['end'] ?? '' ) ); ?></p><span class="tag"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $report['status'] ) ) ); ?></span></div></header><section class="g"><div class="m"><strong><?php echo esc_html( $data['service_level']['score'] ?? 0 ); ?>%</strong>Service-level score</div><div class="m"><strong><?php echo esc_html( $data['service_level']['completed_items'] ?? 0 ); ?></strong>Completed items</div><div class="m"><strong><?php echo esc_html( $data['service_level']['remaining_units'] ?? 0 ); ?></strong>Capacity remaining</div><div class="m"><strong><?php echo esc_html( $data['website_evidence']['summary']['strategy_readiness'] ?? 0 ); ?>%</strong>Strategy readiness</div></section><h2>Executive summary</h2><p><?php echo nl2br( esc_html( $data['client_summary'] ?: 'No custom executive summary was added.' ) ); ?></p><h2>Work recorded</h2><ul><?php foreach ( (array) ( $data['work_delivered'] ?? array() ) as $item ) : ?><li><strong><?php echo esc_html( $item['title'] ); ?></strong> — <?php echo esc_html( ucfirst( str_replace( '_',' ', $item['status'] ) ) ); ?> (<?php echo esc_html( $item['units'] ); ?> units)</li><?php endforeach; ?></ul><h2>Next actions</h2><ul><?php foreach ( (array) ( $data['next_actions'] ?? array() ) as $action ) : ?><li><?php echo esc_html( $action ); ?></li><?php endforeach; ?></ul><footer class="foot"><p><?php echo esc_html( $data['safety_note'] ?? '' ); ?></p><p>Ikon SEO did not send this report or change the public website.</p></footer></main></body></html><?php
		return ob_get_clean();
	}

	public function report( array $args = array() ) {
		global $wpdb;
		$limit = max( 1, min( 200, absint( $args['limit'] ?? 100 ) ) );
		if ( ! $this->tables_ready() ) { return array( 'ready' => false, 'status' => $this->status() ); }
		$plans = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->plans_table()} ORDER BY updated_at DESC,id DESC LIMIT %d", $limit ), ARRAY_A );
		$assignments = $wpdb->get_results( $wpdb->prepare( "SELECT a.*,s.site_name,s.site_url,p.name plan_name,p.version plan_version,p.status plan_status,p.plan_json FROM {$this->assignments_table()} a LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=a.site_id LEFT JOIN {$this->plans_table()} p ON p.id=a.plan_id ORDER BY a.updated_at DESC,a.id DESC LIMIT %d", $limit ), ARRAY_A );
		$capacity = $this->capacity_board( gmdate( 'Y-m-01' ), gmdate( 'Y-m-t' ), $limit );
		$work = $wpdb->get_results( $wpdb->prepare( "SELECT w.*,s.site_name FROM {$this->work_items_table()} w LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=w.site_id ORDER BY FIELD(w.status,'in_progress','awaiting_client','awaiting_approval','planned','completed','cancelled'),w.due_at ASC,w.id DESC LIMIT %d", $limit ), ARRAY_A );
		$reports = $wpdb->get_results( $wpdb->prepare( "SELECT r.*,s.site_name FROM {$this->reports_table()} r LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=r.site_id ORDER BY r.updated_at DESC,r.id DESC LIMIT %d", $limit ), ARRAY_A );
		$prepared_assignments = array();
		foreach ( $assignments ?: array() as $row ) { $prepared_assignments[] = $this->prepare_assignment_row( $row ); }
		return array(
			'ready' => true, 'status' => $this->status(),
			'metrics' => $this->portfolio_metrics( $prepared_assignments, $work ?: array(), $reports ?: array(), $capacity ),
			'plans' => array_map( array( $this, 'prepare_plan_row' ), $plans ?: array() ),
			'assignments' => $prepared_assignments, 'capacity' => $capacity,
			'work_items' => array_map( array( $this, 'prepare_work_row' ), $work ?: array() ),
			'reports' => array_map( array( $this, 'prepare_report_row' ), $reports ?: array() ),
			'safety' => array( 'reports_require_approval' => true, 'delivery_is_manual' => true, 'client_messages_sent' => false, 'live_site_writes' => false ),
			'generated_at' => current_time( 'mysql', true ),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'create_plan': $result = $this->create_plan( (array) ( $payload['plan'] ?? $payload ), $user_id ); break;
			case 'approve_plan': $result = $this->approve_plan( absint( $payload['plan_id'] ?? 0 ), (string) ( $payload['notes'] ?? '' ), $user_id ); break;
			case 'retire_plan': $result = $this->retire_plan( absint( $payload['plan_id'] ?? 0 ), (string) ( $payload['notes'] ?? '' ), $user_id ); break;
			case 'assign_plan': $result = $this->assign_plan( absint( $payload['plan_id'] ?? 0 ), absint( $payload['site_id'] ?? 0 ), (array) ( $payload['assignment'] ?? $payload ), $user_id ); break;
			case 'update_assignment': $result = $this->update_assignment_status( absint( $payload['assignment_id'] ?? 0 ), (string) ( $payload['status'] ?? '' ), (string) ( $payload['notes'] ?? '' ), $user_id ); break;
			case 'set_capacity': $result = $this->set_capacity( (array) ( $payload['capacity'] ?? $payload ), $user_id ); break;
			case 'create_work_item': $result = $this->create_work_item( (array) ( $payload['work_item'] ?? $payload ), $user_id ); break;
			case 'update_work_item': $result = $this->update_work_item( absint( $payload['work_item_id'] ?? 0 ), (array) ( $payload['work_item'] ?? $payload ), $user_id ); break;
			case 'generate_report': $result = $this->generate_report( absint( $payload['assignment_id'] ?? 0 ), (array) ( $payload['report'] ?? $payload ), $user_id ); break;
			case 'approve_report': $result = $this->approve_report( absint( $payload['report_id'] ?? 0 ), (string) ( $payload['notes'] ?? '' ), $user_id ); break;
			case 'mark_report_delivered': $result = $this->mark_report_delivered( absint( $payload['report_id'] ?? 0 ), (string) ( $payload['method'] ?? '' ), (string) ( $payload['notes'] ?? '' ), $user_id ); break;
			case 'read': default: $result = array( 'read_only' => true ); break;
		}
		if ( is_wp_error( $result ) ) { return $result; }
		return array( 'command' => $command, 'result' => $result, 'service_levels' => $this->report( array( 'limit' => absint( $payload['limit'] ?? 100 ) ) ) );
	}

	public function scheduled_monitor() {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return; }
		$ids = $wpdb->get_col( "SELECT id FROM {$this->assignments_table()} WHERE status='active' ORDER BY updated_at ASC LIMIT 25" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $ids ?: array() as $id ) {
			$assignment = $this->get_assignment( $id );
			if ( ! $assignment ) { continue; }
			$period = $this->month_period( gmdate( 'Y-m-d' ) );
			$items = $this->work_items_for_assignment( $assignment['id'], $period['start'], $period['end'], 200 );
			$reports = $this->reports_for_assignment( $assignment['id'], 10 );
			$compliance = $this->calculate_compliance( $assignment, $items, $reports );
			if ( 'attention_required' === $compliance['status'] ) {
				$this->record_event( 'service_level_attention', 'open', 'A managed website requires service-level attention.', array( 'assignment_id' => $assignment['id'], 'score' => $compliance['score'], 'overdue_items' => $compliance['overdue_items'] ), 0 );
			}
		}
		update_option( 'ikon_seo_service_levels_last_monitor', current_time( 'mysql', true ), false );
	}

	private function evidence_fingerprint_for_assignment( array $assignment, $period_start, $period_end, $client = null, $items = null ) {
		if ( null === $client ) {
			$client = $this->agency_command->client_report( $assignment['site_id'] );
		}
		if ( is_wp_error( $client ) || ! is_array( $client ) ) { return ''; }
		if ( null === $items ) {
			$items = $this->work_items_for_assignment( $assignment['id'], $period_start, $period_end, self::MAX_REPORT_ITEMS );
		}
		$work = array();
		foreach ( (array) $items as $item ) {
			$work[] = array(
				'id' => absint( $item['id'] ?? 0 ),
				'title' => sanitize_text_field( $item['title'] ?? '' ),
				'status' => sanitize_key( $item['status'] ?? '' ),
				'units' => absint( $item['units'] ?? 0 ),
				'updated_at' => sanitize_text_field( $item['updated_at'] ?? '' ),
			);
		}
		$source = array(
			'assignment_id' => absint( $assignment['id'] ?? 0 ),
			'plan_id' => absint( $assignment['plan_id'] ?? 0 ),
			'plan_version' => absint( $assignment['plan']['version'] ?? 0 ),
			'plan_status' => sanitize_key( $assignment['plan']['status'] ?? '' ),
			'period_start' => sanitize_text_field( $period_start ),
			'period_end' => sanitize_text_field( $period_end ),
			'snapshot_at' => sanitize_text_field( $client['period']['snapshot_at'] ?? '' ),
			'summary' => (array) ( $client['summary'] ?? array() ),
			'connections' => (array) ( $client['connections'] ?? array() ),
			'work_items' => $work,
		);
		return hash( 'sha256', wp_json_encode( $source ) );
	}

	private function portfolio_metrics( array $assignments, array $work, array $reports, array $capacity ) {
		$m = array( 'active_assignments' => 0, 'paused_assignments' => 0, 'open_items' => 0, 'overdue_items' => 0, 'review_ready_reports' => 0, 'approved_reports' => 0, 'delivered_reports' => 0, 'team_capacity_units' => 0, 'team_allocated_units' => 0 );
		foreach ( $assignments as $a ) { if ( 'active' === $a['status'] ) { $m['active_assignments']++; } elseif ( 'paused' === $a['status'] ) { $m['paused_assignments']++; } }
		$now = time();
		foreach ( $work as $w ) { if ( in_array( $w['status'], array( 'planned','in_progress','awaiting_client','awaiting_approval' ), true ) ) { $m['open_items']++; $due = strtotime( (string) $w['due_at'] . ' UTC' ); if ( $due && $due < $now ) { $m['overdue_items']++; } } }
		foreach ( $reports as $r ) { $key = sanitize_key( $r['status'] ); if ( isset( $m[ $key . '_reports' ] ) ) { $m[ $key . '_reports' ]++; } }
		foreach ( $capacity as $c ) { $m['team_capacity_units'] += absint( $c['capacity_units'] ); $m['team_allocated_units'] += absint( $c['allocated_units'] ); }
		return $m;
	}

	private function capacity_board( $start, $end, $limit ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->capacity_table()} WHERE period_end >= %s AND period_start <= %s ORDER BY period_start DESC,id DESC LIMIT %d", $start, $end, $limit ), ARRAY_A );
		$out = array();
		foreach ( $rows ?: array() as $row ) {
			$allocated = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(units),0) FROM {$this->work_items_table()} WHERE owner_id=%d AND status IN ('planned','in_progress','awaiting_client','awaiting_approval') AND DATE(created_at) BETWEEN %s AND %s", absint( $row['user_id'] ), $row['period_start'], $row['period_end'] ) ) );
			$user = get_userdata( absint( $row['user_id'] ) );
			$out[] = array( 'id' => absint( $row['id'] ), 'user_id' => absint( $row['user_id'] ), 'display_name' => $user ? sanitize_text_field( $user->display_name ) : 'User #' . absint( $row['user_id'] ), 'period_start' => $row['period_start'], 'period_end' => $row['period_end'], 'capacity_units' => absint( $row['capacity_units'] ), 'allocated_units' => $allocated, 'remaining_units' => max( 0, absint( $row['capacity_units'] ) - $allocated ), 'utilisation_percent' => absint( $row['capacity_units'] ) ? round( 100 * $allocated / absint( $row['capacity_units'] ), 1 ) : 0, 'notes' => sanitize_textarea_field( $row['notes'] ) );
		}
		return $out;
	}

	private function capacity_for_user( $user_id, $start, $end ) {
		foreach ( $this->capacity_board( $start, $end, 200 ) as $item ) { if ( absint( $item['user_id'] ) === absint( $user_id ) ) { return $item; } }
		return array();
	}

	private function assignment_units( $assignment_id, $start, $end ) {
		global $wpdb;
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(units),0) FROM {$this->work_items_table()} WHERE assignment_id=%d AND status<>'cancelled' AND DATE(created_at) BETWEEN %s AND %s", absint( $assignment_id ), $start, $end ) ) );
	}

	private function get_plan( $id ) { global $wpdb; $r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->plans_table()} WHERE id=%d", absint( $id ) ), ARRAY_A ); return $r ? $this->prepare_plan_row( $r ) : null; }
	private function get_assignment( $id ) { global $wpdb; $r = $wpdb->get_row( $wpdb->prepare( "SELECT a.*,s.site_name,s.site_url,p.name plan_name,p.version plan_version,p.status plan_status,p.plan_json,p.fingerprint plan_fingerprint FROM {$this->assignments_table()} a LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=a.site_id LEFT JOIN {$this->plans_table()} p ON p.id=a.plan_id WHERE a.id=%d", absint( $id ) ), ARRAY_A ); return $r ? $this->prepare_assignment_row( $r ) : null; }
	private function get_work_item( $id ) { global $wpdb; $r = $wpdb->get_row( $wpdb->prepare( "SELECT w.*,s.site_name FROM {$this->work_items_table()} w LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=w.site_id WHERE w.id=%d", absint( $id ) ), ARRAY_A ); return $r ? $this->prepare_work_row( $r ) : null; }
	private function get_report( $id ) { global $wpdb; $r = $wpdb->get_row( $wpdb->prepare( "SELECT r.*,s.site_name FROM {$this->reports_table()} r LEFT JOIN {$this->agency_command->sites_table()} s ON s.id=r.site_id WHERE r.id=%d", absint( $id ) ), ARRAY_A ); return $r ? $this->prepare_report_row( $r ) : null; }

	private function work_items_for_assignment( $assignment_id, $start, $end, $limit ) { global $wpdb; $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->work_items_table()} WHERE assignment_id=%d AND DATE(created_at) BETWEEN %s AND %s ORDER BY created_at ASC,id ASC LIMIT %d", absint( $assignment_id ), $start, $end, $limit ), ARRAY_A ); return array_map( array( $this, 'prepare_work_row' ), $rows ?: array() ); }
	private function reports_for_assignment( $assignment_id, $limit ) { global $wpdb; $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->reports_table()} WHERE assignment_id=%d ORDER BY period_end DESC,id DESC LIMIT %d", absint( $assignment_id ), $limit ), ARRAY_A ); return array_map( array( $this, 'prepare_report_row' ), $rows ?: array() ); }

	private function prepare_plan_row( array $row ) { $p = json_decode( (string) ( $row['plan_json'] ?? '' ), true ); return array( 'id' => absint( $row['id'] ), 'plan_key' => sanitize_key( $row['plan_key'] ), 'name' => sanitize_text_field( $row['name'] ), 'version' => absint( $row['version'] ), 'status' => sanitize_key( $row['status'] ), 'plan' => is_array( $p ) ? $p : array(), 'fingerprint' => sanitize_text_field( $row['fingerprint'] ), 'notes' => sanitize_textarea_field( $row['notes'] ), 'created_by' => absint( $row['created_by'] ), 'approved_by' => absint( $row['approved_by'] ), 'approved_at' => sanitize_text_field( $row['approved_at'] ), 'created_at' => sanitize_text_field( $row['created_at'] ), 'updated_at' => sanitize_text_field( $row['updated_at'] ) ); }
	private function prepare_assignment_row( array $row ) { $p = json_decode( (string) ( $row['plan_json'] ?? '' ), true ); $capacity = absint( $row['capacity_override_units'] ) ?: absint( $p['monthly_capacity_units'] ?? 0 ); return array( 'id' => absint( $row['id'] ), 'plan_id' => absint( $row['plan_id'] ), 'site_id' => absint( $row['site_id'] ), 'site_name' => sanitize_text_field( $row['site_name'] ?? '' ), 'site_url' => esc_url_raw( $row['site_url'] ?? '' ), 'status' => sanitize_key( $row['status'] ), 'start_date' => sanitize_text_field( $row['start_date'] ), 'renewal_date' => sanitize_text_field( $row['renewal_date'] ), 'capacity_override_units' => absint( $row['capacity_override_units'] ), 'capacity_units' => $capacity, 'client_reporting_enabled' => ! empty( $row['client_reporting_enabled'] ), 'notes' => sanitize_textarea_field( $row['notes'] ), 'plan' => array_merge( is_array( $p ) ? $p : array(), array( 'id' => absint( $row['plan_id'] ), 'name' => sanitize_text_field( $row['plan_name'] ?? '' ), 'version' => absint( $row['plan_version'] ?? 0 ), 'status' => sanitize_key( $row['plan_status'] ?? '' ) ) ), 'created_at' => sanitize_text_field( $row['created_at'] ), 'updated_at' => sanitize_text_field( $row['updated_at'] ) ); }
	private function prepare_work_row( array $row ) { return array( 'id' => absint( $row['id'] ), 'assignment_id' => absint( $row['assignment_id'] ), 'site_id' => absint( $row['site_id'] ), 'site_name' => sanitize_text_field( $row['site_name'] ?? '' ), 'owner_id' => absint( $row['owner_id'] ), 'source_type' => sanitize_key( $row['source_type'] ), 'source_id' => absint( $row['source_id'] ), 'category' => sanitize_key( $row['category'] ), 'title' => sanitize_text_field( $row['title'] ), 'description' => sanitize_textarea_field( $row['description'] ), 'priority' => sanitize_key( $row['priority'] ), 'units' => absint( $row['units'] ), 'status' => sanitize_key( $row['status'] ), 'due_at' => sanitize_text_field( $row['due_at'] ), 'first_action_at' => sanitize_text_field( $row['first_action_at'] ), 'completed_at' => sanitize_text_field( $row['completed_at'] ), 'created_at' => sanitize_text_field( $row['created_at'] ), 'updated_at' => sanitize_text_field( $row['updated_at'] ) ); }
	private function prepare_report_row( array $row ) { $r = json_decode( (string) ( $row['report_json'] ?? '' ), true ); return array( 'id' => absint( $row['id'] ), 'assignment_id' => absint( $row['assignment_id'] ), 'site_id' => absint( $row['site_id'] ), 'site_name' => sanitize_text_field( $row['site_name'] ?? '' ), 'period_start' => sanitize_text_field( $row['period_start'] ), 'period_end' => sanitize_text_field( $row['period_end'] ), 'status' => sanitize_key( $row['status'] ), 'report' => is_array( $r ) ? $r : array(), 'evidence_fingerprint' => sanitize_text_field( $row['evidence_fingerprint'] ), 'prepared_by' => absint( $row['prepared_by'] ), 'approved_by' => absint( $row['approved_by'] ), 'approved_at' => sanitize_text_field( $row['approved_at'] ), 'delivered_by' => absint( $row['delivered_by'] ), 'delivered_at' => sanitize_text_field( $row['delivered_at'] ), 'delivery_method' => sanitize_key( $row['delivery_method'] ), 'decision_notes' => sanitize_textarea_field( $row['decision_notes'] ), 'created_at' => sanitize_text_field( $row['created_at'] ), 'updated_at' => sanitize_text_field( $row['updated_at'] ) ); }

	private function record_event( $action, $status, $message, array $details, $user_id ) {
		global $wpdb;
		if ( $this->table_exists( $this->events_table() ) ) {
			$wpdb->insert( $this->events_table(), array( 'action' => sanitize_key( $action ), 'status' => sanitize_key( $status ), 'message' => sanitize_textarea_field( $message ), 'details_json' => wp_json_encode( $details ), 'user_id' => absint( $user_id ), 'created_at' => current_time( 'mysql', true ) ) );
		}
		$this->logger->log( 'service_levels_' . sanitize_key( $action ), sanitize_key( $status ), $message, null, null, $details );
		$this->history->add( array( 'category' => in_array( $action, array( 'plan_approved','client_report_approved','client_report_delivered' ), true ) ? 'approval' : 'workflow', 'status' => 'completed', 'title' => ucwords( str_replace( '_', ' ', $action ) ), 'summary' => $message, 'details' => $details ), 'agency_service_levels', $user_id );
	}

	private function normalise_lines( $value ) { if ( is_string( $value ) ) { $value = preg_split( '/[\r\n,]+/', $value ); } $out = array(); foreach ( (array) $value as $line ) { $line = trim( sanitize_text_field( $line ) ); if ( $line && ! in_array( $line, $out, true ) ) { $out[] = substr( $line, 0, 255 ); } } return array_slice( $out, 0, 50 ); }
	private function normalise_date( $value ) { $value = trim( (string) $value ); if ( ! $value ) { return ''; } $ts = strtotime( $value . ' UTC' ); return $ts ? gmdate( 'Y-m-d', $ts ) : ''; }
	private function normalise_datetime( $value ) { $value = trim( (string) $value ); if ( ! $value ) { return null; } $ts = strtotime( $value . ' UTC' ); return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null; }
	private function month_period( $date ) { $ts = strtotime( (string) $date . ' UTC' ); if ( ! $ts ) { $ts = time(); } return array( 'start' => gmdate( 'Y-m-01', $ts ), 'end' => gmdate( 'Y-m-t', $ts ) ); }
	private function tables_ready() { return $this->table_exists( $this->plans_table() ) && $this->table_exists( $this->assignments_table() ) && $this->table_exists( $this->capacity_table() ) && $this->table_exists( $this->work_items_table() ) && $this->table_exists( $this->reports_table() ); }
	private function table_exists( $table ) { global $wpdb; return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table; }
}
