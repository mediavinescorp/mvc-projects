<?php
/**
 * Template: City + Industry Intersection Page
 *
 * File: /wp-content/themes/mvc-hello-elementor-child/template-city-industry.php
 *
 * Loaded automatically via template_include filter in functions.php when
 * both city_slug and industry_slug query vars are present in the URL.
 *
 * Add this to functions.php:
 *
 *   add_filter( 'template_include', function( $template ) {
 *       $city_slug     = get_query_var( 'city_slug' );
 *       $industry_slug = get_query_var( 'industry_slug' );
 *       if ( $city_slug && $industry_slug ) {
 *           $override = locate_template( 'template-city-industry.php' );
 *           if ( $override ) return $override;
 *       }
 *       return $template;
 *   });
 *
 * Also add to functions.php:
 *   require_once get_stylesheet_directory() . '/shortcodes/city-industry-page.php';
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<main id="lsb-ci-main" role="main">
    <?php echo do_shortcode( '[city_industry_page]' ); ?>
</main>

<?php
get_footer();