<?php
/**
 * Template Name: City Single Page
 *
 * File: /template-city.php  (place in theme root: /wp-content/themes/mvc-hello-elementor-child/)
 *
 * Assign this template to each cities CPT post via Page Attributes → Template.
 *
 * Requires:
 *   - /shortcodes/city-single-page.php  (included via functions.php)
 *   - functions.php line:
 *       require_once get_stylesheet_directory() . '/shortcodes/city-single-page.php';
 */

get_header();

while ( have_posts() ) :
    the_post();
    echo do_shortcode( '[city_page]' );
endwhile;

get_footer();