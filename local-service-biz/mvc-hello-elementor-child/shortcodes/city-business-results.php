<?php
/**
 * Shortcode: [city_business_results]
 *
 * File: /wp-content/themes/mvc-hello-elementor-child/shortcodes/city-business-results.php
 * Register in functions.php:
 *   require_once get_stylesheet_directory() . '/shortcodes/city-business-results.php';
 *
 * Rewrite rule to add in functions.php inside mvc_custom_rewrite_rules():
 *   add_rewrite_rule(
 *       '^cities/([^/]+)/businesses/?$',
 *       'index.php?pagename=city-businesses&city_slug=$matches[1]',
 *       'top'
 *   );
 * Also add 'city_slug' to your query_vars filter if not already present.
 *
 * Setup:
 *   1. Create a blank WordPress Page with slug: city-businesses
 *   2. Build an Elementor template for that page
 *   3. Drop [city_business_results] into an HTML widget
 *   4. Flush permalinks: Settings → Permalinks → Save
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function lsb_city_business_results_shortcode( $atts ) {

    $atts = shortcode_atts( array(
        'city' => '',
    ), $atts, 'city_business_results' );

    // ── 1. Detect city slug ───────────────────────────────────────────────────
    $city_slug = '';

    if ( ! empty( $atts['city'] ) ) {
        $city_slug = sanitize_title( $atts['city'] );
    }
    if ( empty( $city_slug ) ) {
        $city_slug = get_query_var( 'city_slug', '' );
    }
    if ( empty( $city_slug ) ) {
        $uri       = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
        $uri_parts = array_values( array_filter( explode( '/', $uri ) ) );
        // Pattern: cities/{city-slug}/businesses
        if ( isset( $uri_parts[0], $uri_parts[1] ) && $uri_parts[0] === 'cities' ) {
            $city_slug = sanitize_title( $uri_parts[1] );
        }
    }

    // ── 2. Load city CPT data ─────────────────────────────────────────────────
    $city_name = '';
    if ( ! empty( $city_slug ) ) {
        $city_posts = get_posts( array(
            'post_type'      => 'cities',
            'name'           => $city_slug,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ) );
        if ( ! empty( $city_posts ) ) {
            $city_name = get_the_title( $city_posts[0] );
        }
    }
    if ( empty( $city_name ) ) {
        $city_name = ucwords( str_replace( '-', ' ', $city_slug ) );
    }

    // ── 3. Filters ────────────────────────────────────────────────────────────
    $selected_industry = isset( $_GET['industry'] ) ? sanitize_title( $_GET['industry'] ) : '';
    $selected_sort     = isset( $_GET['sort'] )     ? sanitize_key( $_GET['sort'] )       : 'rating';
    $base_url          = home_url( '/cities/' . $city_slug . '/businesses/' );

    // ── 4. Get all industries alphabetically ──────────────────────────────────
    $all_industry_terms = get_terms( array(
        'taxonomy'   => 'industry_cat',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'parent'     => 0,
    ) );

    if ( is_wp_error( $all_industry_terms ) || empty( $all_industry_terms ) ) {
        return '<p style="color:#8A9BB0;font-size:0.9rem;">No industries found.</p>';
    }

    // Filter to selected industry if chosen
    if ( ! empty( $selected_industry ) ) {
        $all_industry_terms = array_filter( $all_industry_terms, function( $t ) use ( $selected_industry ) {
            return $t->slug === $selected_industry;
        } );
    }

    // ── 5. Pre-query: find which industries have businesses in this city ───────
    // Build one query per industry to split featured vs list — stored as:
    // $industry_data[ $term->slug ] = [ 'term' => $term, 'featured' => [], 'list' => [] ]
    $industry_data  = array();
    $total_found    = 0;

    foreach ( $all_industry_terms as $ind_term ) {

        $tax_query = array(
            'relation' => 'AND',
            array(
                'taxonomy'         => 'industry_cat',
                'field'            => 'slug',
                'terms'            => $ind_term->slug,
                'include_children' => true,
            ),
        );

        if ( ! empty( $city_slug ) ) {
            $tax_query[] = array(
                'taxonomy'         => 'city_cat',
                'field'            => 'slug',
                'terms'            => $city_slug,
                'include_children' => true,
            );
        }

        $q = new WP_Query( array(
            'post_type'      => 'businesses',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query'      => $tax_query,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        if ( ! $q->have_posts() ) {
            wp_reset_postdata();
            continue;
        }

        $featured = array();
        $list     = array();

        while ( $q->have_posts() ) {
            $q->the_post();
            $biz_id      = get_the_ID();
            $plan        = get_field( 'plan_tier', $biz_id );
            $plan_active = get_field( 'plan_active', $biz_id );
            $plan_expiry = get_field( 'plan_expiry', $biz_id );

            $is_paid = ! empty( $plan )
                && $plan_active
                && ( empty( $plan_expiry ) || strtotime( $plan_expiry ) >= time() )
                && in_array( $plan, array( 'premium', 'basic' ), true );

            if ( $is_paid ) {
                $featured[] = $biz_id;
            } else {
                $list[] = $biz_id;
            }
        }
        wp_reset_postdata();

        $industry_data[ $ind_term->slug ] = array(
            'term'     => $ind_term,
            'featured' => $featured,
            'list'     => $list,
            'count'    => count( $featured ) + count( $list ),
        );
        $total_found += count( $featured ) + count( $list );
    }

    // ── 6. Render ─────────────────────────────────────────────────────────────
    ob_start();
    ?>

    <style>
    :root {
      --cbr-navy:      #0D1B2A;
      --cbr-navy-mid:  #1B2F45;
      --cbr-teal:      #00C9A7;
      --cbr-teal-dark: #00A88C;
      --cbr-gold:      #F4C542;
      --cbr-white:     #FFFFFF;
      --cbr-off-white: #F5F7FA;
      --cbr-muted:     #8A9BB0;
      --cbr-text:      #1A2535;
      --cbr-border:    #E4EAF2;
    }

    /* ── Hero ── */
    .cbr-hero {
      background: var(--cbr-navy); padding: 48px 60px 52px;
      position: relative; overflow: hidden; border-radius: 16px; margin-bottom: 40px;
    }
    .cbr-hero-grid {
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(0,201,167,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,201,167,0.04) 1px, transparent 1px);
      background-size: 60px 60px; pointer-events: none; border-radius: 16px;
    }
    .cbr-hero-glow {
      position: absolute; width: 400px; height: 250px;
      background: radial-gradient(ellipse, rgba(0,201,167,0.1) 0%, transparent 70%);
      right: -60px; top: -30px; pointer-events: none;
    }
    .cbr-hero-inner { position: relative; z-index: 1; }

    .cbr-breadcrumb {
      display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
      font-size: 0.78rem; color: rgba(255,255,255,0.4); flex-wrap: wrap;
    }
    .cbr-breadcrumb a { color: rgba(255,255,255,0.4); text-decoration: none; transition: color 0.2s; }
    .cbr-breadcrumb a:hover { color: var(--cbr-teal); }
    .cbr-breadcrumb .sep { color: rgba(255,255,255,0.2); }

    .cbr-hero h1 {
      font-family: 'Syne', sans-serif !important; font-size: clamp(1.6rem, 3.5vw, 2.4rem) !important;
      font-weight: 800 !important; color: var(--cbr-white) !important;
      letter-spacing: -0.03em !important; line-height: 1.1 !important; margin-bottom: 8px !important;
    }
    .cbr-hero h1 em { font-style: normal !important; color: var(--cbr-teal) !important; }

    .cbr-hero-sub {
      font-size: 0.92rem; color: rgba(255,255,255,0.45);
      margin-bottom: 28px; font-weight: 300; line-height: 1.6;
    }

    .cbr-filter-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

    .cbr-select {
      background: rgba(255,255,255,0.07) !important; border: 1px solid rgba(255,255,255,0.12) !important;
      border-radius: 8px !important; color: var(--cbr-white) !important;
      font-family: 'DM Sans', sans-serif !important; font-size: 0.88rem !important;
      padding: 10px 34px 10px 14px !important; outline: none !important;
      cursor: pointer !important; appearance: none !important; -webkit-appearance: none !important;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2300C9A7' stroke-width='2' fill='none'/%3E%3C/svg%3E") !important;
      background-repeat: no-repeat !important; background-position: right 10px center !important; min-width: 180px !important;
    }
    .cbr-select:focus { border-color: var(--cbr-teal) !important; }
    .cbr-select option { background: var(--cbr-navy-mid); color: var(--cbr-white); }

    .cbr-filter-btn {
      background: var(--cbr-teal) !important; color: var(--cbr-navy) !important;
      font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
      font-size: 0.85rem !important; padding: 10px 22px !important;
      border: none !important; border-radius: 8px !important; cursor: pointer !important; white-space: nowrap !important;
    }
    .cbr-filter-btn:hover { background: var(--cbr-teal-dark) !important; }

    .cbr-result-count {
      font-size: 0.8rem; color: rgba(255,255,255,0.35); white-space: nowrap; margin-left: auto;
    }

    /* ── Industry section ── */
    .cbr-industry-section {
      margin-bottom: 56px; padding: 0 60px;
    }

    .cbr-industry-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 20px; padding-bottom: 14px;
      border-bottom: 2px solid var(--cbr-border);
    }

    .cbr-industry-title-wrap { display: flex; align-items: center; gap: 12px; }

    .cbr-industry-icon {
      width: 40px; height: 40px; border-radius: 10px;
      background: rgba(0,201,167,0.1); display: flex;
      align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
    }

    .cbr-industry-name {
      font-family: 'Syne', sans-serif !important; font-weight: 800 !important;
      font-size: 1.3rem !important; color: var(--cbr-navy) !important;
      letter-spacing: -0.02em !important;
    }

    .cbr-industry-count {
      font-size: 0.78rem; color: var(--cbr-muted); font-weight: 400;
    }

    .cbr-industry-link {
      font-size: 0.82rem; color: var(--cbr-teal); text-decoration: none;
      font-weight: 500; white-space: nowrap; transition: gap 0.2s;
      display: flex; align-items: center; gap: 4px;
    }
    .cbr-industry-link:hover { color: var(--cbr-teal-dark); }

    .cbr-section-label {
      font-family: 'Syne', sans-serif; font-size: 0.68rem; font-weight: 600;
      letter-spacing: 0.1em; text-transform: uppercase; color: var(--cbr-teal);
      margin-bottom: 12px; display: block;
    }

    /* ── Featured cards grid ── */
    .cbr-featured-wrap { margin-bottom: 24px; }
    .cbr-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
    @media (max-width: 768px) { .cbr-grid { grid-template-columns: 1fr; } }

    .cbr-card {
      background: var(--cbr-white); border: 1px solid var(--cbr-border); border-radius: 16px;
      padding: 22px; transition: all 0.25s; display: flex; flex-direction: column;
      position: relative; overflow: hidden;
    }
    .cbr-card::after {
      content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
      background: linear-gradient(90deg, var(--cbr-teal), var(--cbr-teal-dark));
      transform: scaleX(0); transform-origin: left; transition: transform 0.3s ease;
    }
    .cbr-card:hover { border-color: rgba(0,201,167,0.3) !important; box-shadow: 0 12px 36px rgba(0,0,0,0.08) !important; transform: translateY(-4px) !important; }
    .cbr-card:hover::after { transform: scaleX(1); }
    .cbr-card.is-premium { border-color: rgba(244,197,66,0.4) !important; background: linear-gradient(135deg, #fffdf5 0%, var(--cbr-white) 60%) !important; }
    .cbr-card.is-premium::after { background: linear-gradient(90deg, var(--cbr-gold), #e8a800); }
    .cbr-card.is-basic { border-color: rgba(0,201,167,0.25) !important; }

    .cbr-tier-badge {
      position: absolute; top: 12px; right: 12px; font-family: 'Syne', sans-serif;
      font-weight: 700; font-size: 0.62rem; letter-spacing: 0.08em;
      text-transform: uppercase; padding: 3px 10px; border-radius: 100px;
    }
    .cbr-tier-badge.premium { background: var(--cbr-gold); color: var(--cbr-navy); }
    .cbr-tier-badge.basic   { background: rgba(0,201,167,0.15); color: var(--cbr-teal-dark); border: 1px solid rgba(0,201,167,0.3); }

    .cbr-card-top { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; }

    .cbr-logo {
      width: 52px; height: 52px; border-radius: 11px; background: var(--cbr-navy);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: 0.9rem;
      color: var(--cbr-teal); flex-shrink: 0; overflow: hidden;
    }
    .cbr-logo img { width: 100%; height: 100%; object-fit: cover; border-radius: 11px; }

    .cbr-card-info { flex: 1; min-width: 0; }
    .cbr-card-niche { font-size: 0.68rem; color: var(--cbr-teal-dark); font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 2px; }
    .cbr-card-name {
      font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
      font-size: 0.95rem !important; color: var(--cbr-navy) !important;
      letter-spacing: -0.01em !important; margin-bottom: 3px !important;
      white-space: normal; word-break: break-word;
    }
    .cbr-card-tagline {
      font-size: 0.78rem; color: var(--cbr-muted); font-weight: 300;
      line-height: 1.4; margin-bottom: 5px; font-style: italic;
    }
    .cbr-card-meta {
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
      font-size: 0.76rem; color: var(--cbr-muted);
    }
    .cbr-card-meta .meta-phone { color: var(--cbr-text); font-weight: 500; }
    .cbr-card-meta .meta-city  { color: var(--cbr-teal-dark); font-weight: 500; }
    .cbr-card-meta .meta-sep   { color: var(--cbr-border); }

    .cbr-rating {
      display: flex; align-items: center; gap: 3px; font-size: 0.8rem;
      color: var(--cbr-gold); font-weight: 700; white-space: nowrap; flex-shrink: 0;
    }
    .cbr-rating-count { font-size: 0.7rem; color: var(--cbr-muted); font-weight: 400; }

    .cbr-desc {
      font-size: 0.82rem; color: var(--cbr-muted) !important; line-height: 1.65;
      margin-bottom: 10px; font-weight: 300; flex: 1;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }

    .cbr-cities { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
    .cbr-city-pill {
      background: rgba(13,27,42,0.06); border: 1px solid rgba(13,27,42,0.1);
      color: var(--cbr-text); font-size: 0.66rem; padding: 2px 8px;
      border-radius: 100px; font-weight: 500; white-space: nowrap;
    }

    .cbr-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px; }
    .cbr-tag {
      background: rgba(0,201,167,0.07); border: 1px solid rgba(0,201,167,0.18);
      color: var(--cbr-teal-dark); font-size: 0.68rem; padding: 3px 9px;
      border-radius: 100px; font-weight: 500; white-space: nowrap;
    }

    .cbr-card-footer {
      display: flex; align-items: center; justify-content: flex-end;
      padding-top: 10px; border-top: 1px solid var(--cbr-border); margin-top: auto;
    }
    .cbr-cta {
      background: var(--cbr-navy) !important; color: var(--cbr-white) !important;
      font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
      font-size: 0.72rem !important; padding: 7px 14px !important;
      border-radius: 7px !important; text-decoration: none !important;
      white-space: nowrap !important; display: inline-block !important; transition: background 0.2s !important;
    }
    .cbr-cta:hover { background: var(--cbr-teal) !important; color: var(--cbr-navy) !important; }

    /* ── Plain list ── */
    .cbr-list-section {
      background: var(--cbr-white); border: 1px solid var(--cbr-border);
      border-radius: 14px; overflow: hidden;
    }
    .cbr-list-header {
      display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px;
      padding: 10px 20px; background: var(--cbr-off-white);
      border-bottom: 1px solid var(--cbr-border);
      font-size: 0.68rem; font-weight: 600; letter-spacing: 0.08em;
      text-transform: uppercase; color: var(--cbr-muted);
    }
    .cbr-list-row {
      display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px;
      padding: 12px 20px; align-items: center;
      border-bottom: 1px solid var(--cbr-border);
      transition: background 0.15s; text-decoration: none !important;
    }
    .cbr-list-row:last-child { border-bottom: none; }
    .cbr-list-row:hover { background: var(--cbr-off-white); }

    .cbr-list-name {
      font-family: 'Syne', sans-serif; font-weight: 600; font-size: 0.88rem;
      color: var(--cbr-navy) !important; text-decoration: none !important;
      display: flex; align-items: center; gap: 10px;
    }
    .cbr-list-initial {
      width: 30px; height: 30px; border-radius: 7px;
      background: var(--cbr-off-white); border: 1px solid var(--cbr-border);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-weight: 700;
      font-size: 0.7rem; color: var(--cbr-navy); flex-shrink: 0;
    }
    .cbr-list-phone { font-size: 0.84rem; color: var(--cbr-muted); }
    .cbr-list-cta {
      background: transparent !important; border: 1px solid var(--cbr-border) !important;
      color: var(--cbr-navy) !important; font-family: 'Syne', sans-serif !important;
      font-weight: 600 !important; font-size: 0.7rem !important;
      padding: 5px 12px !important; border-radius: 6px !important;
      text-decoration: none !important; white-space: nowrap !important;
      transition: all 0.15s !important; display: inline-block !important;
    }
    .cbr-list-cta:hover { background: var(--cbr-teal) !important; border-color: var(--cbr-teal) !important; color: var(--cbr-navy) !important; }

    .cbr-upgrade-nudge {
      background: linear-gradient(135deg, rgba(0,201,167,0.06), rgba(13,27,42,0.03));
      border: 1px dashed rgba(0,201,167,0.3); border-radius: 10px;
      padding: 14px 20px; margin-top: 12px;
      display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .cbr-upgrade-nudge p { font-size: 0.82rem; color: var(--cbr-muted); margin: 0 !important; }
    .cbr-upgrade-nudge strong { color: var(--cbr-navy); }
    .cbr-upgrade-btn {
      background: var(--cbr-teal) !important; color: var(--cbr-navy) !important;
      font-family: 'Syne', sans-serif !important; font-weight: 700 !important;
      font-size: 0.75rem !important; padding: 7px 16px !important;
      border-radius: 7px !important; text-decoration: none !important;
      white-space: nowrap !important; flex-shrink: 0 !important; display: inline-block !important;
    }
    .cbr-upgrade-btn:hover { background: var(--cbr-teal-dark) !important; }

    .cbr-no-results {
      text-align: center; padding: 60px 20px; background: var(--cbr-white);
      border-radius: 16px; border: 1px solid var(--cbr-border); margin: 0 60px;
    }
    .cbr-no-results-icon { font-size: 2.5rem; margin-bottom: 14px; }
    .cbr-no-results h3 {
      font-family: 'Syne', sans-serif !important; font-size: 1.3rem !important;
      font-weight: 700 !important; color: var(--cbr-navy) !important; margin-bottom: 8px !important;
    }
    .cbr-no-results p { font-size: 0.9rem; color: var(--cbr-muted); }

    @media (max-width: 768px) {
      .cbr-hero { padding: 28px 20px 36px; }
      .cbr-industry-section { padding: 0 16px; }
      .cbr-filter-form { flex-direction: column; }
      .cbr-select, .cbr-filter-btn { width: 100% !important; }
      .cbr-list-header, .cbr-list-row { grid-template-columns: 1fr auto; }
      .cbr-list-phone { display: none; }
      .cbr-no-results { margin: 0 16px; }
    }
    </style>

    <?php // ══ HERO ══ ?>
    <div class="cbr-hero">
      <div class="cbr-hero-grid"></div>
      <div class="cbr-hero-glow"></div>
      <div class="cbr-hero-inner">

        <div class="cbr-breadcrumb">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
          <span class="sep">›</span>
          <a href="<?php echo esc_url( home_url( '/cities/' ) ); ?>">Cities</a>
          <span class="sep">›</span>
          <a href="<?php echo esc_url( home_url( '/cities/' . $city_slug . '/' ) ); ?>"><?php echo esc_html( $city_name ); ?></a>
          <span class="sep">›</span>
          <span style="color:rgba(255,255,255,0.55);">Businesses</span>
        </div>

        <h1>Businesses in <em><?php echo esc_html( $city_name ); ?></em></h1>
        <p class="cbr-hero-sub">
          Browse <?php echo number_format( $total_found ); ?> local service professional<?php echo $total_found !== 1 ? 's' : ''; ?> serving <?php echo esc_html( $city_name ); ?>, organized by industry.
        </p>

        <form class="cbr-filter-form" method="GET" action="<?php echo esc_url( $base_url ); ?>">
          <select name="industry" class="cbr-select">
            <option value="">All Industries</option>
            <?php foreach ( $all_industry_terms as $ind ) : ?>
              <option value="<?php echo esc_attr( $ind->slug ); ?>" <?php selected( $selected_industry, $ind->slug ); ?>>
                <?php echo esc_html( $ind->name ); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="sort" class="cbr-select">
            <option value="rating" <?php selected( $selected_sort, 'rating' ); ?>>Highest Rated</option>
            <option value="az"     <?php selected( $selected_sort, 'az' ); ?>>A–Z</option>
          </select>

          <button type="submit" class="cbr-filter-btn">Filter →</button>

          <span class="cbr-result-count">
            <?php echo number_format( $total_found ); ?> business<?php echo $total_found !== 1 ? 'es' : ''; ?> found
          </span>
        </form>
      </div>
    </div>

    <?php if ( empty( $industry_data ) ) : ?>

      <div class="cbr-no-results">
        <div class="cbr-no-results-icon">🔍</div>
        <h3>No businesses found in <?php echo esc_html( $city_name ); ?></h3>
        <p><?php echo ! empty( $selected_industry ) ? 'Try selecting a different industry.' : "We're still adding professionals in this area. Check back soon."; ?></p>
      </div>

    <?php else : ?>

      <?php foreach ( $industry_data as $ind_slug => $data ) :
        $ind_term      = $data['term'];
        $ind_name      = $data['term']->name;
        $ind_count     = $data['count'];
        $ind_featured  = $data['featured'];
        $ind_list      = $data['list'];
       
$ind_page_url = home_url( '/businesses/' . $ind_slug . '/?city=' . $city_slug );

        // Industry icons map
        $icons = array(
          'hvac'            => '❄️',
          'roofing'         => '🏠',
          'plumbing'        => '🔧',
          'auto-body'       => '🚗',
          'locksmith'       => '🔑',
          'restoration'     => '🌊',
          'catering'        => '🍽️',
          'realtors'        => '🏡',
          'public-adjusting'=> '📋',
          'scrap-metals'    => '♻️',
          'dental-broker'   => '🦷',
        );
        $ind_icon = $icons[ $ind_slug ] ?? '🏢';
      ?>

        <div class="cbr-industry-section">

          <div class="cbr-industry-header">
            <div class="cbr-industry-title-wrap">
              <div class="cbr-industry-icon"><?php echo $ind_icon; ?></div>
              <div>
                <div class="cbr-industry-name"><?php echo esc_html( $ind_name ); ?></div>
                <div class="cbr-industry-count"><?php echo number_format( $ind_count ); ?> business<?php echo $ind_count !== 1 ? 'es' : ''; ?></div>
              </div>
            </div>
            <a href="<?php echo esc_url( $ind_page_url ); ?>" class="cbr-industry-link">
              View All <?php echo esc_html( $ind_name ); ?> →
            </a>
          </div>

          <?php // ── Featured cards ── ?>
          <?php if ( ! empty( $ind_featured ) ) : ?>
            <div class="cbr-featured-wrap">
              <span class="cbr-section-label">⭐ Featured Professionals</span>
              <div class="cbr-grid">
                <?php foreach ( $ind_featured as $biz_id ) :
                  $biz_name    = get_the_title( $biz_id );
                  $biz_desc    = wp_strip_all_tags( html_entity_decode( (string) get_field( 'business_description', $biz_id ), ENT_QUOTES, 'UTF-8' ) );
                  $biz_tagline = (string) get_field( 'business_tagline', $biz_id );
                  $biz_phone   = (string) get_field( 'business_phone', $biz_id );
                  $biz_address = (string) get_field( 'business_address', $biz_id );
                  $biz_rating  = (float)  get_field( 'business_rating', $biz_id );
                  $biz_reviews = (int)    get_field( 'business_review_count', $biz_id );
                  $biz_logo    = get_field( 'business_logo', $biz_id );
                  $biz_plan    = get_field( 'plan_tier', $biz_id );

                  $initials = '';
                  foreach ( array_slice( explode( ' ', $biz_name ), 0, 2 ) as $w ) {
                      $initials .= strtoupper( substr( $w, 0, 1 ) );
                  }

                  // 5 random city_cat terms
                  $city_terms = get_the_terms( $biz_id, 'city_cat' );
                  $city_pills = array();
                  if ( ! is_wp_error( $city_terms ) && ! empty( $city_terms ) ) {
                      $shuffled = $city_terms;
                      shuffle( $shuffled );
                      $city_pills = array_slice( $shuffled, 0, 5 );
                  }

                  $service_terms = get_the_terms( $biz_id, 'service_type' );
                  $biz_url       = home_url( '/businesses/' . $ind_slug . '/' . get_post_field( 'post_name', $biz_id ) . '/' );
                  $card_class    = $biz_plan === 'premium' ? 'is-premium' : 'is-basic';
                ?>
                  <div class="cbr-card <?php echo esc_attr( $card_class ); ?>">

                    <span class="cbr-tier-badge <?php echo esc_attr( $biz_plan ); ?>">
                      <?php echo $biz_plan === 'premium' ? '⭐ Premium' : '✓ Featured'; ?>
                    </span>

                    <div class="cbr-card-top">
                      <div class="cbr-logo">
                        <?php if ( ! empty( $biz_logo ) ) : ?>
                          <img src="<?php echo esc_url( is_array( $biz_logo ) ? $biz_logo['url'] : $biz_logo ); ?>"
                               alt="<?php echo esc_attr( $biz_name ); ?>">
                        <?php else : ?>
                          <?php echo esc_html( $initials ); ?>
                        <?php endif; ?>
                      </div>
                      <div class="cbr-card-info">
                        <div class="cbr-card-niche"><?php echo esc_html( $ind_name ); ?></div>
                        <div class="cbr-card-name"><?php echo esc_html( $biz_name ); ?></div>
                        <?php if ( ! empty( $biz_tagline ) ) : ?>
                          <div class="cbr-card-tagline"><?php echo esc_html( $biz_tagline ); ?></div>
                        <?php endif; ?>
                        <div class="cbr-card-meta">
                          <?php if ( ! empty( $biz_phone ) ) : ?>
                            <span class="meta-phone">📞 <?php echo esc_html( $biz_phone ); ?></span>
                          <?php endif; ?>
                          <?php if ( ! empty( $biz_phone ) && ! empty( $biz_address ) ) : ?>
                            <span class="meta-sep">|</span>
                          <?php endif; ?>
                          <?php if ( ! empty( $biz_address ) ) : ?>
                            <span class="meta-city">📍 <?php echo esc_html( $biz_address ); ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                      <?php if ( $biz_rating > 0 ) : ?>
                        <div class="cbr-rating">
                          ★ <?php echo number_format( $biz_rating, 1 ); ?>
                          <?php if ( $biz_reviews > 0 ) : ?>
                            <span class="cbr-rating-count">(<?php echo number_format( $biz_reviews ); ?>)</span>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </div>

                    <?php if ( ! empty( $biz_desc ) ) : ?>
                      <p class="cbr-desc"><?php echo esc_html( $biz_desc ); ?></p>
                    <?php endif; ?>

                    <?php if ( ! empty( $city_pills ) ) : ?>
                      <div class="cbr-cities">
                        <?php foreach ( $city_pills as $cp ) : ?>
                          <span class="cbr-city-pill"><?php echo esc_html( $cp->name ); ?></span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>

                    <?php if ( ! is_wp_error( $service_terms ) && ! empty( $service_terms ) ) : ?>
                      <div class="cbr-tags">
                        <?php foreach ( array_slice( $service_terms, 0, 4 ) as $st ) : ?>
                          <span class="cbr-tag"><?php echo esc_html( $st->name ); ?></span>
                        <?php endforeach; ?>
                        <?php if ( count( $service_terms ) > 4 ) : ?>
                          <span class="cbr-tag" style="opacity:0.6;">+<?php echo count( $service_terms ) - 4; ?> more</span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                    <div class="cbr-card-footer">
                      <a href="<?php echo esc_url( $biz_url ); ?>" class="cbr-cta">View Business →</a>
                    </div>

                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php // ── Plain list ── ?>
          <?php if ( ! empty( $ind_list ) ) : ?>
            <div class="cbr-list-wrap">
              <?php if ( ! empty( $ind_featured ) ) : ?>
                <span class="cbr-section-label" style="margin-top:8px;">All <?php echo esc_html( $ind_name ); ?> Businesses</span>
              <?php endif; ?>
              <div class="cbr-list-section">
                <div class="cbr-list-header">
                  <span>Business Name</span>
                  <span>Phone</span>
                  <span></span>
                </div>
                <?php foreach ( $ind_list as $biz_id ) :
                  $biz_name  = get_the_title( $biz_id );
                  $biz_phone = get_field( 'business_phone', $biz_id );
                  $biz_url   = home_url( '/businesses/' . $ind_slug . '/' . get_post_field( 'post_name', $biz_id ) . '/' );
                  $initials  = '';
                  foreach ( array_slice( explode( ' ', $biz_name ), 0, 2 ) as $w ) {
                      $initials .= strtoupper( substr( $w, 0, 1 ) );
                  }
                ?>
                  <a href="<?php echo esc_url( $biz_url ); ?>" class="cbr-list-row">
                    <span class="cbr-list-name">
                      <span class="cbr-list-initial"><?php echo esc_html( $initials ); ?></span>
                      <?php echo esc_html( $biz_name ); ?>
                    </span>
                    <span class="cbr-list-phone">
                      <?php echo ! empty( $biz_phone ) ? esc_html( $biz_phone ) : '—'; ?>
                    </span>
                    <span class="cbr-list-cta">View →</span>
                  </a>
                <?php endforeach; ?>
              </div>

              <div class="cbr-upgrade-nudge">
                <p><strong>Own one of these businesses?</strong> Upgrade to a Featured listing to showcase your full profile.</p>
                <a href="<?php echo esc_url( home_url( '/get-listed/' ) ); ?>" class="cbr-upgrade-btn">Get Featured →</a>
              </div>
            </div>
          <?php endif; ?>

        </div>

      <?php endforeach; ?>

    <?php endif; ?>

    <?php
    return ob_get_clean();
}
add_action( 'init', function() {
    add_shortcode( 'city_business_results', 'lsb_city_business_results_shortcode' );
} );