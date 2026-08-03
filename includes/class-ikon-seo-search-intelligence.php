<?php

defined( 'ABSPATH' ) || exit;

/**
 * Persistent Search Console intelligence.
 *
 * Stores page-query evidence, groups related queries and identifies probable
 * cannibalisation, striking-distance opportunities and content decay. Results
 * are hypotheses based on Search Console evidence, never ranking guarantees.
 */
final class Ikon_SEO_Search_Intelligence {
	const CRON_HOOK = 'ikon_seo_search_intelligence_refresh';
	const CACHE_KEY = 'ikon_seo_search_intelligence_report';

	private $search_console;
	private $profile;
	private $logger;

	public function __construct( Ikon_SEO_Search_Console $search_console, Ikon_SEO_Profile $profile, Ikon_SEO_Logger $logger ) {
		$this->search_console = $search_console;
		$this->profile        = $profile;
		$this->logger         = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_refresh' ) );
	}

	public function scheduled_refresh() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['search_intelligence_enabled'] ) ) {
			return;
		}
		$status = $this->search_console->status();
		if ( empty( $status['connected'] ) || empty( $status['property'] ) ) {
			return;
		}
		$this->refresh( absint( $settings['search_intelligence_days'] ?? 28 ), absint( $settings['search_intelligence_max_rows'] ?? 50000 ) );
	}

	public function status() {
		global $wpdb;
		$table       = $this->rows_table();
		$cluster_tbl = $this->clusters_table();
		$exists      = $this->table_exists( $table );
		$property    = sanitize_text_field( Ikon_SEO_Plugin::settings()['gsc_property'] ?? '' );
		$property_hash = $this->property_hash( $property );
		$rows        = 0;
		$queries     = 0;
		$pages       = 0;
		$snapshots   = 0;
		$clusters    = 0;
		$last_sync   = '';

		if ( $exists && $property_hash ) {
			$summary = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) AS rows_count, COUNT(DISTINCT query_hash) AS query_count, COUNT(DISTINCT page_hash) AS page_count, COUNT(DISTINCT period_end) AS snapshot_count, MAX(fetched_at) AS last_sync FROM {$table} WHERE property_hash = %s",
					$property_hash
				),
				ARRAY_A
			);
			$rows      = absint( $summary['rows_count'] ?? 0 );
			$queries   = absint( $summary['query_count'] ?? 0 );
			$pages     = absint( $summary['page_count'] ?? 0 );
			$snapshots = absint( $summary['snapshot_count'] ?? 0 );
			$last_sync = sanitize_text_field( $summary['last_sync'] ?? '' );
			if ( $this->table_exists( $cluster_tbl ) ) {
				$clusters = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$cluster_tbl} WHERE property_hash = %s", $property_hash ) ) );
			}
		}

		return array(
			'enabled'      => ! empty( Ikon_SEO_Plugin::settings()['search_intelligence_enabled'] ),
			'connected'    => ! empty( $this->search_console->status()['connected'] ),
			'property'     => $property,
			'rows'         => $rows,
			'queries'      => $queries,
			'pages'        => $pages,
			'snapshots'    => $snapshots,
			'clusters'     => $clusters,
			'last_sync'    => $last_sync,
			'database_ready'=> $exists,
		);
	}

	public function refresh( $days = 28, $max_rows = 50000 ) {
		global $wpdb;
		$days     = max( 7, min( 90, absint( $days ) ) );
		$max_rows = max( 1000, min( 200000, absint( $max_rows ) ) );
		$status   = $this->search_console->status();
		$property = sanitize_text_field( $status['property'] ?? '' );
		if ( empty( $status['connected'] ) || ! $property ) {
			return new WP_Error( 'ikon_seo_search_intelligence_connection', __( 'Connect Search Console and select a property before refreshing Search Intelligence.', 'ikon-seo' ) );
		}
		if ( ! $this->table_exists( $this->rows_table() ) ) {
			return new WP_Error( 'ikon_seo_search_intelligence_table', __( 'The Search Intelligence database is not ready. Reactivate or update Ikon SEO to create its tables.', 'ikon-seo' ) );
		}

		$end            = gmdate( 'Y-m-d', strtotime( '-3 days' ) );
		$start          = gmdate( 'Y-m-d', strtotime( $end . ' -' . ( $days - 1 ) . ' days' ) );
		$previous_end   = gmdate( 'Y-m-d', strtotime( $start . ' -1 day' ) );
		$previous_start = gmdate( 'Y-m-d', strtotime( $previous_end . ' -' . ( $days - 1 ) . ' days' ) );

		$current = $this->search_console->detailed_rows( $start, $end, array( 'query', 'page', 'country', 'device' ), $max_rows );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$previous = $this->search_console->detailed_rows( $previous_start, $previous_end, array( 'query', 'page', 'country', 'device' ), $max_rows );
		if ( is_wp_error( $previous ) ) {
			return $previous;
		}

		$property_hash = $this->property_hash( $property );
		$stored_current = $this->store_period( $property_hash, $property, 'current', $start, $end, (array) ( $current['rows'] ?? array() ) );
		if ( is_wp_error( $stored_current ) ) {
			return $stored_current;
		}
		$stored_previous = $this->store_period( $property_hash, $property, 'previous', $previous_start, $previous_end, (array) ( $previous['rows'] ?? array() ) );
		if ( is_wp_error( $stored_previous ) ) {
			return $stored_previous;
		}
		$this->rebuild_clusters( $property_hash, $end );
		$this->prune_old_snapshots( $property_hash, 18 );
		delete_transient( self::CACHE_KEY );

		$settings                                      = Ikon_SEO_Plugin::settings();
		$settings['search_intelligence_days']           = $days;
		$settings['search_intelligence_max_rows']       = $max_rows;
		$settings['search_intelligence_last_error']     = '';
		$settings['search_intelligence_last_sync']      = current_time( 'mysql', true );
		$settings['search_intelligence_truncated']      = ! empty( $current['truncated'] ) || ! empty( $previous['truncated'] );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );

		$this->logger->log(
			'search_intelligence',
			'success',
			sprintf( 'Stored %d current and %d previous Search Console rows.', absint( $current['row_count'] ?? 0 ), absint( $previous['row_count'] ?? 0 ) )
		);

		return array(
			'current_period'  => array( 'start' => $start, 'end' => $end, 'rows' => absint( $current['row_count'] ?? 0 ), 'truncated' => ! empty( $current['truncated'] ) ),
			'previous_period' => array( 'start' => $previous_start, 'end' => $previous_end, 'rows' => absint( $previous['row_count'] ?? 0 ), 'truncated' => ! empty( $previous['truncated'] ) ),
			'report'          => $this->report( true ),
		);
	}

	public function report( $refresh_cache = false, $limit = 100 ) {
		if ( $refresh_cache ) {
			delete_transient( self::CACHE_KEY );
		}
		if ( ! $refresh_cache ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$status   = $this->status();
		$property = sanitize_text_field( $status['property'] ?? '' );
		if ( ! $property ) {
			return new WP_Error( 'ikon_seo_search_intelligence_property', __( 'Select a Search Console property first.', 'ikon-seo' ) );
		}
		if ( empty( $status['rows'] ) ) {
			return array(
				'status'       => $status,
				'period'       => array(),
				'summary'      => array( 'queries' => 0, 'pages' => 0, 'clusters' => 0, 'cannibalisation' => 0, 'striking_distance' => 0, 'content_decay' => 0 ),
				'clusters'     => array(),
				'cannibalisation' => array(),
				'striking_distance' => array(),
				'content_decay'=> array(),
				'gains'        => array(),
				'page_map'     => array(),
				'limitations'  => array( 'Run the first Search Intelligence refresh to build the page-query evidence database.' ),
			);
		}

		$property_hash = $this->property_hash( $property );
		$period         = $this->latest_period( $property_hash );
		if ( ! $period ) {
			return new WP_Error( 'ikon_seo_search_intelligence_period', __( 'No stored Search Console period is available.', 'ikon-seo' ) );
		}
		$current  = $this->aggregate_rows( $property_hash, $period['period_end'] );
		$previous = $this->aggregate_rows( $property_hash, $period['previous_end'] );
		$analysis = $this->analyse( $current, $previous, $limit );
		$clusters = $this->stored_clusters( $property_hash, $period['period_end'], $limit );

		$result = array(
			'status'      => $status,
			'period'      => $period,
			'summary'     => array(
				'queries'          => count( $current['queries'] ),
				'pages'            => count( $current['pages'] ),
				'clusters'         => count( $clusters ),
				'cannibalisation'  => count( $analysis['cannibalisation'] ),
				'striking_distance'=> count( $analysis['striking_distance'] ),
				'content_decay'    => count( $analysis['content_decay'] ),
				'new_gains'        => count( $analysis['gains'] ),
			),
			'clusters'            => $clusters,
			'cannibalisation'     => $analysis['cannibalisation'],
			'striking_distance'   => $analysis['striking_distance'],
			'content_decay'       => $analysis['content_decay'],
			'gains'               => $analysis['gains'],
			'page_map'            => $analysis['page_map'],
			'branded'             => $analysis['branded'],
			'limitations'         => array_values( array_filter( array(
				'Search Console may omit anonymized and low-volume query rows.',
				! empty( $period['truncated'] ) ? 'The stored dataset reached the configured row limit and may be incomplete.' : '',
				'Cannibalisation and decay are evidence-based hypotheses. Review search intent, current SERPs and backlinks before merging or redirecting pages.',
			) ) ),
			'methodology'         => 'Query-page rows are aggregated across country and device segments. Opportunity and cannibalisation classifications use impression share, position proximity and period-over-period movement; they are not Google ranking scores.',
			'generated_at'        => current_time( 'mysql', true ),
		);
		set_transient( self::CACHE_KEY, $result, 30 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function page_summary( $url ) {
		$report = $this->report( false, 250 );
		if ( ! is_array( $report ) ) {
			return array();
		}
		$key = $this->url_key( $url );
		return (array) ( $report['page_map'][ $key ] ?? array() );
	}

	private function store_period( $property_hash, $property, $period_type, $start, $end, array $rows ) {
		global $wpdb;
		$table = $this->rows_table();
		$wpdb->query( 'START TRANSACTION' );
		$deleted = $wpdb->delete( $table, array( 'property_hash' => $property_hash, 'period_end' => $end ), array( '%s', '%s' ) );
		if ( false === $deleted ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'ikon_seo_search_store_delete', __( 'The previous Search Intelligence period could not be prepared for replacement.', 'ikon-seo' ) );
		}
		$fetched = current_time( 'mysql', true );
		$records = array();
		foreach ( $rows as $row ) {
			$keys    = (array) ( $row['keys'] ?? array() );
			$query   = sanitize_text_field( $keys[0] ?? '' );
			$page    = esc_url_raw( $keys[1] ?? '' );
			$country = sanitize_key( $keys[2] ?? '' );
			$device  = sanitize_key( $keys[3] ?? '' );
			if ( ! $query || ! $page || ! $this->url_belongs_to_site( $page ) ) {
				continue;
			}
			$records[] = array(
				hash( 'sha256', implode( '|', array( $property_hash, $end, $this->normalize_query( $query ), $this->url_key( $page ), $country, $device ) ) ),
				$property_hash,
				$property,
				sanitize_key( $period_type ),
				$start,
				$end,
				hash( 'sha256', $this->normalize_query( $query ) ),
				hash( 'sha256', $this->url_key( $page ) ),
				$query,
				$page,
				$country,
				$device,
				(float) ( $row['clicks'] ?? 0 ),
				(float) ( $row['impressions'] ?? 0 ),
				(float) ( $row['ctr'] ?? 0 ),
				(float) ( $row['position'] ?? 0 ),
				$fetched,
			);
		}

		$columns = 'row_hash, property_hash, property_id, period_type, period_start, period_end, query_hash, page_hash, query_text, page_url, country, device, clicks, impressions, ctr, position, fetched_at';
		$format  = '( %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %f, %f, %f, %f, %s )';
		foreach ( array_chunk( $records, 250 ) as $chunk ) {
			$placeholders = array();
			$values       = array();
			foreach ( $chunk as $record ) {
				$placeholders[] = $format;
				$values = array_merge( $values, $record );
			}
			$sql = "INSERT INTO {$table} ({$columns}) VALUES " . implode( ', ', $placeholders ) . ' ON DUPLICATE KEY UPDATE clicks = VALUES(clicks), impressions = VALUES(impressions), ctr = VALUES(ctr), position = VALUES(position), fetched_at = VALUES(fetched_at)';
			$result = $wpdb->query( $wpdb->prepare( $sql, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( false === $result ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'ikon_seo_search_store_insert', __( 'Search Intelligence rows could not be stored. The previous stored period was preserved where transactions are supported.', 'ikon-seo' ) );
			}
		}
		$wpdb->query( 'COMMIT' );
		return true;
	}

	private function aggregate_rows( $property_hash, $period_end ) {
		global $wpdb;
		$table = $this->rows_table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT query_text, page_url, SUM(clicks) AS clicks, SUM(impressions) AS impressions, SUM(position * impressions) AS weighted_position FROM {$table} WHERE property_hash = %s AND period_end = %s GROUP BY query_hash, page_hash, query_text, page_url",
				$property_hash,
				$period_end
			),
			ARRAY_A
		);
		$queries = array();
		$pages   = array();
		$pairs   = array();
		foreach ( (array) $rows as $row ) {
			$impressions = (float) $row['impressions'];
			$pair = array(
				'query'       => sanitize_text_field( $row['query_text'] ),
				'page'        => esc_url_raw( $row['page_url'] ),
				'clicks'      => (float) $row['clicks'],
				'impressions' => $impressions,
				'ctr'         => $impressions > 0 ? (float) $row['clicks'] / $impressions : 0,
				'position'    => $impressions > 0 ? (float) $row['weighted_position'] / $impressions : 0,
			);
			$qkey = $this->normalize_query( $pair['query'] );
			$pkey = $this->url_key( $pair['page'] );
			$pairs[ $qkey . '|' . $pkey ] = $pair;
			if ( ! isset( $queries[ $qkey ] ) ) {
				$queries[ $qkey ] = array( 'query' => $pair['query'], 'clicks' => 0, 'impressions' => 0, 'pages' => array() );
			}
			$queries[ $qkey ]['clicks'] += $pair['clicks'];
			$queries[ $qkey ]['impressions'] += $pair['impressions'];
			$queries[ $qkey ]['pages'][ $pkey ] = $pair;
			if ( ! isset( $pages[ $pkey ] ) ) {
				$pages[ $pkey ] = array( 'url' => $pair['page'], 'clicks' => 0, 'impressions' => 0, 'weighted_position' => 0, 'queries' => array() );
			}
			$pages[ $pkey ]['clicks'] += $pair['clicks'];
			$pages[ $pkey ]['impressions'] += $pair['impressions'];
			$pages[ $pkey ]['weighted_position'] += $pair['position'] * $pair['impressions'];
			$pages[ $pkey ]['queries'][ $qkey ] = $pair;
		}
		foreach ( $pages as &$page ) {
			$page['position'] = $page['impressions'] > 0 ? round( $page['weighted_position'] / $page['impressions'], 2 ) : 0;
			$page['ctr']      = $page['impressions'] > 0 ? $page['clicks'] / $page['impressions'] : 0;
			unset( $page['weighted_position'] );
		}
		unset( $page );
		return array( 'queries' => $queries, 'pages' => $pages, 'pairs' => $pairs );
	}

	private function analyse( array $current, array $previous, $limit ) {
		$settings        = Ikon_SEO_Plugin::settings();
		$min_impressions = max( 5, absint( $settings['search_intelligence_min_impressions'] ?? 20 ) );
		$decay_percent   = max( 10, min( 90, absint( $settings['search_intelligence_decay_percent'] ?? 30 ) ) );
		$cannibalisation = array();
		$striking        = array();
		$decay           = array();
		$gains           = array();
		$page_map        = array();
		$branded         = array( 'branded' => array( 'clicks' => 0, 'impressions' => 0 ), 'non_branded' => array( 'clicks' => 0, 'impressions' => 0 ) );

		foreach ( $current['queries'] as $qkey => $query ) {
			$is_branded = $this->is_branded_query( $query['query'] );
			$bucket = $is_branded ? 'branded' : 'non_branded';
			$branded[ $bucket ]['clicks'] += $query['clicks'];
			$branded[ $bucket ]['impressions'] += $query['impressions'];

			$pages = array_values( $query['pages'] );
			usort( $pages, function( $a, $b ) { return $b['impressions'] <=> $a['impressions']; } );
			if ( count( $pages ) > 1 && $query['impressions'] >= $min_impressions ) {
				$top          = $pages[0];
				$second       = $pages[1];
				$second_share = $query['impressions'] > 0 ? $second['impressions'] / $query['impressions'] : 0;
				$gap          = abs( $top['position'] - $second['position'] );
				$previous_top = $this->top_query_page( $previous['queries'][ $qkey ] ?? array() );
				$switching    = $previous_top && $this->url_key( $previous_top['page'] ) !== $this->url_key( $top['page'] );
				$classification = 'healthy_supporting';
				$confidence     = 'low';
				if ( $second_share >= 0.25 && $gap <= 8 ) {
					$classification = $switching ? 'url_switching' : 'strong_cannibalisation';
					$confidence     = 'high';
				} elseif ( $second_share >= 0.10 || $switching ) {
					$classification = $switching ? 'url_switching' : 'partial_overlap';
					$confidence     = 'medium';
				}
				if ( 'healthy_supporting' !== $classification ) {
					$item = array(
						'query'          => $query['query'],
						'classification' => $classification,
						'confidence'     => $confidence,
						'impressions'    => round( $query['impressions'], 2 ),
						'clicks'         => round( $query['clicks'], 2 ),
						'pages'          => array_slice( $pages, 0, 5 ),
						'evidence'       => sprintf( '%d pages received impressions; the second page held %.1f%% of query impressions and the leading positions were %.1f places apart.', count( $pages ), $second_share * 100, $gap ),
						'recommended_action' => 'Review the intent and content of both pages before consolidating anything. Reposition, improve internal links or merge only when the pages serve the same intent.',
					);
					$cannibalisation[] = $item;
					foreach ( array_slice( $pages, 0, 5 ) as $page ) {
						$pkey = $this->url_key( $page['page'] );
						$page_map[ $pkey ]['cannibalisation'][] = $item;
					}
				}
			}

			foreach ( $pages as $pair ) {
				if ( $pair['impressions'] >= $min_impressions && $pair['position'] >= 8 && $pair['position'] <= 20 ) {
					$opportunity = array(
						'query'       => $query['query'],
						'page'        => $pair['page'],
						'clicks'      => round( $pair['clicks'], 2 ),
						'impressions' => round( $pair['impressions'], 2 ),
						'ctr'         => round( $pair['ctr'] * 100, 2 ),
						'position'    => round( $pair['position'], 2 ),
						'priority'    => min( 100, (int) round( 35 + min( 45, log( max( 2, $pair['impressions'] ), 2 ) * 5 ) + max( 0, 20 - $pair['position'] ) ) ),
						'recommended_action' => 'Compare current search intent and ranking pages, then strengthen relevance, useful evidence and contextual internal links before considering a rewrite.',
					);
					$striking[] = $opportunity;
					$pkey = $this->url_key( $pair['page'] );
					$page_map[ $pkey ]['striking_distance'][] = $opportunity;
				}
			}
		}

		foreach ( $current['pages'] as $pkey => $page ) {
			$old = (array) ( $previous['pages'][ $pkey ] ?? array() );
			$page_map[ $pkey ]['performance'] = $page;
			$page_map[ $pkey ]['top_queries']  = $this->top_queries_for_page( $page, 10 );
			if ( ! $old || (float) ( $old['impressions'] ?? 0 ) < $min_impressions ) {
				if ( $page['impressions'] >= $min_impressions ) {
					$gain = array( 'page' => $page['url'], 'current_impressions' => round( $page['impressions'], 2 ), 'current_clicks' => round( $page['clicks'], 2 ), 'status' => 'new_or_returning_visibility' );
					$gains[] = $gain;
					$page_map[ $pkey ]['gain'] = $gain;
				}
				continue;
			}
			$impression_change = $this->percent_change( $page['impressions'], $old['impressions'] );
			$click_change      = $this->percent_change( $page['clicks'], $old['clicks'] );
			if ( null !== $impression_change && $impression_change <= -$decay_percent ) {
				$item = array(
					'page'                 => $page['url'],
					'current_clicks'       => round( $page['clicks'], 2 ),
					'previous_clicks'      => round( (float) $old['clicks'], 2 ),
					'clicks_change'        => $click_change,
					'current_impressions'  => round( $page['impressions'], 2 ),
					'previous_impressions' => round( (float) $old['impressions'], 2 ),
					'impressions_change'   => $impression_change,
					'position_change'      => round( (float) $old['position'] - (float) $page['position'], 2 ),
					'confidence'           => (float) $old['impressions'] >= 100 ? 'high' : 'medium',
					'recommended_action'   => 'Review query-level losses, SERP changes, indexing, freshness and competing internal pages before editing the page.',
				);
				$decay[] = $item;
				$page_map[ $pkey ]['content_decay'] = $item;
			}
		}

		usort( $cannibalisation, function( $a, $b ) { return $b['impressions'] <=> $a['impressions']; } );
		usort( $striking, function( $a, $b ) { return $b['priority'] <=> $a['priority']; } );
		usort( $decay, function( $a, $b ) { return $a['impressions_change'] <=> $b['impressions_change']; } );
		usort( $gains, function( $a, $b ) { return $b['current_impressions'] <=> $a['current_impressions']; } );

		return array(
			'cannibalisation' => array_slice( $cannibalisation, 0, $limit ),
			'striking_distance'=> array_slice( $striking, 0, $limit ),
			'content_decay'   => array_slice( $decay, 0, $limit ),
			'gains'           => array_slice( $gains, 0, $limit ),
			'page_map'        => $page_map,
			'branded'         => $branded,
		);
	}

	private function rebuild_clusters( $property_hash, $period_end ) {
		global $wpdb;
		$table = $this->clusters_table();
		if ( ! $this->table_exists( $table ) ) {
			return;
		}
		$current = $this->aggregate_rows( $property_hash, $period_end );
		$clusters = array();
		foreach ( $current['queries'] as $query ) {
			$key = $this->cluster_key( $query['query'] );
			if ( ! isset( $clusters[ $key ] ) ) {
				$clusters[ $key ] = array( 'label' => $this->cluster_label( $query['query'] ), 'queries' => array(), 'clicks' => 0, 'impressions' => 0, 'pages' => array() );
			}
			$clusters[ $key ]['queries'][] = $query['query'];
			$clusters[ $key ]['clicks'] += $query['clicks'];
			$clusters[ $key ]['impressions'] += $query['impressions'];
			foreach ( $query['pages'] as $page ) {
				$pkey = $this->url_key( $page['page'] );
				$clusters[ $key ]['pages'][ $pkey ] = ( $clusters[ $key ]['pages'][ $pkey ] ?? 0 ) + $page['impressions'];
			}
		}
		$wpdb->delete( $table, array( 'property_hash' => $property_hash, 'period_end' => $period_end ), array( '%s', '%s' ) );
		foreach ( $clusters as $key => $cluster ) {
			arsort( $cluster['pages'] );
			$top_page_key = key( $cluster['pages'] );
			$top_page     = '';
			foreach ( $current['pages'] as $pkey => $page ) {
				if ( $pkey === $top_page_key ) {
					$top_page = $page['url'];
					break;
				}
			}
			$wpdb->insert(
				$table,
				array(
					'cluster_hash'  => hash( 'sha256', $property_hash . '|' . $period_end . '|' . $key ),
					'property_hash' => $property_hash,
					'period_end'    => $period_end,
					'cluster_key'   => $key,
					'cluster_label' => $cluster['label'],
					'query_count'   => count( array_unique( $cluster['queries'] ) ),
					'clicks'        => $cluster['clicks'],
					'impressions'   => $cluster['impressions'],
					'top_page'      => $top_page,
					'queries_json'  => wp_json_encode( array_values( array_unique( $cluster['queries'] ) ) ),
					'updated_at'    => current_time( 'mysql', true ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%f', '%s', '%s', '%s' )
			);
		}
	}

	private function stored_clusters( $property_hash, $period_end, $limit ) {
		global $wpdb;
		$table = $this->clusters_table();
		if ( ! $this->table_exists( $table ) ) {
			return array();
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT cluster_key, cluster_label, query_count, clicks, impressions, top_page, queries_json FROM {$table} WHERE property_hash = %s AND period_end = %s ORDER BY impressions DESC LIMIT %d",
				$property_hash,
				$period_end,
				max( 1, min( 500, absint( $limit ) ) )
			),
			ARRAY_A
		);
		foreach ( $rows as &$row ) {
			$row['query_count'] = absint( $row['query_count'] );
			$row['clicks']      = (float) $row['clicks'];
			$row['impressions'] = (float) $row['impressions'];
			$row['queries']     = json_decode( (string) $row['queries_json'], true );
			unset( $row['queries_json'] );
		}
		unset( $row );
		return $rows;
	}

	private function latest_period( $property_hash ) {
		global $wpdb;
		$table = $this->rows_table();
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT period_start, period_end, MAX(fetched_at) AS fetched_at FROM {$table} WHERE property_hash = %s AND period_type = 'current' GROUP BY period_start, period_end ORDER BY period_end DESC LIMIT 1",
				$property_hash
			),
			ARRAY_A
		);
		if ( ! $row ) {
			return array();
		}
		$days = max( 1, (int) floor( ( strtotime( $row['period_end'] ) - strtotime( $row['period_start'] ) ) / DAY_IN_SECONDS ) + 1 );
		$row['previous_end']   = gmdate( 'Y-m-d', strtotime( $row['period_start'] . ' -1 day' ) );
		$row['previous_start'] = gmdate( 'Y-m-d', strtotime( $row['previous_end'] . ' -' . ( $days - 1 ) . ' days' ) );
		$row['days']           = $days;
		$row['truncated']      = ! empty( Ikon_SEO_Plugin::settings()['search_intelligence_truncated'] );
		return $row;
	}

	private function top_query_page( array $query ) {
		if ( empty( $query['pages'] ) ) {
			return array();
		}
		$pages = array_values( $query['pages'] );
		usort( $pages, function( $a, $b ) { return $b['impressions'] <=> $a['impressions']; } );
		return $pages[0] ?? array();
	}

	private function top_queries_for_page( array $page, $limit ) {
		$queries = array_values( (array) ( $page['queries'] ?? array() ) );
		usort( $queries, function( $a, $b ) { return $b['impressions'] <=> $a['impressions']; } );
		return array_slice( $queries, 0, $limit );
	}

	private function cluster_key( $query ) {
		$tokens = $this->query_tokens( $query );
		if ( ! $tokens ) {
			return 'other-' . substr( hash( 'sha256', $query ), 0, 12 );
		}
		return implode( '-', array_slice( $tokens, 0, 5 ) );
	}

	private function cluster_label( $query ) {
		$tokens = $this->query_tokens( $query );
		return $tokens ? ucwords( implode( ' ', array_slice( $tokens, 0, 5 ) ) ) : sanitize_text_field( $query );
	}

	private function query_tokens( $query ) {
		$query = $this->normalize_query( $query );
		$raw   = preg_split( '/[^\p{L}\p{N}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY );
		$stop  = array_fill_keys( array( 'the', 'and', 'for', 'with', 'from', 'that', 'this', 'your', 'our', 'you', 'are', 'near', 'me', 'in', 'on', 'at', 'to', 'of', 'a', 'an', 'best', 'top', 'service', 'services', 'company', 'companies' ), true );
		$tokens = array();
		foreach ( $raw as $token ) {
			if ( isset( $stop[ $token ] ) || strlen( $token ) < 2 ) {
				continue;
			}
			if ( strlen( $token ) > 5 ) {
				$token = preg_replace( '/(?:ing|ers|ies|ed|es)$/', '', $token );
			} elseif ( strlen( $token ) > 4 && 's' === substr( $token, -1 ) ) {
				$token = substr( $token, 0, -1 );
			}
			$tokens[] = $token;
		}
		return array_values( array_unique( array_filter( $tokens ) ) );
	}

	private function is_branded_query( $query ) {
		$profile = $this->profile->get();
		$terms   = array_merge(
			$this->query_tokens( get_bloginfo( 'name' ) ),
			$this->query_tokens( $profile['site_name'] ?? '' ),
			$this->query_tokens( wp_parse_url( home_url( '/' ), PHP_URL_HOST ) )
		);
		$query_tokens = $this->query_tokens( $query );
		return (bool) array_intersect( array_unique( $terms ), $query_tokens );
	}

	private function normalize_query( $query ) {
		$query = remove_accents( strtolower( html_entity_decode( wp_strip_all_tags( (string) $query ) ) ) );
		$query = preg_replace( '/\s+/u', ' ', trim( $query ) );
		return sanitize_text_field( $query );
	}

	private function percent_change( $current, $previous ) {
		$current  = (float) $current;
		$previous = (float) $previous;
		if ( 0.0 === $previous ) {
			return 0.0 === $current ? 0 : null;
		}
		return round( ( ( $current - $previous ) / $previous ) * 100, 2 );
	}

	private function prune_old_snapshots( $property_hash, $keep ) {
		global $wpdb;
		$table = $this->rows_table();
		$dates = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT period_end FROM {$table} WHERE property_hash = %s ORDER BY period_end DESC",
				$property_hash
			)
		);
		$delete = array_slice( (array) $dates, max( 2, absint( $keep ) ) );
		foreach ( $delete as $date ) {
			$wpdb->delete( $table, array( 'property_hash' => $property_hash, 'period_end' => $date ), array( '%s', '%s' ) );
		}
	}

	private function url_belongs_to_site( $url ) {
		$url_host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		return $url_host && $site_host && hash_equals( $site_host, $url_host );
	}

	private function url_key( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return $host . untrailingslashit( '/' . ltrim( $path, '/' ) );
	}

	private function property_hash( $property ) {
		return $property ? hash( 'sha256', $property ) : '';
	}

	private function rows_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_search_rows';
	}

	private function clusters_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_search_clusters';
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}
}
