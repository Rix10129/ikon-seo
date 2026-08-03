<?php

defined( 'ABSPATH' ) || exit;

/**
 * Production readiness, compatibility and scheduled-task health checks.
 */
class Ikon_SEO_Production_Health {
	const CRON_HOOK = 'ikon_seo_production_health_weekly';
	const HEARTBEAT_HOOK = 'ikon_seo_production_heartbeat';

	private $history;
	private $logger;

	public function __construct( Ikon_SEO_Workspace_History $history, Ikon_SEO_Logger $logger ) {
		$this->history = $history;
		$this->logger  = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_run' ) );
		add_action( self::HEARTBEAT_HOOK, array( $this, 'heartbeat' ) );
	}

	public function heartbeat() {
		update_option( 'ikon_seo_cron_heartbeat', current_time( 'mysql', true ), false );
	}

	public function scheduled_run() {
		$this->run( 0, 'scheduled' );
		$this->cleanup();
	}

	public function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_system_health_runs';
	}

	public function run( $user_id = 0, $source = 'manual' ) {
		global $wpdb;
		$checks = array();
		$this->add_check( $checks, 'php_version', version_compare( PHP_VERSION, '7.4', '>=' ) ? 'pass' : 'critical', 'PHP version', 'PHP ' . PHP_VERSION, 'Use PHP 7.4 or newer.' );
		$wp_version = get_bloginfo( 'version' );
		$this->add_check( $checks, 'wordpress_version', version_compare( $wp_version, '6.4', '>=' ) ? 'pass' : 'critical', 'WordPress version', 'WordPress ' . $wp_version, 'Use WordPress 6.4 or newer.' );
		$this->add_check( $checks, 'db_component', Ikon_SEO_Plugin::DB_VERSION === (string) get_option( 'ikon_seo_db_version' ) ? 'pass' : 'critical', 'Database migration', 'Installed component ' . sanitize_text_field( get_option( 'ikon_seo_db_version', 'unknown' ) ), 'Run the plugin database upgrade before using scheduled modules.' );

		$memory = $this->memory_limit_bytes();
		$this->add_check( $checks, 'memory_limit', $memory < 134217728 && -1 !== $memory ? 'warning' : 'pass', 'PHP memory limit', -1 === $memory ? 'Unlimited' : size_format( $memory ), 'A limit below 128 MB may be unreliable for large audits.' );

		$expected_tables = $this->expected_tables();
		$missing_tables = array();
		foreach ( $expected_tables as $table ) {
			if ( ! $this->table_exists( $table ) ) {
				$missing_tables[] = $table;
			}
		}
		$this->add_check( $checks, 'database_tables', $missing_tables ? 'critical' : 'pass', 'Database tables', $missing_tables ? implode( ', ', array_map( array( $this, 'short_table_name' ), $missing_tables ) ) : count( $expected_tables ) . ' expected tables found', 'Reactivate the plugin or restore a verified database backup.' );

		$cron_hooks = $this->expected_cron_hooks();
		$missing_cron = array();
		foreach ( $cron_hooks as $hook ) {
			if ( ! wp_next_scheduled( $hook ) ) {
				$missing_cron[] = $hook;
			}
		}
		$this->add_check( $checks, 'cron_schedule', $missing_cron ? 'warning' : 'pass', 'Scheduled tasks', $missing_cron ? implode( ', ', $missing_cron ) : count( $cron_hooks ) . ' expected events scheduled', 'Reactivate the plugin to repair missing scheduled events.' );

		$heartbeat = sanitize_text_field( get_option( 'ikon_seo_cron_heartbeat', '' ) );
		$heartbeat_age = $heartbeat ? time() - strtotime( $heartbeat . ' UTC' ) : PHP_INT_MAX;
		$heartbeat_state = $heartbeat_age > 6 * HOUR_IN_SECONDS ? 'warning' : 'pass';
		$heartbeat_detail = $heartbeat ? 'Last heartbeat: ' . $heartbeat . ' UTC' : 'No heartbeat recorded yet';
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			$heartbeat_detail .= '; WordPress page-load cron is disabled';
		}
		$this->add_check( $checks, 'cron_heartbeat', $heartbeat_state, 'Scheduler heartbeat', $heartbeat_detail, 'Configure a real server cron when page-load cron is disabled or website traffic is low.' );

		$active = (array) get_option( 'active_plugins', array() );
		$rank_math = $this->plugin_active( $active, 'seo-by-rank-math' );
		$yoast = $this->plugin_active( $active, 'wordpress-seo' );
		$this->add_check( $checks, 'seo_plugin_conflict', $rank_math && $yoast ? 'critical' : 'pass', 'SEO plugin conflict', $rank_math && $yoast ? 'Rank Math and Yoast appear active together' : 'No duplicate supported SEO plugin detected', 'Keep only one primary SEO plugin active unless a tested migration requires otherwise.' );

		$cache_plugins = $this->matching_plugins( $active, array( 'litespeed-cache', 'wp-rocket', 'w3-total-cache', 'wp-super-cache', 'sg-cachepress' ) );
		$this->add_check( $checks, 'cache_layer', 'info', 'Cache layer', $cache_plugins ? implode( ', ', $cache_plugins ) : 'No common cache plugin detected', 'Purge all cache layers after approved metadata, schema or redirect changes.' );
		$security_plugins = $this->matching_plugins( $active, array( 'wordfence', 'better-wp-security', 'all-in-one-wp-security-and-firewall', 'sucuri-scanner' ) );
		$this->add_check( $checks, 'security_layer', 'info', 'Security layer', $security_plugins ? implode( ', ', $security_plugins ) : 'No common security plugin detected', 'Confirm the Ikon SEO REST namespace and scheduled requests are not blocked.' );

		$rest_check = $this->loopback_check();
		$this->add_check( $checks, 'rest_loopback', $rest_check['state'], 'REST loopback', $rest_check['detail'], 'Check SSL, firewall, security-plugin and hosting loopback restrictions.' );

		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		$this->add_check( $checks, 'environment', 'production' === $environment ? 'info' : 'pass', 'WordPress environment', ucfirst( sanitize_key( $environment ) ), 'Use staging for upgrade, rollback and destructive-action testing.' );

		$counts = array( 'pass' => 0, 'info' => 0, 'warning' => 0, 'critical' => 0 );
		foreach ( $checks as $check ) {
			$counts[ $check['state'] ] = ( $counts[ $check['state'] ] ?? 0 ) + 1;
		}
		$status = $counts['critical'] ? 'critical' : ( $counts['warning'] ? 'review' : 'ready' );
		$now = current_time( 'mysql', true );
		$wpdb->insert(
			$this->table_name(),
			array(
				'run_hash'    => hash( 'sha256', home_url( '/' ) . '|' . $now . '|' . wp_json_encode( $counts ) ),
				'status'      => $status,
				'checks_json' => wp_json_encode( $checks ),
				'source'      => sanitize_key( $source ),
				'created_by'  => absint( $user_id ),
				'created_at'  => $now,
			)
		);
		update_option( 'ikon_seo_system_health_last_run', $now, false );
		$this->history->add(
			array(
				'category' => 'system',
				'status'   => 'completed',
				'title'    => 'Production health checks completed',
				'summary'  => sprintf( 'System status: %s. %d critical and %d warning checks.', $status, $counts['critical'], $counts['warning'] ),
				'details'  => array( 'status' => $status, 'counts' => $counts, 'source' => $source ),
			),
			'system_health',
			$user_id
		);
		$this->logger->log( 'production_health', 'success', 'Production readiness and compatibility checks completed.' );
		return array( 'status' => $status, 'counts' => $counts, 'checks' => $checks, 'generated_at' => $now );
	}

	public function report( $refresh = false, $user_id = 0 ) {
		global $wpdb;
		if ( $refresh ) {
			return $this->run( $user_id, 'manual' );
		}
		$row = $this->table_exists( $this->table_name() )
			? $wpdb->get_row( "SELECT * FROM {$this->table_name()} ORDER BY created_at DESC, id DESC LIMIT 1", ARRAY_A )
			: null;
		if ( ! $row ) {
			return array(
				'status' => 'not_run',
				'counts' => array(),
				'checks' => array(),
				'generated_at' => '',
				'message' => __( 'Run the production health checks before approving this release for live use.', 'ikon-seo' ),
			);
		}
		$checks = json_decode( (string) $row['checks_json'], true );
		$counts = array( 'pass' => 0, 'info' => 0, 'warning' => 0, 'critical' => 0 );
		foreach ( is_array( $checks ) ? $checks : array() as $check ) {
			$state = sanitize_key( $check['state'] ?? 'info' );
			$counts[ $state ] = ( $counts[ $state ] ?? 0 ) + 1;
		}
		return array(
			'status'       => sanitize_key( $row['status'] ?? 'review' ),
			'counts'       => $counts,
			'checks'       => is_array( $checks ) ? $checks : array(),
			'generated_at' => sanitize_text_field( $row['created_at'] ?? '' ),
			'source'       => sanitize_key( $row['source'] ?? 'manual' ),
		);
	}

	public function cleanup() {
		global $wpdb;
		$days = max( 30, min( 365, absint( Ikon_SEO_Plugin::settings()['production_health_retention_days'] ?? 90 ) ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$deleted = $this->table_exists( $this->table_name() )
			? $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name()} WHERE created_at < %s", $cutoff ) )
			: 0;
		return array( 'deleted_runs' => max( 0, (int) $deleted ), 'retention_days' => $days );
	}

	private function expected_tables() {
		global $wpdb;
		$suffixes = array(
			'ikon_seo_logs', 'ikon_seo_queue', 'ikon_seo_workspace_history',
			'ikon_seo_evidence', 'ikon_seo_search_rows', 'ikon_seo_search_clusters',
			'ikon_seo_technical_urls', 'ikon_seo_internal_links', 'ikon_seo_pagespeed',
			'ikon_seo_recommendations', 'ikon_seo_outcome_snapshots', 'ikon_seo_outcomes',
			'ikon_seo_recovery_checkpoints', 'ikon_seo_indexation_urls',
			'ikon_seo_indexation_runs', 'ikon_seo_system_health_runs',
			'ikon_seo_schema_audits', 'ikon_seo_media_assets', 'ikon_seo_governance_runs',
			'ikon_seo_experiments', 'ikon_seo_experiment_measurements', 'ikon_seo_claims',
			'ikon_seo_revenue_events', 'ikon_seo_international_pages',
			'ikon_seo_server_log_events', 'ikon_seo_server_log_imports',
			'ikon_seo_portfolio_quality_profiles', 'ikon_seo_portfolio_quality_findings',
			'ikon_seo_portfolio_quality_imports',
		);
		return array_map( function( $suffix ) use ( $wpdb ) { return $wpdb->prefix . $suffix; }, $suffixes );
	}

	private function expected_cron_hooks() {
		return array(
			'ikon_seo_daily_monitor', 'ikon_seo_evidence_crawl',
			Ikon_SEO_Search_Intelligence::CRON_HOOK,
			Ikon_SEO_Technical_Intelligence::CRON_HOOK,
			Ikon_SEO_Automation::RUNNER_HOOK,
			Ikon_SEO_Closed_Loop::CRON_HOOK,
			Ikon_SEO_Indexation_Intelligence::CRON_HOOK,
			Ikon_SEO_Structured_Media_Governance::CRON_HOOK,
			Ikon_SEO_Experiments_Claims_Revenue::CRON_HOOK,
			Ikon_SEO_International_Server_Intelligence::CRON_HOOK,
			Ikon_SEO_Portfolio_Quality_Guard::CRON_HOOK,
			self::CRON_HOOK, self::HEARTBEAT_HOOK,
		);
	}

	private function loopback_check() {
		$url = rest_url( 'ikon-seo/v1/openapi' );
		if ( ! wp_http_validate_url( $url ) ) {
			return array( 'state' => 'warning', 'detail' => 'The local REST URL is invalid.' );
		}
		$response = wp_safe_remote_get( $url, array( 'timeout' => 10, 'redirection' => 2, 'limit_response_size' => 524288 ) );
		if ( is_wp_error( $response ) ) {
			return array( 'state' => 'warning', 'detail' => sanitize_text_field( $response->get_error_message() ) );
		}
		$code = wp_remote_retrieve_response_code( $response );
		return array(
			'state'  => 200 === $code ? 'pass' : 'warning',
			'detail' => 'HTTP ' . absint( $code ) . ' from the local Ikon SEO REST schema endpoint.',
		);
	}

	private function plugin_active( array $active, $slug ) {
		foreach ( $active as $plugin ) {
			if ( 0 === strpos( (string) $plugin, $slug . '/' ) ) {
				return true;
			}
		}
		return false;
	}

	private function matching_plugins( array $active, array $slugs ) {
		$matches = array();
		foreach ( $slugs as $slug ) {
			if ( $this->plugin_active( $active, $slug ) ) {
				$matches[] = $slug;
			}
		}
		return $matches;
	}

	private function add_check( array &$checks, $code, $state, $label, $detail, $recommendation ) {
		$checks[] = array(
			'code'           => sanitize_key( $code ),
			'state'          => in_array( $state, array( 'pass', 'info', 'warning', 'critical' ), true ) ? $state : 'info',
			'label'          => sanitize_text_field( $label ),
			'detail'         => sanitize_text_field( $detail ),
			'recommendation' => sanitize_text_field( $recommendation ),
		);
	}

	private function memory_limit_bytes() {
		$value = trim( (string) ini_get( 'memory_limit' ) );
		if ( '-1' === $value ) {
			return -1;
		}
		$unit = strtolower( substr( $value, -1 ) );
		$number = (float) $value;
		if ( 'g' === $unit ) {
			$number *= 1024;
			$unit = 'm';
		}
		if ( 'm' === $unit ) {
			$number *= 1024;
			$unit = 'k';
		}
		if ( 'k' === $unit ) {
			$number *= 1024;
		}
		return (int) $number;
	}

	private function short_table_name( $table ) {
		global $wpdb;
		return preg_replace( '/^' . preg_quote( $wpdb->prefix, '/' ) . '/', '', $table );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
