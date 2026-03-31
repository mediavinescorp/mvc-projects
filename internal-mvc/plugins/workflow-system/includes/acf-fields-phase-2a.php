<?php
/**
 * Phase 2A - ACF Field Definitions
 * Workflow and Task meta fields including dependencies
 * 
 * INSTALLATION: Add this to your existing acf-fields.php file
 * OR create as separate file and include it
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Phase 2A ACF Fields
 */
function wfs_register_phase_2a_fields() {
    
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    // ============================================
    // WORKFLOW FIELDS - Phase 2A Additions
    // ============================================
    
    acf_add_local_field_group(array(
        'key' => 'group_workflow_phase_2a',
        'title' => 'Workflow Status & Tracking',
        'fields' => array(
            
            // Workflow Status
            array(
                'key' => 'field_workflow_status',
                'label' => 'Workflow Status',
                'name' => 'workflow_status',
                'type' => 'select',
                'required' => 1,
                'default_value' => 'active',
                'choices' => array(
                    'active' => 'Active',
                    'complete' => 'Complete',
                    'cancelled' => 'Cancelled',
                ),
                'instructions' => 'Active workflows show in dashboards. Complete/Cancelled are archived.',
            ),
            
            // Start Date (auto-set on creation)
            array(
                'key' => 'field_workflow_start_date',
                'label' => 'Start Date',
                'name' => 'start_date',
                'type' => 'date_time_picker',
                'display_format' => 'M j, Y g:i a',
                'return_format' => 'Y-m-d H:i:s',
                'instructions' => 'Automatically set when workflow is created',
            ),
            
            // Completion Date (auto-set)
            array(
                'key' => 'field_workflow_completion_date',
                'label' => 'Completion Date',
                'name' => 'completion_date',
                'type' => 'date_time_picker',
                'display_format' => 'M j, Y g:i a',
                'return_format' => 'Y-m-d H:i:s',
                'instructions' => 'Automatically set when last task completes',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_workflow_status',
                            'operator' => '==',
                            'value' => 'complete',
                        ),
                    ),
                ),
            ),
            
            // Total Days (calculated)
            array(
                'key' => 'field_workflow_total_days',
                'label' => 'Total Days',
                'name' => 'total_days',
                'type' => 'number',
                'readonly' => 1,
                'instructions' => 'Calculated: Completion Date - Start Date',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_workflow_status',
                            'operator' => '==',
                            'value' => 'complete',
                        ),
                    ),
                ),
            ),
            
            // Source Template (tracks if created from template)
            array(
                'key' => 'field_workflow_source_template',
                'label' => 'Source Template',
                'name' => 'source_template',
                'type' => 'post_object',
                'post_type' => array('wfs_workflow'),
                'allow_null' => 1,
                'instructions' => 'If created from template, links to original template',
            ),
            
            // Cancellation Reason
            array(
                'key' => 'field_workflow_cancellation_reason',
                'label' => 'Cancellation Reason',
                'name' => 'cancellation_reason',
                'type' => 'textarea',
                'required' => 1,
                'rows' => 3,
                'instructions' => 'Required: Explain why this workflow was cancelled',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_workflow_status',
                            'operator' => '==',
                            'value' => 'cancelled',
                        ),
                    ),
                ),
            ),
            
            // Cancelled By
            array(
                'key' => 'field_workflow_cancelled_by',
                'label' => 'Cancelled By',
                'name' => 'cancelled_by',
                'type' => 'user',
                'return_format' => 'id',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_workflow_status',
                            'operator' => '==',
                            'value' => 'cancelled',
                        ),
                    ),
                ),
            ),
            
            // Cancelled Date
            array(
                'key' => 'field_workflow_cancelled_date',
                'label' => 'Cancelled Date',
                'name' => 'cancelled_date',
                'type' => 'date_time_picker',
                'display_format' => 'M j, Y g:i a',
                'return_format' => 'Y-m-d H:i:s',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_workflow_status',
                            'operator' => '==',
                            'value' => 'cancelled',
                        ),
                    ),
                ),
            ),
            
            // Reopened Reason
            array(
                'key' => 'field_workflow_reopened_reason',
                'label' => 'Reopened Reason',
                'name' => 'reopened_reason',
                'type' => 'textarea',
                'rows' => 3,
                'instructions' => 'Why was this workflow reopened?',
            ),
            
            // Reopened By
            array(
                'key' => 'field_workflow_reopened_by',
                'label' => 'Reopened By',
                'name' => 'reopened_by',
                'type' => 'user',
                'return_format' => 'id',
            ),
            
            // Reopened Date
            array(
                'key' => 'field_workflow_reopened_date',
                'label' => 'Reopened Date',
                'name' => 'reopened_date',
                'type' => 'date_time_picker',
                'display_format' => 'M j, Y g:i a',
                'return_format' => 'Y-m-d H:i:s',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'wfs_workflow',
                ),
            ),
        ),
        'menu_order' => 1,
    ));
    
    // ============================================
    // TASK FIELDS - Phase 2A Additions
    // ============================================
    
    acf_add_local_field_group(array(
        'key' => 'group_task_phase_2a',
        'title' => 'Task Dependencies & Status',
        'fields' => array(
            
            // Task Status (enhanced)
            array(
                'key' => 'field_task_status_enhanced',
                'label' => 'Task Status',
                'name' => 'status',
                'type' => 'select',
                'required' => 1,
                'default_value' => 'to-do',
                'choices' => array(
                    'to-do' => 'To Do',
                    'in-progress' => 'In Progress',
                    'blocked' => 'Blocked',
                    'complete' => 'Complete',
                    'approved' => 'Approved',
                    'cancelled' => 'Cancelled',
                ),
            ),
            
            // Depends On (single task dependency)
            array(
                'key' => 'field_task_depends_on',
                'label' => 'Depends On',
                'name' => 'depends_on',
                'type' => 'post_object',
                'post_type' => array('wfs_task'),
                'allow_null' => 1,
                'return_format' => 'id',
                'instructions' => 'This task can only start after the selected task is completed.',
            ),
            
            // Is Locked (calculated field - for display)
            array(
                'key' => 'field_task_is_locked',
                'label' => 'Task Locked',
                'name' => 'is_locked',
                'type' => 'true_false',
                'ui' => 1,
                'ui_on_text' => 'Locked',
                'ui_off_text' => 'Unlocked',
                'instructions' => 'Locked tasks cannot be worked on until dependency completes',
            ),
            
            // Completed Date
            array(
                'key' => 'field_task_completed_date',
                'label' => 'Completed Date',
                'name' => 'completed_date',
                'type' => 'date_time_picker',
                'display_format' => 'M j, Y g:i a',
                'return_format' => 'Y-m-d H:i:s',
                'instructions' => 'Automatically set when status changes to Complete or Approved',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_task_status_enhanced',
                            'operator' => '==',
                            'value' => 'complete',
                        ),
                    ),
                    array(
                        array(
                            'field' => 'field_task_status_enhanced',
                            'operator' => '==',
                            'value' => 'approved',
                        ),
                    ),
                ),
            ),
            
            // Task Order (for sequential display)
            array(
                'key' => 'field_task_order',
                'label' => 'Task Order',
                'name' => 'task_order',
                'type' => 'number',
                'default_value' => 0,
                'instructions' => 'Order tasks are displayed (lower numbers first)',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'wfs_task',
                ),
            ),
        ),
        'menu_order' => 1,
    ));
}

add_action('acf/init', 'wfs_register_phase_2a_fields');

/**
 * Auto-set start date on workflow creation
 */
function wfs_auto_set_workflow_start_date($post_id) {
    // Only for new workflows
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    $post = get_post($post_id);
    if ($post->post_type !== 'wfs_workflow') {
        return;
    }
    
    // Set start date if not already set
    if (!get_field('start_date', $post_id)) {
        update_field('start_date', current_time('Y-m-d H:i:s'), $post_id);
    }
}
add_action('acf/save_post', 'wfs_auto_set_workflow_start_date', 20);

/**
 * Auto-set completed date when task status changes to complete/approved
 */
function wfs_auto_set_task_completed_date($post_id) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    $post = get_post($post_id);
    if ($post->post_type !== 'wfs_task') {
        return;
    }
    
    $status = get_field('status', $post_id);
    
    // If status is complete or approved, set completed date
    if (in_array($status, array('complete', 'approved'))) {
        if (!get_field('completed_date', $post_id)) {
            update_field('completed_date', current_time('Y-m-d H:i:s'), $post_id);
        }
        
        // Check if this task completion triggers workflow completion
        $workflow_id = get_field('workflow', $post_id);
        if ($workflow_id) {
            wfs_check_workflow_completion($workflow_id);
        }
        
        // Unlock dependent tasks
        wfs_unlock_dependent_tasks($post_id);
    }
}
add_action('acf/save_post', 'wfs_auto_set_task_completed_date', 20);

/**
 * Check if all tasks in workflow are complete
 * If yes, auto-complete the workflow
 */
function wfs_check_workflow_completion($workflow_id) {
    // Get all tasks for this workflow
    $tasks = get_posts(array(
        'post_type' => 'wfs_task',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'workflow',
                'value' => $workflow_id,
            ),
        ),
    ));
    
    if (empty($tasks)) {
        return;
    }
    
    // Check if all tasks are complete or approved
    $all_complete = true;
    $last_completion_date = null;
    
    foreach ($tasks as $task_id) {
        $status = get_field('status', $task_id);
        
        if (!in_array($status, array('complete', 'approved'))) {
            $all_complete = false;
            break;
        }
        
        // Track latest completion date
        $completed_date = get_field('completed_date', $task_id);
        if ($completed_date && (!$last_completion_date || strtotime($completed_date) > strtotime($last_completion_date))) {
            $last_completion_date = $completed_date;
        }
    }
    
    // If all complete, mark workflow as complete
    if ($all_complete) {
        update_field('workflow_status', 'complete', $workflow_id);
        update_field('completion_date', $last_completion_date ?: current_time('Y-m-d H:i:s'), $workflow_id);
        
        // Calculate total days
        $start_date = get_field('start_date', $workflow_id);
        if ($start_date && $last_completion_date) {
            $start = new DateTime($start_date);
            $end = new DateTime($last_completion_date);
            $total_days = $start->diff($end)->days;
            update_field('total_days', $total_days, $workflow_id);
        }
    }
}

/**
 * Unlock tasks that depend on this task
 */
function wfs_unlock_dependent_tasks($completed_task_id) {
    // Find tasks that depend on this task
    $dependent_tasks = get_posts(array(
        'post_type' => 'wfs_task',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'depends_on',
                'value' => $completed_task_id,
            ),
        ),
    ));
    
    foreach ($dependent_tasks as $task) {
        // Unlock the task
        update_field('is_locked', 0, $task->ID);
    }
}

/**
 * Check if task is locked based on dependency
 */
function wfs_is_task_locked($task_id) {
    $depends_on = get_field('depends_on', $task_id);
    
    if (!$depends_on) {
        return false; // No dependency = not locked
    }
    
    // Check if dependency is complete or approved
    $dependency_status = get_field('status', $depends_on);
    
    return !in_array($dependency_status, array('complete', 'approved'));
}
