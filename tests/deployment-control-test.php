<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'IKON_SEO_DIR', dirname( __DIR__ ) . '/' );
define( 'IKON_SEO_VERSION', '1.19.0' );
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( is_scalar( $value ) ? (string) $value : '' ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( is_scalar( $value ) ? (string) $value : '' ) ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function home_url() { return 'https://client.example/'; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function get_option( $key, $default = false ) { return 'ikon_seo_installation_id' === $key ? 'installation-123' : $default; }
function trailingslashit( $value ) { return rtrim( $value, '/\\' ) . '/'; }
function __( $value ) { return $value; }
class WP_Error { private $code; private $message; public function __construct($c,$m){$this->code=$c;$this->message=$m;} public function get_error_code(){return $this->code;} public function get_error_message(){return $this->message;} }
class Ikon_SEO_Plugin { const DB_VERSION='38.0'; public static function settings(){ return array('deployment_license_warning_days'=>30); } }
class Ikon_SEO_Platform_Hardening {}
class Ikon_SEO_Production_Health {}
class Ikon_SEO_Workspace_History {}
class Ikon_SEO_Logger {}
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-deployment-control.php';

$reflection = new ReflectionClass( 'Ikon_SEO_Deployment_Control' );
$engine = $reflection->newInstanceWithoutConstructor();
$failures = array();

if ( 'stable' !== $engine->normalize_channel( 'candidate', 'production' ) ) { $failures[] = 'Production did not force the stable update channel.'; }
if ( 'candidate' !== $engine->normalize_channel( 'candidate', 'staging' ) ) { $failures[] = 'Staging candidate channel was not retained.'; }
if ( 'production' !== $engine->normalize_environment( 'unknown' ) ) { $failures[] = 'Unknown environment was not normalised safely.'; }

$payload = $engine->normalize_entitlement_payload( array(
    'license_id' => 'LIC-100', 'organisation' => 'Example Agency', 'edition' => 'agency',
    'site_fingerprint' => $engine->site_fingerprint(), 'max_sites' => 1,
    'features' => array('managed_updates','core','credentials'),
    'environment_scope' => array('production','staging'),
    'issued_at' => '2026-08-01 00:00:00', 'not_before' => '2026-08-01 00:00:00', 'expires_at' => '2027-08-01 00:00:00',
) );
if ( in_array( 'credentials', $payload['features'], true ) || ! in_array( 'managed_updates', $payload['features'], true ) ) { $failures[] = 'Entitlement feature allowlist failed.'; }
if ( $engine->entitlement_fingerprint( $payload ) !== $engine->entitlement_fingerprint( array_reverse( $payload, true ) ) ) { $failures[] = 'Entitlement fingerprint is not deterministic.'; }

if ( function_exists( 'openssl_pkey_new' ) ) {
    $key = openssl_pkey_new( array( 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA ) );
    openssl_pkey_export( $key, $private );
    $details = openssl_pkey_get_details( $key );
    $method = $reflection->getMethod( 'canonical_json' ); $method->setAccessible( true );
    $canonical = $method->invoke( $engine, $payload );
    openssl_sign( $canonical, $signature, $private, OPENSSL_ALGO_SHA256 );
    $verified = $engine->verify_entitlement_envelope( array( 'payload' => $payload, 'signature' => base64_encode( $signature ) ), $details['key'] );
    if ( $verified instanceof WP_Error || 'verified' !== ( $verified['signature_state'] ?? '' ) ) { $failures[] = 'Valid signed entitlement did not verify.'; }
    $tampered = $payload; $tampered['organisation'] = 'Tampered';
    $invalid = $engine->verify_entitlement_envelope( array( 'payload' => $tampered, 'signature' => base64_encode( $signature ) ), $details['key'] );
    if ( ! ( $invalid instanceof WP_Error ) ) { $failures[] = 'Tampered entitlement signature was accepted.'; }
}

$now = strtotime( '2026-08-05 00:00:00 UTC' );
$active = array( 'status'=>'active','not_before'=>'2026-08-01 00:00:00','expires_at'=>'2027-08-01 00:00:00','environment_scope'=>array('production') );
$expiring = $active; $expiring['expires_at']='2026-08-20 00:00:00';
$expired = $active; $expired['expires_at']='2026-08-04 00:00:00';
if ( 'active' !== $engine->license_state( $active, $now ) || 'expiring' !== $engine->license_state( $expiring, $now ) || 'expired' !== $engine->license_state( $expired, $now ) ) { $failures[] = 'Entitlement lifecycle states are incorrect.'; }

$release = $engine->normalize_release_metadata( array(
    'release_id'=>'ikon-seo-1.20.0','version'=>'1.20.0','database_version'=>'39.0','channel'=>'stable','environment'=>'production',
    'package_sha256'=>str_repeat('a',64),'manifest_sha256'=>str_repeat('b',64),'published_at'=>'2026-08-05 00:00:00'
) );
$platform = array( 'readiness'=>array('status'=>'ready') );
$recovery = array( 'id'=>3, 'payload_hash'=>str_repeat('c',64) );
$gate = $engine->readiness_gate( $release, $active, $platform, $recovery, 'production', '1.19.0' );
if ( 'ready' !== $gate['status'] || false !== $gate['automatic_installation'] || false !== $gate['automatic_rollback'] ) { $failures[] = 'Clean deployment readiness gate failed or safety flags are incorrect.'; }
$unhashed = $release; $unhashed['package_sha256']=str_repeat('0',64);
$blocked = $engine->readiness_gate( $unhashed, $active, $platform, $recovery, 'production', '1.19.0' );
if ( 'blocked' !== $blocked['status'] ) { $failures[] = 'Release without a real ZIP hash was not blocked.'; }
$candidate = $release; $candidate['channel']='candidate';
if ( 'blocked' !== $engine->readiness_gate( $candidate, $active, $platform, $recovery, 'production', '1.19.0' )['status'] ) { $failures[] = 'Candidate release was allowed on production.'; }

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-ikon-seo-deployment-control.php' );
foreach ( array( 'Plugin_Upgrader', 'WP_Upgrader', 'download_url(', 'wp_update_plugins(', 'activate_plugin(', 'deactivate_plugins(', 'wp_publish_post', 'wp_update_post', 'wp_delete_post', 'wp_mail' ) as $needle ) {
    if ( false !== strpos( $source, $needle ) ) { $failures[] = 'Deployment Control contains prohibited automatic or live-change primitive: ' . $needle; }
}
foreach ( array( 'license_expiry_disables_public_site', 'license_expiry_deletes_data', 'manual_wordpress_update_required', 'automatic_plugin_updates' ) as $needle ) {
    if ( false === strpos( $source, $needle ) ) { $failures[] = 'Deployment safety declaration missing: ' . $needle; }
}

if ( $failures ) { fwrite( STDERR, implode("\n",$failures)."\n" ); exit(1); }
echo "Deployment Control entitlement, signature, readiness and no-automatic-update tests passed.\n";
