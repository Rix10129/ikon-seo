<?php

defined( 'ABSPATH' ) || exit;

class Ikon_SEO_Admin {
	private $logger;
	private $connection;
	private $profile;
	private $inventory;
	private $workflow;
	private $migration;
	private $search_console;
	private $queue;
	private $monitor;
	private $local;
	private $gbp;

	public function __construct(
		Ikon_SEO_Logger $logger,
		Ikon_SEO_Connection $connection,
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Workflow $workflow,
		Ikon_SEO_Migration $migration,
		Ikon_SEO_Search_Console $search_console,
		Ikon_SEO_Queue $queue,
		Ikon_SEO_Monitor $monitor,
		Ikon_SEO_Local $local,
		Ikon_SEO_GBP $gbp
	) {
		$this->logger     = $logger;
		$this->connection = $connection;
		$this->profile   = $profile;
		$this->inventory = $inventory;
		$this->workflow  = $workflow;
		$this->migration = $migration;
		$this->search_console = $search_console;
		$this->queue          = $queue;
		$this->monitor        = $monitor;
		$this->local          = $local;
		$this->gbp            = $gbp;

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_ikon_seo_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_ikon_seo_save_profile', array( $this, 'save_profile' ) );
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
		add_action( 'admin_post_ikon_seo_gsc_save_credentials', array( $this, 'gsc_save_credentials' ) );
		add_action( 'admin_post_ikon_seo_gsc_connect', array( $this, 'gsc_connect' ) );
		add_action( 'admin_post_ikon_seo_gsc_callback', array( $this, 'gsc_callback' ) );
		add_action( 'admin_post_ikon_seo_gsc_select_property', array( $this, 'gsc_select_property' ) );
		add_action( 'admin_post_ikon_seo_gsc_disconnect', array( $this, 'gsc_disconnect' ) );
		add_action( 'admin_post_ikon_seo_gsc_refresh', array( $this, 'gsc_refresh' ) );
		add_action( 'admin_post_ikon_seo_queue_import', array( $this, 'queue_import' ) );
		add_action( 'admin_post_ikon_seo_queue_status', array( $this, 'queue_status' ) );
		add_action( 'admin_post_ikon_seo_monitor_run', array( $this, 'monitor_run' ) );
		add_action( 'admin_post_ikon_seo_monitor_schedule', array( $this, 'monitor_schedule' ) );
		add_action( 'admin_post_ikon_seo_monitor_reviewed', array( $this, 'monitor_reviewed' ) );
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
	}

	public function assets( $hook ) {
		if ( 'toplevel_page_ikon-seo' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'ikon-seo-admin', IKON_SEO_URL . 'assets/admin.css', array(), IKON_SEO_VERSION );
		wp_enqueue_script( 'ikon-seo-admin', IKON_SEO_URL . 'assets/admin.js', array(), IKON_SEO_VERSION, true );
	}

	public function plugin_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=ikon-seo' ) ) . '">' . esc_html__( 'Settings', 'ikon-seo' ) . '</a>' );
		return $links;
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab = sanitize_key( $_GET['tab'] ?? 'dashboard' );
		if ( ! in_array( $tab, array( 'dashboard', 'profile', 'connection', 'reviews', 'inventory', 'local-seo', 'business-profile', 'search-console', 'queue', 'monitor', 'migration', 'settings', 'activity' ), true ) ) {
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
				$this->tab_link( 'profile', 'Website Profile', $tab );
				$this->tab_link( 'connection', 'Connection', $tab );
				$this->tab_link( 'reviews', 'Reviews', $tab );
				$this->tab_link( 'inventory', 'Site Inventory', $tab );
				$this->tab_link( 'local-seo', 'Local SEO', $tab );
				$this->tab_link( 'business-profile', 'Business Profile', $tab );
				$this->tab_link( 'search-console', 'Search Console', $tab );
				$this->tab_link( 'queue', 'Page Plans', $tab );
				$this->tab_link( 'monitor', 'Refresh Monitor', $tab );
				$this->tab_link( 'migration', 'Domain Tools', $tab );
				$this->tab_link( 'settings', 'Settings', $tab );
				$this->tab_link( 'activity', 'Activity', $tab );
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
				<?php elseif ( ! empty( $_GET['queue-imported'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php echo esc_html( sprintf( '%d page plans were imported. %d rows were skipped.', absint( $_GET['inserted'] ?? 0 ), absint( $_GET['skipped'] ?? 0 ) ) ); ?></p></div>
				<?php elseif ( ! empty( $_GET['monitor-run'] ) ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'The content refresh monitor completed.', 'ikon-seo' ); ?></p></div>
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
				<?php endif; ?>
				<?php
				if ( 'profile' === $tab ) {
					$this->render_profile( $settings );
				} elseif ( 'connection' === $tab ) {
					$this->render_connection( $settings );
				} elseif ( 'reviews' === $tab ) {
					$this->render_reviews();
				} elseif ( 'inventory' === $tab ) {
					$this->render_inventory();
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
				} elseif ( 'migration' === $tab ) {
					$this->render_migration( $settings );
				} elseif ( 'settings' === $tab ) {
					$this->render_settings( $settings );
				} elseif ( 'activity' === $tab ) {
					$this->render_activity();
				} else {
					$this->render_dashboard( $settings );
				}
				?>
			</div>
		</div>
		<?php
	}

	private function render_dashboard( array $settings ) {
		$profile    = $this->profile->get();
		$gsc        = $this->search_console->status();
		$gbp        = $this->gbp->status();
		$local      = $this->local->summary();
		$queue      = $this->queue->counts();
		$connection_status = $this->connection->status( $settings );
		$checks = array(
			array(
				'label'  => 'Website Profile',
				'ok'     => ! empty( $profile['configured'] ),
				'detail' => ! empty( $profile['configured'] ) ? $profile['industry_label'] . ' using ' . $profile['business_entity_type'] : 'Complete the Website Profile before creating pages.',
			),
			array(
				'label'  => 'Ikon SEO connection',
				'ok'     => 'connected' === $connection_status,
				'detail' => 'connected' === $connection_status
					? 'Connected and verified. The workflow can read the site and create drafts.'
					: ( 'ready' === $connection_status ? 'A secure key exists, but the workflow has not completed pairing yet.' : 'Open Connection and click Connect Ikon SEO.' ),
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
				'detail' => ! empty( $settings['draft_only'] ) ? 'All remote page changes are saved as drafts.' : 'Direct publishing is enabled.',
			),
			array(
				'label'  => 'Remote action switch',
				'ok'     => ! empty( $settings['remote_actions'] ),
				'detail' => ! empty( $settings['remote_actions'] ) ? 'Authenticated read and draft actions are enabled.' : 'All remote actions are paused.',
			),
			array(
				'label'  => 'Search Console insights',
				'ok'     => ! empty( $gsc['connected'] ) && ! empty( $gsc['property'] ),
				'detail' => ! empty( $gsc['connected'] )
					? ( $gsc['property'] ? 'Read-only property: ' . $gsc['property'] : 'Connected; select the correct property.' )
					: 'Optional. Connect read-only Search Console access for performance and indexing insights.',
			),
			array(
				'label'  => 'Page-plan queue',
				'ok'     => true,
				'detail' => sprintf( '%d planned, %d claimed, %d completed and %d failed items.', $queue['planned'], $queue['claimed'], $queue['completed'], $queue['failed'] ),
			),
			array(
				'label'  => 'Local SEO profiles',
				'ok'     => ! empty( $local['locations'] ),
				'detail' => $local['locations']
					? sprintf( '%d local records, %d verified customer-facing locations; NAP status: %s.', $local['locations'], $local['verified_locations'], str_replace( '_', ' ', $local['nap_audit']['status'] ) )
					: 'Optional. Add real locations or service areas before generating local pages.',
			),
			array(
				'label'  => 'Google Business Profile',
				'ok'     => ! empty( $gbp['connected'] ) && ! empty( $gbp['linked_locations'] ),
				'detail' => $gbp['connected']
					? sprintf( '%d locations linked; external mutations remain administrator approval-gated.', $gbp['linked_locations'] )
					: 'Optional. Connect an approved Google project for reviews and local performance.',
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

		<?php
		if ( empty( $profile['configured'] ) ) {
			$next_label = __( 'Complete Website Profile', 'ikon-seo' );
			$next_text  = __( 'Add the website identity, industry and publishing rules first.', 'ikon-seo' );
			$next_url   = admin_url( 'admin.php?page=ikon-seo&tab=profile' );
		} elseif ( 'connected' !== $connection_status ) {
			$next_label = __( 'Connect Ikon SEO', 'ikon-seo' );
			$next_text  = __( 'Pair this website with a temporary code. No permanent key needs to be copied.', 'ikon-seo' );
			$next_url   = admin_url( 'admin.php?page=ikon-seo&tab=connection' );
		} else {
			$next_label = __( 'Scan Website', 'ikon-seo' );
			$next_text  = __( 'Review existing pages before creating a new draft.', 'ikon-seo' );
			$next_url   = admin_url( 'admin.php?page=ikon-seo&tab=inventory' );
		}
		?>
		<div class="ikon-seo-next-step">
			<div><strong><?php esc_html_e( 'Next step', 'ikon-seo' ); ?></strong><p><?php echo esc_html( $next_text ); ?></p></div>
			<a class="button button-primary" href="<?php echo esc_url( $next_url ); ?>"><?php echo esc_html( $next_label ); ?></a>
		</div>

		<div class="ikon-seo-grid">
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Create', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Build a new structured Elementor page with SEO metadata, schema, CTAs, tables, FAQs and internal links.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Improve', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Read an existing page and create a separate review draft. The live URL remains unchanged until approval.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Audit', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Inventory pages, find orphans and keyword overlap, discover internal links, and run a pre-publication quality report.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Approve safely', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Compare an improvement draft, merge it into the original URL, regenerate Elementor CSS, and retain rollback snapshots.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Measure', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Compare Search Console periods, review top queries and pages, inspect Google index status, and check submitted sitemap warnings.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Plan batches', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Import a controlled CSV page plan, then generate each full page interactively through the same profile-aware validation and draft workflow.', 'ikon-seo' ); ?></p>
			</div>
			<div class="ikon-seo-card">
				<h3><?php esc_html_e( 'Optimize locally', 'ikon-seo' ); ?></h3>
				<p><?php esc_html_e( 'Separate real locations from service areas, check NAP, block doorway-page risks, track citations and stage Business Profile actions for approval.', 'ikon-seo' ); ?></p>
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
		$pairing     = $this->connection->current_pairing( $user_id );
		$status      = $this->connection->status( $settings );
		$schema_url  = rest_url( Ikon_SEO_REST::NAMESPACE . '/openapi' );
		$pair_url    = rest_url( Ikon_SEO_REST::NAMESPACE . '/pair' );
		$new_token   = get_transient( 'ikon_seo_new_token_' . $user_id );
		$test_result = get_transient( 'ikon_seo_connection_test_' . $user_id );

		if ( $new_token ) {
			delete_transient( 'ikon_seo_new_token_' . $user_id );
		}
		if ( $test_result ) {
			delete_transient( 'ikon_seo_connection_test_' . $user_id );
		}

		$status_labels = array(
			'disconnected' => __( 'Not connected', 'ikon-seo' ),
			'ready'        => __( 'Waiting for pairing', 'ikon-seo' ),
			'connected'    => __( 'Connected', 'ikon-seo' ),
		);
		?>
		<div class="ikon-seo-section-header">
			<div>
				<h2><?php esc_html_e( 'Connect Ikon SEO', 'ikon-seo' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Simple mode hides API keys and technical setup. Pair this website with a short-lived code.', 'ikon-seo' ); ?></p>
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
					<h3><?php esc_html_e( 'Website connected successfully', 'ikon-seo' ); ?></h3>
					<p><?php esc_html_e( 'Ikon SEO can read this website and create review drafts. Live pages still cannot be changed automatically.', 'ikon-seo' ); ?></p>
					<?php if ( ! empty( $settings['connection_last_seen_at'] ) ) : ?>
						<p class="description"><?php echo esc_html( sprintf( __( 'Last verified activity: %s UTC', 'ikon-seo' ), $settings['connection_last_seen_at'] ) ); ?></p>
					<?php endif; ?>
				</div>
			<?php elseif ( $pairing ) : ?>
				<div class="ikon-seo-connect-icon">2</div>
				<div class="ikon-seo-connect-content">
					<h3><?php esc_html_e( 'Enter this pairing code in the Ikon SEO workflow', 'ikon-seo' ); ?></h3>
					<p><?php esc_html_e( 'Use the website address and code below. The permanent connection key stays hidden.', 'ikon-seo' ); ?></p>
					<div class="ikon-seo-pairing-box">
						<div>
							<span class="ikon-seo-pairing-label"><?php esc_html_e( 'Website', 'ikon-seo' ); ?></span>
							<code id="ikon-seo-pair-site"><?php echo esc_html( untrailingslashit( $pairing['site_url'] ) ); ?></code>
						</div>
						<div>
							<span class="ikon-seo-pairing-label"><?php esc_html_e( 'Pairing code', 'ikon-seo' ); ?></span>
							<strong id="ikon-seo-pair-code" class="ikon-seo-pairing-code"><?php echo esc_html( $pairing['code'] ); ?></strong>
						</div>
					</div>
					<div class="ikon-seo-actions">
						<button type="button" class="button button-primary" data-copy-target="#ikon-seo-pair-code"><?php esc_html_e( 'Copy pairing code', 'ikon-seo' ); ?></button>
						<span class="description" data-ikon-pairing-expires="<?php echo esc_attr( $pairing['expires_at'] ); ?>"><?php esc_html_e( 'Expires in 10 minutes', 'ikon-seo' ); ?></span>
					</div>
				</div>
			<?php else : ?>
				<div class="ikon-seo-connect-icon">1</div>
				<div class="ikon-seo-connect-content">
					<h3><?php esc_html_e( 'One-click website setup', 'ikon-seo' ); ?></h3>
					<p><?php esc_html_e( 'Click the button below. Ikon SEO will create a safe read-and-draft connection and show a temporary pairing code.', 'ikon-seo' ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<div class="ikon-seo-actions ikon-seo-primary-actions">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_start_pairing">
				<?php wp_nonce_field( 'ikon_seo_start_pairing' ); ?>
				<button type="submit" class="button button-primary button-hero"><?php echo esc_html( 'connected' === $status ? __( 'Reconnect Ikon SEO', 'ikon-seo' ) : ( $pairing ? __( 'Create a new code', 'ikon-seo' ) : __( 'Connect Ikon SEO', 'ikon-seo' ) ) ); ?></button>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ikon_seo_test_connection">
				<?php wp_nonce_field( 'ikon_seo_test_connection' ); ?>
				<button type="submit" class="button"><?php esc_html_e( 'Test website API', 'ikon-seo' ); ?></button>
			</form>

			<?php if ( 'connected' === $status ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_refresh_inventory">
					<?php wp_nonce_field( 'ikon_seo_refresh_inventory' ); ?>
					<button type="submit" class="button"><?php esc_html_e( 'Scan website', 'ikon-seo' ); ?></button>
				</form>
			<?php endif; ?>

			<?php if ( ! empty( $settings['token_hash'] ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ikon_seo_revoke_token">
					<?php wp_nonce_field( 'ikon_seo_revoke_token' ); ?>
					<button type="submit" class="button button-link-delete" data-confirm="<?php esc_attr_e( 'Disconnect this website? The current connection will stop working immediately.', 'ikon-seo' ); ?>"><?php esc_html_e( 'Disconnect', 'ikon-seo' ); ?></button>
				</form>
			<?php endif; ?>
		</div>

		<div class="ikon-seo-simple-steps">
			<div><strong>1</strong><span><?php esc_html_e( 'Click Connect', 'ikon-seo' ); ?></span></div>
			<div><strong>2</strong><span><?php esc_html_e( 'Enter the temporary code', 'ikon-seo' ); ?></span></div>
			<div><strong>3</strong><span><?php esc_html_e( 'Scan and create drafts', 'ikon-seo' ); ?></span></div>
		</div>

		<details class="ikon-seo-advanced">
			<summary><?php esc_html_e( 'Advanced connection settings', 'ikon-seo' ); ?></summary>
			<div class="ikon-seo-advanced-content">
				<p class="description"><?php esc_html_e( 'These details are intended for developers and custom integrations. Most users do not need them.', 'ikon-seo' ); ?></p>

				<?php if ( $new_token ) : ?>
					<div class="notice notice-warning inline ikon-seo-token-notice">
						<h3><?php esc_html_e( 'Copy the developer key now', 'ikon-seo' ); ?></h3>
						<p><?php esc_html_e( 'This key is shown once. Use pairing mode above whenever possible.', 'ikon-seo' ); ?></p>
						<div class="ikon-seo-copy-row">
							<code id="ikon-seo-new-token"><?php echo esc_html( $new_token ); ?></code>
							<button type="button" class="button" data-copy-target="#ikon-seo-new-token"><?php esc_html_e( 'Copy key', 'ikon-seo' ); ?></button>
						</div>
					</div>
				<?php endif; ?>

				<div class="ikon-seo-connection-box">
					<label><?php esc_html_e( 'OpenAPI schema URL', 'ikon-seo' ); ?></label>
					<div class="ikon-seo-copy-row">
						<code id="ikon-seo-schema-url"><?php echo esc_html( $schema_url ); ?></code>
						<button type="button" class="button" data-copy-target="#ikon-seo-schema-url"><?php esc_html_e( 'Copy URL', 'ikon-seo' ); ?></button>
					</div>
				</div>

				<div class="ikon-seo-connection-box">
					<label><?php esc_html_e( 'Pairing endpoint', 'ikon-seo' ); ?></label>
					<code><?php echo esc_html( $pair_url ); ?></code>
					<p class="description"><?php esc_html_e( 'POST the temporary code once. The response returns the connection package and invalidates the code.', 'ikon-seo' ); ?></p>
				</div>

				<div class="ikon-seo-connection-box">
					<label><?php esc_html_e( 'Developer authentication', 'ikon-seo' ); ?></label>
					<p><?php echo esc_html( ! empty( $settings['token_hash'] ) ? sprintf( __( 'Key configured (%s). Scopes: %s', 'ikon-seo' ), $settings['token_hint'], implode( ', ', (array) $settings['key_scopes'] ) ) : __( 'No key configured.', 'ikon-seo' ) ); ?></p>
					<p><code>X-Ikon-SEO-Key: YOUR_KEY</code></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ikon_seo_generate_token">
						<?php wp_nonce_field( 'ikon_seo_generate_token' ); ?>
						<button type="submit" class="button"><?php esc_html_e( 'Generate developer key', 'ikon-seo' ); ?></button>
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
		$status    = $this->gbp->status();
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
			<span class="ikon-seo-pill <?php echo $status['connected'] ? 'is-connected' : ''; ?>"><?php echo esc_html( $status['connected'] ? 'Connected' : 'Not connected' ); ?></span>
		</div>
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
		$this->guard( 'ikon_seo_save_settings' );

		$current = Ikon_SEO_Plugin::settings();
		$current['draft_only']          = isset( $_POST['draft_only'] ) ? 1 : 0;
		$current['require_profile_match']= isset( $_POST['require_profile_match'] ) ? 1 : 0;
		$current['remote_actions']      = isset( $_POST['remote_actions'] ) ? 1 : 0;
		$current['remote_merge']        = isset( $_POST['remote_merge'] ) ? 1 : 0;
		$current['rate_limit']          = max( 10, min( 300, absint( $_POST['rate_limit'] ?? 60 ) ) );
		$current['max_payload_kb']      = max( 128, min( 4096, absint( $_POST['max_payload_kb'] ?? 1024 ) ) );
		$current['inventory_limit']     = max( 50, min( 2000, absint( $_POST['inventory_limit'] ?? 300 ) ) );
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
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=settings&updated=1' ) );
		exit;
	}

	public function save_profile() {
		$this->guard( 'ikon_seo_save_profile' );

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
		$this->guard( 'ikon_seo_export_profile' );
		$document = $this->profile->export();
		$filename = 'ikon-seo-profile-' . sanitize_title( $document['profile']['site_name'] ?? 'website' ) . '.json';
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	public function import_profile() {
		$this->guard( 'ikon_seo_import_profile' );
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
		$this->guard( 'ikon_seo_preview_migration' );
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
		$this->guard( 'ikon_seo_apply_migration' );
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
		$this->guard( 'ikon_seo_start_pairing' );

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
		$this->guard( 'ikon_seo_test_connection' );

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
					'message' => __( 'Website API is working. Create a pairing code to connect the approved workflow.', 'ikon-seo' ),
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
		$this->guard( 'ikon_seo_generate_token' );

		if ( ! $this->profile->get()['configured'] ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', 'Complete the Website Profile before generating a developer key.', admin_url( 'admin.php?page=ikon-seo&tab=profile' ) ) );
			exit;
		}

		$token = $this->connection->generate_developer_key( get_current_user_id() );
		set_transient( 'ikon_seo_new_token_' . get_current_user_id(), $token, 5 * MINUTE_IN_SECONDS );
		$this->logger->log( 'developer_key', 'success', 'A developer connection key was generated.' );

		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=connection&key-created=1' ) );
		exit;
	}

	public function revoke_token() {
		$this->guard( 'ikon_seo_revoke_token' );
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
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=inventory&refreshed=1' ) );
		exit;
	}

	public function gsc_save_credentials() {
		$this->guard( 'ikon_seo_gsc_save_credentials' );
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
		$this->guard( 'ikon_seo_gsc_connect' );
		$url = $this->search_console->authorization_url( get_current_user_id() );
		if ( is_wp_error( $url ) ) {
			wp_safe_redirect( add_query_arg( 'ikon-error', $url->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=search-console' ) ) );
			exit;
		}
		wp_redirect( esc_url_raw( $url ) );
		exit;
	}

	public function gsc_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
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
		$this->guard( 'ikon_seo_gsc_select_property' );
		$result = $this->search_console->select_property( wp_unslash( $_POST['gsc_property'] ?? '' ) );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=search-console' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=search-console&gsc-updated=1' );
		wp_safe_redirect( $url );
		exit;
	}

	public function gsc_disconnect() {
		$this->guard( 'ikon_seo_gsc_disconnect' );
		$this->search_console->disconnect();
		wp_safe_redirect( admin_url( 'admin.php?page=ikon-seo&tab=search-console&gsc-updated=1' ) );
		exit;
	}

	public function gsc_refresh() {
		$this->guard( 'ikon_seo_gsc_refresh' );
		$result = $this->search_console->performance( 28, true );
		$url = is_wp_error( $result )
			? add_query_arg( 'ikon-error', $result->get_error_message(), admin_url( 'admin.php?page=ikon-seo&tab=search-console' ) )
			: admin_url( 'admin.php?page=ikon-seo&tab=search-console&gsc-updated=1' );
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

	private function tab_link( $slug, $label, $active ) {
		$url = admin_url( 'admin.php?page=ikon-seo&tab=' . $slug );
		echo '<a class="nav-tab ' . ( $slug === $active ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
}
