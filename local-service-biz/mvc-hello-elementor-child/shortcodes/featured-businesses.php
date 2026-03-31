<?php
/**
 * Featured Businesses Section Shortcode
 * Usage: [featured_businesses]
 * Add to: child theme functions.php
 */

if ( ! function_exists( 'lsb_home_featured_businesses_shortcode' ) ) :
function lsb_home_featured_businesses_shortcode() {

  static $styles_printed = false;

 // Daily random seed (rotates once per day)
$seed = date('Ymd');

global $wpdb;
$wpdb->query( "SET SESSION rand_seed1 = $seed" );

$args = array(
  'post_type'      => 'businesses',
  'posts_per_page' => 3,
  'post_status'    => 'publish',
  'orderby'        => 'rand',
  'meta_query'     => array(
    array(
      'key'     => 'featured_business',
      'value'   => '1',
      'compare' => '=',
    ),
  ),
);

$businesses = new WP_Query( $args );

  $businesses = new WP_Query( $args );

  if ( ! $businesses->have_posts() ) {
    return '<p>No featured businesses found.</p>';
  }

  $first = true;
  ob_start();

  if ( ! $styles_printed ) :
    $styles_printed = true;
  ?>
  <style>
    section.fb-section {
      background: #ffffff !important;
      padding: 100px 40px !important;
      margin: 0 !important;
      position: relative !important;
      box-sizing: border-box !important;
    }
    section.fb-section * { box-sizing: border-box; }
    section.fb-section .fb-inner { max-width: 1200px; margin: 0 auto; width: 100%; }
    section.fb-section .fb-header {
      display: flex !important;
      justify-content: space-between !important;
      align-items: flex-end !important;
      margin-bottom: 48px !important;
      gap: 20px !important;
    }
    section.fb-section .fb-label {
      display: block !important;
      font-size: 0.75rem !important;
      font-weight: 600 !important;
      letter-spacing: 0.12em !important;
      text-transform: uppercase !important;
      color: #00C9A7 !important;
      margin-bottom: 10px !important;
      font-family: 'DM Sans', sans-serif !important;
    }
    section.fb-section .fb-title {
      font-family: 'Syne', sans-serif !important;
      font-size: clamp(1.8rem, 3.5vw, 2.8rem) !important;
      font-weight: 800 !important;
      color: #0D1B2A !important;
      letter-spacing: -0.02em !important;
      line-height: 1.1 !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    section.fb-section .fb-view-all {
      font-family: 'DM Sans', sans-serif !important;
      font-size: 0.9rem !important;
      font-weight: 500 !important;
      color: #00C9A7 !important;
      text-decoration: none !important;
      display: flex !important;
      align-items: center !important;
      gap: 6px !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      background: none !important;
      border: none !important;
      padding: 0 !important;
      margin: 0 !important;
      line-height: 1 !important;
      transition: gap 0.2s !important;
    }
    section.fb-section .fb-view-all:hover { gap: 10px !important; }
 section.fb-section .fb-grid {
  display: grid !important;
  grid-template-columns: 1fr !important;
  gap: 20px !important;
  align-items: stretch !important;
}
    section.fb-section .fb-card {
      background: #F5F7FA !important;
      border: 1px solid #E4EAF2 !important;
      border-radius: 20px !important;
      padding: 28px !important;
      display: flex !important;
      flex-direction: column !important;
      transition: all 0.25s !important;
      position: relative !important;
      overflow: hidden !important;
      text-decoration: none !important;
    }
    section.fb-section .fb-card:hover {
      border-color: rgba(0,201,167,0.3) !important;
      box-shadow: 0 16px 40px rgba(0,0,0,0.08) !important;
      transform: translateY(-4px) !important;
      background: #ffffff !important;
    }
    section.fb-section .fb-card--featured {
      background: #0D1B2A !important;
      border-color: transparent !important;
      padding: 36px !important;
    }
    section.fb-section .fb-card--featured::before {
      content: '' !important;
      position: absolute !important;
      inset: 0 !important;
      background: radial-gradient(ellipse at 90% 10%, rgba(0,201,167,0.15) 0%, transparent 60%) !important;
      pointer-events: none !important;
    }
    section.fb-section .fb-card--featured:hover {
      background: #1B2F45 !important;
      border-color: rgba(0,201,167,0.2) !important;
      box-shadow: 0 20px 48px rgba(13,27,42,0.3) !important;
    }
    section.fb-section .fb-featured-badge {
      position: absolute !important;
      top: 20px !important;
      right: 20px !important;
      background: #F4C542 !important;
      color: #0D1B2A !important;
      font-family: 'Syne', sans-serif !important;
      font-size: 0.65rem !important;
      font-weight: 700 !important;
      letter-spacing: 0.1em !important;
      text-transform: uppercase !important;
      padding: 4px 10px !important;
      border-radius: 100px !important;
      z-index: 1 !important;
      display: inline-block !important;
    }
    section.fb-section .fb-card-top {
      display: flex !important;
      align-items: center !important;
      gap: 14px !important;
      margin-bottom: 18px !important;
    }
    section.fb-section .fb-logo {
      width: 52px !important;
      height: 52px !important;
      border-radius: 12px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-family: 'Syne', sans-serif !important;
      font-weight: 800 !important;
      font-size: 1rem !important;
      flex-shrink: 0 !important;
      background: rgba(0,201,167,0.12) !important;
      color: #00C9A7 !important;
      overflow: hidden !important;
    }
    section.fb-section .fb-logo img {
      width: 100% !important;
      height: 100% !important;
      object-fit: cover !important;
      border-radius: 12px !important;
      display: block !important;
    }
    section.fb-section .fb-info { flex: 1 !important; min-width: 0 !important; }
    section.fb-section .fb-name {
      font-family: 'Syne', sans-serif !important;
      font-weight: 700 !important;
      font-size: 1rem !important;
      color: #0D1B2A !important;
      margin: 0 0 4px !important;
      padding: 0 !important;
      line-height: 1.2 !important;
    }
    section.fb-section .fb-card--featured .fb-name { color: #ffffff !important; }
    section.fb-section .fb-niche {
      font-size: 0.78rem !important;
      color: #00C9A7 !important;
      font-weight: 500 !important;
      font-family: 'DM Sans', sans-serif !important;
      margin: 0 !important;
      line-height: 1 !important;
    }
    section.fb-section .fb-rating {
      display: flex !important;
      align-items: center !important;
      gap: 3px !important;
      font-size: 0.82rem !important;
      color: #F4C542 !important;
      font-weight: 700 !important;
      font-family: 'DM Sans', sans-serif !important;
      flex-shrink: 0 !important;
      white-space: nowrap !important;
    }
    section.fb-section .fb-desc {
      font-size: 0.88rem !important;
      color: #8A9BB0 !important;
      line-height: 1.65 !important;
      margin-bottom: 18px !important;
      font-weight: 300 !important;
      font-family: 'DM Sans', sans-serif !important;
      flex-grow: 1 !important;
    }
    section.fb-section .fb-card--featured .fb-desc { color: rgba(255,255,255,0.45) !important; }
    section.fb-section .fb-tags {
      display: flex !important;
      flex-wrap: wrap !important;
      gap: 6px !important;
      margin-bottom: 20px !important;
    }
    section.fb-section .fb-tag {
      background: rgba(0,201,167,0.08) !important;
      border: 1px solid rgba(0,201,167,0.15) !important;
      color: #00A88C !important;
      font-size: 0.73rem !important;
      padding: 4px 10px !important;
      border-radius: 100px !important;
      font-weight: 500 !important;
      font-family: 'DM Sans', sans-serif !important;
      display: inline-block !important;
      line-height: 1.4 !important;
    }
    section.fb-section .fb-card--featured .fb-tag {
      background: rgba(0,201,167,0.1) !important;
      border-color: rgba(0,201,167,0.2) !important;
      color: #00C9A7 !important;
    }
    section.fb-section .fb-footer {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding-top: 16px !important;
      border-top: 1px solid #E4EAF2 !important;
      margin-top: auto !important;
      gap: 10px !important;
    }
    section.fb-section .fb-card--featured .fb-footer { border-top-color: rgba(255,255,255,0.08) !important; }
    section.fb-section .fb-location {
      font-size: 0.8rem !important;
      color: #8A9BB0 !important;
      font-family: 'DM Sans', sans-serif !important;
      display: flex !important;
      align-items: center !important;
      gap: 4px !important;
      margin: 0 !important;
    }
    section.fb-section .fb-card--featured .fb-location { color: rgba(255,255,255,0.35) !important; }
    section.fb-section .fb-btn {
      background: #0D1B2A !important;
      color: #ffffff !important;
      font-family: 'Syne', sans-serif !important;
      font-weight: 600 !important;
      font-size: 0.78rem !important;
      padding: 8px 18px !important;
      border-radius: 8px !important;
      text-decoration: none !important;
      transition: all 0.2s !important;
      display: inline-block !important;
      line-height: 1.4 !important;
      flex-shrink: 0 !important;
    }
    section.fb-section .fb-btn:hover { background: #00C9A7 !important; color: #0D1B2A !important; }
    section.fb-section .fb-card--featured .fb-btn { background: #00C9A7 !important; color: #0D1B2A !important; }
    section.fb-section .fb-card--featured .fb-btn:hover { background: #00A88C !important; color: #0D1B2A !important; }


@media (max-width: 1024px) {
  section.fb-section .fb-grid {
    grid-template-columns: 1fr 1fr !important;
  }
}

@media (max-width: 767px) {
  section.fb-section {
    padding: 70px 20px !important;
  }

  section.fb-section .fb-header {
    flex-direction: column !important;
    align-items: flex-start !important;
  }

  section.fb-section .fb-grid {
    grid-template-columns: 1fr !important;
  }
}


  </style>
  <?php endif; ?>

  <section class="fb-section">
    <div class="fb-inner">

      <div class="fb-header">
        <div>
          <span class="fb-label">Featured Professionals</span>
          <h2 class="fb-title">Top Rated Local Businesses</h2>
        </div>
        <a href="<?php echo esc_url( get_post_type_archive_link( 'businesses' ) ); ?>" class="fb-view-all">
          View All Businesses →
        </a>
      </div>

      <div class="fb-grid">

      <?php while ( $businesses->have_posts() ) : $businesses->the_post();

        $business_name  = get_the_title();
        $logo           = get_field( 'business_logo' );
        $address        = get_field( 'business_address' );
        $description    = get_field( 'business_description' );
        $rating         = get_field( 'business_rating' );
        $permalink      = get_permalink();

        // Industry taxonomy — slug: industry_cat
        $industries     = get_the_terms( get_the_ID(), 'industry_cat' );
        $industry_name  = ( $industries && ! is_wp_error( $industries ) )
                          ? $industries[0]->name : '';

        // Services taxonomy — slug: service_type (show up to 4 as tags)
        $services       = get_the_terms( get_the_ID(), 'service_type' );

        // City from address fallback or taxonomy if you add one later
        // Pull city from business_address or add a city taxonomy later

        // Initials fallback if no logo
        $initials = '';
        foreach ( explode( ' ', $business_name ) as $word ) {
          $initials .= strtoupper( substr( $word, 0, 1 ) );
          if ( strlen( $initials ) >= 2 ) break;
        }

        $card_class = $first ? 'fb-card fb-card--featured' : 'fb-card';
      ?>

        <div class="<?php echo esc_attr( $card_class ); ?>">

          <?php if ( $first ) : ?>
            <span class="fb-featured-badge">⭐ Top Rated</span>
          <?php endif; ?>

          <div class="fb-card-top">
            <div class="fb-logo">
              <?php if ( $logo ) : ?>
                <img src="<?php echo esc_url( $logo['url'] ); ?>"
                     alt="<?php echo esc_attr( $business_name ); ?>">
              <?php else : ?>
                <?php echo esc_html( $initials ); ?>
              <?php endif; ?>
            </div>
            <div class="fb-info">
              <div class="fb-name"><?php echo esc_html( $business_name ); ?></div>
              <?php if ( $industry_name ) : ?>
                <div class="fb-niche"><?php echo esc_html( $industry_name ); ?></div>
              <?php endif; ?>
            </div>
            <?php if ( $rating ) : ?>
              <div class="fb-rating">★ <?php echo esc_html( number_format( (float) $rating, 1 ) ); ?></div>
            <?php endif; ?>
          </div>

          <?php if ( $description ) : ?>
            <p class="fb-desc">
              <?php echo wp_trim_words( wp_strip_all_tags( $description ), 28, '...' ); ?>
            </p>
          <?php endif; ?>

          <?php if ( $services && ! is_wp_error( $services ) ) : ?>
            <div class="fb-tags">
              <?php
              $count = 0;
              foreach ( $services as $service ) :
                if ( $count >= 4 ) break;
              ?>
                <span class="fb-tag"><?php echo esc_html( $service->name ); ?></span>
              <?php
                $count++;
              endforeach;
              ?>
            </div>
          <?php endif; ?>

          <div class="fb-footer">
            <?php if ( $address ) : ?>
              <span class="fb-location">📍 <?php echo esc_html( $address ); ?></span>
            <?php endif; ?>
            <a href="<?php echo esc_url( $permalink ); ?>" class="fb-btn">View Profile →</a>
          </div>

        </div>

      <?php
        $first = false;
      endwhile;
      wp_reset_postdata();
      ?>

      </div>
    </div>
  </section>

  <?php
  return ob_get_clean();
}

endif;

add_shortcode( 'home_featured_businesses', 'lsb_home_featured_businesses_shortcode' );

