<?php

defined( 'ABSPATH' ) || exit;

/**
 * Same-site evidence crawler. It never follows links to another hostname and
 * never changes page content.
 */
final class Ikon_SEO_Crawler {
	private $logger;

	public function __construct( Ikon_SEO_Logger $logger ) {
		$this->logger = $logger;
	}

	public function register_hooks() {
		add_action( 'ikon_seo_evidence_crawl', array( $this, 'scheduled_crawl' ) );
		add_action( 'save_post', array( $this, 'mark_post_stale' ), 20, 3 );
	}

	public function scheduled_crawl() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['crawler_enabled'] ) ) {
			return;
		}
		$this->crawl_batch( absint( $settings['crawler_batch_size'] ?? 10 ), false );
	}

	public function mark_post_stale( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'publish' !== $post->post_status || ! is_post_type_viewable( $post->post_type ) || 'attachment' === $post->post_type ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_evidence';
		if ( ! $this->table_exists( $table ) ) {
			return;
		}
		$wpdb->update( $table, array( 'stale' => 1 ), array( 'post_id' => absint( $post_id ) ), array( '%d' ), array( '%d' ) );
	}

	public function crawl_batch( $limit = 10, $force = false ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_evidence';
		if ( ! $this->table_exists( $table ) ) {
			return new WP_Error( 'ikon_seo_evidence_table', __( 'The evidence table is not available. Reactivate Ikon SEO to repair its database.', 'ikon-seo' ) );
		}
		$settings   = Ikon_SEO_Plugin::settings();
		$limit      = max( 1, min( 50, absint( $limit ) ) );
		$stale_days = max( 1, min( 90, absint( $settings['crawler_stale_days'] ?? 14 ) ) );
		$cutoff     = gmdate( 'Y-m-d H:i:s', time() - $stale_days * DAY_IN_SECONDS );

		$post_types = $this->public_post_types();
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
		if ( $force ) {
			$args = array_merge( $post_types, array( $limit ) );
			$sql = $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type IN ({$placeholders}) AND p.post_status='publish' ORDER BY p.post_modified_gmt DESC LIMIT %d", $args );
		} else {
			$args = array_merge( $post_types, array( $cutoff, $limit ) );
			$sql = $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$table} e ON e.post_id=p.ID WHERE p.post_type IN ({$placeholders}) AND p.post_status='publish' AND (e.post_id IS NULL OR e.stale=1 OR e.crawled_at < %s) ORDER BY CASE WHEN e.post_id IS NULL THEN 0 ELSE 1 END, e.crawled_at ASC, p.ID ASC LIMIT %d", $args );
		}
		$post_ids = array_map( 'absint', (array) $wpdb->get_col( $sql ) );
		$results  = array();
		foreach ( $post_ids as $post_id ) {
			$results[] = $this->crawl_post( $post_id );
		}
		$this->capture_site_files();
		$failed = count( array_filter( $results, 'is_wp_error' ) );
		$this->logger->log( 'evidence_crawl', $failed ? 'partial' : 'success', sprintf( 'Crawled %d pages; %d requests failed.', count( $results ), $failed ) );
		return array(
			'processed' => count( $results ),
			'failed'    => $failed,
			'remaining' => $this->pending_count( $cutoff ),
			'status'    => $this->status(),
		);
	}

	public function crawl_post( $post_id ) {
		$post = get_post( absint( $post_id ) );
		if ( ! $post || ! in_array( $post->post_type, $this->public_post_types(), true ) || 'publish' !== $post->post_status ) {
			return new WP_Error( 'ikon_seo_crawl_post', __( 'Only published, publicly viewable WordPress content can be crawled.', 'ikon-seo' ) );
		}
		$url = get_permalink( $post );
		if ( ! $url || ! $this->same_site( $url ) ) {
			return new WP_Error( 'ikon_seo_crawl_url', __( 'The page URL is not a valid same-site URL.', 'ikon-seo' ) );
		}

		$started  = microtime( true );
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 15,
				'redirection'         => 5,
				'limit_response_size' => 2 * MB_IN_BYTES,
				'headers'             => array( 'User-Agent' => 'IkonSEO/' . IKON_SEO_VERSION . '; ' . home_url( '/' ) ),
			)
		);
		$duration = (int) round( ( microtime( true ) - $started ) * 1000 );
		if ( is_wp_error( $response ) ) {
			$this->store_error( $post, $url, $duration, $response->get_error_message() );
			return $response;
		}

		$status       = absint( wp_remote_retrieve_response_code( $response ) );
		$content_type = sanitize_text_field( wp_remote_retrieve_header( $response, 'content-type' ) );
		$body         = (string) wp_remote_retrieve_body( $response );
		$parsed       = $this->parse_html( $body, $url );
		$final_url    = esc_url_raw( wp_remote_retrieve_header( $response, 'x-final-url' ) ?: $url );
		$canonical    = esc_url_raw( $parsed['canonical'] );
		$robots       = strtolower( implode( ',', (array) $parsed['robots'] ) );
		$indexable    = 200 === $status && false === strpos( $robots, 'noindex' ) && ( ! $canonical || $this->same_url( $canonical, $url ) );
		$issues       = array();
		if ( 200 !== $status ) {
			$issues[] = array( 'code' => 'http_status', 'impact' => 'critical', 'message' => 'The URL returned HTTP ' . $status . '.' );
		}
		if ( false !== strpos( $robots, 'noindex' ) ) {
			$issues[] = array( 'code' => 'noindex', 'impact' => 'critical', 'message' => 'The rendered page contains a noindex directive.' );
		}
		if ( $canonical && ! $this->same_url( $canonical, $url ) ) {
			$issues[] = array( 'code' => 'canonical_mismatch', 'impact' => 'high', 'message' => 'The canonical points to a different URL.' );
		}
		if ( ! $parsed['title'] ) {
			$issues[] = array( 'code' => 'missing_title', 'impact' => 'high', 'message' => 'The rendered page has no title element.' );
		}
		if ( ! $parsed['description'] ) {
			$issues[] = array( 'code' => 'missing_description', 'impact' => 'low', 'message' => 'The rendered page has no meta-description proposal; this is a search-appearance opportunity rather than a direct ranking failure.' );
		}
		if ( 0 === $parsed['h1_count'] ) {
			$issues[] = array( 'code' => 'missing_h1', 'impact' => 'medium', 'message' => 'The rendered page has no clear H1 heading.' );
		}
		// Multiple H1 elements are retained as evidence but are not an automatic SEO failure.
		if ( $duration > 2500 ) {
			$issues[] = array( 'code' => 'slow_server_response', 'impact' => 'medium', 'message' => 'The crawl request took more than 2.5 seconds.' );
		}

		$record = array(
			'post_id'              => (int) $post->ID,
			'url_hash'             => hash( 'sha256', untrailingslashit( strtolower( $url ) ) ),
			'url'                  => $url,
			'final_url'            => $final_url,
			'status_code'          => $status,
			'content_type'         => $content_type,
			'response_ms'          => $duration,
			'indexable'            => $indexable ? 1 : 0,
			'robots'               => $robots,
			'canonical'            => $canonical,
			'rendered_title'       => $parsed['title'],
			'rendered_description' => $parsed['description'],
			'h1_count'             => $parsed['h1_count'],
			'word_count'           => $parsed['word_count'],
			'internal_links'       => $parsed['internal_links'],
			'external_links'       => $parsed['external_links'],
			'image_count'          => $parsed['image_count'],
			'missing_alt'          => $parsed['missing_alt'],
			'issue_count'          => count( $issues ),
			'evidence_json'        => wp_json_encode( array( 'issues' => $issues, 'h1_text' => $parsed['h1_text'], 'internal_urls' => $parsed['internal_urls'], 'links' => $parsed['links'] ) ),
			'last_error'           => '',
			'stale'                => 0,
			'crawled_at'           => current_time( 'mysql', true ),
			'updated_at'           => current_time( 'mysql', true ),
		);
		$this->store( $record );
		return $this->normalize_record( $record );
	}

	public function status() {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_evidence';
		if ( ! $this->table_exists( $table ) ) {
			return array( 'ready' => false, 'crawled' => 0, 'pending' => 0, 'errors' => 0, 'critical' => 0, 'last_crawl' => '' );
		}
		$post_types = $this->public_post_types();
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
		$published = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ({$placeholders}) AND post_status='publish'", $post_types ) );
		$row = $wpdb->get_row( "SELECT COUNT(*) crawled, SUM(CASE WHEN last_error<>'' THEN 1 ELSE 0 END) errors, SUM(CASE WHEN indexable=0 OR status_code<>200 THEN 1 ELSE 0 END) critical, MAX(crawled_at) last_crawl FROM {$table}", ARRAY_A );
		$crawled = absint( $row['crawled'] ?? 0 );
		return array(
			'ready'      => true,
			'published'  => $published,
			'crawled'    => $crawled,
			'pending'    => max( 0, $published - $crawled ) + (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE stale=1" ),
			'errors'     => absint( $row['errors'] ?? 0 ),
			'critical'   => absint( $row['critical'] ?? 0 ),
			'last_crawl' => sanitize_text_field( $row['last_crawl'] ?? '' ),
			'robots'     => get_option( 'ikon_seo_robots_snapshot', array() ),
			'sitemap'    => get_option( 'ikon_seo_sitemap_snapshot', array() ),
		);
	}

	public function get( $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_evidence';
		if ( ! $this->table_exists( $table ) ) {
			return array();
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE post_id=%d", absint( $post_id ) ), ARRAY_A );
		return is_array( $row ) ? $this->normalize_record( $row ) : array();
	}

	public function list_records( $limit = 300 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_evidence';
		if ( ! $this->table_exists( $table ) ) {
			return array();
		}
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY issue_count DESC, crawled_at ASC LIMIT %d", max( 1, min( 20000, absint( $limit ) ) ) ), ARRAY_A );
		return array_map( array( $this, 'normalize_record' ), (array) $rows );
	}

	private function parse_html( $html, $base_url ) {
		$result = array(
			'title' => '', 'description' => '', 'canonical' => '', 'robots' => array(), 'h1_count' => 0,
			'h1_text' => array(), 'word_count' => 0, 'internal_links' => 0, 'external_links' => 0,
			'internal_urls' => array(), 'links' => array(), 'image_count' => 0, 'missing_alt' => 0,
		);
		if ( ! is_string( $html ) || '' === $html ) {
			return $result;
		}
		if ( class_exists( 'DOMDocument' ) ) {
			$previous = libxml_use_internal_errors( true );
			$dom = new DOMDocument();
			$loaded = $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
			if ( $loaded ) {
				$titles = $dom->getElementsByTagName( 'title' );
				$result['title'] = $titles->length ? sanitize_text_field( $titles->item( 0 )->textContent ) : '';
				foreach ( $dom->getElementsByTagName( 'meta' ) as $meta ) {
					$name = strtolower( trim( $meta->getAttribute( 'name' ) ) );
					if ( 'description' === $name ) {
						$result['description'] = sanitize_text_field( $meta->getAttribute( 'content' ) );
					} elseif ( 'robots' === $name || 'googlebot' === $name ) {
						$result['robots'] = array_merge( $result['robots'], array_map( 'trim', explode( ',', strtolower( $meta->getAttribute( 'content' ) ) ) ) );
					}
				}
				foreach ( $dom->getElementsByTagName( 'link' ) as $link ) {
					if ( 'canonical' === strtolower( trim( $link->getAttribute( 'rel' ) ) ) ) {
						$result['canonical'] = $this->absolute_url( $link->getAttribute( 'href' ), $base_url );
						break;
					}
				}
				foreach ( $dom->getElementsByTagName( 'h1' ) as $h1 ) {
					$result['h1_text'][] = sanitize_text_field( $h1->textContent );
				}
				$result['h1_count'] = count( $result['h1_text'] );
				$internal = array();
				$external = array();
				foreach ( $dom->getElementsByTagName( 'a' ) as $anchor ) {
					$href = $this->absolute_url( $anchor->getAttribute( 'href' ), $base_url );
					if ( ! $href || ! wp_http_validate_url( $href ) ) {
						continue;
					}
					if ( $this->same_site( $href ) ) {
						$clean = untrailingslashit( strtok( $href, '#' ) );
						$internal[] = $clean;
						$rel = strtolower( trim( $anchor->getAttribute( 'rel' ) ) );
						$result['links'][] = array(
							'url' => $clean,
							'anchor' => sanitize_text_field( trim( $anchor->textContent ) ),
							'rel' => sanitize_text_field( $rel ),
							'placement' => $this->link_placement( $anchor ),
							'follow' => false === strpos( ' ' . $rel . ' ', ' nofollow ' ),
						);
					} else {
						$external[] = $href;
					}
				}
				$result['internal_urls']  = array_values( array_unique( array_filter( $internal ) ) );
				$result['internal_links'] = count( $result['internal_urls'] );
				$result['external_links'] = count( array_unique( $external ) );
				foreach ( $dom->getElementsByTagName( 'img' ) as $image ) {
					$result['image_count']++;
					if ( ! trim( $image->getAttribute( 'alt' ) ) ) {
						$result['missing_alt']++;
					}
				}
				$body = $dom->getElementsByTagName( 'body' );
				$text = $body->length ? $body->item( 0 )->textContent : wp_strip_all_tags( $html );
				$result['word_count'] = $this->word_count( $text );
				return $result;
			}
		}
		preg_match( '~<title[^>]*>(.*?)</title>~is', $html, $title );
		preg_match( '~<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)~i', $html, $description );
		preg_match( '~<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']*)~i', $html, $canonical );
		preg_match_all( '~<h1\b[^>]*>(.*?)</h1>~is', $html, $h1s );
		$result['title'] = sanitize_text_field( wp_strip_all_tags( $title[1] ?? '' ) );
		$result['description'] = sanitize_text_field( $description[1] ?? '' );
		$result['canonical'] = $this->absolute_url( $canonical[1] ?? '', $base_url );
		$result['h1_text'] = array_map( 'sanitize_text_field', array_map( 'wp_strip_all_tags', $h1s[1] ?? array() ) );
		$result['h1_count'] = count( $result['h1_text'] );
		$result['word_count'] = $this->word_count( wp_strip_all_tags( $html ) );
		preg_match_all( '~<img\b[^>]*>~i', $html, $images );
		$result['image_count'] = count( $images[0] ?? array() );
		foreach ( $images[0] ?? array() as $image ) {
			if ( ! preg_match( '~\balt=["\'][^"\']+~i', $image ) ) {
				$result['missing_alt']++;
			}
		}
		return $result;
	}

	private function store_error( WP_Post $post, $url, $duration, $message ) {
		$this->store(
			array(
				'post_id' => $post->ID, 'url_hash' => hash( 'sha256', untrailingslashit( strtolower( $url ) ) ), 'url' => $url,
				'final_url' => $url, 'status_code' => 0, 'content_type' => '', 'response_ms' => $duration, 'indexable' => 0,
				'robots' => '', 'canonical' => '', 'rendered_title' => '', 'rendered_description' => '', 'h1_count' => 0,
				'word_count' => 0, 'internal_links' => 0, 'external_links' => 0, 'image_count' => 0, 'missing_alt' => 0,
				'issue_count' => 1, 'evidence_json' => wp_json_encode( array( 'issues' => array( array( 'code' => 'crawl_error', 'impact' => 'critical', 'message' => sanitize_text_field( $message ) ) ) ) ),
				'last_error' => sanitize_text_field( $message ), 'stale' => 0, 'crawled_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ),
			)
		);
	}

	private function store( array $record ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_evidence';
		$wpdb->replace( $table, $record );
	}

	private function normalize_record( array $row ) {
		$row['post_id']        = absint( $row['post_id'] ?? 0 );
		$row['status_code']    = absint( $row['status_code'] ?? 0 );
		$row['response_ms']    = absint( $row['response_ms'] ?? 0 );
		$row['indexable']      = ! empty( $row['indexable'] );
		$row['h1_count']       = absint( $row['h1_count'] ?? 0 );
		$row['word_count']     = absint( $row['word_count'] ?? 0 );
		$row['internal_links'] = absint( $row['internal_links'] ?? 0 );
		$row['external_links'] = absint( $row['external_links'] ?? 0 );
		$row['image_count']    = absint( $row['image_count'] ?? 0 );
		$row['missing_alt']    = absint( $row['missing_alt'] ?? 0 );
		$row['issue_count']    = absint( $row['issue_count'] ?? 0 );
		$row['stale']          = ! empty( $row['stale'] );
		$row['evidence']       = json_decode( (string) ( $row['evidence_json'] ?? '' ), true );
		unset( $row['evidence_json'], $row['url_hash'] );
		return $row;
	}

	private function public_post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		unset( $post_types['attachment'] );
		return $post_types ? array_values( $post_types ) : array( 'page', 'post' );
	}

	private function link_placement( DOMNode $node ) {
		$parent = $node->parentNode;
		while ( $parent ) {
			$name = strtolower( (string) $parent->nodeName );
			if ( in_array( $name, array( 'nav', 'header', 'footer', 'main', 'article', 'aside' ), true ) ) {
				return $name;
			}
			$parent = $parent->parentNode;
		}
		return 'body';
	}

	private function capture_site_files() {
		$robots_url = home_url( '/robots.txt' );
		$response = wp_safe_remote_get( $robots_url, array( 'timeout' => 10, 'limit_response_size' => 256 * KB_IN_BYTES ) );
		$body = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
		update_option(
			'ikon_seo_robots_snapshot',
			array(
				'url' => $robots_url,
				'status' => is_wp_error( $response ) ? 0 : absint( wp_remote_retrieve_response_code( $response ) ),
				'blocks_all' => (bool) preg_match( '~User-agent:\s*\*[^#]*Disallow:\s*/\s*(?:\r?\n|$)~is', $body ),
				'fetched_at' => current_time( 'mysql', true ),
			),
			false
		);
		$sitemap_url = defined( 'RANK_MATH_VERSION' ) ? home_url( '/sitemap_index.xml' ) : home_url( '/wp-sitemap.xml' );
		$sitemap = wp_safe_remote_head( $sitemap_url, array( 'timeout' => 10, 'redirection' => 3 ) );
		update_option(
			'ikon_seo_sitemap_snapshot',
			array(
				'url' => $sitemap_url,
				'status' => is_wp_error( $sitemap ) ? 0 : absint( wp_remote_retrieve_response_code( $sitemap ) ),
				'fetched_at' => current_time( 'mysql', true ),
			),
			false
		);
	}

	private function pending_count( $cutoff ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_evidence';
		$post_types = $this->public_post_types();
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
		$args = array_merge( $post_types, array( $cutoff ) );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$table} e ON e.post_id=p.ID WHERE p.post_type IN ({$placeholders}) AND p.post_status='publish' AND (e.post_id IS NULL OR e.stale=1 OR e.crawled_at < %s)", $args ) );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}

	private function same_site( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$home = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		return $host && $home && hash_equals( $home, $host );
	}

	private function same_url( $a, $b ) {
		return untrailingslashit( strtolower( strtok( (string) $a, '#' ) ) ) === untrailingslashit( strtolower( strtok( (string) $b, '#' ) ) );
	}

	private function absolute_url( $url, $base ) {
		$url = trim( html_entity_decode( (string) $url ) );
		if ( ! $url || preg_match( '~^(?:#|mailto:|tel:|javascript:)~i', $url ) ) {
			return '';
		}
		if ( 0 === strpos( $url, '//' ) ) {
			return esc_url_raw( ( is_ssl() ? 'https:' : 'http:' ) . $url );
		}
		if ( preg_match( '~^https?://~i', $url ) ) {
			return esc_url_raw( $url );
		}
		if ( 0 === strpos( $url, '/' ) ) {
			return esc_url_raw( home_url( $url ) );
		}
		$path = trailingslashit( dirname( (string) wp_parse_url( $base, PHP_URL_PATH ) ) );
		return esc_url_raw( home_url( $path . $url ) );
	}

	private function word_count( $text ) {
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
		if ( ! $text ) {
			return 0;
		}
		preg_match_all( '/[\p{L}\p{N}][\p{L}\p{N}\'’\-]*/u', $text, $matches );
		return count( $matches[0] ?? array() );
	}
}
