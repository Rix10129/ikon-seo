<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function home_url( $path = '/' ) { return 'https://example.com' . $path; }
function untrailingslashit( $value ) { return rtrim( (string) $value, '/\\' ); }
function wp_http_validate_url( $url ) { return (bool) filter_var( $url, FILTER_VALIDATE_URL ); }
class Ikon_SEO_Publishing_Readiness {}
class Ikon_SEO_Search_Intelligence {}
class Ikon_SEO_Analytics {}
class Ikon_SEO_Experiments_Claims_Revenue {}
class Ikon_SEO_Workspace_History {}
class Ikon_SEO_Logger {}
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-search-impact.php';
$reflection = new ReflectionClass( 'Ikon_SEO_Search_Impact' );
$engine = $reflection->newInstanceWithoutConstructor();
$invoke = static function( $name, array $args = array() ) use ( $reflection, $engine ) {
	$method = $reflection->getMethod( $name ); $method->setAccessible( true ); return $method->invokeArgs( $engine, $args );
};
$failures = array();
if ( 40.0 !== round( $invoke( 'metric_change_percent', array( 'clicks', 100, 140 ) ), 1 ) ) { $failures[] = 'Positive metric change was calculated incorrectly.'; }
if ( 20.0 !== round( $invoke( 'metric_change_percent', array( 'position', 10, 8 ) ), 1 ) ) { $failures[] = 'Average-position improvement was not treated as positive.'; }
if ( -20.0 !== round( $invoke( 'metric_change_percent', array( 'position', 10, 12 ) ), 1 ) ) { $failures[] = 'Average-position decline was not treated as negative.'; }
if ( 28 !== $invoke( 'period_days', array( '2026-03-04', '2026-03-31' ) ) ) { $failures[] = 'Inclusive baseline period length is incorrect.'; }
if ( 14 !== $invoke( 'overlap_days', array( '2026-03-01', '2026-03-31', '2026-03-18', '2026-04-15' ) ) ) { $failures[] = 'Evidence overlap calculation is incorrect.'; }
if ( true !== $invoke( 'is_same_site_url', array( 'https://example.com/comparison/' ) ) ) { $failures[] = 'Valid same-site comparison URL was rejected.'; }
if ( false !== $invoke( 'is_same_site_url', array( 'https://competitor.example/page/' ) ) ) { $failures[] = 'External comparison URL was accepted.'; }
if ( false === strpos( $invoke( 'recommended_next_step', array( 'positive_signal', 'high' ) ), 'carefully' ) ) { $failures[] = 'Positive-signal guidance is not appropriately cautious.'; }
$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-ikon-seo-search-impact.php' );
foreach ( array( 'assessment_invalidated', "'outcome' => 'inconclusive'", 'A newer or refreshed measurement invalidated', 'not proof that the release caused', 'comparison_same', 'currency=%s' ) as $needle ) {
	if ( false === strpos( $source, $needle ) ) { $failures[] = 'Lifecycle or evidence guard is missing: ' . $needle; }
}
foreach ( array( 'wp_publish_post', 'wp_update_post', 'wp_delete_post' ) as $needle ) {
	if ( false !== strpos( $source, $needle ) ) { $failures[] = 'Search Impact contains prohibited public-content mutation: ' . $needle; }
}
if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Search Impact metric, comparison, stale-evidence and no-live-change tests passed.\n";
