<?php

defined( 'ABSPATH' ) || exit;

class Ikon_SEO_Logger {
	public function log( $action, $status, $message = '', $post_id = null, $source_id = null, $payload = array(), $request_id = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ikon_seo_logs';
		if ( ! $this->table_exists( $table ) ) {
			return $request_id ? sanitize_text_field( $request_id ) : wp_generate_uuid4();
		}

		$request_id = $request_id ? sanitize_text_field( $request_id ) : wp_generate_uuid4();
		$hash_input = empty( $payload ) ? '' : wp_json_encode( $this->redact( $payload ) );

		$wpdb->insert(
			$table,
			array(
				'request_id'  => $request_id,
				'action'      => sanitize_key( $action ),
				'status'      => sanitize_key( $status ),
				'post_id'     => $post_id ? absint( $post_id ) : null,
				'source_id'   => $source_id ? absint( $source_id ) : null,
				'message'     => sanitize_textarea_field( $message ),
				'payload_hash'=> $hash_input ? hash( 'sha256', $hash_input ) : null,
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
		);

		return $request_id;
	}

	public function recent( $limit = 50 ) {
		global $wpdb;

		$limit = max( 1, min( 200, absint( $limit ) ) );
		$table = $wpdb->prefix . 'ikon_seo_logs';
		if ( ! $this->table_exists( $table ) ) {
			return array();
		}

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ),
			ARRAY_A
		);
	}

	private function table_exists( $table ) {
		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		return $found === $table;
	}

	private function redact( $payload ) {
		foreach ( $payload as $key => $value ) {
			if ( in_array( strtolower( (string) $key ), array( 'token', 'api_key', 'authorization', 'connection_key', 'client_secret', 'refresh_token', 'access_token', 'claim_token' ), true ) ) {
				$payload[ $key ] = '[redacted]';
			} elseif ( is_array( $value ) ) {
				$payload[ $key ] = $this->redact( $value );
			}
		}

		return $payload;
	}
}
