<?php
/**
 * Media Vines Template AJAX Handlers
 * Handles template-related AJAX requests from frontend
 * CORRECTED VERSION: Uses wfs_workflow post type
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MediaVines_Template_AJAX {
    
    public function __construct() {
        // AJAX actions for logged-in users
        add_action('wp_ajax_get_workflow_templates', array($this, 'get_workflow_templates'));
        add_action('wp_ajax_get_template_tasks', array($this, 'get_template_tasks'));
        add_action('wp_ajax_use_template', array($this, 'use_template'));
    }
    
    /**
     * Get workflow templates grouped by category
     */
    public function get_workflow_templates() {
        check_ajax_referer('mediavines_workflow_nonce', 'nonce');
        
        // Get all templates
        $templates = get_posts(array(
            'post_type' => 'wfs_workflow',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_is_template',
                    'value' => '1'
                )
            )
        ));
        
        // Group by category
        $grouped = array(
            'standard' => array(),
            'custom' => array(),
            'archive' => array()
        );
        
        foreach ($templates as $template) {
            $category = get_post_meta($template->ID, '_template_category', true);
            if (empty($category)) {
                $category = 'standard';
            }
            
            // Get client info
            $client_id = get_field('client', $template->ID);
            $client_name = $client_id ? get_the_title($client_id) : 'No Client';
            
            // Get task count
            $task_count = $this->get_template_task_count($template->ID);
            
            $template_data = array(
                'id' => $template->ID,
                'title' => $template->post_title,
                'client' => $client_name,
                'task_count' => $task_count,
                'category' => $category
            );
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = array();
            }
            $grouped[$category][] = $template_data;
        }
        
        // Remove empty categories
        $grouped = array_filter($grouped, function($category) {
            return !empty($category);
        });
        
        wp_send_json_success(array(
            'templates' => $grouped
        ));
    }
    
    /**
     * Get tasks for a template
     */
    public function get_template_tasks() {
        check_ajax_referer('mediavines_workflow_nonce', 'nonce');
        
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        
        if (!$template_id) {
            wp_send_json_error('Invalid template ID');
        }
        
        // Verify it's a template
        $is_template = get_post_meta($template_id, '_is_template', true);
        if (!$is_template) {
            wp_send_json_error('Not a template workflow');
        }
        
        // Get template tasks
        $tasks = get_posts(array(
            'post_type' => 'wfs_task',
            'posts_per_page' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => 'workflow',
                    'value' => $template_id
                )
            )
        ));
        
        $task_data = array();
        foreach ($tasks as $task) {
            $task_data[] = array(
                'id' => $task->ID,
                'title' => $task->post_title,
                'description' => $task->post_content,
                'status' => get_field('status', $task->ID) ?: 'to-do'
            );
        }
        
        wp_send_json_success(array(
            'tasks' => $task_data,
            'template_name' => get_the_title($template_id)
        ));
    }
    
    /**
     * Use template - creates tasks from template for a workflow
     */
    public function use_template() {
        check_ajax_referer('mediavines_workflow_nonce', 'nonce');
        
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $workflow_id = isset($_POST['workflow_id']) ? intval($_POST['workflow_id']) : 0;
        $task_assignments = isset($_POST['task_assignments']) ? $_POST['task_assignments'] : array();
        
        if (!$template_id || !$workflow_id) {
            wp_send_json_error('Missing required parameters');
        }
        
        // Verify template
        $is_template = get_post_meta($template_id, '_is_template', true);
        if (!$is_template) {
            wp_send_json_error('Not a valid template');
        }
        
        // Get template tasks
        $template_tasks = get_posts(array(
            'post_type' => 'wfs_task',
            'posts_per_page' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => 'workflow',
                    'value' => $template_id
                )
            )
        ));
        
        $created_tasks = array();
        
        foreach ($template_tasks as $template_task) {
            // Create new task
            $new_task_args = array(
                'post_title' => $template_task->post_title,
                'post_content' => $template_task->post_content,
                'post_status' => 'publish',
                'post_type' => 'wfs_task',
                'post_author' => get_current_user_id()
            );
            
            $new_task_id = wp_insert_post($new_task_args);
            
            if (!is_wp_error($new_task_id)) {
                // Set workflow
                update_field('workflow', $workflow_id, $new_task_id);
                
                // Set status
                update_field('status', 'to-do', $new_task_id);
                
                // Assign user if specified
                if (isset($task_assignments[$template_task->ID])) {
                    $assigned_user = intval($task_assignments[$template_task->ID]);
                    if ($assigned_user > 0) {
                        update_field('assigned_to', $assigned_user, $new_task_id);
                    }
                }
                
                $created_tasks[] = array(
                    'id' => $new_task_id,
                    'title' => $template_task->post_title
                );
            }
        }
        
        // Increment template usage count
        $usage_count = get_post_meta($template_id, '_template_usage_count', true);
        $usage_count = $usage_count ? intval($usage_count) + 1 : 1;
        update_post_meta($template_id, '_template_usage_count', $usage_count);
        
        wp_send_json_success(array(
            'created_tasks' => $created_tasks,
            'message' => count($created_tasks) . ' tasks created from template'
        ));
    }
    
    /**
     * Get task count for template
     */
    private function get_template_task_count($template_id) {
        $tasks = get_posts(array(
            'post_type' => 'wfs_task',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'workflow',
                    'value' => $template_id
                )
            )
        ));
        
        return count($tasks);
    }
}

// Initialize
new MediaVines_Template_AJAX();
