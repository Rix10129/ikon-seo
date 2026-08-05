<?php

defined( 'ABSPATH' ) || exit;

/**
 * Stores fact-level discovery decisions without allowing a rescan to silently
 * replace previously confirmed business information.
 */
final class Ikon_SEO_Discovery_Review {
	const OPTION_KEY = 'ikon_seo_discovery_review_v1';
	const VERSION    = '1.0';

	private $auto_discovery;
	private $profile;
	private $strategy;
	private $inventory;
	private $local;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Auto_Discovery $auto_discovery,
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Strategy $strategy,
		Ikon_SEO_Inventory $inventory,
		Ikon_SEO_Local $local,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->auto_discovery = $auto_discovery;
		$this->profile        = $profile;
		$this->strategy       = $strategy;
		$this->inventory      = $inventory;
		$this->local          = $local;
		$this->history        = $history;
		$this->logger         = $logger;
	}

	public function register_hooks() {
		add_action( 'ikon_seo_auto_discovery_completed', array( $this, 'reconcile' ), 10, 2 );
	}

	public function report() {
		$discovery = $this->auto_discovery->report();
		$state     = $this->state();
		if ( ! empty( $discovery['generated_at'] ) && sanitize_text_field( $state['generated_at'] ?? '' ) !== sanitize_text_field( $discovery['generated_at'] ?? '' ) ) {
			$this->reconcile( $discovery, $discovery );
			$state = $this->state();
		}
		$facts     = array();
		$groups    = array();
		$sections  = array();

		foreach ( (array) ( $discovery['facts'] ?? array() ) as $fact ) {
			$id     = sanitize_text_field( $fact['id'] ?? '' );
			$record = isset( $state['facts'][ $id ] ) && is_array( $state['facts'][ $id ] ) ? $state['facts'][ $id ] : $this->default_record( $fact, $discovery );
			$item   = array_merge( $fact, $record );
			$item['category'] = $this->category_for_fact( $fact );
			$item['approved_value'] = array_key_exists( 'approved_value', $record ) ? $record['approved_value'] : null;
			$facts[] = $item;
			$group = sanitize_key( $fact['group'] ?? 'other' );
			if ( ! isset( $groups[ $group ] ) ) {
				$groups[ $group ] = array();
			}
			$groups[ $group ][] = $item;
			$category = $item['category'];
			if ( ! isset( $sections[ $category ] ) ) {
				$sections[ $category ] = array();
			}
			$sections[ $category ][] = $item;
		}

		$conflicts = array();
		foreach ( (array) ( $discovery['conflicts'] ?? array() ) as $conflict ) {
			$key = $this->conflict_key( $conflict );
			$record = isset( $state['conflicts'][ $key ] ) && is_array( $state['conflicts'][ $key ] ) ? $state['conflicts'][ $key ] : array();
			$conflicts[] = array_merge(
				$conflict,
				array(
					'id'             => $key,
					'status'         => sanitize_key( $record['status'] ?? 'unresolved' ),
					'selected_value' => sanitize_text_field( $record['selected_value'] ?? '' ),
					'custom_value'   => sanitize_text_field( $record['custom_value'] ?? '' ),
					'updated_at'     => sanitize_text_field( $record['updated_at'] ?? '' ),
					'updated_by'     => absint( $record['updated_by'] ?? 0 ),
				)
			);
		}

		$counts = array_fill_keys( $this->statuses(), 0 );
		foreach ( $facts as $fact ) {
			$status = sanitize_key( $fact['status'] ?? 'detected' );
			if ( ! isset( $counts[ $status ] ) ) {
				$counts[ $status ] = 0;
			}
			$counts[ $status ]++;
		}

		$unresolved = count( array_filter( $conflicts, function( $item ) { return 'resolved' !== ( $item['status'] ?? '' ); } ) );

		return array(
			'version'       => self::VERSION,
			'generated_at'  => sanitize_text_field( $discovery['generated_at'] ?? '' ),
			'facts'         => $facts,
			'groups'        => $groups,
			'sections'      => $sections,
			'conflicts'     => $conflicts,
			'counts'        => $counts,
			'rescan'        => (array) ( $state['rescan'] ?? array() ),
			'archive'       => array_values( (array) ( $state['archive'] ?? array() ) ),
			'ready'         => ! empty( $discovery['generated_at'] ) && 0 === $unresolved && 0 === absint( $counts['needs_confirmation'] ?? 0 ) && 0 === absint( $counts['outdated'] ?? 0 ),
			'unresolved_conflicts' => $unresolved,
			'safety'        => array(
				'publishes_content'  => false,
				'changes_live_pages' => false,
				'changes_redirects'  => false,
				'changes_indexation' => false,
			),
		);
	}

	public function reconcile( $report, $previous_report = array() ) {
		$report          = is_array( $report ) ? $report : array();
		$previous_report = is_array( $previous_report ) ? $previous_report : array();
		$state           = $this->state();
		$old_records     = (array) ( $state['facts'] ?? array() );
		$new_records     = array();
		$new_ids         = array();
		$added           = 0;
		$changed         = 0;
		$unchanged       = 0;
		$outdated        = 0;

		$legacy_applied = array_flip( (array) ( $previous_report['application']['applied_fields'] ?? array() ) );

		foreach ( (array) ( $report['facts'] ?? array() ) as $fact ) {
			$id   = sanitize_text_field( $fact['id'] ?? '' );
			$hash = $this->fact_hash( $fact );
			$new_ids[ $id ] = true;
			if ( ! isset( $old_records[ $id ] ) ) {
				$record = $this->default_record( $fact, $report );
				if ( isset( $legacy_applied[ $id ] ) ) {
					$record['status']         = 'confirmed';
					$record['approved_value'] = $fact['value'] ?? '';
					$record['updated_at']     = sanitize_text_field( $previous_report['application']['applied_at'] ?? '' );
				}
				$new_records[ $id ] = $record;
				$added++;
				continue;
			}

			$record = $old_records[ $id ];
			if ( sanitize_text_field( $record['evidence_hash'] ?? '' ) === $hash ) {
				$record['discovery_generated_at'] = sanitize_text_field( $report['generated_at'] ?? '' );
				$new_records[ $id ] = $record;
				$unchanged++;
				continue;
			}

			$changed++;
			$previous_status = sanitize_key( $record['status'] ?? 'detected' );
			$record['previous_evidence_hash'] = sanitize_text_field( $record['evidence_hash'] ?? '' );
			$record['evidence_hash']          = $hash;
			$record['discovery_generated_at'] = sanitize_text_field( $report['generated_at'] ?? '' );
			$record['changed_at']             = current_time( 'mysql', true );
			if ( in_array( $previous_status, array( 'confirmed', 'edited' ), true ) ) {
				$record['status'] = 'outdated';
				$outdated++;
			} else {
				$record['status'] = $this->fact_has_conflict( $fact, $report ) ? 'conflict' : ( ! empty( $fact['needs_confirmation'] ) ? 'needs_confirmation' : 'detected' );
			}
			$new_records[ $id ] = $record;
		}

		$archive = (array) ( $state['archive'] ?? array() );
		foreach ( $old_records as $id => $record ) {
			if ( isset( $new_ids[ $id ] ) ) {
				continue;
			}
			$record['id']         = $id;
			$record['status']     = 'outdated';
			$record['removed_at'] = current_time( 'mysql', true );
			$archive[ $id ]       = $record;
			$outdated++;
		}

		$new_conflicts = array();
		foreach ( (array) ( $report['conflicts'] ?? array() ) as $conflict ) {
			$key = $this->conflict_key( $conflict );
			$existing = isset( $state['conflicts'][ $key ] ) ? (array) $state['conflicts'][ $key ] : array();
			$new_conflicts[ $key ] = array_merge(
				array(
					'status'         => 'unresolved',
					'selected_value' => '',
					'custom_value'   => '',
					'updated_at'     => '',
					'updated_by'     => 0,
				),
				$existing,
				array( 'discovery_generated_at' => sanitize_text_field( $report['generated_at'] ?? '' ) )
			);
		}

		$state['version']      = self::VERSION;
		$state['facts']        = $new_records;
		$state['conflicts']    = $new_conflicts;
		$state['archive']      = array_slice( $archive, -100, null, true );
		$state['generated_at'] = sanitize_text_field( $report['generated_at'] ?? '' );
		$state['rescan']       = array(
			'new_facts'        => $added,
			'changed_facts'    => $changed,
			'unchanged_facts'  => $unchanged,
			'outdated_facts'   => $outdated,
			'compared_at'      => current_time( 'mysql', true ),
		);
		update_option( self::OPTION_KEY, $state, false );
	}

	public function update_fact( $fact_id, $status, $value = null, $user_id = 0, $expected_generated_at = '' ) {
		$fact_id = sanitize_text_field( $fact_id );
		$status  = sanitize_key( $status );
		if ( ! in_array( $status, $this->statuses(), true ) || in_array( $status, array( 'outdated', 'conflict' ), true ) ) {
			return new WP_Error( 'ikon_seo_fact_status', __( 'The requested fact status is not available for manual selection.', 'ikon-seo' ), array( 'status' => 400 ) );
		}

		$discovery = $this->auto_discovery->report();
		if ( $expected_generated_at && sanitize_text_field( $expected_generated_at ) !== sanitize_text_field( $discovery['generated_at'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_fact_stale', __( 'The discovery report changed. Refresh the review screen before saving this decision.', 'ikon-seo' ), array( 'status' => 409 ) );
		}
		$fact = $this->find_fact( $fact_id, $discovery );
		if ( ! $fact ) {
			return new WP_Error( 'ikon_seo_fact_missing', __( 'The selected discovery fact no longer exists.', 'ikon-seo' ), array( 'status' => 404 ) );
		}

		$state  = $this->state();
		$record = isset( $state['facts'][ $fact_id ] ) ? (array) $state['facts'][ $fact_id ] : $this->default_record( $fact, $discovery );
		$approved_value = null;
		if ( 'confirmed' === $status ) {
			$approved_value = $fact['value'] ?? '';
		} elseif ( 'edited' === $status ) {
			$approved_value = $this->sanitize_fact_value( $value, is_array( $fact['value'] ?? null ) );
			if ( is_array( $approved_value ) ? empty( $approved_value ) : '' === trim( (string) $approved_value ) ) {
				return new WP_Error( 'ikon_seo_fact_value', __( 'Enter the corrected value before marking this fact as edited.', 'ikon-seo' ), array( 'status' => 400 ) );
			}
		}

		$record['status']                 = $status;
		$record['approved_value']         = $approved_value;
		$record['evidence_hash']          = $this->fact_hash( $fact );
		$record['discovery_generated_at'] = sanitize_text_field( $discovery['generated_at'] ?? '' );
		$record['updated_at']             = current_time( 'mysql', true );
		$record['updated_by']             = absint( $user_id );
		$state['facts'][ $fact_id ]       = $record;
		update_option( self::OPTION_KEY, $state, false );

		$this->history->add(
			array(
				'category' => 'strategy',
				'status'   => 'completed',
				'title'    => 'Discovery fact reviewed',
				'summary'  => sprintf( '%s was marked %s.', sanitize_text_field( $fact['label'] ?? $fact_id ), $status ),
				'details'  => array( 'fact_id' => $fact_id, 'decision' => $status ),
			),
			'discovery_review',
			absint( $user_id )
		);

		return $this->report();
	}


	public function accept_high_confidence( $user_id = 0, $expected_generated_at = '' ) {
		$discovery = $this->auto_discovery->report();
		if ( $expected_generated_at && sanitize_text_field( $expected_generated_at ) !== sanitize_text_field( $discovery['generated_at'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_fact_stale', __( 'The discovery report changed. Refresh the review screen before accepting detected facts.', 'ikon-seo' ), array( 'status' => 409 ) );
		}
		$state = $this->state();
		$accepted = array();
		foreach ( (array) ( $discovery['facts'] ?? array() ) as $fact ) {
			$id = sanitize_text_field( $fact['id'] ?? '' );
			$record = isset( $state['facts'][ $id ] ) ? (array) $state['facts'][ $id ] : $this->default_record( $fact, $discovery );
			if ( 'high' !== ( $fact['confidence'] ?? '' ) || ! empty( $fact['needs_confirmation'] ) || ! empty( $fact['identity_sensitive'] ) || $this->fact_has_conflict( $fact, $discovery ) || ! in_array( sanitize_key( $record['status'] ?? '' ), array( 'detected' ), true ) ) {
				continue;
			}
			$record['status']                 = 'confirmed';
			$record['approved_value']         = $fact['value'] ?? '';
			$record['evidence_hash']          = $this->fact_hash( $fact );
			$record['discovery_generated_at'] = sanitize_text_field( $discovery['generated_at'] ?? '' );
			$record['updated_at']             = current_time( 'mysql', true );
			$record['updated_by']             = absint( $user_id );
			$state['facts'][ $id ]            = $record;
			$accepted[] = $id;
		}
		update_option( self::OPTION_KEY, $state, false );
		$this->history->add(
			array(
				'category' => 'strategy',
				'status'   => 'completed',
				'title'    => 'High-confidence discovery facts accepted',
				'summary'  => sprintf( '%d high-confidence, non-sensitive technical facts were confirmed.', count( $accepted ) ),
				'details'  => array( 'facts' => $accepted ),
			),
			'discovery_review',
			absint( $user_id )
		);
		return array( 'accepted' => $accepted, 'report' => $this->report() );
	}

	public function resolve_conflict( $conflict_id, $selected_value = '', $custom_value = '', $user_id = 0, $expected_generated_at = '' ) {
		$discovery = $this->auto_discovery->report();
		if ( $expected_generated_at && sanitize_text_field( $expected_generated_at ) !== sanitize_text_field( $discovery['generated_at'] ?? '' ) ) {
			return new WP_Error( 'ikon_seo_conflict_stale', __( 'The discovery report changed. Refresh before resolving this conflict.', 'ikon-seo' ), array( 'status' => 409 ) );
		}
		$conflict = null;
		foreach ( (array) ( $discovery['conflicts'] ?? array() ) as $candidate ) {
			if ( hash_equals( $this->conflict_key( $candidate ), sanitize_text_field( $conflict_id ) ) ) {
				$conflict = $candidate;
				break;
			}
		}
		if ( ! $conflict ) {
			return new WP_Error( 'ikon_seo_conflict_missing', __( 'The selected conflict no longer exists.', 'ikon-seo' ), array( 'status' => 404 ) );
		}

		$selected_value = sanitize_text_field( $selected_value );
		$custom_value   = sanitize_text_field( $custom_value );
		$allowed        = array_values( array_map( 'sanitize_text_field', (array) ( $conflict['values'] ?? array() ) ) );
		if ( '' === $custom_value && ! in_array( $selected_value, $allowed, true ) && 'multiple_valid' !== $selected_value ) {
			return new WP_Error( 'ikon_seo_conflict_value', __( 'Choose one detected value, enter the correct value, or mark multiple values as valid.', 'ikon-seo' ), array( 'status' => 400 ) );
		}

		$state = $this->state();
		$state['conflicts'][ $conflict_id ] = array(
			'status'                 => 'resolved',
			'selected_value'         => $selected_value,
			'custom_value'           => $custom_value,
			'discovery_generated_at' => sanitize_text_field( $discovery['generated_at'] ?? '' ),
			'updated_at'             => current_time( 'mysql', true ),
			'updated_by'             => absint( $user_id ),
		);
		foreach ( (array) ( $discovery['facts'] ?? array() ) as $fact ) {
			$fact_id = sanitize_text_field( $fact['id'] ?? '' );
			if ( ! $this->fact_matches_conflict( $fact, $conflict ) || ! isset( $state['facts'][ $fact_id ] ) ) {
				continue;
			}
			if ( $custom_value ) {
				$state['facts'][ $fact_id ]['status'] = 'edited';
				$state['facts'][ $fact_id ]['approved_value'] = $this->sanitize_fact_value( $custom_value, is_array( $fact['value'] ?? null ) );
			} elseif ( 'multiple_valid' === $selected_value ) {
				$state['facts'][ $fact_id ]['status'] = 'needs_confirmation';
				$state['facts'][ $fact_id ]['approved_value'] = null;
			} else {
				$state['facts'][ $fact_id ]['status'] = 'confirmed';
				$state['facts'][ $fact_id ]['approved_value'] = $this->sanitize_fact_value( $selected_value, is_array( $fact['value'] ?? null ) );
			}
			$state['facts'][ $fact_id ]['updated_at'] = current_time( 'mysql', true );
			$state['facts'][ $fact_id ]['updated_by'] = absint( $user_id );
		}
		update_option( self::OPTION_KEY, $state, false );
		return $this->report();
	}

	public function apply_confirmed( $user_id = 0 ) {
		$review    = $this->report();
		$discovery = $this->auto_discovery->report();
		$profile_input  = array();
		$strategy_input = array();
		$applied        = array();

		foreach ( (array) ( $review['facts'] ?? array() ) as $fact ) {
			if ( ! in_array( $fact['status'] ?? '', array( 'confirmed', 'edited' ), true ) ) {
				continue;
			}
			$value = array_key_exists( 'approved_value', $fact ) ? $fact['approved_value'] : ( $fact['value'] ?? '' );
			if ( null === $value ) {
				continue;
			}
			$input_value = is_array( $value ) ? implode( "\n", array_map( 'sanitize_text_field', $value ) ) : sanitize_textarea_field( (string) $value );
			if ( 'profile' === ( $fact['group'] ?? '' ) ) {
				$profile_input[ sanitize_key( $fact['field'] ?? '' ) ] = $input_value;
			} elseif ( 'strategy' === ( $fact['group'] ?? '' ) ) {
				$strategy_input[ sanitize_key( $fact['field'] ?? '' ) ] = $input_value;
			}
			$applied[] = sanitize_text_field( $fact['id'] ?? '' );
		}
		if ( ! $applied ) {
			return new WP_Error( 'ikon_seo_no_confirmed_facts', __( 'Confirm or edit at least one fact before applying reviewed values.', 'ikon-seo' ), array( 'status' => 400 ) );
		}

		if ( $profile_input ) {
			$current_settings = Ikon_SEO_Plugin::settings();
			$old_fingerprint  = $this->profile->fingerprint( $current_settings );
			$clean_profile    = $this->profile->sanitize( $profile_input, $current_settings );
			if ( is_wp_error( $clean_profile ) ) {
				return $clean_profile;
			}
			$clean_profile['profile_configured'] = 1;
			$clean_profile['profile_home_url']   = home_url( '/' );
			$new_fingerprint = $this->profile->fingerprint( $clean_profile );
			if ( ! hash_equals( $old_fingerprint, $new_fingerprint ) ) {
				$clean_profile['token_hash'] = '';
				$clean_profile['connection_owner_user_id'] = 0;
				$clean_profile['token_hint'] = '';
				$clean_profile['connection_verified_at'] = '';
				$clean_profile['connection_last_seen_at'] = '';
				$clean_profile['remote_actions'] = 0;
				$clean_profile['gbp_refresh_token'] = '';
				$clean_profile['gbp_account'] = '';
				$clean_profile['gbp_last_error'] = '';
				$this->local->rebind_profile( $old_fingerprint, $new_fingerprint );
			}
			update_option( Ikon_SEO_Plugin::OPTION_KEY, $clean_profile, false );
			$this->inventory->clear_cache();
		}
		if ( $strategy_input ) {
			$result = $this->strategy->save( $strategy_input, absint( $user_id ), 'discovery_review' );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$state = $this->state();
		$state['last_applied_at'] = current_time( 'mysql', true );
		$state['last_applied_by'] = absint( $user_id );
		$state['last_applied_facts'] = $applied;
		update_option( self::OPTION_KEY, $state, false );

		$this->history->add(
			array(
				'category' => 'strategy',
				'status'   => 'completed',
				'title'    => 'Confirmed discovery facts applied',
				'summary'  => sprintf( '%d fact-level decisions were applied to the Website Profile and Strategy.', count( $applied ) ),
				'details'  => array( 'facts' => $applied, 'discovery_generated_at' => $discovery['generated_at'] ?? '' ),
			),
			'discovery_review',
			absint( $user_id )
		);

		return array( 'applied' => $applied, 'review' => $this->report(), 'profile' => $this->profile->get(), 'strategy' => $this->strategy->get() );
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'update_fact':
				return $this->update_fact( $payload['fact_id'] ?? '', $payload['status'] ?? '', $payload['value'] ?? null, $user_id, $payload['generated_at'] ?? '' );
			case 'accept_high_confidence':
				return $this->accept_high_confidence( $user_id, $payload['generated_at'] ?? '' );
			case 'resolve_conflict':
				return $this->resolve_conflict( $payload['conflict_id'] ?? '', $payload['selected_value'] ?? '', $payload['custom_value'] ?? '', $user_id, $payload['generated_at'] ?? '' );
			case 'apply_confirmed':
				return $this->apply_confirmed( $user_id );
			case 'read':
			default:
				return $this->report();
		}
	}

	private function state() {
		$state = get_option( self::OPTION_KEY, array() );
		return is_array( $state ) ? $state : array( 'version' => self::VERSION, 'facts' => array(), 'conflicts' => array(), 'archive' => array(), 'rescan' => array() );
	}

	private function statuses() {
		return array( 'detected', 'confirmed', 'edited', 'rejected', 'conflict', 'needs_confirmation', 'outdated' );
	}

	private function default_record( array $fact, array $report ) {
		return array(
			'status'                 => $this->fact_has_conflict( $fact, $report ) ? 'conflict' : ( ! empty( $fact['needs_confirmation'] ) ? 'needs_confirmation' : 'detected' ),
			'approved_value'         => null,
			'evidence_hash'          => $this->fact_hash( $fact ),
			'discovery_generated_at' => sanitize_text_field( $report['generated_at'] ?? '' ),
			'updated_at'             => '',
			'updated_by'             => 0,
		);
	}

	private function fact_hash( array $fact ) {
		return hash( 'sha256', wp_json_encode( array( 'value' => $fact['value'] ?? '', 'sources' => $fact['sources'] ?? array(), 'confidence' => $fact['confidence'] ?? '' ) ) );
	}

	private function conflict_key( array $conflict ) {
		return 'conflict_' . substr( hash( 'sha256', wp_json_encode( array( 'area' => $conflict['area'] ?? '', 'values' => array_values( (array) ( $conflict['values'] ?? array() ) ) ) ) ), 0, 20 );
	}

	private function find_fact( $fact_id, array $report ) {
		foreach ( (array) ( $report['facts'] ?? array() ) as $fact ) {
			if ( ( $fact['id'] ?? '' ) === $fact_id ) {
				return $fact;
			}
		}
		return null;
	}


	private function category_for_fact( array $fact ) {
		$field = sanitize_key( $fact['field'] ?? '' );
		if ( false !== strpos( $field, 'offering' ) || false !== strpos( $field, 'service_area' ) || false !== strpos( $field, 'target_location' ) ) {
			return 'services_locations';
		}
		if ( false !== strpos( $field, 'audience' ) ) {
			return 'audience';
		}
		if ( false !== strpos( $field, 'conversion' ) || false !== strpos( $field, 'lead_channel' ) || false !== strpos( $field, 'success_metric' ) || false !== strpos( $field, 'primary_goal' ) ) {
			return 'conversions_goals';
		}
		if ( false !== strpos( $field, 'value_proposition' ) || false !== strpos( $field, 'evidence_requirement' ) || false !== strpos( $field, 'editorial_standard' ) ) {
			return 'claims_governance';
		}
		if ( false !== strpos( $field, 'website_mode' ) || false !== strpos( $field, 'monetization' ) ) {
			return 'website_model';
		}
		return 'business_identity';
	}

	private function fact_has_conflict( array $fact, array $report ) {
		foreach ( (array) ( $report['conflicts'] ?? array() ) as $conflict ) {
			if ( $this->fact_matches_conflict( $fact, $conflict ) ) {
				return true;
			}
		}
		return false;
	}

	private function fact_matches_conflict( array $fact, array $conflict ) {
		$field = sanitize_key( $fact['field'] ?? '' );
		$area  = strtolower( sanitize_text_field( $conflict['area'] ?? '' ) );
		if ( false !== strpos( $area, 'phone' ) ) {
			return false !== strpos( $field, 'phone' );
		}
		if ( false !== strpos( $area, 'email' ) ) {
			return false !== strpos( $field, 'email' );
		}
		if ( false !== strpos( $area, 'language' ) ) {
			return false !== strpos( $field, 'language' );
		}
		if ( false !== strpos( $area, 'currency' ) ) {
			return false !== strpos( $field, 'currency' );
		}
		return false;
	}

	private function sanitize_fact_value( $value, $expects_array ) {
		if ( $expects_array ) {
			$values = is_array( $value ) ? $value : preg_split( '/[\r\n,]+/', (string) $value );
			return array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $values ) ) ) );
		}
		return sanitize_textarea_field( (string) $value );
	}
}
