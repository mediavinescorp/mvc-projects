<?php
/**
 * Shortcode: [lsb_industry_service_filter]
 * Individual Industry Page - Service Filter Bar
 *
 * File location: /shortcodes/industry-service-filter.php
 * Usage: [lsb_industry_service_filter] in Elementor HTML widget on single Industry page
 *
 * Pulls all Services linked to the current industry via industry_cat taxonomy
 * and renders them as clickable buttons linking to /industries/{industry}/{service}/
 */

add_shortcode( 'lsb_industry_service_filter', function() {
    ob_start();

    // ── Current Industry Post ──────────────────────────────────────────────
    $post_id       = get_queried_object_id();
    $industry_slug = get_post_field( 'post_name', $post_id );
    $industry_name = get_the_title( $post_id );

    // ── Get all Services for this industry ────────────────────────────────
    $services = get_posts( [
        'post_type'      => 'services',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => [
            [
                'taxonomy' => 'industry_cat',
                'field'    => 'slug',
                'terms'    => $industry_slug,
            ],
        ],
    ] );

    if ( empty( $services ) ) {
        return '';
    }

    ?>

    <section class="lsb-svc-filter" aria-label="Filter by service">
      <div class="lsb-svc-filter__inner">

        <p class="lsb-svc-filter__label">Browse by Service</p>

        <div class="lsb-svc-filter__tags" role="list">
          <?php foreach ( $services as $service ) :
            $service_slug = $service->post_name;
            $service_name = $service->post_title;
            $service_url  = home_url( '/industries/' . $industry_slug . '/' . $service_slug . '/' );
          ?>
            <a
              href="<?php echo esc_url( $service_url ); ?>"
              class="lsb-svc-filter__tag"
              role="listitem"
              title="<?php echo esc_attr( $industry_name . ' — ' . $service_name ); ?>"
            >
              <?php echo esc_html( $service_name ); ?>
            </a>
          <?php endforeach; ?>
        </div>

      </div>
    </section>

    <style>
    .lsb-svc-filter {
      background: var(--navy-mid, #1B2F45);
      padding: 32px 40px;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }
    .lsb-svc-filter__inner {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      gap: 20px;
      flex-wrap: wrap;
    }
    .lsb-svc-filter__label {
      font-family: 'DM Sans', sans-serif;
      font-size: 0.78rem;
      font-weight: 600;
      color: rgba(255, 255, 255, 0.35);
      text-transform: uppercase;
      letter-spacing: 0.1em;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .lsb-svc-filter__tags {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .lsb-svc-filter__tag {
      display: inline-block;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: rgba(255, 255, 255, 0.65);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.82rem;
      font-weight: 400;
      padding: 6px 14px;
      border-radius: 100px;
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .lsb-svc-filter__tag:hover {
      background: var(--teal, #00C9A7);
      border-color: var(--teal, #00C9A7);
      color: var(--navy, #0D1B2A);
      transform: translateY(-1px);
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .lsb-svc-filter { padding: 28px 32px; }
    }
    @media (max-width: 768px) {
      .lsb-svc-filter { padding: 24px 20px; }
      .lsb-svc-filter__inner { flex-direction: column; align-items: flex-start; gap: 14px; }
      .lsb-svc-filter__label { margin-bottom: 0; }
    }
    </style>

    <?php
    return ob_get_clean();
});