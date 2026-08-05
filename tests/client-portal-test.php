<?php
define( 'ABSPATH', __DIR__ . '/' );
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( is_scalar( $value ) ? (string) $value : '' ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( is_scalar( $value ) ? (string) $value : '' ) ); }
function esc_url_raw( $value ) { return filter_var( (string) $value, FILTER_SANITIZE_URL ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function current_time() { return '2026-08-05 00:00:00'; }
class Ikon_SEO_Agency_Command_Centre {}
class Ikon_SEO_Agency_Service_Levels {}
class Ikon_SEO_Executive_Command_Centre {}
class Ikon_SEO_Search_Impact {}
class Ikon_SEO_Workspace_History {}
class Ikon_SEO_Logger {}
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-client-portal.php';

$reflection = new ReflectionClass( 'Ikon_SEO_Client_Portal' );
$portal = $reflection->newInstanceWithoutConstructor();
$failures = array();

$permissions = $portal->normalize_permissions( array( 'overview', 'approved_reports', 'admin_notes', 'overview', 'credentials' ) );
if ( array( 'approved_reports', 'overview' ) !== $permissions ) { $failures[] = 'Permission normalisation did not remove unsupported or duplicate sections.'; }

$all = $portal->normalize_permissions( array() );
if ( 7 !== count( $all ) || in_array( 'credentials', $all, true ) ) { $failures[] = 'Default portal permission set is incomplete or unsafe.'; }

$fingerprint_a = $portal->assignment_fingerprint( array( 'wp_user_id' => 8, 'site_id' => 4, 'permissions' => array( 'overview','planned_work' ), 'expires_at' => '2026-09-01 00:00:00' ) );
$fingerprint_b = $portal->assignment_fingerprint( array( 'site_id' => 4, 'wp_user_id' => 8, 'permissions' => array( 'planned_work','overview' ), 'expires_at' => '2026-09-01 00:00:00' ) );
if ( $fingerprint_a !== $fingerprint_b || 64 !== strlen( $fingerprint_a ) ) { $failures[] = 'Access fingerprints are not deterministic.'; }

$raw = array(
	'generated_at' => '2026-08-05 00:00:00',
	'source_updated_at' => '2026-08-04 23:00:00',
	'overview' => array( 'site_name' => 'Client Site', 'site_url' => 'https://client.example/', 'client_name' => 'Client', 'service_status' => 'active', 'latest_report_period' => 'July', 'internal_margin' => 90 ),
	'service_scope' => array( 'plan_name' => 'Growth', 'status' => 'active', 'included_deliverables' => array( 'Audit' ), 'excluded_services' => array( 'Paid ads' ), 'monthly_fee' => 5000 ),
	'approved_reports' => array(
		array( 'id' => 1, 'status' => 'approved', 'period_start' => '2026-07-01', 'period_end' => '2026-07-31', 'approved_at' => '2026-08-01', 'report' => array( 'client_summary' => 'Approved summary', 'next_actions' => array( 'Publish approved draft manually' ), 'work_delivered' => array( array( 'title' => 'Audit', 'category' => 'technical', 'status' => 'completed', 'units' => 4, 'internal_note' => 'secret' ) ), 'service_level' => array( 'score' => 90, 'completed_items' => 1, 'overdue_items' => 0 ), 'evidence_coverage' => array( 'limitations' => array( 'Data can be delayed.' ) ), 'private_notes' => 'secret' ) ),
		array( 'id' => 2, 'status' => 'review_ready', 'report' => array( 'client_summary' => 'Should not appear' ) ),
	),
	'completed_work' => array( array( 'id' => 3, 'title' => 'Schema audit', 'category' => 'technical', 'status' => 'completed', 'description' => 'internal detail', 'owner_id' => 9 ) ),
	'planned_work' => array( array( 'id' => 4, 'title' => 'Service page brief', 'category' => 'content', 'status' => 'planned', 'due_at' => '2026-08-20', 'description' => 'internal detail' ) ),
	'search_impact' => array(
		array( 'id' => 5, 'status' => 'acknowledged', 'title' => 'Landing page', 'target_url' => 'https://client.example/page', 'primary_metric' => 'clicks', 'outcome' => 'positive_signal', 'confidence' => 'medium', 'adjusted_change_percent' => 12.345, 'assessment' => array( 'decision' => 'retain', 'notes' => 'internal assessment note' ), 'updated_at' => '2026-08-03' ),
		array( 'id' => 6, 'status' => 'assessed', 'title' => 'Unapproved study' ),
	),
	'client_actions' => array( array( 'type' => 'awaiting_client', 'title' => 'Confirm service area', 'status' => 'awaiting_client', 'review_url' => 'https://agency.example/wp-admin/' ), array( 'type' => 'editorial_review', 'title' => 'Internal approval' ) ),
	'credentials' => array( 'token' => 'secret' ),
	'internal_notes' => 'secret',
);
$safe = $portal->sanitize_snapshot_payload( $raw, array() );
if ( isset( $safe['credentials'] ) || isset( $safe['internal_notes'] ) || isset( $safe['overview']['internal_margin'] ) || isset( $safe['service_scope']['monthly_fee'] ) ) { $failures[] = 'Sensitive or internal fields survived snapshot sanitisation.'; }
if ( 1 !== count( $safe['approved_reports'] ) || 'approved' !== $safe['approved_reports'][0]['status'] ) { $failures[] = 'Unapproved client reports were not excluded.'; }
if ( isset( $safe['approved_reports'][0]['report'] ) || isset( $safe['approved_reports'][0]['work_delivered'][0]['internal_note'] ) ) { $failures[] = 'Raw report payload or internal work fields were exposed.'; }
if ( 1 !== count( $safe['search_impact'] ) || isset( $safe['search_impact'][0]['notes'] ) || false !== $safe['search_impact'][0]['causal_claim'] ) { $failures[] = 'Search Impact allowlist exposed unacknowledged or internal assessment evidence.'; }
if ( 1 !== count( $safe['client_actions'] ) || isset( $safe['client_actions'][0]['review_url'] ) ) { $failures[] = 'Client actions expose internal approval types or administrative URLs.'; }
if ( true !== $safe['safety']['read_only'] || false !== $safe['safety']['publishes_content'] || false !== $safe['safety']['sends_messages'] ) { $failures[] = 'Portal safety declaration is incorrect.'; }

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-ikon-seo-client-portal.php' );
foreach ( array( 'wp_publish_post', 'wp_update_post', 'wp_delete_post', 'wp_mail', 'wp_insert_post' ) as $needle ) {
	if ( false !== strpos( $source, $needle ) ) { $failures[] = 'Client portal contains prohibited live or communication primitive: ' . $needle; }
}
foreach ( array( 'public_magic_links' => false, 'raw_agency_tables_exposed' => false, 'one_website_per_access_grant' => true ) as $needle => $expected ) {
	if ( false === strpos( $source, "'{$needle}'" ) ) { $failures[] = 'Client portal safeguard declaration missing: ' . $needle; }
}

if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Client Portal permission, tenant-isolation, allowlist and no-live-change tests passed.\n";
