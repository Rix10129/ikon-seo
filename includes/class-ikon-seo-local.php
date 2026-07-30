<?php

defined( 'ABSPATH' ) || exit;

/**
 * Local SEO records, validation and page-quality safeguards.
 *
 * Location, citation and rank data are always bound to the active Website
 * Profile. A service-area record never creates a customer-facing address or a
 * LocalBusiness entity.
 */
class Ikon_SEO_Local {
	private $profile;
	private $logger;

	public function __construct( Ikon_SEO_Profile $profile, Ikon_SEO_Logger $logger ) {
		$this->profile = $profile;
		$this->logger  = $logger;
	}

	public function register_hooks() {
		add_action( 'ikon_seo_after_merge', array( $this, 'after_merge' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'on_status_change' ), 10, 3 );
	}

	public function summary() {
		$locations = $this->locations();
		$citations = $this->citations( 500 );
		$ranks     = $this->rank_entries( 500 );
		$verified  = 0;
		$service   = 0;

		foreach ( $locations as $location ) {
			if ( ! empty( $location['verified'] ) && ! empty( $location['has_customer_location'] ) ) {
				$verified++;
			}
			if ( 'service_area' === $location['location_type'] ) {
				$service++;
			}
		}

		return array(
			'profile_id'         => $this->profile->fingerprint(),
			'locations'          => count( $locations ),
			'verified_locations' => $verified,
			'service_areas'      => $service,
			'citations'          => count( $citations ),
			'rank_entries'       => count( $ranks ),
			'nap_audit'          => $this->nap_audit(),
		);
	}

	public function locations( $include_inactive = false ) {
		global $wpdb;

		$table   = $wpdb->prefix . 'ikon_seo_locations';
		$profile = $this->profile->fingerprint();
		$sql     = "SELECT * FROM {$table} WHERE profile_id = %s";
		if ( ! $include_inactive ) {
			$sql .= " AND status = 'active'";
		}
		$sql .= ' ORDER BY is_primary DESC, business_name ASC, id ASC';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $profile ), ARRAY_A );

		return array_map( array( $this, 'public_location' ), is_array( $rows ) ? $rows : array() );
	}

	public function location( $id, $include_inactive = false ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ikon_seo_locations';
		$status_sql = $include_inactive ? '' : " AND status = 'active'";
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND profile_id = %s{$status_sql} LIMIT 1",
				absint( $id ),
				$this->profile->fingerprint()
			),
			ARRAY_A
		);

		return is_array( $row ) ? $this->public_location( $row ) : null;
	}

	public function save_location( array $input, $id = 0 ) {
		global $wpdb;

		$clean = $this->sanitize_location( $input );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}

		$table              = $wpdb->prefix . 'ikon_seo_locations';
		$clean['profile_id']= $this->profile->fingerprint();
		$clean['updated_at']= current_time( 'mysql', true );
		if ( ! empty( $clean['page_id'] ) ) {
			$page = get_post( absint( $clean['page_id'] ) );
			if ( ! $page || 'page' !== $page->post_type ) {
				return new WP_Error( 'ikon_seo_local_page', __( 'The assigned landing page must be an existing WordPress page.', 'ikon-seo' ) );
			}
			$collision = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE profile_id = %s AND page_id = %d AND id <> %d LIMIT 1",
					$this->profile->fingerprint(),
					absint( $clean['page_id'] ),
					absint( $id )
				)
			);
			if ( $collision ) {
				return new WP_Error( 'ikon_seo_local_page_collision', __( 'That landing page is already assigned to another local record.', 'ikon-seo' ) );
			}
		}

		if ( $id ) {
			$existing = $this->location( $id, true );
			if ( ! $existing ) {
				return new WP_Error( 'ikon_seo_local_location', __( 'The local location record was not found for this Website Profile.', 'ikon-seo' ) );
			}
			$result = $wpdb->update(
				$table,
				$clean,
				array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() )
			);
			$location_id = absint( $id );
		} else {
			$clean['created_at'] = current_time( 'mysql', true );
			$result = $wpdb->insert( $table, $clean );
			$location_id = absint( $wpdb->insert_id );
		}

		if ( false === $result || ! $location_id ) {
			return new WP_Error( 'ikon_seo_local_save', __( 'The location record could not be saved.', 'ikon-seo' ) );
		}

		if ( ! empty( $clean['is_primary'] ) ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET is_primary = 0 WHERE profile_id = %s AND id <> %d",
					$this->profile->fingerprint(),
					$location_id
				)
			);
		}

		$this->logger->log( 'local_location_save', 'success', 'A profile-bound local location record was saved.' );
		return $this->location( $location_id, true );
	}

	public function delete_location( $id ) {
		global $wpdb;

		$location = $this->location( $id, true );
		if ( ! $location ) {
			return new WP_Error( 'ikon_seo_local_location', __( 'The location record was not found.', 'ikon-seo' ) );
		}
		if ( ! empty( $location['page_id'] ) ) {
			return new WP_Error( 'ikon_seo_local_location_page', __( 'Unassign the linked landing page before deleting this location record.', 'ikon-seo' ) );
		}
		if ( ! empty( $location['gbp_account_name'] ) || ! empty( $location['gbp_location_name'] ) ) {
			return new WP_Error( 'ikon_seo_local_location_gbp', __( 'Unlink this record from Google Business Profile before deleting it.', 'ikon-seo' ) );
		}

		$table  = $wpdb->prefix . 'ikon_seo_locations';
		$result = $wpdb->delete(
			$table,
			array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() )
		);
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_local_delete', __( 'The location record could not be deleted.', 'ikon-seo' ) );
		}
		$this->logger->log( 'local_location_delete', 'success', 'A local location record was deleted.' );
		return true;
	}

	public function link_gbp( $id, $account_name, $location_name, $place_id = '' ) {
		global $wpdb;

		$location = $this->location( $id );
		if ( ! $location ) {
			return new WP_Error( 'ikon_seo_local_location', __( 'The local location record was not found.', 'ikon-seo' ) );
		}
		$account_name  = sanitize_text_field( $account_name );
		$location_name = sanitize_text_field( $location_name );
		if ( ! preg_match( '#^accounts/[0-9]+$#', $account_name ) || ! preg_match( '#^locations/[0-9]+$#', $location_name ) ) {
			return new WP_Error( 'ikon_seo_gbp_resource', __( 'The Google Business Profile account or location resource is invalid.', 'ikon-seo' ) );
		}
		$result = $wpdb->update(
			$wpdb->prefix . 'ikon_seo_locations',
			array(
				'gbp_account_name'  => $account_name,
				'gbp_location_name' => $location_name,
				'place_id'          => sanitize_text_field( $place_id ),
				'updated_at'        => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() )
		);
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_gbp_link', __( 'The Google Business Profile location could not be linked.', 'ikon-seo' ) );
		}
		$this->logger->log( 'gbp_location_link', 'success', 'A profile-bound local record was linked to Google Business Profile.' );
		return $this->location( $id );
	}

	public function unlink_gbp( $id ) {
		global $wpdb;

		if ( ! $this->location( $id, true ) ) {
			return new WP_Error( 'ikon_seo_local_location', __( 'The local location record was not found.', 'ikon-seo' ) );
		}
		$result = $wpdb->update(
			$wpdb->prefix . 'ikon_seo_locations',
			array(
				'gbp_account_name'  => '',
				'gbp_location_name' => '',
				'updated_at'        => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() )
		);
		if ( false === $result ) {
			return new WP_Error( 'ikon_seo_gbp_unlink', __( 'The Google Business Profile location could not be unlinked.', 'ikon-seo' ) );
		}
		$this->reject_gbp_drafts( absint( $id ), 'The linked Google Business Profile location changed before approval.' );
		$this->logger->log( 'gbp_location_unlink', 'success', 'A local record was unlinked from Google Business Profile and pending drafts were rejected.' );
		return $this->location( $id, true );
	}

	public function reject_gbp_drafts( $location_id = 0, $reason = '' ) {
		global $wpdb;

		$where = 'profile_id = %s AND status IN (\'draft\',\'failed\')';
		$args  = array( $this->profile->fingerprint() );
		if ( $location_id ) {
			$where .= ' AND location_id = %d';
			$args[] = absint( $location_id );
		}
		$sql = "UPDATE {$wpdb->prefix}ikon_seo_gbp_drafts SET status = 'rejected', last_error = %s, updated_at = %s WHERE {$where}";
		array_unshift( $args, sanitize_text_field( $reason ?: 'Business Profile approval context changed; recreate this draft after review.' ), current_time( 'mysql', true ) );
		return $wpdb->query( $wpdb->prepare( $sql, $args ) );
	}

	public function rebind_profile( $old_profile_id, $new_profile_id, $old_url = '', $new_url = '' ) {
		global $wpdb;

		$old_profile_id = sanitize_text_field( $old_profile_id );
		$new_profile_id = sanitize_text_field( $new_profile_id );
		if ( ! $old_profile_id || ! $new_profile_id || hash_equals( $old_profile_id, $new_profile_id ) ) {
			return 0;
		}
		$changed = 0;
		foreach ( array( 'ikon_seo_locations', 'ikon_seo_citations', 'ikon_seo_local_ranks', 'ikon_seo_gbp_drafts' ) as $suffix ) {
			$table   = $wpdb->prefix . $suffix;
			$updated = $wpdb->update( $table, array( 'profile_id' => $new_profile_id ), array( 'profile_id' => $old_profile_id ) );
			if ( false !== $updated ) {
				$changed += absint( $updated );
			}
		}
		$wpdb->update(
			$wpdb->prefix . 'ikon_seo_locations',
			array( 'gbp_account_name' => '', 'gbp_location_name' => '', 'updated_at' => current_time( 'mysql', true ) ),
			array( 'profile_id' => $new_profile_id )
		);
		if ( $old_url && $new_url ) {
			$table = $wpdb->prefix . 'ikon_seo_locations';
			foreach ( array( 'website_url', 'appointment_url', 'whatsapp_url', 'image_url', 'logo_url', 'same_as' ) as $field ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$table} SET {$field} = REPLACE({$field}, %s, %s) WHERE profile_id = %s",
						untrailingslashit( esc_url_raw( $old_url ) ),
						untrailingslashit( esc_url_raw( $new_url ) ),
						$new_profile_id
					)
				);
			}
		}
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}ikon_seo_gbp_drafts SET status = 'rejected', last_error = %s, updated_at = %s WHERE profile_id = %s AND status IN ('draft','failed')",
				'Website identity changed before approval; recreate this draft after reviewing the active profile.',
				current_time( 'mysql', true ),
				$new_profile_id
			)
		);
		return $changed;
	}

	public function validate_payload( array $payload ) {
		$local  = is_array( $payload['local'] ?? null ) ? $payload['local'] : array();
		$schema = is_array( $payload['schema'] ?? null ) ? $payload['schema'] : array();
		$type   = sanitize_key( $schema['page_type'] ?? '' );

		if ( ! $local && 'location' !== $type ) {
			return array();
		}

		$errors      = array();
		if ( empty( Ikon_SEO_Plugin::settings()['local_module_enabled'] ) ) {
			return array( 'the Local SEO module is disabled in WordPress settings' );
		}
		$page_kind   = sanitize_key( $local['page_kind'] ?? '' );
		$location_id = absint( $local['location_id'] ?? 0 );
		$location    = $location_id ? $this->location( $location_id ) : null;

		if ( ! in_array( $page_kind, array( 'verified_location', 'service_area' ), true ) ) {
			$errors[] = 'local.page_kind must be verified_location or service_area';
		}
		if ( ! $location ) {
			$errors[] = 'local.location_id must reference an active record in the current Website Profile';
			return $errors;
		}

		if ( 'verified_location' === $page_kind ) {
			if ( empty( $location['verified'] ) || empty( $location['has_customer_location'] ) ) {
				$errors[] = 'verified location pages require a verified customer-facing location record';
			}
			if ( ! in_array( $location['location_type'], array( 'storefront', 'hybrid' ), true ) ) {
				$errors[] = 'verified location pages require a storefront or hybrid location type';
			}
			if ( empty( $location['address']['street'] ) || empty( $location['address']['locality'] ) || empty( $location['address']['country'] ) ) {
				$errors[] = 'verified location pages require a complete street, locality and country';
			}
		}

		if ( 'service_area' === $page_kind ) {
			if ( ! in_array( $location['location_type'], array( 'service_area', 'hybrid' ), true ) ) {
				$errors[] = 'service-area pages require a service-area or hybrid location record';
			}
			if ( empty( $local['target_area'] ) ) {
				$errors[] = 'service-area pages require local.target_area';
			}
			if ( ! empty( $local['show_address'] ) || ! empty( $local['emit_location_entity'] ) ) {
				$errors[] = 'service-area pages cannot show a non-customer address or emit a location entity';
			}
			if ( ! empty( $schema['business_entity'] ) ) {
				$errors[] = 'service-area pages cannot request a LocalBusiness entity';
			}
		}

		$details = array_filter( array_map( 'sanitize_text_field', (array) ( $local['unique_local_details'] ?? array() ) ) );
		if ( count( $details ) < 3 ) {
			$errors[] = 'local.unique_local_details requires at least three genuine location-specific details';
		}
		if ( empty( $local['services'] ) || ! is_array( $local['services'] ) ) {
			$errors[] = 'local.services must identify the services genuinely available in the target area';
		}

		return $errors;
	}

	public function bind_page( $post_id, array $payload ) {
		$local = is_array( $payload['local'] ?? null ) ? $payload['local'] : array();
		if ( ! $local ) {
			delete_post_meta( $post_id, '_ikon_seo_local_config' );
			return;
		}

		$config = array(
			'profile_id'           => $this->profile->fingerprint(),
			'location_id'          => absint( $local['location_id'] ?? 0 ),
			'page_kind'            => sanitize_key( $local['page_kind'] ?? '' ),
			'target_area'          => sanitize_text_field( $local['target_area'] ?? '' ),
			'services'             => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $local['services'] ?? array() ) ) ) ),
			'unique_local_details' => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $local['unique_local_details'] ?? array() ) ) ) ),
			'landmarks'            => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $local['landmarks'] ?? array() ) ) ) ),
			'directions'           => sanitize_textarea_field( $local['directions'] ?? '' ),
			'parking'              => sanitize_textarea_field( $local['parking'] ?? '' ),
			'staff'                => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $local['staff'] ?? array() ) ) ) ),
			'map_url'              => esc_url_raw( $local['map_url'] ?? '' ),
		);
		update_post_meta( $post_id, '_ikon_seo_local_config', $config );
	}

	public function validate_bound_page( $post_id ) {
		$config = get_post_meta( absint( $post_id ), '_ikon_seo_local_config', true );
		if ( ! is_array( $config ) || ! $config ) {
			return array();
		}
		return $this->validate_payload( array( 'local' => $config, 'schema' => array() ) );
	}

	public function quality( array $payload, array $rendered ) {
		$local = is_array( $payload['local'] ?? null ) ? $payload['local'] : array();
		if ( ! $local ) {
			return array(
				'score'             => 100,
				'status'            => 'not_local',
				'critical_failures' => array(),
				'checks'            => array(),
			);
		}

		$location = $this->location( absint( $local['location_id'] ?? 0 ) );
		$kind     = sanitize_key( $local['page_kind'] ?? '' );
		$html     = (string) ( $rendered['post_content'] ?? '' );
		$text     = strtolower( wp_strip_all_tags( $html ) );
		$title    = strtolower( wp_strip_all_tags( ( $payload['seo']['title'] ?? '' ) . ' ' . ( $payload['hero']['title'] ?? $payload['title'] ?? '' ) ) );
		$checks   = array();
		$critical = array();
		$score    = 100;

		$this->quality_check( $checks, $critical, $score, 'local_record', (bool) $location, 'critical', 'The page is bound to a current local profile.', 'The page is not bound to a current local profile.', 30 );

		if ( $location ) {
			$is_real = 'verified_location' === $kind
				? ( ! empty( $location['verified'] ) && ! empty( $location['has_customer_location'] ) && in_array( $location['location_type'], array( 'storefront', 'hybrid' ), true ) )
				: in_array( $location['location_type'], array( 'service_area', 'hybrid' ), true );
			$this->quality_check( $checks, $critical, $score, 'location_eligibility', $is_real, 'critical', 'The selected location is eligible for this page type.', 'A verified location page must use a verified customer-facing location.', 35 );

			$target = strtolower( sanitize_text_field( 'verified_location' === $kind ? $location['address']['locality'] : ( $local['target_area'] ?? '' ) ) );
			$this->quality_check( $checks, $critical, $score, 'local_title', $target && false !== strpos( $title, $target ), 'warning', 'The target location appears in the title or H1.', 'Add the genuine target location to the SEO title or H1.', 6 );

			$phone = $this->normalize_phone( $location['phone'] );
			$this->quality_check( $checks, $critical, $score, 'local_phone', ! $phone || false !== strpos( $this->normalize_phone( $text ), $phone ), 'warning', 'The page uses the location phone number.', 'The location phone number is missing or inconsistent.', 6 );

			if ( 'verified_location' === $kind ) {
				$address_match = true;
				foreach ( array( 'street', 'locality', 'country' ) as $field ) {
					$value = strtolower( sanitize_text_field( $location['address'][ $field ] ?? '' ) );
					if ( $value && false === strpos( $text, $value ) ) {
						$address_match = false;
					}
				}
				$this->quality_check( $checks, $critical, $score, 'visible_nap', $address_match, 'critical', 'The visible address matches the location record.', 'The verified address must be visible and consistent on the page.', 20 );
				$map = esc_url_raw( $local['map_url'] ?? $location['map_url'] );
				$this->quality_check( $checks, $critical, $score, 'map_directions', $map && ! empty( $local['directions'] ), 'warning', 'Map and arrival directions are supplied.', 'Add a valid map URL and useful arrival directions.', 5 );
			} else {
				$no_fake_address = empty( $local['show_address'] ) && empty( $local['emit_location_entity'] );
				$this->quality_check( $checks, $critical, $score, 'service_area_honesty', $no_fake_address, 'critical', 'The service-area page does not claim a customer-facing office.', 'Remove the address or location-entity claim from this service-area page.', 35 );
			}
		}

		$details = array_filter( (array) ( $local['unique_local_details'] ?? array() ) );
		$this->quality_check( $checks, $critical, $score, 'unique_local_detail', count( $details ) >= 3, 'critical', 'At least three genuine local details are recorded.', 'Add at least three genuine local details; city-name swapping is not allowed.', 25 );
		$this->quality_check( $checks, $critical, $score, 'local_services', count( array_filter( (array) ( $local['services'] ?? array() ) ) ) >= 1, 'critical', 'Services available in the area are recorded.', 'Identify services genuinely available in this area.', 20 );

		$similarity = $this->highest_similarity( $text, absint( $payload['source_page_id'] ?? 0 ) );
		$threshold  = max( 0.60, min( 0.95, absint( Ikon_SEO_Plugin::settings()['local_similarity_threshold'] ) / 100 ) );
		$critical_similarity = min( 0.96, $threshold + 0.10 );
		$this->quality_check( $checks, $critical, $score, 'doorway_similarity', $similarity < $threshold, $similarity >= $critical_similarity ? 'critical' : 'warning', 'No highly similar local page was found.', sprintf( 'Local-page similarity is %.0f%%; review doorway-page risk and add genuinely unique information.', $similarity * 100 ), $similarity >= $critical_similarity ? 30 : 10 );

		$score  = max( 0, min( 100, $score ) );
		$status = $critical ? 'needs_changes' : ( count( array_filter( $checks, function( $check ) { return 'warning' === $check['status']; } ) ) ? 'review' : 'ready' );

		return array(
			'score'             => $score,
			'status'            => $status,
			'critical_failures' => array_values( array_unique( $critical ) ),
			'checks'            => $checks,
			'metrics'           => array( 'highest_local_page_similarity' => round( $similarity, 3 ) ),
		);
	}

	public function nap_audit() {
		$locations = $this->locations();
		$items     = array();
		$failures  = 0;
		$warnings  = 0;

		foreach ( $locations as $location ) {
			$checks  = array();
			$page_id = absint( $location['page_id'] );
			$post    = $page_id ? get_post( $page_id ) : null;
			$text    = $post ? strtolower( wp_strip_all_tags( $post->post_content ) ) : '';
			$graph   = $post ? get_post_meta( $page_id, '_ikon_seo_schema_graph', true ) : array();

			$this->nap_check( $checks, 'landing_page', (bool) $post, 'A landing page is assigned.', 'No landing page is assigned.', false );
			if ( $post ) {
				$name_ok = false !== strpos( $text, strtolower( $location['business_name'] ) );
				$phone   = $this->normalize_phone( $location['phone'] );
				$phone_ok= ! $phone || false !== strpos( $this->normalize_phone( $text ), $phone );
				$this->nap_check( $checks, 'business_name', $name_ok, 'Business name matches visible content.', 'Business name is missing or inconsistent.', true );
				$this->nap_check( $checks, 'phone', $phone_ok, 'Phone matches visible content.', 'Phone is missing or inconsistent.', true );

				if ( ! empty( $location['has_customer_location'] ) ) {
					$address_ok = true;
					foreach ( array( 'street', 'locality', 'country' ) as $field ) {
						$value = strtolower( $location['address'][ $field ] ?? '' );
						if ( $value && false === strpos( $text, $value ) ) {
							$address_ok = false;
						}
					}
					$this->nap_check( $checks, 'address', $address_ok, 'Address matches visible content.', 'Address is missing or inconsistent.', true );
				}

				$schema_phone = $this->schema_value( $graph, 'telephone' );
				$schema_ok    = ! $schema_phone || $this->normalize_phone( $schema_phone ) === $phone;
				$this->nap_check( $checks, 'schema_phone', $schema_ok, 'Schema phone matches the master record.', 'Schema phone conflicts with the master record.', true );
			}

			foreach ( $checks as $check ) {
				if ( 'fail' === $check['status'] ) {
					$failures++;
				} elseif ( 'warning' === $check['status'] ) {
					$warnings++;
				}
			}
			$items[] = array(
				'location_id' => $location['id'],
				'name'        => $location['business_name'],
				'page_id'     => $page_id,
				'checks'      => $checks,
			);
		}

		return array(
			'status'       => $failures ? 'needs_changes' : ( $warnings ? 'review' : 'ready' ),
			'failures'     => $failures,
			'warnings'     => $warnings,
			'items'        => $items,
			'generated_at' => current_time( 'mysql', true ),
		);
	}

	public function utm_url( array $input ) {
		$url = esc_url_raw( $input['url'] ?? home_url( '/' ) );
		if ( ! wp_http_validate_url( $url ) || ! $this->same_site( $url ) ) {
			return new WP_Error( 'ikon_seo_local_utm_url', __( 'UTM URLs must use this WordPress website.', 'ikon-seo' ) );
		}

		$params = array(
			'utm_source'   => sanitize_key( $input['source'] ?? 'google' ),
			'utm_medium'   => sanitize_key( $input['medium'] ?? 'organic' ),
			'utm_campaign' => sanitize_title( $input['campaign'] ?? 'google-business-profile' ),
		);
		foreach ( array( 'content', 'term' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$params[ 'utm_' . $field ] = sanitize_title( $input[ $field ] );
			}
		}
		return array( 'url' => add_query_arg( $params, $url ), 'parameters' => $params );
	}

	public function citations( $limit = 100 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ikon_seo_citations';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE profile_id = %s ORDER BY correction_required DESC, next_review ASC, id DESC LIMIT %d",
				$this->profile->fingerprint(),
				max( 1, min( 1000, absint( $limit ) ) )
			),
			ARRAY_A
		);
		return array_map( array( $this, 'public_citation' ), is_array( $rows ) ? $rows : array() );
	}

	public function import_citations_csv( $path ) {
		return $this->import_csv(
			$path,
			array( 'directory_name' ),
			function( $row ) {
				return $this->save_citation( $row );
			}
		);
	}

	public function import_ranks_csv( $path ) {
		return $this->import_csv(
			$path,
			array( 'keyword', 'search_location' ),
			function( $row ) {
				if ( ! empty( $row['competitors'] ) && ! is_array( $row['competitors'] ) ) {
					$row['competitors'] = preg_split( '/\s*\|\s*/', $row['competitors'] );
				}
				return $this->save_rank( $row );
			}
		);
	}

	public function save_citation( array $input, $id = 0 ) {
		global $wpdb;

		$directory = sanitize_text_field( $input['directory_name'] ?? '' );
		$url       = esc_url_raw( $input['listing_url'] ?? '' );
		if ( ! $directory || ( $url && ! wp_http_validate_url( $url ) ) ) {
			return new WP_Error( 'ikon_seo_citation', __( 'Directory name is required and the listing URL must be valid.', 'ikon-seo' ) );
		}
		$location_id = absint( $input['location_id'] ?? 0 );
		if ( $location_id && ! $this->location( $location_id ) ) {
			return new WP_Error( 'ikon_seo_citation_location', __( 'The citation location is not part of the active Website Profile.', 'ikon-seo' ) );
		}

		$data = array(
			'profile_id'          => $this->profile->fingerprint(),
			'location_id'         => $location_id,
			'directory_name'      => $directory,
			'listing_url'         => $url,
			'business_name'       => sanitize_text_field( $input['business_name'] ?? '' ),
			'address'             => sanitize_textarea_field( $input['address'] ?? '' ),
			'phone'               => sanitize_text_field( $input['phone'] ?? '' ),
			'status'              => in_array( sanitize_key( $input['status'] ?? '' ), array( 'live', 'pending', 'missing', 'duplicate', 'closed' ), true ) ? sanitize_key( $input['status'] ) : 'pending',
			'login_owner'         => sanitize_text_field( $input['login_owner'] ?? '' ),
			'last_checked'        => $this->valid_date( $input['last_checked'] ?? '' ),
			'next_review'         => $this->valid_date( $input['next_review'] ?? '' ),
			'duplicate_warning'   => ! empty( $input['duplicate_warning'] ) ? 1 : 0,
			'correction_required' => ! empty( $input['correction_required'] ) ? 1 : 0,
			'notes'               => sanitize_textarea_field( $input['notes'] ?? '' ),
			'updated_at'          => current_time( 'mysql', true ),
		);
		$table = $wpdb->prefix . 'ikon_seo_citations';
		if ( $id ) {
			$result = $wpdb->update( $table, $data, array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() ) );
			$saved  = absint( $id );
		} else {
			$data['created_at'] = current_time( 'mysql', true );
			$result = $wpdb->insert( $table, $data );
			$saved  = absint( $wpdb->insert_id );
		}
		return false === $result ? new WP_Error( 'ikon_seo_citation_save', __( 'The citation record could not be saved.', 'ikon-seo' ) ) : $saved;
	}

	public function delete_citation( $id ) {
		global $wpdb;
		return false !== $wpdb->delete(
			$wpdb->prefix . 'ikon_seo_citations',
			array( 'id' => absint( $id ), 'profile_id' => $this->profile->fingerprint() )
		);
	}

	public function rank_entries( $limit = 100 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ikon_seo_local_ranks';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE profile_id = %s ORDER BY checked_date DESC, id DESC LIMIT %d",
				$this->profile->fingerprint(),
				max( 1, min( 1000, absint( $limit ) ) )
			),
			ARRAY_A
		);
		return array_map( array( $this, 'public_rank' ), is_array( $rows ) ? $rows : array() );
	}

	public function save_rank( array $input ) {
		global $wpdb;

		$keyword = sanitize_text_field( $input['keyword'] ?? '' );
		$area    = sanitize_text_field( $input['search_location'] ?? '' );
		if ( ! $keyword || ! $area ) {
			return new WP_Error( 'ikon_seo_local_rank', __( 'Keyword and search location are required.', 'ikon-seo' ) );
		}
		$location_id = absint( $input['location_id'] ?? 0 );
		if ( $location_id && ! $this->location( $location_id ) ) {
			return new WP_Error( 'ikon_seo_local_rank_location', __( 'The rank location is not part of the active Website Profile.', 'ikon-seo' ) );
		}
		$device = sanitize_key( $input['device'] ?? 'mobile' );
		if ( ! in_array( $device, array( 'mobile', 'desktop' ), true ) ) {
			return new WP_Error( 'ikon_seo_local_rank_device', __( 'Rank device must be mobile or desktop.', 'ikon-seo' ) );
		}
		$engine = sanitize_key( $input['search_engine'] ?? 'google' );
		if ( 'google' !== $engine ) {
			return new WP_Error( 'ikon_seo_local_rank_engine', __( 'This release accepts Google rank observations only.', 'ikon-seo' ) );
		}
		$competitors = $input['competitors'] ?? array();
		if ( ! is_array( $competitors ) ) {
			$competitors = preg_split( '/\s*\|\s*/', (string) $competitors );
		}
		$checked_input = $input['checked_date'] ?? gmdate( 'Y-m-d' );
		$checked       = $this->valid_date( $checked_input );
		if ( ! $checked ) {
			return new WP_Error( 'ikon_seo_local_rank_date', __( 'Rank checked date must use YYYY-MM-DD.', 'ikon-seo' ) );
		}
		$table   = $wpdb->prefix . 'ikon_seo_local_ranks';
		$previous= $wpdb->get_row(
			$wpdb->prepare(
				"SELECT organic_position, local_pack_position FROM {$table} WHERE profile_id = %s AND keyword = %s AND search_location = %s ORDER BY checked_date DESC, id DESC LIMIT 1",
				$this->profile->fingerprint(),
				$keyword,
				$area
			),
			ARRAY_A
		);
		$data = array(
			'profile_id'             => $this->profile->fingerprint(),
			'location_id'            => $location_id,
			'keyword'                => $keyword,
			'search_location'        => $area,
			'device'                 => $device,
			'search_engine'          => $engine,
			'organic_position'       => $this->position( $input['organic_position'] ?? null ),
			'local_pack_position'    => $this->position( $input['local_pack_position'] ?? null ),
			'previous_organic'       => $previous ? $this->position( $previous['organic_position'] ) : null,
			'previous_local_pack'    => $previous ? $this->position( $previous['local_pack_position'] ) : null,
			'competitors'            => wp_json_encode( array_values( array_filter( array_map( 'sanitize_text_field', (array) $competitors ) ) ) ),
			'checked_date'           => $checked,
			'source'                 => sanitize_text_field( $input['source'] ?? 'manual_import' ),
			'created_at'             => current_time( 'mysql', true ),
		);
		$result = $wpdb->insert( $table, $data );
		return false === $result ? new WP_Error( 'ikon_seo_local_rank_save', __( 'The rank entry could not be saved.', 'ikon-seo' ) ) : absint( $wpdb->insert_id );
	}

	public function local_schema( array $payload, $url, $description ) {
		$local = is_array( $payload['local'] ?? null ) ? $payload['local'] : array();
		if ( 'verified_location' !== sanitize_key( $local['page_kind'] ?? '' ) ) {
			return array();
		}
		$location = $this->location( absint( $local['location_id'] ?? 0 ) );
		if ( ! $location || empty( $location['verified'] ) || empty( $location['has_customer_location'] ) ) {
			return array();
		}

		$type    = sanitize_text_field( $location['entity_type'] );
		$allowed = $this->profile->allowed_entity_types( Ikon_SEO_Plugin::settings()['industry'] );
		if ( ! in_array( $type, $allowed, true ) || ! $this->profile->entity_requires_address( $type ) ) {
			$type = in_array( 'LocalBusiness', $allowed, true ) ? 'LocalBusiness' : '';
		}
		if ( ! $type ) {
			return array();
		}

		$node = array(
			'@type'       => $type,
			'@id'         => $url . '#localbusiness',
			'name'        => $location['business_name'],
			'description' => wp_strip_all_tags( $description ),
			'url'         => $url,
			'telephone'   => $location['phone'],
			'email'       => $location['email'],
			'priceRange'  => $location['price_range'],
			'image'       => $location['image_url'],
			'logo'        => $location['logo_url'],
			'hasMap'      => $location['map_url'],
			'address'     => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $location['address']['street'],
				'addressLocality' => $location['address']['locality'],
				'addressRegion'   => $location['address']['region'],
				'postalCode'      => $location['address']['postal'],
				'addressCountry'  => $location['address']['country'],
			),
		);
		if ( defined( 'RANK_MATH_VERSION' ) || ! empty( Ikon_SEO_Plugin::settings()['allow_entity_schema'] ) ) {
			$node['branchOf'] = array( '@id' => home_url( '/#organization' ) );
		}
		if ( is_numeric( $location['latitude'] ) && is_numeric( $location['longitude'] ) ) {
			$node['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $location['latitude'],
				'longitude' => (float) $location['longitude'],
			);
		}
		$hours = $this->opening_hours_schema( $location['opening_hours'] );
		if ( $hours ) {
			$node['openingHoursSpecification'] = $hours;
		}
		if ( $location['service_areas'] ) {
			$node['areaServed'] = array_map(
				function( $area ) {
					return array( '@type' => 'Place', 'name' => sanitize_text_field( $area ) );
				},
				$location['service_areas']
			);
		}
		if ( $location['same_as'] ) {
			$node['sameAs'] = $location['same_as'];
		}
		if ( $location['appointment_url'] ) {
			$node['potentialAction'] = array(
				'@type'  => 'ReserveAction',
				'target' => $location['appointment_url'],
			);
		}
		return $this->strip_empty( $node );
	}

	public function after_merge( $source_id, $draft_id ) {
		$config = get_post_meta( $draft_id, '_ikon_seo_local_config', true );
		if ( ! is_array( $config ) || empty( $config['location_id'] ) || 'verified_location' !== ( $config['page_kind'] ?? '' ) ) {
			return;
		}
		$this->assign_page( absint( $config['location_id'] ), absint( $source_id ) );
	}

	public function on_status_change( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
			return;
		}
		$config = get_post_meta( $post->ID, '_ikon_seo_local_config', true );
		if ( is_array( $config ) && ! empty( $config['location_id'] ) && 'verified_location' === ( $config['page_kind'] ?? '' ) ) {
			$this->assign_page( absint( $config['location_id'] ), $post->ID );
		}
	}

	private function assign_page( $location_id, $page_id ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ikon_seo_locations',
			array( 'page_id' => absint( $page_id ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => absint( $location_id ), 'profile_id' => $this->profile->fingerprint() )
		);
	}

	private function sanitize_location( array $input ) {
		$type = sanitize_key( $input['location_type'] ?? 'storefront' );
		if ( ! in_array( $type, array( 'storefront', 'service_area', 'hybrid', 'online' ), true ) ) {
			return new WP_Error( 'ikon_seo_local_type', __( 'Select storefront, service-area, hybrid or online.', 'ikon-seo' ) );
		}
		$name = sanitize_text_field( $input['business_name'] ?? '' );
		if ( ! $name ) {
			return new WP_Error( 'ikon_seo_local_name', __( 'A business or location name is required.', 'ikon-seo' ) );
		}

		$customer = ! empty( $input['has_customer_location'] ) && in_array( $type, array( 'storefront', 'hybrid' ), true );
		$verified = ! empty( $input['verified'] ) && $customer;
		$street   = sanitize_text_field( $input['address_street'] ?? '' );
		$locality = sanitize_text_field( $input['address_locality'] ?? '' );
		$country  = strtoupper( sanitize_text_field( $input['address_country'] ?? '' ) );
		if ( $country && ! preg_match( '/^[A-Z]{2}$/', $country ) ) {
			return new WP_Error( 'ikon_seo_local_country', __( 'Country must use a two-letter ISO code.', 'ikon-seo' ) );
		}
		if ( $verified && ( ! $street || ! $locality || ! $country ) ) {
			return new WP_Error( 'ikon_seo_local_address', __( 'A verified customer-facing location requires street, locality and country.', 'ikon-seo' ) );
		}
		$latitude_input  = trim( (string) ( $input['latitude'] ?? '' ) );
		$longitude_input = trim( (string) ( $input['longitude'] ?? '' ) );
		if ( ( $latitude_input && ! is_numeric( $latitude_input ) ) || ( $longitude_input && ! is_numeric( $longitude_input ) ) ) {
			return new WP_Error( 'ikon_seo_local_coordinates', __( 'Latitude and longitude must be numeric.', 'ikon-seo' ) );
		}
		$latitude  = '' !== $latitude_input ? (float) $latitude_input : null;
		$longitude = '' !== $longitude_input ? (float) $longitude_input : null;
		if ( null !== $latitude && ( $latitude < -90 || $latitude > 90 ) ) {
			return new WP_Error( 'ikon_seo_local_latitude', __( 'Latitude must be between -90 and 90.', 'ikon-seo' ) );
		}
		if ( null !== $longitude && ( $longitude < -180 || $longitude > 180 ) ) {
			return new WP_Error( 'ikon_seo_local_longitude', __( 'Longitude must be between -180 and 180.', 'ikon-seo' ) );
		}
		if ( ( null === $latitude ) !== ( null === $longitude ) ) {
			return new WP_Error( 'ikon_seo_local_coordinates', __( 'Enter both latitude and longitude or leave both blank.', 'ikon-seo' ) );
		}

		$entity  = sanitize_text_field( $input['entity_type'] ?? Ikon_SEO_Plugin::settings()['business_entity_type'] );
		$allowed = $this->profile->allowed_entity_types( Ikon_SEO_Plugin::settings()['industry'] );
		if ( ! in_array( $entity, $allowed, true ) ) {
			$entity = in_array( 'LocalBusiness', $allowed, true ) ? 'LocalBusiness' : 'Organization';
		}
		if ( $customer && ! $this->profile->entity_requires_address( $entity ) ) {
			$entity = in_array( 'LocalBusiness', $allowed, true ) ? 'LocalBusiness' : $entity;
		}

		return array(
			'status'                => in_array( sanitize_key( $input['status'] ?? '' ), array( 'active', 'inactive' ), true ) ? sanitize_key( $input['status'] ) : 'active',
			'location_type'         => $type,
			'business_name'         => $name,
			'location_label'        => sanitize_text_field( $input['location_label'] ?? '' ),
			'entity_type'           => $entity,
			'phone'                 => sanitize_text_field( $input['phone'] ?? '' ),
			'email'                 => sanitize_email( $input['email'] ?? '' ),
			'website_url'           => esc_url_raw( $input['website_url'] ?? home_url( '/' ) ),
			'appointment_url'       => esc_url_raw( $input['appointment_url'] ?? '' ),
			'whatsapp_url'          => esc_url_raw( $input['whatsapp_url'] ?? '' ),
			'address_street'        => $customer ? $street : '',
			'address_locality'      => $customer ? $locality : '',
			'address_region'        => $customer ? sanitize_text_field( $input['address_region'] ?? '' ) : '',
			'address_postal'        => $customer ? sanitize_text_field( $input['address_postal'] ?? '' ) : '',
			'address_country'       => $customer ? $country : '',
			'latitude'              => $customer && null !== $latitude ? (string) $latitude : '',
			'longitude'             => $customer && null !== $longitude ? (string) $longitude : '',
			'opening_hours'         => wp_json_encode( $this->sanitize_lines( $input['opening_hours'] ?? array() ) ),
			'special_hours'         => wp_json_encode( $this->sanitize_lines( $input['special_hours'] ?? array() ) ),
			'primary_category'      => sanitize_text_field( $input['primary_category'] ?? '' ),
			'additional_categories'=> wp_json_encode( $this->sanitize_lines( $input['additional_categories'] ?? array() ) ),
			'service_areas'         => wp_json_encode( $this->sanitize_lines( $input['service_areas'] ?? array() ) ),
			'services'              => wp_json_encode( $this->sanitize_lines( $input['services'] ?? array() ) ),
			'place_id'              => sanitize_text_field( $input['place_id'] ?? '' ),
			'gbp_account_name'      => sanitize_text_field( $input['gbp_account_name'] ?? '' ),
			'gbp_location_name'     => sanitize_text_field( $input['gbp_location_name'] ?? '' ),
			'map_url'               => esc_url_raw( $input['map_url'] ?? '' ),
			'price_range'           => sanitize_text_field( $input['price_range'] ?? '' ),
			'image_url'             => esc_url_raw( $input['image_url'] ?? '' ),
			'logo_url'              => esc_url_raw( $input['logo_url'] ?? '' ),
			'same_as'               => wp_json_encode( array_values( array_filter( array_map( 'esc_url_raw', $this->lines( $input['same_as'] ?? array() ) ) ) ) ),
			'page_id'               => absint( $input['page_id'] ?? 0 ),
			'has_customer_location' => $customer ? 1 : 0,
			'verified'              => $verified ? 1 : 0,
			'is_primary'            => ! empty( $input['is_primary'] ) ? 1 : 0,
		);
	}

	private function public_location( array $row ) {
		return array(
			'id'                    => absint( $row['id'] ?? 0 ),
			'status'                => sanitize_key( $row['status'] ?? '' ),
			'location_type'         => sanitize_key( $row['location_type'] ?? '' ),
			'business_name'         => sanitize_text_field( $row['business_name'] ?? '' ),
			'location_label'        => sanitize_text_field( $row['location_label'] ?? '' ),
			'entity_type'           => sanitize_text_field( $row['entity_type'] ?? 'LocalBusiness' ),
			'phone'                 => sanitize_text_field( $row['phone'] ?? '' ),
			'email'                 => sanitize_email( $row['email'] ?? '' ),
			'website_url'           => esc_url_raw( $row['website_url'] ?? '' ),
			'appointment_url'       => esc_url_raw( $row['appointment_url'] ?? '' ),
			'whatsapp_url'          => esc_url_raw( $row['whatsapp_url'] ?? '' ),
			'address'               => array(
				'street'   => sanitize_text_field( $row['address_street'] ?? '' ),
				'locality' => sanitize_text_field( $row['address_locality'] ?? '' ),
				'region'   => sanitize_text_field( $row['address_region'] ?? '' ),
				'postal'   => sanitize_text_field( $row['address_postal'] ?? '' ),
				'country'  => sanitize_text_field( $row['address_country'] ?? '' ),
			),
			'latitude'              => sanitize_text_field( $row['latitude'] ?? '' ),
			'longitude'             => sanitize_text_field( $row['longitude'] ?? '' ),
			'opening_hours'         => $this->decode_list( $row['opening_hours'] ?? '' ),
			'special_hours'         => $this->decode_list( $row['special_hours'] ?? '' ),
			'primary_category'      => sanitize_text_field( $row['primary_category'] ?? '' ),
			'additional_categories'=> $this->decode_list( $row['additional_categories'] ?? '' ),
			'service_areas'         => $this->decode_list( $row['service_areas'] ?? '' ),
			'services'              => $this->decode_list( $row['services'] ?? '' ),
			'place_id'              => sanitize_text_field( $row['place_id'] ?? '' ),
			'gbp_account_name'      => sanitize_text_field( $row['gbp_account_name'] ?? '' ),
			'gbp_location_name'     => sanitize_text_field( $row['gbp_location_name'] ?? '' ),
			'map_url'               => esc_url_raw( $row['map_url'] ?? '' ),
			'price_range'           => sanitize_text_field( $row['price_range'] ?? '' ),
			'image_url'             => esc_url_raw( $row['image_url'] ?? '' ),
			'logo_url'              => esc_url_raw( $row['logo_url'] ?? '' ),
			'same_as'               => $this->decode_list( $row['same_as'] ?? '' ),
			'page_id'               => absint( $row['page_id'] ?? 0 ),
			'page_url'              => ! empty( $row['page_id'] ) ? get_permalink( absint( $row['page_id'] ) ) : '',
			'has_customer_location' => ! empty( $row['has_customer_location'] ),
			'verified'              => ! empty( $row['verified'] ),
			'is_primary'            => ! empty( $row['is_primary'] ),
			'updated_at'            => sanitize_text_field( $row['updated_at'] ?? '' ),
		);
	}

	private function public_citation( array $row ) {
		foreach ( array( 'id', 'location_id', 'duplicate_warning', 'correction_required' ) as $field ) {
			$row[ $field ] = absint( $row[ $field ] ?? 0 );
		}
		foreach ( array( 'listing_url' ) as $field ) {
			$row[ $field ] = esc_url_raw( $row[ $field ] ?? '' );
		}
		unset( $row['profile_id'] );
		return $row;
	}

	private function public_rank( array $row ) {
		unset( $row['profile_id'] );
		$row['id']         = absint( $row['id'] ?? 0 );
		$row['location_id']= absint( $row['location_id'] ?? 0 );
		$row['competitors']= $this->decode_list( $row['competitors'] ?? '' );
		foreach ( array( 'organic_position', 'local_pack_position', 'previous_organic', 'previous_local_pack' ) as $field ) {
			$row[ $field ] = null === $row[ $field ] || '' === $row[ $field ] ? null : (float) $row[ $field ];
		}
		return $row;
	}

	private function import_csv( $path, array $required, $callback ) {
		$handle = fopen( $path, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'ikon_seo_local_csv', __( 'The CSV file could not be opened.', 'ikon-seo' ) );
		}
		$header = fgetcsv( $handle );
		if ( ! is_array( $header ) ) {
			fclose( $handle );
			return new WP_Error( 'ikon_seo_local_csv', __( 'The CSV header row is missing.', 'ikon-seo' ) );
		}
		$header = array_map( 'sanitize_key', $header );
		foreach ( $required as $field ) {
			if ( ! in_array( $field, $header, true ) ) {
				fclose( $handle );
				return new WP_Error( 'ikon_seo_local_csv_header', sprintf( __( 'The CSV must include a %s column.', 'ikon-seo' ), $field ) );
			}
		}

		$inserted = 0;
		$skipped  = 0;
		$errors   = array();
		while ( ( $values = fgetcsv( $handle ) ) !== false && ( $inserted + $skipped ) < 1000 ) {
			$values = array_pad( $values, count( $header ), '' );
			$row    = array_combine( $header, array_slice( $values, 0, count( $header ) ) );
			if ( ! is_array( $row ) || ! array_filter( $row, 'strlen' ) ) {
				$skipped++;
				continue;
			}
			$result = call_user_func( $callback, $row );
			if ( is_wp_error( $result ) ) {
				$skipped++;
				if ( count( $errors ) < 10 ) {
					$errors[] = $result->get_error_message();
				}
			} else {
				$inserted++;
			}
		}
		fclose( $handle );
		return array( 'inserted' => $inserted, 'skipped' => $skipped, 'errors' => $errors );
	}

	private function quality_check( array &$checks, array &$critical, &$score, $id, $passed, $severity, $pass, $issue, $penalty ) {
		$status = $passed ? 'pass' : ( 'critical' === $severity ? 'fail' : 'warning' );
		$checks[] = array(
			'id'       => sanitize_key( $id ),
			'status'   => $status,
			'severity' => $severity,
			'message'  => $passed ? $pass : $issue,
		);
		if ( ! $passed ) {
			$score -= absint( $penalty );
			if ( 'critical' === $severity ) {
				$critical[] = sanitize_key( $id );
			}
		}
	}

	private function nap_check( array &$checks, $id, $passed, $pass, $issue, $critical ) {
		$checks[] = array(
			'id'       => sanitize_key( $id ),
			'status'   => $passed ? 'pass' : ( $critical ? 'fail' : 'warning' ),
			'message'  => $passed ? $pass : $issue,
		);
	}

	private function highest_similarity( $text, $exclude_id = 0 ) {
		$current = $this->tokens( $text );
		if ( count( $current ) < 80 ) {
			return 0;
		}
		$posts = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'posts_per_page' => 50,
				'post__not_in'   => $exclude_id ? array( $exclude_id ) : array(),
				'meta_query'     => array(
					array( 'key' => '_ikon_seo_local_config', 'compare' => 'EXISTS' ),
				),
			)
		);
		$highest = 0;
		foreach ( $posts as $post ) {
			$other = $this->tokens( wp_strip_all_tags( $post->post_content ) );
			if ( count( $other ) < 80 ) {
				continue;
			}
			$intersection = array_intersect_key( $current, $other );
			$union        = $current + $other;
			$similarity   = $union ? count( $intersection ) / count( $union ) : 0;
			$highest      = max( $highest, $similarity );
		}
		return $highest;
	}

	private function tokens( $text ) {
		$text  = function_exists( 'mb_strtolower' ) ? mb_strtolower( (string) $text, 'UTF-8' ) : strtolower( (string) $text );
		$words = preg_split( '/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		$stop  = array_flip( array( 'the', 'and', 'for', 'with', 'that', 'this', 'from', 'your', 'our', 'you', 'are', 'was', 'were', 'will', 'have', 'has', 'into', 'about', 'can', 'all', 'not', 'but' ) );
		$out   = array();
		foreach ( $words as $word ) {
			if ( strlen( $word ) < 4 || isset( $stop[ $word ] ) ) {
				continue;
			}
			$out[ $word ] = true;
		}
		return $out;
	}

	private function schema_value( $graph, $key ) {
		foreach ( is_array( $graph ) ? $graph : array() as $node ) {
			if ( is_array( $node ) && isset( $node[ $key ] ) && is_scalar( $node[ $key ] ) ) {
				return (string) $node[ $key ];
			}
		}
		return '';
	}

	private function opening_hours_schema( array $lines ) {
		$output = array();
		$day_map = array(
			'mo' => 'Monday',
			'tu' => 'Tuesday',
			'we' => 'Wednesday',
			'th' => 'Thursday',
			'fr' => 'Friday',
			'sa' => 'Saturday',
			'su' => 'Sunday',
		);
		$day_keys = array_keys( $day_map );
		foreach ( $lines as $line ) {
			if ( ! preg_match( '/^([A-Za-z]{2})(?:-([A-Za-z]{2}))?\s+([0-2]\d:[0-5]\d)-([0-2]\d:[0-5]\d)$/', trim( $line ), $match ) ) {
				continue;
			}
			$start_key = strtolower( $match[1] );
			$end_key   = strtolower( $match[2] ?? '' );
			if ( ! isset( $day_map[ $start_key ] ) || ( $end_key && ! isset( $day_map[ $end_key ] ) ) ) {
				continue;
			}
			$days = array( $day_map[ $start_key ] );
			if ( ! empty( $match[2] ) ) {
				$start_index = array_search( $start_key, $day_keys, true );
				$end_index   = array_search( $end_key, $day_keys, true );
				if ( false !== $start_index && false !== $end_index && $end_index >= $start_index ) {
					$days = array();
					for ( $index = $start_index; $index <= $end_index; $index++ ) {
						$days[] = $day_map[ $day_keys[ $index ] ];
					}
				} else {
					$days[] = $day_map[ $end_key ];
				}
			}
			$output[] = array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => $days,
				'opens'     => $match[3],
				'closes'    => $match[4],
			);
		}
		return $output;
	}

	private function sanitize_lines( $value ) {
		return array_values( array_filter( array_map( 'sanitize_text_field', $this->lines( $value ) ) ) );
	}

	private function lines( $value ) {
		if ( is_array( $value ) ) {
			return $value;
		}
		return preg_split( '/\r\n|\r|\n/', (string) $value );
	}

	private function decode_list( $value ) {
		$data = json_decode( (string) $value, true );
		return is_array( $data ) ? array_values( $data ) : array();
	}

	private function normalize_phone( $value ) {
		return preg_replace( '/[^0-9]+/', '', (string) $value );
	}

	private function same_site( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$site = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		return $host && $site && hash_equals( $site, $host );
	}

	private function valid_date( $date ) {
		$date = sanitize_text_field( $date );
		if ( ! $date ) {
			return null;
		}
		$parsed = DateTime::createFromFormat( 'Y-m-d', $date );
		return $parsed && $parsed->format( 'Y-m-d' ) === $date ? $date : null;
	}

	private function position( $value ) {
		if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
			return null;
		}
		return max( 0, min( 999, (float) $value ) );
	}

	private function strip_empty( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		foreach ( $value as $key => $item ) {
			if ( is_array( $item ) ) {
				$item = $this->strip_empty( $item );
			}
			if ( '' === $item || null === $item || array() === $item ) {
				unset( $value[ $key ] );
			} else {
				$value[ $key ] = $item;
			}
		}
		return $value;
	}
}
