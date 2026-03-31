<?php
/**
 * Shortcode: [featured_cities]
 *
 * File: /wp-content/themes/mvc-hello-elementor-child/shortcodes/featured-cities.php
 * Register in functions.php:
 *   require_once get_stylesheet_directory() . '/shortcodes/featured-cities.php';
 *
 * Renders the Featured Cities section on the homepage.
 *
 * City links go to: /cities/{city-slug}/businesses/
 * "View All" link goes to: /cities/
 *
 * Layout:
 *   - Hero card  → 1 featured city (Los Angeles always pinned, or most businesses)
 *   - Grid right → 4 cities, daily-rotating
 *   - Pills row  → remaining cities, daily-rotating, capped at 9 + "X more" link
 *
 * Daily rotation uses a date seed so cities change once per day,
 * consistently for all visitors on a given day.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// To this:
if ( ! function_exists( 'lsb_homepage_featured_cities_shortcode' ) ) :
function lsb_homepage_featured_cities_shortcode( $atts ) {

    $atts = shortcode_atts( [
        'hero_city'   => 'los-angeles', // slug of the pinned hero city
        'grid_count'  => 4,             // number of cities in the 2x2 grid
        'pill_count'  => 9,             // number of pill cities shown
    ], $atts, 'featured_cities' );

    $hero_slug   = sanitize_title( $atts['hero_city'] );
    $grid_count  = absint( $atts['grid_count'] );
    $pill_count  = absint( $atts['pill_count'] );

    // ── 1. Fetch all city_cat terms ───────────────────────────────────────
    $all_city_terms = get_terms( [
        'taxonomy'   => 'city_cat',
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'parent'     => 0,
    ] );

    if ( is_wp_error( $all_city_terms ) || empty( $all_city_terms ) ) {
        return '<!-- [featured_cities] no city terms found -->';
    }

    $total_cities = count( $all_city_terms );

    // ── 2. Separate hero city from the rest ───────────────────────────────
    $hero_term    = null;
    $other_terms  = [];

    foreach ( $all_city_terms as $term ) {
        if ( $term->slug === $hero_slug ) {
            $hero_term = $term;
        } else {
            $other_terms[] = $term;
        }
    }

    // Fallback: if hero slug not found, use the city with most businesses
    if ( ! $hero_term && ! empty( $all_city_terms ) ) {
        $hero_term   = $all_city_terms[0];
        $other_terms = array_slice( $all_city_terms, 1 );
    }

    // ── 3. Daily seed shuffle of remaining cities ─────────────────────────
    $daily_seed = (int) date( 'Ymd' );

    usort( $other_terms, function( $a, $b ) use ( $daily_seed ) {
        return crc32( $daily_seed . $a->slug ) - crc32( $daily_seed . $b->slug );
    } );

    // ── 4. Split into grid cities and pill cities ─────────────────────────
    $grid_terms      = array_slice( $other_terms, 0, $grid_count );
    $pill_terms      = array_slice( $other_terms, $grid_count, $pill_count );
    $remaining_count = max( 0, $total_cities - 1 - $grid_count - $pill_count );
    // 1 = hero city subtracted

    // ── 5. Helper: get business count for a city term ─────────────────────
    // Uses term->count (businesses CPT posts tagged with this city_cat term)
    // This is already populated by WordPress when hide_empty => true
    // For display we just use the term count directly.

    // ── 6. Helper: get industry names for a city (up to N) ───────────────
    // We query businesses in this city and collect their industry_cat terms
    function lsb_fc_get_city_industries( $city_term_id, $limit = 4 ) {
        $biz_ids = get_posts( [
            'post_type'      => 'businesses',
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'fields'         => 'ids',
            'tax_query'      => [ [
                'taxonomy' => 'city_cat',
                'field'    => 'term_id',
                'terms'    => $city_term_id,
            ] ],
        ] );

        if ( empty( $biz_ids ) ) return [];

        $ind_names = [];
        foreach ( $biz_ids as $biz_id ) {
            $ind_terms = get_the_terms( $biz_id, 'industry_cat' );
            if ( $ind_terms && ! is_wp_error( $ind_terms ) ) {
                foreach ( $ind_terms as $ind ) {
                    $ind_names[ $ind->slug ] = $ind->name;
                }
            }
            if ( count( $ind_names ) >= $limit ) break;
        }

        return array_values( $ind_names );
    }

    // ── 7. Get hero city data ─────────────────────────────────────────────
    $hero_biz_count  = $hero_term->count;
    $hero_industries = lsb_fc_get_city_industries( $hero_term->term_id, 4 );
    $hero_ind_total  = count( get_terms( [
        'taxonomy'   => 'industry_cat',
        'hide_empty' => false,
        'fields'     => 'ids',
    ] ) );
$hero_url = home_url( '/cities/' . $hero_term->slug . '/' );
    $cities_index    = home_url( '/locations/' );

    ob_start();
    ?>

    <style id="lsb-fc-css">

    section.fc-section {
        background: #F5F7FA !important;
        padding: 100px 40px !important;
        margin: 0 !important;
        position: relative !important;
        z-index: 1 !important;
        box-sizing: border-box !important;
    }
    section.fc-section * { box-sizing: border-box; }

    section.fc-section .fc-inner {
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }

    section.fc-section .fc-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-end !important;
        margin-bottom: 48px !important;
        flex-wrap: nowrap !important;
        gap: 20px !important;
    }
    section.fc-section .fc-header-left {
        display: flex;
        flex-direction: column;
    }
    section.fc-section .fc-label {
        display: block !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.12em !important;
        text-transform: uppercase !important;
        color: #00C9A7 !important;
        margin-bottom: 10px !important;
        font-family: 'DM Sans', sans-serif !important;
    }
    section.fc-section .fc-title {
        font-family: 'Syne', sans-serif !important;
        font-size: clamp(1.8rem, 3.5vw, 2.8rem) !important;
        font-weight: 800 !important;
        color: #0D1B2A !important;
        letter-spacing: -0.02em !important;
        line-height: 1.1 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    section.fc-section .fc-view-all {
        font-family: 'DM Sans', sans-serif !important;
        font-size: 0.95rem !important;
        font-weight: 700 !important;
        color: #00A88C !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        transition: gap 0.2s !important;
        white-space: nowrap !important;
        flex-shrink: 0 !important;
        outline: none !important;
    }
    section.fc-section .fc-view-all:hover { gap: 10px !important; }
    section.fc-section .fc-view-all:focus,
    section.fc-section .fc-view-all:focus-visible {
        box-shadow: 0 0 0 4px rgba(0,201,167,0.18) !important;
        border-radius: 8px !important;
    }

    /* Layout */
    section.fc-section .fc-layout {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 20px !important;
        margin-bottom: 20px !important;
    }

    /* Hero card */
    section.fc-section .fc-hero-card {
        background: #0D1B2A !important;
        border-radius: 20px !important;
        padding: 44px 40px !important;
        text-decoration: none !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: flex-end !important;
        min-height: 340px !important;
        position: relative !important;
        overflow: hidden !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
        border: none !important;
        outline: none !important;
    }
    section.fc-section .fc-hero-card::before {
        content: '' !important;
        position: absolute !important;
        inset: 0 !important;
        background:
            radial-gradient(ellipse at 80% 20%, rgba(0,201,167,0.18) 0%, transparent 55%),
            radial-gradient(ellipse at 20% 80%, rgba(244,197,66,0.10) 0%, transparent 55%) !important;
        pointer-events: none !important;
    }
    section.fc-section .fc-hero-card:hover,
    section.fc-section .fc-hero-card:focus-visible {
        transform: translateY(-3px) !important;
        box-shadow: 0 20px 48px rgba(13,27,42,0.30) !important;
    }
    section.fc-section .fc-hero-card:focus-visible {
        box-shadow: 0 0 0 4px rgba(0,201,167,0.20), 0 20px 48px rgba(13,27,42,0.30) !important;
    }
    section.fc-section .fc-hero-grid-bg {
        position: absolute !important;
        inset: 0 !important;
        background-image:
            linear-gradient(rgba(0,201,167,0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,201,167,0.05) 1px, transparent 1px) !important;
        background-size: 40px 40px !important;
        pointer-events: none !important;
    }
    section.fc-section .fc-hero-badge {
        position: absolute !important;
        top: 28px !important;
        left: 40px !important;
        background: #00C9A7 !important;
        color: #0D1B2A !important;
        font-family: 'Syne', sans-serif !important;
        font-size: 0.72rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.10em !important;
        text-transform: uppercase !important;
        padding: 6px 12px !important;
        border-radius: 100px !important;
        display: inline-block !important;
        line-height: 1.2 !important;
    }
    section.fc-section .fc-hero-content { position: relative !important; z-index: 1 !important; }
    section.fc-section .fc-hero-pin {
        font-size: 2rem !important;
        margin-bottom: 14px !important;
        display: block !important;
        line-height: 1 !important;
    }
    section.fc-section .fc-hero-name {
        font-family: 'Syne', sans-serif !important;
        font-size: 2rem !important;
        font-weight: 800 !important;
        color: #FFFFFF !important;
        letter-spacing: -0.02em !important;
        margin-bottom: 10px !important;
        line-height: 1 !important;
        padding: 0 !important;
    }
    section.fc-section .fc-hero-meta {
        font-size: 1rem !important;
        color: rgba(255,255,255,0.82) !important;
        margin-bottom: 22px !important;
        font-weight: 400 !important;
        font-family: 'DM Sans', sans-serif !important;
        line-height: 1.6 !important;
    }
    section.fc-section .fc-hero-tags {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 6px !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }
    section.fc-section .fc-hero-tag {
        background: rgba(255,255,255,0.08) !important;
        border: 1px solid rgba(255,255,255,0.14) !important;
        color: rgba(255,255,255,0.82) !important;
        font-size: 0.82rem !important;
        padding: 5px 10px !important;
        border-radius: 100px !important;
        font-weight: 500 !important;
        font-family: 'DM Sans', sans-serif !important;
        line-height: 1.3 !important;
        display: inline-block !important;
    }

    /* Right grid */
    section.fc-section .fc-grid-right {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        grid-template-rows: 1fr 1fr !important;
        gap: 16px !important;
    }
    section.fc-section .fc-card {
        background: #FFFFFF !important;
        border: 1px solid #E4EAF2 !important;
        border-radius: 16px !important;
        padding: 24px 22px !important;
        text-decoration: none !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s !important;
        position: relative !important;
        overflow: hidden !important;
        outline: none !important;
    }
    section.fc-section .fc-card::after {
        content: '' !important;
        position: absolute !important;
        left: 0 !important; top: 0 !important; bottom: 0 !important;
        width: 3px !important;
        background: #00C9A7 !important;
        transform: scaleY(0) !important;
        transition: transform 0.2s !important;
        transform-origin: bottom !important;
    }
    section.fc-section .fc-card:hover,
    section.fc-section .fc-card:focus-visible {
        border-color: rgba(0,201,167,0.45) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 10px 28px rgba(0,0,0,0.06) !important;
    }
    section.fc-section .fc-card:hover::after,
    section.fc-section .fc-card:focus-visible::after { transform: scaleY(1) !important; }
    section.fc-section .fc-card:focus-visible {
        box-shadow: 0 0 0 4px rgba(0,201,167,0.18), 0 10px 28px rgba(0,0,0,0.06) !important;
    }
    section.fc-section .fc-card-pin {
        font-size: 1.1rem !important;
        margin-bottom: 2px !important;
        display: block !important;
        line-height: 1 !important;
    }
    section.fc-section .fc-card-name {
        font-family: 'Syne', sans-serif !important;
        font-weight: 800 !important;
        font-size: 1rem !important;
        color: #0D1B2A !important;
        letter-spacing: -0.01em !important;
        margin: 0 !important; padding: 0 !important;
        line-height: 1.3 !important;
    }
    section.fc-section .fc-card-meta {
        font-size: 0.95rem !important;
        color: #5F6F85 !important;
        font-weight: 500 !important;
        font-family: 'DM Sans', sans-serif !important;
        line-height: 1.6 !important;
        margin: 0 !important;
    }
    section.fc-section .fc-card-arrow {
        margin-top: auto !important;
        padding-top: 10px !important;
        font-size: 0.95rem !important;
        color: #00A88C !important;
        font-weight: 700 !important;
        font-family: 'DM Sans', sans-serif !important;
        opacity: 0 !important;
        transform: translateX(-4px) !important;
        transition: opacity 0.2s, transform 0.2s !important;
        display: block !important;
    }
    section.fc-section .fc-card:hover .fc-card-arrow,
    section.fc-section .fc-card:focus-visible .fc-card-arrow {
        opacity: 1 !important;
        transform: translateX(0) !important;
    }

    /* Pills */
    section.fc-section .fc-more-cities {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 10px !important;
        align-items: center !important;
        margin-top: 4px !important;
    }
    section.fc-section .fc-more-label {
        font-size: 0.85rem !important;
        color: #5F6F85 !important;
        font-weight: 700 !important;
        font-family: 'DM Sans', sans-serif !important;
        text-transform: uppercase !important;
        letter-spacing: 0.06em !important;
        margin-right: 4px !important;
        line-height: 1 !important;
    }
    section.fc-section .fc-pill {
        background: #FFFFFF !important;
        border: 1px solid #E4EAF2 !important;
        border-radius: 100px !important;
        padding: 10px 18px !important;
        font-size: 0.95rem !important;
        color: #0D1B2A !important;
        font-weight: 600 !important;
        font-family: 'DM Sans', sans-serif !important;
        text-decoration: none !important;
        transition: transform 0.2s, background 0.2s, border-color 0.2s !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        line-height: 1 !important;
        outline: none !important;
    }
    section.fc-section .fc-pill:hover {
        background: #00C9A7 !important;
        border-color: #00C9A7 !important;
        color: #0D1B2A !important;
        transform: translateY(-1px) !important;
    }
    section.fc-section .fc-pill:focus-visible {
        box-shadow: 0 0 0 4px rgba(0,201,167,0.18) !important;
    }
    section.fc-section .fc-pill-dot {
        width: 6px !important;
        height: 6px !important;
        background: #00C9A7 !important;
        border-radius: 50% !important;
        flex-shrink: 0 !important;
        transition: background 0.2s !important;
        display: inline-block !important;
    }
    section.fc-section .fc-pill:hover .fc-pill-dot { background: #0D1B2A !important; }

    /* Responsive */
    @media (max-width: 980px) {
        section.fc-section { padding: 80px 24px !important; }
        section.fc-section .fc-header { flex-wrap: wrap !important; align-items: flex-start !important; }
        section.fc-section .fc-layout { grid-template-columns: 1fr !important; }
        section.fc-section .fc-hero-card { padding: 38px 28px !important; min-height: 300px !important; }
        section.fc-section .fc-hero-badge { left: 28px !important; }
        section.fc-section .fc-grid-right { grid-template-columns: 1fr 1fr !important; }
    }
    @media (max-width: 640px) {
        section.fc-section .fc-grid-right { grid-template-columns: 1fr !important; }
    }
    @media (prefers-reduced-motion: reduce) {
        section.fc-section .fc-hero-card,
        section.fc-section .fc-card,
        section.fc-section .fc-card::after,
        section.fc-section .fc-pill,
        section.fc-section .fc-view-all { transition: none !important; }
    }

    </style>

    <section class="fc-section">
        <div class="fc-inner">

            <div class="fc-header">
                <div class="fc-header-left">
                    <span class="fc-label">Browse by Location</span>
                    <h2 class="fc-title">Top Cities We Serve</h2>
                </div>
                <a href="<?php echo esc_url( $cities_index ); ?>" class="fc-view-all">
                    View All <?php echo esc_html( $total_cities ); ?> Cities →
                </a>
            </div>

            <div class="fc-layout">

                <!-- ── Hero city ── -->
                <a href="<?php echo esc_url( $hero_url ); ?>" class="fc-hero-card">
                    <div class="fc-hero-grid-bg"></div>
                    <span class="fc-hero-badge">Most Popular</span>
                    <div class="fc-hero-content">
                        <span class="fc-hero-pin">📍</span>
                        <div class="fc-hero-name"><?php echo esc_html( $hero_term->name ); ?></div>
                        <div class="fc-hero-meta">
                            <?php echo esc_html( $hero_ind_total ); ?> industries
                            &middot;
                            <?php echo esc_html( number_format( $hero_biz_count ) ); ?>+ businesses listed
                        </div>
                        <?php if ( ! empty( $hero_industries ) ) : ?>
                        <div class="fc-hero-tags">
                            <?php foreach ( $hero_industries as $ind_name ) : ?>
                                <span class="fc-hero-tag"><?php echo esc_html( $ind_name ); ?></span>
                            <?php endforeach; ?>
                            <?php if ( $hero_ind_total > count( $hero_industries ) ) : ?>
                                <span class="fc-hero-tag">+<?php echo esc_html( $hero_ind_total - count( $hero_industries ) ); ?> more</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>

                <!-- ── Grid right: 4 rotating cities ── -->
                <div class="fc-grid-right">
                    <?php foreach ( $grid_terms as $city ) :
                        $city_url   = home_url( '/cities/' . $city->slug . '/' );
                        $biz_count  = $city->count;
                        // Get industry count for this city
                        $city_ind_q = new WP_Query( [
                            'post_type'      => 'businesses',
                            'post_status'    => 'publish',
                            'posts_per_page' => -1,
                            'fields'         => 'ids',
                            'tax_query'      => [ [
                                'taxonomy' => 'city_cat',
                                'field'    => 'term_id',
                                'terms'    => $city->term_id,
                            ] ],
                        ] );
                        $city_biz_ids   = $city_ind_q->posts;
                        wp_reset_postdata();
                        $city_ind_slugs = [];
                        foreach ( $city_biz_ids as $bid ) {
                            $ind_t = get_the_terms( $bid, 'industry_cat' );
                            if ( $ind_t && ! is_wp_error( $ind_t ) ) {
                                foreach ( $ind_t as $it ) $city_ind_slugs[ $it->slug ] = true;
                            }
                        }
                        $city_ind_count = count( $city_ind_slugs );
                    ?>
                    <a href="<?php echo esc_url( $city_url ); ?>" class="fc-card">
                        <div class="fc-card-pin">📍</div>
                        <div class="fc-card-name"><?php echo esc_html( $city->name ); ?></div>
                        <div class="fc-card-meta">
                            <?php echo esc_html( $city_ind_count ); ?> industr<?php echo $city_ind_count === 1 ? 'y' : 'ies'; ?>
                            &middot;
                            <?php echo esc_html( number_format( $biz_count ) ); ?>+ businesses
                        </div>
                        <div class="fc-card-arrow">Explore →</div>
                    </a>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- ── Pills row ── -->
            <?php if ( ! empty( $pill_terms ) ) : ?>
            <div class="fc-more-cities">
                <span class="fc-more-label">More Cities:</span>
                <?php foreach ( $pill_terms as $city ) : ?>
                <a href="<?php echo esc_url( home_url( '/cities/' . $city->slug . '/' ) ); ?>"
                   class="fc-pill">
                    <span class="fc-pill-dot"></span>
                    <?php echo esc_html( $city->name ); ?>
                </a>
                <?php endforeach; ?>
                <?php if ( $remaining_count > 0 ) : ?>
                <a href="<?php echo esc_url( $cities_index ); ?>" class="fc-pill">
                    <span class="fc-pill-dot"></span>
                    + <?php echo esc_html( number_format( $remaining_count ) ); ?> more →
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </section>

    <?php
    return ob_get_clean();
}

add_shortcode( 'featured_cities', 'lsb_homepage_featured_cities_shortcode' );

endif;