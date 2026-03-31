<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class MVC_DE_Image_Query {

	private static $seen_ids = array();

	public static function get_images( $limit = 5, $args = array() ) {
		$limit   = max( 1, absint( $limit ) );
		$context = mvc_de_get_current_page_context();

		$defaults = array(
			'exclude_seen' => false,
			'mark_seen'    => true,
			'mode'         => 'default',
		);

		$args = wp_parse_args( $args, $defaults );

		$images = self::get_cached_images_by_context( $context, $limit, $args );

		if ( ! empty( $images ) && ! empty( $args['mark_seen'] ) ) {
			self::mark_images_seen( $images );
		}

		return $images;
	}

	public static function get_hero_image( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'exclude_seen' => false,
			'mark_seen'    => true,
			'mode'         => 'hero',
		) );

		$images = self::get_images( 1, $args );
		return ! empty( $images ) ? $images[0] : null;
	}

	public static function reset_seen_images() {
		self::$seen_ids = array();
	}

	private static function get_cached_images_by_context( $context, $limit, $args ) {
		$cache_ttl  = (int) apply_filters( 'mvc_de_image_cache_ttl', DAY_IN_SECONDS, $context, $limit, $args );
		$cache_key  = self::build_cache_key( $context, $limit, $args );
		$cached_ids = get_transient( $cache_key );

		if ( is_array( $cached_ids ) && ! empty( $cached_ids ) ) {
			$posts = self::get_attachments_by_ids( $cached_ids );

			if ( ! empty( $args['exclude_seen'] ) ) {
				$posts = self::filter_seen_images( $posts );
			}

			if ( ! empty( $posts ) ) {
				return array_slice( $posts, 0, $limit );
			}
		}

		$images = self::query_images_by_priority( $context, $limit, $args );

		if ( ! empty( $images ) ) {
			$image_ids = wp_list_pluck( $images, 'ID' );
			$image_ids = array_map( 'absint', $image_ids );
			$image_ids = array_values( array_filter( array_unique( $image_ids ) ) );

			if ( ! empty( $image_ids ) ) {
				set_transient( $cache_key, $image_ids, $cache_ttl );
			}
		}

		return $images;
	}

	private static function build_cache_key( $context, $limit, $args ) {
		$normalized = array(
			'page_type'   => isset( $context['page_type'] ) ? (string) $context['page_type'] : '',
			'object_type' => isset( $context['object_type'] ) ? (string) $context['object_type'] : '',
			'industries'  => isset( $context['industries'] ) ? (array) $context['industries'] : array(),
			'services'    => isset( $context['services'] ) ? (array) $context['services'] : array(),
			'cities'      => isset( $context['cities'] ) ? (array) $context['cities'] : array(),
			'businesses'  => isset( $context['businesses'] ) ? (array) $context['businesses'] : array(),
			'limit'       => (int) $limit,
			'mode'        => isset( $args['mode'] ) ? (string) $args['mode'] : 'default',
		//	'rotation'    => gmdate( 'Y-m-d' ),
'rotation' => gmdate( 'Y-m-d-H-i' ),
		);

		return 'mvc_de_img_' . md5( wp_json_encode( $normalized ) );
	}

	private static function query_images_by_priority( $context, $limit, $args ) {
		$mode        = isset( $args['mode'] ) ? (string) $args['mode'] : 'default';
		$object_type = isset( $context['object_type'] ) ? (string) $context['object_type'] : '';

		switch ( $object_type ) {
			case MVC_Directory_Engine::PAGE_ROLE_INDUSTRY_OVERVIEW:
				$priorities = array(
					array( 'industries' ),
					array(),
				);
				break;

			case MVC_Directory_Engine::PAGE_ROLE_SERVICE_OVERVIEW:
				$priorities = array(
					array( 'services' ),
					array( 'industries' ),
					array(),
				);
				break;

			case MVC_Directory_Engine::PAGE_ROLE_CITY_OVERVIEW:
				$priorities = array(
					array( 'cities' ),
					array(),
				);
				break;

			case MVC_Directory_Engine::PAGE_ROLE_BUSINESS_OVERVIEW:
				$priorities = array(
					array( 'businesses' ),
					array( 'industries' ),
					array(),
				);
				break;

			case MVC_Directory_Engine::PAGE_ROLE_HOME:
				$priorities = array(
					array(),
				);
				break;

			default:
				if ( 'hero' === $mode ) {
					$priorities = array(
						array( 'businesses', 'cities', 'services', 'industries' ),
						array( 'cities', 'services', 'industries' ),
						array( 'services', 'industries' ),
						array( 'cities', 'industries' ),
						array( 'industries' ),
						array()
					);
				} else {
					$priorities = array(
						array( 'businesses', 'cities', 'services', 'industries' ),
						array( 'cities', 'services', 'industries' ),
						array( 'services', 'industries' ),
						array( 'industries' ),
						array()
					);
				}
				break;
		}

		foreach ( $priorities as $priority ) {
			$images = self::run_query( $context, $priority, $limit, $args );

			if ( ! empty( $images ) ) {
				return $images;
			}
		}

		return array();
	}

	private static function run_query( $context, $limit_keys, $limit, $args ) {
		$tax_query = array();

		foreach ( $limit_keys as $key ) {
			if ( empty( $context[ $key ] ) ) {
				continue;
			}

			$taxonomy = self::map_taxonomy( $key );
			if ( ! $taxonomy ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $context[ $key ],
			);
		}

		$usage_taxonomy = MVC_Directory_Engine::TAX_IMAGE_USAGE;

if ( taxonomy_exists( $usage_taxonomy ) ) {

	if ( 'hero' === $args['mode'] ) {
		$tax_query[] = array(
			'taxonomy' => $usage_taxonomy,
			'field'    => 'slug',
			'terms'    => array(
				MVC_Directory_Engine::IMAGE_USAGE_HERO,
				MVC_Directory_Engine::IMAGE_USAGE_GALLERY,
			),
			'operator' => 'IN',
		);
	} else {
		$tax_query[] = array(
			'taxonomy' => $usage_taxonomy,
			'field'    => 'slug',
			'terms'    => array(
				MVC_Directory_Engine::IMAGE_USAGE_GALLERY,
			),
			'operator' => 'IN',
		);
	}

	$tax_query[] = array(
		'taxonomy' => $usage_taxonomy,
		'field'    => 'slug',
		'terms'    => array(
			MVC_Directory_Engine::IMAGE_USAGE_LOGO,
			MVC_Directory_Engine::IMAGE_USAGE_ICON,
			MVC_Directory_Engine::IMAGE_USAGE_EXCLUDE,
		),
		'operator' => 'NOT IN',
	);
}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		$query_limit = ! empty( $args['exclude_seen'] ) ? max( $limit * 3, 6 ) : $limit;

		$args_query = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'posts_per_page' => $query_limit,
			'orderby'        => 'rand',
			'tax_query'      => $tax_query,
		);

		$query  = new WP_Query( $args_query );
		$images = ! empty( $query->posts ) ? $query->posts : array();

		if ( ! empty( $args['exclude_seen'] ) ) {
			$images = self::filter_seen_images( $images );
		}

		return array_slice( $images, 0, $limit );
	}

	private static function get_attachments_by_ids( $ids ) {
		$ids = is_array( $ids ) ? array_map( 'absint', $ids ) : array();
		$ids = array_values( array_filter( array_unique( $ids ) ) );

		if ( empty( $ids ) ) {
			return array();
		}

		$posts = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'post__in'       => $ids,
			'orderby'        => 'post__in',
			'numberposts'    => count( $ids ),
		) );

		return is_array( $posts ) ? $posts : array();
	}

	private static function filter_seen_images( $images ) {
		if ( empty( self::$seen_ids ) || empty( $images ) ) {
			return $images;
		}

		$filtered = array();

		foreach ( $images as $image ) {
			$image_id = isset( $image->ID ) ? absint( $image->ID ) : 0;
			if ( $image_id && ! in_array( $image_id, self::$seen_ids, true ) ) {
				$filtered[] = $image;
			}
		}

		return $filtered;
	}

	private static function mark_images_seen( $images ) {
		if ( empty( $images ) ) {
			return;
		}

		foreach ( $images as $image ) {
			$image_id = isset( $image->ID ) ? absint( $image->ID ) : 0;
			if ( $image_id && ! in_array( $image_id, self::$seen_ids, true ) ) {
				self::$seen_ids[] = $image_id;
			}
		}
	}

	private static function map_taxonomy( $key ) {
		$map = array(
			'industries' => MVC_Directory_Engine::TAX_INDUSTRY,
			'services'   => MVC_Directory_Engine::TAX_SERVICE,
			'cities'     => MVC_Directory_Engine::TAX_CITY,
			'businesses' => 'business_cat',
		);

		return isset( $map[ $key ] ) ? $map[ $key ] : false;
	}
}