<?php

defined( 'ABSPATH' ) || exit;

/**
 * Owns the site-wide SEO strategy, operating mode and quality policy.
 *
 * The strategy is intentionally separate from the business identity profile.
 * Changing a strategy does not invalidate the website connection key, while
 * every audit and draft can still read the active strategy before acting.
 */
final class Ikon_SEO_Strategy {
	const VERSION = '1.0';

	private $profile;
	private $history;
	private $logger;

	public function __construct( Ikon_SEO_Profile $profile, Ikon_SEO_Workspace_History $history, Ikon_SEO_Logger $logger ) {
		$this->profile = $profile;
		$this->history = $history;
		$this->logger  = $logger;
	}

	public function modes() {
		return array(
			'local_business' => array(
				'label'       => __( 'Local Business', 'ikon-seo' ),
				'description' => __( 'Prioritises services, locations, local trust, leads and Google Business Profile alignment.', 'ikon-seo' ),
			),
			'editorial' => array(
				'label'       => __( 'Editorial / Blog', 'ikon-seo' ),
				'description' => __( 'Prioritises audience needs, topic hubs, authorship, originality, freshness and monetisation quality.', 'ikon-seo' ),
			),
			'ecommerce' => array(
				'label'       => __( 'Ecommerce', 'ikon-seo' ),
				'description' => __( 'Prioritises products, categories, commercial intent, trust policies, feeds and revenue actions.', 'ikon-seo' ),
			),
			'hybrid' => array(
				'label'       => __( 'Hybrid Business + Publisher', 'ikon-seo' ),
				'description' => __( 'Combines commercial or local pages with a structured editorial growth programme.', 'ikon-seo' ),
			),
		);
	}

	public function goals() {
		return array(
			'leads'             => __( 'Qualified leads', 'ikon-seo' ),
			'calls'             => __( 'Phone calls', 'ikon-seo' ),
			'bookings'          => __( 'Bookings or appointments', 'ikon-seo' ),
			'ecommerce_sales'   => __( 'Ecommerce sales', 'ikon-seo' ),
			'affiliate_revenue' => __( 'Affiliate revenue', 'ikon-seo' ),
			'ad_revenue'        => __( 'Advertising revenue', 'ikon-seo' ),
			'subscriptions'     => __( 'Subscriptions or memberships', 'ikon-seo' ),
			'brand_visibility'  => __( 'Brand visibility and authority', 'ikon-seo' ),
			'mixed'             => __( 'Mixed business outcomes', 'ikon-seo' ),
		);
	}

	public function get() {
		$settings = Ikon_SEO_Plugin::settings();
		$modes    = $this->modes();
		$goals    = $this->goals();
		$mode     = sanitize_key( $settings['website_mode'] ?? 'local_business' );
		if ( ! isset( $modes[ $mode ] ) ) {
			$mode = 'local_business';
		}
		$goal = sanitize_key( $settings['strategy_primary_goal'] ?? 'leads' );
		if ( ! isset( $goals[ $goal ] ) ) {
			$goal = 'mixed';
		}

		$strategy = array(
			'version'                => self::VERSION,
			'configured'             => (bool) ( $settings['strategy_configured'] ?? false ),
			'profile_id'             => $this->profile->fingerprint( $settings ),
			'mode'                   => $mode,
			'mode_label'             => $modes[ $mode ]['label'],
			'mode_description'       => $modes[ $mode ]['description'],
			'primary_goal'           => $goal,
			'primary_goal_label'     => $goals[ $goal ],
			'secondary_goals'        => $this->lines( $settings['strategy_secondary_goals'] ?? '' ),
			'target_audience'        => sanitize_textarea_field( $settings['strategy_target_audience'] ?? '' ),
			'value_proposition'      => sanitize_textarea_field( $settings['strategy_value_proposition'] ?? '' ),
			'main_offerings'         => $this->lines( $settings['strategy_main_offerings'] ?? '' ),
			'excluded_topics'        => $this->lines( $settings['strategy_excluded_topics'] ?? '' ),
			'primary_conversions'    => $this->lines( $settings['strategy_primary_conversions'] ?? '' ),
			'monetization_model'     => sanitize_key( $settings['strategy_monetization_model'] ?? 'service_revenue' ),
			'publishing_capacity'    => absint( $settings['strategy_publishing_capacity'] ?? 4 ),
			'content_owner'          => sanitize_text_field( $settings['strategy_content_owner'] ?? '' ),
			'review_owner'           => sanitize_text_field( $settings['strategy_review_owner'] ?? '' ),
			'editorial_standards'    => sanitize_textarea_field( $settings['strategy_editorial_standards'] ?? '' ),
			'evidence_requirements'  => sanitize_textarea_field( $settings['strategy_evidence_requirements'] ?? '' ),
			'author_policy'          => sanitize_textarea_field( $settings['strategy_author_policy'] ?? '' ),
			'disclosure_policy'      => sanitize_textarea_field( $settings['strategy_disclosure_policy'] ?? '' ),
			'success_metrics'        => $this->lines( $settings['strategy_success_metrics'] ?? '' ),
			'automation_level'       => sanitize_key( $settings['strategy_automation_level'] ?? 'drafts_only' ),
			'risk_tolerance'         => sanitize_key( $settings['strategy_risk_tolerance'] ?? 'balanced' ),
			'quality_gate_threshold' => max( 50, min( 100, absint( $settings['strategy_quality_gate_threshold'] ?? 80 ) ) ),
			'last_updated'           => sanitize_text_field( $settings['strategy_last_updated'] ?? '' ),
			'local' => array(
				'lead_channels'        => $this->lines( $settings['strategy_local_lead_channels'] ?? '' ),
				'review_target_monthly'=> absint( $settings['strategy_local_review_target'] ?? 0 ),
				'service_area_policy'  => sanitize_textarea_field( $settings['strategy_local_service_area_policy'] ?? '' ),
				'proof_requirements'   => sanitize_textarea_field( $settings['strategy_local_proof_requirements'] ?? '' ),
			),
			'editorial' => array(
				'primary_topics'       => $this->lines( $settings['strategy_editorial_primary_topics'] ?? '' ),
				'content_hubs'         => $this->lines( $settings['strategy_editorial_hubs'] ?? '' ),
				'refresh_cycle_days'   => max( 30, min( 730, absint( $settings['strategy_editorial_refresh_days'] ?? 180 ) ) ),
				'originality_standard' => sanitize_textarea_field( $settings['strategy_editorial_originality'] ?? '' ),
			),
			'ecommerce' => array(
				'primary_categories'  => $this->lines( $settings['strategy_ecommerce_categories'] ?? '' ),
				'conversion_events'   => $this->lines( $settings['strategy_ecommerce_conversion_events'] ?? '' ),
				'trust_requirements'  => sanitize_textarea_field( $settings['strategy_ecommerce_trust_requirements'] ?? '' ),
				'feed_policy'         => sanitize_textarea_field( $settings['strategy_ecommerce_feed_policy'] ?? '' ),
			),
		);

		$readiness = $this->readiness( $strategy, $settings );
		$strategy['readiness']           = $readiness;
		$strategy['mode_priorities']      = $this->mode_priorities( $mode );
		$strategy['quality_gate']         = $this->quality_gate( $strategy );
		$strategy['automation_policy']    = $this->automation_policy( $strategy );
		$strategy['recommended_workflow'] = $this->recommended_workflow( $strategy, $readiness );
		$strategy['connected_evidence']   = $this->connected_evidence( $settings );

		return $strategy;
	}

	public function save( array $input, $user_id = 0, $source = 'admin' ) {
		$current = Ikon_SEO_Plugin::settings();
		$clean   = $this->sanitize( $input, $current );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}

		$clean['strategy_configured'] = 1;
		$clean['strategy_last_updated'] = current_time( 'mysql', true );
		$clean['component_version'] = '18.0';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $clean, false );

		$strategy = $this->get();
		$this->history->add(
			array(
				'category' => 'strategy',
				'status'   => 'completed',
				'title'    => 'Website strategy updated',
				'summary'  => sprintf( 'The website was configured for %s mode with %s as the primary goal.', $strategy['mode_label'], $strategy['primary_goal_label'] ),
				'details'  => array(
					'mode'       => $strategy['mode'],
					'goal'       => $strategy['primary_goal'],
					'readiness'  => absint( $strategy['readiness']['score'] ?? 0 ),
					'source'     => sanitize_key( $source ),
					'updated_by' => absint( $user_id ),
				),
			),
			'system'
		);
		$this->logger->log( 'strategy', 'success', 'Website strategy updated.' );
		return $strategy;
	}

	public function sync( array $payload, $user_id = 0 ) {
		$save = ! empty( $payload['save'] );
		if ( ! $save ) {
			return $this->get();
		}
		$strategy = isset( $payload['strategy'] ) && is_array( $payload['strategy'] ) ? $payload['strategy'] : $payload;
		unset( $strategy['save'] );
		return $this->save( $strategy, $user_id, 'workspace' );
	}

	public function page_value( array $item ) {
		$strategy = $this->get();
		$mode     = $strategy['mode'];
		$type     = sanitize_key( $item['post_type'] ?? '' );
		$title    = strtolower( sanitize_text_field( $item['title'] ?? '' ) . ' ' . sanitize_title( $item['slug'] ?? '' ) );
		$score    = 3;
		$reasons  = array();

		if ( absint( get_option( 'page_on_front' ) ) === absint( $item['id'] ?? 0 ) ) {
			return array( 'score' => 5, 'reasons' => array( 'Website homepage' ) );
		}

		if ( 'local_business' === $mode ) {
			$score = 'page' === $type ? 4 : 3;
			$reasons[] = 'Local business mode prioritises commercial landing pages';
		} elseif ( 'editorial' === $mode ) {
			$score = 'post' === $type ? 4 : 3;
			$reasons[] = 'Editorial mode prioritises useful published articles and topic hubs';
		} elseif ( 'ecommerce' === $mode ) {
			$score = in_array( $type, array( 'product', 'product_cat' ), true ) ? 5 : 3;
			$reasons[] = 'Ecommerce mode prioritises revenue-generating products and categories';
		} else {
			$score = in_array( $type, array( 'page', 'post' ), true ) ? 4 : 3;
			$reasons[] = 'Hybrid mode balances commercial and editorial content';
		}

		foreach ( (array) $strategy['main_offerings'] as $offering ) {
			$tokens = $this->tokens( $offering );
			if ( $tokens && count( array_intersect( $tokens, $this->tokens( $title ) ) ) >= min( 2, count( $tokens ) ) ) {
				$score++;
				$reasons[] = 'Aligned with a declared priority offering or topic';
				break;
			}
		}
		foreach ( (array) $strategy['excluded_topics'] as $excluded ) {
			$tokens = $this->tokens( $excluded );
			if ( $tokens && count( array_intersect( $tokens, $this->tokens( $title ) ) ) >= min( 2, count( $tokens ) ) ) {
				$score--;
				$reasons[] = 'Falls within an excluded or non-priority topic';
				break;
			}
		}

		return array( 'score' => max( 1, min( 5, $score ) ), 'reasons' => array_values( array_unique( $reasons ) ) );
	}

	private function sanitize( array $input, array $current ) {
		$current = wp_parse_args( $current, Ikon_SEO_Plugin::defaults() );
		$output  = $current;
		$modes   = $this->modes();
		$goals   = $this->goals();

		$mode = sanitize_key( wp_unslash( $input['website_mode'] ?? $input['mode'] ?? $current['website_mode'] ) );
		if ( ! isset( $modes[ $mode ] ) ) {
			return new WP_Error( 'ikon_seo_strategy_mode', __( 'Select a supported website operating mode.', 'ikon-seo' ), array( 'status' => 400 ) );
		}
		$goal = sanitize_key( wp_unslash( $input['strategy_primary_goal'] ?? $input['primary_goal'] ?? $current['strategy_primary_goal'] ) );
		if ( ! isset( $goals[ $goal ] ) ) {
			return new WP_Error( 'ikon_seo_strategy_goal', __( 'Select a supported primary website goal.', 'ikon-seo' ), array( 'status' => 400 ) );
		}

		$output['website_mode']         = $mode;
		$output['strategy_primary_goal'] = $goal;

		$text_fields = array(
			'strategy_content_owner', 'strategy_review_owner',
		);
		$aliases = array(
			'strategy_content_owner' => 'content_owner',
			'strategy_review_owner'  => 'review_owner',
		);
		foreach ( $text_fields as $field ) {
			$key = array_key_exists( $field, $input ) ? $field : ( $aliases[ $field ] ?? $field );
			if ( array_key_exists( $key, $input ) ) {
				$output[ $field ] = sanitize_text_field( wp_unslash( $input[ $key ] ) );
			}
		}

		$textarea_fields = array(
			'strategy_secondary_goals', 'strategy_target_audience', 'strategy_value_proposition',
			'strategy_main_offerings', 'strategy_excluded_topics', 'strategy_primary_conversions',
			'strategy_editorial_standards', 'strategy_evidence_requirements', 'strategy_author_policy',
			'strategy_disclosure_policy', 'strategy_success_metrics', 'strategy_local_lead_channels',
			'strategy_local_service_area_policy', 'strategy_local_proof_requirements',
			'strategy_editorial_primary_topics', 'strategy_editorial_hubs', 'strategy_editorial_originality',
			'strategy_ecommerce_categories', 'strategy_ecommerce_conversion_events',
			'strategy_ecommerce_trust_requirements', 'strategy_ecommerce_feed_policy',
		);
		$aliases = array(
			'strategy_secondary_goals' => 'secondary_goals',
			'strategy_target_audience' => 'target_audience',
			'strategy_value_proposition' => 'value_proposition',
			'strategy_main_offerings' => 'main_offerings',
			'strategy_excluded_topics' => 'excluded_topics',
			'strategy_primary_conversions' => 'primary_conversions',
			'strategy_editorial_standards' => 'editorial_standards',
			'strategy_evidence_requirements' => 'evidence_requirements',
			'strategy_author_policy' => 'author_policy',
			'strategy_disclosure_policy' => 'disclosure_policy',
			'strategy_success_metrics' => 'success_metrics',
			'strategy_local_lead_channels' => 'local_lead_channels',
			'strategy_local_service_area_policy' => 'local_service_area_policy',
			'strategy_local_proof_requirements' => 'local_proof_requirements',
			'strategy_editorial_primary_topics' => 'editorial_primary_topics',
			'strategy_editorial_hubs' => 'editorial_hubs',
			'strategy_editorial_originality' => 'editorial_originality',
			'strategy_ecommerce_categories' => 'ecommerce_categories',
			'strategy_ecommerce_conversion_events' => 'ecommerce_conversion_events',
			'strategy_ecommerce_trust_requirements' => 'ecommerce_trust_requirements',
			'strategy_ecommerce_feed_policy' => 'ecommerce_feed_policy',
		);
		foreach ( $textarea_fields as $field ) {
			$key = array_key_exists( $field, $input ) ? $field : ( $aliases[ $field ] ?? $field );
			if ( array_key_exists( $key, $input ) ) {
				$value = is_array( $input[ $key ] ) ? implode( "\n", array_map( 'sanitize_text_field', $input[ $key ] ) ) : wp_unslash( $input[ $key ] );
				$output[ $field ] = sanitize_textarea_field( $value );
			}
		}

		$monetization = sanitize_key( wp_unslash( $input['strategy_monetization_model'] ?? $input['monetization_model'] ?? $current['strategy_monetization_model'] ) );
		$allowed_monetization = array( 'service_revenue', 'lead_generation', 'ecommerce', 'affiliate', 'advertising', 'subscription', 'sponsorship', 'mixed', 'none' );
		$output['strategy_monetization_model'] = in_array( $monetization, $allowed_monetization, true ) ? $monetization : 'mixed';

		$automation = sanitize_key( wp_unslash( $input['strategy_automation_level'] ?? $input['automation_level'] ?? $current['strategy_automation_level'] ) );
		$output['strategy_automation_level'] = in_array( $automation, array( 'audit_only', 'drafts_only', 'controlled_changes' ), true ) ? $automation : 'drafts_only';
		$risk = sanitize_key( wp_unslash( $input['strategy_risk_tolerance'] ?? $input['risk_tolerance'] ?? $current['strategy_risk_tolerance'] ) );
		$output['strategy_risk_tolerance'] = in_array( $risk, array( 'conservative', 'balanced', 'growth' ), true ) ? $risk : 'balanced';

		$output['strategy_publishing_capacity'] = max( 0, min( 200, absint( $input['strategy_publishing_capacity'] ?? $input['publishing_capacity'] ?? $current['strategy_publishing_capacity'] ) ) );
		$output['strategy_quality_gate_threshold'] = max( 50, min( 100, absint( $input['strategy_quality_gate_threshold'] ?? $input['quality_gate_threshold'] ?? $current['strategy_quality_gate_threshold'] ) ) );
		$output['strategy_local_review_target'] = max( 0, min( 500, absint( $input['strategy_local_review_target'] ?? $input['local_review_target'] ?? $current['strategy_local_review_target'] ) ) );
		$output['strategy_editorial_refresh_days'] = max( 30, min( 730, absint( $input['strategy_editorial_refresh_days'] ?? $input['editorial_refresh_days'] ?? $current['strategy_editorial_refresh_days'] ) ) );

		return $output;
	}

	private function readiness( array $strategy, array $settings ) {
		$gaps = array();
		$add = function( $code, $impact, $area, $message, $action ) use ( &$gaps ) {
			$gaps[] = array(
				'code'    => sanitize_key( $code ),
				'impact'  => sanitize_key( $impact ),
				'area'    => sanitize_key( $area ),
				'message' => sanitize_text_field( $message ),
				'action'  => sanitize_text_field( $action ),
			);
		};

		if ( empty( $settings['profile_configured'] ) ) {
			$add( 'website_profile', 'critical', 'identity', 'The Website Profile is incomplete.', 'Complete the Website Profile before creating or changing content.' );
		}
		if ( empty( $strategy['target_audience'] ) ) {
			$add( 'target_audience', 'high', 'positioning', 'The target audience is not defined.', 'Describe who the website serves, their needs and their decision context.' );
		}
		if ( empty( $strategy['value_proposition'] ) ) {
			$add( 'value_proposition', 'high', 'positioning', 'The website differentiation is not defined.', 'Document why users should choose this website instead of available alternatives.' );
		}
		if ( empty( $strategy['main_offerings'] ) ) {
			$add( 'priority_offerings', 'high', 'architecture', 'Priority services, products or topics are not listed.', 'List the offers or subjects the website should become known for.' );
		}
		if ( empty( $strategy['primary_conversions'] ) ) {
			$add( 'primary_conversions', 'high', 'measurement', 'Primary conversions are not defined.', 'List measurable outcomes such as qualified forms, calls, bookings, sales or affiliate clicks.' );
		}
		if ( empty( $strategy['success_metrics'] ) ) {
			$add( 'success_metrics', 'high', 'measurement', 'The strategy has no agreed success metrics.', 'Define business and search metrics used to judge progress.' );
		}
		if ( empty( $strategy['editorial_standards'] ) ) {
			$add( 'editorial_standards', 'medium', 'governance', 'Editorial quality standards are not documented.', 'Record minimum quality, tone, accuracy and review requirements.' );
		}
		if ( empty( $strategy['evidence_requirements'] ) ) {
			$add( 'evidence_requirements', 'medium', 'governance', 'Evidence requirements are not documented.', 'Define which claims require sources, proof, real examples or client confirmation.' );
		}
		if ( empty( $strategy['content_owner'] ) || empty( $strategy['review_owner'] ) ) {
			$add( 'workflow_ownership', 'medium', 'operations', 'Content and review ownership is incomplete.', 'Assign who prepares content and who approves it before publication.' );
		}
		if ( 0 === absint( $strategy['publishing_capacity'] ) ) {
			$add( 'publishing_capacity', 'medium', 'operations', 'Monthly publishing capacity is set to zero.', 'Set a realistic monthly capacity so plans do not exceed available resources.' );
		}

		$mode = $strategy['mode'];
		if ( in_array( $mode, array( 'local_business', 'hybrid' ), true ) ) {
			if ( empty( $settings['target_locations'] ) ) {
				$add( 'local_coverage', 'high', 'local', 'No genuine service locations or service areas are defined.', 'Add only locations the business genuinely serves.' );
			}
			if ( empty( $settings['business_phone'] ) && empty( $settings['contact_email'] ) ) {
				$add( 'local_contact', 'high', 'local', 'The local business has no verified phone or contact email in its profile.', 'Add accurate contact details to the Website Profile.' );
			}
			if ( empty( $strategy['local']['lead_channels'] ) ) {
				$add( 'local_lead_channels', 'medium', 'local', 'Local lead channels are not defined.', 'Select the real call, form, messaging or booking actions to measure.' );
			}
			if ( empty( $strategy['local']['proof_requirements'] ) ) {
				$add( 'local_proof', 'medium', 'local', 'Local proof requirements are not defined.', 'Define acceptable evidence such as genuine project photos, credentials, service process and verified reviews.' );
			}
			if ( 0 === $this->local_location_count() ) {
				$add( 'local_location_record', 'medium', 'local', 'No structured location record exists in the Local SEO workspace.', 'Create the primary storefront, hybrid or service-area record.' );
			}
		}

		if ( in_array( $mode, array( 'editorial', 'hybrid' ), true ) ) {
			if ( empty( $strategy['editorial']['primary_topics'] ) ) {
				$add( 'editorial_topics', 'high', 'editorial', 'Primary editorial topics are not defined.', 'Define a narrow set of subjects matched to the audience and business model.' );
			}
			if ( empty( $strategy['editorial']['content_hubs'] ) ) {
				$add( 'content_hubs', 'medium', 'editorial', 'No planned content hubs are listed.', 'Group future articles into a small number of coherent topic hubs.' );
			}
			if ( empty( $strategy['author_policy'] ) ) {
				$add( 'author_policy', 'medium', 'editorial', 'Authorship and review policy is not defined.', 'Document author expertise, reviewer requirements and byline standards.' );
			}
			if ( in_array( $strategy['monetization_model'], array( 'affiliate', 'advertising', 'sponsorship', 'mixed' ), true ) && empty( $strategy['disclosure_policy'] ) ) {
				$add( 'disclosure_policy', 'high', 'editorial', 'The monetisation model requires a disclosure policy.', 'Document affiliate, advertising and sponsorship disclosures before publishing.' );
			}
			if ( empty( $strategy['editorial']['originality_standard'] ) ) {
				$add( 'originality_standard', 'medium', 'editorial', 'The minimum originality standard is not defined.', 'Require first-hand insight, useful synthesis, original examples, data or media appropriate to the topic.' );
			}
		}

		if ( 'ecommerce' === $mode ) {
			if ( ! class_exists( 'WooCommerce' ) ) {
				$add( 'commerce_platform', 'medium', 'ecommerce', 'WooCommerce was not detected.', 'Confirm the commerce platform and connect a supported product data source.' );
			}
			if ( empty( $strategy['ecommerce']['primary_categories'] ) ) {
				$add( 'product_categories', 'high', 'ecommerce', 'Primary product categories are not defined.', 'List the categories that carry strategic revenue importance.' );
			}
			if ( empty( $strategy['ecommerce']['conversion_events'] ) ) {
				$add( 'commerce_events', 'high', 'ecommerce', 'Commerce conversion events are not defined.', 'Define purchases, add-to-cart, checkout and other meaningful events.' );
			}
			if ( empty( $strategy['ecommerce']['trust_requirements'] ) ) {
				$add( 'commerce_trust', 'high', 'ecommerce', 'Ecommerce trust requirements are not documented.', 'Define shipping, returns, payment, stock, warranty and product-evidence standards.' );
			}
		}

		if ( empty( $settings['gsc_refresh_token'] ) || empty( $settings['gsc_property'] ) ) {
			$add( 'search_console', 'medium', 'data', 'Search Console is not fully connected.', 'Connect read-only Search Console evidence before making performance diagnoses.' );
		}
		if ( empty( $settings['ga_refresh_token'] ) || empty( $settings['ga_property'] ) ) {
			$add( 'analytics', 'medium', 'data', 'Google Analytics is not fully connected.', 'Connect read-only Analytics and confirm business conversion events.' );
		}

		$weights = array( 'critical' => 20, 'high' => 12, 'medium' => 7, 'low' => 3 );
		$deduction = 0;
		foreach ( $gaps as $gap ) {
			$deduction += $weights[ $gap['impact'] ] ?? 5;
		}
		$score = max( 0, min( 100, 100 - $deduction ) );
		$level = $score >= 80 ? 'ready' : ( $score >= 60 ? 'developing' : 'incomplete' );
		$counts = array( 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0 );
		foreach ( $gaps as $gap ) {
			if ( isset( $counts[ $gap['impact'] ] ) ) {
				$counts[ $gap['impact'] ]++;
			}
		}

		return array(
			'score'      => $score,
			'level'      => $level,
			'gap_counts' => $counts,
			'gaps'       => $gaps,
			'methodology'=> 'Readiness measures whether the strategy contains enough identity, positioning, conversion, governance, mode-specific and evidence inputs to guide work. It is not a ranking score.',
		);
	}

	private function mode_priorities( $mode ) {
		$map = array(
			'local_business' => array( 'service and location relevance', 'verified business facts', 'local trust and prominence', 'calls, forms and bookings', 'review and citation consistency', 'local landing-page quality' ),
			'editorial'      => array( 'audience usefulness', 'topic hubs and internal links', 'original evidence and authorship', 'content freshness', 'search intent and differentiation', 'monetisation quality and disclosures' ),
			'ecommerce'      => array( 'product and category discovery', 'commercial search intent', 'inventory and structured product data', 'trust and policy clarity', 'revenue conversion events', 'feed and canonical consistency' ),
			'hybrid'         => array( 'commercial conversion pages', 'supporting editorial hubs', 'clear audience and business positioning', 'internal links between information and offers', 'original proof and expertise', 'measured leads or revenue' ),
		);
		return $map[ $mode ] ?? $map['local_business'];
	}

	private function quality_gate( array $strategy ) {
		$requirements = array(
			'Correct search intent and a clearly defined user need',
			'Materially useful and distinct from existing website content',
			'No invented business facts, reviews, credentials, prices or performance claims',
			'Claims supported according to the active evidence policy',
			'Human review before publication or destructive changes',
			'Appropriate conversion path and measurement plan',
		);
		if ( in_array( $strategy['mode'], array( 'editorial', 'hybrid' ), true ) ) {
			$requirements[] = 'Authorship, sourcing, originality and disclosure requirements satisfied';
		}
		if ( in_array( $strategy['mode'], array( 'local_business', 'hybrid' ), true ) ) {
			$requirements[] = 'Locations, services, photos, reviews and trust claims verified against real business evidence';
		}
		if ( 'ecommerce' === $strategy['mode'] ) {
			$requirements[] = 'Product availability, pricing, shipping, returns and structured data match the visible page';
		}
		return array(
			'threshold'    => absint( $strategy['quality_gate_threshold'] ),
			'requirements' => $requirements,
			'failure_rule' => 'A failed quality gate returns the item for revision and does not publish it.',
		);
	}

	private function automation_policy( array $strategy ) {
		$level = $strategy['automation_level'];
		$allowed = array( 'crawling', 'evidence collection', 'diagnosis', 'research summaries', 'content briefs', 'internal-link suggestions', 'metadata alternatives', 'draft creation', 'reports and alerts' );
		if ( 'audit_only' === $level ) {
			$allowed = array( 'crawling', 'evidence collection', 'diagnosis', 'research summaries', 'reports and alerts' );
		}
		return array(
			'level' => $level,
			'automatic_or_draft_actions' => $allowed,
			'always_requires_approval' => array( 'publishing', 'editing live content', 'redirects', 'canonical changes', 'noindex changes', 'deletion', 'schema claims', 'Business Profile changes', 'review replies', 'outreach and external link actions' ),
		);
	}

	private function recommended_workflow( array $strategy, array $readiness ) {
		$steps = array();
		foreach ( array_slice( (array) $readiness['gaps'], 0, 5 ) as $gap ) {
			$steps[] = array( 'type' => 'setup', 'title' => $gap['message'], 'action' => $gap['action'] );
		}
		if ( 'local_business' === $strategy['mode'] ) {
			$mode_steps = array( 'Map genuine services and service areas', 'Audit local landing pages and business facts', 'Compare local competitors and prominence evidence', 'Create approval-only service and location drafts', 'Measure qualified calls, forms and bookings' );
		} elseif ( 'editorial' === $strategy['mode'] ) {
			$mode_steps = array( 'Build a topic and audience opportunity universe', 'Choose a limited set of content hubs', 'Create evidence-based briefs and editorial assignments', 'Apply originality, sourcing and disclosure gates', 'Measure search growth, engagement and monetisation outcomes' );
		} elseif ( 'ecommerce' === $strategy['mode'] ) {
			$mode_steps = array( 'Map products and commercial categories', 'Audit product discovery, canonicals and structured data', 'Connect revenue and checkout events', 'Prioritise high-value category and product improvements', 'Measure revenue, conversion rate and organic visibility' );
		} else {
			$mode_steps = array( 'Map commercial offers and supporting topic hubs', 'Connect every article to a defined business purpose', 'Create an approval-only content and landing-page pipeline', 'Apply common evidence and originality gates', 'Measure both conversions and editorial growth' );
		}
		foreach ( $mode_steps as $step ) {
			$steps[] = array( 'type' => 'workflow', 'title' => $step, 'action' => $step );
		}
		return array_slice( $steps, 0, 10 );
	}

	private function connected_evidence( array $settings ) {
		return array(
			'search_console' => ! empty( $settings['gsc_refresh_token'] ) && ! empty( $settings['gsc_property'] ),
			'analytics'      => ! empty( $settings['ga_refresh_token'] ) && ! empty( $settings['ga_property'] ),
			'local_module'   => ! empty( $settings['local_module_enabled'] ),
			'crawler'        => ! empty( $settings['crawler_enabled'] ),
			'search_intelligence' => ! empty( $settings['search_intelligence_enabled'] ),
			'technical_intelligence' => ! empty( $settings['technical_intelligence_enabled'] ),
			'competitor_content' => ! empty( $settings['competitor_content_enabled'] ),
			'authority_intelligence' => ! empty( $settings['authority_intelligence_enabled'] ),
		);
	}

	private function local_location_count() {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_locations';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return 0;
		}
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE profile_id = %s AND status = 'active'", $this->profile->fingerprint() ) ) );
	}

	private function lines( $value ) {
		if ( is_array( $value ) ) {
			$raw = $value;
		} else {
			$raw = preg_split( '/[\r\n]+/', (string) $value );
		}
		$lines = array();
		foreach ( (array) $raw as $line ) {
			$line = sanitize_text_field( trim( (string) $line ) );
			if ( '' !== $line ) {
				$lines[] = $line;
			}
		}
		return array_values( array_unique( $lines ) );
	}

	private function tokens( $value ) {
		$value = strtolower( wp_strip_all_tags( (string) $value ) );
		$value = preg_replace( '/[^a-z0-9]+/', ' ', $value );
		$stop  = array_fill_keys( array( 'the', 'and', 'for', 'with', 'from', 'that', 'this', 'your', 'our', 'you', 'are', 'service', 'services', 'page', 'blog', 'website' ), true );
		$tokens = array();
		foreach ( preg_split( '/\s+/', trim( $value ) ) as $token ) {
			if ( strlen( $token ) < 3 || isset( $stop[ $token ] ) ) {
				continue;
			}
			$tokens[] = $token;
		}
		return array_values( array_unique( $tokens ) );
	}
}
