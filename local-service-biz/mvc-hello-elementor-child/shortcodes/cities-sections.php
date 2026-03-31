<?php
/**
 * Combined Cities Shortcodes
 *
 * Shortcodes:
 * [featured_cities_block card_count="4"]
 * [featured_cities]
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ─────────────────────────────────────────────
   Shared Helpers
───────────────────────────────────────────── */

if ( ! function_exists( 'lsb_fc_get_city_industries' ) ) {
    function lsb_fc_get_city_industries( $city_term_id, $limit = 4 ) {
        $biz_ids = get_posts([
            'post_type'      => 'businesses',
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy' => 'city_cat',
                'field'    => 'term_id',
                'terms'    => $city_term_id,
            ]],
        ]);

        if ( empty( $biz_ids ) ) return [];

        $ind_names = [];

        foreach ( $biz_ids as $biz_id ) {
            $ind_terms = get_the_terms( $biz_id, 'industry_cat' );

            if ( $ind_terms && ! is_wp_error( $ind_terms ) ) {
                foreach ( $ind_terms as $ind ) {
                    $ind_names[ $ind->slug ] = $ind->name;
                }
            }

            if ( count( $ind_names ) >= $limit ) break;
        }

        return array_slice( array_values( $ind_names ), 0, $limit );
    }
}

if ( ! function_exists( 'lsb_fc_get_city_industry_count' ) ) {
    function lsb_fc_get_city_industry_count( $city_term_id ) {
        $biz_ids = get_posts([
            'post_type'      => 'businesses',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy' => 'city_cat',
                'field'    => 'term_id',
                'terms'    => $city_term_id,
            ]],
        ]);

        if ( empty( $biz_ids ) ) return 0;

        $industry_slugs = [];

        foreach ( $biz_ids as $biz_id ) {
            $ind_terms = get_the_terms( $biz_id, 'industry_cat' );

            if ( $ind_terms && ! is_wp_error( $ind_terms ) ) {
                foreach ( $ind_terms as $ind ) {
                    $industry_slugs[ $ind->slug ] = true;
                }
            }
        }

        return count( $industry_slugs );
    }
}

/* ─────────────────────────────────────────────
   Shortcode 1: [featured_cities_block]
───────────────────────────────────────────── */

if ( ! function_exists( 'lsb_general_featured_cities_shortcode' ) ) {
    function lsb_general_featured_cities_shortcode( $atts ) {

        $atts = shortcode_atts([
            'card_count' => 4,
        ], $atts, 'featured_cities_block');

        $card_count = absint( $atts['card_count'] );
        if ( $card_count < 1 ) $card_count = 4;

        $all_city_terms = get_terms([
            'taxonomy'   => 'city_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'parent'     => 0,
        ]);

        if ( is_wp_error( $all_city_terms ) || empty( $all_city_terms ) ) {
            return '<!-- [featured_cities_block] no city terms found -->';
        }

        $daily_seed = (int) date( 'Ymd' );

        usort( $all_city_terms, function( $a, $b ) use ( $daily_seed ) {
            return crc32( $daily_seed . $a->slug ) - crc32( $daily_seed . $b->slug );
        });

        $featured_cards   = array_slice( $all_city_terms, 0, $card_count );
        $remaining_cities = array_slice( $all_city_terms, $card_count );

        ob_start();
        ?>

        <section class="lsb-gc-section">
            <div class="lsb-gc-inner">

                <div class="lsb-gc-header">
                    <h2 class="lsb-gc-title">Cities You Can Explore</h2>
                    <p class="lsb-gc-intro">
                        Our directory includes businesses serving multiple cities and surrounding areas.
                        Browsing by location helps you find professionals closer to you and discover services
                        available in your area.
                    </p>
                </div>

                <?php if ( ! empty( $featured_cards ) ) : ?>
                    <div class="lsb-gc-cards">
                        <?php foreach ( $featured_cards as $city ) :
                            $city_url       = home_url( '/cities/' . $city->slug . '/' );
                            $biz_count      = (int) $city->count;
                            $industry_count = lsb_fc_get_city_industry_count( $city->term_id );
                            $industry_names = lsb_fc_get_city_industries( $city->term_id, 3 );
                        ?>
                            <a href="<?php echo esc_url( $city_url ); ?>" class="lsb-gc-card">
                                <div class="lsb-gc-card-name"><?php echo esc_html( $city->name ); ?></div>

                                <div class="lsb-gc-card-meta">
                                    <?php echo esc_html( $industry_count ); ?> industr<?php echo $industry_count === 1 ? 'y' : 'ies'; ?>
                                    &middot;
                                    <?php echo esc_html( number_format( $biz_count ) ); ?>+ businesses
                                </div>

                                <?php if ( ! empty( $industry_names ) ) : ?>
                                    <div class="lsb-gc-card-tags">
                                        <?php foreach ( $industry_names as $industry_name ) : ?>
                                            <span class="lsb-gc-card-tag"><?php echo esc_html( $industry_name ); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="lsb-gc-card-arrow">Explore city →</div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $remaining_cities ) ) : ?>
                    <div class="lsb-gc-list-wrap">
                        <ul class="lsb-gc-city-list">
                            <?php
                            usort( $remaining_cities, function( $a, $b ) {
                                return strcasecmp( $a->name, $b->name );
                            });

                            foreach ( $remaining_cities as $city ) :
                                $city_url = home_url( '/cities/' . $city->slug . '/' );
                            ?>
                                <li>
                                    <a href="<?php echo esc_url( $city_url ); ?>" class="lsb-gc-city-link">
                                        <span class="lsb-gc-city-dot"></span>
                                        <?php echo esc_html( $city->name ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

            </div>
        </section>

        <?php
        return ob_get_clean();
    }

    add_shortcode( 'featured_cities_block', 'lsb_general_featured_cities_shortcode' );
}

/* ─────────────────────────────────────────────
   Shortcode 2: [featured_cities]
───────────────────────────────────────────── */

if ( ! function_exists( 'lsb_homepage_featured_cities_shortcode' ) ) {
    function lsb_homepage_featured_cities_shortcode( $atts ) {

        $atts = shortcode_atts( [
            'hero_city'   => 'los-angeles',
            'grid_count'  => 4,
            'pill_count'  => 9,
        ], $atts, 'featured_cities' );

        $hero_slug  = sanitize_title( $atts['hero_city'] );
        $grid_count = absint( $atts['grid_count'] );
        $pill_count = absint( $atts['pill_count'] );

        $all_city_terms = get_terms( [
            'taxonomy'   => 'city_cat',
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
            'parent'     => 0,
        ] );

        if ( is_wp_error( $all_city_terms ) || empty( $all_city_terms ) ) {
            return '<!-- [featured_cities] no city terms found -->';
        }

        $total_cities = count( $all_city_terms );

        $hero_term   = null;
        $other_terms = [];

        foreach ( $all_city_terms as $term ) {
            if ( $term->slug === $hero_slug ) {
                $hero_term = $term;
            } else {
                $other_terms[] = $term;
            }
        }

        if ( ! $hero_term && ! empty( $all_city_terms ) ) {
            $hero_term   = $all_city_terms[0];
            $other_terms = array_slice( $all_city_terms, 1 );
        }

        $daily_seed = (int) date( 'Ymd' );

        usort( $other_terms, function( $a, $b ) use ( $daily_seed ) {
            return crc32( $daily_seed . $a->slug ) - crc32( $daily_seed . $b->slug );
        } );

        $grid_terms      = array_slice( $other_terms, 0, $grid_count );
        $pill_terms      = array_slice( $other_terms, $grid_count, $pill_count );
        $remaining_count = max( 0, $total_cities - 1 - $grid_count - $pill_count );

        $hero_biz_count  = (int) $hero_term->count;
        $hero_industries = lsb_fc_get_city_industries( $hero_term->term_id, 4 );
        $hero_ind_total  = lsb_fc_get_city_industry_count( $hero_term->term_id );
        $hero_url        = home_url( '/cities/' . $hero_term->slug . '/' );
        $cities_index    = home_url( '/locations/' );

        ob_start();
        ?>

        <style id="lsb-fc-css">
        section.fc-section{background:#F5F7FA!important;padding:100px 40px!important;margin:0!important;position:relative!important;z-index:1!important;box-sizing:border-box!important}
        section.fc-section *{box-sizing:border-box}
        section.fc-section .fc-inner{max-width:1200px;margin:0 auto;width:100%}
        section.fc-section .fc-header{display:flex!important;justify-content:space-between!important;align-items:flex-end!important;margin-bottom:48px!important;flex-wrap:nowrap!important;gap:20px!important}
        section.fc-section .fc-header-left{display:flex;flex-direction:column}
        section.fc-section .fc-label{display:block!important;font-size:.75rem!important;font-weight:700!important;letter-spacing:.12em!important;text-transform:uppercase!important;color:#00C9A7!important;margin-bottom:10px!important;font-family:'DM Sans',sans-serif!important}
        section.fc-section .fc-title{font-family:'Syne',sans-serif!important;font-size:clamp(1.8rem,3.5vw,2.8rem)!important;font-weight:800!important;color:#0D1B2A!important;letter-spacing:-.02em!important;line-height:1.1!important;margin:0!important;padding:0!important}
        section.fc-section .fc-view-all{font-family:'DM Sans',sans-serif!important;font-size:.95rem!important;font-weight:700!important;color:#00A88C!important;text-decoration:none!important;display:inline-flex!important;align-items:center!important;gap:6px!important;transition:gap .2s!important;white-space:nowrap!important;flex-shrink:0!important;outline:none!important}
        section.fc-section .fc-view-all:hover{gap:10px!important}
        section.fc-section .fc-layout{display:grid!important;grid-template-columns:1fr 1fr!important;gap:20px!important;margin-bottom:20px!important}
        section.fc-section .fc-hero-card{background:#0D1B2A!important;border-radius:20px!important;padding:44px 40px!important;text-decoration:none!important;display:flex!important;flex-direction:column!important;justify-content:flex-end!important;min-height:340px!important;position:relative!important;overflow:hidden!important;transition:transform .2s,box-shadow .2s!important;border:none!important;outline:none!important}
        section.fc-section .fc-hero-card::before{content:''!important;position:absolute!important;inset:0!important;background:radial-gradient(ellipse at 80% 20%,rgba(0,201,167,.18) 0%,transparent 55%),radial-gradient(ellipse at 20% 80%,rgba(244,197,66,.10) 0%,transparent 55%)!important;pointer-events:none!important}
        section.fc-section .fc-hero-card:hover,section.fc-section .fc-card:hover{transform:translateY(-3px)!important}
        section.fc-section .fc-hero-grid-bg{position:absolute!important;inset:0!important;background-image:linear-gradient(rgba(0,201,167,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(0,201,167,.05) 1px,transparent 1px)!important;background-size:40px 40px!important;pointer-events:none!important}
        section.fc-section .fc-hero-badge{position:absolute!important;top:28px!important;left:40px!important;background:#00C9A7!important;color:#0D1B2A!important;font-family:'Syne',sans-serif!important;font-size:.72rem!important;font-weight:800!important;letter-spacing:.10em!important;text-transform:uppercase!important;padding:6px 12px!important;border-radius:100px!important;display:inline-block!important;line-height:1.2!important}
        section.fc-section .fc-hero-content{position:relative!important;z-index:1!important}
        section.fc-section .fc-hero-pin{font-size:2rem!important;margin-bottom:14px!important;display:block!important;line-height:1!important}
        section.fc-section .fc-hero-name{font-family:'Syne',sans-serif!important;font-size:2rem!important;font-weight:800!important;color:#FFFFFF!important;letter-spacing:-.02em!important;margin-bottom:10px!important;line-height:1!important;padding:0!important}
        section.fc-section .fc-hero-meta{font-size:1rem!important;color:rgba(255,255,255,.82)!important;margin-bottom:22px!important;font-weight:400!important;font-family:'DM Sans',sans-serif!important;line-height:1.6!important}
        section.fc-section .fc-hero-tags{display:flex!important;flex-wrap:wrap!important;gap:6px!important;margin:0!important;padding:0!important;list-style:none!important}
        section.fc-section .fc-hero-tag{background:rgba(255,255,255,.08)!important;border:1px solid rgba(255,255,255,.14)!important;color:rgba(255,255,255,.82)!important;font-size:.82rem!important;padding:5px 10px!important;border-radius:100px!important;font-weight:500!important;font-family:'DM Sans',sans-serif!important;line-height:1.3!important;display:inline-block!important}
        section.fc-section .fc-grid-right{display:grid!important;grid-template-columns:1fr 1fr!important;grid-template-rows:1fr 1fr!important;gap:16px!important}
        section.fc-section .fc-card{background:#FFFFFF!important;border:1px solid #E4EAF2!important;border-radius:16px!important;padding:24px 22px!important;text-decoration:none!important;display:flex!important;flex-direction:column!important;gap:8px!important;transition:transform .2s,box-shadow .2s,border-color .2s!important;position:relative!important;overflow:hidden!important;outline:none!important}
        section.fc-section .fc-card-name{font-family:'Syne',sans-serif!important;font-weight:800!important;font-size:1rem!important;color:#0D1B2A!important;letter-spacing:-.01em!important;line-height:1.3!important}
        section.fc-section .fc-card-meta{font-size:.95rem!important;color:#5F6F85!important;font-weight:500!important;font-family:'DM Sans',sans-serif!important;line-height:1.6!important}
        section.fc-section .fc-card-arrow{margin-top:auto!important;padding-top:10px!important;font-size:.95rem!important;color:#00A88C!important;font-weight:700!important;font-family:'DM Sans',sans-serif!important;opacity:0!important;transform:translateX(-4px)!important;transition:opacity .2s,transform .2s!important}
        section.fc-section .fc-card:hover .fc-card-arrow{opacity:1!important;transform:translateX(0)!important}
        section.fc-section .fc-more-cities{display:flex!important;flex-wrap:wrap!important;gap:10px!important;align-items:center!important;margin-top:4px!important}
        section.fc-section .fc-more-label{font-size:.85rem!important;color:#5F6F85!important;font-weight:700!important;font-family:'DM Sans',sans-serif!important;text-transform:uppercase!important;letter-spacing:.06em!important;margin-right:4px!important;line-height:1!important}
        section.fc-section .fc-pill{background:#FFFFFF!important;border:1px solid #E4EAF2!important;border-radius:100px!important;padding:10px 18px!important;font-size:.95rem!important;color:#0D1B2A!important;font-weight:600!important;font-family:'DM Sans',sans-serif!important;text-decoration:none!important;transition:transform .2s,background .2s,border-color .2s!important;display:inline-flex!important;align-items:center!important;gap:6px!important;line-height:1!important}
        section.fc-section .fc-pill-dot{width:6px!important;height:6px!important;background:#00C9A7!important;border-radius:50%!important;flex-shrink:0!important;display:inline-block!important}
        section.fc-section .fc-pill:hover{background:#00C9A7!important;border-color:#00C9A7!important;color:#0D1B2A!important;transform:translateY(-1px)!important}
        @media (max-width:980px){section.fc-section{padding:80px 24px!important}section.fc-section .fc-header{flex-wrap:wrap!important;align-items:flex-start!important}section.fc-section .fc-layout{grid-template-columns:1fr!important}section.fc-section .fc-hero-card{padding:38px 28px!important;min-height:300px!important}section.fc-section .fc-hero-badge{left:28px!important}section.fc-section .fc-grid-right{grid-template-columns:1fr 1fr!important}}
        @media (max-width:640px){section.fc-section .fc-grid-right{grid-template-columns:1fr!important}}
        </style>

        <section class="fc-section">
            <div class="fc-inner">

                <div class="fc-header">
                    <div class="fc-header-left">
                        <span class="fc-label">Browse by Location</span>
                        <h2 class="fc-title">Top Cities We Serve</h2>
                    </div>
                    <a href="<?php echo esc_url( $cities_index ); ?>" class="fc-view-all">
                        View All <?php echo esc_html( $total_cities ); ?> Cities →
                    </a>
                </div>

                <div class="fc-layout">
                    <a href="<?php echo esc_url( $hero_url ); ?>" class="fc-hero-card">
                        <div class="fc-hero-grid-bg"></div>
                        <span class="fc-hero-badge">Most Popular</span>
                        <div class="fc-hero-content">
                            <span class="fc-hero-pin">📍</span>
                            <div class="fc-hero-name"><?php echo esc_html( $hero_term->name ); ?></div>
                            <div class="fc-hero-meta">
                                <?php echo esc_html( $hero_ind_total ); ?> industries
                                &middot;
                                <?php echo esc_html( number_format( $hero_biz_count ) ); ?>+ businesses listed
                            </div>

                            <?php if ( ! empty( $hero_industries ) ) : ?>
                                <div class="fc-hero-tags">
                                    <?php foreach ( $hero_industries as $ind_name ) : ?>
                                        <span class="fc-hero-tag"><?php echo esc_html( $ind_name ); ?></span>
                                    <?php endforeach; ?>

                                    <?php if ( $hero_ind_total > count( $hero_industries ) ) : ?>
                                        <span class="fc-hero-tag">+<?php echo esc_html( $hero_ind_total - count( $hero_industries ) ); ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>

                    <div class="fc-grid-right">
                        <?php foreach ( $grid_terms as $city ) :
                            $city_url       = home_url( '/cities/' . $city->slug . '/' );
                            $biz_count      = (int) $city->count;
                            $city_ind_count = lsb_fc_get_city_industry_count( $city->term_id );
                        ?>
                            <a href="<?php echo esc_url( $city_url ); ?>" class="fc-card">
                                <div class="fc-card-pin">📍</div>
                                <div class="fc-card-name"><?php echo esc_html( $city->name ); ?></div>
                                <div class="fc-card-meta">
                                    <?php echo esc_html( $city_ind_count ); ?> industr<?php echo $city_ind_count === 1 ? 'y' : 'ies'; ?>
                                    &middot;
                                    <?php echo esc_html( number_format( $biz_count ) ); ?>+ businesses
                                </div>
                                <div class="fc-card-arrow">Explore →</div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ( ! empty( $pill_terms ) ) : ?>
                    <div class="fc-more-cities">
                        <span class="fc-more-label">More Cities:</span>
                        <?php foreach ( $pill_terms as $city ) : ?>
                            <a href="<?php echo esc_url( home_url( '/cities/' . $city->slug . '/' ) ); ?>" class="fc-pill">
                                <span class="fc-pill-dot"></span>
                                <?php echo esc_html( $city->name ); ?>
                            </a>
                        <?php endforeach; ?>

                        <?php if ( $remaining_count > 0 ) : ?>
                            <a href="<?php echo esc_url( $cities_index ); ?>" class="fc-pill">
                                <span class="fc-pill-dot"></span>
                                + <?php echo esc_html( number_format( $remaining_count ) ); ?> more →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </section>

        <?php
        return ob_get_clean();
    }

    add_shortcode( 'featured_cities', 'lsb_homepage_featured_cities_shortcode' );
}