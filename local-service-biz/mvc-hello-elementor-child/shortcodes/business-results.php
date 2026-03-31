<?php
/**
 * Shortcode: [business_results]
 *
 * File: /wp-content/themes/mvc-hello-elementor-child/shortcodes/business-results.php
 * Register in functions.php:
 *   require_once get_stylesheet_directory() . '/shortcodes/business-results.php';
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function lsb_business_results_shortcode( $atts ) {

    $atts = shortcode_atts( array(
        'industry' => '',
    ), $atts, 'business_results' );

    // ── 1. Detect industry slug ───────────────────────────────────────────────
    $industry_slug = '';

    if ( ! empty( $atts['industry'] ) ) {
        $industry_slug = sanitize_title( $atts['industry'] );
    }
    if ( empty( $industry_slug ) ) {
        $industry_slug = get_query_var( 'industry_slug', '' );
    }
    if ( empty( $industry_slug ) ) {
        $uri       = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
        $uri_parts = array_values( array_filter( explode( '/', $uri ) ) );
        if ( isset( $uri_parts[0], $uri_parts[1] ) && $uri_parts[0] === 'businesses' ) {
            $industry_slug = sanitize_title( $uri_parts[1] );
        }
    }

    // ── 2. Load industry CPT data ─────────────────────────────────────────────
    $industry_post        = null;
    $industry_name        = '';
    $industry_description = '';

    if ( ! empty( $industry_slug ) ) {
        $industry_posts = get_posts( array(
            'post_type'      => 'industries',
            'name'           => $industry_slug,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ) );
        if ( ! empty( $industry_posts ) ) {
            $industry_post        = $industry_posts[0];
            $industry_name        = get_the_title( $industry_post );
            $industry_description = get_field( 'industry_description', $industry_post->ID );
        }
    }

    if ( empty( $industry_name ) ) {
        $industry_name = ucwords( str_replace( '-', ' ', $industry_slug ) );
    }

    // ── 3. Filters ────────────────────────────────────────────────────────────
    $selected_city    = isset( $_GET['city'] )    ? sanitize_title( $_GET['city'] )    : '';
    $selected_service = isset( $_GET['service'] ) ? sanitize_title( $_GET['service'] ) : '';
    $selected_sort    = isset( $_GET['sort'] )    ? sanitize_key( $_GET['sort'] )      : 'rating';

    // ── 4. Tax query ──────────────────────────────────────────────────────────
    $tax_query = array( 'relation' => 'AND' );

    if ( ! empty( $industry_slug ) ) {
        $tax_query[] = array(
            'taxonomy'         => 'industry_cat',
            'field'            => 'slug',
            'terms'            => $industry_slug,
            'include_children' => true,
        );
    }
    if ( ! empty( $selected_city ) ) {
        $tax_query[] = array(
            'taxonomy'         => 'city_cat',
            'field'            => 'slug',
            'terms'            => $selected_city,
            'include_children' => true,
        );
    }
    if ( ! empty( $selected_service ) ) {
        $tax_query[] = array(
            'taxonomy'         => 'service_type',
            'field'            => 'slug',
            'terms'            => $selected_service,
            'include_children' => true,
        );
    }

    // ── 5. Query all matching businesses ─────────────────────────────────────
    $q = new WP_Query( array(
        'post_type'      => 'businesses',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'tax_query'      => $tax_query,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );

    $total_found = $q->found_posts;

    // ── 6. Split: featured cards vs plain list ────────────────────────────────
    // All plan logic is inline — no helper functions to avoid redeclaration errors
    $featured_posts = array();
    $list_posts     = array();

    if ( $q->have_posts() ) {
        while ( $q->have_posts() ) {
            $q->the_post();
            $biz_id = get_the_ID();

            $plan        = get_field( 'plan_tier', $biz_id );
            $plan_active = get_field( 'plan_active', $biz_id );
            $plan_expiry = get_field( 'plan_expiry', $biz_id );

            // Treat missing, inactive, or expired as free
            $is_paid = ! empty( $plan )
                && $plan_active
                && ( empty( $plan_expiry ) || strtotime( $plan_expiry ) >= time() )
                && in_array( $plan, array( 'premium', 'basic' ), true );

            if ( $is_paid ) {
                $featured_posts[] = $biz_id;
            } else {
                $list_posts[] = $biz_id;
            }
        }
        wp_reset_postdata();
    }

    // ── 7. Dropdown options ───────────────────────────────────────────────────
    $cities_list = get_terms( array(
        'taxonomy'   => 'city_cat',
        'hide_empty' => true,
        'orderby'    => 'name',
    ) );

    $services_list = array();
    if ( $industry_post ) {
        $raw = get_field( 'service_type', $industry_post->ID );
        if ( ! empty( $raw ) && is_array( $raw ) ) {
            $services_list = $raw;
        }
    }
    if ( empty( $services_list ) ) {
        $services_list = get_terms( array(
            'taxonomy'   => 'service_type',
            'hide_empty' => true,
            'orderby'    => 'name',
        ) );
    }

    $base_url = home_url( '/businesses/' . $industry_slug . '/' );

    // ── 8. Render ─────────────────────────────────────────────────────────────
    ob_start();
    ?>

    <style>
    :root {
      --lsb-navy:      #0D1B2A;
      --lsb-navy-mid:  #1B2F45;
      --lsb-teal:      #00C9A7;
      --lsb-teal-dark: #00A88C;
      --lsb-gold:      #F4C542;
      --lsb-white:     #FFFFFF;
      --lsb-off-white: #F5F7FA;
      --lsb-muted:     #8A9BB0;
      --lsb-text:      #1A2535;
      --lsb-border:    #E4EAF2;
    }

    .lsb-br-hero {
      background: var(--lsb-navy); padding: 48px 40px 52px;
      position: relative; overflow: hidden; border-radius: 16px; margin-bottom: 36px;
    }
    .lsb-br-hero-grid {
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(0,201,167,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,201,167,0.04) 1px, transparent 1px);
      background-size: 60px 60px; pointer-events: none; border-radius: 16px;
    }
    .lsb-br-hero-glow {
      position: absolute; width: 400px; height: 250px;
      background: radial-gradient(ellipse, rgba(0,201,167,0.1) 0%, transparent 70%);
      right: -60px; top: -30px; pointer-events: none;
    }
    .lsb-br-hero-inner { position: relative; z-index: 1; }

    .lsb-br-breadcrumb {
      display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
      font-size: 0.78rem; color: rgba(255,255,255,0.4); flex-wrap: wrap;
    }
    .lsb-br-breadcrumb a { color: rgba(255,255,255,0.4); text-decoration: none; transition: color 0.2s; }
    .lsb-br-breadcrumb a:hover { color: var(--lsb-teal); }
    .lsb-br-breadcrumb .sep { color: rgba(255,255,255,0.2); }

    .lsb-br-hero h1 {
      font-family: 'Syne', sans-serif !important; font-size: clamp(1.6rem, 3.5vw, 2.4rem) !important;
      font-weight: 800 !important; color: var(--lsb-white) !important;
      letter-spacing: -0.03em !important; line-height: 1.1 !important; margin-bottom: 8px !important;
    }
    .lsb-br-hero h1 em { font-style: normal !important; color: var(--lsb-teal) !important; }

    .lsb-br-hero-sub {
      font-size: 0.92rem; color: rgba(255,255,255,0.45);
      margin-bottom: 28px; font-weight: 300; line-height: 1.6;
    }

    .lsb-br-filter-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

    .lsb-br-select {
      background: rgba(255,255,255,0.07) !important; border: 1px solid rgba(255,255,255,0.12) !important;
      border-radius: 8px !important; color: var(--lsb-white) !important;
      font-family: 'DM Sans', sans-serif !important; font-size: 0.88rem !important;
      padding: 10px 34px 10px 14px !important; outline: none !important;
      cursor: pointer !important; appearance: none !important; -webkit-appearance: none !important;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2300C9A7' stroke-width='2' fill='none'/%3E%3C/svg%3E") !important;
      background-repeat: no-repeat !important; background-position: right 10px center !important; min-width: 160px !important;
    }
    .lsb-br-select:focus { border-color: var(--lsb-teal) !important; }
    .lsb-br-select option { background: var(--lsb-navy-mid); color: var(--lsb-white); }

    .lsb-br-filter-btn {
      background: var(--lsb-teal) !important; color: var(--lsb-navy) !important;
      font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
      font-size: 0.85rem !important; padding: 10px 22px !important;
      border: none !important; border-radius: 8px !important; cursor: pointer !important; white-space: nowrap !important;
    }
    .lsb-br-filter-btn:hover { background: var(--lsb-teal-dark) !important; }

    .lsb-br-result-count {
      font-size: 0.8rem; color: rgba(255,255,255,0.35); white-space: nowrap; margin-left: auto;
    }

    .lsb-br-section-label {
      font-family: 'Syne', sans-serif; font-size: 0.72rem; font-weight: 600;
      letter-spacing: 0.1em; text-transform: uppercase; color: var(--lsb-teal);
      margin-bottom: 16px; display: block;
    }

    .lsb-br-featured-section { margin-bottom: 48px; }
    .lsb-br-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    @media (max-width: 768px) { .lsb-br-grid { grid-template-columns: 1fr; } }

    .lsb-br-card {
      background: var(--lsb-white); border: 1px solid var(--lsb-border); border-radius: 16px;
      padding: 24px; transition: all 0.25s; display: flex; flex-direction: column;
      position: relative; overflow: hidden;
    }
    .lsb-br-card::after {
      content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
      background: linear-gradient(90deg, var(--lsb-teal), var(--lsb-teal-dark));
      transform: scaleX(0); transform-origin: left; transition: transform 0.3s ease;
    }
    .lsb-br-card:hover { border-color: rgba(0,201,167,0.3) !important; box-shadow: 0 12px 36px rgba(0,0,0,0.08) !important; transform: translateY(-4px) !important; }
    .lsb-br-card:hover::after { transform: scaleX(1); }
    .lsb-br-card.is-premium { border-color: rgba(244,197,66,0.4) !important; background: linear-gradient(135deg, #fffdf5 0%, var(--lsb-white) 60%) !important; }
    .lsb-br-card.is-premium::after { background: linear-gradient(90deg, var(--lsb-gold), #e8a800); }
    .lsb-br-card.is-basic { border-color: rgba(0,201,167,0.25) !important; }

    .lsb-br-tier-badge {
      position: absolute; top: 14px; right: 14px; font-family: 'Syne', sans-serif;
      font-weight: 700; font-size: 0.65rem; letter-spacing: 0.08em;
      text-transform: uppercase; padding: 3px 10px; border-radius: 100px;
    }
    .lsb-br-tier-badge.premium { background: var(--lsb-gold); color: var(--lsb-navy); }
    .lsb-br-tier-badge.basic   { background: rgba(0,201,167,0.15); color: var(--lsb-teal-dark); border: 1px solid rgba(0,201,167,0.3); }

    .lsb-br-card-top { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }

    .lsb-br-logo {
      width: 56px; height: 56px; border-radius: 11px; background: var(--lsb-navy);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: 0.95rem;
      color: var(--lsb-teal); flex-shrink: 0; overflow: hidden;
    }
    .lsb-br-logo img { width: 100%; height: 100%; object-fit: cover; border-radius: 11px; }

    .lsb-br-card-info { flex: 1; min-width: 0; }
    .lsb-br-card-niche { font-size: 0.72rem; color: var(--lsb-teal-dark); font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 3px; }
    .lsb-br-card-name {
      font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
      font-size: 1rem !important; color: var(--lsb-navy) !important;
      letter-spacing: -0.01em !important; margin-bottom: 4px !important;
      white-space: normal; overflow: visible; word-break: break-word;
    }
    .lsb-br-card-tagline {
      font-size: 0.82rem; color: var(--lsb-muted); font-weight: 300;
      line-height: 1.4; margin-bottom: 6px; font-style: italic;
    }
    .lsb-br-card-meta {
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
      font-size: 0.8rem; color: var(--lsb-muted);
    }
    .lsb-br-card-meta .meta-phone { color: var(--lsb-text); font-weight: 500; }
    .lsb-br-card-meta .meta-city  { color: var(--lsb-teal-dark); font-weight: 500; }
    .lsb-br-card-meta .meta-sep   { color: var(--lsb-border); }

    .lsb-br-rating {
      display: flex; align-items: center; gap: 3px; font-size: 0.82rem;
      color: var(--lsb-gold); font-weight: 700; white-space: nowrap; flex-shrink: 0;
    }
    .lsb-br-rating-count { font-size: 0.72rem; color: var(--lsb-muted); font-weight: 400; }

    .lsb-br-desc {
      font-size: 0.84rem; color: var(--lsb-muted) !important; line-height: 1.65;
      margin-bottom: 12px; font-weight: 300; flex: 1;
      display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    }

    .lsb-br-cities { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px; }
    .lsb-br-city-pill {
      background: rgba(13,27,42,0.06); border: 1px solid rgba(13,27,42,0.1);
      color: var(--lsb-text); font-size: 0.68rem; padding: 2px 8px;
      border-radius: 100px; font-weight: 500; white-space: nowrap;
    }
    .lsb-br-city-pill.is-primary {
      background: rgba(0,201,167,0.1); border-color: rgba(0,201,167,0.25); color: var(--lsb-teal-dark);
    }

    .lsb-br-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 14px; }
    .lsb-br-tag {
      background: rgba(0,201,167,0.07); border: 1px solid rgba(0,201,167,0.18);
      color: var(--lsb-teal-dark); font-size: 0.7rem; padding: 3px 9px;
      border-radius: 100px; font-weight: 500; white-space: nowrap;
    }

    .lsb-br-card-footer {
      display: flex; align-items: center; justify-content: flex-end;
      padding-top: 12px; border-top: 1px solid var(--lsb-border); margin-top: auto;
    }
    .lsb-br-cta {
      background: var(--lsb-navy) !important; color: var(--lsb-white) !important;
      font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
      font-size: 0.75rem !important; padding: 7px 14px !important;
      border-radius: 7px !important; text-decoration: none !important;
      white-space: nowrap !important; flex-shrink: 0 !important;
      display: inline-block !important; transition: background 0.2s !important;
    }
    .lsb-br-cta:hover { background: var(--lsb-teal) !important; color: var(--lsb-navy) !important; }

    .lsb-br-list-section {
      background: var(--lsb-white); border: 1px solid var(--lsb-border);
      border-radius: 16px; overflow: hidden;
    }
    .lsb-br-list-header {
      display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px;
      padding: 12px 24px; background: var(--lsb-off-white);
      border-bottom: 1px solid var(--lsb-border);
      font-size: 0.72rem; font-weight: 600; letter-spacing: 0.08em;
      text-transform: uppercase; color: var(--lsb-muted);
    }
    .lsb-br-list-row {
      display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px;
      padding: 14px 24px; align-items: center;
      border-bottom: 1px solid var(--lsb-border);
      transition: background 0.15s; text-decoration: none !important;
    }
    .lsb-br-list-row:last-child { border-bottom: none; }
    .lsb-br-list-row:hover { background: var(--lsb-off-white); }

    .lsb-br-list-name {
      font-family: 'Syne', sans-serif; font-weight: 600; font-size: 0.9rem;
      color: var(--lsb-navy) !important; text-decoration: none !important;
      display: flex; align-items: center; gap: 10px;
    }
    .lsb-br-list-initial {
      width: 32px; height: 32px; border-radius: 8px;
      background: var(--lsb-off-white); border: 1px solid var(--lsb-border);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-weight: 700;
      font-size: 0.75rem; color: var(--lsb-navy); flex-shrink: 0;
    }
    .lsb-br-list-phone { font-size: 0.88rem; color: var(--lsb-muted); }
    .lsb-br-list-cta {
      background: transparent !important; border: 1px solid var(--lsb-border) !important;
      color: var(--lsb-navy) !important; font-family: 'Syne', sans-serif !important;
      font-weight: 600 !important; font-size: 0.72rem !important;
      padding: 5px 12px !important; border-radius: 6px !important;
      text-decoration: none !important; white-space: nowrap !important;
      transition: all 0.15s !important; display: inline-block !important;
    }
    .lsb-br-list-cta:hover { background: var(--lsb-teal) !important; border-color: var(--lsb-teal) !important; color: var(--lsb-navy) !important; }

    .lsb-br-upgrade-nudge {
      background: linear-gradient(135deg, rgba(0,201,167,0.06), rgba(13,27,42,0.03));
      border: 1px dashed rgba(0,201,167,0.3); border-radius: 12px;
      padding: 18px 24px; margin-top: 16px;
      display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
    }
    .lsb-br-upgrade-nudge p { font-size: 0.85rem; color: var(--lsb-muted); margin: 0 !important; }
    .lsb-br-upgrade-nudge strong { color: var(--lsb-navy); }
    .lsb-br-upgrade-btn {
      background: var(--lsb-teal) !important; color: var(--lsb-navy) !important;
      font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
      font-size: 0.78rem !important; padding: 8px 18px !important;
      border-radius: 7px !important; text-decoration: none !important;
      white-space: nowrap !important; flex-shrink: 0 !important; display: inline-block !important;
    }
    .lsb-br-upgrade-btn:hover { background: var(--lsb-teal-dark) !important; }

    .lsb-br-no-results {
      text-align: center; padding: 60px 20px; background: var(--lsb-white);
      border-radius: 16px; border: 1px solid var(--lsb-border);
    }
    .lsb-br-no-results-icon { font-size: 2.5rem; margin-bottom: 14px; }
    .lsb-br-no-results h3 {
      font-family: 'Syne', sans-serif !important; font-size: 1.3rem !important;
      font-weight: 700 !important; color: var(--lsb-navy) !important; margin-bottom: 8px !important;
    }
    .lsb-br-no-results p { font-size: 0.9rem; color: var(--lsb-muted); }

    @media (max-width: 640px) {
      .lsb-br-hero { padding: 28px 20px 36px; }
      .lsb-br-filter-form { flex-direction: column; }
      .lsb-br-select, .lsb-br-filter-btn { width: 100% !important; }
      .lsb-br-list-header, .lsb-br-list-row { grid-template-columns: 1fr auto; }
      .lsb-br-list-phone { display: none; }
    }
    </style>

    <?php // ══ HERO + FILTERS ══ ?>
    <div class="lsb-br-hero">
      <div class="lsb-br-hero-grid"></div>
      <div class="lsb-br-hero-glow"></div>
      <div class="lsb-br-hero-inner">

        <div class="lsb-br-breadcrumb">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
          <span class="sep">›</span>
          <a href="<?php echo esc_url( home_url( '/businesses/' ) ); ?>">Businesses</a>
          <?php if ( $industry_post ) : ?>
            <span class="sep">›</span>
            <span style="color:rgba(255,255,255,0.55);"><?php echo esc_html( $industry_name ); ?></span>
          <?php endif; ?>
        </div>

        <h1><em><?php echo esc_html( $industry_name ); ?></em> Companies Near You</h1>
        <p class="lsb-br-hero-sub">
          <?php if ( ! empty( $industry_description ) ) :
            // Strip all HTML tags including data attributes
            echo esc_html( wp_trim_words( wp_strip_all_tags( $industry_description ), 20 ) );
          else : ?>
            Browse verified <?php echo esc_html( $industry_name ); ?> professionals serving Greater Los Angeles.
          <?php endif; ?>
        </p>

        <form class="lsb-br-filter-form" method="GET" action="<?php echo esc_url( $base_url ); ?>">
          <select name="city" class="lsb-br-select">
            <option value="">All Cities</option>
            <?php if ( ! is_wp_error( $cities_list ) ) :
              foreach ( $cities_list as $ct ) : ?>
                <option value="<?php echo esc_attr( $ct->slug ); ?>" <?php selected( $selected_city, $ct->slug ); ?>>
                  <?php echo esc_html( $ct->name ); ?>
                </option>
              <?php endforeach;
            endif; ?>
          </select>

          <select name="service" class="lsb-br-select">
            <option value="">All Services</option>
            <?php if ( ! empty( $services_list ) && ! is_wp_error( $services_list ) ) :
              foreach ( $services_list as $sv ) :
                $sv_slug = is_object( $sv ) ? $sv->slug : ( $sv['slug'] ?? '' );
                $sv_name = is_object( $sv ) ? $sv->name : ( $sv['name'] ?? '' );
                if ( empty( $sv_slug ) ) continue; ?>
                <option value="<?php echo esc_attr( $sv_slug ); ?>" <?php selected( $selected_service, $sv_slug ); ?>>
                  <?php echo esc_html( $sv_name ); ?>
                </option>
              <?php endforeach;
            endif; ?>
          </select>

          <select name="sort" class="lsb-br-select">
            <option value="rating"  <?php selected( $selected_sort, 'rating' );  ?>>Highest Rated</option>
            <option value="newest"  <?php selected( $selected_sort, 'newest' );  ?>>Newest</option>
            <option value="az"      <?php selected( $selected_sort, 'az' );      ?>>A–Z</option>
          </select>

          <button type="submit" class="lsb-br-filter-btn">Filter →</button>

          <span class="lsb-br-result-count">
            <?php echo number_format( $total_found ); ?> business<?php echo $total_found !== 1 ? 'es' : ''; ?> found
          </span>
        </form>
      </div>
    </div>

    <?php if ( $total_found === 0 ) : ?>

      <div class="lsb-br-no-results">
        <div class="lsb-br-no-results-icon">🔍</div>
        <h3>No <?php echo esc_html( $industry_name ); ?> businesses found</h3>
        <p><?php echo ( ! empty( $selected_city ) || ! empty( $selected_service ) ) ? 'Try adjusting your filters.' : "We're still adding professionals. Check back soon."; ?></p>
      </div>

    <?php else : ?>

      <?php // ══ SECTION 1: FEATURED CARDS ══ ?>
      <?php if ( ! empty( $featured_posts ) ) : ?>
        <div class="lsb-br-featured-section">
          <span class="lsb-br-section-label">⭐ Featured Professionals</span>
          <div class="lsb-br-grid">
            <?php foreach ( $featured_posts as $biz_id ) :

              $biz_name    = get_the_title( $biz_id );
              $biz_desc    = wp_strip_all_tags( html_entity_decode( (string) get_field( 'business_description', $biz_id ), ENT_QUOTES, 'UTF-8' ) );
              $biz_tagline = (string) get_field( 'business_tagline', $biz_id );
              $biz_phone   = (string) get_field( 'business_phone', $biz_id );
              $biz_rating  = (float) get_field( 'business_rating', $biz_id );
              $biz_reviews = (int)   get_field( 'business_review_count', $biz_id );
              $biz_logo    = get_field( 'business_logo', $biz_id );
              $biz_plan    = get_field( 'plan_tier', $biz_id );

              // Initials
              $initials = '';
              foreach ( array_slice( explode( ' ', $biz_name ), 0, 2 ) as $w ) {
                  $initials .= strtoupper( substr( $w, 0, 1 ) );
              }

             $primary_city_label = (string) get_field( 'business_address', $biz_id );

              // City pills — up to 5 random city_cat terms
              $city_terms = get_the_terms( $biz_id, 'city_cat' );
              $city_pills = array();
              if ( ! is_wp_error( $city_terms ) && ! empty( $city_terms ) ) {
                  $shuffled = $city_terms;
                  shuffle( $shuffled );
                  $city_pills = array_slice( $shuffled, 0, 5 );
              }

              // Service tags
              $service_terms = get_the_terms( $biz_id, 'service_type' );

              // URL
              $biz_url    = home_url( '/businesses/' . $industry_slug . '/' . get_post_field( 'post_name', $biz_id ) . '/' );
              $card_class = $biz_plan === 'premium' ? 'is-premium' : 'is-basic';
            ?>
              <div class="lsb-br-card <?php echo esc_attr( $card_class ); ?>">

                <span class="lsb-br-tier-badge <?php echo esc_attr( $biz_plan ); ?>">
                  <?php echo $biz_plan === 'premium' ? '⭐ Premium' : '✓ Featured'; ?>
                </span>

                <div class="lsb-br-card-top">
                  <div class="lsb-br-logo">
                    <?php if ( ! empty( $biz_logo ) ) : ?>
                      <img src="<?php echo esc_url( is_array( $biz_logo ) ? $biz_logo['url'] : $biz_logo ); ?>"
                           alt="<?php echo esc_attr( $biz_name ); ?>">
                    <?php else : ?>
                      <?php echo esc_html( $initials ); ?>
                    <?php endif; ?>
                  </div>
                  <div class="lsb-br-card-info">
                    <div class="lsb-br-card-niche"><?php echo esc_html( $industry_name ); ?></div>
                    <div class="lsb-br-card-name"><?php echo esc_html( $biz_name ); ?></div>
                    <?php if ( ! empty( $biz_tagline ) ) : ?>
                      <div class="lsb-br-card-tagline"><?php echo esc_html( $biz_tagline ); ?></div>
                    <?php endif; ?>
                    <div class="lsb-br-card-meta">
                      <?php if ( ! empty( $biz_phone ) ) : ?>
                        <span class="meta-phone">📞 <?php echo esc_html( $biz_phone ); ?></span>
                      <?php endif; ?>
                      <?php if ( ! empty( $biz_phone ) && ! empty( $primary_city_label ) ) : ?>
                        <span class="meta-sep">|</span>
                      <?php endif; ?>
                      <?php if ( ! empty( $primary_city_label ) ) : ?>
                        <span class="meta-city">📍 <?php echo esc_html( $primary_city_label ); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php if ( $biz_rating > 0 ) : ?>
                    <div class="lsb-br-rating">
                      ★ <?php echo number_format( $biz_rating, 1 ); ?>
                      <?php if ( $biz_reviews > 0 ) : ?>
                        <span class="lsb-br-rating-count">(<?php echo number_format( $biz_reviews ); ?>)</span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>

                <?php if ( ! empty( $biz_desc ) ) : ?>
                  <p class="lsb-br-desc"><?php echo esc_html( $biz_desc ); ?></p>
                <?php endif; ?>

                <?php if ( ! empty( $city_pills ) ) : ?>
                  <div class="lsb-br-cities">
                    <?php foreach ( $city_pills as $cp ) : ?>
                      <span class="lsb-br-city-pill"><?php echo esc_html( $cp->name ); ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <?php if ( ! is_wp_error( $service_terms ) && ! empty( $service_terms ) ) : ?>
                  <div class="lsb-br-tags">
                    <?php foreach ( array_slice( $service_terms, 0, 4 ) as $st ) : ?>
                      <span class="lsb-br-tag"><?php echo esc_html( $st->name ); ?></span>
                    <?php endforeach; ?>
                    <?php if ( count( $service_terms ) > 4 ) : ?>
                      <span class="lsb-br-tag" style="opacity:0.6;">+<?php echo count( $service_terms ) - 4; ?> more</span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <div class="lsb-br-card-footer">
                  <a href="<?php echo esc_url( $biz_url ); ?>" class="lsb-br-cta">View Business →</a>
                </div>

              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php // ══ SECTION 2: PLAIN LIST ══ ?>
      <?php if ( ! empty( $list_posts ) ) : ?>
        <div class="lsb-br-list-wrap">
          <span class="lsb-br-section-label">All <?php echo esc_html( $industry_name ); ?> Businesses</span>

          <div class="lsb-br-list-section">
            <div class="lsb-br-list-header">
              <span>Business Name</span>
              <span>Phone</span>
              <span></span>
            </div>
            <?php foreach ( $list_posts as $biz_id ) :
              $biz_name  = get_the_title( $biz_id );
              $biz_phone = get_field( 'business_phone', $biz_id );
              $biz_url   = home_url( '/businesses/' . $industry_slug . '/' . get_post_field( 'post_name', $biz_id ) . '/' );
              $initials  = '';
              foreach ( array_slice( explode( ' ', $biz_name ), 0, 2 ) as $w ) {
                  $initials .= strtoupper( substr( $w, 0, 1 ) );
              }
            ?>
              <a href="<?php echo esc_url( $biz_url ); ?>" class="lsb-br-list-row">
                <span class="lsb-br-list-name">
                  <span class="lsb-br-list-initial"><?php echo esc_html( $initials ); ?></span>
                  <?php echo esc_html( $biz_name ); ?>
                </span>
                <span class="lsb-br-list-phone">
                  <?php echo ! empty( $biz_phone ) ? esc_html( $biz_phone ) : '—'; ?>
                </span>
                <span class="lsb-br-list-cta">View →</span>
              </a>
            <?php endforeach; ?>
          </div>

          <div class="lsb-br-upgrade-nudge">
            <p>
              <strong>Own one of these businesses?</strong>
              Upgrade to a Featured listing to showcase your full profile, ratings, and services.
            </p>
            <a href="<?php echo esc_url( home_url( '/get-listed/' ) ); ?>" class="lsb-br-upgrade-btn">
              Get Featured →
            </a>
          </div>
        </div>
      <?php endif; ?>

    <?php endif; ?>

    <?php
    return ob_get_clean();
}
add_shortcode( 'business_results', 'lsb_business_results_shortcode' );