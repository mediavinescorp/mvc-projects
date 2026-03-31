<?php
/**
 * Shortcode: [local_businesses_page]
 * Page: /local-businesses/
 *
 * Displays all businesses with featured_business ACF field = 1,
 * organized by industry_cat taxonomy.
 *
 * Attributes:
 *   industry  - filter by industry_cat slug (optional)
 *   city      - filter by city_cat slug (optional)
 *   columns   - 2 or 3 (default: 3)
 *
 * SEO:
 *   - H1 page intro block with keyword-rich copy + live stats
 *   - Industry anchor jump nav
 *   - H2 per industry group with plain-language intro sentence
 *   - ItemList + LocalBusiness JSON-LD with address + areaServed
 *   - BreadcrumbList JSON-LD
 *   - Inline microdata (itemscope / itemprop)
 *
 * AEO:
 *   - FAQPage JSON-LD (per-industry smart defaults)
 *   - Speakable schema targeting intro + FAQ + group intro CSS classes
 *   - FAQ accordion block at page bottom using <details>/<summary>
 *   - Per-industry plain-language intro sentence for AI extraction
 */

/* ------------------------------------------------------------------
 * INDUSTRY META — AEO intro sentences + FAQ pairs per industry
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'lsb_lbp_industry_meta' ) ) :
function lsb_lbp_industry_meta() {
    return [
        'hvac' => [
            'intro' => 'These are our featured HVAC professionals serving Greater Los Angeles — covering air conditioning installation, heating repair, heat pump systems, and indoor air quality.',
            'faq'   => [
                [ 'q' => 'What HVAC companies serve the Los Angeles area?',        'a' => 'Our directory features verified HVAC professionals serving all 71 cities across Greater Los Angeles, including residential and commercial specialists.' ],
                [ 'q' => 'How much does HVAC installation cost in Los Angeles?',   'a' => 'HVAC installation in Los Angeles typically ranges from $3,000 to $12,000 depending on system type, home size, and brand. Contact a featured professional for a free quote.' ],
            ],
        ],
        'roofing' => [
            'intro' => 'These are our featured roofing contractors serving Greater Los Angeles — specializing in tile roofs, shingle replacement, flat roofs, and emergency leak repair.',
            'faq'   => [
                [ 'q' => 'Which roofing contractors are top-rated in Los Angeles?', 'a' => 'Our featured roofing professionals are among the highest-rated contractors in Greater Los Angeles, verified across tile, shingle, and flat roof work.' ],
                [ 'q' => 'Does homeowners insurance cover roof replacement in CA?', 'a' => 'California homeowners insurance typically covers roof damage from sudden events like storms or fire, but not normal wear. A public adjuster can help evaluate your claim.' ],
            ],
        ],
        'plumbing' => [
            'intro' => 'These are our featured plumbing professionals serving Greater Los Angeles — available for emergency repairs, water heater installation, drain cleaning, and repiping.',
            'faq'   => [
                [ 'q' => 'Are there 24/7 plumbers available in Los Angeles?',      'a' => 'Yes — several of our featured plumbing professionals offer 24/7 emergency services across Greater Los Angeles. Click "Visit Business" on any listing for contact details.' ],
                [ 'q' => 'What plumbing services are most common in LA homes?',    'a' => 'The most common plumbing services in Los Angeles include water heater replacement, sewer line inspection, drain cleaning, and leak detection — especially in older homes.' ],
            ],
        ],
        'auto-body' => [
            'intro' => 'These are our featured auto body shops serving Greater Los Angeles — offering collision repair, paint matching, dent removal, and insurance claim assistance.',
            'faq'   => [
                [ 'q' => 'How do I find a reputable auto body shop in Los Angeles?', 'a' => 'Look for licensed shops with verified reviews, OEM parts availability, and insurance claim experience. All featured auto body shops in our directory are vetted professionals.' ],
            ],
        ],
        'locksmith' => [
            'intro' => 'These are our featured locksmith professionals serving Greater Los Angeles — for residential lockouts, commercial rekeying, car key replacement, and smart lock installation.',
            'faq'   => [
                [ 'q' => 'Can I find an emergency locksmith in Los Angeles?',      'a' => 'Yes — our featured locksmiths offer rapid-response emergency services across Greater Los Angeles for residential, commercial, and automotive lockouts.' ],
            ],
        ],
        'restoration' => [
            'intro' => 'These are our featured restoration contractors serving Greater Los Angeles — specializing in water damage, fire damage, mold remediation, and disaster recovery.',
            'faq'   => [
                [ 'q' => 'What should I do after water damage in my LA home?',     'a' => 'Call a licensed restoration contractor immediately to begin water extraction and drying. Document all damage with photos before cleanup for insurance purposes.' ],
                [ 'q' => 'Does insurance cover mold remediation in California?',   'a' => 'Coverage depends on the cause. Mold from a sudden water event is often covered; mold from long-term neglect typically is not. Consult a public adjuster.' ],
            ],
        ],
        'catering' => [
            'intro' => 'These are our featured catering professionals serving Greater Los Angeles — for corporate events, weddings, private parties, and large-scale productions.',
            'faq'   => [
                [ 'q' => 'How far in advance should I book a caterer in Los Angeles?', 'a' => 'For large events in Los Angeles, book a caterer at least 3–6 months in advance. Peak seasons (spring and fall) book out quickly among top-rated catering professionals.' ],
            ],
        ],
        'realtors' => [
            'intro' => 'These are our featured real estate professionals serving Greater Los Angeles — helping buyers, sellers, and investors navigate the Southern California property market.',
            'faq'   => [
                [ 'q' => 'How do I find a top realtor in Los Angeles?',            'a' => 'Look for licensed agents with strong local market knowledge, recent transaction history, and verified client reviews. Our featured realtors serve all major LA-area neighborhoods.' ],
                [ 'q' => 'What is the average home price in Greater Los Angeles?', 'a' => 'The median home price in Greater Los Angeles exceeds $800,000, with significant variation by city and neighborhood. A local realtor can provide current comparables.' ],
            ],
        ],
        'public-adjusting' => [
            'intro' => 'These are our featured public adjusters serving Greater Los Angeles — advocating for policyholders on property insurance claims for fire, water, wind, and storm damage.',
            'faq'   => [
                [ 'q' => 'What does a public adjuster do in California?',          'a' => 'A public adjuster represents the policyholder — not the insurance company — to document damage, interpret policy language, and negotiate a fair settlement on your claim.' ],
                [ 'q' => 'When should I hire a public adjuster in Los Angeles?',   'a' => 'Hire a public adjuster when your insurance claim is denied, underpaid, or too complex to handle alone — especially after major fire, water, or storm damage in LA.' ],
            ],
        ],
        'scrap-metals' => [
            'intro' => 'These are our featured scrap metal buyers and recyclers serving Greater Los Angeles — purchasing copper, aluminum, steel, and electronic scrap from residential and commercial sources.',
            'faq'   => [
                [ 'q' => 'Where can I sell scrap metal in Los Angeles?',           'a' => 'Our featured scrap metal professionals across Greater Los Angeles buy copper, aluminum, steel, and e-waste. Prices vary by material and current market rates.' ],
            ],
        ],
        'dental-broker' => [
            'intro' => 'These are our featured dental practice brokers serving Greater Los Angeles — facilitating the buying, selling, and valuation of dental practices across Southern California.',
            'faq'   => [
                [ 'q' => 'How do I sell my dental practice in Los Angeles?',       'a' => 'Work with a licensed dental broker who understands California regulations, practice valuation, and buyer financing. Our featured dental brokers serve the entire Greater LA market.' ],
            ],
        ],
    ];
}
endif; // lsb_lbp_industry_meta


/* ------------------------------------------------------------------
 * 1. JSON-LD — BreadcrumbList + ItemList + FAQPage + WebPage speakable
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'lsb_local_businesses_page_schema' ) ) :
function lsb_local_businesses_page_schema() {
    if ( ! is_page( 'local-businesses' ) ) return;

    $is_preview    = ( isset( $_GET['preview'] ) || isset( $_GET['preview_id'] ) );
    $transient_key = 'lsb_lbp_schema';
    $schema_block  = $is_preview ? false : get_transient( $transient_key );

    if ( false === $schema_block ) {

        $query = new WP_Query( [
            'post_type'      => 'businesses',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [[
                'key'     => 'featured_business',
                'value'   => '1',
                'compare' => '=',
            ]],
        ] );

        $list_items = [];
        $position   = 1;
        $ind_meta   = lsb_lbp_industry_meta();
        $all_faqs   = [];

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $pid     = get_the_ID();
                $phone   = get_field( 'business_phone', $pid );
                $address = get_field( 'business_address', $pid );

                $city_terms = wp_get_post_terms( $pid, 'primary_city', [ 'fields' => 'names' ] );
                $city_name  = ( ! is_wp_error( $city_terms ) && ! empty( $city_terms ) ) ? $city_terms[0] : 'Los Angeles';

                $ind_terms = wp_get_post_terms( $pid, 'industry_cat', [ 'fields' => 'names' ] );
                $industry  = ( ! is_wp_error( $ind_terms ) && ! empty( $ind_terms ) ) ? $ind_terms[0] : '';

                $biz = [
                    '@type' => 'LocalBusiness',
                    'name'  => get_the_title(),
                    'url'   => get_permalink(),
                ];

                if ( $phone )   $biz['telephone']  = esc_html( $phone );
                if ( $industry) $biz['knowsAbout'] = esc_html( $industry );

                if ( $city_name ) {
                    $biz['areaServed'] = [ '@type' => 'City', 'name' => esc_html( $city_name ) . ', CA' ];
                }

                if ( $address ) {
                    $biz['address'] = [
                        '@type'           => 'PostalAddress',
                        'streetAddress'   => esc_html( $address ),
                        'addressLocality' => esc_html( $city_name ),
                        'addressRegion'   => 'CA',
                        'addressCountry'  => 'US',
                    ];
                }

                $list_items[] = [
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'item'     => $biz,
                ];
            }
            wp_reset_postdata();
        }

        /* Flatten all industry FAQs */
        foreach ( $ind_meta as $slug => $meta ) {
            if ( empty( $meta['faq'] ) ) continue;
            foreach ( $meta['faq'] as $pair ) {
                $all_faqs[] = [
                    '@type'          => 'Question',
                    'name'           => esc_html( $pair['q'] ),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => esc_html( $pair['a'] ),
                    ],
                ];
            }
        }

        $page_url = get_permalink( get_page_by_path( 'local-businesses' ) );

        /* BreadcrumbList */
        $breadcrumb = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                [ '@type' => 'ListItem', 'position' => 1, 'name' => 'Home',                       'item' => home_url( '/' ) ],
                [ '@type' => 'ListItem', 'position' => 2, 'name' => 'Featured Local Businesses',  'item' => esc_url( $page_url ) ],
            ],
        ];

        /* ItemList */
        $item_list = [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => 'Featured Local Service Businesses — Greater Los Angeles',
            'description'     => 'A curated directory of top-rated featured local service businesses serving 71 cities across Greater Los Angeles, organized by industry.',
            'url'             => esc_url( $page_url ),
            'numberOfItems'   => count( $list_items ),
            'itemListElement' => $list_items,
        ];

        /* FAQPage */
        $faq_schema = [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $all_faqs,
        ];

        /* WebPage + Speakable */
        $webpage = [
            '@context'  => 'https://schema.org',
            '@type'     => 'WebPage',
            'name'      => 'Featured Local Businesses — Greater Los Angeles',
            'url'       => esc_url( $page_url ),
            'speakable' => [
                '@type'       => 'SpeakableSpecification',
                'cssSelector' => [
                    '.lsb-lbp-page-intro',
                    '.lsb-lbp-group-intro',
                    '.lsb-lbp-faq-answer',
                ],
            ],
        ];

        $schema_block  = '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
        $schema_block .= '<script type="application/ld+json">' . wp_json_encode( $item_list,  JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
        $schema_block .= '<script type="application/ld+json">' . wp_json_encode( $faq_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
        $schema_block .= '<script type="application/ld+json">' . wp_json_encode( $webpage,    JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";

        if ( ! $is_preview ) {
            set_transient( $transient_key, $schema_block, 12 * HOUR_IN_SECONDS );
        }
    }

    echo $schema_block;
}
endif; // lsb_local_businesses_page_schema

// template_redirect fires before Elementor renders — ensures wp_head hook
// is registered early enough that schema prints inside <head>, not <body>
if ( ! function_exists( 'lsb_lbp_register_schema_hook' ) ) {
    function lsb_lbp_register_schema_hook() {
        if ( is_page( 'local-businesses' ) ) {
            add_action( 'wp_head', 'lsb_local_businesses_page_schema', 5 );
        }
    }
    add_action( 'template_redirect', 'lsb_lbp_register_schema_hook' );
}


/* ------------------------------------------------------------------
 * 2. CLEAR TRANSIENTS on business save
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'lsb_lbp_clear_transients' ) ) {
    function lsb_lbp_clear_transients() {
        delete_transient( 'lsb_lbp_schema' );
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lsb_lbp_output_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lsb_lbp_output_%'" );
    }
    add_action( 'save_post_businesses', 'lsb_lbp_clear_transients' );
}


/* ------------------------------------------------------------------
 * 3. SHORTCODE
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'lsb_local_businesses_page_shortcode' ) ) :
function lsb_local_businesses_page_shortcode( $atts ) {

    $atts = shortcode_atts( [
        'industry' => '',
        'city'     => '',
        'columns'  => '3',
    ], $atts, 'local_businesses_page' );

    $columns = in_array( (int) $atts['columns'], [ 2, 3 ] ) ? (int) $atts['columns'] : 3;

    /* Skip transient cache on preview / non-published context */
    $is_preview = ( isset( $_GET['preview'] ) || isset( $_GET['preview_id'] ) );

    $transient_key = 'lsb_lbp_output_' . md5( $atts['industry'] . $atts['city'] . $columns );
    if ( ! $is_preview ) {
        $cached = get_transient( $transient_key );
        if ( false !== $cached ) return $cached;
    }

    /* ---------- queries ---------- */
    $meta_query = [[
        'key'     => 'featured_business',
        'value'   => '1',
        'compare' => '=',
    ]];

    $tax_query = [];

    if ( ! empty( $atts['industry'] ) ) {
        $tax_query[] = [ 'taxonomy' => 'industry_cat', 'field' => 'slug', 'terms' => sanitize_text_field( $atts['industry'] ) ];
    }
    if ( ! empty( $atts['city'] ) ) {
        $tax_query[] = [ 'taxonomy' => 'city_cat', 'field' => 'slug', 'terms' => sanitize_text_field( $atts['city'] ) ];
    }
    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }

    $query_args = [
        'post_type'      => 'businesses',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];
    if ( ! empty( $tax_query ) ) $query_args['tax_query'] = $tax_query;

    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        $out = '<p class="lsb-lbp-empty">No featured businesses found.</p>';
        set_transient( $transient_key, $out, 12 * HOUR_IN_SECONDS );
        return $out;
    }

    /* ---------- group by industry_cat ---------- */
    $grouped  = [];
    $ind_meta = lsb_lbp_industry_meta();

    while ( $query->have_posts() ) {
        $query->the_post();
        $pid   = get_the_ID();
        $terms = wp_get_post_terms( $pid, 'industry_cat', [ 'fields' => 'all', 'orderby' => 'parent' ] );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            $group_key  = 'uncategorized';
            $group_name = 'Other Services';
        } else {
            $term       = $terms[0];
            $group_key  = $term->slug;
            $group_name = $term->name;
        }

        $grouped[ $group_key ]['label']   = $group_name;
        $grouped[ $group_key ]['posts'][] = $pid;
    }
    wp_reset_postdata();

    uasort( $grouped, fn( $a, $b ) => strcmp( $a['label'], $b['label'] ) );

    $total_businesses = array_sum( array_map( fn( $g ) => count( $g['posts'] ), $grouped ) );
    $total_industries = count( $grouped );

    $uid = 'lsb-lbp-' . get_the_ID();

    /* ================================================================
     * CSS
     * ============================================================== */
    $out = '
<style>
#' . $uid . ' {
    --lbp-navy:      #0D1B2A;
    --lbp-navy-mid:  #1B2F45;
    --lbp-teal:      #00C9A7;
    --lbp-teal-dark: #00A88C;
    --lbp-gold:      #F4C542;
    --lbp-white:     #FFFFFF;
    --lbp-off-white: #F5F7FA;
    --lbp-muted:     #8A9BB0;
    --lbp-border:    #E4EAF2;
    --lbp-text:      #1A2535;
    font-family: "DM Sans", sans-serif;
    color: var(--lbp-text);
    padding: 0 40px;
}

@media (max-width: 960px) {
    #' . $uid . ' { padding: 0 24px; }
}

@media (max-width: 600px) {
    #' . $uid . ' { padding: 0 16px; }
}

/* ================================================================
   PAGE INTRO BLOCK
   ============================================================== */
#' . $uid . ' .lsb-lbp-page-intro {
    background: var(--lbp-navy);
    border-radius: 0;
    padding: 52px 56px;
    margin-bottom: 40px;
    margin-left: -40px;
    margin-right: -40px;
    position: relative;
    overflow: hidden;
}

#' . $uid . ' .lsb-lbp-page-intro::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(0,201,167,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,201,167,0.04) 1px, transparent 1px);
    background-size: 44px 44px;
    pointer-events: none;
}

#' . $uid . ' .lsb-lbp-page-intro::after {
    content: "";
    position: absolute;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(0,201,167,0.1) 0%, transparent 65%);
    top: -120px;
    right: -100px;
    pointer-events: none;
}

#' . $uid . ' .lsb-lbp-intro-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(0,201,167,0.12);
    border: 1px solid rgba(0,201,167,0.25);
    border-radius: 100px;
    padding: 5px 14px;
    margin-bottom: 22px;
    position: relative;
    z-index: 1;
}

#' . $uid . ' .lsb-lbp-intro-badge-dot {
    width: 6px; height: 6px;
    background: var(--lbp-teal);
    border-radius: 50%;
    animation: lbp-pulse 2s infinite;
}

@keyframes lbp-pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.35; }
}

#' . $uid . ' .lsb-lbp-intro-badge-text {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--lbp-teal);
}

#' . $uid . ' .lsb-lbp-page-h1 {
    font-family: "Syne", sans-serif;
    font-size: clamp(1.7rem, 3.2vw, 2.6rem);
    font-weight: 800;
    color: var(--lbp-white);
    letter-spacing: -0.025em;
    line-height: 1.1;
    margin: 0 0 18px;
    position: relative;
    z-index: 1;
}

#' . $uid . ' .lsb-lbp-page-h1 em {
    font-style: normal;
    color: var(--lbp-teal);
}

#' . $uid . ' .lsb-lbp-intro-text {
    font-size: 1rem;
    color: rgba(255,255,255,0.52);
    line-height: 1.8;
    max-width: 620px;
    font-weight: 300;
    margin: 0 0 32px;
    position: relative;
    z-index: 1;
}

#' . $uid . ' .lsb-lbp-intro-stats {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0;
    position: relative;
    z-index: 1;
}

#' . $uid . ' .lsb-lbp-intro-stat {
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding: 0 28px 0 0;
}

#' . $uid . ' .lsb-lbp-intro-stat-num {
    font-family: "Syne", sans-serif;
    font-size: 1.7rem;
    font-weight: 800;
    color: var(--lbp-white);
    letter-spacing: -0.025em;
    line-height: 1;
}

#' . $uid . ' .lsb-lbp-intro-stat-label {
    font-size: 0.72rem;
    color: var(--lbp-muted);
    text-transform: uppercase;
    letter-spacing: 0.07em;
    font-weight: 400;
}

#' . $uid . ' .lsb-lbp-stat-divider {
    width: 1px;
    height: 36px;
    background: rgba(255,255,255,0.1);
    margin: 0 28px 0 0;
    align-self: center;
}

/* ================================================================
   JUMP NAV
   ============================================================== */
#' . $uid . ' .lsb-lbp-jump-nav {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 52px;
    padding: 18px 22px;
    background: var(--lbp-white);
    border: 1px solid var(--lbp-border);
    border-radius: 12px;
}

#' . $uid . ' .lsb-lbp-jump-label {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--lbp-muted);
    margin-right: 4px;
    white-space: nowrap;
}

#' . $uid . ' .lsb-lbp-jump-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--lbp-off-white);
    border: 1px solid var(--lbp-border);
    border-radius: 100px;
    padding: 6px 13px;
    font-size: 0.81rem;
    font-weight: 500;
    color: var(--lbp-navy);
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}

#' . $uid . ' .lsb-lbp-jump-link:hover {
    background: rgba(0,201,167,0.08);
    border-color: rgba(0,201,167,0.3);
    color: var(--lbp-teal-dark);
}

#' . $uid . ' .lsb-lbp-jump-count {
    background: rgba(0,201,167,0.15);
    color: var(--lbp-teal-dark);
    border-radius: 100px;
    padding: 1px 7px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* ================================================================
   INDUSTRY GROUPS
   ============================================================== */
#' . $uid . ' .lsb-lbp-group {
    margin-bottom: 72px;
    scroll-margin-top: 100px;
}

#' . $uid . ' .lsb-lbp-group:last-of-type { margin-bottom: 0; }

#' . $uid . ' .lsb-lbp-group-title-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--lbp-border);
    margin-bottom: 14px;
    flex-wrap: wrap;
}

#' . $uid . ' .lsb-lbp-industry-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(0,201,167,0.08);
    border: 1px solid rgba(0,201,167,0.2);
    border-radius: 100px;
    padding: 5px 13px;
    flex-shrink: 0;
}

#' . $uid . ' .lsb-lbp-industry-dot {
    width: 7px; height: 7px;
    background: var(--lbp-teal);
    border-radius: 50%;
}

#' . $uid . ' .lsb-lbp-industry-label {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--lbp-teal);
}

#' . $uid . ' .lsb-lbp-group-h2 {
    font-family: "Syne", sans-serif;
    font-size: clamp(1.15rem, 2vw, 1.5rem);
    font-weight: 800;
    color: var(--lbp-navy);
    letter-spacing: -0.02em;
    line-height: 1.15;
    margin: 0;
    flex: 1;
}

#' . $uid . ' .lsb-lbp-group-count {
    margin-left: auto;
    background: var(--lbp-off-white);
    border: 1px solid var(--lbp-border);
    border-radius: 100px;
    padding: 4px 12px;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--lbp-muted);
    white-space: nowrap;
    flex-shrink: 0;
}

/* AEO intro sentence */
#' . $uid . ' .lsb-lbp-group-intro {
    font-size: 0.9rem;
    color: var(--lbp-muted);
    line-height: 1.75;
    font-weight: 300;
    margin: 0 0 24px;
    max-width: 780px;
}

/* ================================================================
   GRID
   ============================================================== */
#' . $uid . ' .lsb-lbp-grid {
    display: grid;
    gap: 18px;
}

#' . $uid . ' .lsb-lbp-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
#' . $uid . ' .lsb-lbp-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }

@media (max-width: 960px) {
    #' . $uid . ' .lsb-lbp-grid.cols-3 { grid-template-columns: repeat(2, 1fr); }
    #' . $uid . ' .lsb-lbp-page-intro  { padding: 36px 28px; margin-left: -24px; margin-right: -24px; }
}

@media (max-width: 600px) {
    #' . $uid . ' .lsb-lbp-grid.cols-2,
    #' . $uid . ' .lsb-lbp-grid.cols-3 { grid-template-columns: 1fr; }
    #' . $uid . ' .lsb-lbp-jump-nav    { display: none; }
    #' . $uid . ' .lsb-lbp-page-intro  { padding: 28px 20px; margin-left: -16px; margin-right: -16px; }
}

/* ================================================================
   CARD
   ============================================================== */
#' . $uid . ' .lsb-lbp-card {
    background: var(--lbp-white);
    border: 1px solid var(--lbp-border);
    border-radius: 16px;
    padding: 26px;
    display: flex;
    flex-direction: column;
    transition: border-color 0.25s, box-shadow 0.25s, transform 0.25s;
    position: relative;
    overflow: hidden;
}

#' . $uid . ' .lsb-lbp-card::after {
    content: "";
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--lbp-teal), var(--lbp-teal-dark));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

#' . $uid . ' .lsb-lbp-card:hover {
    border-color: rgba(0,201,167,0.35);
    box-shadow: 0 12px 36px rgba(0,201,167,0.08), 0 4px 12px rgba(0,0,0,0.05);
    transform: translateY(-4px);
}

#' . $uid . ' .lsb-lbp-card:hover::after { transform: scaleX(1); }

#' . $uid . ' .lsb-lbp-card-top {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 14px;
}

#' . $uid . ' .lsb-lbp-logo-wrap {
    width: 58px; height: 58px;
    border-radius: 13px;
    overflow: hidden;
    flex-shrink: 0;
    background: var(--lbp-navy);
    display: flex;
    align-items: center;
    justify-content: center;
}

#' . $uid . ' .lsb-lbp-logo-wrap img {
    width: 100%; height: 100%;
    object-fit: contain;
    display: block;
}

#' . $uid . ' .lsb-lbp-logo-fallback {
    font-family: "Syne", sans-serif;
    font-weight: 800;
    font-size: 1.15rem;
    color: var(--lbp-teal);
    letter-spacing: -0.02em;
    line-height: 1;
    text-align: center;
}

#' . $uid . ' .lsb-lbp-card-info { flex: 1; min-width: 0; }

#' . $uid . ' .lsb-lbp-card-title {
    font-family: "Syne", sans-serif;
    font-weight: 700;
    font-size: 0.96rem;
    color: var(--lbp-navy);
    letter-spacing: -0.01em;
    line-height: 1.25;
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#' . $uid . ' .lsb-lbp-industry-tag {
    font-size: 0.76rem;
    font-weight: 500;
    color: var(--lbp-teal);
}

#' . $uid . ' .lsb-lbp-city-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.78rem;
    color: var(--lbp-muted);
    margin-bottom: 12px;
}

#' . $uid . ' .lsb-lbp-phone {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--lbp-navy);
    text-decoration: none;
    padding: 10px 13px;
    background: var(--lbp-off-white);
    border: 1px solid var(--lbp-border);
    border-radius: 8px;
    margin-bottom: 14px;
    transition: background 0.2s, border-color 0.2s, color 0.2s;
}

#' . $uid . ' .lsb-lbp-phone:hover {
    background: rgba(0,201,167,0.06);
    border-color: rgba(0,201,167,0.3);
    color: var(--lbp-teal-dark);
}

#' . $uid . ' .lsb-lbp-card-footer {
    margin-top: auto;
    padding-top: 14px;
    border-top: 1px solid var(--lbp-border);
}

#' . $uid . ' .lsb-lbp-visit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    background: var(--lbp-navy);
    color: var(--lbp-white);
    font-family: "Syne", sans-serif;
    font-weight: 700;
    font-size: 0.87rem;
    padding: 13px 20px;
    border-radius: 10px;
    text-decoration: none;
    letter-spacing: 0.02em;
    transition: background 0.2s, color 0.2s, transform 0.15s;
}

#' . $uid . ' .lsb-lbp-visit-btn:hover {
    background: var(--lbp-teal);
    color: var(--lbp-navy);
    transform: translateY(-1px);
}

#' . $uid . ' .lsb-lbp-visit-btn svg       { transition: transform 0.2s; }
#' . $uid . ' .lsb-lbp-visit-btn:hover svg  { transform: translateX(3px); }

/* ================================================================
   FAQ SECTION
   ============================================================== */
#' . $uid . ' .lsb-lbp-faq-section {
    margin-top: 80px;
    padding-top: 60px;
    border-top: 2px solid var(--lbp-border);
}

#' . $uid . ' .lsb-lbp-faq-eyebrow {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--lbp-teal);
    margin-bottom: 10px;
}

#' . $uid . ' .lsb-lbp-faq-h2 {
    font-family: "Syne", sans-serif;
    font-size: clamp(1.3rem, 2.5vw, 2rem);
    font-weight: 800;
    color: var(--lbp-navy);
    letter-spacing: -0.02em;
    line-height: 1.1;
    margin: 0 0 10px;
}

#' . $uid . ' .lsb-lbp-faq-sub {
    font-size: 0.92rem;
    color: var(--lbp-muted);
    font-weight: 300;
    line-height: 1.7;
    max-width: 540px;
    margin-bottom: 36px;
}

#' . $uid . ' .lsb-lbp-faq-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

@media (max-width: 700px) {
    #' . $uid . ' .lsb-lbp-faq-grid { grid-template-columns: 1fr; }
}

#' . $uid . ' .lsb-lbp-faq-item {
    background: var(--lbp-white);
    border: 1px solid var(--lbp-border);
    border-radius: 12px;
    overflow: hidden;
    transition: border-color 0.2s;
}

#' . $uid . ' .lsb-lbp-faq-item:hover { border-color: rgba(0,201,167,0.25); }

#' . $uid . ' .lsb-lbp-faq-q {
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    cursor: pointer;
    font-family: "Syne", sans-serif;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--lbp-navy);
    gap: 14px;
    line-height: 1.45;
    user-select: none;
    -webkit-user-select: none;
    list-style: none;
}

#' . $uid . ' .lsb-lbp-faq-q::-webkit-details-marker { display: none; }

#' . $uid . ' .lsb-lbp-faq-toggle {
    width: 26px; height: 26px;
    min-width: 26px;
    background: var(--lbp-off-white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: var(--lbp-teal);
    transition: transform 0.25s, background 0.2s;
    line-height: 1;
    margin-top: 1px;
}

#' . $uid . ' details[open] > .lsb-lbp-faq-q .lsb-lbp-faq-toggle {
    transform: rotate(45deg);
    background: rgba(0,201,167,0.1);
}

#' . $uid . ' .lsb-lbp-faq-answer {
    padding: 0 20px 18px;
    font-size: 0.875rem;
    color: var(--lbp-muted);
    line-height: 1.8;
    font-weight: 300;
    border-top: 1px solid var(--lbp-border);
    padding-top: 14px;
}

/* ---- Empty ---- */
#' . $uid . ' .lsb-lbp-empty {
    text-align: center;
    color: var(--lbp-muted);
    padding: 48px 24px;
    font-size: 0.95rem;
}

/* ================================================================
   SALES BLOCK — benefit-led, between listings and FAQ
   ============================================================== */
#' . $uid . ' .lsb-lbp-sales-mid {
    background: var(--lbp-navy);
    border-radius: 20px;
    padding: 52px 56px;
    margin: 72px 0;
    position: relative;
    overflow: hidden;
}

#' . $uid . ' .lsb-lbp-sales-mid::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(0,201,167,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,201,167,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
}

#' . $uid . ' .lsb-lbp-sales-mid::after {
    content: "";
    position: absolute;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(0,201,167,0.08) 0%, transparent 65%);
    top: -150px; right: -120px;
    pointer-events: none;
}

#' . $uid . ' .lsb-lbp-sales-mid-inner {
    position: relative;
    z-index: 1;
}

/* eyebrow */
#' . $uid . ' .lsb-lbp-sales-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(0,201,167,0.1);
    border: 1px solid rgba(0,201,167,0.22);
    border-radius: 100px;
    padding: 5px 14px;
    margin-bottom: 20px;
}

#' . $uid . ' .lsb-lbp-sales-eyebrow-dot {
    width: 6px; height: 6px;
    background: var(--lbp-teal);
    border-radius: 50%;
}

#' . $uid . ' .lsb-lbp-sales-eyebrow span {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--lbp-teal);
}

/* heading */
#' . $uid . ' .lsb-lbp-sales-title {
    font-family: "Syne", sans-serif;
    font-size: clamp(1.4rem, 3vw, 2.1rem);
    font-weight: 800;
    color: var(--lbp-white);
    letter-spacing: -0.025em;
    line-height: 1.1;
    margin: 0 0 14px;
    max-width: 680px;
}

#' . $uid . ' .lsb-lbp-sales-title em {
    font-style: normal;
    color: var(--lbp-teal);
}

#' . $uid . ' .lsb-lbp-sales-desc {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.45);
    line-height: 1.75;
    font-weight: 300;
    max-width: 580px;
    margin: 0 0 36px;
}

/* benefit cards row */
#' . $uid . ' .lsb-lbp-sales-benefits {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 36px;
}

#' . $uid . ' .lsb-lbp-sales-benefit-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 20px 20px 18px;
    transition: border-color 0.2s, background 0.2s;
}

#' . $uid . ' .lsb-lbp-sales-benefit-card:hover {
    border-color: rgba(0,201,167,0.25);
    background: rgba(0,201,167,0.05);
}

#' . $uid . ' .lsb-lbp-sales-benefit-icon {
    font-size: 1.4rem;
    margin-bottom: 10px;
    display: block;
}

#' . $uid . ' .lsb-lbp-sales-benefit-title {
    font-family: "Syne", sans-serif;
    font-weight: 700;
    font-size: 0.88rem;
    color: var(--lbp-white);
    letter-spacing: -0.01em;
    margin: 0 0 6px;
}

#' . $uid . ' .lsb-lbp-sales-benefit-desc {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.38);
    line-height: 1.6;
    font-weight: 300;
    margin: 0;
}

/* CTA row */
#' . $uid . ' .lsb-lbp-sales-cta-row {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

#' . $uid . ' .lsb-lbp-sales-btn {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: var(--lbp-teal);
    color: var(--lbp-navy);
    font-family: "Syne", sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    padding: 15px 32px;
    border-radius: 10px;
    text-decoration: none;
    letter-spacing: 0.02em;
    transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    box-shadow: 0 4px 18px rgba(0,201,167,0.3);
    white-space: nowrap;
}

#' . $uid . ' .lsb-lbp-sales-btn:hover {
    background: var(--lbp-teal-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 26px rgba(0,201,167,0.4);
}

#' . $uid . ' .lsb-lbp-sales-btn svg { transition: transform 0.2s; }
#' . $uid . ' .lsb-lbp-sales-btn:hover svg { transform: translateX(3px); }

#' . $uid . ' .lsb-lbp-sales-cta-note {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.28);
    font-weight: 300;
    line-height: 1.5;
}

#' . $uid . ' .lsb-lbp-sales-cta-note strong {
    color: rgba(255,255,255,0.5);
    font-weight: 500;
}

@media (max-width: 860px) {
    #' . $uid . ' .lsb-lbp-sales-benefits { grid-template-columns: repeat(2, 1fr); }
    #' . $uid . ' .lsb-lbp-sales-mid      { padding: 40px 32px; }
}

@media (max-width: 560px) {
    #' . $uid . ' .lsb-lbp-sales-benefits { grid-template-columns: 1fr; }
    #' . $uid . ' .lsb-lbp-sales-mid      { padding: 32px 22px; }
}


</style>
';

    /* ================================================================
     * HTML OUTPUT
     * ============================================================== */
    $out .= '<div id="' . esc_attr( $uid ) . '" class="lsb-lbp-wrapper" itemscope itemtype="https://schema.org/ItemList">';
    $out .= '<meta itemprop="name" content="Featured Local Service Businesses — Greater Los Angeles">';
    $out .= '<meta itemprop="numberOfItems" content="' . esc_attr( $total_businesses ) . '">';

    // Print shared badge styles once
    if ( function_exists( 'lsb_print_badge_styles' ) ) {
        $out .= lsb_print_badge_styles();
    }

    /* ----------------------------------------------------------------
     * PAGE INTRO — H1 + description + live stats
     * -------------------------------------------------------------- */
    $out .= '<div class="lsb-lbp-page-intro">';
    $out .=   '<div class="lsb-lbp-intro-badge">';
    $out .=     '<span class="lsb-lbp-intro-badge-dot"></span>';
    $out .=     '<span class="lsb-lbp-intro-badge-text">Greater Los Angeles Directory</span>';
    $out .=   '</div>';
    $out .=   '<h1 class="lsb-lbp-page-h1">Featured <em>Local Service</em> Businesses<br>Across Greater Los Angeles</h1>';
    $out .=   '<p class="lsb-lbp-intro-text">Browse our hand-picked featured local service professionals serving 71 cities across Greater Los Angeles. From HVAC and roofing to plumbing, restoration, and real estate — every business listed here is verified and ready to serve your community.</p>';
    $out .=   '<div class="lsb-lbp-intro-stats">';
    $out .=     '<div class="lsb-lbp-intro-stat">';
    $out .=       '<span class="lsb-lbp-intro-stat-num">' . esc_html( $total_businesses ) . '</span>';
    $out .=       '<span class="lsb-lbp-intro-stat-label">Featured Businesses</span>';
    $out .=     '</div>';
    $out .=     '<div class="lsb-lbp-stat-divider"></div>';
    $out .=     '<div class="lsb-lbp-intro-stat">';
    $out .=       '<span class="lsb-lbp-intro-stat-num">' . esc_html( $total_industries ) . '</span>';
    $out .=       '<span class="lsb-lbp-intro-stat-label">Service Industries</span>';
    $out .=     '</div>';
    $out .=     '<div class="lsb-lbp-stat-divider"></div>';
    $out .=     '<div class="lsb-lbp-intro-stat">';
    $out .=       '<span class="lsb-lbp-intro-stat-num">71</span>';
    $out .=       '<span class="lsb-lbp-intro-stat-label">Cities Served</span>';
    $out .=     '</div>';
    $out .=   '</div>';
    $out .= '</div>';

    /* ----------------------------------------------------------------
     * JUMP NAV — anchor links per industry group
     * -------------------------------------------------------------- */
    $out .= '<nav class="lsb-lbp-jump-nav" aria-label="Jump to industry section">';
    $out .=   '<span class="lsb-lbp-jump-label">Jump to:</span>';
    foreach ( $grouped as $slug => $group ) {
        $out .= '<a href="#lsb-industry-' . esc_attr( $slug ) . '" class="lsb-lbp-jump-link">';
        $out .=   esc_html( $group['label'] );
        $out .=   '<span class="lsb-lbp-jump-count">' . count( $group['posts'] ) . '</span>';
        $out .= '</a>';
    }
    $out .= '</nav>';

    /* ----------------------------------------------------------------
     * INDUSTRY GROUPS
     * -------------------------------------------------------------- */
    $position = 1;

    foreach ( $grouped as $slug => $group ) {

        $posts      = $group['posts'];
        $post_count = count( $posts );
        $label      = esc_html( $group['label'] );
        $meta       = $ind_meta[ $slug ] ?? [];
        $intro_text = ! empty( $meta['intro'] )
            ? $meta['intro']
            : 'These are our featured ' . $group['label'] . ' professionals serving Greater Los Angeles.';

        $out .= '<div class="lsb-lbp-group" id="lsb-industry-' . esc_attr( $slug ) . '" itemscope itemtype="https://schema.org/ItemList">';
        $out .= '<meta itemprop="name" content="Featured ' . esc_attr( $group['label'] ) . ' Businesses — Greater Los Angeles">';

        $out .= '<div class="lsb-lbp-group-title-row">';
        $out .=   '<div class="lsb-lbp-industry-pill">';
        $out .=     '<span class="lsb-lbp-industry-dot"></span>';
        $out .=     '<span class="lsb-lbp-industry-label">' . $label . '</span>';
        $out .=   '</div>';
        $out .=   '<h2 class="lsb-lbp-group-h2">Featured ' . $label . ' Professionals</h2>';
        $out .=   '<span class="lsb-lbp-group-count">' . $post_count . ' ' . ( $post_count === 1 ? 'business' : 'businesses' ) . '</span>';
        $out .= '</div>';

        /* AEO extraction target */
        $out .= '<p class="lsb-lbp-group-intro">' . esc_html( $intro_text ) . '</p>';

        /* grid */
        $out .= '<div class="lsb-lbp-grid cols-' . $columns . '" role="list">';

        foreach ( $posts as $pid ) {

            $title   = get_the_title( $pid );
            $link    = get_permalink( $pid );
            $phone   = get_field( 'business_phone', $pid );
            $address = get_field( 'business_address', $pid );
            $logo    = get_field( 'business_logo', $pid );
            $badge   = function_exists( 'lsb_get_business_badge' ) ? lsb_get_business_badge( $pid, 'small' ) : '';

            $city_terms = wp_get_post_terms( $pid, 'primary_city', [ 'fields' => 'names' ] );
            $city_name  = ( ! is_wp_error( $city_terms ) && ! empty( $city_terms ) ) ? $city_terms[0] : '';

            /* initials fallback */
            $words    = preg_split( '/\s+/', trim( $title ) );
            $initials = '';
            foreach ( array_slice( $words, 0, 2 ) as $w ) {
                $initials .= strtoupper( mb_substr( $w, 0, 1 ) );
            }

            /* logo */
            if ( ! empty( $logo ) ) {
                $logo_url  = is_array( $logo ) ? ( $logo['url'] ?? '' ) : $logo;
                $logo_alt  = is_array( $logo ) ? ( $logo['alt'] ?? $title ) : $title;
                $logo_html = '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $logo_alt ) . ' logo" loading="lazy" width="58" height="58">';
            } else {
                $logo_html = '<div class="lsb-lbp-logo-fallback" aria-hidden="true">' . esc_html( $initials ) . '</div>';
            }

            /* phone */
            $phone_html = '';
            if ( $phone ) {
                $phone_clean = preg_replace( '/[^0-9+]/', '', $phone );
                $phone_html  = '<a href="tel:' . esc_attr( $phone_clean ) . '" class="lsb-lbp-phone" itemprop="telephone" aria-label="Call ' . esc_attr( $title ) . '">';
                $phone_html .= '📞 <span>' . esc_html( $phone ) . '</span>';
                $phone_html .= '</a>';
            }

            /* city */
            $city_html = '';
            if ( $city_name ) {
                $city_html  = '<div class="lsb-lbp-city-badge" itemprop="areaServed">📍 <span>' . esc_html( $city_name ) . ', CA</span></div>';
            }

            /* card */
            $out .= '<article class="lsb-lbp-card" role="listitem" itemscope itemtype="https://schema.org/LocalBusiness">';
            $out .= '<meta itemprop="position" content="' . $position . '">';

            /* hidden address microdata */
            if ( $address ) {
                $out .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress" style="display:none;">';
                $out .= '<meta itemprop="streetAddress"   content="' . esc_attr( $address ) . '">';
                if ( $city_name ) $out .= '<meta itemprop="addressLocality" content="' . esc_attr( $city_name ) . '">';
                $out .= '<meta itemprop="addressRegion"   content="CA">';
                $out .= '<meta itemprop="addressCountry"  content="US">';
                $out .= '</div>';
            }

            $out .= '<div class="lsb-lbp-card-top">';
            $out .=   '<div class="lsb-lbp-logo-wrap">' . $logo_html . '</div>';
            $out .=   '<div class="lsb-lbp-card-info">';
            $out .=     '<h3 class="lsb-lbp-card-title" itemprop="name">' . esc_html( $title ) . '</h3>';
            $out .=     '<span class="lsb-lbp-industry-tag" itemprop="knowsAbout">' . $label . '</span>';
            if ( $badge ) $out .= '<div class="lsb-lbp-badge-wrap" style="margin-top:6px;">' . $badge . '</div>';
            $out .=   '</div>';
            $out .= '</div>';

            $out .= $city_html;
            $out .= $phone_html;

            $out .= '<div class="lsb-lbp-card-footer">';
            $out .=   '<a href="' . esc_url( $link ) . '" class="lsb-lbp-visit-btn" itemprop="url" aria-label="Visit ' . esc_attr( $title ) . ' business profile">';
            $out .=     'Visit Business';
            $out .=     '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">';
            $out .=       '<path d="M1 7h12M7 1l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>';
            $out .=     '</svg>';
            $out .=   '</a>';
            $out .= '</div>';

            $out .= '</article>';
            $position++;
        }

        $out .= '</div>'; // .lsb-lbp-grid
        $out .= '</div>'; // .lsb-lbp-group
    }

    /* ----------------------------------------------------------------
     * SALES BLOCK — benefit-led, between listings and FAQ
     * -------------------------------------------------------------- */
    $about_url = home_url( '/about/' );

    $out .= '<div class="lsb-lbp-sales-mid" role="complementary" aria-label="Get your business featured">';
    $out .=   '<div class="lsb-lbp-sales-mid-inner">';

    $out .=     '<div class="lsb-lbp-sales-eyebrow">';
    $out .=       '<span class="lsb-lbp-sales-eyebrow-dot"></span>';
    $out .=       '<span>For Local Business Owners</span>';
    $out .=     '</div>';

    $out .=     '<h2 class="lsb-lbp-sales-title">Want to Be <em>Featured</em> Here?<br>Here\'s What You Get.</h2>';
    $out .=     '<p class="lsb-lbp-sales-desc">Get your own verified landing page, appear in front of customers actively searching for your services across Greater Los Angeles, and start receiving direct leads — no commissions, no middlemen.</p>';

    /* benefit cards */
    $out .=     '<div class="lsb-lbp-sales-benefits">';

    $benefits = [
        [ '🌐', 'Your Own Landing Page',      'A dedicated, SEO-optimized business profile page built and hosted for you — no website needed.' ],
        [ '📍', 'Local City Visibility',        'Your business surfaces in searches across Greater Los Angeles cities we cover.' ],
        [ '📞', 'Direct Customer Leads',       'Customers call or contact you directly from your listing. No lead fees, no middlemen ever.' ],
        [ '✅', 'Verified Business Badge',      'Stand out with a verified badge that builds trust and signals credibility to potential customers.' ],
        [ '🏷️', 'Featured in Industry Lists',  'Your business appears in the featured section for your industry alongside top-rated local pros.' ],
        [ '📈', 'SEO & AEO Optimized',         'Your profile is structured to rank in local search and appear in AI-generated answer overviews.' ],
    ];

    foreach ( $benefits as $b ) {
        $out .= '<div class="lsb-lbp-sales-benefit-card">';
        $out .=   '<span class="lsb-lbp-sales-benefit-icon">' . $b[0] . '</span>';
        $out .=   '<h3 class="lsb-lbp-sales-benefit-title">' . esc_html( $b[1] ) . '</h3>';
        $out .=   '<p class="lsb-lbp-sales-benefit-desc">' . esc_html( $b[2] ) . '</p>';
        $out .= '</div>';
    }

    $out .=     '</div>'; // .lsb-lbp-sales-benefits

    /* CTA */
    $out .=     '<div class="lsb-lbp-sales-cta-row">';
    $out .=       '<a href="' . esc_url( $about_url ) . '" class="lsb-lbp-sales-btn" aria-label="Get your business listed on LocalServiceBiz">';
    $out .=         'Get Listed';
    $out .=         '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M1 7h12M7 1l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $out .=       '</a>';
    $out .=       '<p class="lsb-lbp-sales-cta-note"><strong>Free basic listing available.</strong><br>No credit card required to start.</p>';
    $out .=     '</div>';

    $out .=   '</div>'; // .lsb-lbp-sales-mid-inner
    $out .= '</div>'; // .lsb-lbp-sales-mid

    /* ----------------------------------------------------------------
     * FAQ SECTION — AEO / FAQPage schema target
     * Pull all FAQs from all industries, shuffle daily, show 10
     * -------------------------------------------------------------- */

    // Collect every FAQ pair from all industries (not filtered by grouped)
    $all_faq_pairs = [];
    foreach ( lsb_lbp_industry_meta() as $slug => $meta ) {
        if ( empty( $meta['faq'] ) ) continue;
        foreach ( $meta['faq'] as $pair ) {
            $all_faq_pairs[] = $pair;
        }
    }

    // Daily deterministic shuffle — changes every day, consistent within the day
    $daily_seed = date( 'Ymd' );
    usort( $all_faq_pairs, function( $a, $b ) use ( $daily_seed ) {
        return crc32( $daily_seed . $a['q'] ) - crc32( $daily_seed . $b['q'] );
    } );

    // Show up to 10
    $faq_display = array_slice( $all_faq_pairs, 0, 10 );

    $out .= '<div class="lsb-lbp-faq-section" id="lsb-lbp-faq" itemscope itemtype="https://schema.org/FAQPage">';
    $out .=   '<span class="lsb-lbp-faq-eyebrow">Common Questions</span>';
    $out .=   '<h2 class="lsb-lbp-faq-h2">Frequently Asked Questions</h2>';
    $out .=   '<p class="lsb-lbp-faq-sub">Answers to the most common questions about finding featured local service professionals across Greater Los Angeles.</p>';
    $out .=   '<div class="lsb-lbp-faq-grid">';

    foreach ( $faq_display as $pair ) {
        $out .= '<details class="lsb-lbp-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">';
        $out .=   '<summary class="lsb-lbp-faq-q">';
        $out .=     '<span itemprop="name">' . esc_html( $pair['q'] ) . '</span>';
        $out .=     '<span class="lsb-lbp-faq-toggle" aria-hidden="true">+</span>';
        $out .=   '</summary>';
        $out .=   '<div class="lsb-lbp-faq-answer" itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">';
        $out .=     '<span itemprop="text">' . esc_html( $pair['a'] ) . '</span>';
        $out .=   '</div>';
        $out .= '</details>';
    }

    $out .=   '</div>'; // .lsb-lbp-faq-grid
    $out .= '</div>'; // .lsb-lbp-faq-section

    $out .= '</div>'; // .lsb-lbp-wrapper

    if ( ! $is_preview ) {
        set_transient( $transient_key, $out, DAY_IN_SECONDS );
    }

    return $out;
}
add_shortcode( 'local_businesses_page', 'lsb_local_businesses_page_shortcode' );

endif;
