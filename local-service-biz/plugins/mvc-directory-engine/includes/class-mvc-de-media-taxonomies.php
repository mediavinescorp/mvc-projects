<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MVC_DE_Media_Taxonomies {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_business_taxonomy_if_needed' ), 20 );
		add_action( 'init', array( __CLASS__, 'register_image_usage_taxonomy' ), 21 );
		add_action( 'init', array( __CLASS__, 'attach_taxonomies_to_media' ), 30 );
		add_action( 'init', array( __CLASS__, 'ensure_default_image_usage_terms' ), 40 );

		add_action( 'add_attachment', array( __CLASS__, 'assign_default_image_usage_on_upload' ) );

		

	}

	public static function register_business_taxonomy_if_needed() {
		$taxonomy = 'business_cat';

		if ( taxonomy_exists( $taxonomy ) ) {
			return;
		}

		register_taxonomy(
	$taxonomy,
	array( 'attachment', MVC_Directory_Engine::CPT_BUSINESS ),
			array(
				'labels' => array(
					'name'              => 'Businesses',
					'singular_name'     => 'Business',
					'search_items'      => 'Search Businesses',
					'all_items'         => 'All Businesses',
					'edit_item'         => 'Edit Business',
					'update_item'       => 'Update Business',
					'add_new_item'      => 'Add New Business',
					'new_item_name'     => 'New Business Name',
					'menu_name'         => 'Businesses',
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => false,
				'show_tagcloud'     => false,
				'hierarchical'      => true,
				'rewrite'           => false,
				'show_in_rest'      => true,
			)
		);
	}

	public static function register_image_usage_taxonomy() {
		$taxonomy = MVC_Directory_Engine::TAX_IMAGE_USAGE;

		if ( taxonomy_exists( $taxonomy ) ) {
			return;
		}

		register_taxonomy(
			$taxonomy,
			array( 'attachment' ),
			array(
				'labels' => array(
					'name'              => 'Image Usage',
					'singular_name'     => 'Image Usage',
					'search_items'      => 'Search Image Usage',
					'all_items'         => 'All Image Usage Terms',
					'edit_item'         => 'Edit Image Usage',
					'update_item'       => 'Update Image Usage',
					'add_new_item'      => 'Add New Image Usage',
					'new_item_name'     => 'New Image Usage Name',
					'menu_name'         => 'Image Usage',
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => false,
				'show_tagcloud'     => false,
				'hierarchical'      => true,
				'rewrite'           => false,
				'show_in_rest'      => true,
			)
		);
	}

	public static function ensure_default_image_usage_terms() {
		$taxonomy = MVC_Directory_Engine::TAX_IMAGE_USAGE;

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$default_terms = array(
	MVC_Directory_Engine::IMAGE_USAGE_GALLERY => 'Gallery',
	MVC_Directory_Engine::IMAGE_USAGE_HERO    => 'Hero',
	MVC_Directory_Engine::IMAGE_USAGE_SQUARE  => 'Square',
	MVC_Directory_Engine::IMAGE_USAGE_LOGO    => 'Logo',
	MVC_Directory_Engine::IMAGE_USAGE_ICON    => 'Icon',
	MVC_Directory_Engine::IMAGE_USAGE_EXCLUDE => 'Exclude',
);

		foreach ( $default_terms as $slug => $name ) {
			if ( ! term_exists( $slug, $taxonomy ) ) {
				wp_insert_term(
					$name,
					$taxonomy,
					array(
						'slug' => $slug,
					)
				);
			}
		}
	}

	public static function attach_taxonomies_to_media() {
		$taxonomies = array(
			MVC_Directory_Engine::TAX_INDUSTRY,
			MVC_Directory_Engine::TAX_SERVICE,
			MVC_Directory_Engine::TAX_CITY,
			'business_cat',
			MVC_Directory_Engine::TAX_IMAGE_USAGE,
		);

		foreach ( $taxonomies as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				register_taxonomy_for_object_type( $taxonomy, 'attachment' );
			}
		}
	}

	/**
	 * Default all new uploads to gallery unless changed later.
	 */
	public static function assign_default_image_usage_on_upload( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( $attachment_id <= 0 ) {
			return;
		}

		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			return;
		}

		$taxonomy = MVC_Directory_Engine::TAX_IMAGE_USAGE;

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$current_terms = wp_get_object_terms( $attachment_id, $taxonomy, array(
			'fields' => 'slugs',
		) );

		if ( is_wp_error( $current_terms ) || ! empty( $current_terms ) ) {
			return;
		}

		$gallery_term = get_term_by( 'slug', MVC_Directory_Engine::IMAGE_USAGE_GALLERY, $taxonomy );
		if ( $gallery_term && ! is_wp_error( $gallery_term ) ) {
			wp_set_object_terms( $attachment_id, array( (int) $gallery_term->term_id ), $taxonomy, false );
		}
	}

	public static function add_attachment_term_fields( $form_fields, $post ) {
		$attachment_id = (int) $post->ID;

		$taxonomies = array(
			MVC_Directory_Engine::TAX_INDUSTRY    => 'Industry',
			MVC_Directory_Engine::TAX_SERVICE     => 'Service',
			MVC_Directory_Engine::TAX_CITY        => 'City',
			'business_cat'                        => 'Business',
			MVC_Directory_Engine::TAX_IMAGE_USAGE => 'Image Usage',
		);

		foreach ( $taxonomies as $taxonomy => $label ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			$current_terms = wp_get_object_terms(
				$attachment_id,
				$taxonomy,
				array(
					'fields' => 'ids',
				)
			);

			if ( is_wp_error( $current_terms ) ) {
				$current_terms = array();
			}

			$field_key = 'mvc_tax_' . $taxonomy;

			// Image Usage should be single-select radio buttons.
			if ( MVC_Directory_Engine::TAX_IMAGE_USAGE === $taxonomy ) {
				$current_term_id = ! empty( $current_terms[0] ) ? absint( $current_terms[0] ) : 0;

				$options_html = '';
				foreach ( $terms as $term ) {
					$options_html .= sprintf(
						'<label style="display:block;margin-bottom:6px;"><input type="radio" name="attachments[%1$d][%2$s]" value="%3$d" %4$s> %5$s</label>',
						$attachment_id,
						esc_attr( $field_key ),
						(int) $term->term_id,
						checked( $current_term_id, (int) $term->term_id, false ),
						esc_html( $term->name )
					);
				}

				$form_fields[ $field_key ] = array(
					'label' => $label,
					'input' => 'html',
					'html'  => $options_html . '<p class="help">Choose one usage type only.</p>',
				);

				continue;
			}

			$options_html = '';
			foreach ( $terms as $term ) {
				$options_html .= sprintf(
					'<option value="%1$d" %2$s>%3$s</option>',
					(int) $term->term_id,
					selected( in_array( (int) $term->term_id, $current_terms, true ), true, false ),
					esc_html( $term->name )
				);
			}

			$form_fields[ $field_key ] = array(
				'label' => $label,
				'input' => 'html',
				'html'  => sprintf(
					'<select multiple="multiple" style="width:100%%; min-height:120px;" name="attachments[%1$d][%2$s][]">%3$s</select><p class="help">Hold Ctrl/Cmd to select multiple terms.</p>',
					$attachment_id,
					esc_attr( $field_key ),
					$options_html
				),
			);
		}

		return $form_fields;
	}

	public static function save_attachment_term_fields( $post, $attachment ) {
		$attachment_id = isset( $post['ID'] ) ? (int) $post['ID'] : 0;
		if ( $attachment_id <= 0 ) {
			return $post;
		}

		$taxonomies = array(
			MVC_Directory_Engine::TAX_INDUSTRY,
			MVC_Directory_Engine::TAX_SERVICE,
			MVC_Directory_Engine::TAX_CITY,
			'business_cat',
			MVC_Directory_Engine::TAX_IMAGE_USAGE,
		);

		foreach ( $taxonomies as $taxonomy ) {
			$field_key = 'mvc_tax_' . $taxonomy;

			if ( ! isset( $attachment[ $field_key ] ) ) {
				continue;
			}

			// Image Usage is single-select.
			if ( MVC_Directory_Engine::TAX_IMAGE_USAGE === $taxonomy ) {
				$term_id = absint( $attachment[ $field_key ] );
				$term_ids = $term_id > 0 ? array( $term_id ) : array();

				wp_set_object_terms( $attachment_id, $term_ids, $taxonomy, false );
				continue;
			}

			$term_ids = $attachment[ $field_key ];
			$term_ids = is_array( $term_ids ) ? $term_ids : array( $term_ids );
			$term_ids = array_map( 'absint', $term_ids );
			$term_ids = array_values( array_filter( array_unique( $term_ids ) ) );

			wp_set_object_terms( $attachment_id, $term_ids, $taxonomy, false );
		}

		return $post;
	}
}