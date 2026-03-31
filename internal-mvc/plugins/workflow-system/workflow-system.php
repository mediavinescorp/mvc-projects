<?php
/**
 * Plugin Name: Workflow Management System
 * Plugin URI: https://mediavines.com
 * Description: Complete workflow and task management system for Media Vines Corp
 * Version: 1.0.5
 * Author: Media Vines Corp
 * Author URI: https://mediavines.com
 * Text Domain: workflow-system
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WFS_VERSION', '1.0.5');
define('WFS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WFS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WFS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// TEMPLATE SYSTEM - Meta Box for wfs_workflow (CORRECT POST TYPE!)
add_action('add_meta_boxes', 'wfs_add_template_metabox', 10);

function wfs_add_template_metabox() {
    add_meta_box(
        'wfs_template_settings',
        '⭐ Template Settings',
        'wfs_render_template_metabox',
        'wfs_workflow',  // CORRECT: wfs_workflow not workflow!
        'side',
        'high'
    );
}

function wfs_render_template_metabox($post) {
    wp_nonce_field('wfs_template_meta', 'wfs_template_nonce');
    
    $is_template = get_post_meta($post->ID, '_is_template', true);
    $usage_count = get_post_meta($post->ID, '_template_usage_count', true);
    ?>
    <div style="padding: 12px; background: #fff3cd; border: 2px solid #d4af37; border-radius: 4px;">
        <p style="margin: 0 0 12px 0; font-weight: bold; color: #d4af37; font-size: 13px;">
            🎉 Template System Active!
        </p>
        
        <label style="display: block; margin-bottom: 12px; cursor: pointer;">
            <input type="checkbox" 
                   name="is_template" 
                   id="is_template_checkbox"
                   value="1" 
                   <?php checked($is_template, '1'); ?>
                   style="margin-right: 6px;">
            <strong>This is a template workflow</strong>
        </label>
        
        <p style="margin: 0 0 12px 0; font-size: 12px; color: #666; line-height: 1.4;">
            Templates can be used in the dashboard to quickly create multiple tasks at once.
        </p>
        
        <?php if ($is_template && $usage_count): ?>
        <p style="margin: 0 0 12px 0; padding: 8px; background: #f0f0f0; border-radius: 3px; font-size: 12px;">
            <strong>📊 Used:</strong> <?php echo intval($usage_count); ?> times
        </p>
        <?php endif; ?>
        
        <div id="template-category-section" style="<?php echo $is_template ? '' : 'display: none;'; ?>">
            <hr style="margin: 12px 0; border: none; border-top: 1px solid #ddd;">
            
            <p style="margin: 0 0 6px 0; font-size: 13px;">
                <strong>📂 Category:</strong>
            </p>
            
            <select name="template_category" id="template_category_select" style="width: 100%; padding: 4px;">
                <option value="">— Select Category —</option>
                <option value="standard" <?php selected(get_post_meta($post->ID, '_template_category', true), 'standard'); ?>>Standard</option>
                <option value="custom" <?php selected(get_post_meta($post->ID, '_template_category', true), 'custom'); ?>>Custom</option>
                <option value="archive" <?php selected(get_post_meta($post->ID, '_template_category', true), 'archive'); ?>>Archive</option>
            </select>
            
            <p style="margin: 8px 0 0 0; font-size: 11px; color: #666;">
                Organize templates by category
            </p>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#is_template_checkbox').on('change', function() {
            if ($(this).is(':checked')) {
                $('#template-category-section').slideDown(200);
            } else {
                $('#template-category-section').slideUp(200);
            }
        });
    });
    </script>
    <?php
}

add_action('save_post_wfs_workflow', 'wfs_save_template_meta', 10, 2);

function wfs_save_template_meta($post_id, $post) {
    if (!isset($_POST['wfs_template_nonce']) || 
        !wp_verify_nonce($_POST['wfs_template_nonce'], 'wfs_template_meta')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save template flag
    $is_template = isset($_POST['is_template']) ? '1' : '0';
    update_post_meta($post_id, '_is_template', $is_template);
    
    // Initialize usage count
    if ($is_template === '1' && !get_post_meta($post_id, '_template_usage_count', true)) {
        update_post_meta($post_id, '_template_usage_count', 0);
    }
    
    // Save category
    if (isset($_POST['template_category']) && !empty($_POST['template_category'])) {
        update_post_meta($post_id, '_template_category', sanitize_text_field($_POST['template_category']));
    }
}

add_action('admin_notices', function() {
    global $post_type, $pagenow;
    if ($post_type === 'wfs_workflow' && ($pagenow === 'post.php' || $pagenow === 'post-new.php')) {
        echo '<div class="notice notice-success" style="border-left: 4px solid #d4af37;">';
        echo '<p><strong>✅ TEMPLATE SYSTEM LOADED</strong> - Template Settings box should appear in the right sidebar!</p>';
        echo '</div>';
    }
});

/**
 * Main Workflow System Class
 */
class Workflow_System {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        $this->load_dependencies();
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init_components'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    private function load_dependencies() {
        require_once WFS_PLUGIN_DIR . 'includes/custom-post-types.php';
        require_once WFS_PLUGIN_DIR . 'includes/user-roles.php';
        require_once WFS_PLUGIN_DIR . 'includes/acf-fields.php';
        require_once WFS_PLUGIN_DIR . 'includes/dashboard.php';
        require_once WFS_PLUGIN_DIR . 'includes/login-customization.php';
        require_once WFS_PLUGIN_DIR . 'includes/mediavines-template-integration.php';
        
        // Phase 2A: Workflow automation and dependencies
        require_once WFS_PLUGIN_DIR . 'includes/acf-fields-phase-2a.php';
        require_once WFS_PLUGIN_DIR . 'includes/workflow-core-logic.php';

// Template System
require_once plugin_dir_path(__FILE__) . 'includes/acf-template-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/wfs-template-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/wfs-dashboard-template-integration.php';
require_once plugin_dir_path(__FILE__) . 'includes/wfs-template-preview-ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/wfs-workflow-clone.php';

    }
    
    public function init_components() {
        WFS_Custom_Post_Types::register();
        add_action('acf/init', array('WFS_ACF_Fields', 'register'));
        WFS_Dashboard::init();
    }
    
    public function enqueue_scripts() {
        if (is_page('workflow-dashboard')) {
            wp_enqueue_style(
                'wfs-dashboard',
                WFS_PLUGIN_URL . 'assets/css/dashboard.css',
                array(),
                WFS_VERSION
            );
            
            wp_enqueue_script(
                'wfs-dashboard',
                WFS_PLUGIN_URL . 'assets/js/dashboard.js',
                array('jquery'),
                WFS_VERSION,
                true
            );
            
            wp_localize_script('wfs-dashboard', 'wfsData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wfs_dashboard_nonce')
            ));
        }
    }
    
    public function enqueue_admin_scripts() {
        // Add admin-specific styles if needed
    }
    
    public function activate() {
        WFS_Custom_Post_Types::register();
        WFS_User_Roles::create_roles();
        WFS_User_Roles::add_caps_to_admin();
        flush_rewrite_rules();
        $this->create_dashboard_page();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_dashboard_page() {
        $page = get_page_by_path('workflow-dashboard');
        
        if (!$page) {
            wp_insert_post(array(
                'post_title'    => 'Workflow Dashboard',
                'post_name'     => 'workflow-dashboard',
                'post_content'  => '[workflow_dashboard]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => 1,
                'comment_status' => 'closed',
                'ping_status'   => 'closed'
            ));
        }
    }
}

function workflow_system() {
    return Workflow_System::get_instance();
}

workflow_system();
