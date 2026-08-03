<?php

defined( 'ABSPATH' ) || exit;

/**
 * International targeting governance and privacy-preserving server-log evidence.
 */
final class Ikon_SEO_International_Server_Intelligence {
	const CRON_HOOK       = 'ikon_seo_international_server_weekly';
	const MAX_UPLOAD_SIZE = 10485760;
	const MAX_IMPORT_ROWS = 50000;

	private $inventory;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->inventory = $inventory;
		$this->history   = $history;
		$this->logger    = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_review' ) );
	}

	public function pages_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_international_pages';
	}

	public function log_events_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_server_log_events';
	}

	public function imports_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_server_log_imports';
	}

	public function scheduled_review() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['international_server_enabled'] ) ) {
			return;
		}

		$this->audit_international_batch(
			max( 1, min( 25, absint( $settings['international_audit_batch'] ?? 5 ) ) ),
			0,
			false
		);
		$this->cleanup();
	}

	public function status() {
		global $wpdb;
		$settings = Ikon_SEO_Plugin::settings();

		if ( ! $this->tables_ready() ) {
			return array(
				'enabled'      => ! empty( $settings['international_server_enabled'] ),
				'tables_ready' => false,
			);
		}

		$pages  = $this->pages_table();
		$events = $this->log_events_table();
		$since  = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

		return array(
			'enabled'                  => ! empty( $settings['international_server_enabled'] ),
			'tables_ready'             => true,
			'audited_pages'            => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$pages}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'pages_with_issues'        => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$pages} WHERE issue_count > 0" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'hreflang_issues'          => absint( $wpdb->get_var( "SELECT COALESCE(SUM(issue_count),0) FROM {$pages}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'reciprocal_issues'        => absint( $wpdb->get_var( "SELECT COALESCE(SUM(reciprocal_issues),0) FROM {$pages}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'log_events_30_days'       => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$events} WHERE occurred_at >= %s", $since ) ) ),
			'verified_crawler_events'  => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$events} WHERE occurred_at >= %s AND verification_state = 'verified'", $since ) ) ),
			'crawler_errors_30_days'   => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$events} WHERE occurred_at >= %s AND status_code >= 400", $since ) ) ),
			'crawler_5xx_30_days'      => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$events} WHERE occurred_at >= %s AND status_code >= 500", $since ) ) ),
			'crawl_waste_30_days'      => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$events} WHERE occurred_at >= %s AND waste_category <> 'none'", $since ) ) ),
			'last_page_audit'          => sanitize_text_field( $wpdb->get_var( "SELECT MAX(audited_at) FROM {$pages}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'last_log_event'           => sanitize_text_field( $wpdb->get_var( "SELECT MAX(occurred_at) FROM {$events}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'privacy'                  => array(
				'stores_raw_ip'          => false,
				'stores_raw_user_agent'  => false,
				'stores_query_values'    => false,
				'changes_public_pages'   => false,
			),
		);
	}

	public function report( $limit = 100 ) {
		$limit = max( 10, min( 500, absint( $limit ) ) );
		return array(
			'status'                  => $this->status(),
			'international_pages'     => $this->list_pages( $limit ),
			'crawler_summary'         => $this->crawler_summary( 30 ),
			'top_errors'              => $this->top_log_paths( 'errors', $limit ),
			'top_waste'               => $this->top_log_paths( 'waste', $limit ),
			'slow_paths'              => $this->top_log_paths( 'slow', $limit ),
			'important_uncrawled'     => $this->important_uncrawled_pages( min( 100, $limit ), 30 ),
			'locale_map'              => $this->locale_map(),
			'limitations'             => array(
				'Hreflang evidence is based on the rendered HTML retrieved during the stored audit and may differ after cache or deployment changes.',
				'Reciprocal-link checks are complete only for alternate pages that have also been audited.',
				'Crawler verification is available only when the imported log contains an IP address and DNS verification is enabled.',
				'Server logs can show crawler requests but cannot prove indexing, ranking impact or search-engine intent.',
			),
			'generated_at'            => current_time( 'mysql', true ),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'audit_international_batch':
				return $this->audit_international_batch( absint( $payload['limit'] ?? 5 ), $user_id, ! empty( $payload['refresh_inventory'] ) );
			case 'audit_url':
				return $this->audit_url( esc_url_raw( $payload['url'] ?? '' ), $user_id );
			case 'import_log_events':
				return $this->import_log_events( (array) ( $payload['events'] ?? array() ), $user_id, sanitize_key( $payload['source'] ?? 'workspace' ) );
			case 'save_settings':
				return $this->save_settings( $payload, $user_id );
			case 'cleanup':
				return $this->cleanup();
			case 'read':
			default:
				return $this->report( absint( $payload['limit'] ?? 100 ) );
		}
	}

	public function save_settings( array $data, $user_id = 0 ) {
		$settings = Ikon_SEO_Plugin::settings();
		$settings['international_server_enabled']       = empty( $data['enabled'] ) ? 0 : 1;
		$settings['international_audit_batch']           = max( 1, min( 50, absint( $data['audit_batch'] ?? $settings['international_audit_batch'] ?? 5 ) ) );
		$settings['international_stale_days']            = max( 1, min( 365, absint( $data['stale_days'] ?? $settings['international_stale_days'] ?? 30 ) ) );
		$settings['international_locale_map']             = $this->sanitize_locale_map_text( $data['locale_map'] ?? $settings['international_locale_map'] ?? '' );
		$settings['international_x_default_url']          = $this->same_site_url_or_empty( $data['x_default_url'] ?? $settings['international_x_default_url'] ?? '' );
		$settings['server_log_retention_days']            = max( 30, min( 730, absint( $data['retention_days'] ?? $settings['server_log_retention_days'] ?? 180 ) ) );
		$settings['server_log_max_rows']                  = max( 100, min( self::MAX_IMPORT_ROWS, absint( $data['max_rows'] ?? $settings['server_log_max_rows'] ?? 20000 ) ) );
		$settings['server_log_verify_crawlers']           = empty( $data['verify_crawlers'] ) ? 0 : 1;
		$settings['server_log_slow_ms']                   = max( 100, min( 60000, absint( $data['slow_ms'] ?? $settings['server_log_slow_ms'] ?? 1500 ) ) );
		$settings['server_log_store_query_keys']          = empty( $data['store_query_keys'] ) ? 0 : 1;
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );

		$this->history_event(
			'international_server',
			'updated',
			'International and server settings updated',
			'International targeting and server-log evidence settings were updated.',
			array( 'enabled' => ! empty( $settings['international_server_enabled'] ) ),
			$user_id
		);

		return array( 'saved' => true, 'status' => $this->status() );
	}

	public function audit_international_batch( $limit = 5, $user_id = 0, $refresh_inventory = false ) {
		$limit = max( 1, min( 50, absint( $limit ) ) );
		if ( ! $this->table_exists( $this->pages_table() ) ) {
			return new WP_Error( 'ikon_seo_international_table', __( 'The international audit database is not ready.', 'ikon-seo' ) );
		}

		$inventory = $this->inventory->scan( (bool) $refresh_inventory );
		$rows = (array) ( $inventory['items'] ?? $inventory['pages'] ?? array() );
		if ( ! $rows ) {
			$rows = $this->fallback_inventory();
		}

		$settings = Ikon_SEO_Plugin::settings();
		$stale_before = gmdate( 'Y-m-d H:i:s', strtotime( '-' . max( 1, absint( $settings['international_stale_days'] ?? 30 ) ) . ' days' ) );
		$audited = array();
		foreach ( $rows as $row ) {
			if ( count( $audited ) >= $limit ) {
				break;
			}
			$url = esc_url_raw( $row['url'] ?? $row['permalink'] ?? '' );
			if ( ! $url || ! $this->url_belongs_to_site( $url ) ) {
				continue;
			}
			$existing = $this->get_page_by_url( $url );
			if ( $existing && ! empty( $existing['audited_at'] ) && $existing['audited_at'] > $stale_before ) {
				continue;
			}
			$result = $this->audit_url( $url, $user_id, absint( $row['post_id'] ?? $row['id'] ?? 0 ) );
			if ( ! is_wp_error( $result ) ) {
				$audited[] = $result;
			}
		}

		$this->refresh_reciprocal_issues();
		return array( 'audited' => count( $audited ), 'items' => $audited, 'status' => $this->status() );
	}

	public function audit_url( $url, $user_id = 0, $post_id = 0 ) {
		global $wpdb;
		$url = esc_url_raw( $url );
		if ( ! $url || ! $this->url_belongs_to_site( $url ) ) {
			return new WP_Error( 'ikon_seo_international_url', __( 'Use a valid URL from the connected website.', 'ikon-seo' ) );
		}

		$started = microtime( true );
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 15,
				'redirection'         => 3,
				'limit_response_size' => 2097152,
				'headers'             => array( 'Accept' => 'text/html,application/xhtml+xml' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = absint( wp_remote_retrieve_response_code( $response ) );
		$html = (string) wp_remote_retrieve_body( $response );
		$headers = wp_remote_retrieve_headers( $response );
		$response_ms = absint( round( ( microtime( true ) - $started ) * 1000 ) );
		$analysis = $this->analyse_international_html( $url, $html, $headers, $status_code );
		$now = current_time( 'mysql', true );
		$url_hash = hash( 'sha256', $this->normalise_url( $url ) );
		$record = array(
			'url_hash'            => $url_hash,
			'post_id'             => absint( $post_id ?: url_to_postid( $url ) ),
			'url'                 => $url,
			'canonical_url'       => esc_url_raw( $analysis['canonical_url'] ?? '' ),
			'html_lang'           => sanitize_text_field( $analysis['html_lang'] ?? '' ),
			'content_language'    => sanitize_text_field( $analysis['content_language'] ?? '' ),
			'inferred_locale'     => sanitize_text_field( $analysis['inferred_locale'] ?? '' ),
			'hreflang_json'       => wp_json_encode( $analysis['hreflang'] ?? array() ),
			'issues_json'         => wp_json_encode( $analysis['issues'] ?? array() ),
			'issue_count'         => count( (array) ( $analysis['issues'] ?? array() ) ),
			'reciprocal_issues'   => 0,
			'regional_signals_json' => wp_json_encode( $analysis['regional_signals'] ?? array() ),
			'status_code'         => $status_code,
			'response_ms'         => $response_ms,
			'audited_at'          => $now,
			'updated_at'          => $now,
		);

		$existing = $this->get_page_by_url( $url );
		if ( $existing ) {
			$wpdb->update( $this->pages_table(), $record, array( 'url_hash' => $url_hash ) );
		} else {
			$wpdb->insert( $this->pages_table(), $record );
		}

		$this->history_event(
			'international',
			$record['issue_count'] ? 'needs_review' : 'reviewed',
			'International page audited',
			$url,
			array( 'issues' => $record['issue_count'], 'hreflang_entries' => count( (array) ( $analysis['hreflang'] ?? array() ) ) ),
			$user_id,
			$record['post_id']
		);

		return array_merge( $record, array( 'hreflang' => $analysis['hreflang'], 'issues' => $analysis['issues'], 'regional_signals' => $analysis['regional_signals'] ) );
	}

	public function analyse_international_html( $url, $html, $headers = array(), $status_code = 200 ) {
		$html_lang = '';
		$content_language = '';
		$canonical = '';
		$hreflang = array();
		$issues = array();

		if ( preg_match( '/<html[^>]+\blang\s*=\s*["\']([^"\']+)["\']/i', $html, $match ) ) {
			$html_lang = strtolower( trim( html_entity_decode( $match[1], ENT_QUOTES, 'UTF-8' ) ) );
		}
		if ( preg_match( '/<link[^>]+\brel\s*=\s*["\'][^"\']*canonical[^"\']*["\'][^>]+\bhref\s*=\s*["\']([^"\']+)["\']/i', $html, $match ) || preg_match( '/<link[^>]+\bhref\s*=\s*["\']([^"\']+)["\'][^>]+\brel\s*=\s*["\'][^"\']*canonical[^"\']*["\']/i', $html, $match ) ) {
			$canonical = $this->absolute_url( $match[1], $url );
		}

		if ( is_object( $headers ) && method_exists( $headers, 'offsetGet' ) ) {
			$content_language = sanitize_text_field( $headers->offsetGet( 'content-language' ) );
		} elseif ( is_array( $headers ) && isset( $headers['content-language'] ) ) {
			$content_language = sanitize_text_field( is_array( $headers['content-language'] ) ? reset( $headers['content-language'] ) : $headers['content-language'] );
		}
		if ( ! $content_language && preg_match( '/<meta[^>]+http-equiv\s*=\s*["\']content-language["\'][^>]+content\s*=\s*["\']([^"\']+)["\']/i', $html, $match ) ) {
			$content_language = strtolower( trim( $match[1] ) );
		}

		if ( preg_match_all( '/<link\b[^>]*>/i', $html, $links ) ) {
			foreach ( (array) $links[0] as $tag ) {
				if ( ! preg_match( '/\brel\s*=\s*["\']([^"\']+)["\']/i', $tag, $rel_match ) || false === stripos( $rel_match[1], 'alternate' ) ) {
					continue;
				}
				if ( ! preg_match( '/\bhreflang\s*=\s*["\']([^"\']+)["\']/i', $tag, $lang_match ) || ! preg_match( '/\bhref\s*=\s*["\']([^"\']+)["\']/i', $tag, $href_match ) ) {
					continue;
				}
				$lang = strtolower( trim( html_entity_decode( $lang_match[1], ENT_QUOTES, 'UTF-8' ) ) );
				$href = $this->absolute_url( html_entity_decode( $href_match[1], ENT_QUOTES, 'UTF-8' ), $url );
				$hreflang[] = array( 'lang' => $lang, 'url' => $href );
			}
		}

		if ( $status_code < 200 || $status_code >= 400 ) {
			$issues[] = $this->issue( 'page_status', 'high', 'The audited URL did not return a normal successful page response.' );
		}
		if ( ! $html_lang ) {
			$issues[] = $this->issue( 'missing_html_lang', 'medium', 'The page does not expose a language value on the HTML element.' );
		} elseif ( ! $this->validate_locale_code( $html_lang, false ) ) {
			$issues[] = $this->issue( 'invalid_html_lang', 'medium', 'The HTML language value does not use a supported language or language-region format.' );
		}

		$seen = array();
		$x_default_count = 0;
		$x_default_url = '';
		$self_reference = false;
		foreach ( $hreflang as $entry ) {
			$lang = $entry['lang'];
			if ( 'x-default' === $lang ) {
				$x_default_count++;
				if ( ! $x_default_url ) {
					$x_default_url = $entry['url'];
				}
			} elseif ( ! $this->validate_locale_code( $lang, true ) ) {
				$issues[] = $this->issue( 'invalid_hreflang', 'high', 'An alternate link uses an invalid hreflang value: ' . $lang );
			}
			if ( isset( $seen[ $lang ] ) ) {
				$issues[] = $this->issue( 'duplicate_hreflang', 'high', 'More than one alternate URL is declared for ' . $lang . '.' );
			}
			$seen[ $lang ] = true;
			if ( $html_lang && $this->locale_equivalent( $html_lang, $lang ) && $this->same_normalised_url( $url, $entry['url'] ) ) {
				$self_reference = true;
			}
		}

		if ( $x_default_count > 1 ) {
			$issues[] = $this->issue( 'duplicate_x_default', 'high', 'More than one x-default alternate was found.' );
		}
		$settings = Ikon_SEO_Plugin::settings();
		$configured_x_default = esc_url_raw( $settings['international_x_default_url'] ?? '' );
		if ( $configured_x_default && 0 === $x_default_count ) {
			$issues[] = $this->issue( 'missing_configured_x_default', 'medium', 'The configured x-default URL is not declared on this page.' );
		} elseif ( $configured_x_default && $x_default_url && ! $this->same_normalised_url( $configured_x_default, $x_default_url ) ) {
			$issues[] = $this->issue( 'x_default_mismatch', 'medium', 'The page x-default URL differs from the configured website default.' );
		}
		if ( $hreflang && ! $self_reference ) {
			$issues[] = $this->issue( 'missing_self_reference', 'high', 'The alternate-language set does not contain a matching self-reference.' );
		}
		if ( $canonical && ! $this->same_normalised_url( $url, $canonical ) && $hreflang ) {
			$issues[] = $this->issue( 'canonical_hreflang_conflict', 'high', 'The page declares alternates while its canonical points to another URL.' );
		}
		if ( $content_language && $html_lang && ! $this->locale_equivalent( $content_language, $html_lang ) ) {
			$issues[] = $this->issue( 'language_header_mismatch', 'medium', 'The content-language evidence differs from the HTML language value.' );
		}

		$inferred = $this->infer_locale_from_url( $url );
		if ( $inferred && $html_lang && ! $this->locale_equivalent( $inferred, $html_lang ) ) {
			$issues[] = $this->issue( 'url_locale_mismatch', 'medium', 'The configured URL locale differs from the HTML language value.' );
		}

		$visible_text = wp_strip_all_tags( preg_replace( '/<(script|style)[^>]*>.*?<\/\1>/is', ' ', $html ) );
		$regional = $this->regional_signals( $url, $visible_text, $inferred ?: $html_lang );
		foreach ( (array) ( $regional['issues'] ?? array() ) as $regional_issue ) {
			$issues[] = $regional_issue;
		}
		unset( $regional['issues'] );

		return array(
			'html_lang'        => $html_lang,
			'content_language' => strtolower( trim( $content_language ) ),
			'canonical_url'    => $canonical,
			'inferred_locale'  => $inferred,
			'hreflang'         => array_values( $hreflang ),
			'regional_signals' => $regional,
			'issues'           => $this->unique_issues( $issues ),
		);
	}

	public function validate_locale_code( $value, $allow_x_default = true ) {
		$value = strtolower( trim( (string) $value ) );
		if ( $allow_x_default && 'x-default' === $value ) {
			return true;
		}
		return (bool) preg_match( '/^[a-z]{2,3}(?:-[a-z]{4})?(?:-[a-z]{2}|-[0-9]{3})?$/', $value );
	}

	public function import_log_file( $file_path, $filename, $user_id = 0, $format = 'auto' ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return new WP_Error( 'ikon_seo_log_file', __( 'The uploaded server log could not be read.', 'ikon-seo' ) );
		}
		$size = filesize( $file_path );
		if ( false === $size || $size > self::MAX_UPLOAD_SIZE ) {
			return new WP_Error( 'ikon_seo_log_size', __( 'The server-log file is larger than the allowed 10 MB limit.', 'ikon-seo' ) );
		}

		$settings = Ikon_SEO_Plugin::settings();
		$max_rows = max( 100, min( self::MAX_IMPORT_ROWS, absint( $settings['server_log_max_rows'] ?? 20000 ) ) );
		$handle = fopen( $file_path, 'rb' );
		if ( ! $handle ) {
			return new WP_Error( 'ikon_seo_log_open', __( 'The server-log file could not be opened.', 'ikon-seo' ) );
		}

		$events = array();
		$rows_seen = 0;
		$detected = sanitize_key( $format );
		$header = null;
		while ( ! feof( $handle ) && $rows_seen < $max_rows ) {
			$line = fgets( $handle );
			if ( false === $line ) {
				break;
			}
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$rows_seen++;
			if ( 'auto' === $detected ) {
				$detected = false !== strpos( $line, ',' ) && false !== stripos( $line, 'status' ) ? 'csv' : 'combined';
			}
			if ( 'csv' === $detected ) {
				$row = str_getcsv( $line );
				if ( null === $header ) {
					$header = array_map( 'sanitize_key', $row );
					continue;
				}
				$data = array();
				foreach ( $header as $index => $key ) {
					$data[ $key ] = $row[ $index ] ?? '';
				}
				$events[] = $data;
			} else {
				$parsed = $this->parse_combined_log_line( $line );
				if ( $parsed ) {
					$events[] = $parsed;
				}
			}
		}
		$truncated = ! feof( $handle ) || $rows_seen >= $max_rows;
		fclose( $handle );

		$result = $this->import_log_events( $events, $user_id, 'file_' . $detected, $filename, $rows_seen );
		if ( is_array( $result ) ) {
			$result['truncated'] = $truncated;
		}
		return $result;
	}

	public function parse_combined_log_line( $line ) {
		$pattern = '/^(\S+)\s+\S+\s+\S+\s+\[([^\]]+)\]\s+"(\S+)\s+([^\s"]+)(?:\s+HTTP\/[^"]+)?"\s+(\d{3})\s+(\S+)(?:\s+"([^"]*)"\s+"([^"]*)")?(?:\s+(\d+))?$/';
		if ( ! preg_match( $pattern, trim( (string) $line ), $match ) ) {
			return array();
		}
		$date = DateTime::createFromFormat( 'd/M/Y:H:i:s O', $match[2] );
		return array(
			'ip'          => $match[1],
			'occurred_at' => $date ? $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) : '',
			'method'      => $match[3],
			'request'     => $match[4],
			'status'      => absint( $match[5] ),
			'bytes'       => '-' === $match[6] ? 0 : absint( $match[6] ),
			'referrer'    => $match[7] ?? '',
			'user_agent'  => $match[8] ?? '',
			'response_ms' => isset( $match[9] ) ? absint( $match[9] ) : 0,
		);
	}

	public function import_log_events( array $events, $user_id = 0, $source = 'workspace', $filename = '', $rows_seen = null ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_server_log_tables', __( 'The server-log database is not ready.', 'ikon-seo' ) );
		}
		$settings = Ikon_SEO_Plugin::settings();
		$max_rows = max( 1, min( self::MAX_IMPORT_ROWS, absint( $settings['server_log_max_rows'] ?? 20000 ) ) );
		$events = array_slice( $events, 0, $max_rows );
		$batch_id = wp_generate_uuid4();
		$imported = 0;
		$skipped = 0;
		$verified = 0;
		$now = current_time( 'mysql', true );

		foreach ( $events as $raw ) {
			$record = $this->normalise_log_event( (array) $raw );
			if ( is_wp_error( $record ) ) {
				$skipped++;
				continue;
			}
			if ( 'verified' === $record['verification_state'] ) {
				$verified++;
			}
			$inserted = $wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$this->log_events_table()} (event_hash,occurred_at,method,request_path,path_hash,status_code,bytes_sent,response_ms,crawler_family,verification_state,user_agent_hash,ip_hash,query_keys,content_group,waste_category,created_at) VALUES (%s,%s,%s,%s,%s,%d,%d,%d,%s,%s,%s,%s,%s,%s,%s,%s)",
					$record['event_hash'], $record['occurred_at'], $record['method'], $record['request_path'], $record['path_hash'], $record['status_code'], $record['bytes_sent'], $record['response_ms'], $record['crawler_family'], $record['verification_state'], $record['user_agent_hash'], $record['ip_hash'], $record['query_keys'], $record['content_group'], $record['waste_category'], $now
				)
			);
			if ( $inserted ) {
				$imported++;
			} else {
				$skipped++;
			}
		}

		$wpdb->insert(
			$this->imports_table(),
			array(
				'batch_id'       => $batch_id,
				'source_format'  => sanitize_key( $source ),
				'filename'       => sanitize_file_name( $filename ),
				'rows_seen'      => absint( null === $rows_seen ? count( $events ) : $rows_seen ),
				'rows_imported'  => $imported,
				'rows_skipped'   => $skipped,
				'verified_count' => $verified,
				'created_by'     => absint( $user_id ),
				'started_at'     => $now,
				'completed_at'   => current_time( 'mysql', true ),
			)
		);

		$this->history_event(
			'server_logs',
			'imported',
			'Server-log evidence imported',
			sprintf( '%d records imported and %d skipped.', $imported, $skipped ),
			array( 'batch_id' => $batch_id, 'source' => sanitize_key( $source ), 'verified' => $verified ),
			$user_id
		);

		return array( 'batch_id' => $batch_id, 'imported' => $imported, 'skipped' => $skipped, 'verified' => $verified, 'status' => $this->status() );
	}

	public function normalise_log_event( array $raw ) {
		$settings = Ikon_SEO_Plugin::settings();
		$occurred = sanitize_text_field( $raw['occurred_at'] ?? $raw['time'] ?? $raw['timestamp'] ?? '' );
		if ( ! $occurred || false === strtotime( $occurred ) ) {
			return new WP_Error( 'ikon_seo_log_time', __( 'A valid UTC-compatible event time is required.', 'ikon-seo' ) );
		}
		$occurred = gmdate( 'Y-m-d H:i:s', strtotime( $occurred ) );
		$method = strtoupper( sanitize_key( $raw['method'] ?? 'GET' ) );
		if ( ! in_array( $method, array( 'GET', 'HEAD', 'POST', 'OPTIONS' ), true ) ) {
			$method = 'GET';
		}
		$request = (string) ( $raw['request_path'] ?? $raw['request_uri'] ?? $raw['request'] ?? $raw['url'] ?? '' );
		$normalised = $this->normalise_request_path( $request, ! empty( $settings['server_log_store_query_keys'] ) );
		if ( empty( $normalised['path'] ) ) {
			return new WP_Error( 'ikon_seo_log_path', __( 'A request path is required.', 'ikon-seo' ) );
		}
		$status = absint( $raw['status_code'] ?? $raw['status'] ?? 0 );
		if ( $status < 100 || $status > 599 ) {
			$status = 0;
		}
		$user_agent = sanitize_text_field( $raw['user_agent'] ?? $raw['agent'] ?? '' );
		$ip = sanitize_text_field( $raw['ip'] ?? $raw['remote_addr'] ?? '' );
		$crawler = $this->crawler_family( $user_agent );
		$verification = 'unknown';
		if ( 'other' !== $crawler ) {
			$verification = 'declared';
			if ( ! empty( $settings['server_log_verify_crawlers'] ) && $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				$verification = $this->verify_crawler_ip( $ip, $crawler ) ? 'verified' : 'unverified';
			}
		}
		$group = $this->content_group( $normalised['path'] );
		$waste = $this->waste_category( $normalised['path'], $normalised['query_keys'], $status, $group );
		$event_hash = hash( 'sha256', implode( '|', array( $occurred, $method, $normalised['path'], $status, hash( 'sha256', $user_agent ), hash( 'sha256', $ip ) ) ) );

		return array(
			'event_hash'        => $event_hash,
			'occurred_at'       => $occurred,
			'method'            => $method,
			'request_path'      => $normalised['path'],
			'path_hash'         => hash( 'sha256', $normalised['path'] ),
			'status_code'       => $status,
			'bytes_sent'        => absint( $raw['bytes_sent'] ?? $raw['bytes'] ?? 0 ),
			'response_ms'       => max( 0, min( 3600000, absint( $raw['response_ms'] ?? $raw['request_time_ms'] ?? 0 ) ) ),
			'crawler_family'    => $crawler,
			'verification_state'=> $verification,
			'user_agent_hash'   => hash( 'sha256', $user_agent ),
			'ip_hash'           => hash( 'sha256', $ip ),
			'query_keys'        => implode( ',', $normalised['query_keys'] ),
			'content_group'     => $group,
			'waste_category'    => $waste,
		);
	}

	public function normalise_request_path( $request, $store_query_keys = true ) {
		$request = trim( (string) $request );
		if ( preg_match( '/^[A-Z]+\s+([^\s]+)(?:\s+HTTP\/\S+)?$/i', $request, $match ) ) {
			$request = $match[1];
		}
		if ( preg_match( '#^https?://#i', $request ) ) {
			$parts = wp_parse_url( $request );
		} else {
			$parts = wp_parse_url( 'https://placeholder.invalid' . ( '/' === substr( $request, 0, 1 ) ? '' : '/' ) . $request );
		}
		$path = isset( $parts['path'] ) ? rawurldecode( $parts['path'] ) : '/';
		$path = '/' . ltrim( preg_replace( '#/+#', '/', $path ), '/' );
		$query_keys = array();
		if ( $store_query_keys && ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
			$query_keys = array_slice( array_map( 'sanitize_key', array_keys( (array) $query ) ), 0, 25 );
			sort( $query_keys );
		}
		return array( 'path' => $path, 'query_keys' => array_values( array_filter( $query_keys ) ) );
	}

	public function crawler_family( $user_agent ) {
		$ua = strtolower( (string) $user_agent );
		$families = array(
			'googlebot' => array( 'googlebot', 'google-inspectiontool', 'googleother' ),
			'bingbot'   => array( 'bingbot', 'adidxbot' ),
			'applebot'  => array( 'applebot' ),
			'duckduckbot' => array( 'duckduckbot' ),
			'yandexbot' => array( 'yandexbot' ),
			'baiduspider' => array( 'baiduspider' ),
			'other_search_crawler' => array( 'slurp', 'petalbot', 'seznambot' ),
		);
		foreach ( $families as $family => $needles ) {
			foreach ( $needles as $needle ) {
				if ( false !== strpos( $ua, $needle ) ) {
					return $family;
				}
			}
		}
		return 'other';
	}

	public function verify_crawler_ip( $ip, $family ) {
		$family = sanitize_key( $family );
		$domains = array(
			'googlebot' => array( '.googlebot.com', '.google.com', '.googleusercontent.com' ),
			'bingbot'   => array( '.search.msn.com' ),
			'applebot'  => array( '.applebot.apple.com' ),
		);
		if ( empty( $domains[ $family ] ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}
		$cache_key = 'ikon_seo_crawler_verify_' . md5( $family . '|' . $ip );
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return '1' === (string) $cached;
		}
		$host = strtolower( rtrim( (string) gethostbyaddr( $ip ), '.' ) );
		$allowed = false;
		foreach ( $domains[ $family ] as $suffix ) {
			if ( strlen( $host ) > strlen( $suffix ) && substr( $host, -strlen( $suffix ) ) === $suffix ) {
				$allowed = true;
				break;
			}
		}
		if ( $allowed ) {
			$forward = gethostbynamel( $host );
			$allowed = is_array( $forward ) && in_array( $ip, $forward, true );
		}
		set_transient( $cache_key, $allowed ? '1' : '0', DAY_IN_SECONDS );
		return $allowed;
	}

	public function cleanup() {
		global $wpdb;
		if ( ! $this->tables_ready() ) {
			return array( 'deleted' => 0 );
		}
		$settings = Ikon_SEO_Plugin::settings();
		$retention = max( 30, min( 730, absint( $settings['server_log_retention_days'] ?? 180 ) ) );
		$before = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $retention . ' days' ) );
		$deleted = absint( $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->log_events_table()} WHERE occurred_at < %s", $before ) ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->imports_table()} WHERE completed_at < %s", $before ) );
		return array( 'deleted' => $deleted, 'before' => $before );
	}

	private function list_pages( $limit ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->pages_table() ) ) {
			return array();
		}
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->pages_table()} ORDER BY issue_count DESC, audited_at DESC LIMIT %d", $limit ), ARRAY_A );
		foreach ( (array) $rows as &$row ) {
			$row['hreflang'] = json_decode( $row['hreflang_json'] ?? '[]', true ) ?: array();
			$row['issues'] = json_decode( $row['issues_json'] ?? '[]', true ) ?: array();
			$row['regional_signals'] = json_decode( $row['regional_signals_json'] ?? '[]', true ) ?: array();
			unset( $row['hreflang_json'], $row['issues_json'], $row['regional_signals_json'] );
		}
		return array_values( (array) $rows );
	}

	private function crawler_summary( $days ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->log_events_table() ) ) {
			return array();
		}
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days ) . ' days' ) );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT crawler_family, verification_state, COUNT(*) AS requests, SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS errors, SUM(CASE WHEN waste_category <> 'none' THEN 1 ELSE 0 END) AS waste, ROUND(AVG(NULLIF(response_ms,0)),0) AS average_response_ms, MAX(occurred_at) AS last_seen FROM {$this->log_events_table()} WHERE occurred_at >= %s GROUP BY crawler_family, verification_state ORDER BY requests DESC LIMIT 50",
				$since
			),
			ARRAY_A
		);
	}

	private function top_log_paths( $type, $limit ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->log_events_table() ) ) {
			return array();
		}
		$settings = Ikon_SEO_Plugin::settings();
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		$where = "occurred_at >= %s";
		$order = 'requests DESC';
		$params = array( $since );
		if ( 'errors' === $type ) {
			$where .= ' AND status_code >= 400';
		} elseif ( 'waste' === $type ) {
			$where .= " AND waste_category <> 'none'";
		} elseif ( 'slow' === $type ) {
			$where .= ' AND response_ms >= %d';
			$params[] = max( 100, absint( $settings['server_log_slow_ms'] ?? 1500 ) );
			$order = 'average_response_ms DESC';
		}
		$params[] = max( 1, min( 500, absint( $limit ) ) );
		$sql = "SELECT request_path, crawler_family, status_code, waste_category, COUNT(*) AS requests, ROUND(AVG(NULLIF(response_ms,0)),0) AS average_response_ms, MAX(occurred_at) AS last_seen FROM {$this->log_events_table()} WHERE {$where} GROUP BY request_path,crawler_family,status_code,waste_category ORDER BY {$order} LIMIT %d";
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
	}

	private function important_uncrawled_pages( $limit, $days ) {
		global $wpdb;
		$inventory = $this->inventory->scan( false );
		$rows = (array) ( $inventory['items'] ?? $inventory['pages'] ?? array() );
		if ( ! $rows ) {
			$rows = $this->fallback_inventory();
		}
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days ) . ' days' ) );
		$result = array();
		foreach ( $rows as $row ) {
			$url = esc_url_raw( $row['url'] ?? $row['permalink'] ?? '' );
			if ( ! $url ) {
				continue;
			}
			$path = wp_parse_url( $url, PHP_URL_PATH ) ?: '/';
			$count = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->log_events_table()} WHERE path_hash = %s AND occurred_at >= %s AND crawler_family <> 'other'", hash( 'sha256', $path ), $since ) ) );
			$priority = absint( $row['business_value'] ?? $row['priority'] ?? 50 );
			if ( 0 === $count && $priority >= 50 ) {
				$result[] = array( 'url' => $url, 'title' => sanitize_text_field( $row['title'] ?? '' ), 'priority' => $priority, 'crawler_requests' => 0 );
				if ( count( $result ) >= $limit ) {
					break;
				}
			}
		}
		return $result;
	}

	private function refresh_reciprocal_issues() {
		global $wpdb;
		if ( ! $this->table_exists( $this->pages_table() ) ) {
			return;
		}
		$rows = $wpdb->get_results( "SELECT url_hash,url,hreflang_json,issues_json FROM {$this->pages_table()}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$by_url = array();
		foreach ( (array) $rows as $row ) {
			$by_url[ $this->normalise_url( $row['url'] ) ] = $row;
		}
		foreach ( (array) $rows as $row ) {
			$issues = json_decode( $row['issues_json'] ?? '[]', true ) ?: array();
			$issues = array_values( array_filter( $issues, function ( $issue ) { return 'missing_return_link' !== ( $issue['code'] ?? '' ); } ) );
			$reciprocal = 0;
			$alternates = json_decode( $row['hreflang_json'] ?? '[]', true ) ?: array();
			foreach ( $alternates as $alternate ) {
				$target_key = $this->normalise_url( $alternate['url'] ?? '' );
				if ( ! $target_key || empty( $by_url[ $target_key ] ) ) {
					continue;
				}
				$target_alternates = json_decode( $by_url[ $target_key ]['hreflang_json'] ?? '[]', true ) ?: array();
				$returned = false;
				foreach ( $target_alternates as $target_alternate ) {
					if ( $this->same_normalised_url( $row['url'], $target_alternate['url'] ?? '' ) ) {
						$returned = true;
						break;
					}
				}
				if ( ! $returned ) {
					$reciprocal++;
					$issues[] = $this->issue( 'missing_return_link', 'high', 'An audited alternate page does not link back to this URL.' );
				}
			}
			$wpdb->update(
				$this->pages_table(),
				array( 'issues_json' => wp_json_encode( $this->unique_issues( $issues ) ), 'issue_count' => count( $this->unique_issues( $issues ) ), 'reciprocal_issues' => $reciprocal, 'updated_at' => current_time( 'mysql', true ) ),
				array( 'url_hash' => $row['url_hash'] )
			);
		}
	}

	private function locale_map() {
		$settings = Ikon_SEO_Plugin::settings();
		$lines = preg_split( '/\r\n|\r|\n/', (string) ( $settings['international_locale_map'] ?? '' ) );
		$result = array();
		foreach ( (array) $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', $line ) );
			if ( empty( $parts[0] ) || ! $this->validate_locale_code( strtolower( $parts[0] ), false ) ) {
				continue;
			}
			$result[] = array(
				'locale'       => strtolower( $parts[0] ),
				'path_prefix'  => isset( $parts[1] ) ? '/' . trim( $parts[1], '/' ) . '/' : '',
				'country'      => sanitize_text_field( $parts[2] ?? '' ),
				'currency'     => strtoupper( sanitize_text_field( $parts[3] ?? '' ) ),
				'phone_prefix' => sanitize_text_field( $parts[4] ?? '' ),
			);
		}
		return $result;
	}

	private function infer_locale_from_url( $url ) {
		$path = trailingslashit( wp_parse_url( $url, PHP_URL_PATH ) ?: '/' );
		foreach ( $this->locale_map() as $entry ) {
			if ( $entry['path_prefix'] && 0 === strpos( $path, $entry['path_prefix'] ) ) {
				return $entry['locale'];
			}
		}
		return '';
	}

	private function regional_signals( $url, $text, $locale ) {
		$signals = array( 'locale' => $locale, 'currency_found' => false, 'phone_prefix_found' => false, 'country_found' => false, 'issues' => array() );
		foreach ( $this->locale_map() as $entry ) {
			if ( ! $locale || ! $this->locale_equivalent( $locale, $entry['locale'] ) ) {
				continue;
			}
			if ( $entry['currency'] ) {
				$signals['currency_found'] = false !== stripos( $text, $entry['currency'] );
			}
			if ( $entry['phone_prefix'] ) {
				$signals['phone_prefix_found'] = false !== strpos( preg_replace( '/\s+/', '', $text ), preg_replace( '/\s+/', '', $entry['phone_prefix'] ) );
			}
			if ( $entry['country'] ) {
				$signals['country_found'] = false !== stripos( $text, $entry['country'] );
			}
			if ( $entry['currency'] && ! $signals['currency_found'] ) {
				$signals['issues'][] = $this->issue( 'missing_regional_currency_signal', 'low', 'The configured regional currency was not found in the visible page text.' );
			}
			break;
		}
		return $signals;
	}

	private function content_group( $path ) {
		$lower = strtolower( $path );
		if ( preg_match( '/\.(?:css|js|map|woff2?|ttf|eot)$/', $lower ) ) { return 'asset'; }
		if ( preg_match( '/\.(?:jpe?g|png|gif|webp|avif|svg|ico)$/', $lower ) ) { return 'image'; }
		if ( preg_match( '/\.(?:pdf|docx?|xlsx?|zip)$/', $lower ) ) { return 'document'; }
		if ( false !== strpos( $lower, '/wp-admin' ) || false !== strpos( $lower, '/wp-login' ) ) { return 'admin'; }
		if ( false !== strpos( $lower, '/feed' ) ) { return 'feed'; }
		if ( false !== strpos( $lower, '/wp-json' ) ) { return 'api'; }
		return 'page';
	}

	private function waste_category( $path, array $query_keys, $status, $group ) {
		if ( $status >= 500 ) { return 'server_error'; }
		if ( 404 === $status || 410 === $status ) { return 'not_found'; }
		if ( $status >= 300 && $status < 400 ) { return 'redirect'; }
		if ( in_array( $group, array( 'admin', 'api', 'feed' ), true ) ) { return $group; }
		if ( $query_keys ) { return 'parameters'; }
		if ( in_array( $group, array( 'asset', 'image' ), true ) ) { return 'resource'; }
		return 'none';
	}

	private function issue( $code, $severity, $message ) {
		return array( 'code' => sanitize_key( $code ), 'severity' => sanitize_key( $severity ), 'message' => sanitize_text_field( $message ) );
	}

	private function unique_issues( array $issues ) {
		$seen = array();
		$result = array();
		foreach ( $issues as $issue ) {
			$key = sanitize_key( $issue['code'] ?? '' ) . '|' . sanitize_text_field( $issue['message'] ?? '' );
			if ( ! $key || isset( $seen[ $key ] ) ) { continue; }
			$seen[ $key ] = true;
			$result[] = $issue;
		}
		return $result;
	}

	private function sanitize_locale_map_text( $text ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
		$clean = array();
		foreach ( (array) $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', wp_strip_all_tags( $line ) ) );
			if ( empty( $parts[0] ) || ! $this->validate_locale_code( strtolower( $parts[0] ), false ) ) { continue; }
			$clean[] = implode( '|', array_slice( $parts, 0, 5 ) );
		}
		return implode( "\n", array_slice( array_unique( $clean ), 0, 100 ) );
	}

	private function same_site_url_or_empty( $url ) {
		$url = esc_url_raw( $url );
		return $url && $this->url_belongs_to_site( $url ) ? $url : '';
	}

	private function url_belongs_to_site( $url ) {
		$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$url_host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return $site_host && $url_host && $site_host === $url_host && in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true );
	}

	private function normalise_url( $url ) {
		$parts = wp_parse_url( esc_url_raw( $url ) );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) { return ''; }
		$scheme = strtolower( $parts['scheme'] ?? 'https' );
		$host = strtolower( $parts['host'] );
		$path = '/' . ltrim( preg_replace( '#/+#', '/', rawurldecode( $parts['path'] ?? '/' ) ), '/' );
		if ( '/' !== $path ) { $path = untrailingslashit( $path ); }
		return $scheme . '://' . $host . $path;
	}

	private function same_normalised_url( $a, $b ) {
		return $this->normalise_url( $a ) && $this->normalise_url( $a ) === $this->normalise_url( $b );
	}

	private function locale_equivalent( $a, $b ) {
		$a = strtolower( trim( explode( ',', (string) $a )[0] ) );
		$b = strtolower( trim( explode( ',', (string) $b )[0] ) );
		if ( $a === $b ) { return true; }
		return strtok( $a, '-' ) === strtok( $b, '-' );
	}

	private function absolute_url( $href, $base ) {
		$href = trim( (string) $href );
		if ( preg_match( '#^https?://#i', $href ) ) { return esc_url_raw( $href ); }
		$base_parts = wp_parse_url( $base );
		if ( ! is_array( $base_parts ) || empty( $base_parts['host'] ) ) { return ''; }
		$origin = ( $base_parts['scheme'] ?? 'https' ) . '://' . $base_parts['host'];
		if ( 0 === strpos( $href, '//' ) ) { return esc_url_raw( ( $base_parts['scheme'] ?? 'https' ) . ':' . $href ); }
		if ( 0 === strpos( $href, '/' ) ) { return esc_url_raw( $origin . $href ); }
		$dir = trailingslashit( dirname( $base_parts['path'] ?? '/' ) );
		return esc_url_raw( $origin . $dir . $href );
	}

	private function fallback_inventory() {
		$posts = get_posts( array( 'post_type' => get_post_types( array( 'public' => true ) ), 'post_status' => 'publish', 'numberposts' => 500, 'orderby' => 'modified', 'order' => 'DESC' ) );
		$result = array();
		foreach ( $posts as $post ) {
			if ( 'attachment' === $post->post_type ) { continue; }
			$result[] = array( 'post_id' => $post->ID, 'title' => get_the_title( $post ), 'url' => get_permalink( $post ), 'priority' => 50 );
		}
		return $result;
	}

	private function get_page_by_url( $url ) {
		global $wpdb;
		if ( ! $this->table_exists( $this->pages_table() ) ) { return array(); }
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->pages_table()} WHERE url_hash = %s", hash( 'sha256', $this->normalise_url( $url ) ) ), ARRAY_A );
		return is_array( $row ) ? $row : array();
	}

	private function tables_ready() {
		return $this->table_exists( $this->pages_table() ) && $this->table_exists( $this->log_events_table() ) && $this->table_exists( $this->imports_table() );
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	private function history_event( $category, $status, $title, $summary, array $details, $user_id = 0, $post_id = 0 ) {
		if ( method_exists( $this->history, 'add' ) ) {
			$this->history->add(
				array(
					'category'        => sanitize_key( $category ),
					'status'          => sanitize_key( $status ),
					'title'           => sanitize_text_field( $title ),
					'summary'         => sanitize_textarea_field( $summary ),
					'details'         => $details,
					'source'          => 'wordpress',
					'related_post_id' => absint( $post_id ),
					'created_by'      => absint( $user_id ),
				)
			);
		}
	}
}
