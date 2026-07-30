<?php

defined( 'ABSPATH' ) || exit;

class Ikon_SEO_Inventory {
	const CACHE_KEY = 'ikon_seo_inventory_v3';

	public function scan( $refresh = false ) {
		if ( $refresh ) {
			delete_transient( self::CACHE_KEY );
		}

		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			$cached['cached'] = true;
			return $cached;
		}

		$settings = Ikon_SEO_Plugin::settings();
		$limit    = max( 50, min( 2000, absint( $settings['inventory_limit'] ) ) );
		$posts    = get_posts(
			array(
				'post_type'      => array( 'page', 'post' ),
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		$items     = array();
		$url_index = array();
		foreach ( $posts as $post ) {
			$url = get_permalink( $post );
			$key = $this->url_key( $url );
			if ( $key ) {
				$url_index[ $key ] = (int) $post->ID;
			}
		}

		foreach ( $posts as $post ) {
			$items[ $post->ID ] = $this->item( $post, $url_index );
		}

		foreach ( $items as $source_id => $item ) {
			foreach ( $item['internal_link_targets'] as $target_id ) {
				if ( isset( $items[ $target_id ] ) && $source_id !== $target_id ) {
					$items[ $target_id ]['incoming_internal_links']++;
				}
			}
		}

		$home_id = (int) get_option( 'page_on_front' );
		$orphans = array();
		foreach ( $items as $id => &$item ) {
			$item['orphan'] = 'publish' === $item['status'] && 'page' === $item['post_type'] && $home_id !== (int) $id && 0 === $item['incoming_internal_links'];
			if ( $item['orphan'] ) {
				$orphans[] = (int) $id;
			}
			unset( $item['internal_link_targets'] );
		}
		unset( $item );

		$cannibalization = $this->cannibalization_groups( $items );
		$result = array(
			'generated_at'    => current_time( 'mysql', true ),
			'cached'          => false,
			'limit'           => $limit,
			'truncated'       => count( $posts ) >= $limit,
			'summary'         => array(
				'total'                    => count( $items ),
				'published'                => count( array_filter( $items, function( $item ) { return 'publish' === $item['status']; } ) ),
				'orphan_pages'             => count( $orphans ),
				'cannibalization_clusters' => count( $cannibalization ),
				'missing_seo_titles'       => count( array_filter( $items, function( $item ) { return empty( $item['seo_title'] ); } ) ),
				'missing_descriptions'     => count( array_filter( $items, function( $item ) { return empty( $item['seo_description'] ); } ) ),
				'missing_featured_images'  => count( array_filter( $items, function( $item ) { return empty( $item['featured_media_id'] ); } ) ),
			),
			'orphan_page_ids' => $orphans,
			'cannibalization' => $cannibalization,
			'items'           => array_values( $items ),
		);

		set_transient( self::CACHE_KEY, $result, 10 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function candidates( $query, $exclude_id = 0, $limit = 12 ) {
		$inventory = $this->scan();
		$tokens    = $this->tokens( $query );
		$results   = array();

		foreach ( $inventory['items'] as $item ) {
			if ( 'publish' !== $item['status'] || absint( $exclude_id ) === (int) $item['id'] ) {
				continue;
			}
			$haystack = strtolower( $item['title'] . ' ' . $item['slug'] . ' ' . $item['focus_keyword'] );
			$score    = 0;
			foreach ( $tokens as $token ) {
				if ( false !== strpos( $haystack, $token ) ) {
					$score += false !== strpos( strtolower( $item['focus_keyword'] ), $token ) ? 4 : 2;
				}
			}
			if ( $score || ! $tokens ) {
				$results[] = array(
					'id'            => $item['id'],
					'title'         => $item['title'],
					'url'           => $item['url'],
					'focus_keyword' => $item['focus_keyword'],
					'incoming_links'=> $item['incoming_internal_links'],
					'score'         => $score,
				);
			}
		}

		usort(
			$results,
			function( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return $b['incoming_links'] <=> $a['incoming_links'];
				}
				return $b['score'] <=> $a['score'];
			}
		);

		return array_slice( $results, 0, max( 1, min( 30, absint( $limit ) ) ) );
	}

	public function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	private function item( WP_Post $post, array $url_index ) {
		$settings = Ikon_SEO_Plugin::settings();
		$selected = $settings['seo_plugin_preference'];
		if ( 'auto' === $selected ) {
			$selected = defined( 'RANK_MATH_VERSION' ) ? 'rank_math' : ( defined( 'WPSEO_VERSION' ) ? 'yoast' : 'rank_math' );
		}
		$seo_meta = 'yoast' === $selected
			? array(
				'title'       => get_post_meta( $post->ID, '_yoast_wpseo_title', true ),
				'description' => get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ),
				'focus'       => get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true ),
				'canonical'   => get_post_meta( $post->ID, '_yoast_wpseo_canonical', true ),
				'robots'      => array(
					'noindex'  => get_post_meta( $post->ID, '_yoast_wpseo_meta-robots-noindex', true ),
					'nofollow' => get_post_meta( $post->ID, '_yoast_wpseo_meta-robots-nofollow', true ),
				),
			)
			: array(
				'title'       => get_post_meta( $post->ID, 'rank_math_title', true ),
				'description' => get_post_meta( $post->ID, 'rank_math_description', true ),
				'focus'       => get_post_meta( $post->ID, 'rank_math_focus_keyword', true ),
				'canonical'   => get_post_meta( $post->ID, 'rank_math_canonical_url', true ),
				'robots'      => get_post_meta( $post->ID, 'rank_math_robots', true ),
			);
		$elementor = get_post_meta( $post->ID, '_elementor_data', true );
		$decoded   = is_string( $elementor ) ? json_decode( $elementor, true ) : $elementor;
		$urls      = array_merge(
			$this->extract_urls( $post->post_content ),
			$this->elementor_urls( is_array( $decoded ) ? $decoded : array() )
		);
		$urls      = array_values( array_unique( $urls ) );
		$internal  = array();
		$external  = array();
		$targets   = array();
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );

		foreach ( $urls as $url ) {
			if ( ! $this->is_content_link( $url ) ) {
				continue;
			}
			$absolute = 0 === strpos( $url, '/' ) ? home_url( $url ) : $url;
			$host     = strtolower( (string) wp_parse_url( $absolute, PHP_URL_HOST ) );
			if ( ! $host || $home_host === $host ) {
				$internal[] = esc_url_raw( $absolute );
				$key = $this->url_key( $absolute );
				if ( $key && isset( $url_index[ $key ] ) ) {
					$targets[] = $url_index[ $key ];
				}
			} else {
				$external[] = esc_url_raw( $absolute );
			}
		}

		$plain_text = wp_strip_all_tags( $post->post_content );
		if ( is_string( $elementor ) && $elementor ) {
			$plain_text .= ' ' . $this->elementor_text( is_array( $decoded ) ? $decoded : array() );
		}

		return array(
			'id'                      => (int) $post->ID,
			'post_type'               => $post->post_type,
			'title'                   => $post->post_title,
			'slug'                    => $post->post_name,
			'status'                  => $post->post_status,
			'url'                     => get_permalink( $post ),
			'parent_id'               => (int) $post->post_parent,
			'modified_gmt'            => get_post_modified_time( 'c', true, $post ),
			'word_count'              => $this->word_count( $plain_text ),
			'h1'                      => $this->first_h1( $post->post_content, $elementor ),
			'seo_title'               => $seo_meta['title'],
			'seo_description'         => $seo_meta['description'],
			'focus_keyword'           => $seo_meta['focus'],
			'canonical'               => $seo_meta['canonical'],
			'robots'                  => $seo_meta['robots'],
			'featured_media_id'       => get_post_thumbnail_id( $post->ID ),
			'outgoing_internal_links' => count( array_unique( $internal ) ),
			'outgoing_external_links' => count( array_unique( $external ) ),
			'incoming_internal_links' => 0,
			'unresolved_internal_urls'=> array_values( array_filter( array_unique( $internal ), function( $url ) use ( $url_index ) {
				$key = $this->url_key( $url );
				return $key && ! isset( $url_index[ $key ] );
			} ) ),
			'internal_link_targets'   => array_values( array_unique( $targets ) ),
			'managed'                 => (bool) get_post_meta( $post->ID, '_ikon_seo_managed', true ),
			'schema_types'            => $this->schema_types( get_post_meta( $post->ID, '_ikon_seo_schema_graph', true ) ),
		);
	}

	private function cannibalization_groups( array $items ) {
		$groups = array();
		foreach ( $items as $item ) {
			$keywords = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $item['focus_keyword'] ) ) ) );
			foreach ( $keywords as $keyword ) {
				if ( strlen( $keyword ) < 3 ) {
					continue;
				}
				$groups[ $keyword ][] = array(
					'id'     => $item['id'],
					'title'  => $item['title'],
					'url'    => $item['url'],
					'status' => $item['status'],
				);
			}
		}
		return array_filter( $groups, function( $group ) { return count( $group ) > 1; } );
	}

	private function extract_urls( $content ) {
		$content = str_replace( '\/', '/', (string) $content );
		preg_match_all( '~(?:href|url)["\']?\s*[:=]\s*["\'](https?://[^"\']+|/[^"\']+)["\']~i', $content, $matches );
		return array_values( array_unique( array_map( 'html_entity_decode', $matches[1] ?? array() ) ) );
	}

	private function elementor_urls( array $value ) {
		$urls = array();
		foreach ( $value as $item ) {
			if ( is_array( $item ) ) {
				$urls = array_merge( $urls, $this->elementor_urls( $item ) );
			} elseif ( is_string( $item ) ) {
				if ( 0 === strpos( $item, 'https://' ) || 0 === strpos( $item, 'http://' ) || 0 === strpos( $item, '/' ) ) {
					$urls[] = $item;
				}
				$urls = array_merge( $urls, $this->extract_urls( $item ) );
			}
		}
		return array_values( array_unique( $urls ) );
	}

	private function first_h1( $content, $elementor ) {
		if ( preg_match( '/<h1\b[^>]*>(.*?)<\/h1>/is', (string) $content, $match ) ) {
			return trim( wp_strip_all_tags( $match[1] ) );
		}
		$data = is_string( $elementor ) ? json_decode( $elementor, true ) : $elementor;
		return $this->find_elementor_h1( is_array( $data ) ? $data : array() );
	}

	private function find_elementor_h1( array $elements ) {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			if ( 'heading' === ( $element['widgetType'] ?? '' ) && 'h1' === ( $element['settings']['header_size'] ?? '' ) ) {
				return sanitize_text_field( $element['settings']['title'] ?? '' );
			}
			if ( ! empty( $element['elements'] ) ) {
				$found = $this->find_elementor_h1( $element['elements'] );
				if ( $found ) {
					return $found;
				}
			}
		}
		return '';
	}

	private function elementor_text( array $elements ) {
		$text = '';
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			$settings = is_array( $element['settings'] ?? null ) ? $element['settings'] : array();
			foreach ( array( 'title', 'editor', 'text', 'description' ) as $key ) {
				if ( isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ) {
					$text .= ' ' . wp_strip_all_tags( $settings[ $key ] );
				}
			}
			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$text .= ' ' . $this->elementor_text( $element['elements'] );
			}
		}
		return trim( $text );
	}

	private function tokens( $query ) {
		$stop = array( 'and', 'the', 'for', 'with', 'from', 'services', 'service' );
		$raw  = preg_split( '/[^a-z0-9]+/', strtolower( (string) $query ) );
		return array_values( array_filter( array_unique( $raw ), function( $token ) use ( $stop ) {
			return strlen( $token ) > 2 && ! in_array( $token, $stop, true );
		} ) );
	}

	private function url_key( $url ) {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return '/' . trim( $path, '/' );
	}

	private function is_content_link( $url ) {
		$path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		return ! preg_match( '/\.(?:jpe?g|png|gif|webp|svg|avif|css|js|pdf|zip|woff2?|ttf|eot)$/', $path );
	}

	private function word_count( $text ) {
		$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
		return $text ? count( preg_split( '/\s+/u', $text ) ) : 0;
	}

	private function schema_types( $graph ) {
		$types = array();
		foreach ( is_array( $graph ) ? $graph : array() as $node ) {
			foreach ( (array) ( is_array( $node ) ? ( $node['@type'] ?? array() ) : array() ) as $type ) {
				$types[] = sanitize_text_field( $type );
			}
		}
		return array_values( array_unique( $types ) );
	}
}
