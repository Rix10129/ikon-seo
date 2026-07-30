<?php

defined( 'ABSPATH' ) || exit;

/**
 * Manages connection keys and short-lived, one-time pairing codes.
 */
class Ikon_SEO_Connection {
	const PAIR_TTL = 600;

	/**
	 * Start a simple pairing session for the current website.
	 *
	 * @param int $user_id WordPress administrator user ID.
	 * @return array
	 */
	public function start_pairing( $user_id ) {
		$user_id = absint( $user_id );
		$this->clear_user_pairing( $user_id );

		$token    = wp_generate_password( 64, false, false );
		$code_raw = $this->generate_code();
		$expires  = time() + self::PAIR_TTL;

		$settings                           = Ikon_SEO_Plugin::settings();
		$settings['token_hash']             = wp_hash_password( $token );
		$settings['token_hint']             = '••••••' . substr( $token, -6 );
		$settings['remote_actions']         = 1;
		$settings['connection_verified_at'] = '';
		$settings['connection_last_seen_at']= '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		delete_transient( 'ikon_seo_connection_seen_write' );

		$pairing = array(
			'code'       => $this->format_code( $code_raw ),
			'code_raw'   => $code_raw,
			'token'      => $token,
			'user_id'    => $user_id,
			'expires_at' => $expires,
			'site_url'   => home_url( '/' ),
			'schema_url' => rest_url( Ikon_SEO_REST::NAMESPACE . '/openapi' ),
		);

		set_transient( $this->pair_transient_key( $code_raw ), $pairing, self::PAIR_TTL );
		set_transient( $this->user_transient_key( $user_id ), $pairing, self::PAIR_TTL );

		return $this->public_pairing( $pairing );
	}

	/**
	 * Create a traditional developer key that is displayed once.
	 *
	 * @param int $user_id WordPress administrator user ID.
	 * @return string
	 */
	public function generate_developer_key( $user_id ) {
		$this->clear_user_pairing( $user_id );

		$token                                = wp_generate_password( 64, false, false );
		$settings                             = Ikon_SEO_Plugin::settings();
		$settings['token_hash']               = wp_hash_password( $token );
		$settings['token_hint']               = '••••••' . substr( $token, -6 );
		$settings['remote_actions']           = 1;
		$settings['connection_verified_at']   = '';
		$settings['connection_last_seen_at']  = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		delete_transient( 'ikon_seo_connection_seen_write' );

		return $token;
	}

	/**
	 * Exchange a valid one-time pairing code for the connection package.
	 *
	 * @param string $code Pairing code supplied by the approved workflow.
	 * @return array|WP_Error
	 */
	public function exchange( $code ) {
		$code_raw = $this->normalize_code( $code );
		if ( 8 !== strlen( $code_raw ) ) {
			return new WP_Error(
				'ikon_seo_pair_code_invalid',
				__( 'The pairing code is invalid or has expired.', 'ikon-seo' ),
				array( 'status' => 400 )
			);
		}

		$key     = $this->pair_transient_key( $code_raw );
		$pairing = get_transient( $key );
		if ( ! is_array( $pairing ) || empty( $pairing['token'] ) || empty( $pairing['expires_at'] ) || time() > absint( $pairing['expires_at'] ) ) {
			delete_transient( $key );
			return new WP_Error(
				'ikon_seo_pair_code_expired',
				__( 'The pairing code is invalid or has expired.', 'ikon-seo' ),
				array( 'status' => 410 )
			);
		}

		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['remote_actions'] ) || empty( $settings['token_hash'] ) || ! wp_check_password( $pairing['token'], $settings['token_hash'] ) ) {
			delete_transient( $key );
			return new WP_Error(
				'ikon_seo_pairing_replaced',
				__( 'This pairing session is no longer active. Start a new connection from WordPress.', 'ikon-seo' ),
				array( 'status' => 409 )
			);
		}

		delete_transient( $key );
		if ( ! empty( $pairing['user_id'] ) ) {
			delete_transient( $this->user_transient_key( absint( $pairing['user_id'] ) ) );
		}
		$this->mark_seen();

		return array(
			'ok'             => true,
			'site_url'       => home_url( '/' ),
			'schema_url'     => rest_url( Ikon_SEO_REST::NAMESPACE . '/openapi' ),
			'connection_key' => $pairing['token'],
			'header_name'    => 'X-Ikon-SEO-Key',
			'scopes'         => array_values( (array) $settings['key_scopes'] ),
			'draft_only'     => (bool) $settings['draft_only'],
			'message'        => __( 'Pairing completed. Read the health and profile endpoints before creating content.', 'ikon-seo' ),
		);
	}

	/**
	 * Return the active pairing session for an administrator, without the secret token.
	 *
	 * @param int $user_id WordPress administrator user ID.
	 * @return array|null
	 */
	public function current_pairing( $user_id ) {
		$pairing = get_transient( $this->user_transient_key( absint( $user_id ) ) );
		if ( ! is_array( $pairing ) || empty( $pairing['expires_at'] ) || time() > absint( $pairing['expires_at'] ) ) {
			$this->clear_user_pairing( $user_id );
			return null;
		}

		return $this->public_pairing( $pairing );
	}

	/**
	 * Revoke the current key and any pairing session for an administrator.
	 *
	 * @param int $user_id WordPress administrator user ID.
	 * @return void
	 */
	public function revoke( $user_id = 0 ) {
		$this->clear_user_pairing( $user_id );

		$settings                             = Ikon_SEO_Plugin::settings();
		$settings['token_hash']               = '';
		$settings['token_hint']               = '';
		$settings['connection_verified_at']   = '';
		$settings['connection_last_seen_at']  = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		delete_transient( 'ikon_seo_connection_seen_write' );
	}

	/**
	 * Mark a successful authenticated request so the UI can distinguish a key from a real connection.
	 *
	 * @return void
	 */
	public function mark_seen() {
		if ( false !== get_transient( 'ikon_seo_connection_seen_write' ) ) {
			return;
		}

		$settings = Ikon_SEO_Plugin::settings();
		$now      = current_time( 'mysql', true );
		if ( empty( $settings['connection_verified_at'] ) ) {
			$settings['connection_verified_at'] = $now;
		}
		$settings['connection_last_seen_at'] = $now;
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		set_transient( 'ikon_seo_connection_seen_write', 1, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Human-readable connection state.
	 *
	 * @param array|null $settings Plugin settings.
	 * @return string disconnected|ready|connected
	 */
	public function status( $settings = null ) {
		$settings = is_array( $settings ) ? $settings : Ikon_SEO_Plugin::settings();
		if ( empty( $settings['token_hash'] ) ) {
			return 'disconnected';
		}
		if ( ! empty( $settings['connection_verified_at'] ) ) {
			return 'connected';
		}
		return 'ready';
	}

	private function clear_user_pairing( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return;
		}
		$pairing = get_transient( $this->user_transient_key( $user_id ) );
		if ( is_array( $pairing ) && ! empty( $pairing['code_raw'] ) ) {
			delete_transient( $this->pair_transient_key( $pairing['code_raw'] ) );
		}
		delete_transient( $this->user_transient_key( $user_id ) );
	}

	private function public_pairing( array $pairing ) {
		return array(
			'code'       => (string) $pairing['code'],
			'expires_at' => absint( $pairing['expires_at'] ),
			'site_url'   => (string) $pairing['site_url'],
		);
	}

	private function generate_code() {
		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$code     = '';
		$max      = strlen( $alphabet ) - 1;
		for ( $i = 0; $i < 8; $i++ ) {
			$code .= $alphabet[ random_int( 0, $max ) ];
		}
		return $code;
	}

	private function format_code( $code ) {
		$code = $this->normalize_code( $code );
		return substr( $code, 0, 4 ) . '-' . substr( $code, 4, 4 );
	}

	private function normalize_code( $code ) {
		return preg_replace( '/[^A-Z0-9]/', '', strtoupper( sanitize_text_field( (string) $code ) ) );
	}

	private function pair_transient_key( $code ) {
		return 'ikon_seo_pair_' . substr( hash( 'sha256', $this->normalize_code( $code ) ), 0, 32 );
	}

	private function user_transient_key( $user_id ) {
		return 'ikon_seo_pairing_user_' . absint( $user_id );
	}
}
