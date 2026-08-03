<?php

defined( 'ABSPATH' ) || exit;

/**
 * Previews and applies explicit domain changes to Ikon SEO managed data.
 *
 * The tool never runs automatically. An administrator must preview the exact
 * old and new bases, confirm the change, and apply it from the dashboard.
 */
class Ikon_SEO_Migration {
	const SNAPSHOT_META = '_ikon_seo_domain_migration_snapshots';

	private $logger;
	private $inventory;
	private $local;

	public function __construct( Ikon_SEO_Logger $logger, Ikon_SEO_Inventory $inventory, Ikon_SEO_Local $local ) {
		$this->logger    = $logger;
		$this->inventory = $inventory;
		$this->local     = $local;
	}

	public function report( $old_url, $new_url ) {
		$pair = $this->validated_pair( $old_url, $new_url );
		if ( is_wp_error( $pair ) ) {
			return $pair;
		}

		$items = array();
		$ids   = get_posts(
			array(
				'post_type'      => array( 'page', 'post' ),
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => max( 50, min( 2000, absint( Ikon_SEO_Plugin::settings()['inventory_limit'] ) ) ),
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		foreach ( $ids as $post_id ) {
			$fields = $this->affected_fields( $post_id, $pair['old'] );
			if ( $fields ) {
				$items[] = array(
					'post_id' => absint( $post_id ),
					'title'   => get_the_title( $post_id ),
					'status'  => get_post_status( $post_id ),
					'fields'  => $fields,
				);
			}
		}

		return array(
			'old_url'       => $pair['old'],
			'new_url'       => $pair['new'],
			'affected_posts'=> count( $items ),
			'items'         => $items,
			'connection_key_will_be_revoked' => true,
			'google_connections_will_be_reset' => true,
			'local_records_will_be_rebound' => true,
			'automatic'     => false,
		);
	}

	public function apply( $old_url, $new_url, $request_id = '' ) {
		$report = $this->report( $old_url, $new_url );
		if ( is_wp_error( $report ) ) {
			return $report;
		}

		$changed = 0;
		foreach ( $report['items'] as $item ) {
			$post_id  = absint( $item['post_id'] );
			$snapshot = array(
				'id'         => wp_generate_uuid4(),
				'created_at' => current_time( 'mysql', true ),
				'old_url'    => $report['old_url'],
				'new_url'    => $report['new_url'],
				'values'     => array(),
			);

			foreach ( $item['fields'] as $field ) {
				$value = 'post_content' === $field ? get_post_field( 'post_content', $post_id, 'raw' ) : get_post_meta( $post_id, $field, true );
				$snapshot['values'][ $field ] = $value;
				$updated = $this->replace_recursive( $value, $report['old_url'], $report['new_url'] );
				if ( 'post_content' === $field ) {
					wp_update_post( wp_slash( array( 'ID' => $post_id, 'post_content' => $updated ) ) );
				} else {
					update_post_meta( $post_id, $field, $updated );
				}
			}

			$snapshots   = get_post_meta( $post_id, self::SNAPSHOT_META, true );
			$snapshots   = is_array( $snapshots ) ? $snapshots : array();
			array_unshift( $snapshots, $snapshot );
			update_post_meta( $post_id, self::SNAPSHOT_META, array_slice( $snapshots, 0, 3 ) );
			delete_post_meta( $post_id, '_elementor_css' );
			clean_post_cache( $post_id );
			$changed++;
		}

		$settings                     = Ikon_SEO_Plugin::settings();
		$old_profile_id               = ( new Ikon_SEO_Profile() )->fingerprint( $settings );
		$settings['business_url']     = $report['new_url'] . '/';
		$settings['profile_home_url'] = $report['new_url'] . '/';
		$settings['token_hash']       = '';
		$settings['token_hint']       = '';
		$settings['connection_verified_at'] = '';
		$settings['connection_last_seen_at']= '';
		$settings['remote_actions']   = 0;
		$settings['gsc_client_secret']= '';
		$settings['gsc_refresh_token']= '';
		$settings['gsc_property']     = '';
		$settings['gsc_last_error']   = '';
		$settings['ga_client_secret'] = '';
		$settings['ga_refresh_token'] = '';
		$settings['ga_property'] = '';
		$settings['ga_last_error'] = '';
		$settings['pagespeed_api_key'] = '';
		$settings['pagespeed_last_error'] = '';
		$settings['gbp_client_secret']= '';
		$settings['gbp_refresh_token']= '';
		$settings['gbp_account']      = '';
		$settings['gbp_last_error']   = '';
		$new_profile_id               = ( new Ikon_SEO_Profile() )->fingerprint( $settings );
		$local_records                = $this->local->rebind_profile( $old_profile_id, $new_profile_id, $report['old_url'], $report['new_url'] );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );
		$this->inventory->clear_cache();
		$this->logger->log( 'domain_migration', 'success', 'Domain references updated; connection key revoked and Google connections reset.', null, null, array(), $request_id );

		return array(
			'ok'                => true,
			'changed_posts'     => $changed,
			'old_url'           => $report['old_url'],
			'new_url'           => $report['new_url'],
			'connection_revoked'=> true,
			'search_console_reset'=> true,
			'business_profile_reset'=> true,
			'local_records_rebound'=> $local_records,
		);
	}

	private function affected_fields( $post_id, $old_url ) {
		$fields = array(
			'post_content',
			'_elementor_data',
			'_ikon_seo_schema_graph',
			'_ikon_seo_source_url',
			'rank_math_canonical_url',
			'rank_math_facebook_image',
			'rank_math_twitter_image',
		);
		$output = array();
		foreach ( $fields as $field ) {
			$value = 'post_content' === $field ? get_post_field( 'post_content', $post_id, 'raw' ) : get_post_meta( $post_id, $field, true );
			$flat  = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );
			if ( false !== strpos( (string) $flat, $old_url ) ) {
				$output[] = $field;
			}
		}
		return $output;
	}

	private function validated_pair( $old_url, $new_url ) {
		$old = untrailingslashit( esc_url_raw( $old_url ) );
		$new = untrailingslashit( esc_url_raw( $new_url ) );
		if ( ! wp_http_validate_url( $old ) || ! wp_http_validate_url( $new ) ) {
			return new WP_Error( 'ikon_seo_migration_url', 'Enter valid old and new HTTP or HTTPS website URLs.', array( 'status' => 400 ) );
		}
		if ( $old === $new ) {
			return new WP_Error( 'ikon_seo_migration_same', 'The old and new website URLs must be different.', array( 'status' => 400 ) );
		}
		return array( 'old' => $old, 'new' => $new );
	}

	private function replace_recursive( $value, $old_url, $new_url ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = $this->replace_recursive( $item, $old_url, $new_url );
			}
			return $value;
		}
		if ( is_string( $value ) ) {
			return str_replace( $old_url, $new_url, $value );
		}
		return $value;
	}
}
