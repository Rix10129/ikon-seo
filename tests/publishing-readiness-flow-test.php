<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'IKON_SEO_VERSION', '1.11.0' );

function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '_', (string) $value ) ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_title( $value ) { $value = strtolower( trim( strip_tags( (string) $value ) ) ); return trim( preg_replace( '/[^a-z0-9]+/', '-', $value ), '-' ); }
function esc_url_raw( $value ) { return filter_var( (string) $value, FILTER_SANITIZE_URL ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function remove_accents( $value ) { return (string) $value; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function __( $value, ...$args ) { return $value; }
function current_time( $type, $gmt = false ) { return '2026-08-04 08:00:00'; }
function home_url( $path = '/' ) { return 'https://example.com' . ( '/' === substr( $path, 0, 1 ) ? $path : '/' . $path ); }
function untrailingslashit( $value ) { return rtrim( (string) $value, '/\\' ); }
function wp_http_validate_url( $url ) { return (bool) filter_var( $url, FILTER_VALIDATE_URL ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function get_edit_post_link( $id, $context = '' ) { return 'https://example.com/wp-admin/post.php?post=' . absint( $id ); }
function get_preview_post_link( $id ) { return 'https://example.com/?p=' . absint( $id ) . '&preview=true'; }
function get_permalink( $id ) { $post = get_post( $id ); return $post ? 'https://example.com/' . sanitize_title( $post->post_name ?: $post->post_title ) . '/' : ''; }
function has_post_thumbnail( $id ) { return (bool) get_post_meta( $id, '_thumbnail_id', true ); }
function add_action( ...$args ) { return true; }
function get_current_user_id() { return 1; }

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }

$GLOBALS['options'] = array();
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][ $key ] = $value; return true; }

$GLOBALS['transients'] = array();
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][ $key ] = $value; return true; }
function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ] ); return true; }

$content_paragraph = 'Professional office cleaning supports healthier workplaces, clearer routines, and a more consistent experience for employees and visitors. Our team follows the confirmed service scope, communicates arrival details, and provides a practical quotation based on the property requirements. ';
$GLOBALS['posts'] = array(
	501 => (object) array(
		'ID' => 501,
		'post_status' => 'draft',
		'post_type' => 'page',
		'post_title' => 'Office Cleaning Doha',
		'post_name' => 'office-cleaning-doha',
		'post_excerpt' => 'Evidence-led office cleaning page for Doha.',
		'post_content' => '<h1>Office Cleaning Doha</h1>' . str_repeat( '<p>' . $content_paragraph . '</p>', 12 ) . '<p><a href="https://example.com/contact/">Contact us</a> or <a href="tel:+97455555555">call for a quote</a>.</p><form><input name="name"></form>',
		'post_date_gmt' => '0000-00-00 00:00:00',
	),
);
$GLOBALS['post_meta'] = array(
	501 => array(
		'rank_math_title' => 'Office Cleaning Doha | Example Cleaning',
		'rank_math_description' => 'Request professional office cleaning services in Doha from Example Cleaning.',
		'rank_math_canonical' => 'https://example.com/office-cleaning-doha/',
		'rank_math_robots' => array( 'index', 'follow' ),
		'_thumbnail_id' => 900,
	),
);
function get_post( $id ) { return $GLOBALS['posts'][ absint( $id ) ] ?? null; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['post_meta'][ absint( $id ) ][ $key ] = $value; return true; }
function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['post_meta'][ absint( $id ) ][ $key ] ?? ''; }

function user_can( $user_id, $capability ) {
	$user_id = absint( $user_id );
	if ( 1 === $user_id ) { return in_array( $capability, array( 'manage_options', 'publish_posts' ), true ); }
	return false;
}

$GLOBALS['http_body'] = '<!doctype html><html><head><title>Office Cleaning Doha | Example Cleaning</title><meta name="description" content="Request professional office cleaning services in Doha."><meta name="robots" content="index,follow"><link rel="canonical" href="https://example.com/office-cleaning-doha/"><script type="application/ld+json">{"@type":"Service"}</script><script>window.dataLayer = window.dataLayer || [];</script></head><body><h1>Office Cleaning Doha</h1><p>Published content.</p><a href="tel:+97455555555">Call us</a><form><input name="name"></form></body></html>';
function wp_safe_remote_get( $url, $args = array() ) { return array( 'response' => array( 'code' => 200 ), 'headers' => array( 'content-type' => 'text/html; charset=UTF-8' ), 'body' => $GLOBALS['http_body'] ); }
function wp_remote_retrieve_response_code( $response ) { return absint( $response['response']['code'] ?? 0 ); }
function wp_remote_retrieve_headers( $response ) { return $response['headers'] ?? array(); }
function wp_remote_retrieve_body( $response ) { return (string) ( $response['body'] ?? '' ); }

class FakeWpdb {
	public $prefix = 'wp_';
	public $insert_id = 0;
	public $tables = array();

	public function __construct() {
		foreach ( array( 'wp_ikon_seo_publishing_releases', 'wp_ikon_seo_publishing_checks', 'wp_ikon_seo_publishing_snapshots', 'wp_ikon_seo_publishing_events' ) as $table ) {
			$this->tables[ $table ] = array();
		}
	}
	public function esc_like( $value ) { return (string) $value; }
	public function prepare( $query, ...$args ) {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) { $args = $args[0]; }
		foreach ( $args as $arg ) {
			$replacement = is_int( $arg ) || ctype_digit( (string) $arg ) ? (string) (int) $arg : "'" . addslashes( (string) $arg ) . "'";
			$query = preg_replace( '/%[dfs]/', $replacement, $query, 1 );
		}
		return $query;
	}
	public function insert( $table, $row ) {
		if ( ! isset( $this->tables[ $table ] ) ) { $this->tables[ $table ] = array(); }
		$id = $this->tables[ $table ] ? max( array_keys( $this->tables[ $table ] ) ) + 1 : 1;
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
	private function table_from_query( $query ) { return preg_match( '/FROM\s+([a-z0-9_]+)/i', $query, $m ) ? $m[1] : ''; }
	private function filtered( $query ) {
		$table = $this->table_from_query( $query );
		$rows = array_values( $this->tables[ $table ] ?? array() );
		foreach ( array( 'id', 'release_id', 'review_id', 'brief_id', 'post_id' ) as $field ) {
			if ( preg_match( '/(?:WHERE|AND)\s+' . $field . '=(\d+)/i', $query, $m ) ) {
				$rows = array_values( array_filter( $rows, function( $row ) use ( $field, $m ) { return absint( $row[ $field ] ?? 0 ) === absint( $m[1] ); } ) );
			}
		}
		foreach ( array( 'status', 'phase', 'check_key', 'snapshot_type' ) as $field ) {
			if ( preg_match( "/(?:WHERE|AND)\\s+{$field}='([^']+)'/i", $query, $m ) ) {
				$rows = array_values( array_filter( $rows, function( $row ) use ( $field, $m ) { return (string) ( $row[ $field ] ?? '' ) === stripslashes( $m[1] ); } ) );
			}
		}
		return $rows;
	}
	public function get_var( $query ) {
		if ( preg_match( "/SHOW TABLES LIKE '([^']+)'/i", $query, $m ) ) { return stripslashes( $m[1] ); }
		$rows = $this->filtered( $query );
		if ( false !== stripos( $query, 'COUNT(*)' ) ) { return count( $rows ); }
		if ( preg_match( '/SELECT\s+id\s+FROM/i', $query ) ) { return $rows ? absint( $rows[0]['id'] ) : 0; }
		return 0;
	}
	public function get_row( $query, $output = ARRAY_A ) { $rows = $this->filtered( $query ); return $rows ? $rows[0] : null; }
	public function get_results( $query, $output = ARRAY_A ) {
		$rows = $this->filtered( $query );
		if ( false !== stripos( $query, 'ORDER BY id DESC' ) ) { usort( $rows, function( $a, $b ) { return absint( $b['id'] ) - absint( $a['id'] ); } ); }
		else { usort( $rows, function( $a, $b ) { return absint( $a['id'] ) - absint( $b['id'] ); } ); }
		if ( preg_match( '/LIMIT\s+(\d+)/i', $query, $m ) ) { $rows = array_slice( $rows, 0, absint( $m[1] ) ); }
		return $rows;
	}
}
$GLOBALS['wpdb'] = new FakeWpdb();

function normalized_release_hash( $post_id ) {
	$post = get_post( $post_id );
	$normalize = function( $value ) {
		$value = strtolower( strip_tags( (string) $value ) );
		return trim( preg_replace( '/\s+/', ' ', preg_replace( '/[^a-z0-9\s]+/', ' ', $value ) ) );
	};
	$builder = get_post_meta( $post_id, '_elementor_data', true );
	if ( is_array( $builder ) || is_object( $builder ) ) { $builder = json_encode( $builder ); }
	return hash( 'sha256', $normalize( $post->post_title ) . '|' . $normalize( $post->post_excerpt ) . '|' . $normalize( $post->post_content ) . '|' . $normalize( (string) $builder ) );
}

class Ikon_SEO_Editorial_Review {
	public $reviews = array();
	public function review( $id ) { return $this->reviews[ absint( $id ) ] ?? array(); }
	public function report( $args = array(), $refresh = false ) { return array( 'reviews' => array_values( $this->reviews ) ); }
}
class Ikon_SEO_Content_Workbench {
	public $briefs = array();
	public function brief( $id ) { return $this->briefs[ absint( $id ) ] ?? array(); }
}
class Ikon_SEO_Workspace_History { public $events = array(); public function add( $data, $source, $user ) { $this->events[] = array( $data, $source, $user ); } }
class Ikon_SEO_Logger { public $events = array(); public function log( ...$args ) { $this->events[] = $args; } }

require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-publishing-readiness.php';

$editorial = new Ikon_SEO_Editorial_Review();
$workbench = new Ikon_SEO_Content_Workbench();
$history = new Ikon_SEO_Workspace_History();
$logger = new Ikon_SEO_Logger();
$editorial->reviews[20] = array(
	'id' => 20,
	'brief_id' => 10,
	'draft_post_id' => 501,
	'status' => 'signed_off',
	'last_snapshot_hash' => normalized_release_hash( 501 ),
	'draft_changed_after_snapshot' => false,
);
$workbench->briefs[10] = array( 'id' => 10, 'post_id' => 0, 'status' => 'ready' );
$publishing = new Ikon_SEO_Publishing_Readiness( $editorial, $workbench, $history, $logger );
$failures = array();

$unauthorized = $publishing->create_release( 20, array(), 7 );
if ( ! is_wp_error( $unauthorized ) || 'ikon_seo_publishing_manage' !== $unauthorized->get_error_code() ) { $failures[] = 'A non-publisher was allowed to create a release candidate.'; }

$external_target = $publishing->create_release( 20, array( 'target_url' => 'https://external.example.net/office-cleaning/' ), 1 );
if ( ! is_wp_error( $external_target ) || 'ikon_seo_publishing_target_url' !== $external_target->get_error_code() ) { $failures[] = 'An external release target was accepted.'; }
$release = $publishing->create_release( 20, array(), 1 );
if ( is_wp_error( $release ) || 'candidate' !== ( $release['status'] ?? '' ) ) { $failures[] = 'A signed-off draft did not create a release candidate.'; }
if ( 'draft' !== $GLOBALS['posts'][501]->post_status ) { $failures[] = 'Release creation published the controlled draft.'; }
$duplicate = $publishing->create_release( 20, array(), 1 );
if ( ! is_wp_error( $duplicate ) || 'ikon_seo_publishing_exists' !== $duplicate->get_error_code() ) { $failures[] = 'Duplicate release creation was not blocked.'; }

$original_content = $GLOBALS['posts'][501]->post_content;
$GLOBALS['posts'][501]->post_content .= '<p>Post-sign-off change.</p>';
$stale = $publishing->run_preflight( $release['id'], 1 );
if ( ! is_wp_error( $stale ) || 'ikon_seo_publishing_release_changed' !== $stale->get_error_code() ) { $failures[] = 'A changed controlled draft did not invalidate publishing preflight.'; }
$GLOBALS['posts'][501]->post_content = $original_content;

$preflight = $publishing->run_preflight( $release['id'], 1 );
if ( is_wp_error( $preflight ) || 'preflight_passed' !== ( $preflight['status'] ?? '' ) || ! empty( $preflight['blocker_count'] ) ) { $failures[] = 'A clean release candidate did not pass preflight.'; }
$GLOBALS['posts'][501]->post_status = 'future';
$scheduled_ready = $publishing->mark_ready( $release['id'], 'Should not approve a scheduled item.', 1 );
if ( ! is_wp_error( $scheduled_ready ) || 'ikon_seo_publishing_ready_post_status' !== $scheduled_ready->get_error_code() ) { $failures[] = 'A scheduled item was allowed to pass manual-publishing readiness.'; }
$GLOBALS['posts'][501]->post_status = 'draft';
$ready = $publishing->mark_ready( $release['id'], 'Approved for a separate manual WordPress decision.', 1 );
if ( is_wp_error( $ready ) || 'ready_for_manual_publish' !== ( $ready['status'] ?? '' ) ) { $failures[] = 'A successful current preflight could not be approved as ready.'; }
if ( 'draft' !== $GLOBALS['posts'][501]->post_status ) { $failures[] = 'Readiness approval published the WordPress draft.'; }
$meta = get_post_meta( 501, Ikon_SEO_Publishing_Readiness::META_READINESS, true );
if ( ! is_array( $meta ) || false !== ( $meta['publishes_automatically'] ?? null ) || empty( $meta['requires_manual_wordpress_action'] ) ) { $failures[] = 'Readiness metadata does not explicitly require manual publication.'; }

$too_early = $publishing->record_manual_publication( $release['id'], 501, '', 1 );
if ( ! is_wp_error( $too_early ) || 'ikon_seo_publishing_not_public' !== $too_early->get_error_code() ) { $failures[] = 'Publication was recorded before the WordPress post was actually public.'; }
$GLOBALS['posts'][501]->post_status = 'publish';
$GLOBALS['posts'][501]->post_date_gmt = '2026-08-04 08:00:00';
$external_live = $publishing->record_manual_publication( $release['id'], 501, 'https://external.example.net/live/', 1 );
if ( ! is_wp_error( $external_live ) || 'ikon_seo_publishing_live_url' !== $external_live->get_error_code() ) { $failures[] = 'An external public verification URL was accepted.'; }
$recorded = $publishing->record_manual_publication( $release['id'], 501, '', 1 );
if ( is_wp_error( $recorded ) || 'publication_detected' !== ( $recorded['status'] ?? '' ) ) { $failures[] = 'A real manual WordPress publication was not recorded.'; }

$first_verify = $publishing->verify_launch( $release['id'], 1, true );
if ( is_wp_error( $first_verify ) || 'monitoring' !== ( $first_verify['status'] ?? '' ) || ! empty( $first_verify['blocker_count'] ) ) { $failures[] = 'A healthy public launch did not enter monitoring.'; }
$early_complete = $publishing->complete_monitoring( $release['id'], '', 1 );
if ( ! is_wp_error( $early_complete ) || 'ikon_seo_publishing_monitoring_window' !== $early_complete->get_error_code() ) { $failures[] = 'Monitoring closed before all four verification checkpoints.'; }

for ( $i = 0; $i < 3; $i++ ) {
	$result = $publishing->verify_launch( $release['id'], 1, true );
	if ( is_wp_error( $result ) ) { $failures[] = 'A post-launch verification checkpoint failed unexpectedly.'; break; }
}
$completed = $publishing->complete_monitoring( $release['id'], 'All controlled checkpoints reviewed.', 1 );
if ( is_wp_error( $completed ) || 'completed' !== ( $completed['status'] ?? '' ) ) { $failures[] = 'Monitoring did not complete after four successful checkpoints.'; }
$comparison = $publishing->compare_snapshots( $release['id'] );
if ( empty( $comparison['available'] ) ) { $failures[] = 'Release snapshot comparison was not available.'; }
if ( 'publish' !== $GLOBALS['posts'][501]->post_status ) { $failures[] = 'Verification altered the manually selected WordPress status.'; }

if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Controlled publishing readiness, manual publication and four-checkpoint monitoring tests passed.\n";
