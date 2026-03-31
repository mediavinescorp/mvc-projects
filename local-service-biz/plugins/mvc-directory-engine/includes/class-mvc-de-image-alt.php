<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MVC_DE_Image_Alt {

	/**
	 * Build dynamic alt text from page context.
	 *
	 * @param int   $attachment_id
	 * @param array $context
	 * @return string
	 */
	public static function get_dynamic_alt( $attachment_id, $context = array() ) {
		$attachment_id = absint( $attachment_id );

		if ( $attachment_id <= 0 ) {
			return '';
		}

		if ( empty( $context ) && function_exists( 'mvc_de_get_current_page_context' ) ) {
			$context = mvc_de_get_current_page_context();
		}

		$industries = ! empty( $context['industries'] ) && is_array( $context['industries'] ) ? $context['industries'] : array();
		$services   = ! empty( $context['services'] ) && is_array( $context['services'] ) ? $context['services'] : array();
		$cities     = ! empty( $context['cities'] ) && is_array( $context['cities'] ) ? $context['cities'] : array();
		$businesses = ! empty( $context['businesses'] ) && is_array( $context['businesses'] ) ? $context['businesses'] : array();

		$industry_label = self::slug_to_label( isset( $industries[0] ) ? $industries[0] : '' );
		$service_label  = self::slug_to_label( isset( $services[0] ) ? $services[0] : '' );
		$city_label     = self::slug_to_label( isset( $cities[0] ) ? $cities[0] : '' );
		$business_label = self::slug_to_label( isset( $businesses[0] ) ? $businesses[0] : '' );

		$object_type = ! empty( $context['object_type'] ) ? sanitize_key( $context['object_type'] ) : '';

		$alt = '';

		// Highest specificity first.
		if ( $business_label && $service_label && $city_label ) {
			$alt = sprintf( '%1$s image for %2$s in %3$s', $service_label, $business_label, $city_label );
		} elseif ( $business_label && $industry_label && $city_label ) {
			$alt = sprintf( '%1$s image for %2$s in %3$s', $industry_label, $business_label, $city_label );
		} elseif ( $service_label && $city_label ) {
			$alt = sprintf( '%1$s service image in %2$s', $service_label, $city_label );
		} elseif ( $industry_label && $city_label ) {
			$alt = sprintf( '%1$s image in %2$s', $industry_label, $city_label );
		} elseif ( $service_label ) {
			$alt = sprintf( '%s service image', $service_label );
		} elseif ( $industry_label ) {
			$alt = sprintf( '%s image', $industry_label );
		} elseif ( $business_label ) {
			$alt = sprintf( '%s business image', $business_label );
		} else {
			// General overview pages.
			switch ( $object_type ) {
				case 'industry_overview':
					$alt = 'Industry overview image';
					break;
				case 'service_overview':
					$alt = 'Service overview image';
					break;
				case 'city_overview':
					$alt = 'City overview image';
					break;
				case 'business_overview':
					$alt = 'Local business overview image';
					break;
				case 'general':
				default:
					$alt = 'Local service business image';
					break;
			}
		}

		$alt = trim( preg_replace( '/\s+/', ' ', $alt ) );

if ( empty( $alt ) ) {
	$alt = 'Local service business image';
}

		/**
		 * Allow future customization.
		 */
		return apply_filters( 'mvc_de_dynamic_image_alt', $alt, $attachment_id, $context );
	}

	/**
	 * Turn slug into readable label.
	 *
	 * @param string $slug
	 * @return string
	 */
	private static function slug_to_label( $slug ) {
		$slug = is_scalar( $slug ) ? (string) $slug : '';
		$slug = sanitize_title( $slug );

		if ( '' === $slug ) {
			return '';
		}

		$label = str_replace( '-', ' ', $slug );
		$label = trim( preg_replace( '/\s+/', ' ', $label ) );

		return ucwords( $label );
	}
}