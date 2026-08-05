<?php

defined( 'ABSPATH' ) || exit;

/**
 * Builds a review-first website strategy from evidence already available
 * inside WordPress and from connected read-only data sources.
 */
final class Ikon_SEO_Auto_Discovery {
	const OPTION_KEY = 'ikon_seo_auto_discovery_v1';
	const CRON_HOOK  = 'ikon_seo_auto_discovery_once';
	const VERSION    = '1.0';

	private $profile;
	private $strategy;
	private $inventory;
	private $search_intelligence;
	private $analytics;
	private $automation;
	private $local;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Strategy $strategy,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Search_Intelligence $search_intelligence,
		Ikon_SEO_Analytics $analytics,
		Ikon_SEO_Automation $automation,
		Ikon_SEO_Local $local,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->profile             = $profile;
		$this->strategy            = $strategy;
		$this->inventory           = $inventory;
		$this->search_intelligence = $search_intelligence;
		$this->analytics           = $analytics;
		$this->automation          = $automation;
		$this->local               = $local;
		$this->history             = $history;
		$this->logger              = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_run' ) );
		add_action( 'admin_init', array( $this, 'maybe_schedule_first_run' ) );
	}

	public function maybe_schedule_first_run() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['auto_discovery_enabled'] ) || $this->has_report() ) {
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 30, self::CRON_HOOK );
		}
	}

	public function scheduled_run() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['auto_discovery_enabled'] ) || $this->has_report() ) {
			return;
		}

		$this->run(
			true,
			absint( $settings['auto_discovery_max_pages'] ?? 100 ),
			'scheduled'
		);
	}

	public function has_report() {
		$report = get_option( self::OPTION_KEY, array() );
		return is_array( $report ) && ! empty( $report['generated_at'] );
	}

	public function report() {
		$report = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $report ) || empty( $report['generated_at'] ) ) {
			return $this->empty_report();
		}

		return $report;
	}

	public function run( $refresh = true, $limit = 100, $source = 'admin' ) {
		$previous_report = $this->report();
		$limit    = max( 10, min( 300, absint( $limit ) ) );
		$settings = Ikon_SEO_Plugin::settings();
		$profile  = $this->profile->get();
		$strategy = $this->strategy->get();
		$inventory = $this->inventory->scan( (bool) $refresh );
		$pages     = $this->collect_pages( $limit );
		$home      = $this->fetch_homepage();

		$combined_text = trim(
			implode(
				"\n",
				array_merge(
					array( $home['text'] ),
					array_map(
						function( $page ) {
							return $page['title'] . "\n" . $page['text'];
						},
						$pages
					)
				)
			)
		);

		$contacts    = $this->detect_contacts( $settings, $home, $pages );
		$conversions = $this->detect_conversions( $home, $pages, $contacts );
		$offerings   = $this->detect_offerings( $pages );
		$locations   = $this->detect_locations( $settings, $profile, $pages, $combined_text );
		$environment = $this->environment_evidence( $pages );
		$mode        = $this->detect_mode( $environment, $offerings, $conversions, $pages );
		$language    = $this->detect_language( $settings, $home );
		$currency    = $this->detect_currency( $settings, $environment, $language );
		$industry    = $this->detect_industry( $combined_text, $offerings, $environment );
		$entity      = $this->recommended_entity( $industry );
		$goal        = $this->recommended_goal( $mode, $conversions );
		$monetization = $this->recommended_monetization( $mode );
		$audience    = $this->build_audience( $mode, $offerings, $locations );
		$value       = $this->build_value_proposition( $profile, $offerings, $locations, $conversions );
		$metrics     = $this->build_success_metrics( $conversions, $settings );
		$conflicts   = $this->detect_conflicts( $contacts, $language, $currency, $settings, $environment );

		$facts = array();

		$this->add_fact(
			$facts,
			'strategy.website_mode',
			'Operating mode',
			'strategy',
			'website_mode',
			$mode['value'],
			$mode['label'],
			$mode['confidence'],
			$mode['score'],
			$mode['sources'],
			$strategy['mode'] ?? '',
			false,
			false
		);
		$this->add_fact(
			$facts,
			'strategy.strategy_primary_goal',
			'Primary business goal',
			'strategy',
			'strategy_primary_goal',
			$goal['value'],
			$goal['label'],
			$goal['confidence'],
			$goal['score'],
			$goal['sources'],
			$strategy['primary_goal'] ?? '',
			false,
			false
		);
		$this->add_fact(
			$facts,
			'strategy.strategy_main_offerings',
			'Priority services, products or topics',
			'strategy',
			'strategy_main_offerings',
			$offerings,
			implode( "\n", $offerings ),
			empty( $offerings ) ? 'low' : 'high',
			empty( $offerings ) ? 35 : 88,
			array( 'Published page, post, product and category titles' ),
			implode( "\n", (array) ( $strategy['main_offerings'] ?? array() ) ),
			true,
			false
		);
		$this->add_fact(
			$facts,
			'strategy.strategy_primary_conversions',
			'Primary conversion actions',
			'strategy',
			'strategy_primary_conversions',
			$conversions,
			implode( "\n", $conversions ),
			empty( $conversions ) ? 'low' : 'high',
			empty( $conversions ) ? 30 : 91,
			array( 'Visible phone, form, WhatsApp, booking and commerce actions' ),
			implode( "\n", (array) ( $strategy['primary_conversions'] ?? array() ) ),
			true,
			false
		);
		$this->add_fact(
			$facts,
			'strategy.strategy_monetization_model',
			'Monetisation model',
			'strategy',
			'strategy_monetization_model',
			$monetization['value'],
			$monetization['label'],
			$monetization['confidence'],
			$monetization['score'],
			$monetization['sources'],
			$strategy['monetization_model'] ?? '',
			false,
			false
		);
		$this->add_fact(
			$facts,
			'strategy.strategy_target_audience',
			'Target audience',
			'strategy',
			'strategy_target_audience',
			$audience,
			$audience,
			'medium',
			68,
			array( 'Inferred from offerings, conversion paths and confirmed locations' ),
			$strategy['target_audience'] ?? '',
			true,
			false
		);
		$this->add_fact(
			$facts,
			'strategy.strategy_value_proposition',
			'Value proposition',
			'strategy',
			'strategy_value_proposition',
			$value,
			$value,
			'medium',
			62,
			array( 'Summarised from visible offerings and contact paths; business differentiation requires confirmation' ),
			$strategy['value_proposition'] ?? '',
			true,
			false
		);
		$this->add_fact(
			$facts,
			'strategy.strategy_success_metrics',
			'Success metrics',
			'strategy',
			'strategy_success_metrics',
			$metrics,
			implode( "\n", $metrics ),
			'medium',
			72,
			array( 'Detected conversion actions and available Search Console or Analytics connections' ),
			implode( "\n", (array) ( $strategy['success_metrics'] ?? array() ) ),
			true,
			false
		);

		$lead_channels = array_values(
			array_filter(
				$conversions,
				function( $value ) {
					return ! in_array( $value, array( 'Purchases', 'Add to cart', 'Checkout starts', 'Affiliate clicks' ), true );
				}
			)
		);
		if ( $lead_channels ) {
			$this->add_fact(
				$facts,
				'strategy.strategy_local_lead_channels',
				'Local lead channels',
				'strategy',
				'strategy_local_lead_channels',
				$lead_channels,
				implode( "\n", $lead_channels ),
				'high',
				88,
				array( 'Visible conversion controls on the connected website' ),
				implode( "\n", (array) ( $strategy['local']['lead_channels'] ?? array() ) ),
				true,
				false
			);
		}

		$service_policy = $this->build_service_area_policy( $locations );
		if ( $service_policy ) {
			$this->add_fact(
				$facts,
				'strategy.strategy_local_service_area_policy',
				'Service-area policy',
				'strategy',
				'strategy_local_service_area_policy',
				$service_policy,
				$service_policy,
				'medium',
				66,
				array( 'Existing location and profile evidence' ),
				$strategy['local']['service_area_policy'] ?? '',
				true,
				false
			);
		}

		$editorial_standards = 'Use original, useful content written for the website audience. Do not invent prices, guarantees, reviews, qualifications or operational claims. Require human review before publication.';
		$this->add_fact(
			$facts,
			'strategy.strategy_editorial_standards',
			'Editorial standards',
			'strategy',
			'strategy_editorial_standards',
			$editorial_standards,
			$editorial_standards,
			'medium',
			70,
			array( 'Safe baseline policy for approval-first publishing' ),
			$strategy['editorial_standards'] ?? '',
			true,
			false
		);

		$evidence_requirements = 'Business claims require confirmation from an authorised owner. Statistics require a named current source. Prices, service areas, credentials and guarantees must not be inferred from competitors. Use original photos or recorded media rights where applicable.';
		$this->add_fact(
			$facts,
			'strategy.strategy_evidence_requirements',
			'Evidence requirements',
			'strategy',
			'strategy_evidence_requirements',
			$evidence_requirements,
			$evidence_requirements,
			'medium',
			72,
			array( 'Safe baseline evidence policy' ),
			$strategy['evidence_requirements'] ?? '',
			true,
			false
		);

		$this->add_fact(
			$facts,
			'profile.site_name',
			'Website or business name',
			'profile',
			'site_name',
			sanitize_text_field( get_bloginfo( 'name' ) ),
			sanitize_text_field( get_bloginfo( 'name' ) ),
			'high',
			98,
			array( 'WordPress site title' ),
			$profile['site_name'] ?? '',
			false,
			true
		);
		$this->add_fact(
			$facts,
			'profile.default_language',
			'Primary language',
			'profile',
			'default_language',
			$language['value'],
			$language['value'],
			$language['confidence'],
			$language['score'],
			$language['sources'],
			$profile['default_language'] ?? '',
			false,
			false
		);
		$this->add_fact(
			$facts,
			'profile.default_currency',
			'Currency',
			'profile',
			'default_currency',
			$currency['value'],
			$currency['value'],
			$currency['confidence'],
			$currency['score'],
			$currency['sources'],
			$profile['default_currency'] ?? '',
			false,
			false
		);
		$this->add_fact(
			$facts,
			'profile.industry',
			'Industry profile',
			'profile',
			'industry',
			$industry['value'],
			$industry['label'],
			$industry['confidence'],
			$industry['score'],
			$industry['sources'],
			$settings['industry'] ?? '',
			true,
			true
		);
		$this->add_fact(
			$facts,
			'profile.business_entity_type',
			'Business entity schema type',
			'profile',
			'business_entity_type',
			$entity,
			$entity,
			$industry['confidence'],
			max( 50, $industry['score'] - 5 ),
			array( 'Recommended for the detected industry profile' ),
			$settings['business_entity_type'] ?? '',
			true,
			true
		);

		if ( $contacts['phone'] ) {
			$this->add_fact(
				$facts,
				'profile.business_phone',
				'Business phone',
				'profile',
				'business_phone',
				$contacts['phone'][0],
				$contacts['phone'][0],
				1 === count( $contacts['phone'] ) ? 'high' : 'medium',
				1 === count( $contacts['phone'] ) ? 94 : 65,
				array( 'Website profile, tel links and visible homepage content' ),
				$profile['contact']['phone'] ?? '',
				count( $contacts['phone'] ) > 1,
				false
			);
		}
		if ( $contacts['email'] ) {
			$this->add_fact(
				$facts,
				'profile.contact_email',
				'Contact email',
				'profile',
				'contact_email',
				$contacts['email'][0],
				$contacts['email'][0],
				1 === count( $contacts['email'] ) ? 'high' : 'medium',
				1 === count( $contacts['email'] ) ? 94 : 65,
				array( 'Website profile, mail links and visible homepage content' ),
				$profile['contact']['email'] ?? '',
				count( $contacts['email'] ) > 1,
				false
			);
		}
		if ( $contacts['whatsapp'] ) {
			$this->add_fact(
				$facts,
				'profile.whatsapp_url',
				'WhatsApp URL',
				'profile',
				'whatsapp_url',
				$contacts['whatsapp'][0],
				$contacts['whatsapp'][0],
				1 === count( $contacts['whatsapp'] ) ? 'high' : 'medium',
				1 === count( $contacts['whatsapp'] ) ? 92 : 64,
				array( 'Visible WhatsApp links' ),
				$profile['contact']['whatsapp_url'] ?? '',
				count( $contacts['whatsapp'] ) > 1,
				false
			);
		}
		if ( $locations ) {
			$this->add_fact(
				$facts,
				'profile.target_locations',
				'Target locations',
				'profile',
				'target_locations',
				$locations,
				implode( "\n", $locations ),
				'medium',
				64,
				array( 'Existing profile, address and published location-page evidence' ),
				implode( "\n", (array) ( $profile['target_locations'] ?? array() ) ),
				true,
				false
			);
		}

		$facts = array_values(
			array_filter(
				$facts,
				function( $fact ) {
					if ( is_array( $fact['value'] ) ) {
						return ! empty( $fact['value'] );
					}
					return '' !== trim( (string) $fact['value'] );
				}
			)
		);

		$summary = array(
			'pages_reviewed'       => count( $pages ),
			'inventory_items'      => absint( $inventory['summary']['total'] ?? 0 ),
			'high_confidence'      => count( array_filter( $facts, function( $fact ) { return 'high' === $fact['confidence']; } ) ),
			'medium_confidence'    => count( array_filter( $facts, function( $fact ) { return 'medium' === $fact['confidence']; } ) ),
			'needs_confirmation'   => count( array_filter( $facts, function( $fact ) { return ! empty( $fact['needs_confirmation'] ); } ) ),
			'conflicts'            => count( $conflicts ),
			'operating_mode'       => $mode['value'],
			'operating_mode_label' => $mode['label'],
			'connected_search'     => ! empty( $settings['gsc_refresh_token'] ) && ! empty( $settings['gsc_property'] ),
			'connected_analytics'  => ! empty( $settings['ga_refresh_token'] ) && ! empty( $settings['ga_property'] ),
		);

		$report = array(
			'version'       => self::VERSION,
			'status'        => 'complete',
			'generated_at'  => current_time( 'mysql', true ),
			'source'        => sanitize_key( $source ),
			'summary'       => $summary,
			'facts'         => $facts,
			'conflicts'     => $conflicts,
			'environment'   => $environment,
			'safety'        => array(
				'changes_pages'      => false,
				'publishes_content'  => false,
				'changes_redirects'  => false,
				'changes_indexation' => false,
				'changes_profiles'   => false,
				'applies_strategy'   => false,
			),
		);

		update_option( self::OPTION_KEY, $report, false );
		do_action( 'ikon_seo_auto_discovery_completed', $report, $previous_report );
		$settings['auto_discovery_last_run'] = $report['generated_at'];
		$settings['auto_discovery_version']  = self::VERSION;
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );

		$this->history->add(
			array(
				'category' => 'strategy',
				'status'   => 'completed',
				'title'    => 'Website auto discovery completed',
				'summary'  => sprintf(
					'Reviewed %d pages and proposed %d high-confidence strategy or profile values.',
					count( $pages ),
					absint( $summary['high_confidence'] )
				),
				'details'  => array(
					'pages_reviewed'     => count( $pages ),
					'high_confidence'    => absint( $summary['high_confidence'] ),
					'needs_confirmation' => absint( $summary['needs_confirmation'] ),
					'conflicts'          => absint( $summary['conflicts'] ),
					'source'             => sanitize_key( $source ),
				),
			),
			'system'
		);

		return $report;
	}

	public function apply(
		array $selected_fields,
		$overwrite = false,
		$create_workflow = false,
		$run_safe_task = false,
		$user_id = 0
	) {
		$report = $this->report();
		if ( empty( $report['generated_at'] ) ) {
			return new WP_Error( 'ikon_seo_discovery_missing', __( 'Run Auto Discovery before applying suggestions.', 'ikon-seo' ) );
		}

		$selected_fields = array_values(
			array_unique(
				array_filter(
					array_map( 'sanitize_text_field', $selected_fields )
				)
			)
		);
		if ( ! $selected_fields ) {
			return new WP_Error( 'ikon_seo_discovery_selection', __( 'Select at least one detected value to apply.', 'ikon-seo' ) );
		}

		$current_settings = Ikon_SEO_Plugin::settings();
		$current_profile  = $this->profile->get();
		$current_strategy = $this->strategy->get();
		$profile_input    = array();
		$strategy_input   = array();
		$applied          = array();
		$skipped          = array();

		foreach ( $report['facts'] as $fact ) {
			if ( ! in_array( $fact['id'], $selected_fields, true ) ) {
				continue;
			}

			$current_value = $fact['current_value'];
			$has_current   = is_array( $current_value )
				? ! empty( $current_value )
				: '' !== trim( (string) $current_value );

			if ( $has_current && ! $overwrite && ! $this->is_default_value( $fact, $current_settings ) ) {
				$skipped[] = array(
					'id'     => $fact['id'],
					'reason' => 'existing confirmed value preserved',
				);
				continue;
			}

			$value = is_array( $fact['value'] )
				? implode( "\n", array_map( 'sanitize_text_field', $fact['value'] ) )
				: $fact['value'];

			if ( 'profile' === $fact['group'] ) {
				$profile_input[ $fact['field'] ] = $value;
			} else {
				$strategy_input[ $fact['field'] ] = $value;
			}
			$applied[] = $fact['id'];
		}

		if ( isset( $profile_input['industry'] ) && ! isset( $profile_input['business_entity_type'] ) ) {
			foreach ( $report['facts'] as $fact ) {
				if ( 'profile.business_entity_type' === $fact['id'] ) {
					$profile_input['business_entity_type'] = $fact['value'];
					break;
				}
			}
		}
		if ( isset( $profile_input['business_entity_type'] ) && ! isset( $profile_input['industry'] ) ) {
			foreach ( $report['facts'] as $fact ) {
				if ( 'profile.industry' === $fact['id'] ) {
					$profile_input['industry'] = $fact['value'];
					break;
				}
			}
		}

		if ( $profile_input ) {
			$old_fingerprint = $this->profile->fingerprint( $current_settings );
			$clean_profile   = $this->profile->sanitize( $profile_input, $current_settings );
			if ( is_wp_error( $clean_profile ) ) {
				return $clean_profile;
			}

			$clean_profile['profile_configured'] = 1;
			$clean_profile['profile_home_url']   = home_url( '/' );
			$new_fingerprint = $this->profile->fingerprint( $clean_profile );
			if ( ! hash_equals( $old_fingerprint, $new_fingerprint ) ) {
				$clean_profile['token_hash']     = '';
				$clean_profile['connection_owner_user_id'] = 0;
				$clean_profile['token_hint']     = '';
				$clean_profile['connection_verified_at'] = '';
				$clean_profile['connection_last_seen_at'] = '';
				$clean_profile['remote_actions'] = 0;
				$clean_profile['gbp_refresh_token'] = '';
				$clean_profile['gbp_account']       = '';
				$clean_profile['gbp_last_error']    = '';
				$this->local->rebind_profile( $old_fingerprint, $new_fingerprint );
			}
			update_option( Ikon_SEO_Plugin::OPTION_KEY, $clean_profile, false );
			$this->inventory->clear_cache();
			$current_settings = $clean_profile;
		}

		if ( $strategy_input ) {
			$result = $this->strategy->save( $strategy_input, absint( $user_id ), 'auto_discovery' );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$workflow_result = null;
		if ( $create_workflow ) {
			$summary = $this->automation->summary( 5 );
			if ( empty( $summary['workflows'] ) ) {
				$workflow_result = $this->automation->create_workflow(
					$this->automation->recommended_template(),
					array(
						'owner_id'   => absint( $user_id ),
						'created_by' => absint( $user_id ),
					)
				);
			} else {
				$workflow_result = array( 'existing' => true );
			}
		}

		$safe_result = null;
		if ( $run_safe_task ) {
			$safe_result = $this->automation->run_safe_tasks( 1, false );
		}

		$this->history->add(
			array(
				'category' => 'strategy',
				'status'   => 'completed',
				'title'    => 'Auto Discovery suggestions applied',
				'summary'  => sprintf(
					'%d suggested values were applied and %d existing values were preserved.',
					count( $applied ),
					count( $skipped )
				),
				'details'  => array(
					'applied'          => $applied,
					'skipped'          => $skipped,
					'overwrite'        => (bool) $overwrite,
					'workflow_created' => ! is_wp_error( $workflow_result ) && ! empty( $workflow_result ),
					'safe_task_run'    => ! empty( $safe_result ),
					'updated_by'       => absint( $user_id ),
				),
			),
			'system'
		);

		$report['application'] = array(
			'applied_at'       => current_time( 'mysql', true ),
			'applied_fields'   => $applied,
			'skipped_fields'   => $skipped,
			'workflow_created' => ! is_wp_error( $workflow_result ) && ! empty( $workflow_result ),
			'safe_task_run'    => ! empty( $safe_result ),
			'updated_by'       => absint( $user_id ),
		);
		update_option( self::OPTION_KEY, $report, false );

		return array(
			'applied'         => $applied,
			'skipped'         => $skipped,
			'workflow_result' => $workflow_result,
			'safe_task_result'=> $safe_result,
			'strategy'        => $this->strategy->get(),
			'profile'         => $this->profile->get(),
		);
	}

	public function save_settings( array $input ) {
		$settings = Ikon_SEO_Plugin::settings();
		$settings['auto_discovery_enabled'] = ! empty( $input['enabled'] ) ? 1 : 0;
		$settings['auto_discovery_max_pages'] = max( 10, min( 300, absint( $input['max_pages'] ?? 100 ) ) );
		$settings['auto_discovery_include_connected'] = ! empty( $input['include_connected'] ) ? 1 : 0;
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );

		return array(
			'enabled'           => (bool) $settings['auto_discovery_enabled'],
			'max_pages'         => absint( $settings['auto_discovery_max_pages'] ),
			'include_connected' => (bool) $settings['auto_discovery_include_connected'],
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'run':
				return $this->run(
					! empty( $payload['refresh'] ),
					absint( $payload['max_pages'] ?? 100 ),
					'private_workflow'
				);

			case 'apply':
				return $this->apply(
					is_array( $payload['fields'] ?? null ) ? $payload['fields'] : array(),
					! empty( $payload['overwrite'] ),
					! empty( $payload['create_workflow'] ),
					! empty( $payload['run_safe_task'] ),
					absint( $user_id )
				);

			case 'save_settings':
				return $this->save_settings( $payload );

			case 'read':
			default:
				return $this->report();
		}
	}

	private function collect_pages( $limit ) {
		$post_types = array( 'page', 'post' );
		if ( post_type_exists( 'product' ) ) {
			$post_types[] = 'product';
		}

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		$pages = array();
		foreach ( $posts as $post ) {
			$content = (string) $post->post_content;
			$plain   = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( $content ) ) ) );
			$pages[] = array(
				'id'        => absint( $post->ID ),
				'post_type' => sanitize_key( $post->post_type ),
				'title'     => sanitize_text_field( get_the_title( $post ) ),
				'url'       => esc_url_raw( get_permalink( $post ) ),
				'slug'      => sanitize_title( $post->post_name ),
				'text'      => mb_substr( $plain, 0, 12000 ),
				'raw'       => mb_substr( $content, 0, 30000 ),
			);
		}

		return $pages;
	}

	private function fetch_homepage() {
		$result = array(
			'url'   => home_url( '/' ),
			'html'  => '',
			'text'  => '',
			'links' => array(),
		);

		$response = wp_safe_remote_get(
			home_url( '/' ),
			array(
				'timeout'             => 10,
				'redirection'         => 2,
				'limit_response_size' => 1024 * 1024,
				'user-agent'          => 'Ikon SEO Auto Discovery/' . IKON_SEO_VERSION,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $result;
		}

		$html = (string) wp_remote_retrieve_body( $response );
		$result['html'] = mb_substr( $html, 0, 1024 * 1024 );
		$result['text'] = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $html ) ) );

		if ( preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\']/i', $html, $matches ) ) {
			$result['links'] = array_values(
				array_unique(
					array_map( 'esc_url_raw', array_slice( $matches[1], 0, 500 ) )
				)
			);
		}

		return $result;
	}

	private function detect_contacts( array $settings, array $home, array $pages ) {
		$phones   = array();
		$emails   = array();
		$whatsapp = array();

		if ( ! empty( $settings['business_phone'] ) ) {
			$phones[] = sanitize_text_field( $settings['business_phone'] );
		}
		if ( ! empty( $settings['profile_configured'] ) && ! empty( $settings['contact_email'] ) ) {
			$emails[] = sanitize_email( $settings['contact_email'] );
		}
		if ( ! empty( $settings['whatsapp_url'] ) ) {
			$whatsapp[] = esc_url_raw( $settings['whatsapp_url'] );
		}

		$raw_sources = array( $home['html'] );
		foreach ( $pages as $page ) {
			$raw_sources[] = $page['raw'];
		}
		$raw = implode( "\n", $raw_sources );

		if ( preg_match_all( '/href=["\']tel:([^"\']+)/i', $raw, $matches ) ) {
			foreach ( $matches[1] as $phone ) {
				$phone = rawurldecode( $phone );
				$phone = trim( preg_replace( '/[^0-9+()\-.\s]/', '', $phone ) );
				if ( strlen( preg_replace( '/\D/', '', $phone ) ) >= 7 ) {
					$phones[] = $phone;
				}
			}
		}
		if ( preg_match_all( '/href=["\']mailto:([^?"\']+)/i', $raw, $matches ) ) {
			foreach ( $matches[1] as $email ) {
				$email = sanitize_email( rawurldecode( $email ) );
				if ( $email ) {
					$emails[] = $email;
				}
			}
		}
		if ( preg_match_all( '#https?://(?:wa\.me|api\.whatsapp\.com)/[^"\'\s<]+#i', $raw, $matches ) ) {
			foreach ( $matches[0] as $url ) {
				$whatsapp[] = esc_url_raw( html_entity_decode( $url, ENT_QUOTES, 'UTF-8' ) );
			}
		}

		return array(
			'phone'    => array_values( array_unique( array_filter( $phones ) ) ),
			'email'    => array_values( array_unique( array_filter( $emails ) ) ),
			'whatsapp' => array_values( array_unique( array_filter( $whatsapp ) ) ),
		);
	}

	private function detect_conversions( array $home, array $pages, array $contacts ) {
		$conversions = array();
		$raw = $home['html'];
		foreach ( $pages as $page ) {
			$raw .= "\n" . $page['raw'];
		}
		$lower = strtolower( $raw );

		if ( $contacts['phone'] || false !== strpos( $lower, 'tel:' ) ) {
			$conversions[] = 'Phone calls';
		}
		if ( $contacts['whatsapp'] || false !== strpos( $lower, 'whatsapp' ) || false !== strpos( $lower, 'wa.me' ) ) {
			$conversions[] = 'WhatsApp enquiries';
		}
		if (
			false !== strpos( $lower, '<form' ) ||
			false !== strpos( $lower, 'contact-form-7' ) ||
			false !== strpos( $lower, 'wpforms' ) ||
			false !== strpos( $lower, 'elementor-form' ) ||
			false !== strpos( $lower, 'forminator' )
		) {
			$conversions[] = 'Form submissions';
		}
		if (
			false !== strpos( $lower, 'book now' ) ||
			false !== strpos( $lower, 'booking' ) ||
			false !== strpos( $lower, 'appointment' )
		) {
			$conversions[] = 'Booking enquiries';
		}
		if ( class_exists( 'WooCommerce' ) || post_type_exists( 'product' ) ) {
			$conversions[] = 'Purchases';
			$conversions[] = 'Add to cart';
			$conversions[] = 'Checkout starts';
		}
		if ( false !== strpos( $lower, 'affiliate' ) || false !== strpos( $lower, 'sponsored' ) ) {
			$conversions[] = 'Affiliate clicks';
		}

		return array_values( array_unique( $conversions ) );
	}

	private function detect_offerings( array $pages ) {
		$generic = array(
			'home', 'homepage', 'about', 'about us', 'contact', 'contact us',
			'privacy policy', 'terms', 'terms and conditions', 'blog', 'news',
			'cart', 'checkout', 'my account', 'shop', 'store', 'faq', 'faqs',
		);
		$scored = array();

		foreach ( $pages as $page ) {
			$title = trim( $page['title'] );
			if ( '' === $title || in_array( strtolower( $title ), $generic, true ) ) {
				continue;
			}
			$score = 1;
			if ( 'product' === $page['post_type'] ) {
				$score += 5;
			}
			if ( 'page' === $page['post_type'] ) {
				$score += 3;
			}
			if ( preg_match( '/\b(service|services|cleaning|repair|installation|therapy|accounting|tax|driver|transport|curtain|floor|product|buy|shop)\b/i', $title ) ) {
				$score += 4;
			}
			if ( preg_match( '/\b(about|contact|policy|terms|team|careers?|gallery|portfolio)\b/i', $title ) ) {
				$score -= 4;
			}
			$clean = preg_replace( '/\s+(?:in|near)\s+[A-Z][A-Za-z\s-]+$/', '', $title );
			$clean = trim( preg_replace( '/\s+/', ' ', $clean ) );
			if ( strlen( $clean ) < 3 ) {
				continue;
			}
			$key = strtolower( $clean );
			if ( ! isset( $scored[ $key ] ) || $score > $scored[ $key ]['score'] ) {
				$scored[ $key ] = array( 'label' => $clean, 'score' => $score );
			}
		}

		uasort(
			$scored,
			function( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		return array_values(
			array_map(
				function( $item ) {
					return sanitize_text_field( $item['label'] );
				},
				array_slice( $scored, 0, 20, true )
			)
		);
	}

	private function detect_locations( array $settings, array $profile, array $pages, $text ) {
		$locations = array();

		foreach ( array( $settings['target_locations'] ?? '', $settings['address_locality'] ?? '', $settings['address_region'] ?? '', $settings['address_country'] ?? '' ) as $value ) {
			foreach ( preg_split( '/[\r\n,;]+/', (string) $value ) as $part ) {
				$part = sanitize_text_field( trim( $part ) );
				if ( strlen( $part ) >= 2 ) {
					$locations[] = $part;
				}
			}
		}

		foreach ( $pages as $page ) {
			if ( preg_match( '/\b(?:in|near)\s+([A-Z][A-Za-z-]+(?:\s+[A-Z][A-Za-z-]+){0,3})\b/', $page['title'], $match ) ) {
				$locations[] = sanitize_text_field( trim( $match[1] ) );
			}
		}

		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$tld_map = array(
			'.qa' => 'Qatar',
			'.ae' => 'United Arab Emirates',
			'.om' => 'Oman',
			'.ca' => 'Canada',
			'.uk' => 'United Kingdom',
			'.pk' => 'Pakistan',
			'.sa' => 'Saudi Arabia',
			'.au' => 'Australia',
		);
		foreach ( $tld_map as $suffix => $country ) {
			if ( substr( $host, -strlen( $suffix ) ) === $suffix ) {
				$locations[] = $country;
				break;
			}
		}

		return array_values( array_unique( array_filter( $locations ) ) );
	}

	private function environment_evidence( array $pages ) {
		$product_count = 0;
		$post_count    = 0;
		$page_count    = 0;
		foreach ( $pages as $page ) {
			if ( 'product' === $page['post_type'] ) {
				$product_count++;
			} elseif ( 'post' === $page['post_type'] ) {
				$post_count++;
			} elseif ( 'page' === $page['post_type'] ) {
				$page_count++;
			}
		}

		return array(
			'woocommerce'    => class_exists( 'WooCommerce' ) || post_type_exists( 'product' ),
			'elementor'      => defined( 'ELEMENTOR_VERSION' ),
			'rank_math'      => defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ),
			'yoast'          => defined( 'WPSEO_VERSION' ),
			'products'       => $product_count,
			'posts'          => $post_count,
			'pages'          => $page_count,
			'front_page_id'  => absint( get_option( 'page_on_front' ) ),
			'posts_page_id'  => absint( get_option( 'page_for_posts' ) ),
			'permalink_mode' => sanitize_text_field( get_option( 'permalink_structure' ) ),
		);
	}

	private function detect_mode( array $environment, array $offerings, array $conversions, array $pages ) {
		$local_score      = 0;
		$editorial_score  = 0;
		$ecommerce_score  = 0;
		$sources          = array();

		if ( in_array( 'Phone calls', $conversions, true ) || in_array( 'WhatsApp enquiries', $conversions, true ) || in_array( 'Form submissions', $conversions, true ) ) {
			$local_score += 4;
			$sources[] = 'Lead-focused contact actions were detected';
		}
		if ( count( $offerings ) >= 3 ) {
			$local_score += 3;
			$sources[] = 'Several commercial service or product pages were detected';
		}
		if ( $environment['posts'] >= 6 ) {
			$editorial_score += 5;
			$sources[] = 'An established post library was detected';
		} elseif ( $environment['posts'] >= 2 ) {
			$editorial_score += 2;
		}
		if ( $environment['woocommerce'] ) {
			$ecommerce_score += 5;
			$sources[] = 'WooCommerce or product content was detected';
		}
		if ( $environment['products'] >= 3 ) {
			$ecommerce_score += 4;
		}
		if ( in_array( 'Purchases', $conversions, true ) ) {
			$ecommerce_score += 3;
		}

		$mode = 'local_business';
		if ( $ecommerce_score >= 7 && $editorial_score >= 4 ) {
			$mode = 'hybrid';
		} elseif ( $local_score >= 5 && $editorial_score >= 5 ) {
			$mode = 'hybrid';
		} elseif ( $ecommerce_score >= max( 5, $local_score + 2 ) ) {
			$mode = 'ecommerce';
		} elseif ( $editorial_score >= max( 5, $local_score + 2 ) ) {
			$mode = 'editorial';
		}

		$labels = array(
			'local_business' => 'Local Business',
			'editorial'      => 'Editorial / Blog',
			'ecommerce'      => 'Ecommerce',
			'hybrid'         => 'Hybrid Business + Publisher',
		);
		$top_score = max( $local_score, $editorial_score, $ecommerce_score );
		$confidence = $top_score >= 7 ? 'high' : ( $top_score >= 4 ? 'medium' : 'low' );

		return array(
			'value'      => $mode,
			'label'      => $labels[ $mode ],
			'confidence' => $confidence,
			'score'      => min( 98, 52 + ( $top_score * 6 ) ),
			'sources'    => array_values( array_unique( $sources ) ),
			'scores'     => array(
				'local_business' => $local_score,
				'editorial'      => $editorial_score,
				'ecommerce'      => $ecommerce_score,
			),
		);
	}

	private function detect_language( array $settings, array $home ) {
		$candidates = array();
		if ( ! empty( $settings['default_language'] ) ) {
			$candidates[] = sanitize_text_field( $settings['default_language'] );
		}
		$locale = str_replace( '_', '-', determine_locale() );
		if ( preg_match( '/^[a-z]{2,3}(?:-[A-Z]{2})?$/', $locale ) ) {
			$candidates[] = $locale;
		}
		if ( preg_match( '/<html[^>]+lang=["\']([^"\']+)/i', $home['html'], $match ) ) {
			$candidates[] = sanitize_text_field( str_replace( '_', '-', $match[1] ) );
		}

		$candidates = array_values( array_unique( array_filter( $candidates ) ) );
		$value = $candidates ? $candidates[0] : 'en';
		return array(
			'value'      => $value,
			'confidence' => 1 === count( $candidates ) ? 'high' : 'medium',
			'score'      => 1 === count( $candidates ) ? 95 : 72,
			'sources'    => array( 'WordPress locale and rendered HTML language' ),
			'candidates' => $candidates,
		);
	}

	private function detect_currency( array $settings, array $environment, array $language ) {
		$candidates = array();
		if ( function_exists( 'get_woocommerce_currency' ) ) {
			$candidates[] = strtoupper( sanitize_text_field( get_woocommerce_currency() ) );
		}
		if ( ! empty( $settings['default_currency'] ) && 'USD' !== strtoupper( $settings['default_currency'] ) ) {
			$candidates[] = strtoupper( sanitize_text_field( $settings['default_currency'] ) );
		}
		$locale_map = array(
			'QA' => 'QAR',
			'AE' => 'AED',
			'OM' => 'OMR',
			'PK' => 'PKR',
			'SA' => 'SAR',
			'CA' => 'CAD',
			'GB' => 'GBP',
			'AU' => 'AUD',
			'US' => 'USD',
		);
		if ( preg_match( '/-([A-Z]{2})$/', $language['value'], $match ) && isset( $locale_map[ $match[1] ] ) ) {
			$candidates[] = $locale_map[ $match[1] ];
		}
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$tld_currency = array(
			'.qa' => 'QAR',
			'.ae' => 'AED',
			'.om' => 'OMR',
			'.pk' => 'PKR',
			'.sa' => 'SAR',
			'.ca' => 'CAD',
			'.uk' => 'GBP',
			'.au' => 'AUD',
		);
		foreach ( $tld_currency as $suffix => $currency ) {
			if ( substr( $host, -strlen( $suffix ) ) === $suffix ) {
				$candidates[] = $currency;
				break;
			}
		}
		$candidates = array_values( array_unique( array_filter( $candidates ) ) );
		$value = $candidates ? $candidates[0] : 'USD';
		return array(
			'value'      => $value,
			'confidence' => 1 === count( $candidates ) ? 'high' : 'medium',
			'score'      => 1 === count( $candidates ) ? 92 : 68,
			'sources'    => array( 'WooCommerce, active profile, locale and domain evidence' ),
			'candidates' => $candidates,
		);
	}

	private function detect_industry( $text, array $offerings, array $environment ) {
		$haystack = strtolower( $text . ' ' . implode( ' ', $offerings ) );
		$rules = array(
			'home_services'      => array( 'cleaning', 'plumbing', 'electrician', 'pest control', 'maintenance', 'repair', 'flooring', 'curtain', 'marble', 'moving' ),
			'accounting'         => array( 'accounting', 'bookkeeping', 'corporate tax', 'vat', 'audit' ),
			'finance'            => array( 'financial planning', 'investment', 'finance', 'wealth' ),
			'legal'              => array( 'law firm', 'lawyer', 'legal service', 'attorney' ),
			'real_estate'        => array( 'property for sale', 'property for rent', 'real estate', 'realtor' ),
			'therapy_healthcare' => array( 'therapy', 'mental health', 'psychotherapy', 'counseling', 'healthcare' ),
			'dental'             => array( 'dentist', 'dental clinic', 'orthodont' ),
			'driver_transport'   => array( 'driver', 'chauffeur', 'airport transfer', 'transport service' ),
			'automotive'         => array( 'car repair', 'auto service', 'automotive', 'garage' ),
			'travel_hospitality' => array( 'hotel', 'tour', 'travel agency', 'holiday' ),
			'retail'             => array( 'retail', 'shop', 'store' ),
		);

		$scores = array_fill_keys( array_keys( $rules ), 0 );
		foreach ( $rules as $key => $terms ) {
			foreach ( $terms as $term ) {
				if ( false !== strpos( $haystack, $term ) ) {
					$scores[ $key ] += 2;
				}
			}
		}
		if ( $environment['woocommerce'] && $environment['products'] >= 3 ) {
			$scores['retail'] += 4;
		}
		arsort( $scores );
		$key   = key( $scores );
		$score = current( $scores );
		if ( $score < 2 ) {
			$key = $environment['woocommerce'] ? 'ecommerce' : 'general';
		}
		$industries = $this->profile->industries();
		$label = isset( $industries[ $key ] ) ? $industries[ $key ]['label'] : ucfirst( str_replace( '_', ' ', $key ) );

		return array(
			'value'      => $key,
			'label'      => $label,
			'confidence' => $score >= 6 ? 'high' : ( $score >= 2 ? 'medium' : 'low' ),
			'score'      => min( 94, 45 + ( $score * 8 ) ),
			'sources'    => array( 'Published page titles and visible website wording' ),
		);
	}

	private function recommended_entity( $industry ) {
		$industries = $this->profile->industries();
		return $industries[ $industry ]['recommended'] ?? 'Organization';
	}

	private function recommended_goal( array $mode, array $conversions ) {
		if ( 'ecommerce' === $mode['value'] ) {
			return array(
				'value' => 'ecommerce_sales',
				'label' => 'Ecommerce sales',
				'confidence' => 'high',
				'score' => 90,
				'sources' => array( 'Ecommerce mode and purchase actions' ),
			);
		}
		if ( 'editorial' === $mode['value'] ) {
			return array(
				'value' => 'brand_visibility',
				'label' => 'Brand visibility and authority',
				'confidence' => 'medium',
				'score' => 70,
				'sources' => array( 'Editorial publishing model' ),
			);
		}
		if ( in_array( 'Booking enquiries', $conversions, true ) ) {
			return array(
				'value' => 'bookings',
				'label' => 'Bookings or appointments',
				'confidence' => 'high',
				'score' => 88,
				'sources' => array( 'Booking actions were detected' ),
			);
		}
		if ( in_array( 'Phone calls', $conversions, true ) && count( $conversions ) === 1 ) {
			return array(
				'value' => 'calls',
				'label' => 'Phone calls',
				'confidence' => 'high',
				'score' => 87,
				'sources' => array( 'Phone is the only detected conversion action' ),
			);
		}
		return array(
			'value' => 'leads',
			'label' => 'Qualified leads',
			'confidence' => 'high',
			'score' => 88,
			'sources' => array( 'Lead-focused conversion actions and commercial pages' ),
		);
	}

	private function recommended_monetization( array $mode ) {
		$map = array(
			'local_business' => array( 'service_revenue', 'Service revenue' ),
			'editorial'      => array( 'mixed', 'Mixed' ),
			'ecommerce'      => array( 'ecommerce', 'Ecommerce' ),
			'hybrid'         => array( 'mixed', 'Mixed' ),
		);
		$value = $map[ $mode['value'] ] ?? $map['local_business'];
		return array(
			'value'      => $value[0],
			'label'      => $value[1],
			'confidence' => 'medium',
			'score'      => 72,
			'sources'    => array( 'Detected website operating mode' ),
		);
	}

	private function build_audience( $mode, array $offerings, array $locations ) {
		$offering = $offerings ? implode( ', ', array_slice( $offerings, 0, 3 ) ) : 'the website offerings';
		$location = $locations ? ' in ' . implode( ', ', array_slice( $locations, 0, 3 ) ) : '';
		if ( 'ecommerce' === $mode['value'] ) {
			return sprintf( 'People comparing and purchasing %s%s who need clear product information, trust evidence and a convenient checkout path.', $offering, $location );
		}
		if ( 'editorial' === $mode['value'] ) {
			return sprintf( 'Readers researching %s%s who need accurate, original and practically useful information before making a decision.', $offering, $location );
		}
		if ( 'hybrid' === $mode['value'] ) {
			return sprintf( 'Prospective customers and readers researching %s%s who need useful guidance and a clear path to enquire or purchase.', $offering, $location );
		}
		return sprintf( 'Prospective customers looking for %s%s who need to compare the service, confirm suitability and make an enquiry or booking.', $offering, $location );
	}

	private function build_value_proposition( array $profile, array $offerings, array $locations, array $conversions ) {
		$name = sanitize_text_field( $profile['site_name'] ?? get_bloginfo( 'name' ) );
		$offering = $offerings ? implode( ', ', array_slice( $offerings, 0, 3 ) ) : 'its main services';
		$location = $locations ? ' for customers in ' . implode( ', ', array_slice( $locations, 0, 2 ) ) : '';
		$action = $conversions ? strtolower( implode( ', ', array_slice( $conversions, 0, 2 ) ) ) : 'a clear enquiry path';

		return sprintf( '%s presents %s%s with clear service information and access to %s. Confirm the business-specific differentiators before publishing this positioning.', $name, $offering, $location, $action );
	}

	private function build_success_metrics( array $conversions, array $settings ) {
		$metrics = array();
		foreach ( $conversions as $conversion ) {
			$map = array(
				'Phone calls'        => 'Qualified phone enquiries from organic landing pages',
				'WhatsApp enquiries' => 'Qualified WhatsApp enquiries from organic landing pages',
				'Form submissions'   => 'Qualified form submissions from organic landing pages',
				'Booking enquiries'  => 'Completed booking enquiries from organic landing pages',
				'Purchases'          => 'Organic transactions and attributed revenue',
				'Affiliate clicks'   => 'Qualified affiliate clicks and attributed value',
			);
			if ( isset( $map[ $conversion ] ) ) {
				$metrics[] = $map[ $conversion ];
			}
		}
		if ( ! empty( $settings['auto_discovery_include_connected'] ) ) {
			if ( ! empty( $settings['gsc_refresh_token'] ) && ! empty( $settings['gsc_property'] ) ) {
				$metrics[] = 'Search clicks, impressions, CTR and query visibility for priority pages';
			} else {
				$metrics[] = 'Connect Search Console to measure organic clicks, impressions and query visibility';
			}
			if ( ! empty( $settings['ga_refresh_token'] ) && ! empty( $settings['ga_property'] ) ) {
				$metrics[] = 'Organic landing sessions, engagement and tracked key events';
			} else {
				$metrics[] = 'Connect Analytics and configure meaningful conversion events';
			}
		} else {
			$metrics[] = 'Confirm Search Console, Analytics and conversion-event measurement separately';
		}
		return array_values( array_unique( $metrics ) );
	}

	private function build_service_area_policy( array $locations ) {
		if ( ! $locations ) {
			return '';
		}
		return 'Create and optimise location coverage only for areas the business genuinely serves. Use the confirmed service areas: ' . implode( ', ', array_slice( $locations, 0, 12 ) ) . '. Do not create a separate page for every neighbourhood unless the page has a distinct purpose and useful local evidence.';
	}

	private function detect_conflicts( array $contacts, array $language, array $currency, array $settings, array $environment ) {
		$conflicts = array();
		if ( count( $contacts['phone'] ) > 1 ) {
			$conflicts[] = array(
				'area'    => 'Business phone',
				'message' => 'Several phone numbers were detected. Confirm which number is the primary public conversion number.',
				'values'  => array_slice( $contacts['phone'], 0, 10 ),
			);
		}
		if ( count( $contacts['email'] ) > 1 ) {
			$conflicts[] = array(
				'area'    => 'Contact email',
				'message' => 'Several contact email addresses were detected. Confirm the primary public address.',
				'values'  => array_slice( $contacts['email'], 0, 10 ),
			);
		}
		if ( count( $language['candidates'] ) > 1 ) {
			$conflicts[] = array(
				'area'    => 'Language',
				'message' => 'WordPress and the rendered website provide different language signals.',
				'values'  => $language['candidates'],
			);
		}
		if ( count( $currency['candidates'] ) > 1 ) {
			$conflicts[] = array(
				'area'    => 'Currency',
				'message' => 'More than one currency signal was detected.',
				'values'  => $currency['candidates'],
			);
		}
		if ( $environment['rank_math'] && $environment['yoast'] ) {
			$conflicts[] = array(
				'area'    => 'SEO plugins',
				'message' => 'Rank Math and Yoast appear active together. Review duplicate metadata, sitemap and structured-data output.',
				'values'  => array( 'Rank Math', 'Yoast SEO' ),
			);
		}

		return $conflicts;
	}

	private function add_fact(
		array &$facts,
		$id,
		$label,
		$group,
		$field,
		$value,
		$display_value,
		$confidence,
		$score,
		array $sources,
		$current_value,
		$needs_confirmation,
		$identity_sensitive
	) {
		$facts[] = array(
			'id'                 => sanitize_text_field( $id ),
			'label'              => sanitize_text_field( $label ),
			'group'              => sanitize_key( $group ),
			'field'              => sanitize_key( $field ),
			'value'              => $value,
			'display_value'      => sanitize_textarea_field( $display_value ),
			'confidence'         => in_array( $confidence, array( 'high', 'medium', 'low' ), true ) ? $confidence : 'low',
			'score'              => max( 0, min( 100, absint( $score ) ) ),
			'sources'            => array_values( array_map( 'sanitize_text_field', $sources ) ),
			'current_value'      => is_array( $current_value ) ? array_values( array_map( 'sanitize_text_field', $current_value ) ) : sanitize_textarea_field( (string) $current_value ),
			'needs_confirmation' => (bool) $needs_confirmation,
			'identity_sensitive' => (bool) $identity_sensitive,
		);
	}

	private function is_default_value( array $fact, array $settings ) {
		$defaults = Ikon_SEO_Plugin::defaults();
		if ( ! array_key_exists( $fact['field'], $defaults ) ) {
			return false;
		}
		$current = $settings[ $fact['field'] ] ?? '';
		$default = $defaults[ $fact['field'] ];
		return (string) $current === (string) $default;
	}

	private function empty_report() {
		return array(
			'version'      => self::VERSION,
			'status'       => 'not_run',
			'generated_at' => '',
			'source'       => '',
			'summary'      => array(
				'pages_reviewed'       => 0,
				'inventory_items'      => 0,
				'high_confidence'      => 0,
				'medium_confidence'    => 0,
				'needs_confirmation'   => 0,
				'conflicts'            => 0,
				'operating_mode'       => '',
				'operating_mode_label' => '',
				'connected_search'     => false,
				'connected_analytics'  => false,
			),
			'facts'        => array(),
			'conflicts'    => array(),
			'environment'  => array(),
			'application'  => array(
				'applied_at'       => '',
				'applied_fields'   => array(),
				'skipped_fields'   => array(),
				'workflow_created' => false,
				'safe_task_run'    => false,
				'updated_by'       => 0,
			),
			'safety'       => array(
				'changes_pages'      => false,
				'publishes_content'  => false,
				'changes_redirects'  => false,
				'changes_indexation' => false,
				'changes_profiles'   => false,
				'applies_strategy'   => false,
			),
		);
	}
}
