<?php

defined( 'ABSPATH' ) || exit;

/**
 * Stores durable project context in WordPress so private workspaces can resume
 * across new conversations, browsers, devices, or account changes.
 */
class Ikon_SEO_Workspace_History {
	private $profile;

	public function __construct( Ikon_SEO_Profile $profile ) {
		$this->profile = $profile;
	}

	public function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_workspace_history';
	}

	public function add( array $data, $source = 'workspace', $created_by = 0 ) {
		global $wpdb;

		$profile    = $this->profile->get();
		$profile_id = sanitize_text_field( $profile['profile_id'] ?? '' );
		$title      = sanitize_text_field( $data['title'] ?? '' );
		$summary    = sanitize_textarea_field( $data['summary'] ?? '' );

		if ( ! $title || ! $summary ) {
			return new WP_Error( 'ikon_seo_history_required', __( 'A history title and summary are required.', 'ikon-seo' ) );
		}

		$category = sanitize_key( $data['category'] ?? 'note' );
		$allowed_categories = array( 'audit', 'research', 'recommendation', 'strategy', 'page_plan', 'draft', 'change', 'approval', 'workflow', 'task', 'briefing', 'note', 'system' );
		if ( ! in_array( $category, $allowed_categories, true ) ) {
			$category = 'note';
		}

		$status = sanitize_key( $data['status'] ?? 'open' );
		if ( ! in_array( $status, array( 'open', 'completed', 'dismissed' ), true ) ) {
			$status = 'open';
		}

		$details = $data['details'] ?? array();
		if ( is_string( $details ) ) {
			$decoded = json_decode( $details, true );
			$details = is_array( $decoded ) ? $decoded : array( 'notes' => sanitize_textarea_field( $details ) );
		}
		if ( ! is_array( $details ) ) {
			$details = array();
		}

		$now = current_time( 'mysql', true );
		$inserted = $wpdb->insert(
			$this->table_name(),
			array(
				'profile_id'      => $profile_id,
				'category'        => $category,
				'status'          => $status,
				'title'           => substr( $title, 0, 255 ),
				'summary'         => substr( $summary, 0, 10000 ),
				'details'         => wp_json_encode( $this->sanitize_details( $details ) ),
				'source'          => sanitize_key( $source ?: 'workspace' ),
				'related_post_id' => absint( $data['related_post_id'] ?? 0 ),
				'created_by'      => absint( $created_by ),
				'created_at'      => $now,
				'updated_at'      => $now,
				'completed_at'    => 'completed' === $status ? $now : null,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'ikon_seo_history_insert', __( 'The project history item could not be saved.', 'ikon-seo' ) );
		}

		return $this->get( (int) $wpdb->insert_id );
	}

	public function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->table_name() . ' WHERE id = %d', absint( $id ) ), ARRAY_A );
		return $row ? $this->prepare_item( $row ) : null;
	}

	public function update_status( $id, $status ) {
		global $wpdb;
		$status = sanitize_key( $status );
		if ( ! in_array( $status, array( 'open', 'completed', 'dismissed' ), true ) ) {
			return new WP_Error( 'ikon_seo_history_status', __( 'The requested project-history status is invalid.', 'ikon-seo' ) );
		}

		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			$this->table_name(),
			array(
				'status'       => $status,
				'updated_at'   => $now,
				'completed_at' => 'completed' === $status ? $now : null,
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'ikon_seo_history_update', __( 'The project-history status could not be updated.', 'ikon-seo' ) );
		}

		return $this->get( $id );
	}

	public function items( array $args = array() ) {
		global $wpdb;

		$profile    = $this->profile->get();
		$profile_id = sanitize_text_field( $profile['profile_id'] ?? '' );
		$limit      = max( 1, min( 200, absint( $args['limit'] ?? 50 ) ) );
		$status     = sanitize_key( $args['status'] ?? '' );
		$category   = sanitize_key( $args['category'] ?? '' );

		$where  = array( 'profile_id = %s' );
		$params = array( $profile_id );
		if ( in_array( $status, array( 'open', 'completed', 'dismissed' ), true ) ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}
		if ( $category ) {
			$where[]  = 'category = %s';
			$params[] = $category;
		}

		$params[] = $limit;
		$sql = 'SELECT * FROM ' . $this->table_name() . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY updated_at DESC, id DESC LIMIT %d';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return array_map( array( $this, 'prepare_item' ), $rows ?: array() );
	}

	public function state( $limit = 50 ) {
		global $wpdb;

		$profile    = $this->profile->get();
		$profile_id = sanitize_text_field( $profile['profile_id'] ?? '' );
		$counts     = array( 'open' => 0, 'completed' => 0, 'dismissed' => 0, 'total' => 0 );
		$rows       = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT status, COUNT(*) AS total FROM ' . $this->table_name() . ' WHERE profile_id = %s GROUP BY status',
				$profile_id
			),
			ARRAY_A
		);
		foreach ( $rows ?: array() as $row ) {
			$key = sanitize_key( $row['status'] );
			if ( isset( $counts[ $key ] ) ) {
				$counts[ $key ] = absint( $row['total'] );
			}
		}
		$counts['total'] = $counts['open'] + $counts['completed'] + $counts['dismissed'];

		$open_items = $this->items( array( 'status' => 'open', 'limit' => 20 ) );
		$recent     = $this->items( array( 'limit' => $limit ) );
		$last_completed = null;
		foreach ( $recent as $item ) {
			if ( 'completed' === $item['status'] ) {
				$last_completed = $item;
				break;
			}
		}

		return array(
			'ok'                 => true,
			'profile_id'         => $profile_id,
			'site_url'           => home_url( '/' ),
			'counts'             => $counts,
			'last_completed_step'=> $last_completed,
			'next_open_item'     => $open_items ? $open_items[0] : null,
			'pending_items'      => $open_items,
			'recent_history'     => $recent,
			'continuity_note'    => __( 'Use this WordPress project history as the source of truth when starting a new workspace conversation or reconnecting from another account.', 'ikon-seo' ),
			'generated_at'       => current_time( 'mysql', true ),
		);
	}

	public function sync( array $payload, $created_by = 0 ) {
		$result = array();

		if ( ! empty( $payload['event'] ) && is_array( $payload['event'] ) ) {
			$result['saved_event'] = $this->add( $payload['event'], 'workspace', $created_by );
			if ( is_wp_error( $result['saved_event'] ) ) {
				return $result['saved_event'];
			}
		}

		if ( ! empty( $payload['status_update'] ) && is_array( $payload['status_update'] ) ) {
			$result['updated_event'] = $this->update_status(
				absint( $payload['status_update']['id'] ?? 0 ),
				sanitize_key( $payload['status_update']['status'] ?? '' )
			);
			if ( is_wp_error( $result['updated_event'] ) ) {
				return $result['updated_event'];
			}
		}

		$result['state'] = $this->state( absint( $payload['limit'] ?? 50 ) );
		return $result;
	}

	private function prepare_item( array $row ) {
		$details = json_decode( (string) ( $row['details'] ?? '' ), true );
		return array(
			'id'              => absint( $row['id'] ?? 0 ),
			'profile_id'      => sanitize_text_field( $row['profile_id'] ?? '' ),
			'category'        => sanitize_key( $row['category'] ?? 'note' ),
			'status'          => sanitize_key( $row['status'] ?? 'open' ),
			'title'           => sanitize_text_field( $row['title'] ?? '' ),
			'summary'         => sanitize_textarea_field( $row['summary'] ?? '' ),
			'details'         => is_array( $details ) ? $details : array(),
			'source'          => sanitize_key( $row['source'] ?? 'workspace' ),
			'related_post_id' => absint( $row['related_post_id'] ?? 0 ),
			'created_by'      => absint( $row['created_by'] ?? 0 ),
			'created_at'      => sanitize_text_field( $row['created_at'] ?? '' ),
			'updated_at'      => sanitize_text_field( $row['updated_at'] ?? '' ),
			'completed_at'    => sanitize_text_field( $row['completed_at'] ?? '' ),
		);
	}

	private function sanitize_details( array $details ) {
		$clean   = array();
		$is_list = ! $details || array_keys( $details ) === range( 0, count( $details ) - 1 );

		foreach ( $details as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = $this->sanitize_details( $value );
			} elseif ( is_bool( $value ) || is_numeric( $value ) ) {
				$value = $value;
			} else {
				$value = sanitize_textarea_field( (string) $value );
			}

			if ( $is_list ) {
				$clean[] = $value;
			} else {
				$clean[ sanitize_key( $key ) ] = $value;
			}
		}

		return $clean;
	}
}
