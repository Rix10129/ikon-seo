<?php
$root = dirname( __DIR__ );
$failures = array();
$main = file_get_contents( $root . '/ikon-seo.php' );
$plugin = file_get_contents( $root . '/includes/class-ikon-seo-plugin.php' );
$rest = file_get_contents( $root . '/includes/class-ikon-seo-rest.php' );
$admin = file_get_contents( $root . '/includes/class-ikon-seo-admin.php' );
$auth = file_get_contents( $root . '/includes/class-ikon-seo-auth.php' );
$engine = file_get_contents( $root . '/includes/class-ikon-seo-agency-service-levels.php' );

if ( false === strpos( $main, 'Version: 2.0.1' ) || false === strpos( $main, "define( 'IKON_SEO_VERSION', '2.0.1' )" ) ) { $failures[] = 'Plugin version is not 2.0.1.'; }
if ( false === strpos( $main, 'class-ikon-seo-agency-service-levels.php' ) ) { $failures[] = 'Agency Service Levels class is not loaded.'; }
if ( false === strpos( $plugin, "const DB_VERSION = '40.0'" ) ) { $failures[] = 'Database component version is not 35.0.'; }
foreach ( array( 'ikon_seo_service_plans','ikon_seo_service_assignments','ikon_seo_team_capacity','ikon_seo_service_work_items','ikon_seo_client_reports','ikon_seo_service_events','Ikon_SEO_Agency_Service_Levels::CRON_HOOK' ) as $needle ) {
	if ( false === strpos( $plugin, $needle ) ) { $failures[] = 'Database or cron integration missing: ' . $needle; }
}
foreach ( array( '/agency-service-levels','agency_service_levels_report','agency_service_levels_sync','can_agency_service_levels' ) as $needle ) {
	if ( false === strpos( $rest, $needle ) && false === strpos( $auth, $needle ) ) { $failures[] = 'REST or scope integration missing: ' . $needle; }
}
foreach ( array( "'agency-service-levels'", 'render_agency_service_levels', 'agency_service_levels_action', 'download_client_service_report' ) as $needle ) {
	if ( false === strpos( $admin, $needle ) ) { $failures[] = 'Admin integration missing: ' . $needle; }
}
foreach ( array( 'approve_plan','retire_plan','assign_plan','approve_report','mark_report_delivered' ) as $needle ) {
	if ( false === strpos( $auth, $needle ) ) { $failures[] = 'Approval-scope routing missing: ' . $needle; }
}
foreach ( array( 'manual_delivery_only','client_report_stale','different from its preparer','concurrent-work limit','monthly service capacity' ) as $needle ) {
	if ( false === stripos( $engine, $needle ) ) { $failures[] = 'Service-level governance behavior missing: ' . $needle; }
}
foreach ( array( 'wp_publish_post','wp_update_post','wp_delete_post','wp_mail',"'post_status' => 'publish'" ) as $needle ) {
	if ( false !== strpos( $engine, $needle ) ) { $failures[] = 'Service-level engine contains a prohibited primitive: ' . $needle; }
}
$openapi = json_decode( file_get_contents( $root . '/openapi/ikon-seo-openapi.json' ), true );
if ( ! is_array( $openapi ) ) { $failures[] = 'Static OpenAPI JSON is invalid.'; }
else {
	$operations = 0; $ids = array();
	foreach ( (array) ( $openapi['paths'] ?? array() ) as $path ) { foreach ( $path as $method => $operation ) { if ( in_array( $method, array( 'get','post','put','patch','delete' ), true ) ) { $operations++; $ids[] = $operation['operationId'] ?? ''; } } }
	if ( 30 !== $operations || count( $ids ) !== count( array_unique( $ids ) ) ) { $failures[] = 'Static OpenAPI must contain exactly 30 unique operations.'; }
	if ( empty( $openapi['paths']['/agency-service-levels']['post'] ) || empty( $openapi['components']['schemas']['AgencyServiceLevelsSync'] ) ) { $failures[] = 'Agency Service Levels OpenAPI contract is missing.'; }
	if ( ! empty( $openapi['paths']['/workspace-state'] ) ) { $failures[] = 'Replaced Project History action remains in the focused schema.'; }
	if ( '2.0.1' !== ( $openapi['info']['version'] ?? '' ) ) { $failures[] = 'Static OpenAPI version is not 2.0.1.'; }
}
foreach ( array( 'docs/AGENCY-SERVICE-LEVELS-CLIENT-REPORTING.md','docs/UPGRADE-v1.15.md' ) as $relative ) { if ( ! file_exists( $root . '/' . $relative ) ) { $failures[] = 'Release documentation missing: ' . $relative; } }
if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Ikon SEO v2.0.1 integration, service-level governance and focused-schema tests passed.\n";
