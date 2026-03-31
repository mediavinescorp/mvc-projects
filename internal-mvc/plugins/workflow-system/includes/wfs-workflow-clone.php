<?php
/**
 * Workflow Batch Clone System
 * Allows admins to clone workflows in batches with custom start dates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WFS_Workflow_Clone {
    
    public function __construct() {
        // Add clone button to workflow edit screen
        add_action('post_submitbox_misc_actions', array($this, 'add_batch_clone_button'));
        
        // AJAX handler for batch clone
        add_action('wp_ajax_wfs_batch_clone_workflow', array($this, 'ajax_batch_clone_workflow'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add dashboard filter for future workflows
        add_action('wfs_dashboard_query_filter', array($this, 'filter_future_workflows'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        global $post;
        
        // Only load on workflow edit screen
        if (($hook === 'post.php' || $hook === 'post-new.php') && 
            isset($post) && $post->post_type === 'wfs_workflow') {
            
            $plugin_url = plugin_dir_url(dirname(__FILE__));
            
            wp_enqueue_style('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-datepicker');
            
            wp_enqueue_style(
                'wfs-workflow-clone',
                $plugin_url . 'assets/css/workflow-clone.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'wfs-workflow-clone',
                $plugin_url . 'assets/js/workflow-clone.js',
                array('jquery', 'jquery-ui-datepicker'),
                '1.0.0',
                true
            );
            
            wp_localize_script('wfs-workflow-clone', 'wfsClone', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wfs_clone_nonce'),
                'workflowId' => $post->ID,
                'workflowTitle' => $post->post_title
            ));
        }
    }
    
    /**
     * Add batch clone button to workflow edit screen
     */
    public function add_batch_clone_button() {
        global $post;
        
        if (!$post || $post->post_type !== 'wfs_workflow') {
            return;
        }
        
        // Only show for admins
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Don't show for new workflows
        if ($post->post_status === 'auto-draft') {
            return;
        }
        
        ?>
        <div class="misc-pub-section misc-pub-clone-workflow">
            <button type="button" class="button button-secondary button-large" id="batch-clone-workflow" style="width: 100%; margin-top: 10px;">
                📋 Batch Clone Workflow
            </button>
        </div>
        <?php
    }
    
    /**
     * AJAX: Batch clone workflow
     */
    public function ajax_batch_clone_workflow() {
        check_ajax_referer('wfs_clone_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $workflow_id = intval($_POST['workflow_id']);
        $start_dates = $_POST['start_dates']; // Array of dates
        
        if (!$workflow_id || empty($start_dates)) {
            wp_send_json_error('Missing required data');
        }
        
        $original_workflow = get_post($workflow_id);
        
        if (!$original_workflow || $original_workflow->post_type !== 'wfs_workflow') {
            wp_send_json_error('Invalid workflow');
        }
        
        // Get original workflow tasks
        $original_tasks = get_posts(array(
            'post_type' => 'wfs_task',
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => 'task_order',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => 'task_workflow',
                    'value' => $workflow_id
                )
            )
        ));
        
        $created_workflows = array();
        
        // Clone workflow for each date
        foreach ($start_dates as $start_date) {
            $start_date = sanitize_text_field($start_date);
            
            // Format date for workflow name
            $date_obj = DateTime::createFromFormat('Y-m-d', $start_date);
            $formatted_date = $date_obj->format('M j, Y');
            
            // Create new workflow
            $new_workflow_data = array(
                'post_title' => $original_workflow->post_title . ' - ' . $formatted_date,
                'post_content' => $original_workflow->post_content,
                'post_type' => 'wfs_workflow',
                'post_status' => 'publish',
                'post_author' => get_current_user_id()
            );
            
            $new_workflow_id = wp_insert_post($new_workflow_data);
            
            if (is_wp_error($new_workflow_id)) {
                continue;
            }
            
            // Copy workflow ACF fields
            if (function_exists('get_fields')) {
                $workflow_fields = get_fields($workflow_id);
                if ($workflow_fields) {
                    foreach ($workflow_fields as $key => $value) {
                        // Don't copy these fields
                        if (in_array($key, array('completion_date', 'total_days', 'cancelled_date', 
                                                  'cancelled_by', 'cancellation_reason', 'reopened_date', 
                                                  'reopened_by', 'reopened_reason'))) {
                            continue;
                        }
                        
                        update_field($key, $value, $new_workflow_id);
                    }
                }
            }
            
            // Set workflow status to active
            update_field('workflow_status', 'active', $new_workflow_id);
            
            // Set custom start date
            update_field('start_date', $start_date . ' 09:00:00', $new_workflow_id);
            
            // Clone tasks
            $task_id_map = array();
            
            foreach ($original_tasks as $index => $original_task) {
                $new_task_data = array(
                    'post_title' => $original_task->post_title,
                    'post_content' => $original_task->post_content,
                    'post_type' => 'wfs_task',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id()
                );
                
                $new_task_id = wp_insert_post($new_task_data);
                
                if (is_wp_error($new_task_id)) {
                    continue;
                }
                
                // Copy task ACF fields
                if (function_exists('get_fields')) {
                    $task_fields = get_fields($original_task->ID);
                    if ($task_fields) {
                        foreach ($task_fields as $key => $value) {
                            // Skip certain fields
                            if (in_array($key, array('completed_date', 'is_locked', 'depends_on'))) {
                                continue;
                            }
                            
                            // Keep assignment but change status to "assigned"
                            if ($key === 'task_status') {
                                update_field($key, 'assigned', $new_task_id);
                            } else {
                                update_field($key, $value, $new_task_id);
                            }
                        }
                    }
                }
                
                // Link to new workflow
                update_field('task_workflow', $new_workflow_id, $new_task_id);
                
                // Map old task ID to new task ID for dependencies
                $task_id_map[$original_task->ID] = $new_task_id;
            }
            
            // Now set dependencies using the map
            foreach ($original_tasks as $original_task) {
                $depends_on = get_field('depends_on', $original_task->ID);
                
                if ($depends_on && isset($task_id_map[$depends_on]) && isset($task_id_map[$original_task->ID])) {
                    update_field('depends_on', $task_id_map[$depends_on], $task_id_map[$original_task->ID]);
                    
                    // Lock task since it has dependency
                    update_field('is_locked', 1, $task_id_map[$original_task->ID]);
                }
            }
            
            $created_workflows[] = array(
                'id' => $new_workflow_id,
                'title' => $new_workflow_data['post_title'],
                'start_date' => $formatted_date,
                'task_count' => count($original_tasks),
                'edit_link' => admin_url('post.php?post=' . $new_workflow_id . '&action=edit')
            );
        }
        
        wp_send_json_success(array(
            'message' => count($created_workflows) . ' workflows created successfully',
            'workflows' => $created_workflows
        ));
    }
    
    /**
     * Filter out future workflows from dashboard
     */
    public function filter_future_workflows($query) {
        // Only show workflows where start_date <= today
        $today = current_time('Y-m-d');
        
        $meta_query = $query->get('meta_query') ?: array();
        
        $meta_query[] = array(
            'key' => 'start_date',
            'value' => $today . ' 23:59:59',
            'compare' => '<=',
            'type' => 'DATETIME'
        );
        
        $query->set('meta_query', $meta_query);
    }
}

// Initialize
new WFS_Workflow_Clone();
