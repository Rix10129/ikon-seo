<?php

defined( 'ABSPATH' ) || exit;

/**
 * Portfolio Learning and Validated Pattern Library.
 *
 * Aggregates anonymised, human-acknowledged Search Impact evidence into
 * context-bounded patterns. It never applies a pattern, edits a page, or
 * treats an association as a universal rule.
 */
class Ikon_SEO_Pattern_Library {
	const CRON_HOOK = 'ikon_seo_pattern_library_refresh';

	private $search_impact;
	private $publishing_readiness;
	private $profile;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Search_Impact $search_impact,
		Ikon_SEO_Publishing_Readiness $publishing_readiness,
		Ikon_SEO_Profile $profile,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->search_impact = $search_impact;
		$this->publishing_readiness = $publishing_readiness;
		$this->profile = $profile;
		$this->history = $history;
		$this->logger = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_refresh' ) );
	}

	public function patterns_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_patterns';
	}

	public function evidence_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_pattern_evidence';
	}

	public function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'ikon_seo_pattern_events';
	}

	public function status() {
		global $wpdb;
		if ( ! $this->tables_ready() ) {
			return array( 'database_ready' => false, 'patterns' => 0, 'evidence' => 0 );
		}
		return array(
			'database_ready' => true,
			'patterns' => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->patterns_table()}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'evidence' => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->evidence_table()} WHERE is_current=1" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'validated' => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->patterns_table()} WHERE status='validated'" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'review_ready' => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->patterns_table()} WHERE status='review_ready'" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'revalidation_required' => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->patterns_table()} WHERE status='revalidation_required'" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'read':
				return $this->report( array( 'limit' => absint( $payload['limit'] ?? 100 ), 'status' => sanitize_key( $payload['status'] ?? '' ) ) );
			case 'refresh':
				return $this->refresh( $user_id );
			case 'import_evidence':
				return $this->import_evidence( (array) ( $payload['records'] ?? array() ), $user_id );
			case 'validate':
			case 'limit':
			case 'reject':
			case 'retire':
			case 'restore':
				return $this->set_pattern_status( absint( $payload['pattern_id'] ?? 0 ), $command, sanitize_textarea_field( $payload['notes'] ?? '' ), $user_id );
			default:
				return new WP_Error( 'ikon_seo_pattern_command', __( 'Unknown Pattern Library command.', 'ikon-seo' ) );
		}
	}

	public function report( array $args = array(), $refresh = false ) {
		global $wpdb;
		if ( $refresh ) {
			$this->refresh( 0 );
		}
		$status = sanitize_key( $args['status'] ?? '' );
		$limit = max( 1, min( 200, absint( $args['limit'] ?? 100 ) ) );
		$where = '';
		$params = array();
		if ( $status ) {
			$where = ' WHERE status=%s';
			$params[] = $status;
		}
		$params[] = $limit;
		$sql = "SELECT * FROM {$this->patterns_table()}{$where} ORDER BY confidence_score DESC, site_count DESC, updated_at DESC LIMIT %d";
		$rows = $this->tables_ready() ? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$patterns = array_map( array( $this, 'format_pattern' ), $rows ?: array() );
		return array(
			'generated_at' => current_time( 'mysql', true ),
			'status' => $this->status(),
			'patterns' => $patterns,
			'current_context' => $this->current_context(),
			'shareable_evidence' => $this->export_evidence( 100 ),
			'methodology' => array(
				'Only human-acknowledged Search Impact studies and approved anonymised imports are eligible.',
				'Patterns are grouped by website mode, industry, market, language, page type, change family and primary metric.',
				'Automated refresh can make a pattern review-ready but cannot validate it.',
				'An evidence record keeps its first accepted context signature; later imports cannot silently move it into a different pattern.',
				'Imported fingerprints support deduplication but are not independent proof of website identity.',
				'A pattern validated against older evidence becomes revalidation-required when its evidence fingerprint changes.',
				'Patterns describe associated outcomes in a bounded context; they are not universal SEO rules or causal claims.',
			),
			'safety' => array(
				'No command edits, publishes, merges, redirects, deletes, noindexes or changes canonical settings.',
				'No raw URL, business name, query text, content or personal data is included in shareable evidence.',
				'Validated patterns remain advisory and require a separate opportunity, brief, editorial and publishing workflow before use.',
			),
		);
	}

	public function refresh( $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_pattern_manage', __( 'Only an administrator or SEO measurement manager can refresh the Pattern Library.', 'ikon-seo' ) );
		}
		if ( ! $this->tables_ready() ) {
			return new WP_Error( 'ikon_seo_pattern_tables', __( 'Pattern Library database tables are not ready.', 'ikon-seo' ) );
		}
		$sql = "SELECT s.*, r.brief_id, r.publication_mode, b.target_intent, b.dominant_result_type, b.opportunity_id,
			o.type AS opportunity_type, o.category AS opportunity_category, o.primary_source, o.intent AS opportunity_intent,
			o.effort, o.risk, o.confidence AS opportunity_confidence
			FROM {$wpdb->prefix}ikon_seo_impact_studies s
			INNER JOIN {$wpdb->prefix}ikon_seo_publishing_releases r ON r.id=s.release_id
			LEFT JOIN {$wpdb->prefix}ikon_seo_content_briefs b ON b.id=r.brief_id
			LEFT JOIN {$wpdb->prefix}ikon_seo_opportunities o ON o.id=b.opportunity_id
			WHERE s.status='acknowledged' AND s.assessment_json IS NOT NULL
			ORDER BY s.updated_at ASC LIMIT 500";
		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$seen = array();
		$touched = array();
		$local_site_hash = $this->site_fingerprint();
		foreach ( $rows ?: array() as $row ) {
			$source_key = 'local:' . absint( $row['id'] );
			$existing_evidence = $this->find_evidence( $local_site_hash, $source_key );
			if ( $existing_evidence ) {
				$context = $this->decode_json( $existing_evidence['context_json'] ?? '' );
				$pattern_id = absint( $existing_evidence['pattern_id'] );
			} else {
				$context = $this->context_from_row( $row );
				$pattern_id = $this->upsert_pattern( $context );
			}
			if ( ! $pattern_id || ! $context ) {
				continue;
			}
			$assessment = $this->decode_json( $row['assessment_json'] ?? '' );
			$evidence_hash = hash( 'sha256', wp_json_encode( array( 'assessment' => $assessment, 'context' => $context, 'updated_at' => $row['updated_at'] ?? '' ) ) );
			$this->upsert_evidence(
				$pattern_id,
				array(
					'source_type' => 'local',
					'source_site_hash' => $local_site_hash,
					'source_study_key' => $source_key,
					'local_study_id' => absint( $row['id'] ),
					'outcome' => sanitize_key( $row['outcome'] ?? 'inconclusive' ),
					'confidence' => sanitize_key( $row['confidence'] ?? 'low' ),
					'adjusted_change_percent' => null === $row['adjusted_change_percent'] ? null : (float) $row['adjusted_change_percent'],
					'human_decision' => sanitize_key( $assessment['human_decision'] ?? '' ),
					'assessment_hash' => $evidence_hash,
					'observed_at' => sanitize_text_field( $assessment['acknowledged_at'] ?? $row['updated_at'] ?? current_time( 'mysql', true ) ),
					'context' => $context,
				),
				$user_id
			);
			$seen[] = $source_key;
			$touched[ $pattern_id ] = true;
		}
		$this->expire_missing_local_evidence( $seen );
		$pattern_ids = $wpdb->get_col( "SELECT id FROM {$this->patterns_table()}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $pattern_ids ?: array() as $pattern_id ) {
			$this->recalculate_pattern( absint( $pattern_id ), $user_id );
		}
		$settings = Ikon_SEO_Plugin::settings();
		$settings['pattern_library_last_refresh'] = current_time( 'mysql', true );
		$settings['pattern_library_last_error'] = '';
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->record_history( 'research', 'Pattern Library refreshed', sprintf( '%d acknowledged local studies were reviewed. No pattern was automatically applied or validated.', count( $rows ?: array() ) ), $user_id );
		$this->logger->log( 'pattern_library_refresh', 'completed', 'Portfolio pattern candidates refreshed from acknowledged evidence.', null, null, array( 'studies' => count( $rows ?: array() ) ) );
		return $this->report( array( 'limit' => 100 ) );
	}

	public function import_evidence( array $records, $user_id = 0 ) {
		if ( ! $this->can_manage( $user_id ) ) {
			return new WP_Error( 'ikon_seo_pattern_import', __( 'Only an administrator or SEO measurement manager can import pattern evidence.', 'ikon-seo' ) );
		}
		$records = array_slice( $records, 0, 200 );
		$inserted = 0;
		$skipped = 0;
		foreach ( $records as $record ) {
			if ( ! is_array( $record ) ) {
				$skipped++;
				continue;
			}
			$clean = $this->normalize_import_record( $record );
			if ( is_wp_error( $clean ) ) {
				$skipped++;
				continue;
			}
			$existing_evidence = $this->find_evidence( $clean['source_site_hash'], $clean['source_study_key'] );
			if ( $existing_evidence ) {
				$existing_context = $this->decode_json( $existing_evidence['context_json'] ?? '' );
				if ( $this->pattern_key( $existing_context ) !== $this->pattern_key( $clean['context'] ) ) {
					$skipped++;
					continue;
				}
				$pattern_id = absint( $existing_evidence['pattern_id'] );
			} else {
				$pattern_id = $this->upsert_pattern( $clean['context'] );
			}
			if ( ! $pattern_id ) {
				$skipped++;
				continue;
			}
			$clean['source_type'] = 'imported';
			if ( $this->upsert_evidence( $pattern_id, $clean, $user_id ) ) {
				$inserted++;
				$this->recalculate_pattern( $pattern_id, $user_id );
			} else {
				$skipped++;
			}
		}
		$this->record_history( 'research', 'Pattern evidence imported', sprintf( '%d anonymised evidence records were imported; %d were skipped.', $inserted, $skipped ), $user_id );
		return array( 'inserted' => $inserted, 'skipped' => $skipped, 'report' => $this->report( array( 'limit' => 100 ) ) );
	}

	public function set_pattern_status( $pattern_id, $command, $notes, $user_id = 0 ) {
		global $wpdb;
		if ( ! $this->can_approve( $user_id ) ) {
			return new WP_Error( 'ikon_seo_pattern_approve', __( 'Only an administrator or publishing approver can decide pattern status.', 'ikon-seo' ) );
		}
		$pattern = $this->get_pattern( $pattern_id, true );
		if ( ! $pattern ) {
			return new WP_Error( 'ikon_seo_pattern_missing', __( 'The pattern was not found.', 'ikon-seo' ) );
		}
		$map = array( 'validate' => 'validated', 'limit' => 'limited_use', 'reject' => 'rejected', 'retire' => 'retired', 'restore' => 'candidate' );
		$status = $map[ $command ] ?? '';
		if ( ! $status ) {
			return new WP_Error( 'ikon_seo_pattern_status', __( 'Choose a supported pattern decision.', 'ikon-seo' ) );
		}
		if ( 'validated' === $status && ! $this->eligible_for_validation( $pattern ) ) {
			return new WP_Error( 'ikon_seo_pattern_not_ready', __( 'This pattern does not yet meet the minimum cross-site evidence and consistency threshold.', 'ikon-seo' ) );
		}
		if ( 'limited_use' === $status && absint( $pattern['usable_study_count'] ?? 0 ) < 1 ) {
			return new WP_Error( 'ikon_seo_pattern_no_usable_evidence', __( 'Limited-use approval requires at least one usable medium/high-confidence study.', 'ikon-seo' ) );
		}
		if ( in_array( $status, array( 'limited_use', 'rejected', 'retired', 'candidate' ), true ) && ! trim( $notes ) ) {
			return new WP_Error( 'ikon_seo_pattern_decision_notes', __( 'Add a human review note for this pattern decision.', 'ikon-seo' ) );
		}
		$now = current_time( 'mysql', true );
		$update = array(
			'status' => $status,
			'review_notes' => sanitize_textarea_field( $notes ),
			'approved_by' => in_array( $status, array( 'validated', 'limited_use' ), true ) ? absint( $user_id ) : 0,
			'approved_at' => in_array( $status, array( 'validated', 'limited_use' ), true ) ? $now : null,
			'validated_evidence_hash' => in_array( $status, array( 'validated', 'limited_use' ), true ) ? sanitize_text_field( $pattern['evidence_hash'] ) : '',
			'updated_at' => $now,
		);
		$wpdb->update( $this->patterns_table(), $update, array( 'id' => $pattern_id ) );
		$this->event( $pattern_id, 'status_' . $status, sprintf( 'Pattern status changed to %s.', str_replace( '_', ' ', $status ) ), array( 'notes' => $notes ), $user_id );
		$this->record_history( 'approval', 'Pattern decision recorded', sprintf( 'Pattern #%d was marked %s. It remains advisory and was not applied to a website.', $pattern_id, str_replace( '_', ' ', $status ) ), $user_id );
		if ( 'candidate' === $status ) {
			$this->recalculate_pattern( $pattern_id, $user_id );
		}
		return $this->get_pattern( $pattern_id );
	}

	public function scheduled_refresh() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['pattern_library_enabled'] ) ) {
			return;
		}
		$owner = absint( $settings['connection_owner_user_id'] ?? 0 );
		if ( ! $owner ) {
			$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
			$owner = absint( $admins[0] ?? 0 );
		}
		$this->refresh( $owner );
	}

	public function get_pattern( $id, $raw = false ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->patterns_table()} WHERE id=%d LIMIT 1", absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? ( $raw ? $row : $this->format_pattern( $row ) ) : array();
	}

	private function upsert_pattern( array $context ) {
		global $wpdb;
		$key = $this->pattern_key( $context );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->patterns_table()} WHERE pattern_key=%s LIMIT 1", $key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$now = current_time( 'mysql', true );
		if ( $existing ) {
			$wpdb->update( $this->patterns_table(), array( 'updated_at' => $now ), array( 'id' => absint( $existing ) ) );
			return absint( $existing );
		}
		$title = $this->pattern_title( $context );
		$wpdb->insert(
			$this->patterns_table(),
			array(
				'pattern_key' => $key,
				'title' => $title,
				'website_mode' => $context['website_mode'],
				'industry' => $context['industry'],
				'market' => $context['market'],
				'language' => $context['language'],
				'page_type' => $context['page_type'],
				'change_family' => $context['change_family'],
				'primary_metric' => $context['primary_metric'],
				'status' => 'candidate',
				'directional_signal' => 'inconclusive',
				'confidence' => 'low',
				'confidence_score' => 0,
				'applicability_json' => wp_json_encode( $this->applicability( $context ) ),
				'limitations_json' => wp_json_encode( array( 'Insufficient evidence has been reviewed.' ) ),
				'evidence_hash' => '',
				'validated_evidence_hash' => '',
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
		return absint( $wpdb->insert_id );
	}

	private function upsert_evidence( $pattern_id, array $data, $user_id ) {
		global $wpdb;
		$site_hash = sanitize_text_field( $data['source_site_hash'] ?? '' );
		$study_key = sanitize_text_field( $data['source_study_key'] ?? '' );
		if ( ! preg_match( '/^[a-f0-9]{32,64}$/', $site_hash ) || ! preg_match( '/^[A-Za-z0-9._:-]{1,100}$/', $study_key ) ) {
			return false;
		}
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id,pattern_id,assessment_hash FROM {$this->evidence_table()} WHERE source_site_hash=%s AND source_study_key=%s LIMIT 1", $site_hash, $study_key ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$record = array(
			'pattern_id' => absint( $pattern_id ),
			'source_type' => sanitize_key( $data['source_type'] ?? 'imported' ),
			'source_site_hash' => $site_hash,
			'source_study_key' => substr( $study_key, 0, 100 ),
			'local_study_id' => absint( $data['local_study_id'] ?? 0 ),
			'outcome' => $this->allowed_outcome( $data['outcome'] ?? '' ),
			'confidence' => $this->allowed_confidence( $data['confidence'] ?? '' ),
			'adjusted_change_percent' => null === ( $data['adjusted_change_percent'] ?? null ) ? null : max( -10000, min( 10000, (float) $data['adjusted_change_percent'] ) ),
			'human_decision' => sanitize_key( $data['human_decision'] ?? '' ),
			'assessment_hash' => sanitize_text_field( $data['assessment_hash'] ?? '' ),
			'context_json' => wp_json_encode( $data['context'] ?? array() ),
			'is_current' => 1,
			'observed_at' => $this->mysql_date( $data['observed_at'] ?? '' ),
			'imported_by' => absint( $user_id ),
			'updated_at' => current_time( 'mysql', true ),
		);
		if ( $existing ) {
			$wpdb->update( $this->evidence_table(), $record, array( 'id' => absint( $existing['id'] ) ) );
			if ( absint( $existing['pattern_id'] ) !== absint( $pattern_id ) ) {
				$this->recalculate_pattern( absint( $existing['pattern_id'] ), $user_id );
			}
			return true;
		}
		$record['created_at'] = current_time( 'mysql', true );
		$wpdb->insert( $this->evidence_table(), $record );
		return (bool) $wpdb->insert_id;
	}

	private function recalculate_pattern( $pattern_id, $user_id = 0 ) {
		global $wpdb;
		$pattern = $this->get_pattern( $pattern_id, true );
		if ( ! $pattern ) {
			return;
		}
		$stale_days = max( 90, min( 730, absint( Ikon_SEO_Plugin::settings()['pattern_library_stale_days'] ?? 365 ) ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $stale_days * DAY_IN_SECONDS ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->evidence_table()} WHERE pattern_id=%d AND is_current=1 AND observed_at>=%s ORDER BY observed_at ASC", $pattern_id, $cutoff ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$counts = array( 'positive_signal' => 0, 'negative_signal' => 0, 'neutral_signal' => 0, 'inconclusive' => 0 );
		$usable_counts = array( 'positive_signal' => 0, 'negative_signal' => 0, 'neutral_signal' => 0 );
		$sites = array();
		$usable_sites = array();
		$values = array();
		$usable = 0;
		$imported_count = 0;
		$hash_rows = array();
		foreach ( $rows ?: array() as $row ) {
			if ( 'imported' === sanitize_key( $row['source_type'] ?? '' ) ) { $imported_count++; }
			$outcome = $this->allowed_outcome( $row['outcome'] ?? '' );
			$counts[ $outcome ]++;
			$sites[ $row['source_site_hash'] ] = true;
			if ( in_array( $row['confidence'], array( 'medium', 'high' ), true ) && 'inconclusive' !== $outcome ) {
				$usable++;
				$usable_sites[ $row['source_site_hash'] ] = true;
				$usable_counts[ $outcome ]++;
				if ( null !== $row['adjusted_change_percent'] ) {
					$values[] = (float) $row['adjusted_change_percent'];
				}
			}
			$hash_rows[] = array( $row['source_site_hash'], $row['source_study_key'], $row['assessment_hash'], $row['outcome'], $row['confidence'] );
		}
		sort( $hash_rows );
		$evidence_hash = hash( 'sha256', wp_json_encode( $hash_rows ) );
		$site_count = count( $sites );
		$usable_site_count = count( $usable_sites );
		$study_count = count( $rows ?: array() );
		$directional_total = $usable_counts['positive_signal'] + $usable_counts['negative_signal'] + $usable_counts['neutral_signal'];
		$dominant = 'inconclusive';
		$dominant_count = 0;
		foreach ( array( 'positive_signal', 'negative_signal', 'neutral_signal' ) as $key ) {
			if ( $usable_counts[ $key ] > $dominant_count ) {
				$dominant = $key;
				$dominant_count = $usable_counts[ $key ];
			}
		}
		$consistency = $directional_total ? round( 100 * $dominant_count / $directional_total, 2 ) : 0;
		$median = $this->median( $values );
		$score = min( 100, ( $usable_site_count * 12 ) + ( $usable * 5 ) + (int) round( $consistency * 0.35 ) );
		$confidence = $score >= 80 ? 'high' : ( $score >= 55 ? 'medium' : 'low' );
		$eligible = $usable_site_count >= 3 && $usable >= 5 && $consistency >= 65 && 'inconclusive' !== $dominant;
		$old_status = sanitize_key( $pattern['status'] );
		$new_status = $old_status;
		if ( in_array( $old_status, array( 'candidate', 'review_ready' ), true ) ) {
			$new_status = $eligible ? 'review_ready' : 'candidate';
		}
		if ( in_array( $old_status, array( 'validated', 'limited_use' ), true ) && $pattern['validated_evidence_hash'] && ! hash_equals( (string) $pattern['validated_evidence_hash'], $evidence_hash ) ) {
			$new_status = 'revalidation_required';
		}
		$limitations = array();
		if ( $usable_site_count < 3 ) { $limitations[] = 'Usable evidence covers fewer than three distinct website fingerprints.'; }
		if ( $usable < 5 ) { $limitations[] = 'Fewer than five medium/high-confidence directional studies are available.'; }
		if ( $consistency < 65 ) { $limitations[] = 'Directional consistency is below 65%.'; }
		if ( 'inconclusive' === $dominant ) { $limitations[] = 'No usable directional signal is established.'; }
		if ( $imported_count ) { $limitations[] = 'Imported evidence is administrator-approved but its site fingerprint is not independent cryptographic attestation.'; }
		$limitations[] = 'The pattern applies only to the stored context and remains an association, not a causal rule.';
		$update = array(
				'status' => $new_status,
				'directional_signal' => $dominant,
				'confidence' => $confidence,
				'confidence_score' => $score,
				'study_count' => $study_count,
				'usable_study_count' => $usable,
				'usable_site_count' => $usable_site_count,
				'site_count' => $site_count,
				'positive_count' => $counts['positive_signal'],
				'negative_count' => $counts['negative_signal'],
				'neutral_count' => $counts['neutral_signal'],
				'inconclusive_count' => $counts['inconclusive'],
				'consistency_percent' => $consistency,
				'median_change_percent' => null === $median ? null : round( $median, 4 ),
				'evidence_hash' => $evidence_hash,
				'limitations_json' => wp_json_encode( $limitations ),
				'updated_at' => current_time( 'mysql', true ),
			);
		if ( 'revalidation_required' === $new_status ) {
			$update['approved_by'] = 0;
			$update['approved_at'] = null;
		}
		$wpdb->update( $this->patterns_table(), $update, array( 'id' => $pattern_id ) );
		if ( 'revalidation_required' === $new_status && $old_status !== $new_status ) {
			$this->event( $pattern_id, 'evidence_changed', 'Validated evidence changed; human revalidation is required.', array( 'previous_status' => $old_status ), $user_id );
		}
	}

	private function normalize_import_record( array $record ) {
		$forbidden = array( 'url', 'target_url', 'business_name', 'site_name', 'title', 'keyword', 'query', 'content', 'email', 'phone' );
		foreach ( $forbidden as $key ) {
			if ( ! empty( $record[ $key ] ) ) {
				return new WP_Error( 'ikon_seo_pattern_private_data', __( 'Imported pattern evidence must not include URLs, names, queries, content or contact information.', 'ikon-seo' ) );
			}
		}
		$site_hash = strtolower( sanitize_text_field( $record['source_site_fingerprint'] ?? $record['source_site_hash'] ?? '' ) );
		$study_key = sanitize_text_field( $record['source_study_key'] ?? '' );
		if ( ! preg_match( '/^[a-f0-9]{32,64}$/', $site_hash ) || ! preg_match( '/^[A-Za-z0-9._:-]{1,100}$/', $study_key ) ) {
			return new WP_Error( 'ikon_seo_pattern_import_identity', __( 'Each imported record needs an anonymised site fingerprint and source study key.', 'ikon-seo' ) );
		}
		$context = array(
			'website_mode' => sanitize_key( $record['website_mode'] ?? 'hybrid' ),
			'industry' => sanitize_key( $record['industry'] ?? 'general' ),
			'market' => $this->context_token( $record['market'] ?? 'unspecified' ),
			'language' => $this->context_token( $record['language'] ?? 'en' ),
			'page_type' => sanitize_key( $record['page_type'] ?? 'unknown' ),
			'change_family' => sanitize_key( $record['change_family'] ?? 'other' ),
			'primary_metric' => sanitize_key( $record['primary_metric'] ?? 'clicks' ),
		);
		$outcome = $this->allowed_outcome( $record['outcome'] ?? '' );
		$confidence = $this->allowed_confidence( $record['confidence'] ?? '' );
		$observed_raw = sanitize_text_field( $record['observed_at'] ?? '' );
		$observed_timestamp = $observed_raw ? strtotime( $observed_raw . ( false === strpos( $observed_raw, 'UTC' ) ? ' UTC' : '' ) ) : false;
		if ( $observed_timestamp && $observed_timestamp > time() + DAY_IN_SECONDS ) {
			return new WP_Error( 'ikon_seo_pattern_future_evidence', __( 'Imported evidence cannot be dated in the future.', 'ikon-seo' ) );
		}
		$assessment_hash = sanitize_text_field( $record['assessment_hash'] ?? '' );
		if ( ! preg_match( '/^[a-f0-9]{64}$/', $assessment_hash ) ) {
			$assessment_hash = hash( 'sha256', wp_json_encode( array( $context, $outcome, $confidence, $record['adjusted_change_percent'] ?? null, $record['human_decision'] ?? '', $record['observed_at'] ?? '' ) ) );
		}
		return array(
			'source_site_hash' => $site_hash,
			'source_study_key' => substr( $study_key, 0, 100 ),
			'local_study_id' => 0,
			'outcome' => $outcome,
			'confidence' => $confidence,
			'adjusted_change_percent' => null === ( $record['adjusted_change_percent'] ?? null ) ? null : (float) $record['adjusted_change_percent'],
			'human_decision' => sanitize_key( $record['human_decision'] ?? '' ),
			'assessment_hash' => $assessment_hash,
			'observed_at' => $this->mysql_date( $observed_raw ),
			'context' => $context,
		);
	}

	private function context_from_row( array $row ) {
		$settings = Ikon_SEO_Plugin::settings();
		$category = sanitize_key( $row['opportunity_category'] ?? 'other' );
		$type = sanitize_key( $row['opportunity_type'] ?? '' );
		return array(
			'website_mode' => sanitize_key( $settings['website_mode'] ?? 'hybrid' ),
			'industry' => sanitize_key( $settings['industry'] ?? 'general' ),
			'market' => $this->context_token( $settings['target_market'] ?? $settings['address_country'] ?? 'unspecified' ),
			'language' => $this->context_token( $settings['default_language'] ?? get_locale() ),
			'page_type' => sanitize_key( $row['dominant_result_type'] ?? $row['publication_mode'] ?? 'unknown' ),
			'change_family' => $this->change_family( $type, $category ),
			'primary_metric' => sanitize_key( $row['primary_metric'] ?? 'clicks' ),
		);
	}

	private function current_context() {
		$settings = Ikon_SEO_Plugin::settings();
		return array(
			'website_mode' => sanitize_key( $settings['website_mode'] ?? 'hybrid' ),
			'industry' => sanitize_key( $settings['industry'] ?? 'general' ),
			'market' => $this->context_token( $settings['target_market'] ?? $settings['address_country'] ?? 'unspecified' ),
			'language' => $this->context_token( $settings['default_language'] ?? get_locale() ),
		);
	}

	private function change_family( $type, $category ) {
		$combined = $type . ' ' . $category;
		if ( false !== strpos( $combined, 'refresh' ) || false !== strpos( $combined, 'decay' ) ) { return 'content_refresh'; }
		if ( false !== strpos( $combined, 'internal' ) || false !== strpos( $combined, 'link' ) ) { return 'internal_linking'; }
		if ( false !== strpos( $combined, 'local' ) || false !== strpos( $combined, 'location' ) ) { return 'local_coverage'; }
		if ( false !== strpos( $combined, 'conversion' ) || false !== strpos( $combined, 'cta' ) ) { return 'conversion_improvement'; }
		if ( false !== strpos( $combined, 'new' ) || false !== strpos( $combined, 'gap' ) ) { return 'new_content'; }
		if ( false !== strpos( $combined, 'content' ) || false !== strpos( $combined, 'keyword' ) ) { return 'on_page_content'; }
		return 'other';
	}

	private function pattern_key( array $context ) {
		return hash( 'sha256', wp_json_encode( array_values( $context ) ) );
	}

	private function pattern_title( array $context ) {
		return sprintf(
			'%s for %s %s pages — %s',
			ucwords( str_replace( '_', ' ', $context['change_family'] ) ),
			ucwords( str_replace( '_', ' ', $context['website_mode'] ) ),
			ucwords( str_replace( '_', ' ', $context['page_type'] ) ),
			ucwords( str_replace( '_', ' ', $context['primary_metric'] ) )
		);
	}

	private function applicability( array $context ) {
		return array(
			'matches' => $context,
			'requires_human_context_check' => true,
			'forbidden_broadening' => array(
				'Do not apply to a different industry, market, language, page type, change family or primary metric without separate evidence.',
				'Do not convert an associated signal into a guarantee, ranking promise or causal claim.',
				'Do not skip the Opportunity Engine, brief, editorial review or publishing readiness workflow.',
			),
		);
	}

	private function export_evidence( $limit = 100 ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return array(); }
		$limit = max( 1, min( 500, absint( $limit ) ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->evidence_table()} WHERE is_current=1 ORDER BY observed_at DESC LIMIT %d", $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out = array();
		foreach ( $rows ?: array() as $row ) {
			$context = $this->decode_json( $row['context_json'] ?? '' );
			$out[] = array(
				'source_site_fingerprint' => sanitize_text_field( $row['source_site_hash'] ),
				'source_study_key' => sanitize_text_field( $row['source_study_key'] ),
				'website_mode' => sanitize_key( $context['website_mode'] ?? 'hybrid' ),
				'industry' => sanitize_key( $context['industry'] ?? 'general' ),
				'market' => $this->context_token( $context['market'] ?? 'unspecified' ),
				'language' => $this->context_token( $context['language'] ?? 'en' ),
				'page_type' => sanitize_key( $context['page_type'] ?? 'unknown' ),
				'change_family' => sanitize_key( $context['change_family'] ?? 'other' ),
				'primary_metric' => sanitize_key( $context['primary_metric'] ?? 'clicks' ),
				'outcome' => $this->allowed_outcome( $row['outcome'] ),
				'confidence' => $this->allowed_confidence( $row['confidence'] ),
				'adjusted_change_percent' => null === $row['adjusted_change_percent'] ? null : (float) $row['adjusted_change_percent'],
				'human_decision' => sanitize_key( $row['human_decision'] ),
				'assessment_hash' => sanitize_text_field( $row['assessment_hash'] ),
				'observed_at' => sanitize_text_field( $row['observed_at'] ),
			);
		}
		return $out;
	}

	private function format_pattern( array $row ) {
		foreach ( array( 'id', 'confidence_score', 'study_count', 'usable_study_count', 'site_count', 'usable_site_count', 'positive_count', 'negative_count', 'neutral_count', 'inconclusive_count', 'approved_by' ) as $key ) {
			$row[ $key ] = absint( $row[ $key ] ?? 0 );
		}
		$row['consistency_percent'] = (float) ( $row['consistency_percent'] ?? 0 );
		$row['median_change_percent'] = null === $row['median_change_percent'] ? null : (float) $row['median_change_percent'];
		$row['applicability'] = $this->decode_json( $row['applicability_json'] ?? '' );
		$row['limitations'] = $this->decode_json( $row['limitations_json'] ?? '' );
		$row['eligible_for_validation'] = $this->eligible_for_validation( $row );
		$row['events'] = $this->events( absint( $row['id'] ) );
		unset( $row['applicability_json'], $row['limitations_json'], $row['pattern_key'] );
		return $row;
	}

	private function eligible_for_validation( array $pattern ) {
		return absint( $pattern['usable_site_count'] ?? 0 ) >= 3
			&& absint( $pattern['usable_study_count'] ?? 0 ) >= 5
			&& (float) ( $pattern['consistency_percent'] ?? 0 ) >= 65
			&& in_array( sanitize_key( $pattern['directional_signal'] ?? '' ), array( 'positive_signal', 'negative_signal', 'neutral_signal' ), true )
			&& in_array( sanitize_key( $pattern['confidence'] ?? '' ), array( 'medium', 'high' ), true );
	}

	private function find_evidence( $site_hash, $study_key ) {
		global $wpdb;
		if ( ! $this->tables_ready() ) { return array(); }
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->evidence_table()} WHERE source_site_hash=%s AND source_study_key=%s LIMIT 1", sanitize_text_field( $site_hash ), sanitize_text_field( $study_key ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ?: array();
	}

	private function expire_missing_local_evidence( array $seen ) {
		global $wpdb;
		$site = $this->site_fingerprint();
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,source_study_key FROM {$this->evidence_table()} WHERE source_type='local' AND source_site_hash=%s AND is_current=1", $site ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$seen = array_flip( $seen );
		foreach ( $rows ?: array() as $row ) {
			if ( ! isset( $seen[ $row['source_study_key'] ] ) ) {
				$wpdb->update( $this->evidence_table(), array( 'is_current' => 0, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => absint( $row['id'] ) ) );
			}
		}
	}

	private function events( $pattern_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->events_table()} WHERE pattern_id=%d ORDER BY id DESC LIMIT 20", $pattern_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows ?: array() as &$row ) {
			$row['id'] = absint( $row['id'] );
			$row['actor_id'] = absint( $row['actor_id'] );
			$row['payload'] = $this->decode_json( $row['payload_json'] ?? '' );
			unset( $row['payload_json'] );
		}
		return $rows ?: array();
	}

	private function event( $pattern_id, $type, $notes, array $payload, $user_id ) {
		global $wpdb;
		$wpdb->insert( $this->events_table(), array( 'pattern_id' => absint( $pattern_id ), 'event_type' => sanitize_key( $type ), 'actor_id' => absint( $user_id ), 'notes' => sanitize_textarea_field( $notes ), 'payload_json' => wp_json_encode( $payload ), 'created_at' => current_time( 'mysql', true ) ) );
	}

	private function median( array $values ) {
		if ( ! $values ) { return null; }
		sort( $values, SORT_NUMERIC );
		$count = count( $values );
		$middle = (int) floor( $count / 2 );
		return $count % 2 ? (float) $values[ $middle ] : ( (float) $values[ $middle - 1 ] + (float) $values[ $middle ] ) / 2;
	}

	private function allowed_outcome( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'positive_signal', 'negative_signal', 'neutral_signal', 'inconclusive' ), true ) ? $value : 'inconclusive';
	}

	private function allowed_confidence( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'low', 'medium', 'high' ), true ) ? $value : 'low';
	}

	private function context_token( $value ) {
		$value = strtolower( trim( sanitize_text_field( $value ) ) );
		$value = preg_replace( '/[^a-z0-9_-]+/', '_', $value );
		return substr( trim( $value, '_' ), 0, 60 ) ?: 'unspecified';
	}

	private function mysql_date( $value ) {
		$timestamp = $value ? strtotime( (string) $value . ( false === strpos( (string) $value, 'UTC' ) ? ' UTC' : '' ) ) : false;
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : current_time( 'mysql', true );
	}

	private function site_fingerprint() {
		return hash( 'sha256', strtolower( untrailingslashit( home_url( '/' ) ) ) . '|' . wp_salt( 'auth' ) );
	}

	private function decode_json( $value ) {
		$decoded = is_array( $value ) ? $value : json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}

	private function tables_ready() {
		return $this->table_exists( $this->patterns_table() ) && $this->table_exists( $this->evidence_table() ) && $this->table_exists( $this->events_table() );
	}

	private function can_manage( $user_id ) {
		return $user_id > 0 && user_can( $user_id, 'manage_options' );
	}

	private function can_approve( $user_id ) {
		return $user_id > 0 && ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'publish_pages' ) );
	}

	private function record_history( $category, $title, $summary, $user_id ) {
		$this->history->add( array( 'category' => $category, 'status' => 'completed', 'title' => $title, 'summary' => $summary, 'details' => array( 'component' => 'pattern_library', 'publishes_automatically' => false ) ), 'plugin', $user_id );
	}
}
