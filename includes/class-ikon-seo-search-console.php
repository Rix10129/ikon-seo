<?php

defined( 'ABSPATH' ) || exit;

/**
 * Read-only Google Search Console integration.
 *
 * Ikon SEO deliberately requests the webmasters.readonly scope. It does not use
 * the Indexing API, which Google limits to eligible job and livestream pages.
 */
class Ikon_SEO_Search_Console {
	const OAUTH_SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';
	const CACHE_KEY   = 'ikon_seo_gsc_performance';

	private $crypto;
	private $logger;

	public function __construct( Ikon_SEO_Crypto $crypto, Ikon_SEO_Logger $logger ) {
		$this->crypto = $crypto;
		$this->logger = $logger;
	}

	public function status() {
		$settings = Ikon_SEO_Plugin::settings();
		$cached   = get_transient( self::CACHE_KEY );

		return array(
			'configured'    => ! empty( $settings['gsc_client_id'] ) && ! empty( $settings['gsc_client_secret'] ),
			'connected'     => ! empty( $settings['gsc_refresh_token'] ),
			'property'      => sanitize_text_field( $settings['gsc_property'] ),
			'property_type' => empty( $settings['gsc_property'] ) ? '' : ( 0 === strpos( (string) $settings['gsc_property'], 'sc-domain:' ) ? 'domain' : 'url_prefix' ),
			'read_only'     => true,
			'scope'         => self::OAUTH_SCOPE,
			'callback_url'  => $this->callback_url(),
			'last_sync'     => is_array( $cached ) ? sanitize_text_field( $cached['fetched_at'] ?? '' ) : '',
			'last_error'    => sanitize_text_field( $settings['gsc_last_error'] ),
			'crypto_ready'  => $this->crypto->available(),
		);
	}

	public function save_credentials( $client_id, $client_secret ) {
		$client_id     = sanitize_text_field( $client_id );
		$client_secret = trim( (string) $client_secret );

		if ( ! $client_id || ! preg_match( '/\.apps\.googleusercontent\.com$/', $client_id ) ) {
			return new WP_Error( 'ikon_seo_gsc_client_id', __( 'Enter a valid Google OAuth web application client ID.', 'ikon-seo' ) );
		}

		$settings = Ikon_SEO_Plugin::settings();
		if ( '' !== $client_secret ) {
			$encrypted = $this->crypto->encrypt( $client_secret );
			if ( is_wp_error( $encrypted ) ) {
				return $encrypted;
			}
			$settings['gsc_refresh_token'] = '';
			$settings['gsc_property']      = '';
			$settings['gsc_client_secret'] = $encrypted;
		} elseif ( empty( $settings['gsc_client_secret'] ) ) {
			return new WP_Error( 'ikon_seo_gsc_client_secret', __( 'Enter the Google OAuth client secret.', 'ikon-seo' ) );
		} elseif ( $client_id !== $settings['gsc_client_id'] ) {
			return new WP_Error( 'ikon_seo_gsc_client_secret', __( 'Enter the matching client secret when changing the Google OAuth client ID.', 'ikon-seo' ) );
		}

		$settings['gsc_client_id']  = $client_id;
		$settings['gsc_last_error'] = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->clear_cache();

		return $this->status();
	}

	public function authorization_url( $user_id ) {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['gsc_client_id'] ) || empty( $settings['gsc_client_secret'] ) ) {
			return new WP_Error( 'ikon_seo_gsc_credentials', __( 'Save the Google OAuth credentials before connecting.', 'ikon-seo' ) );
		}

		try {
			$state    = bin2hex( random_bytes( 24 ) );
			$verifier = $this->base64url( random_bytes( 48 ) );
		} catch ( Exception $exception ) {
			return new WP_Error( 'ikon_seo_gsc_state', __( 'A secure OAuth state could not be generated.', 'ikon-seo' ) );
		}

		set_transient(
			'ikon_seo_gsc_state_' . hash( 'sha256', $state ),
			array(
				'user_id'  => absint( $user_id ),
				'verifier' => $verifier,
			),
			10 * MINUTE_IN_SECONDS
		);

		return add_query_arg(
			array(
				'client_id'             => $settings['gsc_client_id'],
				'redirect_uri'          => $this->callback_url(),
				'response_type'         => 'code',
				'scope'                 => self::OAUTH_SCOPE,
				'access_type'           => 'offline',
				'prompt'                => 'consent',
				'include_granted_scopes'=> 'true',
				'state'                 => $state,
				'code_challenge'        => $this->base64url( hash( 'sha256', $verifier, true ) ),
				'code_challenge_method' => 'S256',
			),
			'https://accounts.google.com/o/oauth2/v2/auth'
		);
	}

	public function complete_authorization( $state, $code, $user_id ) {
		$state_key = 'ikon_seo_gsc_state_' . hash( 'sha256', (string) $state );
		$pending   = get_transient( $state_key );
		delete_transient( $state_key );

		if ( ! is_array( $pending ) || absint( $pending['user_id'] ?? 0 ) !== absint( $user_id ) || empty( $pending['verifier'] ) ) {
			return new WP_Error( 'ikon_seo_gsc_state', __( 'The Google authorization request expired or did not match this administrator.', 'ikon-seo' ) );
		}
		if ( ! $code ) {
			return new WP_Error( 'ikon_seo_gsc_code', __( 'Google did not return an authorization code.', 'ikon-seo' ) );
		}

		$settings      = Ikon_SEO_Plugin::settings();
		$client_secret = $this->crypto->decrypt( $settings['gsc_client_secret'] );
		if ( is_wp_error( $client_secret ) ) {
			return $client_secret;
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'code'          => sanitize_text_field( $code ),
					'client_id'     => $settings['gsc_client_id'],
					'client_secret' => $client_secret,
					'redirect_uri'  => $this->callback_url(),
					'grant_type'    => 'authorization_code',
					'code_verifier' => $pending['verifier'],
				),
			)
		);
		$data = $this->decode_response( $response, 'Google authorization' );
		if ( is_wp_error( $data ) ) {
			$this->remember_error( $data->get_error_message() );
			return $data;
		}
		if ( empty( $data['refresh_token'] ) ) {
			return new WP_Error( 'ikon_seo_gsc_refresh_token', __( 'Google did not return an offline refresh token. Reconnect and approve access again.', 'ikon-seo' ) );
		}

		$encrypted = $this->crypto->encrypt( $data['refresh_token'] );
		if ( is_wp_error( $encrypted ) ) {
			return $encrypted;
		}

		$settings['gsc_refresh_token'] = $encrypted;
		$settings['gsc_last_error']    = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->store_access_token( $data );
		$this->clear_cache();
		$this->logger->log( 'gsc_connect', 'success', 'Google Search Console connected with read-only access.' );

		return $this->properties();
	}

	public function disconnect() {
		$settings                      = Ikon_SEO_Plugin::settings();
		$settings['gsc_refresh_token'] = '';
		$settings['gsc_property']      = '';
		$settings['gsc_last_error']    = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		delete_transient( 'ikon_seo_gsc_access_token' );
		$this->clear_cache();
		$this->logger->log( 'gsc_disconnect', 'success', 'Google Search Console connection removed.' );
	}

	public function properties() {
		$data = $this->request( 'GET', 'https://www.googleapis.com/webmasters/v3/sites' );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$items = array();
		foreach ( (array) ( $data['siteEntry'] ?? array() ) as $entry ) {
			if ( empty( $entry['siteUrl'] )
				|| 'siteUnverifiedUser' === ( $entry['permissionLevel'] ?? '' )
				|| ! $this->property_matches_site( $entry['siteUrl'] )
			) {
				continue;
			}
			$items[] = array(
				'site_url'         => sanitize_text_field( $entry['siteUrl'] ),
				'permission_level' => sanitize_key( $entry['permissionLevel'] ?? '' ),
			);
		}

		return array( 'items' => $items );
	}

	public function select_property( $property ) {
		$property   = sanitize_text_field( $property );
		$properties = $this->properties();
		if ( is_wp_error( $properties ) ) {
			return $properties;
		}

		$allowed = wp_list_pluck( $properties['items'], 'site_url' );
		if ( ! in_array( $property, $allowed, true ) ) {
			return new WP_Error( 'ikon_seo_gsc_property', __( 'Select a Search Console property available to the connected Google account.', 'ikon-seo' ) );
		}

		$settings                 = Ikon_SEO_Plugin::settings();
		$settings['gsc_property'] = $property;
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->clear_cache();
		return $this->status();
	}

	public function performance( $days = 28, $refresh = false ) {
		$days = max( 7, min( 90, absint( $days ) ) );
		if ( ! $refresh ) {
			$cached = get_transient( self::CACHE_KEY . '_' . $days );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$property = Ikon_SEO_Plugin::settings()['gsc_property'];
		if ( ! $property ) {
			return new WP_Error( 'ikon_seo_gsc_property', __( 'Select a Google Search Console property first.', 'ikon-seo' ) );
		}

		$end            = gmdate( 'Y-m-d', strtotime( '-3 days' ) );
		$start          = gmdate( 'Y-m-d', strtotime( $end . ' -' . ( $days - 1 ) . ' days' ) );
		$previous_end   = gmdate( 'Y-m-d', strtotime( $start . ' -1 day' ) );
		$previous_start = gmdate( 'Y-m-d', strtotime( $previous_end . ' -' . ( $days - 1 ) . ' days' ) );

		$current  = $this->analytics_query( $property, $start, $end );
		$previous = $this->analytics_query( $property, $previous_start, $previous_end );
		$queries  = $this->analytics_query( $property, $start, $end, array( 'query' ), 25 );
		$pages    = $this->analytics_query( $property, $start, $end, array( 'page' ), 1000 );
		$old_pages= $this->analytics_query( $property, $previous_start, $previous_end, array( 'page' ), 1000 );

		foreach ( array( $current, $previous, $queries, $pages, $old_pages ) as $response ) {
			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		$current_totals  = $this->totals( $current );
		$previous_totals = $this->totals( $previous );
		$page_rows       = $this->dimension_rows( $pages, 'page' );
		$previous_map    = array();
		foreach ( $this->dimension_rows( $old_pages, 'page' ) as $row ) {
			$previous_map[ $row['key'] ] = $row;
		}
		foreach ( $page_rows as &$row ) {
			$old                    = $previous_map[ $row['key'] ] ?? array();
			$row['clicks_change']   = $this->percent_change( $row['clicks'], $old['clicks'] ?? 0 );
			$row['impressions_change'] = $this->percent_change( $row['impressions'], $old['impressions'] ?? 0 );
			$row['previous_clicks'] = (float) ( $old['clicks'] ?? 0 );
			$row['previous_impressions'] = (float) ( $old['impressions'] ?? 0 );
		}
		unset( $row );

		$result = array(
			'property'        => $property,
			'period'          => array( 'start' => $start, 'end' => $end, 'days' => $days ),
			'previous_period' => array( 'start' => $previous_start, 'end' => $previous_end ),
			'totals'          => $current_totals,
			'previous_totals' => $previous_totals,
			'changes'         => array(
				'clicks'      => $this->percent_change( $current_totals['clicks'], $previous_totals['clicks'] ),
				'impressions' => $this->percent_change( $current_totals['impressions'], $previous_totals['impressions'] ),
				'ctr'         => $this->percent_change( $current_totals['ctr'], $previous_totals['ctr'] ),
				'position'    => round( $previous_totals['position'] - $current_totals['position'], 2 ),
			),
			'top_queries'     => $this->dimension_rows( $queries, 'query' ),
			'top_pages'       => $page_rows,
			'data_note'       => 'Search Console may omit anonymized and low-volume query rows. Dates end three days ago to reduce partial-data effects.',
			'fetched_at'      => current_time( 'mysql', true ),
		);

		set_transient( self::CACHE_KEY . '_' . $days, $result, 6 * HOUR_IN_SECONDS );
		set_transient( self::CACHE_KEY, $result, 6 * HOUR_IN_SECONDS );
		$this->remember_error( '' );
		return $result;
	}


	/**
	 * Retrieve paginated Search Analytics rows for persistent intelligence.
	 *
	 * Google may still omit anonymized or low-volume queries. This method uses
	 * final data and stops at the configured local row limit.
	 */
	public function detailed_rows( $start, $end, array $dimensions = array( 'query', 'page' ), $max_rows = 50000 ) {
		$property = sanitize_text_field( Ikon_SEO_Plugin::settings()['gsc_property'] ?? '' );
		if ( ! $property ) {
			return new WP_Error( 'ikon_seo_gsc_property', __( 'Select a Google Search Console property first.', 'ikon-seo' ) );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $start ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $end ) ) {
			return new WP_Error( 'ikon_seo_gsc_dates', __( 'Search Console dates must use YYYY-MM-DD format.', 'ikon-seo' ) );
		}
		$allowed = array( 'query', 'page', 'country', 'device', 'date', 'searchAppearance' );
		$dimensions = array_values( array_intersect( array_map( 'sanitize_key', $dimensions ), $allowed ) );
		if ( ! $dimensions ) {
			return new WP_Error( 'ikon_seo_gsc_dimensions', __( 'Select at least one supported Search Console dimension.', 'ikon-seo' ) );
		}
		$max_rows  = max( 1, min( 200000, absint( $max_rows ) ) );
		$page_size = min( 25000, $max_rows );
		$start_row = 0;
		$rows      = array();
		$truncated = false;
		do {
			$response = $this->analytics_query( $property, $start, $end, $dimensions, $page_size, $start_row );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$batch = (array) ( $response['rows'] ?? array() );
			foreach ( $batch as $row ) {
				$rows[] = $row;
				if ( count( $rows ) >= $max_rows ) {
					$truncated = true;
					break 2;
				}
			}
			$start_row += count( $batch );
		} while ( count( $batch ) === $page_size );

		return array(
			'property'   => $property,
			'period'     => array( 'start' => $start, 'end' => $end ),
			'dimensions' => $dimensions,
			'rows'       => $rows,
			'row_count'  => count( $rows ),
			'truncated'  => $truncated,
			'data_note'  => 'Search Console may omit anonymized and low-volume rows even when pagination completes.',
		);
	}

	public function inspect_url( $url ) {
		$url      = esc_url_raw( $url );
		$property = Ikon_SEO_Plugin::settings()['gsc_property'];
		if ( ! $property || ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'ikon_seo_gsc_inspect_url', __( 'A valid site URL and selected Search Console property are required.', 'ikon-seo' ) );
		}
		if ( ! $this->url_belongs_to_site( $url ) ) {
			return new WP_Error( 'ikon_seo_gsc_inspect_host', __( 'Only URLs on this WordPress website can be inspected.', 'ikon-seo' ) );
		}

		$data = $this->request(
			'POST',
			'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect',
			array(
				'inspectionUrl' => $url,
				'siteUrl'       => $property,
				'languageCode'  => sanitize_text_field( Ikon_SEO_Plugin::settings()['default_language'] ),
			)
		);
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$inspection = (array) ( $data['inspectionResult'] ?? array() );
		$result     = (array) ( $inspection['indexStatusResult'] ?? array() );
		$mobile     = (array) ( $inspection['mobileUsabilityResult'] ?? array() );
		$rich       = (array) ( $inspection['richResultsResult'] ?? array() );
		$rich_items = array();
		foreach ( array_slice( (array) ( $rich['detectedItems'] ?? array() ), 0, 50 ) as $detected ) {
			$rich_items[] = array(
				'type'       => sanitize_text_field( $detected['richResultType'] ?? '' ),
				'item_count' => count( (array) ( $detected['items'] ?? array() ) ),
			);
		}

		return array(
			'url'                       => $url,
			'verdict'                   => sanitize_key( $result['verdict'] ?? '' ),
			'coverage_state'            => sanitize_text_field( $result['coverageState'] ?? '' ),
			'indexing_state'            => sanitize_key( $result['indexingState'] ?? '' ),
			'page_fetch_state'          => sanitize_key( $result['pageFetchState'] ?? '' ),
			'robots_txt_state'          => sanitize_key( $result['robotsTxtState'] ?? '' ),
			'last_crawl_time'           => sanitize_text_field( $result['lastCrawlTime'] ?? '' ),
			'google_canonical'          => esc_url_raw( $result['googleCanonical'] ?? '' ),
			'user_canonical'            => esc_url_raw( $result['userCanonical'] ?? '' ),
			'referring_urls'            => array_values( array_filter( array_map( 'esc_url_raw', (array) ( $result['referringUrls'] ?? array() ) ) ) ),
			'mobile_usability_verdict'  => sanitize_key( $mobile['verdict'] ?? '' ),
			'rich_results_verdict'      => sanitize_key( $rich['verdict'] ?? '' ),
			'rich_items'                => $rich_items,
			'inspection_result_link'    => esc_url_raw( $inspection['inspectionResultLink'] ?? '' ),
			'inspection_scope'          => 'indexed_version_only',
			'live_test_available'       => false,
			'submits_for_indexing'      => false,
		);
	}

	public function sitemaps() {
		$property = Ikon_SEO_Plugin::settings()['gsc_property'];
		if ( ! $property ) {
			return new WP_Error( 'ikon_seo_gsc_property', __( 'Select a Google Search Console property first.', 'ikon-seo' ) );
		}
		$url  = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode( $property ) . '/sitemaps';
		$data = $this->request( 'GET', $url );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$items = array();
		foreach ( (array) ( $data['sitemap'] ?? array() ) as $sitemap ) {
			$items[] = array(
				'path'          => esc_url_raw( $sitemap['path'] ?? '' ),
				'last_submitted'=> sanitize_text_field( $sitemap['lastSubmitted'] ?? '' ),
				'is_pending'    => ! empty( $sitemap['isPending'] ),
				'is_sitemaps_index' => ! empty( $sitemap['isSitemapsIndex'] ),
				'errors'        => absint( $sitemap['errors'] ?? 0 ),
				'warnings'      => absint( $sitemap['warnings'] ?? 0 ),
			);
		}
		return array( 'items' => $items );
	}

	public function clear_cache() {
		delete_transient( self::CACHE_KEY );
		if ( class_exists( 'Ikon_SEO_Search_Intelligence' ) ) {
			delete_transient( Ikon_SEO_Search_Intelligence::CACHE_KEY );
		}
		foreach ( array( 7, 28, 30, 60, 90 ) as $days ) {
			delete_transient( self::CACHE_KEY . '_' . $days );
		}
	}

	private function analytics_query( $property, $start, $end, array $dimensions = array(), $row_limit = 1, $start_row = 0 ) {
		$body = array(
			'startDate' => $start,
			'endDate'   => $end,
			'type'      => 'web',
			'dataState' => 'final',
			'rowLimit'  => max( 1, min( 25000, absint( $row_limit ) ) ),
			'startRow'  => max( 0, absint( $start_row ) ),
		);
		if ( $dimensions ) {
			$body['dimensions'] = array_values( $dimensions );
		}
		return $this->request(
			'POST',
			'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode( $property ) . '/searchAnalytics/query',
			$body
		);
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
		if ( 'POST' === $method ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
			$response                        = wp_remote_post( $url, $args );
		} else {
			$response = wp_remote_get( $url, $args );
		}

		$data = $this->decode_response( $response, 'Search Console' );
		if ( is_wp_error( $data ) ) {
			$this->remember_error( $data->get_error_message() );
		}
		return $data;
	}

	private function access_token() {
		$cached = get_transient( 'ikon_seo_gsc_access_token' );
		if ( is_string( $cached ) && $cached ) {
			return $cached;
		}

		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['gsc_refresh_token'] ) || empty( $settings['gsc_client_secret'] ) ) {
			return new WP_Error( 'ikon_seo_gsc_disconnected', __( 'Google Search Console is not connected.', 'ikon-seo' ) );
		}

		$refresh_token = $this->crypto->decrypt( $settings['gsc_refresh_token'] );
		$client_secret = $this->crypto->decrypt( $settings['gsc_client_secret'] );
		if ( is_wp_error( $refresh_token ) ) {
			return $refresh_token;
		}
		if ( is_wp_error( $client_secret ) ) {
			return $client_secret;
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'client_id'     => $settings['gsc_client_id'],
					'client_secret' => $client_secret,
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
				),
			)
		);
		$data = $this->decode_response( $response, 'Google token refresh' );
		if ( is_wp_error( $data ) ) {
			$this->remember_error( $data->get_error_message() );
			return $data;
		}
		if ( empty( $data['access_token'] ) ) {
			return new WP_Error( 'ikon_seo_gsc_access_token', __( 'Google did not return an access token.', 'ikon-seo' ) );
		}
		$this->store_access_token( $data );
		return sanitize_text_field( $data['access_token'] );
	}

	private function store_access_token( array $data ) {
		if ( empty( $data['access_token'] ) ) {
			return;
		}
		$ttl = max( 60, absint( $data['expires_in'] ?? 3600 ) - 120 );
		set_transient( 'ikon_seo_gsc_access_token', sanitize_text_field( $data['access_token'] ), $ttl );
	}

	private function decode_response( $response, $label ) {
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ikon_seo_gsc_transport', $label . ': ' . $response->get_error_message() );
		}
		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			$message = sanitize_text_field( $data['error']['message'] ?? $data['error_description'] ?? ( $label . ' request failed.' ) );
			return new WP_Error( 'ikon_seo_gsc_api', $message, array( 'status' => $status ) );
		}
		return is_array( $data ) ? $data : array();
	}

	private function totals( array $response ) {
		$row = (array) ( $response['rows'][0] ?? array() );
		return array(
			'clicks'      => (float) ( $row['clicks'] ?? 0 ),
			'impressions' => (float) ( $row['impressions'] ?? 0 ),
			'ctr'         => (float) ( $row['ctr'] ?? 0 ),
			'position'    => (float) ( $row['position'] ?? 0 ),
		);
	}

	private function dimension_rows( array $response, $dimension ) {
		$rows = array();
		foreach ( (array) ( $response['rows'] ?? array() ) as $row ) {
			$rows[] = array(
				'dimension'   => $dimension,
				'key'         => sanitize_text_field( $row['keys'][0] ?? '' ),
				'clicks'      => (float) ( $row['clicks'] ?? 0 ),
				'impressions' => (float) ( $row['impressions'] ?? 0 ),
				'ctr'         => (float) ( $row['ctr'] ?? 0 ),
				'position'    => (float) ( $row['position'] ?? 0 ),
			);
		}
		return $rows;
	}

	private function percent_change( $current, $previous ) {
		$current  = (float) $current;
		$previous = (float) $previous;
		if ( 0.0 === $previous ) {
			return 0.0 === $current ? 0 : null;
		}
		return round( ( ( $current - $previous ) / $previous ) * 100, 2 );
	}

	private function url_belongs_to_site( $url ) {
		$url_host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		return $url_host && $site_host && hash_equals( $site_host, $url_host );
	}

	private function property_matches_site( $property ) {
		$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( ! $site_host ) {
			return false;
		}
		if ( 0 === strpos( (string) $property, 'sc-domain:' ) ) {
			$domain = strtolower( trim( substr( (string) $property, 10 ), ". \t\n\r\0\x0B" ) );
			return $domain && ( $site_host === $domain || substr( $site_host, -1 - strlen( $domain ) ) === '.' . $domain );
		}
		$property_host = strtolower( (string) wp_parse_url( $property, PHP_URL_HOST ) );
		return $property_host && hash_equals( $site_host, $property_host );
	}

	private function callback_url() {
		return admin_url( 'admin-post.php?action=ikon_seo_gsc_callback' );
	}

	private function base64url( $value ) {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	private function remember_error( $message ) {
		$settings                   = Ikon_SEO_Plugin::settings();
		$settings['gsc_last_error'] = sanitize_text_field( $message );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
	}
}
