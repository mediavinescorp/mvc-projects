<?php
/**
 * Plugin Name: WFS Resources System
 * Description: Internal resources management system for Media Vines Corp workflow platform
 * Version: 1.0
 * Author: Media Vines Corp
 */

if (!defined('ABSPATH')) {
    exit;
}

class WFS_Resources_System {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('acf/init', array($this, 'register_acf_fields'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_wfs_get_resources', array($this, 'ajax_get_resources'));
        add_action('wp_ajax_wfs_get_resource', array($this, 'ajax_get_resource'));
        add_action('wp_ajax_wfs_save_resource', array($this, 'ajax_save_resource'));
        add_action('wp_ajax_wfs_delete_resource', array($this, 'ajax_delete_resource'));
        add_action('wp_ajax_wfs_get_clients', array($this, 'ajax_get_clients'));
        add_action('wp_ajax_wfs_get_categories', array($this, 'ajax_get_categories'));
        
        // Shortcode
        add_shortcode('wfs_resources_hub', array($this, 'render_resources_hub'));
        
        // Track modifications
        add_action('save_post_wfs_resource', array($this, 'track_modifications'), 10, 3);
    }
    
    public function register_post_types() {
        // Register Resources post type
        register_post_type('wfs_resource', array(
            'labels' => array(
                'name' => 'Resources',
                'singular_name' => 'Resource',
                'add_new' => 'Add New Resource',
                'add_new_item' => 'Add New Resource',
                'edit_item' => 'Edit Resource',
                'new_item' => 'New Resource',
                'view_item' => 'View Resource',
                'search_items' => 'Search Resources',
                'not_found' => 'No resources found',
                'not_found_in_trash' => 'No resources found in trash'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-book-alt',
            'menu_position' => 26,
            'supports' => array('title', 'editor', 'author', 'revisions'),
            'has_archive' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'map_meta_cap' => true
        ));
        
        // Register Resource Categories taxonomy
        register_taxonomy('wfs_resource_category', 'wfs_resource', array(
            'labels' => array(
                'name' => 'Resource Categories',
                'singular_name' => 'Resource Category',
                'search_items' => 'Search Categories',
                'all_items' => 'All Categories',
                'parent_item' => 'Parent Category',
                'parent_item_colon' => 'Parent Category:',
                'edit_item' => 'Edit Category',
                'update_item' => 'Update Category',
                'add_new_item' => 'Add New Category',
                'new_item_name' => 'New Category Name',
                'menu_name' => 'Categories'
            ),
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => false,
            'rewrite' => false,
            'capabilities' => array(
                'manage_terms' => 'manage_options',
                'edit_terms' => 'manage_options',
                'delete_terms' => 'manage_options',
                'assign_terms' => 'edit_posts'
            )
        ));
    }
    
    public function register_acf_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        
        acf_add_local_field_group(array(
            'key' => 'group_wfs_resource',
            'title' => 'Resource Details',
            'fields' => array(
                array(
                    'key' => 'field_wfs_resource_clients',
                    'label' => 'Associated Clients',
                    'name' => 'wfs_resource_clients',
                    'type' => 'relationship',
                    'instructions' => 'Select one or more clients associated with this resource',
                    'required' => 0,
                    'post_type' => array('wfs_client'),
                    'filters' => array('search'),
                    'return_format' => 'id',
                    'multiple' => 1
                ),
                array(
                    'key' => 'field_wfs_resource_last_modified_by',
                    'label' => 'Last Modified By',
                    'name' => 'wfs_resource_last_modified_by',
                    'type' => 'user',
                    'instructions' => 'User who last modified this resource',
                    'required' => 0,
                    'return_format' => 'id',
                    'multiple' => 0
                )
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'wfs_resource',
                    ),
                ),
            ),
        ));
    }
    
    public function track_modifications($post_id, $post, $update) {
        // Only track on updates, not initial creation
        if ($update && !wp_is_post_revision($post_id)) {
            $current_user_id = get_current_user_id();
            update_field('wfs_resource_last_modified_by', $current_user_id, $post_id);
        }
    }
    
    public function enqueue_scripts() {
        if (is_page() && has_shortcode(get_post()->post_content, 'wfs_resources_hub')) {
            wp_enqueue_style('wfs-resources-hub', plugin_dir_url(__FILE__) . 'css/resources-hub.css', array(), '1.0');
            wp_enqueue_script('wfs-resources-hub', plugin_dir_url(__FILE__) . 'js/resources-hub.js', array('jquery'), '1.0', true);
            
            wp_localize_script('wfs-resources-hub', 'wfsResources', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wfs_resources_nonce'),
                'currentUserId' => get_current_user_id()
            ));
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            global $post_type;
            if ('wfs_resource' === $post_type) {
                // Admin-specific scripts if needed
            }
        }
    }
    
    // AJAX: Get all resources with filters
    public function ajax_get_resources() {
        check_ajax_referer('wfs_resources_nonce', 'nonce');
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $client_filter = isset($_POST['client']) ? intval($_POST['client']) : 0;
        $category_filter = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';
        
        $args = array(
            'post_type' => 'wfs_resource',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => $orderby,
            'order' => $order
        );
        
        // Search
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        // Category filter
        if ($category_filter > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'wfs_resource_category',
                    'field' => 'term_id',
                    'terms' => $category_filter
                )
            );
        }
        
        $query = new WP_Query($args);
        $resources = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get clients
                $client_ids = get_field('wfs_resource_clients', $post_id);
                
                // Apply client filter if needed
                if ($client_filter > 0) {
                    if (!is_array($client_ids) || !in_array($client_filter, $client_ids)) {
                        continue;
                    }
                }
                
                $client_names = array();
                if (is_array($client_ids) && !empty($client_ids)) {
                    foreach ($client_ids as $client_id) {
                        $client_names[] = get_the_title($client_id);
                    }
                }
                
                // Get categories
                $categories = wp_get_post_terms($post_id, 'wfs_resource_category');
                $category_names = array();
                if (!empty($categories) && !is_wp_error($categories)) {
                    foreach ($categories as $cat) {
                        $category_names[] = $cat->name;
                    }
                }
                
                // Get author
                $author_id = get_the_author_meta('ID');
                $author_name = get_the_author();
                
                // Get last modified info
                $last_modified_by_id = get_field('wfs_resource_last_modified_by', $post_id);
                $last_modified_by = '';
                if ($last_modified_by_id) {
                    $user_data = get_userdata($last_modified_by_id);
                    $last_modified_by = $user_data ? $user_data->display_name : '';
                }
                
                $resources[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'clients' => $client_names,
                    'client_ids' => $client_ids,
                    'categories' => $category_names,
                    'author' => $author_name,
                    'author_id' => $author_id,
                    'date_created' => get_the_date('Y-m-d H:i:s'),
                    'date_modified' => get_the_modified_date('Y-m-d H:i:s'),
                    'modified_by' => $last_modified_by,
                    'content' => get_the_content()
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($resources);
    }
    
    // AJAX: Get single resource
    public function ajax_get_resource() {
        check_ajax_referer('wfs_resources_nonce', 'nonce');
        
        $resource_id = isset($_POST['resource_id']) ? intval($_POST['resource_id']) : 0;
        
        if (!$resource_id) {
            wp_send_json_error('Invalid resource ID');
        }
        
        $post = get_post($resource_id);
        
        if (!$post || $post->post_type !== 'wfs_resource') {
            wp_send_json_error('Resource not found');
        }
        
        // Get clients
        $client_ids = get_field('wfs_resource_clients', $resource_id);
        
        // Get categories
        $category_ids = wp_get_post_terms($resource_id, 'wfs_resource_category', array('fields' => 'ids'));
        
        // Get last modified info
        $last_modified_by_id = get_field('wfs_resource_last_modified_by', $resource_id);
        
        $resource = array(
            'id' => $resource_id,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'client_ids' => $client_ids ?: array(),
            'category_ids' => $category_ids ?: array(),
            'author_id' => $post->post_author,
            'date_created' => get_the_date('Y-m-d H:i:s', $resource_id),
            'date_modified' => get_the_modified_date('Y-m-d H:i:s', $resource_id),
            'modified_by_id' => $last_modified_by_id
        );
        
        wp_send_json_success($resource);
    }
    
    // AJAX: Save resource (create or update)
    public function ajax_save_resource() {
        check_ajax_referer('wfs_resources_nonce', 'nonce');
        
        $resource_id = isset($_POST['resource_id']) ? intval($_POST['resource_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        // Allow same HTML as WordPress post editor - don't over-sanitize
        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
        $client_ids = isset($_POST['client_ids']) ? array_map('intval', $_POST['client_ids']) : array();
        $category_ids = isset($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : array();
        
        if (empty($title)) {
            wp_send_json_error('Title is required');
        }
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_type' => 'wfs_resource',
            'post_status' => 'publish'
        );
        
        if ($resource_id > 0) {
            // Update existing resource
            $post_data['ID'] = $resource_id;
            $result = wp_update_post($post_data, true);
        } else {
            // Create new resource
            $result = wp_insert_post($post_data, true);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Update clients
        update_field('wfs_resource_clients', $client_ids, $result);
        
        // Update categories
        wp_set_post_terms($result, $category_ids, 'wfs_resource_category');
        
        // Update last modified by
        $current_user_id = get_current_user_id();
        update_field('wfs_resource_last_modified_by', $current_user_id, $result);
        
        wp_send_json_success(array(
            'resource_id' => $result,
            'message' => $resource_id > 0 ? 'Resource updated successfully' : 'Resource created successfully'
        ));
    }
    
    // AJAX: Delete resource
    public function ajax_delete_resource() {
        check_ajax_referer('wfs_resources_nonce', 'nonce');
        
        $resource_id = isset($_POST['resource_id']) ? intval($_POST['resource_id']) : 0;
        
        if (!$resource_id) {
            wp_send_json_error('Invalid resource ID');
        }
        
        $result = wp_delete_post($resource_id, true);
        
        if (!$result) {
            wp_send_json_error('Failed to delete resource');
        }
        
        wp_send_json_success('Resource deleted successfully');
    }
    
    // AJAX: Get clients list
    public function ajax_get_clients() {
        check_ajax_referer('wfs_resources_nonce', 'nonce');
        
        $args = array(
            'post_type' => 'wfs_client',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $query = new WP_Query($args);
        $clients = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $clients[] = array(
                    'id' => get_the_ID(),
                    'name' => get_the_title()
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($clients);
    }
    
    // AJAX: Get categories list
    public function ajax_get_categories() {
        check_ajax_referer('wfs_resources_nonce', 'nonce');
        
        $categories = get_terms(array(
            'taxonomy' => 'wfs_resource_category',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $category_list = array();
        
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $category_list[] = array(
                    'id' => $cat->term_id,
                    'name' => $cat->name
                );
            }
        }
        
        wp_send_json_success($category_list);
    }
    
    // Render the resources hub shortcode
    public function render_resources_hub() {
        if (!is_user_logged_in()) {
            // Get current page URL for redirect after login
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $login_url = wp_login_url($current_url);
            
            return '<div style="text-align: center; padding: 50px 40px; border: 2px solid #D4AF37; border-radius: 8px; background: linear-gradient(135deg, #000 0%, #1a1a1a 100%); margin: 20px auto; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <div style="margin-bottom: 30px;">
                    <img src="https://o9x4z6hft7.wpdns.site/wp-content/uploads/2025/11/mvc-new-black-logo.png" alt="Media Vines Corp" style="max-width: 200px; height: auto; background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                    <div style="color: #D4AF37; font-size: 20px; font-weight: 600; letter-spacing: 1px; margin-top: 15px;">MEDIA VINES CORP</div>
                </div>
                <h2 style="color: #fff; margin: 20px 0 10px 0; font-size: 24px;">Login Required</h2>
                <p style="margin-bottom: 30px; color: #ccc; font-size: 15px;">You must be logged in to access the Resources Hub.</p>
                <a href="' . esc_url($login_url) . '" style="display: inline-block; padding: 14px 40px; background: #D4AF37; color: #000; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 16px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(212, 175, 55, 0.3);">Login Now</a>
            </div>';
        }
        
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/resources-hub.php';
        return ob_get_clean();
    }
}

// Initialize the plugin
WFS_Resources_System::get_instance();