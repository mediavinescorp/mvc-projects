<?php
/**
 * Shortcode: [lsb_industries_grid]
 * Industries Overview Page - Industries Grid Section
 *
 * File location: /shortcodes/industries-grid.php
 * Usage: [lsb_industries_grid] in Elementor HTML widget
 */

add_shortcode( 'lsb_industries_grid', function() {

    $industries = new WP_Query( [
        'post_type'      => 'industries',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    if ( ! $industries->have_posts() ) {
        return '';
    }

    ob_start(); ?>

    <section class="lsb-ind-grid-section">
      <div class="lsb-ind-grid-inner">
        <div class="lsb-ind-grid">

          <?php while ( $industries->have_posts() ) : $industries->the_post();

            $slug         = get_post_field( 'post_name', get_the_ID() );
            $name         = get_the_title();
            $url          = home_url( '/industries/' . $slug . '/' );
            $description  = get_field( 'industry_description' );
            $services_raw = get_field( 'service_type' );
            $has_image    = has_post_thumbnail();
            $image_url    = $has_image ? get_the_post_thumbnail_url( get_the_ID(), 'medium' ) : '';

            // Parse service tags — supports comma-separated string or array/repeater
            $service_tags = [];
            if ( is_array( $services_raw ) ) {
                foreach ( $services_raw as $item ) {
                    $service_tags[] = is_array( $item ) ? reset( $item ) : $item;
                }
            } elseif ( is_string( $services_raw ) && ! empty( $services_raw ) ) {
                $service_tags = array_map( 'trim', explode( ',', $services_raw ) );
            }
            $service_tags = array_slice( $service_tags, 0, 5 );

          ?>

          <a href="<?php echo esc_url( $url ); ?>" class="lsb-ind-card" aria-label="<?php echo esc_attr( $name ); ?> services">

            <div class="lsb-ind-card__image">
              <?php if ( $has_image ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" width="400" height="220">
              <?php else : ?>
                <div class="lsb-ind-card__image-placeholder" aria-hidden="true">
                  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                </div>
              <?php endif; ?>
              <div class="lsb-ind-card__image-overlay" aria-hidden="true"></div>
            </div>

            <div class="lsb-ind-card__body">

              <h3 class="lsb-ind-card__name"><?php echo esc_html( $name ); ?></h3>

              <?php if ( $description ) : ?>
                <p class="lsb-ind-card__desc"><?php echo esc_html( wp_trim_words( $description, 20, '...' ) ); ?></p>
              <?php endif; ?>

              <?php if ( ! empty( $service_tags ) ) : ?>
                <div class="lsb-ind-card__tags" aria-label="Services offered">
                  <?php foreach ( $service_tags as $tag ) : ?>
                    <span class="lsb-ind-card__tag"><?php echo esc_html( $tag ); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <div class="lsb-ind-card__cta">
                <span class="lsb-ind-card__cta-btn">
                  Browse <?php echo esc_html( $name ); ?>
                  <span class="lsb-ind-card__cta-arrow" aria-hidden="true">&#8594;</span>
                </span>
              </div>

            </div>

          </a>

          <?php endwhile; wp_reset_postdata(); ?>

        </div>
      </div>
    </section>

    <style>
    .lsb-ind-grid-section { background: var(--white, #ffffff); padding: 80px 40px; }
    .lsb-ind-grid-inner { max-width: 1200px; margin: 0 auto; }
    .lsb-ind-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; }
    .lsb-ind-card { background: var(--white, #ffffff); border: 1px solid var(--border, #E4EAF2); border-radius: 16px; overflow: hidden; text-decoration: none; display: flex; flex-direction: column; transition: all 0.25s ease; position: relative; }
    .lsb-ind-card::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--teal, #00C9A7), var(--teal-dark, #00A88C)); transform: scaleX(0); transition: transform 0.3s ease; transform-origin: left; }
    .lsb-ind-card:hover { border-color: rgba(0,201,167,0.35); transform: translateY(-5px); box-shadow: 0 16px 40px rgba(0,201,167,0.1), 0 4px 12px rgba(0,0,0,0.06); }
    .lsb-ind-card:hover::after { transform: scaleX(1); }
    .lsb-ind-card__image { position: relative; width: 100%; height: 200px; overflow: hidden; background: var(--navy, #0D1B2A); flex-shrink: 0; }
    .lsb-ind-card__image img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.4s ease; }
    .lsb-ind-card:hover .lsb-ind-card__image img { transform: scale(1.04); }
    .lsb-ind-card__image-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--navy-mid, #1B2F45); color: rgba(255,255,255,0.2); }
    .lsb-ind-card__image-overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, transparent 40%, rgba(13,27,42,0.45) 100%); pointer-events: none; }
    .lsb-ind-card__body { padding: 24px 28px 28px; display: flex; flex-direction: column; gap: 12px; flex: 1; }
    .lsb-ind-card__name { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.25rem; color: var(--navy, #0D1B2A); letter-spacing: -0.02em; line-height: 1.2; margin: 0; }
    .lsb-ind-card__desc { font-family: 'DM Sans', sans-serif; font-size: 0.9rem; color: var(--muted, #8A9BB0); line-height: 1.7; font-weight: 300; margin: 0; flex: 1; }
    .lsb-ind-card__tags { display: flex; flex-wrap: wrap; gap: 6px; }
    .lsb-ind-card__tag { background: var(--off-white, #F5F7FA); border: 1px solid var(--border, #E4EAF2); color: var(--text, #1A2535); font-family: 'DM Sans', sans-serif; font-size: 0.75rem; padding: 4px 10px; border-radius: 100px; font-weight: 400; transition: all 0.2s ease; white-space: nowrap; }
    .lsb-ind-card:hover .lsb-ind-card__tag { background: rgba(0,201,167,0.06); border-color: rgba(0,201,167,0.2); color: var(--teal-dark, #00A88C); }
    .lsb-ind-card__cta { padding-top: 16px; border-top: 1px solid var(--border, #E4EAF2); margin-top: auto; }
    .lsb-ind-card__cta-btn { display: inline-flex; align-items: center; gap: 6px; background: var(--navy, #0D1B2A); color: #ffffff; font-family: 'Syne', sans-serif; font-weight: 600; font-size: 0.82rem; padding: 10px 20px; border-radius: 8px; letter-spacing: 0.01em; transition: all 0.2s ease; white-space: nowrap; }
    .lsb-ind-card:hover .lsb-ind-card__cta-btn { background: var(--teal, #00C9A7); color: var(--navy, #0D1B2A); }
    .lsb-ind-card__cta-arrow { transition: transform 0.2s ease; display: inline-block; }
    .lsb-ind-card:hover .lsb-ind-card__cta-arrow { transform: translateX(4px); }
    @media (max-width: 1024px) { .lsb-ind-grid-section { padding: 60px 32px; } .lsb-ind-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; } }
    @media (max-width: 768px) { .lsb-ind-grid-section { padding: 48px 20px; } .lsb-ind-grid { grid-template-columns: 1fr; gap: 16px; } .lsb-ind-card__image { height: 180px; } .lsb-ind-card__body { padding: 20px 20px 24px; } }
    </style>

    <?php
    return ob_get_clean();
});