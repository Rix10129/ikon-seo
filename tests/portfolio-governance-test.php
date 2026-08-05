<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function __( $value ) { return $value; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class WP_Error { public $code; public $message; public function __construct( $code = '', $message = '' ) { $this->code=$code; $this->message=$message; } }
class Ikon_SEO_Agency_Command_Centre {}
class Ikon_SEO_Crypto {}
class Ikon_SEO_Workspace_History {}
class Ikon_SEO_Logger {}
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-portfolio-governance.php';

$reflection = new ReflectionClass( 'Ikon_SEO_Portfolio_Governance' );
$engine = $reflection->newInstanceWithoutConstructor();
$invoke = static function( $name, array $args = array() ) use ( $reflection, $engine ) {
	$method = $reflection->getMethod( $name );
	$method->setAccessible( true );
	return $method->invokeArgs( $engine, $args );
};
$failures = array();

$policy = $invoke( 'normalize_policy', array( array(
	'minimum_strategy_readiness' => 12,
	'max_safe_batch' => 99,
	'manual_publish_only' => false,
	'pattern_use' => 'automatic',
	'portfolio_evidence' => 'full_content',
	'external_live_writes' => 'enabled',
	'allowed_evidence_sources' => array( 'search_console', 'analytics', 'unknown_source' ),
) ) );
$rules = $policy['rules'] ?? array();
if ( 70 !== ( $rules['minimum_strategy_readiness'] ?? null ) ) { $failures[] = 'Minimum strategy readiness can fall below 70.'; }
if ( 5 !== ( $rules['max_safe_batch'] ?? null ) ) { $failures[] = 'Safe batch is not capped at five.'; }
if ( true !== ( $rules['manual_publish_only'] ?? null ) ) { $failures[] = 'Manual publishing lock can be weakened.'; }
if ( 'advisory_only' !== ( $rules['pattern_use'] ?? null ) ) { $failures[] = 'Pattern Library advisory lock can be weakened.'; }
if ( 'anonymised_only' !== ( $rules['portfolio_evidence'] ?? null ) ) { $failures[] = 'Portfolio privacy lock can be weakened.'; }
if ( 'disabled' !== ( $rules['external_live_writes'] ?? null ) ) { $failures[] = 'External live-write lock can be weakened.'; }
if ( in_array( 'unknown_source', (array) ( $rules['allowed_evidence_sources'] ?? array() ), true ) ) { $failures[] = 'Unknown evidence sources were retained.'; }

$ordered_a = array( 'minimum_strategy_readiness' => 82, 'max_safe_batch' => 2, 'allowed_evidence_sources' => array( 'analytics', 'search_console' ) );
$ordered_b = array( 'allowed_evidence_sources' => array( 'analytics', 'search_console' ), 'max_safe_batch' => 2, 'minimum_strategy_readiness' => 82 );
$policy_a = $invoke( 'normalize_policy', array( $ordered_a ) );
$policy_b = $invoke( 'normalize_policy', array( $ordered_b ) );
$fingerprint_a = $invoke( 'policy_fingerprint', array( 'agency-standard', 3, $policy_a ) );
$fingerprint_b = $invoke( 'policy_fingerprint', array( 'agency-standard', 3, $policy_b ) );
if ( ! hash_equals( $fingerprint_a, $fingerprint_b ) ) { $failures[] = 'Equivalent policy input does not produce a stable fingerprint.'; }

$envelope = array(
	'schema' => Ikon_SEO_Portfolio_Governance::ENVELOPE_SCHEMA,
	'source_fingerprint' => str_repeat( 'a', 64 ),
	'source_label' => 'Ikon SEO Agency',
	'policy_key' => 'agency-standard',
	'policy_name' => 'Agency Standard',
	'policy_version' => 3,
	'policy_fingerprint' => $fingerprint_a,
	'policy' => $policy_a,
);
$normalized = $invoke( 'normalize_envelope', array( $envelope ) );
if ( is_wp_error( $normalized ) || ( $normalized['policy_version'] ?? 0 ) !== 3 ) { $failures[] = 'A valid integrity-checked proposal was rejected.'; }
$envelope['policy']['rules']['max_safe_batch'] = 4;
if ( ! is_wp_error( $invoke( 'normalize_envelope', array( $envelope ) ) ) ) { $failures[] = 'A modified proposal with an old fingerprint was accepted.'; }

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-ikon-seo-portfolio-governance.php' );
foreach ( array( 'proposal_only', 'pending_local_approval', 'local_administrator_only', 'publishes_automatically', 'external_live_writes', 'wp_safe_remote_post' ) as $needle ) {
	if ( false === strpos( $source, $needle ) ) { $failures[] = 'Governance safeguard missing: ' . $needle; }
}
foreach ( array( 'wp_publish_post', 'wp_update_post', 'wp_delete_post', "'post_status' => 'publish'" ) as $needle ) {
	if ( false !== strpos( $source, $needle ) ) { $failures[] = 'Portfolio Governance contains a prohibited live-change primitive: ' . $needle; }
}

if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Portfolio Governance policy locks, proposal integrity and no-live-change tests passed.\n";
