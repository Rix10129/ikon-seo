<?php
$root = dirname( __DIR__ );
$failures = array();
$plugin = file_get_contents( $root . '/ikon-seo.php' );
if ( false === strpos( $plugin, 'Version: 2.0.1' ) || false === strpos( $plugin, "define( 'IKON_SEO_VERSION', '2.0.1' )" ) ) { $failures[] = 'Plugin version was not updated consistently.'; }
foreach ( array( 'class-ikon-seo-content-workbench.php', 'class-ikon-seo-editorial-review.php', 'class-ikon-seo-publishing-readiness.php', 'class-ikon-seo-search-impact.php' ) as $file ) {
	if ( false === strpos( $plugin, $file ) ) { $failures[] = 'Required release component is not loaded: ' . $file; }
}
$plugin_class = file_get_contents( $root . '/includes/class-ikon-seo-plugin.php' );
if ( false === strpos( $plugin_class, "const DB_VERSION = '40.0'" ) ) { $failures[] = 'Database component version is not 35.0.'; }
foreach ( array( 'ikon_seo_impact_studies', 'ikon_seo_impact_measurements', 'ikon_seo_impact_events', 'Ikon_SEO_Search_Impact::CRON_HOOK' ) as $needle ) {
	if ( false === strpos( $plugin_class, $needle ) ) { $failures[] = 'Search Impact database/cron integration missing: ' . $needle; }
}
$openapi = json_decode( file_get_contents( $root . '/openapi/ikon-seo-openapi.json' ), true );
if ( ! is_array( $openapi ) ) { $failures[] = 'Static OpenAPI JSON is invalid.'; }
else {
	$operations = 0; $operation_ids = array();
	foreach ( (array) ( $openapi['paths'] ?? array() ) as $path ) {
		foreach ( $path as $method => $operation ) { if ( in_array( $method, array( 'get','post','put','patch','delete' ), true ) ) { $operations++; if ( ! empty( $operation['operationId'] ) ) { $operation_ids[] = $operation['operationId']; } } }
	}
	if ( 30 !== $operations ) { $failures[] = 'Focused workspace schema no longer has exactly 30 operations.'; }
	if ( count( $operation_ids ) !== count( array_unique( $operation_ids ) ) ) { $failures[] = 'Focused workspace schema contains duplicate operation IDs.'; }
	if ( empty( $openapi['paths']['/search-impact']['post'] ) ) { $failures[] = 'Search Impact workspace action is missing.'; }
	if ( ! empty( $openapi['paths']['/queue/{id}/complete'] ) ) { $failures[] = 'Focused schema still exposes the replaced queue completion action.'; }
	if ( empty( $openapi['components']['schemas']['SearchImpactSync'] ) ) { $failures[] = 'SearchImpactSync schema is missing.'; }
	if ( '2.0.1' !== ( $openapi['info']['version'] ?? '' ) ) { $failures[] = 'Static OpenAPI version is not 2.0.1.'; }
}
$admin = file_get_contents( $root . '/includes/class-ikon-seo-admin.php' );
foreach ( array( "'search-impact'", 'render_search_impact', 'ikon_seo_search_impact_action', 'search_impact_command_form' ) as $needle ) {
	if ( false === strpos( $admin, $needle ) ) { $failures[] = 'Admin integration missing: ' . $needle; }
}
$auth = file_get_contents( $root . '/includes/class-ikon-seo-auth.php' );
if ( false === strpos( $auth, 'can_search_impact' ) || false === strpos( $auth, "'assess'" ) || false === strpos( $auth, "'approve'" ) ) { $failures[] = 'Search Impact approval-scope routing is missing.'; }
$rest = file_get_contents( $root . '/includes/class-ikon-seo-rest.php' );
foreach ( array( "'/search-impact'", 'search_impact_report', 'search_impact_sync', 'SearchImpactSync', 'can_search_impact' ) as $needle ) {
	if ( false === strpos( $rest, $needle ) ) { $failures[] = 'REST integration missing: ' . $needle; }
}
$engine = file_get_contents( $root . '/includes/class-ikon-seo-search-impact.php' );
foreach ( array( 'associations, not', 'not proof', 'assessment_invalidated', 'impact_change_threshold_percent', 'currency=%s' ) as $needle ) {
	if ( false === stripos( $engine, $needle ) ) { $failures[] = 'Search Impact evidence/safety behavior missing: ' . $needle; }
}
foreach ( array( 'wp_publish_post', "'post_status' => 'publish'", 'wp_update_post', 'wp_delete_post' ) as $needle ) {
	if ( false !== strpos( $engine, $needle ) ) { $failures[] = 'Search Impact engine contains a prohibited live-change primitive: ' . $needle; }
}
foreach ( array( 'docs/SEARCH-IMPACT-OUTCOME-ATTRIBUTION.md', 'docs/UPGRADE-v1.12.md' ) as $relative ) {
	if ( ! file_exists( $root . '/' . $relative ) ) { $failures[] = 'Release documentation missing: ' . $relative; }
}
if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Ikon SEO v2.0.1 regression, safety and focused-schema tests passed.\n";
