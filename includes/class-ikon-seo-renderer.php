<?php

defined( 'ABSPATH' ) || exit;

class Ikon_SEO_Renderer {
	private $settings;

	public function __construct() {
		$this->settings = Ikon_SEO_Plugin::settings();
	}

	public function render( array $payload ) {
		$hero     = isset( $payload['hero'] ) && is_array( $payload['hero'] ) ? $payload['hero'] : array();
		$sections = isset( $payload['sections'] ) && is_array( $payload['sections'] ) ? $payload['sections'] : array();
		$faq      = isset( $payload['faq'] ) && is_array( $payload['faq'] ) ? $payload['faq'] : array();

		if ( empty( $hero['title'] ) ) {
			$hero['title'] = $payload['title'] ?? '';
		}
		if ( empty( $hero['description'] ) && ! empty( $payload['excerpt'] ) ) {
			$hero['description'] = $payload['excerpt'];
		}

		$elementor = array( $this->render_hero_elementor( $hero ) );
		$html      = $this->render_hero_html( $hero );

		foreach ( $sections as $index => $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			$elementor[] = $this->render_section_elementor( $section, $index );
			$html       .= $this->render_section_html( $section );
		}

		$review = isset( $payload['content_review'] ) && is_array( $payload['content_review'] ) ? $payload['content_review'] : array();
		if ( ! empty( $review['show_on_page'] ) ) {
			$review_section = $this->review_section( $review );
			$elementor[]    = $this->render_section_elementor( $review_section, count( $sections ) );
			$html          .= $this->render_section_html( $review_section );
		}

		if ( $faq ) {
			$faq_section = array(
				'type'    => 'faq',
				'heading' => $payload['faq_heading'] ?? 'Frequently Asked Questions',
				'items'   => $faq,
			);
			$elementor[] = $this->render_section_elementor( $faq_section, count( $sections ) );
			$html       .= $this->render_section_html( $faq_section );
		}

		return array(
			'elementor_data' => $elementor,
			'post_content'   => $html,
		);
	}

	private function review_section( array $review ) {
		$details = array();
		foreach ( array(
			'reviewed_by'       => 'Reviewed by',
			'fact_checked_date' => 'Fact checked',
			'jurisdiction'      => 'Jurisdiction',
			'applicable_period' => 'Applicable period',
			'next_review_date'  => 'Next review',
		) as $key => $label ) {
			if ( ! empty( $review[ $key ] ) ) {
				$details[] = '<strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $review[ $key ] );
			}
		}
		if ( ! empty( $review['disclaimer'] ) ) {
			$details[] = esc_html( $review['disclaimer'] );
		}
		return array(
			'type'    => 'notice',
			'heading' => sanitize_text_field( $review['heading'] ?? 'Content review information' ),
			'content' => '<p>' . implode( '<br>', $details ) . '</p>',
		);
	}

	public function extract_readable_blocks( $elementor_json ) {
		$data = is_string( $elementor_json ) ? json_decode( $elementor_json, true ) : $elementor_json;
		if ( ! is_array( $data ) ) {
			return array();
		}

		$blocks = array();
		$this->walk_elements( $data, $blocks );
		return $blocks;
	}

	private function walk_elements( array $elements, array &$blocks ) {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$type     = $element['widgetType'] ?? '';
			$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();

			if ( 'heading' === $type && ! empty( $settings['title'] ) ) {
				$blocks[] = array(
					'type'    => 'heading',
					'level'   => $settings['header_size'] ?? 'h2',
					'content' => wp_strip_all_tags( $settings['title'] ),
				);
			} elseif ( 'text-editor' === $type && ! empty( $settings['editor'] ) ) {
				$blocks[] = array(
					'type'    => 'content',
					'content' => wp_kses_post( $settings['editor'] ),
				);
			} elseif ( 'button' === $type && ! empty( $settings['text'] ) ) {
				$blocks[] = array(
					'type'    => 'button',
					'content' => sanitize_text_field( $settings['text'] ),
					'url'     => esc_url_raw( $settings['link']['url'] ?? '' ),
				);
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$this->walk_elements( $element['elements'], $blocks );
			}
		}
	}

	private function render_hero_elementor( array $hero ) {
		$elements = array();

		if ( ! empty( $hero['eyebrow'] ) ) {
			$elements[] = $this->heading_widget( $hero['eyebrow'], 'div', array(
				'typography_typography' => 'custom',
				'typography_font_size'  => array( 'unit' => 'px', 'size' => 15, 'sizes' => array() ),
				'title_color'           => $this->settings['accent_color'],
			) );
		}

		$elements[] = $this->heading_widget( $hero['title'] ?? '', 'h1', array(
			'title_color'           => '#FFFFFF',
			'typography_typography' => 'custom',
			'typography_font_size'  => array( 'unit' => 'px', 'size' => 54, 'sizes' => array() ),
			'typography_font_size_mobile' => array( 'unit' => 'px', 'size' => 36, 'sizes' => array() ),
			'typography_font_weight'=> '700',
		) );

		if ( ! empty( $hero['description'] ) ) {
			$elements[] = $this->text_widget(
				'<div style="font-size:19px;line-height:1.65;color:#E6EEF4;">' . $this->safe_html( $hero['description'] ) . '</div>'
			);
		}

		$buttons = array();
		if ( ! empty( $hero['primary_cta']['label'] ) && ! empty( $hero['primary_cta']['url'] ) ) {
			$buttons[] = $this->button_widget( $hero['primary_cta']['label'], $hero['primary_cta']['url'], 'success' );
		}
		if ( ! empty( $hero['secondary_cta']['label'] ) && ! empty( $hero['secondary_cta']['url'] ) ) {
			$buttons[] = $this->button_widget( $hero['secondary_cta']['label'], $hero['secondary_cta']['url'], 'info' );
		}
		if ( $buttons ) {
			$elements[] = $this->container(
				array(
					'content_width'  => 'full',
					'flex_direction' => 'row',
					'flex_gap'       => array( 'unit' => 'px', 'size' => 14, 'sizes' => array() ),
					'flex_wrap'      => 'wrap',
				),
				$buttons
			);
		}

		$hero_elements = $elements;
		$image_id      = absint( $hero['image_id'] ?? 0 );
		if ( $image_id && wp_attachment_is_image( $image_id ) ) {
			$hero_elements = array(
				$this->container(
					array(
						'content_width'  => 'full',
						'flex_direction' => 'row',
						'flex_wrap'      => 'wrap',
						'align_items'    => 'center',
						'flex_gap'       => array( 'unit' => 'px', 'size' => 48, 'sizes' => array() ),
					),
					array(
						$this->container(
							array(
								'width'        => array( 'unit' => '%', 'size' => 57, 'sizes' => array() ),
								'width_mobile' => array( 'unit' => '%', 'size' => 100, 'sizes' => array() ),
							),
							$elements
						),
						$this->container(
							array(
								'width'        => array( 'unit' => '%', 'size' => 38, 'sizes' => array() ),
								'width_mobile' => array( 'unit' => '%', 'size' => 100, 'sizes' => array() ),
							),
							array( $this->image_widget( $image_id, $hero['image_alt'] ?? $hero['title'] ?? '' ) )
						),
					)
				),
			);
		}

		return $this->container(
			array(
				'content_width'               => 'boxed',
				'boxed_width'                 => $this->size( (int) $this->settings['content_width'] ),
				'min_height'                  => $this->size( 520 ),
				'justify_content'             => 'center',
				'padding'                     => $this->dimensions( 90, 30, 90, 30 ),
				'padding_mobile'              => $this->dimensions( 60, 20, 60, 20 ),
				'background_background'       => 'classic',
				'background_color'            => $this->settings['primary_color'],
				'background_overlay_background'=> 'gradient',
				'background_overlay_color'    => $this->settings['secondary_color'],
				'background_overlay_color_b'  => $this->settings['primary_color'],
				'background_overlay_opacity'  => array( 'unit' => 'px', 'size' => 0.94, 'sizes' => array() ),
			),
			$hero_elements
		);
	}

	private function render_section_elementor( array $section, $index ) {
		$type       = sanitize_key( $section['type'] ?? 'content' );
		$elements   = array();
		$background = 1 === ( $index % 2 ) ? $this->settings['surface_color'] : '#FFFFFF';

		if ( ! empty( $section['eyebrow'] ) ) {
			$elements[] = $this->heading_widget( $section['eyebrow'], 'div', array( 'title_color' => $this->settings['accent_color'] ) );
		}
		if ( ! empty( $section['heading'] ) ) {
			$elements[] = $this->heading_widget(
				$section['heading'],
				'h2',
				'cta' === $type ? array( 'title_color' => '#FFFFFF' ) : array()
			);
		}
		if ( ! empty( $section['intro'] ) ) {
			$elements[] = $this->text_widget( $this->safe_html( $section['intro'] ) );
		}

		switch ( $type ) {
			case 'cards':
			case 'features':
			case 'process':
				$elements[] = $this->cards_elementor( $section['items'] ?? array(), 'process' === $type );
				break;
			case 'checklist':
			case 'documents':
				$elements[] = $this->text_widget( $this->list_html( $section['items'] ?? array(), true ) );
				break;
			case 'table':
				$elements[] = $this->text_widget( $this->table_html( $section ) );
				break;
			case 'faq':
				$elements[] = $this->text_widget( $this->faq_html( $section['items'] ?? array() ) );
				break;
			case 'links':
			case 'related-links':
			case 'sources':
				$elements[] = $this->text_widget( $this->links_html( $section['items'] ?? array() ) );
				break;
			case 'stats':
				$elements[] = $this->stats_elementor( $section['items'] ?? array() );
				break;
			case 'split-content':
				$elements[] = $this->split_content_elementor( $section );
				break;
			case 'trust':
			case 'expert':
				if ( ! empty( $section['content'] ) ) {
					$elements[] = $this->text_widget( $this->safe_html( $section['content'] ) );
				}
				if ( ! empty( $section['items'] ) ) {
					$elements[] = $this->cards_elementor( $section['items'] );
				}
				if ( ! empty( $section['image_id'] ) && wp_attachment_is_image( absint( $section['image_id'] ) ) ) {
					$elements[] = $this->image_widget( absint( $section['image_id'] ), $section['image_alt'] ?? $section['heading'] ?? '' );
				}
				break;
			case 'location-details':
			case 'local-proof':
			case 'reviews':
				if ( ! empty( $section['content'] ) ) {
					$elements[] = $this->text_widget( $this->safe_html( $section['content'] ) );
				}
				if ( ! empty( $section['items'] ) ) {
					$elements[] = $this->cards_elementor( $section['items'] );
				}
				break;
			case 'service-area':
				if ( ! empty( $section['content'] ) ) {
					$elements[] = $this->text_widget( $this->safe_html( $section['content'] ) );
				}
				if ( ! empty( $section['items'] ) ) {
					$elements[] = $this->text_widget( $this->list_html( $section['items'], true ) );
				}
				break;
			case 'map':
				$map = $this->map_elementor( $section );
				if ( $map ) {
					$elements[] = $map;
				}
				break;
			case 'notice':
				if ( ! empty( $section['content'] ) ) {
					$elements[] = $this->text_widget( $this->safe_html( $section['content'] ) );
				}
				$background = '#EAF7F3';
				break;
			case 'cta':
				if ( ! empty( $section['content'] ) ) {
					$elements[] = $this->text_widget( $this->safe_html( $section['content'] ), '#FFFFFF' );
				}
				if ( ! empty( $section['button']['label'] ) && ! empty( $section['button']['url'] ) ) {
					$elements[] = $this->button_widget( $section['button']['label'], $section['button']['url'], 'success' );
				}
				$background = $this->settings['secondary_color'];
				break;
			default:
				if ( ! empty( $section['content'] ) ) {
					$elements[] = $this->text_widget( $this->safe_html( $section['content'] ) );
				}
				if ( ! empty( $section['items'] ) ) {
					$elements[] = $this->text_widget( $this->list_html( $section['items'] ) );
				}
				break;
		}

		$settings = array(
			'content_width'         => 'boxed',
			'boxed_width'           => $this->size( (int) $this->settings['content_width'] ),
			'padding'               => $this->dimensions( 75, 30, 75, 30 ),
			'padding_mobile'        => $this->dimensions( 52, 20, 52, 20 ),
			'background_background' => 'classic',
			'background_color'      => $background,
		);

		if ( 'cta' === $type ) {
			$settings['border_radius'] = $this->dimensions( 0, 0, 0, 0 );
		}

		return $this->container( $settings, $elements );
	}

	private function cards_elementor( $items, $numbered = false ) {
		$cards = array();
		foreach ( is_array( $items ) ? $items : array() as $index => $item ) {
			$item = is_array( $item ) ? $item : array( 'title' => $item );
			$card_elements = array();
			if ( $numbered ) {
				$card_elements[] = $this->heading_widget( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ), 'div', array(
					'title_color' => $this->settings['accent_color'],
				) );
			}
			if ( ! empty( $item['title'] ) ) {
				$card_elements[] = $this->heading_widget( $item['title'], 'h3' );
			}
			if ( ! empty( $item['content'] ) ) {
				$card_elements[] = $this->text_widget( $this->safe_html( $item['content'] ) );
			}
			$cards[] = $this->container(
				array(
					'width'                 => array( 'unit' => '%', 'size' => 31.5, 'sizes' => array() ),
					'width_mobile'          => array( 'unit' => '%', 'size' => 100, 'sizes' => array() ),
					'padding'               => $this->dimensions( 30, 28, 30, 28 ),
					'background_background' => 'classic',
					'background_color'      => '#FFFFFF',
					'border_border'         => 'solid',
					'border_width'          => $this->dimensions( 1, 1, 1, 1 ),
					'border_color'          => '#D9E2EC',
					'border_radius'         => $this->dimensions( 12, 12, 12, 12 ),
				),
				$card_elements
			);
		}

		return $this->container(
			array(
				'content_width'  => 'full',
				'flex_direction' => 'row',
				'flex_wrap'      => 'wrap',
				'flex_gap'       => array( 'unit' => 'px', 'size' => 22, 'sizes' => array() ),
			),
			$cards
		);
	}

	private function stats_elementor( $items ) {
		$cards = array();
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$cards[] = $this->container(
				array(
					'width'                 => array( 'unit' => '%', 'size' => 23, 'sizes' => array() ),
					'width_mobile'          => array( 'unit' => '%', 'size' => 100, 'sizes' => array() ),
					'padding'               => $this->dimensions( 28, 24, 28, 24 ),
					'background_background' => 'classic',
					'background_color'      => '#FFFFFF',
					'border_border'         => 'solid',
					'border_width'          => $this->dimensions( 1, 1, 1, 1 ),
					'border_color'          => '#D9E2EC',
					'border_radius'         => $this->dimensions( 12, 12, 12, 12 ),
				),
				array(
					$this->heading_widget( $item['value'] ?? $item['title'] ?? '', 'div', array( 'title_color' => $this->settings['accent_color'] ) ),
					$this->heading_widget( $item['label'] ?? $item['content'] ?? '', 'h3' ),
				)
			);
		}
		return $this->container(
			array(
				'content_width'  => 'full',
				'flex_direction' => 'row',
				'flex_wrap'      => 'wrap',
				'flex_gap'       => array( 'unit' => 'px', 'size' => 20, 'sizes' => array() ),
			),
			$cards
		);
	}

	private function split_content_elementor( array $section ) {
		$left = array();
		if ( ! empty( $section['content'] ) ) {
			$left[] = $this->text_widget( $this->safe_html( $section['content'] ) );
		}
		if ( ! empty( $section['items'] ) ) {
			$left[] = $this->text_widget( $this->list_html( $section['items'], ! empty( $section['checklist'] ) ) );
		}

		$right = array();
		$image_id = absint( $section['image_id'] ?? 0 );
		if ( $image_id && wp_attachment_is_image( $image_id ) ) {
			$right[] = $this->image_widget( $image_id, $section['image_alt'] ?? $section['heading'] ?? '' );
		} elseif ( ! empty( $section['aside'] ) ) {
			$right[] = $this->text_widget( $this->safe_html( $section['aside'] ) );
		}

		return $this->container(
			array(
				'content_width'  => 'full',
				'flex_direction' => 'row',
				'flex_wrap'      => 'wrap',
				'align_items'    => 'center',
				'flex_gap'       => array( 'unit' => 'px', 'size' => 44, 'sizes' => array() ),
			),
			array(
				$this->container(
					array(
						'width'        => array( 'unit' => '%', 'size' => 56, 'sizes' => array() ),
						'width_mobile' => array( 'unit' => '%', 'size' => 100, 'sizes' => array() ),
					),
					$left
				),
				$this->container(
					array(
						'width'        => array( 'unit' => '%', 'size' => 39, 'sizes' => array() ),
						'width_mobile' => array( 'unit' => '%', 'size' => 100, 'sizes' => array() ),
					),
					$right
				),
			)
		);
	}

	private function render_hero_html( array $hero ) {
		$html  = '<section class="ikon-seo-hero">';
		$html .= ! empty( $hero['eyebrow'] ) ? '<p class="ikon-seo-eyebrow">' . esc_html( $hero['eyebrow'] ) . '</p>' : '';
		$html .= '<h1>' . esc_html( $hero['title'] ?? '' ) . '</h1>';
		$html .= ! empty( $hero['description'] ) ? '<div>' . $this->safe_html( $hero['description'] ) . '</div>' : '';
		if ( ! empty( $hero['primary_cta']['label'] ) && ! empty( $hero['primary_cta']['url'] ) ) {
			$html .= '<p><a href="' . esc_url( $hero['primary_cta']['url'] ) . '">' . esc_html( $hero['primary_cta']['label'] ) . '</a></p>';
		}
		if ( ! empty( $hero['image_id'] ) && wp_attachment_is_image( absint( $hero['image_id'] ) ) ) {
			$html .= wp_get_attachment_image( absint( $hero['image_id'] ), 'large', false, array( 'alt' => sanitize_text_field( $hero['image_alt'] ?? $hero['title'] ?? '' ) ) );
		}
		return $html . '</section>';
	}

	private function render_section_html( array $section ) {
		$type = sanitize_key( $section['type'] ?? 'content' );
		$html = '<section class="ikon-seo-section ikon-seo-' . esc_attr( $type ) . '">';
		$html .= ! empty( $section['heading'] ) ? '<h2>' . esc_html( $section['heading'] ) . '</h2>' : '';
		$html .= ! empty( $section['intro'] ) ? '<div>' . $this->safe_html( $section['intro'] ) . '</div>' : '';

		if ( in_array( $type, array( 'cards', 'features', 'process', 'location-details', 'local-proof', 'reviews' ), true ) ) {
			$html .= ! empty( $section['content'] ) ? '<div>' . $this->safe_html( $section['content'] ) . '</div>' : '';
			foreach ( (array) ( $section['items'] ?? array() ) as $item ) {
				$item  = is_array( $item ) ? $item : array( 'title' => $item );
				$html .= '<article>';
				$html .= ! empty( $item['title'] ) ? '<h3>' . esc_html( $item['title'] ) . '</h3>' : '';
				$html .= ! empty( $item['content'] ) ? '<div>' . $this->safe_html( $item['content'] ) . '</div>' : '';
				$html .= '</article>';
			}
		} elseif ( 'map' === $type ) {
			$html .= $this->map_html( $section );
		} elseif ( 'table' === $type ) {
			$html .= $this->table_html( $section );
		} elseif ( 'faq' === $type ) {
			$html .= $this->faq_html( $section['items'] ?? array() );
		} elseif ( in_array( $type, array( 'links', 'related-links', 'sources' ), true ) ) {
			$html .= $this->links_html( $section['items'] ?? array() );
		} elseif ( 'stats' === $type ) {
			foreach ( (array) ( $section['items'] ?? array() ) as $item ) {
				if ( is_array( $item ) ) {
					$html .= '<p><strong>' . esc_html( $item['value'] ?? $item['title'] ?? '' ) . '</strong> ' . esc_html( $item['label'] ?? $item['content'] ?? '' ) . '</p>';
				}
			}
		} else {
			$html .= ! empty( $section['content'] ) ? '<div>' . $this->safe_html( $section['content'] ) . '</div>' : '';
			$html .= ! empty( $section['items'] ) ? $this->list_html( $section['items'], 'checklist' === $type ) : '';
		}

		if ( ! empty( $section['button']['label'] ) && ! empty( $section['button']['url'] ) ) {
			$html .= '<p><a href="' . esc_url( $section['button']['url'] ) . '">' . esc_html( $section['button']['label'] ) . '</a></p>';
		}
		if ( ! empty( $section['image_id'] ) && wp_attachment_is_image( absint( $section['image_id'] ) ) ) {
			$html .= wp_get_attachment_image( absint( $section['image_id'] ), 'large', false, array( 'alt' => sanitize_text_field( $section['image_alt'] ?? $section['heading'] ?? '' ) ) );
		}

		return $html . '</section>';
	}

	private function map_elementor( array $section ) {
		$query = sanitize_text_field( $section['map_query'] ?? '' );
		$zoom  = max( 5, min( 20, absint( $section['map_zoom'] ?? 14 ) ) );
		if ( $query ) {
			return $this->widget(
				'google_maps',
				array(
					'address' => $query,
					'zoom'    => $zoom,
					'height'  => array( 'unit' => 'px', 'size' => 420, 'sizes' => array() ),
				)
			);
		}
		$embed = $this->safe_map_embed_url( $section['map_embed_url'] ?? '' );
		if ( $embed ) {
			return $this->widget( 'html', array( 'html' => $this->map_iframe( $embed ) ) );
		}
		$url = esc_url_raw( $section['map_url'] ?? '' );
		return $url ? $this->button_widget( $section['map_label'] ?? 'View map and directions', $url, 'info' ) : null;
	}

	private function map_html( array $section ) {
		$embed = $this->safe_map_embed_url( $section['map_embed_url'] ?? '' );
		$html  = $embed ? $this->map_iframe( $embed ) : '';
		$url   = esc_url_raw( $section['map_url'] ?? '' );
		if ( ! $url && ! empty( $section['map_query'] ) ) {
			$url = add_query_arg( array( 'api' => 1, 'query' => sanitize_text_field( $section['map_query'] ) ), 'https://www.google.com/maps/search/' );
		}
		if ( $url ) {
			$html .= '<p><a href="' . esc_url( $url ) . '" rel="noopener">' . esc_html( $section['map_label'] ?? 'View map and directions' ) . '</a></p>';
		}
		return $html;
	}

	private function safe_map_embed_url( $value ) {
		$url   = esc_url_raw( $value );
		$host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$path  = (string) wp_parse_url( $url, PHP_URL_PATH );
		$valid = in_array( $host, array( 'www.google.com', 'maps.google.com' ), true ) && 0 === strpos( $path, '/maps/embed' );
		return $valid && 'https' === strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ? $url : '';
	}

	private function map_iframe( $url ) {
		return '<iframe title="Location map" src="' . esc_url( $url ) . '" width="100%" height="420" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>';
	}

	private function table_html( array $section ) {
		$headers = is_array( $section['headers'] ?? null ) ? $section['headers'] : array();
		$rows    = is_array( $section['rows'] ?? null ) ? $section['rows'] : array();
		if ( ! $headers || ! $rows ) {
			return '';
		}

		$html = '<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse">';
		$html .= '<thead><tr>';
		foreach ( $headers as $header ) {
			$html .= '<th style="padding:14px;border:1px solid #d9e2ec;text-align:left;background:#123b5d;color:#fff">' . esc_html( $header ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$html .= '<tr>';
			foreach ( (array) $row as $cell ) {
				$html .= '<td style="padding:14px;border:1px solid #d9e2ec;vertical-align:top">' . $this->safe_html( $cell ) . '</td>';
			}
			$html .= '</tr>';
		}
		return $html . '</tbody></table></div>';
	}

	private function faq_html( $items ) {
		$html = '<div class="ikon-seo-faq">';
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			if ( ! is_array( $item ) || empty( $item['question'] ) || empty( $item['answer'] ) ) {
				continue;
			}
			$html .= '<details style="padding:18px 0;border-bottom:1px solid #d9e2ec">';
			$html .= '<summary style="font-weight:700;cursor:pointer">' . esc_html( $item['question'] ) . '</summary>';
			$html .= '<div style="padding-top:12px">' . $this->safe_html( $item['answer'] ) . '</div></details>';
		}
		return $html . '</div>';
	}

	private function list_html( $items, $checks = false ) {
		$html = '<ul class="' . ( $checks ? 'ikon-seo-checklist' : 'ikon-seo-list' ) . '">';
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			if ( is_array( $item ) ) {
				$title   = $item['title'] ?? '';
				$content = $item['content'] ?? '';
				$html   .= '<li><strong>' . esc_html( $title ) . '</strong>';
				$html   .= $content ? ' ' . $this->safe_html( $content ) : '';
				$html   .= '</li>';
			} else {
				$html .= '<li>' . $this->safe_html( $item ) . '</li>';
			}
		}
		return $html . '</ul>';
	}

	private function links_html( $items ) {
		$html = '<ul class="ikon-seo-related-links">';
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			if ( ! is_array( $item ) || empty( $item['label'] ) || empty( $item['url'] ) ) {
				continue;
			}
			$html .= '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
		}
		return $html . '</ul>';
	}

	private function container( array $settings, array $elements ) {
		return array(
			'id'       => $this->id(),
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => $settings,
			'elements' => array_values( array_filter( $elements ) ),
		);
	}

	private function heading_widget( $title, $size = 'h2', array $extra = array() ) {
		return $this->widget(
			'heading',
			array_merge(
				array(
					'title'                  => sanitize_text_field( $title ),
					'header_size'            => $size,
					'title_color'            => $this->settings['heading_color'],
					'typography_typography'  => 'custom',
					'typography_font_weight' => '700',
				),
				$extra
			)
		);
	}

	private function text_widget( $html, $color = '' ) {
		return $this->widget(
			'text-editor',
			array(
				'editor'     => $this->safe_html( $html ),
				'text_color' => $color ? sanitize_hex_color( $color ) : $this->settings['text_color'],
			)
		);
	}

	private function button_widget( $label, $url, $type = 'success' ) {
		return $this->widget(
			'button',
			array(
				'text'        => sanitize_text_field( $label ),
				'link'        => array(
					'url'         => esc_url_raw( $url ),
					'is_external' => '',
					'nofollow'    => '',
				),
				'button_type' => $type,
				'size'        => 'md',
			)
		);
	}

	private function image_widget( $attachment_id, $alt = '' ) {
		return $this->widget(
			'image',
			array(
				'image'      => array(
					'id'  => absint( $attachment_id ),
					'url' => esc_url_raw( wp_get_attachment_image_url( $attachment_id, 'full' ) ),
				),
				'image_size' => 'large',
				'caption_source' => 'none',
				'alt'        => sanitize_text_field( $alt ),
			)
		);
	}

	private function widget( $type, array $settings ) {
		return array(
			'id'         => $this->id(),
			'elType'     => 'widget',
			'widgetType' => $type,
			'isInner'    => false,
			'settings'   => $settings,
			'elements'   => array(),
		);
	}

	private function safe_html( $html ) {
		return wp_kses_post( (string) $html );
	}

	private function size( $size ) {
		return array( 'unit' => 'px', 'size' => $size, 'sizes' => array() );
	}

	private function dimensions( $top, $right, $bottom, $left ) {
		return array(
			'unit'     => 'px',
			'top'      => (string) $top,
			'right'    => (string) $right,
			'bottom'   => (string) $bottom,
			'left'     => (string) $left,
			'isLinked' => false,
		);
	}

	private function id() {
		return substr( md5( uniqid( 'ikon', true ) ), 0, 7 );
	}
}
