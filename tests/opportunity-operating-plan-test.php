<?php

define( 'ABSPATH', __DIR__ . '/' );
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '_', (string) $value ) ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function esc_url_raw( $value ) { return filter_var( (string) $value, FILTER_VALIDATE_URL ) ? (string) $value : ''; }
function untrailingslashit( $value ) { return rtrim( (string) $value, '/\\' ); }

require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-closed-loop.php';

$reflection = new ReflectionClass( 'Ikon_SEO_Closed_Loop' );
$closed_loop = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod( 'from_planned_opportunities' );
$method->setAccessible( true );

$existing = array(
	array(
		'post_id' => 42,
		'target_url' => 'https://example.com/office-cleaning/',
		'category' => 'search_growth',
	),
);
$report = array(
	'opportunities' => array(
		array(
			'id' => 10,
			'status' => 'planned',
			'post_id' => 42,
			'target_url' => 'https://example.com/office-cleaning/',
			'category' => 'search_growth',
			'type' => 'striking_distance',
			'title' => 'Duplicate page opportunity',
			'priority' => 80,
			'confidence' => 'high',
		),
		array(
			'id' => 11,
			'status' => 'reviewed',
			'target_url' => 'https://example.com/reviewed-only/',
			'category' => 'content_gap',
			'type' => 'keyword_gap',
			'title' => 'Reviewed but not planned',
		),
		array(
			'id' => 12,
			'status' => 'planned',
			'post_id' => 77,
			'target_url' => 'https://example.com/carpet-cleaning/',
			'category' => 'content_gap',
			'type' => 'keyword_gap',
			'title' => 'Planned carpet cleaning opportunity',
			'summary' => 'Approved provider evidence supports a review.',
			'priority' => 74,
			'confidence' => 'medium',
			'effort' => 'high',
			'risk' => 'low',
			'actions' => array( 'Prepare an evidence-linked improvement brief.' ),
		),
	),
);

$items = $method->invoke( $closed_loop, $report, $existing );
$failures = array();
if ( 1 !== count( $items ) ) { $failures[] = 'Only one non-duplicate planned opportunity should flow into the plan.'; }
$item = $items[0] ?? array();
if ( 'opportunity_engine' !== ( $item['source_module'] ?? '' ) ) { $failures[] = 'Opportunity Engine source was not retained.'; }
if ( empty( $item['approval_required'] ) ) { $failures[] = 'Operating Plan handoff must remain approval-required.'; }
if ( 5 !== ( $item['effort'] ?? 0 ) ) { $failures[] = 'High effort was not mapped correctly.'; }
if ( 12 !== ( $item['evidence']['opportunity_id'] ?? 0 ) ) { $failures[] = 'Original opportunity ID was not retained as evidence.'; }

if ( $failures ) {
	fwrite( STDERR, implode( "\n", $failures ) . "\n" );
	exit( 1 );
}

echo "Opportunity Engine Operating Plan handoff tests passed.\n";
