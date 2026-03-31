<?php
// Enqueue parent theme styles
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . '/style.css'
    );
} );
// Enable shortcodes in Elementor safely
function mvc_do_shortcode_in_elementor( $content ) {
    return do_shortcode( $content );
}
add_filter( 'elementor/widget/render_content', 'mvc_do_shortcode_in_elementor', 10, 1 );
add_filter( 'elementor/frontend/the_content', 'mvc_do_shortcode_in_elementor', 10, 1 );
// Includes
require_once get_stylesheet_directory() . '/includes/rewrite-rules.php';
// Shortcodes
require_once get_stylesheet_directory() . '/shortcodes/lsb-badges.php';
require_once get_stylesheet_directory() . '/shortcodes/count-stats.php';
require_once get_stylesheet_directory() . '/shortcodes/featured-businesses.php';
require_once get_stylesheet_directory() . '/shortcodes/faq-section.php';
require_once get_stylesheet_directory() . '/shortcodes/shortcodes-business.php';
require_once get_stylesheet_directory() . '/shortcodes/lsb-city-search.php';
require_once get_stylesheet_directory() . '/shortcodes/lsb-featured-cities.php';
require_once get_stylesheet_directory() . '/shortcodes/lsb-blog-section.php';
require_once get_stylesheet_directory() . '/shortcodes/lsb-search.php';
require_once get_stylesheet_directory() . '/shortcodes/industries-hero.php';
require_once get_stylesheet_directory() . '/shortcodes/industries-grid.php';
require_once get_stylesheet_directory() . '/shortcodes/industries-sections.php';
require_once get_stylesheet_directory() . '/shortcodes/industry-single-page.php';
require_once get_stylesheet_directory() . '/shortcodes/industry-service-filter.php';
require_once get_stylesheet_directory() . '/shortcodes/business-results.php';
require_once get_stylesheet_directory() . '/shortcodes/city-business-results.php';
require_once get_stylesheet_directory() . '/shortcodes/city-single-page.php';
require_once get_stylesheet_directory() . '/shortcodes/lsb-icon-maps.php';
require_once get_stylesheet_directory() . '/shortcodes/service-single-page.php';
require_once get_stylesheet_directory() . '/shortcodes/cities-sections.php';
require_once get_stylesheet_directory() . '/shortcodes/lsb-industries-grid.php';
require_once get_stylesheet_directory() . '/shortcodes/local-businesses-page.php';
require_once get_stylesheet_directory() . '/shortcodes/business-badge-shortcode.php';
require_once get_stylesheet_directory() . '/includes/acf-defaults.php';
require_once get_stylesheet_directory() . '/includes/acf-repeater-importer.php';
require_once get_stylesheet_directory() . '/shortcodes/city-industry-page.php';


// Fix industries single query
function mvc_fix_industry_query( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        $matched_post_type = $query->get( 'post_type' );
        if ( $matched_post_type === 'industries' && $query->get( 'name' ) ) {
            $query->is_single   = true;
            $query->is_singular = true;
        }
    }
}
add_action( 'pre_get_posts', 'mvc_fix_industry_query' );
// Auto-assign templates to CPT single posts
function mvc_assign_custom_templates( $template ) {
    if ( is_singular( 'services' ) ) {
        $custom = get_stylesheet_directory() . '/template-service.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    if ( is_singular( 'faqs' ) ) {
        $custom = get_stylesheet_directory() . '/template-faq-single.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    if ( is_singular( 'cities' ) ) {
        $custom = get_stylesheet_directory() . '/template-city.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
}
add_filter( 'template_include', 'mvc_assign_custom_templates' );
// Define weather API key only if not already defined
if ( ! defined( 'LSB_WEATHER_API_KEY' ) ) {
    define( 'LSB_WEATHER_API_KEY', '13a72bfef42c66601038bb536e609f30' );
}

add_filter( 'template_include', function( $template ) {
    $city_slug     = get_query_var( 'city_slug' );
    $industry_slug = get_query_var( 'industry_slug' );
    if ( $city_slug && $industry_slug ) {
        $override = locate_template( 'template-city-industry.php' );
        if ( $override ) return $override;
    }
    return $template;
});