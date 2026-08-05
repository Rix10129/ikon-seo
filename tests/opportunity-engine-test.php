<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );

function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '_', (string) $value ) ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function esc_url_raw( $value ) { return filter_var( (string) $value, FILTER_VALIDATE_URL ) ? (string) $value : ''; }
function url_to_postid( $url ) { return false !== strpos( $url, '/known-page/' ) ? 42 : 0; }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function untrailingslashit( $value ) { return rtrim( (string) $value, '/\\' ); }
function __( $value ) { return $value; }

require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-opportunity-engine.php';

$reflection = new ReflectionClass( 'Ikon_SEO_Opportunity_Engine' );
$engine = $reflection->newInstanceWithoutConstructor();
$candidate = $reflection->getMethod( 'candidate' );
$candidate->setAccessible( true );
$header = $reflection->getMethod( 'normalize_header' );
$header->setAccessible( true );
$record = $reflection->getMethod( 'normalize_import_record' );
$record->setAccessible( true );

$high = $candidate->invoke( $engine, array(
	'type' => 'striking_distance',
	'category' => 'search_growth',
	'source' => 'search_console',
	'title' => 'Test opportunity',
	'url' => 'https://example.com/known-page/',
	'keyword' => 'office cleaning doha',
	'impact' => 90,
	'confidence' => 'high',
	'effort' => 'low',
	'risk' => 'low',
	'observed_at' => gmdate( 'Y-m-d' ),
) );
$high_risk = $candidate->invoke( $engine, array(
	'type' => 'cannibalisation',
	'category' => 'architecture',
	'source' => 'search_console',
	'title' => 'Risky opportunity',
	'url' => 'https://example.com/known-page/',
	'keyword' => 'office cleaning doha',
	'impact' => 90,
	'confidence' => 'high',
	'effort' => 'high',
	'risk' => 'high',
	'observed_at' => gmdate( 'Y-m-d' ),
) );

$failures = array();
if ( 42 !== $high['post_id'] ) { $failures[] = 'URL-to-post mapping failed.'; }
if ( $high['priority'] <= $high_risk['priority'] ) { $failures[] = 'Effort and risk did not reduce priority.'; }
if ( 'search_console' !== $high['primary_source'] ) { $failures[] = 'Source sanitisation failed.'; }
if ( 'search_volume' !== $header->invoke( $engine, 'Search Volume' ) ) { $failures[] = 'CSV header mapping failed.'; }
if ( 'keyword_difficulty' !== $header->invoke( $engine, 'KD %' ) ) { $failures[] = 'Provider difficulty header mapping failed.'; }

$normalized = $record->invoke( $engine, array(
	'query' => ' carpet cleaning qatar ',
	'landing_page' => 'https://example.com/carpet-cleaning/',
	'volume' => '390',
	'kd' => '37',
	'rank' => '11',
	'features' => 'local pack,people also ask',
	'date' => '2026-08-04',
), 'semrush' );
if ( 'carpet cleaning qatar' !== $normalized['keyword'] ) { $failures[] = 'Keyword normalisation failed.'; }
if ( 390.0 !== $normalized['search_volume'] ) { $failures[] = 'Volume normalisation failed.'; }
if ( 2 !== count( $normalized['serp_features'] ) ) { $failures[] = 'SERP feature parsing failed.'; }

if ( $failures ) {
	fwrite( STDERR, implode( "\n", $failures ) . "\n" );
	exit( 1 );
}

echo "Opportunity Engine scoring and import-normalisation tests passed.\n";
