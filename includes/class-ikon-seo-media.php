<?php

defined( 'ABSPATH' ) || exit;

class Ikon_SEO_Media {
	public function search( $query = '', $limit = 20 ) {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/webp' ),
				's'              => sanitize_text_field( $query ),
				'posts_per_page' => max( 1, min( 50, absint( $limit ) ) ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return array_values( array_map( array( $this, 'summary' ), $attachments ) );
	}

	public function import( array $payload ) {
		if ( ! array_key_exists( 'alt_text', $payload ) ) {
			return new WP_Error( 'ikon_seo_media_alt', 'alt_text is required. Use an empty value only for a genuinely decorative image.', array( 'status' => 400 ) );
		}
		$url = esc_url_raw( $payload['source_url'] ?? '' );
		if ( ! $url || 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return new WP_Error( 'ikon_seo_media_url', 'A valid HTTPS source_url is required.', array( 'status' => 400 ) );
		}

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( ! $this->allowed_host( $host ) ) {
			return new WP_Error( 'ikon_seo_media_host', 'The image host is not approved in Ikon SEO settings.', array( 'status' => 403 ) );
		}

		$path      = (string) wp_parse_url( $url, PHP_URL_PATH );
		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, array( 'jpg', 'jpeg', 'png', 'webp' ), true ) ) {
			return new WP_Error( 'ikon_seo_media_type', 'Only JPG, PNG and WebP images can be imported.', array( 'status' => 400 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$temp = wp_tempnam( basename( $path ) );
		if ( ! $temp ) {
			return new WP_Error( 'ikon_seo_media_temp', 'WordPress could not create a temporary image file.', array( 'status' => 500 ) );
		}
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 60,
				'redirection'         => 0,
				'stream'              => true,
				'filename'            => $temp,
				'limit_response_size' => 10 * MB_IN_BYTES + 1,
			)
		);
		if ( is_wp_error( $response ) ) {
			wp_delete_file( $temp );
			return $response;
		}
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			wp_delete_file( $temp );
			return new WP_Error( 'ikon_seo_media_response', 'The approved image URL did not return a direct successful response. Redirects are not followed.', array( 'status' => 400 ) );
		}

		$size = filesize( $temp );
		if ( false === $size || $size > 10 * MB_IN_BYTES ) {
			wp_delete_file( $temp );
			return new WP_Error( 'ikon_seo_media_size', 'The image exceeds the 10 MB import limit.', array( 'status' => 413 ) );
		}

		$requested = sanitize_file_name( $payload['filename'] ?? basename( $path ) );
		$basename  = sanitize_file_name( pathinfo( $requested, PATHINFO_FILENAME ) );
		$filename  = ( $basename ?: 'ikon-seo-image' ) . '.' . ( 'jpeg' === $extension ? 'jpg' : $extension );

		$file_array = array(
			'name'     => $filename,
			'tmp_name' => $temp,
		);
		$attachment_id = media_handle_sideload(
			$file_array,
			absint( $payload['parent_id'] ?? 0 ),
			sanitize_text_field( $payload['title'] ?? pathinfo( $filename, PATHINFO_FILENAME ) ),
			array(
				'post_excerpt' => sanitize_textarea_field( $payload['caption'] ?? '' ),
			)
		);

		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $temp );
			return $attachment_id;
		}

		if ( array_key_exists( 'alt_text', $payload ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $payload['alt_text'] ) );
		}

		return $this->summary( get_post( $attachment_id ) );
	}

	public function valid_image_id( $attachment_id ) {
		$attachment = get_post( absint( $attachment_id ) );
		return $attachment
			&& 'attachment' === $attachment->post_type
			&& 0 === strpos( (string) get_post_mime_type( $attachment ), 'image/' );
	}

	public function summary( $attachment ) {
		if ( is_numeric( $attachment ) ) {
			$attachment = get_post( absint( $attachment ) );
		}
		if ( ! $attachment instanceof WP_Post ) {
			return array();
		}
		$metadata = wp_get_attachment_metadata( $attachment->ID );
		return array(
			'id'        => (int) $attachment->ID,
			'title'     => $attachment->post_title,
			'filename'  => basename( get_attached_file( $attachment->ID ) ),
			'url'       => wp_get_attachment_url( $attachment->ID ),
			'alt_text'  => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
			'caption'   => $attachment->post_excerpt,
			'mime_type' => get_post_mime_type( $attachment->ID ),
			'width'     => absint( $metadata['width'] ?? 0 ),
			'height'    => absint( $metadata['height'] ?? 0 ),
		);
	}

	private function allowed_host( $host ) {
		if ( ! $host || 'localhost' === $host || filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return false;
		}
		$settings = Ikon_SEO_Plugin::settings();
		$allowed  = preg_split( '/[\s,]+/', strtolower( (string) $settings['allowed_media_hosts'] ) );
		$allowed[]= strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$allowed  = array_filter( array_unique( array_map( function( $item ) {
			return preg_replace( '/^www\./', '', trim( $item ) );
		}, $allowed ) ) );

		return in_array( preg_replace( '/^www\./', '', $host ), $allowed, true );
	}
}
