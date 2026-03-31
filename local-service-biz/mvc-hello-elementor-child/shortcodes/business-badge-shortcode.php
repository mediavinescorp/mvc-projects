<?php
/**
 * Shortcode: [business_verified_badge]
 * Shows the verification badge on the business landing page (large size)
 * Place in the hero section near the business name in Elementor
 */
if ( ! function_exists( 'lsb_business_verified_badge_shortcode' ) ) {
    function lsb_business_verified_badge_shortcode() {
        $pid = get_the_ID();

        // DEBUG — remove after confirmed working
        $debug  = '<!-- badge debug: ';
        $debug .= 'post_id=' . $pid . ' | ';
        $debug .= 'lsb_get_business_badge=' . ( function_exists( 'lsb_get_business_badge' ) ? 'EXISTS' : 'MISSING' ) . ' | ';
        $debug .= 'lsb_print_badge_styles=' . ( function_exists( 'lsb_print_badge_styles' ) ? 'EXISTS' : 'MISSING' ) . ' | ';
        $debug .= 'lsb_get_badge_tier=' . ( function_exists( 'lsb_get_badge_tier' ) ? lsb_get_badge_tier( $pid ) : 'MISSING' ) . ' | ';
        $debug .= 'plan_tier=' . var_export( get_field( 'plan_tier', $pid ), true ) . ' | ';
        $debug .= 'business_verified=' . var_export( get_field( 'business_verified', $pid ), true );
        $debug .= ' -->';

        if ( ! function_exists( 'lsb_get_business_badge' ) ) return $debug . '<!-- EARLY EXIT: lsb_get_business_badge missing -->';
        if ( ! function_exists( 'lsb_print_badge_styles' ) ) return $debug . '<!-- EARLY EXIT: lsb_print_badge_styles missing -->';

        $badge = lsb_get_business_badge( $pid, 'large' );

        if ( ! $badge ) return $debug . '<!-- EARLY EXIT: badge empty for this post -->';

        // Add lsb-on-dark class for navy hero backgrounds
        $badge = str_replace( 'class="lsb-badge lsb-badge--', 'class="lsb-badge lsb-on-dark lsb-badge--', $badge );

        $out  = $debug;
        $out .= lsb_print_badge_styles();
        $out .= '<div class="lsb-landing-badge-wrap" style="margin-bottom:12px;">' . $badge . '</div>';

        return $out;
    }
    add_shortcode( 'business_verified_badge', 'lsb_business_verified_badge_shortcode' );
}
