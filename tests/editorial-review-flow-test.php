<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'MINUTE_IN_SECONDS', 60 );

function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '_', (string) $value ) ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function __( $value ) { return $value; }
function current_time( $type, $gmt = false ) { return '2026-08-04 08:00:00'; }
function get_gmt_from_date( $value, $format = 'Y-m-d H:i:s' ) { $time = strtotime( $value . ' UTC' ); return $time ? gmdate( $format, $time ) : ''; }
function get_edit_post_link( $id, $context = '' ) { return 'https://example.com/wp-admin/post.php?post=' . absint( $id ); }
function get_preview_post_link( $id ) { return 'https://example.com/?p=' . absint( $id ) . '&preview=true'; }

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }

$GLOBALS['transients'] = array();
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][ $key ] = $value; return true; }
function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ] ); return true; }

$GLOBALS['posts'] = array(
	501 => (object) array(
		'ID' => 501,
		'post_status' => 'draft',
		'post_type' => 'page',
		'post_title' => 'Office Cleaning Doha',
		'post_excerpt' => 'Controlled draft',
		'post_content' => '<h1>Office Cleaning Doha</h1><p>Evidence-led commercial cleaning draft for property managers in Doha.</p>',
	),
);
$GLOBALS['post_meta'] = array();
function get_post( $id ) { return $GLOBALS['posts'][ absint( $id ) ] ?? null; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['post_meta'][ absint( $id ) ][ $key ] = $value; return true; }
function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['post_meta'][ absint( $id ) ][ $key ] ?? ''; }

$GLOBALS['users'] = array(
	5 => (object) array( 'ID' => 5, 'display_name' => 'Writer' ),
	6 => (object) array( 'ID' => 6, 'display_name' => 'Reviewer' ),
	1 => (object) array( 'ID' => 1, 'display_name' => 'Administrator' ),
);
function get_user_by( $field, $id ) { return $GLOBALS['users'][ absint( $id ) ] ?? false; }
function user_can( $user_id, $capability ) {
	$user_id = absint( $user_id );
	if ( 'manage_options' === $capability ) { return 1 === $user_id; }
	return isset( $GLOBALS['users'][ $user_id ] );
}

class FakeWpdb {
	public $prefix = 'wp_';
	public $insert_id = 0;
	public $tables = array();

	public function __construct() {
		foreach ( array( 'wp_ikon_seo_editorial_reviews', 'wp_ikon_seo_editorial_comments', 'wp_ikon_seo_editorial_checks', 'wp_ikon_seo_editorial_snapshots', 'wp_ikon_seo_editorial_events' ) as $table ) {
			$this->tables[ $table ] = array();
		}
	}

	public function prepare( $query, ...$args ) {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) { $args = $args[0]; }
		foreach ( $args as $arg ) {
			$replacement = is_int( $arg ) || ctype_digit( (string) $arg ) ? (string) (int) $arg : "'" . addslashes( (string) $arg ) . "'";
			$query = preg_replace( '/%[dfs]/', $replacement, $query, 1 );
		}
		return $query;
	}

	public function insert( $table, $row ) {
		$id = count( $this->tables[ $table ] ) + 1;
		$row['id'] = $id;
		$this->tables[ $table ][ $id ] = $row;
		$this->insert_id = $id;
		return 1;
	}

	public function update( $table, $data, $where ) {
		$id = absint( $where['id'] ?? 0 );
		if ( ! $id || empty( $this->tables[ $table ][ $id ] ) ) { return false; }
		$this->tables[ $table ][ $id ] = array_merge( $this->tables[ $table ][ $id ], $data );
		return 1;
	}

	private function table_from_query( $query ) {
		return preg_match( '/FROM\s+([a-z0-9_]+)/i', $query, $match ) ? $match[1] : '';
	}

	private function filtered( $query ) {
		$table = $this->table_from_query( $query );
		$rows = array_values( $this->tables[ $table ] ?? array() );
		foreach ( array( 'id', 'brief_id', 'review_id', 'round_number' ) as $field ) {
			if ( preg_match( '/(?:WHERE|AND)\s+' . $field . '=(\d+)/i', $query, $match ) ) {
				$rows = array_values( array_filter( $rows, function( $row ) use ( $field, $match ) { return absint( $row[ $field ] ?? 0 ) === absint( $match[1] ); } ) );
			}
		}
		if ( preg_match( "/(?:WHERE|AND)\s+status='([^']+)'/i", $query, $match ) ) {
			$rows = array_values( array_filter( $rows, function( $row ) use ( $match ) { return (string) ( $row['status'] ?? '' ) === stripslashes( $match[1] ); } ) );
		}
		if ( preg_match( "/(?:WHERE|AND)\s+check_key='([^']+)'/i", $query, $match ) ) {
			$rows = array_values( array_filter( $rows, function( $row ) use ( $match ) { return (string) ( $row['check_key'] ?? '' ) === stripslashes( $match[1] ); } ) );
		}
		return $rows;
	}

	public function get_var( $query ) {
		if ( preg_match( "/SHOW TABLES LIKE '([^']+)'/i", $query, $match ) ) { return stripslashes( $match[1] ); }
		$rows = $this->filtered( $query );
		if ( false !== stripos( $query, 'COUNT(*)' ) ) { return count( $rows ); }
		if ( preg_match( '/SELECT\s+id\s+FROM/i', $query ) ) { return $rows ? absint( $rows[0]['id'] ) : 0; }
		return 0;
	}

	public function get_row( $query, $output = ARRAY_A ) {
		$rows = $this->filtered( $query );
		return $rows ? $rows[0] : null;
	}

	public function get_results( $query, $output = ARRAY_A ) {
		$rows = $this->filtered( $query );
		if ( false !== stripos( $query, 'ORDER BY id DESC' ) ) { usort( $rows, function( $a, $b ) { return absint( $b['id'] ) - absint( $a['id'] ); } ); }
		else { usort( $rows, function( $a, $b ) { return absint( $a['id'] ) - absint( $b['id'] ); } ); }
		if ( preg_match( '/LIMIT\s+(\d+)/i', $query, $match ) ) { $rows = array_slice( $rows, 0, absint( $match[1] ) ); }
		return $rows;
	}
}
$GLOBALS['wpdb'] = new FakeWpdb();

class Ikon_SEO_Content_Workbench {
	const CACHE_KEY = 'ikon_seo_content_workbench_report_v1';
	public $briefs = array();
	public function brief( $id ) { return $this->briefs[ absint( $id ) ] ?? null; }
	public function assert_current( $id ) { return ! empty( $this->briefs[ absint( $id ) ] ) ? true : new WP_Error( 'missing', 'Missing brief' ); }
	public function report( $limit = 100, $refresh = false ) { return array( 'briefs' => array_values( $this->briefs ) ); }
}
class Ikon_SEO_Publisher_Intelligence {}
class Ikon_SEO_Workspace_History { public $events = array(); public function add( $data, $source, $user ) { $this->events[] = $data; } }
class Ikon_SEO_Logger { public $events = array(); public function log( ...$args ) { $this->events[] = $args; } }

require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-editorial-review.php';

$workbench = new Ikon_SEO_Content_Workbench();
$workbench->briefs[10] = array(
	'id' => 10,
	'status' => 'draft_created',
	'draft_post_id' => 501,
	'page_title' => 'Office Cleaning Doha',
	'target_intent' => 'commercial',
	'brief' => array(
		'source_requirements' => array( 'Use confirmed business and service evidence.' ),
		'direct_evidence' => array( 'The website lists office cleaning in Doha.' ),
		'unsupported_claim_exclusions' => array( 'Do not claim to be Qatar’s number-one cleaner.' ),
	),
);
$reviewer = new Ikon_SEO_Editorial_Review( $workbench, new Ikon_SEO_Publisher_Intelligence(), new Ikon_SEO_Workspace_History(), new Ikon_SEO_Logger() );
$failures = array();

$writer_start = $reviewer->start_review( 10, array( 'writer_id' => 5, 'reviewer_id' => 6 ), 5 );
if ( ! is_wp_error( $writer_start ) || 'ikon_seo_editorial_manage' !== $writer_start->get_error_code() ) { $failures[] = 'A non-administrator was allowed to start an editorial review.'; }

$bad_assignment = $reviewer->start_review( 10, array( 'writer_id' => 5, 'reviewer_id' => 5 ), 1 );
if ( ! is_wp_error( $bad_assignment ) || 'ikon_seo_editorial_separation' !== $bad_assignment->get_error_code() ) { $failures[] = 'Writer/reviewer role separation was not enforced.'; }

$review = $reviewer->start_review( 10, array( 'writer_id' => 5, 'reviewer_id' => 6, 'due_at' => '2026-08-10T12:00', 'review_due_at' => '2026-08-12T12:00' ), 1 );
if ( is_wp_error( $review ) || 'assigned' !== ( $review['status'] ?? '' ) ) { $failures[] = 'A valid controlled draft did not start editorial review.'; }
if ( 1 !== count( $review['snapshots'] ?? array() ) ) { $failures[] = 'Baseline snapshot was not stored.'; }
if ( count( $review['checks'] ?? array() ) < 4 ) { $failures[] = 'Source, claim and quality checks were not seeded.'; }

$duplicate = $reviewer->start_review( 10, array(), 1 );
if ( ! is_wp_error( $duplicate ) || 'ikon_seo_editorial_exists' !== $duplicate->get_error_code() ) { $failures[] = 'Duplicate review creation was not blocked.'; }

$requested = $reviewer->request_review( $review['id'], 'First review request.', 5 );
if ( is_wp_error( $requested ) || 'review_requested' !== ( $requested['status'] ?? '' ) || count( $requested['snapshots'] ?? array() ) < 2 ) { $failures[] = 'Review request did not create a snapshot and update status.'; }

foreach ( $requested['checks'] as $check ) { $reviewer->update_check( $check['id'], 'verified', 'Verified in staging.', 6 ); }
$GLOBALS['posts'][501]->post_content .= '<p>Unsnapshotted edit.</p>';
$changed = $reviewer->approve_round( $review['id'], '', 6 );
if ( ! is_wp_error( $changed ) || 'ikon_seo_editorial_changed_after_snapshot' !== $changed->get_error_code() ) { $failures[] = 'An unsnapshotted edit did not block approval.'; }

$changes = $reviewer->request_changes( $review['id'], 'Review the latest edit.', 6 );
if ( is_wp_error( $changes ) || 'changes_requested' !== ( $changes['status'] ?? '' ) ) { $failures[] = 'Revision request was not recorded.'; }
$revision = $reviewer->submit_revision( $review['id'], 'Revision submitted.', 5 );
if ( is_wp_error( $revision ) || 2 !== absint( $revision['round_number'] ?? 0 ) || 'review_requested' !== ( $revision['status'] ?? '' ) ) { $failures[] = 'Revision submission did not open a new review round.'; }
foreach ( $revision['checks'] as $check ) { $reviewer->update_check( $check['id'], 'verified', 'Reverified.', 6 ); }
$comment = $reviewer->add_comment( $review['id'], array( 'type' => 'claim', 'anchor_text' => 'number-one', 'text' => 'Confirm this unsupported phrase is absent.' ), 6 );
$blocked_comment = $reviewer->approve_round( $review['id'], '', 6 );
if ( ! is_wp_error( $blocked_comment ) || 'ikon_seo_editorial_open_comments' !== $blocked_comment->get_error_code() ) { $failures[] = 'Open editorial comments did not block approval.'; }
$reviewer->resolve_comment( $comment['id'], 'resolved', 'Confirmed absent.', 6 );
$writer_approval = $reviewer->approve_round( $review['id'], 'Writer should not approve.', 5 );
if ( ! is_wp_error( $writer_approval ) || 'ikon_seo_editorial_reviewer' !== $writer_approval->get_error_code() ) { $failures[] = 'The assigned writer was allowed to approve the review round.'; }
$approved = $reviewer->approve_round( $review['id'], 'Round approved.', 6 );
if ( is_wp_error( $approved ) || 'approved' !== ( $approved['status'] ?? '' ) ) { $failures[] = 'Verified review round was not approved.'; }

$early_signoff = $reviewer->sign_off( $review['id'], '', 6 );
if ( ! is_wp_error( $early_signoff ) || 'ikon_seo_editorial_quality_gate' !== $early_signoff->get_error_code() ) { $failures[] = 'Final sign-off was not blocked before Content Workbench readiness.'; }
$workbench->briefs[10]['status'] = 'ready';
$signed = $reviewer->sign_off( $review['id'], 'Approved for a separate publishing decision.', 6 );
if ( is_wp_error( $signed ) || 'signed_off' !== ( $signed['status'] ?? '' ) ) { $failures[] = 'Final human sign-off was not recorded.'; }
if ( 'draft' !== $GLOBALS['posts'][501]->post_status ) { $failures[] = 'Editorial sign-off published the WordPress draft.'; }
$signoff_meta = get_post_meta( 501, Ikon_SEO_Editorial_Review::META_SIGNOFF, true );
if ( ! is_array( $signoff_meta ) || ! array_key_exists( 'publishes_automatically', $signoff_meta ) || false !== $signoff_meta['publishes_automatically'] ) { $failures[] = 'Sign-off metadata does not explicitly preserve manual publishing.'; }
$comparison = $reviewer->compare_versions( $review['id'] );
if ( is_wp_error( $comparison ) || empty( $comparison['available'] ) || empty( $comparison['summary']['content_changed'] ) ) { $failures[] = 'Revision comparison did not detect changed content.'; }

if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Editorial assignment, review rounds, revision checks and manual sign-off tests passed.\n";
