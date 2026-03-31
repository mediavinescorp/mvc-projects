<?php
/**
 * Shortcode: [service_page]
 *
 * File: /shortcodes/service-single-page.php
 * Called in functions.php:
 *   require_once get_stylesheet_directory() . '/shortcodes/service-single-page.php';
 * Called in template-service.php:
 *   <?php echo do_shortcode('[service_page]'); ?>
 *
 * DUAL-MODE: detects whether the current service post maps to a PARENT or CHILD
 * service_type term and renders accordingly.
 *
 * PARENT MODE (e.g. "Cooling Services")
 *   Sections: Hero → Overview → Child Services Grid → Cities → Businesses → FAQ → CTA
 *
 * CHILD MODE (e.g. "AC Installation")
 *   Sections: Hero → When You Need This → Signs → Cost Factors → Process →
 *             Cities → Businesses → Related Services → FAQ → CTA
 *
 * DETECTION LOGIC:
 *   1. Get the service CPT post slug
 *   2. Find the matching service_type term by slug (slugs match exactly per spec)
 *   3. If term->parent === 0  → PARENT mode
 *   4. If term->parent  >  0  → CHILD mode
 *
 * TAXONOMY RELATIONSHIPS:
 *   service_type  — links service posts to industry_cat terms
 *   industry_cat  — used to find related businesses and cities
 *   city_cat      — used to find cities offering this service
 *
 * ACF FIELDS ON SERVICES CPT:
 *   service_subtitle            Text        Hero support line
 *   hero_intro                  Textarea    Direct answer intro paragraph
 *   hero_image                  Image       Hero visual (right column)
 *   service_overview            WYSIWYG     Main overview content (both modes)
 *   when_you_need_this_service  WYSIWYG     Educational section (child only)
 *   signs_you_need_this_service Repeater    sign_title (Text), sign_description (Textarea)
 *   cost_factors                Repeater    factor_title (Text), factor_description (Textarea)
 *   service_process             Repeater    step_title (Text), step_description (Textarea)
 *   cta_heading                 Text        CTA section
 *   cta_text                    Textarea    CTA section
 *   cta_button_text             Text        CTA button label
 *   cta_button_link             URL         CTA button destination
 */

if ( ! function_exists( 'lsb_service_page_shortcode' ) ) :

function lsb_service_page_shortcode( $atts ) {

    $atts = shortcode_atts( [ 'service' => '' ], $atts, 'service_page' );

    // ── 1. Current service post ───────────────────────────────────────────
    $post_id = get_the_ID();

    if ( ! empty( $atts['service'] ) ) {
        $override = get_page_by_path( sanitize_title( $atts['service'] ), OBJECT, 'services' );
        if ( $override ) $post_id = $override->ID;
    }

    if ( ! $post_id ) {
        return '<!-- [service_page] no post ID found -->';
    }

    $service_slug  = get_post_field( 'post_name',  $post_id );
    $service_title = get_post_field( 'post_title', $post_id );

    // ── 2. Find service_type term by matching slug ────────────────────────
    $service_term = get_term_by( 'slug', $service_slug, 'service_type' );

    if ( ! $service_term || is_wp_error( $service_term ) ) {
        // Fallback: check any service_type term directly assigned to this post
        $assigned = wp_get_post_terms( $post_id, 'service_type' );
        $service_term = ( ! empty( $assigned ) && ! is_wp_error( $assigned ) ) ? $assigned[0] : null;
    }

    // ── 3. Determine mode ────────────────────────────────────────────────
    $is_child  = $service_term && $service_term->parent > 0;
    $is_parent = $service_term && $service_term->parent === 0;

    // ── 4. ACF fields ─────────────────────────────────────────────────────
    $subtitle      = '';
    $hero_intro    = '';
    $hero_image    = '';
    $overview      = '';
    $when_need     = '';
    $signs         = [];
    $cost_factors  = [];
    $process_steps = [];
    $cta_heading   = '';
    $cta_text      = '';
    $cta_btn_text  = '';
    $cta_btn_link  = '';

    if ( function_exists( 'get_field' ) ) {
        $subtitle      = get_field( 'service_subtitle',            $post_id );
        $hero_intro    = get_field( 'hero_intro',                  $post_id );
        $overview      = get_field( 'service_overview',            $post_id );
        $when_need     = get_field( 'when_you_need_this_service',  $post_id );
        $signs         = get_field( 'signs_you_need_this_service', $post_id ) ?: [];
        $cost_factors  = get_field( 'cost_factors',                $post_id ) ?: [];
        $process_steps = get_field( 'service_process',             $post_id ) ?: [];
        $cta_heading   = get_field( 'cta_heading',                 $post_id );
        $cta_text      = get_field( 'cta_text',                    $post_id );
        $cta_btn_text  = get_field( 'cta_button_text',             $post_id );
        $cta_btn_link  = get_field( 'cta_button_link',             $post_id );

        $raw_img = get_field( 'hero_image', $post_id );
        if ( is_array( $raw_img ) && ! empty( $raw_img['url'] ) ) {
            $hero_image = $raw_img['url'];
        } elseif ( is_string( $raw_img ) && ! empty( $raw_img ) ) {
            $hero_image = $raw_img;
        }
    }

   // ── 5. Industry — read industry_cat terms assigned to this service post ──
$industry_terms     = wp_get_post_terms( $post_id, 'industry_cat' );
$industry_term_ids  = [];
$industry_term_slug = '';
$industry_name      = '';
$industry_post_id   = 0;

if ( ! empty( $industry_terms ) && ! is_wp_error( $industry_terms ) ) {
    $industry_term_ids  = wp_list_pluck( $industry_terms, 'term_id' );
    $industry_term_slug = $industry_terms[0]->slug;
    $industry_name      = $industry_terms[0]->name;

    // Look up industries CPT post for industry-level ACF fields
    $ind_posts = get_posts( [
        'post_type'      => 'industries',
        'name'           => $industry_term_slug,
        'posts_per_page' => 1,
        'post_status'    => 'publish',
    ] );

    if ( ! empty( $ind_posts ) ) {
        $industry_post_id = $ind_posts[0]->ID;
    }
}

    // ── 6. Child services (PARENT mode only) ─────────────────────────────
    $child_services = [];
    if ( $is_parent && $service_term ) {
        // Get child terms of the parent service_type term
        $child_terms = get_terms( [
            'taxonomy'   => 'service_type',
            'parent'     => $service_term->term_id,
            'hide_empty' => false,
        ] );

        if ( ! empty( $child_terms ) && ! is_wp_error( $child_terms ) ) {
            foreach ( $child_terms as $child_term ) {
                // Each child term slug matches a service CPT post slug
                $child_post = get_posts( [
                    'post_type'      => 'services',
                    'name'           => $child_term->slug,
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                ] );
                $child_services[] = [
                    'term' => $child_term,
                    'post' => ! empty( $child_post ) ? $child_post[0] : null,
                ];
            }
        }
    }

    // ── 7. Sibling services (CHILD mode only) ────────────────────────────
    $sibling_services = [];
    if ( $is_child && $service_term ) {
        $sibling_terms = get_terms( [
            'taxonomy'   => 'service_type',
            'parent'     => $service_term->parent,
            'hide_empty' => false,
            'exclude'    => [ $service_term->term_id ],
        ] );

        if ( ! empty( $sibling_terms ) && ! is_wp_error( $sibling_terms ) ) {
            foreach ( $sibling_terms as $sib_term ) {
                $sib_post = get_posts( [
                    'post_type'      => 'services',
                    'name'           => $sib_term->slug,
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                ] );
                if ( ! empty( $sib_post ) ) {
                    $sibling_services[] = [
                        'term' => $sib_term,
                        'post' => $sib_post[0],
                    ];
                }
            }
        }
    }

    // ── 8. Parent term + post link (CHILD mode breadcrumb) ───────────────
    $parent_term      = null;
    $parent_post_url  = '';
    $parent_post_name = '';
    if ( $is_child && $service_term ) {
        $parent_term = get_term( $service_term->parent, 'service_type' );
        if ( $parent_term && ! is_wp_error( $parent_term ) ) {
            $parent_post_name = $parent_term->name;
            $parent_cpt = get_posts( [
                'post_type'      => 'services',
                'name'           => $parent_term->slug,
                'posts_per_page' => 1,
                'post_status'    => 'publish',
            ] );
            if ( ! empty( $parent_cpt ) ) {
                $parent_post_url = get_permalink( $parent_cpt[0]->ID );
            }
        }
    }



    // ── 8B. Random parent services from same industry ─────────────────────
    $industry_parent_services = [];
    $exclude_service_ids      = [];
    $exclude_service_slugs    = [];

    // Exclude current service post
    $exclude_service_ids[]   = $post_id;
    $exclude_service_slugs[] = $service_slug;

    // Exclude current parent post if child mode
    if ( $is_child && ! empty( $parent_term ) ) {
        $exclude_service_slugs[] = $parent_term->slug;

        $parent_cpt_for_exclude = get_posts( [
            'post_type'      => 'services',
            'name'           => $parent_term->slug,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ] );

        if ( ! empty( $parent_cpt_for_exclude ) ) {
            $exclude_service_ids[] = (int) $parent_cpt_for_exclude[0];
        }
    }

    if ( ! empty( $industry_term_ids ) ) {

        // Get parent service_type terms only
        $parent_service_terms = get_terms( [
            'taxonomy'   => 'service_type',
            'hide_empty' => false,
            'parent'     => 0,
        ] );

        if ( ! empty( $parent_service_terms ) && ! is_wp_error( $parent_service_terms ) ) {

            $candidate_parent_posts = [];

            foreach ( $parent_service_terms as $parent_service_term ) {

                // Match parent service CPT by slug
                $parent_service_post = get_posts( [
                    'post_type'      => 'services',
                    'name'           => $parent_service_term->slug,
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                ] );

                if ( empty( $parent_service_post ) ) {
                    continue;
                }

                $parent_service_post = $parent_service_post[0];

                // Exclude duplicates/current page/current parent
                if ( in_array( (int) $parent_service_post->ID, $exclude_service_ids, true ) ) {
                    continue;
                }

                if ( in_array( $parent_service_term->slug, $exclude_service_slugs, true ) ) {
                    continue;
                }

                // Must belong to same industry
                $parent_industry_terms = wp_get_post_terms( $parent_service_post->ID, 'industry_cat', [ 'fields' => 'ids' ] );

                if ( empty( $parent_industry_terms ) || is_wp_error( $parent_industry_terms ) ) {
                    continue;
                }

                $shared_industry = array_intersect( $industry_term_ids, $parent_industry_terms );

                if ( empty( $shared_industry ) ) {
                    continue;
                }

                $candidate_parent_posts[] = [
                    'term' => $parent_service_term,
                    'post' => $parent_service_post,
                ];
            }

            if ( ! empty( $candidate_parent_posts ) ) {
                shuffle( $candidate_parent_posts );
                $industry_parent_services = array_slice( $candidate_parent_posts, 0, 4 );
            }
        }
    }


  // ── 9. Cities by shared industry ─────────────────────────────────────
$city_posts = [];

if ( ! empty( $industry_term_ids ) ) {
    $cities_query = new WP_Query( [
        'post_type'      => 'cities',
        'post_status'    => 'publish',
        'posts_per_page' => 12, // change to 10, 12, 16, or 20
        'orderby'        => 'rand',
        'tax_query'      => [ [
            'taxonomy' => 'industry_cat',
            'field'    => 'term_id',
            'terms'    => $industry_term_ids,
        ] ],
    ] );

    $city_posts = $cities_query->posts;
    wp_reset_postdata();
}

   // ── 10. Businesses — up to 3 featured + 4 non-featured ──────────────────
$all_biz             = [];
$biz_posts           = [];
$biz_featured_cards  = [];
$biz_secondary_cards = [];

if ( ! empty( $industry_term_ids ) ) {
    $biz_query = new WP_Query( [
        'post_type'      => 'businesses',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'business_rating',
        'order'          => 'DESC',
        'tax_query'      => [ [
            'taxonomy' => 'industry_cat',
            'field'    => 'term_id',
            'terms'    => $industry_term_ids,
        ] ],
    ] );

    $all_biz = $biz_query->posts;
    wp_reset_postdata();

    $featured_pool = [];
    $rest_pool     = [];

    foreach ( $all_biz as $b ) {
        $is_featured = function_exists( 'get_field' ) ? get_field( 'featured_business', $b->ID ) : false;
        $plan        = function_exists( 'get_field' ) ? get_field( 'plan_tier', $b->ID ) : '';

        $featured_flag = (
            $is_featured === true ||
            $is_featured === 1 ||
            $is_featured === '1' ||
            strtolower( (string) $plan ) === 'premium'
        );

        if ( $featured_flag ) {
            $featured_pool[] = $b;
        } else {
            $rest_pool[] = $b;
        }
    }

    if ( ! empty( $featured_pool ) ) {
        shuffle( $featured_pool );
    }

    if ( ! empty( $rest_pool ) ) {
        shuffle( $rest_pool );
    }

    $biz_featured_cards  = array_slice( $featured_pool, 0, 3 );
    $biz_secondary_cards = array_slice( $rest_pool, 0, 4 );

    $biz_posts = array_merge( $biz_featured_cards, $biz_secondary_cards );
}



   
    // ── 11. FAQs (filtered by industry_cat) ─────────────────────────────
    $faq_posts = [];
    if ( ! empty( $industry_term_ids ) ) {
        $faq_query = new WP_Query( [
            'post_type'      => 'faqs',
            'post_status'    => 'publish',
            'posts_per_page' => 7,
            'orderby'        => 'none',
            'tax_query'      => [ [
                'taxonomy' => 'industry_cat',
                'field'    => 'term_id',
              'terms'    => $industry_term_ids,
            ] ],
        ] );
        $faq_posts = $faq_query->posts;
        wp_reset_postdata();
    }

    // Daily shuffle for FAQs
    if ( ! empty( $faq_posts ) ) {
        $daily_seed = (int) date( 'Ymd' ) + (int) ( $industry_term_ids[0] ?? crc32( $service_slug ) );
        usort( $faq_posts, function( $a, $b ) use ( $daily_seed ) {
            return crc32( $daily_seed . $a->ID ) - crc32( $daily_seed . $b->ID );
        } );
    }

    // ── 12. Blog Posts (filtered by industry_cat) ───────────────────────────
    $blog_posts = [];
   if ( ! empty( $industry_term_ids ) ) {
        $blog_by_industry = new WP_Query( [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 3,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'tax_query'      => [ [
                'taxonomy' => 'industry_cat',
                'field'    => 'term_id',
              'terms'    => $industry_term_ids,
            ] ],
        ] );
        $blog_posts = $blog_by_industry->posts;
        wp_reset_postdata();
    }
    // Fallback: recent posts if none tagged to this industry
    if ( empty( $blog_posts ) ) {
        $blog_recent = new WP_Query( [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 3,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
        $blog_posts = $blog_recent->posts;
        wp_reset_postdata();
    }

    // ── 12. CTA fallbacks ─────────────────────────────────────────────────
    if ( ! $cta_heading )  $cta_heading  = 'Need ' . $service_title . ' in Your Area?';
    if ( ! $cta_text )     $cta_text     = 'Connect with verified local professionals offering ' . $service_title . ' across Greater Los Angeles. Browse profiles and reach out directly.';
    if ( ! $cta_btn_text ) $cta_btn_text = 'Find a Professional';
    if ( ! $cta_btn_link ) $cta_btn_link = home_url( '/businesses/' );

    // ── 13. URLs ──────────────────────────────────────────────────────────
  $home_url     = trailingslashit( home_url() );
$services_url = $home_url . 'services/';
$industry_url = $industry_term_slug ? $home_url . 'industries/' . $industry_term_slug . '/' : $home_url . 'industries/';

    // ── 14. Icon helper ───────────────────────────────────────────────────
    $svc_icon = function_exists( 'lsb_get_service_icon' ) ? lsb_get_service_icon( $service_slug ) : '🔧';

    // ── 15. Render ────────────────────────────────────────────────────────
    ob_start();
    ?>

    <!-- ============================================================
         STYLES
    ============================================================ -->
    <style id="lsb-service-page-css">

    /* ============================
       COLOR SYSTEM
    ============================ */
    .lsb-sp-dark-bg {
        --lsb-sp-heading: #F5F7FA;
        --lsb-sp-body:    rgba(255,255,255,0.60);
        --lsb-sp-muted:   rgba(255,255,255,0.35);
        --lsb-sp-label:   #00C9A7;
        --lsb-sp-border:  rgba(255,255,255,0.08);
        --lsb-sp-card-bg: rgba(255,255,255,0.04);
    }
    .lsb-sp-light-bg {
        --lsb-sp-heading: #0D1B2A;
        --lsb-sp-body:    #3D4F63;
        --lsb-sp-muted:   #8A9BB0;
        --lsb-sp-label:   #00A88C;
        --lsb-sp-border:  #E4EAF2;
        --lsb-sp-card-bg: #F5F7FA;
    }

    /* ============================
       SHARED TYPOGRAPHY
    ============================ */
    .lsb-sp-label {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--lsb-sp-label);
        margin-bottom: 12px;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-sp-h2 {
        font-family: 'Syne', sans-serif;
        font-size: clamp(1.8rem, 3.5vw, 2.6rem);
        font-weight: 800;
        color: var(--lsb-sp-heading) !important;
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-bottom: 16px;
    }
    .lsb-sp-h3 {
        font-family: 'Syne', sans-serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--lsb-sp-heading) !important;
        letter-spacing: -0.01em;
        line-height: 1.3;
        margin: 0 0 8px !important;
        padding: 0 !important;
    }
    .lsb-sp-body-p {
        font-size: 1rem;
        color: var(--lsb-sp-body) !important;
        line-height: 1.8;
        font-family: 'DM Sans', sans-serif;
    }

    /* ============================
       BREADCRUMB
    ============================ */
    .lsb-sp-breadcrumb-bar {
        background: #0D1B2A;
        padding: 0 40px;
        height: 44px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        margin-top: 72px;
    }
    .lsb-sp-breadcrumb {
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
    .lsb-sp-breadcrumb a           { color: rgba(255,255,255,0.4); text-decoration: none; transition: color .2s; }
    .lsb-sp-breadcrumb a:hover     { color: #00C9A7; }
    .lsb-sp-breadcrumb-sep         { color: rgba(255,255,255,0.2); }
    .lsb-sp-breadcrumb-current     { color: #00C9A7; font-weight: 500; }

    /* ============================
       SECTION 1 — HERO
    ============================ */
    .lsb-sp-hero {
        background: #0D1B2A;
        padding: 56px 40px 64px;
        position: relative;
        overflow: hidden;
    }
    .lsb-sp-hero-grid {
        position: absolute; inset: 0; pointer-events: none;
        background-image:
            linear-gradient(rgba(0,201,167,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,201,167,0.04) 1px, transparent 1px);
        background-size: 60px 60px;
    }
    .lsb-sp-hero-glow-1 {
        position: absolute; pointer-events: none;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(0,201,167,0.1) 0%, transparent 70%);
        top: -120px; right: -80px;
    }
    .lsb-sp-hero-glow-2 {
        position: absolute; pointer-events: none;
        width: 340px; height: 340px;
        background: radial-gradient(circle, rgba(244,197,66,0.06) 0%, transparent 70%);
        bottom: 0; left: 100px;
    }
    .lsb-sp-hero-inner {
        max-width: 1200px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }
    .lsb-sp-hero-cols {
        display: grid;
        grid-template-columns: 1fr 420px;
        gap: 64px;
        align-items: center;
    }
    .lsb-sp-hero-cols.no-image { grid-template-columns: 1fr; max-width: 780px; }

    /* Mode badge */
    .lsb-sp-mode-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(0,201,167,0.12);
        border: 1px solid rgba(0,201,167,0.25);
        border-radius: 100px;
        padding: 6px 16px;
        margin-bottom: 24px;
    }
    .lsb-sp-mode-badge span {
        color: #00C9A7;
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-sp-mode-badge-icon { font-size: 1rem; }

    /* Parent breadcrumb pill (child mode) */
    .lsb-sp-parent-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 100px;
        padding: 5px 14px;
        margin-bottom: 20px;
        text-decoration: none;
        transition: all .2s;
    }
    .lsb-sp-parent-pill:hover { background: rgba(0,201,167,0.1); border-color: rgba(0,201,167,0.3); }
    .lsb-sp-parent-pill span {
        color: rgba(255,255,255,0.55);
        font-size: 0.78rem;
        font-family: 'DM Sans', sans-serif;
        font-weight: 500;
    }
    .lsb-sp-parent-pill-arrow { color: #00C9A7; font-size: 0.72rem; }

    .lsb-sp-hero-icon {
        font-size: 2.4rem;
        line-height: 1;
        margin-bottom: 16px;
        display: block;
    }
   .lsb-sp-h1 {
        font-family: 'Syne', sans-serif !important;
        font-size: clamp(2.2rem, 4.5vw, 3.8rem) !important;
        font-weight: 800 !important;
        color: #F5F7FA !important;
        line-height: 1 !important;
        letter-spacing: -0.01em !important;
        margin-bottom: 20px !important;
    }
    .lsb-sp-h1 span { display: block !important; line-height: 1 !important; }
    .lsb-sp-h1 em { font-style: normal !important; color: #00C9A7 !important; display: block !important; line-height: 1 !important; margin-top: 4px !important; }

    .lsb-sp-hero-subtitle {
        font-size: 1rem;
        color: #00C9A7;
        font-family: 'DM Sans', sans-serif;
        font-weight: 500;
        margin-bottom: 14px;
        letter-spacing: 0.01em;
    }
    .lsb-sp-hero-intro {
        font-size: 1.05rem;
        color: rgba(255,255,255,0.6);
        line-height: 1.8;
        font-weight: 300;
        font-family: 'DM Sans', sans-serif;
        margin-bottom: 32px;
        max-width: 600px;
    }
    .lsb-sp-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }
    .lsb-sp-hero-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 100px;
        padding: 7px 16px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.82rem;
        color: rgba(255,255,255,0.6);
    }
    .lsb-sp-hero-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: #00C9A7; flex-shrink: 0; }
    .lsb-sp-hero-image-wrap     { border-radius: 20px; overflow: hidden; }
    .lsb-sp-hero-image-wrap img { width: 100%; height: auto; display: block; border-radius: 20px; }

    /* ============================
       SECTION 2 — OVERVIEW
    ============================ */
    .lsb-sp-overview-section { background: #ffffff; padding: 80px 40px; }
    .lsb-sp-overview-inner   { max-width: 820px; margin: 0 auto; }
    .lsb-sp-overview-content {
        font-family: 'DM Sans', sans-serif;
        color: var(--lsb-sp-body) !important;
        line-height: 1.8;
    }
    .lsb-sp-overview-content h3 {
        font-family: 'Syne', sans-serif !important;
        font-size: 1.15rem !important;
        font-weight: 700 !important;
        color: var(--lsb-sp-heading) !important;
        letter-spacing: -0.01em !important;
        margin-top: 32px !important;
        margin-bottom: 10px !important;
    }
    .lsb-sp-overview-content h3:first-child { margin-top: 0 !important; }
    .lsb-sp-overview-content p        { font-size: 0.97rem !important; color: var(--lsb-sp-body) !important; line-height: 1.8 !important; margin-bottom: 16px !important; }
    .lsb-sp-overview-content strong   { color: var(--lsb-sp-heading) !important; font-weight: 600 !important; }
    .lsb-sp-overview-content ul       { padding-left: 20px; margin: 8px 0 16px; }
    .lsb-sp-overview-content ul li    { font-size: 0.97rem !important; color: var(--lsb-sp-body) !important; line-height: 1.8 !important; margin-bottom: 4px !important; }

    /* ============================
       CHILD SERVICES GRID (parent mode)
    ============================ */
    .lsb-sp-children-section { background: #F5F7FA; padding: 80px 40px; }
    .lsb-sp-children-inner   { max-width: 1200px; margin: 0 auto; }
    .lsb-sp-children-header  { max-width: 680px; margin-bottom: 48px; }
    .lsb-sp-children-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    .lsb-sp-child-card {
        background: #ffffff;
        border: 1px solid var(--lsb-sp-border);
        border-radius: 16px;
        padding: 28px 24px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        text-decoration: none;
        transition: all .25s;
        position: relative;
        overflow: hidden;
    }
    .lsb-sp-child-card::before {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        background: #00C9A7;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s;
    }
    .lsb-sp-child-card:hover {
        border-color: rgba(0,201,167,0.35);
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0,201,167,0.08);
    }
    .lsb-sp-child-card:hover::before { transform: scaleX(1); }
    .lsb-sp-child-icon {
        width: 48px; height: 48px;
        background: rgba(0,201,167,0.08);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .lsb-sp-child-name {
        font-family: 'Syne', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--lsb-sp-heading) !important;
        letter-spacing: -0.01em;
        line-height: 1.3;
    }
    .lsb-sp-child-desc {
        font-size: 0.875rem;
        color: var(--lsb-sp-body) !important;
        line-height: 1.7;
        font-family: 'DM Sans', sans-serif;
        flex: 1;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .lsb-sp-child-cta {
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
    .lsb-sp-child-card:hover .lsb-sp-child-cta { gap: 10px; }




    /* ============================
       WHEN YOU NEED + SIGNS (child mode)
    ============================ */
    .lsb-sp-when-section { background: #F5F7FA; padding: 80px 40px; }
    .lsb-sp-when-inner   {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 64px;
        align-items: start;
    }
    .lsb-sp-when-content { font-family: 'DM Sans', sans-serif; }
    .lsb-sp-when-content p        { font-size: 0.97rem !important; color: var(--lsb-sp-body) !important; line-height: 1.8 !important; margin-bottom: 16px !important; }
    .lsb-sp-when-content h3       { font-family: 'Syne', sans-serif !important; font-size: 1.05rem !important; font-weight: 700 !important; color: var(--lsb-sp-heading) !important; margin-top: 28px !important; margin-bottom: 8px !important; }
    .lsb-sp-when-content strong   { color: var(--lsb-sp-heading) !important; font-weight: 600 !important; }
    .lsb-sp-when-content ul       { padding-left: 20px; }
    .lsb-sp-when-content ul li    { font-size: 0.97rem !important; color: var(--lsb-sp-body) !important; line-height: 1.8 !important; margin-bottom: 4px !important; }

    /* Signs list */
    .lsb-sp-signs-panel {
        background: #ffffff;
        border: 1px solid var(--lsb-sp-border);
        border-radius: 16px;
        padding: 32px 28px;
    }
    .lsb-sp-signs-title {
        font-family: 'Syne', sans-serif !important;
        font-size: 0.82rem !important;
        font-weight: 700 !important;
        color: var(--lsb-sp-muted) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.1em !important;
        margin-bottom: 20px !important;
    }
    .lsb-sp-sign-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid var(--lsb-sp-border);
    }
    .lsb-sp-sign-item:last-child { border-bottom: none; padding-bottom: 0; }
    .lsb-sp-sign-item:first-of-type { padding-top: 0; }
    .lsb-sp-sign-dot {
        width: 28px; height: 28px;
        background: rgba(0,201,167,0.1);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        color: #00C9A7;
        font-size: 0.85rem;
        font-weight: 700;
        font-family: 'Syne', sans-serif;
        margin-top: 1px;
    }
    .lsb-sp-sign-text { display: flex; flex-direction: column; gap: 4px; }
    .lsb-sp-sign-title { font-family: 'Syne', sans-serif !important; font-size: 0.92rem !important; font-weight: 700 !important; color: var(--lsb-sp-heading) !important; margin: 0 !important; }
    .lsb-sp-sign-desc  { font-family: 'DM Sans', sans-serif !important; font-size: 0.85rem !important; color: var(--lsb-sp-body) !important; line-height: 1.6 !important; margin: 0 !important; }

    /* ============================
       COST FACTORS (child mode)
    ============================ */
    .lsb-sp-cost-section { background: #ffffff; padding: 80px 40px; }
    .lsb-sp-cost-inner   { max-width: 1200px; margin: 0 auto; }
    .lsb-sp-cost-header  { max-width: 680px; margin-bottom: 48px; }
    .lsb-sp-cost-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    .lsb-sp-cost-card {
        background: #F5F7FA;
        border: 1px solid var(--lsb-sp-border);
        border-radius: 16px;
        padding: 28px 24px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: all .25s;
        position: relative;
        overflow: hidden;
    }
    .lsb-sp-cost-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: #F4C542;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s;
    }
    .lsb-sp-cost-card:hover { border-color: rgba(244,197,66,0.4); box-shadow: 0 8px 28px rgba(0,0,0,0.05); background: #ffffff; }
    .lsb-sp-cost-card:hover::before { transform: scaleX(1); }
    .lsb-sp-cost-num {
        font-family: 'Syne', sans-serif;
        font-size: 2rem;
        font-weight: 800;
        color: rgba(244,197,66,0.5);
        letter-spacing: -0.05em;
        line-height: 1;
    }
    .lsb-sp-cost-title { font-family: 'Syne', sans-serif !important; font-size: 1rem !important; font-weight: 700 !important; color: var(--lsb-sp-heading) !important; letter-spacing: -0.01em !important; margin: 0 !important; }
    .lsb-sp-cost-desc  { font-size: 0.875rem !important; color: var(--lsb-sp-body) !important; line-height: 1.7 !important; font-family: 'DM Sans', sans-serif !important; margin: 0 !important; flex: 1; }

    /* ============================
       SERVICE PROCESS (child mode)
    ============================ */
    .lsb-sp-process-section { background: #0D1B2A; padding: 80px 40px; position: relative; overflow: hidden; }
    .lsb-sp-process-grid-bg {
        position: absolute; inset: 0; pointer-events: none;
        background-image:
            linear-gradient(rgba(0,201,167,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,201,167,0.04) 1px, transparent 1px);
        background-size: 60px 60px;
    }
    .lsb-sp-process-inner  { max-width: 1200px; margin: 0 auto; position: relative; z-index: 1; }
    .lsb-sp-process-header { max-width: 680px; margin-bottom: 48px; }
    .lsb-sp-process-steps {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2px;
        background: rgba(255,255,255,0.06);
        border-radius: 16px;
        overflow: hidden;
    }
    .lsb-sp-process-step {
        background: rgba(255,255,255,0.03);
        padding: 40px 32px;
        position: relative;
        transition: background .2s;
    }
    .lsb-sp-process-step:hover { background: rgba(255,255,255,0.06); }
    .lsb-sp-step-num {
        font-family: 'Syne', sans-serif;
        font-size: 3.5rem;
        font-weight: 800;
        color: rgba(255,255,255,0.05);
        line-height: 1;
        margin-bottom: 20px;
        letter-spacing: -0.05em;
    }
    .lsb-sp-step-icon {
        width: 48px; height: 48px;
        background: #00C9A7;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        margin-bottom: 18px;
    }
    .lsb-sp-step-title { font-family: 'Syne', sans-serif !important; font-size: 1.1rem !important; font-weight: 700 !important; color: #F5F7FA !important; letter-spacing: -0.01em !important; margin-bottom: 10px !important; }
    .lsb-sp-step-desc  { font-size: 0.88rem !important; color: rgba(255,255,255,0.45) !important; line-height: 1.7 !important; font-family: 'DM Sans', sans-serif !important; font-weight: 300 !important; margin: 0 !important; }
    .lsb-sp-process-disclaimer {
        margin-top: 32px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.78rem;
        color: rgba(255,255,255,0.28);
        line-height: 1.7;
        font-style: italic;
        font-weight: 300;
        text-align: center;
        max-width: 680px;
        margin-left: auto;
        margin-right: auto;
    }

    /* ============================
       CITIES GRID
    ============================ */
    .lsb-sp-cities-section { background: #F5F7FA; padding: 80px 40px; }
    .lsb-sp-cities-inner   { max-width: 1200px; margin: 0 auto; }
    .lsb-sp-cities-header  { max-width: 680px; margin-bottom: 48px; }
    .lsb-sp-cities-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    .lsb-sp-city-card {
        background: #ffffff;
        border: 1px solid var(--lsb-sp-border);
        border-radius: 14px;
        padding: 22px 18px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        text-decoration: none;
        transition: all .25s;
    }
    .lsb-sp-city-card:hover { border-color: rgba(0,201,167,0.35); transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
    .lsb-sp-city-pin  { font-size: 1.1rem; }
    .lsb-sp-city-name { font-family: 'Syne', sans-serif; font-size: 0.97rem; font-weight: 700; color: var(--lsb-sp-heading) !important; letter-spacing: -0.01em; }
    .lsb-sp-city-cta  { font-size: 0.78rem; font-weight: 600; color: #00A88C !important; font-family: 'DM Sans', sans-serif; margin-top: 4px; }

    /* ============================
       BUSINESSES
    ============================ */
    .lsb-sp-biz-section { background: #ffffff; padding: 80px 40px; }
    .lsb-sp-biz-inner   { max-width: 1200px; margin: 0 auto; }
    .lsb-sp-biz-header  { max-width: 680px; margin-bottom: 48px; }
    .lsb-sp-biz-cards   { display: flex; flex-direction: column; gap: 16px; }

  /* Featured card — redesigned */
    .lsb-sp-biz-card-featured {
        background: #ffffff;
        border: 1px solid #E4EAF2;
        border-left: 6px solid #00C9A7;
        border-radius: 20px;
        padding: 28px 28px 24px 28px;
        display: flex;
        flex-direction: column;
        gap: 0;
        margin-bottom: 8px;
        transition: all .25s;
        position: relative;
        overflow: hidden;
    }
    .lsb-sp-biz-card-featured:hover { box-shadow: 0 16px 48px rgba(0,201,167,0.1); transform: translateY(-3px); border-color: #00C9A7; border-left-color: #00C9A7; }

    .lsb-sp-fc-top {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 16px;
    }
    .lsb-sp-fc-logo {
        width: 60px; height: 60px;
        border-radius: 14px;
        overflow: hidden;
        background: #0D1B2A;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .lsb-sp-fc-logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .lsb-sp-fc-initials { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.05rem; color: #00C9A7; }
    .lsb-sp-fc-identity { flex: 1; min-width: 0; }
    .lsb-sp-fc-badge {
        display: inline-flex; align-items: center; gap: 5px;
        background: rgba(0,201,167,0.10); border: 1px solid rgba(0,201,167,0.25);
        border-radius: 100px; padding: 3px 10px;
        font-size: 0.67rem; font-weight: 600; color: #00A88C;
        letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 6px;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-sp-fc-badge-dot { width: 5px; height: 5px; border-radius: 50%; background: #00C9A7; display: inline-block; }
    .lsb-sp-fc-name {
        font-family: 'Syne', sans-serif !important; font-weight: 800 !important;
        font-size: 1.1rem !important; color: #0D1B2A !important;
        letter-spacing: -0.02em !important; line-height: 1.2 !important; margin: 0 0 3px !important;
    }
    .lsb-sp-fc-industry {
        font-size: 0.78rem; color: #8A9BB0; font-weight: 400;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-sp-fc-right {
        display: flex; flex-direction: column; align-items: flex-end; gap: 7px; flex-shrink: 0;
    }
    .lsb-sp-fc-rating {
        display: flex; align-items: center; gap: 5px;
        background: #FFFBEC; border: 1px solid rgba(244,197,66,0.3);
        border-radius: 100px; padding: 5px 12px;
    }
    .lsb-sp-fc-stars { color: #F4C542; font-size: 0.8rem; }
    .lsb-sp-fc-rating-num { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 0.82rem; color: #0D1B2A; }
    .lsb-sp-fc-plan {
        font-size: 0.65rem; font-weight: 700; color: #F4C542;
        background: #0D1B2A; border-radius: 100px; padding: 3px 10px;
        letter-spacing: 0.08em; text-transform: uppercase;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-sp-fc-divider { height: 1px; background: #F0F4F8; margin: 0 0 16px; }
    .lsb-sp-fc-desc {
        font-size: 0.88rem !important; color: #5A6E83 !important;
        line-height: 1.7 !important; font-weight: 300 !important;
        font-family: 'DM Sans', sans-serif !important; margin: 0 0 16px !important;
    }
    .lsb-sp-fc-meta-row {
        display: flex; align-items: center; flex-wrap: wrap;
        gap: 6px 18px; margin-bottom: 20px;
    }
    .lsb-sp-fc-meta-item {
        display: flex; align-items: center; gap: 7px;
        font-size: 0.78rem; color: #3D4F63; font-weight: 400;
        font-family: 'DM Sans', sans-serif; text-decoration: none;
        transition: color .2s;
    }
    .lsb-sp-fc-meta-item:hover { color: #00A88C; }
    .lsb-sp-fc-meta-icon {
        width: 28px; height: 28px; border-radius: 8px;
        background: #F5F7FA; border: 1px solid #E4EAF2;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.78rem; flex-shrink: 0;
    }
    .lsb-sp-fc-footer {
        display: flex; align-items: center;
        justify-content: space-between; flex-wrap: wrap; gap: 12px;
        padding-top: 16px; border-top: 1px solid #F0F4F8;
    }
    .lsb-sp-fc-location {
        display: flex; align-items: center; gap: 7px;
        font-size: 0.78rem; color: #8A9BB0;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-sp-fc-loc-dot { width: 6px; height: 6px; border-radius: 50%; background: #00C9A7; flex-shrink: 0; }
    .lsb-sp-fc-actions { display: flex; gap: 8px; align-items: center; }
    .lsb-sp-fc-btn-primary {
        background: #0D1B2A !important; color: #ffffff !important;
        font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
        font-size: 0.8rem !important; padding: 10px 22px !important;
        border-radius: 10px !important; text-decoration: none !important;
        letter-spacing: 0.02em !important; white-space: nowrap;
        transition: background .2s !important; display: inline-block !important;
    }
    .lsb-sp-fc-btn-primary:hover { background: #00C9A7 !important; color: #0D1B2A !important; }
    .lsb-sp-fc-btn-secondary {
        background: transparent !important; color: #0D1B2A !important;
        font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
        font-size: 0.8rem !important; padding: 10px 18px !important;
        border-radius: 10px !important; border: 1.5px solid #E4EAF2 !important;
        text-decoration: none !important; white-space: nowrap;
        transition: all .2s !important; display: inline-block !important;
    }
    .lsb-sp-fc-btn-secondary:hover { border-color: #00C9A7 !important; color: #00A88C !important; }

    /* Photo strip inside featured card */
    .lsb-sp-biz-photo-strip {
        display: flex; gap: 8px;
        margin: 16px 0 0; border-radius: 12px; overflow: hidden;
    }
    .lsb-sp-biz-photo-strip-item {
        flex: 1; min-width: 0; border-radius: 10px;
        overflow: hidden; background: #EAF0F5; aspect-ratio: 1 / 1;
    }
    .lsb-sp-biz-photo-strip-item img {
        display: block; width: 100%; height: 100%; object-fit: cover;
    }
    @media (max-width: 700px) {
        .lsb-sp-biz-card-featured { padding: 20px 18px; }
        .lsb-sp-fc-top { flex-wrap: wrap; }
        .lsb-sp-fc-right { flex-direction: row; align-items: center; width: 100%; justify-content: flex-start; }
        .lsb-sp-fc-actions { width: 100%; }
        .lsb-sp-fc-btn-primary, .lsb-sp-fc-btn-secondary { flex: 1; text-align: center !important; }
    }

    /* Secondary cards grid */
    .lsb-sp-biz-secondary-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
    .lsb-sp-biz-card-secondary {
        background: #0D1B2A;
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 14px;
        padding: 22px 24px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: all .25s;
        text-decoration: none;
    }
    .lsb-sp-biz-card-secondary:hover { border-color: rgba(0,201,167,0.3); box-shadow: 0 8px 28px rgba(0,0,0,0.2); transform: translateY(-2px); }
    .lsb-sp-biz-sec-name {
        font-family: 'Syne', sans-serif;
        font-size: 0.95rem;
        font-weight: 700;
        color: #F5F7FA;
        margin: 0;
        line-height: 1.3;
    }
    .lsb-sp-biz-sec-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
    }
    .lsb-sp-biz-sec-phone {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.8rem;
        color: rgba(245,247,250,0.65);
        text-decoration: none;
        transition: color .2s;
    }
    .lsb-sp-biz-sec-phone:hover { color: #00C9A7; }
    .lsb-sp-biz-sec-rating {
        display: flex;
        align-items: center;
        gap: 4px;
        font-family: 'Syne', sans-serif;
        font-size: 0.82rem;
        font-weight: 700;
        color: #F4C542;
    }
    .lsb-sp-biz-sec-view {
        display: inline-block;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.75rem;
        font-weight: 600;
        color: #00C9A7;
        text-decoration: none;
        margin-top: 2px;
        letter-spacing: 0.02em;
        transition: opacity .2s;
    }
    .lsb-sp-biz-sec-view:hover { opacity: 0.75; }
    @media (max-width: 700px) {
        .lsb-sp-biz-secondary-grid { grid-template-columns: 1fr; }
        .lsb-sp-biz-card-featured  { flex-direction: column; align-items: flex-start; padding: 24px; }
    }
    .lsb-sp-biz-card {
        background: #F5F7FA;
        border: 1px solid var(--lsb-sp-border);
        border-radius: 16px;
        padding: 28px 32px;
        display: flex;
        align-items: center;
        gap: 24px;
        transition: all .25s;
    }
    .lsb-sp-biz-card:hover { border-color: rgba(0,201,167,0.4) !important; box-shadow: 0 8px 32px rgba(0,0,0,0.07) !important; background: #ffffff !important; }
    .lsb-sp-biz-logo {
        width: 64px; height: 64px;
        border-radius: 12px;
        overflow: hidden;
        flex-shrink: 0;
        background: #0D1B2A;
        display: flex; align-items: center; justify-content: center;
    }
    .lsb-sp-biz-logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .lsb-sp-biz-initials { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem; color: #00C9A7; }
    .lsb-sp-biz-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
    .lsb-sp-biz-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .lsb-sp-biz-name { font-family: 'Syne', sans-serif !important; font-size: 1.05rem !important; font-weight: 700 !important; color: var(--lsb-sp-heading) !important; margin: 0 !important; }
    .lsb-sp-biz-rating { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
    .lsb-sp-star-full, .lsb-sp-star-half { color: #F4C542; font-size: 0.95rem; }
    .lsb-sp-star-empty { color: #C8D4E0; font-size: 0.95rem; }
    .lsb-sp-rating-num { font-family: 'Syne', sans-serif; font-size: 0.88rem; font-weight: 700; color: var(--lsb-sp-heading) !important; }
    .lsb-sp-biz-desc { font-size: 0.88rem !important; color: var(--lsb-sp-body) !important; line-height: 1.6 !important; font-family: 'DM Sans', sans-serif !important; margin: 0 !important; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .lsb-sp-biz-contact {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 20px;
        margin-top: 4px;
        padding-top: 12px;
        border-top: 1px solid var(--lsb-sp-border);
    }
    .lsb-sp-biz-contact-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--lsb-sp-body) !important;
        text-decoration: none !important;
        transition: color .2s;
        white-space: nowrap;
        max-width: 260px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .lsb-sp-biz-contact-item:hover { color: #00A88C !important; }
    .lsb-sp-biz-contact-icon { font-size: 0.9rem; flex-shrink: 0; }
    .lsb-sp-biz-btn {
        flex-shrink: 0;
        display: inline-block;
        background: #0D1B2A !important;
        color: #ffffff !important;
        font-family: 'Syne', sans-serif !important;
        font-weight: 700 !important;
        font-size: 0.82rem !important;
        padding: 11px 22px !important;
        border-radius: 8px !important;
        text-decoration: none !important;
        transition: background .2s, color .2s !important;
        white-space: nowrap;
    }
    .lsb-sp-biz-btn:hover { background: #00C9A7 !important; color: #0D1B2A !important; }

.lsb-sp-biz-photo-strip {
    display: flex;
    gap: 8px;
    margin: 16px 0 0;
    overflow: hidden;
    border-radius: 12px;
}
.lsb-sp-biz-photo-strip-item {
    flex: 1;
    min-width: 0;
    border-radius: 10px;
    overflow: hidden;
    background: #EAF0F5;
    aspect-ratio: 1 / 1;
}
.lsb-sp-biz-photo-strip-item img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

    /* ============================
       RELATED SERVICES (child mode)
    ============================ */
    .lsb-sp-related-section { background: #F5F7FA; padding: 80px 40px; }
    .lsb-sp-related-inner   { max-width: 1200px; margin: 0 auto; }
    .lsb-sp-related-header  { max-width: 680px; margin-bottom: 40px; }
    .lsb-sp-related-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    .lsb-sp-related-card {
        background: #ffffff;
        border: 1px solid var(--lsb-sp-border);
        border-radius: 14px;
        padding: 22px 18px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        text-decoration: none;
        transition: all .25s;
        position: relative;
        overflow: hidden;
    }
    .lsb-sp-related-card::before {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        background: #00C9A7;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s;
    }
    .lsb-sp-related-card:hover { border-color: rgba(0,201,167,0.35); transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
    .lsb-sp-related-card:hover::before { transform: scaleX(1); }
    .lsb-sp-related-icon { font-size: 1.4rem; }
    .lsb-sp-related-name { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; color: var(--lsb-sp-heading) !important; letter-spacing: -0.01em; line-height: 1.3; }
    .lsb-sp-related-cta  { font-size: 0.78rem; font-weight: 600; color: #00A88C !important; font-family: 'DM Sans', sans-serif; margin-top: auto; transition: gap .2s; display: inline-flex; align-items: center; gap: 5px; }
    .lsb-sp-related-card:hover .lsb-sp-related-cta { gap: 9px; }

    /* ============================
       FAQ
    ============================ */
    .lsb-sp-faq-section { background: #F5F7FA; padding: 80px 40px; }
    .lsb-sp-faq-inner   { max-width: 780px; margin: 0 auto; }
    .lsb-sp-faq-header  { margin-bottom: 48px; }
    .lsb-sp-faq-list    { display: flex; flex-direction: column; gap: 12px; }
    .lsb-sp-faq-item {
        background: #ffffff;
        border: 1px solid var(--lsb-sp-border);
        border-radius: 14px;
        overflow: hidden;
        transition: border-color .25s;
    }
    .lsb-sp-faq-item.is-open { border-color: rgba(0,201,167,0.35); }
    .lsb-sp-faq-trigger {
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
    .lsb-sp-faq-trigger:hover            { background: #F5F7FA; }
    .lsb-sp-faq-item.is-open .lsb-sp-faq-trigger { background: #F5F7FA; }
    .lsb-sp-faq-q {
        font-family: 'Syne', sans-serif !important;
        font-size: 0.97rem !important;
        font-weight: 700 !important;
        color: var(--lsb-sp-heading) !important;
        letter-spacing: -0.01em !important;
        line-height: 1.4 !important;
        margin: 0 !important;
    }
    .lsb-sp-faq-icon {
        width: 30px; height: 30px;
        background: #F5F7FA;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        color: #00C9A7;
        font-size: 1.1rem;
        transition: background .2s, transform .25s;
        border: 1px solid var(--lsb-sp-border);
    }
    .lsb-sp-faq-item.is-open .lsb-sp-faq-icon { transform: rotate(45deg); background: rgba(0,201,167,0.1); border-color: rgba(0,201,167,0.3); }
    .lsb-sp-faq-body { display: none; padding: 0 24px 24px; }
    .lsb-sp-faq-item.is-open .lsb-sp-faq-body { display: block; }
    .lsb-sp-faq-answer {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.92rem !important;
        color: var(--lsb-sp-body) !important;
        line-height: 1.8 !important;
        padding-top: 4px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--lsb-sp-border);
        margin-bottom: 16px;
    }
    .lsb-sp-faq-answer p       { font-size: 0.92rem !important; color: var(--lsb-sp-body) !important; line-height: 1.8 !important; margin-bottom: 10px !important; }
    .lsb-sp-faq-answer p:last-child { margin-bottom: 0 !important; }
    .lsb-sp-faq-answer strong  { color: var(--lsb-sp-heading) !important; font-weight: 600 !important; }
    .lsb-sp-faq-answer ul      { padding-left: 18px; margin-top: 6px; }
    .lsb-sp-faq-answer ul li   { font-size: 0.92rem !important; color: var(--lsb-sp-body) !important; line-height: 1.8 !important; margin-bottom: 4px !important; }
    .lsb-sp-faq-cta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .lsb-sp-faq-learn-more {
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
    .lsb-sp-faq-learn-more::after { content: '→'; color: #00C9A7; font-size: 0.85rem; transition: transform .2s; }
    .lsb-sp-faq-learn-more:hover { color: #0D1B2A !important; gap: 10px; }
    .lsb-sp-faq-learn-more:hover::after { transform: translateX(3px); }
    .lsb-sp-faq-disclaimer {
        font-size: 0.75rem !important;
        color: var(--lsb-sp-muted) !important;
        line-height: 1.6 !important;
        font-family: 'DM Sans', sans-serif !important;
        font-style: italic !important;
        font-weight: 400 !important;
        margin: 24px 0 0 !important;
        text-align: center;
    }
    @media (max-width: 768px) {
        .lsb-sp-faq-section  { padding: 56px 20px; }
        .lsb-sp-faq-cta-row  { flex-direction: column; align-items: flex-start; gap: 10px; }
        .lsb-sp-faq-disclaimer { text-align: left; }
    }

    /* ============================
       BLOG SECTION
    ============================ */
    .lsb-sp-blog-section { background: #ffffff; padding: 80px 40px; }
    .lsb-sp-blog-inner   { max-width: 1200px; margin: 0 auto; }
    .lsb-sp-blog-header  {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 40px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .lsb-sp-blog-view-all {
        color: #00A88C;
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
        transition: gap .2s;
    }
    .lsb-sp-blog-view-all:hover { gap: 9px; }
    .lsb-sp-blog-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }
    .lsb-sp-blog-card {
        background: #F5F7FA;
        border: 1px solid #E4EAF2;
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        text-decoration: none;
        transition: all .25s;
        position: relative;
    }
    .lsb-sp-blog-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: #00C9A7;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s;
        z-index: 1;
    }
    .lsb-sp-blog-card:hover { border-color: rgba(0,201,167,0.35); box-shadow: 0 12px 32px rgba(0,0,0,0.07); transform: translateY(-4px); }
    .lsb-sp-blog-card:hover::before { transform: scaleX(1); }
    .lsb-sp-blog-thumb {
        width: 100%;
        aspect-ratio: 16/9;
        object-fit: cover;
        display: block;
        background: #E4EAF2;
    }
    .lsb-sp-blog-thumb-placeholder {
        width: 100%;
        aspect-ratio: 16/9;
        background: linear-gradient(135deg, #0D1B2A 0%, #1B2F45 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }
    .lsb-sp-blog-body {
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
    }
    .lsb-sp-blog-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .lsb-sp-blog-industry {
        display: inline-block;
        background: rgba(0,201,167,0.1);
        border: 1px solid rgba(0,201,167,0.2);
        color: #00A88C;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 3px 10px;
        border-radius: 100px;
    }
    .lsb-sp-blog-date {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.75rem;
        color: #8A9BB0;
    }
    .lsb-sp-blog-title {
        font-family: 'Syne', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #0D1B2A;
        line-height: 1.4;
        letter-spacing: -0.01em;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .lsb-sp-blog-excerpt {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        color: #3D4F63;
        line-height: 1.7;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }
    .lsb-sp-blog-read {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.82rem;
        font-weight: 600;
        color: #00A88C;
        margin-top: 8px;
        transition: gap .2s;
    }
    .lsb-sp-blog-card:hover .lsb-sp-blog-read { gap: 9px; }
    @media (max-width: 900px) {
        .lsb-sp-blog-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .lsb-sp-blog-section { padding: 56px 20px; }
        .lsb-sp-blog-grid    { grid-template-columns: 1fr; }
    }

    /* ============================
       FINAL CTA
    ============================ */
    .lsb-sp-cta-section {
        background: #0D1B2A;
        padding: 100px 40px;
        position: relative;
        overflow: hidden;
    }
    .lsb-sp-cta-grid-bg {
        position: absolute; inset: 0; pointer-events: none;
        background-image:
            linear-gradient(rgba(0,201,167,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,201,167,0.04) 1px, transparent 1px);
        background-size: 60px 60px;
    }
    .lsb-sp-cta-glow-1 { position: absolute; pointer-events: none; width: 500px; height: 500px; background: radial-gradient(circle, rgba(0,201,167,0.1) 0%, transparent 70%); top: -120px; right: -80px; }
    .lsb-sp-cta-glow-2 { position: absolute; pointer-events: none; width: 400px; height: 400px; background: radial-gradient(circle, rgba(244,197,66,0.06) 0%, transparent 70%); bottom: -80px; left: -60px; }
    .lsb-sp-cta-inner  { max-width: 680px; margin: 0 auto; text-align: center; position: relative; z-index: 1; }
    .lsb-sp-cta-heading { font-family: 'Syne', sans-serif !important; font-size: clamp(2rem, 4vw, 3rem) !important; font-weight: 800 !important; color: #F5F7FA !important; letter-spacing: -0.02em !important; line-height: 1.1 !important; margin-bottom: 20px !important; }
    .lsb-sp-cta-heading em { font-style: normal; color: #00C9A7; }
    .lsb-sp-cta-text   { font-size: 1rem !important; color: rgba(255,255,255,0.55) !important; line-height: 1.8 !important; font-family: 'DM Sans', sans-serif !important; font-weight: 300 !important; margin-bottom: 40px !important; }
    .lsb-sp-cta-btn {
        display: inline-flex; align-items: center; gap: 8px;
        background: #00C9A7 !important; color: #0D1B2A !important;
        font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
        font-size: 0.95rem !important; padding: 16px 36px !important;
        border-radius: 8px !important; text-decoration: none !important;
        letter-spacing: 0.02em !important; transition: background .2s, transform .15s !important;
    }
    .lsb-sp-cta-btn::after { content: '→'; font-size: 1rem; transition: transform .2s; }
    .lsb-sp-cta-btn:hover  { background: #00A88C !important; transform: translateY(-2px); }
    .lsb-sp-cta-btn:hover::after { transform: translateX(4px); }

    /* ============================
       RESPONSIVE
    ============================ */
    @media (max-width: 1024px) {
        .lsb-sp-hero-cols      { grid-template-columns: 1fr; gap: 40px; }
        .lsb-sp-children-grid  { grid-template-columns: repeat(2, 1fr); }
        .lsb-sp-cost-grid      { grid-template-columns: repeat(2, 1fr); }
        .lsb-sp-process-steps  { grid-template-columns: 1fr; gap: 2px; }
        .lsb-sp-cities-grid    { grid-template-columns: repeat(3, 1fr); }
        .lsb-sp-related-grid   { grid-template-columns: repeat(2, 1fr); }
        .lsb-sp-when-inner     { grid-template-columns: 1fr; gap: 40px; }
    }
    @media (max-width: 768px) {
        .lsb-sp-breadcrumb-bar { padding: 0 20px; }
        .lsb-sp-hero           { padding: 40px 20px 52px; }
        .lsb-sp-overview-section, .lsb-sp-when-section, .lsb-sp-cost-section,
        .lsb-sp-process-section, .lsb-sp-cities-section, .lsb-sp-biz-section,
        .lsb-sp-related-section, .lsb-sp-faq-section, .lsb-sp-children-section, .lsb-sp-blog-section { padding: 56px 20px; }
        .lsb-sp-cities-grid    { grid-template-columns: repeat(2, 1fr); }
        .lsb-sp-related-grid   { grid-template-columns: repeat(2, 1fr); }
        .lsb-sp-biz-card       { flex-direction: column; align-items: flex-start; gap: 16px; padding: 24px; }
        .lsb-sp-biz-btn        { width: 100% !important; text-align: center !important; }
        .lsb-sp-cta-section    { padding: 72px 20px; }
    }
    @media (max-width: 480px) {
        .lsb-sp-children-grid  { grid-template-columns: 1fr; }
        .lsb-sp-cost-grid      { grid-template-columns: 1fr; }
        .lsb-sp-cities-grid    { grid-template-columns: repeat(2, 1fr); }
        .lsb-sp-related-grid   { grid-template-columns: 1fr 1fr; }
    }

    </style>

    <?php
    // ── JSON-LD Schema ────────────────────────────────────────────────────
    $schema_service = [
        '@context' => 'https://schema.org',
        '@type'    => 'Service',
        'name'     => $service_title,
        'url'      => get_permalink( $post_id ),
        'provider' => [
            '@type' => 'Organization',
            'name'  => 'LocalServiceBiz',
            'url'   => $home_url,
        ],
        'areaServed' => [
            '@type' => 'Place',
            'name'  => 'Greater Los Angeles, California',
        ],
    ];
    if ( $hero_intro ) $schema_service['description'] = wp_strip_all_tags( $hero_intro );
    ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode( $schema_service, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
    </script>

    <!-- ============================================================
         BREADCRUMB
    ============================================================ -->
    <nav class="lsb-sp-breadcrumb-bar" aria-label="Breadcrumb">
        <ol class="lsb-sp-breadcrumb">
            <li><a href="<?php echo esc_url( $home_url ); ?>">Home</a></li>
            <li class="lsb-sp-breadcrumb-sep" aria-hidden="true">›</li>
            <li><a href="<?php echo esc_url( $services_url ); ?>">Services</a></li>
            <?php if ( ! empty( $industry_term_ids ) ) : ?>
            <li class="lsb-sp-breadcrumb-sep" aria-hidden="true">›</li>
            <li><a href="<?php echo esc_url( $industry_url ); ?>"><?php echo esc_html( $industry_name ); ?></a></li>
            <?php endif; ?>
            <?php if ( $is_child && $parent_post_url ) : ?>
            <li class="lsb-sp-breadcrumb-sep" aria-hidden="true">›</li>
            <li><a href="<?php echo esc_url( $parent_post_url ); ?>"><?php echo esc_html( $parent_post_name ); ?></a></li>
            <?php endif; ?>
            <li class="lsb-sp-breadcrumb-sep" aria-hidden="true">›</li>
            <li class="lsb-sp-breadcrumb-current" aria-current="page"><?php echo esc_html( $service_title ); ?></li>
        </ol>
    </nav>

    <!-- ============================================================
         SECTION 1 — HERO
    ============================================================ -->
    <section class="lsb-sp-hero lsb-sp-dark-bg" aria-labelledby="lsb-sp-h1">

        <div class="lsb-sp-hero-grid" aria-hidden="true"></div>
        <div class="lsb-sp-hero-glow-1" aria-hidden="true"></div>
        <div class="lsb-sp-hero-glow-2" aria-hidden="true"></div>

        <div class="lsb-sp-hero-inner">
            <div class="lsb-sp-hero-cols<?php echo $hero_image ? '' : ' no-image'; ?>">

                <div class="lsb-sp-hero-left">

                    <?php if ( $is_child && $parent_post_url ) : ?>
                    <a href="<?php echo esc_url( $parent_post_url ); ?>" class="lsb-sp-parent-pill">
                        <span class="lsb-sp-parent-pill-arrow">←</span>
                        <span><?php echo esc_html( $parent_post_name ); ?></span>
                    </a>
                    <?php endif; ?>

                    <div class="lsb-sp-mode-badge">
                        <span class="lsb-sp-mode-badge-icon"><?php echo $svc_icon; ?></span>
                        <span>
                            <?php if ( $is_parent ) : ?>
                                <?php echo esc_html( $industry_name ?: 'Service Category' ); ?> · <?php echo count( $child_services ); ?> Services
                            <?php elseif ( $is_child ) : ?>
                                <?php echo esc_html( $parent_post_name ?: $industry_name ?: 'Service' ); ?>
                            <?php else : ?>
                                <?php echo esc_html( $industry_name ?: 'Service' ); ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <span class="lsb-sp-hero-icon" aria-hidden="true"><?php echo $svc_icon; ?></span>

                   <h1 class="lsb-sp-h1" id="lsb-sp-h1">
                        <span>Services Near You:</span>
                        <em><?php echo esc_html( $service_title ); ?></em>
                    </h1>

                    <?php if ( $subtitle ) : ?>
                    <p class="lsb-sp-hero-subtitle"><?php echo wp_kses_post( $subtitle ); ?></p>
                    <?php endif; ?>

                    <?php if ( $hero_intro ) : ?>
                    <p class="lsb-sp-hero-intro"><?php echo wp_kses_post( $hero_intro ); ?></p>
                    <?php endif; ?>

                    <div class="lsb-sp-hero-meta">
                        <?php if ( ! empty( $city_posts ) ) : ?>
                        <span class="lsb-sp-hero-pill">
                            <span class="lsb-sp-hero-pill-dot"></span>
                            <?php echo count( $city_posts ); ?> Cities
                        </span>
                        <?php endif; ?>
                        <?php if ( ! empty( $biz_posts ) ) : ?>
                        <span class="lsb-sp-hero-pill">
                            <span class="lsb-sp-hero-pill-dot"></span>
                            <?php echo count( $biz_posts ); ?>+ Businesses
                        </span>
                        <?php endif; ?>
                        <?php if ( $industry_name ) : ?>
                        <span class="lsb-sp-hero-pill">
                            <span class="lsb-sp-hero-pill-dot"></span>
                            <?php echo esc_html( $industry_name ); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                </div>

                <?php if ( $hero_image ) : ?>
                <div class="lsb-sp-hero-image-wrap">
                    <img src="<?php echo esc_url( $hero_image ); ?>"
                         alt="<?php echo esc_attr( $service_title ); ?>"
                         loading="eager">
                </div>
                <?php endif; ?>

            </div>
        </div>
    </section>

    <!-- ============================================================
         SECTION 2 — OVERVIEW
    ============================================================ -->
    <?php if ( $overview ) : ?>
    <section class="lsb-sp-overview-section lsb-sp-light-bg" id="lsb-sp-overview">
        <div class="lsb-sp-overview-inner">
            <span class="lsb-sp-label">
                <?php echo $is_parent ? 'Category Overview' : 'Service Overview'; ?>
            </span>
            <h2 class="lsb-sp-h2">What Is <?php echo esc_html( $service_title ); ?>?</h2>
            <div class="lsb-sp-overview-content">
                <?php echo wp_kses_post( $overview ); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>





    <?php if ( $is_parent ) : ?>
    <!-- ============================================================
         PARENT MODE — CHILD SERVICES GRID
    ============================================================ -->
    <?php if ( ! empty( $child_services ) ) : ?>
    <section class="lsb-sp-children-section lsb-sp-light-bg" id="lsb-sp-child-services">
        <div class="lsb-sp-children-inner">

            <div class="lsb-sp-children-header">
                <span class="lsb-sp-label">Services Included</span>
                <h2 class="lsb-sp-h2"><?php echo esc_html( $service_title ); ?> Services</h2>
            </div>

            <div class="lsb-sp-children-grid">
                <?php foreach ( $child_services as $child ) :
                    $child_term = $child['term'];
                    $child_post = $child['post'];
                    $child_url  = $child_post ? get_permalink( $child_post->ID ) : '#';
                    $child_icon = function_exists( 'lsb_get_service_icon' ) ? lsb_get_service_icon( $child_term->slug ) : '🔧';
                    $child_desc = '';
                    if ( $child_post ) {
                        $child_desc = get_the_excerpt( $child_post->ID );
                        if ( ! $child_desc ) $child_desc = wp_trim_words( get_post_field( 'post_content', $child_post->ID ), 18, '...' );
                    }
                ?>
                <a href="<?php echo esc_url( $child_url ); ?>" class="lsb-sp-child-card">
                    <div class="lsb-sp-child-icon"><?php echo $child_icon; ?></div>
                    <div class="lsb-sp-child-name"><?php echo esc_html( $child_term->name ); ?></div>
                    <?php if ( $child_desc ) : ?>
                    <p class="lsb-sp-child-desc"><?php echo esc_html( $child_desc ); ?></p>
                    <?php endif; ?>
                    <span class="lsb-sp-child-cta">Learn More →</span>
                </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         PARENT MODE — CITIES
    ============================================================ -->
    <?php if ( ! empty( $city_posts ) ) : ?>
    <section class="lsb-sp-cities-section lsb-sp-light-bg" id="lsb-sp-cities">
        <div class="lsb-sp-cities-inner">

            <div class="lsb-sp-cities-header">
                <span class="lsb-sp-label">Service Area</span>
              <h2 class="lsb-sp-h2">Cities Served by <?php echo esc_html( $industry_name ); ?> Professionals</h2>
            </div>

            <div class="lsb-sp-cities-grid">
                <?php foreach ( $city_posts as $city ) : ?>
               <a href="<?php echo esc_url( home_url( '/cities/' . $city->post_name . '/' . $industry_term_slug . '/' ) ); ?>" class="lsb-sp-city-card">
                    <span class="lsb-sp-city-pin">📍</span>
                    <span class="lsb-sp-city-name"><?php echo esc_html( $city->post_title ); ?></span>
                    <span class="lsb-sp-city-cta">View City →</span>
                </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
    <?php endif; ?>

   <!-- ============================================================
     PARENT MODE — BUSINESSES
============================================================ -->
<?php if ( ! empty( $biz_posts ) ) : ?>
<section class="lsb-sp-biz-section lsb-sp-light-bg" id="lsb-sp-businesses">
    <div class="lsb-sp-biz-inner">

        <div class="lsb-sp-biz-header">
            <span class="lsb-sp-label">Find a Professional</span>
            <h2 class="lsb-sp-h2">Businesses Offering <?php echo esc_html( $service_title ); ?></h2>
        </div>

        <?php
            if ( ! function_exists( 'lsb_sp_stars' ) ) {
                function lsb_sp_stars( $rating ) {
                    $html = '';
                    if ( ! $rating ) return $html;
                    $rf = floatval( $rating );
                    $fs = floor( $rf );
                    $hs = ( $rf - $fs ) >= 0.5;
                    for ( $s = 1; $s <= 5; $s++ ) {
                        if      ( $s <= $fs )             { $html .= '<span class="lsb-sp-star-full">★</span>'; }
                        elseif  ( $s === $fs + 1 && $hs ) { $html .= '<span class="lsb-sp-star-half">★</span>'; }
                        else                              { $html .= '<span class="lsb-sp-star-empty">☆</span>'; }
                    }
                    return $html;
                }
            }

           // Pre-fetch strip images for ALL featured businesses before the loop
            $pb_strip_images_by_id = array();
            if ( function_exists( 'mvc_de_get_square_images' ) && ! empty( $biz_featured_cards ) ) {
                global $post;
                $pb_orig_post = $post;

                foreach ( $biz_featured_cards as $pb_prefetch ) {
                    $pb_prefetch_id = $pb_prefetch->ID;

                    $post = get_post( $pb_prefetch_id );
                    setup_postdata( $post );

                    $pb_prefetch_pool = mvc_de_get_square_images( 8 );

                    $post = $pb_orig_post;
                    setup_postdata( $post );

                    $pb_prefetch_sq = function_exists( 'mvc_de_get_square_image_for_business' )
                        ? mvc_de_get_square_image_for_business( $pb_prefetch_id )
                        : null;
                    $pb_prefetch_exclude = $pb_prefetch_sq ? (int) $pb_prefetch_sq->ID : 0;

                    $pb_prefetch_strip = array();
                    foreach ( $pb_prefetch_pool as $pb_prefetch_candidate ) {
                        if ( count( $pb_prefetch_strip ) >= 4 ) break;
                        if ( $pb_prefetch_exclude && (int) $pb_prefetch_candidate->ID === $pb_prefetch_exclude ) continue;
                        $pb_prefetch_strip[] = $pb_prefetch_candidate;
                    }

                    $pb_strip_images_by_id[ $pb_prefetch_id ] = $pb_prefetch_strip;
                }
            }
        ?>

<?php if ( ! empty( $biz_featured_cards ) ) : ?>
        <div class="lsb-sp-biz-cards">
           <?php foreach ( $biz_featured_cards as $pb_featured ) :
               $pb_id = $pb_featured->ID;

                $pb_title   = $pb_featured->post_title;
                $pb_logo_f  = function_exists( 'get_field' ) ? get_field( 'business_logo',              $pb_id ) : '';
                $pb_desc    = function_exists( 'get_field' ) ? get_field( 'business_short_description', $pb_id ) : '';
                $pb_rating  = function_exists( 'get_field' ) ? get_field( 'business_rating',            $pb_id ) : '';
                $pb_phone   = function_exists( 'get_field' ) ? get_field( 'business_phone',             $pb_id ) : '';
                $pb_website = function_exists( 'get_field' ) ? get_field( 'business_website',           $pb_id ) : '';

                if ( ! $pb_desc ) {
                    $pb_desc = get_the_excerpt( $pb_id );
                }

                $pb_phone_clean   = preg_replace( '/[^0-9+]/', '', $pb_phone );
                $pb_website_label = $pb_website ? preg_replace( '#^https?://(www\.)?#', '', rtrim( $pb_website, '/' ) ) : '';
                $pb_logo_url      = '';

                if ( $pb_logo_f ) {
                    $pb_logo_url = is_array( $pb_logo_f ) ? ( $pb_logo_f['url'] ?? '' ) : wp_get_attachment_image_url( $pb_logo_f, 'thumbnail' );
                }

                if ( ! $pb_logo_url ) {
                    $pb_logo_url = get_the_post_thumbnail_url( $pb_id, 'thumbnail' );
                }


$pb_square_image = function_exists( 'mvc_de_get_square_image_for_business' )
    ? mvc_de_get_square_image_for_business( $pb_id )
    : null;

$pb_square_html = '';

if ( $pb_square_image && ! empty( $pb_square_image->ID ) ) {

    $pb_square_alt = function_exists( 'mvc_de_get_dynamic_image_alt' )
        ? mvc_de_get_dynamic_image_alt( $pb_square_image->ID, array(
            'businesses' => wp_get_post_terms( $pb_id, 'business_cat', array( 'fields' => 'slugs' ) ),
            'cities'     => wp_get_post_terms( $pb_id, MVC_Directory_Engine::TAX_CITY, array( 'fields' => 'slugs' ) ),
            'industries' => wp_get_post_terms( $pb_id, MVC_Directory_Engine::TAX_INDUSTRY, array( 'fields' => 'slugs' ) ),
            'services'   => wp_get_post_terms( $pb_id, MVC_Directory_Engine::TAX_SERVICE, array( 'fields' => 'slugs' ) ),
        ) )
        : $pb_title;

    $pb_square_html = wp_get_attachment_image(
        $pb_square_image->ID,
        'medium_large',
        false,
        array(
            'alt'     => esc_attr( $pb_square_alt ?: $pb_title ),
            'loading' => 'lazy',
        )
    );
}

// Strip images pre-fetched above, keyed by business ID
$pb_strip_images = $pb_strip_images_by_id[ $pb_id ] ?? array();




$pb_initials = '';
                foreach ( array_slice( explode( ' ', $pb_title ), 0, 2 ) as $pw ) {
                    $pb_initials .= strtoupper( substr( $pw, 0, 1 ) );
                }
            ?>
         <article class="lsb-sp-biz-card-featured">

                <div class="lsb-sp-fc-top">
                    <div class="lsb-sp-fc-logo">
                        <?php if ( $pb_logo_url ) : ?>
                        <img src="<?php echo esc_url( $pb_logo_url ); ?>" alt="<?php echo esc_attr( $pb_title ); ?> logo" loading="lazy" width="60" height="60">
                        <?php else : ?>
                        <span class="lsb-sp-fc-initials" aria-hidden="true"><?php echo esc_html( $pb_initials ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="lsb-sp-fc-identity">
                        <div class="lsb-sp-fc-badge"><span class="lsb-sp-fc-badge-dot"></span> Featured</div>
                        <h3 class="lsb-sp-fc-name"><?php echo esc_html( $pb_title ); ?></h3>
                        <div class="lsb-sp-fc-industry"><?php echo esc_html( $industry_name ); ?></div>
                    </div>
                    <div class="lsb-sp-fc-right">
                        <?php if ( $pb_rating ) : ?>
                        <div class="lsb-sp-fc-rating">
                            <span class="lsb-sp-fc-stars"><?php echo lsb_sp_stars( $pb_rating ); ?></span>
                            <span class="lsb-sp-fc-rating-num"><?php echo esc_html( number_format( (float) $pb_rating, 1 ) ); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="lsb-sp-fc-plan">Premium</div>
                    </div>
                </div>

                <div class="lsb-sp-fc-divider"></div>

                <?php if ( $pb_desc ) : ?>
                <p class="lsb-sp-fc-desc"><?php echo esc_html( wp_trim_words( $pb_desc, 28, '…' ) ); ?></p>
                <?php endif; ?>

                <div class="lsb-sp-fc-meta-row">
                    <?php if ( $pb_phone ) : ?>
                    <a href="tel:<?php echo esc_attr( $pb_phone_clean ); ?>" class="lsb-sp-fc-meta-item">
                        <span class="lsb-sp-fc-meta-icon">📞</span><?php echo esc_html( $pb_phone ); ?>
                    </a>
                    <?php endif; ?>
                    <?php if ( $pb_website ) : ?>
                    <a href="<?php echo esc_url( $pb_website ); ?>" class="lsb-sp-fc-meta-item" target="_blank" rel="noopener noreferrer">
                        <span class="lsb-sp-fc-meta-icon">🌐</span><?php echo esc_html( $pb_website_label ); ?>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="lsb-sp-fc-footer">
                    <div class="lsb-sp-fc-location">
                        <span class="lsb-sp-fc-loc-dot"></span>
                        <?php
                        $pb_city_terms = wp_get_post_terms( $pb_id, 'city_cat', array( 'fields' => 'names' ) );
                        echo ! empty( $pb_city_terms ) && ! is_wp_error( $pb_city_terms )
                            ? esc_html( $pb_city_terms[0] )
                            : 'Greater Los Angeles';
                        ?>
                    </div>
                    <div class="lsb-sp-fc-actions">
                        <?php if ( $pb_phone ) : ?>
                        <a href="tel:<?php echo esc_attr( $pb_phone_clean ); ?>" class="lsb-sp-fc-btn-secondary">📞 Call</a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( get_permalink( $pb_id ) ); ?>" class="lsb-sp-fc-btn-primary">View Profile →</a>
                    </div>
                </div>

               <?php if ( ! empty( $pb_strip_images ) ) : ?>
                <div class="lsb-sp-biz-photo-strip">
                    <?php foreach ( $pb_strip_images as $pb_strip_img ) :
                        $pb_strip_alt = function_exists( 'mvc_de_get_dynamic_image_alt' )
                            ? mvc_de_get_dynamic_image_alt( $pb_strip_img->ID, array(
                                'businesses' => wp_get_post_terms( $pb_id, 'business_cat', array( 'fields' => 'slugs' ) ),
                            ) )
                            : $pb_title;
                    ?>
                    <div class="lsb-sp-biz-photo-strip-item">
                        <?php echo wp_get_attachment_image( $pb_strip_img->ID, 'medium', false, array( 'alt' => esc_attr( $pb_strip_alt ?: $pb_title ), 'loading' => 'lazy' ) ); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $biz_secondary_cards ) ) : ?>
        <div class="lsb-sp-biz-secondary-grid">
            <?php foreach ( $biz_secondary_cards as $psb ) :
                $psb_id          = $psb->ID;
                $psb_title       = $psb->post_title;
                $psb_rating      = function_exists( 'get_field' ) ? get_field( 'business_rating', $psb_id ) : '';
                $psb_phone       = function_exists( 'get_field' ) ? get_field( 'business_phone',  $psb_id ) : '';
                $psb_phone_clean = preg_replace( '/[^0-9+]/', '', $psb_phone );
            ?>
            <a href="<?php echo esc_url( get_permalink( $psb_id ) ); ?>" class="lsb-sp-biz-card-secondary">
                <h3 class="lsb-sp-biz-sec-name"><?php echo esc_html( $psb_title ); ?></h3>
                <div class="lsb-sp-biz-sec-row">
                    <?php if ( $psb_phone ) : ?>
                    <span class="lsb-sp-biz-sec-phone" onclick="event.preventDefault(); window.location='tel:<?php echo esc_attr( $psb_phone_clean ); ?>'">
                        📞 <?php echo esc_html( $psb_phone ); ?>
                    </span>
                    <?php endif; ?>

                    <?php if ( $psb_rating ) : ?>
                    <span class="lsb-sp-biz-sec-rating">★ <?php echo esc_html( number_format( (float) $psb_rating, 1 ) ); ?></span>
                    <?php endif; ?>
                </div>
                <span class="lsb-sp-biz-sec-view">View Profile →</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>

    <?php else : // CHILD MODE ?>
    <!-- ============================================================
         CHILD MODE — WHEN YOU NEED + SIGNS
    ============================================================ -->
    <?php if ( $when_need || ! empty( $signs ) ) : ?>
    <section class="lsb-sp-when-section lsb-sp-light-bg" id="lsb-sp-when-need">
        <div class="lsb-sp-when-inner">

            <?php if ( $when_need ) : ?>
            <div>
                <span class="lsb-sp-label">Know the Trigger</span>
                <h2 class="lsb-sp-h2">When Do You Need <?php echo esc_html( $service_title ); ?>?</h2>
                <div class="lsb-sp-when-content">
                    <?php echo wp_kses_post( $when_need ); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $signs ) ) : ?>
            <div class="lsb-sp-signs-panel">
                <p class="lsb-sp-signs-title">Signs You Need This Service</p>
                <?php foreach ( $signs as $i => $sign ) :
                    $sign_title = $sign['sign_title'] ?? '';
                    $sign_desc  = $sign['sign_description'] ?? '';
                    if ( ! $sign_title ) continue;
                ?>
                <div class="lsb-sp-sign-item">
                    <div class="lsb-sp-sign-dot" aria-hidden="true"><?php echo $i + 1; ?></div>
                    <div class="lsb-sp-sign-text">
                        <p class="lsb-sp-sign-title"><?php echo esc_html( $sign_title ); ?></p>
                        <?php if ( $sign_desc ) : ?>
                        <p class="lsb-sp-sign-desc"><?php echo esc_html( $sign_desc ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         CHILD MODE — COST FACTORS
    ============================================================ -->
    <?php if ( ! empty( $cost_factors ) ) : ?>
    <section class="lsb-sp-cost-section lsb-sp-light-bg" id="lsb-sp-cost-factors">
        <div class="lsb-sp-cost-inner">

            <div class="lsb-sp-cost-header">
                <span class="lsb-sp-label">Pricing Transparency</span>
                <h2 class="lsb-sp-h2">What Affects the Cost of <?php echo esc_html( $service_title ); ?>?</h2>
            </div>

            <div class="lsb-sp-cost-grid">
                <?php foreach ( $cost_factors as $idx => $factor ) :
                    $f_title = $factor['factor_title']       ?? '';
                    $f_desc  = $factor['factor_description'] ?? '';
                    if ( ! $f_title ) continue;
                ?>
                <div class="lsb-sp-cost-card">
                    <div class="lsb-sp-cost-num"><?php echo str_pad( $idx + 1, 2, '0', STR_PAD_LEFT ); ?></div>
                    <h3 class="lsb-sp-cost-title"><?php echo esc_html( $f_title ); ?></h3>
                    <?php if ( $f_desc ) : ?>
                    <p class="lsb-sp-cost-desc"><?php echo wp_kses_post( $f_desc ); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         CHILD MODE — SERVICE PROCESS
    ============================================================ -->
    <?php if ( ! empty( $process_steps ) ) : ?>
    <section class="lsb-sp-process-section lsb-sp-dark-bg" id="lsb-sp-process">
        <div class="lsb-sp-process-grid-bg" aria-hidden="true"></div>
        <div class="lsb-sp-process-inner">

            <div class="lsb-sp-process-header">
                <span class="lsb-sp-label">How It Works</span>
                <h2 class="lsb-sp-h2">The <?php echo esc_html( $service_title ); ?> Process</h2>
            </div>

            <div class="lsb-sp-process-steps">
                <?php foreach ( $process_steps as $pi => $step ) :
                    $st_title = $step['step_title']       ?? '';
                    $st_desc  = $step['step_description'] ?? '';
                    if ( ! $st_title ) continue;
                ?>
                <div class="lsb-sp-process-step">
                    <div class="lsb-sp-step-num"><?php echo str_pad( $pi + 1, 2, '0', STR_PAD_LEFT ); ?></div>
                    <div class="lsb-sp-process-body">
                        <h3 class="lsb-sp-step-title"><?php echo esc_html( $st_title ); ?></h3>
                        <?php if ( $st_desc ) : ?>
                        <p class="lsb-sp-step-desc"><?php echo wp_kses_post( $st_desc ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <p class="lsb-sp-process-disclaimer">
                This site is a directory of local service providers. Pricing, processes, timelines, and recommendations vary by company. Always confirm details directly with your chosen provider before proceeding.
            </p>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         CHILD MODE — CITIES
    ============================================================ -->
    <?php if ( ! empty( $city_posts ) ) : ?>
    <section class="lsb-sp-cities-section lsb-sp-light-bg" id="lsb-sp-cities">
        <div class="lsb-sp-cities-inner">

            <div class="lsb-sp-cities-header">
                <span class="lsb-sp-label">Service Area</span>
                <h2 class="lsb-sp-h2">Cities Offering <?php echo esc_html( $service_title ); ?></h2>
            </div>

            <div class="lsb-sp-cities-grid">
                <?php foreach ( $city_posts as $city ) : ?>
               <a href="<?php echo esc_url( home_url( '/cities/' . $city->post_name . '/' . $industry_term_slug . '/' ) ); ?>" class="lsb-sp-city-card">
                    <span class="lsb-sp-city-pin">📍</span>
                    <span class="lsb-sp-city-name"><?php echo esc_html( $city->post_title ); ?></span>
                    <span class="lsb-sp-city-cta">View City →</span>
                </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
    <?php endif; ?>

  <!-- ============================================================
     CHILD MODE — BUSINESSES
============================================================ -->
<?php if ( ! empty( $biz_posts ) ) : ?>
<section class="lsb-sp-biz-section lsb-sp-light-bg" id="lsb-sp-businesses">
    <div class="lsb-sp-biz-inner">

        <div class="lsb-sp-biz-header">
            <span class="lsb-sp-label">Find a Professional</span>
            <h2 class="lsb-sp-h2">Businesses Offering <?php echo esc_html( $service_title ); ?></h2>
        </div>

       <?php
            if ( ! function_exists( 'lsb_sp_stars' ) ) {
                function lsb_sp_stars( $rating ) {
                    $html = '';
                    if ( ! $rating ) return $html;
                    $rf = floatval( $rating );
                    $fs = floor( $rf );
                    $hs = ( $rf - $fs ) >= 0.5;
                    for ( $s = 1; $s <= 5; $s++ ) {
                        if      ( $s <= $fs )             { $html .= '<span class="lsb-sp-star-full">★</span>'; }
                        elseif  ( $s === $fs + 1 && $hs ) { $html .= '<span class="lsb-sp-star-half">★</span>'; }
                        else                              { $html .= '<span class="lsb-sp-star-empty">☆</span>'; }
                    }
                    return $html;
                }
            }

            // Pre-fetch strip images for ALL featured businesses before the loop
            // so each gets its own isolated pool without global $post interference
           $pb_strip_images_by_id = array();
            if ( function_exists( 'mvc_de_get_square_images' ) && ! empty( $biz_featured_cards ) ) {
                global $post;
                wp_reset_postdata();
                $pb_orig_post = $post;

                foreach ( $biz_featured_cards as $pb_prefetch ) {
                    $pb_prefetch_id = $pb_prefetch->ID;

                    $post = get_post( $pb_prefetch_id );
                    setup_postdata( $post );

           $pb_prefetch_pool = mvc_de_get_square_images( 10 );

                // DEBUG — remove after confirming
                error_log( 'STRIP DEBUG business_id=' . $pb_prefetch_id . ' pool_count=' . count( $pb_prefetch_pool ) . ' IDs=' . implode( ',', array_column( (array) $pb_prefetch_pool, 'ID' ) ) );

                    $post = $pb_orig_post;
                    setup_postdata( $post );

                    // Get the square hero image for this business to exclude it
                    $pb_prefetch_sq = function_exists( 'mvc_de_get_square_image_for_business' )
                        ? mvc_de_get_square_image_for_business( $pb_prefetch_id )
                        : null;
                    $pb_prefetch_exclude = $pb_prefetch_sq ? (int) $pb_prefetch_sq->ID : 0;

                    $pb_prefetch_strip = array();
                    foreach ( $pb_prefetch_pool as $pb_prefetch_candidate ) {
                      if ( count( $pb_prefetch_strip ) >= 4 ) break;
                        if ( $pb_prefetch_exclude && (int) $pb_prefetch_candidate->ID === $pb_prefetch_exclude ) continue;
                        $pb_prefetch_strip[] = $pb_prefetch_candidate;
                    }

                    $pb_strip_images_by_id[ $pb_prefetch_id ] = $pb_prefetch_strip;
                }
            }
        ?>

        <?php if ( ! empty( $biz_featured_cards ) ) : ?>
        <div class="lsb-sp-biz-cards">
        <?php foreach ( $biz_featured_cards as $cb_featured ) :
                $cb_id      = $cb_featured->ID;
                $cb_title   = $cb_featured->post_title;
                $cb_logo_f  = function_exists( 'get_field' ) ? get_field( 'business_logo',              $cb_id ) : '';
                $cb_desc    = function_exists( 'get_field' ) ? get_field( 'business_short_description', $cb_id ) : '';
                $cb_rating  = function_exists( 'get_field' ) ? get_field( 'business_rating',            $cb_id ) : '';
                $cb_phone   = function_exists( 'get_field' ) ? get_field( 'business_phone',             $cb_id ) : '';
                $cb_website = function_exists( 'get_field' ) ? get_field( 'business_website',           $cb_id ) : '';

                if ( ! $cb_desc ) {
                    $cb_desc = get_the_excerpt( $cb_id );
                }

                $cb_phone_clean   = preg_replace( '/[^0-9+]/', '', $cb_phone );
                $cb_website_label = $cb_website ? preg_replace( '#^https?://(www\.)?#', '', rtrim( $cb_website, '/' ) ) : '';
                $cb_logo_url      = '';

                if ( $cb_logo_f ) {
                    $cb_logo_url = is_array( $cb_logo_f ) ? ( $cb_logo_f['url'] ?? '' ) : wp_get_attachment_image_url( $cb_logo_f, 'thumbnail' );
                }

                if ( ! $cb_logo_url ) {
                    $cb_logo_url = get_the_post_thumbnail_url( $cb_id, 'thumbnail' );
                }


$cb_square_image = function_exists( 'mvc_de_get_square_image_for_business' )
    ? mvc_de_get_square_image_for_business( $cb_id )
    : null;

$cb_square_html = '';

if ( $cb_square_image && ! empty( $cb_square_image->ID ) ) {

    $cb_square_alt = function_exists( 'mvc_de_get_dynamic_image_alt' )
        ? mvc_de_get_dynamic_image_alt( $cb_square_image->ID, array(
            'businesses' => wp_get_post_terms( $cb_id, 'business_cat', array( 'fields' => 'slugs' ) ),
            'cities'     => wp_get_post_terms( $cb_id, MVC_Directory_Engine::TAX_CITY, array( 'fields' => 'slugs' ) ),
            'industries' => wp_get_post_terms( $cb_id, MVC_Directory_Engine::TAX_INDUSTRY, array( 'fields' => 'slugs' ) ),
            'services'   => wp_get_post_terms( $cb_id, MVC_Directory_Engine::TAX_SERVICE, array( 'fields' => 'slugs' ) ),
        ) )
        : $cb_title;

    $cb_square_html = wp_get_attachment_image(
        $cb_square_image->ID,
        'medium_large',
        false,
        array(
            'alt'     => esc_attr( $cb_square_alt ?: $cb_title ),
            'loading' => 'lazy',
        )
    );
}

// Strip images pre-fetched above, keyed by business ID
$cb_strip_images = $pb_strip_images_by_id[ $cb_id ] ?? array();

 $cb_initials = '';
                foreach ( array_slice( explode( ' ', $cb_title ), 0, 2 ) as $cw ) {
                    $cb_initials .= strtoupper( substr( $cw, 0, 1 ) );
                }
            ?>
           
<article class="lsb-sp-biz-card-featured">

                <div class="lsb-sp-fc-top">
                    <div class="lsb-sp-fc-logo">
                        <?php if ( $cb_logo_url ) : ?>
                        <img src="<?php echo esc_url( $cb_logo_url ); ?>" alt="<?php echo esc_attr( $cb_title ); ?> logo" loading="lazy" width="60" height="60">
                        <?php else : ?>
                        <span class="lsb-sp-fc-initials" aria-hidden="true"><?php echo esc_html( $cb_initials ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="lsb-sp-fc-identity">
                        <div class="lsb-sp-fc-badge"><span class="lsb-sp-fc-badge-dot"></span> Featured</div>
                        <h3 class="lsb-sp-fc-name"><?php echo esc_html( $cb_title ); ?></h3>
                        <div class="lsb-sp-fc-industry"><?php echo esc_html( $industry_name ); ?></div>
                    </div>
                    <div class="lsb-sp-fc-right">
                        <?php if ( $cb_rating ) : ?>
                        <div class="lsb-sp-fc-rating">
                            <span class="lsb-sp-fc-stars"><?php echo lsb_sp_stars( $cb_rating ); ?></span>
                            <span class="lsb-sp-fc-rating-num"><?php echo esc_html( number_format( (float) $cb_rating, 1 ) ); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="lsb-sp-fc-plan">Premium</div>
                    </div>
                </div>

                <div class="lsb-sp-fc-divider"></div>

                <?php if ( $cb_desc ) : ?>
                <p class="lsb-sp-fc-desc"><?php echo esc_html( wp_trim_words( $cb_desc, 28, '…' ) ); ?></p>
                <?php endif; ?>

                <div class="lsb-sp-fc-meta-row">
                    <?php if ( $cb_phone ) : ?>
                    <a href="tel:<?php echo esc_attr( $cb_phone_clean ); ?>" class="lsb-sp-fc-meta-item">
                        <span class="lsb-sp-fc-meta-icon">📞</span><?php echo esc_html( $cb_phone ); ?>
                    </a>
                    <?php endif; ?>
                    <?php if ( $cb_website ) : ?>
                    <a href="<?php echo esc_url( $cb_website ); ?>" class="lsb-sp-fc-meta-item" target="_blank" rel="noopener noreferrer">
                        <span class="lsb-sp-fc-meta-icon">🌐</span><?php echo esc_html( $cb_website_label ); ?>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="lsb-sp-fc-footer">
                    <div class="lsb-sp-fc-location">
                        <span class="lsb-sp-fc-loc-dot"></span>
                        <?php
                        $cb_city_terms = wp_get_post_terms( $cb_id, 'city_cat', array( 'fields' => 'names' ) );
                        echo ! empty( $cb_city_terms ) && ! is_wp_error( $cb_city_terms )
                            ? esc_html( $cb_city_terms[0] )
                            : 'Greater Los Angeles';
                        ?>
                    </div>
                    <div class="lsb-sp-fc-actions">
                        <?php if ( $cb_phone ) : ?>
                        <a href="tel:<?php echo esc_attr( $cb_phone_clean ); ?>" class="lsb-sp-fc-btn-secondary">📞 Call</a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( get_permalink( $cb_id ) ); ?>" class="lsb-sp-fc-btn-primary">View Profile →</a>
                    </div>
                </div>

                <?php if ( ! empty( $cb_strip_images ) ) : ?>
                <div class="lsb-sp-biz-photo-strip">
                    <?php foreach ( $cb_strip_images as $cb_strip_img ) :
                        $cb_strip_alt = function_exists( 'mvc_de_get_dynamic_image_alt' )
                            ? mvc_de_get_dynamic_image_alt( $cb_strip_img->ID, array(
                                'businesses' => wp_get_post_terms( $cb_id, 'business_cat', array( 'fields' => 'slugs' ) ),
                            ) )
                            : $cb_title;
                    ?>
                    <div class="lsb-sp-biz-photo-strip-item">
                        <?php echo wp_get_attachment_image( $cb_strip_img->ID, 'medium', false, array( 'alt' => esc_attr( $cb_strip_alt ?: $cb_title ), 'loading' => 'lazy' ) ); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </article>

            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $biz_secondary_cards ) ) : ?>
        <div class="lsb-sp-biz-secondary-grid">
            <?php foreach ( $biz_secondary_cards as $csb ) :
                $csb_id          = $csb->ID;
                $csb_title       = $csb->post_title;
                $csb_rating      = function_exists( 'get_field' ) ? get_field( 'business_rating', $csb_id ) : '';
                $csb_phone       = function_exists( 'get_field' ) ? get_field( 'business_phone',  $csb_id ) : '';
                $csb_phone_clean = preg_replace( '/[^0-9+]/', '', $csb_phone );
            ?>
            <a href="<?php echo esc_url( get_permalink( $csb_id ) ); ?>" class="lsb-sp-biz-card-secondary">
                <h3 class="lsb-sp-biz-sec-name"><?php echo esc_html( $csb_title ); ?></h3>
                <div class="lsb-sp-biz-sec-row">
                    <?php if ( $csb_phone ) : ?>
                    <span class="lsb-sp-biz-sec-phone" onclick="event.preventDefault(); window.location='tel:<?php echo esc_attr( $csb_phone_clean ); ?>'">
                        📞 <?php echo esc_html( $csb_phone ); ?>
                    </span>
                    <?php endif; ?>

                    <?php if ( $csb_rating ) : ?>
                    <span class="lsb-sp-biz-sec-rating">★ <?php echo esc_html( number_format( (float) $csb_rating, 1 ) ); ?></span>
                    <?php endif; ?>
                </div>
                <span class="lsb-sp-biz-sec-view">View Profile →</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>

    <?php endif; // end $is_parent / else (child) ?>

    <!-- ============================================================
         RELATED SERVICES — CHILD MODE ONLY
    ============================================================ -->
    <?php if ( $is_child && ! empty( $sibling_services ) ) : ?>
    <section class="lsb-sp-related-section lsb-sp-light-bg" id="lsb-sp-related">
        <div class="lsb-sp-related-inner">

            <div class="lsb-sp-related-header">
                <span class="lsb-sp-label">Also Under <?php echo esc_html( $parent_post_name ); ?></span>
                <h2 class="lsb-sp-h2">Related Services</h2>
            </div>

            <div class="lsb-sp-related-grid">
                <?php foreach ( $sibling_services as $sib ) :
                    $sib_term = $sib['term'];
                    $sib_post = $sib['post'];
                    $sib_url  = get_permalink( $sib_post->ID );
                    $sib_icon = function_exists( 'lsb_get_service_icon' ) ? lsb_get_service_icon( $sib_term->slug ) : '🔧';
                ?>
                <a href="<?php echo esc_url( $sib_url ); ?>" class="lsb-sp-related-card">
                    <span class="lsb-sp-related-icon"><?php echo $sib_icon; ?></span>
                    <span class="lsb-sp-related-name"><?php echo esc_html( $sib_term->name ); ?></span>
                    <span class="lsb-sp-related-cta">View Service →</span>
                </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         MORE INDUSTRY SERVICES (both modes)
    ============================================================ -->
    <?php if ( ! empty( $industry_parent_services ) ) : ?>
    <section class="lsb-sp-related-section lsb-sp-light-bg" id="lsb-sp-industry-services">
        <div class="lsb-sp-related-inner">

            <div class="lsb-sp-related-header">
                <span class="lsb-sp-label">More in <?php echo esc_html( $industry_name ); ?></span>
                <h2 class="lsb-sp-h2">Explore More <?php echo esc_html( $industry_name ); ?> Services</h2>
            </div>

            <div class="lsb-sp-related-grid">
                <?php foreach ( $industry_parent_services as $ips ) :
                    $ips_term = $ips['term'];
                    $ips_post = $ips['post'];
                    $ips_url  = get_permalink( $ips_post->ID );
                    $ips_icon = function_exists( 'lsb_get_service_icon' ) ? lsb_get_service_icon( $ips_term->slug ) : '🔧';
                ?>
                <a href="<?php echo esc_url( $ips_url ); ?>" class="lsb-sp-related-card">
                    <span class="lsb-sp-related-icon"><?php echo $ips_icon; ?></span>
                    <span class="lsb-sp-related-name"><?php echo esc_html( $ips_post->post_title ); ?></span>
                    <span class="lsb-sp-related-cta">View Service →</span>
                </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         FAQ (both modes)
    ============================================================ -->
    <?php if ( ! empty( $faq_posts ) ) :
        $faq_schema_items = [];
        foreach ( $faq_posts as $fq ) {
            $fa = function_exists( 'get_field' ) ? get_field( 'faq_short_answer', $fq->ID ) : '';
            if ( $fa ) $faq_schema_items[] = [
                '@type'          => 'Question',
                'name'           => $fq->post_title,
                'acceptedAnswer' => [ '@type' => 'Answer', 'text' => wp_strip_all_tags( $fa ) ],
            ];
        }
        $faq_disclaimer = ( $industry_post_id && function_exists( 'get_field' ) )
            ? get_field( 'faq_source_note_disclaimer', $industry_post_id )
            : '';
    ?>
    <?php if ( ! empty( $faq_schema_items ) ) : ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode( [ '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faq_schema_items ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
    </script>
    <?php endif; ?>

    <section class="lsb-sp-faq-section lsb-sp-light-bg" id="lsb-sp-faq">
        <div class="lsb-sp-faq-inner">

            <div class="lsb-sp-faq-header">
                <span class="lsb-sp-label">Got Questions?</span>
                <h2 class="lsb-sp-h2">Frequently Asked Questions About <?php echo esc_html( $service_title ); ?></h2>
            </div>

            <div class="lsb-sp-faq-list">
                <?php foreach ( $faq_posts as $fi => $fq ) :
                    $fa      = function_exists( 'get_field' ) ? get_field( 'faq_short_answer', $fq->ID ) : '';
                    $fq_url  = get_permalink( $fq->ID );
                    $item_id = 'lsb-sp-faq-' . $service_slug . '-' . $fi;
                    $body_id = 'lsb-sp-faqb-' . $service_slug . '-' . $fi;
                    if ( ! $fa ) continue;
                ?>
                <div class="lsb-sp-faq-item" id="<?php echo esc_attr( $item_id ); ?>">
                    <button class="lsb-sp-faq-trigger"
                            type="button"
                            aria-expanded="false"
                            aria-controls="<?php echo esc_attr( $body_id ); ?>">
                        <span class="lsb-sp-faq-q"><?php echo esc_html( $fq->post_title ); ?></span>
                        <span class="lsb-sp-faq-icon" aria-hidden="true">+</span>
                    </button>
                    <div class="lsb-sp-faq-body"
                         id="<?php echo esc_attr( $body_id ); ?>"
                         role="region"
                         aria-labelledby="<?php echo esc_attr( $item_id ); ?>">
                        <div class="lsb-sp-faq-answer">
                            <?php echo wp_kses_post( $fa ); ?>
                        </div>
                        <div class="lsb-sp-faq-cta-row">
                            <a href="<?php echo esc_url( $fq_url ); ?>" class="lsb-sp-faq-learn-more">
                                Learn More
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ( $faq_disclaimer ) : ?>
            <p class="lsb-sp-faq-disclaimer"><?php echo esc_html( $faq_disclaimer ); ?></p>
            <?php endif; ?>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         BLOG SECTION (both modes)
    ============================================================ -->
    <?php if ( ! empty( $blog_posts ) ) : ?>
    <section class="lsb-sp-blog-section lsb-sp-light-bg" id="lsb-sp-blog">
        <div class="lsb-sp-blog-inner">

            <div class="lsb-sp-blog-header">
                <div>
                    <span class="lsb-sp-label">
                        <?php echo $industry_name ? 'From the ' . esc_html( $industry_name ) . ' Blog' : 'From Our Blog'; ?>
                    </span>
                    <h2 class="lsb-sp-h2">Related Articles &amp; Guides</h2>
                </div>
                <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="lsb-sp-blog-view-all">
                    View All Posts →
                </a>
            </div>

            <div class="lsb-sp-blog-grid">
                <?php foreach ( $blog_posts as $blog_post ) :
                    $blog_id      = $blog_post->ID;
                    $blog_url     = get_permalink( $blog_id );
                    $blog_title   = get_the_title( $blog_id );
                    $blog_date    = get_the_date( 'M j, Y', $blog_id );
                    $blog_excerpt = get_the_excerpt( $blog_id );
                    if ( ! $blog_excerpt ) {
                        $blog_excerpt = wp_trim_words( get_post_field( 'post_content', $blog_id ), 20, '...' );
                    }
                    $blog_thumb   = get_the_post_thumbnail_url( $blog_id, 'medium_large' );

                    // Industry tag for the card
                    $blog_ind_terms = get_the_terms( $blog_id, 'industry_cat' );
                    $blog_ind_label = '';
                    if ( ! empty( $blog_ind_terms ) && ! is_wp_error( $blog_ind_terms ) ) {
                        $blog_ind_label = $blog_ind_terms[0]->name;
                    }
                ?>
                <a href="<?php echo esc_url( $blog_url ); ?>" class="lsb-sp-blog-card">

                    <?php if ( $blog_thumb ) : ?>
                    <img class="lsb-sp-blog-thumb"
                         src="<?php echo esc_url( $blog_thumb ); ?>"
                         alt="<?php echo esc_attr( $blog_title ); ?>"
                         loading="lazy">
                    <?php else : ?>
                    <div class="lsb-sp-blog-thumb-placeholder" aria-hidden="true">
                     <?php echo function_exists( 'lsb_get_industry_icon' ) ? lsb_get_industry_icon( $industry_term_slug ?: '' ) : '📝'; ?>
                    </div>
                    <?php endif; ?>

                    <div class="lsb-sp-blog-body">
                        <div class="lsb-sp-blog-meta">
                            <?php if ( $blog_ind_label ) : ?>
                            <span class="lsb-sp-blog-industry"><?php echo esc_html( $blog_ind_label ); ?></span>
                            <?php endif; ?>
                            <span class="lsb-sp-blog-date"><?php echo esc_html( $blog_date ); ?></span>
                        </div>
                        <h3 class="lsb-sp-blog-title"><?php echo esc_html( $blog_title ); ?></h3>
                        <?php if ( $blog_excerpt ) : ?>
                        <p class="lsb-sp-blog-excerpt"><?php echo esc_html( $blog_excerpt ); ?></p>
                        <?php endif; ?>
                        <span class="lsb-sp-blog-read">Read Article →</span>
                    </div>

                </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         FINAL CTA (both modes)
    ============================================================ -->
    <section class="lsb-sp-cta-section lsb-sp-dark-bg" id="lsb-sp-cta">
        <div class="lsb-sp-cta-grid-bg" aria-hidden="true"></div>
        <div class="lsb-sp-cta-glow-1"  aria-hidden="true"></div>
        <div class="lsb-sp-cta-glow-2"  aria-hidden="true"></div>
        <div class="lsb-sp-cta-inner">
            <span class="lsb-sp-label">Get Started Today</span>
            <h2 class="lsb-sp-cta-heading"><?php echo wp_kses_post( $cta_heading ); ?></h2>
            <p class="lsb-sp-cta-text"><?php echo wp_kses_post( $cta_text ); ?></p>
            <a href="<?php echo esc_url( $cta_btn_link ); ?>" class="lsb-sp-cta-btn">
                <?php echo esc_html( $cta_btn_text ); ?>
            </a>
        </div>
    </section>

    <!-- ============================================================
         FAQ ACCORDION JS
    ============================================================ -->
    <script>
    (function () {
        var items = document.querySelectorAll( '#lsb-sp-faq .lsb-sp-faq-item' );
        items.forEach( function ( item ) {
            var trigger = item.querySelector( '.lsb-sp-faq-trigger' );
            if ( ! trigger ) return;
            trigger.addEventListener( 'click', function () {
                var isOpen = item.classList.contains( 'is-open' );
                items.forEach( function ( el ) {
                    el.classList.remove( 'is-open' );
                    el.querySelector( '.lsb-sp-faq-trigger' ).setAttribute( 'aria-expanded', 'false' );
                } );
                if ( ! isOpen ) {
                    item.classList.add( 'is-open' );
                    trigger.setAttribute( 'aria-expanded', 'true' );
                }
            } );
        } );
    }() );
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode( 'service_page', 'lsb_service_page_shortcode' );

endif;