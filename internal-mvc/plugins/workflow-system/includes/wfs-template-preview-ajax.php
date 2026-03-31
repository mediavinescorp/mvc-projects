<?php
/**
 * Template Preview AJAX Handler
 * Provides template preview data for dashboard
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add AJAX handler for template preview
add_action('wp_ajax_wfs_get_template_preview', 'wfs_ajax_get_template_preview');

/**
 * AJAX: Get template preview data
 */
function wfs_ajax_get_template_preview() {
    check_ajax_referer('wfs_dashboard_templates_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('You must be logged in');
    }
    
    $template_id = intval($_POST['template_id']);
    
    if (!$template_id) {
        wp_send_json_error('Invalid template ID');
    }
    
    $template = get_post($template_id);
    
    if (!$template || $template->post_type !== 'wfs_workflow') {
        wp_send_json_error('Template not found');
    }
    
    // Check if it's actually a template
    if (get_post_meta($template_id, '_is_template', true) !== '1') {
        wp_send_json_error('This is not a template');
    }
    
    // Get template data
    $template_data = array(
        'id' => $template->ID,
        'name' => $template->post_title,
        'description' => $template->post_content ?: 'No description provided',
        'category' => get_post_meta($template_id, '_template_category', true) ?: 'standard',
        'execution_mode' => get_post_meta($template_id, '_execution_mode', true) ?: 'sequential',
        'tasks' => get_post_meta($template_id, '_template_tasks', true) ?: array(),
        'usage_count' => get_post_meta($template_id, '_template_usage_count', true) ?: 0
    );
    
    wp_send_json_success($template_data);
}
