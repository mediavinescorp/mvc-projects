<?php
/**
 * Shortcode: [city_industry_page]
 *
 * File: /shortcodes/city-industry-page.php
 * Called in functions.php:
 *   require_once get_stylesheet_directory() . '/shortcodes/city-industry-page.php';
 * Called in template-city-industry.php:
 *   <?php echo do_shortcode('[city_industry_page]'); ?>
 *
 * URL pattern: /cities/{city-slug}/{industry-slug}/
 * Query vars:  city_slug, industry_slug (set by rewrite-rules.php)
 *
 * Sections:
 *   1.  Hero                → dark bg
 *   2.  City+Industry Context → dark bg — why_this_city_matters (city) + industry overview (industry CPT)
 *   3.  Services Grid       → light bg — scoped to industry_cat
 *   4.  Featured Businesses → white   — dual filtered: city+industry, full cards (up to 6)
 *       + More Businesses   →          plain list: name + phone (up to 10, excludes featured)
 *   5.  Local Tips          → off-white — city local_tips + industry common problems repackaged
 *   6.  Common Problems     → white   — from industry CPT
 *   7.  Weather Widget      → dark bg — same as city page
 *   8.  Why Hire a Pro      → light bg — condensed from industry CPT
 *   9.  FAQ                 → off-white — city+industry dual tag → industry-only fallback, up to 7, daily shuffled
 *   10. Nearby Cities       → white   — same industry, neighboring cities
 *   11. Final CTA           → dark bg
 *
 * ACF fields used (city CPT post):
 *   why_this_city_matters    → WYSIWYG
 *   local_tips               → repeater: tip_icon, tip_title, tip_description
 *   city_hero_intro          → text
 *   city_overview            → text
 *   faq_source_note_disclaimer → text
 *
 * ACF fields used (industry CPT post):
 *   industry_overview        → WYSIWYG (What HVAC Companies Do section)
 *   why_hire_intro           → text
 *   why_hire_points          → repeater: point_text
 *   industry_disclaimer      → text
 */

if ( ! function_exists( 'lsb_city_industry_page_shortcode' ) ) :

function lsb_city_industry_page_shortcode( $atts ) {

    $atts = shortcode_atts( [], $atts, 'city_industry_page' );

    // ── 1. Resolve city_slug and industry_slug from query vars ────────────
    $city_slug     = sanitize_title( get_query_var( 'city_slug' ) );
    $industry_slug = sanitize_title( get_query_var( 'industry_slug' ) );

    // Fallback: allow ?industry= param on city pages to also trigger this
    if ( empty( $industry_slug ) ) {
        $industry_slug = sanitize_title( $_GET['industry'] ?? '' );
    }

    if ( empty( $city_slug ) || empty( $industry_slug ) ) {
        return '<!-- [city_industry_page] missing city_slug or industry_slug -->';
    }

    // ── 2. Resolve city post ──────────────────────────────────────────────
    $city_post = get_page_by_path( $city_slug, OBJECT, 'cities' );
    if ( ! $city_post ) {
        return '<!-- [city_industry_page] city post not found for slug: ' . esc_html( $city_slug ) . ' -->';
    }
    $city_post_id = $city_post->ID;
    $city_name    = $city_post->post_title;

    // ── 3. Resolve industry_cat term ──────────────────────────────────────
    $industry_term = get_term_by( 'slug', $industry_slug, 'industry_cat' );
    if ( ! $industry_term || is_wp_error( $industry_term ) ) {
        return '<!-- [city_industry_page] industry_cat term not found for slug: ' . esc_html( $industry_slug ) . ' -->';
    }
    $industry_name = $industry_term->name;

    // ── 4. Resolve industry CPT post ─────────────────────────────────────
    $industry_posts = get_posts( [
        'post_type'   => 'industries',
        'name'        => $industry_slug,
        'numberposts' => 1,
        'post_status' => 'publish',
    ] );
    $industry_post_id = ! empty( $industry_posts ) ? $industry_posts[0]->ID : null;

    // ── 5. city_cat term for this city post ───────────────────────────────
    $city_cat_terms = wp_get_post_terms( $city_post_id, 'city_cat', [ 'fields' => 'slugs' ] );
    $city_cat_slug  = ! empty( $city_cat_terms ) ? $city_cat_terms[0] : $city_slug;

    // ── 6. ACF fields — city post ─────────────────────────────────────────
    $city_hero_intro    = '';
    $city_overview      = '';
    $why_city_content   = '';
    $local_tips         = [];
    $disclaimer         = '';
    $weather_lookup_name = '';

    if ( function_exists( 'get_field' ) ) {
        $city_hero_intro     = get_field( 'city_hero_intro',            $city_post_id );
        $city_overview       = get_field( 'city_overview',              $city_post_id );
        $why_city_content    = get_field( 'why_this_city_matters',      $city_post_id );
        $local_tips          = get_field( 'local_tips',                 $city_post_id ) ?: [];
        $disclaimer          = get_field( 'faq_source_note_disclaimer', $city_post_id );
        $weather_lookup_name = get_field( 'weather_lookup_name',        $city_post_id );
    }

    // Weather lookup name — use ACF override if set, otherwise fall back to city title
    // Use this for LA neighborhoods (Northridge, Winnetka, etc.) that OWM doesn't know
    $weather_city_name = ! empty( $weather_lookup_name ) ? trim( $weather_lookup_name ) : $city_name;

    // ── 7. ACF fields — industry post ─────────────────────────────────────
    $industry_overview  = '';
    $why_hire_intro     = '';
    $why_hire_points    = [];
    $industry_disclaimer = '';

    if ( $industry_post_id && function_exists( 'get_field' ) ) {
        $industry_overview   = get_field( 'industry_overview',   $industry_post_id );
        $why_hire_intro      = get_field( 'why_hire_intro',      $industry_post_id );
        $why_hire_points     = get_field( 'why_hire_points',     $industry_post_id ) ?: [];
        $industry_disclaimer = get_field( 'industry_disclaimer', $industry_post_id );
    }

    // ── 8. Services — full parent/child grouped structure via mapping engine ─
    // Mirrors the logic in industry-single-page.php exactly.
    // Uses mvc_de_get_industry_services() for parent slugs and
    // mvc_de_get_direct_child_service_slugs() for children under each parent.
    $grouped_services = [];

    $all_service_slugs = ( function_exists( 'mvc_de_get_industry_services' ) )
        ? mvc_de_get_industry_services( $industry_slug )
        : [];

    // Guard: mapping engine must return an array
    if ( ! is_array( $all_service_slugs ) ) {
        $all_service_slugs = [];
    }

    // Filter to top-level parent terms only (parent = 0 in service_type taxonomy)
    $parent_service_slugs = array_filter( $all_service_slugs, function( $slug ) {
        $term = get_term_by( 'slug', $slug, 'service_type' );
        return $term && ! is_wp_error( $term ) && (int) $term->parent === 0;
    } );

    foreach ( $parent_service_slugs as $parent_slug ) {

        // ── Parent term ───────────────────────────────────────────────────
        $parent_term = get_term_by( 'slug', $parent_slug, 'service_type' );
        if ( ! $parent_term || is_wp_error( $parent_term ) ) continue;

        // ── Parent services CPT post — by slug first, then tax fallback ──
        $parent_post = null;
        $by_slug = get_posts( [
            'post_type'      => 'services',
            'name'           => $parent_slug,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ] );
        if ( ! empty( $by_slug ) ) {
            $parent_post = $by_slug[0];
        } else {
            $by_tax = get_posts( [
                'post_type'      => 'services',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'tax_query'      => [ [
                    'taxonomy'         => 'service_type',
                    'field'            => 'term_id',
                    'terms'            => $parent_term->term_id,
                    'include_children' => false,
                ] ],
            ] );
            if ( ! empty( $by_tax ) ) $parent_post = $by_tax[0];
        }

        // ── Parent icon + desc ────────────────────────────────────────────
        $parent_icon = '';
        $parent_desc = '';
        if ( $parent_post && function_exists( 'get_field' ) ) {
            $raw_icon = get_field( 'service_icon', $parent_post->ID );
            if ( is_array( $raw_icon ) && ! empty( $raw_icon['url'] ) ) {
                $parent_icon = $raw_icon['url'];
            } elseif ( is_string( $raw_icon ) && ! empty( $raw_icon ) ) {
                $parent_icon = $raw_icon;
            }
            $parent_desc = get_the_excerpt( $parent_post->ID );
            if ( ! $parent_desc ) {
                $parent_desc = wp_trim_words( get_post_field( 'post_content', $parent_post->ID ), 16, '...' );
            }
        }

        // ── Child slugs from mapping engine ──────────────────────────────
        $child_slugs = ( function_exists( 'mvc_de_get_direct_child_service_slugs' ) )
            ? mvc_de_get_direct_child_service_slugs( $parent_slug )
            : [];
        if ( ! is_array( $child_slugs ) ) $child_slugs = [];

        // ── Build child entries ───────────────────────────────────────────
        $children = [];
        foreach ( $child_slugs as $child_slug ) {
            $child_term = get_term_by( 'slug', $child_slug, 'service_type' );
            if ( ! $child_term || is_wp_error( $child_term ) ) continue;

            $child_post = null;
            $child_by_slug = get_posts( [
                'post_type'      => 'services',
                'name'           => $child_slug,
                'posts_per_page' => 1,
                'post_status'    => 'publish',
            ] );
            if ( ! empty( $child_by_slug ) ) {
                $child_post = $child_by_slug[0];
            } else {
                $child_by_tax = get_posts( [
                    'post_type'      => 'services',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'tax_query'      => [ [
                        'taxonomy'         => 'service_type',
                        'field'            => 'term_id',
                        'terms'            => $child_term->term_id,
                        'include_children' => false,
                    ] ],
                ] );
                if ( ! empty( $child_by_tax ) ) $child_post = $child_by_tax[0];
            }

            $children[] = [
                'term' => $child_term,
                'post' => $child_post,
                'url'  => $child_post ? get_permalink( $child_post->ID ) : '#',
            ];
        }

        $grouped_services[] = [
            'slug'     => $parent_slug,
            'term'     => $parent_term,
            'post'     => $parent_post,
            'url'      => $parent_post ? get_permalink( $parent_post->ID ) : '#',
            'icon'     => $parent_icon,
            'desc'     => $parent_desc,
            'children' => $children,
        ];
    }

    // Fallback: if mapping engine returned nothing, query services directly
    if ( empty( $grouped_services ) ) {
        $svc_query = new WP_Query( [
            'post_type'      => 'services',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'tax_query'      => [ [
                'taxonomy'         => 'industry_cat',
                'field'            => 'slug',
                'terms'            => $industry_slug,
                'include_children' => true,
            ] ],
        ] );
        if ( $svc_query->have_posts() ) {
            // Wrap in a single group so render loop still works
            $grouped_services[] = [
                'slug'     => $industry_slug,
                'term'     => $industry_term,
                'post'     => null,
                'url'      => '#',
                'icon'     => '',
                'desc'     => '',
                'children' => array_map( function( $p ) {
                    return [
                        'term' => (object)[ 'name' => $p->post_title ],
                        'post' => $p,
                        'url'  => get_permalink( $p->ID ),
                    ];
                }, $svc_query->posts ),
            ];
        }
        wp_reset_postdata();
    }

    // Total service count for hero pill
    $service_count = 0;
    foreach ( $grouped_services as $grp ) {
        $service_count += 1 + count( $grp['children'] ?? [] ); // parent + children
    }

    // ── 9. Featured businesses — dual filtered ────────────────────────────
    $featured_args = [
        'post_type'      => 'businesses',
        'post_status'    => 'publish',
        'posts_per_page' => 6,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'business_rating',
        'order'          => 'DESC',
        'tax_query'      => [
            'relation' => 'AND',
            [
                'taxonomy' => 'city_cat',
                'field'    => 'slug',
                'terms'    => $city_cat_slug,
            ],
            [
                'taxonomy'         => 'industry_cat',
                'field'            => 'slug',
                'terms'            => $industry_slug,
                'include_children' => true,
            ],
        ],
        'meta_query'     => [ [
            'key'   => 'featured_business',
            'value' => '1',
        ] ],
    ];
    $featured_query = new WP_Query( $featured_args );
    $featured_posts = $featured_query->posts;
    $featured_ids   = wp_list_pluck( $featured_posts, 'ID' );
    wp_reset_postdata();

    // ── 10. More businesses — exclude featured, name + phone only ─────────
    $more_args = [
        'post_type'      => 'businesses',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post__not_in'   => ! empty( $featured_ids ) ? $featured_ids : [ 0 ],
        'tax_query'      => [
            'relation' => 'AND',
            [
                'taxonomy' => 'city_cat',
                'field'    => 'slug',
                'terms'    => $city_cat_slug,
            ],
            [
                'taxonomy'         => 'industry_cat',
                'field'            => 'slug',
                'terms'            => $industry_slug,
                'include_children' => true,
            ],
        ],
    ];
    $more_query = new WP_Query( $more_args );
    $more_posts = $more_query->posts;
    wp_reset_postdata();

    $total_business_count = count( $featured_posts ) + count( $more_posts );

    // ── 11. FAQs — dual tag first, fallback to industry-only ─────────────
    // ── 11. FAQs — dual tag first, fallback to industry-only ─────────────
    // Use term_id (not slug) — consistent with industry-single-page.php
    $faq_dual_query = new WP_Query( [
        'post_type'      => 'faqs',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'none',
        'tax_query'      => [
            'relation' => 'AND',
            [
                'taxonomy'         => 'city_cat',
                'field'            => 'slug',
                'terms'            => $city_cat_slug,
                'include_children' => true,
            ],
            [
                'taxonomy'         => 'industry_cat',
                'field'            => 'term_id',
                'terms'            => $industry_term->term_id,
                'include_children' => true,
            ],
        ],
    ] );
    $faq_all = $faq_dual_query->posts;
    wp_reset_postdata();

    // Fallback: industry-only FAQs if dual tag returns fewer than 3
    if ( count( $faq_all ) < 3 ) {
        $faq_industry_query = new WP_Query( [
            'post_type'      => 'faqs',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'none',
            'post__not_in'   => ! empty( $faq_all ) ? wp_list_pluck( $faq_all, 'ID' ) : [ 0 ],
            'tax_query'      => [ [
                'taxonomy'         => 'industry_cat',
                'field'            => 'term_id',
                'terms'            => $industry_term->term_id,
                'include_children' => true,
            ] ],
        ] );
        $faq_all = array_merge( $faq_all, $faq_industry_query->posts );
        wp_reset_postdata();
    }

    // Daily shuffle — deterministic per city+industry combo
    $daily_seed = (int) date( 'Ymd' ) + crc32( $city_slug . $industry_slug );
    usort( $faq_all, function( $a, $b ) use ( $daily_seed ) {
        return crc32( $daily_seed . $a->ID ) - crc32( $daily_seed . $b->ID );
    } );
    $faq_posts = array_slice( $faq_all, 0, 7 );

    // ── 12. Nearby cities — same industry ────────────────────────────────
    // Get all cities that have this industry_cat assigned, exclude current
    $nearby_args = [
        'post_type'      => 'cities',
        'post_status'    => 'publish',
        'posts_per_page' => 8,
        'post__not_in'   => [ $city_post_id ],
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => [ [
            'taxonomy'         => 'industry_cat',
            'field'            => 'slug',
            'terms'            => $industry_slug,
            'include_children' => true,
        ] ],
    ];
    $nearby_query = new WP_Query( $nearby_args );
    $nearby_posts = $nearby_query->posts;
    wp_reset_postdata();

    // ── 13. Weather data ──────────────────────────────────────────────────
    $weather_data    = null;
    $forecast_data   = [];
    $weather_api_key = defined( 'LSB_WEATHER_API_KEY' ) ? LSB_WEATHER_API_KEY : '13a72bfef42c66601038bb536e609f30';

    if ( $weather_api_key ) {
        $weather_cache_key  = 'lsb_weather_ca_' . md5( $weather_city_name );
        $forecast_cache_key = 'lsb_forecast_ca_' . md5( $weather_city_name );

        $weather_data  = get_transient( $weather_cache_key );
        $forecast_data = get_transient( $forecast_cache_key );

        if ( false === $weather_data ) {
            $api_url  = 'https://api.openweathermap.org/data/2.5/weather?q='
                . urlencode( $weather_city_name . ',CA,US' )
                . '&units=imperial&appid=' . $weather_api_key;
            $response = wp_remote_get( $api_url, [ 'timeout' => 8 ] );
            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $weather_data = json_decode( wp_remote_retrieve_body( $response ), true );
                set_transient( $weather_cache_key, $weather_data, HOUR_IN_SECONDS );
            } else {
                $weather_data = [];
                set_transient( $weather_cache_key, $weather_data, 15 * MINUTE_IN_SECONDS );
            }
        }

        if ( false === $forecast_data ) {
            $fc_url      = 'https://api.openweathermap.org/data/2.5/forecast?q='
                . urlencode( $weather_city_name . ',CA,US' )
                . '&units=imperial&cnt=24&appid=' . $weather_api_key;
            $fc_response = wp_remote_get( $fc_url, [ 'timeout' => 8 ] );
            if ( ! is_wp_error( $fc_response ) && wp_remote_retrieve_response_code( $fc_response ) === 200 ) {
                $fc_raw        = json_decode( wp_remote_retrieve_body( $fc_response ), true );
                $forecast_data = $fc_raw['list'] ?? [];
                set_transient( $forecast_cache_key, $forecast_data, HOUR_IN_SECONDS );
            } else {
                $forecast_data = [];
                set_transient( $forecast_cache_key, $forecast_data, 15 * MINUTE_IN_SECONDS );
            }
        }
    }

    // Weather emoji helper
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

    // Build 4-day forecast
    $forecast_days = [];
    if ( ! empty( $forecast_data ) ) {
        foreach ( $forecast_data as $entry ) {
            $day_key = date( 'Y-m-d', $entry['dt'] );
            $hour    = (int) date( 'G', $entry['dt'] );
            if ( ! isset( $forecast_days[ $day_key ] ) || abs( $hour - 12 ) < abs( (int) date( 'G', $forecast_days[ $day_key ]['dt'] ) - 12 ) ) {
                $forecast_days[ $day_key ] = $entry;
            }
        }
        $today_key     = date( 'Y-m-d' );
        $forecast_days = array_filter( $forecast_days, fn( $k ) => $k !== $today_key, ARRAY_FILTER_USE_KEY );
        $forecast_days = array_slice( $forecast_days, 0, 4 );
    }

    // ── 14. URLs ──────────────────────────────────────────────────────────
    $home_url      = trailingslashit( home_url() );
    $city_url      = $home_url . 'cities/' . $city_slug . '/';
    $industry_url  = $home_url . 'industries/' . $industry_slug . '/';
    $page_url      = $home_url . 'cities/' . $city_slug . '/' . $industry_slug . '/';
    $biz_url       = $home_url . 'businesses/' . $industry_slug . '/';

    // ── 15. Industry icon ─────────────────────────────────────────────────
    $industry_icon = function_exists( 'lsb_get_industry_icon' )
        ? lsb_get_industry_icon( $industry_slug )
        : '🔧';

    // ── 16. Schema — FAQ ──────────────────────────────────────────────────
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

    // ── 17. Schema — LocalBusiness list ──────────────────────────────────
    $biz_schema_items = [];
    foreach ( $featured_posts as $pos => $biz ) {
        $biz_rating = function_exists( 'get_field' ) ? get_field( 'business_rating', $biz->ID ) : '';
        $biz_phone  = function_exists( 'get_field' ) ? get_field( 'business_phone',  $biz->ID ) : '';
        $biz_logo   = function_exists( 'get_field' ) ? get_field( 'business_logo',   $biz->ID ) : '';
        $logo_url   = '';
        if ( $biz_logo ) {
            $logo_url = is_array( $biz_logo ) ? ( $biz_logo['url'] ?? '' ) : wp_get_attachment_image_url( $biz_logo, 'thumbnail' );
        }
        $biz_schema_items[] = [
            '@type'    => 'ListItem',
            'position' => $pos + 1,
            'item'     => array_filter( [
                '@type'     => 'LocalBusiness',
                'name'      => $biz->post_title,
                'url'       => get_permalink( $biz->ID ),
                'telephone' => $biz_phone ?: null,
                'image'     => $logo_url  ?: null,
                'aggregateRating' => $biz_rating ? [
                    '@type'       => 'AggregateRating',
                    'ratingValue' => $biz_rating,
                    'bestRating'  => '5',
                ] : null,
            ] ),
        ];
    }

    // ── 18. Render ────────────────────────────────────────────────────────
    $uid = 'lsb-ci-' . $city_slug . '-' . $industry_slug;
    $out = '';

    // ============================================================
    // STYLES
    // ============================================================
    $out .= '<style id="lsb-ci-styles">';
    $out .= '
    /* ============================
       COLOR SYSTEM
    ============================ */
    .lsb-ci-dark  {
        --lsb-ci-heading : #F5F7FA;
        --lsb-ci-body    : rgba(255,255,255,0.60);
        --lsb-ci-muted   : rgba(255,255,255,0.35);
        --lsb-ci-label   : #00C9A7;
        --lsb-ci-border  : rgba(255,255,255,0.08);
        --lsb-ci-card-bg : rgba(255,255,255,0.04);
    }
    .lsb-ci-light {
        --lsb-ci-heading : #0D1B2A;
        --lsb-ci-body    : #3D4F63;
        --lsb-ci-muted   : #8A9BB0;
        --lsb-ci-label   : #00A88C;
        --lsb-ci-border  : #E4EAF2;
        --lsb-ci-card-bg : #F5F7FA;
    }

    /* ============================
       SHARED
    ============================ */
    .lsb-ci-inner         { max-width:1200px; margin:0 auto; }
    .lsb-ci-section-label {
        display:inline-block;
        font-size:0.72rem; font-weight:600;
        letter-spacing:0.12em; text-transform:uppercase;
        color:var(--lsb-ci-label);
        margin-bottom:12px;
        font-family:"DM Sans",sans-serif;
    }
    .lsb-ci-h2 {
        font-family:"Syne",sans-serif;
        font-size:clamp(1.8rem,3.5vw,2.6rem);
        font-weight:800;
        color:var(--lsb-ci-heading) !important;
        letter-spacing:-0.02em;
        line-height:1.1;
        margin-bottom:20px;
    }
    .lsb-ci-h2 em { font-style:normal; color:#00C9A7; }

    /* ============================
       BREADCRUMB
    ============================ */
    .lsb-ci-breadcrumb-bar {
        background:#0D1B2A;
        padding:0 40px;
        height:44px;
        display:flex; align-items:center;
        border-bottom:1px solid rgba(255,255,255,0.06);
        margin-top:72px;
    }
    .lsb-ci-breadcrumb {
        max-width:1200px; margin:0 auto; width:100%;
        display:flex; align-items:center; gap:8px;
        font-size:0.8rem;
        color:rgba(255,255,255,0.4);
        list-style:none; padding:0;
        font-family:"DM Sans",sans-serif;
        flex-wrap:wrap;
    }
    .lsb-ci-breadcrumb a       { color:rgba(255,255,255,0.4); text-decoration:none; transition:color .2s; }
    .lsb-ci-breadcrumb a:hover { color:#00C9A7; }
    .lsb-ci-bc-sep             { color:rgba(255,255,255,0.2); }
    .lsb-ci-bc-current         { color:#00C9A7; font-weight:500; }

    /* ============================
       SECTION 1 — HERO
    ============================ */
    .lsb-ci-hero {
        background:#0D1B2A;
        padding:56px 40px 64px;
        position:relative; overflow:hidden;
    }
    .lsb-ci-hero-grid {
        position:absolute; inset:0; pointer-events:none;
        background-image:
            linear-gradient(rgba(0,201,167,0.04) 1px,transparent 1px),
            linear-gradient(90deg,rgba(0,201,167,0.04) 1px,transparent 1px);
        background-size:60px 60px;
    }
    .lsb-ci-hero-glow-1 {
        position:absolute; pointer-events:none;
        width:500px; height:500px;
        background:radial-gradient(circle,rgba(0,201,167,0.12) 0%,transparent 70%);
        top:-120px; right:-80px;
    }
    .lsb-ci-hero-glow-2 {
        position:absolute; pointer-events:none;
        width:340px; height:340px;
        background:radial-gradient(circle,rgba(244,197,66,0.07) 0%,transparent 70%);
        bottom:0; left:100px;
    }
    .lsb-ci-hero-inner { max-width:900px; margin:0 auto; position:relative; z-index:1; }
    .lsb-ci-hero-badge {
        display:inline-flex; align-items:center; gap:8px;
        background:rgba(0,201,167,0.12);
        border:1px solid rgba(0,201,167,0.25);
        border-radius:100px;
        padding:6px 16px;
        margin-bottom:24px;
    }
    .lsb-ci-hero-badge span {
        color:#00C9A7;
        font-size:0.78rem; font-weight:500;
        letter-spacing:0.06em; text-transform:uppercase;
        font-family:"DM Sans",sans-serif;
    }
    .lsb-ci-hero-badge-dot {
        width:6px; height:6px;
        background:#00C9A7; border-radius:50%;
        animation:lsb-ci-pulse 2s infinite;
        flex-shrink:0;
    }
    @keyframes lsb-ci-pulse {
        0%,100% { opacity:1; } 50% { opacity:0.4; }
    }
    .lsb-ci-h1 {
        font-family:"Syne",sans-serif;
        font-size:clamp(2.2rem,4.5vw,3.8rem);
        font-weight:800;
        color:#F5F7FA !important;
        line-height:1;
        letter-spacing:-0.01em;
        margin-bottom:24px;
    }
    .lsb-ci-h1 span { display:block; line-height:1; }
    .lsb-ci-h1 em { font-style:normal; color:#00C9A7; display:block; line-height:1; margin-top:4px; }
    .lsb-ci-hero-sub {
        font-size:1.05rem !important;
        color:rgba(255,255,255,0.60) !important;
        line-height:1.8 !important;
        font-weight:300;
        max-width:680px;
        margin-bottom:32px;
        font-family:"DM Sans",sans-serif;
    }
    .lsb-ci-hero-pills {
        display:flex; flex-wrap:wrap; gap:10px;
        margin-bottom:36px;
    }
    .lsb-ci-hero-pill {
        display:inline-flex; align-items:center; gap:8px;
        background:rgba(255,255,255,0.06);
        border:1px solid rgba(255,255,255,0.12);
        border-radius:100px;
        padding:8px 18px;
        font-family:"DM Sans",sans-serif;
        font-size:0.85rem;
        color:rgba(255,255,255,0.75);
        white-space:nowrap;
    }
    .lsb-ci-hero-pill strong {
        font-family:"Syne",sans-serif;
        font-weight:800; font-size:1rem; color:#00C9A7;
    }
    .lsb-ci-hero-pill-dot {
        width:5px; height:5px;
        background:#00C9A7; border-radius:50%; opacity:0.6;
    }
    .lsb-ci-hero-ctas { display:flex; gap:14px; flex-wrap:wrap; }
    .lsb-ci-btn-primary {
        display:inline-flex; align-items:center; gap:8px;
        background:#00C9A7 !important; color:#0D1B2A !important;
        font-family:"Syne",sans-serif !important;
        font-weight:700 !important; font-size:0.9rem !important;
        padding:14px 28px !important;
        border-radius:8px !important;
        text-decoration:none !important;
        letter-spacing:0.02em !important;
        transition:background .2s, transform .15s !important;
    }
    .lsb-ci-btn-primary:hover { background:#00A88C !important; transform:translateY(-2px); }
    .lsb-ci-btn-secondary {
        display:inline-flex; align-items:center; gap:8px;
        background:transparent !important; color:#ffffff !important;
        font-family:"Syne",sans-serif !important;
        font-weight:600 !important; font-size:0.9rem !important;
        padding:14px 28px !important;
        border-radius:8px !important;
        border:1px solid rgba(255,255,255,0.2) !important;
        text-decoration:none !important;
        letter-spacing:0.02em !important;
        transition:border-color .2s, background .2s !important;
    }
    .lsb-ci-btn-secondary:hover { border-color:#fff !important; background:rgba(255,255,255,0.05) !important; }

    /* ============================
       SECTION 2 — CONTEXT
    ============================ */
    .lsb-ci-context-section {
        background:#ffffff;
        padding:80px 40px;
        position:relative; overflow:hidden;
    }
    .lsb-ci-context-grid-bg {
        position:absolute; inset:0; pointer-events:none;
        background-image:
            linear-gradient(rgba(0,201,167,0.03) 1px,transparent 1px),
            linear-gradient(90deg,rgba(0,201,167,0.03) 1px,transparent 1px);
        background-size:60px 60px;
    }
    .lsb-ci-context-glow {
        position:absolute; pointer-events:none;
        width:500px; height:400px;
        background:radial-gradient(circle,rgba(0,201,167,0.05) 0%,transparent 70%);
        top:-80px; right:-60px;
    }
    .lsb-ci-context-inner {
        max-width:1200px; margin:0 auto;
        position:relative; z-index:1;
        display:grid;
        grid-template-columns:1fr 1.6fr;
        gap:72px; align-items:start;
    }
    .lsb-ci-context-left { position:sticky; top:100px; }
    .lsb-ci-context-eyebrow {
        display:inline-block;
        font-size:0.72rem; font-weight:600;
        letter-spacing:0.12em; text-transform:uppercase;
        color:#00A88C; margin-bottom:12px;
        font-family:"DM Sans",sans-serif;
    }
    .lsb-ci-context-heading {
        font-family:"Syne",sans-serif !important;
        font-size:clamp(1.8rem,3vw,2.6rem) !important;
        font-weight:800 !important;
        color:#0D1B2A !important;
        letter-spacing:-0.02em !important;
        line-height:1.1 !important;
        margin-bottom:20px !important;
    }
    .lsb-ci-context-heading em { font-style:normal; color:#00C9A7; }
    .lsb-ci-context-subtext {
        font-size:0.9rem !important;
        color:#8A9BB0 !important;
        line-height:1.7 !important;
        font-family:"DM Sans",sans-serif !important;
        font-weight:300 !important;
        margin:0 !important;
    }
    .lsb-ci-context-content {
        color:#3D4F63 !important;
        font-family:"DM Sans",sans-serif !important;
        font-size:1rem !important;
        line-height:1.85 !important;
    }
    .lsb-ci-context-content p {
        font-size:1rem !important;
        color:#3D4F63 !important;
        line-height:1.85 !important;
        margin-bottom:20px !important;
        font-weight:400 !important;
    }
    .lsb-ci-context-content p:last-child { margin-bottom:0 !important; }
    .lsb-ci-context-content h3 {
        font-family:"Syne",sans-serif !important;
        font-weight:700 !important; font-size:1.15rem !important;
        color:#0D1B2A !important;
        letter-spacing:-0.01em !important;
        margin-top:32px !important; margin-bottom:12px !important;
    }
    .lsb-ci-context-content strong { color:#0D1B2A !important; font-weight:600 !important; }
    .lsb-ci-context-divider {
        border:none;
        border-top:1px solid #E4EAF2;
        margin:32px 0;
    }
    .lsb-ci-context-source-tag {
        display:inline-flex; align-items:center; gap:6px;
        font-size:0.72rem; color:#8A9BB0;
        font-family:"DM Sans",sans-serif;
        text-transform:uppercase; letter-spacing:0.08em;
        margin-bottom:16px;
    }

    /* ============================
       SECTION 3 — SERVICES GRID
    ============================ */
    .lsb-ci-svc-section { background:#F5F7FA; padding:80px 40px; }
    .lsb-ci-svc-header  { max-width:680px; margin-bottom:48px; }
    .lsb-ci-svc-intro {
        font-size:1rem !important; color:#3D4F63 !important;
        line-height:1.8; font-family:"DM Sans",sans-serif;
        margin-top:12px;
    }
    .lsb-ci-svc-groups  { display:flex; flex-direction:column; gap:40px; }
    .lsb-ci-svc-group-title {
        font-family:"Syne",sans-serif !important;
        font-size:1.05rem !important; font-weight:700 !important;
        color:#0D1B2A !important;
        letter-spacing:-0.01em !important;
        margin-bottom:16px !important;
        display:flex; align-items:center; gap:10px;
    }
    .lsb-ci-svc-group-title span {
        font-size:1.2rem;
    }
    .lsb-ci-svc-grid {
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
        gap:12px;
    }
    .lsb-ci-svc-card {
        background:#ffffff;
        border:1px solid #E4EAF2;
        border-radius:12px;
        padding:18px 20px;
        text-decoration:none;
        display:flex; align-items:center; gap:10px;
        transition:all .25s;
        color:#0D1B2A !important;
        font-family:"DM Sans",sans-serif;
        font-size:0.88rem; font-weight:500;
    }
    .lsb-ci-svc-card::before { content:"→"; color:#00C9A7; font-size:0.9rem; flex-shrink:0; }
    .lsb-ci-svc-card:hover {
        border-color:rgba(0,201,167,0.4);
        box-shadow:0 6px 20px rgba(0,201,167,0.08);
        transform:translateX(4px);
        color:#00A88C !important;
    }

    /* ============================
       SECTION 4 — BUSINESSES
    ============================ */
    .lsb-ci-biz-section { background:#ffffff; padding:80px 40px; }
    .lsb-ci-biz-header  { max-width:680px; margin-bottom:48px; }
    .lsb-ci-biz-count-tag {
        display:inline-flex; align-items:center; gap:6px;
        background:rgba(0,201,167,0.08);
        border:1px solid rgba(0,201,167,0.2);
        color:#00A88C;
        font-size:0.78rem; font-weight:600;
        padding:4px 12px; border-radius:100px;
        font-family:"DM Sans",sans-serif;
        margin-top:12px;
    }

    /* Featured cards */
    .lsb-ci-biz-cards   { display:flex; flex-direction:column; gap:16px; margin-bottom:48px; }
    .lsb-ci-biz-card {
        background:#F5F7FA;
        border:1px solid #E4EAF2;
        border-radius:16px;
        padding:28px 32px;
        display:flex; align-items:center; gap:24px;
        transition:border-color .25s, box-shadow .25s;
    }
    .lsb-ci-biz-card:hover {
        border-color:rgba(0,201,167,0.4) !important;
        box-shadow:0 8px 32px rgba(0,0,0,0.07) !important;
    }
    .lsb-ci-biz-logo {
        width:72px; height:72px;
        border-radius:14px; overflow:hidden;
        flex-shrink:0; background:#0D1B2A;
        display:flex; align-items:center; justify-content:center;
    }
    .lsb-ci-biz-logo img { width:100%; height:100%; object-fit:cover; display:block; }
    .lsb-ci-biz-logo--initials {
        font-family:"Syne",sans-serif;
        font-weight:800; font-size:1.2rem; color:#00C9A7;
    }
    .lsb-ci-biz-body { flex:1; min-width:0; display:flex; flex-direction:column; gap:8px; }
    .lsb-ci-biz-meta-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
    .lsb-ci-biz-name {
        font-family:"Syne",sans-serif !important;
        font-size:1.1rem !important; font-weight:700 !important;
        color:#0D1B2A !important;
        letter-spacing:-0.01em !important; margin:0 !important;
    }
    .lsb-ci-biz-rating { display:flex; align-items:center; gap:5px; flex-shrink:0; }
    .lsb-ci-biz-stars  { display:flex; gap:1px; line-height:1; }
    .lsb-ci-star--full,
    .lsb-ci-star--half  { color:#F4C542; font-size:1rem; }
    .lsb-ci-star--empty { color:#C8D4E0; font-size:1rem; }
    .lsb-ci-biz-rating-num {
        font-family:"Syne",sans-serif;
        font-size:0.9rem; font-weight:700; color:#0D1B2A !important;
    }
    .lsb-ci-biz-desc {
        font-size:0.9rem !important; color:#3D4F63 !important;
        line-height:1.6 !important;
        font-family:"DM Sans",sans-serif; margin:0 !important;
        display:-webkit-box;
        -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    }
    .lsb-ci-biz-location {
        font-size:0.82rem !important; color:#8A9BB0 !important;
        font-family:"DM Sans",sans-serif; margin:0 !important;
    }
    .lsb-ci-biz-cta-btn {
        display:inline-block;
        background:#0D1B2A !important; color:#ffffff !important;
        font-family:"Syne",sans-serif !important;
        font-weight:700 !important; font-size:0.85rem !important;
        padding:12px 24px !important; border-radius:8px !important;
        text-decoration:none !important;
        letter-spacing:0.02em !important;
        transition:background .2s, color .2s !important;
        white-space:nowrap; flex-shrink:0;
    }
    .lsb-ci-biz-cta-btn:hover { background:#00C9A7 !important; color:#0D1B2A !important; }

    /* More businesses list */
    .lsb-ci-more-heading {
        font-family:"Syne",sans-serif !important;
        font-size:1.15rem !important; font-weight:700 !important;
        color:#0D1B2A !important;
        letter-spacing:-0.01em !important;
        margin-bottom:20px !important;
    }
    .lsb-ci-more-list {
        list-style:none; padding:0; margin:0;
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
        gap:12px;
    }
    .lsb-ci-more-item {
        background:#F5F7FA;
        border:1px solid #E4EAF2;
        border-radius:10px;
        padding:16px 20px;
        display:flex; align-items:center;
        justify-content:space-between;
        gap:16px;
        transition:border-color .2s;
    }
    .lsb-ci-more-item:hover { border-color:rgba(0,201,167,0.3); }
    .lsb-ci-more-name {
        font-family:"Syne",sans-serif;
        font-size:0.9rem; font-weight:700;
        color:#0D1B2A; letter-spacing:-0.01em;
    }
    .lsb-ci-more-phone {
        font-family:"DM Sans",sans-serif;
        font-size:0.85rem; font-weight:500;
        color:#00A88C; white-space:nowrap;
        text-decoration:none;
    }
    .lsb-ci-more-phone:hover { color:#0D1B2A; }

    /* ============================
       SECTION 5 — LOCAL TIPS
    ============================ */
    .lsb-ci-tips-section { background:#F5F7FA; padding:80px 40px; }
    .lsb-ci-tips-header  { max-width:680px; margin-bottom:48px; }
    .lsb-ci-tips-grid {
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
        gap:16px;
    }
    .lsb-ci-tip-card {
        background:#ffffff;
        border:1px solid #E4EAF2;
        border-radius:14px;
        padding:28px 24px;
        display:flex; align-items:flex-start; gap:18px;
        transition:border-color .25s, box-shadow .25s, transform .25s;
        position:relative; overflow:hidden;
    }
    .lsb-ci-tip-card::after {
        content:"";
        position:absolute; top:0; left:0; right:0;
        height:3px; background:#00C9A7;
        transform:scaleX(0); transform-origin:left;
        transition:transform .25s;
    }
    .lsb-ci-tip-card:hover { border-color:rgba(0,201,167,0.35); box-shadow:0 8px 28px rgba(0,0,0,0.06); transform:translateY(-3px); }
    .lsb-ci-tip-card:hover::after { transform:scaleX(1); }
    .lsb-ci-tip-icon {
        width:48px; height:48px;
        background:rgba(0,201,167,0.08);
        border-radius:12px;
        display:flex; align-items:center; justify-content:center;
        font-size:1.4rem; flex-shrink:0;
    }
    .lsb-ci-tip-body { flex:1; min-width:0; }
    .lsb-ci-tip-title {
        font-family:"Syne",sans-serif !important;
        font-size:0.97rem !important; font-weight:700 !important;
        color:#0D1B2A !important;
        letter-spacing:-0.01em !important;
        margin-bottom:8px !important; line-height:1.3 !important;
    }
    .lsb-ci-tip-desc {
        font-size:0.88rem !important; color:#3D4F63 !important;
        line-height:1.7 !important;
        font-family:"DM Sans",sans-serif !important;
        font-weight:400 !important; margin:0 !important;
    }
    .lsb-ci-tip-source {
        font-size:0.7rem; color:#8A9BB0;
        font-family:"DM Sans",sans-serif;
        text-transform:uppercase; letter-spacing:0.08em;
        margin-top:10px;
    }

    /* ============================
       SECTION 6 — COMMON PROBLEMS
    ============================ */
    .lsb-ci-prob-section { background:#ffffff; padding:80px 40px; }
    .lsb-ci-prob-header  { max-width:680px; margin-bottom:48px; }
    .lsb-ci-prob-grid {
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(340px,1fr));
        gap:16px;
    }
    .lsb-ci-prob-card {
        background:#F5F7FA;
        border:1px solid #E4EAF2;
        border-radius:14px;
        padding:28px 24px;
        transition:border-color .25s, box-shadow .25s;
    }
    .lsb-ci-prob-card:hover { border-color:rgba(0,201,167,0.3); box-shadow:0 8px 24px rgba(0,0,0,0.05); }
    .lsb-ci-prob-title {
        font-family:"Syne",sans-serif !important;
        font-size:0.97rem !important; font-weight:700 !important;
        color:#0D1B2A !important;
        letter-spacing:-0.01em !important;
        margin-bottom:10px !important; line-height:1.35 !important;
    }
    .lsb-ci-prob-desc {
        font-size:0.88rem !important; color:#3D4F63 !important;
        line-height:1.7 !important;
        font-family:"DM Sans",sans-serif !important;
        font-weight:400 !important; margin:0 !important;
    }

    /* ============================
       SECTION 7 — WEATHER
    ============================ */
    .lsb-ci-weather-section {
        background:#0D1B2A;
        padding:80px 40px;
        position:relative; overflow:hidden;
    }
    .lsb-ci-weather-grid-bg {
        position:absolute; inset:0; pointer-events:none;
        background-image:
            linear-gradient(rgba(0,201,167,0.03) 1px,transparent 1px),
            linear-gradient(90deg,rgba(0,201,167,0.03) 1px,transparent 1px);
        background-size:60px 60px;
    }
    .lsb-ci-weather-inner   { max-width:1200px; margin:0 auto; position:relative; z-index:1; }
    .lsb-ci-weather-header  { max-width:680px; margin-bottom:40px; }
    .lsb-ci-weather-grid {
        display:grid;
        grid-template-columns:280px 1fr;
        gap:24px; align-items:start;
    }
    .lsb-ci-weather-context {
        background:rgba(0,201,167,0.08);
        border:1px solid rgba(0,201,167,0.2);
        border-radius:12px;
        padding:16px 20px;
        margin-top:20px;
        font-family:"DM Sans",sans-serif;
        font-size:0.85rem !important;
        color:rgba(255,255,255,0.6) !important;
        line-height:1.6 !important;
    }
    .lsb-ci-weather-context strong { color:#00C9A7 !important; font-weight:600 !important; }
    .lsb-ci-weather-current {
        background:rgba(255,255,255,0.05);
        border:1px solid rgba(255,255,255,0.08);
        border-radius:20px; padding:32px 28px;
        display:flex; flex-direction:column; gap:16px;
        position:relative; overflow:hidden;
    }
    .lsb-ci-weather-current::before {
        content:""; position:absolute;
        top:-60px; right:-60px;
        width:200px; height:200px;
        background:radial-gradient(circle,rgba(0,201,167,0.12) 0%,transparent 70%);
        pointer-events:none;
    }
    .lsb-ci-weather-icon-row { display:flex; align-items:center; gap:14px; }
    .lsb-ci-weather-icon     { font-size:3rem; line-height:1; }
    .lsb-ci-weather-temp {
        font-family:"Syne",sans-serif;
        font-size:3.2rem; font-weight:800;
        color:#F5F7FA; letter-spacing:-0.04em; line-height:1;
    }
    .lsb-ci-weather-temp sup    { font-size:1.4rem; font-weight:600; vertical-align:super; }
    .lsb-ci-weather-condition   { font-family:"DM Sans",sans-serif; font-size:1rem; font-weight:500; color:#00C9A7; text-transform:capitalize; }
    .lsb-ci-weather-city-label  { font-family:"DM Sans",sans-serif; font-size:0.78rem; color:rgba(255,255,255,0.3); letter-spacing:0.08em; text-transform:uppercase; margin-top:-6px; }
    .lsb-ci-weather-details     { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding-top:16px; border-top:1px solid rgba(255,255,255,0.08); }
    .lsb-ci-weather-detail      { display:flex; flex-direction:column; gap:3px; }
    .lsb-ci-weather-detail-lbl  { font-family:"DM Sans",sans-serif; font-size:0.7rem; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; }
    .lsb-ci-weather-detail-val  { font-family:"Syne",sans-serif; font-size:0.95rem; font-weight:700; color:#F5F7FA; }
    .lsb-ci-weather-updated     { font-family:"DM Sans",sans-serif; font-size:0.7rem; color:rgba(255,255,255,0.2); margin-top:4px; }
    .lsb-ci-forecast-wrap       { display:flex; flex-direction:column; gap:10px; }
    .lsb-ci-forecast-label      { font-family:"DM Sans",sans-serif; font-size:0.72rem; font-weight:600; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:4px; }
    .lsb-ci-forecast-row {
        display:grid;
        grid-template-columns:80px 1fr auto auto;
        align-items:center; gap:16px;
        background:rgba(255,255,255,0.04);
        border:1px solid rgba(255,255,255,0.08);
        border-radius:12px; padding:14px 20px;
        transition:border-color .2s;
    }
    .lsb-ci-forecast-row:hover  { border-color:rgba(0,201,167,0.3); }
    .lsb-ci-forecast-day        { font-family:"Syne",sans-serif; font-size:0.88rem; font-weight:700; color:#F5F7FA; }
    .lsb-ci-forecast-desc       { font-family:"DM Sans",sans-serif; font-size:0.82rem; color:rgba(255,255,255,0.4); text-transform:capitalize; }
    .lsb-ci-forecast-icon       { font-size:1.3rem; }
    .lsb-ci-forecast-temps      { font-family:"Syne",sans-serif; font-size:0.88rem; font-weight:700; color:#F5F7FA; text-align:right; white-space:nowrap; }
    .lsb-ci-forecast-temps span { font-weight:400; color:rgba(255,255,255,0.4); margin-left:6px; }

    /* ============================
       SECTION 8 — WHY HIRE A PRO
    ============================ */
    .lsb-ci-why-section { background:#F5F7FA; padding:80px 40px; }
    .lsb-ci-why-header  { max-width:680px; margin-bottom:48px; }
    .lsb-ci-why-intro {
        font-size:1rem !important; color:#3D4F63 !important;
        line-height:1.8; font-family:"DM Sans",sans-serif;
        margin-top:12px;
    }
    .lsb-ci-why-grid {
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
        gap:16px; margin-bottom:32px;
    }
    .lsb-ci-why-card {
        background:#ffffff;
        border:1px solid #E4EAF2;
        border-radius:14px; padding:28px 24px;
        display:flex; align-items:flex-start; gap:16px;
        transition:border-color .25s, box-shadow .25s;
    }
    .lsb-ci-why-card:hover { border-color:rgba(0,201,167,0.3); box-shadow:0 8px 24px rgba(0,0,0,0.05); }
    .lsb-ci-why-check {
        width:32px; height:32px;
        background:#00C9A7; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        font-size:0.9rem; color:#0D1B2A; font-weight:700;
        flex-shrink:0; margin-top:2px;
    }
    .lsb-ci-why-point {
        font-family:"DM Sans",sans-serif !important;
        font-size:0.92rem !important; color:#0D1B2A !important;
        line-height:1.6 !important; font-weight:500 !important;
        margin:0 !important;
    }
    .lsb-ci-why-disclaimer {
        font-size:0.8rem !important; color:#8A9BB0 !important;
        font-style:italic !important;
        font-family:"DM Sans",sans-serif !important;
        line-height:1.6 !important; margin-top:8px !important;
        padding:16px 20px;
        background:#ffffff; border:1px solid #E4EAF2;
        border-radius:10px;
    }

    /* ============================
       SECTION 9 — FAQ
    ============================ */
    .lsb-ci-faq-section { background:#ffffff; padding:80px 40px; }
    .lsb-ci-faq-inner   { max-width:780px; margin:0 auto; }
    .lsb-ci-faq-header  { margin-bottom:48px; }
    .lsb-ci-faq-list    { display:flex; flex-direction:column; gap:12px; }
    .lsb-ci-faq-item {
        background:#F5F7FA;
        border:1px solid #E4EAF2;
        border-radius:14px; overflow:hidden;
        transition:border-color .25s;
    }
    .lsb-ci-faq-item.is-open { border-color:rgba(0,201,167,0.35); }
    .lsb-ci-faq-trigger {
        width:100%; display:flex; align-items:center;
        justify-content:space-between; gap:16px;
        padding:22px 24px;
        background:transparent; border:none;
        cursor:pointer; text-align:left;
        transition:background .2s;
    }
    .lsb-ci-faq-trigger:hover              { background:#EEF2F7; }
    .lsb-ci-faq-item.is-open .lsb-ci-faq-trigger { background:#EEF2F7; }
    .lsb-ci-faq-question {
        font-family:"Syne",sans-serif !important;
        font-size:0.97rem !important; font-weight:700 !important;
        color:#0D1B2A !important;
        letter-spacing:-0.01em !important; line-height:1.4 !important;
        margin:0 !important;
    }
    .lsb-ci-faq-icon {
        width:30px; height:30px;
        background:#ffffff; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        flex-shrink:0; color:#00C9A7;
        font-size:1.1rem; line-height:1;
        transition:background .2s, transform .25s;
        border:1px solid #E4EAF2;
    }
    .lsb-ci-faq-item.is-open .lsb-ci-faq-icon {
        transform:rotate(45deg);
        background:rgba(0,201,167,0.1);
        border-color:rgba(0,201,167,0.3);
    }
    .lsb-ci-faq-body   { display:none; padding:0 24px 24px; }
    .lsb-ci-faq-item.is-open .lsb-ci-faq-body { display:block; }
    .lsb-ci-faq-answer {
        font-family:"DM Sans",sans-serif;
        font-size:0.92rem !important; color:#3D4F63 !important;
        line-height:1.8 !important;
        padding-top:4px; padding-bottom:18px;
        border-bottom:1px solid #E4EAF2;
        margin-bottom:16px;
    }
    .lsb-ci-faq-answer p { font-size:0.92rem !important; color:#3D4F63 !important; line-height:1.8 !important; margin-bottom:10px !important; }
    .lsb-ci-faq-answer p:last-child { margin-bottom:0 !important; }
    .lsb-ci-faq-answer strong { color:#0D1B2A !important; font-weight:600 !important; }
    .lsb-ci-faq-cta-row {
        display:flex; align-items:center;
        justify-content:space-between; gap:16px; flex-wrap:wrap;
    }
    .lsb-ci-faq-learn-more {
        display:inline-flex; align-items:center; gap:6px;
        font-family:"Syne",sans-serif !important;
        font-size:0.82rem !important; font-weight:700 !important;
        color:#00A88C !important; text-decoration:none !important;
        letter-spacing:0.02em !important;
        transition:gap .2s, color .2s !important;
    }
    .lsb-ci-faq-learn-more::after { content:"→"; color:#00C9A7; }
    .lsb-ci-faq-learn-more:hover  { color:#0D1B2A !important; gap:10px; }
    .lsb-ci-faq-disclaimer {
        font-size:0.75rem !important; color:#8A9BB0 !important;
        font-style:italic !important;
        font-family:"DM Sans",sans-serif !important;
        margin:0 !important; flex:1; text-align:right;
    }

    /* ============================
       SECTION 10 — NEARBY CITIES
    ============================ */
    .lsb-ci-nearby-section { background:#F5F7FA; padding:80px 40px; }
    .lsb-ci-nearby-header  { max-width:680px; margin-bottom:48px; }
    .lsb-ci-nearby-grid {
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
        gap:16px;
    }
    .lsb-ci-nearby-card {
        background:#ffffff;
        border:1px solid #E4EAF2;
        border-radius:14px; padding:24px 20px;
        text-decoration:none;
        display:flex; flex-direction:column; gap:10px;
        transition:all .25s;
        position:relative; overflow:hidden;
    }
    .lsb-ci-nearby-card::before {
        content:""; position:absolute;
        bottom:0; left:0; right:0; height:3px;
        background:#00C9A7;
        transform:scaleX(0); transform-origin:left;
        transition:transform .25s;
    }
    .lsb-ci-nearby-card:hover { border-color:rgba(0,201,167,0.4); transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,201,167,0.08); }
    .lsb-ci-nearby-card:hover::before { transform:scaleX(1); }
    .lsb-ci-nearby-icon  { font-size:1.3rem; }
    .lsb-ci-nearby-name {
        font-family:"Syne",sans-serif !important;
        font-size:1rem !important; font-weight:700 !important;
        color:#0D1B2A !important; letter-spacing:-0.01em !important;
    }
    .lsb-ci-nearby-label {
        font-size:0.8rem; color:#00A88C;
        font-family:"DM Sans",sans-serif; font-weight:500;
    }
    .lsb-ci-nearby-arrow {
        font-size:0.82rem; color:#8A9BB0;
        font-family:"DM Sans",sans-serif;
        margin-top:auto;
        transition:color .2s;
    }
    .lsb-ci-nearby-card:hover .lsb-ci-nearby-arrow { color:#00A88C; }

    /* ============================
       SECTION 11 — FINAL CTA
    ============================ */
    .lsb-ci-cta-section {
        background:#0D1B2A;
        padding:100px 40px;
        position:relative; overflow:hidden;
    }
    .lsb-ci-cta-grid-bg {
        position:absolute; inset:0; pointer-events:none;
        background-image:
            linear-gradient(rgba(0,201,167,0.04) 1px,transparent 1px),
            linear-gradient(90deg,rgba(0,201,167,0.04) 1px,transparent 1px);
        background-size:60px 60px;
    }
    .lsb-ci-cta-glow-1 {
        position:absolute; pointer-events:none;
        width:500px; height:500px;
        background:radial-gradient(circle,rgba(0,201,167,0.10) 0%,transparent 70%);
        top:-120px; right:-80px;
    }
    .lsb-ci-cta-glow-2 {
        position:absolute; pointer-events:none;
        width:400px; height:400px;
        background:radial-gradient(circle,rgba(244,197,66,0.06) 0%,transparent 70%);
        bottom:-80px; left:-60px;
    }
    .lsb-ci-cta-inner {
        max-width:680px; margin:0 auto;
        text-align:center; position:relative; z-index:1;
    }
    .lsb-ci-cta-heading {
        font-family:"Syne",sans-serif !important;
        font-size:clamp(2rem,4vw,3rem) !important;
        font-weight:800 !important; color:#F5F7FA !important;
        letter-spacing:-0.02em !important; line-height:1.1 !important;
        margin-bottom:20px !important;
    }
    .lsb-ci-cta-heading em { font-style:normal; color:#00C9A7; }
    .lsb-ci-cta-sub {
        font-size:1rem !important; color:rgba(255,255,255,0.55) !important;
        line-height:1.8 !important;
        font-family:"DM Sans",sans-serif !important;
        font-weight:300 !important; margin-bottom:40px !important;
    }
    .lsb-ci-cta-btn {
        display:inline-flex; align-items:center; gap:8px;
        background:#00C9A7 !important; color:#0D1B2A !important;
        font-family:"Syne",sans-serif !important;
        font-weight:700 !important; font-size:0.95rem !important;
        padding:16px 36px !important; border-radius:8px !important;
        text-decoration:none !important;
        letter-spacing:0.02em !important;
        transition:background .2s, transform .15s !important;
    }
    .lsb-ci-cta-btn::after { content:"→"; font-size:1rem; transition:transform .2s; }
    .lsb-ci-cta-btn:hover  { background:#00A88C !important; transform:translateY(-2px); }
    .lsb-ci-cta-btn:hover::after { transform:translateX(4px); }

    /* ============================
       RESPONSIVE
    ============================ */
    @media (max-width:1024px) {
        .lsb-ci-context-inner { grid-template-columns:1fr; gap:36px; }
        .lsb-ci-context-left  { position:static; }
    }
    @media (max-width:900px) {
        .lsb-ci-weather-grid  { grid-template-columns:1fr; }
    }
    @media (max-width:768px) {
        .lsb-ci-breadcrumb-bar { padding:0 20px; }
        .lsb-ci-hero,
        .lsb-ci-context-section,
        .lsb-ci-svc-section,
        .lsb-ci-biz-section,
        .lsb-ci-tips-section,
        .lsb-ci-prob-section,
        .lsb-ci-weather-section,
        .lsb-ci-why-section,
        .lsb-ci-faq-section,
        .lsb-ci-nearby-section,
        .lsb-ci-cta-section    { padding:56px 20px; }
        .lsb-ci-biz-card {
            flex-direction:column; align-items:flex-start;
            gap:16px; padding:24px;
        }
        .lsb-ci-biz-cta-btn { width:100% !important; text-align:center !important; }
        .lsb-ci-faq-cta-row { flex-direction:column; align-items:flex-start; gap:10px; }
        .lsb-ci-faq-disclaimer { text-align:left; }
        .lsb-ci-nearby-grid { grid-template-columns:repeat(2,1fr); }
        .lsb-ci-forecast-row { grid-template-columns:70px 1fr auto; }
        .lsb-ci-forecast-icon { display:none; }
        .lsb-ci-cta-btn { width:100%; justify-content:center; }
    }
    ';
    $out .= '</style>';

    // ============================================================
    // SCHEMA — FAQ
    // ============================================================
    if ( ! empty( $faq_schema ) ) {
        $out .= '<script type="application/ld+json">';
        $out .= wp_json_encode( [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $faq_schema,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        $out .= '</script>';
    }

    // ============================================================
    // SCHEMA — LocalBusiness list
    // ============================================================
    if ( ! empty( $biz_schema_items ) ) {
        $out .= '<script type="application/ld+json">';
        $out .= wp_json_encode( [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => $industry_name . ' Businesses in ' . $city_name,
            'itemListElement' => $biz_schema_items,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        $out .= '</script>';
    }

    // ============================================================
    // BREADCRUMB
    // ============================================================
    $out .= '<nav class="lsb-ci-breadcrumb-bar" aria-label="Breadcrumb">';
    $out .= '<ol class="lsb-ci-breadcrumb">';
    $out .= '<li><a href="' . esc_url( $home_url ) . '">Home</a></li>';
    $out .= '<li class="lsb-ci-bc-sep" aria-hidden="true">›</li>';
    $out .= '<li><a href="' . esc_url( $home_url . 'cities/' ) . '">Cities</a></li>';
    $out .= '<li class="lsb-ci-bc-sep" aria-hidden="true">›</li>';
    $out .= '<li><a href="' . esc_url( $city_url ) . '">' . esc_html( $city_name ) . '</a></li>';
    $out .= '<li class="lsb-ci-bc-sep" aria-hidden="true">›</li>';
    $out .= '<li class="lsb-ci-bc-current" aria-current="page">' . esc_html( $industry_name ) . '</li>';
    $out .= '</ol>';
    $out .= '</nav>';

    // ============================================================
    // SECTION 1 — HERO
    // ============================================================
    $hero_sub = $city_hero_intro
        ? $city_hero_intro
        : 'Find trusted ' . $industry_name . ' professionals serving ' . $city_name . ', CA. Browse verified local companies, compare services, and connect directly — no middleman, no fees.';

    $out .= '<section class="lsb-ci-hero lsb-ci-dark" aria-labelledby="lsb-ci-h1">';
    $out .= '<div class="lsb-ci-hero-grid" aria-hidden="true"></div>';
    $out .= '<div class="lsb-ci-hero-glow-1" aria-hidden="true"></div>';
    $out .= '<div class="lsb-ci-hero-glow-2" aria-hidden="true"></div>';
    $out .= '<div class="lsb-ci-hero-inner">';

    // Badge
    $out .= '<div class="lsb-ci-hero-badge">';
    $out .= '<span class="lsb-ci-hero-badge-dot" aria-hidden="true"></span>';
    $out .= '<span>' . esc_html( $industry_name ) . ' · ' . esc_html( $city_name ) . ', CA</span>';
    $out .= '</div>';

    // H1
    $out .= '<h1 class="lsb-ci-h1" id="lsb-ci-h1">';
    $out .= '<span>' . esc_html( $industry_name ) . ' Services</span>';
    $out .= '<em>in ' . esc_html( $city_name ) . ', CA</em>';
    $out .= '</h1>';

    // Subtext
    $out .= '<p class="lsb-ci-hero-sub">' . esc_html( $hero_sub ) . '</p>';

    // Stat pills
    $out .= '<div class="lsb-ci-hero-pills">';
    if ( $total_business_count > 0 ) {
        $out .= '<div class="lsb-ci-hero-pill">';
        $out .= '<span class="lsb-ci-hero-pill-dot" aria-hidden="true"></span>';
        $out .= '<strong>' . esc_html( $total_business_count ) . '</strong>';
        $out .= $total_business_count === 1 ? ' Business Listed' : ' Businesses Listed';
        $out .= '</div>';
    }
    if ( $service_count > 0 ) {
        $out .= '<div class="lsb-ci-hero-pill">';
        $out .= '<span class="lsb-ci-hero-pill-dot" aria-hidden="true"></span>';
        $out .= '<strong>' . esc_html( $service_count ) . '</strong>';
        $out .= $service_count === 1 ? ' Service' : ' Services';
        $out .= '</div>';
    }
    $out .= '<div class="lsb-ci-hero-pill">';
    $out .= '<span class="lsb-ci-hero-pill-dot" aria-hidden="true"></span>';
    $out .= '<strong>' . esc_html( $city_name ) . '</strong>, CA';
    $out .= '</div>';
    $out .= '</div>'; // end pills

    // CTAs
    $out .= '<div class="lsb-ci-hero-ctas">';
    $out .= '<a href="#lsb-ci-businesses" class="lsb-ci-btn-primary">Browse ' . esc_html( $industry_name ) . ' Businesses</a>';
    $out .= '<a href="#lsb-ci-services" class="lsb-ci-btn-secondary">View All Services</a>';
    $out .= '</div>';

    $out .= '</div>'; // end hero-inner
    $out .= '</section>';

    // ============================================================
    // SECTION 2 — CITY + INDUSTRY CONTEXT
    // ============================================================
    $has_city_context     = ! empty( $why_city_content );
    $has_industry_context = ! empty( $industry_overview );

    if ( $has_city_context || $has_industry_context ) {
        $out .= '<section class="lsb-ci-context-section lsb-ci-light" id="lsb-ci-context">';
        $out .= '<div class="lsb-ci-context-grid-bg" aria-hidden="true"></div>';
        $out .= '<div class="lsb-ci-context-glow" aria-hidden="true"></div>';
        $out .= '<div class="lsb-ci-context-inner">';

        // Left col
        $out .= '<div class="lsb-ci-context-left">';
        $out .= '<span class="lsb-ci-context-eyebrow">Local + Industry Insight</span>';
        $out .= '<h2 class="lsb-ci-context-heading">Why <em>' . esc_html( $city_name ) . '</em> Homeowners Rely on ' . esc_html( $industry_name ) . ' Professionals</h2>';
        $out .= '<p class="lsb-ci-context-subtext">What makes ' . esc_html( $industry_name ) . ' services specifically important in ' . esc_html( $city_name ) . ' — local conditions, housing stock, and what to expect.</p>';
        $out .= '</div>';

        // Right col — content
        $out .= '<div class="lsb-ci-context-content">';

        if ( $has_city_context ) {
            $out .= '<span class="lsb-ci-context-source-tag">📍 About ' . esc_html( $city_name ) . '</span>';
            $out .= wp_kses_post( $why_city_content );
        }

        if ( $has_city_context && $has_industry_context ) {
            $out .= '<hr class="lsb-ci-context-divider">';
        }

        if ( $has_industry_context ) {
            $out .= '<span class="lsb-ci-context-source-tag">🔧 About ' . esc_html( $industry_name ) . ' Services</span>';
            $out .= wp_kses_post( $industry_overview );
        }

        $out .= '</div>'; // end content
        $out .= '</div>'; // end inner
        $out .= '</section>';
    }

    // ============================================================
    // SECTION 3 — SERVICES GRID (grouped parent/child layout)
    // ============================================================
    if ( ! empty( $grouped_services ) ) {
        $out .= '<section class="lsb-ci-svc-section lsb-ci-light" id="lsb-ci-services">';
        $out .= '<div class="lsb-ci-inner">';

        $out .= '<div class="lsb-ci-svc-header">';
        $out .= '<span class="lsb-ci-section-label">What We Offer</span>';
        $out .= '<h2 class="lsb-ci-h2">' . esc_html( $industry_name ) . ' Services Available in <em>' . esc_html( $city_name ) . '</em></h2>';
        $out .= '<p class="lsb-ci-svc-intro">Explore the full range of ' . esc_html( $industry_name ) . ' services available through our network of verified professionals serving ' . esc_html( $city_name ) . ' and surrounding areas.</p>';
        $out .= '</div>';

        $out .= '<div class="lsb-ci-svc-grid">';
        foreach ( $grouped_services as $grp ) {
            $grp_url  = $grp['url'];
            $grp_icon = $grp['icon'];
            $grp_desc = $grp['desc'];
            $grp_name = $grp['term']->name ?? '';

            $out .= '<div class="lsb-ci-svc-card lsb-ci-svc-card--grouped">';

            // Parent header
            $out .= '<div class="lsb-ci-svc-parent">';
            $out .= '<div class="lsb-ci-svc-parent-top">';
            $out .= '<div class="lsb-ci-svc-card-icon">';
            if ( $grp_icon ) {
                $out .= '<img src="' . esc_url( $grp_icon ) . '" alt="' . esc_attr( $grp_name ) . '">';
            } else {
                $out .= esc_html( $industry_icon );
            }
            $out .= '</div>';
            $out .= '<a href="' . esc_url( $grp_url ) . '" class="lsb-ci-svc-parent-name">' . esc_html( $grp_name ) . '</a>';
            $out .= '</div>'; // end parent-top
            if ( $grp_desc ) {
                $out .= '<p class="lsb-ci-svc-parent-desc">' . esc_html( $grp_desc ) . '</p>';
            }
            $out .= '</div>'; // end svc-parent

            // Children list
            if ( ! empty( $grp['children'] ) ) {
                $out .= '<ul class="lsb-ci-svc-children">';
                foreach ( $grp['children'] as $child ) {
                    $child_name = $child['term']->name ?? '';
                    $child_url  = $child['url'] ?? '#';
                    $out .= '<li class="lsb-ci-svc-child">';
                    $out .= '<a href="' . esc_url( $child_url ) . '" class="lsb-ci-svc-child-link">';
                    $out .= '<span class="lsb-ci-svc-child-arrow">→</span>';
                    $out .= esc_html( $child_name );
                    $out .= '</a>';
                    $out .= '</li>';
                }
                $out .= '</ul>';
            }

            // Learn more CTA
            $out .= '<a href="' . esc_url( $grp_url ) . '" class="lsb-ci-svc-card-cta">Learn More →</a>';

            $out .= '</div>'; // end svc-card--grouped
        }
        $out .= '</div>'; // end svc-grid

        $out .= '</div>'; // end inner
        $out .= '</section>';

        // Grouped card styles — same as industry page
        $out .= '<style>
        .lsb-ci-svc-card--grouped { display:flex; flex-direction:column; gap:0; padding:0; overflow:hidden; background:#ffffff; border:1px solid #E4EAF2; border-radius:14px; transition:all .25s; position:relative; }
        .lsb-ci-svc-card--grouped::before { content:""; position:absolute; bottom:0; left:0; right:0; height:3px; background:#00C9A7; transform:scaleX(0); transform-origin:left; transition:transform .25s; }
        .lsb-ci-svc-card--grouped:hover { border-color:rgba(0,201,167,0.35); transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,201,167,0.08); }
        .lsb-ci-svc-card--grouped:hover::before { transform:scaleX(1); }
        .lsb-ci-svc-parent { padding:24px 24px 16px; border-bottom:1px solid #E4EAF2; display:flex; flex-direction:column; gap:10px; }
        .lsb-ci-svc-parent-top { display:flex; align-items:center; gap:14px; }
        .lsb-ci-svc-card-icon { width:48px; height:48px; background:rgba(0,201,167,0.08); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; }
        .lsb-ci-svc-card-icon img { width:28px; height:28px; object-fit:contain; }
        .lsb-ci-svc-parent-name { font-family:"Syne",sans-serif !important; font-size:1rem !important; font-weight:700 !important; color:#0D1B2A !important; letter-spacing:-0.01em !important; line-height:1.3 !important; text-decoration:none !important; transition:color .2s !important; }
        .lsb-ci-svc-parent-name:hover { color:#00A88C !important; }
        .lsb-ci-svc-parent-desc { font-size:0.82rem !important; color:#3D4F63 !important; line-height:1.6 !important; font-family:"DM Sans",sans-serif !important; margin:0 !important; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .lsb-ci-svc-children { list-style:none !important; margin:0 !important; padding:12px 24px !important; display:flex !important; flex-direction:column !important; gap:2px !important; flex:1 !important; border-bottom:1px solid #E4EAF2 !important; }
        .lsb-ci-svc-child { margin:0 !important; padding:0 !important; }
        .lsb-ci-svc-child-link { display:flex !important; align-items:center !important; gap:8px !important; padding:7px 10px !important; border-radius:6px !important; text-decoration:none !important; font-size:0.875rem !important; font-family:"DM Sans",sans-serif !important; color:#3D4F63 !important; font-weight:400 !important; transition:background .15s, color .15s !important; }
        .lsb-ci-svc-child-link:hover { background:rgba(0,201,167,0.07) !important; color:#00A88C !important; }
        .lsb-ci-svc-child-arrow { color:#00C9A7 !important; font-size:0.78rem !important; flex-shrink:0 !important; transition:transform .15s !important; }
        .lsb-ci-svc-child-link:hover .lsb-ci-svc-child-arrow { transform:translateX(3px) !important; }
        .lsb-ci-svc-card-cta { display:inline-flex; align-items:center; gap:6px; font-size:0.82rem; font-weight:600; color:#00A88C !important; font-family:"DM Sans",sans-serif; padding:14px 24px !important; transition:gap .2s; text-decoration:none; }
        .lsb-ci-svc-card--grouped:hover .lsb-ci-svc-card-cta { gap:10px; }
        </style>';
    }

    // ============================================================
    // SECTION 4 — BUSINESSES
    // ============================================================
    if ( ! empty( $featured_posts ) || ! empty( $more_posts ) ) {
        $out .= '<section class="lsb-ci-biz-section lsb-ci-light" id="lsb-ci-businesses">';
        $out .= '<div class="lsb-ci-inner">';

        $out .= '<div class="lsb-ci-biz-header">';
        $out .= '<span class="lsb-ci-section-label">Featured Professionals</span>';
        $out .= '<h2 class="lsb-ci-h2">Featured <em>' . esc_html( $industry_name ) . '</em> Businesses in ' . esc_html( $city_name ) . '</h2>';
        if ( $total_business_count > 0 ) {
            $out .= '<span class="lsb-ci-biz-count-tag">' . esc_html( $total_business_count ) . ' ' . esc_html( $industry_name ) . ' ' . ( $total_business_count === 1 ? 'business' : 'businesses' ) . ' serving ' . esc_html( $city_name ) . '</span>';
        }
        $out .= '</div>';

        // Featured cards
        if ( ! empty( $featured_posts ) ) {
            $out .= '<div class="lsb-ci-biz-cards">';
            foreach ( $featured_posts as $biz ) {
                $biz_id     = $biz->ID;
                $biz_title  = $biz->post_title;
                $logo_field = function_exists( 'get_field' ) ? get_field( 'business_logo',              $biz_id ) : '';
                $short_desc = function_exists( 'get_field' ) ? get_field( 'business_short_description', $biz_id ) : '';
                $rating     = function_exists( 'get_field' ) ? get_field( 'business_rating',            $biz_id ) : '';
                if ( ! $short_desc ) $short_desc = get_the_excerpt( $biz_id );

                $logo_url = '';
                if ( $logo_field ) {
                    $logo_url = is_array( $logo_field )
                        ? ( $logo_field['url'] ?? '' )
                        : wp_get_attachment_image_url( $logo_field, 'thumbnail' );
                }
                if ( ! $logo_url ) $logo_url = get_the_post_thumbnail_url( $biz_id, 'thumbnail' );

                $initials = '';
                foreach ( array_slice( explode( ' ', $biz_title ), 0, 2 ) as $word ) {
                    $initials .= strtoupper( substr( $word, 0, 1 ) );
                }

                // Stars
                $stars_html = '';
                if ( $rating ) {
                    $rf = floatval( $rating );
                    $fs = floor( $rf );
                    $hs = ( $rf - $fs ) >= 0.5;
                    for ( $s = 1; $s <= 5; $s++ ) {
                        if      ( $s <= $fs )             { $stars_html .= '<span class="lsb-ci-star--full">★</span>'; }
                        elseif  ( $s === $fs + 1 && $hs ) { $stars_html .= '<span class="lsb-ci-star--half">★</span>'; }
                        else                              { $stars_html .= '<span class="lsb-ci-star--empty">☆</span>'; }
                    }
                }

                $out .= '<article class="lsb-ci-biz-card">';

                // Logo
                if ( $logo_url ) {
                    $out .= '<div class="lsb-ci-biz-logo"><img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $biz_title ) . ' logo" loading="lazy" width="72" height="72"></div>';
                } else {
                    $out .= '<div class="lsb-ci-biz-logo lsb-ci-biz-logo--initials" aria-hidden="true">' . esc_html( $initials ) . '</div>';
                }

                // Body
                $out .= '<div class="lsb-ci-biz-body">';
                $out .= '<div class="lsb-ci-biz-meta-row">';
                $out .= '<h3 class="lsb-ci-biz-name">' . esc_html( $biz_title ) . '</h3>';
                if ( $rating ) {
                    $out .= '<div class="lsb-ci-biz-rating" aria-label="Rating: ' . esc_attr( $rating ) . ' out of 5">';
                    $out .= '<span class="lsb-ci-biz-stars">' . $stars_html . '</span>';
                    $out .= '<span class="lsb-ci-biz-rating-num">' . esc_html( number_format( (float) $rating, 1 ) ) . '</span>';
                    $out .= '</div>';
                }
                $out .= '</div>';
                if ( $short_desc ) {
                    $out .= '<p class="lsb-ci-biz-desc">' . esc_html( wp_trim_words( $short_desc, 25, '…' ) ) . '</p>';
                }
                $out .= '<p class="lsb-ci-biz-location">📍 ' . esc_html( $city_name ) . ', CA</p>';
                $out .= '</div>'; // end body

                // CTA
                $out .= '<a href="' . esc_url( get_permalink( $biz_id ) ) . '" class="lsb-ci-biz-cta-btn" aria-label="View ' . esc_attr( $biz_title ) . ' profile">View Business</a>';

                $out .= '</article>';
            }
            $out .= '</div>'; // end biz-cards
        }

        // More businesses list
        if ( ! empty( $more_posts ) ) {
            $out .= '<h3 class="lsb-ci-more-heading">More ' . esc_html( $industry_name ) . ' Businesses in ' . esc_html( $city_name ) . '</h3>';
            $out .= '<ul class="lsb-ci-more-list">';
            foreach ( $more_posts as $biz ) {
                $biz_id    = $biz->ID;
                $biz_title = $biz->post_title;
                $biz_phone = function_exists( 'get_field' ) ? get_field( 'business_phone', $biz_id ) : '';
                $out .= '<li class="lsb-ci-more-item">';
                $out .= '<span class="lsb-ci-more-name">' . esc_html( $biz_title ) . '</span>';
                if ( $biz_phone ) {
                    $out .= '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $biz_phone ) ) . '" class="lsb-ci-more-phone">' . esc_html( $biz_phone ) . '</a>';
                }
                $out .= '</li>';
            }
            $out .= '</ul>';
        }

        $out .= '</div>'; // end inner
        $out .= '</section>';
    }

    // ============================================================
    // SECTION 5 — LOCAL TIPS
    // ============================================================
    $all_tips = [];

    // City local_tips
    if ( ! empty( $local_tips ) && is_array( $local_tips ) ) {
        foreach ( $local_tips as $idx => $tip ) {
            $title = $tip['tip_title'] ?? '';
            $desc  = $tip['tip_description'] ?? '';
            $icon  = function_exists( 'lsb_resolve_tip_icon' ) ? lsb_resolve_tip_icon( $tip['tip_icon'] ?? '', $idx ) : ( $tip['tip_icon'] ?? '💡' );
            if ( ! $title && ! $desc ) continue;
            $all_tips[] = [
                'icon'   => $icon,
                'title'  => $title,
                'desc'   => $desc,
                'source' => $city_name,
            ];
        }
    }

    // Industry common problems repackaged as tips (first 3)
    if ( $industry_post_id ) {
        $prob_field = function_exists( 'get_field' ) ? get_field( 'common_problems', $industry_post_id ) : [];
        if ( ! empty( $prob_field ) && is_array( $prob_field ) ) {
            $count = 0;
            foreach ( $prob_field as $prob ) {
                if ( $count >= 3 ) break;
                $prob_title = $prob['problem_title'] ?? '';
                $prob_desc  = $prob['problem_description'] ?? '';
                if ( ! $prob_title ) continue;
                $all_tips[] = [
                    'icon'   => '⚠️',
                    'title'  => 'Watch Out: ' . $prob_title,
                    'desc'   => $prob_desc,
                    'source' => $industry_name,
                ];
                $count++;
            }
        }
    }

    if ( ! empty( $all_tips ) ) {
        $out .= '<section class="lsb-ci-tips-section lsb-ci-light" id="lsb-ci-tips">';
        $out .= '<div class="lsb-ci-inner">';

        $out .= '<div class="lsb-ci-tips-header">';
        $out .= '<span class="lsb-ci-section-label">Insider Knowledge</span>';
        $out .= '<h2 class="lsb-ci-h2">' . esc_html( $industry_name ) . ' Tips for <em>' . esc_html( $city_name ) . '</em> Residents</h2>';
        $out .= '</div>';

        $out .= '<div class="lsb-ci-tips-grid">';
        foreach ( $all_tips as $tip ) {
            $out .= '<div class="lsb-ci-tip-card">';
            $out .= '<div class="lsb-ci-tip-icon" aria-hidden="true">' . esc_html( $tip['icon'] ) . '</div>';
            $out .= '<div class="lsb-ci-tip-body">';
            if ( $tip['title'] ) {
                $out .= '<h3 class="lsb-ci-tip-title">' . esc_html( $tip['title'] ) . '</h3>';
            }
            if ( $tip['desc'] ) {
                $out .= '<p class="lsb-ci-tip-desc">' . wp_kses_post( $tip['desc'] ) . '</p>';
            }
            $out .= '<span class="lsb-ci-tip-source">Source: ' . esc_html( $tip['source'] ) . '</span>';
            $out .= '</div>';
            $out .= '</div>';
        }
        $out .= '</div>'; // end tips-grid

        $out .= '</div>'; // end inner
        $out .= '</section>';
    }

    // ============================================================
    // SECTION 6 — COMMON PROBLEMS
    // ============================================================
    if ( $industry_post_id ) {
        $problems = function_exists( 'get_field' ) ? get_field( 'common_problems', $industry_post_id ) : [];
        if ( ! empty( $problems ) && is_array( $problems ) ) {
            $out .= '<section class="lsb-ci-prob-section lsb-ci-light" id="lsb-ci-problems">';
            $out .= '<div class="lsb-ci-inner">';

            $out .= '<div class="lsb-ci-prob-header">';
            $out .= '<span class="lsb-ci-section-label">Diagnose the Issue</span>';
            $out .= '<h2 class="lsb-ci-h2">Common ' . esc_html( $industry_name ) . ' Problems in <em>' . esc_html( $city_name ) . '</em></h2>';
            $out .= '</div>';

            $out .= '<div class="lsb-ci-prob-grid">';
            foreach ( $problems as $prob ) {
                $prob_title = $prob['problem_title'] ?? '';
                $prob_desc  = $prob['problem_description'] ?? '';
                if ( ! $prob_title ) continue;
                $out .= '<div class="lsb-ci-prob-card">';
                $out .= '<h3 class="lsb-ci-prob-title">' . esc_html( $prob_title ) . '</h3>';
                if ( $prob_desc ) {
                    $out .= '<p class="lsb-ci-prob-desc">' . wp_kses_post( $prob_desc ) . '</p>';
                }
                $out .= '</div>';
            }
            $out .= '</div>'; // end prob-grid

            $out .= '</div>'; // end inner
            $out .= '</section>';
        }
    }

    // ============================================================
    // SECTION 7 — WEATHER
    // ============================================================
    $has_weather = ! empty( $weather_data ) && isset( $weather_data['main'] );

    $out .= '<section class="lsb-ci-weather-section lsb-ci-dark" id="lsb-ci-weather">';
    $out .= '<div class="lsb-ci-weather-grid-bg" aria-hidden="true"></div>';
    $out .= '<div class="lsb-ci-weather-inner">';

    $out .= '<div class="lsb-ci-weather-header">';
    $out .= '<span class="lsb-ci-section-label">Current Conditions</span>';
    $out .= '<h2 class="lsb-ci-h2">Today\'s Weather in <em>' . esc_html( $city_name ) . '</em></h2>';
    $out .= '</div>';

    if ( $has_weather ) {
        $temp       = round( $weather_data['main']['temp'] );
        $feels_like = round( $weather_data['main']['feels_like'] );
        $humidity   = $weather_data['main']['humidity'];
        $wind_mph   = round( $weather_data['wind']['speed'] );
        $condition  = $weather_data['weather'][0]['description'] ?? '';
        $icon_code  = $weather_data['weather'][0]['icon'] ?? '01d';
        $emoji      = $weather_emoji( $icon_code );
        $updated    = date( 'g:i A' );

        $out .= '<div class="lsb-ci-weather-grid">';

        // Current conditions
        $out .= '<div class="lsb-ci-weather-current">';
        $out .= '<div class="lsb-ci-weather-icon-row">';
        $out .= '<span class="lsb-ci-weather-icon">' . $emoji . '</span>';
        $out .= '<div><div class="lsb-ci-weather-temp">' . esc_html( $temp ) . '<sup>°F</sup></div></div>';
        $out .= '</div>';
        $out .= '<div class="lsb-ci-weather-condition">' . esc_html( $condition ) . '</div>';
        $out .= '<div class="lsb-ci-weather-city-label">' . esc_html( $city_name ) . ', CA</div>';
        $out .= '<div class="lsb-ci-weather-details">';
        $out .= '<div class="lsb-ci-weather-detail"><span class="lsb-ci-weather-detail-lbl">Feels Like</span><span class="lsb-ci-weather-detail-val">' . esc_html( $feels_like ) . '°</span></div>';
        $out .= '<div class="lsb-ci-weather-detail"><span class="lsb-ci-weather-detail-lbl">Humidity</span><span class="lsb-ci-weather-detail-val">' . esc_html( $humidity ) . '%</span></div>';
        $out .= '<div class="lsb-ci-weather-detail"><span class="lsb-ci-weather-detail-lbl">Wind</span><span class="lsb-ci-weather-detail-val">' . esc_html( $wind_mph ) . ' mph</span></div>';
        $vis_m = $weather_data['visibility'] ?? 0;
        $vis   = $vis_m ? round( $vis_m / 1609.34, 1 ) . ' mi' : 'N/A';
        $out .= '<div class="lsb-ci-weather-detail"><span class="lsb-ci-weather-detail-lbl">Visibility</span><span class="lsb-ci-weather-detail-val">' . esc_html( $vis ) . '</span></div>';
        $out .= '</div>'; // end weather-details
        $out .= '<div class="lsb-ci-weather-updated">Updated at ' . esc_html( $updated ) . '</div>';

        // Industry-weather context note
        $out .= '<div class="lsb-ci-weather-context">';
        $out .= '<strong>Why this matters:</strong> Current weather conditions in ' . esc_html( $city_name ) . ' directly affect your ' . esc_html( $industry_name ) . ' needs. ';
        $out .= 'Extreme temperatures, humidity, and seasonal changes are key factors when scheduling service.';
        $out .= '</div>';

        $out .= '</div>'; // end weather-current

        // Forecast
        $out .= '<div class="lsb-ci-forecast-wrap">';
        $out .= '<div class="lsb-ci-forecast-label">4-Day Forecast</div>';
        if ( ! empty( $forecast_days ) ) {
            foreach ( $forecast_days as $fc ) {
                $fc_day   = date( 'l', $fc['dt'] );
                $fc_hi    = round( $fc['main']['temp_max'] );
                $fc_lo    = round( $fc['main']['temp_min'] );
                $fc_desc  = $fc['weather'][0]['description'] ?? '';
                $fc_icon  = $fc['weather'][0]['icon'] ?? '01d';
                $fc_emoji = $weather_emoji( $fc_icon );
                $out .= '<div class="lsb-ci-forecast-row">';
                $out .= '<span class="lsb-ci-forecast-day">' . esc_html( $fc_day ) . '</span>';
                $out .= '<span class="lsb-ci-forecast-desc">' . esc_html( $fc_desc ) . '</span>';
                $out .= '<span class="lsb-ci-forecast-icon">' . $fc_emoji . '</span>';
                $out .= '<span class="lsb-ci-forecast-temps">' . esc_html( $fc_hi ) . '°<span>' . esc_html( $fc_lo ) . '°</span></span>';
                $out .= '</div>';
            }
        }
        $out .= '</div>'; // end forecast-wrap

        $out .= '</div>'; // end weather-grid
    } else {
        $out .= '<p style="color:rgba(255,255,255,0.4);font-family:\'DM Sans\',sans-serif;font-size:0.9rem;">Weather data is currently unavailable for ' . esc_html( $city_name ) . '.</p>';
    }

    $out .= '</div>'; // end weather-inner
    $out .= '</section>';

    // ============================================================
    // SECTION 8 — WHY HIRE A PRO
    // ============================================================
    $default_why_points = [
        'Licensed and trained ' . $industry_name . ' technicians',
        'Accurate system sizing and load calculations',
        'Proper installation that meets ' . $city_name . ' building codes',
        'Safer handling of specialized equipment and materials',
        'Energy-efficient setup for lower utility costs',
        'Warranty-protected equipment and labor',
        'Faster diagnosis and reliable repairs',
        'Long-term system reliability and performance',
    ];

    $why_points_display = ! empty( $why_hire_points )
        ? array_map( fn( $p ) => $p['point_text'] ?? '', $why_hire_points )
        : $default_why_points;
    $why_points_display = array_filter( $why_points_display );

    $why_intro_display = $why_hire_intro
        ?: 'Hiring a qualified ' . $industry_name . ' professional ensures safety, compliance, and long-term performance. Here\'s why it matters in ' . $city_name . '.';

    $out .= '<section class="lsb-ci-why-section lsb-ci-light" id="lsb-ci-why-hire">';
    $out .= '<div class="lsb-ci-inner">';

    $out .= '<div class="lsb-ci-why-header">';
    $out .= '<span class="lsb-ci-section-label">Professional Advantage</span>';
    $out .= '<h2 class="lsb-ci-h2">Why Hire a Licensed <em>' . esc_html( $industry_name ) . '</em> Professional in ' . esc_html( $city_name ) . '</h2>';
    $out .= '<p class="lsb-ci-why-intro">' . esc_html( $why_intro_display ) . '</p>';
    $out .= '</div>';

    $out .= '<div class="lsb-ci-why-grid">';
    foreach ( $why_points_display as $point ) {
        $out .= '<div class="lsb-ci-why-card">';
        $out .= '<div class="lsb-ci-why-check" aria-hidden="true">✓</div>';
        $out .= '<p class="lsb-ci-why-point">' . esc_html( $point ) . '</p>';
        $out .= '</div>';
    }
    $out .= '</div>'; // end why-grid

    if ( $industry_disclaimer ) {
        $out .= '<p class="lsb-ci-why-disclaimer">' . esc_html( $industry_disclaimer ) . '</p>';
    }

    $out .= '</div>'; // end inner
    $out .= '</section>';

    // ============================================================
    // SECTION 9 — FAQ
    // ============================================================
    if ( ! empty( $faq_posts ) ) {
        $out .= '<section class="lsb-ci-faq-section lsb-ci-light" id="lsb-ci-faq">';
        $out .= '<div class="lsb-ci-faq-inner">';

        $out .= '<div class="lsb-ci-faq-header">';
        $out .= '<span class="lsb-ci-section-label">Got Questions?</span>';
        $out .= '<h2 class="lsb-ci-h2">FAQs — <em>' . esc_html( $industry_name ) . '</em> Services in ' . esc_html( $city_name ) . '</h2>';
        $out .= '</div>';

        $out .= '<div class="lsb-ci-faq-list">';
        foreach ( $faq_posts as $i => $faq ) {
            $short_answer = function_exists( 'get_field' ) ? get_field( 'faq_short_answer', $faq->ID ) : '';
            if ( ! $short_answer ) continue;
            $faq_url     = get_permalink( $faq->ID );
            $item_id     = 'lsb-ci-faq-item-' . $uid . '-' . $i;
            $body_id     = 'lsb-ci-faq-body-' . $uid . '-' . $i;

            $out .= '<div class="lsb-ci-faq-item" id="' . esc_attr( $item_id ) . '">';
            $out .= '<button class="lsb-ci-faq-trigger" type="button" aria-expanded="false" aria-controls="' . esc_attr( $body_id ) . '">';
            $out .= '<span class="lsb-ci-faq-question">' . esc_html( $faq->post_title ) . '</span>';
            $out .= '<span class="lsb-ci-faq-icon" aria-hidden="true">+</span>';
            $out .= '</button>';
            $out .= '<div class="lsb-ci-faq-body" id="' . esc_attr( $body_id ) . '" role="region">';
            $out .= '<div class="lsb-ci-faq-answer">' . wp_kses_post( $short_answer ) . '</div>';
            $out .= '<div class="lsb-ci-faq-cta-row">';
            $out .= '<a href="' . esc_url( $faq_url ) . '" class="lsb-ci-faq-learn-more">Learn More</a>';
            if ( $disclaimer ) {
                $out .= '<p class="lsb-ci-faq-disclaimer">' . esc_html( $disclaimer ) . '</p>';
            }
            $out .= '</div>'; // end cta-row
            $out .= '</div>'; // end faq-body
            $out .= '</div>'; // end faq-item
        }
        $out .= '</div>'; // end faq-list
        $out .= '</div>'; // end faq-inner
        $out .= '</section>';

        // FAQ accordion JS
        $out .= '<script>';
        $out .= '(function(){';
        $out .= 'var items=document.querySelectorAll("#lsb-ci-faq .lsb-ci-faq-item");';
        $out .= 'items.forEach(function(item){';
        $out .= 'var trigger=item.querySelector(".lsb-ci-faq-trigger");';
        $out .= 'if(!trigger)return;';
        $out .= 'trigger.addEventListener("click",function(){';
        $out .= 'var isOpen=item.classList.contains("is-open");';
        $out .= 'items.forEach(function(el){el.classList.remove("is-open");el.querySelector(".lsb-ci-faq-trigger").setAttribute("aria-expanded","false");});';
        $out .= 'if(!isOpen){item.classList.add("is-open");trigger.setAttribute("aria-expanded","true");}';
        $out .= '});';
        $out .= '});';
        $out .= '}());';
        $out .= '</script>';
    }

    // ============================================================
    // SECTION 10 — NEARBY CITIES
    // ============================================================
    if ( ! empty( $nearby_posts ) ) {
        $out .= '<section class="lsb-ci-nearby-section lsb-ci-light" id="lsb-ci-nearby">';
        $out .= '<div class="lsb-ci-inner">';

        $out .= '<div class="lsb-ci-nearby-header">';
        $out .= '<span class="lsb-ci-section-label">Explore More</span>';
        $out .= '<h2 class="lsb-ci-h2">' . esc_html( $industry_name ) . ' Services in <em>Nearby Cities</em></h2>';
        $out .= '</div>';

        $out .= '<div class="lsb-ci-nearby-grid">';
        foreach ( $nearby_posts as $nearby ) {
            $nearby_slug = $nearby->post_name;
            $nearby_name = $nearby->post_title;
            $nearby_url  = home_url( '/cities/' . $nearby_slug . '/' . $industry_slug . '/' );
            $out .= '<a href="' . esc_url( $nearby_url ) . '" class="lsb-ci-nearby-card">';
            $out .= '<span class="lsb-ci-nearby-icon">📍</span>';
            $out .= '<span class="lsb-ci-nearby-name">' . esc_html( $nearby_name ) . '</span>';
            $out .= '<span class="lsb-ci-nearby-label">' . esc_html( $industry_name ) . ' Services</span>';
            $out .= '<span class="lsb-ci-nearby-arrow">View ' . esc_html( $industry_name ) . ' in ' . esc_html( $nearby_name ) . ' →</span>';
            $out .= '</a>';
        }
        $out .= '</div>'; // end nearby-grid
        $out .= '</div>'; // end inner
        $out .= '</section>';
    }

    // ============================================================
    // SECTION 11 — FINAL CTA
    // ============================================================
    $out .= '<section class="lsb-ci-cta-section lsb-ci-dark" id="lsb-ci-cta">';
    $out .= '<div class="lsb-ci-cta-grid-bg" aria-hidden="true"></div>';
    $out .= '<div class="lsb-ci-cta-glow-1" aria-hidden="true"></div>';
    $out .= '<div class="lsb-ci-cta-glow-2" aria-hidden="true"></div>';

    $out .= '<div class="lsb-ci-cta-inner">';
    $out .= '<span class="lsb-ci-section-label">Get Started Today</span>';
    $out .= '<h2 class="lsb-ci-cta-heading">Find a Trusted <em>' . esc_html( $industry_name ) . '</em> Pro in ' . esc_html( $city_name ) . ' Today</h2>';
    $out .= '<p class="lsb-ci-cta-sub">Browse verified ' . esc_html( $industry_name ) . ' businesses serving ' . esc_html( $city_name ) . '. Compare services, check ratings, and connect directly — no middleman, no fees.</p>';
    $out .= '<a href="' . esc_url( $biz_url ) . '" class="lsb-ci-cta-btn">Browse ' . esc_html( $industry_name ) . ' Businesses in ' . esc_html( $city_name ) . '</a>';
    $out .= '</div>'; // end cta-inner

    $out .= '</section>';

    return $out;
}

add_shortcode( 'city_industry_page', 'lsb_city_industry_page_shortcode' );

endif;