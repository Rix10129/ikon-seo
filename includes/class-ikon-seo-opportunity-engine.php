<?php

defined( 'ABSPATH' ) || exit;

/**
 * Unifies read-only SEO evidence into a durable, prioritised opportunity queue.
 *
 * The engine never publishes, redirects, deletes or changes indexing controls.
 * It stores evidence, explains scoring and requires a human status decision.
 */
final class Ikon_SEO_Opportunity_Engine {
	const CRON_HOOK = 'ikon_seo_opportunity_engine_refresh';
	const CACHE_KEY = 'ikon_seo_opportunity_engine_report_v1';

	private $search_intelligence;
	private $analytics;
	private $technical;
	private $indexation;
	private $competitor_content;
	private $authority;
	private $inventory;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Analytics $analytics,
		Ikon_SEO_Technical_Intelligence $technical,
		Ikon_SEO_Indexation_Intelligence $indexation,
		Ikon_SEO_Competitor_Content_Intelligence $competitor_content,
		Ikon_SEO_Authority_Intelligence $authority,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->search_intelligence = $search_intelligence;
		$this->analytics = $analytics;
		$this->technical = $technical;
		$this->indexation = $indexation;
		$this->competitor_content = $competitor_content;
		$this->authority = $authority;
		$this->inventory = $inventory;
		$this->history = $history;
		$this->logger = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_rebuild' ) );
	}

	public function scheduled_rebuild() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['opportunity_engine_enabled'] ) ) {
			return;
		}
		$result = $this->rebuild( absint( $settings['opportunity_engine_max_items'] ?? 300 ), 0, 'scheduled' );
		if ( is_wp_error( $result ) ) {
			$this->remember_error( $result->get_error_message() );
		}
	}

	public function evidence_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_keyword_evidence';
	}

	public function opportunities_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_opportunities';
	}

	public function status() {
		global $wpdb;
		$evidence_ready = $this->table_exists( $this->evidence_table() );
		$opportunities_ready = $this->table_exists( $this->opportunities_table() );
		$settings = Ikon_SEO_Plugin::settings();
		$counts = array( 'open' => 0, 'reviewed' => 0, 'planned' => 0, 'completed' => 0, 'dismissed' => 0, 'total' => 0 );
		if ( $opportunities_ready ) {
			$rows = $wpdb->get_results( "SELECT status,COUNT(*) total FROM {$this->opportunities_table()} WHERE is_current=1 GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( (array) $rows as $row ) {
				$key = sanitize_key( $row['status'] ?? '' );
				if ( isset( $counts[ $key ] ) ) {
					$counts[ $key ] = absint( $row['total'] ?? 0 );
				}
			}
			$counts['total'] = array_sum( array_intersect_key( $counts, array_flip( array( 'open', 'reviewed', 'planned', 'completed', 'dismissed' ) ) ) );
		}
		$source_counts = array();
		if ( $evidence_ready ) {
			$rows = $wpdb->get_results( "SELECT source,COUNT(*) total FROM {$this->evidence_table()} WHERE status='active' GROUP BY source", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( (array) $rows as $row ) {
				$source_counts[ sanitize_key( $row['source'] ?? 'unknown' ) ] = absint( $row['total'] ?? 0 );
			}
		}
		return array(
			'enabled' => ! empty( $settings['opportunity_engine_enabled'] ),
			'database_ready' => $evidence_ready && $opportunities_ready,
			'last_rebuild' => sanitize_text_field( $settings['opportunity_engine_last_rebuild'] ?? '' ),
			'last_error' => sanitize_text_field( $settings['opportunity_engine_last_error'] ?? '' ),
			'stale_days' => absint( $settings['opportunity_engine_stale_days'] ?? 60 ),
			'counts' => $counts,
			'imported_evidence' => array_sum( $source_counts ),
			'evidence_sources' => $source_counts,
			'read_only_analysis' => true,
			'requires_approval' => true,
		);
	}

	public function sync( array $payload, $created_by = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		if ( 'rebuild' === $command ) {
			return $this->rebuild( absint( $payload['limit'] ?? 300 ), $created_by, 'workspace' );
		}
		if ( 'import' === $command ) {
			$records = isset( $payload['records'][0] ) ? (array) $payload['records'] : array( $payload['records'] ?? array() );
			return $this->import_records( $records, sanitize_key( $payload['source'] ?? 'manual' ), $created_by );
		}
		if ( 'update_status' === $command ) {
			return $this->update_status(
				absint( $payload['opportunity_id'] ?? 0 ),
				sanitize_key( $payload['status'] ?? '' ),
				sanitize_textarea_field( $payload['notes'] ?? '' ),
				$created_by
			);
		}
		if ( 'archive_evidence' === $command ) {
			return $this->archive_evidence( absint( $payload['evidence_id'] ?? 0 ) );
		}
		return $this->report( array( 'limit' => absint( $payload['limit'] ?? 100 ) ) );
	}

	public function import_csv( $path, $source = 'manual', $created_by = 0 ) {
		if ( ! is_readable( $path ) ) {
			return new WP_Error( 'ikon_seo_opportunity_csv', __( 'The uploaded evidence file could not be read.', 'ikon-seo' ) );
		}
		$handle = fopen( $path, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'ikon_seo_opportunity_csv', __( 'The uploaded evidence file could not be opened.', 'ikon-seo' ) );
		}
		$header = fgetcsv( $handle );
		if ( ! is_array( $header ) ) {
			fclose( $handle );
			return new WP_Error( 'ikon_seo_opportunity_csv_header', __( 'The evidence CSV does not contain a header row.', 'ikon-seo' ) );
		}
		$normalized_header = array_map( array( $this, 'normalize_header' ), $header );
		$records = array();
		while ( ( $row = fgetcsv( $handle ) ) !== false && count( $records ) < 5000 ) {
			if ( ! array_filter( $row, 'strlen' ) ) {
				continue;
			}
			$record = array();
			foreach ( $normalized_header as $index => $key ) {
				if ( $key ) {
					$record[ $key ] = $row[ $index ] ?? '';
				}
			}
			$records[] = $record;
		}
		fclose( $handle );
		return $this->import_records( $records, $source, $created_by );
	}

	public function import_records( array $records, $source = 'manual', $created_by = 0 ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->evidence_table() ) ) {
			return new WP_Error( 'ikon_seo_opportunity_tables', __( 'The Opportunity Engine tables are not ready. Update or reactivate Ikon SEO.', 'ikon-seo' ) );
		}
		$source = $this->sanitize_source( $source );
		if ( count( $records ) > 5000 ) {
			return new WP_Error( 'ikon_seo_opportunity_import_limit', __( 'A maximum of 5,000 keyword evidence rows can be imported at once.', 'ikon-seo' ) );
		}
		$imported = 0;
		$skipped = 0;
		$now = current_time( 'mysql', true );
		foreach ( $records as $record ) {
			$record = $this->normalize_import_record( (array) $record, $source );
			if ( empty( $record['keyword'] ) ) {
				$skipped++;
				continue;
			}
			$evidence_hash = hash( 'sha256', implode( '|', array(
				$source,
				$this->normalize_text( $record['keyword'] ),
				$this->url_key( $record['target_url'] ),
				strtolower( $record['competitor_domain'] ),
				$record['country'],
				$record['device'],
				$record['observed_at'],
			) ) );
			$sql = "INSERT INTO {$this->evidence_table()}
				(evidence_hash,source,keyword,target_url,competitor_domain,country,device,intent,search_volume,keyword_difficulty,position,previous_position,estimated_traffic,cpc,serp_features_json,evidence_notes,observed_at,status,created_by,created_at,updated_at)
				VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%f,%f,%f,%f,%f,%f,%s,%s,%s,'active',%d,%s,%s)
				ON DUPLICATE KEY UPDATE intent=VALUES(intent),search_volume=VALUES(search_volume),keyword_difficulty=VALUES(keyword_difficulty),position=VALUES(position),previous_position=VALUES(previous_position),estimated_traffic=VALUES(estimated_traffic),cpc=VALUES(cpc),serp_features_json=VALUES(serp_features_json),evidence_notes=VALUES(evidence_notes),status='active',updated_at=VALUES(updated_at)";
			$result = $wpdb->query( $wpdb->prepare(
				$sql,
				$evidence_hash,
				$source,
				$record['keyword'],
				$record['target_url'],
				$record['competitor_domain'],
				$record['country'],
				$record['device'],
				$record['intent'],
				$record['search_volume'],
				$record['keyword_difficulty'],
				$record['position'],
				$record['previous_position'],
				$record['estimated_traffic'],
				$record['cpc'],
				wp_json_encode( $record['serp_features'] ),
				$record['evidence_notes'],
				$record['observed_at'],
				absint( $created_by ),
				$now,
				$now
			) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( false === $result ) {
				$skipped++;
			} else {
				$imported++;
			}
		}
		delete_transient( self::CACHE_KEY );
		$this->history->add(
			array(
				'category' => 'research',
				'title' => 'Keyword evidence imported',
				'summary' => sprintf( '%d rows were imported from %s; %d rows were skipped.', $imported, $source, $skipped ),
				'details' => array( 'source' => $source, 'imported' => $imported, 'skipped' => $skipped ),
			),
			'opportunity_engine',
			$created_by
		);
		$this->logger->log( 'opportunity_evidence_import', 'success', sprintf( 'Imported %d %s keyword evidence rows.', $imported, $source ) );
		return array( 'source' => $source, 'imported' => $imported, 'skipped' => $skipped );
	}

	public function rebuild( $limit = 300, $created_by = 0, $source = 'manual' ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->opportunities_table() ) || ! $this->table_exists( $this->evidence_table() ) ) {
			return new WP_Error( 'ikon_seo_opportunity_tables', __( 'The Opportunity Engine tables are not ready. Update or reactivate Ikon SEO.', 'ikon-seo' ) );
		}
		$limit = max( 25, min( 1000, absint( $limit ) ) );
		$candidates = array();
		$source_health = array();

		$this->collect_search_candidates( $candidates, $source_health, $limit );
		$this->collect_analytics_candidates( $candidates, $source_health, $limit );
		$this->collect_competitor_candidates( $candidates, $source_health, $limit );
		$this->collect_external_keyword_candidates( $candidates, $source_health, $limit );
		$this->collect_indexation_candidates( $candidates, $source_health, $limit );
		$this->collect_technical_candidates( $candidates, $source_health, $limit );
		$this->collect_authority_candidates( $candidates, $source_health, $limit );

		usort( $candidates, function( $a, $b ) { return (int) $b['priority'] <=> (int) $a['priority']; } );
		$candidates = array_slice( $candidates, 0, $limit );
		$table = $this->opportunities_table();
		$wpdb->query( "UPDATE {$table} SET is_current=0 WHERE is_current=1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$stored = 0;
		foreach ( $candidates as $candidate ) {
			if ( $this->store_candidate( $candidate ) ) {
				$stored++;
			}
		}
		$settings = Ikon_SEO_Plugin::settings();
		$settings['opportunity_engine_last_rebuild'] = current_time( 'mysql', true );
		$settings['opportunity_engine_last_error'] = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		delete_transient( self::CACHE_KEY );
		$this->history->add(
			array(
				'category' => 'recommendation',
				'title' => 'Opportunity Engine rebuilt',
				'summary' => sprintf( '%d current opportunities were generated from connected and imported evidence.', $stored ),
				'details' => array( 'stored' => $stored, 'sources' => $source_health, 'trigger' => sanitize_key( $source ) ),
			),
			'opportunity_engine',
			$created_by
		);
		$this->logger->log( 'opportunity_engine', 'success', sprintf( 'Generated %d current opportunities.', $stored ) );
		return array( 'generated' => $stored, 'sources' => $source_health, 'report' => $this->report( array( 'limit' => min( 100, $limit ) ), true ) );
	}

	public function update_status( $id, $status, $notes = '', $user_id = 0 ) {
		global $wpdb;
		$id = absint( $id );
		$status = sanitize_key( $status );
		if ( ! $id || ! in_array( $status, array( 'open', 'reviewed', 'planned', 'completed', 'dismissed' ), true ) ) {
			return new WP_Error( 'ikon_seo_opportunity_status', __( 'Choose a valid opportunity and status.', 'ikon-seo' ) );
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->opportunities_table()} WHERE id=%d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) {
			return new WP_Error( 'ikon_seo_opportunity_missing', __( 'The opportunity could not be found.', 'ikon-seo' ) );
		}
		$now = current_time( 'mysql', true );
		$result = $wpdb->update(
			$this->opportunities_table(),
			array(
				'status' => $status,
				'review_notes' => sanitize_textarea_field( $notes ),
				'reviewed_by' => absint( $user_id ),
				'reviewed_at' => $now,
				'updated_at' => $now,
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_opportunity_update', __( 'The opportunity status could not be saved.', 'ikon-seo' ) );
		}
		delete_transient( self::CACHE_KEY );
		$this->history->add(
			array(
				'category' => 'task',
				'status' => 'completed' === $status ? 'completed' : ( 'dismissed' === $status ? 'dismissed' : 'open' ),
				'title' => 'Opportunity status updated',
				'summary' => sprintf( '%s was marked %s.', sanitize_text_field( $row['title'] ?? 'Opportunity' ), $status ),
				'details' => array( 'opportunity_id' => $id, 'status' => $status, 'notes' => $notes ),
				'related_post_id' => absint( $row['post_id'] ?? 0 ),
			),
			'opportunity_engine',
			$user_id
		);
		return $this->get_opportunity( $id );
	}

	/**
	 * Read one formatted opportunity for an approval-gated downstream workflow.
	 */
	public function opportunity( $id ) {
		return $this->get_opportunity( absint( $id ) );
	}

	public function archive_evidence( $id ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'ikon_seo_opportunity_evidence', __( 'Choose a valid evidence row.', 'ikon-seo' ) );
		}
		$result = $wpdb->update( $this->evidence_table(), array( 'status' => 'archived', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id ), array( '%s', '%s' ), array( '%d' ) );
		delete_transient( self::CACHE_KEY );
		return array( 'archived' => false !== $result, 'evidence_id' => $id );
	}

	public function report( array $args = array(), $refresh = false ) {
		global $wpdb;
		if ( $refresh ) {
			delete_transient( self::CACHE_KEY );
		}
		$has_filters = ! empty( $args['status'] ) || ! empty( $args['category'] ) || ! empty( $args['source'] );
		if ( ! $refresh && ! $has_filters ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$status = $this->status();
		if ( empty( $status['database_ready'] ) ) {
			return array( 'status' => $status, 'summary' => array(), 'opportunities' => array(), 'source_health' => array(), 'limitations' => array( 'Update or reactivate Ikon SEO to create the Opportunity Engine tables.' ) );
		}
		$limit = max( 10, min( 500, absint( $args['limit'] ?? 100 ) ) );
		$where = array( 'is_current=1' );
		$params = array();
		$requested_status = sanitize_key( $args['status'] ?? '' );
		if ( in_array( $requested_status, array( 'open', 'reviewed', 'planned', 'completed', 'dismissed' ), true ) ) {
			$where[] = 'status=%s';
			$params[] = $requested_status;
		}
		$category = sanitize_key( $args['category'] ?? '' );
		if ( $category ) {
			$where[] = 'category=%s';
			$params[] = $category;
		}
		$source = sanitize_key( $args['source'] ?? '' );
		if ( $source ) {
			$where[] = 'primary_source=%s';
			$params[] = $source;
		}
		$params[] = $limit;
		$sql = "SELECT * FROM {$this->opportunities_table()} WHERE " . implode( ' AND ', $where ) . ' ORDER BY priority DESC,confidence_score DESC,last_seen_at DESC LIMIT %d';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$opportunities = array_map( array( $this, 'format_opportunity' ), $rows ?: array() );
		$category_counts = array();
		$source_counts = array();
		$priority_bands = array( 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0 );
		$summary_rows = $wpdb->get_results( "SELECT category,primary_source,priority,status FROM {$this->opportunities_table()} WHERE is_current=1", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( (array) $summary_rows as $row ) {
			$cat = sanitize_key( $row['category'] ?? 'other' );
			$src = sanitize_key( $row['primary_source'] ?? 'unknown' );
			$category_counts[ $cat ] = ( $category_counts[ $cat ] ?? 0 ) + 1;
			$source_counts[ $src ] = ( $source_counts[ $src ] ?? 0 ) + 1;
			$priority = absint( $row['priority'] ?? 0 );
			$band = $priority >= 85 ? 'critical' : ( $priority >= 70 ? 'high' : ( $priority >= 50 ? 'medium' : 'low' ) );
			$priority_bands[ $band ]++;
		}
		arsort( $category_counts );
		arsort( $source_counts );
		$report = array(
			'generated_at' => current_time( 'mysql', true ),
			'status' => $status,
			'summary' => array(
				'current_opportunities' => count( $summary_rows ),
				'actionable' => absint( $status['counts']['open'] ?? 0 ) + absint( $status['counts']['reviewed'] ?? 0 ) + absint( $status['counts']['planned'] ?? 0 ),
				'priority_bands' => $priority_bands,
				'categories' => $category_counts,
				'sources' => $source_counts,
			),
			'opportunities' => $opportunities,
			'source_health' => $this->source_health(),
			'methodology' => 'Priority combines estimated impact, evidence confidence, freshness, implementation effort and change risk. It is an internal work-order score, not a Google ranking score or forecast.',
			'limitations' => array(
				'Imported provider metrics remain provider evidence and are not converted into guaranteed traffic or ranking outcomes.',
				'Search Console can omit anonymised and low-volume queries; Analytics depends on tracking quality and consent configuration.',
				'Competitor and backlink datasets are incomplete unless refreshed through an approved evidence source.',
				'Every recommendation requires human review. The Opportunity Engine cannot publish, redirect, delete, noindex or change canonical settings.',
			),
		);
		if ( ! $has_filters ) {
			set_transient( self::CACHE_KEY, $report, 15 * MINUTE_IN_SECONDS );
		}
		return $report;
	}

	private function collect_search_candidates( array &$candidates, array &$health, $limit ) {
		$report = $this->search_intelligence->report( false, min( 250, $limit ) );
		if ( is_wp_error( $report ) ) {
			$health['search_console'] = array( 'available' => false, 'message' => $report->get_error_message() );
			return;
		}
		$health['search_console'] = array( 'available' => true, 'generated_at' => sanitize_text_field( $report['generated_at'] ?? '' ), 'items' => absint( $report['summary']['queries'] ?? 0 ) );
		foreach ( (array) ( $report['striking_distance'] ?? array() ) as $item ) {
			$candidates[] = $this->candidate( array(
				'type' => 'striking_distance', 'category' => 'search_growth', 'source' => 'search_console',
				'title' => 'Strengthen a striking-distance query',
				'summary' => sprintf( '“%s” averages position %.1f with %.0f impressions.', sanitize_text_field( $item['query'] ?? '' ), (float) ( $item['position'] ?? 0 ), (float) ( $item['impressions'] ?? 0 ) ),
				'url' => $item['page'] ?? '', 'keyword' => $item['query'] ?? '', 'intent' => '',
				'impact' => absint( $item['priority'] ?? 65 ), 'confidence' => (float) ( $item['impressions'] ?? 0 ) >= 100 ? 'high' : 'medium', 'effort' => 'medium', 'risk' => 'low',
				'evidence' => $item, 'actions' => array( $item['recommended_action'] ?? 'Review current intent, evidence and internal links.' ),
			) );
		}
		foreach ( (array) ( $report['content_decay'] ?? array() ) as $item ) {
			$decline = abs( (float) ( $item['impressions_change'] ?? 0 ) );
			$candidates[] = $this->candidate( array(
				'type' => 'content_decay', 'category' => 'performance_recovery', 'source' => 'search_console',
				'title' => 'Investigate declining organic visibility',
				'summary' => sprintf( 'Organic impressions declined %.1f%% for this page.', $decline ),
				'url' => $item['page'] ?? '', 'keyword' => '', 'impact' => min( 95, 55 + (int) round( min( 40, $decline / 2 ) ) ),
				'confidence' => $item['confidence'] ?? 'medium', 'effort' => 'medium', 'risk' => 'medium', 'evidence' => $item,
				'actions' => array( $item['recommended_action'] ?? 'Review query losses, indexing and competing pages before editing.' ),
			) );
		}
		foreach ( (array) ( $report['cannibalisation'] ?? array() ) as $item ) {
			$pages = (array) ( $item['pages'] ?? array() );
			$candidates[] = $this->candidate( array(
				'type' => 'cannibalisation', 'category' => 'architecture', 'source' => 'search_console',
				'title' => 'Review overlapping pages for one query',
				'summary' => sprintf( '%d pages receive visibility for “%s”; classification: %s.', count( $pages ), sanitize_text_field( $item['query'] ?? '' ), str_replace( '_', ' ', sanitize_key( $item['classification'] ?? '' ) ) ),
				'url' => $pages[0]['page'] ?? '', 'keyword' => $item['query'] ?? '', 'impact' => 'high' === ( $item['confidence'] ?? '' ) ? 82 : 68,
				'confidence' => $item['confidence'] ?? 'medium', 'effort' => 'high', 'risk' => 'high', 'evidence' => $item,
				'actions' => array( $item['recommended_action'] ?? 'Compare intent before any merge, canonical or redirect decision.' ),
			) );
		}
	}

	private function collect_analytics_candidates( array &$candidates, array &$health, $limit ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_analytics_pages';
		if ( ! $this->table_exists( $table ) ) {
			$health['analytics'] = array( 'available' => false, 'message' => 'Analytics evidence table unavailable.' );
			return;
		}
		$latest = sanitize_text_field( $wpdb->get_var( "SELECT MAX(period_end) FROM {$table}" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $latest ) {
			$health['analytics'] = array( 'available' => false, 'message' => 'No stored Analytics landing-page evidence.' );
			return;
		}
		$previous = sanitize_text_field( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(period_end) FROM {$table} WHERE period_end<%s", $latest ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE period_end=%s ORDER BY sessions DESC LIMIT %d", $latest, min( 500, $limit * 2 ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$previous_map = array();
		if ( $previous ) {
			$old_rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE period_end=%s", $previous ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( (array) $old_rows as $old ) {
				$previous_map[ $old['page_hash'] ] = $old;
			}
		}
		$health['analytics'] = array( 'available' => true, 'generated_at' => $latest, 'items' => count( $rows ) );
		foreach ( (array) $rows as $row ) {
			$url = preg_match( '~^https?://~i', $row['page_path'] ?? '' ) ? $row['page_path'] : home_url( '/' . ltrim( strtok( (string) ( $row['page_path'] ?? '' ), '?' ), '/' ) );
			$sessions = (float) ( $row['sessions'] ?? 0 );
			$key_events = (float) ( $row['key_events'] ?? 0 );
			if ( $sessions >= 25 && $key_events <= 0 ) {
				$candidates[] = $this->candidate( array(
					'type' => 'conversion_measurement_gap', 'category' => 'conversion', 'source' => 'analytics',
					'title' => 'Review a high-traffic page with no measured key events',
					'summary' => sprintf( 'The landing page recorded %.0f sessions and no stored key events.', $sessions ),
					'url' => $url, 'impact' => min( 88, 50 + (int) round( log( max( 2, $sessions ), 2 ) * 6 ) ),
					'confidence' => 'medium', 'effort' => 'low', 'risk' => 'low', 'evidence' => $row,
					'actions' => array( 'Confirm whether the page should generate leads or revenue, then verify Analytics event configuration and the on-page conversion path.' ),
				) );
			}
			$old = $previous_map[ $row['page_hash'] ] ?? array();
			$old_sessions = (float) ( $old['sessions'] ?? 0 );
			if ( $old_sessions >= 25 && $sessions < $old_sessions * 0.7 ) {
				$change = $old_sessions > 0 ? round( ( ( $sessions - $old_sessions ) / $old_sessions ) * 100, 1 ) : 0;
				$candidates[] = $this->candidate( array(
					'type' => 'landing_page_decline', 'category' => 'performance_recovery', 'source' => 'analytics',
					'title' => 'Investigate a landing-page traffic decline',
					'summary' => sprintf( 'Stored landing-page sessions changed %.1f%% compared with the previous snapshot.', $change ),
					'url' => $url, 'impact' => min( 90, 55 + (int) min( 35, abs( $change ) / 2 ) ), 'confidence' => 'medium', 'effort' => 'medium', 'risk' => 'low',
					'evidence' => array( 'current' => $row, 'previous' => $old, 'change_percent' => $change ),
					'actions' => array( 'Compare Search Console demand, tracking changes, seasonality, referral mix and page changes before diagnosing the decline.' ),
				) );
			}
		}
	}

	private function collect_competitor_candidates( array &$candidates, array &$health, $limit ) {
		$report = $this->competitor_content->report( min( 250, $limit ), false );
		if ( is_wp_error( $report ) ) {
			$health['competitor_content'] = array( 'available' => false, 'message' => $report->get_error_message() );
			return;
		}
		$health['competitor_content'] = array( 'available' => true, 'generated_at' => sanitize_text_field( $report['generated_at'] ?? '' ), 'items' => absint( $report['summary']['research_items'] ?? 0 ) );
		foreach ( (array) ( $report['content_briefs'] ?? array() ) as $brief ) {
			$gap = absint( $brief['gap_priority'] ?? 0 );
			if ( $gap < 55 ) {
				continue;
			}
			$candidates[] = $this->candidate( array(
				'type' => 'competitor_content_gap', 'category' => 'content_gap', 'source' => 'competitor_content',
				'title' => 'Review a supported content coverage gap',
				'summary' => sprintf( 'The stored brief for “%s” has a gap priority of %d.', sanitize_text_field( $brief['target_query'] ?? '' ), $gap ),
				'url' => $brief['page_url'] ?? '', 'keyword' => $brief['target_query'] ?? '', 'intent' => $brief['target_intent'] ?? '',
				'impact' => $gap, 'confidence' => $brief['evidence_confidence'] ?? 'medium', 'effort' => 'medium', 'risk' => 'low', 'evidence' => $brief,
				'actions' => array( 'Validate recurring topics against the business and search intent, then prepare a differentiated page brief without copying competitor wording.' ),
			) );
		}
		foreach ( (array) ( $report['topic_map'] ?? array() ) as $topic ) {
			if ( 'missing_page' !== ( $topic['status'] ?? '' ) || (float) ( $topic['impressions'] ?? 0 ) < 20 ) {
				continue;
			}
			$candidates[] = $this->candidate( array(
				'type' => 'missing_topic_page', 'category' => 'content_gap', 'source' => 'search_console',
				'title' => 'Review demand without a mapped page',
				'summary' => sprintf( 'The topic “%s” has %.0f stored impressions but no mapped leading page.', sanitize_text_field( $topic['topic'] ?? '' ), (float) ( $topic['impressions'] ?? 0 ) ),
				'keyword' => $topic['topic'] ?? '', 'intent' => $topic['intent'] ?? '', 'impact' => min( 88, 50 + (int) round( log( max( 2, (float) $topic['impressions'] ), 2 ) * 5 ) ),
				'confidence' => 'medium', 'effort' => 'high', 'risk' => 'medium', 'evidence' => $topic,
				'actions' => array( 'Confirm the topic belongs to the approved strategy and is not already served by another page before creating a page plan.' ),
			) );
		}
	}

	private function collect_external_keyword_candidates( array &$candidates, array &$health, $limit ) {
		global $wpdb;
		$table = $this->evidence_table();
		$stale_days = max( 7, absint( Ikon_SEO_Plugin::settings()['opportunity_engine_stale_days'] ?? 60 ) );
		$cutoff = gmdate( 'Y-m-d', time() - $stale_days * DAY_IN_SECONDS );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status='active' ORDER BY observed_at DESC,search_volume DESC LIMIT %d", min( 5000, $limit * 10 ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$health['external_keyword_evidence'] = array( 'available' => ! empty( $rows ), 'items' => count( $rows ), 'stale_cutoff' => $cutoff );
		foreach ( (array) $rows as $row ) {
			$volume = (float) ( $row['search_volume'] ?? 0 );
			$position = (float) ( $row['position'] ?? 0 );
			$previous_position = (float) ( $row['previous_position'] ?? 0 );
			$is_stale = ! empty( $row['observed_at'] ) && $row['observed_at'] < $cutoff;
			$confidence = $is_stale ? 'low' : ( $volume >= 100 ? 'high' : 'medium' );
			if ( $position >= 4 && $position <= 20 && ! empty( $row['target_url'] ) ) {
				$candidates[] = $this->candidate( array(
					'type' => 'provider_keyword_growth', 'category' => 'search_growth', 'source' => $row['source'],
					'title' => 'Review an imported keyword growth opportunity',
					'summary' => sprintf( '“%s” is reported at position %.1f with estimated volume %.0f.', sanitize_text_field( $row['keyword'] ?? '' ), $position, $volume ),
					'url' => $row['target_url'], 'keyword' => $row['keyword'], 'intent' => $row['intent'],
					'impact' => min( 92, 48 + (int) min( 35, log( max( 2, $volume ), 2 ) * 5 ) + (int) max( 0, 20 - $position ) ),
					'confidence' => $confidence, 'effort' => 'medium', 'risk' => 'low', 'observed_at' => $row['observed_at'], 'evidence' => $row,
					'actions' => array( 'Verify current Search Console and live SERP evidence before changing the page.' ),
				) );
			} elseif ( empty( $row['target_url'] ) && $volume >= 20 ) {
				$candidates[] = $this->candidate( array(
					'type' => ! empty( $row['competitor_domain'] ) ? 'competitor_keyword_gap' : 'keyword_gap', 'category' => 'content_gap', 'source' => $row['source'],
					'title' => ! empty( $row['competitor_domain'] ) ? 'Review a competitor keyword gap' : 'Review an imported keyword gap',
					'summary' => sprintf( '“%s” has estimated volume %.0f%s.', sanitize_text_field( $row['keyword'] ?? '' ), $volume, ! empty( $row['competitor_domain'] ) ? ' and is associated with ' . sanitize_text_field( $row['competitor_domain'] ) : '' ),
					'keyword' => $row['keyword'], 'intent' => $row['intent'], 'impact' => min( 90, 45 + (int) min( 40, log( max( 2, $volume ), 2 ) * 6 ) ),
					'confidence' => $confidence, 'effort' => 'high', 'risk' => 'medium', 'observed_at' => $row['observed_at'], 'evidence' => $row,
					'actions' => array( 'Confirm strategic relevance, current intent and existing coverage before creating a page plan.' ),
				) );
			}
			if ( $previous_position > 0 && $position > $previous_position + 5 && ! empty( $row['target_url'] ) ) {
				$candidates[] = $this->candidate( array(
					'type' => 'provider_position_loss', 'category' => 'performance_recovery', 'source' => $row['source'],
					'title' => 'Verify an imported ranking loss',
					'summary' => sprintf( '“%s” moved from %.1f to %.1f in the imported dataset.', sanitize_text_field( $row['keyword'] ?? '' ), $previous_position, $position ),
					'url' => $row['target_url'], 'keyword' => $row['keyword'], 'intent' => $row['intent'], 'impact' => min( 90, 55 + (int) min( 30, $position - $previous_position ) ),
					'confidence' => $confidence, 'effort' => 'medium', 'risk' => 'low', 'observed_at' => $row['observed_at'], 'evidence' => $row,
					'actions' => array( 'Confirm the loss in Search Console and current results before diagnosing or editing the page.' ),
				) );
			}
		}
	}

	private function collect_indexation_candidates( array &$candidates, array &$health, $limit ) {
		$report = $this->indexation->report( min( 250, $limit ) );
		$health['indexation'] = array( 'available' => true, 'generated_at' => sanitize_text_field( $report['generated_at'] ?? '' ), 'items' => count( (array) ( $report['recommendations'] ?? array() ) ) );
		foreach ( (array) ( $report['recommendations'] ?? array() ) as $item ) {
			$candidates[] = $this->candidate( array(
				'type' => $item['code'] ?? 'indexation_review', 'category' => 'indexation', 'source' => 'search_console_inspection',
				'title' => $item['title'] ?? 'Review indexation evidence', 'summary' => $item['summary'] ?? '', 'url' => $item['url'] ?? '', 'post_id' => $item['post_id'] ?? 0,
				'impact' => absint( $item['priority'] ?? 75 ), 'confidence' => $item['confidence'] ?? 'high', 'effort' => 'medium', 'risk' => 'high', 'evidence' => $item['evidence'] ?? $item,
				'actions' => array( $item['recommended_action'] ?? 'Review the inspection evidence before changing indexing controls.' ),
			) );
		}
	}

	private function collect_technical_candidates( array &$candidates, array &$health, $limit ) {
		$report = $this->technical->report( false, min( 250, $limit ) );
		if ( is_wp_error( $report ) ) {
			$health['technical'] = array( 'available' => false, 'message' => $report->get_error_message() );
			return;
		}
		$health['technical'] = array( 'available' => true, 'generated_at' => sanitize_text_field( $report['generated_at'] ?? '' ), 'items' => absint( $report['status']['urls'] ?? 0 ) );
		foreach ( array_slice( (array) ( $report['broken_urls'] ?? array() ), 0, 100 ) as $item ) {
			$candidates[] = $this->candidate( array(
				'type' => 'broken_url', 'category' => 'technical', 'source' => 'technical_crawl', 'title' => 'Resolve a broken or unreachable URL',
				'summary' => sprintf( 'The stored status is %d with %d inbound links.', absint( $item['status_code'] ?? 0 ), absint( $item['inbound_links'] ?? 0 ) ),
				'url' => $item['url'] ?? '', 'impact' => min( 98, 65 + min( 30, absint( $item['inbound_links'] ?? 0 ) * 3 ) ), 'confidence' => 'high', 'effort' => 'medium', 'risk' => 'medium', 'evidence' => $item,
				'actions' => array( 'Confirm whether the URL should be restored, internally relinked, or redirected. Any redirect requires explicit approval.' ),
			) );
		}
		foreach ( array_slice( (array) ( $report['orphan_pages'] ?? array() ), 0, 100 ) as $item ) {
			$candidates[] = $this->candidate( array(
				'type' => 'orphan_page', 'category' => 'architecture', 'source' => 'technical_crawl', 'title' => 'Review a page outside the stored internal-link graph',
				'summary' => 'The page was not reachable from the current stored internal-link graph.', 'url' => $item['url'] ?? '', 'post_id' => $item['post_id'] ?? 0,
				'impact' => 66, 'confidence' => 'medium', 'effort' => 'low', 'risk' => 'low', 'evidence' => $item,
				'actions' => array( 'Confirm the page is strategically useful, then review navigation and contextual internal-link opportunities.' ),
			) );
		}
		foreach ( array_slice( (array) ( $report['redirect_chains'] ?? array() ), 0, 50 ) as $item ) {
			$candidates[] = $this->candidate( array(
				'type' => 'redirect_chain', 'category' => 'technical', 'source' => 'technical_crawl', 'title' => 'Review a redirect chain or loop',
				'summary' => sanitize_text_field( $item['chain_text'] ?? '' ), 'url' => $item['url'] ?? '', 'impact' => ! empty( $item['loop'] ) ? 95 : min( 86, 65 + absint( $item['hops'] ?? 1 ) * 5 ),
				'confidence' => 'high', 'effort' => 'medium', 'risk' => 'high', 'evidence' => $item,
				'actions' => array( 'Map the intended final destination and obtain approval before changing any redirect.' ),
			) );
		}
		foreach ( array_slice( (array) ( $report['pagespeed'] ?? array() ), 0, 50 ) as $item ) {
			$score = absint( $item['performance_score'] ?? 0 );
			if ( $score <= 0 || $score >= 70 ) {
				continue;
			}
			$candidates[] = $this->candidate( array(
				'type' => 'pagespeed', 'category' => 'performance', 'source' => 'pagespeed', 'title' => 'Review poor stored page-performance evidence',
				'summary' => sprintf( 'The stored performance score is %d for the %s strategy.', $score, sanitize_text_field( $item['strategy'] ?? 'mobile' ) ), 'url' => $item['url'] ?? '',
				'impact' => min( 88, 55 + ( 70 - $score ) ), 'confidence' => ! empty( $item['field_data_available'] ) ? 'high' : 'medium', 'effort' => 'high', 'risk' => 'medium', 'evidence' => $item,
				'actions' => array( 'Review the listed opportunities with a developer and retest on staging before changing production assets or templates.' ),
			) );
		}
	}

	private function collect_authority_candidates( array &$candidates, array &$health, $limit ) {
		$report = $this->authority->report( min( 250, $limit ), false );
		if ( is_wp_error( $report ) ) {
			$health['authority'] = array( 'available' => false, 'message' => $report->get_error_message() );
			return;
		}
		$health['authority'] = array( 'available' => true, 'generated_at' => sanitize_text_field( $report['generated_at'] ?? '' ), 'items' => absint( $report['summary']['active_backlinks'] ?? 0 ) );
		foreach ( array_slice( (array) ( $report['broken_link_recovery'] ?? array() ), 0, 50 ) as $item ) {
			$candidates[] = $this->candidate( array(
				'type' => 'broken_backlink_recovery', 'category' => 'authority', 'source' => 'authority_import', 'title' => 'Review a recoverable broken backlink',
				'summary' => sprintf( 'A stored backlink from %s points to a missing or redirected target.', sanitize_text_field( $item['source_domain'] ?? '' ) ), 'url' => $item['target_url'] ?? '',
				'impact' => min( 88, 60 + (int) min( 25, (float) ( $item['source_strength'] ?? 0 ) / 4 ) ), 'confidence' => 'medium', 'effort' => 'medium', 'risk' => 'medium', 'evidence' => $item,
				'actions' => array( 'Verify the link is live and relevant, then choose the safest restoration or redirect plan. Outreach and redirects require separate approval.' ),
			) );
		}
		foreach ( array_slice( (array) ( $report['unlinked_priority_pages'] ?? array() ), 0, 50 ) as $item ) {
			$candidates[] = $this->candidate( array(
				'type' => 'authority_gap_page', 'category' => 'authority', 'source' => 'authority_import', 'title' => 'Review an important page with weak imported authority evidence',
				'summary' => sanitize_text_field( $item['evidence'] ?? 'The page appears important but has limited imported backlink evidence.' ), 'url' => $item['url'] ?? $item['target_url'] ?? '',
				'impact' => absint( $item['priority'] ?? 62 ), 'confidence' => 'low', 'effort' => 'high', 'risk' => 'medium', 'evidence' => $item,
				'actions' => array( 'Review internal promotion, useful assets, partnerships and earned-link opportunities. Do not automate outreach or purchase links.' ),
			) );
		}
	}

	private function candidate( array $data ) {
		$confidence = $this->sanitize_confidence( $data['confidence'] ?? 'medium' );
		$effort = $this->sanitize_level( $data['effort'] ?? 'medium' );
		$risk = $this->sanitize_level( $data['risk'] ?? 'low' );
		$observed_at = sanitize_text_field( $data['observed_at'] ?? gmdate( 'Y-m-d' ) );
		$freshness = $this->freshness_score( $observed_at );
		$impact = max( 0, min( 100, absint( $data['impact'] ?? 50 ) ) );
		$confidence_score = array( 'low' => 40, 'medium' => 68, 'high' => 90 )[ $confidence ];
		$effort_score = array( 'low' => 25, 'medium' => 55, 'high' => 82 )[ $effort ];
		$risk_score = array( 'low' => 15, 'medium' => 45, 'high' => 80 )[ $risk ];
		$priority = (int) round( $impact * 0.50 + $confidence_score * 0.25 + ( 100 - $effort_score ) * 0.12 + $freshness * 0.13 - $risk_score * 0.08 );
		$priority = max( 1, min( 100, $priority ) );
		$url = esc_url_raw( $data['url'] ?? '' );
		$post_id = absint( $data['post_id'] ?? 0 );
		if ( ! $post_id && $url ) {
			$post_id = url_to_postid( $url );
		}
		return array(
			'type' => sanitize_key( $data['type'] ?? 'review' ),
			'category' => sanitize_key( $data['category'] ?? 'other' ),
			'primary_source' => sanitize_key( $data['source'] ?? 'unknown' ),
			'title' => sanitize_text_field( $data['title'] ?? 'Review SEO evidence' ),
			'summary' => sanitize_textarea_field( $data['summary'] ?? '' ),
			'target_url' => $url,
			'post_id' => $post_id,
			'keyword' => sanitize_text_field( $data['keyword'] ?? '' ),
			'intent' => sanitize_key( $data['intent'] ?? '' ),
			'priority' => $priority,
			'impact_score' => $impact,
			'confidence' => $confidence,
			'confidence_score' => $confidence_score,
			'effort' => $effort,
			'effort_score' => $effort_score,
			'risk' => $risk,
			'risk_score' => $risk_score,
			'freshness_score' => $freshness,
			'observed_at' => $observed_at,
			'evidence' => is_array( $data['evidence'] ?? null ) ? $data['evidence'] : array( 'note' => sanitize_textarea_field( $data['evidence'] ?? '' ) ),
			'actions' => array_values( array_filter( array_map( 'sanitize_textarea_field', (array) ( $data['actions'] ?? array() ) ) ) ),
		);
	}

	private function store_candidate( array $candidate ) {
		global $wpdb;
		$key = hash( 'sha256', implode( '|', array(
			$candidate['type'],
			$this->url_key( $candidate['target_url'] ),
			$this->normalize_text( $candidate['keyword'] ),
			$candidate['primary_source'],
		) ) );
		$now = current_time( 'mysql', true );
		$sql = "INSERT INTO {$this->opportunities_table()}
			(opportunity_key,type,category,primary_source,title,summary,target_url,post_id,keyword,intent,priority,impact_score,confidence,confidence_score,effort,effort_score,risk,risk_score,freshness_score,evidence_json,actions_json,status,is_current,first_seen_at,last_seen_at,created_at,updated_at)
			VALUES (%s,%s,%s,%s,%s,%s,%s,%d,%s,%s,%d,%d,%s,%d,%s,%d,%s,%d,%d,%s,%s,'open',1,%s,%s,%s,%s)
			ON DUPLICATE KEY UPDATE type=VALUES(type),category=VALUES(category),primary_source=VALUES(primary_source),title=VALUES(title),summary=VALUES(summary),target_url=VALUES(target_url),post_id=VALUES(post_id),keyword=VALUES(keyword),intent=VALUES(intent),priority=VALUES(priority),impact_score=VALUES(impact_score),confidence=VALUES(confidence),confidence_score=VALUES(confidence_score),effort=VALUES(effort),effort_score=VALUES(effort_score),risk=VALUES(risk),risk_score=VALUES(risk_score),freshness_score=VALUES(freshness_score),evidence_json=VALUES(evidence_json),actions_json=VALUES(actions_json),is_current=1,last_seen_at=VALUES(last_seen_at),updated_at=VALUES(updated_at)";
		$result = $wpdb->query( $wpdb->prepare(
			$sql,
			$key,
			$candidate['type'],
			$candidate['category'],
			$candidate['primary_source'],
			$candidate['title'],
			$candidate['summary'],
			$candidate['target_url'],
			$candidate['post_id'],
			$candidate['keyword'],
			$candidate['intent'],
			$candidate['priority'],
			$candidate['impact_score'],
			$candidate['confidence'],
			$candidate['confidence_score'],
			$candidate['effort'],
			$candidate['effort_score'],
			$candidate['risk'],
			$candidate['risk_score'],
			$candidate['freshness_score'],
			wp_json_encode( $candidate['evidence'] ),
			wp_json_encode( $candidate['actions'] ),
			$now,
			$now,
			$now,
			$now
		) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return false !== $result;
	}

	private function get_opportunity( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->opportunities_table()} WHERE id=%d", absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? $this->format_opportunity( $row ) : null;
	}

	private function format_opportunity( array $row ) {
		foreach ( array( 'id', 'post_id', 'priority', 'impact_score', 'confidence_score', 'effort_score', 'risk_score', 'freshness_score', 'reviewed_by', 'is_current' ) as $key ) {
			$row[ $key ] = absint( $row[ $key ] ?? 0 );
		}
		$row['evidence'] = json_decode( (string) ( $row['evidence_json'] ?? '' ), true ) ?: array();
		$row['actions'] = json_decode( (string) ( $row['actions_json'] ?? '' ), true ) ?: array();
		unset( $row['evidence_json'], $row['actions_json'], $row['opportunity_key'] );
		return $row;
	}

	private function source_health() {
		$status = $this->status();
		$search = $this->search_intelligence->status();
		$analytics = $this->analytics->status();
		$technical = $this->technical->status();
		$indexation = $this->indexation->status();
		$competitor = $this->competitor_content->status();
		$authority = $this->authority->status();
		return array(
			'search_console' => array( 'connected' => ! empty( $search['connected'] ), 'last_sync' => sanitize_text_field( $search['last_sync'] ?? '' ) ),
			'analytics' => array( 'connected' => ! empty( $analytics['connected'] ), 'last_sync' => sanitize_text_field( $analytics['last_sync'] ?? '' ) ),
			'technical' => array( 'database_ready' => ! empty( $technical['database_ready'] ), 'last_refresh' => sanitize_text_field( $technical['last_refresh'] ?? '' ) ),
			'indexation' => array( 'database_ready' => ! empty( $indexation['database_ready'] ), 'last_inspection' => sanitize_text_field( $indexation['last_inspection'] ?? '' ) ),
			'competitor_content' => array( 'database_ready' => ! empty( $competitor['database_ready'] ), 'last_updated' => sanitize_text_field( $competitor['last_updated'] ?? '' ) ),
			'authority' => array( 'database_ready' => ! empty( $authority['database_ready'] ), 'last_updated' => sanitize_text_field( $authority['last_updated'] ?? '' ) ),
			'imported_keyword_evidence' => array( 'rows' => absint( $status['imported_evidence'] ?? 0 ), 'sources' => $status['evidence_sources'] ?? array() ),
		);
	}

	private function normalize_import_record( array $record, $source ) {
		$keyword = $record['keyword'] ?? $record['query'] ?? $record['phrase'] ?? '';
		$url = $record['target_url'] ?? $record['url'] ?? $record['landing_page'] ?? '';
		$domain = $record['competitor_domain'] ?? $record['domain'] ?? '';
		if ( $url && ! preg_match( '~^https?://~i', $url ) ) {
			$url = '';
		}
		$domain = strtolower( preg_replace( '/^www\./i', '', sanitize_text_field( $domain ) ) );
		$features = $record['serp_features'] ?? $record['features'] ?? array();
		if ( is_string( $features ) ) {
			$features = preg_split( '/[,;|]+/', $features );
		}
		$observed = sanitize_text_field( $record['observed_at'] ?? $record['date'] ?? gmdate( 'Y-m-d' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $observed ) ) {
			$observed = gmdate( 'Y-m-d' );
		}
		return array(
			'source' => $source,
			'keyword' => sanitize_text_field( $keyword ),
			'target_url' => esc_url_raw( $url ),
			'competitor_domain' => $domain,
			'country' => strtoupper( substr( sanitize_text_field( $record['country'] ?? '' ), 0, 8 ) ),
			'device' => sanitize_key( $record['device'] ?? '' ),
			'intent' => sanitize_key( $record['intent'] ?? $record['keyword_intent'] ?? '' ),
			'search_volume' => max( 0, (float) ( $record['search_volume'] ?? $record['volume'] ?? 0 ) ),
			'keyword_difficulty' => max( 0, min( 100, (float) ( $record['keyword_difficulty'] ?? $record['difficulty'] ?? $record['kd'] ?? 0 ) ) ),
			'position' => max( 0, (float) ( $record['position'] ?? $record['rank'] ?? 0 ) ),
			'previous_position' => max( 0, (float) ( $record['previous_position'] ?? $record['previous_rank'] ?? 0 ) ),
			'estimated_traffic' => max( 0, (float) ( $record['estimated_traffic'] ?? $record['traffic'] ?? 0 ) ),
			'cpc' => max( 0, (float) ( $record['cpc'] ?? 0 ) ),
			'serp_features' => array_slice( array_values( array_filter( array_map( 'sanitize_text_field', (array) $features ) ) ), 0, 30 ),
			'evidence_notes' => sanitize_textarea_field( $record['evidence_notes'] ?? $record['notes'] ?? '' ),
			'observed_at' => $observed,
		);
	}

	private function normalize_header( $header ) {
		$header = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header );
		$key = strtolower( trim( preg_replace( '/[^a-z0-9]+/i', '_', $header ), '_' ) );
		$map = array(
			'keyword' => 'keyword', 'query' => 'keyword', 'phrase' => 'keyword',
			'url' => 'target_url', 'target_url' => 'target_url', 'landing_page' => 'target_url', 'ranking_url' => 'target_url',
			'domain' => 'competitor_domain', 'competitor' => 'competitor_domain', 'competitor_domain' => 'competitor_domain',
			'country' => 'country', 'database' => 'country', 'device' => 'device', 'intent' => 'intent', 'keyword_intent' => 'intent',
			'volume' => 'search_volume', 'search_volume' => 'search_volume',
			'keyword_difficulty' => 'keyword_difficulty', 'difficulty' => 'keyword_difficulty', 'kd' => 'keyword_difficulty', 'kd_percent' => 'keyword_difficulty',
			'position' => 'position', 'rank' => 'position', 'pos' => 'position',
			'previous_position' => 'previous_position', 'previous_rank' => 'previous_position', 'previous_pos' => 'previous_position',
			'traffic' => 'estimated_traffic', 'estimated_traffic' => 'estimated_traffic', 'traffic_percent' => 'estimated_traffic',
			'cpc' => 'cpc', 'serp_features' => 'serp_features', 'features' => 'serp_features',
			'notes' => 'evidence_notes', 'evidence_notes' => 'evidence_notes', 'date' => 'observed_at', 'observed_at' => 'observed_at', 'last_update' => 'observed_at',
		);
		return $map[ $key ] ?? '';
	}

	private function sanitize_source( $source ) {
		$source = sanitize_key( $source );
		return in_array( $source, array( 'semrush', 'ahrefs', 'licensed_provider', 'manual', 'workspace_import' ), true ) ? $source : 'manual';
	}

	private function sanitize_confidence( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'low', 'medium', 'high' ), true ) ? $value : 'medium';
	}

	private function sanitize_level( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'low', 'medium', 'high' ), true ) ? $value : 'medium';
	}

	private function freshness_score( $observed_at ) {
		$timestamp = strtotime( (string) $observed_at );
		if ( ! $timestamp ) {
			return 45;
		}
		$days = max( 0, ( time() - $timestamp ) / DAY_IN_SECONDS );
		if ( $days <= 14 ) {
			return 100;
		}
		if ( $days <= 30 ) {
			return 85;
		}
		if ( $days <= 60 ) {
			return 68;
		}
		if ( $days <= 120 ) {
			return 45;
		}
		return 25;
	}

	private function remember_error( $message ) {
		$settings = Ikon_SEO_Plugin::settings();
		$settings['opportunity_engine_last_error'] = sanitize_text_field( $message );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->logger->log( 'opportunity_engine', 'error', sanitize_text_field( $message ) );
	}

	private function normalize_text( $value ) {
		$value = strtolower( wp_strip_all_tags( (string) $value ) );
		$value = preg_replace( '/\s+/', ' ', trim( $value ) );
		return $value;
	}

	private function url_key( $url ) {
		$url = esc_url_raw( (string) $url );
		return strtolower( untrailingslashit( $url ) );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
