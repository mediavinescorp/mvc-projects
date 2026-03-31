<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MVC_DE_Page_Context {

	/**
	 * Returns the current page context in a normalized structure.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_current_context() {
		$context = array(
			'page_type'       => '',
			'object_type'     => '',
			'post_id'         => 0,
			'queried_term_id' => 0,
			'industries'      => array(),
			'services'        => array(),
			'cities'          => array(),
			'businesses'      => array(),
		);

		// Singular business post.
		if ( is_singular( MVC_Directory_Engine::CPT_BUSINESS ) ) {
			$post_id = get_queried_object_id();

			$context['page_type']   = 'singular';
			$context['object_type'] = 'business';
			$context['post_id']     = (int) $post_id;
			$context['industries']  = self::get_post_term_slugs( $post_id, MVC_Directory_Engine::TAX_INDUSTRY );
			$context['services']    = self::get_business_effective_service_slugs( $post_id );
			$context['cities']      = self::get_post_term_slugs( $post_id, MVC_Directory_Engine::TAX_CITY );
			$context['businesses']  = self::get_business_slugs_for_post( $post_id );

			return self::normalize_context( $context );
		}

		// Service taxonomy archive.
		if ( is_tax( MVC_Directory_Engine::TAX_SERVICE ) ) {
			$term = get_queried_object();

			if ( $term && ! is_wp_error( $term ) ) {
				$service_slug = sanitize_title( $term->slug );
				$mapping      = mvc_de_get_service_mapping( $service_slug );

				$context['page_type']       = 'taxonomy';
				$context['object_type']     = 'service';
				$context['queried_term_id'] = (int) $term->term_id;
				$context['services']        = array( $service_slug );

				if ( ! empty( $mapping['industry_slug'] ) ) {
					$context['industries'] = array( sanitize_title( $mapping['industry_slug'] ) );
				}

				return self::normalize_context( $context );
			}
		}

		// Industry taxonomy archive.
		if ( is_tax( MVC_Directory_Engine::TAX_INDUSTRY ) ) {
			$term = get_queried_object();

			if ( $term && ! is_wp_error( $term ) ) {
				$industry_slug = sanitize_title( $term->slug );

				$context['page_type']       = 'taxonomy';
				$context['object_type']     = 'industry';
				$context['queried_term_id'] = (int) $term->term_id;
				$context['industries']      = array( $industry_slug );

				return self::normalize_context( $context );
			}
		}

		// City taxonomy archive.
		if ( is_tax( MVC_Directory_Engine::TAX_CITY ) ) {
			$term = get_queried_object();

			if ( $term && ! is_wp_error( $term ) ) {
				$city_slug = sanitize_title( $term->slug );

				$context['page_type']       = 'taxonomy';
				$context['object_type']     = 'city';
				$context['queried_term_id'] = (int) $term->term_id;
				$context['cities']          = array( $city_slug );

				return self::normalize_context( $context );
			}
		}

		// Singular generic post/page/custom post.
		if ( is_singular() ) {
			$post_id   = get_queried_object_id();
			$post_type = get_post_type( $post_id ) ? get_post_type( $post_id ) : 'post';

			$context['page_type']   = 'singular';
			$context['object_type'] = $post_type;
			$context['post_id']     = (int) $post_id;

			// Manual overview page roles for normal Pages.
			if ( 'page' === $post_type ) {
				$manual_role = self::get_manual_page_role( $post_id );
				if ( ! empty( $manual_role ) ) {
					$context['object_type'] = $manual_role;
					return self::normalize_context( $context );
				}
			}

			$context['industries'] = self::get_post_term_slugs( $post_id, MVC_Directory_Engine::TAX_INDUSTRY );
			$context['services']   = self::get_post_term_slugs( $post_id, MVC_Directory_Engine::TAX_SERVICE );
			$context['cities']     = self::get_post_term_slugs( $post_id, MVC_Directory_Engine::TAX_CITY );

			if ( MVC_Directory_Engine::CPT_BUSINESS === $post_type ) {
				$context['businesses'] = self::get_business_slugs_for_post( $post_id );
			}

			return self::normalize_context( $context );
		}

		// Post type archive for businesses.
		if ( is_post_type_archive( MVC_Directory_Engine::CPT_BUSINESS ) ) {
			$context['page_type']   = 'archive';
			$context['object_type'] = 'business_archive';

			return self::normalize_context( $context );
		}

		// Fallback: home/front/archive/other.
		if ( is_front_page() || is_home() ) {
			$context['page_type']   = 'front';
			$context['object_type'] = 'general';
			return self::normalize_context( $context );
		}

		if ( is_archive() ) {
			$context['page_type']   = 'archive';
			$context['object_type'] = 'general_archive';
			return self::normalize_context( $context );
		}

		$context['page_type']   = 'other';
		$context['object_type'] = 'general';

		return self::normalize_context( $context );
	}

	/**
	 * Gets normalized post term slugs for a taxonomy.
	 *
	 * @param int    $post_id
	 * @param string $taxonomy
	 * @return array<int,string>
	 */
	private static function get_post_term_slugs( $post_id, $taxonomy ) {
		$post_id = absint( $post_id );

		if ( $post_id <= 0 || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_the_terms( $post_id, $taxonomy );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		$slugs = array();
		foreach ( $terms as $term ) {
			if ( ! empty( $term->slug ) ) {
				$slugs[] = sanitize_title( $term->slug );
			}
		}

		$slugs = array_values( array_unique( array_filter( $slugs ) ) );
		sort( $slugs );

		return $slugs;
	}

	/**
	 * Uses existing engine helper if available to resolve effective business services.
	 *
	 * @param int $post_id
	 * @return array<int,string>
	 */
	private static function get_business_effective_service_slugs( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return array();
		}

		if ( function_exists( 'mvc_de_get_effective_service_slugs' ) ) {
			$slugs = mvc_de_get_effective_service_slugs( $post_id );
			if ( is_array( $slugs ) ) {
				$slugs = array_values( array_unique( array_filter( array_map( 'sanitize_title', $slugs ) ) ) );
				sort( $slugs );
				return $slugs;
			}
		}

		return self::get_post_term_slugs( $post_id, MVC_Directory_Engine::TAX_SERVICE );
	}

	/**
	 * For business pages, use the post slug as the business identifier.
	 *
	 * @param int $post_id
	 * @return array<int,string>
	 */
	private static function get_business_slugs_for_post( $post_id ) {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 ) {
		return array();
	}

	// First try explicit business taxonomy terms.
	$terms = get_the_terms( $post_id, 'business_cat' );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$slugs = array();

		foreach ( $terms as $term ) {
			if ( ! empty( $term->slug ) ) {
				$slugs[] = sanitize_title( $term->slug );
			}
		}

		$slugs = array_values( array_unique( array_filter( $slugs ) ) );
		sort( $slugs );

		if ( ! empty( $slugs ) ) {
			return $slugs;
		}
	}

	// Fallback to post slug if taxonomy term is not assigned yet.
	$post = get_post( $post_id );
	if ( ! $post || is_wp_error( $post ) || empty( $post->post_name ) ) {
		return array();
	}

	return array( sanitize_title( $post->post_name ) );
}

	/**
	 * Detect if a normal WordPress Page has been assigned a manual overview role.
	 *
	 * @param int $post_id
	 * @return string
	 */
	private static function get_manual_page_role( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 || ! function_exists( 'mvc_directory_engine' ) ) {
			return '';
		}

		$page_roles = mvc_directory_engine()->get_page_roles();
		if ( empty( $page_roles ) || ! is_array( $page_roles ) ) {
			return '';
		}

		foreach ( $page_roles as $role => $page_id ) {
			if ( absint( $page_id ) === $post_id ) {
				return sanitize_key( $role );
			}
		}

		return '';
	}

	/**
	 * Normalizes and deduplicates the final context.
	 *
	 * @param array<string,mixed> $context
	 * @return array<string,mixed>
	 */
	private static function normalize_context( $context ) {
		$context['page_type']       = isset( $context['page_type'] ) ? sanitize_key( (string) $context['page_type'] ) : '';
		$context['object_type']     = isset( $context['object_type'] ) ? sanitize_key( (string) $context['object_type'] ) : '';
		$context['post_id']         = isset( $context['post_id'] ) ? absint( $context['post_id'] ) : 0;
		$context['queried_term_id'] = isset( $context['queried_term_id'] ) ? absint( $context['queried_term_id'] ) : 0;

		foreach ( array( 'industries', 'services', 'cities', 'businesses' ) as $key ) {
			$values = isset( $context[ $key ] ) && is_array( $context[ $key ] ) ? $context[ $key ] : array();
			$values = array_values( array_unique( array_filter( array_map( 'sanitize_title', $values ) ) ) );
			sort( $values );
			$context[ $key ] = $values;
		}

		return $context;
	}
}