<?php

defined( 'ABSPATH' ) || exit;

/**
 * Competitor and content intelligence.
 *
 * Stores evidence supplied by an approved research workflow or administrator,
 * compares that evidence with WordPress pages, and builds content-gap and
 * topical-coverage reports. The class never scrapes search engines and never
 * treats competitor observations as facts about the connected business.
 */
final class Ikon_SEO_Competitor_Content_Intelligence {
	const CACHE_KEY = 'ikon_seo_competitor_content_report';

	private $profile;
	private $inventory;
	private $search_intelligence;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->profile             = $profile;
		$this->inventory           = $inventory;
		$this->search_intelligence = $search_intelligence;
		$this->history             = $history;
		$this->logger              = $logger;
	}

	public function research_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_competitor_research';
	}

	public function briefs_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_content_briefs';
	}

	public function status() {
		global $wpdb;
		$research_table = $this->research_table();
		$briefs_table   = $this->briefs_table();
		$research_ready = $this->table_exists( $research_table );
		$briefs_ready   = $this->table_exists( $briefs_table );
		$research_count = $research_ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$research_table} WHERE status = 'active'" ) ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query_count    = $research_ready ? absint( $wpdb->get_var( "SELECT COUNT(DISTINCT query_hash) FROM {$research_table} WHERE status = 'active'" ) ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$domain_count   = $research_ready ? absint( $wpdb->get_var( "SELECT COUNT(DISTINCT competitor_domain) FROM {$research_table} WHERE status = 'active'" ) ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$brief_count    = $briefs_ready ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$briefs_table} WHERE status <> 'archived'" ) ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$last_updated   = $research_ready ? sanitize_text_field( $wpdb->get_var( "SELECT MAX(updated_at) FROM {$research_table}" ) ) : ''; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'enabled'        => ! empty( Ikon_SEO_Plugin::settings()['competitor_content_enabled'] ),
			'database_ready' => $research_ready && $briefs_ready,
			'research_items' => $research_count,
			'queries'        => $query_count,
			'domains'        => $domain_count,
			'briefs'         => $brief_count,
			'last_updated'   => $last_updated,
			'web_scraping'   => false,
		);
	}

	/**
	 * Read the current intelligence state and optionally store research or build
	 * a page brief. This mirrors the durable workspace pattern while keeping all
	 * external evidence auditable in WordPress.
	 */
	public function sync( array $payload, $created_by = 0 ) {
		if ( ! $this->table_exists( $this->research_table() ) || ! $this->table_exists( $this->briefs_table() ) ) {
			return new WP_Error( 'ikon_seo_competitor_tables', __( 'Competitor and Content Intelligence tables are not ready. Update or reactivate Ikon SEO.', 'ikon-seo' ) );
		}

		$saved = array();
		if ( ! empty( $payload['research'] ) ) {
			$records = isset( $payload['research'][0] ) ? (array) $payload['research'] : array( $payload['research'] );
			if ( count( $records ) > 50 ) {
				return new WP_Error( 'ikon_seo_competitor_batch', __( 'A maximum of 50 competitor observations can be stored per request.', 'ikon-seo' ) );
			}
			foreach ( $records as $record ) {
				$result = $this->save_research( (array) $record, $created_by );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				$saved[] = $result;
			}
		}

		$analysis = array();
		if ( ! empty( $payload['analyse'] ) ) {
			$request  = (array) $payload['analyse'];
			$post_id  = absint( $request['post_id'] ?? 0 );
			$query    = sanitize_text_field( $request['target_query'] ?? '' );
			$intent   = sanitize_key( $request['intent'] ?? '' );
			$analysis = $this->analyse_page( $post_id, $query, $intent, true, $created_by );
			if ( is_wp_error( $analysis ) ) {
				return $analysis;
			}
		}

		$limit = max( 10, min( 250, absint( $payload['limit'] ?? 100 ) ) );
		return array(
			'saved'   => $saved,
			'analysis'=> $analysis,
			'report'  => $this->report( $limit, true ),
		);
	}

	public function save_research( array $record, $created_by = 0 ) {
		global $wpdb;
		$query = sanitize_text_field( $record['query'] ?? '' );
		$url   = esc_url_raw( $record['url'] ?? '' );
		if ( ! $query || ! $url || ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) {
			return new WP_Error( 'ikon_seo_competitor_required', __( 'Each competitor observation requires a search query and a valid HTTP or HTTPS page URL.', 'ikon-seo' ) );
		}

		$domain = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( ! $domain ) {
			return new WP_Error( 'ikon_seo_competitor_domain', __( 'The competitor URL does not contain a valid domain.', 'ikon-seo' ) );
		}
		$domain = preg_replace( '/^www\./i', '', $domain );
		$intent = $this->sanitize_intent( $record['intent'] ?? $this->infer_intent( $query ) );
		$type   = $this->sanitize_result_type( $record['result_type'] ?? $this->expected_result_type( $intent ) );
		$source = sanitize_key( $record['source'] ?? 'connected_research' );
		if ( ! in_array( $source, array( 'connected_research', 'manual', 'licensed_provider', 'import' ), true ) ) {
			$source = 'connected_research';
		}
		$observed = sanitize_text_field( $record['observed_at'] ?? gmdate( 'Y-m-d' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $observed ) ) {
			$observed = gmdate( 'Y-m-d' );
		}

		$headings        = $this->sanitize_list( $record['headings'] ?? array(), 40, 255 );
		$entities        = $this->sanitize_list( $record['entities'] ?? array(), 60, 120 );
		$topics          = $this->sanitize_list( $record['topics'] ?? array(), 60, 160 );
		$trust_elements  = $this->sanitize_list( $record['trust_elements'] ?? array(), 40, 180 );
		$conversion      = $this->sanitize_list( $record['conversion_elements'] ?? array(), 40, 180 );
		$serp_features   = $this->sanitize_list( $record['search_features'] ?? array(), 30, 120 );
		$record_hash     = hash( 'sha256', $this->normalize_query( $query ) . '|' . $this->url_key( $url ) . '|' . $observed );
		$query_hash      = hash( 'sha256', $this->normalize_query( $query ) );
		$url_hash        = hash( 'sha256', $this->url_key( $url ) );
		$now             = current_time( 'mysql', true );

		$sql = "INSERT INTO {$this->research_table()}
			(record_hash, query_hash, url_hash, query_text, intent, result_type, competitor_domain, competitor_url, page_title, meta_description, h1_text, word_count, headings_json, entities_json, topics_json, trust_elements_json, conversion_elements_json, search_features_json, evidence_notes, differentiation_notes, evidence_source, observed_at, status, created_by, created_at, updated_at)
			VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,'active',%d,%s,%s)
			ON DUPLICATE KEY UPDATE intent=VALUES(intent), result_type=VALUES(result_type), page_title=VALUES(page_title), meta_description=VALUES(meta_description), h1_text=VALUES(h1_text), word_count=VALUES(word_count), headings_json=VALUES(headings_json), entities_json=VALUES(entities_json), topics_json=VALUES(topics_json), trust_elements_json=VALUES(trust_elements_json), conversion_elements_json=VALUES(conversion_elements_json), search_features_json=VALUES(search_features_json), evidence_notes=VALUES(evidence_notes), differentiation_notes=VALUES(differentiation_notes), evidence_source=VALUES(evidence_source), status='active', updated_at=VALUES(updated_at)";
		$result = $wpdb->query(
			$wpdb->prepare(
				$sql,
				$record_hash,
				$query_hash,
				$url_hash,
				$query,
				$intent,
				$type,
				$domain,
				$url,
				sanitize_text_field( $record['title'] ?? '' ),
				sanitize_textarea_field( $record['meta_description'] ?? '' ),
				sanitize_text_field( $record['h1'] ?? '' ),
				max( 0, absint( $record['word_count'] ?? 0 ) ),
				wp_json_encode( $headings ),
				wp_json_encode( $entities ),
				wp_json_encode( $topics ),
				wp_json_encode( $trust_elements ),
				wp_json_encode( $conversion ),
				wp_json_encode( $serp_features ),
				sanitize_textarea_field( $record['evidence_notes'] ?? '' ),
				sanitize_textarea_field( $record['differentiation_notes'] ?? '' ),
				$source,
				$observed,
				absint( $created_by ),
				$now,
				$now
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_competitor_store', __( 'The competitor observation could not be stored.', 'ikon-seo' ) );
		}
		delete_transient( self::CACHE_KEY );
		$this->logger->log( 'competitor_research', 'success', 'Stored competitor content evidence for ' . $query . '.', 0, 0, array( 'domain' => $domain, 'source' => $source ) );

		return array(
			'record_hash' => $record_hash,
			'query'       => $query,
			'url'         => $url,
			'domain'      => $domain,
			'intent'      => $intent,
			'result_type' => $type,
			'observed_at' => $observed,
		);
	}

	public function archive_research( $id ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return false;
		}
		$result = $wpdb->update( $this->research_table(), array( 'status' => 'archived', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id ), array( '%s', '%s' ), array( '%d' ) );
		delete_transient( self::CACHE_KEY );
		return false !== $result;
	}

	public function analyse_page( $post_id, $target_query = '', $intent = '', $store = true, $created_by = 0 ) {
		global $wpdb;
		$post = get_post( absint( $post_id ) );
		if ( ! $post || ! in_array( $post->post_status, array( 'publish', 'draft', 'pending', 'private' ), true ) ) {
			return new WP_Error( 'ikon_seo_content_page', __( 'Choose a valid WordPress page or post to analyse.', 'ikon-seo' ) );
		}
		$target_query = sanitize_text_field( $target_query );
		if ( ! $target_query ) {
			$target_query = sanitize_text_field( get_post_meta( $post->ID, 'rank_math_focus_keyword', true ) );
		}
		if ( false !== strpos( $target_query, ',' ) ) {
			$target_query = trim( strtok( $target_query, ',' ) );
		}
		if ( ! $target_query ) {
			$target_query = sanitize_text_field( $post->post_title );
		}
		$intent = $this->sanitize_intent( $intent ?: $this->infer_intent( $target_query ) );
		$query_hash = hash( 'sha256', $this->normalize_query( $target_query ) );
		$competitors = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->research_table()} WHERE status = 'active' AND query_hash = %s ORDER BY observed_at DESC, id DESC LIMIT 20",
				$query_hash
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( empty( $competitors ) ) {
			$like = '%' . $wpdb->esc_like( $target_query ) . '%';
			$competitors = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->research_table()} WHERE status = 'active' AND query_text LIKE %s ORDER BY observed_at DESC, id DESC LIMIT 20",
					$like
				),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$page_text     = $this->page_text( $post );
		$page_tokens   = $this->token_set( $page_text );
		$page_headings = $this->extract_headings( $post->post_content );
		$page_intent   = $this->infer_page_intent( $post, $page_text );
		$topic_counts  = array();
		$entity_counts = array();
		$trust_counts  = array();
		$conversion_counts = array();
		$result_types = array();

		foreach ( (array) $competitors as $competitor ) {
			$result_types[] = sanitize_key( $competitor['result_type'] ?? '' );
			foreach ( array_merge( $this->decode_list( $competitor['topics_json'] ?? '' ), $this->decode_list( $competitor['headings_json'] ?? '' ) ) as $topic ) {
				$key = $this->normalize_topic( $topic );
				if ( $key ) {
					$topic_counts[ $key ] = isset( $topic_counts[ $key ] ) ? $topic_counts[ $key ] + 1 : 1;
				}
			}
			foreach ( $this->decode_list( $competitor['entities_json'] ?? '' ) as $entity ) {
				$key = $this->normalize_topic( $entity );
				if ( $key ) {
					$entity_counts[ $key ] = isset( $entity_counts[ $key ] ) ? $entity_counts[ $key ] + 1 : 1;
				}
			}
			foreach ( $this->decode_list( $competitor['trust_elements_json'] ?? '' ) as $item ) {
				$key = sanitize_text_field( $item );
				$trust_counts[ $key ] = isset( $trust_counts[ $key ] ) ? $trust_counts[ $key ] + 1 : 1;
			}
			foreach ( $this->decode_list( $competitor['conversion_elements_json'] ?? '' ) as $item ) {
				$key = sanitize_text_field( $item );
				$conversion_counts[ $key ] = isset( $conversion_counts[ $key ] ) ? $conversion_counts[ $key ] + 1 : 1;
			}
		}

		arsort( $topic_counts );
		arsort( $entity_counts );
		arsort( $trust_counts );
		arsort( $conversion_counts );
		$minimum_occurrence = count( $competitors ) >= 4 ? 2 : 1;
		$missing_topics = array();
		$covered_topics = array();
		foreach ( array_slice( $topic_counts, 0, 40, true ) as $topic => $count ) {
			if ( $count < $minimum_occurrence ) {
				continue;
			}
			if ( $this->topic_present( $topic, $page_tokens, $page_text ) ) {
				$covered_topics[] = array( 'topic' => $topic, 'competitors' => $count );
			} else {
				$missing_topics[] = array( 'topic' => $topic, 'competitors' => $count );
			}
		}
		$missing_entities = array();
		foreach ( array_slice( $entity_counts, 0, 30, true ) as $entity => $count ) {
			if ( $count >= $minimum_occurrence && ! $this->topic_present( $entity, $page_tokens, $page_text ) ) {
				$missing_entities[] = array( 'entity' => $entity, 'competitors' => $count );
			}
		}

		$dominant_type = $result_types ? $this->mode( $result_types ) : $this->expected_result_type( $intent );
		$intent_alignment = $this->intent_alignment( $page_intent, $intent, $post->post_type, $dominant_type );
		$total_topics = count( $missing_topics ) + count( $covered_topics );
		$coverage = $total_topics ? round( count( $covered_topics ) / $total_topics * 100 ) : null;
		$confidence = count( $competitors ) >= 5 ? 'high' : ( count( $competitors ) >= 3 ? 'medium' : 'low' );
		$gap_score = null === $coverage ? 0 : max( 0, min( 100, 100 - $coverage ) );
		if ( 'mismatch' === $intent_alignment['status'] ) {
			$gap_score = min( 100, $gap_score + 25 );
		}

		$requirements = array();
		if ( 'mismatch' === $intent_alignment['status'] ) {
			$requirements[] = 'Reposition the page so its format and primary action match the dominant search intent before expanding content.';
		}
		if ( $missing_topics ) {
			$requirements[] = 'Address only the missing subtopics that are genuinely relevant to the service or subject; do not add filler merely to match competitors.';
		}
		if ( $missing_entities ) {
			$requirements[] = 'Verify whether the recurring entities are factually relevant before adding them.';
		}
		if ( $trust_counts ) {
			$requirements[] = 'Review recurring proof and trust patterns, then add only real evidence the business can substantiate.';
		}
		if ( $conversion_counts ) {
			$requirements[] = 'Compare conversion paths and improve the page action only where it serves the visitor and matches the business process.';
		}
		if ( ! $competitors ) {
			$requirements[] = 'Collect current competitor and search-result evidence before making a high-confidence content decision.';
		}

		$result = array(
			'post_id'             => absint( $post->ID ),
			'page_title'          => sanitize_text_field( $post->post_title ),
			'page_url'            => get_permalink( $post ),
			'target_query'        => $target_query,
			'target_intent'       => $intent,
			'page_intent'         => $page_intent,
			'dominant_result_type'=> $dominant_type,
			'intent_alignment'    => $intent_alignment,
			'competitor_count'    => count( $competitors ),
			'evidence_confidence' => $confidence,
			'topic_coverage'      => $coverage,
			'content_gap_priority'=> $gap_score,
			'covered_topics'      => array_slice( $covered_topics, 0, 20 ),
			'missing_topics'      => array_slice( $missing_topics, 0, 20 ),
			'missing_entities'    => array_slice( $missing_entities, 0, 15 ),
			'recurring_trust_patterns' => $this->counts_to_items( $trust_counts, 12 ),
			'recurring_conversion_patterns' => $this->counts_to_items( $conversion_counts, 12 ),
			'current_headings'    => $page_headings,
			'differentiation_requirements' => $requirements,
			'direct_evidence'     => array_values( array_filter( array(
				count( $competitors ) ? sprintf( '%d stored competitor pages were compared for this query.', count( $competitors ) ) : '',
				null !== $coverage ? sprintf( 'The page covers approximately %d%% of recurring stored competitor topics after normalization.', $coverage ) : '',
				$dominant_type ? 'The most common stored result type is ' . str_replace( '_', ' ', $dominant_type ) . '.' : '',
			) ) ),
			'hypotheses'          => array_values( array_filter( array(
				'mismatch' === $intent_alignment['status'] ? 'Search-intent mismatch may be limiting the page even if its technical SEO is sound.' : '',
				$missing_topics ? 'Recurring missing subtopics may reduce perceived completeness, subject to factual relevance and current search-result validation.' : '',
				$missing_entities ? 'Recurring missing entities may indicate a semantic coverage gap, but they must be validated before inclusion.' : '',
			) ) ),
			'limitations'         => array(
				'Competitor observations are supplied by an administrator, an approved research workflow or a licensed provider; Ikon SEO does not scrape Google Search.',
				'Competitor frequency does not prove that a topic or page element causes rankings.',
				'Authority, links, brand strength, location and search personalization may still explain performance differences.',
			),
			'generated_at'        => current_time( 'mysql', true ),
		);

		if ( $store ) {
			$stored = $this->store_brief( $result, $created_by );
			if ( is_wp_error( $stored ) ) {
				return $stored;
			}
			$result['brief_id'] = $stored;
			$this->history->add(
				array(
					'category'        => 'research',
					'status'          => 'open',
					'title'           => 'Content intelligence brief: ' . $post->post_title,
					'summary'         => sprintf( 'Compared %d stored competitor pages for “%s”; content-gap priority %d/100.', count( $competitors ), $target_query, $gap_score ),
					'details'         => array( 'brief_id' => $stored, 'target_query' => $target_query, 'confidence' => $confidence ),
					'related_post_id' => $post->ID,
				),
				'plugin',
				$created_by
			);
		}
		delete_transient( self::CACHE_KEY );
		return $result;
	}

	private function store_brief( array $result, $created_by = 0 ) {
		global $wpdb;
		$post_id    = absint( $result['post_id'] ?? 0 );
		$query      = sanitize_text_field( $result['target_query'] ?? '' );
		$query_hash = hash( 'sha256', $this->normalize_query( $query ) );
		$now        = current_time( 'mysql', true );
		$sql = "INSERT INTO {$this->briefs_table()}
			(post_id, query_hash, page_url, page_title, target_query, target_intent, page_intent, dominant_result_type, intent_alignment, competitor_count, topic_coverage, gap_priority, evidence_confidence, covered_topics_json, missing_topics_json, missing_entities_json, trust_patterns_json, conversion_patterns_json, requirements_json, direct_evidence_json, hypotheses_json, status, created_by, created_at, updated_at)
			VALUES (%d,%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,'open',%d,%s,%s)
			ON DUPLICATE KEY UPDATE page_url=VALUES(page_url), page_title=VALUES(page_title), target_intent=VALUES(target_intent), page_intent=VALUES(page_intent), dominant_result_type=VALUES(dominant_result_type), intent_alignment=VALUES(intent_alignment), competitor_count=VALUES(competitor_count), topic_coverage=VALUES(topic_coverage), gap_priority=VALUES(gap_priority), evidence_confidence=VALUES(evidence_confidence), covered_topics_json=VALUES(covered_topics_json), missing_topics_json=VALUES(missing_topics_json), missing_entities_json=VALUES(missing_entities_json), trust_patterns_json=VALUES(trust_patterns_json), conversion_patterns_json=VALUES(conversion_patterns_json), requirements_json=VALUES(requirements_json), direct_evidence_json=VALUES(direct_evidence_json), hypotheses_json=VALUES(hypotheses_json), status='open', updated_at=VALUES(updated_at)";
		$result_db = $wpdb->query(
			$wpdb->prepare(
				$sql,
				$post_id,
				$query_hash,
				esc_url_raw( $result['page_url'] ?? '' ),
				sanitize_text_field( $result['page_title'] ?? '' ),
				$query,
				sanitize_key( $result['target_intent'] ?? '' ),
				sanitize_key( $result['page_intent'] ?? '' ),
				sanitize_key( $result['dominant_result_type'] ?? '' ),
				sanitize_key( $result['intent_alignment']['status'] ?? '' ),
				absint( $result['competitor_count'] ?? 0 ),
				null === $result['topic_coverage'] ? null : (string) $result['topic_coverage'],
				absint( $result['content_gap_priority'] ?? 0 ),
				sanitize_key( $result['evidence_confidence'] ?? 'low' ),
				wp_json_encode( $result['covered_topics'] ?? array() ),
				wp_json_encode( $result['missing_topics'] ?? array() ),
				wp_json_encode( $result['missing_entities'] ?? array() ),
				wp_json_encode( $result['recurring_trust_patterns'] ?? array() ),
				wp_json_encode( $result['recurring_conversion_patterns'] ?? array() ),
				wp_json_encode( $result['differentiation_requirements'] ?? array() ),
				wp_json_encode( $result['direct_evidence'] ?? array() ),
				wp_json_encode( $result['hypotheses'] ?? array() ),
				absint( $created_by ),
				$now,
				$now
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $result_db ) {
			return new WP_Error( 'ikon_seo_content_brief_store', __( 'The content intelligence brief could not be stored.', 'ikon-seo' ) );
		}
		$id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->briefs_table()} WHERE post_id = %d AND query_hash = %s", $post_id, $query_hash ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $id;
	}

	public function report( $limit = 100, $refresh = false ) {
		global $wpdb;
		$limit = max( 10, min( 250, absint( $limit ) ) );
		if ( ! $refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$status = $this->status();
		if ( empty( $status['database_ready'] ) ) {
			return new WP_Error( 'ikon_seo_competitor_database', __( 'Competitor and Content Intelligence database tables are not ready.', 'ikon-seo' ) );
		}
		$research = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->research_table()} WHERE status = 'active' ORDER BY observed_at DESC, id DESC LIMIT %d", $limit ),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$briefs = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->briefs_table()} WHERE status <> 'archived' ORDER BY gap_priority DESC, updated_at DESC LIMIT %d", $limit ),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$intents = array();
		$domains = array();
		$queries = array();
		foreach ( (array) $research as &$row ) {
			$row = $this->format_research_row( $row );
			$intents[ $row['intent'] ] = isset( $intents[ $row['intent'] ] ) ? $intents[ $row['intent'] ] + 1 : 1;
			$domains[ $row['competitor_domain'] ] = isset( $domains[ $row['competitor_domain'] ] ) ? $domains[ $row['competitor_domain'] ] + 1 : 1;
			$qkey = $this->normalize_query( $row['query_text'] );
			if ( ! isset( $queries[ $qkey ] ) ) {
				$queries[ $qkey ] = array( 'query' => $row['query_text'], 'intent' => $row['intent'], 'pages' => 0, 'domains' => array(), 'features' => array() );
			}
			$queries[ $qkey ]['pages']++;
			$queries[ $qkey ]['domains'][ $row['competitor_domain'] ] = true;
			foreach ( $row['search_features'] as $feature ) {
				$queries[ $qkey ]['features'][ $feature ] = true;
			}
		}
		unset( $row );
		foreach ( $queries as &$query ) {
			$query['domains']  = array_keys( $query['domains'] );
			$query['features'] = array_keys( $query['features'] );
		}
		unset( $query );
		arsort( $domains );
		arsort( $intents );

		$formatted_briefs = array();
		foreach ( (array) $briefs as $brief ) {
			$formatted_briefs[] = $this->format_brief_row( $brief );
		}
		$topic_map = $this->build_topic_map( $formatted_briefs );
		$result = array(
			'status'      => $status,
			'summary'     => array(
				'research_items'   => count( $research ),
				'queries'          => count( $queries ),
				'domains'          => count( $domains ),
				'content_briefs'   => count( $formatted_briefs ),
				'high_priority_gaps'=> count( array_filter( $formatted_briefs, function( $item ) { return absint( $item['gap_priority'] ?? 0 ) >= 70; } ) ),
			),
			'intent_distribution' => $intents,
			'leading_competitors' => array_slice( $domains, 0, 20, true ),
			'query_research'      => array_values( $queries ),
			'research'            => $research,
			'content_briefs'      => $formatted_briefs,
			'topic_map'           => $topic_map,
			'methodology'         => 'Stored competitor observations are compared with WordPress page text and headings. Repeated topics, entities, proof patterns and result types are surfaced as evidence and hypotheses, not copied or treated as ranking factors.',
			'limitations'         => array(
				'No automated Google Search scraping is performed.',
				'Current search-result research should be refreshed before major content or consolidation decisions.',
				'Competitor content can reveal expectations but should not be copied. Recommendations must be factually accurate and differentiated.',
				'Backlinks, brand strength, proximity and personalization require separate evidence.',
			),
			'generated_at'        => current_time( 'mysql', true ),
		);
		set_transient( self::CACHE_KEY, $result, 30 * MINUTE_IN_SECONDS );
		return $result;
	}

	private function build_topic_map( array $briefs ) {
		$nodes = array();
		$search = $this->search_intelligence->report( false, 100 );
		if ( is_array( $search ) ) {
			foreach ( (array) ( $search['clusters'] ?? array() ) as $cluster ) {
				$label = sanitize_text_field( $cluster['cluster_label'] ?? $cluster['cluster_key'] ?? '' );
				if ( ! $label ) {
					continue;
				}
				$key = $this->normalize_topic( $label );
				$nodes[ $key ] = array(
					'topic'       => $label,
					'source'      => 'search_console',
					'impressions' => (float) ( $cluster['impressions'] ?? 0 ),
					'clicks'      => (float) ( $cluster['clicks'] ?? 0 ),
					'page'        => esc_url_raw( $cluster['top_page'] ?? '' ),
					'status'      => empty( $cluster['top_page'] ) ? 'missing_page' : 'covered',
				);
			}
		}
		foreach ( $briefs as $brief ) {
			$key = $this->normalize_topic( $brief['target_query'] ?? '' );
			if ( ! $key ) {
				continue;
			}
			if ( ! isset( $nodes[ $key ] ) ) {
				$nodes[ $key ] = array( 'topic' => $brief['target_query'], 'source' => 'content_brief', 'impressions' => 0, 'clicks' => 0, 'page' => $brief['page_url'], 'status' => 'covered' );
			}
			$nodes[ $key ]['gap_priority'] = absint( $brief['gap_priority'] ?? 0 );
			$nodes[ $key ]['intent'] = sanitize_key( $brief['target_intent'] ?? '' );
			$nodes[ $key ]['confidence'] = sanitize_key( $brief['evidence_confidence'] ?? 'low' );
		}
		usort( $nodes, function( $a, $b ) {
			$ap = absint( $a['gap_priority'] ?? 0 ) + ( empty( $a['page'] ) ? 40 : 0 );
			$bp = absint( $b['gap_priority'] ?? 0 ) + ( empty( $b['page'] ) ? 40 : 0 );
			return $bp <=> $ap;
		} );
		return array_slice( array_values( $nodes ), 0, 150 );
	}

	private function format_research_row( array $row ) {
		return array(
			'id'                  => absint( $row['id'] ?? 0 ),
			'query_text'          => sanitize_text_field( $row['query_text'] ?? '' ),
			'intent'              => sanitize_key( $row['intent'] ?? '' ),
			'result_type'         => sanitize_key( $row['result_type'] ?? '' ),
			'competitor_domain'   => sanitize_text_field( $row['competitor_domain'] ?? '' ),
			'competitor_url'      => esc_url_raw( $row['competitor_url'] ?? '' ),
			'page_title'          => sanitize_text_field( $row['page_title'] ?? '' ),
			'meta_description'    => sanitize_textarea_field( $row['meta_description'] ?? '' ),
			'h1_text'             => sanitize_text_field( $row['h1_text'] ?? '' ),
			'word_count'          => absint( $row['word_count'] ?? 0 ),
			'headings'            => $this->decode_list( $row['headings_json'] ?? '' ),
			'entities'            => $this->decode_list( $row['entities_json'] ?? '' ),
			'topics'              => $this->decode_list( $row['topics_json'] ?? '' ),
			'trust_elements'      => $this->decode_list( $row['trust_elements_json'] ?? '' ),
			'conversion_elements' => $this->decode_list( $row['conversion_elements_json'] ?? '' ),
			'search_features'     => $this->decode_list( $row['search_features_json'] ?? '' ),
			'evidence_notes'      => sanitize_textarea_field( $row['evidence_notes'] ?? '' ),
			'differentiation_notes'=> sanitize_textarea_field( $row['differentiation_notes'] ?? '' ),
			'evidence_source'     => sanitize_key( $row['evidence_source'] ?? '' ),
			'observed_at'         => sanitize_text_field( $row['observed_at'] ?? '' ),
			'updated_at'          => sanitize_text_field( $row['updated_at'] ?? '' ),
		);
	}

	private function format_brief_row( array $row ) {
		return array(
			'id'                   => absint( $row['id'] ?? 0 ),
			'post_id'              => absint( $row['post_id'] ?? 0 ),
			'page_url'             => esc_url_raw( $row['page_url'] ?? '' ),
			'page_title'           => sanitize_text_field( $row['page_title'] ?? '' ),
			'target_query'         => sanitize_text_field( $row['target_query'] ?? '' ),
			'target_intent'        => sanitize_key( $row['target_intent'] ?? '' ),
			'page_intent'          => sanitize_key( $row['page_intent'] ?? '' ),
			'dominant_result_type' => sanitize_key( $row['dominant_result_type'] ?? '' ),
			'intent_alignment'     => sanitize_key( $row['intent_alignment'] ?? '' ),
			'competitor_count'     => absint( $row['competitor_count'] ?? 0 ),
			'topic_coverage'       => '' === (string) ( $row['topic_coverage'] ?? '' ) ? null : (float) $row['topic_coverage'],
			'gap_priority'         => absint( $row['gap_priority'] ?? 0 ),
			'evidence_confidence'  => sanitize_key( $row['evidence_confidence'] ?? 'low' ),
			'covered_topics'       => $this->decode_json( $row['covered_topics_json'] ?? '' ),
			'missing_topics'       => $this->decode_json( $row['missing_topics_json'] ?? '' ),
			'missing_entities'     => $this->decode_json( $row['missing_entities_json'] ?? '' ),
			'trust_patterns'       => $this->decode_json( $row['trust_patterns_json'] ?? '' ),
			'conversion_patterns'  => $this->decode_json( $row['conversion_patterns_json'] ?? '' ),
			'requirements'         => $this->decode_json( $row['requirements_json'] ?? '' ),
			'direct_evidence'      => $this->decode_json( $row['direct_evidence_json'] ?? '' ),
			'hypotheses'           => $this->decode_json( $row['hypotheses_json'] ?? '' ),
			'status'               => sanitize_key( $row['status'] ?? 'open' ),
			'updated_at'           => sanitize_text_field( $row['updated_at'] ?? '' ),
		);
	}

	private function infer_intent( $query ) {
		$query = $this->normalize_query( $query );
		if ( preg_match( '/\b(how|what|why|when|guide|tips|ideas|meaning|can|does|is)\b/', $query ) ) {
			return 'informational';
		}
		if ( preg_match( '/\b(best|top|review|reviews|vs|versus|compare|comparison|alternative)\b/', $query ) ) {
			return 'commercial_investigation';
		}
		if ( preg_match( '/\b(near me|in doha|in dubai|in qatar|service|services|company|companies|cleaner|cleaners|contractor|repair|installation|booking|quote)\b/', $query ) ) {
			return 'local_service';
		}
		if ( preg_match( '/\b(buy|price|cost|order|book|hire|download|subscribe)\b/', $query ) ) {
			return 'transactional';
		}
		return 'mixed';
	}

	private function infer_page_intent( WP_Post $post, $text ) {
		$title = $this->normalize_query( $post->post_title . ' ' . $text );
		if ( 'product' === $post->post_type ) {
			return 'transactional';
		}
		if ( preg_match( '/\b(contact|get a quote|book now|request quote|our services|service areas|call us|whatsapp)\b/', $title ) ) {
			return 'local_service';
		}
		if ( preg_match( '/\b(best|compare|comparison|review|reviews|versus| vs )\b/', $title ) ) {
			return 'commercial_investigation';
		}
		if ( 'post' === $post->post_type || preg_match( '/\b(how|what|why|guide|tips|ideas)\b/', $title ) ) {
			return 'informational';
		}
		return 'mixed';
	}

	private function intent_alignment( $page_intent, $target_intent, $post_type, $dominant_type ) {
		$aligned = $page_intent === $target_intent || 'mixed' === $target_intent || 'mixed' === $page_intent;
		if ( 'local_service' === $target_intent && in_array( $page_intent, array( 'local_service', 'transactional' ), true ) ) {
			$aligned = true;
		}
		if ( 'commercial_investigation' === $target_intent && in_array( $page_intent, array( 'commercial_investigation', 'informational' ), true ) ) {
			$aligned = true;
		}
		if ( 'service_page' === $dominant_type && 'post' === $post_type ) {
			$aligned = false;
		}
		return array(
			'status' => $aligned ? 'aligned' : 'mismatch',
			'evidence' => $aligned
				? 'The inferred page purpose is compatible with the stored target intent and dominant result format.'
				: 'The inferred page purpose or WordPress content type differs from the stored target intent or dominant result format.',
		);
	}

	private function expected_result_type( $intent ) {
		$map = array(
			'informational'           => 'article',
			'commercial_investigation'=> 'comparison_page',
			'local_service'           => 'service_page',
			'transactional'           => 'product_or_booking_page',
			'mixed'                   => 'mixed_results',
		);
		return $map[ $intent ] ?? 'mixed_results';
	}

	private function sanitize_intent( $intent ) {
		$intent = sanitize_key( $intent );
		$allowed = array( 'informational', 'commercial_investigation', 'local_service', 'transactional', 'navigational', 'mixed' );
		return in_array( $intent, $allowed, true ) ? $intent : 'mixed';
	}

	private function sanitize_result_type( $type ) {
		$type = sanitize_key( $type );
		$allowed = array( 'service_page', 'article', 'category_page', 'comparison_page', 'product_or_booking_page', 'location_page', 'homepage', 'tool', 'video', 'mixed_results' );
		return in_array( $type, $allowed, true ) ? $type : 'mixed_results';
	}

	private function page_text( WP_Post $post ) {
		$content = strip_shortcodes( $post->post_content );
		$content = wp_strip_all_tags( $content, true );
		return trim( html_entity_decode( $post->post_title . ' ' . $post->post_excerpt . ' ' . $content, ENT_QUOTES, 'UTF-8' ) );
	}

	private function extract_headings( $html ) {
		$headings = array();
		if ( preg_match_all( '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', (string) $html, $matches ) ) {
			foreach ( $matches[1] as $heading ) {
				$heading = sanitize_text_field( wp_strip_all_tags( $heading ) );
				if ( $heading ) {
					$headings[] = $heading;
				}
			}
		}
		return array_slice( array_values( array_unique( $headings ) ), 0, 60 );
	}

	private function token_set( $text ) {
		$tokens = preg_split( '/[^\p{L}\p{N}]+/u', $this->lower( $text ), -1, PREG_SPLIT_NO_EMPTY );
		$set = array();
		foreach ( (array) $tokens as $token ) {
			if ( $this->text_length( $token ) >= 3 && ! in_array( $token, $this->stopwords(), true ) ) {
				$set[ $token ] = true;
			}
		}
		return $set;
	}

	private function topic_present( $topic, array $tokens, $text ) {
		$normalized = $this->normalize_topic( $topic );
		if ( ! $normalized ) {
			return false;
		}
		if ( false !== strpos( $this->lower( $text ), $normalized ) ) {
			return true;
		}
		$parts = preg_split( '/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY );
		$found = 0;
		foreach ( $parts as $part ) {
			if ( isset( $tokens[ $part ] ) ) {
				$found++;
			}
		}
		return $parts && $found / count( $parts ) >= 0.75;
	}

	private function normalize_topic( $topic ) {
		$topic = $this->normalize_query( wp_strip_all_tags( (string) $topic ) );
		$parts = preg_split( '/\s+/', $topic, -1, PREG_SPLIT_NO_EMPTY );
		$parts = array_values( array_filter( $parts, function( $part ) { return $this->text_length( $part ) >= 3 && ! in_array( $part, $this->stopwords(), true ); } ) );
		return trim( implode( ' ', array_slice( $parts, 0, 8 ) ) );
	}

	private function normalize_query( $query ) {
		$query = remove_accents( $this->lower( sanitize_text_field( $query ) ) );
		$query = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $query );
		return trim( preg_replace( '/\s+/', ' ', $query ) );
	}


	private function text_length( $value ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value, 'UTF-8' ) : strlen( (string) $value );
	}

	private function text_substr( $value, $start, $length ) {
		return function_exists( 'mb_substr' ) ? mb_substr( (string) $value, $start, $length, 'UTF-8' ) : substr( (string) $value, $start, $length );
	}

	private function lower( $value ) {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( (string) $value, 'UTF-8' ) : strtolower( (string) $value );
	}

	private function url_key( $url ) {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return strtolower( trim( (string) $url ) );
		}
		$host = strtolower( preg_replace( '/^www\./i', '', (string) ( $parts['host'] ?? '' ) ) );
		$path = '/' . ltrim( (string) ( $parts['path'] ?? '/' ), '/' );
		$path = '/' === $path ? '/' : untrailingslashit( $path );
		return $host . $path;
	}

	private function sanitize_list( $value, $limit, $max_length ) {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\r\n,]+/', $value, -1, PREG_SPLIT_NO_EMPTY );
		}
		$items = array();
		foreach ( (array) $value as $item ) {
			$item = sanitize_text_field( is_scalar( $item ) ? (string) $item : '' );
			if ( $item ) {
				$items[] = $this->text_substr( $item, 0, $max_length );
			}
		}
		return array_slice( array_values( array_unique( $items ) ), 0, $limit );
	}

	private function decode_list( $json ) {
		$data = json_decode( (string) $json, true );
		return is_array( $data ) ? array_values( array_filter( array_map( 'sanitize_text_field', $data ) ) ) : array();
	}

	private function decode_json( $json ) {
		$data = json_decode( (string) $json, true );
		return is_array( $data ) ? $data : array();
	}

	private function counts_to_items( array $counts, $limit ) {
		$items = array();
		foreach ( array_slice( $counts, 0, $limit, true ) as $label => $count ) {
			$items[] = array( 'label' => $label, 'competitors' => absint( $count ) );
		}
		return $items;
	}

	private function mode( array $items ) {
		$counts = array_count_values( array_filter( $items ) );
		arsort( $counts );
		return $counts ? (string) key( $counts ) : '';
	}

	private function stopwords() {
		return array( 'the', 'and', 'for', 'with', 'from', 'this', 'that', 'your', 'you', 'our', 'are', 'was', 'were', 'will', 'can', 'has', 'have', 'how', 'what', 'why', 'when', 'where', 'who', 'into', 'about', 'near', 'best', 'top', 'page', 'home' );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
