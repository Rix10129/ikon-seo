<?php

defined( 'ABSPATH' ) || exit;

class Ikon_SEO_Quality {
	private $profile;
	private $local;

	public function __construct( Ikon_SEO_Profile $profile, Ikon_SEO_Local $local ) {
		$this->profile = $profile;
		$this->local   = $local;
	}

	public function audit_payload( array $payload, array $rendered, array $schema_graph = array() ) {
		$title       = wp_strip_all_tags( $payload['title'] ?? '' );
		$hero_title  = wp_strip_all_tags( $payload['hero']['title'] ?? $title );
		$seo_title   = wp_strip_all_tags( $payload['seo']['title'] ?? '' );
		$description = wp_strip_all_tags( $payload['seo']['description'] ?? '' );
		$focus       = trim( wp_strip_all_tags( $payload['seo']['focus_keyword'] ?? '' ) );
		$html        = (string) ( $rendered['post_content'] ?? '' );
		$text        = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $html ) ) );
		$word_count  = $this->word_count( $text );
		$links       = $this->links( $html );
		$checks      = array();
		$score       = 100;
		$profile     = $this->profile->get();

		$this->check(
			$checks,
			$score,
			'website_profile',
			! empty( $profile['configured'] ) && ( empty( $payload['profile_id'] ) || $payload['profile_id'] === $profile['profile_id'] ) ? 'pass' : 'fail',
			'The page is bound to the active Website Profile.',
			'The Website Profile is incomplete or the page was prepared for a different profile.',
			20
		);

		$language = sanitize_text_field( $payload['language'] ?? $profile['default_language'] );
		$this->check(
			$checks,
			$score,
			'profile_language',
			in_array( $language, $profile['supported_languages'], true ) ? 'pass' : 'fail',
			'The page language is enabled in the Website Profile.',
			'The page language is not enabled in the Website Profile.',
			12
		);

		$this->check( $checks, $score, 'page_title', $title ? 'pass' : 'fail', 'Page title is present.', 'A page title is required.', 20 );
		$this->check( $checks, $score, 'single_h1', $hero_title && 1 === substr_count( strtolower( $html ), '<h1' ) ? 'pass' : 'fail', 'Exactly one H1 is present.', 'The page should contain exactly one H1.', 15 );

		$title_length = $this->length( $seo_title );
		$this->check(
			$checks,
			$score,
			'seo_title',
			$title_length >= 30 && $title_length <= 65 ? 'pass' : ( $seo_title ? 'warning' : 'fail' ),
			'SEO title length is within the review range.',
			$seo_title ? 'SEO title is outside the 30–65 character review range.' : 'SEO title is missing.',
			$seo_title ? 5 : 12
		);

		$description_length = $this->length( $description );
		$this->check(
			$checks,
			$score,
			'meta_description',
			$description_length >= 100 && $description_length <= 170 ? 'pass' : ( $description ? 'warning' : 'fail' ),
			'Meta description length is within the review range.',
			$description ? 'Meta description is outside the 100–170 character review range.' : 'Meta description is missing.',
			$description ? 5 : 12
		);

		$focus_in_title = $focus && false !== stripos( $seo_title . ' ' . $hero_title, $focus );
		$this->check(
			$checks,
			$score,
			'focus_alignment',
			$focus_in_title ? 'pass' : ( $focus ? 'warning' : 'fail' ),
			'The focus keyword is aligned with the title or H1.',
			$focus ? 'Review focus-keyword alignment with the SEO title and H1.' : 'A focus keyword is required.',
			$focus ? 5 : 12
		);

		$this->check(
			$checks,
			$score,
			'content_depth',
			$word_count >= 600 ? 'pass' : ( $word_count >= 300 ? 'warning' : 'fail' ),
			'The page has substantial visible content.',
			$word_count >= 300 ? 'The page may need more depth for its target intent.' : 'The page has very little visible content.',
			$word_count >= 300 ? 5 : 12
		);

		$this->check(
			$checks,
			$score,
			'internal_links',
			count( $links['internal'] ) >= 2 ? 'pass' : 'warning',
			'The page contains at least two internal links.',
			'Review internal-link opportunities; fewer than two confirmed internal links were found.',
			5
		);

		$faq_count = count( is_array( $payload['faq'] ?? null ) ? $payload['faq'] : array() );
		$faq_schema = $this->graph_has_type( $schema_graph, 'FAQPage' );
		$this->check(
			$checks,
			$score,
			'faq_visibility',
			! $faq_schema || $faq_count > 0 ? 'pass' : 'fail',
			'FAQ schema, when used, matches visible FAQ content.',
			'FAQPage markup must not be emitted without visible FAQ content.',
			15
		);

		$review = isset( $payload['content_review'] ) && is_array( $payload['content_review'] ) ? $payload['content_review'] : array();
		$ymyl   = ! empty( $review['ymyl'] ) || $this->profile->industry_is_high_trust() || $this->looks_high_trust( $payload );
		if ( $ymyl ) {
			$sources = array_filter( array_map( 'esc_url_raw', (array) ( $review['sources'] ?? array() ) ) );
			$this->check(
				$checks,
				$score,
				'authoritative_sources',
				$sources ? 'pass' : 'fail',
				'Authoritative source URLs are recorded for this high-trust page.',
				'High-trust tax, legal, financial or medical content requires authoritative source URLs.',
				15
			);
			$this->check(
				$checks,
				$score,
				'fact_review',
				! empty( $review['reviewed_by'] ) && ! empty( $review['fact_checked_date'] ) ? 'pass' : 'warning',
				'A reviewer and fact-check date are recorded.',
				'Assign a qualified reviewer and fact-check date before publishing.',
				8
			);
			$this->check(
				$checks,
				$score,
				'applicable_period',
				! empty( $review['jurisdiction'] ) && ! empty( $review['applicable_period'] ) ? 'pass' : 'warning',
				'Jurisdiction and applicable period are recorded.',
				'Add the jurisdiction and applicable tax or regulatory period.',
				5
			);
			$this->check(
				$checks,
				$score,
				'refresh_schedule',
				! empty( $review['next_review_date'] ) ? 'pass' : 'warning',
				'A next review date is recorded for this high-trust page.',
				'Schedule a future review date for time-sensitive high-trust content.',
				4
			);
		}

		$schema_types = $this->schema_types( $schema_graph );
		$this->check(
			$checks,
			$score,
			'schema',
			$schema_types ? 'pass' : 'warning',
			'An allow-listed schema graph is available.',
			'No page-specific schema nodes were generated.',
			4
		);

		$local_report = $this->local->quality( $payload, $rendered );
		if ( 'not_local' !== $local_report['status'] ) {
			foreach ( $local_report['checks'] as $local_check ) {
				$local_check['id'] = 'local_' . sanitize_key( $local_check['id'] );
				$checks[] = $local_check;
			}
			$score = min( $score, absint( $local_report['score'] ) );
		}

		$score    = max( 0, min( 100, $score ) );
		$failures = count( array_filter( $checks, function( $item ) { return 'fail' === $item['status']; } ) );
		$warnings = count( array_filter( $checks, function( $item ) { return 'warning' === $item['status']; } ) );

		return array(
			'score'           => $score,
			'status'          => $failures ? 'needs_changes' : ( $warnings ? 'review' : 'ready' ),
			'requires_review' => (bool) ( $failures || $warnings || $ymyl ),
			'metrics'         => array(
				'word_count'              => $word_count,
				'seo_title_characters'    => $title_length,
				'description_characters'  => $description_length,
				'internal_links'          => count( $links['internal'] ),
				'external_links'          => count( $links['external'] ),
				'faq_count'               => $faq_count,
				'schema_types'            => $schema_types,
				'profile_id'              => $profile['profile_id'],
				'profile_industry'        => $profile['industry'],
				'local_page'              => 'not_local' !== $local_report['status'],
				'local_quality_score'     => 'not_local' !== $local_report['status'] ? absint( $local_report['score'] ) : null,
				'local_similarity'        => $local_report['metrics']['highest_local_page_similarity'] ?? null,
			),
			'checks'          => $checks,
			'local_report'    => $local_report,
			'generated_at'    => current_time( 'mysql', true ),
		);
	}

	public function post_snapshot( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$elementor = get_post_meta( $post_id, '_elementor_data', true );
		$html      = $post->post_content;
		$links     = $this->links( is_string( $elementor ) ? $html . ' ' . $elementor : $html );
		$settings  = Ikon_SEO_Plugin::settings();
		$selected  = $settings['seo_plugin_preference'];
		if ( 'auto' === $selected ) {
			$selected = defined( 'RANK_MATH_VERSION' ) ? 'rank_math' : ( defined( 'WPSEO_VERSION' ) ? 'yoast' : 'rank_math' );
		}
		$seo = 'yoast' === $selected
			? array(
				'title'       => get_post_meta( $post_id, '_yoast_wpseo_title', true ),
				'description' => get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ),
				'focus'       => get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ),
				'canonical'   => get_post_meta( $post_id, '_yoast_wpseo_canonical', true ),
			)
			: array(
				'title'       => get_post_meta( $post_id, 'rank_math_title', true ),
				'description' => get_post_meta( $post_id, 'rank_math_description', true ),
				'focus'       => get_post_meta( $post_id, 'rank_math_focus_keyword', true ),
				'canonical'   => get_post_meta( $post_id, 'rank_math_canonical_url', true ),
			);

		return array(
			'id'               => (int) $post_id,
			'title'            => $post->post_title,
			'slug'             => $post->post_name,
			'status'           => $post->post_status,
			'url'              => get_permalink( $post_id ),
			'word_count'       => $this->word_count( wp_strip_all_tags( $html ) ),
			'seo_title'        => $seo['title'],
			'seo_description'  => $seo['description'],
			'focus_keyword'    => $seo['focus'],
			'canonical'        => $seo['canonical'],
			'internal_links'   => array_values( array_unique( $links['internal'] ) ),
			'external_links'   => array_values( array_unique( $links['external'] ) ),
			'headings'         => $this->headings( $html ),
			'schema_types'     => $this->schema_types( get_post_meta( $post_id, '_ikon_seo_schema_graph', true ) ),
			'featured_media_id'=> get_post_thumbnail_id( $post_id ),
			'quality_report'   => get_post_meta( $post_id, '_ikon_seo_quality_report', true ),
		);
	}

	private function check( array &$checks, &$score, $id, $status, $pass_message, $issue_message, $penalty ) {
		$checks[] = array(
			'id'      => sanitize_key( $id ),
			'status'  => $status,
			'message' => 'pass' === $status ? $pass_message : $issue_message,
		);
		if ( 'pass' !== $status ) {
			$score -= absint( $penalty );
		}
	}

	private function links( $html ) {
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$output    = array( 'internal' => array(), 'external' => array() );
		preg_match_all( '/href\s*=\s*["\']([^"\']+)["\']/i', (string) $html, $matches );
		foreach ( $matches[1] ?? array() as $url ) {
			$url = html_entity_decode( trim( $url ) );
			if ( ! $url || 0 === strpos( $url, '#' ) || 0 === strpos( $url, 'mailto:' ) || 0 === strpos( $url, 'tel:' ) ) {
				continue;
			}
			$absolute = 0 === strpos( $url, '/' ) ? home_url( $url ) : $url;
			$host     = strtolower( (string) wp_parse_url( $absolute, PHP_URL_HOST ) );
			$key      = ! $host || $home_host === $host ? 'internal' : 'external';
			$output[ $key ][] = esc_url_raw( $absolute );
		}
		return $output;
	}

	private function headings( $html ) {
		$output = array();
		preg_match_all( '/<h([1-6])\b[^>]*>(.*?)<\/h\1>/is', (string) $html, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$output[] = array(
				'level' => 'h' . $match[1],
				'text'  => trim( wp_strip_all_tags( $match[2] ) ),
			);
		}
		return $output;
	}

	private function word_count( $text ) {
		$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
		return '' === $text ? 0 : count( preg_split( '/\s+/u', $text ) );
	}

	private function length( $text ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $text ) : strlen( (string) $text );
	}

	private function graph_has_type( array $graph, $type ) {
		return in_array( $type, $this->schema_types( $graph ), true );
	}

	private function schema_types( $graph ) {
		$types = array();
		foreach ( is_array( $graph ) ? $graph : array() as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			foreach ( (array) ( $node['@type'] ?? array() ) as $type ) {
				$types[] = sanitize_text_field( $type );
			}
		}
		return array_values( array_unique( $types ) );
	}

	private function looks_high_trust( array $payload ) {
		$signals = strtolower(
			wp_strip_all_tags(
				implode(
					' ',
					array(
						$payload['title'] ?? '',
						$payload['seo']['focus_keyword'] ?? '',
						$payload['schema']['service']['service_type'] ?? '',
					)
				)
			)
		);
		return (bool) preg_match( '/\b(?:tax|vat|accounting|audit|financial|finance|legal|medical|health|payroll|compliance|gratuity)\b/i', $signals );
	}
}
