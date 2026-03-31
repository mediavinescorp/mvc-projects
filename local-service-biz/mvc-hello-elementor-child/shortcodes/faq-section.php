<?php
/**
 * FAQ Section Shortcode
 * Usage: [faq_section] or [faq_section category="general"]
 * Add to: child theme /shortcodes/faq-section.php
 */

function lsb_faq_section_shortcode( $atts ) {

  static $styles_printed = false;

  $atts = shortcode_atts( array(
    'category' => '',
    'count'    => 6,
  ), $atts );

  $args = array(
    'post_type'      => 'faqs',
    'posts_per_page' => intval( $atts['count'] ),
    'post_status'    => 'publish',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
  );

  // Filter by faq_category if provided
  if ( ! empty( $atts['category'] ) ) {
    $args['tax_query'] = array(
      array(
        'taxonomy' => 'faq_category',
        'field'    => 'slug',
        'terms'    => sanitize_text_field( $atts['category'] ),
      ),
    );
  }

  $faqs = new WP_Query( $args );

  if ( ! $faqs->have_posts() ) {
    return '<p>No FAQs found.</p>';
  }

  ob_start();

  if ( ! $styles_printed ) :
    $styles_printed = true;
  ?>
  <style>
    section.faq-section {
      background: #F5F7FA !important;
      padding: 100px 40px !important;
      margin: 0 !important;
      position: relative !important;
      box-sizing: border-box !important;
    }
    section.faq-section * { box-sizing: border-box; }
    section.faq-section .faq-inner {
      max-width: 1200px !important;
      margin: 0 auto !important;
      width: 100% !important;
    }
    section.faq-section .faq-header {
      margin-bottom: 48px !important;
    }
    section.faq-section .faq-label {
      display: block !important;
      font-size: 0.75rem !important;
      font-weight: 600 !important;
      letter-spacing: 0.12em !important;
      text-transform: uppercase !important;
      color: #00C9A7 !important;
      margin-bottom: 10px !important;
      font-family: 'DM Sans', sans-serif !important;
    }
    section.faq-section .faq-title {
      font-family: 'Syne', sans-serif !important;
      font-size: clamp(1.8rem, 3.5vw, 2.8rem) !important;
      font-weight: 800 !important;
      color: #0D1B2A !important;
      letter-spacing: -0.02em !important;
      line-height: 1.1 !important;
      margin: 0 0 12px !important;
      padding: 0 !important;
    }
    section.faq-section .faq-subtitle {
      font-size: 1rem !important;
      color: #8A9BB0 !important;
      font-weight: 300 !important;
      font-family: 'DM Sans', sans-serif !important;
      margin: 0 !important;
      line-height: 1.6 !important;
    }
    section.faq-section .faq-grid {
      display: grid !important;
      grid-template-columns: 1fr 1fr !important;
      gap: 16px !important;
    }
    section.faq-section .faq-item {
      background: #ffffff !important;
      border: 1px solid #E4EAF2 !important;
      border-radius: 12px !important;
      overflow: hidden !important;
      transition: border-color 0.2s !important;
    }
    section.faq-section .faq-item:hover {
      border-color: rgba(0,201,167,0.3) !important;
    }
    section.faq-section .faq-item.faq-open {
      border-color: #00C9A7 !important;
    }
    section.faq-section .faq-question {
      padding: 22px 24px !important;
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      cursor: pointer !important;
      gap: 16px !important;
      background: none !important;
      border: none !important;
      width: 100% !important;
      text-align: left !important;
    }
    section.faq-section .faq-question-text {
      font-family: 'Syne', sans-serif !important;
      font-weight: 600 !important;
      font-size: 0.95rem !important;
      color: #0D1B2A !important;
      line-height: 1.4 !important;
      margin: 0 !important;
      padding: 0 !important;
      flex: 1 !important;
    }
    section.faq-section .faq-toggle {
      width: 28px !important;
      height: 28px !important;
      min-width: 28px !important;
      background: #F5F7FA !important;
      border-radius: 50% !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-size: 1.1rem !important;
      color: #00C9A7 !important;
      font-weight: 400 !important;
      transition: transform 0.25s, background 0.2s !important;
      flex-shrink: 0 !important;
      line-height: 1 !important;
    }
    section.faq-section .faq-item.faq-open .faq-toggle {
      transform: rotate(45deg) !important;
      background: rgba(0,201,167,0.1) !important;
    }
    section.faq-section .faq-answer {
      max-height: 0 !important;
      overflow: hidden !important;
      transition: max-height 0.35s ease, padding 0.25s ease !important;
      padding: 0 24px !important;
    }
    section.faq-section .faq-item.faq-open .faq-answer {
      max-height: 600px !important;
      padding: 0 24px 22px !important;
    }
    section.faq-section .faq-answer-inner {
      font-size: 0.88rem !important;
      color: #8A9BB0 !important;
      line-height: 1.7 !important;
      font-weight: 300 !important;
      font-family: 'DM Sans', sans-serif !important;
      border-top: 1px solid #E4EAF2 !important;
      padding-top: 16px !important;
    }
    section.faq-section .faq-answer-inner p {
      margin: 0 0 10px !important;
    }
    section.faq-section .faq-answer-inner p:last-child {
      margin: 0 !important;
    }

    @media (max-width: 767px) {
      section.faq-section {
        padding: 70px 20px !important;
      }
      section.faq-section .faq-grid {
        grid-template-columns: 1fr !important;
      }
    }
  </style>
  <?php endif; ?>

  <section class="faq-section">
    <div class="faq-inner">

      <div class="faq-header">
        <span class="faq-label">Common Questions</span>
        <h2 class="faq-title">Frequently Asked Questions</h2>
        <p class="faq-subtitle">Everything you need to know about finding local professionals through our directory.</p>
      </div>

      <div class="faq-grid">

        <?php while ( $faqs->have_posts() ) : $faqs->the_post();
          $question = get_the_title();
          $answer   = get_field( 'faq_short_answer' );

          if ( empty( $answer ) ) continue;
        ?>

          <div class="faq-item">
            <div class="faq-question" onclick="lsbToggleFaq(this)">
              <span class="faq-question-text"><?php echo esc_html( $question ); ?></span>
              <span class="faq-toggle">+</span>
            </div>
            <div class="faq-answer">
              <div class="faq-answer-inner">
                <?php echo wp_kses_post( $answer ); ?>
              </div>
            </div>
          </div>

        <?php endwhile; wp_reset_postdata(); ?>

      </div>
    </div>
  </section>

  <script>
    function lsbToggleFaq(el) {
      var item = el.parentElement;
      item.classList.toggle('faq-open');
    }
  </script>

  <?php
  return ob_get_clean();
}
add_shortcode( 'faq_section', 'lsb_faq_section_shortcode' );