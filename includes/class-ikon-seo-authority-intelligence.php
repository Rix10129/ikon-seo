<?php

defined( 'ABSPATH' ) || exit;

/**
 * Authority and off-site evidence.
 *
 * Imports backlink evidence from administrator-approved CSV files or connected
 * workflows, maps referring domains and anchors, finds broken-link recovery
 * opportunities, and compares competitor link-source gaps. It intentionally
 * avoids proprietary authority claims and never creates or removes links.
 */
final class Ikon_SEO_Authority_Intelligence {
	const CACHE_KEY = 'ikon_seo_authority_intelligence_report';
	const MAX_SYNC_RECORDS = 1000;
	const MAX_CSV_ROWS = 20000;

	private $profile;
	private $inventory;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->profile   = $profile;
		$this->inventory = $inventory;
		$this->history   = $history;
		$this->logger    = $logger;
	}

	public function links_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_backlinks';
	}

	public function imports_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_backlink_imports';
	}

	public function status() {
		global $wpdb;
		$table = $this->links_table();
		$ready = $this->table_exists( $table ) && $this->table_exists( $this->imports_table() );
		if ( ! $ready ) {
			return array(
				'enabled'           => ! empty( Ikon_SEO_Plugin::settings()['authority_intelligence_enabled'] ),
				'database_ready'    => false,
				'backlinks'         => 0,
				'referring_domains' => 0,
				'competitor_links'  => 0,
				'competitors'       => 0,
				'imports'           => 0,
				'last_updated'      => '',
			);
		}

		$backlinks = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE relationship = 'site_backlink' AND status <> 'archived'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$domains   = absint( $wpdb->get_var( "SELECT COUNT(DISTINCT source_domain) FROM {$table} WHERE relationship = 'site_backlink' AND status = 'active'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$comp      = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE relationship = 'competitor_backlink' AND status <> 'archived'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$competitors = absint( $wpdb->get_var( "SELECT COUNT(DISTINCT competitor_domain) FROM {$table} WHERE relationship = 'competitor_backlink' AND status <> 'archived' AND competitor_domain <> ''" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$imports   = absint( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $this->imports_table() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$updated   = sanitize_text_field( $wpdb->get_var( "SELECT MAX(updated_at) FROM {$table}" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'enabled'           => ! empty( Ikon_SEO_Plugin::settings()['authority_intelligence_enabled'] ),
			'database_ready'    => true,
			'backlinks'         => $backlinks,
			'referring_domains' => $domains,
			'competitor_links'  => $comp,
			'competitors'       => $competitors,
			'imports'           => $imports,
			'last_updated'      => $updated,
		);
	}

	/**
	 * Read the current report and optionally store approved observations.
	 */
	public function sync( array $payload, $created_by = 0 ) {
		if ( ! $this->table_exists( $this->links_table() ) ) {
			return new WP_Error( 'ikon_seo_authority_tables', __( 'Authority Intelligence tables are not ready. Update or reactivate Ikon SEO.', 'ikon-seo' ) );
		}

		$saved = array();
		if ( ! empty( $payload['links'] ) ) {
			$records = isset( $payload['links'][0] ) ? (array) $payload['links'] : array( $payload['links'] );
			if ( count( $records ) > self::MAX_SYNC_RECORDS ) {
				return new WP_Error( 'ikon_seo_authority_batch', sprintf( __( 'A maximum of %d link observations can be stored per request.', 'ikon-seo' ), self::MAX_SYNC_RECORDS ) );
			}
			$relationship = sanitize_key( $payload['relationship'] ?? 'site_backlink' );
			$provider     = sanitize_key( $payload['provider'] ?? 'connected_research' );
			$competitor   = sanitize_text_field( $payload['competitor_domain'] ?? '' );
			$result = $this->save_records( $records, $provider, $relationship, $competitor, absint( $created_by ) );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$saved = $result;
		}

		$limit = max( 10, min( 500, absint( $payload['limit'] ?? 100 ) ) );
		return array(
			'saved'  => $saved,
			'report' => $this->report( $limit, true ),
		);
	}

	public function save_records( array $records, $provider = 'manual', $relationship = 'site_backlink', $competitor_domain = '', $created_by = 0, $batch_id = '' ) {
		$provider     = $this->sanitize_provider( $provider );
		$relationship = $this->sanitize_relationship( $relationship );
		$competitor_domain = $this->normalize_domain( $competitor_domain );
		if ( 'competitor_backlink' === $relationship && ! $competitor_domain ) {
			// It may still be inferred from each target URL.
			$competitor_domain = '';
		}
		$batch_id = $batch_id ? sanitize_text_field( $batch_id ) : wp_generate_uuid4();
		$summary = array( 'batch_id' => $batch_id, 'seen' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => array() );

		foreach ( $records as $record ) {
			$summary['seen']++;
			$result = $this->save_record( (array) $record, $provider, $relationship, $competitor_domain, $created_by, $batch_id );
			if ( is_wp_error( $result ) ) {
				$summary['skipped']++;
				if ( count( $summary['errors'] ) < 20 ) {
					$summary['errors'][] = $result->get_error_message();
				}
				continue;
			}
			$summary['imported']++;
		}

		delete_transient( self::CACHE_KEY );
		if ( $summary['imported'] ) {
			$this->logger->log( 'authority_import', 'success', sprintf( 'Stored %d off-site link observations.', $summary['imported'] ), 0, 0, array( 'provider' => $provider, 'relationship' => $relationship ) );
		}
		return $summary;
	}

	public function save_record( array $record, $provider, $relationship, $competitor_domain, $created_by, $batch_id ) {
		global $wpdb;
		$source_url = esc_url_raw( $record['source_url'] ?? $record['referring_url'] ?? '' );
		$target_url = esc_url_raw( $record['target_url'] ?? $record['destination_url'] ?? '' );
		if ( ! $this->valid_http_url( $source_url ) || ! $this->valid_http_url( $target_url ) ) {
			return new WP_Error( 'ikon_seo_authority_url', __( 'Each link observation requires valid source and target HTTP or HTTPS URLs.', 'ikon-seo' ) );
		}

		$source_domain = $this->normalize_domain( wp_parse_url( $source_url, PHP_URL_HOST ) );
		$target_domain = $this->normalize_domain( wp_parse_url( $target_url, PHP_URL_HOST ) );
		if ( ! $source_domain || ! $target_domain || $source_domain === $target_domain ) {
			return new WP_Error( 'ikon_seo_authority_external', __( 'The source and target must be different valid domains.', 'ikon-seo' ) );
		}

		$site_domain = $this->site_domain();
		if ( 'site_backlink' === $relationship && $target_domain !== $site_domain ) {
			return new WP_Error( 'ikon_seo_authority_target', __( 'A website backlink must target the connected website domain.', 'ikon-seo' ) );
		}
		if ( 'competitor_backlink' === $relationship ) {
			$competitor_domain = $this->normalize_domain( $competitor_domain ?: $target_domain );
			if ( ! $competitor_domain || $target_domain !== $competitor_domain ) {
				return new WP_Error( 'ikon_seo_authority_competitor', __( 'A competitor backlink must target the selected competitor domain.', 'ikon-seo' ) );
			}
		}

		$anchor       = sanitize_text_field( $record['anchor_text'] ?? $record['anchor'] ?? '' );
		$link_type    = $this->sanitize_link_type( $record['link_type'] ?? $record['rel'] ?? '' );
		$status       = $this->sanitize_status( $record['status'] ?? '' );
		$first_seen   = $this->sanitize_date( $record['first_seen'] ?? '' );
		$last_seen    = $this->sanitize_date( $record['last_seen'] ?? '' );
		$observed_at  = $this->sanitize_date( $record['observed_at'] ?? gmdate( 'Y-m-d' ) );
		$strength     = max( 0, min( 100, (float) ( $record['source_strength'] ?? $record['authority_metric'] ?? 0 ) ) );
		$traffic      = max( 0, (int) ( $record['source_traffic'] ?? 0 ) );
		$source_title = sanitize_text_field( $record['source_title'] ?? '' );
		$notes        = sanitize_textarea_field( $record['notes'] ?? '' );
		$raw_metrics  = is_array( $record['raw_metrics'] ?? null ) ? $this->sanitize_metrics( $record['raw_metrics'] ) : array();
		$source_key   = $this->url_key( $source_url );
		$target_key   = $this->url_key( $target_url );
		$link_hash    = hash( 'sha256', implode( '|', array( $relationship, $source_key, $target_key, $this->normalize_text( $anchor ) ) ) );
		$target_hash  = hash( 'sha256', $target_key );
		$now          = current_time( 'mysql', true );

		$sql = "INSERT INTO {$this->links_table()}
			(link_hash,relationship,provider,import_batch,source_url,source_domain,source_title,target_url,target_hash,target_domain,competitor_domain,anchor_text,link_type,status,source_strength,source_traffic,first_seen,last_seen,observed_at,raw_metrics_json,notes,created_by,created_at,updated_at)
			VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%f,%d,NULLIF(%s,''),NULLIF(%s,''),NULLIF(%s,''),%s,%s,%d,%s,%s)
			ON DUPLICATE KEY UPDATE provider=VALUES(provider), import_batch=VALUES(import_batch), source_title=VALUES(source_title), link_type=VALUES(link_type), status=VALUES(status), source_strength=VALUES(source_strength), source_traffic=VALUES(source_traffic), first_seen=COALESCE(VALUES(first_seen),first_seen), last_seen=COALESCE(VALUES(last_seen),last_seen), observed_at=VALUES(observed_at), raw_metrics_json=VALUES(raw_metrics_json), notes=VALUES(notes), updated_at=VALUES(updated_at)";
		$result = $wpdb->query(
			$wpdb->prepare(
				$sql,
				$link_hash,
				$relationship,
				$provider,
				$batch_id,
				$source_url,
				$source_domain,
				$source_title,
				$target_url,
				$target_hash,
				$target_domain,
				$competitor_domain,
				$anchor,
				$link_type,
				$status,
				$strength,
				$traffic,
				$first_seen,
				$last_seen,
				$observed_at,
				wp_json_encode( $raw_metrics ),
				$notes,
				absint( $created_by ),
				$now,
				$now
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_authority_store', __( 'The link observation could not be stored.', 'ikon-seo' ) );
		}
		return array( 'link_hash' => $link_hash, 'source_domain' => $source_domain, 'target_url' => $target_url, 'status' => $status );
	}

	/**
	 * Import a CSV exported from a common backlink provider or a generic file.
	 */
	public function import_csv( $file_path, $original_name, $provider, $relationship, $competitor_domain, $created_by = 0 ) {
		if ( ! is_readable( $file_path ) ) {
			return new WP_Error( 'ikon_seo_authority_file', __( 'The backlink CSV file could not be read.', 'ikon-seo' ) );
		}
		$provider     = $this->sanitize_provider( $provider );
		$relationship = $this->sanitize_relationship( $relationship );
		$batch_id     = wp_generate_uuid4();
		$handle       = fopen( $file_path, 'rb' );
		if ( ! $handle ) {
			return new WP_Error( 'ikon_seo_authority_file', __( 'The backlink CSV file could not be opened.', 'ikon-seo' ) );
		}

		$first_line = fgets( $handle );
		if ( false === $first_line ) {
			fclose( $handle );
			return new WP_Error( 'ikon_seo_authority_empty', __( 'The backlink CSV file is empty.', 'ikon-seo' ) );
		}
		$delimiter = $this->detect_delimiter( $first_line );
		rewind( $handle );
		$headers = fgetcsv( $handle, 0, $delimiter );
		if ( ! is_array( $headers ) || count( $headers ) < 2 ) {
			fclose( $handle );
			return new WP_Error( 'ikon_seo_authority_headers', __( 'The backlink CSV requires a header row.', 'ikon-seo' ) );
		}
		$normalized_headers = array_map( array( $this, 'normalize_header' ), $headers );
		$records = array();
		$seen = 0;
		$settings = Ikon_SEO_Plugin::settings();
		$max_rows = max( 100, min( self::MAX_CSV_ROWS, absint( $settings['authority_import_max_rows'] ?? self::MAX_CSV_ROWS ) ) );
		while ( $seen < $max_rows && ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
			$seen++;
			if ( ! array_filter( $row, 'strlen' ) ) {
				continue;
			}
			$assoc = array();
			foreach ( $normalized_headers as $index => $header ) {
				$assoc[ $header ] = isset( $row[ $index ] ) ? trim( (string) $row[ $index ] ) : '';
			}
			$mapped = $this->map_csv_row( $assoc );
			if ( $mapped['source_url'] && $mapped['target_url'] ) {
				$records[] = $mapped;
			}
		}
		$truncated = false;
		if ( $seen >= $max_rows && false !== fgetcsv( $handle, 0, $delimiter ) ) {
			$truncated = true;
		}
		fclose( $handle );

		if ( ! $records ) {
			return new WP_Error( 'ikon_seo_authority_columns', __( 'No usable source URL and target URL columns were found. Use the provided generic template or map a supported provider export.', 'ikon-seo' ) );
		}
		$result = $this->save_records( $records, $provider, $relationship, $competitor_domain, $created_by, $batch_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$result['truncated'] = $truncated;
		$result['max_rows']  = $max_rows;

		global $wpdb;
		$wpdb->insert(
			$this->imports_table(),
			array(
				'batch_id'          => $batch_id,
				'provider'          => $provider,
				'relationship'      => $relationship,
				'competitor_domain' => $this->normalize_domain( $competitor_domain ),
				'filename'          => sanitize_file_name( $original_name ),
				'rows_seen'         => absint( $result['seen'] ),
				'rows_imported'     => absint( $result['imported'] ),
				'rows_skipped'      => absint( $result['skipped'] ),
				'created_by'        => absint( $created_by ),
				'created_at'        => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s' )
		);

		$this->history->add(
			array(
				'category'        => 'research',
				'status'          => 'completed',
				'title'           => __( 'Off-site evidence imported', 'ikon-seo' ),
				'summary'         => sprintf( __( '%1$d link observations were imported from %2$s.', 'ikon-seo' ), absint( $result['imported'] ), $provider ),
				'details'         => array( 'batch_id' => $batch_id, 'relationship' => $relationship, 'competitor_domain' => $this->normalize_domain( $competitor_domain ), 'truncated' => $truncated, 'max_rows' => $max_rows ),
				'related_post_id' => 0,
			),
			'wordpress',
			absint( $created_by )
		);

		return $result;
	}

	public function report( $limit = 100, $refresh = false ) {
		global $wpdb;
		$limit = max( 10, min( 500, absint( $limit ) ) );
		if ( $refresh ) {
			delete_transient( self::CACHE_KEY );
		}
		if ( ! $refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		if ( ! $this->table_exists( $this->links_table() ) ) {
			return new WP_Error( 'ikon_seo_authority_tables', __( 'Authority Intelligence tables are not ready.', 'ikon-seo' ) );
		}

		$table = $this->links_table();
		$analysis_limit = max( 2000, min( 10000, $limit * 20 ) );
		$active_total = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE relationship='site_backlink' AND status='active'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$lost_total = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE relationship='site_backlink' AND status='lost'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$competitor_total = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE relationship='competitor_backlink' AND status='active'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$domain_total = absint( $wpdb->get_var( "SELECT COUNT(DISTINCT source_domain) FROM {$table} WHERE relationship='site_backlink' AND status='active'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$linked_page_total = absint( $wpdb->get_var( "SELECT COUNT(DISTINCT target_hash) FROM {$table} WHERE relationship='site_backlink' AND status='active'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$active_links = (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE relationship='site_backlink' AND status='active' ORDER BY source_strength DESC, updated_at DESC LIMIT %d", $analysis_limit ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);
		$lost_links = (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE relationship='site_backlink' AND status='lost' ORDER BY updated_at DESC LIMIT %d", min( $analysis_limit, max( $limit, 500 ) ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);
		$competitor_links = (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE relationship='competitor_backlink' AND status='active' ORDER BY source_strength DESC, updated_at DESC LIMIT %d", $analysis_limit ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		$referring_domains = $this->referring_domain_summary( $active_links );
		$target_pages      = $this->target_page_summary( $active_links );
		$anchors           = $this->anchor_summary( $active_links );
		$recovery          = $this->broken_recovery( array_merge( $active_links, $lost_links ) );
		$link_gaps         = $this->competitor_gaps( $active_links, $competitor_links );
		$unlinked_pages    = $this->unlinked_priority_pages( $target_pages );
		$hypotheses        = $this->authority_hypotheses( $active_links, $anchors, $unlinked_pages );

		$result = array(
			'generated_at' => current_time( 'mysql', true ),
			'status'       => $this->status(),
			'summary'      => array(
				'active_backlinks'       => $active_total,
				'lost_backlinks'         => $lost_total,
				'referring_domains'      => $domain_total,
				'linked_pages'           => $linked_page_total,
				'competitor_links'       => $competitor_total,
				'broken_recovery_items'  => count( $recovery ),
				'competitor_gap_domains' => count( $link_gaps ),
				'unlinked_priority_pages'=> count( $unlinked_pages ),
			),
			'referring_domains' => array_slice( $referring_domains, 0, $limit ),
			'target_pages'      => array_slice( $target_pages, 0, $limit ),
			'anchor_distribution'=> $anchors,
			'broken_link_recovery'=> array_slice( $recovery, 0, $limit ),
			'lost_links'        => array_slice( array_map( array( $this, 'public_link' ), $lost_links ), 0, $limit ),
			'competitor_link_gaps'=> array_slice( $link_gaps, 0, $limit ),
			'unlinked_priority_pages'=> array_slice( $unlinked_pages, 0, $limit ),
			'hypotheses'        => $hypotheses,
			'limitations'       => array_values( array_filter( array(
				'Authority Intelligence reflects only the imported or connected datasets. It is not a complete index of the web.',
				'Provider metrics are retained as imported evidence and are not converted into an Ikon authority score.',
				'A competitor source-domain gap is an outreach research lead, not proof that a link can or should be acquired.',
				'Link quality, editorial context, spam risk and relationship relevance require human review.',
				( $active_total > count( $active_links ) || $competitor_total > count( $competitor_links ) ) ? sprintf( 'Detailed calculations are bounded to the most recent or strongest %d records per relationship; summary totals use the full database.', $analysis_limit ) : '',
			) ) ),
			'methodology'       => 'The report separates observed link records from hypotheses, maps unique referring domains and target pages, and prioritizes recoverable or competitor-shared sources without promising ranking gains.',
		);
		set_transient( self::CACHE_KEY, $result, 15 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function page_summary( $url ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->links_table() ) || ! $url ) {
			return array( 'available' => false );
		}
		$target_hash = hash( 'sha256', $this->url_key( $url ) );
		$table = $this->links_table();
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) links, COUNT(DISTINCT CASE WHEN status='active' THEN source_domain ELSE NULL END) domains, SUM(CASE WHEN status='active' AND link_type='follow' THEN 1 ELSE 0 END) follow_links, SUM(CASE WHEN status='lost' THEN 1 ELSE 0 END) lost_links, AVG(CASE WHEN status='active' AND source_strength>0 THEN source_strength ELSE NULL END) average_strength FROM {$table} WHERE relationship='site_backlink' AND target_hash=%s AND status <> 'archived'",
				$target_hash
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row || ! absint( $row['links'] ?? 0 ) ) {
			return array( 'available' => true, 'links' => 0, 'domains' => 0, 'follow_links' => 0, 'lost_links' => 0, 'average_strength' => null );
		}
		return array(
			'available'        => true,
			'links'            => absint( $row['links'] ),
			'domains'          => absint( $row['domains'] ),
			'follow_links'     => absint( $row['follow_links'] ),
			'lost_links'       => absint( $row['lost_links'] ),
			'average_strength' => null === $row['average_strength'] ? null : round( (float) $row['average_strength'], 1 ),
		);
	}

	public function archive_link( $id ) {
		global $wpdb;
		$result = $wpdb->update( $this->links_table(), array( 'status' => 'archived', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $id ) ), array( '%s', '%s' ), array( '%d' ) );
		delete_transient( self::CACHE_KEY );
		return false !== $result;
	}

	private function referring_domain_summary( array $links ) {
		$domains = array();
		foreach ( $links as $link ) {
			$key = $link['source_domain'];
			if ( ! isset( $domains[ $key ] ) ) {
				$domains[ $key ] = array( 'domain' => $key, 'links' => 0, 'follow_links' => 0, 'target_pages' => array(), 'anchors' => array(), 'max_strength' => 0.0, 'average_strength' => 0.0, '_strength_total' => 0.0, '_strength_count' => 0 );
			}
			$domains[ $key ]['links']++;
			$domains[ $key ]['follow_links'] += 'follow' === $link['link_type'] ? 1 : 0;
			$domains[ $key ]['target_pages'][ $this->url_key( $link['target_url'] ) ] = $link['target_url'];
			if ( trim( $link['anchor_text'] ) ) {
				$domains[ $key ]['anchors'][ $this->normalize_text( $link['anchor_text'] ) ] = $link['anchor_text'];
			}
			$strength = (float) $link['source_strength'];
			if ( $strength > 0 ) {
				$domains[ $key ]['max_strength'] = max( $domains[ $key ]['max_strength'], $strength );
				$domains[ $key ]['_strength_total'] += $strength;
				$domains[ $key ]['_strength_count']++;
			}
		}
		foreach ( $domains as &$domain ) {
			$domain['target_pages'] = array_values( $domain['target_pages'] );
			$domain['anchors'] = array_values( $domain['anchors'] );
			$domain['average_strength'] = $domain['_strength_count'] ? round( $domain['_strength_total'] / $domain['_strength_count'], 1 ) : null;
			unset( $domain['_strength_total'], $domain['_strength_count'] );
		}
		unset( $domain );
		$domains = array_values( $domains );
		usort( $domains, function( $a, $b ) {
			if ( $a['links'] === $b['links'] ) {
				return $b['max_strength'] <=> $a['max_strength'];
			}
			return $b['links'] <=> $a['links'];
		} );
		return $domains;
	}

	private function target_page_summary( array $links ) {
		$targets = array();
		foreach ( $links as $link ) {
			$key = $this->url_key( $link['target_url'] );
			if ( ! isset( $targets[ $key ] ) ) {
				$post_id = url_to_postid( $link['target_url'] );
				$targets[ $key ] = array(
					'url' => $link['target_url'],
					'post_id' => absint( $post_id ),
					'title' => $post_id ? get_the_title( $post_id ) : '',
					'post_status' => $post_id ? get_post_status( $post_id ) : '',
					'links' => 0,
					'domains' => array(),
					'follow_links' => 0,
					'lost_links' => 0,
				);
			}
			$targets[ $key ]['links']++;
			$targets[ $key ]['domains'][ $link['source_domain'] ] = true;
			$targets[ $key ]['follow_links'] += 'follow' === $link['link_type'] ? 1 : 0;
			$targets[ $key ]['lost_links'] += 'lost' === $link['status'] ? 1 : 0;
		}
		foreach ( $targets as &$target ) {
			$target['domains'] = count( $target['domains'] );
		}
		unset( $target );
		$targets = array_values( $targets );
		usort( $targets, function( $a, $b ) { return $b['domains'] <=> $a['domains']; } );
		return $targets;
	}

	private function anchor_summary( array $links ) {
		$categories = array( 'branded' => 0, 'url' => 0, 'generic' => 0, 'descriptive' => 0, 'empty' => 0 );
		$top = array();
		foreach ( $links as $link ) {
			$anchor = trim( (string) $link['anchor_text'] );
			$category = $this->anchor_category( $anchor );
			$categories[ $category ]++;
			$key = $anchor ? $this->normalize_text( $anchor ) : '(empty)';
			if ( ! isset( $top[ $key ] ) ) {
				$top[ $key ] = array( 'anchor' => $anchor ?: '(empty)', 'category' => $category, 'links' => 0, 'domains' => array() );
			}
			$top[ $key ]['links']++;
			$top[ $key ]['domains'][ $link['source_domain'] ] = true;
		}
		foreach ( $top as &$item ) {
			$item['domains'] = count( $item['domains'] );
		}
		unset( $item );
		$top = array_values( $top );
		usort( $top, function( $a, $b ) { return $b['links'] <=> $a['links']; } );
		$total = max( 1, count( $links ) );
		$shares = array();
		foreach ( $categories as $key => $count ) {
			$shares[ $key ] = array( 'links' => $count, 'share' => round( 100 * $count / $total, 1 ) );
		}
		return array( 'total' => count( $links ), 'categories' => $shares, 'top_anchors' => array_slice( $top, 0, 50 ) );
	}

	private function broken_recovery( array $links ) {
		$items = array();
		foreach ( $links as $link ) {
			$status = $this->technical_status_for_url( $link['target_url'] );
			$post_id = url_to_postid( $link['target_url'] );
			$post_status = $post_id ? get_post_status( $post_id ) : '';
			$problem = '';
			$confidence = 'low';
			if ( $status >= 400 ) {
				$problem = 'Target returns HTTP ' . $status;
				$confidence = 'high';
			} elseif ( $status >= 300 ) {
				$problem = 'Target redirects with HTTP ' . $status;
				$confidence = 'high';
			} elseif ( 'lost' === $link['status'] ) {
				$problem = 'Imported dataset marks the backlink as lost';
				$confidence = 'medium';
			} elseif ( $post_id && 'publish' !== $post_status ) {
				$problem = 'Target content is not currently published';
				$confidence = 'high';
			}
			if ( ! $problem ) {
				continue;
			}
			$items[] = array(
				'source_url' => $link['source_url'],
				'source_domain' => $link['source_domain'],
				'target_url' => $link['target_url'],
				'anchor_text' => $link['anchor_text'],
				'problem' => $problem,
				'confidence' => $confidence,
				'recommended_action' => 'Confirm the external link still exists, then restore the intended target or request an editorial update to the most relevant live URL.',
				'source_strength' => (float) $link['source_strength'],
			);
		}
		usort( $items, function( $a, $b ) { return $b['source_strength'] <=> $a['source_strength']; } );
		return $items;
	}

	private function competitor_gaps( array $own_links, array $competitor_links ) {
		$own_sources = array();
		foreach ( $own_links as $link ) {
			$own_sources[ $link['source_domain'] ] = true;
		}
		$gaps = array();
		foreach ( $competitor_links as $link ) {
			$domain = $link['source_domain'];
			if ( isset( $own_sources[ $domain ] ) ) {
				continue;
			}
			if ( ! isset( $gaps[ $domain ] ) ) {
				$gaps[ $domain ] = array( 'source_domain' => $domain, 'competitors' => array(), 'links' => 0, 'example_urls' => array(), 'max_strength' => 0.0, 'confidence' => 'medium' );
			}
			$gaps[ $domain ]['competitors'][ $link['competitor_domain'] ] = true;
			$gaps[ $domain ]['links']++;
			$gaps[ $domain ]['example_urls'][ $link['source_url'] ] = true;
			$gaps[ $domain ]['max_strength'] = max( $gaps[ $domain ]['max_strength'], (float) $link['source_strength'] );
		}
		foreach ( $gaps as &$gap ) {
			$gap['competitors'] = array_keys( $gap['competitors'] );
			$gap['competitor_count'] = count( $gap['competitors'] );
			$gap['example_urls'] = array_slice( array_keys( $gap['example_urls'] ), 0, 5 );
			$gap['priority'] = min( 100, (int) round( 20 + $gap['competitor_count'] * 15 + min( 30, $gap['links'] * 3 ) + min( 20, $gap['max_strength'] * 0.2 ) ) );
			$gap['recommended_action'] = 'Review topical relevance, editorial standards and contact feasibility before considering outreach or a digital-public-relations opportunity.';
		}
		unset( $gap );
		$gaps = array_values( $gaps );
		usort( $gaps, function( $a, $b ) { return $b['priority'] <=> $a['priority']; } );
		return $gaps;
	}

	private function unlinked_priority_pages( array $target_pages ) {
		global $wpdb;
		$linked = array();
		$table = $this->links_table();
		if ( $this->table_exists( $table ) ) {
			$hashes = (array) $wpdb->get_col( "SELECT DISTINCT target_hash FROM {$table} WHERE relationship='site_backlink' AND status='active'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( $hashes as $hash ) {
				$linked[ (string) $hash ] = true;
			}
		}
		$inventory = $this->inventory->scan( false );
		$pages = array();
		foreach ( (array) ( $inventory['items'] ?? array() ) as $item ) {
			if ( 'publish' !== ( $item['status'] ?? '' ) || isset( $linked[ hash( 'sha256', $this->url_key( $item['url'] ?? '' ) ) ] ) ) {
				continue;
			}
			$value = 'page' === ( $item['post_type'] ?? '' ) ? 70 : 45;
			if ( absint( get_option( 'page_on_front' ) ) === absint( $item['id'] ?? 0 ) ) {
				$value = 100;
			}
			$pages[] = array(
				'post_id' => absint( $item['id'] ?? 0 ),
				'title' => sanitize_text_field( $item['title'] ?? '' ),
				'url' => esc_url_raw( $item['url'] ?? '' ),
				'post_type' => sanitize_key( $item['post_type'] ?? '' ),
				'business_value' => $value,
				'evidence' => 'No active backlink record in the imported dataset targets this URL.',
				'recommended_action' => 'Do not build links solely to fill a count. Confirm search demand, page quality and strategic value before planning promotion or outreach.',
			);
		}
		usort( $pages, function( $a, $b ) { return $b['business_value'] <=> $a['business_value']; } );
		return $pages;
	}

	private function authority_hypotheses( array $links, array $anchors, array $unlinked_pages ) {
		$hypotheses = array();
		$total = count( $links );
		if ( $total >= 10 ) {
			$top = $anchors['top_anchors'][0] ?? array();
			if ( $top && 'descriptive' === ( $top['category'] ?? '' ) && (float) $top['links'] / $total >= 0.35 ) {
				$hypotheses[] = array(
					'code' => 'anchor_concentration',
					'confidence' => 'low',
					'message' => 'One descriptive anchor represents a large share of the imported link records.',
					'evidence' => sprintf( '%s appears in %d of %d active link records.', $top['anchor'], $top['links'], $total ),
					'recommended_action' => 'Review source quality and natural editorial context. Do not try to force an artificial anchor distribution.',
				);
			}
		}
		if ( count( $unlinked_pages ) >= 10 && $total > 0 ) {
			$hypotheses[] = array(
				'code' => 'authority_concentration',
				'confidence' => 'medium',
				'message' => 'Imported off-site evidence appears concentrated on a limited group of URLs.',
				'evidence' => sprintf( '%d published URLs have no active backlink record in the current dataset.', count( $unlinked_pages ) ),
				'recommended_action' => 'Prioritize only commercially or topically important pages after confirming their quality and internal-link support.',
			);
		}
		return $hypotheses;
	}

	private function map_csv_row( array $row ) {
		$source = $this->pick( $row, array( 'source_url', 'referring_page_url', 'referring_page', 'source', 'url_from', 'backlink_url', 'page_url' ) );
		$target = $this->pick( $row, array( 'target_url', 'target_page', 'destination_url', 'landing_page', 'url_to', 'target' ) );
		$anchor = $this->pick( $row, array( 'anchor_text', 'anchor', 'link_anchor' ) );
		$rel    = strtolower( $this->pick( $row, array( 'link_type', 'type', 'rel', 'nofollow', 'dofollow' ) ) );
		$status = strtolower( $this->pick( $row, array( 'status', 'link_status', 'lost', 'is_lost' ) ) );
		if ( in_array( $status, array( 'true', 'yes', '1', 'lost' ), true ) ) {
			$status = 'lost';
		} elseif ( ! $status ) {
			$status = 'active';
		}
		$link_type = 'unknown';
		if ( false !== strpos( $rel, 'nofollow' ) || in_array( $rel, array( 'true', 'yes', '1' ), true ) ) {
			$link_type = 'nofollow';
		} elseif ( false !== strpos( $rel, 'sponsored' ) ) {
			$link_type = 'sponsored';
		} elseif ( false !== strpos( $rel, 'ugc' ) ) {
			$link_type = 'ugc';
		} elseif ( false !== strpos( $rel, 'follow' ) || in_array( $rel, array( 'false', 'no', '0', 'dofollow' ), true ) ) {
			$link_type = 'follow';
		}
		$strength = $this->numeric( $this->pick( $row, array( 'source_strength', 'domain_rating', 'dr', 'authority_score', 'domain_authority', 'da', 'trust_flow', 'tf' ) ) );
		$traffic  = $this->numeric( $this->pick( $row, array( 'source_traffic', 'domain_traffic', 'organic_traffic', 'traffic' ) ) );
		return array(
			'source_url' => $source,
			'target_url' => $target,
			'anchor_text' => $anchor,
			'link_type' => $link_type,
			'status' => $status,
			'first_seen' => $this->pick( $row, array( 'first_seen', 'first_seen_date', 'first_detected' ) ),
			'last_seen' => $this->pick( $row, array( 'last_seen', 'last_seen_date', 'last_check' ) ),
			'observed_at' => $this->pick( $row, array( 'observed_at', 'export_date', 'date' ) ),
			'source_strength' => $strength,
			'source_traffic' => $traffic,
			'source_title' => $this->pick( $row, array( 'source_title', 'referring_page_title', 'title' ) ),
			'notes' => $this->pick( $row, array( 'notes', 'comment' ) ),
			'raw_metrics' => array_filter( array( 'imported_strength' => $strength, 'imported_traffic' => $traffic ), function( $value ) { return $value > 0; } ),
		);
	}

	private function public_link( $link ) {
		return array(
			'id' => absint( $link['id'] ?? 0 ),
			'source_url' => esc_url_raw( $link['source_url'] ?? '' ),
			'source_domain' => sanitize_text_field( $link['source_domain'] ?? '' ),
			'target_url' => esc_url_raw( $link['target_url'] ?? '' ),
			'anchor_text' => sanitize_text_field( $link['anchor_text'] ?? '' ),
			'link_type' => sanitize_key( $link['link_type'] ?? '' ),
			'status' => sanitize_key( $link['status'] ?? '' ),
			'source_strength' => (float) ( $link['source_strength'] ?? 0 ),
			'last_seen' => sanitize_text_field( $link['last_seen'] ?? '' ),
		);
	}

	private function anchor_category( $anchor ) {
		$anchor = trim( strtolower( (string) $anchor ) );
		if ( '' === $anchor ) {
			return 'empty';
		}
		if ( preg_match( '#^(?:https?://|www\.)#i', $anchor ) || false !== strpos( $anchor, $this->site_domain() ) ) {
			return 'url';
		}
		$generic = array( 'click here', 'website', 'visit website', 'learn more', 'read more', 'here', 'source', 'this site', 'link' );
		if ( in_array( $anchor, $generic, true ) ) {
			return 'generic';
		}
		$site_name = strtolower( wp_strip_all_tags( get_bloginfo( 'name' ) ) );
		if ( ( $site_name && false !== strpos( $anchor, $site_name ) ) || false !== strpos( $anchor, preg_replace( '/\.[a-z]{2,}$/i', '', $this->site_domain() ) ) ) {
			return 'branded';
		}
		return 'descriptive';
	}

	private function technical_status_for_url( $url ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_technical_urls';
		if ( ! $this->table_exists( $table ) ) {
			return 0;
		}
		$parts = wp_parse_url( esc_url_raw( trim( (string) $url ) ) );
		if ( ! is_array( $parts ) ) {
			return 0;
		}
		$scheme = strtolower( $parts['scheme'] ?? 'https' );
		$host   = strtolower( $parts['host'] ?? '' );
		$path   = isset( $parts['path'] ) ? '/' . ltrim( $parts['path'], '/' ) : '/';
		$query  = ! empty( $parts['query'] ) ? '?' . $parts['query'] : '';
		$normalized = esc_url_raw( $scheme . '://' . $host . ( isset( $parts['port'] ) ? ':' . absint( $parts['port'] ) : '' ) . $path . $query );
		$hash = hash( 'sha256', untrailingslashit( $normalized ?: (string) $url ) );
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT status_code FROM {$table} WHERE url_hash=%s LIMIT 1", $hash ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private function sanitize_provider( $provider ) {
		$provider = sanitize_key( $provider );
		$allowed = array( 'generic', 'ahrefs', 'semrush', 'majestic', 'search_console', 'manual', 'connected_research', 'licensed_provider', 'import' );
		return in_array( $provider, $allowed, true ) ? $provider : 'generic';
	}

	private function sanitize_relationship( $relationship ) {
		return 'competitor_backlink' === sanitize_key( $relationship ) ? 'competitor_backlink' : 'site_backlink';
	}

	private function sanitize_link_type( $type ) {
		$type = sanitize_key( $type );
		if ( false !== strpos( $type, 'nofollow' ) ) {
			return 'nofollow';
		}
		$allowed = array( 'follow', 'nofollow', 'sponsored', 'ugc', 'unknown' );
		return in_array( $type, $allowed, true ) ? $type : 'unknown';
	}

	private function sanitize_status( $status ) {
		$status = sanitize_key( $status );
		$allowed = array( 'active', 'lost', 'new', 'archived' );
		return in_array( $status, $allowed, true ) ? $status : 'active';
	}

	private function sanitize_date( $date ) {
		$date = trim( sanitize_text_field( $date ) );
		if ( ! $date ) {
			return '';
		}
		$timestamp = strtotime( $date );
		return $timestamp ? gmdate( 'Y-m-d', $timestamp ) : '';
	}

	private function sanitize_metrics( array $metrics ) {
		$clean = array();
		foreach ( array_slice( $metrics, 0, 30, true ) as $key => $value ) {
			$key = sanitize_key( $key );
			if ( is_scalar( $value ) && $key ) {
				$clean[ $key ] = is_numeric( $value ) ? (float) $value : sanitize_text_field( (string) $value );
			}
		}
		return $clean;
	}

	private function valid_http_url( $url ) {
		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		return in_array( $scheme, array( 'http', 'https' ), true ) && (bool) wp_parse_url( $url, PHP_URL_HOST );
	}

	private function site_domain() {
		return $this->normalize_domain( wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	}

	private function normalize_domain( $domain ) {
		$domain = strtolower( trim( (string) $domain ) );
		$domain = preg_replace( '#^https?://#', '', $domain );
		$domain = preg_replace( '#/.*$#', '', $domain );
		$domain = preg_replace( '/^www\./', '', $domain );
		return sanitize_text_field( $domain );
	}

	private function url_key( $url ) {
		$host = $this->normalize_domain( wp_parse_url( $url, PHP_URL_HOST ) );
		$path = rawurldecode( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );
		$path = untrailingslashit( '/' . ltrim( $path, '/' ) );
		return $host . ( $path ?: '/' ) . ( $query ? '?' . $query : '' );
	}

	private function normalize_text( $text ) {
		$text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $text ) ) );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( $text );
	}

	private function normalize_header( $header ) {
		$header = strtolower( trim( (string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF" ) );
		$header = preg_replace( '/[^a-z0-9]+/', '_', $header );
		return trim( $header, '_' );
	}

	private function detect_delimiter( $line ) {
		$counts = array( ',' => substr_count( $line, ',' ), ';' => substr_count( $line, ';' ), "\t" => substr_count( $line, "\t" ) );
		arsort( $counts );
		$delimiter = key( $counts );
		return $counts[ $delimiter ] > 0 ? $delimiter : ',';
	}

	private function pick( array $row, array $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $row[ $key ] ) && '' !== trim( (string) $row[ $key ] ) ) {
				return trim( (string) $row[ $key ] );
			}
		}
		return '';
	}

	private function numeric( $value ) {
		$value = preg_replace( '/[^0-9.\-]/', '', (string) $value );
		return is_numeric( $value ) ? (float) $value : 0;
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}
}
