<?php

defined( 'ABSPATH' ) || exit;

final class Ikon_SEO_Plugin {
	const OPTION_KEY = 'ikon_seo_settings';
	const DB_VERSION = '6.0';

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
		$media     = new Ikon_SEO_Media();
		$workflow  = new Ikon_SEO_Workflow( $logger, $schema, $quality, $local );
		$migration = new Ikon_SEO_Migration( $logger, $inventory, $local );
		$search_console = new Ikon_SEO_Search_Console( $crypto, $logger );
		$queue          = new Ikon_SEO_Queue( $profile );
		$monitor        = new Ikon_SEO_Monitor( $search_console, $logger );
		$monitor->register_hooks();

		new Ikon_SEO_REST( $auth, $connection, $profile, $validator, $renderer, $schema, $quality, $inventory, $media, $workflow, $migration, $search_console, $queue, $monitor, $local, $gbp, $logger );

		if ( is_admin() ) {
			new Ikon_SEO_Admin( $logger, $connection, $profile, $inventory, $workflow, $migration, $search_console, $queue, $monitor, $local, $gbp );
		}

		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_upgrade' ), 20 );
		add_action( 'wp_head', array( $schema, 'print_fallback_graph' ), 30 );
		add_filter( 'rank_math/json_ld', array( $schema, 'merge_rank_math_graph' ), 99, 2 );
		add_filter( 'language_attributes', array( $this, 'filter_language_attributes' ), 10, 2 );
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
			'component_version'  => '6.0',
			'connection_verified_at' => '',
			'connection_last_seen_at'=> '',
			'token_hash'         => '',
			'token_hint'         => '',
			'gsc_client_id'      => '',
			'gsc_client_secret'  => '',
			'gsc_refresh_token'  => '',
			'gsc_property'       => '',
			'gsc_last_error'     => '',
			'gbp_client_id'      => '',
			'gbp_client_secret'  => '',
			'gbp_refresh_token'  => '',
			'gbp_account'        => '',
			'gbp_last_error'     => '',
			'local_module_enabled'=> 1,
			'local_similarity_threshold'=> 78,
			'citation_review_days'=> 180,
			'monitoring_enabled' => 0,
			'default_review_days'=> 180,
			'review_alert_days'  => 14,
			'performance_drop_percent' => 30,
			'performance_min_impressions'=> 50,
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

		if ( false === get_option( self::OPTION_KEY, false ) ) {
			$defaults              = self::defaults();
			$defaults['author_id'] = get_current_user_id();
			add_option( self::OPTION_KEY, $defaults, '', false );
		} else {
			$settings                      = get_option( self::OPTION_KEY, array() );
			$settings['component_version'] = '6.0';
			update_option( self::OPTION_KEY, $settings, false );
		}

		Ikon_SEO_Profile::migrate_legacy_settings();
		if ( ! wp_next_scheduled( 'ikon_seo_daily_monitor' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ikon_seo_daily_monitor' );
		}
		update_option( 'ikon_seo_db_version', self::DB_VERSION, false );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'ikon_seo_daily_monitor' );
	}

	public static function maybe_upgrade() {
		if ( self::DB_VERSION !== get_option( 'ikon_seo_db_version' ) ) {
			self::activate();
			Ikon_SEO_Profile::migrate_legacy_settings();
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
