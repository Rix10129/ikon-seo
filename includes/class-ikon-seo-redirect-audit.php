<?php

defined( 'ABSPATH' ) || exit;

/** Read-only redirect opportunity audit using internal broken links and Rank Math logs when available. */
final class Ikon_SEO_Redirect_Audit {
	const CACHE_KEY = 'ikon_seo_redirect_audit_v1';

	private $inventory;

	public function __construct( Ikon_SEO_Inventory $inventory ) {
		$this->inventory = $inventory;
	}

	public function scan( $refresh = false ) {
		if ( $refresh ) {
			delete_transient( self::CACHE_KEY );
		}
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			$cached['cached'] = true;
			return $cached;
		}

		$inventory = $this->inventory->scan( $refresh );
		$published = array_values( array_filter( (array) ( $inventory['items'] ?? array() ), function( $item ) { return 'publish' === ( $item['status'] ?? '' ); } ) );
		$opportunities = array();

		foreach ( (array) ( $inventory['items'] ?? array() ) as $source ) {
			foreach ( (array) ( $source['unresolved_internal_urls'] ?? array() ) as $url ) {
				$opportunities[] = $this->opportunity( $url, 'internal_link', 1, $source, $published );
			}
		}

		$rank_math = $this->rank_math_404_rows();
		foreach ( $rank_math['rows'] as $row ) {
			$opportunities[] = $this->opportunity(
				home_url( '/' . ltrim( (string) ( $row['uri'] ?? '' ), '/' ) ),
				'rank_math_404',
				max( 1, absint( $row['hits'] ?? 1 ) ),
				array(),
				$published
			);
		}

		$deduped = array();
		foreach ( $opportunities as $item ) {
			$key = strtolower( untrailingslashit( (string) $item['broken_url'] ) );
			if ( isset( $deduped[ $key ] ) ) {
				$deduped[ $key ]['hits'] += $item['hits'];
				$deduped[ $key ]['sources'] = array_values( array_unique( array_merge( $deduped[ $key ]['sources'], $item['sources'] ) ) );
				continue;
			}
			$deduped[ $key ] = $item;
		}
		$opportunities = array_values( $deduped );
		usort( $opportunities, function( $a, $b ) { return $b['hits'] <=> $a['hits']; } );

		$result = array(
			'generated_at' => current_time( 'mysql', true ),
			'cached'       => false,
			'rank_math_404_available' => $rank_math['available'],
			'summary' => array(
				'opportunities'     => count( $opportunities ),
				'internal_broken'   => count( array_filter( $opportunities, function( $item ) { return in_array( 'internal_link', $item['sources'], true ); } ) ),
				'logged_404s'       => count( array_filter( $opportunities, function( $item ) { return in_array( 'rank_math_404', $item['sources'], true ); } ) ),
				'with_suggestion'   => count( array_filter( $opportunities, function( $item ) { return ! empty( $item['suggested_url'] ); } ) ),
			),
			'items' => array_slice( $opportunities, 0, 250 ),
			'policy' => 'Recommendation-only. Redirects are not created automatically.',
		);

		set_transient( self::CACHE_KEY, $result, 10 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	private function rank_math_404_rows() {
		global $wpdb;
		$table = $wpdb->prefix . 'rank_math_404_logs';
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			return array( 'available' => false, 'rows' => array() );
		}
		$rows = $wpdb->get_results( "SELECT uri, hits FROM {$table} ORDER BY hits DESC LIMIT 150", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array( 'available' => true, 'rows' => is_array( $rows ) ? $rows : array() );
	}

	private function opportunity( $url, $source_type, $hits, array $source, array $published ) {
		$suggestion = $this->suggest( $url, $published );
		return array(
			'broken_url'    => esc_url_raw( $url ),
			'hits'          => absint( $hits ),
			'sources'       => array( sanitize_key( $source_type ) ),
			'source_page_id'=> absint( $source['id'] ?? 0 ),
			'source_title'  => sanitize_text_field( $source['title'] ?? '' ),
			'suggested_id'  => absint( $suggestion['id'] ?? 0 ),
			'suggested_url' => esc_url_raw( $suggestion['url'] ?? '' ),
			'suggested_title'=> sanitize_text_field( $suggestion['title'] ?? '' ),
			'confidence'    => absint( $suggestion['confidence'] ?? 0 ),
		);
	}

	private function suggest( $broken_url, array $published ) {
		$path = trim( (string) wp_parse_url( $broken_url, PHP_URL_PATH ), '/' );
		$needle = $this->tokens( $path );
		$best = array();
		foreach ( $published as $item ) {
			$haystack = $this->tokens( ( $item['slug'] ?? '' ) . ' ' . ( $item['title'] ?? '' ) );
			if ( ! $needle || ! $haystack ) {
				continue;
			}
			$common = count( array_intersect( $needle, $haystack ) );
			$score  = (int) round( 100 * ( $common / max( count( $needle ), count( $haystack ) ) ) );
			if ( $score > ( $best['confidence'] ?? 0 ) ) {
				$best = array(
					'id'         => absint( $item['id'] ?? 0 ),
					'url'        => esc_url_raw( $item['url'] ?? '' ),
					'title'      => sanitize_text_field( $item['title'] ?? '' ),
					'confidence' => $score,
				);
			}
		}
		return ( $best['confidence'] ?? 0 ) >= 35 ? $best : array();
	}

	private function tokens( $value ) {
		$value = strtolower( preg_replace( '/[^a-z0-9]+/i', ' ', (string) $value ) );
		$tokens = array_filter( explode( ' ', $value ), function( $token ) { return strlen( $token ) > 2; } );
		return array_values( array_unique( $tokens ) );
	}
}
