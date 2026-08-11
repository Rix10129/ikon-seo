<?php

defined( 'ABSPATH' ) || exit;

/**
 * Controlled, site-specific production page rollouts.
 *
 * This class deliberately keeps the production SEO Services Dubai page isolated
 * from global theme/header/footer changes. A one-click apply creates a restorable
 * snapshot before replacing the page body and only loads the matching stylesheet
 * on the intended public URL.
 */
final class Ikon_SEO_Production_Pages {
	const MENU_SLUG      = 'ikon-seo-production-pages';
	const PAGE_SLUG      = 'seo-services-dubai';
	const PAGE_VERSION   = '1.0.0';
	const BACKUP_META    = '_ikon_seo_prod_backup_seo_services_dubai';
	const VERSION_META   = '_ikon_seo_prod_version_seo_services_dubai';
	const APPLIED_META   = '_ikon_seo_prod_applied_at_seo_services_dubai';
	const TEMPLATE_FILE  = 'production/seo-services-dubai/page-content.html';
	const STYLESHEET_URL = 'assets/production/seo-services-dubai.css';

	public static function bootstrap() {
		$instance = new self();
		add_action( 'admin_menu', array( $instance, 'admin_menu' ), 80 );
		add_action( 'admin_post_ikon_seo_apply_seo_services_dubai', array( $instance, 'apply' ) );
		add_action( 'admin_post_ikon_seo_restore_seo_services_dubai', array( $instance, 'restore' ) );
		add_action( 'wp_enqueue_scripts', array( $instance, 'enqueue_frontend' ), 40 );
	}

	public function admin_menu() {
		add_submenu_page(
			'ikon-seo',
			__( 'Production Pages', 'ikon-seo' ),
			__( 'Production Pages', 'ikon-seo' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_admin' )
		);
	}

	public function enqueue_frontend() {
		if ( ! is_page( self::PAGE_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'ikon-seo-production-seo-services-dubai',
			IKON_SEO_URL . self::STYLESHEET_URL,
			array(),
			IKON_SEO_VERSION
		);
	}

	public function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page       = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );
		$allowed    = $this->allowed_site();
		$version    = $page ? (string) get_post_meta( $page->ID, self::VERSION_META, true ) : '';
		$backup     = $page ? get_post_meta( $page->ID, self::BACKUP_META, true ) : array();
		$is_applied = $page && self::PAGE_VERSION === $version;
		$notice     = sanitize_key( $_GET['ikon-production-status'] ?? '' );
		$error      = sanitize_text_field( wp_unslash( $_GET['ikon-production-error'] ?? '' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ikon Production Pages', 'ikon-seo' ); ?></h1>
			<p><?php esc_html_e( 'Controlled page-by-page deployment with a restorable pre-change snapshot. This rollout does not modify the global header, footer or homepage.', 'ikon-seo' ); ?></p>

			<?php if ( 'applied' === $notice ) : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'SEO Services Dubai production layout applied successfully.', 'ikon-seo' ); ?></p></div>
			<?php elseif ( 'restored' === $notice ) : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'The previous SEO Services Dubai page version was restored.', 'ikon-seo' ); ?></p></div>
			<?php endif; ?>
			<?php if ( $error ) : ?>
				<div class="notice notice-error inline"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>

			<div class="card" style="max-width:980px;padding:24px;margin-top:22px;">
				<h2 style="margin-top:0;"><?php esc_html_e( 'SEO Services in Dubai', 'ikon-seo' ); ?></h2>
				<table class="widefat striped" style="margin:16px 0 22px;">
					<tbody>
						<tr><td><strong><?php esc_html_e( 'Target URL', 'ikon-seo' ); ?></strong></td><td><?php echo esc_html( home_url( '/' . self::PAGE_SLUG . '/' ) ); ?></td></tr>
						<tr><td><strong><?php esc_html_e( 'Existing page', 'ikon-seo' ); ?></strong></td><td><?php echo $page ? esc_html( '#' . $page->ID . ' · ' . $page->post_status ) : esc_html__( 'Not found — apply will create a draft.', 'ikon-seo' ); ?></td></tr>
						<tr><td><strong><?php esc_html_e( 'Production version', 'ikon-seo' ); ?></strong></td><td><?php echo esc_html( self::PAGE_VERSION ); ?></td></tr>
						<tr><td><strong><?php esc_html_e( 'Applied', 'ikon-seo' ); ?></strong></td><td><?php echo $is_applied ? esc_html__( 'Yes', 'ikon-seo' ) : esc_html__( 'No', 'ikon-seo' ); ?></td></tr>
						<tr><td><strong><?php esc_html_e( 'Rollback snapshot', 'ikon-seo' ); ?></strong></td><td><?php echo $backup ? esc_html__( 'Available', 'ikon-seo' ) : esc_html__( 'Not created yet', 'ikon-seo' ); ?></td></tr>
						<tr><td><strong><?php esc_html_e( 'Site guard', 'ikon-seo' ); ?></strong></td><td><?php echo $allowed ? esc_html__( 'Ikon Digitals domain verified', 'ikon-seo' ) : esc_html__( 'Blocked on this domain', 'ikon-seo' ); ?></td></tr>
					</tbody>
				</table>

				<p><strong><?php esc_html_e( 'What apply does', 'ikon-seo' ); ?></strong></p>
				<ul style="list-style:disc;padding-left:22px;">
					<li><?php esc_html_e( 'Finds the existing /seo-services-dubai/ page instead of creating a duplicate indexable URL.', 'ikon-seo' ); ?></li>
					<li><?php esc_html_e( 'Stores the current page body, title, status, Rank Math fields and Elementor data before changing anything.', 'ikon-seo' ); ?></li>
					<li><?php esc_html_e( 'Installs the full production SEO Services Dubai body and scoped responsive stylesheet.', 'ikon-seo' ); ?></li>
					<li><?php esc_html_e( 'Preserves the current published/draft status when the page already exists.', 'ikon-seo' ); ?></li>
					<li><?php esc_html_e( 'Uses the live Contact and Results pages for CTAs; no guessed evidence permalink is generated.', 'ikon-seo' ); ?></li>
					<li><?php esc_html_e( 'Purges common post/page caches after a successful change.', 'ikon-seo' ); ?></li>
				</ul>

				<?php if ( ! $allowed ) : ?>
					<div class="notice notice-warning inline"><p><?php esc_html_e( 'This production rollout is intentionally restricted to ikondigitals.com.', 'ikon-seo' ); ?></p></div>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:10px;">
						<input type="hidden" name="action" value="ikon_seo_apply_seo_services_dubai">
						<?php wp_nonce_field( 'ikon_seo_apply_seo_services_dubai' ); ?>
						<?php submit_button( $is_applied ? __( 'Re-apply Production Page', 'ikon-seo' ) : __( 'Apply Production Page', 'ikon-seo' ), 'primary', 'submit', false ); ?>
					</form>

					<?php if ( $page && $backup ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:10px;">
							<input type="hidden" name="action" value="ikon_seo_restore_seo_services_dubai">
							<?php wp_nonce_field( 'ikon_seo_restore_seo_services_dubai' ); ?>
							<?php submit_button( __( 'Restore Previous Version', 'ikon-seo' ), 'secondary', 'submit', false ); ?>
						</form>
					<?php endif; ?>

					<?php if ( $page ) : ?>
						<a class="button" href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open Page', 'ikon-seo' ); ?></a>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public function apply() {
		$this->guard_action( 'ikon_seo_apply_seo_services_dubai' );

		if ( ! $this->allowed_site() ) {
			$this->redirect_error( 'Production rollout blocked: this build is restricted to ikondigitals.com.' );
		}

		$content = $this->template_content();
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
					'post_excerpt' => 'SEO services in Dubai covering technical SEO, commercial pages, local search, content, authority and AI visibility.',
				),
				true
			);
			if ( is_wp_error( $page_id ) ) {
				$this->redirect_error( $page_id->get_error_message() );
			}
			$page = get_post( $page_id );
		} else {
			$this->create_backup( $page );

			$updated = wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_title'   => 'SEO Services in Dubai',
					'post_content' => $content,
					'post_excerpt' => 'SEO services in Dubai covering technical SEO, commercial pages, local search, content, authority and AI visibility.',
					'post_status'  => $page->post_status,
				),
				true
			);
			if ( is_wp_error( $updated ) ) {
				$this->redirect_error( $updated->get_error_message() );
			}
		}

		$this->clear_builder_override( $page->ID );
		$this->apply_seo_meta( $page->ID );
		update_post_meta( $page->ID, self::VERSION_META, self::PAGE_VERSION );
		update_post_meta( $page->ID, self::APPLIED_META, current_time( 'mysql', true ) );
		clean_post_cache( $page->ID );
		$this->purge_caches( $page->ID );

		wp_safe_redirect( $this->admin_url( array( 'ikon-production-status' => 'applied' ) ) );
		exit;
	}

	public function restore() {
		$this->guard_action( 'ikon_seo_restore_seo_services_dubai' );

		if ( ! $this->allowed_site() ) {
			$this->redirect_error( 'Production rollback blocked: this build is restricted to ikondigitals.com.' );
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

		wp_safe_redirect( $this->admin_url( array( 'ikon-production-status' => 'restored' ) ) );
		exit;
	}

	private function template_content() {
		$file = IKON_SEO_DIR . self::TEMPLATE_FILE;
		if ( ! is_readable( $file ) ) {
			return new WP_Error( 'ikon_seo_production_template_missing', 'The SEO Services Dubai production template is missing.' );
		}

		$content = (string) file_get_contents( $file );
		if ( '' === trim( $content ) ) {
			return new WP_Error( 'ikon_seo_production_template_empty', 'The SEO Services Dubai production template is empty.' );
		}

		$contact_url = $this->page_url( 'contact', home_url( '/contact/' ) );
		$results_url = $this->page_url( 'results', home_url( '/results/' ) );

		return strtr(
			$content,
			array(
				'{{CONTACT_URL}}' => esc_url( $contact_url ),
				'{{RESULTS_URL}}' => esc_url( $results_url ),
			)
		);
	}

	private function page_url( $slug, $fallback ) {
		$page = get_page_by_path( sanitize_title( $slug ), OBJECT, 'page' );
		return $page ? get_permalink( $page ) : $fallback;
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
			$exists      = metadata_exists( 'post', $page->ID, $key );
			$meta[ $key ] = $exists ? get_post_meta( $page->ID, $key, true ) : null;
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
			update_post_meta( $page_id, 'rank_math_title', 'SEO Services in Dubai | Ikon Digitals' );
			update_post_meta( $page_id, 'rank_math_description', 'SEO services in Dubai covering technical SEO, commercial pages, local search, content, authority and AI visibility, backed by documented results.' );
			update_post_meta( $page_id, 'rank_math_focus_keyword', 'SEO services Dubai' );
			update_post_meta( $page_id, 'rank_math_canonical_url', home_url( '/' . self::PAGE_SLUG . '/' ) );
		}
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
			wp_die( esc_html__( 'You do not have permission to manage production pages.', 'ikon-seo' ) );
		}
		check_admin_referer( $nonce_action );
	}

	private function redirect_error( $message ) {
		wp_safe_redirect( $this->admin_url( array( 'ikon-production-error' => rawurlencode( (string) $message ) ) ) );
		exit;
	}

	private function admin_url( array $args = array() ) {
		$url = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		return $args ? add_query_arg( $args, $url ) : $url;
	}
}
