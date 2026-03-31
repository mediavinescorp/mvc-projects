<?php
/**
 * LSB Search + Results (No archive template required)
 * CPT: businesses
 * Taxonomies: industry_cat, city_cat
 */

/** Search Bar Shortcode: [lsb_business_search results_url="/businesses/"] */
add_shortcode('lsb_business_search', function($atts) {
  $atts = shortcode_atts([
    'results_url' => home_url('/businesses/'),
    'placeholder' => 'Enter your city...'
  ], $atts);

  $industries = get_terms([
    'taxonomy'   => 'industry_cat',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ]);

  $cities = get_terms([
    'taxonomy'   => 'city_cat',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ]);

  ob_start(); ?>
    <div class="hero-search lsb-hero-search" data-results-url="<?php echo esc_url($atts['results_url']); ?>">
      <select class="hero-select" aria-label="Select industry">
        <option value="">All Industries</option>
        <?php if (!is_wp_error($industries) && !empty($industries)): ?>
          <?php foreach ($industries as $t): ?>
            <option value="<?php echo esc_attr($t->slug); ?>"><?php echo esc_html($t->name); ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>

      <input class="hero-input" type="text" list="lsb-city-list"
             placeholder="<?php echo esc_attr($atts['placeholder']); ?>" aria-label="Enter city">

      <datalist id="lsb-city-list">
        <?php if (!is_wp_error($cities) && !empty($cities)): ?>
          <?php foreach ($cities as $c): ?>
            <option value="<?php echo esc_attr($c->name); ?>"></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </datalist>

      <button class="hero-btn" type="button">Search →</button>
    </div>

    <script>
    (function(){
      const wrap = document.querySelector('.lsb-hero-search[data-results-url]');
      if (!wrap) return;

      const industryEl = wrap.querySelector('.hero-select');
      const cityEl     = wrap.querySelector('.hero-input');
      const btn        = wrap.querySelector('.hero-btn');
      const resultsUrl = wrap.getAttribute('data-results-url');

      function slugify(str){
        return (str || '')
          .toString()
          .trim()
          .toLowerCase()
          .replace(/&/g, 'and')
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-+|-+$/g, '');
      }

      function go(){
        const industry = industryEl.value || '';
        const cityName = cityEl.value || '';
        const citySlug = slugify(cityName);

        const url = new URL(resultsUrl, window.location.origin);
        if (industry) url.searchParams.set('industry', industry);
        if (citySlug) url.searchParams.set('city', citySlug);

        window.location.href = url.toString();
      }

      btn.addEventListener('click', go);
      cityEl.addEventListener('keydown', (e)=>{ if(e.key === 'Enter') go(); });
    })();
    </script>
  <?php
  return ob_get_clean();
});


/** Results Shortcode: [lsb_business_results] */
add_shortcode('lsb_business_results', function($atts) {

  $industry = isset($_GET['industry']) ? sanitize_text_field($_GET['industry']) : '';
  $city     = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';
  $paged    = max(1, get_query_var('paged') ? (int) get_query_var('paged') : (int) ( $_GET['paged'] ?? 1 ));

  $tax_query = [];
  if ($industry) {
    $tax_query[] = [
      'taxonomy' => 'industry_cat',
      'field'    => 'slug',
      'terms'    => [$industry],
    ];
  }
  if ($city) {
    $tax_query[] = [
      'taxonomy' => 'city_cat',
      'field'    => 'slug',
      'terms'    => [$city],
    ];
  }
  if (count($tax_query) > 1) $tax_query['relation'] = 'AND';

  $q = new WP_Query([
    'post_type'      => 'businesses',
    'post_status'    => 'publish',
    'posts_per_page' => 24,
    'paged'          => $paged,
    'tax_query'      => !empty($tax_query) ? $tax_query : null,
  ]);

  // Labels for the filter header
  $industry_name = '';
  if ($industry) {
    $t = get_term_by('slug', $industry, 'industry_cat');
    if ($t && !is_wp_error($t)) $industry_name = $t->name;
  }

  $city_name = '';
  if ($city) {
    $t = get_term_by('slug', $city, 'city_cat');
    if ($t && !is_wp_error($t)) $city_name = $t->name;
  }

  ob_start(); ?>

  <div class="lsb-results-wrap">
    <div class="lsb-results-head">
      <h2 class="lsb-results-title">Businesses</h2>
      <div class="lsb-results-filters">
        <?php if ($industry_name): ?><span class="lsb-pill">Industry: <?php echo esc_html($industry_name); ?></span><?php endif; ?>
        <?php if ($city_name): ?><span class="lsb-pill">City: <?php echo esc_html($city_name); ?></span><?php endif; ?>
        <?php if (!$industry_name && !$city_name): ?><span class="lsb-pill">Showing all businesses</span><?php endif; ?>
      </div>
    </div>

    <?php if ($q->have_posts()): ?>
      <div class="lsb-grid">
        <?php while ($q->have_posts()): $q->the_post(); ?>
          <a class="lsb-card" href="<?php the_permalink(); ?>">
            <div class="lsb-card-body">
              <h3 class="lsb-card-title"><?php the_title(); ?></h3>
              <div class="lsb-card-meta">
                <?php
                  $inds = get_the_terms(get_the_ID(), 'industry_cat');
                  $cits = get_the_terms(get_the_ID(), 'city_cat');
                ?>
                <?php if ($inds && !is_wp_error($inds)): ?>
                  <div><strong>Industry:</strong> <?php echo esc_html($inds[0]->name); ?></div>
                <?php endif; ?>
                <?php if ($cits && !is_wp_error($cits)): ?>
                  <div><strong>City:</strong> <?php echo esc_html($cits[0]->name); ?></div>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>

      <?php
        // Pagination (keeps query params)
        $base = remove_query_arg('paged');
        echo '<div class="lsb-pagination">';
        echo paginate_links([
          'base'      => esc_url_raw(add_query_arg('paged','%#%',$base)),
          'format'    => '',
          'current'   => $paged,
          'total'     => $q->max_num_pages,
          'prev_text' => '← Prev',
          'next_text' => 'Next →',
        ]);
        echo '</div>';
      ?>

    <?php else: ?>
      <div class="lsb-empty">
        <p><strong>No businesses found</strong> for that filter.</p>
        <p>Try a different city or select “All Industries”.</p>
        <p><a href="<?php echo esc_url( home_url('/businesses/') ); ?>">View all businesses</a></p>
      </div>
    <?php endif; ?>
  </div>

  <style>
    .lsb-results-wrap { max-width: 1100px; margin: 0 auto; padding: 24px 10px; }
    .lsb-results-head { display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
    .lsb-results-title { margin:0; font-size:28px; }
    .lsb-results-filters { display:flex; gap:8px; flex-wrap:wrap; }
    .lsb-pill { background:#f3f6fb; border:1px solid #e4eaf2; padding:6px 10px; border-radius:999px; font-size:13px; }
    .lsb-grid { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:14px; }
    @media (max-width: 980px){ .lsb-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 620px){ .lsb-grid { grid-template-columns: 1fr; } }
    .lsb-card { display:block; text-decoration:none; border:1px solid #e4eaf2; border-radius:14px; background:#fff; padding:16px; transition: transform .12s ease; }
    .lsb-card:hover { transform: translateY(-2px); }
    .lsb-card-title { margin:0 0 8px; font-size:18px; color:#0D1B2A; }
    .lsb-card-meta { font-size:13px; color:#42566b; display:grid; gap:4px; }
    .lsb-empty { border:1px dashed #c9d6e6; border-radius:14px; padding:18px; background:#fbfdff; }
    .lsb-pagination { margin-top:18px; }
    .lsb-pagination .page-numbers { display:inline-block; margin:0 4px 6px 0; padding:8px 10px; border:1px solid #e4eaf2; border-radius:10px; text-decoration:none; }
    .lsb-pagination .current { background:#0D1B2A; color:#fff; border-color:#0D1B2A; }
  </style>

  <?php
  return ob_get_clean();
});