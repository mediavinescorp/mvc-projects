<?php
/**
 * Shortcode: [industry_page]
 *
 * File: /shortcodes/industry-single-page.php
 *
 * CHANGES IN THIS VERSION:
 *   1. Fixed "Undefined array key slug" error in FAQ query — now uses explicit
 *      tax_query with term_id instead of reusing $tax_q[0]
 *   2. Industry Overview section now shows parent service_type cards (icon +
 *      name + taxonomy description) instead of what_this_industry_covers WYSIWYG
 *   3. Cities section now shows 4 random featured cards + A-Z alphabet browser
 *      where clicking a letter reveals cities starting with that letter, each
 *      linking to /cities/{city}/{industry}/
 */

if ( ! function_exists( 'lsb_industry_page_shortcode' ) ) :

function lsb_industry_page_shortcode( $atts ) {

    $atts = shortcode_atts( [ 'industry' => '' ], $atts, 'industry_page' );

    // ── 1. Get current industry post ──────────────────────────────────────
    $industry_post_id = get_the_ID();

    if ( ! empty( $atts['industry'] ) ) {
        $override = get_page_by_path( sanitize_title( $atts['industry'] ), OBJECT, get_post_type() );
        if ( $override ) $industry_post_id = $override->ID;
    }

    if ( ! $industry_post_id ) {
        return '<!-- [industry_page] no post ID found -->';
    }

    // ── 2. CPT fields ─────────────────────────────────────────────────────
    $industry_slug = get_post_field( 'post_name',  $industry_post_id );
    $industry_name = get_post_field( 'post_title', $industry_post_id );

    // ── 3. ACF fields ─────────────────────────────────────────────────────
    $hero_intro        = '';
    $hero_subtitle     = '';
    $hero_image        = '';
    $industry_overview = '';
    $what_covers       = '';

    if ( function_exists( 'get_field' ) ) {
        $hero_intro        = get_field( 'industry_hero_intro',        $industry_post_id );
        $hero_subtitle     = get_field( 'industry_subtitle',          $industry_post_id );
        $industry_overview = get_field( 'industry_overview',          $industry_post_id );
        $what_covers       = get_field( 'what_this_industry_covers',  $industry_post_id );

        $raw_image = get_field( 'industry_hero_image', $industry_post_id );
        if ( is_array( $raw_image ) && ! empty( $raw_image['url'] ) ) {
            $hero_image = $raw_image['url'];
        } elseif ( is_string( $raw_image ) && ! empty( $raw_image ) ) {
            $hero_image = $raw_image;
        }
    }

    // ── 4. industry_cat term — used throughout ────────────────────────────
    $industry_term = get_term_by( 'slug', $industry_slug, 'industry_cat' );

    // ── 5. Services (queried directly by industry_cat term) ──────────────
    $services = [];
    if ( $industry_term && ! is_wp_error( $industry_term ) ) {
        $services_q = new WP_Query( [
            'post_type'      => 'services',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'all',
            'tax_query'      => [ [
                'taxonomy'         => 'industry_cat',
                'field'            => 'term_id',
                'terms'            => $industry_term->term_id,
                'include_children' => true,
            ] ],
        ] );
        $services = $services_q->posts;
        wp_reset_postdata();
    }

    // ── 6. Parent service_type cards for overview section ─────────────────
    // Pull via mapping engine, filter to top-level parents, build card data
    $overview_service_cards = [];

    $all_svc_slugs = function_exists( 'mvc_de_get_industry_services' )
        ? mvc_de_get_industry_services( $industry_slug )
        : [];
    if ( ! is_array( $all_svc_slugs ) ) $all_svc_slugs = [];

    $parent_svc_slugs = array_filter( $all_svc_slugs, function( $slug ) {
        $t = get_term_by( 'slug', $slug, 'service_type' );
        return $t && ! is_wp_error( $t ) && (int) $t->parent === 0;
    } );

    foreach ( $parent_svc_slugs as $psvc_slug ) {
        $psvc_term = get_term_by( 'slug', $psvc_slug, 'service_type' );
        if ( ! $psvc_term || is_wp_error( $psvc_term ) ) continue;

        // Taxonomy description for this service_type term
        $psvc_desc = ! empty( $psvc_term->description )
            ? wp_trim_words( $psvc_term->description, 20, '…' )
            : '';

        // Find matching services CPT post for icon
        $psvc_post = null;
        $by_slug   = get_posts( [
            'post_type'      => 'services',
            'name'           => $psvc_slug,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ] );
        if ( ! empty( $by_slug ) ) {
            $psvc_post = $by_slug[0];
        }

        // Icon from ACF field on services CPT post
        $psvc_icon_url = '';
        if ( $psvc_post && function_exists( 'get_field' ) ) {
            $raw_icon = get_field( 'service_icon', $psvc_post->ID );
            if ( is_array( $raw_icon ) && ! empty( $raw_icon['url'] ) ) {
                $psvc_icon_url = $raw_icon['url'];
            } elseif ( is_string( $raw_icon ) && ! empty( $raw_icon ) ) {
                $psvc_icon_url = $raw_icon;
            }
        }

        $overview_service_cards[] = [
            'term'     => $psvc_term,
            'post'     => $psvc_post,
            'url'      => $psvc_post ? get_permalink( $psvc_post->ID ) : '#',
            'icon_url' => $psvc_icon_url,
            'desc'     => $psvc_desc,
        ];
    }

    // ── 7. Cities ─────────────────────────────────────────────────────────
    // Pull ALL city posts that have this industry_cat assigned
    $all_city_posts = [];
    if ( $industry_term && ! is_wp_error( $industry_term ) ) {
        $all_cities_q = new WP_Query( [
            'post_type'      => 'cities',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'tax_query'      => [ [
                'taxonomy'         => 'industry_cat',
                'field'            => 'term_id',
                'terms'            => $industry_term->term_id,
                'include_children' => true,
            ] ],
        ] );
        $all_city_posts = $all_cities_q->posts;
        wp_reset_postdata();
    }

    // Fallback: use city_cat terms from industry post (original method)
    if ( empty( $all_city_posts ) ) {
        $city_cat_terms = wp_get_post_terms( $industry_post_id, 'city_cat', [ 'fields' => 'all' ] );
        if ( ! empty( $city_cat_terms ) && ! is_wp_error( $city_cat_terms ) ) {
            foreach ( $city_cat_terms as $ct ) {
                $cp = get_posts( [
                    'post_type'      => 'cities',
                    'name'           => $ct->slug,
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                ] );
                if ( ! empty( $cp ) ) $all_city_posts[] = $cp[0];
            }
        }
    }

    // 4 random featured city cards
    $featured_city_posts = $all_city_posts;
    if ( count( $featured_city_posts ) > 4 ) {
        $keys = array_rand( $featured_city_posts, 4 );
        $featured_city_posts = array_map( fn( $k ) => $all_city_posts[ $k ], (array) $keys );
    }

    // Build A-Z index
    $cities_by_letter = [];
    foreach ( $all_city_posts as $cp ) {
        $first = strtoupper( substr( $cp->post_title, 0, 1 ) );
        if ( ! isset( $cities_by_letter[ $first ] ) ) $cities_by_letter[ $first ] = [];
        $cities_by_letter[ $first ][] = $cp;
    }
    ksort( $cities_by_letter );
    $available_letters = array_keys( $cities_by_letter );

    // Keep $cities array for backward compat (hero dropdown)
    $city_cat_terms = wp_get_post_terms( $industry_post_id, 'city_cat', [ 'fields' => 'all' ] );
    $cities = [];
    if ( ! empty( $city_cat_terms ) && ! is_wp_error( $city_cat_terms ) ) {
        foreach ( $city_cat_terms as $city_term ) {
            $city_post = get_posts( [
                'post_type'      => 'cities',
                'name'           => $city_term->slug,
                'posts_per_page' => 1,
                'post_status'    => 'publish',
            ] );
            $cities[] = [
                'term' => $city_term,
                'post' => ! empty( $city_post ) ? $city_post[0] : null,
            ];
        }
    }

    // ── 8. URLs ───────────────────────────────────────────────────────────
    $home_url     = trailingslashit( home_url() );
    $industry_url = $home_url . 'industries/';

    // ── 9. Render ─────────────────────────────────────────────────────────
    ob_start();
    ?>

    <style id="lsb-industry-page-css">

    .lsb-dark-bg {
        --lsb-heading:   #F5F7FA;
        --lsb-body:      rgba(255,255,255,0.60);
        --lsb-muted:     rgba(255,255,255,0.35);
        --lsb-label:     #00C9A7;
        --lsb-border:    rgba(255,255,255,0.08);
        --lsb-card-bg:   rgba(255,255,255,0.04);
        --lsb-divider:   rgba(255,255,255,0.08);
    }
    .lsb-light-bg {
        --lsb-heading:   #0D1B2A;
        --lsb-body:      #3D4F63;
        --lsb-muted:     #8A9BB0;
        --lsb-label:     #00A88C;
        --lsb-border:    #E4EAF2;
        --lsb-card-bg:   #F5F7FA;
        --lsb-divider:   #E4EAF2;
    }
    .lsb-section-label {
        display:inline-block; font-size:0.72rem; font-weight:600;
        letter-spacing:0.12em; text-transform:uppercase;
        color:var(--lsb-label); margin-bottom:12px;
        font-family:'DM Sans',sans-serif;
    }
    .lsb-h2 {
        font-family:'Syne',sans-serif;
        font-size:clamp(1.8rem,3.5vw,2.6rem);
        font-weight:800; color:var(--lsb-heading) !important;
        letter-spacing:-0.02em; line-height:1.1; margin-bottom:20px;
    }
    .lsb-body-p { font-size:1rem; color:var(--lsb-body) !important; line-height:1.8; font-family:'DM Sans',sans-serif; }
    .lsb-muted-label { font-size:0.78rem; font-weight:600; color:var(--lsb-muted) !important; text-transform:uppercase; letter-spacing:0.1em; font-family:'DM Sans',sans-serif; }

    /* ── BREADCRUMB ── */
    .lsb-ih-breadcrumb-bar { background:#0D1B2A; padding:0 40px; height:44px; display:flex; align-items:center; border-bottom:1px solid rgba(255,255,255,0.06); margin-top:72px; }
    .lsb-ih-breadcrumb { max-width:1200px; margin:0 auto; width:100%; display:flex; align-items:center; gap:8px; font-size:0.8rem; color:rgba(255,255,255,0.4); list-style:none; padding:0; font-family:'DM Sans',sans-serif; }
    .lsb-ih-breadcrumb a { color:rgba(255,255,255,0.4); text-decoration:none; transition:color .2s; }
    .lsb-ih-breadcrumb a:hover { color:#00C9A7; }
    .lsb-ih-breadcrumb-sep { color:rgba(255,255,255,0.2); }
    .lsb-ih-breadcrumb-current { color:#00C9A7; font-weight:500; }

    /* ── HERO ── */
    .lsb-ih-hero { background:#0D1B2A; padding:56px 40px 64px; position:relative; overflow:hidden; }
    .lsb-ih-grid-bg { position:absolute; inset:0; pointer-events:none; background-image:linear-gradient(rgba(0,201,167,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(0,201,167,0.04) 1px,transparent 1px); background-size:60px 60px; }
    .lsb-ih-glow-1 { position:absolute; pointer-events:none; width:500px; height:500px; background:radial-gradient(circle,rgba(0,201,167,0.1) 0%,transparent 70%); top:-120px; right:-80px; }
    .lsb-ih-glow-2 { position:absolute; pointer-events:none; width:340px; height:340px; background:radial-gradient(circle,rgba(244,197,66,0.06) 0%,transparent 70%); bottom:0; left:100px; }
    .lsb-ih-inner { max-width:1200px; margin:0 auto; position:relative; z-index:1; }
    .lsb-ih-columns { display:grid; grid-template-columns:1fr 460px; gap:64px; align-items:center; }
    .lsb-ih-columns.no-image { grid-template-columns:1fr; max-width:780px; }
    .lsb-ih-badge { display:inline-flex; align-items:center; background:rgba(0,201,167,0.12); border:1px solid rgba(0,201,167,0.25); border-radius:100px; padding:6px 16px; margin-bottom:24px; }
    .lsb-ih-badge span { color:#00C9A7; font-size:0.78rem; font-weight:500; letter-spacing:0.06em; text-transform:uppercase; font-family:'DM Sans',sans-serif; }
    .lsb-ih-h1 { font-family:'Syne',sans-serif; font-size:clamp(2.2rem,4.5vw,3.8rem); font-weight:800; color:#F5F7FA; line-height:1; letter-spacing:-0.01em; margin-bottom:28px; }
    .lsb-ih-h1 span { display:block; line-height:1; }
    .lsb-ih-h1 em { font-style:normal; color:#00C9A7; display:block; line-height:1; margin-top:4px; }
    .lsb-ih-intro { font-size:1.05rem; color:var(--lsb-body) !important; line-height:1.8; font-weight:300; margin-bottom:12px; font-family:'DM Sans',sans-serif; }
    .lsb-ih-subtitle { font-size:0.95rem; color:var(--lsb-muted) !important; line-height:1.6; font-weight:300; margin-bottom:36px; font-family:'DM Sans',sans-serif; font-style:italic; }
    .lsb-ih-dropdowns { display:flex; flex-wrap:wrap; gap:12px; position:relative; z-index:10; }
    .lsb-ih-dropdown { position:relative; }
    .lsb-ih-dropdown-btn { display:inline-flex; align-items:center; gap:8px; background:#00C9A7; border:none; color:#0D1B2A; font-family:'Syne',sans-serif; font-size:0.88rem; font-weight:700; padding:11px 20px; border-radius:8px; cursor:pointer; letter-spacing:0.01em; transition:background .2s,transform .15s; white-space:nowrap; }
    .lsb-ih-dropdown-btn:hover { background:#00A88C; transform:translateY(-1px); }
    .lsb-ih-dropdown-btn.secondary { background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.15); color:rgba(255,255,255,0.8); }
    .lsb-ih-dropdown-btn.secondary:hover { background:rgba(0,201,167,0.12); border-color:rgba(0,201,167,0.3); color:#00C9A7; transform:translateY(-1px); }
    .lsb-ih-dropdown-btn svg { transition:transform .2s; flex-shrink:0; }
    .lsb-ih-dropdown.is-open .lsb-ih-dropdown-btn svg { transform:rotate(180deg); }
    .lsb-ih-dropdown-menu { display:none; position:absolute; top:calc(100% + 6px); left:0; min-width:220px; max-height:280px; overflow-y:auto; background:#1B2F45; border:1px solid rgba(255,255,255,0.1); border-radius:10px; box-shadow:0 12px 36px rgba(0,0,0,0.4); z-index:100; padding:6px; }
    .lsb-ih-dropdown-menu::-webkit-scrollbar { width:4px; }
    .lsb-ih-dropdown-menu::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.15); border-radius:4px; }
    .lsb-ih-dropdown.is-open .lsb-ih-dropdown-menu { display:block; }
    .lsb-ih-dropdown-menu a { display:block; padding:9px 14px; color:rgba(255,255,255,0.7); text-decoration:none; font-size:0.88rem; font-family:'DM Sans',sans-serif; border-radius:6px; transition:all .15s; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .lsb-ih-dropdown-menu a:hover { background:rgba(0,201,167,0.12); color:#00C9A7; }
    .lsb-ih-dropdown-empty { padding:10px 14px; color:rgba(255,255,255,0.3); font-size:0.82rem; font-family:'DM Sans',sans-serif; font-style:italic; }
    .lsb-ih-image-wrap { border-radius:20px; overflow:hidden; }
    .lsb-ih-image-wrap img { width:100%; height:auto; display:block; border-radius:20px; }

    /* ── OVERVIEW / WHAT WE DO ── */
    .lsb-iwwd-section { background:#ffffff; padding:80px 40px; }
    .lsb-iwwd-inner { max-width:820px; margin:0 auto; }
    .lsb-iwwd-intro { font-size:1.05rem; color:var(--lsb-body) !important; line-height:1.8; font-weight:400; font-family:'DM Sans',sans-serif; margin-bottom:40px; padding-bottom:32px; border-bottom:1px solid var(--lsb-divider); }

    /* Related links strip */
    .lsb-iwwd-related { border-top:1px solid var(--lsb-divider); padding-top:32px; }
    .lsb-iwwd-related-label { font-size:0.78rem; font-weight:600; color:var(--lsb-muted) !important; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:14px; font-family:'DM Sans',sans-serif; }
    .lsb-iwwd-related-links { display:flex; flex-wrap:wrap; gap:8px; }
    .lsb-iwwd-related-link { display:inline-flex; align-items:center; gap:6px; background:var(--lsb-card-bg); border:1px solid var(--lsb-border); color:var(--lsb-heading) !important; font-size:0.85rem; font-weight:500; padding:8px 16px; border-radius:8px; text-decoration:none; font-family:'DM Sans',sans-serif; transition:all .2s; }
    .lsb-iwwd-related-link:hover { background:rgba(0,201,167,0.08); border-color:rgba(0,201,167,0.3); color:#00A88C; }
    .lsb-iwwd-related-link::before { content:'→'; color:#00C9A7; font-size:0.8rem; }

    /* ── SERVICES GRID ── */
    .lsb-svc-section { background:#F5F7FA; padding:80px 40px; }
    .lsb-svc-inner { max-width:1200px; margin:0 auto; }
    .lsb-svc-header { max-width:680px; margin-bottom:48px; }
    .lsb-svc-intro { font-size:1rem; color:var(--lsb-body) !important; line-height:1.8; font-family:'DM Sans',sans-serif; margin-top:12px; }
    .lsb-svc-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
    .lsb-svc-card { background:#ffffff; border:1px solid var(--lsb-border); border-radius:14px; padding:28px 24px; display:flex; flex-direction:column; gap:12px; text-decoration:none; transition:all .25s; position:relative; overflow:hidden; }
    .lsb-svc-card::before { content:''; position:absolute; bottom:0; left:0; right:0; height:3px; background:#00C9A7; transform:scaleX(0); transform-origin:left; transition:transform .25s; }
    .lsb-svc-card:hover { border-color:rgba(0,201,167,0.35); transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,201,167,0.08); }
    .lsb-svc-card:hover::before { transform:scaleX(1); }
    .lsb-svc-card-icon { width:48px; height:48px; background:rgba(0,201,167,0.08); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; }
    .lsb-svc-card-icon img { width:28px; height:28px; object-fit:contain; }
    .lsb-svc-card-name { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; color:var(--lsb-heading) !important; letter-spacing:-0.01em; line-height:1.3; }
    .lsb-svc-card-desc { font-size:0.875rem; color:var(--lsb-body) !important; line-height:1.7; font-family:'DM Sans',sans-serif; flex:1; }
    .lsb-svc-card-cta { display:inline-flex; align-items:center; gap:6px; font-size:0.82rem; font-weight:600; color:#00A88C !important; font-family:'DM Sans',sans-serif; margin-top:4px; transition:gap .2s; }
    .lsb-svc-card:hover .lsb-svc-card-cta { gap:10px; }
    .lsb-svc-empty { font-size:0.95rem; color:var(--lsb-muted) !important; font-family:'DM Sans',sans-serif; font-style:italic; }
    @media (max-width:1024px) { .lsb-svc-grid { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:640px) { .lsb-svc-section { padding:56px 20px; } .lsb-svc-grid { grid-template-columns:1fr; } }

    /* ── CITIES SECTION ── */
    .lsb-cities-section { background:#ffffff; padding:80px 40px; }
    .lsb-cities-inner { max-width:1200px; margin:0 auto; }
    .lsb-cities-header { max-width:680px; margin-bottom:48px; }
    .lsb-cities-intro { font-size:1rem; color:var(--lsb-body) !important; line-height:1.8; font-family:'DM Sans',sans-serif; margin-top:12px; }

    /* A-Z browser */
    .lsb-cities-az-header {
        display:flex; align-items:center; justify-content:space-between;
        margin-bottom:24px; flex-wrap:wrap; gap:12px;
    }
    .lsb-cities-az-label {
        font-family:'Syne',sans-serif;
        font-size:0.88rem; font-weight:700;
        color:#0D1B2A; letter-spacing:-0.01em;
    }
    .lsb-cities-az-bar {
        display:flex; flex-wrap:wrap; gap:6px;
        margin-bottom:32px;
    }
    .lsb-cities-az-btn {
        width:36px; height:36px;
        display:flex; align-items:center; justify-content:center;
        background:#F5F7FA; border:1px solid #E4EAF2;
        border-radius:8px;
        font-family:'Syne',sans-serif; font-size:0.85rem; font-weight:700;
        color:#0D1B2A; cursor:pointer;
        transition:all .2s;
    }
    .lsb-cities-az-btn:hover { background:#0D1B2A; border-color:#0D1B2A; color:#ffffff; }
    .lsb-cities-az-btn.is-active { background:#00C9A7; border-color:#00C9A7; color:#0D1B2A; }
    .lsb-cities-az-btn.is-disabled { opacity:0.3; cursor:default; pointer-events:none; }
    .lsb-cities-az-panel { display:none; }
    .lsb-cities-az-panel.is-active { display:block; }
    .lsb-cities-az-list {
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
        gap:10px;
    }
    .lsb-cities-az-item {
        display:flex; align-items:center; gap:10px;
        background:#F5F7FA; border:1px solid #E4EAF2;
        border-radius:10px; padding:12px 16px;
        text-decoration:none;
        transition:all .2s;
    }
    .lsb-cities-az-item:hover { border-color:rgba(0,201,167,0.4); background:#ffffff; box-shadow:0 4px 16px rgba(0,0,0,0.05); }
    .lsb-cities-az-pin { font-size:0.9rem; flex-shrink:0; }
    .lsb-cities-az-name { font-family:'Syne',sans-serif; font-size:0.9rem; font-weight:700; color:#0D1B2A; letter-spacing:-0.01em; }
    .lsb-cities-az-arrow { margin-left:auto; color:#00C9A7; font-size:0.78rem; flex-shrink:0; transition:transform .2s; }
    .lsb-cities-az-item:hover .lsb-cities-az-arrow { transform:translateX(3px); }

    @media (max-width:640px) { .lsb-cities-section { padding:56px 20px; } .lsb-cities-az-list { grid-template-columns:1fr 1fr; } }

    /* ── FEATURED BUSINESSES ── */
    .lsb-fb-section { background:#F5F7FA; padding:80px 40px; }
    .lsb-fb-inner { max-width:1200px; margin:0 auto; }
    .lsb-fb-header { max-width:680px; margin-bottom:48px; }
    .lsb-fb-intro { font-size:1rem; color:var(--lsb-body) !important; line-height:1.8; font-family:'DM Sans',sans-serif; margin-top:12px; }
    .lsb-fb-cards { display:flex; flex-direction:column; gap:16px; }
    .lsb-fb-card { background:#ffffff; border:1px solid var(--lsb-border); border-radius:16px; padding:28px 32px; display:flex; align-items:center; gap:24px; transition:border-color .25s,box-shadow .25s,background .25s; }
    .lsb-fb-card:hover { border-color:rgba(0,201,167,0.4) !important; box-shadow:0 8px 32px rgba(0,0,0,0.07) !important; background:#ffffff !important; }
    .lsb-fb-logo { width:72px; height:72px; border-radius:14px; overflow:hidden; flex-shrink:0; background:#0D1B2A; display:flex; align-items:center; justify-content:center; }
    .lsb-fb-logo img { width:100%; height:100%; object-fit:cover; display:block; }
    .lsb-fb-logo--initials { font-family:'Syne',sans-serif; font-weight:800; font-size:1.2rem; color:#00C9A7; letter-spacing:-0.01em; }
    .lsb-fb-body { flex:1; min-width:0; display:flex; flex-direction:column; gap:8px; }
    .lsb-fb-meta-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
    .lsb-fb-name { font-family:'Syne',sans-serif !important; font-size:1.1rem !important; font-weight:700 !important; color:var(--lsb-heading) !important; letter-spacing:-0.01em !important; margin:0 !important; padding:0 !important; }
    .lsb-fb-rating { display:flex; align-items:center; gap:5px; flex-shrink:0; }
    .lsb-fb-stars { display:flex; gap:1px; line-height:1; }
    .lsb-fb-star--full,.lsb-fb-star--half { color:#F4C542; font-size:1rem; }
    .lsb-fb-star--empty { color:#C8D4E0; font-size:1rem; }
    .lsb-fb-rating-num { font-family:'Syne',sans-serif; font-size:0.9rem; font-weight:700; color:var(--lsb-heading) !important; }
    .lsb-fb-desc { font-size:0.9rem !important; color:var(--lsb-body) !important; line-height:1.6 !important; font-weight:400 !important; font-family:'DM Sans',sans-serif; margin:0 !important; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .lsb-fb-cities { font-size:0.82rem !important; color:var(--lsb-body) !important; font-family:'DM Sans',sans-serif; margin:0 !important; }
    .lsb-fb-cities-label { font-weight:600; color:#00A88C; margin-right:4px; }
    .lsb-fb-cta { flex-shrink:0; }
    .lsb-fb-btn { display:inline-block; background:#0D1B2A !important; color:#ffffff !important; font-family:'Syne',sans-serif !important; font-weight:700 !important; font-size:0.85rem !important; padding:12px 24px !important; border-radius:8px !important; text-decoration:none !important; letter-spacing:0.02em !important; transition:background .2s,color .2s !important; white-space:nowrap; }
    .lsb-fb-btn:hover { background:#00C9A7 !important; color:#0D1B2A !important; }
    @media (max-width:768px) { .lsb-fb-section { padding:56px 20px; } .lsb-fb-card { flex-direction:column; align-items:flex-start; gap:16px; padding:24px; } .lsb-fb-meta-row { flex-direction:column; align-items:flex-start; gap:8px; } .lsb-fb-cta { width:100%; } .lsb-fb-btn { width:100% !important; text-align:center !important; } }

    /* ── COMMON PROBLEMS ── */
    .lsb-cp-section { background:#ffffff; padding:80px 40px; }
    .lsb-cp-inner { max-width:1200px; margin:0 auto; }
    .lsb-cp-header { max-width:680px; margin-bottom:48px; }
    .lsb-cp-intro { font-size:1rem; color:var(--lsb-body) !important; line-height:1.8; font-family:'DM Sans',sans-serif; margin-top:12px; }
    .lsb-cp-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:20px; }
    .lsb-cp-card { background:#F5F7FA; border:1px solid var(--lsb-border); border-radius:16px; padding:32px 28px; display:flex; flex-direction:column; gap:14px; transition:border-color .25s,box-shadow .25s,background .25s; position:relative; overflow:hidden; }
    .lsb-cp-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:#00C9A7; transform:scaleX(0); transform-origin:left; transition:transform .25s; }
    .lsb-cp-card:hover { border-color:rgba(0,201,167,0.35) !important; box-shadow:0 8px 28px rgba(0,0,0,0.06) !important; background:#ffffff !important; }
    .lsb-cp-card:hover::before { transform:scaleX(1); }
    .lsb-cp-heading { font-family:'Syne',sans-serif !important; font-size:1.05rem !important; font-weight:700 !important; color:var(--lsb-heading) !important; letter-spacing:-0.01em !important; line-height:1.3 !important; margin:0 !important; padding:0 !important; }
    .lsb-cp-desc { font-size:0.9rem !important; color:var(--lsb-body) !important; line-height:1.75 !important; font-family:'DM Sans',sans-serif !important; font-weight:400 !important; margin:0 !important; flex:1; }
    .lsb-cp-btn { display:inline-flex; align-items:center; gap:7px; background:transparent !important; border:1px solid var(--lsb-border) !important; color:var(--lsb-heading) !important; font-family:'Syne',sans-serif !important; font-weight:700 !important; font-size:0.82rem !important; padding:9px 18px !important; border-radius:8px !important; text-decoration:none !important; letter-spacing:0.02em !important; transition:background .2s,border-color .2s,color .2s !important; align-self:flex-start; margin-top:4px; }
    .lsb-cp-btn::after { content:'→'; color:#00C9A7; font-size:0.85rem; transition:transform .2s; }
    .lsb-cp-btn:hover { background:rgba(0,201,167,0.08) !important; border-color:rgba(0,201,167,0.4) !important; color:#00A88C !important; }
    .lsb-cp-card:hover .lsb-cp-btn::after { transform:translateX(3px); }
    @media (max-width:768px) { .lsb-cp-section { padding:56px 20px; } .lsb-cp-grid { grid-template-columns:1fr; } }

    /* ── WHY HIRE A PRO ── */
    .lsb-whp-section { background:#F5F7FA; padding:80px 40px; }
    .lsb-whp-inner { max-width:1200px; margin:0 auto; }
    .lsb-whp-header { max-width:780px; margin-bottom:48px; }
    .lsb-whp-columns { display:grid; grid-template-columns:3fr 2fr; gap:64px; align-items:start; }
    .lsb-whp-content { font-family:'DM Sans',sans-serif; }
    .lsb-whp-content p { font-size:0.97rem !important; color:var(--lsb-body) !important; line-height:1.8 !important; margin-bottom:20px !important; }
    .lsb-whp-content p:last-child { margin-bottom:0 !important; }
    .lsb-whp-content h3 { font-family:'Syne',sans-serif !important; font-size:1.05rem !important; font-weight:700 !important; color:var(--lsb-heading) !important; letter-spacing:-0.01em !important; line-height:1.3 !important; margin-top:32px !important; margin-bottom:10px !important; display:flex; align-items:center; gap:10px; }
    .lsb-whp-content h3:first-child { margin-top:0 !important; }
    .lsb-whp-content h3::before { content:''; display:inline-block; width:4px; height:18px; background:#00C9A7; border-radius:2px; flex-shrink:0; }
    .lsb-whp-content strong { color:var(--lsb-heading) !important; font-weight:600 !important; }
    .lsb-whp-content ul { padding-left:20px; margin-top:8px; margin-bottom:16px; }
    .lsb-whp-content ul li { font-size:0.95rem !important; color:var(--lsb-body) !important; line-height:1.8 !important; margin-bottom:6px !important; }
    .lsb-whp-checklist { background:#ffffff; border:1px solid var(--lsb-border); border-radius:16px; padding:32px 28px; display:flex; flex-direction:column; gap:0; position:sticky; top:96px; }
    .lsb-whp-checklist-title { font-family:'Syne',sans-serif !important; font-size:0.85rem !important; font-weight:700 !important; color:var(--lsb-muted) !important; text-transform:uppercase !important; letter-spacing:0.1em !important; margin-bottom:20px !important; }
    .lsb-whp-checklist-item { display:flex; align-items:flex-start; gap:14px; padding:14px 0; border-bottom:1px solid var(--lsb-divider); }
    .lsb-whp-checklist-item:last-child { border-bottom:none; padding-bottom:0; }
    .lsb-whp-checklist-item:first-of-type { padding-top:0; }
    .lsb-whp-check-icon { width:28px; height:28px; background:rgba(0,201,167,0.10); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
    .lsb-whp-check-icon img { width:16px; height:16px; object-fit:contain; }
    .lsb-whp-check-icon-default { color:#00C9A7; font-size:0.9rem; line-height:1; }
    .lsb-whp-check-text { font-size:0.92rem !important; color:var(--lsb-body) !important; line-height:1.6 !important; font-family:'DM Sans',sans-serif !important; font-weight:400 !important; margin:0 !important; }
    @media (max-width:1024px) { .lsb-whp-columns { grid-template-columns:1fr; gap:40px; } .lsb-whp-checklist { position:static; } }
    @media (max-width:768px) { .lsb-whp-section { padding:56px 20px; } }

    /* ── FAQ ── */
    .lsb-faq-section { background:#F5F7FA; padding:80px 40px; }
    .lsb-faq-inner { max-width:780px; margin:0 auto; }
    .lsb-faq-header { margin-bottom:48px; }
    .lsb-faq-list { display:flex; flex-direction:column; gap:12px; }
    .lsb-faq-item { background:#ffffff; border:1px solid var(--lsb-border); border-radius:14px; overflow:hidden; transition:border-color .25s; }
    .lsb-faq-item.is-open { border-color:rgba(0,201,167,0.35); }
    .lsb-faq-trigger { width:100%; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:22px 24px; background:transparent; border:none; cursor:pointer; text-align:left; transition:background .2s; }
    .lsb-faq-trigger:hover { background:#F5F7FA; }
    .lsb-faq-item.is-open .lsb-faq-trigger { background:#F5F7FA; }
    .lsb-faq-question { font-family:'Syne',sans-serif !important; font-size:0.97rem !important; font-weight:700 !important; color:var(--lsb-heading) !important; letter-spacing:-0.01em !important; line-height:1.4 !important; margin:0 !important; padding:0 !important; }
    .lsb-faq-icon { width:30px; height:30px; background:#F5F7FA; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#00C9A7; font-size:1.1rem; line-height:1; transition:background .2s,transform .25s; border:1px solid var(--lsb-border); }
    .lsb-faq-item.is-open .lsb-faq-icon { transform:rotate(45deg); background:rgba(0,201,167,0.1); border-color:rgba(0,201,167,0.3); }
    .lsb-faq-body { display:none; padding:0 24px 24px; }
    .lsb-faq-item.is-open .lsb-faq-body { display:block; }
    .lsb-faq-answer { font-family:'DM Sans',sans-serif; font-size:0.92rem !important; color:var(--lsb-body) !important; line-height:1.8 !important; padding-top:4px; padding-bottom:18px; border-bottom:1px solid var(--lsb-divider); margin-bottom:16px; }
    .lsb-faq-answer p { font-size:0.92rem !important; color:var(--lsb-body) !important; line-height:1.8 !important; margin-bottom:10px !important; }
    .lsb-faq-answer p:last-child { margin-bottom:0 !important; }
    .lsb-faq-answer strong { color:var(--lsb-heading) !important; font-weight:600 !important; }
    .lsb-faq-answer ul { padding-left:18px; margin-top:6px; }
    .lsb-faq-answer ul li { font-size:0.92rem !important; color:var(--lsb-body) !important; line-height:1.8 !important; margin-bottom:4px !important; }
    .lsb-faq-cta-row { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .lsb-faq-learn-more { display:inline-flex; align-items:center; gap:6px; font-family:'Syne',sans-serif !important; font-size:0.82rem !important; font-weight:700 !important; color:#00A88C !important; text-decoration:none !important; letter-spacing:0.02em !important; transition:gap .2s,color .2s !important; }
    .lsb-faq-learn-more::after { content:'→'; color:#00C9A7; font-size:0.85rem; transition:transform .2s; }
    .lsb-faq-learn-more:hover { color:#0D1B2A !important; gap:10px; }
    .lsb-faq-learn-more:hover::after { transform:translateX(3px); }
    .lsb-faq-disclaimer { font-size:0.75rem !important; color:var(--lsb-muted) !important; line-height:1.6 !important; font-family:'DM Sans',sans-serif !important; font-style:italic !important; font-weight:400 !important; margin:0 !important; flex:1; text-align:right; }
    @media (max-width:768px) { .lsb-faq-section { padding:56px 20px; } .lsb-faq-cta-row { flex-direction:column; align-items:flex-start; gap:10px; } .lsb-faq-disclaimer { text-align:left; } }

    /* ── CTA ── */
    .lsb-cta-section { background:#0D1B2A; padding:100px 40px; position:relative; overflow:hidden; }
    .lsb-cta-grid-bg { position:absolute; inset:0; pointer-events:none; background-image:linear-gradient(rgba(0,201,167,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(0,201,167,0.04) 1px,transparent 1px); background-size:60px 60px; }
    .lsb-cta-glow-1 { position:absolute; pointer-events:none; width:500px; height:500px; background:radial-gradient(circle,rgba(0,201,167,0.1) 0%,transparent 70%); top:-120px; right:-80px; }
    .lsb-cta-glow-2 { position:absolute; pointer-events:none; width:400px; height:400px; background:radial-gradient(circle,rgba(244,197,66,0.06) 0%,transparent 70%); bottom:-80px; left:-60px; }
    .lsb-cta-inner { max-width:680px; margin:0 auto; text-align:center; position:relative; z-index:1; }
    .lsb-cta-heading { font-family:'Syne',sans-serif !important; font-size:clamp(2rem,4vw,3rem) !important; font-weight:800 !important; color:#F5F7FA !important; letter-spacing:-0.02em !important; line-height:1.1 !important; margin-bottom:20px !important; }
    .lsb-cta-heading em { font-style:normal; color:#00C9A7; }
    .lsb-cta-text { font-size:1rem !important; color:rgba(255,255,255,0.55) !important; line-height:1.8 !important; font-family:'DM Sans',sans-serif !important; font-weight:300 !important; margin-bottom:40px !important; }
    .lsb-cta-text p { font-size:1rem !important; color:rgba(255,255,255,0.55) !important; line-height:1.8 !important; margin-bottom:0 !important; }
    .lsb-cta-btn { display:inline-flex; align-items:center; gap:8px; background:#00C9A7 !important; color:#0D1B2A !important; font-family:'Syne',sans-serif !important; font-weight:700 !important; font-size:0.95rem !important; padding:16px 36px !important; border-radius:8px !important; text-decoration:none !important; letter-spacing:0.02em !important; transition:background .2s,transform .15s !important; }
    .lsb-cta-btn::after { content:'→'; font-size:1rem; transition:transform .2s; }
    .lsb-cta-btn:hover { background:#00A88C !important; transform:translateY(-2px); }
    .lsb-cta-btn:hover::after { transform:translateX(4px); }
    @media (max-width:768px) { .lsb-cta-section { padding:72px 20px; } .lsb-cta-btn { width:100%; justify-content:center; } }

    /* ── RESPONSIVE ── */
    @media (max-width:1024px) { .lsb-ih-columns { grid-template-columns:1fr; gap:40px; } .lsb-ih-image-wrap { max-width:560px; } }
    @media (max-width:768px) { .lsb-ih-breadcrumb-bar { padding:0 20px; } .lsb-ih-hero { padding:40px 20px 52px; } .lsb-ih-h1 { font-size:2rem; line-height:1.3; } .lsb-ih-dropdowns { gap:8px; } .lsb-iwwd-section { padding:56px 20px; } .lsb-iwwd-intro { margin-bottom:28px; padding-bottom:24px; } }

    </style>

    <!-- ============================================================
         SECTION 1 — HERO
    ============================================================ -->
    <nav class="lsb-ih-breadcrumb-bar" aria-label="Breadcrumb">
        <ol class="lsb-ih-breadcrumb">
            <li><a href="<?php echo esc_url( $home_url ); ?>">Home</a></li>
            <li class="lsb-ih-breadcrumb-sep" aria-hidden="true">›</li>
            <li><a href="<?php echo esc_url( $industry_url ); ?>">Industries</a></li>
            <li class="lsb-ih-breadcrumb-sep" aria-hidden="true">›</li>
            <li class="lsb-ih-breadcrumb-current" aria-current="page"><?php echo esc_html( $industry_name ); ?></li>
        </ol>
    </nav>

    <section class="lsb-ih-hero lsb-dark-bg" aria-labelledby="lsb-ih-heading-<?php echo esc_attr( $industry_slug ); ?>">
        <div class="lsb-ih-grid-bg" aria-hidden="true"></div>
        <div class="lsb-ih-glow-1"  aria-hidden="true"></div>
        <div class="lsb-ih-glow-2"  aria-hidden="true"></div>
        <div class="lsb-ih-inner">
            <div class="lsb-ih-columns<?php echo $hero_image ? '' : ' no-image'; ?>">
                <div class="lsb-ih-left">
                    <div class="lsb-ih-badge">
                        <span><?php echo esc_html( $industry_name ); ?></span>
                    </div>
                    <h1 class="lsb-ih-h1" id="lsb-ih-heading-<?php echo esc_attr( $industry_slug ); ?>">
                        <span><?php echo esc_html( $industry_name ); ?> Services</span>
                        <em>Near You</em>
                    </h1>
                    <?php if ( $hero_intro ) : ?>
                    <p class="lsb-ih-intro"><?php echo wp_kses_post( $hero_intro ); ?></p>
                    <?php endif; ?>
                    <?php if ( $hero_subtitle ) : ?>
                    <p class="lsb-ih-subtitle"><?php echo wp_kses_post( $hero_subtitle ); ?></p>
                    <?php endif; ?>
                    <div class="lsb-ih-dropdowns">
                        <div class="lsb-ih-dropdown" id="lsb-dd-services-<?php echo esc_attr( $industry_slug ); ?>">
                            <button class="lsb-ih-dropdown-btn" type="button" aria-expanded="false">
                                View Services
                                <svg width="12" height="8" viewBox="0 0 12 8" fill="none"><path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                            <div class="lsb-ih-dropdown-menu" role="menu">
                                <?php if ( ! empty( $services ) ) : ?>
                                    <?php foreach ( $services as $svc ) : ?>
                                    <a href="<?php echo esc_url( get_permalink( $svc->ID ) ); ?>" role="menuitem"><?php echo esc_html( $svc->post_title ); ?></a>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <p class="lsb-ih-dropdown-empty">No services found</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="lsb-ih-dropdown" id="lsb-dd-cities-<?php echo esc_attr( $industry_slug ); ?>">
                            <button class="lsb-ih-dropdown-btn secondary" type="button" aria-expanded="false">
                                Browse Cities
                                <svg width="12" height="8" viewBox="0 0 12 8" fill="none"><path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                            <div class="lsb-ih-dropdown-menu" role="menu">
                                <?php if ( ! empty( $cities ) ) : ?>
                                    <?php foreach ( $cities as $city ) : ?>
                                    <a href="<?php echo esc_url( home_url( '/cities/' . $city['term']->slug . '/' . $industry_slug . '/' ) ); ?>" role="menuitem"><?php echo esc_html( $city['term']->name ); ?></a>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <p class="lsb-ih-dropdown-empty">No cities found</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ( $hero_image ) : ?>
                <div class="lsb-ih-image-wrap">
                    <img src="<?php echo esc_url( $hero_image ); ?>" alt="<?php echo esc_attr( $industry_name ); ?> Services" loading="eager">
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================
         SECTION 2 — INDUSTRY OVERVIEW + PARENT SERVICE TYPE CARDS
         CHANGE: Replaced what_this_industry_covers WYSIWYG with
         parent service_type cards (icon + name + taxonomy description)
    ============================================================ -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "What <?php echo esc_js( $industry_name ); ?> Companies Do",
        "description": "<?php echo esc_js( wp_strip_all_tags( $industry_overview ) ); ?>",
        "about": { "@type": "Service", "name": "<?php echo esc_js( $industry_name ); ?> Services", "areaServed": { "@type": "Place", "name": "Greater Los Angeles, California" } },
        "publisher": { "@type": "Organization", "name": "LocalServiceBiz", "url": "<?php echo esc_js( $home_url ); ?>" }
    }
    </script>

    <section class="lsb-iwwd-section lsb-light-bg" id="lsb-what-we-do">
        <div class="lsb-iwwd-inner">

            <span class="lsb-section-label">Industry Overview</span>
            <h2 class="lsb-h2">What <?php echo esc_html( $industry_name ); ?> Companies Do</h2>

            <?php if ( $industry_overview ) : ?>
            <p class="lsb-iwwd-intro"><?php echo wp_kses_post( $industry_overview ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $services ) ) : ?>
            <div class="lsb-iwwd-related">
                <p class="lsb-iwwd-related-label">Related <?php echo esc_html( $industry_name ); ?> Services</p>
                <div class="lsb-iwwd-related-links">
                    <?php foreach ( $services as $svc ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $svc->ID ) ); ?>" class="lsb-iwwd-related-link"><?php echo esc_html( $svc->post_title ); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- ============================================================
         SECTION 3 — SERVICES GRID (grouped by parent service_type)
    ============================================================ -->
    <?php
    $all_service_slugs = function_exists( 'mvc_de_get_industry_services' )
        ? mvc_de_get_industry_services( $industry_slug )
        : [];
    if ( ! is_array( $all_service_slugs ) ) $all_service_slugs = [];

    $parent_service_slugs = array_filter( $all_service_slugs, function( $slug ) {
        $term = get_term_by( 'slug', $slug, 'service_type' );
        return $term && ! is_wp_error( $term ) && (int) $term->parent === 0;
    } );

    $grouped_services = [];
    foreach ( $parent_service_slugs as $parent_slug ) {
        $parent_term = get_term_by( 'slug', $parent_slug, 'service_type' );
        if ( ! $parent_term || is_wp_error( $parent_term ) ) continue;

        $parent_post = null;
        $by_slug = get_posts( [ 'post_type' => 'services', 'name' => $parent_slug, 'posts_per_page' => 1, 'post_status' => 'publish' ] );
        if ( ! empty( $by_slug ) ) {
            $parent_post = $by_slug[0];
        } else {
            $by_tax = get_posts( [ 'post_type' => 'services', 'post_status' => 'publish', 'posts_per_page' => 1, 'tax_query' => [ [ 'taxonomy' => 'service_type', 'field' => 'term_id', 'terms' => $parent_term->term_id, 'include_children' => false ] ] ] );
            if ( ! empty( $by_tax ) ) $parent_post = $by_tax[0];
        }

        $parent_icon = '';
        $parent_desc = '';
        if ( $parent_post && function_exists( 'get_field' ) ) {
            $raw_icon = get_field( 'service_icon', $parent_post->ID );
            if ( is_array( $raw_icon ) && ! empty( $raw_icon['url'] ) ) { $parent_icon = $raw_icon['url']; }
            elseif ( is_string( $raw_icon ) && ! empty( $raw_icon ) ) { $parent_icon = $raw_icon; }
            $parent_desc = get_the_excerpt( $parent_post->ID );
            if ( ! $parent_desc ) $parent_desc = wp_trim_words( get_post_field( 'post_content', $parent_post->ID ), 16, '...' );
        }

        $child_slugs = function_exists( 'mvc_de_get_direct_child_service_slugs' ) ? mvc_de_get_direct_child_service_slugs( $parent_slug ) : [];
        if ( ! is_array( $child_slugs ) ) $child_slugs = [];

        $children = [];
        foreach ( $child_slugs as $child_slug ) {
            $child_term = get_term_by( 'slug', $child_slug, 'service_type' );
            if ( ! $child_term || is_wp_error( $child_term ) ) continue;
            $child_post = null;
            $child_by_slug = get_posts( [ 'post_type' => 'services', 'name' => $child_slug, 'posts_per_page' => 1, 'post_status' => 'publish' ] );
            if ( ! empty( $child_by_slug ) ) { $child_post = $child_by_slug[0]; }
            else {
                $child_by_tax = get_posts( [ 'post_type' => 'services', 'post_status' => 'publish', 'posts_per_page' => 1, 'tax_query' => [ [ 'taxonomy' => 'service_type', 'field' => 'term_id', 'terms' => $child_term->term_id, 'include_children' => false ] ] ] );
                if ( ! empty( $child_by_tax ) ) $child_post = $child_by_tax[0];
            }
            $children[] = [ 'term' => $child_term, 'post' => $child_post, 'url' => $child_post ? get_permalink( $child_post->ID ) : '#' ];
        }

        $grouped_services[] = [ 'slug' => $parent_slug, 'term' => $parent_term, 'post' => $parent_post, 'url' => $parent_post ? get_permalink( $parent_post->ID ) : '#', 'icon' => $parent_icon, 'desc' => $parent_desc, 'children' => $children ];
    }
    ?>

    <?php if ( ! empty( $grouped_services ) ) : ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode( [ '@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => $industry_name . ' Services Offered', 'description' => $industry_name . ' services available in Greater Los Angeles', 'numberOfItems' => count( $grouped_services ), 'itemListElement' => array_map( function( $grp, $i ) { return [ '@type' => 'ListItem', 'position' => $i + 1, 'name' => $grp['term']->name, 'url' => $grp['url'] ]; }, $grouped_services, array_keys( $grouped_services ) ) ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
    </script>

    <section class="lsb-svc-section lsb-light-bg" id="lsb-services-grid">
        <div class="lsb-svc-inner">
            <div class="lsb-svc-header">
                <span class="lsb-section-label">What We Offer</span>
                <h2 class="lsb-h2"><?php echo esc_html( $industry_name ); ?> Services Offered</h2>
                <?php $svc_intro = function_exists( 'get_field' ) ? get_field( 'industry_services_intro', $industry_post_id ) : ''; if ( $svc_intro ) : ?>
                <p class="lsb-svc-intro"><?php echo wp_kses_post( $svc_intro ); ?></p>
                <?php endif; ?>
            </div>
            <div class="lsb-svc-grid">
                <?php foreach ( $grouped_services as $grp ) : ?>
                <div class="lsb-svc-card lsb-svc-card--grouped">
                    <div class="lsb-svc-parent">
                        <div class="lsb-svc-parent-top">
                            <div class="lsb-svc-card-icon">
                                <?php if ( $grp['icon'] ) : ?><img src="<?php echo esc_url( $grp['icon'] ); ?>" alt="<?php echo esc_attr( $grp['term']->name ); ?>"><?php else : ?>🔧<?php endif; ?>
                            </div>
                            <a href="<?php echo esc_url( $grp['url'] ); ?>" class="lsb-svc-parent-name"><?php echo esc_html( $grp['term']->name ); ?></a>
                        </div>
                        <?php if ( $grp['desc'] ) : ?><p class="lsb-svc-parent-desc"><?php echo esc_html( $grp['desc'] ); ?></p><?php endif; ?>
                    </div>
                    <?php if ( ! empty( $grp['children'] ) ) : ?>
                    <ul class="lsb-svc-children">
                        <?php foreach ( $grp['children'] as $child ) : ?>
                        <li class="lsb-svc-child"><a href="<?php echo esc_url( $child['url'] ); ?>" class="lsb-svc-child-link"><span class="lsb-svc-child-arrow">→</span><?php echo esc_html( $child['term']->name ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $grp['url'] ); ?>" class="lsb-svc-card-cta">Learn More →</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <style>
    .lsb-svc-card--grouped { display:flex; flex-direction:column; gap:0; padding:0; overflow:hidden; }
    .lsb-svc-parent { padding:24px 24px 16px; border-bottom:1px solid var(--lsb-border); display:flex; flex-direction:column; gap:10px; }
    .lsb-svc-parent-top { display:flex; align-items:center; gap:14px; }
    .lsb-svc-parent-name { font-family:'Syne',sans-serif !important; font-size:1rem !important; font-weight:700 !important; color:var(--lsb-heading) !important; letter-spacing:-0.01em !important; line-height:1.3 !important; text-decoration:none !important; transition:color .2s !important; }
    .lsb-svc-parent-name:hover { color:#00A88C !important; }
    .lsb-svc-parent-desc { font-size:0.82rem !important; color:var(--lsb-body) !important; line-height:1.6 !important; font-family:'DM Sans',sans-serif !important; margin:0 !important; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .lsb-svc-children { list-style:none !important; margin:0 !important; padding:12px 24px !important; display:flex !important; flex-direction:column !important; gap:2px !important; flex:1 !important; border-bottom:1px solid var(--lsb-border) !important; }
    .lsb-svc-child { margin:0 !important; padding:0 !important; }
    .lsb-svc-child-link { display:flex !important; align-items:center !important; gap:8px !important; padding:7px 10px !important; border-radius:6px !important; text-decoration:none !important; font-size:0.875rem !important; font-family:'DM Sans',sans-serif !important; color:var(--lsb-body) !important; font-weight:400 !important; transition:background .15s,color .15s !important; }
    .lsb-svc-child-link:hover { background:rgba(0,201,167,0.07) !important; color:#00A88C !important; }
    .lsb-svc-child-arrow { color:#00C9A7 !important; font-size:0.78rem !important; flex-shrink:0 !important; transition:transform .15s !important; }
    .lsb-svc-child-link:hover .lsb-svc-child-arrow { transform:translateX(3px) !important; }
    .lsb-svc-card--grouped .lsb-svc-card-cta { padding:14px 24px !important; margin-top:0 !important; border-top:none !important; }
    </style>
    <?php endif; ?>

    <!-- ============================================================
         SECTION 4 — CITIES GRID
         CHANGE: 4 random featured cards + A-Z alphabet browser
         All city links point to /cities/{city}/{industry}/
    ============================================================ -->
    <?php if ( ! empty( $all_city_posts ) ) : ?>

    <script type="application/ld+json">
    <?php
    $city_schema_items = [];
    foreach ( $all_city_posts as $i => $cp ) {
        $city_schema_items[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'item'     => [
                '@type'   => 'Place',
                'name'    => $cp->post_title,
                'url'     => home_url( '/cities/' . $cp->post_name . '/' . $industry_slug . '/' ),
                'address' => [ '@type' => 'PostalAddress', 'addressLocality' => $cp->post_title, 'addressRegion' => 'CA', 'addressCountry' => 'US' ],
            ],
        ];
    }
    echo wp_json_encode( [ '@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Cities Offering ' . $industry_name . ' Services', 'numberOfItems' => count( $all_city_posts ), 'itemListElement' => $city_schema_items ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
    ?>
    </script>

    <section class="lsb-cities-section lsb-light-bg" id="lsb-cities-grid">
        <div class="lsb-cities-inner">

            <div class="lsb-cities-header">
                <span class="lsb-section-label">Service Area</span>
                <h2 class="lsb-h2">Cities Offering <?php echo esc_html( $industry_name ); ?> Services</h2>
                <?php $cities_intro = function_exists( 'get_field' ) ? get_field( 'industry_cities_intro', $industry_post_id ) : ''; if ( $cities_intro ) : ?>
                <p class="lsb-cities-intro"><?php echo wp_kses_post( $cities_intro ); ?></p>
                <?php endif; ?>
            </div>

            <!-- A-Z Browser -->
            <?php if ( ! empty( $cities_by_letter ) ) :
                $uid = 'lsb-az-' . $industry_slug;
                $all_letters = range( 'A', 'Z' );
                $first_letter = $available_letters[0] ?? 'A';
            ?>
            <div class="lsb-cities-az-header">
                <span class="lsb-cities-az-label">Browse All <?php echo esc_html( $industry_name ); ?> Cities</span>
            </div>
            <div class="lsb-cities-az-bar" id="<?php echo esc_attr( $uid ); ?>-bar">
                <?php foreach ( $all_letters as $letter ) :
                    $has = in_array( $letter, $available_letters, true );
                    $cls = $has ? ( $letter === $first_letter ? 'lsb-cities-az-btn is-active' : 'lsb-cities-az-btn' ) : 'lsb-cities-az-btn is-disabled';
                ?>
                <button class="<?php echo esc_attr( $cls ); ?>"
                        type="button"
                        data-letter="<?php echo esc_attr( $letter ); ?>"
                        data-target="<?php echo esc_attr( $uid . '-panel-' . $letter ); ?>"
                        <?php echo ! $has ? 'aria-disabled="true"' : ''; ?>>
                    <?php echo esc_html( $letter ); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ( $cities_by_letter as $letter => $letter_cities ) :
                $panel_id = $uid . '-panel-' . $letter;
            ?>
            <div class="lsb-cities-az-panel<?php echo $letter === $first_letter ? ' is-active' : ''; ?>"
                 id="<?php echo esc_attr( $panel_id ); ?>">
                <div class="lsb-cities-az-list">
                    <?php foreach ( $letter_cities as $lcp ) :
                        $lcp_url = home_url( '/cities/' . $lcp->post_name . '/' . $industry_slug . '/' );
                    ?>
                    <a href="<?php echo esc_url( $lcp_url ); ?>" class="lsb-cities-az-item">
                        <span class="lsb-cities-az-pin">📍</span>
                        <span class="lsb-cities-az-name"><?php echo esc_html( $lcp->post_title ); ?></span>
                        <span class="lsb-cities-az-arrow">→</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <script>
            (function(){
                var bar = document.getElementById('<?php echo esc_js( $uid ); ?>-bar');
                if (!bar) return;
                bar.addEventListener('click', function(e){
                    var btn = e.target.closest('.lsb-cities-az-btn');
                    if (!btn || btn.classList.contains('is-disabled')) return;
                    var target = btn.getAttribute('data-target');
                    // Deactivate all
                    bar.querySelectorAll('.lsb-cities-az-btn').forEach(function(b){ b.classList.remove('is-active'); });
                    document.querySelectorAll('[id^="<?php echo esc_js( $uid ); ?>-panel-"]').forEach(function(p){ p.classList.remove('is-active'); });
                    // Activate clicked
                    btn.classList.add('is-active');
                    var panel = document.getElementById(target);
                    if (panel) panel.classList.add('is-active');
                });
            }());
            </script>

            <?php endif; ?>

        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         SECTION 5 — FEATURED BUSINESSES
    ============================================================ -->
    <?php
    $featured_biz_query = new WP_Query( [
        'post_type'      => 'businesses',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'business_rating',
        'order'          => 'DESC',
        'tax_query'      => [ [
            'taxonomy'         => 'industry_cat',
            'field'            => 'term_id',
            'terms'            => $industry_term ? $industry_term->term_id : 0,
            'include_children' => true,
        ] ],
        'meta_query'     => [ [ 'key' => 'featured_business', 'value' => '1' ] ],
    ] );
    ?>
    <?php if ( $featured_biz_query->have_posts() ) : ?>
    <?php
    $fb_schema_items = [];
    $fb_position     = 1;
    $fb_posts        = $featured_biz_query->posts;
    wp_reset_postdata();

    foreach ( $fb_posts as $biz ) :
        $biz_id   = $biz->ID;
        $logo_id  = function_exists( 'get_field' ) ? get_field( 'business_logo',  $biz_id ) : '';
        $rating   = function_exists( 'get_field' ) ? get_field( 'business_rating', $biz_id ) : '';
        $phone    = function_exists( 'get_field' ) ? get_field( 'business_phone',  $biz_id ) : '';
        $logo_url = '';
        if ( $logo_id ) { $logo_url = is_array( $logo_id ) ? ( $logo_id['url'] ?? '' ) : wp_get_attachment_image_url( $logo_id, 'thumbnail' ); }
        if ( ! $logo_url ) $logo_url = get_the_post_thumbnail_url( $biz_id, 'thumbnail' );
        $fb_schema_items[] = [ '@type' => 'ListItem', 'position' => $fb_position++, 'item' => array_filter( [ '@type' => 'LocalBusiness', 'name' => $biz->post_title, 'url' => get_permalink( $biz_id ), 'telephone' => $phone ?: null, 'image' => $logo_url ?: null, 'aggregateRating' => $rating ? [ '@type' => 'AggregateRating', 'ratingValue' => $rating, 'bestRating' => '5' ] : null ] ) ];
    endforeach;
    ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode( [ '@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Featured ' . $industry_name . ' Businesses', 'itemListElement' => $fb_schema_items ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
    </script>

    <section class="lsb-fb-section lsb-light-bg" id="lsb-featured-businesses">
        <div class="lsb-fb-inner">
            <div class="lsb-fb-header">
                <span class="lsb-section-label">Featured Professionals</span>
                <h2 class="lsb-h2">Featured <?php echo esc_html( $industry_name ); ?> Businesses</h2>
                <?php $fb_intro = function_exists( 'get_field' ) ? get_field( 'industry_featured_intro', $industry_post_id ) : ''; if ( $fb_intro ) : ?>
                <p class="lsb-fb-intro"><?php echo wp_kses_post( $fb_intro ); ?></p>
                <?php endif; ?>
            </div>
            <div class="lsb-fb-cards">
                <?php foreach ( $fb_posts as $biz ) :
                    $biz_id     = $biz->ID;
                    $biz_title  = $biz->post_title;
                    $logo_id    = function_exists( 'get_field' ) ? get_field( 'business_logo',              $biz_id ) : '';
                    $short_desc = function_exists( 'get_field' ) ? get_field( 'business_short_description', $biz_id ) : '';
                    $rating     = function_exists( 'get_field' ) ? get_field( 'business_rating',            $biz_id ) : '';
                    if ( ! $short_desc ) $short_desc = get_the_excerpt( $biz_id );
                    $logo_url = '';
                    if ( $logo_id ) { $logo_url = is_array( $logo_id ) ? ( $logo_id['url'] ?? '' ) : wp_get_attachment_image_url( $logo_id, 'thumbnail' ); }
                    if ( ! $logo_url ) $logo_url = get_the_post_thumbnail_url( $biz_id, 'thumbnail' );
                    $initials = '';
                    foreach ( array_slice( explode( ' ', $biz_title ), 0, 2 ) as $word ) { $initials .= strtoupper( substr( $word, 0, 1 ) ); }
                    $city_terms_all = wp_get_post_terms( $biz_id, 'city_cat' );
                    $cities_display = '';
                    if ( ! empty( $city_terms_all ) && ! is_wp_error( $city_terms_all ) ) {
                        $cities_display = implode( ' &bull; ', array_slice( wp_list_pluck( $city_terms_all, 'name' ), 0, 5 ) );
                    }
                    $biz_url    = get_permalink( $biz_id );
                    $stars_html = '';
                    if ( $rating ) {
                        $rf = floatval( $rating ); $fs = floor( $rf ); $hs = ( $rf - $fs ) >= 0.5;
                        for ( $s = 1; $s <= 5; $s++ ) {
                            if      ( $s <= $fs )             { $stars_html .= '<span class="lsb-fb-star lsb-fb-star--full">★</span>'; }
                            elseif  ( $s === $fs + 1 && $hs ) { $stars_html .= '<span class="lsb-fb-star lsb-fb-star--half">★</span>'; }
                            else                              { $stars_html .= '<span class="lsb-fb-star lsb-fb-star--empty">☆</span>'; }
                        }
                    }
                ?>
                <article class="lsb-fb-card">
                    <?php if ( $logo_url ) : ?>
                    <div class="lsb-fb-logo"><img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $biz_title ); ?> logo" loading="lazy" width="72" height="72"></div>
                    <?php else : ?>
                    <div class="lsb-fb-logo lsb-fb-logo--initials" aria-hidden="true"><?php echo esc_html( $initials ); ?></div>
                    <?php endif; ?>
                    <div class="lsb-fb-body">
                        <div class="lsb-fb-meta-row">
                            <h3 class="lsb-fb-name"><?php echo esc_html( $biz_title ); ?></h3>
                            <?php if ( $rating ) : ?>
                            <div class="lsb-fb-rating" aria-label="Rating: <?php echo esc_attr( $rating ); ?> out of 5">
                                <span class="lsb-fb-stars"><?php echo $stars_html; ?></span>
                                <span class="lsb-fb-rating-num"><?php echo esc_html( number_format( (float) $rating, 1 ) ); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if ( $short_desc ) : ?><p class="lsb-fb-desc"><?php echo esc_html( wp_trim_words( $short_desc, 25, '…' ) ); ?></p><?php endif; ?>
                        <?php if ( $cities_display ) : ?>
                        <p class="lsb-fb-cities"><span class="lsb-fb-cities-label">Serves:</span><?php echo wp_kses_post( $cities_display ); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="lsb-fb-cta"><a href="<?php echo esc_url( $biz_url ); ?>" class="lsb-fb-btn" aria-label="View <?php echo esc_attr( $biz_title ); ?> profile">View Business</a></div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         SECTION 6 — COMMON PROBLEMS
    ============================================================ -->
    <?php
    $problems_intro  = function_exists( 'get_field' ) ? get_field( 'industry_problems_intro', $industry_post_id ) : '';
    $common_problems = function_exists( 'get_field' ) ? get_field( 'common_problems', $industry_post_id ) : [];
    ?>
    <?php if ( ! empty( $common_problems ) ) :
        $cp_resolved = [];
        $faq_schema  = [];
        foreach ( $common_problems as $problem ) {
            $p_title = $problem['problem_title']       ?? '';
            $p_desc  = $problem['problem_description'] ?? '';
            $p_term  = $problem['related_service']     ?? null;
            if ( ! $p_title ) continue;
            $svc_url = '#lsb-services-grid';
            if ( $p_term && ! is_wp_error( $p_term ) ) {

                // Normalize — ACF taxonomy field can return object, array, term_id integer,
                // or (as seen in debug) a numerically-indexed array of WP_Term objects
                $svc_type_term = null;
                if ( is_array( $p_term ) && isset( $p_term[0] ) && is_object( $p_term[0] ) ) {
                    // ACF returning [ WP_Term ] — most common case
                    $svc_type_term = $p_term[0];
                } elseif ( is_object( $p_term ) && isset( $p_term->term_id ) ) {
                    $svc_type_term = $p_term;
                } elseif ( is_numeric( $p_term ) ) {
                    $svc_type_term = get_term( (int) $p_term, 'service_type' );
                } elseif ( is_array( $p_term ) && ! empty( $p_term['term_id'] ) ) {
                    $svc_type_term = get_term( (int) $p_term['term_id'], 'service_type' );
                }

                if ( $svc_type_term && ! is_wp_error( $svc_type_term ) ) {
                    $svc_match = get_posts( [
                        'post_type'      => 'services',
                        'post_status'    => 'publish',
                        'posts_per_page' => 1,
                        'tax_query'      => [ [
                            'taxonomy'         => 'service_type',
                            'field'            => 'term_id',
                            'terms'            => $svc_type_term->term_id,
                            'include_children' => false,
                        ] ],
                    ] );

                    // 2nd try: also check child terms (some services may be tagged to child)
                    if ( empty( $svc_match ) ) {
                        $svc_match = get_posts( [
                            'post_type'      => 'services',
                            'post_status'    => 'publish',
                            'posts_per_page' => 1,
                            'tax_query'      => [ [
                                'taxonomy'         => 'service_type',
                                'field'            => 'term_id',
                                'terms'            => $svc_type_term->term_id,
                                'include_children' => true,
                            ] ],
                        ] );
                    }

                    // 3rd try: match post slug to term slug
                    if ( empty( $svc_match ) ) {
                        $svc_match = get_posts( [
                            'post_type'      => 'services',
                            'name'           => $svc_type_term->slug,
                            'posts_per_page' => 1,
                            'post_status'    => 'publish',
                        ] );
                    }

                    // 4th try: match post slug to term name (slugified)
                    if ( empty( $svc_match ) ) {
                        $svc_match = get_posts( [
                            'post_type'      => 'services',
                            'name'           => sanitize_title( $svc_type_term->name ),
                            'posts_per_page' => 1,
                            'post_status'    => 'publish',
                        ] );
                    }

                    if ( ! empty( $svc_match ) ) {
                        $svc_url = get_permalink( $svc_match[0]->ID );
                    } else {
                        $svc_url = '#lsb-services-grid';
                    }
                }
            }
            $cp_resolved[] = [ 'title' => $p_title, 'desc' => $p_desc, 'svc_url' => $svc_url ];
            $faq_schema[]  = [ '@type' => 'Question', 'name' => $p_title . ' — Causes, Signs, and Solutions', 'acceptedAnswer' => [ '@type' => 'Answer', 'text' => wp_strip_all_tags( $p_desc ) ] ];
        }
    ?>
    <?php if ( ! empty( $cp_resolved ) ) : ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode( [ '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faq_schema ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
    </script>
    <section class="lsb-cp-section lsb-light-bg" id="lsb-common-problems">
        <div class="lsb-cp-inner">
            <div class="lsb-cp-header">
                <span class="lsb-section-label">Diagnose the Issue</span>
                <h2 class="lsb-h2">Common <?php echo esc_html( $industry_name ); ?> Problems</h2>
                <?php if ( $problems_intro ) : ?><p class="lsb-cp-intro"><?php echo wp_kses_post( $problems_intro ); ?></p><?php endif; ?>
            </div>
            <div class="lsb-cp-grid">
                <?php foreach ( $cp_resolved as $cp ) : ?>
                <article class="lsb-cp-card">
                    <h3 class="lsb-cp-heading"><?php echo esc_html( $cp['title'] ); ?> &mdash; Causes, Signs, and Solutions</h3>
                    <?php if ( $cp['desc'] ) : ?><p class="lsb-cp-desc"><?php echo wp_kses_post( $cp['desc'] ); ?></p><?php endif; ?>
                    <a href="<?php echo esc_url( $cp['svc_url'] ); ?>" class="lsb-cp-btn">Find Help</a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>

    <!-- ============================================================
         SECTION 7 — WHY HIRE A PROFESSIONAL
    ============================================================ -->
    <?php
    $why_hire_content   = function_exists( 'get_field' ) ? get_field( 'why_hire_a_pro',    $industry_post_id ) : '';
    $why_hire_checklist = function_exists( 'get_field' ) ? get_field( 'why_hire_checklist', $industry_post_id ) : [];
    ?>
    <?php if ( $why_hire_content || ! empty( $why_hire_checklist ) ) : ?>
    <section class="lsb-whp-section lsb-light-bg" id="lsb-why-hire-pro">
        <div class="lsb-whp-inner">
            <div class="lsb-whp-header">
                <span class="lsb-section-label">Professional Advantage</span>
                <h2 class="lsb-h2">Why Hire a Professional for <?php echo esc_html( $industry_name ); ?> Services</h2>
            </div>
            <div class="lsb-whp-columns">
                <?php if ( $why_hire_content ) : ?>
                <div class="lsb-whp-content"><?php echo wp_kses_post( $why_hire_content ); ?></div>
                <?php endif; ?>
                <?php if ( ! empty( $why_hire_checklist ) ) : ?>
                <div class="lsb-whp-checklist" role="list" aria-label="Why hire a professional checklist">
                    <p class="lsb-whp-checklist-title">Key Reasons</p>
                    <?php foreach ( $why_hire_checklist as $item ) :
                        $check_text = $item['checklist_text'] ?? '';
                        $check_icon = $item['checklist_icon'] ?? null;
                        if ( ! $check_text ) continue;
                        $icon_url = '';
                        if ( $check_icon ) { $icon_url = is_array( $check_icon ) ? ( $check_icon['url'] ?? '' ) : wp_get_attachment_image_url( $check_icon, 'thumbnail' ); }
                    ?>
                    <div class="lsb-whp-checklist-item" role="listitem">
                        <div class="lsb-whp-check-icon" aria-hidden="true">
                            <?php if ( $icon_url ) : ?><img src="<?php echo esc_url( $icon_url ); ?>" alt="" width="16" height="16"><?php else : ?><span class="lsb-whp-check-icon-default">✓</span><?php endif; ?>
                        </div>
                        <p class="lsb-whp-check-text"><?php echo wp_kses_post( $check_text ); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         SECTION 8 — FAQ
         FIX: Replaced $tax_q[0] reuse with explicit term_id query
         to resolve "Undefined array key slug" error on line 2229
    ============================================================ -->
    <?php
    $daily_seed = (int) date( 'Ymd' ) + crc32( $industry_slug );

    // FIXED: explicit tax_query using term_id instead of reusing $tax_q[0]
    $faq_query = new WP_Query( [
        'post_type'      => 'faqs',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'none',
        'tax_query'      => [ [
            'taxonomy'         => 'industry_cat',
            'field'            => 'term_id',
            'terms'            => $industry_term ? $industry_term->term_id : 0,
            'include_children' => true,
        ] ],
    ] );
    ?>
    <?php if ( $faq_query->have_posts() ) : ?>
    <?php
    $faq_all  = $faq_query->posts;
    $faq_sch  = [];
    wp_reset_postdata();

    usort( $faq_all, function( $a, $b ) use ( $daily_seed ) {
        return crc32( $daily_seed . $a->ID ) - crc32( $daily_seed . $b->ID );
    } );
    $faq_posts = array_slice( $faq_all, 0, 7 );

    $disclaimer = function_exists( 'get_field' ) ? get_field( 'faq_source_note_disclaimer', $industry_post_id ) : '';

    foreach ( $faq_posts as $faq ) {
        $short_answer = function_exists( 'get_field' ) ? get_field( 'faq_short_answer', $faq->ID ) : '';
        if ( ! $short_answer ) continue;
        $faq_sch[] = [ '@type' => 'Question', 'name' => $faq->post_title, 'acceptedAnswer' => [ '@type' => 'Answer', 'text' => wp_strip_all_tags( $short_answer ) ] ];
    }
    ?>
    <?php if ( ! empty( $faq_sch ) ) : ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode( [ '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faq_sch ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
    </script>
    <?php endif; ?>

    <section class="lsb-faq-section lsb-light-bg" id="lsb-faq">
        <div class="lsb-faq-inner">
            <div class="lsb-faq-header">
                <span class="lsb-section-label">Got Questions?</span>
                <h2 class="lsb-h2">Frequently Asked Questions About <?php echo esc_html( $industry_name ); ?></h2>
            </div>
            <div class="lsb-faq-list">
                <?php foreach ( $faq_posts as $i => $faq ) :
                    $short_answer = function_exists( 'get_field' ) ? get_field( 'faq_short_answer', $faq->ID ) : '';
                    $faq_url      = get_permalink( $faq->ID );
                    $faq_item_id  = 'lsb-faq-item-' . $industry_slug . '-' . $i;
                    $faq_body_id  = 'lsb-faq-body-' . $industry_slug . '-' . $i;
                    if ( ! $short_answer ) continue;
                ?>
                <div class="lsb-faq-item" id="<?php echo esc_attr( $faq_item_id ); ?>">
                    <button class="lsb-faq-trigger" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $faq_body_id ); ?>">
                        <span class="lsb-faq-question"><?php echo esc_html( $faq->post_title ); ?></span>
                        <span class="lsb-faq-icon" aria-hidden="true">+</span>
                    </button>
                    <div class="lsb-faq-body" id="<?php echo esc_attr( $faq_body_id ); ?>" role="region" aria-labelledby="<?php echo esc_attr( $faq_item_id ); ?>">
                        <div class="lsb-faq-answer"><?php echo wp_kses_post( $short_answer ); ?></div>
                        <div class="lsb-faq-cta-row">
                            <a href="<?php echo esc_url( $faq_url ); ?>" class="lsb-faq-learn-more">Learn More</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ( $disclaimer ) : ?>
            <p class="lsb-faq-disclaimer" style="margin-top:24px;text-align:center;"><?php echo esc_html( $disclaimer ); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <script>
    (function(){
        var faqItems = document.querySelectorAll('#lsb-faq .lsb-faq-item');
        faqItems.forEach(function(item){
            var trigger = item.querySelector('.lsb-faq-trigger');
            if (!trigger) return;
            trigger.addEventListener('click', function(){
                var isOpen = item.classList.contains('is-open');
                faqItems.forEach(function(el){ el.classList.remove('is-open'); el.querySelector('.lsb-faq-trigger').setAttribute('aria-expanded','false'); });
                if (!isOpen){ item.classList.add('is-open'); trigger.setAttribute('aria-expanded','true'); }
            });
        });
    }());
    </script>
    <?php endif; ?>

    <!-- ============================================================
         SECTION 9 — FINAL CTA
    ============================================================ -->
    <?php
    $cta_heading     = function_exists( 'get_field' ) ? get_field( 'industry_cta_heading',     $industry_post_id ) : '';
    $cta_text        = function_exists( 'get_field' ) ? get_field( 'industry_cta_text',        $industry_post_id ) : '';
    $cta_button_text = function_exists( 'get_field' ) ? get_field( 'industry_cta_button_text', $industry_post_id ) : '';
    $cta_button_link = function_exists( 'get_field' ) ? get_field( 'industry_cta_button_link', $industry_post_id ) : '';
    if ( ! $cta_heading )     $cta_heading     = 'Ready to Find a Trusted ' . $industry_name . ' Professional?';
    if ( ! $cta_text )        $cta_text        = 'Connect with verified local ' . $industry_name . ' experts serving your area. Browse profiles, compare services, and reach out directly — no middleman, no fees.';
    if ( ! $cta_button_text ) $cta_button_text = 'Find a Professional';
    if ( ! $cta_button_link ) $cta_button_link = home_url( '/businesses/' . $industry_slug . '/' );
    ?>
    <section class="lsb-cta-section lsb-dark-bg" id="lsb-final-cta">
        <div class="lsb-cta-grid-bg" aria-hidden="true"></div>
        <div class="lsb-cta-glow-1"  aria-hidden="true"></div>
        <div class="lsb-cta-glow-2"  aria-hidden="true"></div>
        <div class="lsb-cta-inner">
            <span class="lsb-section-label">Get Started Today</span>
            <h2 class="lsb-cta-heading"><?php echo wp_kses_post( $cta_heading ); ?></h2>
            <?php if ( $cta_text ) : ?><div class="lsb-cta-text"><?php echo wp_kses_post( $cta_text ); ?></div><?php endif; ?>
            <a href="<?php echo esc_url( $cta_button_link ); ?>" class="lsb-cta-btn"><?php echo esc_html( $cta_button_text ); ?></a>
        </div>
    </section>

    <!-- DROPDOWN JS -->
    <script>
    (function(){
        var slug = <?php echo wp_json_encode( $industry_slug ); ?>;
        var ddIds = ['lsb-dd-services-'+slug,'lsb-dd-cities-'+slug];
        ddIds.forEach(function(id){
            var wrapper = document.getElementById(id);
            if (!wrapper) return;
            var btn = wrapper.querySelector('.lsb-ih-dropdown-btn');
            var menu = wrapper.querySelector('.lsb-ih-dropdown-menu');
            if (!btn||!menu) return;
            btn.addEventListener('click',function(e){
                e.stopPropagation();
                var isOpen = wrapper.classList.contains('is-open');
                document.querySelectorAll('.lsb-ih-dropdown.is-open').forEach(function(el){ el.classList.remove('is-open'); el.querySelector('.lsb-ih-dropdown-btn').setAttribute('aria-expanded','false'); });
                if (!isOpen){ wrapper.classList.add('is-open'); btn.setAttribute('aria-expanded','true'); }
            });
        });
        document.addEventListener('click',function(){ document.querySelectorAll('.lsb-ih-dropdown.is-open').forEach(function(el){ el.classList.remove('is-open'); el.querySelector('.lsb-ih-dropdown-btn').setAttribute('aria-expanded','false'); }); });
        document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ document.querySelectorAll('.lsb-ih-dropdown.is-open').forEach(function(el){ el.classList.remove('is-open'); el.querySelector('.lsb-ih-dropdown-btn').setAttribute('aria-expanded','false'); }); } });
    }());
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode( 'industry_page', 'lsb_industry_page_shortcode' );

endif;