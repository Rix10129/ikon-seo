<?php
$root = dirname( __DIR__ );
$failures = array();
$manifest_file = $root . '/release/manifest.json';
$signature_file = $root . '/release/manifest.sig';
$public_key_file = $root . '/release/public-key.pem';
foreach ( array( $manifest_file, $signature_file, $public_key_file ) as $file ) {
	if ( ! is_readable( $file ) ) { $failures[] = 'Release verification file is missing: ' . basename( $file ); }
}
if ( ! $failures ) {
	$raw = file_get_contents( $manifest_file );
	$manifest = json_decode( $raw, true );
	if ( ! is_array( $manifest ) || '2.0.1' !== ( $manifest['release'] ?? '' ) || '40.0' !== ( $manifest['database_version'] ?? '' ) ) { $failures[] = 'Release manifest metadata is invalid.'; }
	$signature = base64_decode( trim( file_get_contents( $signature_file ) ), true );
	$public_key = openssl_pkey_get_public( file_get_contents( $public_key_file ) );
	if ( false === $signature || false === $public_key || 1 !== openssl_verify( $raw, $signature, $public_key, OPENSSL_ALGO_SHA256 ) ) { $failures[] = 'Detached RSA-SHA256 manifest signature is invalid.'; }
	foreach ( (array) ( $manifest['files'] ?? array() ) as $relative => $expected ) {
		$file = $root . '/' . $relative;
		if ( ! is_file( $file ) ) { $failures[] = 'Manifest file is missing: ' . $relative; continue; }
		if ( ! hash_equals( (string) $expected, hash_file( 'sha256', $file ) ) ) { $failures[] = 'Manifest hash mismatch: ' . $relative; }
	}
}
if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Signed release manifest and packaged file hashes passed.\n";
