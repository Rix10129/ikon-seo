<?php

defined( 'ABSPATH' ) || exit;

/**
 * Read-only compatibility and conflict diagnostics for Rank Math.
 */
final class Ikon_SEO_Rank_Math {
	const CACHE_KEY = 'ikon_seo_rank_math_audit_v1';

	private $inventory;

	public function __construct( Ikon_SEO_Inventory $inventory ) {
		$this->inventory = $inventory;
	}

	public function audit( $refresh = false ) {
		if ( $refresh ) {
			delete_transient( self::CACHE_KEY );
		}

		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			$cached['cached'] = true;
			return $cached;
		}

		$inventory = $this->inventory->scan( $refresh );
		$active    = defined( 'RANK_MATH_VERSION' ) || class_exists( '\\RankMath\\Helper' );
		$modules_option = get_option( 'rank_math_modules', array() );
		$modules = array();
		if ( is_array( $modules_option ) ) {
			$keys    = array_keys( $modules_option );
			$is_list = $keys === range( 0, count( $modules_option ) - 1 );
			if ( $is_list ) {
				$modules = $modules_option;
			} else {
				foreach ( $modules_option as $module => $enabled ) {
					if ( $enabled ) {
						$modules[] = $module;
					}
				}
			}
		}
		$modules = array_values( array_unique( array_filter( array_map( 'sanitize_key', $modules ) ) ) );
		$titles    = get_option( 'rank-math-options-titles', array() );
		$sitemap   = get_option( 'rank-math-options-sitemap', array() );
		$general   = get_option( 'rank-math-options-general', array() );

		$title_map       = array();
		$description_map = array();
		$canonical_map   = array();
		$issues          = array();
		$schema_conflicts= array();

		foreach ( (array) ( $inventory['items'] ?? array() ) as $item ) {
			$id = absint( $item['id'] ?? 0 );
			if ( ! $id ) {
				continue;
			}

			$title = $this->normalize( $item['seo_title'] ?? '' );
			$desc  = $this->normalize( $item['seo_description'] ?? '' );
			$canon = untrailingslashit( strtolower( esc_url_raw( $item['canonical'] ?? '' ) ) );
			if ( $title ) {
				$title_map[ $title ][] = $item;
			}
			if ( $desc ) {
				$description_map[ $desc ][] = $item;
			}
			if ( $canon ) {
				$canonical_map[ $canon ][] = $item;
			}

			if ( 'publish' === ( $item['status'] ?? '' ) ) {
				if ( ! $title ) {
					$issues[] = $this->issue( 'missing_title', 'high', $item, 'Published page has no Rank Math SEO title.' );
				}
				if ( ! $desc ) {
					$issues[] = $this->issue( 'missing_description', 'medium', $item, 'Published page has no Rank Math meta description.' );
				}
				if ( $this->is_noindex( $item['robots'] ?? array() ) ) {
					$issues[] = $this->issue( 'published_noindex', 'high', $item, 'Published page is configured as noindex.' );
				}
			}

			if ( $canon && ! $this->same_site( $canon ) ) {
				$issues[] = $this->issue( 'external_canonical', 'high', $item, 'Canonical URL points to another hostname.' );
			}

			$ikon_types = array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $item['schema_types'] ?? array() ) ) ) );
			$rank_types = $this->rank_math_schema_types( $id );
			$duplicates = array_values( array_intersect( $ikon_types, $rank_types ) );
			if ( $duplicates ) {
				$schema_conflicts[] = array(
					'id'        => $id,
					'title'     => sanitize_text_field( $item['title'] ?? '' ),
					'url'       => esc_url_raw( $item['url'] ?? '' ),
					'duplicates'=> $duplicates,
				);
			}
		}

		$duplicates = array(
			'titles'       => $this->duplicate_groups( $title_map ),
			'descriptions' => $this->duplicate_groups( $description_map ),
			'canonicals'   => $this->duplicate_groups( $canonical_map ),
		);

		$result = array(
			'generated_at' => current_time( 'mysql', true ),
			'cached'       => false,
			'active'       => $active,
			'version'      => defined( 'RANK_MATH_VERSION' ) ? RANK_MATH_VERSION : '',
			'pro_active'   => defined( 'RANK_MATH_PRO_VERSION' ) || defined( 'RANK_MATH_PRO_FILE' ),
			'pro_version'  => defined( 'RANK_MATH_PRO_VERSION' ) ? RANK_MATH_PRO_VERSION : '',
			'modules'      => $modules,
			'settings'     => array(
				'titles_configured'  => is_array( $titles ) && ! empty( $titles ),
				'sitemap_configured' => is_array( $sitemap ) && ! empty( $sitemap ),
				'general_configured' => is_array( $general ) && ! empty( $general ),
				'sitemap_url'        => home_url( '/sitemap_index.xml' ),
			),
			'summary' => array(
				'pages_checked'       => count( (array) ( $inventory['items'] ?? array() ) ),
				'issues'              => count( $issues ),
				'high_priority'       => count( array_filter( $issues, function( $issue ) { return 'high' === $issue['priority']; } ) ),
				'duplicate_titles'    => count( $duplicates['titles'] ),
				'duplicate_descriptions' => count( $duplicates['descriptions'] ),
				'schema_conflicts'    => count( $schema_conflicts ),
			),
			'issues'           => array_slice( $issues, 0, 250 ),
			'duplicates'       => $duplicates,
			'schema_conflicts' => $schema_conflicts,
			'recommendations'  => $this->recommendations( $active, $modules, $issues, $duplicates, $schema_conflicts ),
		);

		set_transient( self::CACHE_KEY, $result, 10 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	private function normalize( $value ) {
		$value = strtolower( trim( wp_strip_all_tags( (string) $value ) ) );
		return preg_replace( '/\\s+/', ' ', $value );
	}

	private function is_noindex( $robots ) {
		if ( is_array( $robots ) ) {
			return in_array( 'noindex', array_map( 'strtolower', $robots ), true ) || ! empty( $robots['noindex'] );
		}
		return false !== stripos( (string) $robots, 'noindex' );
	}

	private function same_site( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$home = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		return ! $host || $host === $home;
	}

	private function issue( $type, $priority, array $item, $message ) {
		return array(
			'type'     => sanitize_key( $type ),
			'priority' => sanitize_key( $priority ),
			'id'       => absint( $item['id'] ?? 0 ),
			'title'    => sanitize_text_field( $item['title'] ?? '' ),
			'url'      => esc_url_raw( $item['url'] ?? '' ),
			'message'  => sanitize_text_field( $message ),
		);
	}

	private function duplicate_groups( array $map ) {
		$groups = array();
		foreach ( $map as $value => $items ) {
			if ( count( $items ) < 2 ) {
				continue;
			}
			$groups[] = array(
				'value' => $value,
				'pages' => array_map(
					function( $item ) {
						return array(
							'id'    => absint( $item['id'] ?? 0 ),
							'title' => sanitize_text_field( $item['title'] ?? '' ),
							'url'   => esc_url_raw( $item['url'] ?? '' ),
						);
					},
					$items
				),
			);
		}
		return $groups;
	}

	private function rank_math_schema_types( $post_id ) {
		$types = array();
		$all   = get_post_meta( $post_id );
		foreach ( is_array( $all ) ? $all : array() as $key => $values ) {
			if ( 0 !== strpos( $key, 'rank_math_schema_' ) ) {
				continue;
			}
			foreach ( (array) $values as $value ) {
				$decoded = maybe_unserialize( $value );
				if ( is_string( $decoded ) ) {
					$json = json_decode( $decoded, true );
					$decoded = is_array( $json ) ? $json : $decoded;
				}
				if ( is_array( $decoded ) && ! empty( $decoded['@type'] ) ) {
					$types = array_merge( $types, (array) $decoded['@type'] );
				}
			}
		}
		return array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $types ) ) ) );
	}

	private function recommendations( $active, array $modules, array $issues, array $duplicates, array $schema_conflicts ) {
		$items = array();
		if ( ! $active ) {
			$items[] = 'Rank Math is not active. Ikon SEO will continue using its fallback metadata and schema safeguards.';
			return $items;
		}
		if ( ! in_array( 'sitemap', $modules, true ) ) {
			$items[] = 'Review the Rank Math Sitemap module before relying on sitemap diagnostics.';
		}
		if ( $issues ) {
			$items[] = 'Review high-priority metadata and indexing issues before creating more pages.';
		}
		if ( $duplicates['titles'] || $duplicates['descriptions'] ) {
			$items[] = 'Resolve duplicate titles and descriptions to make page intent clearer.';
		}
		if ( $schema_conflicts ) {
			$items[] = 'Remove duplicate schema ownership: keep Rank Math as renderer and use Ikon SEO for controlled additions.';
		}
		if ( ! $items ) {
			$items[] = 'No major Rank Math compatibility conflicts were detected in the current scan.';
		}
		return $items;
	}
}
