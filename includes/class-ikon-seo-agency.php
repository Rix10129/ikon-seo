<?php

defined( 'ABSPATH' ) || exit;

/**
 * Keeps technical workspace controls available only to explicitly approved agency users.
 */
final class Ikon_SEO_Agency {
	const META_KEY = '_ikon_seo_agency_access';

	public static function bootstrap_owner() {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		/*
		 * Recovery rule: when the website has exactly one WordPress
		 * administrator, that administrator must not be locked out of the
		 * agency controls because an older upgrade missed the user-meta flag.
		 */
		if ( self::is_current_user_sole_administrator() ) {
			update_user_meta( $user_id, self::META_KEY, 1 );
			return;
		}

		$users = self::user_ids();
		if ( ! $users ) {
			update_user_meta( $user_id, self::META_KEY, 1 );
		}
	}

	public static function can_manage() {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( (bool) get_user_meta( $user_id, self::META_KEY, true ) ) {
			return true;
		}

		if ( self::is_current_user_sole_administrator() ) {
			update_user_meta( $user_id, self::META_KEY, 1 );
			return true;
		}

		return false;
	}

	private static function is_current_user_sole_administrator() {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$admins = array_map(
			'absint',
			get_users(
				array(
					'role__in' => array( 'administrator' ),
					'fields'   => 'ids',
				)
			)
		);

		return 1 === count( $admins ) && (int) $admins[0] === (int) $user_id;
	}

	public static function user_ids() {
		return array_map(
			'absint',
			get_users(
				array(
					'meta_key'   => self::META_KEY,
					'meta_value' => 1,
					'fields'     => 'ids',
				)
			)
		);
	}

	public static function set_user_ids( array $user_ids ) {
		$allowed = array_values( array_unique( array_filter( array_map( 'absint', $user_ids ) ) ) );
		if ( get_current_user_id() ) {
			$allowed[] = get_current_user_id();
			$allowed   = array_values( array_unique( $allowed ) );
		}
		$admins  = get_users(
			array(
				'role__in' => array( 'administrator' ),
				'fields'   => 'ids',
			)
		);

		foreach ( $admins as $admin_id ) {
			if ( in_array( (int) $admin_id, $allowed, true ) ) {
				update_user_meta( $admin_id, self::META_KEY, 1 );
			} else {
				delete_user_meta( $admin_id, self::META_KEY );
			}
		}

		if ( ! self::user_ids() && get_current_user_id() ) {
			update_user_meta( get_current_user_id(), self::META_KEY, 1 );
		}
	}

	public static function customer_tabs() {
		return array(
			'dashboard',
			'strategy',
			'workflow-automation',
			'publisher-intelligence',
			'reviews',
			'history',
			'inventory',
			'seo-health',
			'diagnostics',
			'search-intelligence',
			'opportunity-engine',
			'content-workbench',
			'editorial-review',
			'publishing-readiness',
			'search-impact',
			'agency-governance',
			'content-intelligence',
			'authority-intelligence',
			'visibility-brand',
			'closed-loop',
			'indexation',
			'governance',
			'experiments-claims-revenue',
			'international-server',
			'portfolio-quality',
			'technical-intelligence',
			'analytics',
			'image-audit',
			'redirects',
			'local-growth',
			'local-seo',
			'business-profile',
			'queue',
			'monitor',
		);
	}
}
