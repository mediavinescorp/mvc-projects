<?php
/**
 * Shortcode: Featured Cities
 * File:      shortcodes/lsb-featured-cities.php
 * Usage:     [lsb_featured_cities]
 *
 * require_once get_stylesheet_directory() . '/shortcodes/lsb-featured-cities.php';
 *
 * Randomly pulls 3 cities from city_cat taxonomy on every page load.
 * Shows business count, industry count, and top industry tags per city.
 *
 * ACF fields on City CPT (optional — falls back gracefully):
 *   city_neighborhoods  (True/False)  — shows "Neighborhoods available"
 */

if ( ! function_exists( 'lsb_featured_cities_shortcode' ) ) :

function lsb_featured_cities_shortcode() {

    // ── Pull 3 random cities from city_cat ────────────────
    $all_cities = get_terms([
        'taxonomy'   => 'city_cat',
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 0, // get all
    ]);

    if ( is_wp_error( $all_cities ) || empty( $all_cities ) ) {
        return '';
    }

    // Shuffle and take 3
    shuffle( $all_cities );
    $cities = array_slice( $all_cities, 0, 3 );

    // ── Build city data ───────────────────────────────────
    $city_data = [];

    foreach ( $cities as $term ) {

        $business_count = (int) $term->count;

        // Get all businesses in this city
        $biz_ids = get_posts([
            'post_type'      => 'businesses',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy' => 'city_cat',
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ]],
        ]);

        // Count distinct industries
        $industry_ids   = [];
        $industry_names = [];

        foreach ( $biz_ids as $biz_id ) {
            $inds = get_the_terms( $biz_id, 'industry_cat' );
            if ( $inds && ! is_wp_error( $inds ) ) {
                foreach ( $inds as $ind ) {
                    if ( ! isset( $industry_ids[ $ind->term_id ] ) ) {
                        $industry_ids[ $ind->term_id ]   = true;
                        $industry_names[ $ind->term_id ] = $ind->name;
                    }
                }
            }
        }

        $industry_count = count( $industry_ids );
        $industry_tags  = array_values( $industry_names );

        // City CPT permalink
        $city_post = get_posts([
            'post_type'      => 'city',
            'name'           => $term->slug,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);

        $city_url = ! empty( $city_post )
            ? get_permalink( $city_post[0] )
            : get_term_link( $term );

        // Neighborhoods available — check if city has terms in neighborhoods taxonomy
        $has_neighborhoods = false;
        if ( ! empty( $city_post ) ) {
            $neighborhoods = get_the_terms( $city_post[0], 'neighborhoods' );
            $has_neighborhoods = ( $neighborhoods && ! is_wp_error( $neighborhoods ) );
        }

        $city_data[] = [
            'name'             => $term->name,
            'url'              => $city_url,
            'businesses'       => $business_count,
            'industries'       => $industry_count,
            'tags'             => $industry_tags,
            'has_neighborhoods' => $has_neighborhoods,
        ];
    }

    ob_start(); ?>

    <style>
    .lsb-fc-section {
        background: var(--off-white);
        padding: 80px 40px;
    }
    .lsb-fc-inner {
        max-width: 1200px;
        margin: 0 auto;
    }
    .lsb-fc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 32px;
    }
    .lsb-fc-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--teal);
        margin-bottom: 8px;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-fc-title {
        font-family: 'Syne', sans-serif;
        font-size: clamp(1.6rem, 3vw, 2.2rem);
        font-weight: 800;
        color: var(--navy);
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin: 0;
    }
    .lsb-fc-view-all {
        color: var(--teal);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        transition: gap 0.2s;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-fc-view-all:hover { gap: 10px; color: var(--teal); }

    /* ── Grid ──────────────────────────────────────────── */
    .lsb-fc-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    /* ── Card ──────────────────────────────────────────── */
    .lsb-fc-card {
        background: var(--navy);
        border-radius: 20px;
        padding: 28px;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        gap: 0;
        position: relative;
        overflow: hidden;
        transition: transform 0.25s, box-shadow 0.25s;
        min-height: 240px;
    }
    .lsb-fc-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 90% 10%, rgba(0,201,167,0.12) 0%, transparent 60%);
        pointer-events: none;
    }
    .lsb-fc-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 48px rgba(13,27,42,0.25);
    }

    /* Edit pencil icon */
    .lsb-fc-card-edit {
        position: absolute;
        top: 16px;
        right: 16px;
        opacity: 0.25;
        font-size: 0.8rem;
        color: var(--white);
    }

    /* Pin */
    .lsb-fc-pin {
        font-size: 1.4rem;
        margin-bottom: 16px;
        display: block;
        position: relative;
        z-index: 1;
    }

    /* City name */
    .lsb-fc-name {
        font-family: 'Syne', sans-serif;
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--teal);
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-bottom: 6px;
        position: relative;
        z-index: 1;
    }

    /* Meta line */
    .lsb-fc-meta {
        font-size: 0.82rem;
        color: rgba(255,255,255,0.5);
        margin-bottom: 4px;
        font-family: 'DM Sans', sans-serif;
        position: relative;
        z-index: 1;
    }

    .lsb-fc-neighborhoods {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.35);
        margin-bottom: 20px;
        font-family: 'DM Sans', sans-serif;
        font-style: italic;
        position: relative;
        z-index: 1;
    }

    /* Industry tags */
    .lsb-fc-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: auto;
        padding-top: 16px;
        position: relative;
        z-index: 1;
    }

    .lsb-fc-tag {
        background: rgba(0,201,167,0.1);
        border: 1px solid rgba(0,201,167,0.2);
        color: var(--teal);
        font-size: 0.73rem;
        padding: 4px 10px;
        border-radius: 100px;
        font-weight: 500;
        font-family: 'DM Sans', sans-serif;
    }

    .lsb-fc-tag-more {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.4);
        font-size: 0.73rem;
        padding: 4px 10px;
        border-radius: 100px;
        font-weight: 500;
        font-family: 'DM Sans', sans-serif;
    }

    @media (max-width: 768px) {
        .lsb-fc-grid { grid-template-columns: 1fr; }
        .lsb-fc-section { padding: 60px 20px; }
    }
    </style>

    <section class="lsb-fc-section">
      <div class="lsb-fc-inner">

        <!-- Header -->
        <div class="lsb-fc-header">
          <div>
            <span class="lsb-fc-label">Explore by Location</span>
            <h2 class="lsb-fc-title">Featured Cities</h2>
          </div>
          <a href="/cities/" class="lsb-fc-view-all">
            View all <?php echo wp_count_posts( 'city' )->publish; ?> →
          </a>
        </div>

        <!-- City Cards -->
        <div class="lsb-fc-grid">
          <?php foreach ( $city_data as $city ) :

            // Show max 4 tags, rest as "+X more"
            $visible_tags = array_slice( $city['tags'], 0, 4 );
            $extra        = count( $city['tags'] ) - count( $visible_tags );

          ?>
          <a href="<?php echo esc_url( $city['url'] ); ?>" class="lsb-fc-card">

            <span class="lsb-fc-card-edit">✎</span>
            <span class="lsb-fc-pin">📍</span>

            <div class="lsb-fc-name"><?php echo esc_html( $city['name'] ); ?></div>

            <div class="lsb-fc-meta">
              <?php echo esc_html( $city['businesses'] ); ?>+ businesses
              · <?php echo esc_html( $city['industries'] ); ?> industries
            </div>

            <?php if ( $city['has_neighborhoods'] ) : ?>
              <div class="lsb-fc-neighborhoods">Neighborhoods available</div>
            <?php endif; ?>

            <?php if ( ! empty( $visible_tags ) ) : ?>
            <div class="lsb-fc-tags">
              <?php foreach ( $visible_tags as $tag ) : ?>
                <span class="lsb-fc-tag"><?php echo esc_html( $tag ); ?></span>
              <?php endforeach; ?>
              <?php if ( $extra > 0 ) : ?>
                <span class="lsb-fc-tag-more">+<?php echo $extra; ?> more</span>
              <?php endif; ?>
            </div>
            <?php endif; ?>

          </a>
          <?php endforeach; ?>
        </div>

      </div>
    </section>

    <?php return ob_get_clean();
}

endif;

add_shortcode( 'lsb_featured_cities', 'lsb_featured_cities_shortcode' );