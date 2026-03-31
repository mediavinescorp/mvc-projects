<?php
/**
 * Dashboard Template Integration
 * Adds "Create from Template" functionality to the dashboard
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WFS_Dashboard_Template_Integration {
    
    public function __construct() {
        // Add AJAX handler for creating workflow from template
        add_action('wp_ajax_wfs_create_workflow_from_template', array($this, 'ajax_create_workflow_from_template'));
        
        // Add template selector to dashboard
        add_action('wfs_dashboard_create_task_modal', array($this, 'add_template_selector'));
        
        // Enqueue additional scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_template_scripts'));
    }
    
   /**
 * Enqueue template integration scripts
 */
public function enqueue_template_scripts() {
    // Only load on dashboard page
    if (!is_page('workflow-dashboard')) {
        return;
    }
    
    // Get plugin URL (go up from includes/ to main plugin folder)
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    
    wp_enqueue_style(
        'wfs-dashboard-templates',
        $plugin_url . 'assets/css/dashboard-templates.css',
        array(),
        '1.0.1'
    );
    
    wp_enqueue_script(
        'wfs-dashboard-templates',
        $plugin_url . 'assets/js/dashboard-templates.js',
        array('jquery'),
        '1.0.1',
        true
    );
    
    wp_localize_script('wfs-dashboard-templates', 'wfsDashboardTemplates', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wfs_dashboard_templates_nonce')
    ));
}
    
    /**
     * Add template selector to create task modal
     */
    public function add_template_selector() {
        // Get all active templates
        $templates = $this->get_active_templates();
        
        if (empty($templates)) {
            return;
        }
        
        ?>
        <!-- Template Selection Section -->
        <div class="create-task-section template-selection-section">
            <div class="section-header">
                <h3>Or Create from Template</h3>
                <p class="section-description">Select a pre-configured workflow template to create multiple tasks at once</p>
            </div>
            
            <div class="template-grid">
                <?php foreach ($templates as $template): 
                    $task_count = is_array($template['tasks']) ? count($template['tasks']) : 0;
                    $execution_label = $this->get_execution_mode_label($template['execution_mode']);
                ?>
                    <div class="template-card" data-template-id="<?php echo esc_attr($template['id']); ?>">
                        <div class="template-card-header">
                            <span class="template-icon">
                                <span class="dashicons dashicons-category"></span>
                            </span>
                            <span class="template-category-badge"><?php echo esc_html($template['category']); ?></span>
                        </div>
                        
                        <div class="template-card-body">
                            <h4><?php echo esc_html($template['name']); ?></h4>
                            <?php if (!empty($template['description'])): ?>
                                <p class="template-description"><?php echo esc_html($template['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="template-meta">
                                <span class="template-meta-item">
                                    <span class="dashicons dashicons-list-view"></span>
                                    <?php echo $task_count; ?> tasks
                                </span>
                                <span class="template-meta-item">
                                    <span class="dashicons dashicons-networking"></span>
                                    <?php echo $execution_label; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="template-card-footer">
                            <button type="button" class="button button-secondary view-template-tasks" 
                                    data-template-id="<?php echo esc_attr($template['id']); ?>">
                                View Tasks
                            </button>
                            <button type="button" class="button button-primary use-template" 
                                    data-template-id="<?php echo esc_attr($template['id']); ?>">
                                Use Template
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Template Preview Modal -->
        <div id="template-preview-modal" class="wfs-modal" style="display: none;">
            <div class="wfs-modal-overlay"></div>
            <div class="wfs-modal-content template-preview-content">
                <div class="wfs-modal-header">
                    <h2 id="template-preview-title">Template Preview</h2>
                    <button class="wfs-modal-close">&times;</button>
                </div>
                
                <div class="wfs-modal-body">
                    <div id="template-preview-details">
                        <div class="preview-section">
                            <h3>Description</h3>
                            <p id="template-preview-description"></p>
                        </div>
                        
                        <div class="preview-section">
                            <h3>Execution Mode</h3>
                            <div id="template-preview-execution"></div>
                        </div>
                        
                        <div class="preview-section">
                            <h3>Tasks</h3>
                            <div id="template-preview-tasks" class="preview-tasks-list">
                                <!-- Tasks will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="wfs-modal-footer">
                    <button type="button" class="button button-secondary close-preview">Close</button>
                    <button type="button" class="button button-primary use-this-template">Use This Template</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get all active templates
     */
    private function get_active_templates() {
        $templates = get_posts(array(
            'post_type' => 'wfs_workflow',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_is_template',
                    'value' => '1'
                )
            ),
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $templates_data = array();
        
        foreach ($templates as $template) {
            $templates_data[] = array(
                'id' => $template->ID,
                'name' => $template->post_title,
                'description' => $template->post_content,
                'category' => get_post_meta($template->ID, '_template_category', true) ?: 'standard',
                'execution_mode' => get_post_meta($template->ID, '_execution_mode', true) ?: 'sequential',
                'tasks' => get_post_meta($template->ID, '_template_tasks', true) ?: array(),
                'usage_count' => get_post_meta($template->ID, '_template_usage_count', true) ?: 0
            );
        }
        
        return $templates_data;
    }
    
    /**
     * Get execution mode label
     */
    private function get_execution_mode_label($mode) {
        $labels = array(
            'sequential' => 'Sequential',
            'parallel' => 'Parallel',
            'custom' => 'Custom'
        );
        
        return isset($labels[$mode]) ? $labels[$mode] : 'Sequential';
    }
    
    /**
     * AJAX: Create workflow from template
     */
    public function ajax_create_workflow_from_template() {
        check_ajax_referer('wfs_dashboard_templates_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in');
        }
        
        $template_id = intval($_POST['template_id']);
        $client_id = intval($_POST['client_id']);
        $workflow_name = sanitize_text_field($_POST['workflow_name']);
        
        if (!$template_id || !$client_id) {
            wp_send_json_error('Missing required fields');
        }
        
        // Get template
        $template = get_post($template_id);
        
        if (!$template || $template->post_type !== 'wfs_workflow') {
            wp_send_json_error('Invalid template');
        }
        
        $template_tasks = get_post_meta($template_id, '_template_tasks', true);
        $execution_mode = get_post_meta($template_id, '_execution_mode', true) ?: 'sequential';
        
        if (empty($template_tasks)) {
            wp_send_json_error('Template has no tasks');
        }
        
        // Create workflow
        $workflow_data = array(
            'post_title' => $workflow_name ?: $template->post_title,
            'post_type' => 'wfs_workflow',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        );
        
        $workflow_id = wp_insert_post($workflow_data);
        
        if (is_wp_error($workflow_id)) {
            wp_send_json_error('Failed to create workflow: ' . $workflow_id->get_error_message());
        }
        
        // Set workflow fields
        update_field('workflow_client', $client_id, $workflow_id);
        update_field('workflow_status', 'active', $workflow_id);
        update_field('start_date', current_time('Y-m-d H:i:s'), $workflow_id);
        update_field('source_template', $template_id, $workflow_id);
        
        // Create tasks from template
        $created_tasks = array();
        $task_id_map = array(); // Map template task index to actual task ID
        
        foreach ($template_tasks as $index => $task_data) {
            $task_post_data = array(
                'post_title' => $task_data['title'],
                'post_content' => $task_data['description'] ?: '',
                'post_type' => 'wfs_task',
                'post_status' => 'publish',
                'post_author' => get_current_user_id()
            );
            
            $task_id = wp_insert_post($task_post_data);
            
            if (!is_wp_error($task_id)) {
                // Set task fields
                update_field('task_workflow', $workflow_id, $task_id);
                update_field('task_status', $task_data['status'] ?: 'to-do', $task_id);
                update_field('task_priority', $task_data['priority'] ?: 'medium', $task_id);
                update_field('task_order', $index, $task_id);
                
                // Store mapping for dependency resolution
                $task_id_map[$index] = $task_id;
                
                $created_tasks[] = array(
                    'id' => $task_id,
                    'title' => $task_data['title'],
                    'index' => $index
                );
            }
        }
        
        // Now set dependencies based on execution mode and task mappings
        foreach ($template_tasks as $index => $task_data) {
            if (!isset($task_id_map[$index])) {
                continue;
            }
            
            $task_id = $task_id_map[$index];
            $depends_on_index = null;
            
            if ($execution_mode === 'sequential' && $index > 0) {
                // Sequential: each task depends on previous
                $depends_on_index = $index - 1;
            } elseif ($execution_mode === 'custom' && isset($task_data['depends_on']) && $task_data['depends_on'] !== '') {
                // Custom: use specified dependency
                $depends_on_index = intval($task_data['depends_on']);
            }
            
            // Set dependency if valid
            if ($depends_on_index !== null && isset($task_id_map[$depends_on_index])) {
                update_field('depends_on', $task_id_map[$depends_on_index], $task_id);
                
                // Lock task if dependency is not complete
                $dependency_status = get_field('task_status', $task_id_map[$depends_on_index]);
                $is_locked = !in_array($dependency_status, array('complete', 'approved'));
                update_field('is_locked', $is_locked, $task_id);
            }
        }
        
        // Increment template usage count
        $usage_count = get_post_meta($template_id, '_template_usage_count', true) ?: 0;
        update_post_meta($template_id, '_template_usage_count', $usage_count + 1);
        
        wp_send_json_success(array(
            'message' => 'Workflow created successfully from template',
            'workflow_id' => $workflow_id,
            'task_count' => count($created_tasks),
            'tasks' => $created_tasks
        ));
    }
}

// Initialize
new WFS_Dashboard_Template_Integration();
