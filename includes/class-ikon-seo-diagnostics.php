<?php

defined( 'ABSPATH' ) || exit;

/**
 * Evidence-based page diagnostics. It separates direct facts from hypotheses
 * and never claims to know Google's private ranking algorithm.
 */
final class Ikon_SEO_Diagnostics {
	const CACHE_KEY = 'ikon_seo_diagnostics_v5';

	private $crawler;
	private $inventory;
	private $rank_math;
	private $search_console;
	private $search_intelligence;
	private $analytics;
	private $technical;
	private $authority;
	private $strategy;

	public function __construct(
		Ikon_SEO_Crawler $crawler,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Rank_Math $rank_math,
		Ikon_SEO_Search_Console $search_console,
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Analytics $analytics,
		Ikon_SEO_Technical_Intelligence $technical,
		Ikon_SEO_Authority_Intelligence $authority,
		Ikon_SEO_Strategy $strategy
	) {
		$this->crawler        = $crawler;
		$this->inventory      = $inventory;
		$this->rank_math      = $rank_math;
		$this->search_console = $search_console;
		$this->search_intelligence = $search_intelligence;
		$this->analytics      = $analytics;
		$this->technical      = $technical;
		$this->authority      = $authority;
		$this->strategy       = $strategy;
	}

	public function register_hooks() {
		add_action( 'add_meta_boxes_page', array( $this, 'add_meta_box' ) );
		add_action( 'add_meta_boxes_post', array( $this, 'add_meta_box' ) );
	}

	public function add_meta_box() {
		add_meta_box(
			'ikon-seo-page-diagnostics',
			__( 'Ikon SEO: Ranking Evidence', 'ikon-seo' ),
			array( $this, 'render_meta_box' ),
			array( 'page', 'post' ),
			'side',
			'default'
		);
	}

	public function render_meta_box( WP_Post $post ) {
		$report = $this->page_report( $post->ID, false, false );
		if ( is_wp_error( $report ) ) {
			echo '<p>' . esc_html( $report->get_error_message() ) . '</p>';
			return;
		}
		if ( empty( $report['crawled'] ) ) {
			echo '<p>' . esc_html__( 'This page has not been crawled yet. Run an Evidence Crawl from Ikon SEO.', 'ikon-seo' ) . '</p>';
		} else {
			echo '<p><strong>' . esc_html( sprintf( __( 'Work priority: %d/100', 'ikon-seo' ), absint( $report['fix_priority'] ) ) ) . '</strong></p>';
			echo '<p><small>' . esc_html( sprintf( __( 'Ranking priority: %d/100 · Evidence: %s', 'ikon-seo' ), absint( $report['priorities']['ranking'] ?? 0 ), ucfirst( $report['data_sufficiency']['level'] ?? 'limited' ) ) ) . '</small></p>';
			if ( ! empty( $report['primary_finding']['message'] ) ) {
				echo '<p>' . esc_html( $report['primary_finding']['message'] ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'No critical on-page blocker was detected in the stored evidence.', 'ikon-seo' ) . '</p>';
			}
		}
		echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=ikon-seo&tab=diagnostics&post_id=' . absint( $post->ID ) ) ) . '">' . esc_html__( 'Open full diagnosis', 'ikon-seo' ) . '</a></p>';
	}

	public function site_report( $refresh = false, $include_remote = true ) {
		if ( $refresh ) {
			delete_transient( self::CACHE_KEY );
		}
		if ( ! $refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$inventory = $this->inventory->scan( $refresh );
		$records   = $this->crawler->list_records( 1000 );
		$record_map = array();
		foreach ( $records as $record ) {
			$record_map[ absint( $record['post_id'] ) ] = $record;
		}
		$remote = $this->remote_maps( $include_remote, $refresh );
		$reports = array();
		foreach ( (array) ( $inventory['items'] ?? array() ) as $item ) {
			if ( 'publish' !== ( $item['status'] ?? '' ) ) {
				continue;
			}
			$reports[] = $this->build_page_report( $item, $record_map[ absint( $item['id'] ) ] ?? array(), $inventory, $remote );
		}
		usort( $reports, function( $a, $b ) { return $b['fix_priority'] <=> $a['fix_priority']; } );
		$counts = array( 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0 );
		$category_counts = array( 'ranking' => 0, 'search_appearance' => 0, 'opportunity' => 0, 'conversion' => 0, 'measurement' => 0, 'experience' => 0, 'accessibility' => 0 );
		$sufficiency_counts = array( 'strong' => 0, 'good' => 0, 'developing' => 0, 'limited' => 0 );
		$blockers = array();
		foreach ( $reports as $report ) {
			foreach ( $report['blockers'] as $blocker ) {
				$impact = $blocker['impact'];
				$category = $blocker['category'] ?? 'ranking';
				if ( isset( $counts[ $impact ] ) ) {
					$counts[ $impact ]++;
				}
				if ( isset( $category_counts[ $category ] ) ) {
					$category_counts[ $category ]++;
				}
				$blockers[ $blocker['code'] ] = ( $blockers[ $blocker['code'] ] ?? 0 ) + 1;
			}
			$level = $report['data_sufficiency']['level'] ?? 'limited';
			if ( isset( $sufficiency_counts[ $level ] ) ) {
				$sufficiency_counts[ $level ]++;
			}
			$this->store_diagnostics( $report['post_id'], $report );
		}
		arsort( $blockers );
		$result = array(
			'generated_at' => current_time( 'mysql', true ),
			'methodology'  => 'Direct facts are separated from hypotheses and from conversion or measurement issues. Work priority combines likely impact, evidence confidence, strategy-aware business value and implementation effort; it is not a Google ranking score.',
			'data_sources' => array(
				'wordpress'      => true,
				'crawler'        => $this->crawler->status(),
				'rank_math'      => defined( 'RANK_MATH_VERSION' ),
				'search_console' => $remote['gsc_available'],
				'analytics'      => $remote['ga_available'],
				'search_intelligence' => ! empty( $this->search_intelligence->status()['rows'] ),
				'authority_intelligence' => ! empty( $this->authority->status()['backlinks'] ),
				'website_strategy' => array( 'configured' => (bool) $this->strategy->get()['configured'], 'mode' => $this->strategy->get()['mode'] ),
			),
			'summary' => array(
				'pages_diagnosed' => count( $reports ),
				'pages_crawled'   => count( $records ),
				'blockers'        => $counts,
				'top_blockers'    => array_slice( $blockers, 0, 10, true ),
				'categories'      => $category_counts,
				'data_sufficiency'=> $sufficiency_counts,
			),
			'pages' => array_slice( $reports, 0, 500 ),
		);
		set_transient( self::CACHE_KEY, $result, 15 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function page_report( $post_id, $refresh = false, $include_remote = true ) {
		$post_id   = absint( $post_id );
		$inventory = $this->inventory->scan( $refresh );
		$item      = null;
		foreach ( (array) ( $inventory['items'] ?? array() ) as $candidate ) {
			if ( absint( $candidate['id'] ?? 0 ) === $post_id ) {
				$item = $candidate;
				break;
			}
		}
		if ( ! $item ) {
			return new WP_Error( 'ikon_seo_diagnostic_page', __( 'The requested page is not in the current Site Inventory.', 'ikon-seo' ) );
		}
		$remote = $this->remote_maps( $include_remote, $refresh );
		$remote['inspection'] = array();
		if ( $include_remote && $refresh && ! empty( $this->search_console->status()['connected'] ) ) {
			$inspection = $this->search_console->inspect_url( $item['url'] );
			if ( is_array( $inspection ) ) {
				$remote['inspection'] = $inspection;
			}
		}
		$report = $this->build_page_report( $item, $this->crawler->get( $post_id ), $inventory, $remote );
		$this->store_diagnostics( $post_id, $report );
		return $report;
	}

	public function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	private function build_page_report( array $item, array $crawl, array $inventory, array $remote ) {
		$post_id        = absint( $item['id'] ?? 0 );
		$url            = esc_url_raw( $item['url'] ?? '' );
		$gsc            = $this->map_by_url( $remote['gsc_pages'], $url );
		$ga             = $this->map_by_url( $remote['ga_pages'], $url, true );
		$inspection     = (array) ( $remote['inspection'] ?? array() );
		$search_intel   = $this->search_intelligence->page_summary( $url );
		$technical      = $this->technical->page_summary( $url );
		$authority      = $this->authority->page_summary( $url );
		$business_value = $this->business_value( $item, $gsc, $ga );
		$strategy_context = $this->strategy->get();
		$findings       = array();

		$add = function( $code, $impact, $confidence, $evidence_type, $category, $root_cause, $message, $evidence, $action, $effort = 3, $actionability = 'review', $scope = 'page' ) use ( &$findings, $business_value ) {
			$impact       = sanitize_key( $impact );
			$confidence   = sanitize_key( $confidence );
			$category     = sanitize_key( $category );
			$effort       = max( 1, min( 5, absint( $effort ) ) );
			$priority     = $this->finding_priority( $impact, $confidence, $category, $business_value['score'], $effort );
			$findings[]   = array(
				'code'               => sanitize_key( $code ),
				'root_cause'         => sanitize_key( $root_cause ),
				'category'           => $category,
				'scope'              => sanitize_key( $scope ),
				'impact'             => $impact,
				'confidence'         => $confidence,
				'evidence_type'      => sanitize_key( $evidence_type ),
				'message'            => sanitize_text_field( $message ),
				'evidence'           => sanitize_text_field( $evidence ),
				'evidence_items'     => array( sanitize_text_field( $evidence ) ),
				'recommended_action' => sanitize_text_field( $action ),
				'effort'             => $effort,
				'actionability'      => sanitize_key( $actionability ),
				'priority_score'     => $priority,
			);
		};

		if ( $crawl ) {
			if ( 200 !== absint( $crawl['status_code'] ?? 0 ) ) {
				$add( 'http_status', 'critical', 'high', 'direct', 'ranking', 'http_response', 'The page is not returning a normal 200 response.', 'Crawler HTTP status: ' . absint( $crawl['status_code'] ?? 0 ), 'Repair the URL response, redirect, server rule or broken page before content work.', 2, 'high' );
			}
			if ( empty( $crawl['indexable'] ) ) {
				$add( 'not_indexable', 'critical', 'high', 'direct', 'ranking', 'indexability', 'The rendered page is not currently indexable by the crawler rules.', 'Robots: ' . ( $crawl['robots'] ?: 'none' ) . '; canonical: ' . ( $crawl['canonical'] ?: 'self/absent' ), 'Review noindex and canonical directives, then confirm the intended indexed URL.', 2, 'high' );
			}

			$stored_title   = trim( (string) ( $item['seo_title'] ?? '' ) );
			$rendered_title = trim( (string) ( $crawl['rendered_title'] ?? '' ) );
			if ( ! $rendered_title ) {
				$add( 'title_not_rendered', 'high', 'high', 'direct', 'ranking', 'title_output', 'The rendered HTML has no title element.', $stored_title ? 'SEO metadata exists but the crawler found no rendered title element.' : 'No stored SEO title and no rendered title element were found.', 'Review the SEO plugin and theme output, then add a unique title aligned with the page purpose.', 2, 'high' );
			} elseif ( ! $stored_title ) {
				$add( 'stored_title_missing', 'medium', 'high', 'direct', 'search_appearance', 'title_management', 'No custom SEO title is stored, although the page still renders a title.', 'The rendered title appears to be generated from WordPress or the active SEO template.', 'Review whether the generated title is unique, concise and aligned with the intended query before adding an override.', 2, 'medium' );
			}

			$stored_description   = trim( (string) ( $item['seo_description'] ?? '' ) );
			$rendered_description = trim( (string) ( $crawl['rendered_description'] ?? '' ) );
			if ( ! $rendered_description ) {
				$add( 'description_not_rendered', 'medium', 'high', 'direct', 'search_appearance', 'description_output', 'The rendered HTML has no meta-description proposal.', $stored_description ? 'SEO description metadata exists but did not render in the crawled HTML.' : 'No stored or rendered meta description was found.', 'Add or repair an accurate snippet proposal. Search engines may still choose visible page text instead.', 2, 'medium' );
			}

			if ( 0 === absint( $crawl['h1_count'] ?? 0 ) ) {
				$add( 'missing_h1', 'medium', 'high', 'direct', 'ranking', 'main_heading', 'The rendered page has no clear H1 heading.', 'Crawler H1 count: 0.', 'Add a clear page-level heading that communicates the main purpose. Multiple H1 elements are not treated as an automatic failure.', 2, 'high' );
			}
			if ( absint( $crawl['response_ms'] ?? 0 ) > 2500 ) {
				$add( 'slow_origin_response', 'medium', 'medium', 'direct', 'experience', 'origin_performance', 'The server response was slow during the evidence crawl.', 'Observed response: ' . absint( $crawl['response_ms'] ) . ' ms.', 'Check hosting, caching and heavy server-side work; validate with field and laboratory performance data before deciding priority.', 3, 'medium' );
			}
			if ( absint( $crawl['missing_alt'] ?? 0 ) > 0 ) {
				$add( 'missing_image_alt', 'low', 'high', 'direct', 'accessibility', 'image_alternatives', 'Some rendered images have empty alternative text.', absint( $crawl['missing_alt'] ) . ' of ' . absint( $crawl['image_count'] ) . ' images are missing ALT text.', 'Review informative images and add concise contextual ALT text; leave decorative images empty.', 2, 'high' );
			}
		}


		if ( ! empty( $technical['available'] ) ) {
			$url_evidence = (array) ( $technical['url'] ?? array() );
			$performance  = (array) ( $technical['pagespeed'] ?? array() );
			if ( ! empty( $url_evidence['post_id'] ) && false === strpos( (string) ( $url_evidence['source_flags'] ?? '' ), 'sitemap' ) ) {
				$add( 'sitemap_gap', 'medium', 'high', 'direct', 'measurement', 'sitemap_membership', 'The published page was not found in the discovered XML sitemaps.', 'URL sources: ' . ( $url_evidence['source_flags'] ?: 'none' ) . '.', 'Confirm the intended indexability and include the canonical page in the active sitemap when appropriate.', 2, 'high' );
			}
			if ( isset( $url_evidence['crawl_depth'] ) && intval( $url_evidence['crawl_depth'] ) < 0 ) {
				$add( 'link_graph_orphan', 'high', 'high', 'direct', 'ranking', 'internal_discovery', 'The page is not reachable from the homepage in the stored internal-link graph.', 'Stored crawl depth: not reachable; inbound links: ' . absint( $url_evidence['inbound_links'] ?? 0 ) . '.', 'Add relevant followed links from reachable topical pages and verify the page belongs in the site architecture.', 3, 'high' );
			} elseif ( intval( $url_evidence['crawl_depth'] ?? 0 ) > 3 ) {
				$add( 'deep_link_depth', 'medium', 'high', 'direct', 'opportunity', 'internal_discovery', 'The page is more than three followed-link steps from the homepage in the stored graph.', 'Stored crawl depth: ' . intval( $url_evidence['crawl_depth'] ) . '.', 'Review navigation and contextual hub links so important pages are easier to discover without flattening the entire site.', 3, 'review' );
			}
			if ( ! empty( $url_evidence['redirect_target'] ) ) {
				$add( 'published_url_redirects', 'high', 'high', 'direct', 'ranking', 'http_response', 'The stored page URL currently returns a redirect.', 'Redirect target: ' . $url_evidence['redirect_target'] . '.', 'Confirm the correct canonical destination, update internal links and remove the obsolete URL from the sitemap where appropriate.', 2, 'high' );
			}
			if ( ! empty( $performance['field_data_available'] ) && ( floatval( $performance['field_lcp_ms'] ?? 0 ) > 2500 || floatval( $performance['field_inp_ms'] ?? 0 ) > 200 || floatval( $performance['field_cls'] ?? 0 ) > 0.1 ) ) {
				$add( 'field_web_vitals_need_work', 'high', 'high', 'direct', 'experience', 'real_user_performance', 'Real-user Core Web Vitals evidence exceeds one or more recommended thresholds.', sprintf( 'Field LCP %.0f ms, INP %.0f ms, CLS %.3f.', $performance['field_lcp_ms'] ?? 0, $performance['field_inp_ms'] ?? 0, $performance['field_cls'] ?? 0 ), 'Investigate the affected template and field metric, then validate the fix across representative pages.', 4, 'review', 'template' );
			} elseif ( ! empty( $performance ) && absint( $performance['performance_score'] ?? 0 ) > 0 && absint( $performance['performance_score'] ?? 0 ) < 50 ) {
				$add( 'weak_lighthouse_performance', 'medium', 'medium', 'direct', 'experience', 'lab_performance', 'The latest stored Lighthouse performance score is weak.', 'Performance score: ' . absint( $performance['performance_score'] ) . '/100 for ' . sanitize_key( $performance['strategy'] ?? 'mobile' ) . '.', 'Review the stored performance opportunities and confirm the problem with field data before prioritising broad template changes.', 4, 'review', 'template' );
			}
		}

		if ( ! empty( $item['orphan'] ) ) {
			$add( 'orphan_page', 'high', 'high', 'direct', 'ranking', 'internal_discovery', 'No incoming internal content link was found for this page.', 'Site Inventory incoming internal links: 0.', 'Add relevant contextual links from established pages and include the page in the correct navigation or topical hub.', 3, 'high' );
		}
		if ( ! empty( $item['unresolved_internal_urls'] ) ) {
			$add( 'broken_internal_links', 'high', 'high', 'direct', 'ranking', 'broken_internal_destinations', 'The page contains internal URLs that do not map to a known WordPress page.', count( $item['unresolved_internal_urls'] ) . ' unresolved internal URLs.', 'Repair or redirect broken internal destinations.', 2, 'high' );
		}

		$word_count = $crawl ? absint( $crawl['word_count'] ?? 0 ) : absint( $item['word_count'] ?? 0 );
		if ( $word_count > 0 && $word_count < 40 ) {
			$add( 'very_limited_visible_content', 'high', 'high', 'direct', 'ranking', 'main_content_availability', 'The crawler found very little visible main text, which may indicate an incomplete page or inaccessible rendered content.', 'Observed visible word count: ' . $word_count . '. This is an availability signal, not a preferred SEO word count.', 'Confirm that the main content renders correctly and satisfies the user task. Add content only where it improves completeness or usefulness.', 4, 'medium' );
		} elseif ( 0 === $word_count && $crawl ) {
			$add( 'no_visible_content', 'critical', 'high', 'direct', 'ranking', 'main_content_availability', 'The crawler found no meaningful visible main text.', 'Observed visible word count: 0.', 'Check rendering, template output and page content before any optimization work.', 3, 'high' );
		}

		$stored_topic = trim( (string) ( $item['focus_keyword'] ?? '' ) );
		if ( $stored_topic && $crawl ) {
			$h1_text   = implode( ' ', (array) ( $crawl['evidence']['h1_text'] ?? array() ) );
			$alignment = $this->semantic_overlap( $stored_topic, trim( (string) ( $crawl['rendered_title'] ?? '' ) . ' ' . $h1_text . ' ' . (string) ( $item['title'] ?? '' ) ) );
			if ( $alignment < 0.34 ) {
				$add( 'weak_topic_alignment', 'medium', 'medium', 'inferred', 'opportunity', 'topic_alignment', 'The stored target topic has weak semantic overlap with the rendered title and main headings.', 'Semantic topic coverage: ' . round( $alignment * 100 ) . '%. This is a language signal, not proof of intent mismatch.', 'Review the target query and dominant search intent, then improve title and heading clarity without forcing exact-match wording.', 3, 'review' );
			}
		}

		foreach ( (array) ( $inventory['cannibalization'] ?? array() ) as $keyword => $group ) {
			$ids = wp_list_pluck( $group, 'id' );
			if ( in_array( $post_id, array_map( 'absint', $ids ), true ) ) {
				$add( 'stored_topic_overlap', 'medium', 'low', 'inferred', 'opportunity', 'potential_cannibalization', 'Multiple internal pages share the same stored focus topic.', 'Shared stored focus topic: ' . $keyword . '; pages: ' . count( $group ) . '. This alone does not prove cannibalisation.', 'Compare page-query overlap, intent, semantic similarity and ranking movement before differentiating, consolidating or redirecting.', 4, 'review' );
			}
		}

		if ( $gsc ) {
			if ( (float) $gsc['impressions'] >= 100 && (float) $gsc['position'] <= 10 && (float) $gsc['ctr'] < 0.01 ) {
				$add( 'low_serp_ctr', 'high', 'medium', 'inferred', 'search_appearance', 'serp_click_through', 'The page earns visible impressions but an unusually low click-through rate.', sprintf( '%.0f impressions, %.2f%% CTR, average position %.1f.', $gsc['impressions'], $gsc['ctr'] * 100, $gsc['position'] ), 'Review title-link clarity, snippet promise, SERP features and whether the page matches the query wording.', 3, 'medium' );
			}
			if ( (float) $gsc['impressions'] >= 50 && (float) $gsc['position'] > 7 && (float) $gsc['position'] <= 20 ) {
				$add( 'striking_distance', 'high', 'medium', 'inferred', 'opportunity', 'ranking_opportunity', 'The page is receiving demand near the first page and may be a strong improvement candidate.', sprintf( 'Average position %.1f from %.0f impressions.', $gsc['position'], $gsc['impressions'] ), 'Inspect its leading queries, intent match, differentiation, internal-link support and authority requirements.', 4, 'review' );
			}
			if ( null !== ( $gsc['clicks_change'] ?? null ) && (float) $gsc['clicks_change'] <= -30 && (float) $gsc['previous_clicks'] >= 5 ) {
				$add( 'organic_click_decline', 'high', 'medium', 'inferred', 'ranking', 'organic_performance_decline', 'Organic clicks declined materially versus the previous period.', 'Clicks changed by ' . $gsc['clicks_change'] . '%.', 'Check query-level losses, position changes, seasonality, SERP changes, technical changes and content freshness before rewriting.', 4, 'review' );
			}
		}


		if ( $search_intel ) {
			if ( ! empty( $search_intel['cannibalisation'] ) ) {
				$signal = (array) $search_intel['cannibalisation'][0];
				$add( 'query_page_overlap', 'high', sanitize_key( $signal['confidence'] ?? 'medium' ), 'inferred', 'ranking', 'query_page_overlap', 'Search Console shows meaningful query visibility shared with another internal URL.', sanitize_text_field( $signal['evidence'] ?? 'Multiple internal URLs receive impressions for the same query.' ), 'Review both pages against the same current search intent. Reposition, consolidate or change internal linking only when they serve the same intent.', 4, 'review' );
			}
			if ( ! empty( $search_intel['content_decay'] ) ) {
				$decay = (array) $search_intel['content_decay'];
				$add( 'query_level_content_decay', 'high', sanitize_key( $decay['confidence'] ?? 'medium' ), 'inferred', 'ranking', 'organic_performance_decline', 'Stored page-query evidence shows a material period-over-period loss in organic visibility.', sprintf( 'Impressions changed by %s%% (%s to %s).', $decay['impressions_change'] ?? 0, $decay['previous_impressions'] ?? 0, $decay['current_impressions'] ?? 0 ), 'Review lost queries, indexing, SERP changes, freshness, competing internal pages and authority before changing the page.', 4, 'review' );
			}
			if ( ! empty( $search_intel['striking_distance'] ) ) {
				$opportunity = (array) $search_intel['striking_distance'][0];
				$add( 'query_level_striking_distance', 'high', 'medium', 'inferred', 'opportunity', 'ranking_opportunity', 'One or more stored queries are close enough to page one to justify focused improvement research.', sprintf( 'Leading opportunity: “%s” at position %.1f from %.0f impressions.', $opportunity['query'] ?? '', $opportunity['position'] ?? 0, $opportunity['impressions'] ?? 0 ), 'Compare the current SERP and search intent, then improve relevance, differentiated evidence and contextual internal-link support.', 3, 'review' );
			}
		}

		if ( $ga ) {
			if ( (float) $ga['sessions'] >= 20 && (float) $ga['engagement_rate'] < 0.40 ) {
				$add( 'low_engagement', 'medium', 'low', 'inferred', 'conversion', 'landing_page_engagement', 'On-site engagement is low enough to warrant a user-experience review.', sprintf( '%.0f sessions and %.1f%% engagement rate.', $ga['sessions'], $ga['engagement_rate'] * 100 ), 'Review intent alignment, above-the-fold clarity, mobile usability, speed and CTA relevance. Do not treat engagement as a confirmed ranking factor.', 4, 'review' );
			}
			if ( (float) $ga['sessions'] >= 30 && (float) $ga['key_events'] <= 0 ) {
				$add( 'no_key_events', 'medium', 'low', 'inferred', 'measurement', 'conversion_measurement', 'Analytics recorded traffic but no key events for this landing page.', sprintf( '%.0f sessions and %.0f key events.', $ga['sessions'], $ga['key_events'] ), 'First verify key-event tracking and attribution, then review CTA clarity and conversion paths.', 3, 'review' );
			}
		}

		if ( $inspection ) {
			if ( 'pass' !== strtolower( (string) ( $inspection['verdict'] ?? '' ) ) ) {
				$add( 'google_index_verdict', 'critical', 'high', 'direct', 'ranking', 'google_indexing', 'Google Search Console does not report a passing indexed verdict for this URL.', 'Coverage: ' . ( $inspection['coverage_state'] ?? 'unknown' ) . '; verdict: ' . ( $inspection['verdict'] ?? 'unknown' ) . '.', 'Resolve the reported indexing or fetch state, then re-inspect the URL after Google recrawls it.', 3, 'high' );
			}
			if ( ! empty( $inspection['google_canonical'] ) && $this->url_key( $inspection['google_canonical'] ) !== $this->url_key( $url ) ) {
				$add( 'google_canonical_mismatch', 'critical', 'high', 'direct', 'ranking', 'canonical_selection', 'Google selected a different canonical URL.', 'Google canonical: ' . $inspection['google_canonical'] . '.', 'Review duplicate content, redirects, internal links, sitemap entries and canonical consistency.', 3, 'high' );
			}
		}

		$robots_snapshot = (array) ( $remote['crawl_status']['robots'] ?? array() );
		if ( ! empty( $robots_snapshot['blocks_all'] ) ) {
			$add( 'robots_blocks_site', 'critical', 'high', 'direct', 'ranking', 'sitewide_robots', 'The stored robots.txt snapshot appears to block the entire site.', 'User-agent * contains Disallow: /.', 'Correct robots.txt immediately and verify with Search Console.', 2, 'high', 'sitewide' );
		}
		$sitemap_snapshot = (array) ( $remote['crawl_status']['sitemap'] ?? array() );
		if ( $sitemap_snapshot && 200 !== absint( $sitemap_snapshot['status'] ?? 0 ) ) {
			$add( 'sitemap_unavailable', 'medium', 'high', 'direct', 'measurement', 'sitemap_availability', 'The expected sitemap index did not return HTTP 200 during the crawl.', 'Sitemap HTTP status: ' . absint( $sitemap_snapshot['status'] ?? 0 ) . '.', 'Confirm the active sitemap URL and Rank Math sitemap configuration.', 2, 'high', 'sitewide' );
		}

		$findings = $this->deduplicate_findings( $findings );
		usort( $findings, function( $a, $b ) {
			if ( (int) $a['priority_score'] === (int) $b['priority_score'] ) {
				return $this->weight( $b['impact'] ) <=> $this->weight( $a['impact'] );
			}
			return (int) $b['priority_score'] <=> (int) $a['priority_score'];
		} );

		$ranking     = $this->findings_by_category( $findings, array( 'ranking' ) );
		$search      = $this->findings_by_category( $findings, array( 'search_appearance', 'opportunity' ) );
		$conversion  = $this->findings_by_category( $findings, array( 'conversion' ) );
		$measurement = $this->findings_by_category( $findings, array( 'measurement' ) );
		$other       = $this->findings_by_category( $findings, array( 'experience', 'accessibility' ) );
		$priorities  = array(
			'ranking'          => $this->aggregate_priority( $ranking ),
			'search_growth'    => $this->aggregate_priority( $search ),
			'conversion'       => $this->aggregate_priority( $conversion ),
			'measurement'      => $this->aggregate_priority( $measurement ),
			'experience_other' => $this->aggregate_priority( $other ),
		);
		$fix_priority = max( $priorities );
		if ( ! empty( $authority['available'] ) ) {
			if ( absint( $authority['lost_links'] ?? 0 ) > 0 ) {
				$add( 'lost_backlink_evidence', 'high', 'medium', 'direct', 'opportunity', 'offsite_recovery', 'Imported off-site evidence includes lost links to this page.', sprintf( '%d imported link records are marked as lost.', absint( $authority['lost_links'] ) ), 'Verify whether the source links are genuinely lost, then recover the original target or request an editorial update where appropriate.', 3, 'review' );
			}
			if ( 0 === absint( $authority['domains'] ?? 0 ) && ! empty( $gsc ) && (float) ( $gsc['position'] ?? 0 ) >= 8 && (float) ( $gsc['position'] ?? 0 ) <= 30 && (float) ( $gsc['impressions'] ?? 0 ) >= 100 ) {
				$add( 'limited_imported_authority', 'medium', 'low', 'inferred', 'opportunity', 'offsite_support', 'The current imported dataset contains no referring domain for a page already receiving organic demand.', sprintf( 'Search Console shows %.0f impressions at average position %.1f; the imported backlink dataset contains zero referring domains to this URL.', (float) $gsc['impressions'], (float) $gsc['position'] ), 'First confirm content quality, intent alignment and internal links. Then research relevant editorial, partnership or digital-public-relations opportunities rather than pursuing link counts.', 5, 'review' );
			}
		}

		$sufficiency  = $this->data_sufficiency( ! empty( $crawl ), ! empty( $gsc ), ! empty( $ga ), ! empty( $inspection ), ! empty( $search_intel ), ! empty( $technical['available'] ), ! empty( $authority['available'] ) );

		$report = array(
			'post_id'              => $post_id,
			'title'                => sanitize_text_field( $item['title'] ?? '' ),
			'url'                  => $url,
			'post_type'            => sanitize_key( $item['post_type'] ?? '' ),
			'crawled'              => ! empty( $crawl ),
			'crawled_at'           => sanitize_text_field( $crawl['crawled_at'] ?? '' ),
			'fix_priority'         => $fix_priority,
			'priorities'           => $priorities,
			'business_value'       => $business_value,
			'website_strategy'     => array( 'mode' => $strategy_context['mode'], 'primary_goal' => $strategy_context['primary_goal'], 'readiness' => absint( $strategy_context['readiness']['score'] ?? 0 ) ),
			'data_sufficiency'     => $sufficiency,
			'primary_blocker'      => $ranking[0] ?? array(),
			'primary_finding'      => $findings[0] ?? array(),
			'blockers'             => $findings,
			'ranking_blockers'     => $ranking,
			'search_opportunities' => $search,
			'conversion_issues'    => $conversion,
			'measurement_issues'   => $measurement,
			'other_findings'       => $other,
			'evidence'             => array( 'crawl' => $crawl, 'inventory' => $item, 'search_console' => $gsc, 'search_intelligence' => $search_intel, 'technical_intelligence' => $technical, 'authority_intelligence' => $authority, 'analytics' => $ga, 'url_inspection' => $inspection ),
			'limitations'          => array_values( array_filter( array(
				$remote['gsc_available'] ? '' : 'Search Console data is not connected or not available for this report.',
				$remote['ga_available'] ? '' : 'Google Analytics data is not connected or not available for this report.',
				empty( $search_intel ) ? 'The persistent page-query database has not been refreshed or has no row for this URL.' : '',
				empty( $technical['available'] ) ? 'Technical Intelligence has not discovered this URL yet.' : '',
				$sufficiency['level'] === 'strong' ? '' : 'The diagnosis is limited by missing evidence sources; review the data-sufficiency section before acting on inferred findings.',
				empty( $authority['available'] ) ? 'Backlink and referring-domain evidence has not been imported for this report.' : '',
				'Rendered JavaScript evidence and current competitor SERPs require separate external research or an approved provider.',
			) ) ),
			'methodology_version'  => '2.2',
			'methodology'          => 'Findings combine crawler, technical graph, PageSpeed, page-query, Search Console, Analytics, imported off-site evidence and inspection evidence while separating ranking, search appearance, conversion and measurement. Work priority combines likely impact, evidence confidence, strategy-aware business value and implementation effort; it is not a Google ranking score.',
		);
		return $report;
	}

	private function finding_priority( $impact, $confidence, $category, $business_value, $effort ) {
		$impact_score = array( 'critical' => 100, 'high' => 72, 'medium' => 42, 'low' => 18 )[ $impact ] ?? 10;
		$confidence_factor = array( 'high' => 1.0, 'medium' => 0.72, 'low' => 0.45 )[ $confidence ] ?? 0.45;
		$category_factor = array(
			'ranking'           => 1.0,
			'search_appearance' => 0.82,
			'opportunity'       => 0.76,
			'experience'        => 0.68,
			'conversion'        => 0.64,
			'measurement'       => 0.58,
			'accessibility'     => 0.42,
		)[ $category ] ?? 0.60;
		$value_factor  = 0.65 + ( max( 1, min( 5, absint( $business_value ) ) ) * 0.07 );
		$effort_factor = array( 1 => 1.15, 2 => 1.05, 3 => 0.95, 4 => 0.85, 5 => 0.75 )[ max( 1, min( 5, absint( $effort ) ) ) ];
		return max( 1, min( 100, (int) round( $impact_score * $confidence_factor * $category_factor * $value_factor * $effort_factor ) ) );
	}

	private function deduplicate_findings( array $findings ) {
		$groups = array();
		foreach ( $findings as $finding ) {
			$key = ( $finding['scope'] ?? 'page' ) . '|' . ( $finding['category'] ?? 'other' ) . '|' . ( $finding['root_cause'] ?? $finding['code'] );
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = $finding;
				continue;
			}
			$current = $groups[ $key ];
			if ( (int) $finding['priority_score'] > (int) $current['priority_score'] ) {
				$finding['evidence_items'] = array_values( array_unique( array_merge( (array) $current['evidence_items'], (array) $finding['evidence_items'] ) ) );
				$groups[ $key ] = $finding;
			} else {
				$current['evidence_items'] = array_values( array_unique( array_merge( (array) $current['evidence_items'], (array) $finding['evidence_items'] ) ) );
				$current['evidence']       = implode( ' ', array_slice( $current['evidence_items'], 0, 3 ) );
				$groups[ $key ]             = $current;
			}
		}
		return array_values( $groups );
	}

	private function findings_by_category( array $findings, array $categories ) {
		return array_values( array_filter( $findings, function( $finding ) use ( $categories ) {
			return in_array( $finding['category'] ?? '', $categories, true );
		} ) );
	}

	private function aggregate_priority( array $findings ) {
		if ( ! $findings ) {
			return 0;
		}
		$scores = array_values( array_map( function( $finding ) { return absint( $finding['priority_score'] ?? 0 ); }, $findings ) );
		rsort( $scores );
		$total = ( $scores[0] ?? 0 ) + 0.30 * ( $scores[1] ?? 0 ) + 0.15 * ( $scores[2] ?? 0 );
		return max( 0, min( 100, (int) round( $total ) ) );
	}

	private function data_sufficiency( $has_crawl, $has_gsc, $has_ga, $has_inspection, $has_search_intelligence = false, $has_technical = false, $has_authority = false ) {
		$score   = 10;
		$present = array( 'wordpress_inventory' );
		$missing = array();
		if ( $has_crawl ) {
			$score += 20;
			$present[] = 'same_site_crawl';
		} else {
			$missing[] = 'same_site_crawl';
		}
		if ( $has_gsc ) {
			$score += 20;
			$present[] = 'search_console';
		} else {
			$missing[] = 'search_console';
		}
		if ( $has_search_intelligence ) {
			$score += 15;
			$present[] = 'page_query_database';
		} else {
			$missing[] = 'page_query_database';
		}
		if ( $has_technical ) {
			$score += 15;
			$present[] = 'technical_intelligence';
		} else {
			$missing[] = 'technical_intelligence';
		}
		if ( $has_authority ) {
			$score += 10;
			$present[] = 'authority_intelligence';
		} else {
			$missing[] = 'authority_intelligence';
		}
		if ( $has_ga ) {
			$score += 10;
			$present[] = 'analytics';
		} else {
			$missing[] = 'analytics';
		}
		if ( $has_inspection ) {
			$score += 10;
			$present[] = 'url_inspection';
		} else {
			$missing[] = 'url_inspection';
		}
		$level = $score >= 80 ? 'strong' : ( $score >= 60 ? 'good' : ( $score >= 35 ? 'developing' : 'limited' ) );
		return array( 'score' => min( 100, $score ), 'level' => $level, 'present' => $present, 'missing' => $missing );
	}

	private function semantic_overlap( $target, $candidate ) {
		$target_tokens    = $this->semantic_tokens( $target );
		$candidate_tokens = $this->semantic_tokens( $candidate );
		if ( ! $target_tokens ) {
			return 1.0;
		}
		return count( array_intersect( $target_tokens, $candidate_tokens ) ) / max( 1, count( $target_tokens ) );
	}

	private function semantic_tokens( $text ) {
		$text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $text ) ) );
		$raw  = preg_split( '/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		$stop = array_fill_keys( array( 'the', 'and', 'for', 'with', 'from', 'that', 'this', 'your', 'our', 'you', 'are', 'was', 'were', 'have', 'has', 'had', 'into', 'near', 'best', 'top', 'how', 'what', 'why', 'who', 'where', 'when', 'service', 'services' ), true );
		$tokens = array();
		foreach ( $raw as $token ) {
			if ( isset( $stop[ $token ] ) || strlen( $token ) < 3 ) {
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

	private function business_value( array $item, array $gsc, array $ga ) {
		$strategy_value = $this->strategy->page_value( $item );
		$score   = absint( $strategy_value['score'] ?? 3 );
		$reasons = (array) ( $strategy_value['reasons'] ?? array() );
		if ( absint( get_option( 'page_on_front' ) ) === absint( $item['id'] ?? 0 ) ) {
			$score = 5;
			$reasons[] = 'Website homepage';
		}
		if ( $gsc && (float) ( $gsc['impressions'] ?? 0 ) >= 500 ) {
			$score++;
			$reasons[] = 'Meaningful organic demand';
		}
		if ( $ga && (float) ( $ga['key_events'] ?? 0 ) > 0 ) {
			$score++;
			$reasons[] = 'Recorded business event contribution';
		}
		$score = max( 1, min( 5, $score ) );
		return array( 'score' => $score, 'level' => array( 1 => 'low', 2 => 'moderate', 3 => 'important', 4 => 'high', 5 => 'critical' )[ $score ], 'reasons' => array_values( array_unique( $reasons ) ) );
	}

	private function remote_maps( $include_remote, $refresh ) {
		$result = array( 'gsc_available' => false, 'ga_available' => false, 'gsc_pages' => array(), 'ga_pages' => array(), 'inspection' => array(), 'crawl_status' => $this->crawler->status() );
		if ( ! $include_remote ) {
			return $result;
		}
		$gsc_status = $this->search_console->status();
		if ( ! empty( $gsc_status['connected'] ) && ! empty( $gsc_status['property'] ) ) {
			$gsc = $this->search_console->performance( 28, $refresh );
			if ( is_array( $gsc ) ) {
				$result['gsc_available'] = true;
				$result['gsc_pages'] = (array) ( $gsc['top_pages'] ?? array() );
			}
		}
		$ga_status = $this->analytics->status();
		if ( ! empty( $ga_status['connected'] ) && ! empty( $ga_status['property'] ) ) {
			$ga = $this->analytics->report( 28, $refresh );
			if ( is_array( $ga ) ) {
				$result['ga_available'] = true;
				$result['ga_pages'] = (array) ( $ga['top_pages'] ?? array() );
			}
		}
		return $result;
	}

	private function map_by_url( array $rows, $url, $analytics = false ) {
		$target = $this->url_key( $url );
		foreach ( $rows as $row ) {
			$candidate = $analytics ? ( $row['url'] ?? '' ) : ( $row['key'] ?? '' );
			if ( $candidate && $target === $this->url_key( $candidate ) ) {
				return $row;
			}
		}
		return array();
	}

	private function url_key( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return $host . untrailingslashit( '/' . ltrim( $path, '/' ) );
	}

	private function store_diagnostics( $post_id, array $report ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_evidence';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
			return;
		}
		$wpdb->update(
			$table,
			array( 'diagnostics_json' => wp_json_encode( $report ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'post_id' => absint( $post_id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	private function weight( $impact ) {
		return array( 'critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1 )[ $impact ] ?? 0;
	}
}
