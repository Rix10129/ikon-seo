<?php

defined( 'ABSPATH' ) || exit;

/**
 * Controlled experiments, content claim records and privacy-preserving revenue attribution.
 */
final class Ikon_SEO_Experiments_Claims_Revenue {
	const CRON_HOOK = 'ikon_seo_experiments_claims_weekly';

	private $search_intelligence;
	private $analytics;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Analytics $analytics,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->search_intelligence = $search_intelligence;
		$this->analytics           = $analytics;
		$this->history             = $history;
		$this->logger              = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_review' ) );
	}

	public function experiments_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_experiments';
	}

	public function measurements_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_experiment_measurements';
	}

	public function claims_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_claims';
	}

	public function revenue_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_revenue_events';
	}

	public function scheduled_review() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['experiments_claims_revenue_enabled'] ) ) {
			return;
		}
		$this->mark_overdue_claims();
		$this->refresh_experiment_states();
		$this->cleanup();
	}

	public function status() {
		global $wpdb;
		$experiments = $this->experiments_table();
		$measurements = $this->measurements_table();
		$claims = $this->claims_table();
		$revenue = $this->revenue_table();
		$now = current_time( 'mysql', true );

		if ( ! $this->tables_ready() ) {
			return array(
				'enabled' => ! empty( Ikon_SEO_Plugin::settings()['experiments_claims_revenue_enabled'] ),
				'tables_ready' => false,
			);
		}

		$experiment_counts = array();
		$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$experiments} GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( (array) $rows as $row ) {
			$experiment_counts[ sanitize_key( $row['status'] ?? '' ) ] = absint( $row['total'] ?? 0 );
		}

		$claim_counts = array();
		$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$claims} GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( (array) $rows as $row ) {
			$claim_counts[ sanitize_key( $row['status'] ?? '' ) ] = absint( $row['total'] ?? 0 );
		}

		$settings = Ikon_SEO_Plugin::settings();
		$days = max( 1, min( 365, absint( $settings['revenue_reporting_days'] ?? 30 ) ) );
		$currency = $this->sanitize_currency( $settings['revenue_default_currency'] ?? 'USD' );
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days . ' days' ) );
		$summary = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS events, SUM(CASE WHEN qualified = 1 THEN 1 ELSE 0 END) AS qualified, SUM(CASE WHEN customer = 1 THEN 1 ELSE 0 END) AS customers, COALESCE(SUM(CASE WHEN currency = %s THEN value ELSE 0 END),0) AS revenue FROM {$revenue} WHERE occurred_at >= %s",
				$currency,
				$since
			),
			ARRAY_A
		);

		return array(
			'enabled' => ! empty( $settings['experiments_claims_revenue_enabled'] ),
			'tables_ready' => true,
			'experiments' => $experiment_counts,
			'measurements' => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$measurements}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'claims' => $claim_counts,
			'claims_due' => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$claims} WHERE status NOT IN ('retired','dismissed') AND review_due_at IS NOT NULL AND review_due_at <= %s", $now ) ) ),
			'revenue_period_days' => $days,
			'revenue_events' => absint( $summary['events'] ?? 0 ),
			'qualified_leads' => absint( $summary['qualified'] ?? 0 ),
			'customers' => absint( $summary['customers'] ?? 0 ),
			'attributed_value' => round( (float) ( $summary['revenue'] ?? 0 ), 2 ),
			'currency' => $currency,
			'safe_boundary' => array(
				'publishes_content' => false,
				'changes_live_pages' => false,
				'contacts_leads' => false,
				'changes_crm_records' => false,
				'stores_plain_contact_details' => false,
			),
		);
	}

	public function report( $limit = 100 ) {
		$limit = max( 10, min( 500, absint( $limit ) ) );
		return array(
			'status' => $this->status(),
			'experiments' => $this->list_experiments( $limit ),
			'claims' => $this->list_claims( $limit ),
			'revenue' => $this->revenue_report( $limit ),
			'data_quality' => $this->portfolio_data_quality(),
			'limitations' => array(
				'Experiment movement is observational unless the test and comparison groups are genuinely comparable.',
				'Search demand, seasonality, campaigns, tracking changes and unrelated website work can affect outcomes.',
				'Revenue attribution depends on complete landing-page, campaign and conversion records.',
				'A verified claim record documents a review; it does not replace professional or legal review where required.',
			),
			'generated_at' => current_time( 'mysql', true ),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'save_experiment':
				return $this->save_experiment( (array) ( $payload['experiment'] ?? $payload ), $user_id );
			case 'update_experiment':
				return $this->update_experiment( absint( $payload['experiment_id'] ?? 0 ), (array) ( $payload['experiment'] ?? $payload ), $user_id );
			case 'capture_measurement':
				return $this->capture_measurement( absint( $payload['experiment_id'] ?? 0 ), (array) ( $payload['measurement'] ?? $payload ), $user_id );
			case 'save_claims':
				$claims = isset( $payload['claims'] ) ? (array) $payload['claims'] : array( (array) ( $payload['claim'] ?? $payload ) );
				return $this->save_claims( $claims, $user_id );
			case 'update_claim':
				return $this->update_claim( absint( $payload['claim_id'] ?? 0 ), (array) ( $payload['claim'] ?? $payload ), $user_id );
			case 'import_revenue_events':
				return $this->import_revenue_events( (array) ( $payload['events'] ?? array() ), $user_id, sanitize_key( $payload['source'] ?? 'workspace' ) );
			case 'save_settings':
				return $this->save_settings( $payload, $user_id );
			case 'cleanup':
				return $this->cleanup();
			case 'read':
			default:
				return $this->report( absint( $payload['limit'] ?? 100 ) );
		}
	}

	public function save_experiment( array $data, $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->experiments_table() ) ) {
			return new WP_Error( 'ikon_seo_experiment_table', __( 'The experiment database is not ready.', 'ikon-seo' ) );
		}

		$title = sanitize_text_field( $data['title'] ?? '' );
		$hypothesis = sanitize_textarea_field( $data['hypothesis'] ?? '' );
		if ( ! $title || ! $hypothesis ) {
			return new WP_Error( 'ikon_seo_experiment_required', __( 'An experiment title and hypothesis are required.', 'ikon-seo' ) );
		}

		$test_urls = $this->sanitize_site_urls( (array) ( $data['test_urls'] ?? array() ) );
		$comparison_urls = $this->sanitize_site_urls( (array) ( $data['comparison_urls'] ?? array() ) );
		if ( ! $test_urls ) {
			return new WP_Error( 'ikon_seo_experiment_test_urls', __( 'Add at least one same-site test URL.', 'ikon-seo' ) );
		}
		if ( array_intersect( $test_urls, $comparison_urls ) ) {
			return new WP_Error( 'ikon_seo_experiment_overlap', __( 'A URL cannot be in both the test and comparison groups.', 'ikon-seo' ) );
		}

		$overlap = $this->active_url_overlap( array_merge( $test_urls, $comparison_urls ), 0 );
		if ( $overlap ) {
			return new WP_Error( 'ikon_seo_experiment_active_overlap', sprintf( __( 'A selected URL already belongs to an active experiment: %s', 'ikon-seo' ), implode( ', ', array_slice( $overlap, 0, 3 ) ) ) );
		}

		$status = sanitize_key( $data['status'] ?? 'draft' );
		if ( ! in_array( $status, $this->experiment_statuses(), true ) ) {
			$status = 'draft';
		}
		$primary_metric = sanitize_key( $data['primary_metric'] ?? 'clicks' );
		if ( ! in_array( $primary_metric, $this->allowed_metrics(), true ) ) {
			$primary_metric = 'clicks';
		}
		$minimum_days = max( 7, min( 180, absint( $data['minimum_days'] ?? 28 ) ) );
		$start_date = $this->date_or_null( $data['start_date'] ?? '' );
		$end_date = $this->date_or_null( $data['end_date'] ?? '' );
		$now = current_time( 'mysql', true );
		$key = hash( 'sha256', wp_json_encode( array( $title, $hypothesis, $test_urls, microtime( true ) ) ) );

		$record = array(
			'experiment_key' => $key,
			'title' => $title,
			'hypothesis' => $hypothesis,
			'change_type' => sanitize_key( $data['change_type'] ?? 'content' ),
			'status' => $status,
			'primary_metric' => $primary_metric,
			'secondary_metrics_json' => wp_json_encode( array_values( array_intersect( array_map( 'sanitize_key', (array) ( $data['secondary_metrics'] ?? array() ) ), $this->allowed_metrics() ) ) ),
			'test_urls_json' => wp_json_encode( $test_urls ),
			'comparison_urls_json' => wp_json_encode( $comparison_urls ),
			'minimum_days' => $minimum_days,
			'start_date' => $start_date,
			'end_date' => $end_date,
			'notes' => sanitize_textarea_field( $data['notes'] ?? '' ),
			'created_by' => absint( $user_id ),
			'created_at' => $now,
			'updated_at' => $now,
		);
		$inserted = $wpdb->insert( $this->experiments_table(), $record );
		if ( false === $inserted ) {
			return new WP_Error( 'ikon_seo_experiment_store', __( 'The experiment could not be stored.', 'ikon-seo' ) );
		}
		$id = absint( $wpdb->insert_id );
		$this->history_event( 'experiment', 'planned', 'SEO experiment created', $title, array( 'experiment_id' => $id, 'status' => $status, 'test_urls' => count( $test_urls ), 'comparison_urls' => count( $comparison_urls ) ), $user_id );
		return array( 'saved' => true, 'experiment' => $this->get_experiment( $id ) );
	}

	public function update_experiment( $experiment_id, array $data, $user_id = 0 ) {
		global $wpdb;
		$experiment = $this->get_experiment( $experiment_id );
		if ( ! $experiment ) {
			return new WP_Error( 'ikon_seo_experiment_missing', __( 'The experiment could not be found.', 'ikon-seo' ) );
		}

		$update = array( 'updated_at' => current_time( 'mysql', true ) );
		if ( array_key_exists( 'status', $data ) ) {
			$status = sanitize_key( $data['status'] );
			if ( ! in_array( $status, $this->experiment_statuses(), true ) ) {
				return new WP_Error( 'ikon_seo_experiment_status', __( 'The experiment status is not supported.', 'ikon-seo' ) );
			}
			$update['status'] = $status;
		}
		foreach ( array( 'title', 'change_type', 'primary_metric' ) as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = 'change_type' === $field || 'primary_metric' === $field ? sanitize_key( $data[ $field ] ) : sanitize_text_field( $data[ $field ] );
			}
		}
		foreach ( array( 'hypothesis', 'notes' ) as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = sanitize_textarea_field( $data[ $field ] );
			}
		}
		foreach ( array( 'start_date', 'end_date' ) as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = $this->date_or_null( $data[ $field ] );
			}
		}
		if ( array_key_exists( 'minimum_days', $data ) ) {
			$update['minimum_days'] = max( 7, min( 180, absint( $data['minimum_days'] ) ) );
		}

		$result = $wpdb->update( $this->experiments_table(), $update, array( 'id' => absint( $experiment_id ) ) );
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_experiment_update', __( 'The experiment could not be updated.', 'ikon-seo' ) );
		}
		$this->history_event( 'experiment', sanitize_key( $update['status'] ?? 'updated' ), 'SEO experiment updated', sanitize_text_field( $update['title'] ?? $experiment['title'] ), array( 'experiment_id' => absint( $experiment_id ) ), $user_id );
		return array( 'saved' => true, 'experiment' => $this->get_experiment( $experiment_id ) );
	}

	public function capture_measurement( $experiment_id, array $data, $user_id = 0 ) {
		global $wpdb;
		$experiment = $this->get_experiment( $experiment_id );
		if ( ! $experiment ) {
			return new WP_Error( 'ikon_seo_experiment_missing', __( 'The experiment could not be found.', 'ikon-seo' ) );
		}
		$phase = sanitize_key( $data['phase'] ?? 'outcome' );
		if ( ! in_array( $phase, array( 'baseline', 'checkpoint', 'outcome' ), true ) ) {
			$phase = 'outcome';
		}
		$period_start = $this->date_or_null( $data['period_start'] ?? '' );
		$period_end = $this->date_or_null( $data['period_end'] ?? '' );
		$metrics = isset( $data['metrics'] ) && is_array( $data['metrics'] ) ? $this->sanitize_metrics( $data['metrics'] ) : $this->aggregate_url_metrics( (array) $experiment['test_urls'] );
		$comparison = isset( $data['comparison_metrics'] ) && is_array( $data['comparison_metrics'] ) ? $this->sanitize_metrics( $data['comparison_metrics'] ) : $this->aggregate_url_metrics( (array) $experiment['comparison_urls'] );
		$data_quality = $this->measurement_quality( $experiment, $metrics, $comparison, $period_start, $period_end );
		$baseline = $this->baseline_measurement( $experiment_id );
		$outcome = 'baseline' === $phase || ! $baseline ? 'baseline' : $this->classify_outcome( $experiment, $baseline['metrics'], $metrics, $data_quality, (array) ( $baseline['comparison_metrics'] ?? array() ), $comparison );
		if ( 'baseline' !== $outcome ) {
			$data_quality['outcome_analysis'] = $this->outcome_analysis( $experiment, $baseline['metrics'], $metrics, (array) ( $baseline['comparison_metrics'] ?? array() ), $comparison );
		}
		$confidence = sanitize_key( $data_quality['confidence'] ?? 'low' );
		$now = current_time( 'mysql', true );
		$record = array(
			'experiment_id' => absint( $experiment_id ),
			'phase' => $phase,
			'period_start' => $period_start,
			'period_end' => $period_end,
			'metrics_json' => wp_json_encode( $metrics ),
			'comparison_metrics_json' => wp_json_encode( $comparison ),
			'data_quality_json' => wp_json_encode( $data_quality ),
			'outcome' => $outcome,
			'confidence' => $confidence,
			'notes' => sanitize_textarea_field( $data['notes'] ?? '' ),
			'created_by' => absint( $user_id ),
			'measured_at' => $now,
		);
		if ( false === $wpdb->insert( $this->measurements_table(), $record ) ) {
			return new WP_Error( 'ikon_seo_measurement_store', __( 'The experiment measurement could not be stored.', 'ikon-seo' ) );
		}
		$status = 'baseline' === $phase ? 'running' : ( 'outcome' === $phase ? 'monitoring' : sanitize_key( $experiment['status'] ) );
		if ( 'outcome' === $phase && in_array( $outcome, array( 'improved', 'declined', 'neutral', 'inconclusive' ), true ) ) {
			$status = 'completed';
		}
		$wpdb->update( $this->experiments_table(), array( 'status' => $status, 'updated_at' => $now ), array( 'id' => absint( $experiment_id ) ) );
		$this->history_event( 'experiment', $outcome, 'Experiment measurement recorded', $experiment['title'], array( 'experiment_id' => absint( $experiment_id ), 'phase' => $phase, 'outcome' => $outcome, 'confidence' => $confidence ), $user_id );
		return array( 'saved' => true, 'measurement' => $this->latest_measurement( $experiment_id ), 'experiment' => $this->get_experiment( $experiment_id ) );
	}

	public function save_claims( array $claims, $user_id = 0 ) {
		global $wpdb;
		$table = $this->claims_table();
		if ( ! $this->table_exists( $table ) ) {
			return new WP_Error( 'ikon_seo_claim_table', __( 'The claim database is not ready.', 'ikon-seo' ) );
		}
		$claims = array_slice( $claims, 0, 200 );
		$saved = 0;
		$skipped = 0;
		$ids = array();
		foreach ( $claims as $claim ) {
			$claim = (array) $claim;
			$text = trim( sanitize_textarea_field( $claim['claim_text'] ?? '' ) );
			if ( ! $text ) {
				$skipped++;
				continue;
			}
			$post_id = absint( $claim['post_id'] ?? 0 );
			$source_url = esc_url_raw( $claim['source_url'] ?? '' );
			$hash = hash( 'sha256', strtolower( preg_replace( '/\s+/', ' ', $text ) ) . '|' . $post_id );
			$status = sanitize_key( $claim['status'] ?? 'needs_review' );
			if ( ! in_array( $status, $this->claim_statuses(), true ) ) {
				$status = 'needs_review';
			}
			$risk = sanitize_key( $claim['risk_level'] ?? 'standard' );
			if ( ! in_array( $risk, array( 'standard', 'sensitive', 'high' ), true ) ) {
				$risk = 'standard';
			}
			$review_due = $this->date_or_null( $claim['review_due_at'] ?? '' );
			if ( ! $review_due ) {
				$settings = Ikon_SEO_Plugin::settings();
				$default_days = absint( $settings['claim_default_review_days'] ?? 180 );
				$high_risk_days = absint( $settings['claim_high_risk_review_days'] ?? 30 );
				$days = 'high' === $risk ? max( 1, min( 365, $high_risk_days ) ) : max( 7, min( 730, $default_days ) );
				$review_due = gmdate( 'Y-m-d H:i:s', strtotime( '+' . $days . ' days' ) );
			}
			$now = current_time( 'mysql', true );
			$claim_type = sanitize_key( $claim['claim_type'] ?? 'factual' );
			if ( ! in_array( $claim_type, $this->claim_types(), true ) ) {
				$claim_type = 'factual';
			}
			$source_type = sanitize_key( $claim['source_type'] ?? 'secondary' );
			if ( ! in_array( $source_type, $this->source_types(), true ) ) {
				$source_type = 'secondary';
			}
			$record = array(
				'post_id' => $post_id,
				'claim_hash' => $hash,
				'claim_text' => $text,
				'claim_type' => $claim_type,
				'risk_level' => $risk,
				'source_url' => $source_url,
				'source_title' => sanitize_text_field( $claim['source_title'] ?? '' ),
				'source_type' => $source_type,
				'source_published_at' => $this->date_or_null( $claim['source_published_at'] ?? '' ),
				'status' => $status,
				'verified_at' => 'verified' === $status ? $now : null,
				'review_due_at' => $review_due,
				'reviewer_id' => absint( $claim['reviewer_id'] ?? $user_id ),
				'notes' => sanitize_textarea_field( $claim['notes'] ?? '' ),
				'created_by' => absint( $user_id ),
				'created_at' => $now,
				'updated_at' => $now,
			);
			$sql = "INSERT INTO {$table} (post_id,claim_hash,claim_text,claim_type,risk_level,source_url,source_title,source_type,source_published_at,status,verified_at,review_due_at,reviewer_id,notes,created_by,created_at,updated_at) VALUES (%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%d,%s,%s) ON DUPLICATE KEY UPDATE source_url=VALUES(source_url),source_title=VALUES(source_title),source_type=VALUES(source_type),source_published_at=VALUES(source_published_at),status=VALUES(status),verified_at=VALUES(verified_at),review_due_at=VALUES(review_due_at),reviewer_id=VALUES(reviewer_id),notes=VALUES(notes),updated_at=VALUES(updated_at)";
			$result = $wpdb->query( $wpdb->prepare( $sql, $record['post_id'], $record['claim_hash'], $record['claim_text'], $record['claim_type'], $record['risk_level'], $record['source_url'], $record['source_title'], $record['source_type'], $record['source_published_at'], $record['status'], $record['verified_at'], $record['review_due_at'], $record['reviewer_id'], $record['notes'], $record['created_by'], $record['created_at'], $record['updated_at'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( false === $result ) {
				$skipped++;
				continue;
			}
			$saved++;
			$stored_id = absint( $wpdb->insert_id );
			if ( ! $stored_id ) {
				$stored_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE post_id = %d AND claim_hash = %s LIMIT 1", $post_id, $hash ) ) );
			}
			if ( $stored_id ) {
				$ids[] = $stored_id;
			}
		}
		if ( $saved ) {
			$this->history_event( 'claim', 'stored', 'Content claims stored', sprintf( '%d claim records were stored or updated.', $saved ), array( 'saved' => $saved, 'skipped' => $skipped ), $user_id );
		}
		return array( 'saved' => $saved, 'skipped' => $skipped, 'ids' => $ids );
	}

	public function update_claim( $claim_id, array $data, $user_id = 0 ) {
		global $wpdb;
		$claim = $this->get_claim( $claim_id );
		if ( ! $claim ) {
			return new WP_Error( 'ikon_seo_claim_missing', __( 'The claim record could not be found.', 'ikon-seo' ) );
		}
		$update = array( 'updated_at' => current_time( 'mysql', true ) );
		if ( array_key_exists( 'status', $data ) ) {
			$status = sanitize_key( $data['status'] );
			if ( ! in_array( $status, $this->claim_statuses(), true ) ) {
				return new WP_Error( 'ikon_seo_claim_status', __( 'The claim status is not supported.', 'ikon-seo' ) );
			}
			$update['status'] = $status;
			$update['verified_at'] = 'verified' === $status ? current_time( 'mysql', true ) : null;
		}
		foreach ( array( 'source_title', 'notes' ) as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = 'notes' === $field ? sanitize_textarea_field( $data[ $field ] ) : sanitize_text_field( $data[ $field ] );
			}
		}
		if ( array_key_exists( 'source_url', $data ) ) {
			$update['source_url'] = esc_url_raw( $data['source_url'] );
		}
		if ( array_key_exists( 'review_due_at', $data ) ) {
			$update['review_due_at'] = $this->date_or_null( $data['review_due_at'] );
		}
		if ( array_key_exists( 'reviewer_id', $data ) ) {
			$update['reviewer_id'] = absint( $data['reviewer_id'] );
		}
		if ( false === $wpdb->update( $this->claims_table(), $update, array( 'id' => absint( $claim_id ) ) ) ) {
			return new WP_Error( 'ikon_seo_claim_update', __( 'The claim record could not be updated.', 'ikon-seo' ) );
		}
		$this->history_event( 'claim', sanitize_key( $update['status'] ?? 'updated' ), 'Content claim updated', wp_trim_words( $claim['claim_text'], 18 ), array( 'claim_id' => absint( $claim_id ) ), $user_id );
		return array( 'saved' => true, 'claim' => $this->get_claim( $claim_id ) );
	}

	public function import_revenue_events( array $events, $user_id = 0, $source = 'manual' ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->revenue_table() ) ) {
			return new WP_Error( 'ikon_seo_revenue_table', __( 'The attribution database is not ready.', 'ikon-seo' ) );
		}
		$events = array_slice( $events, 0, 1000 );
		$saved = 0;
		$skipped = 0;
		$settings = Ikon_SEO_Plugin::settings();
		$default_currency = strtoupper( sanitize_text_field( $settings['revenue_default_currency'] ?? 'USD' ) );
		$now = current_time( 'mysql', true );
		foreach ( $events as $event ) {
			$event = (array) $event;
			$event_type = sanitize_key( $event['event_type'] ?? 'lead' );
			if ( ! in_array( $event_type, array( 'lead', 'qualified_lead', 'appointment', 'proposal', 'sale', 'refund', 'affiliate', 'advertising', 'other' ), true ) ) {
				$skipped++;
				continue;
			}
			$landing_url = esc_url_raw( $event['landing_url'] ?? '' );
			if ( $landing_url && ! $this->url_belongs_to_site( $landing_url ) ) {
				$skipped++;
				continue;
			}
			$occurred_at = $this->date_or_null( $event['occurred_at'] ?? '' );
			if ( ! $occurred_at ) {
				$occurred_at = $now;
			}
			$reference = sanitize_text_field( $event['event_ref'] ?? $event['lead_ref'] ?? '' );
			if ( ! $reference ) {
				$reference = wp_generate_uuid4();
			}
			$reference_hash = hash_hmac( 'sha256', $reference, wp_salt( 'auth' ) );
			$value = round( (float) ( $event['value'] ?? 0 ), 2 );
			if ( 'refund' === $event_type && $value > 0 ) {
				$value = -1 * $value;
			}
			$currency = preg_replace( '/[^A-Z]/', '', strtoupper( sanitize_text_field( $event['currency'] ?? $default_currency ) ) );
			if ( 3 !== strlen( $currency ) ) {
				$currency = $default_currency;
			}
			$event_key = hash( 'sha256', implode( '|', array( $reference_hash, $event_type, $occurred_at, $value, $landing_url ) ) );
			$metadata = $this->sanitize_metadata( (array) ( $event['metadata'] ?? array() ) );
			$record = array(
				'event_key' => $event_key,
				'event_type' => $event_type,
				'occurred_at' => $occurred_at,
				'source_name' => sanitize_text_field( $event['source'] ?? $source ),
				'medium' => sanitize_text_field( $event['medium'] ?? '' ),
				'campaign' => sanitize_text_field( $event['campaign'] ?? '' ),
				'landing_url' => $landing_url,
				'post_id' => absint( $event['post_id'] ?? 0 ),
				'reference_hash' => $reference_hash,
				'crm_stage' => sanitize_key( $event['crm_stage'] ?? '' ),
				'value' => $value,
				'currency' => $currency,
				'qualified' => ! empty( $event['qualified'] ) || in_array( $event_type, array( 'qualified_lead', 'appointment', 'proposal', 'sale' ), true ) ? 1 : 0,
				'customer' => ! empty( $event['customer'] ) || 'sale' === $event_type ? 1 : 0,
				'metadata_json' => wp_json_encode( $metadata ),
				'import_source' => sanitize_key( $source ),
				'imported_by' => absint( $user_id ),
				'created_at' => $now,
			);
			$inserted = $wpdb->insert( $this->revenue_table(), $record );
			if ( false === $inserted ) {
				if ( false !== strpos( (string) $wpdb->last_error, 'Duplicate' ) ) {
					$skipped++;
					continue;
				}
				$skipped++;
				continue;
			}
			$saved++;
		}
		if ( $saved ) {
			$this->history_event( 'attribution', 'imported', 'Revenue attribution evidence imported', sprintf( '%d privacy-preserving conversion or value records were stored.', $saved ), array( 'saved' => $saved, 'skipped' => $skipped, 'source' => sanitize_key( $source ) ), $user_id );
		}
		return array( 'saved' => $saved, 'skipped' => $skipped, 'privacy' => array( 'plain_contact_details_stored' => false, 'references_hashed' => true ) );
	}

	public function save_settings( array $payload, $user_id = 0 ) {
		$settings = Ikon_SEO_Plugin::settings();
		$settings['experiments_claims_revenue_enabled'] = ! empty( $payload['enabled'] ) ? 1 : 0;
		$settings['experiment_minimum_days'] = max( 7, min( 180, absint( $payload['experiment_minimum_days'] ?? $settings['experiment_minimum_days'] ?? 28 ) ) );
		$settings['experiment_minimum_observations'] = max( 1, min( 100000, absint( $payload['experiment_minimum_observations'] ?? $settings['experiment_minimum_observations'] ?? 100 ) ) );
		$settings['experiment_change_threshold_percent'] = max( 1, min( 100, (float) ( $payload['experiment_change_threshold_percent'] ?? $settings['experiment_change_threshold_percent'] ?? 10 ) ) );
		$settings['claim_default_review_days'] = max( 7, min( 730, absint( $payload['claim_default_review_days'] ?? $settings['claim_default_review_days'] ?? 180 ) ) );
		$settings['claim_high_risk_review_days'] = max( 1, min( 365, absint( $payload['claim_high_risk_review_days'] ?? $settings['claim_high_risk_review_days'] ?? 30 ) ) );
		$settings['revenue_default_currency'] = $this->sanitize_currency( $payload['revenue_default_currency'] ?? $settings['revenue_default_currency'] ?? 'USD' );
		$settings['revenue_reporting_days'] = max( 7, min( 365, absint( $payload['revenue_reporting_days'] ?? $settings['revenue_reporting_days'] ?? 30 ) ) );
		$settings['experiments_claims_revenue_retention_days'] = max( 90, min( 1825, absint( $payload['retention_days'] ?? $settings['experiments_claims_revenue_retention_days'] ?? 730 ) ) );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->history_event( 'configuration', 'completed', 'Experiments, Claims and Revenue policy updated', 'Measurement, claim-review and attribution policies were updated.', array( 'enabled' => (bool) $settings['experiments_claims_revenue_enabled'] ), $user_id );
		return array( 'saved' => true, 'status' => $this->status() );
	}

	public function list_experiments( $limit = 100 ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->experiments_table() ) ) {
			return array();
		}
		$experiments_table = $this->experiments_table();
		$measurements_table = $this->measurements_table();
		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$experiments_table} ORDER BY updated_at DESC LIMIT %d", max( 1, min( 500, absint( $limit ) ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as &$row ) {
			$row = $this->hydrate_experiment( $row );
			$row['latest_measurement'] = $this->latest_measurement( absint( $row['id'] ) );
			$row['measurement_count'] = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$measurements_table} WHERE experiment_id = %d", absint( $row['id'] ) ) ) );
		}
		unset( $row );
		return $rows;
	}

	public function get_experiment( $experiment_id ) {
		global $wpdb;
		$table = $this->experiments_table();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $experiment_id ) ), ARRAY_A );
		return $row ? $this->hydrate_experiment( $row ) : array();
	}

	public function list_claims( $limit = 100 ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->claims_table() ) ) {
			return array();
		}
		$table = $this->claims_table();
		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY CASE status WHEN 'overdue' THEN 0 WHEN 'needs_review' THEN 1 WHEN 'disputed' THEN 2 ELSE 3 END, updated_at DESC LIMIT %d", max( 1, min( 500, absint( $limit ) ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as &$row ) {
			$row['post_title'] = $row['post_id'] ? get_the_title( absint( $row['post_id'] ) ) : '';
			$row['edit_url'] = $row['post_id'] ? get_edit_post_link( absint( $row['post_id'] ), 'raw' ) : '';
		}
		unset( $row );
		return $rows;
	}

	public function get_claim( $claim_id ) {
		global $wpdb;
		$table = $this->claims_table();
		return (array) $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $claim_id ) ), ARRAY_A );
	}

	public function revenue_report( $limit = 100 ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->revenue_table() ) ) {
			return array( 'summary' => array(), 'landing_pages' => array(), 'channels' => array(), 'events' => array() );
		}
		$table = $this->revenue_table();
		$settings = Ikon_SEO_Plugin::settings();
		$days = max( 7, min( 365, absint( $settings['revenue_reporting_days'] ?? 30 ) ) );
		$currency = $this->sanitize_currency( $settings['revenue_default_currency'] ?? 'USD' );
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days . ' days' ) );
		$summary = (array) $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS events, SUM(qualified) AS qualified, SUM(customer) AS customers, COALESCE(SUM(CASE WHEN currency = %s THEN value ELSE 0 END),0) AS value FROM {$table} WHERE occurred_at >= %s", $currency, $since ), ARRAY_A );
		$landing = $wpdb->get_results( $wpdb->prepare( "SELECT landing_url, currency, COUNT(*) AS events, SUM(qualified) AS qualified, SUM(customer) AS customers, COALESCE(SUM(value),0) AS value FROM {$table} WHERE occurred_at >= %s AND currency = %s GROUP BY landing_url,currency ORDER BY value DESC, events DESC LIMIT %d", $since, $currency, min( 100, $limit ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$channels = $wpdb->get_results( $wpdb->prepare( "SELECT source_name, medium, campaign, currency, COUNT(*) AS events, SUM(qualified) AS qualified, SUM(customer) AS customers, COALESCE(SUM(value),0) AS value FROM {$table} WHERE occurred_at >= %s AND currency = %s GROUP BY source_name,medium,campaign,currency ORDER BY value DESC, events DESC LIMIT %d", $since, $currency, min( 100, $limit ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$currency_totals = $wpdb->get_results( $wpdb->prepare( "SELECT currency, COUNT(*) AS events, COALESCE(SUM(value),0) AS value FROM {$table} WHERE occurred_at >= %s GROUP BY currency ORDER BY value DESC", $since ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$events = $wpdb->get_results( $wpdb->prepare( "SELECT id,event_type,occurred_at,source_name,medium,campaign,landing_url,post_id,crm_stage,value,currency,qualified,customer,import_source,created_at FROM {$table} ORDER BY occurred_at DESC LIMIT %d", min( 500, $limit ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array(
			'period' => array( 'days' => $days, 'since' => $since ),
			'summary' => array( 'events' => absint( $summary['events'] ?? 0 ), 'qualified' => absint( $summary['qualified'] ?? 0 ), 'customers' => absint( $summary['customers'] ?? 0 ), 'value' => round( (float) ( $summary['value'] ?? 0 ), 2 ), 'currency' => $currency ),
			'currency_totals' => $currency_totals,
			'landing_pages' => $landing,
			'channels' => $channels,
			'events' => $events,
			'privacy' => array( 'contact_details_included' => false, 'reference_hashes_exposed' => false ),
		);
	}

	public function cleanup() {
		global $wpdb;
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_ecr_tables', __( 'The experiment, claim and attribution databases are not ready.', 'ikon-seo' ) );
		}
		$settings = Ikon_SEO_Plugin::settings();
		$retention = max( 90, min( 1825, absint( $settings['experiments_claims_revenue_retention_days'] ?? 730 ) ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $retention . ' days' ) );
		$measurements_table = $this->measurements_table();
		$experiments_table = $this->experiments_table();
		$revenue_table = $this->revenue_table();
		$deleted_measurements = $wpdb->query( $wpdb->prepare( "DELETE FROM {$measurements_table} WHERE measured_at < %s AND experiment_id IN (SELECT id FROM {$experiments_table} WHERE status IN ('completed','cancelled','archived'))", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$deleted_revenue = $wpdb->query( $wpdb->prepare( "DELETE FROM {$revenue_table} WHERE occurred_at < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array( 'measurements_deleted' => max( 0, (int) $deleted_measurements ), 'revenue_events_deleted' => max( 0, (int) $deleted_revenue ), 'cutoff' => $cutoff );
	}

	private function aggregate_url_metrics( array $urls ) {
		$totals = array( 'clicks' => 0.0, 'impressions' => 0.0, 'ctr' => 0.0, 'position' => 0.0, 'sessions' => 0.0, 'active_users' => 0.0, 'views' => 0.0, 'key_events' => 0.0, 'revenue' => 0.0, 'observations' => 0 );
		$position_weight = 0.0;
		foreach ( $urls as $url ) {
			$summary = $this->search_intelligence->page_summary( $url );
			$clicks = (float) ( $summary['clicks'] ?? 0 );
			$impressions = (float) ( $summary['impressions'] ?? 0 );
			$totals['clicks'] += $clicks;
			$totals['impressions'] += $impressions;
			$position = (float) ( $summary['position'] ?? $summary['average_position'] ?? 0 );
			if ( $position > 0 && $impressions > 0 ) {
				$position_weight += $position * $impressions;
			}
			if ( $impressions > 0 ) {
				$totals['observations'] += (int) round( $impressions );
			}
		}
		$totals['ctr'] = $totals['impressions'] > 0 ? $totals['clicks'] / $totals['impressions'] : 0;
		$totals['position'] = $totals['impressions'] > 0 ? $position_weight / $totals['impressions'] : 0;
		$analytics = $this->analytics->report( 28, false );
		if ( ! is_wp_error( $analytics ) ) {
			$paths = array();
			foreach ( $urls as $url ) {
				$path = wp_parse_url( $url, PHP_URL_PATH );
				$paths[ untrailingslashit( $path ?: '/' ) ] = true;
			}
			foreach ( (array) ( $analytics['top_pages'] ?? array() ) as $row ) {
				$path = untrailingslashit( (string) ( $row['path'] ?? '/' ) );
				if ( ! isset( $paths[ $path ] ) ) {
					continue;
				}
				$totals['sessions'] += (float) ( $row['sessions'] ?? 0 );
				$totals['active_users'] += (float) ( $row['active_users'] ?? 0 );
				$totals['views'] += (float) ( $row['views'] ?? 0 );
				$totals['key_events'] += (float) ( $row['key_events'] ?? 0 );
			}
		}
		$revenue = $this->revenue_for_urls( $urls, 28 );
		$totals['revenue'] = $revenue['value'];
		$totals['qualified_leads'] = $revenue['qualified'];
		$totals['customers'] = $revenue['customers'];
		return $totals;
	}

	private function revenue_for_urls( array $urls, $days ) {
		global $wpdb;
		if ( ! $urls || ! $this->table_exists( $this->revenue_table() ) ) {
			return array( 'value' => 0.0, 'qualified' => 0, 'customers' => 0 );
		}
		$table = $this->revenue_table();
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days ) . ' days' ) );
		$placeholders = implode( ',', array_fill( 0, count( $urls ), '%s' ) );
		$sql = "SELECT COALESCE(SUM(value),0) AS value, SUM(qualified) AS qualified, SUM(customer) AS customers FROM {$table} WHERE occurred_at >= %s AND landing_url IN ({$placeholders})";
		$params = array_merge( array( $since ), $urls );
		$row = (array) $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array( 'value' => round( (float) ( $row['value'] ?? 0 ), 2 ), 'qualified' => absint( $row['qualified'] ?? 0 ), 'customers' => absint( $row['customers'] ?? 0 ) );
	}

	private function measurement_quality( array $experiment, array $metrics, array $comparison, $period_start, $period_end ) {
		$settings = Ikon_SEO_Plugin::settings();
		$issues = array();
		$score = 100;
		$days = 0;
		if ( $period_start && $period_end ) {
			$days = max( 0, (int) floor( ( strtotime( $period_end ) - strtotime( $period_start ) ) / DAY_IN_SECONDS ) + 1 );
		}
		$minimum_days = max( 7, absint( $experiment['minimum_days'] ?? $settings['experiment_minimum_days'] ?? 28 ) );
		if ( $days && $days < $minimum_days ) {
			$issues[] = 'The measured period is shorter than the configured minimum.';
			$score -= 25;
		}
		$observations = (float) ( $metrics['observations'] ?? $metrics['impressions'] ?? 0 );
		$minimum_observations = max( 1, absint( $settings['experiment_minimum_observations'] ?? 100 ) );
		if ( $observations < $minimum_observations ) {
			$issues[] = 'The measured sample is below the configured minimum observation count.';
			$score -= 30;
		}
		if ( empty( $experiment['comparison_urls'] ) ) {
			$issues[] = 'No comparison group was supplied; seasonality and unrelated changes are harder to separate.';
			$score -= 20;
		} elseif ( ! $comparison ) {
			$issues[] = 'Comparison URLs are configured, but no comparison metrics were recorded.';
			$score -= 20;
		}
		if ( empty( $metrics['clicks'] ) && empty( $metrics['sessions'] ) && empty( $metrics['key_events'] ) && empty( $metrics['revenue'] ) ) {
			$issues[] = 'No usable outcome metrics were recorded.';
			$score -= 40;
		}
		$score = max( 0, min( 100, $score ) );
		$confidence = $score >= 80 ? 'high' : ( $score >= 55 ? 'medium' : 'low' );
		return array( 'score' => $score, 'confidence' => $confidence, 'issues' => $issues, 'period_days' => $days, 'minimum_days' => $minimum_days, 'observations' => $observations, 'minimum_observations' => $minimum_observations );
	}

	private function classify_outcome( array $experiment, array $baseline, array $current, array $quality, array $baseline_comparison = array(), array $current_comparison = array() ) {
		if ( 'low' === ( $quality['confidence'] ?? 'low' ) ) {
			return 'inconclusive';
		}
		$analysis = $this->outcome_analysis( $experiment, $baseline, $current, $baseline_comparison, $current_comparison );
		if ( empty( $analysis['comparable'] ) ) {
			return 'inconclusive';
		}
		$threshold = max( 1, (float) ( Ikon_SEO_Plugin::settings()['experiment_change_threshold_percent'] ?? 10 ) );
		$change = (float) ( $analysis['adjusted_change_percent'] ?? $analysis['test_change_percent'] ?? 0 );
		if ( $change >= $threshold ) {
			return 'improved';
		}
		if ( $change <= -$threshold ) {
			return 'declined';
		}
		return 'neutral';
	}

	private function outcome_analysis( array $experiment, array $baseline, array $current, array $baseline_comparison = array(), array $current_comparison = array() ) {
		$metric = sanitize_key( $experiment['primary_metric'] ?? 'clicks' );
		$before = (float) ( $baseline[ $metric ] ?? 0 );
		$after = (float) ( $current[ $metric ] ?? 0 );
		if ( 0.0 === $before && 0.0 === $after ) {
			return array( 'metric' => $metric, 'comparable' => false, 'test_change_percent' => null, 'comparison_change_percent' => null, 'adjusted_change_percent' => null );
		}
		$test_change = $this->metric_change_percent( $metric, $before, $after );
		$comparison_change = null;
		$adjusted_change = $test_change;
		if ( array_key_exists( $metric, $baseline_comparison ) && array_key_exists( $metric, $current_comparison ) ) {
			$comparison_before = (float) $baseline_comparison[ $metric ];
			$comparison_after = (float) $current_comparison[ $metric ];
			if ( 0.0 !== $comparison_before || 0.0 !== $comparison_after ) {
				$comparison_change = $this->metric_change_percent( $metric, $comparison_before, $comparison_after );
				$adjusted_change = $test_change - $comparison_change;
			}
		}
		return array(
			'metric' => $metric,
			'comparable' => true,
			'before' => $before,
			'after' => $after,
			'test_change_percent' => round( $test_change, 2 ),
			'comparison_change_percent' => null === $comparison_change ? null : round( $comparison_change, 2 ),
			'adjusted_change_percent' => round( $adjusted_change, 2 ),
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

	private function portfolio_data_quality() {
		$status = $this->status();
		$issues = array();
		$score = 100;
		if ( empty( Ikon_SEO_Plugin::settings()['gsc_property'] ) ) {
			$issues[] = 'Search Console is not configured, so experiment search metrics may be unavailable.';
			$score -= 25;
		}
		if ( empty( Ikon_SEO_Plugin::settings()['ga_property'] ) ) {
			$issues[] = 'Analytics is not configured, so on-site outcome metrics may be unavailable.';
			$score -= 25;
		}
		if ( empty( $status['revenue_events'] ) ) {
			$issues[] = 'No attribution events are stored for the current reporting period.';
			$score -= 20;
		}
		if ( ! empty( $status['claims_due'] ) ) {
			$issues[] = 'Some claim records are due for verification.';
			$score -= min( 20, absint( $status['claims_due'] ) );
		}
		$score = max( 0, min( 100, $score ) );
		return array( 'score' => $score, 'confidence' => $score >= 80 ? 'high' : ( $score >= 55 ? 'medium' : 'low' ), 'issues' => $issues );
	}

	private function latest_measurement( $experiment_id ) {
		global $wpdb;
		$measurements_table = $this->measurements_table();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$measurements_table} WHERE experiment_id = %d ORDER BY measured_at DESC,id DESC LIMIT 1", absint( $experiment_id ) ), ARRAY_A );
		return $row ? $this->hydrate_measurement( $row ) : array();
	}

	private function baseline_measurement( $experiment_id ) {
		global $wpdb;
		$measurements_table = $this->measurements_table();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$measurements_table} WHERE experiment_id = %d AND phase = 'baseline' ORDER BY measured_at ASC,id ASC LIMIT 1", absint( $experiment_id ) ), ARRAY_A );
		return $row ? $this->hydrate_measurement( $row ) : array();
	}

	private function hydrate_experiment( array $row ) {
		$row['id'] = absint( $row['id'] ?? 0 );
		$row['test_urls'] = $this->decode_json_array( $row['test_urls_json'] ?? '[]' );
		$row['comparison_urls'] = $this->decode_json_array( $row['comparison_urls_json'] ?? '[]' );
		$row['secondary_metrics'] = $this->decode_json_array( $row['secondary_metrics_json'] ?? '[]' );
		unset( $row['test_urls_json'], $row['comparison_urls_json'], $row['secondary_metrics_json'] );
		return $row;
	}

	private function hydrate_measurement( array $row ) {
		$row['id'] = absint( $row['id'] ?? 0 );
		$row['experiment_id'] = absint( $row['experiment_id'] ?? 0 );
		$row['metrics'] = $this->decode_json_assoc( $row['metrics_json'] ?? '{}' );
		$row['comparison_metrics'] = $this->decode_json_assoc( $row['comparison_metrics_json'] ?? '{}' );
		$row['data_quality'] = $this->decode_json_assoc( $row['data_quality_json'] ?? '{}' );
		unset( $row['metrics_json'], $row['comparison_metrics_json'], $row['data_quality_json'] );
		return $row;
	}

	private function active_url_overlap( array $urls, $exclude_id = 0 ) {
		global $wpdb;
		if ( ! $urls ) {
			return array();
		}
		$experiments_table = $this->experiments_table();
		$rows = $wpdb->get_results( "SELECT id,title,test_urls_json,comparison_urls_json FROM {$experiments_table} WHERE status IN ('approved','running','monitoring')", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$matches = array();
		foreach ( (array) $rows as $row ) {
			if ( absint( $row['id'] ) === absint( $exclude_id ) ) {
				continue;
			}
			$existing = array_merge( $this->decode_json_array( $row['test_urls_json'] ?? '[]' ), $this->decode_json_array( $row['comparison_urls_json'] ?? '[]' ) );
			if ( array_intersect( $urls, $existing ) ) {
				$matches[] = sanitize_text_field( $row['title'] ?? '' );
			}
		}
		return array_values( array_unique( array_filter( $matches ) ) );
	}

	private function mark_overdue_claims() {
		global $wpdb;
		if ( ! $this->table_exists( $this->claims_table() ) ) {
			return;
		}
		$now = current_time( 'mysql', true );
		$claims_table = $this->claims_table();
		$wpdb->query( $wpdb->prepare( "UPDATE {$claims_table} SET status = 'overdue', updated_at = %s WHERE status IN ('verified','needs_review') AND review_due_at IS NOT NULL AND review_due_at <= %s", $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private function refresh_experiment_states() {
		global $wpdb;
		if ( ! $this->table_exists( $this->experiments_table() ) ) {
			return;
		}
		$today = gmdate( 'Y-m-d' );
		$experiments_table = $this->experiments_table();
		$wpdb->query( $wpdb->prepare( "UPDATE {$experiments_table} SET status = 'monitoring', updated_at = %s WHERE status = 'running' AND end_date IS NOT NULL AND end_date <= %s", current_time( 'mysql', true ), $today ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private function history_event( $category, $status, $title, $summary, array $details, $user_id ) {
		if ( ! $user_id ) {
			return;
		}
		$this->history->add(
			array(
				'category' => sanitize_key( $category ),
				'status' => sanitize_key( $status ),
				'title' => sanitize_text_field( $title ),
				'summary' => sanitize_textarea_field( $summary ),
				'details' => $details,
			),
			'experiments',
			$user_id
		);
	}

	private function sanitize_site_urls( array $urls ) {
		$clean = array();
		foreach ( $urls as $url ) {
			$url = esc_url_raw( is_array( $url ) ? ( $url['url'] ?? '' ) : $url );
			if ( $url && $this->url_belongs_to_site( $url ) ) {
				$clean[] = $this->normalize_url( $url );
			}
		}
		return array_values( array_unique( $clean ) );
	}

	private function url_belongs_to_site( $url ) {
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$url_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return $home_host && $url_host && $home_host === $url_host && wp_http_validate_url( $url );
	}

	private function normalize_url( $url ) {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return '';
		}
		$scheme = strtolower( $parts['scheme'] ?? 'https' );
		$host = strtolower( $parts['host'] );
		$port = isset( $parts['port'] ) ? ':' . absint( $parts['port'] ) : '';
		$path = isset( $parts['path'] ) && '' !== $parts['path'] ? $parts['path'] : '/';
		$query = isset( $parts['query'] ) && '' !== $parts['query'] ? '?' . $parts['query'] : '';
		return $scheme . '://' . $host . $port . $path . $query;
	}

	private function sanitize_metrics( array $metrics ) {
		$clean = array();
		foreach ( array_merge( $this->allowed_metrics(), array( 'observations', 'qualified_leads', 'customers' ) ) as $metric ) {
			if ( array_key_exists( $metric, $metrics ) ) {
				$clean[ $metric ] = (float) $metrics[ $metric ];
			}
		}
		return $clean;
	}

	private function sanitize_metadata( array $metadata ) {
		$blocked = array( 'name', 'email', 'phone', 'address', 'customer_name', 'customer_email', 'customer_phone', 'message', 'notes' );
		$clean = array();
		foreach ( array_slice( $metadata, 0, 30, true ) as $key => $value ) {
			$key = sanitize_key( $key );
			if ( ! $key || in_array( $key, $blocked, true ) || is_array( $value ) || is_object( $value ) ) {
				continue;
			}
			$clean[ $key ] = sanitize_text_field( (string) $value );
		}
		return $clean;
	}

	private function sanitize_currency( $currency ) {
		$currency = preg_replace( '/[^A-Z]/', '', strtoupper( sanitize_text_field( $currency ) ) );
		return 3 === strlen( $currency ) ? $currency : 'USD';
	}

	private function date_or_null( $value ) {
		$value = sanitize_text_field( (string) $value );
		if ( ! $value ) {
			return null;
		}
		$timestamp = strtotime( $value );
		return false === $timestamp ? null : gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	private function decode_json_array( $json ) {
		$data = json_decode( (string) $json, true );
		return is_array( $data ) ? array_values( $data ) : array();
	}

	private function decode_json_assoc( $json ) {
		$data = json_decode( (string) $json, true );
		return is_array( $data ) ? $data : array();
	}

	private function allowed_metrics() {
		return array( 'clicks', 'impressions', 'ctr', 'position', 'sessions', 'active_users', 'views', 'key_events', 'qualified_leads', 'customers', 'revenue' );
	}

	private function experiment_statuses() {
		return array( 'draft', 'approved', 'running', 'monitoring', 'completed', 'cancelled', 'archived' );
	}

	private function claim_statuses() {
		return array( 'needs_review', 'verified', 'overdue', 'disputed', 'unsupported', 'retired', 'dismissed' );
	}

	private function claim_types() {
		return array( 'factual', 'statistic', 'legal', 'medical', 'financial', 'pricing', 'product', 'service', 'other' );
	}

	private function source_types() {
		return array( 'primary', 'official', 'secondary', 'internal', 'expert_review', 'other' );
	}

	private function tables_ready() {
		return $this->table_exists( $this->experiments_table() ) && $this->table_exists( $this->measurements_table() ) && $this->table_exists( $this->claims_table() ) && $this->table_exists( $this->revenue_table() );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
