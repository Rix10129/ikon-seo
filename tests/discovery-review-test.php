<?php

$GLOBALS['ikon_options'] = array();
function get_option( $key, $default = array() ) { return $GLOBALS['ikon_options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = false ) { $GLOBALS['ikon_options'][ $key ] = $value; return true; }
function sanitize_text_field( $value ) { return trim( preg_replace( '/\s+/', ' ', strip_tags( (string) $value ) ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function current_time( $type, $gmt = false ) { return '2026-08-04 06:30:00'; }
function __( $value, $domain = null ) { return $value; }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }

class WP_Error {
	private $code;
	private $message;
	private $data;
	public function __construct( $code, $message, $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
class Ikon_SEO_Plugin {
	const OPTION_KEY = 'ikon_seo_settings';
	public static function settings() { return get_option( self::OPTION_KEY, array() ); }
}
class Ikon_SEO_Auto_Discovery {
	public $data;
	public function report() { return $this->data; }
}
class Ikon_SEO_Profile {
	public function get() { return get_option( Ikon_SEO_Plugin::OPTION_KEY, array() ); }
	public function sanitize( $input, $current ) { return array_merge( $current, $input ); }
	public function fingerprint( $settings ) { return hash( 'sha256', json_encode( array( $settings['site_name'] ?? '', $settings['default_language'] ?? '' ) ) ); }
}
class Ikon_SEO_Strategy {
	public $saved = array();
	public function get() { return $this->saved; }
	public function save( $input, $user_id, $source ) { $this->saved = array_merge( $this->saved, $input ); return $this->saved; }
}
class Ikon_SEO_Inventory { public function clear_cache() {} }
class Ikon_SEO_Local { public function rebind_profile( $old, $new ) {} }
class Ikon_SEO_Workspace_History { public function add( $event, $source = '', $user_id = 0 ) {} }
class Ikon_SEO_Logger {}

define( 'ABSPATH', __DIR__ );
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-discovery-review.php';

function assert_true( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	echo "PASS: {$message}\n";
}

$discovery = new Ikon_SEO_Auto_Discovery();
$discovery->data = array(
	'generated_at' => 'scan-1',
	'facts' => array(
		array( 'id' => 'profile.site_name', 'label' => 'Site name', 'group' => 'profile', 'field' => 'site_name', 'value' => 'Example Co', 'display_value' => 'Example Co', 'confidence' => 'high', 'score' => 98, 'sources' => array( 'WordPress' ), 'needs_confirmation' => false ),
		array( 'id' => 'strategy.strategy_target_audience', 'label' => 'Audience', 'group' => 'strategy', 'field' => 'strategy_target_audience', 'value' => 'Homeowners', 'display_value' => 'Homeowners', 'confidence' => 'medium', 'score' => 65, 'sources' => array( 'Website copy' ), 'needs_confirmation' => true ),
	),
	'conflicts' => array(
		array( 'area' => 'Phone', 'message' => 'Two numbers found', 'values' => array( '+974 111', '+974 222' ) ),
	),
	'application' => array(),
);
$profile = new Ikon_SEO_Profile();
$strategy = new Ikon_SEO_Strategy();
$review = new Ikon_SEO_Discovery_Review( $discovery, $profile, $strategy, new Ikon_SEO_Inventory(), new Ikon_SEO_Local(), new Ikon_SEO_Workspace_History(), new Ikon_SEO_Logger() );

$report = $review->report();
assert_true( 1 === $report['counts']['detected'], 'high-confidence technical fact starts as detected' );
assert_true( 1 === $report['counts']['needs_confirmation'], 'business inference starts as needs confirmation' );
assert_true( 1 === $report['unresolved_conflicts'], 'conflict starts unresolved' );

$bulk = $review->accept_high_confidence( 7, 'scan-1' );
assert_true( ! is_wp_error( $bulk ) && in_array( 'profile.site_name', $bulk['accepted'], true ), 'safe bulk action accepts high-confidence non-sensitive facts' );

$result = $review->update_fact( 'strategy.strategy_target_audience', 'confirmed', null, 7, 'scan-1' );
assert_true( ! is_wp_error( $result ) && 2 === $result['counts']['confirmed'], 'fact can be confirmed with optimistic locking' );
$conflict_id = $result['conflicts'][0]['id'];
$result = $review->resolve_conflict( $conflict_id, '+974 111', '', 7, 'scan-1' );
assert_true( ! is_wp_error( $result ) && 0 === $result['unresolved_conflicts'], 'detected conflict can be resolved' );
$old = $discovery->data;
$discovery->data = $old;
$discovery->data['generated_at'] = 'scan-2';
$discovery->data['facts'][1]['value'] = 'Homeowners and office managers';
$discovery->data['facts'][1]['display_value'] = 'Homeowners and office managers';
$discovery->data['conflicts'] = array();
$review->reconcile( $discovery->data, $old );
$report = $review->report();
$audience = array_values( array_filter( $report['facts'], function( $fact ) { return 'strategy.strategy_target_audience' === $fact['id']; } ) )[0];
assert_true( 'outdated' === $audience['status'], 'changed confirmed evidence is marked outdated after rescan' );
assert_true( 'Homeowners' === $audience['approved_value'], 'previously approved value is preserved after rescan' );
assert_true( 1 === $report['rescan']['changed_facts'], 'rescan comparison counts changed facts' );

$stale = $review->update_fact( 'strategy.strategy_target_audience', 'confirmed', null, 7, 'scan-1' );
assert_true( is_wp_error( $stale ) && 'ikon_seo_fact_stale' === $stale->get_error_code(), 'stale workspace decision is rejected' );

$result = $review->update_fact( 'strategy.strategy_target_audience', 'edited', "Homeowners\nOffice managers", 7, 'scan-2' );
assert_true( ! is_wp_error( $result ) && true === $result['ready'], 'edited value clears the outdated gate' );
$apply = $review->apply_confirmed( 7 );
assert_true( ! is_wp_error( $apply ) && 2 === count( $apply['applied'] ), 'confirmed and edited values apply to profile and strategy' );
assert_true( 'Example Co' === get_option( Ikon_SEO_Plugin::OPTION_KEY, array() )['site_name'], 'profile value was applied' );
assert_true( false !== strpos( $strategy->saved['strategy_target_audience'], 'Office managers' ), 'edited strategy value was applied' );

echo "All discovery review tests passed.\n";
