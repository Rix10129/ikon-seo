<?php

defined( 'ABSPATH' ) || exit;

/**
 * Draft-readiness checks for structured page payloads.
 *
 * These checks protect data quality and publishing safety. They are not a
 * ranking score and intentionally avoid fixed word-count or character-count
 * targets that could encourage filler content.
 */
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
		$h1_count    = preg_match_all( '/<h1\b[^>]*>/i', $html, $unused );
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
			20,
			'publishing_safety'
		);

		$language = sanitize_text_field( $payload['language'] ?? $profile['default_language'] );
		$this->check(
			$checks,
			$score,
			'profile_language',
			in_array( $language, $profile['supported_languages'], true ) ? 'pass' : 'fail',
			'The page language is enabled in the Website Profile.',
			'The page language is not enabled in the Website Profile.',
			12,
			'publishing_safety'
		);

		$this->check( $checks, $score, 'page_title', $title ? 'pass' : 'fail', 'Page title is present.', 'A page title is required.', 20, 'content_structure' );

		$h1_status = $hero_title && $h1_count >= 1 ? 'pass' : 'fail';
		$h1_issue  = $hero_title ? 'The rendered draft does not contain a clear page-level H1.' : 'A clear page-level heading is required.';
		$this->check(
			$checks,
			$score,
			'page_heading',
			$h1_status,
			'A clear page-level H1 is present. Additional H1 elements are reviewed as hierarchy context rather than an automatic ranking failure.',
			$h1_issue,
			12,
			'content_structure'
		);

		$title_length = $this->length( $seo_title );
		$this->check(
			$checks,
			$score,
			'seo_title',
			$seo_title ? 'pass' : 'fail',
			'An SEO title is present. Its clarity, uniqueness, likely truncation and intent match should be reviewed rather than judged by a fixed character limit.',
			'An SEO title is missing.',
			12,
			'search_appearance'
		);

		$description_length = $this->length( $description );
		$this->check(
			$checks,
			$score,
			'meta_description',
			$description ? 'pass' : 'warning',
			'A meta description proposal is present. Search engines may still generate a different snippet.',
			'No meta description proposal is present. This is a search-appearance opportunity, not a direct ranking failure.',
			4,
			'search_appearance'
		);

		$alignment = $focus ? $this->semantic_overlap( $focus, trim( $seo_title . ' ' . $hero_title . ' ' . $title ) ) : 1.0;
		$this->check(
			$checks,
			$score,
			'topic_alignment',
			! $focus || $alignment >= 0.50 ? 'pass' : 'warning',
			$focus ? 'The target topic is semantically represented in the title and main heading.' : 'A separate focus-keyword field is optional; the page topic can be inferred from its title and headings.',
			'The stored target topic has weak semantic overlap with the title and main heading. Review intent alignment rather than forcing an exact-match phrase.',
			4,
			'intent_alignment'
		);

		$content_status = $word_count <= 0 ? 'fail' : ( $word_count < 40 ? 'warning' : 'pass' );
		$content_issue  = $word_count <= 0
			? 'The rendered draft contains no meaningful visible text.'
			: 'The rendered draft contains very little visible text and may be incomplete. Compare completeness with user intent before adding content.';
		$this->check(
			$checks,
			$score,
			'content_completeness',
			$content_status,
			'Visible content is present. No fixed word-count target is applied.',
			$content_issue,
			$word_count <= 0 ? 15 : 5,
			'content_quality'
		);

		$this->check(
			$checks,
			$score,
			'internal_relationships',
			count( $links['internal'] ) >= 1 ? 'pass' : 'warning',
			'The draft includes at least one contextual relationship to another website page.',
			'No contextual internal link is present in the draft. Review the page’s relationship to relevant hubs, services or supporting articles; no fixed link count is required.',
			4,
			'internal_linking'
		);

		$faq_count  = count( is_array( $payload['faq'] ?? null ) ? $payload['faq'] : array() );
		$faq_schema = $this->graph_has_type( $schema_graph, 'FAQPage' );
		$this->check(
			$checks,
			$score,
			'faq_visibility',
			! $faq_schema || $faq_count > 0 ? 'pass' : 'fail',
			'FAQ schema, when used, matches visible FAQ content.',
			'FAQPage markup must not be emitted without visible FAQ content.',
			15,
			'structured_data'
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
				15,
				'trust'
			);
			$this->check(
				$checks,
				$score,
				'fact_review',
				! empty( $review['reviewed_by'] ) && ! empty( $review['fact_checked_date'] ) ? 'pass' : 'warning',
				'A reviewer and fact-check date are recorded.',
				'Assign a qualified reviewer and fact-check date before publishing.',
				8,
				'trust'
			);
			$this->check(
				$checks,
				$score,
				'applicable_period',
				! empty( $review['jurisdiction'] ) && ! empty( $review['applicable_period'] ) ? 'pass' : 'warning',
				'Jurisdiction and applicable period are recorded.',
				'Add the jurisdiction and applicable tax or regulatory period.',
				5,
				'trust'
			);
			$this->check(
				$checks,
				$score,
				'refresh_schedule',
				! empty( $review['next_review_date'] ) ? 'pass' : 'warning',
				'A next review date is recorded for this high-trust page.',
				'Schedule a future review date for time-sensitive high-trust content.',
				4,
				'trust'
			);
		}

		$schema_types = $this->schema_types( $schema_graph );
		$this->check(
			$checks,
			$score,
			'schema',
			$schema_types ? 'pass' : 'warning',
			'An allow-listed schema graph is available.',
			'No page-specific schema nodes were generated. Schema is optional unless it accurately represents the visible page purpose.',
			3,
			'structured_data'
		);

		$local_report = $this->local->quality( $payload, $rendered );
		if ( 'not_local' !== $local_report['status'] ) {
			foreach ( $local_report['checks'] as $local_check ) {
				$local_check['id']       = 'local_' . sanitize_key( $local_check['id'] );
				$local_check['category'] = 'local_quality';
				$checks[]                = $local_check;
			}
			$score = min( $score, absint( $local_report['score'] ) );
		}

		$score    = max( 0, min( 100, $score ) );
		$failures = count( array_filter( $checks, function( $item ) { return 'fail' === $item['status']; } ) );
		$warnings = count( array_filter( $checks, function( $item ) { return 'warning' === $item['status']; } ) );
		$strategy_settings = Ikon_SEO_Plugin::settings();
		$strategy_threshold = max( 50, min( 100, absint( $strategy_settings['strategy_quality_gate_threshold'] ?? 80 ) ) );
		$strategy_configured = ! empty( $strategy_settings['strategy_configured'] );
		$strategy_failed = $strategy_configured && $score < $strategy_threshold;

		return array(
			'score'               => $score,
			'score_label'         => 'Draft review readiness',
			'methodology_version' => '2.1',
			'methodology'         => 'Safety and completeness checks only. No fixed word-count, title-length, description-length or exact-keyword requirement is used as a ranking score. When the Website Strategy is configured, its draft-readiness threshold is applied as a quality gate.',
			'status'              => ( $failures || $strategy_failed ) ? 'needs_changes' : ( $warnings ? 'review' : 'ready' ),
			'requires_review'     => (bool) ( $failures || $warnings || $ymyl ),
			'metrics'             => array(
				'word_count'             => $word_count,
				'h1_count'               => absint( $h1_count ),
				'seo_title_characters'   => $title_length,
				'description_characters' => $description_length,
				'topic_alignment'        => round( $alignment, 3 ),
				'internal_links'         => count( $links['internal'] ),
				'external_links'         => count( $links['external'] ),
				'faq_count'              => $faq_count,
				'schema_types'           => $schema_types,
				'profile_id'             => $profile['profile_id'],
				'profile_industry'       => $profile['industry'],
				'website_mode'          => sanitize_key( $strategy_settings['website_mode'] ?? 'local_business' ),
				'strategy_quality_gate'  => $strategy_threshold,
				'strategy_gate_passed'   => ! $strategy_failed,
				'local_page'             => 'not_local' !== $local_report['status'],
				'local_quality_score'    => 'not_local' !== $local_report['status'] ? absint( $local_report['score'] ) : null,
				'local_similarity'       => $local_report['metrics']['highest_local_page_similarity'] ?? null,
			),
			'checks'              => $checks,
			'local_report'        => $local_report,
			'generated_at'        => current_time( 'mysql', true ),
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
			'id'                => (int) $post_id,
			'title'             => $post->post_title,
			'slug'              => $post->post_name,
			'status'            => $post->post_status,
			'url'               => get_permalink( $post_id ),
			'word_count'        => $this->word_count( wp_strip_all_tags( $html ) ),
			'seo_title'         => $seo['title'],
			'seo_description'   => $seo['description'],
			'focus_keyword'     => $seo['focus'],
			'canonical'         => $seo['canonical'],
			'internal_links'    => array_values( array_unique( $links['internal'] ) ),
			'external_links'    => array_values( array_unique( $links['external'] ) ),
			'headings'          => $this->headings( $html ),
			'schema_types'      => $this->schema_types( get_post_meta( $post_id, '_ikon_seo_schema_graph', true ) ),
			'featured_media_id' => get_post_thumbnail_id( $post_id ),
			'quality_report'    => get_post_meta( $post_id, '_ikon_seo_quality_report', true ),
		);
	}

	private function check( array &$checks, &$score, $id, $status, $pass_message, $issue_message, $penalty, $category = 'quality' ) {
		$checks[] = array(
			'id'       => sanitize_key( $id ),
			'status'   => $status,
			'category' => sanitize_key( $category ),
			'message'  => 'pass' === $status ? $pass_message : $issue_message,
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

	private function semantic_overlap( $target, $candidate ) {
		$target_tokens    = $this->semantic_tokens( $target );
		$candidate_tokens = $this->semantic_tokens( $candidate );
		if ( ! $target_tokens ) {
			return 1.0;
		}
		$matches = array_intersect( $target_tokens, $candidate_tokens );
		return count( $matches ) / max( 1, count( $target_tokens ) );
	}

	private function semantic_tokens( $text ) {
		$text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $text ) ) );
		$raw  = preg_split( '/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		$stop = array_fill_keys(
			array( 'the', 'and', 'for', 'with', 'from', 'that', 'this', 'your', 'our', 'you', 'are', 'was', 'were', 'have', 'has', 'had', 'into', 'near', 'best', 'top', 'how', 'what', 'why', 'who', 'where', 'when', 'service', 'services' ),
			true
		);
		$tokens = array();
		foreach ( $raw as $token ) {
			if ( isset( $stop[ $token ] ) || strlen( $token ) < 3 ) {
				continue;
			}
			$tokens[] = $this->light_stem( $token );
		}
		return array_values( array_unique( array_filter( $tokens ) ) );
	}

	private function light_stem( $token ) {
		$token = (string) $token;
		if ( strlen( $token ) > 6 && preg_match( '/(?:ing|ers|ies)$/', $token ) ) {
			return preg_replace( '/(?:ing|ers|ies)$/', '', $token );
		}
		if ( strlen( $token ) > 5 && preg_match( '/(?:ed|es)$/', $token ) ) {
			return preg_replace( '/(?:ed|es)$/', '', $token );
		}
		if ( strlen( $token ) > 4 && 's' === substr( $token, -1 ) ) {
			return substr( $token, 0, -1 );
		}
		return $token;
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
