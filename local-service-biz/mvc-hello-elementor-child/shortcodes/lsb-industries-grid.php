<?php
/**
 * Shortcode: [lsb_industries_grid]
 * File: /shortcodes/lsb-industries-grid.php
 *
 * Renders the industries grid dynamically:
 *   - Pulls all top-level industry_cat terms
 *   - Counts published businesses CPT posts assigned to each term
 *   - Pulls the term description from the industry_cat term
 *   - Links to /industries/{term-slug}/
 *
 * Registration: add to functions.php →
 *   require_once get_stylesheet_directory() . '/shortcodes/lsb-industries-grid.php';
 */

if ( ! function_exists( 'lsb_industries_grid_shortcode' ) ) :

function lsb_industries_grid_shortcode( $atts ) {

    $atts = shortcode_atts( [
        'hide_empty' => false, // set true to hide industries with 0 businesses
    ], $atts, 'lsb_industries_grid' );

    // ── 1. Fetch all top-level industry_cat terms ─────────────────────────
    $industry_terms = get_terms( [
        'taxonomy'   => 'industry_cat',
        'hide_empty' => (bool) $atts['hide_empty'],
        'orderby'    => 'name',
        'order'      => 'ASC',
        'parent'     => 0,
    ] );

    if ( is_wp_error( $industry_terms ) || empty( $industry_terms ) ) {
        return '<p>No industries found.</p>';
    }

    // ── 2. Icon map keyed by term slug ────────────────────────────────────
    // Add/edit slugs here to match your exact industry_cat slugs in WP
    $icon_map = [
        'hvac'             => '❄️',
        'roofing'          => '🏠',
        'plumbing'         => '🔧',
        'auto-body-shop'   => '🚗',
        'auto-body'        => '🚗',
        'locksmith'        => '🔑',
        'restoration'      => '🌊',
        'catering'         => '🍽️',
        'realtors'         => '🏡',
        'public-adjusting' => '📋',
        'scrap-metals'     => '♻️',
        'dental-broker'    => '🦷',
    ];

    // ── 3. Build output ───────────────────────────────────────────────────
    $out = '';

    $out .= '<div class="lsb-ig-grid">';

    foreach ( $industry_terms as $term ) {

        // Business count: query businesses CPT filtered by this industry_cat term
        $business_query = new WP_Query( [
            'post_type'      => 'businesses',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids', // lightweight — only fetch IDs
            'tax_query'      => [
                [
                    'taxonomy'         => 'industry_cat',
                    'field'            => 'term_id',
                    'terms'            => $term->term_id,
                    'include_children' => true,
                ],
            ],
        ] );

        $business_count = $business_query->found_posts;
        wp_reset_postdata();

        // Term description (set in WP Admin → Industries taxonomy edit screen)
        $description = ! empty( $term->description )
            ? esc_html( $term->description )
            : '';

        // Icon fallback to 🏢 if slug not in map
        $icon = isset( $icon_map[ $term->slug ] ) ? $icon_map[ $term->slug ] : '🏢';

        // Industry single page URL
        $url = esc_url( home_url( '/industries/' . $term->slug . '/' ) );

        $count_label = $business_count === 1 ? '1 professional' : $business_count . ' professionals';

        $out .= '<a href="' . $url . '" class="lsb-ig-card" aria-label="' . esc_attr( $term->name ) . ' — ' . esc_attr( $count_label ) . '">';
        $out .=   '<div class="lsb-ig-icon" aria-hidden="true">' . $icon . '</div>';
        $out .=   '<div class="lsb-ig-name">' . esc_html( $term->name ) . '</div>';
        if ( $description ) {
            $out .= '<div class="lsb-ig-desc">' . $description . '</div>';
        }
        $out .=   '<div class="lsb-ig-count">' . esc_html( $count_label ) . '</div>';
        $out .= '</a>';
    }

    $out .= '</div>';

    // ── 4. Styles ─────────────────────────────────────────────────────────
    $out .= '<style>
.lsb-ig-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}
.lsb-ig-card {
    background: #F5F7FA;
    border: 1px solid #E4EAF2;
    border-radius: 14px;
    padding: 28px 24px;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s, background 0.2s;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
    overflow: hidden;
    outline: none;
}
.lsb-ig-card::before {
    content: "";
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: #00C9A7;
    transform: scaleX(0);
    transition: transform 0.2s;
    transform-origin: left;
}
.lsb-ig-card:hover,
.lsb-ig-card:focus,
.lsb-ig-card:focus-visible {
    border-color: #00C9A7;
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(13,27,42,0.10);
    background: #FFFFFF;
}
.lsb-ig-card:hover::before,
.lsb-ig-card:focus::before,
.lsb-ig-card:focus-visible::before {
    transform: scaleX(1);
}
.lsb-ig-card:focus,
.lsb-ig-card:focus-visible {
    box-shadow:
        0 0 0 4px rgba(0,201,167,0.18),
        0 12px 32px rgba(13,27,42,0.10);
}
.lsb-ig-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    background: rgba(0,201,167,0.10);
    flex-shrink: 0;
}
.lsb-ig-name {
    font-family: "Syne", sans-serif;
    font-weight: 800;
    font-size: 1rem;
    color: #0D1B2A;
    letter-spacing: -0.01em;
    margin: 0;
}
.lsb-ig-desc {
    font-family: "DM Sans", sans-serif;
    font-size: 0.82rem;
    color: #5F6F85;
    font-weight: 400;
    line-height: 1.5;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.lsb-ig-count {
    font-family: "DM Sans", sans-serif;
    font-size: 0.9rem;
    color: #00C9A7;
    font-weight: 600;
    letter-spacing: 0.01em;
    margin: 0;
    margin-top: auto;
}
@media (prefers-reduced-motion: reduce) {
    .lsb-ig-card,
    .lsb-ig-card::before { transition: none; }
}
</style>';

    return $out;
}

add_shortcode( 'lsb_industries_grid', 'lsb_industries_grid_shortcode' );

endif;