<?php

defined( 'ABSPATH' ) || exit;

class Ikon_SEO_Admin {
	private $logger;
	private $connection;
	private $profile;
	private $inventory;
	private $rank_math;
	private $image_audit;
	private $redirect_audit;
	private $workflow;
	private $migration;
	private $search_console;
	private $search_intelligence;
	private $analytics;
	private $crawler;
	private $technical;
	private $indexation;
	private $production_health;
	private $platform_hardening;
	private $deployment_control;
	private $production_certification;
	private $staging_validation;
	private $competitor_content;
	private $authority;
	private $strategy;
	private $publisher;
	private $diagnostics;
	private $queue;
	private $monitor;
	private $automation;
	private $history;
	private $local_growth;
	private $visibility_brand;
	private $closed_loop;
	private $agency_command;
	private $portfolio_governance;
	private $agency_service_levels;
	private $executive_command;
	private $structured_media_governance;
	private $experiments_claims_revenue;
	private $international_server;
	private $portfolio_quality_guard;
	private $auto_discovery;
	private $discovery_review;
	private $guided_launch;
	private $opportunity_engine;
	private $content_workbench;
	private $editorial_review;
	private $publishing_readiness;
	private $search_impact;
	private $pattern_library;
	private $local;
	private $gbp;

	public function __construct(
		Ikon_SEO_Logger $logger,
		Ikon_SEO_Connection $connection,
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Rank_Math $rank_math,
		Ikon_SEO_Image_Audit $image_audit,
		Ikon_SEO_Redirect_Audit $redirect_audit,
		Ikon_SEO_Workflow $workflow,
		Ikon_SEO_Migration $migration,
		Ikon_SEO_Search_Console $search_console,
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Analytics $analytics,
		Ikon_SEO_Crawler $crawler,
		Ikon_SEO_Technical_Intelligence $technical,
		Ikon_SEO_Indexation_Intelligence $indexation,
		Ikon_SEO_Production_Health $production_health,
		Ikon_SEO_Platform_Hardening $platform_hardening,
		Ikon_SEO_Deployment_Control $deployment_control,
		Ikon_SEO_Production_Certification $production_certification,
		Ikon_SEO_Staging_Validation $staging_validation,
		Ikon_SEO_Competitor_Content_Intelligence $competitor_content,
		Ikon_SEO_Authority_Intelligence $authority,
		Ikon_SEO_Strategy $strategy,
		Ikon_SEO_Publisher_Intelligence $publisher,
		Ikon_SEO_Diagnostics $diagnostics,
		Ikon_SEO_Queue $queue,
		Ikon_SEO_Monitor $monitor,
		Ikon_SEO_Automation $automation,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Local_Growth $local_growth,
		Ikon_SEO_Visibility_Brand_Intelligence $visibility_brand,
		Ikon_SEO_Closed_Loop $closed_loop,
		Ikon_SEO_Agency_Command_Centre $agency_command,
		Ikon_SEO_Portfolio_Governance $portfolio_governance,
		Ikon_SEO_Agency_Service_Levels $agency_service_levels,
		Ikon_SEO_Executive_Command_Centre $executive_command,
		Ikon_SEO_Structured_Media_Governance $structured_media_governance,
		Ikon_SEO_Experiments_Claims_Revenue $experiments_claims_revenue,
		Ikon_SEO_International_Server_Intelligence $international_server,
		Ikon_SEO_Portfolio_Quality_Guard $portfolio_quality_guard,
		Ikon_SEO_Auto_Discovery $auto_discovery,
		Ikon_SEO_Discovery_Review $discovery_review,
		Ikon_SEO_Guided_Launch $guided_launch,
		Ikon_SEO_Opportunity_Engine $opportunity_engine,
		Ikon_SEO_Content_Workbench $content_workbench,
		Ikon_SEO_Editorial_Review $editorial_review,
		Ikon_SEO_Publishing_Readiness $publishing_readiness,
		Ikon_SEO_Search_Impact $search_impact,
		Ikon_SEO_Pattern_Library $pattern_library,
		Ikon_SEO_Local $local,
		Ikon_SEO_GBP $gbp
	) {
		$this->logger     = $logger;
		$this->connection = $connection;
		$this->profile   = $profile;
		$this->inventory = $inventory;
		$this->rank_math = $rank_math;
		$this->image_audit = $image_audit;
		$this->redirect_audit = $redirect_audit;
		$this->workflow  = $workflow;
		$this->migration = $migration;
		$this->search_console = $search_console;
		$this->search_intelligence = $search_intelligence;
		$this->analytics      = $analytics;
		$this->crawler        = $crawler;
		$this->technical      = $technical;
		$this->indexation     = $indexation;
		$this->production_health = $production_health;
		$this->platform_hardening = $platform_hardening;
		$this->deployment_control = $deployment_control;
		$this->production_certification = $production_certification;
		$this->staging_validation = $staging_validation;
		$this->competitor_content = $competitor_content;
		$this->authority       = $authority;
		$this->strategy        = $strategy;
		$this->publisher       = $publisher;
		$this->diagnostics    = $diagnostics;
		$this->queue          = $queue;
		$this->monitor        = $monitor;
		$this->automation     = $automation;
		$this->history        = $history;
		$this->local_growth   = $local_growth;
		$this->visibility_brand = $visibility_brand;
		$this->closed_loop = $closed_loop;
		$this->agency_command = $agency_command;
		$this->portfolio_governance = $portfolio_governance;
		$this->agency_service_levels = $agency_service_levels;
		$this->executive_command = $executive_command;
		$this->structured_media_governance = $structured_media_governance;
		$this->experiments_claims_revenue = $experiments_claims_revenue;
		$this->international_server = $international_server;
		$this->portfolio_quality_guard = $portfolio_quality_guard;
		$this->auto_discovery = $auto_discovery;
		$this->discovery_review = $discovery_review;
		$this->guided_launch = $guided_launch;
		$this->opportunity_engine = $opportunity_engine;
		$this->content_workbench = $content_workbench;
		$this->editorial_review = $editorial_review;
		$this->publishing_readiness = $publishing_readiness;
		$this->search_impact = $search_impact;
		$this->pattern_library = $pattern_library;
		$this->local          = $local;
		$this->gbp            = $gbp;

		Ikon_SEO_Agency::bootstrap_owner();

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_ikon_seo_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_ikon_seo_save_profile', array( $this, 'save_profile' ) );
		add_action( 'admin_post_ikon_seo_save_strategy', array( $this, 'save_strategy' ) );
		add_action( 'admin_post_ikon_seo_run_auto_discovery', array( $this, 'run_auto_discovery' ) );
		add_action( 'admin_post_ikon_seo_apply_auto_discovery', array( $this, 'apply_auto_discovery' ) );
		add_action( 'admin_post_ikon_seo_save_auto_discovery_settings', array( $this, 'save_auto_discovery_settings' ) );
		add_action( 'admin_post_ikon_seo_update_discovery_fact', array( $this, 'update_discovery_fact' ) );
		add_action( 'admin_post_ikon_seo_accept_high_confidence_facts', array( $this, 'accept_high_confidence_facts' ) );
		add_action( 'admin_post_ikon_seo_resolve_discovery_conflict', array( $this, 'resolve_discovery_conflict' ) );
		add_action( 'admin_post_ikon_seo_apply_confirmed_discovery_facts', array( $this, 'apply_confirmed_discovery_facts' ) );
		add_action( 'admin_post_ikon_seo_run_guided_launch', array( $this, 'run_guided_launch' ) );
		add_action( 'admin_post_ikon_seo_acknowledge_discovery_conflicts', array( $this, 'acknowledge_discovery_conflicts' ) );
		add_action( 'admin_post_ikon_seo_rebuild_opportunity_engine', array( $this, 'rebuild_opportunity_engine' ) );
		add_action( 'admin_post_ikon_seo_import_opportunity_evidence', array( $this, 'import_opportunity_evidence' ) );
		add_action( 'admin_post_ikon_seo_update_opportunity_status', array( $this, 'update_opportunity_status' ) );
		add_action( 'admin_post_ikon_seo_save_opportunity_engine_settings', array( $this, 'save_opportunity_engine_settings' ) );
		add_action( 'admin_post_ikon_seo_create_content_brief', array( $this, 'create_content_brief' ) );
		add_action( 'admin_post_ikon_seo_approve_content_brief', array( $this, 'approve_content_brief' ) );
		add_action( 'admin_post_ikon_seo_reject_content_brief', array( $this, 'reject_content_brief' ) );
		add_action( 'admin_post_ikon_seo_create_content_scaffold', array( $this, 'create_content_scaffold' ) );
		add_action( 'admin_post_ikon_seo_evaluate_content_draft', array( $this, 'evaluate_content_draft' ) );
		add_action( 'admin_post_ikon_seo_mark_content_ready', array( $this, 'mark_content_ready' ) );
		add_action( 'admin_post_ikon_seo_editorial_action', array( $this, 'editorial_action' ) );
		add_action( 'admin_post_ikon_seo_publishing_action', array( $this, 'publishing_action' ) );
		add_action( 'admin_post_ikon_seo_search_impact_action', array( $this, 'search_impact_action' ) );
		add_action( 'admin_post_ikon_seo_pattern_library_action', array( $this, 'pattern_library_action' ) );
		add_action( 'admin_post_ikon_seo_export_profile', array( $this, 'export_profile' ) );
		add_action( 'admin_post_ikon_seo_import_profile', array( $this, 'import_profile' ) );
		add_action( 'admin_post_ikon_seo_preview_migration', array( $this, 'preview_migration' ) );
		add_action( 'admin_post_ikon_seo_apply_migration', array( $this, 'apply_migration' ) );
		add_action( 'admin_post_ikon_seo_start_pairing', array( $this, 'start_pairing' ) );
		add_action( 'admin_post_ikon_seo_test_connection', array( $this, 'test_connection' ) );
		add_action( 'admin_post_ikon_seo_generate_token', array( $this, 'generate_token' ) );
		add_action( 'admin_post_ikon_seo_revoke_token', array( $this, 'revoke_token' ) );
		add_action( 'admin_post_ikon_seo_merge_review', array( $this, 'merge_review' ) );
		add_action( 'admin_post_ikon_seo_rollback_page', array( $this, 'rollback_page' ) );
		add_action( 'admin_post_ikon_seo_refresh_inventory', array( $this, 'refresh_inventory' ) );
		add_action( 'admin_post_ikon_seo_refresh_rank_math', array( $this, 'refresh_rank_math' ) );
		add_action( 'admin_post_ikon_seo_refresh_image_audit', array( $this, 'refresh_image_audit' ) );
		add_action( 'admin_post_ikon_seo_refresh_redirects', array( $this, 'refresh_redirects' ) );
		add_action( 'admin_post_ikon_seo_save_agency_access', array( $this, 'save_agency_access' ) );
		add_action( 'admin_post_ikon_seo_add_history_note', array( $this, 'add_history_note' ) );
		add_action( 'admin_post_ikon_seo_update_history_status', array( $this, 'update_history_status' ) );
		add_action( 'admin_post_ikon_seo_export_workspace_setup', array( $this, 'export_workspace_setup' ) );
		add_action( 'admin_post_ikon_seo_gsc_save_credentials', array( $this, 'gsc_save_credentials' ) );
		add_action( 'admin_post_ikon_seo_gsc_connect', array( $this, 'gsc_connect' ) );
		add_action( 'admin_post_ikon_seo_gsc_callback', array( $this, 'gsc_callback' ) );
		add_action( 'admin_post_ikon_seo_gsc_select_property', array( $this, 'gsc_select_property' ) );
		add_action( 'admin_post_ikon_seo_gsc_disconnect', array( $this, 'gsc_disconnect' ) );
		add_action( 'admin_post_ikon_seo_gsc_refresh', array( $this, 'gsc_refresh' ) );
		add_action( 'admin_post_ikon_seo_refresh_search_intelligence', array( $this, 'refresh_search_intelligence' ) );
		add_action( 'admin_post_ikon_seo_refresh_technical_intelligence', array( $this, 'refresh_technical_intelligence' ) );
		add_action( 'admin_post_ikon_seo_check_technical_urls', array( $this, 'check_technical_urls' ) );
		add_action( 'admin_post_ikon_seo_save_pagespeed_key', array( $this, 'save_pagespeed_key' ) );
		add_action( 'admin_post_ikon_seo_refresh_pagespeed', array( $this, 'refresh_pagespeed' ) );
		add_action( 'admin_post_ikon_seo_save_indexation_settings', array( $this, 'save_indexation_settings' ) );
		add_action( 'admin_post_ikon_seo_seed_indexation', array( $this, 'seed_indexation' ) );
		add_action( 'admin_post_ikon_seo_run_indexation_batch', array( $this, 'run_indexation_batch' ) );
		add_action( 'admin_post_ikon_seo_inspect_indexation_url', array( $this, 'inspect_indexation_url' ) );
		add_action( 'admin_post_ikon_seo_run_production_health', array( $this, 'run_production_health' ) );
		add_action( 'admin_post_ikon_seo_platform_hardening_action', array( $this, 'platform_hardening_action' ) );
		add_action( 'admin_post_ikon_seo_deployment_control_action', array( $this, 'deployment_control_action' ) );
		add_action( 'admin_post_ikon_seo_production_certification_action', array( $this, 'production_certification_action' ) );
		add_action( 'admin_post_ikon_seo_staging_validation_action', array( $this, 'staging_validation_action' ) );
		add_action( 'admin_post_ikon_seo_save_governance_settings', array( $this, 'save_governance_settings' ) );
		add_action( 'admin_post_ikon_seo_run_schema_governance', array( $this, 'run_schema_governance' ) );
		add_action( 'admin_post_ikon_seo_run_media_governance', array( $this, 'run_media_governance' ) );
		add_action( 'admin_post_ikon_seo_audit_governance_url', array( $this, 'audit_governance_url' ) );
		add_action( 'admin_post_ikon_seo_save_media_rights', array( $this, 'save_media_rights' ) );
		add_action( 'admin_post_ikon_seo_cleanup_governance', array( $this, 'cleanup_governance' ) );
		add_action( 'admin_post_ikon_seo_save_ecr_settings', array( $this, 'save_ecr_settings' ) );
		add_action( 'admin_post_ikon_seo_create_experiment', array( $this, 'create_experiment' ) );
		add_action( 'admin_post_ikon_seo_update_experiment', array( $this, 'update_experiment' ) );
		add_action( 'admin_post_ikon_seo_capture_experiment_measurement', array( $this, 'capture_experiment_measurement' ) );
		add_action( 'admin_post_ikon_seo_save_claim_record', array( $this, 'save_claim_record' ) );
		add_action( 'admin_post_ikon_seo_update_claim_record', array( $this, 'update_claim_record' ) );
		add_action( 'admin_post_ikon_seo_save_revenue_event', array( $this, 'save_revenue_event' ) );
		add_action( 'admin_post_ikon_seo_cleanup_ecr', array( $this, 'cleanup_ecr' ) );
		add_action( 'admin_post_ikon_seo_save_international_server_settings', array( $this, 'save_international_server_settings' ) );
		add_action( 'admin_post_ikon_seo_run_international_audit', array( $this, 'run_international_audit' ) );
		add_action( 'admin_post_ikon_seo_audit_international_url', array( $this, 'audit_international_url' ) );
		add_action( 'admin_post_ikon_seo_import_server_log', array( $this, 'import_server_log' ) );
		add_action( 'admin_post_ikon_seo_cleanup_server_logs', array( $this, 'cleanup_server_logs' ) );
		add_action( 'admin_post_ikon_seo_save_portfolio_quality_settings', array( $this, 'save_portfolio_quality_settings' ) );
		add_action( 'admin_post_ikon_seo_scan_portfolio_quality', array( $this, 'scan_portfolio_quality' ) );
		add_action( 'admin_post_ikon_seo_evaluate_portfolio_quality', array( $this, 'evaluate_portfolio_quality' ) );
		add_action( 'admin_post_ikon_seo_import_portfolio_quality', array( $this, 'import_portfolio_quality' ) );
		add_action( 'admin_post_ikon_seo_export_portfolio_quality', array( $this, 'export_portfolio_quality' ) );
		add_action( 'admin_post_ikon_seo_update_portfolio_quality_finding', array( $this, 'update_portfolio_quality_finding' ) );
		add_action( 'admin_post_ikon_seo_cleanup_portfolio_quality', array( $this, 'cleanup_portfolio_quality' ) );
		add_action( 'admin_post_ikon_seo_save_competitor_research', array( $this, 'save_competitor_research' ) );
		add_action( 'admin_post_ikon_seo_archive_competitor_research', array( $this, 'archive_competitor_research' ) );
		add_action( 'admin_post_ikon_seo_analyse_content_page', array( $this, 'analyse_content_page' ) );
		add_action( 'admin_post_ikon_seo_import_authority_csv', array( $this, 'import_authority_csv' ) );
		add_action( 'admin_post_ikon_seo_archive_authority_link', array( $this, 'archive_authority_link' ) );
		add_action( 'admin_post_ikon_seo_ga_save_credentials', array( $this, 'ga_save_credentials' ) );
		add_action( 'admin_post_ikon_seo_ga_connect', array( $this, 'ga_connect' ) );
		add_action( 'admin_post_ikon_seo_ga_callback', array( $this, 'ga_callback' ) );
		add_action( 'admin_post_ikon_seo_ga_select_property', array( $this, 'ga_select_property' ) );
		add_action( 'admin_post_ikon_seo_ga_disconnect', array( $this, 'ga_disconnect' ) );
		add_action( 'admin_post_ikon_seo_ga_refresh', array( $this, 'ga_refresh' ) );
		add_action( 'admin_post_ikon_seo_crawl_evidence', array( $this, 'crawl_evidence' ) );
		add_action( 'admin_post_ikon_seo_refresh_diagnostics', array( $this, 'refresh_diagnostics' ) );
		add_action( 'admin_post_ikon_seo_queue_import', array( $this, 'queue_import' ) );
		add_action( 'admin_post_ikon_seo_queue_status', array( $this, 'queue_status' ) );
		add_action( 'admin_post_ikon_seo_monitor_run', array( $this, 'monitor_run' ) );
		add_action( 'admin_post_ikon_seo_monitor_schedule', array( $this, 'monitor_schedule' ) );
		add_action( 'admin_post_ikon_seo_monitor_reviewed', array( $this, 'monitor_reviewed' ) );
		add_action( 'admin_post_ikon_seo_create_workflow', array( $this, 'create_workflow' ) );
		add_action( 'admin_post_ikon_seo_update_workflow_task', array( $this, 'update_workflow_task' ) );
		add_action( 'admin_post_ikon_seo_approve_workflow_task', array( $this, 'approve_workflow_task' ) );
		add_action( 'admin_post_ikon_seo_run_workflow_automation', array( $this, 'run_workflow_automation' ) );
		add_action( 'admin_post_ikon_seo_save_workflow_automation', array( $this, 'save_workflow_automation' ) );
		add_action( 'admin_post_ikon_seo_generate_workflow_briefing', array( $this, 'generate_workflow_briefing' ) );
		add_action( 'admin_post_ikon_seo_save_publisher_keywords', array( $this, 'save_publisher_keywords' ) );
		add_action( 'admin_post_ikon_seo_save_publisher_hub', array( $this, 'save_publisher_hub' ) );
		add_action( 'admin_post_ikon_seo_save_publisher_item', array( $this, 'save_publisher_item' ) );
		add_action( 'admin_post_ikon_seo_save_publisher_contributor', array( $this, 'save_publisher_contributor' ) );
		add_action( 'admin_post_ikon_seo_generate_publisher_calendar', array( $this, 'generate_publisher_calendar' ) );
		add_action( 'admin_post_ikon_seo_review_publisher_lifecycle', array( $this, 'review_publisher_lifecycle' ) );
		add_action( 'admin_post_ikon_seo_evaluate_publisher_post', array( $this, 'evaluate_publisher_post' ) );
		add_action( 'admin_post_ikon_seo_export_publisher_signatures', array( $this, 'export_publisher_signatures' ) );
		add_action( 'admin_post_ikon_seo_import_publisher_signatures', array( $this, 'import_publisher_signatures' ) );
		add_action( 'admin_post_ikon_seo_refresh_local_growth', array( $this, 'refresh_local_growth' ) );
		add_action( 'admin_post_ikon_seo_save_local_growth_settings', array( $this, 'save_local_growth_settings' ) );
		add_action( 'admin_post_ikon_seo_save_local_prominence', array( $this, 'save_local_prominence' ) );
		add_action( 'admin_post_ikon_seo_update_local_review_task', array( $this, 'update_local_review_task' ) );
		add_action( 'admin_post_ikon_seo_save_visibility_brand_settings', array( $this, 'save_visibility_brand_settings' ) );
		add_action( 'admin_post_ikon_seo_save_visibility_observation', array( $this, 'save_visibility_observation' ) );
		add_action( 'admin_post_ikon_seo_save_brand_mention', array( $this, 'save_brand_mention' ) );
		add_action( 'admin_post_ikon_seo_update_brand_mention', array( $this, 'update_brand_mention' ) );
		add_action( 'admin_post_ikon_seo_refresh_visibility_snapshot', array( $this, 'refresh_visibility_snapshot' ) );
		add_action( 'admin_post_ikon_seo_save_closed_loop_settings', array( $this, 'save_closed_loop_settings' ) );
		add_action( 'admin_post_ikon_seo_refresh_closed_loop_plan', array( $this, 'refresh_closed_loop_plan' ) );
		add_action( 'admin_post_ikon_seo_update_closed_loop_recommendation', array( $this, 'update_closed_loop_recommendation' ) );
		add_action( 'admin_post_ikon_seo_run_closed_loop_measurements', array( $this, 'run_closed_loop_measurements' ) );
		add_action( 'admin_post_ikon_seo_create_closed_loop_checkpoint', array( $this, 'create_closed_loop_checkpoint' ) );
		add_action( 'admin_post_ikon_seo_restore_closed_loop_checkpoint', array( $this, 'restore_closed_loop_checkpoint' ) );
		add_action( 'admin_post_ikon_seo_save_agency_command_settings', array( $this, 'save_agency_command_settings' ) );
		add_action( 'admin_post_ikon_seo_portfolio_governance_action', array( $this, 'portfolio_governance_action' ) );
		add_action( 'admin_post_ikon_seo_agency_service_levels_action', array( $this, 'agency_service_levels_action' ) );
		add_action( 'admin_post_ikon_seo_download_client_service_report', array( $this, 'download_client_service_report' ) );
		add_action( 'admin_post_ikon_seo_generate_agency_agent_key', array( $this, 'generate_agency_agent_key' ) );
		add_action( 'admin_post_ikon_seo_revoke_agency_agent_key', array( $this, 'revoke_agency_agent_key' ) );
		add_action( 'admin_post_ikon_seo_add_managed_site', array( $this, 'add_managed_site' ) );
		add_action( 'admin_post_ikon_seo_update_managed_site', array( $this, 'update_managed_site' ) );
		add_action( 'admin_post_ikon_seo_delete_managed_site', array( $this, 'delete_managed_site' ) );
		add_action( 'admin_post_ikon_seo_refresh_managed_site', array( $this, 'refresh_managed_site' ) );
		add_action( 'admin_post_ikon_seo_refresh_all_managed_sites', array( $this, 'refresh_all_managed_sites' ) );
		add_action( 'admin_post_ikon_seo_record_agency_usage', array( $this, 'record_agency_usage' ) );
		add_action( 'admin_post_ikon_seo_resolve_agency_alert', array( $this, 'resolve_agency_alert' ) );
		add_action( 'admin_post_ikon_seo_refresh_executive_command', array( $this, 'refresh_executive_command' ) );
		add_action( 'admin_post_ikon_seo_update_executive_risk', array( $this, 'update_executive_risk' ) );
		add_action( 'admin_post_ikon_seo_update_executive_notification', array( $this, 'update_executive_notification' ) );
		add_action( 'admin_post_ikon_seo_export_agency_report', array( $this, 'export_agency_report' ) );
		add_action( 'admin_post_ikon_seo_export_agency_portfolio', array( $this, 'export_agency_portfolio' ) );
		add_action( 'admin_post_ikon_seo_local_save_location', array( $this, 'local_save_location' ) );
		add_action( 'admin_post_ikon_seo_local_delete_location', array( $this, 'local_delete_location' ) );
		add_action( 'admin_post_ikon_seo_local_save_citation', array( $this, 'local_save_citation' ) );
		add_action( 'admin_post_ikon_seo_local_delete_citation', array( $this, 'local_delete_citation' ) );
		add_action( 'admin_post_ikon_seo_local_import_citations', array( $this, 'local_import_citations' ) );
		add_action( 'admin_post_ikon_seo_local_export_citations', array( $this, 'local_export_citations' ) );
		add_action( 'admin_post_ikon_seo_local_save_rank', array( $this, 'local_save_rank' ) );
		add_action( 'admin_post_ikon_seo_local_import_ranks', array( $this, 'local_import_ranks' ) );
		add_action( 'admin_post_ikon_seo_local_generate_utm', array( $this, 'local_generate_utm' ) );
		add_action( 'admin_post_ikon_seo_gbp_save_credentials', array( $this, 'gbp_save_credentials' ) );
		add_action( 'admin_post_ikon_seo_gbp_set_availability', array( $this, 'gbp_set_availability' ) );
		add_action( 'admin_post_ikon_seo_gbp_connect', array( $this, 'gbp_connect' ) );
		add_action( 'admin_post_ikon_seo_gbp_callback', array( $this, 'gbp_callback' ) );
		add_action( 'admin_post_ikon_seo_gbp_select_account', array( $this, 'gbp_select_account' ) );
		add_action( 'admin_post_ikon_seo_gbp_link_location', array( $this, 'gbp_link_location' ) );
		add_action( 'admin_post_ikon_seo_gbp_unlink_location', array( $this, 'gbp_unlink_location' ) );
		add_action( 'admin_post_ikon_seo_gbp_disconnect', array( $this, 'gbp_disconnect' ) );
		add_action( 'admin_post_ikon_seo_gbp_refresh', array( $this, 'gbp_refresh' ) );
		add_action( 'admin_post_ikon_seo_gbp_stage_draft', array( $this, 'gbp_stage_draft' ) );
		add_action( 'admin_post_ikon_seo_gbp_approve_draft', array( $this, 'gbp_approve_draft' ) );
		add_action( 'admin_post_ikon_seo_gbp_reject_draft', array( $this, 'gbp_reject_draft' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( IKON_SEO_FILE ), array( $this, 'plugin_links' ) );
	}

	public function menu() {
		add_menu_page(
			__( 'Ikon SEO', 'ikon-seo' ),
			__( 'Ikon SEO', 'ikon-seo' ),
			'manage_options',
			'ikon-seo',
			array( $this, 'render' ),
			'dashicons-chart-line',
			58
		);

		add_submenu_page(
			'edit.php',
			__( 'Ikon SEO Editorial Review', 'ikon-seo' ),
			__( 'Ikon SEO Review', 'ikon-seo' ),
			'edit_posts',
			'ikon-seo-editorial',
			array( $this, 'render_editorial_portal' )
		);
	}

	public function assets( $hook ) {
		if ( ! in_array( $hook, array( 'toplevel_page_ikon-seo', 'posts_page_ikon-seo-editorial' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'ikon-seo-admin', IKON_SEO_URL . 'assets/admin.css', array(), IKON_SEO_VERSION );
		wp_enqueue_script( 'ikon-seo-admin', IKON_SEO_URL . 'assets/admin.js', array(), IKON_SEO_VERSION, true );
	}


	public function render_editorial_portal() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		?><div class="wrap ikon-seo-wrap"><div class="ikon-seo-header"><div><p class="ikon-seo-kicker"><?php esc_html_e( 'IKON DIGITALS', 'ikon-seo' ); ?></p><h1><?php esc_html_e( 'Ikon SEO Editorial Review', 'ikon-seo' ); ?></h1><p><?php esc_html_e( 'Your assigned controlled drafts, review rounds and revision decisions.', 'ikon-seo' ); ?></p></div><span class="ikon-seo-version">v<?php echo esc_html( IKON_SEO_VERSION ); ?></span></div><?php
		if ( ! empty( $_GET['editorial-review-updated'] ) ) {
			?><div class="notice notice-success inline"><p><?php esc_html_e( 'Editorial review was updated. No content was published.', 'ikon-seo' ); ?></p></div><?php
		}
		if ( ! empty( $_GET['ikon-error'] ) ) {
			?><div class="notice notice-error inline"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['ikon-error'] ) ) ); ?></p></div><?php
		}
		$this->render_editorial_review();
		?></div><?php
	}

	public function plugin_links( $links ) {
		$label = Ikon_SEO_Agency::can_manage() ? __( 'Settings', 'ikon-seo' ) : __( 'Open', 'ikon-seo' );
		$tab   = Ikon_SEO_Agency::can_manage() ? '&tab=settings' : '';
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=ikon-seo' . $tab ) ) . '">' . esc_html( $label ) . '</a>' );
		return $links;
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$agency = Ikon_SEO_Agency::can_manage();
		$tab = sanitize_key( $_GET['tab'] ?? 'dashboard' );
		$all_tabs = array( 'dashboard', 'auto-discovery', 'discovery-review', 'guided-launch', 'strategy', 'workflow-automation', 'publisher-intelligence', 'profile', 'connection', 'reviews', 'history', 'inventory', 'seo-health', 'diagnostics', 'search-intelligence', 'opportunity-engine', 'content-workbench', 'editorial-review', 'publishing-readiness', 'search-impact', 'pattern-library', 'agency-governance', 'agency-service-levels', 'content-intelligence', 'authority-intelligence', 'visibility-brand', 'closed-loop', 'platform-health', 'deployment-control', 'production-certification', 'staging-validation', 'indexation', 'governance', 'experiments-claims-revenue', 'international-server', 'portfolio-quality', 'technical-intelligence', 'analytics', 'image-audit', 'redirects', 'local-growth', 'local-seo', 'business-profile', 'search-console', 'queue', 'monitor', 'agency-command-centre', 'migration', 'settings', 'activity', 'agency' );
		if ( ! in_array( $tab, $all_tabs, true ) || ( ! $agency && ! in_array( $tab, Ikon_SEO_Agency::customer_tabs(), true ) ) ) {
			$tab = 'dashboard';
		}

		$settings = Ikon_SEO_Plugin::settings();
		?>
		<div class="wrap ikon-seo-wrap">
			<div class="ikon-seo-header">
				<div>
					<p class="ikon-seo-kicker"><?php esc_html_e( 'IKON DIGITALS', 'ikon-seo' ); ?></p>
					<h1><?php esc_html_e( 'Ikon SEO', 'ikon-seo' ); ?></h1>
					<p><?php esc_html_e( 'Create and improve structured SEO pages with a secure approval-first workflow.', 'ikon-seo' ); ?></p>
				</div>
				<span class="ikon-seo-version">v<?php echo esc_html( IKON_SEO_VERSION ); ?></span>
			</div>

			<nav class="nav-tab-wrapper">
				<?php
				$this->tab_link( 'dashboard', 'Overview', $tab );
				if ( $agency ) {
					$this->tab_link( 'auto-discovery', 'Auto Discovery', $tab );
					$this->tab_link( 'discovery-review', 'Fact Review', $tab );
					$this->tab_link( 'guided-launch', 'Guided Launch', $tab );
				}
				$this->tab_link( 'strategy', 'Website Strategy', $tab );
				$this->tab_link( 'workflow-automation', 'Workflow Automation', $tab );
				$this->tab_link( 'publisher-intelligence', 'Publisher Intelligence', $tab );
				$this->tab_link( 'reviews', 'Reviews', $tab );
				$this->tab_link( 'history', 'Project History', $tab );
				$this->tab_link( 'inventory', 'Site Inventory', $tab );
				$this->tab_link( 'seo-health', 'SEO Health', $tab );
				$this->tab_link( 'diagnostics', 'Page Diagnostics', $tab );
				$this->tab_link( 'search-intelligence', 'Search Intelligence', $tab );
				$this->tab_link( 'opportunity-engine', 'Opportunity Engine', $tab );
				$this->tab_link( 'content-workbench', 'Content Workbench', $tab );
				$this->tab_link( 'editorial-review', 'Editorial Review', $tab );
				$this->tab_link( 'publishing-readiness', 'Publishing Readiness', $tab );
				$this->tab_link( 'search-impact', 'Search Impact', $tab );
				$this->tab_link( 'pattern-library', 'Pattern Library', $tab );
				$this->tab_link( 'agency-governance', 'Portfolio Governance', $tab );
				$this->tab_link( 'agency-service-levels', 'Service Levels', $tab );
				$this->tab_link( 'content-intelligence', 'Content Intelligence', $tab );
				$this->tab_link( 'authority-intelligence', 'Authority Intelligence', $tab );
				$this->tab_link( 'visibility-brand', 'Visibility & Brand', $tab );
				$this->tab_link( 'closed-loop', 'Operating Plan', $tab );
				if ( $agency ) { $this->tab_link( 'platform-health', 'Platform Health', $tab ); $this->tab_link( 'deployment-control', 'Deployment Control', $tab ); $this->tab_link( 'production-certification', 'Production Certification', $tab ); $this->tab_link( 'staging-validation', 'Staging Validation', $tab ); }
				$this->tab_link( 'indexation', 'Indexation Intelligence', $tab );
				$this->tab_link( 'governance', 'Structured Data & Media', $tab );
				$this->tab_link( 'experiments-claims-revenue', 'Experiments, Claims & Revenue', $tab );
				$this->tab_link( 'international-server', 'International & Server', $tab );
				$this->tab_link( 'portfolio-quality', 'Portfolio Quality', $tab );
				$this->tab_link( 'technical-intelligence', 'Technical Intelligence', $tab );
				$this->tab_link( 'analytics', 'Analytics', $tab );
				$this->tab_link( 'image-audit', 'Image Audit', $tab );
				$this->tab_link( 'redirects', 'Redirect Opportunities', $tab );
				$this->tab_link( 'local-growth', 'Local Growth', $tab );
				$this->tab_link( 'local-seo', 'Local SEO', $tab );
				$this->tab_link( 'business-profile', 'Business Profile', $tab );
				$this->tab_link( 'queue', 'Page Plans', $tab );
				$this->tab_link( 'monitor', 'Refresh Monitor', $tab );
				if ( $agency ) {
					$this->tab_link( 'agency-command-centre', 'Agency Command Centre', $tab );
					$this->tab_link( 'profile', 'Website Profile', $tab );
					$this->tab_link( 'search-console', 'Search Console', $tab );
					$this->tab_link( 'connection', 'Workflow Access', $tab );
					$this->tab_link( 'migration', 'Domain Tools', $tab );
					$this->tab_link( 'settings', 'Settings', $tab );
					$this->tab_link( 'activity', 'Activity', $tab );
					$this->tab_link( 'agency', 'Agency Access', $tab );
				}
				?>
			</nav>

			<div class="ikon-seo-panel">
				<?php if ( ! empty( $_GET['ikon-error'] ) ) : ?>
					<div class="notice notice-error inline"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['ikon-error'] ) ) ); ?></p></div>
				<?php elseif ( ! empty( $_GET['updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Settings saved successfully.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['pairing-started'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'A temporary pairing code was created. It will expire automatically.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['key-revoked'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The website was disconnected and the old key is no longer valid.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['strategy-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The Website Strategy was saved. Audits and drafts will now use the updated operating mode and quality policy.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['auto-discovery-run'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Auto Discovery reviewed the website and prepared strategy suggestions. No live content or public settings were changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['auto-discovery-applied'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The selected Auto Discovery suggestions were applied. Existing confirmed values were preserved unless overwrite was explicitly selected.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['auto-discovery-settings'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Auto Discovery settings were saved.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['discovery-review-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The fact-level discovery decision was saved. Confirmed business information will not be silently replaced by a later rescan.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['discovery-review-applied'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Confirmed and corrected discovery facts were applied to the Website Profile and Strategy.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['workflow-created'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The approval-first workflow was created.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['workflow-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The workflow task was updated.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['workflow-approved'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The workflow approval was recorded. No live content was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['workflow-run'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The safe read-only workflow runner completed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['workflow-settings-saved'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Workflow automation settings were saved.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['workflow-briefing'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'A workflow briefing was generated and added to Project History.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['profile-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The Website Profile was saved. Refresh the connected workflow before its next write.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['profile-imported'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The Website Profile was imported. Remote actions are paused and a new connection key is required.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['migration-applied'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The approved domain migration was applied. Remote actions are paused and the old key was revoked.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['merged'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The approved draft was merged into the original page and a rollback snapshot was saved.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['rolled-back'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The page was restored from its Ikon SEO snapshot.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['gsc-connected'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Google Search Console was connected with read-only access. Select the correct property below.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['gsc-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The Search Console configuration was updated.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['ga-connected'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Google Analytics was connected with read-only access.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['ga-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The Google Analytics configuration was updated.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['evidence-crawled'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The evidence crawl batch completed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['diagnostics-refreshed'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Page diagnostics were refreshed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['search-intelligence-refreshed'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Search Intelligence was refreshed and stored.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['opportunity-engine-rebuilt'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The evidence-based opportunity queue was rebuilt. No live website changes were made.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['opportunity-evidence-imported'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The approved keyword evidence file was imported.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['opportunity-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The opportunity review status was saved.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['content-workbench-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The controlled content workflow was updated. No public page was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['authority-imported'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Authority and off-site evidence was imported.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['publisher-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Publisher Intelligence was updated.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['content-intelligence-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Competitor and Content Intelligence was updated.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['technical-refreshed'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Technical URL discovery and the internal-link graph were refreshed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['technical-checked'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The technical URL check batch completed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['pagespeed-refreshed'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The PageSpeed evidence batch completed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['pagespeed-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The performance-data configuration was updated.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['queue-imported'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php echo esc_html( sprintf( '%d page plans were imported. %d rows were skipped.', absint( $_GET['inserted'] ?? 0 ), absint( $_GET['skipped'] ?? 0 ) ) ); ?></p></div>
				<?php elseif ( ! empty( $_GET['monitor-run'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The content refresh monitor completed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['visibility-brand-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Visibility and Brand Intelligence was updated. No outreach, publication or reputation response was sent.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['indexation-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Indexation Intelligence was updated. No indexing request or live-page change was made.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['governance-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Structured Data and Media Governance was updated. No markup, image or published page was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['ecr-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Experiment, claim or attribution evidence was updated. No live page, CRM record or customer communication was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['international-server-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'International and server evidence was updated. No public page, redirect or indexing setting was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['portfolio-quality-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Portfolio quality evidence and review gates were updated. No public page was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['guided-launch-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Guided Launch updated the workflow and Operating Plan using bounded read-only tasks. No live page or external profile was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['closed-loop-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The operating plan was updated. Live pages and public profiles were not changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['editorial-review-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Editorial review was updated. No content was published.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['search-impact-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Search impact evidence was updated. No public page or external system was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['pattern-library-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Pattern Library evidence or review status was updated. No pattern was applied to a public page.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['portfolio-governance-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Portfolio Governance was updated. Remote policies remain proposals until a local administrator accepts them, and no public content was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['agency-service-levels-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Agency service levels, capacity or report records were updated. No client message was sent and no public website was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['platform-health-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Platform health, release integrity or recovery records were updated. No live content was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['production-certification-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Production certification or controlled rollout evidence was updated. No plugin, page or public website was changed automatically.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['staging-validation-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Staging validation evidence was updated. Temporary self-test artefacts were removed, and no public content was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['agency-command-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The Agency Command Centre was updated. Remote websites remain read-only.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['agency-site-refreshed'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The managed website snapshot was refreshed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['agency-agent-key'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'A new read-only agency site key was generated. Copy it now; it will not be displayed again.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['local-growth-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The Local Growth System was updated. No public profile or live page was changed.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['local-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The Local SEO workspace was updated.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['gbp-connected'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Google Business Profile was connected. Select the correct account and match each location.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['gbp-draft-staged'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The Google Business Profile action was staged. It has not been sent.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['gbp-sent'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The administrator-approved Google Business Profile action was sent.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['gbp-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The Google Business Profile configuration was updated.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['history-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Project history was updated.', 'ikon-seo' ); ?></p></div>
				<?php elseif ( ! empty( $_GET['gbp-availability-updated'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'Your Google Business Profile preference was saved.', 'ikon-seo' ); ?></p></div>
				<?php endif; ?>
				<?php
				if ( 'auto-discovery' === $tab ) {
					$this->render_auto_discovery();
				} elseif ( 'discovery-review' === $tab ) {
					$this->render_discovery_review();
				} elseif ( 'guided-launch' === $tab ) {
					$this->render_guided_launch();
				} elseif ( 'strategy' === $tab ) {
					$this->render_strategy();
				} elseif ( 'workflow-automation' === $tab ) {
					$this->render_workflow_automation();
				} elseif ( 'publisher-intelligence' === $tab ) {
					$this->render_publisher_intelligence();
				} elseif ( 'profile' === $tab ) {
					$this->render_profile( $settings );
				} elseif ( 'connection' === $tab ) {
					$this->render_connection( $settings );
				} elseif ( 'reviews' === $tab ) {
					$this->render_reviews();
				} elseif ( 'history' === $tab ) {
					$this->render_history();
				} elseif ( 'inventory' === $tab ) {
					$this->render_inventory();
				} elseif ( 'seo-health' === $tab ) {
					$this->render_seo_health();
				} elseif ( 'diagnostics' === $tab ) {
					$this->render_diagnostics();
				} elseif ( 'search-intelligence' === $tab ) {
					$this->render_search_intelligence();
				} elseif ( 'opportunity-engine' === $tab ) {
					$this->render_opportunity_engine();
				} elseif ( 'content-workbench' === $tab ) {
					$this->render_content_workbench();
				} elseif ( 'editorial-review' === $tab ) {
					$this->render_editorial_review();
				} elseif ( 'publishing-readiness' === $tab ) {
					$this->render_publishing_readiness();
				} elseif ( 'search-impact' === $tab ) {
					$this->render_search_impact();
				} elseif ( 'pattern-library' === $tab ) {
					$this->render_pattern_library();
				} elseif ( 'agency-governance' === $tab ) {
					$this->render_portfolio_governance();
				} elseif ( 'agency-service-levels' === $tab ) {
					$this->render_agency_service_levels();
				} elseif ( 'content-intelligence' === $tab ) {
					$this->render_content_intelligence();
				} elseif ( 'authority-intelligence' === $tab ) {
					$this->render_authority_intelligence();
				} elseif ( 'visibility-brand' === $tab ) {
					$this->render_visibility_brand();
				} elseif ( 'closed-loop' === $tab ) {
					$this->render_closed_loop();
				} elseif ( 'platform-health' === $tab ) {
					$this->render_platform_health();
				} elseif ( 'deployment-control' === $tab ) {
					$this->render_deployment_control();
				} elseif ( 'production-certification' === $tab ) {
					$this->render_production_certification();
				} elseif ( 'staging-validation' === $tab ) {
					$this->render_staging_validation();
				} elseif ( 'indexation' === $tab ) {
					$this->render_indexation();
				} elseif ( 'governance' === $tab ) {
					$this->render_governance();
				} elseif ( 'experiments-claims-revenue' === $tab ) {
					$this->render_experiments_claims_revenue();
				} elseif ( 'international-server' === $tab ) {
					$this->render_international_server();
				} elseif ( 'portfolio-quality' === $tab ) {
					$this->render_portfolio_quality();
				} elseif ( 'technical-intelligence' === $tab ) {
					$this->render_technical_intelligence();
				} elseif ( 'analytics' === $tab ) {
					$this->render_analytics( $settings );
				} elseif ( 'image-audit' === $tab ) {
					$this->render_image_audit();
				} elseif ( 'redirects' === $tab ) {
					$this->render_redirects();
				} elseif ( 'local-growth' === $tab ) {
					$this->render_local_growth();
				} elseif ( 'local-seo' === $tab ) {
					$this->render_local_seo();
				} elseif ( 'business-profile' === $tab ) {
					$this->render_business_profile( $settings );
				} elseif ( 'search-console' === $tab ) {
					$this->render_search_console( $settings );
				} elseif ( 'queue' === $tab ) {
					$this->render_queue();
				} elseif ( 'monitor' === $tab ) {
					$this->render_monitor();
				} elseif ( 'agency-command-centre' === $tab ) {
					$this->render_agency_command_centre();
				} elseif ( 'migration' === $tab ) {
					$this->render_migration( $settings );
				} elseif ( 'settings' === $tab ) {
					$this->render_settings( $settings );
				} elseif ( 'activity' === $tab ) {
					$this->render_activity();
				} elseif ( 'agency' === $tab ) {
					$this->render_agency_access();
				} else {
					$this->render_dashboard( $settings );
				}
				?>
			</div>
		</div>
		<?php
	}

	private function render_dashboard( array $settings ) {
		$profile          = $this->profile->get();
		$strategy         = $this->strategy->get();
		$gsc              = $this->search_console->status();
		$ga               = $this->analytics->status();
		$crawl            = $this->crawler->status();
		$gbp              = $this->gbp->status();
		$local            = $this->local->summary();
		$queue            = $this->queue->counts();
		$automation       = $this->automation->summary( 20 );
		$inventory_status = $this->inventory->status();
		$discovery        = $this->auto_discovery->report();
		$connection_status = $this->connection->status( $settings );
		$gbp_availability  = sanitize_key( $settings['gbp_availability'] ?? 'unknown' );

		$checks = array(
			array(
				'label'  => 'Website Strategy',
				'ok'     => ! empty( $strategy['configured'] ) && absint( $strategy['readiness']['score'] ?? 0 ) >= 60,
				'detail' => ! empty( $strategy['configured'] )
					? sprintf( '%s mode · %s · %d/100 readiness.', $strategy['mode_label'], $strategy['primary_goal_label'], absint( $strategy['readiness']['score'] ?? 0 ) )
					: 'Define the operating mode, audience, conversions, quality standards and success metrics.',
			),
			array(
				'label'  => 'Website Profile',
				'ok'     => ! empty( $profile['configured'] ),
				'detail' => ! empty( $profile['configured'] ) ? $profile['industry_label'] . ' using ' . $profile['business_entity_type'] : 'Complete the Website Profile before planning pages.',
			),
			array(
				'label'  => 'Website scan',
				'ok'     => ! empty( $inventory_status['scanned'] ),
				'detail' => ! empty( $inventory_status['scanned'] )
					? sprintf( '%d items scanned. Last scan: %s UTC.', absint( $inventory_status['summary']['total'] ?? 0 ), $inventory_status['generated_at'] )
					: 'Scan the website to find missing metadata, orphan pages and keyword overlap.',
			),
			array(
				'label'  => 'Connected workflow (optional)',
				'ok'     => true,
				'detail' => 'connected' === $connection_status
					? 'Connected and verified. A compatible workflow can read the site and create drafts.'
					: ( 'ready' === $connection_status ? 'Workflow access is prepared, but the private workspace has not verified it yet. Local tools still work.' : 'Not connected. Website scans, Local SEO, profiles and Page Plans still work normally.' ),
			),
			array(
				'label'  => 'Page builder',
				'ok'     => 'elementor' !== $settings['builder_preference'] || defined( 'ELEMENTOR_VERSION' ),
				'detail' => 'elementor' === $profile['builder']['detected']
					? 'Detected Elementor ' . $profile['builder']['version']
					: 'Gutenberg/WordPress editor detected.',
			),
			array(
				'label'  => 'SEO integration',
				'ok'     => 'none' !== $profile['seo_plugin']['detected'] || 'none' === $settings['seo_plugin_preference'],
				'detail' => 'none' !== $profile['seo_plugin']['detected']
					? 'Detected ' . $profile['seo_plugin']['detected'] . ' ' . $profile['seo_plugin']['version']
					: 'No supported SEO plugin detected; safe fallback schema remains available.',
			),
			array(
				'label'  => 'Approval-first publishing',
				'ok'     => ! empty( $settings['draft_only'] ),
				'detail' => ! empty( $settings['draft_only'] ) ? 'Any connected workflow must save page changes as drafts.' : 'Direct publishing is enabled.',
			),
			array(
				'label'  => 'Search Console (optional)',
				'ok'     => true,
				'detail' => ! empty( $gsc['connected'] )
					? ( $gsc['property'] ? 'Read-only property: ' . $gsc['property'] : 'Connected; select the correct property.' )
					: 'Not connected. Add it later for performance and indexing insights.',
			),
			array(
				'label'  => 'Google Analytics (optional)',
				'ok'     => true,
				'detail' => ! empty( $ga['connected'] )
					? ( $ga['property'] ? 'Read-only property: ' . $ga['property'] : 'Connected; select the correct GA4 property.' )
					: 'Not connected. Add it later for landing-page engagement and key-event evidence.',
			),
			array(
				'label'  => 'Evidence foundation',
				'ok'     => ! empty( $crawl['crawled'] ),
				'detail' => ! empty( $crawl['crawled'] )
					? sprintf( '%d of %d published pages crawled; %d pending or stale.', absint( $crawl['crawled'] ), absint( $crawl['published'] ), absint( $crawl['pending'] ) )
					: 'Run the first evidence crawl to create page-level ranking diagnostics.',
			),
			array(
				'label'  => 'Workflow Automation',
				'ok'     => ! empty( $automation['workflows'] ),
				'detail' => ! empty( $automation['workflows'] )
					? sprintf( '%d workflows, %d ready tasks and %d awaiting approval.', count( $automation['workflows'] ), absint( $automation['counts']['ready'] ?? 0 ), absint( $automation['counts']['pending_approval'] ?? 0 ) )
					: 'Create a mode-based workflow to turn strategy into assigned, approval-first tasks.',
			),
			array(
				'label'  => 'Page Plans',
				'ok'     => true,
				'detail' => sprintf( '%d planned, %d claimed, %d completed and %d failed items.', $queue['planned'], $queue['claimed'], $queue['completed'], $queue['failed'] ),
			),
			array(
				'label'  => 'Local SEO workspace',
				'ok'     => true,
				'detail' => $local['locations']
					? sprintf( '%d local records, %d verified customer-facing locations; NAP status: %s.', $local['locations'], $local['verified_locations'], str_replace( '_', ' ', $local['nap_audit']['status'] ) )
					: 'Ready. Add only real locations or service areas when available.',
			),
			array(
				'label'  => 'Google Business Profile (optional)',
				'ok'     => true,
				'detail' => $gbp['connected']
					? sprintf( '%d locations linked; external mutations remain administrator approval-gated.', $gbp['linked_locations'] )
					: ( 'not_available' === $gbp_availability ? 'Skipped for now. This does not block any website SEO tools.' : 'Not connected. Choose “No, not yet” or connect it later.' ),
			),
		);
		?>
		<h2><?php echo esc_html( $profile['site_name'] . ' workflow' ); ?></h2>
		<p class="description">
			<?php
			echo esc_html(
				$profile['configured']
					? $profile['industry_label'] . ' profile · ' . $profile['default_language'] . ' · ' . $profile['business_entity_type'] . ' schema policy'
					: 'Complete the website-specific profile. No accounting, location or language assumptions are applied automatically.'
			);
			?>
		</p>

		<div class="notice notice-info inline">
			<p><strong><?php esc_html_e( 'Core SEO tools work without any external connection or Google Business Profile.', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'Start with the website scan. Optional workflow and Google integrations can be connected later.', 'ikon-seo' ); ?></p>
		</div>

		<?php
		if ( empty( $discovery['generated_at'] ) && ( empty( $profile['configured'] ) || empty( $strategy['configured'] ) ) ) {
			$next_label = __( 'Research and Configure Website', 'ikon-seo' );
			$next_text  = __( 'Automatically inspect the website, propose profile and strategy values, and show which business decisions still need confirmation.', 'ikon-seo' );
			$next_url   = admin_url( 'admin.php?page=ikon-seo&tab=auto-discovery' );
		} elseif ( ! empty( $discovery['generated_at'] ) && ( empty( $profile['configured'] ) || empty( $strategy['configured'] ) || absint( $strategy['readiness']['score'] ?? 0 ) < 60 ) ) {
			$next_label = __( 'Review Detected Strategy', 'ikon-seo' );
			$next_text  = __( 'Review the detected facts, resolve conflicts and apply selected suggestions.', 'ikon-seo' );
			$next_url   = admin_url( 'admin.php?page=ikon-seo&tab=auto-discovery' );
		} elseif ( empty( $profile['configured'] ) ) {
			$next_label = __( 'Complete Website Profile', 'ikon-seo' );
			$next_text  = __( 'Add the website identity, industry and publishing rules first.', 'ikon-seo' );
			$next_url   = admin_url( 'admin.php?page=ikon-seo&tab=profile' );
		} elseif ( empty( $strategy['configured'] ) || absint( $strategy['readiness']['score'] ?? 0 ) < 60 ) {
			$next_label = __( 'Complete Website Strategy', 'ikon-seo' );
			$next_text  = __( 'Set the operating mode, target audience, business goals, conversion actions and quality policy.', 'ikon-seo' );
			$next_url   = admin_url( 'admin.php?page=ikon-seo&tab=strategy' );
		} elseif ( empty( $inventory_status['scanned'] ) ) {
			$next_label = __( 'Scan Website', 'ikon-seo' );
			$next_text  = __( 'Scan existing pages before planning or creating anything new. No workflow connection is required.', 'ikon-seo' );
			$next_url   = admin_url( 'admin.php?page=ikon-seo&tab=inventory' );
		} elseif ( empty( $automation['workflows'] ) ) {
			$next_label = __( 'Create SEO Workflow', 'ikon-seo' );
			$next_text  = __( 'Turn the website strategy into assigned tasks, approvals, safe evidence refreshes and briefings.', 'ikon-seo' );
			$next_url   = admin_url( 'admin.php?page=ikon-seo&tab=workflow-automation' );
		} else {
			$next_label = __( 'Open Page Plans', 'ikon-seo' );
			$next_text  = __( 'Use the scan findings to prepare controlled service and location page plans.', 'ikon-seo' );
			$next_url   = admin_url( 'admin.php?page=ikon-seo&tab=queue' );
		}
		?>
		<div class="ikon-seo-next-step">
			<div><strong><?php esc_html_e( 'Next step', 'ikon-seo' ); ?></strong><p><?php echo esc_html( $next_text ); ?></p></div>
			<a class="button button-primary" href="<?php echo esc_url( $next_url ); ?>"><?php echo esc_html( $next_label ); ?></a>
		</div>

		<div class="ikon-seo-grid">
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Guide every decision', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'The Website Strategy tells audits and drafts whether to prioritize local leads, editorial growth, ecommerce revenue or a hybrid model.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Scan and audit', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Inventory pages, find orphans and keyword overlap, discover internal links and review metadata locally in WordPress.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Plan pages', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Create controlled page plans for services, areas and supporting content without publishing anything.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Optimize locally', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Manage real locations and service areas, check NAP, track citations and avoid doorway-page risks.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Create structured drafts — optional', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'A separately configured private workflow can build structured Elementor or Gutenberg drafts. The plugin itself does not generate content.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Improve safely — optional', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Connected workflows create separate review drafts. Live URLs remain unchanged until an administrator approves a merge.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Measure — optional', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Connect Search Console later to compare performance, inspect indexing information and review sitemap warnings.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Approve safely', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Review drafts, preserve the original page ID and URL, regenerate Elementor CSS and retain rollback snapshots.', 'ikon-seo' ); ?></p>
			</div>
		</div>

		<h2><?php esc_html_e( 'Setup status', 'ikon-seo' ); ?></h2>
		<div class="ikon-seo-checks">
			<?php foreach ( $checks as $check ) : ?>
				<div class="ikon-seo-check">
					<span class="ikon-seo-status <?php echo $check['ok'] ? 'is-ok' : 'is-warning'; ?>">
						<?php echo $check['ok'] ? '✓' : '!'; ?>
					</span>
					<div>
						<strong><?php echo esc_html( $check['label'] ); ?></strong>
						<p><?php echo esc_html( $check['detail'] ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private function render_connection( array $settings ) {
		$user_id     = get_current_user_id();
		$status      = $this->connection->status( $settings );
		$schema_url  = rest_url( Ikon_SEO_REST::NAMESPACE . '/openapi' );
		$new_token   = get_transient( 'ikon_seo_new_token_' . $user_id );
		$test_result = get_transient( 'ikon_seo_connection_test_' . $user_id );

		if ( $new_token ) {
			delete_transient( 'ikon_seo_new_token_' . $user_id );
		}
		if ( $test_result ) {
			delete_transient( 'ikon_seo_connection_test_' . $user_id );
		}

		$status_labels = array(
			'disconnected' => __( 'Not configured', 'ikon-seo' ),
			'ready'        => __( 'Setup pending', 'ikon-seo' ),
			'connected'    => __( 'Connected', 'ikon-seo' ),
		);
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Connected Draft Workflow — Optional', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'This optional connection lets a private operator workspace create or improve review drafts. It is not required for website scanning, Local SEO, profiles or Page Plans.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo 'connected' === $status ? 'is-connected' : ''; ?>"><?php echo esc_html( $status_labels[ $status ] ); ?></span>
		</div>

		<?php if ( is_array( $test_result ) ) : ?>
			<div class="notice <?php echo ! empty( $test_result['ok'] ) ? 'notice-success' : 'notice-error'; ?> inline"><p><?php echo esc_html( $test_result['message'] ?? '' ); ?></p></div>
		<?php endif; ?>

		<div class="ikon-seo-connect-hero">
			<?php if ( 'connected' === $status ) : ?>
				<div class="ikon-seo-connect-icon is-connected">✓</div>
				<div class="ikon-seo-connect-content">
					<h3><?php esc_html_e( 'Private workflow connected', 'ikon-seo' ); ?></h3>
					<p><?php esc_html_e( 'The workflow can read this website and create review drafts. Live pages still cannot be changed automatically.', 'ikon-seo' ); ?></p>
					<?php if ( ! empty( $settings['connection_last_seen_at'] ) ) : ?>
						<p class="description"><?php echo esc_html( sprintf( __( 'Last verified activity: %s UTC', 'ikon-seo' ), $settings['connection_last_seen_at'] ) ); ?></p>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="ikon-seo-connect-icon">✓</div>
				<div class="ikon-seo-connect-content">
					<h3><?php esc_html_e( 'You can continue without connecting a workflow', 'ikon-seo' ); ?></h3>
					<p><?php esc_html_e( 'Website scans, audits, Local SEO, Business Profile choices and Page Plans run locally in WordPress. A private workflow can be connected later for structured draft creation.', 'ikon-seo' ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<div class="ikon-seo-actions ikon-seo-primary-actions">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_refresh_inventory">
				<?php wp_nonce_field( 'ikon_seo_refresh_inventory' ); ?>
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Scan Website', 'ikon-seo' ); ?></button>
			</form>
			<a class="button button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=queue' ) ); ?>"><?php esc_html_e( 'Open Page Plans', 'ikon-seo' ); ?></a>
			<?php if ( 'connected' === $status ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_revoke_token">
					<?php wp_nonce_field( 'ikon_seo_revoke_token' ); ?>
					<button type="submit" class="button button-link-delete" data-confirm="<?php esc_attr_e( 'Disconnect this workflow? Its current key will stop working immediately.', 'ikon-seo' ); ?>"><?php esc_html_e( 'Disconnect workflow', 'ikon-seo' ); ?></button>
				</form>
			<?php endif; ?>
		</div>

		<div class="ikon-seo-simple-steps">
			<div><strong>1</strong><span><?php esc_html_e( 'Scan the website', 'ikon-seo' ); ?></span></div>
			<div><strong>2</strong><span><?php esc_html_e( 'Review Local SEO and Page Plans', 'ikon-seo' ); ?></span></div>
			<div><strong>3</strong><span><?php esc_html_e( 'Connect a private workflow later if needed', 'ikon-seo' ); ?></span></div>
		</div>

		<details class="ikon-seo-advanced">
			<summary><?php esc_html_e( 'Workflow access settings', 'ikon-seo' ); ?></summary>
			<div class="ikon-seo-advanced-content">
				<div class="notice notice-warning inline"><p><strong><?php esc_html_e( 'Restricted setup:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'These controls are for the private operator workspace. Use them only during a planned one-time setup.', 'ikon-seo' ); ?></p></div>

				<div class="ikon-seo-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ikon_seo_test_connection">
						<?php wp_nonce_field( 'ikon_seo_test_connection' ); ?>
						<button type="submit" class="button"><?php esc_html_e( 'Test website API', 'ikon-seo' ); ?></button>
					</form>
				</div>

				<?php if ( $new_token ) : ?>
					<div class="notice notice-warning inline ikon-seo-token-notice">
						<h3><?php esc_html_e( 'Copy the workflow key now', 'ikon-seo' ); ?></h3>
						<p><?php esc_html_e( 'This key is shown once. Store it in the compatible workflow, never in page content or screenshots.', 'ikon-seo' ); ?></p>
						<div class="ikon-seo-copy-row">
							<code id="ikon-seo-new-token"><?php echo esc_html( $new_token ); ?></code>
							<button type="button" class="button" data-copy-target="#ikon-seo-new-token"><?php esc_html_e( 'Copy key', 'ikon-seo' ); ?></button>
						</div>
					</div>
				<?php endif; ?>



				<div class="ikon-seo-connection-box">
					<label><?php esc_html_e( 'OpenAPI schema URL', 'ikon-seo' ); ?></label>
					<div class="ikon-seo-copy-row"><code id="ikon-seo-schema-url"><?php echo esc_html( $schema_url ); ?></code><button type="button" class="button" data-copy-target="#ikon-seo-schema-url"><?php esc_html_e( 'Copy URL', 'ikon-seo' ); ?></button></div>
				</div>


				<div class="ikon-seo-connection-box">
					<label><?php esc_html_e( 'Workflow authentication', 'ikon-seo' ); ?></label>
					<p><?php echo esc_html( ! empty( $settings['token_hash'] ) ? sprintf( __( 'Key configured (%s). Scopes: %s', 'ikon-seo' ), $settings['token_hint'], implode( ', ', (array) $settings['key_scopes'] ) ) : __( 'No key configured.', 'ikon-seo' ) ); ?></p>
					<p><code>X-Ikon-SEO-Key: YOUR_KEY</code></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ikon_seo_generate_token">
						<?php wp_nonce_field( 'ikon_seo_generate_token' ); ?>
						<button type="submit" class="button"><?php esc_html_e( 'Generate workflow key', 'ikon-seo' ); ?></button>
					</form>
				</div>
			</div>
		</details>
		<?php
	}

	private function render_reviews() {
		$reviews = $this->workflow->reviews( 100 );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Improvement review queue', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Merging preserves the original page ID and URL, creates a rollback snapshot, copies approved Elementor and Rank Math data, and regenerates Elementor CSS.', 'ikon-seo' ); ?></p>
			</div>
		</div>

		<table class="widefat striped ikon-seo-log">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Improvement draft', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Live source', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Quality', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Key changes', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'ikon-seo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $reviews ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No improvement drafts are waiting for review.', 'ikon-seo' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $reviews as $review ) : ?>
					<?php
					$comparison = $this->workflow->comparison( $review['draft_id'] );
					$changes    = is_wp_error( $comparison ) ? array() : $comparison['changes'];
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( $review['draft_edit_url'] ); ?>"><?php echo esc_html( $review['draft_title'] ); ?></a></strong>
							<br><code>#<?php echo absint( $review['draft_id'] ); ?></code>
							<?php if ( $review['merged'] ) : ?>
								<span class="ikon-seo-pill is-connected"><?php esc_html_e( 'Merged', 'ikon-seo' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $review['source_url'] ) : ?>
								<a href="<?php echo esc_url( $review['source_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $review['source_title'] ); ?></a>
								<br><code>#<?php echo absint( $review['source_id'] ); ?></code>
							<?php else : ?>
								<span class="ikon-seo-pill is-failed"><?php esc_html_e( 'Missing source', 'ikon-seo' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<strong><?php echo absint( $review['quality_score'] ); ?>/100</strong>
							<br><?php echo esc_html( ucwords( str_replace( '_', ' ', $review['quality_status'] ) ) ); ?>
						</td>
						<td>
							<?php if ( $changes ) : ?>
								<?php echo esc_html( sprintf( '%+d words, %+d internal links', $changes['word_count_change'], $changes['internal_link_change'] ) ); ?>
								<?php if ( $changes['schema_added'] ) : ?>
									<br><?php echo esc_html( 'Schema: +' . implode( ', ', $changes['schema_added'] ) ); ?>
								<?php endif; ?>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
						<td>
							<div class="ikon-seo-actions is-stacked">
								<?php $review_builder = get_post_meta( $review['draft_id'], '_ikon_seo_builder', true ); ?>
								<a class="button" href="<?php echo esc_url( 'gutenberg' === $review_builder ? get_edit_post_link( $review['draft_id'], 'raw' ) : admin_url( 'post.php?post=' . absint( $review['draft_id'] ) . '&action=elementor' ) ); ?>"><?php echo esc_html( 'gutenberg' === $review_builder ? 'Open in editor' : 'Open in Elementor' ); ?></a>
								<?php if ( ! $review['merged'] && $review['source_url'] && 'needs_changes' !== $review['quality_status'] && 'not_checked' !== $review['quality_status'] ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="ikon_seo_merge_review">
										<input type="hidden" name="draft_id" value="<?php echo absint( $review['draft_id'] ); ?>">
										<?php wp_nonce_field( 'ikon_seo_merge_review_' . absint( $review['draft_id'] ) ); ?>
										<button type="submit" class="button button-primary" data-confirm="<?php esc_attr_e( 'Merge this reviewed draft into the live source page? A rollback snapshot will be created first.', 'ikon-seo' ); ?>"><?php esc_html_e( 'Approve and merge', 'ikon-seo' ); ?></button>
									</form>
								<?php elseif ( ! $review['merged'] && in_array( $review['quality_status'], array( 'needs_changes', 'not_checked' ), true ) ) : ?>
									<span class="description"><?php esc_html_e( 'Resolve quality failures before merge.', 'ikon-seo' ); ?></span>
								<?php endif; ?>
								<?php $snapshots = $review['source_id'] ? $this->workflow->snapshots( $review['source_id'] ) : array(); ?>
								<?php if ( $snapshots ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="ikon_seo_rollback_page">
										<input type="hidden" name="source_id" value="<?php echo absint( $review['source_id'] ); ?>">
										<input type="hidden" name="snapshot_id" value="<?php echo esc_attr( $snapshots[0]['id'] ); ?>">
										<?php wp_nonce_field( 'ikon_seo_rollback_page_' . absint( $review['source_id'] ) ); ?>
										<button type="submit" class="button" data-confirm="<?php esc_attr_e( 'Restore the newest Ikon SEO snapshot for this page?', 'ikon-seo' ); ?>"><?php esc_html_e( 'Rollback latest', 'ikon-seo' ); ?></button>
									</form>
								<?php endif; ?>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_inventory() {
		$inventory = $this->inventory->scan();
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Website SEO inventory', 'ikon-seo' ); ?></h2>
				<p class="description"><?php echo esc_html( 'Generated ' . $inventory['generated_at'] . ' UTC. Results are cached for 10 minutes.' ); ?></p>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_refresh_inventory">
				<?php wp_nonce_field( 'ikon_seo_refresh_inventory' ); ?>
				<button type="submit" class="button"><?php esc_html_e( 'Refresh inventory', 'ikon-seo' ); ?></button>
			</form>
		</div>

		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'This scan runs locally in WordPress.', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'No workflow connection or Google Business Profile is required.', 'ikon-seo' ); ?></p></div>
		<div class="ikon-seo-actions"><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=queue' ) ); ?>"><?php esc_html_e( 'Open Page Plans', 'ikon-seo' ); ?></a><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) ); ?>"><?php esc_html_e( 'Open Local SEO', 'ikon-seo' ); ?></a></div>

		<div class="ikon-seo-metrics">
			<?php foreach ( array(
				'total'                    => 'Pages and posts',
				'published'                => 'Published',
				'orphan_pages'             => 'Potential orphans',
				'cannibalization_clusters' => 'Keyword overlaps',
				'missing_seo_titles'       => 'Missing SEO titles',
				'missing_descriptions'     => 'Missing descriptions',
			) as $key => $label ) : ?>
				<div class="ikon-seo-metric"><strong><?php echo absint( $inventory['summary'][ $key ] ); ?></strong><span><?php echo esc_html( $label ); ?></span></div>
			<?php endforeach; ?>
		</div>

		<?php if ( $inventory['cannibalization'] ) : ?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Potential focus-keyword overlap:', 'ikon-seo' ); ?></strong>
				<?php echo esc_html( implode( ', ', array_slice( array_keys( $inventory['cannibalization'] ), 0, 10 ) ) ); ?></p>
			</div>
		<?php endif; ?>

		<table class="widefat striped ikon-seo-log">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Focus keyword', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Words', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Links in / out', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Issues', 'ikon-seo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( array_slice( $inventory['items'], 0, 200 ) as $item ) : ?>
					<tr>
						<td><strong><a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></strong><br><code><?php echo esc_html( $item['slug'] ); ?></code></td>
						<td><?php echo esc_html( ucfirst( $item['status'] ) ); ?></td>
						<td><?php echo esc_html( $item['focus_keyword'] ?: '—' ); ?></td>
						<td><?php echo absint( $item['word_count'] ); ?></td>
						<td><?php echo absint( $item['incoming_internal_links'] ) . ' / ' . absint( $item['outgoing_internal_links'] ); ?></td>
						<td>
							<?php
							$issues = array();
							if ( $item['orphan'] ) {
								$issues[] = 'Potential orphan';
							}
							if ( ! $item['seo_title'] ) {
								$issues[] = 'Missing SEO title';
							}
							if ( ! $item['seo_description'] ) {
								$issues[] = 'Missing description';
							}
							echo esc_html( $issues ? implode( ', ', $issues ) : '—' );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}


	private function render_seo_health() {
		$audit = $this->rank_math->audit();
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'SEO health and Rank Math compatibility', 'ikon-seo' ); ?></h2>
				<p class="description"><?php echo esc_html( 'Generated ' . $audit['generated_at'] . ' UTC. This is a read-only diagnostic.' ); ?></p>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_refresh_rank_math">
				<?php wp_nonce_field( 'ikon_seo_refresh_rank_math' ); ?>
				<button type="submit" class="button"><?php esc_html_e( 'Refresh SEO health', 'ikon-seo' ); ?></button>
			</form>
		</div>

		<?php if ( empty( $audit['active'] ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Rank Math is not active. Ikon SEO will continue using its fallback safeguards, but Rank Math-specific checks are unavailable.', 'ikon-seo' ); ?></p></div>
		<?php else : ?>
			<div class="notice notice-success inline"><p><?php echo esc_html( sprintf( 'Rank Math %s detected. Ikon SEO is checking compatibility rather than duplicating its output.', $audit['version'] ?: 'active' ) ); ?></p></div>
		<?php endif; ?>

		<div class="ikon-seo-metrics">
			<?php foreach ( array(
				'pages_checked'          => 'Pages checked',
				'issues'                 => 'Issues',
				'high_priority'          => 'High priority',
				'duplicate_titles'       => 'Duplicate titles',
				'duplicate_descriptions' => 'Duplicate descriptions',
				'schema_conflicts'       => 'Schema conflicts',
			) as $key => $label ) : ?>
				<div class="ikon-seo-metric"><strong><?php echo absint( $audit['summary'][ $key ] ?? 0 ); ?></strong><span><?php echo esc_html( $label ); ?></span></div>
			<?php endforeach; ?>
		</div>

		<h3><?php esc_html_e( 'Detected modules', 'ikon-seo' ); ?></h3>
		<p>
			<?php if ( empty( $audit['modules'] ) ) : ?>
				<span class="description"><?php esc_html_e( 'No enabled Rank Math modules were detected.', 'ikon-seo' ); ?></span>
			<?php else : ?>
				<?php foreach ( $audit['modules'] as $module ) : ?><span class="ikon-seo-pill"><?php echo esc_html( ucwords( str_replace( array( '-', '_' ), ' ', $module ) ) ); ?></span> <?php endforeach; ?>
			<?php endif; ?>
		</p>

		<h3><?php esc_html_e( 'Recommended actions', 'ikon-seo' ); ?></h3>
		<ul class="ul-disc">
			<?php foreach ( (array) $audit['recommendations'] as $recommendation ) : ?><li><?php echo esc_html( $recommendation ); ?></li><?php endforeach; ?>
		</ul>

		<h3><?php esc_html_e( 'Page-level issues', 'ikon-seo' ); ?></h3>
		<table class="widefat striped ikon-seo-log">
			<thead><tr><th><?php esc_html_e( 'Priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Issue', 'ikon-seo' ); ?></th></tr></thead>
			<tbody>
			<?php if ( empty( $audit['issues'] ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'No page-level compatibility issues were detected.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
			<?php foreach ( array_slice( (array) $audit['issues'], 0, 200 ) as $issue ) : ?>
				<tr>
					<td><span class="ikon-seo-pill <?php echo 'high' === $issue['priority'] ? 'is-failed' : ''; ?>"><?php echo esc_html( ucfirst( $issue['priority'] ) ); ?></span></td>
					<td><a href="<?php echo esc_url( get_edit_post_link( $issue['id'] ) ); ?>"><?php echo esc_html( $issue['title'] ?: '#' . $issue['id'] ); ?></a></td>
					<td><?php echo esc_html( $issue['message'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( ! empty( $audit['schema_conflicts'] ) ) : ?>
			<h3><?php esc_html_e( 'Schema ownership conflicts', 'ikon-seo' ); ?></h3>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'The pages below contain matching schema types from Rank Math and Ikon SEO. Review which system should own each type before publishing.', 'ikon-seo' ); ?></p></div>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Duplicate types', 'ikon-seo' ); ?></th></tr></thead><tbody>
			<?php foreach ( $audit['schema_conflicts'] as $conflict ) : ?><tr><td><a href="<?php echo esc_url( get_edit_post_link( $conflict['id'] ) ); ?>"><?php echo esc_html( $conflict['title'] ); ?></a></td><td><?php echo esc_html( implode( ', ', $conflict['duplicates'] ) ); ?></td></tr><?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>
		<?php
	}

	private function render_image_audit() {
		$audit = $this->image_audit->scan();
		$labels = array(
			'missing_alt'         => 'Missing ALT text',
			'filename_alt'        => 'ALT matches filename',
			'duplicate_alt'       => 'Duplicate ALT text',
			'missing_caption'     => 'Missing caption',
			'missing_description' => 'Missing description',
		);
		?>
		<div class="ikon-seo-section-header">
			<div><h2><?php esc_html_e( 'Image SEO audit', 'ikon-seo' ); ?></h2><p class="description"><?php echo esc_html( 'Generated ' . $audit['generated_at'] . ' UTC. No image metadata is changed automatically.' ); ?></p></div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_refresh_image_audit"><?php wp_nonce_field( 'ikon_seo_refresh_image_audit' ); ?><button type="submit" class="button"><?php esc_html_e( 'Refresh image audit', 'ikon-seo' ); ?></button></form>
		</div>
		<div class="ikon-seo-metrics">
			<?php foreach ( array( 'total' => 'Images checked', 'with_issues' => 'Images with issues', 'missing_alt' => 'Missing ALT', 'filename_alt' => 'Filename ALT', 'duplicate_alt_groups' => 'Duplicate ALT groups', 'missing_caption' => 'Missing captions' ) as $key => $label ) : ?><div class="ikon-seo-metric"><strong><?php echo absint( $audit['summary'][ $key ] ?? 0 ); ?></strong><span><?php echo esc_html( $label ); ?></span></div><?php endforeach; ?>
		</div>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'ALT text should describe the image in its page context. Do not stuff locations or keywords into every image.', 'ikon-seo' ); ?></p></div>
		<table class="widefat striped ikon-seo-log">
			<thead><tr><th><?php esc_html_e( 'Image', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Current ALT', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Attached page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Issues', 'ikon-seo' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( array_slice( (array) $audit['items'], 0, 250 ) as $item ) : ?>
				<?php if ( empty( $item['issues'] ) ) { continue; } ?>
				<tr>
					<td><a href="<?php echo esc_url( $item['edit_url'] ); ?>"><?php echo esc_html( $item['title'] ?: $item['filename'] ); ?></a><br><code><?php echo esc_html( $item['filename'] ); ?></code></td>
					<td><?php echo esc_html( $item['alt'] ?: '—' ); ?></td>
					<td><?php echo $item['attached_to'] ? '<a href="' . esc_url( get_edit_post_link( $item['attached_to'] ) ) . '">#' . absint( $item['attached_to'] ) . '</a>' : '—'; ?></td>
					<td><?php echo esc_html( implode( ', ', array_map( function( $issue ) use ( $labels ) { return $labels[ $issue ] ?? $issue; }, $item['issues'] ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_redirects() {
		$audit = $this->redirect_audit->scan();
		?>
		<div class="ikon-seo-section-header">
			<div><h2><?php esc_html_e( 'Redirect opportunities', 'ikon-seo' ); ?></h2><p class="description"><?php echo esc_html( 'Generated ' . $audit['generated_at'] . ' UTC. Recommendations are never applied automatically.' ); ?></p></div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_refresh_redirects"><?php wp_nonce_field( 'ikon_seo_refresh_redirects' ); ?><button type="submit" class="button"><?php esc_html_e( 'Refresh opportunities', 'ikon-seo' ); ?></button></form>
		</div>
		<div class="ikon-seo-metrics">
			<?php foreach ( array( 'opportunities' => 'Opportunities', 'internal_broken' => 'Broken internal URLs', 'logged_404s' => 'Logged 404 URLs', 'with_suggestion' => 'Suggested destinations' ) as $key => $label ) : ?><div class="ikon-seo-metric"><strong><?php echo absint( $audit['summary'][ $key ] ?? 0 ); ?></strong><span><?php echo esc_html( $label ); ?></span></div><?php endforeach; ?>
		</div>
		<?php if ( empty( $audit['rank_math_404_available'] ) ) : ?><div class="notice notice-info inline"><p><?php esc_html_e( 'The Rank Math 404 log table was not detected. Internal broken-link opportunities are still included.', 'ikon-seo' ); ?></p></div><?php endif; ?>
		<table class="widefat striped ikon-seo-log">
			<thead><tr><th><?php esc_html_e( 'Broken URL', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Hits', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Source', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Suggested destination', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Confidence', 'ikon-seo' ); ?></th></tr></thead>
			<tbody>
			<?php if ( empty( $audit['items'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No redirect opportunities were found in the current scan.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
			<?php foreach ( (array) $audit['items'] as $item ) : ?>
				<tr>
					<td><code><?php echo esc_html( wp_parse_url( $item['broken_url'], PHP_URL_PATH ) ?: $item['broken_url'] ); ?></code></td>
					<td><?php echo absint( $item['hits'] ); ?></td>
					<td><?php echo esc_html( implode( ', ', array_map( function( $source ) { return 'rank_math_404' === $source ? '404 log' : 'Internal link'; }, $item['sources'] ) ) ); ?></td>
					<td><?php echo $item['suggested_url'] ? '<a href="' . esc_url( $item['suggested_url'] ) . '" target="_blank" rel="noopener">' . esc_html( $item['suggested_title'] ?: $item['suggested_url'] ) . '</a>' : 'Manual review'; ?></td>
					<td><?php echo $item['suggested_url'] ? absint( $item['confidence'] ) . '%' : '—'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_history() {
		$state = $this->history->state( 100 );
		$items = (array) ( $state['recent_history'] ?? array() );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Project history', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Persistent project context stored in WordPress. A private workspace can resume from this history even when a new conversation, browser, device, or account is used.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill is-connected"><?php echo absint( $state['counts']['total'] ?? 0 ); ?> <?php esc_html_e( 'items', 'ikon-seo' ); ?></span>
		</div>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo absint( $state['counts']['open'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Open items', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $state['counts']['completed'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Completed', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $state['counts']['dismissed'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Dismissed', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( $state['last_completed_step']['title'] ?? '—' ); ?></strong><span><?php esc_html_e( 'Last completed step', 'ikon-seo' ); ?></span></div>
		</div>

		<div class="ikon-seo-two-columns">
			<div>
				<h3><?php esc_html_e( 'Add project note', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_add_history_note">
					<?php wp_nonce_field( 'ikon_seo_add_history_note' ); ?>
					<table class="form-table" role="presentation">
						<tr><th><label for="history_category"><?php esc_html_e( 'Type', 'ikon-seo' ); ?></label></th><td><select id="history_category" name="category"><?php foreach ( array( 'note' => 'Note', 'audit' => 'Audit', 'research' => 'Research', 'recommendation' => 'Recommendation', 'page_plan' => 'Page plan', 'approval' => 'Approval', 'change' => 'Change' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
						<tr><th><label for="history_title"><?php esc_html_e( 'Title', 'ikon-seo' ); ?></label></th><td><input required class="regular-text" id="history_title" name="title" maxlength="255"></td></tr>
						<tr><th><label for="history_summary"><?php esc_html_e( 'Summary', 'ikon-seo' ); ?></label></th><td><textarea required class="large-text" rows="5" id="history_summary" name="summary"></textarea></td></tr>
						<tr><th><label for="history_status"><?php esc_html_e( 'Status', 'ikon-seo' ); ?></label></th><td><select id="history_status" name="status"><option value="open"><?php esc_html_e( 'Open', 'ikon-seo' ); ?></option><option value="completed"><?php esc_html_e( 'Completed', 'ikon-seo' ); ?></option></select></td></tr>
					</table>
					<?php submit_button( __( 'Save project note', 'ikon-seo' ) ); ?>
				</form>
			</div>
			<div>
				<h3><?php esc_html_e( 'Conversation continuity', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'At the start of a new private-workspace conversation, the workspace should read the Website Profile, Site Inventory, Project History and Page Plans before making recommendations.', 'ikon-seo' ); ?></p>
				<?php if ( Ikon_SEO_Agency::can_manage() ) : ?>
					<div class="notice notice-info inline"><p><?php esc_html_e( 'Changing accounts does not remove this history because it is stored on the website. Generate a fresh workflow key for the new account, reconnect the same schema URL, confirm access, and revoke the old key.', 'ikon-seo' ); ?></p></div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ikon_seo_export_workspace_setup">
						<?php wp_nonce_field( 'ikon_seo_export_workspace_setup' ); ?>
						<button type="submit" class="button"><?php esc_html_e( 'Download workspace transfer guide', 'ikon-seo' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
		</div>

		<h3><?php esc_html_e( 'Saved history', 'ikon-seo' ); ?></h3>
		<table class="widefat striped ikon-seo-log">
			<thead><tr><th><?php esc_html_e( 'Updated', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Type', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Item', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $items ) : ?><tr><td colspan="5"><?php esc_html_e( 'No project history has been saved yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
			<?php foreach ( $items as $item ) : ?>
				<tr>
					<td><?php echo esc_html( $item['updated_at'] ); ?> UTC</td>
					<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['category'] ) ) ); ?></td>
					<td><strong><?php echo esc_html( $item['title'] ); ?></strong><br><span class="description"><?php echo esc_html( $item['summary'] ); ?></span><?php if ( ! empty( $item['related_post_id'] ) ) : ?><br><a href="<?php echo esc_url( get_edit_post_link( $item['related_post_id'] ) ); ?>"><?php echo esc_html( sprintf( 'Open page #%d', $item['related_post_id'] ) ); ?></a><?php endif; ?></td>
					<td><span class="ikon-seo-pill <?php echo 'completed' === $item['status'] ? 'is-connected' : ( 'dismissed' === $item['status'] ? 'is-failed' : '' ); ?>"><?php echo esc_html( ucfirst( $item['status'] ) ); ?></span></td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="ikon_seo_update_history_status"><input type="hidden" name="history_id" value="<?php echo absint( $item['id'] ); ?>">
							<?php wp_nonce_field( 'ikon_seo_update_history_status_' . absint( $item['id'] ) ); ?>
							<select name="status"><option value="open" <?php selected( $item['status'], 'open' ); ?>><?php esc_html_e( 'Open', 'ikon-seo' ); ?></option><option value="completed" <?php selected( $item['status'], 'completed' ); ?>><?php esc_html_e( 'Completed', 'ikon-seo' ); ?></option><option value="dismissed" <?php selected( $item['status'], 'dismissed' ); ?>><?php esc_html_e( 'Dismissed', 'ikon-seo' ); ?></option></select>
							<button type="submit" class="button button-small"><?php esc_html_e( 'Update', 'ikon-seo' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_agency_access() {
		if ( ! Ikon_SEO_Agency::can_manage() ) {
			return;
		}
		$agency_ids = Ikon_SEO_Agency::user_ids();
		$admins = get_users( array( 'role__in' => array( 'administrator' ), 'orderby' => 'display_name' ) );
		?>
		<h2><?php esc_html_e( 'Agency access', 'ikon-seo' ); ?></h2>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'Only selected agency administrators can see Website Profile imports, workflow credentials, Search Console credentials, domain tools, settings and activity logs. Other administrators receive the client-safe interface.', 'ikon-seo' ); ?></p></div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_save_agency_access">
			<?php wp_nonce_field( 'ikon_seo_save_agency_access' ); ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Agency access', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Administrator', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Email', 'ikon-seo' ); ?></th></tr></thead><tbody>
			<?php foreach ( $admins as $admin ) : ?><tr><td><input type="checkbox" name="agency_user_ids[]" value="<?php echo absint( $admin->ID ); ?>" <?php checked( in_array( (int) $admin->ID, $agency_ids, true ) ); ?>></td><td><?php echo esc_html( $admin->display_name ); ?> <code>#<?php echo absint( $admin->ID ); ?></code></td><td><?php echo esc_html( $admin->user_email ); ?></td></tr><?php endforeach; ?>
			</tbody></table>
			<?php submit_button( __( 'Save agency access', 'ikon-seo' ) ); ?>
		</form>
		<?php
	}


	private function render_local_growth() {
		$settings = Ikon_SEO_Plugin::settings();
		$days     = max( 7, min( 90, absint( $settings['local_conversion_days'] ?? 30 ) ) );
		$report   = $this->local_growth->report( false, $days );
		$readiness = (array) ( $report['readiness'] ?? array() );
		$profile   = (array) ( $report['profile_alignment'] ?? array() );
		$areas     = (array) ( $report['service_area_validation'] ?? array() );
		$landing   = (array) ( $report['landing_architecture'] ?? array() );
		$citations = (array) ( $report['citation_health'] ?? array() );
		$reviews   = (array) ( $report['review_workflow'] ?? array() );
		$conversions = (array) ( $report['conversions'] ?? array() );
		$prominence = (array) ( $report['competitor_prominence'] ?? array() );
		$agency = Ikon_SEO_Agency::can_manage();
		$users  = get_users( array( 'orderby' => 'display_name', 'fields' => array( 'ID', 'display_name' ) ) );
		?>
		<div class="ikon-seo-section-header">
			<div><h2><?php esc_html_e( 'Local Growth System', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Combine business-profile alignment, review response workflows, citation consistency, service-area policy, local landing-page coverage, conversions and competitor prominence.', 'ikon-seo' ); ?></p></div>
			<span class="ikon-seo-pill <?php echo 'strong' === ( $readiness['status'] ?? '' ) ? 'is-connected' : ''; ?>"><?php echo esc_html( absint( $readiness['score'] ?? 0 ) . '/100 readiness' ); ?></span>
		</div>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'Readiness measures evidence and workflow completeness. It is not a local ranking score. Distance from a searcher cannot be controlled, and public profile changes always require explicit administrator approval.', 'ikon-seo' ); ?></p></div>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo absint( $profile['critical_mismatches'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Profile mismatches', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $areas['unique_service_areas'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Verified service areas', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $citations['consistency_percent'] ?? 0 ); ?>%</strong><span><?php esc_html_e( 'Citation consistency', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $reviews['counts']['overdue'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Overdue review tasks', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $landing['uncovered_offerings'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Offering gaps', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $prominence['evidence_count'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Competitor observations', 'ikon-seo' ); ?></span></div>
		</div>

		<?php if ( $agency ) : ?>
		<div class="ikon-seo-two-columns">
			<div>
				<h3><?php esc_html_e( 'Refresh evidence', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_refresh_local_growth"><?php wp_nonce_field( 'ikon_seo_refresh_local_growth' ); ?>
					<p><label><?php esc_html_e( 'Reporting period', 'ikon-seo' ); ?> <input type="number" name="days" min="7" max="90" value="<?php echo absint( $days ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></label></p>
					<p><label><input type="checkbox" name="remote_refresh" value="1"> <?php esc_html_e( 'Request fresh connected review and conversion evidence', 'ikon-seo' ); ?></label></p>
					<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Refresh Local Growth', 'ikon-seo' ); ?></button></p>
				</form>
			</div>
			<div>
				<h3><?php esc_html_e( 'Workflow settings', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_local_growth_settings"><?php wp_nonce_field( 'ikon_seo_save_local_growth_settings' ); ?>
					<p><label><input type="checkbox" name="local_growth_enabled" value="1" <?php checked( ! empty( $settings['local_growth_enabled'] ) ); ?>> <?php esc_html_e( 'Enable weekly Local Growth refresh', 'ikon-seo' ); ?></label></p>
					<p><label><?php esc_html_e( 'Review response target', 'ikon-seo' ); ?> <input type="number" min="1" max="30" name="local_review_response_days" value="<?php echo absint( $settings['local_review_response_days'] ?? 3 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></label></p>
					<p><label><?php esc_html_e( 'Citation consistency target', 'ikon-seo' ); ?> <input type="number" min="50" max="100" name="local_citation_target_percent" value="<?php echo absint( $settings['local_citation_target_percent'] ?? 90 ); ?>">%</label></p>
					<p><label><?php esc_html_e( 'Competitor evidence stale after', 'ikon-seo' ); ?> <input type="number" min="14" max="365" name="local_prominence_stale_days" value="<?php echo absint( $settings['local_prominence_stale_days'] ?? 90 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></label></p>
					<p><button class="button" type="submit"><?php esc_html_e( 'Save Local Growth settings', 'ikon-seo' ); ?></button></p>
				</form>
			</div>
		</div>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Priority actions', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Area', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recommended action', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Approval', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['recommendations'] ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'No immediate Local Growth actions were identified.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) ( $report['recommendations'] ?? array() ) as $item ) : ?><tr><td><strong><?php echo esc_html( ucfirst( $item['priority'] ) ); ?></strong></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['category'] ) ) ); ?></td><td><?php echo esc_html( $item['action'] ); ?></td><td><?php echo ! empty( $item['approval_required'] ) ? esc_html__( 'Required', 'ikon-seo' ) : esc_html__( 'Planning only', 'ikon-seo' ); ?></td></tr><?php endforeach; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Business information and service areas', 'ikon-seo' ); ?></h3>
		<div class="ikon-seo-two-columns">
			<div><h4><?php esc_html_e( 'Profile alignment', 'ikon-seo' ); ?></h4><p><?php echo esc_html( sprintf( '%d linked of %d local records; %d confirmed mismatches and %d warnings.', absint( $profile['linked_locations'] ?? 0 ), absint( $profile['locations'] ?? 0 ), absint( $profile['critical_mismatches'] ?? 0 ), absint( $profile['warnings'] ?? 0 ) ) ); ?></p><?php foreach ( array_slice( (array) ( $profile['items'] ?? array() ), 0, 20 ) as $row ) : ?><p><strong><?php echo esc_html( $row['label'] ); ?></strong> — <?php echo esc_html( ucwords( str_replace( '_', ' ', $row['status'] ) ) ); ?><?php if ( ! empty( $row['failures'] ) || ! empty( $row['warnings'] ) ) : ?> · <?php echo esc_html( absint( $row['failures'] ) . ' failures, ' . absint( $row['warnings'] ) . ' warnings' ); ?><?php endif; ?></p><?php endforeach; ?></div>
			<div><h4><?php esc_html_e( 'Service-area validation', 'ikon-seo' ); ?></h4><p><?php echo esc_html( $areas['policy_note'] ?? '' ); ?></p><?php if ( empty( $areas['issues'] ) ) : ?><p><?php esc_html_e( 'No service-area policy conflicts were detected.', 'ikon-seo' ); ?></p><?php else : ?><ul><?php foreach ( array_slice( (array) $areas['issues'], 0, 30 ) as $issue ) : ?><li><strong><?php echo esc_html( ucfirst( $issue['severity'] ) ); ?>:</strong> <?php echo esc_html( $issue['issue'] ); ?> <?php echo esc_html( $issue['action'] ); ?></li><?php endforeach; ?></ul><?php endif; ?></div>
		</div>

		<h3><?php esc_html_e( 'Local landing-page architecture', 'ikon-seo' ); ?></h3>
		<p class="description"><?php echo esc_html( $landing['architecture_rule'] ?? '' ); ?></p>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Offering', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Existing coverage', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Guidance', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $landing['service_coverage'] ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'Add the real services or products to Website Strategy to build coverage evidence.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( array_slice( (array) ( $landing['service_coverage'] ?? array() ), 0, 100 ) as $row ) : ?><tr><td><strong><?php echo esc_html( $row['offering'] ); ?></strong></td><td><?php if ( ! empty( $row['covered'] ) && ! empty( $row['matched_page'] ) ) : ?><a href="<?php echo esc_url( get_edit_post_link( absint( $row['matched_page']['id'] ) ) ); ?>"><?php echo esc_html( $row['matched_page']['title'] ); ?></a><?php else : ?><?php esc_html_e( 'No strong page match', 'ikon-seo' ); ?><?php endif; ?></td><td><?php echo esc_html( $row['recommendation'] ); ?></td></tr><?php endforeach; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Citation and conversion evidence', 'ikon-seo' ); ?></h3>
		<div class="ikon-seo-two-columns">
			<div><h4><?php esc_html_e( 'Citation health', 'ikon-seo' ); ?></h4><p><?php echo esc_html( sprintf( '%d records; %d%% consistent against a %d%% target. %d corrections, %d duplicates, %d stale.', absint( $citations['total'] ?? 0 ), absint( $citations['consistency_percent'] ?? 0 ), absint( $citations['target_percent'] ?? 0 ), absint( $citations['corrections'] ?? 0 ), absint( $citations['duplicates'] ?? 0 ), absint( $citations['stale'] ?? 0 ) ) ); ?></p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) ); ?>"><?php esc_html_e( 'Open citation workspace', 'ikon-seo' ); ?></a></div>
			<div><h4><?php esc_html_e( 'Local conversions', 'ikon-seo' ); ?></h4><p><?php echo esc_html( ucfirst( $conversions['status'] ?? 'unavailable' ) ); ?></p><?php foreach ( (array) ( $conversions['totals'] ?? array() ) as $source => $metrics ) : ?><p><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $source ) ) ); ?>:</strong> <?php $pairs=array(); foreach ( (array) $metrics as $name=>$value ) { $pairs[] = ucwords( str_replace( '_', ' ', $name ) ) . ' ' . number_format_i18n( (float) $value, 0 ); } echo esc_html( implode( ' · ', array_slice( $pairs, 0, 8 ) ) ); ?></p><?php endforeach; ?><?php if ( ! empty( $conversions['measurement_gaps'] ) ) : ?><p><strong><?php esc_html_e( 'Measurement gaps:', 'ikon-seo' ); ?></strong> <?php echo esc_html( implode( ', ', (array) $conversions['measurement_gaps'] ) ); ?></p><?php endif; ?></div>
		</div>

		<h3><?php esc_html_e( 'Review response workflow', 'ikon-seo' ); ?></h3>
		<p class="description"><?php echo esc_html( $reviews['privacy_note'] ?? '' ); ?> <?php echo esc_html( $reviews['approval_note'] ?? '' ); ?></p>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Rating', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status and timing', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Owner and notes', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $reviews['items'] ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'No synchronized review workflow records are available.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( array_slice( (array) ( $reviews['items'] ?? array() ), 0, 100 ) as $item ) : ?><tr><td><?php echo esc_html( absint( $item['star_rating'] ) . '/5' ); ?><br><?php echo ! empty( $item['has_reply'] ) ? esc_html__( 'Reply detected', 'ikon-seo' ) : esc_html__( 'No reply detected', 'ikon-seo' ); ?></td><td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['status'] ) ) ); ?></strong><?php if ( ! empty( $item['overdue'] ) ) : ?> <span class="ikon-seo-pill is-failed"><?php esc_html_e( 'Overdue', 'ikon-seo' ); ?></span><?php endif; ?><br><span class="description"><?php echo esc_html( $item['due_at'] ? 'Due ' . $item['due_at'] . ' UTC' : 'No due date' ); ?></span></td><td><?php if ( $agency ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_update_local_review_task"><input type="hidden" name="review_task_id" value="<?php echo absint( $item['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_update_local_review_task_' . absint( $item['id'] ) ); ?><select name="status"><?php foreach ( array( 'open'=>'Open','in_progress'=>'In progress','draft_staged'=>'Draft staged','responded'=>'Responded','dismissed'=>'Dismissed' ) as $value=>$label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $item['status'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><select name="owner_id"><option value="0"><?php esc_html_e( 'Unassigned', 'ikon-seo' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>" <?php selected( absint( $item['owner_id'] ), absint( $user->ID ) ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select><br><textarea name="notes" rows="2" class="large-text"><?php echo esc_textarea( $item['notes'] ); ?></textarea><button class="button button-small" type="submit"><?php esc_html_e( 'Update workflow', 'ikon-seo' ); ?></button></form><?php else : ?><?php echo esc_html( $item['owner_name'] ?: 'Unassigned' ); ?><br><span class="description"><?php echo esc_html( $item['notes'] ); ?></span><?php endif; ?></td></tr><?php endforeach; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Competitor prominence evidence', 'ikon-seo' ); ?></h3>
		<p class="description"><?php echo esc_html( $prominence['note'] ?? '' ); ?></p>
		<?php if ( $agency ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ikon-seo-card"><input type="hidden" name="action" value="ikon_seo_save_local_prominence"><?php wp_nonce_field( 'ikon_seo_save_local_prominence' ); ?><div class="ikon-seo-two-columns"><p><label><?php esc_html_e( 'Competitor', 'ikon-seo' ); ?><br><input class="regular-text" required name="competitor_name"></label></p><p><label><?php esc_html_e( 'Domain', 'ikon-seo' ); ?><br><input class="regular-text" name="competitor_domain"></label></p><p><label><?php esc_html_e( 'Query', 'ikon-seo' ); ?><br><input class="regular-text" name="query"></label></p><p><label><?php esc_html_e( 'Evidence type', 'ikon-seo' ); ?><br><select name="source_type"><?php foreach ( array( 'local_pack','organic','reviews','citations','backlinks','brand_mentions','directories','manual' ) as $value ) : ?><option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $value ) ) ); ?></option><?php endforeach; ?></select></label></p><p><label><?php esc_html_e( 'Source URL', 'ikon-seo' ); ?><br><input class="large-text" type="url" name="source_url"></label></p><p><label><?php esc_html_e( 'Observed date', 'ikon-seo' ); ?><br><input type="date" name="observed_at" value="<?php echo esc_attr( current_time( 'Y-m-d', true ) ); ?>"></label></p></div><p><label><?php esc_html_e( 'Concise evidence', 'ikon-seo' ); ?><br><textarea class="large-text" rows="3" required name="evidence"></textarea></label></p><p><button class="button" type="submit"><?php esc_html_e( 'Store competitor evidence', 'ikon-seo' ); ?></button></p></form><?php endif; ?>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Competitor', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Observed', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $prominence['items'] ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'No competitor prominence evidence has been stored.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( array_slice( (array) ( $prominence['items'] ?? array() ), 0, 100 ) as $item ) : ?><tr><td><strong><?php echo esc_html( $item['competitor_name'] ); ?></strong><br><?php echo esc_html( $item['competitor_domain'] ); ?></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['source_type'] ) ) ); ?><?php if ( $item['query'] ) : ?> · <?php echo esc_html( $item['query'] ); ?><?php endif; ?><br><span class="description"><?php echo esc_html( $item['evidence'] ); ?></span></td><td><?php echo esc_html( $item['observed_at'] ); ?><?php if ( ! empty( $item['stale'] ) ) : ?><br><span class="ikon-seo-pill is-failed"><?php esc_html_e( 'Stale', 'ikon-seo' ); ?></span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table>
		<?php
	}

	private function render_local_seo() {
		$locations = $this->local->locations( true );
		$summary   = $this->local->summary();
		$audit     = $summary['nap_audit'];
		$edit_id   = absint( $_GET['edit_location'] ?? 0 );
			$editing   = $edit_id ? $this->local->location( $edit_id, true ) : null;
		$defaults  = array(
			'id' => 0, 'status' => 'active', 'location_type' => 'storefront', 'business_name' => Ikon_SEO_Plugin::settings()['site_name'],
			'location_label' => '', 'entity_type' => Ikon_SEO_Plugin::settings()['business_entity_type'], 'phone' => Ikon_SEO_Plugin::settings()['business_phone'],
			'email' => Ikon_SEO_Plugin::settings()['contact_email'], 'website_url' => home_url( '/' ), 'appointment_url' => '', 'whatsapp_url' => '',
			'address' => array( 'street' => '', 'locality' => '', 'region' => '', 'postal' => '', 'country' => '' ),
			'latitude' => '', 'longitude' => '', 'opening_hours' => array(), 'special_hours' => array(), 'primary_category' => '',
			'additional_categories' => array(), 'service_areas' => array(), 'services' => array(), 'place_id' => '', 'map_url' => '',
			'price_range' => '', 'image_url' => '', 'logo_url' => '', 'same_as' => array(), 'page_id' => 0,
			'has_customer_location' => false, 'verified' => false, 'is_primary' => false,
		);
		$form      = wp_parse_args( is_array( $editing ) ? $editing : array(), $defaults );
		$citations = $this->local->citations( 200 );
		$ranks     = $this->local->rank_entries( 200 );
		$utm       = get_transient( 'ikon_seo_local_utm_' . get_current_user_id() );
		if ( $utm ) {
			delete_transient( 'ikon_seo_local_utm_' . get_current_user_id() );
		}
		$entities = $this->profile->entity_types();
		$allowed  = $this->profile->allowed_entity_types( Ikon_SEO_Plugin::settings()['industry'] );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Local SEO workspace', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Manage real locations and service areas, audit NAP consistency, build tracked URLs, and maintain citations and imported rank observations.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo 'needs_changes' === $audit['status'] ? 'is-failed' : 'is-connected'; ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $audit['status'] ) ) ); ?></span>
		</div>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['locations'] ); ?></strong><span><?php esc_html_e( 'Location records', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['verified_locations'] ); ?></strong><span><?php esc_html_e( 'Verified locations', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['citations'] ); ?></strong><span><?php esc_html_e( 'Citations', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $audit['failures'] ); ?></strong><span><?php esc_html_e( 'NAP failures', 'ikon-seo' ); ?></span></div>
		</div>

		<h3><?php echo esc_html( $edit_id ? 'Edit location record' : 'Add location or service area' ); ?></h3>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'Only storefront and hybrid records with customers served at the address can be verified or receive a location entity. Service-area records never expose an address.', 'ikon-seo' ); ?></p></div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_local_save_location">
			<input type="hidden" name="location_id" value="<?php echo absint( $form['id'] ); ?>">
			<?php wp_nonce_field( 'ikon_seo_local_save_location' ); ?>
			<div class="ikon-seo-two-columns">
				<table class="form-table" role="presentation">
					<tr><th><label for="local_business_name"><?php esc_html_e( 'Business/location name', 'ikon-seo' ); ?></label></th><td><input class="regular-text" required id="local_business_name" name="business_name" value="<?php echo esc_attr( $form['business_name'] ); ?>"></td></tr>
					<tr><th><label for="location_label"><?php esc_html_e( 'Internal label', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="location_label" name="location_label" value="<?php echo esc_attr( $form['location_label'] ); ?>"></td></tr>
					<tr><th><label for="location_type"><?php esc_html_e( 'Location type', 'ikon-seo' ); ?></label></th><td><select id="location_type" name="location_type"><?php foreach ( array( 'storefront' => 'Storefront', 'service_area' => 'Service area', 'hybrid' => 'Hybrid', 'online' => 'Online only' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $form['location_type'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><label for="local_entity_type"><?php esc_html_e( 'Local entity type', 'ikon-seo' ); ?></label></th><td><select id="local_entity_type" name="entity_type"><?php foreach ( $allowed as $entity ) : ?><option value="<?php echo esc_attr( $entity ); ?>" <?php selected( $form['entity_type'], $entity ); ?>><?php echo esc_html( $entities[ $entity ]['label'] ?? $entity ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><label for="local_phone"><?php esc_html_e( 'Local phone', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="local_phone" name="phone" value="<?php echo esc_attr( $form['phone'] ); ?>"></td></tr>
					<tr><th><label for="local_email"><?php esc_html_e( 'Email', 'ikon-seo' ); ?></label></th><td><input class="regular-text" type="email" id="local_email" name="email" value="<?php echo esc_attr( $form['email'] ); ?>"></td></tr>
						<tr><th><label for="local_website_url"><?php esc_html_e( 'Website/landing URL', 'ikon-seo' ); ?></label></th><td><input class="regular-text" type="url" id="local_website_url" name="website_url" value="<?php echo esc_attr( $form['website_url'] ); ?>"></td></tr>
						<tr><th><label for="appointment_url"><?php esc_html_e( 'Appointment URL', 'ikon-seo' ); ?></label></th><td><input class="regular-text" type="url" id="appointment_url" name="appointment_url" value="<?php echo esc_attr( $form['appointment_url'] ); ?>"></td></tr>
						<tr><th><label for="local_whatsapp_url"><?php esc_html_e( 'WhatsApp URL', 'ikon-seo' ); ?></label></th><td><input class="regular-text" type="url" id="local_whatsapp_url" name="whatsapp_url" value="<?php echo esc_attr( $form['whatsapp_url'] ); ?>"></td></tr>
						<tr><th><label for="local_page_id"><?php esc_html_e( 'Assigned WordPress page ID', 'ikon-seo' ); ?></label></th><td><input type="number" min="0" id="local_page_id" name="page_id" value="<?php echo absint( $form['page_id'] ); ?>"></td></tr>
				</table>
				<table class="form-table" role="presentation">
					<tr><th><label for="address_street"><?php esc_html_e( 'Street address', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="address_street" name="address_street" value="<?php echo esc_attr( $form['address']['street'] ); ?>"></td></tr>
					<tr><th><label for="address_locality"><?php esc_html_e( 'City/locality', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="address_locality" name="address_locality" value="<?php echo esc_attr( $form['address']['locality'] ); ?>"></td></tr>
					<tr><th><label for="address_region"><?php esc_html_e( 'Region/state', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="address_region" name="address_region" value="<?php echo esc_attr( $form['address']['region'] ); ?>"></td></tr>
					<tr><th><label for="address_postal"><?php esc_html_e( 'Postal code', 'ikon-seo' ); ?></label></th><td><input id="address_postal" name="address_postal" value="<?php echo esc_attr( $form['address']['postal'] ); ?>"></td></tr>
					<tr><th><label for="address_country"><?php esc_html_e( 'Country code', 'ikon-seo' ); ?></label></th><td><input maxlength="2" id="address_country" name="address_country" value="<?php echo esc_attr( $form['address']['country'] ); ?>"></td></tr>
					<tr><th><label for="latitude"><?php esc_html_e( 'Latitude / longitude', 'ikon-seo' ); ?></label></th><td><input class="small-text" id="latitude" name="latitude" value="<?php echo esc_attr( $form['latitude'] ); ?>"> <input class="small-text" name="longitude" value="<?php echo esc_attr( $form['longitude'] ); ?>"></td></tr>
						<tr><th><label for="map_url"><?php esc_html_e( 'Google Maps URL', 'ikon-seo' ); ?></label></th><td><input class="regular-text" type="url" id="map_url" name="map_url" value="<?php echo esc_attr( $form['map_url'] ); ?>"></td></tr>
						<tr><th><label for="primary_category"><?php esc_html_e( 'Primary GBP category', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="primary_category" name="primary_category" value="<?php echo esc_attr( $form['primary_category'] ); ?>"></td></tr>
						<tr><th><label for="price_range"><?php esc_html_e( 'Price range', 'ikon-seo' ); ?></label></th><td><input id="price_range" name="price_range" value="<?php echo esc_attr( $form['price_range'] ); ?>" placeholder="$$"></td></tr>
						<tr><th><label for="local_image_url"><?php esc_html_e( 'Location image URL', 'ikon-seo' ); ?></label></th><td><input class="regular-text" type="url" id="local_image_url" name="image_url" value="<?php echo esc_attr( $form['image_url'] ); ?>"></td></tr>
						<tr><th><label for="local_logo_url"><?php esc_html_e( 'Location logo URL', 'ikon-seo' ); ?></label></th><td><input class="regular-text" type="url" id="local_logo_url" name="logo_url" value="<?php echo esc_attr( $form['logo_url'] ); ?>"></td></tr>
					<tr><th><?php esc_html_e( 'Eligibility', 'ikon-seo' ); ?></th><td><label><input type="checkbox" name="has_customer_location" value="1" <?php checked( $form['has_customer_location'] ); ?>> Customers are served at this address</label><br><label><input type="checkbox" name="verified" value="1" <?php checked( $form['verified'] ); ?>> Verified location</label><br><label><input type="checkbox" name="is_primary" value="1" <?php checked( $form['is_primary'] ); ?>> Primary location</label></td></tr>
				</table>
			</div>
			<table class="form-table" role="presentation">
				<tr><th><label for="service_areas"><?php esc_html_e( 'Service areas', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="service_areas" name="service_areas"><?php echo esc_textarea( implode( "\n", $form['service_areas'] ) ); ?></textarea><p class="description"><?php esc_html_e( 'One genuine city, district or service area per line.', 'ikon-seo' ); ?></p></td></tr>
				<tr><th><label for="local_services"><?php esc_html_e( 'Services at this location', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="local_services" name="services"><?php echo esc_textarea( implode( "\n", $form['services'] ) ); ?></textarea></td></tr>
					<tr><th><label for="opening_hours"><?php esc_html_e( 'Opening hours', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="opening_hours" name="opening_hours"><?php echo esc_textarea( implode( "\n", $form['opening_hours'] ) ); ?></textarea><p class="description"><?php esc_html_e( 'Use lines such as Mo-Fr 09:00-17:00 for structured data.', 'ikon-seo' ); ?></p></td></tr>
					<tr><th><label for="special_hours"><?php esc_html_e( 'Special-hours notes', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="special_hours" name="special_hours"><?php echo esc_textarea( implode( "\n", $form['special_hours'] ) ); ?></textarea><p class="description"><?php esc_html_e( 'Operational notes only in this release; confirm holiday hours in Google Business Profile.', 'ikon-seo' ); ?></p></td></tr>
					<tr><th><label for="additional_categories"><?php esc_html_e( 'Additional GBP categories', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="additional_categories" name="additional_categories"><?php echo esc_textarea( implode( "\n", $form['additional_categories'] ) ); ?></textarea></td></tr>
					<tr><th><label for="same_as"><?php esc_html_e( 'Official profile URLs', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="same_as" name="same_as"><?php echo esc_textarea( implode( "\n", $form['same_as'] ) ); ?></textarea></td></tr>
			</table>
			<p><button type="submit" class="button button-primary"><?php echo esc_html( $edit_id ? 'Update location' : 'Add location' ); ?></button><?php if ( $edit_id ) : ?> <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) ); ?>"><?php esc_html_e( 'Cancel edit', 'ikon-seo' ); ?></a><?php endif; ?></p>
		</form>

		<h3><?php esc_html_e( 'Location records', 'ikon-seo' ); ?></h3>
		<table class="widefat striped ikon-seo-log">
			<thead><tr><th><?php esc_html_e( 'Location', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Type', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'NAP', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Landing page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Actions', 'ikon-seo' ); ?></th></tr></thead>
			<tbody>
				<?php if ( ! $locations ) : ?><tr><td colspan="5"><?php esc_html_e( 'No local records yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
				<?php foreach ( $locations as $location ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $location['business_name'] ); ?></strong><?php if ( $location['is_primary'] ) : ?> <span class="ikon-seo-pill is-connected"><?php esc_html_e( 'Primary', 'ikon-seo' ); ?></span><?php endif; ?><br><?php echo esc_html( $location['location_label'] ?: $location['address']['locality'] ); ?></td>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $location['location_type'] ) ) ); ?><br><?php echo $location['verified'] ? '<span class="ikon-seo-pill is-connected">Verified</span>' : '<span class="ikon-seo-pill">Unverified</span>'; ?></td>
						<td><?php echo esc_html( $location['phone'] ?: 'No phone' ); ?><br><?php echo esc_html( implode( ', ', array_filter( $location['address'] ) ) ?: 'Address hidden/not applicable' ); ?></td>
						<td><?php if ( $location['page_url'] ) : ?><a href="<?php echo esc_url( $location['page_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( '#' . $location['page_id'] ); ?></a><?php else : ?>—<?php endif; ?></td>
							<td><a class="button" href="<?php echo esc_url( add_query_arg( 'edit_location', $location['id'], admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) ) ); ?>"><?php esc_html_e( 'Edit', 'ikon-seo' ); ?></a><?php if ( ! $location['page_id'] && ! $location['gbp_location_name'] ) : ?><form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_local_delete_location"><input type="hidden" name="location_id" value="<?php echo absint( $location['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_local_delete_location_' . absint( $location['id'] ) ); ?><button class="button button-link-delete" type="submit" data-confirm="<?php esc_attr_e( 'Delete this unassigned local record?', 'ikon-seo' ); ?>"><?php esc_html_e( 'Delete', 'ikon-seo' ); ?></button></form><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'NAP consistency audit', 'ikon-seo' ); ?></h3>
		<?php foreach ( $audit['items'] as $item ) : ?>
			<div class="ikon-seo-connection-box"><strong><?php echo esc_html( $item['name'] ); ?></strong><ul><?php foreach ( $item['checks'] as $check ) : ?><li><span class="ikon-seo-pill <?php echo 'fail' === $check['status'] ? 'is-failed' : ( 'pass' === $check['status'] ? 'is-connected' : '' ); ?>"><?php echo esc_html( strtoupper( $check['status'] ) ); ?></span> <?php echo esc_html( $check['message'] ); ?></li><?php endforeach; ?></ul></div>
		<?php endforeach; ?>

		<h3><?php esc_html_e( 'Local UTM builder', 'ikon-seo' ); ?></h3>
		<?php if ( is_array( $utm ) && ! empty( $utm['url'] ) ) : ?><div class="notice notice-success inline"><p><code id="ikon-local-utm"><?php echo esc_html( $utm['url'] ); ?></code> <button type="button" class="button" data-copy-target="#ikon-local-utm"><?php esc_html_e( 'Copy', 'ikon-seo' ); ?></button></p></div><?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_local_generate_utm"><?php wp_nonce_field( 'ikon_seo_local_generate_utm' ); ?>
			<div class="ikon-seo-inline-fields"><input class="regular-text" type="url" required name="url" value="<?php echo esc_attr( home_url( '/' ) ); ?>"><input required name="campaign" placeholder="Campaign"><input name="content" placeholder="Content/location"><button class="button" type="submit"><?php esc_html_e( 'Build tracked URL', 'ikon-seo' ); ?></button></div>
		</form>

		<div class="ikon-seo-two-columns">
			<div>
				<h3><?php esc_html_e( 'Citation tracker', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_local_save_citation"><?php wp_nonce_field( 'ikon_seo_local_save_citation' ); ?>
					<p><input class="regular-text" required name="directory_name" placeholder="Directory name"></p>
					<p><input class="regular-text" type="url" name="listing_url" placeholder="Listing URL"></p>
					<p><select name="location_id"><option value="0"><?php esc_html_e( 'Website-wide', 'ikon-seo' ); ?></option><?php foreach ( $locations as $location ) : ?><option value="<?php echo absint( $location['id'] ); ?>"><?php echo esc_html( $location['business_name'] . ' — ' . $location['location_label'] ); ?></option><?php endforeach; ?></select> <select name="status"><option value="pending">Pending</option><option value="live">Live</option><option value="missing">Missing</option><option value="duplicate">Duplicate</option></select></p>
						<p><input name="business_name" placeholder="Name used"> <input name="phone" placeholder="Phone used"></p>
						<p><textarea class="large-text" name="address" placeholder="Address used"></textarea></p>
						<p><input name="login_owner" placeholder="Listing owner"> <input type="date" name="last_checked" title="Last checked"> <input type="date" name="next_review" title="Next review"></p>
						<p><textarea class="large-text" name="notes" placeholder="Notes"></textarea></p>
					<p><label><input type="checkbox" name="correction_required" value="1"> Correction required</label> <label><input type="checkbox" name="duplicate_warning" value="1"> Duplicate warning</label></p>
					<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Add citation', 'ikon-seo' ); ?></button></p>
				</form>
				<form class="ikon-seo-inline-fields" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_local_import_citations"><?php wp_nonce_field( 'ikon_seo_local_import_citations' ); ?><input type="file" required name="csv_file" accept=".csv,text/csv"><button class="button" type="submit"><?php esc_html_e( 'Import citations CSV', 'ikon-seo' ); ?></button></form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_local_export_citations"><?php wp_nonce_field( 'ikon_seo_local_export_citations' ); ?><button class="button" type="submit"><?php esc_html_e( 'Export citations CSV', 'ikon-seo' ); ?></button></form>
			</div>
			<div>
				<h3><?php esc_html_e( 'Local rank workspace', 'ikon-seo' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Store manual or provider-exported observations. Ikon SEO does not scrape Google or claim automatic geo-grid accuracy.', 'ikon-seo' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_local_save_rank"><?php wp_nonce_field( 'ikon_seo_local_save_rank' ); ?>
					<p><input class="regular-text" required name="keyword" placeholder="Keyword"></p>
						<p><input class="regular-text" required name="search_location" placeholder="City/ZIP/coordinates label"></p>
						<p><select name="location_id"><option value="0"><?php esc_html_e( 'Unassigned location', 'ikon-seo' ); ?></option><?php foreach ( $locations as $location ) : ?><option value="<?php echo absint( $location['id'] ); ?>"><?php echo esc_html( $location['business_name'] . ' — ' . $location['location_label'] ); ?></option><?php endforeach; ?></select> <input name="source" placeholder="Data source"></p>
						<p><input class="regular-text" name="competitors" placeholder="Competitors separated by |"></p>
					<p><input type="number" min="0" step="0.1" name="organic_position" placeholder="Organic"> <input type="number" min="0" step="0.1" name="local_pack_position" placeholder="Local pack"></p>
					<p><select name="device"><option value="mobile">Mobile</option><option value="desktop">Desktop</option></select> <input type="date" name="checked_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"></p>
					<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Add rank observation', 'ikon-seo' ); ?></button></p>
				</form>
				<form class="ikon-seo-inline-fields" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_local_import_ranks"><?php wp_nonce_field( 'ikon_seo_local_import_ranks' ); ?><input type="file" required name="csv_file" accept=".csv,text/csv"><button class="button" type="submit"><?php esc_html_e( 'Import rank CSV', 'ikon-seo' ); ?></button></form>
			</div>
		</div>

		<table class="widefat striped ikon-seo-log"><thead><tr><th><?php esc_html_e( 'Citation', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'NAP used', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( ! $citations ) : ?><tr><td colspan="4"><?php esc_html_e( 'No citations recorded.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( $citations as $citation ) : ?><tr><td><strong><?php echo esc_html( $citation['directory_name'] ); ?></strong><br><?php if ( $citation['listing_url'] ) : ?><a href="<?php echo esc_url( $citation['listing_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open listing', 'ikon-seo' ); ?></a><?php endif; ?></td><td><?php echo esc_html( $citation['business_name'] ); ?><br><?php echo esc_html( $citation['phone'] ); ?></td><td><?php echo esc_html( ucfirst( $citation['status'] ) ); ?><?php if ( $citation['correction_required'] ) : ?> <span class="ikon-seo-pill is-failed"><?php esc_html_e( 'Fix', 'ikon-seo' ); ?></span><?php endif; ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_local_delete_citation"><input type="hidden" name="citation_id" value="<?php echo absint( $citation['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_local_delete_citation_' . absint( $citation['id'] ) ); ?><button class="button button-link-delete" type="submit" data-confirm="<?php esc_attr_e( 'Delete this citation record?', 'ikon-seo' ); ?>"><?php esc_html_e( 'Delete', 'ikon-seo' ); ?></button></form></td></tr><?php endforeach; ?></tbody></table>

		<h3><?php esc_html_e( 'Recent rank observations', 'ikon-seo' ); ?></h3>
		<table class="widefat striped ikon-seo-log"><thead><tr><th><?php esc_html_e( 'Keyword', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Search location', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Organic', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Local pack', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Date', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( ! $ranks ) : ?><tr><td colspan="5"><?php esc_html_e( 'No rank observations recorded.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( array_slice( $ranks, 0, 100 ) as $rank ) : ?><tr><td><?php echo esc_html( $rank['keyword'] ); ?></td><td><?php echo esc_html( $rank['search_location'] . ' · ' . ucfirst( $rank['device'] ) ); ?></td><td><?php echo null === $rank['organic_position'] ? '—' : esc_html( $rank['organic_position'] ); ?></td><td><?php echo null === $rank['local_pack_position'] ? '—' : esc_html( $rank['local_pack_position'] ); ?></td><td><?php echo esc_html( $rank['checked_date'] ); ?></td></tr><?php endforeach; ?></tbody></table>
		<?php
	}

	private function render_business_profile( array $settings ) {
		$status       = $this->gbp->status();
		$availability = sanitize_key( $settings['gbp_availability'] ?? 'unknown' );
		if ( ! in_array( $availability, array( 'unknown', 'available', 'not_available' ), true ) ) {
			$availability = 'unknown';
		}
		if ( ! empty( $status['connected'] ) ) {
			$availability = 'available';
		}
		$status_label = $status['connected'] ? __( 'Connected', 'ikon-seo' ) : ( 'not_available' === $availability ? __( 'Not using GBP', 'ikon-seo' ) : ( 'available' === $availability ? __( 'Not connected', 'ikon-seo' ) : __( 'Optional setup', 'ikon-seo' ) ) );
		$status_class = $status['connected'] ? 'is-connected' : '';
			$locations = $this->local->locations( true );
		$accounts  = $status['connected'] ? $this->gbp->accounts() : array( 'items' => array() );
		$remote    = $status['connected'] && $status['account'] ? $this->gbp->remote_locations() : array( 'items' => array() );
		$drafts    = $this->gbp->drafts( 100 );
		$view_id   = absint( $_GET['gbp_location_id'] ?? 0 );
			$reviews   = $view_id ? $this->gbp->reviews( $view_id ) : null;
			$performance = $view_id ? $this->gbp->performance( $view_id, 30 ) : null;
			$comparison  = $view_id ? $this->gbp->comparison( $view_id ) : null;
			$keywords    = $view_id ? $this->gbp->search_keywords( $view_id, 3 ) : null;
		if ( $view_id && ! is_wp_error( $reviews ) ) {
			$this->gbp->mark_reviews_seen( $view_id );
		}
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Google Business Profile', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Read locations, reviews and performance. Posts and review replies are staged locally and require explicit administrator approval.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
		</div>

		<div class="ikon-seo-connection-box">
			<h3><?php esc_html_e( 'Do you currently have a Google Business Profile?', 'ikon-seo' ); ?></h3>
			<p class="description"><?php esc_html_e( 'This is optional. Website auditing, Local SEO, page planning, schema and draft creation can continue without a Google Business Profile.', 'ikon-seo' ); ?></p>
			<div class="ikon-seo-actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_gbp_set_availability">
					<input type="hidden" name="gbp_availability" value="not_available">
					<?php wp_nonce_field( 'ikon_seo_gbp_set_availability' ); ?>
					<button class="button <?php echo 'not_available' === $availability ? 'button-primary' : ''; ?>" type="submit"><?php esc_html_e( 'No, not yet', 'ikon-seo' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_gbp_set_availability">
					<input type="hidden" name="gbp_availability" value="available">
					<?php wp_nonce_field( 'ikon_seo_gbp_set_availability' ); ?>
					<button class="button <?php echo 'available' === $availability ? 'button-primary' : ''; ?>" type="submit"><?php esc_html_e( 'Yes, I have one', 'ikon-seo' ); ?></button>
				</form>
			</div>
		</div>

		<?php if ( 'not_available' === $availability ) : ?>
			<div class="notice notice-success inline"><p><strong><?php esc_html_e( 'Google Business Profile skipped.', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'Nothing is missing. You can continue with website scanning, Local SEO and Page Plans, and connect a profile later.', 'ikon-seo' ); ?></p></div>
			<div class="ikon-seo-actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=inventory' ) ); ?>"><?php esc_html_e( 'Scan website', 'ikon-seo' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) ); ?>"><?php esc_html_e( 'Open Local SEO', 'ikon-seo' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=queue' ) ); ?>"><?php esc_html_e( 'Open Page Plans', 'ikon-seo' ); ?></a>
			</div>
			<?php return; ?>
		<?php elseif ( 'unknown' === $availability ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Choose one option above. Select “No, not yet” to hide this technical setup and continue normally.', 'ikon-seo' ); ?></p></div>
			<?php return; ?>
		<?php endif; ?>

		<?php if ( ! Ikon_SEO_Agency::can_manage() && ! $status['connected'] ) : ?>
			<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Connection setup is managed by Ikon.', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'You can continue using website audits, Local SEO and Page Plans. An approved agency administrator can connect the Business Profile later.', 'ikon-seo' ); ?></p></div>
			<?php return; ?>
		<?php endif; ?>

		<div class="notice notice-warning inline"><p><strong><?php esc_html_e( 'Permission model:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'Google provides the broad business.manage scope, not a read-only scope. Ikon SEO enforces read-only operation by default and exposes no remote send endpoint. Only the exact draft approved here can be sent.', 'ikon-seo' ); ?></p></div>
		<?php if ( ! empty( $status['review_alerts']['total'] ) ) : ?><div class="notice notice-info inline"><p><?php echo esc_html( sprintf( '%d new or updated reviews were detected across linked locations.', absint( $status['review_alerts']['total'] ) ) ); ?></p></div><?php endif; ?>
		<?php if ( $status['last_error'] ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $status['last_error'] ); ?></p></div><?php endif; ?>

		<h3><?php esc_html_e( '1. OAuth application', 'ikon-seo' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Your Google Cloud project must first receive Business Profile API access. Add the callback below as an authorized redirect URI.', 'ikon-seo' ); ?></p>
		<div class="ikon-seo-copy-row"><code id="ikon-gbp-callback"><?php echo esc_html( $status['callback_url'] ); ?></code><button type="button" class="button" data-copy-target="#ikon-gbp-callback"><?php esc_html_e( 'Copy callback', 'ikon-seo' ); ?></button></div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_gbp_save_credentials"><?php wp_nonce_field( 'ikon_seo_gbp_save_credentials' ); ?>
			<table class="form-table" role="presentation"><tr><th><label for="gbp_client_id"><?php esc_html_e( 'Google client ID', 'ikon-seo' ); ?></label></th><td><input class="large-text" id="gbp_client_id" name="gbp_client_id" value="<?php echo esc_attr( $settings['gbp_client_id'] ); ?>"></td></tr><tr><th><label for="gbp_client_secret"><?php esc_html_e( 'Client secret', 'ikon-seo' ); ?></label></th><td><input class="regular-text" type="password" id="gbp_client_secret" name="gbp_client_secret" autocomplete="new-password"><p class="description"><?php esc_html_e( 'Encrypted with site-specific WordPress salts. Leave blank to retain the saved secret.', 'ikon-seo' ); ?></p></td></tr></table>
			<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Save credentials', 'ikon-seo' ); ?></button></p>
		</form>
		<div class="ikon-seo-actions">
			<?php if ( $status['configured'] && ! $status['connected'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_gbp_connect"><?php wp_nonce_field( 'ikon_seo_gbp_connect' ); ?><button class="button button-primary" type="submit"><?php esc_html_e( 'Connect Google account', 'ikon-seo' ); ?></button></form><?php endif; ?>
			<?php if ( $status['connected'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_gbp_disconnect"><?php wp_nonce_field( 'ikon_seo_gbp_disconnect' ); ?><button class="button button-link-delete" type="submit" data-confirm="<?php esc_attr_e( 'Disconnect Google Business Profile?', 'ikon-seo' ); ?>"><?php esc_html_e( 'Disconnect', 'ikon-seo' ); ?></button></form><?php endif; ?>
		</div>

		<?php if ( is_wp_error( $accounts ) ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $accounts->get_error_message() ); ?></p></div><?php elseif ( $status['connected'] ) : ?>
			<h3><?php esc_html_e( '2. Account and location matching', 'ikon-seo' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_gbp_select_account"><?php wp_nonce_field( 'ikon_seo_gbp_select_account' ); ?><select name="gbp_account" required><option value=""><?php esc_html_e( 'Choose account', 'ikon-seo' ); ?></option><?php foreach ( $accounts['items'] as $account ) : ?><option value="<?php echo esc_attr( $account['name'] ); ?>" <?php selected( $status['account'], $account['name'] ); ?>><?php echo esc_html( $account['account_name'] . ' — ' . $account['name'] ); ?></option><?php endforeach; ?></select> <button class="button" type="submit"><?php esc_html_e( 'Select account', 'ikon-seo' ); ?></button></form>
			<?php if ( is_wp_error( $remote ) ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $remote->get_error_message() ); ?></p></div><?php elseif ( $status['account'] ) : ?>
				<table class="widefat striped ikon-seo-log"><thead><tr><th><?php esc_html_e( 'Ikon SEO location', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Google location', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Reports', 'ikon-seo' ); ?></th></tr></thead><tbody>
					<?php foreach ( $locations as $location ) : ?><tr><td><strong><?php echo esc_html( $location['business_name'] ); ?></strong><br><?php echo esc_html( $location['location_label'] ); ?><?php if ( 'inactive' === $location['status'] ) : ?> <span class="ikon-seo-pill"><?php esc_html_e( 'Inactive', 'ikon-seo' ); ?></span><?php endif; ?></td><td><?php if ( 'online' === $location['location_type'] || 'inactive' === $location['status'] ) : ?><span class="description"><?php echo esc_html( 'online' === $location['location_type'] ? 'Online-only records are not eligible.' : 'Activate this local record before linking it.' ); ?></span><?php else : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_gbp_link_location"><input type="hidden" name="local_id" value="<?php echo absint( $location['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_gbp_link_location_' . absint( $location['id'] ) ); ?><select name="remote_name" required><option value=""><?php esc_html_e( 'Choose matching GBP location', 'ikon-seo' ); ?></option><?php foreach ( $remote['items'] as $remote_location ) : ?><option value="<?php echo esc_attr( $remote_location['name'] ); ?>" <?php selected( $location['gbp_location_name'], $remote_location['name'] ); ?>><?php echo esc_html( $remote_location['title'] . ' — ' . $remote_location['name'] ); ?></option><?php endforeach; ?></select> <button class="button" type="submit"><?php esc_html_e( 'Link', 'ikon-seo' ); ?></button></form><?php endif; ?><?php if ( $location['gbp_location_name'] ) : ?><form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_gbp_unlink_location"><input type="hidden" name="local_id" value="<?php echo absint( $location['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_gbp_unlink_location_' . absint( $location['id'] ) ); ?><button class="button button-link-delete" type="submit" data-confirm="<?php esc_attr_e( 'Unlink this Google Business Profile location?', 'ikon-seo' ); ?>"><?php esc_html_e( 'Unlink', 'ikon-seo' ); ?></button></form><?php endif; ?></td><td><?php if ( $location['gbp_location_name'] && 'active' === $location['status'] ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( 'gbp_location_id', $location['id'], admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) ) ); ?>"><?php esc_html_e( 'Reviews & performance', 'ikon-seo' ); ?></a><?php else : ?>—<?php endif; ?></td></tr><?php endforeach; ?>
				</tbody></table>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $view_id ) : ?>
			<h3><?php esc_html_e( 'Website versus Google Business Profile', 'ikon-seo' ); ?></h3>
			<?php if ( is_wp_error( $comparison ) ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $comparison->get_error_message() ); ?></p></div><?php else : ?><div class="ikon-seo-connection-box"><p><strong><?php echo esc_html( 'Consistency status: ' . ucwords( str_replace( '_', ' ', $comparison['status'] ) ) ); ?></strong></p><ul><?php foreach ( $comparison['checks'] as $check ) : ?><li><span class="ikon-seo-pill <?php echo 'fail' === $check['status'] ? 'is-failed' : ( 'pass' === $check['status'] ? 'is-connected' : '' ); ?>"><?php echo esc_html( strtoupper( $check['status'] ) ); ?></span> <?php echo esc_html( ucwords( str_replace( '_', ' ', $check['id'] ) ) . ': ' . $check['message'] ); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
				<?php if ( ! is_wp_error( $comparison ) && ! empty( $comparison['remote']['new_review_url'] ) ) : ?>
					<div class="ikon-seo-connection-box">
						<p><strong><?php esc_html_e( 'Google review request link', 'ikon-seo' ); ?></strong></p>
						<div class="ikon-seo-copy-row"><code id="ikon-gbp-review-link"><?php echo esc_html( $comparison['remote']['new_review_url'] ); ?></code><button type="button" class="button" data-copy-target="#ikon-gbp-review-link"><?php esc_html_e( 'Copy review link', 'ikon-seo' ); ?></button> <a class="button" href="<?php echo esc_url( $comparison['remote']['new_review_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open link', 'ikon-seo' ); ?></a></div>
						<p class="description"><?php esc_html_e( 'Request reviews from all customers without incentives or review gating. Google’s Business Profile “Get more reviews” screen can generate the official QR code for this same link.', 'ikon-seo' ); ?></p>
					</div>
				<?php endif; ?>
				<h3><?php esc_html_e( 'Performance — last 30 days', 'ikon-seo' ); ?></h3>
				<?php if ( is_wp_error( $performance ) ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $performance->get_error_message() ); ?></p></div><?php else : ?><div class="ikon-seo-metrics"><?php foreach ( (array) $performance['totals'] as $metric => $value ) : ?><div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $value ) ); ?></strong><span><?php echo esc_html( ucwords( str_replace( '_', ' ', $metric ) ) ); ?></span></div><?php endforeach; ?></div><?php endif; ?>
				<h3><?php esc_html_e( 'Search keywords — last three complete months', 'ikon-seo' ); ?></h3>
				<?php if ( is_wp_error( $keywords ) ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $keywords->get_error_message() ); ?></p></div><?php elseif ( empty( $keywords['items'] ) ) : ?><p class="description"><?php esc_html_e( 'Google returned no monthly search-keyword rows for this period.', 'ikon-seo' ); ?></p><?php else : ?><table class="widefat striped ikon-seo-log"><thead><tr><th><?php esc_html_e( 'Keyword', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Impressions', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( array_slice( $keywords['items'], 0, 100 ) as $keyword ) : ?><tr><td><?php echo esc_html( $keyword['keyword'] ); ?></td><td><?php echo null !== $keyword['value'] ? esc_html( number_format_i18n( $keyword['value'] ) ) : esc_html( 'Below ' . number_format_i18n( $keyword['threshold'] ) ); ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
			<h3><?php esc_html_e( 'Reviews and response drafts', 'ikon-seo' ); ?></h3>
			<?php if ( is_wp_error( $reviews ) ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $reviews->get_error_message() ); ?></p></div><?php else : ?><p><?php echo esc_html( sprintf( '%.1f average from %d reviews.', $reviews['average_rating'], $reviews['total_reviews'] ) ); ?></p><?php foreach ( $reviews['items'] as $review ) : ?><div class="ikon-seo-connection-box"><strong><?php echo esc_html( $review['reviewer_name'] . ' — ' . ucwords( strtolower( str_replace( '_', ' ', $review['star_rating'] ) ) ) ); ?></strong><p><?php echo esc_html( $review['comment'] ?: 'No written comment.' ); ?></p><?php if ( $review['owner_reply'] ) : ?><p><em><?php echo esc_html( 'Current reply: ' . $review['owner_reply'] ); ?></em></p><?php endif; ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_gbp_stage_draft"><input type="hidden" name="location_id" value="<?php echo absint( $view_id ); ?>"><input type="hidden" name="draft_type" value="review_reply"><input type="hidden" name="review_name" value="<?php echo esc_attr( $review['name'] ); ?>"><?php wp_nonce_field( 'ikon_seo_gbp_stage_draft' ); ?><textarea class="large-text" rows="3" required maxlength="4000" name="content" placeholder="Draft a personalized response"></textarea><p><button class="button" type="submit"><?php esc_html_e( 'Stage reply for approval', 'ikon-seo' ); ?></button></p></form></div><?php endforeach; ?><?php endif; ?>
			<h3><?php esc_html_e( 'Stage a Google Post', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_gbp_stage_draft"><input type="hidden" name="location_id" value="<?php echo absint( $view_id ); ?>"><input type="hidden" name="draft_type" value="google_post"><?php wp_nonce_field( 'ikon_seo_gbp_stage_draft' ); ?><p><select name="topic_type"><option value="STANDARD">Standard</option><option value="EVENT">Event</option><option value="OFFER">Offer</option></select> <input name="event_title" placeholder="Event/offer title"></p><p><textarea class="large-text" rows="4" required maxlength="1500" name="content" placeholder="Post text"></textarea></p><p><select name="call_to_action"><option value="">No call to action</option><option value="book">Book</option><option value="order">Order</option><option value="shop">Shop</option><option value="learn_more">Learn more</option><option value="sign_up">Sign up</option><option value="call">Call</option></select> <input class="regular-text" type="url" name="call_to_action_url" placeholder="Same-site CTA URL"></p><p><input type="datetime-local" name="start_time"> <input type="datetime-local" name="end_time"></p><p><input name="coupon_code" placeholder="Offer coupon code"> <input class="regular-text" type="url" name="redeem_online_url" placeholder="Same-site redemption URL"></p><p><textarea class="large-text" rows="2" name="terms_conditions" placeholder="Offer terms and conditions"></textarea></p><p><button class="button" type="submit"><?php esc_html_e( 'Stage post for approval', 'ikon-seo' ); ?></button></p></form>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Administrator approval queue', 'ikon-seo' ); ?></h3>
		<table class="widefat striped ikon-seo-log"><thead><tr><th><?php esc_html_e( 'Draft', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Content', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Actions', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( ! $drafts ) : ?><tr><td colspan="4"><?php esc_html_e( 'No Business Profile drafts.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( $drafts as $draft ) : ?><tr><td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $draft['draft_type'] ) ) ); ?></strong><br><?php echo esc_html( '#' . $draft['id'] . ' · Location ' . $draft['location_id'] ); ?></td><td><?php echo esc_html( wp_trim_words( $draft['content'], 35 ) ); ?><?php if ( $draft['last_error'] ) : ?><br><span class="ikon-seo-error-text"><?php echo esc_html( $draft['last_error'] ); ?></span><?php endif; ?></td><td><span class="ikon-seo-pill <?php echo 'failed' === $draft['status'] ? 'is-failed' : ( 'sent' === $draft['status'] ? 'is-connected' : '' ); ?>"><?php echo esc_html( ucfirst( $draft['status'] ) ); ?></span></td><td><?php if ( 'draft' === $draft['status'] ) : ?><div class="ikon-seo-actions"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_gbp_approve_draft"><input type="hidden" name="draft_id" value="<?php echo absint( $draft['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_gbp_approve_draft_' . absint( $draft['id'] ) ); ?><button class="button button-primary" type="submit" data-confirm="<?php esc_attr_e( 'Send this exact content to Google Business Profile now?', 'ikon-seo' ); ?>"><?php esc_html_e( 'Approve & send', 'ikon-seo' ); ?></button></form><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_gbp_reject_draft"><input type="hidden" name="draft_id" value="<?php echo absint( $draft['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_gbp_reject_draft_' . absint( $draft['id'] ) ); ?><button class="button" type="submit"><?php esc_html_e( 'Reject', 'ikon-seo' ); ?></button></form></div><?php else : ?>—<?php endif; ?></td></tr><?php endforeach; ?></tbody></table>
		<?php
	}

	private function render_auto_discovery() {
		$report   = $this->auto_discovery->report();
		$settings = Ikon_SEO_Plugin::settings();
		$summary  = (array) ( $report['summary'] ?? array() );
		$facts    = (array) ( $report['facts'] ?? array() );
		$conflicts = (array) ( $report['conflicts'] ?? array() );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Auto Discovery & Strategy Builder', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Inspect the existing website, propose profile and strategy values, show confidence and evidence, and request confirmation before anything is applied.', 'ikon-seo' ); ?></p>
			</div>
			<?php if ( ! empty( $report['generated_at'] ) ) : ?>
				<span class="ikon-seo-pill is-connected"><?php echo esc_html( 'Last run: ' . $report['generated_at'] . ' UTC' ); ?></span>
			<?php endif; ?>
		</div>

		<div class="notice notice-info inline">
			<p><strong><?php esc_html_e( 'Review-first automation:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'The scan reads WordPress, public same-site pages and available read-only connections. It does not publish, edit pages, create redirects, change indexation, contact competitors or invent business facts.', 'ikon-seo' ); ?></p>
		</div>

		<div class="ikon-seo-grid ikon-seo-grid-3">
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( '1. Research', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Review page titles, visible content, contact paths, website plugins, products, posts, language and regional signals.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( '2. Confirm', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'High-confidence technical facts may be selected immediately. Services, audiences, locations and positioning remain confirmation-controlled.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( '3. Prepare', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Apply selected values, optionally create the recommended workflow and run one bounded read-only task.', 'ikon-seo' ); ?></p>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ikon-seo-card">
			<input type="hidden" name="action" value="ikon_seo_run_auto_discovery">
			<?php wp_nonce_field( 'ikon_seo_run_auto_discovery' ); ?>
			<h3><?php echo empty( $report['generated_at'] ) ? esc_html__( 'Research this website', 'ikon-seo' ) : esc_html__( 'Refresh website research', 'ikon-seo' ); ?></h3>
			<p><?php esc_html_e( 'Use a bounded page count for shared hosting. The first run should normally review 50 to 100 pages.', 'ikon-seo' ); ?></p>
			<label>
				<strong><?php esc_html_e( 'Maximum public pages to review', 'ikon-seo' ); ?></strong>
				<input type="number" name="max_pages" min="10" max="300" value="<?php echo esc_attr( absint( $settings['auto_discovery_max_pages'] ?? 100 ) ); ?>">
			</label>
			<?php submit_button( empty( $report['generated_at'] ) ? __( 'Research and Configure This Website', 'ikon-seo' ) : __( 'Refresh Auto Discovery', 'ikon-seo' ), 'primary', 'submit', false ); ?>
		</form>

		<?php if ( ! empty( $report['generated_at'] ) ) : ?>
			<div class="ikon-seo-metrics">
				<div class="ikon-seo-metric"><strong><?php echo absint( $summary['pages_reviewed'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Pages reviewed', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo absint( $summary['high_confidence'] ?? 0 ); ?></strong><span><?php esc_html_e( 'High-confidence values', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo absint( $summary['needs_confirmation'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Need confirmation', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo absint( $summary['conflicts'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Conflicts detected', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( $summary['operating_mode_label'] ?? '—' ); ?></strong><span><?php esc_html_e( 'Suggested mode', 'ikon-seo' ); ?></span></div>
			</div>

			<?php if ( $conflicts ) : ?>
				<div class="notice notice-warning inline"><p><strong><?php esc_html_e( 'Resolve these conflicts before accepting related values.', 'ikon-seo' ); ?></strong></p></div>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Area', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Finding', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Detected values', 'ikon-seo' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $conflicts as $conflict ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $conflict['area'] ?? '' ); ?></strong></td>
							<td><?php echo esc_html( $conflict['message'] ?? '' ); ?></td>
							<td><?php echo esc_html( implode( ', ', (array) ( $conflict['values'] ?? array() ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_apply_auto_discovery">
				<?php wp_nonce_field( 'ikon_seo_apply_auto_discovery' ); ?>
				<h3><?php esc_html_e( 'Review detected values', 'ikon-seo' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Only checked values will be applied. Existing confirmed values remain unchanged unless overwrite is explicitly enabled.', 'ikon-seo' ); ?></p>

				<table class="widefat striped ikon-seo-log">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Use', 'ikon-seo' ); ?></th>
							<th><?php esc_html_e( 'Field', 'ikon-seo' ); ?></th>
							<th><?php esc_html_e( 'Suggested value', 'ikon-seo' ); ?></th>
							<th><?php esc_html_e( 'Confidence', 'ikon-seo' ); ?></th>
							<th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th>
							<th><?php esc_html_e( 'Current value', 'ikon-seo' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $facts as $fact ) : ?>
						<?php
						$checked = 'high' === ( $fact['confidence'] ?? '' ) && empty( $fact['needs_confirmation'] ) && empty( $fact['identity_sensitive'] );
						$current = is_array( $fact['current_value'] ?? null ) ? implode( "\n", $fact['current_value'] ) : (string) ( $fact['current_value'] ?? '' );
						?>
						<tr>
							<td><input type="checkbox" name="fields[]" value="<?php echo esc_attr( $fact['id'] ); ?>" <?php checked( $checked ); ?>></td>
							<td>
								<strong><?php echo esc_html( $fact['label'] ); ?></strong>
								<br><small><?php echo esc_html( ucfirst( $fact['group'] ) ); ?></small>
								<?php if ( ! empty( $fact['identity_sensitive'] ) ) : ?><br><span class="ikon-seo-pill is-failed"><?php esc_html_e( 'May reset workflow identity', 'ikon-seo' ); ?></span><?php endif; ?>
							</td>
							<td><div style="white-space:pre-line;max-width:420px"><?php echo esc_html( $fact['display_value'] ); ?></div></td>
							<td>
								<span class="ikon-seo-pill <?php echo 'high' === $fact['confidence'] ? 'is-connected' : ( 'low' === $fact['confidence'] ? 'is-failed' : '' ); ?>"><?php echo esc_html( ucfirst( $fact['confidence'] ) . ' · ' . absint( $fact['score'] ) . '/100' ); ?></span>
								<?php if ( ! empty( $fact['needs_confirmation'] ) ) : ?><br><small><?php esc_html_e( 'Business confirmation required', 'ikon-seo' ); ?></small><?php endif; ?>
							</td>
							<td><?php echo esc_html( implode( '; ', (array) $fact['sources'] ) ); ?></td>
							<td><div style="white-space:pre-line;max-width:300px"><?php echo esc_html( $current ? $current : '—' ); ?></div></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<div class="ikon-seo-card" style="margin-top:20px">
					<label><input type="checkbox" name="overwrite" value="1"> <?php esc_html_e( 'Overwrite existing confirmed values selected above', 'ikon-seo' ); ?></label><br>
					<label><input type="checkbox" name="create_workflow" value="1" checked> <?php esc_html_e( 'Create the recommended workflow when no active workflow exists', 'ikon-seo' ); ?></label><br>
					<label><input type="checkbox" name="run_safe_task" value="1"> <?php esc_html_e( 'Run one bounded read-only workflow task after applying', 'ikon-seo' ); ?></label>
					<p class="description"><?php esc_html_e( 'Identity-sensitive changes can invalidate the existing workflow key. Leave those unchecked unless the current Website Profile is incorrect.', 'ikon-seo' ); ?></p>
				</div>

				<?php submit_button( __( 'Apply Selected Suggestions', 'ikon-seo' ), 'primary' ); ?>
			</form>

			<div class="ikon-seo-next-step">
				<div><strong><?php esc_html_e( 'Next: review uncertain facts', 'ikon-seo' ); ?></strong><p><?php esc_html_e( 'Confirm, correct or reject business facts and resolve conflicts before Guided Launch can activate the workflow.', 'ikon-seo' ); ?></p></div>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=discovery-review' ) ); ?>"><?php esc_html_e( 'Open Fact Review', 'ikon-seo' ); ?></a>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ikon-seo-card">
			<input type="hidden" name="action" value="ikon_seo_save_auto_discovery_settings">
			<?php wp_nonce_field( 'ikon_seo_save_auto_discovery_settings' ); ?>
			<h3><?php esc_html_e( 'Auto Discovery settings', 'ikon-seo' ); ?></h3>
			<label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['auto_discovery_enabled'] ) ); ?>> <?php esc_html_e( 'Schedule the first local discovery after installation or upgrade', 'ikon-seo' ); ?></label><br>
			<label><input type="checkbox" name="include_connected" value="1" <?php checked( ! empty( $settings['auto_discovery_include_connected'] ) ); ?>> <?php esc_html_e( 'Use available read-only Search Console and Analytics connection status when suggesting metrics', 'ikon-seo' ); ?></label><br>
			<label><?php esc_html_e( 'Default page limit', 'ikon-seo' ); ?> <input type="number" name="max_pages" min="10" max="300" value="<?php echo esc_attr( absint( $settings['auto_discovery_max_pages'] ?? 100 ) ); ?>"></label>
			<?php submit_button( __( 'Save Auto Discovery Settings', 'ikon-seo' ), 'secondary' ); ?>
		</form>

		<div class="notice notice-info inline">
			<p><?php esc_html_e( 'Competitor and keyword research still requires stored competitor evidence, approved imports, Search Console data or a separately configured research workflow. This module does not scrape search engines from the WordPress host.', 'ikon-seo' ); ?></p>
		</div>
		<?php
	}



	private function render_discovery_review() {
		$report    = $this->discovery_review->report();
		$sections  = (array) ( $report['sections'] ?? array() );
		$counts    = (array) ( $report['counts'] ?? array() );
		$rescan    = (array) ( $report['rescan'] ?? array() );
		$conflicts = (array) ( $report['conflicts'] ?? array() );
		$generated = sanitize_text_field( $report['generated_at'] ?? '' );
		$status_labels = array(
			'detected'           => __( 'Detected', 'ikon-seo' ),
			'confirmed'          => __( 'Confirmed', 'ikon-seo' ),
			'edited'             => __( 'Edited by user', 'ikon-seo' ),
			'rejected'           => __( 'Rejected', 'ikon-seo' ),
			'needs_confirmation' => __( 'Needs confirmation', 'ikon-seo' ),
		);
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Fact-Level Strategy Review', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Confirm, correct or reject each detected fact. A later rescan marks changed confirmed evidence as outdated instead of overwriting your decision.', 'ikon-seo' ); ?></p>
			</div>
			<?php if ( $generated ) : ?><span class="ikon-seo-pill is-connected"><?php echo esc_html( 'Discovery: ' . $generated . ' UTC' ); ?></span><?php endif; ?>
		</div>

		<?php if ( ! $generated ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Run Auto Discovery before reviewing facts.', 'ikon-seo' ); ?></p></div>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=auto-discovery' ) ); ?>"><?php esc_html_e( 'Open Auto Discovery', 'ikon-seo' ); ?></a>
			<?php return; ?>
		<?php endif; ?>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo absint( $counts['confirmed'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Confirmed', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $counts['edited'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Corrected', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $counts['needs_confirmation'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Need confirmation', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $counts['outdated'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Changed after rescan', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $report['unresolved_conflicts'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Unresolved conflicts', 'ikon-seo' ); ?></span></div>
		</div>

		<div class="ikon-seo-card">
			<h3><?php esc_html_e( 'Latest rescan comparison', 'ikon-seo' ); ?></h3>
			<p><?php echo esc_html( sprintf( '%d new facts, %d changed facts, %d unchanged facts and %d outdated or removed facts.', absint( $rescan['new_facts'] ?? 0 ), absint( $rescan['changed_facts'] ?? 0 ), absint( $rescan['unchanged_facts'] ?? 0 ), absint( $rescan['outdated_facts'] ?? 0 ) ) ); ?></p>
		</div>

		<div class="ikon-seo-card">
			<h3><?php esc_html_e( 'Safe bulk review', 'ikon-seo' ); ?></h3>
			<p><?php esc_html_e( 'Accept only high-confidence, non-sensitive technical facts. Identity-sensitive fields, business decisions and conflicting evidence are excluded.', 'ikon-seo' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_accept_high_confidence_facts">
				<input type="hidden" name="generated_at" value="<?php echo esc_attr( $generated ); ?>">
				<?php wp_nonce_field( 'ikon_seo_accept_high_confidence_facts' ); ?>
				<button class="button" type="submit"><?php esc_html_e( 'Accept High-Confidence Technical Facts', 'ikon-seo' ); ?></button>
			</form>
		</div>

		<?php if ( $conflicts ) : ?>
			<h3><?php esc_html_e( 'Conflict resolution', 'ikon-seo' ); ?></h3>
			<?php foreach ( $conflicts as $conflict ) : ?>
				<div class="ikon-seo-card">
					<h4><?php echo esc_html( $conflict['area'] ?? __( 'Detected conflict', 'ikon-seo' ) ); ?> <span class="ikon-seo-pill <?php echo 'resolved' === ( $conflict['status'] ?? '' ) ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ucfirst( $conflict['status'] ?? 'unresolved' ) ); ?></span></h4>
					<p><?php echo esc_html( $conflict['message'] ?? '' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ikon_seo_resolve_discovery_conflict">
						<input type="hidden" name="conflict_id" value="<?php echo esc_attr( $conflict['id'] ?? '' ); ?>">
						<input type="hidden" name="generated_at" value="<?php echo esc_attr( $generated ); ?>">
						<?php wp_nonce_field( 'ikon_seo_resolve_discovery_conflict' ); ?>
						<p><select name="selected_value"><option value=""><?php esc_html_e( 'Choose the correct detected value', 'ikon-seo' ); ?></option><?php foreach ( (array) ( $conflict['values'] ?? array() ) as $value ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $conflict['selected_value'] ?? '', $value ); ?>><?php echo esc_html( $value ); ?></option><?php endforeach; ?><option value="multiple_valid" <?php selected( $conflict['selected_value'] ?? '', 'multiple_valid' ); ?>><?php esc_html_e( 'Multiple values are valid', 'ikon-seo' ); ?></option></select></p>
						<p><input class="regular-text" type="text" name="custom_value" value="<?php echo esc_attr( $conflict['custom_value'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Or enter the correct value', 'ikon-seo' ); ?>"></p>
						<button class="button" type="submit"><?php esc_html_e( 'Resolve conflict', 'ikon-seo' ); ?></button>
					</form>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php $section_labels = array( 'business_identity' => __( 'Business identity', 'ikon-seo' ), 'website_model' => __( 'Website model', 'ikon-seo' ), 'services_locations' => __( 'Services and locations', 'ikon-seo' ), 'audience' => __( 'Target audience', 'ikon-seo' ), 'conversions_goals' => __( 'Conversions and goals', 'ikon-seo' ), 'claims_governance' => __( 'Claims and quality governance', 'ikon-seo' ) ); ?>
		<?php foreach ( $sections as $section => $facts ) : ?>
			<h3><?php echo esc_html( $section_labels[ $section ] ?? ucwords( str_replace( '_', ' ', $section ) ) ); ?></h3>
			<table class="widefat striped ikon-seo-log">
				<thead><tr><th><?php esc_html_e( 'Field', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Detected value and evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Current decision', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Review', 'ikon-seo' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( (array) $facts as $fact ) :
					$approved = $fact['approved_value'] ?? '';
					$approved_text = is_array( $approved ) ? implode( "\n", $approved ) : (string) $approved;
					$status = sanitize_key( $fact['status'] ?? 'detected' );
				?>
					<tr>
						<td><strong><?php echo esc_html( $fact['label'] ?? '' ); ?></strong><br><code><?php echo esc_html( $fact['id'] ?? '' ); ?></code></td>
						<td><?php echo nl2br( esc_html( $fact['display_value'] ?? '' ) ); ?><br><small><?php echo esc_html( implode( ' · ', (array) ( $fact['sources'] ?? array() ) ) ); ?></small><br><span class="ikon-seo-pill"><?php echo esc_html( ucfirst( $fact['confidence'] ?? 'low' ) . ' · ' . absint( $fact['score'] ?? 0 ) . '/100' ); ?></span></td>
						<td><span class="ikon-seo-pill <?php echo in_array( $status, array( 'confirmed', 'edited' ), true ) ? 'is-connected' : ( 'outdated' === $status ? 'is-failed' : '' ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $status ) ) ); ?></span><?php if ( $approved_text ) : ?><br><small><?php echo nl2br( esc_html( $approved_text ) ); ?></small><?php endif; ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="ikon_seo_update_discovery_fact">
								<input type="hidden" name="fact_id" value="<?php echo esc_attr( $fact['id'] ?? '' ); ?>">
								<input type="hidden" name="generated_at" value="<?php echo esc_attr( $generated ); ?>">
								<?php wp_nonce_field( 'ikon_seo_update_discovery_fact' ); ?>
								<select name="status"><?php foreach ( $status_labels as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
								<textarea class="large-text" rows="2" name="value" placeholder="<?php esc_attr_e( 'Corrected value, required only for Edited by user', 'ikon-seo' ); ?>"><?php echo esc_textarea( 'edited' === $status ? $approved_text : '' ); ?></textarea>
								<button class="button" type="submit"><?php esc_html_e( 'Save decision', 'ikon-seo' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endforeach; ?>

		<div class="ikon-seo-card">
			<h3><?php esc_html_e( 'Apply reviewed facts', 'ikon-seo' ); ?></h3>
			<p><?php esc_html_e( 'Only facts marked Confirmed or Edited by user will be applied. Rejected, uncertain and outdated facts remain excluded.', 'ikon-seo' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_apply_confirmed_discovery_facts">
				<?php wp_nonce_field( 'ikon_seo_apply_confirmed_discovery_facts' ); ?>
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Apply Confirmed Values', 'ikon-seo' ); ?></button>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=guided-launch' ) ); ?>"><?php esc_html_e( 'Open Guided Launch', 'ikon-seo' ); ?></a>
			</form>
		</div>
		<?php
	}

	private function render_guided_launch() {
		$report   = $this->guided_launch->report();
		$stages   = (array) ( $report['stages'] ?? array() );
		$actions  = (array) ( $report['next_actions'] ?? array() );
		$last_run = (array) ( $report['last_run'] ?? array() );
		$score    = absint( $report['score'] ?? 0 );
		$status   = sanitize_key( $report['status'] ?? 'setup_required' );
		$discovery = (array) ( $this->auto_discovery->report() );
		$conflicts = (array) ( $discovery['conflicts'] ?? array() );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Guided Launch & Strategy Activation', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Turn confirmed discovery evidence into a mode-specific workflow, bounded read-only audits and the initial approval-controlled Operating Plan.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo 100 === $score ? 'is-connected' : ( $score < 40 ? 'is-failed' : '' ); ?>"><?php echo esc_html( $score . '/100 activated' ); ?></span>
		</div>

		<div class="notice notice-info inline">
			<p><strong><?php esc_html_e( 'Safe activation:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'This process can create an internal workflow, run read-only evidence tasks and refresh recommendations. It cannot publish, edit live pages, create redirects, change canonicals or indexation, update Business Profile information or perform outreach.', 'ikon-seo' ); ?></p>
		</div>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo esc_html( $score . '%' ); ?></strong><span><?php esc_html_e( 'Launch progress', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $report['strategy']['readiness_score'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Strategy readiness', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $report['workflow']['completed_tasks'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Safe tasks completed', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $report['operating_plan']['recommendations'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Plan recommendations', 'ikon-seo' ); ?></span></div>
		</div>

		<h3><?php esc_html_e( 'Activation stages', 'ikon-seo' ); ?></h3>
		<div class="ikon-seo-grid ikon-seo-grid-3">
			<?php foreach ( $stages as $index => $stage ) : ?>
				<div class="ikon-seo-card">
					<p class="ikon-seo-kicker"><?php echo esc_html( sprintf( 'STAGE %d', $index + 1 ) ); ?></p>
					<h3><?php echo esc_html( $stage['label'] ); ?></h3>
					<p><?php echo esc_html( $stage['description'] ); ?></p>
					<p><span class="ikon-seo-pill <?php echo ! empty( $stage['complete'] ) ? 'is-connected' : 'is-failed'; ?>"><?php echo ! empty( $stage['complete'] ) ? esc_html__( 'Complete', 'ikon-seo' ) : esc_html__( 'Action required', 'ikon-seo' ); ?></span></p>
					<a class="button" href="<?php echo esc_url( $stage['url'] ); ?>"><?php esc_html_e( 'Review stage', 'ikon-seo' ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( empty( $discovery['generated_at'] ) ) : ?>
			<div class="ikon-seo-next-step">
				<div><strong><?php esc_html_e( 'Start with website research', 'ikon-seo' ); ?></strong><p><?php esc_html_e( 'Guided Launch requires an Auto Discovery report so it can preserve uncertain business decisions for confirmation.', 'ikon-seo' ); ?></p></div>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=auto-discovery' ) ); ?>"><?php esc_html_e( 'Open Auto Discovery', 'ikon-seo' ); ?></a>
			</div>
		<?php else : ?>
			<div class="ikon-seo-two-columns">
				<section class="ikon-seo-card">
					<h3><?php esc_html_e( 'Activate the confirmed strategy', 'ikon-seo' ); ?></h3>
					<p><?php esc_html_e( 'Run the onboarding handoff in a controlled batch. The Operating Plan uses evidence currently available in Ikon SEO and does not refresh external sources during this action.', 'ikon-seo' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ikon_seo_run_guided_launch">
						<?php wp_nonce_field( 'ikon_seo_run_guided_launch' ); ?>
						<p><label><input type="checkbox" name="create_workflow" value="1" checked> <?php esc_html_e( 'Create the recommended workflow if none exists', 'ikon-seo' ); ?></label></p>
						<p><label><input type="checkbox" name="run_safe_tasks" value="1" checked> <?php esc_html_e( 'Run an initial read-only task batch', 'ikon-seo' ); ?></label></p>
						<p><label><?php esc_html_e( 'Safe tasks in this batch', 'ikon-seo' ); ?> <input type="number" min="1" max="5" name="task_batch" value="3"></label></p>
						<p><label><input type="checkbox" name="build_plan" value="1" checked> <?php esc_html_e( 'Build or refresh the initial Operating Plan', 'ikon-seo' ); ?></label></p>
						<?php submit_button( __( 'Start Safe Strategy Activation', 'ikon-seo' ), 'primary' ); ?>
					</form>
				</section>

				<section class="ikon-seo-card">
					<h3><?php esc_html_e( 'Latest activation run', 'ikon-seo' ); ?></h3>
					<?php if ( empty( $last_run['run_at'] ) ) : ?>
						<p><?php esc_html_e( 'No activation run has been recorded yet.', 'ikon-seo' ); ?></p>
					<?php else : ?>
						<p><strong><?php echo esc_html( $last_run['run_at'] . ' UTC' ); ?></strong></p>
						<ul>
							<li><?php echo esc_html( sprintf( 'Safe tasks processed: %d', absint( $last_run['safe_tasks_processed'] ?? 0 ) ) ); ?></li>
							<li><?php echo esc_html( sprintf( 'Operating Plan items generated: %d', absint( $last_run['plan_items_generated'] ?? 0 ) ) ); ?></li>
						</ul>
						<?php if ( ! empty( $last_run['errors'] ) ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html( implode( ' ', (array) $last_run['errors'] ) ); ?></p></div><?php endif; ?>
					<?php endif; ?>
					<p><span class="ikon-seo-pill <?php echo 'activated' === $status ? 'is-connected' : ''; ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $status ) ) ); ?></span></p>
				</section>
			</div>
		<?php endif; ?>

		<?php if ( $conflicts ) : ?>
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Discovery conflicts', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Resolve these in the Website Profile or explicitly acknowledge that they were reviewed. Acknowledgement does not select a value or change the website.', 'ikon-seo' ); ?></p>
				<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Area', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Issue', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Detected values', 'ikon-seo' ); ?></th></tr></thead><tbody>
				<?php foreach ( $conflicts as $conflict ) : ?><tr><td><strong><?php echo esc_html( $conflict['area'] ?? '' ); ?></strong></td><td><?php echo esc_html( $conflict['message'] ?? '' ); ?></td><td><?php echo esc_html( implode( ', ', (array) ( $conflict['values'] ?? array() ) ) ); ?></td></tr><?php endforeach; ?>
				</tbody></table>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px">
					<input type="hidden" name="action" value="ikon_seo_acknowledge_discovery_conflicts">
					<?php wp_nonce_field( 'ikon_seo_acknowledge_discovery_conflicts' ); ?>
					<label><input type="checkbox" name="acknowledged" value="1" <?php checked( ! empty( $report['conflicts_acknowledged'] ) ); ?>> <?php esc_html_e( 'I reviewed these conflicts and confirmed the correct values separately', 'ikon-seo' ); ?></label>
					<?php submit_button( __( 'Save Conflict Review', 'ikon-seo' ), 'secondary' ); ?>
				</form>
			</section>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Next five actions', 'ikon-seo' ); ?></h3>
		<div class="ikon-seo-grid ikon-seo-grid-3">
			<?php if ( ! $actions ) : ?><div class="ikon-seo-card"><h3><?php esc_html_e( 'Launch complete', 'ikon-seo' ); ?></h3><p><?php esc_html_e( 'Review the active workflow and Operating Plan for approval-controlled execution.', 'ikon-seo' ); ?></p></div><?php endif; ?>
			<?php foreach ( $actions as $index => $action ) : ?>
				<div class="ikon-seo-card">
					<p class="ikon-seo-kicker"><?php echo esc_html( sprintf( 'ACTION %d', $index + 1 ) ); ?></p>
					<h3><?php echo esc_html( $action['title'] ); ?></h3>
					<p><?php echo esc_html( $action['reason'] ); ?></p>
					<a class="button" href="<?php echo esc_url( $action['url'] ); ?>"><?php esc_html_e( 'Open action', 'ikon-seo' ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private function render_strategy() {
		$strategy = $this->strategy->get();
		$agency   = Ikon_SEO_Agency::can_manage();
		$modes    = $this->strategy->modes();
		$goals    = $this->strategy->goals();
		$readiness = (array) ( $strategy['readiness'] ?? array() );
		$level = sanitize_key( $readiness['level'] ?? 'incomplete' );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Website Strategy and Operating Mode', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Define what this website is, who it serves, how it creates value, what success means and which safeguards every audit and draft must follow.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo 'ready' === $level ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( absint( $readiness['score'] ?? 0 ) . '/100 strategy readiness' ); ?></span>
		</div>

		<?php if ( $agency ) : ?>
		<div class="ikon-seo-next-step">
			<div><strong><?php esc_html_e( 'Prefer automatic setup?', 'ikon-seo' ); ?></strong><p><?php esc_html_e( 'Scan the website, review detected facts and apply selected strategy suggestions instead of completing every field manually.', 'ikon-seo' ); ?></p></div>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=auto-discovery' ) ); ?>"><?php esc_html_e( 'Open Auto Discovery', 'ikon-seo' ); ?></a>
		</div>
		<?php endif; ?>

		<div class="notice notice-info inline"><p><?php esc_html_e( 'Strategy readiness is a setup and governance measure—not a ranking score. Published pages, redirects, canonicals and external actions still require explicit approval.', 'ikon-seo' ); ?></p></div>

		<div class="ikon-seo-grid ikon-seo-grid-4">
			<div class="ikon-seo-card"><span class="ikon-seo-label"><?php esc_html_e( 'Operating mode', 'ikon-seo' ); ?></span><strong><?php echo esc_html( $strategy['mode_label'] ); ?></strong><p><?php echo esc_html( $strategy['mode_description'] ); ?></p></div>
			<div class="ikon-seo-card"><span class="ikon-seo-label"><?php esc_html_e( 'Primary goal', 'ikon-seo' ); ?></span><strong><?php echo esc_html( $strategy['primary_goal_label'] ); ?></strong><p><?php echo esc_html( sprintf( __( '%d planned content items per month', 'ikon-seo' ), absint( $strategy['publishing_capacity'] ) ) ); ?></p></div>
			<div class="ikon-seo-card"><span class="ikon-seo-label"><?php esc_html_e( 'Quality gate', 'ikon-seo' ); ?></span><strong><?php echo esc_html( absint( $strategy['quality_gate_threshold'] ) . '/100' ); ?></strong><p><?php esc_html_e( 'Failed items return for revision and are not published.', 'ikon-seo' ); ?></p></div>
			<div class="ikon-seo-card"><span class="ikon-seo-label"><?php esc_html_e( 'Automation policy', 'ikon-seo' ); ?></span><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $strategy['automation_level'] ) ) ); ?></strong><p><?php echo esc_html( ucfirst( $strategy['risk_tolerance'] ) . ' risk policy' ); ?></p></div>
		</div>

		<?php if ( $agency ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_save_strategy">
			<?php wp_nonce_field( 'ikon_seo_save_strategy' ); ?>

			<h3><?php esc_html_e( '1. Website purpose and positioning', 'ikon-seo' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr><th><label for="website_mode"><?php esc_html_e( 'Operating mode', 'ikon-seo' ); ?></label></th><td><select id="website_mode" name="website_mode"><?php foreach ( $modes as $key => $mode ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $strategy['mode'], $key ); ?>><?php echo esc_html( $mode['label'] ); ?></option><?php endforeach; ?></select><p class="description"><?php esc_html_e( 'This changes priority logic and required evidence; it does not change published content automatically.', 'ikon-seo' ); ?></p></td></tr>
				<tr><th><label for="strategy_primary_goal"><?php esc_html_e( 'Primary business goal', 'ikon-seo' ); ?></label></th><td><select id="strategy_primary_goal" name="strategy_primary_goal"><?php foreach ( $goals as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $strategy['primary_goal'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
				<tr><th><label for="strategy_secondary_goals"><?php esc_html_e( 'Secondary goals', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="strategy_secondary_goals" name="strategy_secondary_goals"><?php echo esc_textarea( implode( "\n", $strategy['secondary_goals'] ) ); ?></textarea><p class="description"><?php esc_html_e( 'One secondary goal per line.', 'ikon-seo' ); ?></p></td></tr>
				<tr><th><label for="strategy_target_audience"><?php esc_html_e( 'Target audience', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_target_audience" name="strategy_target_audience"><?php echo esc_textarea( $strategy['target_audience'] ); ?></textarea><p class="description"><?php esc_html_e( 'Describe who they are, what they need and the decision they are trying to make.', 'ikon-seo' ); ?></p></td></tr>
				<tr><th><label for="strategy_value_proposition"><?php esc_html_e( 'Distinct value proposition', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_value_proposition" name="strategy_value_proposition"><?php echo esc_textarea( $strategy['value_proposition'] ); ?></textarea></td></tr>
				<tr><th><label for="strategy_main_offerings"><?php esc_html_e( 'Priority services, products or topics', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="5" id="strategy_main_offerings" name="strategy_main_offerings"><?php echo esc_textarea( implode( "\n", $strategy['main_offerings'] ) ); ?></textarea><p class="description"><?php esc_html_e( 'One strategic priority per line. These influence business-value prioritisation.', 'ikon-seo' ); ?></p></td></tr>
				<tr><th><label for="strategy_excluded_topics"><?php esc_html_e( 'Excluded or non-priority topics', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="strategy_excluded_topics" name="strategy_excluded_topics"><?php echo esc_textarea( implode( "\n", $strategy['excluded_topics'] ) ); ?></textarea></td></tr>
			</table>

			<h3><?php esc_html_e( '2. Conversion, capacity and measurement', 'ikon-seo' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr><th><label for="strategy_primary_conversions"><?php esc_html_e( 'Primary conversion actions', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_primary_conversions" name="strategy_primary_conversions"><?php echo esc_textarea( implode( "\n", $strategy['primary_conversions'] ) ); ?></textarea><p class="description"><?php esc_html_e( 'Use measurable actions such as qualified form, call, booking, purchase or affiliate click.', 'ikon-seo' ); ?></p></td></tr>
				<tr><th><label for="strategy_monetization_model"><?php esc_html_e( 'Monetisation model', 'ikon-seo' ); ?></label></th><td><select id="strategy_monetization_model" name="strategy_monetization_model"><?php foreach ( array( 'service_revenue'=>'Service revenue', 'lead_generation'=>'Lead generation', 'ecommerce'=>'Ecommerce', 'affiliate'=>'Affiliate', 'advertising'=>'Advertising', 'subscription'=>'Subscription', 'sponsorship'=>'Sponsorship', 'mixed'=>'Mixed', 'none'=>'No direct monetisation' ) as $key=>$label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $strategy['monetization_model'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
				<tr><th><label for="strategy_publishing_capacity"><?php esc_html_e( 'Realistic monthly capacity', 'ikon-seo' ); ?></label></th><td><input type="number" min="0" max="200" id="strategy_publishing_capacity" name="strategy_publishing_capacity" value="<?php echo esc_attr( $strategy['publishing_capacity'] ); ?>"> <span class="description"><?php esc_html_e( 'New or substantially refreshed items per month.', 'ikon-seo' ); ?></span></td></tr>
				<tr><th><label for="strategy_success_metrics"><?php esc_html_e( 'Success metrics', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_success_metrics" name="strategy_success_metrics"><?php echo esc_textarea( implode( "\n", $strategy['success_metrics'] ) ); ?></textarea></td></tr>
				<tr><th><label for="strategy_content_owner"><?php esc_html_e( 'Content owner', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="strategy_content_owner" name="strategy_content_owner" value="<?php echo esc_attr( $strategy['content_owner'] ); ?>"></td></tr>
				<tr><th><label for="strategy_review_owner"><?php esc_html_e( 'Review and approval owner', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="strategy_review_owner" name="strategy_review_owner" value="<?php echo esc_attr( $strategy['review_owner'] ); ?>"></td></tr>
			</table>

			<h3><?php esc_html_e( '3. Quality, evidence and automation policy', 'ikon-seo' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr><th><label for="strategy_editorial_standards"><?php esc_html_e( 'Editorial standards', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="5" id="strategy_editorial_standards" name="strategy_editorial_standards"><?php echo esc_textarea( $strategy['editorial_standards'] ); ?></textarea></td></tr>
				<tr><th><label for="strategy_evidence_requirements"><?php esc_html_e( 'Evidence requirements', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="5" id="strategy_evidence_requirements" name="strategy_evidence_requirements"><?php echo esc_textarea( $strategy['evidence_requirements'] ); ?></textarea><p class="description"><?php esc_html_e( 'Define when sources, real examples, photos, credentials or client confirmation are required.', 'ikon-seo' ); ?></p></td></tr>
				<tr><th><label for="strategy_author_policy"><?php esc_html_e( 'Authorship and reviewer policy', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_author_policy" name="strategy_author_policy"><?php echo esc_textarea( $strategy['author_policy'] ); ?></textarea></td></tr>
				<tr><th><label for="strategy_disclosure_policy"><?php esc_html_e( 'Advertising, affiliate and sponsorship disclosure policy', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_disclosure_policy" name="strategy_disclosure_policy"><?php echo esc_textarea( $strategy['disclosure_policy'] ); ?></textarea></td></tr>
				<tr><th><label for="strategy_quality_gate_threshold"><?php esc_html_e( 'Quality gate threshold', 'ikon-seo' ); ?></label></th><td><input type="number" min="50" max="100" id="strategy_quality_gate_threshold" name="strategy_quality_gate_threshold" value="<?php echo esc_attr( $strategy['quality_gate_threshold'] ); ?>"> /100</td></tr>
				<tr><th><label for="strategy_automation_level"><?php esc_html_e( 'Automation level', 'ikon-seo' ); ?></label></th><td><select id="strategy_automation_level" name="strategy_automation_level"><option value="audit_only" <?php selected( $strategy['automation_level'], 'audit_only' ); ?>>Audit and recommendations only</option><option value="drafts_only" <?php selected( $strategy['automation_level'], 'drafts_only' ); ?>>Audits plus review drafts</option><option value="controlled_changes" <?php selected( $strategy['automation_level'], 'controlled_changes' ); ?>>Controlled changes with approvals</option></select></td></tr>
				<tr><th><label for="strategy_risk_tolerance"><?php esc_html_e( 'Risk policy', 'ikon-seo' ); ?></label></th><td><select id="strategy_risk_tolerance" name="strategy_risk_tolerance"><option value="conservative" <?php selected( $strategy['risk_tolerance'], 'conservative' ); ?>>Conservative</option><option value="balanced" <?php selected( $strategy['risk_tolerance'], 'balanced' ); ?>>Balanced</option><option value="growth" <?php selected( $strategy['risk_tolerance'], 'growth' ); ?>>Growth-oriented</option></select></td></tr>
			</table>

			<h3><?php esc_html_e( '4. Local Business mode settings', 'ikon-seo' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr><th><label for="strategy_local_lead_channels"><?php esc_html_e( 'Lead channels', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="strategy_local_lead_channels" name="strategy_local_lead_channels"><?php echo esc_textarea( implode( "\n", $strategy['local']['lead_channels'] ) ); ?></textarea></td></tr>
				<tr><th><label for="strategy_local_review_target"><?php esc_html_e( 'Monthly verified review target', 'ikon-seo' ); ?></label></th><td><input type="number" min="0" max="500" id="strategy_local_review_target" name="strategy_local_review_target" value="<?php echo esc_attr( $strategy['local']['review_target_monthly'] ); ?>"></td></tr>
				<tr><th><label for="strategy_local_service_area_policy"><?php esc_html_e( 'Service-area policy', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_local_service_area_policy" name="strategy_local_service_area_policy"><?php echo esc_textarea( $strategy['local']['service_area_policy'] ); ?></textarea></td></tr>
				<tr><th><label for="strategy_local_proof_requirements"><?php esc_html_e( 'Local proof requirements', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_local_proof_requirements" name="strategy_local_proof_requirements"><?php echo esc_textarea( $strategy['local']['proof_requirements'] ); ?></textarea></td></tr>
			</table>

			<h3><?php esc_html_e( '5. Editorial / Blog mode settings', 'ikon-seo' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr><th><label for="strategy_editorial_primary_topics"><?php esc_html_e( 'Primary editorial topics', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_editorial_primary_topics" name="strategy_editorial_primary_topics"><?php echo esc_textarea( implode( "\n", $strategy['editorial']['primary_topics'] ) ); ?></textarea></td></tr>
				<tr><th><label for="strategy_editorial_hubs"><?php esc_html_e( 'Planned content hubs', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_editorial_hubs" name="strategy_editorial_hubs"><?php echo esc_textarea( implode( "\n", $strategy['editorial']['content_hubs'] ) ); ?></textarea></td></tr>
				<tr><th><label for="strategy_editorial_refresh_days"><?php esc_html_e( 'Default refresh cycle', 'ikon-seo' ); ?></label></th><td><input type="number" min="30" max="730" id="strategy_editorial_refresh_days" name="strategy_editorial_refresh_days" value="<?php echo esc_attr( $strategy['editorial']['refresh_cycle_days'] ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td></tr>
				<tr><th><label for="strategy_editorial_originality"><?php esc_html_e( 'Originality standard', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_editorial_originality" name="strategy_editorial_originality"><?php echo esc_textarea( $strategy['editorial']['originality_standard'] ); ?></textarea></td></tr>
			</table>

			<h3><?php esc_html_e( '6. Ecommerce mode foundation', 'ikon-seo' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr><th><label for="strategy_ecommerce_categories"><?php esc_html_e( 'Priority product categories', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_ecommerce_categories" name="strategy_ecommerce_categories"><?php echo esc_textarea( implode( "\n", $strategy['ecommerce']['primary_categories'] ) ); ?></textarea></td></tr>
				<tr><th><label for="strategy_ecommerce_conversion_events"><?php esc_html_e( 'Commerce conversion events', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="strategy_ecommerce_conversion_events" name="strategy_ecommerce_conversion_events"><?php echo esc_textarea( implode( "\n", $strategy['ecommerce']['conversion_events'] ) ); ?></textarea></td></tr>
				<tr><th><label for="strategy_ecommerce_trust_requirements"><?php esc_html_e( 'Commerce trust requirements', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_ecommerce_trust_requirements" name="strategy_ecommerce_trust_requirements"><?php echo esc_textarea( $strategy['ecommerce']['trust_requirements'] ); ?></textarea></td></tr>
				<tr><th><label for="strategy_ecommerce_feed_policy"><?php esc_html_e( 'Product-feed and data policy', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="strategy_ecommerce_feed_policy" name="strategy_ecommerce_feed_policy"><?php echo esc_textarea( $strategy['ecommerce']['feed_policy'] ); ?></textarea></td></tr>
			</table>

			<?php submit_button( __( 'Save Website Strategy', 'ikon-seo' ), 'primary' ); ?>
		</form>
		<?php else : ?>
			<div class="ikon-seo-card"><p><?php esc_html_e( 'Strategy editing is restricted to approved Agency administrators. Clients can review the active goals, standards and gaps here.', 'ikon-seo' ); ?></p></div>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Strategy gaps', 'ikon-seo' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Impact', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Area', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Gap', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recommended action', 'ikon-seo' ); ?></th></tr></thead>
			<tbody><?php if ( ! empty( $readiness['gaps'] ) ) : foreach ( $readiness['gaps'] as $gap ) : ?><tr><td><strong><?php echo esc_html( ucfirst( $gap['impact'] ) ); ?></strong></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $gap['area'] ) ) ); ?></td><td><?php echo esc_html( $gap['message'] ); ?></td><td><?php echo esc_html( $gap['action'] ); ?></td></tr><?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e( 'No material strategy setup gap was detected.', 'ikon-seo' ); ?></td></tr><?php endif; ?></tbody>
		</table>

		<div class="ikon-seo-grid ikon-seo-grid-2">
			<section class="ikon-seo-card"><h3><?php esc_html_e( 'Mode priorities', 'ikon-seo' ); ?></h3><?php foreach ( (array) $strategy['mode_priorities'] as $item ) : ?><p>• <?php echo esc_html( $item ); ?></p><?php endforeach; ?></section>
			<section class="ikon-seo-card"><h3><?php esc_html_e( 'Recommended workflow', 'ikon-seo' ); ?></h3><?php foreach ( (array) $strategy['recommended_workflow'] as $item ) : ?><p>• <?php echo esc_html( $item['action'] ); ?></p><?php endforeach; ?></section>
		</div>
		<?php
	}

	private function render_profile( array $settings ) {
		$profile    = $this->profile->get();
		$industries = $this->profile->industries();
		$entities   = $this->profile->entity_types();
		$allowed    = $this->profile->allowed_entity_types( $settings['industry'] );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Website setup and identity', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'This profile controls business facts, language, design defaults and which schema types are permitted on this installation.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo $profile['configured'] ? 'is-connected' : 'is-failed'; ?>">
				<?php echo esc_html( $profile['configured'] ? 'Profile ready' : 'Setup required' ); ?>
			</span>
		</div>

		<div class="ikon-seo-profile-id">
			<strong><?php esc_html_e( 'Current profile ID:', 'ikon-seo' ); ?></strong>
			<code><?php echo esc_html( $profile['profile_id'] ); ?></code>
			<p class="description"><?php esc_html_e( 'Connected workflows read this ID before every write. Identity changes invalidate the previous ID and connection key.', 'ikon-seo' ); ?></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_save_profile">
			<?php wp_nonce_field( 'ikon_seo_save_profile' ); ?>

			<h3><?php esc_html_e( '1. Business identity', 'ikon-seo' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="site_name"><?php esc_html_e( 'Business name', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" required id="site_name" name="site_name" value="<?php echo esc_attr( $settings['site_name'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="business_url"><?php esc_html_e( 'Canonical business URL', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" required type="url" id="business_url" name="business_url" value="<?php echo esc_attr( $settings['business_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="industry"><?php esc_html_e( 'Website industry', 'ikon-seo' ); ?></label></th>
					<td>
						<select id="industry" name="industry" data-ikon-industry>
							<?php foreach ( $industries as $key => $industry ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" data-recommended="<?php echo esc_attr( $industry['recommended'] ); ?>" <?php selected( $settings['industry'], $key ); ?>><?php echo esc_html( $industry['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="business_entity_type"><?php esc_html_e( 'Schema business entity', 'ikon-seo' ); ?></label></th>
					<td>
						<select id="business_entity_type" name="business_entity_type" data-ikon-entity>
							<?php foreach ( $entities as $type => $entity ) : ?>
								<?php
								$entity_industries = array();
								foreach ( array_keys( $industries ) as $industry_key ) {
									if ( in_array( $type, $this->profile->allowed_entity_types( $industry_key ), true ) ) {
										$entity_industries[] = $industry_key;
									}
								}
								?>
								<option value="<?php echo esc_attr( $type ); ?>" data-industries="<?php echo esc_attr( implode( ',', $entity_industries ) ); ?>" <?php selected( $settings['business_entity_type'], $type ); ?>><?php echo esc_html( $entity['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html( 'Currently allowed: ' . implode( ', ', $allowed ) . '. The connected workflow cannot override this selection.' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="target_market"><?php esc_html_e( 'Target market', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" id="target_market" name="target_market" value="<?php echo esc_attr( $settings['target_market'] ); ?>" placeholder="Country, region or audience"></td>
				</tr>
				<tr>
					<th scope="row"><label for="target_locations"><?php esc_html_e( 'Target locations', 'ikon-seo' ); ?></label></th>
					<td><textarea class="large-text" rows="4" id="target_locations" name="target_locations"><?php echo esc_textarea( $settings['target_locations'] ); ?></textarea><p class="description"><?php esc_html_e( 'One genuine service location or service area per line.', 'ikon-seo' ); ?></p></td>
				</tr>
			</table>

			<h3><?php esc_html_e( '2. Language, currency and contact details', 'ikon-seo' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="default_language"><?php esc_html_e( 'Default language', 'ikon-seo' ); ?></label></th>
					<td><input required id="default_language" name="default_language" value="<?php echo esc_attr( $settings['default_language'] ); ?>" pattern="[a-z]{2,3}(-[A-Z]{2})?"> <span class="description">en, en-AE, ur-PK</span></td>
				</tr>
				<tr>
					<th scope="row"><label for="supported_languages"><?php esc_html_e( 'Supported languages', 'ikon-seo' ); ?></label></th>
					<td><textarea class="large-text code" rows="3" id="supported_languages" name="supported_languages"><?php echo esc_textarea( $settings['supported_languages'] ); ?></textarea><p class="description"><?php esc_html_e( 'One language code per line. Page writes in other languages are rejected.', 'ikon-seo' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="default_currency"><?php esc_html_e( 'Default currency', 'ikon-seo' ); ?></label></th>
					<td><input required maxlength="3" size="5" id="default_currency" name="default_currency" value="<?php echo esc_attr( $settings['default_currency'] ); ?>"> <span class="description">AED, USD, CAD, QAR</span></td>
				</tr>
				<tr>
					<th scope="row"><label for="business_phone"><?php esc_html_e( 'Business phone', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" id="business_phone" name="business_phone" value="<?php echo esc_attr( $settings['business_phone'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="contact_email"><?php esc_html_e( 'Public contact email', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" type="email" id="contact_email" name="contact_email" value="<?php echo esc_attr( $settings['contact_email'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="whatsapp_url"><?php esc_html_e( 'WhatsApp URL', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" type="url" id="whatsapp_url" name="whatsapp_url" value="<?php echo esc_attr( $settings['whatsapp_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="business_logo"><?php esc_html_e( 'Business logo URL', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" type="url" id="business_logo" name="business_logo" value="<?php echo esc_attr( $settings['business_logo'] ); ?>"></td>
				</tr>
			</table>

			<h3><?php esc_html_e( '3. Builder, SEO and design rules', 'ikon-seo' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="builder_preference"><?php esc_html_e( 'Page builder', 'ikon-seo' ); ?></label></th>
					<td>
						<select id="builder_preference" name="builder_preference">
							<option value="auto" <?php selected( $settings['builder_preference'], 'auto' ); ?>>Auto-detect</option>
							<option value="elementor" <?php selected( $settings['builder_preference'], 'elementor' ); ?>>Elementor</option>
							<option value="gutenberg" <?php selected( $settings['builder_preference'], 'gutenberg' ); ?>>Gutenberg</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="seo_plugin_preference"><?php esc_html_e( 'SEO plugin', 'ikon-seo' ); ?></label></th>
					<td>
						<select id="seo_plugin_preference" name="seo_plugin_preference">
							<option value="auto" <?php selected( $settings['seo_plugin_preference'], 'auto' ); ?>>Auto-detect</option>
							<option value="rank_math" <?php selected( $settings['seo_plugin_preference'], 'rank_math' ); ?>>Rank Math</option>
							<option value="yoast" <?php selected( $settings['seo_plugin_preference'], 'yoast' ); ?>>Yoast</option>
							<option value="none" <?php selected( $settings['seo_plugin_preference'], 'none' ); ?>>None</option>
						</select>
					</td>
				</tr>
				<?php foreach ( array(
					'primary_color'   => 'Primary colour',
					'secondary_color' => 'Secondary colour',
					'accent_color'    => 'Accent colour',
					'heading_color'   => 'Heading colour',
					'text_color'      => 'Text colour',
					'surface_color'   => 'Section background',
				) as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input type="color" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $settings[ $key ] ); ?>"></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="content_width"><?php esc_html_e( 'Content width', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="content_width" name="content_width" min="800" max="1600" value="<?php echo esc_attr( $settings['content_width'] ); ?>"> px</td>
				</tr>
				<tr>
					<th scope="row"><label for="content_rules"><?php esc_html_e( 'Website-specific content rules', 'ikon-seo' ); ?></label></th>
					<td><textarea class="large-text" rows="5" id="content_rules" name="content_rules"><?php echo esc_textarea( $settings['content_rules'] ); ?></textarea><p class="description"><?php esc_html_e( 'Record verified claims, wording constraints, prohibited claims, preferred terminology and compliance requirements.', 'ikon-seo' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="cta_templates"><?php esc_html_e( 'Approved CTA templates', 'ikon-seo' ); ?></label></th>
					<td><textarea class="large-text" rows="4" id="cta_templates" name="cta_templates"><?php echo esc_textarea( $settings['cta_templates'] ); ?></textarea></td>
				</tr>
			</table>

			<h3><?php echo esc_html( '4. Verified entity and ' . $settings['business_entity_type'] . ' schema' ); ?></h3>
			<p class="description"><?php esc_html_e( 'A local-business subtype is emitted only when the office details are accurate, publicly verifiable and explicitly enabled.', 'ikon-seo' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Entity schema', 'ikon-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="allow_entity_schema" value="1" <?php checked( $settings['allow_entity_schema'] ); ?>> <?php esc_html_e( 'Allow the active business entity on explicitly approved pages', 'ikon-seo' ); ?></label><br>
						<label><input type="checkbox" name="verified_business" value="1" <?php checked( $settings['verified_business'] ); ?>> <?php esc_html_e( 'The business and office details below are accurate and publicly verifiable', 'ikon-seo' ); ?></label>
					</td>
				</tr>
				<?php foreach ( array(
					'address_street'   => 'Street address',
					'address_locality' => 'Locality / city',
					'address_region'   => 'Region',
					'address_postal'   => 'Postal code',
					'address_country'  => 'Country code',
					'latitude'         => 'Latitude',
					'longitude'        => 'Longitude',
					'price_range'      => 'Price range',
				) as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input class="regular-text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $settings[ $key ] ); ?>"></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="opening_hours"><?php esc_html_e( 'Opening hours', 'ikon-seo' ); ?></label></th>
					<td><textarea class="large-text code" rows="3" id="opening_hours" name="opening_hours"><?php echo esc_textarea( $settings['opening_hours'] ); ?></textarea><p class="description">Mo-Fr 09:00-18:00</p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Semantic FAQ markup', 'ikon-seo' ); ?></th>
					<td><label><input type="checkbox" name="semantic_faq" value="1" <?php checked( $settings['semantic_faq'] ); ?>> <?php esc_html_e( 'Allow FAQPage only when matching FAQs are visible', 'ikon-seo' ); ?></label><p class="description"><?php esc_html_e( 'This does not promise a Google FAQ rich result.', 'ikon-seo' ); ?></p></td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Website Profile', 'ikon-seo' ) ); ?>
		</form>

		<hr>
		<h3><?php esc_html_e( 'Portable profile', 'ikon-seo' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Export reusable business, schema and design rules. Connection keys, logs and page content are excluded.', 'ikon-seo' ); ?></p>
		<div class="ikon-seo-actions">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_export_profile">
				<?php wp_nonce_field( 'ikon_seo_export_profile' ); ?>
				<button type="submit" class="button"><?php esc_html_e( 'Export profile JSON', 'ikon-seo' ); ?></button>
			</form>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_import_profile">
				<?php wp_nonce_field( 'ikon_seo_import_profile' ); ?>
				<input required type="file" name="profile_file" accept="application/json,.json">
				<button type="submit" class="button" data-confirm="<?php esc_attr_e( 'Import this profile? The connection key will be revoked and remote actions paused.', 'ikon-seo' ); ?>"><?php esc_html_e( 'Import profile', 'ikon-seo' ); ?></button>
			</form>
		</div>
		<?php
	}

	private function render_search_console( array $settings ) {
		$status      = $this->search_console->status();
		$properties  = $status['connected'] ? $this->search_console->properties() : array( 'items' => array() );
		$performance = $status['connected'] && $status['property'] ? $this->search_console->performance( 28, false ) : null;
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Google Search Console', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Read-only search performance, sitemap status and indexed-version inspection. Ikon SEO cannot request indexing or change Search Console.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo $status['connected'] ? 'is-connected' : 'is-failed'; ?>"><?php echo $status['connected'] ? esc_html__( 'Read-only connected', 'ikon-seo' ) : esc_html__( 'Not connected', 'ikon-seo' ); ?></span>
		</div>

		<div class="notice notice-info inline">
			<p><?php esc_html_e( 'Create a Google OAuth Web application, add the callback URL below as an authorized redirect URI, and enable the Search Console API. Credentials stay encrypted on this WordPress installation.', 'ikon-seo' ); ?></p>
		</div>
		<div class="ikon-seo-connection-box">
			<label><?php esc_html_e( 'Authorized redirect URI', 'ikon-seo' ); ?></label>
			<div class="ikon-seo-copy-row">
				<code id="ikon-seo-gsc-callback"><?php echo esc_html( $status['callback_url'] ); ?></code>
				<button type="button" class="button" data-copy-target="#ikon-seo-gsc-callback"><?php esc_html_e( 'Copy', 'ikon-seo' ); ?></button>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_gsc_save_credentials">
			<?php wp_nonce_field( 'ikon_seo_gsc_save_credentials' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="gsc_client_id"><?php esc_html_e( 'OAuth client ID', 'ikon-seo' ); ?></label></th>
					<td><input required class="large-text code" id="gsc_client_id" name="gsc_client_id" value="<?php echo esc_attr( $settings['gsc_client_id'] ); ?>" autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><label for="gsc_client_secret"><?php esc_html_e( 'OAuth client secret', 'ikon-seo' ); ?></label></th>
					<td>
						<input class="regular-text" type="password" id="gsc_client_secret" name="gsc_client_secret" value="" autocomplete="new-password">
						<p class="description"><?php echo $settings['gsc_client_secret'] ? esc_html__( 'A secret is already encrypted. Leave this blank to keep it.', 'ikon-seo' ) : esc_html__( 'Required for the first connection.', 'ikon-seo' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save OAuth credentials', 'ikon-seo' ), 'secondary' ); ?>
		</form>

		<?php if ( $status['configured'] && ! $status['connected'] ) : ?>
			<form class="ikon-seo-actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_gsc_connect">
				<?php wp_nonce_field( 'ikon_seo_gsc_connect' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Connect Google Search Console', 'ikon-seo' ); ?></button>
			</form>
		<?php endif; ?>

		<?php if ( $status['last_error'] ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $status['last_error'] ); ?></p></div>
		<?php endif; ?>

		<?php if ( $status['connected'] ) : ?>
			<hr>
			<h3><?php esc_html_e( 'Search Console property', 'ikon-seo' ); ?></h3>
			<?php if ( is_wp_error( $properties ) ) : ?>
				<div class="notice notice-error inline"><p><?php echo esc_html( $properties->get_error_message() ); ?></p></div>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_gsc_select_property">
					<?php wp_nonce_field( 'ikon_seo_gsc_select_property' ); ?>
					<select required name="gsc_property">
						<option value=""><?php esc_html_e( 'Select a verified property', 'ikon-seo' ); ?></option>
						<?php foreach ( $properties['items'] as $property ) : ?>
							<option value="<?php echo esc_attr( $property['site_url'] ); ?>" <?php selected( $status['property'], $property['site_url'] ); ?>><?php echo esc_html( $property['site_url'] . ' — ' . $property['permission_level'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php submit_button( __( 'Save property', 'ikon-seo' ), 'secondary', 'submit', false ); ?>
				</form>
			<?php endif; ?>

			<div class="ikon-seo-actions">
				<?php if ( $status['property'] ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ikon_seo_gsc_refresh">
						<?php wp_nonce_field( 'ikon_seo_gsc_refresh' ); ?>
						<button type="submit" class="button"><?php esc_html_e( 'Refresh performance now', 'ikon-seo' ); ?></button>
					</form>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_gsc_disconnect">
					<?php wp_nonce_field( 'ikon_seo_gsc_disconnect' ); ?>
					<button type="submit" class="button" data-confirm="<?php esc_attr_e( 'Disconnect Search Console and remove its stored refresh token?', 'ikon-seo' ); ?>"><?php esc_html_e( 'Disconnect', 'ikon-seo' ); ?></button>
				</form>
			</div>
		<?php endif; ?>

		<?php if ( is_array( $performance ) ) : ?>
			<hr>
			<h3><?php echo esc_html( sprintf( 'Performance: %s to %s', $performance['period']['start'], $performance['period']['end'] ) ); ?></h3>
			<div class="ikon-seo-metrics">
				<?php
				$metrics = array(
					'Clicks'      => array( $performance['totals']['clicks'], $performance['changes']['clicks'] ),
					'Impressions' => array( $performance['totals']['impressions'], $performance['changes']['impressions'] ),
					'CTR'         => array( $performance['totals']['ctr'] * 100, $performance['changes']['ctr'], '%' ),
					'Position'    => array( $performance['totals']['position'], $performance['changes']['position'] ),
				);
				foreach ( $metrics as $label => $metric ) :
					?>
					<div class="ikon-seo-metric">
						<strong><?php echo esc_html( number_format_i18n( $metric[0], 'CTR' === $label || 'Position' === $label ? 2 : 0 ) . ( $metric[2] ?? '' ) ); ?></strong>
						<span><?php echo esc_html( $label ); ?></span>
						<small><?php echo esc_html( null === $metric[1] ? 'New data' : ( $metric[1] > 0 ? '+' : '' ) . $metric[1] . ( 'Position' === $label ? ' places' : '%' ) ); ?></small>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="ikon-seo-two-columns">
				<div>
					<h3><?php esc_html_e( 'Top queries', 'ikon-seo' ); ?></h3>
					<table class="widefat striped">
						<thead><tr><th><?php esc_html_e( 'Query', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Clicks', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Impressions', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Position', 'ikon-seo' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( array_slice( $performance['top_queries'], 0, 15 ) as $row ) : ?>
								<tr><td><?php echo esc_html( $row['key'] ); ?></td><td><?php echo esc_html( number_format_i18n( $row['clicks'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['impressions'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['position'], 1 ) ); ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div>
					<h3><?php esc_html_e( 'Top pages', 'ikon-seo' ); ?></h3>
					<table class="widefat striped">
						<thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Clicks', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Impressions change', 'ikon-seo' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( array_slice( $performance['top_pages'], 0, 15 ) as $row ) : ?>
								<tr><td><a href="<?php echo esc_url( $row['key'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $row['key'], PHP_URL_PATH ) ?: $row['key'] ); ?></a></td><td><?php echo esc_html( number_format_i18n( $row['clicks'] ) ); ?></td><td><?php echo esc_html( null === $row['impressions_change'] ? 'New' : ( $row['impressions_change'] > 0 ? '+' : '' ) . $row['impressions_change'] . '%' ); ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<p class="description"><?php echo esc_html( $performance['data_note'] ); ?></p>
		<?php elseif ( is_wp_error( $performance ) ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( $performance->get_error_message() ); ?></p></div>
		<?php endif; ?>
		<?php
	}

	private function render_diagnostics() {
		$status   = $this->crawler->status();
		$report   = $this->diagnostics->site_report( false, true );
		$post_id  = absint( $_GET['post_id'] ?? 0 );
		$selected = $post_id ? $this->diagnostics->page_report( $post_id, false, true ) : null;
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Page diagnostics', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Evidence-based reasons a page may be underperforming. Ranking blockers, search opportunities, conversion issues and measurement gaps are reported separately.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo $status['crawled'] ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( sprintf( '%d of %d crawled', absint( $status['crawled'] ), absint( $status['published'] ?? 0 ) ) ); ?></span>
		</div>

		<div class="notice notice-info inline"><p><?php esc_html_e( 'Work priority combines likely impact, evidence confidence, business value and implementation effort. It is not a Google ranking score, and inferred findings should not be treated as confirmed causes.', 'ikon-seo' ); ?></p></div>

		<div class="ikon-seo-actions">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_crawl_evidence">
				<?php wp_nonce_field( 'ikon_seo_crawl_evidence' ); ?>
				<label><?php esc_html_e( 'Batch size', 'ikon-seo' ); ?> <input type="number" name="limit" min="1" max="50" value="<?php echo esc_attr( Ikon_SEO_Plugin::settings()['crawler_batch_size'] ); ?>" style="width:75px"></label>
				<label><input type="checkbox" name="force" value="1"> <?php esc_html_e( 'Recrawl newest pages', 'ikon-seo' ); ?></label>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Run evidence crawl', 'ikon-seo' ); ?></button>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_refresh_diagnostics">
				<?php wp_nonce_field( 'ikon_seo_refresh_diagnostics' ); ?>
				<button type="submit" class="button"><?php esc_html_e( 'Refresh diagnoses', 'ikon-seo' ); ?></button>
			</form>
		</div>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $status['crawled'] ) ); ?></strong><span><?php esc_html_e( 'Crawled', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $status['pending'] ) ); ?></strong><span><?php esc_html_e( 'Pending or stale', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $status['critical'] ) ); ?></strong><span><?php esc_html_e( 'Indexing/HTTP concerns', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $status['errors'] ) ); ?></strong><span><?php esc_html_e( 'Crawl errors', 'ikon-seo' ); ?></span></div>
		</div>

		<?php if ( is_wp_error( $report ) ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $report->get_error_message() ); ?></p></div>
		<?php else : ?>
			<?php if ( is_array( $selected ) ) : ?>
				<hr>
				<h3><?php echo esc_html( 'Diagnosis: ' . $selected['title'] ); ?></h3>
				<p><a href="<?php echo esc_url( $selected['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $selected['url'] ); ?></a></p>
				<div class="ikon-seo-metrics">
					<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $selected['fix_priority'] ) ); ?>/100</strong><span><?php esc_html_e( 'Work priority', 'ikon-seo' ); ?></span></div>
					<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $selected['priorities']['ranking'] ?? 0 ) ); ?>/100</strong><span><?php esc_html_e( 'Ranking priority', 'ikon-seo' ); ?></span></div>
					<div class="ikon-seo-metric"><strong><?php echo esc_html( ucfirst( $selected['data_sufficiency']['level'] ?? 'limited' ) ); ?></strong><span><?php esc_html_e( 'Evidence sufficiency', 'ikon-seo' ); ?></span></div>
					<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $selected['business_value']['score'] ?? 1 ) ); ?>/5</strong><span><?php esc_html_e( 'Business value', 'ikon-seo' ); ?></span></div>
				</div>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Category and priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Finding', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recommended action', 'ikon-seo' ); ?></th></tr></thead>
					<tbody>
					<?php if ( $selected['blockers'] ) : foreach ( $selected['blockers'] as $blocker ) : ?>
						<tr>
							<td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $blocker['category'] ?? 'finding' ) ) ); ?></strong><br><small><?php echo esc_html( absint( $blocker['priority_score'] ?? 0 ) . '/100 · ' . ucfirst( $blocker['impact'] ) . ' impact · ' . ucfirst( $blocker['confidence'] ) . ' confidence · effort ' . absint( $blocker['effort'] ?? 3 ) . '/5' ); ?></small></td>
							<td><?php echo esc_html( $blocker['message'] ); ?><br><small><?php echo esc_html( 'Root cause: ' . str_replace( '_', ' ', $blocker['root_cause'] ?? $blocker['code'] ) ); ?></small></td>
							<td><?php echo esc_html( $blocker['evidence'] ); ?><br><small><?php echo esc_html( ucfirst( $blocker['evidence_type'] ) . ' evidence' ); ?></small></td>
							<td><?php echo esc_html( $blocker['recommended_action'] ); ?></td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No major finding was detected in the currently available evidence.', 'ikon-seo' ); ?></td></tr>
					<?php endif; ?>
					</tbody>
				</table>
				<?php if ( ! empty( $selected['limitations'] ) ) : ?><p class="description"><?php echo esc_html( implode( ' ', $selected['limitations'] ) ); ?></p><?php endif; ?>
				<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=diagnostics' ) ); ?>"><?php esc_html_e( 'Back to all pages', 'ikon-seo' ); ?></a></p>
			<?php endif; ?>

			<hr>
			<h3><?php esc_html_e( 'Pages requiring attention', 'ikon-seo' ); ?></h3>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Work / ranking priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Primary finding', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence sufficiency', 'ikon-seo' ); ?></th><th></th></tr></thead>
				<tbody>
				<?php foreach ( array_slice( (array) ( $report['pages'] ?? array() ), 0, 150 ) as $page ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $page['title'] ); ?></strong><br><small><?php echo esc_html( wp_parse_url( $page['url'], PHP_URL_PATH ) ?: '/' ); ?></small></td>
						<td><?php echo esc_html( absint( $page['fix_priority'] ) . '/100 work' ); ?><br><small><?php echo esc_html( absint( $page['priorities']['ranking'] ?? 0 ) . '/100 ranking' ); ?></small></td>
						<td><?php echo esc_html( $page['primary_finding']['message'] ?? 'No major stored finding' ); ?></td>
						<td><strong><?php echo esc_html( ucfirst( $page['data_sufficiency']['level'] ?? 'limited' ) ); ?></strong><br><small><?php echo esc_html( absint( $page['data_sufficiency']['score'] ?? 0 ) . '/100 evidence coverage' ); ?></small></td>
						<td><a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=diagnostics&post_id=' . absint( $page['post_id'] ) ) ); ?>"><?php esc_html_e( 'Diagnose', 'ikon-seo' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}


	private function render_search_intelligence() {
		$settings = Ikon_SEO_Plugin::settings();
		$status   = $this->search_intelligence->status();
		$report   = $this->search_intelligence->report( false, 100 );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Search Intelligence', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Persistent Search Console page-query evidence, related query clusters, cannibalisation signals, striking-distance opportunities and content decay.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo $status['rows'] ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( $status['rows'] ? sprintf( '%s rows stored', number_format_i18n( $status['rows'] ) ) : 'Not refreshed' ); ?></span>
		</div>

		<div class="notice notice-info inline"><p><?php esc_html_e( 'These classifications are evidence-based hypotheses. Review search intent, current competitors, backlinks and business goals before merging, redirecting or rewriting pages.', 'ikon-seo' ); ?></p></div>

		<?php if ( empty( $status['connected'] ) || empty( $status['property'] ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Connect Search Console and select a property before building the Search Intelligence database.', 'ikon-seo' ); ?></p></div>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ikon-seo-actions">
				<input type="hidden" name="action" value="ikon_seo_refresh_search_intelligence">
				<?php wp_nonce_field( 'ikon_seo_refresh_search_intelligence' ); ?>
				<label><?php esc_html_e( 'Period', 'ikon-seo' ); ?> <input type="number" name="days" min="7" max="90" value="<?php echo esc_attr( absint( $settings['search_intelligence_days'] ?? 28 ) ); ?>" style="width:75px"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></label>
				<label><?php esc_html_e( 'Maximum rows', 'ikon-seo' ); ?> <input type="number" name="max_rows" min="1000" max="200000" step="1000" value="<?php echo esc_attr( absint( $settings['search_intelligence_max_rows'] ?? 50000 ) ); ?>" style="width:110px"></label>
				<label><?php esc_html_e( 'Minimum impressions', 'ikon-seo' ); ?> <input type="number" name="min_impressions" min="5" max="1000" value="<?php echo esc_attr( absint( $settings['search_intelligence_min_impressions'] ?? 20 ) ); ?>" style="width:80px"></label>
				<label><?php esc_html_e( 'Decay threshold', 'ikon-seo' ); ?> <input type="number" name="decay_percent" min="10" max="90" value="<?php echo esc_attr( absint( $settings['search_intelligence_decay_percent'] ?? 30 ) ); ?>" style="width:75px">%</label>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Refresh Search Intelligence', 'ikon-seo' ); ?></button>
			</form>
		<?php endif; ?>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['queries'] ) ); ?></strong><span><?php esc_html_e( 'Stored queries', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['pages'] ) ); ?></strong><span><?php esc_html_e( 'Search pages', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['clusters'] ) ); ?></strong><span><?php esc_html_e( 'Query clusters', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['snapshots'] ) ); ?></strong><span><?php esc_html_e( 'Stored periods', 'ikon-seo' ); ?></span></div>
		</div>

		<?php if ( is_wp_error( $report ) ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( $report->get_error_message() ); ?></p></div>
		<?php elseif ( empty( $report['period'] ) ) : ?>
			<p><?php esc_html_e( 'Run the first refresh to create the page-query database.', 'ikon-seo' ); ?></p>
		<?php else : ?>
			<p class="description"><?php echo esc_html( sprintf( 'Current period: %s to %s. Previous comparison: %s to %s. Last stored: %s UTC.', $report['period']['period_start'], $report['period']['period_end'], $report['period']['previous_start'], $report['period']['previous_end'], $status['last_sync'] ?: '—' ) ); ?></p>
			<div class="ikon-seo-metrics">
				<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $report['summary']['cannibalisation'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Overlap signals', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $report['summary']['striking_distance'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Striking distance', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $report['summary']['content_decay'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Decay signals', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $report['summary']['new_gains'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'New visibility gains', 'ikon-seo' ); ?></span></div>
			</div>

			<h3><?php esc_html_e( 'Potential cannibalisation and URL switching', 'ikon-seo' ); ?></h3>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Query', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Classification', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Pages', 'ikon-seo' ); ?></th></tr></thead>
				<tbody>
				<?php if ( ! empty( $report['cannibalisation'] ) ) : foreach ( array_slice( $report['cannibalisation'], 0, 40 ) as $item ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $item['query'] ); ?></strong><br><small><?php echo esc_html( number_format_i18n( $item['impressions'] ) . ' impressions' ); ?></small></td>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['classification'] ) ) ); ?><br><small><?php echo esc_html( ucfirst( $item['confidence'] ) . ' confidence' ); ?></small></td>
						<td><?php echo esc_html( $item['evidence'] ); ?></td>
						<td><?php foreach ( array_slice( (array) $item['pages'], 0, 3 ) as $page ) : ?><a href="<?php echo esc_url( $page['page'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $page['page'], PHP_URL_PATH ) ?: '/' ); ?></a><br><small><?php echo esc_html( sprintf( '%.0f impressions · position %.1f', $page['impressions'], $page['position'] ) ); ?></small><br><?php endforeach; ?></td>
					</tr>
				<?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e( 'No meaningful overlap signal was detected in the stored period.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Striking-distance opportunities', 'ikon-seo' ); ?></h3>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Query and page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Position', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Impressions', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Opportunity priority', 'ikon-seo' ); ?></th></tr></thead>
				<tbody>
				<?php if ( ! empty( $report['striking_distance'] ) ) : foreach ( array_slice( $report['striking_distance'], 0, 50 ) as $item ) : ?>
					<tr><td><strong><?php echo esc_html( $item['query'] ); ?></strong><br><a href="<?php echo esc_url( $item['page'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $item['page'], PHP_URL_PATH ) ?: '/' ); ?></a></td><td><?php echo esc_html( number_format_i18n( $item['position'], 1 ) ); ?></td><td><?php echo esc_html( number_format_i18n( $item['impressions'] ) ); ?></td><td><?php echo esc_html( absint( $item['priority'] ) . '/100' ); ?></td></tr>
				<?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e( 'No query-page pair currently meets the stored striking-distance thresholds.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Content decay evidence', 'ikon-seo' ); ?></h3>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Impressions', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Clicks', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Confidence', 'ikon-seo' ); ?></th></tr></thead>
				<tbody>
				<?php if ( ! empty( $report['content_decay'] ) ) : foreach ( array_slice( $report['content_decay'], 0, 40 ) as $item ) : ?>
					<tr><td><a href="<?php echo esc_url( $item['page'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $item['page'], PHP_URL_PATH ) ?: '/' ); ?></a></td><td><?php echo esc_html( sprintf( '%s%% (%s → %s)', $item['impressions_change'], number_format_i18n( $item['previous_impressions'] ), number_format_i18n( $item['current_impressions'] ) ) ); ?></td><td><?php echo esc_html( null === $item['clicks_change'] ? 'New comparison' : $item['clicks_change'] . '%' ); ?></td><td><?php echo esc_html( ucfirst( $item['confidence'] ) ); ?></td></tr>
				<?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e( 'No page exceeded the configured period-over-period decay threshold.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Leading query clusters', 'ikon-seo' ); ?></h3>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Cluster', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Queries', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Impressions', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Leading page', 'ikon-seo' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( array_slice( (array) ( $report['clusters'] ?? array() ), 0, 40 ) as $cluster ) : ?>
					<tr><td><strong><?php echo esc_html( $cluster['cluster_label'] ); ?></strong></td><td><?php echo esc_html( absint( $cluster['query_count'] ) ); ?><br><small><?php echo esc_html( implode( ', ', array_slice( (array) $cluster['queries'], 0, 4 ) ) ); ?></small></td><td><?php echo esc_html( number_format_i18n( $cluster['impressions'] ) ); ?></td><td><?php if ( $cluster['top_page'] ) : ?><a href="<?php echo esc_url( $cluster['top_page'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $cluster['top_page'], PHP_URL_PATH ) ?: '/' ); ?></a><?php else : ?>—<?php endif; ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( ! empty( $report['limitations'] ) ) : ?><p class="description"><?php echo esc_html( implode( ' ', $report['limitations'] ) ); ?></p><?php endif; ?>
		<?php endif; ?>
		<?php
	}



	private function render_opportunity_engine() {
		$report = $this->opportunity_engine->report( array( 'limit' => 150 ) );
		$status = (array) ( $report['status'] ?? array() );
		$summary = (array) ( $report['summary'] ?? array() );
		$agency = Ikon_SEO_Agency::can_manage();
		$settings = Ikon_SEO_Plugin::settings();
		?>
		<div class="ikon-seo-section-heading">
			<div><h2><?php esc_html_e( 'Evidence Intelligence & Opportunity Engine', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Combines Search Console, stored Analytics, technical, indexation, competitor, authority and approved provider evidence into one prioritised review queue.', 'ikon-seo' ); ?></p></div>
			<?php if ( $agency ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_rebuild_opportunity_engine"><?php wp_nonce_field( 'ikon_seo_rebuild_opportunity_engine' ); ?><input type="hidden" name="limit" value="<?php echo absint( $settings['opportunity_engine_max_items'] ?? 300 ); ?>"><button class="button button-primary"><?php esc_html_e( 'Rebuild Opportunity Queue', 'ikon-seo' ); ?></button></form><?php endif; ?>
		</div>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'This module performs read-only analysis. It cannot publish, redirect, delete, noindex or change canonical settings.', 'ikon-seo' ); ?></p></div>
		<div class="ikon-seo-metrics">
			<?php foreach ( array(
				array( 'value' => absint( $summary['current_opportunities'] ?? 0 ), 'label' => 'Current opportunities' ),
				array( 'value' => absint( $summary['actionable'] ?? 0 ), 'label' => 'Actionable items' ),
				array( 'value' => absint( $status['imported_evidence'] ?? 0 ), 'label' => 'Imported keyword rows' ),
				array( 'value' => absint( $summary['priority_bands']['critical'] ?? 0 ), 'label' => 'Critical priority' ),
			) as $metric ) : ?><div class="ikon-seo-metric"><strong><?php echo absint( $metric['value'] ); ?></strong><span><?php echo esc_html( $metric['label'] ); ?></span></div><?php endforeach; ?>
		</div>
		<p class="description"><?php echo esc_html( 'Last rebuild: ' . ( $status['last_rebuild'] ?: 'Not run yet' ) ); ?></p>

		<?php if ( $agency ) : ?>
		<div class="ikon-seo-grid ikon-seo-grid-2">
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Import keyword evidence', 'ikon-seo' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Upload a CSV export from Semrush, Ahrefs or another licensed provider. The file is stored as evidence and does not trigger content changes.', 'ikon-seo' ); ?></p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_import_opportunity_evidence"><?php wp_nonce_field( 'ikon_seo_import_opportunity_evidence' ); ?>
					<p><label><strong><?php esc_html_e( 'Evidence source', 'ikon-seo' ); ?></strong><br><select name="source"><option value="semrush">Semrush</option><option value="ahrefs">Ahrefs</option><option value="licensed_provider">Licensed provider</option><option value="manual">Manual export</option></select></label></p>
					<p><input type="file" name="evidence_csv" accept=".csv,text/csv" required></p>
					<?php submit_button( __( 'Import Evidence CSV', 'ikon-seo' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Engine settings', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_opportunity_engine_settings"><?php wp_nonce_field( 'ikon_seo_save_opportunity_engine_settings' ); ?>
					<p><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['opportunity_engine_enabled'] ) ); ?>> <?php esc_html_e( 'Enable weekly queue rebuilds', 'ikon-seo' ); ?></label></p>
					<p><label><strong><?php esc_html_e( 'Maximum current opportunities', 'ikon-seo' ); ?></strong><br><input type="number" min="25" max="1000" name="max_items" value="<?php echo absint( $settings['opportunity_engine_max_items'] ?? 300 ); ?>"></label></p>
					<p><label><strong><?php esc_html_e( 'Imported evidence stale after days', 'ikon-seo' ); ?></strong><br><input type="number" min="7" max="365" name="stale_days" value="<?php echo absint( $settings['opportunity_engine_stale_days'] ?? 60 ); ?>"></label></p>
					<?php submit_button( __( 'Save Engine Settings', 'ikon-seo' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Highest-priority opportunities', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Opportunity', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Review', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['opportunities'] ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'No current opportunities are stored. Connect evidence sources or import an approved keyword export, then rebuild the queue.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) ( $report['opportunities'] ?? array() ) as $item ) : ?>
		<tr>
			<td><strong><?php echo absint( $item['priority'] ?? 0 ); ?></strong><br><small><?php echo esc_html( ucfirst( $item['confidence'] ?? 'medium' ) . ' confidence' ); ?></small></td>
			<td><strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong><br><?php echo esc_html( $item['summary'] ?? '' ); ?><?php if ( ! empty( $item['target_url'] ) ) : ?><br><a href="<?php echo esc_url( $item['target_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_parse_url( $item['target_url'], PHP_URL_PATH ) ?: $item['target_url'] ); ?></a><?php endif; ?><?php if ( ! empty( $item['keyword'] ) ) : ?><br><small><?php echo esc_html( 'Keyword: ' . $item['keyword'] ); ?></small><?php endif; ?></td>
			<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['primary_source'] ?? '' ) ) ); ?><br><small><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['category'] ?? '' ) ) ); ?> · <?php echo esc_html( ucfirst( $item['effort'] ?? 'medium' ) . ' effort · ' . ucfirst( $item['risk'] ?? 'low' ) . ' risk' ); ?></small><?php if ( ! empty( $item['actions'][0] ) ) : ?><p class="description"><?php echo esc_html( $item['actions'][0] ); ?></p><?php endif; ?></td>
			<td><?php if ( $agency ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_update_opportunity_status"><input type="hidden" name="opportunity_id" value="<?php echo absint( $item['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_update_opportunity_status_' . absint( $item['id'] ) ); ?><select name="status"><option value="open" <?php selected( $item['status'], 'open' ); ?>>Open</option><option value="reviewed" <?php selected( $item['status'], 'reviewed' ); ?>>Reviewed</option><option value="planned" <?php selected( $item['status'], 'planned' ); ?>>Planned</option><option value="completed" <?php selected( $item['status'], 'completed' ); ?>>Completed</option><option value="dismissed" <?php selected( $item['status'], 'dismissed' ); ?>>Dismissed</option></select><br><textarea name="notes" rows="2" placeholder="Review notes"><?php echo esc_textarea( $item['review_notes'] ?? '' ); ?></textarea><br><button class="button button-small"><?php esc_html_e( 'Save', 'ikon-seo' ); ?></button></form><?php else : ?><?php echo esc_html( ucfirst( $item['status'] ?? 'open' ) ); ?><?php endif; ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody></table>
		<h3><?php esc_html_e( 'Evidence source health', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Source', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'State', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) ( $report['source_health'] ?? array() ) as $source => $details ) : ?><tr><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $source ) ) ); ?></td><td><code><?php echo esc_html( wp_json_encode( $details ) ); ?></code></td></tr><?php endforeach; ?></tbody></table>
		<?php
	}


	private function render_content_workbench() {
		$report = $this->content_workbench->report( 150, false );
		$status = (array) ( $report['status'] ?? array() );
		$counts = (array) ( $status['counts'] ?? array() );
		$agency = Ikon_SEO_Agency::can_manage();
		?>
		<div class="ikon-seo-section-heading">
			<div><h2><?php esc_html_e( 'Content Planning & Controlled Draft Generation', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Convert only Planned opportunities into evidence-led, versioned briefs and separate unpublished WordPress drafts.', 'ikon-seo' ); ?></p></div>
			<span class="ikon-seo-pill <?php echo ! empty( $status['database_ready'] ) ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ! empty( $status['database_ready'] ) ? 'Draft-only controls active' : 'Database update required' ); ?></span>
		</div>
		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Approval boundary:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'Brief approval is required before a separate draft can be created. This workbench cannot publish, edit live pages, redirect, delete, noindex or change canonicals.', 'ikon-seo' ); ?></p></div>
		<div class="ikon-seo-metrics">
			<?php foreach ( array( array( $counts['proposed'] ?? 0, 'Proposed briefs' ), array( $counts['approved'] ?? 0, 'Approved briefs' ), array( $counts['draft_created'] ?? 0, 'Controlled drafts' ), array( $counts['ready'] ?? 0, 'Ready for human review' ) ) as $metric ) : ?><div class="ikon-seo-metric"><strong><?php echo absint( $metric[0] ); ?></strong><span><?php echo esc_html( $metric[1] ); ?></span></div><?php endforeach; ?>
		</div>

		<h3><?php esc_html_e( 'Planned opportunities awaiting a brief', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Opportunity', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['eligible_opportunities'] ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'No eligible Planned content opportunities are waiting for a brief.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) ( $report['eligible_opportunities'] ?? array() ) as $item ) : ?><tr><td><strong><?php echo absint( $item['priority'] ?? 0 ); ?></strong></td><td><strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong><br><?php echo esc_html( $item['summary'] ?? '' ); ?><?php if ( ! empty( $item['keyword'] ) ) : ?><br><small><?php echo esc_html( 'Target: ' . $item['keyword'] ); ?></small><?php endif; ?></td><td><?php if ( $agency ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_create_content_brief"><input type="hidden" name="opportunity_id" value="<?php echo absint( $item['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_create_content_brief_' . absint( $item['id'] ) ); ?><button class="button button-primary"><?php esc_html_e( 'Build Proposed Brief', 'ikon-seo' ); ?></button></form><?php else : ?><?php esc_html_e( 'Agency approval required', 'ikon-seo' ); ?><?php endif; ?></td></tr><?php endforeach; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Versioned content briefs and drafts', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Brief', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence and plan', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Controlled action', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['briefs'] ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'No Content Workbench briefs have been created.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) ( $report['briefs'] ?? array() ) as $brief ) : $plan = (array) ( $brief['brief'] ?? array() ); ?>
		<tr>
			<td><strong><?php echo esc_html( $brief['page_title'] ?? '' ); ?></strong><br><span class="ikon-seo-pill"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $brief['status'] ?? 'proposed' ) ) ); ?></span><br><small><?php echo esc_html( 'Brief #' . absint( $brief['id'] ) . ' · v' . absint( $brief['brief_version'] ) . ' · Priority ' . absint( $brief['gap_priority'] ) ); ?></small><?php if ( ! empty( $brief['draft_edit_url'] ) ) : ?><p><a class="button button-small" href="<?php echo esc_url( $brief['draft_edit_url'] ); ?>"><?php esc_html_e( 'Edit Draft', 'ikon-seo' ); ?></a> <a class="button button-small" target="_blank" rel="noopener" href="<?php echo esc_url( $brief['draft_preview_url'] ); ?>"><?php esc_html_e( 'Preview', 'ikon-seo' ); ?></a></p><?php endif; ?></td>
			<td><strong><?php echo esc_html( $brief['target_query'] ?? '' ); ?></strong><br><small><?php echo esc_html( ucfirst( $brief['target_intent'] ?? 'mixed' ) . ' intent · ' . ucfirst( $brief['evidence_confidence'] ?? 'low' ) . ' confidence' ); ?></small><p class="description"><?php echo esc_html( $brief['direct_evidence'][0] ?? 'Evidence summary unavailable.' ); ?></p><?php if ( ! empty( $plan['section_plan'] ) ) : ?><details><summary><?php echo esc_html( count( $plan['section_plan'] ) . ' planned sections' ); ?></summary><ol><?php foreach ( array_slice( (array) $plan['section_plan'], 0, 12 ) as $section ) : ?><li><?php echo esc_html( $section['heading'] ?? '' ); ?></li><?php endforeach; ?></ol></details><?php endif; ?></td>
			<td><?php if ( ! $agency ) : ?><?php esc_html_e( 'Agency approval required', 'ikon-seo' ); ?><?php elseif ( 'proposed' === $brief['status'] ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_approve_content_brief"><input type="hidden" name="brief_id" value="<?php echo absint( $brief['id'] ); ?>"><input type="hidden" name="evidence_hash" value="<?php echo esc_attr( $brief['evidence_hash'] ); ?>"><?php wp_nonce_field( 'ikon_seo_approve_content_brief_' . absint( $brief['id'] ) ); ?><button class="button button-primary"><?php esc_html_e( 'Approve Current Brief', 'ikon-seo' ); ?></button></form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px"><input type="hidden" name="action" value="ikon_seo_reject_content_brief"><input type="hidden" name="brief_id" value="<?php echo absint( $brief['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_reject_content_brief_' . absint( $brief['id'] ) ); ?><textarea name="notes" rows="2" placeholder="Reason for rejection"></textarea><br><button class="button button-small"><?php esc_html_e( 'Reject', 'ikon-seo' ); ?></button></form>
			<?php elseif ( in_array( $brief['status'], array( 'outdated','rejected' ), true ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_create_content_brief"><input type="hidden" name="opportunity_id" value="<?php echo absint( $brief['opportunity_id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_create_content_brief_' . absint( $brief['opportunity_id'] ) ); ?><button class="button button-primary"><?php esc_html_e( 'Regenerate From Current Evidence', 'ikon-seo' ); ?></button></form>
			<?php elseif ( 'approved' === $brief['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_create_content_scaffold"><input type="hidden" name="brief_id" value="<?php echo absint( $brief['id'] ); ?>"><input type="hidden" name="evidence_hash" value="<?php echo esc_attr( $brief['evidence_hash'] ); ?>"><?php wp_nonce_field( 'ikon_seo_create_content_scaffold_' . absint( $brief['id'] ) ); ?><button class="button button-primary"><?php esc_html_e( 'Create Unpublished Scaffold', 'ikon-seo' ); ?></button></form>
			<?php elseif ( 'draft_created' === $brief['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_evaluate_content_draft"><input type="hidden" name="brief_id" value="<?php echo absint( $brief['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_evaluate_content_draft_' . absint( $brief['id'] ) ); ?><button class="button"><?php esc_html_e( 'Run Quality Gate', 'ikon-seo' ); ?></button></form><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px"><input type="hidden" name="action" value="ikon_seo_mark_content_ready"><input type="hidden" name="brief_id" value="<?php echo absint( $brief['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_mark_content_ready_' . absint( $brief['id'] ) ); ?><button class="button button-primary"><?php esc_html_e( 'Mark Ready After Pass', 'ikon-seo' ); ?></button></form>
			<?php elseif ( 'ready' === $brief['status'] ) : ?><strong><?php esc_html_e( 'Ready for human review', 'ikon-seo' ); ?></strong><p class="description"><?php esc_html_e( 'Publishing remains a separate WordPress decision.', 'ikon-seo' ); ?></p><?php else : ?><span><?php echo esc_html( ucfirst( $brief['status'] ?? '' ) ); ?></span><?php endif; ?></td>
		</tr><?php endforeach; ?>
		</tbody></table>
		<?php
	}


	private function render_editorial_review() {
		$can_manage = current_user_can( 'manage_options' );
		$report = $this->editorial_review->report( array( 'limit' => 100, 'user_id' => $can_manage ? 0 : get_current_user_id() ), false );
		$users = get_users( array( 'capability' => 'edit_posts', 'orderby' => 'display_name', 'order' => 'ASC' ) );
		$status = (array) ( $report['status'] ?? array() );
		$summary = (array) ( $report['summary'] ?? array() );
		?>
		<div class="ikon-seo-section-header"><div><h2><?php esc_html_e( 'Editorial Review & Revision Control', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Assign writers and reviewers, store immutable review snapshots, verify sources and claims, request revisions and record final human sign-off without automatic publishing.', 'ikon-seo' ); ?></p></div><span class="ikon-seo-pill <?php echo ! empty( $status['database_ready'] ) ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ! empty( $status['database_ready'] ) ? 'Approval-first' : 'Database update required' ); ?></span></div>
		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Publishing boundary:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'Final sign-off changes only the internal workflow status. The WordPress draft remains unpublished until a separate authorised publishing decision.', 'ikon-seo' ); ?></p></div>
		<?php if ( empty( $status['database_ready'] ) ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Reactivate or update Ikon SEO to create the editorial review tables.', 'ikon-seo' ); ?></p></div><?php return; endif; ?>
		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['active'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Active reviews', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['awaiting_review'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Awaiting review', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['overdue'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Overdue', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['blocked'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Blocked', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['open_comments'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Open comments', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['signed_off'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Signed off', 'ikon-seo' ); ?></span></div>
		</div>

		<?php if ( $can_manage ) : ?>
		<h3><?php esc_html_e( 'Drafts Ready for Editorial Assignment', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Draft', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Assignment', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['startable_briefs'] ) ) : ?><tr><td colspan="2"><?php esc_html_e( 'No unassigned controlled drafts are available.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) ( $report['startable_briefs'] ?? array() ) as $brief ) : ?>
		<tr><td><strong><?php echo esc_html( $brief['page_title'] ?? 'Controlled draft' ); ?></strong><br><small><?php echo esc_html( 'Brief #' . absint( $brief['id'] ) . ' · Draft #' . absint( $brief['draft_post_id'] ) . ' · ' . ucfirst( $brief['status'] ?? '' ) ); ?></small></td><td>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_editorial_action"><input type="hidden" name="command" value="start_review"><input type="hidden" name="brief_id" value="<?php echo absint( $brief['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_editorial_start_review_' . absint( $brief['id'] ) ); ?>
		<label><?php esc_html_e( 'Writer', 'ikon-seo' ); ?> <select name="writer_id"><option value="0"><?php esc_html_e( 'Unassigned', 'ikon-seo' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label>
		<label><?php esc_html_e( 'Reviewer', 'ikon-seo' ); ?> <select name="reviewer_id"><option value="0"><?php esc_html_e( 'Unassigned', 'ikon-seo' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label>
		<label><?php esc_html_e( 'Writing due', 'ikon-seo' ); ?> <input type="datetime-local" name="due_at"></label> <label><?php esc_html_e( 'Review due', 'ikon-seo' ); ?> <input type="datetime-local" name="review_due_at"></label> <button class="button button-primary"><?php esc_html_e( 'Start Review', 'ikon-seo' ); ?></button></form>
		</td></tr><?php endforeach; ?></tbody></table>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Editorial Queue', 'ikon-seo' ); ?></h3>
		<?php if ( empty( $report['reviews'] ) ) : ?><p><?php esc_html_e( 'No editorial reviews have been started.', 'ikon-seo' ); ?></p><?php endif; ?>
		<?php foreach ( (array) ( $report['reviews'] ?? array() ) as $review ) : $nonce_id = absint( $review['id'] ); ?>
		<div class="ikon-seo-card" style="margin-bottom:16px">
			<div class="ikon-seo-section-header"><div><h3 style="margin:0"><?php echo esc_html( $review['brief']['page_title'] ?? ( 'Review #' . $nonce_id ) ); ?></h3><p class="description"><?php echo esc_html( 'Round ' . absint( $review['round_number'] ) . ' · Draft #' . absint( $review['draft_post_id'] ) . ' · ' . ucfirst( str_replace( '_', ' ', $review['status'] ) ) ); ?><?php if ( ! empty( $review['is_overdue'] ) ) : ?> · <strong><?php esc_html_e( 'Overdue', 'ikon-seo' ); ?></strong><?php endif; ?></p></div><div><?php if ( ! empty( $review['edit_url'] ) ) : ?><a class="button" href="<?php echo esc_url( $review['edit_url'] ); ?>"><?php esc_html_e( 'Edit Draft', 'ikon-seo' ); ?></a><?php endif; ?> <?php if ( ! empty( $review['preview_url'] ) ) : ?><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( $review['preview_url'] ); ?>"><?php esc_html_e( 'Preview', 'ikon-seo' ); ?></a><?php endif; ?></div></div>
			<?php if ( ! empty( $review['draft_changed_after_snapshot'] ) ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'The draft changed after the latest snapshot. Submit a new review request or revision before approval.', 'ikon-seo' ); ?></p></div><?php endif; ?>
			<?php if ( 'blocked' === $review['status'] ) : ?><div class="notice notice-error inline"><p><strong><?php esc_html_e( 'Blocked:', 'ikon-seo' ); ?></strong> <?php echo esc_html( $review['blocked_reason'] ); ?></p></div><?php endif; ?>
			<p><strong><?php esc_html_e( 'Writer:', 'ikon-seo' ); ?></strong> <?php echo esc_html( $review['writer']['name'] ?? 'Unassigned' ); ?> &nbsp; <strong><?php esc_html_e( 'Reviewer:', 'ikon-seo' ); ?></strong> <?php echo esc_html( $review['reviewer']['name'] ?? 'Unassigned' ); ?> &nbsp; <strong><?php esc_html_e( 'Open comments:', 'ikon-seo' ); ?></strong> <?php echo absint( $review['open_comment_count'] ); ?> &nbsp; <strong><?php esc_html_e( 'Pending checks:', 'ikon-seo' ); ?></strong> <?php echo absint( $review['pending_required_check_count'] ); ?></p>

			<details><summary><strong><?php esc_html_e( 'Assignment, deadlines and workflow actions', 'ikon-seo' ); ?></strong></summary>
			<?php if ( $can_manage ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:12px 0"><input type="hidden" name="action" value="ikon_seo_editorial_action"><input type="hidden" name="command" value="assign"><input type="hidden" name="review_id" value="<?php echo $nonce_id; ?>"><?php wp_nonce_field( 'ikon_seo_editorial_assign_' . $nonce_id ); ?>
			<select name="writer_id"><option value="0"><?php esc_html_e( 'Writer: unassigned', 'ikon-seo' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>" <?php selected( absint( $review['writer_id'] ), absint( $user->ID ) ); ?>><?php echo esc_html( 'Writer: ' . $user->display_name ); ?></option><?php endforeach; ?></select>
			<select name="reviewer_id"><option value="0"><?php esc_html_e( 'Reviewer: unassigned', 'ikon-seo' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>" <?php selected( absint( $review['reviewer_id'] ), absint( $user->ID ) ); ?>><?php echo esc_html( 'Reviewer: ' . $user->display_name ); ?></option><?php endforeach; ?></select>
			<input type="datetime-local" name="due_at" value="<?php echo esc_attr( $review['due_at'] ? get_date_from_gmt( $review['due_at'], 'Y-m-d\TH:i' ) : '' ); ?>"> <input type="datetime-local" name="review_due_at" value="<?php echo esc_attr( $review['review_due_at'] ? get_date_from_gmt( $review['review_due_at'], 'Y-m-d\TH:i' ) : '' ); ?>"> <button class="button"><?php esc_html_e( 'Update Assignment', 'ikon-seo' ); ?></button></form>
			<?php endif; ?>
			<div style="display:flex;gap:8px;flex-wrap:wrap">
			<?php $this->editorial_command_form( $review, 'request_review', 'Submit for Review', true ); ?>
			<?php $this->editorial_command_form( $review, 'request_changes', 'Request Changes', false, true ); ?>
			<?php $this->editorial_command_form( $review, 'submit_revision', 'Submit Revision', true, true ); ?>
			<?php $this->editorial_command_form( $review, 'approve_round', 'Approve Round', true, true ); ?>
			<?php $this->editorial_command_form( $review, 'sign_off', 'Record Final Sign-off', true, true ); ?>
			<?php if ( 'blocked' === $review['status'] ) : $this->editorial_command_form( $review, 'unblock', 'Reopen Review', false, true ); else : $this->editorial_command_form( $review, 'block', 'Block Review', false, true ); endif; ?>
			</div></details>

			<details><summary><strong><?php esc_html_e( 'Source and claim verification', 'ikon-seo' ); ?></strong></summary>
			<table class="widefat striped" style="margin-top:10px"><thead><tr><th><?php esc_html_e( 'Type', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Requirement', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Decision', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) $review['checks'] as $check ) : ?><tr><td><?php echo esc_html( ucfirst( $check['type'] ) ); ?></td><td><strong><?php echo esc_html( $check['label'] ); ?></strong><?php if ( ! empty( $check['evidence'] ) ) : ?><br><small><?php echo esc_html( $check['evidence'] ); ?></small><?php endif; ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_editorial_action"><input type="hidden" name="command" value="update_check"><input type="hidden" name="review_id" value="<?php echo $nonce_id; ?>"><input type="hidden" name="check_id" value="<?php echo absint( $check['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_editorial_update_check_' . $nonce_id ); ?><select name="check_status"><option value="pending" <?php selected( $check['status'], 'pending' ); ?>>Pending</option><option value="verified" <?php selected( $check['status'], 'verified' ); ?>>Verified</option><option value="failed" <?php selected( $check['status'], 'failed' ); ?>>Failed</option><option value="not_applicable" <?php selected( $check['status'], 'not_applicable' ); ?>>Not applicable</option></select><input type="text" name="notes" value="<?php echo esc_attr( $check['notes'] ); ?>" placeholder="Evidence or note"><button class="button button-small"><?php esc_html_e( 'Save', 'ikon-seo' ); ?></button></form></td></tr><?php endforeach; ?></tbody></table></details>

			<details><summary><strong><?php esc_html_e( 'Comments and revision requests', 'ikon-seo' ); ?></strong></summary>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:10px 0"><input type="hidden" name="action" value="ikon_seo_editorial_action"><input type="hidden" name="command" value="add_comment"><input type="hidden" name="review_id" value="<?php echo $nonce_id; ?>"><?php wp_nonce_field( 'ikon_seo_editorial_add_comment_' . $nonce_id ); ?><select name="comment_type"><option value="general">General</option><option value="inline">Inline</option><option value="source">Source</option><option value="claim">Claim</option><option value="structure">Structure</option><option value="seo">SEO</option><option value="accessibility">Accessibility</option></select> <input type="text" name="anchor_text" placeholder="Quoted text or section anchor"> <textarea name="comment_text" rows="2" style="width:100%" placeholder="Specific revision request"></textarea><button class="button"><?php esc_html_e( 'Add Comment', 'ikon-seo' ); ?></button></form>
			<?php if ( empty( $review['comments'] ) ) : ?><p><?php esc_html_e( 'No comments recorded.', 'ikon-seo' ); ?></p><?php endif; ?>
			<?php foreach ( (array) $review['comments'] as $comment ) : ?><div style="border-left:3px solid #ccd0d4;padding:8px 12px;margin:8px 0"><strong><?php echo esc_html( ucfirst( $comment['type'] ) . ' · ' . ucfirst( $comment['status'] ) ); ?></strong><?php if ( $comment['anchor_text'] ) : ?> <code><?php echo esc_html( $comment['anchor_text'] ); ?></code><?php endif; ?><p><?php echo esc_html( $comment['text'] ); ?></p><?php if ( 'open' === $comment['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_editorial_action"><input type="hidden" name="command" value="resolve_comment"><input type="hidden" name="review_id" value="<?php echo $nonce_id; ?>"><input type="hidden" name="comment_id" value="<?php echo absint( $comment['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_editorial_resolve_comment_' . $nonce_id ); ?><select name="resolution"><option value="resolved">Resolved</option><option value="dismissed">Dismissed</option></select><input type="text" name="notes" placeholder="Resolution note"><button class="button button-small"><?php esc_html_e( 'Close Comment', 'ikon-seo' ); ?></button></form><?php endif; ?></div><?php endforeach; ?></details>

			<details><summary><strong><?php esc_html_e( 'Revision history and comparison', 'ikon-seo' ); ?></strong></summary><p><?php echo esc_html( count( (array) $review['snapshots'] ) . ' immutable snapshots stored.' ); ?></p><?php if ( ! empty( $review['latest_comparison']['available'] ) ) : $comparison = $review['latest_comparison']['summary']; ?><p><?php echo esc_html( sprintf( 'Latest change: %d words, %d added paragraphs, %d removed paragraphs.', intval( $comparison['word_count_change'] ), absint( $comparison['added_paragraphs'] ), absint( $comparison['removed_paragraphs'] ) ) ); ?></p><?php endif; ?><ol><?php foreach ( (array) $review['events'] as $event ) : ?><li><strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $event['event_type'] ) ) ); ?></strong> — <?php echo esc_html( $event['notes'] ); ?> <small><?php echo esc_html( $event['created_at'] ); ?></small></li><?php endforeach; ?></ol></details>
		</div>
		<?php endforeach;
	}


	private function render_publishing_readiness() {
		$report = $this->publishing_readiness->report( array( 'limit' => 100 ), false );
		$status = (array) ( $report['status'] ?? array() );
		$summary = (array) ( $report['summary'] ?? array() );
		?>
		<div class="ikon-seo-section-header"><div><h2><?php esc_html_e( 'Controlled Publishing Readiness', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Turn final editorial sign-off into an immutable release candidate, run launch preflight, record a separate readiness approval and verify the public result after a manual WordPress publishing decision.', 'ikon-seo' ); ?></p></div><span class="ikon-seo-pill <?php echo ! empty( $status['database_ready'] ) ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ! empty( $status['database_ready'] ) ? 'Manual publishing boundary' : 'Database update required' ); ?></span></div>
		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Safety boundary:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'This screen never publishes, schedules, merges, redirects, deletes, or changes canonical and indexing settings. It prepares and verifies a separate human publishing action.', 'ikon-seo' ); ?></p></div>
		<?php if ( empty( $status['database_ready'] ) ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Reactivate or update Ikon SEO to create the v1.11.0 publishing readiness tables.', 'ikon-seo' ); ?></p></div><?php return; endif; ?>
		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['active'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Active releases', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['ready_for_manual_publish'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Ready to publish manually', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['awaiting_verification'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Awaiting verification', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['issues_found'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Launch issues', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['blockers'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Current blockers', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['completed'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Completed', 'ikon-seo' ); ?></span></div>
		</div>

		<h3><?php esc_html_e( 'Signed-off Drafts Ready for a Release Candidate', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Signed-off draft', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Release setup', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['eligible_reviews'] ) ) : ?><tr><td colspan="2"><?php esc_html_e( 'No current signed-off editorial drafts are waiting for release preparation.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) ( $report['eligible_reviews'] ?? array() ) as $review ) : $brief = (array) ( $review['brief'] ?? array() ); ?>
		<tr><td><strong><?php echo esc_html( $brief['page_title'] ?? 'Controlled draft' ); ?></strong><br><small><?php echo esc_html( 'Editorial review #' . absint( $review['id'] ) . ' · Draft #' . absint( $review['draft_post_id'] ) ); ?></small><p><a class="button button-small" href="<?php echo esc_url( $review['edit_url'] ?? '' ); ?>"><?php esc_html_e( 'Review Draft', 'ikon-seo' ); ?></a></p></td><td>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_publishing_action"><input type="hidden" name="command" value="create_release"><input type="hidden" name="review_id" value="<?php echo absint( $review['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_publishing_create_release_' . absint( $review['id'] ) ); ?><label><?php esc_html_e( 'Proposed slug', 'ikon-seo' ); ?> <input type="text" name="slug" value="<?php echo esc_attr( sanitize_title( $brief['page_title'] ?? '' ) ); ?>"></label> <button class="button button-primary"><?php esc_html_e( 'Create Release Candidate', 'ikon-seo' ); ?></button></form>
		</td></tr><?php endforeach; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Release Candidates and Launch Monitoring', 'ikon-seo' ); ?></h3>
		<?php if ( empty( $report['releases'] ) ) : ?><p><?php esc_html_e( 'No publishing releases have been created.', 'ikon-seo' ); ?></p><?php endif; ?>
		<?php foreach ( (array) ( $report['releases'] ?? array() ) as $release ) : $id = absint( $release['id'] ); $review = (array) ( $release['editorial_review'] ?? array() ); $brief = (array) ( $review['brief'] ?? array() ); ?>
		<div class="ikon-seo-card" style="margin:14px 0">
			<div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap"><div><h3 style="margin:0"><?php echo esc_html( $brief['page_title'] ?? ( 'Release #' . $id ) ); ?></h3><p><span class="ikon-seo-pill"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $release['status'] ) ) ); ?></span> <small><?php echo esc_html( 'Release #' . $id . ' · ' . ucfirst( str_replace( '_', ' ', $release['publication_mode'] ) ) ); ?></small></p><p class="description"><?php echo esc_html( $release['target_url'] ?: 'Target URL will be confirmed at publication.' ); ?></p></div><div><strong><?php echo absint( $release['preflight_score'] ); ?>/100 preflight<?php if ( ! empty( $release['last_verified_at'] ) ) : ?><br><?php echo absint( $release['verification_score'] ); ?>/100 verification<?php endif; ?></strong><br><small><?php echo esc_html( absint( $release['blocker_count'] ) . ' blockers · ' . absint( $release['warning_count'] ) . ' warnings' ); ?></small></div></div>
			<?php if ( ! empty( $release['draft_changed_after_release'] ) ) : ?><div class="notice notice-error inline"><p><?php esc_html_e( 'The draft changed after the release candidate was created. Readiness and preflight are no longer current; return it to Editorial Review.', 'ikon-seo' ); ?></p></div><?php endif; ?>
			<?php if ( 'blocked' === $release['status'] && $release['blocked_reason'] ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html( $release['blocked_reason'] ); ?></p></div><?php endif; ?>
			<div style="display:flex;gap:8px;flex-wrap:wrap;margin:10px 0">
			<?php if ( in_array( $release['status'], array( 'candidate','preflight_failed','preflight_passed' ), true ) ) : $this->publishing_command_form( $release, 'run_preflight', 'Run Current Preflight', true ); endif; ?>
			<?php if ( 'preflight_passed' === $release['status'] ) : $this->publishing_command_form( $release, 'mark_ready', 'Approve Readiness', true, true ); endif; ?>
			<?php if ( 'ready_for_manual_publish' === $release['status'] ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_publishing_action"><input type="hidden" name="command" value="record_manual_publication"><input type="hidden" name="release_id" value="<?php echo $id; ?>"><?php wp_nonce_field( 'ikon_seo_publishing_record_manual_publication_' . $id ); ?><input type="number" min="1" name="live_post_id" value="<?php echo absint( $release['publication_mode'] === 'new_page' ? $release['draft_post_id'] : $release['source_post_id'] ); ?>" placeholder="Published post ID"><input type="url" name="live_url" placeholder="Optional public URL"><button class="button button-primary"><?php esc_html_e( 'Record Manual Publication', 'ikon-seo' ); ?></button></form>
			<?php endif; ?>
			<?php if ( in_array( $release['status'], array( 'publication_detected','monitoring','issues_found','verified' ), true ) ) : $this->publishing_command_form( $release, 'verify_launch', 'Verify Public Launch', true ); endif; ?>
			<?php if ( in_array( $release['status'], array( 'verified','monitoring' ), true ) && ! absint( $release['blocker_count'] ) ) : $this->publishing_command_form( $release, 'complete_monitoring', 'Complete Monitoring', false, true ); endif; ?>
			<?php if ( 'blocked' === $release['status'] ) : $this->publishing_command_form( $release, 'unblock', 'Reopen Release', false, true ); elseif ( ! in_array( $release['status'], array( 'completed','cancelled' ), true ) ) : $this->publishing_command_form( $release, 'block', 'Block Release', false, true ); endif; ?>
			</div>
			<p><a class="button button-small" href="<?php echo esc_url( $release['draft_edit_url'] ); ?>"><?php esc_html_e( 'Open Controlled Draft', 'ikon-seo' ); ?></a><?php if ( $release['target_url'] && $release['published_at'] ) : ?> <a class="button button-small" target="_blank" rel="noopener" href="<?php echo esc_url( $release['target_url'] ); ?>"><?php esc_html_e( 'Open Live URL', 'ikon-seo' ); ?></a><?php endif; ?></p>
			<details><summary><strong><?php esc_html_e( 'Preflight and post-launch checks', 'ikon-seo' ); ?></strong></summary><table class="widefat striped" style="margin-top:10px"><thead><tr><th><?php esc_html_e( 'Phase', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Check', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Result', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $release['checks'] ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'No checks have been run.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( (array) $release['checks'] as $check ) : ?><tr><td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $check['phase'] ) ) ); ?></td><td><strong><?php echo esc_html( $check['label'] ); ?></strong><br><small><?php echo esc_html( ucfirst( $check['severity'] ) . ' · Expected: ' . $check['expected'] ); ?></small></td><td><span class="ikon-seo-pill"><?php echo esc_html( ucfirst( $check['status'] ) ); ?></span><br><small><?php echo esc_html( $check['observed'] ); ?></small></td></tr><?php endforeach; ?></tbody></table></details>
			<details><summary><strong><?php esc_html_e( 'Snapshots and event history', 'ikon-seo' ); ?></strong></summary><p><?php echo esc_html( count( (array) $release['snapshots'] ) . ' immutable release snapshots stored.' ); ?></p><ol><?php foreach ( (array) $release['events'] as $event ) : ?><li><strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $event['type'] ) ) ); ?></strong> — <?php echo esc_html( $event['notes'] ); ?> <small><?php echo esc_html( $event['created_at'] ); ?></small></li><?php endforeach; ?></ol></details>
		</div>
		<?php endforeach;
	}

	private function publishing_command_form( array $release, $command, $label, $primary = false, $notes = false ) {
		$id = absint( $release['id'] );
		?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_publishing_action"><input type="hidden" name="command" value="<?php echo esc_attr( $command ); ?>"><input type="hidden" name="release_id" value="<?php echo $id; ?>"><?php wp_nonce_field( 'ikon_seo_publishing_' . $command . '_' . $id ); ?><?php if ( $notes ) : ?><input type="text" name="notes" placeholder="Decision or blocking note"><?php endif; ?><button class="button <?php echo $primary ? 'button-primary' : ''; ?>"><?php echo esc_html( $label ); ?></button></form><?php
	}

	private function editorial_command_form( array $review, $command, $label, $primary = false, $notes = false ) {
		$allowed = array(
			'request_review' => array( 'assigned', 'writing', 'changes_requested' ),
			'request_changes' => array( 'review_requested', 'approved' ),
			'submit_revision' => array( 'changes_requested' ),
			'approve_round' => array( 'review_requested' ),
			'sign_off' => array( 'approved' ),
			'block' => array( 'unassigned', 'assigned', 'writing', 'review_requested', 'changes_requested', 'approved' ),
			'unblock' => array( 'blocked' ),
		);
		if ( empty( $allowed[ $command ] ) || ! in_array( $review['status'], $allowed[ $command ], true ) ) {
			return;
		}
		$user_id = get_current_user_id();
		$can_manage = current_user_can( 'manage_options' );
		$writer_actions = array( 'request_review', 'submit_revision' );
		$reviewer_actions = array( 'request_changes', 'approve_round', 'sign_off' );
		if ( in_array( $command, $writer_actions, true ) && ! $can_manage && absint( $review['writer_id'] ) !== $user_id ) { return; }
		if ( in_array( $command, $reviewer_actions, true ) && ! $can_manage && absint( $review['reviewer_id'] ) !== $user_id ) { return; }
		if ( in_array( $command, array( 'block', 'unblock' ), true ) && ! $can_manage ) { return; }
		$id = absint( $review['id'] );
		?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_editorial_action"><input type="hidden" name="command" value="<?php echo esc_attr( $command ); ?>"><input type="hidden" name="review_id" value="<?php echo $id; ?>"><?php wp_nonce_field( 'ikon_seo_editorial_' . $command . '_' . $id ); ?><?php if ( $notes ) : ?><input type="text" name="notes" placeholder="Optional decision note"><?php endif; ?><button class="button <?php echo $primary ? 'button-primary' : ''; ?>"><?php echo esc_html( $label ); ?></button></form><?php
	}

	private function render_portfolio_governance() {
		$report = $this->portfolio_governance->report( array( 'limit' => 100 ) );
		$status = (array) ( $report['status'] ?? array() );
		$agent  = (array) ( $status['agent'] ?? array() );
		$active = (array) ( $report['active_policy'] ?? array() );
		$compliance = (array) ( $report['compliance'] ?? array() );
		$one_time_key = $this->portfolio_governance->consume_agent_key( get_current_user_id() );
		$agency_report = Ikon_SEO_Agency::can_manage() ? $this->agency_command->summary( 100 ) : array();
		?>
		<div class="ikon-seo-section-header"><div><h2><?php esc_html_e( 'Agency Portfolio Synchronisation & Governance', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Create versioned agency policies, deliver them as proposals, require local approval and monitor governance compliance across managed websites.', 'ikon-seo' ); ?></p></div><span class="ikon-seo-pill <?php echo ! empty( $status['database_ready'] ) ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ! empty( $status['database_ready'] ) ? 'Approval-first governance' : 'Database update required' ); ?></span></div>
		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Safety boundary:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'The agency can submit a policy proposal, but only an administrator on this WordPress website can activate it. Policies cannot publish, merge, redirect, delete, noindex, update public profiles or perform outreach.', 'ikon-seo' ); ?></p></div>
		<?php if ( empty( $status['database_ready'] ) ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Reactivate or update Ikon SEO to create the current Portfolio Governance tables.', 'ikon-seo' ); ?></p></div><?php return; endif; ?>

		<div class="ikon-seo-card">
			<h3><?php esc_html_e( 'Managed Website Governance Connection', 'ikon-seo' ); ?></h3>
			<p><?php esc_html_e( 'Generate a separate proposal-only key for the agency command centre. Do not reuse the read-only snapshot key or the private workspace key.', 'ikon-seo' ); ?></p>
			<?php if ( $one_time_key ) : ?><div class="notice notice-success inline"><p><strong><?php esc_html_e( 'Copy this key now:', 'ikon-seo' ); ?></strong></p><p><code style="word-break:break-all"><?php echo esc_html( $one_time_key ); ?></code></p><p><small><?php echo esc_html( $agent['endpoint'] ?? '' ); ?></small></p></div><?php endif; ?>
			<p><?php echo esc_html( ! empty( $agent['configured'] ) ? sprintf( 'Configured · ending %s · created %s', $agent['last4'] ?? '', $agent['created_at'] ?? '' ) : 'No governance proposal key has been generated.' ); ?></p>
			<div style="display:flex;gap:8px;flex-wrap:wrap">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_portfolio_governance_action"><input type="hidden" name="command" value="generate_agent_key"><?php wp_nonce_field( 'ikon_seo_portfolio_governance_action' ); ?><button class="button button-primary"><?php esc_html_e( 'Generate New Proposal Key', 'ikon-seo' ); ?></button></form>
			<?php if ( ! empty( $agent['configured'] ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_portfolio_governance_action"><input type="hidden" name="command" value="revoke_agent_key"><?php wp_nonce_field( 'ikon_seo_portfolio_governance_action' ); ?><button class="button"><?php esc_html_e( 'Revoke Proposal Key', 'ikon-seo' ); ?></button></form><?php endif; ?>
			</div>
		</div>

		<div class="ikon-seo-card">
			<h3><?php esc_html_e( 'Active Local Policy & Compliance', 'ikon-seo' ); ?></h3>
			<?php if ( ! $active ) : ?><p><?php esc_html_e( 'No agency policy is active on this website.', 'ikon-seo' ); ?></p><?php else : ?>
			<p><strong><?php echo esc_html( $active['policy_name'] ?? '' ); ?></strong> <?php echo esc_html( 'v' . absint( $active['policy_version'] ?? 0 ) ); ?> · <code><?php echo esc_html( substr( $active['policy_fingerprint'] ?? '', 0, 16 ) ); ?>…</code></p>
			<div class="ikon-seo-metrics"><div class="ikon-seo-metric"><strong><?php echo absint( $compliance['score'] ?? 0 ); ?>%</strong><span><?php esc_html_e( 'Compliance', 'ikon-seo' ); ?></span></div><div class="ikon-seo-metric"><strong><?php echo absint( $compliance['effective_limits']['minimum_strategy_readiness'] ?? 70 ); ?></strong><span><?php esc_html_e( 'Minimum strategy score', 'ikon-seo' ); ?></span></div><div class="ikon-seo-metric"><strong><?php echo absint( $compliance['effective_limits']['max_safe_batch'] ?? 3 ); ?></strong><span><?php esc_html_e( 'Maximum safe batch', 'ikon-seo' ); ?></span></div></div>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Control', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Purpose', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) ( $compliance['checks'] ?? array() ) as $check ) : ?><tr><td><?php echo esc_html( $check['label'] ?? '' ); ?></td><td><strong><?php echo esc_html( strtoupper( $check['status'] ?? '' ) ); ?></strong></td><td><?php echo esc_html( $check['description'] ?? '' ); ?></td></tr><?php endforeach; ?></tbody></table>
			<?php endif; ?>
		</div>

		<h3><?php esc_html_e( 'Local Policy Inbox', 'ikon-seo' ); ?></h3>
		<?php if ( empty( $report['inbox'] ) ) : ?><p><?php esc_html_e( 'No governance proposals have been received.', 'ikon-seo' ); ?></p><?php endif; ?>
		<?php foreach ( (array) ( $report['inbox'] ?? array() ) as $item ) : $rules=(array)($item['policy']['rules']??array()); ?>
		<div class="ikon-seo-card"><h3><?php echo esc_html( $item['policy_name'] . ' v' . absint( $item['policy_version'] ) ); ?></h3><p><span class="ikon-seo-pill"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $item['status'] ) ) ); ?></span> <?php echo esc_html( 'From ' . $item['source_label'] ); ?></p><p><?php echo esc_html( sprintf( 'Strategy readiness ≥ %d · safe batch ≤ %d · retention %d days', absint( $rules['minimum_strategy_readiness'] ?? 70 ), absint( $rules['max_safe_batch'] ?? 3 ), absint( $rules['data_retention_days'] ?? 365 ) ) ); ?></p><p><code><?php echo esc_html( $item['policy_fingerprint'] ); ?></code></p>
		<?php if ( 'pending_local_approval' === $item['status'] ) : ?><div style="display:flex;gap:8px;flex-wrap:wrap"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_portfolio_governance_action"><input type="hidden" name="command" value="accept_proposal"><input type="hidden" name="proposal_id" value="<?php echo absint( $item['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_portfolio_governance_action' ); ?><input type="text" name="notes" placeholder="Local approval note"><button class="button button-primary"><?php esc_html_e( 'Accept Locally', 'ikon-seo' ); ?></button></form><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_portfolio_governance_action"><input type="hidden" name="command" value="reject_proposal"><input type="hidden" name="proposal_id" value="<?php echo absint( $item['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_portfolio_governance_action' ); ?><input required type="text" name="notes" placeholder="Reason for rejection"><button class="button"><?php esc_html_e( 'Reject', 'ikon-seo' ); ?></button></form></div><?php endif; ?></div>
		<?php endforeach; ?>

		<?php if ( Ikon_SEO_Agency::can_manage() ) : ?>
		<div class="ikon-seo-card"><h3><?php esc_html_e( 'Create Versioned Agency Policy', 'ikon-seo' ); ?></h3><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_portfolio_governance_action"><input type="hidden" name="command" value="create_policy"><?php wp_nonce_field( 'ikon_seo_portfolio_governance_action' ); ?><table class="form-table"><tr><th><?php esc_html_e( 'Policy name', 'ikon-seo' ); ?></th><td><input required class="regular-text" name="name" placeholder="Agency Standard Policy"></td></tr><tr><th><?php esc_html_e( 'Policy key', 'ikon-seo' ); ?></th><td><input name="policy_key" placeholder="agency-standard"></td></tr><tr><th><?php esc_html_e( 'Minimum strategy readiness', 'ikon-seo' ); ?></th><td><input type="number" min="70" max="100" name="minimum_strategy_readiness" value="70"></td></tr><tr><th><?php esc_html_e( 'Maximum safe batch', 'ikon-seo' ); ?></th><td><input type="number" min="1" max="5" name="max_safe_batch" value="3"></td></tr><tr><th><?php esc_html_e( 'Retention', 'ikon-seo' ); ?></th><td><input type="number" min="90" max="1095" name="data_retention_days" value="365"> days</td></tr><tr><th><?php esc_html_e( 'Required gates', 'ikon-seo' ); ?></th><td><label><input type="checkbox" name="require_fact_review" value="1" checked> Fact review</label><br><label><input type="checkbox" name="require_brief_approval" value="1" checked> Brief approval</label><br><label><input type="checkbox" name="require_editorial_review" value="1" checked> Editorial review</label><br><label><input type="checkbox" name="require_publishing_preflight" value="1" checked> Publishing preflight</label><br><label><input type="checkbox" name="require_impact_study" value="1"> Impact study</label></td></tr><tr><th><?php esc_html_e( 'Notes', 'ikon-seo' ); ?></th><td><textarea name="notes" rows="3" class="large-text"></textarea></td></tr></table><button class="button button-primary"><?php esc_html_e( 'Create Draft Policy', 'ikon-seo' ); ?></button></form></div>

		<h3><?php esc_html_e( 'Agency Policies', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Policy', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Controls', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) ( $report['policies'] ?? array() ) as $policy ) : ?><tr><td><strong><?php echo esc_html( $policy['name'] ); ?></strong> <?php echo esc_html( 'v' . absint( $policy['version'] ) ); ?><br><code><?php echo esc_html( substr( $policy['fingerprint'],0,16) ); ?>…</code></td><td><?php echo esc_html( ucfirst( $policy['status'] ) ); ?></td><td><?php if ( 'draft' === $policy['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_portfolio_governance_action"><input type="hidden" name="command" value="approve_policy"><input type="hidden" name="policy_id" value="<?php echo absint( $policy['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_portfolio_governance_action' ); ?><input type="text" name="notes" placeholder="Approval note"><button class="button button-primary"><?php esc_html_e( 'Approve', 'ikon-seo' ); ?></button></form><?php elseif ( 'approved' === $policy['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_portfolio_governance_action"><input type="hidden" name="command" value="retire_policy"><input type="hidden" name="policy_id" value="<?php echo absint( $policy['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_portfolio_governance_action' ); ?><input required type="text" name="notes" placeholder="Retirement reason"><button class="button"><?php esc_html_e( 'Retire', 'ikon-seo' ); ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table>

		<div class="ikon-seo-card"><h3><?php esc_html_e( 'Assign & Synchronise', 'ikon-seo' ); ?></h3><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_portfolio_governance_action"><input type="hidden" name="command" value="save_site_key"><?php wp_nonce_field( 'ikon_seo_portfolio_governance_action' ); ?><select name="site_id" required><option value=""><?php esc_html_e( 'Select managed website', 'ikon-seo' ); ?></option><?php foreach ( (array) ( $agency_report['sites'] ?? array() ) as $site ) : ?><option value="<?php echo absint( $site['id'] ); ?>"><?php echo esc_html( $site['site_name'] . ' — ' . $site['site_url'] ); ?></option><?php endforeach; ?></select> <input required class="regular-text" name="governance_key" placeholder="ikon_governance_…"> <button class="button"><?php esc_html_e( 'Save Proposal Key', 'ikon-seo' ); ?></button></form><hr><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_portfolio_governance_action"><input type="hidden" name="command" value="assign_policy"><?php wp_nonce_field( 'ikon_seo_portfolio_governance_action' ); ?><select name="policy_id" required><option value=""><?php esc_html_e( 'Select approved policy', 'ikon-seo' ); ?></option><?php foreach ( (array) ( $report['policies'] ?? array() ) as $policy ) : if ( 'approved' !== $policy['status'] ) continue; ?><option value="<?php echo absint( $policy['id'] ); ?>"><?php echo esc_html( $policy['name'] . ' v' . $policy['version'] ); ?></option><?php endforeach; ?></select> <select name="site_id" required><option value=""><?php esc_html_e( 'Select managed website', 'ikon-seo' ); ?></option><?php foreach ( (array) ( $agency_report['sites'] ?? array() ) as $site ) : ?><option value="<?php echo absint( $site['id'] ); ?>"><?php echo esc_html( $site['site_name'] ); ?></option><?php endforeach; ?></select> <button class="button button-primary"><?php esc_html_e( 'Assign Policy', 'ikon-seo' ); ?></button></form></div>

		<h3><?php esc_html_e( 'Policy Assignments', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Policy', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Remote status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) ( $report['assignments'] ?? array() ) as $assignment ) : ?><tr><td><?php echo esc_html( $assignment['site_name'] ?: $assignment['site_url'] ); ?></td><td><?php echo esc_html( $assignment['policy_name'] . ' v' . $assignment['policy_version'] ); ?></td><td><?php echo esc_html( ucfirst( str_replace( '_',' ', $assignment['remote_status'] ?: $assignment['status'] ) ) ); ?><?php if ( $assignment['last_error'] ) : ?><br><small><?php echo esc_html( $assignment['last_error'] ); ?></small><?php endif; ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_portfolio_governance_action"><input type="hidden" name="command" value="sync_assignment"><input type="hidden" name="assignment_id" value="<?php echo absint( $assignment['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_portfolio_governance_action' ); ?><button class="button"><?php esc_html_e( 'Send Proposal', 'ikon-seo' ); ?></button></form></td></tr><?php endforeach; ?></tbody></table>
		<?php endif; ?>
		<?php
	}

	private function render_pattern_library() {
		$report = $this->pattern_library->report( array( 'limit' => 100 ) );
		$status = $report['status'] ?? array();
		?>
		<div class="ikon-card">
			<h2><?php esc_html_e( 'Portfolio Learning & Validated Pattern Library', 'ikon-seo' ); ?></h2>
			<p><?php esc_html_e( 'Build context-bounded advisory patterns from human-acknowledged impact studies. A pattern is never automatically validated or applied.', 'ikon-seo' ); ?></p>
			<div class="ikon-grid ikon-grid-4">
				<div><strong><?php echo esc_html( absint( $status['patterns'] ?? 0 ) ); ?></strong><br><?php esc_html_e( 'Patterns', 'ikon-seo' ); ?></div>
				<div><strong><?php echo esc_html( absint( $status['evidence'] ?? 0 ) ); ?></strong><br><?php esc_html_e( 'Evidence records', 'ikon-seo' ); ?></div>
				<div><strong><?php echo esc_html( absint( $status['review_ready'] ?? 0 ) ); ?></strong><br><?php esc_html_e( 'Ready for review', 'ikon-seo' ); ?></div>
				<div><strong><?php echo esc_html( absint( $status['validated'] ?? 0 ) ); ?></strong><br><?php esc_html_e( 'Human validated', 'ikon-seo' ); ?></div>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px"><input type="hidden" name="action" value="ikon_seo_pattern_library_action"><input type="hidden" name="command" value="refresh"><?php wp_nonce_field( 'ikon_seo_pattern_library_refresh_0' ); ?><button class="button button-primary"><?php esc_html_e( 'Refresh Pattern Candidates', 'ikon-seo' ); ?></button></form>
		</div>
		<div class="ikon-card">
			<h3><?php esc_html_e( 'Import anonymised portfolio evidence', 'ikon-seo' ); ?></h3>
			<p><?php esc_html_e( 'Paste a JSON array exported from another Ikon SEO website. URLs, business names, queries, content and contact details are rejected.', 'ikon-seo' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_pattern_library_action"><input type="hidden" name="command" value="import_evidence"><?php wp_nonce_field( 'ikon_seo_pattern_library_import_evidence_0' ); ?><textarea name="records_json" rows="8" class="large-text code" placeholder="[{&quot;source_site_fingerprint&quot;:&quot;...&quot;}]"></textarea><p><button class="button"><?php esc_html_e( 'Import Approved Evidence', 'ikon-seo' ); ?></button></p></form>
		</div>
		<?php foreach ( (array) ( $report['patterns'] ?? array() ) as $pattern ) : ?>
		<div class="ikon-card">
			<h3><?php echo esc_html( $pattern['title'] ?? '' ); ?></h3>
			<p><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $pattern['status'] ?? '' ) ) ); ?></strong> · <?php echo esc_html( ucwords( str_replace( '_', ' ', $pattern['directional_signal'] ?? '' ) ) ); ?> · <?php echo esc_html( ucfirst( $pattern['confidence'] ?? 'low' ) ); ?> confidence</p>
			<p><?php echo esc_html( sprintf( '%d studies (%d usable) across %d anonymised sites (%d usable) · %.1f%% directional consistency · median adjusted change: %s', absint( $pattern['study_count'] ?? 0 ), absint( $pattern['usable_study_count'] ?? 0 ), absint( $pattern['site_count'] ?? 0 ), absint( $pattern['usable_site_count'] ?? 0 ), (float) ( $pattern['consistency_percent'] ?? 0 ), null === ( $pattern['median_change_percent'] ?? null ) ? 'n/a' : number_format_i18n( (float) $pattern['median_change_percent'], 1 ) . '%' ) ); ?></p>
			<p><code><?php echo esc_html( implode( ' / ', array( $pattern['website_mode'] ?? '', $pattern['industry'] ?? '', $pattern['market'] ?? '', $pattern['language'] ?? '', $pattern['page_type'] ?? '', $pattern['change_family'] ?? '', $pattern['primary_metric'] ?? '' ) ) ); ?></code></p>
			<?php if ( ! empty( $pattern['limitations'] ) ) : ?><ul><?php foreach ( $pattern['limitations'] as $limitation ) : ?><li><?php echo esc_html( $limitation ); ?></li><?php endforeach; ?></ul><?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center"><input type="hidden" name="action" value="ikon_seo_pattern_library_action"><input type="hidden" name="pattern_id" value="<?php echo absint( $pattern['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_pattern_library_decision_' . absint( $pattern['id'] ) ); ?><select name="command"><option value="validate">Validate</option><option value="limit">Limited use</option><option value="reject">Reject</option><option value="retire">Retire</option><option value="restore">Restore candidate</option></select><input type="text" name="notes" placeholder="Human review note"><button class="button"><?php esc_html_e( 'Record Decision', 'ikon-seo' ); ?></button></form>
		</div>
		<?php endforeach; ?>
		<div class="ikon-card"><h3><?php esc_html_e( 'Shareable anonymised evidence bundle', 'ikon-seo' ); ?></h3><textarea readonly rows="12" class="large-text code"><?php echo esc_textarea( wp_json_encode( $report['shareable_evidence'] ?? array(), JSON_PRETTY_PRINT ) ); ?></textarea></div>
		<?php
	}

	private function render_agency_service_levels() {
		$report = $this->agency_service_levels->report( array( 'limit' => 100 ) );
		$status = (array) ( $report['status'] ?? array() );
		$metrics = (array) ( $report['metrics'] ?? array() );
		$agency = Ikon_SEO_Agency::can_manage() ? $this->agency_command->summary( 100 ) : array( 'sites' => array() );
		$users = get_users( array( 'fields' => array( 'ID', 'display_name' ), 'number' => 100 ) );
		?>
		<div class="ikon-seo-section-header"><div><h2><?php esc_html_e( 'Agency Service Levels, Capacity & Client Reporting', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Define versioned service plans, allocate bounded monthly capacity, track delivery and prepare evidence-based client reports with separate human approval.', 'ikon-seo' ); ?></p></div><span class="ikon-seo-pill <?php echo ! empty( $status['database_ready'] ) ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ! empty( $status['database_ready'] ) ? 'Manual client delivery only' : 'Database update required' ); ?></span></div>
		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Safety boundary:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'Ikon SEO can prepare and approve a report record, but it cannot email a client, publish content, promise rankings, or change a managed website.', 'ikon-seo' ); ?></p></div>
		<?php if ( empty( $status['database_ready'] ) ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Reactivate or update Ikon SEO to create the v1.15.0 service-level tables.', 'ikon-seo' ); ?></p></div><?php return; endif; ?>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo absint( $metrics['active_assignments'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Active clients', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $metrics['open_items'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Open work items', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $metrics['overdue_items'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Overdue items', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $metrics['review_ready_reports'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Reports to approve', 'ikon-seo' ); ?></span></div>
		</div>

		<?php if ( Ikon_SEO_Agency::can_manage() ) : ?>
		<div class="ikon-seo-grid">
		<div class="ikon-seo-card"><h3><?php esc_html_e( 'Create Service Plan', 'ikon-seo' ); ?></h3><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_agency_service_levels_action"><input type="hidden" name="command" value="create_plan"><?php wp_nonce_field( 'ikon_seo_agency_service_levels_action' ); ?><table class="form-table"><tr><th><?php esc_html_e( 'Name', 'ikon-seo' ); ?></th><td><input required class="regular-text" name="name" placeholder="Managed SEO Growth"></td></tr><tr><th><?php esc_html_e( 'Monthly capacity', 'ikon-seo' ); ?></th><td><input type="number" min="1" max="1000" name="monthly_capacity_units" value="20"> units</td></tr><tr><th><?php esc_html_e( 'Concurrent work', 'ikon-seo' ); ?></th><td><input type="number" min="1" max="100" name="max_concurrent_items" value="5"></td></tr><tr><th><?php esc_html_e( 'Response target', 'ikon-seo' ); ?></th><td><input type="number" min="1" max="720" name="response_target_hours" value="48"> hours</td></tr><tr><th><?php esc_html_e( 'Report cadence', 'ikon-seo' ); ?></th><td><select name="report_cadence"><option>monthly</option><option>fortnightly</option><option>quarterly</option></select></td></tr><tr><th><?php esc_html_e( 'Included deliverables', 'ikon-seo' ); ?></th><td><textarea name="included_deliverables" rows="4" class="large-text" placeholder="Technical review&#10;Content planning&#10;Monthly report"></textarea></td></tr><tr><th><?php esc_html_e( 'Excluded services', 'ikon-seo' ); ?></th><td><textarea name="excluded_services" rows="3" class="large-text" placeholder="Paid advertising&#10;Guaranteed rankings"></textarea></td></tr></table><button class="button button-primary"><?php esc_html_e( 'Create Draft Plan', 'ikon-seo' ); ?></button></form></div>

		<div class="ikon-seo-card"><h3><?php esc_html_e( 'Set Team Capacity', 'ikon-seo' ); ?></h3><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_agency_service_levels_action"><input type="hidden" name="command" value="set_capacity"><?php wp_nonce_field( 'ikon_seo_agency_service_levels_action' ); ?><p><select name="user_id" required><option value=""><?php esc_html_e( 'Select team member', 'ikon-seo' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></p><p><input type="date" name="period_start" value="<?php echo esc_attr( gmdate( 'Y-m-01' ) ); ?>"> to <input type="date" name="period_end" value="<?php echo esc_attr( gmdate( 'Y-m-t' ) ); ?>"></p><p><input type="number" min="1" max="2000" name="capacity_units" value="80"> <?php esc_html_e( 'capacity units', 'ikon-seo' ); ?></p><p><textarea name="notes" class="large-text" rows="3" placeholder="Working days, leave or constraints"></textarea></p><button class="button"><?php esc_html_e( 'Save Capacity', 'ikon-seo' ); ?></button></form></div>
		</div>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Service Plans', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Plan', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Limits', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Controls', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) ( $report['plans'] ?? array() ) as $plan ) : $rules=(array)($plan['plan']??array()); ?><tr><td><strong><?php echo esc_html( $plan['name'] ); ?></strong> <?php echo esc_html( 'v' . absint( $plan['version'] ) ); ?><br><code><?php echo esc_html( substr( $plan['fingerprint'], 0, 14 ) ); ?>…</code></td><td><?php echo esc_html( absint( $rules['monthly_capacity_units'] ?? 0 ) . ' units · ' . absint( $rules['max_concurrent_items'] ?? 0 ) . ' concurrent · ' . absint( $rules['response_target_hours'] ?? 0 ) . 'h response' ); ?></td><td><?php echo esc_html( ucfirst( $plan['status'] ) ); ?></td><td><?php if ( 'draft' === $plan['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_agency_service_levels_action"><input type="hidden" name="command" value="approve_plan"><input type="hidden" name="plan_id" value="<?php echo absint( $plan['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_agency_service_levels_action' ); ?><input required name="notes" placeholder="Approval note"><button class="button button-primary"><?php esc_html_e( 'Approve', 'ikon-seo' ); ?></button></form><?php elseif ( 'approved' === $plan['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_agency_service_levels_action"><input type="hidden" name="command" value="retire_plan"><input type="hidden" name="plan_id" value="<?php echo absint( $plan['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_agency_service_levels_action' ); ?><input required name="notes" placeholder="Retirement reason"><button class="button"><?php esc_html_e( 'Retire', 'ikon-seo' ); ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table>

		<?php if ( Ikon_SEO_Agency::can_manage() ) : ?><div class="ikon-seo-card"><h3><?php esc_html_e( 'Assign Approved Plan', 'ikon-seo' ); ?></h3><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_agency_service_levels_action"><input type="hidden" name="command" value="assign_plan"><?php wp_nonce_field( 'ikon_seo_agency_service_levels_action' ); ?><select name="plan_id" required><option value=""><?php esc_html_e( 'Select approved plan', 'ikon-seo' ); ?></option><?php foreach ( (array) ( $report['plans'] ?? array() ) as $plan ) : if ( 'approved' !== $plan['status'] ) continue; ?><option value="<?php echo absint( $plan['id'] ); ?>"><?php echo esc_html( $plan['name'] . ' v' . $plan['version'] ); ?></option><?php endforeach; ?></select> <select name="site_id" required><option value=""><?php esc_html_e( 'Select managed website', 'ikon-seo' ); ?></option><?php foreach ( (array) ( $agency['sites'] ?? array() ) as $site ) : ?><option value="<?php echo absint( $site['id'] ); ?>"><?php echo esc_html( $site['site_name'] ); ?></option><?php endforeach; ?></select> <input type="date" name="start_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"> <label><input type="checkbox" name="client_reporting_enabled" value="1" checked> <?php esc_html_e( 'Enable client reports', 'ikon-seo' ); ?></label> <button class="button button-primary"><?php esc_html_e( 'Assign Plan', 'ikon-seo' ); ?></button></form></div><?php endif; ?>

		<h3><?php esc_html_e( 'Client Assignments & Work', 'ikon-seo' ); ?></h3>
		<?php foreach ( (array) ( $report['assignments'] ?? array() ) as $assignment ) : ?>
		<div class="ikon-seo-card"><h3><?php echo esc_html( $assignment['site_name'] . ' — ' . ( $assignment['plan']['name'] ?? '' ) ); ?></h3><p><?php echo esc_html( ucfirst( $assignment['status'] ) . ' · ' . absint( $assignment['capacity_units'] ) . ' monthly units · plan v' . absint( $assignment['plan']['version'] ?? 0 ) ); ?></p>
		<?php if ( 'active' === $assignment['status'] ) : ?><div class="ikon-seo-grid"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_agency_service_levels_action"><input type="hidden" name="command" value="create_work_item"><input type="hidden" name="assignment_id" value="<?php echo absint( $assignment['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_agency_service_levels_action' ); ?><h4><?php esc_html_e( 'Add Work Item', 'ikon-seo' ); ?></h4><p><input required class="regular-text" name="title" placeholder="Prepare service-page brief"></p><p><select name="owner_id"><option value="0"><?php esc_html_e( 'Unassigned', 'ikon-seo' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select> <input type="number" min="1" max="1000" name="units" value="2"> units</p><p><input type="datetime-local" name="due_at"></p><button class="button"><?php esc_html_e( 'Create Work Item', 'ikon-seo' ); ?></button></form>
		<?php if ( ! empty( $assignment['client_reporting_enabled'] ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_agency_service_levels_action"><input type="hidden" name="command" value="generate_report"><input type="hidden" name="assignment_id" value="<?php echo absint( $assignment['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_agency_service_levels_action' ); ?><h4><?php esc_html_e( 'Generate Client Report', 'ikon-seo' ); ?></h4><p><input type="date" name="period_start" value="<?php echo esc_attr( gmdate( 'Y-m-01' ) ); ?>"> to <input type="date" name="period_end" value="<?php echo esc_attr( gmdate( 'Y-m-t' ) ); ?>"></p><p><textarea name="client_summary" rows="3" class="large-text" placeholder="Evidence-based executive summary"></textarea></p><p><textarea name="next_actions" rows="3" class="large-text" placeholder="Next approved actions, one per line"></textarea></p><button class="button button-primary"><?php esc_html_e( 'Generate Review-Ready Report', 'ikon-seo' ); ?></button></form><?php endif; ?></div><?php endif; ?>
		</div><?php endforeach; ?>

		<h3><?php esc_html_e( 'Team Capacity', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Team member', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Period', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Capacity', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Allocated', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Utilisation', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) ( $report['capacity'] ?? array() ) as $row ) : ?><tr><td><?php echo esc_html( $row['display_name'] ); ?></td><td><?php echo esc_html( $row['period_start'] . ' — ' . $row['period_end'] ); ?></td><td><?php echo absint( $row['capacity_units'] ); ?></td><td><?php echo absint( $row['allocated_units'] ); ?></td><td><?php echo esc_html( $row['utilisation_percent'] . '%' ); ?></td></tr><?php endforeach; ?></tbody></table>

		<h3><?php esc_html_e( 'Work Queue', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Website / item', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Units', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Due', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Update', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) ( $report['work_items'] ?? array() ) as $item ) : ?><tr><td><strong><?php echo esc_html( $item['title'] ); ?></strong><br><small><?php echo esc_html( $item['site_name'] ); ?></small></td><td><?php echo absint( $item['units'] ); ?></td><td><?php echo esc_html( $item['due_at'] ?: '—' ); ?></td><td><?php echo esc_html( ucfirst( str_replace( '_',' ', $item['status'] ) ) ); ?></td><td><?php if ( ! in_array( $item['status'], array( 'completed','cancelled' ), true ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_agency_service_levels_action"><input type="hidden" name="command" value="update_work_item"><input type="hidden" name="work_item_id" value="<?php echo absint( $item['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_agency_service_levels_action' ); ?><select name="status"><option value="in_progress">In progress</option><option value="awaiting_client">Awaiting client</option><option value="awaiting_approval">Awaiting approval</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select><button class="button"><?php esc_html_e( 'Update', 'ikon-seo' ); ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table>

		<h3><?php esc_html_e( 'Client Reports', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Website / period', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Controls', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) ( $report['reports'] ?? array() ) as $client_report ) : ?><tr><td><strong><?php echo esc_html( $client_report['site_name'] ); ?></strong><br><?php echo esc_html( $client_report['period_start'] . ' — ' . $client_report['period_end'] ); ?></td><td><?php echo esc_html( ucfirst( str_replace( '_',' ', $client_report['status'] ) ) ); ?></td><td><code><?php echo esc_html( substr( $client_report['evidence_fingerprint'], 0, 14 ) ); ?>…</code></td><td><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ikon_seo_download_client_service_report&report_id=' . absint( $client_report['id'] ) ), 'ikon_seo_download_client_service_report_' . absint( $client_report['id'] ) ) ); ?>"><?php esc_html_e( 'Preview HTML', 'ikon-seo' ); ?></a><?php if ( 'review_ready' === $client_report['status'] ) : ?><form style="display:inline" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_agency_service_levels_action"><input type="hidden" name="command" value="approve_report"><input type="hidden" name="report_id" value="<?php echo absint( $client_report['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_agency_service_levels_action' ); ?><input required name="notes" placeholder="Approval note"><button class="button button-primary"><?php esc_html_e( 'Approve', 'ikon-seo' ); ?></button></form><?php elseif ( 'approved' === $client_report['status'] ) : ?><form style="display:inline" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_agency_service_levels_action"><input type="hidden" name="command" value="mark_report_delivered"><input type="hidden" name="report_id" value="<?php echo absint( $client_report['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_agency_service_levels_action' ); ?><select name="method"><option value="manual_email">Manual email</option><option value="manual_portal">Manual portal</option><option value="manual_meeting">Client meeting</option><option value="manual_download">Manual download</option></select><input name="notes" placeholder="Delivery note"><button class="button"><?php esc_html_e( 'Mark Manually Delivered', 'ikon-seo' ); ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table>
		<?php
	}

	private function render_search_impact() {
		$report = $this->search_impact->report( array( 'limit' => 100 ), false );
		$status = (array) ( $report['status'] ?? array() );
		$summary = (array) ( $report['summary'] ?? array() );
		?>
		<div class="ikon-seo-section-header"><div><h2><?php esc_html_e( 'Search Impact & Outcome Attribution', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Compare pre-launch and post-launch first-party evidence, account for comparison pages and confounders, and record a cautious human outcome decision.', 'ikon-seo' ); ?></p></div><span class="ikon-seo-pill <?php echo ! empty( $status['database_ready'] ) ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ! empty( $status['database_ready'] ) ? 'Association, not causation' : 'Database update required' ); ?></span></div>
		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Interpretation boundary:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'Measured movement after a release is an association. Ikon SEO does not claim that the release caused the result and never reverses or expands a page automatically.', 'ikon-seo' ); ?></p></div>
		<?php if ( empty( $status['database_ready'] ) ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Reactivate or update Ikon SEO to create the v1.12.0 Search Impact tables.', 'ikon-seo' ); ?></p></div><?php return; endif; ?>
		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['active'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Active studies', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['ready_for_assessment'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Ready to assess', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['positive_signal'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Positive signals', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['negative_signal'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Negative signals', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $summary['inconclusive'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Inconclusive', 'ikon-seo' ); ?></span></div>
		</div>

		<h3><?php esc_html_e( 'Published Releases Ready for Measurement', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Release', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Measurement plan', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['eligible_releases'] ) ) : ?><tr><td colspan="2"><?php esc_html_e( 'No newly published release is waiting for an impact study.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) ( $report['eligible_releases'] ?? array() ) as $release ) : $release_id = absint( $release['id'] ); ?>
		<tr><td><strong><?php echo esc_html( get_the_title( absint( $release['live_post_id'] ?? 0 ) ) ?: ( 'Release #' . $release_id ) ); ?></strong><br><small><?php echo esc_html( $release['target_url'] ?? '' ); ?></small><br><small><?php echo esc_html( 'Published: ' . ( $release['published_at'] ?? '' ) ); ?></small></td><td>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_search_impact_action"><input type="hidden" name="command" value="create_study"><input type="hidden" name="release_id" value="<?php echo $release_id; ?>"><?php wp_nonce_field( 'ikon_seo_search_impact_create_study_' . $release_id ); ?>
		<label><?php esc_html_e( 'Primary metric', 'ikon-seo' ); ?> <select name="primary_metric"><option value="clicks">Clicks</option><option value="impressions">Impressions</option><option value="position">Average position</option><option value="sessions">Sessions</option><option value="key_events">Key events</option><option value="qualified_leads">Qualified leads</option><option value="customers">Customers</option><option value="revenue">Revenue</option></select></label>
		<label><?php esc_html_e( 'Comparison URL', 'ikon-seo' ); ?> <input type="url" name="comparison_url" placeholder="Optional same-site page"></label>
		<label><?php esc_html_e( 'Evaluate after', 'ikon-seo' ); ?> <select name="evaluation_days"><option value="28">28 days</option><option value="56">56 days</option><option value="90">90 days</option></select></label>
		<button class="button button-primary"><?php esc_html_e( 'Create Impact Study', 'ikon-seo' ); ?></button></form>
		</td></tr><?php endforeach; ?></tbody></table>

		<h3><?php esc_html_e( 'Impact Studies', 'ikon-seo' ); ?></h3>
		<?php if ( empty( $report['studies'] ) ) : ?><p><?php esc_html_e( 'No impact studies have been created.', 'ikon-seo' ); ?></p><?php endif; ?>
		<?php foreach ( (array) ( $report['studies'] ?? array() ) as $study ) : $id = absint( $study['id'] ); $baseline = (array) ( $study['baseline'] ?? array() ); $latest = (array) ( $study['latest'] ?? array() ); $metric = sanitize_key( $study['primary_metric'] ); ?>
		<div class="ikon-seo-card" style="margin:14px 0">
			<div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap"><div><h3 style="margin:0"><?php echo esc_html( $study['title'] ?: ( 'Impact Study #' . $id ) ); ?></h3><p><span class="ikon-seo-pill"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $study['status'] ) ) ); ?></span> <span class="ikon-seo-pill"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $study['outcome'] ) ) ); ?></span></p><p class="description"><?php echo esc_html( $study['target_url'] ); ?></p></div><div><strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $metric ) ) ); ?></strong><br><small><?php echo esc_html( ucfirst( $study['confidence'] ) . ' confidence' ); ?></small><?php if ( null !== $study['adjusted_change_percent'] ) : ?><br><strong><?php echo esc_html( number_format_i18n( (float) $study['adjusted_change_percent'], 1 ) . '% adjusted' ); ?></strong><?php endif; ?></div></div>
			<?php if ( 'blocked' === $study['status'] ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html( $study['blocked_reason'] ); ?></p></div><?php endif; ?>
			<?php if ( $baseline ) : ?><p><?php echo esc_html( sprintf( 'Baseline: %s %s · %d/100 evidence quality.', number_format_i18n( (float) ( $baseline['metrics'][ $metric ] ?? 0 ), 2 ), str_replace( '_', ' ', $metric ), absint( $baseline['quality_score'] ?? 0 ) ) ); ?><?php if ( $latest && 'post_launch' === ( $latest['checkpoint_type'] ?? '' ) ) : ?> <?php echo esc_html( sprintf( 'Latest: %s at day %d · %d/100 quality.', number_format_i18n( (float) ( $latest['metrics'][ $metric ] ?? 0 ), 2 ), absint( $latest['checkpoint_days'] ), absint( $latest['quality_score'] ?? 0 ) ) ); ?><?php endif; ?></p><?php endif; ?>
			<div style="display:flex;gap:8px;flex-wrap:wrap;margin:10px 0">
			<?php if ( 'baseline_pending' === $study['status'] ) : $this->search_impact_command_form( $study, 'capture_baseline', 'Capture Baseline', true ); endif; ?>
			<?php if ( in_array( $study['status'], array( 'monitoring','ready_for_assessment','assessed','acknowledged' ), true ) ) : foreach ( array( 7,28,56,90 ) as $day ) : $exists = false; foreach ( (array) $study['measurements'] as $measurement ) { if ( 'post_launch' === ( $measurement['checkpoint_type'] ?? '' ) && $day === absint( $measurement['checkpoint_days'] ?? 0 ) ) { $exists = true; break; } } if ( ! $exists ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_search_impact_action"><input type="hidden" name="command" value="capture_checkpoint"><input type="hidden" name="study_id" value="<?php echo $id; ?>"><input type="hidden" name="checkpoint_days" value="<?php echo $day; ?>"><?php wp_nonce_field( 'ikon_seo_search_impact_capture_checkpoint_' . $id ); ?><button class="button"><?php echo esc_html( 'Capture Day ' . $day ); ?></button></form>
			<?php endif; endforeach; endif; ?>
			<?php if ( 'ready_for_assessment' === $study['status'] ) : $this->search_impact_command_form( $study, 'assess', 'Assess Outcome', true, true ); endif; ?>
			<?php if ( 'blocked' === $study['status'] ) : $this->search_impact_command_form( $study, 'unblock', 'Unblock', false, true ); elseif ( ! in_array( $study['status'], array( 'archived' ), true ) ) : $this->search_impact_command_form( $study, 'block', 'Block Study', false, true ); endif; ?>
			</div>
			<?php if ( 'assessed' === $study['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><input type="hidden" name="action" value="ikon_seo_search_impact_action"><input type="hidden" name="command" value="acknowledge"><input type="hidden" name="study_id" value="<?php echo $id; ?>"><?php wp_nonce_field( 'ikon_seo_search_impact_acknowledge_' . $id ); ?><select name="decision"><option value="retain">Retain</option><option value="expand_carefully">Expand carefully</option><option value="continue_monitoring">Continue monitoring</option><option value="investigate">Investigate</option><option value="consider_revision">Consider controlled revision</option><option value="no_action">No action</option></select><input type="text" name="notes" placeholder="Decision note"><button class="button button-primary"><?php esc_html_e( 'Record Human Decision', 'ikon-seo' ); ?></button></form><?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px"><input type="hidden" name="action" value="ikon_seo_search_impact_action"><input type="hidden" name="command" value="add_confounder"><input type="hidden" name="study_id" value="<?php echo $id; ?>"><?php wp_nonce_field( 'ikon_seo_search_impact_add_confounder_' . $id ); ?><select name="confounder_type"><option value="seasonality">Seasonality</option><option value="algorithm_update">Algorithm update</option><option value="sitewide_change">Sitewide change</option><option value="tracking_change">Tracking change</option><option value="campaign">Paid or other campaign</option><option value="pricing">Pricing change</option><option value="availability">Availability change</option><option value="competitor_change">Competitor change</option><option value="other">Other</option></select><input type="text" required name="notes" placeholder="Describe a possible confounder"><button class="button"><?php esc_html_e( 'Record Confounder', 'ikon-seo' ); ?></button></form>
			<details><summary><strong><?php esc_html_e( 'Evidence, limitations and event history', 'ikon-seo' ); ?></strong></summary><?php if ( ! empty( $study['assessment']['language'] ) ) : ?><p><strong><?php echo esc_html( $study['assessment']['language'] ); ?></strong></p><?php endif; ?><ul><?php foreach ( (array) ( $latest['limitations'] ?? $baseline['limitations'] ?? array() ) as $limitation ) : ?><li><?php echo esc_html( $limitation ); ?></li><?php endforeach; ?></ul><ol><?php foreach ( array_slice( (array) $study['events'], 0, 20 ) as $event ) : ?><li><strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $event['type'] ) ) ); ?></strong> — <?php echo esc_html( $event['notes'] ); ?> <small><?php echo esc_html( $event['created_at'] ); ?></small></li><?php endforeach; ?></ol></details>
		</div>
		<?php endforeach; ?>
		<?php
	}

	private function search_impact_command_form( array $study, $command, $label, $primary = false, $notes = false ) {
		$id = absint( $study['id'] );
		?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_search_impact_action"><input type="hidden" name="command" value="<?php echo esc_attr( $command ); ?>"><input type="hidden" name="study_id" value="<?php echo $id; ?>"><?php wp_nonce_field( 'ikon_seo_search_impact_' . $command . '_' . $id ); ?><?php if ( $notes ) : ?><input type="text" name="notes" placeholder="Optional note"><?php endif; ?><button class="button <?php echo $primary ? 'button-primary' : ''; ?>"><?php echo esc_html( $label ); ?></button></form><?php
	}

	private function render_content_intelligence() {
		$status = $this->competitor_content->status();
		$report = $this->competitor_content->report( 100, false );
		$posts  = get_posts(
			array(
				'post_type'      => get_post_types( array( 'public' => true ), 'names' ),
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 300,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Competitor & Content Intelligence', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Store current competitor observations, classify search intent, compare page coverage and build evidence-based differentiation briefs without copying competitor content.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo $status['research_items'] ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( $status['research_items'] ? sprintf( '%s observations', number_format_i18n( $status['research_items'] ) ) : 'No research stored' ); ?></span>
		</div>

		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Research safety:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'Ikon SEO does not scrape Google Search. Add evidence from current web research, an approved provider or manual review. Competitor claims must never be reused as facts about this business.', 'ikon-seo' ); ?></p></div>

		<?php if ( is_wp_error( $report ) ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( $report->get_error_message() ); ?></p></div>
		<?php else : ?>
			<div class="ikon-seo-metrics">
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['queries'] ) ); ?></strong><span><?php esc_html_e( 'Researched queries', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['domains'] ) ); ?></strong><span><?php esc_html_e( 'Competitor domains', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['briefs'] ) ); ?></strong><span><?php esc_html_e( 'Stored page briefs', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $report['summary']['high_priority_gaps'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'High-priority gaps', 'ikon-seo' ); ?></span></div>
			</div>
		<?php endif; ?>

		<div class="ikon-seo-grid ikon-seo-grid-2">
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Add competitor evidence', 'ikon-seo' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Store one reviewed page. Use short factual observations rather than copied paragraphs.', 'ikon-seo' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_competitor_research">
					<?php wp_nonce_field( 'ikon_seo_save_competitor_research' ); ?>
					<table class="form-table" role="presentation">
						<tr><th><label for="cc_query"><?php esc_html_e( 'Search query', 'ikon-seo' ); ?></label></th><td><input class="regular-text" required id="cc_query" name="query"></td></tr>
						<tr><th><label for="cc_url"><?php esc_html_e( 'Competitor page URL', 'ikon-seo' ); ?></label></th><td><input class="large-text code" required type="url" id="cc_url" name="url"></td></tr>
						<tr><th><?php esc_html_e( 'Intent', 'ikon-seo' ); ?></th><td><select name="intent"><option value="mixed">Mixed / uncertain</option><option value="local_service">Local service</option><option value="transactional">Transactional</option><option value="commercial_investigation">Commercial investigation</option><option value="informational">Informational</option><option value="navigational">Navigational</option></select></td></tr>
						<tr><th><?php esc_html_e( 'Result type', 'ikon-seo' ); ?></th><td><select name="result_type"><option value="mixed_results">Mixed results</option><option value="service_page">Service page</option><option value="location_page">Location page</option><option value="article">Article</option><option value="comparison_page">Comparison page</option><option value="category_page">Category page</option><option value="product_or_booking_page">Product or booking page</option><option value="homepage">Homepage</option><option value="tool">Tool</option><option value="video">Video</option></select></td></tr>
						<tr><th><label for="cc_title"><?php esc_html_e( 'Page title', 'ikon-seo' ); ?></label></th><td><input class="large-text" id="cc_title" name="title"></td></tr>
						<tr><th><label for="cc_h1"><?php esc_html_e( 'Main heading', 'ikon-seo' ); ?></label></th><td><input class="large-text" id="cc_h1" name="h1"></td></tr>
						<tr><th><label for="cc_headings"><?php esc_html_e( 'Section headings', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="cc_headings" name="headings" placeholder="One heading per line"></textarea></td></tr>
						<tr><th><label for="cc_topics"><?php esc_html_e( 'Important topics', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="cc_topics" name="topics" placeholder="One topic per line"></textarea></td></tr>
						<tr><th><label for="cc_entities"><?php esc_html_e( 'Relevant entities', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="cc_entities" name="entities" placeholder="Services, locations, products or concepts"></textarea></td></tr>
						<tr><th><label for="cc_trust"><?php esc_html_e( 'Proof and trust patterns', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="cc_trust" name="trust_elements" placeholder="Case studies, credentials, real photos, policies"></textarea></td></tr>
						<tr><th><label for="cc_conversion"><?php esc_html_e( 'Conversion patterns', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="3" id="cc_conversion" name="conversion_elements" placeholder="Quote form, booking, call, pricing"></textarea></td></tr>
						<tr><th><label for="cc_features"><?php esc_html_e( 'Search-result features', 'ikon-seo' ); ?></label></th><td><input class="large-text" id="cc_features" name="search_features" placeholder="Local pack, images, videos, snippets"></td></tr>
						<tr><th><label for="cc_notes"><?php esc_html_e( 'Evidence notes', 'ikon-seo' ); ?></label></th><td><textarea class="large-text" rows="4" id="cc_notes" name="evidence_notes"></textarea></td></tr>
						<tr><th><label for="cc_observed"><?php esc_html_e( 'Observed date', 'ikon-seo' ); ?></label></th><td><input type="date" id="cc_observed" name="observed_at" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"></td></tr>
					</table>
					<?php submit_button( __( 'Store competitor evidence', 'ikon-seo' ), 'secondary' ); ?>
				</form>
			</section>

			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Build a page brief', 'ikon-seo' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Compare one WordPress page with stored evidence for a target query. The report separates direct observations from hypotheses.', 'ikon-seo' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_analyse_content_page">
					<?php wp_nonce_field( 'ikon_seo_analyse_content_page' ); ?>
					<table class="form-table" role="presentation">
						<tr><th><label for="cc_post_id"><?php esc_html_e( 'Page or post', 'ikon-seo' ); ?></label></th><td><select required id="cc_post_id" name="post_id"><option value=""><?php esc_html_e( 'Select content', 'ikon-seo' ); ?></option><?php foreach ( $posts as $post ) : ?><option value="<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $post->post_title . ' [' . $post->post_type . ' · ' . $post->post_status . ']' ); ?></option><?php endforeach; ?></select></td></tr>
						<tr><th><label for="cc_target_query"><?php esc_html_e( 'Target query', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="cc_target_query" name="target_query"><p class="description"><?php esc_html_e( 'Leave blank to use the Rank Math focus keyword or page title.', 'ikon-seo' ); ?></p></td></tr>
						<tr><th><?php esc_html_e( 'Target intent', 'ikon-seo' ); ?></th><td><select name="intent"><option value="">Infer from query</option><option value="local_service">Local service</option><option value="transactional">Transactional</option><option value="commercial_investigation">Commercial investigation</option><option value="informational">Informational</option><option value="navigational">Navigational</option><option value="mixed">Mixed</option></select></td></tr>
					</table>
					<?php submit_button( __( 'Create evidence-based brief', 'ikon-seo' ), 'primary' ); ?>
				</form>
			</section>
		</div>

		<?php if ( is_array( $report ) ) : ?>
			<h3><?php esc_html_e( 'Page-level content briefs', 'ikon-seo' ); ?></h3>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Page and query', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Intent', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Gap priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Leading requirements', 'ikon-seo' ); ?></th></tr></thead>
				<tbody>
				<?php if ( ! empty( $report['content_briefs'] ) ) : foreach ( array_slice( $report['content_briefs'], 0, 50 ) as $brief ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( $brief['page_url'] ); ?>" target="_blank" rel="noopener"><strong><?php echo esc_html( $brief['page_title'] ); ?></strong></a><br><small><?php echo esc_html( $brief['target_query'] ); ?></small></td>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $brief['target_intent'] ) ) ); ?><br><small><?php echo esc_html( 'Alignment: ' . str_replace( '_', ' ', $brief['intent_alignment'] ) ); ?></small></td>
						<td><?php echo esc_html( sprintf( '%d competitor pages · %s confidence', $brief['competitor_count'], $brief['evidence_confidence'] ) ); ?><br><small><?php echo null === $brief['topic_coverage'] ? esc_html__( 'Coverage unavailable', 'ikon-seo' ) : esc_html( number_format_i18n( $brief['topic_coverage'], 0 ) . '% recurring-topic coverage' ); ?></small></td>
						<td><strong><?php echo esc_html( absint( $brief['gap_priority'] ) . '/100' ); ?></strong></td>
						<td><?php foreach ( array_slice( (array) $brief['requirements'], 0, 3 ) as $requirement ) : ?><div>• <?php echo esc_html( $requirement ); ?></div><?php endforeach; ?></td>
					</tr>
				<?php endforeach; else : ?><tr><td colspan="5"><?php esc_html_e( 'No page brief has been created yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Topical coverage map', 'ikon-seo' ); ?></h3>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Topic', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Source', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Page coverage', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Gap priority', 'ikon-seo' ); ?></th></tr></thead>
				<tbody>
				<?php if ( ! empty( $report['topic_map'] ) ) : foreach ( array_slice( $report['topic_map'], 0, 60 ) as $node ) : ?>
					<tr><td><strong><?php echo esc_html( $node['topic'] ); ?></strong><?php if ( ! empty( $node['intent'] ) ) : ?><br><small><?php echo esc_html( ucwords( str_replace( '_', ' ', $node['intent'] ) ) ); ?></small><?php endif; ?></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $node['source'] ) ) ); ?></td><td><?php if ( ! empty( $node['page'] ) ) : ?><a href="<?php echo esc_url( $node['page'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $node['page'], PHP_URL_PATH ) ?: '/' ); ?></a><?php else : ?><span class="ikon-seo-pill is-failed"><?php esc_html_e( 'No mapped page', 'ikon-seo' ); ?></span><?php endif; ?></td><td><?php echo esc_html( absint( $node['gap_priority'] ?? 0 ) . '/100' ); ?></td></tr>
				<?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e( 'Connect Search Console or create content briefs to build the topic map.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Stored competitor observations', 'ikon-seo' ); ?></h3>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Query', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Competitor page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Intent and type', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead>
				<tbody>
				<?php if ( ! empty( $report['research'] ) ) : foreach ( array_slice( $report['research'], 0, 100 ) as $item ) : ?>
					<tr><td><strong><?php echo esc_html( $item['query_text'] ); ?></strong><br><small><?php echo esc_html( $item['observed_at'] ); ?></small></td><td><a href="<?php echo esc_url( $item['competitor_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $item['page_title'] ?: $item['competitor_domain'] ); ?></a><br><small><?php echo esc_html( $item['competitor_domain'] ); ?></small></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['intent'] ) ) ); ?><br><small><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['result_type'] ) ) ); ?></small></td><td><?php echo esc_html( sprintf( '%d headings · %d topics · %d entities', count( $item['headings'] ), count( $item['topics'] ), count( $item['entities'] ) ) ); ?><br><small><?php echo esc_html( $item['evidence_source'] ); ?></small></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_archive_competitor_research"><input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_archive_competitor_research' ); ?><button class="button button-small" type="submit" data-confirm="<?php esc_attr_e( 'Archive this competitor observation?', 'ikon-seo' ); ?>"><?php esc_html_e( 'Archive', 'ikon-seo' ); ?></button></form></td></tr>
				<?php endforeach; else : ?><tr><td colspan="5"><?php esc_html_e( 'No competitor evidence is stored yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}



	private function render_governance() {
		$report   = $this->structured_media_governance->report( 100 );
		$status   = (array) ( $report['status'] ?? array() );
		$schema   = (array) ( $report['schema'] ?? array() );
		$media    = (array) ( $report['media'] ?? array() );
		$schema_status = (array) ( $schema['status'] ?? array() );
		$media_status  = (array) ( $media['status'] ?? array() );
		$settings = Ikon_SEO_Plugin::settings();
		$agency   = Ikon_SEO_Agency::can_manage();
		?>
		<section class="ikon-seo-card">
			<h2><?php esc_html_e( 'Structured Data & Media Governance', 'ikon-seo' ); ?></h2>
			<p><?php esc_html_e( 'Review rendered structured data, image quality, page-media gaps and source records. The module records evidence and recommendations without changing front-end markup, media files or published pages.', 'ikon-seo' ); ?></p>
			<div class="ikon-seo-stat-grid">
				<div><strong><?php echo absint( $schema_status['audited_pages'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Pages reviewed', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo absint( $schema_status['pages_with_errors'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Schema errors', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo absint( $media_status['audited_assets'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Images reviewed', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo absint( $media_status['assets_with_issues'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Media issues', 'ikon-seo' ); ?></span></div>
			</div>
			<p><span class="ikon-seo-pill <?php echo ! empty( $status['enabled'] ) ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ! empty( $status['enabled'] ) ? 'Scheduled review enabled' : 'Scheduled review disabled' ); ?></span></p>
		</section>

		<?php if ( $agency ) : ?>
		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Governance policy', 'ikon-seo' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_save_governance_settings">
				<?php wp_nonce_field( 'ikon_seo_save_governance_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Scheduled reviews', 'ikon-seo' ); ?></th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['structured_media_governance_enabled'] ) ); ?>> <?php esc_html_e( 'Enable weekly bounded review batches', 'ikon-seo' ); ?></label></td></tr>
					<tr><th><label for="schema-batch-size"><?php esc_html_e( 'Schema batch size', 'ikon-seo' ); ?></label></th><td><input id="schema-batch-size" type="number" min="1" max="100" name="schema_batch_size" value="<?php echo absint( $settings['schema_governance_batch_size'] ?? 10 ); ?>"></td></tr>
					<tr><th><label for="schema-stale-days"><?php esc_html_e( 'Schema evidence age', 'ikon-seo' ); ?></label></th><td><input id="schema-stale-days" type="number" min="1" max="365" name="schema_stale_days" value="<?php echo absint( $settings['schema_governance_stale_days'] ?? 30 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td></tr>
					<tr><th><label for="media-batch-size"><?php esc_html_e( 'Media batch size', 'ikon-seo' ); ?></label></th><td><input id="media-batch-size" type="number" min="1" max="500" name="media_batch_size" value="<?php echo absint( $settings['media_governance_batch_size'] ?? 50 ); ?>"></td></tr>
					<tr><th><label for="media-stale-days"><?php esc_html_e( 'Media evidence age', 'ikon-seo' ); ?></label></th><td><input id="media-stale-days" type="number" min="1" max="365" name="media_stale_days" value="<?php echo absint( $settings['media_governance_stale_days'] ?? 30 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td></tr>
					<tr><th><label for="large-file-kb"><?php esc_html_e( 'Large image warning', 'ikon-seo' ); ?></label></th><td><input id="large-file-kb" type="number" min="100" max="10000" name="large_file_kb" value="<?php echo absint( $settings['media_governance_large_file_kb'] ?? 500 ); ?>"> KB</td></tr>
					<tr><th><label for="alt-max-chars"><?php esc_html_e( 'Alt-text review length', 'ikon-seo' ); ?></label></th><td><input id="alt-max-chars" type="number" min="60" max="300" name="alt_max_chars" value="<?php echo absint( $settings['media_governance_alt_max_chars'] ?? 160 ); ?>"> <?php esc_html_e( 'characters', 'ikon-seo' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Source records', 'ikon-seo' ); ?></th><td><label><input type="checkbox" name="require_source_records" value="1" <?php checked( ! empty( $settings['media_governance_require_source_records'] ) ); ?>> <?php esc_html_e( 'Warn when an image has no source record', 'ikon-seo' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Duplicate detection', 'ikon-seo' ); ?></th><td><label><input type="checkbox" name="file_hashes" value="1" <?php checked( ! empty( $settings['media_governance_file_hashes'] ) ); ?>> <?php esc_html_e( 'Calculate bounded local file hashes', 'ikon-seo' ); ?></label></td></tr>
					<tr><th><label for="governance-retention"><?php esc_html_e( 'Evidence retention', 'ikon-seo' ); ?></label></th><td><input id="governance-retention" type="number" min="30" max="730" name="retention_days" value="<?php echo absint( $settings['governance_retention_days'] ?? 180 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td></tr>
				</table>
				<?php submit_button( __( 'Save governance policy', 'ikon-seo' ) ); ?>
			</form>
		</section>

		<div class="ikon-seo-grid ikon-seo-grid-2">
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Run structured-data review', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_run_schema_governance">
					<?php wp_nonce_field( 'ikon_seo_run_schema_governance' ); ?>
					<p><label><?php esc_html_e( 'Pages', 'ikon-seo' ); ?> <input type="number" name="limit" min="1" max="100" value="3"></label></p>
					<p><label><input type="checkbox" name="force" value="1"> <?php esc_html_e( 'Recheck previously reviewed pages', 'ikon-seo' ); ?></label></p>
					<?php submit_button( __( 'Review structured data', 'ikon-seo' ), 'secondary' ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_audit_governance_url">
					<?php wp_nonce_field( 'ikon_seo_audit_governance_url' ); ?>
					<p><label><?php esc_html_e( 'Same-site URL', 'ikon-seo' ); ?><br><input class="large-text" type="url" name="url" required></label></p>
					<?php submit_button( __( 'Review one URL', 'ikon-seo' ), 'secondary' ); ?>
				</form>
			</section>
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Run media review', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_run_media_governance">
					<?php wp_nonce_field( 'ikon_seo_run_media_governance' ); ?>
					<p><label><?php esc_html_e( 'Images', 'ikon-seo' ); ?> <input type="number" name="limit" min="1" max="500" value="10"></label></p>
					<p><label><input type="checkbox" name="force" value="1"> <?php esc_html_e( 'Recheck previously reviewed images', 'ikon-seo' ); ?></label></p>
					<?php submit_button( __( 'Review media', 'ikon-seo' ), 'secondary' ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_cleanup_governance">
					<?php wp_nonce_field( 'ikon_seo_cleanup_governance' ); ?>
					<?php submit_button( __( 'Clean expired evidence', 'ikon-seo' ), 'secondary' ); ?>
				</form>
			</section>
		</div>

		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Save an image source and rights record', 'ikon-seo' ); ?></h3>
			<p class="description"><?php esc_html_e( 'This records editorial provenance only. It does not alter the image, alt text or published pages.', 'ikon-seo' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_save_media_rights">
				<?php wp_nonce_field( 'ikon_seo_save_media_rights' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><label for="rights-attachment-id"><?php esc_html_e( 'Attachment ID', 'ikon-seo' ); ?></label></th><td><input id="rights-attachment-id" type="number" min="1" name="attachment_id" required></td></tr>
					<tr><th><label for="rights-source-type"><?php esc_html_e( 'Source type', 'ikon-seo' ); ?></label></th><td><select id="rights-source-type" name="source_type"><option value="original">Original</option><option value="licensed">Licensed</option><option value="client_supplied">Client supplied</option><option value="generated">Generated</option><option value="public_domain">Public domain</option><option value="unknown">Unknown</option></select></td></tr>
					<tr><th><label for="rights-source-url"><?php esc_html_e( 'Source URL', 'ikon-seo' ); ?></label></th><td><input id="rights-source-url" class="large-text" type="url" name="source_url"></td></tr>
					<tr><th><label for="rights-license-name"><?php esc_html_e( 'License name', 'ikon-seo' ); ?></label></th><td><input id="rights-license-name" class="regular-text" type="text" name="license_name"></td></tr>
					<tr><th><label for="rights-license-url"><?php esc_html_e( 'License URL', 'ikon-seo' ); ?></label></th><td><input id="rights-license-url" class="large-text" type="url" name="license_url"></td></tr>
					<tr><th><label for="rights-creator"><?php esc_html_e( 'Creator or supplier', 'ikon-seo' ); ?></label></th><td><input id="rights-creator" class="regular-text" type="text" name="creator"></td></tr>
					<tr><th><label for="rights-notes"><?php esc_html_e( 'Rights notes', 'ikon-seo' ); ?></label></th><td><textarea id="rights-notes" class="large-text" rows="3" name="rights_notes"></textarea></td></tr>
				</table>
				<?php submit_button( __( 'Save source record', 'ikon-seo' ), 'secondary' ); ?>
			</form>
		</section>
		<?php endif; ?>

		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Structured-data findings', 'ikon-seo' ); ?></h3>
			<?php if ( empty( $schema['items'] ) ) : ?>
				<p><?php esc_html_e( 'No structured-data evidence has been stored yet.', 'ikon-seo' ); ?></p>
			<?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'URL', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Detected types', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Findings', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Checked', 'ikon-seo' ); ?></th></tr></thead><tbody>
			<?php foreach ( (array) $schema['items'] as $item ) : ?>
				<tr><td><a href="<?php echo esc_url( $item['url'] ?? '' ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_parse_url( $item['url'] ?? '', PHP_URL_PATH ) ?: $item['url'] ); ?></a></td><td><?php echo esc_html( implode( ', ', (array) ( $item['detected_types'] ?? array() ) ) ?: '—' ); ?></td><td><strong><?php echo absint( $item['error_count'] ?? 0 ); ?></strong> <?php esc_html_e( 'errors', 'ikon-seo' ); ?> · <strong><?php echo absint( $item['warning_count'] ?? 0 ); ?></strong> <?php esc_html_e( 'warnings', 'ikon-seo' ); ?><br><?php foreach ( array_slice( (array) ( $item['issues'] ?? array() ), 0, 3 ) as $issue ) : ?><span class="description"><?php echo esc_html( $issue['message'] ?? '' ); ?></span><br><?php endforeach; ?></td><td><?php echo esc_html( $item['checked_at'] ?? '' ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>
		</section>

		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Media findings', 'ikon-seo' ); ?></h3>
			<?php if ( empty( $media['items'] ) ) : ?>
				<p><?php esc_html_e( 'No media evidence has been stored yet.', 'ikon-seo' ); ?></p>
			<?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Image', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Usage', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Source', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Findings', 'ikon-seo' ); ?></th></tr></thead><tbody>
			<?php foreach ( (array) $media['items'] as $item ) : ?>
				<tr><td><?php if ( ! empty( $item['edit_url'] ) ) : ?><a href="<?php echo esc_url( $item['edit_url'] ); ?>"><?php echo esc_html( $item['filename'] ?? '' ); ?></a><?php else : ?><?php echo esc_html( $item['filename'] ?? '' ); ?><?php endif; ?><br><span class="description"><?php echo esc_html( absint( $item['width'] ?? 0 ) . '×' . absint( $item['height'] ?? 0 ) . ' · ' . size_format( absint( $item['file_size'] ?? 0 ) ) ); ?></span></td><td><?php echo absint( $item['usage_count'] ?? 0 ); ?></td><td><?php echo esc_html( $item['source_type'] ?? 'unknown' ); ?></td><td><strong><?php echo absint( $item['issue_count'] ?? 0 ); ?></strong><br><?php foreach ( array_slice( (array) ( $item['issues'] ?? array() ), 0, 3 ) as $issue ) : ?><span class="description"><?php echo esc_html( $issue['message'] ?? '' ); ?></span><br><?php endforeach; ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>
		</section>

		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Page-media gaps', 'ikon-seo' ); ?></h3>
			<?php if ( empty( $media['page_gaps'] ) ) : ?><p><?php esc_html_e( 'No stored page-media gaps were found in the reviewed set.', 'ikon-seo' ); ?></p><?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Gap evidence', 'ikon-seo' ); ?></th></tr></thead><tbody>
			<?php foreach ( (array) $media['page_gaps'] as $gap ) : ?><tr><td><a href="<?php echo esc_url( $gap['url'] ?? '' ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $gap['title'] ?? '' ); ?></a></td><td><?php echo esc_html( implode( ', ', (array) ( $gap['gap_codes'] ?? array() ) ) ); ?></td></tr><?php endforeach; ?>
			</tbody></table><?php endif; ?>
		</section>
		<?php
	}


	private function render_experiments_claims_revenue() {
		$report = $this->experiments_claims_revenue->report( 100 );
		$status = (array) ( $report['status'] ?? array() );
		$experiments = (array) ( $report['experiments'] ?? array() );
		$claims = (array) ( $report['claims'] ?? array() );
		$revenue = (array) ( $report['revenue'] ?? array() );
		$quality = (array) ( $report['data_quality'] ?? array() );
		$settings = Ikon_SEO_Plugin::settings();
		$agency = Ikon_SEO_Agency::can_manage();
		$experiment_counts = (array) ( $status['experiments'] ?? array() );
		$claim_counts = (array) ( $status['claims'] ?? array() );
		?>
		<section class="ikon-seo-card">
			<h2><?php esc_html_e( 'Experiments, Claims & Revenue', 'ikon-seo' ); ?></h2>
			<p><?php esc_html_e( 'Plan controlled SEO tests, verify important content claims and connect landing pages with privacy-preserving lead or revenue evidence. No public content, CRM record or customer communication is changed automatically.', 'ikon-seo' ); ?></p>
			<div class="ikon-seo-stat-grid">
				<div><strong><?php echo absint( ( $experiment_counts['approved'] ?? 0 ) + ( $experiment_counts['running'] ?? 0 ) + ( $experiment_counts['monitoring'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Active experiments', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo absint( $status['measurements'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Measurements', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo absint( $status['claims_due'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Claims due', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo esc_html( ( $status['currency'] ?? 'USD' ) . ' ' . number_format_i18n( (float) ( $status['attributed_value'] ?? 0 ), 2 ) ); ?></strong><span><?php esc_html_e( 'Attributed value', 'ikon-seo' ); ?></span></div>
			</div>
			<p><span class="ikon-seo-pill <?php echo absint( $quality['score'] ?? 0 ) >= 70 ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( sprintf( 'Evidence quality %d%%', absint( $quality['score'] ?? 0 ) ) ); ?></span></p>
			<?php foreach ( (array) ( $quality['issues'] ?? array() ) as $issue ) : ?><p class="description">• <?php echo esc_html( $issue ); ?></p><?php endforeach; ?>
		</section>

		<?php if ( $agency ) : ?>
		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Measurement and evidence policy', 'ikon-seo' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_save_ecr_settings">
				<?php wp_nonce_field( 'ikon_seo_save_ecr_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Module', 'ikon-seo' ); ?></th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['experiments_claims_revenue_enabled'] ) ); ?>> <?php esc_html_e( 'Enable scheduled evidence maintenance', 'ikon-seo' ); ?></label></td></tr>
					<tr><th><label><?php esc_html_e( 'Minimum experiment days', 'ikon-seo' ); ?></label></th><td><input type="number" min="7" max="180" name="experiment_minimum_days" value="<?php echo absint( $settings['experiment_minimum_days'] ?? 28 ); ?>"></td></tr>
					<tr><th><label><?php esc_html_e( 'Minimum observations', 'ikon-seo' ); ?></label></th><td><input type="number" min="1" max="100000" name="experiment_minimum_observations" value="<?php echo absint( $settings['experiment_minimum_observations'] ?? 100 ); ?>"></td></tr>
					<tr><th><label><?php esc_html_e( 'Material-change threshold', 'ikon-seo' ); ?></label></th><td><input type="number" min="1" max="100" step="0.1" name="experiment_change_threshold_percent" value="<?php echo esc_attr( $settings['experiment_change_threshold_percent'] ?? 10 ); ?>">%</td></tr>
					<tr><th><label><?php esc_html_e( 'Standard claim review', 'ikon-seo' ); ?></label></th><td><input type="number" min="7" max="730" name="claim_default_review_days" value="<?php echo absint( $settings['claim_default_review_days'] ?? 180 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td></tr>
					<tr><th><label><?php esc_html_e( 'High-risk claim review', 'ikon-seo' ); ?></label></th><td><input type="number" min="1" max="365" name="claim_high_risk_review_days" value="<?php echo absint( $settings['claim_high_risk_review_days'] ?? 30 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td></tr>
					<tr><th><label><?php esc_html_e( 'Default currency', 'ikon-seo' ); ?></label></th><td><input type="text" maxlength="3" size="4" name="revenue_default_currency" value="<?php echo esc_attr( $settings['revenue_default_currency'] ?? 'USD' ); ?>"></td></tr>
					<tr><th><label><?php esc_html_e( 'Revenue reporting window', 'ikon-seo' ); ?></label></th><td><input type="number" min="7" max="365" name="revenue_reporting_days" value="<?php echo absint( $settings['revenue_reporting_days'] ?? 30 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td></tr>
					<tr><th><label><?php esc_html_e( 'Evidence retention', 'ikon-seo' ); ?></label></th><td><input type="number" min="90" max="1825" name="retention_days" value="<?php echo absint( $settings['experiments_claims_revenue_retention_days'] ?? 730 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td></tr>
				</table>
				<?php submit_button( __( 'Save policy', 'ikon-seo' ) ); ?>
			</form>
		</section>

		<div class="ikon-seo-grid ikon-seo-grid-2">
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Create controlled experiment', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_create_experiment">
					<?php wp_nonce_field( 'ikon_seo_create_experiment' ); ?>
					<p><label><?php esc_html_e( 'Title', 'ikon-seo' ); ?><br><input class="large-text" type="text" name="title" required></label></p>
					<p><label><?php esc_html_e( 'Hypothesis', 'ikon-seo' ); ?><br><textarea class="large-text" rows="3" name="hypothesis" required></textarea></label></p>
					<p><label><?php esc_html_e( 'Change type', 'ikon-seo' ); ?><br><select name="change_type"><option value="content">Content</option><option value="title">Title</option><option value="internal_links">Internal links</option><option value="schema">Structured data</option><option value="template">Template</option><option value="media">Media</option><option value="conversion">Conversion</option><option value="technical">Technical</option><option value="other">Other</option></select></label></p>
					<p><label><?php esc_html_e( 'Primary metric', 'ikon-seo' ); ?><br><select name="primary_metric"><option value="clicks">Clicks</option><option value="impressions">Impressions</option><option value="ctr">CTR</option><option value="position">Average position</option><option value="sessions">Sessions</option><option value="key_events">Key events</option><option value="qualified_leads">Qualified leads</option><option value="customers">Customers</option><option value="revenue">Revenue</option></select></label></p>
					<p><label><?php esc_html_e( 'Test URLs — one per line', 'ikon-seo' ); ?><br><textarea class="large-text code" rows="4" name="test_urls" required></textarea></label></p>
					<p><label><?php esc_html_e( 'Comparison URLs — one per line', 'ikon-seo' ); ?><br><textarea class="large-text code" rows="4" name="comparison_urls"></textarea></label></p>
					<p><label><?php esc_html_e( 'Minimum days', 'ikon-seo' ); ?> <input type="number" min="7" max="180" name="minimum_days" value="<?php echo absint( $settings['experiment_minimum_days'] ?? 28 ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Initial status', 'ikon-seo' ); ?> <select name="status"><option value="draft">Draft</option><option value="approved">Approved</option></select></label></p>
					<?php submit_button( __( 'Create experiment', 'ikon-seo' ), 'secondary' ); ?>
				</form>
			</section>

			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Add source-backed claim', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_claim_record">
					<?php wp_nonce_field( 'ikon_seo_save_claim_record' ); ?>
					<p><label><?php esc_html_e( 'WordPress post ID', 'ikon-seo' ); ?><br><input type="number" min="0" name="post_id" value="0"></label></p>
					<p><label><?php esc_html_e( 'Claim', 'ikon-seo' ); ?><br><textarea class="large-text" rows="4" name="claim_text" required></textarea></label></p>
					<p><label><?php esc_html_e( 'Claim type', 'ikon-seo' ); ?><br><select name="claim_type"><option value="factual">Factual</option><option value="statistic">Statistic</option><option value="legal">Legal</option><option value="medical">Medical</option><option value="financial">Financial</option><option value="pricing">Pricing</option><option value="product">Product</option><option value="service">Service</option><option value="other">Other</option></select></label></p>
					<p><label><?php esc_html_e( 'Risk', 'ikon-seo' ); ?><br><select name="risk_level"><option value="standard">Standard</option><option value="sensitive">Sensitive</option><option value="high">High</option></select></label></p>
					<p><label><?php esc_html_e( 'Source URL', 'ikon-seo' ); ?><br><input class="large-text" type="url" name="source_url"></label></p>
					<p><label><?php esc_html_e( 'Source title', 'ikon-seo' ); ?><br><input class="large-text" type="text" name="source_title"></label></p>
					<p><label><?php esc_html_e( 'Source type', 'ikon-seo' ); ?><br><select name="source_type"><option value="primary">Primary</option><option value="official">Official</option><option value="secondary">Secondary</option><option value="internal">Internal</option><option value="expert_review">Expert review</option><option value="other">Other</option></select></label></p>
					<p><label><?php esc_html_e( 'Source published', 'ikon-seo' ); ?><br><input type="date" name="source_published_at"></label></p>
					<p><label><?php esc_html_e( 'Status', 'ikon-seo' ); ?><br><select name="status"><option value="needs_review">Needs review</option><option value="verified">Verified</option><option value="unsupported">Unsupported</option></select></label></p>
					<p><label><?php esc_html_e( 'Review due', 'ikon-seo' ); ?><br><input type="date" name="review_due_at"></label></p>
					<?php submit_button( __( 'Save claim record', 'ikon-seo' ), 'secondary' ); ?>
				</form>
			</section>
		</div>

		<div class="ikon-seo-grid ikon-seo-grid-2">
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Record conversion or revenue evidence', 'ikon-seo' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Use an internal reference only. It is hashed before storage. Do not enter customer names, email addresses, phone numbers or message content.', 'ikon-seo' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_revenue_event">
					<?php wp_nonce_field( 'ikon_seo_save_revenue_event' ); ?>
					<p><label><?php esc_html_e( 'Internal event reference', 'ikon-seo' ); ?><br><input class="regular-text" type="text" name="event_ref" required></label></p>
					<p><label><?php esc_html_e( 'Event type', 'ikon-seo' ); ?><br><select name="event_type"><option value="lead">Lead</option><option value="qualified_lead">Qualified lead</option><option value="appointment">Appointment</option><option value="proposal">Proposal</option><option value="sale">Sale</option><option value="refund">Refund</option><option value="affiliate">Affiliate</option><option value="advertising">Advertising</option><option value="other">Other</option></select></label></p>
					<p><label><?php esc_html_e( 'Occurred at', 'ikon-seo' ); ?><br><input type="datetime-local" name="occurred_at"></label></p>
					<p><label><?php esc_html_e( 'Source / medium / campaign', 'ikon-seo' ); ?><br><input type="text" name="source" placeholder="organic"> <input type="text" name="medium" placeholder="search"> <input type="text" name="campaign" placeholder="optional"></label></p>
					<p><label><?php esc_html_e( 'Landing URL', 'ikon-seo' ); ?><br><input class="large-text" type="url" name="landing_url"></label></p>
					<p><label><?php esc_html_e( 'CRM stage', 'ikon-seo' ); ?><br><input class="regular-text" type="text" name="crm_stage"></label></p>
					<p><label><?php esc_html_e( 'Value', 'ikon-seo' ); ?> <input type="number" step="0.01" name="value" value="0"> <input type="text" maxlength="3" size="4" name="currency" value="<?php echo esc_attr( $settings['revenue_default_currency'] ?? 'USD' ); ?>"></label></p>
					<p><label><input type="checkbox" name="qualified" value="1"> <?php esc_html_e( 'Qualified lead', 'ikon-seo' ); ?></label> &nbsp; <label><input type="checkbox" name="customer" value="1"> <?php esc_html_e( 'Customer or completed sale', 'ikon-seo' ); ?></label></p>
					<?php submit_button( __( 'Record evidence', 'ikon-seo' ), 'secondary' ); ?>
				</form>
			</section>
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Maintenance', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Cleanup removes old completed-experiment measurements and old revenue events according to the retention policy. It does not delete published content.', 'ikon-seo' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_cleanup_ecr">
					<?php wp_nonce_field( 'ikon_seo_cleanup_ecr' ); ?>
					<?php submit_button( __( 'Clean expired evidence', 'ikon-seo' ), 'secondary' ); ?>
				</form>
			</section>
		</div>
		<?php endif; ?>

		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Experiments', 'ikon-seo' ); ?></h3>
			<?php if ( ! $experiments ) : ?><p><?php esc_html_e( 'No experiments have been stored.', 'ikon-seo' ); ?></p><?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Experiment', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Groups', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Metric', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Latest evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Controls', 'ikon-seo' ); ?></th></tr></thead><tbody>
			<?php foreach ( $experiments as $experiment ) : $measurement = (array) ( $experiment['latest_measurement'] ?? array() ); ?>
			<tr>
				<td><strong><?php echo esc_html( $experiment['title'] ?? '' ); ?></strong><br><span class="description"><?php echo esc_html( $experiment['status'] ?? '' ); ?> · <?php echo esc_html( wp_trim_words( $experiment['hypothesis'] ?? '', 18 ) ); ?></span></td>
				<td><?php echo absint( count( (array) ( $experiment['test_urls'] ?? array() ) ) ); ?> test · <?php echo absint( count( (array) ( $experiment['comparison_urls'] ?? array() ) ) ); ?> comparison</td>
				<td><?php echo esc_html( $experiment['primary_metric'] ?? '' ); ?></td>
				<td><?php echo esc_html( $measurement['outcome'] ?? 'Not measured' ); ?><?php if ( ! empty( $measurement['confidence'] ) ) : ?><br><span class="description"><?php echo esc_html( $measurement['confidence'] ); ?> confidence</span><?php endif; ?></td>
				<td><?php if ( $agency ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:8px"><input type="hidden" name="action" value="ikon_seo_update_experiment"><input type="hidden" name="experiment_id" value="<?php echo absint( $experiment['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_update_experiment_' . absint( $experiment['id'] ) ); ?><select name="status"><option value="approved">Approved</option><option value="running">Running</option><option value="monitoring">Monitoring</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option><option value="archived">Archived</option></select> <button class="button button-small"><?php esc_html_e( 'Update', 'ikon-seo' ); ?></button></form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_capture_experiment_measurement"><input type="hidden" name="experiment_id" value="<?php echo absint( $experiment['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_capture_experiment_measurement_' . absint( $experiment['id'] ) ); ?><select name="phase"><option value="baseline">Baseline</option><option value="checkpoint">Checkpoint</option><option value="outcome">Outcome</option></select> <button class="button button-small"><?php esc_html_e( 'Capture evidence', 'ikon-seo' ); ?></button></form>
				<?php else : ?>—<?php endif; ?></td>
			</tr>
			<?php endforeach; ?>
			</tbody></table><?php endif; ?>
		</section>

		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Claim ledger', 'ikon-seo' ); ?></h3>
			<?php if ( ! $claims ) : ?><p><?php esc_html_e( 'No content claims have been stored.', 'ikon-seo' ); ?></p><?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Claim', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Source', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Review due', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Control', 'ikon-seo' ); ?></th></tr></thead><tbody>
			<?php foreach ( $claims as $claim ) : ?><tr>
				<td><?php echo esc_html( wp_trim_words( $claim['claim_text'] ?? '', 24 ) ); ?><?php if ( ! empty( $claim['post_title'] ) ) : ?><br><span class="description"><?php echo esc_html( $claim['post_title'] ); ?></span><?php endif; ?></td>
				<td><?php if ( ! empty( $claim['source_url'] ) ) : ?><a href="<?php echo esc_url( $claim['source_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $claim['source_title'] ?: wp_parse_url( $claim['source_url'], PHP_URL_HOST ) ); ?></a><?php else : ?>—<?php endif; ?></td>
				<td><?php echo esc_html( $claim['status'] ?? '' ); ?> · <?php echo esc_html( $claim['risk_level'] ?? '' ); ?></td>
				<td><?php echo esc_html( $claim['review_due_at'] ?? '—' ); ?></td>
				<td><?php if ( $agency ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_update_claim_record"><input type="hidden" name="claim_id" value="<?php echo absint( $claim['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_update_claim_record_' . absint( $claim['id'] ) ); ?><select name="status"><option value="verified">Verified</option><option value="needs_review">Needs review</option><option value="disputed">Disputed</option><option value="unsupported">Unsupported</option><option value="retired">Retired</option><option value="dismissed">Dismissed</option></select> <button class="button button-small"><?php esc_html_e( 'Update', 'ikon-seo' ); ?></button></form><?php else : ?>—<?php endif; ?></td>
			</tr><?php endforeach; ?>
			</tbody></table><?php endif; ?>
		</section>

		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Landing-page and channel attribution', 'ikon-seo' ); ?></h3>
			<?php $summary = (array) ( $revenue['summary'] ?? array() ); ?>
			<p><strong><?php echo absint( $summary['events'] ?? 0 ); ?></strong> events · <strong><?php echo absint( $summary['qualified'] ?? 0 ); ?></strong> qualified · <strong><?php echo absint( $summary['customers'] ?? 0 ); ?></strong> customers · <strong><?php echo esc_html( ( $summary['currency'] ?? 'USD' ) . ' ' . number_format_i18n( (float) ( $summary['value'] ?? 0 ), 2 ) ); ?></strong></p>
			<?php if ( ! empty( $revenue['landing_pages'] ) ) : ?><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Landing page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Events', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Qualified', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Customers', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Value', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( array_slice( (array) $revenue['landing_pages'], 0, 20 ) as $row ) : ?><tr><td><?php if ( ! empty( $row['landing_url'] ) ) : ?><a href="<?php echo esc_url( $row['landing_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_parse_url( $row['landing_url'], PHP_URL_PATH ) ?: $row['landing_url'] ); ?></a><?php else : ?>Unassigned<?php endif; ?></td><td><?php echo absint( $row['events'] ?? 0 ); ?></td><td><?php echo absint( $row['qualified'] ?? 0 ); ?></td><td><?php echo absint( $row['customers'] ?? 0 ); ?></td><td><?php echo esc_html( number_format_i18n( (float) ( $row['value'] ?? 0 ), 2 ) ); ?></td></tr><?php endforeach; ?></tbody></table><?php else : ?><p><?php esc_html_e( 'No conversion or revenue evidence has been stored for the reporting window.', 'ikon-seo' ); ?></p><?php endif; ?>
		</section>
		<?php
	}



	private function render_portfolio_quality() {
		$report = $this->portfolio_quality_guard->report( 100 );
		$status = (array) ( $report['status'] ?? array() );
		$settings = (array) ( $report['settings'] ?? array() );
		$findings = (array) ( $report['findings'] ?? array() );
		$sites = (array) ( $report['portfolio_sites'] ?? array() );
		$profiles = (array) ( $report['local_profiles'] ?? array() );
		$agency = Ikon_SEO_Agency::can_manage();
		?>
		<div class="ikon-seo-section-head">
			<div>
				<h2><?php esc_html_e( 'Portfolio Quality & Footprint Guard', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Review cross-site similarity, repeated templates, author and media reuse, thin programmatic clusters and publishing footprints without storing complete page content.', 'ikon-seo' ); ?></p>
			</div>
		</div>

		<div class="ikon-seo-grid ikon-seo-grid-4">
			<div class="ikon-seo-card"><span><?php esc_html_e( 'Local profiles', 'ikon-seo' ); ?></span><strong><?php echo esc_html( absint( $status['local_profiles'] ?? 0 ) ); ?></strong></div>
			<div class="ikon-seo-card"><span><?php esc_html_e( 'Portfolio websites', 'ikon-seo' ); ?></span><strong><?php echo esc_html( absint( $status['portfolio_sites'] ?? 0 ) ); ?></strong></div>
			<div class="ikon-seo-card"><span><?php esc_html_e( 'Open findings', 'ikon-seo' ); ?></span><strong><?php echo esc_html( absint( $status['open_findings'] ?? 0 ) ); ?></strong></div>
			<div class="ikon-seo-card"><span><?php esc_html_e( 'Review blocks', 'ikon-seo' ); ?></span><strong><?php echo esc_html( absint( $status['blocking_findings'] ?? 0 ) ); ?></strong></div>
		</div>

		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Safety boundary', 'ikon-seo' ); ?></h3>
			<p><?php esc_html_e( 'The guard stores hashes, bounded topic terms and structural counts. It does not store complete imported articles, identify authors by name, change public pages or declare that a website has violated a search policy.', 'ikon-seo' ); ?></p>
		</section>

		<?php if ( $agency ) : ?>
		<div class="ikon-seo-grid ikon-seo-grid-2">
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Guard settings', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_portfolio_quality_settings">
					<?php wp_nonce_field( 'ikon_seo_save_portfolio_quality_settings' ); ?>
					<p><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <?php esc_html_e( 'Enable weekly portfolio review', 'ikon-seo' ); ?></label></p>
					<p><label><?php esc_html_e( 'Scan batch', 'ikon-seo' ); ?><br><input type="number" min="5" max="200" name="scan_batch" value="<?php echo esc_attr( absint( $settings['scan_batch'] ?? 25 ) ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Content similarity threshold (%)', 'ikon-seo' ); ?><br><input type="number" min="55" max="98" name="content_threshold" value="<?php echo esc_attr( absint( $settings['content_threshold'] ?? 72 ) ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Topic overlap threshold (%)', 'ikon-seo' ); ?><br><input type="number" min="55" max="98" name="topic_threshold" value="<?php echo esc_attr( absint( $settings['topic_threshold'] ?? 80 ) ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Repeated-template threshold (%)', 'ikon-seo' ); ?><br><input type="number" min="50" max="100" name="template_threshold" value="<?php echo esc_attr( absint( $settings['template_threshold'] ?? 90 ) ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Thin-page warning below words', 'ikon-seo' ); ?><br><input type="number" min="100" max="2000" name="thin_words" value="<?php echo esc_attr( absint( $settings['thin_words'] ?? 450 ) ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Minimum repeated-page cluster', 'ikon-seo' ); ?><br><input type="number" min="3" max="50" name="cluster_min" value="<?php echo esc_attr( absint( $settings['cluster_min'] ?? 4 ) ); ?>"></label></p>
					<p><label><input type="checkbox" name="block_review_ready" value="1" <?php checked( ! empty( $settings['block_review_ready'] ) ); ?>> <?php esc_html_e( 'Block review-ready status for high-risk findings', 'ikon-seo' ); ?></label></p>
					<p><label><input type="checkbox" name="media_hashing" value="1" <?php checked( ! empty( $settings['media_hashing'] ) ); ?>> <?php esc_html_e( 'Use bounded local file hashes for media-reuse review', 'ikon-seo' ); ?></label></p>
					<p><label><?php esc_html_e( 'Evidence retention days', 'ikon-seo' ); ?><br><input type="number" min="90" max="1095" name="retention_days" value="<?php echo esc_attr( absint( $settings['retention_days'] ?? 365 ) ); ?>"></label></p>
					<?php submit_button( __( 'Save guard settings', 'ikon-seo' ) ); ?>
				</form>
			</section>

			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Run and exchange evidence', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_scan_portfolio_quality">
					<?php wp_nonce_field( 'ikon_seo_scan_portfolio_quality' ); ?>
					<p><label><?php esc_html_e( 'Local pages to scan', 'ikon-seo' ); ?><br><input type="number" min="1" max="200" name="limit" value="25"></label></p>
					<p><label><input type="checkbox" name="force" value="1"> <?php esc_html_e( 'Refresh recently scanned pages', 'ikon-seo' ); ?></label></p>
					<?php submit_button( __( 'Create local signatures', 'ikon-seo' ), 'secondary' ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_evaluate_portfolio_quality">
					<?php wp_nonce_field( 'ikon_seo_evaluate_portfolio_quality' ); ?>
					<input type="hidden" name="limit" value="100">
					<?php submit_button( __( 'Evaluate current portfolio', 'ikon-seo' ), 'secondary' ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_export_portfolio_quality">
					<?php wp_nonce_field( 'ikon_seo_export_portfolio_quality' ); ?>
					<input type="hidden" name="limit" value="500">
					<?php submit_button( __( 'Download signature bundle', 'ikon-seo' ), 'secondary' ); ?>
				</form>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_import_portfolio_quality">
					<?php wp_nonce_field( 'ikon_seo_import_portfolio_quality' ); ?>
					<p><label><?php esc_html_e( 'Import another managed website bundle', 'ikon-seo' ); ?><br><input type="file" name="portfolio_bundle" accept="application/json,.json" required></label></p>
					<?php submit_button( __( 'Import bundle', 'ikon-seo' ), 'secondary' ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_cleanup_portfolio_quality">
					<?php wp_nonce_field( 'ikon_seo_cleanup_portfolio_quality' ); ?>
					<?php submit_button( __( 'Clean expired evidence', 'ikon-seo' ), 'delete' ); ?>
				</form>
			</section>
		</div>
		<?php endif; ?>

		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Portfolio findings', 'ikon-seo' ); ?></h3>
			<?php if ( ! $findings ) : ?>
				<p><?php esc_html_e( 'No portfolio findings are stored yet. Scan local pages, import at least one external bundle, then evaluate the portfolio.', 'ikon-seo' ); ?></p>
			<?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Severity', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Local page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Finding', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Compared website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Score', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th></tr></thead><tbody>
			<?php foreach ( $findings as $finding ) : ?>
			<tr>
				<td><strong><?php echo esc_html( strtoupper( $finding['severity'] ?? '' ) ); ?></strong><?php if ( ! empty( $finding['blocks_review'] ) ) : ?><br><small><?php esc_html_e( 'Blocks review', 'ikon-seo' ); ?></small><?php endif; ?></td>
				<td><a href="<?php echo esc_url( $finding['local_url'] ?? '' ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $finding['local_url'] ?? '', PHP_URL_PATH ) ?: $finding['local_url'] ?? '' ); ?></a></td>
				<td><?php echo esc_html( $finding['summary'] ?? '' ); ?><br><small><?php echo esc_html( $finding['category'] ?? '' ); ?></small></td>
				<td><?php echo esc_html( $finding['compared_site'] ?? 'Local cluster' ); ?><?php if ( ! empty( $finding['compared_url'] ) ) : ?><br><a href="<?php echo esc_url( $finding['compared_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Review comparison', 'ikon-seo' ); ?></a><?php endif; ?></td>
				<td><?php echo esc_html( number_format_i18n( (float) ( $finding['risk_score'] ?? 0 ), 1 ) ); ?></td>
				<td><?php echo esc_html( $finding['status'] ?? '' ); ?><?php if ( $agency ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px"><input type="hidden" name="action" value="ikon_seo_update_portfolio_quality_finding"><input type="hidden" name="finding_id" value="<?php echo esc_attr( absint( $finding['id'] ?? 0 ) ); ?>"><?php wp_nonce_field( 'ikon_seo_update_portfolio_quality_finding_' . absint( $finding['id'] ?? 0 ) ); ?><select name="status"><option value="reviewed"><?php esc_html_e( 'Reviewed', 'ikon-seo' ); ?></option><option value="accepted"><?php esc_html_e( 'Accept risk', 'ikon-seo' ); ?></option><option value="resolved"><?php esc_html_e( 'Resolved', 'ikon-seo' ); ?></option><option value="dismissed"><?php esc_html_e( 'Dismissed', 'ikon-seo' ); ?></option></select><?php submit_button( __( 'Update', 'ikon-seo' ), 'small', 'submit', false ); ?></form><?php endif; ?></td>
			</tr>
			<?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>
		</section>

		<div class="ikon-seo-grid ikon-seo-grid-2">
			<section class="ikon-seo-card"><h3><?php esc_html_e( 'Imported portfolio websites', 'ikon-seo' ); ?></h3><?php if ( ! $sites ) : ?><p><?php esc_html_e( 'No external bundles imported.', 'ikon-seo' ); ?></p><?php else : ?><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Profiles', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Updated', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( $sites as $site ) : ?><tr><td><?php echo esc_html( $site['site_label'] ?? '' ); ?></td><td><?php echo esc_html( absint( $site['pages'] ?? 0 ) ); ?></td><td><?php echo esc_html( $site['updated_at'] ?? '' ); ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></section>
			<section class="ikon-seo-card"><h3><?php esc_html_e( 'Recent local signatures', 'ikon-seo' ); ?></h3><?php if ( ! $profiles ) : ?><p><?php esc_html_e( 'No local page profiles yet.', 'ikon-seo' ); ?></p><?php else : ?><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Words', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Headings', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Scanned', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( array_slice( $profiles, 0, 25 ) as $profile ) : ?><tr><td><a href="<?php echo esc_url( $profile['content_url'] ?? '' ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $profile['content_title'] ?? '' ); ?></a></td><td><?php echo esc_html( absint( $profile['word_count'] ?? 0 ) ); ?></td><td><?php echo esc_html( absint( $profile['heading_count'] ?? 0 ) ); ?></td><td><?php echo esc_html( $profile['scanned_at'] ?? '' ); ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></section>
		</div>
		<?php
	}

	private function render_international_server() {
		$report = $this->international_server->report( 100 );
		$status = (array) ( $report['status'] ?? array() );
		$settings = Ikon_SEO_Plugin::settings();
		$agency = Ikon_SEO_Agency::can_manage();
		$pages = (array) ( $report['international_pages'] ?? array() );
		?>
		<div class="ikon-seo-section-header">
			<div><h2><?php esc_html_e( 'International & Server Intelligence', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Review language targeting, hreflang relationships, localisation signals and privacy-preserving crawler evidence.', 'ikon-seo' ); ?></p></div>
			<span class="ikon-seo-pill"><?php echo esc_html( absint( $status['audited_pages'] ?? 0 ) . ' audited pages' ); ?></span>
		</div>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'This module reads rendered pages and imported server logs. It does not edit hreflang, canonicals, content, robots rules, redirects or indexing settings.', 'ikon-seo' ); ?></p></div>
		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo absint( $status['audited_pages'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Audited pages', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $status['pages_with_issues'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Pages needing review', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $status['reciprocal_issues'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Return-link issues', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $status['log_events_30_days'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Crawler records', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $status['crawler_errors_30_days'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Crawler errors', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $status['crawl_waste_30_days'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Potential crawl waste', 'ikon-seo' ); ?></span></div>
		</div>

		<?php if ( $agency ) : ?>
		<div class="ikon-seo-grid ikon-seo-grid-2">
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'International settings', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_international_server_settings">
					<?php wp_nonce_field( 'ikon_seo_save_international_server_settings' ); ?>
					<table class="form-table"><tbody>
					<tr><th><?php esc_html_e( 'Module', 'ikon-seo' ); ?></th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['international_server_enabled'] ) ); ?>> <?php esc_html_e( 'Enable weekly bounded review', 'ikon-seo' ); ?></label></td></tr>
					<tr><th><label for="international_locale_map"><?php esc_html_e( 'Locale map', 'ikon-seo' ); ?></label></th><td><textarea id="international_locale_map" name="locale_map" rows="6" class="large-text code"><?php echo esc_textarea( $settings['international_locale_map'] ?? '' ); ?></textarea><p class="description">en-QA|en|Qatar|QAR|+974</p></td></tr>
					<tr><th><label for="international_x_default_url"><?php esc_html_e( 'Website default URL', 'ikon-seo' ); ?></label></th><td><input id="international_x_default_url" type="url" class="large-text" name="x_default_url" value="<?php echo esc_attr( $settings['international_x_default_url'] ?? '' ); ?>"><p class="description"><?php esc_html_e( 'Optional same-site URL expected for x-default.', 'ikon-seo' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Page batch', 'ikon-seo' ); ?></th><td><input type="number" min="1" max="50" name="audit_batch" value="<?php echo absint( $settings['international_audit_batch'] ?? 5 ); ?>"></td></tr>
					<tr><th><?php esc_html_e( 'Stale after', 'ikon-seo' ); ?></th><td><input type="number" min="1" max="365" name="stale_days" value="<?php echo absint( $settings['international_stale_days'] ?? 30 ); ?>"> days</td></tr>
					<tr><th><?php esc_html_e( 'Log retention', 'ikon-seo' ); ?></th><td><input type="number" min="30" max="730" name="retention_days" value="<?php echo absint( $settings['server_log_retention_days'] ?? 180 ); ?>"> days</td></tr>
					<tr><th><?php esc_html_e( 'Maximum imported rows', 'ikon-seo' ); ?></th><td><input type="number" min="100" max="50000" name="max_rows" value="<?php echo absint( $settings['server_log_max_rows'] ?? 20000 ); ?>"></td></tr>
					<tr><th><?php esc_html_e( 'Slow-response threshold', 'ikon-seo' ); ?></th><td><input type="number" min="100" max="60000" name="slow_ms" value="<?php echo absint( $settings['server_log_slow_ms'] ?? 1500 ); ?>"> ms</td></tr>
					<tr><th><?php esc_html_e( 'Privacy and verification', 'ikon-seo' ); ?></th><td><label><input type="checkbox" name="store_query_keys" value="1" <?php checked( ! empty( $settings['server_log_store_query_keys'] ) ); ?>> <?php esc_html_e( 'Store query-parameter names without values', 'ikon-seo' ); ?></label><br><label><input type="checkbox" name="verify_crawlers" value="1" <?php checked( ! empty( $settings['server_log_verify_crawlers'] ) ); ?>> <?php esc_html_e( 'Verify supported crawler IPs with reverse and forward DNS', 'ikon-seo' ); ?></label></td></tr>
					</tbody></table>
					<?php submit_button( __( 'Save settings', 'ikon-seo' ) ); ?>
				</form>
			</section>
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Run reviews', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:18px"><input type="hidden" name="action" value="ikon_seo_run_international_audit"><?php wp_nonce_field( 'ikon_seo_run_international_audit' ); ?><label><?php esc_html_e( 'Batch size', 'ikon-seo' ); ?> <input type="number" min="1" max="50" name="limit" value="3"></label> <?php submit_button( __( 'Audit page batch', 'ikon-seo' ), 'secondary', 'submit', false ); ?></form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:18px"><input type="hidden" name="action" value="ikon_seo_audit_international_url"><?php wp_nonce_field( 'ikon_seo_audit_international_url' ); ?><label><?php esc_html_e( 'Same-site URL', 'ikon-seo' ); ?><br><input type="url" class="large-text" name="url" required></label><?php submit_button( __( 'Audit URL', 'ikon-seo' ), 'secondary' ); ?></form>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_import_server_log"><?php wp_nonce_field( 'ikon_seo_import_server_log' ); ?><label><?php esc_html_e( 'Apache combined log or generic CSV', 'ikon-seo' ); ?><br><input type="file" name="server_log" accept=".log,.txt,.csv" required></label><p class="description"><?php esc_html_e( 'Maximum 10 MB. IP addresses and user agents are hashed; query values are not stored.', 'ikon-seo' ); ?></p><?php submit_button( __( 'Import server evidence', 'ikon-seo' ), 'secondary' ); ?></form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_cleanup_server_logs"><?php wp_nonce_field( 'ikon_seo_cleanup_server_logs' ); ?><?php submit_button( __( 'Clean expired server evidence', 'ikon-seo' ), 'secondary' ); ?></form>
			</section>
		</div>
		<?php endif; ?>

		<section class="ikon-seo-card"><h3><?php esc_html_e( 'International page findings', 'ikon-seo' ); ?></h3>
		<?php if ( ! $pages ) : ?><p><?php esc_html_e( 'No pages have been audited yet.', 'ikon-seo' ); ?></p><?php else : ?><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Language', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Alternates', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Issues', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Audited', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( $pages as $row ) : ?><tr><td><a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_parse_url( $row['url'], PHP_URL_PATH ) ?: $row['url'] ); ?></a></td><td><?php echo esc_html( $row['html_lang'] ?: '—' ); ?><?php if ( ! empty( $row['inferred_locale'] ) ) : ?><br><small><?php echo esc_html( 'Configured: ' . $row['inferred_locale'] ); ?></small><?php endif; ?></td><td><?php echo absint( count( (array) ( $row['hreflang'] ?? array() ) ) ); ?></td><td><strong><?php echo absint( $row['issue_count'] ?? 0 ); ?></strong><?php foreach ( array_slice( (array) ( $row['issues'] ?? array() ), 0, 3 ) as $issue ) : ?><br><small><?php echo esc_html( $issue['message'] ?? '' ); ?></small><?php endforeach; ?></td><td><?php echo esc_html( $row['audited_at'] ?? '' ); ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></section>

		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Crawler evidence', 'ikon-seo' ); ?></h3>
		<?php if ( empty( $report['crawler_summary'] ) ) : ?><p><?php esc_html_e( 'Import a server log to review crawler activity.', 'ikon-seo' ); ?></p><?php else : ?><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Crawler', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Verification', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Requests', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Errors', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Waste', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Average response', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) $report['crawler_summary'] as $row ) : ?><tr><td><?php echo esc_html( $row['crawler_family'] ); ?></td><td><?php echo esc_html( $row['verification_state'] ); ?></td><td><?php echo absint( $row['requests'] ); ?></td><td><?php echo absint( $row['errors'] ); ?></td><td><?php echo absint( $row['waste'] ); ?></td><td><?php echo absint( $row['average_response_ms'] ); ?> ms</td></tr><?php endforeach; ?></tbody></table><?php endif; ?></section>

		<div class="ikon-seo-grid ikon-seo-grid-2"><section class="ikon-seo-card"><h3><?php esc_html_e( 'Top crawler errors', 'ikon-seo' ); ?></h3><?php $this->render_server_path_rows( (array) ( $report['top_errors'] ?? array() ) ); ?></section><section class="ikon-seo-card"><h3><?php esc_html_e( 'Potential crawl waste', 'ikon-seo' ); ?></h3><?php $this->render_server_path_rows( (array) ( $report['top_waste'] ?? array() ) ); ?></section></div>
		<?php
	}

	private function render_server_path_rows( array $rows ) {
		if ( ! $rows ) { echo '<p>' . esc_html__( 'No matching evidence is stored.', 'ikon-seo' ) . '</p>'; return; }
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Path', 'ikon-seo' ) . '</th><th>' . esc_html__( 'Crawler', 'ikon-seo' ) . '</th><th>' . esc_html__( 'Requests', 'ikon-seo' ) . '</th><th>' . esc_html__( 'Evidence', 'ikon-seo' ) . '</th></tr></thead><tbody>';
		foreach ( array_slice( $rows, 0, 20 ) as $row ) { echo '<tr><td>' . esc_html( $row['request_path'] ?? '' ) . '</td><td>' . esc_html( $row['crawler_family'] ?? '' ) . '</td><td>' . absint( $row['requests'] ?? 0 ) . '</td><td>' . esc_html( ( $row['waste_category'] ?? '' ) . ( ! empty( $row['status_code'] ) ? ' · ' . $row['status_code'] : '' ) ) . '</td></tr>'; }
		echo '</tbody></table>';
	}


	private function render_platform_health() {
		$report = $this->platform_hardening->report();
		$readiness = (array) ( $report['readiness'] ?? array() );
		$integrity = (array) ( $report['integrity'] ?? array() );
		$compat = (array) ( $report['compatibility'] ?? array() );
		$security = (array) ( $report['security'] ?? array() );
		$archives = (array) ( $report['recovery_archives'] ?? array() );
		$upgrades = (array) ( $report['upgrade_journal'] ?? array() );
		?>
		<section class="ikon-seo-card">
			<h2><?php esc_html_e( 'Platform Hardening & Release Management', 'ikon-seo' ); ?></h2>
			<p><?php esc_html_e( 'Verify the packaged release, review server compatibility and security, create credential-free recovery archives, and inspect the database upgrade journal before production deployment.', 'ikon-seo' ); ?></p>
			<div class="ikon-seo-stat-grid">
				<div><strong><?php echo esc_html( ucfirst( $readiness['status'] ?? 'not run' ) ); ?></strong><span><?php esc_html_e( 'Release readiness', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo esc_html( ucfirst( $integrity['overall_status'] ?? 'not run' ) ); ?></strong><span><?php esc_html_e( 'Package integrity', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo absint( $compat['counts']['critical'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Compatibility blocks', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo absint( $security['counts']['warning'] ?? 0 ) + absint( $security['counts']['critical'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Security reviews', 'ikon-seo' ); ?></span></div>
			</div>
			<?php foreach ( (array) ( $readiness['blocks'] ?? array() ) as $block ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $block ); ?></p></div><?php endforeach; ?>
			<?php foreach ( (array) ( $readiness['warnings'] ?? array() ) as $warning ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html( $warning ); ?></p></div><?php endforeach; ?>
		</section>
		<div class="ikon-seo-two-columns">
		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Release and diagnostics actions', 'ikon-seo' ); ?></h3>
			<?php foreach ( array( 'run_checks' => 'Run full hardening checks', 'verify_release' => 'Verify signed release manifest', 'repair_scheduler' => 'Repair expected scheduled events', 'cleanup' => 'Apply retention cleanup' ) as $command => $label ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:10px"><input type="hidden" name="action" value="ikon_seo_platform_hardening_action"><input type="hidden" name="command" value="<?php echo esc_attr( $command ); ?>"><?php wp_nonce_field( 'ikon_seo_platform_hardening_action' ); ?><button class="button <?php echo 'run_checks' === $command ? 'button-primary' : ''; ?>"><?php echo esc_html( $label ); ?></button></form>
			<?php endforeach; ?>
		</section>
		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Recovery and support archives', 'ikon-seo' ); ?></h3>
			<?php foreach ( array( 'configuration' => 'Create configuration recovery point', 'support' => 'Create sanitized support bundle' ) as $type => $label ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:12px"><input type="hidden" name="action" value="ikon_seo_platform_hardening_action"><input type="hidden" name="command" value="create_archive"><input type="hidden" name="archive_type" value="<?php echo esc_attr( $type ); ?>"><?php wp_nonce_field( 'ikon_seo_platform_hardening_action' ); ?><input class="regular-text" name="label" placeholder="Optional archive label"> <button class="button"><?php echo esc_html( $label ); ?></button></form>
			<?php endforeach; ?>
			<p class="description"><?php esc_html_e( 'Archives exclude connection keys, OAuth secrets, API keys, passwords, personal data and WordPress page content.', 'ikon-seo' ); ?></p>
		</section></div>
		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Compatibility and security matrix', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Check', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'State', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recommendation', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php foreach ( array_merge( (array) ( $compat['items'] ?? array() ), (array) ( $security['items'] ?? array() ) ) as $item ) : ?><tr><td><?php echo esc_html( $item['label'] ?? '' ); ?></td><td><?php echo esc_html( strtoupper( $item['state'] ?? 'info' ) ); ?></td><td><?php echo esc_html( $item['detail'] ?? '' ); ?></td><td><?php echo esc_html( $item['recommendation'] ?? '' ); ?></td></tr><?php endforeach; ?>
		</tbody></table></section>
		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Recovery archives', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th>ID</th><th><?php esc_html_e( 'Type and label', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Version', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Payload hash', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Created', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Restore', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( $archives ) : foreach ( $archives as $archive ) : ?><tr><td><?php echo absint( $archive['id'] ); ?></td><td><?php echo esc_html( ucfirst( $archive['archive_type'] ) . ' · ' . $archive['label'] ); ?></td><td><?php echo esc_html( $archive['plugin_version'] . ' / DB ' . $archive['db_version'] ); ?></td><td><code><?php echo esc_html( substr( $archive['payload_hash'], 0, 16 ) ); ?>…</code></td><td><?php echo esc_html( $archive['created_at'] ); ?></td><td><?php if ( 'configuration' === $archive['archive_type'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Restore the credential-free plugin configuration from this exact archive?');"><input type="hidden" name="action" value="ikon_seo_platform_hardening_action"><input type="hidden" name="command" value="restore_archive"><input type="hidden" name="archive_id" value="<?php echo absint( $archive['id'] ); ?>"><input type="hidden" name="expected_hash" value="<?php echo esc_attr( $archive['payload_hash'] ); ?>"><?php wp_nonce_field( 'ikon_seo_platform_hardening_action' ); ?><button class="button"><?php esc_html_e( 'Restore configuration', 'ikon-seo' ); ?></button></form><?php else : ?>—<?php endif; ?></td></tr><?php endforeach; else : ?><tr><td colspan="6"><?php esc_html_e( 'No recovery archive has been created.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		</tbody></table></section>
		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Upgrade journal', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Plugin', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Database', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recorded', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( $upgrades ) : foreach ( $upgrades as $upgrade ) : ?><tr><td><?php echo esc_html( ucfirst( $upgrade['status'] ?? '' ) ); ?></td><td><?php echo esc_html( ( $upgrade['from_plugin_version'] ?: 'new' ) . ' → ' . $upgrade['to_plugin_version'] ); ?></td><td><?php echo esc_html( ( $upgrade['from_db_version'] ?: 'new' ) . ' → ' . $upgrade['to_db_version'] ); ?></td><td><?php echo esc_html( $upgrade['created_at'] ); ?></td></tr><?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e( 'No upgrade record is available yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?></tbody></table></section>
		<?php
	}

	private function render_indexation() {
		$status = $this->indexation->status();
		$report = $this->indexation->report( 100 );
		$health = $this->production_health->report( false );
		$settings = Ikon_SEO_Plugin::settings();
		$agency = Ikon_SEO_Agency::can_manage();
		$connected = ! empty( $status['connected'] );
		?>
		<div class="ikon-seo-section-header">
			<div><h2><?php esc_html_e( 'Indexation Intelligence', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Quota-aware Search Console inspection history, canonical comparison, crawl evidence and production-readiness checks.', 'ikon-seo' ); ?></p></div>
			<span class="ikon-seo-pill <?php echo $connected ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( $connected ? 'Search Console ready' : 'Search Console connection required' ); ?></span>
		</div>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'This module reads the version currently known to Google. It does not request indexing, run the live URL test, edit canonicals, remove noindex rules or change published pages.', 'ikon-seo' ); ?></p></div>
		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['total_urls'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Tracked URLs', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['indexed_urls'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Indexed', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['not_indexed'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Not indexed', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['canonical_mismatches'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Canonical mismatches', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['queued'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Queued', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['stale'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Stale evidence', 'ikon-seo' ); ?></span></div>
		</div>
		<?php if ( ! $connected ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Connect Google Search Console and select the correct property before running inspections.', 'ikon-seo' ); ?></p></div><?php endif; ?>

		<?php if ( $agency ) : ?>
		<div class="ikon-seo-grid ikon-seo-grid-2">
			<form class="ikon-seo-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_save_indexation_settings"><?php wp_nonce_field( 'ikon_seo_save_indexation_settings' ); ?>
				<h3><?php esc_html_e( 'Inspection policy', 'ikon-seo' ); ?></h3>
				<p><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['indexation_intelligence_enabled'] ) ); ?>> <?php esc_html_e( 'Enable scheduled inspections', 'ikon-seo' ); ?></label></p>
				<p><label><input type="checkbox" name="reinspect_after_change" value="1" <?php checked( ! empty( $settings['indexation_reinspect_after_change'] ) ); ?>> <?php esc_html_e( 'Queue published pages after they change', 'ikon-seo' ); ?></label></p>
				<p><label><?php esc_html_e( 'Local daily budget', 'ikon-seo' ); ?><br><input type="number" min="1" max="2000" name="daily_budget" value="<?php echo absint( $settings['indexation_daily_budget'] ?? 100 ); ?>"></label></p>
				<p><label><?php esc_html_e( 'Default inspection batch', 'ikon-seo' ); ?><br><input type="number" min="1" max="100" name="inspection_batch" value="<?php echo absint( $settings['indexation_inspection_batch'] ?? 10 ); ?>"></label></p>
				<p><label><?php esc_html_e( 'Inventory seed limit', 'ikon-seo' ); ?><br><input type="number" min="10" max="5000" name="seed_batch" value="<?php echo absint( $settings['indexation_seed_batch'] ?? 500 ); ?>"></label></p>
				<p><label><?php esc_html_e( 'Reinspect evidence older than', 'ikon-seo' ); ?><br><input type="number" min="1" max="365" name="stale_days" value="<?php echo absint( $settings['indexation_stale_days'] ?? 14 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></label></p>
				<p><label><?php esc_html_e( 'Run-history retention', 'ikon-seo' ); ?><br><input type="number" min="30" max="730" name="retention_days" value="<?php echo absint( $settings['indexation_history_retention_days'] ?? 180 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></label></p>
				<button class="button"><?php esc_html_e( 'Save inspection policy', 'ikon-seo' ); ?></button>
			</form>
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Safe inspection controls', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_seed_indexation"><?php wp_nonce_field( 'ikon_seo_seed_indexation' ); ?><label><?php esc_html_e( 'Inventory limit', 'ikon-seo' ); ?> <input type="number" min="10" max="5000" name="limit" value="<?php echo absint( $settings['indexation_seed_batch'] ?? 500 ); ?>" style="width:90px"></label> <label><input type="checkbox" name="refresh_inventory" value="1"> <?php esc_html_e( 'Refresh inventory first', 'ikon-seo' ); ?></label><p><button class="button"><?php esc_html_e( 'Prepare URL queue', 'ikon-seo' ); ?></button></p></form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_run_indexation_batch"><?php wp_nonce_field( 'ikon_seo_run_indexation_batch' ); ?><label><?php esc_html_e( 'Batch', 'ikon-seo' ); ?> <input type="number" min="1" max="100" name="limit" value="<?php echo absint( $settings['indexation_inspection_batch'] ?? 10 ); ?>" style="width:75px"></label> <label><input type="checkbox" name="force" value="1"> <?php esc_html_e( 'Include recently inspected URLs', 'ikon-seo' ); ?></label><p><button class="button button-primary" <?php disabled( ! $connected ); ?>><?php esc_html_e( 'Run read-only inspections', 'ikon-seo' ); ?></button></p></form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_inspect_indexation_url"><?php wp_nonce_field( 'ikon_seo_inspect_indexation_url' ); ?><label><?php esc_html_e( 'Inspect one website URL', 'ikon-seo' ); ?><br><input class="large-text" type="url" name="url" required placeholder="<?php echo esc_attr( home_url( '/important-page/' ) ); ?>"></label><p><button class="button" <?php disabled( ! $connected ); ?>><?php esc_html_e( 'Inspect stored Google version', 'ikon-seo' ); ?></button></p></form>
				<p class="description"><?php echo esc_html( sprintf( __( '%1$d of %2$d local daily inspections used. The configured budget never exceeds the documented per-property daily limit.', 'ikon-seo' ), absint( $status['quota']['used_today'] ?? 0 ), absint( $status['quota']['local_daily_budget'] ?? 0 ) ) ); ?></p>
			</section>
		</div>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Indexation issues requiring review', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'URL', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Google evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Canonical', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Last inspection', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['issues'] ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'No stored indexation issue is available yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( array_slice( (array) ( $report['issues'] ?? array() ), 0, 100 ) as $item ) : ?><tr><td><a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_parse_url( $item['url'], PHP_URL_PATH ) ?: '/' ); ?></a><br><small><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['issue_code'] ?: 'review' ) ) ); ?></small></td><td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['inspection_status'] ?: 'unknown' ) ) ); ?></strong><br><small><?php echo esc_html( $item['coverage_state'] ?: $item['last_error'] ); ?></small></td><td><small><?php echo esc_html( $item['google_canonical'] ?: 'No Google canonical stored' ); ?></small><?php if ( ! empty( $item['canonical_mismatch'] ) ) : ?><br><strong><?php esc_html_e( 'Mismatch', 'ikon-seo' ); ?></strong><?php endif; ?></td><td><?php echo esc_html( $item['inspected_at'] ?: 'Not inspected' ); ?><br><small><?php echo esc_html( $item['last_crawl_time'] ?: '' ); ?></small></td></tr><?php endforeach; ?>
		</tbody></table>

		<hr><div class="ikon-seo-section-header"><div><h3><?php esc_html_e( 'Production health', 'ikon-seo' ); ?></h3><p class="description"><?php esc_html_e( 'Checks migrations, expected tables, schedules, REST loopback and common plugin conflicts.', 'ikon-seo' ); ?></p></div><span class="ikon-seo-pill <?php echo 'ready' === ( $health['status'] ?? '' ) ? 'is-connected' : ( 'critical' === ( $health['status'] ?? '' ) ? 'is-failed' : '' ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $health['status'] ?? 'not_run' ) ) ); ?></span></div>
		<?php if ( $agency ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ikon-seo-actions"><input type="hidden" name="action" value="ikon_seo_run_production_health"><?php wp_nonce_field( 'ikon_seo_run_production_health' ); ?><button class="button"><?php esc_html_e( 'Run production health checks', 'ikon-seo' ); ?></button></form><?php endif; ?>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Check', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'State', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recommended review', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $health['checks'] ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'Production health has not been run yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) ( $health['checks'] ?? array() ) as $check ) : ?><tr><td><strong><?php echo esc_html( $check['label'] ); ?></strong></td><td><?php echo esc_html( ucfirst( $check['state'] ) ); ?></td><td><?php echo esc_html( $check['detail'] ); ?></td><td><?php echo esc_html( $check['recommendation'] ); ?></td></tr><?php endforeach; ?>
		</tbody></table>
		<?php
	}


	private function render_deployment_control() {
		$report = $this->deployment_control->report();
		$license = (array) ( $report['active_entitlement'] ?? array() );
		$releases = (array) ( $report['releases'] ?? array() );
		$plans = (array) ( $report['deployments'] ?? array() );
		$environment = sanitize_key( $report['environment'] ?? 'production' );
		?>
		<section class="ikon-seo-card">
			<h2><?php esc_html_e( 'Deployment Control, Licensing & Managed Updates', 'ikon-seo' ); ?></h2>
			<p><?php esc_html_e( 'Register signed release metadata, require an active entitlement and recovery point, obtain separate approval, then record and verify a WordPress update performed manually by an administrator.', 'ikon-seo' ); ?></p>
			<div class="ikon-seo-stat-grid">
				<div><strong><?php echo esc_html( ucfirst( $environment ) ); ?></strong><span><?php esc_html_e( 'Environment', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo esc_html( $license ? ucfirst( $license['status'] ) : 'Missing' ); ?></strong><span><?php esc_html_e( 'Entitlement', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo count( $releases ); ?></strong><span><?php esc_html_e( 'Registered releases', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo count( $plans ); ?></strong><span><?php esc_html_e( 'Deployment records', 'ikon-seo' ); ?></span></div>
			</div>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Ikon SEO does not download or install plugin code. WordPress updates and any rollback remain manual administrator actions.', 'ikon-seo' ); ?></p></div>
		</section>
		<div class="ikon-seo-two-columns">
		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Entitlement', 'ikon-seo' ); ?></h3>
		<?php if ( 'production' !== $environment ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_deployment_control_action"><input type="hidden" name="command" value="create_evaluation"><?php wp_nonce_field( 'ikon_seo_deployment_control_action' ); ?><p><label><?php esc_html_e( 'Evaluation organisation', 'ikon-seo' ); ?><br><input class="regular-text" name="organisation" value="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></label></p><p><label><?php esc_html_e( 'Days', 'ikon-seo' ); ?><br><input type="number" min="1" max="30" name="days" value="14"></label></p><button class="button"><?php esc_html_e( 'Create staging evaluation', 'ikon-seo' ); ?></button></form>
		<?php else : ?><p class="description"><?php esc_html_e( 'Production requires a signed entitlement envelope issued for this website fingerprint.', 'ikon-seo' ); ?></p><?php endif; ?>
		<?php if ( $license ) : ?><hr><p><strong><?php echo esc_html( $license['organisation'] ); ?></strong><br><?php echo esc_html( ucfirst( $license['edition'] ) . ' · ' . ucfirst( $license['status'] ) ); ?><br><small><?php echo esc_html( $license['expires_at'] ?: 'No expiry recorded' ); ?></small></p><?php endif; ?>
		</section>
		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Release catalogue', 'ikon-seo' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_deployment_control_action"><input type="hidden" name="command" value="register_installed_release"><?php wp_nonce_field( 'ikon_seo_deployment_control_action' ); ?><button class="button button-primary"><?php esc_html_e( 'Register installed signed release', 'ikon-seo' ); ?></button></form>
		<?php if ( $releases ) : ?><ul><?php foreach ( array_slice( $releases, 0, 10 ) as $release ) : ?><li><strong>v<?php echo esc_html( $release['version'] ); ?></strong> · <?php echo esc_html( ucfirst( $release['channel'] ) ); ?> · <?php echo esc_html( ucfirst( $release['signature_state'] ) ); ?></li><?php endforeach; ?></ul><?php else : ?><p class="description"><?php esc_html_e( 'No release metadata has been registered.', 'ikon-seo' ); ?></p><?php endif; ?>
		</section></div>
		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Prepare deployment', 'ikon-seo' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_deployment_control_action"><input type="hidden" name="command" value="create_plan"><?php wp_nonce_field( 'ikon_seo_deployment_control_action' ); ?><p><label><?php esc_html_e( 'Registered release', 'ikon-seo' ); ?><br><select name="release_id" required><option value=""><?php esc_html_e( 'Select release', 'ikon-seo' ); ?></option><?php foreach ( $releases as $release ) : ?><option value="<?php echo absint( $release['id'] ); ?>">v<?php echo esc_html( $release['version'] . ' · ' . $release['channel'] ); ?></option><?php endforeach; ?></select></label></p><p><textarea class="large-text" name="notes" rows="2" placeholder="Deployment notes"></textarea></p><button class="button"><?php esc_html_e( 'Run preflight and prepare', 'ikon-seo' ); ?></button></form>
		</section>
		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Deployment history', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th>ID</th><th><?php esc_html_e( 'Versions', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Fingerprint', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( ! $plans ) : ?><tr><td colspan="5"><?php esc_html_e( 'No deployment plan has been prepared.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( $plans as $plan ) : ?><tr><td><?php echo absint( $plan['id'] ); ?></td><td><?php echo esc_html( $plan['from_version'] . ' → ' . $plan['target_version'] ); ?></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $plan['status'] ) ) ); ?></td><td><code><?php echo esc_html( substr( $plan['preflight_fingerprint'], 0, 16 ) ); ?>…</code></td><td>
		<?php if ( 'prepared' === $plan['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_deployment_control_action"><input type="hidden" name="command" value="approve_plan"><input type="hidden" name="plan_id" value="<?php echo absint( $plan['id'] ); ?>"><input type="hidden" name="expected_fingerprint" value="<?php echo esc_attr( $plan['preflight_fingerprint'] ); ?>"><?php wp_nonce_field( 'ikon_seo_deployment_control_action' ); ?><input name="notes" placeholder="Approval notes"> <button class="button"><?php esc_html_e( 'Approve manual update', 'ikon-seo' ); ?></button></form><?php elseif ( 'approved_manual_install' === $plan['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_deployment_control_action"><input type="hidden" name="command" value="record_manual_deployment"><input type="hidden" name="plan_id" value="<?php echo absint( $plan['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_deployment_control_action' ); ?><input name="notes" placeholder="Manual installation record"> <button class="button"><?php esc_html_e( 'Record manual update', 'ikon-seo' ); ?></button></form><?php elseif ( in_array( $plan['status'], array( 'deployed_pending_verification','verification_failed' ), true ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_deployment_control_action"><input type="hidden" name="command" value="verify_deployment"><input type="hidden" name="plan_id" value="<?php echo absint( $plan['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_deployment_control_action' ); ?><button class="button"><?php esc_html_e( 'Verify deployment', 'ikon-seo' ); ?></button></form><?php elseif ( 'verified' === $plan['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_deployment_control_action"><input type="hidden" name="command" value="close_plan"><input type="hidden" name="plan_id" value="<?php echo absint( $plan['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_deployment_control_action' ); ?><input name="notes" required placeholder="Closure notes"> <button class="button"><?php esc_html_e( 'Close record', 'ikon-seo' ); ?></button></form><?php else : ?>—<?php endif; ?>
		</td></tr><?php endforeach; ?></tbody></table></section>
		<?php
	}


	private function render_production_certification() {
		$report = $this->production_certification->report( array( 'limit' => 50 ) );
		$contracts = (array) ( $report['contracts'] ?? array() );
		$certifications = (array) ( $report['certifications'] ?? array() );
		$rollouts = (array) ( $report['rollouts'] ?? array() );
		?>
		<div class="ikon-seo-grid">
			<div class="ikon-seo-card"><h2><?php esc_html_e( 'Production Agency Platform', 'ikon-seo' ); ?></h2><p><?php esc_html_e( 'Certify a specific signed release, database version and operating contract before a controlled manual rollout. This screen records evidence and approvals only.', 'ikon-seo' ); ?></p><p><strong><?php esc_html_e( 'Installed release:', 'ikon-seo' ); ?></strong> <?php echo esc_html( IKON_SEO_VERSION ); ?> · <strong><?php esc_html_e( 'Database:', 'ikon-seo' ); ?></strong> <?php echo esc_html( Ikon_SEO_Plugin::DB_VERSION ); ?></p><p><code>manual_distribution_only = true</code><br><code>automatic_installation = false</code><br><code>automatic_rollback = false</code><br><code>remote_publishing = false</code></p></div>
			<div class="ikon-seo-card"><h3><?php esc_html_e( 'Create support contract', 'ikon-seo' ); ?></h3><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_production_certification_action"><input type="hidden" name="command" value="create_contract"><?php wp_nonce_field( 'ikon_seo_production_certification_action' ); ?><p><input class="regular-text" name="label" value="Ikon SEO Production Support Contract" required></p><p><label><?php esc_html_e( 'Support window (days)', 'ikon-seo' ); ?> <input type="number" min="90" max="1095" name="support_window_days" value="365"></label></p><p><label><?php esc_html_e( 'Recovery drill window (days)', 'ikon-seo' ); ?> <input type="number" min="7" max="365" name="recovery_drill_days" value="90"></label></p><p><textarea class="large-text" name="notes" rows="3" placeholder="Supported hosting, plugin and operational assumptions"></textarea></p><button class="button button-primary"><?php esc_html_e( 'Create draft contract', 'ikon-seo' ); ?></button></form></div>
		</div>
		<h2><?php esc_html_e( 'Support contracts', 'ikon-seo' ); ?></h2><table class="widefat striped"><thead><tr><th>ID</th><th><?php esc_html_e( 'Contract', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Version', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( ! $contracts ) : ?><tr><td colspan="5"><?php esc_html_e( 'No production support contract exists yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( $contracts as $contract ) : ?><tr><td><?php echo absint( $contract['id'] ); ?></td><td><strong><?php echo esc_html( $contract['contract']['label'] ?? $contract['contract_key'] ); ?></strong><br><code><?php echo esc_html( substr( $contract['fingerprint'], 0, 16 ) ); ?>…</code></td><td><?php echo esc_html( $contract['version'] ); ?></td><td><?php echo esc_html( ucfirst( $contract['status'] ) ); ?></td><td><?php if ( 'draft' === $contract['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_production_certification_action"><input type="hidden" name="command" value="approve_contract"><input type="hidden" name="contract_id" value="<?php echo absint( $contract['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_production_certification_action' ); ?><button class="button"><?php esc_html_e( 'Approve with different admin', 'ikon-seo' ); ?></button></form><?php elseif ( 'approved' === $contract['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_production_certification_action"><input type="hidden" name="command" value="create_certification"><input type="hidden" name="contract_id" value="<?php echo absint( $contract['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_production_certification_action' ); ?><select name="environment"><option value="production">Production</option><option value="staging">Staging</option><option value="development">Development</option><option value="local">Local</option></select> <button class="button"><?php esc_html_e( 'Start certification', 'ikon-seo' ); ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table>
		<h2><?php esc_html_e( 'Certification runs', 'ikon-seo' ); ?></h2><table class="widefat striped"><thead><tr><th>ID</th><th><?php esc_html_e( 'Release', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Environment', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Score', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Review', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( ! $certifications ) : ?><tr><td colspan="6"><?php esc_html_e( 'No certification run exists yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( $certifications as $cert ) : ?><tr><td><?php echo absint( $cert['id'] ); ?></td><td><?php echo esc_html( $cert['release_version'] . ' / DB ' . $cert['database_version'] ); ?></td><td><?php echo esc_html( ucfirst( $cert['environment'] ) ); ?></td><td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $cert['status'] ) ) ); ?></td><td><?php echo absint( $cert['score'] ); ?>/100</td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_production_certification_action"><input type="hidden" name="certification_id" value="<?php echo absint( $cert['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_production_certification_action' ); ?><select name="command"><option value="refresh_certification">Refresh evidence gate</option><?php if ( 'review_ready' === $cert['status'] ) : ?><option value="approve_certification">Approve exact evidence</option><?php endif; ?></select><input type="hidden" name="evidence_fingerprint" value="<?php echo esc_attr( $cert['evidence_fingerprint'] ); ?>"> <button class="button"><?php esc_html_e( 'Apply', 'ikon-seo' ); ?></button></form><details><summary><?php esc_html_e( 'Record a check', 'ikon-seo' ); ?></summary><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_production_certification_action"><input type="hidden" name="command" value="record_check"><input type="hidden" name="certification_id" value="<?php echo absint( $cert['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_production_certification_action' ); ?><p><select name="check_key"><?php foreach ( $this->production_certification->allowed_checks() as $key => $definition ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $definition['label'] . ( $definition['critical'] ? ' — critical' : '' ) ); ?></option><?php endforeach; ?></select></p><p><select name="check_status"><option value="passed">Passed</option><option value="failed">Failed</option><option value="pending">Pending</option><option value="waived">Waived (non-critical only)</option></select></p><p><textarea class="large-text" name="evidence" rows="3" placeholder="Test run, compatibility result, restore drill or audit evidence"></textarea></p><p><textarea class="large-text" name="notes" rows="2" placeholder="Reviewer notes"></textarea></p><button class="button"><?php esc_html_e( 'Record check', 'ikon-seo' ); ?></button></form></details></td></tr><?php endforeach; ?></tbody></table>
		<h2><?php esc_html_e( 'Controlled rollout waves', 'ikon-seo' ); ?></h2><p><?php esc_html_e( 'Rollout waves record manual deployments to managed-site IDs. They never install the plugin remotely.', 'ikon-seo' ); ?></p>
		<div class="ikon-seo-card"><h3><?php esc_html_e( 'Create rollout wave', 'ikon-seo' ); ?></h3><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_production_certification_action"><input type="hidden" name="command" value="create_rollout"><?php wp_nonce_field( 'ikon_seo_production_certification_action' ); ?><p><select name="certification_id" required><option value=""><?php esc_html_e( 'Select approved certification', 'ikon-seo' ); ?></option><?php foreach ( $certifications as $cert ) : if ( 'approved' !== $cert['status'] ) { continue; } ?><option value="<?php echo absint( $cert['id'] ); ?>"><?php echo esc_html( '#' . absint( $cert['id'] ) . ' — ' . $cert['release_version'] . ' — ' . ucfirst( $cert['environment'] ) ); ?></option><?php endforeach; ?></select></p><p><input class="regular-text" name="label" placeholder="Pilot rollout" required></p><p><input class="large-text" name="site_ids" placeholder="Managed-site IDs, comma separated: 1,2,3" required></p><p><select name="channel"><option value="stable">Stable</option><option value="candidate">Candidate</option><option value="internal">Internal</option></select></p><button class="button"><?php esc_html_e( 'Create controlled wave', 'ikon-seo' ); ?></button></form></div>
		<table class="widefat striped"><thead><tr><th>ID</th><th><?php esc_html_e( 'Label', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Sites', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Manual result', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( ! $rollouts ) : ?><tr><td colspan="5"><?php esc_html_e( 'No rollout wave exists yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( $rollouts as $wave ) : ?><tr><td><?php echo absint( $wave['id'] ); ?></td><td><?php echo esc_html( $wave['label'] ); ?></td><td><?php echo count( (array) ( $wave['site_ids'] ?? array() ) ); ?></td><td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $wave['status'] ) ) ); ?></td><td><?php if ( 'draft' === $wave['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_production_certification_action"><input type="hidden" name="command" value="approve_rollout"><input type="hidden" name="rollout_id" value="<?php echo absint( $wave['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_production_certification_action' ); ?><button class="button"><?php esc_html_e( 'Approve with different admin', 'ikon-seo' ); ?></button></form><?php elseif ( in_array( $wave['status'], array( 'approved','in_progress','paused' ), true ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_production_certification_action"><input type="hidden" name="command" value="record_rollout_result"><input type="hidden" name="rollout_id" value="<?php echo absint( $wave['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_production_certification_action' ); ?><select name="site_id"><?php foreach ( (array) ( $wave['site_ids'] ?? array() ) as $site_id ) : ?><option value="<?php echo absint( $site_id ); ?>"><?php echo esc_html( 'Site ' . absint( $site_id ) ); ?></option><?php endforeach; ?></select><select name="rollout_status"><option value="successful">Successful</option><option value="failed">Failed</option><option value="deferred">Deferred</option><option value="pending">Pending</option></select><input name="notes" placeholder="Manual deployment evidence"> <button class="button"><?php esc_html_e( 'Record', 'ikon-seo' ); ?></button></form><?php elseif ( 'review_ready' === $wave['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_production_certification_action"><input type="hidden" name="command" value="close_rollout"><input type="hidden" name="rollout_id" value="<?php echo absint( $wave['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_production_certification_action' ); ?><button class="button"><?php esc_html_e( 'Close wave', 'ikon-seo' ); ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table>
		<?php
	}

	private function render_technical_intelligence() {
		$status = $this->technical->status();
		$report = $this->technical->report( false, 100 );
		$agency = Ikon_SEO_Agency::can_manage();
		?>
		<div class="ikon-seo-section-header">
			<div><h2><?php esc_html_e( 'Technical Intelligence', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Multi-source URL discovery, sitemap parity, internal-link graph, redirect evidence and PageSpeed diagnostics.', 'ikon-seo' ); ?></p></div>
			<span class="ikon-seo-pill <?php echo ! empty( $status['ready'] ) ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ! empty( $status['ready'] ) ? number_format_i18n( $status['total_urls'] ) . ' URLs known' : 'Database repair required' ); ?></span>
		</div>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'This workspace is read-only. It identifies evidence and opportunities but does not edit links, redirects, canonicals, sitemaps or performance settings.', 'ikon-seo' ); ?></p></div>
		<div class="ikon-seo-actions">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_refresh_technical_intelligence"><?php wp_nonce_field( 'ikon_seo_refresh_technical_intelligence' ); ?><button class="button button-primary"><?php esc_html_e( 'Refresh discovery and link graph', 'ikon-seo' ); ?></button></form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_check_technical_urls"><?php wp_nonce_field( 'ikon_seo_check_technical_urls' ); ?><label><?php esc_html_e( 'URL batch', 'ikon-seo' ); ?> <input type="number" name="limit" min="1" max="100" value="20" style="width:70px"></label> <button class="button"><?php esc_html_e( 'Check status and redirects', 'ikon-seo' ); ?></button></form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_refresh_pagespeed"><?php wp_nonce_field( 'ikon_seo_refresh_pagespeed' ); ?><label><?php esc_html_e( 'PageSpeed batch', 'ikon-seo' ); ?> <input type="number" name="limit" min="1" max="10" value="3" style="width:60px"></label> <select name="strategy"><option value="mobile"><?php esc_html_e( 'Mobile', 'ikon-seo' ); ?></option><option value="desktop"><?php esc_html_e( 'Desktop', 'ikon-seo' ); ?></option></select> <button class="button"><?php esc_html_e( 'Run performance evidence', 'ikon-seo' ); ?></button></form>
		</div>
		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['total_urls'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Discovered URLs', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['internal_links'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Stored internal links', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['orphans'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Graph orphans', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $status['failed_urls'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Failed checks', 'ikon-seo' ); ?></span></div>
		</div>
		<?php if ( $agency ) : ?>
			<hr><h3><?php esc_html_e( 'Performance data access', 'ikon-seo' ); ?></h3><p class="description"><?php esc_html_e( 'A Google Cloud API key is optional for lab reports and required for direct Chrome User Experience field-data requests. The key is encrypted at rest and never exposed in reports.', 'ikon-seo' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ikon-seo-actions"><input type="hidden" name="action" value="ikon_seo_save_pagespeed_key"><?php wp_nonce_field( 'ikon_seo_save_pagespeed_key' ); ?><input type="password" name="pagespeed_api_key" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( ! empty( $status['pagespeed_key_configured'] ) ? 'Key configured — leave blank to keep it' : 'Paste Google Cloud API key' ); ?>" style="min-width:340px"><label><input type="checkbox" name="clear_key" value="1"> <?php esc_html_e( 'Remove stored key', 'ikon-seo' ); ?></label><button class="button"><?php esc_html_e( 'Save performance-data key', 'ikon-seo' ); ?></button></form>
			<?php if ( ! empty( $status['pagespeed_last_error'] ) ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html( $status['pagespeed_last_error'] ); ?></p></div><?php endif; ?>
		<?php endif; ?>
		<?php if ( is_wp_error( $report ) ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $report->get_error_message() ); ?></p></div><?php else : ?>
			<?php $sections = array( 'broken_internal_links' => 'Broken or redirected internal links', 'redirects' => 'Redirecting URLs', 'redirect_chains' => 'Redirect chains and loops', 'canonical_clusters' => 'Shared canonical clusters', 'orphan_pages' => 'Pages outside the stored link graph', 'deep_pages' => 'Pages deeper than three clicks', 'sitemap_gaps' => 'Published content missing from discovered sitemaps', 'sitemap_only' => 'Sitemap URLs missing from WordPress and the stored link graph', 'weak_anchors' => 'Weak or empty internal anchors', 'nofollow_internal' => 'Nofollow internal links' ); ?>
			<?php foreach ( $sections as $key => $label ) : ?><h3><?php echo esc_html( $label ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Source or page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Destination or evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Details', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( ! empty( $report[ $key ] ) ) : foreach ( array_slice( $report[ $key ], 0, 25 ) as $item ) : ?><tr><td><?php echo esc_html( wp_parse_url( $item['source_url'] ?? $item['url'] ?? $item['canonical_url'] ?? '', PHP_URL_PATH ) ?: '/' ); ?></td><td><?php echo esc_html( wp_parse_url( $item['destination_url'] ?? $item['redirect_target'] ?? $item['urls'] ?? '', PHP_URL_PATH ) ?: ( $item['source_flags'] ?? '—' ) ); ?></td><td><?php echo esc_html( isset( $item['status_code'] ) ? 'HTTP ' . absint( $item['status_code'] ) : ( isset( $item['hops'] ) ? absint( $item['hops'] ) . ' hops' . ( ! empty( $item['loop'] ) ? ' · loop' : '' ) : ( isset( $item['total'] ) ? absint( $item['total'] ) . ' URLs' : ( $item['anchor_text'] ?? ( isset( $item['crawl_depth'] ) ? 'Depth ' . intval( $item['crawl_depth'] ) : 'Review' ) ) ) ) ); ?></td></tr><?php endforeach; else : ?><tr><td colspan="3"><?php esc_html_e( 'No stored issue in this category.', 'ikon-seo' ); ?></td></tr><?php endif; ?></tbody></table><?php endforeach; ?>
			<h3><?php esc_html_e( 'PageSpeed and Core Web Vitals evidence', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Lab scores', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Lab metrics', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Field metrics', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( ! empty( $report['pagespeed'] ) ) : foreach ( array_slice( $report['pagespeed'], 0, 25 ) as $item ) : ?><tr><td><?php echo esc_html( wp_parse_url( $item['url'], PHP_URL_PATH ) ?: '/' ); ?><br><small><?php echo esc_html( ucfirst( $item['strategy'] ) ); ?></small></td><td><?php echo esc_html( sprintf( 'Performance %d · SEO %d · Accessibility %d', $item['performance_score'], $item['seo_score'], $item['accessibility_score'] ) ); ?></td><td><?php echo esc_html( sprintf( 'LCP %.0f ms · TBT %.0f ms · CLS %.3f', $item['lcp_ms'], $item['tbt_ms'], $item['cls'] ) ); ?></td><td><?php echo ! empty( $item['field_data_available'] ) ? esc_html( sprintf( 'LCP %.0f ms · INP %.0f ms · CLS %.3f', $item['field_lcp_ms'], $item['field_inp_ms'], $item['field_cls'] ) ) : esc_html__( 'No sufficient field data', 'ikon-seo' ); ?></td></tr><?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e( 'Run a performance evidence batch to populate this table.', 'ikon-seo' ); ?></td></tr><?php endif; ?></tbody></table>
			<p class="description"><?php echo esc_html( implode( ' ', (array) ( $report['limitations'] ?? array() ) ) ); ?></p>
		<?php endif; ?>
		<?php
	}

	private function render_analytics( array $settings ) {
		$status      = $this->analytics->status();
		$agency      = Ikon_SEO_Agency::can_manage();
		$properties  = $agency && $status['connected'] ? $this->analytics->properties() : array( 'items' => array() );
		$performance = $status['connected'] && $status['property'] ? $this->analytics->report( 28, false ) : null;
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Google Analytics 4', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Read-only landing-page sessions, users, engagement, views and key events. Analytics supports diagnosis but does not reveal Google ranking factors.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo $status['connected'] ? 'is-connected' : 'is-failed'; ?>"><?php echo $status['connected'] ? esc_html__( 'Read-only connected', 'ikon-seo' ) : esc_html__( 'Not connected', 'ikon-seo' ); ?></span>
		</div>

		<?php if ( $agency ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Enable the Google Analytics Admin API and Google Analytics Data API in the same Google Cloud project. Add the callback URL below to the OAuth Web application. Credentials remain encrypted on this WordPress installation.', 'ikon-seo' ); ?></p></div>
			<div class="ikon-seo-connection-box">
				<label><?php esc_html_e( 'Authorized redirect URI', 'ikon-seo' ); ?></label>
				<div class="ikon-seo-copy-row"><code id="ikon-seo-ga-callback"><?php echo esc_html( $status['callback_url'] ); ?></code><button type="button" class="button" data-copy-target="#ikon-seo-ga-callback"><?php esc_html_e( 'Copy', 'ikon-seo' ); ?></button></div>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_ga_save_credentials">
				<?php wp_nonce_field( 'ikon_seo_ga_save_credentials' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'Reuse Search Console credentials', 'ikon-seo' ); ?></th><td><label><input type="checkbox" name="use_gsc_credentials" value="1"> <?php esc_html_e( 'Use the OAuth client already saved for Search Console', 'ikon-seo' ); ?></label></td></tr>
					<tr><th scope="row"><label for="ga_client_id"><?php esc_html_e( 'OAuth client ID', 'ikon-seo' ); ?></label></th><td><input class="large-text code" id="ga_client_id" name="ga_client_id" value="<?php echo esc_attr( $settings['ga_client_id'] ); ?>" autocomplete="off"></td></tr>
					<tr><th scope="row"><label for="ga_client_secret"><?php esc_html_e( 'OAuth client secret', 'ikon-seo' ); ?></label></th><td><input class="regular-text" type="password" id="ga_client_secret" name="ga_client_secret" value="" autocomplete="new-password"><p class="description"><?php echo $settings['ga_client_secret'] ? esc_html__( 'A secret is already encrypted. Leave blank to keep it.', 'ikon-seo' ) : esc_html__( 'Required unless Search Console credentials are reused.', 'ikon-seo' ); ?></p></td></tr>
				</table>
				<?php submit_button( __( 'Save Analytics credentials', 'ikon-seo' ), 'secondary' ); ?>
			</form>
			<?php if ( $status['configured'] && ! $status['connected'] ) : ?>
				<form class="ikon-seo-actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_ga_connect"><?php wp_nonce_field( 'ikon_seo_ga_connect' ); ?><button type="submit" class="button button-primary"><?php esc_html_e( 'Connect Google Analytics', 'ikon-seo' ); ?></button></form>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $status['last_error'] && $agency ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $status['last_error'] ); ?></p></div><?php endif; ?>

		<?php if ( $status['connected'] && $agency ) : ?>
			<hr><h3><?php esc_html_e( 'Analytics property', 'ikon-seo' ); ?></h3>
			<?php if ( is_wp_error( $properties ) ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $properties->get_error_message() ); ?></p></div>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_ga_select_property"><?php wp_nonce_field( 'ikon_seo_ga_select_property' ); ?><select required name="ga_property"><option value=""><?php esc_html_e( 'Select a GA4 property', 'ikon-seo' ); ?></option><?php foreach ( $properties['items'] as $property ) : ?><option value="<?php echo esc_attr( $property['property'] ); ?>" <?php selected( $status['property'], $property['property'] ); ?>><?php echo esc_html( $property['account_name'] . ' — ' . $property['display_name'] . ' (' . $property['property'] . ')' ); ?></option><?php endforeach; ?></select> <?php submit_button( __( 'Save property', 'ikon-seo' ), 'secondary', 'submit', false ); ?></form>
			<?php endif; ?>
			<div class="ikon-seo-actions">
				<?php if ( $status['property'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_ga_refresh"><?php wp_nonce_field( 'ikon_seo_ga_refresh' ); ?><button type="submit" class="button"><?php esc_html_e( 'Refresh Analytics now', 'ikon-seo' ); ?></button></form><?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_ga_disconnect"><?php wp_nonce_field( 'ikon_seo_ga_disconnect' ); ?><button type="submit" class="button" data-confirm="<?php esc_attr_e( 'Disconnect Analytics and remove its stored refresh token?', 'ikon-seo' ); ?>"><?php esc_html_e( 'Disconnect', 'ikon-seo' ); ?></button></form>
			</div>
		<?php endif; ?>

		<?php if ( is_array( $performance ) ) : ?>
			<hr><h3><?php echo esc_html( sprintf( 'Landing-page behaviour: %s to %s', $performance['period']['start'], $performance['period']['end'] ) ); ?></h3>
			<div class="ikon-seo-metrics">
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $performance['totals']['sessions'] ) ); ?></strong><span><?php esc_html_e( 'Sessions', 'ikon-seo' ); ?></span><small><?php echo esc_html( null === $performance['changes']['sessions'] ? 'New data' : ( $performance['changes']['sessions'] > 0 ? '+' : '' ) . $performance['changes']['sessions'] . '%' ); ?></small></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $performance['totals']['active_users'] ) ); ?></strong><span><?php esc_html_e( 'Active users', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $performance['totals']['engagement_rate'] * 100, 1 ) . '%' ); ?></strong><span><?php esc_html_e( 'Engagement rate', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $performance['totals']['key_events'], 1 ) ); ?></strong><span><?php esc_html_e( 'Key events', 'ikon-seo' ); ?></span></div>
			</div>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Landing page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Sessions', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Users', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Engagement', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Views', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Key events', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( array_slice( $performance['top_pages'], 0, 100 ) as $row ) : ?><tr><td><a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $row['path'] ); ?></a></td><td><?php echo esc_html( number_format_i18n( $row['sessions'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['active_users'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['engagement_rate'] * 100, 1 ) . '%' ); ?></td><td><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['key_events'], 1 ) ); ?></td></tr><?php endforeach; ?></tbody></table>
			<p class="description"><?php echo esc_html( $performance['data_note'] ); ?></p>
		<?php elseif ( is_wp_error( $performance ) ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html( $performance->get_error_message() ); ?></p></div><?php elseif ( ! $status['connected'] ) : ?><p><?php esc_html_e( 'Analytics is optional. Connect it to add real on-site behaviour and key-event evidence to page diagnoses.', 'ikon-seo' ); ?></p><?php endif; ?>
		<?php
	}

	private function render_queue() {
		$counts = $this->queue->counts();
		$items  = $this->queue->list_items( '', 100 );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Page-plan queue', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Import approved keyword plans. The queue never writes content by itself; a connected workflow must generate and return every complete page payload.', 'ikon-seo' ); ?></p>
			</div>
		</div>
		<div class="ikon-seo-metrics">
			<?php foreach ( array( 'planned' => 'Planned', 'claimed' => 'Claimed', 'completed' => 'Completed', 'failed' => 'Failed', 'paused' => 'Paused' ) as $key => $label ) : ?>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( $counts[ $key ] ); ?></strong><span><?php echo esc_html( $label ); ?></span></div>
			<?php endforeach; ?>
		</div>

		<div class="ikon-seo-connection-box">
			<h3><?php esc_html_e( 'Import CSV page plan', 'ikon-seo' ); ?></h3>
			<p><?php esc_html_e( 'Maximum 500 rows and 2 MB. Duplicate active keyword/location combinations are skipped.', 'ikon-seo' ); ?></p>
			<p><code>keyword,service,location,page_type,language,template_hint,desired_slug,source_page_id,priority</code></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_queue_import">
				<?php wp_nonce_field( 'ikon_seo_queue_import' ); ?>
				<input required type="file" name="queue_file" accept=".csv,text/csv">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Import page plans', 'ikon-seo' ); ?></button>
			</form>
		</div>

		<table class="widefat striped ikon-seo-log">
			<thead><tr><th><?php esc_html_e( 'Plan', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Target', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Attempts', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Result / error', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Actions', 'ikon-seo' ); ?></th></tr></thead>
			<tbody>
				<?php if ( ! $items ) : ?><tr><td colspan="6"><?php esc_html_e( 'No page plans have been imported.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
				<?php foreach ( $items as $item ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $item['keyword'] ); ?></strong><br><small><?php echo esc_html( $item['service'] ); ?></small></td>
						<td><?php echo esc_html( implode( ' · ', array_filter( array( $item['location'], $item['page_type'], $item['language'] ) ) ) ); ?></td>
						<td><span class="ikon-seo-pill <?php echo 'completed' === $item['status'] ? 'is-connected' : ( 'failed' === $item['status'] ? 'is-failed' : '' ); ?>"><?php echo esc_html( ucfirst( $item['status'] ) ); ?></span></td>
						<td><?php echo esc_html( $item['attempts'] ); ?></td>
						<td>
							<?php if ( $item['post_id'] ) : ?><a href="<?php echo esc_url( $item['edit_url'] ); ?>"><?php echo esc_html( 'Draft #' . $item['post_id'] ); ?></a><?php endif; ?>
							<?php if ( $item['last_error'] ) : ?><span class="ikon-seo-error-text"><?php echo esc_html( $item['last_error'] ); ?></span><?php endif; ?>
						</td>
						<td>
							<div class="ikon-seo-actions is-stacked">
								<?php
								$actions = array();
								if ( in_array( $item['status'], array( 'failed', 'paused', 'claimed' ), true ) ) {
									$actions['planned'] = 'Reset';
								}
								if ( 'planned' === $item['status'] ) {
									$actions['paused'] = 'Pause';
								}
								if ( in_array( $item['status'], array( 'completed', 'failed', 'paused' ), true ) ) {
									$actions['archived'] = 'Archive';
								}
								foreach ( $actions as $status => $label ) :
									?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="ikon_seo_queue_status">
										<input type="hidden" name="queue_id" value="<?php echo esc_attr( $item['id'] ); ?>">
										<input type="hidden" name="queue_status" value="<?php echo esc_attr( $status ); ?>">
										<?php wp_nonce_field( 'ikon_seo_queue_status_' . $item['id'] ); ?>
										<button type="submit" class="button button-small"><?php echo esc_html( $label ); ?></button>
									</form>
								<?php endforeach; ?>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_monitor() {
		$summary = $this->monitor->summary();
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Content refresh monitor', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Flags review dates and meaningful Search Console declines. It never edits, regenerates or publishes a page automatically.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo $summary['enabled'] ? 'is-connected' : ''; ?>"><?php echo $summary['enabled'] ? esc_html__( 'Enabled', 'ikon-seo' ) : esc_html__( 'Disabled', 'ikon-seo' ); ?></span>
		</div>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo esc_html( $summary['counts']['overdue'] ); ?></strong><span><?php esc_html_e( 'Overdue', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( $summary['counts']['due_soon'] ); ?></strong><span><?php esc_html_e( 'Due soon', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( $summary['counts']['performance'] ); ?></strong><span><?php esc_html_e( 'Performance alerts', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( $summary['next_cron_gmt'] ? gmdate( 'M j', strtotime( $summary['next_cron_gmt'] ) ) : '—' ); ?></strong><span><?php esc_html_e( 'Next daily check', 'ikon-seo' ); ?></span></div>
		</div>

		<form class="ikon-seo-actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_monitor_run">
			<?php wp_nonce_field( 'ikon_seo_monitor_run' ); ?>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Run monitor now', 'ikon-seo' ); ?></button>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=settings' ) ); ?>"><?php esc_html_e( 'Monitoring settings', 'ikon-seo' ); ?></a>
		</form>

		<div class="ikon-seo-connection-box">
			<h3><?php esc_html_e( 'Add an existing page to the review schedule', 'ikon-seo' ); ?></h3>
			<p><?php echo esc_html( sprintf( 'Enter a WordPress page or post ID. It will be marked reviewed today and scheduled again after %d days.', absint( Ikon_SEO_Plugin::settings()['default_review_days'] ) ) ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_monitor_schedule">
				<?php wp_nonce_field( 'ikon_seo_monitor_schedule' ); ?>
				<label for="monitor_post_id" class="screen-reader-text"><?php esc_html_e( 'Page or post ID', 'ikon-seo' ); ?></label>
				<input required type="number" min="1" id="monitor_post_id" name="post_id" placeholder="<?php esc_attr_e( 'Page or post ID', 'ikon-seo' ); ?>">
				<button type="submit" class="button"><?php esc_html_e( 'Add to schedule', 'ikon-seo' ); ?></button>
			</form>
		</div>

		<table class="widefat striped ikon-seo-log">
			<thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Reason', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Review date', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Performance', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead>
			<tbody>
				<?php if ( ! $summary['items'] ) : ?><tr><td colspan="5"><?php esc_html_e( 'No refresh recommendations currently meet the configured thresholds.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
				<?php foreach ( $summary['items'] as $item ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( $item['edit_url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a><br><small><?php echo esc_html( $item['url'] ); ?></small></td>
						<td><span class="ikon-seo-pill <?php echo 'overdue' === $item['reason'] || 'performance' === $item['reason'] ? 'is-failed' : ''; ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['reason'] ) ) ); ?></span></td>
						<td><?php echo esc_html( $item['next_review_date'] ?: 'Not scheduled' ); ?></td>
						<td>
							<?php if ( ! empty( $item['performance'] ) ) : ?>
								<?php echo esc_html( $item['performance']['impressions_change'] . '% impressions · ' . ( $item['performance']['clicks_change'] ?? 0 ) . '% clicks' ); ?>
							<?php else : ?>—<?php endif; ?>
						</td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="ikon_seo_monitor_reviewed">
								<input type="hidden" name="post_id" value="<?php echo esc_attr( $item['post_id'] ); ?>">
								<?php wp_nonce_field( 'ikon_seo_monitor_reviewed_' . $item['post_id'] ); ?>
								<button type="submit" class="button button-small"><?php esc_html_e( 'Mark reviewed', 'ikon-seo' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_migration( array $settings ) {
		$report = get_transient( 'ikon_seo_migration_report_' . get_current_user_id() );
		?>
		<h2><?php esc_html_e( 'Domain migration safeguards', 'ikon-seo' ); ?></h2>
		<p><?php esc_html_e( 'Preview the exact stored references before applying a domain change. Nothing is updated automatically.', 'ikon-seo' ); ?></p>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'Applying a migration updates matched Ikon SEO, Elementor and Rank Math references, rebinds local records, saves per-page snapshots, pauses remote actions, revokes the connection key, and clears site-bound Google connections.', 'ikon-seo' ); ?></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_preview_migration">
			<?php wp_nonce_field( 'ikon_seo_preview_migration' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="migration_old_url"><?php esc_html_e( 'Old website URL', 'ikon-seo' ); ?></label></th>
					<td><input required class="regular-text" type="url" id="migration_old_url" name="old_url" value="<?php echo esc_attr( $settings['profile_home_url'] ?: $settings['business_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="migration_new_url"><?php esc_html_e( 'New website URL', 'ikon-seo' ); ?></label></th>
					<td><input required class="regular-text" type="url" id="migration_new_url" name="new_url" value="<?php echo esc_attr( home_url( '/' ) ); ?>"></td>
				</tr>
			</table>
			<?php submit_button( __( 'Preview domain migration', 'ikon-seo' ), 'secondary' ); ?>
		</form>

		<?php if ( is_array( $report ) && isset( $report['affected_posts'] ) ) : ?>
			<h3><?php echo esc_html( sprintf( 'Preview: %d affected pages or posts', absint( $report['affected_posts'] ) ) ); ?></h3>
			<p><code><?php echo esc_html( $report['old_url'] ); ?></code> → <code><?php echo esc_html( $report['new_url'] ); ?></code></p>
			<table class="widefat striped ikon-seo-log">
				<thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Matched fields', 'ikon-seo' ); ?></th></tr></thead>
				<tbody>
					<?php if ( ! $report['items'] ) : ?><tr><td colspan="3"><?php esc_html_e( 'No stored references match the old URL.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
					<?php foreach ( array_slice( $report['items'], 0, 200 ) as $item ) : ?>
						<tr><td><a href="<?php echo esc_url( get_edit_post_link( $item['post_id'] ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></td><td><?php echo esc_html( $item['status'] ); ?></td><td><?php echo esc_html( implode( ', ', $item['fields'] ) ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $report['affected_posts'] ) : ?>
				<form class="ikon-seo-actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_apply_migration">
					<input type="hidden" name="old_url" value="<?php echo esc_attr( $report['old_url'] ); ?>">
					<input type="hidden" name="new_url" value="<?php echo esc_attr( $report['new_url'] ); ?>">
					<?php wp_nonce_field( 'ikon_seo_apply_migration' ); ?>
					<button type="submit" class="button button-primary" data-confirm="<?php esc_attr_e( 'Apply this exact domain migration now? The connection key will be revoked.', 'ikon-seo' ); ?>"><?php esc_html_e( 'Apply approved migration', 'ikon-seo' ); ?></button>
				</form>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	private function render_settings( array $settings ) {
		?>
		<h2><?php esc_html_e( 'Workflow security and operating limits', 'ikon-seo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Business identity, schema and design rules are managed on the Website Profile tab.', 'ikon-seo' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_save_settings">
			<?php wp_nonce_field( 'ikon_seo_save_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Publishing safety', 'ikon-seo' ); ?></th>
					<td><label><input type="checkbox" name="draft_only" value="1" <?php checked( $settings['draft_only'] ); ?>> <?php esc_html_e( 'Always save externally created or improved pages as drafts', 'ikon-seo' ); ?></label><p class="description"><?php esc_html_e( 'Improve mode always creates a separate review copy.', 'ikon-seo' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Profile-bound writes', 'ikon-seo' ); ?></th>
					<td><label><input type="checkbox" name="require_profile_match" value="1" <?php checked( $settings['require_profile_match'] ); ?>> <?php esc_html_e( 'Require the current Website Profile ID on every remote page write', 'ikon-seo' ); ?></label><p class="description"><?php esc_html_e( 'Recommended. This blocks content prepared for another client website.', 'ikon-seo' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Remote actions', 'ikon-seo' ); ?></th>
					<td><label><input type="checkbox" name="remote_actions" value="1" <?php checked( $settings['remote_actions'] ); ?>> <?php esc_html_e( 'Enable authenticated remote actions', 'ikon-seo' ); ?></label><p class="description"><?php esc_html_e( 'Turn this off to pause every connection immediately without deleting its key.', 'ikon-seo' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Connection key scopes', 'ikon-seo' ); ?></th>
					<td>
					<?php foreach ( array( 'read' => 'Read profiles, audits, pages, media and connected insights', 'draft' => 'Create and update drafts or page-plan claims', 'approve' => 'Merge and rollback remotely' ) as $scope => $label ) : ?>
							<label class="ikon-seo-scope"><input type="checkbox" name="key_scopes[]" value="<?php echo esc_attr( $scope ); ?>" <?php checked( in_array( $scope, (array) $settings['key_scopes'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Remote approval', 'ikon-seo' ); ?></th>
					<td><label><input type="checkbox" name="remote_merge" value="1" <?php checked( $settings['remote_merge'] ); ?>> <?php esc_html_e( 'Allow remote merge and rollback when the key also has approve scope', 'ikon-seo' ); ?></label><p class="description"><?php esc_html_e( 'Disabled by default. Administrator approval on the Reviews tab remains available.', 'ikon-seo' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="rate_limit"><?php esc_html_e( 'Hourly request limit', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="rate_limit" name="rate_limit" min="10" max="300" value="<?php echo esc_attr( $settings['rate_limit'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="max_payload_kb"><?php esc_html_e( 'Maximum request size', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="max_payload_kb" name="max_payload_kb" min="128" max="4096" value="<?php echo esc_attr( $settings['max_payload_kb'] ); ?>"> KB</td>
				</tr>
				<tr>
					<th scope="row"><label for="inventory_limit"><?php esc_html_e( 'Inventory scan limit', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="inventory_limit" name="inventory_limit" min="50" max="2000" value="<?php echo esc_attr( $settings['inventory_limit'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="image_audit_limit"><?php esc_html_e( 'Image audit limit', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="image_audit_limit" name="image_audit_limit" min="50" max="1000" value="<?php echo esc_attr( $settings['image_audit_limit'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="allowed_media_hosts"><?php esc_html_e( 'Approved image hosts', 'ikon-seo' ); ?></label></th>
					<td><textarea class="large-text code" rows="3" id="allowed_media_hosts" name="allowed_media_hosts"><?php echo esc_textarea( $settings['allowed_media_hosts'] ); ?></textarea><p class="description"><?php esc_html_e( 'One hostname per line or separated by commas. The website hostname is always allowed.', 'ikon-seo' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Local SEO module', 'ikon-seo' ); ?></th>
					<td><label><input type="checkbox" name="local_module_enabled" value="1" <?php checked( $settings['local_module_enabled'] ); ?>> <?php esc_html_e( 'Enable profile-bound locations, local validation, schema, NAP, citations and rank workspace', 'ikon-seo' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="local_similarity_threshold"><?php esc_html_e( 'Local-page similarity warning', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="local_similarity_threshold" name="local_similarity_threshold" min="60" max="95" value="<?php echo esc_attr( $settings['local_similarity_threshold'] ); ?>">%<p class="description"><?php esc_html_e( 'High similarity between city pages is treated as possible doorway-page risk.', 'ikon-seo' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="citation_review_days"><?php esc_html_e( 'Citation review interval', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="citation_review_days" name="citation_review_days" min="30" max="730" value="<?php echo esc_attr( $settings['citation_review_days'] ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content refresh monitoring', 'ikon-seo' ); ?></th>
					<td><label><input type="checkbox" name="monitoring_enabled" value="1" <?php checked( $settings['monitoring_enabled'] ); ?>> <?php esc_html_e( 'Run a daily, recommendation-only content refresh check', 'ikon-seo' ); ?></label><p class="description"><?php esc_html_e( 'WP-Cron depends on website traffic unless your host connects it to a real server scheduler.', 'ikon-seo' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="default_review_days"><?php esc_html_e( 'Default review interval', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="default_review_days" name="default_review_days" min="30" max="730" value="<?php echo esc_attr( $settings['default_review_days'] ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="review_alert_days"><?php esc_html_e( 'Upcoming review window', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="review_alert_days" name="review_alert_days" min="1" max="90" value="<?php echo esc_attr( $settings['review_alert_days'] ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="performance_drop_percent"><?php esc_html_e( 'Performance decline threshold', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="performance_drop_percent" name="performance_drop_percent" min="10" max="90" value="<?php echo esc_attr( $settings['performance_drop_percent'] ); ?>">%</td>
				</tr>
				<tr>
					<th scope="row"><label for="performance_min_impressions"><?php esc_html_e( 'Minimum previous impressions', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="performance_min_impressions" name="performance_min_impressions" min="10" max="100000" value="<?php echo esc_attr( $settings['performance_min_impressions'] ); ?>"><p class="description"><?php esc_html_e( 'Low-volume pages are excluded from percentage-based decline alerts.', 'ikon-seo' ); ?></p></td>
				</tr>
			</table>
			<?php submit_button( __( 'Save workflow settings', 'ikon-seo' ) ); ?>
		</form>
		<?php
		return;

		?>
		<h2><?php esc_html_e( 'Site profile and design defaults', 'ikon-seo' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_save_settings">
			<?php wp_nonce_field( 'ikon_seo_save_settings' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="site_name"><?php esc_html_e( 'Site name', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" id="site_name" name="site_name" value="<?php echo esc_attr( $settings['site_name'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="target_market"><?php esc_html_e( 'Target market', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" id="target_market" name="target_market" value="<?php echo esc_attr( $settings['target_market'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="default_language"><?php esc_html_e( 'Default language', 'ikon-seo' ); ?></label></th>
					<td><input id="default_language" name="default_language" value="<?php echo esc_attr( $settings['default_language'] ); ?>" pattern="[a-z]{2,3}(-[A-Z]{2})?"></td>
				</tr>
				<tr>
					<th scope="row"><label for="business_phone"><?php esc_html_e( 'Business phone', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" id="business_phone" name="business_phone" value="<?php echo esc_attr( $settings['business_phone'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="whatsapp_url"><?php esc_html_e( 'WhatsApp URL', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" type="url" id="whatsapp_url" name="whatsapp_url" value="<?php echo esc_attr( $settings['whatsapp_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="business_url"><?php esc_html_e( 'Business URL', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" type="url" id="business_url" name="business_url" value="<?php echo esc_attr( $settings['business_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="business_logo"><?php esc_html_e( 'Business logo URL', 'ikon-seo' ); ?></label></th>
					<td><input class="regular-text" type="url" id="business_logo" name="business_logo" value="<?php echo esc_attr( $settings['business_logo'] ); ?>"></td>
				</tr>
				<?php foreach ( array(
					'primary_color'   => 'Primary colour',
					'secondary_color' => 'Secondary colour',
					'accent_color'    => 'Accent colour',
					'heading_color'   => 'Heading colour',
					'text_color'      => 'Text colour',
					'surface_color'   => 'Section background',
				) as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input type="color" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $settings[ $key ] ); ?>"></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="content_width"><?php esc_html_e( 'Content width', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="content_width" name="content_width" min="800" max="1600" value="<?php echo esc_attr( $settings['content_width'] ); ?>"> px</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Publishing safety', 'ikon-seo' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="draft_only" value="1" <?php checked( $settings['draft_only'] ); ?>>
							<?php esc_html_e( 'Always save externally created or improved pages as drafts', 'ikon-seo' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Recommended. Improve mode always creates a separate review copy.', 'ikon-seo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Remote actions', 'ikon-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="remote_actions" value="1" <?php checked( $settings['remote_actions'] ); ?>> <?php esc_html_e( 'Enable authenticated remote actions', 'ikon-seo' ); ?></label>
						<p class="description"><?php esc_html_e( 'Turn this off to pause every connection immediately without deleting its key.', 'ikon-seo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Connection key scopes', 'ikon-seo' ); ?></th>
					<td>
						<?php foreach ( array( 'read' => 'Read audits, pages and media', 'draft' => 'Create and update drafts', 'approve' => 'Merge and rollback remotely' ) as $scope => $label ) : ?>
							<label class="ikon-seo-scope"><input type="checkbox" name="key_scopes[]" value="<?php echo esc_attr( $scope ); ?>" <?php checked( in_array( $scope, (array) $settings['key_scopes'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Remote approval', 'ikon-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="remote_merge" value="1" <?php checked( $settings['remote_merge'] ); ?>> <?php esc_html_e( 'Allow remote merge and rollback when the key also has approve scope', 'ikon-seo' ); ?></label>
						<p class="description"><?php esc_html_e( 'Disabled by default. Admin approval on the Reviews tab remains available.', 'ikon-seo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="rate_limit"><?php esc_html_e( 'Hourly request limit', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="rate_limit" name="rate_limit" min="10" max="300" value="<?php echo esc_attr( $settings['rate_limit'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="max_payload_kb"><?php esc_html_e( 'Maximum request size', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="max_payload_kb" name="max_payload_kb" min="128" max="4096" value="<?php echo esc_attr( $settings['max_payload_kb'] ); ?>"> KB</td>
				</tr>
				<tr>
					<th scope="row"><label for="inventory_limit"><?php esc_html_e( 'Inventory scan limit', 'ikon-seo' ); ?></label></th>
					<td><input type="number" id="inventory_limit" name="inventory_limit" min="50" max="1000" value="<?php echo esc_attr( $settings['inventory_limit'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="allowed_media_hosts"><?php esc_html_e( 'Approved image hosts', 'ikon-seo' ); ?></label></th>
					<td>
						<textarea class="large-text code" rows="3" id="allowed_media_hosts" name="allowed_media_hosts"><?php echo esc_textarea( $settings['allowed_media_hosts'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One hostname per line or separated by commas. The website hostname is always allowed.', 'ikon-seo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Semantic FAQ markup', 'ikon-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="semantic_faq" value="1" <?php checked( $settings['semantic_faq'] ); ?>> <?php esc_html_e( 'Allow FAQPage semantic schema when matching FAQs are visible', 'ikon-seo' ); ?></label>
						<p class="description"><?php esc_html_e( 'FAQ rich results are retired; this setting does not promise a Google enhancement.', 'ikon-seo' ); ?></p>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Verified office and business-entity schema', 'ikon-seo' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Enable this only for a genuine, verifiable office. Empty or unverified data will not generate a local-business node.', 'ikon-seo' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Verified business entity', 'ikon-seo' ); ?></th>
					<td><label><input type="checkbox" name="verified_business" value="1" <?php checked( $settings['verified_business'] ); ?>> <?php esc_html_e( 'The office details below are accurate and publicly verifiable', 'ikon-seo' ); ?></label></td>
				</tr>
				<?php foreach ( array(
					'address_street'   => 'Street address',
					'address_locality' => 'Locality / city',
					'address_region'   => 'Region',
					'address_postal'   => 'Postal code',
					'address_country'  => 'Country code',
					'latitude'         => 'Latitude',
					'longitude'        => 'Longitude',
					'price_range'      => 'Price range',
				) as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input class="regular-text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $settings[ $key ] ); ?>"></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="opening_hours"><?php esc_html_e( 'Opening hours', 'ikon-seo' ); ?></label></th>
					<td><textarea class="large-text code" rows="3" id="opening_hours" name="opening_hours"><?php echo esc_textarea( $settings['opening_hours'] ); ?></textarea><p class="description"><?php esc_html_e( 'One Schema.org opening-hours value per line, for example Mo-Fr 09:00-18:00.', 'ikon-seo' ); ?></p></td>
				</tr>
			</table>

			<?php submit_button( __( 'Save settings', 'ikon-seo' ) ); ?>
		</form>
		<?php
	}

	private function render_activity() {
		$logs = $this->logger->recent( 100 );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Recent activity', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Payload content and connection keys are not stored in this log.', 'ikon-seo' ); ?></p>
			</div>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=activity' ) ); ?>"><?php esc_html_e( 'Refresh activity', 'ikon-seo' ); ?></a>
		</div>
		<table class="widefat striped ikon-seo-log">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Time (UTC)', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Source', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Message', 'ikon-seo' ); ?></th>
					<th><?php esc_html_e( 'Request ID', 'ikon-seo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $logs ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'No activity recorded yet.', 'ikon-seo' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( $log['created_at'] ); ?></td>
						<td><?php echo esc_html( ucfirst( $log['action'] ) ); ?></td>
						<td><span class="ikon-seo-pill <?php echo 'success' === $log['status'] ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ucfirst( $log['status'] ) ); ?></span></td>
						<td><?php echo $log['post_id'] ? '<a href="' . esc_url( get_edit_post_link( $log['post_id'] ) ) . '">#' . absint( $log['post_id'] ) . '</a>' : '—'; ?></td>
						<td><?php echo $log['source_id'] ? '<a href="' . esc_url( get_edit_post_link( $log['source_id'] ) ) . '">#' . absint( $log['source_id'] ) . '</a>' : '—'; ?></td>
						<td><?php echo esc_html( $log['message'] ); ?></td>
						<td><code><?php echo esc_html( substr( $log['request_id'], 0, 12 ) ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	public function save_settings() {
		$this->guard_agency( 'ikon_seo_save_settings' );

		$current = Ikon_SEO_Plugin::settings();
		$current['draft_only']          = isset( $_POST['draft_only'] ) ? 1 : 0;
		$current['require_profile_match']= isset( $_POST['require_profile_match'] ) ? 1 : 0;
		$current['remote_actions']      = isset( $_POST['remote_actions'] ) ? 1 : 0;
		$current['remote_merge']        = isset( $_POST['remote_merge'] ) ? 1 : 0;
		$current['rate_limit']          = max( 10, min( 300, absint( $_POST['rate_limit'] ?? 60 ) ) );
		$current['max_payload_kb']      = max( 128, min( 4096, absint( $_POST['max_payload_kb'] ?? 1024 ) ) );
		$current['inventory_limit']     = max( 50, min( 2000, absint( $_POST['inventory_limit'] ?? 300 ) ) );
		$current['image_audit_limit']   = max( 50, min( 1000, absint( $_POST['image_audit_limit'] ?? 300 ) ) );
		$current['allowed_media_hosts'] = sanitize_textarea_field( wp_unslash( $_POST['allowed_media_hosts'] ?? '' ) );
		$current['local_module_enabled']= isset( $_POST['local_module_enabled'] ) ? 1 : 0;
		$current['local_similarity_threshold'] = max( 60, min( 95, absint( $_POST['local_similarity_threshold'] ?? 78 ) ) );
		$current['citation_review_days']= max( 30, min( 730, absint( $_POST['citation_review_days'] ?? 180 ) ) );
		$current['monitoring_enabled']  = isset( $_POST['monitoring_enabled'] ) ? 1 : 0;
		$current['default_review_days'] = max( 30, min( 730, absint( $_POST['default_review_days'] ?? 180 ) ) );
		$current['review_alert_days']   = max( 1, min( 90, absint( $_POST['review_alert_days'] ?? 14 ) ) );
		$current['performance_drop_percent'] = max( 10, min( 90, absint( $_POST['performance_drop_percent'] ?? 30 ) ) );
		$current['performance_min_impressions'] = max( 10, min( 100000, absint( $_POST['performance_min_impressions'] ?? 50 ) ) );
		$scopes = array_intersect( array_map( 'sanitize_key', (array) ( $_POST['key_scopes'] ?? array() ) ), array( 'read', 'draft', 'approve' ) );
		$current['key_scopes'] = $scopes ? array_values( $scopes ) : array( 'read' );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $current, false );
		$this->inventory->clear_cache();
		$this->rank_math->clear_cache();
		$this->image_audit->clear_cache();
		$this->redirect_audit->clear_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=settings&updated=1' ) );
		exit;
	}

	public function run_auto_discovery() {
		$this->guard_agency( 'ikon_seo_run_auto_discovery' );
		$max_pages = absint( $_POST['max_pages'] ?? 100 );
		$result = $this->auto_discovery->run( true, $max_pages, 'admin' );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'auto-discovery', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=auto-discovery&auto-discovery-run=1' ) );
		exit;
	}

	public function apply_auto_discovery() {
		$this->guard_agency( 'ikon_seo_apply_auto_discovery' );
		$fields = is_array( $_POST['fields'] ?? null )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['fields'] ) )
			: array();
		$result = $this->auto_discovery->apply(
			$fields,
			! empty( $_POST['overwrite'] ),
			! empty( $_POST['create_workflow'] ),
			! empty( $_POST['run_safe_task'] ),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'auto-discovery', $result->get_error_message() );
		}
		$this->diagnostics->clear_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=auto-discovery&auto-discovery-applied=1' ) );
		exit;
	}

	public function save_auto_discovery_settings() {
		$this->guard_agency( 'ikon_seo_save_auto_discovery_settings' );
		$this->auto_discovery->save_settings(
			array(
				'enabled'           => ! empty( $_POST['enabled'] ),
				'max_pages'         => absint( $_POST['max_pages'] ?? 100 ),
				'include_connected' => ! empty( $_POST['include_connected'] ),
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=auto-discovery&auto-discovery-settings=1' ) );
		exit;
	}




	public function accept_high_confidence_facts() {
		$this->guard_agency( 'ikon_seo_accept_high_confidence_facts' );
		$result = $this->discovery_review->accept_high_confidence(
			get_current_user_id(),
			sanitize_text_field( wp_unslash( $_POST['generated_at'] ?? '' ) )
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'discovery-review', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=discovery-review&discovery-review-updated=1' ) );
		exit;
	}

	public function update_discovery_fact() {
		$this->guard_agency( 'ikon_seo_update_discovery_fact' );
		$result = $this->discovery_review->update_fact(
			sanitize_text_field( wp_unslash( $_POST['fact_id'] ?? '' ) ),
			sanitize_key( wp_unslash( $_POST['status'] ?? '' ) ),
			wp_unslash( $_POST['value'] ?? '' ),
			get_current_user_id(),
			sanitize_text_field( wp_unslash( $_POST['generated_at'] ?? '' ) )
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'discovery-review', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=discovery-review&discovery-review-updated=1' ) );
		exit;
	}

	public function resolve_discovery_conflict() {
		$this->guard_agency( 'ikon_seo_resolve_discovery_conflict' );
		$result = $this->discovery_review->resolve_conflict(
			sanitize_text_field( wp_unslash( $_POST['conflict_id'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['selected_value'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['custom_value'] ?? '' ) ),
			get_current_user_id(),
			sanitize_text_field( wp_unslash( $_POST['generated_at'] ?? '' ) )
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'discovery-review', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=discovery-review&discovery-review-updated=1' ) );
		exit;
	}

	public function apply_confirmed_discovery_facts() {
		$this->guard_agency( 'ikon_seo_apply_confirmed_discovery_facts' );
		$result = $this->discovery_review->apply_confirmed( get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'discovery-review', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=discovery-review&discovery-review-applied=1' ) );
		exit;
	}

	public function run_guided_launch() {
		$this->guard_agency( 'ikon_seo_run_guided_launch' );
		$result = $this->guided_launch->activate(
			array(
				'create_workflow' => ! empty( $_POST['create_workflow'] ),
				'run_safe_tasks'  => ! empty( $_POST['run_safe_tasks'] ),
				'task_batch'      => absint( $_POST['task_batch'] ?? 3 ),
				'build_plan'      => ! empty( $_POST['build_plan'] ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'guided-launch', $result->get_error_message() );
		}
		$this->diagnostics->clear_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=guided-launch&guided-launch-updated=1' ) );
		exit;
	}

	public function acknowledge_discovery_conflicts() {
		$this->guard_agency( 'ikon_seo_acknowledge_discovery_conflicts' );
		$this->guided_launch->acknowledge_conflicts( ! empty( $_POST['acknowledged'] ), get_current_user_id() );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=guided-launch&guided-launch-updated=1' ) );
		exit;
	}


	public function rebuild_opportunity_engine() {
		$this->require_workflow_manager( 'rebuild the Opportunity Engine' );
		check_admin_referer( 'ikon_seo_rebuild_opportunity_engine' );
		$result = $this->opportunity_engine->rebuild( absint( $_POST['limit'] ?? 300 ), get_current_user_id(), 'admin' );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'opportunity-engine', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=opportunity-engine&opportunity-engine-rebuilt=1' ) );
		exit;
	}

	public function import_opportunity_evidence() {
		$this->require_workflow_manager( 'import keyword evidence' );
		check_admin_referer( 'ikon_seo_import_opportunity_evidence' );
		$file = $_FILES['evidence_csv'] ?? array();
		if ( empty( $file['tmp_name'] ) || ! empty( $file['error'] ) || (int) ( $file['size'] ?? 0 ) > 2 * MB_IN_BYTES ) {
			$this->redirect_error( 'opportunity-engine', __( 'Upload a readable CSV file no larger than 2 MB.', 'ikon-seo' ) );
		}
		$name = sanitize_file_name( $file['name'] ?? '' );
		if ( 'csv' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
			$this->redirect_error( 'opportunity-engine', __( 'Only CSV evidence files are accepted.', 'ikon-seo' ) );
		}
		$result = $this->opportunity_engine->import_csv( $file['tmp_name'], sanitize_key( $_POST['source'] ?? 'manual' ), get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'opportunity-engine', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=opportunity-engine&opportunity-evidence-imported=1' ) );
		exit;
	}

	public function update_opportunity_status() {
		$this->require_workflow_manager( 'review an Opportunity Engine item' );
		$id = absint( $_POST['opportunity_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_update_opportunity_status_' . $id );
		$result = $this->opportunity_engine->update_status( $id, sanitize_key( $_POST['status'] ?? '' ), sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ), get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'opportunity-engine', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=opportunity-engine&opportunity-updated=1' ) );
		exit;
	}

	public function save_opportunity_engine_settings() {
		$this->require_workflow_manager( 'change Opportunity Engine settings' );
		check_admin_referer( 'ikon_seo_save_opportunity_engine_settings' );
		$settings = Ikon_SEO_Plugin::settings();
		$settings['opportunity_engine_enabled'] = ! empty( $_POST['enabled'] ) ? 1 : 0;
		$settings['opportunity_engine_max_items'] = max( 25, min( 1000, absint( $_POST['max_items'] ?? 300 ) ) );
		$settings['opportunity_engine_stale_days'] = max( 7, min( 365, absint( $_POST['stale_days'] ?? 60 ) ) );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=opportunity-engine&updated=1' ) );
		exit;
	}


	public function create_content_brief() {
		$this->require_workflow_manager( 'create a controlled content brief' );
		$id = absint( $_POST['opportunity_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_create_content_brief_' . $id );
		$result = $this->content_workbench->create_brief( $id, get_current_user_id() );
		$this->content_workbench_redirect( $result );
	}

	public function approve_content_brief() {
		$this->require_workflow_manager( 'approve a controlled content brief' );
		$id = absint( $_POST['brief_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_approve_content_brief_' . $id );
		$result = $this->content_workbench->approve_brief( $id, sanitize_text_field( wp_unslash( $_POST['evidence_hash'] ?? '' ) ), get_current_user_id() );
		$this->content_workbench_redirect( $result );
	}

	public function reject_content_brief() {
		$this->require_workflow_manager( 'reject a controlled content brief' );
		$id = absint( $_POST['brief_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_reject_content_brief_' . $id );
		$result = $this->content_workbench->reject_brief( $id, sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ), get_current_user_id() );
		$this->content_workbench_redirect( $result );
	}

	public function create_content_scaffold() {
		$this->require_workflow_manager( 'create an unpublished content scaffold' );
		$id = absint( $_POST['brief_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_create_content_scaffold_' . $id );
		$result = $this->content_workbench->create_scaffold( $id, sanitize_text_field( wp_unslash( $_POST['evidence_hash'] ?? '' ) ), get_current_user_id() );
		$this->content_workbench_redirect( $result );
	}

	public function evaluate_content_draft() {
		$this->require_workflow_manager( 'evaluate a controlled content draft' );
		$id = absint( $_POST['brief_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_evaluate_content_draft_' . $id );
		$result = $this->content_workbench->evaluate_draft( $id, get_current_user_id() );
		$this->content_workbench_redirect( $result );
	}

	public function mark_content_ready() {
		$this->require_workflow_manager( 'mark a controlled draft ready for review' );
		$id = absint( $_POST['brief_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_mark_content_ready_' . $id );
		$result = $this->content_workbench->mark_ready( $id, get_current_user_id() );
		$this->content_workbench_redirect( $result );
	}


	public function editorial_action() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to participate in editorial reviews.', 'ikon-seo' ) );
		}
		$command = sanitize_key( wp_unslash( $_POST['command'] ?? '' ) );
		$review_id = absint( $_POST['review_id'] ?? 0 );
		$brief_id = absint( $_POST['brief_id'] ?? 0 );
		$nonce_id = 'start_review' === $command ? $brief_id : $review_id;
		if ( in_array( $command, array( 'start_review', 'assign', 'block', 'unblock' ), true ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Only an administrator can perform this editorial action.', 'ikon-seo' ) );
		}
		check_admin_referer( 'ikon_seo_editorial_' . $command . '_' . $nonce_id );
		$assignment = array(
			'writer_id' => absint( $_POST['writer_id'] ?? 0 ),
			'reviewer_id' => absint( $_POST['reviewer_id'] ?? 0 ),
			'due_at' => sanitize_text_field( wp_unslash( $_POST['due_at'] ?? '' ) ),
			'review_due_at' => sanitize_text_field( wp_unslash( $_POST['review_due_at'] ?? '' ) ),
		);
		$notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
		switch ( $command ) {
			case 'start_review': $result = $this->editorial_review->start_review( $brief_id, $assignment, get_current_user_id() ); break;
			case 'assign': $result = $this->editorial_review->assign( $review_id, $assignment, get_current_user_id() ); break;
			case 'request_review': $result = $this->editorial_review->request_review( $review_id, $notes, get_current_user_id() ); break;
			case 'add_comment': $result = $this->editorial_review->add_comment( $review_id, array( 'type' => sanitize_key( wp_unslash( $_POST['comment_type'] ?? 'general' ) ), 'anchor_text' => sanitize_text_field( wp_unslash( $_POST['anchor_text'] ?? '' ) ), 'text' => sanitize_textarea_field( wp_unslash( $_POST['comment_text'] ?? '' ) ) ), get_current_user_id() ); break;
			case 'resolve_comment': $result = $this->editorial_review->resolve_comment( absint( $_POST['comment_id'] ?? 0 ), sanitize_key( wp_unslash( $_POST['resolution'] ?? 'resolved' ) ), $notes, get_current_user_id() ); break;
			case 'update_check': $result = $this->editorial_review->update_check( absint( $_POST['check_id'] ?? 0 ), sanitize_key( wp_unslash( $_POST['check_status'] ?? 'pending' ) ), $notes, get_current_user_id() ); break;
			case 'request_changes': $result = $this->editorial_review->request_changes( $review_id, $notes, get_current_user_id() ); break;
			case 'submit_revision': $result = $this->editorial_review->submit_revision( $review_id, $notes, get_current_user_id() ); break;
			case 'approve_round': $result = $this->editorial_review->approve_round( $review_id, $notes, get_current_user_id() ); break;
			case 'sign_off': $result = $this->editorial_review->sign_off( $review_id, $notes, get_current_user_id() ); break;
			case 'block': $result = $this->editorial_review->block( $review_id, $notes, get_current_user_id() ); break;
			case 'unblock': $result = $this->editorial_review->unblock( $review_id, $notes, get_current_user_id() ); break;
			default: $result = new WP_Error( 'ikon_seo_editorial_command', __( 'Unknown editorial action.', 'ikon-seo' ) );
		}
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'editorial-review', $result->get_error_message() );
		}
		$redirect_url = current_user_can( 'manage_options' ) ? admin_url( 'admin.php?page=ikon-seo&tab=editorial-review&editorial-review-updated=1' ) : admin_url( 'edit.php?page=ikon-seo-editorial&editorial-review-updated=1' );
		wp_safe_redirect( $redirect_url );
		exit;
	}


	public function publishing_action() {
		$this->require_workflow_manager( 'manage controlled publishing readiness' );
		$command = sanitize_key( wp_unslash( $_POST['command'] ?? '' ) );
		$release_id = absint( $_POST['release_id'] ?? 0 );
		$review_id = absint( $_POST['review_id'] ?? 0 );
		$nonce_id = 'create_release' === $command ? $review_id : $release_id;
		check_admin_referer( 'ikon_seo_publishing_' . $command . '_' . $nonce_id );
		$notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
		switch ( $command ) {
			case 'create_release':
				$result = $this->publishing_readiness->create_release( $review_id, array( 'slug' => sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) ), 'target_url' => esc_url_raw( wp_unslash( $_POST['target_url'] ?? '' ) ) ), get_current_user_id() );
				break;
			case 'run_preflight':
				$result = $this->publishing_readiness->run_preflight( $release_id, get_current_user_id() );
				break;
			case 'mark_ready':
				$result = $this->publishing_readiness->mark_ready( $release_id, $notes, get_current_user_id() );
				break;
			case 'record_manual_publication':
				$result = $this->publishing_readiness->record_manual_publication( $release_id, absint( $_POST['live_post_id'] ?? 0 ), esc_url_raw( wp_unslash( $_POST['live_url'] ?? '' ) ), get_current_user_id() );
				break;
			case 'verify_launch':
				$result = $this->publishing_readiness->verify_launch( $release_id, get_current_user_id(), true );
				break;
			case 'complete_monitoring':
				$result = $this->publishing_readiness->complete_monitoring( $release_id, $notes, get_current_user_id() );
				break;
			case 'block':
				$result = $this->publishing_readiness->block( $release_id, $notes, get_current_user_id() );
				break;
			case 'unblock':
				$result = $this->publishing_readiness->unblock( $release_id, $notes, get_current_user_id() );
				break;
			default:
				$result = new WP_Error( 'ikon_seo_publishing_command', __( 'Unknown publishing readiness action.', 'ikon-seo' ) );
		}
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'publishing-readiness', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=publishing-readiness&publishing-readiness-updated=1' ) );
		exit;
	}


	public function agency_service_levels_action() {
		if ( ! current_user_can( 'manage_options' ) || ! Ikon_SEO_Agency::can_manage() ) {
			wp_die( esc_html__( 'Agency access is required to manage service levels and client reports.', 'ikon-seo' ) );
		}
		check_admin_referer( 'ikon_seo_agency_service_levels_action' );
		$command = sanitize_key( wp_unslash( $_POST['command'] ?? 'read' ) );
		$payload = array(
			'command' => $command,
			'plan_id' => absint( $_POST['plan_id'] ?? 0 ),
			'site_id' => absint( $_POST['site_id'] ?? 0 ),
			'assignment_id' => absint( $_POST['assignment_id'] ?? 0 ),
			'work_item_id' => absint( $_POST['work_item_id'] ?? 0 ),
			'report_id' => absint( $_POST['report_id'] ?? 0 ),
			'status' => sanitize_key( wp_unslash( $_POST['status'] ?? '' ) ),
			'method' => sanitize_key( wp_unslash( $_POST['method'] ?? '' ) ),
			'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
		);
		if ( 'create_plan' === $command ) {
			$payload['plan'] = array(
				'name' => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'plan_key' => sanitize_key( wp_unslash( $_POST['plan_key'] ?? '' ) ),
				'currency' => sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) ),
				'monthly_fee' => (float) ( $_POST['monthly_fee'] ?? 0 ),
				'monthly_capacity_units' => absint( $_POST['monthly_capacity_units'] ?? 20 ),
				'max_concurrent_items' => absint( $_POST['max_concurrent_items'] ?? 5 ),
				'response_target_hours' => absint( $_POST['response_target_hours'] ?? 48 ),
				'report_cadence' => sanitize_key( wp_unslash( $_POST['report_cadence'] ?? 'monthly' ) ),
				'review_cadence' => sanitize_key( wp_unslash( $_POST['review_cadence'] ?? 'monthly' ) ),
				'included_deliverables' => sanitize_textarea_field( wp_unslash( $_POST['included_deliverables'] ?? '' ) ),
				'excluded_services' => sanitize_textarea_field( wp_unslash( $_POST['excluded_services'] ?? '' ) ),
				'report_evidence' => sanitize_textarea_field( wp_unslash( $_POST['report_evidence'] ?? '' ) ),
				'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
			);
		} elseif ( 'assign_plan' === $command ) {
			$payload['assignment'] = array(
				'start_date' => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ),
				'renewal_date' => sanitize_text_field( wp_unslash( $_POST['renewal_date'] ?? '' ) ),
				'capacity_override_units' => absint( $_POST['capacity_override_units'] ?? 0 ),
				'client_reporting_enabled' => ! empty( $_POST['client_reporting_enabled'] ),
				'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
			);
		} elseif ( 'set_capacity' === $command ) {
			$payload['capacity'] = array(
				'user_id' => absint( $_POST['user_id'] ?? 0 ),
				'period_start' => sanitize_text_field( wp_unslash( $_POST['period_start'] ?? '' ) ),
				'period_end' => sanitize_text_field( wp_unslash( $_POST['period_end'] ?? '' ) ),
				'capacity_units' => absint( $_POST['capacity_units'] ?? 0 ),
				'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
			);
		} elseif ( 'create_work_item' === $command || 'update_work_item' === $command ) {
			$payload['work_item'] = array(
				'assignment_id' => absint( $_POST['assignment_id'] ?? 0 ),
				'title' => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
				'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
				'owner_id' => absint( $_POST['owner_id'] ?? 0 ),
				'category' => sanitize_key( wp_unslash( $_POST['category'] ?? 'seo_operations' ) ),
				'priority' => sanitize_key( wp_unslash( $_POST['priority'] ?? 'normal' ) ),
				'units' => absint( $_POST['units'] ?? 1 ),
				'due_at' => sanitize_text_field( wp_unslash( $_POST['due_at'] ?? '' ) ),
				'status' => sanitize_key( wp_unslash( $_POST['status'] ?? 'planned' ) ),
			);
		} elseif ( 'generate_report' === $command ) {
			$payload['report'] = array(
				'period_start' => sanitize_text_field( wp_unslash( $_POST['period_start'] ?? '' ) ),
				'period_end' => sanitize_text_field( wp_unslash( $_POST['period_end'] ?? '' ) ),
				'client_summary' => sanitize_textarea_field( wp_unslash( $_POST['client_summary'] ?? '' ) ),
				'next_actions' => sanitize_textarea_field( wp_unslash( $_POST['next_actions'] ?? '' ) ),
			);
		}
		$result = $this->agency_service_levels->sync( $payload, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'agency-service-levels', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-service-levels&agency-service-levels-updated=1' ) );
		exit;
	}

	public function download_client_service_report() {
		if ( ! current_user_can( 'manage_options' ) || ! Ikon_SEO_Agency::can_manage() ) {
			wp_die( esc_html__( 'Agency access is required to preview client reports.', 'ikon-seo' ) );
		}
		$report_id = absint( $_GET['report_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_download_client_service_report_' . $report_id );
		$html = $this->agency_service_levels->render_report_html( $report_id );
		if ( is_wp_error( $html ) ) {
			wp_die( esc_html( $html->get_error_message() ) );
		}
		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: inline; filename="ikon-seo-client-report-' . $report_id . '.html"' );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Full escaped standalone report document.
		exit;
	}

	public function portfolio_governance_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage portfolio governance.', 'ikon-seo' ) );
		}
		check_admin_referer( 'ikon_seo_portfolio_governance_action' );
		$command = sanitize_key( wp_unslash( $_POST['command'] ?? 'read' ) );
		$central_commands = array( 'create_policy','approve_policy','retire_policy','save_site_key','assign_policy','sync_assignment' );
		if ( in_array( $command, $central_commands, true ) && ! Ikon_SEO_Agency::can_manage() ) {
			wp_die( esc_html__( 'Agency access is required for central governance actions.', 'ikon-seo' ) );
		}
		if ( 'generate_agent_key' === $command ) {
			$result = $this->portfolio_governance->generate_agent_key( get_current_user_id() );
		} elseif ( 'revoke_agent_key' === $command ) {
			$result = $this->portfolio_governance->revoke_agent_key( get_current_user_id() );
		} else {
			$payload = array(
				'command' => $command,
				'policy_id' => absint( $_POST['policy_id'] ?? 0 ),
				'site_id' => absint( $_POST['site_id'] ?? 0 ),
				'assignment_id' => absint( $_POST['assignment_id'] ?? 0 ),
				'proposal_id' => absint( $_POST['proposal_id'] ?? 0 ),
				'governance_key' => sanitize_text_field( wp_unslash( $_POST['governance_key'] ?? '' ) ),
				'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
			);
			if ( 'create_policy' === $command ) {
				$payload['policy'] = array(
					'name' => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
					'policy_key' => sanitize_key( wp_unslash( $_POST['policy_key'] ?? '' ) ),
					'minimum_strategy_readiness' => absint( $_POST['minimum_strategy_readiness'] ?? 70 ),
					'max_safe_batch' => absint( $_POST['max_safe_batch'] ?? 3 ),
					'data_retention_days' => absint( $_POST['data_retention_days'] ?? 365 ),
					'require_fact_review' => ! empty( $_POST['require_fact_review'] ),
					'require_guided_launch' => true,
					'require_brief_approval' => ! empty( $_POST['require_brief_approval'] ),
					'require_editorial_review' => ! empty( $_POST['require_editorial_review'] ),
					'require_publishing_preflight' => ! empty( $_POST['require_publishing_preflight'] ),
					'require_impact_study' => ! empty( $_POST['require_impact_study'] ),
					'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
				);
			}
			$result = $this->portfolio_governance->sync( $payload, get_current_user_id() );
		}
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'agency-governance', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-governance&portfolio-governance-updated=1' ) );
		exit;
	}

	public function pattern_library_action() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You are not allowed to manage the Pattern Library.', 'ikon-seo' ) ); }
		$command = sanitize_key( wp_unslash( $_POST['command'] ?? 'read' ) );
		$pattern_id = absint( $_POST['pattern_id'] ?? 0 );
		$nonce_action = in_array( $command, array( 'validate','limit','reject','retire','restore' ), true ) ? 'ikon_seo_pattern_library_decision_' . $pattern_id : 'ikon_seo_pattern_library_' . $command . '_0';
		check_admin_referer( $nonce_action );
		$payload = array( 'command' => $command, 'pattern_id' => $pattern_id, 'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ) );
		if ( 'import_evidence' === $command ) {
			$decoded = json_decode( wp_unslash( $_POST['records_json'] ?? '[]' ), true );
			if ( ! is_array( $decoded ) ) { $this->redirect_error( 'pattern-library', __( 'The imported evidence must be a valid JSON array.', 'ikon-seo' ) ); }
			$payload['records'] = $decoded;
		}
		$result = $this->pattern_library->sync( $payload, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'pattern-library', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=pattern-library&pattern-library-updated=1' ) );
		exit;
	}

	public function search_impact_action() {
		$this->require_workflow_manager( 'manage search impact measurement' );
		$command = sanitize_key( wp_unslash( $_POST['command'] ?? '' ) );
		$study_id = absint( $_POST['study_id'] ?? 0 );
		$release_id = absint( $_POST['release_id'] ?? 0 );
		$nonce_id = 'create_study' === $command ? $release_id : $study_id;
		check_admin_referer( 'ikon_seo_search_impact_' . $command . '_' . $nonce_id );
		$notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
		switch ( $command ) {
			case 'create_study':
				$result = $this->search_impact->create_study( $release_id, array( 'primary_metric' => sanitize_key( wp_unslash( $_POST['primary_metric'] ?? 'clicks' ) ), 'comparison_url' => esc_url_raw( wp_unslash( $_POST['comparison_url'] ?? '' ) ), 'evaluation_days' => absint( $_POST['evaluation_days'] ?? 28 ) ), get_current_user_id() );
				break;
			case 'capture_baseline': $result = $this->search_impact->capture_baseline( $study_id, get_current_user_id(), true ); break;
			case 'capture_checkpoint': $result = $this->search_impact->capture_checkpoint( $study_id, absint( $_POST['checkpoint_days'] ?? 0 ), get_current_user_id(), true ); break;
			case 'add_confounder': $result = $this->search_impact->add_confounder( $study_id, array( 'type' => sanitize_key( wp_unslash( $_POST['confounder_type'] ?? 'other' ) ), 'notes' => $notes, 'occurred_at' => current_time( 'mysql', true ) ), get_current_user_id() ); break;
			case 'assess': $result = $this->search_impact->assess( $study_id, $notes, get_current_user_id() ); break;
			case 'acknowledge': $result = $this->search_impact->acknowledge( $study_id, sanitize_key( wp_unslash( $_POST['decision'] ?? '' ) ), $notes, get_current_user_id() ); break;
			case 'block': $result = $this->search_impact->block( $study_id, $notes, get_current_user_id() ); break;
			case 'unblock': $result = $this->search_impact->unblock( $study_id, $notes, get_current_user_id() ); break;
			default: $result = new WP_Error( 'ikon_seo_search_impact_command', __( 'Unknown Search Impact action.', 'ikon-seo' ) );
		}
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'search-impact', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=search-impact&search-impact-updated=1' ) );
		exit;
	}

	private function content_workbench_redirect( $result ) {
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'content-workbench', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=content-workbench&content-workbench-updated=1' ) );
		exit;
	}

	public function save_strategy() {
		$this->guard_agency( 'ikon_seo_save_strategy' );
		$result = $this->strategy->save( $_POST, get_current_user_id(), 'admin' );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'strategy', $result->get_error_message() );
		}
		$this->diagnostics->clear_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=strategy&strategy-updated=1' ) );
		exit;
	}

	public function save_profile() {
		$this->guard_agency( 'ikon_seo_save_profile' );

		$current         = Ikon_SEO_Plugin::settings();
		$old_fingerprint = $this->profile->fingerprint( $current );
		$clean           = $this->profile->sanitize( $_POST, $current );
		if ( is_wp_error( $clean ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', $clean->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=profile' ) ) );
			exit;
		}

		$clean['profile_configured'] = 1;
		$clean['profile_home_url']   = home_url( '/' );
		$new_fingerprint             = $this->profile->fingerprint( $clean );
		if ( ! hash_equals( $old_fingerprint, $new_fingerprint ) ) {
				$clean['token_hash']     = '';
				$clean['connection_owner_user_id'] = 0;
				$clean['token_hint']     = '';
				$clean['connection_verified_at'] = '';
				$clean['connection_last_seen_at'] = '';
				$clean['remote_actions'] = 0;
				$clean['gbp_refresh_token'] = '';
				$clean['gbp_account']       = '';
				$clean['gbp_last_error']    = '';
				$this->local->rebind_profile( $old_fingerprint, $new_fingerprint );
		}

		update_option( Ikon_SEO_Plugin::OPTION_KEY, $clean, false );
		$this->inventory->clear_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=profile&profile-updated=1' ) );
		exit;
	}

	public function export_profile() {
		$this->guard_agency( 'ikon_seo_export_profile' );
		$document = $this->profile->export();
		$filename = 'ikon-seo-profile-' . sanitize_title( $document['profile']['site_name'] ?? 'website' ) . '.json';
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	public function import_profile() {
		$this->guard_agency( 'ikon_seo_import_profile' );
		$old_fingerprint = $this->profile->fingerprint();
		$file = $_FILES['profile_file'] ?? array();
		if ( empty( $file['tmp_name'] ) || ! empty( $file['error'] ) || absint( $file['size'] ?? 0 ) > 262144 ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', 'Choose a valid profile JSON file no larger than 256 KB.', admin_url( 'admin.php?page=ikon-seo&tab=profile' ) ) );
			exit;
		}
		$raw      = file_get_contents( $file['tmp_name'] );
		$document = json_decode( (string) $raw, true );
		$result   = is_array( $document ) ? $this->profile->import( $document ) : new WP_Error( 'ikon_seo_profile_json', 'The uploaded profile is not valid JSON.' );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=profile' ) ) );
			exit;
		}
		$this->local->rebind_profile( $old_fingerprint, $result['profile_id'] );
		$this->inventory->clear_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=profile&profile-imported=1' ) );
		exit;
	}

	public function preview_migration() {
		$this->guard_agency( 'ikon_seo_preview_migration' );
		$result = $this->migration->report(
			sanitize_text_field( wp_unslash( $_POST['old_url'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['new_url'] ?? '' ) )
		);
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=migration' ) ) );
			exit;
		}
		set_transient( 'ikon_seo_migration_report_' . get_current_user_id(), $result, 30 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=migration&migration-preview=1' ) );
		exit;
	}

	public function apply_migration() {
		$this->guard_agency( 'ikon_seo_apply_migration' );
		$result = $this->migration->apply(
			sanitize_text_field( wp_unslash( $_POST['old_url'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['new_url'] ?? '' ) )
		);
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=migration' ) ) );
			exit;
		}
		delete_transient( 'ikon_seo_migration_report_' . get_current_user_id() );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=migration&migration-applied=1' ) );
		exit;
	}

	public function start_pairing() {
		$this->guard_agency( 'ikon_seo_start_pairing' );

		if ( ! $this->profile->get()['configured'] ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', 'Complete the Website Profile before connecting Ikon SEO.', admin_url( 'admin.php?page=ikon-seo&tab=profile' ) ) );
			exit;
		}

		$this->connection->start_pairing( get_current_user_id() );
		$this->logger->log( 'pairing', 'success', 'A new one-time pairing code was created.' );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=connection&pairing-started=1' ) );
		exit;
	}

	public function test_connection() {
		$this->guard_agency( 'ikon_seo_test_connection' );

		$result = array( 'ok' => false, 'message' => __( 'The website API test failed.', 'ikon-seo' ) );
		$url    = rest_url( Ikon_SEO_REST::NAMESPACE . '/openapi' );
		$check  = wp_remote_get(
			$url,
			array(
				'timeout'     => 12,
				'redirection' => 2,
				'headers'     => array( 'Cache-Control' => 'no-cache' ),
			)
		);

		if ( is_wp_error( $check ) ) {
			$result['message'] = sprintf( __( 'The WordPress REST API could not be reached: %s', 'ikon-seo' ), $check->get_error_message() );
		} else {
			$code = wp_remote_retrieve_response_code( $check );
			$body = json_decode( wp_remote_retrieve_body( $check ), true );
			if ( 200 === $code && is_array( $body ) && ! empty( $body['openapi'] ) ) {
				$result = array(
					'ok'      => true,
					'message' => __( 'Website API is working. Local SEO tools are ready; a private workflow connection remains optional.', 'ikon-seo' ),
				);
			} else {
				$result['message'] = sprintf( __( 'The website API returned HTTP %d. A security or cache rule may be blocking WordPress REST requests.', 'ikon-seo' ), absint( $code ) );
			}
		}

		set_transient( 'ikon_seo_connection_test_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
		$this->logger->log( 'connection_test', $result['ok'] ? 'success' : 'failed', $result['message'] );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=connection&api-tested=1' ) );
		exit;
	}

	public function generate_token() {
		$this->guard_agency( 'ikon_seo_generate_token' );

		if ( ! $this->profile->get()['configured'] ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', 'Complete the Website Profile before generating a workflow key.', admin_url( 'admin.php?page=ikon-seo&tab=profile' ) ) );
			exit;
		}

		$token = $this->connection->generate_developer_key( get_current_user_id() );
		set_transient( 'ikon_seo_new_token_' . get_current_user_id(), $token, 5 * MINUTE_IN_SECONDS );
		$this->logger->log( 'developer_key', 'success', 'A developer connection key was generated.' );

		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=connection&key-created=1' ) );
		exit;
	}

	public function revoke_token() {
		$this->guard_agency( 'ikon_seo_revoke_token' );
		$this->connection->revoke( get_current_user_id() );
		$this->logger->log( 'disconnect', 'success', 'The Ikon SEO connection was revoked.' );

		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=connection&key-revoked=1' ) );
		exit;
	}

	public function merge_review() {
		$draft_id = absint( $_POST['draft_id'] ?? 0 );
		$this->guard( 'ikon_seo_merge_review_' . $draft_id );

		$result = $this->workflow->merge( $draft_id );
		$this->inventory->clear_cache();
		$args = is_wp_error( $result )
			? array( 'ikon-error' => $result->get_error_message() )
			: array( 'merged' => 1, 'source' => absint( $result['source_id'] ) );
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=ikon-seo&tab=reviews' ) ) );
		exit;
	}

	public function rollback_page() {
		$source_id = absint( $_POST['source_id'] ?? 0 );
		$this->guard( 'ikon_seo_rollback_page_' . $source_id );

		$result = $this->workflow->rollback( $source_id, sanitize_text_field( wp_unslash( $_POST['snapshot_id'] ?? '' ) ) );
		$this->inventory->clear_cache();
		$args = is_wp_error( $result )
			? array( 'ikon-error' => $result->get_error_message() )
			: array( 'rolled-back' => 1, 'source' => $source_id );
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=ikon-seo&tab=reviews' ) ) );
		exit;
	}

	public function refresh_inventory() {
		$this->guard( 'ikon_seo_refresh_inventory' );
		$this->inventory->scan( true );
		$this->rank_math->clear_cache();
		$this->redirect_audit->clear_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=inventory&refreshed=1' ) );
		exit;
	}


	public function refresh_rank_math() {
		$this->guard( 'ikon_seo_refresh_rank_math' );
		$this->rank_math->audit( true );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=seo-health&refreshed=1' ) );
		exit;
	}

	public function refresh_image_audit() {
		$this->guard( 'ikon_seo_refresh_image_audit' );
		$this->image_audit->scan( true );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=image-audit&refreshed=1' ) );
		exit;
	}

	public function refresh_redirects() {
		$this->guard( 'ikon_seo_refresh_redirects' );
		$this->redirect_audit->scan( true );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=redirects&refreshed=1' ) );
		exit;
	}

	public function add_history_note() {
		$this->guard( 'ikon_seo_add_history_note' );
		$result = $this->history->add(
			array(
				'category' => sanitize_key( $_POST['category'] ?? 'note' ),
				'status'   => sanitize_key( $_POST['status'] ?? 'open' ),
				'title'    => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
				'summary'  => sanitize_textarea_field( wp_unslash( $_POST['summary'] ?? '' ) ),
			),
			'wordpress',
			get_current_user_id()
		);
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=history' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=history&history-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function update_history_status() {
		$id = absint( $_POST['history_id'] ?? 0 );
		$this->guard( 'ikon_seo_update_history_status_' . $id );
		$result = $this->history->update_status( $id, sanitize_key( $_POST['status'] ?? '' ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=history' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=history&history-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function export_workspace_setup() {
		$this->guard_agency( 'ikon_seo_export_workspace_setup' );
		$profile = $this->profile->get();
		$strategy = $this->strategy->get();
		$payload = array(
			'format'       => 'ikon-seo-workspace-transfer',
			'version'      => IKON_SEO_VERSION,
			'generated_at' => current_time( 'mysql', true ),
			'site_name'    => sanitize_text_field( $profile['site_name'] ?? get_bloginfo( 'name' ) ),
			'site_url'     => home_url( '/' ),
			'profile_id'   => sanitize_text_field( $profile['profile_id'] ?? '' ),
			'strategy'     => array( 'mode' => $strategy['mode'], 'primary_goal' => $strategy['primary_goal'], 'readiness' => absint( $strategy['readiness']['score'] ?? 0 ) ),
			'schema_url'   => rest_url( Ikon_SEO_REST::NAMESPACE . '/openapi' ),
			'startup_sequence' => array(
				'Read the connected Website Profile.',
				'Read the Website Strategy and operating mode.',
				'Read the Site Inventory.',
				'Call syncIkonSEOProjectHistory with an empty object to resume project context.',
				'Read open Page Plans before recommending or creating new pages.',
			),
			'account_change_steps' => array(
				'Create a private workspace in the new account.',
				'Import the same schema URL.',
				'Generate a fresh workflow key in WordPress and save it as Bearer authentication.',
				'Test connection and project-history actions.',
				'Revoke the old workflow key after the new account is confirmed.',
			),
			'security' => 'No workflow key is included in this export.',
		);
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="ikon-seo-workspace-transfer-' . gmdate( 'Ymd-His' ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	public function save_agency_access() {
		$this->guard_agency( 'ikon_seo_save_agency_access' );
		Ikon_SEO_Agency::set_user_ids( (array) ( $_POST['agency_user_ids'] ?? array() ) );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency&updated=1' ) );
		exit;
	}

	public function gsc_save_credentials() {
		$this->guard_agency( 'ikon_seo_gsc_save_credentials' );
		$result = $this->search_console->save_credentials(
			wp_unslash( $_POST['gsc_client_id'] ?? '' ),
			wp_unslash( $_POST['gsc_client_secret'] ?? '' )
		);
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=search-console' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=search-console&gsc-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gsc_connect() {
		$this->guard_agency( 'ikon_seo_gsc_connect' );
		$url = $this->search_console->authorization_url( get_current_user_id() );
		if ( is_wp_error( $url ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', $url->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=search-console' ) ) );
			exit;
		}
		wp_redirect( esc_url_raw( $url ) );
		exit;
	}

	public function gsc_callback() {
		if ( ! current_user_can( 'manage_options' ) || ! Ikon_SEO_Agency::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to connect Search Console.', 'ikon-seo' ) );
		}
		if ( ! empty( $_GET['error'] ) ) {
			$message = sanitize_text_field( wp_unslash( $_GET['error_description'] ?? $_GET['error'] ) );
			wp_safe_redirect( add_query_arg( 'ikon-error', $message, admin_url( 'admin.php?page=ikon-seo&tab=search-console' ) ) );
			exit;
		}
		$result = $this->search_console->complete_authorization(
			sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) ),
			get_current_user_id()
		);
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=search-console' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=search-console&gsc-connected=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gsc_select_property() {
		$this->guard_agency( 'ikon_seo_gsc_select_property' );
		$result = $this->search_console->select_property( wp_unslash( $_POST['gsc_property'] ?? '' ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=search-console' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=search-console&gsc-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gsc_disconnect() {
		$this->guard_agency( 'ikon_seo_gsc_disconnect' );
		$this->search_console->disconnect();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=search-console&gsc-updated=1' ) );
		exit;
	}

	public function gsc_refresh() {
		$this->guard_agency( 'ikon_seo_gsc_refresh' );
		$result = $this->search_console->performance( 28, true );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=search-console' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=search-console&gsc-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function ga_save_credentials() {
		$this->guard_agency( 'ikon_seo_ga_save_credentials' );
		$result = $this->analytics->save_credentials(
			wp_unslash( $_POST['ga_client_id'] ?? '' ),
			wp_unslash( $_POST['ga_client_secret'] ?? '' ),
			! empty( $_POST['use_gsc_credentials'] )
		);
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=analytics' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=analytics&ga-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function ga_connect() {
		$this->guard_agency( 'ikon_seo_ga_connect' );
		$url = $this->analytics->authorization_url( get_current_user_id() );
		if ( is_wp_error( $url ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', $url->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=analytics' ) ) );
			exit;
		}
		wp_redirect( esc_url_raw( $url ) );
		exit;
	}

	public function ga_callback() {
		if ( ! current_user_can( 'manage_options' ) || ! Ikon_SEO_Agency::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to connect Google Analytics.', 'ikon-seo' ) );
		}
		if ( ! empty( $_GET['error'] ) ) {
			$message = sanitize_text_field( wp_unslash( $_GET['error_description'] ?? $_GET['error'] ) );
			wp_safe_redirect( add_query_arg( 'ikon-error', $message, admin_url( 'admin.php?page=ikon-seo&tab=analytics' ) ) );
			exit;
		}
		$result = $this->analytics->complete_authorization(
			sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) ),
			get_current_user_id()
		);
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=analytics' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=analytics&ga-connected=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function ga_select_property() {
		$this->guard_agency( 'ikon_seo_ga_select_property' );
		$result = $this->analytics->select_property( wp_unslash( $_POST['ga_property'] ?? '' ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=analytics' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=analytics&ga-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function ga_disconnect() {
		$this->guard_agency( 'ikon_seo_ga_disconnect' );
		$this->analytics->disconnect();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=analytics&ga-updated=1' ) );
		exit;
	}

	public function ga_refresh() {
		$this->guard_agency( 'ikon_seo_ga_refresh' );
		$result = $this->analytics->report( 28, true );
		$this->diagnostics->clear_cache();
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=analytics' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=analytics&ga-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function crawl_evidence() {
		$this->guard( 'ikon_seo_crawl_evidence' );
		$result = $this->crawler->crawl_batch(
			absint( $_POST['limit'] ?? Ikon_SEO_Plugin::settings()['crawler_batch_size'] ),
			! empty( $_POST['force'] )
		);
		$this->diagnostics->clear_cache();
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=diagnostics' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=diagnostics&evidence-crawled=1' );
		wp_safe_redirect( $url );
		exit;
	}


	public function refresh_search_intelligence() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to refresh Search Intelligence.', 'ikon-seo' ) );
		}
		check_admin_referer( 'ikon_seo_refresh_search_intelligence' );
		$settings = Ikon_SEO_Plugin::settings();
		$settings['search_intelligence_days']            = max( 7, min( 90, absint( $_POST['days'] ?? 28 ) ) );
		$settings['search_intelligence_max_rows']        = max( 1000, min( 200000, absint( $_POST['max_rows'] ?? 50000 ) ) );
		$settings['search_intelligence_min_impressions'] = max( 5, min( 1000, absint( $_POST['min_impressions'] ?? 20 ) ) );
		$settings['search_intelligence_decay_percent']   = max( 10, min( 90, absint( $_POST['decay_percent'] ?? 30 ) ) );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$result = $this->search_intelligence->refresh( $settings['search_intelligence_days'], $settings['search_intelligence_max_rows'] );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'search-intelligence', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=search-intelligence&search-intelligence-refreshed=1' ) );
		exit;
	}


	public function refresh_technical_intelligence() {
		$this->guard( 'ikon_seo_refresh_technical_intelligence' );
		$result = $this->technical->refresh_discovery();
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'technical-intelligence', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=technical-intelligence&technical-refreshed=1' ) ); exit;
	}

	public function check_technical_urls() {
		$this->guard( 'ikon_seo_check_technical_urls' );
		$result = $this->technical->check_urls( absint( $_POST['limit'] ?? 20 ) );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'technical-intelligence', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=technical-intelligence&technical-checked=1' ) ); exit;
	}

	public function save_pagespeed_key() {
		$this->guard_agency( 'ikon_seo_save_pagespeed_key' );
		$key = ! empty( $_POST['clear_key'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['pagespeed_api_key'] ?? '' ) );
		if ( '' === $key && empty( $_POST['clear_key'] ) ) { wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=technical-intelligence&pagespeed-updated=1' ) ); exit; }
		$result = $this->technical->save_api_key( $key );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'technical-intelligence', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=technical-intelligence&pagespeed-updated=1' ) ); exit;
	}

	public function refresh_pagespeed() {
		$this->guard( 'ikon_seo_refresh_pagespeed' );
		$result = $this->technical->run_pagespeed( absint( $_POST['limit'] ?? 3 ), sanitize_key( $_POST['strategy'] ?? 'mobile' ) );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'technical-intelligence', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=technical-intelligence&pagespeed-refreshed=1' ) ); exit;
	}


	public function save_competitor_research() {
		$this->guard( 'ikon_seo_save_competitor_research' );
		$record = array(
			'query'               => sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) ),
			'url'                 => esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ),
			'intent'              => sanitize_key( $_POST['intent'] ?? 'mixed' ),
			'result_type'         => sanitize_key( $_POST['result_type'] ?? 'mixed_results' ),
			'title'               => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
			'h1'                  => sanitize_text_field( wp_unslash( $_POST['h1'] ?? '' ) ),
			'headings'            => sanitize_textarea_field( wp_unslash( $_POST['headings'] ?? '' ) ),
			'topics'              => sanitize_textarea_field( wp_unslash( $_POST['topics'] ?? '' ) ),
			'entities'            => sanitize_textarea_field( wp_unslash( $_POST['entities'] ?? '' ) ),
			'trust_elements'      => sanitize_textarea_field( wp_unslash( $_POST['trust_elements'] ?? '' ) ),
			'conversion_elements' => sanitize_textarea_field( wp_unslash( $_POST['conversion_elements'] ?? '' ) ),
			'search_features'     => sanitize_text_field( wp_unslash( $_POST['search_features'] ?? '' ) ),
			'evidence_notes'      => sanitize_textarea_field( wp_unslash( $_POST['evidence_notes'] ?? '' ) ),
			'observed_at'         => sanitize_text_field( $_POST['observed_at'] ?? gmdate( 'Y-m-d' ) ),
			'source'              => 'manual',
		);
		$result = $this->competitor_content->save_research( $record, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'content-intelligence', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=content-intelligence&content-intelligence-updated=1' ) );
		exit;
	}

	public function archive_competitor_research() {
		$this->guard( 'ikon_seo_archive_competitor_research' );
		if ( ! $this->competitor_content->archive_research( absint( $_POST['id'] ?? 0 ) ) ) {
			$this->redirect_error( 'content-intelligence', __( 'The competitor observation could not be archived.', 'ikon-seo' ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=content-intelligence&content-intelligence-updated=1' ) );
		exit;
	}

	public function analyse_content_page() {
		$this->guard( 'ikon_seo_analyse_content_page' );
		$result = $this->competitor_content->analyse_page(
			absint( $_POST['post_id'] ?? 0 ),
			sanitize_text_field( wp_unslash( $_POST['target_query'] ?? '' ) ),
			sanitize_key( $_POST['intent'] ?? '' ),
			true,
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'content-intelligence', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=content-intelligence&content-intelligence-updated=1' ) );
		exit;
	}

	public function refresh_diagnostics() {
		$this->guard( 'ikon_seo_refresh_diagnostics' );
		$result = $this->diagnostics->site_report( true, true );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=diagnostics' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=diagnostics&diagnostics-refreshed=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function queue_import() {
		$this->guard( 'ikon_seo_queue_import' );
		$file = $_FILES['queue_file'] ?? array();
		$name = sanitize_file_name( $file['name'] ?? '' );
		if ( empty( $file['tmp_name'] ) || ! empty( $file['error'] ) || absint( $file['size'] ?? 0 ) > 2 * MB_IN_BYTES || 'csv' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', 'Choose a valid CSV file no larger than 2 MB.', admin_url( 'admin.php?page=ikon-seo&tab=queue' ) ) );
			exit;
		}

		$result = $this->queue->import_csv( $file['tmp_name'], $name );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=queue' ) ) );
			exit;
		}
		$this->logger->log( 'queue_import', 'success', sprintf( '%d page plans imported; %d skipped.', $result['inserted'], $result['skipped'] ) );
		wp_safe_redirect(
			add_query_arg(
				array(
					'queue-imported' => 1,
					'inserted'       => absint( $result['inserted'] ),
					'skipped'        => absint( $result['skipped'] ),
				),
				admin_url( 'admin.php?page=ikon-seo&tab=queue' )
			)
		);
		exit;
	}

	public function queue_status() {
		$id = absint( $_POST['queue_id'] ?? 0 );
		$this->guard( 'ikon_seo_queue_status_' . $id );
		$result = $this->queue->admin_status( $id, wp_unslash( $_POST['queue_status'] ?? '' ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=queue' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=queue' );
		wp_safe_redirect( $url );
		exit;
	}

	public function monitor_run() {
		$this->guard( 'ikon_seo_monitor_run' );
		$this->monitor->run_daily( true );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=monitor&monitor-run=1' ) );
		exit;
	}

	public function monitor_schedule() {
		$this->guard( 'ikon_seo_monitor_schedule' );
		$result = $this->monitor->mark_reviewed( absint( $_POST['post_id'] ?? 0 ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=monitor' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=monitor' );
		wp_safe_redirect( $url );
		exit;
	}

	public function monitor_reviewed() {
		$post_id = absint( $_POST['post_id'] ?? 0 );
		$this->guard( 'ikon_seo_monitor_reviewed_' . $post_id );
		$result = $this->monitor->mark_reviewed( $post_id );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=monitor' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=monitor' );
		wp_safe_redirect( $url );
		exit;
	}

	public function local_save_location() {
		$this->guard( 'ikon_seo_local_save_location' );
		$result = $this->local->save_location( wp_unslash( $_POST ), absint( $_POST['location_id'] ?? 0 ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=local-seo&local-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function local_delete_location() {
		$id = absint( $_POST['location_id'] ?? 0 );
		$this->guard( 'ikon_seo_local_delete_location_' . $id );
		$result = $this->local->delete_location( $id );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=local-seo&local-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function local_save_citation() {
		$this->guard( 'ikon_seo_local_save_citation' );
		$result = $this->local->save_citation( wp_unslash( $_POST ), absint( $_POST['citation_id'] ?? 0 ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=local-seo&local-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function local_delete_citation() {
		$id = absint( $_POST['citation_id'] ?? 0 );
		$this->guard( 'ikon_seo_local_delete_citation_' . $id );
		$this->local->delete_citation( $id );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=local-seo&local-updated=1' ) );
		exit;
	}

	public function local_import_citations() {
		$this->guard( 'ikon_seo_local_import_citations' );
		$file = $_FILES['csv_file'] ?? array();
		if ( ! $this->valid_local_csv_upload( $file ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', 'Choose a valid CSV file no larger than 2 MB.', admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) ) );
			exit;
		}
		$result = $this->local->import_citations_csv( $file['tmp_name'] );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) )
			: add_query_arg( array( 'local-updated' => 1, 'inserted' => absint( $result['inserted'] ), 'skipped' => absint( $result['skipped'] ) ), admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) );
		wp_safe_redirect( $url );
		exit;
	}

	public function local_export_citations() {
		$this->guard( 'ikon_seo_local_export_citations' );
		$rows = $this->local->citations( 1000 );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="ikon-seo-citations-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'directory_name', 'listing_url', 'location_id', 'business_name', 'address', 'phone', 'status', 'login_owner', 'last_checked', 'next_review', 'duplicate_warning', 'correction_required', 'notes' ) );
		foreach ( $rows as $row ) {
			fputcsv( $output, array(
				$row['directory_name'], $row['listing_url'], $row['location_id'], $row['business_name'], $row['address'], $row['phone'], $row['status'],
				$row['login_owner'], $row['last_checked'], $row['next_review'], $row['duplicate_warning'], $row['correction_required'], $row['notes'],
			) );
		}
		fclose( $output );
		exit;
	}

	public function local_save_rank() {
		$this->guard( 'ikon_seo_local_save_rank' );
		$result = $this->local->save_rank( wp_unslash( $_POST ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=local-seo&local-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function local_import_ranks() {
		$this->guard( 'ikon_seo_local_import_ranks' );
		$file = $_FILES['csv_file'] ?? array();
		if ( ! $this->valid_local_csv_upload( $file ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', 'Choose a valid CSV file no larger than 2 MB.', admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) ) );
			exit;
		}
		$result = $this->local->import_ranks_csv( $file['tmp_name'] );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) )
			: add_query_arg( array( 'local-updated' => 1, 'inserted' => absint( $result['inserted'] ), 'skipped' => absint( $result['skipped'] ) ), admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) );
		wp_safe_redirect( $url );
		exit;
	}

	public function local_generate_utm() {
		$this->guard( 'ikon_seo_local_generate_utm' );
		$result = $this->local->utm_url(
			array(
				'url'      => wp_unslash( $_POST['url'] ?? '' ),
				'source'   => 'google',
				'medium'   => 'organic',
				'campaign' => wp_unslash( $_POST['campaign'] ?? '' ),
				'content'  => wp_unslash( $_POST['content'] ?? '' ),
			)
		);
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=local-seo' ) ) );
			exit;
		}
		set_transient( 'ikon_seo_local_utm_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=local-seo#local-utm' ) );
		exit;
	}

	public function gbp_set_availability() {
		$this->guard( 'ikon_seo_gbp_set_availability' );
		$availability = sanitize_key( wp_unslash( $_POST['gbp_availability'] ?? 'unknown' ) );
		if ( ! in_array( $availability, array( 'available', 'not_available' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', __( 'Choose whether you currently have a Google Business Profile.', 'ikon-seo' ), admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) ) );
			exit;
		}
		if ( 'not_available' === $availability ) {
			$this->gbp->disconnect();
		}
		$settings                     = Ikon_SEO_Plugin::settings();
		$settings['gbp_availability'] = $availability;
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->logger->log( 'gbp_availability', 'success', 'Google Business Profile preference updated: ' . $availability . '.' );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-availability-updated=1' ) );
		exit;
	}

	public function gbp_save_credentials() {
		$this->guard( 'ikon_seo_gbp_save_credentials' );
		$result = $this->gbp->save_credentials(
			wp_unslash( $_POST['gbp_client_id'] ?? '' ),
			wp_unslash( $_POST['gbp_client_secret'] ?? '' )
		);
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gbp_connect() {
		$this->guard( 'ikon_seo_gbp_connect' );
		$url = $this->gbp->authorization_url( get_current_user_id() );
		if ( is_wp_error( $url ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', $url->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) ) );
			exit;
		}
		wp_redirect( esc_url_raw( $url ) );
		exit;
	}

	public function gbp_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to connect Google Business Profile.', 'ikon-seo' ) );
		}
		if ( ! empty( $_GET['error'] ) ) {
			$message = sanitize_text_field( wp_unslash( $_GET['error_description'] ?? $_GET['error'] ) );
			wp_safe_redirect( add_query_arg( 'ikon-error', $message, admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) ) );
			exit;
		}
		$result = $this->gbp->complete_authorization(
			sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) ),
			get_current_user_id()
		);
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-connected=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gbp_select_account() {
		$this->guard( 'ikon_seo_gbp_select_account' );
		$result = $this->gbp->select_account( wp_unslash( $_POST['gbp_account'] ?? '' ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gbp_link_location() {
		$id = absint( $_POST['local_id'] ?? 0 );
		$this->guard( 'ikon_seo_gbp_link_location_' . $id );
		$result = $this->gbp->link_location( $id, wp_unslash( $_POST['remote_name'] ?? '' ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gbp_unlink_location() {
		$id = absint( $_POST['local_id'] ?? 0 );
		$this->guard( 'ikon_seo_gbp_unlink_location_' . $id );
		$result = $this->gbp->unlink_location( $id );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gbp_disconnect() {
		$this->guard( 'ikon_seo_gbp_disconnect' );
		$this->gbp->disconnect();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-updated=1' ) );
		exit;
	}

	public function gbp_refresh() {
		$this->guard( 'ikon_seo_gbp_refresh' );
		$this->gbp->clear_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-updated=1' ) );
		exit;
	}

	public function gbp_stage_draft() {
		$this->guard( 'ikon_seo_gbp_stage_draft' );
		$result = $this->gbp->stage_draft( wp_unslash( $_POST ), get_current_user_id() );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-draft-staged=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gbp_approve_draft() {
		$id = absint( $_POST['draft_id'] ?? 0 );
		$this->guard( 'ikon_seo_gbp_approve_draft_' . $id );
		$result = $this->gbp->approve_draft( $id, get_current_user_id() );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-sent=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gbp_reject_draft() {
		$id = absint( $_POST['draft_id'] ?? 0 );
		$this->guard( 'ikon_seo_gbp_reject_draft_' . $id );
		$result = $this->gbp->reject_draft( $id, get_current_user_id() );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=business-profile' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=business-profile&gbp-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function save_agency_command_settings() {
		$this->guard_agency( 'ikon_seo_save_agency_command_settings' );
		$current = Ikon_SEO_Plugin::settings();
		$current['agency_command_enabled']        = isset( $_POST['enabled'] ) ? 1 : 0;
		$current['agency_command_refresh_hours']  = max( 1, min( 168, absint( $_POST['refresh_hours'] ?? 6 ) ) );
		$current['agency_command_batch_size']     = max( 1, min( 50, absint( $_POST['batch_size'] ?? 10 ) ) );
		$current['agency_command_default_budget'] = max( 0, (float) ( $_POST['default_budget'] ?? 0 ) );
		$current['agency_command_currency']       = strtoupper( substr( sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) ), 0, 3 ) );
		$current['agency_command_brand_name']     = sanitize_text_field( wp_unslash( $_POST['brand_name'] ?? 'Ikon SEO' ) );
		$current['agency_command_logo_url']       = esc_url_raw( wp_unslash( $_POST['logo_url'] ?? '' ) );
		$current['agency_command_client_footer']  = sanitize_textarea_field( wp_unslash( $_POST['client_footer'] ?? '' ) );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $current, false );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}

	public function generate_agency_agent_key() {
		$this->guard_agency( 'ikon_seo_generate_agency_agent_key' );
		$result = $this->agency_command->generate_agent_key( get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'agency-command-centre', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}

	public function revoke_agency_agent_key() {
		$this->guard_agency( 'ikon_seo_revoke_agency_agent_key' );
		$this->agency_command->revoke_agent_key();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}

	public function add_managed_site() {
		$this->guard_agency( 'ikon_seo_add_managed_site' );
		$result = $this->agency_command->add_site(
			array(
				'client_name'    => sanitize_text_field( wp_unslash( $_POST['client_name'] ?? '' ) ),
				'group_name'     => sanitize_text_field( wp_unslash( $_POST['group_name'] ?? '' ) ),
				'site_url'       => esc_url_raw( wp_unslash( $_POST['site_url'] ?? '' ) ),
				'site_key'       => trim( (string) wp_unslash( $_POST['site_key'] ?? '' ) ),
				'monthly_budget' => max( 0, (float) ( $_POST['monthly_budget'] ?? 0 ) ),
				'currency'       => sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'agency-command-centre', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}

	public function update_managed_site() {
		$this->guard_agency( 'ikon_seo_update_managed_site' );
		$site_id = absint( $_POST['site_id'] ?? 0 );
		$result  = $this->agency_command->update_site(
			$site_id,
			array(
				'client_name'    => sanitize_text_field( wp_unslash( $_POST['client_name'] ?? '' ) ),
				'group_name'     => sanitize_text_field( wp_unslash( $_POST['group_name'] ?? '' ) ),
				'monthly_budget' => isset( $_POST['monthly_budget'] ) ? wp_unslash( $_POST['monthly_budget'] ) : '',
				'currency'       => sanitize_text_field( wp_unslash( $_POST['currency'] ?? '' ) ),
				'site_key'       => trim( (string) wp_unslash( $_POST['site_key'] ?? '' ) ),
				'enabled'        => isset( $_POST['enabled'] ) ? 1 : 0,
			)
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'agency-command-centre', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}

	public function delete_managed_site() {
		$site_id = absint( $_POST['site_id'] ?? 0 );
		$this->guard_agency( 'ikon_seo_delete_managed_site_' . $site_id );
		$result = $this->agency_command->delete_site( $site_id );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'agency-command-centre', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}

	public function refresh_managed_site() {
		$site_id = absint( $_POST['site_id'] ?? 0 );
		$this->guard_agency( 'ikon_seo_refresh_managed_site_' . $site_id );
		$result = $this->agency_command->refresh_site( $site_id );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'agency-command-centre', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-site-refreshed=1' ) );
		exit;
	}

	public function refresh_all_managed_sites() {
		$this->guard_agency( 'ikon_seo_refresh_all_managed_sites' );
		$settings = Ikon_SEO_Plugin::settings();
		$result   = $this->agency_command->refresh_all( absint( $settings['agency_command_batch_size'] ?? 10 ) );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'agency-command-centre', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-site-refreshed=1' ) );
		exit;
	}

	public function record_agency_usage() {
		$this->guard_agency( 'ikon_seo_record_agency_usage' );
		$site_id = absint( $_POST['site_id'] ?? 0 );
		$result  = $this->agency_command->record_usage(
			$site_id,
			array(
				'category'   => sanitize_key( $_POST['category'] ?? 'research' ),
				'amount'     => max( 0, (float) ( $_POST['amount'] ?? 0 ) ),
				'currency'   => sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) ),
				'units'      => max( 0, (float) ( $_POST['units'] ?? 0 ) ),
				'note'       => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ),
				'event_date' => gmdate( 'Y-m-d' ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'agency-command-centre', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}

	public function resolve_agency_alert() {
		$alert_id = absint( $_POST['alert_id'] ?? 0 );
		$this->guard_agency( 'ikon_seo_resolve_agency_alert_' . $alert_id );
		$result = $this->agency_command->resolve_alert( $alert_id, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'agency-command-centre', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}


	public function refresh_executive_command() {
		$this->guard_agency( 'ikon_seo_refresh_executive_command' );
		$result = $this->executive_command->refresh( 200, get_current_user_id(), ! empty( $_POST['refresh_remote'] ) );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'agency-command-centre', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}

	public function update_executive_risk() {
		$risk_id = absint( $_POST['risk_id'] ?? 0 );
		$this->guard_agency( 'ikon_seo_update_executive_risk_' . $risk_id );
		$command = sanitize_key( $_POST['risk_command'] ?? 'assign_risk' );
		$payload = array(
			'command' => $command,
			'risk_id' => $risk_id,
			'owner_id' => absint( $_POST['owner_id'] ?? 0 ),
			'due_at' => sanitize_text_field( wp_unslash( $_POST['due_at'] ?? '' ) ),
			'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
		);
		$result = $this->executive_command->sync( $payload, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'agency-command-centre', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}

	public function update_executive_notification() {
		$notification_id = absint( $_POST['notification_id'] ?? 0 );
		$this->guard_agency( 'ikon_seo_update_executive_notification_' . $notification_id );
		$result = $this->executive_command->sync(
			array( 'command' => sanitize_key( $_POST['notification_command'] ?? 'acknowledge_notification' ), 'notification_id' => $notification_id ),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'agency-command-centre', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre&agency-command-updated=1' ) );
		exit;
	}

	public function export_agency_report() {
		$site_id = absint( $_POST['site_id'] ?? 0 );
		$this->guard_agency( 'ikon_seo_export_agency_report_' . $site_id );
		$html = $this->agency_command->render_report_html( $site_id );
		if ( is_wp_error( $html ) ) {
			$this->redirect_error( 'agency-command-centre', $html->get_error_message() );
		}
		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset', 'UTF-8' ) );
		header( 'Content-Disposition: attachment; filename="ikon-seo-client-report-' . $site_id . '-' . gmdate( 'Y-m-d' ) . '.html"' );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated report is escaped inside the renderer.
		exit;
	}

	public function export_agency_portfolio() {
		$this->guard_agency( 'ikon_seo_export_agency_portfolio' );
		$csv = $this->agency_command->portfolio_csv();
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="ikon-seo-portfolio-' . gmdate( 'Y-m-d' ) . '.csv"' );
		echo "\xEF\xBB\xBF"; // UTF-8 BOM for spreadsheet compatibility.
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV cells are encoded with fputcsv.
		exit;
	}

	private function valid_local_csv_upload( $file ) {
		$name = sanitize_file_name( $file['name'] ?? '' );
		return ! empty( $file['tmp_name'] )
			&& empty( $file['error'] )
			&& absint( $file['size'] ?? 0 ) <= 2 * MB_IN_BYTES
			&& 'csv' === strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	}

	private function guard( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Ikon SEO.', 'ikon-seo' ) );
		}
		check_admin_referer( $action );
	}


	private function guard_agency( $action ) {
		$this->guard( $action );
		if ( ! Ikon_SEO_Agency::can_manage() ) {
			wp_die( esc_html__( 'This setting is restricted to the Ikon agency account.', 'ikon-seo' ) );
		}
	}

	private function tab_link( $slug, $label, $active ) {
		$url = admin_url( 'admin.php?page=ikon-seo&tab=' . $slug );
		echo '<a class="nav-tab ' . ( $slug === $active ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}


	private function render_agency_command_centre() {
		if ( ! Ikon_SEO_Agency::can_manage() ) {
			return;
		}
		$settings = Ikon_SEO_Plugin::settings();
		$report   = $this->agency_command->summary( 200 );
		$executive_filters = array(
			'limit'         => 200,
			'site_id'       => absint( $_GET['command_site_id'] ?? 0 ),
			'severity'      => sanitize_key( $_GET['command_severity'] ?? '' ),
			'approval_type' => sanitize_key( $_GET['command_approval_type'] ?? '' ),
			'owner_id'      => absint( $_GET['command_owner_id'] ?? 0 ),
			'search'        => sanitize_text_field( wp_unslash( $_GET['command_search'] ?? '' ) ),
		);
		$executive = $this->executive_command->report( $executive_filters );
		$agent    = $this->agency_command->agent_status();
		$one_time_key = $this->agency_command->consume_agent_key( get_current_user_id() );
		$sites    = (array) ( $report['sites'] ?? array() );
		$metrics  = (array) ( $report['metrics'] ?? array() );
		$executive_metrics = (array) ( $executive['metrics'] ?? array() );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Agency Command Centre', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Consolidate website health, approvals, operational risks, service levels, capacity forecasts and executive portfolio analytics from one read-only agency dashboard.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo ! empty( $settings['agency_command_enabled'] ) ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( ! empty( $settings['agency_command_enabled'] ) ? 'Portfolio monitoring enabled' : 'Portfolio monitoring paused' ); ?></span>
		</div>

		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Safety boundary:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'The command centre reads bounded snapshots only. Publishing, redirects, live-page changes, public-profile edits and external outreach remain on the individual website and require human approval.', 'ikon-seo' ); ?></p></div>

		<form class="ikon-seo-card" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="ikon-seo">
			<input type="hidden" name="tab" value="agency-command-centre">
			<h3><?php esc_html_e( 'Portfolio filters', 'ikon-seo' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Search across connected websites, approval records, risks and internal notifications.', 'ikon-seo' ); ?></p>
			<div class="ikon-seo-inline-form">
				<label><?php esc_html_e( 'Website', 'ikon-seo' ); ?> <select name="command_site_id"><option value="0"><?php esc_html_e( 'All websites', 'ikon-seo' ); ?></option><?php foreach ( (array) ( $report['sites'] ?? array() ) as $filter_site ) : ?><option value="<?php echo absint( $filter_site['id'] ); ?>" <?php selected( $executive_filters['site_id'], absint( $filter_site['id'] ) ); ?>><?php echo esc_html( trim( ( $filter_site['client_name'] ?? '' ) . ' — ' . ( $filter_site['site_name'] ?? '' ), ' —' ) ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Severity', 'ikon-seo' ); ?> <select name="command_severity"><option value=""><?php esc_html_e( 'All severities', 'ikon-seo' ); ?></option><?php foreach ( array( 'critical','high','medium','low' ) as $severity ) : ?><option value="<?php echo esc_attr( $severity ); ?>" <?php selected( $executive_filters['severity'], $severity ); ?>><?php echo esc_html( ucfirst( $severity ) ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Approval type', 'ikon-seo' ); ?> <select name="command_approval_type"><option value=""><?php esc_html_e( 'All decisions', 'ikon-seo' ); ?></option><?php foreach ( array( 'fact_review','content_brief','editorial_review','publishing_readiness','manual_publication','search_impact','pattern_validation','governance_policy','client_report' ) as $approval_type ) : ?><option value="<?php echo esc_attr( $approval_type ); ?>" <?php selected( $executive_filters['approval_type'], $approval_type ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $approval_type ) ) ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Owner ID', 'ikon-seo' ); ?> <input type="number" min="0" name="command_owner_id" value="<?php echo esc_attr( $executive_filters['owner_id'] ); ?>" size="6"></label>
				<label><?php esc_html_e( 'Search', 'ikon-seo' ); ?> <input type="search" name="command_search" value="<?php echo esc_attr( $executive_filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Client, site, risk or decision', 'ikon-seo' ); ?>"></label>
				<button class="button button-primary"><?php esc_html_e( 'Apply filters', 'ikon-seo' ); ?></button>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=agency-command-centre' ) ); ?>"><?php esc_html_e( 'Clear', 'ikon-seo' ); ?></a>
			</div>
		</form>

		<?php if ( empty( $report['ready'] ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'The Agency Command Centre database is not ready. Deactivate and reactivate Ikon SEO once to run the database upgrade.', 'ikon-seo' ); ?></p></div>
		<?php endif; ?>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $metrics['total_sites'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Managed websites', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $metrics['attention'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Need attention', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $metrics['approvals'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Awaiting review', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $metrics['critical_alerts'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Critical alerts', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $metrics['overdue_tasks'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Overdue tasks', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $metrics['budget_risk'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Budget watch', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $metrics['duplication_risk'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Overlap reviews', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $metrics['stale'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Stale snapshots', 'ikon-seo' ); ?></span></div>
		</div>


		<section class="ikon-seo-card">
			<div class="ikon-seo-section-header"><div><h3><?php esc_html_e( 'Executive portfolio overview', 'ikon-seo' ); ?></h3><p class="description"><?php esc_html_e( 'Health scores expose their components and represent operational readiness, not rankings or revenue forecasts.', 'ikon-seo' ); ?></p></div><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_refresh_executive_command"><?php wp_nonce_field( 'ikon_seo_refresh_executive_command' ); ?><label><input type="checkbox" name="refresh_remote" value="1"> <?php esc_html_e( 'Refresh remote snapshots first', 'ikon-seo' ); ?></label> <button class="button button-primary"><?php esc_html_e( 'Refresh command centre', 'ikon-seo' ); ?></button></form></div>
			<div class="ikon-seo-metrics">
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $executive_metrics['websites'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Websites', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( (float) ( $executive_metrics['portfolio_health_average'] ?? 0 ), 1 ) ); ?></strong><span><?php esc_html_e( 'Average health', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $executive_metrics['websites_requiring_attention'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Require attention', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $executive_metrics['pending_approvals'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Pending approvals', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $executive_metrics['open_risks'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Open risks', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $executive_metrics['critical_risks'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Critical risks', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( (float) ( $executive_metrics['capacity_utilisation_percent'] ?? 0 ), 1 ) ); ?>%</strong><span><?php esc_html_e( 'Capacity used', 'ikon-seo' ); ?></span></div>
				<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( absint( $executive_metrics['reports_awaiting_approval'] ?? 0 ) ) ); ?></strong><span><?php esc_html_e( 'Reports to approve', 'ikon-seo' ); ?></span></div>
			</div>
		</section>

		<?php $portfolio_analytics = (array) ( $executive['portfolio_analytics'] ?? array() ); ?>
		<h2><?php esc_html_e( 'Executive portfolio analytics', 'ikon-seo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Aggregated operational counts remain segmented by workflow stage. They describe recorded activity and do not claim rankings, traffic, leads or revenue.', 'ikon-seo' ); ?></p>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Area', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recorded status totals', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php foreach ( array( 'health_levels' => 'Portfolio health', 'opportunities' => 'Opportunities', 'content' => 'Content production', 'editorial' => 'Editorial review', 'publishing' => 'Publishing readiness', 'search_impact' => 'Search Impact', 'client_reporting' => 'Client reporting' ) as $analytics_key => $analytics_label ) : ?>
			<tr><td><strong><?php echo esc_html( $analytics_label ); ?></strong></td><td><?php $status_totals = (array) ( $portfolio_analytics[ $analytics_key ] ?? array() ); if ( empty( $status_totals ) ) { esc_html_e( 'No recorded data', 'ikon-seo' ); } else { $pieces = array(); foreach ( $status_totals as $status_name => $status_count ) { $pieces[] = ucwords( str_replace( '_', ' ', $status_name ) ) . ': ' . absint( $status_count ); } echo esc_html( implode( ' · ', $pieces ) ); } ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>

		<h2><?php esc_html_e( 'Portfolio health scorecards', 'ikon-seo' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Health', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Workflow', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Service level', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Next action', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $executive['sites'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'Connect and refresh managed websites to build portfolio scorecards.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) ( $executive['sites'] ?? array() ) as $site ) : ?><tr><td><strong><?php echo esc_html( $site['client_name'] . ' — ' . $site['site_name'] ); ?></strong><br><small><?php echo esc_html( $site['last_snapshot_at'] ); ?></small></td><td><strong><?php echo esc_html( absint( $site['health']['score'] ) ); ?>/100</strong> · <?php echo esc_html( ucfirst( $site['health']['level'] ) ); ?><br><small><?php foreach ( (array) $site['health']['components'] as $label => $score ) { echo esc_html( ucwords( str_replace( '_', ' ', $label ) ) . ': ' . $score . ' ' ); } ?></small></td><td><?php echo esc_html( absint( $site['approval_count'] ) ); ?> approvals<br><?php echo esc_html( absint( $site['risk_count'] ) ); ?> risks</td><td><?php echo esc_html( $site['service']['plan_name'] ?? 'No active plan' ); ?><br><small><?php echo esc_html( (float) ( $site['service']['capacity_percent'] ?? 0 ) ); ?>% capacity · <?php echo esc_html( absint( $site['service']['overdue_items'] ?? 0 ) ); ?> overdue</small></td><td><?php echo esc_html( $site['next_action'] ); ?></td></tr><?php endforeach; ?>
		</tbody></table>

		<h2><?php esc_html_e( 'Unified approval inbox', 'ikon-seo' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Decision', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Due', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Review', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $executive['approvals'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No controlled decisions are currently waiting.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( array_slice( (array) ( $executive['approvals'] ?? array() ), 0, 100 ) as $item ) : ?><tr><td><?php echo esc_html( trim( $item['client_name'] . ' — ' . $item['site_name'], ' —' ) ); ?></td><td><strong><?php echo esc_html( $item['title'] ); ?></strong><br><small><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['type'] ) ) ); ?> · <?php echo esc_html( ucwords( str_replace( '_', ' ', $item['status'] ) ) ); ?></small></td><td><?php echo esc_html( absint( $item['priority'] ) ); ?></td><td><?php echo esc_html( $item['due_at'] ?: '—' ); ?></td><td><?php if ( $item['review_url'] ) : ?><a class="button" href="<?php echo esc_url( $item['review_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open review', 'ikon-seo' ); ?></a><?php else : ?>—<?php endif; ?></td></tr><?php endforeach; ?></tbody></table>

		<h2><?php esc_html_e( 'Portfolio risk register', 'ikon-seo' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Severity', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Website and risk', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Owner / due', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recommended action', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Decision', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $executive['risks'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No active executive risks are stored.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( array_slice( (array) ( $executive['risks'] ?? array() ), 0, 150 ) as $risk ) : ?><tr><td><span class="ikon-seo-pill <?php echo in_array( $risk['severity'], array( 'critical','high' ), true ) ? 'is-failed' : 'is-connected'; ?>"><?php echo esc_html( ucfirst( $risk['severity'] ) ); ?></span></td><td><strong><?php echo esc_html( $risk['site_name'] ?: 'Portfolio-wide' ); ?> — <?php echo esc_html( $risk['title'] ); ?></strong><br><?php echo esc_html( $risk['evidence']['summary'] ?? '' ); ?></td><td><?php echo esc_html( $risk['owner_id'] ? 'User #' . absint( $risk['owner_id'] ) : 'Unassigned' ); ?><br><small><?php echo esc_html( $risk['due_at'] ?: 'No due date' ); ?></small></td><td><?php echo esc_html( $risk['recommended_action'] ); ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_update_executive_risk"><input type="hidden" name="risk_id" value="<?php echo absint( $risk['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_update_executive_risk_' . absint( $risk['id'] ) ); ?><p><select name="risk_command"><option value="assign_risk">Assign/update</option><option value="resolve_risk">Resolve</option></select></p><p><input type="number" min="0" name="owner_id" value="<?php echo absint( $risk['owner_id'] ); ?>" placeholder="User ID"> <input type="datetime-local" name="due_at"></p><p><textarea name="notes" rows="2" placeholder="Required resolution note"></textarea></p><button class="button"><?php esc_html_e( 'Save', 'ikon-seo' ); ?></button></form></td></tr><?php endforeach; ?></tbody></table>

		<h2><?php esc_html_e( 'Internal notifications', 'ikon-seo' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Notification', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Updated', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $executive['notifications'] ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'No active internal notifications.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( array_slice( (array) ( $executive['notifications'] ?? array() ), 0, 100 ) as $notice ) : ?><tr><td><?php echo esc_html( ucfirst( $notice['status'] ) ); ?></td><td><strong><?php echo esc_html( $notice['title'] ); ?></strong><br><?php echo esc_html( $notice['summary'] ); ?></td><td><?php echo esc_html( $notice['updated_at'] ); ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_update_executive_notification"><input type="hidden" name="notification_id" value="<?php echo absint( $notice['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_update_executive_notification_' . absint( $notice['id'] ) ); ?><select name="notification_command"><option value="acknowledge_notification">Acknowledge</option><option value="dismiss_notification">Dismiss</option></select> <button class="button"><?php esc_html_e( 'Save', 'ikon-seo' ); ?></button></form></td></tr><?php endforeach; ?></tbody></table>

		<h2><?php esc_html_e( 'Capacity forecast', 'ikon-seo' ); ?></h2>
		<?php $forecast = (array) ( $executive['capacity_forecast'] ?? array() ); ?>
		<p><?php echo esc_html( sprintf( '%s committed of %s units (%s%%). %s overdue items and %s unassigned items.', absint( $forecast['committed_units'] ?? 0 ), absint( $forecast['total_capacity_units'] ?? 0 ), (float) ( $forecast['utilisation_percent'] ?? 0 ), absint( $forecast['overdue_items'] ?? 0 ), absint( $forecast['unassigned_items'] ?? 0 ) ) ); ?></p>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Team member', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Capacity', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Committed', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Remaining', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Utilisation', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $forecast['people'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No team capacity periods are configured.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( (array) ( $forecast['people'] ?? array() ) as $person ) : ?><tr><td><?php echo esc_html( $person['display_name'] ); ?></td><td><?php echo esc_html( absint( $person['capacity_units'] ) ); ?></td><td><?php echo esc_html( absint( $person['committed_units'] ) ); ?></td><td><?php echo esc_html( absint( $person['remaining_units'] ) ); ?></td><td><?php echo esc_html( (float) $person['utilisation_percent'] ); ?>%</td></tr><?php endforeach; ?></tbody></table>

		<?php if ( $one_time_key ) : ?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Copy this site key now:', 'ikon-seo' ); ?></strong></p>
				<p><textarea class="large-text code" rows="3" readonly onclick="this.select()"><?php echo esc_textarea( $one_time_key ); ?></textarea></p>
				<p><?php esc_html_e( 'Store it in the command-centre website only. It cannot publish or edit content and will not be shown again.', 'ikon-seo' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="ikon-seo-grid">
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Connect this website to a command centre', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Generate a separate read-only key for this website. Do not reuse the private workspace key.', 'ikon-seo' ); ?></p>
				<p><strong><?php esc_html_e( 'Snapshot endpoint:', 'ikon-seo' ); ?></strong><br><code><?php echo esc_html( $agent['endpoint'] ); ?></code></p>
				<p><strong><?php esc_html_e( 'Status:', 'ikon-seo' ); ?></strong> <?php echo esc_html( $agent['enabled'] ? 'Enabled' : ( $agent['configured'] ? 'Paused' : 'Not configured' ) ); ?><?php if ( $agent['last4'] ) : ?> · ending <?php echo esc_html( $agent['last4'] ); ?><?php endif; ?></p>
				<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_generate_agency_agent_key"><?php wp_nonce_field( 'ikon_seo_generate_agency_agent_key' ); ?>
					<button class="button button-primary"><?php echo esc_html( $agent['configured'] ? 'Replace site key' : 'Generate site key' ); ?></button>
				</form>
				<?php if ( $agent['configured'] ) : ?>
					<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ikon_seo_revoke_agency_agent_key"><?php wp_nonce_field( 'ikon_seo_revoke_agency_agent_key' ); ?>
						<button class="button"><?php esc_html_e( 'Revoke site key', 'ikon-seo' ); ?></button>
					</form>
				<?php endif; ?>
			</div>

			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Command-centre settings', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_agency_command_settings"><?php wp_nonce_field( 'ikon_seo_save_agency_command_settings' ); ?>
					<p><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['agency_command_enabled'] ) ); ?>> <?php esc_html_e( 'Enable scheduled portfolio monitoring on this website', 'ikon-seo' ); ?></label></p>
					<p><label><?php esc_html_e( 'Snapshot interval (hours)', 'ikon-seo' ); ?><br><input type="number" min="1" max="168" name="refresh_hours" value="<?php echo esc_attr( absint( $settings['agency_command_refresh_hours'] ?? 6 ) ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Sites per scheduled batch', 'ikon-seo' ); ?><br><input type="number" min="1" max="50" name="batch_size" value="<?php echo esc_attr( absint( $settings['agency_command_batch_size'] ?? 10 ) ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Default monthly research budget', 'ikon-seo' ); ?><br><input type="number" min="0" step="0.01" name="default_budget" value="<?php echo esc_attr( (float) ( $settings['agency_command_default_budget'] ?? 0 ) ); ?>"> <input type="text" maxlength="3" size="4" name="currency" value="<?php echo esc_attr( $settings['agency_command_currency'] ?? 'USD' ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Report brand name', 'ikon-seo' ); ?><br><input class="regular-text" type="text" name="brand_name" value="<?php echo esc_attr( $settings['agency_command_brand_name'] ?? 'Ikon SEO' ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Report logo URL', 'ikon-seo' ); ?><br><input class="large-text" type="url" name="logo_url" value="<?php echo esc_attr( $settings['agency_command_logo_url'] ?? '' ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Report footer', 'ikon-seo' ); ?><br><textarea class="large-text" rows="3" name="client_footer"><?php echo esc_textarea( $settings['agency_command_client_footer'] ?? '' ); ?></textarea></label></p>
					<button class="button button-primary"><?php esc_html_e( 'Save command-centre settings', 'ikon-seo' ); ?></button>
				</form>
			</div>
		</div>

		<h2><?php esc_html_e( 'Add a managed website', 'ikon-seo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'On the client website, generate a site key in its Agency Command Centre tab. Paste the complete key below. The website must use a public HTTPS address.', 'ikon-seo' ); ?></p>
		<form class="ikon-seo-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ikon_seo_add_managed_site"><?php wp_nonce_field( 'ikon_seo_add_managed_site' ); ?>
			<table class="form-table"><tbody>
			<tr><th><label for="ikon-agency-client"><?php esc_html_e( 'Client name', 'ikon-seo' ); ?></label></th><td><input id="ikon-agency-client" class="regular-text" name="client_name" required></td></tr>
			<tr><th><label for="ikon-agency-group"><?php esc_html_e( 'Portfolio group', 'ikon-seo' ); ?></label></th><td><input id="ikon-agency-group" class="regular-text" name="group_name" placeholder="Local clients, Editorial sites, UAE"></td></tr>
			<tr><th><label for="ikon-agency-url"><?php esc_html_e( 'Website URL', 'ikon-seo' ); ?></label></th><td><input id="ikon-agency-url" class="regular-text" type="url" name="site_url" placeholder="https://example.com/" required></td></tr>
			<tr><th><label for="ikon-agency-key"><?php esc_html_e( 'Read-only site key', 'ikon-seo' ); ?></label></th><td><input id="ikon-agency-key" class="large-text code" type="password" name="site_key" autocomplete="new-password" required></td></tr>
			<tr><th><label for="ikon-agency-budget"><?php esc_html_e( 'Monthly budget', 'ikon-seo' ); ?></label></th><td><input id="ikon-agency-budget" type="number" min="0" step="0.01" name="monthly_budget" value="<?php echo esc_attr( (float) ( $settings['agency_command_default_budget'] ?? 0 ) ); ?>"> <input type="text" maxlength="3" size="4" name="currency" value="<?php echo esc_attr( $settings['agency_command_currency'] ?? 'USD' ); ?>"></td></tr>
			</tbody></table>
			<button class="button button-primary"><?php esc_html_e( 'Connect and verify website', 'ikon-seo' ); ?></button>
		</form>

		<div class="ikon-seo-section-header"><div><h2><?php esc_html_e( 'Managed websites', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Prioritize websites by evidence, approvals, deadlines and budget—not by a fabricated ranking score.', 'ikon-seo' ); ?></p></div><div>
			<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_refresh_all_managed_sites"><?php wp_nonce_field( 'ikon_seo_refresh_all_managed_sites' ); ?><button class="button button-primary"><?php esc_html_e( 'Refresh portfolio snapshots', 'ikon-seo' ); ?></button></form>
			<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_export_agency_portfolio"><?php wp_nonce_field( 'ikon_seo_export_agency_portfolio' ); ?><button class="button"><?php esc_html_e( 'Export portfolio CSV', 'ikon-seo' ); ?></button></form>
		</div></div>

		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Client / website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Attention', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Strategy', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Priority evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Workflow', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Budget', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Actions', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( ! $sites ) : ?><tr><td colspan="7"><?php esc_html_e( 'No managed websites have been connected yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( $sites as $site ) : $snap = (array) ( $site['snapshot'] ?? array() ); ?>
		<tr>
			<td><strong><?php echo esc_html( $site['client_name'] ); ?></strong><br><a href="<?php echo esc_url( $site['site_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $site['site_name'] ); ?></a><br><span class="description"><?php echo esc_html( $site['group_name'] ?: 'Ungrouped' ); ?> · <?php echo esc_html( $site['last_snapshot_at'] ?: 'Never refreshed' ); ?></span><?php if ( $site['last_error'] ) : ?><br><span class="description"><?php echo esc_html( $site['last_error'] ); ?></span><?php endif; ?></td>
			<td><span class="ikon-seo-pill <?php echo in_array( $site['attention'], array( 'critical','high' ), true ) ? 'is-failed' : 'is-connected'; ?>"><?php echo esc_html( ucfirst( $site['attention'] ) ); ?></span><?php if ( $site['stale'] ) : ?><br><small><?php esc_html_e( 'Snapshot stale', 'ikon-seo' ); ?></small><?php endif; ?></td>
			<td><?php echo esc_html( $snap['strategy']['mode_label'] ?? 'Not available' ); ?><br><strong><?php echo esc_html( absint( $snap['strategy']['readiness'] ?? 0 ) ); ?>%</strong> <?php esc_html_e( 'ready', 'ikon-seo' ); ?></td>
			<td><?php echo esc_html( absint( $snap['diagnostics']['blockers']['critical'] ?? 0 ) ); ?> critical · <?php echo esc_html( absint( $snap['diagnostics']['blockers']['high'] ?? 0 ) ); ?> high<br><?php echo esc_html( absint( $snap['technical']['failed_urls'] ?? 0 ) ); ?> failed URLs · <?php echo esc_html( absint( $snap['technical']['orphans'] ?? 0 ) ); ?> orphans</td>
			<td><?php echo esc_html( count( (array) ( $snap['approvals']['review_drafts'] ?? array() ) ) + count( (array) ( $snap['approvals']['workflow_tasks'] ?? array() ) ) ); ?> approvals<br><?php echo esc_html( absint( $snap['workflow']['overdue'] ?? 0 ) ); ?> overdue</td>
			<td><?php echo esc_html( $site['budget']['currency'] . ' ' . number_format_i18n( $site['budget']['used'], 2 ) ); ?> / <?php echo esc_html( number_format_i18n( $site['budget']['limit'], 2 ) ); ?><br><?php echo esc_html( number_format_i18n( $site['budget']['percent'], 1 ) ); ?>%</td>
			<td>
				<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_refresh_managed_site"><input type="hidden" name="site_id" value="<?php echo absint( $site['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_refresh_managed_site_' . absint( $site['id'] ) ); ?><button class="button"><?php esc_html_e( 'Refresh', 'ikon-seo' ); ?></button></form>
				<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_export_agency_report"><input type="hidden" name="site_id" value="<?php echo absint( $site['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_export_agency_report_' . absint( $site['id'] ) ); ?><button class="button"><?php esc_html_e( 'Client report', 'ikon-seo' ); ?></button></form>
				<?php if ( ! empty( $snap['site']['admin_url'] ) ) : ?><a class="button" href="<?php echo esc_url( $snap['site']['admin_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open site', 'ikon-seo' ); ?></a><?php endif; ?>
				<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Remove this website from the command centre?');"><input type="hidden" name="action" value="ikon_seo_delete_managed_site"><input type="hidden" name="site_id" value="<?php echo absint( $site['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_delete_managed_site_' . absint( $site['id'] ) ); ?><button class="button-link-delete"><?php esc_html_e( 'Remove', 'ikon-seo' ); ?></button></form>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody></table>

		<?php if ( $sites ) : ?>
		<div class="ikon-seo-grid">
			<div class="ikon-seo-card"><h3><?php esc_html_e( 'Update a managed website', 'ikon-seo' ); ?></h3><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_update_managed_site"><?php wp_nonce_field( 'ikon_seo_update_managed_site' ); ?><p><select name="site_id" required><option value=""><?php esc_html_e( 'Select website', 'ikon-seo' ); ?></option><?php foreach ( $sites as $site ) : ?><option value="<?php echo absint( $site['id'] ); ?>"><?php echo esc_html( $site['client_name'] . ' — ' . $site['site_name'] ); ?></option><?php endforeach; ?></select></p><p><input class="regular-text" name="client_name" placeholder="Client name"></p><p><input class="regular-text" name="group_name" placeholder="Portfolio group"></p><p><input type="number" min="0" step="0.01" name="monthly_budget" placeholder="Monthly budget"> <input type="text" maxlength="3" size="4" name="currency" value="<?php echo esc_attr( $settings['agency_command_currency'] ?? 'USD' ); ?>"></p><p><input class="large-text code" type="password" name="site_key" placeholder="Optional replacement site key"></p><p><label><input type="checkbox" name="enabled" value="1" checked> <?php esc_html_e( 'Active', 'ikon-seo' ); ?></label></p><button class="button"><?php esc_html_e( 'Update website', 'ikon-seo' ); ?></button></form></div>
			<div class="ikon-seo-card"><h3><?php esc_html_e( 'Record research or service usage', 'ikon-seo' ); ?></h3><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_record_agency_usage"><?php wp_nonce_field( 'ikon_seo_record_agency_usage' ); ?><p><select name="site_id" required><option value=""><?php esc_html_e( 'Select website', 'ikon-seo' ); ?></option><?php foreach ( $sites as $site ) : ?><option value="<?php echo absint( $site['id'] ); ?>"><?php echo esc_html( $site['client_name'] . ' — ' . $site['site_name'] ); ?></option><?php endforeach; ?></select></p><p><select name="category"><option value="research">Research</option><option value="serp_data">Search-result data</option><option value="backlinks">Authority data</option><option value="content">Content production</option><option value="performance">Performance tools</option><option value="reporting">Reporting</option><option value="other">Other</option></select></p><p><input type="number" min="0" step="0.01" name="amount" placeholder="Cost amount"> <input type="text" maxlength="3" size="4" name="currency" value="<?php echo esc_attr( $settings['agency_command_currency'] ?? 'USD' ); ?>"></p><p><input type="number" min="0" step="0.01" name="units" placeholder="Optional units"></p><p><textarea class="large-text" name="note" rows="3" placeholder="What was used or purchased"></textarea></p><button class="button"><?php esc_html_e( 'Record usage', 'ikon-seo' ); ?></button></form></div>
		</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Cross-site approval queue', 'ikon-seo' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Type', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Item', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Review', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $report['approvals'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No stored items are awaiting approval.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( array_slice( (array) ( $report['approvals'] ?? array() ), 0, 100 ) as $item ) : ?><tr><td><?php echo esc_html( $item['client_name'] . ' — ' . $item['site_name'] ); ?></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['type'] ) ) ); ?></td><td><?php echo esc_html( $item['title'] ); ?></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['status'] ) ) ); ?><?php if ( $item['due_at'] ) : ?><br><small><?php echo esc_html( $item['due_at'] ); ?></small><?php endif; ?></td><td><?php if ( $item['review_url'] ) : ?><a class="button" href="<?php echo esc_url( $item['review_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open website', 'ikon-seo' ); ?></a><?php else : ?>—<?php endif; ?></td></tr><?php endforeach; ?></tbody></table>

		<h2><?php esc_html_e( 'Portfolio alerts', 'ikon-seo' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Severity', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Alert', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Last seen', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $report['alerts'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No open portfolio alerts.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( array_slice( (array) ( $report['alerts'] ?? array() ), 0, 150 ) as $alert ) : ?><tr><td><span class="ikon-seo-pill <?php echo in_array( $alert['severity'], array( 'critical','high' ), true ) ? 'is-failed' : 'is-connected'; ?>"><?php echo esc_html( ucfirst( $alert['severity'] ) ); ?></span></td><td><?php echo esc_html( $alert['client_name'] . ' — ' . $alert['site_name'] ); ?></td><td><strong><?php echo esc_html( $alert['title'] ); ?></strong><br><?php echo esc_html( $alert['summary'] ); ?></td><td><?php echo esc_html( $alert['last_seen_at'] ); ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_resolve_agency_alert"><input type="hidden" name="alert_id" value="<?php echo absint( $alert['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_resolve_agency_alert_' . absint( $alert['id'] ) ); ?><button class="button"><?php esc_html_e( 'Resolve', 'ikon-seo' ); ?></button></form></td></tr><?php endforeach; ?></tbody></table>

		<h2><?php esc_html_e( 'Portfolio duplication review', 'ikon-seo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'These warnings compare privacy-preserving content signatures. They do not transmit full article text and are not automatic plagiarism decisions.', 'ikon-seo' ); ?></p>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Overlap', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'First website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Second website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Review requirement', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $report['duplication_risks'] ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'No significant cross-site signature overlap is stored.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( array_slice( (array) ( $report['duplication_risks'] ?? array() ), 0, 50 ) as $risk ) : ?><tr><td><?php echo esc_html( number_format_i18n( 100 * $risk['score'], 1 ) ); ?>%</td><td><strong><?php echo esc_html( $risk['site_a']['site_name'] ); ?></strong><br><a href="<?php echo esc_url( $risk['site_a']['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $risk['site_a']['title'] ); ?></a></td><td><strong><?php echo esc_html( $risk['site_b']['site_name'] ); ?></strong><br><a href="<?php echo esc_url( $risk['site_b']['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $risk['site_b']['title'] ); ?></a></td><td><?php echo esc_html( $risk['note'] ); ?></td></tr><?php endforeach; ?></tbody></table>

		<h2><?php esc_html_e( 'Portfolio benchmarks', 'ikon-seo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Benchmarks compare operational readiness and stored evidence. They are not ranking scores and should be interpreted within each website’s business model.', 'ikon-seo' ); ?></p>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Website', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Strategy readiness', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Priority findings', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Technical', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Workload', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $report['benchmarks']['sites'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'Connect websites to build portfolio benchmarks.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( (array) ( $report['benchmarks']['sites'] ?? array() ) as $row ) : ?><tr><td><?php echo esc_html( $row['client_name'] . ' — ' . $row['site_name'] ); ?></td><td><?php echo esc_html( $row['strategy_readiness'] ); ?>%</td><td><?php echo esc_html( $row['critical_findings'] ); ?> critical · <?php echo esc_html( $row['high_findings'] ); ?> high</td><td><?php echo esc_html( $row['failed_urls'] ); ?> failed · <?php echo esc_html( $row['orphan_pages'] ); ?> orphans</td><td><?php echo esc_html( $row['approvals'] ); ?> approvals · <?php echo esc_html( $row['overdue_tasks'] ); ?> overdue</td></tr><?php endforeach; ?></tbody></table>
		<?php
	}

	private function render_authority_intelligence() {
		$status = $this->authority->status();
		$report = $this->authority->report( 100, false );
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Authority & Off-Site Evidence', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Map imported backlinks, referring domains, anchors, broken-link recovery and competitor source gaps without inventing an authority score.', 'ikon-seo' ); ?></p>
			</div>
			<span class="ikon-seo-pill <?php echo $status['backlinks'] ? 'is-connected' : 'is-failed'; ?>"><?php echo esc_html( $status['backlinks'] ? sprintf( '%s link records', number_format_i18n( $status['backlinks'] ) ) : 'No evidence imported' ); ?></span>
		</div>

		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Evidence policy:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'This dashboard reflects only imported datasets. Provider metrics are displayed as supplied and are never converted into an Ikon authority score. Review relevance and editorial quality before outreach.', 'ikon-seo' ); ?></p></div>

		<?php if ( is_wp_error( $report ) ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( $report->get_error_message() ); ?></p></div>
			<?php return; ?>
		<?php endif; ?>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $report['summary']['active_backlinks'] ) ); ?></strong><span><?php esc_html_e( 'Active records', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $report['summary']['referring_domains'] ) ); ?></strong><span><?php esc_html_e( 'Referring domains', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $report['summary']['broken_recovery_items'] ) ); ?></strong><span><?php esc_html_e( 'Recovery items', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( number_format_i18n( $report['summary']['competitor_gap_domains'] ) ); ?></strong><span><?php esc_html_e( 'Competitor source gaps', 'ikon-seo' ); ?></span></div>
		</div>

		<?php if ( Ikon_SEO_Agency::can_manage() ) : ?>
		<section class="ikon-seo-card">
			<h3><?php esc_html_e( 'Import approved link evidence', 'ikon-seo' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Upload a generic CSV or an export from Ahrefs, Semrush or Majestic. The file is parsed temporarily and is not kept in the Media Library.', 'ikon-seo' ); ?> <a href="<?php echo esc_url( IKON_SEO_URL . 'docs/AUTHORITY-IMPORT-TEMPLATE.csv' ); ?>"><?php esc_html_e( 'Download generic template', 'ikon-seo' ); ?></a></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_import_authority_csv">
				<?php wp_nonce_field( 'ikon_seo_import_authority_csv' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><label for="authority_file"><?php esc_html_e( 'CSV file', 'ikon-seo' ); ?></label></th><td><input required type="file" accept=".csv,.tsv,.txt,text/csv,text/tab-separated-values" id="authority_file" name="authority_file"></td></tr>
					<tr><th><label for="authority_provider"><?php esc_html_e( 'Source format', 'ikon-seo' ); ?></label></th><td><select id="authority_provider" name="provider"><option value="generic">Generic CSV</option><option value="ahrefs">Ahrefs export</option><option value="semrush">Semrush export</option><option value="majestic">Majestic export</option><option value="search_console">Search Console export</option></select></td></tr>
					<tr><th><label for="authority_relationship"><?php esc_html_e( 'Evidence type', 'ikon-seo' ); ?></label></th><td><select id="authority_relationship" name="relationship"><option value="site_backlink">Links to this website</option><option value="competitor_backlink">Links to a competitor</option></select></td></tr>
					<tr><th><label for="authority_competitor"><?php esc_html_e( 'Competitor domain', 'ikon-seo' ); ?></label></th><td><input class="regular-text" id="authority_competitor" name="competitor_domain" placeholder="example.com"><p class="description"><?php esc_html_e( 'Required only for competitor evidence.', 'ikon-seo' ); ?></p></td></tr>
				</table>
				<?php submit_button( __( 'Import evidence', 'ikon-seo' ) ); ?>
			</form>
		</section>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Leading referring domains', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Domain', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Links', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Follow', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Target pages', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Imported strength', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( $report['referring_domains'] ) : foreach ( array_slice( $report['referring_domains'], 0, 50 ) as $domain ) : ?>
			<tr><td><strong><?php echo esc_html( $domain['domain'] ); ?></strong></td><td><?php echo esc_html( number_format_i18n( $domain['links'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $domain['follow_links'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( count( $domain['target_pages'] ) ) ); ?></td><td><?php echo null === $domain['average_strength'] ? '—' : esc_html( $domain['average_strength'] ); ?></td></tr>
		<?php endforeach; else : ?><tr><td colspan="5"><?php esc_html_e( 'No website backlink evidence has been imported.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Broken-link and lost-link recovery', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Source', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Target', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Confidence', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( $report['broken_link_recovery'] ) : foreach ( array_slice( $report['broken_link_recovery'], 0, 50 ) as $item ) : ?>
			<tr><td><a href="<?php echo esc_url( $item['source_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $item['source_domain'] ); ?></a></td><td><a href="<?php echo esc_url( $item['target_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $item['target_url'], PHP_URL_PATH ) ?: '/' ); ?></a></td><td><?php echo esc_html( $item['problem'] ); ?></td><td><?php echo esc_html( ucfirst( $item['confidence'] ) ); ?></td></tr>
		<?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e( 'No recoverable broken or lost link was detected in the imported evidence.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Competitor source-domain gaps', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Source domain', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Competitors linked', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Observed links', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Research priority', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( $report['competitor_link_gaps'] ) : foreach ( array_slice( $report['competitor_link_gaps'], 0, 50 ) as $gap ) : ?>
			<tr><td><strong><?php echo esc_html( $gap['source_domain'] ); ?></strong></td><td><?php echo esc_html( implode( ', ', array_slice( $gap['competitors'], 0, 4 ) ) ); ?></td><td><?php echo esc_html( number_format_i18n( $gap['links'] ) ); ?></td><td><?php echo esc_html( absint( $gap['priority'] ) ); ?>/100</td></tr>
		<?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e( 'Import competitor backlink evidence to identify shared source-domain gaps.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Anchor evidence', 'ikon-seo' ); ?></h3>
		<div class="ikon-seo-metrics">
		<?php foreach ( (array) $report['anchor_distribution']['categories'] as $category => $data ) : ?>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( $data['share'] ); ?>%</strong><span><?php echo esc_html( ucfirst( str_replace( '_', ' ', $category ) ) ); ?></span></div>
		<?php endforeach; ?>
		</div>
		<p class="description"><?php echo esc_html( implode( ' ', $report['limitations'] ) ); ?></p>
		<?php
	}

	public function import_authority_csv() {
		if ( ! current_user_can( 'manage_options' ) || ! Ikon_SEO_Agency::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to import authority evidence.', 'ikon-seo' ) );
		}
		check_admin_referer( 'ikon_seo_import_authority_csv' );
		$file = $_FILES['authority_file'] ?? array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $file['tmp_name'] ) || ! empty( $file['error'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$this->redirect_error( 'authority-intelligence', __( 'Choose a valid CSV file to import.', 'ikon-seo' ) );
		}
		if ( (int) ( $file['size'] ?? 0 ) > 10 * MB_IN_BYTES ) {
			$this->redirect_error( 'authority-intelligence', __( 'The CSV file exceeds the 10 MB import limit.', 'ikon-seo' ) );
		}
		$extension = strtolower( pathinfo( sanitize_file_name( $file['name'] ?? '' ), PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, array( 'csv', 'tsv', 'txt' ), true ) ) {
			$this->redirect_error( 'authority-intelligence', __( 'Only CSV, TSV or plain-text table exports are accepted.', 'ikon-seo' ) );
		}
		$result = $this->authority->import_csv(
			$file['tmp_name'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$file['name'] ?? 'backlinks.csv', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			sanitize_key( $_POST['provider'] ?? 'generic' ),
			sanitize_key( $_POST['relationship'] ?? 'site_backlink' ),
			sanitize_text_field( wp_unslash( $_POST['competitor_domain'] ?? '' ) ),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'authority-intelligence', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=authority-intelligence&authority-imported=1' ) );
		exit;
	}

	public function archive_authority_link() {
		if ( ! current_user_can( 'manage_options' ) || ! Ikon_SEO_Agency::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to archive authority evidence.', 'ikon-seo' ) );
		}
		check_admin_referer( 'ikon_seo_archive_authority_link' );
		if ( ! $this->authority->archive_link( absint( $_POST['id'] ?? 0 ) ) ) {
			$this->redirect_error( 'authority-intelligence', __( 'The authority evidence could not be archived.', 'ikon-seo' ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=authority-intelligence' ) );
		exit;
	}

	private function render_workflow_automation() {
		$state = $this->automation->summary( 100 );
		if ( empty( $state['ready'] ) ) {
			?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Workflow tables are not ready. Reactivate the plugin or run the database upgrade.', 'ikon-seo' ); ?></p></div><?php
			return;
		}
		$counts    = (array) $state['counts'];
		$templates = (array) $state['templates'];
		$settings  = Ikon_SEO_Plugin::settings();
		$users     = get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name' ) );
		?>
		<h2><?php esc_html_e( 'Workflow Automation', 'ikon-seo' ); ?></h2>
		<p><?php esc_html_e( 'Turn the Website Strategy into repeatable tasks, approvals, safe evidence refreshes and permanent briefings. Unattended execution is restricted to read-only analysis.', 'ikon-seo' ); ?></p>
		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Safety boundary:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'Publishing, live edits, redirects, canonical or indexing changes, outreach and business-profile changes always require human approval.', 'ikon-seo' ); ?></p></div>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo esc_html( count( (array) $state['workflows'] ) ); ?></strong><span><?php esc_html_e( 'Workflows', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $counts['ready'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Ready', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $counts['pending_approval'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Awaiting approval', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $state['overdue'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Overdue', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $counts['failed'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Failed', 'ikon-seo' ); ?></span></div>
		</div>

		<?php if ( Ikon_SEO_Agency::can_manage() ) : ?>
		<div class="ikon-seo-grid">
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Create a mode-based workflow', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_create_workflow">
					<?php wp_nonce_field( 'ikon_seo_create_workflow' ); ?>
					<p><label for="workflow_template"><strong><?php esc_html_e( 'Template', 'ikon-seo' ); ?></strong></label><br>
					<select id="workflow_template" name="template">
					<?php foreach ( $templates as $key => $template ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $state['recommended_template'] ); ?>><?php echo esc_html( $template['label'] . ' — ' . $template['task_count'] . ' tasks' ); ?></option>
					<?php endforeach; ?>
					</select></p>
					<p><label for="workflow_name"><strong><?php esc_html_e( 'Workflow name', 'ikon-seo' ); ?></strong></label><br><input class="regular-text" id="workflow_name" name="name" placeholder="<?php esc_attr_e( 'Quarterly SEO growth cycle', 'ikon-seo' ); ?>"></p>
					<p><label for="workflow_start"><strong><?php esc_html_e( 'Start date', 'ikon-seo' ); ?></strong></label><br><input type="date" id="workflow_start" name="start_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"></p>
					<p><label for="workflow_owner"><strong><?php esc_html_e( 'Default owner', 'ikon-seo' ); ?></strong></label><br><select id="workflow_owner" name="owner_id"><option value="0"><?php esc_html_e( 'Unassigned', 'ikon-seo' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>" <?php selected( get_current_user_id(), $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></p>
					<?php submit_button( __( 'Create workflow', 'ikon-seo' ), 'primary', 'submit', false ); ?>
				</form>
			</section>
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Scheduler and retry policy', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_workflow_automation">
					<?php wp_nonce_field( 'ikon_seo_save_workflow_automation' ); ?>
					<p><label><input type="checkbox" name="workflow_automation_enabled" value="1" <?php checked( $settings['workflow_automation_enabled'] ); ?>> <?php esc_html_e( 'Run due read-only tasks automatically', 'ikon-seo' ); ?></label></p>
					<p><label><input type="checkbox" name="workflow_daily_briefing_enabled" value="1" <?php checked( $settings['workflow_daily_briefing_enabled'] ); ?>> <?php esc_html_e( 'Create daily briefings', 'ikon-seo' ); ?></label></p>
					<p><label><input type="checkbox" name="workflow_weekly_briefing_enabled" value="1" <?php checked( $settings['workflow_weekly_briefing_enabled'] ); ?>> <?php esc_html_e( 'Create weekly briefings', 'ikon-seo' ); ?></label></p>
					<p><label for="workflow_runner_batch"><?php esc_html_e( 'Maximum tasks per run', 'ikon-seo' ); ?></label><br><input type="number" min="1" max="10" id="workflow_runner_batch" name="workflow_runner_batch" value="<?php echo esc_attr( $settings['workflow_runner_batch'] ); ?>"></p>
					<p><label for="workflow_retry_limit"><?php esc_html_e( 'Retry limit', 'ikon-seo' ); ?></label><br><input type="number" min="1" max="10" id="workflow_retry_limit" name="workflow_retry_limit" value="<?php echo esc_attr( $settings['workflow_retry_limit'] ); ?>"></p>
					<?php submit_button( __( 'Save scheduler settings', 'ikon-seo' ), 'secondary', 'submit', false ); ?>
				</form>
				<hr>
				<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_run_workflow_automation"><?php wp_nonce_field( 'ikon_seo_run_workflow_automation' ); ?><button class="button button-primary"><?php esc_html_e( 'Run safe tasks now', 'ikon-seo' ); ?></button></form>
				<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_generate_workflow_briefing"><input type="hidden" name="period" value="weekly"><?php wp_nonce_field( 'ikon_seo_generate_workflow_briefing' ); ?><button class="button"><?php esc_html_e( 'Generate briefing', 'ikon-seo' ); ?></button></form>
				<p class="description"><?php echo esc_html( $state['scheduler']['wp_cron_note'] ?? '' ); ?></p>
			</section>
		</div>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Workflow progress', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Workflow', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Mode', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Progress', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Started', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $state['workflows'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No workflow has been created yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) $state['workflows'] as $workflow ) : ?><tr><td><strong><?php echo esc_html( $workflow['name'] ); ?></strong><br><code><?php echo esc_html( $workflow['template_key'] ); ?></code></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $workflow['website_mode'] ) ) ); ?></td><td><?php echo esc_html( ucfirst( $workflow['status'] ) ); ?></td><td><?php echo esc_html( absint( $workflow['progress_percent'] ) ); ?>%</td><td><?php echo esc_html( $workflow['start_date'] ); ?></td></tr><?php endforeach; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Open tasks', 'ikon-seo' ); ?></h3>
		<table class="widefat striped ikon-seo-log"><thead><tr><th><?php esc_html_e( 'Task', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Owner / due', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Safety', 'ikon-seo' ); ?></th><?php if ( Ikon_SEO_Agency::can_manage() ) : ?><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th><?php endif; ?></tr></thead><tbody>
		<?php if ( empty( $state['tasks'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'There are no open tasks.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) $state['tasks'] as $task ) : ?>
		<tr>
			<td><strong><?php echo esc_html( $task['title'] ); ?></strong><br><span class="description"><?php echo esc_html( $task['workflow_name'] ); ?> · <?php echo esc_html( $task['description'] ); ?></span><?php if ( $task['last_error'] ) : ?><br><span class="ikon-seo-pill is-failed"><?php echo esc_html( $task['last_error'] ); ?></span><?php endif; ?></td>
			<td><span class="ikon-seo-pill<?php echo $task['overdue'] ? ' is-failed' : ''; ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $task['status'] ) ) ); ?></span></td>
			<td><?php echo esc_html( $task['owner_name'] ?: 'Unassigned' ); ?><br><?php echo esc_html( $task['due_at'] ?: 'No due date' ); ?></td>
			<td><?php echo esc_html( ucfirst( $task['safe_level'] ) ); ?><?php if ( $task['automation_action'] ) : ?><br><code><?php echo esc_html( $task['automation_action'] ); ?></code><?php endif; ?></td>
			<?php if ( Ikon_SEO_Agency::can_manage() ) : ?><td>
				<?php if ( 'pending_approval' === $task['status'] ) : ?><form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_approve_workflow_task"><input type="hidden" name="task_id" value="<?php echo absint( $task['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_approve_workflow_task_' . absint( $task['id'] ) ); ?><button class="button button-primary"><?php esc_html_e( 'Approve', 'ikon-seo' ); ?></button></form><?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_update_workflow_task"><input type="hidden" name="task_id" value="<?php echo absint( $task['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_update_workflow_task_' . absint( $task['id'] ) ); ?>
					<select name="status"><option value="<?php echo esc_attr( $task['status'] ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $task['status'] ) ) ); ?></option><option value="completed"><?php esc_html_e( 'Completed', 'ikon-seo' ); ?></option><option value="skipped"><?php esc_html_e( 'Skipped', 'ikon-seo' ); ?></option><option value="ready"><?php esc_html_e( 'Ready', 'ikon-seo' ); ?></option><option value="blocked"><?php esc_html_e( 'Blocked', 'ikon-seo' ); ?></option></select>
					<input type="date" name="due_at" value="<?php echo esc_attr( $task['due_at'] ? substr( $task['due_at'], 0, 10 ) : '' ); ?>">
					<button class="button"><?php esc_html_e( 'Update', 'ikon-seo' ); ?></button>
				</form>
			</td><?php endif; ?>
		</tr>
		<?php endforeach; ?>
		</tbody></table>

		<?php if ( ! empty( $state['latest_briefing'] ) ) : ?><section class="ikon-seo-card"><h3><?php esc_html_e( 'Latest briefing', 'ikon-seo' ); ?></h3><p><?php echo esc_html( $state['latest_briefing']['summary'] ?? '' ); ?></p><p class="description"><?php echo esc_html( $state['latest_briefing']['generated_at'] ?? '' ); ?> UTC</p></section><?php endif; ?>
		<?php
	}

	public function create_workflow() {
		$this->require_workflow_manager( 'create workflows' );
		check_admin_referer( 'ikon_seo_create_workflow' );
		$result = $this->automation->create_workflow(
			sanitize_key( $_POST['template'] ?? '' ),
			array(
				'name'       => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'owner_id'   => absint( $_POST['owner_id'] ?? 0 ),
				'start_date' => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ),
				'created_by' => get_current_user_id(),
			)
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'workflow-automation', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=workflow-automation&workflow-created=1' ) );
		exit;
	}

	public function update_workflow_task() {
		$this->require_workflow_manager( 'update workflow tasks' );
		$task_id = absint( $_POST['task_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_update_workflow_task_' . $task_id );
		$result = $this->automation->update_task(
			$task_id,
			array(
				'status' => sanitize_key( $_POST['status'] ?? '' ),
				'due_at' => sanitize_text_field( wp_unslash( $_POST['due_at'] ?? '' ) ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'workflow-automation', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=workflow-automation&workflow-updated=1' ) );
		exit;
	}

	public function approve_workflow_task() {
		$this->require_workflow_manager( 'approve workflow tasks' );
		$task_id = absint( $_POST['task_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_approve_workflow_task_' . $task_id );
		$result = $this->automation->approve_task( $task_id, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( 'workflow-automation', $result->get_error_message() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=workflow-automation&workflow-approved=1' ) );
		exit;
	}

	public function run_workflow_automation() {
		$this->require_workflow_manager( 'run workflow automation' );
		check_admin_referer( 'ikon_seo_run_workflow_automation' );
		$settings = Ikon_SEO_Plugin::settings();
		$this->automation->run_safe_tasks( absint( $settings['workflow_runner_batch'] ?? 3 ), true );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=workflow-automation&workflow-run=1' ) );
		exit;
	}

	public function save_workflow_automation() {
		$this->require_workflow_manager( 'change workflow automation settings' );
		check_admin_referer( 'ikon_seo_save_workflow_automation' );
		$this->automation->save_settings( wp_unslash( $_POST ) );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=workflow-automation&workflow-settings-saved=1' ) );
		exit;
	}

	public function generate_workflow_briefing() {
		$this->require_workflow_manager( 'generate workflow briefings' );
		check_admin_referer( 'ikon_seo_generate_workflow_briefing' );
		$this->automation->generate_briefing( sanitize_key( $_POST['period'] ?? 'weekly' ), true );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=workflow-automation&workflow-briefing=1' ) );
		exit;
	}


	private function render_publisher_intelligence() {
		$report = $this->publisher->report( 100, false );
		$status = (array) ( $report['status'] ?? array() );
		if ( empty( $status['database_ready'] ) ) {
			?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Publisher Intelligence tables are not ready. Reactivate the plugin or run the database upgrade.', 'ikon-seo' ); ?></p></div><?php
			return;
		}
		$counts = (array) ( $status['counts'] ?? array() );
		$users = get_users( array( 'fields' => array( 'ID','display_name' ), 'orderby' => 'display_name' ) );
		$posts = get_posts( array( 'post_type' => get_post_types( array( 'public' => true ) ), 'post_status' => array( 'publish','draft','pending' ), 'posts_per_page' => 250, 'orderby' => 'modified', 'order' => 'DESC' ) );
		?>
		<h2><?php esc_html_e( 'Publisher Intelligence', 'ikon-seo' ); ?></h2>
		<p><?php esc_html_e( 'Plan topic hubs, manage an evidence-led editorial calendar, assign authors and reviewers, enforce quality gates, detect portfolio overlap and review content lifecycle decisions.', 'ikon-seo' ); ?></p>
		<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Approval boundary:', 'ikon-seo' ); ?></strong> <?php esc_html_e( 'This workspace can plan and review content, but it never publishes, consolidates, redirects or retires pages automatically.', 'ikon-seo' ); ?></p></div>
		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $counts['keywords'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Opportunities', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $counts['hubs'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Topic hubs', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $counts['pipeline'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Pipeline items', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $counts['awaiting_review'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Awaiting review', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo esc_html( absint( $counts['refresh_due'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Refresh due', 'ikon-seo' ); ?></span></div>
		</div>

		<?php if ( Ikon_SEO_Agency::can_manage() ) : ?>
		<div class="ikon-seo-grid">
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Add opportunities', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_publisher_keywords"><?php wp_nonce_field( 'ikon_seo_save_publisher_keywords' ); ?>
					<p><label><strong><?php esc_html_e( 'One keyword or opportunity per line', 'ikon-seo' ); ?></strong><br><textarea class="large-text" rows="6" name="keywords" required></textarea></label></p>
					<p><label><?php esc_html_e( 'Cluster', 'ikon-seo' ); ?><br><input class="regular-text" name="cluster"></label></p>
					<p><label><?php esc_html_e( 'Intent', 'ikon-seo' ); ?><br><select name="intent"><option value="informational">Informational</option><option value="commercial">Commercial</option><option value="transactional">Transactional</option><option value="local_service">Local service</option><option value="mixed">Mixed</option></select></label></p>
					<p><label><?php esc_html_e( 'Page type', 'ikon-seo' ); ?><br><select name="page_type"><option value="article">Article</option><option value="guide">Guide</option><option value="comparison">Comparison</option><option value="review">Review</option><option value="service">Service</option><option value="location">Location</option><option value="category">Category</option><option value="product">Product</option><option value="hub">Hub</option></select></label></p>
					<p><label><?php esc_html_e( 'Demand', 'ikon-seo' ); ?><br><select name="demand_band"><option value="unknown">Unknown</option><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="very_high">Very high</option></select></label> <label><?php esc_html_e( 'Difficulty', 'ikon-seo' ); ?> <select name="difficulty_band"><option value="unknown">Unknown</option><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="very_high">Very high</option></select></label></p>
					<p><label><?php esc_html_e( 'Business value', 'ikon-seo' ); ?> <input type="number" min="0" max="100" name="business_value" value="60"></label></p>
					<?php submit_button( __( 'Save opportunities', 'ikon-seo' ), 'primary', 'submit', false ); ?>
				</form>
			</section>
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Create a topic hub', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_publisher_hub"><?php wp_nonce_field( 'ikon_seo_save_publisher_hub' ); ?>
					<p><label><?php esc_html_e( 'Hub title', 'ikon-seo' ); ?><br><input class="regular-text" name="title" required></label></p>
					<p><label><?php esc_html_e( 'Purpose', 'ikon-seo' ); ?><br><textarea class="large-text" rows="3" name="description"></textarea></label></p>
					<p><label><?php esc_html_e( 'Target audience', 'ikon-seo' ); ?><br><textarea class="large-text" rows="2" name="target_audience"></textarea></label></p>
					<p><label><?php esc_html_e( 'Pillar page', 'ikon-seo' ); ?><br><select name="pillar_post_id"><option value="0">Not assigned</option><?php foreach ( $posts as $post ) : ?><option value="<?php echo absint( $post->ID ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></option><?php endforeach; ?></select></label></p>
					<p><label><?php esc_html_e( 'Opportunity IDs', 'ikon-seo' ); ?><br><input class="regular-text" name="keyword_ids" placeholder="1, 2, 3"></label></p>
					<?php submit_button( __( 'Save topic hub', 'ikon-seo' ), 'secondary', 'submit', false ); ?>
				</form>
			</section>
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Create a pipeline item', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_publisher_item"><?php wp_nonce_field( 'ikon_seo_save_publisher_item' ); ?>
					<p><label><?php esc_html_e( 'Working title', 'ikon-seo' ); ?><br><input class="regular-text" name="title" required></label></p>
					<p><label><?php esc_html_e( 'Opportunity', 'ikon-seo' ); ?><br><select name="keyword_id"><option value="0">Not assigned</option><?php foreach ( (array) $report['keywords'] as $keyword ) : ?><option value="<?php echo absint( $keyword['id'] ); ?>"><?php echo esc_html( $keyword['keyword'] ); ?></option><?php endforeach; ?></select></label></p>
					<p><label><?php esc_html_e( 'Topic hub', 'ikon-seo' ); ?><br><select name="hub_id"><option value="0">Not assigned</option><?php foreach ( (array) $report['hubs'] as $hub ) : ?><option value="<?php echo absint( $hub['id'] ); ?>"><?php echo esc_html( $hub['title'] ); ?></option><?php endforeach; ?></select></label></p>
					<p><label><?php esc_html_e( 'Stage', 'ikon-seo' ); ?><br><select name="stage"><option value="idea">Idea</option><option value="research">Research</option><option value="planned">Planned</option><option value="brief">Brief</option><option value="drafting">Drafting</option><option value="review">Review</option></select></label></p>
					<p><label><?php esc_html_e( 'Author', 'ikon-seo' ); ?><br><select name="author_id"><option value="0">Unassigned</option><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label> <label><?php esc_html_e( 'Reviewer', 'ikon-seo' ); ?> <select name="reviewer_id"><option value="0">Unassigned</option><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label></p>
					<p><label><?php esc_html_e( 'Due date', 'ikon-seo' ); ?><br><input type="date" name="due_at"></label></p>
					<p><label><?php esc_html_e( 'Linked WordPress content', 'ikon-seo' ); ?><br><select name="target_post_id"><option value="0">Not linked</option><?php foreach ( $posts as $post ) : ?><option value="<?php echo absint( $post->ID ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></option><?php endforeach; ?></select></label></p>
					<p><label><?php esc_html_e( 'Source requirements', 'ikon-seo' ); ?><br><textarea class="large-text" rows="2" name="source_requirements"></textarea></label></p>
					<p><label><input type="checkbox" name="originality_required" value="1" checked> <?php esc_html_e( 'Require originality review', 'ikon-seo' ); ?></label> <label><input type="checkbox" name="disclosure_required" value="1"> <?php esc_html_e( 'Require commercial disclosure', 'ikon-seo' ); ?></label></p>
					<?php submit_button( __( 'Save pipeline item', 'ikon-seo' ), 'secondary', 'submit', false ); ?>
				</form>
			</section>
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Contributor profile', 'ikon-seo' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_save_publisher_contributor"><?php wp_nonce_field( 'ikon_seo_save_publisher_contributor' ); ?>
					<p><label><?php esc_html_e( 'WordPress user', 'ikon-seo' ); ?><br><select name="user_id" required><?php foreach ( $users as $user ) : ?><option value="<?php echo absint( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label></p>
					<p><label><?php esc_html_e( 'Roles', 'ikon-seo' ); ?><br><input class="regular-text" name="roles" placeholder="author, reviewer, subject_expert"></label></p>
					<p><label><?php esc_html_e( 'Expertise', 'ikon-seo' ); ?><br><textarea class="large-text" rows="3" name="expertise"></textarea></label></p>
					<p><label><?php esc_html_e( 'Qualification or experience evidence', 'ikon-seo' ); ?><br><textarea class="large-text" rows="2" name="evidence"></textarea></label></p>
					<p><label><input type="checkbox" name="active" value="1" checked> <?php esc_html_e( 'Active contributor', 'ikon-seo' ); ?></label></p>
					<?php submit_button( __( 'Save contributor profile', 'ikon-seo' ), 'secondary', 'submit', false ); ?>
				</form>
			</section>
		</div>

		<div class="ikon-seo-grid">
			<section class="ikon-seo-card"><h3><?php esc_html_e( 'Planning and lifecycle tools', 'ikon-seo' ); ?></h3>
				<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_generate_publisher_calendar"><?php wp_nonce_field( 'ikon_seo_generate_publisher_calendar' ); ?><label><?php esc_html_e( 'Weeks', 'ikon-seo' ); ?> <input type="number" min="1" max="52" name="weeks" value="12"></label> <button class="button button-primary"><?php esc_html_e( 'Generate editorial calendar', 'ikon-seo' ); ?></button></form>
				<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_review_publisher_lifecycle"><?php wp_nonce_field( 'ikon_seo_review_publisher_lifecycle' ); ?><button class="button"><?php esc_html_e( 'Run lifecycle review', 'ikon-seo' ); ?></button></form>
			</section>
			<section class="ikon-seo-card"><h3><?php esc_html_e( 'Portfolio duplication safeguards', 'ikon-seo' ); ?></h3><p><?php esc_html_e( 'Export privacy-preserving signatures from one website and import them into another. Full article text is not included.', 'ikon-seo' ); ?></p>
				<form class="ikon-seo-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_export_publisher_signatures"><?php wp_nonce_field( 'ikon_seo_export_publisher_signatures' ); ?><button class="button"><?php esc_html_e( 'Export signature bundle', 'ikon-seo' ); ?></button></form>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_import_publisher_signatures"><?php wp_nonce_field( 'ikon_seo_import_publisher_signatures' ); ?><input type="file" name="signature_file" accept="application/json,.json" required> <button class="button"><?php esc_html_e( 'Import signature bundle', 'ikon-seo' ); ?></button></form>
			</section>
		</div>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Editorial pipeline', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Content', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Stage', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Owner / due', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Quality gate', 'ikon-seo' ); ?></th><?php if ( Ikon_SEO_Agency::can_manage() ) : ?><th><?php esc_html_e( 'Review', 'ikon-seo' ); ?></th><?php endif; ?></tr></thead><tbody>
		<?php if ( empty( $report['pipeline'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No editorial item has been created.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) $report['pipeline'] as $item ) : $author = get_user_by( 'id', absint( $item['author_id'] ) ); ?>
		<tr><td><strong><?php echo esc_html( $item['title'] ); ?></strong><br><span class="description"><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['content_type'] ) ) ); ?><?php if ( $item['target_post_id'] ) : ?> · #<?php echo absint( $item['target_post_id'] ); ?><?php endif; ?></span></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['stage'] ) ) ); ?></td><td><?php echo esc_html( $author ? $author->display_name : 'Unassigned' ); ?><br><?php echo esc_html( $item['due_at'] ?: 'No due date' ); ?></td><td><?php echo esc_html( absint( $item['quality_score'] ) ); ?>/100<br><span class="ikon-seo-pill"><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['gate_status'] ) ) ); ?></span></td><?php if ( Ikon_SEO_Agency::can_manage() ) : ?><td><?php if ( $item['target_post_id'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_evaluate_publisher_post"><input type="hidden" name="post_id" value="<?php echo absint( $item['target_post_id'] ); ?>"><input type="hidden" name="item_id" value="<?php echo absint( $item['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_evaluate_publisher_post_' . absint( $item['id'] ) ); ?><button class="button"><?php esc_html_e( 'Run quality gate', 'ikon-seo' ); ?></button></form><?php else : ?>—<?php endif; ?></td><?php endif; ?></tr>
		<?php endforeach; ?></tbody></table>

		<h3><?php esc_html_e( 'Highest-priority opportunities', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Opportunity', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Cluster / intent', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Demand / difficulty', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['keywords'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No opportunities have been stored.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( array_slice( (array) $report['keywords'], 0, 50 ) as $keyword ) : ?><tr><td><strong><?php echo esc_html( $keyword['keyword'] ); ?></strong></td><td><?php echo esc_html( $keyword['cluster'] ?: 'Unclustered' ); ?><br><?php echo esc_html( ucwords( str_replace( '_', ' ', $keyword['intent'] ) ) ); ?></td><td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $keyword['demand_band'] ) ) ); ?> / <?php echo esc_html( ucfirst( str_replace( '_', ' ', $keyword['difficulty_band'] ) ) ); ?></td><td><?php echo esc_html( absint( $keyword['priority'] ) ); ?></td><td><?php echo esc_html( ucfirst( $keyword['status'] ) ); ?></td></tr><?php endforeach; ?></tbody></table>

		<h3><?php esc_html_e( 'Topic hubs', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Hub', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Pillar', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Coverage', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Readiness', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['hubs'] ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'No topic hubs have been created.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) $report['hubs'] as $hub ) : ?><tr><td><strong><?php echo esc_html( $hub['title'] ); ?></strong><br><span class="description"><?php echo esc_html( $hub['description'] ); ?></span></td><td><?php echo $hub['pillar_post_id'] ? esc_html( get_the_title( $hub['pillar_post_id'] ) ) : 'Not assigned'; ?></td><td><?php echo esc_html( count( $hub['keyword_ids'] ) ); ?> opportunities / <?php echo esc_html( count( $hub['supporting_post_ids'] ) ); ?> supporting pages</td><td><?php echo esc_html( absint( $hub['readiness'] ) ); ?>%</td></tr><?php endforeach; ?></tbody></table>

		<?php if ( ! empty( $report['lifecycle'] ) ) : ?><h3><?php esc_html_e( 'Content lifecycle recommendations', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Page', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recommendation', 'ikon-seo' ); ?></th></tr></thead><tbody><?php foreach ( (array) $report['lifecycle'] as $row ) : ?><tr><td><a href="<?php echo esc_url( get_edit_post_link( $row['post_id'] ) ); ?>"><?php echo esc_html( $row['title'] ); ?></a></td><td><?php echo esc_html( implode( ' ', (array) $row['reasons'] ) ); ?></td><td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $row['recommended_action'] ) ) ); ?></strong><br><?php echo esc_html( ucfirst( $row['confidence'] ) ); ?> confidence</td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
		<?php
	}

	public function save_publisher_keywords() {
		$this->require_workflow_manager( 'manage publishing opportunities' );
		check_admin_referer( 'ikon_seo_save_publisher_keywords' );
		$lines = preg_split( '/[\r\n]+/', sanitize_textarea_field( wp_unslash( $_POST['keywords'] ?? '' ) ) );
		foreach ( array_slice( array_filter( array_map( 'trim', $lines ) ), 0, 250 ) as $keyword ) {
			$result = $this->publisher->save_keyword( array( 'keyword' => $keyword, 'cluster' => sanitize_text_field( wp_unslash( $_POST['cluster'] ?? '' ) ), 'intent' => sanitize_key( $_POST['intent'] ?? 'mixed' ), 'page_type' => sanitize_key( $_POST['page_type'] ?? 'article' ), 'demand_band' => sanitize_key( $_POST['demand_band'] ?? 'unknown' ), 'difficulty_band' => sanitize_key( $_POST['difficulty_band'] ?? 'unknown' ), 'business_value' => absint( $_POST['business_value'] ?? 50 ), 'source' => 'manual' ), get_current_user_id() );
			if ( is_wp_error( $result ) ) { $this->redirect_error( 'publisher-intelligence', $result->get_error_message() ); }
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=publisher-intelligence&publisher-updated=1' ) ); exit;
	}

	public function save_publisher_hub() {
		$this->require_workflow_manager( 'manage topic hubs' ); check_admin_referer( 'ikon_seo_save_publisher_hub' );
		$result = $this->publisher->save_hub( array( 'title' => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ), 'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ), 'target_audience' => sanitize_textarea_field( wp_unslash( $_POST['target_audience'] ?? '' ) ), 'pillar_post_id' => absint( $_POST['pillar_post_id'] ?? 0 ), 'keyword_ids' => sanitize_text_field( wp_unslash( $_POST['keyword_ids'] ?? '' ) ) ), get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'publisher-intelligence', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=publisher-intelligence&publisher-updated=1' ) ); exit;
	}

	public function save_publisher_item() {
		$this->require_workflow_manager( 'manage the editorial pipeline' ); check_admin_referer( 'ikon_seo_save_publisher_item' );
		$result = $this->publisher->save_item( array( 'title' => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ), 'keyword_id' => absint( $_POST['keyword_id'] ?? 0 ), 'hub_id' => absint( $_POST['hub_id'] ?? 0 ), 'stage' => sanitize_key( $_POST['stage'] ?? 'idea' ), 'author_id' => absint( $_POST['author_id'] ?? 0 ), 'reviewer_id' => absint( $_POST['reviewer_id'] ?? 0 ), 'due_at' => sanitize_text_field( wp_unslash( $_POST['due_at'] ?? '' ) ), 'target_post_id' => absint( $_POST['target_post_id'] ?? 0 ), 'source_requirements' => sanitize_textarea_field( wp_unslash( $_POST['source_requirements'] ?? '' ) ), 'originality_required' => ! empty( $_POST['originality_required'] ), 'disclosure_required' => ! empty( $_POST['disclosure_required'] ) ), get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'publisher-intelligence', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=publisher-intelligence&publisher-updated=1' ) ); exit;
	}

	public function save_publisher_contributor() {
		$this->require_workflow_manager( 'manage publisher contributors' ); check_admin_referer( 'ikon_seo_save_publisher_contributor' );
		$result = $this->publisher->save_contributor( array( 'user_id' => absint( $_POST['user_id'] ?? 0 ), 'roles' => sanitize_text_field( wp_unslash( $_POST['roles'] ?? '' ) ), 'expertise' => sanitize_textarea_field( wp_unslash( $_POST['expertise'] ?? '' ) ), 'evidence' => sanitize_textarea_field( wp_unslash( $_POST['evidence'] ?? '' ) ), 'active' => ! empty( $_POST['active'] ) ) );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'publisher-intelligence', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=publisher-intelligence&publisher-updated=1' ) ); exit;
	}

	public function generate_publisher_calendar() {
		$this->require_workflow_manager( 'generate the editorial calendar' ); check_admin_referer( 'ikon_seo_generate_publisher_calendar' );
		$result = $this->publisher->generate_calendar( absint( $_POST['weeks'] ?? 12 ), 0, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'publisher-intelligence', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=publisher-intelligence&publisher-updated=1' ) ); exit;
	}

	public function review_publisher_lifecycle() {
		$this->require_workflow_manager( 'review the content lifecycle' ); check_admin_referer( 'ikon_seo_review_publisher_lifecycle' );
		$this->publisher->review_lifecycle( 100, true, get_current_user_id() );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=publisher-intelligence&publisher-updated=1' ) ); exit;
	}

	public function evaluate_publisher_post() {
		$this->require_workflow_manager( 'run publisher quality gates' ); $item_id = absint( $_POST['item_id'] ?? 0 ); check_admin_referer( 'ikon_seo_evaluate_publisher_post_' . $item_id );
		$result = $this->publisher->evaluate_post( absint( $_POST['post_id'] ?? 0 ), $item_id, true, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'publisher-intelligence', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=publisher-intelligence&publisher-updated=1' ) ); exit;
	}

	public function export_publisher_signatures() {
		$this->require_workflow_manager( 'export portfolio signatures' ); check_admin_referer( 'ikon_seo_export_publisher_signatures' );
		$bundle = $this->publisher->export_signature_bundle( absint( Ikon_SEO_Plugin::settings()['publisher_signature_export_limit'] ?? 500 ) );
		nocache_headers(); header( 'Content-Type: application/json; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="ikon-seo-portfolio-signatures-' . gmdate( 'Ymd-His' ) . '.json"' ); echo wp_json_encode( $bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); exit;
	}

	public function import_publisher_signatures() {
		$this->require_workflow_manager( 'import portfolio signatures' ); check_admin_referer( 'ikon_seo_import_publisher_signatures' );
		$file = $_FILES['signature_file'] ?? array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $file['tmp_name'] ) || ! empty( $file['error'] ) || absint( $file['size'] ?? 0 ) > 5 * MB_IN_BYTES ) { $this->redirect_error( 'publisher-intelligence', __( 'Select a valid portfolio signature JSON file smaller than 5 MB.', 'ikon-seo' ) ); }
		$raw = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$bundle = json_decode( (string) $raw, true );
		$result = is_array( $bundle ) ? $this->publisher->import_signature_bundle( $bundle, get_current_user_id() ) : new WP_Error( 'ikon_seo_publisher_signature_json', __( 'The signature file is not valid JSON.', 'ikon-seo' ) );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'publisher-intelligence', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=publisher-intelligence&publisher-updated=1' ) ); exit;
	}


	public function refresh_local_growth() {
		$this->require_workflow_manager( 'refresh local growth evidence' );
		check_admin_referer( 'ikon_seo_refresh_local_growth' );
		$result = $this->local_growth->refresh( ! empty( $_POST['remote_refresh'] ), absint( $_POST['days'] ?? 30 ), false );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'local-growth', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=local-growth&local-growth-updated=1' ) ); exit;
	}

	public function save_local_growth_settings() {
		$this->require_workflow_manager( 'manage local growth settings' );
		check_admin_referer( 'ikon_seo_save_local_growth_settings' );
		$settings = get_option( Ikon_SEO_Plugin::OPTION_KEY, array() );
		$settings['local_growth_enabled'] = ! empty( $_POST['local_growth_enabled'] ) ? 1 : 0;
		$settings['local_review_response_days'] = max( 1, min( 30, absint( $_POST['local_review_response_days'] ?? 3 ) ) );
		$settings['local_citation_target_percent'] = max( 50, min( 100, absint( $_POST['local_citation_target_percent'] ?? 90 ) ) );
		$settings['local_prominence_stale_days'] = max( 14, min( 365, absint( $_POST['local_prominence_stale_days'] ?? 90 ) ) );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=local-growth&local-growth-updated=1' ) ); exit;
	}

	public function save_local_prominence() {
		$this->require_workflow_manager( 'store local competitor evidence' );
		check_admin_referer( 'ikon_seo_save_local_prominence' );
		$result = $this->local_growth->save_prominence(
			array(
				'competitor_name' => sanitize_text_field( wp_unslash( $_POST['competitor_name'] ?? '' ) ),
				'competitor_domain' => sanitize_text_field( wp_unslash( $_POST['competitor_domain'] ?? '' ) ),
				'query' => sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) ),
				'source_type' => sanitize_key( $_POST['source_type'] ?? 'manual' ),
				'source_url' => esc_url_raw( wp_unslash( $_POST['source_url'] ?? '' ) ),
				'evidence' => sanitize_textarea_field( wp_unslash( $_POST['evidence'] ?? '' ) ),
				'observed_at' => sanitize_text_field( wp_unslash( $_POST['observed_at'] ?? '' ) ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'local-growth', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=local-growth&local-growth-updated=1' ) ); exit;
	}

	public function update_local_review_task() {
		$this->require_workflow_manager( 'manage local review workflows' );
		$id = absint( $_POST['review_task_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_update_local_review_task_' . $id );
		$result = $this->local_growth->update_review_task(
			$id,
			array(
				'status' => sanitize_key( $_POST['status'] ?? 'open' ),
				'owner_id' => absint( $_POST['owner_id'] ?? 0 ),
				'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'local-growth', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=local-growth&local-growth-updated=1' ) ); exit;
	}


	private function render_visibility_brand() {
		$settings = Ikon_SEO_Plugin::settings();
		$report = $this->visibility_brand->report( 150 );
		$status = (array) ( $report['status'] ?? array() );
		$counts = (array) ( $report['counts'] ?? array() );
		$coverage = (array) ( $report['coverage'] ?? array() );
		$agency = Ikon_SEO_Agency::can_manage();
		?>
		<div class="ikon-seo-section-header">
			<div><h2><?php esc_html_e( 'Visibility & Brand Intelligence', 'ikon-seo' ); ?></h2><p class="description"><?php esc_html_e( 'Combine organic, local, authority, brand-mention and sampled citation evidence without pretending the dataset is complete.', 'ikon-seo' ); ?></p></div>
			<span class="ikon-seo-pill <?php echo 'strong' === ( $coverage['status'] ?? '' ) ? 'is-connected' : ''; ?>"><?php echo esc_html( absint( $coverage['score'] ?? 0 ) . '% evidence coverage' ); ?></span>
		</div>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'Evidence coverage measures data completeness, not rankings or market share. Sampled answer-engine observations vary by query, user, location and time. Outreach and public responses always require approval.', 'ikon-seo' ); ?></p></div>
		<?php if ( empty( $status['database_ready'] ) ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'The Visibility and Brand Intelligence database is not ready. Deactivate and reactivate Ikon SEO once to run the database upgrade.', 'ikon-seo' ); ?></p></div><?php return; endif; ?>

		<div class="ikon-seo-metrics">
			<div class="ikon-seo-metric"><strong><?php echo absint( $counts['observations'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Visibility observations', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $counts['brand_mentions'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Brand mentions', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $counts['unlinked_mentions'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Unlinked mentions', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $counts['own_citations_observed'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Sampled citations', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $counts['own_absences_observed'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Sampled absence gaps', 'ikon-seo' ); ?></span></div>
			<div class="ikon-seo-metric"><strong><?php echo absint( $status['competitors'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Observed competitors', 'ikon-seo' ); ?></span></div>
		</div>

		<h3><?php esc_html_e( 'Priority actions', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Area', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recommended action', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Approval', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['recommendations'] ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'No immediate visibility or brand actions were identified from the stored evidence.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) ( $report['recommendations'] ?? array() ) as $item ) : ?><tr><td><strong><?php echo esc_html( ucfirst( $item['priority'] ) ); ?></strong></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['category'] ) ) ); ?></td><td><?php echo esc_html( $item['action'] ); ?></td><td><?php echo ! empty( $item['approval_required'] ) ? esc_html__( 'Required', 'ikon-seo' ) : esc_html__( 'Research only', 'ikon-seo' ); ?></td></tr><?php endforeach; ?>
		</tbody></table>

		<?php if ( $agency ) : ?>
		<div class="ikon-seo-two-columns">
			<section class="ikon-seo-card"><h3><?php esc_html_e( 'Brand evidence settings', 'ikon-seo' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_save_visibility_brand_settings"><?php wp_nonce_field( 'ikon_seo_save_visibility_brand_settings' ); ?>
			<p><label><input type="checkbox" name="visibility_brand_enabled" value="1" <?php checked( ! empty( $settings['visibility_brand_enabled'] ) ); ?>> <?php esc_html_e( 'Enable weekly evidence snapshots', 'ikon-seo' ); ?></label></p>
			<p><label><strong><?php esc_html_e( 'Primary brand name', 'ikon-seo' ); ?></strong><br><input class="regular-text" name="brand_name" value="<?php echo esc_attr( $settings['visibility_brand_name'] ?? get_bloginfo( 'name' ) ); ?>"></label></p>
			<p><label><strong><?php esc_html_e( 'Brand aliases', 'ikon-seo' ); ?></strong><br><textarea class="large-text" rows="4" name="brand_aliases"><?php echo esc_textarea( $settings['visibility_brand_aliases'] ?? '' ); ?></textarea></label><br><span class="description"><?php esc_html_e( 'One verified alias per line.', 'ikon-seo' ); ?></span></p>
			<p><label><strong><?php esc_html_e( 'Competitors', 'ikon-seo' ); ?></strong><br><textarea class="large-text" rows="4" name="competitors"><?php echo esc_textarea( $settings['visibility_competitors'] ?? '' ); ?></textarea></label><br><span class="description"><?php esc_html_e( 'One competitor name or domain per line.', 'ikon-seo' ); ?></span></p>
			<p><label><?php esc_html_e( 'Observation stale after', 'ikon-seo' ); ?> <input type="number" min="7" max="365" name="observation_stale_days" value="<?php echo absint( $settings['visibility_observation_stale_days'] ?? 45 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></label></p>
			<p><label><?php esc_html_e( 'Mention review target', 'ikon-seo' ); ?> <input type="number" min="7" max="365" name="mention_review_days" value="<?php echo absint( $settings['visibility_mention_review_days'] ?? 30 ); ?>"> <?php esc_html_e( 'days', 'ikon-seo' ); ?></label></p>
			<p><label><?php esc_html_e( 'Minimum preferred confidence', 'ikon-seo' ); ?> <select name="min_confidence"><option value="low" <?php selected( $settings['visibility_min_confidence'] ?? 'medium', 'low' ); ?>>Low</option><option value="medium" <?php selected( $settings['visibility_min_confidence'] ?? 'medium', 'medium' ); ?>>Medium</option><option value="high" <?php selected( $settings['visibility_min_confidence'] ?? 'medium', 'high' ); ?>>High</option></select></label></p>
			<?php submit_button( __( 'Save visibility settings', 'ikon-seo' ) ); ?></form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_refresh_visibility_snapshot"><?php wp_nonce_field( 'ikon_seo_refresh_visibility_snapshot' ); ?><button class="button button-primary"><?php esc_html_e( 'Refresh combined snapshot', 'ikon-seo' ); ?></button></form>
			</section>

			<section class="ikon-seo-card"><h3><?php esc_html_e( 'Store reviewed visibility observation', 'ikon-seo' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_save_visibility_observation"><?php wp_nonce_field( 'ikon_seo_save_visibility_observation' ); ?>
			<p><label><?php esc_html_e( 'Observation type', 'ikon-seo' ); ?><br><select name="observation_type"><option value="organic_search">Organic search</option><option value="local_search">Local search</option><option value="answer_engine">Answer engine</option><option value="news">News</option><option value="editorial">Editorial</option><option value="directory">Directory</option><option value="video">Video</option><option value="social">Social</option><option value="other">Other</option></select></label></p>
			<p><label><?php esc_html_e( 'Query or question', 'ikon-seo' ); ?><br><input class="large-text" name="query"></label></p>
			<p><label><?php esc_html_e( 'Brand role', 'ikon-seo' ); ?><br><select name="brand_role"><option value="own_brand">Connected brand</option><option value="competitor">Competitor</option><option value="neutral_source">Neutral source</option></select></label></p>
			<p><label><?php esc_html_e( 'Brand or competitor name', 'ikon-seo' ); ?><br><input class="large-text" name="brand_name"></label></p>
			<p><label><?php esc_html_e( 'Competitor domain', 'ikon-seo' ); ?><br><input class="large-text" name="competitor_domain" placeholder="example.com"></label></p>
			<p><label><?php esc_html_e( 'Observed status', 'ikon-seo' ); ?><br><select name="mention_status"><option value="mentioned">Mentioned</option><option value="cited">Cited</option><option value="absent">Absent</option><option value="unclear">Unclear</option></select></label></p>
			<p><label><?php esc_html_e( 'Source URL', 'ikon-seo' ); ?><br><input class="large-text" type="url" name="source_url"></label></p>
			<p><label><?php esc_html_e( 'Cited URL', 'ikon-seo' ); ?><br><input class="large-text" type="url" name="cited_url"></label></p>
			<p><label><?php esc_html_e( 'Evidence note', 'ikon-seo' ); ?><br><textarea class="large-text" rows="4" name="evidence_excerpt"></textarea></label></p>
			<p><label><?php esc_html_e( 'Confidence', 'ikon-seo' ); ?> <select name="confidence"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></label></p>
			<?php submit_button( __( 'Store observation', 'ikon-seo' ) ); ?></form>
			</section>
		</div>

		<section class="ikon-seo-card"><h3><?php esc_html_e( 'Store reviewed brand mention', 'ikon-seo' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_save_brand_mention"><?php wp_nonce_field( 'ikon_seo_save_brand_mention' ); ?>
		<div class="ikon-seo-two-columns"><div><p><label><?php esc_html_e( 'Mention URL', 'ikon-seo' ); ?><br><input class="large-text" type="url" name="mention_url" required></label></p><p><label><?php esc_html_e( 'Mention title', 'ikon-seo' ); ?><br><input class="large-text" name="mention_title"></label></p><p><label><?php esc_html_e( 'Mention type', 'ikon-seo' ); ?><br><select name="mention_type"><option value="editorial">Editorial</option><option value="news">News</option><option value="directory">Directory</option><option value="review">Review</option><option value="resource">Resource</option><option value="forum">Forum</option><option value="social">Social</option><option value="video">Video</option><option value="podcast">Podcast</option><option value="other">Other</option></select></label></p></div><div><p><label><input type="checkbox" name="linked" value="1"> <?php esc_html_e( 'The mention links to the connected website', 'ikon-seo' ); ?></label></p><p><label><?php esc_html_e( 'Linked target URL', 'ikon-seo' ); ?><br><input class="large-text" type="url" name="target_url"></label></p><p><label><?php esc_html_e( 'Sentiment', 'ikon-seo' ); ?> <select name="sentiment"><option value="unknown">Unknown</option><option value="positive">Positive</option><option value="neutral">Neutral</option><option value="mixed">Mixed</option><option value="negative">Negative</option></select></label></p><p><label><?php esc_html_e( 'Relevance', 'ikon-seo' ); ?> <input type="number" min="0" max="100" name="relevance" value="50"></label></p></div></div>
		<p><label><?php esc_html_e( 'Short evidence note', 'ikon-seo' ); ?><br><textarea class="large-text" rows="3" name="mention_excerpt"></textarea></label></p>
		<?php submit_button( __( 'Store brand mention', 'ikon-seo' ) ); ?></form></section>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Unlinked mention and reputation opportunities', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Source', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Type', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Sentiment', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><?php if ( $agency ) : ?><th><?php esc_html_e( 'Workflow', 'ikon-seo' ); ?></th><?php endif; ?></tr></thead><tbody>
		<?php if ( empty( $report['unlinked_opportunities'] ) ) : ?><tr><td colspan="6"><?php esc_html_e( 'No unlinked mention opportunities are stored yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( array_slice( (array) ( $report['unlinked_opportunities'] ?? array() ), 0, 50 ) as $mention ) : ?><tr><td><strong><?php echo absint( $mention['opportunity_priority'] ); ?></strong></td><td><a href="<?php echo esc_url( $mention['mention_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $mention['source_domain'] ); ?></a><br><small><?php echo esc_html( $mention['mention_title'] ); ?></small></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $mention['mention_type'] ) ) ); ?></td><td><?php echo esc_html( ucfirst( $mention['sentiment'] ) ); ?></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $mention['status'] ) ) ); ?></td><?php if ( $agency ) : ?><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_update_brand_mention"><input type="hidden" name="mention_id" value="<?php echo absint( $mention['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_update_brand_mention_' . absint( $mention['id'] ) ); ?><select name="status"><option value="reviewed">Reviewed</option><option value="opportunity">Opportunity</option><option value="outreach_planned">Outreach planned</option><option value="correction_planned">Correction planned</option><option value="converted">Converted</option><option value="dismissed">Dismissed</option></select><button class="button button-small"><?php esc_html_e( 'Update', 'ikon-seo' ); ?></button></form></td><?php endif; ?></tr><?php endforeach; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Competitor visibility comparison', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Competitor', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Observations', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Mentions', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Citations', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Observed queries', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['competitor_comparison'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No competitor visibility observations are stored yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( array_slice( (array) ( $report['competitor_comparison'] ?? array() ), 0, 30 ) as $row ) : ?><tr><td><strong><?php echo esc_html( $row['competitor'] ); ?></strong></td><td><?php echo absint( $row['observations'] ); ?></td><td><?php echo absint( $row['mentions'] ); ?></td><td><?php echo absint( $row['citations'] ); ?></td><td><?php echo esc_html( implode( ', ', array_slice( (array) $row['queries'], 0, 5 ) ) ); ?></td></tr><?php endforeach; ?>
		</tbody></table>
		<?php
	}

	public function save_visibility_brand_settings() {
		$this->require_workflow_manager( 'manage visibility and brand settings' );
		check_admin_referer( 'ikon_seo_save_visibility_brand_settings' );
		$result = $this->visibility_brand->save_settings(
			array(
				'enabled' => ! empty( $_POST['visibility_brand_enabled'] ),
				'brand_name' => sanitize_text_field( wp_unslash( $_POST['brand_name'] ?? '' ) ),
				'brand_aliases' => sanitize_textarea_field( wp_unslash( $_POST['brand_aliases'] ?? '' ) ),
				'competitors' => sanitize_textarea_field( wp_unslash( $_POST['competitors'] ?? '' ) ),
				'observation_stale_days' => absint( $_POST['observation_stale_days'] ?? 45 ),
				'mention_review_days' => absint( $_POST['mention_review_days'] ?? 30 ),
				'min_confidence' => sanitize_key( $_POST['min_confidence'] ?? 'medium' ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'visibility-brand', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=visibility-brand&visibility-brand-updated=1' ) ); exit;
	}

	public function save_visibility_observation() {
		$this->require_workflow_manager( 'store visibility evidence' );
		check_admin_referer( 'ikon_seo_save_visibility_observation' );
		$result = $this->visibility_brand->save_observation(
			array(
				'observation_type' => sanitize_key( $_POST['observation_type'] ?? 'organic_search' ),
				'query' => sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) ),
				'brand_role' => sanitize_key( $_POST['brand_role'] ?? 'own_brand' ),
				'brand_name' => sanitize_text_field( wp_unslash( $_POST['brand_name'] ?? '' ) ),
				'competitor_domain' => sanitize_text_field( wp_unslash( $_POST['competitor_domain'] ?? '' ) ),
				'mention_status' => sanitize_key( $_POST['mention_status'] ?? 'mentioned' ),
				'source_url' => esc_url_raw( wp_unslash( $_POST['source_url'] ?? '' ) ),
				'cited_url' => esc_url_raw( wp_unslash( $_POST['cited_url'] ?? '' ) ),
				'evidence_excerpt' => sanitize_textarea_field( wp_unslash( $_POST['evidence_excerpt'] ?? '' ) ),
				'confidence' => sanitize_key( $_POST['confidence'] ?? 'medium' ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'visibility-brand', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=visibility-brand&visibility-brand-updated=1' ) ); exit;
	}

	public function save_brand_mention() {
		$this->require_workflow_manager( 'store brand mention evidence' );
		check_admin_referer( 'ikon_seo_save_brand_mention' );
		$result = $this->visibility_brand->save_mention(
			array(
				'mention_url' => esc_url_raw( wp_unslash( $_POST['mention_url'] ?? '' ) ),
				'mention_title' => sanitize_text_field( wp_unslash( $_POST['mention_title'] ?? '' ) ),
				'mention_type' => sanitize_key( $_POST['mention_type'] ?? 'editorial' ),
				'linked' => ! empty( $_POST['linked'] ),
				'target_url' => esc_url_raw( wp_unslash( $_POST['target_url'] ?? '' ) ),
				'sentiment' => sanitize_key( $_POST['sentiment'] ?? 'unknown' ),
				'relevance' => absint( $_POST['relevance'] ?? 50 ),
				'mention_excerpt' => sanitize_textarea_field( wp_unslash( $_POST['mention_excerpt'] ?? '' ) ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'visibility-brand', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=visibility-brand&visibility-brand-updated=1' ) ); exit;
	}

	public function update_brand_mention() {
		$this->require_workflow_manager( 'manage brand mention workflows' );
		$id = absint( $_POST['mention_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_update_brand_mention_' . $id );
		$result = $this->visibility_brand->update_mention( $id, array( 'status' => sanitize_key( $_POST['status'] ?? 'reviewed' ) ), get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'visibility-brand', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=visibility-brand&visibility-brand-updated=1' ) ); exit;
	}

	public function refresh_visibility_snapshot() {
		$this->require_workflow_manager( 'refresh visibility and brand evidence' );
		check_admin_referer( 'ikon_seo_refresh_visibility_snapshot' );
		$result = $this->visibility_brand->refresh_snapshot( get_current_user_id(), 'manual' );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'visibility-brand', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=visibility-brand&visibility-brand-updated=1' ) ); exit;
	}




	public function save_governance_settings() {
		$this->require_workflow_manager( 'manage Structured Data and Media Governance settings' );
		check_admin_referer( 'ikon_seo_save_governance_settings' );
		$result = $this->structured_media_governance->save_settings(
			array(
				'enabled'                => ! empty( $_POST['enabled'] ),
				'schema_batch_size'      => absint( $_POST['schema_batch_size'] ?? 10 ),
				'schema_stale_days'      => absint( $_POST['schema_stale_days'] ?? 30 ),
				'media_batch_size'       => absint( $_POST['media_batch_size'] ?? 50 ),
				'media_stale_days'       => absint( $_POST['media_stale_days'] ?? 30 ),
				'large_file_kb'          => absint( $_POST['large_file_kb'] ?? 500 ),
				'alt_max_chars'          => absint( $_POST['alt_max_chars'] ?? 160 ),
				'require_source_records' => ! empty( $_POST['require_source_records'] ),
				'file_hashes'            => ! empty( $_POST['file_hashes'] ),
				'retention_days'         => absint( $_POST['retention_days'] ?? 180 ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'governance', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=governance&governance-updated=1' ) ); exit;
	}

	public function run_schema_governance() {
		$this->require_workflow_manager( 'run structured-data governance' );
		check_admin_referer( 'ikon_seo_run_schema_governance' );
		$result = $this->structured_media_governance->schema_governance()->audit_batch( absint( $_POST['limit'] ?? 3 ), ! empty( $_POST['force'] ), get_current_user_id(), 'admin' );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'governance', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=governance&governance-updated=1' ) ); exit;
	}

	public function run_media_governance() {
		$this->require_workflow_manager( 'run media governance' );
		check_admin_referer( 'ikon_seo_run_media_governance' );
		$result = $this->structured_media_governance->media_governance()->audit_batch( absint( $_POST['limit'] ?? 10 ), ! empty( $_POST['force'] ), get_current_user_id(), 'admin' );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'governance', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=governance&governance-updated=1' ) ); exit;
	}

	public function audit_governance_url() {
		$this->require_workflow_manager( 'review structured data for a website URL' );
		check_admin_referer( 'ikon_seo_audit_governance_url' );
		$result = $this->structured_media_governance->schema_governance()->audit_url( esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ), get_current_user_id(), 'admin' );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'governance', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=governance&governance-updated=1' ) ); exit;
	}

	public function save_media_rights() {
		$this->require_workflow_manager( 'save a media source and rights record' );
		check_admin_referer( 'ikon_seo_save_media_rights' );
		$result = $this->structured_media_governance->media_governance()->save_rights(
			absint( $_POST['attachment_id'] ?? 0 ),
			array(
				'source_type' => sanitize_key( $_POST['source_type'] ?? 'unknown' ),
				'source_url'  => esc_url_raw( wp_unslash( $_POST['source_url'] ?? '' ) ),
				'license_name'=> sanitize_text_field( wp_unslash( $_POST['license_name'] ?? '' ) ),
				'license_url' => esc_url_raw( wp_unslash( $_POST['license_url'] ?? '' ) ),
				'creator'     => sanitize_text_field( wp_unslash( $_POST['creator'] ?? '' ) ),
				'rights_notes'=> sanitize_textarea_field( wp_unslash( $_POST['rights_notes'] ?? '' ) ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'governance', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=governance&governance-updated=1' ) ); exit;
	}

	public function cleanup_governance() {
		$this->require_workflow_manager( 'clean expired governance evidence' );
		check_admin_referer( 'ikon_seo_cleanup_governance' );
		$result = $this->structured_media_governance->cleanup();
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'governance', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=governance&governance-updated=1' ) ); exit;
	}



	public function save_ecr_settings() {
		$this->require_workflow_manager( 'manage experiment, claim and revenue settings' );
		check_admin_referer( 'ikon_seo_save_ecr_settings' );
		$result = $this->experiments_claims_revenue->save_settings(
			array(
				'enabled' => ! empty( $_POST['enabled'] ),
				'experiment_minimum_days' => absint( $_POST['experiment_minimum_days'] ?? 28 ),
				'experiment_minimum_observations' => absint( $_POST['experiment_minimum_observations'] ?? 100 ),
				'experiment_change_threshold_percent' => (float) ( $_POST['experiment_change_threshold_percent'] ?? 10 ),
				'claim_default_review_days' => absint( $_POST['claim_default_review_days'] ?? 180 ),
				'claim_high_risk_review_days' => absint( $_POST['claim_high_risk_review_days'] ?? 30 ),
				'revenue_default_currency' => sanitize_text_field( wp_unslash( $_POST['revenue_default_currency'] ?? 'USD' ) ),
				'revenue_reporting_days' => absint( $_POST['revenue_reporting_days'] ?? 30 ),
				'retention_days' => absint( $_POST['retention_days'] ?? 730 ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'experiments-claims-revenue', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=experiments-claims-revenue&ecr-updated=1' ) ); exit;
	}

	public function create_experiment() {
		$this->require_workflow_manager( 'create an SEO experiment' );
		check_admin_referer( 'ikon_seo_create_experiment' );
		$test_urls = preg_split( '/\r\n|\r|\n/', (string) wp_unslash( $_POST['test_urls'] ?? '' ) );
		$comparison_urls = preg_split( '/\r\n|\r|\n/', (string) wp_unslash( $_POST['comparison_urls'] ?? '' ) );
		$result = $this->experiments_claims_revenue->save_experiment(
			array(
				'title' => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
				'hypothesis' => sanitize_textarea_field( wp_unslash( $_POST['hypothesis'] ?? '' ) ),
				'change_type' => sanitize_key( $_POST['change_type'] ?? 'content' ),
				'status' => sanitize_key( $_POST['status'] ?? 'draft' ),
				'primary_metric' => sanitize_key( $_POST['primary_metric'] ?? 'clicks' ),
				'test_urls' => array_map( 'trim', (array) $test_urls ),
				'comparison_urls' => array_map( 'trim', (array) $comparison_urls ),
				'minimum_days' => absint( $_POST['minimum_days'] ?? 28 ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'experiments-claims-revenue', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=experiments-claims-revenue&ecr-updated=1' ) ); exit;
	}

	public function update_experiment() {
		$this->require_workflow_manager( 'update an SEO experiment' );
		$experiment_id = absint( $_POST['experiment_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_update_experiment_' . $experiment_id );
		$result = $this->experiments_claims_revenue->update_experiment(
			$experiment_id,
			array( 'status' => sanitize_key( $_POST['status'] ?? 'approved' ) ),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'experiments-claims-revenue', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=experiments-claims-revenue&ecr-updated=1' ) ); exit;
	}

	public function capture_experiment_measurement() {
		$this->require_workflow_manager( 'capture experiment evidence' );
		$experiment_id = absint( $_POST['experiment_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_capture_experiment_measurement_' . $experiment_id );
		$days = max( 7, absint( Ikon_SEO_Plugin::settings()['experiment_minimum_days'] ?? 28 ) );
		$result = $this->experiments_claims_revenue->capture_measurement(
			$experiment_id,
			array(
				'phase' => sanitize_key( $_POST['phase'] ?? 'checkpoint' ),
				'period_start' => gmdate( 'Y-m-d', strtotime( '-' . $days . ' days' ) ),
				'period_end' => gmdate( 'Y-m-d' ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'experiments-claims-revenue', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=experiments-claims-revenue&ecr-updated=1' ) ); exit;
	}

	public function save_claim_record() {
		$this->require_workflow_manager( 'save a content claim record' );
		check_admin_referer( 'ikon_seo_save_claim_record' );
		$result = $this->experiments_claims_revenue->save_claims(
			array(
				array(
					'post_id' => absint( $_POST['post_id'] ?? 0 ),
					'claim_text' => sanitize_textarea_field( wp_unslash( $_POST['claim_text'] ?? '' ) ),
					'risk_level' => sanitize_key( $_POST['risk_level'] ?? 'standard' ),
					'source_url' => esc_url_raw( wp_unslash( $_POST['source_url'] ?? '' ) ),
					'source_title' => sanitize_text_field( wp_unslash( $_POST['source_title'] ?? '' ) ),
					'source_type' => sanitize_key( $_POST['source_type'] ?? 'secondary' ),
					'status' => sanitize_key( $_POST['status'] ?? 'needs_review' ),
					'review_due_at' => sanitize_text_field( wp_unslash( $_POST['review_due_at'] ?? '' ) ),
				),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'experiments-claims-revenue', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=experiments-claims-revenue&ecr-updated=1' ) ); exit;
	}

	public function update_claim_record() {
		$this->require_workflow_manager( 'update a content claim record' );
		$claim_id = absint( $_POST['claim_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_update_claim_record_' . $claim_id );
		$result = $this->experiments_claims_revenue->update_claim(
			$claim_id,
			array( 'status' => sanitize_key( $_POST['status'] ?? 'needs_review' ) ),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'experiments-claims-revenue', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=experiments-claims-revenue&ecr-updated=1' ) ); exit;
	}

	public function save_revenue_event() {
		$this->require_workflow_manager( 'record conversion or revenue evidence' );
		check_admin_referer( 'ikon_seo_save_revenue_event' );
		$result = $this->experiments_claims_revenue->import_revenue_events(
			array(
				array(
					'event_ref' => sanitize_text_field( wp_unslash( $_POST['event_ref'] ?? '' ) ),
					'event_type' => sanitize_key( $_POST['event_type'] ?? 'lead' ),
					'occurred_at' => sanitize_text_field( wp_unslash( $_POST['occurred_at'] ?? '' ) ),
					'source' => sanitize_text_field( wp_unslash( $_POST['source'] ?? 'manual' ) ),
					'medium' => sanitize_text_field( wp_unslash( $_POST['medium'] ?? '' ) ),
					'campaign' => sanitize_text_field( wp_unslash( $_POST['campaign'] ?? '' ) ),
					'landing_url' => esc_url_raw( wp_unslash( $_POST['landing_url'] ?? '' ) ),
					'crm_stage' => sanitize_key( $_POST['crm_stage'] ?? '' ),
					'value' => (float) ( $_POST['value'] ?? 0 ),
					'currency' => sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) ),
					'qualified' => ! empty( $_POST['qualified'] ),
					'customer' => ! empty( $_POST['customer'] ),
				),
			),
			get_current_user_id(),
			'admin'
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'experiments-claims-revenue', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=experiments-claims-revenue&ecr-updated=1' ) ); exit;
	}

	public function cleanup_ecr() {
		$this->require_workflow_manager( 'clean expired experiment and attribution evidence' );
		check_admin_referer( 'ikon_seo_cleanup_ecr' );
		$result = $this->experiments_claims_revenue->cleanup();
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'experiments-claims-revenue', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=experiments-claims-revenue&ecr-updated=1' ) ); exit;
	}


	public function save_international_server_settings() {
		$this->require_workflow_manager( 'manage international and server settings' );
		check_admin_referer( 'ikon_seo_save_international_server_settings' );
		$result = $this->international_server->save_settings(
			array(
				'enabled' => ! empty( $_POST['enabled'] ),
				'audit_batch' => absint( $_POST['audit_batch'] ?? 5 ),
				'stale_days' => absint( $_POST['stale_days'] ?? 30 ),
				'locale_map' => sanitize_textarea_field( wp_unslash( $_POST['locale_map'] ?? '' ) ),
				'x_default_url' => esc_url_raw( wp_unslash( $_POST['x_default_url'] ?? '' ) ),
				'retention_days' => absint( $_POST['retention_days'] ?? 180 ),
				'max_rows' => absint( $_POST['max_rows'] ?? 20000 ),
				'slow_ms' => absint( $_POST['slow_ms'] ?? 1500 ),
				'verify_crawlers' => ! empty( $_POST['verify_crawlers'] ),
				'store_query_keys' => ! empty( $_POST['store_query_keys'] ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'international-server', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=international-server&international-server-updated=1' ) ); exit;
	}

	public function run_international_audit() {
		$this->require_workflow_manager( 'run an international page audit' );
		check_admin_referer( 'ikon_seo_run_international_audit' );
		$result = $this->international_server->audit_international_batch( absint( $_POST['limit'] ?? 3 ), get_current_user_id(), false );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'international-server', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=international-server&international-server-updated=1' ) ); exit;
	}

	public function audit_international_url() {
		$this->require_workflow_manager( 'audit an international page' );
		check_admin_referer( 'ikon_seo_audit_international_url' );
		$result = $this->international_server->audit_url( esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ), get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'international-server', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=international-server&international-server-updated=1' ) ); exit;
	}

	public function import_server_log() {
		$this->require_workflow_manager( 'import server-log evidence' );
		check_admin_referer( 'ikon_seo_import_server_log' );
		if ( empty( $_FILES['server_log']['tmp_name'] ) || ! is_uploaded_file( $_FILES['server_log']['tmp_name'] ) ) { $this->redirect_error( 'international-server', __( 'Choose a valid server-log file.', 'ikon-seo' ) ); }
		$result = $this->international_server->import_log_file( $_FILES['server_log']['tmp_name'], sanitize_file_name( wp_unslash( $_FILES['server_log']['name'] ?? 'server.log' ) ), get_current_user_id(), 'auto' );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'international-server', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=international-server&international-server-updated=1' ) ); exit;
	}

	public function cleanup_server_logs() {
		$this->require_workflow_manager( 'clean expired server evidence' );
		check_admin_referer( 'ikon_seo_cleanup_server_logs' );
		$result = $this->international_server->cleanup();
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'international-server', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=international-server&international-server-updated=1' ) ); exit;
	}


	public function save_portfolio_quality_settings() {
		$this->require_workflow_manager( 'manage Portfolio Quality settings' );
		check_admin_referer( 'ikon_seo_save_portfolio_quality_settings' );
		$result = $this->portfolio_quality_guard->save_settings(
			array(
				'enabled' => ! empty( $_POST['enabled'] ),
				'scan_batch' => absint( $_POST['scan_batch'] ?? 25 ),
				'content_threshold' => absint( $_POST['content_threshold'] ?? 72 ),
				'topic_threshold' => absint( $_POST['topic_threshold'] ?? 80 ),
				'template_threshold' => absint( $_POST['template_threshold'] ?? 90 ),
				'thin_words' => absint( $_POST['thin_words'] ?? 450 ),
				'cluster_min' => absint( $_POST['cluster_min'] ?? 4 ),
				'block_review_ready' => ! empty( $_POST['block_review_ready'] ),
				'media_hashing' => ! empty( $_POST['media_hashing'] ),
				'retention_days' => absint( $_POST['retention_days'] ?? 365 ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'portfolio-quality', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=portfolio-quality&portfolio-quality-updated=1' ) ); exit;
	}

	public function scan_portfolio_quality() {
		$this->require_workflow_manager( 'create privacy-preserving portfolio signatures' );
		check_admin_referer( 'ikon_seo_scan_portfolio_quality' );
		$result = $this->portfolio_quality_guard->scan_local( absint( $_POST['limit'] ?? 25 ), get_current_user_id(), ! empty( $_POST['force'] ) );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'portfolio-quality', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=portfolio-quality&portfolio-quality-updated=1' ) ); exit;
	}

	public function evaluate_portfolio_quality() {
		$this->require_workflow_manager( 'evaluate portfolio quality evidence' );
		check_admin_referer( 'ikon_seo_evaluate_portfolio_quality' );
		$result = $this->portfolio_quality_guard->evaluate( absint( $_POST['limit'] ?? 100 ), get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'portfolio-quality', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=portfolio-quality&portfolio-quality-updated=1' ) ); exit;
	}

	public function import_portfolio_quality() {
		$this->require_workflow_manager( 'import a portfolio quality bundle' );
		check_admin_referer( 'ikon_seo_import_portfolio_quality' );
		if ( empty( $_FILES['portfolio_bundle']['tmp_name'] ) || ! is_uploaded_file( $_FILES['portfolio_bundle']['tmp_name'] ) ) { $this->redirect_error( 'portfolio-quality', __( 'Choose a valid JSON bundle.', 'ikon-seo' ) ); }
		if ( absint( $_FILES['portfolio_bundle']['size'] ?? 0 ) > Ikon_SEO_Portfolio_Quality_Guard::MAX_BUNDLE_SIZE ) { $this->redirect_error( 'portfolio-quality', __( 'The portfolio bundle is larger than 5 MB.', 'ikon-seo' ) ); }
		$raw = file_get_contents( $_FILES['portfolio_bundle']['tmp_name'] );
		$bundle = json_decode( (string) $raw, true );
		if ( ! is_array( $bundle ) ) { $this->redirect_error( 'portfolio-quality', __( 'The portfolio bundle is not valid JSON.', 'ikon-seo' ) ); }
		$result = $this->portfolio_quality_guard->import_bundle( $bundle, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'portfolio-quality', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=portfolio-quality&portfolio-quality-updated=1' ) ); exit;
	}

	public function export_portfolio_quality() {
		$this->require_workflow_manager( 'export a portfolio quality bundle' );
		check_admin_referer( 'ikon_seo_export_portfolio_quality' );
		$bundle = $this->portfolio_quality_guard->export_bundle( absint( $_POST['limit'] ?? 500 ) );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="ikon-seo-portfolio-quality-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	public function update_portfolio_quality_finding() {
		$this->require_workflow_manager( 'review a portfolio quality finding' );
		$finding_id = absint( $_POST['finding_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_update_portfolio_quality_finding_' . $finding_id );
		$result = $this->portfolio_quality_guard->update_finding( $finding_id, sanitize_key( $_POST['status'] ?? 'reviewed' ), get_current_user_id(), sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ) );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'portfolio-quality', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=portfolio-quality&portfolio-quality-updated=1' ) ); exit;
	}

	public function cleanup_portfolio_quality() {
		$this->require_workflow_manager( 'clean expired portfolio quality evidence' );
		check_admin_referer( 'ikon_seo_cleanup_portfolio_quality' );
		$result = $this->portfolio_quality_guard->cleanup();
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'portfolio-quality', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=portfolio-quality&portfolio-quality-updated=1' ) ); exit;
	}

	public function save_indexation_settings() {
		$this->require_workflow_manager( 'manage Indexation Intelligence settings' );
		check_admin_referer( 'ikon_seo_save_indexation_settings' );
		$this->indexation->save_settings(
			array(
				'enabled' => ! empty( $_POST['enabled'] ),
				'reinspect_after_change' => ! empty( $_POST['reinspect_after_change'] ),
				'daily_budget' => absint( $_POST['daily_budget'] ?? 100 ),
				'inspection_batch' => absint( $_POST['inspection_batch'] ?? 10 ),
				'seed_batch' => absint( $_POST['seed_batch'] ?? 500 ),
				'stale_days' => absint( $_POST['stale_days'] ?? 14 ),
				'retention_days' => absint( $_POST['retention_days'] ?? 180 ),
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=indexation&indexation-updated=1' ) ); exit;
	}

	public function seed_indexation() {
		$this->require_workflow_manager( 'prepare the indexation queue' );
		check_admin_referer( 'ikon_seo_seed_indexation' );
		$result = $this->indexation->seed_inventory( absint( $_POST['limit'] ?? 500 ), ! empty( $_POST['refresh_inventory'] ), get_current_user_id(), 'admin' );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'indexation', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=indexation&indexation-updated=1' ) ); exit;
	}

	public function run_indexation_batch() {
		$this->require_workflow_manager( 'run indexation inspections' );
		check_admin_referer( 'ikon_seo_run_indexation_batch' );
		$result = $this->indexation->inspect_batch( absint( $_POST['limit'] ?? 10 ), ! empty( $_POST['force'] ), get_current_user_id(), 'admin' );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'indexation', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=indexation&indexation-updated=1' ) ); exit;
	}

	public function inspect_indexation_url() {
		$this->require_workflow_manager( 'inspect a website URL' );
		check_admin_referer( 'ikon_seo_inspect_indexation_url' );
		$result = $this->indexation->inspect_one( esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ), get_current_user_id(), 'admin' );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'indexation', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=indexation&indexation-updated=1' ) ); exit;
	}


	public function platform_hardening_action() {
		$this->require_workflow_manager( 'manage platform hardening and recovery records' );
		check_admin_referer( 'ikon_seo_platform_hardening_action' );
		$payload = array(
			'command' => sanitize_key( $_POST['command'] ?? 'read' ),
			'archive_type' => sanitize_key( $_POST['archive_type'] ?? 'configuration' ),
			'label' => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
			'archive_id' => absint( $_POST['archive_id'] ?? 0 ),
			'expected_hash' => sanitize_text_field( wp_unslash( $_POST['expected_hash'] ?? '' ) ),
		);
		$result = $this->platform_hardening->sync( $payload, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'platform-health', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=platform-health&platform-health-updated=1' ) ); exit;
	}


	public function deployment_control_action() {
		$this->require_workflow_manager( 'manage deployment and entitlement records' );
		check_admin_referer( 'ikon_seo_deployment_control_action' );
		$command = sanitize_key( wp_unslash( $_POST['command'] ?? 'read' ) );
		$payload = array(
			'command' => $command,
			'entitlement_id' => absint( $_POST['entitlement_id'] ?? 0 ),
			'release_id' => absint( $_POST['release_id'] ?? 0 ),
			'plan_id' => absint( $_POST['plan_id'] ?? 0 ),
			'expected_fingerprint' => sanitize_text_field( wp_unslash( $_POST['expected_fingerprint'] ?? '' ) ),
			'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
		);
		if ( 'create_evaluation' === $command ) {
			$payload['evaluation'] = array( 'organisation' => sanitize_text_field( wp_unslash( $_POST['organisation'] ?? '' ) ), 'days' => absint( $_POST['days'] ?? 14 ), 'environment' => Ikon_SEO_Plugin::settings()['deployment_environment'] ?? '' );
		} elseif ( 'create_plan' === $command ) {
			$payload['plan'] = array( 'environment' => Ikon_SEO_Plugin::settings()['deployment_environment'] ?? '', 'notes' => $payload['notes'] );
		}
		$result = $this->deployment_control->sync( $payload, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'deployment-control', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=deployment-control&deployment-control-updated=1' ) ); exit;
	}

	public function run_production_health() {
		$this->require_workflow_manager( 'run production health checks' );
		check_admin_referer( 'ikon_seo_run_production_health' );
		$result = $this->production_health->run( get_current_user_id(), 'admin' );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'indexation', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=indexation&indexation-updated=1' ) ); exit;
	}


	public function production_certification_action() {
		$this->require_workflow_manager( 'manage production certification and controlled rollout records' );
		check_admin_referer( 'ikon_seo_production_certification_action' );
		$command = sanitize_key( wp_unslash( $_POST['command'] ?? 'read' ) );
		$payload = array(
			'command' => $command,
			'contract_id' => absint( $_POST['contract_id'] ?? 0 ),
			'certification_id' => absint( $_POST['certification_id'] ?? 0 ),
			'rollout_id' => absint( $_POST['rollout_id'] ?? 0 ),
			'evidence_fingerprint' => sanitize_text_field( wp_unslash( $_POST['evidence_fingerprint'] ?? '' ) ),
		);
		if ( 'create_contract' === $command ) {
			$payload['contract'] = array( 'label'=>sanitize_text_field(wp_unslash($_POST['label']??'')), 'support_window_days'=>absint($_POST['support_window_days']??365), 'recovery_drill_days'=>absint($_POST['recovery_drill_days']??90), 'notes'=>sanitize_textarea_field(wp_unslash($_POST['notes']??'')) );
		} elseif ( 'create_certification' === $command ) {
			$payload['certification'] = array( 'environment'=>sanitize_key(wp_unslash($_POST['environment']??'production')) );
		} elseif ( 'record_check' === $command ) {
			$payload['check_key'] = sanitize_key( wp_unslash( $_POST['check_key'] ?? '' ) );
			$payload['check'] = array( 'status'=>sanitize_key(wp_unslash($_POST['check_status']??'pending')), 'evidence'=>sanitize_textarea_field(wp_unslash($_POST['evidence']??'')), 'notes'=>sanitize_textarea_field(wp_unslash($_POST['notes']??'')) );
		} elseif ( 'create_rollout' === $command ) {
			$site_ids = preg_split( '/[\s,]+/', sanitize_text_field( wp_unslash( $_POST['site_ids'] ?? '' ) ) );
			$payload['rollout'] = array( 'label'=>sanitize_text_field(wp_unslash($_POST['label']??'')), 'site_ids'=>array_values(array_filter(array_map('absint',(array)$site_ids))), 'channel'=>sanitize_key(wp_unslash($_POST['channel']??'stable')) );
		} elseif ( 'record_rollout_result' === $command ) {
			$payload['site_id'] = absint( $_POST['site_id'] ?? 0 );
			$payload['status'] = sanitize_key( wp_unslash( $_POST['rollout_status'] ?? 'pending' ) );
			$payload['notes'] = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
		}
		$result = $this->production_certification->sync( $payload, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'production-certification', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=production-certification&production-certification-updated=1' ) ); exit;
	}


	private function render_staging_validation() {
		$report      = $this->staging_validation->report( array( 'limit' => 15, 'include_checks' => true ) );
		$runs        = (array) ( $report['runs'] ?? array() );
		$environment = sanitize_key( $report['environment'] ?? 'production' );
		$can_run     = in_array( $environment, array( 'local', 'development', 'staging' ), true );
		$pack_run    = absint( $_GET['pack_run'] ?? 0 );
		$pack        = $pack_run ? $this->staging_validation->evidence_pack( $pack_run ) : null;
		?>
		<section class="ikon-seo-card">
			<h2><?php esc_html_e( 'Staging Validation & Evidence Runner', 'ikon-seo' ); ?></h2>
			<p><?php esc_html_e( 'Run non-destructive environment checks on the actual WordPress staging website. Evidence is locked to an exact fingerprint and requires approval by a different administrator before it can be used in Production Certification.', 'ikon-seo' ); ?></p>
			<p><strong><?php esc_html_e( 'Detected environment:', 'ikon-seo' ); ?></strong> <?php echo esc_html( ucfirst( $environment ) ); ?></p>
			<p class="description"><?php esc_html_e( 'The runner may create temporary Ikon SEO database records, cache values and files. It removes its self-test artefacts and cannot publish, install plugins or change public content.', 'ikon-seo' ); ?></p>
			<?php if ( ! $can_run ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'Automated staging validation is disabled on production environments. Clone the website to staging and set WP_ENVIRONMENT_TYPE to staging before running these checks.', 'ikon-seo' ); ?></p></div>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_staging_validation_action">
					<input type="hidden" name="command" value="start_run">
					<input type="hidden" name="environment" value="<?php echo esc_attr( $environment ); ?>">
					<?php wp_nonce_field( 'ikon_seo_staging_validation_action' ); ?>
					<button class="button button-primary"><?php esc_html_e( 'Start full staging validation', 'ikon-seo' ); ?></button>
				</form>
			<?php endif; ?>
		</section>

		<?php if ( is_wp_error( $pack ) ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $pack->get_error_message() ); ?></p></div>
		<?php elseif ( is_array( $pack ) ) : ?>
			<section class="ikon-seo-card">
				<h3><?php esc_html_e( 'Approved certification evidence pack', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Copy this privacy-minimised JSON into your controlled certification record. The pack does not approve Production Certification automatically.', 'ikon-seo' ); ?></p>
				<textarea class="large-text code" rows="20" readonly><?php echo esc_textarea( wp_json_encode( $pack, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea>
			</section>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Validation runs', 'ikon-seo' ); ?></h2>
		<table class="widefat striped">
			<thead><tr><th>ID</th><th><?php esc_html_e( 'Environment', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Score', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $runs ) : ?><tr><td colspan="6"><?php esc_html_e( 'No staging validation run exists yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
			<?php foreach ( $runs as $run ) : ?>
				<tr>
					<td><?php echo absint( $run['id'] ); ?></td>
					<td><?php echo esc_html( ucfirst( $run['environment'] ) ); ?><br><small><?php echo esc_html( 'WP ' . $run['wordpress_version'] . ' · PHP ' . $run['php_version'] ); ?></small></td>
					<td><strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $run['status'] ) ) ); ?></strong><br><small><?php echo esc_html( count( (array) $run['blocks'] ) . ' blocks · ' . count( (array) $run['warnings'] ) . ' warnings' ); ?></small></td>
					<td><?php echo absint( $run['score'] ); ?>/100</td>
					<td><code><?php echo esc_html( substr( (string) $run['evidence_fingerprint'], 0, 16 ) ); ?>…</code><br><small><?php echo esc_html( $run['updated_at'] ); ?></small></td>
					<td>
						<?php if ( 'approved' !== $run['status'] ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:6px;">
								<input type="hidden" name="action" value="ikon_seo_staging_validation_action"><input type="hidden" name="command" value="run_checks"><input type="hidden" name="run_id" value="<?php echo absint( $run['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_staging_validation_action' ); ?><button class="button"><?php esc_html_e( 'Rerun checks', 'ikon-seo' ); ?></button>
							</form>
						<?php endif; ?>
						<?php if ( 'review_ready' === $run['status'] ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
								<input type="hidden" name="action" value="ikon_seo_staging_validation_action"><input type="hidden" name="command" value="approve_run"><input type="hidden" name="run_id" value="<?php echo absint( $run['id'] ); ?>"><input type="hidden" name="evidence_fingerprint" value="<?php echo esc_attr( $run['evidence_fingerprint'] ); ?>"><?php wp_nonce_field( 'ikon_seo_staging_validation_action' ); ?><button class="button button-primary"><?php esc_html_e( 'Approve with different admin', 'ikon-seo' ); ?></button>
							</form>
						<?php elseif ( 'approved' === $run['status'] ) : ?>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ikon-seo&tab=staging-validation&pack_run=' . absint( $run['id'] ) ) ); ?>"><?php esc_html_e( 'View evidence pack', 'ikon-seo' ); ?></a>
						<?php endif; ?>
					</td>
				</tr>
				<tr><td colspan="6">
					<details><summary><?php echo esc_html( sprintf( __( 'Review %d checks', 'ikon-seo' ), count( (array) $run['checks'] ) ) ); ?></summary>
						<table class="widefat striped" style="margin-top:8px;"><thead><tr><th><?php esc_html_e( 'Check', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Critical', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence summary', 'ikon-seo' ); ?></th></tr></thead><tbody>
						<?php foreach ( (array) $run['checks'] as $check ) : ?><tr><td><?php echo esc_html( $check['label'] ); ?><br><code><?php echo esc_html( $check['check_key'] ); ?></code></td><td><?php echo ! empty( $check['critical'] ) ? esc_html__( 'Yes', 'ikon-seo' ) : esc_html__( 'Advisory', 'ikon-seo' ); ?></td><td><?php echo esc_html( ucfirst( $check['status'] ) ); ?></td><td><?php echo esc_html( $check['message'] ); ?><br><small><code><?php echo esc_html( substr( $check['evidence_hash'], 0, 16 ) ); ?>…</code></small></td></tr><?php endforeach; ?>
						</tbody></table>
					</details>
				</td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}


	public function staging_validation_action() {
		$this->require_workflow_manager( 'run and approve staging validation evidence' );
		check_admin_referer( 'ikon_seo_staging_validation_action' );
		$command = sanitize_key( wp_unslash( $_POST['command'] ?? 'read' ) );
		$payload = array(
			'command'              => $command,
			'run_id'               => absint( $_POST['run_id'] ?? 0 ),
			'evidence_fingerprint' => sanitize_text_field( wp_unslash( $_POST['evidence_fingerprint'] ?? '' ) ),
		);
		if ( 'start_run' === $command ) {
			$payload['run'] = array( 'environment' => sanitize_key( wp_unslash( $_POST['environment'] ?? 'staging' ) ) );
		}
		$result = $this->staging_validation->sync( $payload, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'staging-validation', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=staging-validation&staging-validation-updated=1' ) ); exit;
	}

	private function render_closed_loop() {
		$report   = $this->closed_loop->report( 200 );
		$status   = (array) ( $report['status'] ?? array() );
		$summary  = (array) ( $report['summary'] ?? array() );
		$health   = (array) ( $report['system_health'] ?? array() );
		$settings = Ikon_SEO_Plugin::settings();
		$agency   = Ikon_SEO_Agency::can_manage();
		?>
		<section class="ikon-seo-card">
			<h2><?php esc_html_e( 'Closed-Loop SEO Operating Plan', 'ikon-seo' ); ?></h2>
			<p><?php esc_html_e( 'One prioritised plan combining page evidence, search performance, technical findings, content lifecycle, authority, local growth and brand visibility. Approved recommendations can be measured against a stored baseline after completion.', 'ikon-seo' ); ?></p>
			<div class="ikon-seo-stat-grid">
				<div><strong><?php echo absint( $summary['recommendations'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Recommendations', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo absint( $status['due_measurements'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Due measurements', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo absint( $summary['outcomes']['succeeded'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Successful outcomes', 'ikon-seo' ); ?></span></div>
				<div><strong><?php echo ! empty( $health['healthy'] ) ? 'Ready' : 'Review'; ?></strong><span><?php esc_html_e( 'System health', 'ikon-seo' ); ?></span></div>
			</div>
			<p><span class="ikon-seo-pill <?php echo ! empty( $settings['closed_loop_safe_mode'] ) ? 'is-failed' : 'is-connected'; ?>"><?php echo esc_html( ! empty( $settings['closed_loop_safe_mode'] ) ? 'Safe mode enabled' : 'Scheduled measurement enabled' ); ?></span></p>
		</section>

		<?php if ( $agency ) : ?>
		<div class="ikon-seo-two-columns">
			<section class="ikon-seo-card"><h3><?php esc_html_e( 'Operating-plan settings', 'ikon-seo' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_save_closed_loop_settings"><?php wp_nonce_field( 'ikon_seo_save_closed_loop_settings' ); ?>
			<p><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['closed_loop_enabled'] ) ); ?>> <?php esc_html_e( 'Enable scheduled outcome measurements', 'ikon-seo' ); ?></label></p>
			<p><label><input type="checkbox" name="safe_mode" value="1" <?php checked( ! empty( $settings['closed_loop_safe_mode'] ) ); ?>> <?php esc_html_e( 'Safe mode: pause scheduled measurements', 'ikon-seo' ); ?></label></p>
			<p><label><?php esc_html_e( 'Measurement windows in days', 'ikon-seo' ); ?><br><input class="regular-text" name="measurement_windows" value="<?php echo esc_attr( $settings['closed_loop_measurement_windows'] ?? '14,28,60,90' ); ?>"></label></p>
			<p><label><?php esc_html_e( 'Measurements per daily batch', 'ikon-seo' ); ?><br><input type="number" min="1" max="50" name="measurement_batch" value="<?php echo absint( $settings['closed_loop_measurement_batch'] ?? 5 ); ?>"></label></p>
			<?php submit_button( __( 'Save settings', 'ikon-seo' ) ); ?></form></section>
			<section class="ikon-seo-card"><h3><?php esc_html_e( 'Refresh and recovery', 'ikon-seo' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_refresh_closed_loop_plan"><?php wp_nonce_field( 'ikon_seo_refresh_closed_loop_plan' ); ?><p><label><input type="checkbox" name="refresh_sources" value="1"> <?php esc_html_e( 'Refresh available evidence before rebuilding the plan', 'ikon-seo' ); ?></label></p><button class="button button-primary"><?php esc_html_e( 'Refresh operating plan', 'ikon-seo' ); ?></button></form>
			<hr><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_run_closed_loop_measurements"><?php wp_nonce_field( 'ikon_seo_run_closed_loop_measurements' ); ?><button class="button"><?php esc_html_e( 'Run due measurements now', 'ikon-seo' ); ?></button></form>
			<hr><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_create_closed_loop_checkpoint"><?php wp_nonce_field( 'ikon_seo_create_closed_loop_checkpoint' ); ?><p><input class="regular-text" name="reason" value="Before configuration changes"></p><button class="button"><?php esc_html_e( 'Create recovery checkpoint', 'ikon-seo' ); ?></button></form>
			</section>
		</div>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Prioritised recommendations', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Priority', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recommendation', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Evidence', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><?php if ( $agency ) : ?><th><?php esc_html_e( 'Action', 'ikon-seo' ); ?></th><?php endif; ?></tr></thead><tbody>
		<?php if ( empty( $report['recommendations'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'Refresh the operating plan to consolidate the current evidence.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( array_slice( (array) ( $report['recommendations'] ?? array() ), 0, 100 ) as $item ) : ?>
		<tr><td><strong><?php echo absint( $item['priority'] ); ?></strong><br><small><?php echo esc_html( ucfirst( $item['confidence'] ) ); ?> confidence</small></td><td><strong><?php echo esc_html( $item['title'] ); ?></strong><br><small><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['source_module'] ) ) ); ?> · <?php echo esc_html( ucwords( str_replace( '_', ' ', $item['category'] ) ) ); ?></small><?php if ( ! empty( $item['target_url'] ) ) : ?><br><a href="<?php echo esc_url( $item['target_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View target', 'ikon-seo' ); ?></a><?php endif; ?></td><td><?php echo esc_html( $item['rationale'] ); ?><br><small><?php echo esc_html( $item['action']['recommended_action'] ?? '' ); ?></small></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['status'] ) ) ); ?></td>
		<?php if ( $agency ) : ?><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ikon_seo_update_closed_loop_recommendation"><input type="hidden" name="recommendation_id" value="<?php echo absint( $item['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_update_closed_loop_recommendation_' . absint( $item['id'] ) ); ?><select name="command"><option value="approve">Approve</option><option value="start">Start</option><option value="complete">Complete and monitor</option><option value="measure">Measure now</option><option value="dismiss">Dismiss</option></select><input class="small-text" type="number" min="0" max="365" name="window_days" placeholder="Days"><br><textarea name="notes" rows="2" placeholder="Optional note"></textarea><br><button class="button button-small"><?php esc_html_e( 'Apply', 'ikon-seo' ); ?></button></form></td><?php endif; ?></tr>
		<?php endforeach; ?></tbody></table>

		<h3><?php esc_html_e( 'Outcome measurements', 'ikon-seo' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Recommendation', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Window', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Outcome', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Summary', 'ikon-seo' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $report['recent_outcomes'] ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'No outcome windows have been scheduled yet.', 'ikon-seo' ); ?></td></tr><?php endif; ?>
		<?php foreach ( array_slice( (array) ( $report['recent_outcomes'] ?? array() ), 0, 50 ) as $outcome ) : ?><tr><td><?php echo esc_html( $outcome['title'] ); ?></td><td><?php echo absint( $outcome['window_days'] ); ?> days<br><small><?php echo esc_html( $outcome['measured_at'] ?: $outcome['due_at'] ); ?></small></td><td><?php echo esc_html( ucfirst( $outcome['outcome'] ) ); ?><br><small><?php echo esc_html( ucfirst( $outcome['confidence'] ) ); ?> confidence</small></td><td><?php echo esc_html( $outcome['summary'] ); ?></td></tr><?php endforeach; ?>
		</tbody></table>

		<?php if ( $agency ) : ?><h3><?php esc_html_e( 'Recovery checkpoints', 'ikon-seo' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Created', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Reason', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Version', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Status', 'ikon-seo' ); ?></th><th><?php esc_html_e( 'Recovery', 'ikon-seo' ); ?></th></tr></thead><tbody><?php if ( empty( $report['checkpoints'] ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No recovery checkpoints are stored.', 'ikon-seo' ); ?></td></tr><?php endif; ?><?php foreach ( (array) ( $report['checkpoints'] ?? array() ) as $checkpoint ) : ?><tr><td><?php echo esc_html( $checkpoint['created_at'] ); ?></td><td><?php echo esc_html( $checkpoint['reason'] ); ?></td><td><?php echo esc_html( $checkpoint['component_version'] ); ?></td><td><?php echo esc_html( ucfirst( $checkpoint['status'] ) ); ?></td><td><?php if ( 'available' === $checkpoint['status'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Restore non-secret Ikon SEO configuration from this checkpoint?');"><input type="hidden" name="action" value="ikon_seo_restore_closed_loop_checkpoint"><input type="hidden" name="checkpoint_id" value="<?php echo absint( $checkpoint['id'] ); ?>"><?php wp_nonce_field( 'ikon_seo_restore_closed_loop_checkpoint_' . absint( $checkpoint['id'] ) ); ?><button class="button button-small"><?php esc_html_e( 'Restore configuration', 'ikon-seo' ); ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
		<?php
	}

	public function save_closed_loop_settings() {
		$this->require_workflow_manager( 'manage operating-plan settings' );
		check_admin_referer( 'ikon_seo_save_closed_loop_settings' );
		$settings = Ikon_SEO_Plugin::settings();
		$settings['closed_loop_enabled'] = ! empty( $_POST['enabled'] ) ? 1 : 0;
		$settings['closed_loop_safe_mode'] = ! empty( $_POST['safe_mode'] ) ? 1 : 0;
		$settings['closed_loop_measurement_batch'] = max( 1, min( 50, absint( $_POST['measurement_batch'] ?? 5 ) ) );
		$windows = array_values( array_unique( array_filter( array_map( 'absint', preg_split( '/[^0-9]+/', sanitize_text_field( wp_unslash( $_POST['measurement_windows'] ?? '14,28,60,90' ) ) ) ) ) ) );
		$windows = array_values( array_filter( $windows, function( $days ) { return $days >= 1 && $days <= 365; } ) );
		sort( $windows );
		$settings['closed_loop_measurement_windows'] = implode( ',', $windows ?: array( 14, 28, 60, 90 ) );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=closed-loop&closed-loop-updated=1' ) ); exit;
	}

	public function refresh_closed_loop_plan() {
		$this->require_workflow_manager( 'refresh the operating plan' );
		check_admin_referer( 'ikon_seo_refresh_closed_loop_plan' );
		$result = $this->closed_loop->refresh_plan( ! empty( $_POST['refresh_sources'] ), 200, true, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'closed-loop', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=closed-loop&closed-loop-updated=1' ) ); exit;
	}

	public function update_closed_loop_recommendation() {
		$this->require_workflow_manager( 'manage an operating-plan recommendation' );
		$id = absint( $_POST['recommendation_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_update_closed_loop_recommendation_' . $id );
		$command = sanitize_key( $_POST['command'] ?? '' );
		$notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
		if ( 'approve' === $command ) { $result = $this->closed_loop->approve( $id, get_current_user_id() ); }
		elseif ( 'start' === $command ) { $result = $this->closed_loop->start( $id, get_current_user_id() ); }
		elseif ( 'complete' === $command ) { $result = $this->closed_loop->complete( $id, $notes, get_current_user_id() ); }
		elseif ( 'measure' === $command ) { $result = $this->closed_loop->measure( $id, absint( $_POST['window_days'] ?? 0 ), true, get_current_user_id() ); }
		else { $result = $this->closed_loop->dismiss( $id, $notes, get_current_user_id() ); }
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'closed-loop', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=closed-loop&closed-loop-updated=1' ) ); exit;
	}

	public function run_closed_loop_measurements() {
		$this->require_workflow_manager( 'run outcome measurements' );
		check_admin_referer( 'ikon_seo_run_closed_loop_measurements' );
		$result = $this->closed_loop->run_due_measurements( absint( Ikon_SEO_Plugin::settings()['closed_loop_measurement_batch'] ?? 5 ), get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'closed-loop', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=closed-loop&closed-loop-updated=1' ) ); exit;
	}

	public function create_closed_loop_checkpoint() {
		$this->require_workflow_manager( 'create a recovery checkpoint' );
		check_admin_referer( 'ikon_seo_create_closed_loop_checkpoint' );
		$result = $this->closed_loop->create_checkpoint( sanitize_text_field( wp_unslash( $_POST['reason'] ?? 'Manual checkpoint' ) ), get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'closed-loop', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=closed-loop&closed-loop-updated=1' ) ); exit;
	}

	public function restore_closed_loop_checkpoint() {
		$this->require_workflow_manager( 'restore a recovery checkpoint' );
		$id = absint( $_POST['checkpoint_id'] ?? 0 );
		check_admin_referer( 'ikon_seo_restore_closed_loop_checkpoint_' . $id );
		$result = $this->closed_loop->restore_checkpoint( $id, get_current_user_id() );
		if ( is_wp_error( $result ) ) { $this->redirect_error( 'closed-loop', $result->get_error_message() ); }
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=closed-loop&closed-loop-updated=1' ) ); exit;
	}


	private function redirect_error( $tab, $message ) {
		if ( 'editorial-review' === $tab && ! current_user_can( 'manage_options' ) ) {
			$url = admin_url( 'edit.php?page=ikon-seo-editorial' );
		} else {
			$url = admin_url( 'admin.php?page=ikon-seo&tab=' . sanitize_key( $tab ) );
		}
		wp_safe_redirect( add_query_arg( 'ikon-error', sanitize_text_field( $message ), $url ) );
		exit;
	}

	private function require_workflow_manager( $action ) {
		if ( ! current_user_can( 'manage_options' ) || ! Ikon_SEO_Agency::can_manage() ) {
			wp_die( esc_html( sprintf( __( 'You do not have permission to %s.', 'ikon-seo' ), $action ) ) );
		}
	}

}
