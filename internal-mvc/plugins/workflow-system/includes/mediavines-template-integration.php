<?php
/**
 * Media Vines Template System Integration - CORRECTED
 * Loads template system components for wfs_workflow post type
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MediaVines_Template_System_Integration {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        $includes_dir = WFS_PLUGIN_DIR . 'includes/';
        
        // AJAX handlers
        if (!class_exists('MediaVines_Template_AJAX') && file_exists($includes_dir . 'mediavines-template-ajax.php')) {
            require_once $includes_dir . 'mediavines-template-ajax.php';
        }
    }
    
    private function init_hooks() {
        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add admin list features
        add_action('restrict_manage_posts', array($this, 'add_template_filter'));
        add_filter('parse_query', array($this, 'filter_by_template'));
        add_filter('manage_wfs_workflow_posts_columns', array($this, 'add_template_column'));
        add_action('manage_wfs_workflow_posts_custom_column', array($this, 'template_column_content'), 10, 2);
        
        // Add duplicate action handler ONLY (button is now in workflow-core-logic.php to prevent duplicates)
        add_action('admin_action_duplicate_as_template', array($this, 'duplicate_workflow_as_template'));
        
        // NOTE: We DO NOT add post_submitbox_misc_actions hook here anymore
        // All buttons are now consolidated in workflow-core-logic.php
    }
    
    public function enqueue_frontend_assets() {
        if (!is_page('workflow-dashboard')) {
            return;
        }
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'mediavines-templates',
            WFS_PLUGIN_URL . 'includes/mediavines-template-frontend.js',
            array('jquery'),
            WFS_VERSION,
            true
        );
        
        // Enqueue CSS
        wp_enqueue_style(
            'mediavines-templates',
            WFS_PLUGIN_URL . 'includes/mediavines-template-styles.css',
            array(),
            WFS_VERSION
        );
        
        // Localized data
        wp_localize_script('mediavines-templates', 'mediavinesWorkflow', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mediavines_workflow_nonce')
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        global $post_type;
        if ($post_type !== 'wfs_workflow') {
            return;
        }
        
        wp_add_inline_style('wp-admin', '
            .column-is_template { width: 150px; }
        ');
    }
    
    public function add_template_filter($post_type) {
        if ($post_type !== 'wfs_workflow') {
            return;
        }
        
        $selected = isset($_GET['template_filter']) ? $_GET['template_filter'] : '';
        ?>
        <select name="template_filter">
            <option value="">All Workflows</option>
            <option value="templates" <?php selected($selected, 'templates'); ?>>Templates Only</option>
            <option value="regular" <?php selected($selected, 'regular'); ?>>Regular Workflows</option>
        </select>
        <?php
    }
    
    public function filter_by_template($query) {
        global $pagenow, $typenow;
        
        if ($pagenow === 'edit.php' && $typenow === 'wfs_workflow' && isset($_GET['template_filter'])) {
            $meta_query = array();
            
            if ($_GET['template_filter'] === 'templates') {
                $meta_query[] = array('key' => '_is_template', 'value' => '1');
            } elseif ($_GET['template_filter'] === 'regular') {
                $meta_query[] = array(
                    'relation' => 'OR',
                    array('key' => '_is_template', 'value' => '1', 'compare' => '!='),
                    array('key' => '_is_template', 'compare' => 'NOT EXISTS')
                );
            }
            
            if (!empty($meta_query)) {
                $query->set('meta_query', $meta_query);
            }
        }
    }
    
    public function add_template_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['is_template'] = 'Template';
            }
        }
        return $new_columns;
    }
    
    public function template_column_content($column, $post_id) {
        if ($column === 'is_template') {
            $is_template = get_post_meta($post_id, '_is_template', true);
            if ($is_template === '1') {
                $category = get_post_meta($post_id, '_template_category', true);
                $cat_name = ucfirst($category ?: 'Standard');
                echo '<span style="color: #D4AF37;">⭐ ' . esc_html($cat_name) . '</span>';
            } else {
                echo '—';
            }
        }
    }
    
    /**
     * NOTE: The "Duplicate as Template" button has been moved to workflow-core-logic.php
     * to consolidate all workflow action buttons in one place and prevent duplicates.
     * This function only handles the duplication action itself.
     */
    
    public function duplicate_workflow_as_template() {
        if (!isset($_GET['post']) || !isset($_GET['_wpnonce'])) {
            wp_die('Invalid request');
        }
        
        $post_id = intval($_GET['post']);
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'duplicate_template_' . $post_id)) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied');
        }
        
        $original = get_post($post_id);
        if (!$original || $original->post_type !== 'wfs_workflow') {
            wp_die('Invalid workflow');
        }
        
        // Create template
        $new_post_id = wp_insert_post(array(
            'post_title'   => $original->post_title . ' (Template)',
            'post_content' => $original->post_content,
            'post_status'  => 'publish',
            'post_type'    => 'wfs_workflow',
            'post_author'  => get_current_user_id()
        ));
        
        if (is_wp_error($new_post_id)) {
            wp_die('Failed to create template');
        }
        
        // Copy ACF fields
        if (function_exists('get_fields')) {
            $fields = get_fields($post_id);
            if ($fields) {
                foreach ($fields as $key => $value) {
                    update_field($key, $value, $new_post_id);
                }
            }
        }
        
        // Mark as template
        update_post_meta($new_post_id, '_is_template', '1');
        update_post_meta($new_post_id, '_template_usage_count', 0);
        
        // Copy tasks
        $tasks = get_posts(array(
            'post_type' => 'wfs_task',
            'posts_per_page' => -1,
            'meta_query' => array(
                array('key' => 'workflow', 'value' => $post_id)
            )
        ));
        
        foreach ($tasks as $task) {
            $new_task_id = wp_insert_post(array(
                'post_title'   => $task->post_title,
                'post_content' => $task->post_content,
                'post_status'  => 'publish',
                'post_type' => 'wfs_task'
            ));
            
            if (!is_wp_error($new_task_id) && function_exists('get_fields')) {
                $task_fields = get_fields($task->ID);
                if ($task_fields) {
                    foreach ($task_fields as $key => $value) {
                        if ($key !== 'assigned_to') {
                            update_field($key, $value, $new_task_id);
                        }
                    }
                }
                update_field('workflow', $new_post_id, $new_task_id);
            }
        }
        
        wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
        exit;
    }
}

// Initialize
MediaVines_Template_System_Integration::get_instance();
