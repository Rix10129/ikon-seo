<?php

defined( 'ABSPATH' ) || exit;

/**
 * Human editorial collaboration and revision governance for controlled drafts.
 *
 * This layer never publishes content. It stores assignments, review rounds,
 * evidence checklists, structured comments, immutable snapshots and final
 * sign-off records around Content Workbench drafts.
 */
final class Ikon_SEO_Editorial_Review {
	const CACHE_KEY = 'ikon_seo_editorial_review_report_v1';
	const META_REVIEW_ID = '_ikon_seo_editorial_review_id';
	const META_SIGNOFF = '_ikon_seo_editorial_signoff';

	private $content_workbench;
	private $publisher;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Content_Workbench $content_workbench,
		Ikon_SEO_Publisher_Intelligence $publisher,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->content_workbench = $content_workbench;
		$this->publisher = $publisher;
		$this->history = $history;
		$this->logger = $logger;
	}

	public function reviews_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_editorial_reviews';
	}

	public function comments_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_editorial_comments';
	}

	public function checks_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_editorial_checks';
	}

	public function snapshots_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_editorial_snapshots';
	}

	public function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_editorial_events';
	}

	public function review( $id ) {
		return $this->get_review( absint( $id ) );
	}

	public function status() {
		global $wpdb;
		$ready = $this->tables_ready();
		$counts = array();
		if ( $ready ) {
			foreach ( array( 'unassigned', 'assigned', 'writing', 'review_requested', 'changes_requested', 'approved', 'signed_off', 'blocked' ) as $status ) {
				$counts[ $status ] = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->reviews_table()} WHERE status=%s", $status ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}
		return array(
			'database_ready' => $ready,
			'counts' => $counts,
			'publishing_is_automatic' => false,
			'final_signoff_publishes' => false,
			'requires_separate_wordpress_publish_decision' => true,
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'start_review':
				return $this->start_review( absint( $payload['brief_id'] ?? 0 ), (array) ( $payload['assignment'] ?? array() ), $user_id );
			case 'assign':
				return $this->assign( absint( $payload['review_id'] ?? 0 ), (array) ( $payload['assignment'] ?? array() ), $user_id );
			case 'request_review':
				return $this->request_review( absint( $payload['review_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'add_comment':
				return $this->add_comment( absint( $payload['review_id'] ?? 0 ), (array) ( $payload['comment'] ?? array() ), $user_id );
			case 'resolve_comment':
				return $this->resolve_comment( absint( $payload['comment_id'] ?? 0 ), sanitize_key( $payload['resolution'] ?? 'resolved' ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'update_check':
				return $this->update_check( absint( $payload['check_id'] ?? 0 ), sanitize_key( $payload['check_status'] ?? 'pending' ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'request_changes':
				return $this->request_changes( absint( $payload['review_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'submit_revision':
				return $this->submit_revision( absint( $payload['review_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'approve_round':
				return $this->approve_round( absint( $payload['review_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'sign_off':
				return $this->sign_off( absint( $payload['review_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'block':
				return $this->block( absint( $payload['review_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'unblock':
				return $this->unblock( absint( $payload['review_id'] ?? 0 ), sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			case 'compare':
				return $this->compare_versions( absint( $payload['review_id'] ?? 0 ), absint( $payload['from_snapshot_id'] ?? 0 ), absint( $payload['to_snapshot_id'] ?? 0 ) );
			case 'read':
			default:
				return $this->report( array( 'limit' => absint( $payload['limit'] ?? 100 ) ), false );
		}
	}

	public function report( array $args = array(), $refresh = false ) {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'limit' => 100, 'status' => '', 'user_id' => 0 ) );
		$limit = max( 10, min( 250, absint( $args['limit'] ) ) );
		$status_filter = sanitize_key( $args['status'] );
		$user_filter = absint( $args['user_id'] );
		if ( $user_filter && $this->can_manage( $user_filter ) ) {
			$user_filter = 0;
		}
		$cache_key = self::CACHE_KEY . '_' . md5( wp_json_encode( array( $limit, $status_filter, $user_filter ) ) );
		if ( $user_filter ) {
			$refresh = true;
		}
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
				'reviews' => array(),
				'startable_briefs' => array(),
				'limitations' => array( 'Update or reactivate Ikon SEO to create the v1.10.0 editorial review tables.' ),
			);
		}

		$where_parts = array();
		$params = array();
		if ( $status_filter ) {
			$where_parts[] = 'status=%s';
			$params[] = $status_filter;
		}
		if ( $user_filter ) {
			$where_parts[] = '(writer_id=%d OR reviewer_id=%d)';
			$params[] = $user_filter;
			$params[] = $user_filter;
		}
		$where = $where_parts ? ' WHERE ' . implode( ' AND ', $where_parts ) : '';
		$query = "SELECT * FROM {$this->reviews_table()}{$where} ORDER BY FIELD(status,'blocked','review_requested','changes_requested','approved','writing','assigned','unassigned','signed_off'), COALESCE(review_due_at,due_at,'9999-12-31 00:00:00') ASC, updated_at DESC LIMIT %d"; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$params[] = $limit;
		$rows = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$reviews = array_map( array( $this, 'format_review' ), $rows ?: array() );

		$linked = array();
		foreach ( $reviews as $review ) {
			$linked[ absint( $review['brief_id'] ) ] = true;
		}
		$workbench = $this->content_workbench->report( 250, false );
		$startable = array();
		foreach ( $user_filter ? array() : (array) ( $workbench['briefs'] ?? array() ) as $brief ) {
			if ( in_array( sanitize_key( $brief['status'] ?? '' ), array( 'draft_created', 'ready' ), true ) && ! empty( $brief['draft_post_id'] ) && empty( $linked[ absint( $brief['id'] ) ] ) ) {
				$startable[] = $brief;
			}
		}

		$now = time();
		$summary = array(
			'active' => 0,
			'overdue' => 0,
			'blocked' => 0,
			'awaiting_review' => 0,
			'signed_off' => 0,
			'open_comments' => 0,
			'pending_required_checks' => 0,
		);
		$calendar = array();
		foreach ( $reviews as $review ) {
			if ( 'signed_off' !== $review['status'] ) {
				$summary['active']++;
			}
			if ( ! empty( $review['is_overdue'] ) ) {
				$summary['overdue']++;
			}
			if ( 'blocked' === $review['status'] ) {
				$summary['blocked']++;
			}
			if ( 'review_requested' === $review['status'] ) {
				$summary['awaiting_review']++;
			}
			if ( 'signed_off' === $review['status'] ) {
				$summary['signed_off']++;
			}
			$summary['open_comments'] += absint( $review['open_comment_count'] );
			$summary['pending_required_checks'] += absint( $review['pending_required_check_count'] );
			foreach ( array( 'due_at' => 'Writing due', 'review_due_at' => 'Review due' ) as $field => $label ) {
				if ( ! empty( $review[ $field ] ) ) {
					$calendar[] = array(
						'review_id' => absint( $review['id'] ),
						'brief_id' => absint( $review['brief_id'] ),
						'title' => $label . ': ' . ( $review['brief']['page_title'] ?? 'Controlled draft' ),
						'due_at' => $review[ $field ],
						'status' => $review['status'],
					);
				}
			}
		}
		usort( $calendar, function( $a, $b ) { return strcmp( (string) $a['due_at'], (string) $b['due_at'] ); } );

		$result = array(
			'generated_at' => current_time( 'mysql', true ),
			'status' => $status,
			'summary' => $summary,
			'reviews' => $reviews,
			'startable_briefs' => $startable,
			'calendar' => $calendar,
			'workflow' => array( 'assign', 'write', 'request_review', 'comment_and_verify', 'request_changes_or_approve', 'final_signoff', 'separate_publish_decision' ),
			'safety' => array(
				'Editorial sign-off records approval but never publishes a post.',
				'Open comments, failed checks, pending required checks, changed drafts and stale opportunity evidence block sign-off.',
				'Every review request and revision submission stores an immutable draft snapshot.',
				'Publishing, redirects, deletion, canonical and noindex changes remain outside this workflow.',
			),
		);
		if ( ! $user_filter ) {
			set_transient( $cache_key, $result, 3 * MINUTE_IN_SECONDS );
		}
		return $result;
	}

	public function start_review( $brief_id, array $assignment = array(), $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_manage', __( 'Only an administrator can start and assign editorial reviews.', 'ikon-seo' ) );
		}
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_editorial_tables', __( 'Editorial Review database tables are not ready.', 'ikon-seo' ) );
		}
		$brief = $this->content_workbench->brief( $brief_id );
		if ( ! $brief || ! in_array( sanitize_key( $brief['status'] ?? '' ), array( 'draft_created', 'ready' ), true ) || empty( $brief['draft_post_id'] ) ) {
			return new WP_Error( 'ikon_seo_editorial_brief_state', __( 'Create a controlled draft before starting editorial review.', 'ikon-seo' ) );
		}
		$current = $this->content_workbench->assert_current( $brief_id );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$post = get_post( absint( $brief['draft_post_id'] ) );
		if ( ! $post || 'publish' === $post->post_status ) {
			return new WP_Error( 'ikon_seo_editorial_draft_state', __( 'The controlled draft is missing or is no longer unpublished.', 'ikon-seo' ) );
		}
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->reviews_table()} WHERE brief_id=%d LIMIT 1", $brief_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $existing ) {
			return new WP_Error( 'ikon_seo_editorial_exists', __( 'An editorial review already exists for this brief.', 'ikon-seo' ) );
		}
		$assignment = $this->validate_assignment( $assignment );
		if ( is_wp_error( $assignment ) ) {
			return $assignment;
		}
		$status = $assignment['writer_id'] || $assignment['reviewer_id'] ? 'assigned' : 'unassigned';
		$now = current_time( 'mysql', true );
		$inserted = $wpdb->insert(
			$this->reviews_table(),
			array(
				'brief_id' => absint( $brief_id ),
				'draft_post_id' => absint( $brief['draft_post_id'] ),
				'round_number' => 1,
				'status' => $status,
				'writer_id' => $assignment['writer_id'],
				'reviewer_id' => $assignment['reviewer_id'],
				'due_at' => $assignment['due_at'],
				'review_due_at' => $assignment['review_due_at'],
				'blocked_reason' => '',
				'final_signoff_by' => 0,
				'final_signoff_at' => null,
				'current_snapshot_id' => 0,
				'last_snapshot_hash' => '',
				'created_by' => absint( $user_id ),
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
		if ( false === $inserted || ! $wpdb->insert_id ) {
			return new WP_Error( 'ikon_seo_editorial_store', __( 'The editorial review could not be created.', 'ikon-seo' ) );
		}
		$review_id = absint( $wpdb->insert_id );
		$snapshot = $this->create_snapshot( $review_id, 1, absint( $brief['draft_post_id'] ), $user_id, 'baseline' );
		if ( is_wp_error( $snapshot ) ) {
			return $snapshot;
		}
		$this->seed_checks( $review_id, 1, $brief );
		update_post_meta( absint( $brief['draft_post_id'] ), self::META_REVIEW_ID, $review_id );
		update_post_meta( absint( $brief['draft_post_id'] ), '_ikon_seo_workflow_status', 'editorial_assignment' );
		$this->event( $review_id, 1, 'review_started', 'Editorial review started.', array( 'assignment' => $assignment ), $user_id );
		$this->record_history( 'editorial', 'Editorial review started', sprintf( 'Review #%d started for controlled draft #%d. No public page was changed.', $review_id, absint( $brief['draft_post_id'] ) ), $review_id, $user_id, absint( $brief['draft_post_id'] ) );
		$this->clear_cache();
		return $this->get_review( $review_id );
	}

	public function assign( $review_id, array $assignment, $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_manage', __( 'Only an administrator can change editorial assignments.', 'ikon-seo' ) );
		}
		$review = $this->get_review( $review_id, true );
		if ( ! $review ) {
			return new WP_Error( 'ikon_seo_editorial_missing', __( 'The editorial review could not be found.', 'ikon-seo' ) );
		}
		if ( 'signed_off' === $review['status'] ) {
			return new WP_Error( 'ikon_seo_editorial_signed', __( 'A signed-off review cannot be reassigned.', 'ikon-seo' ) );
		}
		$assignment = $this->validate_assignment( $assignment );
		if ( is_wp_error( $assignment ) ) {
			return $assignment;
		}
		$new_status = 'unassigned' === $review['status'] && ( $assignment['writer_id'] || $assignment['reviewer_id'] ) ? 'assigned' : $review['status'];
		$wpdb->update(
			$this->reviews_table(),
			array(
				'writer_id' => $assignment['writer_id'],
				'reviewer_id' => $assignment['reviewer_id'],
				'due_at' => $assignment['due_at'],
				'review_due_at' => $assignment['review_due_at'],
				'status' => $new_status,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $review_id )
		);
		$this->event( $review_id, absint( $review['round_number'] ), 'assignment_updated', 'Editorial assignment and deadlines updated.', array( 'assignment' => $assignment ), $user_id );
		$this->clear_cache();
		return $this->get_review( $review_id );
	}

	public function request_review( $review_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		$review = $this->get_review( $review_id, true );
		if ( ! $review || ! in_array( $review['status'], array( 'assigned', 'writing', 'changes_requested' ), true ) ) {
			return new WP_Error( 'ikon_seo_editorial_request_state', __( 'This review is not in a state that can be submitted for review.', 'ikon-seo' ) );
		}
		if ( ! $this->can_write( $review, $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_writer_permission', __( 'Only the assigned writer or an administrator can submit this draft for review.', 'ikon-seo' ) );
		}
		$current = $this->content_workbench->assert_current( absint( $review['brief_id'] ) );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$snapshot = $this->create_snapshot( $review_id, absint( $review['round_number'] ), absint( $review['draft_post_id'] ), $user_id, 'review_request' );
		if ( is_wp_error( $snapshot ) ) {
			return $snapshot;
		}
		$wpdb->update( $this->reviews_table(), array( 'status' => 'review_requested', 'blocked_reason' => '', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $review_id ) );
		update_post_meta( absint( $review['draft_post_id'] ), '_ikon_seo_workflow_status', 'editorial_review_requested' );
		$this->event( $review_id, absint( $review['round_number'] ), 'review_requested', $notes ?: 'Draft submitted for editorial review.', array( 'snapshot_id' => absint( $snapshot['id'] ) ), $user_id );
		$this->clear_cache();
		return $this->get_review( $review_id );
	}

	public function add_comment( $review_id, array $comment, $user_id = 0 ) {
		global $wpdb;
		$review = $this->get_review( $review_id, true );
		if ( ! $review || 'signed_off' === $review['status'] ) {
			return new WP_Error( 'ikon_seo_editorial_comment_state', __( 'Comments cannot be added to this review.', 'ikon-seo' ) );
		}
		if ( ! $this->can_participate( $review, $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_participant', __( 'Only the assigned writer, reviewer or an administrator can comment.', 'ikon-seo' ) );
		}
		$type = sanitize_key( $comment['type'] ?? 'general' );
		if ( ! in_array( $type, array( 'inline', 'source', 'claim', 'structure', 'seo', 'accessibility', 'general' ), true ) ) {
			$type = 'general';
		}
		$text = sanitize_textarea_field( $comment['text'] ?? '' );
		if ( '' === trim( $text ) ) {
			return new WP_Error( 'ikon_seo_editorial_comment_empty', __( 'Enter a revision request or editorial comment.', 'ikon-seo' ) );
		}
		$assigned_to = absint( $comment['assigned_to'] ?? $review['writer_id'] );
		if ( $assigned_to && ! $this->valid_editor( $assigned_to ) ) {
			return new WP_Error( 'ikon_seo_editorial_assignee', __( 'The selected comment assignee cannot edit posts.', 'ikon-seo' ) );
		}
		$now = current_time( 'mysql', true );
		$wpdb->insert(
			$this->comments_table(),
			array(
				'review_id' => $review_id,
				'round_number' => absint( $review['round_number'] ),
				'comment_type' => $type,
				'anchor_text' => sanitize_text_field( $comment['anchor_text'] ?? '' ),
				'section_key' => sanitize_key( $comment['section_key'] ?? '' ),
				'comment_text' => $text,
				'status' => 'open',
				'created_by' => absint( $user_id ),
				'assigned_to' => $assigned_to,
				'resolved_by' => 0,
				'resolved_at' => null,
				'resolution_notes' => '',
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
		$comment_id = absint( $wpdb->insert_id );
		$this->event( $review_id, absint( $review['round_number'] ), 'comment_added', 'Editorial comment added.', array( 'comment_id' => $comment_id, 'type' => $type ), $user_id );
		$this->clear_cache();
		return $this->get_comment( $comment_id );
	}

	public function resolve_comment( $comment_id, $resolution = 'resolved', $notes = '', $user_id = 0 ) {
		global $wpdb;
		$comment = $this->get_comment( $comment_id, true );
		if ( ! $comment ) {
			return new WP_Error( 'ikon_seo_editorial_comment_missing', __( 'The editorial comment could not be found.', 'ikon-seo' ) );
		}
		$review = $this->get_review( absint( $comment['review_id'] ), true );
		if ( ! $review || ! $this->can_participate( $review, $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_participant', __( 'Only an assigned participant or administrator can resolve this comment.', 'ikon-seo' ) );
		}
		$resolution = in_array( $resolution, array( 'resolved', 'dismissed', 'open' ), true ) ? $resolution : 'resolved';
		$wpdb->update(
			$this->comments_table(),
			array(
				'status' => $resolution,
				'resolved_by' => 'open' === $resolution ? 0 : absint( $user_id ),
				'resolved_at' => 'open' === $resolution ? null : current_time( 'mysql', true ),
				'resolution_notes' => sanitize_textarea_field( $notes ),
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $comment_id )
		);
		$this->event( absint( $comment['review_id'] ), absint( $comment['round_number'] ), 'comment_' . $resolution, 'Editorial comment status changed to ' . $resolution . '.', array( 'comment_id' => $comment_id ), $user_id );
		$this->clear_cache();
		return $this->get_comment( $comment_id );
	}

	public function update_check( $check_id, $status, $notes = '', $user_id = 0 ) {
		global $wpdb;
		$check = $this->get_check( $check_id, true );
		if ( ! $check ) {
			return new WP_Error( 'ikon_seo_editorial_check_missing', __( 'The verification item could not be found.', 'ikon-seo' ) );
		}
		$review = $this->get_review( absint( $check['review_id'] ), true );
		if ( ! $review || ! $this->can_review( $review, $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_reviewer', __( 'Only the assigned reviewer or an administrator can verify sources and claims.', 'ikon-seo' ) );
		}
		$status = in_array( $status, array( 'pending', 'verified', 'failed', 'not_applicable' ), true ) ? $status : 'pending';
		$wpdb->update(
			$this->checks_table(),
			array(
				'status' => $status,
				'notes' => sanitize_textarea_field( $notes ),
				'checked_by' => 'pending' === $status ? 0 : absint( $user_id ),
				'checked_at' => 'pending' === $status ? null : current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $check_id )
		);
		$this->event( absint( $check['review_id'] ), absint( $check['round_number'] ), 'check_' . $status, 'Verification item marked ' . $status . '.', array( 'check_id' => $check_id, 'check_type' => $check['check_type'] ), $user_id );
		$this->clear_cache();
		return $this->get_check( $check_id );
	}

	public function request_changes( $review_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		$review = $this->get_review( $review_id, true );
		if ( ! $review || ! in_array( $review['status'], array( 'review_requested', 'approved' ), true ) ) {
			return new WP_Error( 'ikon_seo_editorial_changes_state', __( 'Changes can be requested only during an active review round.', 'ikon-seo' ) );
		}
		if ( ! $this->can_review( $review, $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_reviewer', __( 'Only the assigned reviewer or an administrator can request changes.', 'ikon-seo' ) );
		}
		if ( '' === trim( $notes ) && 0 === $this->open_comment_count( $review_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_changes_empty', __( 'Add a comment or explain the requested revision.', 'ikon-seo' ) );
		}
		$wpdb->update( $this->reviews_table(), array( 'status' => 'changes_requested', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $review_id ) );
		update_post_meta( absint( $review['draft_post_id'] ), '_ikon_seo_workflow_status', 'editorial_changes_requested' );
		$this->event( $review_id, absint( $review['round_number'] ), 'changes_requested', $notes ?: 'Revision requested from open editorial comments.', array(), $user_id );
		$this->clear_cache();
		return $this->get_review( $review_id );
	}

	public function submit_revision( $review_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		$review = $this->get_review( $review_id, true );
		if ( ! $review || 'changes_requested' !== $review['status'] ) {
			return new WP_Error( 'ikon_seo_editorial_revision_state', __( 'A revision can be submitted only after changes were requested.', 'ikon-seo' ) );
		}
		if ( ! $this->can_write( $review, $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_writer_permission', __( 'Only the assigned writer or an administrator can submit a revision.', 'ikon-seo' ) );
		}
		$current = $this->content_workbench->assert_current( absint( $review['brief_id'] ) );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$new_round = absint( $review['round_number'] ) + 1;
		$snapshot = $this->create_snapshot( $review_id, $new_round, absint( $review['draft_post_id'] ), $user_id, 'revision_submission' );
		if ( is_wp_error( $snapshot ) ) {
			return $snapshot;
		}
		$this->copy_checks_to_round( $review_id, absint( $review['round_number'] ), $new_round );
		$wpdb->update( $this->reviews_table(), array( 'round_number' => $new_round, 'status' => 'review_requested', 'blocked_reason' => '', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $review_id ) );
		update_post_meta( absint( $review['draft_post_id'] ), '_ikon_seo_workflow_status', 'editorial_review_requested' );
		$this->event( $review_id, $new_round, 'revision_submitted', $notes ?: 'A new revision was submitted for editorial review.', array( 'snapshot_id' => absint( $snapshot['id'] ) ), $user_id );
		$this->clear_cache();
		return $this->get_review( $review_id );
	}

	public function approve_round( $review_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		$review = $this->get_review( $review_id, true );
		if ( ! $review || 'review_requested' !== $review['status'] ) {
			return new WP_Error( 'ikon_seo_editorial_approve_state', __( 'Only a submitted review round can be approved.', 'ikon-seo' ) );
		}
		$gate = $this->approval_gate( $review );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		if ( ! $this->can_review( $review, $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_reviewer', __( 'Only the assigned reviewer or an administrator can approve this round.', 'ikon-seo' ) );
		}
		$wpdb->update( $this->reviews_table(), array( 'status' => 'approved', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $review_id ) );
		update_post_meta( absint( $review['draft_post_id'] ), '_ikon_seo_workflow_status', 'editorial_round_approved' );
		$this->event( $review_id, absint( $review['round_number'] ), 'round_approved', $notes ?: 'Editorial review round approved.', array( 'snapshot_id' => absint( $review['current_snapshot_id'] ) ), $user_id );
		$this->clear_cache();
		return $this->get_review( $review_id );
	}

	public function sign_off( $review_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		$review = $this->get_review( $review_id, true );
		if ( ! $review || 'approved' !== $review['status'] ) {
			return new WP_Error( 'ikon_seo_editorial_signoff_state', __( 'Approve the current editorial round before final sign-off.', 'ikon-seo' ) );
		}
		$brief = $this->content_workbench->brief( absint( $review['brief_id'] ) );
		if ( ! $brief || 'ready' !== sanitize_key( $brief['status'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_editorial_quality_gate', __( 'The controlled draft must pass Publisher Intelligence and be marked Ready before final sign-off.', 'ikon-seo' ) );
		}
		$current = $this->content_workbench->assert_current( absint( $review['brief_id'] ) );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$gate = $this->approval_gate( $review );
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		if ( ! $this->can_review( $review, $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_signoff_reviewer', __( 'Only the assigned reviewer or an administrator can sign off this draft.', 'ikon-seo' ) );
		}
		$now = current_time( 'mysql', true );
		$wpdb->update( $this->reviews_table(), array( 'status' => 'signed_off', 'final_signoff_by' => absint( $user_id ), 'final_signoff_at' => $now, 'updated_at' => $now ), array( 'id' => $review_id ) );
		update_post_meta(
			absint( $review['draft_post_id'] ),
			self::META_SIGNOFF,
			array(
				'review_id' => $review_id,
				'round_number' => absint( $review['round_number'] ),
				'snapshot_hash' => sanitize_text_field( $review['last_snapshot_hash'] ),
				'signed_off_by' => absint( $user_id ),
				'signed_off_at' => $now,
				'notes' => sanitize_textarea_field( $notes ),
				'publishes_automatically' => false,
			)
		);
		update_post_meta( absint( $review['draft_post_id'] ), '_ikon_seo_workflow_status', 'approved_for_manual_publish' );
		$this->event( $review_id, absint( $review['round_number'] ), 'final_signoff', $notes ?: 'Final human editorial sign-off recorded. Publishing remains separate.', array( 'automatic_publish' => false ), $user_id );
		$this->record_history( 'approval', 'Final editorial sign-off recorded', sprintf( 'Controlled draft #%d received final human sign-off. It remains unpublished.', absint( $review['draft_post_id'] ) ), $review_id, $user_id, absint( $review['draft_post_id'] ) );
		$this->clear_cache();
		return $this->get_review( $review_id );
	}

	public function block( $review_id, $reason = '', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_manage', __( 'Only an administrator can block an editorial review.', 'ikon-seo' ) );
		}
		$review = $this->get_review( $review_id, true );
		if ( ! $review || 'signed_off' === $review['status'] ) {
			return new WP_Error( 'ikon_seo_editorial_block_state', __( 'This review cannot be blocked.', 'ikon-seo' ) );
		}
		if ( '' === trim( $reason ) ) {
			return new WP_Error( 'ikon_seo_editorial_block_reason', __( 'Explain what is blocking the review.', 'ikon-seo' ) );
		}
		$wpdb->update( $this->reviews_table(), array( 'status' => 'blocked', 'blocked_reason' => sanitize_textarea_field( $reason ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $review_id ) );
		$this->event( $review_id, absint( $review['round_number'] ), 'blocked', $reason, array( 'previous_status' => $review['status'] ), $user_id );
		$this->clear_cache();
		return $this->get_review( $review_id );
	}

	public function unblock( $review_id, $notes = '', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_manage', __( 'Only an administrator can reopen a blocked editorial review.', 'ikon-seo' ) );
		}
		$review = $this->get_review( $review_id, true );
		if ( ! $review || 'blocked' !== $review['status'] ) {
			return new WP_Error( 'ikon_seo_editorial_unblock_state', __( 'Only a blocked review can be reopened.', 'ikon-seo' ) );
		}
		$next = $this->open_comment_count( $review_id ) ? 'changes_requested' : 'assigned';
		$wpdb->update( $this->reviews_table(), array( 'status' => $next, 'blocked_reason' => '', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $review_id ) );
		$this->event( $review_id, absint( $review['round_number'] ), 'unblocked', $notes ?: 'Editorial review reopened.', array( 'next_status' => $next ), $user_id );
		$this->clear_cache();
		return $this->get_review( $review_id );
	}

	public function compare_versions( $review_id, $from_snapshot_id = 0, $to_snapshot_id = 0 ) {
		global $wpdb;
		$review = $this->get_review( $review_id, true );
		if ( ! $review ) {
			return new WP_Error( 'ikon_seo_editorial_missing', __( 'The editorial review could not be found.', 'ikon-seo' ) );
		}
		$snapshots = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->snapshots_table()} WHERE review_id=%d ORDER BY id ASC", $review_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( count( $snapshots ) < 2 ) {
			return array( 'review_id' => $review_id, 'available' => false, 'message' => 'At least two snapshots are required for comparison.' );
		}
		$by_id = array();
		foreach ( $snapshots as $snapshot ) {
			$by_id[ absint( $snapshot['id'] ) ] = $snapshot;
		}
		$from = $from_snapshot_id && isset( $by_id[ $from_snapshot_id ] ) ? $by_id[ $from_snapshot_id ] : $snapshots[ count( $snapshots ) - 2 ];
		$to = $to_snapshot_id && isset( $by_id[ $to_snapshot_id ] ) ? $by_id[ $to_snapshot_id ] : $snapshots[ count( $snapshots ) - 1 ];
		$from_lines = $this->meaningful_lines( $from['content'] );
		$to_lines = $this->meaningful_lines( $to['content'] );
		$added = array_values( array_diff( $to_lines, $from_lines ) );
		$removed = array_values( array_diff( $from_lines, $to_lines ) );
		return array(
			'review_id' => $review_id,
			'available' => true,
			'from' => $this->format_snapshot( $from, false ),
			'to' => $this->format_snapshot( $to, false ),
			'summary' => array(
				'title_changed' => (string) $from['title'] !== (string) $to['title'],
				'word_count_before' => absint( $from['word_count'] ),
				'word_count_after' => absint( $to['word_count'] ),
				'word_count_change' => absint( $to['word_count'] ) - absint( $from['word_count'] ),
				'added_paragraphs' => count( $added ),
				'removed_paragraphs' => count( $removed ),
				'content_changed' => (string) $from['snapshot_hash'] !== (string) $to['snapshot_hash'],
			),
			'added_excerpt' => array_slice( $added, 0, 20 ),
			'removed_excerpt' => array_slice( $removed, 0, 20 ),
		);
	}

	private function approval_gate( array $review ) {
		$current = $this->content_workbench->assert_current( absint( $review['brief_id'] ) );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		if ( $this->open_comment_count( absint( $review['id'] ) ) ) {
			return new WP_Error( 'ikon_seo_editorial_open_comments', __( 'Resolve or dismiss all open editorial comments first.', 'ikon-seo' ) );
		}
		$check_counts = $this->check_counts( absint( $review['id'] ), absint( $review['round_number'] ) );
		if ( $check_counts['failed'] ) {
			return new WP_Error( 'ikon_seo_editorial_failed_checks', __( 'Resolve failed source or claim verification checks first.', 'ikon-seo' ) );
		}
		if ( $check_counts['pending_required'] ) {
			return new WP_Error( 'ikon_seo_editorial_pending_checks', __( 'Complete all required source and claim verification checks first.', 'ikon-seo' ) );
		}
		$post_hash = $this->current_post_hash( absint( $review['draft_post_id'] ) );
		if ( ! $post_hash || ! hash_equals( (string) $review['last_snapshot_hash'], $post_hash ) ) {
			return new WP_Error( 'ikon_seo_editorial_changed_after_snapshot', __( 'The draft changed after the latest review snapshot. Submit a new revision before approval.', 'ikon-seo' ) );
		}
		return true;
	}

	private function create_snapshot( $review_id, $round_number, $post_id, $user_id, $reason ) {
		global $wpdb;
		$post = get_post( $post_id );
		if ( ! $post || 'publish' === $post->post_status ) {
			return new WP_Error( 'ikon_seo_editorial_snapshot_post', __( 'The unpublished controlled draft could not be snapshotted.', 'ikon-seo' ) );
		}
		$builder_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( is_array( $builder_data ) || is_object( $builder_data ) ) {
			$builder_data = wp_json_encode( $builder_data );
		}
		$builder_data = (string) $builder_data;
		if ( strlen( $builder_data ) > 524288 ) {
			$builder_data = substr( $builder_data, 0, 524288 );
		}
		$content = (string) $post->post_content;
		$hash = $this->snapshot_hash( $post->post_title, $post->post_excerpt, $content, $builder_data );
		$now = current_time( 'mysql', true );
		$wpdb->insert(
			$this->snapshots_table(),
			array(
				'review_id' => absint( $review_id ),
				'round_number' => absint( $round_number ),
				'draft_post_id' => absint( $post_id ),
				'snapshot_hash' => $hash,
				'snapshot_reason' => sanitize_key( $reason ),
				'title' => sanitize_text_field( $post->post_title ),
				'excerpt' => sanitize_textarea_field( $post->post_excerpt ),
				'content' => $content,
				'builder_data' => $builder_data,
				'word_count' => str_word_count( wp_strip_all_tags( $content ) ),
				'created_by' => absint( $user_id ),
				'created_at' => $now,
			)
		);
		$snapshot_id = absint( $wpdb->insert_id );
		if ( ! $snapshot_id ) {
			return new WP_Error( 'ikon_seo_editorial_snapshot_store', __( 'The draft snapshot could not be stored.', 'ikon-seo' ) );
		}
		$wpdb->update( $this->reviews_table(), array( 'current_snapshot_id' => $snapshot_id, 'last_snapshot_hash' => $hash, 'updated_at' => $now ), array( 'id' => absint( $review_id ) ) );
		return $this->get_snapshot( $snapshot_id );
	}

	private function seed_checks( $review_id, $round_number, array $brief ) {
		$brief_data = (array) ( $brief['brief'] ?? array() );
		$items = array();
		foreach ( (array) ( $brief_data['source_requirements'] ?? array() ) as $index => $label ) {
			$items[] = array( 'type' => 'source', 'key' => 'source_' . md5( $index . '|' . $label ), 'label' => $label, 'evidence' => '', 'required' => 1 );
		}
		foreach ( (array) ( $brief_data['direct_evidence'] ?? array() ) as $index => $evidence ) {
			$items[] = array( 'type' => 'source', 'key' => 'evidence_' . md5( $index . '|' . $evidence ), 'label' => 'Confirm direct evidence is represented accurately.', 'evidence' => $evidence, 'required' => 1 );
		}
		foreach ( (array) ( $brief_data['unsupported_claims'] ?? $brief_data['unsupported_claim_exclusions'] ?? array() ) as $index => $claim ) {
			$items[] = array( 'type' => 'claim', 'key' => 'claim_' . md5( $index . '|' . $claim ), 'label' => 'Confirm this unsupported claim is excluded or properly evidenced.', 'evidence' => $claim, 'required' => 1 );
		}
		$items[] = array( 'type' => 'quality', 'key' => 'quality_intent', 'label' => 'Target intent and page type are still correct.', 'evidence' => sanitize_text_field( $brief['target_intent'] ?? '' ), 'required' => 1 );
		$items[] = array( 'type' => 'quality', 'key' => 'quality_links', 'label' => 'Internal links and conversion actions are relevant and not misleading.', 'evidence' => '', 'required' => 1 );
		foreach ( $items as $item ) {
			$this->insert_check( $review_id, $round_number, $item );
		}
	}

	private function copy_checks_to_round( $review_id, $from_round, $to_round ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->checks_table()} WHERE review_id=%d AND round_number=%d ORDER BY id ASC", $review_id, $from_round ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows ?: array() as $row ) {
			$this->insert_check(
				$review_id,
				$to_round,
				array(
					'type' => $row['check_type'],
					'key' => $row['check_key'],
					'label' => $row['label'],
					'evidence' => $row['evidence'],
					'required' => absint( $row['required'] ),
				)
			);
		}
	}

	private function insert_check( $review_id, $round_number, array $item ) {
		global $wpdb;
		$key = sanitize_key( $item['key'] ?? md5( wp_json_encode( $item ) ) );
		$exists = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->checks_table()} WHERE review_id=%d AND round_number=%d AND check_key=%s LIMIT 1", $review_id, $round_number, $key ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $exists ) {
			return;
		}
		$now = current_time( 'mysql', true );
		$wpdb->insert(
			$this->checks_table(),
			array(
				'review_id' => absint( $review_id ),
				'round_number' => absint( $round_number ),
				'check_type' => sanitize_key( $item['type'] ?? 'quality' ),
				'check_key' => $key,
				'label' => sanitize_text_field( $item['label'] ?? '' ),
				'evidence' => sanitize_textarea_field( $item['evidence'] ?? '' ),
				'required' => ! empty( $item['required'] ) ? 1 : 0,
				'status' => 'pending',
				'notes' => '',
				'checked_by' => 0,
				'checked_at' => null,
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
	}

	private function format_review( $row ) {
		$review_id = absint( $row['id'] );
		$brief = $this->content_workbench->brief( absint( $row['brief_id'] ) );
		$comments = $this->comments( $review_id );
		$checks = $this->checks( $review_id, absint( $row['round_number'] ) );
		$snapshots = $this->snapshots( $review_id );
		$events = $this->events( $review_id );
		$check_counts = $this->check_counts_from_rows( $checks );
		$current_hash = $this->current_post_hash( absint( $row['draft_post_id'] ) );
		$due = $row['review_due_at'] ?: $row['due_at'];
		$is_overdue = $due && 'signed_off' !== $row['status'] && strtotime( $due . ' UTC' ) < time();
		$formatted = array(
			'id' => $review_id,
			'brief_id' => absint( $row['brief_id'] ),
			'draft_post_id' => absint( $row['draft_post_id'] ),
			'round_number' => absint( $row['round_number'] ),
			'status' => sanitize_key( $row['status'] ),
			'writer_id' => absint( $row['writer_id'] ),
			'writer' => $this->user_summary( absint( $row['writer_id'] ) ),
			'reviewer_id' => absint( $row['reviewer_id'] ),
			'reviewer' => $this->user_summary( absint( $row['reviewer_id'] ) ),
			'due_at' => $row['due_at'],
			'review_due_at' => $row['review_due_at'],
			'is_overdue' => (bool) $is_overdue,
			'blocked_reason' => $row['blocked_reason'],
			'final_signoff_by' => absint( $row['final_signoff_by'] ),
			'final_signoff_user' => $this->user_summary( absint( $row['final_signoff_by'] ) ),
			'final_signoff_at' => $row['final_signoff_at'],
			'current_snapshot_id' => absint( $row['current_snapshot_id'] ),
			'last_snapshot_hash' => $row['last_snapshot_hash'],
			'draft_changed_after_snapshot' => $current_hash && $row['last_snapshot_hash'] ? ! hash_equals( (string) $row['last_snapshot_hash'], $current_hash ) : false,
			'open_comment_count' => count( array_filter( $comments, function( $comment ) { return 'open' === $comment['status']; } ) ),
			'pending_required_check_count' => $check_counts['pending_required'],
			'failed_check_count' => $check_counts['failed'],
			'comments' => $comments,
			'checks' => $checks,
			'snapshots' => $snapshots,
			'events' => $events,
			'brief' => $brief,
			'edit_url' => get_edit_post_link( absint( $row['draft_post_id'] ), 'raw' ),
			'preview_url' => get_preview_post_link( absint( $row['draft_post_id'] ) ),
			'created_at' => $row['created_at'],
			'updated_at' => $row['updated_at'],
		);
		if ( count( $snapshots ) >= 2 ) {
			$formatted['latest_comparison'] = $this->compare_versions( $review_id );
		} else {
			$formatted['latest_comparison'] = array( 'available' => false );
		}
		return $formatted;
	}

	private function comments( $review_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->comments_table()} WHERE review_id=%d ORDER BY FIELD(status,'open','resolved','dismissed'), round_number DESC, id DESC", $review_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( array( $this, 'format_comment' ), $rows ?: array() );
	}

	private function checks( $review_id, $round_number ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->checks_table()} WHERE review_id=%d AND round_number=%d ORDER BY FIELD(check_type,'source','claim','quality'), id ASC", $review_id, $round_number ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( array( $this, 'format_check' ), $rows ?: array() );
	}

	private function snapshots( $review_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,review_id,round_number,draft_post_id,snapshot_hash,snapshot_reason,title,word_count,created_by,created_at FROM {$this->snapshots_table()} WHERE review_id=%d ORDER BY id ASC", $review_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( array( $this, 'format_snapshot' ), $rows ?: array() );
	}

	private function events( $review_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->events_table()} WHERE review_id=%d ORDER BY id DESC LIMIT 100", $review_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( function( $row ) {
			return array(
				'id' => absint( $row['id'] ),
				'round_number' => absint( $row['round_number'] ),
				'event_type' => sanitize_key( $row['event_type'] ),
				'actor_id' => absint( $row['actor_id'] ),
				'actor' => $this->user_summary( absint( $row['actor_id'] ) ),
				'notes' => $row['notes'],
				'payload' => $this->decode_json( $row['payload_json'] ),
				'created_at' => $row['created_at'],
			);
		}, $rows ?: array() );
	}

	private function get_review( $id, $raw = false ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->reviews_table()} WHERE id=%d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? ( $raw ? $row : $this->format_review( $row ) ) : null;
	}

	private function get_comment( $id, $raw = false ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->comments_table()} WHERE id=%d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? ( $raw ? $row : $this->format_comment( $row ) ) : null;
	}

	private function get_check( $id, $raw = false ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->checks_table()} WHERE id=%d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? ( $raw ? $row : $this->format_check( $row ) ) : null;
	}

	private function get_snapshot( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->snapshots_table()} WHERE id=%d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? $this->format_snapshot( $row ) : null;
	}

	private function format_comment( $row ) {
		return array(
			'id' => absint( $row['id'] ),
			'review_id' => absint( $row['review_id'] ),
			'round_number' => absint( $row['round_number'] ),
			'type' => sanitize_key( $row['comment_type'] ),
			'anchor_text' => $row['anchor_text'],
			'section_key' => $row['section_key'],
			'text' => $row['comment_text'],
			'status' => sanitize_key( $row['status'] ),
			'created_by' => absint( $row['created_by'] ),
			'creator' => $this->user_summary( absint( $row['created_by'] ) ),
			'assigned_to' => absint( $row['assigned_to'] ),
			'assignee' => $this->user_summary( absint( $row['assigned_to'] ) ),
			'resolved_by' => absint( $row['resolved_by'] ),
			'resolution_notes' => $row['resolution_notes'],
			'resolved_at' => $row['resolved_at'],
			'created_at' => $row['created_at'],
			'updated_at' => $row['updated_at'],
		);
	}

	private function format_check( $row ) {
		return array(
			'id' => absint( $row['id'] ),
			'review_id' => absint( $row['review_id'] ),
			'round_number' => absint( $row['round_number'] ),
			'type' => sanitize_key( $row['check_type'] ),
			'key' => sanitize_key( $row['check_key'] ),
			'label' => $row['label'],
			'evidence' => $row['evidence'],
			'required' => (bool) $row['required'],
			'status' => sanitize_key( $row['status'] ),
			'notes' => $row['notes'],
			'checked_by' => absint( $row['checked_by'] ),
			'checked_by_user' => $this->user_summary( absint( $row['checked_by'] ) ),
			'checked_at' => $row['checked_at'],
		);
	}

	private function format_snapshot( $row, $with_content = false ) {
		$result = array(
			'id' => absint( $row['id'] ),
			'review_id' => absint( $row['review_id'] ),
			'round_number' => absint( $row['round_number'] ),
			'draft_post_id' => absint( $row['draft_post_id'] ),
			'snapshot_hash' => $row['snapshot_hash'],
			'reason' => sanitize_key( $row['snapshot_reason'] ),
			'title' => $row['title'],
			'word_count' => absint( $row['word_count'] ),
			'created_by' => absint( $row['created_by'] ),
			'creator' => $this->user_summary( absint( $row['created_by'] ) ),
			'created_at' => $row['created_at'],
		);
		if ( $with_content ) {
			$result['excerpt'] = $row['excerpt'];
			$result['content'] = $row['content'];
		}
		return $result;
	}

	private function validate_assignment( array $assignment ) {
		$writer_id = absint( $assignment['writer_id'] ?? 0 );
		$reviewer_id = absint( $assignment['reviewer_id'] ?? 0 );
		if ( $writer_id && ! $this->valid_editor( $writer_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_writer', __( 'The selected writer cannot edit posts.', 'ikon-seo' ) );
		}
		if ( $reviewer_id && ! $this->valid_editor( $reviewer_id ) ) {
			return new WP_Error( 'ikon_seo_editorial_reviewer', __( 'The selected reviewer cannot edit posts.', 'ikon-seo' ) );
		}
		if ( $writer_id && $reviewer_id && $writer_id === $reviewer_id ) {
			return new WP_Error( 'ikon_seo_editorial_separation', __( 'Assign different people as writer and reviewer when both roles are used.', 'ikon-seo' ) );
		}
		return array(
			'writer_id' => $writer_id,
			'reviewer_id' => $reviewer_id,
			'due_at' => $this->sanitize_datetime( $assignment['due_at'] ?? '' ),
			'review_due_at' => $this->sanitize_datetime( $assignment['review_due_at'] ?? '' ),
		);
	}

	private function valid_editor( $user_id ) {
		if ( ! $user_id ) {
			return true;
		}
		return function_exists( 'user_can' ) ? user_can( $user_id, 'edit_posts' ) : (bool) get_user_by( 'id', $user_id );
	}

	private function can_manage( $user_id ) {
		$user_id = absint( $user_id );
		return $user_id && function_exists( 'user_can' ) && user_can( $user_id, 'manage_options' );
	}

	private function can_write( array $review, $user_id ) {
		$user_id = absint( $user_id );
		return $this->can_manage( $user_id ) || ( $user_id && absint( $review['writer_id'] ) === $user_id );
	}

	private function can_participate( array $review, $user_id ) {
		$user_id = absint( $user_id );
		return $this->can_manage( $user_id ) || ( $user_id && in_array( $user_id, array( absint( $review['writer_id'] ), absint( $review['reviewer_id'] ) ), true ) );
	}

	private function can_review( array $review, $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return false;
		}
		if ( absint( $review['reviewer_id'] ) && absint( $review['reviewer_id'] ) === $user_id ) {
			return true;
		}
		return function_exists( 'user_can' ) && user_can( $user_id, 'manage_options' );
	}

	private function user_summary( $user_id ) {
		if ( ! $user_id ) {
			return null;
		}
		$user = get_user_by( 'id', $user_id );
		return $user ? array( 'id' => absint( $user->ID ), 'name' => sanitize_text_field( $user->display_name ) ) : array( 'id' => absint( $user_id ), 'name' => 'User #' . absint( $user_id ) );
	}

	private function open_comment_count( $review_id ) {
		global $wpdb;
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->comments_table()} WHERE review_id=%d AND status='open'", $review_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private function check_counts( $review_id, $round_number ) {
		return $this->check_counts_from_rows( $this->checks( $review_id, $round_number ) );
	}

	private function check_counts_from_rows( array $checks ) {
		$counts = array( 'pending_required' => 0, 'failed' => 0, 'verified' => 0, 'not_applicable' => 0 );
		foreach ( $checks as $check ) {
			$status = sanitize_key( $check['status'] ?? 'pending' );
			if ( 'pending' === $status && ! empty( $check['required'] ) ) {
				$counts['pending_required']++;
			}
			if ( isset( $counts[ $status ] ) ) {
				$counts[ $status ]++;
			}
		}
		return $counts;
	}

	private function event( $review_id, $round_number, $type, $notes, array $payload, $user_id ) {
		global $wpdb;
		$wpdb->insert(
			$this->events_table(),
			array(
				'review_id' => absint( $review_id ),
				'round_number' => absint( $round_number ),
				'event_type' => sanitize_key( $type ),
				'actor_id' => absint( $user_id ),
				'notes' => sanitize_textarea_field( $notes ),
				'payload_json' => wp_json_encode( $payload ),
				'created_at' => current_time( 'mysql', true ),
			)
		);
	}

	private function record_history( $category, $title, $summary, $review_id, $user_id, $post_id = 0 ) {
		$this->history->add( array( 'category' => $category, 'status' => 'open', 'title' => $title, 'summary' => $summary, 'details' => array( 'editorial_review_id' => absint( $review_id ) ), 'related_post_id' => absint( $post_id ) ), 'editorial_review', $user_id );
		$this->logger->log( 'editorial_review', 'success', $summary, absint( $post_id ), absint( $review_id ) );
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
		return $this->snapshot_hash( $post->post_title, $post->post_excerpt, $post->post_content, $builder );
	}

	private function snapshot_hash( $title, $excerpt, $content, $builder_data ) {
		return hash( 'sha256', $this->normalize( $title ) . '|' . $this->normalize( $excerpt ) . '|' . $this->normalize( $content ) . '|' . $this->normalize( $builder_data ) );
	}

	private function meaningful_lines( $content ) {
		$content = preg_replace( '/<\/(p|div|section|li|h[1-6])>/i', "\n", (string) $content );
		$content = wp_strip_all_tags( $content );
		$lines = preg_split( '/[\r\n]+/', $content );
		$result = array();
		foreach ( $lines ?: array() as $line ) {
			$line = trim( preg_replace( '/\s+/', ' ', $line ) );
			if ( strlen( $line ) >= 20 ) {
				$result[] = $line;
			}
		}
		return array_values( array_unique( $result ) );
	}

	private function sanitize_datetime( $value ) {
		$value = sanitize_text_field( $value );
		if ( '' === $value ) {
			return null;
		}
		$value = str_replace( 'T', ' ', $value );
		if ( function_exists( 'get_gmt_from_date' ) ) {
			$gmt = get_gmt_from_date( $value, 'Y-m-d H:i:s' );
			if ( $gmt ) {
				return $gmt;
			}
		}
		$time = strtotime( $value );
		return $time ? gmdate( 'Y-m-d H:i:s', $time ) : null;
	}

	private function tables_ready() {
		foreach ( array( $this->reviews_table(), $this->comments_table(), $this->checks_table(), $this->snapshots_table(), $this->events_table() ) as $table ) {
			if ( ! $this->table_exists( $table ) ) {
				return false;
			}
		}
		return true;
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	private function decode_json( $value ) {
		$data = json_decode( (string) $value, true );
		return is_array( $data ) ? $data : array();
	}

	private function normalize( $value ) {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $value ) ) );
	}

	private function clear_cache() {
		global $wpdb;
		// WordPress has no wildcard transient deletion API. Delete known report variants.
		foreach ( array( 50, 100, 150, 250 ) as $limit ) {
			foreach ( array( '', 'unassigned', 'assigned', 'writing', 'blocked', 'review_requested', 'changes_requested', 'approved', 'signed_off' ) as $status ) {
				delete_transient( self::CACHE_KEY . '_' . md5( wp_json_encode( array( $limit, $status ) ) ) );
			}
		}
		delete_transient( Ikon_SEO_Content_Workbench::CACHE_KEY );
	}
}
