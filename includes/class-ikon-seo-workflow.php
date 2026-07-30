<?php

defined( 'ABSPATH' ) || exit;

class Ikon_SEO_Workflow {
	private $logger;
	private $schema;
	private $quality;
	private $local;

	public function __construct( Ikon_SEO_Logger $logger, Ikon_SEO_Schema $schema, Ikon_SEO_Quality $quality, Ikon_SEO_Local $local ) {
		$this->logger  = $logger;
		$this->schema  = $schema;
		$this->quality = $quality;
		$this->local   = $local;
	}

	public function reviews( $limit = 50 ) {
		$posts = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'draft', 'pending', 'private' ),
				'posts_per_page' => max( 1, min( 200, absint( $limit ) ) ),
				'meta_query'     => array(
					array(
						'key'     => '_ikon_seo_source_page_id',
						'compare' => 'EXISTS',
					),
				),
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		$output = array();
		foreach ( $posts as $post ) {
			$source_id = absint( get_post_meta( $post->ID, '_ikon_seo_source_page_id', true ) );
			$source    = get_post( $source_id );
			$report    = get_post_meta( $post->ID, '_ikon_seo_quality_report', true );
			$output[]  = array(
				'draft_id'       => (int) $post->ID,
				'draft_title'    => $post->post_title,
				'draft_status'   => $post->post_status,
				'draft_edit_url' => get_edit_post_link( $post->ID, 'raw' ),
				'source_id'      => $source_id,
				'source_title'   => $source ? $source->post_title : '',
				'source_url'     => $source ? get_permalink( $source ) : '',
				'quality_score'  => absint( is_array( $report ) ? ( $report['score'] ?? 0 ) : 0 ),
				'quality_status' => is_array( $report ) ? ( $report['status'] ?? 'not_checked' ) : 'not_checked',
				'merged'         => (bool) get_post_meta( $post->ID, '_ikon_seo_merged_to', true ),
				'modified_gmt'   => get_post_modified_time( 'c', true, $post ),
			);
		}
		return $output;
	}

	public function comparison( $draft_id ) {
		$draft = get_post( absint( $draft_id ) );
		if ( ! $draft || 'page' !== $draft->post_type ) {
			return new WP_Error( 'ikon_seo_review_not_found', 'Improvement draft not found.', array( 'status' => 404 ) );
		}
		$source_id = absint( get_post_meta( $draft->ID, '_ikon_seo_source_page_id', true ) );
		$source    = get_post( $source_id );
		if ( ! $source || 'page' !== $source->post_type ) {
			return new WP_Error( 'ikon_seo_source_not_found', 'The source page linked to this review no longer exists.', array( 'status' => 404 ) );
		}

		$before = $this->quality->post_snapshot( $source_id );
		$after  = $this->quality->post_snapshot( $draft->ID );
		return array(
			'draft_id' => (int) $draft->ID,
			'source_id'=> $source_id,
			'before'   => $before,
			'after'    => $after,
			'changes'  => array(
				'title_changed'            => $before['title'] !== $after['title'],
				'seo_title_changed'        => $before['seo_title'] !== $after['seo_title'],
				'description_changed'      => $before['seo_description'] !== $after['seo_description'],
				'focus_keyword_changed'    => $before['focus_keyword'] !== $after['focus_keyword'],
				'word_count_change'        => (int) $after['word_count'] - (int) $before['word_count'],
				'internal_link_change'     => count( $after['internal_links'] ) - count( $before['internal_links'] ),
				'heading_count_change'     => count( $after['headings'] ) - count( $before['headings'] ),
				'schema_added'             => array_values( array_diff( $after['schema_types'], $before['schema_types'] ) ),
				'schema_removed'           => array_values( array_diff( $before['schema_types'], $after['schema_types'] ) ),
				'featured_image_changed'   => $before['featured_media_id'] !== $after['featured_media_id'],
			),
			'can_merge' => ! get_post_meta( $draft->ID, '_ikon_seo_merged_to', true ),
		);
	}

	public function merge( $draft_id, $request_id = '' ) {
		$draft = get_post( absint( $draft_id ) );
		if ( ! $draft || 'page' !== $draft->post_type ) {
			return new WP_Error( 'ikon_seo_review_not_found', 'Improvement draft not found.', array( 'status' => 404 ) );
		}
		if ( get_post_meta( $draft->ID, '_ikon_seo_merged_to', true ) ) {
			return new WP_Error( 'ikon_seo_review_merged', 'This improvement draft has already been merged.', array( 'status' => 409 ) );
		}
		$quality = get_post_meta( $draft->ID, '_ikon_seo_quality_report', true );
		if ( ! is_array( $quality ) || 'needs_changes' === ( $quality['status'] ?? '' ) ) {
			return new WP_Error(
				'ikon_seo_quality_block',
				'This improvement draft has unresolved quality failures. Update and recheck the draft before merging.',
				array( 'status' => 409 )
			);
		}
		$draft_profile = (string) get_post_meta( $draft->ID, '_ikon_seo_profile_id', true );
		$current_profile = ( new Ikon_SEO_Profile() )->fingerprint();
		if ( ! $draft_profile || ! hash_equals( $current_profile, $draft_profile ) ) {
			return new WP_Error(
				'ikon_seo_profile_changed',
				'The Website Profile changed after this draft was created. Refresh or recreate the draft before merging.',
				array( 'status' => 409 )
			);
		}
		$local_errors = $this->local->validate_bound_page( $draft->ID );
		if ( $local_errors ) {
			return new WP_Error(
				'ikon_seo_local_changed',
				'The Local SEO record changed after this draft was checked: ' . implode( '; ', $local_errors ),
				array( 'status' => 409 )
			);
		}

		$source_id = absint( get_post_meta( $draft->ID, '_ikon_seo_source_page_id', true ) );
		$source    = get_post( $source_id );
		if ( ! $source || 'page' !== $source->post_type ) {
			return new WP_Error( 'ikon_seo_source_not_found', 'The source page linked to this review no longer exists.', array( 'status' => 404 ) );
		}

		$lock_key = 'ikon_seo_merge_lock_' . $source_id;
		if ( get_transient( $lock_key ) ) {
			return new WP_Error( 'ikon_seo_merge_locked', 'Another merge is currently running for this page.', array( 'status' => 409 ) );
		}
		set_transient( $lock_key, 1, 2 * MINUTE_IN_SECONDS );

		$snapshot = $this->snapshot( $source, $draft->ID );
		$snapshots = get_post_meta( $source_id, '_ikon_seo_snapshots', true );
		$snapshots = is_array( $snapshots ) ? $snapshots : array();
		array_unshift( $snapshots, $snapshot );
		$snapshots = array_slice( $snapshots, 0, 5 );
		update_post_meta( $source_id, '_ikon_seo_snapshots', $snapshots );

		if ( function_exists( 'wp_save_post_revision' ) ) {
			wp_save_post_revision( $source_id );
		}

		$result = wp_update_post(
			wp_slash(
				array(
					'ID'           => $source_id,
					'post_title'   => $draft->post_title,
					'post_excerpt' => $draft->post_excerpt,
					'post_content' => $draft->post_content,
					'post_parent'  => $draft->post_parent,
					'menu_order'   => $draft->menu_order,
				)
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			delete_transient( $lock_key );
			return $result;
		}

		$this->copy_managed_meta( $draft->ID, $source_id );
		$featured_id = get_post_thumbnail_id( $draft->ID );
		if ( $featured_id ) {
			set_post_thumbnail( $source_id, $featured_id );
		} else {
			delete_post_thumbnail( $source_id );
		}

		update_post_meta( $source_id, '_ikon_seo_last_merge_draft', $draft->ID );
		update_post_meta( $source_id, '_ikon_seo_last_merge_at', current_time( 'mysql', true ) );
		update_post_meta( $draft->ID, '_ikon_seo_merged_to', $source_id );
		update_post_meta( $draft->ID, '_ikon_seo_merged_at', current_time( 'mysql', true ) );
		update_post_meta( $draft->ID, '_ikon_seo_workflow_status', 'merged' );

		$this->refresh_builder( $source_id );
		delete_transient( $lock_key );

		$request_id = $request_id ?: wp_generate_uuid4();
		$this->logger->log( 'merge', 'success', 'Improvement draft merged into the original page.', $source_id, $draft->ID, array(), $request_id );
		do_action( 'ikon_seo_after_merge', $source_id, $draft->ID );

		return array(
			'ok'          => true,
			'source_id'   => $source_id,
			'draft_id'    => (int) $draft->ID,
			'live_url'    => get_permalink( $source_id ),
			'edit_url'    => get_edit_post_link( $source_id, 'raw' ),
			'snapshot_id' => $snapshot['id'],
			'request_id'  => $request_id,
		);
	}

	public function rollback( $source_id, $snapshot_id = '', $request_id = '' ) {
		$source = get_post( absint( $source_id ) );
		if ( ! $source || 'page' !== $source->post_type ) {
			return new WP_Error( 'ikon_seo_source_not_found', 'Source page not found.', array( 'status' => 404 ) );
		}

		$snapshots = get_post_meta( $source->ID, '_ikon_seo_snapshots', true );
		$snapshots = is_array( $snapshots ) ? $snapshots : array();
		$index     = null;
		foreach ( $snapshots as $key => $snapshot ) {
			if ( ! $snapshot_id || hash_equals( (string) ( $snapshot['id'] ?? '' ), (string) $snapshot_id ) ) {
				$index = $key;
				break;
			}
		}
		if ( null === $index ) {
			return new WP_Error( 'ikon_seo_snapshot_not_found', 'No matching Ikon SEO rollback snapshot was found.', array( 'status' => 404 ) );
		}

		$snapshot = $snapshots[ $index ];
		if ( function_exists( 'wp_save_post_revision' ) ) {
			wp_save_post_revision( $source->ID );
		}

		$result = wp_update_post(
			wp_slash(
				array_merge(
					array( 'ID' => $source->ID ),
					$snapshot['post']
				)
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		foreach ( $snapshot['meta'] as $key => $value ) {
			if ( null === $value ) {
				delete_post_meta( $source->ID, $key );
			} else {
				$this->write_meta( $source->ID, $key, $value );
			}
		}

		if ( ! empty( $snapshot['featured_media_id'] ) ) {
			set_post_thumbnail( $source->ID, absint( $snapshot['featured_media_id'] ) );
		} else {
			delete_post_thumbnail( $source->ID );
		}

		$snapshots[ $index ]['rolled_back_at'] = current_time( 'mysql', true );
		update_post_meta( $source->ID, '_ikon_seo_snapshots', $snapshots );
		update_post_meta( $source->ID, '_ikon_seo_last_rollback_at', current_time( 'mysql', true ) );

		$this->refresh_builder( $source->ID );
		$request_id = $request_id ?: wp_generate_uuid4();
		$this->logger->log( 'rollback', 'success', 'Page restored from an Ikon SEO snapshot.', $source->ID, absint( $snapshot['draft_id'] ?? 0 ), array(), $request_id );
		do_action( 'ikon_seo_after_rollback', $source->ID, $snapshot );

		return array(
			'ok'          => true,
			'source_id'   => (int) $source->ID,
			'snapshot_id' => $snapshot['id'],
			'live_url'    => get_permalink( $source->ID ),
			'edit_url'    => get_edit_post_link( $source->ID, 'raw' ),
			'request_id'  => $request_id,
		);
	}

	public function snapshots( $source_id ) {
		$snapshots = get_post_meta( absint( $source_id ), '_ikon_seo_snapshots', true );
		$output    = array();
		foreach ( is_array( $snapshots ) ? $snapshots : array() as $snapshot ) {
			$output[] = array(
				'id'             => $snapshot['id'] ?? '',
				'created_at'     => $snapshot['created_at'] ?? '',
				'draft_id'       => absint( $snapshot['draft_id'] ?? 0 ),
				'original_title' => $snapshot['post']['post_title'] ?? '',
				'rolled_back_at' => $snapshot['rolled_back_at'] ?? '',
			);
		}
		return $output;
	}

	private function snapshot( WP_Post $source, $draft_id ) {
		$meta = array();
		foreach ( $this->managed_meta_keys() as $key ) {
			$exists = metadata_exists( 'post', $source->ID, $key );
			$meta[ $key ] = $exists ? get_post_meta( $source->ID, $key, true ) : null;
		}
		return array(
			'id'                => wp_generate_uuid4(),
			'created_at'        => current_time( 'mysql', true ),
			'created_by'        => get_current_user_id(),
			'draft_id'          => absint( $draft_id ),
			'post'              => array(
				'post_title'   => $source->post_title,
				'post_excerpt' => $source->post_excerpt,
				'post_content' => $source->post_content,
				'post_parent'  => $source->post_parent,
				'menu_order'   => $source->menu_order,
			),
			'meta'              => $meta,
			'featured_media_id' => get_post_thumbnail_id( $source->ID ),
		);
	}

	private function copy_managed_meta( $from_id, $to_id ) {
		$from_url = get_permalink( $from_id );
		$to_url   = get_permalink( $to_id );
		foreach ( $this->managed_meta_keys() as $key ) {
			if ( metadata_exists( 'post', $from_id, $key ) ) {
				$value = get_post_meta( $from_id, $key, true );
				if ( '_ikon_seo_schema_graph' === $key && is_array( $value ) ) {
					$value = $this->rebase_schema_graph( $value, $from_url, $to_url );
				}
				$this->write_meta( $to_id, $key, $value );
			} else {
				delete_post_meta( $to_id, $key );
			}
		}
		update_post_meta( $to_id, '_ikon_seo_managed', 1 );
	}

	private function managed_meta_keys() {
		return array(
			'_ikon_seo_managed',
			'_ikon_seo_profile_id',
			'_ikon_seo_language',
			'_ikon_seo_payload_version',
			'_ikon_seo_component_version',
			'_ikon_seo_builder',
			'_ikon_seo_schema_graph',
			'_ikon_seo_quality_report',
			'_ikon_seo_content_review',
			'_ikon_seo_last_reviewed',
			'_ikon_seo_next_review',
			'_elementor_edit_mode',
			'_elementor_template_type',
			'_elementor_version',
			'_elementor_data',
			'_elementor_page_settings',
			'_wp_page_template',
			'rank_math_title',
			'rank_math_description',
			'rank_math_focus_keyword',
			'rank_math_canonical_url',
			'rank_math_robots',
			'rank_math_facebook_title',
			'rank_math_facebook_description',
			'rank_math_facebook_image',
			'rank_math_twitter_title',
			'rank_math_twitter_description',
			'rank_math_twitter_image',
			'rank_math_twitter_use_facebook',
			'_yoast_wpseo_title',
			'_yoast_wpseo_metadesc',
			'_yoast_wpseo_focuskw',
			'_yoast_wpseo_canonical',
			'_yoast_wpseo_meta-robots-noindex',
			'_yoast_wpseo_meta-robots-nofollow',
		);
	}

	private function refresh_builder( $post_id ) {
		delete_post_meta( $post_id, '_elementor_css' );
		clean_post_cache( $post_id );
		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			try {
				$css = \Elementor\Core\Files\CSS\Post::create( $post_id );
				if ( $css ) {
					$css->update();
				}
			} catch ( Throwable $exception ) {
				// Elementor will regenerate the CSS on the next normal page load.
			}
		}
	}

	private function write_meta( $post_id, $key, $value ) {
		if ( '_elementor_data' === $key && is_string( $value ) ) {
			$value = wp_slash( $value );
		}
		update_post_meta( $post_id, $key, $value );
	}

	private function rebase_schema_graph( $value, $from_url, $to_url ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = $this->rebase_schema_graph( $item, $from_url, $to_url );
			}
			return $value;
		}
		if ( is_string( $value ) && $from_url && 0 === strpos( $value, $from_url ) ) {
			return $to_url . substr( $value, strlen( $from_url ) );
		}
		return $value;
	}
}
