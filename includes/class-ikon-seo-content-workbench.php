<?php

defined( 'ABSPATH' ) || exit;

/**
 * Evidence-led content brief and draft orchestration.
 *
 * Planned opportunities may become versioned briefs. Brief approval is
 * required before a separate WordPress draft can be created. This class never
 * publishes, redirects, deletes, changes canonicals or edits a live page.
 */
final class Ikon_SEO_Content_Workbench {
	const CACHE_KEY = 'ikon_seo_content_workbench_report_v1';
	const META_BRIEF_ID = '_ikon_seo_content_brief_id';
	const META_OPPORTUNITY_ID = '_ikon_seo_opportunity_id';
	const META_EVIDENCE_HASH = '_ikon_seo_content_evidence_hash';
	const META_BRIEF_VERSION = '_ikon_seo_content_brief_version';
	const META_DRAFT_KIND = '_ikon_seo_controlled_draft_kind';

	private $opportunity_engine;
	private $publisher;
	private $competitor_content;
	private $strategy;
	private $profile;
	private $inventory;
	private $renderer;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Opportunity_Engine $opportunity_engine,
		Ikon_SEO_Publisher_Intelligence $publisher,
		Ikon_SEO_Competitor_Content_Intelligence $competitor_content,
		Ikon_SEO_Strategy $strategy,
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Renderer $renderer,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->opportunity_engine = $opportunity_engine;
		$this->publisher = $publisher;
		$this->competitor_content = $competitor_content;
		$this->strategy = $strategy;
		$this->profile = $profile;
		$this->inventory = $inventory;
		$this->renderer = $renderer;
		$this->history = $history;
		$this->logger = $logger;
	}

	public function table() {
		return $this->competitor_content->briefs_table();
	}

	/** Public read-only access for the editorial governance layer. */
	public function brief( $brief_id ) {
		return $this->get_brief( absint( $brief_id ) );
	}

	/** Revalidate the evidence lock without changing the draft. */
	public function assert_current( $brief_id ) {
		$brief = $this->get_brief( absint( $brief_id ), true );
		if ( ! $brief ) {
			return new WP_Error( 'ikon_seo_content_brief_missing', __( 'The content brief could not be found.', 'ikon-seo' ) );
		}
		return $this->validate_evidence( $brief, $brief['evidence_hash'] );
	}

	public function status() {
		global $wpdb;
		$ready = $this->table_exists( $this->table() ) && $this->columns_ready();
		$counts = array( 'proposed' => 0, 'approved' => 0, 'draft_created' => 0, 'ready' => 0, 'rejected' => 0, 'outdated' => 0, 'total' => 0 );
		if ( $ready ) {
			$rows = $wpdb->get_results( "SELECT status,COUNT(*) total FROM {$this->table()} WHERE opportunity_id > 0 GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( (array) $rows as $row ) {
				$key = sanitize_key( $row['status'] ?? '' );
				if ( isset( $counts[ $key ] ) ) {
					$counts[ $key ] = absint( $row['total'] ?? 0 );
				}
			}
			$counts['total'] = array_sum( array_intersect_key( $counts, array_flip( array( 'proposed','approved','draft_created','ready','rejected','outdated' ) ) ) );
		}
		return array(
			'database_ready' => $ready,
			'counts' => $counts,
			'draft_only' => true,
			'publishing_is_automatic' => false,
			'requires_planned_opportunity' => true,
			'requires_brief_approval' => true,
		);
	}

	public function sync( array $payload, $created_by = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'create_brief':
				return $this->create_brief( absint( $payload['opportunity_id'] ?? 0 ), $created_by );
			case 'approve_brief':
				return $this->approve_brief( absint( $payload['brief_id'] ?? 0 ), sanitize_text_field( $payload['evidence_hash'] ?? '' ), $created_by );
			case 'reject_brief':
				return $this->reject_brief( absint( $payload['brief_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $created_by );
			case 'create_scaffold':
				return $this->create_scaffold( absint( $payload['brief_id'] ?? 0 ), sanitize_text_field( $payload['evidence_hash'] ?? '' ), $created_by );
			case 'submit_draft':
				return $this->submit_draft( absint( $payload['brief_id'] ?? 0 ), sanitize_text_field( $payload['evidence_hash'] ?? '' ), (array) ( $payload['page_payload'] ?? array() ), $created_by );
			case 'evaluate_draft':
				return $this->evaluate_draft( absint( $payload['brief_id'] ?? 0 ), $created_by );
			case 'mark_ready':
				return $this->mark_ready( absint( $payload['brief_id'] ?? 0 ), $created_by );
			case 'read':
			default:
				return $this->report( absint( $payload['limit'] ?? 100 ), false );
		}
	}

	public function report( $limit = 100, $refresh = false ) {
		global $wpdb;
		$limit = max( 10, min( 250, absint( $limit ) ) );
		if ( $refresh ) {
			delete_transient( self::CACHE_KEY );
		} else {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$status = $this->status();
		if ( empty( $status['database_ready'] ) ) {
			return array( 'status' => $status, 'briefs' => array(), 'eligible_opportunities' => array(), 'limitations' => array( 'Update or reactivate Ikon SEO to add the v1.9.0 Content Workbench columns.' ) );
		}
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE opportunity_id > 0 ORDER BY FIELD(status,'outdated','proposed','approved','draft_created','ready','rejected'), gap_priority DESC, updated_at DESC LIMIT %d", $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$briefs = array_map( array( $this, 'format_brief' ), $rows ?: array() );
		$opportunity_report = $this->opportunity_engine->report( array( 'status' => 'planned', 'limit' => $limit ) );
		$linked = array();
		foreach ( $briefs as $brief ) {
			$linked[ absint( $brief['opportunity_id'] ) ] = true;
		}
		$eligible = array();
		foreach ( (array) ( $opportunity_report['opportunities'] ?? array() ) as $opportunity ) {
			if ( $this->is_content_opportunity( $opportunity ) && empty( $linked[ absint( $opportunity['id'] ?? 0 ) ] ) ) {
				$eligible[] = $opportunity;
			}
		}
		$result = array(
			'generated_at' => current_time( 'mysql', true ),
			'status' => $status,
			'briefs' => $briefs,
			'eligible_opportunities' => $eligible,
			'workflow' => array( 'planned_opportunity', 'proposed_brief', 'approved_brief', 'separate_draft', 'quality_gate', 'human_review' ),
			'safety' => array(
				'No public page is created or updated.',
				'An approved brief and matching evidence hash are required before draft creation.',
				'Existing live pages receive separate revision drafts rather than direct edits.',
				'Publishing, redirects, deletion, canonical and noindex changes remain outside this workflow.',
			),
		);
		set_transient( self::CACHE_KEY, $result, 5 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function create_brief( $opportunity_id, $created_by = 0 ) {
		global $wpdb;
		if ( ! $this->status()['database_ready'] ) {
			return new WP_Error( 'ikon_seo_content_workbench_tables', __( 'Content Workbench database columns are not ready.', 'ikon-seo' ) );
		}
		$opportunity = $this->opportunity_engine->opportunity( $opportunity_id );
		if ( ! $opportunity ) {
			return new WP_Error( 'ikon_seo_content_opportunity_missing', __( 'The selected opportunity could not be found.', 'ikon-seo' ) );
		}
		if ( 'planned' !== sanitize_key( $opportunity['status'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_content_opportunity_unplanned', __( 'Mark the opportunity Planned before creating a content brief.', 'ikon-seo' ) );
		}
		if ( ! $this->is_content_opportunity( $opportunity ) ) {
			return new WP_Error( 'ikon_seo_content_opportunity_type', __( 'This opportunity requires technical, authority or indexation work rather than a content draft.', 'ikon-seo' ) );
		}

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE opportunity_id=%d ORDER BY id DESC LIMIT 1", $opportunity_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $existing && ! in_array( sanitize_key( $existing['status'] ?? '' ), array( 'proposed', 'outdated', 'rejected' ), true ) ) {
			return new WP_Error( 'ikon_seo_content_brief_locked', __( 'The current brief is approved or already in drafting. Finish, reject or invalidate that workflow before regenerating it.', 'ikon-seo' ) );
		}
		if ( $existing && ! empty( $existing['draft_post_id'] ) ) {
			$existing_draft = get_post( absint( $existing['draft_post_id'] ) );
			if ( $existing_draft && 'trash' !== $existing_draft->post_status ) {
				return new WP_Error( 'ikon_seo_content_draft_exists', __( 'Trash the existing controlled draft before regenerating this brief.', 'ikon-seo' ) );
			}
		}
		$version = $existing ? absint( $existing['brief_version'] ?? 0 ) + 1 : 1;
		$evidence_hash = $this->opportunity_hash( $opportunity );
		$brief = $this->build_brief( $opportunity, $version, $evidence_hash );
		$publisher_item = $this->publisher->save_item(
			array(
				'id' => absint( $existing['publisher_item_id'] ?? 0 ),
				'title' => $brief['working_title'],
				'content_type' => $brief['page_type'],
				'intent' => $brief['search_intent'],
				'stage' => 'brief',
				'priority' => absint( $opportunity['priority'] ?? 50 ),
				'target_post_id' => absint( $brief['source_post_id'] ?? 0 ),
				'source_requirements' => implode( "\n", (array) $brief['source_requirements'] ),
				'evidence_notes' => implode( "\n", (array) $brief['direct_evidence'] ),
				'originality_required' => 1,
				'disclosure_required' => ! empty( $brief['disclosure_required'] ),
				'brief' => $brief,
				'notes' => 'Created from Opportunity Engine item #' . absint( $opportunity_id ),
			),
			$created_by
		);
		if ( is_wp_error( $publisher_item ) ) {
			return $publisher_item;
		}
		$now = current_time( 'mysql', true );
		$row = array(
			'post_id' => absint( $brief['source_post_id'] ?? 0 ),
			'query_hash' => hash( 'sha256', 'opportunity|' . $opportunity_id . '|' . $this->normalize( $brief['primary_query'] ) ),
			'page_url' => esc_url_raw( $brief['target_url'] ?? '' ),
			'page_title' => sanitize_text_field( $brief['working_title'] ),
			'target_query' => sanitize_text_field( $brief['primary_query'] ),
			'target_intent' => sanitize_key( $brief['search_intent'] ),
			'page_intent' => sanitize_key( $brief['search_intent'] ),
			'dominant_result_type' => sanitize_key( $brief['page_type'] ),
			'intent_alignment' => 'planned',
			'competitor_count' => absint( $brief['competitor_count'] ?? 0 ),
			'topic_coverage' => null,
			'gap_priority' => absint( $opportunity['priority'] ?? 50 ),
			'evidence_confidence' => sanitize_key( $opportunity['confidence'] ?? 'medium' ),
			'covered_topics_json' => wp_json_encode( $brief['confirmed_topics'] ),
			'missing_topics_json' => wp_json_encode( $brief['recommended_topics'] ),
			'missing_entities_json' => wp_json_encode( $brief['entities_to_verify'] ),
			'trust_patterns_json' => wp_json_encode( $brief['trust_requirements'] ),
			'conversion_patterns_json' => wp_json_encode( $brief['conversion_requirements'] ),
			'requirements_json' => wp_json_encode( $brief ),
			'direct_evidence_json' => wp_json_encode( $brief['direct_evidence'] ),
			'hypotheses_json' => wp_json_encode( $brief['hypotheses'] ),
			'status' => 'proposed',
			'created_by' => absint( $created_by ),
			'created_at' => $now,
			'updated_at' => $now,
			'opportunity_id' => absint( $opportunity_id ),
			'publisher_item_id' => absint( $publisher_item['id'] ?? 0 ),
			'brief_version' => $version,
			'evidence_hash' => $evidence_hash,
			'approval_notes' => '',
			'approved_by' => 0,
			'approved_at' => null,
			'draft_post_id' => 0,
			'draft_hash' => '',
			'draft_created_by' => 0,
			'draft_created_at' => null,
			'last_error' => '',
		);
		if ( $existing ) {
			unset( $row['created_by'], $row['created_at'] );
			$result = $wpdb->update( $this->table(), $row, array( 'id' => absint( $existing['id'] ) ) );
			$brief_id = absint( $existing['id'] );
		} else {
			$result = $wpdb->insert( $this->table(), $row );
			$brief_id = absint( $wpdb->insert_id );
		}
		if ( false === $result || ! $brief_id ) {
			return new WP_Error( 'ikon_seo_content_brief_store', __( 'The controlled content brief could not be stored.', 'ikon-seo' ) );
		}
		$this->record_history( 'briefing', 'Content brief proposed', sprintf( 'Brief #%d was created from planned opportunity #%d and requires approval.', $brief_id, $opportunity_id ), $brief_id, $created_by );
		$this->clear_cache();
		return $this->get_brief( $brief_id );
	}

	public function approve_brief( $brief_id, $expected_hash = '', $user_id = 0 ) {
		$brief = $this->get_brief( $brief_id, true );
		if ( ! $brief ) {
			return new WP_Error( 'ikon_seo_content_brief_missing', __( 'The content brief could not be found.', 'ikon-seo' ) );
		}
		if ( 'proposed' !== $brief['status'] ) {
			return new WP_Error( 'ikon_seo_content_brief_state', __( 'Only a current proposed brief can be approved. Regenerate outdated or rejected briefs first.', 'ikon-seo' ) );
		}
		$current = $this->validate_evidence( $brief, $expected_hash );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		global $wpdb;
		$wpdb->update( $this->table(), array( 'status' => 'approved', 'approved_by' => absint( $user_id ), 'approved_at' => current_time( 'mysql', true ), 'approval_notes' => 'Approved against the current evidence snapshot.', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $brief_id ) );
		if ( ! empty( $brief['publisher_item_id'] ) ) {
			$this->publisher->save_item( array( 'id' => $brief['publisher_item_id'], 'title' => $brief['page_title'], 'content_type' => $brief['page_type'], 'intent' => $brief['target_intent'], 'stage' => 'approved', 'priority' => $brief['gap_priority'], 'target_post_id' => $brief['post_id'], 'brief' => $brief['brief'] ), $user_id );
		}
		$this->record_history( 'approval', 'Content brief approved', sprintf( 'Brief #%d is approved for separate draft creation. No public page was changed.', $brief_id ), $brief_id, $user_id );
		$this->clear_cache();
		return $this->get_brief( $brief_id );
	}

	public function reject_brief( $brief_id, $notes = '', $user_id = 0 ) {
		$brief = $this->get_brief( $brief_id, true );
		if ( ! $brief ) {
			return new WP_Error( 'ikon_seo_content_brief_missing', __( 'The content brief could not be found.', 'ikon-seo' ) );
		}
		global $wpdb;
		$wpdb->update( $this->table(), array( 'status' => 'rejected', 'approval_notes' => sanitize_textarea_field( $notes ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $brief_id ) );
		if ( ! empty( $brief['publisher_item_id'] ) ) {
			$this->publisher->save_item( array( 'id' => $brief['publisher_item_id'], 'title' => $brief['page_title'], 'content_type' => $brief['page_type'], 'intent' => $brief['target_intent'], 'stage' => 'archived', 'priority' => $brief['gap_priority'], 'brief' => $brief['brief'], 'notes' => $notes ), $user_id );
		}
		$this->record_history( 'approval', 'Content brief rejected', sprintf( 'Brief #%d was rejected. No draft was created.', $brief_id ), $brief_id, $user_id );
		$this->clear_cache();
		return $this->get_brief( $brief_id );
	}

	public function create_scaffold( $brief_id, $expected_hash = '', $user_id = 0 ) {
		$brief = $this->approved_current_brief( $brief_id, $expected_hash );
		if ( is_wp_error( $brief ) ) {
			return $brief;
		}
		$payload = $this->scaffold_payload( $brief );
		return $this->store_draft( $brief, $payload, 'scaffold', $user_id );
	}

	public function submit_draft( $brief_id, $expected_hash, array $payload, $user_id = 0 ) {
		$brief = $this->approved_current_brief( $brief_id, $expected_hash );
		if ( is_wp_error( $brief ) ) {
			return $brief;
		}
		$validation = $this->validate_page_payload( $payload, $brief );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		return $this->store_draft( $brief, $validation, 'generated', $user_id );
	}

	public function evaluate_draft( $brief_id, $user_id = 0 ) {
		$brief = $this->get_brief( $brief_id, true );
		if ( ! $brief || empty( $brief['draft_post_id'] ) || empty( $brief['publisher_item_id'] ) ) {
			return new WP_Error( 'ikon_seo_content_draft_missing', __( 'Create a controlled draft before running the quality gate.', 'ikon-seo' ) );
		}
		$current = $this->validate_evidence( $brief, $brief['evidence_hash'] );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$post = get_post( $brief['draft_post_id'] );
		if ( ! $post || 'publish' === $post->post_status ) {
			return new WP_Error( 'ikon_seo_content_draft_state', __( 'The controlled draft is missing or is no longer a draft.', 'ikon-seo' ) );
		}
		$review = $this->publisher->evaluate_post( $post->ID, $brief['publisher_item_id'], true, $user_id );
		if ( is_wp_error( $review ) ) {
			return $review;
		}
		return array( 'brief' => $this->get_brief( $brief_id ), 'quality_gate' => $review );
	}

	public function mark_ready( $brief_id, $user_id = 0 ) {
		$brief = $this->get_brief( $brief_id, true );
		if ( ! $brief || empty( $brief['draft_post_id'] ) ) {
			return new WP_Error( 'ikon_seo_content_draft_missing', __( 'Create and evaluate a controlled draft first.', 'ikon-seo' ) );
		}
		$current = $this->validate_evidence( $brief, $brief['evidence_hash'] );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$review = get_post_meta( $brief['draft_post_id'], Ikon_SEO_Publisher_Intelligence::POST_META_REVIEW, true );
		if ( ! is_array( $review ) || 'passed' !== sanitize_key( $review['gate_status'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_content_quality_gate', __( 'The Publisher Intelligence quality gate must pass before marking the draft ready for human review.', 'ikon-seo' ) );
		}
		global $wpdb;
		$wpdb->update( $this->table(), array( 'status' => 'ready', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $brief_id ) );
		$this->publisher->save_item( array( 'id' => $brief['publisher_item_id'], 'title' => $brief['page_title'], 'content_type' => $brief['page_type'], 'intent' => $brief['target_intent'], 'stage' => 'ready', 'priority' => $brief['gap_priority'], 'target_post_id' => $brief['draft_post_id'], 'brief' => $brief['brief'] ), $user_id );
		$this->record_history( 'approval', 'Controlled draft ready for human review', sprintf( 'Draft #%d passed the internal quality gate. Publishing still requires a separate WordPress decision.', $brief['draft_post_id'] ), $brief_id, $user_id, $brief['draft_post_id'] );
		$this->clear_cache();
		return $this->get_brief( $brief_id );
	}

	private function store_draft( array $brief, array $payload, $kind, $user_id ) {
		$rendered = $this->renderer->render( $payload );
		$source = ! empty( $brief['post_id'] ) ? get_post( $brief['post_id'] ) : null;
		$post_type = $source ? $source->post_type : $this->post_type_for_page_type( $brief['page_type'] );
		$title = sanitize_text_field( $payload['title'] ?? $brief['page_title'] );
		if ( $source ) {
			$title = sprintf( 'Revision draft — %s', $title );
		}
		$postarr = array(
			'post_type' => $post_type,
			'post_title' => $title,
			'post_name' => sanitize_title( $payload['slug'] ?? $brief['brief']['suggested_slug'] ?? $title ),
			'post_excerpt' => sanitize_textarea_field( $payload['excerpt'] ?? '' ),
			'post_content' => $rendered['post_content'],
			'post_status' => 'draft',
			'post_author' => absint( $user_id ) ?: absint( Ikon_SEO_Plugin::settings()['author_id'] ?? 0 ),
		);
		$post_id = wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		$settings = Ikon_SEO_Plugin::settings();
		$builder = sanitize_key( $settings['builder_preference'] ?? 'auto' );
		if ( 'auto' === $builder ) {
			$builder = defined( 'ELEMENTOR_VERSION' ) ? 'elementor' : 'gutenberg';
		}
		update_post_meta( $post_id, '_ikon_seo_managed', 1 );
		update_post_meta( $post_id, '_ikon_seo_workflow_status', 'awaiting_review' );
		update_post_meta( $post_id, '_ikon_seo_builder', $builder );
		update_post_meta( $post_id, self::META_BRIEF_ID, absint( $brief['id'] ) );
		update_post_meta( $post_id, self::META_OPPORTUNITY_ID, absint( $brief['opportunity_id'] ) );
		update_post_meta( $post_id, self::META_EVIDENCE_HASH, sanitize_text_field( $brief['evidence_hash'] ) );
		update_post_meta( $post_id, self::META_BRIEF_VERSION, absint( $brief['brief_version'] ) );
		update_post_meta( $post_id, self::META_DRAFT_KIND, sanitize_key( $kind ) );
		if ( $source ) {
			update_post_meta( $post_id, '_ikon_seo_source_page_id', $source->ID );
			update_post_meta( $post_id, '_ikon_seo_source_url', get_permalink( $source ) );
		}
		if ( 'elementor' === $builder ) {
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_template_type', 'post' === $post_type ? 'wp-post' : 'wp-page' );
			update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $rendered['elementor_data'] ) ) );
			update_post_meta( $post_id, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
		}
		$draft_hash = hash( 'sha256', $post_id . '|' . $brief['evidence_hash'] . '|' . wp_json_encode( $payload ) );
		global $wpdb;
		$wpdb->update( $this->table(), array( 'status' => 'draft_created', 'draft_post_id' => $post_id, 'draft_hash' => $draft_hash, 'draft_created_by' => absint( $user_id ), 'draft_created_at' => current_time( 'mysql', true ), 'last_error' => '', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $brief['id'] ) ) );
		$this->publisher->save_item( array( 'id' => $brief['publisher_item_id'], 'title' => $brief['page_title'], 'content_type' => $brief['page_type'], 'intent' => $brief['target_intent'], 'stage' => 'drafting', 'priority' => $brief['gap_priority'], 'target_post_id' => $post_id, 'brief' => $brief['brief'] ), $user_id );
		$this->record_history( 'draft', 'Controlled WordPress draft created', sprintf( 'Draft #%d was created from approved brief #%d. It remains unpublished.', $post_id, $brief['id'] ), $brief['id'], $user_id, $post_id );
		$this->clear_cache();
		return array( 'brief' => $this->get_brief( $brief['id'] ), 'post_id' => $post_id, 'edit_url' => get_edit_post_link( $post_id, 'raw' ), 'preview_url' => get_preview_post_link( $post_id ), 'published' => false );
	}

	private function approved_current_brief( $brief_id, $expected_hash ) {
		$brief = $this->get_brief( $brief_id, true );
		if ( ! $brief ) {
			return new WP_Error( 'ikon_seo_content_brief_missing', __( 'The content brief could not be found.', 'ikon-seo' ) );
		}
		if ( 'approved' !== $brief['status'] ) {
			return new WP_Error( 'ikon_seo_content_brief_unapproved', __( 'Approve the current brief before creating a draft.', 'ikon-seo' ) );
		}
		if ( ! empty( $brief['draft_post_id'] ) ) {
			$existing_draft = get_post( absint( $brief['draft_post_id'] ) );
			if ( $existing_draft && 'trash' !== $existing_draft->post_status ) {
				return new WP_Error( 'ikon_seo_content_draft_exists', __( 'This approved brief already has a controlled draft. Review or trash that draft before regenerating the brief.', 'ikon-seo' ) );
			}
		}
		$valid = $this->validate_evidence( $brief, $expected_hash );
		return is_wp_error( $valid ) ? $valid : $brief;
	}

	private function validate_evidence( array $brief, $expected_hash = '' ) {
		$expected_hash = sanitize_text_field( $expected_hash );
		if ( $expected_hash && ! hash_equals( (string) $brief['evidence_hash'], $expected_hash ) ) {
			return new WP_Error( 'ikon_seo_content_stale_request', __( 'The brief evidence token changed. Refresh the workbench before continuing.', 'ikon-seo' ), array( 'status' => 409 ) );
		}
		$opportunity = $this->opportunity_engine->opportunity( absint( $brief['opportunity_id'] ) );
		if ( ! $opportunity || 'planned' !== sanitize_key( $opportunity['status'] ?? '' ) || ! hash_equals( (string) $brief['evidence_hash'], $this->opportunity_hash( $opportunity ) ) ) {
			global $wpdb;
			$wpdb->update( $this->table(), array( 'status' => 'outdated', 'last_error' => 'The source opportunity or its evidence changed after this brief was generated.', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $brief['id'] ) ) );
			$this->clear_cache();
			return new WP_Error( 'ikon_seo_content_evidence_changed', __( 'The source opportunity changed. Regenerate and reapprove the brief before drafting.', 'ikon-seo' ), array( 'status' => 409 ) );
		}
		return true;
	}

	private function build_brief( array $opportunity, $version, $evidence_hash ) {
		$strategy = $this->strategy->get();
		$profile = $this->profile->get();
		$evidence = (array) ( $opportunity['evidence'] ?? array() );
		$query = sanitize_text_field( $opportunity['keyword'] ?? '' );
		if ( ! $query ) {
			$query = sanitize_text_field( $opportunity['title'] ?? '' );
		}
		$source_post_id = absint( $opportunity['post_id'] ?? 0 );
		if ( ! $source_post_id && ! empty( $opportunity['target_url'] ) ) {
			$source_post_id = url_to_postid( $opportunity['target_url'] );
		}
		$source_post = $source_post_id ? get_post( $source_post_id ) : null;
		$page_type = $this->page_type( $opportunity, $source_post );
		$intent = $this->intent( $opportunity, $page_type );
		$title = $source_post ? $source_post->post_title : $this->working_title( $query, $page_type, $profile );
		$topics = $this->extract_lists( $evidence, array( 'topics','missing_topics','queries','entities','headings' ), 30 );
		$entities = $this->extract_lists( $evidence, array( 'entities','missing_entities' ), 20 );
		$actions = array_values( array_filter( array_map( 'sanitize_textarea_field', (array) ( $opportunity['actions'] ?? array() ) ) ) );
		$direct_evidence = array_values( array_filter( array(
			sanitize_textarea_field( $opportunity['summary'] ?? '' ),
			'Primary source: ' . ucwords( str_replace( '_', ' ', sanitize_key( $opportunity['primary_source'] ?? 'unknown' ) ) ),
			'Opportunity priority: ' . absint( $opportunity['priority'] ?? 0 ) . '/100; confidence: ' . sanitize_key( $opportunity['confidence'] ?? 'medium' ) . '.',
			! empty( $opportunity['observed_at'] ) ? 'Evidence observed: ' . sanitize_text_field( $opportunity['observed_at'] ) . '.' : '',
		) ) );
		$audience = sanitize_textarea_field( $strategy['target_audience'] ?? $profile['target_audience'] ?? '' );
		$value = sanitize_textarea_field( $strategy['value_proposition'] ?? '' );
		$conversions = $this->lines( $strategy['primary_conversions'] ?? '' );
		$offerings = $this->lines( $strategy['main_offerings'] ?? '' );
		return array(
			'brief_version' => absint( $version ),
			'evidence_hash' => $evidence_hash,
			'generated_at' => current_time( 'mysql', true ),
			'opportunity_id' => absint( $opportunity['id'] ?? 0 ),
			'page_action' => $source_post ? 'separate_improvement_draft' : 'new_draft',
			'source_post_id' => $source_post_id,
			'target_url' => esc_url_raw( $opportunity['target_url'] ?? ( $source_post ? get_permalink( $source_post ) : '' ) ),
			'working_title' => $title,
			'suggested_slug' => sanitize_title( $query ?: $title ),
			'page_type' => $page_type,
			'primary_query' => $query,
			'search_intent' => $intent,
			'target_audience' => $audience,
			'value_proposition' => $value,
			'confirmed_offerings' => $offerings,
			'confirmed_topics' => array_values( array_unique( array_filter( array_merge( array( $query ), array_slice( $topics, 0, 10 ) ) ) ) ),
			'recommended_topics' => array_values( array_unique( array_filter( array_slice( $topics, 0, 20 ) ) ) ),
			'entities_to_verify' => $entities,
			'section_plan' => $this->section_plan( $page_type, $query, $topics, $source_post ),
			'internal_link_candidates' => $this->internal_link_candidates( $query, $source_post_id ),
			'conversion_requirements' => $conversions,
			'trust_requirements' => array_values( array_filter( array( 'Use only confirmed business facts.', 'Cite suitable primary or authoritative sources for factual claims.', 'Do not copy competitor wording or structure.', 'Keep pricing, guarantees, certifications and qualifications excluded unless confirmed.' ) ) ),
			'source_requirements' => $this->source_requirements( $strategy, $page_type ),
			'unsupported_claims' => array( 'best', 'number one', '#1', 'guaranteed results', 'cheapest', 'certified professionals' ),
			'differentiation_requirements' => array_merge( $actions, array_filter( array( $value ? 'Express the confirmed value proposition without unsupported superiority claims.' : '' ) ) ),
			'direct_evidence' => $direct_evidence,
			'hypotheses' => array( 'The opportunity score prioritises review; it does not guarantee rankings, traffic or leads.', 'Suggested topics and entities require factual relevance checks before inclusion.' ),
			'quality_gates' => array( 'Brief approved against current evidence', 'Original content', 'Source policy met', 'Internal links reviewed', 'CTA uses confirmed conversion paths', 'Publisher Intelligence quality gate passed', 'Human review before publication' ),
			'disclosure_required' => in_array( sanitize_key( $strategy['monetization_model'] ?? '' ), array( 'affiliate','advertising','sponsorship','mixed' ), true ),
			'competitor_count' => absint( $evidence['competitor_count'] ?? 0 ),
		);
	}

	private function scaffold_payload( array $brief ) {
		$b = (array) $brief['brief'];
		$sections = array();
		foreach ( (array) ( $b['section_plan'] ?? array() ) as $section ) {
			$sections[] = array(
				'type' => 'content',
				'heading' => sanitize_text_field( $section['heading'] ?? '' ),
				'content' => '<p><em>Drafting note: ' . esc_html( $section['purpose'] ?? 'Write evidence-based, original content for this section.' ) . '</em></p>',
			);
		}
		$sections[] = array( 'type' => 'notice', 'heading' => 'Editorial controls', 'content' => '<p>This is an unpublished editorial scaffold. Verify all facts, sources, claims, internal links and calls to action before human approval.</p>' );
		return array(
			'title' => sanitize_text_field( $b['working_title'] ?? $brief['page_title'] ),
			'slug' => sanitize_title( $b['suggested_slug'] ?? '' ),
			'excerpt' => sanitize_textarea_field( $brief['direct_evidence'][0] ?? '' ),
			'hero' => array( 'title' => sanitize_text_field( $b['working_title'] ?? $brief['page_title'] ), 'description' => 'Editorial scaffold generated from an approved Ikon SEO brief. Replace all drafting notes before review.' ),
			'sections' => $sections,
			'faq' => array(),
		);
	}

	private function validate_page_payload( array $payload, array $brief ) {
		$title = sanitize_text_field( $payload['title'] ?? '' );
		if ( ! $title ) {
			return new WP_Error( 'ikon_seo_content_title', __( 'A draft title is required.', 'ikon-seo' ) );
		}
		$sections = isset( $payload['sections'] ) && is_array( $payload['sections'] ) ? array_slice( $payload['sections'], 0, 40 ) : array();
		if ( ! $sections ) {
			return new WP_Error( 'ikon_seo_content_sections', __( 'The controlled draft requires at least one content section.', 'ikon-seo' ) );
		}
		$clean = array(
			'title' => $title,
			'slug' => sanitize_title( $payload['slug'] ?? $brief['brief']['suggested_slug'] ?? $title ),
			'excerpt' => substr( sanitize_textarea_field( $payload['excerpt'] ?? '' ), 0, 1000 ),
			'hero' => $this->sanitize_recursive( (array) ( $payload['hero'] ?? array() ) ),
			'sections' => $this->sanitize_recursive( $sections ),
			'faq' => $this->sanitize_recursive( array_slice( (array) ( $payload['faq'] ?? array() ), 0, 30 ) ),
			'faq_heading' => sanitize_text_field( $payload['faq_heading'] ?? 'Frequently Asked Questions' ),
		);
		if ( empty( $clean['hero']['title'] ) ) {
			$clean['hero']['title'] = $title;
		}
		$serialized = wp_json_encode( $clean );
		if ( strlen( $serialized ) > 512 * 1024 ) {
			return new WP_Error( 'ikon_seo_content_payload_size', __( 'The controlled draft payload is larger than 512 KB.', 'ikon-seo' ) );
		}
		return $clean;
	}

	private function get_brief( $id, $raw = false ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id=%d AND opportunity_id>0", absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) {
			return array();
		}
		return $raw ? $this->format_brief( $row ) : $this->format_brief( $row );
	}

	private function format_brief( $row ) {
		$brief = $this->decode_json( $row['requirements_json'] ?? '' );
		$draft_post_id = absint( $row['draft_post_id'] ?? 0 );
		return array(
			'id' => absint( $row['id'] ?? 0 ),
			'opportunity_id' => absint( $row['opportunity_id'] ?? 0 ),
			'publisher_item_id' => absint( $row['publisher_item_id'] ?? 0 ),
			'brief_version' => absint( $row['brief_version'] ?? 1 ),
			'evidence_hash' => sanitize_text_field( $row['evidence_hash'] ?? '' ),
			'status' => sanitize_key( $row['status'] ?? 'proposed' ),
			'post_id' => absint( $row['post_id'] ?? 0 ),
			'page_url' => esc_url_raw( $row['page_url'] ?? '' ),
			'page_title' => sanitize_text_field( $row['page_title'] ?? '' ),
			'target_query' => sanitize_text_field( $row['target_query'] ?? '' ),
			'target_intent' => sanitize_key( $row['target_intent'] ?? 'mixed' ),
			'page_type' => sanitize_key( $row['dominant_result_type'] ?? 'article' ),
			'gap_priority' => absint( $row['gap_priority'] ?? 0 ),
			'evidence_confidence' => sanitize_key( $row['evidence_confidence'] ?? 'low' ),
			'direct_evidence' => $this->decode_json( $row['direct_evidence_json'] ?? '' ),
			'brief' => $brief,
			'approval_notes' => sanitize_textarea_field( $row['approval_notes'] ?? '' ),
			'approved_by' => absint( $row['approved_by'] ?? 0 ),
			'approved_at' => sanitize_text_field( $row['approved_at'] ?? '' ),
			'draft_post_id' => $draft_post_id,
			'draft_hash' => sanitize_text_field( $row['draft_hash'] ?? '' ),
			'draft_edit_url' => $draft_post_id ? get_edit_post_link( $draft_post_id, 'raw' ) : '',
			'draft_preview_url' => $draft_post_id ? get_preview_post_link( $draft_post_id ) : '',
			'last_error' => sanitize_textarea_field( $row['last_error'] ?? '' ),
			'updated_at' => sanitize_text_field( $row['updated_at'] ?? '' ),
		);
	}

	private function opportunity_hash( array $opportunity ) {
		$copy = array(
			'id' => absint( $opportunity['id'] ?? 0 ),
			'status' => sanitize_key( $opportunity['status'] ?? '' ),
			'type' => sanitize_key( $opportunity['type'] ?? '' ),
			'category' => sanitize_key( $opportunity['category'] ?? '' ),
			'primary_source' => sanitize_key( $opportunity['primary_source'] ?? '' ),
			'title' => sanitize_text_field( $opportunity['title'] ?? '' ),
			'summary' => sanitize_textarea_field( $opportunity['summary'] ?? '' ),
			'target_url' => esc_url_raw( $opportunity['target_url'] ?? '' ),
			'post_id' => absint( $opportunity['post_id'] ?? 0 ),
			'keyword' => sanitize_text_field( $opportunity['keyword'] ?? '' ),
			'intent' => sanitize_key( $opportunity['intent'] ?? '' ),
			'priority' => absint( $opportunity['priority'] ?? 0 ),
			'confidence' => sanitize_key( $opportunity['confidence'] ?? '' ),
			'observed_at' => sanitize_text_field( $opportunity['observed_at'] ?? '' ),
			'evidence' => $opportunity['evidence'] ?? array(),
			'actions' => $opportunity['actions'] ?? array(),
		);
		return hash( 'sha256', wp_json_encode( $copy ) );
	}

	private function is_content_opportunity( array $opportunity ) {
		$category = sanitize_key( $opportunity['category'] ?? '' );
		$type = sanitize_key( $opportunity['type'] ?? '' );
		if ( in_array( $category, array( 'technical','indexation','authority','performance' ), true ) ) {
			return false;
		}
		if ( in_array( $type, array( 'broken_url','redirect_chain','pagespeed','broken_backlink_recovery','authority_gap_page' ), true ) ) {
			return false;
		}
		return in_array( $category, array( 'content_gap','content','performance_recovery','local','architecture','conversion','search_visibility','other' ), true ) || ! empty( $opportunity['keyword'] );
	}

	private function page_type( array $opportunity, $source_post = null ) {
		if ( $source_post ) {
			return 'post' === $source_post->post_type ? 'article' : 'service';
		}
		$type = sanitize_key( $opportunity['type'] ?? '' );
		$category = sanitize_key( $opportunity['category'] ?? '' );
		$intent = sanitize_key( $opportunity['intent'] ?? '' );
		if ( false !== strpos( $type, 'local' ) || 'local' === $category || 'local_service' === $intent ) {
			return 'location';
		}
		if ( in_array( $intent, array( 'transactional','commercial' ), true ) ) {
			return 'service';
		}
		return 'article';
	}

	private function intent( array $opportunity, $page_type ) {
		$intent = sanitize_key( $opportunity['intent'] ?? '' );
		if ( in_array( $intent, array( 'local_service','transactional','commercial','informational','navigational','mixed' ), true ) ) {
			return $intent;
		}
		return in_array( $page_type, array( 'service','location','product','category' ), true ) ? 'commercial' : 'informational';
	}

	private function working_title( $query, $page_type, array $profile ) {
		$query = trim( sanitize_text_field( $query ) );
		if ( ! $query ) {
			return 'Evidence-led content draft';
		}
		return ucwords( $query );
	}

	private function section_plan( $page_type, $query, array $topics, $source_post ) {
		$sections = array();
		if ( $source_post ) {
			$sections[] = array( 'heading' => 'Revision objective', 'purpose' => 'Explain what the existing page should improve while preserving accurate, useful content.' );
		}
		if ( in_array( $page_type, array( 'service','location' ), true ) ) {
			$sections[] = array( 'heading' => 'Service overview', 'purpose' => 'Answer the main commercial need clearly using only confirmed services and locations.' );
			$sections[] = array( 'heading' => 'Who this service is for', 'purpose' => 'Describe the confirmed audience and relevant use cases.' );
			$sections[] = array( 'heading' => 'What the service includes', 'purpose' => 'Explain the genuine scope without inventing pricing, guarantees, certifications or qualifications.' );
			$sections[] = array( 'heading' => 'How the process works', 'purpose' => 'Provide a truthful, practical process based on confirmed business information.' );
			$sections[] = array( 'heading' => 'Service area and next step', 'purpose' => 'Use only confirmed service areas and conversion actions.' );
		} else {
			$sections[] = array( 'heading' => 'Direct answer', 'purpose' => 'Answer the primary query early and precisely.' );
			$sections[] = array( 'heading' => 'Key considerations', 'purpose' => 'Explain the factors a reader needs to make a sound decision.' );
			$sections[] = array( 'heading' => 'Practical guidance', 'purpose' => 'Give original, actionable guidance supported by appropriate evidence.' );
			$sections[] = array( 'heading' => 'Common questions', 'purpose' => 'Address relevant questions without padding or unsupported claims.' );
		}
		foreach ( array_slice( $topics, 0, 6 ) as $topic ) {
			$sections[] = array( 'heading' => ucwords( sanitize_text_field( $topic ) ), 'purpose' => 'Include only when factually relevant and useful to the approved intent.' );
		}
		return array_slice( $sections, 0, 12 );
	}

	private function internal_link_candidates( $query, $exclude_post_id = 0 ) {
		$scan = $this->inventory->scan( false );
		$query_tokens = array_filter( preg_split( '/\s+/', $this->normalize( $query ) ) );
		$candidates = array();
		foreach ( (array) ( $scan['items'] ?? array() ) as $item ) {
			$id = absint( $item['id'] ?? 0 );
			if ( ! $id || $id === absint( $exclude_post_id ) || 'publish' !== ( $item['status'] ?? '' ) ) {
				continue;
			}
			$text = $this->normalize( ( $item['title'] ?? '' ) . ' ' . ( $item['slug'] ?? '' ) );
			$score = 0;
			foreach ( $query_tokens as $token ) {
				if ( strlen( $token ) > 2 && false !== strpos( $text, $token ) ) {
					$score++;
				}
			}
			if ( $score ) {
				$candidates[] = array( 'post_id' => $id, 'title' => sanitize_text_field( $item['title'] ?? '' ), 'url' => esc_url_raw( $item['url'] ?? get_permalink( $id ) ), 'relevance_tokens' => $score );
			}
		}
		usort( $candidates, function( $a, $b ) { return $b['relevance_tokens'] <=> $a['relevance_tokens']; } );
		return array_slice( $candidates, 0, 10 );
	}

	private function source_requirements( array $strategy, $page_type ) {
		$requirements = $this->lines( $strategy['evidence_requirements'] ?? '' );
		if ( ! $requirements ) {
			$requirements[] = 'Use primary or authoritative sources for factual claims that are not established business facts.';
		}
		if ( in_array( $page_type, array( 'article','guide','comparison','review' ), true ) ) {
			$requirements[] = 'Record source URLs and access dates during drafting.';
		}
		return array_values( array_unique( $requirements ) );
	}

	private function post_type_for_page_type( $page_type ) {
		return in_array( sanitize_key( $page_type ), array( 'article','guide','comparison','review','news' ), true ) ? 'post' : 'page';
	}

	private function extract_lists( array $data, array $keys, $limit ) {
		$result = array();
		foreach ( $keys as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}
			$values = is_array( $data[ $key ] ) ? $data[ $key ] : preg_split( '/[\r\n,]+/', (string) $data[ $key ] );
			foreach ( $values as $value ) {
				if ( is_array( $value ) ) {
					$value = $value['label'] ?? $value['query'] ?? $value['name'] ?? '';
				}
				$value = substr( sanitize_text_field( $value ), 0, 160 );
				if ( $value ) {
					$result[] = $value;
				}
			}
		}
		return array_slice( array_values( array_unique( $result ) ), 0, $limit );
	}

	private function lines( $value ) {
		if ( is_array( $value ) ) {
			$parts = $value;
		} else {
			$parts = preg_split( '/[\r\n,]+/', (string) $value );
		}
		return array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $parts ) ) ) );
	}

	private function sanitize_recursive( $value, $depth = 0 ) {
		if ( $depth > 7 ) {
			return array();
		}
		$result = array();
		foreach ( array_slice( (array) $value, 0, 100 ) as $key => $item ) {
			$key = is_int( $key ) ? $key : sanitize_key( $key );
			if ( is_array( $item ) ) {
				$result[ $key ] = $this->sanitize_recursive( $item, $depth + 1 );
			} else {
				$result[ $key ] = substr( wp_kses_post( (string) $item ), 0, 20000 );
			}
		}
		return $result;
	}

	private function decode_json( $value ) {
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function normalize( $value ) {
		$value = remove_accents( strtolower( wp_strip_all_tags( (string) $value ) ) );
		return trim( preg_replace( '/\s+/', ' ', preg_replace( '/[^a-z0-9\s]+/', ' ', $value ) ) );
	}

	private function columns_ready() {
		global $wpdb;
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$this->table()}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return ! array_diff( array( 'opportunity_id','publisher_item_id','brief_version','evidence_hash','approved_by','draft_post_id' ), (array) $columns );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	private function clear_cache() {
		delete_transient( self::CACHE_KEY );
		delete_transient( Ikon_SEO_Publisher_Intelligence::CACHE_KEY );
		delete_transient( Ikon_SEO_Competitor_Content_Intelligence::CACHE_KEY );
	}

	private function record_history( $category, $title, $summary, $brief_id, $user_id, $post_id = 0 ) {
		$this->history->add( array( 'category' => $category, 'status' => 'open', 'title' => $title, 'summary' => $summary, 'details' => array( 'brief_id' => absint( $brief_id ) ), 'related_post_id' => absint( $post_id ) ), 'content_workbench', $user_id );
		$this->logger->log( 'content_workbench', 'success', $summary, absint( $post_id ), absint( $brief_id ) );
	}
}
