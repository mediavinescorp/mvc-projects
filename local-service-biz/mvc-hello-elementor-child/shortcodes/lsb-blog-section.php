<?php
/**
 * Shortcode: Blog Section
 * File:      shortcodes/lsb-blog-section.php
 * Usage:     [lsb_blog_section]
 *
 * Attributes (all optional, use slugs):
 *   industry="hvac"
 *   city="los-angeles"
 *   service="ac-installation"
 *   count="4"
 *
 * Examples:
 *   [lsb_blog_section]
 *   [lsb_blog_section industry="hvac"]
 *   [lsb_blog_section city="los-angeles"]
 *   [lsb_blog_section industry="roofing" city="pasadena"]
 *   [lsb_blog_section service="ac-installation" count="4"]
 *
 * require_once get_stylesheet_directory() . '/shortcodes/lsb-blog-section.php';
 */

if ( ! function_exists( 'lsb_blog_section_shortcode' ) ) :

function lsb_blog_section_shortcode( $atts ) {

    $atts = shortcode_atts([
        'industry' => '',
        'city'     => '',
        'service'  => '',
        'count'    => 4,
    ], $atts );

    // ── Build tax_query from attributes ──────────────────
    $tax_query = [];

    if ( ! empty( $atts['industry'] ) ) {
        $tax_query[] = [
            'taxonomy' => 'industry_cat',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $atts['industry'] ),
        ];
    }

    if ( ! empty( $atts['city'] ) ) {
        $tax_query[] = [
            'taxonomy' => 'city_cat',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $atts['city'] ),
        ];
    }

    if ( ! empty( $atts['service'] ) ) {
        $tax_query[] = [
            'taxonomy' => 'service_type',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $atts['service'] ),
        ];
    }

    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }

    // ── Query posts ───────────────────────────────────────
    $args = [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['count'] ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    $posts = new WP_Query( $args );

    if ( ! $posts->have_posts() ) {
        return '';
    }

    // ── Build section label ───────────────────────────────
    $label_parts = [];
    if ( ! empty( $atts['industry'] ) ) {
        $term = get_term_by( 'slug', $atts['industry'], 'industry_cat' );
        if ( $term ) $label_parts[] = $term->name;
    }
    if ( ! empty( $atts['city'] ) ) {
        $term = get_term_by( 'slug', $atts['city'], 'city_cat' );
        if ( $term ) $label_parts[] = $term->name;
    }
    if ( ! empty( $atts['service'] ) ) {
        $term = get_term_by( 'slug', $atts['service'], 'service_type' );
        if ( $term ) $label_parts[] = $term->name;
    }

    $section_label = ! empty( $label_parts )
        ? implode( ' · ', $label_parts )
        : 'Latest Articles';

    static $styles_printed = false;

    ob_start(); ?>

    <?php if ( ! $styles_printed ) :
        $styles_printed = true; ?>
    <style>
    .lsb-blog-section {
        background: #F5F7FA;
        padding: 80px 40px;
    }
    .lsb-blog-inner {
        max-width: 1200px;
        margin: 0 auto;
    }
    .lsb-blog-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 36px;
    }
    .lsb-blog-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #00C9A7;
        margin-bottom: 8px;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-blog-title {
        font-family: 'Syne', sans-serif;
        font-size: clamp(1.6rem, 3vw, 2.2rem);
        font-weight: 800;
        color: #0D1B2A;
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin: 0;
        padding: 0;
    }
    .lsb-blog-view-all {
        color: #00C9A7;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        font-family: 'DM Sans', sans-serif;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        transition: gap 0.2s;
    }
    .lsb-blog-view-all:hover { gap: 10px; color: #00C9A7; }

    /* ── Grid — 4 in a row ─────────────────────────────── */
    .lsb-blog-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    /* ── Card ──────────────────────────────────────────── */
    .lsb-blog-card {
        background: #ffffff;
        border: 1px solid #E4EAF2;
        border-radius: 16px;
        overflow: hidden;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        transition: all 0.25s;
    }
    .lsb-blog-card:hover {
        border-color: #00C9A7;
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.07);
    }
    .lsb-blog-card-img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        display: block;
        background: #1B2F45;
    }
    .lsb-blog-card-placeholder {
        width: 100%;
        height: 160px;
        background: linear-gradient(135deg, #0D1B2A, #1B2F45);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        flex-shrink: 0;
    }
    .lsb-blog-card-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .lsb-blog-card-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
    }
    .lsb-blog-card-date {
        font-size: 0.73rem;
        color: #8A9BB0;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-blog-card-cat {
        font-size: 0.7rem;
        font-weight: 600;
        color: #00A88C;
        background: rgba(0,201,167,0.08);
        border: 1px solid rgba(0,201,167,0.15);
        border-radius: 100px;
        padding: 2px 8px;
        font-family: 'DM Sans', sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .lsb-blog-card-title {
        font-family: 'Syne', sans-serif;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0D1B2A;
        line-height: 1.35;
        margin: 0 0 10px;
        letter-spacing: -0.01em;
    }
    .lsb-blog-card-excerpt {
        font-size: 0.82rem;
        color: #8A9BB0;
        line-height: 1.6;
        font-weight: 300;
        font-family: 'DM Sans', sans-serif;
        flex: 1;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 16px;
    }
    .lsb-blog-card-read {
        font-size: 0.82rem;
        font-weight: 600;
        color: #00C9A7;
        font-family: 'DM Sans', sans-serif;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: auto;
        transition: gap 0.2s;
    }
    .lsb-blog-card:hover .lsb-blog-card-read { gap: 8px; }

    @media (max-width: 1024px) {
        .lsb-blog-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .lsb-blog-grid { grid-template-columns: 1fr; }
        .lsb-blog-section { padding: 60px 20px; }
    }
    </style>
    <?php endif; ?>

    <section class="lsb-blog-section">
      <div class="lsb-blog-inner">

        <div class="lsb-blog-header">
          <div>
            <span class="lsb-blog-label"><?php echo esc_html( $section_label ); ?></span>
            <h2 class="lsb-blog-title">Latest Articles</h2>
          </div>
          <a href="/blog/" class="lsb-blog-view-all">View All Posts →</a>
        </div>

        <div class="lsb-blog-grid">
          <?php while ( $posts->have_posts() ) : $posts->the_post();

            $thumb   = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
            $date    = get_the_date( 'M j, Y' );
            $excerpt = wp_trim_words( get_the_excerpt(), 18 );
            $link    = get_permalink();
            $title   = get_the_title();

            // Industry tag for the card
            $ind_terms = get_the_terms( get_the_ID(), 'industry_cat' );
            $cat_label = ( $ind_terms && ! is_wp_error( $ind_terms ) ) ? $ind_terms[0]->name : '';

          ?>
          <a href="<?php echo esc_url( $link ); ?>" class="lsb-blog-card">

            <?php if ( $thumb ) : ?>
              <img src="<?php echo esc_url( $thumb ); ?>"
                   alt="<?php echo esc_attr( $title ); ?>"
                   class="lsb-blog-card-img">
            <?php else : ?>
              <div class="lsb-blog-card-placeholder">📝</div>
            <?php endif; ?>

            <div class="lsb-blog-card-body">

              <div class="lsb-blog-card-meta">
                <span class="lsb-blog-card-date"><?php echo esc_html( $date ); ?></span>
                <?php if ( $cat_label ) : ?>
                  <span class="lsb-blog-card-cat"><?php echo esc_html( $cat_label ); ?></span>
                <?php endif; ?>
              </div>

              <div class="lsb-blog-card-title"><?php echo esc_html( $title ); ?></div>

              <?php if ( $excerpt ) : ?>
                <div class="lsb-blog-card-excerpt"><?php echo esc_html( $excerpt ); ?></div>
              <?php endif; ?>

              <span class="lsb-blog-card-read">Read More →</span>

            </div>
          </a>
          <?php endwhile; wp_reset_postdata(); ?>
        </div>

      </div>
    </section>

    <?php return ob_get_clean();
}

endif;

add_shortcode( 'lsb_blog_section', 'lsb_blog_section_shortcode' );