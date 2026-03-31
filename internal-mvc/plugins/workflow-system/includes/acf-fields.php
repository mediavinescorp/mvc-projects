<?php
/**
 * ACF Fields
 * Registers Advanced Custom Fields for Phase 1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WFS_ACF_Fields {
    
    /**
     * Register ACF fields
     */
    public static function register() {
        // Tell ACF where to load JSON files from
        add_filter('acf/settings/load_json', array(__CLASS__, 'json_load_point'));
        
        if (function_exists('acf_add_local_field_group')) {
            self::register_client_fields();
            self::register_workflow_fields();
            self::register_task_fields();
        }
    }
    
    /**
     * Tell ACF to load JSON from our plugin directory
     */
    public static function json_load_point($paths) {
        // Remove original path
        unset($paths[0]);
        
        // Append our path
        $paths[] = WFS_PLUGIN_DIR . 'acf-json';
        
        return $paths;
    }
    
    /**
     * Register Client fields
     */
    private static function register_client_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_wfs_client',
            'title' => 'Client Information',
            'fields' => array(
                array(
                    'key' => 'field_client_email',
                    'label' => 'Email Address',
                    'name' => 'client_email',
                    'type' => 'email',
                    'instructions' => 'Primary email for this client',
                ),
                array(
                    'key' => 'field_client_phone',
                    'label' => 'Phone Number',
                    'name' => 'client_phone',
                    'type' => 'text',
                    'instructions' => 'Primary phone number',
                ),
                array(
                    'key' => 'field_client_address',
                    'label' => 'Address',
                    'name' => 'client_address',
                    'type' => 'textarea',
                    'instructions' => 'Physical address',
                    'rows' => 3,
                ),
                array(
                    'key' => 'field_client_website',
                    'label' => 'Website URL',
                    'name' => 'client_website',
                    'type' => 'url',
                    'instructions' => 'Client website URL',
                ),
                array(
                    'key' => 'field_client_license',
                    'label' => 'License Number',
                    'name' => 'client_license',
                    'type' => 'text',
                    'instructions' => 'License or registration number',
                ),
                array(
                    'key' => 'field_client_notes',
                    'label' => 'Additional Notes',
                    'name' => 'client_notes',
                    'type' => 'textarea',
                    'instructions' => 'Any other important information about this client',
                    'rows' => 4,
                ),
                array(
                    'key' => 'field_client_active',
                    'label' => 'Active Client',
                    'name' => 'client_active',
                    'type' => 'true_false',
                    'default_value' => 1,
                    'ui' => 1,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'wfs_client',
                    ),
                ),
            ),
        ));
    }
    
    /**
     * Register Workflow fields
     */
    private static function register_workflow_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_wfs_workflow',
            'title' => 'Workflow Details',
            'fields' => array(
                array(
                    'key' => 'field_workflow_client',
                    'label' => 'Client',
                    'name' => 'workflow_client',
                    'type' => 'post_object',
                    'instructions' => 'Select the client for this workflow',
                    'required' => 1,
                    'post_type' => array('wfs_client'),
                    'return_format' => 'id',
                    'ui' => 1,
                ),
                array(
                    'key' => 'field_workflow_status',
                    'label' => 'Status',
                    'name' => 'workflow_status',
                    'type' => 'select',
                    'choices' => array(
                        'active' => 'Active',
                        'completed' => 'Completed',
                    ),
                    'default_value' => 'active',
                    'ui' => 1,
                    'return_format' => 'value',
                ),
                array(
                    'key' => 'field_workflow_start_date',
                    'label' => 'Start Date',
                    'name' => 'workflow_start_date',
                    'type' => 'date_time_picker',
                    'instructions' => 'Automatically set on creation',
                    'display_format' => 'F j, Y g:i a',
                    'return_format' => 'Y-m-d H:i:s',
                    'readonly' => 1,
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
        ));
    }
    
    /**
     * Register Task fields - Phase 1 basic fields
     */
    private static function register_task_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_wfs_task',
            'title' => 'Task Details',
            'fields' => array(
                array(
                    'key' => 'field_task_workflow',
                    'label' => 'Workflow',
                    'name' => 'task_workflow',
                    'type' => 'post_object',
                    'instructions' => 'Select the workflow this task belongs to',
                    'required' => 1,
                    'post_type' => array('wfs_workflow'),
                    'return_format' => 'id',
                    'ui' => 1,
                ),
                array(
                    'key' => 'field_task_assigned_user',
                    'label' => 'Assigned To',
                    'name' => 'task_assigned_user',
                    'type' => 'user',
                    'instructions' => 'Select the user assigned to this task',
                    'required' => 1,
                    'role' => array('wfs_team_member', 'wfs_supervisor', 'wfs_admin', 'administrator'),
                    'return_format' => 'id',
                    'ui' => 1,
                ),
                array(
                    'key' => 'field_task_status',
                    'label' => 'Status',
                    'name' => 'task_status',
                    'type' => 'select',
                    'choices' => array(
                        'assigned' => 'Assigned',
                        'in_progress' => 'In Progress',
                        'waiting' => 'Waiting',
                        'needs_info' => 'Needs Info',
                        'awaiting_external' => 'Awaiting External',
                        'needs_approval' => 'Needs Approval',
                        'completed' => 'Completed',
                    ),
                    'default_value' => 'assigned',
                    'ui' => 1,
                    'return_format' => 'value',
                ),
                array(
                    'key' => 'field_task_due_date',
                    'label' => 'Due Date',
                    'name' => 'task_due_date',
                    'type' => 'date_picker',
                    'instructions' => 'When should this task be completed?',
                    'display_format' => 'F j, Y',
                    'return_format' => 'Y-m-d',
                ),
                array(
                    'key' => 'field_task_start_date',
                    'label' => 'Start Date',
                    'name' => 'task_start_date',
                    'type' => 'date_time_picker',
                    'instructions' => 'Automatically set on creation',
                    'display_format' => 'F j, Y g:i a',
                    'return_format' => 'Y-m-d H:i:s',
                    'readonly' => 1,
                ),
                array(
                    'key' => 'field_task_notes',
                    'label' => 'Notes & Updates',
                    'name' => 'task_notes',
                    'type' => 'repeater',
                    'instructions' => 'Conversation thread for this task',
                    'layout' => 'block',
                    'button_label' => 'Add Note',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_note_content',
                            'label' => 'Note',
                            'name' => 'note_content',
                            'type' => 'textarea',
                            'rows' => 3,
                            'required' => 1,
                        ),
                        array(
                            'key' => 'field_note_user',
                            'label' => 'User',
                            'name' => 'note_user',
                            'type' => 'text',
                            'readonly' => 1,
                        ),
                        array(
                            'key' => 'field_note_timestamp',
                            'label' => 'Timestamp',
                            'name' => 'note_timestamp',
                            'type' => 'text',
                            'readonly' => 1,
                        ),
                    ),
                ),
                array(
                    'key' => 'field_task_activity_log',
                    'label' => 'Activity Log',
                    'name' => 'task_activity_log',
                    'type' => 'textarea',
                    'instructions' => 'Automatically tracks all task changes',
                    'readonly' => 1,
                    'rows' => 6,
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
        ));
    }
}

/**
 * Auto-populate start dates on post creation
 */
add_action('acf/save_post', function($post_id) {
    // Skip autosave and revisions
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    
    $post_type = get_post_type($post_id);
    
    // Set workflow start date
    if ($post_type === 'wfs_workflow') {
        $start_date = get_field('workflow_start_date', $post_id);
        if (empty($start_date)) {
            update_field('workflow_start_date', current_time('Y-m-d H:i:s'), $post_id);
        }
    }
    
    // Set task start date and initialize activity log
    if ($post_type === 'wfs_task') {
        $start_date = get_field('task_start_date', $post_id);
        if (empty($start_date)) {
            update_field('task_start_date', current_time('Y-m-d H:i:s'), $post_id);
            
            // Initialize activity log
            $user = wp_get_current_user();
            $log_entry = sprintf(
                "[%s] Created by %s\n",
                current_time('F j, Y g:i a'),
                $user->display_name
            );
            update_field('task_activity_log', $log_entry, $post_id);
        }
    }
}, 20);

/**
 * Log task status changes
 */
add_filter('acf/update_value/name=task_status', function($value, $post_id, $field) {
    $old_value = get_field('task_status', $post_id);
    
    // Only log if status actually changed
    if ($old_value && $old_value !== $value) {
        $current_log = get_field('task_activity_log', $post_id);
        $user = wp_get_current_user();
        
        $status_labels = array(
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'waiting' => 'Waiting',
            'needs_info' => 'Needs Info',
            'awaiting_external' => 'Awaiting External',
            'needs_approval' => 'Needs Approval',
            'completed' => 'Completed',
        );
        
        $log_entry = sprintf(
            "[%s] Status changed: %s → %s (by %s)\n",
            current_time('F j, Y g:i a'),
            $status_labels[$old_value] ?? $old_value,
            $status_labels[$value] ?? $value,
            $user->display_name
        );
        
        update_field('task_activity_log', $current_log . $log_entry, $post_id);
    }
    
    return $value;
}, 10, 3);

/**
 * Log task assignment changes
 */
add_filter('acf/update_value/name=task_assigned_user', function($value, $post_id, $field) {
    $old_value = get_field('task_assigned_user', $post_id);
    
    // Only log if assignment actually changed
    if ($old_value && $old_value !== $value) {
        $current_log = get_field('task_activity_log', $post_id);
        $current_user = wp_get_current_user();
        $old_user = get_userdata($old_value);
        $new_user = get_userdata($value);
        
        $log_entry = sprintf(
            "[%s] Reassigned: %s → %s (by %s)\n",
            current_time('F j, Y g:i a'),
            $old_user->display_name,
            $new_user->display_name,
            $current_user->display_name
        );
        
        update_field('task_activity_log', $current_log . $log_entry, $post_id);
    }
    
    return $value;
}, 10, 3);
