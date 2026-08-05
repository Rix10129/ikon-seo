<?php

define( 'ABSPATH', __DIR__ . '/' );

function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '_', (string) $value ) ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_title( $value ) { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $value ) ), '-' ); }
function esc_url_raw( $value ) { return filter_var( (string) $value, FILTER_VALIDATE_URL ) ? (string) $value : ''; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_kses_post( $value ) { return (string) $value; }
function __( $value ) { return $value; }

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }

require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-content-workbench.php';

$reflection = new ReflectionClass( 'Ikon_SEO_Content_Workbench' );
$workbench = $reflection->newInstanceWithoutConstructor();
$is_content = $reflection->getMethod( 'is_content_opportunity' );
$is_content->setAccessible( true );
$hash = $reflection->getMethod( 'opportunity_hash' );
$hash->setAccessible( true );
$validate = $reflection->getMethod( 'validate_page_payload' );
$validate->setAccessible( true );
$post_type = $reflection->getMethod( 'post_type_for_page_type' );
$post_type->setAccessible( true );

$failures = array();
$content_opportunity = array( 'id' => 10, 'status' => 'planned', 'category' => 'content_gap', 'type' => 'keyword_gap', 'keyword' => 'office cleaning doha' );
$technical_opportunity = array( 'id' => 11, 'status' => 'planned', 'category' => 'technical', 'type' => 'pagespeed', 'keyword' => 'office cleaning doha' );
if ( ! $is_content->invoke( $workbench, $content_opportunity ) ) { $failures[] = 'Content opportunity was not accepted.'; }
if ( $is_content->invoke( $workbench, $technical_opportunity ) ) { $failures[] = 'Technical opportunity entered the content workflow.'; }

$base = array(
	'id' => 10, 'status' => 'planned', 'type' => 'keyword_gap', 'category' => 'content_gap', 'primary_source' => 'search_console',
	'title' => 'Office cleaning page', 'summary' => 'Evidence summary', 'target_url' => 'https://example.com/office-cleaning/',
	'post_id' => 42, 'keyword' => 'office cleaning doha', 'intent' => 'commercial', 'priority' => 80, 'confidence' => 'high',
	'observed_at' => '2026-08-04', 'evidence' => array( 'impressions' => 1000 ), 'actions' => array( 'Improve the page.' ),
);
$hash_one = $hash->invoke( $workbench, $base );
$hash_two = $hash->invoke( $workbench, $base );
$changed = $base; $changed['evidence']['impressions'] = 1200;
$hash_changed = $hash->invoke( $workbench, $changed );
if ( 64 !== strlen( $hash_one ) || $hash_one !== $hash_two ) { $failures[] = 'Evidence hash is not deterministic.'; }
if ( $hash_one === $hash_changed ) { $failures[] = 'Evidence changes did not invalidate the hash.'; }

$sections = array();
for ( $i = 1; $i <= 45; $i++ ) { $sections[] = array( 'type' => 'content', 'heading' => 'Section ' . $i, 'content' => '<p>Draft content.</p>' ); }
$brief = array( 'brief' => array( 'suggested_slug' => 'office-cleaning-doha' ) );
$payload = $validate->invoke( $workbench, array( 'title' => 'Office Cleaning Doha', 'sections' => $sections, 'faq' => array_fill( 0, 35, array( 'question' => 'Q', 'answer' => 'A' ) ) ), $brief );
if ( is_wp_error( $payload ) ) { $failures[] = 'Valid controlled draft payload was rejected.'; }
if ( 40 !== count( $payload['sections'] ?? array() ) ) { $failures[] = 'Section bound was not enforced.'; }
if ( 30 !== count( $payload['faq'] ?? array() ) ) { $failures[] = 'FAQ bound was not enforced.'; }
$missing_title = $validate->invoke( $workbench, array( 'sections' => array( array( 'heading' => 'A', 'content' => 'B' ) ) ), $brief );
if ( ! is_wp_error( $missing_title ) || 'ikon_seo_content_title' !== $missing_title->get_error_code() ) { $failures[] = 'Missing draft title was not blocked.'; }
if ( 'post' !== $post_type->invoke( $workbench, 'article' ) || 'page' !== $post_type->invoke( $workbench, 'service' ) ) { $failures[] = 'Page-type to WordPress post-type mapping failed.'; }

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-ikon-seo-content-workbench.php' );
foreach ( array(
	"'post_status' => 'draft'" => 'Draft-only post status is missing.',
	'ikon_seo_content_draft_exists' => 'Duplicate controlled-draft gate is missing.',
	'$this->validate_evidence( $brief, $brief[\'evidence_hash\'] )' => 'Stale-evidence check is missing from later workflow stages.',
	'Revision draft — %s' => 'Separate revision-draft behavior is missing.',
) as $needle => $message ) {
	if ( false === strpos( $source, $needle ) ) { $failures[] = $message; }
}

if ( $failures ) {
	fwrite( STDERR, implode( "\n", $failures ) . "\n" );
	exit( 1 );
}

echo "Content Workbench evidence, bounds and draft-safety tests passed.\n";
