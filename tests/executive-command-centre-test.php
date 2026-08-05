<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function esc_url_raw( $value ) { return (string) $value; }
function __( $value ) { return $value; }
class Ikon_SEO_Agency_Command_Centre {}
class Ikon_SEO_Portfolio_Governance {}
class Ikon_SEO_Agency_Service_Levels {}
class Ikon_SEO_Workspace_History {}
class Ikon_SEO_Logger {}
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-executive-command-centre.php';

$reflection = new ReflectionClass( 'Ikon_SEO_Executive_Command_Centre' );
$engine = $reflection->newInstanceWithoutConstructor();
$failures = array();

$healthy = $engine->calculate_health(
	array(
		'status' => 'connected',
		'stale' => false,
		'last_error' => '',
		'snapshot' => array(
			'strategy' => array( 'readiness' => 100 ),
			'connections' => array( 'search_console' => array( 'connected' => true ), 'analytics' => array( 'connected' => true ) ),
			'diagnostics' => array( 'blockers' => array( 'critical' => 0, 'high' => 0 ) ),
			'technical' => array( 'failed_urls' => 0 ),
			'workflow' => array( 'overdue' => 0 ),
			'operations' => array(
				'publishing' => array( 'counts' => array( 'completed' => 1 ) ),
				'search_impact' => array( 'counts' => array( 'acknowledged' => 1 ) ),
			),
		),
	),
	array(),
	array( 'compliance_score' => 100 )
);
if ( 100 !== $healthy['score'] || 'healthy' !== $healthy['level'] ) { $failures[] = 'Healthy portfolio inputs do not produce a transparent 100-point health score.'; }
if ( 100 !== (int) round( array_sum( $healthy['components'] ) ) ) { $failures[] = 'Health score does not equal the visible component total.'; }

$degraded = $engine->calculate_health(
	array(
		'status' => 'error',
		'stale' => true,
		'last_error' => 'Connection failed',
		'snapshot' => array(
			'strategy' => array( 'readiness' => 40 ),
			'connections' => array( 'search_console' => array( 'connected' => false ), 'analytics' => array( 'connected' => false ) ),
			'diagnostics' => array( 'blockers' => array( 'critical' => 2, 'high' => 2 ) ),
			'technical' => array( 'failed_urls' => 8 ),
			'workflow' => array( 'overdue' => 4 ),
			'operations' => array(
				'publishing' => array( 'counts' => array( 'issues_found' => 2, 'ready_for_manual_publish' => 1 ) ),
				'search_impact' => array( 'counts' => array() ),
			),
		),
	),
	array( 'workflow' => 3 ),
	array( 'compliance_score' => 50 )
);
if ( $degraded['score'] >= $healthy['score'] || 'critical' !== $degraded['level'] ) { $failures[] = 'Operational risks do not reduce the health score or critical classification.'; }
if ( false === strpos( strtolower( $degraded['methodology'] ), 'not a ranking score' ) ) { $failures[] = 'Health-score methodology does not clearly reject ranking or revenue interpretation.'; }

$forecast = $engine->forecast_capacity_from_report(
	array(
		'capacity' => array(
			array( 'user_id' => 7, 'display_name' => 'Writer', 'capacity_units' => 20, 'allocated_units' => 18 ),
			array( 'user_id' => 8, 'display_name' => 'Reviewer', 'capacity_units' => 10, 'allocated_units' => 4 ),
		),
		'work_items' => array(
			array( 'status' => 'planned', 'owner_id' => 0, 'units' => 3, 'due_at' => '2000-01-01 00:00:00' ),
			array( 'status' => 'in_progress', 'owner_id' => 7, 'units' => 5, 'due_at' => '' ),
			array( 'status' => 'completed', 'owner_id' => 8, 'units' => 99, 'due_at' => '2000-01-01 00:00:00' ),
		),
	)
);
if ( 30 !== $forecast['total_capacity_units'] || 22 !== $forecast['committed_units'] || 8 !== $forecast['remaining_units'] ) { $failures[] = 'Capacity totals are incorrect.'; }
if ( 73.3 !== $forecast['utilisation_percent'] ) { $failures[] = 'Portfolio utilisation percentage is incorrect.'; }
if ( 1 !== $forecast['unassigned_items'] || 1 !== $forecast['overdue_items'] || 8 !== $forecast['forecast_30_day_units'] ) { $failures[] = 'Workload forecast does not correctly identify unassigned, overdue or upcoming work.'; }
if ( true !== $forecast['at_risk'] || false !== $forecast['automatic_reassignment'] ) { $failures[] = 'Capacity risk or no-auto-reassignment safeguard is incorrect.'; }

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-ikon-seo-executive-command-centre.php' );
foreach ( array( 'remote_monitoring_only', 'approvals_remain_local', 'client_portal_preview', 'automatic_reassignment' ) as $needle ) {
	if ( false === strpos( $source, $needle ) ) { $failures[] = 'Executive command safeguard missing: ' . $needle; }
}
foreach ( array( 'wp_publish_post', 'wp_update_post', 'wp_delete_post', 'wp_mail', "'post_status' => 'publish'" ) as $needle ) {
	if ( false !== strpos( $source, $needle ) ) { $failures[] = 'Executive command engine contains a prohibited live or communication primitive: ' . $needle; }
}

if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Executive Command Centre health scoring, capacity forecasting and no-live-change tests passed.\n";
