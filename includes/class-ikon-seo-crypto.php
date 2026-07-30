<?php

defined( 'ABSPATH' ) || exit;

/**
 * Encrypts site-local integration secrets at rest.
 *
 * The encryption key is derived from WordPress salts and the current site URL.
 * Exported profiles never contain values handled by this class.
 */
class Ikon_SEO_Crypto {
	const PREFIX = 'ikon-v1:';

	public function available() {
		return function_exists( 'openssl_encrypt' )
			&& function_exists( 'openssl_decrypt' )
			&& function_exists( 'random_bytes' );
	}

	public function encrypt( $plaintext ) {
		$plaintext = (string) $plaintext;
		if ( '' === $plaintext ) {
			return '';
		}
		if ( ! $this->available() ) {
			return new WP_Error(
				'ikon_seo_crypto_unavailable',
				__( 'OpenSSL is required to store integration credentials securely.', 'ikon-seo' )
			);
		}

		try {
			$iv = random_bytes( 12 );
		} catch ( Exception $exception ) {
			return new WP_Error( 'ikon_seo_crypto_random', __( 'A secure encryption nonce could not be generated.', 'ikon-seo' ) );
		}

		$tag        = '';
		$ciphertext = openssl_encrypt(
			$plaintext,
			'aes-256-gcm',
			$this->key(),
			OPENSSL_RAW_DATA,
			$iv,
			$tag,
			$this->aad()
		);

		if ( false === $ciphertext || 16 !== strlen( $tag ) ) {
			return new WP_Error( 'ikon_seo_crypto_encrypt', __( 'The integration credential could not be encrypted.', 'ikon-seo' ) );
		}

		return self::PREFIX . base64_encode( $iv . $tag . $ciphertext );
	}

	public function decrypt( $encoded ) {
		$encoded = (string) $encoded;
		if ( '' === $encoded ) {
			return '';
		}
		if ( 0 !== strpos( $encoded, self::PREFIX ) || ! $this->available() ) {
			return new WP_Error( 'ikon_seo_crypto_format', __( 'The stored integration credential cannot be read safely.', 'ikon-seo' ) );
		}

		$raw = base64_decode( substr( $encoded, strlen( self::PREFIX ) ), true );
		if ( false === $raw || strlen( $raw ) < 29 ) {
			return new WP_Error( 'ikon_seo_crypto_format', __( 'The stored integration credential is invalid.', 'ikon-seo' ) );
		}

		$iv         = substr( $raw, 0, 12 );
		$tag        = substr( $raw, 12, 16 );
		$ciphertext = substr( $raw, 28 );
		$plaintext  = openssl_decrypt(
			$ciphertext,
			'aes-256-gcm',
			$this->key(),
			OPENSSL_RAW_DATA,
			$iv,
			$tag,
			$this->aad()
		);

		if ( false === $plaintext ) {
			return new WP_Error( 'ikon_seo_crypto_decrypt', __( 'The stored integration credential could not be decrypted.', 'ikon-seo' ) );
		}

		return $plaintext;
	}

	private function key() {
		return hash( 'sha256', wp_salt( 'auth' ) . '|' . home_url( '/' ) . '|ikon-seo-integrations', true );
	}

	private function aad() {
		return 'ikon-seo|' . home_url( '/' );
	}
}
