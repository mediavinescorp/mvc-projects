<?php
/**
 * Custom Permalink / Rewrite Rules
 */
function mvc_custom_rewrite_rules() {
    // Neighborhood under City under Industry
    add_rewrite_rule(
        '^industries/([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?post_type=neighborhoods&name=$matches[3]&industry_cat=$matches[1]&city_cat=$matches[2]',
        'top'
    );
    // City under Industry
    add_rewrite_rule(
        '^industries/([^/]+)/([^/]+)/?$',
        'index.php?post_type=cities&name=$matches[2]&industry_cat=$matches[1]',
        'top'
    );
    // Industry
    add_rewrite_rule(
        '^industries/([^/]+)/?$',
        'index.php?post_type=industries&name=$matches[1]',
        'top'
    );
    // Service under Industry
    add_rewrite_rule(
        '^services/([^/]+)/([^/]+)/?$',
        'index.php?post_type=services&name=$matches[2]&industry_cat=$matches[1]',
        'top'
    );
    // City businesses results page — must be ABOVE the city single rule
    add_rewrite_rule(
        '^cities/([^/]+)/businesses/?$',
        'index.php?pagename=city-businesses&city_slug=$matches[1]',
        'top'
    );
    // ── NEW: City + Industry intersection page ────────────────────────────
    // Must be BELOW city-businesses and ABOVE city single rule
    // Resolves: /cities/northridge/hvac/
    add_rewrite_rule(
        '^cities/([^/]+)/([^/]+)/?$',
        'index.php?city_slug=$matches[1]&industry_slug=$matches[2]',
        'top'
    );
    // ─────────────────────────────────────────────────────────────────────
    // City single page — must be BELOW the city-businesses and city-industry rules
    add_rewrite_rule(
        '^cities/([^/]+)/?$',
        'index.php?post_type=cities&name=$matches[1]',
        'top'
    );
    // Business results by industry (archive)
    add_rewrite_rule(
        '^businesses/([^/]+)/?$',
        'index.php?post_type=businesses&industry_slug=$matches[1]',
        'top'
    );
    // Business under Industry (single)
    add_rewrite_rule(
        '^businesses/([^/]+)/([^/]+)/?$',
        'index.php?post_type=businesses&name=$matches[2]&industry_cat=$matches[1]',
        'top'
    );
}
add_action( 'init', 'mvc_custom_rewrite_rules' );

/**
 * Whitelist custom query vars
 */
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'industry_slug'; // used by businesses archive AND new city+industry route
    $vars[] = 'city_slug';     // used by city-businesses page AND new city+industry route
    return $vars;
} );

/**
 * Business permalink: /businesses/industry/business/
 */
function mvc_business_permalink( $post_link, $post ) {
    if ( $post->post_type !== 'businesses' ) {
        return $post_link;
    }
    $industry_slug  = 'no-industry';
    $industry_terms = get_the_terms( $post->ID, 'industry_cat' );
    if ( ! empty( $industry_terms ) && ! is_wp_error( $industry_terms ) ) {
        $industry_slug = $industry_terms[0]->slug;
    }
    return home_url( '/businesses/' . $industry_slug . '/' . $post->post_name . '/' );
}
add_filter( 'post_type_link', 'mvc_business_permalink', 10, 2 );

/**
 * Service permalink: /services/industry/service/
 */
function mvc_service_permalink( $post_link, $post ) {
    if ( $post->post_type !== 'services' ) {
        return $post_link;
    }
    $industry_slug  = 'no-industry';
    $industry_terms = get_the_terms( $post->ID, 'industry_cat' );
    if ( ! empty( $industry_terms ) && ! is_wp_error( $industry_terms ) ) {
        $industry_slug = $industry_terms[0]->slug;
    }
    return home_url( '/services/' . $industry_slug . '/' . $post->post_name . '/' );
}
add_filter( 'post_type_link', 'mvc_service_permalink', 10, 2 );