<?php
/**
 * Shortcode: [city_page]
 *
 * File: /shortcodes/city-single-page.php
 * Called in functions.php:
 *   require_once get_stylesheet_directory() . '/shortcodes/city-single-page.php';
 * Called in template-city.php:
 *   <?php echo do_shortcode('[city_page]'); ?>
 *
 * Sections:
 *   1. Hero             → dark bg — city_overview, industry count, service count
 *   2. Map Embed        → light bg — map_embed ACF field
 *   3. Why City Matters → dark bg  — why_this_city_matters ACF field
 *   4. Industries       → light bg — hidden when ?industry= param present
 *   5. Local Tips       → off-white — local_tips repeater field (list with icons)
 *   6. Featured Biz     → white    — 3 random (unfiltered) or industry-filtered
 *   7. FAQ              → light bg — city_cat filtered, + industry_cat when param present
 *   8. Blog Posts       → off-white — recent posts tagged with city_cat or industry_cat
 *   9. Final CTA        → dark bg
 *
 * ACF fields used on cities CPT:
 *   city_hero_intro          → hero intro paragraph (fallback if city_overview empty)
 *   city_subtitle            → hero subtitle line (italic)
 *   city_hero_image          → hero image (right col, skipped if empty)
 *   city_overview            → main overview paragraph shown in hero
 *   city_industries_intro    → intro above industries grid
 *   city_featured_intro      → intro above featured businesses
 *   city_cta_heading         → final CTA heading
 *   city_cta_text            → final CTA body text
 *   city_cta_button_text     → final CTA button label
 *   city_cta_button_link     → final CTA button URL
 *   faq_source_note_disclaimer → disclaimer shown per FAQ item
 *   map_embed                → iframe embed code for the city map
 *   why_this_city_matters    → WYSIWYG content for Why City Matters section
 *   local_tips               → repeater field: each row has tip_icon (text/emoji), tip_title (text), tip_description (textarea)
 */

if ( ! function_exists( 'lsb_city_page_shortcode' ) ) :

function lsb_city_page_shortcode( $atts ) {

    $atts = shortcode_atts( [ 'city' => '' ], $atts, 'city_page' );

    // ── 1. Get current city post ──────────────────────────────────────────
    $city_post_id = get_the_ID();

    if ( ! empty( $atts['city'] ) ) {
        $override = get_page_by_path( sanitize_title( $atts['city'] ), OBJECT, 'cities' );
        if ( $override ) $city_post_id = $override->ID;
    }

    if ( ! $city_post_id ) {
        return '<!-- [city_page] no post ID found -->';
    }

    // ── 2. Context detection — ?industry= param ───────────────────────────
    $filter_industry_slug = sanitize_title( $_GET['industry'] ?? '' );
    $filter_industry_term = null;
    $is_industry_filtered = false;

    if ( ! empty( $filter_industry_slug ) ) {
        $filter_industry_term = get_term_by( 'slug', $filter_industry_slug, 'industry_cat' );
        if ( $filter_industry_term && ! is_wp_error( $filter_industry_term ) ) {
            $is_industry_filtered = true;
        } else {
            $filter_industry_slug = '';
        }
    }

    // ── 3. CPT fields ─────────────────────────────────────────────────────
    $city_slug = get_post_field( 'post_name',  $city_post_id );
    $city_name = get_post_field( 'post_title', $city_post_id );

    // ── 4. ACF fields ─────────────────────────────────────────────────────
    $hero_intro       = '';
    $hero_subtitle    = '';
    $hero_image       = '';
    $city_overview    = '';
    $industries_intro = '';
    $featured_intro   = '';
    $cta_heading      = '';
    $cta_text         = '';
    $cta_button_text  = '';
    $cta_button_link  = '';
    $disclaimer       = '';
    $map_embed        = '';
    $why_content      = '';
    $local_tips       = [];

    if ( function_exists( 'get_field' ) ) {
        $hero_intro       = get_field( 'city_hero_intro',          $city_post_id );
        $hero_subtitle    = get_field( 'city_subtitle',            $city_post_id );
        $city_overview    = get_field( 'city_overview',            $city_post_id );
        $industries_intro = get_field( 'city_industries_intro',    $city_post_id );
        $featured_intro   = get_field( 'city_featured_intro',      $city_post_id );
        $cta_heading      = get_field( 'city_cta_heading',         $city_post_id );
        $cta_text         = get_field( 'city_cta_text',            $city_post_id );
        $cta_button_text  = get_field( 'city_cta_button_text',     $city_post_id );
        $cta_button_link  = get_field( 'city_cta_button_link',     $city_post_id );
        $disclaimer          = get_field( 'faq_source_note_disclaimer', $city_post_id );
        $map_embed           = get_field( 'map_embed',                  $city_post_id );
        $weather_lookup_name = get_field( 'weather_lookup_name',        $city_post_id );
        $why_content      = get_field( 'why_this_city_matters',    $city_post_id );
        $local_tips       = get_field( 'local_tips',               $city_post_id );

        $raw_image = get_field( 'city_hero_image', $city_post_id );
        if ( is_array( $raw_image ) && ! empty( $raw_image['url'] ) ) {
            $hero_image = $raw_image['url'];
        } elseif ( is_string( $raw_image ) && ! empty( $raw_image ) ) {
            $hero_image = $raw_image;
        }
    }

   // Use city_overview as hero_intro fallback — removed; hero uses city_hero_intro directly

    // Weather lookup name — ACF override for LA neighborhoods that OWM won't find by name
    $weather_city_name = ! empty( $weather_lookup_name ) ? trim( $weather_lookup_name ) : $city_name;

    // ── 5. city_cat term for this city post ───────────────────────────────
    $city_cat_terms = wp_get_post_terms( $city_post_id, 'city_cat', [ 'fields' => 'slugs' ] );
    $city_cat_slug  = ! empty( $city_cat_terms ) ? $city_cat_terms[0] : $city_slug;

    // ── 6. Industries available in this city ──────────────────────────────
    $industry_terms = wp_get_post_terms( $city_post_id, 'industry_cat', [ 'fields' => 'all' ] );
    if ( is_wp_error( $industry_terms ) ) $industry_terms = [];

    // ── 7. Industry & Service counts for hero ─────────────────────────────
    $industry_count = count( $industry_terms );

    // Count services tagged to this city via city_cat
    $service_count_query = new WP_Query( [
        'post_type'      => 'services',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => [ [
            'taxonomy' => 'city_cat',
            'field'    => 'slug',
            'terms'    => $city_cat_slug,
        ] ],
    ] );
    $service_count = $service_count_query->found_posts;
    wp_reset_postdata();

    // ── 8. Featured businesses ────────────────────────────────────────────
    $fb_tax_query = [ [
        'taxonomy' => 'city_cat',
        'field'    => 'slug',
        'terms'    => $city_cat_slug,
    ] ];

    if ( $is_industry_filtered ) {
        $fb_tax_query[] = [
            'taxonomy' => 'industry_cat',
            'field'    => 'slug',
            'terms'    => $filter_industry_slug,
        ];
        $fb_tax_query['relation'] = 'AND';
    }

    $fb_query_args = [
        'post_type'      => 'businesses',
        'post_status'    => 'publish',
        'posts_per_page' => $is_industry_filtered ? 10 : 3,
        'orderby'        => $is_industry_filtered ? 'meta_value_num' : 'rand',
        'order'          => 'DESC',
        'tax_query'      => $fb_tax_query,
        'meta_query'     => [ [
            'key'   => 'featured_business',
            'value' => '1',
        ] ],
    ];

    if ( $is_industry_filtered ) {
        $fb_query_args['meta_key'] = 'business_rating';
    }

    $fb_query = new WP_Query( $fb_query_args );
    $fb_posts = $fb_query->posts;
    wp_reset_postdata();

    // ── 9. FAQs ───────────────────────────────────────────────────────────
    $faq_tax_query = [ [
        'taxonomy' => 'city_cat',
        'field'    => 'slug',
        'terms'    => $city_cat_slug,
    ] ];

    if ( $is_industry_filtered ) {
        $faq_tax_query[] = [
            'taxonomy'         => 'industry_cat',
            'field'            => 'slug',
            'terms'            => $filter_industry_slug,
            'operator'         => 'IN',
            'include_children' => true,
        ];
        $faq_tax_query['relation'] = 'AND';
    }

    $faq_query = new WP_Query( [
        'post_type'      => 'faqs',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'none',
        'tax_query'      => $faq_tax_query,
    ] );
    $faq_all = $faq_query->posts;
    wp_reset_postdata();

    $daily_seed = (int) date( 'Ymd' ) + crc32( $city_slug );
    usort( $faq_all, function( $a, $b ) use ( $daily_seed ) {
        return crc32( $daily_seed . $a->ID ) - crc32( $daily_seed . $b->ID );
    } );
    $faq_posts = array_slice( $faq_all, 0, 7 );

    // ── 10. Blog posts ────────────────────────────────────────────────────
    // Pull recent posts tagged with city_cat or industry_cat (if filtered)
    $blog_tax_query = [ [
        'taxonomy' => 'category',
        'field'    => 'slug',
        'terms'    => $city_cat_slug,
        'operator' => 'IN',
    ] ];

    if ( $is_industry_filtered ) {
        $blog_tax_query = [
            'relation' => 'OR',
            [
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $city_cat_slug,
            ],
            [
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $filter_industry_slug,
            ],
        ];
    }

    // Try category-tagged posts first; fall back to recent posts if none
    $blog_query = new WP_Query( [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 3,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => $blog_tax_query,
    ] );

    // Fallback: latest 3 posts regardless of taxonomy
    if ( ! $blog_query->have_posts() ) {
        $blog_query = new WP_Query( [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 3,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
    }
    $blog_posts = $blog_query->posts;
    wp_reset_postdata();

    // ── 11. CTA fallbacks ─────────────────────────────────────────────────
    $industry_label = $is_industry_filtered ? esc_html( $filter_industry_term->name ) . ' ' : '';

    if ( ! $cta_heading )     $cta_heading     = 'Find Trusted ' . $industry_label . 'Professionals in ' . $city_name;
    if ( ! $cta_text )        $cta_text        = 'Connect with verified local ' . strtolower( $industry_label ) . 'service experts serving ' . $city_name . '. Browse profiles, compare services, and reach out directly — no middleman, no fees.';
    if ( ! $cta_button_text ) $cta_button_text = 'Find a Professional';
    if ( ! $cta_button_link ) {
        $cta_button_link = $is_industry_filtered
            ? home_url( '/businesses/' . $filter_industry_slug . '/?city=' . $city_slug )
            : home_url( '/cities/' . $city_slug . '/businesses/' );
    }

    // ── 12. URLs ──────────────────────────────────────────────────────────
    $home_url   = trailingslashit( home_url() );
    $cities_url = $home_url . 'cities/';

    // ── 13. Hero page title ───────────────────────────────────────────────
    $hero_title_main = $city_name;
    $hero_title_em   = $is_industry_filtered
        ? $filter_industry_term->name . ' Services'
        : 'Local Services';

    // ── 14. Hero badge label ──────────────────────────────────────────────
    $hero_badge_label = $is_industry_filtered
        ? $filter_industry_term->name . ' · ' . $city_name . ', CA'
        : $city_name . ', California';

    // ── 15. Tip icon keyword → emoji mapper ───────────────────────────────
    // Icon resolution delegated to lsb-icon-maps.php (loaded via functions.php)

    // ── Weather data ──────────────────────────────────────────────────────
    $weather_data    = null;
    $forecast_data   = [];
    $weather_api_key = defined( 'LSB_WEATHER_API_KEY' ) ? LSB_WEATHER_API_KEY : '13a72bfef42c66601038bb536e609f30';

    if ( $weather_api_key ) {
      $weather_cache_key  = 'lsb_weather_ca_' . md5( $weather_city_name );
        $forecast_cache_key = 'lsb_forecast_ca_' . md5( $weather_city_name );

        $weather_data  = get_transient( $weather_cache_key );
        $forecast_data = get_transient( $forecast_cache_key );

        if ( false === $weather_data ) {
          $api_url = 'https://api.openweathermap.org/data/2.5/weather?q='
                . urlencode( $weather_city_name . ',CA,US' )
                . '&units=imperial&appid=' . $weather_api_key;

            $response = wp_remote_get( $api_url, [ 'timeout' => 8 ] );

            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $weather_data = json_decode( wp_remote_retrieve_body( $response ), true );
                set_transient( $weather_cache_key, $weather_data, HOUR_IN_SECONDS );
            } else {
                $weather_data = []; // empty = error state, prevents re-fetching until transient expires
                set_transient( $weather_cache_key, $weather_data, 15 * MINUTE_IN_SECONDS );
            }
        }

        if ( false === $forecast_data ) {
         $fc_url = 'https://api.openweathermap.org/data/2.5/forecast?q='
                . urlencode( $weather_city_name . ',CA,US' )
                . '&units=imperial&cnt=24&appid=' . $weather_api_key;

            $fc_response = wp_remote_get( $fc_url, [ 'timeout' => 8 ] );

            if ( ! is_wp_error( $fc_response ) && wp_remote_retrieve_response_code( $fc_response ) === 200 ) {
                $fc_raw       = json_decode( wp_remote_retrieve_body( $fc_response ), true );
                $forecast_data = $fc_raw['list'] ?? [];
                set_transient( $forecast_cache_key, $forecast_data, HOUR_IN_SECONDS );
            } else {
                $forecast_data = [];
                set_transient( $forecast_cache_key, $forecast_data, 15 * MINUTE_IN_SECONDS );
            }
        }
    }

    // Weather icon helper — maps OWM icon codes to emojis
    $weather_emoji = function( $icon_code ) {
        $map = [
            '01d' => '☀️',  '01n' => '🌙',
            '02d' => '⛅',  '02n' => '⛅',
            '03d' => '☁️',  '03n' => '☁️',
            '04d' => '☁️',  '04n' => '☁️',
            '09d' => '🌧️', '09n' => '🌧️',
            '10d' => '🌦️', '10n' => '🌧️',
            '11d' => '⛈️', '11n' => '⛈️',
            '13d' => '❄️',  '13n' => '❄️',
            '50d' => '🌫️', '50n' => '🌫️',
        ];
        return $map[ $icon_code ] ?? '🌤️';
    };

    // Build 5-day forecast — one entry per unique day (noon reading preferred)
    $forecast_days = [];
    if ( ! empty( $forecast_data ) ) {
        foreach ( $forecast_data as $entry ) {
            $day_key = date( 'Y-m-d', $entry['dt'] );
            $hour    = (int) date( 'G', $entry['dt'] );
            if ( ! isset( $forecast_days[ $day_key ] ) || abs( $hour - 12 ) < abs( (int) date( 'G', $forecast_days[ $day_key ]['dt'] ) - 12 ) ) {
                $forecast_days[ $day_key ] = $entry;
            }
        }
        // Skip today, take next 4 days
        $today_key     = date( 'Y-m-d' );
        $forecast_days = array_filter( $forecast_days, fn( $k ) => $k !== $today_key, ARRAY_FILTER_USE_KEY );
        $forecast_days = array_slice( $forecast_days, 0, 4 );
    }

    // ── RSS / Google News data ────────────────────────────────────────────
    // Build list of industries to fetch news for
    $news_industries = [];
    if ( $is_industry_filtered && $filter_industry_term ) {
        $news_industries[] = [
            'slug' => $filter_industry_slug,
            'name' => $filter_industry_term->name,
        ];
    } elseif ( ! empty( $industry_terms ) ) {
        foreach ( $industry_terms as $ind_t ) {
            $news_industries[] = [
                'slug' => $ind_t->slug,
                'name' => $ind_t->name,
            ];
        }
    }

    // Fetch and cache RSS per industry
    $news_feeds = []; // keyed by industry slug → array of article arrays

    foreach ( $news_industries as $news_ind ) {
        $cache_key = 'lsb_news_' . md5( $news_ind['name'] . '_' . $city_name );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            $news_feeds[ $news_ind['slug'] ] = $cached;
            continue;
        }

        $query   = urlencode( $news_ind['name'] . ' ' . $city_name . ' contractors' );
        $rss_url = 'https://news.google.com/rss/search?q=' . $query . '&hl=en-US&gl=US&ceid=US:en';
        $resp    = wp_remote_get( $rss_url, [ 'timeout' => 10 ] );

        $articles = [];
        if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) === 200 ) {
            $xml = simplexml_load_string( wp_remote_retrieve_body( $resp ) );
            if ( $xml && isset( $xml->channel->item ) ) {
                $count = 0;
                foreach ( $xml->channel->item as $item ) {
                    if ( $count >= 4 ) break;
                    // Extract source from title — Google News format: "Title - Source"
                    $raw_title = (string) $item->title;
                    $source    = '';
                    if ( preg_match( '/\s[-–]\s([^-–]+)$/', $raw_title, $m ) ) {
                        $source    = trim( $m[1] );
                        $raw_title = trim( preg_replace( '/\s[-–]\s[^-–]+$/', '', $raw_title ) );
                    }
                    $articles[] = [
                        'title'  => $raw_title,
                        'source' => $source,
                        'link'   => (string) $item->link,
                        'date'   => date( 'M j, Y', strtotime( (string) $item->pubDate ) ),
                    ];
                    $count++;
                }
            }
        }

        set_transient( $cache_key, $articles, 6 * HOUR_IN_SECONDS );
        $news_feeds[ $news_ind['slug'] ] = $articles;
    }

    // ── 16. Render ────────────────────────────────────────────────────────
    ob_start();
    ?>

    <!-- ============================================================
         STYLES
    ============================================================ -->
    <style id="lsb-city-page-css">

    /* ============================
       COLOR SYSTEM
    ============================ */
    .lsb-cp-dark-bg {
        --lsb-heading:  #F5F7FA;
        --lsb-body:     rgba(255,255,255,0.60);
        --lsb-muted:    rgba(255,255,255,0.35);
        --lsb-label:    #00C9A7;
        --lsb-border:   rgba(255,255,255,0.08);
        --lsb-card-bg:  rgba(255,255,255,0.04);
        --lsb-divider:  rgba(255,255,255,0.08);
    }
    .lsb-cp-light-bg {
        --lsb-heading:  #0D1B2A;
        --lsb-body:     #3D4F63;
        --lsb-muted:    #8A9BB0;
        --lsb-label:    #00A88C;
        --lsb-border:   #E4EAF2;
        --lsb-card-bg:  #F5F7FA;
        --lsb-divider:  #E4EAF2;
    }

    /* ============================
       SHARED TYPOGRAPHY
    ============================ */
    .lsb-cp-section-label {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--lsb-label);
        margin-bottom: 12px;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-cp-h2 {
        font-family: 'Syne', sans-serif;
        font-size: clamp(1.8rem, 3.5vw, 2.6rem);
        font-weight: 800;
        color: var(--lsb-heading) !important;
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-bottom: 20px;
    }

    /* ============================
       BREADCRUMB BAR
    ============================ */
    .lsb-cp-breadcrumb-bar {
        background: #0D1B2A;
        padding: 0 40px;
        height: 44px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        margin-top: 72px;
    }
    .lsb-cp-breadcrumb {
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        color: rgba(255,255,255,0.4);
        list-style: none;
        padding: 0;
        font-family: 'DM Sans', sans-serif;
        flex-wrap: wrap;
    }
    .lsb-cp-breadcrumb a           { color: rgba(255,255,255,0.4); text-decoration: none; transition: color .2s; }
    .lsb-cp-breadcrumb a:hover     { color: #00C9A7; }
    .lsb-cp-breadcrumb-sep         { color: rgba(255,255,255,0.2); }
    .lsb-cp-breadcrumb-current     { color: #00C9A7; font-weight: 500; }

    /* ============================
       SECTION 1 — HERO
    ============================ */
    .lsb-cp-hero {
        background: #0D1B2A;
        padding: 56px 40px 64px;
        position: relative;
        overflow: hidden;
    }
    .lsb-cp-hero-grid-bg {
        position: absolute; inset: 0; pointer-events: none;
        background-image:
            linear-gradient(rgba(0,201,167,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,201,167,0.04) 1px, transparent 1px);
        background-size: 60px 60px;
    }
    .lsb-cp-hero-glow-1 {
        position: absolute; pointer-events: none;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(0,201,167,0.10) 0%, transparent 70%);
        top: -120px; right: -80px;
    }
    .lsb-cp-hero-glow-2 {
        position: absolute; pointer-events: none;
        width: 340px; height: 340px;
        background: radial-gradient(circle, rgba(244,197,66,0.06) 0%, transparent 70%);
        bottom: 0; left: 100px;
    }
    .lsb-cp-hero-inner {
        max-width: 1200px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }
    .lsb-cp-hero-columns {
        display: grid;
        grid-template-columns: 1fr 460px;
        gap: 64px;
        align-items: center;
    }
    .lsb-cp-hero-columns.no-image {
        grid-template-columns: 1fr;
        max-width: 780px;
    }

    /* Industry context banner */
    .lsb-cp-context-banner {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(244,197,66,0.10);
        border: 1px solid rgba(244,197,66,0.25);
        border-radius: 8px;
        padding: 10px 16px;
        margin-bottom: 20px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.82rem;
        color: rgba(255,255,255,0.7);
    }
    .lsb-cp-context-banner a {
        color: #F4C542;
        font-weight: 600;
        text-decoration: none;
        transition: color .2s;
    }
    .lsb-cp-context-banner a:hover { color: #fff; }

    /* Badge */
    .lsb-cp-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(0,201,167,0.12);
        border: 1px solid rgba(0,201,167,0.25);
        border-radius: 100px;
        padding: 6px 16px;
        margin-bottom: 24px;
    }
    .lsb-cp-hero-badge span {
        color: #00C9A7;
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-cp-hero-badge-dot {
        width: 6px; height: 6px;
        background: #00C9A7;
        border-radius: 50%;
        animation: lsb-cp-pulse 2s infinite;
        flex-shrink: 0;
    }
    @keyframes lsb-cp-pulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.4; }
    }

    /* H1 */
  .lsb-cp-h1 {
    font-family: 'Syne', sans-serif;
    font-size: clamp(2.2rem, 4.5vw, 3.8rem);
    font-weight: 800;
    color: #F5F7FA;
    line-height: 1;
    letter-spacing: -0.01em;
    margin-bottom: 28px;
}
.lsb-cp-h1 span { display: block; line-height: 1; }
.lsb-cp-h1 em { font-style: normal; color: #00C9A7; display: block; line-height: 1; margin-top: 4px; }

    /* City overview */
    .lsb-cp-hero-overview {
        font-size: 1.05rem;
        color: rgba(255,255,255,0.65) !important;
        line-height: 1.8;
        font-weight: 300;
        margin-bottom: 28px;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-cp-hero-overview p {
        font-size: 1.05rem !important;
        color: rgba(255,255,255,0.65) !important;
        line-height: 1.8 !important;
        margin-bottom: 0 !important;
    }

    /* Hero stat pills */
    .lsb-cp-hero-stats {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 4px;
    }
    .lsb-cp-hero-stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 100px;
        padding: 8px 18px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        color: rgba(255,255,255,0.75);
        white-space: nowrap;
    }
    .lsb-cp-hero-stat-pill strong {
        font-family: 'Syne', sans-serif;
        font-weight: 800;
        font-size: 1rem;
        color: #00C9A7;
    }
    .lsb-cp-hero-stat-pill-dot {
        width: 5px; height: 5px;
        background: #00C9A7;
        border-radius: 50%;
        opacity: 0.6;
        flex-shrink: 0;
    }

    /* Hero image */
    .lsb-cp-hero-image-wrap     { border-radius: 20px; overflow: hidden; }
    .lsb-cp-hero-image-wrap img { width: 100%; height: auto; display: block; border-radius: 20px; }

    /* ============================
       SECTION 2 — MAP EMBED
    ============================ */
    .lsb-cp-map-section {
        background: #F5F7FA;
        padding: 72px 40px;
    }
    .lsb-cp-map-inner {
        max-width: 1200px;
        margin: 0 auto;
    }
    .lsb-cp-map-header {
        max-width: 680px;
        margin-bottom: 36px;
    }
    .lsb-cp-map-header .lsb-cp-h2 {
        margin-bottom: 8px;
    }
    .lsb-cp-map-header p {
        font-size: 0.95rem !important;
        color: #3D4F63 !important;
        line-height: 1.7 !important;
        font-family: 'DM Sans', sans-serif;
        margin: 0 !important;
    }
    .lsb-cp-map-embed-wrap {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #E4EAF2;
        box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        line-height: 0;
        background: #e8ecf0;
    }
    .lsb-cp-map-embed-wrap iframe {
        width: 100% !important;
        height: 420px !important;
        border: none !important;
        display: block !important;
    }

    @media (max-width: 768px) {
        .lsb-cp-map-section           { padding: 52px 20px; }
        .lsb-cp-map-embed-wrap iframe  { height: 300px !important; }
    }

    /* ============================
       SECTION 3 — WHY CITY MATTERS
    ============================ */
    .lsb-cp-why-section {
        background: #0D1B2A;
        padding: 80px 40px;
        position: relative;
        overflow: hidden;
    }
    .lsb-cp-why-grid-bg {
        position: absolute; inset: 0; pointer-events: none;
        background-image:
            linear-gradient(rgba(0,201,167,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,201,167,0.03) 1px, transparent 1px);
        background-size: 60px 60px;
    }
    .lsb-cp-why-glow {
        position: absolute; pointer-events: none;
        width: 500px; height: 400px;
        background: radial-gradient(circle, rgba(0,201,167,0.08) 0%, transparent 70%);
        top: -80px; right: -60px;
    }
    .lsb-cp-why-inner {
        max-width: 1200px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: 1fr 1.6fr;
        gap: 72px;
        align-items: start;
    }
    .lsb-cp-why-left { position: sticky; top: 100px; }
    .lsb-cp-why-eyebrow {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #00C9A7;
        margin-bottom: 12px;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-cp-why-heading {
        font-family: 'Syne', sans-serif !important;
        font-size: clamp(1.8rem, 3vw, 2.6rem) !important;
        font-weight: 800 !important;
        color: #F5F7FA !important;
        letter-spacing: -0.02em !important;
        line-height: 1.1 !important;
        margin-bottom: 20px !important;
    }
    .lsb-cp-why-heading em {
        font-style: normal;
        color: #00C9A7;
    }
    .lsb-cp-why-subtext {
        font-size: 0.9rem !important;
        color: rgba(255,255,255,0.4) !important;
        line-height: 1.7 !important;
        font-family: 'DM Sans', sans-serif !important;
        font-weight: 300 !important;
        margin: 0 !important;
    }
    .lsb-cp-why-content {
        color: rgba(255,255,255,0.65) !important;
        font-family: 'DM Sans', sans-serif !important;
        font-size: 1rem !important;
        line-height: 1.85 !important;
    }
    .lsb-cp-why-content p {
        font-size: 1rem !important;
        color: rgba(255,255,255,0.65) !important;
        line-height: 1.85 !important;
        margin-bottom: 20px !important;
        font-weight: 300 !important;
    }
    .lsb-cp-why-content p:last-child  { margin-bottom: 0 !important; }
    .lsb-cp-why-content h2,
    .lsb-cp-why-content h3,
    .lsb-cp-why-content h4 {
        font-family: 'Syne', sans-serif !important;
        font-weight: 700 !important;
        color: #F5F7FA !important;
        letter-spacing: -0.01em !important;
        margin-top: 32px !important;
        margin-bottom: 12px !important;
    }
    .lsb-cp-why-content h2 { font-size: 1.4rem !important; }
    .lsb-cp-why-content h3 { font-size: 1.15rem !important; }
    .lsb-cp-why-content strong { color: #00C9A7 !important; font-weight: 600 !important; }
    .lsb-cp-why-content ul,
    .lsb-cp-why-content ol {
        padding-left: 20px !important;
        margin-bottom: 20px !important;
    }
    .lsb-cp-why-content ul li,
    .lsb-cp-why-content ol li {
        font-size: 1rem !important;
        color: rgba(255,255,255,0.65) !important;
        line-height: 1.85 !important;
        margin-bottom: 8px !important;
    }

    @media (max-width: 960px) {
        .lsb-cp-why-inner  { grid-template-columns: 1fr; gap: 36px; }
        .lsb-cp-why-left   { position: static; }
        .lsb-cp-why-section { padding: 60px 20px; }
    }

    /* ============================
       SECTION 4 — INDUSTRIES GRID
    ============================ */
    .lsb-cp-ind-section {
        background: #ffffff;
        padding: 80px 40px;
    }
    .lsb-cp-ind-inner   { max-width: 1200px; margin: 0 auto; }
    .lsb-cp-ind-header  { max-width: 680px; margin-bottom: 48px; }
    .lsb-cp-ind-intro {
        font-size: 1rem;
        color: #3D4F63 !important;
        line-height: 1.8;
        font-family: 'DM Sans', sans-serif;
        margin-top: 12px;
    }
    .lsb-cp-ind-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
    }
    .lsb-cp-ind-card {
        background: #F5F7FA;
        border: 1px solid #E4EAF2;
        border-radius: 14px;
        padding: 28px 24px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        text-decoration: none;
        transition: all .25s;
        position: relative;
        overflow: hidden;
    }
    .lsb-cp-ind-card::before {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        background: #00C9A7;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s;
    }
    .lsb-cp-ind-card:hover {
        border-color: rgba(0,201,167,0.35);
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0,201,167,0.08);
        background: #ffffff;
    }
    .lsb-cp-ind-card:hover::before { transform: scaleX(1); }
    .lsb-cp-ind-icon {
        width: 48px; height: 48px;
        background: rgba(0,201,167,0.08);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .lsb-cp-ind-icon img { width: 28px; height: 28px; object-fit: contain; }
    .lsb-cp-ind-name {
        font-family: 'Syne', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #0D1B2A !important;
        letter-spacing: -0.01em;
        line-height: 1.3;
    }
    .lsb-cp-ind-cta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #00A88C !important;
        font-family: 'DM Sans', sans-serif;
        margin-top: 4px;
        transition: gap .2s;
    }
    .lsb-cp-ind-card:hover .lsb-cp-ind-cta { gap: 10px; }

    @media (max-width: 640px) {
        .lsb-cp-ind-section { padding: 56px 20px; }
        .lsb-cp-ind-grid    { grid-template-columns: repeat(2, 1fr); }
    }

    /* ============================
       SECTION 5 — LOCAL TIPS
    ============================ */
    .lsb-cp-tips-section {
        background: #F5F7FA;
        padding: 80px 40px;
    }
    .lsb-cp-tips-inner  { max-width: 1200px; margin: 0 auto; }
    .lsb-cp-tips-header { max-width: 680px; margin-bottom: 48px; }
    .lsb-cp-tips-header .lsb-cp-h2 { margin-bottom: 12px; }
    .lsb-cp-tips-header p {
        font-size: 0.95rem !important;
        color: #3D4F63 !important;
        line-height: 1.7 !important;
        font-family: 'DM Sans', sans-serif;
        margin: 0 !important;
    }
    .lsb-cp-tips-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .lsb-cp-tip-item {
        background: #ffffff;
        border: 1px solid #E4EAF2;
        border-radius: 14px;
        padding: 28px 24px;
        display: flex;
        align-items: flex-start;
        gap: 18px;
        transition: border-color .25s, box-shadow .25s, transform .25s;
    }
    .lsb-cp-tip-item:hover {
        border-color: rgba(0,201,167,0.35);
        box-shadow: 0 8px 28px rgba(0,0,0,0.06);
        transform: translateY(-3px);
    }
    .lsb-cp-tip-icon-wrap {
        width: 48px;
        height: 48px;
        background: rgba(0,201,167,0.08);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
        line-height: 1;
    }
    .lsb-cp-tip-body { flex: 1; min-width: 0; }
    .lsb-cp-tip-title {
        font-family: 'Syne', sans-serif !important;
        font-size: 0.97rem !important;
        font-weight: 700 !important;
        color: #0D1B2A !important;
        letter-spacing: -0.01em !important;
        margin-bottom: 6px !important;
        line-height: 1.3 !important;
    }
    .lsb-cp-tip-desc {
        font-size: 0.88rem !important;
        color: #3D4F63 !important;
        line-height: 1.7 !important;
        font-family: 'DM Sans', sans-serif !important;
        font-weight: 400 !important;
        margin: 0 !important;
    }

    @media (max-width: 768px) {
        .lsb-cp-tips-section { padding: 56px 20px; }
        .lsb-cp-tips-list    { grid-template-columns: 1fr; }
    }

    /* ============================
       SECTION 6 — FEATURED BUSINESSES
    ============================ */
    .lsb-cp-fb-section {
        background: #ffffff;
        padding: 80px 40px;
    }
    .lsb-cp-fb-inner   { max-width: 1200px; margin: 0 auto; }
    .lsb-cp-fb-header  { max-width: 680px; margin-bottom: 48px; }
    .lsb-cp-fb-intro {
        font-size: 1rem;
        color: #3D4F63 !important;
        line-height: 1.8;
        font-family: 'DM Sans', sans-serif;
        margin-top: 12px;
    }
    .lsb-cp-fb-cards   { display: flex; flex-direction: column; gap: 16px; }
    .lsb-cp-fb-card {
        background: #F5F7FA;
        border: 1px solid #E4EAF2;
        border-radius: 16px;
        padding: 28px 32px;
        display: flex;
        align-items: center;
        gap: 24px;
        transition: border-color .25s, box-shadow .25s;
    }
    .lsb-cp-fb-card:hover {
        border-color: rgba(0,201,167,0.4) !important;
        box-shadow: 0 8px 32px rgba(0,0,0,0.07) !important;
    }
    .lsb-cp-fb-logo {
        width: 72px; height: 72px;
        border-radius: 14px;
        overflow: hidden;
        flex-shrink: 0;
        background: #0D1B2A;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .lsb-cp-fb-logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .lsb-cp-fb-logo--initials {
        font-family: 'Syne', sans-serif;
        font-weight: 800;
        font-size: 1.2rem;
        color: #00C9A7;
        letter-spacing: -0.01em;
    }
    .lsb-cp-fb-body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .lsb-cp-fb-meta-row {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }
    .lsb-cp-fb-name {
        font-family: 'Syne', sans-serif !important;
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        color: #0D1B2A !important;
        letter-spacing: -0.01em !important;
        margin: 0 !important;
    }
    .lsb-cp-fb-industry-tag {
        display: inline-block;
        background: rgba(0,201,167,0.08);
        border: 1px solid rgba(0,201,167,0.2);
        color: #00A88C;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 100px;
        font-family: 'DM Sans', sans-serif;
        white-space: nowrap;
    }
    .lsb-cp-fb-rating  { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
    .lsb-cp-fb-stars   { display: flex; gap: 1px; line-height: 1; }
    .lsb-cp-fb-star--full,
    .lsb-cp-fb-star--half  { color: #F4C542; font-size: 1rem; }
    .lsb-cp-fb-star--empty { color: #C8D4E0; font-size: 1rem; }
    .lsb-cp-fb-rating-num {
        font-family: 'Syne', sans-serif;
        font-size: 0.9rem;
        font-weight: 700;
        color: #0D1B2A !important;
    }
    .lsb-cp-fb-desc {
        font-size: 0.9rem !important;
        color: #3D4F63 !important;
        line-height: 1.6 !important;
        font-family: 'DM Sans', sans-serif;
        margin: 0 !important;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .lsb-cp-fb-location {
        font-size: 0.82rem !important;
        color: #3D4F63 !important;
        font-family: 'DM Sans', sans-serif;
        margin: 0 !important;
    }
    .lsb-cp-fb-cta { flex-shrink: 0; }
    .lsb-cp-fb-btn {
        display: inline-block;
        background: #0D1B2A !important;
        color: #ffffff !important;
        font-family: 'Syne', sans-serif !important;
        font-weight: 700 !important;
        font-size: 0.85rem !important;
        padding: 12px 24px !important;
        border-radius: 8px !important;
        text-decoration: none !important;
        letter-spacing: 0.02em !important;
        transition: background .2s, color .2s !important;
        white-space: nowrap;
    }
    .lsb-cp-fb-btn:hover { background: #00C9A7 !important; color: #0D1B2A !important; }

    @media (max-width: 768px) {
        .lsb-cp-fb-section { padding: 56px 20px; }
        .lsb-cp-fb-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
            padding: 24px;
        }
        .lsb-cp-fb-meta-row { flex-direction: column; align-items: flex-start; gap: 8px; }
        .lsb-cp-fb-cta { width: 100%; }
        .lsb-cp-fb-btn { width: 100% !important; text-align: center !important; }
    }

    /* ============================
       SECTION 7 — FAQ
    ============================ */
    .lsb-cp-faq-section { background: #F5F7FA; padding: 80px 40px; }
    .lsb-cp-faq-inner   { max-width: 780px; margin: 0 auto; }
    .lsb-cp-faq-header  { margin-bottom: 48px; }
    .lsb-cp-faq-list    { display: flex; flex-direction: column; gap: 12px; }
    .lsb-cp-faq-item {
        background: #ffffff;
        border: 1px solid #E4EAF2;
        border-radius: 14px;
        overflow: hidden;
        transition: border-color .25s;
    }
    .lsb-cp-faq-item.is-open { border-color: rgba(0,201,167,0.35); }
    .lsb-cp-faq-trigger {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 24px;
        background: transparent;
        border: none;
        cursor: pointer;
        text-align: left;
        transition: background .2s;
    }
    .lsb-cp-faq-trigger:hover              { background: #EEF2F7; }
    .lsb-cp-faq-item.is-open .lsb-cp-faq-trigger { background: #EEF2F7; }
    .lsb-cp-faq-question {
        font-family: 'Syne', sans-serif !important;
        font-size: 0.97rem !important;
        font-weight: 700 !important;
        color: #0D1B2A !important;
        letter-spacing: -0.01em !important;
        line-height: 1.4 !important;
        margin: 0 !important;
    }
    .lsb-cp-faq-icon {
        width: 30px; height: 30px;
        background: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: #00C9A7;
        font-size: 1.1rem;
        line-height: 1;
        transition: background .2s, transform .25s;
        border: 1px solid #E4EAF2;
    }
    .lsb-cp-faq-item.is-open .lsb-cp-faq-icon {
        transform: rotate(45deg);
        background: rgba(0,201,167,0.1);
        border-color: rgba(0,201,167,0.3);
    }
    .lsb-cp-faq-body   { display: none; padding: 0 24px 24px; }
    .lsb-cp-faq-item.is-open .lsb-cp-faq-body { display: block; }
    .lsb-cp-faq-answer {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.92rem !important;
        color: #3D4F63 !important;
        line-height: 1.8 !important;
        padding-top: 4px;
        padding-bottom: 18px;
        border-bottom: 1px solid #E4EAF2;
        margin-bottom: 16px;
    }
    .lsb-cp-faq-answer p  { font-size: 0.92rem !important; color: #3D4F63 !important; line-height: 1.8 !important; margin-bottom: 10px !important; }
    .lsb-cp-faq-answer p:last-child  { margin-bottom: 0 !important; }
    .lsb-cp-faq-answer strong        { color: #0D1B2A !important; font-weight: 600 !important; }
    .lsb-cp-faq-answer ul            { padding-left: 18px; margin-top: 6px; }
    .lsb-cp-faq-answer ul li         { font-size: 0.92rem !important; color: #3D4F63 !important; line-height: 1.8 !important; margin-bottom: 4px !important; }
    .lsb-cp-faq-cta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .lsb-cp-faq-learn-more {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: 'Syne', sans-serif !important;
        font-size: 0.82rem !important;
        font-weight: 700 !important;
        color: #00A88C !important;
        text-decoration: none !important;
        letter-spacing: 0.02em !important;
        transition: gap .2s, color .2s !important;
    }
    .lsb-cp-faq-learn-more::after { content: '→'; color: #00C9A7; font-size: 0.85rem; transition: transform .2s; }
    .lsb-cp-faq-learn-more:hover  { color: #0D1B2A !important; gap: 10px; }
    .lsb-cp-faq-learn-more:hover::after { transform: translateX(3px); }
    .lsb-cp-faq-disclaimer {
        font-size: 0.75rem !important;
        color: #8A9BB0 !important;
        line-height: 1.6 !important;
        font-family: 'DM Sans', sans-serif !important;
        font-style: italic !important;
        font-weight: 400 !important;
        margin: 0 !important;
        flex: 1;
        text-align: right;
    }

    @media (max-width: 768px) {
        .lsb-cp-faq-section  { padding: 56px 20px; }
        .lsb-cp-faq-cta-row  { flex-direction: column; align-items: flex-start; gap: 10px; }
        .lsb-cp-faq-disclaimer { text-align: left; }
    }

    /* ============================
       SECTION 8 — BLOG POSTS
    ============================ */
    .lsb-cp-blog-section {
        background: #ffffff;
        padding: 80px 40px;
    }
    .lsb-cp-blog-inner  { max-width: 1200px; margin: 0 auto; }
    .lsb-cp-blog-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 48px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .lsb-cp-blog-header-left { max-width: 560px; }
    .lsb-cp-blog-view-all {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: 'Syne', sans-serif;
        font-size: 0.88rem;
        font-weight: 700;
        color: #00A88C !important;
        text-decoration: none !important;
        letter-spacing: 0.02em;
        transition: gap .2s;
        white-space: nowrap;
    }
    .lsb-cp-blog-view-all::after { content: '→'; color: #00C9A7; }
    .lsb-cp-blog-view-all:hover  { gap: 10px; }
    .lsb-cp-blog-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }
    .lsb-cp-blog-card {
        background: #F5F7FA;
        border: 1px solid #E4EAF2;
        border-radius: 16px;
        overflow: hidden;
        text-decoration: none !important;
        transition: border-color .25s, box-shadow .25s, transform .25s;
        display: flex;
        flex-direction: column;
    }
    .lsb-cp-blog-card:hover {
        border-color: rgba(0,201,167,0.35);
        box-shadow: 0 12px 36px rgba(0,0,0,0.07);
        transform: translateY(-4px);
    }
    .lsb-cp-blog-thumb {
        width: 100%;
        aspect-ratio: 16/9;
        overflow: hidden;
        background: #0D1B2A;
        position: relative;
    }
    .lsb-cp-blog-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .4s;
    }
    .lsb-cp-blog-card:hover .lsb-cp-blog-thumb img { transform: scale(1.04); }
    .lsb-cp-blog-thumb-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0D1B2A 0%, #1B2F45 100%);
        font-size: 2.5rem;
        opacity: 0.4;
    }
    .lsb-cp-blog-card-body {
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
    }
    .lsb-cp-blog-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .lsb-cp-blog-category {
        display: inline-block;
        background: rgba(0,201,167,0.08);
        border: 1px solid rgba(0,201,167,0.18);
        color: #00A88C;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 100px;
        font-family: 'DM Sans', sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .lsb-cp-blog-date {
        font-size: 0.78rem;
        color: #8A9BB0;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-cp-blog-title {
        font-family: 'Syne', sans-serif !important;
        font-size: 1rem !important;
        font-weight: 700 !important;
        color: #0D1B2A !important;
        letter-spacing: -0.01em !important;
        line-height: 1.35 !important;
        margin: 0 !important;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .lsb-cp-blog-excerpt {
        font-size: 0.87rem !important;
        color: #3D4F63 !important;
        line-height: 1.65 !important;
        font-family: 'DM Sans', sans-serif !important;
        font-weight: 400 !important;
        margin: 0 !important;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }
    .lsb-cp-blog-read-more {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: 'Syne', sans-serif;
        font-size: 0.82rem;
        font-weight: 700;
        color: #00A88C !important;
        letter-spacing: 0.02em;
        margin-top: 4px;
        transition: gap .2s;
    }
    .lsb-cp-blog-read-more::after { content: '→'; color: #00C9A7; font-size: 0.85rem; }
    .lsb-cp-blog-card:hover .lsb-cp-blog-read-more { gap: 10px; }

    @media (max-width: 960px) {
        .lsb-cp-blog-grid    { grid-template-columns: repeat(2, 1fr); }
        .lsb-cp-blog-section { padding: 60px 20px; }
    }
    @media (max-width: 640px) {
        .lsb-cp-blog-grid { grid-template-columns: 1fr; }
        .lsb-cp-blog-header { flex-direction: column; align-items: flex-start; }
    }

    /* ============================
       SECTION 9 — WEATHER
    ============================ */

    .lsb-cp-weather-section { background: #ffffff; padding: 80px 40px; }
    .lsb-cp-weather-inner   { max-width: 1200px; margin: 0 auto; }
    .lsb-cp-weather-header  { max-width: 680px; margin-bottom: 40px; }
    .lsb-cp-weather-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 24px;
        align-items: start;
    }
    .lsb-cp-weather-current {
        background: linear-gradient(135deg, #0D1B2A 0%, #1B2F45 100%);
        border-radius: 20px;
        padding: 32px 28px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        position: relative;
        overflow: hidden;
    }
    .lsb-cp-weather-current::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 200px; height: 200px;
        background: radial-gradient(circle, rgba(0,201,167,0.12) 0%, transparent 70%);
        pointer-events: none;
    }
    .lsb-cp-weather-icon-wrap { display: flex; align-items: center; gap: 14px; }
    .lsb-cp-weather-icon      { font-size: 3rem; line-height: 1; }
    .lsb-cp-weather-temp {
        font-family: 'Syne', sans-serif;
        font-size: 3.2rem;
        font-weight: 800;
        color: #F5F7FA;
        letter-spacing: -0.04em;
        line-height: 1;
    }
    .lsb-cp-weather-temp sup    { font-size: 1.4rem; font-weight: 600; vertical-align: super; }
    .lsb-cp-weather-condition   { font-family: 'DM Sans', sans-serif; font-size: 1rem; font-weight: 500; color: #00C9A7; text-transform: capitalize; }
    .lsb-cp-weather-city-label  { font-family: 'DM Sans', sans-serif; font-size: 0.78rem; color: rgba(255,255,255,0.35); letter-spacing: 0.08em; text-transform: uppercase; margin-top: -6px; }
    .lsb-cp-weather-details     { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.08); }
    .lsb-cp-weather-detail-item { display: flex; flex-direction: column; gap: 3px; }
    .lsb-cp-weather-detail-label { font-family: 'DM Sans', sans-serif; font-size: 0.7rem; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.08em; }
    .lsb-cp-weather-detail-val  { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; color: #F5F7FA; }
    .lsb-cp-weather-updated     { font-family: 'DM Sans', sans-serif; font-size: 0.7rem; color: rgba(255,255,255,0.2); margin-top: 4px; }
    .lsb-cp-weather-forecast    { display: flex; flex-direction: column; gap: 10px; align-self: stretch; }
    .lsb-cp-weather-forecast-label { font-family: 'DM Sans', sans-serif; font-size: 0.72rem; font-weight: 600; color: #8A9BB0; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; }
    .lsb-cp-forecast-row {
        display: grid;
        grid-template-columns: 80px 1fr auto auto;
        align-items: center;
        gap: 16px;
        background: #F5F7FA;
        border: 1px solid #E4EAF2;
        border-radius: 12px;
        padding: 14px 20px;
        transition: border-color .2s;
    }
    .lsb-cp-forecast-row:hover  { border-color: rgba(0,201,167,0.3); }
    .lsb-cp-forecast-day        { font-family: 'Syne', sans-serif; font-size: 0.88rem; font-weight: 700; color: #0D1B2A; }
    .lsb-cp-forecast-desc       { font-family: 'DM Sans', sans-serif; font-size: 0.82rem; color: #8A9BB0; text-transform: capitalize; }
    .lsb-cp-forecast-icon       { font-size: 1.3rem; }
    .lsb-cp-forecast-temps      { font-family: 'Syne', sans-serif; font-size: 0.88rem; font-weight: 700; color: #0D1B2A; text-align: right; white-space: nowrap; }
    .lsb-cp-forecast-temps span { font-weight: 400; color: #8A9BB0; margin-left: 6px; }
    .lsb-cp-weather-error       { background: #F5F7FA; border: 1px solid #E4EAF2; border-radius: 14px; padding: 28px 24px; font-family: 'DM Sans', sans-serif; font-size: 0.9rem; color: #8A9BB0; font-style: italic; }
    @media (max-width: 900px) { .lsb-cp-weather-grid { grid-template-columns: 1fr; } }
    @media (max-width: 640px) {
        .lsb-cp-weather-section { padding: 56px 20px; }
        .lsb-cp-forecast-row    { grid-template-columns: 70px 1fr auto; }
        .lsb-cp-forecast-icon   { display: none; }
    }

    /* ============================
       SECTION 10 — NEWS / RSS
    ============================ */

    .lsb-cp-news-section { background: #F5F7FA; padding: 80px 40px; }
    .lsb-cp-news-inner   { max-width: 1200px; margin: 0 auto; }
    .lsb-cp-news-header  { max-width: 680px; margin-bottom: 48px; }
    .lsb-cp-news-tabs    { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 32px; }
    .lsb-cp-news-tab {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #ffffff;
        border: 1px solid #E4EAF2;
        border-radius: 100px;
        padding: 8px 18px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        font-weight: 500;
        color: #3D4F63;
        cursor: pointer;
        transition: all .2s;
        white-space: nowrap;
    }
    .lsb-cp-news-tab:hover,
    .lsb-cp-news-tab.is-active   { background: #0D1B2A; border-color: #0D1B2A; color: #ffffff; }
    .lsb-cp-news-tab-icon        { font-size: 1rem; }
    .lsb-cp-news-panel           { display: none; }
    .lsb-cp-news-panel.is-active { display: block; }
    .lsb-cp-news-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    .lsb-cp-news-card {
        background: #ffffff;
        border: 1px solid #E4EAF2;
        border-radius: 14px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        text-decoration: none;
        transition: all .25s;
        position: relative;
        overflow: hidden;
    }
    .lsb-cp-news-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: #00C9A7;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s;
    }
    .lsb-cp-news-card:hover                { border-color: rgba(0,201,167,0.3); box-shadow: 0 8px 24px rgba(0,0,0,0.05); transform: translateY(-3px); }
    .lsb-cp-news-card:hover::before        { transform: scaleX(1); }
    .lsb-cp-news-source  { font-family: 'DM Sans', sans-serif; font-size: 0.72rem; font-weight: 600; color: #00A88C; text-transform: uppercase; letter-spacing: 0.08em; }
    .lsb-cp-news-title {
        font-family: 'Syne', sans-serif;
        font-size: 0.97rem;
        font-weight: 700;
        color: #0D1B2A;
        line-height: 1.4;
        letter-spacing: -0.01em;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .lsb-cp-news-date  { font-family: 'DM Sans', sans-serif; font-size: 0.78rem; color: #8A9BB0; margin-top: auto; }
    .lsb-cp-news-read  { display: inline-flex; align-items: center; gap: 5px; font-family: 'DM Sans', sans-serif; font-size: 0.8rem; font-weight: 600; color: #00A88C; margin-top: 4px; transition: gap .2s; }
    .lsb-cp-news-card:hover .lsb-cp-news-read { gap: 9px; }
    .lsb-cp-news-empty { font-family: 'DM Sans', sans-serif; font-size: 0.9rem; color: #8A9BB0; font-style: italic; padding: 20px 0; }
    .lsb-cp-news-credit { margin-top: 20px; font-family: 'DM Sans', sans-serif; font-size: 0.72rem; color: #8A9BB0; display: flex; align-items: center; gap: 6px; }
    @media (max-width: 768px) {
        .lsb-cp-news-section { padding: 56px 20px; }
        .lsb-cp-news-grid    { grid-template-columns: 1fr; }
    }

    /* ============================
       SECTION 11 — FINAL CTA
    ============================ */

    .lsb-cp-cta-section {
        background: #0D1B2A;
        padding: 100px 40px;
        position: relative;
        overflow: hidden;
    }
    .lsb-cp-cta-grid-bg {
        position: absolute; inset: 0; pointer-events: none;
        background-image:
            linear-gradient(rgba(0,201,167,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,201,167,0.04) 1px, transparent 1px);
        background-size: 60px 60px;
    }
    .lsb-cp-cta-glow-1 {
        position: absolute; pointer-events: none;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(0,201,167,0.10) 0%, transparent 70%);
        top: -120px; right: -80px;
    }
    .lsb-cp-cta-glow-2 {
        position: absolute; pointer-events: none;
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(244,197,66,0.06) 0%, transparent 70%);
        bottom: -80px; left: -60px;
    }
    .lsb-cp-cta-inner {
        max-width: 680px;
        margin: 0 auto;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    .lsb-cp-cta-heading {
        font-family: 'Syne', sans-serif !important;
        font-size: clamp(2rem, 4vw, 3rem) !important;
        font-weight: 800 !important;
        color: #F5F7FA !important;
        letter-spacing: -0.02em !important;
        line-height: 1.1 !important;
        margin-bottom: 20px !important;
    }
    .lsb-cp-cta-heading em { font-style: normal; color: #00C9A7; }
    .lsb-cp-cta-text {
        font-size: 1rem !important;
        color: rgba(255,255,255,0.55) !important;
        line-height: 1.8 !important;
        font-family: 'DM Sans', sans-serif !important;
        font-weight: 300 !important;
        margin-bottom: 40px !important;
    }
    .lsb-cp-cta-text p { font-size: 1rem !important; color: rgba(255,255,255,0.55) !important; line-height: 1.8 !important; margin-bottom: 0 !important; }
    .lsb-cp-cta-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #00C9A7 !important;
        color: #0D1B2A !important;
        font-family: 'Syne', sans-serif !important;
        font-weight: 700 !important;
        font-size: 0.95rem !important;
        padding: 16px 36px !important;
        border-radius: 8px !important;
        text-decoration: none !important;
        letter-spacing: 0.02em !important;
        transition: background .2s, transform .15s !important;
    }
    .lsb-cp-cta-btn::after { content: '→'; font-size: 1rem; transition: transform .2s; }
    .lsb-cp-cta-btn:hover  { background: #00A88C !important; transform: translateY(-2px); }
    .lsb-cp-cta-btn:hover::after { transform: translateX(4px); }

    @media (max-width: 768px) {
        .lsb-cp-cta-section { padding: 72px 20px; }
        .lsb-cp-cta-btn     { width: 100%; justify-content: center; }
    }

    /* ============================
       HERO RESPONSIVE
    ============================ */
    @media (max-width: 1024px) {
        .lsb-cp-hero-columns    { grid-template-columns: 1fr; gap: 40px; }
        .lsb-cp-hero-image-wrap { max-width: 560px; }
    }
    @media (max-width: 768px) {
        .lsb-cp-breadcrumb-bar { padding: 0 20px; }
        .lsb-cp-hero           { padding: 40px 20px 52px; }
        .lsb-cp-h1             { font-size: 2.8rem; }
        .lsb-cp-hero-stats     { gap: 8px; }
    }

    </style>

    <!-- ============================================================
         BREADCRUMB
    ============================================================ -->
    <nav class="lsb-cp-breadcrumb-bar" aria-label="Breadcrumb">
        <ol class="lsb-cp-breadcrumb">
            <li><a href="<?php echo esc_url( $home_url ); ?>">Home</a></li>
            <li class="lsb-cp-breadcrumb-sep" aria-hidden="true">›</li>
            <li><a href="<?php echo esc_url( $cities_url ); ?>">Cities</a></li>
            <?php if ( $is_industry_filtered ) : ?>
            <li class="lsb-cp-breadcrumb-sep" aria-hidden="true">›</li>
            <li>
                <a href="<?php echo esc_url( home_url( '/industries/' . $filter_industry_slug . '/' ) ); ?>">
                    <?php echo esc_html( $filter_industry_term->name ); ?>
                </a>
            </li>
            <?php endif; ?>
            <li class="lsb-cp-breadcrumb-sep" aria-hidden="true">›</li>
            <li class="lsb-cp-breadcrumb-current" aria-current="page">
                <?php echo $is_industry_filtered
                    ? esc_html( $filter_industry_term->name . ' in ' . $city_name )
                    : esc_html( $city_name );
                ?>
            </li>
        </ol>
    </nav>

    <!-- ============================================================
         SECTION 1 — HERO
    ============================================================ -->
    <section class="lsb-cp-hero lsb-cp-dark-bg"
             aria-labelledby="lsb-cp-heading-<?php echo esc_attr( $city_slug ); ?>">

        <div class="lsb-cp-hero-grid-bg" aria-hidden="true"></div>
        <div class="lsb-cp-hero-glow-1"  aria-hidden="true"></div>
        <div class="lsb-cp-hero-glow-2"  aria-hidden="true"></div>

        <div class="lsb-cp-hero-inner">
            <div class="lsb-cp-hero-columns<?php echo $hero_image ? '' : ' no-image'; ?>">

                <div class="lsb-cp-hero-left">

                    <?php if ( $is_industry_filtered ) : ?>
                    <div class="lsb-cp-context-banner" role="note">
                        <span>←</span>
                        Viewing
                        <a href="<?php echo esc_url( home_url( '/industries/' . $filter_industry_slug . '/' ) ); ?>">
                            <?php echo esc_html( $filter_industry_term->name ); ?>
                        </a>
                        services in <?php echo esc_html( $city_name ); ?>
                        &nbsp;·&nbsp;
                        <a href="<?php echo esc_url( home_url( '/cities/' . $city_slug . '/' ) ); ?>">
                            View all services
                        </a>
                    </div>
                    <?php endif; ?>

                    <div class="lsb-cp-hero-badge">
                        <span class="lsb-cp-hero-badge-dot" aria-hidden="true"></span>
                        <span><?php echo esc_html( $hero_badge_label ); ?></span>
                    </div>

                    <h1 class="lsb-cp-h1"
    id="lsb-cp-heading-<?php echo esc_attr( $city_slug ); ?>">
    <span><?php echo esc_html( $hero_title_main ); ?></span>
    <em><?php echo esc_html( $hero_title_em ); ?></em>
</h1>

                    <?php if ( $hero_intro ) : ?>
                    <div class="lsb-cp-hero-overview">
                        <p><?php echo esc_html( $hero_intro ); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Industry + Service count pills -->
                    <div class="lsb-cp-hero-stats">
                        <?php if ( $industry_count > 0 ) : ?>
                        <div class="lsb-cp-hero-stat-pill">
                            <span class="lsb-cp-hero-stat-pill-dot" aria-hidden="true"></span>
                            <strong><?php echo esc_html( $industry_count ); ?></strong>
                            <?php echo $industry_count === 1 ? 'Industry' : 'Industries'; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ( $service_count > 0 ) : ?>
                        <div class="lsb-cp-hero-stat-pill">
                            <span class="lsb-cp-hero-stat-pill-dot" aria-hidden="true"></span>
                            <strong><?php echo esc_html( $service_count ); ?></strong>
                            <?php echo $service_count === 1 ? 'Service' : 'Services'; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>

                <?php if ( $hero_image ) : ?>
                <div class="lsb-cp-hero-image-wrap">
                    <img src="<?php echo esc_url( $hero_image ); ?>"
                         alt="<?php echo esc_attr( $city_name ); ?> local services"
                         loading="eager">
                </div>
                <?php endif; ?>

            </div>
        </div>
    </section>

    <!-- ============================================================
         SECTION 2 — WEATHER
    ============================================================ -->

    <?php
    $has_weather = ! empty( $weather_data ) && isset( $weather_data['main'] );
    ?>
    <section class="lsb-cp-weather-section lsb-cp-light-bg" id="lsb-cp-weather">
        <div class="lsb-cp-weather-inner">

            <div class="lsb-cp-weather-header">
                <span class="lsb-cp-section-label">Current Conditions</span>
                <h2 class="lsb-cp-h2"><?php echo esc_html( $city_name ); ?> Weather</h2>
            </div>

            <?php if ( $has_weather ) :
                $temp       = round( $weather_data['main']['temp'] );
                $feels_like = round( $weather_data['main']['feels_like'] );
                $humidity   = $weather_data['main']['humidity'];
                $wind_mph   = round( $weather_data['wind']['speed'] );
                $condition  = $weather_data['weather'][0]['description'] ?? '';
                $icon_code  = $weather_data['weather'][0]['icon'] ?? '01d';
                $emoji      = $weather_emoji( $icon_code );
                $updated    = date( 'g:i A' );
            ?>
            <div class="lsb-cp-weather-grid">

                <!-- Current conditions card -->
                <div class="lsb-cp-weather-current">

                    <div class="lsb-cp-weather-icon-wrap">
                        <span class="lsb-cp-weather-icon"><?php echo $emoji; ?></span>
                        <div>
                            <div class="lsb-cp-weather-temp">
                                <?php echo esc_html( $temp ); ?><sup>°F</sup>
                            </div>
                        </div>
                    </div>

                    <div class="lsb-cp-weather-condition"><?php echo esc_html( $condition ); ?></div>
                    <div class="lsb-cp-weather-city-label"><?php echo esc_html( $city_name ); ?>, CA</div>

                    <div class="lsb-cp-weather-details">
                        <div class="lsb-cp-weather-detail-item">
                            <span class="lsb-cp-weather-detail-label">Feels Like</span>
                            <span class="lsb-cp-weather-detail-val"><?php echo esc_html( $feels_like ); ?>°</span>
                        </div>
                        <div class="lsb-cp-weather-detail-item">
                            <span class="lsb-cp-weather-detail-label">Humidity</span>
                            <span class="lsb-cp-weather-detail-val"><?php echo esc_html( $humidity ); ?>%</span>
                        </div>
                        <div class="lsb-cp-weather-detail-item">
                            <span class="lsb-cp-weather-detail-label">Wind</span>
                            <span class="lsb-cp-weather-detail-val"><?php echo esc_html( $wind_mph ); ?> mph</span>
                        </div>
                        <div class="lsb-cp-weather-detail-item">
                            <span class="lsb-cp-weather-detail-label">Visibility</span>
                            <span class="lsb-cp-weather-detail-val">
                                <?php
                                $vis_m = $weather_data['visibility'] ?? 0;
                                echo esc_html( $vis_m ? round( $vis_m / 1609.34, 1 ) . ' mi' : 'N/A' );
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="lsb-cp-weather-updated">Updated at <?php echo esc_html( $updated ); ?></div>

                </div>

                <!-- Forecast strip -->
                <div class="lsb-cp-weather-forecast">
                    <div class="lsb-cp-weather-forecast-label">4-Day Forecast</div>

                    <?php if ( ! empty( $forecast_days ) ) :
                        foreach ( $forecast_days as $fc ) :
                            $fc_day   = date( 'l', $fc['dt'] );
                            $fc_hi    = round( $fc['main']['temp_max'] );
                            $fc_lo    = round( $fc['main']['temp_min'] );
                            $fc_desc  = $fc['weather'][0]['description'] ?? '';
                            $fc_icon  = $fc['weather'][0]['icon'] ?? '01d';
                            $fc_emoji = $weather_emoji( $fc_icon );
                    ?>
                    <div class="lsb-cp-forecast-row">
                        <span class="lsb-cp-forecast-day"><?php echo esc_html( $fc_day ); ?></span>
                        <span class="lsb-cp-forecast-desc"><?php echo esc_html( $fc_desc ); ?></span>
                        <span class="lsb-cp-forecast-icon"><?php echo $fc_emoji; ?></span>
                        <span class="lsb-cp-forecast-temps">
                            <?php echo esc_html( $fc_hi ); ?>°
                            <span><?php echo esc_html( $fc_lo ); ?>°</span>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php else : ?>
                    <p class="lsb-cp-weather-error">Forecast unavailable.</p>
                    <?php endif; ?>
                </div>

            </div>
            <?php else : ?>
            <p class="lsb-cp-weather-error">Weather data is currently unavailable for <?php echo esc_html( $city_name ); ?>. Check back shortly.</p>
            <?php endif; ?>

        </div>
    </section>

    <!-- ============================================================
         SECTION 3 — MAP EMBED
    ============================================================ -->
    <?php if ( $map_embed ) : ?>
    <section class="lsb-cp-map-section lsb-cp-light-bg" id="lsb-cp-map">
        <div class="lsb-cp-map-inner">
            <div class="lsb-cp-map-embed-wrap">
                <?php echo $map_embed; // Already an iframe — do not escape ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         SECTION 3 — WHY CITY MATTERS
    ============================================================ -->
    <?php if ( $why_content ) : ?>
    <section class="lsb-cp-why-section lsb-cp-dark-bg" id="lsb-cp-why-matters">
        <div class="lsb-cp-why-grid-bg" aria-hidden="true"></div>
        <div class="lsb-cp-why-glow"    aria-hidden="true"></div>

        <div class="lsb-cp-why-inner">

            <div class="lsb-cp-why-left">
                <span class="lsb-cp-why-eyebrow">Local Insight</span>
                <h2 class="lsb-cp-why-heading">
                    Why <em><?php echo esc_html( $city_name ); ?></em> Matters
                </h2>
                <p class="lsb-cp-why-subtext">
                    Understanding your city helps you find the right professionals for your specific needs and local conditions.
                </p>
            </div>

            <div class="lsb-cp-why-content">
                <?php echo wp_kses_post( $why_content ); ?>
            </div>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         SECTION 4 — INDUSTRIES GRID (hidden when industry filtered)
    ============================================================ -->

    <?php if ( ! $is_industry_filtered && ! empty( $industry_terms ) ) : ?>

    <script type="application/ld+json">
    <?php echo wp_json_encode( [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => 'Industries Available in ' . $city_name,
        'numberOfItems'   => count( $industry_terms ),
        'itemListElement' => array_map( function( $term, $i ) use ( $city_slug ) {
            return [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'item'     => [
                    '@type' => 'Service',
                    'name'  => $term->name,
                   'url'   => home_url( '/cities/' . $city_slug . '/' . $term->slug . '/' ),
                ],
            ];
        }, $industry_terms, array_keys( $industry_terms ) ),
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
    </script>

    <section class="lsb-cp-ind-section lsb-cp-light-bg" id="lsb-cp-industries">
        <div class="lsb-cp-ind-inner">

            <div class="lsb-cp-ind-header">
                <span class="lsb-cp-section-label">What We Offer</span>
                <h2 class="lsb-cp-h2">Industries Serving <?php echo esc_html( $city_name ); ?></h2>
                <?php if ( $industries_intro ) : ?>
                <p class="lsb-cp-ind-intro"><?php echo wp_kses_post( $industries_intro ); ?></p>
                <?php endif; ?>
            </div>

            <div class="lsb-cp-ind-grid">
                <?php foreach ( $industry_terms as $ind_term ) :
                   $ind_url  = home_url( '/cities/' . $city_slug . '/' . $ind_term->slug . '/' );
                    $ind_icon = lsb_get_industry_icon( $ind_term->slug );
                ?>
                <a href="<?php echo esc_url( $ind_url ); ?>" class="lsb-cp-ind-card">

                    <div class="lsb-cp-ind-icon">
                        <?php echo esc_html( $ind_icon ); ?>
                    </div>

                    <div class="lsb-cp-ind-name"><?php echo esc_html( $ind_term->name ); ?></div>

                    <span class="lsb-cp-ind-cta">View Services →</span>

                </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <?php endif; // end industries grid ?>

    <!-- ============================================================
         SECTION 5 — LOCAL TIPS
    ============================================================ -->
    <?php if ( ! empty( $local_tips ) && is_array( $local_tips ) ) : ?>
    <section class="lsb-cp-tips-section lsb-cp-light-bg" id="lsb-cp-local-tips">
        <div class="lsb-cp-tips-inner">

            <div class="lsb-cp-tips-header">
                <span class="lsb-cp-section-label">Insider Knowledge</span>
                <h2 class="lsb-cp-h2">Local Tips for <?php echo esc_html( $city_name ); ?></h2>
                <p>Helpful advice from locals and service professionals working in <?php echo esc_html( $city_name ); ?>.</p>
            </div>

            <ul class="lsb-cp-tips-list">
                <?php foreach ( $local_tips as $idx => $tip ) :
                    $tip_icon  = lsb_resolve_tip_icon( $tip['tip_icon'] ?? '', $idx );
                    $tip_title = ! empty( $tip['tip_title'] ) ? $tip['tip_title'] : '';
                    $tip_desc  = ! empty( $tip['tip_description'] ) ? $tip['tip_description'] : '';
                    if ( ! $tip_title && ! $tip_desc ) continue;
                ?>
                <li class="lsb-cp-tip-item">
                    <div class="lsb-cp-tip-icon-wrap" aria-hidden="true">
                        <?php echo esc_html( $tip_icon ); ?>
                    </div>
                    <div class="lsb-cp-tip-body">
                        <?php if ( $tip_title ) : ?>
                        <h3 class="lsb-cp-tip-title"><?php echo esc_html( $tip_title ); ?></h3>
                        <?php endif; ?>
                        <?php if ( $tip_desc ) : ?>
                        <p class="lsb-cp-tip-desc"><?php echo wp_kses_post( $tip_desc ); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         SECTION 6 — FEATURED BUSINESSES
    ============================================================ -->

    <?php if ( ! empty( $fb_posts ) ) :

        $fb_schema_items = [];
        foreach ( $fb_posts as $pos => $biz ) {
            $biz_rating = function_exists( 'get_field' ) ? get_field( 'business_rating', $biz->ID ) : '';
            $biz_phone  = function_exists( 'get_field' ) ? get_field( 'business_phone',  $biz->ID ) : '';
            $biz_logo   = function_exists( 'get_field' ) ? get_field( 'business_logo',   $biz->ID ) : '';
            $logo_url   = '';
            if ( $biz_logo ) {
                $logo_url = is_array( $biz_logo ) ? ( $biz_logo['url'] ?? '' ) : wp_get_attachment_image_url( $biz_logo, 'thumbnail' );
            }
            $schema_entry = [
                '@type'    => 'ListItem',
                'position' => $pos + 1,
                'item'     => array_filter( [
                    '@type'     => 'LocalBusiness',
                    'name'      => $biz->post_title,
                    'url'       => get_permalink( $biz->ID ),
                    'telephone' => $biz_phone  ?: null,
                    'image'     => $logo_url   ?: null,
                    'aggregateRating' => $biz_rating ? [
                        '@type'       => 'AggregateRating',
                        'ratingValue' => $biz_rating,
                        'bestRating'  => '5',
                    ] : null,
                ] ),
            ];
            $fb_schema_items[] = $schema_entry;
        }
        $fb_section_title = $is_industry_filtered
            ? 'Featured ' . $filter_industry_term->name . ' Businesses in ' . $city_name
            : 'Featured Businesses in ' . $city_name;
    ?>

    <script type="application/ld+json">
    <?php echo wp_json_encode( [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => $fb_section_title,
        'itemListElement' => $fb_schema_items,
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
    </script>

    <section class="lsb-cp-fb-section lsb-cp-light-bg" id="lsb-cp-featured-businesses">
        <div class="lsb-cp-fb-inner">

            <div class="lsb-cp-fb-header">
                <span class="lsb-cp-section-label">
                    <?php echo $is_industry_filtered ? 'Filtered by ' . esc_html( $filter_industry_term->name ) : 'Featured Professionals'; ?>
                </span>
                <h2 class="lsb-cp-h2"><?php echo esc_html( $fb_section_title ); ?></h2>
                <?php if ( $featured_intro ) : ?>
                <p class="lsb-cp-fb-intro"><?php echo wp_kses_post( $featured_intro ); ?></p>
                <?php endif; ?>
            </div>

            <div class="lsb-cp-fb-cards">
                <?php foreach ( $fb_posts as $biz ) :
                    $biz_id     = $biz->ID;
                    $biz_title  = $biz->post_title;
                    $logo_id    = function_exists( 'get_field' ) ? get_field( 'business_logo',              $biz_id ) : '';
                    $short_desc = function_exists( 'get_field' ) ? get_field( 'business_short_description', $biz_id ) : '';
                    $rating     = function_exists( 'get_field' ) ? get_field( 'business_rating',            $biz_id ) : '';

                    if ( ! $short_desc ) $short_desc = get_the_excerpt( $biz_id );

                    $logo_url = '';
                    if ( $logo_id ) {
                        $logo_url = is_array( $logo_id )
                            ? ( $logo_id['url'] ?? '' )
                            : wp_get_attachment_image_url( $logo_id, 'thumbnail' );
                    }
                    if ( ! $logo_url ) $logo_url = get_the_post_thumbnail_url( $biz_id, 'thumbnail' );

                    $initials = '';
                    foreach ( array_slice( explode( ' ', $biz_title ), 0, 2 ) as $word ) {
                        $initials .= strtoupper( substr( $word, 0, 1 ) );
                    }

                    $biz_industry_terms = wp_get_post_terms( $biz_id, 'industry_cat' );
                    $biz_industry_label = '';
                    if ( ! $is_industry_filtered && ! empty( $biz_industry_terms ) && ! is_wp_error( $biz_industry_terms ) ) {
                        $biz_industry_label = $biz_industry_terms[0]->name;
                    }

                    $biz_url = get_permalink( $biz_id );

                    $stars_html = '';
                    if ( $rating ) {
                        $rf = floatval( $rating );
                        $fs = floor( $rf );
                        $hs = ( $rf - $fs ) >= 0.5;
                        for ( $s = 1; $s <= 5; $s++ ) {
                            if      ( $s <= $fs )             { $stars_html .= '<span class="lsb-cp-fb-star lsb-cp-fb-star--full">★</span>'; }
                            elseif  ( $s === $fs + 1 && $hs ) { $stars_html .= '<span class="lsb-cp-fb-star lsb-cp-fb-star--half">★</span>'; }
                            else                              { $stars_html .= '<span class="lsb-cp-fb-star lsb-cp-fb-star--empty">☆</span>'; }
                        }
                    }
                ?>
                <article class="lsb-cp-fb-card">

                    <?php if ( $logo_url ) : ?>
                    <div class="lsb-cp-fb-logo">
                        <img src="<?php echo esc_url( $logo_url ); ?>"
                             alt="<?php echo esc_attr( $biz_title ); ?> logo"
                             loading="lazy" width="72" height="72">
                    </div>
                    <?php else : ?>
                    <div class="lsb-cp-fb-logo lsb-cp-fb-logo--initials" aria-hidden="true">
                        <?php echo esc_html( $initials ); ?>
                    </div>
                    <?php endif; ?>

                    <div class="lsb-cp-fb-body">
                        <div class="lsb-cp-fb-meta-row">
                            <h3 class="lsb-cp-fb-name"><?php echo esc_html( $biz_title ); ?></h3>
                            <?php if ( $biz_industry_label ) : ?>
                            <span class="lsb-cp-fb-industry-tag"><?php echo esc_html( $biz_industry_label ); ?></span>
                            <?php endif; ?>
                            <?php if ( $rating ) : ?>
                            <div class="lsb-cp-fb-rating" aria-label="Rating: <?php echo esc_attr( $rating ); ?> out of 5">
                                <span class="lsb-cp-fb-stars"><?php echo $stars_html; ?></span>
                                <span class="lsb-cp-fb-rating-num"><?php echo esc_html( number_format( (float) $rating, 1 ) ); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if ( $short_desc ) : ?>
                        <p class="lsb-cp-fb-desc"><?php echo esc_html( wp_trim_words( $short_desc, 25, '…' ) ); ?></p>
                        <?php endif; ?>
                        <p class="lsb-cp-fb-location">📍 <?php echo esc_html( $city_name ); ?>, CA</p>
                    </div>

                    <div class="lsb-cp-fb-cta">
                        <a href="<?php echo esc_url( $biz_url ); ?>"
                           class="lsb-cp-fb-btn"
                           aria-label="View <?php echo esc_attr( $biz_title ); ?> profile">
                            View Business
                        </a>
                    </div>

                </article>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <?php endif; // end featured businesses ?>

    <!-- ============================================================
         SECTION 7 — FAQ
    ============================================================ -->

    <?php if ( ! empty( $faq_posts ) ) :
        $faq_schema = [];
        foreach ( $faq_posts as $faq ) {
            $short_answer = function_exists( 'get_field' ) ? get_field( 'faq_short_answer', $faq->ID ) : '';
            if ( ! $short_answer ) continue;
            $faq_schema[] = [
                '@type'          => 'Question',
                'name'           => $faq->post_title,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => wp_strip_all_tags( $short_answer ),
                ],
            ];
        }
        $faq_heading = $is_industry_filtered
            ? 'FAQs About ' . $filter_industry_term->name . ' Services in ' . $city_name
            : 'Frequently Asked Questions About ' . $city_name;
    ?>

    <?php if ( ! empty( $faq_schema ) ) : ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode( [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $faq_schema,
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
    </script>
    <?php endif; ?>

    <section class="lsb-cp-faq-section lsb-cp-light-bg" id="lsb-cp-faq">
        <div class="lsb-cp-faq-inner">

            <div class="lsb-cp-faq-header">
                <span class="lsb-cp-section-label">Got Questions?</span>
                <h2 class="lsb-cp-h2"><?php echo esc_html( $faq_heading ); ?></h2>
            </div>

            <div class="lsb-cp-faq-list">
                <?php foreach ( $faq_posts as $i => $faq ) :
                    $short_answer = function_exists( 'get_field' ) ? get_field( 'faq_short_answer', $faq->ID ) : '';
                    if ( ! $short_answer ) continue;
                    $faq_url     = get_permalink( $faq->ID );
                    $faq_item_id = 'lsb-cp-faq-item-' . $city_slug . '-' . $i;
                    $faq_body_id = 'lsb-cp-faq-body-' . $city_slug . '-' . $i;
                ?>
                <div class="lsb-cp-faq-item" id="<?php echo esc_attr( $faq_item_id ); ?>">

                    <button class="lsb-cp-faq-trigger"
                            type="button"
                            aria-expanded="false"
                            aria-controls="<?php echo esc_attr( $faq_body_id ); ?>">
                        <span class="lsb-cp-faq-question"><?php echo esc_html( $faq->post_title ); ?></span>
                        <span class="lsb-cp-faq-icon" aria-hidden="true">+</span>
                    </button>

                    <div class="lsb-cp-faq-body"
                         id="<?php echo esc_attr( $faq_body_id ); ?>"
                         role="region"
                         aria-labelledby="<?php echo esc_attr( $faq_item_id ); ?>">

                        <div class="lsb-cp-faq-answer">
                            <?php echo wp_kses_post( $short_answer ); ?>
                        </div>

                        <div class="lsb-cp-faq-cta-row">
                            <a href="<?php echo esc_url( $faq_url ); ?>"
                               class="lsb-cp-faq-learn-more">
                                Learn More
                            </a>
                            <?php if ( $disclaimer ) : ?>
                            <p class="lsb-cp-faq-disclaimer"><?php echo esc_html( $disclaimer ); ?></p>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <!-- FAQ accordion JS -->
    <script>
    (function () {
        var faqItems = document.querySelectorAll( '#lsb-cp-faq .lsb-cp-faq-item' );
        faqItems.forEach( function ( item ) {
            var trigger = item.querySelector( '.lsb-cp-faq-trigger' );
            if ( ! trigger ) return;
            trigger.addEventListener( 'click', function () {
                var isOpen = item.classList.contains( 'is-open' );
                faqItems.forEach( function ( el ) {
                    el.classList.remove( 'is-open' );
                    el.querySelector( '.lsb-cp-faq-trigger' ).setAttribute( 'aria-expanded', 'false' );
                } );
                if ( ! isOpen ) {
                    item.classList.add( 'is-open' );
                    trigger.setAttribute( 'aria-expanded', 'true' );
                }
            } );
        } );
    }() );
    </script>

    <?php endif; // end FAQ ?>

    <!-- ============================================================
         SECTION 8 — BLOG POSTS
    ============================================================ -->
    <?php if ( ! empty( $blog_posts ) ) : ?>
    <section class="lsb-cp-blog-section lsb-cp-light-bg" id="lsb-cp-blog">
        <div class="lsb-cp-blog-inner">

            <div class="lsb-cp-blog-header">
                <div class="lsb-cp-blog-header-left">
                    <span class="lsb-cp-section-label">Resources &amp; Guides</span>
                    <h2 class="lsb-cp-h2" style="margin-bottom:0;">
                        <?php echo $is_industry_filtered
                            ? esc_html( $filter_industry_term->name . ' Tips for ' . $city_name )
                            : esc_html( 'Helpful Articles for ' . $city_name . ' Residents' );
                        ?>
                    </h2>
                </div>
                <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"
                   class="lsb-cp-blog-view-all">
                    View All Articles
                </a>
            </div>

            <div class="lsb-cp-blog-grid">
                <?php foreach ( $blog_posts as $post ) :
                    $post_id    = $post->ID;
                    $post_url   = get_permalink( $post_id );
                    $post_title = get_the_title( $post_id );
                    $post_date  = get_the_date( 'M j, Y', $post_id );
                    $post_thumb = get_the_post_thumbnail_url( $post_id, 'medium_large' );
                    $post_excerpt = get_the_excerpt( $post_id );
                    if ( ! $post_excerpt ) {
                        $post_excerpt = wp_trim_words( get_the_content( null, false, $post_id ), 20, '…' );
                    }

                    // Primary category
                    $post_cats = get_the_category( $post_id );
                    $primary_cat = ! empty( $post_cats ) ? $post_cats[0]->name : '';
                ?>
                <a href="<?php echo esc_url( $post_url ); ?>" class="lsb-cp-blog-card">

                    <div class="lsb-cp-blog-thumb">
                        <?php if ( $post_thumb ) : ?>
                            <img src="<?php echo esc_url( $post_thumb ); ?>"
                                 alt="<?php echo esc_attr( $post_title ); ?>"
                                 loading="lazy">
                        <?php else : ?>
                            <div class="lsb-cp-blog-thumb-placeholder" aria-hidden="true">📰</div>
                        <?php endif; ?>
                    </div>

                    <div class="lsb-cp-blog-card-body">
                        <div class="lsb-cp-blog-meta">
                            <?php if ( $primary_cat ) : ?>
                            <span class="lsb-cp-blog-category"><?php echo esc_html( $primary_cat ); ?></span>
                            <?php endif; ?>
                            <span class="lsb-cp-blog-date"><?php echo esc_html( $post_date ); ?></span>
                        </div>
                        <h3 class="lsb-cp-blog-title"><?php echo esc_html( $post_title ); ?></h3>
                        <p class="lsb-cp-blog-excerpt"><?php echo esc_html( $post_excerpt ); ?></p>
                        <span class="lsb-cp-blog-read-more">Read Article</span>
                    </div>

                </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
    <?php endif; // end blog ?>

    <!-- ============================================================
         SECTION 10 — LOCAL NEWS / RSS
    ============================================================ -->

    <?php if ( ! empty( $news_feeds ) && ! empty( $news_industries ) ) : ?>
    <section class="lsb-cp-news-section lsb-cp-light-bg" id="lsb-cp-news">
        <div class="lsb-cp-news-inner">

            <div class="lsb-cp-news-header">
                <span class="lsb-cp-section-label">Stay Informed</span>
                <h2 class="lsb-cp-h2">
                    <?php if ( $is_industry_filtered && $filter_industry_term ) : ?>
                        <?php echo esc_html( $filter_industry_term->name ); ?> News in <?php echo esc_html( $city_name ); ?>
                    <?php else : ?>
                        Local Industry News for <?php echo esc_html( $city_name ); ?>
                    <?php endif; ?>
                </h2>
            </div>

            <?php
            $single_industry = count( $news_industries ) === 1;
            ?>

            <?php if ( ! $single_industry ) : ?>
            <!-- Tabs — only shown when multiple industries -->
            <div class="lsb-cp-news-tabs" role="tablist" id="lsb-news-tabs-<?php echo esc_attr( $city_slug ); ?>">
                <?php foreach ( $news_industries as $idx => $ni ) :
                    $has_articles = ! empty( $news_feeds[ $ni['slug'] ] );
                    if ( ! $has_articles ) continue;
                    $icon = function_exists( 'lsb_get_industry_icon' ) ? lsb_get_industry_icon( $ni['slug'] ) : '📰';
                ?>
                <button class="lsb-cp-news-tab<?php echo $idx === 0 ? ' is-active' : ''; ?>"
                        role="tab"
                        data-panel="lsb-news-panel-<?php echo esc_attr( $city_slug . '-' . $ni['slug'] ); ?>"
                        aria-selected="<?php echo $idx === 0 ? 'true' : 'false'; ?>">
                    <span class="lsb-cp-news-tab-icon"><?php echo $icon; ?></span>
                    <?php echo esc_html( $ni['name'] ); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Feed panels -->
            <?php
            $first_active_set = false;
            foreach ( $news_industries as $idx => $ni ) :
                $articles = $news_feeds[ $ni['slug'] ] ?? [];
                if ( empty( $articles ) ) continue;
                $panel_id    = 'lsb-news-panel-' . $city_slug . '-' . $ni['slug'];
                $is_first    = ! $first_active_set;
                $first_active_set = true;
                $panel_class = $single_industry ? 'lsb-cp-news-panel is-active' : ( $is_first ? 'lsb-cp-news-panel is-active' : 'lsb-cp-news-panel' );
            ?>
            <div class="<?php echo esc_attr( $panel_class ); ?>"
                 id="<?php echo esc_attr( $panel_id ); ?>"
                 role="tabpanel">

                <div class="lsb-cp-news-grid">
                    <?php foreach ( $articles as $article ) : ?>
                    <a href="<?php echo esc_url( $article['link'] ); ?>"
                       class="lsb-cp-news-card"
                       target="_blank"
                       rel="noopener noreferrer">

                        <?php if ( $article['source'] ) : ?>
                        <span class="lsb-cp-news-source"><?php echo esc_html( $article['source'] ); ?></span>
                        <?php endif; ?>

                        <span class="lsb-cp-news-title"><?php echo esc_html( $article['title'] ); ?></span>

                        <span class="lsb-cp-news-date"><?php echo esc_html( $article['date'] ); ?></span>

                        <span class="lsb-cp-news-read">Read Article →</span>

                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="lsb-cp-news-credit">
                    📰 News sourced from Google News — <?php echo esc_html( $ni['name'] ); ?> in <?php echo esc_html( $city_name ); ?>
                </div>

            </div>
            <?php endforeach; ?>

        </div>
    </section>

    <!-- News tab JS -->
    <?php if ( ! $single_industry ) : ?>
    <script>
    (function () {
        var tabContainer = document.getElementById( 'lsb-news-tabs-<?php echo esc_js( $city_slug ); ?>' );
        if ( ! tabContainer ) return;
        tabContainer.addEventListener( 'click', function ( e ) {
            var tab = e.target.closest( '.lsb-cp-news-tab' );
            if ( ! tab ) return;
            var panelId = tab.getAttribute( 'data-panel' );
            // Deactivate all tabs
            tabContainer.querySelectorAll( '.lsb-cp-news-tab' ).forEach( function ( t ) {
                t.classList.remove( 'is-active' );
                t.setAttribute( 'aria-selected', 'false' );
            } );
            // Hide all panels
            document.querySelectorAll( '#lsb-cp-news .lsb-cp-news-panel' ).forEach( function ( p ) {
                p.classList.remove( 'is-active' );
            } );
            // Activate clicked
            tab.classList.add( 'is-active' );
            tab.setAttribute( 'aria-selected', 'true' );
            var panel = document.getElementById( panelId );
            if ( panel ) panel.classList.add( 'is-active' );
        } );
    }() );
    </script>
    <?php endif; ?>

    <?php endif; // end news section ?>

    <!-- ============================================================
         SECTION 11 — FINAL CTA
    ============================================================ -->

    <section class="lsb-cp-cta-section lsb-cp-dark-bg" id="lsb-cp-final-cta">

        <div class="lsb-cp-cta-grid-bg" aria-hidden="true"></div>
        <div class="lsb-cp-cta-glow-1"  aria-hidden="true"></div>
        <div class="lsb-cp-cta-glow-2"  aria-hidden="true"></div>

        <div class="lsb-cp-cta-inner">

            <span class="lsb-cp-section-label">Get Started Today</span>

            <h2 class="lsb-cp-cta-heading">
                <?php echo wp_kses_post( $cta_heading ); ?>
            </h2>

            <?php if ( $cta_text ) : ?>
            <div class="lsb-cp-cta-text">
                <?php echo wp_kses_post( $cta_text ); ?>
            </div>
            <?php endif; ?>

            <a href="<?php echo esc_url( $cta_button_link ); ?>"
               class="lsb-cp-cta-btn">
                <?php echo esc_html( $cta_button_text ); ?>
            </a>

        </div>

    </section>

    <?php
    return ob_get_clean();
}

add_shortcode( 'city_page', 'lsb_city_page_shortcode' );

endif;