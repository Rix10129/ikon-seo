<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-platform-hardening.php';

$failures = array();
$reflection = new ReflectionClass( 'Ikon_SEO_Platform_Hardening' );
$instance = $reflection->newInstanceWithoutConstructor();

$safe_method = $reflection->getMethod( 'safe_settings' );
$safe_method->setAccessible( true );
$safe = $safe_method->invoke( $instance, array(
	'site_name' => 'Example',
	'token_hash' => 'secret-token',
	'gsc_client_secret' => 'secret-client',
	'pagespeed_api_key' => 'secret-api-key',
	'strategy_quality_gate_threshold' => 80,
	'nested' => array( 'password' => 'secret-password', 'mode' => 'safe' ),
) );
if ( isset( $safe['token_hash'] ) || isset( $safe['gsc_client_secret'] ) || isset( $safe['pagespeed_api_key'] ) ) { $failures[] = 'Credential-like top-level settings were not excluded.'; }
if ( isset( $safe['nested']['password'] ) || 'safe' !== ( $safe['nested']['mode'] ?? '' ) ) { $failures[] = 'Nested secret redaction failed.'; }
if ( 80 !== ( $safe['strategy_quality_gate_threshold'] ?? null ) ) { $failures[] = 'Safe operational configuration was not retained.'; }

$gate_method = $reflection->getMethod( 'readiness_gate' );
$gate_method->setAccessible( true );
$ready = $gate_method->invoke( $instance,
	array( 'status' => 'ready' ),
	array( 'overall_status' => 'verified' ),
	array( 'status' => 'compatible' ),
	array( 'status' => 'hardened' ),
	array( array( 'created_at' => gmdate( 'Y-m-d H:i:s' ) ) )
);
if ( 'ready' !== ( $ready['status'] ?? '' ) ) { $failures[] = 'A clean platform did not reach ready state.'; }
$blocked = $gate_method->invoke( $instance,
	array( 'status' => 'critical' ),
	array( 'overall_status' => 'failed' ),
	array( 'status' => 'critical' ),
	array( 'status' => 'critical' ),
	array()
);
if ( 'blocked' !== ( $blocked['status'] ?? '' ) || count( $blocked['blocks'] ?? array() ) < 4 ) { $failures[] = 'Critical platform evidence did not block release readiness.'; }

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-ikon-seo-platform-hardening.php' );
foreach ( array( 'wp_publish_post', 'wp_update_post', 'wp_delete_post', 'wp_mail', "'post_status' => 'publish'" ) as $needle ) {
	if ( false !== strpos( $source, $needle ) ) { $failures[] = 'Platform hardening contains prohibited primitive: ' . $needle; }
}
foreach ( array( 'restore_requires_exact_payload_hash', 'credentials_are_excluded_from_archives', 'automatic_rollback', 'live_content_changes' ) as $needle ) {
	if ( false === strpos( $source, $needle ) ) { $failures[] = 'Platform safeguard is missing: ' . $needle; }
}

if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Platform hardening secret-redaction, readiness-gate and no-live-change tests passed.\n";
