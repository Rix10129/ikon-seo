<?php
/**
 * Plugin Name: Ikon SEO
 * Plugin URI: https://ikondigitals.com/
 * Description: Closed-loop SEO operations with portfolio quality safeguards, international targeting, privacy-preserving evidence, controlled experiments, governance and approval-first improvements.
 * Version: 1.5.0
 * Author: Ikon Digitals
 * Author URI: https://ikondigitals.com/
 * Text Domain: ikon-seo
 * Requires at least: 6.4
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'IKON_SEO_VERSION', '1.5.0' );
define( 'IKON_SEO_FILE', __FILE__ );
define( 'IKON_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'IKON_SEO_URL', plugin_dir_url( __FILE__ ) );

require_once IKON_SEO_DIR . 'includes/class-ikon-seo-logger.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-agency.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-connection.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-auth.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-crypto.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-profile.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-local.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-gbp.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-validator.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-renderer.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-schema.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-quality.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-inventory.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-rank-math.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-image-audit.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-schema-governance.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-media-governance.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-structured-media-governance.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-experiments-claims-revenue.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-international-server-intelligence.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-portfolio-quality-guard.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-redirect-audit.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-media.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-workflow.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-migration.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-search-console.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-search-intelligence.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-technical-intelligence.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-indexation-intelligence.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-competitor-content-intelligence.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-authority-intelligence.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-strategy.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-automation.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-publisher-intelligence.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-local-growth.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-agency-command-centre.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-visibility-brand-intelligence.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-closed-loop.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-production-health.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-analytics.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-crawler.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-diagnostics.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-queue.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-monitor.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-workspace-history.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-rest.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-admin.php';
require_once IKON_SEO_DIR . 'includes/class-ikon-seo-plugin.php';

register_activation_hook( __FILE__, array( 'Ikon_SEO_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Ikon_SEO_Plugin', 'deactivate' ) );

function ikon_seo() {
	return Ikon_SEO_Plugin::instance();
}

ikon_seo();
