<?php
/**
 * Shortcode: [industry_hero]
 *
 * File: /shortcodes/industry-page-hero.php
 * Called in functions.php:
 *   require_once get_stylesheet_directory() . '/shortcodes/industry-page-hero.php';
 * Called in template-industry.php:
 *   <?php echo do_shortcode('[industry_hero]'); ?>
 *
 * ACF fields used:
 *   industry_hero_intro  → short definition paragraph (left column)
 *   industry_subtitle    → subtitle line under intro (left column)
 *   industry_hero_image  → hero image (right column, only renders if set)
 *
 * Dropdown buttons:
 *   View Services  → Services CPT filtered by industry → /industries/{industry}/{service}/
 *   Browse Cities  → Cities CPT filtered by industry   → /industries/{industry}/{city}/
 */

if ( ! function_exists( 'lsb_industry_hero_shortcode' ) ) :

function lsb_industry_hero_shortcode( $atts ) {

    $atts = shortcode_atts( [ 'industry' => '' ], $atts, 'industry_hero' );

    // ── 1. Get current industry post ──────────────────────────────────────
    $industry_post_id = get_the_ID();

    if ( ! empty( $atts['industry'] ) ) {
        $override = get_page_by_path( sanitize_title( $atts['industry'] ), OBJECT, get_post_type() );
        if ( $override ) $industry_post_id = $override->ID;
    }

    if ( ! $industry_post_id ) {
        return '<!-- [industry_hero] no post ID found -->';
    }

    // ── 2. CPT fields ─────────────────────────────────────────────────────
    $industry_slug = get_post_field( 'post_name',  $industry_post_id );
    $industry_name = get_post_field( 'post_title', $industry_post_id );

    // ── 3. ACF fields ─────────────────────────────────────────────────────
    $hero_intro    = '';
    $hero_subtitle = '';
    $hero_image    = '';

    if ( function_exists( 'get_field' ) ) {
        $hero_intro    = get_field( 'industry_hero_intro', $industry_post_id );
        $hero_subtitle = get_field( 'industry_subtitle',   $industry_post_id );
        $raw_image     = get_field( 'industry_hero_image', $industry_post_id );

        if ( is_array( $raw_image ) && ! empty( $raw_image['url'] ) ) {
            $hero_image = $raw_image['url'];
        } elseif ( is_string( $raw_image ) && ! empty( $raw_image ) ) {
            $hero_image = $raw_image;
        }
    }

    // ── 4. Tax query ──────────────────────────────────────────────────────
    $tax_q = [ [
        'taxonomy' => 'industry_cat',
        'field'    => 'slug',
        'terms'    => $industry_slug,
    ] ];

    // ── 5. Services dropdown list ─────────────────────────────────────────
    $services_q = new WP_Query( [
        'post_type'      => 'services',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => $tax_q,
    ] );
    $services = $services_q->posts;
    wp_reset_postdata();

    // ── 6. Cities dropdown list ───────────────────────────────────────────
    $cities_q = new WP_Query( [
        'post_type'      => 'cities',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => $tax_q,
    ] );
    $cities = $cities_q->posts;
    wp_reset_postdata();

    // ── 7. URLs ───────────────────────────────────────────────────────────
    $home_url     = trailingslashit( home_url() );
    $industry_url = $home_url . 'industries/';

    // ── 8. Render ─────────────────────────────────────────────────────────
    ob_start();

    static $styles_printed = false;
    if ( ! $styles_printed ) :
        $styles_printed = true;
    ?>
    <style id="lsb-industry-hero-css">

    /* ── Breadcrumb ── */
    .lsb-ih-breadcrumb-bar {
        background: #0D1B2A;
        padding: 0 40px;
        height: 44px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        margin-top: 72px;
    }
    .lsb-ih-breadcrumb {
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        color: rgba(255,255,255,0.4);
        list-style: none;
        padding: 0;
    }
    .lsb-ih-breadcrumb a {
        color: rgba(255,255,255,0.4);
        text-decoration: none;
        transition: color .2s;
    }
    .lsb-ih-breadcrumb a:hover { color: #00C9A7; }
    .lsb-ih-breadcrumb-sep { color: rgba(255,255,255,0.2); }
    .lsb-ih-breadcrumb-current { color: #00C9A7; font-weight: 500; }

    /* ── Hero wrapper ── */
    .lsb-ih-hero {
        background: #0D1B2A;
        padding: 56px 40px 64px;
        position: relative;
        overflow: hidden;
    }
    .lsb-ih-grid-bg {
        position: absolute; inset: 0; pointer-events: none;
        background-image:
            linear-gradient(rgba(0,201,167,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,201,167,0.04) 1px, transparent 1px);
        background-size: 60px 60px;
    }
    .lsb-ih-glow-1 {
        position: absolute; pointer-events: none;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(0,201,167,0.1) 0%, transparent 70%);
        top: -120px; right: -80px;
    }
    .lsb-ih-glow-2 {
        position: absolute; pointer-events: none;
        width: 340px; height: 340px;
        background: radial-gradient(circle, rgba(244,197,66,0.06) 0%, transparent 70%);
        bottom: 0; left: 100px;
    }
    .lsb-ih-inner {
        max-width: 1200px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    /* ── Two column layout ── */
    .lsb-ih-columns {
        display: grid;
        grid-template-columns: 1fr 460px;
        gap: 64px;
        align-items: center;
    }
    .lsb-ih-columns.no-image {
        grid-template-columns: 1fr;
        max-width: 780px;
    }

    /* ── Badge ── */
    .lsb-ih-badge {
        display: inline-flex;
        align-items: center;
        background: rgba(0,201,167,0.12);
        border: 1px solid rgba(0,201,167,0.25);
        border-radius: 100px;
        padding: 6px 16px;
        margin-bottom: 24px;
    }
    .lsb-ih-badge span {
        color: #00C9A7;
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        font-family: 'DM Sans', sans-serif;
    }

    /* ── H1 ── */
    .lsb-ih-h1 {
        font-family: 'Syne', sans-serif;
        font-size: clamp(3.2rem, 7vw, 6rem);
        font-weight: 800;
        color: #ffffff;
        line-height: 1.1;
        letter-spacing: -0.04em;
        margin-bottom: 28px;
    }

    .lsb-ih-h1 em { font-style: normal; color: #00C9A7; display: block; line-height: 1.1; }

    /* ── Intro + subtitle ── */
    .lsb-ih-intro {
        font-size: 1.05rem;
        color: rgba(255,255,255,0.55);
        line-height: 1.8;
        font-weight: 300;
        margin-bottom: 12px;
        font-family: 'DM Sans', sans-serif;
    }
    .lsb-ih-subtitle {
        font-size: 0.95rem;
        color: rgba(255,255,255,0.35);
        line-height: 1.6;
        font-weight: 300;
        margin-bottom: 36px;
        font-family: 'DM Sans', sans-serif;
        font-style: italic;
    }

    /* ── Dropdown buttons ── */
    .lsb-ih-dropdowns {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        position: relative;
        z-index: 10;
    }
    .lsb-ih-dropdown {
        position: relative;
    }
    .lsb-ih-dropdown-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #00C9A7;
        border: none;
        color: #0D1B2A;
        font-family: 'Syne', sans-serif;
        font-size: 0.88rem;
        font-weight: 700;
        padding: 11px 20px;
        border-radius: 8px;
        cursor: pointer;
        letter-spacing: 0.01em;
        transition: background .2s, transform .15s;
        white-space: nowrap;
    }
    .lsb-ih-dropdown-btn:hover {
        background: #00A88C;
        transform: translateY(-1px);
    }
    .lsb-ih-dropdown-btn.secondary {
        background: rgba(255,255,255,0.07);
        border: 1px solid rgba(255,255,255,0.15);
        color: rgba(255,255,255,0.8);
    }
    .lsb-ih-dropdown-btn.secondary:hover {
        background: rgba(0,201,167,0.12);
        border-color: rgba(0,201,167,0.3);
        color: #00C9A7;
        transform: translateY(-1px);
    }
    .lsb-ih-dropdown-btn svg {
        transition: transform .2s;
        flex-shrink: 0;
    }
    .lsb-ih-dropdown.is-open .lsb-ih-dropdown-btn svg {
        transform: rotate(180deg);
    }

    /* ── Dropdown menu ── */
    .lsb-ih-dropdown-menu {
        display: none;
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        min-width: 220px;
        max-height: 280px;
        overflow-y: auto;
        background: #1B2F45;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        box-shadow: 0 12px 36px rgba(0,0,0,0.4);
        z-index: 100;
        padding: 6px;
    }
    .lsb-ih-dropdown-menu::-webkit-scrollbar { width: 4px; }
    .lsb-ih-dropdown-menu::-webkit-scrollbar-track { background: transparent; }
    .lsb-ih-dropdown-menu::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

    .lsb-ih-dropdown.is-open .lsb-ih-dropdown-menu {
        display: block;
    }
    .lsb-ih-dropdown-menu a {
        display: block;
        padding: 9px 14px;
        color: rgba(255,255,255,0.7);
        text-decoration: none;
        font-size: 0.88rem;
        font-family: 'DM Sans', sans-serif;
        border-radius: 6px;
        transition: all .15s;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .lsb-ih-dropdown-menu a:hover {
        background: rgba(0,201,167,0.12);
        color: #00C9A7;
    }
    .lsb-ih-dropdown-empty {
        padding: 10px 14px;
        color: rgba(255,255,255,0.3);
        font-size: 0.82rem;
        font-family: 'DM Sans', sans-serif;
        font-style: italic;
    }

    /* ── Right: hero image ── */
    .lsb-ih-image-wrap {
        border-radius: 20px;
        overflow: hidden;
    }
    .lsb-ih-image-wrap img {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 20px;
    }

    /* ── Responsive ── */
    @media (max-width: 1024px) {
        .lsb-ih-columns { grid-template-columns: 1fr; gap: 40px; }
        .lsb-ih-image-wrap { max-width: 560px; }
    }
    @media (max-width: 768px) {
        .lsb-ih-breadcrumb-bar { padding: 0 20px; }
        .lsb-ih-hero { padding: 40px 20px 52px; }
        .lsb-ih-h1 { font-size: 2.4rem; }
        .lsb-ih-dropdowns { gap: 8px; }
    }
    </style>
    <?php endif; ?>

    <!-- ── BREADCRUMB ── -->
    <nav class="lsb-ih-breadcrumb-bar" aria-label="Breadcrumb">
        <ol class="lsb-ih-breadcrumb">
            <li><a href="<?php echo esc_url( $home_url ); ?>">Home</a></li>
            <li class="lsb-ih-breadcrumb-sep" aria-hidden="true">›</li>
            <li><a href="<?php echo esc_url( $industry_url ); ?>">Industries</a></li>
            <li class="lsb-ih-breadcrumb-sep" aria-hidden="true">›</li>
            <li class="lsb-ih-breadcrumb-current" aria-current="page"><?php echo esc_html( $industry_name ); ?></li>
        </ol>
    </nav>

    <!-- ── HERO ── -->
    <section class="lsb-ih-hero" aria-labelledby="lsb-ih-heading-<?php echo esc_attr( $industry_slug ); ?>">

        <div class="lsb-ih-grid-bg" aria-hidden="true"></div>
        <div class="lsb-ih-glow-1"  aria-hidden="true"></div>
        <div class="lsb-ih-glow-2"  aria-hidden="true"></div>

        <div class="lsb-ih-inner">
            <div class="lsb-ih-columns<?php echo $hero_image ? '' : ' no-image'; ?>">

                <!-- LEFT -->
                <div class="lsb-ih-left">

                    <div class="lsb-ih-badge">
                        <span><?php echo esc_html( $industry_name ); ?></span>
                    </div>

                    <h1 class="lsb-ih-h1" id="lsb-ih-heading-<?php echo esc_attr( $industry_slug ); ?>">
                        <?php echo esc_html( $industry_name ); ?> Services<br><em>Near You</em>
                    </h1>

                    <?php if ( $hero_intro ) : ?>
                    <p class="lsb-ih-intro"><?php echo wp_kses_post( $hero_intro ); ?></p>
                    <?php endif; ?>

                    <?php if ( $hero_subtitle ) : ?>
                    <p class="lsb-ih-subtitle"><?php echo wp_kses_post( $hero_subtitle ); ?></p>
                    <?php endif; ?>

                    <!-- Dropdown buttons -->
                    <div class="lsb-ih-dropdowns">

                        <!-- View Services dropdown -->
                        <div class="lsb-ih-dropdown" id="lsb-dd-services-<?php echo esc_attr( $industry_slug ); ?>">
                            <button class="lsb-ih-dropdown-btn" type="button" aria-expanded="false"
                                    aria-controls="lsb-dd-services-menu-<?php echo esc_attr( $industry_slug ); ?>">
                                View Services
                                <svg width="12" height="8" viewBox="0 0 12 8" fill="none">
                                    <path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                            <div class="lsb-ih-dropdown-menu"
                                 id="lsb-dd-services-menu-<?php echo esc_attr( $industry_slug ); ?>"
                                 role="menu">
                                <?php if ( ! empty( $services ) ) : ?>
                                    <?php foreach ( $services as $svc ) :
                                        $svc_url = home_url( "/industries/{$industry_slug}/{$svc->post_name}/" );
                                    ?>
                                    <a href="<?php echo esc_url( $svc_url ); ?>" role="menuitem">
                                        <?php echo esc_html( $svc->post_title ); ?>
                                    </a>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <p class="lsb-ih-dropdown-empty">No services found</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Browse Cities dropdown -->
                        <div class="lsb-ih-dropdown" id="lsb-dd-cities-<?php echo esc_attr( $industry_slug ); ?>">
                            <button class="lsb-ih-dropdown-btn secondary" type="button" aria-expanded="false"
                                    aria-controls="lsb-dd-cities-menu-<?php echo esc_attr( $industry_slug ); ?>">
                                Browse Cities
                                <svg width="12" height="8" viewBox="0 0 12 8" fill="none">
                                    <path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                            <div class="lsb-ih-dropdown-menu"
                                 id="lsb-dd-cities-menu-<?php echo esc_attr( $industry_slug ); ?>"
                                 role="menu">
                                <?php if ( ! empty( $cities ) ) : ?>
                                    <?php foreach ( $cities as $city ) :
                                        $city_url = home_url( "/industries/{$industry_slug}/{$city->post_name}/" );
                                    ?>
                                    <a href="<?php echo esc_url( $city_url ); ?>" role="menuitem">
                                        <?php echo esc_html( $city->post_title ); ?>
                                    </a>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <p class="lsb-ih-dropdown-empty">No cities found</p>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div><!-- /dropdowns -->

                </div><!-- /left -->

                <!-- RIGHT: hero image (only if ACF field is set) -->
                <?php if ( $hero_image ) : ?>
                <div class="lsb-ih-image-wrap">
                    <img src="<?php echo esc_url( $hero_image ); ?>"
                         alt="<?php echo esc_attr( $industry_name ); ?> Services"
                         loading="eager">
                </div>
                <?php endif; ?>

            </div><!-- /columns -->
        </div><!-- /inner -->
    </section>

    <!-- ── DROPDOWN JS ── -->
    <script>
    (function () {
        var slug = <?php echo wp_json_encode( $industry_slug ); ?>;

        var ddIds = [
            'lsb-dd-services-' + slug,
            'lsb-dd-cities-'   + slug,
        ];

        ddIds.forEach( function ( id ) {
            var wrapper = document.getElementById( id );
            if ( ! wrapper ) return;

            var btn  = wrapper.querySelector( '.lsb-ih-dropdown-btn' );
            var menu = wrapper.querySelector( '.lsb-ih-dropdown-menu' );

            if ( ! btn || ! menu ) return;

            btn.addEventListener( 'click', function ( e ) {
                e.stopPropagation();
                var isOpen = wrapper.classList.contains( 'is-open' );

                // Close all other open dropdowns first
                document.querySelectorAll( '.lsb-ih-dropdown.is-open' ).forEach( function ( el ) {
                    el.classList.remove( 'is-open' );
                    el.querySelector( '.lsb-ih-dropdown-btn' ).setAttribute( 'aria-expanded', 'false' );
                });

                if ( ! isOpen ) {
                    wrapper.classList.add( 'is-open' );
                    btn.setAttribute( 'aria-expanded', 'true' );
                }
            });
        });

        // Close on outside click
        document.addEventListener( 'click', function () {
            document.querySelectorAll( '.lsb-ih-dropdown.is-open' ).forEach( function ( el ) {
                el.classList.remove( 'is-open' );
                el.querySelector( '.lsb-ih-dropdown-btn' ).setAttribute( 'aria-expanded', 'false' );
            });
        });

        // Close on Escape key
        document.addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'Escape' ) {
                document.querySelectorAll( '.lsb-ih-dropdown.is-open' ).forEach( function ( el ) {
                    el.classList.remove( 'is-open' );
                    el.querySelector( '.lsb-ih-dropdown-btn' ).setAttribute( 'aria-expanded', 'false' );
                });
            }
        });
    }());
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode( 'industry_hero', 'lsb_industry_hero_shortcode' );

endif;