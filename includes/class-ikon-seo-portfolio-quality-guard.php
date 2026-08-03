<?php

defined( 'ABSPATH' ) || exit;

/**
 * Privacy-preserving portfolio quality and footprint review.
 */
final class Ikon_SEO_Portfolio_Quality_Guard {
	const CRON_HOOK       = 'ikon_seo_portfolio_quality_weekly';
	const BUNDLE_FORMAT   = 'ikon-seo-portfolio-quality-v2';
	const MAX_BUNDLE_SIZE = 5242880;
	const MAX_ITEMS       = 2000;

	private $profile;
	private $publisher;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Publisher_Intelligence $publisher,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->profile   = $profile;
		$this->publisher = $publisher;
		$this->history   = $history;
		$this->logger    = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_review' ) );
		add_action( 'save_post', array( $this, 'mark_post_stale' ), 30, 3 );
	}

	public function profiles_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_portfolio_quality_profiles';
	}

	public function findings_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_portfolio_quality_findings';
	}

	public function imports_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_portfolio_quality_imports';
	}

	public function scheduled_review() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['portfolio_quality_enabled'] ) ) {
			return;
		}
		$limit = max( 5, min( 100, absint( $settings['portfolio_quality_scan_batch'] ?? 25 ) ) );
		$this->scan_local( $limit, 0, false );
		$this->evaluate( $limit, 0 );
		$this->cleanup();
	}

	public function mark_post_stale( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || ! $post instanceof WP_Post || 'attachment' === $post->post_type ) {
			return;
		}
		if ( ! in_array( $post->post_status, array( 'publish', 'draft', 'pending', 'future' ), true ) ) {
			return;
		}
		delete_post_meta( $post_id, '_ikon_seo_portfolio_quality_scanned_at' );
	}

	public function status() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		if ( ! $this->tables_ready() ) {
			return array(
				'enabled'      => ! empty( $settings['portfolio_quality_enabled'] ),
				'tables_ready' => false,
			);
		}

		$profiles = $this->profiles_table();
		$findings = $this->findings_table();
		$imports  = $this->imports_table();
		$profile_id = $this->profile_id();

		return array(
			'enabled'              => ! empty( $settings['portfolio_quality_enabled'] ),
			'tables_ready'         => true,
			'local_profiles'       => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$profiles} WHERE profile_id=%s AND source='local' AND status='active'", $profile_id ) ) ),
			'imported_profiles'    => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$profiles} WHERE profile_id=%s AND source='imported' AND status='active'", $profile_id ) ) ),
			'portfolio_sites'      => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT source_site_hash) FROM {$profiles} WHERE profile_id=%s AND source='imported' AND status='active'", $profile_id ) ) ),
			'open_findings'        => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$findings} WHERE profile_id=%s AND status='open'", $profile_id ) ) ),
			'blocking_findings'    => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$findings} WHERE profile_id=%s AND status='open' AND blocks_review=1", $profile_id ) ) ),
			'critical_findings'    => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$findings} WHERE profile_id=%s AND status='open' AND severity='critical'", $profile_id ) ) ),
			'high_findings'        => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$findings} WHERE profile_id=%s AND status='open' AND severity='high'", $profile_id ) ) ),
			'blocked_pipeline'     => $this->blocked_pipeline_count(),
			'import_batches'       => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$imports} WHERE profile_id=%s", $profile_id ) ) ),
			'last_scan'            => sanitize_text_field( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(scanned_at) FROM {$profiles} WHERE profile_id=%s AND source='local'", $profile_id ) ) ),
			'last_evaluation'      => sanitize_text_field( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(updated_at) FROM {$findings} WHERE profile_id=%s", $profile_id ) ) ),
			'review_block_enabled' => ! empty( $settings['portfolio_quality_block_review_ready'] ),
			'privacy'              => array(
				'full_content_stored' => false,
				'author_names_stored' => false,
				'file_contents_stored'=> false,
				'changes_live_pages'  => false,
			),
		);
	}

	public function report( $limit = 100 ) {
		$limit = max( 10, min( 500, absint( $limit ) ) );
		return array(
			'status'          => $this->status(),
			'findings'        => $this->list_findings( $limit ),
			'local_profiles'  => $this->list_profiles( 'local', min( 100, $limit ) ),
			'portfolio_sites' => $this->portfolio_sites(),
			'settings'        => $this->public_settings(),
			'gate_policy'     => array(
				'blocks_review_ready' => ! empty( Ikon_SEO_Plugin::settings()['portfolio_quality_block_review_ready'] ),
				'approval_required'   => true,
				'changes_public_pages' => false,
			),
			'limitations'     => array(
				'Similarity findings are review signals and do not prove copying, spam or a search-policy violation.',
				'Hashed author and media matches can indicate reuse but cannot establish ownership, permission or editorial quality.',
				'Topic overlap can be appropriate when websites serve different audiences or provide materially different evidence.',
				'Publishing-pattern observations are contextual and should not be treated as a ranking factor.',
			),
			'generated_at'    => current_time( 'mysql', true ),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'scan_local':
				return $this->scan_local( absint( $payload['limit'] ?? 25 ), $user_id, ! empty( $payload['force'] ) );
			case 'evaluate':
				return $this->evaluate( absint( $payload['limit'] ?? 50 ), $user_id );
			case 'import_bundle':
				return $this->import_bundle( (array) ( $payload['bundle'] ?? array() ), $user_id );
			case 'update_finding':
				return $this->update_finding( absint( $payload['finding_id'] ?? 0 ), sanitize_key( $payload['status'] ?? 'reviewed' ), $user_id, sanitize_textarea_field( $payload['notes'] ?? '' ) );
			case 'save_settings':
				return $this->save_settings( $payload, $user_id );
			case 'cleanup':
				return $this->cleanup();
			case 'export_bundle':
				return $this->export_bundle( absint( $payload['limit'] ?? 500 ) );
			case 'read':
			default:
				return $this->report( absint( $payload['limit'] ?? 100 ) );
		}
	}

	public function save_settings( array $data, $user_id = 0 ) {
		$settings = Ikon_SEO_Plugin::settings();
		$settings['portfolio_quality_enabled']              = empty( $data['enabled'] ) ? 0 : 1;
		$settings['portfolio_quality_scan_batch']           = max( 5, min( 200, absint( $data['scan_batch'] ?? $settings['portfolio_quality_scan_batch'] ?? 25 ) ) );
		$settings['portfolio_quality_content_threshold']    = max( 55, min( 98, absint( $data['content_threshold'] ?? $settings['portfolio_quality_content_threshold'] ?? 72 ) ) );
		$settings['portfolio_quality_topic_threshold']      = max( 55, min( 98, absint( $data['topic_threshold'] ?? $settings['portfolio_quality_topic_threshold'] ?? 80 ) ) );
		$settings['portfolio_quality_template_threshold']   = max( 50, min( 100, absint( $data['template_threshold'] ?? $settings['portfolio_quality_template_threshold'] ?? 90 ) ) );
		$settings['portfolio_quality_thin_words']           = max( 100, min( 2000, absint( $data['thin_words'] ?? $settings['portfolio_quality_thin_words'] ?? 450 ) ) );
		$settings['portfolio_quality_cluster_min']          = max( 3, min( 50, absint( $data['cluster_min'] ?? $settings['portfolio_quality_cluster_min'] ?? 4 ) ) );
		$settings['portfolio_quality_block_review_ready']   = empty( $data['block_review_ready'] ) ? 0 : 1;
		$settings['portfolio_quality_media_hashing']        = empty( $data['media_hashing'] ) ? 0 : 1;
		$settings['portfolio_quality_retention_days']       = max( 90, min( 1095, absint( $data['retention_days'] ?? $settings['portfolio_quality_retention_days'] ?? 365 ) ) );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->history_event( 'portfolio_quality', 'updated', 'Portfolio quality policy updated', 'Portfolio-level similarity, differentiation and review-gate settings were updated.', array( 'review_block' => ! empty( $settings['portfolio_quality_block_review_ready'] ) ), $user_id );
		return array( 'saved' => true, 'status' => $this->status() );
	}

	public function scan_local( $limit = 25, $user_id = 0, $force = false ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_portfolio_quality_tables', __( 'The Portfolio Quality database is not ready.', 'ikon-seo' ) );
		}
		$limit = max( 1, min( 200, absint( $limit ) ) );
		$post_types = get_post_types( array( 'public' => true ) );
		unset( $post_types['attachment'] );
		$args = array(
			'post_type'      => array_values( $post_types ),
			'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
			'posts_per_page' => $limit * 4,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		);
		$posts = get_posts( $args );
		$scanned = array();
		$stale_days = 30;
		$stale_before = time() - $stale_days * DAY_IN_SECONDS;
		foreach ( $posts as $post ) {
			if ( count( $scanned ) >= $limit ) {
				break;
			}
			$last = (string) get_post_meta( $post->ID, '_ikon_seo_portfolio_quality_scanned_at', true );
			if ( ! $force && $last && strtotime( $last . ' UTC' ) > $stale_before ) {
				continue;
			}
			$profile = $this->build_local_profile( $post );
			$this->upsert_profile( $profile );
			update_post_meta( $post->ID, '_ikon_seo_portfolio_quality_scanned_at', current_time( 'mysql', true ) );
			$scanned[] = array( 'post_id' => $post->ID, 'url' => $profile['content_url'], 'word_count' => $profile['word_count'] );
		}
		$this->history_event( 'portfolio_quality', 'reviewed', 'Portfolio quality signatures refreshed', sprintf( '%d local pages were converted into privacy-preserving structural signatures.', count( $scanned ) ), array( 'scanned' => count( $scanned ), 'force' => (bool) $force ), $user_id );
		return array( 'scanned' => count( $scanned ), 'items' => $scanned, 'status' => $this->status() );
	}

	public function export_bundle( $limit = 500 ) {
		$limit = max( 1, min( self::MAX_ITEMS, absint( $limit ) ) );
		$items = $this->list_profiles( 'local', $limit, true );
		return array(
			'format'       => self::BUNDLE_FORMAT,
			'site_label'   => sanitize_text_field( get_bloginfo( 'name' ) ),
			'site_url'     => home_url( '/' ),
			'site_hash'    => hash( 'sha256', strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) ),
			'generated_at' => current_time( 'mysql', true ),
			'privacy'      => array( 'full_content' => false, 'author_identity' => false, 'file_contents' => false ),
			'items'        => $items,
		);
	}

	public function import_bundle( array $bundle, $user_id = 0 ) {
		global $wpdb;
		$format = sanitize_text_field( $bundle['format'] ?? '' );
		if ( ! in_array( $format, array( self::BUNDLE_FORMAT, 'ikon-seo-portfolio-signatures-v1' ), true ) ) {
			return new WP_Error( 'ikon_seo_portfolio_quality_format', __( 'The portfolio quality bundle format is not supported.', 'ikon-seo' ) );
		}
		$site_label = sanitize_text_field( $bundle['site_label'] ?? '' );
		$site_url   = esc_url_raw( $bundle['site_url'] ?? '' );
		$site_host  = strtolower( (string) wp_parse_url( $site_url, PHP_URL_HOST ) );
		$current    = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( ! $site_label || ! $site_host || $site_host === $current ) {
			return new WP_Error( 'ikon_seo_portfolio_quality_site', __( 'Import a bundle from a different managed website.', 'ikon-seo' ) );
		}
		$site_hash = hash( 'sha256', $site_host );
		$bundle_id = substr( hash( 'sha256', $format . '|' . $site_hash . '|' . sanitize_text_field( $bundle['generated_at'] ?? '' ) . '|' . wp_json_encode( $bundle['items'] ?? array() ) ), 0, 40 );
		$items = array_slice( (array) ( $bundle['items'] ?? array() ), 0, self::MAX_ITEMS );
		$stored = 0;
		$skipped = 0;
		$publisher_bundle = array( 'format' => 'ikon-seo-portfolio-signatures-v1', 'site_label' => $site_label, 'site_url' => $site_url, 'items' => array() );
		foreach ( $items as $item ) {
			$item = (array) $item;
			$signature = $this->sanitize_hash_list( $item['signature'] ?? array(), 200 );
			if ( ! $signature ) {
				$skipped++;
				continue;
			}
			$profile = array(
				'profile_id'       => $this->profile_id(),
				'source'           => 'imported',
				'source_site_hash' => $site_hash,
				'source_site_label'=> $site_label,
				'bundle_id'        => $bundle_id,
				'post_id'          => 0,
				'content_url'      => esc_url_raw( $item['content_url'] ?? '' ),
				'content_title'    => sanitize_text_field( $item['content_title'] ?? '' ),
				'content_type'     => sanitize_key( $item['content_type'] ?? 'article' ),
				'word_count'       => absint( $item['word_count'] ?? 0 ),
				'heading_count'    => absint( $item['heading_count'] ?? 0 ),
				'paragraph_count'  => absint( $item['paragraph_count'] ?? 0 ),
				'internal_links'   => absint( $item['internal_links'] ?? 0 ),
				'signature_json'   => wp_json_encode( $signature ),
				'topics_json'      => wp_json_encode( $this->sanitize_terms( $item['topics'] ?? array(), 40 ) ),
				'heading_hash'     => sanitize_text_field( $item['heading_hash'] ?? '' ),
				'template_hash'    => sanitize_text_field( $item['template_hash'] ?? '' ),
				'title_pattern_hash'=> sanitize_text_field( $item['title_pattern_hash'] ?? '' ),
				'author_hash'      => sanitize_text_field( $item['author_hash'] ?? '' ),
				'media_hashes_json'=> wp_json_encode( $this->sanitize_hash_list( $item['media_hashes'] ?? array(), 30 ) ),
				'publish_pattern'  => sanitize_text_field( $item['publish_pattern'] ?? '' ),
				'published_at'     => $this->mysql_date_or_null( $item['published_at'] ?? '' ),
				'status'           => 'active',
				'scanned_at'       => current_time( 'mysql', true ),
				'updated_at'       => current_time( 'mysql', true ),
			);
			if ( $this->upsert_profile( $profile ) ) {
				$stored++;
				$publisher_bundle['items'][] = array( 'content_url' => $profile['content_url'], 'content_title' => $profile['content_title'], 'content_type' => $profile['content_type'], 'signature' => $signature, 'topics' => json_decode( $profile['topics_json'], true ) );
			} else {
				$skipped++;
			}
		}
		$this->publisher->import_signature_bundle( $publisher_bundle, $user_id );
		$now = current_time( 'mysql', true );
		$sql = "INSERT INTO {$this->imports_table()} (profile_id,bundle_id,source_site_hash,source_site_label,source_site_url,rows_seen,rows_imported,rows_skipped,created_by,created_at) VALUES (%s,%s,%s,%s,%s,%d,%d,%d,%d,%s) ON DUPLICATE KEY UPDATE rows_seen=VALUES(rows_seen),rows_imported=VALUES(rows_imported),rows_skipped=VALUES(rows_skipped),created_by=VALUES(created_by),created_at=VALUES(created_at)";
		$wpdb->query( $wpdb->prepare( $sql, $this->profile_id(), $bundle_id, $site_hash, $site_label, $site_url, count( $items ), $stored, $skipped, absint( $user_id ), $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->history_event( 'portfolio_quality', 'imported', 'Portfolio quality bundle imported', sprintf( '%d privacy-preserving page profiles were imported from %s.', $stored, $site_label ), array( 'stored' => $stored, 'skipped' => $skipped, 'bundle_id' => $bundle_id ), $user_id );
		return array( 'stored' => $stored, 'skipped' => $skipped, 'bundle_id' => $bundle_id, 'status' => $this->status() );
	}

	public function evaluate( $limit = 50, $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_portfolio_quality_tables', __( 'The Portfolio Quality database is not ready.', 'ikon-seo' ) );
		}
		$limit = max( 1, min( 200, absint( $limit ) ) );
		$profile_id = $this->profile_id();
		$local = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->profiles_table()} WHERE profile_id=%s AND source='local' AND status='active' ORDER BY updated_at DESC LIMIT %d", $profile_id, $limit ), ARRAY_A );
		$remote = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->profiles_table()} WHERE profile_id=%s AND source='imported' AND status='active' ORDER BY updated_at DESC LIMIT 2000", $profile_id ), ARRAY_A );
		$active_keys = array();
		$created = 0;
		foreach ( (array) $local as $local_row ) {
			$best = array();
			foreach ( (array) $remote as $remote_row ) {
				$comparison = $this->compare_profiles( $local_row, $remote_row );
				if ( empty( $best ) || $comparison['risk_score'] > $best['risk_score'] ) {
					$best = $comparison + array( 'remote' => $remote_row );
				}
			}
			if ( $best ) {
				$cross_findings = $this->cross_site_findings( $local_row, $best['remote'], $best );
				foreach ( $cross_findings as $finding ) {
					$key = $this->upsert_finding( $finding, $local_row, $best['remote'] );
					if ( $key ) { $active_keys[] = $key; $created++; }
				}
			}
		}
		foreach ( $this->local_cluster_findings( $local ) as $cluster ) {
			$local_row = $cluster['local'];
			$key = $this->upsert_finding( $cluster['finding'], $local_row, array() );
			if ( $key ) { $active_keys[] = $key; $created++; }
		}
		$this->resolve_stale_findings( $active_keys );
		$this->enforce_publisher_gates();
		$this->publish_operating_plan_recommendations();
		$this->history_event( 'portfolio_quality', $created ? 'needs_review' : 'reviewed', 'Portfolio quality evaluation completed', sprintf( '%d current review signals were calculated.', $created ), array( 'active_findings' => $created, 'local_pages' => count( $local ), 'portfolio_pages' => count( $remote ) ), $user_id );
		return array( 'evaluated' => count( $local ), 'active_findings' => $created, 'status' => $this->status(), 'findings' => $this->list_findings( min( 100, $limit ) ) );
	}

	public function update_finding( $finding_id, $status, $user_id = 0, $notes = '' ) {
		global $wpdb;
		$allowed = array( 'open', 'reviewed', 'accepted', 'resolved', 'dismissed' );
		$status = in_array( $status, $allowed, true ) ? $status : 'reviewed';
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->findings_table()} WHERE id=%d AND profile_id=%s", absint( $finding_id ), $this->profile_id() ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'ikon_seo_portfolio_quality_finding', __( 'The portfolio quality finding was not found.', 'ikon-seo' ) );
		}
		$wpdb->update( $this->findings_table(), array( 'status' => $status, 'review_notes' => sanitize_textarea_field( $notes ), 'reviewed_by' => absint( $user_id ), 'reviewed_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $finding_id ) ) );
		$this->enforce_publisher_gates();
		$this->history_event( 'portfolio_quality', $status, 'Portfolio quality finding updated', sanitize_text_field( $row['summary'] ?? '' ), array( 'finding_id' => absint( $finding_id ), 'status' => $status ), $user_id, absint( $row['local_post_id'] ?? 0 ) );
		return array( 'updated' => true, 'finding_id' => absint( $finding_id ), 'status' => $status );
	}

	public function cleanup() {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return array( 'deleted' => 0 ); }
		$days = max( 90, min( 1095, absint( Ikon_SEO_Plugin::settings()['portfolio_quality_retention_days'] ?? 365 ) ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days . ' days' ) );
		$deleted_profiles = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->profiles_table()} WHERE source='imported' AND updated_at < %s", $cutoff ) );
		$deleted_findings = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->findings_table()} WHERE status IN ('resolved','dismissed') AND updated_at < %s", $cutoff ) );
		$deleted_imports  = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->imports_table()} WHERE created_at < %s", $cutoff ) );
		return array( 'deleted' => absint( $deleted_profiles ) + absint( $deleted_findings ) + absint( $deleted_imports ) );
	}

	private function build_local_profile( WP_Post $post ) {
		$content = (string) $post->post_content;
		$plain = html_entity_decode( wp_strip_all_tags( strip_shortcodes( $content ) ), ENT_QUOTES, 'UTF-8' );
		$tokens = $this->meaningful_tokens( $post->post_title . ' ' . $plain );
		$signature = $this->signature_from_tokens( $tokens );
		$headings = $this->headings( $content );
		$template = $this->template_sequence( $content );
		$author = get_userdata( $post->post_author );
		$author_identity = $author ? strtolower( trim( $author->display_name . '|' . get_user_meta( $author->ID, 'description', true ) ) ) : '';
		$media_hashes = $this->media_hashes( $post );
		$url = get_permalink( $post );
		return array(
			'profile_id'        => $this->profile_id(),
			'source'            => 'local',
			'source_site_hash'  => hash( 'sha256', strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) ),
			'source_site_label' => sanitize_text_field( get_bloginfo( 'name' ) ),
			'bundle_id'         => '',
			'post_id'           => $post->ID,
			'content_url'       => esc_url_raw( $url ),
			'content_title'     => sanitize_text_field( get_the_title( $post ) ),
			'content_type'      => sanitize_key( $post->post_type ),
			'word_count'        => count( preg_split( '/\s+/u', trim( $plain ), -1, PREG_SPLIT_NO_EMPTY ) ),
			'heading_count'     => count( $headings ),
			'paragraph_count'   => preg_match_all( '/<p\b/i', $content, $matches ),
			'internal_links'    => $this->internal_link_count( $content ),
			'signature_json'    => wp_json_encode( $signature ),
			'topics_json'       => wp_json_encode( array_slice( array_values( array_unique( $tokens ) ), 0, 40 ) ),
			'heading_hash'      => hash( 'sha256', implode( '|', $headings ) ),
			'template_hash'     => hash( 'sha256', implode( '|', $template ) ),
			'title_pattern_hash'=> hash( 'sha256', $this->title_pattern( $post->post_title ) ),
			'author_hash'       => $author_identity ? hash( 'sha256', $author_identity ) : '',
			'media_hashes_json' => wp_json_encode( $media_hashes ),
			'publish_pattern'   => mysql2date( 'N-H', $post->post_date_gmt ?: $post->post_date, false ),
			'published_at'      => $this->mysql_date_or_null( $post->post_date_gmt ?: $post->post_date ),
			'status'            => 'active',
			'scanned_at'        => current_time( 'mysql', true ),
			'updated_at'        => current_time( 'mysql', true ),
		);
	}

	private function upsert_profile( array $row ) {
		global $wpdb;
		$url_hash = hash( 'sha256', $this->normalise_url( $row['content_url'] ?? '' ) . '|' . sanitize_key( $row['source'] ?? 'local' ) . '|' . sanitize_text_field( $row['source_site_hash'] ?? '' ) );
		if ( ! $url_hash || empty( $row['signature_json'] ) ) { return false; }
		$sql = "INSERT INTO {$this->profiles_table()} (profile_id,profile_hash,source,source_site_hash,source_site_label,bundle_id,post_id,content_url,content_title,content_type,word_count,heading_count,paragraph_count,internal_links,signature_json,topics_json,heading_hash,template_hash,title_pattern_hash,author_hash,media_hashes_json,publish_pattern,published_at,status,scanned_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%d,%s,%s,%s,%d,%d,%d,%d,%s,%s,%s,%s,%s,%s,%s,%s,NULLIF(%s,''),%s,%s,%s) ON DUPLICATE KEY UPDATE source_site_label=VALUES(source_site_label),bundle_id=VALUES(bundle_id),post_id=VALUES(post_id),content_url=VALUES(content_url),content_title=VALUES(content_title),content_type=VALUES(content_type),word_count=VALUES(word_count),heading_count=VALUES(heading_count),paragraph_count=VALUES(paragraph_count),internal_links=VALUES(internal_links),signature_json=VALUES(signature_json),topics_json=VALUES(topics_json),heading_hash=VALUES(heading_hash),template_hash=VALUES(template_hash),title_pattern_hash=VALUES(title_pattern_hash),author_hash=VALUES(author_hash),media_hashes_json=VALUES(media_hashes_json),publish_pattern=VALUES(publish_pattern),published_at=VALUES(published_at),status='active',scanned_at=VALUES(scanned_at),updated_at=VALUES(updated_at)";
		$result = $wpdb->query( $wpdb->prepare( $sql, sanitize_text_field( $row['profile_id'] ?? $this->profile_id() ), $url_hash, sanitize_key( $row['source'] ?? 'local' ), sanitize_text_field( $row['source_site_hash'] ?? '' ), sanitize_text_field( $row['source_site_label'] ?? '' ), sanitize_text_field( $row['bundle_id'] ?? '' ), absint( $row['post_id'] ?? 0 ), esc_url_raw( $row['content_url'] ?? '' ), sanitize_text_field( $row['content_title'] ?? '' ), sanitize_key( $row['content_type'] ?? 'article' ), absint( $row['word_count'] ?? 0 ), absint( $row['heading_count'] ?? 0 ), absint( $row['paragraph_count'] ?? 0 ), absint( $row['internal_links'] ?? 0 ), (string) $row['signature_json'], (string) ( $row['topics_json'] ?? '[]' ), sanitize_text_field( $row['heading_hash'] ?? '' ), sanitize_text_field( $row['template_hash'] ?? '' ), sanitize_text_field( $row['title_pattern_hash'] ?? '' ), sanitize_text_field( $row['author_hash'] ?? '' ), (string) ( $row['media_hashes_json'] ?? '[]' ), sanitize_text_field( $row['publish_pattern'] ?? '' ), $row['published_at'] ?: null, sanitize_key( $row['status'] ?? 'active' ), sanitize_text_field( $row['scanned_at'] ?? current_time( 'mysql', true ) ), sanitize_text_field( $row['updated_at'] ?? current_time( 'mysql', true ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return false !== $result;
	}

	private function compare_profiles( array $local, array $remote ) {
		$content = $this->jaccard( json_decode( $local['signature_json'] ?? '[]', true ) ?: array(), json_decode( $remote['signature_json'] ?? '[]', true ) ?: array() );
		$topics  = $this->jaccard( json_decode( $local['topics_json'] ?? '[]', true ) ?: array(), json_decode( $remote['topics_json'] ?? '[]', true ) ?: array() );
		$headings = $local['heading_hash'] && $local['heading_hash'] === ( $remote['heading_hash'] ?? '' ) ? 1 : 0;
		$template = $local['template_hash'] && $local['template_hash'] === ( $remote['template_hash'] ?? '' ) ? 1 : 0;
		$author = $local['author_hash'] && $local['author_hash'] === ( $remote['author_hash'] ?? '' ) ? 1 : 0;
		$media = $this->jaccard( json_decode( $local['media_hashes_json'] ?? '[]', true ) ?: array(), json_decode( $remote['media_hashes_json'] ?? '[]', true ) ?: array() );
		$cadence = $local['publish_pattern'] && $local['publish_pattern'] === ( $remote['publish_pattern'] ?? '' ) ? 1 : 0;
		$risk = round( 100 * ( 0.50 * $content + 0.16 * $topics + 0.12 * $template + 0.07 * $headings + 0.06 * $media + 0.05 * $author + 0.04 * $cadence ), 1 );
		return array( 'content' => $content, 'topics' => $topics, 'headings' => $headings, 'template' => $template, 'author' => $author, 'media' => $media, 'cadence' => $cadence, 'risk_score' => $risk );
	}

	private function cross_site_findings( array $local, array $remote, array $scores ) {
		$settings = Ikon_SEO_Plugin::settings();
		$content_threshold = absint( $settings['portfolio_quality_content_threshold'] ?? 72 ) / 100;
		$topic_threshold = absint( $settings['portfolio_quality_topic_threshold'] ?? 80 ) / 100;
		$template_threshold = absint( $settings['portfolio_quality_template_threshold'] ?? 90 ) / 100;
		$findings = array();
		if ( $scores['content'] >= $content_threshold ) {
			$severity = $scores['content'] >= 0.88 ? 'critical' : 'high';
			$findings[] = $this->finding( 'cross_site_content_similarity', $severity, round( $scores['content'] * 100, 1 ), 'A page has unusually high privacy-signature overlap with another managed website.', true, $scores );
		}
		if ( $scores['template'] >= $template_threshold && $scores['headings'] && $scores['content'] >= max( 0.45, $content_threshold - 0.15 ) ) {
			$findings[] = $this->finding( 'repeated_template_footprint', 'high', round( max( $scores['content'], 0.8 ) * 100, 1 ), 'The page reuses both the structural template and heading pattern found on another managed website.', true, $scores );
		}
		if ( $scores['topics'] >= $topic_threshold && $scores['content'] < $content_threshold ) {
			$findings[] = $this->finding( 'topic_map_overlap', 'medium', round( $scores['topics'] * 100, 1 ), 'The topic coverage strongly overlaps another managed website and requires a clear audience or evidence distinction.', false, $scores );
		}
		if ( $scores['media'] >= 0.5 && $scores['media'] > 0 ) {
			$findings[] = $this->finding( 'reused_media_assets', $scores['media'] >= 0.8 ? 'high' : 'medium', round( $scores['media'] * 100, 1 ), 'Identical media file hashes appear across managed websites.', $scores['media'] >= 0.8, $scores );
		}
		if ( $scores['author'] && $scores['content'] >= 0.45 ) {
			$findings[] = $this->finding( 'reused_author_footprint', 'medium', 70, 'The same hashed author identity appears with materially similar content on another managed website.', false, $scores );
		}
		if ( $scores['cadence'] && $scores['template'] && $scores['content'] >= 0.45 ) {
			$findings[] = $this->finding( 'synchronised_publishing_pattern', 'low', 55, 'The publishing-time pattern and page structure match another managed website.', false, $scores );
		}
		return $findings;
	}

	private function local_cluster_findings( array $rows ) {
		$settings = Ikon_SEO_Plugin::settings();
		$thin_words = absint( $settings['portfolio_quality_thin_words'] ?? 450 );
		$cluster_min = absint( $settings['portfolio_quality_cluster_min'] ?? 4 );
		$groups = array();
		foreach ( $rows as $row ) {
			$key = sanitize_text_field( $row['template_hash'] ?? '' ) . '|' . sanitize_text_field( $row['title_pattern_hash'] ?? '' );
			if ( ! trim( $key, '|' ) ) { continue; }
			$groups[ $key ][] = $row;
		}
		$result = array();
		foreach ( $groups as $group ) {
			if ( count( $group ) < $cluster_min ) { continue; }
			$thin = array_filter( $group, function( $row ) use ( $thin_words ) { return absint( $row['word_count'] ?? 0 ) < $thin_words; } );
			if ( count( $thin ) < $cluster_min ) { continue; }
			foreach ( $thin as $row ) {
				$result[] = array( 'local' => $row, 'finding' => $this->finding( 'thin_programmatic_cluster', 'high', 85, sprintf( 'This page belongs to a cluster of %d thin pages sharing the same title and template pattern.', count( $thin ) ), true, array( 'cluster_size' => count( $thin ), 'word_count' => absint( $row['word_count'] ?? 0 ) ) ) );
			}
		}
		return $result;
	}

	private function finding( $category, $severity, $score, $summary, $blocks, array $evidence ) {
		return array( 'category' => sanitize_key( $category ), 'severity' => sanitize_key( $severity ), 'risk_score' => min( 100, max( 0, (float) $score ) ), 'summary' => sanitize_text_field( $summary ), 'blocks_review' => $blocks ? 1 : 0, 'evidence' => $evidence );
	}

	private function upsert_finding( array $finding, array $local, array $remote ) {
		global $wpdb;
		$key = hash( 'sha256', $this->profile_id() . '|' . absint( $local['id'] ?? 0 ) . '|' . absint( $remote['id'] ?? 0 ) . '|' . sanitize_key( $finding['category'] ?? '' ) );
		$now = current_time( 'mysql', true );
		$sql = "INSERT INTO {$this->findings_table()} (profile_id,finding_key,local_profile_id,local_post_id,local_url,compared_profile_id,compared_site_hash,compared_site_label,compared_url,category,severity,risk_score,summary,evidence_json,status,blocks_review,first_seen_at,last_seen_at,reviewed_by,reviewed_at,review_notes,created_at,updated_at) VALUES (%s,%s,%d,%d,%s,%d,%s,%s,%s,%s,%s,%f,%s,%s,'open',%d,%s,%s,0,NULL,'',%s,%s) ON DUPLICATE KEY UPDATE severity=VALUES(severity),risk_score=VALUES(risk_score),summary=VALUES(summary),evidence_json=VALUES(evidence_json),blocks_review=VALUES(blocks_review),status=IF(status IN ('resolved','dismissed'),status,'open'),last_seen_at=VALUES(last_seen_at),updated_at=VALUES(updated_at)";
		$result = $wpdb->query( $wpdb->prepare( $sql, $this->profile_id(), $key, absint( $local['id'] ?? 0 ), absint( $local['post_id'] ?? 0 ), esc_url_raw( $local['content_url'] ?? '' ), absint( $remote['id'] ?? 0 ), sanitize_text_field( $remote['source_site_hash'] ?? '' ), sanitize_text_field( $remote['source_site_label'] ?? '' ), esc_url_raw( $remote['content_url'] ?? '' ), sanitize_key( $finding['category'] ?? '' ), sanitize_key( $finding['severity'] ?? 'medium' ), (float) ( $finding['risk_score'] ?? 0 ), sanitize_text_field( $finding['summary'] ?? '' ), wp_json_encode( $finding['evidence'] ?? array() ), empty( $finding['blocks_review'] ) ? 0 : 1, $now, $now, $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return false === $result ? '' : $key;
	}

	private function resolve_stale_findings( array $active_keys ) {
		global $wpdb;
		if ( ! $active_keys ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$this->findings_table()} SET status='resolved',updated_at=%s WHERE profile_id=%s AND status='open'", current_time( 'mysql', true ), $this->profile_id() ) );
			return;
		}
		$placeholders = implode( ',', array_fill( 0, count( $active_keys ), '%s' ) );
		$args = array_merge( array( current_time( 'mysql', true ), $this->profile_id() ), $active_keys );
		$sql = "UPDATE {$this->findings_table()} SET status='resolved',updated_at=%s WHERE profile_id=%s AND status='open' AND finding_key NOT IN ({$placeholders})";
		$wpdb->query( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private function enforce_publisher_gates() {
		global $wpdb;
		$items = $wpdb->prefix . 'ikon_seo_publisher_items';
		if ( ! $this->table_exists( $items ) ) { return; }
		$settings = Ikon_SEO_Plugin::settings();
		$wpdb->query( "UPDATE {$items} SET gate_status='not_reviewed' WHERE gate_status='blocked_portfolio'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( empty( $settings['portfolio_quality_block_review_ready'] ) ) { return; }
		$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT local_post_id FROM {$this->findings_table()} WHERE profile_id=%s AND status='open' AND blocks_review=1 AND local_post_id > 0", $this->profile_id() ) );
		foreach ( array_map( 'absint', $post_ids ) as $post_id ) {
			$wpdb->update( $items, array( 'gate_status' => 'blocked_portfolio', 'updated_at' => current_time( 'mysql', true ) ), array( 'target_post_id' => $post_id ) );
		}
	}

	private function publish_operating_plan_recommendations() {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_recommendations';
		if ( ! $this->table_exists( $table ) ) { return; }
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->findings_table()} WHERE profile_id=%s AND status='open' AND severity IN ('critical','high') ORDER BY risk_score DESC LIMIT 100", $this->profile_id() ), ARRAY_A );
		$now = current_time( 'mysql', true );
		foreach ( (array) $rows as $row ) {
			$key = hash( 'sha256', 'portfolio_quality|' . sanitize_text_field( $row['finding_key'] ?? '' ) );
			$sql = "INSERT INTO {$table} (recommendation_key,profile_id,post_id,target_url,source_module,category,root_cause,title,rationale,evidence_json,action_json,priority,confidence,business_value,effort,status,approval_required,baseline_snapshot_id,workflow_task_id,completion_notes,completed_at,created_by,created_at,updated_at) VALUES (%s,%s,%d,%s,'portfolio_quality','quality','portfolio_footprint',%s,%s,%s,%s,%d,'medium',4,3,'proposed',1,0,0,'',NULL,0,%s,%s) ON DUPLICATE KEY UPDATE title=VALUES(title),rationale=VALUES(rationale),evidence_json=VALUES(evidence_json),priority=VALUES(priority),updated_at=VALUES(updated_at)";
			$wpdb->query( $wpdb->prepare( $sql, $key, $this->profile_id(), absint( $row['local_post_id'] ?? 0 ), esc_url_raw( $row['local_url'] ?? '' ), sanitize_text_field( 'Review portfolio quality risk: ' . ( $row['category'] ?? 'content overlap' ) ), sanitize_textarea_field( $row['summary'] ?? '' ), (string) ( $row['evidence_json'] ?? '{}' ), wp_json_encode( array( 'requires_human_review' => true, 'automatic_publication' => false ) ), min( 100, max( 50, absint( $row['risk_score'] ?? 70 ) ) ), $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}

	private function list_profiles( $source, $limit, $for_export = false ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->profiles_table() ) ) { return array(); }
		$source = 'imported' === $source ? 'imported' : 'local';
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->profiles_table()} WHERE profile_id=%s AND source=%s AND status='active' ORDER BY updated_at DESC LIMIT %d", $this->profile_id(), $source, max( 1, min( self::MAX_ITEMS, absint( $limit ) ) ) ), ARRAY_A );
		$result = array();
		foreach ( (array) $rows as $row ) {
			$item = array(
				'id'                 => absint( $row['id'] ?? 0 ),
				'post_id'            => absint( $row['post_id'] ?? 0 ),
				'content_url'        => esc_url_raw( $row['content_url'] ?? '' ),
				'content_title'      => sanitize_text_field( $row['content_title'] ?? '' ),
				'content_type'       => sanitize_key( $row['content_type'] ?? '' ),
				'word_count'        => absint( $row['word_count'] ?? 0 ),
				'heading_count'      => absint( $row['heading_count'] ?? 0 ),
				'paragraph_count'    => absint( $row['paragraph_count'] ?? 0 ),
				'internal_links'     => absint( $row['internal_links'] ?? 0 ),
				'signature'          => $this->sanitize_hash_list( json_decode( $row['signature_json'] ?? '[]', true ) ?: array(), 200 ),
				'topics'             => $this->sanitize_terms( json_decode( $row['topics_json'] ?? '[]', true ) ?: array(), 40 ),
				'heading_hash'       => sanitize_text_field( $row['heading_hash'] ?? '' ),
				'template_hash'      => sanitize_text_field( $row['template_hash'] ?? '' ),
				'title_pattern_hash' => sanitize_text_field( $row['title_pattern_hash'] ?? '' ),
				'author_hash'        => sanitize_text_field( $row['author_hash'] ?? '' ),
				'media_hashes'       => $this->sanitize_hash_list( json_decode( $row['media_hashes_json'] ?? '[]', true ) ?: array(), 30 ),
				'publish_pattern'    => sanitize_text_field( $row['publish_pattern'] ?? '' ),
				'published_at'       => sanitize_text_field( $row['published_at'] ?? '' ),
				'scanned_at'         => sanitize_text_field( $row['scanned_at'] ?? '' ),
			);
			if ( ! $for_export ) {
				$item['source_site_label'] = sanitize_text_field( $row['source_site_label'] ?? '' );
			}
			$result[] = $item;
		}
		return $result;
	}

	private function list_findings( $limit ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->findings_table() ) ) { return array(); }
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->findings_table()} WHERE profile_id=%s ORDER BY FIELD(status,'open','reviewed','accepted','resolved','dismissed'), FIELD(severity,'critical','high','medium','low'), risk_score DESC, updated_at DESC LIMIT %d", $this->profile_id(), max( 1, min( 500, absint( $limit ) ) ) ), ARRAY_A );
		return array_map( function( $row ) { return array( 'id' => absint( $row['id'] ?? 0 ), 'post_id' => absint( $row['local_post_id'] ?? 0 ), 'local_url' => esc_url_raw( $row['local_url'] ?? '' ), 'compared_site' => sanitize_text_field( $row['compared_site_label'] ?? '' ), 'compared_url' => esc_url_raw( $row['compared_url'] ?? '' ), 'category' => sanitize_key( $row['category'] ?? '' ), 'severity' => sanitize_key( $row['severity'] ?? '' ), 'risk_score' => (float) ( $row['risk_score'] ?? 0 ), 'summary' => sanitize_text_field( $row['summary'] ?? '' ), 'evidence' => json_decode( $row['evidence_json'] ?? '{}', true ) ?: array(), 'status' => sanitize_key( $row['status'] ?? '' ), 'blocks_review' => ! empty( $row['blocks_review'] ), 'review_notes' => sanitize_textarea_field( $row['review_notes'] ?? '' ), 'updated_at' => sanitize_text_field( $row['updated_at'] ?? '' ) ); }, $rows ?: array() );
	}

	private function portfolio_sites() {
		global $wpdb;
		if ( ! $this->table_exists( $this->profiles_table() ) ) { return array(); }
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT source_site_hash,source_site_label,COUNT(*) AS pages,MAX(updated_at) AS updated_at FROM {$this->profiles_table()} WHERE profile_id=%s AND source='imported' AND status='active' GROUP BY source_site_hash,source_site_label ORDER BY pages DESC LIMIT 200", $this->profile_id() ), ARRAY_A );
		return array_map( function( $row ) { return array( 'site_hash' => sanitize_text_field( $row['source_site_hash'] ?? '' ), 'site_label' => sanitize_text_field( $row['source_site_label'] ?? '' ), 'pages' => absint( $row['pages'] ?? 0 ), 'updated_at' => sanitize_text_field( $row['updated_at'] ?? '' ) ); }, $rows ?: array() );
	}

	private function public_settings() {
		$settings = Ikon_SEO_Plugin::settings();
		return array(
			'enabled'             => ! empty( $settings['portfolio_quality_enabled'] ),
			'scan_batch'          => absint( $settings['portfolio_quality_scan_batch'] ?? 25 ),
			'content_threshold'   => absint( $settings['portfolio_quality_content_threshold'] ?? 72 ),
			'topic_threshold'     => absint( $settings['portfolio_quality_topic_threshold'] ?? 80 ),
			'template_threshold'  => absint( $settings['portfolio_quality_template_threshold'] ?? 90 ),
			'thin_words'          => absint( $settings['portfolio_quality_thin_words'] ?? 450 ),
			'cluster_min'         => absint( $settings['portfolio_quality_cluster_min'] ?? 4 ),
			'block_review_ready'  => ! empty( $settings['portfolio_quality_block_review_ready'] ),
			'media_hashing'       => ! empty( $settings['portfolio_quality_media_hashing'] ),
			'retention_days'      => absint( $settings['portfolio_quality_retention_days'] ?? 365 ),
		);
	}

	private function blocked_pipeline_count() {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_publisher_items';
		return $this->table_exists( $table ) ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE gate_status='blocked_portfolio'" ) ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private function headings( $content ) {
		$headings = array();
		if ( preg_match_all( '/<h([1-6])\b[^>]*>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$text = $this->normalise_text( wp_strip_all_tags( $match[2] ) );
				$headings[] = 'h' . $match[1] . ':' . implode( '-', array_slice( preg_split( '/\s+/', $text ), 0, 8 ) );
			}
		}
		return array_slice( $headings, 0, 80 );
	}

	private function template_sequence( $content ) {
		$sequence = array();
		if ( function_exists( 'parse_blocks' ) ) {
			$walk = function( $blocks ) use ( &$walk, &$sequence ) {
				foreach ( (array) $blocks as $block ) {
					$sequence[] = sanitize_key( $block['blockName'] ?? 'html' );
					if ( ! empty( $block['innerBlocks'] ) ) { $walk( $block['innerBlocks'] ); }
					if ( count( $sequence ) >= 120 ) { return; }
				}
			};
			$walk( parse_blocks( $content ) );
		}
		if ( ! array_filter( $sequence ) && preg_match_all( '/<(section|article|div|h[1-6]|p|ul|ol|table|figure|form)\b/i', $content, $matches ) ) {
			$sequence = array_map( 'strtolower', array_slice( $matches[1], 0, 120 ) );
		}
		return array_values( array_filter( $sequence ) );
	}

	private function internal_link_count( $content ) {
		$count = 0;
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( preg_match_all( '/<a\b[^>]+href=["\']([^"\']+)["\']/i', $content, $matches ) ) {
			foreach ( $matches[1] as $href ) {
				$link_host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
				if ( 0 === strpos( $href, '/' ) || ( $link_host && $link_host === $host ) ) { $count++; }
			}
		}
		return $count;
	}

	private function media_hashes( WP_Post $post ) {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['portfolio_quality_media_hashing'] ) ) { return array(); }
		$ids = array();
		$featured = get_post_thumbnail_id( $post );
		if ( $featured ) { $ids[] = $featured; }
		if ( preg_match_all( '/wp-image-(\d+)/', $post->post_content, $matches ) ) {
			$ids = array_merge( $ids, array_map( 'absint', $matches[1] ) );
		}
		$hashes = array();
		foreach ( array_slice( array_values( array_unique( array_filter( $ids ) ) ), 0, 12 ) as $id ) {
			$file = get_attached_file( $id );
			if ( ! $file || ! is_readable( $file ) || filesize( $file ) > 10485760 ) { continue; }
			$hash = hash_file( 'sha256', $file );
			if ( $hash ) { $hashes[] = $hash; }
		}
		return array_values( array_unique( $hashes ) );
	}

	private function title_pattern( $title ) {
		$title = $this->normalise_text( $title );
		$title = preg_replace( '/\b(?:in|near|at|for)\s+[a-z0-9\s-]+$/', ' in-location', $title );
		$title = preg_replace( '/\b\d+\b/', 'number', $title );
		return trim( preg_replace( '/\s+/', ' ', $title ) );
	}

	private function meaningful_tokens( $text ) {
		$text = $this->normalise_text( $text );
		$parts = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		$stop = array_flip( array( 'the','and','for','with','that','this','from','your','you','our','are','was','were','have','has','had','not','but','can','will','into','than','then','their','they','them','its','also','about','how','what','when','where','which','who','why','a','an','to','of','in','on','at','by','or','as','is','be','it','we' ) );
		$tokens = array();
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( strlen( $part ) < 3 || isset( $stop[ $part ] ) || is_numeric( $part ) ) { continue; }
			$tokens[] = substr( $part, 0, 50 );
			if ( count( $tokens ) >= 1500 ) { break; }
		}
		return $tokens;
	}

	private function signature_from_tokens( array $tokens ) {
		$tokens = array_values( array_filter( $tokens ) );
		$shingles = array();
		for ( $i = 0, $count = count( $tokens ); $i < $count; $i += 3 ) {
			$chunk = array_slice( $tokens, $i, 5 );
			if ( count( $chunk ) >= 3 ) { $shingles[] = substr( hash( 'sha256', implode( '|', $chunk ) ), 0, 16 ); }
		}
		sort( $shingles, SORT_STRING );
		return array_slice( array_values( array_unique( $shingles ) ), 0, 200 );
	}

	private function jaccard( array $a, array $b ) {
		$a = array_values( array_unique( array_filter( array_map( 'strval', $a ) ) ) );
		$b = array_values( array_unique( array_filter( array_map( 'strval', $b ) ) ) );
		if ( ! $a || ! $b ) { return 0; }
		$intersection = count( array_intersect( $a, $b ) );
		$union = count( array_unique( array_merge( $a, $b ) ) );
		return $union ? $intersection / $union : 0;
	}

	private function sanitize_hash_list( $values, $limit ) {
		$result = array();
		foreach ( array_slice( (array) $values, 0, $limit ) as $value ) {
			$value = preg_replace( '/[^a-f0-9]/', '', strtolower( (string) $value ) );
			if ( strlen( $value ) >= 12 && strlen( $value ) <= 64 ) { $result[] = $value; }
		}
		return array_values( array_unique( $result ) );
	}

	private function sanitize_terms( $values, $limit ) {
		$result = array();
		foreach ( array_slice( (array) $values, 0, $limit ) as $value ) {
			$value = sanitize_text_field( $value );
			if ( $value ) { $result[] = substr( $value, 0, 80 ); }
		}
		return array_values( array_unique( $result ) );
	}

	private function normalise_text( $text ) {
		$text = strtolower( remove_accents( wp_strip_all_tags( (string) $text ) ) );
		$text = preg_replace( '/[^a-z0-9\s-]+/', ' ', $text );
		return trim( preg_replace( '/\s+/', ' ', $text ) );
	}

	private function normalise_url( $url ) {
		$parts = wp_parse_url( esc_url_raw( $url ) );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) { return ''; }
		$scheme = strtolower( $parts['scheme'] ?? 'https' );
		$host = strtolower( $parts['host'] );
		$path = '/' . ltrim( preg_replace( '#/+#', '/', rawurldecode( $parts['path'] ?? '/' ) ), '/' );
		if ( '/' !== $path ) { $path = untrailingslashit( $path ); }
		return $scheme . '://' . $host . $path;
	}

	private function mysql_date_or_null( $value ) {
		$timestamp = strtotime( (string) $value . ' UTC' );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	private function profile_id() {
		$profile = $this->profile->get();
		return sanitize_text_field( $profile['profile_id'] ?? $this->profile->fingerprint() );
	}

	private function tables_ready() {
		return $this->table_exists( $this->profiles_table() ) && $this->table_exists( $this->findings_table() ) && $this->table_exists( $this->imports_table() );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	private function history_event( $category, $status, $title, $summary, array $details, $user_id = 0, $post_id = 0 ) {
		if ( method_exists( $this->history, 'add' ) ) {
			$this->history->add( array( 'category' => sanitize_key( $category ), 'status' => sanitize_key( $status ), 'title' => sanitize_text_field( $title ), 'summary' => sanitize_textarea_field( $summary ), 'details' => $details, 'source' => 'wordpress', 'related_post_id' => absint( $post_id ), 'created_by' => absint( $user_id ) ) );
		}
	}
}
