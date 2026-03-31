<?php
/**
 * Shortcode: [lsb_industries_sections]
 * Industries Overview Page - Why, Popular Searches, SEO Text, CTA Banner
 *
 * File location: /shortcodes/industries-sections.php
 * Usage: [lsb_industries_sections] in Elementor HTML widget
 */

add_shortcode( 'lsb_industries_sections', function() {
    ob_start();

    // Ensure Syne + DM Sans are loaded
    wp_enqueue_style( 'lsb-google-fonts', 'https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap', [], null );

    // Pull cities dynamically for popular searches
    $cities = new WP_Query( [
        'post_type'      => 'cities',
        'post_status'    => 'publish',
        'posts_per_page' => 6,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ] );

    // Pull industries dynamically for popular searches
    $industries = new WP_Query( [
        'post_type'      => 'industries',
        'post_status'    => 'publish',
        'posts_per_page' => 6,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    // Build popular search combos
    $combos = [];
    if ( $industries->have_posts() && $cities->have_posts() ) {
        $ind_list  = [];
        $city_list = [];
        while ( $industries->have_posts() ) {
            $industries->the_post();
            $ind_list[] = [
                'name' => get_the_title(),
                'slug' => get_post_field( 'post_name', get_the_ID() ),
                'icon' => get_field( 'industry_icon' ) ?: '&#127962;',
            ];
        }
        wp_reset_postdata();
        while ( $cities->have_posts() ) {
            $cities->the_post();
            $city_list[] = [
                'name' => get_the_title(),
                'slug' => get_post_field( 'post_name', get_the_ID() ),
            ];
        }
        wp_reset_postdata();

        // Pair each industry with a city round-robin
        foreach ( $ind_list as $i => $ind ) {
            $city = $city_list[ $i % count( $city_list ) ];
            $combos[] = [
                'label'    => $ind['name'] . ' in ' . $city['name'],
                'url'      => home_url( '/industries/' . $ind['slug'] . '/' . $city['slug'] . '/' ),
                'icon'     => $ind['icon'],
                'sub'      => 'Browse ' . $ind['name'] . ' professionals',
            ];
        }
    }

    ?>

    <!-- =====================================================================
         SECTION 1: WHY LOCALSERVICEBIZ
         ===================================================================== -->
    <section class="lsb-why">
      <div class="lsb-why__inner">

        <div class="lsb-why__header">
          <span class="lsb-slbl">Why LocalServiceBiz</span>
         <h2 class="lsb-sh lsb-sh--light">The Smarter Way to<br>Find Local Pros</h2>
<p class="lsb-ssub lsb-ssub--light">We built the most comprehensive service directory for Greater California &mdash; so you spend less time searching and more time getting things done.</p>
        </div>

        <div class="lsb-why__grid">

          <div class="lsb-why__item">
            <div class="lsb-why__icon" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="lsb-why__content">
              <h3 class="lsb-why__title">Verified Listings</h3>
              <p class="lsb-why__desc">Every profile includes real contact info, license details, and service areas. Connect with legitimate professionals &mdash; no lead forms, no middlemen.</p>
            </div>
          </div>

          <div class="lsb-why__item">
            <div class="lsb-why__icon" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="lsb-why__content">
              <h3 class="lsb-why__title">Hyper-Local Coverage</h3>
              <p class="lsb-why__desc">Covering cities across Greater California means you&rsquo;ll find pros that actually serve your neighborhood &mdash; from Beverly Hills to Long Beach, Malibu to Pasadena.</p>
            </div>
          </div>

          <div class="lsb-why__item">
            <div class="lsb-why__icon" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            </div>
            <div class="lsb-why__content">
              <h3 class="lsb-why__title">Direct Connection</h3>
              <p class="lsb-why__desc">Call, visit their site, or request a quote directly from their listing. No waiting, no bidding wars &mdash; just you and the professional, connected instantly.</p>
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- =====================================================================
         SECTION 2: POPULAR SEARCHES
         ===================================================================== -->
    <?php if ( ! empty( $combos ) ) : ?>
    <section class="lsb-popular">
      <div class="lsb-popular__inner">

        <span class="lsb-slbl">Trending Searches</span>
       <h2 class="lsb-sh lsb-sh--dark">Popular Industry + City Combinations</h2>
<p class="lsb-ssub lsb-ssub--dark">The most searched service categories across Greater California.</p>

        <div class="lsb-popular__grid">
          <?php foreach ( $combos as $combo ) : ?>
            <a href="<?php echo esc_url( $combo['url'] ); ?>" class="lsb-popular__link">
              <div class="lsb-popular__icon" aria-hidden="true"><?php echo $combo['icon']; ?></div>
              <div class="lsb-popular__text">
                <span class="lsb-popular__name"><?php echo esc_html( $combo['label'] ); ?></span>
                <span class="lsb-popular__sub"><?php echo esc_html( $combo['sub'] ); ?></span>
              </div>
              <span class="lsb-popular__arrow" aria-hidden="true">&#8594;</span>
            </a>
          <?php endforeach; ?>
        </div>

      </div>
    </section>
    <?php endif; ?>

    <!-- =====================================================================
         SECTION 3: SEO TEXT BLOCK
         ===================================================================== -->
    <section class="lsb-seo">
      <div class="lsb-seo__inner">
        <h2 class="lsb-seo__title">Home &amp; Local Service Professionals in Greater California</h2>
        <div class="lsb-seo__body">
          <p>LocalServiceBiz is Greater California&rsquo;s most comprehensive directory of <strong>local home service professionals</strong>. Whether you&rsquo;re dealing with a burst pipe at midnight, looking to sell your dental practice, or planning a corporate event for 500 guests &mdash; our platform connects you directly with the right expert in your neighborhood.</p>
          <p>We cover multiple service industries ranging from essential home maintenance trades like <strong>HVAC, roofing, and plumbing</strong> to specialized professional services including <strong>public adjusting, dental brokerage, and real estate</strong>. Our directory spans cities across California, ensuring hyper-local results that actually serve your address.</p>
          <p>Unlike national lead-generation platforms, LocalServiceBiz gives you <strong>direct access to business profiles</strong> &mdash; with real phone numbers, service details, and verified reviews &mdash; so you can contact the professional of your choice without barriers, fees, or waiting periods.</p>
        </div>
      </div>
    </section>

    <!-- =====================================================================
         SECTION 4: CTA BANNER
         ===================================================================== -->
    <section class="lsb-cta">
      <div class="lsb-cta__bg" aria-hidden="true"></div>
      <div class="lsb-cta__inner">
        <div class="lsb-cta__content">
          <span class="lsb-slbl">For Business Owners</span>
          <h2 class="lsb-cta__title">Own a Local Service Business?</h2>
          <p class="lsb-cta__sub">Join hundreds of verified businesses already connecting with customers across Greater California. Get listed today and start growing your business.</p>
        </div>
        <div class="lsb-cta__buttons">
          <a href="<?php echo esc_url( home_url( '/get-listed/' ) ); ?>" class="lsb-cta__btn-primary">Get Listed Today</a>
          <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="lsb-cta__btn-secondary">View Pricing</a>
        </div>
      </div>
    </section>

    <style>
   /* =========================================================================
   Shared utilities
   ========================================================================= */
.lsb-slbl {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #00C9A7;
  margin-bottom: 12px;
  font-family: 'DM Sans', sans-serif;
}

.lsb-sh {
  font-family: 'Syne', sans-serif !important;
  font-size: clamp(1.6rem, 3vw, 2.4rem) !important;
  font-weight: 800 !important;
  color: inherit;
  letter-spacing: -0.02em;
  line-height: 1.15;
  margin: 0 0 14px;
}

.lsb-sh--light {
  color: #ffffff !important;
}

.lsb-sh--dark {
  color: var(--navy, #0D1B2A) !important;
}

.lsb-ssub {
  font-family: 'DM Sans', sans-serif;
  font-size: 1rem;
  color: inherit;
  line-height: 1.75;
  font-weight: 300;
  max-width: 520px;
  margin: 0 0 48px;
}

.lsb-ssub--light {
  color: #ffffff !important;
}

.lsb-ssub--dark {
  color: var(--muted, #8A9BB0) !important;
}

    /* =========================================================================
       Section 1: Why LocalServiceBiz
       ========================================================================= */
    .lsb-why {
      background: var(--navy, #0D1B2A);
      padding: 80px 40px;
      position: relative;
      overflow: hidden;
    }
    .lsb-why::before {
      content: '';
      position: absolute;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(0,201,167,0.08) 0%, transparent 70%);
      top: -100px;
      right: -100px;
      pointer-events: none;
    }
    .lsb-why__inner {
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }
    .lsb-why__header {
      max-width: 600px;
      margin-bottom: 56px;
    }
    .lsb-why__grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2px;
      background: rgba(255,255,255,0.05);
      border-radius: 16px;
      overflow: hidden;
    }
    .lsb-why__item {
      background: rgba(255,255,255,0.03);
      padding: 40px 36px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      transition: background 0.2s;
    }
    .lsb-why__item:hover { background: rgba(255,255,255,0.06); }
    .lsb-why__icon {
      width: 48px;
      height: 48px;
      background: rgba(0,201,167,0.12);
      border: 1px solid rgba(0,201,167,0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--teal, #00C9A7);
      flex-shrink: 0;
    }
    .lsb-why__title {
      font-family: 'Syne', sans-serif !important;
      font-weight: 800 !important;
      font-size: 1.2rem !important;
      color: #ffffff !important;
      margin: 0;
      letter-spacing: -0.01em;
    }
    .lsb-why__desc {
      font-family: 'DM Sans', sans-serif;
      font-size: 0.9rem;
      color: #ffffff !important;
      line-height: 1.75;
      font-weight: 300;
      margin: 0;
    }

    /* =========================================================================
       Section 2: Popular Searches
       ========================================================================= */
    .lsb-popular {
      background: var(--off-white, #F5F7FA);
      padding: 80px 40px;
    }
    .lsb-popular__inner {
      max-width: 1200px;
      margin: 0 auto;
    }
    .lsb-popular__grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 12px;
    }
    .lsb-popular__link {
      background: #ffffff;
      border: 1px solid var(--border, #E4EAF2);
      border-radius: 10px;
      padding: 16px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .lsb-popular__link:hover {
      border-color: var(--teal, #00C9A7);
      background: rgba(0,201,167,0.03);
      transform: translateX(3px);
    }
    .lsb-popular__icon {
      font-size: 1.1rem;
      width: 38px;
      height: 38px;
      background: var(--off-white, #F5F7FA);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .lsb-popular__text {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .lsb-popular__name {
      font-family: 'Syne', sans-serif;
      font-weight: 600;
      font-size: 0.88rem;
      color: var(--navy, #0D1B2A);
    }
    .lsb-popular__sub {
      font-family: 'DM Sans', sans-serif;
      font-size: 0.75rem;
      color: var(--muted, #8A9BB0);
    }
    .lsb-popular__arrow {
      color: var(--teal, #00C9A7);
      font-size: 0.9rem;
      opacity: 0;
      transition: opacity 0.2s, transform 0.2s;
    }
    .lsb-popular__link:hover .lsb-popular__arrow {
      opacity: 1;
      transform: translateX(3px);
    }

    /* =========================================================================
       Section 3: SEO Text Block
       ========================================================================= */
    .lsb-seo {
      background: #ffffff;
      padding: 80px 40px;
      border-top: 1px solid var(--border, #E4EAF2);
    }
    .lsb-seo__inner {
      max-width: 1200px;
      margin: 0 auto;
    }
    .lsb-seo__title {
      font-family: 'Syne', sans-serif;
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--navy, #0D1B2A);
      letter-spacing: -0.02em;
      margin: 0 0 24px;
    }
    .lsb-seo__body {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 32px;
    }
    .lsb-seo__body p {
      font-family: 'DM Sans', sans-serif;
      font-size: 0.92rem;
      color: var(--muted, #8A9BB0);
      line-height: 1.8;
      font-weight: 300;
      margin: 0;
    }
    .lsb-seo__body p strong {
      color: var(--text, #1A2535);
      font-weight: 500;
    }

    /* =========================================================================
       Section 4: CTA Banner
       ========================================================================= */
    .lsb-cta {
      background: var(--navy, #0D1B2A);
      padding: 80px 40px;
      position: relative;
      overflow: hidden;
    }
    .lsb-cta__bg {
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse at 20% 50%, rgba(0,201,167,0.08) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 50%, rgba(244,197,66,0.06) 0%, transparent 60%);
      pointer-events: none;
    }
    .lsb-cta__inner {
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 48px;
      flex-wrap: wrap;
    }
    .lsb-cta__content { max-width: 600px; }
    .lsb-cta__title {
      font-family: 'Syne', sans-serif !important;
      font-size: clamp(1.8rem, 3vw, 2.6rem) !important;
      font-weight: 800 !important;
      color: #ffffff !important;
      letter-spacing: -0.02em;
      line-height: 1.1;
      margin: 8px 0 16px;
    }
    .lsb-cta__sub {
      font-family: 'DM Sans', sans-serif;
      font-size: 1rem;
      color: #ffffff !important;
      line-height: 1.75;
      font-weight: 300;
      margin: 0;
    }
    .lsb-cta__buttons {
      display: flex;
      gap: 14px;
      flex-shrink: 0;
      flex-wrap: wrap;
    }
    .lsb-cta__btn-primary {
      background: var(--teal, #00C9A7);
      color: var(--navy, #0D1B2A);
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 0.95rem;
      padding: 16px 32px;
      border-radius: 8px;
      text-decoration: none;
      letter-spacing: 0.01em;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .lsb-cta__btn-primary:hover {
      background: var(--teal-dark, #00A88C);
      transform: translateY(-2px);
    }
    .lsb-cta__btn-secondary {
      background: transparent;
      color: #ffffff;
      font-family: 'Syne', sans-serif;
      font-weight: 600;
      font-size: 0.95rem;
      padding: 16px 32px;
      border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.2);
      text-decoration: none;
      letter-spacing: 0.01em;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .lsb-cta__btn-secondary:hover {
      border-color: #ffffff;
      background: rgba(255,255,255,0.05);
    }

    /* =========================================================================
       Responsive
       ========================================================================= */
    @media (max-width: 1024px) {
      .lsb-why, .lsb-popular, .lsb-seo, .lsb-cta { padding: 60px 32px; }
      .lsb-seo__body { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
      .lsb-why, .lsb-popular, .lsb-seo, .lsb-cta { padding: 48px 20px; }
      .lsb-why__grid { grid-template-columns: 1fr; gap: 1px; }
      .lsb-why__item { padding: 28px 24px; }
      .lsb-popular__grid { grid-template-columns: 1fr; }
      .lsb-seo__body { grid-template-columns: 1fr; gap: 20px; }
      .lsb-cta__inner { flex-direction: column; align-items: flex-start; gap: 32px; }
      .lsb-cta__buttons { width: 100%; }
      .lsb-cta__btn-primary, .lsb-cta__btn-secondary { flex: 1; text-align: center; justify-content: center; }
    }
    </style>

    <?php
    return ob_get_clean();
});