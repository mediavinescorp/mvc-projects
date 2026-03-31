<?php
/**
 * Plugin Name:       MV BioLink Generator
 * Plugin URI:        https://www.mediavines.com
 * Description:       Let local service businesses generate a branded bio link page with contact info, social links, custom links, layout choices, and a vCard QR code for saving contacts directly to phone.
 * Version:           1.0.0
 * Author:            Media Vines Corp
 * Author URI:        https://www.mediavines.com
 * License:           GPL-2.0+
 * Text Domain:       mv-biolink
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MVBL_VERSION', '1.0.0' );
define( 'MVBL_PATH',    plugin_dir_path( __FILE__ ) );
define( 'MVBL_URL',     plugin_dir_url( __FILE__ ) );
define( 'MVBL_QR_DIR',  'mv-biolink-qr' );

require_once MVBL_PATH . 'includes/cpt.php';
require_once MVBL_PATH . 'includes/vcard.php';
require_once MVBL_PATH . 'includes/qr-generator.php';
require_once MVBL_PATH . 'includes/form-handler.php';
require_once MVBL_PATH . 'includes/shortcode.php';

/* ── Enqueue frontend assets ── */
add_action( 'wp_enqueue_scripts', 'mvbl_enqueue_assets' );
function mvbl_enqueue_assets() {
    wp_enqueue_style(  'mvbl-style',  MVBL_URL . 'assets/css/style.css',  [], MVBL_VERSION );
    wp_enqueue_script( 'mvbl-script', MVBL_URL . 'assets/js/form.js', [ 'jquery' ], MVBL_VERSION, true );
    wp_localize_script( 'mvbl-script', 'MVBL', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'mvbl_submit' ),
    ]);
}

/* ── Load our template for the biolink CPT ── */
add_filter( 'single_template', 'mvbl_load_template' );
function mvbl_load_template( $template ) {
    if ( get_post_type() === 'biolink_page' ) {
        $custom = MVBL_PATH . 'templates/single-biolink.php';
        if ( file_exists( $custom ) ) return $custom;
    }
    return $template;
}

/* ── Activation ── */
register_activation_hook( __FILE__, 'mvbl_activate' );
function mvbl_activate() {
    mvbl_register_cpt();
    flush_rewrite_rules();
    $upload = wp_upload_dir();
    $dir    = $upload['basedir'] . '/' . MVBL_QR_DIR;
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
        file_put_contents( $dir . '/index.php', '<?php // Silence is golden.' );
    }
}

register_deactivation_hook( __FILE__, function() { flush_rewrite_rules(); } );
