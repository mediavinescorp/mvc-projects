<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ════════════════════════════════════════════
   DATA HELPERS
   ════════════════════════════════════════════ */

function mvtd_get_tools() {
    $tools = get_option( 'mvtd_tools', [] );
    if ( ! is_array( $tools ) ) return [];
    // Sort by order field
    usort( $tools, fn( $a, $b ) => ( $a['order'] ?? 99 ) <=> ( $b['order'] ?? 99 ) );
    return $tools;
}

function mvtd_save_tools( array $tools ) {
    update_option( 'mvtd_tools', $tools );
}

function mvtd_get_tool_by_id( $id ) {
    foreach ( mvtd_get_tools() as $tool ) {
        if ( ( $tool['id'] ?? '' ) === $id ) return $tool;
    }
    return null;
}

function mvtd_generate_id( $name ) {
    return sanitize_title( $name ) ?: 'tool-' . time();
}

/* ════════════════════════════════════════════
   CATEGORIES
   ════════════════════════════════════════════ */

function mvtd_get_all_categories() {
    return [
        'reviews'   => 'Reviews',
        'seo'       => 'SEO',
        'marketing' => 'Marketing',
        'social'    => 'Social Media',
        'website'   => 'Website',
        'email'     => 'Email',
        'analytics' => 'Analytics',
        'other'     => 'Other',
    ];
}

function mvtd_get_active_categories() {
    $all   = mvtd_get_all_categories();
    $tools = mvtd_get_tools();
    $used  = [];
    foreach ( $tools as $t ) {
        $cat = $t['category'] ?? 'other';
        if ( ! in_array( $cat, $used ) ) $used[] = $cat;
    }
    // Build ordered list: all first, then only used categories
    $result = [ 'all' => 'All Tools' ];
    foreach ( $all as $key => $label ) {
        if ( in_array( $key, $used ) ) $result[ $key ] = $label;
    }
    return $result;
}

/* ════════════════════════════════════════════
   ICONS
   Grouped by theme for the admin picker UI
   ════════════════════════════════════════════ */

function mvtd_get_icon_groups() {
    return [
        'Reviews & Trust' => [
            'star'        => 'Star / Reviews',
            'thumbs-up'   => 'Thumbs Up',
            'award'       => 'Award / Badge',
            'shield'      => 'Trust / Shield',
            'heart'       => 'Favorite',
        ],
        'SEO & Search' => [
            'search'      => 'Search / SEO',
            'trending-up' => 'Trending Up',
            'bar-chart'   => 'Analytics / Chart',
            'target'      => 'Target / Goal',
            'globe'       => 'Website / Domain',
        ],
        'Local Business' => [
            'map-pin'     => 'Location / Map Pin',
            'map'         => 'Map',
            'building'    => 'Business / Building',
            'phone'       => 'Phone',
            'clock'       => 'Hours / Schedule',
        ],
        'Marketing' => [
            'mail'        => 'Email',
            'share'       => 'Share / Social',
            'megaphone'   => 'Marketing / Promote',
            'users'       => 'Audience / Customers',
            'message'     => 'Messaging / Chat',
        ],
        'Tools & Tech' => [
            'qr'          => 'QR Code',
            'link'        => 'Link / URL',
            'settings'    => 'Settings / Config',
            'tool'        => 'Tool / Wrench',
            'zap'         => 'Fast / Automation',
            'layers'      => 'Layers / Stack',
        ],
    ];
}

function mvtd_get_icon_svg( $icon ) {
    $icons = [
        'star'        => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'thumbs-up'   => '<path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>',
        'award'       => '<circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>',
        'shield'      => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'heart'       => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
        'search'      => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'trending-up' => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
        'bar-chart'   => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'target'      => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
        'globe'       => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'map-pin'     => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'map'         => '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>',
        'building'    => '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M8 10h.01M16 10h.01M8 14h.01M16 14h.01M12 14h.01"/>',
        'phone'       => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'clock'       => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'mail'        => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/>',
        'share'       => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>',
        'megaphone'   => '<path d="M3 11l19-9-9 19-2-8-8-2z"/>',
        'users'       => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'message'     => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'qr'          => '<rect x="3" y="3" width="5" height="5"/><rect x="16" y="3" width="5" height="5"/><rect x="3" y="16" width="5" height="5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/>',
        'link'        => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'tool'        => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'zap'         => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'layers'      => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
    ];
    $path = $icons[ $icon ] ?? $icons['tool'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

/* flat list for JS */
function mvtd_get_icons_flat() {
    $flat = [];
    foreach ( mvtd_get_icon_groups() as $group => $items ) {
        foreach ( $items as $key => $label ) {
            $flat[ $key ] = [ 'label' => $label, 'group' => $group ];
        }
    }
    return $flat;
}
