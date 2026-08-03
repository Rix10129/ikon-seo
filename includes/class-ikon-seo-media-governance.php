<?php

defined( 'ABSPATH' ) || exit;

/**
 * Media and image governance with privacy-conscious rights records.
 *
 * The module audits metadata, file characteristics, usage and provenance. It
 * never rewrites alt text, replaces media or changes published page content.
 */
final class Ikon_SEO_Media_Governance {
	const CACHE_KEY = 'ikon_seo_media_governance_report_v1';

	private $history;
	private $logger;

	public function __construct( Ikon_SEO_Workspace_History $history, Ikon_SEO_Logger $logger ) {
		$this->history = $history;
		$this->logger  = $logger;
	}

	public function table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_media_assets';
	}

	public function runs_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_governance_runs';
	}

	public function status() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		$table = $this->table();
		if ( ! $this->table_exists( $table ) ) {
			return array(
				'ready'   => false,
				'enabled' => ! empty( $settings['structured_media_governance_enabled'] ),
				'message' => __( 'Media governance tables are unavailable. Reactivate Ikon SEO to repair the database.', 'ikon-seo' ),
			);
		}
		$row = $wpdb->get_row(
			"SELECT COUNT(*) audited_assets,
			SUM(CASE WHEN issue_count > 0 THEN 1 ELSE 0 END) assets_with_issues,
			SUM(CASE WHEN alt_text='' THEN 1 ELSE 0 END) missing_alt,
			SUM(CASE WHEN duplicate_group_size > 1 THEN 1 ELSE 0 END) duplicate_assets,
			SUM(CASE WHEN usage_count=0 THEN 1 ELSE 0 END) unused_assets,
			SUM(CASE WHEN source_type='unknown' THEN 1 ELSE 0 END) unknown_source,
			MAX(checked_at) last_checked
			FROM {$table}",
			ARRAY_A
		);
		return array(
			'ready'              => true,
			'enabled'            => ! empty( $settings['structured_media_governance_enabled'] ),
			'audited_assets'     => absint( $row['audited_assets'] ?? 0 ),
			'assets_with_issues' => absint( $row['assets_with_issues'] ?? 0 ),
			'missing_alt'        => absint( $row['missing_alt'] ?? 0 ),
			'duplicate_assets'   => absint( $row['duplicate_assets'] ?? 0 ),
			'unused_assets'      => absint( $row['unused_assets'] ?? 0 ),
			'unknown_source'     => absint( $row['unknown_source'] ?? 0 ),
			'last_checked'       => sanitize_text_field( $row['last_checked'] ?? '' ),
			'changes_media'      => false,
		);
	}

	public function report( $limit = 100 ) {
		global $wpdb;
		$limit = max( 10, min( 500, absint( $limit ) ) );
		$table = $this->table();
		if ( ! $this->table_exists( $table ) ) {
			return array( 'status' => $this->status(), 'items' => array(), 'page_gaps' => array() );
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				ORDER BY issue_count DESC, file_size DESC, checked_at DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		$items = array();
		$issue_counts = array();
		foreach ( (array) $rows as $row ) {
			$issues = $this->decode_json( $row['issues_json'] ?? '' );
			foreach ( $issues as $issue ) {
				$code = sanitize_key( $issue['code'] ?? 'unknown' );
				$issue_counts[ $code ] = absint( $issue_counts[ $code ] ?? 0 ) + 1;
			}
			$items[] = array(
				'attachment_id'       => absint( $row['attachment_id'] ?? 0 ),
				'edit_url'            => get_edit_post_link( absint( $row['attachment_id'] ?? 0 ), '' ),
				'url'                 => esc_url_raw( $row['url'] ?? '' ),
				'filename'            => sanitize_file_name( $row['filename'] ?? '' ),
				'mime_type'           => sanitize_text_field( $row['mime_type'] ?? '' ),
				'width'               => absint( $row['width'] ?? 0 ),
				'height'              => absint( $row['height'] ?? 0 ),
				'file_size'           => absint( $row['file_size'] ?? 0 ),
				'usage_count'         => absint( $row['usage_count'] ?? 0 ),
				'featured_usage'      => absint( $row['featured_usage'] ?? 0 ),
				'social_usage'        => absint( $row['social_usage'] ?? 0 ),
				'alt_text'            => sanitize_text_field( $row['alt_text'] ?? '' ),
				'source_type'         => sanitize_key( $row['source_type'] ?? 'unknown' ),
				'source_url'          => esc_url_raw( $row['source_url'] ?? '' ),
				'license_name'        => sanitize_text_field( $row['license_name'] ?? '' ),
				'license_url'         => esc_url_raw( $row['license_url'] ?? '' ),
				'creator'             => sanitize_text_field( $row['creator'] ?? '' ),
				'rights_notes'        => sanitize_textarea_field( $row['rights_notes'] ?? '' ),
				'duplicate_group_size'=> absint( $row['duplicate_group_size'] ?? 0 ),
				'issues'              => $issues,
				'issue_count'         => absint( $row['issue_count'] ?? 0 ),
				'checked_at'          => sanitize_text_field( $row['checked_at'] ?? '' ),
			);
		}
		arsort( $issue_counts );
		return array(
			'status'       => $this->status(),
			'items'        => $items,
			'issue_counts' => $issue_counts,
			'page_gaps'    => $this->page_media_gaps( min( 200, $limit ) ),
			'limitations'  => array(
				__( 'Missing alt text is not automatically an error for a genuinely decorative image; human review is required.', 'ikon-seo' ),
				__( 'A duplicate file hash shows identical bytes on this website, not necessarily a licensing or editorial violation.', 'ikon-seo' ),
			),
		);
	}

	public function audit_batch( $limit = 50, $force = false, $user_id = 0, $source = 'manual' ) {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['structured_media_governance_enabled'] ) ) {
			return new WP_Error( 'ikon_seo_governance_disabled', __( 'Structured Data and Media Governance is disabled.', 'ikon-seo' ) );
		}
		if ( ! $this->table_exists( $this->table() ) ) {
			return new WP_Error( 'ikon_seo_media_table', __( 'The media-governance table is unavailable.', 'ikon-seo' ) );
		}
		$limit = max( 1, min( 500, absint( $limit ) ) );
		$args = array();
		$where = '';
		if ( ! $force ) {
			$stale_days = max( 1, min( 365, absint( $settings['media_governance_stale_days'] ?? 30 ) ) );
			$where = ' AND (m.checked_at IS NULL OR m.checked_at < %s)';
			$args[] = gmdate( 'Y-m-d H:i:s', time() - $stale_days * DAY_IN_SECONDS );
		}
		$args[] = $limit;
		$sql = "SELECT p.ID
			FROM {$wpdb->posts} p
			LEFT JOIN {$this->table()} m ON m.attachment_id = p.ID
			WHERE p.post_type='attachment' AND p.post_status='inherit' AND p.post_mime_type LIKE 'image/%' {$where}
			ORDER BY CASE WHEN m.checked_at IS NULL THEN 0 ELSE 1 END, p.post_date_gmt DESC
			LIMIT %d";
		$ids = $wpdb->get_col( $wpdb->prepare( $sql, $args ) );
		$run_id = $this->start_run( 'media', $source );
		$processed = 0;
		$errors = 0;
		$items = array();
		foreach ( (array) $ids as $id ) {
			$result = $this->audit_attachment( absint( $id ), $user_id, $source );
			if ( is_wp_error( $result ) ) {
				$errors++;
				$items[] = array( 'attachment_id' => absint( $id ), 'error' => $result->get_error_message() );
			} else {
				$processed++;
				$items[] = $result;
			}
		}
		$this->finish_run( $run_id, $processed, $errors, array( 'source' => $source ) );
		delete_transient( self::CACHE_KEY );
		if ( $user_id ) {
			$this->history->add(
				array(
					'category' => 'audit',
					'status'   => $errors ? 'partial' : 'completed',
					'title'    => 'Media governance batch completed',
					'summary'  => sprintf( '%d image assets were reviewed and %d processing errors were recorded.', $processed, $errors ),
					'details'  => array( 'processed' => $processed, 'errors' => $errors, 'source' => $source ),
				),
				'governance',
				$user_id
			);
		}
		return array( 'processed' => $processed, 'errors' => $errors, 'items' => $items, 'status' => $this->status() );
	}

	public function audit_attachment( $attachment_id, $user_id = 0, $source = 'manual' ) {
		global $wpdb;
		$attachment_id = absint( $attachment_id );
		$post = get_post( $attachment_id );
		if ( ! $post instanceof WP_Post || 'attachment' !== $post->post_type || 0 !== strpos( (string) get_post_mime_type( $post ), 'image/' ) ) {
			return new WP_Error( 'ikon_seo_media_asset', __( 'Choose a valid image attachment.', 'ikon-seo' ) );
		}
		$file = get_attached_file( $attachment_id );
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$metadata = is_array( $metadata ) ? $metadata : array();
		$url = wp_get_attachment_url( $attachment_id );
		$file_size = $file && is_readable( $file ) ? filesize( $file ) : 0;
		$file_size = false === $file_size ? 0 : absint( $file_size );
		$file_hash = '';
		$settings = Ikon_SEO_Plugin::settings();
		if ( ! empty( $settings['media_governance_file_hashes'] ) && $file && is_readable( $file ) && $file_size <= 25 * MB_IN_BYTES ) {
			$file_hash = (string) hash_file( 'sha256', $file );
		}

		$alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		$filename = sanitize_file_name( basename( (string) $file ) );
		$source_type = sanitize_key( get_post_meta( $attachment_id, '_ikon_seo_media_source_type', true ) ?: 'unknown' );
		if ( ! in_array( $source_type, array( 'original', 'licensed', 'client_supplied', 'generated', 'public_domain', 'unknown' ), true ) ) {
			$source_type = 'unknown';
		}
		$rights = array(
			'source_url'   => esc_url_raw( get_post_meta( $attachment_id, '_ikon_seo_media_source_url', true ) ),
			'license_name' => sanitize_text_field( get_post_meta( $attachment_id, '_ikon_seo_media_license_name', true ) ),
			'license_url'  => esc_url_raw( get_post_meta( $attachment_id, '_ikon_seo_media_license_url', true ) ),
			'creator'      => sanitize_text_field( get_post_meta( $attachment_id, '_ikon_seo_media_creator', true ) ),
			'rights_notes' => sanitize_textarea_field( get_post_meta( $attachment_id, '_ikon_seo_media_rights_notes', true ) ),
		);
		$usage = $this->usage_counts( $attachment_id, $url );
		$issues = $this->asset_issues(
			array(
				'alt'         => $alt,
				'filename'    => $filename,
				'width'       => absint( $metadata['width'] ?? 0 ),
				'height'      => absint( $metadata['height'] ?? 0 ),
				'file_size'   => $file_size,
				'mime_type'   => (string) get_post_mime_type( $post ),
				'usage_count' => $usage['usage_count'],
				'created_at'  => $post->post_date_gmt,
				'source_type' => $source_type,
				'rights'      => $rights,
			)
		);

		$now = current_time( 'mysql', true );
		$data = array(
			'attachment_id'        => $attachment_id,
			'url'                  => esc_url_raw( $url ),
			'filename'             => $filename,
			'file_hash'            => $file_hash,
			'mime_type'            => sanitize_text_field( get_post_mime_type( $post ) ),
			'width'                => absint( $metadata['width'] ?? 0 ),
			'height'               => absint( $metadata['height'] ?? 0 ),
			'file_size'            => $file_size,
			'usage_count'          => absint( $usage['usage_count'] ),
			'featured_usage'       => absint( $usage['featured_usage'] ),
			'social_usage'         => absint( $usage['social_usage'] ),
			'alt_text'             => $alt,
			'caption'              => sanitize_text_field( $post->post_excerpt ),
			'description_present'  => '' !== trim( wp_strip_all_tags( $post->post_content ) ) ? 1 : 0,
			'source_type'          => $source_type,
			'source_url'           => $rights['source_url'],
			'license_name'         => $rights['license_name'],
			'license_url'          => $rights['license_url'],
			'creator'              => $rights['creator'],
			'rights_notes'         => $rights['rights_notes'],
			'issues_json'          => wp_json_encode( $issues ),
			'issue_count'          => count( $issues ),
			'duplicate_group_size' => 1,
			'checked_at'           => $now,
			'updated_at'           => $now,
		);
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT attachment_id FROM {$this->table()} WHERE attachment_id=%d", $attachment_id ) );
		if ( $existing ) {
			$wpdb->update( $this->table(), $data, array( 'attachment_id' => $attachment_id ) );
		} else {
			$data['created_at'] = $now;
			$wpdb->insert( $this->table(), $data );
		}

		$duplicate_size = 1;
		if ( $file_hash ) {
			$duplicate_size = max( 1, absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE file_hash=%s AND file_hash<>''", $file_hash ) ) ) );
			$wpdb->update( $this->table(), array( 'duplicate_group_size' => $duplicate_size ), array( 'file_hash' => $file_hash ) );
			if ( $duplicate_size > 1 && ! $this->has_issue( $issues, 'duplicate_file' ) ) {
				$issues[] = $this->issue( 'duplicate_file', 'warning', __( 'Identical image bytes are stored more than once on this website.', 'ikon-seo' ) );
				$wpdb->update(
					$this->table(),
					array( 'issues_json' => wp_json_encode( $issues ), 'issue_count' => count( $issues ) ),
					array( 'attachment_id' => $attachment_id )
				);
			}
		}
		delete_transient( self::CACHE_KEY );

		if ( $user_id ) {
			$this->history->add(
				array(
					'category' => 'audit',
					'status'   => 'completed',
					'title'    => 'Media asset reviewed',
					'summary'  => sprintf( '%s was reviewed with %d governance findings.', $filename, count( $issues ) ),
					'details'  => array( 'attachment_id' => $attachment_id, 'source' => $source ),
				),
				'governance',
				$user_id
			);
		}

		return array(
			'attachment_id'        => $attachment_id,
			'url'                  => esc_url_raw( $url ),
			'filename'             => $filename,
			'width'                => absint( $metadata['width'] ?? 0 ),
			'height'               => absint( $metadata['height'] ?? 0 ),
			'file_size'            => $file_size,
			'usage_count'          => absint( $usage['usage_count'] ),
			'source_type'          => $source_type,
			'duplicate_group_size' => $duplicate_size,
			'issues'               => $issues,
			'checked_at'           => $now,
		);
	}

	public function save_rights( $attachment_id, array $payload, $user_id = 0 ) {
		$attachment_id = absint( $attachment_id );
		$post = get_post( $attachment_id );
		if ( ! $post instanceof WP_Post || 'attachment' !== $post->post_type || 0 !== strpos( (string) get_post_mime_type( $post ), 'image/' ) ) {
			return new WP_Error( 'ikon_seo_media_asset', __( 'Choose a valid image attachment.', 'ikon-seo' ) );
		}
		$source_type = sanitize_key( $payload['source_type'] ?? 'unknown' );
		$allowed = array( 'original', 'licensed', 'client_supplied', 'generated', 'public_domain', 'unknown' );
		if ( ! in_array( $source_type, $allowed, true ) ) {
			return new WP_Error( 'ikon_seo_media_source_type', __( 'Choose a supported media source type.', 'ikon-seo' ) );
		}
		$fields = array(
			'_ikon_seo_media_source_type'  => $source_type,
			'_ikon_seo_media_source_url'   => esc_url_raw( $payload['source_url'] ?? '' ),
			'_ikon_seo_media_license_name' => sanitize_text_field( $payload['license_name'] ?? '' ),
			'_ikon_seo_media_license_url'  => esc_url_raw( $payload['license_url'] ?? '' ),
			'_ikon_seo_media_creator'      => sanitize_text_field( $payload['creator'] ?? '' ),
			'_ikon_seo_media_rights_notes' => sanitize_textarea_field( $payload['rights_notes'] ?? '' ),
		);
		foreach ( $fields as $key => $value ) {
			if ( '' === $value && '_ikon_seo_media_source_type' !== $key ) {
				delete_post_meta( $attachment_id, $key );
			} else {
				update_post_meta( $attachment_id, $key, $value );
			}
		}
		$result = $this->audit_attachment( $attachment_id, 0, 'rights_update' );
		if ( $user_id && ! is_wp_error( $result ) ) {
			$this->history->add(
				array(
					'category' => 'content',
					'status'   => 'completed',
					'title'    => 'Media rights record updated',
					'summary'  => sprintf( 'The source and rights record for attachment %d was updated without changing the image or published pages.', $attachment_id ),
					'details'  => array( 'attachment_id' => $attachment_id, 'source_type' => $source_type ),
				),
				'governance',
				$user_id
			);
		}
		return $result;
	}

	public function cleanup() {
		global $wpdb;
		$deleted = 0;
		if ( $this->table_exists( $this->table() ) ) {
			$deleted = $wpdb->query(
				"DELETE m FROM {$this->table()} m
				LEFT JOIN {$wpdb->posts} p ON p.ID=m.attachment_id
				WHERE p.ID IS NULL OR p.post_type<>'attachment'"
			);
		}
		delete_transient( self::CACHE_KEY );
		return array( 'deleted_orphans' => absint( $deleted ) );
	}

	private function asset_issues( array $asset ) {
		$settings = Ikon_SEO_Plugin::settings();
		$issues = array();
		$alt = trim( (string) $asset['alt'] );
		$filename_label = strtolower( preg_replace( '/[-_]+/', ' ', pathinfo( (string) $asset['filename'], PATHINFO_FILENAME ) ) );
		$alt_normalized = strtolower( preg_replace( '/\s+/', ' ', $alt ) );
		if ( '' === $alt ) {
			$issues[] = $this->issue( 'missing_alt', 'warning', __( 'Alt text is empty. Confirm whether the image is decorative or needs a useful description.', 'ikon-seo' ) );
		} elseif ( $filename_label && $alt_normalized === trim( $filename_label ) ) {
			$issues[] = $this->issue( 'filename_alt', 'warning', __( 'Alt text appears to repeat the file name rather than describe the image purpose.', 'ikon-seo' ) );
		}
		$max_alt = max( 60, min( 300, absint( $settings['media_governance_alt_max_chars'] ?? 160 ) ) );
		if ( $this->text_length( $alt ) > $max_alt ) {
			$issues[] = $this->issue( 'alt_too_long', 'warning', sprintf( __( 'Alt text exceeds the configured %d-character review threshold.', 'ikon-seo' ), $max_alt ) );
		}
		if ( in_array( $alt_normalized, array( 'image', 'photo', 'picture', 'graphic', 'banner', 'logo image' ), true ) ) {
			$issues[] = $this->issue( 'generic_alt', 'warning', __( 'Alt text is too generic to explain the image purpose.', 'ikon-seo' ) );
		}
		if ( $this->repeated_words( $alt ) ) {
			$issues[] = $this->issue( 'repeated_alt_words', 'warning', __( 'Alt text repeats the same non-trivial word several times and needs editorial review.', 'ikon-seo' ) );
		}
		if ( empty( $asset['width'] ) || empty( $asset['height'] ) ) {
			$issues[] = $this->issue( 'missing_dimensions', 'error', __( 'WordPress image dimensions are unavailable.', 'ikon-seo' ) );
		}
		$large_kb = max( 100, min( 10000, absint( $settings['media_governance_large_file_kb'] ?? 500 ) ) );
		if ( absint( $asset['file_size'] ) > $large_kb * 1024 ) {
			$issues[] = $this->issue( 'large_file', 'warning', sprintf( __( 'The original file exceeds the configured %d KB review threshold.', 'ikon-seo' ), $large_kb ) );
		}
		if ( in_array( $asset['mime_type'], array( 'image/jpeg', 'image/png' ), true ) && absint( $asset['file_size'] ) > 250 * 1024 ) {
			$issues[] = $this->issue( 'modern_format_review', 'info', __( 'A modern-format or compression review may reduce this image file size.', 'ikon-seo' ) );
		}
		$age = ! empty( $asset['created_at'] ) ? time() - strtotime( $asset['created_at'] . ' UTC' ) : 0;
		if ( 0 === absint( $asset['usage_count'] ) && $age > 90 * DAY_IN_SECONDS ) {
			$issues[] = $this->issue( 'unused_media', 'info', __( 'No current parent, content, featured-image or social-preview usage was detected.', 'ikon-seo' ) );
		}
		if ( ! empty( $settings['media_governance_require_source_records'] ) && 'unknown' === $asset['source_type'] ) {
			$issues[] = $this->issue( 'source_record_missing', 'warning', __( 'A source or ownership record is required by the configured media policy.', 'ikon-seo' ) );
		}
		$rights = (array) $asset['rights'];
		if ( 'licensed' === $asset['source_type'] && ( empty( $rights['license_name'] ) || empty( $rights['license_url'] ) ) ) {
			$issues[] = $this->issue( 'license_record_incomplete', 'warning', __( 'Licensed media should record both the licence name and supporting URL.', 'ikon-seo' ) );
		}
		if ( 'generated' === $asset['source_type'] && empty( $rights['rights_notes'] ) ) {
			$issues[] = $this->issue( 'generation_record_incomplete', 'info', __( 'Generated media should include a short creation and review note.', 'ikon-seo' ) );
		}
		if ( in_array( $asset['source_type'], array( 'original', 'client_supplied' ), true ) && empty( $rights['creator'] ) ) {
			$issues[] = $this->issue( 'creator_record_missing', 'info', __( 'Recording the creator or rights owner improves future asset governance.', 'ikon-seo' ) );
		}
		return array_slice( $issues, 0, 100 );
	}

	private function usage_counts( $attachment_id, $url ) {
		global $wpdb;
		$parent = get_post_field( 'post_parent', $attachment_id ) ? 1 : 0;
		$featured = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT pm.post_id)
					FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON p.ID=pm.post_id AND p.post_status='publish'
					WHERE pm.meta_key='_thumbnail_id' AND pm.meta_value=%d",
					$attachment_id
				)
			)
		);
		$content_like_id = '%' . $wpdb->esc_like( 'wp-image-' . $attachment_id ) . '%';
		$content_like_url = '%' . $wpdb->esc_like( (string) $url ) . '%';
		$content = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts}
					WHERE post_status='publish' AND post_type NOT IN ('attachment','revision')
					AND (post_content LIKE %s OR post_content LIKE %s)",
					$content_like_id,
					$content_like_url
				)
			)
		);
		$social_keys = array( 'rank_math_facebook_image_id', 'rank_math_twitter_image_id', '_yoast_wpseo_opengraph-image-id', '_yoast_wpseo_twitter-image-id' );
		$placeholders = implode( ',', array_fill( 0, count( $social_keys ), '%s' ) );
		$args = array_merge( $social_keys, array( (string) $attachment_id ) );
		$social = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
					WHERE meta_key IN ({$placeholders}) AND meta_value=%s",
					$args
				)
			)
		);
		return array(
			'usage_count'    => $parent + $featured + $content + $social,
			'featured_usage' => $featured,
			'social_usage'   => $social,
		);
	}

	private function page_media_gaps( $limit ) {
		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] );
		$post_ids = get_posts(
			array(
				'post_type'      => array_values( $types ),
				'post_status'    => 'publish',
				'posts_per_page' => max( 10, min( 200, absint( $limit ) ) ),
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		$items = array();
		foreach ( (array) $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$content = (string) $post->post_content;
			preg_match_all( '#<img\b[^>]*>#i', $content, $images );
			$missing_dimensions = 0;
			$missing_alt_attribute = 0;
			foreach ( (array) ( $images[0] ?? array() ) as $tag ) {
				if ( ! preg_match( '/\bwidth\s*=\s*["\'][^"\']+["\']/i', $tag ) || ! preg_match( '/\bheight\s*=\s*["\'][^"\']+["\']/i', $tag ) ) {
					$missing_dimensions++;
				}
				if ( ! preg_match( '/\balt\s*=\s*["\'][^"\']*["\']/i', $tag ) ) {
					$missing_alt_attribute++;
				}
			}
			$featured = has_post_thumbnail( $post_id );
			$social = $featured || get_post_meta( $post_id, 'rank_math_facebook_image', true ) || get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true );
			$gap_codes = array();
			if ( 'post' === $post->post_type && ! $featured ) {
				$gap_codes[] = 'featured_image_missing';
			}
			if ( ! $social ) {
				$gap_codes[] = 'social_preview_missing';
			}
			if ( $missing_dimensions ) {
				$gap_codes[] = 'content_dimensions_missing';
			}
			if ( $missing_alt_attribute ) {
				$gap_codes[] = 'content_alt_attribute_missing';
			}
			if ( $gap_codes ) {
				$items[] = array(
					'post_id'               => absint( $post_id ),
					'title'                 => sanitize_text_field( get_the_title( $post_id ) ),
					'url'                   => esc_url_raw( get_permalink( $post_id ) ),
					'post_type'             => sanitize_key( $post->post_type ),
					'image_count'           => count( (array) ( $images[0] ?? array() ) ),
					'missing_dimensions'    => $missing_dimensions,
					'missing_alt_attribute' => $missing_alt_attribute,
					'gap_codes'             => $gap_codes,
				);
			}
		}
		return array_slice( $items, 0, 100 );
	}

	private function repeated_words( $text ) {
		$words = preg_split( '/[^\p{L}\p{N}]+/u', strtolower( (string) $text ) );
		$counts = array();
		foreach ( array_filter( (array) $words ) as $word ) {
			if ( $this->text_length( $word ) < 4 ) {
				continue;
			}
			$counts[ $word ] = absint( $counts[ $word ] ?? 0 ) + 1;
			if ( $counts[ $word ] >= 4 ) {
				return true;
			}
		}
		return false;
	}

	private function text_length( $text ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $text ) : strlen( (string) $text );
	}

	private function has_issue( array $issues, $code ) {
		foreach ( $issues as $issue ) {
			if ( $code === ( $issue['code'] ?? '' ) ) {
				return true;
			}
		}
		return false;
	}

	private function issue( $code, $severity, $message ) {
		return array(
			'code'     => sanitize_key( $code ),
			'severity' => in_array( $severity, array( 'error', 'warning', 'info' ), true ) ? $severity : 'info',
			'message'  => sanitize_text_field( $message ),
		);
	}

	private function decode_json( $value ) {
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function start_run( $type, $source ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->runs_table() ) ) {
			return 0;
		}
		$wpdb->insert(
			$this->runs_table(),
			array(
				'run_type'   => sanitize_key( $type ),
				'source'     => sanitize_key( $source ),
				'status'     => 'running',
				'started_at' => current_time( 'mysql', true ),
			)
		);
		return absint( $wpdb->insert_id );
	}

	private function finish_run( $run_id, $processed, $errors, array $summary ) {
		global $wpdb;
		if ( ! $run_id ) {
			return;
		}
		$wpdb->update(
			$this->runs_table(),
			array(
				'status'       => $errors ? 'partial' : 'completed',
				'processed'    => absint( $processed ),
				'errors'       => absint( $errors ),
				'summary_json' => wp_json_encode( $summary ),
				'completed_at' => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $run_id ) )
		);
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
