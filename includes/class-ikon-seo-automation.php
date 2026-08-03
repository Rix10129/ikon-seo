<?php

defined( 'ABSPATH' ) || exit;

/**
 * Repeatable, approval-first SEO workflows and safe scheduled evidence refreshes.
 *
 * This engine deliberately limits unattended execution to read-only analysis.
 * Draft creation, live edits, redirects, publishing, outreach and external
 * profile changes remain manual or approval-controlled.
 */
final class Ikon_SEO_Automation {
	const RUNNER_HOOK   = 'ikon_seo_workflow_runner';
	const DAILY_HOOK    = 'ikon_seo_workflow_daily_briefing';
	const WEEKLY_HOOK   = 'ikon_seo_workflow_weekly_briefing';
	const LOCK_KEY      = 'ikon_seo_workflow_runner_lock';
	const BRIEFING_KEY  = 'ikon_seo_workflow_latest_briefing';

	private $profile;
	private $strategy;
	private $inventory;
	private $crawler;
	private $diagnostics;
	private $search_intelligence;
	private $technical;
	private $analytics;
	private $local_growth;
	private $monitor;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Strategy $strategy,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Crawler $crawler,
		Ikon_SEO_Diagnostics $diagnostics,
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Technical_Intelligence $technical,
		Ikon_SEO_Analytics $analytics,
		Ikon_SEO_Local_Growth $local_growth,
		Ikon_SEO_Monitor $monitor,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->profile             = $profile;
		$this->strategy            = $strategy;
		$this->inventory           = $inventory;
		$this->crawler             = $crawler;
		$this->diagnostics         = $diagnostics;
		$this->search_intelligence = $search_intelligence;
		$this->technical           = $technical;
		$this->analytics           = $analytics;
		$this->local_growth        = $local_growth;
		$this->monitor             = $monitor;
		$this->history             = $history;
		$this->logger              = $logger;
	}

	public function register_hooks() {
		add_action( self::RUNNER_HOOK, array( $this, 'scheduled_runner' ) );
		add_action( self::DAILY_HOOK, array( $this, 'scheduled_daily_briefing' ) );
		add_action( self::WEEKLY_HOOK, array( $this, 'scheduled_weekly_briefing' ) );
	}

	public function scheduled_runner() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['workflow_automation_enabled'] ) ) {
			return;
		}
		$this->run_safe_tasks( max( 1, min( 10, absint( $settings['workflow_runner_batch'] ?? 3 ) ) ) );
	}

	public function scheduled_daily_briefing() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( ! empty( $settings['workflow_daily_briefing_enabled'] ) ) {
			$this->generate_briefing( 'daily', true );
		}
	}

	public function scheduled_weekly_briefing() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( ! empty( $settings['workflow_weekly_briefing_enabled'] ) ) {
			$this->generate_briefing( 'weekly', true );
		}
	}

	public function workflows_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_workflows';
	}

	public function tasks_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_workflow_tasks';
	}

	public function runs_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_workflow_runs';
	}

	public function templates() {
		$shared_foundation = array(
			array( 'key' => 'strategy_check', 'title' => 'Confirm website strategy', 'description' => 'Review the active operating mode, goals, conversions, quality standards and automation boundaries.', 'category' => 'strategy', 'priority' => 100, 'automation_action' => 'strategy_check', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 0, 'depends_on' => array() ),
			array( 'key' => 'inventory_scan', 'title' => 'Refresh website inventory', 'description' => 'Refresh the WordPress page inventory before planning work.', 'category' => 'technical', 'priority' => 95, 'automation_action' => 'inventory_scan', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 0, 'depends_on' => array( 'strategy_check' ) ),
			array( 'key' => 'evidence_crawl', 'title' => 'Run evidence crawl', 'description' => 'Collect current page-level technical and on-page evidence in controlled batches.', 'category' => 'technical', 'priority' => 92, 'automation_action' => 'evidence_crawl', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 1, 'depends_on' => array( 'inventory_scan' ) ),
			array( 'key' => 'technical_refresh', 'title' => 'Refresh technical intelligence', 'description' => 'Refresh URL discovery, sitemap parity and the internal-link graph.', 'category' => 'technical', 'priority' => 90, 'automation_action' => 'technical_refresh', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 1, 'depends_on' => array( 'evidence_crawl' ) ),
			array( 'key' => 'search_refresh', 'title' => 'Refresh search intelligence', 'description' => 'Refresh Search Console page-query evidence when a property is connected.', 'category' => 'measurement', 'priority' => 88, 'automation_action' => 'search_intelligence_refresh', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 1, 'depends_on' => array( 'inventory_scan' ) ),
			array( 'key' => 'analytics_refresh', 'title' => 'Refresh analytics evidence', 'description' => 'Refresh landing-page engagement and conversion evidence when Analytics is connected.', 'category' => 'measurement', 'priority' => 84, 'automation_action' => 'analytics_refresh', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 1, 'depends_on' => array( 'inventory_scan' ) ),
			array( 'key' => 'local_growth_refresh', 'title' => 'Refresh local growth evidence', 'description' => 'Refresh local profile alignment, service-area validation, citations, review workflow and conversion snapshots when applicable.', 'category' => 'local', 'priority' => 85, 'automation_action' => 'local_growth_refresh', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 2, 'depends_on' => array( 'inventory_scan' ) ),
			array( 'key' => 'diagnostics_refresh', 'title' => 'Refresh page diagnoses', 'description' => 'Combine the latest technical, search, analytics and authority evidence into page priorities.', 'category' => 'analysis', 'priority' => 86, 'automation_action' => 'diagnostics_refresh', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 2, 'depends_on' => array( 'evidence_crawl', 'technical_refresh' ) ),
	);

		$templates = array(
			'local_growth' => array(
				'label' => 'Local Business Growth',
				'mode' => 'local_business',
				'description' => 'A repeatable local-business workflow focused on service coverage, local proof and qualified leads.',
				'tasks' => array_merge(
					$shared_foundation,
					array(
						array( 'key' => 'local_competitor_review', 'title' => 'Review local competitors and intent', 'description' => 'Research current organic and local competitors, services, proof and conversion patterns.', 'category' => 'research', 'priority' => 82, 'automation_action' => '', 'safe_level' => 'manual', 'approval_required' => 0, 'offset_days' => 3, 'depends_on' => array( 'diagnostics_refresh', 'local_growth_refresh' ) ),
						array( 'key' => 'service_gap_plan', 'title' => 'Approve service and location plan', 'description' => 'Review proposed service, supporting and genuine location pages before drafting.', 'category' => 'planning', 'priority' => 80, 'automation_action' => '', 'safe_level' => 'manual', 'approval_required' => 1, 'offset_days' => 5, 'depends_on' => array( 'local_competitor_review' ) ),
						array( 'key' => 'draft_review', 'title' => 'Review structured drafts', 'description' => 'Review claims, local proof, calls to action, metadata, internal links and schema before publication.', 'category' => 'approval', 'priority' => 78, 'automation_action' => '', 'safe_level' => 'draft', 'approval_required' => 1, 'offset_days' => 10, 'depends_on' => array( 'service_gap_plan' ) ),
						array( 'key' => 'manual_publish', 'title' => 'Publish approved pages manually', 'description' => 'Publish only after quality, legal, business-claim and client review.', 'category' => 'publication', 'priority' => 70, 'automation_action' => '', 'safe_level' => 'live', 'approval_required' => 1, 'offset_days' => 14, 'depends_on' => array( 'draft_review' ) ),
						array( 'key' => 'outcome_review', 'title' => 'Measure leads and visibility', 'description' => 'Compare search visibility, landing-page engagement and qualified lead outcomes after implementation.', 'category' => 'measurement', 'priority' => 72, 'automation_action' => 'monitor_refresh', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 42, 'depends_on' => array( 'manual_publish' ) ),
					)
				),
			),
			'editorial_growth' => array(
				'label' => 'Editorial / Blog Growth',
				'mode' => 'editorial',
				'description' => 'A controlled editorial workflow for topic hubs, original articles, refreshes and monetization.',
				'tasks' => array_merge(
					$shared_foundation,
					array(
						array( 'key' => 'topic_gap_research', 'title' => 'Research topic and intent gaps', 'description' => 'Review search demand, current result formats, topic hubs and portfolio overlap before briefing.', 'category' => 'research', 'priority' => 84, 'automation_action' => '', 'safe_level' => 'manual', 'approval_required' => 0, 'offset_days' => 3, 'depends_on' => array( 'diagnostics_refresh' ) ),
						array( 'key' => 'editorial_brief_approval', 'title' => 'Approve editorial briefs', 'description' => 'Approve intent, originality, evidence, authorship, sources and conversion requirements.', 'category' => 'planning', 'priority' => 82, 'automation_action' => '', 'safe_level' => 'manual', 'approval_required' => 1, 'offset_days' => 5, 'depends_on' => array( 'topic_gap_research' ) ),
						array( 'key' => 'article_quality_review', 'title' => 'Run article quality review', 'description' => 'Review originality, source quality, expertise, internal links, disclosure and usefulness.', 'category' => 'approval', 'priority' => 80, 'automation_action' => '', 'safe_level' => 'draft', 'approval_required' => 1, 'offset_days' => 12, 'depends_on' => array( 'editorial_brief_approval' ) ),
						array( 'key' => 'manual_publish', 'title' => 'Publish approved articles manually', 'description' => 'Publish only after editorial and factual approval.', 'category' => 'publication', 'priority' => 70, 'automation_action' => '', 'safe_level' => 'live', 'approval_required' => 1, 'offset_days' => 16, 'depends_on' => array( 'article_quality_review' ) ),
						array( 'key' => 'content_refresh_review', 'title' => 'Measure and refresh content', 'description' => 'Review visibility, engagement, revenue and content decay after publication.', 'category' => 'measurement', 'priority' => 74, 'automation_action' => 'monitor_refresh', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 45, 'depends_on' => array( 'manual_publish' ) ),
					)
				),
			),
			'ecommerce_growth' => array(
				'label' => 'Ecommerce Growth',
				'mode' => 'ecommerce',
				'description' => 'A commercial workflow for product, category, trust, feed and transaction evidence.',
				'tasks' => array_merge(
					$shared_foundation,
					array(
						array( 'key' => 'category_gap_research', 'title' => 'Review product and category opportunities', 'description' => 'Research commercial intent, category coverage, product differentiation and competitor trust patterns.', 'category' => 'research', 'priority' => 84, 'automation_action' => '', 'safe_level' => 'manual', 'approval_required' => 0, 'offset_days' => 3, 'depends_on' => array( 'diagnostics_refresh' ) ),
						array( 'key' => 'commerce_plan_approval', 'title' => 'Approve commercial page plan', 'description' => 'Approve product, category and supporting-content priorities before drafting.', 'category' => 'planning', 'priority' => 82, 'automation_action' => '', 'safe_level' => 'manual', 'approval_required' => 1, 'offset_days' => 5, 'depends_on' => array( 'category_gap_research' ) ),
						array( 'key' => 'commerce_quality_review', 'title' => 'Review products, schema and trust', 'description' => 'Review price, availability, product facts, policies, identifiers, schema and conversion paths.', 'category' => 'approval', 'priority' => 80, 'automation_action' => '', 'safe_level' => 'draft', 'approval_required' => 1, 'offset_days' => 12, 'depends_on' => array( 'commerce_plan_approval' ) ),
						array( 'key' => 'manual_publish', 'title' => 'Publish approved commercial changes manually', 'description' => 'Release only approved product and category changes.', 'category' => 'publication', 'priority' => 70, 'automation_action' => '', 'safe_level' => 'live', 'approval_required' => 1, 'offset_days' => 16, 'depends_on' => array( 'commerce_quality_review' ) ),
						array( 'key' => 'revenue_review', 'title' => 'Measure transactions and search outcomes', 'description' => 'Compare visibility, engagement, key events, revenue and product outcomes.', 'category' => 'measurement', 'priority' => 74, 'automation_action' => 'monitor_refresh', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 45, 'depends_on' => array( 'manual_publish' ) ),
					)
				),
			),
			'hybrid_growth' => array(
				'label' => 'Hybrid Growth',
				'mode' => 'hybrid',
				'description' => 'A balanced workflow for websites combining commercial pages and editorial growth.',
				'tasks' => array_merge(
					$shared_foundation,
					array(
						array( 'key' => 'mixed_opportunity_research', 'title' => 'Review commercial and editorial opportunities', 'description' => 'Separate commercial, local and informational intent before choosing target pages.', 'category' => 'research', 'priority' => 84, 'automation_action' => '', 'safe_level' => 'manual', 'approval_required' => 0, 'offset_days' => 3, 'depends_on' => array( 'diagnostics_refresh' ) ),
						array( 'key' => 'mixed_plan_approval', 'title' => 'Approve the balanced growth plan', 'description' => 'Approve page types, ownership and publishing capacity before drafting.', 'category' => 'planning', 'priority' => 82, 'automation_action' => '', 'safe_level' => 'manual', 'approval_required' => 1, 'offset_days' => 5, 'depends_on' => array( 'mixed_opportunity_research' ) ),
						array( 'key' => 'mixed_quality_review', 'title' => 'Review drafts against the correct intent', 'description' => 'Apply commercial or editorial quality gates according to page purpose.', 'category' => 'approval', 'priority' => 80, 'automation_action' => '', 'safe_level' => 'draft', 'approval_required' => 1, 'offset_days' => 12, 'depends_on' => array( 'mixed_plan_approval' ) ),
						array( 'key' => 'manual_publish', 'title' => 'Publish approved changes manually', 'description' => 'Publish only after the appropriate business or editorial approval.', 'category' => 'publication', 'priority' => 70, 'automation_action' => '', 'safe_level' => 'live', 'approval_required' => 1, 'offset_days' => 16, 'depends_on' => array( 'mixed_quality_review' ) ),
						array( 'key' => 'mixed_outcome_review', 'title' => 'Measure business and editorial outcomes', 'description' => 'Compare leads, revenue, search visibility and engagement according to page type.', 'category' => 'measurement', 'priority' => 74, 'automation_action' => 'monitor_refresh', 'safe_level' => 'read', 'approval_required' => 0, 'offset_days' => 45, 'depends_on' => array( 'manual_publish' ) ),
					)
				),
			),
		);

		return $templates;
	}

	public function recommended_template() {
		$strategy = $this->strategy->get();
		$mode     = sanitize_key( $strategy['mode'] ?? 'local_business' );
		$map      = array(
			'local_business' => 'local_growth',
			'editorial'      => 'editorial_growth',
			'ecommerce'      => 'ecommerce_growth',
			'hybrid'         => 'hybrid_growth',
		);
		return $map[ $mode ] ?? 'local_growth';
	}

	public function create_workflow( $template_key, array $args = array() ) {
		global $wpdb;
		$template_key = sanitize_key( $template_key );
		$templates    = $this->templates();
		if ( empty( $templates[ $template_key ] ) ) {
			return new WP_Error( 'ikon_seo_workflow_template', __( 'Select a supported workflow template.', 'ikon-seo' ) );
		}
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_workflow_tables', __( 'Workflow tables are not available. Reactivate or update Ikon SEO.', 'ikon-seo' ) );
		}

		$profile_id = $this->profile_id();
		$existing   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->workflows_table()} WHERE profile_id=%s AND template_key=%s AND status IN ('active','paused') ORDER BY id DESC LIMIT 1",
				$profile_id,
				$template_key
			)
		);
		if ( $existing && empty( $args['allow_duplicate'] ) ) {
			return new WP_Error( 'ikon_seo_workflow_duplicate', __( 'An active workflow already uses this template.', 'ikon-seo' ), array( 'workflow_id' => absint( $existing ) ) );
		}

		$template   = $templates[ $template_key ];
		$start_date = sanitize_text_field( $args['start_date'] ?? gmdate( 'Y-m-d' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
			$start_date = gmdate( 'Y-m-d' );
		}
		$owner_id = absint( $args['owner_id'] ?? get_current_user_id() );
		if ( $owner_id && ! get_user_by( 'id', $owner_id ) ) {
			$owner_id = 0;
		}
		$now  = current_time( 'mysql', true );
		$name = sanitize_text_field( $args['name'] ?? $template['label'] );
		$wpdb->insert(
			$this->workflows_table(),
			array(
				'profile_id'       => $profile_id,
				'template_key'     => $template_key,
				'name'             => substr( $name, 0, 255 ),
				'website_mode'     => sanitize_key( $template['mode'] ),
				'status'           => 'active',
				'owner_id'         => $owner_id,
				'progress_percent' => 0,
				'start_date'       => $start_date,
				'created_by'       => absint( $args['created_by'] ?? get_current_user_id() ),
				'created_at'       => $now,
				'updated_at'       => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s' )
		);
		$workflow_id = absint( $wpdb->insert_id );
		if ( ! $workflow_id ) {
			return new WP_Error( 'ikon_seo_workflow_insert', __( 'The workflow could not be created.', 'ikon-seo' ) );
		}

		$key_to_id = array();
		foreach ( $template['tasks'] as $task ) {
			$due_at = gmdate( 'Y-m-d H:i:s', strtotime( $start_date . ' +' . absint( $task['offset_days'] ) . ' days 09:00:00 UTC' ) );
			$status = empty( $task['depends_on'] ) ? ( ! empty( $task['approval_required'] ) ? 'pending_approval' : 'ready' ) : 'pending';
			$wpdb->insert(
				$this->tasks_table(),
				array(
					'workflow_id'       => $workflow_id,
					'task_key'          => sanitize_key( $task['key'] ),
					'title'             => sanitize_text_field( $task['title'] ),
					'description'       => sanitize_textarea_field( $task['description'] ),
					'category'          => sanitize_key( $task['category'] ),
					'status'            => $status,
					'priority'          => max( 0, min( 100, absint( $task['priority'] ) ) ),
					'owner_id'          => $owner_id,
					'due_at'            => $due_at,
					'dependency_ids'    => '[]',
					'automation_action' => sanitize_key( $task['automation_action'] ),
					'safe_level'        => sanitize_key( $task['safe_level'] ),
					'approval_required' => ! empty( $task['approval_required'] ) ? 1 : 0,
					'next_run_at'       => $due_at,
					'attempts'          => 0,
					'max_attempts'      => max( 1, min( 10, absint( Ikon_SEO_Plugin::settings()['workflow_retry_limit'] ?? 3 ) ) ),
					'payload_json'      => wp_json_encode( array() ),
					'result_json'       => wp_json_encode( array() ),
					'created_at'        => $now,
					'updated_at'        => $now,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
			);
			$key_to_id[ sanitize_key( $task['key'] ) ] = absint( $wpdb->insert_id );
		}

		foreach ( $template['tasks'] as $task ) {
			$task_key = sanitize_key( $task['key'] );
			$deps     = array();
			foreach ( (array) $task['depends_on'] as $dependency_key ) {
				if ( ! empty( $key_to_id[ $dependency_key ] ) ) {
					$deps[] = absint( $key_to_id[ $dependency_key ] );
				}
			}
			$wpdb->update(
				$this->tasks_table(),
				array( 'dependency_ids' => wp_json_encode( $deps ) ),
				array( 'id' => absint( $key_to_id[ $task_key ] ) ),
				array( '%s' ),
				array( '%d' )
			);
		}

		$this->history->add(
			array(
				'category' => 'workflow',
				'status'   => 'open',
				'title'    => 'Workflow created: ' . $name,
				'summary'  => sprintf( '%d approval-first tasks were created from the %s template.', count( $template['tasks'] ), $template['label'] ),
				'details'  => array( 'workflow_id' => $workflow_id, 'template' => $template_key, 'mode' => $template['mode'] ),
			),
			'workflow',
			absint( $args['created_by'] ?? get_current_user_id() )
		);
		$this->logger->log( 'workflow_create', 'success', 'Created workflow ' . $name, null, $workflow_id, array( 'template' => $template_key ) );
		return $this->get_workflow( $workflow_id, true );
	}

	public function summary( $limit = 50 ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) {
			return array( 'ready' => false, 'workflows' => array(), 'tasks' => array(), 'counts' => array(), 'templates' => $this->templates() );
		}
		$profile_id = $this->profile_id();
		$limit      = max( 1, min( 200, absint( $limit ) ) );
		$workflows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->workflows_table()} WHERE profile_id=%s ORDER BY FIELD(status,'active','paused','completed','archived'), updated_at DESC LIMIT %d",
				$profile_id,
				$limit
			),
			ARRAY_A
		);
		$counts = array_fill_keys( array( 'pending', 'ready', 'in_progress', 'pending_approval', 'approved', 'completed', 'failed', 'blocked', 'skipped' ), 0 );
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.status, COUNT(*) total FROM {$this->tasks_table()} t INNER JOIN {$this->workflows_table()} w ON w.id=t.workflow_id WHERE w.profile_id=%s GROUP BY t.status",
				$profile_id
			),
			ARRAY_A
		);
		foreach ( $rows ?: array() as $row ) {
			if ( isset( $counts[ $row['status'] ] ) ) {
				$counts[ $row['status'] ] = absint( $row['total'] );
			}
		}
		$tasks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*, w.name workflow_name, w.template_key, w.website_mode FROM {$this->tasks_table()} t INNER JOIN {$this->workflows_table()} w ON w.id=t.workflow_id WHERE w.profile_id=%s AND t.status NOT IN ('completed','skipped') ORDER BY CASE WHEN t.due_at IS NOT NULL AND t.due_at < UTC_TIMESTAMP() THEN 0 ELSE 1 END, t.priority DESC, t.due_at ASC, t.id ASC LIMIT %d",
				$profile_id,
				$limit
			),
			ARRAY_A
		);
		$overdue = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->tasks_table()} t INNER JOIN {$this->workflows_table()} w ON w.id=t.workflow_id WHERE w.profile_id=%s AND t.status NOT IN ('completed','skipped') AND t.due_at IS NOT NULL AND t.due_at < UTC_TIMESTAMP()",
					$profile_id
				)
			)
		);
		$latest_briefing = get_option( self::BRIEFING_KEY, array() );
		return array(
			'ready'                => true,
			'profile_id'           => $profile_id,
			'recommended_template' => $this->recommended_template(),
			'templates'            => $this->public_templates(),
			'counts'               => $counts,
			'overdue'              => $overdue,
			'workflows'            => array_map( array( $this, 'public_workflow' ), $workflows ?: array() ),
			'tasks'                => array_map( array( $this, 'public_task' ), $tasks ?: array() ),
			'scheduler'            => $this->scheduler_status(),
			'latest_briefing'      => is_array( $latest_briefing ) ? $latest_briefing : array(),
			'safety'               => $this->safety_policy(),
			'generated_at'         => current_time( 'mysql', true ),
		);
	}

	public function get_workflow( $id, $include_tasks = false ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->workflows_table()} WHERE id=%d AND profile_id=%s", absint( $id ), $this->profile_id() ),
			ARRAY_A
		);
		if ( ! $row ) {
			return null;
		}
		$result = $this->public_workflow( $row );
		if ( $include_tasks ) {
			$result['tasks'] = $this->tasks( absint( $id ), array(), 500 );
		}
		return $result;
	}

	public function tasks( $workflow_id = 0, array $statuses = array(), $limit = 100 ) {
		global $wpdb;
		$where  = array( 'w.profile_id=%s' );
		$params = array( $this->profile_id() );
		if ( $workflow_id ) {
			$where[]  = 't.workflow_id=%d';
			$params[] = absint( $workflow_id );
		}
		$clean_statuses = array_values( array_intersect( array_map( 'sanitize_key', $statuses ), $this->statuses() ) );
		if ( $clean_statuses ) {
			$placeholders = implode( ',', array_fill( 0, count( $clean_statuses ), '%s' ) );
			$where[]      = "t.status IN ({$placeholders})";
			$params       = array_merge( $params, $clean_statuses );
		}
		$params[] = max( 1, min( 1000, absint( $limit ) ) );
		$sql = "SELECT t.*, w.name workflow_name, w.template_key, w.website_mode FROM {$this->tasks_table()} t INNER JOIN {$this->workflows_table()} w ON w.id=t.workflow_id WHERE " . implode( ' AND ', $where ) . ' ORDER BY t.priority DESC, t.due_at ASC, t.id ASC LIMIT %d';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		return array_map( array( $this, 'public_task' ), $rows ?: array() );
	}

	public function update_task( $task_id, array $changes, $user_id = 0 ) {
		global $wpdb;
		$task = $this->get_task( $task_id );
		if ( ! $task ) {
			return new WP_Error( 'ikon_seo_workflow_task_missing', __( 'The workflow task was not found.', 'ikon-seo' ) );
		}
		$data    = array( 'updated_at' => current_time( 'mysql', true ) );
		$formats = array( '%s' );
		if ( array_key_exists( 'status', $changes ) ) {
			$status = sanitize_key( $changes['status'] );
			if ( ! in_array( $status, $this->statuses(), true ) ) {
				return new WP_Error( 'ikon_seo_workflow_task_status', __( 'The requested task status is not supported.', 'ikon-seo' ) );
			}
			if ( in_array( $status, array( 'completed', 'skipped' ), true ) && ! $this->dependencies_complete( $task ) ) {
				return new WP_Error( 'ikon_seo_workflow_dependency', __( 'Complete or skip the task dependencies first.', 'ikon-seo' ) );
			}
			$data['status'] = $status;
			$formats[]      = '%s';
			$data['completed_at'] = 'completed' === $status ? current_time( 'mysql', true ) : null;
			$formats[] = null;
		}
		if ( array_key_exists( 'owner_id', $changes ) ) {
			$owner_id = absint( $changes['owner_id'] );
			if ( $owner_id && ! get_user_by( 'id', $owner_id ) ) {
				return new WP_Error( 'ikon_seo_workflow_owner', __( 'Select an existing WordPress user as task owner.', 'ikon-seo' ) );
			}
			$data['owner_id'] = $owner_id;
			$formats[]        = '%d';
		}
		if ( array_key_exists( 'due_at', $changes ) ) {
			$due_at = sanitize_text_field( $changes['due_at'] );
			if ( $due_at && false === strtotime( $due_at . ' UTC' ) ) {
				return new WP_Error( 'ikon_seo_workflow_due', __( 'Enter a valid task due date.', 'ikon-seo' ) );
			}
			$data['due_at']      = $due_at ? gmdate( 'Y-m-d H:i:s', strtotime( $due_at . ' UTC' ) ) : null;
			$data['next_run_at'] = $data['due_at'];
			$formats[]           = null;
			$formats[]           = null;
		}
		if ( array_key_exists( 'notes', $changes ) ) {
			$payload          = $this->decode_json( $task['payload_json'] ?? '' );
			$payload['notes'] = sanitize_textarea_field( $changes['notes'] );
			$data['payload_json'] = wp_json_encode( $payload );
			$formats[] = '%s';
		}
		$updated = $wpdb->update( $this->tasks_table(), $data, array( 'id' => absint( $task_id ) ), $formats, array( '%d' ) );
		if ( false === $updated ) {
			return new WP_Error( 'ikon_seo_workflow_task_update', __( 'The workflow task could not be updated.', 'ikon-seo' ) );
		}
		$this->refresh_task_readiness( absint( $task['workflow_id'] ) );
		$this->update_workflow_progress( absint( $task['workflow_id'] ) );
		$updated_task = $this->get_task( $task_id );
		$this->history->add(
			array(
				'category' => 'task',
				'status'   => in_array( $updated_task['status'], array( 'completed', 'skipped' ), true ) ? 'completed' : 'open',
				'title'    => 'Workflow task updated: ' . $updated_task['title'],
				'summary'  => 'Task status is now ' . str_replace( '_', ' ', $updated_task['status'] ) . '.',
				'details'  => array( 'workflow_id' => absint( $task['workflow_id'] ), 'task_id' => absint( $task_id ), 'changes' => $changes ),
			),
			'workflow',
			absint( $user_id )
		);
		return $this->public_task( $updated_task );
	}

	public function approve_task( $task_id, $user_id = 0 ) {
		$task = $this->get_task( $task_id );
		if ( ! $task ) {
			return new WP_Error( 'ikon_seo_workflow_task_missing', __( 'The workflow task was not found.', 'ikon-seo' ) );
		}
		if ( empty( $task['approval_required'] ) ) {
			return new WP_Error( 'ikon_seo_workflow_approval_not_required', __( 'This task does not require approval.', 'ikon-seo' ) );
		}
		if ( ! $this->dependencies_complete( $task ) ) {
			return new WP_Error( 'ikon_seo_workflow_dependency', __( 'Complete the task dependencies before approval.', 'ikon-seo' ) );
		}
		global $wpdb;
		$wpdb->update(
			$this->tasks_table(),
			array( 'status' => 'approved', 'approved_by' => absint( $user_id ), 'approved_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => absint( $task_id ) ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
		$this->history->add(
			array(
				'category' => 'approval',
				'status'   => 'completed',
				'title'    => 'Workflow task approved: ' . $task['title'],
				'summary'  => 'The approval was recorded. This does not publish or change live content.',
				'details'  => array( 'workflow_id' => absint( $task['workflow_id'] ), 'task_id' => absint( $task_id ) ),
			),
			'workflow',
			absint( $user_id )
		);
		return $this->public_task( $this->get_task( $task_id ) );
	}

	public function run_safe_tasks( $limit = 3, $force = false ) {
		global $wpdb;
		$limit = max( 1, min( 10, absint( $limit ) ) );
		if ( get_transient( self::LOCK_KEY ) && ! $force ) {
			return array( 'locked' => true, 'processed' => 0, 'results' => array() );
		}
		set_transient( self::LOCK_KEY, 1, 15 * MINUTE_IN_SECONDS );
		$this->refresh_all_readiness();
		$where_due = $force ? '1=1' : "(t.next_run_at IS NULL OR t.next_run_at <= UTC_TIMESTAMP())";
		$tasks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.* FROM {$this->tasks_table()} t INNER JOIN {$this->workflows_table()} w ON w.id=t.workflow_id WHERE w.profile_id=%s AND w.status='active' AND t.status IN ('ready','approved') AND t.automation_action<>'' AND t.safe_level='read' AND {$where_due} ORDER BY t.priority DESC, t.next_run_at ASC, t.id ASC LIMIT %d",
				$this->profile_id(),
				$limit
			),
			ARRAY_A
		);
		$results = array();
		foreach ( $tasks ?: array() as $task ) {
			$results[] = $this->run_task( $task );
		}
		delete_transient( self::LOCK_KEY );
		return array( 'locked' => false, 'processed' => count( $results ), 'results' => $results, 'generated_at' => current_time( 'mysql', true ) );
	}

	public function generate_briefing( $period = 'daily', $store_history = true ) {
		global $wpdb;
		$period     = 'weekly' === sanitize_key( $period ) ? 'weekly' : 'daily';
		$profile_id = $this->profile_id();
		$window     = 'weekly' === $period ? 14 : 3;
		$tasks      = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*, w.name workflow_name FROM {$this->tasks_table()} t INNER JOIN {$this->workflows_table()} w ON w.id=t.workflow_id WHERE w.profile_id=%s AND t.status NOT IN ('completed','skipped') ORDER BY CASE WHEN t.due_at IS NOT NULL AND t.due_at < UTC_TIMESTAMP() THEN 0 ELSE 1 END, t.priority DESC, t.due_at ASC LIMIT 20",
				$profile_id
			),
			ARRAY_A
		);
		$completed = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->tasks_table()} t INNER JOIN {$this->workflows_table()} w ON w.id=t.workflow_id WHERE w.profile_id=%s AND t.status='completed' AND t.completed_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
					$profile_id,
					$window
				)
			)
		);
		$failed = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->tasks_table()} t INNER JOIN {$this->workflows_table()} w ON w.id=t.workflow_id WHERE w.profile_id=%s AND t.status='failed'",
					$profile_id
				)
			)
		);
		$approval = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->tasks_table()} t INNER JOIN {$this->workflows_table()} w ON w.id=t.workflow_id WHERE w.profile_id=%s AND t.status='pending_approval'",
					$profile_id
				)
			)
		);
		$overdue = array();
		$next    = array();
		foreach ( $tasks ?: array() as $task ) {
			$item = $this->public_task( $task );
			if ( ! empty( $item['overdue'] ) ) {
				$overdue[] = $item;
			} elseif ( count( $next ) < 8 ) {
				$next[] = $item;
			}
		}
		$briefing = array(
			'period'             => $period,
			'completed_recently' => $completed,
			'pending_approval'   => $approval,
			'failed_tasks'       => $failed,
			'overdue_tasks'      => array_slice( $overdue, 0, 8 ),
			'next_actions'       => array_slice( $next, 0, 8 ),
			'summary'            => sprintf( '%d completed recently, %d awaiting approval, %d failed and %d overdue.', $completed, $approval, $failed, count( $overdue ) ),
			'generated_at'       => current_time( 'mysql', true ),
		);
		update_option( self::BRIEFING_KEY, $briefing, false );
		if ( $store_history ) {
			$this->history->add(
				array(
					'category' => 'briefing',
					'status'   => $failed || $overdue || $approval ? 'open' : 'completed',
					'title'    => ucfirst( $period ) . ' SEO workflow briefing',
					'summary'  => $briefing['summary'],
					'details'  => array( 'pending_approval' => $approval, 'failed_tasks' => $failed, 'overdue_count' => count( $overdue ), 'next_actions' => array_map( function( $task ) { return $task['title']; }, $briefing['next_actions'] ) ),
				),
				'workflow',
				0
			);
		}
		return $briefing;
	}

	public function save_settings( array $input ) {
		$settings = get_option( Ikon_SEO_Plugin::OPTION_KEY, array() );
		$settings['workflow_automation_enabled']      = ! empty( $input['workflow_automation_enabled'] ) ? 1 : 0;
		$settings['workflow_daily_briefing_enabled']  = ! empty( $input['workflow_daily_briefing_enabled'] ) ? 1 : 0;
		$settings['workflow_weekly_briefing_enabled'] = ! empty( $input['workflow_weekly_briefing_enabled'] ) ? 1 : 0;
		$settings['workflow_runner_batch']             = max( 1, min( 10, absint( $input['workflow_runner_batch'] ?? 3 ) ) );
		$settings['workflow_retry_limit']              = max( 1, min( 10, absint( $input['workflow_retry_limit'] ?? 3 ) ) );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		return true;
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		$result  = array();
		if ( 'create_workflow' === $command ) {
			$result['created_workflow'] = $this->create_workflow(
				sanitize_key( $payload['template'] ?? $this->recommended_template() ),
				array(
					'name'            => sanitize_text_field( $payload['name'] ?? '' ),
					'owner_id'        => absint( $payload['owner_id'] ?? $user_id ),
					'start_date'      => sanitize_text_field( $payload['start_date'] ?? '' ),
					'allow_duplicate' => ! empty( $payload['allow_duplicate'] ),
					'created_by'      => absint( $user_id ),
				)
			);
			if ( is_wp_error( $result['created_workflow'] ) ) {
				return $result['created_workflow'];
			}
		} elseif ( 'update_task' === $command ) {
			$result['updated_task'] = $this->update_task( absint( $payload['task_id'] ?? 0 ), (array) ( $payload['changes'] ?? array() ), $user_id );
			if ( is_wp_error( $result['updated_task'] ) ) {
				return $result['updated_task'];
			}
		} elseif ( 'approve_task' === $command ) {
			$result['approved_task'] = $this->approve_task( absint( $payload['task_id'] ?? 0 ), $user_id );
			if ( is_wp_error( $result['approved_task'] ) ) {
				return $result['approved_task'];
			}
		} elseif ( 'run_safe_tasks' === $command ) {
			$result['run'] = $this->run_safe_tasks( absint( $payload['limit'] ?? 3 ), true );
		} elseif ( 'generate_briefing' === $command ) {
			$result['briefing'] = $this->generate_briefing( sanitize_key( $payload['period'] ?? 'daily' ), true );
		} elseif ( 'read' !== $command ) {
			return new WP_Error( 'ikon_seo_workflow_command', __( 'The requested workflow command is not supported.', 'ikon-seo' ) );
		}
		$result['state'] = $this->summary( absint( $payload['limit'] ?? 50 ) );
		return $result;
	}

	private function run_task( array $task ) {
		global $wpdb;
		$task_id = absint( $task['id'] );
		if ( ! $this->dependencies_complete( $task ) ) {
			return array( 'task_id' => $task_id, 'status' => 'blocked', 'message' => 'Dependencies are incomplete.' );
		}
		$action = sanitize_key( $task['automation_action'] );
		if ( ! $action || 'read' !== sanitize_key( $task['safe_level'] ) ) {
			return array( 'task_id' => $task_id, 'status' => 'manual', 'message' => 'This task requires manual or approved work.' );
		}
		$wpdb->update(
			$this->tasks_table(),
			array( 'status' => 'in_progress', 'attempts' => absint( $task['attempts'] ) + 1, 'last_run_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $task_id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
		$run_id = wp_generate_uuid4();
		$wpdb->insert(
			$this->runs_table(),
			array( 'task_id' => $task_id, 'run_uuid' => $run_id, 'status' => 'running', 'attempt' => absint( $task['attempts'] ) + 1, 'started_at' => current_time( 'mysql', true ), 'result_json' => wp_json_encode( array() ) ),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);
		$run_row_id = absint( $wpdb->insert_id );
		$result = $this->execute_read_action( $action );
		if ( is_wp_error( $result ) ) {
			$attempts = absint( $task['attempts'] ) + 1;
			$max      = max( 1, absint( $task['max_attempts'] ) );
			$failed   = $attempts >= $max;
			$delay    = min( DAY_IN_SECONDS, 15 * MINUTE_IN_SECONDS * pow( 2, max( 0, $attempts - 1 ) ) );
			$wpdb->update(
				$this->tasks_table(),
				array(
					'status'      => $failed ? 'failed' : 'ready',
					'last_error'  => sanitize_textarea_field( $result->get_error_message() ),
					'next_run_at' => $failed ? null : gmdate( 'Y-m-d H:i:s', time() + $delay ),
					'updated_at'  => current_time( 'mysql', true ),
				),
				array( 'id' => $task_id ),
				array( '%s', '%s', null, '%s' ),
				array( '%d' )
			);
			$wpdb->update(
				$this->runs_table(),
				array( 'status' => 'failed', 'message' => sanitize_textarea_field( $result->get_error_message() ), 'completed_at' => current_time( 'mysql', true ) ),
				array( 'id' => $run_row_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
			$this->logger->log( 'workflow_task', 'failed', $result->get_error_message(), null, $task_id, array( 'action' => $action, 'attempt' => $attempts ) );
			return array( 'task_id' => $task_id, 'status' => $failed ? 'failed' : 'retry_scheduled', 'message' => $result->get_error_message() );
		}

		$public_result = $this->summarize_result( $result );
		$wpdb->update(
			$this->tasks_table(),
			array( 'status' => 'completed', 'last_error' => '', 'result_json' => wp_json_encode( $public_result ), 'completed_at' => current_time( 'mysql', true ), 'next_run_at' => null, 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $task_id ),
			array( '%s', '%s', '%s', '%s', null, '%s' ),
			array( '%d' )
		);
		$wpdb->update(
			$this->runs_table(),
			array( 'status' => 'completed', 'message' => 'Read-only automation completed.', 'result_json' => wp_json_encode( $public_result ), 'completed_at' => current_time( 'mysql', true ) ),
			array( 'id' => $run_row_id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		$this->refresh_task_readiness( absint( $task['workflow_id'] ) );
		$this->update_workflow_progress( absint( $task['workflow_id'] ) );
		$this->history->add(
			array(
				'category' => 'task',
				'status'   => 'completed',
				'title'    => 'Automated task completed: ' . sanitize_text_field( $task['title'] ),
				'summary'  => 'A read-only workflow task completed. No live page or external profile was changed.',
				'details'  => array( 'workflow_id' => absint( $task['workflow_id'] ), 'task_id' => $task_id, 'action' => $action, 'result' => $public_result ),
			),
			'workflow',
			0
		);
		$this->logger->log( 'workflow_task', 'success', 'Read-only automation completed.', null, $task_id, array( 'action' => $action ) );
		return array( 'task_id' => $task_id, 'status' => 'completed', 'result' => $public_result );
	}

	private function execute_read_action( $action ) {
		$settings = Ikon_SEO_Plugin::settings();
		switch ( $action ) {
			case 'strategy_check':
				return $this->strategy->get();
			case 'inventory_scan':
				return $this->inventory->scan( true );
			case 'evidence_crawl':
				return $this->crawler->crawl_batch( max( 1, min( 25, absint( $settings['crawler_batch_size'] ?? 10 ) ) ), true );
			case 'technical_refresh':
				$discovery = $this->technical->refresh_discovery();
				if ( is_wp_error( $discovery ) ) {
					return $discovery;
				}
				$checks = $this->technical->check_urls( max( 5, min( 50, absint( $settings['technical_check_batch_size'] ?? 20 ) ) ) );
				return is_wp_error( $checks ) ? $checks : array( 'discovery' => $discovery, 'checks' => $checks );
			case 'search_intelligence_refresh':
				$status = $this->search_intelligence->status();
				if ( empty( $status['connected'] ) || empty( $status['property'] ) ) {
					return array( 'status' => 'not_connected', 'completed' => true, 'note' => 'Search Console refresh skipped because no property is connected.' );
				}
				return $this->search_intelligence->refresh( absint( $settings['search_intelligence_days'] ?? 28 ), absint( $settings['search_intelligence_max_rows'] ?? 50000 ) );
			case 'analytics_refresh':
				$status = $this->analytics->status();
				if ( empty( $status['connected'] ) || empty( $status['property'] ) ) {
					return array( 'status' => 'not_connected', 'completed' => true, 'note' => 'Analytics refresh skipped because no property is connected.' );
				}
				return $this->analytics->report( 28, true );
			case 'local_growth_refresh':
				$strategy = $this->strategy->get();
				if ( ! in_array( $strategy['mode'], array( 'local_business', 'hybrid' ), true ) ) {
					return array( 'status' => 'not_applicable', 'completed' => true, 'note' => 'Local growth refresh skipped because the active website mode is not local or hybrid.' );
				}
				return $this->local_growth->refresh( true, absint( $settings['local_conversion_days'] ?? 30 ), true );
			case 'diagnostics_refresh':
				return $this->diagnostics->site_report( true, true );
			case 'monitor_refresh':
				return $this->monitor->run_daily( true );
		}
		return new WP_Error( 'ikon_seo_workflow_action', __( 'This workflow action cannot run unattended.', 'ikon-seo' ) );
	}

	private function refresh_all_readiness() {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$this->workflows_table()} WHERE profile_id=%s AND status='active'", $this->profile_id() ) );
		foreach ( $ids ?: array() as $id ) {
			$this->refresh_task_readiness( absint( $id ) );
			$this->update_workflow_progress( absint( $id ) );
		}
	}

	private function refresh_task_readiness( $workflow_id ) {
		global $wpdb;
		$tasks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->tasks_table()} WHERE workflow_id=%d ORDER BY id ASC", absint( $workflow_id ) ), ARRAY_A );
		foreach ( $tasks ?: array() as $task ) {
			if ( ! in_array( $task['status'], array( 'pending', 'blocked' ), true ) ) {
				continue;
			}
			$status = $this->dependencies_complete( $task ) ? ( ! empty( $task['approval_required'] ) ? 'pending_approval' : 'ready' ) : 'pending';
			if ( $status !== $task['status'] ) {
				$wpdb->update( $this->tasks_table(), array( 'status' => $status, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $task['id'] ) ), array( '%s', '%s' ), array( '%d' ) );
			}
		}
	}

	private function dependencies_complete( array $task ) {
		global $wpdb;
		$ids = array_filter( array_map( 'absint', (array) $this->decode_json( $task['dependency_ids'] ?? '' ) ) );
		if ( ! $ids ) {
			return true;
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,status FROM {$this->tasks_table()} WHERE id IN ({$placeholders})", $ids ), ARRAY_A );
		if ( count( $rows ) !== count( $ids ) ) {
			return false;
		}
		foreach ( $rows as $row ) {
			if ( ! in_array( $row['status'], array( 'completed', 'skipped' ), true ) ) {
				return false;
			}
		}
		return true;
	}

	private function update_workflow_progress( $workflow_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) total, SUM(CASE WHEN status IN ('completed','skipped') THEN 1 ELSE 0 END) done FROM {$this->tasks_table()} WHERE workflow_id=%d", absint( $workflow_id ) ), ARRAY_A );
		$total    = max( 0, absint( $row['total'] ?? 0 ) );
		$done     = max( 0, absint( $row['done'] ?? 0 ) );
		$progress = $total ? (int) round( ( $done / $total ) * 100 ) : 0;
		$status   = $total && $done >= $total ? 'completed' : null;
		$data     = array( 'progress_percent' => $progress, 'updated_at' => current_time( 'mysql', true ) );
		$formats  = array( '%d', '%s' );
		if ( $status ) {
			$data['status']       = $status;
			$data['completed_at'] = current_time( 'mysql', true );
			$formats[] = '%s';
			$formats[] = '%s';
		}
		$wpdb->update( $this->workflows_table(), $data, array( 'id' => absint( $workflow_id ) ), $formats, array( '%d' ) );
	}

	private function get_task( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT t.* FROM {$this->tasks_table()} t INNER JOIN {$this->workflows_table()} w ON w.id=t.workflow_id WHERE t.id=%d AND w.profile_id=%s",
				absint( $id ),
				$this->profile_id()
			),
			ARRAY_A
		);
	}

	private function public_workflow( array $row ) {
		return array(
			'id'               => absint( $row['id'] ?? 0 ),
			'template_key'     => sanitize_key( $row['template_key'] ?? '' ),
			'name'             => sanitize_text_field( $row['name'] ?? '' ),
			'website_mode'     => sanitize_key( $row['website_mode'] ?? '' ),
			'status'           => sanitize_key( $row['status'] ?? '' ),
			'owner_id'         => absint( $row['owner_id'] ?? 0 ),
			'progress_percent' => absint( $row['progress_percent'] ?? 0 ),
			'start_date'       => sanitize_text_field( $row['start_date'] ?? '' ),
			'created_at'       => sanitize_text_field( $row['created_at'] ?? '' ),
			'updated_at'       => sanitize_text_field( $row['updated_at'] ?? '' ),
			'completed_at'     => sanitize_text_field( $row['completed_at'] ?? '' ),
		);
	}

	private function public_task( array $row ) {
		$owner = ! empty( $row['owner_id'] ) ? get_user_by( 'id', absint( $row['owner_id'] ) ) : null;
		$due   = sanitize_text_field( $row['due_at'] ?? '' );
		return array(
			'id'                 => absint( $row['id'] ?? 0 ),
			'workflow_id'        => absint( $row['workflow_id'] ?? 0 ),
			'workflow_name'      => sanitize_text_field( $row['workflow_name'] ?? '' ),
			'task_key'           => sanitize_key( $row['task_key'] ?? '' ),
			'title'              => sanitize_text_field( $row['title'] ?? '' ),
			'description'        => sanitize_textarea_field( $row['description'] ?? '' ),
			'category'           => sanitize_key( $row['category'] ?? '' ),
			'status'             => sanitize_key( $row['status'] ?? '' ),
			'priority'           => absint( $row['priority'] ?? 0 ),
			'owner_id'           => absint( $row['owner_id'] ?? 0 ),
			'owner_name'         => $owner ? $owner->display_name : '',
			'due_at'             => $due,
			'overdue'            => $due && strtotime( $due . ' UTC' ) < time() && ! in_array( sanitize_key( $row['status'] ?? '' ), array( 'completed', 'skipped' ), true ),
			'dependency_ids'     => array_map( 'absint', (array) $this->decode_json( $row['dependency_ids'] ?? '' ) ),
			'automation_action'  => sanitize_key( $row['automation_action'] ?? '' ),
			'safe_level'         => sanitize_key( $row['safe_level'] ?? '' ),
			'approval_required'  => ! empty( $row['approval_required'] ),
			'approved_by'        => absint( $row['approved_by'] ?? 0 ),
			'approved_at'        => sanitize_text_field( $row['approved_at'] ?? '' ),
			'attempts'           => absint( $row['attempts'] ?? 0 ),
			'max_attempts'       => absint( $row['max_attempts'] ?? 0 ),
			'last_error'         => sanitize_textarea_field( $row['last_error'] ?? '' ),
			'last_run_at'        => sanitize_text_field( $row['last_run_at'] ?? '' ),
			'next_run_at'        => sanitize_text_field( $row['next_run_at'] ?? '' ),
			'payload'            => $this->decode_json( $row['payload_json'] ?? '' ),
			'result'             => $this->decode_json( $row['result_json'] ?? '' ),
			'created_at'         => sanitize_text_field( $row['created_at'] ?? '' ),
			'updated_at'         => sanitize_text_field( $row['updated_at'] ?? '' ),
			'completed_at'       => sanitize_text_field( $row['completed_at'] ?? '' ),
		);
	}

	private function public_templates() {
		$output = array();
		foreach ( $this->templates() as $key => $template ) {
			$output[ $key ] = array(
				'key'         => $key,
				'label'       => $template['label'],
				'mode'        => $template['mode'],
				'description' => $template['description'],
				'task_count'  => count( $template['tasks'] ),
			);
		}
		return $output;
	}

	private function scheduler_status() {
		$settings = Ikon_SEO_Plugin::settings();
		return array(
			'enabled'                  => ! empty( $settings['workflow_automation_enabled'] ),
			'daily_briefing_enabled'   => ! empty( $settings['workflow_daily_briefing_enabled'] ),
			'weekly_briefing_enabled'  => ! empty( $settings['workflow_weekly_briefing_enabled'] ),
			'runner_batch'             => absint( $settings['workflow_runner_batch'] ?? 3 ),
			'retry_limit'              => absint( $settings['workflow_retry_limit'] ?? 3 ),
			'next_runner'              => $this->format_timestamp( wp_next_scheduled( self::RUNNER_HOOK ) ),
			'next_daily_briefing'      => $this->format_timestamp( wp_next_scheduled( self::DAILY_HOOK ) ),
			'next_weekly_briefing'     => $this->format_timestamp( wp_next_scheduled( self::WEEKLY_HOOK ) ),
			'wp_cron_note'             => __( 'WordPress scheduled events depend on site traffic unless the hosting provider connects WP-Cron to a real server scheduler.', 'ikon-seo' ),
		);
	}

	private function safety_policy() {
		$strategy = $this->strategy->get();
		return array(
			'automation_level' => sanitize_key( $strategy['automation_level'] ?? 'drafts_only' ),
			'risk_tolerance'   => sanitize_key( $strategy['risk_tolerance'] ?? 'balanced' ),
			'automatic'        => array( 'read-only crawling', 'evidence refreshes', 'diagnostic refreshes', 'briefings and alerts' ),
			'approval_required'=> array( 'draft acceptance', 'live edits', 'publishing', 'redirects', 'canonical or indexing changes', 'external outreach', 'business-profile changes' ),
		);
	}

	private function summarize_result( $result ) {
		if ( is_array( $result ) ) {
			$summary = array();
			foreach ( array( 'ok', 'status', 'generated_at', 'last_sync', 'processed', 'crawled', 'stored', 'total', 'rows', 'pages', 'queries', 'clusters', 'updated' ) as $key ) {
				if ( array_key_exists( $key, $result ) && ( is_scalar( $result[ $key ] ) || null === $result[ $key ] ) ) {
					$summary[ $key ] = $result[ $key ];
				}
			}
			if ( ! $summary ) {
				$summary['completed'] = true;
				$summary['result_type'] = 'structured_report';
			}
			return $summary;
		}
		return array( 'completed' => true, 'result' => sanitize_text_field( (string) $result ) );
	}

	private function profile_id() {
		$profile = $this->profile->get();
		return sanitize_text_field( $profile['profile_id'] ?? $this->profile->fingerprint() );
	}

	private function statuses() {
		return array( 'pending', 'ready', 'in_progress', 'pending_approval', 'approved', 'completed', 'failed', 'blocked', 'skipped' );
	}

	private function tables_ready() {
		return $this->table_exists( $this->workflows_table() ) && $this->table_exists( $this->tasks_table() ) && $this->table_exists( $this->runs_table() );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}

	private function decode_json( $value ) {
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function format_timestamp( $timestamp ) {
		return $timestamp ? gmdate( 'Y-m-d H:i:s', absint( $timestamp ) ) : '';
	}
}
