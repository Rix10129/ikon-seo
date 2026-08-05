<?php
$root = dirname( __DIR__ );
$failures = array();
$main = file_get_contents( $root . '/ikon-seo.php' );
$plugin = file_get_contents( $root . '/includes/class-ikon-seo-plugin.php' );
$rest = file_get_contents( $root . '/includes/class-ikon-seo-rest.php' );
$auth = file_get_contents( $root . '/includes/class-ikon-seo-auth.php' );
$health = file_get_contents( $root . '/includes/class-ikon-seo-production-health.php' );
$portal = file_get_contents( $root . '/includes/class-ikon-seo-client-portal.php' );

if ( false === strpos( $main, 'Version: 2.0.1' ) || false === strpos( $main, "define( 'IKON_SEO_VERSION', '2.0.1' )" ) ) { $failures[] = 'Plugin version is not 2.0.1.'; }
if ( false === strpos( $main, 'class-ikon-seo-client-portal.php' ) ) { $failures[] = 'Client Portal class is not loaded.'; }
if ( false === strpos( $plugin, "const DB_VERSION = '40.0'" ) ) { $failures[] = 'Database component version is not 37.0.'; }
foreach ( array( 'ikon_seo_client_portal_access', 'ikon_seo_client_portal_snapshots', 'ikon_seo_client_portal_events', 'Ikon_SEO_Client_Portal::CRON_HOOK' ) as $needle ) {
	if ( false === strpos( $plugin, $needle ) ) { $failures[] = 'Client Portal database or cron integration missing: ' . $needle; }
}
foreach ( array( '/client-portal', '/client-portal-admin', 'client_portal_report', 'client_portal_admin_sync' ) as $needle ) {
	if ( false === strpos( $rest, $needle ) ) { $failures[] = 'Client Portal REST integration missing: ' . $needle; }
}
if ( false === strpos( $auth, 'can_client_portal_admin' ) ) { $failures[] = 'Client Portal admin authorisation is missing.'; }
foreach ( array( 'read', 'preview_user', 'create_access', 'activate_access', 'revoke_access', 'refresh_snapshot' ) as $command ) {
	if ( false === strpos( $auth, "'" . $command . "'" ) ) { $failures[] = 'Client Portal approve-scope command is missing: ' . $command; }
}
foreach ( array( 'ikon_seo_client_portal_access', 'ikon_seo_client_portal_snapshots', 'ikon_seo_client_portal_events', 'Ikon_SEO_Client_Portal::CRON_HOOK' ) as $needle ) {
	if ( false === strpos( $health, $needle ) ) { $failures[] = 'Production health does not cover Client Portal resource: ' . $needle; }
}
foreach ( array( 'assignment_fingerprint', 'sanitize_snapshot_payload', 'pending', 'active', 'revoked', 'expires_at', 'ip_hash', 'user_agent_hash' ) as $needle ) {
	if ( false === strpos( $portal, $needle ) ) { $failures[] = 'Client Portal lifecycle or privacy control missing: ' . $needle; }
}
foreach ( array( 'wp_publish_post', 'wp_update_post', 'wp_delete_post', 'wp_mail', 'wp_insert_post' ) as $needle ) {
	if ( false !== strpos( $portal, $needle ) ) { $failures[] = 'Client Portal contains prohibited primitive: ' . $needle; }
}
$openapi = json_decode( file_get_contents( $root . '/openapi/ikon-seo-openapi.json' ), true );
if ( ! is_array( $openapi ) ) { $failures[] = 'Static OpenAPI JSON is invalid.'; }
else {
	$operations = 0; $ids = array();
	foreach ( (array) ( $openapi['paths'] ?? array() ) as $path ) { foreach ( $path as $method => $operation ) { if ( in_array( $method, array( 'get','post','put','patch','delete' ), true ) ) { $operations++; $ids[] = $operation['operationId'] ?? ''; } } }
	if ( 30 !== $operations || count( $ids ) !== count( array_unique( $ids ) ) ) { $failures[] = 'Static focused OpenAPI must contain exactly 30 unique operations.'; }
	if ( '2.0.1' !== ( $openapi['info']['version'] ?? '' ) ) { $failures[] = 'Static OpenAPI version is not 2.0.1.'; }
}
foreach ( array( 'docs/CLIENT-PORTAL.md', 'docs/UPGRADE-v1.18.md' ) as $relative ) {
	if ( ! file_exists( $root . '/' . $relative ) ) { $failures[] = 'Client Portal documentation missing: ' . $relative; }
}
if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Ikon SEO v2.0.1 Client Portal integration, isolation, privacy and focused-schema tests passed.\n";
