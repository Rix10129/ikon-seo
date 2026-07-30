<?php

defined( 'ABSPATH' ) || exit;

/**
 * Owns the website identity and the schema policy for the current installation.
 *
 * A profile is intentionally site-local. Connection keys are never exported,
 * and remote write requests can be bound to the profile fingerprint so content
 * prepared for one client cannot be sent to another client by mistake.
 */
class Ikon_SEO_Profile {
	const VERSION = '5.0';
	// Keep the v0.4 identity seed so feature-only upgrades do not invalidate
	// an otherwise unchanged website profile or its bound drafts.
	const IDENTITY_VERSION = '4.0';

	public function get() {
		$settings  = Ikon_SEO_Plugin::settings();
		$industry  = sanitize_key( $settings['industry'] );
		$entity    = sanitize_text_field( $settings['business_entity_type'] );
		$industries= $this->industries();
		$entities  = $this->entity_types();

		return array(
			'profile_id'            => $this->fingerprint( $settings ),
			'profile_version'       => self::VERSION,
			'configured'            => (bool) $settings['profile_configured'],
			'site_name'             => sanitize_text_field( $settings['site_name'] ),
			'site_url'              => home_url( '/' ),
			'business_url'          => esc_url_raw( $settings['business_url'] ?: home_url( '/' ) ),
			'industry'              => $industry,
			'industry_label'        => $industries[ $industry ]['label'] ?? 'Other',
			'business_entity_type'  => $entity,
			'business_entity_label' => $entities[ $entity ]['label'] ?? 'Organization',
			'target_market'         => sanitize_text_field( $settings['target_market'] ),
			'target_locations'      => $this->lines( $settings['target_locations'] ),
			'default_language'      => sanitize_text_field( $settings['default_language'] ),
			'supported_languages'   => $this->languages( $settings['supported_languages'], $settings['default_language'] ),
			'default_currency'      => sanitize_text_field( $settings['default_currency'] ),
			'contact'               => array(
				'phone'        => sanitize_text_field( $settings['business_phone'] ),
				'email'        => sanitize_email( $settings['contact_email'] ),
				'whatsapp_url' => esc_url_raw( $settings['whatsapp_url'] ),
			),
			'builder'               => $this->builder(),
			'seo_plugin'            => $this->seo_plugin(),
			'verified_business'     => (bool) $settings['verified_business'],
			'has_verified_address'  => $this->has_verified_address( $settings ),
			'allow_entity_schema'   => (bool) $settings['allow_entity_schema'],
			'allowed_entity_types'  => $this->allowed_entity_types( $industry ),
			'allowed_schema_types'  => $this->allowed_schema_types( $settings ),
			'require_profile_match' => (bool) $settings['require_profile_match'],
			'draft_only'            => (bool) $settings['draft_only'],
			'remote_merge'          => (bool) $settings['remote_merge'],
			'component_version'     => sanitize_text_field( $settings['component_version'] ),
		);
	}

	public function export() {
		$settings = Ikon_SEO_Plugin::settings();
		$keys     = array(
			'site_name',
			'industry',
			'business_entity_type',
			'target_market',
			'target_locations',
			'default_language',
			'supported_languages',
			'default_currency',
			'business_phone',
			'contact_email',
			'whatsapp_url',
			'business_url',
			'business_logo',
			'primary_color',
			'secondary_color',
			'accent_color',
			'heading_color',
			'text_color',
			'surface_color',
			'content_width',
			'builder_preference',
			'seo_plugin_preference',
			'verified_business',
			'allow_entity_schema',
			'address_street',
			'address_locality',
			'address_region',
			'address_postal',
			'address_country',
			'latitude',
			'longitude',
			'opening_hours',
			'price_range',
			'semantic_faq',
			'content_rules',
			'cta_templates',
		);
		$data = array();
		foreach ( $keys as $key ) {
			$data[ $key ] = $settings[ $key ] ?? '';
		}

		return array(
			'format'          => 'ikon-seo-profile',
			'format_version'  => 1,
			'profile_version' => self::VERSION,
			'exported_at'     => current_time( 'mysql', true ),
			'profile'         => $data,
		);
	}

	public function import( array $document ) {
		if ( 'ikon-seo-profile' !== ( $document['format'] ?? '' ) || empty( $document['profile'] ) || ! is_array( $document['profile'] ) ) {
			return new WP_Error( 'ikon_seo_profile_format', 'This is not a valid Ikon SEO website profile.', array( 'status' => 400 ) );
		}

		$current = Ikon_SEO_Plugin::settings();
		$input   = $document['profile'];
		$clean   = $this->sanitize( $input, $current );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}

		$clean['profile_configured'] = 1;
		$clean['profile_version']    = self::VERSION;
		$clean['profile_home_url']   = home_url( '/' );
		$clean['token_hash']         = '';
		$clean['token_hint']         = '';
		$clean['connection_verified_at'] = '';
		$clean['connection_last_seen_at']= '';
		$clean['remote_actions']     = 0;
		$clean['gbp_refresh_token']  = '';
		$clean['gbp_account']        = '';
		$clean['gbp_last_error']     = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $clean, false );

		return $this->get();
	}

	public function sanitize( array $input, array $current = array() ) {
		$current    = wp_parse_args( $current, Ikon_SEO_Plugin::defaults() );
		$industry   = sanitize_key( wp_unslash( $input['industry'] ?? $current['industry'] ) );
		$industries = $this->industries();
		if ( ! isset( $industries[ $industry ] ) ) {
			return new WP_Error( 'ikon_seo_industry', 'Select a supported website industry.', array( 'status' => 400 ) );
		}

		$entity  = sanitize_text_field( wp_unslash( $input['business_entity_type'] ?? $industries[ $industry ]['recommended'] ) );
		$allowed = $this->allowed_entity_types( $industry );
		if ( ! in_array( $entity, $allowed, true ) ) {
			return new WP_Error(
				'ikon_seo_entity_policy',
				'The selected business entity is not allowed for this industry profile.',
				array( 'status' => 400, 'allowed_entity_types' => $allowed )
			);
		}

		$output = $current;
		$text_fields = array(
			'site_name',
			'target_market',
			'default_language',
			'default_currency',
			'business_phone',
			'builder_preference',
			'seo_plugin_preference',
			'address_street',
			'address_locality',
			'address_region',
			'address_postal',
			'address_country',
			'latitude',
			'longitude',
			'price_range',
		);
		foreach ( $text_fields as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$output[ $field ] = sanitize_text_field( wp_unslash( $input[ $field ] ) );
			}
		}

		$output['industry']             = $industry;
		$output['business_entity_type'] = $entity;
		$output['business_url']         = esc_url_raw( wp_unslash( $input['business_url'] ?? $current['business_url'] ) );
		$output['business_logo']        = esc_url_raw( wp_unslash( $input['business_logo'] ?? $current['business_logo'] ) );
		$output['whatsapp_url']         = esc_url_raw( wp_unslash( $input['whatsapp_url'] ?? $current['whatsapp_url'] ) );
		$output['contact_email']        = sanitize_email( wp_unslash( $input['contact_email'] ?? $current['contact_email'] ) );
		$output['target_locations']     = sanitize_textarea_field( wp_unslash( $input['target_locations'] ?? $current['target_locations'] ) );
		$output['supported_languages']  = sanitize_textarea_field( wp_unslash( $input['supported_languages'] ?? $current['supported_languages'] ) );
		$output['opening_hours']        = sanitize_textarea_field( wp_unslash( $input['opening_hours'] ?? $current['opening_hours'] ) );
		$output['content_rules']        = sanitize_textarea_field( wp_unslash( $input['content_rules'] ?? $current['content_rules'] ) );
		$output['cta_templates']        = sanitize_textarea_field( wp_unslash( $input['cta_templates'] ?? $current['cta_templates'] ) );
		$output['verified_business']    = ! empty( $input['verified_business'] ) ? 1 : 0;
		$output['allow_entity_schema']  = ! empty( $input['allow_entity_schema'] ) ? 1 : 0;
		$output['semantic_faq']         = ! empty( $input['semantic_faq'] ) ? 1 : 0;
		$output['content_width']        = max( 800, min( 1600, absint( $input['content_width'] ?? $current['content_width'] ) ) );
		$output['profile_version']      = self::VERSION;

		foreach ( array( 'primary_color', 'secondary_color', 'accent_color', 'heading_color', 'text_color', 'surface_color' ) as $color ) {
			if ( array_key_exists( $color, $input ) ) {
				$value = sanitize_hex_color( wp_unslash( $input[ $color ] ) );
				if ( $value ) {
					$output[ $color ] = $value;
				}
			}
		}

		$language = $output['default_language'];
		if ( ! preg_match( '/^[a-z]{2,3}(?:-[A-Z]{2})?$/', $language ) ) {
			return new WP_Error( 'ikon_seo_language', 'Default language must use a value such as en, en-AE or ur-PK.', array( 'status' => 400 ) );
		}
		$output['supported_languages'] = implode( "\n", $this->languages( $output['supported_languages'], $language ) );

		$currency = strtoupper( $output['default_currency'] );
		if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) {
			return new WP_Error( 'ikon_seo_currency', 'Default currency must be a three-letter ISO code.', array( 'status' => 400 ) );
		}
		$output['default_currency'] = $currency;

		if ( ! $output['site_name'] || ! wp_http_validate_url( $output['business_url'] ) ) {
			return new WP_Error( 'ikon_seo_profile_required', 'Business name and a valid business URL are required.', array( 'status' => 400 ) );
		}

		if ( ! in_array( $output['builder_preference'], array( 'auto', 'elementor', 'gutenberg' ), true ) ) {
			$output['builder_preference'] = 'auto';
		}
		if ( ! in_array( $output['seo_plugin_preference'], array( 'auto', 'rank_math', 'yoast', 'none' ), true ) ) {
			$output['seo_plugin_preference'] = 'auto';
		}

		return $output;
	}

	public function validate_payload( array $payload ) {
		$profile = $this->get();
		$errors  = array();

		if ( ! $profile['configured'] ) {
			$errors[] = 'complete the Ikon SEO Website Profile before creating pages';
		}
		if ( $profile['require_profile_match'] ) {
			$submitted = sanitize_text_field( $payload['profile_id'] ?? '' );
			if ( ! $submitted ) {
				$errors[] = 'profile_id is required; read the current Website Profile before writing';
			} elseif ( ! hash_equals( $profile['profile_id'], $submitted ) ) {
				$errors[] = 'profile_id does not match this website; refresh the Website Profile';
			}
		}

		$language = sanitize_text_field( $payload['language'] ?? $profile['default_language'] );
		if ( ! in_array( $language, $profile['supported_languages'], true ) ) {
			$errors[] = 'language is not enabled in the active Website Profile';
		}

		$config = is_array( $payload['schema'] ?? null ) ? $payload['schema'] : array();
		if ( array_key_exists( 'business_entity', $config ) && ! is_array( $config['business_entity'] ) ) {
			$errors[] = 'schema.business_entity must be an object';
		}
		$entity_requested = ! empty( $config['business_entity'] ) || ! empty( $config['accounting_service'] );
		if ( $entity_requested && ! $profile['allow_entity_schema'] ) {
			$errors[] = 'business entity schema is disabled in the active Website Profile';
		}
		if ( ! empty( $config['business_entity']['type'] ) && $config['business_entity']['type'] !== $profile['business_entity_type'] ) {
			$errors[] = 'schema.business_entity.type must match the active Website Profile';
		}
		if ( ! empty( $config['accounting_service'] ) && 'AccountingService' !== $profile['business_entity_type'] ) {
			$errors[] = 'legacy accounting_service schema is only allowed for an AccountingService profile';
		}
		if ( $entity_requested && ! in_array( $profile['business_entity_type'], $profile['allowed_entity_types'], true ) ) {
			$errors[] = 'the active business entity is not allowed by the industry schema policy';
		}

		$entity = $this->entity_types()[ $profile['business_entity_type'] ] ?? array();
		if ( $entity_requested && ! empty( $entity['requires_address'] ) && ! $profile['has_verified_address'] ) {
			$errors[] = 'this LocalBusiness subtype requires a verified physical address in the Website Profile';
		}

		return $errors;
	}

	public function fingerprint( array $settings = array() ) {
		$settings = wp_parse_args( $settings, Ikon_SEO_Plugin::settings() );
		$identity = array(
			home_url( '/' ),
			$settings['business_url'],
			$settings['site_name'],
			$settings['industry'],
			$settings['business_entity_type'],
			self::IDENTITY_VERSION,
		);
		return substr( hash( 'sha256', implode( '|', array_map( 'strval', $identity ) ) ), 0, 24 );
	}

	public function industries() {
		return array(
			'general'             => array( 'label' => 'General business', 'recommended' => 'Organization', 'ymyl' => false ),
			'accounting'          => array( 'label' => 'Accounting and tax', 'recommended' => 'AccountingService', 'ymyl' => true ),
			'finance'             => array( 'label' => 'Financial services', 'recommended' => 'FinancialService', 'ymyl' => true ),
			'legal'               => array( 'label' => 'Legal services', 'recommended' => 'LegalService', 'ymyl' => true ),
			'real_estate'         => array( 'label' => 'Real estate', 'recommended' => 'RealEstateAgent', 'ymyl' => true ),
			'therapy_healthcare'  => array( 'label' => 'Therapy and healthcare', 'recommended' => 'Organization', 'ymyl' => true ),
			'dental'              => array( 'label' => 'Dental practice', 'recommended' => 'Dentist', 'ymyl' => true ),
			'driver_transport'    => array( 'label' => 'Driver and transport services', 'recommended' => 'Organization', 'ymyl' => false ),
			'home_services'       => array( 'label' => 'Home and property services', 'recommended' => 'HomeAndConstructionBusiness', 'ymyl' => false ),
			'automotive'          => array( 'label' => 'Automotive services', 'recommended' => 'AutomotiveBusiness', 'ymyl' => false ),
			'travel_hospitality'  => array( 'label' => 'Travel and hospitality', 'recommended' => 'TravelAgency', 'ymyl' => false ),
			'retail'              => array( 'label' => 'Retail store', 'recommended' => 'Store', 'ymyl' => false ),
			'ecommerce'           => array( 'label' => 'Online store', 'recommended' => 'OnlineStore', 'ymyl' => false ),
			'restaurant'          => array( 'label' => 'Restaurant or food business', 'recommended' => 'Restaurant', 'ymyl' => false ),
			'hotel'               => array( 'label' => 'Hotel or accommodation', 'recommended' => 'Hotel', 'ymyl' => false ),
			'education'           => array( 'label' => 'Education', 'recommended' => 'EducationalOrganization', 'ymyl' => false ),
			'nonprofit'           => array( 'label' => 'Nonprofit', 'recommended' => 'NGO', 'ymyl' => false ),
			'employment'          => array( 'label' => 'Recruitment and employment', 'recommended' => 'EmploymentAgency', 'ymyl' => false ),
			'marketing_agency'    => array( 'label' => 'Marketing or digital agency', 'recommended' => 'Organization', 'ymyl' => false ),
			'software'            => array( 'label' => 'Software or technology', 'recommended' => 'Organization', 'ymyl' => false ),
		);
	}

	public function entity_types() {
		return array(
			'Organization'                => array( 'label' => 'Organization', 'requires_address' => false ),
			'LocalBusiness'               => array( 'label' => 'Local business', 'requires_address' => true ),
			'AccountingService'           => array( 'label' => 'Accounting service', 'requires_address' => true ),
			'FinancialService'            => array( 'label' => 'Financial service', 'requires_address' => true ),
			'LegalService'                => array( 'label' => 'Legal service', 'requires_address' => true ),
			'RealEstateAgent'             => array( 'label' => 'Real estate agent', 'requires_address' => true ),
			'MedicalClinic'               => array( 'label' => 'Medical clinic', 'requires_address' => true ),
			'Dentist'                     => array( 'label' => 'Dentist', 'requires_address' => true ),
			'HealthAndBeautyBusiness'     => array( 'label' => 'Health and beauty business', 'requires_address' => true ),
			'HomeAndConstructionBusiness' => array( 'label' => 'Home and construction business', 'requires_address' => true ),
			'AutomotiveBusiness'          => array( 'label' => 'Automotive business', 'requires_address' => true ),
			'AutoRepair'                  => array( 'label' => 'Auto repair', 'requires_address' => true ),
			'TravelAgency'                => array( 'label' => 'Travel agency', 'requires_address' => true ),
			'Store'                       => array( 'label' => 'Store', 'requires_address' => true ),
			'OnlineStore'                 => array( 'label' => 'Online store', 'requires_address' => false ),
			'Restaurant'                  => array( 'label' => 'Restaurant', 'requires_address' => true ),
			'Hotel'                       => array( 'label' => 'Hotel', 'requires_address' => true ),
			'EducationalOrganization'     => array( 'label' => 'Educational organization', 'requires_address' => false ),
			'NGO'                         => array( 'label' => 'NGO', 'requires_address' => false ),
			'EmploymentAgency'            => array( 'label' => 'Employment agency', 'requires_address' => true ),
		);
	}

	public function allowed_entity_types( $industry ) {
		$map = array(
			'general'            => array( 'Organization', 'LocalBusiness' ),
			'accounting'         => array( 'Organization', 'LocalBusiness', 'AccountingService', 'FinancialService' ),
			'finance'            => array( 'Organization', 'LocalBusiness', 'FinancialService', 'AccountingService' ),
			'legal'              => array( 'Organization', 'LocalBusiness', 'LegalService' ),
			'real_estate'        => array( 'Organization', 'LocalBusiness', 'RealEstateAgent' ),
			'therapy_healthcare' => array( 'Organization', 'LocalBusiness', 'MedicalClinic', 'HealthAndBeautyBusiness' ),
			'dental'             => array( 'Organization', 'LocalBusiness', 'MedicalClinic', 'Dentist' ),
			'driver_transport'   => array( 'Organization', 'LocalBusiness' ),
			'home_services'      => array( 'Organization', 'LocalBusiness', 'HomeAndConstructionBusiness' ),
			'automotive'         => array( 'Organization', 'LocalBusiness', 'AutomotiveBusiness', 'AutoRepair' ),
			'travel_hospitality' => array( 'Organization', 'LocalBusiness', 'TravelAgency', 'Hotel' ),
			'retail'             => array( 'Organization', 'LocalBusiness', 'Store', 'OnlineStore' ),
			'ecommerce'          => array( 'Organization', 'OnlineStore', 'Store' ),
			'restaurant'         => array( 'Organization', 'LocalBusiness', 'Restaurant' ),
			'hotel'              => array( 'Organization', 'LocalBusiness', 'Hotel' ),
			'education'          => array( 'Organization', 'EducationalOrganization', 'LocalBusiness' ),
			'nonprofit'          => array( 'Organization', 'NGO' ),
			'employment'         => array( 'Organization', 'LocalBusiness', 'EmploymentAgency' ),
			'marketing_agency'   => array( 'Organization', 'LocalBusiness' ),
			'software'           => array( 'Organization', 'OnlineStore' ),
		);
		return $map[ sanitize_key( $industry ) ] ?? array( 'Organization' );
	}

	public function allowed_schema_types( array $settings = array() ) {
		$settings = wp_parse_args( $settings, Ikon_SEO_Plugin::settings() );
		$types    = array(
			'WebPage',
			'AboutPage',
			'ContactPage',
			'CollectionPage',
			'ProfilePage',
			'Service',
			'BreadcrumbList',
			'Article',
			'BlogPosting',
			'Person',
			'ItemList',
			'OfferCatalog',
			'WebApplication',
			'HowTo',
			'VideoObject',
			'ImageObject',
		);
		if ( ! empty( $settings['allow_entity_schema'] ) ) {
			$types[] = sanitize_text_field( $settings['business_entity_type'] );
		}
		if ( ! empty( $settings['local_module_enabled'] ) ) {
			foreach ( $this->allowed_entity_types( $settings['industry'] ) as $entity_type ) {
				if ( $this->entity_requires_address( $entity_type ) ) {
					$types[] = $entity_type;
				}
			}
			$types[] = 'LocalBusiness';
		}
		if ( ! empty( $settings['semantic_faq'] ) ) {
			$types[] = 'FAQPage';
		}
		return array_values( array_unique( array_filter( $types ) ) );
	}

	public function entity_requires_address( $type ) {
		$types = $this->entity_types();
		return ! empty( $types[ $type ]['requires_address'] );
	}

	public function industry_is_high_trust( $industry = '' ) {
		$industry  = $industry ?: Ikon_SEO_Plugin::settings()['industry'];
		$industries= $this->industries();
		return ! empty( $industries[ sanitize_key( $industry ) ]['ymyl'] );
	}

	public function has_verified_address( array $settings = array() ) {
		$settings = wp_parse_args( $settings, Ikon_SEO_Plugin::settings() );
		return ! empty( $settings['verified_business'] )
			&& ! empty( $settings['address_street'] )
			&& ! empty( $settings['address_locality'] )
			&& ! empty( $settings['address_country'] );
	}

	public static function migrate_legacy_settings() {
		$settings = get_option( Ikon_SEO_Plugin::OPTION_KEY, array() );
		if ( ! is_array( $settings ) || ( $settings['profile_version'] ?? '' ) === self::VERSION ) {
			return;
		}

		// v0.3 and newer profiles are already website-independent. Preserve every
		// configured identity field when adding later modules.
		if ( version_compare( (string) ( $settings['profile_version'] ?? '0' ), '3.0', '>=' ) ) {
			$settings['profile_version'] = self::VERSION;
			$settings['profile_home_url']= home_url( '/' );
			update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
			return;
		}

		$is_zerosync = false !== stripos( (string) ( $settings['site_name'] ?? '' ), 'ZeroSync' )
			|| false !== stripos( (string) ( $settings['business_url'] ?? '' ), 'zerosyncaccountants.ae' );

		$settings['profile_version']      = self::VERSION;
		$settings['profile_home_url']     = home_url( '/' );
		$settings['profile_configured']   = $is_zerosync ? 1 : 0;
		$settings['industry']             = $is_zerosync ? 'accounting' : 'general';
		$settings['business_entity_type'] = $is_zerosync ? 'AccountingService' : 'Organization';
		$settings['target_locations']     = $is_zerosync ? "Dubai\nUnited Arab Emirates" : '';
		$settings['supported_languages']  = $settings['default_language'] ?? self::locale();
		$settings['default_currency']     = $is_zerosync ? 'AED' : 'USD';
		$settings['contact_email']        = '';
		$settings['builder_preference']   = 'auto';
		$settings['seo_plugin_preference']= 'auto';
		$settings['allow_entity_schema']  = 0;
		$settings['require_profile_match']= 1;
		$settings['content_rules']        = '';
		$settings['cta_templates']        = '';

		if ( ! $is_zerosync && 'ZeroSync Accountants' === ( $settings['site_name'] ?? '' ) ) {
			$settings['site_name']        = get_bloginfo( 'name' );
			$settings['business_url']     = home_url( '/' );
			$settings['target_market']    = '';
			$settings['business_phone']   = '';
			$settings['whatsapp_url']     = '';
			$settings['address_locality'] = '';
			$settings['address_region']   = '';
			$settings['address_country']  = '';
		}

		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
	}

	public static function locale() {
		$locale = str_replace( '_', '-', (string) get_locale() );
		return preg_match( '/^[a-z]{2,3}(?:-[A-Z]{2})?$/', $locale ) ? $locale : 'en';
	}

	private function builder() {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			return array( 'detected' => 'elementor', 'version' => ELEMENTOR_VERSION );
		}
		return array( 'detected' => 'gutenberg', 'version' => get_bloginfo( 'version' ) );
	}

	private function seo_plugin() {
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			return array( 'detected' => 'rank_math', 'version' => RANK_MATH_VERSION );
		}
		if ( defined( 'WPSEO_VERSION' ) ) {
			return array( 'detected' => 'yoast', 'version' => WPSEO_VERSION );
		}
		return array( 'detected' => 'none', 'version' => '' );
	}

	private function lines( $value ) {
		return array_values( array_unique( array_filter( array_map( 'sanitize_text_field', preg_split( '/[\r\n,]+/', (string) $value ) ) ) ) );
	}

	private function languages( $value, $default ) {
		$languages = $this->lines( $value );
		$languages[] = sanitize_text_field( $default );
		return array_values(
			array_unique(
				array_filter(
					$languages,
					function( $language ) {
						return (bool) preg_match( '/^[a-z]{2,3}(?:-[A-Z]{2})?$/', $language );
					}
				)
			)
		);
	}
}
