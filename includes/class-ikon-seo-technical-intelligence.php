<?php

defined( 'ABSPATH' ) || exit;

/**
 * Technical URL discovery, internal-link graph and performance evidence.
 * Read-only: it never edits content, redirects, canonicals or sitemaps.
 */
final class Ikon_SEO_Technical_Intelligence {
	const CRON_HOOK = 'ikon_seo_technical_intelligence_refresh';
	const CACHE_KEY = 'ikon_seo_technical_intelligence_report_v1';

	private $crawler;
	private $crypto;
	private $logger;

	public function __construct( Ikon_SEO_Crawler $crawler, Ikon_SEO_Crypto $crypto, Ikon_SEO_Logger $logger ) {
		$this->crawler = $crawler;
		$this->crypto  = $crypto;
		$this->logger  = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_refresh' ) );
	}

	public function scheduled_refresh() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['technical_intelligence_enabled'] ) ) {
			return;
		}
		$this->refresh_discovery();
		$this->check_urls( max( 5, min( 50, absint( $settings['technical_check_batch_size'] ?? 20 ) ) ) );
	}

	public function save_api_key( $key ) {
		$key = trim( (string) $key );
		$settings = get_option( Ikon_SEO_Plugin::OPTION_KEY, array() );
		if ( '' === $key ) {
			$settings['pagespeed_api_key'] = '';
			$settings['pagespeed_last_error'] = '';
			update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
			return true;
		}
		$encrypted = $this->crypto->encrypt( $key );
		if ( is_wp_error( $encrypted ) ) {
			return $encrypted;
		}
		$settings['pagespeed_api_key'] = $encrypted;
		$settings['pagespeed_last_error'] = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		return true;
	}

	public function status() {
		global $wpdb;
		$urls = $this->urls_table();
		$links = $this->links_table();
		$psi = $this->pagespeed_table();
		if ( ! $this->table_exists( $urls ) || ! $this->table_exists( $links ) || ! $this->table_exists( $psi ) ) {
			return array( 'ready' => false );
		}
		$home_hash = $this->url_hash( home_url( '/' ) );
		$row = $wpdb->get_row(
			"SELECT COUNT(*) total_urls,
			SUM(CASE WHEN source_flags LIKE '%sitemap%' THEN 1 ELSE 0 END) sitemap_urls,
			SUM(CASE WHEN status_code>=400 OR (status_code=0 AND checked_at IS NOT NULL) THEN 1 ELSE 0 END) failed_urls,
			SUM(CASE WHEN redirect_target<>'' THEN 1 ELSE 0 END) redirects,
			SUM(CASE WHEN post_id>0 AND crawl_depth<0 THEN 1 ELSE 0 END) orphans,
			MAX(checked_at) last_check
			FROM {$urls}",
			ARRAY_A
		);
		$settings = Ikon_SEO_Plugin::settings();
		return array(
			'ready' => true,
			'total_urls' => absint( $row['total_urls'] ?? 0 ),
			'sitemap_urls' => absint( $row['sitemap_urls'] ?? 0 ),
			'failed_urls' => absint( $row['failed_urls'] ?? 0 ),
			'redirects' => absint( $row['redirects'] ?? 0 ),
			'orphans' => absint( $row['orphans'] ?? 0 ),
			'internal_links' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$links}" ),
			'pagespeed_urls' => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT url_hash) FROM {$psi}" ),
			'homepage_known' => (bool) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$urls} WHERE url_hash=%s", $home_hash ) ),
			'last_check' => sanitize_text_field( $row['last_check'] ?? '' ),
			'last_discovery' => sanitize_text_field( get_option( 'ikon_seo_technical_last_discovery', '' ) ),
			'pagespeed_key_configured' => ! empty( $settings['pagespeed_api_key'] ),
			'pagespeed_last_error' => sanitize_text_field( $settings['pagespeed_last_error'] ?? '' ),
		);
	}

	public function refresh_discovery() {
		global $wpdb;
		$urls_table = $this->urls_table();
		$links_table = $this->links_table();
		if ( ! $this->table_exists( $urls_table ) || ! $this->table_exists( $links_table ) ) {
			return new WP_Error( 'ikon_seo_technical_tables', __( 'The Technical Intelligence tables are unavailable. Reactivate Ikon SEO to repair its database.', 'ikon-seo' ) );
		}
		$settings = Ikon_SEO_Plugin::settings();
		$max_urls = max( 100, min( 20000, absint( $settings['technical_max_urls'] ?? 5000 ) ) );
		$sources = array();
		$this->discover_wordpress( $sources, $max_urls );
		$this->discover_sitemaps( $sources, $max_urls );
		$this->discover_crawler( $sources, $max_urls );
		$this->discover_search_console( $sources, $max_urls );
		$this->discover_analytics( $sources, $max_urls );

		$now = current_time( 'mysql', true );
		foreach ( array_slice( $sources, 0, $max_urls, true ) as $url => $data ) {
			$normalized = $this->normalize_url( $url );
			if ( ! $normalized ) {
				continue;
			}
			$row = array(
				'url_hash' => $this->url_hash( $normalized ),
				'url' => $normalized,
				'post_id' => absint( $data['post_id'] ?? 0 ),
				'post_type' => sanitize_key( $data['post_type'] ?? '' ),
				'source_flags' => implode( ',', array_values( array_unique( (array) ( $data['sources'] ?? array() ) ) ) ),
				'status_code' => absint( $data['status_code'] ?? 0 ),
				'canonical_url' => esc_url_raw( $data['canonical_url'] ?? '' ),
				'response_ms' => absint( $data['response_ms'] ?? 0 ),
				'checked_at' => sanitize_text_field( $data['checked_at'] ?? '' ) ?: null,
				'last_seen' => $now,
				'updated_at' => $now,
			);
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$urls_table} WHERE url_hash=%s", $row['url_hash'] ), ARRAY_A );
			if ( $existing ) {
				// Discovery should enrich the record without erasing a prior HTTP probe.
				if ( empty( $row['status_code'] ) && ! empty( $existing['status_code'] ) ) {
					$row['status_code'] = absint( $existing['status_code'] );
				}
				if ( empty( $row['canonical_url'] ) && ! empty( $existing['canonical_url'] ) ) {
					$row['canonical_url'] = esc_url_raw( $existing['canonical_url'] );
				}
				if ( empty( $row['response_ms'] ) && ! empty( $existing['response_ms'] ) ) {
					$row['response_ms'] = absint( $existing['response_ms'] );
				}
				if ( empty( $row['checked_at'] ) && ! empty( $existing['checked_at'] ) ) {
					$row['checked_at'] = sanitize_text_field( $existing['checked_at'] );
				}
				$row = array_merge( $existing, $row );
			}
			$wpdb->replace( $urls_table, $row );
		}
		$this->cleanup_stale_urls();
		$this->rebuild_link_graph( $max_urls );
		$this->calculate_graph_metrics();
		update_option( 'ikon_seo_technical_last_discovery', $now, false );
		delete_transient( self::CACHE_KEY );
		$this->logger->log( 'technical_discovery', 'success', sprintf( 'Discovered %d same-site URLs and rebuilt the internal-link graph.', count( $sources ) ) );
		return array( 'discovered' => count( $sources ), 'status' => $this->status() );
	}

	private function discover_wordpress( array &$sources, $max_urls ) {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		unset( $post_types['attachment'] );
		$query = new WP_Query( array(
			'post_type' => array_values( $post_types ), 'post_status' => 'publish', 'posts_per_page' => $max_urls,
			'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true,
		) );
		foreach ( (array) $query->posts as $post_id ) {
			$url = get_permalink( $post_id );
			if ( $url ) {
				$this->add_source( $sources, $url, 'wordpress', absint( $post_id ), get_post_type( $post_id ) );
			}
		}
		$front_id = absint( get_option( 'page_on_front' ) );
		$this->add_source( $sources, home_url( '/' ), 'wordpress', $front_id, $front_id ? get_post_type( $front_id ) : 'front' );
	}

	private function discover_sitemaps( array &$sources, $max_urls ) {
		$candidates = array( home_url( '/sitemap_index.xml' ), home_url( '/wp-sitemap.xml' ) );
		$robots = wp_safe_remote_get( home_url( '/robots.txt' ), array( 'timeout' => 8, 'redirection' => 2, 'limit_response_size' => 256 * KB_IN_BYTES ) );
		if ( ! is_wp_error( $robots ) && preg_match_all( '~^\s*Sitemap:\s*(https?://\S+)~im', (string) wp_remote_retrieve_body( $robots ), $matches ) ) {
			$candidates = array_merge( $candidates, $matches[1] );
		}
		$candidates = array_values( array_unique( array_map( 'esc_url_raw', $candidates ) ) );
		$seen = array();
		foreach ( $candidates as $candidate ) {
			$this->parse_sitemap( $candidate, $sources, $seen, 0, $max_urls );
			if ( count( $sources ) >= $max_urls ) {
				break;
			}
		}
	}

	private function parse_sitemap( $url, array &$sources, array &$seen, $depth, $max_urls ) {
		$url = esc_url_raw( $url );
		if ( ! $url || $depth > 2 || isset( $seen[ $url ] ) || ! $this->same_site( $url ) || count( $sources ) >= $max_urls ) {
			return;
		}
		$seen[ $url ] = true;
		$response = wp_safe_remote_get( $url, array( 'timeout' => 12, 'redirection' => 3, 'limit_response_size' => 5 * MB_IN_BYTES ) );
		if ( is_wp_error( $response ) || 200 !== absint( wp_remote_retrieve_response_code( $response ) ) ) {
			return;
		}
		$body = (string) wp_remote_retrieve_body( $response );
		if ( strlen( $body ) > 2 && "\x1f\x8b" === substr( $body, 0, 2 ) && function_exists( 'gzdecode' ) ) {
			$decoded = gzdecode( $body );
			if ( is_string( $decoded ) ) { $body = $decoded; }
		}
		if ( ! preg_match_all( '~<loc>\s*(.*?)\s*</loc>~is', $body, $matches ) ) {
			return;
		}
		$is_index = false !== stripos( $body, '<sitemapindex' );
		foreach ( $matches[1] as $location ) {
			$location = html_entity_decode( trim( wp_strip_all_tags( $location ) ), ENT_QUOTES, 'UTF-8' );
			if ( $is_index && preg_match( '~\.xml(?:\.gz)?(?:\?|$)~i', $location ) ) {
				$this->parse_sitemap( $location, $sources, $seen, $depth + 1, $max_urls );
			} else {
				$this->add_source( $sources, $location, 'sitemap' );
			}
			if ( count( $sources ) >= $max_urls ) {
				break;
			}
		}
	}

	private function discover_crawler( array &$sources, $max_urls ) {
		foreach ( $this->crawler->list_records( min( 1000, $max_urls ) ) as $record ) {
			$this->add_source( $sources, $record['url'] ?? '', 'crawler', absint( $record['post_id'] ?? 0 ), get_post_type( absint( $record['post_id'] ?? 0 ) ) );
			$record_url = $this->normalize_url( $record['url'] ?? '' );
			if ( $record_url && isset( $sources[ $record_url ] ) ) {
				$sources[ $record_url ]['status_code'] = absint( $record['status_code'] ?? 0 );
				$sources[ $record_url ]['canonical_url'] = esc_url_raw( $record['canonical'] ?? '' );
				$sources[ $record_url ]['response_ms'] = absint( $record['response_ms'] ?? 0 );
				$sources[ $record_url ]['checked_at'] = sanitize_text_field( $record['crawled_at'] ?? '' );
			}
			foreach ( (array) ( $record['evidence']['internal_urls'] ?? array() ) as $url ) {
				$this->add_source( $sources, $url, 'internal-link' );
			}
		}
	}

	private function discover_search_console( array &$sources, $max_urls ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_search_rows';
		if ( ! $this->table_exists( $table ) ) {
			return;
		}
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT page_url FROM {$table} ORDER BY impressions DESC LIMIT %d", min( $max_urls, 10000 ) ) );
		foreach ( (array) $rows as $url ) {
			$this->add_source( $sources, $url, 'search-console' );
		}
	}

	private function discover_analytics( array &$sources, $max_urls ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_analytics_pages';
		if ( ! $this->table_exists( $table ) ) {
			return;
		}
		$paths = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT page_path FROM {$table} ORDER BY sessions DESC LIMIT %d", min( $max_urls, 10000 ) ) );
		foreach ( (array) $paths as $path ) {
			$url = preg_match( '~^https?://~i', $path ) ? $path : home_url( '/' . ltrim( $path, '/' ) );
			$this->add_source( $sources, $url, 'analytics' );
		}
	}

	private function add_source( array &$sources, $url, $source, $post_id = 0, $post_type = '' ) {
		$url = $this->normalize_url( $url );
		if ( ! $url ) {
			return;
		}
		if ( ! isset( $sources[ $url ] ) ) {
			$sources[ $url ] = array( 'sources' => array(), 'post_id' => 0, 'post_type' => '' );
		}
		$sources[ $url ]['sources'][] = sanitize_key( $source );
		if ( $post_id ) {
			$sources[ $url ]['post_id'] = absint( $post_id );
			$sources[ $url ]['post_type'] = sanitize_key( $post_type );
		}
	}

	private function cleanup_stale_urls() {
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( 90 * DAY_IN_SECONDS ) );
		$table = $this->urls_table();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE last_seen<>'' AND last_seen<%s", $cutoff ) );
	}

	private function rebuild_link_graph( $max_urls = 5000 ) {
		global $wpdb;
		$table = $this->links_table();
		$now = current_time( 'mysql', true );
		foreach ( $this->crawler->list_records( max( 1, min( 20000, absint( $max_urls ) ) ) ) as $record ) {
			$source = $this->normalize_url( $record['url'] ?? '' );
			if ( ! $source ) {
				continue;
			}
			$links = (array) ( $record['evidence']['links'] ?? array() );
			if ( ! $links ) {
				foreach ( (array) ( $record['evidence']['internal_urls'] ?? array() ) as $url ) {
					$links[] = array( 'url' => $url, 'anchor' => '', 'rel' => '', 'placement' => 'unknown', 'follow' => true );
				}
			}
			foreach ( $links as $link ) {
				$destination = $this->normalize_url( $link['url'] ?? '' );
				if ( ! $destination ) {
					continue;
				}
				$row_hash = hash( 'sha256', $source . '|' . $destination . '|' . ( $link['anchor'] ?? '' ) . '|' . ( $link['placement'] ?? '' ) );
				$source_hash      = $this->url_hash( $source );
				$destination_hash = $this->url_hash( $destination );
				$anchor           = sanitize_text_field( $link['anchor'] ?? '' );
				$rel              = sanitize_text_field( $link['rel'] ?? '' );
				$placement        = sanitize_key( $link['placement'] ?? 'unknown' );
				$follow           = empty( $link['follow'] ) ? 0 : 1;
				$wpdb->query( $wpdb->prepare(
					"INSERT INTO {$table} (link_hash,source_hash,destination_hash,source_url,destination_url,anchor_text,rel,placement,follow,first_seen,last_seen)
					VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s)
					ON DUPLICATE KEY UPDATE source_hash=VALUES(source_hash),destination_hash=VALUES(destination_hash),source_url=VALUES(source_url),destination_url=VALUES(destination_url),anchor_text=VALUES(anchor_text),rel=VALUES(rel),placement=VALUES(placement),follow=VALUES(follow),last_seen=VALUES(last_seen)",
					$row_hash, $source_hash, $destination_hash, $source, $destination, $anchor, $rel, $placement, $follow, $now, $now
				) );
			}
		}
		// Remove links that were not observed in this complete graph rebuild.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE last_seen<%s", $now ) );
	}

	private function calculate_graph_metrics() {
		global $wpdb;
		$urls = $this->urls_table();
		$links = $this->links_table();
		$wpdb->query( "UPDATE {$urls} SET inbound_links=0,outbound_links=0,crawl_depth=-1" );
		$wpdb->query( "UPDATE {$urls} u JOIN (SELECT destination_hash,COUNT(*) total FROM {$links} WHERE follow=1 GROUP BY destination_hash) x ON x.destination_hash=u.url_hash SET u.inbound_links=x.total" );
		$wpdb->query( "UPDATE {$urls} u JOIN (SELECT source_hash,COUNT(*) total FROM {$links} GROUP BY source_hash) x ON x.source_hash=u.url_hash SET u.outbound_links=x.total" );
		$start = $this->url_hash( home_url( '/' ) );
		$depth = array( $start => 0 );
		$frontier = array( $start );
		for ( $level = 0; $level < 12 && $frontier; $level++ ) {
			$placeholders = implode( ',', array_fill( 0, count( $frontier ), '%s' ) );
			$sql = $wpdb->prepare( "SELECT DISTINCT destination_hash FROM {$links} WHERE follow=1 AND source_hash IN ({$placeholders})", $frontier );
			$next = array();
			foreach ( (array) $wpdb->get_col( $sql ) as $hash ) {
				if ( ! isset( $depth[ $hash ] ) ) {
					$depth[ $hash ] = $level + 1;
					$next[] = $hash;
				}
			}
			$frontier = $next;
		}
		foreach ( $depth as $hash => $value ) {
			$wpdb->update( $urls, array( 'crawl_depth' => absint( $value ) ), array( 'url_hash' => $hash ), array( '%d' ), array( '%s' ) );
		}
	}

	public function check_urls( $limit = 20 ) {
		global $wpdb;
		$table = $this->urls_table();
		$limit = max( 1, min( 100, absint( $limit ) ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY CASE WHEN checked_at IS NULL OR checked_at='' THEN 0 ELSE 1 END, checked_at ASC LIMIT %d", $limit ), ARRAY_A );
		$checked = 0;
		foreach ( (array) $rows as $row ) {
			$this->probe_url( $row );
			$checked++;
		}
		delete_transient( self::CACHE_KEY );
		return array( 'checked' => $checked, 'status' => $this->status() );
	}

	private function probe_url( array $row ) {
		global $wpdb;
		$url = $row['url'];
		$started = microtime( true );
		$response = wp_safe_remote_head( $url, array( 'timeout' => 12, 'redirection' => 0, 'headers' => array( 'User-Agent' => 'IkonSEO/' . IKON_SEO_VERSION ) ) );
		if ( is_wp_error( $response ) || in_array( absint( wp_remote_retrieve_response_code( $response ) ), array( 405, 501 ), true ) ) {
			$response = wp_safe_remote_get( $url, array( 'timeout' => 12, 'redirection' => 0, 'limit_response_size' => 64 * KB_IN_BYTES, 'headers' => array( 'User-Agent' => 'IkonSEO/' . IKON_SEO_VERSION ) ) );
		}
		$duration = (int) round( ( microtime( true ) - $started ) * 1000 );
		$status = is_wp_error( $response ) ? 0 : absint( wp_remote_retrieve_response_code( $response ) );
		$location = is_wp_error( $response ) ? '' : esc_url_raw( wp_remote_retrieve_header( $response, 'location' ) );
		if ( $location && 0 === strpos( $location, '/' ) ) {
			$location = home_url( $location );
		}
		$error = is_wp_error( $response ) ? sanitize_text_field( $response->get_error_message() ) : '';
		$wpdb->update( $this->urls_table(), array(
			'status_code' => $status,
			'redirect_target' => $location,
			'response_ms' => $duration,
			'last_error' => $error,
			'checked_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		), array( 'url_hash' => $row['url_hash'] ) );
	}

	public function run_pagespeed( $limit = 3, $strategy = 'mobile' ) {
		global $wpdb;
		$strategy = in_array( $strategy, array( 'mobile', 'desktop' ), true ) ? $strategy : 'mobile';
		$limit = max( 1, min( 10, absint( $limit ) ) );
		$urls_table = $this->urls_table();
		$pagespeed_table = $this->pagespeed_table();
		$urls = $wpdb->get_col( $wpdb->prepare(
			"SELECT u.url FROM {$urls_table} u LEFT JOIN {$pagespeed_table} p ON p.url_hash=u.url_hash AND p.strategy=%s WHERE u.post_id>0 AND u.status_code IN (0,200) ORDER BY CASE WHEN p.url_hash IS NULL THEN 0 ELSE 1 END, p.fetched_at ASC, u.inbound_links DESC, u.post_id ASC LIMIT %d",
			$strategy,
			$limit
		) );
		$results = array();
		foreach ( (array) $urls as $url ) {
			$results[] = $this->pagespeed_url( $url, $strategy );
		}
		delete_transient( self::CACHE_KEY );
		return array( 'processed' => count( $results ), 'results' => $results );
	}

	private function pagespeed_url( $url, $strategy ) {
		$settings = Ikon_SEO_Plugin::settings();
		$key = '';
		if ( ! empty( $settings['pagespeed_api_key'] ) ) {
			$key = $this->crypto->decrypt( $settings['pagespeed_api_key'] );
			if ( is_wp_error( $key ) ) {
				$key = '';
			}
		}
		$query = array( 'url' => $url, 'strategy' => $strategy );
		if ( $key ) {
			$query['key'] = $key;
		}
		$endpoint = add_query_arg( $query, 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed' );
		$endpoint .= '&category=PERFORMANCE&category=SEO&category=ACCESSIBILITY&category=BEST_PRACTICES';
		$response = wp_safe_remote_get( $endpoint, array( 'timeout' => 60, 'redirection' => 2, 'limit_response_size' => 12 * MB_IN_BYTES ) );
		if ( is_wp_error( $response ) ) {
			$this->remember_pagespeed_error( $response->get_error_message(), $key );
			return array( 'url' => $url, 'error' => $response->get_error_message() );
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( absint( wp_remote_retrieve_response_code( $response ) ) >= 400 || ! is_array( $body ) ) {
			$message = sanitize_text_field( $body['error']['message'] ?? 'PageSpeed request failed.' );
			$this->remember_pagespeed_error( $message, $key );
			return array( 'url' => $url, 'error' => $message );
		}
		$categories = $body['lighthouseResult']['categories'] ?? array();
		$audits = $body['lighthouseResult']['audits'] ?? array();
		$field = $this->query_crux( $url, $strategy, $key );
		$record = array(
			'url_hash' => $this->url_hash( $url ), 'url' => $url, 'strategy' => $strategy,
			'performance_score' => $this->score( $categories['performance']['score'] ?? null ),
			'seo_score' => $this->score( $categories['seo']['score'] ?? null ),
			'accessibility_score' => $this->score( $categories['accessibility']['score'] ?? null ),
			'best_practices_score' => $this->score( $categories['best-practices']['score'] ?? null ),
			'lcp_ms' => $this->audit_numeric( $audits, 'largest-contentful-paint' ),
			'inp_ms' => $this->audit_numeric( $audits, 'interaction-to-next-paint' ),
			'cls' => $this->audit_numeric( $audits, 'cumulative-layout-shift' ),
			'tbt_ms' => $this->audit_numeric( $audits, 'total-blocking-time' ),
			'fcp_ms' => $this->audit_numeric( $audits, 'first-contentful-paint' ),
			'field_lcp_ms' => floatval( $field['lcp_ms'] ?? 0 ),
			'field_inp_ms' => floatval( $field['inp_ms'] ?? 0 ),
			'field_cls' => floatval( $field['cls'] ?? 0 ),
			'field_ttfb_ms' => floatval( $field['ttfb_ms'] ?? 0 ),
			'field_data_available' => empty( $field['available'] ) ? 0 : 1,
			'opportunities_json' => wp_json_encode( $this->top_opportunities( $audits ) ),
			'fetched_at' => current_time( 'mysql', true ),
		);
		global $wpdb;
		$wpdb->replace( $this->pagespeed_table(), $record );
		$this->remember_pagespeed_error( '' );
		return $this->normalize_pagespeed( $record );
	}

	private function query_crux( $url, $strategy, $key ) {
		if ( ! $key ) {
			return array( 'available' => false );
		}
		$endpoint = add_query_arg( array( 'key' => $key ), 'https://chromeuxreport.googleapis.com/v1/records:queryRecord' );
		$body = array( 'url' => $url, 'formFactor' => 'desktop' === $strategy ? 'DESKTOP' : 'PHONE', 'metrics' => array( 'largest_contentful_paint', 'interaction_to_next_paint', 'cumulative_layout_shift', 'experimental_time_to_first_byte' ) );
		$response = wp_safe_remote_post( $endpoint, array( 'timeout' => 30, 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $body ), 'limit_response_size' => 2 * MB_IN_BYTES ) );
		if ( is_wp_error( $response ) || 200 !== absint( wp_remote_retrieve_response_code( $response ) ) ) {
			return array( 'available' => false );
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$metrics = $data['record']['metrics'] ?? array();
		return array(
			'available' => ! empty( $metrics ),
			'lcp_ms' => floatval( $metrics['largest_contentful_paint']['percentiles']['p75'] ?? 0 ),
			'inp_ms' => floatval( $metrics['interaction_to_next_paint']['percentiles']['p75'] ?? 0 ),
			'cls' => floatval( $metrics['cumulative_layout_shift']['percentiles']['p75'] ?? 0 ),
			'ttfb_ms' => floatval( $metrics['experimental_time_to_first_byte']['percentiles']['p75'] ?? 0 ),
		);
	}

	public function report( $refresh = false, $limit = 100 ) {
		if ( ! $this->table_exists( $this->urls_table() ) || ! $this->table_exists( $this->links_table() ) || ! $this->table_exists( $this->pagespeed_table() ) ) {
			return new WP_Error( 'ikon_seo_technical_tables', __( 'The Technical Intelligence tables are unavailable. Reactivate Ikon SEO to repair its database.', 'ikon-seo' ) );
		}
		if ( $refresh ) {
			$result = $this->refresh_discovery();
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		if ( ! $refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		global $wpdb;
		$limit = max( 10, min( 500, absint( $limit ) ) );
		$urls = $this->urls_table();
		$links = $this->links_table();
		$psi = $this->pagespeed_table();
		$redirect_rows = (array) $wpdb->get_results( "SELECT url,status_code,redirect_target,inbound_links,source_flags FROM {$urls} WHERE redirect_target<>'' ORDER BY inbound_links DESC", ARRAY_A );
		$report = array(
			'generated_at' => gmdate( 'c' ),
			'status' => $this->status(),
			'orphan_pages' => $wpdb->get_results( $wpdb->prepare( "SELECT url,post_id,post_type,source_flags,crawl_depth,inbound_links FROM {$urls} WHERE post_id>0 AND crawl_depth<0 ORDER BY post_id ASC LIMIT %d", $limit ), ARRAY_A ),
			'deep_pages' => $wpdb->get_results( $wpdb->prepare( "SELECT url,post_id,post_type,crawl_depth,inbound_links FROM {$urls} WHERE crawl_depth>3 ORDER BY crawl_depth DESC,inbound_links ASC LIMIT %d", $limit ), ARRAY_A ),
			'sitemap_gaps' => $wpdb->get_results( $wpdb->prepare( "SELECT url,post_id,post_type,source_flags,status_code FROM {$urls} WHERE post_id>0 AND source_flags NOT LIKE '%%sitemap%%' ORDER BY inbound_links DESC LIMIT %d", $limit ), ARRAY_A ),
			'sitemap_only' => $wpdb->get_results( $wpdb->prepare( "SELECT url,status_code,last_error FROM {$urls} WHERE source_flags LIKE '%%sitemap%%' AND source_flags NOT LIKE '%%wordpress%%' AND source_flags NOT LIKE '%%internal-link%%' ORDER BY status_code DESC LIMIT %d", $limit ), ARRAY_A ),
			'broken_urls' => $wpdb->get_results( $wpdb->prepare( "SELECT url,status_code,last_error,inbound_links,source_flags FROM {$urls} WHERE status_code>=400 OR (status_code=0 AND checked_at<>'') ORDER BY inbound_links DESC LIMIT %d", $limit ), ARRAY_A ),
			'redirects' => array_slice( $redirect_rows, 0, $limit ),
			'redirect_chains' => array_slice( $this->redirect_chains( $redirect_rows ), 0, $limit ),
			'broken_internal_links' => $wpdb->get_results( $wpdb->prepare( "SELECT l.source_url,l.destination_url,l.anchor_text,l.placement,u.status_code,u.redirect_target FROM {$links} l LEFT JOIN {$urls} u ON u.url_hash=l.destination_hash WHERE u.status_code>=300 OR (u.status_code=0 AND u.checked_at IS NOT NULL) ORDER BY u.status_code DESC LIMIT %d", $limit ), ARRAY_A ),
			'weak_anchors' => $wpdb->get_results( $wpdb->prepare( "SELECT source_url,destination_url,anchor_text,placement,follow FROM {$links} WHERE TRIM(anchor_text)='' OR LOWER(TRIM(anchor_text)) IN ('click here','read more','learn more','here','more') ORDER BY source_url LIMIT %d", $limit ), ARRAY_A ),
			'nofollow_internal' => $wpdb->get_results( $wpdb->prepare( "SELECT source_url,destination_url,anchor_text,placement FROM {$links} WHERE follow=0 ORDER BY source_url LIMIT %d", $limit ), ARRAY_A ),
			'canonical_clusters' => $wpdb->get_results( $wpdb->prepare( "SELECT canonical_url,COUNT(*) total,GROUP_CONCAT(url SEPARATOR '\n') urls FROM {$urls} WHERE canonical_url<>'' GROUP BY canonical_url HAVING total>1 ORDER BY total DESC LIMIT %d", $limit ), ARRAY_A ),
			'pagespeed' => array_map( array( $this, 'normalize_pagespeed' ), (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$psi} ORDER BY performance_score ASC,fetched_at DESC LIMIT %d", $limit ), ARRAY_A ) ),
			'limitations' => array(
				'JavaScript-rendered links may be absent because the same-site crawler currently analyses server-returned HTML.',
				'Field Core Web Vitals are shown only when the CrUX API has enough real-user data for the URL.',
				'An orphan warning means the page was not reachable from the stored internal-link graph; navigation generated outside crawled HTML may change that conclusion.',
			),
		);
		set_transient( self::CACHE_KEY, $report, 30 * MINUTE_IN_SECONDS );
		return $report;
	}

	public function page_summary( $url ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->urls_table() ) || ! $this->table_exists( $this->pagespeed_table() ) ) {
			return array( 'available' => false, 'url' => array(), 'pagespeed' => array() );
		}
		$hash = $this->url_hash( $url );
		$urls_table = $this->urls_table();
		$pagespeed_table = $this->pagespeed_table();
		$url_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$urls_table} WHERE url_hash=%s", $hash ), ARRAY_A );
		$performance = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$pagespeed_table} WHERE url_hash=%s ORDER BY fetched_at DESC LIMIT 1", $hash ), ARRAY_A );
		return array(
			'available' => (bool) $url_row,
			'url' => $url_row ? $this->normalize_url_row( $url_row ) : array(),
			'pagespeed' => $performance ? $this->normalize_pagespeed( $performance ) : array(),
		);
	}


	private function redirect_chains( array $rows ) {
		$map = array();
		foreach ( $rows as $row ) {
			$source = $this->normalize_url( $row['url'] ?? '' );
			$target = $this->normalize_url( $row['redirect_target'] ?? '' );
			if ( $source && $target ) { $map[ $source ] = $target; }
		}
		$chains = array();
		foreach ( $map as $source => $target ) {
			$path = array( $source );
			$current = $target;
			$loop = false;
			for ( $hop = 0; $hop < 8; $hop++ ) {
				$path[] = $current;
				if ( in_array( $current, array_slice( $path, 0, -1 ), true ) ) { $loop = true; break; }
				if ( empty( $map[ $current ] ) ) { break; }
				$current = $map[ $current ];
			}
			if ( count( $path ) > 2 || $loop ) {
				$chains[] = array(
					'url' => $source,
					'destination_url' => end( $path ),
					'chain_text' => implode( ' → ', array_map( function( $url ) { return wp_parse_url( $url, PHP_URL_PATH ) ?: '/'; }, $path ) ),
					'hops' => max( 1, count( $path ) - 1 ),
					'loop' => $loop,
					'status_code' => $loop ? 508 : 300,
				);
			}
		}
		usort( $chains, function( $a, $b ) { return (int) $b['hops'] <=> (int) $a['hops']; } );
		return $chains;
	}

	private function top_opportunities( array $audits ) {
		$items = array();
		foreach ( $audits as $id => $audit ) {
			$savings = floatval( $audit['details']['overallSavingsMs'] ?? 0 );
			if ( $savings <= 0 && 'numeric' !== ( $audit['scoreDisplayMode'] ?? '' ) ) {
				continue;
			}
			if ( isset( $audit['score'] ) && floatval( $audit['score'] ) >= 0.9 ) {
				continue;
			}
			$items[] = array( 'id' => sanitize_key( $id ), 'title' => sanitize_text_field( $audit['title'] ?? $id ), 'description' => sanitize_text_field( wp_strip_all_tags( $audit['description'] ?? '' ) ), 'savings_ms' => $savings );
		}
		usort( $items, function( $a, $b ) { return $b['savings_ms'] <=> $a['savings_ms']; } );
		return array_slice( $items, 0, 10 );
	}

	private function score( $score ) { return null === $score ? 0 : (int) round( floatval( $score ) * 100 ); }
	private function audit_numeric( array $audits, $id ) { return floatval( $audits[ $id ]['numericValue'] ?? 0 ); }
	private function remember_pagespeed_error( $message, $secret = '' ) {
		$message = (string) $message;
		if ( '' !== (string) $secret ) {
			$message = str_replace( array( (string) $secret, rawurlencode( (string) $secret ) ), '[redacted]', $message );
		}
		$settings = get_option( Ikon_SEO_Plugin::OPTION_KEY, array() );
		$settings['pagespeed_last_error'] = sanitize_text_field( $message );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
	}
	private function normalize_pagespeed( array $row ) {
		foreach ( array( 'performance_score','seo_score','accessibility_score','best_practices_score','field_data_available' ) as $key ) { $row[ $key ] = absint( $row[ $key ] ?? 0 ); }
		foreach ( array( 'lcp_ms','inp_ms','cls','tbt_ms','fcp_ms','field_lcp_ms','field_inp_ms','field_cls','field_ttfb_ms' ) as $key ) { $row[ $key ] = floatval( $row[ $key ] ?? 0 ); }
		$row['opportunities'] = json_decode( (string) ( $row['opportunities_json'] ?? '' ), true ) ?: array();
		unset( $row['opportunities_json'] );
		return $row;
	}
	private function normalize_url_row( array $row ) {
		foreach ( array( 'post_id','status_code','response_ms','inbound_links','outbound_links','crawl_depth' ) as $key ) { $row[ $key ] = intval( $row[ $key ] ?? 0 ); }
		return $row;
	}
	private function normalize_url( $url ) {
		$url = esc_url_raw( trim( (string) $url ) );
		if ( ! $url || ! wp_http_validate_url( $url ) || ! $this->same_site( $url ) ) { return ''; }
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) { return ''; }
		$scheme = strtolower( $parts['scheme'] ?? 'https' );
		$host = strtolower( $parts['host'] ?? '' );
		$path = isset( $parts['path'] ) ? '/' . ltrim( $parts['path'], '/' ) : '/';
		$query = ! empty( $parts['query'] ) ? '?' . $parts['query'] : '';
		return esc_url_raw( $scheme . '://' . $host . ( isset( $parts['port'] ) ? ':' . absint( $parts['port'] ) : '' ) . $path . $query );
	}
	private function same_site( $url ) {
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$url_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return $home_host && $url_host && preg_replace( '/^www\./', '', $home_host ) === preg_replace( '/^www\./', '', $url_host );
	}
	private function url_hash( $url ) {
		$normalized = $this->normalize_url( $url );
		return hash( 'sha256', untrailingslashit( $normalized ?: (string) $url ) );
	}
	private function urls_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_technical_urls'; }
	private function links_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_link_graph'; }
	private function pagespeed_table() { global $wpdb; return $wpdb->prefix . 'ikon_seo_pagespeed'; }
	private function table_exists( $table ) { global $wpdb; return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); }
}
