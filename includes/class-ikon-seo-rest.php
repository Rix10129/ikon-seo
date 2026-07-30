<?php

defined( 'ABSPATH' ) || exit;

class Ikon_SEO_REST {
	const NAMESPACE = 'ikon-seo/v1';

	private $auth;
	private $connection;
	private $profile;
	private $validator;
	private $renderer;
	private $schema;
	private $quality;
	private $inventory;
	private $media;
	private $workflow;
	private $migration;
	private $search_console;
	private $queue;
	private $monitor;
	private $local;
	private $gbp;
	private $logger;

	public function __construct(
		Ikon_SEO_Auth $auth,
		Ikon_SEO_Connection $connection,
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Validator $validator,
		Ikon_SEO_Renderer $renderer,
		Ikon_SEO_Schema $schema,
		Ikon_SEO_Quality $quality,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Media $media,
		Ikon_SEO_Workflow $workflow,
		Ikon_SEO_Migration $migration,
		Ikon_SEO_Search_Console $search_console,
		Ikon_SEO_Queue $queue,
		Ikon_SEO_Monitor $monitor,
		Ikon_SEO_Local $local,
		Ikon_SEO_GBP $gbp,
		Ikon_SEO_Logger $logger
	) {
		$this->auth       = $auth;
		$this->connection = $connection;
		$this->profile   = $profile;
		$this->validator = $validator;
		$this->renderer  = $renderer;
		$this->schema    = $schema;
		$this->quality   = $quality;
		$this->inventory = $inventory;
		$this->media     = $media;
		$this->workflow  = $workflow;
		$this->migration = $migration;
		$this->search_console = $search_console;
		$this->queue          = $queue;
		$this->monitor        = $monitor;
		$this->local          = $local;
		$this->gbp            = $gbp;
		$this->logger    = $logger;

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		$this->route( '/openapi', WP_REST_Server::READABLE, 'openapi', '__return_true' );
		$this->route( '/pair', WP_REST_Server::CREATABLE, 'pair', '__return_true' );
		$this->route( '/health', WP_REST_Server::READABLE, 'health', array( $this->auth, 'can_read' ) );
		$this->route( '/profile', WP_REST_Server::READABLE, 'profile', array( $this->auth, 'can_read' ) );
		$this->route( '/profile/export', WP_REST_Server::READABLE, 'profile_export', array( $this->auth, 'can_read' ) );
		$this->route( '/schema/preview', WP_REST_Server::CREATABLE, 'schema_preview', array( $this->auth, 'can_read' ) );
		$this->route( '/domain-migration', WP_REST_Server::READABLE, 'domain_migration_report', array( $this->auth, 'can_read' ) );

		register_rest_route(
			self::NAMESPACE,
			'/pages',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_pages' ),
					'permission_callback' => array( $this->auth, 'can_read' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_page' ),
					'permission_callback' => array( $this->auth, 'can_draft' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/pages/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_page' ),
					'permission_callback' => array( $this->auth, 'can_read' ),
				),
				array(
					'methods'             => array( 'PUT', 'PATCH' ),
					'callback'            => array( $this, 'update_page' ),
					'permission_callback' => array( $this->auth, 'can_draft' ),
				),
			)
		);

		$this->route( '/inventory', WP_REST_Server::READABLE, 'inventory', array( $this->auth, 'can_read' ) );
		$this->route( '/internal-links', WP_REST_Server::READABLE, 'internal_links', array( $this->auth, 'can_read' ) );

		register_rest_route(
			self::NAMESPACE,
			'/media',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'media_search' ),
					'permission_callback' => array( $this->auth, 'can_read' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'media_import' ),
					'permission_callback' => array( $this->auth, 'can_draft' ),
				),
			)
		);

		$this->route( '/reviews', WP_REST_Server::READABLE, 'reviews', array( $this->auth, 'can_read' ) );
		$this->route( '/reviews/(?P<id>\d+)/comparison', WP_REST_Server::READABLE, 'comparison', array( $this->auth, 'can_read' ) );
		$this->route( '/reviews/(?P<id>\d+)/merge', WP_REST_Server::CREATABLE, 'merge_review', array( $this->auth, 'can_approve' ) );
		$this->route( '/pages/(?P<id>\d+)/snapshots', WP_REST_Server::READABLE, 'snapshots', array( $this->auth, 'can_read' ) );
		$this->route( '/pages/(?P<id>\d+)/rollback', WP_REST_Server::CREATABLE, 'rollback', array( $this->auth, 'can_approve' ) );
		$this->route( '/logs', WP_REST_Server::READABLE, 'logs', array( $this->auth, 'can_read' ) );
		$this->route( '/search-console/status', WP_REST_Server::READABLE, 'search_console_status', array( $this->auth, 'can_read' ) );
		$this->route( '/search-console/performance', WP_REST_Server::READABLE, 'search_console_performance', array( $this->auth, 'can_read' ) );
		$this->route( '/search-console/inspect', WP_REST_Server::READABLE, 'search_console_inspect', array( $this->auth, 'can_read' ) );
		$this->route( '/search-console/sitemaps', WP_REST_Server::READABLE, 'search_console_sitemaps', array( $this->auth, 'can_read' ) );
		$this->route( '/queue', WP_REST_Server::READABLE, 'queue_items', array( $this->auth, 'can_read' ) );
		$this->route( '/queue/(?P<id>\d+)/claim', WP_REST_Server::CREATABLE, 'queue_claim', array( $this->auth, 'can_draft' ) );
		$this->route( '/queue/(?P<id>\d+)/complete', WP_REST_Server::CREATABLE, 'queue_complete', array( $this->auth, 'can_draft' ) );
		$this->route( '/monitoring', WP_REST_Server::READABLE, 'monitoring', array( $this->auth, 'can_read' ) );
		$this->route( '/local', WP_REST_Server::READABLE, 'local_summary', array( $this->auth, 'can_read' ) );
		$this->route( '/local/locations', WP_REST_Server::READABLE, 'local_locations', array( $this->auth, 'can_read' ) );
		$this->route( '/local/nap-audit', WP_REST_Server::READABLE, 'local_nap_audit', array( $this->auth, 'can_read' ) );
		$this->route( '/local/citations', WP_REST_Server::READABLE, 'local_citations', array( $this->auth, 'can_read' ) );
		$this->route( '/local/ranks', WP_REST_Server::READABLE, 'local_ranks', array( $this->auth, 'can_read' ) );
		$this->route( '/local/utm', WP_REST_Server::CREATABLE, 'local_utm', array( $this->auth, 'can_read' ) );
		$this->route( '/local/gbp/status', WP_REST_Server::READABLE, 'gbp_status', array( $this->auth, 'can_read' ) );
		$this->route( '/local/gbp/comparison', WP_REST_Server::READABLE, 'gbp_comparison', array( $this->auth, 'can_read' ) );
		$this->route( '/local/gbp/reviews', WP_REST_Server::READABLE, 'gbp_reviews', array( $this->auth, 'can_read' ) );
		$this->route( '/local/gbp/performance', WP_REST_Server::READABLE, 'gbp_performance', array( $this->auth, 'can_read' ) );
		$this->route( '/local/gbp/search-keywords', WP_REST_Server::READABLE, 'gbp_search_keywords', array( $this->auth, 'can_read' ) );
		register_rest_route(
			self::NAMESPACE,
			'/local/gbp/drafts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'gbp_drafts' ),
					'permission_callback' => array( $this->auth, 'can_read' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'gbp_stage_draft' ),
					'permission_callback' => array( $this->auth, 'can_draft' ),
				),
			)
		);
	}

	private function route( $route, $methods, $callback, $permission ) {
		register_rest_route(
			self::NAMESPACE,
			$route,
			array(
				'methods'             => $methods,
				'callback'            => array( $this, $callback ),
				'permission_callback' => $permission,
			)
		);
	}

	public function openapi() {
		return rest_ensure_response( $this->openapi_schema() );
	}

	public function pair( WP_REST_Request $request ) {
		$ip       = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
		$rate_key = 'ikon_seo_pair_attempt_' . substr( hash( 'sha256', $ip ), 0, 20 );
		$attempts = (int) get_transient( $rate_key );
		if ( $attempts >= 20 ) {
			return new WP_Error(
				'ikon_seo_pair_rate_limited',
				__( 'Too many pairing attempts. Try again later.', 'ikon-seo' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );

		$result = $this->connection->exchange( $request->get_param( 'code' ) );
		if ( is_wp_error( $result ) ) {
			$this->logger->log( 'pairing', 'failed', $result->get_error_message() );
			return $result;
		}

		$this->logger->log( 'pairing', 'success', 'One-time website pairing completed.' );
		$response = rest_ensure_response( $result );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		return $response;
	}

	public function health() {
		$settings = Ikon_SEO_Plugin::settings();
		$profile  = $this->profile->get();
		$gsc      = $this->search_console->status();
		$gbp      = $this->gbp->status();
		$local    = $this->local->summary();
		return rest_ensure_response(
			array(
				'ok'              => true,
				'plugin'          => 'Ikon SEO',
				'version'         => IKON_SEO_VERSION,
				'site_name'       => $settings['site_name'],
				'site_url'        => home_url( '/' ),
				'builder'         => $profile['builder'],
				'seo_plugin'      => $profile['seo_plugin'],
				'default_status'  => $settings['draft_only'] ? 'draft' : 'configurable',
				'language'        => $settings['default_language'],
				'target_market'   => $settings['target_market'],
				'profile_id'      => $profile['profile_id'],
				'profile_ready'   => $profile['configured'],
				'industry'        => $profile['industry'],
				'business_entity' => $profile['business_entity_type'],
				'key_scopes'      => array_values( (array) $settings['key_scopes'] ),
				'remote_merge'    => (bool) $settings['remote_merge'],
				'component_version'=> $settings['component_version'],
				'search_console'   => array(
					'connected' => $gsc['connected'],
					'property'  => $gsc['property'],
					'read_only' => true,
					'last_sync' => $gsc['last_sync'],
				),
				'queue_counts'     => $this->queue->counts(),
				'monitoring_enabled'=> (bool) $settings['monitoring_enabled'],
				'local_seo'         => array(
					'enabled'            => (bool) $settings['local_module_enabled'],
					'locations'          => $local['locations'],
					'verified_locations' => $local['verified_locations'],
					'nap_status'         => $local['nap_audit']['status'],
				),
				'business_profile'  => array(
					'connected'        => $gbp['connected'],
					'linked_locations' => $gbp['linked_locations'],
					'policy_mode'      => $gbp['policy_mode'],
					'remote_mutations' => false,
				),
				'features'        => array(
					'create',
					'improve-copy',
					'universal-components-v3',
					'rank-math-schema-merge',
					'site-inventory',
					'internal-link-discovery',
					'quality-report',
					'media-library',
					'comparison',
					'admin-merge',
					'rollback-snapshots',
					'idempotency',
					'scoped-key',
					'universal-website-profile',
					'profile-bound-writes',
					'dynamic-schema-policy',
					'profile-import-export',
					'schema-preview',
					'domain-migration-preview',
					'search-console-readonly',
					'search-performance-comparison',
					'url-index-inspection',
					'csv-page-plan-queue',
					'queue-claim-locks',
					'content-refresh-monitor',
					'daily-monitoring-cron',
					'multi-location-profiles',
					'service-area-safeguards',
					'nap-consistency-audit',
					'local-page-quality-gate',
					'doorway-page-similarity-check',
					'localbusiness-schema-policy',
					'citation-workspace',
					'utm-builder',
					'local-rank-import-workspace',
					'gbp-read-insights',
					'gbp-review-monitoring',
					'gbp-performance-reporting',
					'gbp-admin-approved-drafts',
				),
			)
		);
	}

	public function profile() {
		return rest_ensure_response( $this->profile->get() );
	}

	public function profile_export() {
		return rest_ensure_response( $this->profile->export() );
	}

	public function schema_preview( WP_REST_Request $request ) {
		$payload = (array) $request->get_json_params();
		$valid   = $this->validator->validate_page_payload( $payload );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		return rest_ensure_response( $this->schema->preview( $payload ) );
	}

	public function domain_migration_report( WP_REST_Request $request ) {
		$result = $this->migration->report(
			sanitize_text_field( (string) $request->get_param( 'old_url' ) ),
			sanitize_text_field( (string) $request->get_param( 'new_url' ) )
		);
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function list_pages( WP_REST_Request $request ) {
		$search   = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$status   = sanitize_key( (string) $request->get_param( 'status' ) );
		$per_page = max( 1, min( 50, absint( $request->get_param( 'per_page' ) ?: 20 ) ) );
		$statuses = array( 'publish', 'draft', 'pending', 'future', 'private' );
		$query    = new WP_Query(
			array(
				'post_type'      => 'page',
				'post_status'    => in_array( $status, $statuses, true ) ? $status : $statuses,
				's'              => $search,
				'posts_per_page' => $per_page,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => false,
			)
		);

		return rest_ensure_response(
			array(
				'items' => array_map( array( $this, 'page_summary' ), $query->posts ),
				'total' => (int) $query->found_posts,
			)
		);
	}

	public function get_page( WP_REST_Request $request ) {
		$post = get_post( absint( $request['id'] ) );
		if ( ! $post || 'page' !== $post->post_type ) {
			return new WP_Error( 'ikon_seo_page_not_found', 'Page not found.', array( 'status' => 404 ) );
		}

		$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
		return rest_ensure_response(
			array_merge(
				$this->page_summary( $post ),
				array(
					'excerpt'        => $post->post_excerpt,
					'content_html'   => wp_kses_post( $post->post_content ),
					'content_blocks' => $this->renderer->extract_readable_blocks( $elementor_data ),
					'seo'            => $this->rank_math_meta( $post->ID ),
					'schema_types'   => $this->schema->types_for_post( $post->ID ),
					'quality_report' => get_post_meta( $post->ID, '_ikon_seo_quality_report', true ),
					'content_review' => get_post_meta( $post->ID, '_ikon_seo_content_review', true ),
					'last_reviewed'  => get_post_meta( $post->ID, '_ikon_seo_last_reviewed', true ),
					'next_review_date'=> get_post_meta( $post->ID, '_ikon_seo_next_review', true ),
					'featured_media' => $this->media->summary( get_post_thumbnail_id( $post->ID ) ),
					'source_page_id' => absint( get_post_meta( $post->ID, '_ikon_seo_source_page_id', true ) ),
					'workflow_status'=> get_post_meta( $post->ID, '_ikon_seo_workflow_status', true ),
					'profile_id'     => get_post_meta( $post->ID, '_ikon_seo_profile_id', true ),
				)
			)
		);
	}

	public function create_page( WP_REST_Request $request ) {
		$replay = $this->idempotent_replay( $request );
		if ( $replay ) {
			return rest_ensure_response( $replay );
		}

		$result = $this->create_from_payload( (array) $request->get_json_params(), $this->request_id( $request ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->store_idempotent_result( $request, $result );
		return new WP_REST_Response( $result, 201 );
	}

	private function create_from_payload( array $payload, $request_id ) {
		$mode       = sanitize_key( $payload['mode'] ?? 'create' );
		$source     = null;

		if ( 'improve' === $mode ) {
			$source = get_post( absint( $payload['source_page_id'] ?? 0 ) );
			if ( ! $source || 'page' !== $source->post_type ) {
				$this->logger->log( 'improve', 'failed', 'Source page not found.', null, $payload['source_page_id'] ?? null, $payload, $request_id );
				return new WP_Error( 'ikon_seo_source_not_found', 'The source page for improvement was not found.', array( 'status' => 404 ) );
			}
			$payload['title']   = ! empty( $payload['title'] ) ? $payload['title'] : $source->post_title;
			$payload['excerpt'] = isset( $payload['excerpt'] ) ? $payload['excerpt'] : $source->post_excerpt;
		}

		$valid = $this->validator->validate_page_payload( $payload );
		if ( is_wp_error( $valid ) ) {
			$this->logger->log( $mode, 'failed', $valid->get_error_message(), null, $source ? $source->ID : null, $payload, $request_id );
			return $valid;
		}

		$slug = sanitize_title( $payload['slug'] ?? $payload['title'] );
		if ( 'create' === $mode ) {
			$duplicate = $this->duplicate_page( $payload['title'], $slug );
			if ( $duplicate ) {
				return new WP_Error(
					'ikon_seo_duplicate_page',
					'A page already uses this title or slug. Use improve mode or choose a different target.',
					array(
						'status'           => 409,
						'existing_page_id' => $duplicate,
						'existing_url'     => get_permalink( $duplicate ),
					)
				);
			}
		} else {
			$slug .= '-seo-review-' . gmdate( 'Ymd-His' );
		}

		$result = $this->save_page( 0, $payload, $slug, $source, $request_id );
		if ( is_wp_error( $result ) ) {
			$this->logger->log( $mode, 'failed', $result->get_error_message(), null, $source ? $source->ID : null, $payload, $request_id );
			return $result;
		}

		$this->logger->log( $mode, 'success', 'Page draft created.', $result['id'], $source ? $source->ID : null, $payload, $request_id );
		return $result;
	}

	public function update_page( WP_REST_Request $request ) {
		$replay = $this->idempotent_replay( $request );
		if ( $replay ) {
			return rest_ensure_response( $replay );
		}

		$post = get_post( absint( $request['id'] ) );
		if ( ! $post || 'page' !== $post->post_type ) {
			return new WP_Error( 'ikon_seo_page_not_found', 'Page not found.', array( 'status' => 404 ) );
		}

		$settings = Ikon_SEO_Plugin::settings();
		$managed  = (bool) get_post_meta( $post->ID, '_ikon_seo_managed', true );
		if ( 'publish' === $post->post_status && ( $settings['draft_only'] || ! $settings['allow_live_updates'] ) ) {
			return new WP_Error( 'ikon_seo_live_update_blocked', 'Published pages cannot be changed directly. Use improve mode.', array( 'status' => 403 ) );
		}
		if ( ! $managed ) {
			return new WP_Error( 'ikon_seo_unmanaged_draft', 'Only Ikon SEO managed drafts can be updated remotely.', array( 'status' => 403 ) );
		}

		$payload          = (array) $request->get_json_params();
		$payload['title'] = ! empty( $payload['title'] ) ? $payload['title'] : $post->post_title;
		$valid            = $this->validator->validate_page_payload( $payload, true );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$request_id = $this->request_id( $request );
		$slug       = ! empty( $payload['slug'] ) ? sanitize_title( $payload['slug'] ) : $post->post_name;
		$result     = $this->save_page( $post->ID, $payload, $slug, null, $request_id );
		if ( is_wp_error( $result ) ) {
			$this->logger->log( 'update', 'failed', $result->get_error_message(), $post->ID, null, $payload, $request_id );
			return $result;
		}

		$this->logger->log( 'update', 'success', 'Draft updated.', $post->ID, null, $payload, $request_id );
		$this->store_idempotent_result( $request, $result );
		return rest_ensure_response( $result );
	}

	public function inventory( WP_REST_Request $request ) {
		return rest_ensure_response( $this->inventory->scan( (bool) $request->get_param( 'refresh' ) ) );
	}

	public function internal_links( WP_REST_Request $request ) {
		$query   = sanitize_text_field( (string) $request->get_param( 'query' ) );
		$exclude = absint( $request->get_param( 'exclude_id' ) );
		$limit   = absint( $request->get_param( 'limit' ) ?: 12 );
		return rest_ensure_response( array( 'items' => $this->inventory->candidates( $query, $exclude, $limit ) ) );
	}

	public function media_search( WP_REST_Request $request ) {
		return rest_ensure_response(
			array(
				'items' => $this->media->search(
					sanitize_text_field( (string) $request->get_param( 'search' ) ),
					absint( $request->get_param( 'limit' ) ?: 20 )
				),
			)
		);
	}

	public function media_import( WP_REST_Request $request ) {
		$replay = $this->idempotent_replay( $request );
		if ( $replay ) {
			return rest_ensure_response( $replay );
		}
		$result = $this->media->import( (array) $request->get_json_params() );
		if ( ! is_wp_error( $result ) ) {
			$this->logger->log( 'media', 'success', 'Approved image imported into the Media Library.', $result['id'], null, array(), $this->request_id( $request ) );
			$this->store_idempotent_result( $request, $result );
		}
		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 201 );
	}

	public function reviews( WP_REST_Request $request ) {
		return rest_ensure_response( array( 'items' => $this->workflow->reviews( absint( $request->get_param( 'limit' ) ?: 50 ) ) ) );
	}

	public function comparison( WP_REST_Request $request ) {
		$result = $this->workflow->comparison( absint( $request['id'] ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function merge_review( WP_REST_Request $request ) {
		$replay = $this->idempotent_replay( $request );
		if ( $replay ) {
			return rest_ensure_response( $replay );
		}
		$result = $this->workflow->merge( absint( $request['id'] ), $this->request_id( $request ) );
		if ( ! is_wp_error( $result ) ) {
			$this->inventory->clear_cache();
			$this->store_idempotent_result( $request, $result );
		}
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function snapshots( WP_REST_Request $request ) {
		return rest_ensure_response( array( 'items' => $this->workflow->snapshots( absint( $request['id'] ) ) ) );
	}

	public function rollback( WP_REST_Request $request ) {
		$replay = $this->idempotent_replay( $request );
		if ( $replay ) {
			return rest_ensure_response( $replay );
		}
		$payload = (array) $request->get_json_params();
		$result  = $this->workflow->rollback(
			absint( $request['id'] ),
			sanitize_text_field( $payload['snapshot_id'] ?? '' ),
			$this->request_id( $request )
		);
		if ( ! is_wp_error( $result ) ) {
			$this->inventory->clear_cache();
			$this->store_idempotent_result( $request, $result );
		}
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function logs( WP_REST_Request $request ) {
		$limit = max( 1, min( 100, absint( $request->get_param( 'limit' ) ?: 25 ) ) );
		return rest_ensure_response( array( 'items' => $this->logger->recent( $limit ) ) );
	}

	public function search_console_status() {
		return rest_ensure_response( $this->search_console->status() );
	}

	public function search_console_performance( WP_REST_Request $request ) {
		$result = $this->search_console->performance(
			absint( $request->get_param( 'days' ) ?: 28 ),
			(bool) $request->get_param( 'refresh' )
		);
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function search_console_inspect( WP_REST_Request $request ) {
		$result = $this->search_console->inspect_url( (string) $request->get_param( 'url' ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function search_console_sitemaps() {
		$result = $this->search_console->sitemaps();
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function queue_items( WP_REST_Request $request ) {
		return rest_ensure_response(
			array(
				'counts' => $this->queue->counts(),
				'items'  => $this->queue->list_items(
					sanitize_key( (string) $request->get_param( 'status' ) ),
					absint( $request->get_param( 'limit' ) ?: 50 ),
					sanitize_text_field( (string) $request->get_param( 'batch_id' ) )
				),
			)
		);
	}

	public function queue_claim( WP_REST_Request $request ) {
		$replay = $this->idempotent_replay( $request );
		if ( $replay ) {
			return rest_ensure_response( $replay );
		}
		$result = $this->queue->claim( absint( $request['id'] ) );
		if ( ! is_wp_error( $result ) ) {
			$this->logger->log( 'queue_claim', 'success', 'Page plan claimed for generation.', null, null, array(), $this->request_id( $request ) );
			$this->store_idempotent_result( $request, $result );
		}
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function queue_complete( WP_REST_Request $request ) {
		$replay = $this->idempotent_replay( $request );
		if ( $replay ) {
			return rest_ensure_response( $replay );
		}

		$body  = (array) $request->get_json_params();
		$item  = $this->queue->verify_claim( absint( $request['id'] ), sanitize_text_field( $body['claim_token'] ?? '' ) );
		if ( is_wp_error( $item ) ) {
			return $item;
		}
		if ( empty( $body['page'] ) || ! is_array( $body['page'] ) ) {
			return new WP_Error( 'ikon_seo_queue_payload', 'A complete page payload is required.', array( 'status' => 400 ) );
		}

		$payload = $this->queue->apply_plan_to_payload( $item, $body['page'] );
		$result  = $this->create_from_payload( $payload, $this->request_id( $request ) );
		if ( is_wp_error( $result ) ) {
			$this->queue->fail( $item['id'], $result->get_error_message() );
			$this->logger->log( 'queue_complete', 'failed', $result->get_error_message(), null, null, $payload, $this->request_id( $request ) );
			return $result;
		}

		$queue_item           = $this->queue->complete( $item['id'], $result['id'] );
		$result['queue_item'] = $queue_item;
		update_post_meta( $result['id'], '_ikon_seo_queue_id', absint( $item['id'] ) );
		update_post_meta( $result['id'], '_ikon_seo_queue_batch', sanitize_text_field( $item['batch_id'] ) );
		$this->logger->log( 'queue_complete', 'success', 'Page plan completed as a WordPress draft.', $result['id'], null, $payload, $this->request_id( $request ) );
		$this->store_idempotent_result( $request, $result );
		return new WP_REST_Response( $result, 201 );
	}

	public function monitoring() {
		return rest_ensure_response( $this->monitor->summary() );
	}

	public function local_summary() {
		return rest_ensure_response( $this->local->summary() );
	}

	public function local_locations() {
		return rest_ensure_response( array( 'items' => $this->local->locations() ) );
	}

	public function local_nap_audit() {
		return rest_ensure_response( $this->local->nap_audit() );
	}

	public function local_citations( WP_REST_Request $request ) {
		return rest_ensure_response( array( 'items' => $this->local->citations( absint( $request->get_param( 'limit' ) ?: 100 ) ) ) );
	}

	public function local_ranks( WP_REST_Request $request ) {
		return rest_ensure_response( array( 'items' => $this->local->rank_entries( absint( $request->get_param( 'limit' ) ?: 100 ) ) ) );
	}

	public function local_utm( WP_REST_Request $request ) {
		$result = $this->local->utm_url( (array) $request->get_json_params() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function gbp_status() {
		return rest_ensure_response( $this->gbp->status() );
	}

	public function gbp_comparison( WP_REST_Request $request ) {
		$result = $this->gbp->comparison( absint( $request->get_param( 'location_id' ) ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function gbp_reviews( WP_REST_Request $request ) {
		$result = $this->gbp->reviews( absint( $request->get_param( 'location_id' ) ), (bool) $request->get_param( 'refresh' ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function gbp_performance( WP_REST_Request $request ) {
		$result = $this->gbp->performance(
			absint( $request->get_param( 'location_id' ) ),
			absint( $request->get_param( 'days' ) ?: 30 ),
			(bool) $request->get_param( 'refresh' )
		);
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function gbp_search_keywords( WP_REST_Request $request ) {
		$result = $this->gbp->search_keywords(
			absint( $request->get_param( 'location_id' ) ),
			absint( $request->get_param( 'months' ) ?: 3 ),
			(bool) $request->get_param( 'refresh' )
		);
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function gbp_drafts( WP_REST_Request $request ) {
		return rest_ensure_response( array( 'items' => $this->gbp->drafts( absint( $request->get_param( 'limit' ) ?: 100 ) ) ) );
	}

	public function gbp_stage_draft( WP_REST_Request $request ) {
		$replay = $this->idempotent_replay( $request );
		if ( $replay ) {
			return rest_ensure_response( $replay );
		}
		$result = $this->gbp->stage_draft( (array) $request->get_json_params(), 0 );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$this->store_idempotent_result( $request, $result );
		return new WP_REST_Response( $result, 201 );
	}

	private function save_page( $post_id, array $payload, $slug, $source, $request_id ) {
		$settings = Ikon_SEO_Plugin::settings();
		$rendered = $this->renderer->render( $payload );
		$status   = sanitize_key( $payload['status'] ?? 'draft' );
		foreach ( array( 'featured_media_id', 'social_media_id' ) as $media_key ) {
			if ( ! empty( $payload[ $media_key ] ) && ! $this->media->valid_image_id( absint( $payload[ $media_key ] ) ) ) {
				return new WP_Error( 'ikon_seo_media_id', $media_key . ' is not a valid image attachment.', array( 'status' => 400 ) );
			}
		}
		if ( $settings['draft_only'] || $source ) {
			$status = 'draft';
		} elseif ( ! in_array( $status, array( 'draft', 'pending', 'publish', 'future' ), true ) ) {
			$status = 'draft';
		}

		$postarr = array(
			'ID'           => $post_id ? absint( $post_id ) : 0,
			'post_type'    => 'page',
			'post_title'   => sanitize_text_field( $payload['title'] ),
			'post_name'    => $slug,
			'post_excerpt' => sanitize_textarea_field( $payload['excerpt'] ?? '' ),
			'post_content' => $rendered['post_content'],
			'post_status'  => $status,
			'post_parent'  => absint( $payload['parent_id'] ?? 0 ),
			'menu_order'   => intval( $payload['menu_order'] ?? 0 ),
		);
		if ( ! $post_id ) {
			$postarr['post_author'] = $source ? absint( $source->post_author ) : absint( $settings['author_id'] );
		}

		$saved_id = wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $saved_id ) ) {
			return $saved_id;
		}

		update_post_meta( $saved_id, '_ikon_seo_managed', 1 );
		update_post_meta( $saved_id, '_ikon_seo_request_id', $request_id );
		update_post_meta( $saved_id, '_ikon_seo_profile_id', $this->profile->fingerprint() );
		update_post_meta( $saved_id, '_ikon_seo_language', sanitize_text_field( $payload['language'] ?? $settings['default_language'] ) );
		update_post_meta( $saved_id, '_ikon_seo_payload_version', '5.0' );
		update_post_meta( $saved_id, '_ikon_seo_component_version', sanitize_text_field( $settings['component_version'] ) );
		update_post_meta( $saved_id, '_ikon_seo_workflow_status', $source ? 'awaiting_review' : 'draft' );
		$builder = $settings['builder_preference'];
		if ( 'auto' === $builder ) {
			$builder = defined( 'ELEMENTOR_VERSION' ) ? 'elementor' : 'gutenberg';
		}
		update_post_meta( $saved_id, '_ikon_seo_builder', $builder );
		if ( 'elementor' === $builder ) {
			update_post_meta( $saved_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $saved_id, '_elementor_template_type', 'wp-page' );
			update_post_meta( $saved_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
			update_post_meta( $saved_id, '_elementor_data', wp_slash( wp_json_encode( $rendered['elementor_data'] ) ) );
			update_post_meta( $saved_id, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
			delete_post_meta( $saved_id, '_elementor_css' );
		} else {
			foreach ( array( '_elementor_edit_mode', '_elementor_template_type', '_elementor_version', '_elementor_data', '_elementor_page_settings', '_elementor_css' ) as $elementor_key ) {
				delete_post_meta( $saved_id, $elementor_key );
			}
		}

		$template = sanitize_key( $payload['page_template'] ?? 'default' );
		if ( in_array( $template, array( 'default', 'elementor_canvas', 'elementor_header_footer' ), true ) ) {
			update_post_meta( $saved_id, '_wp_page_template', $template );
		}
		if ( $source ) {
			update_post_meta( $saved_id, '_ikon_seo_source_page_id', $source->ID );
			update_post_meta( $saved_id, '_ikon_seo_source_url', get_permalink( $source ) );
		}

		$this->save_rank_math_meta( $saved_id, $payload['seo'] ?? array() );
		$media_result = $this->apply_media( $saved_id, $payload );
		if ( is_wp_error( $media_result ) ) {
			if ( ! $post_id ) {
				wp_delete_post( $saved_id, true );
			}
			return $media_result;
		}

		$content_review = $this->sanitize_content_review( $payload['content_review'] ?? array() );
		update_post_meta( $saved_id, '_ikon_seo_content_review', $content_review );
		$this->monitor->sync_post_review( $saved_id, $content_review );
		$this->local->bind_page( $saved_id, $payload );

		$schema_graph = $this->schema->build( $payload, $saved_id );
		update_post_meta( $saved_id, '_ikon_seo_schema_graph', $schema_graph );
		delete_post_meta( $saved_id, '_ikon_seo_schema' );

		$quality = $this->quality->audit_payload( $payload, $rendered, $schema_graph );
		update_post_meta( $saved_id, '_ikon_seo_quality_report', $quality );
		$this->inventory->clear_cache();
		clean_post_cache( $saved_id );

		$saved = get_post( $saved_id );
		return array_merge(
			$this->page_summary( $saved ),
			array(
				'ok'                 => true,
				'request_id'         => $request_id,
				'edit_url'           => get_edit_post_link( $saved_id, 'raw' ),
				'elementor_edit_url' => 'elementor' === $builder ? admin_url( 'post.php?post=' . $saved_id . '&action=elementor' ) : '',
				'builder'            => $builder,
				'replacement_target' => $source ? array(
					'id'    => $source->ID,
					'title' => $source->post_title,
					'url'   => get_permalink( $source ),
				) : null,
				'quality_report'      => $quality,
				'schema_types'        => $this->schema->types_for_post( $saved_id ),
				'profile_id'          => $this->profile->fingerprint(),
				'featured_media'      => $this->media->summary( get_post_thumbnail_id( $saved_id ) ),
				'warnings'            => $this->compatibility_warnings(),
			)
		);
	}

	private function save_rank_math_meta( $post_id, $seo ) {
		$seo = is_array( $seo ) ? $seo : array();
		$settings = Ikon_SEO_Plugin::settings();
		$selected = $settings['seo_plugin_preference'];
		if ( 'auto' === $selected ) {
			$selected = defined( 'RANK_MATH_VERSION' ) ? 'rank_math' : ( defined( 'WPSEO_VERSION' ) ? 'yoast' : 'none' );
		}
		if ( 'rank_math' === $selected ) {
			$map = array(
				'title'               => 'rank_math_title',
				'description'         => 'rank_math_description',
				'focus_keyword'       => 'rank_math_focus_keyword',
				'canonical'           => 'rank_math_canonical_url',
				'facebook_title'      => 'rank_math_facebook_title',
				'facebook_description'=> 'rank_math_facebook_description',
				'facebook_image'      => 'rank_math_facebook_image',
				'twitter_title'       => 'rank_math_twitter_title',
				'twitter_description' => 'rank_math_twitter_description',
				'twitter_image'       => 'rank_math_twitter_image',
			);
			foreach ( $map as $input => $meta_key ) {
				if ( array_key_exists( $input, $seo ) ) {
					$value = false !== strpos( $input, 'image' ) || 'canonical' === $input ? esc_url_raw( $seo[ $input ] ) : sanitize_text_field( $seo[ $input ] );
					$value ? update_post_meta( $post_id, $meta_key, $value ) : delete_post_meta( $post_id, $meta_key );
				}
			}
			if ( isset( $seo['robots'] ) && is_array( $seo['robots'] ) ) {
				$allowed = array_intersect( array_map( 'sanitize_key', $seo['robots'] ), array( 'index', 'noindex', 'follow', 'nofollow', 'noarchive', 'nosnippet' ) );
				update_post_meta( $post_id, 'rank_math_robots', array_values( $allowed ) );
			}
			if ( array_key_exists( 'twitter_use_facebook', $seo ) ) {
				if ( ! empty( $seo['twitter_use_facebook'] ) ) {
					update_post_meta( $post_id, 'rank_math_twitter_use_facebook', 'on' );
				} else {
					delete_post_meta( $post_id, 'rank_math_twitter_use_facebook' );
				}
			}
		}
		if ( 'yoast' === $selected ) {
			$yoast_map = array(
				'title'         => '_yoast_wpseo_title',
				'description'   => '_yoast_wpseo_metadesc',
				'focus_keyword' => '_yoast_wpseo_focuskw',
				'canonical'     => '_yoast_wpseo_canonical',
			);
			foreach ( $yoast_map as $input => $meta_key ) {
				if ( array_key_exists( $input, $seo ) ) {
					$value = 'canonical' === $input ? esc_url_raw( $seo[ $input ] ) : sanitize_text_field( $seo[ $input ] );
					$value ? update_post_meta( $post_id, $meta_key, $value ) : delete_post_meta( $post_id, $meta_key );
				}
			}
			if ( isset( $seo['robots'] ) && is_array( $seo['robots'] ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', in_array( 'noindex', $seo['robots'], true ) ? '1' : '0' );
				update_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', in_array( 'nofollow', $seo['robots'], true ) ? '1' : '0' );
			}
		}
	}

	private function rank_math_meta( $post_id ) {
		$settings = Ikon_SEO_Plugin::settings();
		$selected = $settings['seo_plugin_preference'];
		if ( 'auto' === $selected ) {
			$selected = defined( 'RANK_MATH_VERSION' ) ? 'rank_math' : ( defined( 'WPSEO_VERSION' ) ? 'yoast' : 'rank_math' );
		}
		if ( 'yoast' === $selected ) {
			return array(
				'title'         => get_post_meta( $post_id, '_yoast_wpseo_title', true ),
				'description'   => get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ),
				'focus_keyword' => get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ),
				'canonical'     => get_post_meta( $post_id, '_yoast_wpseo_canonical', true ),
				'robots'        => array_filter( array(
					'1' === get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ) ? 'noindex' : 'index',
					'1' === get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true ) ? 'nofollow' : 'follow',
				) ),
			);
		}
		return array(
			'title'                => get_post_meta( $post_id, 'rank_math_title', true ),
			'description'          => get_post_meta( $post_id, 'rank_math_description', true ),
			'focus_keyword'        => get_post_meta( $post_id, 'rank_math_focus_keyword', true ),
			'canonical'            => get_post_meta( $post_id, 'rank_math_canonical_url', true ),
			'robots'               => get_post_meta( $post_id, 'rank_math_robots', true ),
			'facebook_title'       => get_post_meta( $post_id, 'rank_math_facebook_title', true ),
			'facebook_description' => get_post_meta( $post_id, 'rank_math_facebook_description', true ),
			'facebook_image'       => get_post_meta( $post_id, 'rank_math_facebook_image', true ),
			'twitter_title'        => get_post_meta( $post_id, 'rank_math_twitter_title', true ),
			'twitter_description'  => get_post_meta( $post_id, 'rank_math_twitter_description', true ),
			'twitter_image'        => get_post_meta( $post_id, 'rank_math_twitter_image', true ),
			'twitter_use_facebook' => (bool) get_post_meta( $post_id, 'rank_math_twitter_use_facebook', true ),
		);
	}

	private function apply_media( $post_id, array $payload ) {
		if ( array_key_exists( 'featured_media_id', $payload ) ) {
			$attachment_id = absint( $payload['featured_media_id'] );
			if ( $attachment_id && ! $this->media->valid_image_id( $attachment_id ) ) {
				return new WP_Error( 'ikon_seo_featured_media', 'featured_media_id is not a valid image attachment.', array( 'status' => 400 ) );
			}
			if ( $attachment_id ) {
				set_post_thumbnail( $post_id, $attachment_id );
			} else {
				delete_post_thumbnail( $post_id );
			}
		}
		if ( ! empty( $payload['social_media_id'] ) ) {
			$social_id = absint( $payload['social_media_id'] );
			if ( ! $this->media->valid_image_id( $social_id ) ) {
				return new WP_Error( 'ikon_seo_social_media', 'social_media_id is not a valid image attachment.', array( 'status' => 400 ) );
			}
			$url = wp_get_attachment_image_url( $social_id, 'full' );
			$selected = Ikon_SEO_Plugin::settings()['seo_plugin_preference'];
			if ( 'auto' === $selected ) {
				$selected = defined( 'RANK_MATH_VERSION' ) ? 'rank_math' : ( defined( 'WPSEO_VERSION' ) ? 'yoast' : 'none' );
			}
			if ( 'rank_math' === $selected ) {
				update_post_meta( $post_id, 'rank_math_facebook_image', esc_url_raw( $url ) );
				update_post_meta( $post_id, 'rank_math_twitter_image', esc_url_raw( $url ) );
			} elseif ( 'yoast' === $selected ) {
				update_post_meta( $post_id, '_yoast_wpseo_opengraph-image', esc_url_raw( $url ) );
				update_post_meta( $post_id, '_yoast_wpseo_twitter-image', esc_url_raw( $url ) );
			}
		}
		return true;
	}

	private function sanitize_content_review( $review ) {
		$review = is_array( $review ) ? $review : array();
		return array(
			'ymyl'              => ! empty( $review['ymyl'] ),
			'sources'           => array_values( array_filter( array_map( 'esc_url_raw', (array) ( $review['sources'] ?? array() ) ) ) ),
			'reviewed_by'       => sanitize_text_field( $review['reviewed_by'] ?? '' ),
			'fact_checked_date' => sanitize_text_field( $review['fact_checked_date'] ?? '' ),
			'applicable_period' => sanitize_text_field( $review['applicable_period'] ?? '' ),
			'jurisdiction'      => sanitize_text_field( $review['jurisdiction'] ?? '' ),
			'disclaimer'        => sanitize_textarea_field( $review['disclaimer'] ?? '' ),
			'next_review_date'  => sanitize_text_field( $review['next_review_date'] ?? '' ),
			'show_on_page'      => ! empty( $review['show_on_page'] ),
			'heading'           => sanitize_text_field( $review['heading'] ?? '' ),
		);
	}

	public function page_summary( $post ) {
		return array(
			'id'          => (int) $post->ID,
			'title'       => $post->post_title,
			'slug'        => $post->post_name,
			'status'      => $post->post_status,
			'url'         => get_permalink( $post ),
			'modified_gmt'=> get_post_modified_time( 'c', true, $post ),
			'managed'     => (bool) get_post_meta( $post->ID, '_ikon_seo_managed', true ),
		);
	}

	private function duplicate_page( $title, $slug ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing ) {
			return (int) $existing->ID;
		}
		$title_match = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private', 'trash' ),
				'title'          => sanitize_text_field( $title ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return $title_match ? absint( $title_match[0] ) : 0;
	}

	private function compatibility_warnings() {
		$warnings = array();
		$settings = Ikon_SEO_Plugin::settings();
		if ( 'elementor' === $settings['builder_preference'] && ! defined( 'ELEMENTOR_VERSION' ) ) {
			$warnings[] = 'The profile requires Elementor, but Elementor was not detected. Fallback WordPress content was still created.';
		}
		if ( 'rank_math' === $settings['seo_plugin_preference'] && ! defined( 'RANK_MATH_VERSION' ) ) {
			$warnings[] = 'The profile requires Rank Math, but Rank Math was not detected. Ikon SEO will print its allow-listed fallback schema graph.';
		}
		if ( 'yoast' === $settings['seo_plugin_preference'] && ! defined( 'WPSEO_VERSION' ) ) {
			$warnings[] = 'The profile requires Yoast, but Yoast was not detected.';
		}
		return $warnings;
	}

	private function request_id( WP_REST_Request $request ) {
		$value = sanitize_text_field( (string) $request->get_header( 'x-request-id' ) );
		if ( ! $value ) {
			$value = sanitize_text_field( (string) $request->get_header( 'x-idempotency-key' ) );
		}
		return $value ? substr( hash( 'sha256', $value ), 0, 64 ) : wp_generate_uuid4();
	}

	private function idempotency_cache_key( WP_REST_Request $request ) {
		$key = trim( (string) $request->get_header( 'x-idempotency-key' ) );
		if ( strlen( $key ) < 8 || strlen( $key ) > 128 ) {
			return '';
		}
		return 'ikon_seo_idem_' . hash( 'sha256', $request->get_method() . '|' . $request->get_route() . '|' . $key );
	}

	private function idempotent_replay( WP_REST_Request $request ) {
		$key = $this->idempotency_cache_key( $request );
		if ( ! $key ) {
			return false;
		}
		$result = get_transient( $key );
		if ( ! is_array( $result ) ) {
			return false;
		}
		$result['idempotent_replay'] = true;
		return $result;
	}

	private function store_idempotent_result( WP_REST_Request $request, array $result ) {
		$key = $this->idempotency_cache_key( $request );
		if ( $key ) {
			$ttl = false !== strpos( $request->get_route(), '/queue/' ) && false !== strpos( $request->get_route(), '/claim' )
				? HOUR_IN_SECONDS
				: DAY_IN_SECONDS;
			set_transient( $key, $result, $ttl );
		}
	}

	private function openapi_schema() {
		$idempotency = array(
			'name'        => 'X-Idempotency-Key',
			'in'          => 'header',
			'required'    => false,
			'description' => 'A unique 8–128 character value. Reuse it only when retrying the exact same write.',
			'schema'      => array( 'type' => 'string', 'minLength' => 8, 'maxLength' => 128 ),
		);
		$id_parameter = array( 'name' => 'id', 'in' => 'path', 'required' => true, 'schema' => array( 'type' => 'integer' ) );

		$document = array(
			'openapi' => '3.1.0',
			'info'    => array(
				'title'       => 'Ikon SEO',
				'version'     => IKON_SEO_VERSION,
				'description' => 'Audits, plans, measures, creates, improves and safely approves SEO-focused WordPress pages.',
			),
			'servers' => array( array( 'url' => untrailingslashit( rest_url( self::NAMESPACE ) ) ) ),
			'security'=> array(),
			'paths'   => array(
				'/pair' => array(
					'post' => array(
						'operationId'              => 'pairIkonSEOWebsite',
						'summary'                  => 'Exchange a short-lived one-time pairing code for the website connection package',
						'security'                 => array(),
						'x-openai-isConsequential' => true,
						'requestBody'              => array(
							'required' => true,
							'content'  => array(
								'application/json' => array(
									'schema' => array(
										'type'       => 'object',
										'required'   => array( 'code' ),
										'properties' => array( 'code' => array( 'type' => 'string', 'example' => 'ABCD-2345' ) ),
									),
								),
							),
						),
						'responses' => array( '200' => array( 'description' => 'One-time connection package' ) ),
					),
				),
				'/health' => array( 'get' => $this->operation( 'checkIkonSEOConnection', 'Check site capabilities' ) ),
				'/profile' => array( 'get' => $this->operation( 'readIkonSEOWebsiteProfile', 'Read the active website identity, schema policy and publishing rules before writing' ) ),
				'/profile/export' => array( 'get' => $this->operation( 'exportIkonSEOWebsiteProfile', 'Export the non-secret portable website profile' ) ),
				'/schema/preview' => array( 'post' => $this->write_operation( 'previewIkonSEOSchema', 'Preview profile-aware schema and conflict warnings without changing WordPress', '#/components/schemas/PagePayload', array(), false, false ) ),
				'/domain-migration' => array( 'get' => array_merge( $this->operation( 'previewIkonSEODomainMigration', 'Preview stored references affected by a domain change; applying remains administrator-only' ), array(
					'parameters' => array(
						array( 'name' => 'old_url', 'in' => 'query', 'required' => true, 'schema' => array( 'type' => 'string', 'format' => 'uri' ) ),
						array( 'name' => 'new_url', 'in' => 'query', 'required' => true, 'schema' => array( 'type' => 'string', 'format' => 'uri' ) ),
					),
				) ) ),
				'/pages'  => array(
					'get'  => array_merge( $this->operation( 'findWordPressPages', 'Find existing pages before creating content' ), array(
						'parameters' => array(
							array( 'name' => 'search', 'in' => 'query', 'schema' => array( 'type' => 'string' ) ),
							array( 'name' => 'status', 'in' => 'query', 'schema' => array( 'type' => 'string' ) ),
							array( 'name' => 'per_page', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'maximum' => 50 ) ),
						),
					) ),
					'post' => $this->write_operation( 'createOrImproveWordPressPage', 'Create a page or separate improvement draft', '#/components/schemas/PagePayload', array( $idempotency ) ),
				),
				'/pages/{id}' => array(
					'get'   => array_merge( $this->operation( 'readWordPressPage', 'Read page content, SEO, schema and quality data' ), array( 'parameters' => array( $id_parameter ) ) ),
					'patch' => $this->write_operation( 'updateIkonSEODraft', 'Update an Ikon SEO managed draft', '#/components/schemas/PagePayload', array( $id_parameter, $idempotency ) ),
				),
				'/inventory' => array( 'get' => array_merge( $this->operation( 'auditWordPressInventory', 'Audit pages, links, metadata, orphans and keyword overlap' ), array(
					'parameters' => array( array( 'name' => 'refresh', 'in' => 'query', 'schema' => array( 'type' => 'boolean' ) ) ),
				) ) ),
				'/internal-links' => array( 'get' => array_merge( $this->operation( 'findInternalLinkCandidates', 'Find confirmed internal-link destinations' ), array(
					'parameters' => array(
						array( 'name' => 'query', 'in' => 'query', 'required' => true, 'schema' => array( 'type' => 'string' ) ),
						array( 'name' => 'exclude_id', 'in' => 'query', 'schema' => array( 'type' => 'integer' ) ),
						array( 'name' => 'limit', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'maximum' => 30 ) ),
					),
				) ) ),
				'/media' => array(
					'get' => array_merge( $this->operation( 'findWordPressImages', 'Search the existing Media Library' ), array(
						'parameters' => array(
							array( 'name' => 'search', 'in' => 'query', 'schema' => array( 'type' => 'string' ) ),
							array( 'name' => 'limit', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'maximum' => 50 ) ),
						),
					) ),
					'post'=> $this->write_operation( 'importApprovedImage', 'Import an image from an approved HTTPS host', '#/components/schemas/MediaImport', array( $idempotency ) ),
				),
				'/reviews' => array( 'get' => $this->operation( 'listIkonSEOReviews', 'List improvement drafts awaiting review' ) ),
				'/reviews/{id}/comparison' => array( 'get' => array_merge( $this->operation( 'compareIkonSEOReview', 'Compare an improvement draft with its live source' ), array( 'parameters' => array( $id_parameter ) ) ) ),
				'/reviews/{id}/merge' => array( 'post' => $this->write_operation( 'mergeApprovedIkonSEOReview', 'Merge an approved draft into its original page', null, array( $id_parameter, $idempotency ), true ) ),
				'/pages/{id}/snapshots' => array( 'get' => array_merge( $this->operation( 'listIkonSEOSnapshots', 'List rollback snapshots for a page' ), array( 'parameters' => array( $id_parameter ) ) ) ),
				'/pages/{id}/rollback' => array( 'post' => $this->write_operation( 'rollbackIkonSEOPage', 'Restore a page from an Ikon SEO snapshot', '#/components/schemas/Rollback', array( $id_parameter, $idempotency ), true ) ),
				'/logs' => array( 'get' => array_merge( $this->operation( 'readIkonSEOActivity', 'Read recent workflow activity' ), array(
					'parameters' => array( array( 'name' => 'limit', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'maximum' => 100 ) ) ),
				) ) ),
				'/search-console/status' => array( 'get' => $this->operation( 'readSearchConsoleStatus', 'Check the read-only Search Console connection without exposing credentials' ) ),
				'/search-console/performance' => array( 'get' => array_merge( $this->operation( 'readSearchPerformance', 'Compare current and previous Search Console performance and list leading queries and pages' ), array(
					'parameters' => array(
						array( 'name' => 'days', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'minimum' => 7, 'maximum' => 90, 'default' => 28 ) ),
						array( 'name' => 'refresh', 'in' => 'query', 'schema' => array( 'type' => 'boolean', 'default' => false ) ),
					),
				) ) ),
				'/search-console/inspect' => array( 'get' => array_merge( $this->operation( 'inspectGoogleIndexStatus', 'Inspect the indexed version of a URL on this website' ), array(
					'parameters' => array(
						array( 'name' => 'url', 'in' => 'query', 'required' => true, 'schema' => array( 'type' => 'string', 'format' => 'uri' ) ),
					),
				) ) ),
				'/search-console/sitemaps' => array( 'get' => $this->operation( 'readSearchConsoleSitemaps', 'Read submitted sitemap status, warnings and errors' ) ),
				'/queue' => array( 'get' => array_merge( $this->operation( 'listIkonSEOPagePlans', 'List CSV-imported page plans awaiting interactive generation' ), array(
					'parameters' => array(
						array( 'name' => 'status', 'in' => 'query', 'schema' => array( 'type' => 'string', 'enum' => array( 'planned', 'claimed', 'paused', 'completed', 'failed', 'archived' ) ) ),
						array( 'name' => 'batch_id', 'in' => 'query', 'schema' => array( 'type' => 'string' ) ),
						array( 'name' => 'limit', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'maximum' => 200 ) ),
					),
				) ) ),
				'/queue/{id}/claim' => array( 'post' => $this->write_operation( 'claimIkonSEOPagePlan', 'Claim one page plan for up to one hour before generating its payload', null, array( $id_parameter, $idempotency ) ) ),
				'/queue/{id}/complete' => array( 'post' => $this->write_operation( 'completeIkonSEOPagePlan', 'Validate a completed page payload, create its WordPress draft and complete the page plan', '#/components/schemas/QueueCompletion', array( $id_parameter, $idempotency ) ) ),
				'/monitoring' => array( 'get' => $this->operation( 'readContentRefreshMonitor', 'Read overdue reviews, upcoming refresh dates and meaningful Search Console declines' ) ),
				'/local' => array( 'get' => $this->operation( 'readIkonSEOLocalSummary', 'Read local-location, citation, rank-workspace and NAP audit status' ) ),
				'/local/locations' => array( 'get' => $this->operation( 'readIkonSEOLocations', 'Read profile-bound storefront, hybrid and service-area records before preparing local content' ) ),
				'/local/nap-audit' => array( 'get' => $this->operation( 'auditIkonSEONAP', 'Compare master local business data with assigned landing pages and schema' ) ),
				'/local/citations' => array( 'get' => array_merge( $this->operation( 'readIkonSEOCitations', 'Read the citation consistency workspace' ), array(
					'parameters' => array( array( 'name' => 'limit', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'maximum' => 1000 ) ) ),
				) ) ),
				'/local/ranks' => array( 'get' => array_merge( $this->operation( 'readIkonSEOLocalRanks', 'Read manually imported organic and local-pack rank history; no Google scraping is performed' ), array(
					'parameters' => array( array( 'name' => 'limit', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'maximum' => 1000 ) ) ),
				) ) ),
				'/local/utm' => array( 'post' => $this->write_operation( 'buildIkonSEOLocalUTM', 'Build a controlled same-site UTM URL without changing WordPress', '#/components/schemas/UTMRequest', array(), false, false ) ),
				'/local/gbp/status' => array( 'get' => $this->operation( 'readGoogleBusinessProfileStatus', 'Read Google Business Profile connection and safety status without exposing credentials' ) ),
				'/local/gbp/comparison' => array( 'get' => array_merge( $this->operation( 'compareGoogleBusinessProfileLocation', 'Compare a linked Google Business Profile location with the website master record' ), array(
					'parameters' => array(
						array( 'name' => 'location_id', 'in' => 'query', 'required' => true, 'schema' => array( 'type' => 'integer' ) ),
					),
				) ) ),
				'/local/gbp/reviews' => array( 'get' => array_merge( $this->operation( 'readGoogleBusinessProfileReviews', 'Read recent reviews for a linked verified location' ), array(
					'parameters' => array(
						array( 'name' => 'location_id', 'in' => 'query', 'required' => true, 'schema' => array( 'type' => 'integer' ) ),
						array( 'name' => 'refresh', 'in' => 'query', 'schema' => array( 'type' => 'boolean', 'default' => false ) ),
					),
				) ) ),
					'/local/gbp/performance' => array( 'get' => array_merge( $this->operation( 'readGoogleBusinessProfilePerformance', 'Read Maps and Search impressions, website clicks, call clicks, directions, bookings and conversations' ), array(
					'parameters' => array(
						array( 'name' => 'location_id', 'in' => 'query', 'required' => true, 'schema' => array( 'type' => 'integer' ) ),
						array( 'name' => 'days', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'minimum' => 7, 'maximum' => 90, 'default' => 30 ) ),
						array( 'name' => 'refresh', 'in' => 'query', 'schema' => array( 'type' => 'boolean', 'default' => false ) ),
						),
					) ) ),
					'/local/gbp/search-keywords' => array( 'get' => array_merge( $this->operation( 'readGoogleBusinessProfileSearchKeywords', 'Read monthly search keywords and their reported impression values or privacy thresholds' ), array(
						'parameters' => array(
							array( 'name' => 'location_id', 'in' => 'query', 'required' => true, 'schema' => array( 'type' => 'integer' ) ),
							array( 'name' => 'months', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 18, 'default' => 3 ) ),
							array( 'name' => 'refresh', 'in' => 'query', 'schema' => array( 'type' => 'boolean', 'default' => false ) ),
						),
					) ) ),
				'/local/gbp/drafts' => array(
					'get'  => array_merge( $this->operation( 'readGoogleBusinessProfileDrafts', 'Read staged Google Business Profile posts and review replies' ), array(
						'parameters' => array( array( 'name' => 'limit', 'in' => 'query', 'schema' => array( 'type' => 'integer', 'maximum' => 500 ) ) ),
					) ),
					'post' => $this->write_operation( 'stageGoogleBusinessProfileDraft', 'Stage a post or review reply for explicit WordPress administrator approval; this action never sends it to Google', '#/components/schemas/GBPDraft', array( $idempotency ) ),
				),
			),
			'components' => array(
				'securitySchemes' => array(
					'IkonSEOKey' => array( 'type' => 'apiKey', 'in' => 'header', 'name' => 'X-Ikon-SEO-Key' ),
				),
				'schemas' => $this->openapi_schemas(),
			),
		);

		return $this->with_connection_header( $document );
	}


	private function with_connection_header( array $document ) {
		$methods = array( 'get', 'post', 'put', 'patch', 'delete' );
		$header  = array(
			'name'        => 'X-Ikon-SEO-Key',
			'in'          => 'header',
			'required'    => true,
			'description' => 'Connection key returned by pairIkonSEOWebsite. Keep it private and send it on every authenticated request.',
			'schema'      => array( 'type' => 'string' ),
		);

		foreach ( (array) ( $document['paths'] ?? array() ) as $path => $operations ) {
			if ( '/pair' === $path ) {
				continue;
			}
			foreach ( $methods as $method ) {
				if ( empty( $operations[ $method ] ) || ! is_array( $operations[ $method ] ) ) {
					continue;
				}
				$parameters = (array) ( $document['paths'][ $path ][ $method ]['parameters'] ?? array() );
				$duplicate  = false;
				foreach ( $parameters as $parameter ) {
					if ( is_array( $parameter ) && 'header' === ( $parameter['in'] ?? '' ) && 'X-Ikon-SEO-Key' === ( $parameter['name'] ?? '' ) ) {
						$duplicate = true;
						break;
					}
				}
				if ( ! $duplicate ) {
					array_unshift( $parameters, $header );
					$document['paths'][ $path ][ $method ]['parameters'] = $parameters;
				}
			}
		}

		return $document;
	}

	private function operation( $operation_id, $summary ) {
		return array(
			'operationId' => $operation_id,
			'summary'     => $summary,
			'responses'   => array( '200' => array( 'description' => 'Successful response' ) ),
		);
	}

	private function write_operation( $operation_id, $summary, $schema_ref = null, array $parameters = array(), $approval = false, $consequential = true ) {
		$operation = array(
			'operationId'              => $operation_id,
			'summary'                  => $summary,
			'x-openai-isConsequential' => (bool) $consequential,
			'parameters'               => $parameters,
			'responses'                => array(
				'200' => array( 'description' => 'Action completed' ),
				'201' => array( 'description' => 'Resource created' ),
				'400' => array( 'description' => 'Invalid request' ),
				'403' => array( 'description' => 'Action or scope disabled' ),
				'409' => array( 'description' => 'Conflict or duplicate' ),
			),
		);
		if ( $schema_ref ) {
			$operation['requestBody'] = array(
				'required' => true,
				'content'  => array( 'application/json' => array( 'schema' => array( '$ref' => $schema_ref ) ) ),
			);
		}
		if ( $approval ) {
			$operation['description'] = 'Disabled by default. Requires the approve key scope and Remote merge setting.';
		}
		return $operation;
	}

	private function openapi_schemas() {
		return array(
			'Button' => array(
				'type' => 'object',
				'properties' => array(
					'label' => array( 'type' => 'string' ),
					'url'   => array( 'type' => 'string', 'format' => 'uri' ),
				),
			),
			'FAQ' => array(
				'type' => 'object',
				'required' => array( 'question', 'answer' ),
				'properties' => array(
					'question' => array( 'type' => 'string' ),
					'answer'   => array( 'type' => 'string' ),
				),
			),
			'SectionItem' => array(
				'type' => 'object',
				'properties' => array(
					'title'   => array( 'type' => 'string' ),
					'content' => array( 'type' => 'string' ),
					'label'   => array( 'type' => 'string' ),
					'value'   => array( 'type' => 'string' ),
					'url'     => array( 'type' => 'string' ),
				),
			),
			'Section' => array(
				'type' => 'object',
				'required' => array( 'type', 'heading' ),
				'properties' => array(
					'type'     => array( 'type' => 'string', 'enum' => array( 'content', 'cards', 'features', 'checklist', 'table', 'process', 'faq', 'related-links', 'cta', 'split-content', 'stats', 'trust', 'documents', 'notice', 'expert', 'sources', 'location-details', 'service-area', 'map', 'local-proof', 'reviews' ) ),
					'eyebrow'  => array( 'type' => 'string' ),
					'heading'  => array( 'type' => 'string' ),
					'intro'    => array( 'type' => 'string' ),
					'content'  => array( 'type' => 'string' ),
					'aside'    => array( 'type' => 'string' ),
					'items'    => array( 'type' => 'array', 'items' => array( '$ref' => '#/components/schemas/SectionItem' ) ),
					'headers'  => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					'rows'     => array( 'type' => 'array', 'items' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ) ),
					'image_id' => array( 'type' => 'integer' ),
					'image_alt'=> array( 'type' => 'string' ),
					'map_query'=> array( 'type' => 'string', 'description' => 'Verified address or place query for the native Elementor map widget.' ),
					'map_url'  => array( 'type' => 'string', 'format' => 'uri' ),
					'map_embed_url' => array( 'type' => 'string', 'format' => 'uri', 'description' => 'Optional HTTPS www.google.com/maps/embed URL.' ),
					'map_label'=> array( 'type' => 'string' ),
					'map_zoom' => array( 'type' => 'integer', 'minimum' => 5, 'maximum' => 20 ),
					'button'   => array( '$ref' => '#/components/schemas/Button' ),
				),
			),
			'SEO' => array(
				'type' => 'object',
				'required' => array( 'title', 'description', 'focus_keyword' ),
				'properties' => array(
					'title'                => array( 'type' => 'string' ),
					'description'          => array( 'type' => 'string' ),
					'focus_keyword'        => array( 'type' => 'string' ),
					'canonical'            => array( 'type' => 'string', 'format' => 'uri' ),
					'robots'               => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					'facebook_title'       => array( 'type' => 'string' ),
					'facebook_description' => array( 'type' => 'string' ),
					'facebook_image'       => array( 'type' => 'string', 'format' => 'uri' ),
					'twitter_title'        => array( 'type' => 'string' ),
					'twitter_description'  => array( 'type' => 'string' ),
					'twitter_image'        => array( 'type' => 'string', 'format' => 'uri' ),
					'twitter_use_facebook' => array( 'type' => 'boolean' ),
				),
			),
			'PagePayload' => array(
				'type' => 'object',
				'required' => array( 'profile_id', 'title', 'sections', 'seo' ),
				'properties' => array(
					'profile_id'        => array( 'type' => 'string', 'description' => 'Current profile_id returned by GET /profile. Prevents cross-client writes.' ),
					'mode'              => array( 'type' => 'string', 'enum' => array( 'create', 'improve' ) ),
					'source_page_id'    => array( 'type' => 'integer' ),
					'title'             => array( 'type' => 'string' ),
					'slug'              => array( 'type' => 'string' ),
					'excerpt'           => array( 'type' => 'string' ),
					'language'          => array( 'type' => 'string' ),
					'status'            => array( 'type' => 'string', 'enum' => array( 'draft', 'pending', 'publish' ) ),
					'parent_id'         => array( 'type' => 'integer' ),
					'page_template'     => array( 'type' => 'string', 'enum' => array( 'default', 'elementor_header_footer', 'elementor_canvas' ) ),
					'featured_media_id' => array( 'type' => 'integer' ),
					'social_media_id'   => array( 'type' => 'integer' ),
					'hero'              => array(
						'type' => 'object',
						'properties' => array(
							'eyebrow'       => array( 'type' => 'string' ),
							'title'         => array( 'type' => 'string' ),
							'description'   => array( 'type' => 'string' ),
							'image_id'      => array( 'type' => 'integer' ),
							'image_alt'     => array( 'type' => 'string' ),
							'primary_cta'   => array( '$ref' => '#/components/schemas/Button' ),
							'secondary_cta' => array( '$ref' => '#/components/schemas/Button' ),
						),
					),
					'sections'       => array( 'type' => 'array', 'maxItems' => 40, 'items' => array( '$ref' => '#/components/schemas/Section' ) ),
					'faq'            => array( 'type' => 'array', 'maxItems' => 30, 'items' => array( '$ref' => '#/components/schemas/FAQ' ) ),
					'seo'            => array( '$ref' => '#/components/schemas/SEO' ),
					'schema'         => array( '$ref' => '#/components/schemas/SchemaConfig' ),
					'local'          => array( '$ref' => '#/components/schemas/LocalPage' ),
					'content_review' => array( '$ref' => '#/components/schemas/ContentReview' ),
				),
			),
			'LocalPage' => array(
				'type' => 'object',
				'description' => 'Required for schema.page_type=location. Verified locations and service areas use different safety policies.',
				'required' => array( 'location_id', 'page_kind', 'services', 'unique_local_details' ),
				'properties' => array(
					'location_id'          => array( 'type' => 'integer', 'description' => 'ID returned by GET /local/locations.' ),
					'page_kind'            => array( 'type' => 'string', 'enum' => array( 'verified_location', 'service_area' ) ),
					'target_area'          => array( 'type' => 'string' ),
					'services'             => array( 'type' => 'array', 'minItems' => 1, 'items' => array( 'type' => 'string' ) ),
					'unique_local_details' => array( 'type' => 'array', 'minItems' => 3, 'items' => array( 'type' => 'string' ) ),
					'landmarks'            => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					'directions'           => array( 'type' => 'string' ),
					'parking'              => array( 'type' => 'string' ),
					'staff'                => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					'map_url'              => array( 'type' => 'string', 'format' => 'uri' ),
					'show_address'          => array( 'type' => 'boolean', 'description' => 'Must be false for service-area pages.' ),
					'emit_location_entity'  => array( 'type' => 'boolean', 'description' => 'Must be false for service-area pages.' ),
				),
			),
			'SchemaConfig' => array(
				'type' => 'object',
				'description' => 'Allow-listed schema configuration. FAQ rich results are retired; semantic FAQ markup is optional.',
				'properties' => array(
					'page_type'         => array( 'type' => 'string', 'enum' => array( 'webpage', 'service', 'article', 'profile', 'collection', 'tool', 'about', 'contact', 'location', 'howto' ) ),
					'semantic_faq'      => array( 'type' => 'boolean' ),
					'service'           => array( 'type' => 'object', 'additionalProperties' => true ),
					'breadcrumbs'       => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
					'article'           => array( 'type' => 'object', 'additionalProperties' => true ),
					'person'            => array( 'type' => 'object', 'additionalProperties' => true ),
					'collection'        => array( 'type' => 'object', 'additionalProperties' => true ),
					'application'       => array( 'type' => 'object', 'additionalProperties' => true ),
					'business_entity'   => array( 'type' => 'object', 'description' => 'Optional explicit entity node. Its type is always taken from the active Website Profile.', 'additionalProperties' => true ),
					'accounting_service'=> array( 'type' => 'object', 'deprecated' => true, 'description' => 'v0.2 compatibility only; rejected outside an AccountingService profile.', 'additionalProperties' => true ),
					'howto'             => array( 'type' => 'object', 'additionalProperties' => true ),
					'video'             => array( 'type' => 'object', 'additionalProperties' => true ),
				),
			),
			'ContentReview' => array(
				'type' => 'object',
				'properties' => array(
					'ymyl'              => array( 'type' => 'boolean' ),
					'sources'           => array( 'type' => 'array', 'items' => array( 'type' => 'string', 'format' => 'uri' ) ),
					'reviewed_by'       => array( 'type' => 'string' ),
					'fact_checked_date' => array( 'type' => 'string', 'format' => 'date' ),
					'applicable_period' => array( 'type' => 'string' ),
					'jurisdiction'      => array( 'type' => 'string' ),
					'disclaimer'        => array( 'type' => 'string' ),
					'next_review_date'  => array( 'type' => 'string', 'format' => 'date' ),
					'show_on_page'      => array( 'type' => 'boolean' ),
					'heading'           => array( 'type' => 'string' ),
				),
			),
			'MediaImport' => array(
				'type' => 'object',
				'required' => array( 'source_url', 'alt_text' ),
				'properties' => array(
					'source_url' => array( 'type' => 'string', 'format' => 'uri' ),
					'filename'   => array( 'type' => 'string' ),
					'title'      => array( 'type' => 'string' ),
					'alt_text'   => array( 'type' => 'string' ),
					'caption'    => array( 'type' => 'string' ),
					'parent_id'  => array( 'type' => 'integer' ),
				),
			),
			'QueueCompletion' => array(
				'type' => 'object',
				'required' => array( 'claim_token', 'page' ),
				'properties' => array(
					'claim_token' => array( 'type' => 'string', 'minLength' => 32, 'description' => 'One-time token returned by the claim action. It expires after one hour.' ),
					'page'        => array( '$ref' => '#/components/schemas/PagePayload' ),
				),
			),
			'Rollback' => array(
				'type' => 'object',
				'properties' => array(
					'snapshot_id' => array( 'type' => 'string', 'description' => 'Optional. The newest snapshot is used when omitted.' ),
				),
			),
			'UTMRequest' => array(
				'type' => 'object',
				'required' => array( 'url', 'campaign' ),
				'properties' => array(
					'url'      => array( 'type' => 'string', 'format' => 'uri', 'description' => 'Must be on the current WordPress website.' ),
					'source'   => array( 'type' => 'string', 'default' => 'google' ),
					'medium'   => array( 'type' => 'string', 'default' => 'organic' ),
					'campaign' => array( 'type' => 'string' ),
					'content'  => array( 'type' => 'string' ),
					'term'     => array( 'type' => 'string' ),
				),
			),
			'GBPDraft' => array(
				'type' => 'object',
				'required' => array( 'profile_id', 'location_id', 'draft_type', 'content' ),
				'description' => 'Stages content locally. It cannot send, publish or reply until a WordPress administrator approves the exact draft.',
				'properties' => array(
					'profile_id'        => array( 'type' => 'string', 'description' => 'Current profile_id returned by GET /profile.' ),
					'location_id'       => array( 'type' => 'integer' ),
					'draft_type'       => array( 'type' => 'string', 'enum' => array( 'review_reply', 'google_post' ) ),
					'content'          => array( 'type' => 'string', 'maxLength' => 4000 ),
					'review_name'      => array( 'type' => 'string' ),
					'topic_type'       => array( 'type' => 'string', 'enum' => array( 'STANDARD', 'EVENT', 'OFFER' ) ),
					'language_code'    => array( 'type' => 'string' ),
					'call_to_action'   => array( 'type' => 'string' ),
					'call_to_action_url'=> array( 'type' => 'string', 'format' => 'uri' ),
					'event_title'      => array( 'type' => 'string' ),
					'start_time'       => array( 'type' => 'string', 'format' => 'date-time' ),
					'end_time'         => array( 'type' => 'string', 'format' => 'date-time' ),
					'coupon_code'      => array( 'type' => 'string' ),
					'redeem_online_url'=> array( 'type' => 'string', 'format' => 'uri' ),
					'terms_conditions' => array( 'type' => 'string' ),
				),
			),
		);
	}
}
