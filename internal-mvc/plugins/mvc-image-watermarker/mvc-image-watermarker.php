<?php
/**
 * Plugin Name: MVC Image Watermarker
 * Plugin URI:  https://mediavines.com
 * Description: Batch watermark images with logo and text overlays. Outputs JPEG with structured filenames: Industry - Keyword - Location - Client Name.
 * Version:     1.1.0
 * Author:      Media Vines Corp
 * Author URI:  https://mediavines.com
 * License:     GPL2
 * Text Domain: mvc-image-watermarker
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MVC_IW_VERSION', '1.1.0' );
define( 'MVC_IW_URL', plugin_dir_url( __FILE__ ) );
define( 'MVC_IW_PATH', plugin_dir_path( __FILE__ ) );

// Enqueue assets only when shortcode is present
function mvc_iw_enqueue_assets() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'image_watermarker' ) ) {
        wp_enqueue_style(
            'mvc-image-watermarker',
            MVC_IW_URL . 'assets/watermarker.css',
            array(),
            MVC_IW_VERSION
        );
    }
}
add_action( 'wp_enqueue_scripts', 'mvc_iw_enqueue_assets' );

// Register shortcode
function mvc_iw_shortcode( $atts ) {
    ob_start();
    include MVC_IW_PATH . 'templates/watermarker.php';
    return ob_get_clean();
}
add_shortcode( 'image_watermarker', 'mvc_iw_shortcode' );

// Add settings link on plugin page
function mvc_iw_plugin_links( $links ) {
    $links[] = '<a href="' . admin_url( 'options-general.php?page=mvc-image-watermarker' ) . '">Settings</a>';
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mvc_iw_plugin_links' );

// Settings page
function mvc_iw_register_settings() {
    register_setting( 'mvc_iw_settings', 'mvc_iw_defaults', array(
        'sanitize_callback' => 'mvc_iw_sanitize_defaults',
        'default' => array(
            'out_w'        => 1200,
            'out_h'        => 800,
            'quality'      => 92,
            'logo_size'    => 12,
            'logo_margin'  => 20,
            'logo_opacity' => 85,
            'logo_pos'     => 'br',
            'txt1'         => '',
            'fs1'          => 24,
            'fc1'          => '#ffffff',
            'to1'          => 85,
            'ff1'          => 'Arial',
            'fw1'          => 'bold',
            'tp1'          => 'br',
            'ts1'          => 'dark',
            'txt2'         => '',
            'fs2'          => 18,
            'fc2'          => '#ffffff',
            'to2'          => 70,
            'ff2'          => 'Arial',
            'fw2'          => 'normal',
            'tp2'          => 'bl',
            'ts2'          => 'dark',
        ),
    ) );
    add_options_page(
        'MVC Image Watermarker',
        'MVC Watermarker',
        'manage_options',
        'mvc-image-watermarker',
        'mvc_iw_settings_page'
    );
}
add_action( 'admin_menu', 'mvc_iw_register_settings' );
add_action( 'admin_init', 'mvc_iw_register_settings' );

function mvc_iw_sanitize_defaults( $input ) {
    $clean = array();
    $ints = array('out_w','out_h','quality','logo_size','logo_margin','logo_opacity','fs1','to1','fs2','to2');
    $strs = array('logo_pos','txt1','fc1','ff1','fw1','tp1','ts1','txt2','fc2','ff2','fw2','tp2','ts2');
    foreach ($ints as $k) { $clean[$k] = isset($input[$k]) ? intval($input[$k]) : 0; }
    foreach ($strs as $k) { $clean[$k] = isset($input[$k]) ? sanitize_text_field($input[$k]) : ''; }
    return $clean;
}

function mvc_iw_settings_page() {
    $defaults = get_option('mvc_iw_defaults', array());
    ?>
    <div class="wrap">
        <h1>MVC Image Watermarker — Default Settings</h1>
        <p style="color:#666;">These become the pre-filled defaults in the tool. Users can still change them per session.</p>
        <form method="post" action="options.php">
            <?php settings_fields('mvc_iw_settings'); ?>
            <table class="form-table">
                <tr><th>Output Width (px)</th><td><input type="number" name="mvc_iw_defaults[out_w]" value="<?php echo esc_attr($defaults['out_w'] ?? 1200); ?>" class="small-text"></td></tr>
                <tr><th>Output Height (px)</th><td><input type="number" name="mvc_iw_defaults[out_h]" value="<?php echo esc_attr($defaults['out_h'] ?? 800); ?>" class="small-text"></td></tr>
                <tr><th>JPEG Quality (%)</th><td><input type="number" name="mvc_iw_defaults[quality]" value="<?php echo esc_attr($defaults['quality'] ?? 92); ?>" min="1" max="100" class="small-text"></td></tr>
                <tr><th colspan="2"><hr><strong>Logo Defaults</strong></th></tr>
                <tr><th>Logo Size (% of width)</th><td><input type="number" name="mvc_iw_defaults[logo_size]" value="<?php echo esc_attr($defaults['logo_size'] ?? 12); ?>" class="small-text"></td></tr>
                <tr><th>Logo Margin (px)</th><td><input type="number" name="mvc_iw_defaults[logo_margin]" value="<?php echo esc_attr($defaults['logo_margin'] ?? 20); ?>" class="small-text"></td></tr>
                <tr><th>Logo Opacity (%)</th><td><input type="number" name="mvc_iw_defaults[logo_opacity]" value="<?php echo esc_attr($defaults['logo_opacity'] ?? 85); ?>" class="small-text"></td></tr>
                <tr><th colspan="2"><hr><strong>Text Overlay 1 Default</strong></th></tr>
                <tr><th>Default Text</th><td><input type="text" name="mvc_iw_defaults[txt1]" value="<?php echo esc_attr($defaults['txt1'] ?? ''); ?>" class="regular-text" placeholder="© Media Vines Corp"></td></tr>
                <tr><th colspan="2"><hr><strong>Text Overlay 2 Default</strong></th></tr>
                <tr><th>Default Text</th><td><input type="text" name="mvc_iw_defaults[txt2]" value="<?php echo esc_attr($defaults['txt2'] ?? ''); ?>" class="regular-text" placeholder="mediavines.com"></td></tr>
            </table>
            <p><strong>Shortcode:</strong> <code>[image_watermarker]</code> — paste this into any page or Elementor HTML widget.</p>
            <?php submit_button('Save Defaults'); ?>
        </form>
    </div>
    <?php
}
