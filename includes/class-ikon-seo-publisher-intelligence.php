<?php

defined( 'ABSPATH' ) || exit;

/**
 * Publisher Intelligence.
 *
 * Provides an evidence-led publishing system for editorial, affiliate,
 * advertising, local-business and hybrid sites. It stores opportunities,
 * topic hubs, editorial assignments, privacy-preserving portfolio signatures
 * and quality-gate evidence. It never publishes or removes content.
 */
final class Ikon_SEO_Publisher_Intelligence {
	const CACHE_KEY = 'ikon_seo_publisher_report';
	const META_ROLES = '_ikon_seo_publisher_roles';
	const META_EXPERTISE = '_ikon_seo_publisher_expertise';
	const META_EVIDENCE = '_ikon_seo_publisher_evidence';
	const META_ACTIVE = '_ikon_seo_publisher_active';
	const POST_META_REVIEW = '_ikon_seo_publisher_review';

	private $profile;
	private $strategy;
	private $inventory;
	private $search_intelligence;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Strategy $strategy,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->profile             = $profile;
		$this->strategy            = $strategy;
		$this->inventory           = $inventory;
		$this->search_intelligence = $search_intelligence;
		$this->history             = $history;
		$this->logger              = $logger;
	}


	public function register_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'mark_review_stale' ), 20, 3 );
	}

	public function register_meta_boxes() {
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $post_type ) {
			if ( 'attachment' === $post_type ) {
				continue;
			}
			add_meta_box( 'ikon-seo-publisher-review', __( 'Ikon SEO: Publisher Review', 'ikon-seo' ), array( $this, 'render_meta_box' ), $post_type, 'side', 'default' );
		}
	}

	public function render_meta_box( WP_Post $post ) {
		$review = get_post_meta( $post->ID, self::POST_META_REVIEW, true );
		$item   = $this->item_for_post( $post->ID );
		if ( ! $item && ! $review ) {
			echo '<p>' . esc_html__( 'This content is not linked to the Publisher Intelligence pipeline yet.', 'ikon-seo' ) . '</p>';
			echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=ikon-seo&tab=publisher-intelligence' ) ) . '">' . esc_html__( 'Open Publisher Intelligence', 'ikon-seo' ) . '</a></p>';
			return;
		}
		if ( $item ) {
			echo '<p><strong>' . esc_html__( 'Pipeline stage:', 'ikon-seo' ) . '</strong> ' . esc_html( ucwords( str_replace( '_', ' ', $item['stage'] ) ) ) . '</p>';
		}
		if ( is_array( $review ) && ! empty( $review ) ) {
			echo '<p><strong>' . esc_html__( 'Quality gate:', 'ikon-seo' ) . '</strong> ' . esc_html( absint( $review['quality_score'] ?? 0 ) ) . '/100</p>';
			echo '<p><strong>' . esc_html__( 'Status:', 'ikon-seo' ) . '</strong> ' . esc_html( ucwords( str_replace( '_', ' ', sanitize_key( $review['gate_status'] ?? 'not_reviewed' ) ) ) ) . '</p>';
			echo '<p class="description">' . esc_html__( 'A passed gate means ready for human review. It does not publish this content.', 'ikon-seo' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'The linked pipeline item has not been evaluated or the saved review is stale.', 'ikon-seo' ) . '</p>';
		}
		echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=ikon-seo&tab=publisher-intelligence' ) ) . '">' . esc_html__( 'Review in Ikon SEO', 'ikon-seo' ) . '</a></p>';
	}

	public function mark_review_stale( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! $update || ! $post instanceof WP_Post ) {
			return;
		}
		if ( ! get_post_meta( $post_id, self::POST_META_REVIEW, true ) ) {
			return;
		}
		delete_post_meta( $post_id, self::POST_META_REVIEW );
		$item = $this->item_for_post( $post_id );
		if ( ! empty( $item['id'] ) ) {
			global $wpdb;
			$wpdb->update( $this->items_table(), array( 'quality_score' => 0, 'gate_status' => 'stale', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $item['id'] ) ) );
			$this->clear_cache();
		}
	}

	public function keywords_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_publisher_keywords';
	}

	public function hubs_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_publisher_hubs';
	}

	public function items_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_publisher_items';
	}

	public function signatures_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_portfolio_signatures';
	}

	public function status() {
		global $wpdb;
		$ready = $this->tables_ready();
		$counts = array(
			'keywords' => 0,
			'hubs' => 0,
			'pipeline' => 0,
			'awaiting_review' => 0,
			'refresh_due' => 0,
			'portfolio_signatures' => 0,
		);
		if ( $ready ) {
			$profile_id = $this->profile_id();
			$counts['keywords'] = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->keywords_table()} WHERE profile_id=%s AND status <> 'archived'", $profile_id ) ) );
			$counts['hubs'] = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->hubs_table()} WHERE profile_id=%s AND status <> 'archived'", $profile_id ) ) );
			$counts['pipeline'] = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->items_table()} WHERE profile_id=%s AND stage NOT IN ('published','archived')", $profile_id ) ) );
			$counts['awaiting_review'] = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->items_table()} WHERE profile_id=%s AND stage IN ('review','revision','ready')", $profile_id ) ) );
			$counts['refresh_due'] = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->items_table()} WHERE profile_id=%s AND refresh_due IS NOT NULL AND refresh_due <= %s AND stage <> 'archived'", $profile_id, gmdate( 'Y-m-d' ) ) ) );
			$counts['portfolio_signatures'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->signatures_table()}" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return array(
			'enabled' => ! empty( Ikon_SEO_Plugin::settings()['publisher_intelligence_enabled'] ),
			'database_ready' => $ready,
			'counts' => $counts,
			'website_mode' => sanitize_key( $this->strategy->get()['mode'] ?? 'local_business' ),
			'quality_threshold' => max( 50, min( 100, absint( $this->strategy->get()['quality_gate_threshold'] ?? 80 ) ) ),
			'publishing_is_automatic' => false,
		);
	}

	public function sync( array $payload, $created_by = 0 ) {
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_publisher_tables', __( 'Publisher Intelligence tables are not ready. Update or reactivate Ikon SEO.', 'ikon-seo' ) );
		}
		$command = sanitize_key( $payload['command'] ?? 'read' );
		$result = array();
		switch ( $command ) {
			case 'save_keyword':
				$result = $this->save_keyword( (array) ( $payload['keyword'] ?? array() ), $created_by );
				break;
			case 'save_hub':
				$result = $this->save_hub( (array) ( $payload['hub'] ?? array() ), $created_by );
				break;
			case 'save_item':
				$result = $this->save_item( (array) ( $payload['item'] ?? array() ), $created_by );
				break;
			case 'save_contributor':
				$result = $this->save_contributor( (array) ( $payload['contributor'] ?? array() ) );
				break;
			case 'evaluate_post':
				$result = $this->evaluate_post( absint( $payload['post_id'] ?? 0 ), absint( $payload['item_id'] ?? 0 ), true, $created_by );
				break;
			case 'generate_calendar':
				$result = $this->generate_calendar( absint( $payload['weeks'] ?? 12 ), absint( $payload['capacity_per_month'] ?? 0 ), $created_by );
				break;
			case 'review_lifecycle':
				$result = $this->review_lifecycle( absint( $payload['limit'] ?? 50 ), true, $created_by );
				break;
			case 'save_signature':
				$result = $this->save_portfolio_signature( (array) ( $payload['signature'] ?? array() ), $created_by );
				break;
			case 'read':
			default:
				$result = array( 'read_only' => true );
				break;
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return array(
			'command' => $command,
			'result' => $result,
			'report' => $this->report( absint( $payload['limit'] ?? 100 ), true ),
		);
	}

	public function report( $limit = 100, $refresh = false ) {
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
			return array( 'status' => $status, 'keywords' => array(), 'hubs' => array(), 'pipeline' => array(), 'contributors' => array(), 'lifecycle' => array(), 'limitations' => array( 'Database upgrade required.' ) );
		}
		$profile_id = $this->profile_id();
		global $wpdb;
		$keywords = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->keywords_table()} WHERE profile_id=%s AND status <> 'archived' ORDER BY priority DESC, updated_at DESC LIMIT %d", $profile_id, $limit ), ARRAY_A );
		$hubs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->hubs_table()} WHERE profile_id=%s AND status <> 'archived' ORDER BY readiness DESC, updated_at DESC LIMIT %d", $profile_id, $limit ), ARRAY_A );
		$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->items_table()} WHERE profile_id=%s AND stage <> 'archived' ORDER BY FIELD(stage,'review','revision','ready','drafting','brief','research','planned','idea','refresh','consolidate','retire','published'), due_at IS NULL, due_at ASC, priority DESC LIMIT %d", $profile_id, $limit ), ARRAY_A );
		$report = array(
			'status' => $status,
			'keywords' => array_map( array( $this, 'prepare_keyword' ), $keywords ?: array() ),
			'hubs' => array_map( array( $this, 'prepare_hub' ), $hubs ?: array() ),
			'pipeline' => array_map( array( $this, 'prepare_item' ), $items ?: array() ),
			'contributors' => $this->contributors(),
			'lifecycle' => $this->review_lifecycle( 30, false, 0 ),
			'portfolio' => $this->portfolio_summary(),
			'quality_policy' => $this->quality_policy(),
			'limitations' => array(
				'Keyword demand and difficulty bands are imported or researched estimates; they are not measured by WordPress.',
				'Portfolio duplication checks compare privacy-preserving content signatures and topic terms, not full-text copies.',
				'Lifecycle recommendations require human review before consolidation, retirement, redirecting or publication.',
			),
			'generated_at' => current_time( 'mysql', true ),
		);
		set_transient( self::CACHE_KEY, $report, 15 * MINUTE_IN_SECONDS );
		return $report;
	}

	public function save_keyword( array $data, $created_by = 0 ) {
		global $wpdb;
		$keyword = sanitize_text_field( $data['keyword'] ?? '' );
		if ( ! $keyword ) {
			return new WP_Error( 'ikon_seo_publisher_keyword', __( 'A keyword or opportunity phrase is required.', 'ikon-seo' ) );
		}
		$profile_id = $this->profile_id();
		$country = strtoupper( substr( sanitize_text_field( $data['country'] ?? '' ), 0, 3 ) );
		$language = substr( sanitize_key( $data['language'] ?? Ikon_SEO_Profile::locale() ), 0, 16 );
		$hash = hash( 'sha256', $profile_id . '|' . $this->normalize_text( $keyword ) . '|' . $country . '|' . $language );
		$intent = $this->allowed( $data['intent'] ?? 'mixed', array( 'local_service', 'transactional', 'commercial', 'informational', 'navigational', 'mixed' ), 'mixed' );
		$page_type = $this->allowed( $data['page_type'] ?? 'article', array( 'article', 'guide', 'comparison', 'review', 'news', 'service', 'location', 'category', 'product', 'tool', 'hub', 'other' ), 'article' );
		$demand = $this->allowed( $data['demand_band'] ?? 'unknown', array( 'unknown', 'very_low', 'low', 'medium', 'high', 'very_high' ), 'unknown' );
		$difficulty = $this->allowed( $data['difficulty_band'] ?? 'unknown', array( 'unknown', 'low', 'medium', 'high', 'very_high' ), 'unknown' );
		$business_value = max( 0, min( 100, absint( $data['business_value'] ?? 50 ) ) );
		$priority = $this->opportunity_priority( $demand, $difficulty, $business_value, $intent );
		$now = current_time( 'mysql', true );
		$sql = "INSERT INTO {$this->keywords_table()} (profile_id,keyword_hash,keyword_text,cluster_name,intent,page_type,country,language,demand_band,difficulty_band,business_value,priority,status,target_post_id,source,notes,created_by,created_at,updated_at)
		VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%d,%s,%d,%s,%s,%d,%s,%s)
		ON DUPLICATE KEY UPDATE cluster_name=VALUES(cluster_name), intent=VALUES(intent), page_type=VALUES(page_type), demand_band=VALUES(demand_band), difficulty_band=VALUES(difficulty_band), business_value=VALUES(business_value), priority=VALUES(priority), status=VALUES(status), target_post_id=VALUES(target_post_id), source=VALUES(source), notes=VALUES(notes), updated_at=VALUES(updated_at)";
		$result = $wpdb->query( $wpdb->prepare(
			$sql,
			$profile_id, $hash, $keyword, sanitize_text_field( $data['cluster'] ?? '' ), $intent, $page_type, $country, $language, $demand, $difficulty,
			$business_value, $priority, $this->allowed( $data['status'] ?? 'idea', array( 'idea','planned','briefed','drafting','published','declined','archived' ), 'idea' ),
			absint( $data['target_post_id'] ?? 0 ), sanitize_key( $data['source'] ?? 'manual' ), sanitize_textarea_field( $data['notes'] ?? '' ), absint( $created_by ), $now, $now
		) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_publisher_keyword_store', __( 'The publishing opportunity could not be stored.', 'ikon-seo' ) );
		}
		$id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->keywords_table()} WHERE profile_id=%s AND keyword_hash=%s", $profile_id, $hash ) ) );
		$this->clear_cache();
		return $this->get_keyword( $id );
	}

	public function save_hub( array $data, $created_by = 0 ) {
		global $wpdb;
		$title = sanitize_text_field( $data['title'] ?? '' );
		if ( ! $title ) {
			return new WP_Error( 'ikon_seo_publisher_hub_title', __( 'A topic-hub title is required.', 'ikon-seo' ) );
		}
		$id = absint( $data['id'] ?? 0 );
		$profile_id = $this->profile_id();
		$slug = sanitize_title( $data['slug'] ?? $title );
		$keyword_ids = $this->sanitize_ids( $data['keyword_ids'] ?? array(), 500 );
		$supporting_ids = $this->sanitize_ids( $data['supporting_post_ids'] ?? array(), 500 );
		$readiness = $this->hub_readiness( absint( $data['pillar_post_id'] ?? 0 ), $keyword_ids, $supporting_ids, $data );
		$row = array(
			'profile_id' => $profile_id,
			'title' => $title,
			'slug' => $slug,
			'description' => sanitize_textarea_field( $data['description'] ?? '' ),
			'target_audience' => sanitize_textarea_field( $data['target_audience'] ?? '' ),
			'monetization_goal' => sanitize_text_field( $data['monetization_goal'] ?? '' ),
			'pillar_post_id' => absint( $data['pillar_post_id'] ?? 0 ),
			'keyword_ids_json' => wp_json_encode( $keyword_ids ),
			'supporting_post_ids_json' => wp_json_encode( $supporting_ids ),
			'readiness' => $readiness,
			'status' => $this->allowed( $data['status'] ?? 'planned', array( 'planned','active','complete','archived' ), 'planned' ),
			'updated_at' => current_time( 'mysql', true ),
		);
		if ( $id ) {
			$updated = $wpdb->update( $this->hubs_table(), $row, array( 'id' => $id, 'profile_id' => $profile_id ) );
			if ( false === $updated ) {
				return new WP_Error( 'ikon_seo_publisher_hub_update', __( 'The topic hub could not be updated.', 'ikon-seo' ) );
			}
		} else {
			$row['created_by'] = absint( $created_by );
			$row['created_at'] = current_time( 'mysql', true );
			$inserted = $wpdb->insert( $this->hubs_table(), $row );
			if ( false === $inserted ) {
				return new WP_Error( 'ikon_seo_publisher_hub_store', __( 'The topic hub could not be stored.', 'ikon-seo' ) );
			}
			$id = absint( $wpdb->insert_id );
		}
		$this->clear_cache();
		return $this->get_hub( $id );
	}

	public function save_item( array $data, $created_by = 0 ) {
		global $wpdb;
		$title = sanitize_text_field( $data['title'] ?? '' );
		if ( ! $title ) {
			return new WP_Error( 'ikon_seo_publisher_item_title', __( 'A content-pipeline title is required.', 'ikon-seo' ) );
		}
		$id = absint( $data['id'] ?? 0 );
		$profile_id = $this->profile_id();
		$stage = $this->allowed( $data['stage'] ?? 'idea', $this->stages(), 'idea' );
		$due = sanitize_text_field( $data['due_at'] ?? '' );
		if ( $due && ! preg_match( '/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?$/', $due ) ) {
			$due = '';
		}
		if ( $due && 10 === strlen( $due ) ) {
			$due .= ' 09:00:00';
		}
		$refresh_due = sanitize_text_field( $data['refresh_due'] ?? '' );
		if ( $refresh_due && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $refresh_due ) ) {
			$refresh_due = '';
		}
		$brief = $this->sanitize_recursive( (array) ( $data['brief'] ?? array() ) );
		$row = array(
			'profile_id' => $profile_id,
			'keyword_id' => absint( $data['keyword_id'] ?? 0 ),
			'hub_id' => absint( $data['hub_id'] ?? 0 ),
			'title' => $title,
			'content_type' => $this->allowed( $data['content_type'] ?? 'article', array( 'article','guide','comparison','review','news','service','location','category','product','tool','hub','other' ), 'article' ),
			'intent' => $this->allowed( $data['intent'] ?? 'mixed', array( 'local_service','transactional','commercial','informational','navigational','mixed' ), 'mixed' ),
			'stage' => $stage,
			'priority' => max( 0, min( 100, absint( $data['priority'] ?? 50 ) ) ),
			'author_id' => $this->valid_user_id( $data['author_id'] ?? 0 ),
			'reviewer_id' => $this->valid_user_id( $data['reviewer_id'] ?? 0 ),
			'due_at' => $due ?: null,
			'target_post_id' => absint( $data['target_post_id'] ?? 0 ),
			'source_requirements' => sanitize_textarea_field( $data['source_requirements'] ?? '' ),
			'evidence_notes' => sanitize_textarea_field( $data['evidence_notes'] ?? '' ),
			'originality_required' => array_key_exists( 'originality_required', $data ) ? ( empty( $data['originality_required'] ) ? 0 : 1 ) : 1,
			'disclosure_required' => empty( $data['disclosure_required'] ) ? 0 : 1,
			'brief_json' => wp_json_encode( $brief ),
			'lifecycle_action' => $this->allowed( $data['lifecycle_action'] ?? 'none', array( 'none','keep','update','consolidate','redirect_review','retire_review' ), 'none' ),
			'refresh_due' => $refresh_due ?: null,
			'notes' => sanitize_textarea_field( $data['notes'] ?? '' ),
			'updated_at' => current_time( 'mysql', true ),
		);
		if ( $id ) {
			$updated = $wpdb->update( $this->items_table(), $row, array( 'id' => $id, 'profile_id' => $profile_id ) );
			if ( false === $updated ) {
				return new WP_Error( 'ikon_seo_publisher_item_update', __( 'The pipeline item could not be updated.', 'ikon-seo' ) );
			}
		} else {
			$row['created_by'] = absint( $created_by );
			$row['created_at'] = current_time( 'mysql', true );
			$inserted = $wpdb->insert( $this->items_table(), $row );
			if ( false === $inserted ) {
				return new WP_Error( 'ikon_seo_publisher_item_store', __( 'The pipeline item could not be stored.', 'ikon-seo' ) );
			}
			$id = absint( $wpdb->insert_id );
		}
		$this->clear_cache();
		return $this->get_item( $id );
	}

	public function save_contributor( array $data ) {
		$user_id = $this->valid_user_id( $data['user_id'] ?? 0 );
		if ( ! $user_id ) {
			return new WP_Error( 'ikon_seo_publisher_contributor', __( 'Select a valid WordPress user.', 'ikon-seo' ) );
		}
		$roles = array_values( array_intersect( $this->sanitize_list( $data['roles'] ?? array(), 10, 30 ), array( 'author','editor','reviewer','fact_checker','subject_expert' ) ) );
		update_user_meta( $user_id, self::META_ROLES, $roles );
		update_user_meta( $user_id, self::META_EXPERTISE, sanitize_textarea_field( $data['expertise'] ?? '' ) );
		update_user_meta( $user_id, self::META_EVIDENCE, sanitize_textarea_field( $data['evidence'] ?? '' ) );
		update_user_meta( $user_id, self::META_ACTIVE, empty( $data['active'] ) ? 0 : 1 );
		$this->clear_cache();
		return $this->contributor( $user_id );
	}

	public function generate_calendar( $weeks = 12, $capacity_per_month = 0, $created_by = 0 ) {
		global $wpdb;
		$weeks = max( 1, min( 52, absint( $weeks ) ) );
		$strategy = $this->strategy->get();
		$capacity = $capacity_per_month ? absint( $capacity_per_month ) : absint( $strategy['publishing_capacity'] ?? 4 );
		$capacity = max( 1, min( 100, $capacity ) );
		$total = max( 1, (int) ceil( $capacity * $weeks / 4.345 ) );
		$profile_id = $this->profile_id();
		$keywords = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->keywords_table()} WHERE profile_id=%s AND status IN ('idea','planned') ORDER BY priority DESC, id ASC LIMIT %d", $profile_id, $total ), ARRAY_A );
		$created = array();
		$start = strtotime( 'next monday 09:00 UTC' );
		$per_week = max( 1, (int) ceil( $capacity / 4.345 ) );
		foreach ( $keywords ?: array() as $index => $keyword ) {
			$exists = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->items_table()} WHERE profile_id=%s AND keyword_id=%d AND stage <> 'archived' LIMIT 1", $profile_id, absint( $keyword['id'] ) ) ) );
			if ( $exists ) {
				continue;
			}
			$week_index = (int) floor( count( $created ) / $per_week );
			if ( $week_index >= $weeks ) {
				break;
			}
			$day_offset = count( $created ) % $per_week;
			$due = gmdate( 'Y-m-d H:i:s', strtotime( '+' . $week_index . ' weeks +' . $day_offset . ' days', $start ) );
			$item = $this->save_item(
				array(
					'keyword_id' => absint( $keyword['id'] ),
					'title' => sanitize_text_field( $keyword['keyword_text'] ),
					'content_type' => sanitize_key( $keyword['page_type'] ),
					'intent' => sanitize_key( $keyword['intent'] ),
					'stage' => 'planned',
					'priority' => absint( $keyword['priority'] ),
					'due_at' => $due,
					'originality_required' => 1,
					'disclosure_required' => in_array( sanitize_key( $strategy['monetization_model'] ?? '' ), array( 'affiliate','advertising','sponsorship','mixed' ), true ) ? 1 : 0,
					'source_requirements' => sanitize_textarea_field( $strategy['evidence_requirements'] ?? '' ),
				),
				$created_by
			);
			if ( ! is_wp_error( $item ) ) {
				$created[] = $item;
				$wpdb->update( $this->keywords_table(), array( 'status' => 'planned', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $keyword['id'] ) ) );
			}
		}
		if ( $created ) {
			$this->history->add( array( 'category' => 'page_plan', 'status' => 'open', 'title' => 'Editorial calendar generated', 'summary' => sprintf( 'Created %d planned publishing items across %d weeks.', count( $created ), $weeks ), 'details' => array( 'capacity_per_month' => $capacity, 'item_ids' => wp_list_pluck( $created, 'id' ) ) ), 'publisher', $created_by );
		}
		$this->clear_cache();
		return array( 'created' => count( $created ), 'weeks' => $weeks, 'capacity_per_month' => $capacity, 'items' => $created );
	}

	public function evaluate_post( $post_id, $item_id = 0, $store = true, $created_by = 0 ) {
		$post = get_post( absint( $post_id ) );
		if ( ! $post || ! in_array( $post->post_type, get_post_types( array( 'public' => true ) ), true ) ) {
			return new WP_Error( 'ikon_seo_publisher_post', __( 'Select a valid public WordPress post, page or product.', 'ikon-seo' ) );
		}
		$item = $item_id ? $this->get_item( $item_id ) : $this->item_for_post( $post->ID );
		$keyword = array();
		if ( ! empty( $item['keyword_id'] ) ) {
			$keyword = $this->get_keyword( absint( $item['keyword_id'] ) );
		}
		$target = sanitize_text_field( $keyword['keyword_text'] ?? '' );
		$content = (string) $post->post_content;
		$plain = wp_strip_all_tags( strip_shortcodes( $content ) );
		$tokens = $this->meaningful_tokens( $post->post_title . ' ' . $plain );
		$signature = $this->signature_from_tokens( $tokens );
		$local_similarity = $this->find_local_similarity( $post->ID, $tokens );
		$portfolio_similarity = $this->find_portfolio_similarity( $signature );
		$links = $this->link_counts( $content );
		$author = $this->contributor( absint( $item['author_id'] ?? $post->post_author ) );
		$reviewer = ! empty( $item['reviewer_id'] ) ? $this->contributor( absint( $item['reviewer_id'] ) ) : array();
		$strategy = $this->strategy->get();
		$similarity_thresholds = $this->similarity_thresholds();
		$threshold = max( 50, min( 100, absint( $strategy['quality_gate_threshold'] ?? 80 ) ) );
		$title_alignment = $target ? $this->token_coverage( $this->meaningful_tokens( $target ), $this->meaningful_tokens( $post->post_title ) ) : 0.5;
		$content_alignment = $target ? $this->token_coverage( $this->meaningful_tokens( $target ), $tokens ) : 0.5;
		$word_count = str_word_count( $plain );
		$requires_originality = ! array_key_exists( 'originality_required', $item ) || ! empty( $item['originality_required'] );
		$requires_disclosure = ! empty( $item['disclosure_required'] ) || in_array( sanitize_key( $strategy['monetization_model'] ?? '' ), array( 'affiliate','advertising','sponsorship','mixed' ), true );
		$has_disclosure = (bool) preg_match( '/\b(affiliate|sponsored|advertis(?:e|ing)|commission|disclosure)\b/i', $plain );
		$requires_sources = ! empty( $item['source_requirements'] ) || in_array( sanitize_key( $item['content_type'] ?? $post->post_type ), array( 'guide','comparison','review','news' ), true );
		$source_score = $requires_sources ? min( 1, $links['external'] / 2 ) : ( $links['external'] ? 1 : 0.8 );
		$author_score = ! empty( $author['active'] ) && ! empty( $author['expertise'] ) ? 1 : ( $author ? 0.5 : 0 );
		$reviewer_score = ! empty( $reviewer['active'] ) && ! empty( $reviewer['expertise'] ) ? 1 : ( $reviewer ? 0.5 : 0.65 );
		$originality_score = max( 0, 1 - max( (float) ( $local_similarity['score'] ?? 0 ), (float) ( $portfolio_similarity['score'] ?? 0 ) ) );
		$alignment_score = min( 1, ( 0.4 * $title_alignment ) + ( 0.6 * $content_alignment ) );
		$internal_score = min( 1, $links['internal'] / 3 );
		$brief_coverage = $this->brief_coverage_score( (array) ( $item['brief'] ?? array() ), $tokens );
		$disclosure_score = $requires_disclosure ? ( $has_disclosure ? 1 : 0 ) : 1;
		$score = (int) round( 100 * ( 0.22 * $alignment_score + 0.22 * $originality_score + 0.15 * $source_score + 0.14 * $author_score + 0.08 * $reviewer_score + 0.09 * $internal_score + 0.05 * $brief_coverage + 0.05 * $disclosure_score ) );
		$critical = array();
		$findings = array();
		if ( $target && $alignment_score < 0.35 ) {
			$critical[] = 'The draft has weak semantic alignment with its approved target opportunity.';
		}
		if ( $requires_originality && (float) ( $local_similarity['score'] ?? 0 ) >= $similarity_thresholds['local'] ) {
			$critical[] = 'The draft is highly similar to another page on this website.';
		}
		if ( $requires_originality && (float) ( $portfolio_similarity['score'] ?? 0 ) >= $similarity_thresholds['portfolio'] ) {
			$critical[] = 'The draft is highly similar to an imported portfolio signature.';
		}
		if ( ! $requires_originality && max( (float) ( $local_similarity['score'] ?? 0 ), (float) ( $portfolio_similarity['score'] ?? 0 ) ) >= min( $similarity_thresholds['local'], $similarity_thresholds['portfolio'] ) ) {
			$findings[] = 'High content similarity was detected, but originality blocking is disabled for this pipeline item.';
		}
		if ( $requires_sources && $links['external'] < 1 ) {
			$critical[] = 'The approved evidence policy requires sources, but no external source link was detected.';
		}
		if ( $requires_disclosure && ! $has_disclosure ) {
			$critical[] = 'A commercial disclosure appears to be required but was not detected.';
		}
		if ( empty( $author['active'] ) || empty( $author['expertise'] ) ) {
			$findings[] = 'The assigned author does not have a complete active expertise profile.';
		}
		if ( $links['internal'] < 1 ) {
			$findings[] = 'No contextual internal link was detected in the saved content.';
		}
		$gate = $critical ? 'blocked' : ( $score >= $threshold ? 'passed' : 'revision' );
		$review = array(
			'post_id' => $post->ID,
			'item_id' => absint( $item['id'] ?? 0 ),
			'title' => get_the_title( $post ),
			'url' => get_permalink( $post ),
			'target_keyword' => $target,
			'quality_score' => $score,
			'quality_threshold' => $threshold,
			'gate_status' => $gate,
			'word_count' => $word_count,
			'links' => $links,
			'alignment' => round( $alignment_score * 100, 1 ),
			'originality' => round( $originality_score * 100, 1 ),
			'originality_required' => $requires_originality,
			'brief_coverage' => round( $brief_coverage * 100, 1 ),
			'local_similarity' => $local_similarity,
			'portfolio_similarity' => $portfolio_similarity,
			'similarity_thresholds' => array( 'local' => round( $similarity_thresholds['local'] * 100, 1 ), 'portfolio' => round( $similarity_thresholds['portfolio'] * 100, 1 ) ),
			'author' => $author,
			'reviewer' => $reviewer,
			'critical_findings' => $critical,
			'improvement_findings' => $findings,
			'signature' => $signature,
			'evaluated_at' => current_time( 'mysql', true ),
			'safety_note' => 'Passing this gate means ready for human review, not approved for automatic publication.',
		);
		if ( $store ) {
			update_post_meta( $post->ID, self::POST_META_REVIEW, $review );
			if ( ! empty( $item['id'] ) ) {
				global $wpdb;
				$wpdb->update( $this->items_table(), array( 'quality_score' => $score, 'gate_status' => $gate, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $item['id'] ) ) );
			}
			$this->history->add( array( 'category' => 'approval', 'status' => 'open', 'title' => 'Publisher quality gate reviewed', 'summary' => sprintf( '%s received a quality-gate score of %d with status %s.', get_the_title( $post ), $score, $gate ), 'details' => array( 'quality_score' => $score, 'gate_status' => $gate, 'critical_findings' => $critical ), 'related_post_id' => $post->ID ), 'publisher', $created_by );
		}
		$this->clear_cache();
		return $review;
	}

	public function review_lifecycle( $limit = 50, $store = false, $created_by = 0 ) {
		$limit = max( 1, min( 200, absint( $limit ) ) );
		$strategy = $this->strategy->get();
		$similarity_thresholds = $this->similarity_thresholds();
		$refresh_days = max( 30, min( 730, absint( $strategy['editorial_refresh_days'] ?? 180 ) ) );
		$posts = get_posts( array( 'post_type' => get_post_types( array( 'public' => true ) ), 'post_status' => 'publish', 'posts_per_page' => $limit, 'orderby' => 'modified', 'order' => 'ASC', 'fields' => 'all' ) );
		$results = array();
		foreach ( $posts as $post ) {
			$url = get_permalink( $post );
			$search = $this->search_intelligence->page_summary( $url );
			$performance = (array) ( $search['performance'] ?? array() );
			$decay = (array) ( $search['content_decay'] ?? array() );
			$age_days = max( 0, (int) floor( ( time() - strtotime( $post->post_modified_gmt . ' UTC' ) ) / DAY_IN_SECONDS ) );
			$review = get_post_meta( $post->ID, self::POST_META_REVIEW, true );
			$similarity = (float) ( $review['local_similarity']['score'] ?? 0 );
			$impressions = (float) ( $performance['impressions'] ?? 0 );
			$clicks = (float) ( $performance['clicks'] ?? 0 );
			$action = 'keep';
			$confidence = 'medium';
			$reasons = array();
			if ( $similarity >= $similarity_thresholds['local'] ) {
				$action = 'consolidate';
				$confidence = 'high';
				$reasons[] = 'High same-site similarity was detected in the latest quality review.';
			} elseif ( $decay ) {
				$action = 'update';
				$confidence = 'high';
				$reasons[] = 'Search visibility decline was detected in stored Search Console evidence.';
			} elseif ( $age_days >= $refresh_days ) {
				$action = 'update';
				$confidence = 'medium';
				$reasons[] = 'The content has exceeded the configured refresh interval.';
			} elseif ( $age_days > 730 && $impressions <= 1 && $clicks <= 0 ) {
				$action = 'retire_review';
				$confidence = 'low';
				$reasons[] = 'The page is old and has almost no stored search visibility; intent, links and conversions must be checked before retirement.';
			} else {
				$reasons[] = 'No strong refresh, consolidation or retirement signal was detected.';
			}
			$item = array( 'post_id' => $post->ID, 'title' => get_the_title( $post ), 'url' => $url, 'modified_days_ago' => $age_days, 'impressions' => $impressions, 'clicks' => $clicks, 'recommended_action' => $action, 'confidence' => $confidence, 'reasons' => $reasons );
			$results[] = $item;
			if ( $store && 'keep' !== $action ) {
				$this->upsert_lifecycle_item( $post, $action, $refresh_days, $created_by );
			}
		}
		if ( $store ) {
			$this->history->add( array( 'category' => 'audit', 'status' => 'open', 'title' => 'Content lifecycle review completed', 'summary' => sprintf( 'Reviewed %d published items for refresh, consolidation and retirement signals.', count( $results ) ), 'details' => array( 'reviewed' => count( $results ), 'refresh_days' => $refresh_days ) ), 'publisher', $created_by );
			$this->clear_cache();
		}
		return $results;
	}

	public function save_portfolio_signature( array $data, $created_by = 0 ) {
		global $wpdb;
		$site_label = sanitize_text_field( $data['site_label'] ?? '' );
		$content_url = esc_url_raw( $data['content_url'] ?? '' );
		$signature = $this->sanitize_signature( $data['signature'] ?? array() );
		if ( ! $site_label || ! $signature ) {
			return new WP_Error( 'ikon_seo_publisher_signature', __( 'A site label and a privacy-preserving content signature are required.', 'ikon-seo' ) );
		}
		$hash = hash( 'sha256', strtolower( $site_label ) . '|' . $content_url . '|' . implode( '|', $signature ) );
		$now = current_time( 'mysql', true );
		$sql = "INSERT INTO {$this->signatures_table()} (signature_hash,site_label,site_url,content_url,content_title,content_type,signature_json,topics_json,created_by,created_at,updated_at)
		VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s)
		ON DUPLICATE KEY UPDATE site_label=VALUES(site_label), site_url=VALUES(site_url), content_url=VALUES(content_url), content_title=VALUES(content_title), content_type=VALUES(content_type), signature_json=VALUES(signature_json), topics_json=VALUES(topics_json), updated_at=VALUES(updated_at)";
		$result = $wpdb->query( $wpdb->prepare( $sql, $hash, $site_label, esc_url_raw( $data['site_url'] ?? '' ), $content_url, sanitize_text_field( $data['content_title'] ?? '' ), sanitize_key( $data['content_type'] ?? 'article' ), wp_json_encode( $signature ), wp_json_encode( $this->sanitize_list( $data['topics'] ?? array(), 100, 120 ) ), absint( $created_by ), $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_publisher_signature_store', __( 'The portfolio signature could not be stored.', 'ikon-seo' ) );
		}
		$this->clear_cache();
		return array( 'signature_hash' => $hash, 'site_label' => $site_label, 'content_url' => $content_url, 'signature_terms' => count( $signature ) );
	}

	public function export_signature_bundle( $limit = 500 ) {
		$limit = max( 1, min( 1000, absint( $limit ) ) );
		$posts = get_posts( array( 'post_type' => get_post_types( array( 'public' => true ) ), 'post_status' => array( 'publish','draft','pending' ), 'posts_per_page' => $limit, 'orderby' => 'modified', 'order' => 'DESC' ) );
		$items = array();
		foreach ( $posts as $post ) {
			$tokens = $this->meaningful_tokens( $post->post_title . ' ' . wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );
			$items[] = array( 'content_url' => get_permalink( $post ), 'content_title' => get_the_title( $post ), 'content_type' => $post->post_type, 'signature' => $this->signature_from_tokens( $tokens ), 'topics' => array_slice( array_values( array_unique( $tokens ) ), 0, 30 ) );
		}
		return array( 'format' => 'ikon-seo-portfolio-signatures-v1', 'site_label' => get_bloginfo( 'name' ), 'site_url' => home_url( '/' ), 'generated_at' => current_time( 'mysql', true ), 'items' => $items );
	}

	public function import_signature_bundle( array $bundle, $created_by = 0 ) {
		if ( 'ikon-seo-portfolio-signatures-v1' !== sanitize_text_field( $bundle['format'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_publisher_signature_format', __( 'The portfolio signature file format is not supported.', 'ikon-seo' ) );
		}
		$items = array_slice( (array) ( $bundle['items'] ?? array() ), 0, 1000 );
		$stored = 0;
		$skipped = 0;
		foreach ( $items as $item ) {
			$item = (array) $item;
			$item['site_label'] = sanitize_text_field( $bundle['site_label'] ?? '' );
			$item['site_url'] = esc_url_raw( $bundle['site_url'] ?? '' );
			$result = $this->save_portfolio_signature( $item, $created_by );
			if ( is_wp_error( $result ) ) {
				$skipped++;
			} else {
				$stored++;
			}
		}
		return array( 'stored' => $stored, 'skipped' => $skipped );
	}

	private function upsert_lifecycle_item( WP_Post $post, $action, $refresh_days, $created_by ) {
		$item = $this->item_for_post( $post->ID );
		$data = array(
			'id' => absint( $item['id'] ?? 0 ),
			'title' => get_the_title( $post ),
			'content_type' => 'post' === $post->post_type ? 'article' : $post->post_type,
			'stage' => 'consolidate' === $action ? 'consolidate' : ( 'retire_review' === $action ? 'retire' : 'refresh' ),
			'priority' => 'consolidate' === $action ? 85 : 70,
			'target_post_id' => $post->ID,
			'lifecycle_action' => $action,
			'refresh_due' => gmdate( 'Y-m-d', strtotime( '+' . $refresh_days . ' days' ) ),
			'notes' => 'Generated by the evidence-based lifecycle review. Human approval is required.',
		);
		return $this->save_item( $data, $created_by );
	}



	private function brief_coverage_score( array $brief, array $content_tokens ) {
		$requirements = array();
		$this->collect_brief_requirements( $brief, $requirements );
		$requirements = array_slice( array_values( array_unique( array_filter( $requirements ) ) ), 0, 40 );
		if ( ! $requirements ) {
			return 0.8;
		}
		$covered = 0;
		foreach ( $requirements as $requirement ) {
			$requirement_tokens = $this->meaningful_tokens( $requirement );
			if ( $requirement_tokens && $this->token_coverage( $requirement_tokens, $content_tokens ) >= 0.6 ) {
				$covered++;
			}
		}
		return count( $requirements ) ? $covered / count( $requirements ) : 0.8;
	}

	private function collect_brief_requirements( array $brief, array &$requirements, $depth = 0 ) {
		if ( $depth > 5 || count( $requirements ) >= 80 ) {
			return;
		}
		$accepted = array( 'required_sections', 'sections', 'topics', 'entities', 'questions', 'must_cover', 'coverage', 'key_points', 'subtopics' );
		foreach ( $brief as $key => $value ) {
			$key = sanitize_key( $key );
			if ( is_array( $value ) ) {
				if ( in_array( $key, $accepted, true ) ) {
					foreach ( $value as $entry ) {
						if ( is_scalar( $entry ) ) {
							$text = sanitize_text_field( (string) $entry );
							if ( $text ) { $requirements[] = $text; }
						}
					}
				}
				$this->collect_brief_requirements( $value, $requirements, $depth + 1 );
			} elseif ( in_array( $key, $accepted, true ) ) {
				foreach ( preg_split( '/[\r\n,]+/', (string) $value ) as $entry ) {
					$text = sanitize_text_field( $entry );
					if ( $text ) { $requirements[] = $text; }
				}
			}
		}
	}

	private function similarity_thresholds() {
		$settings = Ikon_SEO_Plugin::settings();
		return array(
			'local' => max( 0.5, min( 0.98, absint( $settings['publisher_local_similarity_threshold'] ?? 82 ) / 100 ) ),
			'portfolio' => max( 0.5, min( 0.98, absint( $settings['publisher_portfolio_similarity_threshold'] ?? 70 ) / 100 ) ),
		);
	}

	private function quality_policy() {
		$strategy = $this->strategy->get();
		return array(
			'threshold' => max( 50, min( 100, absint( $strategy['quality_gate_threshold'] ?? 80 ) ) ),
			'editorial_standards' => sanitize_textarea_field( $strategy['editorial_standards'] ?? '' ),
			'evidence_requirements' => sanitize_textarea_field( $strategy['evidence_requirements'] ?? '' ),
			'author_policy' => sanitize_textarea_field( $strategy['author_policy'] ?? '' ),
			'disclosure_policy' => sanitize_textarea_field( $strategy['disclosure_policy'] ?? '' ),
			'automatic_publication' => false,
		);
	}

	private function contributors() {
		$users = get_users( array( 'fields' => array( 'ID','display_name','user_email' ), 'orderby' => 'display_name' ) );
		$result = array();
		foreach ( $users as $user ) {
			$profile = $this->contributor( $user->ID );
			if ( $profile['roles'] || $profile['active'] ) {
				$result[] = $profile;
			}
		}
		return $result;
	}

	private function contributor( $user_id ) {
		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user ) {
			return array();
		}
		return array(
			'user_id' => absint( $user->ID ),
			'name' => sanitize_text_field( $user->display_name ),
			'roles' => array_values( array_filter( (array) get_user_meta( $user->ID, self::META_ROLES, true ) ) ),
			'expertise' => sanitize_textarea_field( get_user_meta( $user->ID, self::META_EXPERTISE, true ) ),
			'evidence' => sanitize_textarea_field( get_user_meta( $user->ID, self::META_EVIDENCE, true ) ),
			'active' => (bool) get_user_meta( $user->ID, self::META_ACTIVE, true ),
		);
	}

	private function portfolio_summary() {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT site_label, site_url, COUNT(*) AS total, MAX(updated_at) AS updated_at FROM {$this->signatures_table()} GROUP BY site_label,site_url ORDER BY total DESC LIMIT 100", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( function( $row ) { return array( 'site_label' => sanitize_text_field( $row['site_label'] ), 'site_url' => esc_url_raw( $row['site_url'] ), 'signatures' => absint( $row['total'] ), 'updated_at' => sanitize_text_field( $row['updated_at'] ) ); }, $rows ?: array() );
	}

	private function find_local_similarity( $post_id, array $tokens ) {
		$posts = get_posts( array( 'post_type' => get_post_types( array( 'public' => true ) ), 'post_status' => array( 'publish','draft','pending' ), 'posts_per_page' => 200, 'post__not_in' => array( absint( $post_id ) ), 'orderby' => 'modified', 'order' => 'DESC' ) );
		$best = array( 'score' => 0, 'post_id' => 0, 'title' => '', 'url' => '' );
		foreach ( $posts as $other ) {
			$other_tokens = $this->meaningful_tokens( $other->post_title . ' ' . wp_strip_all_tags( strip_shortcodes( $other->post_content ) ) );
			$score = $this->token_overlap( $tokens, $other_tokens );
			if ( $score > $best['score'] ) {
				$best = array( 'score' => round( $score, 4 ), 'post_id' => $other->ID, 'title' => get_the_title( $other ), 'url' => get_permalink( $other ) );
			}
		}
		return $best;
	}

	private function find_portfolio_similarity( array $signature ) {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT site_label,site_url,content_url,content_title,signature_json FROM {$this->signatures_table()} ORDER BY updated_at DESC LIMIT 2000", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$best = array( 'score' => 0, 'site_label' => '', 'title' => '', 'url' => '' );
		$current_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		foreach ( $rows ?: array() as $row ) {
			$signature_host = strtolower( (string) wp_parse_url( $row['site_url'] ?? '', PHP_URL_HOST ) );
			if ( $signature_host && $current_host && $signature_host === $current_host ) {
				continue;
			}
			$other = json_decode( $row['signature_json'], true );
			$score = $this->token_overlap( $signature, is_array( $other ) ? $other : array() );
			if ( $score > $best['score'] ) {
				$best = array( 'score' => round( $score, 4 ), 'site_label' => sanitize_text_field( $row['site_label'] ), 'title' => sanitize_text_field( $row['content_title'] ), 'url' => esc_url_raw( $row['content_url'] ) );
			}
		}
		return $best;
	}

	private function signature_from_tokens( array $tokens ) {
		$tokens = array_values( array_unique( array_filter( $tokens ) ) );
		$shingles = array();
		$count = count( $tokens );
		for ( $i = 0; $i < $count; $i += 3 ) {
			$chunk = array_slice( $tokens, $i, 5 );
			if ( count( $chunk ) >= 3 ) {
				$shingles[] = substr( hash( 'sha256', implode( '|', $chunk ) ), 0, 16 );
			}
		}
		sort( $shingles, SORT_STRING );
		return array_slice( array_values( array_unique( $shingles ) ), 0, 160 );
	}

	private function meaningful_tokens( $text ) {
		$text = $this->normalize_text( $text );
		$parts = preg_split( '/\s+/', $text );
		$stop = array_flip( array( 'the','and','for','with','that','this','from','your','you','our','are','was','were','have','has','had','not','but','can','will','into','than','then','their','they','them','its','also','about','how','what','when','where','which','who','why','a','an','to','of','in','on','at','by','or','as','is','be','it','we' ) );
		$tokens = array();
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( strlen( $part ) < 3 || isset( $stop[ $part ] ) || is_numeric( $part ) ) {
				continue;
			}
			$tokens[] = substr( $part, 0, 50 );
			if ( count( $tokens ) >= 1200 ) {
				break;
			}
		}
		return $tokens;
	}


	private function token_coverage( array $required, array $observed ) {
		$required = array_values( array_unique( array_filter( $required ) ) );
		$observed = array_values( array_unique( array_filter( $observed ) ) );
		if ( ! $required || ! $observed ) {
			return 0;
		}
		return count( array_intersect( $required, $observed ) ) / count( $required );
	}

	private function token_overlap( array $a, array $b ) {
		$a = array_values( array_unique( array_filter( $a ) ) );
		$b = array_values( array_unique( array_filter( $b ) ) );
		if ( ! $a || ! $b ) {
			return 0;
		}
		$intersection = array_intersect( $a, $b );
		$union = array_unique( array_merge( $a, $b ) );
		return count( $union ) ? count( $intersection ) / count( $union ) : 0;
	}

	private function link_counts( $content ) {
		$counts = array( 'internal' => 0, 'external' => 0, 'total' => 0 );
		if ( preg_match_all( '/href=["\']([^"\']+)["\']/i', (string) $content, $matches ) ) {
			$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
			foreach ( array_unique( $matches[1] ) as $url ) {
				if ( 0 === strpos( $url, '#' ) || 0 === strpos( $url, 'mailto:' ) || 0 === strpos( $url, 'tel:' ) ) {
					continue;
				}
				$counts['total']++;
				$link_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
				if ( ! $link_host || $link_host === $host ) {
					$counts['internal']++;
				} else {
					$counts['external']++;
				}
			}
		}
		return $counts;
	}

	private function get_keyword( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->keywords_table()} WHERE id=%d AND profile_id=%s", absint( $id ), $this->profile_id() ), ARRAY_A );
		return $row ? $this->prepare_keyword( $row ) : array();
	}

	private function get_hub( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->hubs_table()} WHERE id=%d AND profile_id=%s", absint( $id ), $this->profile_id() ), ARRAY_A );
		return $row ? $this->prepare_hub( $row ) : array();
	}

	private function get_item( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->items_table()} WHERE id=%d AND profile_id=%s", absint( $id ), $this->profile_id() ), ARRAY_A );
		return $row ? $this->prepare_item( $row ) : array();
	}

	private function item_for_post( $post_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->items_table()} WHERE profile_id=%s AND target_post_id=%d AND stage <> 'archived' ORDER BY updated_at DESC LIMIT 1", $this->profile_id(), absint( $post_id ) ), ARRAY_A );
		return $row ? $this->prepare_item( $row ) : array();
	}

	private function prepare_keyword( $row ) {
		return array(
			'id' => absint( $row['id'] ), 'keyword' => sanitize_text_field( $row['keyword_text'] ), 'cluster' => sanitize_text_field( $row['cluster_name'] ), 'intent' => sanitize_key( $row['intent'] ), 'page_type' => sanitize_key( $row['page_type'] ), 'country' => sanitize_text_field( $row['country'] ), 'language' => sanitize_text_field( $row['language'] ), 'demand_band' => sanitize_key( $row['demand_band'] ), 'difficulty_band' => sanitize_key( $row['difficulty_band'] ), 'business_value' => absint( $row['business_value'] ), 'priority' => absint( $row['priority'] ), 'status' => sanitize_key( $row['status'] ), 'target_post_id' => absint( $row['target_post_id'] ), 'source' => sanitize_key( $row['source'] ), 'notes' => sanitize_textarea_field( $row['notes'] ), 'updated_at' => sanitize_text_field( $row['updated_at'] ),
		);
	}

	private function prepare_hub( $row ) {
		return array(
			'id' => absint( $row['id'] ), 'title' => sanitize_text_field( $row['title'] ), 'slug' => sanitize_title( $row['slug'] ), 'description' => sanitize_textarea_field( $row['description'] ), 'target_audience' => sanitize_textarea_field( $row['target_audience'] ), 'monetization_goal' => sanitize_text_field( $row['monetization_goal'] ), 'pillar_post_id' => absint( $row['pillar_post_id'] ), 'keyword_ids' => $this->decode_ids( $row['keyword_ids_json'] ), 'supporting_post_ids' => $this->decode_ids( $row['supporting_post_ids_json'] ), 'readiness' => absint( $row['readiness'] ), 'status' => sanitize_key( $row['status'] ), 'updated_at' => sanitize_text_field( $row['updated_at'] ),
		);
	}

	private function prepare_item( $row ) {
		return array(
			'id' => absint( $row['id'] ), 'keyword_id' => absint( $row['keyword_id'] ), 'hub_id' => absint( $row['hub_id'] ), 'title' => sanitize_text_field( $row['title'] ), 'content_type' => sanitize_key( $row['content_type'] ), 'intent' => sanitize_key( $row['intent'] ), 'stage' => sanitize_key( $row['stage'] ), 'priority' => absint( $row['priority'] ), 'author_id' => absint( $row['author_id'] ), 'reviewer_id' => absint( $row['reviewer_id'] ), 'due_at' => sanitize_text_field( $row['due_at'] ), 'target_post_id' => absint( $row['target_post_id'] ), 'source_requirements' => sanitize_textarea_field( $row['source_requirements'] ), 'evidence_notes' => sanitize_textarea_field( $row['evidence_notes'] ), 'originality_required' => (bool) $row['originality_required'], 'disclosure_required' => (bool) $row['disclosure_required'], 'brief' => $this->decode_json( $row['brief_json'] ), 'quality_score' => absint( $row['quality_score'] ), 'gate_status' => sanitize_key( $row['gate_status'] ), 'lifecycle_action' => sanitize_key( $row['lifecycle_action'] ), 'refresh_due' => sanitize_text_field( $row['refresh_due'] ), 'notes' => sanitize_textarea_field( $row['notes'] ), 'updated_at' => sanitize_text_field( $row['updated_at'] ),
		);
	}

	private function hub_readiness( $pillar_id, array $keyword_ids, array $supporting_ids, array $data ) {
		$score = 0;
		if ( $pillar_id && get_post( $pillar_id ) ) { $score += 35; }
		if ( $keyword_ids ) { $score += min( 30, count( $keyword_ids ) * 5 ); }
		if ( $supporting_ids ) { $score += min( 25, count( $supporting_ids ) * 5 ); }
		if ( ! empty( $data['description'] ) ) { $score += 5; }
		if ( ! empty( $data['target_audience'] ) ) { $score += 5; }
		return min( 100, $score );
	}

	private function opportunity_priority( $demand, $difficulty, $business_value, $intent ) {
		$demand_map = array( 'unknown' => 35, 'very_low' => 15, 'low' => 30, 'medium' => 55, 'high' => 78, 'very_high' => 92 );
		$difficulty_map = array( 'unknown' => 50, 'low' => 85, 'medium' => 62, 'high' => 38, 'very_high' => 20 );
		$intent_bonus = in_array( $intent, array( 'local_service','transactional','commercial' ), true ) ? 8 : 0;
		return min( 100, (int) round( 0.35 * $demand_map[ $demand ] + 0.25 * $difficulty_map[ $difficulty ] + 0.40 * $business_value + $intent_bonus ) );
	}

	private function stages() {
		return array( 'idea','research','planned','brief','approved','drafting','review','revision','ready','published','refresh','consolidate','retire','archived' );
	}

	private function profile_id() {
		$profile = $this->profile->get();
		return sanitize_text_field( $profile['profile_id'] ?? '' );
	}

	private function tables_ready() {
		return $this->table_exists( $this->keywords_table() ) && $this->table_exists( $this->hubs_table() ) && $this->table_exists( $this->items_table() ) && $this->table_exists( $this->signatures_table() );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	private function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	private function valid_user_id( $value ) {
		$id = absint( $value );
		return $id && get_user_by( 'id', $id ) ? $id : 0;
	}

	private function allowed( $value, array $allowed, $fallback ) {
		$value = sanitize_key( $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private function normalize_text( $text ) {
		$text = remove_accents( strtolower( wp_strip_all_tags( (string) $text ) ) );
		$text = preg_replace( '/[^a-z0-9\s]+/', ' ', $text );
		return trim( preg_replace( '/\s+/', ' ', $text ) );
	}

	private function sanitize_list( $value, $limit, $length ) {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\r\n,]+/', $value );
		}
		$result = array();
		foreach ( array_slice( (array) $value, 0, $limit ) as $item ) {
			$item = substr( sanitize_text_field( $item ), 0, $length );
			if ( $item ) { $result[] = $item; }
		}
		return array_values( array_unique( $result ) );
	}

	private function sanitize_ids( $value, $limit ) {
		if ( is_string( $value ) ) { $value = preg_split( '/[\s,]+/', $value ); }
		return array_slice( array_values( array_unique( array_filter( array_map( 'absint', (array) $value ) ) ) ), 0, $limit );
	}

	private function sanitize_signature( $value ) {
		if ( is_string( $value ) ) { $value = preg_split( '/[\s,]+/', $value ); }
		$result = array();
		foreach ( array_slice( (array) $value, 0, 200 ) as $part ) {
			$part = strtolower( preg_replace( '/[^a-f0-9]/i', '', (string) $part ) );
			if ( strlen( $part ) >= 8 && strlen( $part ) <= 64 ) { $result[] = $part; }
		}
		return array_values( array_unique( $result ) );
	}

	private function sanitize_recursive( $value, $depth = 0 ) {
		if ( $depth > 6 ) { return array(); }
		$result = array();
		foreach ( array_slice( (array) $value, 0, 100 ) as $key => $item ) {
			$key = sanitize_key( $key );
			if ( is_array( $item ) ) { $result[ $key ] = $this->sanitize_recursive( $item, $depth + 1 ); }
			else { $result[ $key ] = substr( sanitize_textarea_field( $item ), 0, 5000 ); }
		}
		return $result;
	}

	private function decode_json( $value ) {
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function decode_ids( $value ) {
		return array_values( array_filter( array_map( 'absint', $this->decode_json( $value ) ) ) );
	}
}
