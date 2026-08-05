<?php

defined( 'ABSPATH' ) || exit;

/**
 * Consolidates Ikon SEO evidence into one approval-first operating plan and
 * measures whether completed recommendations produced useful outcomes.
 */
final class Ikon_SEO_Closed_Loop {
	const CRON_HOOK           = 'ikon_seo_closed_loop_measurements';
	const MAX_PLAN_ITEMS      = 500;
	const MAX_SYNC_ITEMS      = 100;
	const MAX_CHECKPOINTS     = 20;
	const DEFAULT_WINDOWS     = '14,28,60,90';

	private $profile;
	private $strategy;
	private $diagnostics;
	private $search_intelligence;
	private $analytics;
	private $technical;
	private $indexation;
	private $competitor_content;
	private $authority;
	private $opportunity_engine;
	private $publisher;
	private $local_growth;
	private $visibility_brand;
	private $automation;
	private $history;
	private $crypto;
	private $logger;

	public function __construct(
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Strategy $strategy,
		Ikon_SEO_Diagnostics $diagnostics,
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Analytics $analytics,
		Ikon_SEO_Technical_Intelligence $technical,
		Ikon_SEO_Indexation_Intelligence $indexation,
		Ikon_SEO_Competitor_Content_Intelligence $competitor_content,
		Ikon_SEO_Authority_Intelligence $authority,
		Ikon_SEO_Opportunity_Engine $opportunity_engine,
		Ikon_SEO_Publisher_Intelligence $publisher,
		Ikon_SEO_Local_Growth $local_growth,
		Ikon_SEO_Visibility_Brand_Intelligence $visibility_brand,
		Ikon_SEO_Automation $automation,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Crypto $crypto,
		Ikon_SEO_Logger $logger
	) {
		$this->profile             = $profile;
		$this->strategy            = $strategy;
		$this->diagnostics         = $diagnostics;
		$this->search_intelligence = $search_intelligence;
		$this->analytics           = $analytics;
		$this->technical           = $technical;
		$this->indexation          = $indexation;
		$this->competitor_content  = $competitor_content;
		$this->authority           = $authority;
		$this->opportunity_engine  = $opportunity_engine;
		$this->publisher           = $publisher;
		$this->local_growth        = $local_growth;
		$this->visibility_brand    = $visibility_brand;
		$this->automation          = $automation;
		$this->history             = $history;
		$this->crypto              = $crypto;
		$this->logger              = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_measurements' ) );
	}

	public function recommendations_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_recommendations';
	}

	public function snapshots_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_outcome_snapshots';
	}

	public function outcomes_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_outcomes';
	}

	public function checkpoints_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_recovery_checkpoints';
	}

	public function scheduled_measurements() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['closed_loop_enabled'] ) || ! empty( $settings['closed_loop_safe_mode'] ) ) {
			return;
		}
		$this->run_due_measurements( absint( $settings['closed_loop_measurement_batch'] ?? 5 ) );
	}

	public function status() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		$ready    = $this->tables_ready();
		$counts   = array();
		foreach ( array( 'proposed', 'approved', 'in_progress', 'monitoring', 'succeeded', 'neutral', 'declined', 'inconclusive', 'dismissed' ) as $status ) {
			$counts[ $status ] = $ready ? absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->recommendations_table()} WHERE profile_id=%s AND status=%s", $this->profile_id(), $status ) ) ) : 0;
		}
		$due = $ready ? absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->outcomes_table()} o INNER JOIN {$this->recommendations_table()} r ON r.id=o.recommendation_id WHERE r.profile_id=%s AND o.measured_at IS NULL AND o.due_at <= %s", $this->profile_id(), current_time( 'mysql', true ) ) ) ) : 0;
		return array(
			'enabled'            => ! empty( $settings['closed_loop_enabled'] ),
			'safe_mode'          => ! empty( $settings['closed_loop_safe_mode'] ),
			'database_ready'     => $ready,
			'counts'             => $counts,
			'due_measurements'   => $due,
			'measurement_batch'  => max( 1, min( 50, absint( $settings['closed_loop_measurement_batch'] ?? 5 ) ) ),
			'windows'            => $this->measurement_windows(),
			'last_plan_refresh'  => sanitize_text_field( get_option( 'ikon_seo_closed_loop_last_plan', '' ) ),
			'last_measurement'   => sanitize_text_field( get_option( 'ikon_seo_closed_loop_last_measurement', '' ) ),
			'component_version'  => sanitize_text_field( $settings['component_version'] ?? '' ),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_closed_loop_tables', __( 'Closed-Loop tables are unavailable. Reactivate Ikon SEO to repair the database.', 'ikon-seo' ) );
		}
		$command = sanitize_key( $payload['command'] ?? 'read' );
		$result  = array( 'read_only' => true );
		switch ( $command ) {
			case 'refresh_plan':
				$result = $this->refresh_plan( ! empty( $payload['refresh_sources'] ), absint( $payload['limit'] ?? 100 ), true, $user_id );
				break;
			case 'approve':
				$result = $this->approve( absint( $payload['recommendation_id'] ?? 0 ), $user_id );
				break;
			case 'start':
				$result = $this->start( absint( $payload['recommendation_id'] ?? 0 ), $user_id );
				break;
			case 'complete':
				$result = $this->complete( absint( $payload['recommendation_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
				break;
			case 'dismiss':
				$result = $this->dismiss( absint( $payload['recommendation_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
				break;
			case 'measure':
				$result = $this->measure( absint( $payload['recommendation_id'] ?? 0 ), absint( $payload['window_days'] ?? 0 ), true, $user_id );
				break;
			case 'run_due_measurements':
				$result = $this->run_due_measurements( absint( $payload['limit'] ?? 5 ), $user_id );
				break;
			case 'create_checkpoint':
				$result = $this->create_checkpoint( sanitize_text_field( $payload['reason'] ?? 'Manual checkpoint' ), $user_id );
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
			'command'     => $command,
			'result'      => $result,
			'closed_loop' => $this->report( absint( $payload['limit'] ?? 100 ) ),
		);
	}

	public function refresh_plan( $refresh_sources = false, $limit = 100, $store = true, $user_id = 0 ) {
		$limit  = max( 10, min( self::MAX_PLAN_ITEMS, absint( $limit ) ) );
		$items  = array();
		$errors = array();

		$diagnostics = $this->diagnostics->site_report( (bool) $refresh_sources, true );
		if ( is_wp_error( $diagnostics ) ) {
			$errors[] = $diagnostics->get_error_message();
		} else {
			$items = array_merge( $items, $this->from_diagnostics( (array) $diagnostics ) );
		}

		$search = $this->search_intelligence->report( false, 100 );
		if ( is_wp_error( $search ) ) {
			$errors[] = $search->get_error_message();
		} else {
			$items = array_merge( $items, $this->from_search( (array) $search ) );
		}

		$technical = $this->technical->report( false, 100 );
		if ( is_wp_error( $technical ) ) {
			$errors[] = $technical->get_error_message();
		} else {
			$items = array_merge( $items, $this->from_module_recommendations( (array) $technical, 'technical', 78 ) );
		}

		$indexation = $this->indexation->report( 100 );
		if ( is_wp_error( $indexation ) ) {
			$errors[] = $indexation->get_error_message();
		} else {
			$items = array_merge( $items, $this->from_module_recommendations( (array) $indexation, 'indexation', 82 ) );
		}

		$publisher = $this->publisher->report( 100, false );
		if ( is_wp_error( $publisher ) ) {
			$errors[] = $publisher->get_error_message();
		} else {
			$items = array_merge( $items, $this->from_publisher( (array) $publisher ) );
		}

		$local = $this->local_growth->report( false, 30 );
		if ( is_wp_error( $local ) ) {
			$errors[] = $local->get_error_message();
		} else {
			$items = array_merge( $items, $this->from_module_recommendations( (array) $local, 'local_growth', 74 ) );
		}

		$visibility = $this->visibility_brand->report( 100 );
		if ( is_wp_error( $visibility ) ) {
			$errors[] = $visibility->get_error_message();
		} else {
			$items = array_merge( $items, $this->from_module_recommendations( (array) $visibility, 'visibility_brand', 62 ) );
		}

		$authority = $this->authority->report( 100, false );
		if ( is_wp_error( $authority ) ) {
			$errors[] = $authority->get_error_message();
		} else {
			$items = array_merge( $items, $this->from_module_recommendations( (array) $authority, 'authority', 64 ) );
		}

		$opportunities = $this->opportunity_engine->report( array( 'limit' => 100, 'status' => 'planned' ) );
		if ( is_wp_error( $opportunities ) ) {
			$errors[] = $opportunities->get_error_message();
		} else {
			$items = array_merge( $items, $this->from_planned_opportunities( (array) $opportunities, $items ) );
		}

		$items = $this->deduplicate_plan( $items );
		usort( $items, function( $a, $b ) { return (int) $b['priority'] <=> (int) $a['priority']; } );
		$items = array_slice( $items, 0, $limit );
		$stored = 0;
		if ( $store ) {
			foreach ( $items as $item ) {
				$saved = $this->upsert_recommendation( $item, $user_id );
				if ( ! is_wp_error( $saved ) ) {
					$stored++;
				}
			}
			update_option( 'ikon_seo_closed_loop_last_plan', current_time( 'mysql', true ), false );
			$this->history->add(
				array(
					'category' => 'audit',
					'status'   => 'completed',
					'title'    => 'Closed-Loop operating plan refreshed',
					'summary'  => sprintf( '%d prioritised recommendations were consolidated from available evidence.', $stored ),
					'details'  => array( 'stored' => $stored, 'errors' => $errors ),
				),
				'closed_loop',
				absint( $user_id )
			);
		}
		return array( 'generated' => count( $items ), 'stored' => $stored, 'errors' => $errors, 'items' => $items );
	}

	public function report( $limit = 100 ) {
		$limit = max( 10, min( self::MAX_PLAN_ITEMS, absint( $limit ) ) );
		$recommendations = $this->recommendations( '', $limit );
		$counts = array();
		$module_counts = array();
		$priority_bands = array( 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0 );
		foreach ( $recommendations as $item ) {
			$status = sanitize_key( $item['status'] ?? 'proposed' );
			$module = sanitize_key( $item['source_module'] ?? 'other' );
			$counts[ $status ] = ( $counts[ $status ] ?? 0 ) + 1;
			$module_counts[ $module ] = ( $module_counts[ $module ] ?? 0 ) + 1;
			$priority = absint( $item['priority'] ?? 0 );
			$band = $priority >= 85 ? 'critical' : ( $priority >= 70 ? 'high' : ( $priority >= 45 ? 'medium' : 'low' ) );
			$priority_bands[ $band ]++;
		}
		$outcomes = $this->recent_outcomes( 100 );
		$outcome_counts = array( 'succeeded' => 0, 'neutral' => 0, 'declined' => 0, 'inconclusive' => 0, 'pending' => 0 );
		foreach ( $outcomes as $outcome ) {
			$key = sanitize_key( $outcome['outcome'] ?? 'pending' );
			$outcome_counts[ $key ] = ( $outcome_counts[ $key ] ?? 0 ) + 1;
		}
		$result = array(
			'status'          => $this->status(),
			'summary'         => array(
				'recommendations' => count( $recommendations ),
				'by_status'       => $counts,
				'by_module'       => $module_counts,
				'priority_bands'  => $priority_bands,
				'outcomes'        => $outcome_counts,
			),
			'recommendations' => $recommendations,
			'due_measurements'=> $this->due_measurements( 50 ),
			'recent_outcomes' => $outcomes,
			'system_health'   => $this->system_health(),
			'checkpoints'     => $this->checkpoints( 10 ),
			'methodology'     => 'Recommendations are consolidated from available evidence, deduplicated by root cause, approval-controlled, measured against stored baselines and classified as succeeded, neutral, declined or inconclusive. Results remain observational and do not prove causation.',
			'generated_at'    => current_time( 'mysql', true ),
		);
		return $result;
	}

	public function recommendations( $status = '', $limit = 100 ) {
		global $wpdb;
		$limit = max( 1, min( self::MAX_PLAN_ITEMS, absint( $limit ) ) );
		if ( $status ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->recommendations_table()} WHERE profile_id=%s AND status=%s ORDER BY priority DESC, updated_at DESC LIMIT %d", $this->profile_id(), sanitize_key( $status ), $limit ), ARRAY_A );
		} else {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->recommendations_table()} WHERE profile_id=%s ORDER BY FIELD(status,'monitoring','in_progress','approved','proposed','inconclusive','declined','neutral','succeeded','dismissed'), priority DESC, updated_at DESC LIMIT %d", $this->profile_id(), $limit ), ARRAY_A );
		}
		return array_map( array( $this, 'prepare_recommendation' ), $rows ?: array() );
	}

	public function approve( $recommendation_id, $user_id = 0 ) {
		$recommendation = $this->get_recommendation( $recommendation_id );
		if ( is_wp_error( $recommendation ) ) {
			return $recommendation;
		}
		if ( ! in_array( $recommendation['status'], array( 'proposed', 'inconclusive', 'declined', 'neutral' ), true ) ) {
			return new WP_Error( 'ikon_seo_closed_loop_status', __( 'Only proposed or reviewed recommendations can be approved.', 'ikon-seo' ) );
		}
		$baseline = $this->capture_snapshot( $recommendation_id, 'baseline', $user_id );
		if ( is_wp_error( $baseline ) ) {
			return $baseline;
		}
		$this->update_recommendation_status( $recommendation_id, 'approved', $user_id );
		return array( 'recommendation_id' => $recommendation_id, 'status' => 'approved', 'baseline' => $baseline );
	}

	public function start( $recommendation_id, $user_id = 0 ) {
		$recommendation = $this->get_recommendation( $recommendation_id );
		if ( is_wp_error( $recommendation ) ) {
			return $recommendation;
		}
		if ( ! in_array( $recommendation['status'], array( 'approved', 'proposed' ), true ) ) {
			return new WP_Error( 'ikon_seo_closed_loop_start', __( 'The recommendation is not ready to start.', 'ikon-seo' ) );
		}
		if ( empty( $recommendation['baseline_snapshot_id'] ) ) {
			$baseline = $this->capture_snapshot( $recommendation_id, 'baseline', $user_id );
			if ( is_wp_error( $baseline ) ) {
				return $baseline;
			}
		}
		$this->update_recommendation_status( $recommendation_id, 'in_progress', $user_id );
		return array( 'recommendation_id' => $recommendation_id, 'status' => 'in_progress' );
	}

	public function complete( $recommendation_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		$recommendation = $this->get_recommendation( $recommendation_id );
		if ( is_wp_error( $recommendation ) ) {
			return $recommendation;
		}
		if ( ! in_array( $recommendation['status'], array( 'approved', 'in_progress' ), true ) ) {
			return new WP_Error( 'ikon_seo_closed_loop_complete', __( 'Only approved or in-progress recommendations can enter monitoring.', 'ikon-seo' ) );
		}
		if ( empty( $recommendation['baseline_snapshot_id'] ) ) {
			$baseline = $this->capture_snapshot( $recommendation_id, 'baseline', $user_id );
			if ( is_wp_error( $baseline ) ) {
				return $baseline;
			}
		}
		$now = current_time( 'mysql', true );
		$wpdb->update(
			$this->recommendations_table(),
			array( 'status' => 'monitoring', 'completion_notes' => substr( sanitize_textarea_field( $notes ), 0, 10000 ), 'completed_at' => $now, 'updated_at' => $now ),
			array( 'id' => $recommendation_id, 'profile_id' => $this->profile_id() ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);
		$this->schedule_outcomes( $recommendation_id, $now );
		$this->record_history( $recommendation_id, 'monitoring', 'Recommendation completed and outcome monitoring started', $notes, $user_id );
		return array( 'recommendation_id' => $recommendation_id, 'status' => 'monitoring', 'measurement_windows' => $this->measurement_windows() );
	}

	public function dismiss( $recommendation_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		$recommendation = $this->get_recommendation( $recommendation_id );
		if ( is_wp_error( $recommendation ) ) {
			return $recommendation;
		}
		$wpdb->update(
			$this->recommendations_table(),
			array( 'status' => 'dismissed', 'completion_notes' => substr( sanitize_textarea_field( $notes ), 0, 10000 ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $recommendation_id, 'profile_id' => $this->profile_id() ),
			array( '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);
		$this->record_history( $recommendation_id, 'dismissed', 'Recommendation dismissed', $notes, $user_id );
		return array( 'recommendation_id' => $recommendation_id, 'status' => 'dismissed' );
	}

	public function capture_snapshot( $recommendation_id, $snapshot_type = 'manual', $user_id = 0 ) {
		global $wpdb;
		$recommendation = $this->get_recommendation( $recommendation_id );
		if ( is_wp_error( $recommendation ) ) {
			return $recommendation;
		}
		$metrics = $this->collect_metrics( $recommendation );
		$captured_at = current_time( 'mysql', true );
		$hash = hash( 'sha256', implode( '|', array( $recommendation_id, sanitize_key( $snapshot_type ), $captured_at, wp_json_encode( $metrics ) ) ) );
		$result = $wpdb->insert(
			$this->snapshots_table(),
			array(
				'snapshot_hash'    => $hash,
				'recommendation_id'=> $recommendation_id,
				'snapshot_type'    => sanitize_key( $snapshot_type ),
				'metrics_json'     => wp_json_encode( $metrics ),
				'captured_at'      => $captured_at,
				'created_by'       => absint( $user_id ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d' )
		);
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_closed_loop_snapshot', __( 'The outcome snapshot could not be stored.', 'ikon-seo' ) );
		}
		$snapshot_id = absint( $wpdb->insert_id );
		if ( 'baseline' === sanitize_key( $snapshot_type ) ) {
			$wpdb->update( $this->recommendations_table(), array( 'baseline_snapshot_id' => $snapshot_id, 'updated_at' => $captured_at ), array( 'id' => $recommendation_id ), array( '%d', '%s' ), array( '%d' ) );
		}
		return array( 'snapshot_id' => $snapshot_id, 'snapshot_type' => sanitize_key( $snapshot_type ), 'captured_at' => $captured_at, 'metrics' => $metrics );
	}

	public function measure( $recommendation_id, $window_days = 0, $force = false, $user_id = 0 ) {
		global $wpdb;
		$recommendation = $this->get_recommendation( $recommendation_id );
		if ( is_wp_error( $recommendation ) ) {
			return $recommendation;
		}
		if ( empty( $recommendation['baseline_snapshot_id'] ) ) {
			return new WP_Error( 'ikon_seo_closed_loop_baseline', __( 'A baseline snapshot is required before measuring an outcome.', 'ikon-seo' ) );
		}
		$window_days = $window_days ? absint( $window_days ) : $this->next_due_window( $recommendation_id );
		if ( ! $window_days ) {
			return new WP_Error( 'ikon_seo_closed_loop_window', __( 'No due measurement window was found.', 'ikon-seo' ) );
		}
		$outcome_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->outcomes_table()} WHERE recommendation_id=%d AND window_days=%d", $recommendation_id, $window_days ), ARRAY_A );
		if ( ! $outcome_row ) {
			return new WP_Error( 'ikon_seo_closed_loop_outcome', __( 'The requested outcome window is not scheduled.', 'ikon-seo' ) );
		}
		if ( ! $force && ! empty( $outcome_row['measured_at'] ) ) {
			return new WP_Error( 'ikon_seo_closed_loop_measured', __( 'This outcome window has already been measured.', 'ikon-seo' ) );
		}
		$current = $this->capture_snapshot( $recommendation_id, 'measurement_' . $window_days, $user_id );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$baseline = $this->snapshot( absint( $recommendation['baseline_snapshot_id'] ) );
		if ( ! $baseline ) {
			return new WP_Error( 'ikon_seo_closed_loop_baseline_missing', __( 'The baseline snapshot could not be read.', 'ikon-seo' ) );
		}
		$comparison = $this->compare_metrics( (array) $baseline['metrics'], (array) $current['metrics'] );
		$now = current_time( 'mysql', true );
		$wpdb->update(
			$this->outcomes_table(),
			array(
				'measured_at'      => $now,
				'measurement_snapshot_id' => absint( $current['snapshot_id'] ),
				'outcome'          => $comparison['outcome'],
				'confidence'       => $comparison['confidence'],
				'summary'          => $comparison['summary'],
				'deltas_json'      => wp_json_encode( $comparison['deltas'] ),
				'updated_at'       => $now,
			),
			array( 'id' => absint( $outcome_row['id'] ) ),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		$final_status = $this->final_status( $recommendation_id );
		if ( $final_status ) {
			$this->update_recommendation_status( $recommendation_id, $final_status, $user_id, false );
		}
		update_option( 'ikon_seo_closed_loop_last_measurement', $now, false );
		$this->record_history( $recommendation_id, $comparison['outcome'], 'Recommendation outcome measured', $comparison['summary'], $user_id );
		return array( 'recommendation_id' => $recommendation_id, 'window_days' => $window_days, 'comparison' => $comparison, 'final_status' => $final_status ?: 'monitoring' );
	}

	public function run_due_measurements( $limit = 5, $user_id = 0 ) {
		global $wpdb;
		$limit = max( 1, min( 50, absint( $limit ) ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT o.recommendation_id,o.window_days FROM {$this->outcomes_table()} o INNER JOIN {$this->recommendations_table()} r ON r.id=o.recommendation_id WHERE r.profile_id=%s AND r.status='monitoring' AND o.measured_at IS NULL AND o.due_at <= %s ORDER BY o.due_at ASC LIMIT %d", $this->profile_id(), current_time( 'mysql', true ), $limit ), ARRAY_A );
		$summary = array( 'seen' => 0, 'measured' => 0, 'failed' => 0, 'errors' => array() );
		foreach ( $rows ?: array() as $row ) {
			$summary['seen']++;
			$result = $this->measure( absint( $row['recommendation_id'] ), absint( $row['window_days'] ), false, $user_id );
			if ( is_wp_error( $result ) ) {
				$summary['failed']++;
				if ( count( $summary['errors'] ) < 10 ) {
					$summary['errors'][] = $result->get_error_message();
				}
			} else {
				$summary['measured']++;
			}
		}
		return $summary;
	}

	public function create_checkpoint( $reason = 'Manual checkpoint', $user_id = 0 ) {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		$payload  = array(
			'format'            => 'ikon-seo-recovery-v1',
			'created_at'        => current_time( 'mysql', true ),
			'plugin_version'    => IKON_SEO_VERSION,
			'component_version' => sanitize_text_field( $settings['component_version'] ?? '' ),
			'profile_id'        => $this->profile_id(),
			'settings'          => $this->recovery_settings( $settings ),
		);
		$encoded = $this->crypto->encrypt( wp_json_encode( $payload ) );
		if ( is_wp_error( $encoded ) ) {
			return $encoded;
		}
		$hash = hash( 'sha256', $payload['profile_id'] . '|' . $payload['created_at'] . '|' . $reason . '|' . $payload['component_version'] );
		$result = $wpdb->insert(
			$this->checkpoints_table(),
			array(
				'checkpoint_hash'  => $hash,
				'profile_id'       => $payload['profile_id'],
				'reason'           => substr( sanitize_text_field( $reason ), 0, 255 ),
				'component_version'=> $payload['component_version'],
				'payload_encrypted'=> $encoded,
				'status'           => 'available',
				'created_by'       => absint( $user_id ),
				'created_at'       => $payload['created_at'],
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_closed_loop_checkpoint', __( 'The recovery checkpoint could not be stored.', 'ikon-seo' ) );
		}
		$this->trim_checkpoints();
		$this->history->add(
			array(
				'category' => 'system',
				'status'   => 'completed',
				'title'    => 'Recovery checkpoint created',
				'summary'  => sanitize_text_field( $reason ),
				'details'  => array( 'checkpoint_id' => absint( $wpdb->insert_id ), 'component_version' => $payload['component_version'], 'credentials_included' => false ),
			),
			'closed_loop',
			absint( $user_id )
		);
		return array( 'checkpoint_id' => absint( $wpdb->insert_id ), 'created_at' => $payload['created_at'], 'credentials_included' => false );
	}

	public function restore_checkpoint( $checkpoint_id, $user_id = 0 ) {
		global $wpdb;
		if ( ! Ikon_SEO_Agency::can_manage() ) {
			return new WP_Error( 'ikon_seo_closed_loop_restore_permission', __( 'Only an approved agency administrator can restore a recovery checkpoint.', 'ikon-seo' ) );
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->checkpoints_table()} WHERE id=%d AND profile_id=%s", absint( $checkpoint_id ), $this->profile_id() ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'ikon_seo_closed_loop_checkpoint_missing', __( 'The recovery checkpoint was not found.', 'ikon-seo' ) );
		}
		$decoded = $this->crypto->decrypt( $row['payload_encrypted'] );
		if ( is_wp_error( $decoded ) ) {
			return $decoded;
		}
		$payload = json_decode( $decoded, true );
		if ( ! is_array( $payload ) || 'ikon-seo-recovery-v1' !== ( $payload['format'] ?? '' ) || ! is_array( $payload['settings'] ?? null ) ) {
			return new WP_Error( 'ikon_seo_closed_loop_checkpoint_format', __( 'The recovery checkpoint format is invalid.', 'ikon-seo' ) );
		}
		$current = Ikon_SEO_Plugin::settings();
		$restored = array_merge( $current, $this->recovery_settings( $payload['settings'] ) );
		$restored['component_version'] = Ikon_SEO_Plugin::DB_VERSION;
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $restored, false );
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->checkpoints_table(), array( 'status' => 'restored', 'restored_at' => $now, 'restored_by' => absint( $user_id ) ), array( 'id' => absint( $checkpoint_id ) ), array( '%s', '%s', '%d' ), array( '%d' ) );
		$this->history->add(
			array(
				'category' => 'system',
				'status'   => 'completed',
				'title'    => 'Recovery checkpoint restored',
				'summary'  => 'Non-secret Ikon SEO configuration was restored from an approved checkpoint.',
				'details'  => array( 'checkpoint_id' => absint( $checkpoint_id ), 'credentials_preserved' => true ),
			),
			'closed_loop',
			absint( $user_id )
		);
		return array( 'checkpoint_id' => absint( $checkpoint_id ), 'restored_at' => $now, 'credentials_preserved' => true );
	}

	public function system_health() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		$tables = array(
			'recommendations' => $this->recommendations_table(),
			'snapshots'       => $this->snapshots_table(),
			'outcomes'        => $this->outcomes_table(),
			'checkpoints'     => $this->checkpoints_table(),
		);
		$table_status = array();
		foreach ( $tables as $key => $table ) {
			$table_status[ $key ] = $this->table_exists( $table );
		}
		$cron = wp_next_scheduled( self::CRON_HOOK );
		$issues = array();
		if ( in_array( false, $table_status, true ) ) {
			$issues[] = array( 'severity' => 'critical', 'code' => 'database_tables', 'message' => 'One or more Closed-Loop database tables are unavailable.' );
		}
		if ( ! $cron ) {
			$issues[] = array( 'severity' => 'high', 'code' => 'measurement_schedule', 'message' => 'The daily outcome-measurement schedule is not active.' );
		}
		if ( (string) ( $settings['component_version'] ?? '' ) !== (string) Ikon_SEO_Plugin::DB_VERSION ) {
			$issues[] = array( 'severity' => 'high', 'code' => 'component_version', 'message' => 'The stored component version does not match the plugin database version.' );
		}
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			$issues[] = array( 'severity' => 'critical', 'code' => 'php_version', 'message' => 'The PHP version is below the supported minimum.' );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Automation::RUNNER_HOOK ) ) {
			$issues[] = array( 'severity' => 'medium', 'code' => 'workflow_schedule', 'message' => 'The safe workflow runner schedule is not active.' );
		}
		return array(
			'healthy'            => empty( $issues ),
			'safe_mode'          => ! empty( $settings['closed_loop_safe_mode'] ),
			'tables'             => $table_status,
			'next_measurement_run'=> $cron ? gmdate( 'Y-m-d H:i:s', $cron ) : '',
			'php_version'        => PHP_VERSION,
			'wordpress_version'  => get_bloginfo( 'version' ),
			'plugin_version'     => IKON_SEO_VERSION,
			'database_version'   => Ikon_SEO_Plugin::DB_VERSION,
			'issues'             => $issues,
			'recovery_checkpoints'=> $this->table_exists( $this->checkpoints_table() ) ? absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->checkpoints_table()} WHERE profile_id=%s", $this->profile_id() ) ) ) : 0,
		);
	}

	private function from_diagnostics( array $report ) {
		$items = array();
		foreach ( array_slice( (array) ( $report['pages'] ?? array() ), 0, 200 ) as $page ) {
			$post_id = absint( $page['post_id'] ?? 0 );
			$url = esc_url_raw( $page['url'] ?? '' );
			$title = sanitize_text_field( $page['title'] ?? '' );
			$business_value = absint( $page['business_value']['score'] ?? 3 );
			foreach ( array_slice( (array) ( $page['blockers'] ?? array() ), 0, 3 ) as $finding ) {
				$items[] = array(
					'post_id'           => $post_id,
					'target_url'        => $url,
					'source_module'     => 'diagnostics',
					'category'          => sanitize_key( $finding['category'] ?? 'ranking' ),
					'root_cause'        => sanitize_key( $finding['root_cause'] ?? $finding['code'] ?? 'page_finding' ),
					'title'             => $title ? $title . ': ' . sanitize_text_field( $finding['message'] ?? 'Page improvement' ) : sanitize_text_field( $finding['message'] ?? 'Page improvement' ),
					'rationale'         => sanitize_text_field( $finding['evidence'] ?? '' ),
					'evidence'          => $finding,
					'action'            => array( 'recommended_action' => sanitize_text_field( $finding['recommended_action'] ?? '' ), 'actionability' => sanitize_key( $finding['actionability'] ?? 'review' ) ),
					'priority'          => absint( $finding['priority_score'] ?? $page['fix_priority'] ?? 50 ),
					'confidence'        => sanitize_key( $finding['confidence'] ?? 'medium' ),
					'business_value'    => max( 1, min( 5, $business_value ) ),
					'effort'            => max( 1, min( 5, absint( $finding['effort'] ?? 3 ) ) ),
					'approval_required' => true,
				);
			}
		}
		return $items;
	}

	private function from_search( array $report ) {
		$items = array();
		foreach ( array_slice( (array) ( $report['striking_distance'] ?? array() ), 0, 50 ) as $row ) {
			$items[] = $this->simple_item( 'search_intelligence', 'opportunity', 'striking_distance', 'Strengthen visibility for “' . sanitize_text_field( $row['query'] ?? '' ) . '”', sanitize_text_field( $row['recommended_action'] ?? '' ), $row, absint( $row['priority'] ?? 65 ), 'high', esc_url_raw( $row['page'] ?? '' ) );
		}
		foreach ( array_slice( (array) ( $report['content_decay'] ?? array() ), 0, 50 ) as $row ) {
			$priority = min( 100, 65 + absint( abs( (float) ( $row['impressions_change'] ?? 0 ) ) / 4 ) );
			$items[] = $this->simple_item( 'search_intelligence', 'ranking', 'content_decay', 'Review declining organic visibility', sanitize_text_field( $row['recommended_action'] ?? '' ), $row, $priority, sanitize_key( $row['confidence'] ?? 'medium' ), esc_url_raw( $row['page'] ?? '' ) );
		}
		foreach ( array_slice( (array) ( $report['cannibalisation'] ?? array() ), 0, 50 ) as $row ) {
			$items[] = $this->simple_item( 'search_intelligence', 'ranking', sanitize_key( $row['classification'] ?? 'page_overlap' ), 'Review competing pages for “' . sanitize_text_field( $row['query'] ?? '' ) . '”', sanitize_text_field( $row['recommended_action'] ?? '' ), $row, 'high' === ( $row['confidence'] ?? '' ) ? 84 : 68, sanitize_key( $row['confidence'] ?? 'medium' ), '' );
		}
		return $items;
	}

	private function from_planned_opportunities( array $report, array $existing_items ) {
		$items = array();
		$existing_signatures = array();
		foreach ( $existing_items as $existing ) {
			$url = $this->normal_url( $existing['target_url'] ?? '' );
			$post_id = absint( $existing['post_id'] ?? 0 );
			$category = sanitize_key( $existing['category'] ?? 'opportunity' );
			if ( $url || $post_id ) {
				$existing_signatures[ $post_id . '|' . $url . '|' . $category ] = true;
			}
		}
		foreach ( array_slice( (array) ( $report['opportunities'] ?? array() ), 0, 100 ) as $row ) {
			if ( 'planned' !== sanitize_key( $row['status'] ?? '' ) ) {
				continue;
			}
			$url = esc_url_raw( $row['target_url'] ?? '' );
			$post_id = absint( $row['post_id'] ?? 0 );
			$category = sanitize_key( $row['category'] ?? 'opportunity' );
			$signature = $post_id . '|' . $this->normal_url( $url ) . '|' . $category;
			if ( ( $url || $post_id ) && isset( $existing_signatures[ $signature ] ) ) {
				continue;
			}
			$actions = (array) ( $row['actions'] ?? array() );
			$action = sanitize_text_field( $actions[0] ?? 'Review the approved opportunity evidence and define the implementation task.' );
			$evidence = array(
				'opportunity_id' => absint( $row['id'] ?? 0 ),
				'primary_source' => sanitize_key( $row['primary_source'] ?? 'unknown' ),
				'keyword' => sanitize_text_field( $row['keyword'] ?? '' ),
				'intent' => sanitize_key( $row['intent'] ?? '' ),
				'effort' => sanitize_key( $row['effort'] ?? 'medium' ),
				'risk' => sanitize_key( $row['risk'] ?? 'low' ),
				'freshness_score' => absint( $row['freshness_score'] ?? 0 ),
				'summary' => sanitize_textarea_field( $row['summary'] ?? '' ),
				'supporting_evidence' => (array) ( $row['evidence'] ?? array() ),
			);
			$item = $this->simple_item(
				'opportunity_engine',
				$category,
				sanitize_key( $row['type'] ?? 'planned_opportunity' ),
				sanitize_text_field( $row['title'] ?? 'Planned SEO opportunity' ),
				$action,
				$evidence,
				absint( $row['priority'] ?? 50 ),
				sanitize_key( $row['confidence'] ?? 'medium' ),
				$url,
				$post_id
			);
			$item['effort'] = array( 'low' => 2, 'medium' => 3, 'high' => 5 )[ sanitize_key( $row['effort'] ?? 'medium' ) ] ?? 3;
			$items[] = $item;
		}
		return $items;
	}

	private function from_publisher( array $report ) {
		$items = array();
		foreach ( array_slice( (array) ( $report['lifecycle'] ?? $report['lifecycle_recommendations'] ?? array() ), 0, 100 ) as $row ) {
			$post_id = absint( $row['post_id'] ?? 0 );
			$decision = sanitize_key( $row['decision'] ?? $row['recommendation'] ?? 'review' );
			$items[] = $this->simple_item( 'publisher', 'content_lifecycle', $decision, sanitize_text_field( $row['title'] ?? 'Review content lifecycle' ), sanitize_text_field( $row['recommended_action'] ?? $row['reason'] ?? 'Review the page and confirm the appropriate lifecycle action.' ), $row, absint( $row['priority'] ?? 55 ), sanitize_key( $row['confidence'] ?? 'medium' ), $post_id ? get_permalink( $post_id ) : '', $post_id );
		}
		return $items;
	}

	private function from_module_recommendations( array $report, $module, $default_priority ) {
		$items = array();
		$recommendations = array();
		foreach ( array( 'recommendations', 'actions', 'priorities', 'opportunities' ) as $key ) {
			if ( ! empty( $report[ $key ] ) && is_array( $report[ $key ] ) ) {
				$recommendations = array_merge( $recommendations, $report[ $key ] );
			}
		}
		foreach ( array_slice( $recommendations, 0, 100 ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$title = sanitize_text_field( $row['title'] ?? $row['action'] ?? $row['message'] ?? ucfirst( str_replace( '_', ' ', $module ) ) . ' review' );
			$action = sanitize_text_field( $row['recommended_action'] ?? $row['action'] ?? $row['summary'] ?? 'Review the supporting evidence before making a change.' );
			$priority = absint( $row['priority'] ?? $row['priority_score'] ?? $default_priority );
			$items[] = $this->simple_item( $module, sanitize_key( $row['category'] ?? 'opportunity' ), sanitize_key( $row['code'] ?? $row['type'] ?? 'module_recommendation' ), $title, $action, $row, $priority, sanitize_key( $row['confidence'] ?? 'medium' ), esc_url_raw( $row['url'] ?? $row['page'] ?? '' ), absint( $row['post_id'] ?? 0 ) );
		}
		return $items;
	}

	private function simple_item( $module, $category, $root_cause, $title, $action, array $evidence, $priority, $confidence, $url = '', $post_id = 0 ) {
		return array(
			'post_id'           => absint( $post_id ),
			'target_url'        => esc_url_raw( $url ),
			'source_module'     => sanitize_key( $module ),
			'category'          => sanitize_key( $category ),
			'root_cause'        => sanitize_key( $root_cause ),
			'title'             => sanitize_text_field( $title ),
			'rationale'         => sanitize_text_field( $evidence['evidence'] ?? $evidence['summary'] ?? $evidence['reason'] ?? '' ),
			'evidence'          => $evidence,
			'action'            => array( 'recommended_action' => sanitize_text_field( $action ) ),
			'priority'          => max( 1, min( 100, absint( $priority ) ) ),
			'confidence'        => in_array( $confidence, array( 'low', 'medium', 'high' ), true ) ? $confidence : 'medium',
			'business_value'    => 3,
			'effort'            => 3,
			'approval_required' => true,
		);
	}

	private function deduplicate_plan( array $items ) {
		$deduped = array();
		foreach ( $items as $item ) {
			$key = $this->recommendation_key( $item );
			if ( ! isset( $deduped[ $key ] ) || absint( $item['priority'] ?? 0 ) > absint( $deduped[ $key ]['priority'] ?? 0 ) ) {
				$item['recommendation_key'] = $key;
				$deduped[ $key ] = $item;
			}
		}
		return array_values( $deduped );
	}

	private function upsert_recommendation( array $item, $user_id = 0 ) {
		global $wpdb;
		$key = sanitize_text_field( $item['recommendation_key'] ?? $this->recommendation_key( $item ) );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id,status FROM {$this->recommendations_table()} WHERE recommendation_key=%s", $key ), ARRAY_A );
		$now = current_time( 'mysql', true );
		$data = array(
			'recommendation_key' => $key,
			'profile_id'         => $this->profile_id(),
			'post_id'            => absint( $item['post_id'] ?? 0 ),
			'target_url'         => esc_url_raw( $item['target_url'] ?? '' ),
			'source_module'      => sanitize_key( $item['source_module'] ?? 'other' ),
			'category'           => sanitize_key( $item['category'] ?? 'opportunity' ),
			'root_cause'         => sanitize_key( $item['root_cause'] ?? 'recommendation' ),
			'title'              => substr( sanitize_text_field( $item['title'] ?? 'SEO recommendation' ), 0, 255 ),
			'rationale'          => substr( sanitize_textarea_field( $item['rationale'] ?? '' ), 0, 10000 ),
			'evidence_json'      => wp_json_encode( $this->bounded_array( (array) ( $item['evidence'] ?? array() ) ) ),
			'action_json'        => wp_json_encode( $this->bounded_array( (array) ( $item['action'] ?? array() ) ) ),
			'priority'           => max( 1, min( 100, absint( $item['priority'] ?? 50 ) ) ),
			'confidence'         => $this->confidence( $item['confidence'] ?? 'medium' ),
			'business_value'     => max( 1, min( 5, absint( $item['business_value'] ?? 3 ) ) ),
			'effort'             => max( 1, min( 5, absint( $item['effort'] ?? 3 ) ) ),
			'approval_required'  => ! empty( $item['approval_required'] ) ? 1 : 0,
			'updated_at'         => $now,
		);
		if ( $existing ) {
			if ( in_array( $existing['status'], array( 'succeeded', 'dismissed' ), true ) ) {
				return array( 'recommendation_id' => absint( $existing['id'] ), 'status' => $existing['status'], 'unchanged' => true );
			}
			$wpdb->update( $this->recommendations_table(), $data, array( 'id' => absint( $existing['id'] ) ) );
			return array( 'recommendation_id' => absint( $existing['id'] ), 'status' => $existing['status'], 'updated' => true );
		}
		$data['status']     = 'proposed';
		$data['created_by'] = absint( $user_id );
		$data['created_at'] = $now;
		$result = $wpdb->insert( $this->recommendations_table(), $data );
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_closed_loop_store', __( 'The recommendation could not be stored.', 'ikon-seo' ) );
		}
		return array( 'recommendation_id' => absint( $wpdb->insert_id ), 'status' => 'proposed', 'created' => true );
	}

	private function collect_metrics( array $recommendation ) {
		$post_id = absint( $recommendation['post_id'] ?? 0 );
		$url = esc_url_raw( $recommendation['target_url'] ?? '' );
		if ( ! $url && $post_id ) {
			$url = get_permalink( $post_id );
		}
		$metrics = array(
			'captured_at' => current_time( 'mysql', true ),
			'post_id'     => $post_id,
			'url'         => $url,
			'search'      => array(),
			'analytics'   => array(),
			'diagnostics' => array(),
			'technical'   => array(),
			'indexation'  => array(),
			'data_sources'=> array(),
		);
		if ( $url ) {
			$search = $this->search_intelligence->page_summary( $url );
			if ( ! empty( $search['performance'] ) ) {
				$performance = (array) $search['performance'];
				$metrics['search'] = array(
					'clicks'      => (float) ( $performance['clicks'] ?? 0 ),
					'impressions' => (float) ( $performance['impressions'] ?? 0 ),
					'ctr'         => (float) ( $performance['ctr'] ?? 0 ),
					'position'    => (float) ( $performance['position'] ?? 0 ),
				);
				$metrics['data_sources'][] = 'search_intelligence';
			}
			$technical = $this->technical->page_summary( $url );
			if ( is_array( $technical ) && ! empty( $technical ) ) {
				$metrics['technical'] = $this->bounded_array( $technical );
				$metrics['data_sources'][] = 'technical_intelligence';
			}

			$indexation = $this->indexation->page_summary( $url );
			if ( is_array( $indexation ) && ! empty( $indexation['available'] ) ) {
				$metrics['indexation'] = $this->bounded_array( $indexation );
				$metrics['data_sources'][] = 'indexation_intelligence';
			}
		}
		if ( $post_id ) {
			$diagnostic = $this->diagnostics->page_report( $post_id, false, true );
			if ( is_array( $diagnostic ) ) {
				$metrics['diagnostics'] = array(
					'fix_priority'     => absint( $diagnostic['fix_priority'] ?? 0 ),
					'ranking_priority' => absint( $diagnostic['priorities']['ranking'] ?? 0 ),
					'finding_count'    => count( (array) ( $diagnostic['blockers'] ?? array() ) ),
					'evidence_level'   => sanitize_key( $diagnostic['data_sufficiency']['level'] ?? 'limited' ),
				);
				$metrics['data_sources'][] = 'diagnostics';
			}
		}
		$ga = $this->analytics->report( 28, false );
		if ( is_array( $ga ) && $url ) {
			$path = (string) wp_parse_url( $url, PHP_URL_PATH );
			foreach ( (array) ( $ga['top_pages'] ?? array() ) as $row ) {
				if ( $this->normal_path( $row['path'] ?? '' ) === $this->normal_path( $path ) ) {
					$metrics['analytics'] = array(
						'sessions'         => (float) ( $row['sessions'] ?? 0 ),
						'active_users'     => (float) ( $row['active_users'] ?? 0 ),
						'engaged_sessions' => (float) ( $row['engaged_sessions'] ?? 0 ),
						'engagement_rate'  => (float) ( $row['engagement_rate'] ?? 0 ),
						'views'             => (float) ( $row['views'] ?? 0 ),
						'key_events'        => (float) ( $row['key_events'] ?? 0 ),
					);
					$metrics['data_sources'][] = 'analytics';
					break;
				}
			}
		}
		$metrics['data_sources'] = array_values( array_unique( $metrics['data_sources'] ) );
		return $metrics;
	}

	private function compare_metrics( array $baseline, array $current ) {
		$deltas = array();
		$weighted = 0.0;
		$weight_total = 0.0;
		$signals = 0;
		$add = function( $key, $before, $after, $weight, $higher_is_better = true ) use ( &$deltas, &$weighted, &$weight_total, &$signals ) {
			$before = (float) $before;
			$after  = (float) $after;
			if ( 0.0 === $before && 0.0 === $after ) {
				return;
			}
			$change = 0.0 === $before ? ( $after > 0 ? 100.0 : 0.0 ) : ( ( $after - $before ) / abs( $before ) ) * 100;
			$directional = $higher_is_better ? $change : -$change;
			$deltas[ $key ] = array( 'before' => round( $before, 4 ), 'after' => round( $after, 4 ), 'change_percent' => round( $change, 2 ), 'directional_change' => round( $directional, 2 ) );
			$weighted += max( -100, min( 100, $directional ) ) * $weight;
			$weight_total += $weight;
			$signals++;
		};

		$add( 'search_clicks', $baseline['search']['clicks'] ?? 0, $current['search']['clicks'] ?? 0, 1.2, true );
		$add( 'search_impressions', $baseline['search']['impressions'] ?? 0, $current['search']['impressions'] ?? 0, 1.0, true );
		$add( 'search_ctr', $baseline['search']['ctr'] ?? 0, $current['search']['ctr'] ?? 0, 0.7, true );
		$add( 'search_position', $baseline['search']['position'] ?? 0, $current['search']['position'] ?? 0, 1.0, false );
		$add( 'sessions', $baseline['analytics']['sessions'] ?? 0, $current['analytics']['sessions'] ?? 0, 0.8, true );
		$add( 'key_events', $baseline['analytics']['key_events'] ?? 0, $current['analytics']['key_events'] ?? 0, 1.2, true );
		$add( 'diagnostic_priority', $baseline['diagnostics']['fix_priority'] ?? 0, $current['diagnostics']['fix_priority'] ?? 0, 0.6, false );
		$add( 'finding_count', $baseline['diagnostics']['finding_count'] ?? 0, $current['diagnostics']['finding_count'] ?? 0, 0.5, false );

		$score = $weight_total > 0 ? $weighted / $weight_total : 0;
		if ( $signals < 2 ) {
			$outcome = 'inconclusive';
			$confidence = 'low';
			$summary = 'Too little comparable evidence is available to determine whether the recommendation helped.';
		} elseif ( $score >= 12 ) {
			$outcome = 'succeeded';
			$confidence = $signals >= 5 ? 'high' : 'medium';
			$summary = sprintf( 'The measured evidence improved with a weighted directional change of %.1f.', $score );
		} elseif ( $score <= -12 ) {
			$outcome = 'declined';
			$confidence = $signals >= 5 ? 'high' : 'medium';
			$summary = sprintf( 'The measured evidence declined with a weighted directional change of %.1f. Review seasonality, tracking, external changes and the implementation before reversing anything.', $score );
		} else {
			$outcome = 'neutral';
			$confidence = $signals >= 4 ? 'medium' : 'low';
			$summary = sprintf( 'No material directional change was measured. The weighted change was %.1f.', $score );
		}
		return array( 'outcome' => $outcome, 'confidence' => $confidence, 'score' => round( $score, 2 ), 'signals' => $signals, 'summary' => $summary, 'deltas' => $deltas );
	}

	private function schedule_outcomes( $recommendation_id, $completed_at ) {
		global $wpdb;
		foreach ( $this->measurement_windows() as $days ) {
			$due_at = gmdate( 'Y-m-d H:i:s', strtotime( $completed_at . ' UTC +' . absint( $days ) . ' days' ) );
			$now = current_time( 'mysql', true );
			$sql = "INSERT INTO {$this->outcomes_table()} (recommendation_id,window_days,due_at,outcome,confidence,summary,deltas_json,created_at,updated_at) VALUES (%d,%d,%s,'pending','low','','{}',%s,%s) ON DUPLICATE KEY UPDATE due_at=VALUES(due_at),updated_at=VALUES(updated_at)";
			$wpdb->query( $wpdb->prepare( $sql, absint( $recommendation_id ), absint( $days ), $due_at, $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}

	private function due_measurements( $limit = 50 ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT o.*,r.title,r.target_url,r.post_id FROM {$this->outcomes_table()} o INNER JOIN {$this->recommendations_table()} r ON r.id=o.recommendation_id WHERE r.profile_id=%s AND o.measured_at IS NULL ORDER BY o.due_at ASC LIMIT %d", $this->profile_id(), max( 1, min( 200, absint( $limit ) ) ) ), ARRAY_A );
		return array_map( function( $row ) {
			return array(
				'id'                => absint( $row['id'] ?? 0 ),
				'recommendation_id' => absint( $row['recommendation_id'] ?? 0 ),
				'window_days'       => absint( $row['window_days'] ?? 0 ),
				'due_at'            => sanitize_text_field( $row['due_at'] ?? '' ),
				'overdue'           => ! empty( $row['due_at'] ) && strtotime( $row['due_at'] . ' UTC' ) <= time(),
				'title'             => sanitize_text_field( $row['title'] ?? '' ),
				'target_url'        => esc_url_raw( $row['target_url'] ?? '' ),
				'post_id'           => absint( $row['post_id'] ?? 0 ),
			);
		}, $rows ?: array() );
	}

	private function recent_outcomes( $limit = 100 ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT o.*,r.title,r.target_url,r.post_id FROM {$this->outcomes_table()} o INNER JOIN {$this->recommendations_table()} r ON r.id=o.recommendation_id WHERE r.profile_id=%s ORDER BY COALESCE(o.measured_at,o.due_at) DESC LIMIT %d", $this->profile_id(), max( 1, min( 500, absint( $limit ) ) ) ), ARRAY_A );
		return array_map( function( $row ) {
			return array(
				'id'                => absint( $row['id'] ?? 0 ),
				'recommendation_id' => absint( $row['recommendation_id'] ?? 0 ),
				'window_days'       => absint( $row['window_days'] ?? 0 ),
				'due_at'            => sanitize_text_field( $row['due_at'] ?? '' ),
				'measured_at'       => sanitize_text_field( $row['measured_at'] ?? '' ),
				'outcome'           => sanitize_key( $row['outcome'] ?? 'pending' ),
				'confidence'        => sanitize_key( $row['confidence'] ?? 'low' ),
				'summary'           => sanitize_text_field( $row['summary'] ?? '' ),
				'deltas'            => $this->decode_json( $row['deltas_json'] ?? '{}' ),
				'title'             => sanitize_text_field( $row['title'] ?? '' ),
				'target_url'        => esc_url_raw( $row['target_url'] ?? '' ),
				'post_id'           => absint( $row['post_id'] ?? 0 ),
			);
		}, $rows ?: array() );
	}

	private function final_status( $recommendation_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT outcome,window_days,measured_at FROM {$this->outcomes_table()} WHERE recommendation_id=%d ORDER BY window_days ASC", absint( $recommendation_id ) ), ARRAY_A );
		$measured = array_values( array_filter( $rows ?: array(), function( $row ) { return ! empty( $row['measured_at'] ); } ) );
		if ( ! $measured ) {
			return '';
		}
		$latest = end( $measured );
		$all_done = count( $measured ) === count( $rows );
		if ( $all_done ) {
			return sanitize_key( $latest['outcome'] ?? 'inconclusive' );
		}
		return '';
	}

	private function update_recommendation_status( $recommendation_id, $status, $user_id = 0, $record = true ) {
		global $wpdb;
		$status = sanitize_key( $status );
		$wpdb->update( $this->recommendations_table(), array( 'status' => $status, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $recommendation_id ), 'profile_id' => $this->profile_id() ), array( '%s', '%s' ), array( '%d', '%s' ) );
		if ( $record ) {
			$this->record_history( $recommendation_id, $status, 'Recommendation status updated', '', $user_id );
		}
	}

	private function record_history( $recommendation_id, $status, $title, $summary, $user_id = 0 ) {
		$recommendation = $this->get_recommendation( $recommendation_id );
		if ( is_wp_error( $recommendation ) ) {
			return;
		}
		$this->history->add(
			array(
				'category'        => 'experiment',
				'status'          => in_array( $status, array( 'succeeded', 'neutral', 'declined', 'inconclusive', 'dismissed' ), true ) ? 'completed' : 'open',
				'title'           => sanitize_text_field( $title ),
				'summary'         => sanitize_text_field( $summary ?: $recommendation['title'] ),
				'details'         => array( 'recommendation_id' => absint( $recommendation_id ), 'recommendation_status' => sanitize_key( $status ), 'source_module' => $recommendation['source_module'], 'target_url' => $recommendation['target_url'] ),
				'related_post_id' => absint( $recommendation['post_id'] ),
			),
			'closed_loop',
			absint( $user_id )
		);
	}

	private function get_recommendation( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->recommendations_table()} WHERE id=%d AND profile_id=%s", absint( $id ), $this->profile_id() ), ARRAY_A );
		return $row ? $this->prepare_recommendation( $row ) : new WP_Error( 'ikon_seo_closed_loop_missing', __( 'The recommendation was not found.', 'ikon-seo' ) );
	}

	private function prepare_recommendation( $row ) {
		return array(
			'id'                   => absint( $row['id'] ?? 0 ),
			'recommendation_key'   => sanitize_text_field( $row['recommendation_key'] ?? '' ),
			'post_id'              => absint( $row['post_id'] ?? 0 ),
			'target_url'           => esc_url_raw( $row['target_url'] ?? '' ),
			'source_module'        => sanitize_key( $row['source_module'] ?? '' ),
			'category'             => sanitize_key( $row['category'] ?? '' ),
			'root_cause'           => sanitize_key( $row['root_cause'] ?? '' ),
			'title'                => sanitize_text_field( $row['title'] ?? '' ),
			'rationale'            => sanitize_textarea_field( $row['rationale'] ?? '' ),
			'evidence'             => $this->decode_json( $row['evidence_json'] ?? '{}' ),
			'action'               => $this->decode_json( $row['action_json'] ?? '{}' ),
			'priority'             => absint( $row['priority'] ?? 0 ),
			'confidence'           => sanitize_key( $row['confidence'] ?? 'medium' ),
			'business_value'       => absint( $row['business_value'] ?? 0 ),
			'effort'               => absint( $row['effort'] ?? 0 ),
			'status'               => sanitize_key( $row['status'] ?? 'proposed' ),
			'approval_required'    => ! empty( $row['approval_required'] ),
			'baseline_snapshot_id' => absint( $row['baseline_snapshot_id'] ?? 0 ),
			'workflow_task_id'     => absint( $row['workflow_task_id'] ?? 0 ),
			'completion_notes'     => sanitize_textarea_field( $row['completion_notes'] ?? '' ),
			'completed_at'         => sanitize_text_field( $row['completed_at'] ?? '' ),
			'created_at'           => sanitize_text_field( $row['created_at'] ?? '' ),
			'updated_at'           => sanitize_text_field( $row['updated_at'] ?? '' ),
		);
	}

	private function snapshot( $snapshot_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->snapshots_table()} WHERE id=%d", absint( $snapshot_id ) ), ARRAY_A );
		if ( ! $row ) {
			return array();
		}
		return array( 'id' => absint( $row['id'] ), 'snapshot_type' => sanitize_key( $row['snapshot_type'] ), 'captured_at' => sanitize_text_field( $row['captured_at'] ), 'metrics' => $this->decode_json( $row['metrics_json'] ) );
	}

	private function next_due_window( $recommendation_id ) {
		global $wpdb;
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT window_days FROM {$this->outcomes_table()} WHERE recommendation_id=%d AND measured_at IS NULL ORDER BY due_at ASC LIMIT 1", absint( $recommendation_id ) ) ) );
	}

	private function checkpoints( $limit = 10 ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,reason,component_version,status,created_by,created_at,restored_at,restored_by FROM {$this->checkpoints_table()} WHERE profile_id=%s ORDER BY created_at DESC LIMIT %d", $this->profile_id(), max( 1, min( self::MAX_CHECKPOINTS, absint( $limit ) ) ) ), ARRAY_A );
		return array_map( function( $row ) {
			return array( 'id' => absint( $row['id'] ), 'reason' => sanitize_text_field( $row['reason'] ), 'component_version' => sanitize_text_field( $row['component_version'] ), 'status' => sanitize_key( $row['status'] ), 'created_by' => absint( $row['created_by'] ), 'created_at' => sanitize_text_field( $row['created_at'] ), 'restored_at' => sanitize_text_field( $row['restored_at'] ), 'restored_by' => absint( $row['restored_by'] ) );
		}, $rows ?: array() );
	}

	private function trim_checkpoints() {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$this->checkpoints_table()} WHERE profile_id=%s ORDER BY created_at DESC LIMIT 999 OFFSET %d", $this->profile_id(), self::MAX_CHECKPOINTS ) );
		foreach ( $ids ?: array() as $id ) {
			$wpdb->delete( $this->checkpoints_table(), array( 'id' => absint( $id ) ), array( '%d' ) );
		}
	}

	private function recovery_settings( array $settings ) {
		$sensitive = array(
			'token_hash', 'token_hint', 'connection_verified_at', 'connection_last_seen_at',
			'gsc_client_id', 'gsc_client_secret', 'gsc_refresh_token',
			'ga_client_id', 'ga_client_secret', 'ga_refresh_token',
			'gbp_client_id', 'gbp_client_secret', 'gbp_refresh_token',
			'pagespeed_api_key',
		);
		foreach ( $sensitive as $key ) {
			unset( $settings[ $key ] );
		}
		return $settings;
	}

	private function measurement_windows() {
		$settings = Ikon_SEO_Plugin::settings();
		$raw = sanitize_text_field( $settings['closed_loop_measurement_windows'] ?? self::DEFAULT_WINDOWS );
		$windows = array_values( array_unique( array_filter( array_map( 'absint', preg_split( '/[^0-9]+/', $raw ) ) ) ) );
		$windows = array_values( array_filter( $windows, function( $days ) { return $days >= 1 && $days <= 365; } ) );
		sort( $windows );
		return $windows ?: array( 14, 28, 60, 90 );
	}

	private function recommendation_key( array $item ) {
		return hash( 'sha256', implode( '|', array( $this->profile_id(), sanitize_key( $item['source_module'] ?? 'other' ), absint( $item['post_id'] ?? 0 ), $this->normal_url( $item['target_url'] ?? '' ), sanitize_key( $item['category'] ?? 'opportunity' ), sanitize_key( $item['root_cause'] ?? 'recommendation' ) ) ) );
	}

	private function normal_url( $url ) {
		$url = esc_url_raw( $url );
		return $url ? strtolower( untrailingslashit( $url ) ) : '';
	}

	private function normal_path( $path ) {
		$path = (string) $path;
		if ( false !== strpos( $path, '?' ) ) {
			$path = strstr( $path, '?', true );
		}
		return '/' . trim( strtolower( $path ), '/' );
	}

	private function confidence( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'low', 'medium', 'high' ), true ) ? $value : 'medium';
	}

	private function profile_id() {
		$profile = $this->profile->get();
		return sanitize_text_field( $profile['profile_id'] ?? $this->profile->fingerprint() );
	}

	private function tables_ready() {
		return $this->table_exists( $this->recommendations_table() ) && $this->table_exists( $this->snapshots_table() ) && $this->table_exists( $this->outcomes_table() ) && $this->table_exists( $this->checkpoints_table() );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	private function decode_json( $value ) {
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function bounded_array( array $value ) {
		$encoded = wp_json_encode( $value );
		if ( strlen( $encoded ) <= 100000 ) {
			return $value;
		}
		return array( 'truncated' => true, 'summary' => substr( wp_strip_all_tags( $encoded ), 0, 90000 ) );
	}
}
