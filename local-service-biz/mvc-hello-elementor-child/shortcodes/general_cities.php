<?php
/**
 * Shortcode: [featured_cities_block]
 *
 * Purpose:
 * - Show H2 + intro text
 * - Show 4 random city cards
 * - Show all remaining cities in a responsive 4-column list
 *
 * File:
 * /wp-content/themes/mvc-hello-elementor-child/shortcodes/featured-cities.php
 *
 * Register in functions.php:
 * require_once get_stylesheet_directory() . '/shortcodes/featured-cities.php';
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'lsb_general_featured_cities_shortcode' ) ) :

/* ─────────────────────────────────────────────
   Helpers
───────────────────────────────────────────── */

function lsb_fc_get_city_industries( $city_term_id, $limit = 4 ) {

    $biz_ids = get_posts([
        'post_type'      => 'businesses',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'fields'         => 'ids',
        'tax_query'      => [[
            'taxonomy' => 'city_cat',
            'field'    => 'term_id',
            'terms'    => $city_term_id,
        ]],
    ]);

    if ( empty($biz_ids) ) return [];

    $ind_names = [];

    foreach ( $biz_ids as $biz_id ) {

        $ind_terms = get_the_terms($biz_id, 'industry_cat');

        if ( $ind_terms && ! is_wp_error($ind_terms) ) {
            foreach ( $ind_terms as $ind ) {
                $ind_names[$ind->slug] = $ind->name;
            }
        }

        if ( count($ind_names) >= $limit ) break;
    }

    return array_slice(array_values($ind_names), 0, $limit);
}

function lsb_fc_get_city_industry_count( $city_term_id ) {

    $biz_ids = get_posts([
        'post_type'      => 'businesses',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => [[
            'taxonomy' => 'city_cat',
            'field'    => 'term_id',
            'terms'    => $city_term_id,
        ]],
    ]);

    if ( empty($biz_ids) ) return 0;

    $industry_slugs = [];

    foreach ( $biz_ids as $biz_id ) {

        $ind_terms = get_the_terms($biz_id, 'industry_cat');

        if ( $ind_terms && ! is_wp_error($ind_terms) ) {
            foreach ( $ind_terms as $ind ) {
                $industry_slugs[$ind->slug] = true;
            }
        }
    }

    return count($industry_slugs);
}


/* ─────────────────────────────────────────────
   Main Shortcode
───────────────────────────────────────────── */

function lsb_general_featured_cities_shortcode( $atts ) {

    $atts = shortcode_atts([
        'card_count' => 4,
    ], $atts, 'featured_cities_block');

    $card_count = absint($atts['card_count']);
    if ( $card_count < 1 ) $card_count = 4;

    $all_city_terms = get_terms([
        'taxonomy'   => 'city_cat',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'parent'     => 0,
    ]);

    if ( is_wp_error($all_city_terms) || empty($all_city_terms) ) {
        return '<!-- [featured_cities_block] no city terms found -->';
    }

    $total_cities = count($all_city_terms);

    /* Daily shuffle */
    $daily_seed = (int) date('Ymd');

    usort($all_city_terms, function($a, $b) use ($daily_seed) {
        return crc32($daily_seed . $a->slug) - crc32($daily_seed . $b->slug);
    });

    $featured_cards   = array_slice($all_city_terms, 0, $card_count);
    $remaining_cities = array_slice($all_city_terms, $card_count);

    ob_start();
?>

<section class="lsb-gc-section">
<div class="lsb-gc-inner">

<div class="lsb-gc-header">

<h2 class="lsb-gc-title">Cities You Can Explore</h2>

<p class="lsb-gc-intro">
Our directory includes businesses serving multiple cities and surrounding areas.
Browsing by location helps you find professionals closer to you and discover services
available in your area.
</p>

</div>

<?php if ( ! empty($featured_cards) ) : ?>
<div class="lsb-gc-cards">

<?php foreach ( $featured_cards as $city ) :

$city_url        = home_url('/cities/' . $city->slug . '/');
$biz_count       = (int) $city->count;
$industry_count  = lsb_fc_get_city_industry_count($city->term_id);
$industry_names  = lsb_fc_get_city_industries($city->term_id, 3);

?>

<a href="<?php echo esc_url($city_url); ?>" class="lsb-gc-card">

<div class="lsb-gc-card-name"><?php echo esc_html($city->name); ?></div>

<div class="lsb-gc-card-meta">
<?php echo esc_html($industry_count); ?> industr<?php echo $industry_count === 1 ? 'y' : 'ies'; ?>
&middot;
<?php echo esc_html(number_format($biz_count)); ?>+ businesses
</div>

<?php if ( ! empty($industry_names) ) : ?>
<div class="lsb-gc-card-tags">
<?php foreach ( $industry_names as $industry_name ) : ?>
<span class="lsb-gc-card-tag"><?php echo esc_html($industry_name); ?></span>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="lsb-gc-card-arrow">Explore city →</div>

</a>

<?php endforeach; ?>

</div>
<?php endif; ?>


<?php if ( ! empty($remaining_cities) ) : ?>

<div class="lsb-gc-list-wrap">

<ul class="lsb-gc-city-list">

<?php

usort($remaining_cities,function($a,$b){
return strcasecmp($a->name,$b->name);
});

foreach ( $remaining_cities as $city ) :

$city_url = home_url('/cities/' . $city->slug . '/');

?>

<li>
<a href="<?php echo esc_url($city_url); ?>" class="lsb-gc-city-link">
<span class="lsb-gc-city-dot"></span>
<?php echo esc_html($city->name); ?>
</a>
</li>

<?php endforeach; ?>

</ul>
</div>

<?php endif; ?>

</div>
</section>


<?php
return ob_get_clean();

}

add_shortcode('featured_cities_block','lsb_general_featured_cities_shortcode');

endif;