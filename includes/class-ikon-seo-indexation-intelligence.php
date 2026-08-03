<?php

defined( 'ABSPATH' ) || exit;

/**
 * Persistent, quota-aware URL Inspection evidence.
 *
 * This module reads the version currently known to Google. It does not submit
 * URLs for indexing and it cannot run the Search Console live test.
 */
class Ikon_SEO_Indexation_Intelligence {
	const CRON_HOOK = 'ikon_seo_indexation_daily';
	const CACHE_KEY = 'ikon_seo_indexation_report_v1';
	const OFFICIAL_SITE_DAILY_LIMIT = 2000;

	private $search_console;
	private $inventory;
	private $technical;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Search_Console $search_console,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Technical_Intelligence $technical,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->search_console = $search_console;
		$this->inventory      = $inventory;
		$this->technical      = $technical;
		$this->history        = $history;
		$this->logger         = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_run' ) );
		add_action( 'save_post', array( $this, 'queue_changed_post' ), 20, 3 );
	}

	public function scheduled_run() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['indexation_intelligence_enabled'] ) ) {
			return;
		}
		$this->seed_inventory( absint( $settings['indexation_seed_batch'] ?? 500 ), false, 0, 'scheduled' );
		$this->inspect_batch( absint( $settings['indexation_inspection_batch'] ?? 10 ), false, 0, 'scheduled' );
		$this->cleanup();
	}

	public function queue_changed_post( $post_id, $post, $update ) {
		if ( ! $post instanceof WP_Post || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['indexation_reinspect_after_change'] ) || 'publish' !== $post->post_status ) {
			return;
		}
		if ( ! is_post_type_viewable( $post->post_type ) ) {
			return;
		}
		$url = get_permalink( $post_id );
		if ( $url ) {
			$this->queue_urls( array( $url ), 95, 'post_change', 0 );
		}
	}

	public function urls_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_indexation_urls';
	}

	public function runs_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_indexation_runs';
	}

	public function status() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		$urls     = $this->urls_table();
		$runs     = $this->runs_table();

		if ( ! $this->table_exists( $urls ) || ! $this->table_exists( $runs ) ) {
			return array(
				'ready' => false,
				'enabled' => ! empty( $settings['indexation_intelligence_enabled'] ),
				'message' => __( 'Indexation tables are unavailable. Reactivate Ikon SEO to repair the database.', 'ikon-seo' ),
			);
		}

		$stale_days = max( 1, min( 365, absint( $settings['indexation_stale_days'] ?? 14 ) ) );
		$stale_cutoff = gmdate( 'Y-m-d H:i:s', time() - $stale_days * DAY_IN_SECONDS );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) total_urls,
				SUM(CASE WHEN queue_status='queued' THEN 1 ELSE 0 END) queued,
				SUM(CASE WHEN inspection_status='indexed' THEN 1 ELSE 0 END) indexed_urls,
				SUM(CASE WHEN inspection_status='not_indexed' THEN 1 ELSE 0 END) not_indexed,
				SUM(CASE WHEN canonical_mismatch=1 THEN 1 ELSE 0 END) canonical_mismatches,
				SUM(CASE WHEN issue_code IN ('robots_blocked','meta_noindex','fetch_failed') THEN 1 ELSE 0 END) blocked_or_failed,
				SUM(CASE WHEN queue_status='error' THEN 1 ELSE 0 END) errors,
				SUM(CASE WHEN inspected_at IS NULL OR inspected_at < %s THEN 1 ELSE 0 END) stale,
				MAX(inspected_at) last_inspection
				FROM {$urls}",
				$stale_cutoff
			),
			ARRAY_A
		);

		$used_today = $this->quota_used_today();
		$local_budget = $this->daily_budget();
		$gsc = $this->search_console->status();

		return array(
			'ready'                => true,
			'enabled'              => ! empty( $settings['indexation_intelligence_enabled'] ),
			'connected'            => ! empty( $gsc['connected'] ) && ! empty( $gsc['property'] ),
			'property'             => sanitize_text_field( $gsc['property'] ?? '' ),
			'total_urls'           => absint( $row['total_urls'] ?? 0 ),
			'queued'               => absint( $row['queued'] ?? 0 ),
			'indexed_urls'         => absint( $row['indexed_urls'] ?? 0 ),
			'not_indexed'          => absint( $row['not_indexed'] ?? 0 ),
			'canonical_mismatches' => absint( $row['canonical_mismatches'] ?? 0 ),
			'blocked_or_failed'     => absint( $row['blocked_or_failed'] ?? 0 ),
			'errors'               => absint( $row['errors'] ?? 0 ),
			'stale'                => absint( $row['stale'] ?? 0 ),
			'last_inspection'      => sanitize_text_field( $row['last_inspection'] ?? '' ),
			'quota'                => array(
				'used_today'          => $used_today,
				'local_daily_budget'  => $local_budget,
				'official_site_limit' => self::OFFICIAL_SITE_DAILY_LIMIT,
				'remaining_local'     => max( 0, $local_budget - $used_today ),
			),
			'inspection_scope'     => 'google_indexed_version_only',
			'submits_for_indexing' => false,
		);
	}

	public function seed_inventory( $limit = 500, $refresh = false, $user_id = 0, $source = 'manual' ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->urls_table() ) ) {
			return new WP_Error( 'ikon_seo_indexation_table', __( 'The Indexation Intelligence table is unavailable.', 'ikon-seo' ) );
		}
		$limit = max( 1, min( 5000, absint( $limit ) ) );
		$inventory = $this->inventory->scan( (bool) $refresh );
		if ( is_wp_error( $inventory ) ) {
			return $inventory;
		}
		$items = array_slice( (array) ( $inventory['items'] ?? array() ), 0, $limit );
		$front_id = absint( get_option( 'page_on_front' ) );
		$inserted = 0;
		$updated = 0;
		$now = current_time( 'mysql', true );

		foreach ( $items as $item ) {
			if ( 'publish' !== ( $item['status'] ?? '' ) || empty( $item['url'] ) ) {
				continue;
			}
			$url = $this->normalize_url( $item['url'] );
			if ( ! $url ) {
				continue;
			}
			$priority = 60;
			if ( absint( $item['id'] ?? 0 ) === $front_id ) {
				$priority = 100;
			} elseif ( 'page' === ( $item['post_type'] ?? '' ) ) {
				$priority = 85;
			} elseif ( ! empty( $item['orphan'] ) || empty( $item['incoming_internal_links'] ) ) {
				$priority = 75;
			}
			$robots = $item['robots'] ?? array();
			$local_noindex = $this->robots_noindex( $robots );
			$technical = $this->technical->page_summary( $url );
			$source_flags = sanitize_text_field( $technical['url']['source_flags'] ?? '' );
			$in_sitemap = false !== strpos( $source_flags, 'sitemap' );
			$hash = hash( 'sha256', $url );
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id,queue_status,inspected_at FROM {$this->urls_table()} WHERE url_hash=%s", $hash ), ARRAY_A );
			$data = array(
				'url_hash'      => $hash,
				'post_id'       => absint( $item['id'] ?? 0 ),
				'url'           => $url,
				'source'        => sanitize_key( $source ),
				'priority'      => $priority,
				'local_noindex' => $local_noindex ? 1 : 0,
				'in_sitemap'    => $in_sitemap ? 1 : 0,
				'updated_at'    => $now,
			);
			if ( $existing ) {
				if ( empty( $existing['inspected_at'] ) ) {
					$data['queue_status'] = 'queued';
				}
				$wpdb->update( $this->urls_table(), $data, array( 'id' => absint( $existing['id'] ) ) );
				$updated++;
			} else {
				$data['queue_status']     = 'queued';
				$data['inspection_status'] = 'pending';
				$data['requested_at']     = $now;
				$data['created_at']       = $now;
				$wpdb->insert( $this->urls_table(), $data );
				$inserted++;
			}
		}

		delete_transient( self::CACHE_KEY );
		$this->history->add(
			array(
				'category' => 'audit',
				'status'   => 'completed',
				'title'    => 'Indexation queue refreshed',
				'summary'  => sprintf( '%d new URLs and %d existing URLs were prepared for quota-aware inspection.', $inserted, $updated ),
				'details'  => array( 'inserted' => $inserted, 'updated' => $updated, 'source' => $source ),
			),
			'indexation',
			$user_id
		);
		return array( 'inserted' => $inserted, 'updated' => $updated, 'status' => $this->status() );
	}

	public function queue_urls( array $urls, $priority = 80, $source = 'manual', $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->urls_table() ) ) {
			return new WP_Error( 'ikon_seo_indexation_table', __( 'The Indexation Intelligence table is unavailable.', 'ikon-seo' ) );
		}
		$priority = max( 1, min( 100, absint( $priority ) ) );
		$queued = 0;
		$skipped = 0;
		$now = current_time( 'mysql', true );
		foreach ( array_slice( $urls, 0, 1000 ) as $candidate ) {
			$url = $this->normalize_url( $candidate );
			if ( ! $url ) {
				$skipped++;
				continue;
			}
			$hash = hash( 'sha256', $url );
			$post_id = url_to_postid( $url );
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->urls_table()} WHERE url_hash=%s", $hash ) );
			$data = array(
				'url_hash'       => $hash,
				'post_id'        => absint( $post_id ),
				'url'            => $url,
				'source'         => sanitize_key( $source ),
				'priority'       => $priority,
				'queue_status'   => 'queued',
				'requested_at'   => $now,
				'updated_at'     => $now,
				'last_error'     => '',
			);
			if ( $existing ) {
				$wpdb->update( $this->urls_table(), $data, array( 'id' => absint( $existing ) ) );
			} else {
				$data['inspection_status'] = 'pending';
				$data['created_at'] = $now;
				$wpdb->insert( $this->urls_table(), $data );
			}
			$queued++;
		}
		delete_transient( self::CACHE_KEY );
		if ( $queued && $user_id ) {
			$this->history->add(
				array(
					'category' => 'task',
					'status'   => 'completed',
					'title'    => 'URLs added to the indexation queue',
					'summary'  => sprintf( '%d URLs were queued for read-only Search Console inspection.', $queued ),
					'details'  => array( 'queued' => $queued, 'skipped' => $skipped, 'source' => $source ),
				),
				'indexation',
				$user_id
			);
		}
		return array( 'queued' => $queued, 'skipped' => $skipped );
	}

	public function inspect_batch( $limit = 10, $force = false, $user_id = 0, $source = 'manual' ) {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['indexation_intelligence_enabled'] ) ) {
			return new WP_Error( 'ikon_seo_indexation_disabled', __( 'Indexation Intelligence is disabled.', 'ikon-seo' ) );
		}
		$gsc = $this->search_console->status();
		if ( empty( $gsc['connected'] ) || empty( $gsc['property'] ) ) {
			return new WP_Error( 'ikon_seo_indexation_gsc', __( 'Connect Google Search Console and select the correct property first.', 'ikon-seo' ) );
		}
		$lock_key = 'ikon_seo_indexation_batch_lock';
		if ( get_transient( $lock_key ) ) {
			return new WP_Error( 'ikon_seo_indexation_locked', __( 'Another indexation batch is already running.', 'ikon-seo' ) );
		}
		set_transient( $lock_key, 1, 10 * MINUTE_IN_SECONDS );

		$limit = max( 1, min( 100, absint( $limit ) ) );
		$remaining = max( 0, $this->daily_budget() - $this->quota_used_today() );
		$limit = min( $limit, $remaining );
		if ( $limit < 1 ) {
			delete_transient( $lock_key );
			return new WP_Error( 'ikon_seo_indexation_budget', __( 'The local daily inspection budget has been reached.', 'ikon-seo' ) );
		}

		$stale_days = max( 1, min( 365, absint( $settings['indexation_stale_days'] ?? 14 ) ) );
		$stale_cutoff = gmdate( 'Y-m-d H:i:s', time() - $stale_days * DAY_IN_SECONDS );
		if ( $force ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$this->urls_table()} WHERE queue_status IN ('queued','error','complete') ORDER BY priority DESC, requested_at ASC, id ASC LIMIT %d",
				$limit
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$this->urls_table()} WHERE (queue_status IN ('queued','error') OR inspected_at IS NULL OR inspected_at < %s) ORDER BY priority DESC, requested_at ASC, id ASC LIMIT %d",
				$stale_cutoff,
				$limit
			);
		}
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		$success = 0;
		$failed = 0;
		$results = array();
		foreach ( $rows ?: array() as $row ) {
			$result = $this->inspect_row( $row, $user_id, $source );
			if ( is_wp_error( $result ) ) {
				$failed++;
				$results[] = array( 'url' => $row['url'], 'ok' => false, 'error' => $result->get_error_message() );
				if ( $this->is_quota_error( $result ) ) {
					break;
				}
			} else {
				$success++;
				$results[] = array( 'url' => $row['url'], 'ok' => true, 'status' => $result['inspection_status'], 'issue' => $result['issue_code'] );
			}
		}
		delete_transient( $lock_key );
		delete_transient( self::CACHE_KEY );

		$this->history->add(
			array(
				'category' => 'audit',
				'status'   => 'completed',
				'title'    => 'Indexation inspection batch completed',
				'summary'  => sprintf( '%d URL inspections succeeded and %d failed.', $success, $failed ),
				'details'  => array( 'success' => $success, 'failed' => $failed, 'source' => $source ),
			),
			'indexation',
			$user_id
		);

		return array( 'success' => $success, 'failed' => $failed, 'results' => $results, 'status' => $this->status() );
	}

	public function inspect_one( $url, $user_id = 0, $source = 'manual' ) {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['indexation_intelligence_enabled'] ) ) {
			return new WP_Error( 'ikon_seo_indexation_disabled', __( 'Indexation Intelligence is disabled.', 'ikon-seo' ) );
		}
		$status = $this->search_console->status();
		if ( empty( $status['connected'] ) || empty( $status['property'] ) ) {
			return new WP_Error( 'ikon_seo_indexation_gsc', __( 'Connect Google Search Console and select the correct property first.', 'ikon-seo' ) );
		}
		if ( $this->quota_used_today() >= $this->daily_budget() ) {
			return new WP_Error( 'ikon_seo_indexation_budget', __( 'The local daily inspection budget has been reached.', 'ikon-seo' ) );
		}
		$queued = $this->queue_urls( array( $url ), 100, $source, $user_id );
		if ( is_wp_error( $queued ) ) {
			return $queued;
		}
		if ( empty( $queued['queued'] ) ) {
			return new WP_Error( 'ikon_seo_indexation_url', __( 'The URL could not be queued.', 'ikon-seo' ) );
		}
		global $wpdb;
		$normalized = $this->normalize_url( $url );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->urls_table()} WHERE url_hash=%s", hash( 'sha256', $normalized ) ), ARRAY_A );
		return $row ? $this->inspect_row( $row, $user_id, $source ) : new WP_Error( 'ikon_seo_indexation_row', __( 'The queued URL could not be loaded.', 'ikon-seo' ) );
	}

	public function report( $limit = 100 ) {
		global $wpdb;
		$limit = max( 10, min( 500, absint( $limit ) ) );
		if ( ! $this->table_exists( $this->urls_table() ) ) {
			return array( 'status' => $this->status(), 'recommendations' => array(), 'items' => array() );
		}
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) && $limit <= 100 ) {
			return $cached;
		}
		$issues = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->urls_table()} WHERE issue_code<>'' OR canonical_mismatch=1 OR inspection_status='not_indexed' ORDER BY priority DESC, inspected_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		$recent = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->urls_table()} ORDER BY inspected_at DESC, priority DESC LIMIT %d", $limit ),
			ARRAY_A
		);
		$recommendations = array();
		foreach ( $issues ?: array() as $row ) {
			$issue = sanitize_key( $row['issue_code'] ?? '' );
			$title = 'Review Google index status';
			$action = 'Review the stored inspection evidence and the live page before changing indexing controls.';
			$priority = absint( $row['priority'] ?? 70 );
			if ( 'canonical_mismatch' === $issue ) {
				$title = 'Review Google-selected canonical';
				$action = 'Compare internal links, sitemap inclusion, redirects and the declared canonical before approving a change.';
				$priority = max( 85, $priority );
			} elseif ( in_array( $issue, array( 'robots_blocked', 'meta_noindex', 'fetch_failed' ), true ) ) {
				$title = 'Resolve an indexing block on an important URL';
				$action = 'Confirm whether the block is intentional. Remove it only after approval and technical verification.';
				$priority = max( 92, $priority );
			} elseif ( 'not_indexed' === $issue ) {
				$title = 'Investigate an important URL not indexed by Google';
				$action = 'Check content value, internal links, sitemap inclusion, canonical signals and crawlability before deciding what to change.';
				$priority = max( 80, $priority );
			}
			$recommendations[] = array(
				'title'              => $title,
				'code'               => $issue ?: 'indexation_review',
				'category'           => 'indexation',
				'url'                => esc_url_raw( $row['url'] ?? '' ),
				'post_id'            => absint( $row['post_id'] ?? 0 ),
				'priority'           => min( 100, $priority ),
				'confidence'         => 'high',
				'summary'            => sanitize_text_field( $row['coverage_state'] ?? $row['last_error'] ?? '' ),
				'recommended_action' => $action,
				'evidence'           => array(
					'verdict'          => sanitize_key( $row['verdict'] ?? '' ),
					'coverage_state'   => sanitize_text_field( $row['coverage_state'] ?? '' ),
					'google_canonical' => esc_url_raw( $row['google_canonical'] ?? '' ),
					'user_canonical'   => esc_url_raw( $row['user_canonical'] ?? '' ),
					'last_crawl_time'  => sanitize_text_field( $row['last_crawl_time'] ?? '' ),
				),
			);
		}
		$report = array(
			'generated_at'     => gmdate( 'c' ),
			'status'           => $this->status(),
			'recommendations'  => $recommendations,
			'issues'           => array_map( array( $this, 'normalize_row' ), $issues ?: array() ),
			'recent'           => array_map( array( $this, 'normalize_row' ), $recent ?: array() ),
			'limitations'      => array(
				'The URL Inspection API reports the version currently known to Google; it does not test the live URL.',
				'A missing or neutral result does not prove that Google will never index the page.',
				'The module does not request indexing and does not use the separate Indexing API.',
			),
		);
		if ( $limit <= 100 ) {
			set_transient( self::CACHE_KEY, $report, 15 * MINUTE_IN_SECONDS );
		}
		return $report;
	}

	public function page_summary( $url ) {
		global $wpdb;
		$url = $this->normalize_url( $url );
		if ( ! $url || ! $this->table_exists( $this->urls_table() ) ) {
			return array( 'available' => false );
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->urls_table()} WHERE url_hash=%s", hash( 'sha256', $url ) ), ARRAY_A );
		return $row ? array_merge( array( 'available' => true ), $this->normalize_row( $row ) ) : array( 'available' => false );
	}

	public function save_settings( array $payload ) {
		$settings = Ikon_SEO_Plugin::settings();
		$settings['indexation_intelligence_enabled'] = ! empty( $payload['enabled'] ) ? 1 : 0;
		$settings['indexation_reinspect_after_change'] = ! empty( $payload['reinspect_after_change'] ) ? 1 : 0;
		$settings['indexation_daily_budget'] = max( 1, min( self::OFFICIAL_SITE_DAILY_LIMIT, absint( $payload['daily_budget'] ?? 100 ) ) );
		$settings['indexation_inspection_batch'] = max( 1, min( 100, absint( $payload['inspection_batch'] ?? 10 ) ) );
		$settings['indexation_seed_batch'] = max( 10, min( 5000, absint( $payload['seed_batch'] ?? 500 ) ) );
		$settings['indexation_stale_days'] = max( 1, min( 365, absint( $payload['stale_days'] ?? 14 ) ) );
		$settings['indexation_history_retention_days'] = max( 30, min( 730, absint( $payload['retention_days'] ?? 180 ) ) );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		delete_transient( self::CACHE_KEY );
		return $this->status();
	}

	public function cleanup() {
		global $wpdb;
		$days = max( 30, min( 730, absint( Ikon_SEO_Plugin::settings()['indexation_history_retention_days'] ?? 180 ) ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$deleted_runs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->runs_table()} WHERE created_at < %s", $cutoff ) );
		return array( 'deleted_runs' => max( 0, (int) $deleted_runs ), 'retention_days' => $days );
	}

	private function inspect_row( array $row, $user_id = 0, $source = 'manual' ) {
		global $wpdb;
		$url = $this->normalize_url( $row['url'] ?? '' );
		if ( ! $url ) {
			return new WP_Error( 'ikon_seo_indexation_invalid_url', __( 'The queued URL is invalid or outside this website.', 'ikon-seo' ) );
		}
		$wpdb->update( $this->urls_table(), array( 'queue_status' => 'inspecting', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $row['id'] ) ) );
		$result = $this->search_console->inspect_url( $url );
		$now = current_time( 'mysql', true );

		if ( is_wp_error( $result ) ) {
			$error = sanitize_text_field( $result->get_error_message() );
			$wpdb->update(
				$this->urls_table(),
				array( 'queue_status' => 'error', 'last_error' => $error, 'updated_at' => $now ),
				array( 'id' => absint( $row['id'] ) )
			);
			$this->record_run( $url, 'error', '', 'api_error', $error, $source, $user_id );
			$this->logger->log( 'indexation_inspection', 'failed', $error );
			return $result;
		}

		$issue = $this->issue_from_result( $result, ! empty( $row['local_noindex'] ) );
		$inspection_status = 'unknown';
		if ( 'pass' === ( $result['verdict'] ?? '' ) ) {
			$inspection_status = 'indexed';
		} elseif ( in_array( ( $result['verdict'] ?? '' ), array( 'fail', 'neutral' ), true ) ) {
			$inspection_status = 'not_indexed';
		}
		$canonical_mismatch = ! empty( $result['google_canonical'] ) && ! empty( $result['user_canonical'] ) && $this->canonical_key( $result['google_canonical'] ) !== $this->canonical_key( $result['user_canonical'] );
		if ( $canonical_mismatch ) {
			$issue = 'canonical_mismatch';
		}
		$data = array(
			'queue_status'             => 'complete',
			'inspection_status'        => $inspection_status,
			'verdict'                  => sanitize_key( $result['verdict'] ?? '' ),
			'coverage_state'           => sanitize_text_field( $result['coverage_state'] ?? '' ),
			'indexing_state'           => sanitize_key( $result['indexing_state'] ?? '' ),
			'page_fetch_state'         => sanitize_key( $result['page_fetch_state'] ?? '' ),
			'robots_txt_state'         => sanitize_key( $result['robots_txt_state'] ?? '' ),
			'last_crawl_time'          => $this->mysql_datetime( $result['last_crawl_time'] ?? '' ),
			'google_canonical'         => esc_url_raw( $result['google_canonical'] ?? '' ),
			'user_canonical'           => esc_url_raw( $result['user_canonical'] ?? '' ),
			'canonical_mismatch'       => $canonical_mismatch ? 1 : 0,
			'mobile_usability_verdict' => sanitize_key( $result['mobile_usability_verdict'] ?? '' ),
			'rich_results_verdict'     => sanitize_key( $result['rich_results_verdict'] ?? '' ),
			'rich_items_json'          => wp_json_encode( array_slice( (array) ( $result['rich_items'] ?? array() ), 0, 50 ) ),
			'issue_code'               => sanitize_key( $issue ),
			'last_error'               => '',
			'inspected_at'             => $now,
			'updated_at'               => $now,
		);
		$wpdb->update( $this->urls_table(), $data, array( 'id' => absint( $row['id'] ) ) );
		$this->record_run( $url, 'success', $data['verdict'], $data['issue_code'], $data['coverage_state'], $source, $user_id );
		$this->logger->log( 'indexation_inspection', 'success', 'Stored read-only Google index evidence for one URL.' );
		delete_transient( self::CACHE_KEY );
		return array_merge( $this->normalize_row( array_merge( $row, $data ) ), array( 'inspection_scope' => 'google_indexed_version_only' ) );
	}

	private function record_run( $url, $status, $verdict, $issue, $message, $source, $user_id ) {
		global $wpdb;
		$wpdb->insert(
			$this->runs_table(),
			array(
				'url_hash'    => hash( 'sha256', $url ),
				'url'         => $url,
				'status'      => sanitize_key( $status ),
				'verdict'     => sanitize_key( $verdict ),
				'issue_code'  => sanitize_key( $issue ),
				'message'     => substr( sanitize_textarea_field( $message ), 0, 10000 ),
				'source'      => sanitize_key( $source ),
				'requested_by'=> absint( $user_id ),
				'created_at'  => current_time( 'mysql', true ),
			)
		);
	}

	private function issue_from_result( array $result, $local_noindex = false ) {
		$robots = sanitize_key( $result['robots_txt_state'] ?? '' );
		$indexing = sanitize_key( $result['indexing_state'] ?? '' );
		$fetch = sanitize_key( $result['page_fetch_state'] ?? '' );
		if ( false !== strpos( $robots, 'disallow' ) || false !== strpos( $robots, 'blocked' ) ) {
			return 'robots_blocked';
		}
		if ( $local_noindex || false !== strpos( $indexing, 'blocked_by_meta_tag' ) || false !== strpos( $indexing, 'noindex' ) ) {
			return 'meta_noindex';
		}
		if ( $fetch && ! in_array( $fetch, array( 'successful', 'page_fetch_state_unspecified' ), true ) ) {
			return 'fetch_failed';
		}
		if ( 'pass' !== sanitize_key( $result['verdict'] ?? '' ) ) {
			return 'not_indexed';
		}
		return '';
	}

	private function daily_budget() {
		return max( 1, min( self::OFFICIAL_SITE_DAILY_LIMIT, absint( Ikon_SEO_Plugin::settings()['indexation_daily_budget'] ?? 100 ) ) );
	}

	private function quota_used_today() {
		global $wpdb;
		if ( ! $this->table_exists( $this->runs_table() ) ) {
			return 0;
		}
		$start = gmdate( 'Y-m-d 00:00:00' );
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->runs_table()} WHERE created_at >= %s", $start ) ) );
	}

	private function is_quota_error( WP_Error $error ) {
		$message = strtolower( $error->get_error_message() );
		return false !== strpos( $message, 'quota' ) || false !== strpos( $message, 'rate limit' ) || false !== strpos( $message, 'resource_exhausted' );
	}

	private function robots_noindex( $robots ) {
		if ( is_array( $robots ) ) {
			foreach ( $robots as $key => $value ) {
				if ( 'noindex' === sanitize_key( (string) $key ) && ! empty( $value ) ) {
					return true;
				}
				if ( 'noindex' === sanitize_key( (string) $value ) ) {
					return true;
				}
			}
			return false;
		}
		return in_array( strtolower( trim( (string) $robots ) ), array( '1', 'noindex', 'yes', 'true' ), true );
	}

	private function normalize_row( array $row ) {
		foreach ( array( 'id', 'post_id', 'priority', 'canonical_mismatch', 'local_noindex', 'in_sitemap' ) as $key ) {
			$row[ $key ] = absint( $row[ $key ] ?? 0 );
		}
		$row['rich_items'] = json_decode( (string) ( $row['rich_items_json'] ?? '' ), true ) ?: array();
		unset( $row['rich_items_json'], $row['url_hash'] );
		return $row;
	}

	private function normalize_url( $url ) {
		$url = esc_url_raw( trim( (string) $url ) );
		if ( ! $url || ! wp_http_validate_url( $url ) ) {
			return '';
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$site = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( ! $host || ! $site || ! hash_equals( $site, $host ) ) {
			return '';
		}
		return $url;
	}

	private function canonical_key( $url ) {
		$url = esc_url_raw( $url );
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return '';
		}
		$scheme = strtolower( (string) ( $parts['scheme'] ?? 'https' ) );
		$host   = strtolower( (string) $parts['host'] );
		$port   = isset( $parts['port'] ) ? ':' . absint( $parts['port'] ) : '';
		$path   = (string) ( $parts['path'] ?? '/' );
		if ( '/' !== $path ) {
			$path = untrailingslashit( $path );
		}
		$query = isset( $parts['query'] ) && '' !== $parts['query'] ? '?' . $parts['query'] : '';
		return $scheme . '://' . $host . $port . ( $path ?: '/' ) . $query;
	}

	private function mysql_datetime( $value ) {
		$timestamp = strtotime( (string) $value );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
