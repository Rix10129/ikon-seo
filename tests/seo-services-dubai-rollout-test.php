<?php
$root = dirname( __DIR__ );
$failures = array();
$package = $root . '/production/seo-services-dubai';
$plugin_file = $package . '/ikon-seo-services-dubai-rollout.php';
$template_file = $package . '/page-content.html';
$css_file = $package . '/assets/page.css';

foreach ( array( $plugin_file, $template_file, $css_file ) as $file ) {
	if ( ! is_readable( $file ) ) {
		$failures[] = 'Rollout package file missing: ' . str_replace( $root . '/', '', $file );
	}
}

if ( is_readable( $plugin_file ) ) {
	$plugin = file_get_contents( $plugin_file );
	foreach ( array(
		'Plugin Name: Ikon SEO Services Dubai Rollout',
		"const PAGE_SLUG     = 'seo-services-dubai'",
		"return 'ikondigitals.com' === \$host",
		'Preview New Layout',
		'create_backup',
		'clear_builder_override',
		'Restore Previous Version',
		"'post_status'  => 'draft'",
		"'rank_math_focus_keyword', 'SEO services Dubai'",
	) as $needle ) {
		if ( false === strpos( $plugin, $needle ) ) {
			$failures[] = 'Rollout safety/integration marker missing: ' . $needle;
		}
	}
	if ( false !== strpos( $plugin, 'rawurlencode' ) ) {
		$failures[] = 'Error messages should not be pre-encoded before add_query_arg().';
	}
}

if ( is_readable( $template_file ) ) {
	$template = file_get_contents( $template_file );
	foreach ( array(
		'<h1>SEO Services in Dubai',
		'{{CONTACT_URL}}',
		'{{RESULTS_URL}}',
		'FWF Safe Driver',
		'ZeroSync Accountants',
		'+140%',
		'+147%',
		'Google Maps',
		'AI Search',
		'Can you guarantee a #1 Google ranking?',
	) as $needle ) {
		if ( false === strpos( $template, $needle ) ) {
			$failures[] = 'Production page content marker missing: ' . $needle;
		}
	}
	if ( false !== stripos( $template, 'guaranteed ranking' ) ) {
		$failures[] = 'Production template contains unsupported guaranteed-ranking language.';
	}
}

if ( is_readable( $css_file ) ) {
	$css = file_get_contents( $css_file );
	foreach ( array( '.ikon-sd {', '@media (max-width: 1100px)', '@media (max-width: 860px)', '@media (max-width: 620px)', 'overflow: clip' ) as $needle ) {
		if ( false === strpos( $css, $needle ) ) {
			$failures[] = 'Responsive/scoped CSS marker missing: ' . $needle;
		}
	}
	if ( preg_match( '/(^|\})\s*(body|html|h1|h2|h3|p|a)\s*\{/m', $css ) ) {
		$failures[] = 'Production stylesheet contains an unscoped global selector.';
	}
}

$core = file_get_contents( $root . '/ikon-seo.php' );
if ( false === strpos( $core, 'Version: 2.0.1' ) || false === strpos( $core, "define( 'IKON_SEO_VERSION', '2.0.1' )" ) ) {
	$failures[] = 'Standalone rollout must not change the signed Ikon SEO 2.0.1 core version.';
}
if ( false !== strpos( $core, 'class-ikon-seo-production-pages.php' ) ) {
	$failures[] = 'Standalone rollout must not be wired into Ikon SEO core.';
}

if ( $failures ) {
	fwrite( STDERR, implode( "\n", $failures ) . "\n" );
	exit( 1 );
}

echo "SEO Services Dubai standalone rollout package static safety checks passed.\n";
