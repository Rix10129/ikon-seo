<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function esc_url_raw( $value ) { return (string) $value; }
function __( $value ) { return $value; }
class Ikon_SEO_Agency_Command_Centre {}
class Ikon_SEO_Portfolio_Governance {}
class Ikon_SEO_Workspace_History {}
class Ikon_SEO_Logger {}
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-agency-service-levels.php';

$reflection = new ReflectionClass( 'Ikon_SEO_Agency_Service_Levels' );
$engine = $reflection->newInstanceWithoutConstructor();
$failures = array();

$plan = $engine->normalize_plan( array(
	'name' => 'Growth Plan',
	'currency' => 'qar',
	'monthly_capacity_units' => 0,
	'max_concurrent_items' => 500,
	'response_target_hours' => 0,
	'report_cadence' => 'sometimes',
	'included_deliverables' => "Technical review\nContent brief\nTechnical review",
	'excluded_services' => array( 'Guaranteed rankings' ),
	'manual_delivery_only' => false,
	'live_site_writes' => true,
	'ranking_guarantee' => true,
) );
if ( 1 !== $plan['monthly_capacity_units'] ) { $failures[] = 'Monthly capacity is not bounded to at least one unit.'; }
if ( 100 !== $plan['max_concurrent_items'] ) { $failures[] = 'Concurrent work is not capped.'; }
if ( 1 !== $plan['response_target_hours'] ) { $failures[] = 'Response target is not bounded.'; }
if ( 'monthly' !== $plan['report_cadence'] ) { $failures[] = 'Invalid report cadence was retained.'; }
if ( 'QAR' !== $plan['currency'] ) { $failures[] = 'Currency was not normalised.'; }
if ( 2 !== count( $plan['included_deliverables'] ) ) { $failures[] = 'Deliverables were not deduplicated.'; }
if ( true !== $plan['manual_delivery_only'] || false !== $plan['live_site_writes'] || false !== $plan['ranking_guarantee'] ) { $failures[] = 'Permanent service-plan safety locks can be weakened.'; }

$equivalent = $engine->normalize_plan( array(
	'name' => 'Growth Plan', 'currency' => 'QAR', 'monthly_capacity_units' => 1,
	'max_concurrent_items' => 100, 'response_target_hours' => 1,
	'report_cadence' => 'monthly', 'included_deliverables' => array( 'Technical review','Content brief' ),
	'excluded_services' => array( 'Guaranteed rankings' ),
) );
if ( ! hash_equals( $engine->plan_fingerprint( $plan ), $engine->plan_fingerprint( $equivalent ) ) ) { $failures[] = 'Equivalent service plans do not produce stable fingerprints.'; }

$assignment = array( 'capacity_units' => 20, 'plan' => array( 'response_target_hours' => 24 ) );
$items = array(
	array( 'units'=>4,'status'=>'completed','created_at'=>'2026-07-01 08:00:00','first_action_at'=>'2026-07-01 12:00:00','due_at'=>'2026-07-05 00:00:00' ),
	array( 'units'=>8,'status'=>'in_progress','created_at'=>'2026-07-02 08:00:00','first_action_at'=>'2026-07-04 12:00:00','due_at'=>'2026-07-10 00:00:00' ),
	array( 'units'=>3,'status'=>'planned','created_at'=>'2026-07-03 08:00:00','first_action_at'=>'','due_at'=>'2026-07-06 00:00:00' ),
);
$compliance = $engine->calculate_compliance( $assignment, $items, array( array( 'status'=>'approved' ) ), '2026-07-12 00:00:00' );
if ( 15 !== $compliance['used_units'] || 5 !== $compliance['remaining_units'] ) { $failures[] = 'Capacity usage calculation is incorrect.'; }
if ( 2 !== $compliance['open_items'] || 2 !== $compliance['overdue_items'] ) { $failures[] = 'Open or overdue work calculation is incorrect.'; }
if ( 1 !== $compliance['response_breaches'] ) { $failures[] = 'Response breach calculation is incorrect.'; }
if ( 'attention_required' !== $compliance['status'] ) { $failures[] = 'Low compliance was not flagged for attention.'; }

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-ikon-seo-agency-service-levels.php' );
foreach ( array( 'manual_delivery_only', 'client_approval_required', 'ranking_guarantee', 'client_report_stale', 'sent_by_plugin' ) as $needle ) {
	if ( false === strpos( $source, $needle ) ) { $failures[] = 'Service-level safeguard missing: ' . $needle; }
}
foreach ( array( 'wp_publish_post', 'wp_update_post', 'wp_delete_post', 'wp_mail', "'post_status' => 'publish'" ) as $needle ) {
	if ( false !== strpos( $source, $needle ) ) { $failures[] = 'Service-level engine contains a prohibited live or communication primitive: ' . $needle; }
}

if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Agency Service Levels plan locks, capacity scoring, stale-report language and no-live-change tests passed.\n";
