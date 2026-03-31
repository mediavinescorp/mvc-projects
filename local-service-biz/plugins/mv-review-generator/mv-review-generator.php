<?php
/**
 * Plugin Name:       MV Review Page Generator
 * Plugin URI:        https://mediavines.com
 * Description:       Generate branded Google review pages for clients. Each submission creates a unique CPT with QR code saved to your media library.
 * Version:           1.0.0
 * Author:            Media Vines Corp
 * Author URI:        https://mediavines.com
 * License:           GPL-2.0+
 * Text Domain:       mv-review-generator
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MVRG_VERSION',  '1.0.0' );
define( 'MVRG_PATH',     plugin_dir_path( __FILE__ ) );
define( 'MVRG_URL',      plugin_dir_url( __FILE__ ) );
define( 'MVRG_QR_DIR',  'mv-qrcodes' );

require_once MVRG_PATH . 'includes/cpt.php';
require_once MVRG_PATH . 'includes/qr-generator.php';
require_once MVRG_PATH . 'includes/form-handler.php';
require_once MVRG_PATH . 'includes/shortcode.php';

/* ── Enqueue assets ── */
add_action( 'wp_enqueue_scripts', 'mvrg_enqueue_assets' );
function mvrg_enqueue_assets() {
    wp_enqueue_style(
        'mvrg-style',
        MVRG_URL . 'assets/css/style.css',
        [],
        MVRG_VERSION
    );
    wp_enqueue_script(
        'mvrg-form',
        MVRG_URL . 'assets/js/form.js',
        [ 'jquery' ],
        MVRG_VERSION,
        true
    );
    wp_localize_script( 'mvrg-form', 'MVRG', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'mvrg_submit' ),
    ]);
}

/* ── Use our template for the CPT single view ── */
add_filter( 'single_template', 'mvrg_load_review_template' );
function mvrg_load_review_template( $template ) {
    if ( get_post_type() === 'review_page' ) {
        $custom = MVRG_PATH . 'templates/single-review-page.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
}

/* ── Flush rewrite rules on activation ── */
register_activation_hook( __FILE__, 'mvrg_activate' );
function mvrg_activate() {
    mvrg_register_cpt();
    flush_rewrite_rules();
    // Create QR upload directory
    $upload = wp_upload_dir();
    $qr_dir = $upload['basedir'] . '/' . MVRG_QR_DIR;
    if ( ! file_exists( $qr_dir ) ) {
        wp_mkdir_p( $qr_dir );
    }
}

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});
