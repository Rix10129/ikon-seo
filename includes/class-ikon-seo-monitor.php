<?php

defined( 'ABSPATH' ) || exit;

/**
 * Produces refresh recommendations without rewriting or publishing content.
 */
class Ikon_SEO_Monitor {
	private $search_console;
	private $logger;

	public function __construct( Ikon_SEO_Search_Console $search_console, Ikon_SEO_Logger $logger ) {
		$this->search_console = $search_console;
		$this->logger         = $logger;
	}

	public function register_hooks() {
		add_action( 'ikon_seo_daily_monitor', array( $this, 'run_daily' ) );
	}

	public function run_daily( $force = false ) {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['monitoring_enabled'] ) && ! $force ) {
			return;
		}

		$performance = null;
		if ( $this->search_console->status()['connected'] && $this->search_console->status()['property'] ) {
			$performance = $this->search_console->performance( 28, true );
		}

		$summary = $this->summary( $performance );
		set_transient( 'ikon_seo_monitor_summary', $summary, DAY_IN_SECONDS );
		$this->logger->log(
			'monitor',
			'success',
			sprintf(
				'Refresh monitor completed: %d overdue, %d due soon and %d performance alerts.',
				$summary['counts']['overdue'],
				$summary['counts']['due_soon'],
				$summary['counts']['performance']
			)
		);
	}

	public function summary( $performance = null ) {
		$settings  = Ikon_SEO_Plugin::settings();
		$today     = gmdate( 'Y-m-d' );
		$soon      = gmdate( 'Y-m-d', time() + max( 1, absint( $settings['review_alert_days'] ) ) * DAY_IN_SECONDS );
		$scheduled = $this->scheduled_items( $soon );
		$items     = array();
		$counts    = array( 'overdue' => 0, 'due_soon' => 0, 'performance' => 0 );

		foreach ( $scheduled as $item ) {
			$item['reason'] = $item['next_review_date'] < $today ? 'overdue' : 'due_soon';
			$counts[ $item['reason'] ]++;
			$items[ 'post-' . $item['post_id'] ] = $item;
		}

		if ( null === $performance ) {
			$performance = get_transient( Ikon_SEO_Search_Console::CACHE_KEY );
		}
		if ( is_array( $performance ) ) {
			foreach ( (array) ( $performance['top_pages'] ?? array() ) as $row ) {
				$decline  = $row['impressions_change'];
				$previous = (float) ( $row['previous_impressions'] ?? 0 );
				if ( null === $decline || $previous < absint( $settings['performance_min_impressions'] ) || $decline > -1 * absint( $settings['performance_drop_percent'] ) ) {
					continue;
				}
				$post_id = url_to_postid( $row['key'] );
				if ( ! $post_id ) {
					continue;
				}
				$key = 'post-' . $post_id;
				if ( ! isset( $items[ $key ] ) ) {
					$post = get_post( $post_id );
					if ( ! $post ) {
						continue;
					}
					$items[ $key ] = array(
						'post_id'          => $post_id,
						'title'            => $post->post_title,
						'url'              => get_permalink( $post ),
						'edit_url'         => get_edit_post_link( $post_id, 'raw' ),
						'next_review_date' => get_post_meta( $post_id, '_ikon_seo_next_review', true ),
						'reason'           => 'performance',
					);
				}
				$items[ $key ]['performance'] = array(
					'impressions_change' => $decline,
					'clicks_change'      => $row['clicks_change'],
					'current_impressions'=> $row['impressions'],
					'previous_impressions'=> $previous,
				);
				$items[ $key ]['reason'] = 'performance';
				$counts['performance']++;
			}
		}

		return array(
			'enabled'       => (bool) $settings['monitoring_enabled'],
			'counts'        => $counts,
			'items'         => array_values( $items ),
			'thresholds'    => array(
				'alert_days'              => absint( $settings['review_alert_days'] ),
				'performance_drop_percent'=> absint( $settings['performance_drop_percent'] ),
				'minimum_impressions'     => absint( $settings['performance_min_impressions'] ),
			),
			'next_cron_gmt' => $this->next_cron(),
			'generated_at'  => current_time( 'mysql', true ),
		);
	}

	public function sync_post_review( $post_id, array $review ) {
		$next = sanitize_text_field( $review['next_review_date'] ?? '' );
		$fact = sanitize_text_field( $review['fact_checked_date'] ?? '' );
		if ( $next && $this->valid_date( $next ) ) {
			update_post_meta( $post_id, '_ikon_seo_next_review', $next );
		} else {
			delete_post_meta( $post_id, '_ikon_seo_next_review' );
		}
		if ( $fact && $this->valid_date( $fact ) ) {
			update_post_meta( $post_id, '_ikon_seo_last_reviewed', $fact );
		}
	}

	public function mark_reviewed( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, array( 'page', 'post' ), true ) ) {
			return new WP_Error( 'ikon_seo_monitor_post', __( 'The page or post was not found.', 'ikon-seo' ) );
		}
		$days = max( 30, min( 730, absint( Ikon_SEO_Plugin::settings()['default_review_days'] ) ) );
		update_post_meta( $post_id, '_ikon_seo_last_reviewed', gmdate( 'Y-m-d' ) );
		update_post_meta( $post_id, '_ikon_seo_next_review', gmdate( 'Y-m-d', time() + $days * DAY_IN_SECONDS ) );
		return array(
			'post_id'          => absint( $post_id ),
			'last_reviewed'    => get_post_meta( $post_id, '_ikon_seo_last_reviewed', true ),
			'next_review_date' => get_post_meta( $post_id, '_ikon_seo_next_review', true ),
		);
	}

	private function scheduled_items( $end_date ) {
		$query = new WP_Query(
			array(
				'post_type'      => array( 'page', 'post' ),
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'posts_per_page' => 200,
				'meta_key'       => '_ikon_seo_next_review',
				'meta_value'     => $end_date,
				'meta_compare'   => '<=',
				'meta_type'      => 'DATE',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		return array_map(
			function( $post ) {
				return array(
					'post_id'          => absint( $post->ID ),
					'title'            => $post->post_title,
					'status'           => $post->post_status,
					'url'              => get_permalink( $post ),
					'edit_url'         => get_edit_post_link( $post->ID, 'raw' ),
					'last_reviewed'    => get_post_meta( $post->ID, '_ikon_seo_last_reviewed', true ),
					'next_review_date' => get_post_meta( $post->ID, '_ikon_seo_next_review', true ),
				);
			},
			$query->posts
		);
	}

	private function next_cron() {
		$timestamp = wp_next_scheduled( 'ikon_seo_daily_monitor' );
		return $timestamp ? gmdate( 'c', $timestamp ) : '';
	}

	private function valid_date( $value ) {
		$date = DateTime::createFromFormat( 'Y-m-d', $value );
		return $date && $date->format( 'Y-m-d' ) === $value;
	}
}
