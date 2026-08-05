<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'KB_IN_BYTES', 1024 );
define( 'HOUR_IN_SECONDS', 3600 );
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function __( $value, ...$args ) { return $value; }
function wp_check_password( $provided, $hash ) { return hash_equals( (string) $hash, (string) $provided ); }
$GLOBALS['transients'] = array();
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][ $key ] = $value; return true; }

class WP_Error {
	private $code;
	public function __construct( $code, $message, $data = array() ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class WP_REST_Request {
	private $payload;
	public function __construct( $payload ) { $this->payload = $payload; }
	public function get_json_params() { return $this->payload; }
	public function get_header( $name ) { return 'x-ikon-seo-key' === strtolower( $name ) ? 'secret-key' : ''; }
	public function get_body() { return json_encode( $this->payload ); }
}
class Ikon_SEO_Plugin {
	public static function settings() { return $GLOBALS['settings']; }
}
class Ikon_SEO_Connection { public $seen = 0; public function mark_seen() { $this->seen++; } }

require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-auth.php';

$GLOBALS['settings'] = array(
	'remote_actions' => 1,
	'token_hash' => 'secret-key',
	'key_scopes' => array( 'read', 'draft' ),
	'max_payload_kb' => 128,
	'rate_limit' => 60,
);
$connection = new Ikon_SEO_Connection();
$auth = new Ikon_SEO_Auth( $connection );
$failures = array();

$draft = $auth->can_publishing_readiness( new WP_REST_Request( array( 'command' => 'create_release' ) ) );
if ( true !== $draft ) { $failures[] = 'Draft scope did not allow release preparation.'; }
$approval_denied = $auth->can_publishing_readiness( new WP_REST_Request( array( 'command' => 'mark_ready' ) ) );
if ( ! is_wp_error( $approval_denied ) || 'ikon_seo_scope_denied' !== $approval_denied->get_error_code() ) { $failures[] = 'Draft-only key was allowed to approve publishing readiness.'; }
$GLOBALS['settings']['key_scopes'][] = 'approve';
$approval = $auth->can_publishing_readiness( new WP_REST_Request( array( 'command' => 'mark_ready' ) ) );
if ( true !== $approval ) { $failures[] = 'Approve scope did not allow publishing-readiness approval.'; }
$verification = $auth->can_publishing_readiness( new WP_REST_Request( array( 'command' => 'verify_launch' ) ) );
if ( true !== $verification ) { $failures[] = 'Draft scope did not allow read-only launch verification.'; }
$impact_draft = $auth->can_search_impact( new WP_REST_Request( array( 'command' => 'create_study' ) ) );
if ( true !== $impact_draft ) { $failures[] = 'Draft scope did not allow Search Impact study preparation.'; }
$impact_assess = $auth->can_search_impact( new WP_REST_Request( array( 'command' => 'assess' ) ) );
if ( true !== $impact_assess ) { $failures[] = 'Approve scope did not allow Search Impact assessment.'; }
if ( $connection->seen < 5 ) { $failures[] = 'Authenticated workspace activity was not recorded.'; }

if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Publishing Readiness draft/approve connection-scope tests passed.\n";
