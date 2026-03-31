<?php
/**
 * LSB Business Badge Helper
 * File: /shortcodes/lsb-badges.php
 *
 * Provides a single shared function + CSS for business verification badges.
 * Used by: local-businesses-page.php (cards) and shortcodes-business.php (landing page)
 *
 * Badge tiers (uses existing ACF fields):
 *   🏅 Licensed & Verified — plan_tier = premium + business_verified = true
 *   ✅ Verified             — plan_tier = basic + business_verified = true
 *   (no badge)             — free tier or business_verified = false
 *
 * ACF fields required:
 *   plan_tier          — select: free / basic / premium
 *   business_verified  — true/false
 *
 * Usage:
 *   $badge = lsb_get_business_badge( $post_id );           // returns HTML or ''
 *   echo lsb_get_business_badge( $post_id, 'large' );      // 'small' (default) or 'large'
 *   lsb_print_badge_styles();                              // call once per page to print CSS
 */

// FILE LOAD CONFIRMATION — remove after badges are working
add_action( 'wp_footer', function() {
    echo '<!-- lsb-badges.php loaded OK | lsb_get_badge_tier exists: ' . ( function_exists( 'lsb_get_badge_tier' ) ? 'YES' : 'NO' ) . ' -->';
} );

if ( ! function_exists( 'lsb_get_badge_tier' ) ) :

/* ------------------------------------------------------------------
 * 1. DETERMINE BADGE TIER FOR A GIVEN POST
 * Returns: 'licensed' | 'verified' | ''
 * ------------------------------------------------------------------ */
function lsb_get_badge_tier( $post_id ) {
    $plan_tier  = get_field( 'plan_tier', $post_id );
    $verified   = get_field( 'business_verified', $post_id );

    // Must be verified to get any badge
    if ( ! $verified ) return '';

    // Licensed & Verified — premium + verified
    if ( $plan_tier === 'premium' ) {
        return 'licensed';
    }

    // Verified — basic + verified
    if ( $plan_tier === 'basic' ) {
        return 'verified';
    }

    return '';
}
endif;


if ( ! function_exists( 'lsb_get_business_badge' ) ) :

/* ------------------------------------------------------------------
 * 2. RETURN BADGE HTML
 * $size: 'small' (for cards) | 'large' (for landing pages)
 * ------------------------------------------------------------------ */
function lsb_get_business_badge( $post_id, $size = 'small' ) {
    $tier = lsb_get_badge_tier( $post_id );
    if ( ! $tier ) return '';

    $size_class = ( $size === 'large' ) ? ' lsb-badge--large' : '';

    if ( $tier === 'licensed' ) {
        return '<span class="lsb-badge lsb-badge--licensed' . $size_class . '" title="This business has been verified and their license has been confirmed by LocalServiceBiz">'
            . '<span class="lsb-badge__icon">🏅</span>'
            . '<span class="lsb-badge__label">Licensed &amp; Verified</span>'
            . '</span>';
    }

    if ( $tier === 'verified' ) {
        return '<span class="lsb-badge lsb-badge--verified' . $size_class . '" title="This business has been verified by LocalServiceBiz">'
            . '<span class="lsb-badge__icon">✅</span>'
            . '<span class="lsb-badge__label">Verified</span>'
            . '</span>';
    }

    return '';
}
endif;


if ( ! function_exists( 'lsb_print_badge_styles' ) ) :
/* ------------------------------------------------------------------
 * 3. BADGE CSS — returns CSS string, prints once per page load
 * ------------------------------------------------------------------ */
function lsb_print_badge_styles() {
    static $printed = false;
    if ( $printed ) return '';
    $printed = true;

    return '
<style>
/* ================================================================
   LSB BUSINESS BADGES
   ================================================================ */
.lsb-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border-radius: 100px;
    padding: 3px 10px;
    font-family: "DM Sans", sans-serif;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    line-height: 1;
    white-space: nowrap;
    cursor: default;
    text-decoration: none;
    transition: opacity 0.2s;
    vertical-align: middle;
}
.lsb-badge:hover { opacity: 0.85; }
.lsb-badge__icon { font-size: 0.75rem; line-height: 1; flex-shrink: 0; }
.lsb-badge__label { line-height: 1; }
.lsb-badge--verified {
    background: rgba(0,201,167,0.12);
    border: 1px solid rgba(0,201,167,0.3);
    color: #00A88C;
}
.lsb-badge--licensed {
    background: rgba(244,197,66,0.12);
    border: 1px solid rgba(244,197,66,0.35);
    color: #C49A10;
}
.lsb-badge--large { padding: 6px 14px; font-size: 0.82rem; gap: 7px; }
.lsb-badge--large .lsb-badge__icon { font-size: 0.9rem; }
.lsb-badge--verified.lsb-on-dark {
    background: rgba(0,201,167,0.15);
    border-color: rgba(0,201,167,0.4);
    color: #00C9A7;
}
.lsb-badge--licensed.lsb-on-dark {
    background: rgba(244,197,66,0.15);
    border-color: rgba(244,197,66,0.4);
    color: #F4C542;
}
</style>
';
}
endif;
