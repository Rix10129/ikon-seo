<?php

defined( 'ABSPATH' ) || exit;

/**
 * Local growth intelligence that combines profile alignment, reviews,
 * citations, service areas, landing-page architecture, conversions and
 * competitor-prominence evidence.
 *
 * All external-profile writes remain staged and administrator-approved in the
 * existing Business Profile workflow. This class performs read-only analysis
 * and stores planning evidence only.
 */
final class Ikon_SEO_Local_Growth {
	const CRON_HOOK = 'ikon_seo_local_growth_weekly_refresh';

	private $profile;
	private $local;
	private $gbp;
	private $analytics;
	private $competitor_content;
	private $authority;
	private $strategy;
	private $inventory;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Local $local,
		Ikon_SEO_GBP $gbp,
		Ikon_SEO_Analytics $analytics,
		Ikon_SEO_Competitor_Content_Intelligence $competitor_content,
		Ikon_SEO_Authority_Intelligence $authority,
		Ikon_SEO_Strategy $strategy,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->profile            = $profile;
		$this->local              = $local;
		$this->gbp                = $gbp;
		$this->analytics          = $analytics;
		$this->competitor_content = $competitor_content;
		$this->authority          = $authority;
		$this->strategy           = $strategy;
		$this->inventory          = $inventory;
		$this->history            = $history;
		$this->logger             = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_refresh' ) );
	}

	public function scheduled_refresh() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['local_growth_enabled'] ) ) {
			return;
		}
		$strategy = $this->strategy->get();
		if ( ! in_array( $strategy['mode'], array( 'local_business', 'hybrid' ), true ) ) {
			return;
		}
		$this->refresh( true, absint( $settings['local_conversion_days'] ?? 30 ), true );
	}

	public function status() {
		$settings  = Ikon_SEO_Plugin::settings();
		$locations = $this->local->locations();
		$linked    = 0;
		foreach ( $locations as $location ) {
			if ( ! empty( $location['gbp_location_name'] ) ) {
				$linked++;
			}
		}
		return array(
			'enabled'             => ! empty( $settings['local_growth_enabled'] ),
			'profile_id'          => $this->profile->fingerprint(),
			'locations'           => count( $locations ),
			'linked_locations'    => $linked,
			'gbp_connected'       => ! empty( $this->gbp->status()['connected'] ),
			'analytics_connected' => ! empty( $this->analytics->status()['connected'] ),
			'last_sync'           => sanitize_text_field( $settings['local_growth_last_sync'] ?? '' ),
			'last_error'          => sanitize_text_field( $settings['local_growth_last_error'] ?? '' ),
			'read_only_analysis'  => true,
			'external_writes'     => 'approval_required',
		);
	}

	public function report( $refresh = false, $days = 30 ) {
		$days       = max( 7, min( 90, absint( $days ) ) );
		$settings   = Ikon_SEO_Plugin::settings();
		$strategy   = $this->strategy->get();
		$locations  = $this->local->locations();
		$citations  = $this->local->citations( 1000 );
		$local_base = $this->local->summary();
		$status     = $this->status();

		$review_sync = null;
		if ( $refresh && ! empty( $status['gbp_connected'] ) ) {
			$review_sync = $this->sync_reviews( true );
		}

		$conversion_sync = null;
		if ( $refresh ) {
			$conversion_sync = $this->sync_conversions( $days, true );
		}

		$profile_alignment  = $this->profile_alignment( $locations, $refresh );
		$service_areas      = $this->service_area_validation( $locations, $strategy );
		$landing_pages      = $this->landing_architecture( $locations, $strategy );
		$citation_health    = $this->citation_health( $citations, $locations );
		$review_workflow    = $this->review_workflow();
		$conversions        = $this->conversion_report( $days, false );
		$prominence         = $this->competitor_prominence();
		$readiness          = $this->readiness( $strategy, $profile_alignment, $service_areas, $landing_pages, $citation_health, $review_workflow, $conversions, $prominence );
		$recommendations    = $this->recommendations( $readiness, $profile_alignment, $service_areas, $landing_pages, $citation_health, $review_workflow, $conversions, $prominence );

		return array(
			'status'                 => $status,
			'mode'                   => $strategy['mode'],
			'mode_label'             => $strategy['mode_label'],
			'local_summary'          => $local_base,
			'readiness'              => $readiness,
			'profile_alignment'      => $profile_alignment,
			'service_area_validation'=> $service_areas,
			'landing_architecture'   => $landing_pages,
			'citation_health'        => $citation_health,
			'review_workflow'        => $review_workflow,
			'conversions'            => $conversions,
			'competitor_prominence'  => $prominence,
			'recommendations'        => $recommendations,
			'refresh_results'        => array(
				'reviews'     => is_wp_error( $review_sync ) ? array( 'error' => $review_sync->get_error_message() ) : $review_sync,
				'conversions' => is_wp_error( $conversion_sync ) ? array( 'error' => $conversion_sync->get_error_message() ) : $conversion_sync,
			),
			'limitations'            => array(
				'Distance from an individual searcher cannot be optimized or predicted by this report.',
				'Business Profile data requires an approved Google connection and may be limited by API access or privacy thresholds.',
				'Competitor prominence reflects only stored research and imported authority evidence.',
				'No page, citation, review reply, redirect or public profile is changed by this report.',
			),
			'generated_at'           => current_time( 'mysql', true ),
		);
	}

	public function refresh( $remote = false, $days = 30, $scheduled = false ) {
		$days   = max( 7, min( 90, absint( $days ) ) );
		$result = $this->report( (bool) $remote, $days );
		$settings = get_option( Ikon_SEO_Plugin::OPTION_KEY, array() );
		$settings['local_growth_last_sync']  = current_time( 'mysql', true );
		$settings['local_growth_last_error'] = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );

		if ( ! $scheduled ) {
			$this->history->add(
				array(
					'category' => 'local_growth',
					'status'   => 'completed',
					'title'    => 'Local growth evidence refreshed',
					'summary'  => sprintf( 'Local readiness is %d/100 with %d recommended actions.', absint( $result['readiness']['score'] ?? 0 ), count( $result['recommendations'] ?? array() ) ),
					'details'  => array(
						'readiness'       => absint( $result['readiness']['score'] ?? 0 ),
						'remote_refresh'  => (bool) $remote,
						'conversion_days' => $days,
					),
				),
				'local_growth',
				0
			);
		}
		$this->logger->log( 'local_growth_refresh', 'success', 'Local growth evidence refreshed.' );
		return $result;
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		$result  = array();

		if ( 'refresh' === $command ) {
			$result['refresh'] = $this->refresh( ! empty( $payload['remote_refresh'] ), absint( $payload['days'] ?? 30 ), false );
		} elseif ( 'sync_reviews' === $command ) {
			$result['review_sync'] = $this->sync_reviews( ! empty( $payload['remote_refresh'] ) );
			if ( is_wp_error( $result['review_sync'] ) ) {
				return $result['review_sync'];
			}
		} elseif ( 'sync_conversions' === $command ) {
			$result['conversion_sync'] = $this->sync_conversions( absint( $payload['days'] ?? 30 ), ! empty( $payload['remote_refresh'] ) );
			if ( is_wp_error( $result['conversion_sync'] ) ) {
				return $result['conversion_sync'];
			}
		} elseif ( 'save_prominence' === $command ) {
			$result['prominence'] = $this->save_prominence( (array) ( $payload['prominence'] ?? array() ), $user_id );
			if ( is_wp_error( $result['prominence'] ) ) {
				return $result['prominence'];
			}
		} elseif ( 'update_review_task' === $command ) {
			$result['review_task'] = $this->update_review_task( absint( $payload['review_task_id'] ?? 0 ), (array) ( $payload['changes'] ?? array() ), $user_id );
			if ( is_wp_error( $result['review_task'] ) ) {
				return $result['review_task'];
			}
		} elseif ( 'read' !== $command ) {
			return new WP_Error( 'ikon_seo_local_growth_command', __( 'The requested local growth command is not supported.', 'ikon-seo' ) );
		}

		$result['state'] = $this->report( false, absint( $payload['days'] ?? 30 ) );
		return $result;
	}

	public function sync_reviews( $refresh = false ) {
		$status = $this->gbp->status();
		if ( empty( $status['connected'] ) ) {
			return array( 'status' => 'not_connected', 'synced' => 0, 'note' => 'Business Profile is not connected.' );
		}
		$locations = $this->local->locations();
		$synced    = 0;
		$errors    = array();
		foreach ( array_slice( $locations, 0, 25 ) as $location ) {
			if ( empty( $location['gbp_location_name'] ) ) {
				continue;
			}
			$reviews = $this->gbp->reviews( absint( $location['id'] ), (bool) $refresh );
			if ( is_wp_error( $reviews ) ) {
				$errors[] = sprintf( 'Location %d: %s', absint( $location['id'] ), $reviews->get_error_message() );
				continue;
			}
			foreach ( (array) ( $reviews['items'] ?? array() ) as $review ) {
				if ( $this->store_review_task( $location, $review ) ) {
					$synced++;
				}
			}
		}
		$this->logger->log( 'local_review_sync', $errors ? 'warning' : 'success', sprintf( '%d review workflow records synchronized.', $synced ) );
		return array( 'status' => $errors ? 'partial' : 'completed', 'synced' => $synced, 'errors' => $errors, 'fetched_at' => current_time( 'mysql', true ) );
	}

	public function update_review_task( $id, array $changes, $user_id = 0 ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'ikon_seo_local_review_task', __( 'Select a review workflow item.', 'ikon-seo' ) );
		}
		$table = $this->review_table();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d AND profile_id=%s", $id, $this->profile->fingerprint() ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'ikon_seo_local_review_task', __( 'The review workflow item was not found.', 'ikon-seo' ) );
		}
		$data = array( 'updated_at' => current_time( 'mysql', true ) );
		if ( isset( $changes['status'] ) ) {
			$status = sanitize_key( $changes['status'] );
			if ( ! in_array( $status, array( 'open', 'in_progress', 'draft_staged', 'responded', 'dismissed' ), true ) ) {
				return new WP_Error( 'ikon_seo_local_review_status', __( 'Select a supported review workflow status.', 'ikon-seo' ) );
			}
			$data['status'] = $status;
			if ( 'responded' === $status ) {
				$data['responded_at'] = current_time( 'mysql', true );
			}
		}
		if ( isset( $changes['owner_id'] ) ) {
			$data['owner_id'] = absint( $changes['owner_id'] );
		}
		if ( isset( $changes['notes'] ) ) {
			$data['notes'] = sanitize_textarea_field( $changes['notes'] );
		}
		$updated = $wpdb->update( $table, $data, array( 'id' => $id, 'profile_id' => $this->profile->fingerprint() ) );
		if ( false === $updated ) {
			return new WP_Error( 'ikon_seo_local_review_update', __( 'The review workflow item could not be updated.', 'ikon-seo' ) );
		}
		$this->history->add(
			array(
				'category' => 'review',
				'status'   => 'responded' === ( $data['status'] ?? '' ) ? 'completed' : 'open',
				'title'    => 'Review workflow updated',
				'summary'  => sprintf( 'Review workflow item #%d was updated. No reply was sent by this action.', $id ),
				'details'  => array( 'review_task_id' => $id, 'changes' => array_keys( $data ), 'updated_by' => absint( $user_id ) ),
			),
			'local_growth',
			0
		);
		return $this->review_task( $id );
	}

	public function save_prominence( array $input, $user_id = 0 ) {
		global $wpdb;
		$competitor = sanitize_text_field( $input['competitor_name'] ?? '' );
		$evidence   = sanitize_textarea_field( $input['evidence'] ?? '' );
		$source     = sanitize_key( $input['source_type'] ?? 'manual' );
		$allowed    = array( 'local_pack', 'organic', 'reviews', 'citations', 'backlinks', 'brand_mentions', 'directories', 'manual' );
		if ( ! $competitor || ! $evidence ) {
			return new WP_Error( 'ikon_seo_local_prominence_required', __( 'Competitor name and evidence are required.', 'ikon-seo' ) );
		}
		if ( ! in_array( $source, $allowed, true ) ) {
			$source = 'manual';
		}
		$domain = strtolower( trim( sanitize_text_field( $input['competitor_domain'] ?? '' ) ) );
		$domain = preg_replace( '#^https?://#', '', $domain );
		$domain = preg_replace( '#/.*$#', '', $domain );
		if ( $domain && ! preg_match( '/^[a-z0-9.-]+$/', $domain ) ) {
			return new WP_Error( 'ikon_seo_local_prominence_domain', __( 'Enter a valid competitor domain.', 'ikon-seo' ) );
		}
		$confidence = sanitize_key( $input['confidence'] ?? 'medium' );
		if ( ! in_array( $confidence, array( 'low', 'medium', 'high' ), true ) ) {
			$confidence = 'medium';
		}
		$observed = sanitize_text_field( $input['observed_at'] ?? '' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $observed ) ) {
			$observed = current_time( 'Y-m-d', true );
		}
		$data = array(
			'profile_id'        => $this->profile->fingerprint(),
			'competitor_name'   => $competitor,
			'competitor_domain' => $domain,
			'query_text'        => sanitize_text_field( $input['query'] ?? '' ),
			'source_type'       => $source,
			'source_url'        => esc_url_raw( $input['source_url'] ?? '' ),
			'evidence_text'     => $evidence,
			'metric_name'       => sanitize_text_field( $input['metric_name'] ?? '' ),
			'metric_value'      => is_numeric( $input['metric_value'] ?? null ) ? (float) $input['metric_value'] : null,
			'confidence'        => $confidence,
			'observed_at'       => $observed,
			'status'            => 'active',
			'created_by'        => absint( $user_id ),
			'created_at'        => current_time( 'mysql', true ),
			'updated_at'        => current_time( 'mysql', true ),
		);
		$inserted = $wpdb->insert( $this->prominence_table(), $data );
		if ( false === $inserted ) {
			return new WP_Error( 'ikon_seo_local_prominence_save', __( 'The competitor prominence evidence could not be saved.', 'ikon-seo' ) );
		}
		$id = absint( $wpdb->insert_id );
		$this->history->add(
			array(
				'category' => 'research',
				'status'   => 'completed',
				'title'    => 'Local competitor evidence stored',
				'summary'  => sprintf( 'Stored %s evidence for %s.', str_replace( '_', ' ', $source ), $competitor ),
				'details'  => array( 'prominence_id' => $id, 'source_type' => $source, 'query' => $data['query_text'], 'created_by' => absint( $user_id ) ),
			),
			'local_growth',
			0
		);
		$this->logger->log( 'local_prominence_save', 'success', 'Local competitor prominence evidence stored.' );
		return $this->prominence_entry( $id );
	}

	public function sync_conversions( $days = 30, $refresh = false ) {
		global $wpdb;
		$days     = max( 7, min( 90, absint( $days ) ) );
		$settings = Ikon_SEO_Plugin::settings();
		$rows     = array();
		$errors   = array();

		$ga_status = $this->analytics->status();
		if ( ! empty( $ga_status['connected'] ) && ! empty( $ga_status['property'] ) ) {
			$ga = $this->analytics->report( $days, (bool) $refresh );
			if ( is_wp_error( $ga ) ) {
				$errors[] = $ga->get_error_message();
			} else {
				foreach ( array( 'sessions', 'active_users', 'engaged_sessions', 'views', 'key_events' ) as $metric ) {
					$rows[] = array(
						'location_id' => 0,
						'source'      => 'analytics',
						'metric_name' => $metric,
						'metric_value'=> (float) ( $ga['totals'][ $metric ] ?? 0 ),
						'period_start'=> sanitize_text_field( $ga['period']['start'] ?? '' ),
						'period_end'  => sanitize_text_field( $ga['period']['end'] ?? '' ),
						'context'     => array( 'property' => sanitize_text_field( $ga['property'] ?? '' ) ),
					);
				}
			}
		}

		$gbp_status = $this->gbp->status();
		if ( ! empty( $gbp_status['connected'] ) ) {
			foreach ( array_slice( $this->local->locations(), 0, 25 ) as $location ) {
				if ( empty( $location['gbp_location_name'] ) ) {
					continue;
				}
				$performance = $this->gbp->performance( absint( $location['id'] ), $days, (bool) $refresh );
				if ( is_wp_error( $performance ) ) {
					$errors[] = sprintf( 'Location %d: %s', absint( $location['id'] ), $performance->get_error_message() );
					continue;
				}
				foreach ( (array) ( $performance['totals'] ?? array() ) as $metric => $value ) {
					$rows[] = array(
						'location_id' => absint( $location['id'] ),
						'source'      => 'business_profile',
						'metric_name' => sanitize_key( $metric ),
						'metric_value'=> (float) $value,
						'period_start'=> sanitize_text_field( $performance['period']['start'] ?? '' ),
						'period_end'  => sanitize_text_field( $performance['period']['end'] ?? '' ),
						'context'     => array( 'location_name' => sanitize_text_field( $location['location_label'] ?: $location['business_name'] ) ),
					);
				}
			}
		}

		$table = $this->conversion_table();
		$stored = 0;
		foreach ( $rows as $row ) {
			if ( ! $row['period_start'] || ! $row['period_end'] ) {
				continue;
			}
			$replaced = $wpdb->replace(
				$table,
				array(
					'profile_id'   => $this->profile->fingerprint(),
					'location_id'  => absint( $row['location_id'] ),
					'period_start' => $row['period_start'],
					'period_end'   => $row['period_end'],
					'source'       => $row['source'],
					'metric_name'  => $row['metric_name'],
					'metric_value' => (float) $row['metric_value'],
					'context_json' => wp_json_encode( $row['context'] ),
					'fetched_at'   => current_time( 'mysql', true ),
				)
			);
			if ( false !== $replaced ) {
				$stored++;
			}
		}

		if ( $stored ) {
			$settings['local_growth_last_sync'] = current_time( 'mysql', true );
			update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		}
		return array( 'status' => $errors ? 'partial' : 'completed', 'stored' => $stored, 'errors' => $errors, 'period_days' => $days, 'fetched_at' => current_time( 'mysql', true ) );
	}

	private function profile_alignment( array $locations, $refresh ) {
		$gbp_status = $this->gbp->status();
		$items      = array();
		$critical   = 0;
		$warnings   = 0;
		$linked     = 0;
		foreach ( $locations as $location ) {
			$row = array(
				'location_id'   => absint( $location['id'] ),
				'label'         => sanitize_text_field( $location['location_label'] ?: $location['business_name'] ),
				'linked'        => ! empty( $location['gbp_location_name'] ),
				'status'        => 'not_linked',
				'failures'      => 0,
				'warnings'      => 0,
				'checks'        => array(),
			);
			if ( $row['linked'] ) {
				$linked++;
			}
			if ( $row['linked'] && ! empty( $gbp_status['connected'] ) ) {
				$comparison = $this->gbp->comparison( absint( $location['id'] ) );
				if ( is_wp_error( $comparison ) ) {
					$row['status']   = 'unavailable';
					$row['warnings'] = 1;
					$row['checks'][] = array( 'id' => 'profile_comparison', 'status' => 'warning', 'message' => $comparison->get_error_message() );
				} else {
					$row['status']   = sanitize_key( $comparison['status'] ?? 'review' );
					$row['failures'] = absint( $comparison['failures'] ?? 0 );
					$row['warnings'] = absint( $comparison['warnings'] ?? 0 );
					$row['checks']   = array_slice( (array) ( $comparison['checks'] ?? array() ), 0, 30 );
				}
			} elseif ( $row['linked'] ) {
				$row['status']   = 'connection_required';
				$row['warnings'] = 1;
			}
			$critical += $row['failures'];
			$warnings += $row['warnings'];
			$items[]   = $row;
		}
		$nap = $this->local->nap_audit();
		return array(
			'status'              => $critical ? 'needs_changes' : ( $warnings || 'ready' !== sanitize_key( $nap['status'] ?? '' ) ? 'review' : 'aligned' ),
			'locations'           => count( $locations ),
			'linked_locations'    => $linked,
			'critical_mismatches' => $critical,
			'warnings'            => $warnings,
			'nap_audit'           => $nap,
			'items'               => $items,
			'gbp_connected'       => ! empty( $gbp_status['connected'] ),
		);
	}

	private function service_area_validation( array $locations, array $strategy ) {
		$issues       = array();
		$area_owners  = array();
		$total_areas  = 0;
		$service_rows = 0;
		foreach ( $locations as $location ) {
			$type  = sanitize_key( $location['location_type'] ?? '' );
			$areas = array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $location['service_areas'] ?? array() ) ) ) );
			if ( in_array( $type, array( 'service_area', 'hybrid' ), true ) ) {
				$service_rows++;
				if ( ! $areas ) {
					$issues[] = array( 'severity' => 'high', 'location_id' => absint( $location['id'] ), 'issue' => 'No verified service areas are recorded for this service-area or hybrid record.', 'action' => 'Add only areas the business genuinely serves.' );
				}
			}
			foreach ( $areas as $area ) {
				$key = $this->normalize_text( $area );
				if ( ! $key ) {
					continue;
				}
				$total_areas++;
				$area_owners[ $key ][] = array( 'location_id' => absint( $location['id'] ), 'area' => $area );
			}
			if ( 'service_area' === $type && ! empty( $location['has_customer_location'] ) ) {
				$issues[] = array( 'severity' => 'critical', 'location_id' => absint( $location['id'] ), 'issue' => 'A service-area-only record is marked as customer-facing.', 'action' => 'Remove the public-address claim unless customers are genuinely served at the location.' );
			}
		}
		foreach ( $area_owners as $owners ) {
			$location_ids = array_unique( wp_list_pluck( $owners, 'location_id' ) );
			if ( count( $location_ids ) > 1 ) {
				$issues[] = array( 'severity' => 'medium', 'location_id' => 0, 'issue' => sprintf( 'The service area “%s” is assigned to multiple local records.', $owners[0]['area'] ), 'action' => 'Confirm ownership and avoid competing local landing pages.' );
			}
		}
		if ( $service_rows && empty( $strategy['local']['service_area_policy'] ) ) {
			$issues[] = array( 'severity' => 'medium', 'location_id' => 0, 'issue' => 'The Website Strategy does not define a service-area policy.', 'action' => 'Document which areas are genuinely served and what evidence is required before creating a location page.' );
		}
		return array(
			'status'                => count( array_filter( $issues, function( $item ) { return in_array( $item['severity'], array( 'critical', 'high' ), true ); } ) ) ? 'needs_changes' : ( $issues ? 'review' : 'ready' ),
			'service_area_records'  => $service_rows,
			'unique_service_areas'  => count( $area_owners ),
			'total_area_assignments'=> $total_areas,
			'policy_defined'        => ! empty( $strategy['local']['service_area_policy'] ),
			'issues'                => $issues,
			'policy_note'           => 'A service-area name alone is not sufficient reason to create a landing page. Require genuine service evidence and a distinct user need.',
		);
	}

	private function landing_architecture( array $locations, array $strategy ) {
		$pages      = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => 1000, 'orderby' => 'ID', 'order' => 'ASC' ) );
		$page_index = array();
		foreach ( $pages as $page ) {
			$text = $this->normalize_text( $page->post_title . ' ' . $page->post_name );
			$page_index[] = array( 'id' => absint( $page->ID ), 'title' => $page->post_title, 'slug' => $page->post_name, 'status' => $page->post_status, 'tokens' => $this->tokens( $text ), 'url' => get_permalink( $page ) );
		}
		$offerings = array_values( array_filter( (array) ( $strategy['main_offerings'] ?? array() ) ) );
		if ( ! $offerings ) {
			foreach ( $locations as $location ) {
				$offerings = array_merge( $offerings, (array) ( $location['services'] ?? array() ) );
			}
			$offerings = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $offerings ) ) ) );
		}
		$service_coverage = array();
		foreach ( array_slice( $offerings, 0, 100 ) as $offering ) {
			$match = $this->best_page_match( $offering, $page_index );
			$service_coverage[] = array(
				'offering'      => sanitize_text_field( $offering ),
				'covered'       => ! empty( $match ) && (float) $match['similarity'] >= 0.5,
				'matched_page'  => $match,
				'recommendation'=> ! empty( $match ) && (float) $match['similarity'] >= 0.5 ? 'Review and strengthen the existing page.' : 'Plan one dedicated page only when the offering is commercially important and distinct.',
			);
		}
		$location_coverage = array();
		foreach ( $locations as $location ) {
			$assigned = absint( $location['page_id'] ?? 0 );
			$areas    = (array) ( $location['service_areas'] ?? array() );
			$location_coverage[] = array(
				'location_id'    => absint( $location['id'] ),
				'label'          => sanitize_text_field( $location['location_label'] ?: $location['business_name'] ),
				'location_type'  => sanitize_key( $location['location_type'] ),
				'assigned_page_id'=> $assigned,
				'assigned_page'  => $assigned ? array( 'id' => $assigned, 'title' => get_the_title( $assigned ), 'url' => get_permalink( $assigned ), 'status' => get_post_status( $assigned ) ) : null,
				'service_areas'  => $areas,
				'services'       => (array) ( $location['services'] ?? array() ),
				'needs_page_review' => ! $assigned,
				'page_policy'    => ! $assigned ? 'Review whether one genuine location or service-area page is justified. Do not create doorway pages for every area.' : 'Review uniqueness, proof, conversions and internal links on the assigned page.',
			);
		}
		$covered = count( array_filter( $service_coverage, function( $item ) { return ! empty( $item['covered'] ); } ) );
		return array(
			'status'             => $offerings && $covered < count( $service_coverage ) ? 'gaps_found' : 'review',
			'offering_count'     => count( $offerings ),
			'covered_offerings'  => $covered,
			'uncovered_offerings'=> max( 0, count( $service_coverage ) - $covered ),
			'service_coverage'   => $service_coverage,
			'location_coverage'  => $location_coverage,
			'architecture_rule'  => 'Map one clear search intent to one primary page. Use supporting pages only when they add distinct local evidence or answer a different user need.',
		);
	}

	private function citation_health( array $citations, array $locations ) {
		$settings    = Ikon_SEO_Plugin::settings();
		$target      = max( 50, min( 100, absint( $settings['local_citation_target_percent'] ?? 90 ) ) );
		$today       = current_time( 'Y-m-d', true );
		$consistent  = 0;
		$corrections = 0;
		$duplicates  = 0;
		$stale       = 0;
		$pending     = 0;
		$items       = array();
		foreach ( $citations as $citation ) {
			$needs_correction = ! empty( $citation['correction_required'] );
			$duplicate        = ! empty( $citation['duplicate_warning'] );
			$is_stale         = ! empty( $citation['next_review'] ) && $citation['next_review'] < $today;
			$is_pending       = in_array( sanitize_key( $citation['status'] ?? '' ), array( 'pending', 'unverified', 'needs_review' ), true );
			if ( $needs_correction ) { $corrections++; }
			if ( $duplicate ) { $duplicates++; }
			if ( $is_stale ) { $stale++; }
			if ( $is_pending ) { $pending++; }
			if ( ! $needs_correction && ! $duplicate && ! $is_pending ) { $consistent++; }
			if ( $needs_correction || $duplicate || $is_stale || $is_pending ) {
				$items[] = array(
					'id'                  => absint( $citation['id'] ),
					'directory_name'      => sanitize_text_field( $citation['directory_name'] ?? '' ),
					'location_id'         => absint( $citation['location_id'] ?? 0 ),
					'status'              => sanitize_key( $citation['status'] ?? '' ),
					'correction_required' => $needs_correction,
					'duplicate_warning'   => $duplicate,
					'stale'               => $is_stale,
					'next_review'         => sanitize_text_field( $citation['next_review'] ?? '' ),
				);
			}
		}
		$total = count( $citations );
		$score = $total ? (int) round( ( $consistent / $total ) * 100 ) : 0;
		return array(
			'status'              => ! $total ? 'no_evidence' : ( $score >= $target && ! $corrections && ! $duplicates ? 'healthy' : 'needs_review' ),
			'total'               => $total,
			'consistent'          => $consistent,
			'consistency_percent' => $score,
			'target_percent'      => $target,
			'corrections'         => $corrections,
			'duplicates'          => $duplicates,
			'stale'               => $stale,
			'pending'             => $pending,
			'items'               => array_slice( $items, 0, 100 ),
			'note'                => 'Citation quantity is not a substitute for accurate, relevant and maintained business information.',
		);
	}

	private function review_workflow() {
		global $wpdb;
		$table = $this->review_table();
		$profile = $this->profile->fingerprint();
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE profile_id=%s ORDER BY FIELD(status,'open','in_progress','draft_staged','responded','dismissed'), due_at ASC, id DESC LIMIT 300", $profile ), ARRAY_A );
		$counts = array( 'open' => 0, 'overdue' => 0, 'in_progress' => 0, 'draft_staged' => 0, 'responded' => 0, 'dismissed' => 0, 'negative_open' => 0 );
		$items  = array();
		$now    = time();
		foreach ( $rows ?: array() as $row ) {
			$item = $this->public_review_task( $row );
			$status = $item['status'];
			if ( isset( $counts[ $status ] ) ) { $counts[ $status ]++; }
			if ( $item['due_at'] && strtotime( $item['due_at'] . ' UTC' ) < $now && ! in_array( $status, array( 'responded', 'dismissed' ), true ) ) {
				$item['overdue'] = true;
				$counts['overdue']++;
			}
			if ( $item['star_rating'] && $item['star_rating'] <= 3 && ! in_array( $status, array( 'responded', 'dismissed' ), true ) ) {
				$counts['negative_open']++;
			}
			$items[] = $item;
		}
		return array(
			'status'        => $counts['overdue'] || $counts['negative_open'] ? 'attention_required' : ( $counts['open'] || $counts['in_progress'] || $counts['draft_staged'] ? 'active' : 'clear' ),
			'counts'        => $counts,
			'items'         => $items,
			'privacy_note'  => 'Only review identifiers, rating, reply state and workflow timing are stored. Review text is not stored permanently by this module.',
			'approval_note' => 'Reply content must be staged and explicitly approved in the Business Profile screen before it is sent.',
		);
	}

	private function conversion_report( $days = 30, $refresh = false ) {
		global $wpdb;
		$days = max( 7, min( 90, absint( $days ) ) );
		$table = $this->conversion_table();
		$profile = $this->profile->fingerprint();
		$latest_end = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(period_end) FROM {$table} WHERE profile_id=%s", $profile ) );
		$rows = array();
		if ( $latest_end ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE profile_id=%s AND period_end=%s ORDER BY source ASC, location_id ASC, metric_name ASC", $profile, $latest_end ), ARRAY_A );
		}
		$totals = array();
		$by_location = array();
		foreach ( $rows ?: array() as $row ) {
			$source = sanitize_key( $row['source'] );
			$metric = sanitize_key( $row['metric_name'] );
			$value  = (float) $row['metric_value'];
			$totals[ $source ][ $metric ] = (float) ( $totals[ $source ][ $metric ] ?? 0 ) + $value;
			$location_id = absint( $row['location_id'] );
			if ( $location_id ) {
				$by_location[ $location_id ][ $metric ] = (float) ( $by_location[ $location_id ][ $metric ] ?? 0 ) + $value;
			}
		}
		$strategy = $this->strategy->get();
		$lead_channels = (array) ( $strategy['local']['lead_channels'] ?? array() );
		$coverage = array();
		foreach ( $lead_channels as $channel ) {
			$key = $this->normalize_text( $channel );
			$matched = false;
			foreach ( $totals as $source => $metrics ) {
				foreach ( $metrics as $metric => $value ) {
					$tokens = $this->normalize_text( $metric );
					if ( ( false !== strpos( $key, 'call' ) && false !== strpos( $tokens, 'call' ) ) || ( false !== strpos( $key, 'book' ) && false !== strpos( $tokens, 'book' ) ) || ( false !== strpos( $key, 'whatsapp' ) && false !== strpos( $tokens, 'conversation' ) ) || ( false !== strpos( $key, 'form' ) && 'key events' === str_replace( '_', ' ', $tokens ) ) ) {
						$matched = true;
					}
				}
			}
			$coverage[] = array( 'channel' => sanitize_text_field( $channel ), 'measured' => $matched );
		}
		return array(
			'status'            => $rows ? 'available' : 'no_snapshot',
			'latest_period_end' => sanitize_text_field( $latest_end ?: '' ),
			'days_requested'    => $days,
			'totals'            => $totals,
			'by_location'       => $by_location,
			'lead_channel_coverage' => $coverage,
			'measurement_gaps'  => array_values( array_map( function( $item ) { return $item['channel']; }, array_filter( $coverage, function( $item ) { return empty( $item['measured'] ); } ) ) ),
			'note'              => 'Conversions should be configured as real calls, forms, messages, bookings or sales. Traffic alone is not a local growth outcome.',
		);
	}

	private function competitor_prominence() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();
		$stale_days = max( 14, min( 365, absint( $settings['local_prominence_stale_days'] ?? 90 ) ) );
		$cutoff = gmdate( 'Y-m-d', strtotime( '-' . $stale_days . ' days' ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->prominence_table()} WHERE profile_id=%s AND status='active' ORDER BY observed_at DESC, id DESC LIMIT 500", $this->profile->fingerprint() ), ARRAY_A );
		$items = array();
		$by_competitor = array();
		$by_source = array();
		$stale = 0;
		foreach ( $rows ?: array() as $row ) {
			$item = $this->public_prominence( $row );
			$item['stale'] = $item['observed_at'] && $item['observed_at'] < $cutoff;
			if ( $item['stale'] ) { $stale++; }
			$key = strtolower( $item['competitor_domain'] ?: $item['competitor_name'] );
			$by_competitor[ $key ] = ( $by_competitor[ $key ] ?? 0 ) + 1;
			$by_source[ $item['source_type'] ] = ( $by_source[ $item['source_type'] ] ?? 0 ) + 1;
			$items[] = $item;
		}
		$authority_gaps = array();
		$authority = $this->authority->report( 50, false );
		if ( is_array( $authority ) ) {
			$authority_gaps = array_slice( (array) ( $authority['competitor_gaps'] ?? array() ), 0, 25 );
		}
		arsort( $by_competitor );
		arsort( $by_source );
		return array(
			'status'             => $items || $authority_gaps ? 'evidence_available' : 'no_evidence',
			'evidence_count'     => count( $items ),
			'stale_count'        => $stale,
			'stale_days'         => $stale_days,
			'by_competitor'      => $by_competitor,
			'by_source'          => $by_source,
			'items'              => array_slice( $items, 0, 100 ),
			'authority_gap_leads'=> $authority_gaps,
			'note'               => 'Prominence is broader than one metric. Review relevant links, citations, reviews, brand recognition and real-world reputation without manufacturing signals.',
		);
	}

	private function readiness( array $strategy, array $profile, array $areas, array $landing, array $citations, array $reviews, array $conversions, array $prominence ) {
		$checks = array();
		$this->add_readiness( $checks, 'strategy_mode', in_array( $strategy['mode'], array( 'local_business', 'hybrid' ), true ), 10, 'Use Local Business or Hybrid mode for the local growth workflow.' );
		$this->add_readiness( $checks, 'locations', ! empty( $profile['locations'] ), 12, 'Add at least one real storefront, hybrid or service-area record.' );
		$this->add_readiness( $checks, 'profile_alignment', 'needs_changes' !== $profile['status'], 12, 'Resolve confirmed business-information mismatches.' );
		$this->add_readiness( $checks, 'service_area_policy', ! empty( $areas['policy_defined'] ) || empty( $areas['service_area_records'] ), 8, 'Define a service-area policy before creating area pages.' );
		$this->add_readiness( $checks, 'landing_architecture', empty( $landing['uncovered_offerings'] ), 12, 'Map commercially important services to one clear primary page each.' );
		$this->add_readiness( $checks, 'citation_health', in_array( $citations['status'], array( 'healthy', 'no_evidence' ), true ), 10, 'Review inaccurate, duplicate or stale citation records.' );
		$this->add_readiness( $checks, 'review_workflow', 'attention_required' !== $reviews['status'], 10, 'Address overdue review-response tasks through the approval queue.' );
		$this->add_readiness( $checks, 'conversion_measurement', 'available' === $conversions['status'], 14, 'Connect and configure real local conversion measurement.' );
		$this->add_readiness( $checks, 'competitor_evidence', 'evidence_available' === $prominence['status'], 12, 'Store current competitor prominence observations from approved research.' );
		$score = 0;
		$max   = 0;
		foreach ( $checks as $check ) {
			$max += $check['weight'];
			if ( $check['passed'] ) { $score += $check['weight']; }
		}
		$score = $max ? (int) round( ( $score / $max ) * 100 ) : 0;
		return array(
			'score'  => $score,
			'status' => $score >= 85 ? 'strong' : ( $score >= 65 ? 'developing' : 'foundation_needed' ),
			'checks' => $checks,
			'note'   => 'Readiness reflects configured evidence and workflow completeness. It is not a local ranking score.',
		);
	}

	private function recommendations( array $readiness, array $profile, array $areas, array $landing, array $citations, array $reviews, array $conversions, array $prominence ) {
		$items = array();
		foreach ( $readiness['checks'] as $check ) {
			if ( ! $check['passed'] ) {
				$items[] = array( 'priority' => $check['weight'] >= 12 ? 'high' : 'medium', 'category' => sanitize_key( $check['id'] ), 'action' => $check['action'], 'approval_required' => false );
			}
		}
		if ( ! empty( $profile['critical_mismatches'] ) ) {
			$items[] = array( 'priority' => 'critical', 'category' => 'profile_alignment', 'action' => 'Review confirmed website-versus-profile mismatches before changing public information.', 'approval_required' => true );
		}
		if ( ! empty( $reviews['counts']['negative_open'] ) ) {
			$items[] = array( 'priority' => 'high', 'category' => 'reviews', 'action' => 'Prepare personalized responses for open one-to-three-star reviews and submit them through administrator approval.', 'approval_required' => true );
		}
		if ( ! empty( $areas['issues'] ) ) {
			$items[] = array( 'priority' => 'high', 'category' => 'service_areas', 'action' => 'Resolve service-area ownership, address visibility and policy issues before creating location content.', 'approval_required' => false );
		}
		if ( ! empty( $landing['uncovered_offerings'] ) ) {
			$items[] = array( 'priority' => 'high', 'category' => 'landing_pages', 'action' => 'Review uncovered high-value offerings and approve only genuinely distinct service pages.', 'approval_required' => true );
		}
		if ( ! empty( $citations['corrections'] ) || ! empty( $citations['duplicates'] ) ) {
			$items[] = array( 'priority' => 'high', 'category' => 'citations', 'action' => 'Correct inaccurate citation records and investigate duplicates using the listing owner accounts.', 'approval_required' => true );
		}
		if ( ! empty( $conversions['measurement_gaps'] ) ) {
			$items[] = array( 'priority' => 'medium', 'category' => 'measurement', 'action' => 'Configure measurement for: ' . implode( ', ', array_slice( $conversions['measurement_gaps'], 0, 5 ) ) . '.', 'approval_required' => true );
		}
		if ( ! empty( $prominence['stale_count'] ) ) {
			$items[] = array( 'priority' => 'medium', 'category' => 'competitors', 'action' => 'Refresh stale competitor prominence observations before using them in strategy decisions.', 'approval_required' => false );
		}
		$weight = array( 'critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1 );
		usort( $items, function( $a, $b ) use ( $weight ) { return ( $weight[ $b['priority'] ] ?? 0 ) <=> ( $weight[ $a['priority'] ] ?? 0 ); } );
		return array_slice( $this->unique_recommendations( $items ), 0, 20 );
	}

	private function store_review_task( array $location, array $review ) {
		global $wpdb;
		$name = sanitize_text_field( $review['name'] ?? '' );
		if ( ! $name ) {
			return false;
		}
		$rating_map = array( 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'one_star' => 1, 'two_star' => 2, 'three_star' => 3, 'four_star' => 4, 'five_star' => 5 );
		$rating_key = sanitize_key( $review['star_rating'] ?? '' );
		$rating     = absint( $rating_map[ $rating_key ] ?? 0 );
		$created    = $this->mysql_datetime( $review['create_time'] ?? '' );
		$updated    = $this->mysql_datetime( $review['update_time'] ?? '' );
		$reply      = ! empty( $review['owner_reply'] );
		$settings   = Ikon_SEO_Plugin::settings();
		$response_days = max( 1, min( 30, absint( $settings['local_review_response_days'] ?? 3 ) ) );
		$due = $created ? gmdate( 'Y-m-d H:i:s', strtotime( $created . ' UTC +' . $response_days . ' days' ) ) : gmdate( 'Y-m-d H:i:s', time() + $response_days * DAY_IN_SECONDS );
		$table = $this->review_table();
		$hash  = hash( 'sha256', $name );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE profile_id=%s AND review_hash=%s", $this->profile->fingerprint(), $hash ), ARRAY_A );
		$status = $reply ? 'responded' : sanitize_key( $existing['status'] ?? 'open' );
		if ( $reply ) {
			$status = 'responded';
		} elseif ( in_array( $status, array( 'responded', 'dismissed' ), true ) && empty( $review['owner_reply'] ) ) {
			$status = 'open';
		}
		$data = array(
			'profile_id'      => $this->profile->fingerprint(),
			'location_id'     => absint( $location['id'] ),
			'review_hash'     => $hash,
			'review_ref'      => $name,
			'star_rating'     => $rating,
			'has_comment'     => ! empty( $review['comment'] ) ? 1 : 0,
			'has_reply'       => $reply ? 1 : 0,
			'status'          => $status,
			'due_at'          => $due,
			'first_seen_at'   => $existing['first_seen_at'] ?? current_time( 'mysql', true ),
			'last_seen_at'    => current_time( 'mysql', true ),
			'review_created_at'=> $created,
			'review_updated_at'=> $updated,
			'responded_at'    => $reply ? ( $this->mysql_datetime( $review['reply_update_time'] ?? '' ) ?: current_time( 'mysql', true ) ) : null,
			'owner_id'        => absint( $existing['owner_id'] ?? 0 ),
			'notes'           => sanitize_textarea_field( $existing['notes'] ?? '' ),
			'updated_at'      => current_time( 'mysql', true ),
		);
		if ( $existing ) {
			return false !== $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ) );
		}
		$data['created_at'] = current_time( 'mysql', true );
		return false !== $wpdb->insert( $table, $data );
	}

	private function review_task( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->review_table()} WHERE id=%d AND profile_id=%s", absint( $id ), $this->profile->fingerprint() ), ARRAY_A );
		return $row ? $this->public_review_task( $row ) : null;
	}

	private function prominence_entry( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->prominence_table()} WHERE id=%d AND profile_id=%s", absint( $id ), $this->profile->fingerprint() ), ARRAY_A );
		return $row ? $this->public_prominence( $row ) : null;
	}

	private function public_review_task( array $row ) {
		$owner = ! empty( $row['owner_id'] ) ? get_user_by( 'id', absint( $row['owner_id'] ) ) : null;
		return array(
			'id'               => absint( $row['id'] ?? 0 ),
			'location_id'      => absint( $row['location_id'] ?? 0 ),
			'star_rating'      => absint( $row['star_rating'] ?? 0 ),
			'has_comment'      => ! empty( $row['has_comment'] ),
			'has_reply'        => ! empty( $row['has_reply'] ),
			'status'           => sanitize_key( $row['status'] ?? 'open' ),
			'due_at'           => sanitize_text_field( $row['due_at'] ?? '' ),
			'first_seen_at'    => sanitize_text_field( $row['first_seen_at'] ?? '' ),
			'last_seen_at'     => sanitize_text_field( $row['last_seen_at'] ?? '' ),
			'review_created_at'=> sanitize_text_field( $row['review_created_at'] ?? '' ),
			'review_updated_at'=> sanitize_text_field( $row['review_updated_at'] ?? '' ),
			'responded_at'     => sanitize_text_field( $row['responded_at'] ?? '' ),
			'owner_id'         => absint( $row['owner_id'] ?? 0 ),
			'owner_name'       => $owner ? $owner->display_name : '',
			'notes'            => sanitize_textarea_field( $row['notes'] ?? '' ),
			'created_at'       => sanitize_text_field( $row['created_at'] ?? '' ),
			'updated_at'       => sanitize_text_field( $row['updated_at'] ?? '' ),
		);
	}

	private function public_prominence( array $row ) {
		return array(
			'id'                => absint( $row['id'] ?? 0 ),
			'competitor_name'   => sanitize_text_field( $row['competitor_name'] ?? '' ),
			'competitor_domain' => sanitize_text_field( $row['competitor_domain'] ?? '' ),
			'query'             => sanitize_text_field( $row['query_text'] ?? '' ),
			'source_type'       => sanitize_key( $row['source_type'] ?? '' ),
			'source_url'        => esc_url_raw( $row['source_url'] ?? '' ),
			'evidence'          => sanitize_textarea_field( $row['evidence_text'] ?? '' ),
			'metric_name'       => sanitize_text_field( $row['metric_name'] ?? '' ),
			'metric_value'      => null === $row['metric_value'] || '' === $row['metric_value'] ? null : (float) $row['metric_value'],
			'confidence'        => sanitize_key( $row['confidence'] ?? 'medium' ),
			'observed_at'       => sanitize_text_field( $row['observed_at'] ?? '' ),
			'status'            => sanitize_key( $row['status'] ?? 'active' ),
			'created_at'        => sanitize_text_field( $row['created_at'] ?? '' ),
			'updated_at'        => sanitize_text_field( $row['updated_at'] ?? '' ),
		);
	}

	private function best_page_match( $text, array $page_index ) {
		$tokens = $this->tokens( $text );
		if ( ! $tokens ) {
			return null;
		}
		$best = null;
		foreach ( $page_index as $page ) {
			$intersection = array_intersect( $tokens, $page['tokens'] );
			$union = array_unique( array_merge( $tokens, $page['tokens'] ) );
			$similarity = $union ? count( $intersection ) / count( $union ) : 0;
			if ( null === $best || $similarity > $best['similarity'] ) {
				$best = array( 'id' => $page['id'], 'title' => $page['title'], 'slug' => $page['slug'], 'status' => $page['status'], 'url' => $page['url'], 'similarity' => round( $similarity, 3 ) );
			}
		}
		return $best;
	}

	private function add_readiness( array &$checks, $id, $passed, $weight, $action ) {
		$checks[] = array( 'id' => sanitize_key( $id ), 'passed' => (bool) $passed, 'weight' => absint( $weight ), 'action' => sanitize_text_field( $action ) );
	}

	private function unique_recommendations( array $items ) {
		$seen = array();
		$out  = array();
		foreach ( $items as $item ) {
			$key = md5( strtolower( trim( $item['action'] ) ) );
			if ( isset( $seen[ $key ] ) ) { continue; }
			$seen[ $key ] = true;
			$out[] = $item;
		}
		return $out;
	}

	private function normalize_text( $text ) {
		$text = strtolower( remove_accents( wp_strip_all_tags( (string) $text ) ) );
		$text = preg_replace( '/[^a-z0-9]+/', ' ', $text );
		return trim( preg_replace( '/\s+/', ' ', $text ) );
	}

	private function tokens( $text ) {
		$stop = array( 'the','and','or','for','in','of','to','a','an','with','near','best','top','service','services','company' );
		return array_values( array_unique( array_filter( explode( ' ', $this->normalize_text( $text ) ), function( $token ) use ( $stop ) { return strlen( $token ) > 2 && ! in_array( $token, $stop, true ); } ) ) );
	}

	private function mysql_datetime( $value ) {
		$value = sanitize_text_field( $value );
		if ( ! $value ) { return null; }
		$time = strtotime( $value );
		return $time ? gmdate( 'Y-m-d H:i:s', $time ) : null;
	}

	private function review_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_local_review_tasks';
	}

	private function prominence_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_local_prominence';
	}

	private function conversion_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_local_conversions';
	}
}
