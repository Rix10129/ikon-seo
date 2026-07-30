<?php

defined( 'ABSPATH' ) || exit;

/**
 * Google Business Profile integration.
 *
 * Google only provides the broad business.manage OAuth scope. Ikon SEO uses it
 * in read-only policy mode for imports, reviews and performance. Mutations are
 * never exposed as direct remote actions: they are staged locally and require
 * a WordPress administrator to approve the exact post or review reply.
 */
class Ikon_SEO_GBP {
	const OAUTH_SCOPE = 'https://www.googleapis.com/auth/business.manage';

	private $crypto;
	private $logger;
	private $local;
	private $profile;

	public function __construct( Ikon_SEO_Crypto $crypto, Ikon_SEO_Logger $logger, Ikon_SEO_Local $local, Ikon_SEO_Profile $profile ) {
		$this->crypto  = $crypto;
		$this->logger  = $logger;
		$this->local   = $local;
		$this->profile = $profile;
	}

	public function register_hooks() {
		add_action( 'ikon_seo_daily_monitor', array( $this, 'daily_review_check' ), 20 );
	}

	public function status() {
		$settings = Ikon_SEO_Plugin::settings();
		$linked   = 0;
		foreach ( $this->local->locations( true ) as $location ) {
			if ( ! empty( $location['gbp_location_name'] ) ) {
				$linked++;
			}
		}

		return array(
			'configured'        => ! empty( $settings['gbp_client_id'] ) && ! empty( $settings['gbp_client_secret'] ),
			'connected'         => ! empty( $settings['gbp_refresh_token'] ),
			'account'           => sanitize_text_field( $settings['gbp_account'] ),
			'linked_locations'  => $linked,
			'scope'             => self::OAUTH_SCOPE,
			'policy_mode'       => 'read_only_with_admin_approved_mutations',
			'remote_mutations'  => false,
			'approval_required' => true,
			'callback_url'      => $this->callback_url(),
			'last_error'        => sanitize_text_field( $settings['gbp_last_error'] ),
			'crypto_ready'      => $this->crypto->available(),
			'api_access_note'   => 'The Google Cloud project must be approved for Business Profile API access. Google does not provide a sandbox.',
			'review_alerts'     => $this->review_alerts(),
		);
	}

	public function save_credentials( $client_id, $client_secret ) {
		$client_id     = sanitize_text_field( $client_id );
		$client_secret = trim( (string) $client_secret );
		if ( ! $client_id || ! preg_match( '/\.apps\.googleusercontent\.com$/', $client_id ) ) {
			return new WP_Error( 'ikon_seo_gbp_client_id', __( 'Enter a valid Google OAuth web application client ID.', 'ikon-seo' ) );
		}

		$settings = Ikon_SEO_Plugin::settings();
		$this->clear_cache();
		if ( '' !== $client_secret ) {
			$encrypted = $this->crypto->encrypt( $client_secret );
			if ( is_wp_error( $encrypted ) ) {
				return $encrypted;
			}
			$settings['gbp_refresh_token'] = '';
			$settings['gbp_account']       = '';
			$settings['gbp_client_secret'] = $encrypted;
		} elseif ( empty( $settings['gbp_client_secret'] ) ) {
			return new WP_Error( 'ikon_seo_gbp_client_secret', __( 'Enter the Google OAuth client secret.', 'ikon-seo' ) );
		} elseif ( $client_id !== $settings['gbp_client_id'] ) {
			return new WP_Error( 'ikon_seo_gbp_client_secret', __( 'Enter the matching client secret when changing the Google OAuth client ID.', 'ikon-seo' ) );
		}

		$settings['gbp_client_id'] = $client_id;
		$settings['gbp_last_error']= '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->clear_cache();
		return $this->status();
	}

	public function authorization_url( $user_id ) {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['gbp_client_id'] ) || empty( $settings['gbp_client_secret'] ) ) {
			return new WP_Error( 'ikon_seo_gbp_credentials', __( 'Save Google Business Profile OAuth credentials before connecting.', 'ikon-seo' ) );
		}
		try {
			$state    = bin2hex( random_bytes( 24 ) );
			$verifier = $this->base64url( random_bytes( 48 ) );
		} catch ( Exception $exception ) {
			return new WP_Error( 'ikon_seo_gbp_state', __( 'A secure OAuth state could not be generated.', 'ikon-seo' ) );
		}
		set_transient(
			'ikon_seo_gbp_state_' . hash( 'sha256', $state ),
			array( 'user_id' => absint( $user_id ), 'verifier' => $verifier ),
			10 * MINUTE_IN_SECONDS
		);

		return add_query_arg(
			array(
				'client_id'              => $settings['gbp_client_id'],
				'redirect_uri'           => $this->callback_url(),
				'response_type'          => 'code',
				'scope'                  => self::OAUTH_SCOPE,
				'access_type'            => 'offline',
				'prompt'                 => 'consent',
				'include_granted_scopes' => 'true',
				'state'                  => $state,
				'code_challenge'         => $this->base64url( hash( 'sha256', $verifier, true ) ),
				'code_challenge_method'  => 'S256',
			),
			'https://accounts.google.com/o/oauth2/v2/auth'
		);
	}

	public function complete_authorization( $state, $code, $user_id ) {
		$key     = 'ikon_seo_gbp_state_' . hash( 'sha256', (string) $state );
		$pending = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $pending ) || absint( $pending['user_id'] ?? 0 ) !== absint( $user_id ) || empty( $pending['verifier'] ) ) {
			return new WP_Error( 'ikon_seo_gbp_state', __( 'The Google authorization request expired or did not match this administrator.', 'ikon-seo' ) );
		}
		if ( ! $code ) {
			return new WP_Error( 'ikon_seo_gbp_code', __( 'Google did not return an authorization code.', 'ikon-seo' ) );
		}

		$settings      = Ikon_SEO_Plugin::settings();
		$client_secret = $this->crypto->decrypt( $settings['gbp_client_secret'] );
		if ( is_wp_error( $client_secret ) ) {
			return $client_secret;
		}
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'code'          => sanitize_text_field( $code ),
					'client_id'     => $settings['gbp_client_id'],
					'client_secret' => $client_secret,
					'redirect_uri'  => $this->callback_url(),
					'grant_type'    => 'authorization_code',
					'code_verifier' => $pending['verifier'],
				),
			)
		);
		$data = $this->decode_response( $response, 'Google Business Profile authorization' );
		if ( is_wp_error( $data ) ) {
			$this->remember_error( $data->get_error_message() );
			return $data;
		}
		if ( empty( $data['refresh_token'] ) ) {
			return new WP_Error( 'ikon_seo_gbp_refresh_token', __( 'Google did not return an offline refresh token. Reconnect and approve access again.', 'ikon-seo' ) );
		}
		$encrypted = $this->crypto->encrypt( $data['refresh_token'] );
		if ( is_wp_error( $encrypted ) ) {
			return $encrypted;
		}
		$settings['gbp_refresh_token'] = $encrypted;
		$settings['gbp_last_error']    = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->store_access_token( $data );
		$this->logger->log( 'gbp_connect', 'success', 'Google Business Profile connected. Mutations remain administrator approval-gated.' );
		return $this->accounts();
	}

	public function disconnect() {
		$settings                      = Ikon_SEO_Plugin::settings();
		$this->clear_cache();
		$settings['gbp_refresh_token'] = '';
		$settings['gbp_account']       = '';
		$settings['gbp_last_error']    = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->local->reject_gbp_drafts( 0, 'Google Business Profile was disconnected before approval.' );
		delete_transient( 'ikon_seo_gbp_access_token' );
		$this->logger->log( 'gbp_disconnect', 'success', 'Google Business Profile connection removed.' );
	}

	public function accounts() {
		$items = array();
		$token = '';
		for ( $page = 0; $page < 10; $page++ ) {
			$args = array( 'pageSize' => 20 );
			if ( $token ) {
				$args['pageToken'] = $token;
			}
			$data = $this->request( 'GET', 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts?' . http_build_query( $args ) );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			foreach ( (array) ( $data['accounts'] ?? array() ) as $account ) {
				$name = sanitize_text_field( $account['name'] ?? '' );
				if ( ! preg_match( '#^accounts/[0-9]+$#', $name ) ) {
					continue;
				}
				$items[] = array(
					'name'         => $name,
					'account_name' => sanitize_text_field( $account['accountName'] ?? '' ),
					'type'         => sanitize_key( $account['type'] ?? '' ),
					'role'         => sanitize_key( $account['role'] ?? '' ),
				);
			}
			$token = sanitize_text_field( $data['nextPageToken'] ?? '' );
			if ( ! $token ) {
				break;
			}
		}
		return array( 'items' => $items );
	}

	public function select_account( $account_name ) {
		$account_name = sanitize_text_field( $account_name );
		$accounts     = $this->accounts();
		if ( is_wp_error( $accounts ) ) {
			return $accounts;
		}
		if ( ! in_array( $account_name, wp_list_pluck( $accounts['items'], 'name' ), true ) ) {
			return new WP_Error( 'ikon_seo_gbp_account', __( 'Select an account available to the connected Google user.', 'ikon-seo' ) );
		}
		$settings                = Ikon_SEO_Plugin::settings();
		$previous                = sanitize_text_field( $settings['gbp_account'] );
		if ( $previous && ! hash_equals( $previous, $account_name ) ) {
			$this->clear_cache();
			foreach ( $this->local->locations( true ) as $location ) {
				if ( ! empty( $location['gbp_location_name'] ) ) {
					$this->local->unlink_gbp( $location['id'] );
				}
			}
		}
		$settings['gbp_account'] = $account_name;
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->clear_cache();
		return $this->remote_locations( true );
	}

	public function remote_locations( $refresh = false ) {
		$account = sanitize_text_field( Ikon_SEO_Plugin::settings()['gbp_account'] );
		if ( ! preg_match( '#^accounts/[0-9]+$#', $account ) ) {
			return new WP_Error( 'ikon_seo_gbp_account', __( 'Select a Google Business Profile account first.', 'ikon-seo' ) );
		}
		$cache_key = 'ikon_seo_gbp_locations_' . md5( $account );
		if ( ! $refresh ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$mask = implode(
			',',
			array(
				'name',
				'title',
				'storeCode',
				'phoneNumbers',
				'categories',
				'storefrontAddress',
				'websiteUri',
				'regularHours',
				'specialHours',
				'serviceArea',
				'metadata',
				'latlng',
				'openInfo',
				'profile',
				'serviceItems',
			)
		);
		$items = array();
		$token = '';
		for ( $page = 0; $page < 20; $page++ ) {
			$args = array( 'readMask' => $mask, 'pageSize' => 100 );
			if ( $token ) {
				$args['pageToken'] = $token;
			}
			$url  = 'https://mybusinessbusinessinformation.googleapis.com/v1/' . $account . '/locations?' . http_build_query( $args );
			$data = $this->request( 'GET', $url );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			foreach ( (array) ( $data['locations'] ?? array() ) as $location ) {
				$name = sanitize_text_field( $location['name'] ?? '' );
				if ( ! preg_match( '#^locations/[0-9]+$#', $name ) ) {
					continue;
				}
				$items[] = array(
					'name'              => $name,
					'title'             => sanitize_text_field( $location['title'] ?? '' ),
					'store_code'        => sanitize_text_field( $location['storeCode'] ?? '' ),
					'primary_phone'     => sanitize_text_field( $location['phoneNumbers']['primaryPhone'] ?? '' ),
					'additional_phones' => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $location['phoneNumbers']['additionalPhones'] ?? array() ) ) ) ),
					'primary_category'  => sanitize_text_field( $location['categories']['primaryCategory']['displayName'] ?? '' ),
					'additional_categories' => array_values( array_filter( array_map( function( $category ) {
						return sanitize_text_field( $category['displayName'] ?? '' );
					}, (array) ( $location['categories']['additionalCategories'] ?? array() ) ) ) ),
					'address'           => $this->clean_remote_address( (array) ( $location['storefrontAddress'] ?? array() ) ),
					'website_url'       => esc_url_raw( $location['websiteUri'] ?? '' ),
					'place_id'          => sanitize_text_field( $location['metadata']['placeId'] ?? '' ),
					'maps_url'          => esc_url_raw( $location['metadata']['mapsUri'] ?? '' ),
					'new_review_url'    => esc_url_raw( $location['metadata']['newReviewUri'] ?? '' ),
					'can_delete'        => ! empty( $location['metadata']['canDelete'] ),
					'open_for_business' => 'OPEN' === ( $location['openInfo']['status'] ?? '' ),
					'regular_hours'     => $this->clean_regular_hours( (array) ( $location['regularHours']['periods'] ?? array() ) ),
					'special_hours'     => (array) ( $location['specialHours']['specialHourPeriods'] ?? array() ),
					'service_areas'     => $this->clean_service_areas( (array) ( $location['serviceArea'] ?? array() ) ),
					'services'          => $this->clean_service_items( (array) ( $location['serviceItems'] ?? array() ) ),
					'fetched_at'        => current_time( 'mysql', true ),
				);
			}
			$token = sanitize_text_field( $data['nextPageToken'] ?? '' );
			if ( ! $token ) {
				break;
			}
		}
		$result = array( 'account' => $account, 'items' => $items );
		set_transient( $cache_key, $result, 30 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function link_location( $local_id, $remote_name ) {
		$local = $this->local->location( $local_id );
		if ( ! $local ) {
			return new WP_Error( 'ikon_seo_gbp_local', __( 'The local location record was not found.', 'ikon-seo' ) );
		}
		if ( 'online' === $local['location_type'] ) {
			return new WP_Error( 'ikon_seo_gbp_eligibility', __( 'An online-only record cannot be linked to Google Business Profile.', 'ikon-seo' ) );
		}
		$remote_name = sanitize_text_field( $remote_name );
		$remote      = $this->remote_locations();
		if ( is_wp_error( $remote ) ) {
			return $remote;
		}
		$matched = null;
		foreach ( $remote['items'] as $item ) {
			if ( $item['name'] === $remote_name ) {
				$matched = $item;
				break;
			}
		}
		if ( ! $matched ) {
			return new WP_Error( 'ikon_seo_gbp_location', __( 'Select a location available to the chosen Google Business Profile account.', 'ikon-seo' ) );
		}
		$this->clear_cache();
		$result = $this->local->link_gbp(
			$local_id,
			$remote['account'],
			$matched['name'],
			$matched['place_id']
		);
		$this->clear_cache();
		return $result;
	}

	public function unlink_location( $local_id ) {
		$this->clear_cache();
		return $this->local->unlink_gbp( $local_id );
	}

	public function comparison( $local_id ) {
		$location = $this->local->location( $local_id );
		if ( ! $location || ! $location['gbp_location_name'] ) {
			return new WP_Error( 'ikon_seo_gbp_link', __( 'Link this local record to a Google Business Profile location first.', 'ikon-seo' ) );
		}
		$remote = $this->remote_locations();
		if ( is_wp_error( $remote ) ) {
			return $remote;
		}
		$match = null;
		foreach ( $remote['items'] as $item ) {
			if ( $item['name'] === $location['gbp_location_name'] ) {
				$match = $item;
				break;
			}
		}
		if ( ! $match ) {
			return new WP_Error( 'ikon_seo_gbp_location', __( 'The linked Google Business Profile location was not returned by the selected account.', 'ikon-seo' ) );
		}

		$checks = array();
		$this->compare_value( $checks, 'business_name', $location['business_name'], $match['title'], false );
		$this->compare_value( $checks, 'phone', $this->normalize_phone( $location['phone'] ), $this->normalize_phone( $match['primary_phone'] ), false );
		$this->compare_value( $checks, 'website_url', $this->canonical_url( $location['website_url'] ), $this->canonical_url( $match['website_url'] ), false );
		$this->compare_value( $checks, 'primary_category', $location['primary_category'], $match['primary_category'], true );
		$this->compare_set( $checks, 'additional_categories', $location['additional_categories'], $match['additional_categories'], true );
		$this->compare_value( $checks, 'locality', $location['address']['locality'], $match['address']['locality'], false );
		$this->compare_value( $checks, 'street', $location['address']['street'], implode( ', ', $match['address']['address_lines'] ), ! $location['has_customer_location'] );
		$this->compare_value( $checks, 'region', $location['address']['region'], $match['address']['administrative_area'], true );
		$this->compare_value( $checks, 'postal_code', $location['address']['postal'], $match['address']['postal_code'], true );
		$this->compare_value( $checks, 'country', strtoupper( $location['address']['country'] ), strtoupper( $match['address']['region_code'] ), false );
		$this->compare_value( $checks, 'place_id', $location['place_id'], $match['place_id'], true );
		$this->compare_set( $checks, 'opening_hours', $location['opening_hours'], $match['regular_hours'], true );
		$this->compare_set( $checks, 'service_areas', $location['service_areas'], $match['service_areas'], true );
		$this->compare_set( $checks, 'services', $location['services'], $match['services'], true );

		$failures = count( array_filter( $checks, function( $check ) { return 'fail' === $check['status']; } ) );
		$warnings = count( array_filter( $checks, function( $check ) { return 'warning' === $check['status']; } ) );
		return array(
			'location_id' => absint( $local_id ),
			'status'      => $failures ? 'needs_changes' : ( $warnings ? 'review' : 'consistent' ),
			'failures'    => $failures,
			'warnings'    => $warnings,
			'checks'      => $checks,
			'remote'      => $match,
			'generated_at'=> current_time( 'mysql', true ),
		);
	}

	public function reviews( $local_id, $refresh = false ) {
		$parent = $this->review_parent( $local_id );
		if ( is_wp_error( $parent ) ) {
			return $parent;
		}
		$cache_key = 'ikon_seo_gbp_reviews_' . md5( $parent );
		if ( ! $refresh ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$items = array();
		$token = '';
		$average_rating = 0;
		$total_reviews  = 0;
		for ( $page = 0; $page < 4; $page++ ) {
			$args = array( 'pageSize' => 50, 'orderBy' => 'updateTime desc' );
			if ( $token ) {
				$args['pageToken'] = $token;
			}
			$url  = 'https://mybusiness.googleapis.com/v4/' . $parent . '/reviews?' . http_build_query( $args );
			$data = $this->request( 'GET', $url );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			$average_rating = (float) ( $data['averageRating'] ?? $average_rating );
			$total_reviews  = absint( $data['totalReviewCount'] ?? $total_reviews );
			foreach ( (array) ( $data['reviews'] ?? array() ) as $review ) {
				$name = sanitize_text_field( $review['name'] ?? '' );
				if ( 0 !== strpos( $name, $parent . '/reviews/' ) ) {
					continue;
				}
				$items[] = array(
					'name'             => $name,
					'review_id'        => sanitize_text_field( $review['reviewId'] ?? '' ),
					'reviewer_name'    => sanitize_text_field( $review['reviewer']['displayName'] ?? '' ),
					'reviewer_photo'   => esc_url_raw( $review['reviewer']['profilePhotoUrl'] ?? '' ),
					'star_rating'      => sanitize_key( $review['starRating'] ?? '' ),
					'comment'          => sanitize_textarea_field( $review['comment'] ?? '' ),
					'create_time'      => sanitize_text_field( $review['createTime'] ?? '' ),
					'update_time'      => sanitize_text_field( $review['updateTime'] ?? '' ),
					'owner_reply'      => sanitize_textarea_field( $review['reviewReply']['comment'] ?? '' ),
					'reply_update_time'=> sanitize_text_field( $review['reviewReply']['updateTime'] ?? '' ),
					'review_reply_url' => esc_url_raw( $review['reviewReplyUrl'] ?? '' ),
					'policy_violation' => isset( $review['policyViolation'] ) ? (array) $review['policyViolation'] : array(),
				);
			}
			$token = sanitize_text_field( $data['nextPageToken'] ?? '' );
			if ( ! $token ) {
				break;
			}
		}
		$result = array(
			'location_id'   => absint( $local_id ),
			'average_rating'=> $average_rating,
			'total_reviews' => $total_reviews ?: count( $items ),
			'items'         => $items,
			'fetched_at'    => current_time( 'mysql', true ),
			'storage_note'  => 'Review content is cached briefly and is not added to the permanent Ikon SEO database.',
		);
		set_transient( $cache_key, $result, 10 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function search_keywords( $local_id, $months = 3, $refresh = false ) {
		$location = $this->local->location( $local_id );
		if ( ! $location || ! preg_match( '#^locations/([0-9]+)$#', $location['gbp_location_name'], $match ) ) {
			return new WP_Error( 'ikon_seo_gbp_link', __( 'Link this local record to a Google Business Profile location first.', 'ikon-seo' ) );
		}
		$months    = max( 1, min( 18, absint( $months ) ) );
		$remote_id = $match[1];
		$cache_key = 'ikon_seo_gbp_keywords_' . $remote_id . '_' . $months;
		if ( ! $refresh ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$end   = new DateTime( 'first day of last month', new DateTimeZone( 'UTC' ) );
		$start = clone $end;
		$start->modify( '-' . ( $months - 1 ) . ' months' );
		$items = array();
		$token = '';
		for ( $page = 0; $page < 10; $page++ ) {
			$args = array(
				'monthlyRange.startMonth.year' => $start->format( 'Y' ),
				'monthlyRange.startMonth.month'=> $start->format( 'n' ),
				'monthlyRange.endMonth.year'   => $end->format( 'Y' ),
				'monthlyRange.endMonth.month'  => $end->format( 'n' ),
				'pageSize'                     => 100,
			);
			if ( $token ) {
				$args['pageToken'] = $token;
			}
			$url  = 'https://businessprofileperformance.googleapis.com/v1/locations/' . rawurlencode( $remote_id ) . '/searchkeywords/impressions/monthly?' . http_build_query( $args );
			$data = $this->request( 'GET', $url );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			foreach ( (array) ( $data['searchKeywordsCounts'] ?? array() ) as $row ) {
				$insights = (array) ( $row['insightsValue'] ?? array() );
				$items[]  = array(
					'keyword'   => sanitize_text_field( $row['searchKeyword'] ?? '' ),
					'value'     => isset( $insights['value'] ) ? absint( $insights['value'] ) : null,
					'threshold' => isset( $insights['threshold'] ) ? absint( $insights['threshold'] ) : null,
				);
			}
			$token = sanitize_text_field( $data['nextPageToken'] ?? '' );
			if ( ! $token ) {
				break;
			}
		}
		$result = array(
			'location_id' => absint( $local_id ),
			'period'      => array( 'start_month' => $start->format( 'Y-m' ), 'end_month' => $end->format( 'Y-m' ), 'months' => $months ),
			'items'       => $items,
			'fetched_at'  => current_time( 'mysql', true ),
		);
		set_transient( $cache_key, $result, 6 * HOUR_IN_SECONDS );
		return $result;
	}

	public function performance( $local_id, $days = 30, $refresh = false ) {
		$location = $this->local->location( $local_id );
		if ( ! $location || ! preg_match( '#^locations/([0-9]+)$#', $location['gbp_location_name'], $match ) ) {
			return new WP_Error( 'ikon_seo_gbp_link', __( 'Link this local record to a Google Business Profile location first.', 'ikon-seo' ) );
		}
		$days      = max( 7, min( 90, absint( $days ) ) );
		$location_id = $match[1];
		$cache_key = 'ikon_seo_gbp_performance_' . $location_id . '_' . $days;
		if ( ! $refresh ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$end   = new DateTime( 'yesterday', new DateTimeZone( 'UTC' ) );
		$start = clone $end;
		$start->modify( '-' . ( $days - 1 ) . ' days' );
		$metrics = array(
			'BUSINESS_IMPRESSIONS_DESKTOP_MAPS',
			'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH',
			'BUSINESS_IMPRESSIONS_MOBILE_MAPS',
			'BUSINESS_IMPRESSIONS_MOBILE_SEARCH',
			'WEBSITE_CLICKS',
			'CALL_CLICKS',
			'BUSINESS_DIRECTION_REQUESTS',
			'BUSINESS_CONVERSATIONS',
			'BUSINESS_BOOKINGS',
		);
		$query = array(
			'dailyRange.startDate.year' => $start->format( 'Y' ),
			'dailyRange.startDate.month'=> $start->format( 'n' ),
			'dailyRange.startDate.day'  => $start->format( 'j' ),
			'dailyRange.endDate.year'   => $end->format( 'Y' ),
			'dailyRange.endDate.month'  => $end->format( 'n' ),
			'dailyRange.endDate.day'    => $end->format( 'j' ),
		);
		$metric_query = implode( '&', array_map( function( $metric ) { return 'dailyMetrics=' . rawurlencode( $metric ); }, $metrics ) );
		$url  = 'https://businessprofileperformance.googleapis.com/v1/locations/' . rawurlencode( $location_id ) . ':fetchMultiDailyMetricsTimeSeries?' . $metric_query . '&' . http_build_query( $query );
		$data = $this->request( 'GET', $url );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$series = array();
		$totals = array();
		foreach ( (array) ( $data['multiDailyMetricTimeSeries'] ?? array() ) as $multi ) {
			foreach ( (array) ( $multi['dailyMetricTimeSeries'] ?? array() ) as $row ) {
				$metric = sanitize_key( $row['dailyMetric'] ?? '' );
				$points = array();
				$total  = 0;
				foreach ( (array) ( $row['timeSeries']['datedValues'] ?? array() ) as $point ) {
					$value = (float) ( $point['value'] ?? 0 );
					$date  = (array) ( $point['date'] ?? array() );
					$points[] = array(
						'date'  => sprintf( '%04d-%02d-%02d', absint( $date['year'] ?? 0 ), absint( $date['month'] ?? 0 ), absint( $date['day'] ?? 0 ) ),
						'value' => $value,
					);
					$total += $value;
				}
				$series[ $metric ] = $points;
				$totals[ $metric ] = $total;
			}
		}
		$result = array(
			'location_id' => absint( $local_id ),
			'period'      => array( 'start' => $start->format( 'Y-m-d' ), 'end' => $end->format( 'Y-m-d' ), 'days' => $days ),
			'totals'      => $totals,
			'series'      => $series,
			'fetched_at'  => current_time( 'mysql', true ),
		);
		set_transient( $cache_key, $result, 6 * HOUR_IN_SECONDS );
		return $result;
	}

	public function stage_draft( array $input, $user_id = 0 ) {
		global $wpdb;

		if ( ! $user_id ) {
			$submitted = sanitize_text_field( $input['profile_id'] ?? '' );
			$current   = $this->profile->fingerprint();
			if ( ! $submitted || ! hash_equals( $current, $submitted ) ) {
				return new WP_Error( 'ikon_seo_gbp_profile', __( 'Read the current Website Profile and include its profile_id before staging a Business Profile draft.', 'ikon-seo' ) );
			}
		}

		$type     = sanitize_key( $input['draft_type'] ?? '' );
		$local_id = absint( $input['location_id'] ?? 0 );
		$location = $this->local->location( $local_id );
		if ( ! $location || ! preg_match( '#^accounts/[0-9]+$#', $location['gbp_account_name'] ) || ! preg_match( '#^locations/[0-9]+$#', $location['gbp_location_name'] ) ) {
			return new WP_Error( 'ikon_seo_gbp_link', __( 'The selected local record is not linked to a Google Business Profile location.', 'ikon-seo' ) );
		}
		if ( ! in_array( $type, array( 'review_reply', 'google_post' ), true ) ) {
			return new WP_Error( 'ikon_seo_gbp_draft_type', __( 'Draft type must be review_reply or google_post.', 'ikon-seo' ) );
		}

		$content = sanitize_textarea_field( $input['content'] ?? '' );
		if ( ! $content || strlen( $content ) > ( 'review_reply' === $type ? 4000 : 1500 ) ) {
			return new WP_Error( 'ikon_seo_gbp_content', __( 'Draft content is missing or exceeds the allowed length.', 'ikon-seo' ) );
		}
		$remote_resource = '';
		$action          = array();
		if ( 'review_reply' === $type ) {
			$remote_resource = sanitize_text_field( $input['review_name'] ?? '' );
			$parent          = $this->review_parent( $local_id );
			if ( is_wp_error( $parent ) || 0 !== strpos( $remote_resource, $parent . '/reviews/' ) ) {
				return new WP_Error( 'ikon_seo_gbp_review', __( 'The review resource does not belong to the linked location.', 'ikon-seo' ) );
			}
		} else {
			$topic = strtoupper( sanitize_text_field( $input['topic_type'] ?? 'STANDARD' ) );
			if ( ! in_array( $topic, array( 'STANDARD', 'EVENT', 'OFFER' ), true ) ) {
				$topic = 'STANDARD';
			}
			$call_to_action_url = $this->same_site_url( $input['call_to_action_url'] ?? '' );
			$redeem_online_url  = $this->same_site_url( $input['redeem_online_url'] ?? '' );
			if ( is_wp_error( $call_to_action_url ) ) {
				return $call_to_action_url;
			}
			if ( is_wp_error( $redeem_online_url ) ) {
				return $redeem_online_url;
			}
			$call_to_action = sanitize_key( $input['call_to_action'] ?? '' );
			$allowed_actions = array( 'book', 'order', 'shop', 'learn_more', 'sign_up', 'call' );
			if ( $call_to_action && ! in_array( $call_to_action, $allowed_actions, true ) ) {
				return new WP_Error( 'ikon_seo_gbp_action', __( 'Choose a supported Google Post call to action.', 'ikon-seo' ) );
			}
			if ( (bool) $call_to_action !== (bool) $call_to_action_url ) {
				return new WP_Error( 'ikon_seo_gbp_action_url', __( 'A Google Post call to action and its same-site URL must be supplied together.', 'ikon-seo' ) );
			}
			$action = array(
				'topic_type'       => $topic,
				'language_code'    => sanitize_text_field( $input['language_code'] ?? Ikon_SEO_Plugin::settings()['default_language'] ),
				'call_to_action'   => $call_to_action,
				'call_to_action_url'=> $call_to_action_url,
				'event_title'      => sanitize_text_field( $input['event_title'] ?? '' ),
				'start_time'       => sanitize_text_field( $input['start_time'] ?? '' ),
				'end_time'         => sanitize_text_field( $input['end_time'] ?? '' ),
				'coupon_code'      => sanitize_text_field( $input['coupon_code'] ?? '' ),
				'redeem_online_url'=> $redeem_online_url,
				'terms_conditions' => sanitize_textarea_field( $input['terms_conditions'] ?? '' ),
			);
			$remote_resource = $location['gbp_account_name'] . '/' . $location['gbp_location_name'];
		}

		$table = $wpdb->prefix . 'ikon_seo_gbp_drafts';
		$data  = array(
			'profile_id'       => $this->profile->fingerprint(),
			'location_id'      => $local_id,
			'draft_type'       => $type,
			'remote_resource'  => $remote_resource,
			'content'          => $content,
			'action_data'      => wp_json_encode( $action ),
			'status'           => 'draft',
			'created_by'       => absint( $user_id ),
			'approved_by'      => 0,
			'created_at'       => current_time( 'mysql', true ),
			'updated_at'       => current_time( 'mysql', true ),
		);
		$result = $wpdb->insert( $table, $data );
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_gbp_draft', __( 'The Google Business Profile draft could not be staged.', 'ikon-seo' ) );
		}
		$id = absint( $wpdb->insert_id );
		$this->logger->log( 'gbp_stage', 'success', 'A Google Business Profile action was staged for administrator approval.' );
		return $this->draft( $id );
	}

	public function drafts( $limit = 100 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ikon_seo_gbp_drafts';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE profile_id = %s ORDER BY FIELD(status,'draft','failed','sent','rejected'), id DESC LIMIT %d",
				$this->profile->fingerprint(),
				max( 1, min( 500, absint( $limit ) ) )
			),
			ARRAY_A
		);
		return array_map( array( $this, 'public_draft' ), is_array( $rows ) ? $rows : array() );
	}

	public function draft( $id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ikon_seo_gbp_drafts WHERE id = %d AND profile_id = %s LIMIT 1",
				absint( $id ),
				$this->profile->fingerprint()
			),
			ARRAY_A
		);
		return is_array( $row ) ? $this->public_draft( $row ) : null;
	}

	public function approve_draft( $id, $user_id ) {
		global $wpdb;

		if ( ! $user_id || ! user_can( $user_id, 'manage_options' ) ) {
			return new WP_Error( 'ikon_seo_gbp_approval', __( 'Only a WordPress administrator can approve and send a Business Profile draft.', 'ikon-seo' ) );
		}
		$draft = $this->draft( $id );
		if ( ! $draft || 'draft' !== $draft['status'] ) {
			return new WP_Error( 'ikon_seo_gbp_draft_status', __( 'Only a pending Google Business Profile draft can be approved.', 'ikon-seo' ) );
		}
		$lock = 'ikon_seo_gbp_send_' . absint( $id );
		if ( get_transient( $lock ) ) {
			return new WP_Error( 'ikon_seo_gbp_locked', __( 'This Google Business Profile draft is already being processed.', 'ikon-seo' ) );
		}
		set_transient( $lock, 1, 2 * MINUTE_IN_SECONDS );

		if ( 'review_reply' === $draft['draft_type'] ) {
			$result = $this->request(
				'PUT',
				'https://mybusiness.googleapis.com/v4/' . $draft['remote_resource'] . '/reply',
				array( 'comment' => $draft['content'] )
			);
		} else {
			$body   = $this->post_body( $draft );
			$result = is_wp_error( $body ) ? $body : $this->request(
				'POST',
				'https://mybusiness.googleapis.com/v4/' . $draft['remote_resource'] . '/localPosts',
				$body
			);
		}

		delete_transient( $lock );
		$status = is_wp_error( $result ) ? 'failed' : 'sent';
		$error  = is_wp_error( $result ) ? $result->get_error_message() : '';
		$wpdb->update(
			$wpdb->prefix . 'ikon_seo_gbp_drafts',
			array(
				'status'      => $status,
				'approved_by' => absint( $user_id ),
				'sent_at'     => 'sent' === $status ? current_time( 'mysql', true ) : null,
				'last_error'  => sanitize_textarea_field( $error ),
				'updated_at'  => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() )
		);
		$this->logger->log( 'gbp_approve', $status, $error ?: 'A WordPress administrator approved and sent a Google Business Profile action.' );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$this->clear_cache();
		return $this->draft( $id );
	}

	public function reject_draft( $id, $user_id ) {
		global $wpdb;

		if ( ! $user_id || ! user_can( $user_id, 'manage_options' ) ) {
			return new WP_Error( 'ikon_seo_gbp_approval', __( 'Only a WordPress administrator can reject a Business Profile draft.', 'ikon-seo' ) );
		}
		$draft = $this->draft( $id );
		if ( ! $draft || ! in_array( $draft['status'], array( 'draft', 'failed' ), true ) ) {
			return new WP_Error( 'ikon_seo_gbp_draft_status', __( 'Only a pending or failed draft can be rejected.', 'ikon-seo' ) );
		}
		$wpdb->update(
			$wpdb->prefix . 'ikon_seo_gbp_drafts',
			array(
				'status'      => 'rejected',
				'approved_by' => absint( $user_id ),
				'updated_at'  => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() )
		);
		$this->logger->log( 'gbp_reject', 'success', 'A Google Business Profile draft was rejected without sending.' );
		return $this->draft( $id );
	}

	public function clear_cache() {
		delete_transient( 'ikon_seo_gbp_access_token' );
		$account = sanitize_text_field( Ikon_SEO_Plugin::settings()['gbp_account'] );
		if ( $account ) {
			delete_transient( 'ikon_seo_gbp_locations_' . md5( $account ) );
		}
		foreach ( $this->local->locations( true ) as $location ) {
			$parent = $this->review_parent( $location['id'] );
			if ( ! is_wp_error( $parent ) ) {
				delete_transient( 'ikon_seo_gbp_reviews_' . md5( $parent ) );
			}
			if ( preg_match( '#^locations/([0-9]+)$#', $location['gbp_location_name'], $match ) ) {
				foreach ( array( 7, 28, 30, 60, 90 ) as $days ) {
					delete_transient( 'ikon_seo_gbp_performance_' . $match[1] . '_' . $days );
				}
				for ( $months = 1; $months <= 18; $months++ ) {
					delete_transient( 'ikon_seo_gbp_keywords_' . $match[1] . '_' . $months );
				}
			}
		}
	}

	public function daily_review_check() {
		if ( ! $this->status()['connected'] ) {
			return;
		}
		$state = get_option( 'ikon_seo_gbp_review_alerts', array() );
		$state = is_array( $state ) ? $state : array();
		foreach ( $this->local->locations() as $location ) {
			if ( ! $location['gbp_location_name'] ) {
				continue;
			}
			$result = $this->reviews( $location['id'], true );
			if ( is_wp_error( $result ) || empty( $result['items'] ) ) {
				continue;
			}
			$latest = sanitize_text_field( $result['items'][0]['update_time'] ?? '' );
			$known  = sanitize_text_field( $state[ $location['id'] ]['latest_time'] ?? '' );
			if ( ! $known ) {
				$state[ $location['id'] ] = array( 'latest_time' => $latest, 'new_count' => 0, 'checked_at' => current_time( 'mysql', true ) );
				continue;
			}
			$new_count = 0;
			foreach ( $result['items'] as $review ) {
				if ( ! empty( $review['update_time'] ) && strtotime( $review['update_time'] ) > strtotime( $known ) ) {
					$new_count++;
				}
			}
			$state[ $location['id'] ] = array(
				'latest_time' => $latest ?: $known,
				'new_count'   => absint( $state[ $location['id'] ]['new_count'] ?? 0 ) + $new_count,
				'checked_at'  => current_time( 'mysql', true ),
			);
		}
		update_option( 'ikon_seo_gbp_review_alerts', $state, false );
	}

	public function mark_reviews_seen( $local_id ) {
		$state = get_option( 'ikon_seo_gbp_review_alerts', array() );
		$state = is_array( $state ) ? $state : array();
		if ( isset( $state[ absint( $local_id ) ] ) ) {
			$state[ absint( $local_id ) ]['new_count'] = 0;
			update_option( 'ikon_seo_gbp_review_alerts', $state, false );
		}
	}

	private function review_alerts() {
		$state = get_option( 'ikon_seo_gbp_review_alerts', array() );
		$state = is_array( $state ) ? $state : array();
		$total = 0;
		$items = array();
		foreach ( $state as $location_id => $alert ) {
			$count = absint( $alert['new_count'] ?? 0 );
			if ( ! $count ) {
				continue;
			}
			$total += $count;
			$items[] = array(
				'location_id' => absint( $location_id ),
				'new_count'   => $count,
				'latest_time' => sanitize_text_field( $alert['latest_time'] ?? '' ),
				'checked_at'  => sanitize_text_field( $alert['checked_at'] ?? '' ),
			);
		}
		return array( 'total' => $total, 'items' => $items );
	}

	private function review_parent( $local_id ) {
		$location = $this->local->location( $local_id );
		if ( ! $location || ! preg_match( '#^accounts/[0-9]+$#', $location['gbp_account_name'] ) || ! preg_match( '#^locations/[0-9]+$#', $location['gbp_location_name'] ) ) {
			return new WP_Error( 'ikon_seo_gbp_link', __( 'Link this local record to a Google Business Profile location first.', 'ikon-seo' ) );
		}
		return $location['gbp_account_name'] . '/' . $location['gbp_location_name'];
	}

	private function post_body( array $draft ) {
		$action = is_array( $draft['action_data'] ) ? $draft['action_data'] : array();
		$topic  = strtoupper( sanitize_text_field( $action['topic_type'] ?? 'STANDARD' ) );
		$body   = array(
			'languageCode' => sanitize_text_field( $action['language_code'] ?? Ikon_SEO_Plugin::settings()['default_language'] ),
			'summary'      => $draft['content'],
			'topicType'    => $topic,
		);
		if ( ! empty( $action['call_to_action'] ) && ! empty( $action['call_to_action_url'] ) ) {
			$allowed = array( 'book', 'order', 'shop', 'learn_more', 'sign_up', 'call' );
			$type    = strtolower( sanitize_key( $action['call_to_action'] ) );
			if ( in_array( $type, $allowed, true ) ) {
				$body['callToAction'] = array(
					'actionType' => strtoupper( $type ),
					'url'        => esc_url_raw( $action['call_to_action_url'] ),
				);
			}
		}
		if ( in_array( $topic, array( 'EVENT', 'OFFER' ), true ) ) {
			$start = $this->date_time( $action['start_time'] ?? '' );
			$end   = $this->date_time( $action['end_time'] ?? '' );
			if ( ! $start || ! $end || empty( $action['event_title'] ) ) {
				return new WP_Error( 'ikon_seo_gbp_event', __( 'Event and offer posts require a title, valid start time and valid end time.', 'ikon-seo' ) );
			}
			$body['event'] = array(
				'title'    => sanitize_text_field( $action['event_title'] ),
				'schedule' => array( 'startDate' => $start['date'], 'startTime' => $start['time'], 'endDate' => $end['date'], 'endTime' => $end['time'] ),
			);
		}
		if ( 'OFFER' === $topic ) {
			$body['offer'] = array(
				'couponCode'      => sanitize_text_field( $action['coupon_code'] ?? '' ),
				'redeemOnlineUrl' => esc_url_raw( $action['redeem_online_url'] ?? '' ),
				'termsConditions' => sanitize_textarea_field( $action['terms_conditions'] ?? '' ),
			);
		}
		return $body;
	}

	private function request( $method, $url, array $body = array() ) {
		$token = $this->access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$args = array(
			'timeout' => 25,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept'        => 'application/json',
			),
		);
		if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}
		$response = wp_remote_request( $url, array_merge( $args, array( 'method' => $method ) ) );
		$data     = $this->decode_response( $response, 'Google Business Profile' );
		if ( is_wp_error( $data ) ) {
			$this->remember_error( $data->get_error_message() );
		}
		return $data;
	}

	private function access_token() {
		$cached = get_transient( 'ikon_seo_gbp_access_token' );
		if ( is_string( $cached ) && $cached ) {
			return $cached;
		}
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['gbp_refresh_token'] ) || empty( $settings['gbp_client_secret'] ) ) {
			return new WP_Error( 'ikon_seo_gbp_disconnected', __( 'Google Business Profile is not connected.', 'ikon-seo' ) );
		}
		$refresh = $this->crypto->decrypt( $settings['gbp_refresh_token'] );
		$secret  = $this->crypto->decrypt( $settings['gbp_client_secret'] );
		if ( is_wp_error( $refresh ) ) {
			return $refresh;
		}
		if ( is_wp_error( $secret ) ) {
			return $secret;
		}
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'client_id'     => $settings['gbp_client_id'],
					'client_secret' => $secret,
					'refresh_token' => $refresh,
					'grant_type'    => 'refresh_token',
				),
			)
		);
		$data = $this->decode_response( $response, 'Google Business Profile token refresh' );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( empty( $data['access_token'] ) ) {
			return new WP_Error( 'ikon_seo_gbp_access_token', __( 'Google did not return an access token.', 'ikon-seo' ) );
		}
		$this->store_access_token( $data );
		return sanitize_text_field( $data['access_token'] );
	}

	private function store_access_token( array $data ) {
		if ( empty( $data['access_token'] ) ) {
			return;
		}
		set_transient(
			'ikon_seo_gbp_access_token',
			sanitize_text_field( $data['access_token'] ),
			max( 60, absint( $data['expires_in'] ?? 3600 ) - 120 )
		);
	}

	private function decode_response( $response, $label ) {
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ikon_seo_gbp_transport', $label . ': ' . $response->get_error_message() );
		}
		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			$message = sanitize_text_field( $data['error']['message'] ?? $data['error_description'] ?? ( $label . ' request failed.' ) );
			return new WP_Error( 'ikon_seo_gbp_api', $message, array( 'status' => $status ) );
		}
		return is_array( $data ) ? $data : array();
	}

	private function public_draft( array $row ) {
		return array(
			'id'              => absint( $row['id'] ?? 0 ),
			'location_id'     => absint( $row['location_id'] ?? 0 ),
			'draft_type'      => sanitize_key( $row['draft_type'] ?? '' ),
			'remote_resource' => sanitize_text_field( $row['remote_resource'] ?? '' ),
			'content'         => sanitize_textarea_field( $row['content'] ?? '' ),
			'action_data'     => is_array( json_decode( (string) ( $row['action_data'] ?? '' ), true ) ) ? json_decode( (string) $row['action_data'], true ) : array(),
			'status'          => sanitize_key( $row['status'] ?? '' ),
			'created_by'      => absint( $row['created_by'] ?? 0 ),
			'approved_by'     => absint( $row['approved_by'] ?? 0 ),
			'created_at'      => sanitize_text_field( $row['created_at'] ?? '' ),
			'updated_at'      => sanitize_text_field( $row['updated_at'] ?? '' ),
			'sent_at'         => sanitize_text_field( $row['sent_at'] ?? '' ),
			'last_error'      => sanitize_textarea_field( $row['last_error'] ?? '' ),
			'approval_note'   => 'Sending is available only from the WordPress administrator screen after reviewing this exact content.',
		);
	}

	private function compare_value( array &$checks, $id, $local, $remote, $optional ) {
		$local  = trim( strtolower( (string) $local ) );
		$remote = trim( strtolower( (string) $remote ) );
		$empty  = ! $local || ! $remote;
		$match  = ! $empty && hash_equals( $local, $remote );
		$status = $match ? 'pass' : ( $optional && $empty ? 'warning' : 'fail' );
		$checks[] = array(
			'id'     => sanitize_key( $id ),
			'status' => $status,
			'local'  => sanitize_text_field( $local ),
			'google' => sanitize_text_field( $remote ),
			'message'=> $match ? 'Website and Google Business Profile values match.' : ( $empty ? 'A value is missing on the website or Google Business Profile.' : 'Website and Google Business Profile values differ.' ),
		);
	}

	private function compare_set( array &$checks, $id, array $local, array $remote, $optional ) {
		if ( 'opening_hours' === $id ) {
			$local  = $this->normalize_hours_lines( $local );
			$remote = $this->normalize_hours_lines( $remote );
		}
		$clean = function( array $items ) {
			$items = array_values( array_unique( array_filter( array_map( function( $item ) {
				return strtolower( trim( sanitize_text_field( $item ) ) );
			}, $items ) ) ) );
			sort( $items, SORT_STRING );
			return $items;
		};
		$local  = $clean( $local );
		$remote = $clean( $remote );
		$empty  = ! $local || ! $remote;
		$match  = ! $empty && $local === $remote;
		$status = $match ? 'pass' : ( $optional && $empty ? 'warning' : 'fail' );
		$checks[] = array(
			'id'     => sanitize_key( $id ),
			'status' => $status,
			'local'  => implode( ' | ', $local ),
			'google' => implode( ' | ', $remote ),
			'message'=> $match ? 'Website and Google Business Profile values match.' : ( $empty ? 'A value is missing on the website or Google Business Profile.' : 'Website and Google Business Profile values differ.' ),
		);
	}

	private function clean_remote_address( array $address ) {
		return array(
			'language_code'       => sanitize_text_field( $address['languageCode'] ?? '' ),
			'region_code'         => sanitize_text_field( $address['regionCode'] ?? '' ),
			'postal_code'         => sanitize_text_field( $address['postalCode'] ?? '' ),
			'administrative_area' => sanitize_text_field( $address['administrativeArea'] ?? '' ),
			'locality'            => sanitize_text_field( $address['locality'] ?? '' ),
			'sublocality'         => sanitize_text_field( $address['sublocality'] ?? '' ),
			'address_lines'       => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $address['addressLines'] ?? array() ) ) ) ),
		);
	}

	private function clean_regular_hours( array $periods ) {
		$days = array( 'MONDAY' => 'Mo', 'TUESDAY' => 'Tu', 'WEDNESDAY' => 'We', 'THURSDAY' => 'Th', 'FRIDAY' => 'Fr', 'SATURDAY' => 'Sa', 'SUNDAY' => 'Su' );
		$out  = array();
		foreach ( $periods as $period ) {
			$day   = $days[ strtoupper( sanitize_text_field( $period['openDay'] ?? '' ) ) ] ?? '';
			$open  = $this->time_of_day( (array) ( $period['openTime'] ?? array() ) );
			$close = $this->time_of_day( (array) ( $period['closeTime'] ?? array() ) );
			if ( $day && $open && $close ) {
				$out[] = $day . ' ' . $open . '-' . $close;
			}
		}
		return $out;
	}

	private function clean_service_areas( array $service_area ) {
		$out = array();
		foreach ( (array) ( $service_area['places']['placeInfos'] ?? array() ) as $place ) {
			$name = sanitize_text_field( $place['placeName'] ?? '' );
			if ( $name ) {
				$out[] = $name;
			}
		}
		$region = sanitize_text_field( $service_area['regionCode'] ?? '' );
		if ( $region ) {
			$out[] = strtoupper( $region );
		}
		return array_values( array_unique( $out ) );
	}

	private function clean_service_items( array $items ) {
		$out = array();
		foreach ( $items as $item ) {
			$name = sanitize_text_field( $item['freeFormServiceItem']['label']['displayName'] ?? $item['structuredServiceItem']['serviceTypeId'] ?? '' );
			if ( $name ) {
				$out[] = $name;
			}
		}
		return array_values( array_unique( $out ) );
	}

	private function normalize_hours_lines( array $lines ) {
		$map = array( 'mo', 'tu', 'we', 'th', 'fr', 'sa', 'su' );
		$out = array();
		foreach ( $lines as $line ) {
			if ( ! preg_match( '/^([A-Za-z]{2})(?:-([A-Za-z]{2}))?\s+([0-2]\d:[0-5]\d)-([0-2]\d:[0-5]\d)$/', trim( $line ), $match ) ) {
				continue;
			}
			$start = array_search( strtolower( $match[1] ), $map, true );
			$end   = empty( $match[2] ) ? $start : array_search( strtolower( $match[2] ), $map, true );
			if ( false === $start || false === $end || $end < $start ) {
				continue;
			}
			for ( $index = $start; $index <= $end; $index++ ) {
				$out[] = ucfirst( $map[ $index ] ) . ' ' . $match[3] . '-' . $match[4];
			}
		}
		return $out;
	}

	private function time_of_day( array $time ) {
		if ( ! isset( $time['hours'] ) ) {
			return '';
		}
		return sprintf( '%02d:%02d', absint( $time['hours'] ), absint( $time['minutes'] ?? 0 ) );
	}

	private function normalize_phone( $value ) {
		return preg_replace( '/[^0-9]+/', '', (string) $value );
	}

	private function canonical_url( $value ) {
		$url = esc_url_raw( $value );
		if ( ! $url ) {
			return '';
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return '';
		}
		return strtolower( $parts['host'] . untrailingslashit( $parts['path'] ?? '/' ) );
	}

	private function same_site_url( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		$url   = esc_url_raw( $value );
		$host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$local = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( ! $url || ! $host || ! $local || ! hash_equals( $local, $host ) ) {
			return new WP_Error( 'ikon_seo_gbp_url', __( 'Google Post and offer links must use this WordPress website.', 'ikon-seo' ) );
		}
		return $url;
	}

	private function date_time( $value ) {
		$value = sanitize_text_field( $value );
		$date  = DateTime::createFromFormat( 'Y-m-d\TH:i', $value );
		if ( $date && $date->format( 'Y-m-d\TH:i' ) === $value ) {
			return array(
				'date' => array( 'year' => (int) $date->format( 'Y' ), 'month' => (int) $date->format( 'n' ), 'day' => (int) $date->format( 'j' ) ),
				'time' => array( 'hours' => (int) $date->format( 'G' ), 'minutes' => (int) $date->format( 'i' ), 'seconds' => 0, 'nanos' => 0 ),
			);
		}
		$timestamp = strtotime( $value );
		if ( ! $timestamp ) {
			return null;
		}
		return array(
			'date' => array( 'year' => (int) gmdate( 'Y', $timestamp ), 'month' => (int) gmdate( 'n', $timestamp ), 'day' => (int) gmdate( 'j', $timestamp ) ),
			'time' => array( 'hours' => (int) gmdate( 'G', $timestamp ), 'minutes' => (int) gmdate( 'i', $timestamp ), 'seconds' => 0, 'nanos' => 0 ),
		);
	}

	private function callback_url() {
		return admin_url( 'admin-post.php?action=ikon_seo_gbp_callback' );
	}

	private function base64url( $value ) {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	private function remember_error( $message ) {
		$settings                   = Ikon_SEO_Plugin::settings();
		$settings['gbp_last_error'] = sanitize_text_field( $message );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
	}
}
