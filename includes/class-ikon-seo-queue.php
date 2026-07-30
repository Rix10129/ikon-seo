<?php

defined( 'ABSPATH' ) || exit;

/**
 * Stores human-approved page plans for interactive connected generation.
 *
 * The queue does not call a model. A connected workflow claims a plan, creates
 * a full page payload and returns it to Ikon SEO for normal validation.
 */
class Ikon_SEO_Queue {
	const MAX_IMPORT_ROWS = 500;

	private $profile;

	public function __construct( Ikon_SEO_Profile $profile ) {
		$this->profile = $profile;
	}

	public function import_csv( $path, $filename = '' ) {
		if ( ! is_readable( $path ) ) {
			return new WP_Error( 'ikon_seo_queue_file', __( 'The CSV file could not be read.', 'ikon-seo' ) );
		}

		$handle = fopen( $path, 'r' );
		if ( false === $handle ) {
			return new WP_Error( 'ikon_seo_queue_open', __( 'The CSV file could not be opened.', 'ikon-seo' ) );
		}

		$headers = fgetcsv( $handle );
		if ( ! is_array( $headers ) ) {
			fclose( $handle );
			return new WP_Error( 'ikon_seo_queue_headers', __( 'The CSV file must contain a header row.', 'ikon-seo' ) );
		}

		$headers = array_map(
			function( $value ) {
				$value = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $value );
				return sanitize_key( strtolower( trim( $value ) ) );
			},
			$headers
		);
		$allowed = array( 'keyword', 'service', 'location', 'page_type', 'language', 'template_hint', 'desired_slug', 'source_page_id', 'priority' );
		if ( ! in_array( 'keyword', $headers, true ) ) {
			fclose( $handle );
			return new WP_Error( 'ikon_seo_queue_keyword', __( 'The CSV header must include keyword.', 'ikon-seo' ) );
		}
		foreach ( $headers as $header ) {
			if ( ! in_array( $header, $allowed, true ) ) {
				fclose( $handle );
				return new WP_Error( 'ikon_seo_queue_header', sprintf( __( 'Unsupported CSV column: %s', 'ikon-seo' ), $header ) );
			}
		}

		$profile  = $this->profile->get();
		$batch_id = 'batch-' . gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 6, false, false );
		$rows     = array();
		$skipped  = 0;

		while ( ( $values = fgetcsv( $handle ) ) !== false ) {
			if ( count( $rows ) >= self::MAX_IMPORT_ROWS ) {
				break;
			}
			$values = array_pad( $values, count( $headers ), '' );
			$raw    = array_combine( $headers, array_slice( $values, 0, count( $headers ) ) );
			$item   = $this->sanitize_item( $raw, $profile );
			if ( is_wp_error( $item ) ) {
				$skipped++;
				continue;
			}
			$rows[] = $item;
		}
		fclose( $handle );

		if ( ! $rows ) {
			return new WP_Error( 'ikon_seo_queue_empty', __( 'No valid page-plan rows were found in the CSV file.', 'ikon-seo' ) );
		}

		global $wpdb;
		$table    = $wpdb->prefix . 'ikon_seo_queue';
		$inserted = 0;
		foreach ( $rows as $item ) {
			if ( $this->active_duplicate( $item ) ) {
				$skipped++;
				continue;
			}
			$ok = $wpdb->insert(
				$table,
				array_merge(
					$item,
					array(
						'batch_id'   => $batch_id,
						'status'     => 'planned',
						'attempts'   => 0,
						'created_at' => current_time( 'mysql', true ),
						'updated_at' => current_time( 'mysql', true ),
					)
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
			);
			if ( false !== $ok ) {
				$inserted++;
			}
		}

		$source_page_id = absint( $raw['source_page_id'] ?? 0 );
		if ( $source_page_id && 'page' !== get_post_type( $source_page_id ) ) {
			return new WP_Error( 'ikon_seo_queue_row_source', 'source_page_id must reference an existing WordPress page.' );
		}

		return array(
			'batch_id' => $batch_id,
			'filename' => sanitize_file_name( $filename ),
			'inserted' => $inserted,
			'skipped'  => $skipped,
			'limit'    => self::MAX_IMPORT_ROWS,
		);
	}

	public function list_items( $status = '', $limit = 50, $batch_id = '' ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'ikon_seo_queue';
		$status = sanitize_key( $status );
		$limit  = max( 1, min( 200, absint( $limit ) ) );
		$where  = array( 'profile_id = %s' );
		$args   = array( $this->profile->fingerprint() );

		if ( 'planned' === $status ) {
			$where[] = "(status = 'planned' OR (status = 'claimed' AND claimed_at < %s))";
			$args[]  = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );
		} elseif ( in_array( $status, $this->statuses(), true ) ) {
			$where[] = 'status = %s';
			$args[]  = $status;
		}
		if ( $batch_id ) {
			$where[] = 'batch_id = %s';
			$args[]  = sanitize_text_field( $batch_id );
		}
		$args[] = $limit;

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY priority DESC, id ASC LIMIT %d';
		return array_map( array( $this, 'public_item' ), $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) );
	}

	public function counts() {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_queue';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT status, COUNT(*) AS total FROM {$table} WHERE profile_id = %s GROUP BY status",
				$this->profile->fingerprint()
			),
			ARRAY_A
		);
		$counts = array_fill_keys( $this->statuses(), 0 );
		foreach ( $rows as $row ) {
			if ( isset( $counts[ $row['status'] ] ) ) {
				$counts[ $row['status'] ] = absint( $row['total'] );
			}
		}
		$stale = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE profile_id = %s AND status = 'claimed' AND claimed_at < %s",
					$this->profile->fingerprint(),
					gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS )
				)
			)
		);
		$counts['claimed'] = max( 0, $counts['claimed'] - $stale );
		$counts['planned'] += $stale;
		return $counts;
	}

	public function get( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_queue';
		$item  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND profile_id = %s",
				absint( $id ),
				$this->profile->fingerprint()
			),
			ARRAY_A
		);
		return $item ?: null;
	}

	public function claim( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_queue';
		$item  = $this->get( $id );
		if ( ! $item ) {
			return new WP_Error( 'ikon_seo_queue_not_found', __( 'Page-plan item not found for this Website Profile.', 'ikon-seo' ), array( 'status' => 404 ) );
		}
		if ( 'planned' !== $item['status'] && ! $this->claim_is_stale( $item ) ) {
			return new WP_Error( 'ikon_seo_queue_claimed', __( 'This page plan is not available to claim.', 'ikon-seo' ), array( 'status' => 409 ) );
		}

		try {
			$token = bin2hex( random_bytes( 24 ) );
		} catch ( Exception $exception ) {
			return new WP_Error( 'ikon_seo_queue_token', __( 'A secure queue claim could not be generated.', 'ikon-seo' ) );
		}

		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				 SET status = 'claimed', claim_token_hash = %s, claimed_at = %s, attempts = attempts + 1, updated_at = %s
				 WHERE id = %d AND profile_id = %s AND (status = 'planned' OR (status = 'claimed' AND claimed_at < %s))",
				hash( 'sha256', $token ),
				current_time( 'mysql', true ),
				current_time( 'mysql', true ),
				absint( $id ),
				$this->profile->fingerprint(),
				gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS )
			)
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'ikon_seo_queue_race', __( 'Another workflow claimed this page plan first.', 'ikon-seo' ), array( 'status' => 409 ) );
		}

		$claimed                = $this->public_item( $this->get( $id ) );
		$claimed['claim_token'] = $token;
		$claimed['expires_in']  = HOUR_IN_SECONDS;
		return $claimed;
	}

	public function verify_claim( $id, $token ) {
		$item = $this->get( $id );
		if ( ! $item || 'claimed' !== $item['status'] || empty( $item['claim_token_hash'] ) || empty( $item['claimed_at'] ) ) {
			return new WP_Error( 'ikon_seo_queue_claim', __( 'Claim this page plan before completing it.', 'ikon-seo' ), array( 'status' => 409 ) );
		}
		if ( $this->claim_is_stale( $item ) || ! hash_equals( $item['claim_token_hash'], hash( 'sha256', (string) $token ) ) ) {
			return new WP_Error( 'ikon_seo_queue_claim_expired', __( 'The page-plan claim expired or the claim token is invalid.', 'ikon-seo' ), array( 'status' => 409 ) );
		}
		return $item;
	}

	public function complete( $id, $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_queue';
		$wpdb->update(
			$table,
			array(
				'status'           => 'completed',
				'post_id'          => absint( $post_id ),
				'claim_token_hash' => '',
				'claimed_at'       => null,
				'last_error'       => '',
				'updated_at'       => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() ),
			array( '%s', '%d', '%s', null, '%s', '%s' ),
			array( '%d', '%s' )
		);
		return $this->public_item( $this->get( $id ) );
	}

	public function fail( $id, $message ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ikon_seo_queue',
			array(
				'status'           => 'failed',
				'claim_token_hash' => '',
				'claimed_at'       => null,
				'last_error'       => sanitize_textarea_field( $message ),
				'updated_at'       => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() ),
			array( '%s', '%s', null, '%s', '%s' ),
			array( '%d', '%s' )
		);
	}

	public function admin_status( $id, $status ) {
		$status = sanitize_key( $status );
		if ( ! in_array( $status, array( 'planned', 'paused', 'archived' ), true ) ) {
			return new WP_Error( 'ikon_seo_queue_status', __( 'Unsupported page-plan status action.', 'ikon-seo' ) );
		}
		$item = $this->get( $id );
		if ( ! $item ) {
			return new WP_Error( 'ikon_seo_queue_not_found', __( 'Page-plan item not found.', 'ikon-seo' ) );
		}

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ikon_seo_queue',
			array(
				'status'           => $status,
				'claim_token_hash' => '',
				'claimed_at'       => null,
				'last_error'       => 'planned' === $status ? '' : $item['last_error'],
				'updated_at'       => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() ),
			array( '%s', '%s', null, '%s', '%s' ),
			array( '%d', '%s' )
		);
		return $this->public_item( $this->get( $id ) );
	}

	public function apply_plan_to_payload( array $item, array $payload ) {
		$payload['profile_id'] = $this->profile->fingerprint();
		$payload['language']   = $item['language'];
		if ( ! empty( $item['desired_slug'] ) ) {
			$payload['slug'] = $item['desired_slug'];
		}
		if ( ! empty( $item['source_page_id'] ) ) {
			$payload['mode']           = 'improve';
			$payload['source_page_id'] = absint( $item['source_page_id'] );
		}
		$payload['seo']                  = is_array( $payload['seo'] ?? null ) ? $payload['seo'] : array();
		$payload['seo']['focus_keyword'] = $item['keyword'];
		$payload['schema']               = is_array( $payload['schema'] ?? null ) ? $payload['schema'] : array();
		$payload['schema']['page_type']  = $item['page_type'];
		$payload['queue_context'] = array(
			'queue_id'      => absint( $item['id'] ),
			'batch_id'      => sanitize_text_field( $item['batch_id'] ),
			'keyword'       => sanitize_text_field( $item['keyword'] ),
			'service'       => sanitize_text_field( $item['service'] ),
			'location'      => sanitize_text_field( $item['location'] ),
			'page_type'     => sanitize_key( $item['page_type'] ),
			'template_hint' => sanitize_text_field( $item['template_hint'] ),
		);
		return $payload;
	}

	private function sanitize_item( array $raw, array $profile ) {
		$keyword = sanitize_text_field( $raw['keyword'] ?? '' );
		if ( ! $keyword ) {
			return new WP_Error( 'ikon_seo_queue_row_keyword', 'Missing keyword.' );
		}

		$page_type = sanitize_key( $raw['page_type'] ?? 'service' );
		if ( ! in_array( $page_type, array( 'service', 'location', 'article', 'collection', 'tool', 'profile', 'about', 'contact', 'howto' ), true ) ) {
			return new WP_Error( 'ikon_seo_queue_row_type', 'Unsupported page_type.' );
		}

		$language = sanitize_text_field( $raw['language'] ?? $profile['default_language'] );
		if ( ! in_array( $language, $profile['supported_languages'], true ) ) {
			return new WP_Error( 'ikon_seo_queue_row_language', 'Unsupported language.' );
		}

		return array(
			'profile_id'    => $profile['profile_id'],
			'keyword'       => $keyword,
			'service'       => sanitize_text_field( $raw['service'] ?? '' ),
			'location'      => sanitize_text_field( $raw['location'] ?? '' ),
			'page_type'     => $page_type,
			'language'      => $language,
			'template_hint' => sanitize_text_field( $raw['template_hint'] ?? '' ),
			'desired_slug'  => sanitize_title( $raw['desired_slug'] ?? '' ),
			'source_page_id'=> $source_page_id,
			'priority'      => max( 0, min( 100, absint( $raw['priority'] ?? 50 ) ) ),
		);
	}

	private function active_duplicate( array $item ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_queue';
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				 WHERE profile_id = %s AND keyword = %s AND location = %s
				 AND status IN ('planned','claimed','paused','completed') LIMIT 1",
				$item['profile_id'],
				$item['keyword'],
				$item['location']
			)
		);
	}

	private function claim_is_stale( array $item ) {
		$claimed = strtotime( (string) ( $item['claimed_at'] ?? '' ) . ' UTC' );
		return ! $claimed || $claimed < time() - HOUR_IN_SECONDS;
	}

	private function public_item( array $item ) {
		unset( $item['claim_token_hash'] );
		if ( 'claimed' === $item['status'] && $this->claim_is_stale( $item ) ) {
			$item['status']     = 'planned';
			$item['claimed_at'] = null;
		}
		$item['id']             = absint( $item['id'] );
		$item['source_page_id'] = absint( $item['source_page_id'] );
		$item['post_id']        = absint( $item['post_id'] );
		$item['priority']       = absint( $item['priority'] );
		$item['attempts']       = absint( $item['attempts'] );
		if ( $item['post_id'] ) {
			$item['edit_url'] = get_edit_post_link( $item['post_id'], 'raw' );
			$item['url']      = get_permalink( $item['post_id'] );
		}
		return $item;
	}

	private function statuses() {
		return array( 'planned', 'claimed', 'paused', 'completed', 'failed', 'archived' );
	}
}
