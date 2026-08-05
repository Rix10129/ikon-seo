<?php
$root = dirname( __DIR__ );
$failures = array();
$main = file_get_contents( $root . '/ikon-seo.php' );
$plugin = file_get_contents( $root . '/includes/class-ikon-seo-plugin.php' );
$rest = file_get_contents( $root . '/includes/class-ikon-seo-rest.php' );
$admin = file_get_contents( $root . '/includes/class-ikon-seo-admin.php' );
$auth = file_get_contents( $root . '/includes/class-ikon-seo-auth.php' );
$command = file_get_contents( $root . '/includes/class-ikon-seo-executive-command-centre.php' );
$agent = file_get_contents( $root . '/includes/class-ikon-seo-agency-command-centre.php' );

if ( false === strpos( $main, 'Version: 2.0.1' ) || false === strpos( $main, "define( 'IKON_SEO_VERSION', '2.0.1' )" ) ) { $failures[] = 'Plugin version is not 2.0.1.'; }
if ( false === strpos( $main, 'class-ikon-seo-executive-command-centre.php' ) ) { $failures[] = 'Executive Command Centre class is not loaded.'; }
if ( false === strpos( $plugin, "const DB_VERSION = '40.0'" ) ) { $failures[] = 'Database component version is not 35.0.'; }
foreach ( array( 'ikon_seo_command_risks', 'ikon_seo_command_notifications', 'Ikon_SEO_Executive_Command_Centre::CRON_HOOK', 'new Ikon_SEO_Executive_Command_Centre' ) as $needle ) {
	if ( false === strpos( $plugin, $needle ) ) { $failures[] = 'Executive database, cron or constructor integration missing: ' . $needle; }
}
foreach ( array( '/agency-command-centre', 'agency_command_sync', 'can_executive_command', 'AgencyCommandSync' ) as $needle ) {
	if ( false === strpos( $rest, $needle ) && false === strpos( $auth, $needle ) ) { $failures[] = 'Executive REST or scope integration missing: ' . $needle; }
}
foreach ( array( 'assign_risk', 'resolve_risk', 'reopen_risk', 'acknowledge_notification', 'dismiss_notification' ) as $needle ) {
	if ( false === strpos( $auth, $needle ) ) { $failures[] = 'Executive approval-scope routing missing: ' . $needle; }
}
foreach ( array( 'Executive portfolio overview', 'Portfolio filters', 'Executive portfolio analytics', 'Unified approval inbox', 'Portfolio risk register', 'Internal notifications', 'Capacity forecast' ) as $needle ) {
	if ( false === strpos( $admin, $needle ) ) { $failures[] = 'Executive command UI integration missing: ' . $needle; }
}
foreach ( array( 'operations', 'extended_operations_snapshot', 'approval_items' ) as $needle ) {
	if ( false === strpos( $agent, $needle ) ) { $failures[] = 'Managed-site snapshot was not extended for newer workflow modules: ' . $needle; }
}
foreach ( array( 'remote_monitoring_only', 'approvals_remain_local', 'client_portal_preview', 'not a ranking score', 'automatic_reassignment' ) as $needle ) {
	if ( false === stripos( $command, $needle ) ) { $failures[] = 'Executive governance safeguard missing: ' . $needle; }
}
foreach ( array( 'wp_publish_post', 'wp_update_post', 'wp_delete_post', 'wp_mail', "'post_status' => 'publish'" ) as $needle ) {
	if ( false !== strpos( $command, $needle ) ) { $failures[] = 'Executive command engine contains a prohibited primitive: ' . $needle; }
}
$openapi = json_decode( file_get_contents( $root . '/openapi/ikon-seo-openapi.json' ), true );
if ( ! is_array( $openapi ) ) { $failures[] = 'Static OpenAPI JSON is invalid.'; }
else {
	$operations = 0; $ids = array();
	foreach ( (array) ( $openapi['paths'] ?? array() ) as $path ) { foreach ( $path as $method => $operation ) { if ( in_array( $method, array( 'get','post','put','patch','delete' ), true ) ) { $operations++; $ids[] = $operation['operationId'] ?? ''; } } }
	if ( 30 !== $operations || count( $ids ) !== count( array_unique( $ids ) ) ) { $failures[] = 'Static OpenAPI must contain exactly 30 unique operations.'; }
	if ( empty( $openapi['paths']['/agency-command-centre']['post'] ) || empty( $openapi['components']['schemas']['AgencyCommandSync'] ) ) { $failures[] = 'Executive Command Centre OpenAPI contract is missing.'; }
	$commands = $openapi['components']['schemas']['AgencyCommandSync']['properties']['command']['enum'] ?? array();
	foreach ( array( 'refresh_portfolio', 'assign_risk', 'resolve_risk', 'acknowledge_notification', 'client_portal_preview' ) as $required ) { if ( ! in_array( $required, $commands, true ) ) { $failures[] = 'Executive OpenAPI command missing: ' . $required; } }
	if ( '2.0.1' !== ( $openapi['info']['version'] ?? '' ) ) { $failures[] = 'Static OpenAPI version is not 2.0.1.'; }
}
foreach ( array( 'docs/AGENCY-COMMAND-CENTRE-EXECUTIVE-ANALYTICS.md', 'docs/UPGRADE-v1.16.md' ) as $relative ) {
	if ( ! file_exists( $root . '/' . $relative ) ) { $failures[] = 'Release documentation missing: ' . $relative; }
}
if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Ikon SEO v2.0.1 Executive Command Centre integration, governance and focused-schema tests passed.\n";
