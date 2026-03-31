<?php
/**
 * Shortcode: [lsb_hero_stats]
 * File: /shortcodes/count-stats.php
 */
add_shortcode( 'lsb_hero_stats', function() {
    $stat_businesses = wp_count_posts( 'businesses' );
    $stat_businesses = ! empty( $stat_businesses->publish ) ? (int) $stat_businesses->publish : 0;
    $stat_cities = wp_count_posts( 'cities' );
    $stat_cities = ! empty( $stat_cities->publish ) ? (int) $stat_cities->publish : 0;
    $stat_industries = wp_count_posts( 'industries' );
    $stat_industries = ! empty( $stat_industries->publish ) ? (int) $stat_industries->publish : 0;

    // Pull count from service_type taxonomy terms instead of services CPT posts
    $stat_services = wp_count_terms( 'service_type', [ 'hide_empty' => false ] );
    $stat_services = is_wp_error( $stat_services ) ? 0 : (int) $stat_services;

    ob_start(); ?>
    <style>
      .hs-wrap {
        display: flex !important;
        gap: 0 !important;
        align-items: stretch !important;
        flex-wrap: wrap !important;
      }
      .hs-item {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 6px !important;
        padding: 0 44px 0 0 !important;
        margin: 0 44px 0 0 !important;
        position: relative !important;
        opacity: 0;
        transform: translateY(10px);
        animation: hsStatUp 0.5s ease forwards !important;
      }
      .hs-item:nth-child(1) { animation-delay: 0.0s !important; }
      .hs-item:nth-child(2) { animation-delay: 0.1s !important; }
      .hs-item:nth-child(3) { animation-delay: 0.2s !important; }
      .hs-item:nth-child(4) { animation-delay: 0.3s !important; }
      @keyframes hsStatUp {
        to { opacity: 1; transform: translateY(0); }
      }
      .hs-item:not(:last-child)::after {
        content: '' !important;
        position: absolute !important;
        right: 0 !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        width: 1px !important;
        height: 36px !important;
        background: linear-gradient(to bottom, transparent, rgba(0,201,167,0.35), transparent) !important;
      }
      .hs-item:last-child {
        padding-right: 0 !important;
        margin-right: 0 !important;
      }
      .hs-num-wrap {
        display: flex !important;
        align-items: baseline !important;
        gap: 2px !important;
      }
      .hs-num {
        font-family: 'Syne', sans-serif !important;
        font-size: 2rem !important;
        font-weight: 800 !important;
        color: #FFFFFF !important;
        letter-spacing: -0.03em !important;
        line-height: 1 !important;
        font-variant-numeric: tabular-nums !important;
      }
      .hs-suffix {
        font-family: 'Syne', sans-serif !important;
        font-size: 1.4rem !important;
        font-weight: 700 !important;
        color: #00C9A7 !important;
        line-height: 1 !important;
        letter-spacing: -0.02em !important;
      }
      .hs-label-wrap {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
      }
      .hs-dot {
        width: 5px !important;
        height: 5px !important;
        border-radius: 50% !important;
        background: #00C9A7 !important;
        opacity: 0.6 !important;
        flex-shrink: 0 !important;
      }
      .hs-label {
        font-family: 'DM Sans', sans-serif !important;
        font-size: 0.72rem !important;
        font-weight: 600 !important;
        color: rgba(255,255,255,0.5) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.12em !important;
        line-height: 1 !important;
      }
      @media (max-width: 640px) {
        .hs-item { padding: 0 !important; margin: 0 !important; }
        .hs-item::after { display: none !important; }
        .hs-wrap { gap: 28px !important; }
        .hs-num { font-size: 1.6rem !important; }
      }
    </style>

    <div class="hs-wrap">
      <div class="hs-item">
        <div class="hs-num-wrap">
          <span class="hs-num" data-target="<?php echo esc_attr( $stat_businesses ); ?>">0</span>
          <span class="hs-suffix">+</span>
        </div>
        <div class="hs-label-wrap">
          <span class="hs-dot"></span>
          <span class="hs-label">Businesses</span>
        </div>
      </div>
      <div class="hs-item">
        <div class="hs-num-wrap">
          <span class="hs-num" data-target="<?php echo esc_attr( $stat_cities ); ?>">0</span>
        </div>
        <div class="hs-label-wrap">
          <span class="hs-dot"></span>
          <span class="hs-label">Cities</span>
        </div>
      </div>
      <div class="hs-item">
        <div class="hs-num-wrap">
          <span class="hs-num" data-target="<?php echo esc_attr( $stat_industries ); ?>">0</span>
        </div>
        <div class="hs-label-wrap">
          <span class="hs-dot"></span>
          <span class="hs-label">Industries</span>
        </div>
      </div>
      <div class="hs-item">
        <div class="hs-num-wrap">
          <span class="hs-num" data-target="<?php echo esc_attr( $stat_services ); ?>">0</span>
          <span class="hs-suffix">+</span>
        </div>
        <div class="hs-label-wrap">
          <span class="hs-dot"></span>
          <span class="hs-label">Services</span>
        </div>
      </div>
    </div>

    <script>
    (function() {
      function easeOutExpo(t) {
        return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
      }
      function animateCount(el, target, duration, delay) {
        setTimeout(function() {
          var start = performance.now();
          function update(now) {
            var progress = Math.min((now - start) / duration, 1);
            el.textContent = Math.round(easeOutExpo(progress) * target);
            if (progress < 1) requestAnimationFrame(update);
            else el.textContent = target;
          }
          requestAnimationFrame(update);
        }, delay);
      }
      document.querySelectorAll('.hs-num[data-target]').forEach(function(el, i) {
        animateCount(el, parseInt(el.dataset.target), 1800, 400 + i * 120);
      });
    })();
    </script>
    <?php
    return ob_get_clean();
});