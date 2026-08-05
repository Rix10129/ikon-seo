<?php

defined( 'ABSPATH' ) || exit;

/**
 * Search Impact Monitoring and Outcome Attribution.
 *
 * This component reads stored first-party evidence and privacy-preserving
 * attribution records. It does not alter, publish, redirect, noindex, merge,
 * or otherwise modify public content. Outcome labels are associations, not
 * claims that an SEO release caused the measured change.
 */
final class Ikon_SEO_Search_Impact {
	const CACHE_KEY = 'ikon_seo_search_impact_report_v1';
	const CRON_HOOK = 'ikon_seo_search_impact_monitoring';

	private $publishing_readiness;
	private $search_intelligence;
	private $analytics;
	private $experiments_claims_revenue;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Publishing_Readiness $publishing_readiness,
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Analytics $analytics,
		Ikon_SEO_Experiments_Claims_Revenue $experiments_claims_revenue,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->publishing_readiness = $publishing_readiness;
		$this->search_intelligence = $search_intelligence;
		$this->analytics = $analytics;
		$this->experiments_claims_revenue = $experiments_claims_revenue;
		$this->history = $history;
		$this->logger = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'run_due_measurements' ) );
	}

	public function studies_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_impact_studies';
	}

	public function measurements_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_impact_measurements';
	}

	public function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_impact_events';
	}

	public function status() {
		global $wpdb;
		$ready = $this->tables_ready();
		$counts = array();
		if ( $ready ) {
			foreach ( array( 'baseline_pending', 'monitoring', 'ready_for_assessment', 'assessed', 'acknowledged', 'blocked', 'archived' ) as $status ) {
				$counts[ $status ] = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->studies_table()} WHERE status=%s", $status ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}
		return array(
			'database_ready' => $ready,
			'counts' => $counts,
			'checkpoints' => array( 7, 28, 56, 90 ),
			'causal_claims' => false,
			'automatic_live_changes' => false,
			'sources' => array( 'google_search_console_stored_evidence', 'google_analytics_stored_evidence', 'privacy_preserving_revenue_events', 'manual_confounders' ),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'create_study':
				return $this->create_study( absint( $payload['release_id'] ?? 0 ), (array) ( $payload['plan'] ?? array() ), $user_id );
			case 'capture_baseline':
				return $this->capture_baseline( absint( $payload['study_id'] ?? 0 ), $user_id, ! empty( $payload['refresh'] ) );
			case 'capture_checkpoint':
				return $this->capture_checkpoint( absint( $payload['study_id'] ?? 0 ), absint( $payload['checkpoint_days'] ?? 0 ), $user_id, ! empty( $payload['refresh'] ) );
			case 'add_confounder':
				return $this->add_confounder( absint( $payload['study_id'] ?? 0 ), (array) ( $payload['confounder'] ?? array() ), $user_id );
			case 'assess':
				return $this->assess( absint( $payload['study_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'acknowledge':
				return $this->acknowledge( absint( $payload['study_id'] ?? 0 ), sanitize_key( $payload['decision'] ?? '' ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'block':
				return $this->block( absint( $payload['study_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'unblock':
				return $this->unblock( absint( $payload['study_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'read':
			default:
				return $this->report( array( 'limit' => absint( $payload['limit'] ?? 100 ), 'status' => sanitize_key( $payload['status'] ?? '' ) ), false );
		}
	}

	public function report( array $args = array(), $refresh = false ) {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'limit' => 100, 'status' => '' ) );
		$limit = max( 10, min( 250, absint( $args['limit'] ) ) );
		$status_filter = sanitize_key( $args['status'] );
		$cache_version = max( 1, absint( get_option( 'ikon_seo_search_impact_cache_version', 1 ) ) );
		$cache_key = self::CACHE_KEY . '_' . $cache_version . '_' . md5( wp_json_encode( array( $limit, $status_filter ) ) );
		if ( $refresh ) {
			delete_transient( $cache_key );
		} else {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$status = $this->status();
		if ( empty( $status['database_ready'] ) ) {
			return array(
				'status' => $status,
				'studies' => array(),
				'eligible_releases' => array(),
				'limitations' => array( 'Update or reactivate Ikon SEO to create the v1.12.0 Search Impact tables.' ),
			);
		}

		$where = '';
		$params = array();
		if ( $status_filter ) {
			$where = ' WHERE status=%s';
			$params[] = $status_filter;
		}
		$query = "SELECT * FROM {$this->studies_table()}{$where} ORDER BY FIELD(status,'blocked','baseline_pending','ready_for_assessment','monitoring','assessed','acknowledged','archived'), updated_at DESC LIMIT %d"; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$params[] = $limit;
		$rows = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$studies = array_map( array( $this, 'format_study' ), $rows ?: array() );
		$linked = array();
		foreach ( $studies as $study ) {
			$linked[ absint( $study['release_id'] ) ] = true;
		}

		$eligible = array();
		$publishing = $this->publishing_readiness->report( array( 'limit' => 250 ), false );
		foreach ( (array) ( $publishing['releases'] ?? array() ) as $release ) {
			if ( empty( $linked[ absint( $release['id'] ) ] ) && ! empty( $release['published_at'] ) && ! in_array( $release['status'], array( 'cancelled' ), true ) ) {
				$eligible[] = $release;
			}
		}

		$summary = array(
			'active' => 0,
			'baseline_pending' => 0,
			'monitoring' => 0,
			'ready_for_assessment' => 0,
			'assessed' => 0,
			'acknowledged' => 0,
			'blocked' => 0,
			'positive_signal' => 0,
			'negative_signal' => 0,
			'neutral_signal' => 0,
			'inconclusive' => 0,
		);
		foreach ( $studies as $study ) {
			if ( ! in_array( $study['status'], array( 'acknowledged', 'archived' ), true ) ) {
				$summary['active']++;
			}
			if ( isset( $summary[ $study['status'] ] ) ) {
				$summary[ $study['status'] ]++;
			}
			if ( isset( $summary[ $study['outcome'] ] ) ) {
				$summary[ $study['outcome'] ]++;
			}
		}

		$result = array(
			'generated_at' => current_time( 'mysql', true ),
			'status' => $status,
			'summary' => $summary,
			'studies' => $studies,
			'eligible_releases' => $eligible,
			'methodology' => array(
				'Baseline and post-launch measurements use stored first-party evidence with explicit source periods and coverage notes.',
				'Optional comparison-page movement is subtracted from target-page movement when both measurements are usable.',
				'Confidence is reduced by missing sources, small samples, overlapping measurement windows, stale evidence and recorded confounders.',
				'Outcome labels describe an association after a release; they do not prove the release caused the result.',
			),
			'safety' => array(
				'No command publishes, edits, redirects, deletes, noindexes, merges, or submits a page for indexing.',
				'No target or success metric is invented when connected evidence is unavailable.',
				'Negative results produce review recommendations only; reversals require a separate human publishing decision.',
			),
		);
		set_transient( $cache_key, $result, 5 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function create_study( $release_id, array $plan = array(), $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_impact_manage', __( 'Only an administrator or SEO measurement manager can create an impact study.', 'ikon-seo' ) );
		}
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_impact_tables', __( 'Search Impact database tables are not ready.', 'ikon-seo' ) );
		}
		$release = $this->publishing_readiness->get_release( $release_id );
		if ( ! $release || empty( $release['published_at'] ) || empty( $release['target_url'] ) ) {
			return new WP_Error( 'ikon_seo_impact_published_release', __( 'A recorded manual publication is required before creating an impact study.', 'ikon-seo' ) );
		}
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->studies_table()} WHERE release_id=%d LIMIT 1", $release_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $existing ) {
			return new WP_Error( 'ikon_seo_impact_exists', __( 'An impact study already exists for this publishing release.', 'ikon-seo' ) );
		}
		$allowed_metrics = array( 'clicks', 'impressions', 'ctr', 'position', 'sessions', 'active_users', 'views', 'key_events', 'qualified_leads', 'customers', 'revenue' );
		$primary_metric = sanitize_key( $plan['primary_metric'] ?? 'clicks' );
		if ( ! in_array( $primary_metric, $allowed_metrics, true ) ) {
			$primary_metric = 'clicks';
		}
		$comparison_url = esc_url_raw( $plan['comparison_url'] ?? '' );
		if ( $comparison_url && ! $this->is_same_site_url( $comparison_url ) ) {
			return new WP_Error( 'ikon_seo_impact_comparison_url', __( 'The comparison URL must belong to this WordPress website.', 'ikon-seo' ) );
		}
		if ( $comparison_url && $this->url_hash( $comparison_url ) === $this->url_hash( $release['target_url'] ) ) {
			return new WP_Error( 'ikon_seo_impact_comparison_same', __( 'The comparison URL must be different from the published target URL.', 'ikon-seo' ) );
		}
		$settings = Ikon_SEO_Plugin::settings();
		$baseline_days = max( 7, min( 90, absint( $plan['baseline_days'] ?? ( $settings['impact_default_baseline_days'] ?? 28 ) ) ) );
		$evaluation_days = max( 7, min( 180, absint( $plan['evaluation_days'] ?? ( $settings['impact_default_evaluation_days'] ?? 28 ) ) ) );
		$now = current_time( 'mysql', true );
		$wpdb->insert(
			$this->studies_table(),
			array(
				'release_id' => absint( $release_id ),
				'live_post_id' => absint( $release['live_post_id'] ?? 0 ),
				'target_url' => esc_url_raw( $release['target_url'] ),
				'target_hash' => $this->url_hash( $release['target_url'] ),
				'comparison_url' => $comparison_url,
				'comparison_hash' => $comparison_url ? $this->url_hash( $comparison_url ) : '',
				'primary_metric' => $primary_metric,
				'baseline_days' => $baseline_days,
				'evaluation_days' => $evaluation_days,
				'status' => 'baseline_pending',
				'baseline_measurement_id' => 0,
				'latest_measurement_id' => 0,
				'confidence' => 'low',
				'outcome' => 'inconclusive',
				'adjusted_change_percent' => null,
				'assessment_json' => wp_json_encode( array() ),
				'blocked_reason' => '',
				'owner_id' => absint( $user_id ),
				'created_by' => absint( $user_id ),
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
		if ( ! $wpdb->insert_id ) {
			return new WP_Error( 'ikon_seo_impact_store', __( 'The impact study could not be stored.', 'ikon-seo' ) );
		}
		$study_id = absint( $wpdb->insert_id );
		$this->event( $study_id, 'study_created', 'Search impact study created. No causal conclusion has been made.', array( 'release_id' => $release_id, 'primary_metric' => $primary_metric ), $user_id );
		$this->record_history( 'measurement', 'Search impact study created', sprintf( 'Study #%d was created for published release #%d.', $study_id, $release_id ), $study_id, $user_id, absint( $release['live_post_id'] ?? 0 ) );
		$this->capture_baseline( $study_id, $user_id, false );
		$this->clear_cache();
		return $this->get_study( $study_id );
	}

	public function capture_baseline( $study_id, $user_id = 0, $refresh = false ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_impact_manage', __( 'Only an administrator or SEO measurement manager can capture a baseline.', 'ikon-seo' ) );
		}
		$study = $this->get_study( $study_id, true );
		if ( ! $study || 'blocked' === $study['status'] || 'archived' === $study['status'] ) {
			return new WP_Error( 'ikon_seo_impact_baseline_state', __( 'This impact study cannot capture a baseline.', 'ikon-seo' ) );
		}
		if ( ! empty( $study['baseline_measurement_id'] ) && ! $refresh ) {
			return $this->get_study( $study_id );
		}
		$release = $this->publishing_readiness->get_release( absint( $study['release_id'] ) );
		if ( ! $release || empty( $release['published_at'] ) ) {
			return new WP_Error( 'ikon_seo_impact_release_missing', __( 'The linked publication record is unavailable.', 'ikon-seo' ) );
		}
		$published_ts = strtotime( $release['published_at'] . ' UTC' );
		$period_end = gmdate( 'Y-m-d', $published_ts - DAY_IN_SECONDS );
		$period_start = gmdate( 'Y-m-d', strtotime( $period_end . ' -' . ( max( 7, absint( $study['baseline_days'] ) ) - 1 ) . ' days' ) );
		$measurement_id = $this->store_measurement( $study, 'baseline', 0, $period_start, $period_end, $user_id );
		if ( is_wp_error( $measurement_id ) ) {
			return $measurement_id;
		}
		$measurement = $this->get_measurement( $measurement_id );
		$latest_post = $this->latest_post_measurement( $study_id );
		$next_status = $latest_post && absint( $latest_post['checkpoint_days'] ) >= absint( $study['evaluation_days'] ) ? 'ready_for_assessment' : 'monitoring';
		$wpdb->update(
			$this->studies_table(),
			array(
				'status' => $next_status,
				'baseline_measurement_id' => $measurement_id,
				'latest_measurement_id' => $latest_post ? absint( $latest_post['id'] ) : $measurement_id,
				'confidence' => sanitize_key( $measurement['confidence'] ?? 'low' ),
				'outcome' => 'inconclusive',
				'adjusted_change_percent' => null,
				'assessment_json' => wp_json_encode( array() ),
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $study_id )
		);
		$this->event( $study_id, 'baseline_captured', 'Pre-launch baseline captured from available stored evidence.', array( 'measurement_id' => $measurement_id, 'quality_score' => $measurement['quality_score'] ?? 0 ), $user_id );
		$this->clear_cache();
		return $this->get_study( $study_id );
	}

	public function capture_checkpoint( $study_id, $checkpoint_days = 0, $user_id = 0, $refresh = false ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_impact_manage', __( 'Only an administrator or SEO measurement manager can capture an impact checkpoint.', 'ikon-seo' ) );
		}
		$study = $this->get_study( $study_id, true );
		if ( ! $study || ! in_array( $study['status'], array( 'monitoring', 'ready_for_assessment', 'assessed', 'acknowledged' ), true ) ) {
			return new WP_Error( 'ikon_seo_impact_checkpoint_state', __( 'This study is not available for post-launch measurement.', 'ikon-seo' ) );
		}
		$release = $this->publishing_readiness->get_release( absint( $study['release_id'] ) );
		if ( ! $release || empty( $release['published_at'] ) ) {
			return new WP_Error( 'ikon_seo_impact_release_missing', __( 'The linked publication record is unavailable.', 'ikon-seo' ) );
		}
		$published_ts = strtotime( $release['published_at'] . ' UTC' );
		$elapsed_days = max( 0, (int) floor( ( time() - $published_ts ) / DAY_IN_SECONDS ) );
		$allowed = array( 7, 28, 56, 90 );
		if ( ! $checkpoint_days ) {
			foreach ( $allowed as $candidate ) {
				if ( $candidate <= $elapsed_days && ! $this->checkpoint_exists( $study_id, $candidate ) ) {
					$checkpoint_days = $candidate;
				}
			}
		}
		if ( ! in_array( $checkpoint_days, $allowed, true ) ) {
			return new WP_Error( 'ikon_seo_impact_checkpoint', __( 'Choose a supported 7, 28, 56 or 90 day checkpoint.', 'ikon-seo' ) );
		}
		if ( $elapsed_days < $checkpoint_days ) {
			return new WP_Error( 'ikon_seo_impact_checkpoint_early', sprintf( __( 'The %d-day checkpoint is not due yet.', 'ikon-seo' ), $checkpoint_days ) );
		}
		$existing = $this->measurement_for_checkpoint( $study_id, $checkpoint_days );
		if ( $existing && ! $refresh ) {
			return $this->get_study( $study_id );
		}
		$period_end = gmdate( 'Y-m-d', $published_ts + ( $checkpoint_days * DAY_IN_SECONDS ) );
		if ( strtotime( $period_end . ' 23:59:59 UTC' ) > time() ) {
			$period_end = gmdate( 'Y-m-d' );
		}
		$period_start = gmdate( 'Y-m-d', $published_ts );
		$measurement_id = $this->store_measurement( $study, 'post_launch', $checkpoint_days, $period_start, $period_end, $user_id );
		if ( is_wp_error( $measurement_id ) ) {
			return $measurement_id;
		}
		$measurement = $this->get_measurement( $measurement_id );
		$status = $checkpoint_days >= absint( $study['evaluation_days'] ) ? 'ready_for_assessment' : 'monitoring';
		$assessment_invalidated = in_array( $study['status'], array( 'assessed', 'acknowledged' ), true );
		$wpdb->update(
			$this->studies_table(),
			array(
				'status' => $status,
				'latest_measurement_id' => $measurement_id,
				'confidence' => sanitize_key( $measurement['confidence'] ?? 'low' ),
				'outcome' => 'inconclusive',
				'adjusted_change_percent' => null,
				'assessment_json' => wp_json_encode( array() ),
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $study_id )
		);
		if ( $assessment_invalidated ) {
			$this->event( $study_id, 'assessment_invalidated', 'A newer or refreshed measurement invalidated the previous assessment and human decision.', array( 'checkpoint_days' => $checkpoint_days ), $user_id );
		}
		$this->event( $study_id, 'checkpoint_captured', sprintf( '%d-day post-launch checkpoint captured.', $checkpoint_days ), array( 'measurement_id' => $measurement_id, 'quality_score' => $measurement['quality_score'] ?? 0 ), $user_id );
		$this->clear_cache();
		return $this->get_study( $study_id );
	}

	public function add_confounder( $study_id, array $data, $user_id = 0 ) {
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_impact_manage', __( 'Only an administrator or SEO measurement manager can record a confounder.', 'ikon-seo' ) );
		}
		$study = $this->get_study( $study_id, true );
		if ( ! $study ) {
			return new WP_Error( 'ikon_seo_impact_study', __( 'The impact study was not found.', 'ikon-seo' ) );
		}
		$type = sanitize_key( $data['type'] ?? 'other' );
		$allowed = array( 'seasonality', 'algorithm_update', 'sitewide_change', 'tracking_change', 'paid_campaign', 'pricing_change', 'availability_change', 'competitor_change', 'other' );
		if ( ! in_array( $type, $allowed, true ) ) {
			$type = 'other';
		}
		$notes = sanitize_textarea_field( $data['notes'] ?? '' );
		if ( ! $notes ) {
			return new WP_Error( 'ikon_seo_impact_confounder_notes', __( 'Describe the confounding event or change.', 'ikon-seo' ) );
		}
		$occurred_at = sanitize_text_field( $data['occurred_at'] ?? current_time( 'mysql', true ) );
		$direction = sanitize_key( $data['direction'] ?? 'unknown' );
		if ( ! in_array( $direction, array( 'positive', 'negative', 'mixed', 'unknown' ), true ) ) {
			$direction = 'unknown';
		}
		$this->event( $study_id, 'confounder', $notes, array( 'type' => $type, 'direction' => $direction, 'occurred_at' => $occurred_at ), $user_id );
		$this->clear_cache();
		return $this->get_study( $study_id );
	}

	public function assess( $study_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_approve( $user_id ) ) {
			return new WP_Error( 'ikon_seo_impact_approve', __( 'Only an administrator or publishing approver can assess an impact study.', 'ikon-seo' ) );
		}
		$study = $this->get_study( $study_id, true );
		if ( ! $study || ! in_array( $study['status'], array( 'ready_for_assessment', 'assessed' ), true ) ) {
			return new WP_Error( 'ikon_seo_impact_assessment_state', __( 'The configured evaluation checkpoint must be captured before assessment.', 'ikon-seo' ) );
		}
		$baseline = $this->get_measurement( absint( $study['baseline_measurement_id'] ) );
		$latest = $this->get_measurement( absint( $study['latest_measurement_id'] ) );
		if ( ! $baseline || ! $latest || 'baseline' !== $baseline['checkpoint_type'] || 'post_launch' !== $latest['checkpoint_type'] ) {
			return new WP_Error( 'ikon_seo_impact_measurements', __( 'A baseline and a post-launch measurement are required.', 'ikon-seo' ) );
		}
		if ( absint( $latest['checkpoint_days'] ) < absint( $study['evaluation_days'] ) ) {
			return new WP_Error( 'ikon_seo_impact_evaluation_window', __( 'The configured evaluation window has not been measured yet.', 'ikon-seo' ) );
		}
		$metric = sanitize_key( $study['primary_metric'] );
		$before = (float) ( $baseline['metrics'][ $metric ] ?? 0 );
		$after = (float) ( $latest['metrics'][ $metric ] ?? 0 );
		$raw_change = $this->metric_change_percent( $metric, $before, $after );
		$comparison_change = null;
		$adjusted = $raw_change;
		if ( ! empty( $study['comparison_url'] ) && ! empty( $baseline['comparison_metrics'] ) && ! empty( $latest['comparison_metrics'] ) ) {
			$c_before = (float) ( $baseline['comparison_metrics'][ $metric ] ?? 0 );
			$c_after = (float) ( $latest['comparison_metrics'][ $metric ] ?? 0 );
			if ( 0.0 !== $c_before || 0.0 !== $c_after ) {
				$comparison_change = $this->metric_change_percent( $metric, $c_before, $c_after );
				$adjusted = $raw_change - $comparison_change;
			}
		}
		$confounders = $this->confounders( $study_id );
		$quality_score = min( absint( $baseline['quality_score'] ), absint( $latest['quality_score'] ) );
		$quality_score = max( 0, $quality_score - min( 30, count( $confounders ) * 8 ) );
		$confidence = $quality_score >= 80 ? 'high' : ( $quality_score >= 55 ? 'medium' : 'low' );
		$threshold = max( 1, min( 100, (float) ( Ikon_SEO_Plugin::settings()['impact_change_threshold_percent'] ?? 10 ) ) );
		$outcome = 'inconclusive';
		if ( 'low' !== $confidence && ( 0.0 !== $before || 0.0 !== $after ) ) {
			$outcome = $adjusted >= $threshold ? 'positive_signal' : ( $adjusted <= -$threshold ? 'negative_signal' : 'neutral_signal' );
		}
		$assessment = array(
			'metric' => $metric,
			'baseline_value' => $before,
			'post_launch_value' => $after,
			'raw_change_percent' => round( $raw_change, 2 ),
			'comparison_change_percent' => null === $comparison_change ? null : round( $comparison_change, 2 ),
			'adjusted_change_percent' => round( $adjusted, 2 ),
			'quality_score' => $quality_score,
			'confidence' => $confidence,
			'outcome' => $outcome,
			'confounder_count' => count( $confounders ),
			'language' => 'This is an evidence-based association after the release, not proof that the release caused the change.',
			'recommended_next_step' => $this->recommended_next_step( $outcome, $confidence ),
			'notes' => sanitize_textarea_field( $notes ),
			'assessed_at' => current_time( 'mysql', true ),
			'assessed_by' => absint( $user_id ),
		);
		$wpdb->update(
			$this->studies_table(),
			array(
				'status' => 'assessed',
				'confidence' => $confidence,
				'outcome' => $outcome,
				'adjusted_change_percent' => round( $adjusted, 2 ),
				'assessment_json' => wp_json_encode( $assessment ),
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $study_id )
		);
		$this->event( $study_id, 'outcome_assessed', sprintf( 'Outcome assessed as %s with %s confidence.', str_replace( '_', ' ', $outcome ), $confidence ), $assessment, $user_id );
		$this->record_history( 'measurement', 'Search impact outcome assessed', sprintf( 'Study #%d recorded a %s with %s confidence. This is not a causal claim.', $study_id, str_replace( '_', ' ', $outcome ), $confidence ), $study_id, $user_id, absint( $study['live_post_id'] ) );
		$this->clear_cache();
		return $this->get_study( $study_id );
	}

	public function acknowledge( $study_id, $decision, $notes = '', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_approve( $user_id ) ) {
			return new WP_Error( 'ikon_seo_impact_approve', __( 'Only an administrator or publishing approver can acknowledge an impact outcome.', 'ikon-seo' ) );
		}
		$study = $this->get_study( $study_id, true );
		if ( ! $study || 'assessed' !== $study['status'] ) {
			return new WP_Error( 'ikon_seo_impact_ack_state', __( 'Assess the study before recording a human decision.', 'ikon-seo' ) );
		}
		$allowed = array( 'retain', 'expand_carefully', 'continue_monitoring', 'investigate', 'consider_revision', 'no_action' );
		$decision = sanitize_key( $decision );
		if ( ! in_array( $decision, $allowed, true ) ) {
			return new WP_Error( 'ikon_seo_impact_decision', __( 'Choose a supported outcome decision.', 'ikon-seo' ) );
		}
		$assessment = $this->decode_json( $study['assessment_json'] );
		$assessment['human_decision'] = $decision;
		$assessment['decision_notes'] = sanitize_textarea_field( $notes );
		$assessment['acknowledged_by'] = absint( $user_id );
		$assessment['acknowledged_at'] = current_time( 'mysql', true );
		$wpdb->update(
			$this->studies_table(),
			array( 'status' => 'acknowledged', 'assessment_json' => wp_json_encode( $assessment ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $study_id )
		);
		$this->event( $study_id, 'outcome_acknowledged', sprintf( 'Human decision recorded: %s.', str_replace( '_', ' ', $decision ) ), array( 'decision' => $decision, 'notes' => $notes ), $user_id );
		$this->clear_cache();
		return $this->get_study( $study_id );
	}

	public function block( $study_id, $notes, $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_approve( $user_id ) || ! trim( $notes ) ) {
			return new WP_Error( 'ikon_seo_impact_block', __( 'An approver and a reason are required to block a study.', 'ikon-seo' ) );
		}
		$study = $this->get_study( $study_id, true );
		if ( ! $study || 'archived' === $study['status'] ) {
			return new WP_Error( 'ikon_seo_impact_block_state', __( 'This study cannot be blocked.', 'ikon-seo' ) );
		}
		$wpdb->update( $this->studies_table(), array( 'status' => 'blocked', 'blocked_reason' => sanitize_textarea_field( $notes ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $study_id ) );
		$this->event( $study_id, 'blocked', $notes, array(), $user_id );
		$this->clear_cache();
		return $this->get_study( $study_id );
	}

	public function unblock( $study_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_approve( $user_id ) ) {
			return new WP_Error( 'ikon_seo_impact_unblock', __( 'Only an approver can unblock a study.', 'ikon-seo' ) );
		}
		$study = $this->get_study( $study_id, true );
		if ( ! $study || 'blocked' !== $study['status'] ) {
			return new WP_Error( 'ikon_seo_impact_unblock_state', __( 'The study is not blocked.', 'ikon-seo' ) );
		}
		$latest_post = $this->latest_post_measurement( $study_id );
		$status = empty( $study['baseline_measurement_id'] ) ? 'baseline_pending' : ( $latest_post && absint( $latest_post['checkpoint_days'] ) >= absint( $study['evaluation_days'] ) ? 'ready_for_assessment' : 'monitoring' );
		$wpdb->update( $this->studies_table(), array( 'status' => $status, 'blocked_reason' => '', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $study_id ) );
		$this->event( $study_id, 'unblocked', sanitize_textarea_field( $notes ), array( 'restored_status' => $status ), $user_id );
		$this->clear_cache();
		return $this->get_study( $study_id );
	}

	public function run_due_measurements() {
		global $wpdb;
		if ( ! $this->tables_ready() || empty( Ikon_SEO_Plugin::settings()['search_impact_enabled'] ) ) {
			return;
		}
		$rows = $wpdb->get_results( "SELECT id FROM {$this->studies_table()} WHERE status='monitoring' ORDER BY updated_at ASC LIMIT 3", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows ?: array() as $row ) {
			$study = $this->get_study( absint( $row['id'] ), true );
			$release = $study ? $this->publishing_readiness->get_release( absint( $study['release_id'] ) ) : array();
			if ( ! $release || empty( $release['published_at'] ) ) {
				continue;
			}
			$elapsed = max( 0, (int) floor( ( time() - strtotime( $release['published_at'] . ' UTC' ) ) / DAY_IN_SECONDS ) );
			foreach ( array( 7, 28, 56, 90 ) as $checkpoint ) {
				if ( $checkpoint <= $elapsed && ! $this->checkpoint_exists( absint( $study['id'] ), $checkpoint ) ) {
					$this->capture_checkpoint( absint( $study['id'] ), $checkpoint, absint( $study['owner_id'] ), false );
					break;
				}
			}
		}
	}

	public function get_study( $id, $raw = false ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->studies_table()} WHERE id=%d LIMIT 1", absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? ( $raw ? $row : $this->format_study( $row ) ) : array();
	}

	private function format_study( $row ) {
		$study = array(
			'id' => absint( $row['id'] ),
			'release_id' => absint( $row['release_id'] ),
			'live_post_id' => absint( $row['live_post_id'] ),
			'target_url' => esc_url_raw( $row['target_url'] ),
			'comparison_url' => esc_url_raw( $row['comparison_url'] ),
			'primary_metric' => sanitize_key( $row['primary_metric'] ),
			'baseline_days' => absint( $row['baseline_days'] ),
			'evaluation_days' => absint( $row['evaluation_days'] ),
			'status' => sanitize_key( $row['status'] ),
			'baseline_measurement_id' => absint( $row['baseline_measurement_id'] ),
			'latest_measurement_id' => absint( $row['latest_measurement_id'] ),
			'confidence' => sanitize_key( $row['confidence'] ),
			'outcome' => sanitize_key( $row['outcome'] ),
			'adjusted_change_percent' => null === $row['adjusted_change_percent'] ? null : (float) $row['adjusted_change_percent'],
			'assessment' => $this->decode_json( $row['assessment_json'] ),
			'blocked_reason' => sanitize_textarea_field( $row['blocked_reason'] ),
			'owner_id' => absint( $row['owner_id'] ),
			'created_by' => absint( $row['created_by'] ),
			'created_at' => $row['created_at'],
			'updated_at' => $row['updated_at'],
		);
		$study['baseline'] = $study['baseline_measurement_id'] ? $this->get_measurement( $study['baseline_measurement_id'] ) : array();
		$study['latest'] = $study['latest_measurement_id'] ? $this->get_measurement( $study['latest_measurement_id'] ) : array();
		$study['measurements'] = $this->measurements( $study['id'] );
		$study['events'] = $this->events( $study['id'] );
		$study['confounders'] = $this->confounders( $study['id'] );
		$study['title'] = $study['live_post_id'] ? get_the_title( $study['live_post_id'] ) : '';
		$study['edit_url'] = $study['live_post_id'] ? get_edit_post_link( $study['live_post_id'], 'raw' ) : '';
		return $study;
	}

	private function store_measurement( array $study, $checkpoint_type, $checkpoint_days, $period_start, $period_end, $user_id ) {
		global $wpdb;
		$target = $this->collect_metrics( $study['target_url'], $period_start, $period_end );
		$comparison = ! empty( $study['comparison_url'] ) ? $this->collect_metrics( $study['comparison_url'], $period_start, $period_end ) : array();
		$quality = $this->measurement_quality( $target, $comparison, ! empty( $study['comparison_url'] ), $period_start, $period_end, $checkpoint_type );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->measurements_table()} WHERE study_id=%d AND checkpoint_type=%s AND checkpoint_days=%d LIMIT 1", absint( $study['id'] ), sanitize_key( $checkpoint_type ), absint( $checkpoint_days ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$record = array(
			'study_id' => absint( $study['id'] ),
			'checkpoint_type' => sanitize_key( $checkpoint_type ),
			'checkpoint_days' => absint( $checkpoint_days ),
			'period_start' => sanitize_text_field( $period_start ),
			'period_end' => sanitize_text_field( $period_end ),
			'metrics_json' => wp_json_encode( $target['metrics'] ),
			'comparison_metrics_json' => wp_json_encode( $comparison['metrics'] ?? array() ),
			'sources_json' => wp_json_encode( $target['sources'] ),
			'comparison_sources_json' => wp_json_encode( $comparison['sources'] ?? array() ),
			'quality_score' => absint( $quality['score'] ),
			'confidence' => sanitize_key( $quality['confidence'] ),
			'limitations_json' => wp_json_encode( $quality['limitations'] ),
			'created_by' => absint( $user_id ),
			'created_at' => current_time( 'mysql', true ),
		);
		if ( $existing ) {
			$wpdb->update( $this->measurements_table(), $record, array( 'id' => absint( $existing ) ) );
			return absint( $existing );
		}
		$wpdb->insert( $this->measurements_table(), $record );
		return $wpdb->insert_id ? absint( $wpdb->insert_id ) : new WP_Error( 'ikon_seo_impact_measurement_store', __( 'The impact measurement could not be stored.', 'ikon-seo' ) );
	}

	private function collect_metrics( $url, $desired_start, $desired_end ) {
		$metrics = array(
			'clicks' => 0.0,
			'impressions' => 0.0,
			'ctr' => 0.0,
			'position' => 0.0,
			'sessions' => 0.0,
			'active_users' => 0.0,
			'views' => 0.0,
			'key_events' => 0.0,
			'qualified_leads' => 0.0,
			'customers' => 0.0,
			'revenue' => 0.0,
		);
		$sources = array();
		$search = $this->search_metrics( $url, $desired_start, $desired_end );
		if ( $search ) {
			foreach ( array( 'clicks', 'impressions', 'ctr', 'position' ) as $key ) {
				$metrics[ $key ] = (float) ( $search['metrics'][ $key ] ?? 0 );
			}
			$sources['search_console'] = $search['source'];
		}
		$analytics = $this->analytics_metrics( $url, $desired_start, $desired_end );
		if ( $analytics ) {
			foreach ( array( 'sessions', 'active_users', 'views', 'key_events' ) as $key ) {
				$metrics[ $key ] = (float) ( $analytics['metrics'][ $key ] ?? 0 );
			}
			$sources['analytics'] = $analytics['source'];
		}
		$revenue = $this->revenue_metrics( $url, $desired_start, $desired_end );
		foreach ( array( 'qualified_leads', 'customers', 'revenue' ) as $key ) {
			$metrics[ $key ] = (float) ( $revenue['metrics'][ $key ] ?? 0 );
		}
		$sources['revenue_attribution'] = $revenue['source'];
		return array( 'metrics' => $metrics, 'sources' => $sources );
	}

	private function search_metrics( $url, $desired_start, $desired_end ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_search_rows';
		if ( ! $this->table_exists( $table ) ) {
			return array();
		}
		$page_hash = hash( 'sha256', $this->url_key( $url ) );
		$period_end = $wpdb->get_var( $wpdb->prepare( "SELECT period_end FROM {$table} WHERE page_hash=%s AND period_end<=%s GROUP BY period_end ORDER BY ABS(DATEDIFF(period_end,%s)) ASC LIMIT 1", $page_hash, $desired_end, $desired_end ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $period_end ) {
			return array();
		}
		$row = (array) $wpdb->get_row( $wpdb->prepare( "SELECT MIN(period_start) AS period_start, MAX(period_end) AS period_end, SUM(clicks) AS clicks, SUM(impressions) AS impressions, SUM(position*impressions) AS position_weight, MAX(fetched_at) AS fetched_at FROM {$table} WHERE page_hash=%s AND period_end=%s", $page_hash, $period_end ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$impressions = (float) ( $row['impressions'] ?? 0 );
		$clicks = (float) ( $row['clicks'] ?? 0 );
		return array(
			'metrics' => array(
				'clicks' => $clicks,
				'impressions' => $impressions,
				'ctr' => $impressions > 0 ? $clicks / $impressions : 0,
				'position' => $impressions > 0 ? (float) $row['position_weight'] / $impressions : 0,
			),
			'source' => $this->source_meta( 'google_search_console', $desired_start, $desired_end, $row['period_start'] ?? '', $row['period_end'] ?? '', $row['fetched_at'] ?? '', $impressions ),
		);
	}

	private function analytics_metrics( $url, $desired_start, $desired_end ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_analytics_pages';
		if ( ! $this->table_exists( $table ) ) {
			return array();
		}
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$path = '/' . ltrim( $path ?: '/', '/' );
		$hashes = array_unique( array( hash( 'sha256', $path ), hash( 'sha256', untrailingslashit( $path ) ), hash( 'sha256', trailingslashit( $path ) ) ) );
		$placeholders = implode( ',', array_fill( 0, count( $hashes ), '%s' ) );
		$params = array_merge( $hashes, array( $desired_end, $desired_end ) );
		$period_end = $wpdb->get_var( $wpdb->prepare( "SELECT period_end FROM {$table} WHERE page_hash IN ({$placeholders}) AND period_end<=%s GROUP BY period_end ORDER BY ABS(DATEDIFF(period_end,%s)) ASC LIMIT 1", $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $period_end ) {
			return array();
		}
		$params = array_merge( $hashes, array( $period_end ) );
		$row = (array) $wpdb->get_row( $wpdb->prepare( "SELECT MIN(period_start) AS period_start, MAX(period_end) AS period_end, SUM(sessions) AS sessions, SUM(active_users) AS active_users, SUM(views) AS views, SUM(key_events) AS key_events, MAX(fetched_at) AS fetched_at FROM {$table} WHERE page_hash IN ({$placeholders}) AND period_end=%s", $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array(
			'metrics' => array(
				'sessions' => (float) ( $row['sessions'] ?? 0 ),
				'active_users' => (float) ( $row['active_users'] ?? 0 ),
				'views' => (float) ( $row['views'] ?? 0 ),
				'key_events' => (float) ( $row['key_events'] ?? 0 ),
			),
			'source' => $this->source_meta( 'google_analytics', $desired_start, $desired_end, $row['period_start'] ?? '', $row['period_end'] ?? '', $row['fetched_at'] ?? '', (float) ( $row['sessions'] ?? 0 ) ),
		);
	}

	private function revenue_metrics( $url, $period_start, $period_end ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_revenue_events';
		if ( ! $this->table_exists( $table ) ) {
			return array( 'metrics' => array(), 'source' => $this->source_meta( 'revenue_attribution', $period_start, $period_end, '', '', '', 0, false ) );
		}
		$start = $period_start . ' 00:00:00';
		$end = $period_end . ' 23:59:59';
		$post_id = url_to_postid( $url );
		$settings = Ikon_SEO_Plugin::settings();
		$currency = strtoupper( sanitize_text_field( $settings['revenue_default_currency'] ?? $settings['currency'] ?? 'USD' ) );
		$row = (array) $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS events, COALESCE(SUM(qualified),0) AS qualified, COALESCE(SUM(customer),0) AS customers, COALESCE(SUM(value),0) AS revenue, MAX(created_at) AS fetched_at FROM {$table} WHERE occurred_at BETWEEN %s AND %s AND currency=%s AND (landing_url=%s OR (%d>0 AND post_id=%d))",
				$start,
				$end,
				$currency,
				$url,
				$post_id,
				$post_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$source = $this->source_meta( 'revenue_attribution', $period_start, $period_end, $period_start, $period_end, $row['fetched_at'] ?? '', (float) ( $row['events'] ?? 0 ), true );
		$source['currency'] = $currency;
		return array(
			'metrics' => array( 'qualified_leads' => (float) ( $row['qualified'] ?? 0 ), 'customers' => (float) ( $row['customers'] ?? 0 ), 'revenue' => (float) ( $row['revenue'] ?? 0 ) ),
			'source' => $source,
		);
	}

	private function source_meta( $source, $desired_start, $desired_end, $actual_start, $actual_end, $fetched_at, $observations, $available = true ) {
		$desired_days = $this->period_days( $desired_start, $desired_end );
		$actual_days = $actual_start && $actual_end ? $this->period_days( $actual_start, $actual_end ) : 0;
		$overlap = $actual_start && $actual_end ? $this->overlap_days( $desired_start, $desired_end, $actual_start, $actual_end ) : 0;
		return array(
			'source' => sanitize_key( $source ),
			'available' => (bool) $available && (bool) $actual_start,
			'desired_period' => array( 'start' => $desired_start, 'end' => $desired_end, 'days' => $desired_days ),
			'actual_period' => array( 'start' => $actual_start, 'end' => $actual_end, 'days' => $actual_days ),
			'coverage_percent' => $desired_days ? min( 100, round( 100 * $overlap / $desired_days, 2 ) ) : 0,
			'observations' => (float) $observations,
			'fetched_at' => $fetched_at,
			'stale_days' => $fetched_at ? max( 0, (int) floor( ( time() - strtotime( $fetched_at . ' UTC' ) ) / DAY_IN_SECONDS ) ) : null,
		);
	}

	private function measurement_quality( array $target, array $comparison, $comparison_required, $period_start, $period_end, $checkpoint_type ) {
		$limitations = array();
		$score = 100;
		$available = 0;
		foreach ( array( 'search_console', 'analytics', 'revenue_attribution' ) as $source ) {
			$meta = (array) ( $target['sources'][ $source ] ?? array() );
			if ( ! empty( $meta['available'] ) ) {
				$available++;
				if ( (float) ( $meta['coverage_percent'] ?? 0 ) < 80 ) {
					$score -= 10;
					$limitations[] = sprintf( '%s covers less than 80%% of the desired period.', str_replace( '_', ' ', $source ) );
				}
				if ( null !== ( $meta['stale_days'] ?? null ) && absint( $meta['stale_days'] ) > 35 ) {
					$score -= 10;
					$limitations[] = sprintf( '%s evidence is more than 35 days old.', str_replace( '_', ' ', $source ) );
				}
			} else {
				$score -= 15;
				$limitations[] = sprintf( '%s evidence is unavailable.', str_replace( '_', ' ', $source ) );
			}
		}
		$observations = (float) ( $target['metrics']['impressions'] ?? 0 ) + (float) ( $target['metrics']['sessions'] ?? 0 );
		$minimum = max( 10, absint( Ikon_SEO_Plugin::settings()['impact_minimum_observations'] ?? 100 ) );
		if ( $observations < $minimum ) {
			$score -= 25;
			$limitations[] = 'The combined impressions and sessions sample is below the configured minimum.';
		}
		if ( $comparison_required ) {
			$comparison_available = 0;
			foreach ( (array) ( $comparison['sources'] ?? array() ) as $meta ) {
				if ( ! empty( $meta['available'] ) ) {
					$comparison_available++;
				}
			}
			if ( ! $comparison_available ) {
				$score -= 20;
				$limitations[] = 'The configured comparison page has no usable evidence for this period.';
			}
		} else {
			$score -= 8;
			$limitations[] = 'No comparison page was configured, so sitewide and seasonal movement is harder to separate.';
		}
		if ( 'post_launch' === $checkpoint_type ) {
			foreach ( (array) ( $target['sources'] ?? array() ) as $meta ) {
				if ( ! empty( $meta['actual_period']['start'] ) && $meta['actual_period']['start'] < $period_start ) {
					$score -= 12;
					$limitations[] = 'At least one post-launch source period includes pre-launch days.';
					break;
				}
			}
		}
		if ( 0 === $available ) {
			$score = 0;
		}
		$score = max( 0, min( 100, $score ) );
		return array( 'score' => $score, 'confidence' => $score >= 80 ? 'high' : ( $score >= 55 ? 'medium' : 'low' ), 'limitations' => array_values( array_unique( $limitations ) ) );
	}

	private function get_measurement( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->measurements_table()} WHERE id=%d LIMIT 1", absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? $this->format_measurement( $row ) : array();
	}

	private function measurements( $study_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->measurements_table()} WHERE study_id=%d ORDER BY checkpoint_days ASC,id ASC", absint( $study_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( array( $this, 'format_measurement' ), $rows ?: array() );
	}

	private function format_measurement( $row ) {
		return array(
			'id' => absint( $row['id'] ),
			'study_id' => absint( $row['study_id'] ),
			'checkpoint_type' => sanitize_key( $row['checkpoint_type'] ),
			'checkpoint_days' => absint( $row['checkpoint_days'] ),
			'period_start' => $row['period_start'],
			'period_end' => $row['period_end'],
			'metrics' => $this->decode_json( $row['metrics_json'] ),
			'comparison_metrics' => $this->decode_json( $row['comparison_metrics_json'] ),
			'sources' => $this->decode_json( $row['sources_json'] ),
			'comparison_sources' => $this->decode_json( $row['comparison_sources_json'] ),
			'quality_score' => absint( $row['quality_score'] ),
			'confidence' => sanitize_key( $row['confidence'] ),
			'limitations' => $this->decode_json( $row['limitations_json'] ),
			'created_by' => absint( $row['created_by'] ),
			'created_at' => $row['created_at'],
		);
	}

	private function latest_post_measurement( $study_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->measurements_table()} WHERE study_id=%d AND checkpoint_type='post_launch' ORDER BY checkpoint_days DESC,id DESC LIMIT 1", absint( $study_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? $this->format_measurement( $row ) : array();
	}

	private function measurement_for_checkpoint( $study_id, $checkpoint_days ) {
		global $wpdb;
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->measurements_table()} WHERE study_id=%d AND checkpoint_type='post_launch' AND checkpoint_days=%d LIMIT 1", absint( $study_id ), absint( $checkpoint_days ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $id ? $this->get_measurement( absint( $id ) ) : array();
	}

	private function checkpoint_exists( $study_id, $checkpoint_days ) {
		return ! empty( $this->measurement_for_checkpoint( $study_id, $checkpoint_days ) );
	}

	private function events( $study_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->events_table()} WHERE study_id=%d ORDER BY id DESC LIMIT 100", absint( $study_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( array( $this, 'format_event' ), $rows ?: array() );
	}

	private function confounders( $study_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->events_table()} WHERE study_id=%d AND event_type='confounder' ORDER BY id DESC", absint( $study_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( array( $this, 'format_event' ), $rows ?: array() );
	}

	private function format_event( $row ) {
		return array(
			'id' => absint( $row['id'] ),
			'type' => sanitize_key( $row['event_type'] ),
			'actor_id' => absint( $row['actor_id'] ),
			'notes' => sanitize_textarea_field( $row['notes'] ),
			'payload' => $this->decode_json( $row['payload_json'] ),
			'created_at' => $row['created_at'],
		);
	}

	private function event( $study_id, $type, $notes, array $payload, $user_id ) {
		global $wpdb;
		$wpdb->insert(
			$this->events_table(),
			array(
				'study_id' => absint( $study_id ),
				'event_type' => sanitize_key( $type ),
				'actor_id' => absint( $user_id ),
				'notes' => sanitize_textarea_field( $notes ),
				'payload_json' => wp_json_encode( $payload ),
				'created_at' => current_time( 'mysql', true ),
			)
		);
	}

	private function metric_change_percent( $metric, $before, $after ) {
		$before = (float) $before;
		$after = (float) $after;
		if ( 'position' === $metric ) {
			return $before > 0 ? ( ( $before - $after ) / $before ) * 100 : 0.0;
		}
		return 0.0 === $before ? ( $after > 0 ? 100.0 : 0.0 ) : ( ( $after - $before ) / abs( $before ) ) * 100;
	}

	private function recommended_next_step( $outcome, $confidence ) {
		if ( 'low' === $confidence || 'inconclusive' === $outcome ) {
			return 'Continue monitoring, improve evidence coverage and review confounders before making a content decision.';
		}
		if ( 'positive_signal' === $outcome ) {
			return 'Retain the release and consider carefully applying the validated pattern to another approved opportunity.';
		}
		if ( 'negative_signal' === $outcome ) {
			return 'Investigate query intent, indexing, internal links, competitor movement and tracking before considering a controlled revision.';
		}
		return 'Retain the page, continue monitoring and prioritise higher-impact opportunities.';
	}

	private function url_key( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return $host . untrailingslashit( '/' . ltrim( $path, '/' ) );
	}

	private function url_hash( $url ) {
		return hash( 'sha256', $this->url_key( $url ) );
	}

	private function period_days( $start, $end ) {
		if ( ! $start || ! $end ) {
			return 0;
		}
		return max( 0, (int) floor( ( strtotime( $end ) - strtotime( $start ) ) / DAY_IN_SECONDS ) + 1 );
	}

	private function overlap_days( $a_start, $a_end, $b_start, $b_end ) {
		$start = max( strtotime( $a_start ), strtotime( $b_start ) );
		$end = min( strtotime( $a_end ), strtotime( $b_end ) );
		return $end < $start ? 0 : (int) floor( ( $end - $start ) / DAY_IN_SECONDS ) + 1;
	}

	private function decode_json( $value ) {
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function is_same_site_url( $url ) {
		if ( ! wp_http_validate_url( $url ) ) {
			return false;
		}
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$url_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		return $home_host && $url_host && $home_host === $url_host && in_array( $scheme, array( 'http', 'https' ), true );
	}

	private function can_manage( $user_id ) {
		$user_id = absint( $user_id );
		return $user_id && ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'publish_posts' ) || user_can( $user_id, 'publish_pages' ) );
	}

	private function can_approve( $user_id ) {
		$user_id = absint( $user_id );
		return $user_id && user_can( $user_id, 'manage_options' );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	private function tables_ready() {
		return $this->table_exists( $this->studies_table() ) && $this->table_exists( $this->measurements_table() ) && $this->table_exists( $this->events_table() );
	}

	private function clear_cache() {
		$version = max( 1, absint( get_option( 'ikon_seo_search_impact_cache_version', 1 ) ) );
		update_option( 'ikon_seo_search_impact_cache_version', $version + 1, false );
	}

	private function record_history( $category, $title, $summary, $study_id, $user_id, $post_id = 0 ) {
		$this->history->add( array( 'category' => $category, 'status' => 'open', 'title' => $title, 'summary' => $summary, 'details' => array( 'impact_study_id' => absint( $study_id ) ), 'related_post_id' => absint( $post_id ) ), 'search_impact', $user_id );
		$this->logger->log( 'search_impact', 'success', $summary, absint( $post_id ), absint( $study_id ) );
	}
}
