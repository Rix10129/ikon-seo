<?php

defined( 'ABSPATH' ) || exit;

/**
 * Visibility and brand evidence for search, local, editorial and answer-engine observations.
 *
 * This module stores concise, reviewed evidence. It does not scrape search engines,
 * contact publishers, send outreach or claim complete coverage of generated answers.
 */
final class Ikon_SEO_Visibility_Brand_Intelligence {
	const CRON_HOOK        = 'ikon_seo_visibility_brand_refresh';
	const MAX_SYNC_RECORDS = 500;
	const MAX_REPORT_ROWS  = 500;
	const CACHE_KEY        = 'ikon_seo_visibility_brand_report';

	private $profile;
	private $search_intelligence;
	private $local_growth;
	private $authority;
	private $competitor_content;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Local_Growth $local_growth,
		Ikon_SEO_Authority_Intelligence $authority,
		Ikon_SEO_Competitor_Content_Intelligence $competitor_content,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->profile             = $profile;
		$this->search_intelligence = $search_intelligence;
		$this->local_growth        = $local_growth;
		$this->authority           = $authority;
		$this->competitor_content  = $competitor_content;
		$this->history             = $history;
		$this->logger              = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_refresh' ) );
	}

	public function observations_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_visibility_observations';
	}

	public function mentions_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_brand_mentions';
	}

	public function snapshots_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_visibility_snapshots';
	}

	public function scheduled_refresh() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['visibility_brand_enabled'] ) ) {
			return;
		}
		$this->refresh_snapshot( 0, 'scheduled' );
	}

	public function status() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		$ready = $this->tables_ready();
		if ( ! $ready ) {
			return array(
				'enabled'              => ! empty( $settings['visibility_brand_enabled'] ),
				'database_ready'       => false,
				'observations'         => 0,
				'brand_mentions'       => 0,
				'unlinked_mentions'    => 0,
				'competitors'          => 0,
				'last_observed_at'     => '',
				'last_snapshot_at'     => '',
			);
		}

		$profile_id = $this->profile_id();
		$observations = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->observations_table()} WHERE profile_id=%s AND status <> 'archived'", $profile_id ) ) );
		$mentions = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->mentions_table()} WHERE profile_id=%s AND status <> 'archived'", $profile_id ) ) );
		$unlinked = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->mentions_table()} WHERE profile_id=%s AND linked=0 AND status IN ('new','reviewed','opportunity')", $profile_id ) ) );
		$competitors = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT competitor_domain) FROM {$this->observations_table()} WHERE profile_id=%s AND competitor_domain <> '' AND status <> 'archived'", $profile_id ) ) );
		$last_observed = sanitize_text_field( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(observed_at) FROM {$this->observations_table()} WHERE profile_id=%s", $profile_id ) ) );
		$last_snapshot = sanitize_text_field( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(captured_at) FROM {$this->snapshots_table()} WHERE profile_id=%s", $profile_id ) ) );

		return array(
			'enabled'              => ! empty( $settings['visibility_brand_enabled'] ),
			'database_ready'       => true,
			'observations'         => $observations,
			'brand_mentions'       => $mentions,
			'unlinked_mentions'    => $unlinked,
			'competitors'          => $competitors,
			'last_observed_at'     => $last_observed,
			'last_snapshot_at'     => $last_snapshot,
			'brand_name'           => sanitize_text_field( $settings['visibility_brand_name'] ?? get_bloginfo( 'name' ) ),
			'aliases'              => $this->line_list( $settings['visibility_brand_aliases'] ?? '' ),
			'configured_competitors'=> $this->line_list( $settings['visibility_competitors'] ?? '' ),
		);
	}

	public function save_settings( array $input, $user_id = 0 ) {
		$settings = Ikon_SEO_Plugin::settings();
		$settings['visibility_brand_enabled'] = ! empty( $input['enabled'] ) ? 1 : 0;
		$settings['visibility_brand_name'] = sanitize_text_field( $input['brand_name'] ?? get_bloginfo( 'name' ) );
		$settings['visibility_brand_aliases'] = $this->sanitize_lines( $input['brand_aliases'] ?? '' );
		$settings['visibility_competitors'] = $this->sanitize_lines( $input['competitors'] ?? '' );
		$settings['visibility_observation_stale_days'] = max( 7, min( 365, absint( $input['observation_stale_days'] ?? 45 ) ) );
		$settings['visibility_mention_review_days'] = max( 7, min( 365, absint( $input['mention_review_days'] ?? 30 ) ) );
		$settings['visibility_min_confidence'] = $this->sanitize_confidence( $input['min_confidence'] ?? 'medium' );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		delete_transient( self::CACHE_KEY );

		$this->history->add(
			array(
				'category' => 'strategy',
				'status'   => 'completed',
				'title'    => 'Visibility and brand settings updated',
				'summary'  => 'Brand names, competitor references and evidence-review rules were updated.',
				'details'  => array(
					'brand_name' => $settings['visibility_brand_name'],
					'aliases' => $this->line_list( $settings['visibility_brand_aliases'] ),
					'competitors' => $this->line_list( $settings['visibility_competitors'] ),
				),
			),
			'visibility',
			absint( $user_id )
		);
		return $this->status();
	}

	public function sync( array $payload, $user_id = 0 ) {
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_visibility_tables', __( 'Visibility and Brand Intelligence tables are unavailable. Reactivate Ikon SEO to repair the database.', 'ikon-seo' ) );
		}

		$command = sanitize_key( $payload['command'] ?? 'read' );
		$result = array( 'read_only' => true );
		switch ( $command ) {
			case 'save_observations':
				$result = $this->save_observations( (array) ( $payload['observations'] ?? array() ), $user_id );
				break;
			case 'save_mentions':
				$result = $this->save_mentions( (array) ( $payload['mentions'] ?? array() ), $user_id );
				break;
			case 'update_mention':
				$result = $this->update_mention( absint( $payload['mention_id'] ?? 0 ), (array) ( $payload['mention'] ?? array() ), $user_id );
				break;
			case 'refresh_snapshot':
				$result = $this->refresh_snapshot( $user_id, 'workspace' );
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
			'command' => $command,
			'result'  => $result,
			'visibility' => $this->report( absint( $payload['limit'] ?? 100 ) ),
		);
	}

	public function save_observations( array $records, $user_id = 0 ) {
		if ( isset( $records['query'] ) || isset( $records['source_url'] ) ) {
			$records = array( $records );
		}
		if ( count( $records ) > self::MAX_SYNC_RECORDS ) {
			return new WP_Error( 'ikon_seo_visibility_batch', sprintf( __( 'A maximum of %d visibility observations can be stored per request.', 'ikon-seo' ), self::MAX_SYNC_RECORDS ) );
		}
		$summary = array( 'seen' => 0, 'saved' => 0, 'skipped' => 0, 'errors' => array() );
		foreach ( $records as $record ) {
			$summary['seen']++;
			$saved = $this->save_observation( (array) $record, $user_id );
			if ( is_wp_error( $saved ) ) {
				$summary['skipped']++;
				if ( count( $summary['errors'] ) < 20 ) {
					$summary['errors'][] = $saved->get_error_message();
				}
			} else {
				$summary['saved']++;
			}
		}
		if ( $summary['saved'] ) {
			delete_transient( self::CACHE_KEY );
			$this->history->add(
				array(
					'category' => 'research',
					'status'   => 'completed',
					'title'    => 'Visibility observations stored',
					'summary'  => sprintf( '%d reviewed visibility observations were stored.', $summary['saved'] ),
					'details'  => $summary,
				),
				'visibility',
				absint( $user_id )
			);
		}
		return $summary;
	}

	public function save_observation( array $record, $user_id = 0 ) {
		global $wpdb;
		$query = sanitize_text_field( $record['query'] ?? '' );
		$source_url = esc_url_raw( $record['source_url'] ?? '' );
		$source_name = sanitize_text_field( $record['source_name'] ?? '' );
		$type = $this->sanitize_observation_type( $record['observation_type'] ?? $record['type'] ?? 'organic_search' );
		$brand_role = $this->sanitize_brand_role( $record['brand_role'] ?? 'own_brand' );
		$mention_status = $this->sanitize_mention_status( $record['mention_status'] ?? 'mentioned' );
		$competitor_domain = $this->normalize_domain( $record['competitor_domain'] ?? '' );
		$brand_name = sanitize_text_field( $record['brand_name'] ?? '' );
		$cited_url = esc_url_raw( $record['cited_url'] ?? '' );
		$observed_at = $this->sanitize_datetime( $record['observed_at'] ?? current_time( 'mysql', true ) );
		if ( ! $query && ! $source_url ) {
			return new WP_Error( 'ikon_seo_visibility_required', __( 'A visibility observation requires a query or a supporting source URL.', 'ikon-seo' ) );
		}
		if ( $source_url && ! $this->valid_http_url( $source_url ) ) {
			return new WP_Error( 'ikon_seo_visibility_source_url', __( 'The visibility source URL is invalid.', 'ikon-seo' ) );
		}
		if ( $cited_url && ! $this->valid_http_url( $cited_url ) ) {
			return new WP_Error( 'ikon_seo_visibility_cited_url', __( 'The cited URL is invalid.', 'ikon-seo' ) );
		}
		if ( 'competitor' === $brand_role && ! $competitor_domain && ! $brand_name ) {
			return new WP_Error( 'ikon_seo_visibility_competitor', __( 'Competitor observations require a competitor domain or name.', 'ikon-seo' ) );
		}
		$confidence = $this->sanitize_confidence( $record['confidence'] ?? 'medium' );
		$sentiment = $this->sanitize_sentiment( $record['sentiment'] ?? 'unknown' );
		$prominence = max( 0, min( 100, absint( $record['prominence'] ?? 0 ) ) );
		$position_text = sanitize_text_field( $record['position_text'] ?? '' );
		$evidence_excerpt = sanitize_textarea_field( $record['evidence_excerpt'] ?? $record['evidence'] ?? '' );
		$notes = sanitize_textarea_field( $record['notes'] ?? '' );
		$profile_id = $this->profile_id();
		$key = hash( 'sha256', implode( '|', array( $profile_id, $type, $this->normalize_text( $query ), $brand_role, $this->normalize_domain( $competitor_domain ), $this->url_key( $source_url ), gmdate( 'Y-m-d', strtotime( $observed_at ) ) ) ) );
		$now = current_time( 'mysql', true );
		$sql = "INSERT INTO {$this->observations_table()}
			(observation_hash,profile_id,observation_type,query_text,query_hash,brand_role,brand_name,competitor_domain,mention_status,cited_url,source_name,source_url,sentiment,prominence,position_text,evidence_excerpt,confidence,observed_at,status,notes,created_by,created_at,updated_at)
			VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s,%s,%s,'active',%s,%d,%s,%s)
			ON DUPLICATE KEY UPDATE mention_status=VALUES(mention_status), cited_url=VALUES(cited_url), source_name=VALUES(source_name), source_url=VALUES(source_url), sentiment=VALUES(sentiment), prominence=VALUES(prominence), position_text=VALUES(position_text), evidence_excerpt=VALUES(evidence_excerpt), confidence=VALUES(confidence), notes=VALUES(notes), updated_at=VALUES(updated_at)";
		$result = $wpdb->query(
			$wpdb->prepare(
				$sql,
				$key,
				$profile_id,
				$type,
				$query,
				hash( 'sha256', $this->normalize_text( $query ) ),
				$brand_role,
				$brand_name,
				$competitor_domain,
				$mention_status,
				$cited_url,
				$source_name,
				$source_url,
				$sentiment,
				$prominence,
				$position_text,
				substr( $evidence_excerpt, 0, 2000 ),
				$confidence,
				$observed_at,
				substr( $notes, 0, 5000 ),
				absint( $user_id ),
				$now,
				$now
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_visibility_store', __( 'The visibility observation could not be stored.', 'ikon-seo' ) );
		}
		return array( 'observation_hash' => $key, 'type' => $type, 'query' => $query, 'brand_role' => $brand_role );
	}

	public function save_mentions( array $records, $user_id = 0 ) {
		if ( isset( $records['mention_url'] ) ) {
			$records = array( $records );
		}
		if ( count( $records ) > self::MAX_SYNC_RECORDS ) {
			return new WP_Error( 'ikon_seo_mentions_batch', sprintf( __( 'A maximum of %d brand mentions can be stored per request.', 'ikon-seo' ), self::MAX_SYNC_RECORDS ) );
		}
		$summary = array( 'seen' => 0, 'saved' => 0, 'skipped' => 0, 'errors' => array() );
		foreach ( $records as $record ) {
			$summary['seen']++;
			$saved = $this->save_mention( (array) $record, $user_id );
			if ( is_wp_error( $saved ) ) {
				$summary['skipped']++;
				if ( count( $summary['errors'] ) < 20 ) {
					$summary['errors'][] = $saved->get_error_message();
				}
			} else {
				$summary['saved']++;
			}
		}
		if ( $summary['saved'] ) {
			delete_transient( self::CACHE_KEY );
			$this->history->add(
				array(
					'category' => 'research',
					'status'   => 'completed',
					'title'    => 'Brand mentions stored',
					'summary'  => sprintf( '%d reviewed brand mention records were stored.', $summary['saved'] ),
					'details'  => $summary,
				),
				'visibility',
				absint( $user_id )
			);
		}
		return $summary;
	}

	public function save_mention( array $record, $user_id = 0 ) {
		global $wpdb;
		$mention_url = esc_url_raw( $record['mention_url'] ?? $record['source_url'] ?? '' );
		if ( ! $this->valid_http_url( $mention_url ) ) {
			return new WP_Error( 'ikon_seo_mention_url', __( 'A valid mention URL is required.', 'ikon-seo' ) );
		}
		$source_domain = $this->normalize_domain( wp_parse_url( $mention_url, PHP_URL_HOST ) );
		if ( ! $source_domain || $source_domain === $this->site_domain() ) {
			return new WP_Error( 'ikon_seo_mention_external', __( 'A brand mention must come from an external website.', 'ikon-seo' ) );
		}
		$target_url = esc_url_raw( $record['target_url'] ?? '' );
		if ( $target_url && ! $this->valid_http_url( $target_url ) ) {
			return new WP_Error( 'ikon_seo_mention_target', __( 'The linked target URL is invalid.', 'ikon-seo' ) );
		}
		$linked = ! empty( $record['linked'] ) || ( $target_url && $this->normalize_domain( wp_parse_url( $target_url, PHP_URL_HOST ) ) === $this->site_domain() );
		$mention_type = $this->sanitize_mention_type( $record['mention_type'] ?? 'editorial' );
		$sentiment = $this->sanitize_sentiment( $record['sentiment'] ?? 'unknown' );
		$relevance = max( 0, min( 100, absint( $record['relevance'] ?? 50 ) ) );
		$source_strength = max( 0, min( 100, absint( $record['source_strength'] ?? 0 ) ) );
		$status = $this->sanitize_mention_workflow_status( $record['status'] ?? ( $linked ? 'reviewed' : 'new' ) );
		$brand_name = sanitize_text_field( $record['brand_name'] ?? Ikon_SEO_Plugin::settings()['visibility_brand_name'] ?? get_bloginfo( 'name' ) );
		$mention_title = sanitize_text_field( $record['mention_title'] ?? '' );
		$mention_excerpt = sanitize_textarea_field( $record['mention_excerpt'] ?? '' );
		$notes = sanitize_textarea_field( $record['notes'] ?? '' );
		$discovered_at = $this->sanitize_datetime( $record['discovered_at'] ?? current_time( 'mysql', true ) );
		$last_checked = $this->sanitize_datetime( $record['last_checked'] ?? current_time( 'mysql', true ) );
		$profile_id = $this->profile_id();
		$hash = hash( 'sha256', implode( '|', array( $profile_id, $this->url_key( $mention_url ), $this->normalize_text( $brand_name ) ) ) );
		$now = current_time( 'mysql', true );
		$sql = "INSERT INTO {$this->mentions_table()}
			(mention_hash,profile_id,mention_url,source_domain,mention_type,brand_name,mention_title,mention_excerpt,linked,target_url,sentiment,relevance,source_strength,status,discovered_at,last_checked,notes,created_by,created_at,updated_at)
			VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s,%d,%d,%s,%s,%s,%s,%d,%s,%s)
			ON DUPLICATE KEY UPDATE mention_type=VALUES(mention_type), mention_title=VALUES(mention_title), mention_excerpt=VALUES(mention_excerpt), linked=VALUES(linked), target_url=VALUES(target_url), sentiment=VALUES(sentiment), relevance=VALUES(relevance), source_strength=VALUES(source_strength), status=VALUES(status), last_checked=VALUES(last_checked), notes=VALUES(notes), updated_at=VALUES(updated_at)";
		$result = $wpdb->query(
			$wpdb->prepare(
				$sql,
				$hash,
				$profile_id,
				$mention_url,
				$source_domain,
				$mention_type,
				$brand_name,
				$mention_title,
				substr( $mention_excerpt, 0, 2000 ),
				$linked ? 1 : 0,
				$target_url,
				$sentiment,
				$relevance,
				$source_strength,
				$status,
				$discovered_at,
				$last_checked,
				substr( $notes, 0, 5000 ),
				absint( $user_id ),
				$now,
				$now
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_mention_store', __( 'The brand mention could not be stored.', 'ikon-seo' ) );
		}
		return array( 'mention_hash' => $hash, 'source_domain' => $source_domain, 'linked' => $linked, 'status' => $status );
	}

	public function update_mention( $mention_id, array $input, $user_id = 0 ) {
		global $wpdb;
		$mention_id = absint( $mention_id );
		if ( ! $mention_id ) {
			return new WP_Error( 'ikon_seo_mention_id', __( 'Select a valid brand mention.', 'ikon-seo' ) );
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->mentions_table()} WHERE id=%d AND profile_id=%s", $mention_id, $this->profile_id() ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'ikon_seo_mention_missing', __( 'The brand mention was not found.', 'ikon-seo' ) );
		}
		$data = array(
			'status'       => $this->sanitize_mention_workflow_status( $input['status'] ?? $row['status'] ),
			'notes'        => sanitize_textarea_field( $input['notes'] ?? $row['notes'] ),
			'last_checked' => $this->sanitize_datetime( $input['last_checked'] ?? current_time( 'mysql', true ) ),
			'updated_at'   => current_time( 'mysql', true ),
		);
		if ( isset( $input['linked'] ) ) {
			$data['linked'] = ! empty( $input['linked'] ) ? 1 : 0;
		}
		if ( isset( $input['target_url'] ) ) {
			$target_url = esc_url_raw( $input['target_url'] );
			if ( $target_url && ! $this->valid_http_url( $target_url ) ) {
				return new WP_Error( 'ikon_seo_mention_target', __( 'The linked target URL is invalid.', 'ikon-seo' ) );
			}
			$data['target_url'] = $target_url;
		}
		$updated = $wpdb->update( $this->mentions_table(), $data, array( 'id' => $mention_id, 'profile_id' => $this->profile_id() ) );
		if ( false === $updated ) {
			return new WP_Error( 'ikon_seo_mention_update', __( 'The brand mention could not be updated.', 'ikon-seo' ) );
		}
		delete_transient( self::CACHE_KEY );
		$this->history->add(
			array(
				'category' => 'task',
				'status'   => in_array( $data['status'], array( 'converted', 'dismissed', 'archived' ), true ) ? 'completed' : 'open',
				'title'    => 'Brand mention workflow updated',
				'summary'  => sprintf( 'Brand mention #%d was moved to %s.', $mention_id, str_replace( '_', ' ', $data['status'] ) ),
				'details'  => array( 'mention_id' => $mention_id, 'status' => $data['status'], 'updated_by' => absint( $user_id ) ),
			),
			'visibility',
			absint( $user_id )
		);
		return $this->get_mention( $mention_id );
	}

	public function refresh_snapshot( $user_id = 0, $source = 'manual' ) {
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_visibility_tables', __( 'Visibility and Brand Intelligence tables are unavailable.', 'ikon-seo' ) );
		}
		global $wpdb;
		$report = $this->build_report( 200 );
		$summary = array(
			'coverage' => $report['coverage'],
			'combined_evidence' => $report['combined_evidence'],
			'counts' => $report['counts'],
			'competitor_comparison' => array_slice( $report['competitor_comparison'], 0, 20 ),
			'opportunity_counts' => $report['opportunity_counts'],
		);
		$captured_at = current_time( 'mysql', true );
		$snapshot_hash = hash( 'sha256', $this->profile_id() . '|' . gmdate( 'Y-m-d', strtotime( $captured_at ) ) . '|' . wp_json_encode( $summary ) );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->snapshots_table()} (snapshot_hash,profile_id,period_start,period_end,summary_json,source,captured_at) VALUES (%s,%s,%s,%s,%s,%s,%s) ON DUPLICATE KEY UPDATE summary_json=VALUES(summary_json), source=VALUES(source), captured_at=VALUES(captured_at)",
				$snapshot_hash,
				$this->profile_id(),
				gmdate( 'Y-m-d', strtotime( '-29 days' ) ),
				gmdate( 'Y-m-d' ),
				wp_json_encode( $summary ),
				sanitize_key( $source ),
				$captured_at
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->snapshots_table()} WHERE profile_id=%s AND id NOT IN (SELECT id FROM (SELECT id FROM {$this->snapshots_table()} WHERE profile_id=%s ORDER BY captured_at DESC LIMIT 52) recent)", $this->profile_id(), $this->profile_id() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		delete_transient( self::CACHE_KEY );
		$this->logger->log( 'visibility_snapshot', 'success', 'Visibility and brand evidence snapshot refreshed.' );
		if ( 'scheduled' !== $source ) {
			$this->history->add(
				array(
					'category' => 'audit',
					'status'   => 'completed',
					'title'    => 'Visibility and brand snapshot refreshed',
					'summary'  => 'Organic, local, authority, mention and sampled citation evidence was combined into a new snapshot.',
					'details'  => $summary,
				),
				'visibility',
				absint( $user_id )
			);
		}
		return array( 'captured_at' => $captured_at, 'summary' => $summary );
	}

	public function report( $limit = 100 ) {
		$limit = max( 10, min( self::MAX_REPORT_ROWS, absint( $limit ) ) );
		return $this->build_report( $limit );
	}

	private function build_report( $limit ) {
		$status = $this->status();
		if ( empty( $status['database_ready'] ) ) {
			return array( 'ready' => false, 'status' => $status, 'counts' => array(), 'recommendations' => array() );
		}
		$observations = $this->observations( $limit );
		$mentions = $this->mentions( $limit );
		$opportunities = array_values( array_filter( $mentions, function( $item ) {
			return empty( $item['linked'] ) && in_array( $item['status'], array( 'new', 'reviewed', 'opportunity' ), true );
		} ) );
		usort( $opportunities, function( $a, $b ) { return $b['opportunity_priority'] <=> $a['opportunity_priority']; } );

		$own_observations = array_values( array_filter( $observations, function( $row ) { return 'own_brand' === $row['brand_role']; } ) );
		$competitor_observations = array_values( array_filter( $observations, function( $row ) { return 'competitor' === $row['brand_role']; } ) );
		$own_cited = count( array_filter( $own_observations, function( $row ) { return 'cited' === $row['mention_status']; } ) );
		$own_mentioned = count( array_filter( $own_observations, function( $row ) { return in_array( $row['mention_status'], array( 'mentioned', 'cited' ), true ); } ) );
		$own_absent = count( array_filter( $own_observations, function( $row ) { return 'absent' === $row['mention_status']; } ) );
		$positive = count( array_filter( $mentions, function( $row ) { return 'positive' === $row['sentiment']; } ) );
		$negative = count( array_filter( $mentions, function( $row ) { return 'negative' === $row['sentiment']; } ) );
		$linked = count( array_filter( $mentions, function( $row ) { return ! empty( $row['linked'] ); } ) );
		$competitor_comparison = $this->competitor_comparison( $competitor_observations );
		$combined = $this->combined_evidence();
		$coverage = $this->evidence_coverage( $status, $observations, $mentions, $combined );
		$recommendations = $this->recommendations( $coverage, $opportunities, $own_absent, $negative, $competitor_comparison, $combined );
		$snapshots = $this->snapshots( 12 );

		return array(
			'ready' => true,
			'status' => $status,
			'counts' => array(
				'observations' => count( $observations ),
				'own_mentions_observed' => $own_mentioned,
				'own_citations_observed' => $own_cited,
				'own_absences_observed' => $own_absent,
				'brand_mentions' => count( $mentions ),
				'linked_mentions' => $linked,
				'unlinked_mentions' => count( $opportunities ),
				'positive_mentions' => $positive,
				'negative_mentions' => $negative,
			),
			'opportunity_counts' => array(
				'unlinked_mentions' => count( $opportunities ),
				'high_priority' => count( array_filter( $opportunities, function( $row ) { return $row['opportunity_priority'] >= 70; } ) ),
				'citation_gaps' => $own_absent,
				'negative_mentions' => $negative,
			),
			'coverage' => $coverage,
			'combined_evidence' => $combined,
			'observations' => $observations,
			'brand_mentions' => $mentions,
			'unlinked_opportunities' => array_slice( $opportunities, 0, 100 ),
			'competitor_comparison' => $competitor_comparison,
			'recommendations' => $recommendations,
			'snapshots' => $snapshots,
			'limitations' => array(
				'Sampled answer-engine observations do not represent every generated answer, user, location or time.',
				'Unlinked mentions are editorial research leads, not permission to send outreach automatically.',
				'Sentiment and source-strength values require human verification.',
				'No visibility count or evidence-coverage percentage guarantees rankings, mentions or citations.',
			),
			'safety' => array(
				'automatic_outreach' => false,
				'automatic_link_building' => false,
				'automatic_publication' => false,
				'automatic_reputation_response' => false,
			),
			'generated_at' => current_time( 'mysql', true ),
		);
	}

	private function observations( $limit ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->observations_table()} WHERE profile_id=%s AND status <> 'archived' ORDER BY observed_at DESC,id DESC LIMIT %d", $this->profile_id(), $limit ), ARRAY_A );
		return array_map( array( $this, 'prepare_observation' ), $rows ?: array() );
	}

	private function mentions( $limit ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->mentions_table()} WHERE profile_id=%s AND status <> 'archived' ORDER BY relevance DESC,last_checked DESC,id DESC LIMIT %d", $this->profile_id(), $limit ), ARRAY_A );
		return array_map( array( $this, 'prepare_mention' ), $rows ?: array() );
	}

	private function get_mention( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->mentions_table()} WHERE id=%d AND profile_id=%s", absint( $id ), $this->profile_id() ), ARRAY_A );
		return $row ? $this->prepare_mention( $row ) : null;
	}

	private function prepare_observation( array $row ) {
		return array(
			'id' => absint( $row['id'] ),
			'observation_type' => sanitize_key( $row['observation_type'] ),
			'query' => sanitize_text_field( $row['query_text'] ),
			'brand_role' => sanitize_key( $row['brand_role'] ),
			'brand_name' => sanitize_text_field( $row['brand_name'] ),
			'competitor_domain' => sanitize_text_field( $row['competitor_domain'] ),
			'mention_status' => sanitize_key( $row['mention_status'] ),
			'cited_url' => esc_url_raw( $row['cited_url'] ),
			'source_name' => sanitize_text_field( $row['source_name'] ),
			'source_url' => esc_url_raw( $row['source_url'] ),
			'sentiment' => sanitize_key( $row['sentiment'] ),
			'prominence' => absint( $row['prominence'] ),
			'position_text' => sanitize_text_field( $row['position_text'] ),
			'evidence_excerpt' => sanitize_textarea_field( $row['evidence_excerpt'] ),
			'confidence' => sanitize_key( $row['confidence'] ),
			'observed_at' => sanitize_text_field( $row['observed_at'] ),
			'notes' => sanitize_textarea_field( $row['notes'] ),
		);
	}

	private function prepare_mention( array $row ) {
		$priority = absint( $row['relevance'] );
		$priority += min( 20, absint( $row['source_strength'] ) / 5 );
		if ( empty( $row['linked'] ) ) { $priority += 10; }
		if ( 'positive' === $row['sentiment'] ) { $priority += 5; }
		if ( 'negative' === $row['sentiment'] ) { $priority += 15; }
		$priority = max( 0, min( 100, absint( round( $priority ) ) ) );
		return array(
			'id' => absint( $row['id'] ),
			'mention_url' => esc_url_raw( $row['mention_url'] ),
			'source_domain' => sanitize_text_field( $row['source_domain'] ),
			'mention_type' => sanitize_key( $row['mention_type'] ),
			'brand_name' => sanitize_text_field( $row['brand_name'] ),
			'mention_title' => sanitize_text_field( $row['mention_title'] ),
			'mention_excerpt' => sanitize_textarea_field( $row['mention_excerpt'] ),
			'linked' => ! empty( $row['linked'] ),
			'target_url' => esc_url_raw( $row['target_url'] ),
			'sentiment' => sanitize_key( $row['sentiment'] ),
			'relevance' => absint( $row['relevance'] ),
			'source_strength' => absint( $row['source_strength'] ),
			'status' => sanitize_key( $row['status'] ),
			'discovered_at' => sanitize_text_field( $row['discovered_at'] ),
			'last_checked' => sanitize_text_field( $row['last_checked'] ),
			'notes' => sanitize_textarea_field( $row['notes'] ),
			'opportunity_priority' => $priority,
		);
	}

	private function combined_evidence() {
		$search = $this->search_intelligence->status();
		$local = $this->local_growth->status();
		$authority = $this->authority->status();
		$competitor = $this->competitor_content->status();
		return array(
			'organic_search' => array(
				'connected' => ! empty( $search['connected'] ),
				'rows' => absint( $search['rows'] ?? 0 ),
				'queries' => absint( $search['queries'] ?? 0 ),
				'pages' => absint( $search['pages'] ?? 0 ),
				'last_sync' => sanitize_text_field( $search['last_sync'] ?? '' ),
			),
			'local_visibility' => array(
				'enabled' => ! empty( $local['enabled'] ),
				'locations' => absint( $local['locations'] ?? 0 ),
				'last_sync' => sanitize_text_field( $local['last_sync'] ?? '' ),
			),
			'off_site_authority' => array(
				'enabled' => ! empty( $authority['enabled'] ),
				'backlinks' => absint( $authority['backlinks'] ?? 0 ),
				'referring_domains' => absint( $authority['referring_domains'] ?? 0 ),
				'competitors' => absint( $authority['competitors'] ?? 0 ),
				'last_updated' => sanitize_text_field( $authority['last_updated'] ?? '' ),
			),
			'competitor_research' => array(
				'enabled' => ! empty( $competitor['enabled'] ),
				'observations' => absint( $competitor['observations'] ?? $competitor['research'] ?? 0 ),
				'last_updated' => sanitize_text_field( $competitor['last_updated'] ?? '' ),
			),
		);
	}

	private function evidence_coverage( array $status, array $observations, array $mentions, array $combined ) {
		$checks = array(
			'brand_configured' => ! empty( $status['brand_name'] ),
			'aliases_configured' => ! empty( $status['aliases'] ),
			'competitors_configured' => ! empty( $status['configured_competitors'] ),
			'organic_evidence' => ! empty( $combined['organic_search']['rows'] ),
			'local_evidence' => ! empty( $combined['local_visibility']['locations'] ),
			'authority_evidence' => ! empty( $combined['off_site_authority']['backlinks'] ),
			'visibility_observations' => count( $observations ) >= 5,
			'brand_mentions' => count( $mentions ) >= 3,
		);
		$complete = count( array_filter( $checks ) );
		$score = absint( round( 100 * $complete / count( $checks ) ) );
		return array(
			'score' => $score,
			'status' => $score >= 80 ? 'strong' : ( $score >= 50 ? 'developing' : 'limited' ),
			'checks' => $checks,
			'note' => 'Evidence coverage measures data completeness. It is not a search, local or brand visibility score.',
		);
	}

	private function competitor_comparison( array $observations ) {
		$groups = array();
		foreach ( $observations as $row ) {
			$key = $row['competitor_domain'] ?: $this->normalize_text( $row['brand_name'] );
			if ( ! $key ) { continue; }
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = array( 'competitor' => $row['competitor_domain'] ?: $row['brand_name'], 'observations' => 0, 'mentions' => 0, 'citations' => 0, 'positive' => 0, 'negative' => 0, 'queries' => array() );
			}
			$groups[ $key ]['observations']++;
			if ( in_array( $row['mention_status'], array( 'mentioned', 'cited' ), true ) ) { $groups[ $key ]['mentions']++; }
			if ( 'cited' === $row['mention_status'] ) { $groups[ $key ]['citations']++; }
			if ( 'positive' === $row['sentiment'] ) { $groups[ $key ]['positive']++; }
			if ( 'negative' === $row['sentiment'] ) { $groups[ $key ]['negative']++; }
			if ( $row['query'] ) { $groups[ $key ]['queries'][ $row['query'] ] = true; }
		}
		foreach ( $groups as &$group ) {
			$group['queries'] = array_slice( array_keys( $group['queries'] ), 0, 20 );
		}
		unset( $group );
		usort( $groups, function( $a, $b ) { return $b['citations'] <=> $a['citations'] ?: $b['mentions'] <=> $a['mentions']; } );
		return array_values( $groups );
	}

	private function recommendations( array $coverage, array $opportunities, $absent, $negative, array $competitors, array $combined ) {
		$items = array();
		if ( $coverage['score'] < 50 ) {
			$items[] = $this->recommendation( 'high', 'evidence', 'Complete the brand aliases, competitor list and a representative set of reviewed observations before drawing visibility conclusions.', false );
		}
		if ( count( $opportunities ) ) {
			$items[] = $this->recommendation( 'high', 'unlinked_mentions', sprintf( 'Review %d unlinked brand mentions and decide whether each requires relationship building, correction, a link request or no action.', count( $opportunities ) ), true );
		}
		if ( $absent ) {
			$items[] = $this->recommendation( 'high', 'citation_gap', sprintf( 'Review %d sampled queries where the brand was absent and competitors or other sources were visible.', $absent ), false );
		}
		if ( $negative ) {
			$items[] = $this->recommendation( 'high', 'reputation', sprintf( 'Manually verify %d negative mention observations and prepare an approved response or correction plan where appropriate.', $negative ), true );
		}
		if ( ! empty( $competitors ) ) {
			$top = $competitors[0];
			$items[] = $this->recommendation( 'medium', 'competitor_visibility', sprintf( 'Compare the sources and topics supporting %s across %d stored observations before planning new content or promotion.', $top['competitor'], $top['observations'] ), false );
		}
		if ( empty( $combined['off_site_authority']['backlinks'] ) ) {
			$items[] = $this->recommendation( 'medium', 'authority_data', 'Import verified backlink evidence before concluding that weak authority is preventing visibility.', false );
		}
		if ( empty( $combined['organic_search']['rows'] ) ) {
			$items[] = $this->recommendation( 'medium', 'search_data', 'Connect or refresh Search Console so brand observations can be compared with real query and page performance.', false );
		}
		return $items;
	}

	private function recommendation( $priority, $category, $action, $approval_required ) {
		return array( 'priority' => sanitize_key( $priority ), 'category' => sanitize_key( $category ), 'action' => sanitize_text_field( $action ), 'approval_required' => (bool) $approval_required );
	}

	private function snapshots( $limit ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->snapshots_table()} WHERE profile_id=%s ORDER BY captured_at DESC LIMIT %d", $this->profile_id(), max( 1, min( 52, absint( $limit ) ) ) ), ARRAY_A );
		$result = array();
		foreach ( $rows ?: array() as $row ) {
			$summary = json_decode( (string) $row['summary_json'], true );
			$result[] = array( 'period_start' => sanitize_text_field( $row['period_start'] ), 'period_end' => sanitize_text_field( $row['period_end'] ), 'source' => sanitize_key( $row['source'] ), 'captured_at' => sanitize_text_field( $row['captured_at'] ), 'summary' => is_array( $summary ) ? $summary : array() );
		}
		return $result;
	}

	private function tables_ready() {
		return $this->table_exists( $this->observations_table() ) && $this->table_exists( $this->mentions_table() ) && $this->table_exists( $this->snapshots_table() );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	private function profile_id() {
		$profile = $this->profile->get();
		return sanitize_text_field( $profile['profile_id'] ?? $this->profile->fingerprint() );
	}

	private function site_domain() {
		return $this->normalize_domain( wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	}

	private function normalize_domain( $value ) {
		$value = strtolower( trim( (string) $value ) );
		if ( false !== strpos( $value, '://' ) ) {
			$value = (string) wp_parse_url( $value, PHP_URL_HOST );
		}
		$value = preg_replace( '/^www\./', '', $value );
		$value = preg_replace( '/[^a-z0-9.\-]/', '', $value );
		return trim( $value, '.' );
	}

	private function valid_http_url( $url ) {
		if ( ! $url || ! wp_http_validate_url( $url ) ) { return false; }
		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		return in_array( $scheme, array( 'http', 'https' ), true );
	}

	private function url_key( $url ) {
		$url = esc_url_raw( $url );
		if ( ! $url ) { return ''; }
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) { return strtolower( untrailingslashit( $url ) ); }
		$scheme = strtolower( $parts['scheme'] ?? 'https' );
		$host = $this->normalize_domain( $parts['host'] ?? '' );
		$path = isset( $parts['path'] ) ? '/' . ltrim( $parts['path'], '/' ) : '/';
		return $scheme . '://' . $host . untrailingslashit( $path );
	}

	private function normalize_text( $value ) {
		$value = strtolower( remove_accents( wp_strip_all_tags( (string) $value ) ) );
		$value = preg_replace( '/[^a-z0-9]+/', ' ', $value );
		return trim( preg_replace( '/\s+/', ' ', $value ) );
	}

	private function sanitize_lines( $value ) {
		$items = is_array( $value ) ? $value : preg_split( '/\r\n|\r|\n/', (string) $value );
		$items = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', (array) $items ) ) ) );
		return implode( "\n", array_slice( $items, 0, 100 ) );
	}

	private function line_list( $value ) {
		$value = $this->sanitize_lines( $value );
		return $value ? explode( "\n", $value ) : array();
	}

	private function sanitize_datetime( $value ) {
		$timestamp = strtotime( (string) $value );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : current_time( 'mysql', true );
	}

	private function sanitize_observation_type( $value ) {
		$value = sanitize_key( $value );
		$allowed = array( 'organic_search', 'local_search', 'answer_engine', 'news', 'editorial', 'directory', 'video', 'social', 'other' );
		return in_array( $value, $allowed, true ) ? $value : 'organic_search';
	}

	private function sanitize_brand_role( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'own_brand', 'competitor', 'neutral_source' ), true ) ? $value : 'own_brand';
	}

	private function sanitize_mention_status( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'mentioned', 'cited', 'absent', 'unclear' ), true ) ? $value : 'mentioned';
	}

	private function sanitize_sentiment( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'positive', 'neutral', 'negative', 'mixed', 'unknown' ), true ) ? $value : 'unknown';
	}

	private function sanitize_confidence( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'low', 'medium', 'high' ), true ) ? $value : 'medium';
	}

	private function sanitize_mention_type( $value ) {
		$value = sanitize_key( $value );
		$allowed = array( 'editorial', 'news', 'directory', 'review', 'resource', 'forum', 'social', 'video', 'podcast', 'other' );
		return in_array( $value, $allowed, true ) ? $value : 'editorial';
	}

	private function sanitize_mention_workflow_status( $value ) {
		$value = sanitize_key( $value );
		$allowed = array( 'new', 'reviewed', 'opportunity', 'outreach_planned', 'correction_planned', 'converted', 'dismissed', 'archived' );
		return in_array( $value, $allowed, true ) ? $value : 'new';
	}
}
