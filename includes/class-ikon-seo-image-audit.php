<?php

defined( 'ABSPATH' ) || exit;

/** Read-only image metadata audit. */
final class Ikon_SEO_Image_Audit {
	const CACHE_KEY = 'ikon_seo_image_audit_v1';

	public function scan( $refresh = false ) {
		if ( $refresh ) {
			delete_transient( self::CACHE_KEY );
		}
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			$cached['cached'] = true;
			return $cached;
		}

		$settings = Ikon_SEO_Plugin::settings();
		$limit    = max( 50, min( 1000, absint( $settings['image_audit_limit'] ?? 300 ) ) );
		$images   = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$items   = array();
		$alt_map = array();
		foreach ( $images as $image ) {
			$alt       = trim( (string) get_post_meta( $image->ID, '_wp_attachment_image_alt', true ) );
			$filename  = pathinfo( (string) get_attached_file( $image->ID ), PATHINFO_FILENAME );
			$normalized_filename = $this->normalize( str_replace( array( '-', '_' ), ' ', $filename ) );
			$normalized_alt      = $this->normalize( $alt );
			$issues = array();
			if ( '' === $alt ) {
				$issues[] = 'missing_alt';
			} elseif ( $normalized_alt && $normalized_filename === $normalized_alt ) {
				$issues[] = 'filename_alt';
			}
			if ( '' === trim( (string) $image->post_excerpt ) ) {
				$issues[] = 'missing_caption';
			}
			if ( '' === trim( (string) $image->post_content ) ) {
				$issues[] = 'missing_description';
			}
			if ( $normalized_alt ) {
				$alt_map[ $normalized_alt ][] = (int) $image->ID;
			}

			$items[ $image->ID ] = array(
				'id'          => (int) $image->ID,
				'title'       => sanitize_text_field( $image->post_title ),
				'url'         => esc_url_raw( wp_get_attachment_url( $image->ID ) ),
				'edit_url'    => esc_url_raw( get_edit_post_link( $image->ID, '' ) ),
				'alt'         => $alt,
				'caption'     => sanitize_text_field( $image->post_excerpt ),
				'description' => wp_strip_all_tags( $image->post_content ),
				'filename'    => sanitize_file_name( basename( (string) get_attached_file( $image->ID ) ) ),
				'attached_to' => (int) $image->post_parent,
				'issues'      => $issues,
			);
		}

		$duplicate_groups = array();
		foreach ( $alt_map as $alt => $ids ) {
			if ( count( $ids ) < 2 ) {
				continue;
			}
			$duplicate_groups[] = array( 'alt' => $alt, 'image_ids' => $ids );
			foreach ( $ids as $id ) {
				$items[ $id ]['issues'][] = 'duplicate_alt';
			}
		}

		$items = array_values( $items );
		$result = array(
			'generated_at' => current_time( 'mysql', true ),
			'cached'       => false,
			'limit'        => $limit,
			'truncated'    => count( $images ) >= $limit,
			'summary'      => array(
				'total'               => count( $items ),
				'with_issues'         => count( array_filter( $items, function( $item ) { return ! empty( $item['issues'] ); } ) ),
				'missing_alt'         => $this->issue_count( $items, 'missing_alt' ),
				'filename_alt'        => $this->issue_count( $items, 'filename_alt' ),
				'duplicate_alt_groups'=> count( $duplicate_groups ),
				'missing_caption'     => $this->issue_count( $items, 'missing_caption' ),
				'missing_description' => $this->issue_count( $items, 'missing_description' ),
			),
			'duplicate_alt_groups' => $duplicate_groups,
			'items' => $items,
		);

		set_transient( self::CACHE_KEY, $result, 10 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	private function normalize( $value ) {
		$value = strtolower( trim( wp_strip_all_tags( (string) $value ) ) );
		return preg_replace( '/\\s+/', ' ', $value );
	}

	private function issue_count( array $items, $issue ) {
		return count( array_filter( $items, function( $item ) use ( $issue ) { return in_array( $issue, (array) $item['issues'], true ); } ) );
	}
}
