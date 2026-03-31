<?php
/**
 * Template Name: FAQ Single Page
 * Description: AEO-focused single FAQ post template for LocalServiceBiz
 *
 * Usage: Assign this page template to individual FAQ CPT posts,
 * or use as an Elementor single template for the `faqs` post type.
 *
 * ACF Fields used:
 *   faq_short_answer  (WYSIWYG)
 *   faq_full_answer   (WYSIWYG)
 *
 * Taxonomies used:
 *   industry_cat, service_type, city_cat
 */

get_header();

// ── Core data ─────────────────────────────────────────────────────────────
$post_id       = get_the_ID();
$question      = get_the_title();
$short_answer  = get_field( 'faq_short_answer', $post_id );
$full_answer   = get_field( 'faq_full_answer',  $post_id );

// Taxonomies assigned to this FAQ
$industries    = get_the_terms( $post_id, 'industry_cat' ) ?: [];
$services      = get_the_terms( $post_id, 'service_type' ) ?: [];
$cities        = get_the_terms( $post_id, 'city_cat' )     ?: [];

// Build tax_query arrays for related content queries
$industry_ids  = wp_list_pluck( $industries, 'term_id' );
$service_ids   = wp_list_pluck( $services,   'term_id' );

// ── Related FAQs ──────────────────────────────────────────────────────────
$related_faq_args = [
    'post_type'      => 'faqs',
    'posts_per_page' => 4,
    'post__not_in'   => [ $post_id ],
    'orderby'        => 'rand',
];
$tax_query_parts = [];
if ( $industry_ids ) {
    $tax_query_parts[] = [
        'taxonomy' => 'industry_cat',
        'field'    => 'term_id',
        'terms'    => $industry_ids,
    ];
}
if ( $service_ids ) {
    $tax_query_parts[] = [
        'taxonomy' => 'service_type',
        'field'    => 'term_id',
        'terms'    => $service_ids,
    ];
}
if ( count( $tax_query_parts ) > 1 ) {
    $related_faq_args['tax_query'] = array_merge( [ 'relation' => 'OR' ], $tax_query_parts );
} elseif ( count( $tax_query_parts ) === 1 ) {
    $related_faq_args['tax_query'] = $tax_query_parts;
}
$related_faqs = new WP_Query( $related_faq_args );

// ── Related Services ──────────────────────────────────────────────────────
$related_services = [];
if ( $industry_ids ) {
    $service_args = [
        'post_type'      => 'services',
        'posts_per_page' => 6,
        'tax_query'      => [[
            'taxonomy' => 'industry_cat',
            'field'    => 'term_id',
            'terms'    => $industry_ids,
        ]],
    ];
    $related_services = get_posts( $service_args );
}

// ── Breadcrumb ────────────────────────────────────────────────────────────
$primary_industry = ! empty( $industries ) ? $industries[0] : null;

// ── FAQ Schema JSON-LD ────────────────────────────────────────────────────
$schema_answer = wp_strip_all_tags( $short_answer . ' ' . $full_answer );
$faq_schema = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => [[
        '@type'          => 'Question',
        'name'           => $question,
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => esc_attr( $schema_answer ),
        ],
    ]],
];
?>

<?php // ── Schema output ─────────────────────────────────────────────────── ?>
<script type="application/ld+json"><?php echo wp_json_encode( $faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

<div class="lsb-faq-page">

    <?php // ══════════════════════════════════════════════════════════════
    //  BREADCRUMB BAR
    // ══════════════════════════════════════════════════════════════ ?>
    <div class="lsb-faq-breadcrumb-bar">
        <div class="lsb-faq-breadcrumb-inner">
            <nav class="lsb-faq-breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
                <span class="lsb-faq-bc-sep" aria-hidden="true">›</span>
                <a href="<?php echo esc_url( home_url( '/faqs/' ) ); ?>">FAQs</a>
                <?php if ( $primary_industry ) : ?>
                    <span class="lsb-faq-bc-sep" aria-hidden="true">›</span>
                    <a href="<?php echo esc_url( get_term_link( $primary_industry ) ); ?>">
                        <?php echo esc_html( $primary_industry->name ); ?>
                    </a>
                <?php endif; ?>
                <span class="lsb-faq-bc-sep" aria-hidden="true">›</span>
                <span class="lsb-faq-bc-current"><?php echo esc_html( $question ); ?></span>
            </nav>
        </div>
    </div>

    <?php // ══════════════════════════════════════════════════════════════
    //  HERO — Question as H1
    // ══════════════════════════════════════════════════════════════ ?>
    <section class="lsb-faq-hero">
        <div class="lsb-faq-hero-grid" aria-hidden="true"></div>
        <div class="lsb-faq-hero-glow"  aria-hidden="true"></div>
        <div class="lsb-faq-hero-inner">

            <?php if ( $industries || $services ) : ?>
            <div class="lsb-faq-hero-tags">
                <?php foreach ( $industries as $ind ) : ?>
                    <a href="<?php echo esc_url( home_url( '/industries/' . $ind->slug . '/' ) ); ?>"
                       class="lsb-faq-tag lsb-faq-tag--industry">
                        <?php echo esc_html( $ind->name ); ?>
                    </a>
                <?php endforeach; ?>
                <?php foreach ( $services as $svc ) : ?>
                    <span class="lsb-faq-tag lsb-faq-tag--service">
                        <?php echo esc_html( $svc->name ); ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h1 class="lsb-faq-question"><?php echo esc_html( $question ); ?></h1>

            <?php // ── Short Answer Block (AEO answer box) ────────────── ?>
            <?php if ( $short_answer ) : ?>
            <div class="lsb-faq-short-answer" role="region" aria-label="Quick answer">
                <div class="lsb-faq-sa-label">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <circle cx="8" cy="8" r="7.5" stroke="#00C9A7" stroke-width="1"/>
                        <path d="M5 8l2 2 4-4" stroke="#00C9A7" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Quick Answer
                </div>
                <div class="lsb-faq-sa-text">
                    <?php echo wp_kses_post( $short_answer ); ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </section>

    <?php // ══════════════════════════════════════════════════════════════
    //  MAIN CONTENT AREA
    // ══════════════════════════════════════════════════════════════ ?>
    <section class="lsb-faq-body-section">
        <div class="lsb-faq-body-inner">

            <?php // ── Left: Full Answer ────────────────────────────────── ?>
            <div class="lsb-faq-main-col">

                <?php if ( $full_answer ) : ?>
                <div class="lsb-faq-full-answer">
                    <h2 class="lsb-faq-section-heading">Detailed Explanation</h2>
                    <div class="lsb-faq-prose">
                        <?php echo wp_kses_post( $full_answer ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── Related Services ─────────────────────────────── ?>
                <?php if ( $related_services ) : ?>
                <div class="lsb-faq-related-services">
                    <h2 class="lsb-faq-section-heading">Related Services</h2>
                    <div class="lsb-faq-service-pills">
                        <?php foreach ( $related_services as $svc ) :
                         $svc_url = get_permalink( $svc->ID );
                        ?>
                            <a href="<?php echo esc_url( $svc_url ); ?>" class="lsb-faq-service-pill">
                                <?php echo esc_html( $svc->post_title ); ?>
                                <span class="lsb-faq-pill-arrow" aria-hidden="true">→</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <?php // ── Right: Sidebar ────────────────────────────────────── ?>
            <aside class="lsb-faq-sidebar" aria-label="Related content">

                <?php // ── CTA Card ─────────────────────────────────── ?>
                <div class="lsb-faq-cta-card">
                    <div class="lsb-faq-cta-icon" aria-hidden="true">🤝</div>
                    <h3 class="lsb-faq-cta-title">Find a Local Professional</h3>
                    <p class="lsb-faq-cta-desc">Connect directly with trusted <?php echo $primary_industry ? esc_html( $primary_industry->name ) : 'local service'; ?> experts near you — no middlemen, no fees.</p>
                    <?php
                    $cta_url = $primary_industry
                        ? home_url( '/industries/' . $primary_industry->slug . '/' )
                        : home_url( '/industries/' );
                    ?>
                    <a href="<?php echo esc_url( $cta_url ); ?>" class="lsb-faq-cta-btn">
                        Browse Professionals →
                    </a>
                </div>

                <?php // ── Cities ────────────────────────────────────── ?>
                <?php if ( $cities ) : ?>
                <div class="lsb-faq-sidebar-card">
                    <h3 class="lsb-faq-sidebar-heading">Cities Covered</h3>
                    <ul class="lsb-faq-cities-list">
                        <?php foreach ( $cities as $city ) : ?>
                            <li>
                                <a href="<?php echo esc_url( get_term_link( $city ) ); ?>">
                                    <span class="lsb-faq-city-pin" aria-hidden="true">📍</span>
                                    <?php echo esc_html( $city->name ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php // ── Industries ────────────────────────────────── ?>
                <?php if ( $industries ) : ?>
                <div class="lsb-faq-sidebar-card">
                    <h3 class="lsb-faq-sidebar-heading">Industries</h3>
                    <ul class="lsb-faq-ind-list">
                        <?php foreach ( $industries as $ind ) : ?>
                            <li>
                                <a href="<?php echo esc_url( home_url( '/industries/' . $ind->slug . '/' ) ); ?>">
                                    <?php echo esc_html( $ind->name ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

            </aside>

        </div>
    </section>

    <?php // ══════════════════════════════════════════════════════════════
    //  RELATED FAQs
    // ══════════════════════════════════════════════════════════════ ?>
    <?php if ( $related_faqs->have_posts() ) : ?>
    <section class="lsb-faq-related-section">
        <div class="lsb-faq-related-inner">
            <span class="lsb-faq-label">Keep Exploring</span>
            <h2 class="lsb-faq-related-title">Related Questions</h2>
            <div class="lsb-faq-related-grid">
                <?php while ( $related_faqs->have_posts() ) : $related_faqs->the_post(); ?>
                    <?php
                    $r_short = get_field( 'faq_short_answer' );
                    $r_inds  = get_the_terms( get_the_ID(), 'industry_cat' ) ?: [];
                    $r_ind   = ! empty( $r_inds ) ? $r_inds[0]->name : '';
                    ?>
                    <a href="<?php the_permalink(); ?>" class="lsb-faq-related-card">
                        <?php if ( $r_ind ) : ?>
                            <span class="lsb-faq-related-ind"><?php echo esc_html( $r_ind ); ?></span>
                        <?php endif; ?>
                        <h3 class="lsb-faq-related-q"><?php the_title(); ?></h3>
                        <?php if ( $r_short ) : ?>
                            <p class="lsb-faq-related-excerpt">
                                <?php echo wp_trim_words( wp_strip_all_tags( $r_short ), 18, '…' ); ?>
                            </p>
                        <?php endif; ?>
                        <span class="lsb-faq-related-arrow" aria-hidden="true">→</span>
                    </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</div><!-- /.lsb-faq-page -->

<?php // ══════════════════════════════════════════════════════════════════
//  STYLES
// ══════════════════════════════════════════════════════════════════ ?>
<style>
/* ── Reset / scope ─────────────────────────────────────────────────────── */
.lsb-faq-page {
    --navy:       #0D1B2A;
    --navy-mid:   #1B2F45;
    --teal:       #00C9A7;
    --teal-dark:  #00A88C;
    --gold:       #F4C542;
    --white:      #FFFFFF;
    --off-white:  #F5F7FA;
    --muted:      #8A9BB0;
    --text:       #1A2535;
    --border:     #E4EAF2;
    font-family: 'DM Sans', sans-serif;
    color: var(--text);
}

/* ── Breadcrumb bar ────────────────────────────────────────────────────── */
.lsb-faq-breadcrumb-bar {
    background: var(--navy);
    margin-top: 72px; /* fixed header clearance */
    border-bottom: 1px solid rgba(255,255,255,0.06);
    padding: 0 40px;
}
.lsb-faq-breadcrumb-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 14px 0;
}
.lsb-faq-breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 400;
}
.lsb-faq-breadcrumb a {
    color: rgba(255,255,255,0.5) !important;
    text-decoration: none !important;
    transition: color 0.2s;
}
.lsb-faq-breadcrumb a:hover { color: var(--teal) !important; }
.lsb-faq-bc-sep { color: rgba(255,255,255,0.2); }
.lsb-faq-bc-current {
    color: var(--teal) !important;
    font-weight: 500;
    max-width: 400px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Hero ──────────────────────────────────────────────────────────────── */
.lsb-faq-hero {
    background: var(--navy);
    position: relative;
    overflow: hidden;
    padding: 72px 40px 80px;
}
.lsb-faq-hero-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(0,201,167,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,201,167,0.04) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
}
.lsb-faq-hero-glow {
    position: absolute;
    width: 600px;
    height: 400px;
    background: radial-gradient(ellipse, rgba(0,201,167,0.1) 0%, transparent 70%);
    top: -80px;
    right: -60px;
    pointer-events: none;
}
.lsb-faq-hero-inner {
    max-width: 900px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* Tags */
.lsb-faq-hero-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 22px;
}
.lsb-faq-tag {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    padding: 5px 14px;
    border-radius: 100px;
    text-decoration: none !important;
}
.lsb-faq-tag--industry {
    background: rgba(0,201,167,0.12);
    border: 1px solid rgba(0,201,167,0.3);
    color: var(--teal) !important;
    transition: background 0.2s;
}
.lsb-faq-tag--industry:hover { background: rgba(0,201,167,0.2); }
.lsb-faq-tag--service {
    background: rgba(244,197,66,0.1);
    border: 1px solid rgba(244,197,66,0.25);
    color: var(--gold) !important;
}

/* H1 */
.lsb-faq-question {
    font-family: 'Syne', sans-serif !important;
    font-size: clamp(1.8rem, 4vw, 3rem) !important;
    font-weight: 800 !important;
    color: var(--white) !important;
    line-height: 1.1 !important;
    letter-spacing: -0.025em !important;
    margin: 0 0 36px !important;
}

/* Short answer */
.lsb-faq-short-answer {
    background: rgba(0,201,167,0.07);
    border: 1px solid rgba(0,201,167,0.2);
    border-left: 4px solid var(--teal);
    border-radius: 12px;
    padding: 28px 32px;
    max-width: 820px;
}
.lsb-faq-sa-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--teal) !important;
    margin-bottom: 14px;
}
.lsb-faq-sa-text,
.lsb-faq-sa-text p {
    font-size: 1.05rem !important;
    color: rgba(255,255,255,0.85) !important;
    line-height: 1.75 !important;
    font-weight: 300 !important;
    margin: 0 !important;
}

/* ── Body section (2-col layout) ───────────────────────────────────────── */
.lsb-faq-body-section {
    background: var(--off-white);
    padding: 80px 40px;
}
.lsb-faq-body-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 48px;
    align-items: start;
}

/* Full answer prose */
.lsb-faq-section-heading {
    font-family: 'Syne', sans-serif !important;
    font-size: 1.3rem !important;
    font-weight: 700 !important;
    color: var(--navy) !important;
    letter-spacing: -0.01em !important;
    margin: 0 0 20px !important;
}
.lsb-faq-full-answer {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 36px;
}
.lsb-faq-prose,
.lsb-faq-prose p {
    font-size: 1rem !important;
    color: #3D4F63 !important;
    line-height: 1.8 !important;
    font-weight: 400 !important;
}
.lsb-faq-prose h2,
.lsb-faq-prose h3 {
    font-family: 'Syne', sans-serif !important;
    font-weight: 700 !important;
    color: var(--navy) !important;
    margin-top: 28px !important;
    margin-bottom: 12px !important;
}
.lsb-faq-prose ul,
.lsb-faq-prose ol {
    margin: 14px 0 14px 24px !important;
    color: #3D4F63 !important;
    line-height: 1.8 !important;
}
.lsb-faq-prose li { margin-bottom: 6px !important; }
.lsb-faq-prose strong { color: var(--navy) !important; font-weight: 600 !important; }
.lsb-faq-prose a { color: var(--teal-dark) !important; }

/* Related services */
.lsb-faq-related-services {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 32px 40px;
}
.lsb-faq-service-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 4px;
}
.lsb-faq-service-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--off-white);
    border: 1px solid var(--border);
    color: var(--navy) !important;
    font-size: 0.875rem;
    font-weight: 500;
    padding: 10px 18px;
    border-radius: 100px;
    text-decoration: none !important;
    transition: all 0.2s;
}
.lsb-faq-service-pill:hover {
    border-color: var(--teal);
    background: rgba(0,201,167,0.06);
    color: var(--teal-dark) !important;
    transform: translateY(-1px);
}
.lsb-faq-pill-arrow { color: var(--teal); font-size: 0.85rem; }

/* ── Sidebar ───────────────────────────────────────────────────────────── */
.lsb-faq-sidebar { display: flex; flex-direction: column; gap: 20px; }

/* CTA card */
.lsb-faq-cta-card {
    background: var(--navy);
    border-radius: 16px;
    padding: 32px 28px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.lsb-faq-cta-card::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(0,201,167,0.15) 0%, transparent 70%);
    pointer-events: none;
}
.lsb-faq-cta-icon { font-size: 2rem; margin-bottom: 16px; display: block; }
.lsb-faq-cta-title {
    font-family: 'Syne', sans-serif !important;
    font-size: 1.1rem !important;
    font-weight: 700 !important;
    color: var(--white) !important;
    margin: 0 0 12px !important;
    letter-spacing: -0.01em !important;
}
.lsb-faq-cta-desc {
    font-size: 0.875rem !important;
    color: rgba(255,255,255,0.5) !important;
    line-height: 1.6 !important;
    font-weight: 300 !important;
    margin: 0 0 24px !important;
}
.lsb-faq-cta-btn {
    display: inline-block;
    background: var(--teal);
    color: var(--navy) !important;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 0.875rem;
    padding: 13px 24px;
    border-radius: 8px;
    text-decoration: none !important;
    letter-spacing: 0.01em;
    transition: all 0.2s;
}
.lsb-faq-cta-btn:hover { background: var(--teal-dark); transform: translateY(-1px); }

/* Generic sidebar card */
.lsb-faq-sidebar-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px 28px;
}
.lsb-faq-sidebar-heading {
    font-family: 'Syne', sans-serif !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.1em !important;
    text-transform: uppercase !important;
    color: var(--muted) !important;
    margin: 0 0 16px !important;
}
.lsb-faq-cities-list,
.lsb-faq-ind-list {
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.lsb-faq-cities-list a,
.lsb-faq-ind-list a {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #3D4F63 !important;
    text-decoration: none !important;
    font-size: 0.9rem;
    font-weight: 400;
    transition: color 0.2s;
}
.lsb-faq-cities-list a:hover,
.lsb-faq-ind-list a:hover { color: var(--teal-dark) !important; }
.lsb-faq-city-pin { font-size: 0.85rem; }

/* ── Related FAQs ───────────────────────────────────────────────────────── */
.lsb-faq-related-section {
    background: var(--white);
    padding: 80px 40px;
    border-top: 1px solid var(--border);
}
.lsb-faq-related-inner { max-width: 1200px; margin: 0 auto; }
.lsb-faq-label {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--teal);
    margin-bottom: 10px;
}
.lsb-faq-related-title {
    font-family: 'Syne', sans-serif !important;
    font-size: clamp(1.5rem, 2.5vw, 2rem) !important;
    font-weight: 800 !important;
    color: var(--navy) !important;
    letter-spacing: -0.02em !important;
    margin: 0 0 36px !important;
}
.lsb-faq-related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
}
.lsb-faq-related-card {
    background: var(--off-white);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 28px 24px;
    text-decoration: none !important;
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
    transition: all 0.25s;
    overflow: hidden;
}
.lsb-faq-related-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: var(--teal);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.25s;
}
.lsb-faq-related-card:hover {
    border-color: var(--teal);
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0,201,167,0.08);
    background: var(--white);
}
.lsb-faq-related-card:hover::after { transform: scaleX(1); }
.lsb-faq-related-ind {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--teal) !important;
}
.lsb-faq-related-q {
    font-family: 'Syne', sans-serif !important;
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    color: var(--navy) !important;
    line-height: 1.3 !important;
    margin: 0 !important;
}
.lsb-faq-related-excerpt {
    font-size: 0.82rem !important;
    color: var(--muted) !important;
    line-height: 1.6 !important;
    font-weight: 300 !important;
    margin: 0 !important;
    flex: 1;
}
.lsb-faq-related-arrow {
    color: var(--teal);
    font-size: 0.9rem;
    font-weight: 600;
    margin-top: 4px;
    transition: transform 0.2s;
}
.lsb-faq-related-card:hover .lsb-faq-related-arrow { transform: translateX(4px); }

/* ── Responsive ─────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .lsb-faq-body-inner {
        grid-template-columns: 1fr;
    }
    .lsb-faq-sidebar {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    }
    .lsb-faq-hero { padding: 52px 24px 60px; }
    .lsb-faq-body-section { padding: 52px 24px; }
    .lsb-faq-related-section { padding: 52px 24px; }
    .lsb-faq-breadcrumb-bar { padding: 0 24px; }
}
@media (max-width: 600px) {
    .lsb-faq-short-answer { padding: 20px 20px; }
    .lsb-faq-full-answer { padding: 24px; }
    .lsb-faq-sidebar { grid-template-columns: 1fr; }
    .lsb-faq-related-grid { grid-template-columns: 1fr; }
}
</style>

<?php get_footer(); ?>