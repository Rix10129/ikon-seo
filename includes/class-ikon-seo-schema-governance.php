<?php

defined( 'ABSPATH' ) || exit;

/**
 * Read-only structured-data governance for rendered public pages.
 *
 * The auditor records bounded evidence and recommendations. It does not alter
 * front-end markup, Rank Math, Yoast SEO, theme output or published content.
 */
final class Ikon_SEO_Schema_Governance {
	const CACHE_KEY = 'ikon_seo_schema_governance_report_v1';

	private $history;
	private $logger;

	public function __construct( Ikon_SEO_Workspace_History $history, Ikon_SEO_Logger $logger ) {
		$this->history = $history;
		$this->logger  = $logger;
	}

	public function table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_schema_audits';
	}

	public function runs_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_governance_runs';
	}

	public function status() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		$table    = $this->table();

		if ( ! $this->table_exists( $table ) ) {
			return array(
				'ready'   => false,
				'enabled' => ! empty( $settings['structured_media_governance_enabled'] ),
				'message' => __( 'Structured-data governance tables are unavailable. Reactivate Ikon SEO to repair the database.', 'ikon-seo' ),
			);
		}

		$row = $wpdb->get_row(
			"SELECT COUNT(*) audited_pages,
			SUM(CASE WHEN error_count > 0 THEN 1 ELSE 0 END) pages_with_errors,
			SUM(CASE WHEN warning_count > 0 THEN 1 ELSE 0 END) pages_with_warnings,
			SUM(error_count) total_errors,
			SUM(warning_count) total_warnings,
			SUM(CASE WHEN schema_count = 0 THEN 1 ELSE 0 END) pages_without_schema,
			SUM(CASE WHEN duplicate_primary = 1 THEN 1 ELSE 0 END) duplicate_primary_pages,
			MAX(checked_at) last_checked
			FROM {$table}",
			ARRAY_A
		);

		return array(
			'ready'                   => true,
			'enabled'                 => ! empty( $settings['structured_media_governance_enabled'] ),
			'audited_pages'           => absint( $row['audited_pages'] ?? 0 ),
			'pages_with_errors'       => absint( $row['pages_with_errors'] ?? 0 ),
			'pages_with_warnings'     => absint( $row['pages_with_warnings'] ?? 0 ),
			'total_errors'            => absint( $row['total_errors'] ?? 0 ),
			'total_warnings'          => absint( $row['total_warnings'] ?? 0 ),
			'pages_without_schema'    => absint( $row['pages_without_schema'] ?? 0 ),
			'duplicate_primary_pages' => absint( $row['duplicate_primary_pages'] ?? 0 ),
			'last_checked'            => sanitize_text_field( $row['last_checked'] ?? '' ),
			'changes_markup'          => false,
		);
	}

	public function report( $limit = 100 ) {
		global $wpdb;
		$limit = max( 10, min( 500, absint( $limit ) ) );
		$table = $this->table();
		if ( ! $this->table_exists( $table ) ) {
			return array( 'status' => $this->status(), 'items' => array(), 'issue_counts' => array() );
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				ORDER BY error_count DESC, warning_count DESC, checked_at DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		$items = array();
		$issue_counts = array();
		foreach ( (array) $rows as $row ) {
			$issues = $this->decode_json( $row['issues_json'] ?? '' );
			foreach ( $issues as $issue ) {
				$code = sanitize_key( $issue['code'] ?? 'unknown' );
				$issue_counts[ $code ] = absint( $issue_counts[ $code ] ?? 0 ) + 1;
			}
			$items[] = array(
				'post_id'           => absint( $row['post_id'] ?? 0 ),
				'url'               => esc_url_raw( $row['url'] ?? '' ),
				'status_code'       => absint( $row['status_code'] ?? 0 ),
				'schema_count'      => absint( $row['schema_count'] ?? 0 ),
				'node_count'        => absint( $row['node_count'] ?? 0 ),
				'detected_types'    => $this->decode_json( $row['detected_types_json'] ?? '' ),
				'duplicate_types'   => $this->decode_json( $row['duplicate_types_json'] ?? '' ),
				'provider_hints'    => $this->decode_json( $row['provider_hints_json'] ?? '' ),
				'candidate_features'=> $this->decode_json( $row['candidate_features_json'] ?? '' ),
				'issues'            => $issues,
				'error_count'       => absint( $row['error_count'] ?? 0 ),
				'warning_count'     => absint( $row['warning_count'] ?? 0 ),
				'checked_at'        => sanitize_text_field( $row['checked_at'] ?? '' ),
			);
		}
		arsort( $issue_counts );

		return array(
			'status'       => $this->status(),
			'items'        => $items,
			'issue_counts' => $issue_counts,
			'limitations'  => array(
				__( 'The audit validates rendered JSON-LD structure and selected consistency rules; it does not guarantee a search enhancement.', 'ikon-seo' ),
				__( 'Provider hints are operational evidence and may not identify every theme or extension that prints markup.', 'ikon-seo' ),
			),
		);
	}

	public function audit_batch( $limit = 10, $force = false, $user_id = 0, $source = 'manual' ) {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['structured_media_governance_enabled'] ) ) {
			return new WP_Error( 'ikon_seo_governance_disabled', __( 'Structured Data and Media Governance is disabled.', 'ikon-seo' ) );
		}
		if ( ! $this->table_exists( $this->table() ) ) {
			return new WP_Error( 'ikon_seo_schema_table', __( 'The structured-data audit table is unavailable.', 'ikon-seo' ) );
		}

		$limit = max( 1, min( 100, absint( $limit ) ) );
		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] );
		$types = array_values( array_filter( array_map( 'sanitize_key', $types ) ) );
		if ( ! $types ) {
			return array( 'processed' => 0, 'errors' => 0, 'items' => array() );
		}

		$placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		$args = $types;
		$where = '';
		if ( ! $force ) {
			$stale_days = max( 1, min( 365, absint( $settings['schema_governance_stale_days'] ?? 30 ) ) );
			$where = ' AND (a.checked_at IS NULL OR a.checked_at < %s)';
			$args[] = gmdate( 'Y-m-d H:i:s', time() - $stale_days * DAY_IN_SECONDS );
		}
		$args[] = $limit;
		$sql = "SELECT p.ID
			FROM {$wpdb->posts} p
			LEFT JOIN {$this->table()} a ON a.post_id = p.ID
			WHERE p.post_status='publish' AND p.post_type IN ({$placeholders}) {$where}
			ORDER BY CASE WHEN a.checked_at IS NULL THEN 0 ELSE 1 END, p.post_modified_gmt DESC
			LIMIT %d";
		$post_ids = $wpdb->get_col( $wpdb->prepare( $sql, $args ) );

		$run_id = $this->start_run( 'schema', $source );
		$processed = 0;
		$errors = 0;
		$items = array();
		foreach ( (array) $post_ids as $post_id ) {
			$result = $this->audit_post( absint( $post_id ), true, 0, $source );
			if ( is_wp_error( $result ) ) {
				$errors++;
				$items[] = array( 'post_id' => absint( $post_id ), 'error' => $result->get_error_message() );
			} else {
				$processed++;
				$items[] = $result;
			}
		}
		$this->finish_run( $run_id, $processed, $errors, array( 'source' => $source ) );
		delete_transient( self::CACHE_KEY );

		if ( $user_id ) {
			$this->history->add(
				array(
					'category' => 'audit',
					'status'   => $errors ? 'partial' : 'completed',
					'title'    => 'Structured-data governance batch completed',
					'summary'  => sprintf( '%d pages were reviewed and %d fetch or parsing errors were recorded.', $processed, $errors ),
					'details'  => array( 'processed' => $processed, 'errors' => $errors, 'source' => $source ),
				),
				'governance',
				$user_id
			);
		}

		return array( 'processed' => $processed, 'errors' => $errors, 'items' => $items, 'status' => $this->status() );
	}

	public function audit_url( $url, $user_id = 0, $source = 'manual' ) {
		$url = $this->normalize_url( $url );
		if ( ! $url ) {
			return new WP_Error( 'ikon_seo_schema_url', __( 'Choose a valid URL on this website.', 'ikon-seo' ) );
		}
		$post_id = url_to_postid( $url );
		return $this->audit_post( $post_id, true, $user_id, $source, $url );
	}

	public function audit_post( $post_id, $force = false, $user_id = 0, $source = 'manual', $explicit_url = '' ) {
		global $wpdb;
		$post_id = absint( $post_id );
		$url = $explicit_url ? $this->normalize_url( $explicit_url ) : ( $post_id ? $this->normalize_url( get_permalink( $post_id ) ) : '' );
		if ( ! $url ) {
			return new WP_Error( 'ikon_seo_schema_page', __( 'The public page URL could not be resolved.', 'ikon-seo' ) );
		}

		if ( ! $force ) {
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE url_hash=%s", hash( 'sha256', $url ) ), ARRAY_A );
			if ( $existing ) {
				return $this->row_result( $existing );
			}
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 25,
				'redirection'         => 2,
				'limit_response_size' => 2 * MB_IN_BYTES,
				'headers'             => array( 'Accept' => 'text/html,application/xhtml+xml' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->store_fetch_error( $post_id, $url, $response->get_error_message() );
			return $response;
		}

		$status_code = absint( wp_remote_retrieve_response_code( $response ) );
		$html = (string) wp_remote_retrieve_body( $response );
		if ( $status_code < 200 || $status_code >= 400 || '' === $html ) {
			$message = sprintf( __( 'The page returned HTTP %d or an empty response.', 'ikon-seo' ), $status_code );
			$this->store_fetch_error( $post_id, $url, $message, $status_code );
			return new WP_Error( 'ikon_seo_schema_fetch', $message );
		}

		$analysis = $this->analyse_html( $html, $post_id, $url );
		$now = current_time( 'mysql', true );
		$data = array(
			'url_hash'                   => hash( 'sha256', $url ),
			'post_id'                    => $post_id,
			'url'                        => $url,
			'status_code'                => $status_code,
			'schema_count'               => absint( $analysis['schema_count'] ),
			'node_count'                 => absint( $analysis['node_count'] ),
			'detected_types_json'        => wp_json_encode( $analysis['detected_types'] ),
			'duplicate_types_json'       => wp_json_encode( $analysis['duplicate_types'] ),
			'provider_hints_json'        => wp_json_encode( $analysis['provider_hints'] ),
			'candidate_features_json'    => wp_json_encode( $analysis['candidate_features'] ),
			'issues_json'                => wp_json_encode( $analysis['issues'] ),
			'error_count'                => absint( $analysis['error_count'] ),
			'warning_count'              => absint( $analysis['warning_count'] ),
			'info_count'                 => absint( $analysis['info_count'] ),
			'duplicate_primary'          => ! empty( $analysis['duplicate_primary'] ) ? 1 : 0,
			'visible_alignment_percent'  => absint( $analysis['visible_alignment_percent'] ),
			'last_error'                 => '',
			'checked_at'                 => $now,
			'updated_at'                 => $now,
		);
		$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table()} WHERE url_hash=%s", $data['url_hash'] ) );
		if ( $existing_id ) {
			$wpdb->update( $this->table(), $data, array( 'id' => absint( $existing_id ) ) );
		} else {
			$data['created_at'] = $now;
			$wpdb->insert( $this->table(), $data );
		}
		delete_transient( self::CACHE_KEY );

		if ( $user_id ) {
			$this->history->add(
				array(
					'category' => 'audit',
					'status'   => 'completed',
					'title'    => 'Structured-data page review completed',
					'summary'  => sprintf( '%d structured nodes were reviewed with %d errors and %d warnings.', $analysis['node_count'], $analysis['error_count'], $analysis['warning_count'] ),
					'details'  => array( 'post_id' => $post_id, 'url' => $url, 'source' => $source ),
				),
				'governance',
				$user_id
			);
		}

		return array_merge( array( 'post_id' => $post_id, 'url' => $url, 'checked_at' => $now ), $analysis );
	}

	public function cleanup() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		$retention = max( 30, min( 730, absint( $settings['governance_retention_days'] ?? 180 ) ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $retention * DAY_IN_SECONDS );
		$deleted_runs = 0;
		if ( $this->table_exists( $this->runs_table() ) ) {
			$deleted_runs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->runs_table()} WHERE started_at < %s", $cutoff ) );
		}
		return array( 'deleted_runs' => absint( $deleted_runs ), 'retention_days' => $retention );
	}

	private function analyse_html( $html, $post_id, $url ) {
		$issues = array();
		$scripts = array();
		preg_match_all( '#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches );
		foreach ( (array) ( $matches[1] ?? array() ) as $raw ) {
			$raw = trim( html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			if ( '' === $raw ) {
				continue;
			}
			$decoded = json_decode( $raw, true );
			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
				$issues[] = $this->issue( 'invalid_json_ld', 'error', __( 'A JSON-LD block could not be parsed as valid JSON.', 'ikon-seo' ) );
				continue;
			}
			$scripts[] = $decoded;
		}

		$nodes = array();
		$has_context = false;
		foreach ( $scripts as $script ) {
			if ( ! empty( $script['@context'] ) ) {
				$has_context = true;
			}
			if ( isset( $script['@graph'] ) && is_array( $script['@graph'] ) ) {
				foreach ( $script['@graph'] as $node ) {
					if ( is_array( $node ) ) {
						$nodes[] = $node;
					}
				}
			} elseif ( $this->is_list( $script ) ) {
				foreach ( $script as $node ) {
					if ( is_array( $node ) ) {
						$nodes[] = $node;
					}
				}
			} else {
				$nodes[] = $script;
			}
		}

		if ( $scripts && ! $has_context ) {
			$issues[] = $this->issue( 'missing_context', 'warning', __( 'Structured data was found without a visible @context declaration.', 'ikon-seo' ) );
		}
		if ( ! $scripts ) {
			$issues[] = $this->issue( 'no_json_ld', 'warning', __( 'No rendered JSON-LD block was detected on this page.', 'ikon-seo' ) );
		}

		$type_counts = array();
		$id_counts = array();
		$visible_text = $this->visible_text( $html );
		$alignment_checks = 0;
		$alignment_matches = 0;
		foreach ( $nodes as $index => $node ) {
			$types = $this->node_types( $node );
			if ( ! $types ) {
				$issues[] = $this->issue( 'node_missing_type', 'warning', sprintf( __( 'Structured node %d has no @type.', 'ikon-seo' ), $index + 1 ) );
				continue;
			}
			foreach ( $types as $type ) {
				$type_counts[ $type ] = absint( $type_counts[ $type ] ?? 0 ) + 1;
				$this->validate_node( $node, $type, $issues );
			}
			$id = sanitize_text_field( $node['@id'] ?? '' );
			if ( $id ) {
				$id_counts[ $id ] = absint( $id_counts[ $id ] ?? 0 ) + 1;
			}
			$label = sanitize_text_field( $node['headline'] ?? ( $node['name'] ?? '' ) );
			if ( $label && $this->text_length( $label ) >= 4 ) {
				$alignment_checks++;
				if ( false !== $this->text_position( $visible_text, wp_strip_all_tags( $label ) ) ) {
					$alignment_matches++;
				} elseif ( array_intersect( $types, array( 'Article', 'BlogPosting', 'Product', 'Service', 'FAQPage', 'HowTo', 'VideoObject' ) ) ) {
					$issues[] = $this->issue( 'visible_content_mismatch', 'warning', sprintf( __( 'The %s name or headline was not found in the visible page text.', 'ikon-seo' ), implode( '/', $types ) ) );
				}
			}
		}

		foreach ( $id_counts as $id => $count ) {
			if ( $count > 1 ) {
				$issues[] = $this->issue( 'duplicate_node_id', 'warning', sprintf( __( 'The structured node identifier %s appears %d times.', 'ikon-seo' ), $id, $count ) );
			}
		}

		$duplicate_types = array();
		$ignored_duplicates = array( 'ImageObject', 'Offer', 'ListItem', 'Question', 'Answer', 'PostalAddress', 'Place', 'GeoCoordinates', 'ContactPoint', 'AggregateRating', 'Review' );
		foreach ( $type_counts as $type => $count ) {
			if ( $count > 1 && ! in_array( $type, $ignored_duplicates, true ) ) {
				$duplicate_types[ $type ] = $count;
			}
		}

		$primary_types = array( 'Organization', 'LocalBusiness', 'WebSite', 'WebPage', 'Article', 'BlogPosting', 'Product', 'Service', 'BreadcrumbList', 'FAQPage' );
		$duplicate_primary = false;
		foreach ( $duplicate_types as $type => $count ) {
			$severity = in_array( $type, $primary_types, true ) ? 'warning' : 'info';
			if ( 'warning' === $severity ) {
				$duplicate_primary = true;
			}
			$issues[] = $this->issue( 'duplicate_schema_type', $severity, sprintf( __( '%s appears %d times and should be reviewed for conflicting output.', 'ikon-seo' ), $type, $count ) );
		}

		$provider_hints = $this->provider_hints( $html );
		if ( in_array( 'Rank Math', $provider_hints, true ) && in_array( 'Yoast SEO', $provider_hints, true ) ) {
			$issues[] = $this->issue( 'multiple_schema_providers', 'warning', __( 'Rank Math and Yoast SEO both appear active. Review duplicate metadata and structured output.', 'ikon-seo' ) );
		}

		$post = $post_id ? get_post( $post_id ) : null;
		if ( $post instanceof WP_Post && 'post' === $post->post_type && ! array_intersect( array_keys( $type_counts ), array( 'Article', 'BlogPosting', 'NewsArticle' ) ) ) {
			$issues[] = $this->issue( 'article_type_missing', 'info', __( 'This published post does not expose an Article-family node in the rendered JSON-LD.', 'ikon-seo' ) );
		}

		$candidate_types = array( 'Article', 'BlogPosting', 'NewsArticle', 'Product', 'LocalBusiness', 'BreadcrumbList', 'FAQPage', 'VideoObject', 'HowTo', 'Recipe', 'Event', 'JobPosting' );
		$candidate_features = array_values( array_intersect( array_keys( $type_counts ), $candidate_types ) );

		$error_count = 0;
		$warning_count = 0;
		$info_count = 0;
		foreach ( $issues as $issue ) {
			if ( 'error' === $issue['severity'] ) {
				$error_count++;
			} elseif ( 'warning' === $issue['severity'] ) {
				$warning_count++;
			} else {
				$info_count++;
			}
		}
		ksort( $type_counts );
		ksort( $duplicate_types );

		return array(
			'schema_count'              => count( $scripts ),
			'node_count'                => count( $nodes ),
			'detected_types'            => $type_counts,
			'duplicate_types'           => $duplicate_types,
			'provider_hints'            => $provider_hints,
			'candidate_features'        => $candidate_features,
			'issues'                    => array_slice( $issues, 0, 200 ),
			'error_count'               => $error_count,
			'warning_count'             => $warning_count,
			'info_count'                => $info_count,
			'duplicate_primary'         => $duplicate_primary,
			'visible_alignment_percent' => $alignment_checks ? (int) round( 100 * $alignment_matches / $alignment_checks ) : 0,
		);
	}

	private function validate_node( array $node, $type, array &$issues ) {
		$requirements = array(
			'Organization'   => array( 'name', 'url' ),
			'LocalBusiness'  => array( 'name', 'url' ),
			'WebSite'        => array( 'name', 'url' ),
			'WebPage'        => array( 'name', 'url' ),
			'Article'        => array( 'headline', 'datePublished', 'dateModified', 'author', 'publisher' ),
			'BlogPosting'    => array( 'headline', 'datePublished', 'dateModified', 'author', 'publisher' ),
			'NewsArticle'    => array( 'headline', 'datePublished', 'dateModified', 'author', 'publisher' ),
			'Product'        => array( 'name', 'image', 'description' ),
			'Service'        => array( 'name', 'provider' ),
			'BreadcrumbList' => array( 'itemListElement' ),
			'FAQPage'        => array( 'mainEntity' ),
			'VideoObject'    => array( 'name', 'description', 'thumbnailUrl', 'uploadDate' ),
			'HowTo'          => array( 'name', 'step' ),
			'Person'         => array( 'name' ),
		);
		foreach ( (array) ( $requirements[ $type ] ?? array() ) as $property ) {
			if ( ! isset( $node[ $property ] ) || '' === $node[ $property ] || array() === $node[ $property ] ) {
				$severity = in_array( $property, array( 'name', 'headline', 'itemListElement', 'mainEntity', 'step' ), true ) ? 'error' : 'warning';
				$issues[] = $this->issue( 'missing_required_property', $severity, sprintf( __( '%s is missing the %s property.', 'ikon-seo' ), $type, $property ) );
			}
		}

		if ( 'Product' === $type && empty( $node['offers'] ) && empty( $node['review'] ) && empty( $node['aggregateRating'] ) ) {
			$issues[] = $this->issue( 'product_commercial_evidence_missing', 'warning', __( 'Product markup has no offers, review or aggregate-rating evidence.', 'ikon-seo' ) );
		}
		if ( 'FAQPage' === $type && ! empty( $node['mainEntity'] ) && ! is_array( $node['mainEntity'] ) ) {
			$issues[] = $this->issue( 'faq_entity_shape', 'error', __( 'FAQPage mainEntity should contain structured question records.', 'ikon-seo' ) );
		}
	}

	private function provider_hints( $html ) {
		$providers = array();
		if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) || false !== stripos( $html, 'rank-math' ) ) {
			$providers[] = 'Rank Math';
		}
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' ) || false !== stripos( $html, 'yoast-schema-graph' ) ) {
			$providers[] = 'Yoast SEO';
		}
		if ( false !== stripos( $html, 'woocommerce' ) ) {
			$providers[] = 'WooCommerce';
		}
		if ( false !== stripos( $html, 'elementor' ) ) {
			$providers[] = 'Elementor';
		}
		$theme = wp_get_theme();
		if ( $theme && $theme->exists() ) {
			$providers[] = 'Theme: ' . sanitize_text_field( $theme->get( 'Name' ) );
		}
		return array_values( array_unique( $providers ) );
	}

	private function store_fetch_error( $post_id, $url, $message, $status_code = 0 ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$data = array(
			'url_hash'                  => hash( 'sha256', $url ),
			'post_id'                   => absint( $post_id ),
			'url'                       => $url,
			'status_code'               => absint( $status_code ),
			'schema_count'              => 0,
			'node_count'                => 0,
			'detected_types_json'       => '[]',
			'duplicate_types_json'      => '[]',
			'provider_hints_json'       => '[]',
			'candidate_features_json'   => '[]',
			'issues_json'               => wp_json_encode( array( $this->issue( 'fetch_failed', 'error', sanitize_text_field( $message ) ) ) ),
			'error_count'               => 1,
			'warning_count'             => 0,
			'info_count'                => 0,
			'duplicate_primary'         => 0,
			'visible_alignment_percent' => 0,
			'last_error'                => sanitize_text_field( $message ),
			'checked_at'                => $now,
			'updated_at'                => $now,
		);
		$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table()} WHERE url_hash=%s", $data['url_hash'] ) );
		if ( $existing_id ) {
			$wpdb->update( $this->table(), $data, array( 'id' => absint( $existing_id ) ) );
		} else {
			$data['created_at'] = $now;
			$wpdb->insert( $this->table(), $data );
		}
	}

	private function start_run( $type, $source ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->runs_table() ) ) {
			return 0;
		}
		$wpdb->insert(
			$this->runs_table(),
			array(
				'run_type'   => sanitize_key( $type ),
				'source'     => sanitize_key( $source ),
				'status'     => 'running',
				'started_at' => current_time( 'mysql', true ),
			)
		);
		return absint( $wpdb->insert_id );
	}

	private function finish_run( $run_id, $processed, $errors, array $summary ) {
		global $wpdb;
		if ( ! $run_id ) {
			return;
		}
		$wpdb->update(
			$this->runs_table(),
			array(
				'status'       => $errors ? 'partial' : 'completed',
				'processed'    => absint( $processed ),
				'errors'       => absint( $errors ),
				'summary_json' => wp_json_encode( $summary ),
				'completed_at' => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $run_id ) )
		);
	}

	private function row_result( array $row ) {
		return array(
			'post_id'           => absint( $row['post_id'] ?? 0 ),
			'url'               => esc_url_raw( $row['url'] ?? '' ),
			'schema_count'      => absint( $row['schema_count'] ?? 0 ),
			'node_count'        => absint( $row['node_count'] ?? 0 ),
			'detected_types'    => $this->decode_json( $row['detected_types_json'] ?? '' ),
			'duplicate_types'   => $this->decode_json( $row['duplicate_types_json'] ?? '' ),
			'provider_hints'    => $this->decode_json( $row['provider_hints_json'] ?? '' ),
			'candidate_features'=> $this->decode_json( $row['candidate_features_json'] ?? '' ),
			'issues'            => $this->decode_json( $row['issues_json'] ?? '' ),
			'error_count'       => absint( $row['error_count'] ?? 0 ),
			'warning_count'     => absint( $row['warning_count'] ?? 0 ),
			'checked_at'        => sanitize_text_field( $row['checked_at'] ?? '' ),
		);
	}

	private function node_types( array $node ) {
		$types = $node['@type'] ?? array();
		$types = is_array( $types ) ? $types : array( $types );
		return array_values( array_filter( array_map( 'sanitize_text_field', $types ) ) );
	}

	private function visible_text( $html ) {
		$html = preg_replace( '#<script\b[^>]*>.*?</script>#is', ' ', (string) $html );
		$html = preg_replace( '#<style\b[^>]*>.*?</style>#is', ' ', $html );
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return preg_replace( '/\s+/u', ' ', trim( $text ) );
	}

	private function text_length( $text ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $text ) : strlen( (string) $text );
	}

	private function text_position( $haystack, $needle ) {
		return function_exists( 'mb_stripos' ) ? mb_stripos( (string) $haystack, (string) $needle ) : stripos( (string) $haystack, (string) $needle );
	}

	private function issue( $code, $severity, $message ) {
		return array(
			'code'     => sanitize_key( $code ),
			'severity' => in_array( $severity, array( 'error', 'warning', 'info' ), true ) ? $severity : 'info',
			'message'  => sanitize_text_field( $message ),
		);
	}

	private function normalize_url( $url ) {
		$url = esc_url_raw( trim( (string) $url ) );
		if ( ! $url || 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) && 'http' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return '';
		}
		$home_host = strtolower( preg_replace( '/^www\./', '', (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) );
		$url_host  = strtolower( preg_replace( '/^www\./', '', (string) wp_parse_url( $url, PHP_URL_HOST ) ) );
		if ( ! $home_host || $home_host !== $url_host ) {
			return '';
		}
		return $url;
	}

	private function decode_json( $value ) {
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function is_list( array $array ) {
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $array );
		}
		return array_keys( $array ) === range( 0, count( $array ) - 1 );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
