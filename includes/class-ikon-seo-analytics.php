<?php

defined( 'ABSPATH' ) || exit;

/**
 * Read-only Google Analytics 4 integration using Google's OAuth and Data APIs.
 */
final class Ikon_SEO_Analytics {
	const OAUTH_SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';
	const CACHE_KEY   = 'ikon_seo_ga4_report_v1';

	private $crypto;
	private $logger;

	public function __construct( Ikon_SEO_Crypto $crypto, Ikon_SEO_Logger $logger ) {
		$this->crypto = $crypto;
		$this->logger = $logger;
	}

	public function status() {
		$settings = Ikon_SEO_Plugin::settings();
		return array(
			'configured'  => ! empty( $settings['ga_client_id'] ) && ! empty( $settings['ga_client_secret'] ),
			'connected'   => ! empty( $settings['ga_refresh_token'] ),
			'property'    => sanitize_text_field( $settings['ga_property'] ?? '' ),
			'last_error'  => sanitize_text_field( $settings['ga_last_error'] ?? '' ),
			'last_sync'   => sanitize_text_field( get_option( 'ikon_seo_ga_last_sync', '' ) ),
			'callback_url'=> $this->callback_url(),
			'read_only'   => true,
		);
	}

	public function save_credentials( $client_id, $client_secret, $use_search_console = false ) {
		$settings = Ikon_SEO_Plugin::settings();
		$client_id = sanitize_text_field( $client_id );
		$client_secret = trim( (string) $client_secret );

		if ( $use_search_console ) {
			if ( empty( $settings['gsc_client_id'] ) || empty( $settings['gsc_client_secret'] ) ) {
				return new WP_Error( 'ikon_seo_ga_gsc_credentials', __( 'Save Search Console OAuth credentials before reusing them for Analytics.', 'ikon-seo' ) );
			}
			$client_id = sanitize_text_field( $settings['gsc_client_id'] );
			$settings['ga_client_secret'] = $settings['gsc_client_secret'];
		} else {
			if ( ! $client_id ) {
				return new WP_Error( 'ikon_seo_ga_client_id', __( 'Enter the Google OAuth client ID.', 'ikon-seo' ) );
			}
			if ( $client_secret ) {
				$encrypted = $this->crypto->encrypt( $client_secret );
				if ( is_wp_error( $encrypted ) ) {
					return $encrypted;
				}
				$settings['ga_client_secret'] = $encrypted;
			} elseif ( empty( $settings['ga_client_secret'] ) ) {
				return new WP_Error( 'ikon_seo_ga_client_secret', __( 'Enter the Google OAuth client secret.', 'ikon-seo' ) );
			} elseif ( $client_id !== (string) ( $settings['ga_client_id'] ?? '' ) ) {
				return new WP_Error( 'ikon_seo_ga_client_secret', __( 'Enter the matching client secret when changing the Google OAuth client ID.', 'ikon-seo' ) );
			}
		}

		$settings['ga_client_id']     = $client_id;
		$settings['ga_refresh_token'] = '';
		$settings['ga_property']      = '';
		$settings['ga_last_error']    = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->clear_cache();
		return $this->status();
	}

	public function authorization_url( $user_id ) {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['ga_client_id'] ) || empty( $settings['ga_client_secret'] ) ) {
			return new WP_Error( 'ikon_seo_ga_credentials', __( 'Save Google Analytics OAuth credentials before connecting.', 'ikon-seo' ) );
		}

		try {
			$state    = bin2hex( random_bytes( 24 ) );
			$verifier = $this->base64url( random_bytes( 48 ) );
		} catch ( Exception $exception ) {
			return new WP_Error( 'ikon_seo_ga_state', __( 'A secure Google authorization state could not be generated.', 'ikon-seo' ) );
		}

		set_transient(
			'ikon_seo_ga_state_' . hash( 'sha256', $state ),
			array( 'user_id' => absint( $user_id ), 'verifier' => $verifier ),
			10 * MINUTE_IN_SECONDS
		);

		return add_query_arg(
			array(
				'client_id'              => $settings['ga_client_id'],
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
		$key     = 'ikon_seo_ga_state_' . hash( 'sha256', (string) $state );
		$pending = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $pending ) || absint( $pending['user_id'] ?? 0 ) !== absint( $user_id ) || empty( $pending['verifier'] ) ) {
			return new WP_Error( 'ikon_seo_ga_state', __( 'The Google Analytics authorization request expired or did not match this administrator.', 'ikon-seo' ) );
		}
		if ( ! $code ) {
			return new WP_Error( 'ikon_seo_ga_code', __( 'Google did not return an authorization code.', 'ikon-seo' ) );
		}

		$settings      = Ikon_SEO_Plugin::settings();
		$client_secret = $this->crypto->decrypt( $settings['ga_client_secret'] );
		if ( is_wp_error( $client_secret ) ) {
			return $client_secret;
		}
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'code'          => sanitize_text_field( $code ),
					'client_id'     => $settings['ga_client_id'],
					'client_secret' => $client_secret,
					'redirect_uri'  => $this->callback_url(),
					'grant_type'    => 'authorization_code',
					'code_verifier' => $pending['verifier'],
				),
			)
		);
		$data = $this->decode_response( $response, 'Google Analytics authorization' );
		if ( is_wp_error( $data ) ) {
			$this->remember_error( $data->get_error_message() );
			return $data;
		}
		if ( empty( $data['refresh_token'] ) ) {
			return new WP_Error( 'ikon_seo_ga_refresh_token', __( 'Google did not return an offline refresh token. Reconnect and approve access again.', 'ikon-seo' ) );
		}
		$encrypted = $this->crypto->encrypt( $data['refresh_token'] );
		if ( is_wp_error( $encrypted ) ) {
			return $encrypted;
		}
		$settings['ga_refresh_token'] = $encrypted;
		$settings['ga_last_error']    = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->store_access_token( $data );
		$this->clear_cache();
		$this->logger->log( 'ga_connect', 'success', 'Google Analytics connected with read-only access.' );
		return $this->properties();
	}

	public function disconnect() {
		$settings                     = Ikon_SEO_Plugin::settings();
		$settings['ga_refresh_token'] = '';
		$settings['ga_property']      = '';
		$settings['ga_last_error']    = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		delete_transient( 'ikon_seo_ga_access_token' );
		$this->clear_cache();
		$this->logger->log( 'ga_disconnect', 'success', 'Google Analytics connection removed.' );
	}

	public function properties() {
		$items = array();
		$page_token = '';
		for ( $page = 0; $page < 5; $page++ ) {
			$url = 'https://analyticsadmin.googleapis.com/v1beta/accountSummaries?pageSize=200';
			if ( $page_token ) {
				$url = add_query_arg( 'pageToken', $page_token, $url );
			}
			$data = $this->request( 'GET', $url );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			foreach ( (array) ( $data['accountSummaries'] ?? array() ) as $account ) {
				foreach ( (array) ( $account['propertySummaries'] ?? array() ) as $property ) {
					if ( empty( $property['property'] ) ) {
						continue;
					}
					$items[] = array(
						'property'     => sanitize_text_field( $property['property'] ),
						'display_name' => sanitize_text_field( $property['displayName'] ?? $property['property'] ),
						'account_name' => sanitize_text_field( $account['displayName'] ?? '' ),
						'can_edit'     => ! empty( $property['canEdit'] ),
					);
				}
			}
			$page_token = sanitize_text_field( $data['nextPageToken'] ?? '' );
			if ( ! $page_token ) {
				break;
			}
		}
		return array( 'items' => $items );
	}

	public function select_property( $property ) {
		$property = sanitize_text_field( $property );
		if ( ! preg_match( '~^properties/\d+$~', $property ) ) {
			return new WP_Error( 'ikon_seo_ga_property', __( 'Select a valid Google Analytics 4 property.', 'ikon-seo' ) );
		}
		$properties = $this->properties();
		if ( is_wp_error( $properties ) ) {
			return $properties;
		}
		$allowed = wp_list_pluck( $properties['items'], 'property' );
		if ( ! in_array( $property, $allowed, true ) ) {
			return new WP_Error( 'ikon_seo_ga_property', __( 'Select a Google Analytics property available to the connected account.', 'ikon-seo' ) );
		}
		$settings                = Ikon_SEO_Plugin::settings();
		$settings['ga_property'] = $property;
		$settings['ga_last_error'] = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->clear_cache();
		return $this->status();
	}

	public function report( $days = 28, $refresh = false ) {
		$days = max( 7, min( 90, absint( $days ) ) );
		$key  = self::CACHE_KEY . '_' . $days;
		if ( ! $refresh ) {
			$cached = get_transient( $key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$property = sanitize_text_field( Ikon_SEO_Plugin::settings()['ga_property'] ?? '' );
		if ( ! $property ) {
			return new WP_Error( 'ikon_seo_ga_property', __( 'Select a Google Analytics 4 property first.', 'ikon-seo' ) );
		}
		$end            = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
		$start          = gmdate( 'Y-m-d', strtotime( $end . ' -' . ( $days - 1 ) . ' days' ) );
		$previous_end   = gmdate( 'Y-m-d', strtotime( $start . ' -1 day' ) );
		$previous_start = gmdate( 'Y-m-d', strtotime( $previous_end . ' -' . ( $days - 1 ) . ' days' ) );

		$current  = $this->run_report( $property, $start, $end );
		$previous = $this->run_report( $property, $previous_start, $previous_end );
		$pages     = $this->run_report( $property, $start, $end, array( 'landingPagePlusQueryString' ), 250 );
		$old_pages = $this->run_report( $property, $previous_start, $previous_end, array( 'landingPagePlusQueryString' ), 250 );
		foreach ( array( $current, $previous, $pages, $old_pages ) as $response ) {
			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		$current_totals  = $this->totals( $current );
		$previous_totals = $this->totals( $previous );
		$page_rows       = $this->page_rows( $pages );
		$previous_map    = array();
		foreach ( $this->page_rows( $old_pages ) as $row ) {
			$previous_map[ $row['path'] ] = $row;
		}
		foreach ( $page_rows as &$row ) {
			$old = $previous_map[ $row['path'] ] ?? array();
			$row['sessions_change'] = $this->percent_change( $row['sessions'], $old['sessions'] ?? 0 );
			$row['views_change']    = $this->percent_change( $row['views'], $old['views'] ?? 0 );
		}
		unset( $row );

		$result = array(
			'property'        => $property,
			'period'          => array( 'start' => $start, 'end' => $end, 'days' => $days ),
			'previous_period' => array( 'start' => $previous_start, 'end' => $previous_end ),
			'totals'          => $current_totals,
			'previous_totals' => $previous_totals,
			'changes'         => array(
				'sessions'    => $this->percent_change( $current_totals['sessions'], $previous_totals['sessions'] ),
				'active_users'=> $this->percent_change( $current_totals['active_users'], $previous_totals['active_users'] ),
				'views'       => $this->percent_change( $current_totals['views'], $previous_totals['views'] ),
				'key_events'  => $this->percent_change( $current_totals['key_events'], $previous_totals['key_events'] ),
			),
			'top_pages'       => $page_rows,
			'data_note'       => 'Google Analytics measures on-site behaviour. It does not explain rankings by itself, and privacy thresholds or tracking configuration can affect totals.',
			'fetched_at'      => current_time( 'mysql', true ),
		);
		set_transient( $key, $result, 6 * HOUR_IN_SECONDS );
		update_option( 'ikon_seo_ga_last_sync', $result['fetched_at'], false );
		$this->store_snapshot( $result );
		$this->remember_error( '' );
		return $result;
	}

	public function clear_cache() {
		for ( $days = 7; $days <= 90; $days++ ) {
			delete_transient( self::CACHE_KEY . '_' . $days );
		}
	}

	private function run_report( $property, $start, $end, array $dimensions = array(), $limit = 1 ) {
		$body = array(
			'dateRanges' => array( array( 'startDate' => $start, 'endDate' => $end ) ),
			'metrics'    => array_map(
				function( $name ) { return array( 'name' => $name ); },
				array( 'sessions', 'activeUsers', 'engagedSessions', 'engagementRate', 'screenPageViews', 'keyEvents', 'averageSessionDuration' )
			),
			'limit'      => (string) max( 1, min( 250000, absint( $limit ) ) ),
		);
		if ( $dimensions ) {
			$body['dimensions'] = array_map( function( $name ) { return array( 'name' => $name ); }, $dimensions );
			$body['orderBys'] = array( array( 'metric' => array( 'metricName' => 'sessions' ), 'desc' => true ) );
		}
		return $this->request( 'POST', 'https://analyticsdata.googleapis.com/v1beta/' . $property . ':runReport', $body );
	}

	private function request( $method, $url, array $body = array() ) {
		$token = $this->access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$args = array(
			'timeout' => 30,
			'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json' ),
		);
		if ( 'POST' === strtoupper( $method ) ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body'] = wp_json_encode( $body );
			$response = wp_remote_post( $url, $args );
		} else {
			$response = wp_remote_get( $url, $args );
		}
		$data = $this->decode_response( $response, 'Google Analytics' );
		if ( is_wp_error( $data ) ) {
			$this->remember_error( $data->get_error_message() );
		}
		return $data;
	}

	private function access_token() {
		$cached = get_transient( 'ikon_seo_ga_access_token' );
		if ( is_string( $cached ) && $cached ) {
			return $cached;
		}
		$settings      = Ikon_SEO_Plugin::settings();
		$refresh_token = $this->crypto->decrypt( $settings['ga_refresh_token'] ?? '' );
		$client_secret = $this->crypto->decrypt( $settings['ga_client_secret'] ?? '' );
		if ( is_wp_error( $refresh_token ) ) {
			return $refresh_token;
		}
		if ( is_wp_error( $client_secret ) ) {
			return $client_secret;
		}
		if ( ! $refresh_token ) {
			return new WP_Error( 'ikon_seo_ga_not_connected', __( 'Connect Google Analytics first.', 'ikon-seo' ) );
		}
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'client_id'     => $settings['ga_client_id'],
					'client_secret' => $client_secret,
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
				),
			)
		);
		$data = $this->decode_response( $response, 'Google token refresh' );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( empty( $data['access_token'] ) ) {
			return new WP_Error( 'ikon_seo_ga_access_token', __( 'Google did not return an Analytics access token.', 'ikon-seo' ) );
		}
		$this->store_access_token( $data );
		return sanitize_text_field( $data['access_token'] );
	}

	private function store_access_token( array $data ) {
		if ( empty( $data['access_token'] ) ) {
			return;
		}
		$ttl = max( 60, absint( $data['expires_in'] ?? 3600 ) - 120 );
		set_transient( 'ikon_seo_ga_access_token', sanitize_text_field( $data['access_token'] ), $ttl );
	}

	private function decode_response( $response, $label ) {
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ikon_seo_ga_transport', $label . ': ' . $response->get_error_message() );
		}
		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			$message = sanitize_text_field( $data['error']['message'] ?? $data['error_description'] ?? ( $label . ' request failed.' ) );
			return new WP_Error( 'ikon_seo_ga_api', $message, array( 'status' => $status ) );
		}
		return is_array( $data ) ? $data : array();
	}

	private function totals( array $response ) {
		$row = (array) ( $response['rows'][0]['metricValues'] ?? array() );
		$values = array_map( function( $item ) { return (float) ( $item['value'] ?? 0 ); }, $row );
		return array(
			'sessions'                 => (float) ( $values[0] ?? 0 ),
			'active_users'             => (float) ( $values[1] ?? 0 ),
			'engaged_sessions'         => (float) ( $values[2] ?? 0 ),
			'engagement_rate'          => (float) ( $values[3] ?? 0 ),
			'views'                     => (float) ( $values[4] ?? 0 ),
			'key_events'                => (float) ( $values[5] ?? 0 ),
			'average_session_duration'  => (float) ( $values[6] ?? 0 ),
		);
	}

	private function page_rows( array $response ) {
		$rows = array();
		foreach ( (array) ( $response['rows'] ?? array() ) as $row ) {
			$path = sanitize_text_field( $row['dimensionValues'][0]['value'] ?? '' );
			if ( ! $path || '(not set)' === strtolower( $path ) ) {
				continue;
			}
			$metric_values = array_map( function( $item ) { return (float) ( $item['value'] ?? 0 ); }, (array) ( $row['metricValues'] ?? array() ) );
			$rows[] = array(
				'path'                     => $path,
				'url'                      => esc_url_raw( home_url( '/' . ltrim( strtok( $path, '?' ), '/' ) ) ),
				'sessions'                 => (float) ( $metric_values[0] ?? 0 ),
				'active_users'             => (float) ( $metric_values[1] ?? 0 ),
				'engaged_sessions'         => (float) ( $metric_values[2] ?? 0 ),
				'engagement_rate'          => (float) ( $metric_values[3] ?? 0 ),
				'views'                     => (float) ( $metric_values[4] ?? 0 ),
				'key_events'                => (float) ( $metric_values[5] ?? 0 ),
				'average_session_duration'  => (float) ( $metric_values[6] ?? 0 ),
			);
		}
		return $rows;
	}

	private function store_snapshot( array $report ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ikon_seo_analytics_pages';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
			return;
		}
		foreach ( array_slice( (array) ( $report['top_pages'] ?? array() ), 0, 500 ) as $row ) {
			$path = substr( (string) ( $row['path'] ?? '' ), 0, 500 );
			$wpdb->replace(
				$table,
				array(
					'property_id'       => sanitize_text_field( $report['property'] ),
					'page_hash'         => hash( 'sha256', $path ),
					'page_path'         => $path,
					'period_start'      => sanitize_text_field( $report['period']['start'] ),
					'period_end'        => sanitize_text_field( $report['period']['end'] ),
					'sessions'          => (float) $row['sessions'],
					'active_users'      => (float) $row['active_users'],
					'engaged_sessions'  => (float) $row['engaged_sessions'],
					'engagement_rate'   => (float) $row['engagement_rate'],
					'views'              => (float) $row['views'],
					'key_events'         => (float) $row['key_events'],
					'fetched_at'         => current_time( 'mysql', true ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%s' )
			);
		}
	}

	private function percent_change( $current, $previous ) {
		$current = (float) $current;
		$previous = (float) $previous;
		if ( 0.0 === $previous ) {
			return 0.0 === $current ? 0 : null;
		}
		return round( ( ( $current - $previous ) / $previous ) * 100, 2 );
	}

	private function callback_url() {
		return admin_url( 'admin-post.php?action=ikon_seo_ga_callback' );
	}

	private function base64url( $value ) {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	private function remember_error( $message ) {
		$settings                  = Ikon_SEO_Plugin::settings();
		$settings['ga_last_error'] = sanitize_text_field( $message );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
	}
}
