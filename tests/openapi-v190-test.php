<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'IKON_SEO_VERSION', '2.0.1' );
function rest_url( $path = '' ) { return 'https://example.com/wp-json/' . ltrim( $path, '/' ); }
function untrailingslashit( $value ) { return rtrim( (string) $value, '/\\' ); }
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-rest.php';
$reflection = new ReflectionClass( 'Ikon_SEO_REST' );
$rest = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod( 'openapi_schema' ); $method->setAccessible( true );
$schema = $method->invoke( $rest );
$failures = array(); $operations = 0; $operation_ids = array();
foreach ( (array) ( $schema['paths'] ?? array() ) as $path ) {
	foreach ( $path as $method_name => $operation ) { if ( in_array( $method_name, array( 'get','post','put','patch','delete' ), true ) ) { $operations++; if ( ! empty( $operation['operationId'] ) ) { $operation_ids[] = $operation['operationId']; } } }
}
if ( 30 !== $operations ) { $failures[] = 'Dynamic focused OpenAPI schema must contain exactly 30 operations.'; }
if ( count( $operation_ids ) !== count( array_unique( $operation_ids ) ) ) { $failures[] = 'Dynamic focused schema contains duplicate operation IDs.'; }
if ( empty( $schema['paths']['/search-impact']['post'] ) ) { $failures[] = 'Dynamic Search Impact operation is missing.'; }
if ( ! empty( $schema['paths']['/queue/{id}/complete'] ) ) { $failures[] = 'Dynamic focused schema still exposes queue completion.'; }
if ( empty( $schema['components']['schemas']['SearchImpactSync'] ) ) { $failures[] = 'SearchImpactSync schema is missing.'; }
if ( empty( $schema['paths']['/portfolio-governance']['post'] ) ) { $failures[] = 'Dynamic Portfolio Governance operation is missing.'; }
if ( empty( $schema['components']['schemas']['PortfolioGovernanceSync'] ) ) { $failures[] = 'PortfolioGovernanceSync schema is missing.'; }
if ( empty( $schema['paths']['/pattern-library']['post'] ) ) { $failures[] = 'Dynamic Pattern Library operation is missing.'; }
if ( empty( $schema['components']['schemas']['PatternLibrarySync'] ) ) { $failures[] = 'PatternLibrarySync schema is missing.'; }
if ( '2.0.1' !== ( $schema['info']['version'] ?? '' ) ) { $failures[] = 'Dynamic schema version is incorrect.'; }
if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Dynamic Ikon SEO v2.0.1 OpenAPI schema tests passed.\n";
