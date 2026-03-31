<?php
/**
 * Business Shortcodes
 * File: shortcodes-business.php
 * Location: your-child-theme/shortcodes/shortcodes-business.php
 *
 * Add this one line to your functions.php:
 * require_once get_stylesheet_directory() . '/shortcodes/shortcodes-business.php';
 */


/**
 * Shortcode: [business_hero]
 * Unified hero section: logo, title, badge, meta row, gallery, CTA buttons
 */
function lsb_business_hero() {
    $post_id = get_the_ID();

    // --- Data ---
    $title         = get_the_title( $post_id );
    $logo_field    = get_field( 'business_logo', $post_id );
    $logo_url      = is_array( $logo_field ) ? ( $logo_field['url'] ?? '' ) : $logo_field;
    $logo_initials = '';
    if ( ! $logo_url ) {
        // Generate initials fallback from business name
        $words = explode( ' ', $title );
        foreach ( array_slice( $words, 0, 2 ) as $w ) {
            $logo_initials .= strtoupper( substr( $w, 0, 1 ) );
        }
    }

  
    // Contact
    $phone   = get_field( 'business_phone', $post_id );
    $website = get_field( 'business_website', $post_id );
    $email   = get_field( 'business_email', $post_id );

    // Rating / meta
    $rating       = get_field( 'business_rating', $post_id );
    $review_count = get_field( 'business_review_count', $post_id );
    $address      = get_field( 'business_address', $post_id );
    $founded      = get_field( 'business_year_founded', $post_id );

    // Industry badge
    $industry_terms = get_the_terms( $post_id, 'industry_cat' );
    $industry_name  = ( $industry_terms && ! is_wp_error( $industry_terms ) ) ? $industry_terms[0]->name : '';

    // Verification badge
    $badge_html = '';
    if ( function_exists( 'lsb_get_business_badge' ) ) {
        $badge = lsb_get_business_badge( $post_id, 'small' );
        if ( $badge ) {
            if ( function_exists( 'lsb_print_badge_styles' ) ) {
                $badge_html .= lsb_print_badge_styles();
            }
            $badge_html .= $badge;
        }
    }

    // Unique ID for lightbox
    $uid       = 'lsb_hero_' . $post_id;
    

    // --- Stars ---
    $stars_html = '';
    if ( $rating ) {
        $full  = floor( $rating );
        $half  = ( $rating - $full >= 0.5 ) ? '★' : '';
        $stars_html = str_repeat( '★', $full ) . $half;
    }

    // --- Output ---
    $out = '';

    // ---- CSS ----
    $out .= '
    <style>
        .lsb-hero {
            background: #0D1B2A;
            padding: 36px 36px 32px;
            position: relative;
            overflow: hidden;
            font-family: "DM Sans", sans-serif;
        }
        .lsb-hero::before {
            content: "";
            position: absolute;
            top: -80px; right: -80px;
            width: 320px; height: 320px;
            background: radial-gradient(circle, rgba(0,201,167,0.10) 0%, transparent 70%);
            pointer-events: none;
        }
        .lsb-hero::after {
            content: "";
            position: absolute;
            bottom: -60px; left: 40px;
            width: 240px; height: 240px;
            background: radial-gradient(circle, rgba(244,197,66,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        /* ---- TOP ROW ---- */
        .lsb-hero-top {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 24px;
            align-items: flex-start;
            position: relative;
            z-index: 1;
        }

        /* Logo */
        .lsb-hero-logo {
            width: 100px;
            height: 100px;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lsb-hero-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .lsb-hero-logo-initials {
            font-family: "Syne", sans-serif;
            font-weight: 800;
            font-size: 2rem;
            color: #00C9A7;
            letter-spacing: -0.02em;
        }

        /* Middle: title + badge + meta */
        .lsb-hero-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 0;
        }
        .lsb-hero-badges {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .lsb-hero-industry-pill {
            display: inline-flex;
            align-items: center;
            background: rgba(0,201,167,0.12);
            border: 1px solid rgba(0,201,167,0.25);
            border-radius: 100px;
            padding: 4px 14px;
        }
        .lsb-hero-industry-pill span {
            color: #00C9A7;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }
        .lsb-hero-title {
            font-family: "Syne", sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.4;
            letter-spacing: -0.01em;
            margin: 0;
        }
        .lsb-hero-tagline {
            font-size: 1.15rem;
            color: rgba(255,255,255,0.5);
            line-height: 1.5;
            font-weight: 300;
            margin: 0;
        }
        .lsb-hero-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px 14px;
        }
        .lsb-hero-stars { color: #F4C542; font-size: 0.95rem; letter-spacing: 1px; }
        .lsb-hero-rating-num {
            font-family: "Syne", sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            color: #ffffff;
        }
        .lsb-hero-review-count { font-size: 0.78rem; color: #8A9BB0; }
        .lsb-hero-meta-sep { color: rgba(255,255,255,0.15); font-size: 0.75rem; }
        .lsb-hero-meta-pill { font-size: 0.78rem; color: rgba(255,255,255,0.45); display: flex; align-items: center; gap: 4px; }

        /* Right: CTA buttons */
        .lsb-hero-cta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 160px;
            max-width: 200px;
            flex-shrink: 0;
        }
        .lsb-hero-btn {
            display: block;
            text-align: center;
            border-radius: 10px;
            text-decoration: none;
            font-family: "Syne", sans-serif;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 11px 16px;
            transition: all 0.2s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            box-sizing: border-box;
            width: 100%;
        }
        .lsb-hero-btn-phone {
            background: #00C9A7;
            color: #0D1B2A !important;
        }
        .lsb-hero-btn-phone:hover { background: #00A88C; transform: translateY(-1px); color: #0D1B2A !important; }
        .lsb-hero-btn-alt {
            background: rgba(255,255,255,0.06);
            color: #ffffff !important;
            border: 1px solid rgba(255,255,255,0.12);
            font-weight: 600;
        }
        .lsb-hero-btn-alt:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.25); color: #ffffff !important; }

      
      
       

        /* ---- RESPONSIVE ---- */
        @media (max-width: 900px) {
            .lsb-hero-top {
                grid-template-columns: auto 1fr;
                grid-template-rows: auto auto;
            }
            .lsb-hero-cta {
                grid-column: 1 / -1;
                flex-direction: row;
                max-width: 100%;
                min-width: 0;
            }
            .lsb-hero-btn { flex: 1; }
                   }
        @media (max-width: 600px) {
            .lsb-hero { padding: 20px 16px; }
            .lsb-hero-logo { width: 72px; height: 72px; }
            .lsb-hero-title { font-size: 1.2rem; }
            .lsb-hero-cta { flex-direction: column; }
                   }

/* ── Business Hero H1 ───────────────────────────────────────────────────────── */
.lsb-biz-hero__h1 {
  font-family: "Syne" sans-serif !important;
  font-size: 45px !important;
  font-weight: 700 !important;
  line-height: 1.3 !important;
  letter-spacing: -0.01em !important;
  color: #ffffff !important;
  margin: 0 !important;
}

@media (max-width: 1024px) {
  .lsb-biz-hero__h1 { font-size: 18px !important; }
}

@media (max-width: 768px) {
  .lsb-biz-hero__h1 { font-size: 16px !important; }
}

    </style>';

    
    // ---- Hero wrapper ----
    $out .= '<div class="lsb-hero">';

    // ---- Top row ----
    $out .= '<div class="lsb-hero-top">';

    // Logo
    $out .= '<div class="lsb-hero-logo">';
    if ( $logo_url ) {
        $out .= '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $title ) . ' logo">';
    } else {
        $out .= '<span class="lsb-hero-logo-initials">' . esc_html( $logo_initials ) . '</span>';
    }
    $out .= '</div>';

    // Info: badges + title + meta
    $out .= '<div class="lsb-hero-info">';

    // Badges row
    $out .= '<div class="lsb-hero-badges">';
    if ( $industry_name ) {
        $out .= '<div class="lsb-hero-industry-pill"><span>' . esc_html( $industry_name ) . '</span></div>';
    }
    if ( $badge_html ) {
        $out .= $badge_html;
    }
    $out .= '</div>';

    // Title
  $out .= '<h1 class="lsb-biz-hero__h1">' . esc_html( $title ) . '</h1>';

    // Tagline
    $tagline = get_field( 'business_tagline', $post_id );
    if ( $tagline ) {
        $out .= '<p class="lsb-hero-tagline">' . esc_html( $tagline ) . '</p>';
    }

    // Meta row
    $meta_parts = [];
    if ( $rating ) {
        $meta_parts[] = '<span style="display:flex;align-items:center;gap:6px;">'
            . '<span class="lsb-hero-stars">' . esc_html( $stars_html ) . '</span>'
            . '<span class="lsb-hero-rating-num">' . esc_html( number_format( $rating, 1 ) ) . '</span>'
            . ( $review_count ? '<span class="lsb-hero-review-count">(' . esc_html( $review_count ) . ' reviews)</span>' : '' )
            . '</span>';
    }
    if ( $address ) {
        $meta_parts[] = '<span class="lsb-hero-meta-pill">📍 ' . esc_html( $address ) . '</span>';
    }
    if ( $founded ) {
        $meta_parts[] = '<span class="lsb-hero-meta-pill">📅 Est. ' . esc_html( $founded ) . '</span>';
    }
    if ( $meta_parts ) {
        $out .= '<div class="lsb-hero-meta">';
        $out .= implode( '<span class="lsb-hero-meta-sep">·</span>', $meta_parts );
        $out .= '</div>';
    }

    $out .= '</div>'; // .lsb-hero-info

    // CTA buttons
    $out .= '<div class="lsb-hero-cta">';
    if ( $phone ) {
        $clean = preg_replace( '/[^0-9+]/', '', $phone );
        $out .= '<a href="tel:' . esc_attr( $clean ) . '" class="lsb-hero-btn lsb-hero-btn-phone">' . esc_html( $phone ) . '</a>';
    }
    if ( $website ) {
        $out .= '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener noreferrer" class="lsb-hero-btn lsb-hero-btn-alt">Visit Website</a>';
    }
    if ( $email ) {
        $out .= '<a href="mailto:' . esc_attr( $email ) . '" class="lsb-hero-btn lsb-hero-btn-alt">Send Message</a>';
    }
    $out .= '</div>'; // .lsb-hero-cta

    $out .= '</div>'; // .lsb-hero-top

  

    $out .= '</div>'; // .lsb-hero

   
    return $out;
}
add_shortcode( 'business_hero', 'lsb_business_hero' );


/**
 * Shortcode: [business_industry_badge]
 */
function lsb_business_industry_badge() {
    $pid   = get_the_ID();
    $terms = get_the_terms( $pid, 'industry_cat' );
    if ( ! $terms || is_wp_error( $terms ) ) return '';
    $industry = $terms[0];

    $output  = '<div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px;">';

    // Industry pill
    $output .= '<div class="lsb-category-tag" style="margin-bottom:0;">';
    $output .= '<span>' . esc_html( $industry->name ) . '</span>';
    $output .= '</div>';

    // Verification badge — inline, right next to industry pill
    if ( function_exists( 'lsb_get_business_badge' ) ) {
        $badge = lsb_get_business_badge( $pid, 'small' );
        if ( $badge ) {
            $output .= lsb_print_badge_styles();
            $output .= $badge;
        }
    }

    $output .= '</div>';

    $output .= '
    <style>
        .lsb-category-tag {
            display: inline-flex;
            align-items: center;
            background: rgba(0,201,167,0.12);
            border: 1px solid rgba(0,201,167,0.2);
            border-radius: 100px;
            padding: 4px 14px;
        }
        .lsb-category-tag span {
            color: #00C9A7;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
    </style>';
    return $output;
}
add_shortcode( 'business_industry_badge', 'lsb_business_industry_badge' );


/**
 * Shortcode: [business_meta_row]
 */
function lsb_business_meta_row() {
    $post_id      = get_the_ID();
    $rating       = get_field( 'business_rating', $post_id );
    $review_count = get_field( 'business_review_count', $post_id );
    $address      = get_field( 'business_address', $post_id );
    $founded      = get_field( 'business_year_founded', $post_id );
    $items = [];

    if ( $rating ) {
        $full_stars = floor( $rating );
        $half_star  = ( $rating - $full_stars >= 0.5 ) ? '★' : '';
        $stars_html = str_repeat( '★', $full_stars ) . $half_star;
        $count_html = $review_count
            ? '<span class="lsb-rating-count">(' . esc_html( $review_count ) . ' reviews)</span>'
            : '';
        $items[] = '<span class="lsb-meta-rating">
                        <span class="lsb-stars">' . esc_html( $stars_html ) . '</span>
                        <span class="lsb-rating-num">' . esc_html( number_format( $rating, 1 ) ) . '</span>
                        ' . $count_html . '
                    </span>';
    }
    if ( $address ) {
        $items[] = '<span class="lsb-meta-pill">📍 ' . esc_html( $address ) . '</span>';
    }
    if ( $founded ) {
        $items[] = '<span class="lsb-meta-pill">📅 In business since ' . esc_html( $founded ) . '</span>';
    }
    if ( empty( $items ) ) return '';

    $output  = '<div class="lsb-meta-row">';
    $output .= implode( '<span class="lsb-meta-sep">·</span>', $items );
    $output .= '</div>';
    $output .= '
    <style>
        .lsb-meta-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px 16px;
            margin-top: 10px;
        }
        .lsb-stars { color: #F4C542; font-size: 1rem; letter-spacing: 1px; }
        .lsb-rating-num {
            font-family: "Syne", sans-serif;
            font-weight: 700;
            font-size: 1rem;
            color: #ffffff;
            margin-left: 6px;
        }
        .lsb-rating-count { font-size: 0.82rem; color: #8A9BB0; margin-left: 4px; }
        .lsb-meta-rating { display: flex; align-items: center; }
        .lsb-meta-pill { font-size: 0.82rem; color: rgba(255,255,255,0.5); display: flex; align-items: center; gap: 5px; }
        .lsb-meta-sep { color: rgba(255,255,255,0.15); font-size: 0.8rem; }
    </style>';
    return $output;
}
add_shortcode( 'business_meta_row', 'lsb_business_meta_row' );


/**
 * Shortcode: [business_cta_buttons]
 */
function lsb_business_cta_buttons() {
    $post_id = get_the_ID();
    $phone   = get_field( 'business_phone', $post_id );
    $website = get_field( 'business_website', $post_id );
    $email   = get_field( 'business_email', $post_id );
    if ( ! $phone && ! $website && ! $email ) return '';

    $out = '
    <style>
        .lsb-cta-buttons { display: flex; flex-direction: column; gap: 8px; width: 100%; }
        .lsb-btn-primary {
            display: block; text-align: center;
            background: #00C9A7; color: #0D1B2A !important;
            font-family: "Syne", sans-serif; font-weight: 700; font-size: 0.82rem;
            padding: 12px 10px; border-radius: 8px; text-decoration: none;
            letter-spacing: 0.01em; transition: background 0.2s, transform 0.15s;
            width: 100%; box-sizing: border-box;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .lsb-btn-primary:hover { background: #00A88C; transform: translateY(-2px); color: #0D1B2A !important; }
        .lsb-btn-secondary {
            display: block; text-align: center;
            background: rgba(255,255,255,0.06); color: #ffffff !important;
            font-family: "Syne", sans-serif; font-weight: 600; font-size: 0.82rem;
            padding: 11px 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12);
            text-decoration: none; letter-spacing: 0.01em; transition: all 0.2s;
            width: 100%; box-sizing: border-box;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .lsb-btn-secondary:hover { border-color: rgba(255,255,255,0.4); background: rgba(255,255,255,0.1); color: #ffffff !important; }
    </style>
    <div class="lsb-cta-buttons">';

    if ( $phone ) {
        $clean = preg_replace( '/[^0-9+]/', '', $phone );
        $out .= '<a href="tel:' . esc_attr( $clean ) . '" class="lsb-btn-primary">' . esc_html( $phone ) . '</a>';
    }
    if ( $website ) {
        $out .= '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener noreferrer" class="lsb-btn-secondary">Visit Website</a>';
    }
    if ( $email ) {
        $out .= '<a href="mailto:' . esc_attr( $email ) . '" class="lsb-btn-secondary">Send Message</a>';
    }
    $out .= '</div>';
    return $out;
}
add_shortcode( 'business_cta_buttons', 'lsb_business_cta_buttons' );




/**
 * Shortcode: [business_photo_carousel]
 * Dynamic square-image grid for business profile pages
 * Uses: image_usage = square, with business → city → industry fallback
 */
function lsb_business_photo_carousel( $atts ) {

    $atts = shortcode_atts( array(
        'limit' => 4,
    ), $atts, 'business_photo_carousel' );

    $limit = max( 1, absint( $atts['limit'] ) );

    if ( ! function_exists( 'mvc_de_get_square_images' ) ) {
        return '';
    }

    $images = mvc_de_get_square_images( $limit );

    if ( empty( $images ) ) {
        return '';
    }

    $output = '
    <style>
        .lsb-photo-grid {
            display: grid;
            grid-template-columns: repeat(4, 250px);
            gap: 8px;
            justify-content: center;
            margin: 0 auto;
        }
        .lsb-photo-grid-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            background: #1B2F45;
            width: 250px;
            height: 250px;
        }
        .lsb-photo-grid-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        @media (max-width: 1200px) {
            .lsb-photo-grid {
                grid-template-columns: repeat(2, 250px);
            }
        }

        @media (max-width: 600px) {
            .lsb-photo-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
            .lsb-photo-grid-item {
                width: 100%;
                height: auto;
                aspect-ratio: 1 / 1;
            }
        }
    </style>

    <div class="lsb-photo-grid">';

    foreach ( $images as $img ) {
        $attachment_id = (int) $img->ID;

        $context = function_exists( 'mvc_de_get_current_page_context' )
            ? mvc_de_get_current_page_context()
            : array();

        $alt = function_exists( 'mvc_de_get_dynamic_image_alt' )
            ? mvc_de_get_dynamic_image_alt( $attachment_id, $context )
            : '';

        if ( empty( $alt ) ) {
            $alt = get_the_title( $attachment_id );
        }

        $image_html = wp_get_attachment_image(
            $attachment_id,
            'medium_large',
            false,
            array(
                'alt'   => esc_attr( $alt ),
                'class' => 'lsb-photo-grid-img',
            )
        );

        if ( empty( $image_html ) ) {
            continue;
        }

        $output .= '<div class="lsb-photo-grid-item">';
        $output .= $image_html;
        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode( 'business_photo_carousel', 'lsb_business_photo_carousel' );
/**
 * Shortcode: [business_hours]
 */
function lsb_business_hours() {
    $post_id = get_the_ID();
    $hours   = get_field( 'business_hours', $post_id );

    if ( empty( $hours ) ) return '';

    $output = '
    <style>
        .lsb-hours-wrap {
            background: #ffffff;
            border: 1px solid #E4EAF2;
            border-radius: 12px;
            padding: 24px;
            font-family: "DM Sans", sans-serif;
        }
        .lsb-hours-title {
            font-family: "Syne", sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            color: #0D1B2A;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #E4EAF2;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lsb-hours-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            border-bottom: 1px solid #F0F4F8;
            font-size: 0.875rem;
        }
        .lsb-hours-row:last-child { border-bottom: none; }
        .lsb-hours-day { color: #1A2535; font-weight: 500; }
        .lsb-hours-time { color: #3D4F63; font-weight: 400; }
        .lsb-hours-closed {
            color: #E05C5C; font-weight: 500;
            font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;
        }
    </style>
    <div class="lsb-hours-wrap">
        <div class="lsb-hours-title">🕐 Business Hours</div>';

    foreach ( $hours as $row ) {
        $day        = esc_html( $row['day_label'] );
        $is_closed  = (bool) $row['closed'];
        $open_time  = esc_html( $row['open_time'] );
        $close_time = esc_html( $row['close_time'] );

        $output .= '<div class="lsb-hours-row">';
        $output .= '<span class="lsb-hours-day">' . $day . '</span>';
        if ( $is_closed ) {
            $output .= '<span class="lsb-hours-closed">Closed</span>';
        } else {
            $output .= '<span class="lsb-hours-time">' . $open_time . ' – ' . $close_time . '</span>';
        }
        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}
add_shortcode( 'business_hours', 'lsb_business_hours' );


/**
 * Shortcode: [business_about]
 */
function lsb_business_about() {
    $post_id     = get_the_ID();
    $description = get_field( 'business_full_description', $post_id );
    if ( ! $description ) return '';

    $output = '
    <style>
        .lsb-about-wrap {
            background: #ffffff;
            border: 1px solid #E4EAF2;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 24px;
        }
        .lsb-card-title {
            font-family: "Syne", sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #0D1B2A;
            letter-spacing: -0.01em;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #E4EAF2;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lsb-about-text {
            font-size: 1rem;
            color: #0D1B2A;
            line-height: 1.8;
            font-weight: 400;
        }
        .lsb-about-text p + p { margin-top: 14px; }
    </style>
    <div class="lsb-about-wrap">
        <div class="lsb-card-title">About</div>
        <div class="lsb-about-text">' . wp_kses_post( $description ) . '</div>
    </div>';

    return $output;
}
add_shortcode( 'business_about', 'lsb_business_about' );


/**
 * Shortcode: [business_reviews]
 */
function lsb_business_reviews() {
    $post_id    = get_the_ID();
    $widget_url = get_field( 'business_review_widget_url', $post_id );
    if ( ! $widget_url ) return '';

    $output = '
    <style>
        .lsb-reviews-wrap {
            background: #ffffff;
            border: 1px solid #E4EAF2;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 24px;
        }
        .lsb-reviews-embed { width: 100%; margin-top: 16px; }
        .lsb-reviews-embed iframe { min-width: 100%; width: 100%; border: none; }
    </style>
    <div class="lsb-reviews-wrap">
        <div class="lsb-card-title">Customer Reviews</div>
        <div class="lsb-reviews-embed">
            <script type="text/javascript" src="https://reputationhub.site/reputation/assets/review-widget.js"></script>
            <iframe class="lc_reviews_widget"
                src="' . esc_url( $widget_url ) . '"
                frameborder="0"
                scrolling="no"
                style="min-width:100%; width:100%;">
            </iframe>
        </div>
    </div>';

    return $output;
}
add_shortcode( 'business_reviews', 'lsb_business_reviews' );


/**
 * Shortcode: [business_contact_card]
 */
function lsb_business_contact_card() {
    $post_id = get_the_ID();
    $phone   = get_field( 'business_phone', $post_id );
    $website = get_field( 'business_website', $post_id );
    $email   = get_field( 'business_email', $post_id );
    if ( ! $phone && ! $website && ! $email ) return '';

    $output = '
    <style>
        .lsb-contact-card {
            background: #0D1B2A; border-radius: 16px; padding: 28px;
            margin-bottom: 20px; position: relative; overflow: hidden;
        }
        .lsb-contact-card::before {
            content: ""; position: absolute; top: -60px; right: -60px;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(0,201,167,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        .lsb-contact-card-title { font-family: "Syne", sans-serif; font-weight: 700; font-size: 1.05rem; color: #ffffff; margin-bottom: 4px; position: relative; z-index: 1; }
        .lsb-contact-card-sub { font-size: 0.82rem; color: rgba(255,255,255,0.4); margin-bottom: 20px; position: relative; z-index: 1; }
        .lsb-contact-btn-primary {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background: #00C9A7; color: #0D1B2A !important;
            font-family: "Syne", sans-serif; font-weight: 700; font-size: 0.95rem;
            padding: 14px 20px; border-radius: 10px; text-decoration: none;
            margin-bottom: 10px; transition: all 0.2s; position: relative; z-index: 1;
        }
        .lsb-contact-btn-primary:hover { background: #00A88C; transform: translateY(-1px); color: #0D1B2A !important; }
        .lsb-contact-btn-alt {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background: rgba(255,255,255,0.06); color: #ffffff !important;
            font-family: "Syne", sans-serif; font-weight: 600; font-size: 0.9rem;
            padding: 12px 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);
            text-decoration: none; margin-bottom: 10px; transition: all 0.2s; position: relative; z-index: 1;
        }
        .lsb-contact-btn-alt:hover { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: #ffffff !important; }
        .lsb-contact-divider { height: 1px; background: rgba(255,255,255,0.08); margin: 18px 0; position: relative; z-index: 1; }
        .lsb-contact-detail { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; position: relative; z-index: 1; }
        .lsb-contact-detail:last-child { margin-bottom: 0; }
        .lsb-contact-detail-icon { width: 32px; height: 32px; background: rgba(255,255,255,0.06); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0; }
        .lsb-contact-detail-label { font-size: 0.72rem; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 2px; }
        .lsb-contact-detail-value { font-size: 1rem; color: #ffffff; }
        .lsb-contact-detail-value a { color: #ffffff !important; text-decoration: none; }
        .lsb-contact-detail-value a:hover { color: #00C9A7 !important; }
    </style>
    <div class="lsb-contact-card">
        <div class="lsb-contact-card-title">Get in Touch</div>
        <div class="lsb-contact-card-sub">We typically respond within 1 hour</div>';

    if ( $phone ) {
        $clean = preg_replace( '/[^0-9+]/', '', $phone );
        $output .= '<a href="tel:' . esc_attr( $clean ) . '" class="lsb-contact-btn-primary">📞 ' . esc_html( $phone ) . '</a>';
    }
    if ( $website ) {
        $output .= '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener noreferrer" class="lsb-contact-btn-alt">🌐 Visit Website</a>';
    }
    if ( $email ) {
        $output .= '<a href="mailto:' . esc_attr( $email ) . '" class="lsb-contact-btn-alt">✉️ Send Message</a>';
    }

    $output .= '<div class="lsb-contact-divider"></div>';

    if ( $phone ) {
        $output .= '<div class="lsb-contact-detail">
            <div class="lsb-contact-detail-icon">📞</div>
            <div>
                <div class="lsb-contact-detail-label">Phone</div>
                <div class="lsb-contact-detail-value"><a href="tel:' . esc_attr( preg_replace('/[^0-9+]/','',$phone) ) . '">' . esc_html( $phone ) . '</a></div>
            </div>
        </div>';
    }
    if ( $email ) {
        $output .= '<div class="lsb-contact-detail">
            <div class="lsb-contact-detail-icon">✉️</div>
            <div>
                <div class="lsb-contact-detail-label">Email</div>
                <div class="lsb-contact-detail-value"><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></div>
            </div>
        </div>';
    }
    if ( $website ) {
        $output .= '<div class="lsb-contact-detail">
            <div class="lsb-contact-detail-icon">🌐</div>
            <div>
                <div class="lsb-contact-detail-label">Website</div>
                <div class="lsb-contact-detail-value"><a href="' . esc_url( $website ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( preg_replace('#^https?://#', '', $website) ) . '</a></div>
            </div>
        </div>';
    }

    $output .= '</div>';
    return $output;
}
add_shortcode( 'business_contact_card', 'lsb_business_contact_card' );


/**
 * Shortcode: [business_services]
 */
function lsb_business_services() {
    $post_id = get_the_ID();
    $terms   = get_the_terms( $post_id, 'service_type' );
    if ( ! $terms || is_wp_error( $terms ) ) return '';
    $output = '
    <style>
        .lsb-services-wrap { background: #ffffff; border: 1px solid #E4EAF2; border-radius: 16px; padding: 24px; margin-bottom: 20px; }
        .lsb-services-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
        @media (max-width: 1024px) {
            .lsb-services-grid { grid-template-columns: 1fr !important; gap: 8px !important; width: 100% !important; }
            .lsb-services-wrap { padding: 16px !important; width: 100% !important; box-sizing: border-box !important; }
            .lsb-service-item { width: 100% !important; box-sizing: border-box !important; padding: 14px 16px !important; }
            .lsb-service-name { font-size: 0.9rem !important; }
            .lsb-service-desc { font-size: 0.8rem !important; display: block !important; visibility: visible !important; }
        }
        .lsb-service-item {
            display: flex !important; align-items: flex-start; gap: 10px;
            background: #F5F7FA; border: 1px solid #E4EAF2; border-radius: 8px;
            padding: 10px 12px; text-decoration: none;
            transition: all 0.2s; box-sizing: border-box;
        }
        .lsb-service-item:hover { border-color: #00C9A7; background: rgba(0,201,167,0.04); }
        .lsb-service-check { width: 22px; height: 22px; min-width: 22px; background: rgba(0,201,167,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #00C9A7; flex-shrink: 0; margin-top: 2px; }
        .lsb-service-text { display: flex; flex-direction: column; gap: 4px; min-width: 0; flex: 1; }
        .lsb-service-name { font-size: 0.875rem; font-weight: 500; color: #0D1B2A; line-height: 1.3; }
        .lsb-service-desc { font-size: 0.775rem; color: #3D4F63; line-height: 1.6; font-weight: 400; display: block !important; visibility: visible !important; white-space: normal; word-wrap: break-word; }
    </style>
    <div class="lsb-services-wrap">
        <div class="lsb-card-title">Services Offered</div>
        <div class="lsb-services-grid">';
    foreach ( $terms as $term ) {
        $matching = get_posts( [
            'post_type'      => 'services',
            'title'          => $term->name,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        ] );
        $link        = ! empty( $matching ) ? get_permalink( $matching[0]->ID ) : get_term_link( $term );
        $description = ! empty( $term->description ) ? '<span class="lsb-service-desc">' . esc_html( $term->description ) . '</span>' : '';
        $output .= '<a href="' . esc_url( $link ) . '" class="lsb-service-item">
                        <span class="lsb-service-check">✓</span>
                        <span class="lsb-service-text">
                            <span class="lsb-service-name">' . esc_html( $term->name ) . '</span>
                            ' . $description . '
                        </span>
                    </a>';
    }
    $output .= '    </div>
    </div>';
    return $output;
}
add_shortcode( 'business_services', 'lsb_business_services' );


/**
 * Shortcode: [business_areas]
 */
function lsb_business_areas() {
    $post_id     = get_the_ID();
    $terms       = get_the_terms( $post_id, 'city_cat' );
    $description = get_field( 'business_short_description', $post_id );
    $map_embed   = get_field( 'business_map_embed', $post_id );
    if ( ! $terms || is_wp_error( $terms ) ) return '';
    $output = '
    <style>
        .lsb-areas-wrap { background: #ffffff; border: 1px solid #E4EAF2; border-radius: 16px; padding: 24px; margin-bottom: 20px; }
        .lsb-areas-desc { font-size: 0.875rem; color: #3D4F63; line-height: 1.7; font-weight: 400; margin-top: 12px; margin-bottom: 4px; }
        .lsb-areas-map { margin-top: 16px; border-radius: 10px; overflow: hidden; border: 1px solid #E4EAF2; }
        .lsb-areas-map iframe { width: 100%; height: 220px; display: block; border: none; }
        .lsb-areas-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 6px 8px !important;
            margin-top: 16px;
        }
        @media (max-width: 1024px) {
            .lsb-areas-grid { grid-template-columns: repeat(3, 1fr) !important; }
            .lsb-area-tag { font-size: 0.68rem !important; white-space: normal !important; word-break: break-word !important; overflow: visible !important; text-overflow: unset !important; }
        }
        @media (max-width: 600px) {
            .lsb-areas-grid { grid-template-columns: repeat(2, 1fr) !important; }
            .lsb-area-tag { font-size: 0.60rem !important; white-space: normal !important; word-break: break-word !important; overflow: visible !important; text-overflow: unset !important; padding: 5px 6px !important; }
        }
        .lsb-area-tag {
            display: flex !important; align-items: flex-start; gap: 5px;
            font-size: 0.8rem !important; color: #0D1B2A; text-decoration: none;
            padding: 6px 8px; background: #F5F7FA; border: 1px solid #E4EAF2; border-radius: 8px;
            transition: all 0.2s; white-space: normal !important; word-break: break-word !important;
            overflow: visible !important; text-overflow: unset !important; line-height: 1.4;
            box-sizing: border-box !important; width: 100% !important;
        }
        .lsb-area-tag:hover { border-color: #00C9A7; color: #00A88C; background: rgba(0,201,167,0.05); }
        .lsb-area-dot { width: 4px; height: 4px; min-width: 4px; background: #00C9A7; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
    </style>
    <div class="lsb-areas-wrap">
        <div class="lsb-card-title">Areas Served</div>';

    if ( $description ) {
        $output .= '<div class="lsb-areas-desc">' . wp_kses_post( $description ) . '</div>';
    }
    if ( $map_embed ) {
        $output .= '<div class="lsb-areas-map">' . $map_embed . '</div>';
    }

    $output .= '<div class="lsb-areas-grid">';
    foreach ( $terms as $term ) {
        $matching = get_posts( [
            'post_type'      => 'cities',
            'title'          => $term->name,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        ] );
        $link = ! empty( $matching ) ? get_permalink( $matching[0]->ID ) : get_term_link( $term );
        $output .= '<a href="' . esc_url( $link ) . '" class="lsb-area-tag">
                        <span class="lsb-area-dot"></span>
                        📍 ' . esc_html( $term->name ) . '
                    </a>';
    }
    $output .= '    </div>
    </div>';
    return $output;
}
add_shortcode( 'business_areas', 'lsb_business_areas' );


/**
 * Shortcode: [business_blog]
 * 4 posts, single column, dynamic title from industry_cat
 */
function lsb_business_blog() {
    $post_id = get_the_ID();
    $terms   = get_the_terms( $post_id, 'industry_cat' );
    if ( ! $terms || is_wp_error( $terms ) ) return '';
    $term_ids      = wp_list_pluck( $terms, 'term_id' );
    $industry_name = $terms[0]->name;
    $query = new WP_Query([
        'post_type'      => 'post',
        'posts_per_page' => 4,
        'post_status'    => 'publish',
        'tax_query'      => [
            [
                'taxonomy' => 'industry_cat',
                'field'    => 'term_id',
                'terms'    => $term_ids,
            ]
        ],
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);
    if ( ! $query->have_posts() ) return '';
    $output = '
    <style>
        .lsb-blog-wrap { background: #F5F7FA; border-radius: 16px; padding: 32px; margin-top: 24px; }
        .lsb-blog-grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 20px; }
        .lsb-blog-card { background: #ffffff; border: 1px solid #E4EAF2; border-radius: 14px; overflow: hidden; text-decoration: none; display: block; transition: all 0.25s; }
        .lsb-blog-card:hover { border-color: #00C9A7; transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.07); }
        .lsb-blog-img { width: 100%; height: 160px; object-fit: cover; display: block; background: #1B2F45; }
        .lsb-blog-img-placeholder { width: 100%; height: 160px; background: linear-gradient(135deg, #0D1B2A, #1B2F45); display: flex; align-items: center; justify-content: center; font-size: 2rem; }
        .lsb-blog-body { padding: 18px; }
        .lsb-blog-date { font-size: 0.75rem; color: #8A9BB0; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
        .lsb-blog-title { font-family: "Syne", sans-serif; font-size: 0.95rem; font-weight: 700; color: #0D1B2A; line-height: 1.3; margin-bottom: 10px; letter-spacing: -0.01em; }
        .lsb-blog-excerpt { font-size: 0.84rem; color: #4A5568; line-height: 1.6; font-weight: 400; margin-bottom: 14px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .lsb-blog-read-more { font-size: 0.82rem; font-weight: 600; color: #00C9A7; display: flex; align-items: center; gap: 4px; transition: gap 0.2s; }
        .lsb-blog-card:hover .lsb-blog-read-more { gap: 8px; }
    </style>
    <div class="lsb-blog-wrap">
        <div class="lsb-card-title">Articles About ' . esc_html( $industry_name ) . '</div>
        <div class="lsb-blog-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $thumb   = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
        $excerpt = get_the_excerpt();
        $date    = get_the_date( 'M j, Y' );
        $link    = get_permalink();
        $title   = get_the_title();
        $output .= '<a href="' . esc_url( $link ) . '" class="lsb-blog-card">';
        if ( $thumb ) {
            $output .= '<img src="' . esc_url( $thumb ) . '" alt="' . esc_attr( $title ) . '" class="lsb-blog-img">';
        } else {
            $output .= '<div class="lsb-blog-img-placeholder">📝</div>';
        }
        $output .= '<div class="lsb-blog-body">';
        $output .= '<div class="lsb-blog-date">' . esc_html( $date ) . '</div>';
        $output .= '<div class="lsb-blog-title">' . esc_html( $title ) . '</div>';
        if ( $excerpt ) {
            $output .= '<div class="lsb-blog-excerpt">' . esc_html( wp_trim_words( $excerpt, 20 ) ) . '</div>';
        }
        $output .= '<div class="lsb-blog-read-more">Read More →</div>';
        $output .= '</div>';
        $output .= '</a>';
    }
    wp_reset_postdata();
    $output .= '    </div>
    </div>';
    return $output;
}
add_shortcode( 'business_blog', 'lsb_business_blog' );


/**
 * Shortcode: [business_why_us]
 */
function lsb_business_why_us() {
    $post_id = get_the_ID();
    $rows    = get_field( 'why_us', $post_id );

    if ( empty( $rows ) ) return '';

    $output = '
    <style>
        .lsb-why-wrap { background: #ffffff; border: 1px solid #E4EAF2; border-radius: 16px; padding: 24px; margin-bottom: 20px; }
        .lsb-why-list { display: flex; flex-direction: column; gap: 16px; margin-top: 16px; }
        .lsb-why-item {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 16px; background: #F5F7FA; border: 1px solid #E4EAF2;
            border-radius: 10px; transition: all 0.2s;
        }
        .lsb-why-item:hover { border-color: #00C9A7; background: rgba(0,201,167,0.03); }
        .lsb-why-icon { width: 36px; height: 36px; min-width: 36px; background: rgba(0,201,167,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #00C9A7; font-size: 1rem; flex-shrink: 0; margin-top: 2px; }
        .lsb-why-content { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .lsb-why-reason { font-family: "Syne", sans-serif; font-size: 0.9rem; font-weight: 700; color: #0D1B2A; line-height: 1.3; letter-spacing: -0.01em; }
        .lsb-why-desc { font-size: 0.82rem; color: #3D4F63; line-height: 1.65; font-weight: 400; }
        .lsb-why-desc p { margin: 0; }
        .lsb-why-desc ul { margin: 6px 0 0 16px; padding: 0; }
        .lsb-why-desc li { margin-bottom: 4px; }
    </style>
    <div class="lsb-why-wrap">
        <div class="lsb-card-title">Why Choose Us</div>
        <div class="lsb-why-list">';

    foreach ( $rows as $row ) {
        $reason = ! empty( $row['why_us_reason'] ) ? esc_html( $row['why_us_reason'] ) : '';
        $desc   = ! empty( $row['why_us_description'] ) ? wp_kses_post( $row['why_us_description'] ) : '';
        if ( ! $reason && ! $desc ) continue;
        $output .= '<div class="lsb-why-item">
            <div class="lsb-why-icon">✓</div>
            <div class="lsb-why-content">';
        if ( $reason ) $output .= '<div class="lsb-why-reason">' . $reason . '</div>';
        if ( $desc )   $output .= '<div class="lsb-why-desc">' . $desc . '</div>';
        $output .= '</div></div>';
    }

    $output .= '</div></div>';
    return $output;
}
add_shortcode( 'business_why_us', 'lsb_business_why_us' );


/**
 * Shortcode: [business_cta]
 */
function lsb_business_cta() {
    $post_id     = get_the_ID();
    $cta_text    = get_field( 'primary_cta_text', $post_id );
    $phone       = get_field( 'business_phone', $post_id );
    $cta_link    = get_field( 'primary_cta_link', $post_id );

    if ( ! $cta_text && ! $phone && ! $cta_link ) return '';

    $clean_phone = $phone ? preg_replace( '/[^0-9+]/', '', $phone ) : '';
    $unique_id   = 'lsb-bcta-' . get_the_ID();

    $output = '
    <style>
        #' . $unique_id . ' {
            background: #00C9A7; border-radius: 16px; padding: 40px 28px;
            margin-bottom: 20px; text-align: center; position: relative; overflow: hidden;
        }
        #' . $unique_id . '::before { content: ""; position: absolute; top: -40px; right: -40px; width: 160px; height: 160px; background: rgba(255,255,255,0.08); border-radius: 50%; pointer-events: none; }
        #' . $unique_id . '::after  { content: ""; position: absolute; bottom: -30px; left: -30px; width: 120px; height: 120px; background: rgba(255,255,255,0.06); border-radius: 50%; pointer-events: none; }
        #' . $unique_id . ' .bcta-text { font-family: "Syne", sans-serif; font-size: 1.5rem; font-weight: 800; color: #0D1B2A; line-height: 1.25; letter-spacing: -0.02em; margin-bottom: 28px; position: relative; z-index: 1; }
        #' . $unique_id . ' .bcta-buttons { display: flex; flex-direction: row; gap: 10px; justify-content: center; position: relative; z-index: 1; flex-wrap: wrap; }
        #' . $unique_id . ' .bcta-btn { min-width: 160px; box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-family: "Syne", sans-serif; font-weight: 700; font-size: 0.9rem; padding: 13px 24px; border-radius: 8px; text-decoration: none; transition: all 0.2s; }
        #' . $unique_id . ' .bcta-btn-phone { background: #0D1B2A; color: #ffffff !important; }
        #' . $unique_id . ' .bcta-btn-phone:hover { background: #1B2F45; transform: translateY(-2px); }
        #' . $unique_id . ' .bcta-btn-contact { background: rgba(255,255,255,0.25); color: #0D1B2A !important; border: 1px solid rgba(13,27,42,0.15); }
        #' . $unique_id . ' .bcta-btn-contact:hover { background: rgba(255,255,255,0.4); transform: translateY(-2px); }
        @media (max-width: 600px) {
            #' . $unique_id . ' .bcta-text { font-size: 1.2rem; }
            #' . $unique_id . ' .bcta-buttons { flex-direction: column; align-items: stretch; }
            #' . $unique_id . ' .bcta-btn { width: 100%; }
        }
    </style>
    <div id="' . $unique_id . '">
        <div class="bcta-text">' . esc_html( $cta_text ) . '</div>
        <div class="bcta-buttons">';

    if ( $phone ) {
        $output .= '<a href="tel:' . esc_attr( $clean_phone ) . '" class="bcta-btn bcta-btn-phone">📞 ' . esc_html( $phone ) . '</a>';
    }
    if ( $cta_link ) {
        $output .= '<a href="' . esc_url( $cta_link ) . '" class="bcta-btn bcta-btn-contact">✉️ Contact Us</a>';
    }

    $output .= '</div></div>';
    return $output;
}
add_shortcode( 'business_cta', 'lsb_business_cta' );


/**
 * Shortcode: [business_faqs]
 */
function lsb_business_faqs() {
    $post_id = get_the_ID();
    $rows    = get_field( 'business_faqs', $post_id );

    if ( empty( $rows ) ) return '';

    $unique_id = 'lsb-faq-' . get_the_ID();

    $output = '
    <style>
        #' . $unique_id . ' { background: #ffffff; border: 1px solid #E4EAF2; border-radius: 16px; padding: 24px; margin-bottom: 20px; }
        #' . $unique_id . ' .faq-title { font-family: "Syne", sans-serif; font-weight: 700; font-size: 0.95rem; color: #0D1B2A; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #E4EAF2; display: flex; align-items: center; gap: 8px; }
        #' . $unique_id . ' .faq-list { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
        #' . $unique_id . ' .faq-item { border: 1px solid #E4EAF2; border-radius: 10px; overflow: hidden; }
        #' . $unique_id . ' .faq-question { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 16px; background: #F5F7FA; cursor: pointer; font-family: "Syne", sans-serif; font-weight: 600; font-size: 0.88rem; color: #0D1B2A; line-height: 1.4; transition: background 0.2s; user-select: none; }
        #' . $unique_id . ' .faq-question:hover { background: #EDF0F5; }
        #' . $unique_id . ' .faq-item.open .faq-question { background: #EDF0F5; color: #00A88C; }
        #' . $unique_id . ' .faq-toggle { width: 24px; height: 24px; min-width: 24px; background: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #00C9A7; font-weight: 400; transition: transform 0.25s, background 0.2s; flex-shrink: 0; }
        #' . $unique_id . ' .faq-item.open .faq-toggle { transform: rotate(45deg); background: rgba(0,201,167,0.1); }
        #' . $unique_id . ' .faq-answer { display: none; padding: 14px 16px; background: #FAFBFD; font-size: 0.84rem; color: #3D4F63; line-height: 1.7; font-weight: 400; border-top: 1px solid #E4EAF2; }
        #' . $unique_id . ' .faq-answer p { margin: 0 0 8px; }
        #' . $unique_id . ' .faq-answer p:last-child { margin-bottom: 0; }
        #' . $unique_id . ' .faq-answer ul { margin: 0 0 8px 18px; padding: 0; }
        #' . $unique_id . ' .faq-answer li { margin-bottom: 4px; }
        #' . $unique_id . ' .faq-item.open .faq-answer { display: block; }
    </style>
    <div id="' . $unique_id . '">
        <div class="faq-title">❓ Frequently Asked Questions</div>
        <div class="faq-list">';

    foreach ( $rows as $i => $row ) {
        $question = ! empty( $row['business_faq_q'] ) ? esc_html( $row['business_faq_q'] ) : '';
        $answer   = ! empty( $row['business_faq_a'] ) ? wp_kses_post( $row['business_faq_a'] ) : '';
        if ( ! $question && ! $answer ) continue;
        $item_id = $unique_id . '-item-' . $i;
        $output .= '
        <div class="faq-item" id="' . $item_id . '">
            <div class="faq-question" onclick="lsbFaqToggle(\'' . $item_id . '\')">
                <span>' . $question . '</span>
                <span class="faq-toggle">+</span>
            </div>
            <div class="faq-answer">' . $answer . '</div>
        </div>';
    }

    $output .= '</div></div>
    <script>
    function lsbFaqToggle(id) {
        var item = document.getElementById(id);
        if (!item) return;
        item.classList.toggle("open");
    }
    </script>';

    return $output;
}
add_shortcode( 'business_faqs', 'lsb_business_faqs' );


/**
 * Shortcode: [business_schema]
 * Outputs LocalBusiness + AggregateRating + FAQPage JSON-LD
 */
function lsb_business_schema() {
    $post_id       = get_the_ID();
    $name          = get_the_title( $post_id );
    $url           = get_permalink( $post_id );
    $phone         = get_field( 'business_phone', $post_id );
    $website       = get_field( 'business_website', $post_id );
    $address       = get_field( 'business_address', $post_id );
    $rating        = get_field( 'business_rating', $post_id );
    $review_count  = get_field( 'business_review_count', $post_id );
    $description   = get_field( 'business_description', $post_id );
    $hours         = get_field( 'business_hours', $post_id );
    $faqs          = get_field( 'business_faqs', $post_id );
    $image         = get_the_post_thumbnail_url( $post_id, 'large' );

    $industry_terms = get_the_terms( $post_id, 'industry_cat' );
    $industry_slug  = ( $industry_terms && ! is_wp_error( $industry_terms ) ) ? $industry_terms[0]->slug : '';

    $type_map = [
        'hvac'             => 'HVACBusiness',
        'plumbing'         => 'Plumber',
        'roofing'          => 'RoofingContractor',
        'auto-body'        => 'AutoBodyShop',
        'locksmith'        => 'Locksmith',
        'restoration'      => 'HomeAndConstructionBusiness',
        'catering'         => 'FoodEstablishment',
        'realtors'         => 'RealEstateAgent',
        'public-adjusting' => 'ProfessionalService',
        'scrap-metals'     => 'LocalBusiness',
        'dental-broker'    => 'ProfessionalService',
    ];
    $schema_type = $type_map[ $industry_slug ] ?? 'LocalBusiness';

    $schema   = [ '@context' => 'https://schema.org', '@graph' => [] ];
    $business = [ '@type' => $schema_type, '@id' => $url . '#localbusiness', 'name' => $name, 'url' => $url ];

    if ( $description ) $business['description'] = wp_strip_all_tags( $description );
    if ( $phone )       $business['telephone']   = $phone;
    if ( $website )     $business['sameAs']      = $website;
    if ( $image )       $business['image']       = $image;

    if ( $address ) {
        $business['address'] = [ '@type' => 'PostalAddress', 'streetAddress' => $address ];
        $city_terms = get_the_terms( $post_id, 'city_cat' );
        if ( $city_terms && ! is_wp_error( $city_terms ) ) {
            $business['address']['addressLocality'] = $city_terms[0]->name;
        }
        $business['address']['addressRegion']  = 'CA';
        $business['address']['addressCountry'] = 'US';
    }

    if ( $rating && $review_count ) {
        $business['aggregateRating'] = [
            '@type' => 'AggregateRating', 'ratingValue' => (string) $rating,
            'reviewCount' => (string) $review_count, 'bestRating' => '5', 'worstRating' => '1',
        ];
    }

    $day_map = [ 'monday' => 'Mo', 'tuesday' => 'Tu', 'wednesday' => 'We', 'thursday' => 'Th', 'friday' => 'Fr', 'saturday' => 'Sa', 'sunday' => 'Su' ];
    if ( $hours ) {
        $opening_hours = [];
        foreach ( $hours as $row ) {
            if ( ! empty( $row['closed'] ) ) continue;
            $day_label  = strtolower( trim( $row['day_label'] ) );
            $day_abbr   = $day_map[ $day_label ] ?? $day_label;
            $open_time  = $row['open_time'] ?? '';
            $close_time = $row['close_time'] ?? '';
            if ( $day_abbr && $open_time && $close_time ) {
                $opening_hours[] = [ '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'https://schema.org/' . ucfirst( $day_label ), 'opens' => $open_time, 'closes' => $close_time ];
            }
        }
        if ( ! empty( $opening_hours ) ) $business['openingHoursSpecification'] = $opening_hours;
    }

    $schema['@graph'][] = $business;

    if ( $faqs ) {
        $faq_entities = [];
        foreach ( $faqs as $row ) {
            $q = ! empty( $row['business_faq_q'] ) ? wp_strip_all_tags( $row['business_faq_q'] ) : '';
            $a = ! empty( $row['business_faq_a'] ) ? wp_strip_all_tags( $row['business_faq_a'] ) : '';
            if ( $q && $a ) {
                $faq_entities[] = [ '@type' => 'Question', 'name' => $q, 'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $a ] ];
            }
        }
        if ( ! empty( $faq_entities ) ) {
            $schema['@graph'][] = [ '@type' => 'FAQPage', '@id' => $url . '#faqpage', 'mainEntity' => $faq_entities ];
        }
    }

    return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}
add_shortcode( 'business_schema', 'lsb_business_schema' );