<?php

defined( 'ABSPATH' ) || exit;

final class Ikon_SEO_Plugin {
	const OPTION_KEY = 'ikon_seo_settings';
	const DB_VERSION = '25.0';

	private static $instance;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$logger     = new Ikon_SEO_Logger();
		$connection = new Ikon_SEO_Connection();
		$auth       = new Ikon_SEO_Auth( $connection );
		$crypto    = new Ikon_SEO_Crypto();
		$profile   = new Ikon_SEO_Profile();
		$local     = new Ikon_SEO_Local( $profile, $logger );
		$local->register_hooks();
		$gbp       = new Ikon_SEO_GBP( $crypto, $logger, $local, $profile );
		$gbp->register_hooks();
		$validator = new Ikon_SEO_Validator( $profile, $local );
		$renderer  = new Ikon_SEO_Renderer();
		$schema    = new Ikon_SEO_Schema( $profile, $local );
		$quality   = new Ikon_SEO_Quality( $profile, $local );
		$inventory = new Ikon_SEO_Inventory();
		$rank_math = new Ikon_SEO_Rank_Math( $inventory );
		$image_audit = new Ikon_SEO_Image_Audit();
		$redirect_audit = new Ikon_SEO_Redirect_Audit( $inventory );
		$media     = new Ikon_SEO_Media();
		$workflow  = new Ikon_SEO_Workflow( $logger, $schema, $quality, $local );
		$migration = new Ikon_SEO_Migration( $logger, $inventory, $local );
		$search_console = new Ikon_SEO_Search_Console( $crypto, $logger );
		$search_intelligence = new Ikon_SEO_Search_Intelligence( $search_console, $profile, $logger );
		$search_intelligence->register_hooks();
		$analytics      = new Ikon_SEO_Analytics( $crypto, $logger );
		$crawler        = new Ikon_SEO_Crawler( $logger );
		$crawler->register_hooks();
		$technical      = new Ikon_SEO_Technical_Intelligence( $crawler, $crypto, $logger );
		$technical->register_hooks();
		$history        = new Ikon_SEO_Workspace_History( $profile );
		$schema_governance = new Ikon_SEO_Schema_Governance( $history, $logger );
		$media_governance = new Ikon_SEO_Media_Governance( $history, $logger );
		$structured_media_governance = new Ikon_SEO_Structured_Media_Governance( $schema_governance, $media_governance, $history, $logger );
		$structured_media_governance->register_hooks();
		$experiments_claims_revenue = new Ikon_SEO_Experiments_Claims_Revenue( $search_intelligence, $analytics, $history, $logger );
		$experiments_claims_revenue->register_hooks();
		$international_server = new Ikon_SEO_International_Server_Intelligence( $inventory, $history, $logger );
		$international_server->register_hooks();
		$indexation     = new Ikon_SEO_Indexation_Intelligence( $search_console, $inventory, $technical, $history, $logger );
		$indexation->register_hooks();
		$production_health = new Ikon_SEO_Production_Health( $history, $logger );
		$production_health->register_hooks();
		$strategy       = new Ikon_SEO_Strategy( $profile, $history, $logger );
		$publisher      = new Ikon_SEO_Publisher_Intelligence( $profile, $strategy, $inventory, $search_intelligence, $history, $logger );
		$publisher->register_hooks();
		$portfolio_quality_guard = new Ikon_SEO_Portfolio_Quality_Guard( $profile, $publisher, $history, $logger );
		$portfolio_quality_guard->register_hooks();
		$competitor_content = new Ikon_SEO_Competitor_Content_Intelligence( $profile, $inventory, $search_intelligence, $history, $logger );
		$authority       = new Ikon_SEO_Authority_Intelligence( $profile, $inventory, $history, $logger );
		$local_growth    = new Ikon_SEO_Local_Growth( $profile, $local, $gbp, $analytics, $competitor_content, $authority, $strategy, $inventory, $history, $logger );
		$local_growth->register_hooks();
		$diagnostics    = new Ikon_SEO_Diagnostics( $crawler, $inventory, $rank_math, $search_console, $search_intelligence, $analytics, $technical, $authority, $strategy );
		$diagnostics->register_hooks();
		$queue          = new Ikon_SEO_Queue( $profile );
		$monitor        = new Ikon_SEO_Monitor( $search_console, $logger );
		$monitor->register_hooks();
		$automation     = new Ikon_SEO_Automation( $profile, $strategy, $inventory, $crawler, $diagnostics, $search_intelligence, $technical, $analytics, $local_growth, $monitor, $history, $logger );
		$automation->register_hooks();
		$visibility_brand = new Ikon_SEO_Visibility_Brand_Intelligence( $profile, $search_intelligence, $local_growth, $authority, $competitor_content, $history, $logger );
		$visibility_brand->register_hooks();
		$closed_loop      = new Ikon_SEO_Closed_Loop( $profile, $strategy, $diagnostics, $search_intelligence, $analytics, $technical, $indexation, $competitor_content, $authority, $publisher, $local_growth, $visibility_brand, $automation, $history, $crypto, $logger );
		$closed_loop->register_hooks();
		$agency_command  = new Ikon_SEO_Agency_Command_Centre( $profile, $strategy, $inventory, $workflow, $diagnostics, $search_intelligence, $technical, $indexation, $production_health, $analytics, $automation, $publisher, $local_growth, $visibility_brand, $closed_loop, $portfolio_quality_guard, $queue, $monitor, $history, $crypto, $logger );
		$agency_command->register_hooks();

		new Ikon_SEO_REST( $auth, $connection, $profile, $validator, $renderer, $schema, $quality, $inventory, $rank_math, $image_audit, $redirect_audit, $media, $workflow, $migration, $search_console, $search_intelligence, $analytics, $crawler, $technical, $indexation, $production_health, $competitor_content, $authority, $strategy, $publisher, $diagnostics, $queue, $monitor, $automation, $history, $local_growth, $visibility_brand, $closed_loop, $agency_command, $structured_media_governance, $experiments_claims_revenue, $international_server, $portfolio_quality_guard, $local, $gbp, $logger );

		if ( is_admin() ) {
			new Ikon_SEO_Admin( $logger, $connection, $profile, $inventory, $rank_math, $image_audit, $redirect_audit, $workflow, $migration, $search_console, $search_intelligence, $analytics, $crawler, $technical, $indexation, $production_health, $competitor_content, $authority, $strategy, $publisher, $diagnostics, $queue, $monitor, $automation, $history, $local_growth, $visibility_brand, $closed_loop, $agency_command, $structured_media_governance, $experiments_claims_revenue, $international_server, $portfolio_quality_guard, $local, $gbp );
		}

		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_upgrade' ), 20 );
		add_action( 'wp_head', array( $schema, 'print_fallback_graph' ), 30 );
		add_filter( 'rank_math/json_ld', array( $schema, 'merge_rank_math_graph' ), 99, 2 );
		add_filter( 'language_attributes', array( $this, 'filter_language_attributes' ), 10, 2 );
	}

	public static function cron_schedules( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'ikon-seo' ),
			);
		}
		return $schedules;
	}


	public static function defaults() {
		$language = Ikon_SEO_Profile::locale();
		return array(
			'profile_version'    => Ikon_SEO_Profile::VERSION,
			'profile_configured' => 0,
			'profile_home_url'   => home_url( '/' ),
			'site_name'          => get_bloginfo( 'name' ),
			'industry'           => 'general',
			'business_entity_type'=> 'Organization',
			'target_market'      => '',
			'target_locations'   => '',
			'default_language'   => $language,
			'supported_languages'=> $language,
			'default_currency'   => 'USD',
			'business_phone'     => '',
			'contact_email'      => sanitize_email( get_option( 'admin_email' ) ),
			'whatsapp_url'       => '',
			'primary_color'      => '#153A5B',
			'secondary_color'    => '#0B2540',
			'accent_color'       => '#1A9B7A',
			'heading_color'      => '#102A43',
			'text_color'         => '#334E68',
			'surface_color'      => '#F3F7FA',
			'content_width'      => 1180,
			'builder_preference' => 'auto',
			'seo_plugin_preference'=> 'auto',
			'content_rules'      => '',
			'cta_templates'      => '',
			'author_id'          => 0,
			'draft_only'         => 1,
			'allow_live_updates' => 0,
			'rate_limit'         => 60,
			'max_payload_kb'     => 1024,
			'inventory_limit'    => 300,
			'image_audit_limit'  => 300,
			'key_scopes'         => array( 'read', 'draft' ),
			'remote_actions'     => 1,
			'remote_merge'       => 0,
			'semantic_faq'       => 0,
			'verified_business'  => 0,
			'allow_entity_schema'=> 0,
			'require_profile_match'=> 1,
			'business_url'       => home_url( '/' ),
			'business_logo'      => '',
			'price_range'        => '',
			'address_street'     => '',
			'address_locality'   => '',
			'address_region'     => '',
			'address_postal'     => '',
			'address_country'    => '',
			'latitude'           => '',
			'longitude'          => '',
			'opening_hours'      => '',
			'allowed_media_hosts'=> wp_parse_url( home_url( '/' ), PHP_URL_HOST ),
			'strategy_configured' => 0,
			'website_mode' => 'local_business',
			'strategy_primary_goal' => 'leads',
			'strategy_secondary_goals' => '',
			'strategy_target_audience' => '',
			'strategy_value_proposition' => '',
			'strategy_main_offerings' => '',
			'strategy_excluded_topics' => '',
			'strategy_primary_conversions' => '',
			'strategy_monetization_model' => 'service_revenue',
			'strategy_publishing_capacity' => 4,
			'strategy_content_owner' => '',
			'strategy_review_owner' => '',
			'strategy_editorial_standards' => '',
			'strategy_evidence_requirements' => '',
			'strategy_author_policy' => '',
			'strategy_disclosure_policy' => '',
			'strategy_success_metrics' => '',
			'strategy_automation_level' => 'drafts_only',
			'strategy_risk_tolerance' => 'balanced',
			'strategy_quality_gate_threshold' => 80,
			'strategy_local_lead_channels' => "Phone calls
Quote forms
WhatsApp enquiries",
			'strategy_local_review_target' => 0,
			'strategy_local_service_area_policy' => '',
			'strategy_local_proof_requirements' => '',
			'strategy_editorial_primary_topics' => '',
			'strategy_editorial_hubs' => '',
			'strategy_editorial_refresh_days' => 180,
			'strategy_editorial_originality' => '',
			'strategy_ecommerce_categories' => '',
			'strategy_ecommerce_conversion_events' => "Purchase
Add to cart
Begin checkout",
			'strategy_ecommerce_trust_requirements' => '',
			'strategy_ecommerce_feed_policy' => '',
			'strategy_last_updated' => '',
			'component_version'  => '25.0',
			'connection_verified_at' => '',
			'connection_last_seen_at'=> '',
			'token_hash'         => '',
			'token_hint'         => '',
			'gsc_client_id'      => '',
			'gsc_client_secret'  => '',
			'gsc_refresh_token'  => '',
			'gsc_property'       => '',
			'gsc_last_error'     => '',
			'search_intelligence_enabled' => 1,
			'search_intelligence_days' => 28,
			'search_intelligence_max_rows' => 50000,
			'search_intelligence_min_impressions' => 20,
			'search_intelligence_decay_percent' => 30,
			'search_intelligence_last_sync' => '',
			'search_intelligence_last_error' => '',
			'search_intelligence_truncated' => 0,
			'technical_intelligence_enabled' => 1,
			'technical_max_urls' => 5000,
			'technical_check_batch_size' => 20,
			'competitor_content_enabled' => 1,
			'competitor_research_stale_days' => 60,
			'competitor_min_pages' => 3,
			'authority_intelligence_enabled' => 1,
			'authority_import_max_rows' => 20000,
			'pagespeed_api_key' => '',
			'pagespeed_last_error' => '',
			'ga_client_id'       => '',
			'ga_client_secret'   => '',
			'ga_refresh_token'   => '',
			'ga_property'        => '',
			'ga_last_error'      => '',
			'crawler_enabled'    => 1,
			'crawler_batch_size' => 10,
			'crawler_stale_days' => 14,
			'gbp_availability'   => 'unknown',
			'gbp_client_id'      => '',
			'gbp_client_secret'  => '',
			'gbp_refresh_token'  => '',
			'gbp_account'        => '',
			'gbp_last_error'     => '',
			'local_module_enabled'=> 1,
			'local_similarity_threshold'=> 78,
			'citation_review_days'=> 180,
			'local_growth_enabled' => 1,
			'local_review_response_days' => 3,
			'local_citation_target_percent' => 90,
			'local_conversion_days' => 30,
			'local_prominence_stale_days' => 90,
			'local_growth_last_sync' => '',
			'local_growth_last_error' => '',
			'monitoring_enabled' => 0,
			'default_review_days'=> 180,
			'review_alert_days'  => 14,
			'performance_drop_percent' => 30,
			'performance_min_impressions'=> 50,
			'workflow_automation_enabled' => 1,
			'workflow_daily_briefing_enabled' => 1,
			'workflow_weekly_briefing_enabled' => 1,
			'workflow_runner_batch' => 3,
			'workflow_retry_limit' => 3,
			'publisher_intelligence_enabled' => 1,
			'publisher_portfolio_similarity_threshold' => 70,
			'publisher_local_similarity_threshold' => 82,
			'publisher_signature_export_limit' => 500,
			'visibility_brand_enabled' => 1,
			'visibility_brand_name' => get_bloginfo( 'name' ),
			'visibility_brand_aliases' => '',
			'visibility_competitors' => '',
			'visibility_observation_stale_days' => 45,
			'visibility_mention_review_days' => 30,
			'visibility_min_confidence' => 'medium',
			'visibility_last_snapshot' => '',
			'indexation_intelligence_enabled' => 1,
			'indexation_daily_budget' => 100,
			'indexation_inspection_batch' => 10,
			'indexation_seed_batch' => 500,
			'indexation_stale_days' => 14,
			'indexation_reinspect_after_change' => 1,
			'indexation_history_retention_days' => 180,
			'production_health_retention_days' => 90,
			'structured_media_governance_enabled' => 1,
			'schema_governance_batch_size' => 10,
			'schema_governance_stale_days' => 30,
			'media_governance_batch_size' => 50,
			'media_governance_stale_days' => 30,
			'media_governance_large_file_kb' => 500,
			'media_governance_alt_max_chars' => 160,
			'media_governance_require_source_records' => 0,
			'media_governance_file_hashes' => 1,
			'governance_retention_days' => 180,
			'experiments_claims_revenue_enabled' => 1,
			'experiment_minimum_days' => 28,
			'experiment_minimum_observations' => 100,
			'experiment_change_threshold_percent' => 10,
			'claim_default_review_days' => 180,
			'claim_high_risk_review_days' => 30,
			'revenue_default_currency' => 'USD',
			'revenue_reporting_days' => 30,
			'experiments_claims_revenue_retention_days' => 730,
			'international_server_enabled' => 1,
			'international_audit_batch' => 5,
			'international_stale_days' => 30,
			'international_locale_map' => '',
			'international_x_default_url' => '',
			'server_log_retention_days' => 180,
			'server_log_max_rows' => 20000,
			'server_log_verify_crawlers' => 0,
			'server_log_slow_ms' => 1500,
			'server_log_store_query_keys' => 1,
			'portfolio_quality_enabled' => 1,
			'portfolio_quality_scan_batch' => 25,
			'portfolio_quality_content_threshold' => 72,
			'portfolio_quality_topic_threshold' => 80,
			'portfolio_quality_template_threshold' => 90,
			'portfolio_quality_thin_words' => 450,
			'portfolio_quality_cluster_min' => 4,
			'portfolio_quality_block_review_ready' => 1,
			'portfolio_quality_media_hashing' => 1,
			'portfolio_quality_retention_days' => 365,
			'closed_loop_enabled' => 1,
			'closed_loop_safe_mode' => 0,
			'closed_loop_measurement_batch' => 5,
			'closed_loop_measurement_windows' => '14,28,60,90',
			'closed_loop_auto_plan_refresh' => 1,
			'agency_command_enabled' => 0,
			'agency_command_refresh_hours' => 6,
			'agency_command_batch_size' => 10,
			'agency_command_currency' => 'USD',
			'agency_command_default_budget' => 0,
			'agency_command_brand_name' => 'Ikon SEO',
			'agency_command_logo_url' => '',
			'agency_command_client_footer' => '',
		);
	}

	public static function settings() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	public static function activate() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'ikon_seo_logs';
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_id varchar(64) NOT NULL,
			action varchar(40) NOT NULL,
			status varchar(20) NOT NULL,
			post_id bigint(20) unsigned DEFAULT NULL,
			source_id bigint(20) unsigned DEFAULT NULL,
			message text DEFAULT NULL,
			payload_hash varchar(64) DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY action (action),
			KEY status (status),
			KEY post_id (post_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );

		$queue_table = $wpdb->prefix . 'ikon_seo_queue';
		$queue_sql   = "CREATE TABLE {$queue_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			batch_id varchar(64) NOT NULL,
			profile_id varchar(64) NOT NULL,
			keyword varchar(255) NOT NULL,
			service varchar(255) NOT NULL DEFAULT '',
			location varchar(255) NOT NULL DEFAULT '',
			page_type varchar(30) NOT NULL DEFAULT 'service',
			language varchar(16) NOT NULL DEFAULT 'en',
			template_hint varchar(255) NOT NULL DEFAULT '',
			desired_slug varchar(200) NOT NULL DEFAULT '',
			source_page_id bigint(20) unsigned NOT NULL DEFAULT 0,
			priority smallint(5) unsigned NOT NULL DEFAULT 50,
			status varchar(20) NOT NULL DEFAULT 'planned',
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			claim_token_hash varchar(64) NOT NULL DEFAULT '',
			claimed_at datetime DEFAULT NULL,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			last_error text DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY batch_id (batch_id),
			KEY profile_status (profile_id,status),
			KEY post_id (post_id),
			KEY priority (priority)
		) {$charset_collate};";

		dbDelta( $queue_sql );

		$locations_table = $wpdb->prefix . 'ikon_seo_locations';
		$locations_sql   = "CREATE TABLE {$locations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			location_type varchar(20) NOT NULL DEFAULT 'storefront',
			business_name varchar(255) NOT NULL,
			location_label varchar(255) NOT NULL DEFAULT '',
			entity_type varchar(80) NOT NULL DEFAULT 'LocalBusiness',
			phone varchar(80) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL DEFAULT '',
			website_url text DEFAULT NULL,
			appointment_url text DEFAULT NULL,
			whatsapp_url text DEFAULT NULL,
			address_street varchar(255) NOT NULL DEFAULT '',
			address_locality varchar(190) NOT NULL DEFAULT '',
			address_region varchar(190) NOT NULL DEFAULT '',
			address_postal varchar(40) NOT NULL DEFAULT '',
			address_country varchar(8) NOT NULL DEFAULT '',
			latitude varchar(40) NOT NULL DEFAULT '',
			longitude varchar(40) NOT NULL DEFAULT '',
			opening_hours longtext DEFAULT NULL,
			special_hours longtext DEFAULT NULL,
			primary_category varchar(255) NOT NULL DEFAULT '',
			additional_categories longtext DEFAULT NULL,
			service_areas longtext DEFAULT NULL,
			services longtext DEFAULT NULL,
			place_id varchar(190) NOT NULL DEFAULT '',
			gbp_account_name varchar(190) NOT NULL DEFAULT '',
			gbp_location_name varchar(190) NOT NULL DEFAULT '',
			map_url text DEFAULT NULL,
			price_range varchar(40) NOT NULL DEFAULT '',
			image_url text DEFAULT NULL,
			logo_url text DEFAULT NULL,
			same_as longtext DEFAULT NULL,
			page_id bigint(20) unsigned NOT NULL DEFAULT 0,
			has_customer_location tinyint(1) unsigned NOT NULL DEFAULT 0,
			verified tinyint(1) unsigned NOT NULL DEFAULT 0,
			is_primary tinyint(1) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY profile_status (profile_id,status),
			KEY profile_primary (profile_id,is_primary),
			KEY page_id (page_id),
			KEY place_id (place_id)
		) {$charset_collate};";
		dbDelta( $locations_sql );

		$citations_table = $wpdb->prefix . 'ikon_seo_citations';
		$citations_sql   = "CREATE TABLE {$citations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			location_id bigint(20) unsigned NOT NULL DEFAULT 0,
			directory_name varchar(255) NOT NULL,
			listing_url text DEFAULT NULL,
			business_name varchar(255) NOT NULL DEFAULT '',
			address text DEFAULT NULL,
			phone varchar(80) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'pending',
			login_owner varchar(190) NOT NULL DEFAULT '',
			last_checked date DEFAULT NULL,
			next_review date DEFAULT NULL,
			duplicate_warning tinyint(1) unsigned NOT NULL DEFAULT 0,
			correction_required tinyint(1) unsigned NOT NULL DEFAULT 0,
			notes text DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY profile_location (profile_id,location_id),
			KEY profile_status (profile_id,status),
			KEY next_review (next_review)
		) {$charset_collate};";
		dbDelta( $citations_sql );

		$ranks_table = $wpdb->prefix . 'ikon_seo_local_ranks';
		$ranks_sql   = "CREATE TABLE {$ranks_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			location_id bigint(20) unsigned NOT NULL DEFAULT 0,
			keyword varchar(255) NOT NULL,
			search_location varchar(255) NOT NULL,
			device varchar(20) NOT NULL DEFAULT 'mobile',
			search_engine varchar(20) NOT NULL DEFAULT 'google',
			organic_position decimal(7,2) DEFAULT NULL,
			local_pack_position decimal(7,2) DEFAULT NULL,
			previous_organic decimal(7,2) DEFAULT NULL,
			previous_local_pack decimal(7,2) DEFAULT NULL,
			competitors longtext DEFAULT NULL,
			checked_date date NOT NULL,
			source varchar(80) NOT NULL DEFAULT 'manual_import',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY profile_keyword (profile_id,keyword(100)),
			KEY profile_location (profile_id,location_id),
			KEY checked_date (checked_date)
		) {$charset_collate};";
		dbDelta( $ranks_sql );

		$gbp_drafts_table = $wpdb->prefix . 'ikon_seo_gbp_drafts';
		$gbp_drafts_sql   = "CREATE TABLE {$gbp_drafts_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			location_id bigint(20) unsigned NOT NULL DEFAULT 0,
			draft_type varchar(30) NOT NULL,
			remote_resource varchar(255) NOT NULL,
			content longtext NOT NULL,
			action_data longtext DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'draft',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			approved_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			sent_at datetime DEFAULT NULL,
			last_error text DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY profile_status (profile_id,status),
			KEY profile_location (profile_id,location_id),
			KEY draft_type (draft_type)
		) {$charset_collate};";
		dbDelta( $gbp_drafts_sql );


		$evidence_table = $wpdb->prefix . 'ikon_seo_evidence';
		$evidence_sql   = "CREATE TABLE {$evidence_table} (
			post_id bigint(20) unsigned NOT NULL,
			url_hash char(64) NOT NULL,
			url text NOT NULL,
			final_url text DEFAULT NULL,
			status_code smallint(5) unsigned NOT NULL DEFAULT 0,
			content_type varchar(190) NOT NULL DEFAULT '',
			response_ms int(10) unsigned NOT NULL DEFAULT 0,
			indexable tinyint(1) unsigned NOT NULL DEFAULT 0,
			robots varchar(255) NOT NULL DEFAULT '',
			canonical text DEFAULT NULL,
			rendered_title text DEFAULT NULL,
			rendered_description text DEFAULT NULL,
			h1_count smallint(5) unsigned NOT NULL DEFAULT 0,
			word_count int(10) unsigned NOT NULL DEFAULT 0,
			internal_links int(10) unsigned NOT NULL DEFAULT 0,
			external_links int(10) unsigned NOT NULL DEFAULT 0,
			image_count int(10) unsigned NOT NULL DEFAULT 0,
			missing_alt int(10) unsigned NOT NULL DEFAULT 0,
			issue_count int(10) unsigned NOT NULL DEFAULT 0,
			evidence_json longtext DEFAULT NULL,
			diagnostics_json longtext DEFAULT NULL,
			last_error text DEFAULT NULL,
			stale tinyint(1) unsigned NOT NULL DEFAULT 0,
			crawled_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (post_id),
			UNIQUE KEY url_hash (url_hash),
			KEY status_code (status_code),
			KEY indexable (indexable),
			KEY stale (stale),
			KEY crawled_at (crawled_at)
		) {$charset_collate};";
		dbDelta( $evidence_sql );

		$analytics_table = $wpdb->prefix . 'ikon_seo_analytics_pages';
		$analytics_sql   = "CREATE TABLE {$analytics_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			property_id varchar(80) NOT NULL,
			page_hash char(64) NOT NULL,
			page_path varchar(500) NOT NULL,
			period_start date NOT NULL,
			period_end date NOT NULL,
			sessions decimal(14,2) NOT NULL DEFAULT 0,
			active_users decimal(14,2) NOT NULL DEFAULT 0,
			engaged_sessions decimal(14,2) NOT NULL DEFAULT 0,
			engagement_rate decimal(12,6) NOT NULL DEFAULT 0,
			views decimal(14,2) NOT NULL DEFAULT 0,
			key_events decimal(14,2) NOT NULL DEFAULT 0,
			fetched_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY property_page_period (property_id,page_hash,period_end),
			KEY page_hash (page_hash),
			KEY period_end (period_end)
		) {$charset_collate};";
		dbDelta( $analytics_sql );


		$search_rows_table = $wpdb->prefix . 'ikon_seo_search_rows';
		$search_rows_sql   = "CREATE TABLE {$search_rows_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			row_hash char(64) NOT NULL,
			property_hash char(64) NOT NULL,
			property_id varchar(255) NOT NULL,
			period_type varchar(20) NOT NULL DEFAULT 'current',
			period_start date NOT NULL,
			period_end date NOT NULL,
			query_hash char(64) NOT NULL,
			page_hash char(64) NOT NULL,
			query_text text NOT NULL,
			page_url text NOT NULL,
			country varchar(8) NOT NULL DEFAULT '',
			device varchar(20) NOT NULL DEFAULT '',
			clicks decimal(14,2) NOT NULL DEFAULT 0,
			impressions decimal(14,2) NOT NULL DEFAULT 0,
			ctr decimal(12,8) NOT NULL DEFAULT 0,
			position decimal(10,4) NOT NULL DEFAULT 0,
			fetched_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY row_hash (row_hash),
			KEY property_period (property_hash(24),period_end),
			KEY query_hash (query_hash),
			KEY page_hash (page_hash),
			KEY period_end (period_end)
		) {$charset_collate};";
		dbDelta( $search_rows_sql );

		$search_clusters_table = $wpdb->prefix . 'ikon_seo_search_clusters';
		$search_clusters_sql   = "CREATE TABLE {$search_clusters_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			cluster_hash char(64) NOT NULL,
			property_hash char(64) NOT NULL,
			period_end date NOT NULL,
			cluster_key varchar(190) NOT NULL,
			cluster_label varchar(255) NOT NULL,
			query_count int(10) unsigned NOT NULL DEFAULT 0,
			clicks decimal(14,2) NOT NULL DEFAULT 0,
			impressions decimal(14,2) NOT NULL DEFAULT 0,
			top_page text DEFAULT NULL,
			queries_json longtext DEFAULT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY cluster_hash (cluster_hash),
			KEY property_period (property_hash(24),period_end),
			KEY impressions (impressions)
		) {$charset_collate};";
		dbDelta( $search_clusters_sql );


		$technical_urls_table = $wpdb->prefix . 'ikon_seo_technical_urls';
		$technical_urls_sql = "CREATE TABLE {$technical_urls_table} (
			url_hash char(64) NOT NULL,
			url text NOT NULL,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			post_type varchar(40) NOT NULL DEFAULT '',
			source_flags varchar(255) NOT NULL DEFAULT '',
			status_code smallint(5) unsigned NOT NULL DEFAULT 0,
			redirect_target text DEFAULT NULL,
			canonical_url text DEFAULT NULL,
			response_ms int(10) unsigned NOT NULL DEFAULT 0,
			inbound_links int(10) unsigned NOT NULL DEFAULT 0,
			outbound_links int(10) unsigned NOT NULL DEFAULT 0,
			crawl_depth smallint(6) NOT NULL DEFAULT -1,
			last_error text DEFAULT NULL,
			last_seen datetime NOT NULL,
			checked_at datetime DEFAULT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (url_hash),
			KEY post_id (post_id),
			KEY status_code (status_code),
			KEY crawl_depth (crawl_depth),
			KEY checked_at (checked_at)
		) {$charset_collate};";
		dbDelta( $technical_urls_sql );

		$link_graph_table = $wpdb->prefix . 'ikon_seo_link_graph';
		$link_graph_sql = "CREATE TABLE {$link_graph_table} (
			link_hash char(64) NOT NULL,
			source_hash char(64) NOT NULL,
			destination_hash char(64) NOT NULL,
			source_url text NOT NULL,
			destination_url text NOT NULL,
			anchor_text text DEFAULT NULL,
			rel varchar(255) NOT NULL DEFAULT '',
			placement varchar(30) NOT NULL DEFAULT 'unknown',
			follow tinyint(1) unsigned NOT NULL DEFAULT 1,
			first_seen datetime NOT NULL,
			last_seen datetime NOT NULL,
			PRIMARY KEY  (link_hash),
			KEY source_hash (source_hash),
			KEY destination_hash (destination_hash),
			KEY placement (placement),
			KEY follow (follow)
		) {$charset_collate};";
		dbDelta( $link_graph_sql );

		$pagespeed_table = $wpdb->prefix . 'ikon_seo_pagespeed';
		$pagespeed_sql = "CREATE TABLE {$pagespeed_table} (
			url_hash char(64) NOT NULL,
			url text NOT NULL,
			strategy varchar(10) NOT NULL DEFAULT 'mobile',
			performance_score smallint(5) unsigned NOT NULL DEFAULT 0,
			seo_score smallint(5) unsigned NOT NULL DEFAULT 0,
			accessibility_score smallint(5) unsigned NOT NULL DEFAULT 0,
			best_practices_score smallint(5) unsigned NOT NULL DEFAULT 0,
			lcp_ms decimal(12,2) NOT NULL DEFAULT 0,
			inp_ms decimal(12,2) NOT NULL DEFAULT 0,
			cls decimal(12,4) NOT NULL DEFAULT 0,
			tbt_ms decimal(12,2) NOT NULL DEFAULT 0,
			fcp_ms decimal(12,2) NOT NULL DEFAULT 0,
			field_lcp_ms decimal(12,2) NOT NULL DEFAULT 0,
			field_inp_ms decimal(12,2) NOT NULL DEFAULT 0,
			field_cls decimal(12,4) NOT NULL DEFAULT 0,
			field_ttfb_ms decimal(12,2) NOT NULL DEFAULT 0,
			field_data_available tinyint(1) unsigned NOT NULL DEFAULT 0,
			opportunities_json longtext DEFAULT NULL,
			fetched_at datetime NOT NULL,
			PRIMARY KEY  (url_hash,strategy),
			KEY performance_score (performance_score),
			KEY fetched_at (fetched_at)
		) {$charset_collate};";
		dbDelta( $pagespeed_sql );



		$competitor_research_table = $wpdb->prefix . 'ikon_seo_competitor_research';
		$competitor_research_sql = "CREATE TABLE {$competitor_research_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			record_hash char(64) NOT NULL,
			query_hash char(64) NOT NULL,
			url_hash char(64) NOT NULL,
			query_text varchar(255) NOT NULL,
			intent varchar(40) NOT NULL DEFAULT 'mixed',
			result_type varchar(50) NOT NULL DEFAULT 'mixed_results',
			competitor_domain varchar(190) NOT NULL,
			competitor_url text NOT NULL,
			page_title text DEFAULT NULL,
			meta_description text DEFAULT NULL,
			h1_text text DEFAULT NULL,
			word_count int(10) unsigned NOT NULL DEFAULT 0,
			headings_json longtext DEFAULT NULL,
			entities_json longtext DEFAULT NULL,
			topics_json longtext DEFAULT NULL,
			trust_elements_json longtext DEFAULT NULL,
			conversion_elements_json longtext DEFAULT NULL,
			search_features_json longtext DEFAULT NULL,
			evidence_notes text DEFAULT NULL,
			differentiation_notes text DEFAULT NULL,
			evidence_source varchar(40) NOT NULL DEFAULT 'connected_research',
			observed_at date NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY record_hash (record_hash),
			KEY query_hash (query_hash),
			KEY url_hash (url_hash),
			KEY competitor_domain (competitor_domain),
			KEY intent (intent),
			KEY observed_at (observed_at),
			KEY status (status)
		) {$charset_collate};";
		dbDelta( $competitor_research_sql );

		$content_briefs_table = $wpdb->prefix . 'ikon_seo_content_briefs';
		$content_briefs_sql = "CREATE TABLE {$content_briefs_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			query_hash char(64) NOT NULL,
			page_url text NOT NULL,
			page_title text DEFAULT NULL,
			target_query varchar(255) NOT NULL,
			target_intent varchar(40) NOT NULL DEFAULT 'mixed',
			page_intent varchar(40) NOT NULL DEFAULT 'mixed',
			dominant_result_type varchar(50) NOT NULL DEFAULT 'mixed_results',
			intent_alignment varchar(20) NOT NULL DEFAULT 'unknown',
			competitor_count int(10) unsigned NOT NULL DEFAULT 0,
			topic_coverage decimal(6,2) DEFAULT NULL,
			gap_priority smallint(5) unsigned NOT NULL DEFAULT 0,
			evidence_confidence varchar(20) NOT NULL DEFAULT 'low',
			covered_topics_json longtext DEFAULT NULL,
			missing_topics_json longtext DEFAULT NULL,
			missing_entities_json longtext DEFAULT NULL,
			trust_patterns_json longtext DEFAULT NULL,
			conversion_patterns_json longtext DEFAULT NULL,
			requirements_json longtext DEFAULT NULL,
			direct_evidence_json longtext DEFAULT NULL,
			hypotheses_json longtext DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'open',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY post_query (post_id,query_hash),
			KEY gap_priority (gap_priority),
			KEY target_intent (target_intent),
			KEY status (status),
			KEY updated_at (updated_at)
		) {$charset_collate};";
		dbDelta( $content_briefs_sql );


		$backlinks_table = $wpdb->prefix . 'ikon_seo_backlinks';
		$backlinks_sql = "CREATE TABLE {$backlinks_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			link_hash char(64) NOT NULL,
			relationship varchar(30) NOT NULL DEFAULT 'site_backlink',
			provider varchar(40) NOT NULL DEFAULT 'generic',
			import_batch varchar(64) NOT NULL DEFAULT '',
			source_url text NOT NULL,
			source_domain varchar(190) NOT NULL,
			source_title text DEFAULT NULL,
			target_url text NOT NULL,
			target_hash char(64) NOT NULL,
			target_domain varchar(190) NOT NULL,
			competitor_domain varchar(190) NOT NULL DEFAULT '',
			anchor_text text DEFAULT NULL,
			link_type varchar(20) NOT NULL DEFAULT 'unknown',
			status varchar(20) NOT NULL DEFAULT 'active',
			source_strength decimal(6,2) NOT NULL DEFAULT 0,
			source_traffic bigint(20) unsigned NOT NULL DEFAULT 0,
			first_seen date DEFAULT NULL,
			last_seen date DEFAULT NULL,
			observed_at date DEFAULT NULL,
			raw_metrics_json longtext DEFAULT NULL,
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY link_hash (link_hash),
			KEY relationship_status (relationship,status),
			KEY source_domain (source_domain),
			KEY target_hash (target_hash),
			KEY competitor_domain (competitor_domain),
			KEY provider (provider),
			KEY updated_at (updated_at)
		) {$charset_collate};";
		dbDelta( $backlinks_sql );

		$backlink_imports_table = $wpdb->prefix . 'ikon_seo_backlink_imports';
		$backlink_imports_sql = "CREATE TABLE {$backlink_imports_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			batch_id varchar(64) NOT NULL,
			provider varchar(40) NOT NULL DEFAULT 'generic',
			relationship varchar(30) NOT NULL DEFAULT 'site_backlink',
			competitor_domain varchar(190) NOT NULL DEFAULT '',
			filename varchar(255) NOT NULL DEFAULT '',
			rows_seen int(10) unsigned NOT NULL DEFAULT 0,
			rows_imported int(10) unsigned NOT NULL DEFAULT 0,
			rows_skipped int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY batch_id (batch_id),
			KEY provider (provider),
			KEY relationship (relationship),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $backlink_imports_sql );


		$workflows_table = $wpdb->prefix . 'ikon_seo_workflows';
		$workflows_sql = "CREATE TABLE {$workflows_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			template_key varchar(60) NOT NULL,
			name varchar(255) NOT NULL,
			website_mode varchar(40) NOT NULL DEFAULT 'local_business',
			status varchar(20) NOT NULL DEFAULT 'active',
			owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
			progress_percent smallint(5) unsigned NOT NULL DEFAULT 0,
			start_date date NOT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			completed_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY profile_status (profile_id,status),
			KEY template_key (template_key),
			KEY owner_id (owner_id),
			KEY updated_at (updated_at)
		) {$charset_collate};";
		dbDelta( $workflows_sql );

		$workflow_tasks_table = $wpdb->prefix . 'ikon_seo_workflow_tasks';
		$workflow_tasks_sql = "CREATE TABLE {$workflow_tasks_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			workflow_id bigint(20) unsigned NOT NULL,
			task_key varchar(80) NOT NULL,
			title varchar(255) NOT NULL,
			description text DEFAULT NULL,
			category varchar(40) NOT NULL DEFAULT 'analysis',
			status varchar(30) NOT NULL DEFAULT 'pending',
			priority smallint(5) unsigned NOT NULL DEFAULT 50,
			owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
			due_at datetime DEFAULT NULL,
			dependency_ids longtext DEFAULT NULL,
			automation_action varchar(80) NOT NULL DEFAULT '',
			safe_level varchar(20) NOT NULL DEFAULT 'manual',
			approval_required tinyint(1) unsigned NOT NULL DEFAULT 0,
			approved_by bigint(20) unsigned NOT NULL DEFAULT 0,
			approved_at datetime DEFAULT NULL,
			next_run_at datetime DEFAULT NULL,
			last_run_at datetime DEFAULT NULL,
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			max_attempts smallint(5) unsigned NOT NULL DEFAULT 3,
			last_error text DEFAULT NULL,
			payload_json longtext DEFAULT NULL,
			result_json longtext DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			completed_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY workflow_task (workflow_id,task_key),
			KEY workflow_status (workflow_id,status),
			KEY owner_id (owner_id),
			KEY due_at (due_at),
			KEY next_run_at (next_run_at),
			KEY automation_action (automation_action)
		) {$charset_collate};";
		dbDelta( $workflow_tasks_sql );

		$workflow_runs_table = $wpdb->prefix . 'ikon_seo_workflow_runs';
		$workflow_runs_sql = "CREATE TABLE {$workflow_runs_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			task_id bigint(20) unsigned NOT NULL,
			run_uuid varchar(64) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'running',
			attempt smallint(5) unsigned NOT NULL DEFAULT 1,
			message text DEFAULT NULL,
			result_json longtext DEFAULT NULL,
			started_at datetime NOT NULL,
			completed_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY run_uuid (run_uuid),
			KEY task_id (task_id),
			KEY status (status),
			KEY started_at (started_at)
		) {$charset_collate};";
		dbDelta( $workflow_runs_sql );


		$publisher_keywords_table = $wpdb->prefix . 'ikon_seo_publisher_keywords';
		$publisher_keywords_sql = "CREATE TABLE {$publisher_keywords_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			keyword_hash char(64) NOT NULL,
			keyword_text varchar(255) NOT NULL,
			cluster_name varchar(255) NOT NULL DEFAULT '',
			intent varchar(30) NOT NULL DEFAULT 'mixed',
			page_type varchar(30) NOT NULL DEFAULT 'article',
			country varchar(8) NOT NULL DEFAULT '',
			language varchar(16) NOT NULL DEFAULT 'en',
			demand_band varchar(20) NOT NULL DEFAULT 'unknown',
			difficulty_band varchar(20) NOT NULL DEFAULT 'unknown',
			business_value smallint(5) unsigned NOT NULL DEFAULT 50,
			priority smallint(5) unsigned NOT NULL DEFAULT 50,
			status varchar(20) NOT NULL DEFAULT 'idea',
			target_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			source varchar(40) NOT NULL DEFAULT 'manual',
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY profile_keyword (profile_id,keyword_hash),
			KEY profile_status (profile_id,status),
			KEY cluster_name (cluster_name(100)),
			KEY priority (priority),
			KEY target_post_id (target_post_id)
		) {$charset_collate};";
		dbDelta( $publisher_keywords_sql );

		$publisher_hubs_table = $wpdb->prefix . 'ikon_seo_publisher_hubs';
		$publisher_hubs_sql = "CREATE TABLE {$publisher_hubs_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			title varchar(255) NOT NULL,
			slug varchar(200) NOT NULL,
			description text DEFAULT NULL,
			target_audience text DEFAULT NULL,
			monetization_goal varchar(255) NOT NULL DEFAULT '',
			pillar_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			keyword_ids_json longtext DEFAULT NULL,
			supporting_post_ids_json longtext DEFAULT NULL,
			readiness smallint(5) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'planned',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY profile_slug (profile_id,slug),
			KEY profile_status (profile_id,status),
			KEY pillar_post_id (pillar_post_id),
			KEY readiness (readiness)
		) {$charset_collate};";
		dbDelta( $publisher_hubs_sql );

		$publisher_items_table = $wpdb->prefix . 'ikon_seo_publisher_items';
		$publisher_items_sql = "CREATE TABLE {$publisher_items_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			keyword_id bigint(20) unsigned NOT NULL DEFAULT 0,
			hub_id bigint(20) unsigned NOT NULL DEFAULT 0,
			title varchar(255) NOT NULL,
			content_type varchar(30) NOT NULL DEFAULT 'article',
			intent varchar(30) NOT NULL DEFAULT 'mixed',
			stage varchar(30) NOT NULL DEFAULT 'idea',
			priority smallint(5) unsigned NOT NULL DEFAULT 50,
			author_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			due_at datetime DEFAULT NULL,
			target_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			source_requirements text DEFAULT NULL,
			evidence_notes text DEFAULT NULL,
			originality_required tinyint(1) unsigned NOT NULL DEFAULT 1,
			disclosure_required tinyint(1) unsigned NOT NULL DEFAULT 0,
			brief_json longtext DEFAULT NULL,
			quality_score smallint(5) unsigned NOT NULL DEFAULT 0,
			gate_status varchar(20) NOT NULL DEFAULT 'not_reviewed',
			lifecycle_action varchar(30) NOT NULL DEFAULT 'none',
			refresh_due date DEFAULT NULL,
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY profile_stage (profile_id,stage),
			KEY keyword_id (keyword_id),
			KEY hub_id (hub_id),
			KEY target_post_id (target_post_id),
			KEY author_id (author_id),
			KEY reviewer_id (reviewer_id),
			KEY due_at (due_at),
			KEY refresh_due (refresh_due)
		) {$charset_collate};";
		dbDelta( $publisher_items_sql );

		$portfolio_signatures_table = $wpdb->prefix . 'ikon_seo_portfolio_signatures';
		$portfolio_signatures_sql = "CREATE TABLE {$portfolio_signatures_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			signature_hash char(64) NOT NULL,
			site_label varchar(255) NOT NULL,
			site_url text DEFAULT NULL,
			content_url text DEFAULT NULL,
			content_title varchar(255) NOT NULL DEFAULT '',
			content_type varchar(30) NOT NULL DEFAULT 'article',
			signature_json longtext NOT NULL,
			topics_json longtext DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY signature_hash (signature_hash),
			KEY site_label (site_label(100)),
			KEY content_type (content_type),
			KEY updated_at (updated_at)
		) {$charset_collate};";
		dbDelta( $portfolio_signatures_sql );


		$local_review_tasks_table = $wpdb->prefix . 'ikon_seo_local_review_tasks';
		$local_review_tasks_sql = "CREATE TABLE {$local_review_tasks_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			location_id bigint(20) unsigned NOT NULL DEFAULT 0,
			review_hash char(64) NOT NULL,
			review_ref varchar(255) NOT NULL,
			star_rating tinyint(2) unsigned NOT NULL DEFAULT 0,
			has_comment tinyint(1) unsigned NOT NULL DEFAULT 0,
			has_reply tinyint(1) unsigned NOT NULL DEFAULT 0,
			status varchar(24) NOT NULL DEFAULT 'open',
			due_at datetime DEFAULT NULL,
			first_seen_at datetime NOT NULL,
			last_seen_at datetime NOT NULL,
			review_created_at datetime DEFAULT NULL,
			review_updated_at datetime DEFAULT NULL,
			responded_at datetime DEFAULT NULL,
			owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
			notes text DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY profile_review (profile_id,review_hash),
			KEY location_status (location_id,status),
			KEY due_at (due_at),
			KEY owner_id (owner_id)
		) {$charset_collate};";
		dbDelta( $local_review_tasks_sql );

		$local_prominence_table = $wpdb->prefix . 'ikon_seo_local_prominence';
		$local_prominence_sql = "CREATE TABLE {$local_prominence_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			competitor_name varchar(255) NOT NULL,
			competitor_domain varchar(190) NOT NULL DEFAULT '',
			query_text varchar(255) NOT NULL DEFAULT '',
			source_type varchar(30) NOT NULL DEFAULT 'manual',
			source_url text DEFAULT NULL,
			evidence_text text NOT NULL,
			metric_name varchar(120) NOT NULL DEFAULT '',
			metric_value decimal(18,4) DEFAULT NULL,
			confidence varchar(20) NOT NULL DEFAULT 'medium',
			observed_at date NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY profile_status (profile_id,status),
			KEY competitor_domain (competitor_domain),
			KEY query_text (query_text(100)),
			KEY source_type (source_type),
			KEY observed_at (observed_at)
		) {$charset_collate};";
		dbDelta( $local_prominence_sql );

		$local_conversions_table = $wpdb->prefix . 'ikon_seo_local_conversions';
		$local_conversions_sql = "CREATE TABLE {$local_conversions_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			location_id bigint(20) unsigned NOT NULL DEFAULT 0,
			period_start date NOT NULL,
			period_end date NOT NULL,
			source varchar(24) NOT NULL,
			metric_name varchar(80) NOT NULL,
			metric_value decimal(20,4) NOT NULL DEFAULT 0,
			context_json longtext DEFAULT NULL,
			fetched_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY metric_snapshot (profile_id,location_id,period_start,period_end,source,metric_name),
			KEY profile_period (profile_id,period_end),
			KEY location_id (location_id),
			KEY source (source)
		) {$charset_collate};";
		dbDelta( $local_conversions_sql );


		$visibility_observations_table = $wpdb->prefix . 'ikon_seo_visibility_observations';
		$visibility_observations_sql = "CREATE TABLE {$visibility_observations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			observation_hash char(64) NOT NULL,
			profile_id varchar(64) NOT NULL,
			observation_type varchar(30) NOT NULL DEFAULT 'organic_search',
			query_text text DEFAULT NULL,
			query_hash char(64) NOT NULL,
			brand_role varchar(24) NOT NULL DEFAULT 'own_brand',
			brand_name varchar(255) NOT NULL DEFAULT '',
			competitor_domain varchar(190) NOT NULL DEFAULT '',
			mention_status varchar(20) NOT NULL DEFAULT 'mentioned',
			cited_url text DEFAULT NULL,
			source_name varchar(255) NOT NULL DEFAULT '',
			source_url text DEFAULT NULL,
			sentiment varchar(20) NOT NULL DEFAULT 'unknown',
			prominence smallint(5) unsigned NOT NULL DEFAULT 0,
			position_text varchar(255) NOT NULL DEFAULT '',
			evidence_excerpt text DEFAULT NULL,
			confidence varchar(12) NOT NULL DEFAULT 'medium',
			observed_at datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY observation_hash (observation_hash),
			KEY profile_type (profile_id,observation_type),
			KEY query_hash (query_hash),
			KEY competitor_domain (competitor_domain),
			KEY observed_at (observed_at)
		) {$charset_collate};";
		dbDelta( $visibility_observations_sql );

		$brand_mentions_table = $wpdb->prefix . 'ikon_seo_brand_mentions';
		$brand_mentions_sql = "CREATE TABLE {$brand_mentions_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			mention_hash char(64) NOT NULL,
			profile_id varchar(64) NOT NULL,
			mention_url text NOT NULL,
			source_domain varchar(190) NOT NULL,
			mention_type varchar(30) NOT NULL DEFAULT 'editorial',
			brand_name varchar(255) NOT NULL DEFAULT '',
			mention_title varchar(255) NOT NULL DEFAULT '',
			mention_excerpt text DEFAULT NULL,
			linked tinyint(1) unsigned NOT NULL DEFAULT 0,
			target_url text DEFAULT NULL,
			sentiment varchar(20) NOT NULL DEFAULT 'unknown',
			relevance smallint(5) unsigned NOT NULL DEFAULT 50,
			source_strength smallint(5) unsigned NOT NULL DEFAULT 0,
			status varchar(24) NOT NULL DEFAULT 'new',
			discovered_at datetime NOT NULL,
			last_checked datetime NOT NULL,
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY mention_hash (mention_hash),
			KEY profile_status (profile_id,status),
			KEY source_domain (source_domain),
			KEY linked_status (linked,status),
			KEY last_checked (last_checked)
		) {$charset_collate};";
		dbDelta( $brand_mentions_sql );

		$visibility_snapshots_table = $wpdb->prefix . 'ikon_seo_visibility_snapshots';
		$visibility_snapshots_sql = "CREATE TABLE {$visibility_snapshots_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			snapshot_hash char(64) NOT NULL,
			profile_id varchar(64) NOT NULL,
			period_start date NOT NULL,
			period_end date NOT NULL,
			summary_json longtext NOT NULL,
			source varchar(30) NOT NULL DEFAULT 'manual',
			captured_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY snapshot_hash (snapshot_hash),
			KEY profile_period (profile_id,period_end),
			KEY captured_at (captured_at)
		) {$charset_collate};";
		dbDelta( $visibility_snapshots_sql );

		$agency_sites_table = $wpdb->prefix . 'ikon_seo_agency_sites';
		$agency_sites_sql = "CREATE TABLE {$agency_sites_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			client_name varchar(255) NOT NULL DEFAULT '',
			group_name varchar(190) NOT NULL DEFAULT '',
			site_name varchar(255) NOT NULL,
			site_url text NOT NULL,
			site_hash char(64) NOT NULL,
			encrypted_key longtext NOT NULL,
			enabled tinyint(1) unsigned NOT NULL DEFAULT 1,
			status varchar(20) NOT NULL DEFAULT 'connected',
			monthly_budget decimal(14,2) NOT NULL DEFAULT 0,
			currency varchar(3) NOT NULL DEFAULT 'USD',
			report_label varchar(255) NOT NULL DEFAULT '',
			last_snapshot_at datetime DEFAULT NULL,
			last_error text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY site_hash (site_hash),
			KEY client_name (client_name(100)),
			KEY group_name (group_name(100)),
			KEY enabled_status (enabled,status),
			KEY last_snapshot_at (last_snapshot_at)
		) {$charset_collate};";
		dbDelta( $agency_sites_sql );

		$agency_snapshots_table = $wpdb->prefix . 'ikon_seo_agency_snapshots';
		$agency_snapshots_sql = "CREATE TABLE {$agency_snapshots_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			site_id bigint(20) unsigned NOT NULL,
			snapshot_hash char(64) NOT NULL,
			snapshot_json longtext NOT NULL,
			captured_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY site_snapshot (site_id,snapshot_hash),
			KEY site_captured (site_id,captured_at)
		) {$charset_collate};";
		dbDelta( $agency_snapshots_sql );

		$agency_alerts_table = $wpdb->prefix . 'ikon_seo_agency_alerts';
		$agency_alerts_sql = "CREATE TABLE {$agency_alerts_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			site_id bigint(20) unsigned NOT NULL,
			alert_key char(64) NOT NULL,
			category varchar(40) NOT NULL,
			severity varchar(20) NOT NULL DEFAULT 'medium',
			title varchar(255) NOT NULL,
			summary text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'open',
			source varchar(30) NOT NULL DEFAULT 'snapshot',
			first_seen_at datetime NOT NULL,
			last_seen_at datetime NOT NULL,
			resolved_at datetime DEFAULT NULL,
			resolved_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY site_alert (site_id,alert_key),
			KEY status_severity (status,severity),
			KEY site_status (site_id,status),
			KEY last_seen_at (last_seen_at)
		) {$charset_collate};";
		dbDelta( $agency_alerts_sql );

		$agency_usage_table = $wpdb->prefix . 'ikon_seo_agency_usage';
		$agency_usage_sql = "CREATE TABLE {$agency_usage_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			site_id bigint(20) unsigned NOT NULL,
			category varchar(40) NOT NULL DEFAULT 'research',
			amount decimal(14,2) NOT NULL DEFAULT 0,
			currency varchar(3) NOT NULL DEFAULT 'USD',
			units decimal(14,2) NOT NULL DEFAULT 0,
			note text DEFAULT NULL,
			event_date date NOT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY site_period (site_id,event_date),
			KEY category (category)
		) {$charset_collate};";
		dbDelta( $agency_usage_sql );


		$recommendations_table = $wpdb->prefix . 'ikon_seo_recommendations';
		$recommendations_sql = "CREATE TABLE {$recommendations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			recommendation_key char(64) NOT NULL,
			profile_id varchar(64) NOT NULL,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			target_url text DEFAULT NULL,
			source_module varchar(40) NOT NULL DEFAULT 'other',
			category varchar(40) NOT NULL DEFAULT 'opportunity',
			root_cause varchar(80) NOT NULL DEFAULT 'recommendation',
			title varchar(255) NOT NULL,
			rationale longtext DEFAULT NULL,
			evidence_json longtext DEFAULT NULL,
			action_json longtext DEFAULT NULL,
			priority smallint(5) unsigned NOT NULL DEFAULT 50,
			confidence varchar(12) NOT NULL DEFAULT 'medium',
			business_value tinyint(3) unsigned NOT NULL DEFAULT 3,
			effort tinyint(3) unsigned NOT NULL DEFAULT 3,
			status varchar(24) NOT NULL DEFAULT 'proposed',
			approval_required tinyint(1) unsigned NOT NULL DEFAULT 1,
			baseline_snapshot_id bigint(20) unsigned NOT NULL DEFAULT 0,
			workflow_task_id bigint(20) unsigned NOT NULL DEFAULT 0,
			completion_notes longtext DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY recommendation_key (recommendation_key),
			KEY profile_status (profile_id,status),
			KEY post_id (post_id),
			KEY priority (priority),
			KEY source_module (source_module),
			KEY updated_at (updated_at)
		) {$charset_collate};";
		dbDelta( $recommendations_sql );

		$outcome_snapshots_table = $wpdb->prefix . 'ikon_seo_outcome_snapshots';
		$outcome_snapshots_sql = "CREATE TABLE {$outcome_snapshots_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			snapshot_hash char(64) NOT NULL,
			recommendation_id bigint(20) unsigned NOT NULL,
			snapshot_type varchar(40) NOT NULL DEFAULT 'manual',
			metrics_json longtext NOT NULL,
			captured_at datetime NOT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY snapshot_hash (snapshot_hash),
			KEY recommendation_type (recommendation_id,snapshot_type),
			KEY captured_at (captured_at)
		) {$charset_collate};";
		dbDelta( $outcome_snapshots_sql );

		$outcomes_table = $wpdb->prefix . 'ikon_seo_outcomes';
		$outcomes_sql = "CREATE TABLE {$outcomes_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			recommendation_id bigint(20) unsigned NOT NULL,
			window_days smallint(5) unsigned NOT NULL,
			due_at datetime NOT NULL,
			measured_at datetime DEFAULT NULL,
			measurement_snapshot_id bigint(20) unsigned NOT NULL DEFAULT 0,
			outcome varchar(24) NOT NULL DEFAULT 'pending',
			confidence varchar(12) NOT NULL DEFAULT 'low',
			summary text DEFAULT NULL,
			deltas_json longtext DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY recommendation_window (recommendation_id,window_days),
			KEY due_status (due_at,measured_at),
			KEY outcome (outcome)
		) {$charset_collate};";
		dbDelta( $outcomes_sql );

		$recovery_checkpoints_table = $wpdb->prefix . 'ikon_seo_recovery_checkpoints';
		$recovery_checkpoints_sql = "CREATE TABLE {$recovery_checkpoints_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			checkpoint_hash char(64) NOT NULL,
			profile_id varchar(64) NOT NULL,
			reason varchar(255) NOT NULL,
			component_version varchar(20) NOT NULL,
			payload_encrypted longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'available',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			restored_at datetime DEFAULT NULL,
			restored_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY checkpoint_hash (checkpoint_hash),
			KEY profile_created (profile_id,created_at),
			KEY status (status)
		) {$charset_collate};";
		dbDelta( $recovery_checkpoints_sql );


		$indexation_urls_table = $wpdb->prefix . 'ikon_seo_indexation_urls';
		$indexation_urls_sql = "CREATE TABLE {$indexation_urls_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url_hash char(64) NOT NULL,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			url text NOT NULL,
			source varchar(40) NOT NULL DEFAULT 'inventory',
			priority smallint(5) unsigned NOT NULL DEFAULT 60,
			queue_status varchar(20) NOT NULL DEFAULT 'queued',
			inspection_status varchar(20) NOT NULL DEFAULT 'pending',
			verdict varchar(20) NOT NULL DEFAULT '',
			coverage_state varchar(255) NOT NULL DEFAULT '',
			indexing_state varchar(60) NOT NULL DEFAULT '',
			page_fetch_state varchar(60) NOT NULL DEFAULT '',
			robots_txt_state varchar(60) NOT NULL DEFAULT '',
			last_crawl_time datetime DEFAULT NULL,
			google_canonical text DEFAULT NULL,
			user_canonical text DEFAULT NULL,
			canonical_mismatch tinyint(1) unsigned NOT NULL DEFAULT 0,
			local_noindex tinyint(1) unsigned NOT NULL DEFAULT 0,
			in_sitemap tinyint(1) unsigned NOT NULL DEFAULT 0,
			mobile_usability_verdict varchar(20) NOT NULL DEFAULT '',
			rich_results_verdict varchar(20) NOT NULL DEFAULT '',
			rich_items_json longtext DEFAULT NULL,
			issue_code varchar(60) NOT NULL DEFAULT '',
			last_error text DEFAULT NULL,
			requested_at datetime DEFAULT NULL,
			inspected_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY url_hash (url_hash),
			KEY queue_priority (queue_status,priority),
			KEY inspection_status (inspection_status),
			KEY issue_code (issue_code),
			KEY post_id (post_id),
			KEY inspected_at (inspected_at)
		) {$charset_collate};";
		dbDelta( $indexation_urls_sql );

		$indexation_runs_table = $wpdb->prefix . 'ikon_seo_indexation_runs';
		$indexation_runs_sql = "CREATE TABLE {$indexation_runs_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url_hash char(64) NOT NULL,
			url text NOT NULL,
			status varchar(20) NOT NULL,
			verdict varchar(20) NOT NULL DEFAULT '',
			issue_code varchar(60) NOT NULL DEFAULT '',
			message text DEFAULT NULL,
			source varchar(40) NOT NULL DEFAULT 'manual',
			requested_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY url_created (url_hash,created_at),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $indexation_runs_sql );

		$system_health_table = $wpdb->prefix . 'ikon_seo_system_health_runs';
		$system_health_sql = "CREATE TABLE {$system_health_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			run_hash char(64) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'review',
			checks_json longtext NOT NULL,
			source varchar(40) NOT NULL DEFAULT 'manual',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY run_hash (run_hash),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $system_health_sql );


		$schema_audits_table = $wpdb->prefix . 'ikon_seo_schema_audits';
		$schema_audits_sql = "CREATE TABLE {$schema_audits_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url_hash char(64) NOT NULL,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			url text NOT NULL,
			status_code smallint(5) unsigned NOT NULL DEFAULT 0,
			schema_count smallint(5) unsigned NOT NULL DEFAULT 0,
			node_count smallint(5) unsigned NOT NULL DEFAULT 0,
			detected_types_json longtext DEFAULT NULL,
			duplicate_types_json longtext DEFAULT NULL,
			provider_hints_json longtext DEFAULT NULL,
			candidate_features_json longtext DEFAULT NULL,
			issues_json longtext DEFAULT NULL,
			error_count smallint(5) unsigned NOT NULL DEFAULT 0,
			warning_count smallint(5) unsigned NOT NULL DEFAULT 0,
			info_count smallint(5) unsigned NOT NULL DEFAULT 0,
			duplicate_primary tinyint(1) unsigned NOT NULL DEFAULT 0,
			visible_alignment_percent smallint(5) unsigned NOT NULL DEFAULT 0,
			last_error text DEFAULT NULL,
			checked_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY url_hash (url_hash),
			KEY post_id (post_id),
			KEY error_count (error_count),
			KEY warning_count (warning_count),
			KEY checked_at (checked_at)
		) {$charset_collate};";
		dbDelta( $schema_audits_sql );

		$media_assets_table = $wpdb->prefix . 'ikon_seo_media_assets';
		$media_assets_sql = "CREATE TABLE {$media_assets_table} (
			attachment_id bigint(20) unsigned NOT NULL,
			url text DEFAULT NULL,
			filename varchar(255) NOT NULL DEFAULT '',
			file_hash char(64) NOT NULL DEFAULT '',
			mime_type varchar(100) NOT NULL DEFAULT '',
			width int(10) unsigned NOT NULL DEFAULT 0,
			height int(10) unsigned NOT NULL DEFAULT 0,
			file_size bigint(20) unsigned NOT NULL DEFAULT 0,
			usage_count int(10) unsigned NOT NULL DEFAULT 0,
			featured_usage int(10) unsigned NOT NULL DEFAULT 0,
			social_usage int(10) unsigned NOT NULL DEFAULT 0,
			alt_text text DEFAULT NULL,
			caption text DEFAULT NULL,
			description_present tinyint(1) unsigned NOT NULL DEFAULT 0,
			source_type varchar(30) NOT NULL DEFAULT 'unknown',
			source_url text DEFAULT NULL,
			license_name varchar(255) NOT NULL DEFAULT '',
			license_url text DEFAULT NULL,
			creator varchar(255) NOT NULL DEFAULT '',
			rights_notes text DEFAULT NULL,
			issues_json longtext DEFAULT NULL,
			issue_count smallint(5) unsigned NOT NULL DEFAULT 0,
			duplicate_group_size smallint(5) unsigned NOT NULL DEFAULT 1,
			checked_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (attachment_id),
			KEY file_hash (file_hash),
			KEY issue_count (issue_count),
			KEY source_type (source_type),
			KEY checked_at (checked_at)
		) {$charset_collate};";
		dbDelta( $media_assets_sql );

		$governance_runs_table = $wpdb->prefix . 'ikon_seo_governance_runs';
		$governance_runs_sql = "CREATE TABLE {$governance_runs_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			run_type varchar(20) NOT NULL,
			source varchar(40) NOT NULL DEFAULT 'manual',
			status varchar(20) NOT NULL DEFAULT 'running',
			processed int(10) unsigned NOT NULL DEFAULT 0,
			errors int(10) unsigned NOT NULL DEFAULT 0,
			summary_json longtext DEFAULT NULL,
			started_at datetime NOT NULL,
			completed_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY run_type (run_type),
			KEY status (status),
			KEY started_at (started_at)
		) {$charset_collate};";
		dbDelta( $governance_runs_sql );


		$experiments_table = $wpdb->prefix . 'ikon_seo_experiments';
		$experiments_sql = "CREATE TABLE {$experiments_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			experiment_key char(64) NOT NULL,
			title varchar(255) NOT NULL,
			hypothesis text NOT NULL,
			change_type varchar(40) NOT NULL DEFAULT 'content',
			status varchar(30) NOT NULL DEFAULT 'draft',
			primary_metric varchar(40) NOT NULL DEFAULT 'clicks',
			secondary_metrics_json longtext DEFAULT NULL,
			test_urls_json longtext NOT NULL,
			comparison_urls_json longtext DEFAULT NULL,
			minimum_days smallint(5) unsigned NOT NULL DEFAULT 28,
			start_date datetime DEFAULT NULL,
			end_date datetime DEFAULT NULL,
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY experiment_key (experiment_key),
			KEY status (status),
			KEY updated_at (updated_at)
		) {$charset_collate};";
		dbDelta( $experiments_sql );

		$experiment_measurements_table = $wpdb->prefix . 'ikon_seo_experiment_measurements';
		$experiment_measurements_sql = "CREATE TABLE {$experiment_measurements_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			experiment_id bigint(20) unsigned NOT NULL,
			phase varchar(20) NOT NULL DEFAULT 'outcome',
			period_start datetime DEFAULT NULL,
			period_end datetime DEFAULT NULL,
			metrics_json longtext NOT NULL,
			comparison_metrics_json longtext DEFAULT NULL,
			data_quality_json longtext DEFAULT NULL,
			outcome varchar(30) NOT NULL DEFAULT 'inconclusive',
			confidence varchar(20) NOT NULL DEFAULT 'low',
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			measured_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY experiment_phase (experiment_id,phase),
			KEY outcome (outcome),
			KEY measured_at (measured_at)
		) {$charset_collate};";
		dbDelta( $experiment_measurements_sql );

		$claims_table = $wpdb->prefix . 'ikon_seo_claims';
		$claims_sql = "CREATE TABLE {$claims_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			claim_hash char(64) NOT NULL,
			claim_text text NOT NULL,
			claim_type varchar(40) NOT NULL DEFAULT 'factual',
			risk_level varchar(20) NOT NULL DEFAULT 'standard',
			source_url text DEFAULT NULL,
			source_title varchar(255) NOT NULL DEFAULT '',
			source_type varchar(40) NOT NULL DEFAULT 'secondary',
			source_published_at datetime DEFAULT NULL,
			status varchar(30) NOT NULL DEFAULT 'needs_review',
			verified_at datetime DEFAULT NULL,
			review_due_at datetime DEFAULT NULL,
			reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY post_claim (post_id,claim_hash),
			KEY status_due (status,review_due_at),
			KEY risk_level (risk_level),
			KEY post_id (post_id)
		) {$charset_collate};";
		dbDelta( $claims_sql );

		$revenue_events_table = $wpdb->prefix . 'ikon_seo_revenue_events';
		$revenue_events_sql = "CREATE TABLE {$revenue_events_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_key char(64) NOT NULL,
			event_type varchar(40) NOT NULL,
			occurred_at datetime NOT NULL,
			source_name varchar(100) NOT NULL DEFAULT '',
			medium varchar(100) NOT NULL DEFAULT '',
			campaign varchar(191) NOT NULL DEFAULT '',
			landing_url text DEFAULT NULL,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reference_hash char(64) NOT NULL,
			crm_stage varchar(60) NOT NULL DEFAULT '',
			value decimal(18,2) NOT NULL DEFAULT 0.00,
			currency char(3) NOT NULL DEFAULT 'USD',
			qualified tinyint(1) unsigned NOT NULL DEFAULT 0,
			customer tinyint(1) unsigned NOT NULL DEFAULT 0,
			metadata_json longtext DEFAULT NULL,
			import_source varchar(40) NOT NULL DEFAULT 'manual',
			imported_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_key (event_key),
			KEY occurred_at (occurred_at),
			KEY event_type (event_type),
			KEY post_id (post_id),
			KEY campaign (campaign)
		) {$charset_collate};";
		dbDelta( $revenue_events_sql );


		$international_pages_table = $wpdb->prefix . 'ikon_seo_international_pages';
		$international_pages_sql = "CREATE TABLE {$international_pages_table} (
			url_hash char(64) NOT NULL,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			url text NOT NULL,
			canonical_url text DEFAULT NULL,
			html_lang varchar(35) NOT NULL DEFAULT '',
			content_language varchar(35) NOT NULL DEFAULT '',
			inferred_locale varchar(35) NOT NULL DEFAULT '',
			hreflang_json longtext DEFAULT NULL,
			issues_json longtext DEFAULT NULL,
			issue_count int(10) unsigned NOT NULL DEFAULT 0,
			reciprocal_issues int(10) unsigned NOT NULL DEFAULT 0,
			regional_signals_json longtext DEFAULT NULL,
			status_code smallint(5) unsigned NOT NULL DEFAULT 0,
			response_ms int(10) unsigned NOT NULL DEFAULT 0,
			audited_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (url_hash),
			KEY post_id (post_id),
			KEY issue_count (issue_count),
			KEY html_lang (html_lang),
			KEY audited_at (audited_at)
		) {$charset_collate};";
		dbDelta( $international_pages_sql );

		$server_log_events_table = $wpdb->prefix . 'ikon_seo_server_log_events';
		$server_log_events_sql = "CREATE TABLE {$server_log_events_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_hash char(64) NOT NULL,
			occurred_at datetime NOT NULL,
			method varchar(10) NOT NULL DEFAULT 'GET',
			request_path text NOT NULL,
			path_hash char(64) NOT NULL,
			status_code smallint(5) unsigned NOT NULL DEFAULT 0,
			bytes_sent bigint(20) unsigned NOT NULL DEFAULT 0,
			response_ms int(10) unsigned NOT NULL DEFAULT 0,
			crawler_family varchar(40) NOT NULL DEFAULT 'other',
			verification_state varchar(20) NOT NULL DEFAULT 'unknown',
			user_agent_hash char(64) NOT NULL,
			ip_hash char(64) NOT NULL,
			query_keys varchar(500) NOT NULL DEFAULT '',
			content_group varchar(30) NOT NULL DEFAULT 'page',
			waste_category varchar(30) NOT NULL DEFAULT 'none',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_hash (event_hash),
			KEY occurred_at (occurred_at),
			KEY path_hash (path_hash),
			KEY crawler_state (crawler_family,verification_state),
			KEY status_code (status_code),
			KEY waste_category (waste_category),
			KEY response_ms (response_ms)
		) {$charset_collate};";
		dbDelta( $server_log_events_sql );

		$server_log_imports_table = $wpdb->prefix . 'ikon_seo_server_log_imports';
		$server_log_imports_sql = "CREATE TABLE {$server_log_imports_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			batch_id varchar(64) NOT NULL,
			source_format varchar(40) NOT NULL DEFAULT 'manual',
			filename varchar(255) NOT NULL DEFAULT '',
			rows_seen int(10) unsigned NOT NULL DEFAULT 0,
			rows_imported int(10) unsigned NOT NULL DEFAULT 0,
			rows_skipped int(10) unsigned NOT NULL DEFAULT 0,
			verified_count int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			started_at datetime NOT NULL,
			completed_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY batch_id (batch_id),
			KEY completed_at (completed_at),
			KEY source_format (source_format)
		) {$charset_collate};";
		dbDelta( $server_log_imports_sql );


		$portfolio_quality_profiles_table = $wpdb->prefix . 'ikon_seo_portfolio_quality_profiles';
		$portfolio_quality_profiles_sql = "CREATE TABLE {$portfolio_quality_profiles_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			profile_hash char(64) NOT NULL,
			source varchar(20) NOT NULL DEFAULT 'local',
			source_site_hash char(64) NOT NULL,
			source_site_label varchar(255) NOT NULL DEFAULT '',
			bundle_id varchar(40) NOT NULL DEFAULT '',
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			content_url text DEFAULT NULL,
			content_title varchar(255) NOT NULL DEFAULT '',
			content_type varchar(40) NOT NULL DEFAULT 'article',
			word_count int(10) unsigned NOT NULL DEFAULT 0,
			heading_count int(10) unsigned NOT NULL DEFAULT 0,
			paragraph_count int(10) unsigned NOT NULL DEFAULT 0,
			internal_links int(10) unsigned NOT NULL DEFAULT 0,
			signature_json longtext NOT NULL,
			topics_json longtext DEFAULT NULL,
			heading_hash char(64) NOT NULL DEFAULT '',
			template_hash char(64) NOT NULL DEFAULT '',
			title_pattern_hash char(64) NOT NULL DEFAULT '',
			author_hash char(64) NOT NULL DEFAULT '',
			media_hashes_json longtext DEFAULT NULL,
			publish_pattern varchar(20) NOT NULL DEFAULT '',
			published_at datetime DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			scanned_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY profile_hash (profile_hash),
			KEY profile_source (profile_id,source,status),
			KEY source_site_hash (source_site_hash),
			KEY post_id (post_id),
			KEY template_hash (template_hash),
			KEY updated_at (updated_at)
		) {$charset_collate};";
		dbDelta( $portfolio_quality_profiles_sql );

		$portfolio_quality_findings_table = $wpdb->prefix . 'ikon_seo_portfolio_quality_findings';
		$portfolio_quality_findings_sql = "CREATE TABLE {$portfolio_quality_findings_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			finding_key char(64) NOT NULL,
			local_profile_id bigint(20) unsigned NOT NULL DEFAULT 0,
			local_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			local_url text DEFAULT NULL,
			compared_profile_id bigint(20) unsigned NOT NULL DEFAULT 0,
			compared_site_hash char(64) NOT NULL DEFAULT '',
			compared_site_label varchar(255) NOT NULL DEFAULT '',
			compared_url text DEFAULT NULL,
			category varchar(50) NOT NULL,
			severity varchar(20) NOT NULL DEFAULT 'medium',
			risk_score decimal(6,2) NOT NULL DEFAULT 0,
			summary text NOT NULL,
			evidence_json longtext DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'open',
			blocks_review tinyint(1) unsigned NOT NULL DEFAULT 0,
			first_seen_at datetime NOT NULL,
			last_seen_at datetime NOT NULL,
			reviewed_by bigint(20) unsigned NOT NULL DEFAULT 0,
			reviewed_at datetime DEFAULT NULL,
			review_notes text DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY finding_key (finding_key),
			KEY profile_status (profile_id,status),
			KEY local_post_id (local_post_id),
			KEY severity (severity),
			KEY blocks_review (blocks_review),
			KEY updated_at (updated_at)
		) {$charset_collate};";
		dbDelta( $portfolio_quality_findings_sql );

		$portfolio_quality_imports_table = $wpdb->prefix . 'ikon_seo_portfolio_quality_imports';
		$portfolio_quality_imports_sql = "CREATE TABLE {$portfolio_quality_imports_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			bundle_id varchar(40) NOT NULL,
			source_site_hash char(64) NOT NULL,
			source_site_label varchar(255) NOT NULL DEFAULT '',
			source_site_url text DEFAULT NULL,
			rows_seen int(10) unsigned NOT NULL DEFAULT 0,
			rows_imported int(10) unsigned NOT NULL DEFAULT 0,
			rows_skipped int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY bundle_id (bundle_id),
			KEY profile_site (profile_id,source_site_hash),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $portfolio_quality_imports_sql );

		$history_table = $wpdb->prefix . 'ikon_seo_workspace_history';
		$history_sql   = "CREATE TABLE {$history_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_id varchar(64) NOT NULL,
			category varchar(30) NOT NULL DEFAULT 'note',
			status varchar(20) NOT NULL DEFAULT 'open',
			title varchar(255) NOT NULL,
			summary text NOT NULL,
			details longtext DEFAULT NULL,
			source varchar(30) NOT NULL DEFAULT 'workspace',
			related_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			completed_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY profile_status (profile_id,status),
			KEY category (category),
			KEY related_post_id (related_post_id),
			KEY updated_at (updated_at)
		) {$charset_collate};";
		dbDelta( $history_sql );

		if ( false === get_option( self::OPTION_KEY, false ) ) {
			$defaults              = self::defaults();
			$defaults['author_id'] = get_current_user_id();
			add_option( self::OPTION_KEY, $defaults, '', false );
		} else {
			$settings                      = get_option( self::OPTION_KEY, array() );
			$settings['component_version'] = '25.0';
			update_option( self::OPTION_KEY, $settings, false );
		}

		Ikon_SEO_Agency::bootstrap_owner();
		Ikon_SEO_Profile::migrate_legacy_settings();
		if ( ! wp_next_scheduled( 'ikon_seo_daily_monitor' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ikon_seo_daily_monitor' );
		}
		if ( ! wp_next_scheduled( 'ikon_seo_evidence_crawl' ) ) {
			wp_schedule_event( time() + 2 * HOUR_IN_SECONDS, 'daily', 'ikon_seo_evidence_crawl' );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Search_Intelligence::CRON_HOOK ) ) {
			wp_schedule_event( time() + 6 * HOUR_IN_SECONDS, 'weekly', Ikon_SEO_Search_Intelligence::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Technical_Intelligence::CRON_HOOK ) ) {
			wp_schedule_event( time() + 8 * HOUR_IN_SECONDS, 'weekly', Ikon_SEO_Technical_Intelligence::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Automation::RUNNER_HOOK ) ) {
			wp_schedule_event( time() + 30 * MINUTE_IN_SECONDS, 'hourly', Ikon_SEO_Automation::RUNNER_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Automation::DAILY_HOOK ) ) {
			wp_schedule_event( strtotime( 'tomorrow 08:00 UTC' ), 'daily', Ikon_SEO_Automation::DAILY_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Automation::WEEKLY_HOOK ) ) {
			wp_schedule_event( strtotime( 'next monday 08:30 UTC' ), 'weekly', Ikon_SEO_Automation::WEEKLY_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Local_Growth::CRON_HOOK ) ) {
			wp_schedule_event( strtotime( 'next wednesday 09:00 UTC' ), 'weekly', Ikon_SEO_Local_Growth::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Visibility_Brand_Intelligence::CRON_HOOK ) ) {
			wp_schedule_event( strtotime( 'next thursday 09:30 UTC' ), 'weekly', Ikon_SEO_Visibility_Brand_Intelligence::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Agency_Command_Centre::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', Ikon_SEO_Agency_Command_Centre::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Closed_Loop::CRON_HOOK ) ) {
			wp_schedule_event( strtotime( 'tomorrow 10:00 UTC' ), 'daily', Ikon_SEO_Closed_Loop::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Indexation_Intelligence::CRON_HOOK ) ) {
			wp_schedule_event( strtotime( 'tomorrow 11:00 UTC' ), 'daily', Ikon_SEO_Indexation_Intelligence::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Production_Health::CRON_HOOK ) ) {
			wp_schedule_event( strtotime( 'next sunday 07:00 UTC' ), 'weekly', Ikon_SEO_Production_Health::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Production_Health::HEARTBEAT_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', Ikon_SEO_Production_Health::HEARTBEAT_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Structured_Media_Governance::CRON_HOOK ) ) {
			wp_schedule_event( strtotime( 'next tuesday 07:30 UTC' ), 'weekly', Ikon_SEO_Structured_Media_Governance::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Experiments_Claims_Revenue::CRON_HOOK ) ) {
			wp_schedule_event( strtotime( 'next friday 08:00 UTC' ), 'weekly', Ikon_SEO_Experiments_Claims_Revenue::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_International_Server_Intelligence::CRON_HOOK ) ) {
			wp_schedule_event( strtotime( 'next saturday 08:30 UTC' ), 'weekly', Ikon_SEO_International_Server_Intelligence::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( Ikon_SEO_Portfolio_Quality_Guard::CRON_HOOK ) ) {
			wp_schedule_event( strtotime( 'next sunday 09:30 UTC' ), 'weekly', Ikon_SEO_Portfolio_Quality_Guard::CRON_HOOK );
		}
		update_option( 'ikon_seo_db_version', self::DB_VERSION, false );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'ikon_seo_daily_monitor' );
		wp_clear_scheduled_hook( 'ikon_seo_evidence_crawl' );
		wp_clear_scheduled_hook( Ikon_SEO_Search_Intelligence::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Technical_Intelligence::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Automation::RUNNER_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Automation::DAILY_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Automation::WEEKLY_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Local_Growth::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Visibility_Brand_Intelligence::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Agency_Command_Centre::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Closed_Loop::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Indexation_Intelligence::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Production_Health::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Production_Health::HEARTBEAT_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Structured_Media_Governance::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Experiments_Claims_Revenue::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_International_Server_Intelligence::CRON_HOOK );
		wp_clear_scheduled_hook( Ikon_SEO_Portfolio_Quality_Guard::CRON_HOOK );
	}

	public static function maybe_upgrade() {
		if ( self::DB_VERSION !== get_option( 'ikon_seo_db_version' ) ) {
			self::activate();
			Ikon_SEO_Profile::migrate_legacy_settings();
		}

		$settings = get_option( self::OPTION_KEY, array() );
		if ( is_array( $settings ) && '25.0' !== (string) ( $settings['component_version'] ?? '' ) ) {
			$settings['component_version'] = '25.0';
			update_option( self::OPTION_KEY, $settings, false );
		}
	}

	public function filter_language_attributes( $output, $doctype ) {
		if ( ! is_singular( 'page' ) ) {
			return $output;
		}

		$language = get_post_meta( get_queried_object_id(), '_ikon_seo_language', true );
		if ( ! $language || ! preg_match( '/^[a-z]{2,3}(?:-[A-Z]{2})?$/', $language ) ) {
			return $output;
		}

		if ( preg_match( '/lang=("|\')[^"\']+("|\')/', $output ) ) {
			return preg_replace( '/lang=("|\')[^"\']+("|\')/', 'lang="' . esc_attr( $language ) . '"', $output );
		}

		return trim( $output . ' lang="' . esc_attr( $language ) . '"' );
	}
}
