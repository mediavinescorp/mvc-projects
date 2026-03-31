<?php
/**
 * Plugin Name:       MV Tools Directory
 * Plugin URI:        https://www.mediavines.com
 * Description:       A dynamic tools directory for local service businesses. Add, edit and manage tools entirely from the WordPress admin — no file editing required.
 * Version:           2.0.0
 * Author:            Media Vines Corp
 * Author URI:        https://www.mediavines.com
 * License:           GPL-2.0+
 * Text Domain:       mv-tools-directory
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MVTD_VERSION', '2.0.0' );
define( 'MVTD_PATH',    plugin_dir_path( __FILE__ ) );
define( 'MVTD_URL',     plugin_dir_url( __FILE__ ) );

require_once MVTD_PATH . 'includes/data.php';
require_once MVTD_PATH . 'includes/admin.php';
require_once MVTD_PATH . 'includes/shortcode.php';

/* ── Enqueue frontend assets ── */
add_action( 'wp_enqueue_scripts', 'mvtd_enqueue_assets' );
function mvtd_enqueue_assets() {
    wp_enqueue_style(  'mvtd-style',  MVTD_URL . 'assets/css/style.css',  [], MVTD_VERSION );
    wp_enqueue_script( 'mvtd-script', MVTD_URL . 'assets/js/tools.js', [], MVTD_VERSION, true );
}

/* ── Enqueue admin assets ── */
add_action( 'admin_enqueue_scripts', 'mvtd_enqueue_admin_assets' );
function mvtd_enqueue_admin_assets( $hook ) {
    if ( strpos( $hook, 'mv-tools-directory' ) === false ) return;
    wp_enqueue_style(  'mvtd-admin-style',  MVTD_URL . 'assets/css/admin.css',  [], MVTD_VERSION );
    wp_enqueue_script( 'mvtd-admin-script', MVTD_URL . 'assets/js/admin.js', [ 'jquery' ], MVTD_VERSION, true );
    wp_localize_script( 'mvtd-admin-script', 'MVTD', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'mvtd_admin' ),
    ]);
}

/* ── On activation: seed with the Review Generator tool ── */
register_activation_hook( __FILE__, 'mvtd_activate' );
function mvtd_activate() {
    if ( get_option( 'mvtd_tools' ) !== false ) return; // don't overwrite
    $seed = [
        [
            'id'          => 'review-generator',
            'name'        => 'Google Review Page Generator',
            'tagline'     => 'Generate a branded, scannable review page for any local business in seconds.',
            'description' => 'Turn happy customers into 5-star Google reviews. Enter your business name, Google review link, and logo — and we instantly generate a beautiful page with a QR code, mobile step-by-step instructions, and share buttons. Each business gets their own permanent, branded URL.',
            'url'         => 'https://localservicebiz.com/reviews-generator/',
            'icon'        => 'star',
            'category'    => 'reviews',
            'badge'       => 'Free',
            'badge_color' => 'green',
            'status'      => 'active',
            'features'    => [
                'Generates a unique QR code for every business',
                'Mobile-optimized with 5-step scan instructions',
                'Upload your business logo',
                'Share page via email or text instantly',
            ],
            'order'       => 1,
        ],
    ];
    update_option( 'mvtd_tools', $seed );
}
