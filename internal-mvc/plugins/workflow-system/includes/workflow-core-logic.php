<?php
/**
 * Phase 2A - Workflow Core Logic
 * Handles workflow automation, validation, and lifecycle
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WFS_Workflow_Core {
    
    public function __construct() {
        // Validation hooks
        add_action('save_post_wfs_workflow', array($this, 'validate_workflow'), 10, 2);
        
        // Cancellation hooks
        add_action('admin_action_cancel_workflow', array($this, 'handle_cancel_workflow'));
        
        // Reopen hooks
        add_action('admin_action_reopen_workflow', array($this, 'handle_reopen_workflow'));
        
        // Add admin actions to workflow edit screen
        add_action('post_submitbox_misc_actions', array($this, 'add_workflow_actions'));
    }
    
/**
 * Validate workflow on save (admin backend only)
 * Active non-template workflows must have at least 1 task
 */
public function validate_workflow($post_id, $post) {
    // Only run in admin backend
    if (!is_admin()) {
        return;
    }
    
    // Skip for autosave/revision
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    
    // Get the workflow status being saved
    $workflow_status = isset($_POST['acf']) && isset($_POST['acf']['field_workflow_status']) 
        ? $_POST['acf']['field_workflow_status'] 
        : get_field('workflow_status', $post_id);
    
    // Only validate when trying to publish as active
    if ($workflow_status !== 'active') {
        return; // Draft/other statuses are fine without tasks
    }
    
    // Check if it's a template
    $is_template = get_post_meta($post_id, '_is_template', true);
    if ($is_template === '1') {
        return; // Templates can exist without tasks
    }
    
    // Check if workflow has at least 1 task
    $tasks = get_posts(array(
        'post_type' => 'wfs_task',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'workflow',
                'value' => $post_id,
            ),
        ),
    ));
    
    if (empty($tasks)) {
        // UNHOOK to prevent infinite loop
        remove_action('save_post_wfs_workflow', array($this, 'validate_workflow'), 10);
        
        // Force back to draft
        update_field('workflow_status', 'draft', $post_id);
        
        // Set admin notice
        set_transient('wfs_admin_notice_' . get_current_user_id(), array(
            'type' => 'error',
            'message' => 'Workflow must have at least one task before being set to Active. Saved as Draft instead.'
        ), 30);
        
        // RE-HOOK
        add_action('save_post_wfs_workflow', array($this, 'validate_workflow'), 10, 2);
    }
}    
    /**
     * Show validation errors
     */
    public function show_validation_errors() {
        global $post;
        if (!$post || $post->post_type !== 'wfs_workflow') {
            return;
        }
        
        $error = get_transient('wfs_workflow_error_' . $post->ID);
        if ($error) {
            echo '<div class="notice notice-error"><p><strong>Error:</strong> ' . esc_html($error) . '</p></div>';
            delete_transient('wfs_workflow_error_' . $post->ID);
        }
    }
    
    /**
     * Add cancel/reopen/duplicate actions to workflow edit screen
     * CONSOLIDATED - All workflow actions in one place
     */
    public function add_workflow_actions() {
        global $post;
        if (!$post || $post->post_type !== 'wfs_workflow') {
            return;
        }
        
        $status = get_field('workflow_status', $post->ID);
        $is_template = get_post_meta($post->ID, '_is_template', true);
        $source_template = get_field('source_template', $post->ID);
        
        ?>
        <div class="misc-pub-section misc-pub-workflow-actions">
            <?php if ($is_template === '1'): ?>
                <!-- Template Workflows -->
                <div style="text-align: center; color: #D4AF37; font-weight: bold; padding: 5px; margin: 5px 0;">
                    ⭐ Template Workflow
                </div>
                
            <?php else: ?>
                <!-- Regular Workflows -->
                
                <?php if ($status === 'active'): ?>
                    <!-- Active Workflow: Show Cancel button -->
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?action=cancel_workflow&post=' . $post->ID), 'cancel_workflow_' . $post->ID); ?>" 
                       class="button button-secondary" 
                       style="width: 100%; text-align: center; margin-top: 5px; background: #dc3232; color: white;"
                       onclick="return confirm('Are you sure you want to cancel this workflow?');">
                        ❌ Cancel Workflow
                    </a>
                    
                <?php elseif (in_array($status, array('complete', 'cancelled'))): ?>
                    <?php if ($source_template): ?>
                        <!-- Template-based workflow: Cannot reopen -->
                        <p style="color: #999; font-size: 12px; margin: 5px 0; text-align: center;">
                            ⚠️ Template-based workflows cannot be reopened
                        </p>
                    <?php else: ?>
                        <!-- Custom workflow: Can reopen -->
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?action=reopen_workflow&post=' . $post->ID), 'reopen_workflow_' . $post->ID); ?>" 
                           class="button button-secondary" 
                           style="width: 100%; text-align: center; margin-top: 5px;">
                            🔄 Reopen Workflow
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Duplicate as Template button (for non-template workflows) -->
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?action=duplicate_as_template&post=' . $post->ID), 'duplicate_template_' . $post->ID); ?>" 
                   class="button button-secondary" 
                   style="width: 100%; text-align: center; margin-top: 5px;">
                    📋 Duplicate as Template
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Handle cancel workflow action
     */
    public function handle_cancel_workflow() {
        if (!isset($_GET['post']) || !isset($_GET['_wpnonce'])) {
            wp_die('Invalid request');
        }
        
        $post_id = intval($_GET['post']);
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'cancel_workflow_' . $post_id)) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Permission denied');
        }
        
        // Show cancellation form
        $this->show_cancel_form($post_id);
    }
    
    /**
     * Show cancellation form
     */
    private function show_cancel_form($post_id) {
        $post = get_post($post_id);
        
        // Handle form submission
        if (isset($_POST['confirm_cancel']) && wp_verify_nonce($_POST['cancel_nonce'], 'cancel_workflow_' . $post_id)) {
            $reason = sanitize_textarea_field($_POST['cancellation_reason']);
            
            if (empty($reason)) {
                $error = 'Cancellation reason is required.';
            } else {
                // Update workflow
                update_field('workflow_status', 'cancelled', $post_id);
                update_field('cancellation_reason', $reason, $post_id);
                update_field('cancelled_by', get_current_user_id(), $post_id);
                update_field('cancelled_date', current_time('Y-m-d H:i:s'), $post_id);
                
                // Cancel all tasks in this workflow
                $this->cancel_workflow_tasks($post_id);
                
                // Redirect back
                wp_redirect(admin_url('post.php?action=edit&post=' . $post_id . '&cancelled=1'));
                exit;
            }
        }
        
        // Show form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cancel Workflow</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; padding: 40px; background: #f0f0f1; }
                .cancel-form { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                h1 { color: #dc3232; margin-top: 0; }
                label { display: block; margin: 20px 0 8px 0; font-weight: 600; }
                textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical; }
                .buttons { margin-top: 20px; display: flex; gap: 10px; }
                button, .button { padding: 10px 20px; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
                .cancel-button { background: #dc3232; color: white; }
                .back-button { background: #f0f0f0; color: #333; }
                .error { background: #ffebe9; border-left: 4px solid #dc3232; padding: 12px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="cancel-form">
                <h1>Cancel Workflow</h1>
                <p><strong><?php echo esc_html($post->post_title); ?></strong></p>
                
                <?php if (isset($error)): ?>
                <div class="error"><?php echo esc_html($error); ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <?php wp_nonce_field('cancel_workflow_' . $post_id, 'cancel_nonce'); ?>
                    
                    <label for="cancellation_reason">Cancellation Reason (Required)</label>
                    <textarea name="cancellation_reason" id="cancellation_reason" rows="5" required><?php echo isset($_POST['cancellation_reason']) ? esc_textarea($_POST['cancellation_reason']) : ''; ?></textarea>
                    
                    <div class="buttons">
                        <button type="submit" name="confirm_cancel" class="cancel-button">Confirm Cancellation</button>
                        <a href="<?php echo admin_url('post.php?action=edit&post=' . $post_id); ?>" class="button back-button">Go Back</a>
                    </div>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Cancel all tasks in workflow
     */
    private function cancel_workflow_tasks($workflow_id) {
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
        
        foreach ($tasks as $task_id) {
            update_field('status', 'cancelled', $task_id);
        }
    }
    
    /**
     * Handle reopen workflow action
     */
    public function handle_reopen_workflow() {
        if (!isset($_GET['post']) || !isset($_GET['_wpnonce'])) {
            wp_die('Invalid request');
        }
        
        $post_id = intval($_GET['post']);
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'reopen_workflow_' . $post_id)) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Permission denied');
        }
        
        // Check if it's a custom workflow (not from template)
        $source_template = get_field('source_template', $post_id);
        if ($source_template) {
            wp_die('Template-based workflows cannot be reopened. Please create a new workflow from the template.');
        }
        
        // Show reopen form
        $this->show_reopen_form($post_id);
    }
    
    /**
     * Show reopen form
     */
    private function show_reopen_form($post_id) {
        $post = get_post($post_id);
        
        // Handle form submission
        if (isset($_POST['confirm_reopen']) && wp_verify_nonce($_POST['reopen_nonce'], 'reopen_workflow_' . $post_id)) {
            $reason = sanitize_textarea_field($_POST['reopened_reason']);
            
            if (empty($reason)) {
                $error = 'Reason for reopening is required.';
            } else {
                // Update workflow
                update_field('workflow_status', 'active', $post_id);
                update_field('reopened_reason', $reason, $post_id);
                update_field('reopened_by', get_current_user_id(), $post_id);
                update_field('reopened_date', current_time('Y-m-d H:i:s'), $post_id);
                
                // Clear completion data
                delete_field('completion_date', $post_id);
                delete_field('total_days', $post_id);
                
                // Redirect back
                wp_redirect(admin_url('post.php?action=edit&post=' . $post_id . '&reopened=1'));
                exit;
            }
        }
        
        // Show form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Reopen Workflow</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; padding: 40px; background: #f0f0f1; }
                .reopen-form { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                h1 { color: #2271b1; margin-top: 0; }
                label { display: block; margin: 20px 0 8px 0; font-weight: 600; }
                textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical; }
                .buttons { margin-top: 20px; display: flex; gap: 10px; }
                button, .button { padding: 10px 20px; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
                .reopen-button { background: #2271b1; color: white; }
                .back-button { background: #f0f0f0; color: #333; }
                .error { background: #ffebe9; border-left: 4px solid #dc3232; padding: 12px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="reopen-form">
                <h1>Reopen Workflow</h1>
                <p><strong><?php echo esc_html($post->post_title); ?></strong></p>
                
                <?php if (isset($error)): ?>
                <div class="error"><?php echo esc_html($error); ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <?php wp_nonce_field('reopen_workflow_' . $post_id, 'reopen_nonce'); ?>
                    
                    <label for="reopened_reason">Reason for Reopening (Required)</label>
                    <textarea name="reopened_reason" id="reopened_reason" rows="5" required><?php echo isset($_POST['reopened_reason']) ? esc_textarea($_POST['reopened_reason']) : ''; ?></textarea>
                    
                    <div class="buttons">
                        <button type="submit" name="confirm_reopen" class="reopen-button">Confirm Reopen</button>
                        <a href="<?php echo admin_url('post.php?action=edit&post=' . $post_id); ?>" class="button back-button">Go Back</a>
                    </div>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Initialize - Create only ONE instance
$wfs_workflow_core = new WFS_Workflow_Core();

// Show validation errors - Use the SAME instance
add_action('admin_notices', array($wfs_workflow_core, 'show_validation_errors'));
