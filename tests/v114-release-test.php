<?php
$root = dirname( __DIR__ );
$failures = array();
$main = file_get_contents( $root . '/ikon-seo.php' );
$plugin = file_get_contents( $root . '/includes/class-ikon-seo-plugin.php' );
$rest = file_get_contents( $root . '/includes/class-ikon-seo-rest.php' );
$admin = file_get_contents( $root . '/includes/class-ikon-seo-admin.php' );
$auth = file_get_contents( $root . '/includes/class-ikon-seo-auth.php' );
$agency = file_get_contents( $root . '/includes/class-ikon-seo-agency-command-centre.php' );
$launch = file_get_contents( $root . '/includes/class-ikon-seo-guided-launch.php' );
$engine = file_get_contents( $root . '/includes/class-ikon-seo-portfolio-governance.php' );

if ( false === strpos( $main, 'Version: 2.0.1' ) || false === strpos( $main, "define( 'IKON_SEO_VERSION', '2.0.1' )" ) ) { $failures[] = 'Plugin version is not 2.0.1.'; }
if ( false === strpos( $main, 'class-ikon-seo-portfolio-governance.php' ) ) { $failures[] = 'Portfolio Governance class is not loaded.'; }
if ( false === strpos( $plugin, "const DB_VERSION = '40.0'" ) ) { $failures[] = 'Database component version is not 35.0.'; }
foreach ( array( 'ikon_seo_governance_policies', 'ikon_seo_governance_assignments', 'ikon_seo_governance_inbox', 'ikon_seo_governance_events', 'encrypted_governance_key', 'Ikon_SEO_Portfolio_Governance::CRON_HOOK' ) as $needle ) {
	if ( false === strpos( $plugin, $needle ) ) { $failures[] = 'Database or cron integration missing: ' . $needle; }
}
foreach ( array( '/agency-governance-agent', '/portfolio-governance', 'portfolio_governance_report', 'portfolio_governance_sync', 'PortfolioGovernanceSync', 'can_portfolio_governance' ) as $needle ) {
	if ( false === strpos( $rest, $needle ) ) { $failures[] = 'REST integration missing: ' . $needle; }
}
foreach ( array( "'agency-governance'", 'render_portfolio_governance', 'portfolio_governance_action', 'ikon_seo_portfolio_governance_action' ) as $needle ) {
	if ( false === strpos( $admin, $needle ) ) { $failures[] = 'Admin integration missing: ' . $needle; }
}
foreach ( array( 'can_portfolio_governance', 'approve_policy', 'retire_policy', 'accept_proposal', 'reject_proposal' ) as $needle ) {
	if ( false === strpos( $auth, $needle ) ) { $failures[] = 'Governance scope routing missing: ' . $needle; }
}
foreach ( array( 'governance_status', 'governance_last_sync_at', 'governance_last_error' ) as $needle ) {
	if ( false === strpos( $agency, $needle ) ) { $failures[] = 'Agency site governance health missing: ' . $needle; }
}
foreach ( array( 'minimum_strategy_readiness', 'max_safe_batch', 'active governance policy' ) as $needle ) {
	if ( false === stripos( $launch, $needle ) ) { $failures[] = 'Guided Launch policy enforcement missing: ' . $needle; }
}
foreach ( array( 'manual_publish_only', 'advisory_only', 'anonymised_only', 'external_live_writes', 'pending_local_approval', 'proposal_only' ) as $needle ) {
	if ( false === strpos( $engine, $needle ) ) { $failures[] = 'Portfolio Governance safety behavior missing: ' . $needle; }
}
foreach ( array( 'wp_publish_post', 'wp_update_post', 'wp_delete_post', "'post_status' => 'publish'" ) as $needle ) {
	if ( false !== strpos( $engine, $needle ) ) { $failures[] = 'Governance engine contains a prohibited live-change primitive: ' . $needle; }
}

$openapi = json_decode( file_get_contents( $root . '/openapi/ikon-seo-openapi.json' ), true );
if ( ! is_array( $openapi ) ) { $failures[] = 'Static OpenAPI JSON is invalid.'; }
else {
	$operations = 0; $ids = array();
	foreach ( (array) ( $openapi['paths'] ?? array() ) as $path ) {
		foreach ( $path as $method => $operation ) {
			if ( in_array( $method, array( 'get','post','put','patch','delete' ), true ) ) { $operations++; $ids[] = $operation['operationId'] ?? ''; }
		}
	}
	if ( 30 !== $operations || count( $ids ) !== count( array_unique( $ids ) ) ) { $failures[] = 'Static OpenAPI must contain exactly 30 unique operations.'; }
	if ( empty( $openapi['paths']['/portfolio-governance']['post'] ) || empty( $openapi['components']['schemas']['PortfolioGovernanceSync'] ) ) { $failures[] = 'Static Portfolio Governance contract is missing.'; }
	if ( ! empty( $openapi['paths']['/queue/{id}/complete'] ) ) { $failures[] = 'Replaced queue completion operation remains in the focused schema.'; }
	if ( '2.0.1' !== ( $openapi['info']['version'] ?? '' ) ) { $failures[] = 'Static OpenAPI version is not 2.0.1.'; }
}
foreach ( array( 'docs/AGENCY-PORTFOLIO-GOVERNANCE.md', 'docs/UPGRADE-v1.14.md' ) as $relative ) {
	if ( ! file_exists( $root . '/' . $relative ) ) { $failures[] = 'Release documentation missing: ' . $relative; }
}
if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Ikon SEO v2.0.1 integration, governance and focused-schema tests passed.\n";
