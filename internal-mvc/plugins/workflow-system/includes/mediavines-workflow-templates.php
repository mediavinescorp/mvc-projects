<?php
/**
 * Media Vines Workflow Templates - Backend
 * Adds template functionality to workflows
 * Version: 2.0 - Forced registration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MediaVines_Workflow_Templates {
    
    public function __construct() {
        // Register taxonomy early
        add_action('init', array($this, 'register_template_taxonomy'), 0);
        
        // Add meta boxes - try multiple hooks
        add_action('add_meta_boxes', array($this, 'add_template_meta_box'), 1);
        add_action('add_meta_boxes_workflow', array($this, 'add_template_meta_box'), 1);
        
        // Force meta box in block editor
        add_filter('rest_prepare_workflow', array($this, 'add_template_to_rest'), 10, 3);
        
        // Save template meta data
        add_action('save_post_workflow', array($this, 'save_template_meta'), 10, 2);
        add_action('save_post', array($this, 'save_template_meta_universal'), 10, 2);
        
        // Add duplicate button
        add_action('post_submitbox_misc_actions', array($this, 'add_duplicate_button'));
        
        // Handle duplication
        add_action('admin_action_duplicate_as_template', array($this, 'duplicate_workflow_as_template'));
        
        // List filters
        add_action('restrict_manage_posts', array($this, 'add_template_filter'));
        add_filter('parse_query', array($this, 'filter_by_template'));
        
        // List columns
        add_filter('manage_workflow_posts_columns', array($this, 'add_template_column'));
        add_action('manage_workflow_posts_custom_column', array($this, 'template_column_content'), 10, 2);
        
        // Debug hook
        add_action('admin_notices', array($this, 'debug_meta_boxes'));
    }
    
    /**
     * Debug - show if meta boxes are registered
     */
    public function debug_meta_boxes() {
        global $wp_meta_boxes, $post_type;
        
        if ($post_type === 'workflow' && isset($_GET['post'])) {
            $has_template_box = false;
            if (isset($wp_meta_boxes['workflow']['side'])) {
                foreach ($wp_meta_boxes['workflow']['side'] as $priority => $boxes) {
                    if (isset($boxes['workflow_template_settings'])) {
                        $has_template_box = true;
                        break;
                    }
                }
            }
            
            echo '<div class="notice notice-info">';
            echo '<p><strong>Template System Debug:</strong></p>';
            echo '<p>Meta box registered: ' . ($has_template_box ? '✅ YES' : '❌ NO') . '</p>';
            echo '<p>Post type: ' . $post_type . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Register template category taxonomy
     */
    public function register_template_taxonomy() {
        $labels = array(
            'name'              => 'Template Categories',
            'singular_name'     => 'Template Category',
            'menu_name'         => 'Template Categories',
        );
        
        register_taxonomy('template_category', array('workflow'), array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => false,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'template-category'),
        ));
        
        // Create default categories
        $this->create_default_categories();
    }
    
    /**
     * Create default template categories
     */
    private function create_default_categories() {
        $categories = array(
            'Standard' => 'Standard workflow templates',
            'Custom' => 'Custom workflow templates',
            'Archive' => 'Archived workflow templates'
        );
        
        foreach ($categories as $name => $description) {
            if (!term_exists($name, 'template_category')) {
                wp_insert_term($name, 'template_category', array(
                    'description' => $description,
                    'slug' => sanitize_title($name)
                ));
            }
        }
    }
    
    /**
     * Add template meta box - FORCED
     */
    public function add_template_meta_box() {
        // Remove any existing box with same ID
        remove_meta_box('workflow_template_settings', 'workflow', 'side');
        
        // Add it fresh
        add_meta_box(
            'workflow_template_settings',
            '⭐ Template Settings',
            array($this, 'render_template_meta_box'),
            'workflow',
            'side',
            'high'
        );
    }
    
    /**
     * Render template meta box
     */
    public function render_template_meta_box($post) {
        wp_nonce_field('workflow_template_meta', 'workflow_template_nonce');
        
        $is_template = get_post_meta($post->ID, '_is_template', true);
        $usage_count = get_post_meta($post->ID, '_template_usage_count', true);
        
        ?>
        <div style="padding: 10px 0;">
            <p style="margin: 0 0 10px 0;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" 
                           name="is_template" 
                           value="1" 
                           <?php checked($is_template, '1'); ?>
                           style="margin-right: 8px;">
                    <strong>This is a template workflow</strong>
                </label>
            </p>
            
            <p class="description" style="margin: 0 0 15px 0; font-size: 12px; color: #666;">
                Templates can be used to quickly create multiple tasks at once from the dashboard.
            </p>
            
            <?php if ($is_template && $usage_count): ?>
            <p style="margin: 0 0 15px 0; padding: 8px; background: #f0f0f0; border-radius: 3px;">
                <small><strong>Used:</strong> <?php echo intval($usage_count); ?> times</small>
            </p>
            <?php endif; ?>
            
            <div id="template-category-section" style="<?php echo $is_template ? '' : 'display: none;'; ?>">
                <hr style="margin: 15px 0;">
                <p style="margin: 0 0 8px 0;">
                    <label for="template_category"><strong>Template Category:</strong></label>
                </p>
                <?php
                $terms = wp_get_post_terms($post->ID, 'template_category');
                $selected = !empty($terms) && !is_wp_error($terms) ? $terms[0]->term_id : '';
                
                wp_dropdown_categories(array(
                    'taxonomy' => 'template_category',
                    'name' => 'template_category',
                    'id' => 'template_category',
                    'selected' => $selected,
                    'hide_empty' => false,
                    'show_option_none' => '— Select Category —',
                    'option_none_value' => ''
                ));
                ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('input[name="is_template"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#template-category-section').slideDown();
                } else {
                    $('#template-category-section').slideUp();
                }
            });
        });
        </script>
        
        <style>
        #workflow_template_settings .inside { margin: 0; padding: 0; }
        #template_category { width: 100%; }
        </style>
        <?php
    }
    
    /**
     * Add template to REST API
     */
    public function add_template_to_rest($response, $post, $request) {
        if ($post->post_type === 'workflow') {
            $is_template = get_post_meta($post->ID, '_is_template', true);
            $response->data['is_template'] = $is_template === '1';
        }
        return $response;
    }
    
    /**
     * Save template meta data
     */
    public function save_template_meta($post_id, $post) {
        $this->save_template_meta_universal($post_id, $post);
    }
    
    /**
     * Save template meta - universal
     */
    public function save_template_meta_universal($post_id, $post) {
        // Only for workflows
        if (!isset($post->post_type) || $post->post_type !== 'workflow') {
            return;
        }
        
        // Check nonce
        if (!isset($_POST['workflow_template_nonce']) || 
            !wp_verify_nonce($_POST['workflow_template_nonce'], 'workflow_template_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save template flag
        $is_template = isset($_POST['is_template']) ? '1' : '0';
        update_post_meta($post_id, '_is_template', $is_template);
        
        // Initialize usage count if new template
        if ($is_template === '1' && !get_post_meta($post_id, '_template_usage_count', true)) {
            update_post_meta($post_id, '_template_usage_count', 0);
        }
        
        // Save template category
        if ($is_template === '1' && isset($_POST['template_category']) && !empty($_POST['template_category'])) {
            wp_set_post_terms($post_id, array(intval($_POST['template_category'])), 'template_category');
        } else {
            wp_set_post_terms($post_id, array(), 'template_category');
        }
    }
    
    /**
     * Add duplicate button
     */
    public function add_duplicate_button() {
        global $post;
        
        if (!$post || $post->post_type !== 'workflow') {
            return;
        }
        
        $is_template = get_post_meta($post->ID, '_is_template', true);
        
        ?>
        <div class="misc-pub-section misc-pub-template-actions">
            <?php if (!$is_template): ?>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?action=duplicate_as_template&post=' . $post->ID), 'duplicate_template_' . $post->ID); ?>" 
               class="button button-secondary" 
               style="width: 100%; text-align: center; margin-top: 5px;">
                📋 Duplicate as Template
            </a>
            <?php else: ?>
            <div style="padding: 5px 0; text-align: center; color: #D4AF37; font-weight: bold;">
                ⭐ Template Workflow
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Duplicate workflow as template
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
        if (!$original || $original->post_type !== 'workflow') {
            wp_die('Invalid workflow');
        }
        
        // Create template
        $new_post_id = wp_insert_post(array(
            'post_title'   => $original->post_title . ' (Template)',
            'post_content' => $original->post_content,
            'post_status'  => 'publish',
            'post_type'    => 'workflow',
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
            'post_type' => 'task',
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
                'post_type'    => 'task'
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
    
    /**
     * Add filter
     */
    public function add_template_filter($post_type) {
        if ($post_type !== 'workflow') {
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
    
    /**
     * Filter by template
     */
    public function filter_by_template($query) {
        global $pagenow, $typenow;
        
        if ($pagenow === 'edit.php' && $typenow === 'workflow' && isset($_GET['template_filter'])) {
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
    
    /**
     * Add column
     */
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
    
    /**
     * Column content
     */
    public function template_column_content($column, $post_id) {
        if ($column === 'is_template') {
            $is_template = get_post_meta($post_id, '_is_template', true);
            if ($is_template) {
                $terms = wp_get_post_terms($post_id, 'template_category');
                $category = !empty($terms) ? $terms[0]->name : 'Uncategorized';
                echo '<span style="color: #D4AF37;">⭐ ' . esc_html($category) . '</span>';
            } else {
                echo '—';
            }
        }
    }
}

// Initialize immediately
new MediaVines_Workflow_Templates();