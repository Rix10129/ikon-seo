<?php
/**
 * Plugin Name: Ikon SEO Services Dubai Rollout
 * Plugin URI: https://ikondigitals.com/
 * Description: Site-specific preview, apply and rollback controller for the Ikon Digitals SEO Services Dubai production page.
 * Version: 1.1.0
 * Author: Ikon Digitals
 * Author URI: https://ikondigitals.com/
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Text Domain: ikon-seo-services-dubai-rollout
 */

defined( 'ABSPATH' ) || exit;

final class Ikon_SEO_Services_Dubai_Rollout {
	const VERSION       = '1.1.0';
	const MENU_SLUG     = 'ikon-seo-services-dubai-rollout';
	const PAGE_SLUG     = 'seo-services-dubai';
	const BACKUP_META   = '_ikon_sd_rollout_backup';
	const VERSION_META  = '_ikon_sd_rollout_version';
	const APPLIED_META  = '_ikon_sd_rollout_applied_at';
	const PREVIEW_NONCE = 'ikon_sd_rollout_preview';

	private $plugin_file;
	private $plugin_dir;
	private $plugin_url;

	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_dir  = plugin_dir_path( $plugin_file );
		$this->plugin_url  = plugin_dir_url( $plugin_file );

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 90 );
		add_action( 'admin_post_ikon_sd_apply', array( $this, 'apply' ) );
		add_action( 'admin_post_ikon_sd_restore', array( $this, 'restore' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ), 50 );
		add_filter( 'the_content', array( $this, 'preview_content' ), 9999 );
		add_filter( 'plugin_action_links_' . plugin_basename( $plugin_file ), array( $this, 'plugin_links' ) );
	}

	public function admin_menu() {
		$parent = menu_page_url( 'ikon-seo', false ) ? 'ikon-seo' : 'options-general.php';
		add_submenu_page(
			$parent,
			__( 'SEO Services Dubai Rollout', 'ikon-seo-services-dubai-rollout' ),
			__( 'Production Page', 'ikon-seo-services-dubai-rollout' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_admin' )
		);
	}

	public function plugin_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( $this->admin_url() ) . '">' . esc_html__( 'Open rollout', 'ikon-seo-services-dubai-rollout' ) . '</a>' );
		return $links;
	}

	public function enqueue_frontend() {
		if ( ! is_page( self::PAGE_SLUG ) ) {
			return;
		}

		$page = get_queried_object();
		$has_rollout = $page instanceof WP_Post && self::VERSION === (string) get_post_meta( $page->ID, self::VERSION_META, true );
		if ( ! $has_rollout && ! $this->is_preview_request() ) {
			return;
		}

		wp_enqueue_style(
			'ikon-seo-services-dubai-production',
			$this->plugin_url . 'assets/page.css',
			array(),
			self::VERSION
		);
	}

	public function preview_content( $content ) {
		if ( ! $this->is_preview_request() || ! is_page( self::PAGE_SLUG ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$production = $this->production_content();
		return is_wp_error( $production ) ? $content : $production;
	}

	public function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page       = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );
		$allowed    = $this->allowed_site();
		$backup     = $page ? get_post_meta( $page->ID, self::BACKUP_META, true ) : array();
		$version    = $page ? (string) get_post_meta( $page->ID, self::VERSION_META, true ) : '';
		$is_applied = $page && self::VERSION === $version;
		$status     = sanitize_key( $_GET['ikon-sd-status'] ?? '' );
		$error      = sanitize_text_field( wp_unslash( $_GET['ikon-sd-error'] ?? '' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'SEO Services Dubai — Production Rollout', 'ikon-seo-services-dubai-rollout' ); ?></h1>
			<p><?php esc_html_e( 'Preview first, then apply the production page with an automatic rollback snapshot. The homepage, header, footer, menus and Proof Library are not changed.', 'ikon-seo-services-dubai-rollout' ); ?></p>

			<?php if ( 'applied' === $status ) : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'Production page applied. Open the live page and complete visual QA.', 'ikon-seo-services-dubai-rollout' ); ?></p></div>
			<?php elseif ( 'restored' === $status ) : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'Previous page version restored successfully.', 'ikon-seo-services-dubai-rollout' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $error ) : ?>
				<div class="notice notice-error inline"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>

			<div class="card" style="max-width:1020px;padding:26px;margin-top:22px;">
				<h2 style="margin-top:0;"><?php esc_html_e( 'SEO Services in Dubai', 'ikon-seo-services-dubai-rollout' ); ?></h2>
				<table class="widefat striped" style="margin:18px 0 24px;">
					<tbody>
						<tr><td><strong><?php esc_html_e( 'Target', 'ikon-seo-services-dubai-rollout' ); ?></strong></td><td><?php echo esc_html( home_url( '/' . self::PAGE_SLUG . '/' ) ); ?></td></tr>
						<tr><td><strong><?php esc_html_e( 'Existing page', 'ikon-seo-services-dubai-rollout' ); ?></strong></td><td><?php echo $page ? esc_html( '#' . $page->ID . ' · ' . $page->post_status ) : esc_html__( 'Not found — Apply will create a draft only.', 'ikon-seo-services-dubai-rollout' ); ?></td></tr>
						<tr><td><strong><?php esc_html_e( 'Rollout version', 'ikon-seo-services-dubai-rollout' ); ?></strong></td><td><?php echo esc_html( self::VERSION ); ?></td></tr>
						<tr><td><strong><?php esc_html_e( 'Applied', 'ikon-seo-services-dubai-rollout' ); ?></strong></td><td><?php echo $is_applied ? esc_html__( 'Yes', 'ikon-seo-services-dubai-rollout' ) : esc_html__( 'No', 'ikon-seo-services-dubai-rollout' ); ?></td></tr>
						<tr><td><strong><?php esc_html_e( 'Rollback snapshot', 'ikon-seo-services-dubai-rollout' ); ?></strong></td><td><?php echo $backup ? esc_html__( 'Available', 'ikon-seo-services-dubai-rollout' ) : esc_html__( 'Not created yet', 'ikon-seo-services-dubai-rollout' ); ?></td></tr>
						<tr><td><strong><?php esc_html_e( 'Site guard', 'ikon-seo-services-dubai-rollout' ); ?></strong></td><td><?php echo $allowed ? esc_html__( 'ikondigitals.com verified', 'ikon-seo-services-dubai-rollout' ) : esc_html__( 'Blocked on this domain', 'ikon-seo-services-dubai-rollout' ); ?></td></tr>
					</tbody>
				</table>

				<ul style="list-style:disc;padding-left:22px;">
					<li><?php esc_html_e( 'Preview does not write to the page database.', 'ikon-seo-services-dubai-rollout' ); ?></li>
					<li><?php esc_html_e( 'Apply finds the current page by slug and does not create a duplicate URL.', 'ikon-seo-services-dubai-rollout' ); ?></li>
					<li><?php esc_html_e( 'Before replacement, title/content/excerpt/status, Rank Math fields, Elementor data and page-template data are saved.', 'ikon-seo-services-dubai-rollout' ); ?></li>
					<li><?php esc_html_e( 'The existing published/draft status is preserved.', 'ikon-seo-services-dubai-rollout' ); ?></li>
					<li><?php esc_html_e( 'No guessed Proof Library evidence permalink is created; proof CTAs use the verified Results hub.', 'ikon-seo-services-dubai-rollout' ); ?></li>
				</ul>

				<?php if ( ! $allowed ) : ?>
					<div class="notice notice-warning inline"><p><?php esc_html_e( 'This rollout is intentionally locked to ikondigitals.com.', 'ikon-seo-services-dubai-rollout' ); ?></p></div>
				<?php else : ?>
					<p style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
						<?php if ( $page ) : ?>
							<a class="button button-secondary" href="<?php echo esc_url( $this->preview_url( $page ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview New Layout', 'ikon-seo-services-dubai-rollout' ); ?></a>
						<?php endif; ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
							<input type="hidden" name="action" value="ikon_sd_apply">
							<?php wp_nonce_field( 'ikon_sd_apply' ); ?>
							<?php submit_button( $is_applied ? __( 'Re-apply Production Page', 'ikon-seo-services-dubai-rollout' ) : __( 'Apply Production Page', 'ikon-seo-services-dubai-rollout' ), 'primary', 'submit', false ); ?>
						</form>
						<?php if ( $page && $backup ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
								<input type="hidden" name="action" value="ikon_sd_restore">
								<?php wp_nonce_field( 'ikon_sd_restore' ); ?>
								<?php submit_button( __( 'Restore Previous Version', 'ikon-seo-services-dubai-rollout' ), 'secondary', 'submit', false ); ?>
							</form>
						<?php endif; ?>
						<?php if ( $page ) : ?>
							<a class="button" href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open Current Page', 'ikon-seo-services-dubai-rollout' ); ?></a>
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public function apply() {
		$this->guard_action( 'ikon_sd_apply' );

		if ( ! $this->allowed_site() ) {
			$this->redirect_error( 'Production rollout blocked: this plugin is restricted to ikondigitals.com.' );
		}

		$content = $this->production_content();
		if ( is_wp_error( $content ) ) {
			$this->redirect_error( $content->get_error_message() );
		}

		$page = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );
		if ( ! $page ) {
			$page_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'draft',
					'post_title'   => 'SEO Services in Dubai',
					'post_name'    => self::PAGE_SLUG,
					'post_content' => $content,
					'post_excerpt' => $this->excerpt(),
				),
				true
			);
			if ( is_wp_error( $page_id ) ) {
				$this->redirect_error( $page_id->get_error_message() );
			}
			$page = get_post( $page_id );
		} else {
			$this->create_backup( $page );
			$result = wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_content' => $content,
					'post_excerpt' => $this->excerpt(),
					'post_status'  => $page->post_status,
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				$this->redirect_error( $result->get_error_message() );
			}
		}

		$this->clear_builder_override( $page->ID );
		$this->apply_seo_meta( $page->ID );
		update_post_meta( $page->ID, self::VERSION_META, self::VERSION );
		update_post_meta( $page->ID, self::APPLIED_META, current_time( 'mysql', true ) );
		clean_post_cache( $page->ID );
		$this->purge_caches( $page->ID );

		wp_safe_redirect( $this->admin_url( array( 'ikon-sd-status' => 'applied' ) ) );
		exit;
	}

	public function restore() {
		$this->guard_action( 'ikon_sd_restore' );

		if ( ! $this->allowed_site() ) {
			$this->redirect_error( 'Production rollback blocked: this plugin is restricted to ikondigitals.com.' );
		}

		$page = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );
		if ( ! $page ) {
			$this->redirect_error( 'The SEO Services Dubai page could not be found.' );
		}

		$backup = get_post_meta( $page->ID, self::BACKUP_META, true );
		if ( ! is_array( $backup ) || empty( $backup['post'] ) ) {
			$this->redirect_error( 'No rollback snapshot is available for this page.' );
		}

		$post = $backup['post'];
		$result = wp_update_post(
			array(
				'ID'           => $page->ID,
				'post_title'   => (string) ( $post['post_title'] ?? $page->post_title ),
				'post_content' => (string) ( $post['post_content'] ?? '' ),
				'post_excerpt' => (string) ( $post['post_excerpt'] ?? '' ),
				'post_status'  => (string) ( $post['post_status'] ?? $page->post_status ),
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			$this->redirect_error( $result->get_error_message() );
		}

		foreach ( (array) ( $backup['meta'] ?? array() ) as $key => $value ) {
			if ( null === $value ) {
				delete_post_meta( $page->ID, $key );
			} else {
				update_post_meta( $page->ID, $key, $value );
			}
		}

		delete_post_meta( $page->ID, self::VERSION_META );
		delete_post_meta( $page->ID, self::APPLIED_META );
		delete_post_meta( $page->ID, self::BACKUP_META );
		clean_post_cache( $page->ID );
		$this->purge_caches( $page->ID );

		wp_safe_redirect( $this->admin_url( array( 'ikon-sd-status' => 'restored' ) ) );
		exit;
	}

	private function production_content() {
		$file = $this->plugin_dir . 'page-content.html';
		if ( ! is_readable( $file ) ) {
			return new WP_Error( 'ikon_sd_template_missing', 'The production page template is missing from the rollout plugin.' );
		}

		$content = (string) file_get_contents( $file );
		if ( '' === trim( $content ) ) {
			return new WP_Error( 'ikon_sd_template_empty', 'The production page template is empty.' );
		}

		return strtr(
			$content,
			array(
				'{{CONTACT_URL}}' => esc_url( $this->find_page_url( array( 'contact', 'contact-us' ), home_url( '/contact/' ) ) ),
				'{{RESULTS_URL}}' => esc_url( $this->find_page_url( array( 'results', 'seo-results' ), home_url( '/results/' ) ) ),
			)
		);
	}

	private function find_page_url( array $slugs, $fallback ) {
		foreach ( $slugs as $slug ) {
			$page = get_page_by_path( sanitize_title( $slug ), OBJECT, 'page' );
			if ( $page ) {
				return get_permalink( $page );
			}
		}
		return $fallback;
	}

	private function excerpt() {
		return 'SEO services in Dubai for technical SEO, local search, content, authority and AI visibility, backed by documented results.';
	}

	private function create_backup( WP_Post $page ) {
		$existing = get_post_meta( $page->ID, self::BACKUP_META, true );
		if ( is_array( $existing ) && ! empty( $existing['post'] ) ) {
			return;
		}

		$keys = array(
			'rank_math_title',
			'rank_math_description',
			'rank_math_focus_keyword',
			'rank_math_canonical_url',
			'_elementor_data',
			'_elementor_edit_mode',
			'_elementor_template_type',
			'_wp_page_template',
		);
		$meta = array();
		foreach ( $keys as $key ) {
			$meta[ $key ] = metadata_exists( 'post', $page->ID, $key ) ? get_post_meta( $page->ID, $key, true ) : null;
		}

		update_post_meta(
			$page->ID,
			self::BACKUP_META,
			array(
				'created_at' => current_time( 'mysql', true ),
				'post'       => array(
					'post_title'   => $page->post_title,
					'post_content' => $page->post_content,
					'post_excerpt' => $page->post_excerpt,
					'post_status'  => $page->post_status,
				),
				'meta'       => $meta,
			)
		);
	}

	private function clear_builder_override( $page_id ) {
		if ( metadata_exists( 'post', $page_id, '_elementor_data' ) ) {
			delete_post_meta( $page_id, '_elementor_data' );
			delete_post_meta( $page_id, '_elementor_edit_mode' );
		}
	}

	private function apply_seo_meta( $page_id ) {
		if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
			update_post_meta( $page_id, 'rank_math_title', 'SEO Services Dubai | Proven Search Growth | Ikon Digitals' );
			update_post_meta( $page_id, 'rank_math_description', 'SEO services in Dubai for technical SEO, local search, content, authority and AI visibility. See documented results and request an SEO opportunity review.' );
			update_post_meta( $page_id, 'rank_math_focus_keyword', 'SEO services Dubai' );
			update_post_meta( $page_id, 'rank_math_canonical_url', home_url( '/' . self::PAGE_SLUG . '/' ) );
		}
	}

	private function preview_url( WP_Post $page ) {
		return wp_nonce_url(
			add_query_arg( 'ikon_preview_seo_services_dubai', '1', get_permalink( $page ) ),
			self::PREVIEW_NONCE
		);
	}

	private function is_preview_request() {
		if ( empty( $_GET['ikon_preview_seo_services_dubai'] ) || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
		return $nonce && wp_verify_nonce( $nonce, self::PREVIEW_NONCE );
	}

	private function purge_caches( $page_id ) {
		if ( has_action( 'litespeed_purge_post' ) ) {
			do_action( 'litespeed_purge_post', $page_id );
		}
		if ( function_exists( 'rocket_clean_post' ) ) {
			rocket_clean_post( $page_id );
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
	}

	private function allowed_site() {
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$host = preg_replace( '/^www\./', '', $host );
		return 'ikondigitals.com' === $host;
	}

	private function guard_action( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage this rollout.', 'ikon-seo-services-dubai-rollout' ) );
		}
		check_admin_referer( $nonce_action );
	}

	private function redirect_error( $message ) {
		wp_safe_redirect( $this->admin_url( array( 'ikon-sd-error' => (string) $message ) ) );
		exit;
	}

	private function admin_url( array $args = array() ) {
		$url = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		return $args ? add_query_arg( $args, $url ) : $url;
	}
}

new Ikon_SEO_Services_Dubai_Rollout( __FILE__ );
