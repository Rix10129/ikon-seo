<?php
$root = dirname( __DIR__ );
$failures = array();
$main = file_get_contents( $root . '/ikon-seo.php' );
$plugin = file_get_contents( $root . '/includes/class-ikon-seo-plugin.php' );
$rest = file_get_contents( $root . '/includes/class-ikon-seo-rest.php' );
$admin = file_get_contents( $root . '/includes/class-ikon-seo-admin.php' );
$auth = file_get_contents( $root . '/includes/class-ikon-seo-auth.php' );
$hardening = file_get_contents( $root . '/includes/class-ikon-seo-platform-hardening.php' );
$health = file_get_contents( $root . '/includes/class-ikon-seo-production-health.php' );

if ( false === strpos( $main, 'Version: 2.0.1' ) || false === strpos( $main, "define( 'IKON_SEO_VERSION', '2.0.1' )" ) ) { $failures[] = 'Plugin version is not 2.0.1.'; }
if ( false === strpos( $main, 'class-ikon-seo-platform-hardening.php' ) ) { $failures[] = 'Platform hardening class is not loaded.'; }
if ( false === strpos( $plugin, "const DB_VERSION = '40.0'" ) ) { $failures[] = 'Database component version is not 37.0.'; }
foreach ( array( 'ikon_seo_release_integrity_runs', 'ikon_seo_recovery_archives', 'ikon_seo_upgrade_journal', 'Ikon_SEO_Platform_Hardening::CRON_HOOK', 'record_upgrade_journal' ) as $needle ) {
	if ( false === strpos( $plugin, $needle ) ) { $failures[] = 'Platform database, cron or upgrade integration missing: ' . $needle; }
}
foreach ( array( '/platform-hardening', 'platform_hardening_sync', 'PlatformHardeningSync' ) as $needle ) {
	if ( false === strpos( $rest, $needle ) ) { $failures[] = 'Platform REST integration missing: ' . $needle; }
}
if ( false === strpos( $auth, 'can_platform_hardening' ) || false === strpos( $auth, "array( 'restore_archive', 'repair_scheduler', 'cleanup' )" ) ) { $failures[] = 'Platform draft/approve scope separation is missing.'; }
foreach ( array( 'Platform Hardening & Release Management', 'Compatibility and security matrix', 'Recovery archives', 'Upgrade journal' ) as $needle ) {
	if ( false === strpos( $admin, $needle ) ) { $failures[] = 'Platform admin interface missing: ' . $needle; }
}
foreach ( array( 'detached_rsa_sha256_manifest', 'preview_restore', 'expected_hash', 'credentials_included', 'automatic_rollback' ) as $needle ) {
	if ( false === strpos( $hardening, $needle ) ) { $failures[] = 'Platform hardening behavior missing: ' . $needle; }
}
if ( substr_count( $health, "'ikon_seo_" ) < 80 ) { $failures[] = 'Production health table registry was not expanded for the complete platform.'; }

$openapi = json_decode( file_get_contents( $root . '/openapi/ikon-seo-openapi.json' ), true );
if ( ! is_array( $openapi ) ) { $failures[] = 'Static OpenAPI JSON is invalid.'; }
else {
	$operations = 0; $ids = array();
	foreach ( (array) ( $openapi['paths'] ?? array() ) as $path ) { foreach ( $path as $method => $operation ) { if ( in_array( $method, array( 'get','post','put','patch','delete' ), true ) ) { $operations++; $ids[] = $operation['operationId'] ?? ''; } } }
	if ( 30 !== $operations || count( $ids ) !== count( array_unique( $ids ) ) ) { $failures[] = 'Static OpenAPI must contain exactly 30 unique operations.'; }
	if ( empty( $openapi['paths']['/platform-hardening']['post'] ) || empty( $openapi['components']['schemas']['PlatformHardeningSync'] ) ) { $failures[] = 'Platform Hardening OpenAPI contract is missing.'; }
	if ( '2.0.1' !== ( $openapi['info']['version'] ?? '' ) ) { $failures[] = 'Static OpenAPI version is not 2.0.1.'; }
}
foreach ( array( 'docs/PLATFORM-HARDENING-RELEASE-MANAGEMENT.md', 'docs/UPGRADE-v1.17.md' ) as $relative ) {
	if ( ! file_exists( $root . '/' . $relative ) ) { $failures[] = 'Release documentation missing: ' . $relative; }
}
foreach ( array( 'wp_publish_post', 'wp_update_post', 'wp_delete_post', 'wp_mail' ) as $needle ) {
	if ( false !== strpos( $hardening, $needle ) ) { $failures[] = 'Platform hardening contains prohibited primitive: ' . $needle; }
}
if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Ikon SEO v2.0.1 platform hardening, recovery, migration and focused-schema tests passed.\n";
