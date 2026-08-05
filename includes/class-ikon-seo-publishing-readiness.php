<?php

defined( 'ABSPATH' ) || exit;

/**
 * Controlled publishing readiness and post-launch verification.
 *
 * This component never publishes, schedules, merges, redirects, deletes, or
 * changes canonical/indexing settings. It creates an immutable release package,
 * runs bounded checks, records a separate readiness approval, detects a manual
 * WordPress publication, and verifies the resulting public URL.
 */
final class Ikon_SEO_Publishing_Readiness {
	const CACHE_KEY = 'ikon_seo_publishing_readiness_report_v1';
	const CRON_HOOK = 'ikon_seo_publishing_verification';
	const META_RELEASE_ID = '_ikon_seo_publishing_release_id';
	const META_READINESS = '_ikon_seo_publishing_readiness';

	private $editorial_review;
	private $content_workbench;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Editorial_Review $editorial_review,
		Ikon_SEO_Content_Workbench $content_workbench,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->editorial_review = $editorial_review;
		$this->content_workbench = $content_workbench;
		$this->history = $history;
		$this->logger = $logger;
	}

	public function register_hooks() {
		add_action( 'transition_post_status', array( $this, 'detect_manual_publication' ), 20, 3 );
		add_action( self::CRON_HOOK, array( $this, 'run_due_verifications' ) );
	}

	public function releases_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_publishing_releases';
	}

	public function checks_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_publishing_checks';
	}

	public function snapshots_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_publishing_snapshots';
	}

	public function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_publishing_events';
	}

	public function status() {
		global $wpdb;
		$ready = $this->tables_ready();
		$counts = array();
		if ( $ready ) {
			foreach ( array( 'candidate', 'preflight_failed', 'preflight_passed', 'ready_for_manual_publish', 'publication_detected', 'monitoring', 'issues_found', 'verified', 'completed', 'blocked', 'cancelled' ) as $status ) {
				$counts[ $status ] = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->releases_table()} WHERE status=%s", $status ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}
		return array(
			'database_ready' => $ready,
			'counts' => $counts,
			'publishes_automatically' => false,
			'merges_automatically' => false,
			'changes_indexing_automatically' => false,
			'monitoring_windows' => array( 'launch', '24_hours', '7_days', '28_days' ),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'create_release':
				return $this->create_release( absint( $payload['review_id'] ?? 0 ), (array) ( $payload['plan'] ?? array() ), $user_id );
			case 'run_preflight':
				return $this->run_preflight( absint( $payload['release_id'] ?? 0 ), $user_id );
			case 'mark_ready':
				return $this->mark_ready( absint( $payload['release_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'record_manual_publication':
				return $this->record_manual_publication( absint( $payload['release_id'] ?? 0 ), absint( $payload['live_post_id'] ?? 0 ), esc_url_raw( $payload['live_url'] ?? '' ), $user_id );
			case 'verify_launch':
				return $this->verify_launch( absint( $payload['release_id'] ?? 0 ), $user_id, ! empty( $payload['refresh'] ) );
			case 'complete_monitoring':
				return $this->complete_monitoring( absint( $payload['release_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'block':
				return $this->block( absint( $payload['release_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'unblock':
				return $this->unblock( absint( $payload['release_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'compare':
				return $this->compare_snapshots( absint( $payload['release_id'] ?? 0 ), absint( $payload['from_snapshot_id'] ?? 0 ), absint( $payload['to_snapshot_id'] ?? 0 ) );
			case 'read':
			default:
				return $this->report( array( 'limit' => absint( $payload['limit'] ?? 100 ), 'status' => sanitize_key( $payload['status'] ?? '' ) ), false );
		}
	}

	public function report( array $args = array(), $refresh = false ) {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'limit' => 100, 'status' => '' ) );
		$limit = max( 10, min( 250, absint( $args['limit'] ) ) );
		$status_filter = sanitize_key( $args['status'] );
		$cache_version = max( 1, absint( get_option( 'ikon_seo_publishing_cache_version', 1 ) ) );
		$cache_key = self::CACHE_KEY . '_' . $cache_version . '_' . md5( wp_json_encode( array( $limit, $status_filter ) ) );
		if ( $refresh ) {
			delete_transient( $cache_key );
		} else {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$status = $this->status();
		if ( empty( $status['database_ready'] ) ) {
			return array(
				'status' => $status,
				'releases' => array(),
				'eligible_reviews' => array(),
				'limitations' => array( 'Update or reactivate Ikon SEO to create the v1.11.0 publishing readiness tables.' ),
			);
		}

		$where = '';
		$params = array();
		if ( $status_filter ) {
			$where = ' WHERE status=%s';
			$params[] = $status_filter;
		}
		$query = "SELECT * FROM {$this->releases_table()}{$where} ORDER BY FIELD(status,'blocked','preflight_failed','issues_found','publication_detected','monitoring','ready_for_manual_publish','preflight_passed','candidate','verified','completed','cancelled'), updated_at DESC LIMIT %d"; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$params[] = $limit;
		$rows = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$releases = array_map( array( $this, 'format_release' ), $rows ?: array() );

		$linked = array();
		foreach ( $releases as $release ) {
			$linked[ absint( $release['review_id'] ) ] = true;
		}
		$eligible = array();
		$editorial = $this->editorial_review->report( array( 'limit' => 250, 'status' => 'signed_off' ), false );
		foreach ( (array) ( $editorial['reviews'] ?? array() ) as $review ) {
			if ( empty( $linked[ absint( $review['id'] ) ] ) && empty( $review['draft_changed_after_snapshot'] ) ) {
				$eligible[] = $review;
			}
		}

		$summary = array(
			'active' => 0,
			'blocked' => 0,
			'ready_for_manual_publish' => 0,
			'awaiting_verification' => 0,
			'issues_found' => 0,
			'verified' => 0,
			'completed' => 0,
			'blockers' => 0,
			'warnings' => 0,
		);
		foreach ( $releases as $release ) {
			if ( ! in_array( $release['status'], array( 'completed', 'cancelled' ), true ) ) {
				$summary['active']++;
			}
			if ( isset( $summary[ $release['status'] ] ) ) {
				$summary[ $release['status'] ]++;
			}
			if ( in_array( $release['status'], array( 'publication_detected', 'monitoring' ), true ) ) {
				$summary['awaiting_verification']++;
			}
			$summary['blockers'] += absint( $release['blocker_count'] );
			$summary['warnings'] += absint( $release['warning_count'] );
		}

		$result = array(
			'generated_at' => current_time( 'mysql', true ),
			'status' => $status,
			'summary' => $summary,
			'releases' => $releases,
			'eligible_reviews' => $eligible,
			'workflow' => array( 'signed_off_editorial_review', 'release_candidate', 'preflight', 'readiness_approval', 'manual_wordpress_publish', 'launch_verification', '24h_7d_28d_monitoring' ),
			'safety' => array(
				'No command in this component publishes, schedules, merges, redirects, deletes, or changes indexing settings.',
				'Readiness approval is invalidated when the signed-off draft changes.',
				'Manual publication must be detected or explicitly recorded before public verification can begin.',
				'Post-launch checks are bounded, read-only HTTP observations and produce recommendations rather than automatic fixes.',
			),
		);
		set_transient( $cache_key, $result, 3 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function create_release( $review_id, array $plan = array(), $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_publishing_manage', __( 'Only an administrator or publishing manager can create a release candidate.', 'ikon-seo' ) );
		}
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_publishing_tables', __( 'Publishing Readiness database tables are not ready.', 'ikon-seo' ) );
		}
		$review = $this->editorial_review->review( $review_id );
		if ( ! $review || 'signed_off' !== sanitize_key( $review['status'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_publishing_signoff', __( 'Final editorial sign-off is required before creating a release candidate.', 'ikon-seo' ) );
		}
		if ( ! empty( $review['draft_changed_after_snapshot'] ) ) {
			return new WP_Error( 'ikon_seo_publishing_changed_after_signoff', __( 'The controlled draft changed after its signed-off snapshot. Reopen editorial review first.', 'ikon-seo' ) );
		}
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->releases_table()} WHERE review_id=%d LIMIT 1", $review_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $existing ) {
			return new WP_Error( 'ikon_seo_publishing_exists', __( 'A publishing release already exists for this editorial review.', 'ikon-seo' ) );
		}
		$post = get_post( absint( $review['draft_post_id'] ) );
		if ( ! $post || ! in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) {
			return new WP_Error( 'ikon_seo_publishing_draft', __( 'The signed-off controlled item must remain a draft or pending review before a release candidate is created.', 'ikon-seo' ) );
		}
		$brief = $this->content_workbench->brief( absint( $review['brief_id'] ) );
		$source_post_id = absint( $brief['post_id'] ?? 0 );
		$mode = $source_post_id ? 'existing_page_revision' : 'new_page';
		$planned_slug = sanitize_title( $plan['slug'] ?? '' );
		$slug = $planned_slug ?: sanitize_title( $post->post_name ?: $post->post_title );
		$planned_url = esc_url_raw( $plan['target_url'] ?? '' );
		$target_url = $planned_url ?: esc_url_raw( $source_post_id ? get_permalink( $source_post_id ) : home_url( '/' . $slug . '/' ) );
		if ( ! $this->is_same_site_url( $target_url ) ) {
			return new WP_Error( 'ikon_seo_publishing_target_url', __( 'The publishing target must be a valid URL on this WordPress website.', 'ikon-seo' ) );
		}
		$release_hash = $this->current_post_hash( $post->ID );
		if ( ! $release_hash || ! hash_equals( (string) $review['last_snapshot_hash'], $release_hash ) ) {
			return new WP_Error( 'ikon_seo_publishing_snapshot_mismatch', __( 'The current draft no longer matches the final editorial snapshot.', 'ikon-seo' ) );
		}
		$now = current_time( 'mysql', true );
		$inserted = $wpdb->insert(
			$this->releases_table(),
			array(
				'review_id' => absint( $review_id ),
				'brief_id' => absint( $review['brief_id'] ),
				'draft_post_id' => absint( $review['draft_post_id'] ),
				'source_post_id' => $source_post_id,
				'live_post_id' => 0,
				'publication_mode' => $mode,
				'status' => 'candidate',
				'target_url' => $target_url,
				'proposed_slug' => $slug,
				'release_hash' => $release_hash,
				'signoff_snapshot_hash' => sanitize_text_field( $review['last_snapshot_hash'] ),
				'preflight_score' => 0,
				'verification_score' => 0,
				'blocker_count' => 0,
				'warning_count' => 0,
				'readiness_approved_by' => 0,
				'readiness_approved_at' => null,
				'manual_publish_by' => 0,
				'published_at' => null,
				'launch_snapshot_id' => 0,
				'last_verified_at' => null,
				'next_check_at' => null,
				'monitoring_until' => null,
				'blocked_reason' => '',
				'created_by' => absint( $user_id ),
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
		if ( false === $inserted || ! $wpdb->insert_id ) {
			return new WP_Error( 'ikon_seo_publishing_store', __( 'The release candidate could not be stored.', 'ikon-seo' ) );
		}
		$release_id = absint( $wpdb->insert_id );
		update_post_meta( $post->ID, self::META_RELEASE_ID, $release_id );
		$this->snapshot( $release_id, 'release_candidate', $post->ID, $target_url, array( 'source' => 'signed_off_draft' ), $user_id );
		$this->event( $release_id, 'release_created', 'Controlled release candidate created. Publishing remains separate.', array( 'mode' => $mode, 'target_url' => $target_url ), $user_id );
		$this->record_history( 'approval', 'Publishing release candidate created', sprintf( 'Release #%d was created from signed-off draft #%d. It remains unpublished.', $release_id, $post->ID ), $release_id, $user_id, $post->ID );
		$this->clear_cache();
		return $this->get_release( $release_id );
	}

	public function run_preflight( $release_id, $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_publishing_manage', __( 'Only an administrator or publishing manager can run release preflight.', 'ikon-seo' ) );
		}
		$release = $this->get_release( $release_id, true );
		if ( ! $release || in_array( $release['status'], array( 'completed', 'cancelled' ), true ) ) {
			return new WP_Error( 'ikon_seo_publishing_preflight_state', __( 'This release cannot run preflight.', 'ikon-seo' ) );
		}
		$gate = $this->assert_release_current( $release );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		$post = get_post( absint( $release['draft_post_id'] ) );
		$checks = $this->preflight_checks( $release, $post );
		$this->store_checks( $release_id, 'preflight', $checks, $user_id );
		$counts = $this->count_check_results( $checks );
		$score = $this->score_checks( $checks );
		$status = $counts['blockers'] ? 'preflight_failed' : 'preflight_passed';
		$wpdb->update(
			$this->releases_table(),
			array(
				'status' => $status,
				'preflight_score' => $score,
				'blocker_count' => $counts['blockers'],
				'warning_count' => $counts['warnings'],
				'readiness_approved_by' => 0,
				'readiness_approved_at' => null,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $release_id )
		);
		$this->snapshot( $release_id, 'preflight', $post->ID, $release['target_url'], array( 'score' => $score, 'checks' => $checks ), $user_id );
		$this->event( $release_id, 'preflight_completed', sprintf( 'Preflight completed with score %d, %d blockers and %d warnings.', $score, $counts['blockers'], $counts['warnings'] ), array( 'score' => $score, 'counts' => $counts ), $user_id );
		$this->clear_cache();
		return $this->get_release( $release_id );
	}

	public function mark_ready( $release_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_publishing_manage', __( 'Only an administrator or publishing manager can approve publishing readiness.', 'ikon-seo' ) );
		}
		$release = $this->get_release( $release_id, true );
		if ( ! $release || 'preflight_passed' !== $release['status'] ) {
			return new WP_Error( 'ikon_seo_publishing_ready_state', __( 'A successful current preflight is required before readiness approval.', 'ikon-seo' ) );
		}
		$gate = $this->assert_release_current( $release );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		if ( absint( $release['blocker_count'] ) ) {
			return new WP_Error( 'ikon_seo_publishing_blockers', __( 'Resolve all publishing blockers before readiness approval.', 'ikon-seo' ) );
		}
		$post = get_post( absint( $release['draft_post_id'] ) );
		if ( ! $post || ! in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) {
			return new WP_Error( 'ikon_seo_publishing_ready_post_status', __( 'The controlled item must remain a draft or pending review until the separate manual publishing decision.', 'ikon-seo' ) );
		}
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->releases_table(), array( 'status' => 'ready_for_manual_publish', 'readiness_approved_by' => absint( $user_id ), 'readiness_approved_at' => $now, 'updated_at' => $now ), array( 'id' => $release_id ) );
		update_post_meta(
			absint( $release['draft_post_id'] ),
			self::META_READINESS,
			array(
				'release_id' => $release_id,
				'release_hash' => sanitize_text_field( $release['release_hash'] ),
				'approved_by' => absint( $user_id ),
				'approved_at' => $now,
				'notes' => sanitize_textarea_field( $notes ),
				'publishes_automatically' => false,
				'requires_manual_wordpress_action' => true,
			)
		);
		$this->event( $release_id, 'readiness_approved', $notes ?: 'Release approved as ready for a separate manual WordPress publishing decision.', array( 'automatic_publish' => false ), $user_id );
		$this->record_history( 'approval', 'Publishing readiness approved', sprintf( 'Release #%d passed preflight and is ready for a separate manual publishing decision.', $release_id ), $release_id, $user_id, absint( $release['draft_post_id'] ) );
		$this->clear_cache();
		return $this->get_release( $release_id );
	}

	public function record_manual_publication( $release_id, $live_post_id = 0, $live_url = '', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_publishing_manage', __( 'Only an administrator or publishing manager can record a publication.', 'ikon-seo' ) );
		}
		$release = $this->get_release( $release_id, true );
		if ( ! $release || ! in_array( $release['status'], array( 'ready_for_manual_publish', 'publication_detected', 'monitoring', 'issues_found', 'verified' ), true ) ) {
			return new WP_Error( 'ikon_seo_publishing_record_state', __( 'Readiness approval is required before recording manual publication.', 'ikon-seo' ) );
		}
		if ( 'ready_for_manual_publish' === $release['status'] ) {
			$gate = $this->assert_release_current( $release );
			if ( is_wp_error( $gate ) ) {
				return $gate;
			}
		}
		$live_post_id = $live_post_id ?: ( 'new_page' === $release['publication_mode'] ? absint( $release['draft_post_id'] ) : absint( $release['source_post_id'] ) );
		$live_post = $live_post_id ? get_post( $live_post_id ) : null;
		if ( ! $live_post || 'publish' !== $live_post->post_status ) {
			return new WP_Error( 'ikon_seo_publishing_not_public', __( 'The selected WordPress post is not published. Publish or merge it manually first.', 'ikon-seo' ) );
		}
		$live_url = esc_url_raw( $live_url ?: get_permalink( $live_post_id ) );
		if ( ! $this->is_same_site_url( $live_url ) ) {
			return new WP_Error( 'ikon_seo_publishing_live_url', __( 'A valid public URL on this WordPress website is required.', 'ikon-seo' ) );
		}
		$published_at = $live_post->post_date_gmt && '0000-00-00 00:00:00' !== $live_post->post_date_gmt ? $live_post->post_date_gmt : current_time( 'mysql', true );
		$monitoring_until = gmdate( 'Y-m-d H:i:s', strtotime( $published_at . ' UTC' ) + 28 * DAY_IN_SECONDS );
		$now = current_time( 'mysql', true );
		$wpdb->update(
			$this->releases_table(),
			array(
				'status' => 'publication_detected',
				'live_post_id' => $live_post_id,
				'target_url' => $live_url,
				'manual_publish_by' => absint( $user_id ),
				'published_at' => $published_at,
				'next_check_at' => $now,
				'monitoring_until' => $monitoring_until,
				'updated_at' => $now,
			),
			array( 'id' => $release_id )
		);
		$snapshot = $this->snapshot( $release_id, 'launch_record', $live_post_id, $live_url, array( 'detected_post_status' => 'publish' ), $user_id );
		if ( is_array( $snapshot ) && ! empty( $snapshot['id'] ) ) {
			$wpdb->update( $this->releases_table(), array( 'launch_snapshot_id' => absint( $snapshot['id'] ) ), array( 'id' => $release_id ) );
		}
		$this->event( $release_id, 'manual_publication_recorded', 'Manual WordPress publication was detected or recorded. Public verification is now available.', array( 'live_post_id' => $live_post_id, 'live_url' => $live_url ), $user_id );
		$this->record_history( 'measurement', 'Manual publication recorded', sprintf( 'Release #%d is now linked to published post #%d for read-only launch verification.', $release_id, $live_post_id ), $release_id, $user_id, $live_post_id );
		$this->clear_cache();
		return $this->get_release( $release_id );
	}

	public function verify_launch( $release_id, $user_id = 0, $refresh = false ) {
		global $wpdb;
		$release = $this->get_release( $release_id, true );
		if ( ! $release || ! in_array( $release['status'], array( 'publication_detected', 'monitoring', 'issues_found', 'verified' ), true ) ) {
			return new WP_Error( 'ikon_seo_publishing_verify_state', __( 'Record the manual publication before running public verification.', 'ikon-seo' ) );
		}
		$url = esc_url_raw( $release['target_url'] );
		if ( ! $this->is_same_site_url( $url ) ) {
			return new WP_Error( 'ikon_seo_publishing_verify_url', __( 'The release does not have a valid same-site public URL.', 'ikon-seo' ) );
		}
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 10,
				'redirection' => 3,
				'limit_response_size' => 1024 * 1024,
				'user-agent' => 'Ikon-SEO/' . ( defined( 'IKON_SEO_VERSION' ) ? IKON_SEO_VERSION : '1.11.0' ) . '; ' . home_url( '/' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->store_checks( $release_id, 'post_launch', array( $this->check( 'http_request', 'Public page can be fetched', 'blocker', 'failed', 'HTTP 200', $response->get_error_message(), 'The public URL could not be checked.' ) ), $user_id );
			$wpdb->update( $this->releases_table(), array( 'status' => 'issues_found', 'blocker_count' => 1, 'warning_count' => 0, 'last_verified_at' => current_time( 'mysql', true ), 'next_check_at' => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $release_id ) );
			$this->clear_cache();
			return $this->get_release( $release_id );
		}
		$code = absint( wp_remote_retrieve_response_code( $response ) );
		$headers = wp_remote_retrieve_headers( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		if ( strlen( $body ) > 1024 * 1024 ) {
			$body = substr( $body, 0, 1024 * 1024 );
		}
		$checks = $this->public_checks( $release, $code, $headers, $body );
		$this->store_checks( $release_id, 'post_launch', $checks, $user_id );
		$counts = $this->count_check_results( $checks );
		$score = $this->score_checks( $checks );
		$status = $counts['blockers'] ? 'issues_found' : 'verified';
		$now = current_time( 'mysql', true );
		$next = $this->next_monitoring_check( $release_id, $release['published_at'] ?: $now );
		if ( $next && strtotime( $next . ' UTC' ) <= strtotime( (string) $release['monitoring_until'] . ' UTC' ) ) {
			$status = $counts['blockers'] ? 'issues_found' : 'monitoring';
		} else {
			$next = null;
		}
		$wpdb->update(
			$this->releases_table(),
			array(
				'status' => $status,
				'verification_score' => $score,
				'blocker_count' => $counts['blockers'],
				'warning_count' => $counts['warnings'],
				'last_verified_at' => $now,
				'next_check_at' => $next,
				'updated_at' => $now,
			),
			array( 'id' => $release_id )
		);
		$this->snapshot( $release_id, 'post_launch', absint( $release['live_post_id'] ), $url, array( 'status_code' => $code, 'headers' => $this->headers_array( $headers ), 'body_hash' => hash( 'sha256', $body ), 'checks' => $checks ), $user_id );
		$this->event( $release_id, 'launch_verified', sprintf( 'Public verification completed with score %d, %d blockers and %d warnings.', $score, $counts['blockers'], $counts['warnings'] ), array( 'status_code' => $code, 'score' => $score, 'counts' => $counts, 'next_check_at' => $next ), $user_id );
		$this->clear_cache();
		return $this->get_release( $release_id );
	}

	public function complete_monitoring( $release_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_publishing_manage', __( 'Only an administrator or publishing manager can complete release monitoring.', 'ikon-seo' ) );
		}
		$release = $this->get_release( $release_id, true );
		if ( ! $release || ! in_array( $release['status'], array( 'verified', 'monitoring' ), true ) ) {
			return new WP_Error( 'ikon_seo_publishing_complete_state', __( 'A release must pass public verification before monitoring can be completed.', 'ikon-seo' ) );
		}
		if ( absint( $release['blocker_count'] ) ) {
			return new WP_Error( 'ikon_seo_publishing_complete_blockers', __( 'Resolve launch blockers before completing monitoring.', 'ikon-seo' ) );
		}
		$verification_count = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->snapshots_table()} WHERE release_id=%d AND snapshot_type='post_launch'", $release_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$window_elapsed = ! empty( $release['monitoring_until'] ) && time() >= strtotime( $release['monitoring_until'] . ' UTC' );
		if ( $verification_count < 4 && ! $window_elapsed ) {
			return new WP_Error( 'ikon_seo_publishing_monitoring_window', __( 'Complete the launch, 24-hour, 7-day and 28-day verification checkpoints before closing monitoring.', 'ikon-seo' ) );
		}
		$wpdb->update( $this->releases_table(), array( 'status' => 'completed', 'next_check_at' => null, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $release_id ) );
		$this->event( $release_id, 'monitoring_completed', $notes ?: 'Post-launch monitoring completed after human review.', array(), $user_id );
		$this->record_history( 'measurement', 'Post-launch monitoring completed', sprintf( 'Release #%d completed its controlled publishing verification workflow.', $release_id ), $release_id, $user_id, absint( $release['live_post_id'] ) );
		$this->clear_cache();
		return $this->get_release( $release_id );
	}

	public function block( $release_id, $reason = '', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_publishing_manage', __( 'Only an administrator or publishing manager can block a release.', 'ikon-seo' ) );
		}
		$release = $this->get_release( $release_id, true );
		if ( ! $release || in_array( $release['status'], array( 'completed', 'cancelled' ), true ) ) {
			return new WP_Error( 'ikon_seo_publishing_block_state', __( 'This release cannot be blocked.', 'ikon-seo' ) );
		}
		if ( '' === trim( $reason ) ) {
			return new WP_Error( 'ikon_seo_publishing_block_reason', __( 'Explain what is blocking the release.', 'ikon-seo' ) );
		}
		$wpdb->update( $this->releases_table(), array( 'status' => 'blocked', 'blocked_reason' => sanitize_textarea_field( $reason ), 'next_check_at' => null, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $release_id ) );
		$this->event( $release_id, 'release_blocked', $reason, array( 'previous_status' => $release['status'] ), $user_id );
		$this->clear_cache();
		return $this->get_release( $release_id );
	}

	public function unblock( $release_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_publishing_manage', __( 'Only an administrator or publishing manager can reopen a release.', 'ikon-seo' ) );
		}
		$release = $this->get_release( $release_id, true );
		if ( ! $release || 'blocked' !== $release['status'] ) {
			return new WP_Error( 'ikon_seo_publishing_unblock_state', __( 'Only a blocked release can be reopened.', 'ikon-seo' ) );
		}
		$next = $release['published_at'] ? 'publication_detected' : 'candidate';
		$wpdb->update( $this->releases_table(), array( 'status' => $next, 'blocked_reason' => '', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $release_id ) );
		$this->event( $release_id, 'release_unblocked', $notes ?: 'Publishing release reopened.', array( 'next_status' => $next ), $user_id );
		$this->clear_cache();
		return $this->get_release( $release_id );
	}

	public function compare_snapshots( $release_id, $from_snapshot_id = 0, $to_snapshot_id = 0 ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->snapshots_table()} WHERE release_id=%d ORDER BY id ASC", $release_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( count( $rows ?: array() ) < 2 ) {
			return array( 'release_id' => $release_id, 'available' => false, 'message' => 'At least two release snapshots are required.' );
		}
		$by_id = array();
		foreach ( $rows as $row ) {
			$by_id[ absint( $row['id'] ) ] = $row;
		}
		$from = $from_snapshot_id && isset( $by_id[ $from_snapshot_id ] ) ? $by_id[ $from_snapshot_id ] : $rows[ count( $rows ) - 2 ];
		$to = $to_snapshot_id && isset( $by_id[ $to_snapshot_id ] ) ? $by_id[ $to_snapshot_id ] : $rows[ count( $rows ) - 1 ];
		return array(
			'release_id' => $release_id,
			'available' => true,
			'from' => $this->format_snapshot( $from ),
			'to' => $this->format_snapshot( $to ),
			'summary' => array(
				'post_changed' => (string) $from['content_hash'] !== (string) $to['content_hash'],
				'metadata_changed' => (string) $from['meta_hash'] !== (string) $to['meta_hash'],
				'url_changed' => untrailingslashit( (string) $from['url'] ) !== untrailingslashit( (string) $to['url'] ),
				'status_code_before' => absint( $from['status_code'] ),
				'status_code_after' => absint( $to['status_code'] ),
			),
		);
	}

	public function detect_manual_publication( $new_status, $old_status, $post ) {
		global $wpdb;
		if ( 'publish' !== $new_status || 'publish' === $old_status || ! $post || empty( $post->ID ) || ! $this->tables_ready() ) {
			return;
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->releases_table()} WHERE draft_post_id=%d AND status='ready_for_manual_publish' LIMIT 1", absint( $post->ID ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $row ) {
			$this->record_manual_publication( absint( $row['id'] ), absint( $post->ID ), get_permalink( $post->ID ), get_current_user_id() );
		}
	}

	public function run_due_verifications() {
		global $wpdb;
		if ( ! $this->tables_ready() ) {
			return;
		}
		$now = current_time( 'mysql', true );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$this->releases_table()} WHERE status IN ('publication_detected','monitoring','issues_found','verified') AND next_check_at IS NOT NULL AND next_check_at<=%s ORDER BY next_check_at ASC LIMIT 3", $now ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows ?: array() as $row ) {
			$this->verify_launch( absint( $row['id'] ), 0, true );
		}
	}

	public function get_release( $id, $raw = false ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->releases_table()} WHERE id=%d LIMIT 1", absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) {
			return array();
		}
		return $raw ? $row : $this->format_release( $row );
	}

	private function format_release( $row ) {
		$release_id = absint( $row['id'] );
		$checks = $this->checks( $release_id );
		$snapshots = $this->snapshots( $release_id );
		$events = $this->events( $release_id );
		$review = $this->editorial_review->review( absint( $row['review_id'] ) );
		$current_hash = $this->current_post_hash( absint( $row['draft_post_id'] ) );
		return array(
			'id' => $release_id,
			'review_id' => absint( $row['review_id'] ),
			'brief_id' => absint( $row['brief_id'] ),
			'draft_post_id' => absint( $row['draft_post_id'] ),
			'source_post_id' => absint( $row['source_post_id'] ),
			'live_post_id' => absint( $row['live_post_id'] ),
			'publication_mode' => sanitize_key( $row['publication_mode'] ),
			'status' => sanitize_key( $row['status'] ),
			'target_url' => esc_url_raw( $row['target_url'] ),
			'proposed_slug' => sanitize_title( $row['proposed_slug'] ),
			'release_hash' => sanitize_text_field( $row['release_hash'] ),
			'signoff_snapshot_hash' => sanitize_text_field( $row['signoff_snapshot_hash'] ),
			'draft_changed_after_release' => $current_hash && $row['release_hash'] ? ! hash_equals( (string) $row['release_hash'], $current_hash ) : false,
			'preflight_score' => absint( $row['preflight_score'] ),
			'verification_score' => absint( $row['verification_score'] ?? 0 ),
			'blocker_count' => absint( $row['blocker_count'] ),
			'warning_count' => absint( $row['warning_count'] ),
			'readiness_approved_by' => absint( $row['readiness_approved_by'] ),
			'readiness_approved_at' => $row['readiness_approved_at'],
			'manual_publish_by' => absint( $row['manual_publish_by'] ),
			'published_at' => $row['published_at'],
			'launch_snapshot_id' => absint( $row['launch_snapshot_id'] ),
			'last_verified_at' => $row['last_verified_at'],
			'next_check_at' => $row['next_check_at'],
			'monitoring_until' => $row['monitoring_until'],
			'blocked_reason' => sanitize_textarea_field( $row['blocked_reason'] ),
			'checks' => $checks,
			'snapshots' => $snapshots,
			'events' => $events,
			'editorial_review' => $review,
			'draft_edit_url' => get_edit_post_link( absint( $row['draft_post_id'] ), 'raw' ),
			'draft_preview_url' => get_preview_post_link( absint( $row['draft_post_id'] ) ),
			'live_edit_url' => absint( $row['live_post_id'] ) ? get_edit_post_link( absint( $row['live_post_id'] ), 'raw' ) : '',
			'created_at' => $row['created_at'],
			'updated_at' => $row['updated_at'],
		);
	}

	private function assert_release_current( array $release ) {
		$review = $this->editorial_review->review( absint( $release['review_id'] ) );
		if ( ! $review || 'signed_off' !== sanitize_key( $review['status'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_publishing_signoff_stale', __( 'The related editorial review is no longer signed off.', 'ikon-seo' ) );
		}
		if ( ! hash_equals( (string) $release['signoff_snapshot_hash'], (string) $review['last_snapshot_hash'] ) ) {
			return new WP_Error( 'ikon_seo_publishing_signoff_changed', __( 'The final editorial snapshot changed. Create a new release candidate after review.', 'ikon-seo' ) );
		}
		$current = $this->current_post_hash( absint( $release['draft_post_id'] ) );
		if ( ! $current || ! hash_equals( (string) $release['release_hash'], $current ) ) {
			return new WP_Error( 'ikon_seo_publishing_release_changed', __( 'The controlled draft changed after the release candidate was created. Run editorial review again.', 'ikon-seo' ) );
		}
		return true;
	}

	private function preflight_checks( array $release, $post ) {
		$content = (string) $post->post_content;
		$plain = trim( wp_strip_all_tags( $content ) );
		$word_count = str_word_count( $plain );
		$slug = sanitize_title( $post->post_name ?: $release['proposed_slug'] );
		$seo_title = $this->first_meta( $post->ID, array( 'rank_math_title', '_yoast_wpseo_title' ) );
		$description = $this->first_meta( $post->ID, array( 'rank_math_description', '_yoast_wpseo_metadesc' ) );
		$robots = strtolower( (string) $this->first_meta( $post->ID, array( 'rank_math_robots', '_yoast_wpseo_meta-robots-noindex' ) ) );
		$canonical = $this->first_meta( $post->ID, array( 'rank_math_canonical', '_yoast_wpseo_canonical' ) );
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$canonical_host = strtolower( (string) wp_parse_url( $canonical, PHP_URL_HOST ) );
		$has_placeholder = (bool) preg_match( '/\b(lorem ipsum|todo|tbd|drafting note|insert (?:text|image|link)|replace this)\b/i', $content );
		$has_internal = (bool) preg_match( '#href=["\']https?://' . preg_quote( $host, '#' ) . '#i', $content );
		$has_conversion = (bool) preg_match( '#(tel:|mailto:|wa\.me/|api\.whatsapp\.com|<form\b|book|request (?:a )?quote|contact us|schedule)#i', $content );
		$featured = function_exists( 'has_post_thumbnail' ) ? has_post_thumbnail( $post->ID ) : (bool) get_post_meta( $post->ID, '_thumbnail_id', true );
		$checks = array(
			$this->check( 'editorial_snapshot', 'Draft matches final editorial snapshot', 'blocker', 'passed', $release['release_hash'], $this->current_post_hash( $post->ID ), 'Any change after sign-off invalidates readiness.' ),
			$this->check( 'unpublished_state', 'Controlled item remains unpublished during preflight', 'blocker', in_array( $post->post_status, array( 'draft', 'pending' ), true ) ? 'passed' : 'failed', 'draft or pending', $post->post_status, 'Scheduled, private and published states require a separate human decision and cannot pass release preflight.' ),
			$this->check( 'title', 'Page title is present', 'blocker', trim( $post->post_title ) ? 'passed' : 'failed', 'Non-empty title', $post->post_title, '' ),
			$this->check( 'slug', 'Proposed slug is valid', 'blocker', $slug ? 'passed' : 'failed', 'Readable non-empty slug', $slug, '' ),
			$this->check( 'content_depth', 'Draft contains substantial finished content', 'warning', $word_count >= 250 ? 'passed' : 'warning', 'At least 250 words or an intentional short-page exception', (string) $word_count . ' words', '' ),
			$this->check( 'placeholders', 'Drafting placeholders are removed', 'blocker', $has_placeholder ? 'failed' : 'passed', 'No drafting placeholders', $has_placeholder ? 'Placeholder-like text detected' : 'None detected', '' ),
			$this->check( 'seo_title', 'SEO title is configured', 'warning', $seo_title ? 'passed' : 'warning', 'SEO title', $seo_title ?: 'Not explicitly configured', '' ),
			$this->check( 'meta_description', 'Meta description is configured', 'warning', $description ? 'passed' : 'warning', 'Meta description', $description ?: 'Not explicitly configured', '' ),
			$this->check( 'robots', 'No noindex directive is prepared for the public page', 'blocker', false !== strpos( $robots, 'noindex' ) || '1' === trim( $robots ) ? 'failed' : 'passed', 'Indexable after manual publication', $robots ?: 'No explicit noindex value', '' ),
			$this->check( 'canonical', 'Canonical target is same-site', 'blocker', $canonical && $canonical_host && $canonical_host !== $host ? 'failed' : 'passed', 'Same-site canonical or automatic self-canonical', $canonical ?: 'Automatic', '' ),
			$this->check( 'internal_links', 'Relevant internal links are included', 'warning', $has_internal ? 'passed' : 'warning', 'At least one same-site contextual link where appropriate', $has_internal ? 'Detected' : 'Not detected', '' ),
			$this->check( 'conversion', 'Conversion action is present where the strategy requires one', 'warning', $has_conversion ? 'passed' : 'warning', 'Relevant call, form, booking, email or contact action', $has_conversion ? 'Detected' : 'Not detected', '' ),
			$this->check( 'featured_media', 'Featured media is selected where appropriate', 'warning', $featured ? 'passed' : 'warning', 'Featured image or intentional exception', $featured ? 'Configured' : 'Not configured', '' ),
		);
		if ( absint( $release['source_post_id'] ) ) {
			$source = get_post( absint( $release['source_post_id'] ) );
			$checks[] = $this->check( 'source_page', 'Original live page still exists for the separate revision workflow', 'blocker', $source && 'publish' === $source->post_status ? 'passed' : 'failed', 'Published source page', $source ? $source->post_status : 'Missing', 'Ikon SEO does not merge the controlled draft automatically.' );
		}
		return $checks;
	}

	private function public_checks( array $release, $code, $headers, $body ) {
		$title = $this->extract_tag( $body, 'title' );
		$description = $this->extract_meta( $body, 'description' );
		$canonical = $this->extract_link( $body, 'canonical' );
		$robots_meta = strtolower( $this->extract_meta( $body, 'robots' ) );
		$x_robots = strtolower( (string) $this->header_value( $headers, 'x-robots-tag' ) );
		$h1_count = preg_match_all( '/<h1\b/i', $body, $matches );
		$target = untrailingslashit( esc_url_raw( $release['target_url'] ) );
		$canonical_ok = ! $canonical || untrailingslashit( esc_url_raw( $canonical ) ) === $target;
		$noindex = false !== strpos( $robots_meta, 'noindex' ) || false !== strpos( $x_robots, 'noindex' );
		$has_schema = false !== stripos( $body, 'application/ld+json' );
		$has_tracking = (bool) preg_match( '/(googletagmanager\.com|gtag\s*\(|dataLayer\s*=|google-analytics\.com|matomo|plausible\.io)/i', $body );
		$has_conversion = (bool) preg_match( '#(tel:|mailto:|wa\.me/|api\.whatsapp\.com|<form\b|book|request (?:a )?quote|contact us|schedule)#i', $body );
		return array(
			$this->check( 'http_status', 'Public URL returns a successful response', 'blocker', 200 === $code ? 'passed' : 'failed', 'HTTP 200', 'HTTP ' . $code, '' ),
			$this->check( 'indexability', 'Public page is not marked noindex', 'blocker', $noindex ? 'failed' : 'passed', 'No noindex directive', trim( $robots_meta . ' ' . $x_robots ) ?: 'No noindex detected', '' ),
			$this->check( 'canonical', 'Rendered canonical matches the launch URL', 'blocker', $canonical_ok ? 'passed' : 'failed', $target, $canonical ?: 'No explicit canonical detected', '' ),
			$this->check( 'rendered_title', 'Rendered title is present', 'blocker', $title ? 'passed' : 'failed', 'Non-empty title', $title ?: 'Missing', '' ),
			$this->check( 'rendered_description', 'Rendered meta description is present', 'warning', $description ? 'passed' : 'warning', 'Non-empty description', $description ?: 'Missing', '' ),
			$this->check( 'single_h1', 'Rendered page has one primary H1', 'warning', 1 === $h1_count ? 'passed' : 'warning', 'Exactly one H1', (string) $h1_count, '' ),
			$this->check( 'structured_data', 'Structured data is rendered', 'warning', $has_schema ? 'passed' : 'warning', 'Relevant JSON-LD where appropriate', $has_schema ? 'Detected' : 'Not detected', '' ),
			$this->check( 'measurement', 'Analytics or measurement code is present', 'warning', $has_tracking ? 'passed' : 'warning', 'Approved measurement implementation', $has_tracking ? 'Detected' : 'Not detected', '' ),
			$this->check( 'conversion', 'Primary conversion action remains available', 'warning', $has_conversion ? 'passed' : 'warning', 'Relevant public conversion action', $has_conversion ? 'Detected' : 'Not detected', '' ),
		);
	}

	private function check( $key, $label, $severity, $status, $expected, $observed, $details ) {
		return array(
			'key' => sanitize_key( $key ),
			'label' => sanitize_text_field( $label ),
			'severity' => in_array( $severity, array( 'blocker', 'warning', 'info' ), true ) ? $severity : 'warning',
			'status' => in_array( $status, array( 'passed', 'warning', 'failed', 'pending', 'not_applicable' ), true ) ? $status : 'pending',
			'expected' => sanitize_textarea_field( $expected ),
			'observed' => sanitize_textarea_field( $observed ),
			'details' => sanitize_textarea_field( $details ),
		);
	}

	private function store_checks( $release_id, $phase, array $checks, $user_id ) {
		global $wpdb;
		foreach ( $checks as $check ) {
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->checks_table()} WHERE release_id=%d AND phase=%s AND check_key=%s LIMIT 1", $release_id, $phase, $check['key'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$data = array(
				'release_id' => absint( $release_id ),
				'phase' => sanitize_key( $phase ),
				'check_key' => sanitize_key( $check['key'] ),
				'label' => sanitize_text_field( $check['label'] ),
				'severity' => sanitize_key( $check['severity'] ),
				'status' => sanitize_key( $check['status'] ),
				'expected_value' => sanitize_textarea_field( $check['expected'] ),
				'observed_value' => sanitize_textarea_field( $check['observed'] ),
				'details' => sanitize_textarea_field( $check['details'] ),
				'checked_by' => absint( $user_id ),
				'checked_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			);
			if ( $existing ) {
				$wpdb->update( $this->checks_table(), $data, array( 'id' => absint( $existing ) ) );
			} else {
				$data['created_at'] = current_time( 'mysql', true );
				$wpdb->insert( $this->checks_table(), $data );
			}
		}
	}

	private function checks( $release_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->checks_table()} WHERE release_id=%d ORDER BY FIELD(phase,'preflight','post_launch'), FIELD(severity,'blocker','warning','info'), id ASC", $release_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map(
			function( $row ) {
				return array(
					'id' => absint( $row['id'] ),
					'phase' => sanitize_key( $row['phase'] ),
					'key' => sanitize_key( $row['check_key'] ),
					'label' => sanitize_text_field( $row['label'] ),
					'severity' => sanitize_key( $row['severity'] ),
					'status' => sanitize_key( $row['status'] ),
					'expected' => sanitize_textarea_field( $row['expected_value'] ),
					'observed' => sanitize_textarea_field( $row['observed_value'] ),
					'details' => sanitize_textarea_field( $row['details'] ),
					'checked_by' => absint( $row['checked_by'] ),
					'checked_at' => $row['checked_at'],
				);
			},
			$rows ?: array()
		);
	}

	private function snapshot( $release_id, $type, $post_id, $url, array $payload, $user_id ) {
		global $wpdb;
		$post = $post_id ? get_post( $post_id ) : null;
		$title = $post ? (string) $post->post_title : '';
		$content = $post ? (string) $post->post_content : '';
		$meta = $post_id ? $this->release_meta( $post_id ) : array();
		$status_code = absint( $payload['status_code'] ?? 0 );
		$now = current_time( 'mysql', true );
		$wpdb->insert(
			$this->snapshots_table(),
			array(
				'release_id' => absint( $release_id ),
				'snapshot_type' => sanitize_key( $type ),
				'post_id' => absint( $post_id ),
				'url' => esc_url_raw( $url ),
				'status_code' => $status_code,
				'title' => sanitize_text_field( $title ),
				'canonical' => esc_url_raw( $meta['canonical'] ?? '' ),
				'robots' => sanitize_text_field( $meta['robots'] ?? '' ),
				'content_hash' => ! empty( $payload['body_hash'] ) ? sanitize_text_field( $payload['body_hash'] ) : ( $post ? $this->current_post_hash( $post_id ) : '' ),
				'meta_hash' => hash( 'sha256', wp_json_encode( $meta ) ),
				'payload_json' => wp_json_encode( $payload ),
				'created_by' => absint( $user_id ),
				'created_at' => $now,
			)
		);
		return $wpdb->insert_id ? $this->get_snapshot( absint( $wpdb->insert_id ) ) : array();
	}

	private function snapshots( $release_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->snapshots_table()} WHERE release_id=%d ORDER BY id ASC", $release_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( array( $this, 'format_snapshot' ), $rows ?: array() );
	}

	private function get_snapshot( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->snapshots_table()} WHERE id=%d LIMIT 1", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? $this->format_snapshot( $row ) : array();
	}

	private function format_snapshot( $row ) {
		return array(
			'id' => absint( $row['id'] ),
			'release_id' => absint( $row['release_id'] ),
			'type' => sanitize_key( $row['snapshot_type'] ),
			'post_id' => absint( $row['post_id'] ),
			'url' => esc_url_raw( $row['url'] ),
			'status_code' => absint( $row['status_code'] ),
			'title' => sanitize_text_field( $row['title'] ),
			'canonical' => esc_url_raw( $row['canonical'] ),
			'robots' => sanitize_text_field( $row['robots'] ),
			'content_hash' => sanitize_text_field( $row['content_hash'] ),
			'meta_hash' => sanitize_text_field( $row['meta_hash'] ),
			'payload' => $this->decode_json( $row['payload_json'] ),
			'created_by' => absint( $row['created_by'] ),
			'created_at' => $row['created_at'],
		);
	}

	private function events( $release_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->events_table()} WHERE release_id=%d ORDER BY id DESC LIMIT 100", $release_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map(
			function( $row ) {
				return array(
					'id' => absint( $row['id'] ),
					'type' => sanitize_key( $row['event_type'] ),
					'actor_id' => absint( $row['actor_id'] ),
					'notes' => sanitize_textarea_field( $row['notes'] ),
					'payload' => $this->decode_json( $row['payload_json'] ),
					'created_at' => $row['created_at'],
				);
			},
			$rows ?: array()
		);
	}

	private function event( $release_id, $type, $notes, array $payload, $user_id ) {
		global $wpdb;
		$wpdb->insert(
			$this->events_table(),
			array(
				'release_id' => absint( $release_id ),
				'event_type' => sanitize_key( $type ),
				'actor_id' => absint( $user_id ),
				'notes' => sanitize_textarea_field( $notes ),
				'payload_json' => wp_json_encode( $payload ),
				'created_at' => current_time( 'mysql', true ),
			)
		);
	}

	private function count_check_results( array $checks ) {
		$result = array( 'blockers' => 0, 'warnings' => 0, 'passed' => 0 );
		foreach ( $checks as $check ) {
			if ( 'failed' === $check['status'] && 'blocker' === $check['severity'] ) {
				$result['blockers']++;
			} elseif ( in_array( $check['status'], array( 'warning', 'failed' ), true ) ) {
				$result['warnings']++;
			} elseif ( 'passed' === $check['status'] ) {
				$result['passed']++;
			}
		}
		return $result;
	}

	private function score_checks( array $checks ) {
		if ( ! $checks ) {
			return 0;
		}
		$total = 0;
		$earned = 0;
		foreach ( $checks as $check ) {
			$weight = 'blocker' === $check['severity'] ? 3 : ( 'warning' === $check['severity'] ? 1 : 0.5 );
			$total += $weight;
			if ( 'passed' === $check['status'] || 'not_applicable' === $check['status'] ) {
				$earned += $weight;
			} elseif ( 'warning' === $check['status'] ) {
				$earned += 0.5 * $weight;
			}
		}
		return $total ? max( 0, min( 100, (int) round( 100 * $earned / $total ) ) ) : 0;
	}

	private function next_monitoring_check( $release_id, $published_at ) {
		global $wpdb;
		$count = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->snapshots_table()} WHERE release_id=%d AND snapshot_type='post_launch'", $release_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$base = strtotime( $published_at . ' UTC' );
		$offsets = array( DAY_IN_SECONDS, 7 * DAY_IN_SECONDS, 28 * DAY_IN_SECONDS );
		if ( $count >= count( $offsets ) ) {
			return null;
		}
		return gmdate( 'Y-m-d H:i:s', $base + $offsets[ $count ] );
	}

	private function current_post_hash( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		$builder = get_post_meta( $post_id, '_elementor_data', true );
		if ( is_array( $builder ) || is_object( $builder ) ) {
			$builder = wp_json_encode( $builder );
		}
		$builder = (string) $builder;
		if ( strlen( $builder ) > 524288 ) {
			$builder = substr( $builder, 0, 524288 );
		}
		return hash( 'sha256', $this->normalize( $post->post_title ) . '|' . $this->normalize( $post->post_excerpt ) . '|' . $this->normalize( $post->post_content ) . '|' . $this->normalize( $builder ) );
	}

	private function release_meta( $post_id ) {
		return array(
			'seo_title' => $this->first_meta( $post_id, array( 'rank_math_title', '_yoast_wpseo_title' ) ),
			'description' => $this->first_meta( $post_id, array( 'rank_math_description', '_yoast_wpseo_metadesc' ) ),
			'canonical' => $this->first_meta( $post_id, array( 'rank_math_canonical', '_yoast_wpseo_canonical' ) ),
			'robots' => $this->first_meta( $post_id, array( 'rank_math_robots', '_yoast_wpseo_meta-robots-noindex' ) ),
			'featured_image' => absint( get_post_meta( $post_id, '_thumbnail_id', true ) ),
		);
	}

	private function first_meta( $post_id, array $keys ) {
		foreach ( $keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( is_array( $value ) ) {
				$value = implode( ',', array_map( 'sanitize_text_field', $value ) );
			}
			if ( '' !== trim( (string) $value ) ) {
				return (string) $value;
			}
		}
		return '';
	}

	private function extract_tag( $html, $tag ) {
		return preg_match( '#<' . preg_quote( $tag, '#' ) . '\b[^>]*>(.*?)</' . preg_quote( $tag, '#' ) . '>#is', $html, $match ) ? trim( wp_strip_all_tags( html_entity_decode( $match[1], ENT_QUOTES, 'UTF-8' ) ) ) : '';
	}

	private function extract_meta( $html, $name ) {
		$patterns = array(
			'#<meta\b[^>]*(?:name|property)=["\']' . preg_quote( $name, '#' ) . '["\'][^>]*content=["\']([^"\']*)["\'][^>]*>#is',
			'#<meta\b[^>]*content=["\']([^"\']*)["\'][^>]*(?:name|property)=["\']' . preg_quote( $name, '#' ) . '["\'][^>]*>#is',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $html, $match ) ) {
				return trim( html_entity_decode( $match[1], ENT_QUOTES, 'UTF-8' ) );
			}
		}
		return '';
	}

	private function extract_link( $html, $rel ) {
		$patterns = array(
			'#<link\b[^>]*rel=["\']' . preg_quote( $rel, '#' ) . '["\'][^>]*href=["\']([^"\']*)["\'][^>]*>#is',
			'#<link\b[^>]*href=["\']([^"\']*)["\'][^>]*rel=["\']' . preg_quote( $rel, '#' ) . '["\'][^>]*>#is',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $html, $match ) ) {
				return esc_url_raw( $match[1] );
			}
		}
		return '';
	}

	private function header_value( $headers, $key ) {
		if ( is_object( $headers ) && method_exists( $headers, 'offsetGet' ) ) {
			return $headers->offsetGet( $key );
		}
		if ( is_array( $headers ) ) {
			foreach ( $headers as $name => $value ) {
				if ( strtolower( (string) $name ) === strtolower( $key ) ) {
					return is_array( $value ) ? implode( ', ', $value ) : $value;
				}
			}
		}
		return '';
	}

	private function headers_array( $headers ) {
		if ( is_object( $headers ) && method_exists( $headers, 'getAll' ) ) {
			return (array) $headers->getAll();
		}
		return is_array( $headers ) ? $headers : array();
	}

	private function normalize( $value ) {
		$value = function_exists( 'remove_accents' ) ? remove_accents( (string) $value ) : (string) $value;
		$value = strtolower( wp_strip_all_tags( $value ) );
		return trim( preg_replace( '/\s+/', ' ', preg_replace( '/[^a-z0-9\s]+/', ' ', $value ) ) );
	}

	private function decode_json( $value ) {
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function is_same_site_url( $url ) {
		if ( ! wp_http_validate_url( $url ) ) {
			return false;
		}
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$url_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		return $home_host && $url_host && $home_host === $url_host && in_array( $scheme, array( 'http', 'https' ), true );
	}

	private function can_manage( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return false;
		}
		return user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'publish_posts' ) || user_can( $user_id, 'publish_pages' );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	private function tables_ready() {
		return $this->table_exists( $this->releases_table() ) && $this->table_exists( $this->checks_table() ) && $this->table_exists( $this->snapshots_table() ) && $this->table_exists( $this->events_table() );
	}

	private function clear_cache() {
		$version = max( 1, absint( get_option( 'ikon_seo_publishing_cache_version', 1 ) ) );
		update_option( 'ikon_seo_publishing_cache_version', $version + 1, false );
	}

	private function record_history( $category, $title, $summary, $release_id, $user_id, $post_id = 0 ) {
		$this->history->add( array( 'category' => $category, 'status' => 'open', 'title' => $title, 'summary' => $summary, 'details' => array( 'publishing_release_id' => absint( $release_id ) ), 'related_post_id' => absint( $post_id ) ), 'publishing_readiness', $user_id );
		$this->logger->log( 'publishing_readiness', 'success', $summary, absint( $post_id ), absint( $release_id ) );
	}
}
