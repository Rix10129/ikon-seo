<?php

defined( 'ABSPATH' ) || exit;

/**
 * Builds an allow-listed Schema.org graph and merges it into Rank Math.
 *
 * Ikon SEO intentionally does not accept arbitrary JSON-LD. Every emitted node
 * is assembled from a known payload shape so remote content cannot inject
 * scripts or unsupported schema claims.
 */
class Ikon_SEO_Schema {
	private $profile;
	private $local;

	public function __construct( Ikon_SEO_Profile $profile, Ikon_SEO_Local $local ) {
		$this->profile = $profile;
		$this->local   = $local;
	}

	public function build( array $payload, $post_id ) {
		$settings = Ikon_SEO_Plugin::settings();
		$config   = isset( $payload['schema'] ) && is_array( $payload['schema'] ) ? $payload['schema'] : array();
		$url      = $post_id ? get_permalink( $post_id ) : home_url( '/' . sanitize_title( $payload['slug'] ?? $payload['title'] ?? 'preview' ) . '/' );
		$title    = wp_strip_all_tags( $payload['seo']['title'] ?? $payload['title'] ?? get_the_title( $post_id ) );
		$desc     = wp_strip_all_tags( $payload['seo']['description'] ?? $payload['excerpt'] ?? '' );
		$language = sanitize_text_field( $payload['language'] ?? $settings['default_language'] );
		$page_type= sanitize_key( $config['page_type'] ?? 'webpage' );
		$graph    = array();

		$webpage_types = array(
			'about'      => 'AboutPage',
			'contact'    => 'ContactPage',
			'collection' => 'CollectionPage',
			'profile'    => 'ProfilePage',
		);
		$webpage_type = $webpage_types[ $page_type ] ?? 'WebPage';

		$webpage = array(
			'@type'       => $webpage_type,
			'@id'         => $url . '#webpage',
			'url'         => $url,
			'name'        => $title,
			'description' => $desc,
			'inLanguage'  => $language,
		);

		if ( has_post_thumbnail( $post_id ) ) {
			$image_url = wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'full' );
			if ( $image_url ) {
				$webpage['primaryImageOfPage'] = array(
					'@type' => 'ImageObject',
					'url'   => esc_url_raw( $image_url ),
				);
			}
		}

		$graph[] = $webpage;
		$local_node = $this->local->local_schema( $payload, $url, $desc );
		if ( $local_node ) {
			$graph[] = $local_node;
			$graph[0]['mainEntity'] = array( '@id' => $local_node['@id'] );
		}

		if ( ! empty( $config['breadcrumbs'] ) && is_array( $config['breadcrumbs'] ) ) {
			$items = array();
			foreach ( $config['breadcrumbs'] as $position => $crumb ) {
				if ( ! is_array( $crumb ) || empty( $crumb['name'] ) || empty( $crumb['url'] ) ) {
					continue;
				}
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => count( $items ) + 1,
					'name'     => sanitize_text_field( $crumb['name'] ),
					'item'     => esc_url_raw( $crumb['url'] ),
				);
			}
			if ( $items ) {
				$graph[] = array(
					'@type'           => 'BreadcrumbList',
					'@id'             => $url . '#breadcrumb',
					'itemListElement' => $items,
				);
			}
		}

		$service = isset( $config['service'] ) && is_array( $config['service'] ) ? $config['service'] : array();
		if ( ! empty( $service['name'] ) ) {
			$service_node = array(
				'@type'       => 'Service',
				'@id'         => $url . '#service',
				'name'        => sanitize_text_field( $service['name'] ),
				'description' => wp_strip_all_tags( $service['description'] ?? $desc ),
				'url'         => $url,
				'provider'    => $local_node ? array( '@id' => $local_node['@id'] ) : $this->provider_reference( $settings ),
			);

			if ( ! empty( $service['service_type'] ) ) {
				$service_node['serviceType'] = sanitize_text_field( $service['service_type'] );
			}

			$areas = $this->places( $service['area_served'] ?? array() );
			if ( $areas ) {
				$service_node['areaServed'] = $areas;
			}

			if ( ! empty( $service['offers'] ) && is_array( $service['offers'] ) ) {
				$offers = $this->offers( $service['offers'] );
				if ( $offers ) {
					$service_node['hasOfferCatalog'] = array(
						'@type'           => 'OfferCatalog',
						'name'            => sanitize_text_field( $service['catalog_name'] ?? $service['name'] ),
						'itemListElement' => $offers,
					);
				}
			}
			$graph[] = $service_node;
		}

		if ( 'article' === $page_type || ! empty( $config['article'] ) ) {
			$article = isset( $config['article'] ) && is_array( $config['article'] ) ? $config['article'] : array();
			$node    = array(
				'@type'         => sanitize_key( $article['type'] ?? '' ) === 'article' ? 'Article' : 'BlogPosting',
				'@id'           => $url . '#article',
				'headline'      => sanitize_text_field( $article['headline'] ?? ( $payload['title'] ?? $title ) ),
				'description'   => wp_strip_all_tags( $article['description'] ?? $desc ),
				'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
				'datePublished' => $this->iso_date( $article['date_published'] ?? get_the_date( DATE_W3C, $post_id ) ),
				'dateModified'  => $this->iso_date( $article['date_modified'] ?? get_the_modified_date( DATE_W3C, $post_id ) ),
				'publisher'     => $this->provider_reference( $settings ),
			);
			if ( ! empty( $article['author']['name'] ) ) {
				$node['author'] = $this->person_reference( $article['author'], $url . '#author' );
			}
			$graph[] = $node;
		}

		if ( 'profile' === $page_type || ! empty( $config['person'] ) ) {
			$person = isset( $config['person'] ) && is_array( $config['person'] ) ? $config['person'] : array();
			if ( ! empty( $person['name'] ) ) {
				$person_node = $this->person_reference( $person, $url . '#person' );
				$person_node['@type'] = 'Person';
				$graph[] = $person_node;
				$graph[0]['mainEntity'] = array( '@id' => $url . '#person' );
			}
		}

		if ( 'collection' === $page_type || ! empty( $config['collection'] ) ) {
			$collection = isset( $config['collection'] ) && is_array( $config['collection'] ) ? $config['collection'] : array();
			$items      = array();
			foreach ( (array) ( $collection['items'] ?? array() ) as $item ) {
				if ( ! is_array( $item ) || empty( $item['name'] ) || empty( $item['url'] ) ) {
					continue;
				}
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => count( $items ) + 1,
					'name'     => sanitize_text_field( $item['name'] ),
					'url'      => esc_url_raw( $item['url'] ),
				);
			}
			if ( $items ) {
				$graph[] = array(
					'@type'           => 'ItemList',
					'@id'             => $url . '#itemlist',
					'name'            => sanitize_text_field( $collection['name'] ?? $title ),
					'itemListElement' => $items,
				);
			}
		}

		if ( 'tool' === $page_type || ! empty( $config['application'] ) ) {
			$app = isset( $config['application'] ) && is_array( $config['application'] ) ? $config['application'] : array();
			if ( ! empty( $app['name'] ) ) {
				$app_node = array(
					'@type'               => 'WebApplication',
					'@id'                 => $url . '#application',
					'name'                => sanitize_text_field( $app['name'] ),
					'description'         => wp_strip_all_tags( $app['description'] ?? $desc ),
					'url'                 => $url,
					'applicationCategory' => sanitize_text_field( $app['application_category'] ?? 'WebApplication' ),
					'operatingSystem'     => sanitize_text_field( $app['operating_system'] ?? 'Any' ),
					'isAccessibleForFree' => ! empty( $app['is_free'] ),
				);
				if ( isset( $app['price'] ) && '' !== (string) $app['price'] ) {
					$app_node['offers'] = array(
						'@type'         => 'Offer',
						'price'         => sanitize_text_field( $app['price'] ),
						'priceCurrency' => sanitize_text_field( $app['currency'] ?? $settings['default_currency'] ),
					);
				}
				$graph[] = $app_node;
			}
		}

		if ( 'howto' === $page_type && ! empty( $config['howto']['steps'] ) && is_array( $config['howto']['steps'] ) ) {
			$steps = array();
			foreach ( $config['howto']['steps'] as $step ) {
				if ( ! is_array( $step ) || empty( $step['name'] ) || empty( $step['text'] ) ) {
					continue;
				}
				$steps[] = array(
					'@type' => 'HowToStep',
					'name'  => sanitize_text_field( $step['name'] ),
					'text'  => wp_strip_all_tags( $step['text'] ),
				);
			}
			if ( $steps ) {
				$graph[] = array(
					'@type'       => 'HowTo',
					'@id'         => $url . '#howto',
					'name'        => sanitize_text_field( $config['howto']['name'] ?? $title ),
					'description' => wp_strip_all_tags( $config['howto']['description'] ?? $desc ),
					'step'        => $steps,
				);
			}
		}

		if ( ! empty( $config['video']['name'] ) && ! empty( $config['video']['thumbnail_url'] ) && ! empty( $config['video']['upload_date'] ) ) {
			$video = $config['video'];
			$node  = array(
				'@type'        => 'VideoObject',
				'@id'          => $url . '#video',
				'name'         => sanitize_text_field( $video['name'] ),
				'description'  => wp_strip_all_tags( $video['description'] ?? $desc ),
				'thumbnailUrl' => array( esc_url_raw( $video['thumbnail_url'] ) ),
				'uploadDate'   => $this->iso_date( $video['upload_date'] ),
			);
			if ( ! empty( $video['content_url'] ) ) {
				$node['contentUrl'] = esc_url_raw( $video['content_url'] );
			}
			if ( ! empty( $video['embed_url'] ) ) {
				$node['embedUrl'] = esc_url_raw( $video['embed_url'] );
			}
			$graph[] = $node;
		}

		$entity_config = isset( $config['business_entity'] ) && is_array( $config['business_entity'] )
			? $config['business_entity']
			: ( 'AccountingService' === $settings['business_entity_type'] && ! empty( $config['accounting_service'] ) ? $config['accounting_service'] : array() );
		if ( ! $local_node && ! empty( $settings['profile_configured'] ) && ! empty( $settings['allow_entity_schema'] ) && $entity_config ) {
			$requires_address = $this->profile->entity_requires_address( $settings['business_entity_type'] );
			if ( ! $requires_address || $this->profile->has_verified_address( $settings ) ) {
				$graph[] = $this->business_entity( $settings, $entity_config, $desc );
			}
		}

		$faq_enabled = ! empty( $settings['semantic_faq'] ) || ! empty( $config['semantic_faq'] );
		if ( $faq_enabled ) {
			$faq_entities = $this->faq_entities( $payload['faq'] ?? array() );
			if ( $faq_entities ) {
				$graph[] = array(
					'@type'      => 'FAQPage',
					'@id'        => $url . '#faq',
					'mainEntity' => $faq_entities,
				);
			}
		}

		return array_values( array_filter( array_map( array( $this, 'strip_empty' ), $graph ) ) );
	}

	public function merge_rank_math_graph( $data, $jsonld = null ) {
		if ( ! is_singular() ) {
			return $data;
		}

		$nodes = get_post_meta( get_queried_object_id(), '_ikon_seo_schema_graph', true );
		if ( ! is_array( $nodes ) || ! $nodes ) {
			return $data;
		}

		$data          = is_array( $data ) ? $data : array();
		$entity_type   = Ikon_SEO_Plugin::settings()['business_entity_type'];
		$singleton_types = array_merge(
			array( 'WebPage', 'AboutPage', 'ContactPage', 'CollectionPage', 'ProfilePage', 'BreadcrumbList', 'FAQPage' ),
			array_keys( $this->profile->entity_types() )
		);
		$existing_ids  = array();
		$existing_types= array();
		$this->collect_existing( $data, $existing_ids, $existing_types );

		foreach ( $nodes as $index => $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			$id    = (string) ( $node['@id'] ?? '' );
			$types = (array) ( $node['@type'] ?? array() );
			$duplicate_type = false;
			$is_location_branch = false !== strpos( $id, '#localbusiness' );

			if ( ! $is_location_branch ) {
				foreach ( $types as $type ) {
					if ( in_array( $type, $singleton_types, true )
						&& in_array( $type, $existing_types, true ) ) {
						$duplicate_type = true;
						break;
					}
				}
			}

			if ( $id && in_array( $id, $existing_ids, true ) ) {
				if ( in_array( $entity_type, $types, true ) || array_intersect( $types, array_keys( $this->profile->entity_types() ) ) ) {
					$this->merge_existing_node( $data, $id, $node );
				}
				continue;
			}

			if ( $duplicate_type ) {
				continue;
			}

			$key = 'ikon-seo-' . sanitize_key( implode( '-', $types ) ) . '-' . $index;
			$data[ $key ] = $node;
		}

		return $data;
	}

	public function print_fallback_graph() {
		if ( defined( 'RANK_MATH_VERSION' ) || ! is_singular() ) {
			return;
		}

		$nodes = get_post_meta( get_queried_object_id(), '_ikon_seo_schema_graph', true );
		if ( ! is_array( $nodes ) || ! $nodes ) {
			return;
		}
		if ( defined( 'WPSEO_VERSION' ) ) {
			$yoast_types = array( 'WebPage', 'AboutPage', 'ContactPage', 'CollectionPage', 'ProfilePage', 'BreadcrumbList', 'Article', 'BlogPosting' );
			$nodes = array_values(
				array_filter(
					$nodes,
					function( $node ) use ( $yoast_types ) {
						return ! array_intersect( (array) ( $node['@type'] ?? array() ), $yoast_types );
					}
				)
			);
			if ( ! $nodes ) {
				return;
			}
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@graph'   => $nodes,
		);

		echo "\n<script type=\"application/ld+json\">";
		echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
		echo "</script>\n";
	}

	public function types_for_post( $post_id ) {
		$nodes = get_post_meta( $post_id, '_ikon_seo_schema_graph', true );
		$types = array();
		foreach ( is_array( $nodes ) ? $nodes : array() as $node ) {
			foreach ( (array) ( $node['@type'] ?? array() ) as $type ) {
				$types[] = sanitize_text_field( $type );
			}
		}
		return array_values( array_unique( $types ) );
	}

	private function business_entity( array $settings, $config, $desc ) {
		$config = is_array( $config ) ? $config : array();
		$node   = array(
			'@type'       => sanitize_text_field( $settings['business_entity_type'] ),
			'@id'         => home_url( '/#organization' ),
			'name'        => sanitize_text_field( $config['name'] ?? $settings['site_name'] ),
			'description' => wp_strip_all_tags( $config['description'] ?? $desc ),
			'url'         => esc_url_raw( $settings['business_url'] ?: home_url( '/' ) ),
			'telephone'   => sanitize_text_field( $settings['business_phone'] ),
			'priceRange'  => sanitize_text_field( $settings['price_range'] ),
		);
		if ( ! empty( $settings['address_street'] ) || ! empty( $settings['address_locality'] ) || ! empty( $settings['address_country'] ) ) {
			$node['address'] = array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => sanitize_text_field( $settings['address_street'] ),
				'addressLocality' => sanitize_text_field( $settings['address_locality'] ),
				'addressRegion'   => sanitize_text_field( $settings['address_region'] ),
				'postalCode'      => sanitize_text_field( $settings['address_postal'] ),
				'addressCountry'  => sanitize_text_field( $settings['address_country'] ),
			);
		}
		if ( $settings['business_logo'] ) {
			$node['logo'] = esc_url_raw( $settings['business_logo'] );
		}
		if ( is_numeric( $settings['latitude'] ) && is_numeric( $settings['longitude'] ) ) {
			$node['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $settings['latitude'],
				'longitude' => (float) $settings['longitude'],
			);
		}
		if ( ! empty( $settings['opening_hours'] ) ) {
			$node['openingHours'] = array_map( 'trim', explode( "\n", $settings['opening_hours'] ) );
		}
		$areas = $this->places( $config['area_served'] ?? array() );
		if ( $areas ) {
			$node['areaServed'] = $areas;
		}
		return $node;
	}

	public function preview( array $payload ) {
		$graph   = $this->build( $payload, 0 );
		$profile = $this->profile->get();
		$types   = array();
		foreach ( $graph as $node ) {
			foreach ( (array) ( $node['@type'] ?? array() ) as $type ) {
				$types[] = sanitize_text_field( $type );
			}
		}

		$requested_entity = ! empty( $payload['schema']['business_entity'] ) || ! empty( $payload['schema']['accounting_service'] );
		$entity_generated = in_array( $profile['business_entity_type'], $types, true );
		$warnings         = array();
		if ( $requested_entity && ! $entity_generated ) {
			$warnings[] = 'The business entity node was not generated. Check profile verification, entity-schema permission and address requirements.';
		}
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			$warnings[] = 'Rank Math is active; matching IDs and duplicate page/entity types will be merged or skipped on the live page.';
		}

		return array(
			'profile_id'          => $profile['profile_id'],
			'profile_entity_type' => $profile['business_entity_type'],
			'allowed_types'       => $profile['allowed_schema_types'],
			'generated_types'     => array_values( array_unique( $types ) ),
			'graph'               => $graph,
			'warnings'            => $warnings,
		);
	}

	private function provider_reference( array $settings ) {
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			return array( '@id' => home_url( '/#organization' ) );
		}

		$type = ! empty( $settings['allow_entity_schema'] ) ? sanitize_text_field( $settings['business_entity_type'] ) : 'Organization';
		if ( $this->profile->entity_requires_address( $type ) && ! $this->profile->has_verified_address( $settings ) ) {
			$type = 'Organization';
		}

		return array(
			'@type' => $type,
			'@id'   => home_url( '/#organization' ),
			'name'  => sanitize_text_field( $settings['site_name'] ),
			'url'   => esc_url_raw( $settings['business_url'] ?: home_url( '/' ) ),
		);
	}

	private function offers( array $offers ) {
		$output = array();
		foreach ( $offers as $offer ) {
			if ( ! is_array( $offer ) || empty( $offer['name'] ) ) {
				continue;
			}
			$item = array(
				'@type'       => 'Offer',
				'name'        => sanitize_text_field( $offer['name'] ),
				'description' => wp_strip_all_tags( $offer['description'] ?? '' ),
				'itemOffered' => array(
					'@type' => 'Service',
					'name'  => sanitize_text_field( $offer['name'] ),
				),
			);
			if ( ! empty( $offer['url'] ) ) {
				$item['url'] = esc_url_raw( $offer['url'] );
			}
			$output[] = $item;
		}
		return $output;
	}

	private function person_reference( array $person, $id ) {
		$node = array(
			'@type'       => 'Person',
			'@id'         => esc_url_raw( $person['id'] ?? $id ),
			'name'        => sanitize_text_field( $person['name'] ),
			'description' => wp_strip_all_tags( $person['description'] ?? '' ),
			'url'         => esc_url_raw( $person['url'] ?? '' ),
			'jobTitle'    => sanitize_text_field( $person['job_title'] ?? '' ),
		);
		if ( ! empty( $person['image'] ) ) {
			$node['image'] = esc_url_raw( $person['image'] );
		}
		if ( ! empty( $person['credentials'] ) && is_array( $person['credentials'] ) ) {
			$node['hasCredential'] = array_map(
				function( $credential ) {
					return array(
						'@type'              => 'EducationalOccupationalCredential',
						'credentialCategory' => sanitize_text_field( $credential ),
					);
				},
				$person['credentials']
			);
		}
		if ( ! empty( $person['knows_about'] ) && is_array( $person['knows_about'] ) ) {
			$node['knowsAbout'] = array_map( 'sanitize_text_field', $person['knows_about'] );
		}
		return $this->strip_empty( $node );
	}

	private function faq_entities( $faq ) {
		$entities = array();
		foreach ( is_array( $faq ) ? $faq : array() as $item ) {
			if ( ! is_array( $item ) || empty( $item['question'] ) || empty( $item['answer'] ) ) {
				continue;
			}
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $item['question'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $item['answer'] ),
				),
			);
		}
		return $entities;
	}

	private function places( $areas ) {
		$output = array();
		foreach ( is_array( $areas ) ? $areas : array( $areas ) as $area ) {
			if ( ! is_scalar( $area ) || '' === trim( (string) $area ) ) {
				continue;
			}
			$output[] = array(
				'@type' => 'Place',
				'name'  => sanitize_text_field( $area ),
			);
		}
		return $output;
	}

	private function iso_date( $date ) {
		$timestamp = strtotime( (string) $date );
		return $timestamp ? gmdate( DATE_W3C, $timestamp ) : '';
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

	private function collect_existing( array $data, array &$ids, array &$types ) {
		foreach ( $data as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			if ( isset( $node['@graph'] ) && is_array( $node['@graph'] ) ) {
				$this->collect_existing( $node['@graph'], $ids, $types );
			}
			if ( ! empty( $node['@id'] ) ) {
				$ids[] = (string) $node['@id'];
			}
			foreach ( (array) ( $node['@type'] ?? array() ) as $type ) {
				$types[] = (string) $type;
			}
		}
	}

	private function merge_existing_node( array &$data, $id, array $incoming ) {
		foreach ( $data as &$node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			if ( isset( $node['@graph'] ) && is_array( $node['@graph'] ) ) {
				$this->merge_existing_node( $node['@graph'], $id, $incoming );
			}
			if ( (string) ( $node['@id'] ?? '' ) !== (string) $id ) {
				continue;
			}
			$types = array_values( array_unique( array_merge( (array) ( $node['@type'] ?? array() ), (array) ( $incoming['@type'] ?? array() ) ) ) );
			$node  = array_merge( $node, $incoming );
			$node['@type'] = 1 === count( $types ) ? $types[0] : $types;
			return;
		}
		unset( $node );
	}
}
