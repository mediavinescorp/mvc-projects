<?php
/**
 * Workflow Template Manager
 * Complete template management system for creating workflow templates with predefined tasks
 * Version: 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WFS_Template_Manager {
    
  public function __construct() {
      
    // Admin menu
    add_action('admin_menu', array($this, 'add_template_manager_menu'));
    
    // AJAX handlers
    add_action('wp_ajax_wfs_save_template', array($this, 'ajax_save_template'));
    add_action('wp_ajax_wfs_get_template', array($this, 'ajax_get_template'));
    add_action('wp_ajax_wfs_delete_template', array($this, 'ajax_delete_template'));
    add_action('wp_ajax_wfs_get_all_templates', array($this, 'ajax_get_all_templates'));
    add_action('wp_ajax_wfs_reorder_template_tasks', array($this, 'ajax_reorder_template_tasks'));
    
    // Enqueue scripts and styles
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
}
    
    /**
     * Add admin menu for template manager
     */
    public function add_template_manager_menu() {
        add_submenu_page(
            'edit.php?post_type=wfs_workflow',
            'Template Manager',
            'Template Manager',
            'manage_wfs_templates',
            'wfs-template-manager',
            array($this, 'render_template_manager_page')
        );
    }
    
   /**
 * Enqueue scripts and styles
 */
public function enqueue_scripts($hook) {
    // Only load on our template manager page
   // TEMPORARY DEBUG - See what the actual hook is
error_log('Template Manager Hook: ' . $hook);

if ($hook !== 'workflow_page_wfs-template-manager' && 
    $hook !== 'wfs_workflow_page_wfs-template-manager') {
        return;
    }
    
    // Get plugin URL (go up from includes/ to main plugin folder)
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    
    wp_enqueue_style(
        'wfs-template-manager',
        $plugin_url . 'assets/css/template-manager.css',
        array(),
        '1.0.1'
    );
    
    wp_enqueue_script('jquery-ui-sortable');
    
    wp_enqueue_script(
        'wfs-template-manager',
        $plugin_url . 'assets/js/template-manager.js',
        array('jquery', 'jquery-ui-sortable'),
        '1.0.1',
        true
    );
    
    wp_localize_script('wfs-template-manager', 'wfsTemplateManager', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wfs_template_nonce')
    ));
}
    
    /**
     * Render template manager page
     */
    public function render_template_manager_page() {
        // Check permissions
        if (!current_user_can('manage_wfs_templates')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        ?>
        <div class="wrap wfs-template-manager-wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-category" style="color: #D4AF37;"></span>
                Workflow Template Manager
            </h1>
            <button class="page-title-action" id="create-new-template">Create New Template</button>
            <hr class="wp-header-end">
            
            <div class="wfs-template-manager-container">
                <!-- Template List Sidebar -->
                <div class="wfs-template-list-sidebar">
                    <div class="sidebar-header">
                        <h2>Templates</h2>
                        <div class="template-search">
                            <input type="text" id="template-search" placeholder="Search templates...">
                        </div>
                    </div>
                    
                    <div class="template-list" id="template-list">
                        <div class="loading-templates">
                            <span class="spinner is-active"></span>
                            <p>Loading templates...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Template Editor -->
                <div class="wfs-template-editor">
                    <div class="editor-welcome" id="editor-welcome">
                        <div class="welcome-icon">
                            <span class="dashicons dashicons-category"></span>
                        </div>
                        <h2>Workflow Template Manager</h2>
                        <p>Select a template to edit or create a new one</p>
                        <button class="button button-primary button-hero" id="welcome-create-template">
                            Create Your First Template
                        </button>
                    </div>
                    
                    <div class="editor-content" id="editor-content" style="display: none;">
                        <form id="template-form">
                            <input type="hidden" id="template-id" name="template_id">
                            
                            <!-- Template Basic Info -->
                            <div class="template-section">
                                <div class="section-header">
                                    <h2>Template Information</h2>
                                </div>
                                
                                <div class="form-field">
                                    <label for="template-name">Template Name *</label>
                                    <input type="text" id="template-name" name="template_name" required 
                                           placeholder="e.g., New Client Onboarding">
                                    <p class="description">Give this template a descriptive name</p>
                                </div>
                                
                                <div class="form-field">
                                    <label for="template-description">Description</label>
                                    <textarea id="template-description" name="template_description" rows="3" 
                                              placeholder="Describe when to use this template"></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-field">
                                        <label for="template-category">Category *</label>
                                        <select id="template-category" name="template_category" required>
                                            <option value="">-- Select Category --</option>
                                            <option value="standard">Standard</option>
                                            <option value="custom">Custom</option>
                                            <option value="client-specific">Client-Specific</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-field">
                                        <label>
                                            <input type="checkbox" id="template-active" name="template_active" checked>
                                            Active Template
                                        </label>
                                        <p class="description">Inactive templates won't appear in workflow creation</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Task Execution Settings -->
                            <div class="template-section">
                                <div class="section-header">
                                    <h2>Task Execution Mode</h2>
                                </div>
                                
                                <div class="execution-mode-selector">
                                    <label class="execution-mode-option">
                                        <input type="radio" name="execution_mode" value="sequential" checked>
                                        <div class="mode-card">
                                            <div class="mode-icon">
                                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                                            </div>
                                            <h3>Sequential</h3>
                                            <p>Tasks must be completed in order. Each task is locked until the previous one is done.</p>
                                        </div>
                                    </label>
                                    
                                    <label class="execution-mode-option">
                                        <input type="radio" name="execution_mode" value="parallel">
                                        <div class="mode-card">
                                            <div class="mode-icon">
                                                <span class="dashicons dashicons-editor-justify"></span>
                                            </div>
                                            <h3>Parallel</h3>
                                            <p>All tasks can be worked on simultaneously. No dependencies enforced.</p>
                                        </div>
                                    </label>
                                    
                                    <label class="execution-mode-option">
                                        <input type="radio" name="execution_mode" value="custom">
                                        <div class="mode-card">
                                            <div class="mode-icon">
                                                <span class="dashicons dashicons-networking"></span>
                                            </div>
                                            <h3>Custom Dependencies</h3>
                                            <p>Set specific dependencies for each task. Mix sequential and parallel execution.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Template Tasks -->
                            <div class="template-section">
                                <div class="section-header">
                                    <h2>Template Tasks</h2>
                                    <button type="button" class="button button-primary" id="add-template-task">
                                        <span class="dashicons dashicons-plus-alt"></span> Add Task
                                    </button>
                                </div>
                                
                                <div id="template-tasks-container" class="tasks-container">
                                    <div class="no-tasks-message">
                                        <span class="dashicons dashicons-info"></span>
                                        <p>No tasks added yet. Click "Add Task" to create your first task.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" class="button button-primary button-large" id="save-template">
                                    Save Template
                                </button>
                                <button type="button" class="button button-secondary button-large" id="cancel-edit">
                                    Cancel
                                </button>
                                <button type="button" class="button button-link-delete" id="delete-template" style="margin-left: auto;">
                                    Delete Template
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Task Item Template (Hidden) -->
        <script type="text/template" id="task-item-template">
            <div class="task-item" data-task-index="{{index}}">
                <div class="task-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
                
                <div class="task-number">{{number}}</div>
                
                <div class="task-content">
                    <div class="task-header">
                        <input type="text" class="task-title" name="tasks[{{index}}][title]" 
                               placeholder="Task title" value="{{title}}" required>
                        <button type="button" class="task-remove" data-task-index="{{index}}">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    
                    <div class="task-details">
                        <textarea class="task-description" name="tasks[{{index}}][description]" 
                                  placeholder="Task description (optional)" rows="2">{{description}}</textarea>
                        
                        <div class="task-meta">
                            <div class="task-meta-field dependency-field" style="display: none;">
                                <label>Depends On:</label>
                                <select class="task-dependency" name="tasks[{{index}}][depends_on]">
                                    <option value="">-- No Dependency --</option>
                                </select>
                            </div>
                            
                            <div class="task-meta-field">
                                <label>Default Status:</label>
                                <select class="task-status" name="tasks[{{index}}][status]">
                                    <option value="to-do">To Do</option>
                                    <option value="assigned">Assigned</option>
                                </select>
                            </div>
                            
                            <div class="task-meta-field">
                                <label>Priority:</label>
                                <select class="task-priority" name="tasks[{{index}}][priority]">
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </script>
        <?php
    }
    
    /**
     * AJAX: Save template
     */
    public function ajax_save_template() {
        check_ajax_referer('wfs_template_nonce', 'nonce');
        
        if (!current_user_can('manage_wfs_templates')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $template_data = array(
            'name' => sanitize_text_field($_POST['template_name']),
            'description' => sanitize_textarea_field($_POST['template_description']),
            'category' => sanitize_text_field($_POST['template_category']),
            'active' => isset($_POST['template_active']) ? 1 : 0,
            'execution_mode' => sanitize_text_field($_POST['execution_mode']),
            'tasks' => array()
        );
        
        // Process tasks
        if (isset($_POST['tasks']) && is_array($_POST['tasks'])) {
            foreach ($_POST['tasks'] as $index => $task) {
                $template_data['tasks'][] = array(
                    'title' => sanitize_text_field($task['title']),
                    'description' => sanitize_textarea_field($task['description']),
                    'depends_on' => intval($task['depends_on']),
                    'status' => sanitize_text_field($task['status']),
                    'priority' => sanitize_text_field($task['priority']),
                    'order' => $index
                );
            }
        }
        
        if ($template_id > 0) {
            // Update existing template
            $post_data = array(
                'ID' => $template_id,
                'post_title' => $template_data['name'],
                'post_content' => $template_data['description'],
                'post_status' => $template_data['active'] ? 'publish' : 'draft'
            );
            
            $result = wp_update_post($post_data);
        } else {
            // Create new template
            $post_data = array(
                'post_title' => $template_data['name'],
                'post_content' => $template_data['description'],
                'post_status' => $template_data['active'] ? 'publish' : 'draft',
                'post_type' => 'wfs_workflow',
                'post_author' => get_current_user_id()
            );
            
            $result = wp_insert_post($post_data);
            $template_id = $result;
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Save template metadata
        update_post_meta($template_id, '_is_template', '1');
        update_post_meta($template_id, '_template_category', $template_data['category']);
        update_post_meta($template_id, '_execution_mode', $template_data['execution_mode']);
        update_post_meta($template_id, '_template_tasks', $template_data['tasks']);
        update_post_meta($template_id, '_template_usage_count', get_post_meta($template_id, '_template_usage_count', true) ?: 0);
        
        wp_send_json_success(array(
            'message' => 'Template saved successfully',
            'template_id' => $template_id
        ));
    }
    
    /**
     * AJAX: Get template
     */
    public function ajax_get_template() {
        check_ajax_referer('wfs_template_nonce', 'nonce');
        
        if (!current_user_can('manage_wfs_templates')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template_id = intval($_POST['template_id']);
        $template = get_post($template_id);
        
        if (!$template || $template->post_type !== 'wfs_workflow') {
            wp_send_json_error('Template not found');
        }
        
        $template_data = array(
            'id' => $template->ID,
            'name' => $template->post_title,
            'description' => $template->post_content,
            'category' => get_post_meta($template->ID, '_template_category', true),
            'active' => $template->post_status === 'publish',
            'execution_mode' => get_post_meta($template->ID, '_execution_mode', true) ?: 'sequential',
            'tasks' => get_post_meta($template->ID, '_template_tasks', true) ?: array(),
            'usage_count' => get_post_meta($template->ID, '_template_usage_count', true) ?: 0
        );
        
        wp_send_json_success($template_data);
    }
    
    /**
     * AJAX: Delete template
     */
    public function ajax_delete_template() {
        check_ajax_referer('wfs_template_nonce', 'nonce');
        
        if (!current_user_can('manage_wfs_templates')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template_id = intval($_POST['template_id']);
        
        $result = wp_delete_post($template_id, true);
        
        if (!$result) {
            wp_send_json_error('Failed to delete template');
        }
        
        wp_send_json_success('Template deleted successfully');
    }
    
    /**
     * AJAX: Get all templates
     */
    public function ajax_get_all_templates() {
        check_ajax_referer('wfs_template_nonce', 'nonce');
        
        if (!current_user_can('manage_wfs_templates')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $templates = get_posts(array(
            'post_type' => 'wfs_workflow',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft'),
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
            $tasks = get_post_meta($template->ID, '_template_tasks', true) ?: array();
            $templates_data[] = array(
                'id' => $template->ID,
                'name' => $template->post_title,
                'description' => $template->post_content,
                'category' => get_post_meta($template->ID, '_template_category', true),
                'active' => $template->post_status === 'publish',
                'execution_mode' => get_post_meta($template->ID, '_execution_mode', true) ?: 'sequential',
                'task_count' => count($tasks),
                'usage_count' => get_post_meta($template->ID, '_template_usage_count', true) ?: 0
            );
        }
        
        wp_send_json_success($templates_data);
    }
    
    /**
     * AJAX: Reorder template tasks
     */
    public function ajax_reorder_template_tasks() {
        check_ajax_referer('wfs_template_nonce', 'nonce');
        
        if (!current_user_can('manage_wfs_templates')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template_id = intval($_POST['template_id']);
        $task_order = $_POST['task_order'];
        
        $tasks = get_post_meta($template_id, '_template_tasks', true);
        
        if (!$tasks) {
            wp_send_json_error('No tasks found');
        }
        
        // Reorder tasks
        $reordered_tasks = array();
        foreach ($task_order as $index) {
            if (isset($tasks[$index])) {
                $reordered_tasks[] = $tasks[$index];
            }
        }
        
        update_post_meta($template_id, '_template_tasks', $reordered_tasks);
        
        wp_send_json_success('Tasks reordered successfully');
    }
}

// Initialize
new WFS_Template_Manager();
