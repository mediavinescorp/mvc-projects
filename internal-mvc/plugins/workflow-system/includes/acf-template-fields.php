<?php
/**
 * ACF Fields for Template System
 * Additional fields needed for workflow template management
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Template System ACF Fields
 */
function wfs_register_template_system_fields() {
    
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    // ============================================
    // TEMPLATE METADATA FIELDS
    // ============================================
    
    acf_add_local_field_group(array(
        'key' => 'group_template_metadata',
        'title' => 'Template Metadata',
        'fields' => array(
            
            // Is Template Flag
            array(
                'key' => 'field_is_template',
                'label' => 'Is Template',
                'name' => '_is_template',
                'type' => 'true_false',
                'ui' => 1,
                'ui_on_text' => 'Yes',
                'ui_off_text' => 'No',
                'instructions' => 'Mark this workflow as a template',
            ),
            
            // Template Category
            array(
                'key' => 'field_template_category',
                'label' => 'Template Category',
                'name' => '_template_category',
                'type' => 'select',
                'choices' => array(
                    'standard' => 'Standard',
                    'custom' => 'Custom',
                    'client-specific' => 'Client-Specific',
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_is_template',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            
            // Execution Mode
            array(
                'key' => 'field_execution_mode',
                'label' => 'Execution Mode',
                'name' => '_execution_mode',
                'type' => 'select',
                'choices' => array(
                    'sequential' => 'Sequential',
                    'parallel' => 'Parallel',
                    'custom' => 'Custom Dependencies',
                ),
                'default_value' => 'sequential',
                'instructions' => 'How tasks should be executed',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_is_template',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            
            // Template Tasks (stored as serialized array)
            array(
                'key' => 'field_template_tasks',
                'label' => 'Template Tasks',
                'name' => '_template_tasks',
                'type' => 'textarea',
                'instructions' => 'Internal storage for template tasks (managed by Template Manager)',
                'readonly' => 1,
                'rows' => 3,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_is_template',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            
            // Template Usage Count
            array(
                'key' => 'field_template_usage_count',
                'label' => 'Usage Count',
                'name' => '_template_usage_count',
                'type' => 'number',
                'default_value' => 0,
                'readonly' => 1,
                'instructions' => 'Number of times this template has been used',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_is_template',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
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
        'menu_order' => 5,
        'position' => 'side',
        'style' => 'default',
    ));
    
    // ============================================
    // TASK TEMPLATE FIELDS (additions)
    // ============================================
    
    acf_add_local_field_group(array(
        'key' => 'group_task_template_fields',
        'title' => 'Task Template Fields',
        'fields' => array(
            
            // Task Priority
            array(
                'key' => 'field_task_priority',
                'label' => 'Priority',
                'name' => 'task_priority',
                'type' => 'select',
                'choices' => array(
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'critical' => 'Critical',
                ),
                'default_value' => 'medium',
            ),
            
            // Task Order (for sorting)
            array(
                'key' => 'field_task_order_number',
                'label' => 'Task Order',
                'name' => 'task_order',
                'type' => 'number',
                'default_value' => 0,
                'instructions' => 'Order in which tasks are displayed',
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
        'menu_order' => 10,
    ));
}

add_action('acf/init', 'wfs_register_template_system_fields', 20);

/**
 * Helper function to get template tasks
 */
function wfs_get_template_tasks($template_id) {
    $tasks = get_post_meta($template_id, '_template_tasks', true);
    
    if (!is_array($tasks)) {
        return array();
    }
    
    return $tasks;
}

/**
 * Helper function to check if workflow is a template
 */
function wfs_is_template($workflow_id) {
    return get_post_meta($workflow_id, '_is_template', true) === '1';
}

/**
 * Helper function to get template category
 */
function wfs_get_template_category($template_id) {
    $category = get_post_meta($template_id, '_template_category', true);
    return $category ?: 'standard';
}

/**
 * Helper function to get execution mode
 */
function wfs_get_execution_mode($template_id) {
    $mode = get_post_meta($template_id, '_execution_mode', true);
    return $mode ?: 'sequential';
}
