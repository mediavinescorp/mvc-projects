<?php
/**
 * Dashboard Class - Phase 2A Part 3
 * Three-tab dashboard: MY TASKS, MONITORING, ARCHIVE
 * Updated with Priority Display
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WFS_Dashboard {
    
    public static function init() {
        add_shortcode('workflow_dashboard', array(__CLASS__, 'render_dashboard'));
        
        // AJAX handlers
        add_action('wp_ajax_wfs_update_task_status', array(__CLASS__, 'ajax_update_task_status'));
        add_action('wp_ajax_wfs_cancel_workflow', array(__CLASS__, 'ajax_cancel_workflow'));
        add_action('wp_ajax_wfs_reopen_workflow', array(__CLASS__, 'ajax_reopen_workflow'));
        add_action('wp_ajax_wfs_get_task_details', array(__CLASS__, 'ajax_get_task_details'));
        add_action('wp_ajax_wfs_create_workflow_from_template', array(__CLASS__, 'ajax_create_workflow_from_template'));
    // NEW - Add these two lines:
    add_action('wp_ajax_wfs_get_workflow_tasks', array(__CLASS__, 'ajax_get_workflow_tasks'));
    add_action('wp_ajax_wfs_create_new_task', array(__CLASS__, 'ajax_create_new_task'));
add_action('wp_ajax_wfs_get_client_workflows', array(__CLASS__, 'ajax_get_client_workflows'));

}
    
    public static function render_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to access the workflow dashboard.</p>';
        }
        
        $current_user = wp_get_current_user();
        $user_role = self::get_user_role();
        $is_admin = in_array($user_role, array('admin', 'supervisor'));
        
        ob_start();
        ?>
        <div class="wfs-dashboard-wrapper">
            <div class="wfs-dashboard-header">
                <h1>Workflow Dashboard</h1>
                <div class="wfs-user-info">
                    <span class="wfs-username"><?php echo esc_html($current_user->display_name); ?></span>
                    <span class="wfs-role-badge"><?php echo esc_html(ucfirst($user_role)); ?></span>
                </div>
            </div>
            
</div>

<!-- Create New Task Section -->
<div class="wfs-create-task-section">
    <button class="wfs-btn wfs-btn-primary" id="open-create-task-modal">
        ➕ Create New Task
    </button>
</div>

<!-- Mobile Dropdown (shows on mobile only) -->

         <!-- Mobile Dropdown (shows on mobile only) -->
<div class="mobile-tab-dropdown" style="display: none;">
    <select class="mobile-tab-select" id="mobile-tab-select">
        <option value="my-tasks" selected>MY TASKS</option>
        <option value="monitoring">MONITORING</option>
        <option value="archive">ARCHIVE</option>
    </select>
</div>

<!-- Tab Navigation (shows on desktop only) -->
<div class="wfs-tabs-nav">
    <button class="wfs-tab-button active" data-tab="my-tasks">
        <span class="tab-icon">✓</span>
        <span class="tab-label">MY TASKS</span>
    </button>
    <button class="wfs-tab-button" data-tab="monitoring">
        <span class="tab-icon">📊</span>
        <span class="tab-label">MONITORING</span>
    </button>
    <button class="wfs-tab-button" data-tab="archive">
        <span class="tab-icon">📁</span>
        <span class="tab-label">ARCHIVE</span>
    </button>
</div>

<script>
// Mobile dropdown tab switcher
(function() {
    const select = document.getElementById('mobile-tab-select');
    if (!select) return;
    
    const tabButtons = document.querySelectorAll('.wfs-tab-button');
    
    // When dropdown changes, click the corresponding tab
    select.addEventListener('change', function() {
        const selectedTab = this.value;
        tabButtons.forEach(button => {
            if (button.getAttribute('data-tab') === selectedTab) {
                button.click();
            }
        });
    });
    
    // When tab is clicked, update dropdown
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabValue = this.getAttribute('data-tab');
            select.value = tabValue;
        });
    });
})();
</script>
            
            <!-- Tab Content -->
            <div class="wfs-tabs-content">
                
                <!-- MY TASKS Tab -->
                <div class="wfs-tab-panel active" id="my-tasks-panel">
                    <?php self::render_my_tasks_tab($current_user->ID); ?>
                </div>
                
                <!-- MONITORING Tab -->
                <div class="wfs-tab-panel" id="monitoring-panel">
                    <?php self::render_monitoring_tab($is_admin); ?>
                </div>
                
                <!-- ARCHIVE Tab -->
                <div class="wfs-tab-panel" id="archive-panel">
                    <?php self::render_archive_tab($is_admin); ?>
                </div>
                
            </div>
        </div>
        
        <!-- Modals -->
        <?php self::render_modals($is_admin); ?>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render MY TASKS tab - Shows only active/unlocked tasks assigned to user
     */
    private static function render_my_tasks_tab($user_id) {
        // Get all tasks assigned to this user
        $tasks = get_posts(array(
            'post_type' => 'wfs_task',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'task_assigned_user',
                    'value' => $user_id,
                    'compare' => '='
                )
            )
        ));
        
        // Filter to only active/unlocked tasks
        $active_tasks = array();
        foreach ($tasks as $task) {
            $status = get_field('task_status', $task->ID);
            $workflow_id = get_field('task_workflow', $task->ID);
            $workflow_status = get_field('workflow_status', $workflow_id);
            
            // Only show tasks from active workflows
            if ($workflow_status !== 'active') {
                continue;
            }
            
            // Skip completed/cancelled tasks
            if (in_array($status, array('completed', 'cancelled'))) {
                continue;
            }
            
            // Check if task is unlocked (no dependencies or dependencies are complete)
            if (self::is_task_unlocked($task->ID)) {
                $active_tasks[] = $task;
            }
        }
        
        ?>
        <div class="wfs-my-tasks-container">
            <div class="wfs-section-header">
                <h2>My Active Tasks</h2>
                <div class="wfs-task-count"><?php echo count($active_tasks); ?> tasks</div>
            </div>
            
            <?php if (empty($active_tasks)): ?>
                <div class="wfs-empty-state">
                    <div class="empty-icon">✨</div>
                    <h3>All caught up!</h3>
                    <p>You have no active tasks at the moment.</p>
                </div>
            <?php else: ?>
                <div class="wfs-task-list">
                    <?php foreach ($active_tasks as $task): 
                        $workflow_id = get_field('task_workflow', $task->ID);
                        $client_id = get_field('workflow_client', $workflow_id);
                        $client = get_post($client_id);
                        $status = get_field('task_status', $task->ID);
                        $due_date = get_field('task_due_date', $task->ID);
                        $workflow = get_post($workflow_id);
                        $assigned_user_id = get_field('task_assigned_user', $task->ID);
                        $assigned_user = $assigned_user_id ? get_userdata($assigned_user_id) : null;
                    ?>
                        <div class="wfs-task-item" data-task-id="<?php echo $task->ID; ?>">
                            <div class="wfs-task-collapsed">
                                <div class="task-status-icon status-<?php echo esc_attr($status); ?>">
                                    <?php echo self::get_status_icon($status); ?>
                                </div>
                                <div class="task-main-info">
                                    <h4 class="task-title"><?php echo esc_html($task->post_title); ?></h4>
                                    <div class="task-meta">
                                        <?php 
                                        $priority = get_field('task_priority', $task->ID) ?: 'medium';
                                        $priority_icons = array('high' => '🔴', 'medium' => '🟡', 'low' => '🟢');
                                        $priority_labels = array('high' => 'High', 'medium' => 'Medium', 'low' => 'Low');
                                        ?>
                                        <span class="task-priority priority-<?php echo esc_attr($priority); ?>">
                                            <?php echo $priority_icons[$priority]; ?> <?php echo $priority_labels[$priority]; ?>
                                        </span>
                                        <span class="task-client">Client: <?php echo esc_html($client->post_title); ?></span>
                                        <?php if ($due_date): ?>
                                            <span class="task-due">Due: <?php echo date('M j', strtotime($due_date)); ?></span>
                                        <?php endif; ?>
                                        <span class="task-workflow">Workflow: <?php echo esc_html($workflow->post_title); ?></span>
                                    </div>
                                </div>
                                <div class="task-status-badge status-<?php echo esc_attr($status); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('-', ' ', $status))); ?>
                                </div>
                                <button class="task-expand-btn" aria-label="Expand task">→</button>
                            </div>
                            
                            <div class="wfs-task-expanded" style="display: none;">
                                <div class="task-detail-content">
                                    <div class="task-description">
                                        <?php echo wpautop($task->post_content); ?>
                                    </div>
                                    
                                    <div class="task-actions">
                                        <label for="task-status-<?php echo $task->ID; ?>">Update Status:</label>
                                        <select class="task-status-select" id="task-status-<?php echo $task->ID; ?>">
                                            <option value="assigned" <?php selected($status, 'assigned'); ?>>Assigned</option>
                                            <option value="in_progress" <?php selected($status, 'in_progress'); ?>>In Progress</option>
                                            <option value="waiting" <?php selected($status, 'waiting'); ?>>Waiting</option>
                                            <option value="needs_info" <?php selected($status, 'needs_info'); ?>>Needs Info</option>
                                            <option value="awaiting_external" <?php selected($status, 'awaiting_external'); ?>>Awaiting External</option>
                                            <option value="needs_approval" <?php selected($status, 'needs_approval'); ?>>Needs Approval</option>
                                            <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
                                        </select>
                                        
                                        <label for="task-reassign-<?php echo $task->ID; ?>">Reassign To (optional):</label>
                                        <select class="task-reassign-select" id="task-reassign-<?php echo $task->ID; ?>">
                                            <option value="">-- Keep Current Assignee --</option>
                                            <?php
                                            $all_users = get_users(array('orderby' => 'display_name'));
                                            foreach ($all_users as $user):
                                                $selected = ($assigned_user_id == $user->ID) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $user->ID; ?>" <?php echo $selected; ?>>
                                                    <?php echo esc_html($user->display_name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        
                                        <textarea class="task-note-input" placeholder="Add a note (required for status changes or reassignments)..." rows="3"></textarea>
                                        
                                        <button class="wfs-btn wfs-btn-primary update-task-status" data-task-id="<?php echo $task->ID; ?>" data-current-assignee="<?php echo $assigned_user_id; ?>">
                                            Update Task
                                        </button>
                                    </div>
                                    
                                    <?php 
                                    $assignment_history = get_field('task_assignment_history', $task->ID);
                                    if ($assignment_history && !empty($assignment_history)): 
                                    ?>
                                        <div class="task-assignment-history">
                                            <h5>📋 Assignment History</h5>
                                            <?php 
                                            // Reverse to show most recent first
                                            $assignment_history = array_reverse($assignment_history);
                                            foreach ($assignment_history as $assignment): 
                                                $from_user = get_userdata($assignment['from_user']);
                                                $to_user = get_userdata($assignment['to_user']);
                                                $timestamp = $assignment['assignment_timestamp'];
                                                $note = $assignment['assignment_note'];
                                            ?>
                                                <div class="assignment-history-item">
                                                    <div class="assignment-header">
                                                        <span class="assignment-users">
                                                            <?php echo esc_html($from_user ? $from_user->display_name : 'Unknown'); ?> 
                                                            → 
                                                            <?php echo esc_html($to_user ? $to_user->display_name : 'Unknown'); ?>
                                                        </span>
                                                        <span class="assignment-date"><?php echo date('M j, Y g:i A', strtotime($timestamp)); ?></span>
                                                    </div>
                                                    <div class="assignment-note"><?php echo esc_html($note); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $notes = get_field('task_activity_log', $task->ID);
                                    if ($notes): 
                                    ?>
                                        <div class="task-notes-history">
                                            <h5>Activity History</h5>
                                            <?php foreach ($notes as $note): ?>
                                                <div class="task-note-item">
                                                    <div class="note-header">
                                                        <strong><?php echo esc_html(get_userdata($note['user'])->display_name); ?></strong>
                                                        <span class="note-date"><?php echo date('M j, Y g:i A', strtotime($note['timestamp'])); ?></span>
                                                    </div>
                                                    <div class="note-content"><?php echo esc_html($note['note']); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render MONITORING tab - Shows all active workflows grouped by workflow
     */
    private static function render_monitoring_tab($is_admin) {
        // Get all active workflows
        $workflows = get_posts(array(
            'post_type' => 'wfs_workflow',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'workflow_status',
                    'value' => 'active'
                )
            )
        ));
        
        ?>
        <div class="wfs-monitoring-container">
            <div class="wfs-section-header">
                <h2>Active Workflows</h2>
                <div class="wfs-workflow-count"><?php echo count($workflows); ?> workflows</div>
            </div>
            
            <?php if (empty($workflows)): ?>
                <div class="wfs-empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No Active Workflows</h3>
                    <p>All workflows are completed or archived.</p>
                </div>
            <?php else: ?>
                <div class="wfs-workflow-list">
                    <?php foreach ($workflows as $workflow): 
                        $client_id = get_field('workflow_client', $workflow->ID);
                        $client = get_post($client_id);
                        $start_date = get_field('workflow_start_date', $workflow->ID);
                        
                        // Get all tasks for this workflow
                        $tasks = get_posts(array(
                            'post_type' => 'wfs_task',
                            'posts_per_page' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => 'task_workflow',
                                    'value' => $workflow->ID
                                )
                            )
                        ));
                    ?>
                        <div class="wfs-workflow-card" data-workflow-id="<?php echo $workflow->ID; ?>">
                            <div class="workflow-header">
                                <div class="workflow-title-section">
                                    <h3 class="workflow-title">📁 <?php echo esc_html($workflow->post_title); ?></h3>
                                    <div class="workflow-meta">
                                        <span class="workflow-client"><?php echo esc_html($client->post_title); ?></span>
                                        <span class="workflow-date">Started: <?php echo date('M j, Y', strtotime($start_date)); ?></span>
                                    </div>
                                </div>
                                <?php if ($is_admin): ?>
                                    <button class="wfs-btn wfs-btn-danger cancel-workflow-btn" data-workflow-id="<?php echo $workflow->ID; ?>">
                                        ❌ Cancel Workflow
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="workflow-tasks">
                                <?php if (empty($tasks)): ?>
                                    <div class="workflow-task-item" style="color: #999; font-style: italic;">
                                        <span class="task-icon">ℹ️</span>
                                        <span class="task-name">No tasks assigned to this workflow yet</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($tasks as $task):
                                        $task_status = get_field('task_status', $task->ID);
                                        $task_assigned_user_id = get_field('task_assigned_user', $task->ID);
                                        $task_assigned_user = $task_assigned_user_id ? get_userdata($task_assigned_user_id) : null;
                                        $task_is_blocked = !self::is_task_unlocked($task->ID);
                                    ?>
                                        <div class="monitoring-task-item" data-task-id="<?php echo $task->ID; ?>">
                                            <div class="monitoring-task-collapsed">
                                                <span class="task-icon">
                                                    <?php if ($task_is_blocked): ?>
                                                        🔒
                                                    <?php elseif ($task_status === 'completed'): ?>
                                                        ✓
                                                    <?php else: ?>
                                                        ☐
                                                    <?php endif; ?>
                                                </span>
                                                <span class="task-name"><?php echo esc_html($task->post_title); ?></span>
                                                <?php 
                                                $task_priority = get_field('task_priority', $task->ID) ?: 'medium';
                                                $priority_icons = array('high' => '🔴', 'medium' => '🟡', 'low' => '🟢');
                                                ?>
                                                <span class="task-priority-badge priority-<?php echo esc_attr($task_priority); ?>"><?php echo $priority_icons[$task_priority]; ?></span>
                                                <span class="task-status-label">[<?php echo esc_html(ucfirst(str_replace('_', ' ', $task_status))); ?>]</span>
                                                <?php if ($task_assigned_user): ?>
                                                    <span class="task-assignee">- <?php echo esc_html($task_assigned_user->display_name); ?></span>
                                                <?php endif; ?>
                                                <button class="monitoring-task-expand-btn" aria-label="View details">→</button>
                                            </div>
                                            
                                            <div class="monitoring-task-expanded" style="display: none;">
                                                <div class="task-detail-content readonly">
                                                    <?php if ($task->post_content): ?>
                                                        <div class="task-description">
                                                            <strong>Description:</strong>
                                                            <?php echo wpautop($task->post_content); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php 
                                                    $task_due_date = get_field('task_due_date', $task->ID);
                                                    $task_depends_on = get_field('task_depends_on', $task->ID);
                                                    $task_start_date = get_field('task_start_date', $task->ID);
                                                    ?>
                                                    
                                                    <div class="task-info-grid">
                                                        <?php if ($task_assigned_user): ?>
                                                            <div class="task-info-item">
                                                                <strong>Assigned To:</strong> <?php echo esc_html($task_assigned_user->display_name); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($task_due_date): ?>
                                                            <div class="task-info-item">
                                                                <strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($task_due_date)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($task_depends_on): ?>
                                                            <div class="task-info-item">
                                                                <strong>Depends On:</strong> <?php echo esc_html(get_the_title($task_depends_on)); ?>
                                                                <?php if ($task_is_blocked): ?>
                                                                    <span style="color: #dc3232; font-weight: 600;"> (Blocking this task)</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php 
                                                    $task_assignment_history = get_field('task_assignment_history', $task->ID);
                                                    if ($task_assignment_history && !empty($task_assignment_history)): 
                                                    ?>
                                                        <div class="task-assignment-history readonly">
                                                            <h5>📋 Assignment History</h5>
                                                            <?php 
                                                            $task_assignment_history = array_reverse($task_assignment_history);
                                                            foreach ($task_assignment_history as $assignment): 
                                                                $from_user = get_userdata($assignment['from_user']);
                                                                $to_user = get_userdata($assignment['to_user']);
                                                            ?>
                                                                <div class="assignment-history-item">
                                                                    <div class="assignment-header">
                                                                        <span class="assignment-users">
                                                                            <?php echo esc_html($from_user ? $from_user->display_name : 'Unknown'); ?> 
                                                                            → 
                                                                            <?php echo esc_html($to_user ? $to_user->display_name : 'Unknown'); ?>
                                                                        </span>
                                                                        <span class="assignment-date"><?php echo date('M j, Y g:i A', strtotime($assignment['assignment_timestamp'])); ?></span>
                                                                    </div>
                                                                    <div class="assignment-note"><?php echo esc_html($assignment['assignment_note']); ?></div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php 
                                                    $task_notes = get_field('task_activity_log', $task->ID);
                                                    if ($task_notes && !empty($task_notes)): 
                                                    ?>
                                                        <div class="task-notes-history readonly">
                                                            <h5>Activity History</h5>
                                                            <?php 
                                                            $task_notes = array_reverse($task_notes);
                                                            foreach ($task_notes as $note): 
                                                                $note_user = get_userdata($note['user']);
                                                            ?>
                                                                <div class="task-note-item">
                                                                    <div class="note-header">
                                                                        <strong><?php echo esc_html($note_user ? $note_user->display_name : 'Unknown'); ?></strong>
                                                                        <span class="note-date"><?php echo date('M j, Y g:i A', strtotime($note['timestamp'])); ?></span>
                                                                    </div>
                                                                    <div class="note-content"><?php echo esc_html($note['note']); ?></div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render ARCHIVE tab - Shows completed and cancelled workflows
     */
    private static function render_archive_tab($is_admin) {
        // Get all completed and cancelled workflows
        $workflows = get_posts(array(
            'post_type' => 'wfs_workflow',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'workflow_status',
                    'value' => 'completed'
                ),
                array(
                    'key' => 'workflow_status',
                    'value' => 'cancelled'
                )
            )
        ));
        
        ?>
        <div class="wfs-archive-container">
            <div class="wfs-section-header">
                <h2>Archived Workflows</h2>
                <div class="wfs-filter-controls">
                    <select class="wfs-archive-filter">
                        <option value="all">All</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($workflows)): ?>
                <div class="wfs-empty-state">
                    <div class="empty-icon">📦</div>
                    <h3>No Archived Workflows</h3>
                    <p>Completed and cancelled workflows will appear here.</p>
                </div>
            <?php else: ?>
                <div class="wfs-archive-workflow-list">
                    <?php foreach ($workflows as $workflow): 
                        $status = get_field('workflow_status', $workflow->ID);
                        $client_id = get_field('workflow_client', $workflow->ID);
                        $client = get_post($client_id);
                        $source_template = get_field('workflow_source_template', $workflow->ID);
                        $is_template_based = !empty($source_template);
                        
                        $date_field = $status === 'completed' ? 'workflow_completion_date' : 'workflow_cancelled_date';
                        $date = get_field($date_field, $workflow->ID);
                        
                        $cancellation_reason = get_field('workflow_cancellation_reason', $workflow->ID);
                        
                        // Get all tasks for this workflow
                        $tasks = get_posts(array(
                            'post_type' => 'wfs_task',
                            'posts_per_page' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => 'task_workflow',
                                    'value' => $workflow->ID
                                )
                            )
                        ));
                    ?>
                        <div class="wfs-archive-workflow-card" data-workflow-id="<?php echo $workflow->ID; ?>" data-status="<?php echo esc_attr($status); ?>">
                            <div class="archive-workflow-header">
                                <div class="archive-workflow-title-section">
                                    <h3 class="archive-workflow-title">
                                        📁 <?php echo esc_html($workflow->post_title); ?>
                                    </h3>
                                    <div class="archive-workflow-meta">
                                        <span class="workflow-client"><?php echo esc_html($client ? $client->post_title : 'No Client'); ?></span>
                                        <span class="status-badge status-<?php echo esc_attr($status); ?>">
                                            <?php echo esc_html(ucfirst($status)); ?>
                                        </span>
                                        <?php if ($date): ?>
                                            <span class="workflow-date"><?php echo date('M j, Y', strtotime($date)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                  <?php if ($status === 'cancelled'): ?>
    <?php 
   $cancelled_by_id = get_field('workflow_cancelled_by', $workflow->ID);
// ACF User field returns array, not just ID
$cancelled_by = is_array($cancelled_by_id) ? get_userdata($cancelled_by_id['ID']) : ($cancelled_by_id ? get_userdata($cancelled_by_id) : null);
    $cancelled_date = get_field('workflow_cancelled_date', $workflow->ID);
    $cancellation_reason = get_field('workflow_cancellation_reason', $workflow->ID);
    ?>
    <div class="cancellation-info">
        <strong>Cancelled by:</strong> <?php echo esc_html($cancelled_by ? $cancelled_by->display_name : 'Unknown'); ?>
        <?php if ($cancelled_date): ?>
            on <?php echo date('M j, Y g:i A', strtotime($cancelled_date)); ?>
        <?php endif; ?>
        <?php if ($cancellation_reason): ?>
            <br><strong>Reason:</strong> <?php echo esc_html($cancellation_reason); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
                                </div>
                                <?php if ($is_admin): ?>
                                    <div class="archive-workflow-actions">
                                        <?php if ($is_template_based): ?>
                                            <span class="no-reopen-msg">⚠️ Cannot reopen template-based workflows</span>
                                        <?php else: ?>
                                            <button class="wfs-btn wfs-btn-secondary reopen-workflow-btn" data-workflow-id="<?php echo $workflow->ID; ?>">
                                                🔄 Reopen Workflow
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($tasks)): ?>
                                <div class="archive-workflow-tasks">
                                    <h5 class="tasks-header">Tasks (<?php echo count($tasks); ?>)</h5>
                                    <?php foreach ($tasks as $task):
                                        $task_status = get_field('task_status', $task->ID);
                                        $task_assigned_user_id = get_field('task_assigned_user', $task->ID);
                                        $task_assigned_user = $task_assigned_user_id ? get_userdata($task_assigned_user_id) : null;
                                    ?>
                                        <div class="archive-task-item" data-task-id="<?php echo $task->ID; ?>">
                                            <div class="archive-task-collapsed">
                                                <span class="task-icon">
                                                    <?php if ($task_status === 'completed'): ?>
                                                        ✓
                                                    <?php elseif ($task_status === 'cancelled'): ?>
                                                        ✖
                                                    <?php else: ?>
                                                        ☐
                                                    <?php endif; ?>
                                                </span>
                                                <span class="task-name"><?php echo esc_html($task->post_title); ?></span>
                                                <?php 
                                                $task_priority = get_field('task_priority', $task->ID) ?: 'medium';
                                                $priority_icons = array('high' => '🔴', 'medium' => '🟡', 'low' => '🟢');
                                                ?>
                                                <span class="task-priority-badge priority-<?php echo esc_attr($task_priority); ?>"><?php echo $priority_icons[$task_priority]; ?></span>
                                                <span class="task-status-label">[<?php echo esc_html(ucfirst(str_replace('_', ' ', $task_status))); ?>]</span>
                                                <?php if ($task_assigned_user): ?>
                                                    <span class="task-assignee">- <?php echo esc_html($task_assigned_user->display_name); ?></span>
                                                <?php endif; ?>
                                                <button class="archive-task-expand-btn" aria-label="View details">→</button>
                                            </div>
                                            
                                            <div class="archive-task-expanded" style="display: none;">
                                                <div class="task-detail-content readonly">
                                                    <?php if ($task->post_content): ?>
                                                        <div class="task-description">
                                                            <strong>Description:</strong>
                                                            <?php echo wpautop($task->post_content); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php 
                                                    $task_due_date = get_field('task_due_date', $task->ID);
                                                    $task_completed_date = get_field('task_completed_date', $task->ID);
                                                    $task_depends_on = get_field('task_depends_on', $task->ID);
                                                    ?>
                                                    
                                                    <div class="task-info-grid">
                                                        <?php if ($task_assigned_user): ?>
                                                            <div class="task-info-item">
                                                                <strong>Assigned To:</strong> <?php echo esc_html($task_assigned_user->display_name); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($task_due_date): ?>
                                                            <div class="task-info-item">
                                                                <strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($task_due_date)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($task_completed_date): ?>
                                                            <div class="task-info-item">
                                                                <strong>Completed:</strong> <?php echo date('M j, Y g:i A', strtotime($task_completed_date)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($task_depends_on): ?>
                                                            <div class="task-info-item">
                                                                <strong>Depends On:</strong> <?php echo esc_html(get_the_title($task_depends_on)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php 
                                                    $task_assignment_history = get_field('task_assignment_history', $task->ID);
                                                    if ($task_assignment_history && !empty($task_assignment_history)): 
                                                    ?>
                                                        <div class="task-assignment-history readonly">
                                                            <h5>📋 Assignment History</h5>
                                                            <?php 
                                                            $task_assignment_history = array_reverse($task_assignment_history);
                                                            foreach ($task_assignment_history as $assignment): 
                                                                $from_user = get_userdata($assignment['from_user']);
                                                                $to_user = get_userdata($assignment['to_user']);
                                                            ?>
                                                                <div class="assignment-history-item">
                                                                    <div class="assignment-header">
                                                                        <span class="assignment-users">
                                                                            <?php echo esc_html($from_user ? $from_user->display_name : 'Unknown'); ?> 
                                                                            → 
                                                                            <?php echo esc_html($to_user ? $to_user->display_name : 'Unknown'); ?>
                                                                        </span>
                                                                        <span class="assignment-date"><?php echo date('M j, Y g:i A', strtotime($assignment['assignment_timestamp'])); ?></span>
                                                                    </div>
                                                                    <div class="assignment-note"><?php echo esc_html($assignment['assignment_note']); ?></div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php 
                                                    $task_notes = get_field('task_activity_log', $task->ID);
                                                    if ($task_notes && !empty($task_notes)): 
                                                    ?>
                                                        <div class="task-notes-history readonly">
                                                            <h5>Activity History</h5>
                                                            <?php 
                                                            $task_notes = array_reverse($task_notes);
                                                            foreach ($task_notes as $note): 
                                                                $note_user = get_userdata($note['user']);
                                                            ?>
                                                                <div class="task-note-item">
                                                                    <div class="note-header">
                                                                        <strong><?php echo esc_html($note_user ? $note_user->display_name : 'Unknown'); ?></strong>
                                                                        <span class="note-date"><?php echo date('M j, Y g:i A', strtotime($note['timestamp'])); ?></span>
                                                                    </div>
                                                                    <div class="note-content"><?php echo esc_html($note['note']); ?></div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render modals
     */
    private static function render_modals($is_admin) {
        ?>
        <!-- Cancel Workflow Modal -->
        <?php if ($is_admin): ?>
        <div class="wfs-modal" id="cancel-workflow-modal">
            <div class="wfs-modal-content">
                <div class="wfs-modal-header">
                    <h3>Cancel Workflow</h3>
                    <button class="wfs-modal-close">&times;</button>
                </div>
                <div class="wfs-modal-body">
                    <p>Are you sure you want to cancel this workflow?</p>
                    <label for="cancel-reason">Reason (required):</label>
                    <textarea id="cancel-reason" rows="4" placeholder="Explain why this workflow is being cancelled..."></textarea>
                </div>
                <div class="wfs-modal-footer">
                    <button class="wfs-btn wfs-btn-secondary wfs-modal-cancel">Cancel</button>
                    <button class="wfs-btn wfs-btn-danger confirm-cancel-workflow">Confirm Cancellation</button>
                </div>
            </div>
        </div>
        
        <!-- Reopen Workflow Modal -->
        <div class="wfs-modal" id="reopen-workflow-modal">
            <div class="wfs-modal-content">
                <div class="wfs-modal-header">
                    <h3>Reopen Workflow</h3>
                    <button class="wfs-modal-close">&times;</button>
                </div>
                <div class="wfs-modal-body">
                    <label for="reopen-reason">Reason for reopening:</label>
                    <textarea id="reopen-reason" rows="4" placeholder="Explain why this workflow is being reopened..."></textarea>
                </div>
                <div class="wfs-modal-footer">
                    <button class="wfs-btn wfs-btn-secondary wfs-modal-cancel">Cancel</button>
                    <button class="wfs-btn wfs-btn-primary confirm-reopen-workflow">Reopen</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

<!-- Create Task Modal -->
<div class="wfs-modal" id="create-task-modal">
    <div class="wfs-modal-content wfs-modal-large">
        <div class="wfs-modal-header">
            <h3>Create New Task</h3>
            <button class="wfs-modal-close">&times;</button>
        </div>
        <div class="wfs-modal-body">
            <form id="create-task-form">
                
                <div class="form-row">
                    <label for="new-task-client">Client (required):</label>
                    <select id="new-task-client" required>
                        <option value="">-- Select Client --</option>
                        <?php
                        // Get all clients
                        $clients = get_posts(array(
                            'post_type' => 'wfs_client',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ));
                        
                        foreach ($clients as $client):
                        ?>
                            <option value="<?php echo $client->ID; ?>">
                                <?php echo esc_html($client->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row" id="workflow-row" style="display: none;">
                    <label for="new-task-workflow">Workflow (required):</label>
                    <select id="new-task-workflow" required disabled>
                        <option value="">-- Select Client First --</option>
                    </select>
                    <small>Active workflows for selected client</small>
                </div>
                
                <div class="form-row">
                    <label for="new-task-title">Task Title (required):</label>
                    <input type="text" id="new-task-title" required placeholder="Enter task title...">
                </div>
                
                <div class="form-row">
                    <label for="new-task-description">Description:</label>
                    <textarea id="new-task-description" rows="4" placeholder="Enter task description..."></textarea>
                </div>
                
                <div class="form-row">
                    <label for="new-task-priority">Priority:</label>
                    <select id="new-task-priority">
                        <option value="medium">🟡 Medium</option>
                        <option value="high">🔴 High</option>
                        <option value="low">🟢 Low</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="new-task-assigned">Assign To:</label>
                    <select id="new-task-assigned">
                        <option value="">-- Assign Later --</option>
                        <?php
                        $all_users = get_users(array('orderby' => 'display_name'));
                        $current_user_id = get_current_user_id();
                        foreach ($all_users as $user):
                        ?>
                            <option value="<?php echo $user->ID; ?>" <?php selected($user->ID, $current_user_id); ?>>
                                <?php echo esc_html($user->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="new-task-due-date">Due Date:</label>
                    <input type="date" id="new-task-due-date">
                </div>
                
                <div class="form-row" id="depends-on-row" style="display: none;">
                    <label for="new-task-depends-on">Depends On:</label>
                    <select id="new-task-depends-on">
                        <option value="">-- No Dependency --</option>
                    </select>
                    <small>Tasks from selected workflow</small>
                </div>
                
            </form>
            
            <?php
            // ============================================
            // TEMPLATE SYSTEM INTEGRATION - ADD THIS HERE
            // ============================================
            do_action('wfs_dashboard_create_task_modal');
            ?>
            
        </div>
        <div class="wfs-modal-footer">
            <button class="wfs-btn wfs-btn-secondary wfs-modal-cancel">Cancel</button>
            <button class="wfs-btn wfs-btn-primary" id="confirm-create-task">Create Task</button>
        </div>
    </div>
</div>




 <?php
    }
    
    /**
     * Check if task is unlocked (no dependencies or dependencies are complete)
     */
    private static function is_task_unlocked($task_id) {
        $depends_on = get_field('depends_on', $task_id);
        
        if (empty($depends_on)) {
            return true; // No dependencies, task is unlocked
        }
        
        // Check if dependency is complete
        $dep_status = get_field('task_status', $depends_on);
        return in_array($dep_status, array('completed'));
    }
    
    /**
     * Get status icon
     */
    private static function get_status_icon($status) {
        $icons = array(
            'assigned' => '📋',
            'in_progress' => '▶',
            'waiting' => '⏸',
            'needs_info' => '❓',
            'awaiting_external' => '⏳',
            'needs_approval' => '👀',
            'completed' => '✓',
            'blocked' => '🔒',
            'cancelled' => '✖'
        );
        
        return isset($icons[$status]) ? $icons[$status] : '☐';
    }
    
    /**
     * Get user role
     */
    private static function get_user_role() {
        $user = wp_get_current_user();
        if (in_array('administrator', $user->roles)) {
            return 'admin';
        }
        if (in_array('wfs_admin', $user->roles)) {
            return 'admin';
        }
        if (in_array('wfs_supervisor', $user->roles)) {
            return 'supervisor';
        }
        return 'team_member';
    }
    
    /**
     * AJAX: Update task status
     */
    public static function ajax_update_task_status() {
        check_ajax_referer('wfs_dashboard_nonce', 'nonce');
        
        $task_id = intval($_POST['task_id']);
        $new_status = sanitize_text_field($_POST['status']);
        $note = sanitize_textarea_field($_POST['note']);
        $new_assignee = isset($_POST['new_assignee']) ? intval($_POST['new_assignee']) : 0;
        $current_assignee = isset($_POST['current_assignee']) ? intval($_POST['current_assignee']) : 0;
        
        // Check if there's a status change or reassignment
        $current_status = get_field('task_status', $task_id);
        $has_status_change = ($new_status !== $current_status);
        $has_reassignment = ($new_assignee > 0 && $new_assignee != $current_assignee);
        
        if (empty($note) && ($has_status_change || $has_reassignment)) {
            wp_send_json_error(array('message' => 'Note is required for status changes or reassignments.'));
        }
        
        // Update status if changed
        if ($has_status_change) {
            update_field('task_status', $new_status, $task_id);
        }
        
        // Handle reassignment
        if ($has_reassignment) {
            // Update assigned user
            update_field('task_assigned_user', $new_assignee, $task_id);
            
            // Add to assignment history
            $assignment_history = get_field('task_assignment_history', $task_id);
            if (!is_array($assignment_history)) {
                $assignment_history = array();
            }
            
            $assignment_history[] = array(
                'from_user' => $current_assignee,
                'to_user' => $new_assignee,
                'assignment_note' => $note,
                'assignment_timestamp' => current_time('Y-m-d H:i:s')
            );
            
            update_field('task_assignment_history', $assignment_history, $task_id);
        }
        
        // Add activity log note if there was a change
        if ($has_status_change || $has_reassignment) {
            $notes = get_field('task_activity_log', $task_id);
            if (!is_array($notes)) {
                $notes = array();
            }
            
            $note_text = $note;
            if ($has_reassignment) {
                $new_user = get_userdata($new_assignee);
                $note_text = '[Reassigned to ' . $new_user->display_name . '] ' . $note;
            }
            if ($has_status_change) {
                $note_text = '[Status: ' . ucfirst(str_replace('_', ' ', $new_status)) . '] ' . $note_text;
            }
            
            $notes[] = array(
                'user' => get_current_user_id(),
                'timestamp' => current_time('Y-m-d H:i:s'),
                'note' => $note_text,
                'status_change' => $new_status
            );
            update_field('task_activity_log', $notes, $task_id);
        }
        
        // Set completion date if marked completed
        if ($new_status === 'completed') {
            update_field('task_completed_date', current_time('Y-m-d H:i:s'), $task_id);
            
            // Check if workflow should auto-complete
            $workflow_id = get_field('task_workflow', $task_id);
            self::check_workflow_completion($workflow_id);
        }
        
        wp_send_json_success(array('message' => 'Task updated successfully.'));
    }
    
    /**
     * Check if workflow should auto-complete
     */
    private static function check_workflow_completion($workflow_id) {
        // Get all tasks for this workflow
        $tasks = get_posts(array(
            'post_type' => 'wfs_task',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'task_workflow',
                    'value' => $workflow_id
                )
            )
        ));
        
        // Check if all tasks are complete
        $all_complete = true;
        $last_completion_date = null;
        
        foreach ($tasks as $task_id) {
            $status = get_field('task_status', $task_id);
            if (!in_array($status, array('completed'))) {
                $all_complete = false;
                break;
            }
            
            // Track latest completion date
            $completed_date = get_field('completed_date', $task_id);
            if ($completed_date && (!$last_completion_date || strtotime($completed_date) > strtotime($last_completion_date))) {
                $last_completion_date = $completed_date;
            }
        }
        
        // If all tasks complete, mark workflow as complete
        if ($all_complete && $last_completion_date) {
            update_field('workflow_status', 'complete', $workflow_id);
            update_field('workflow_completion_date', $last_completion_date, $workflow_id);
            
            // Calculate total days
            $start_date = get_field('workflow_start_date', $workflow_id);
            if ($start_date) {
                $total_days = round((strtotime($last_completion_date) - strtotime($start_date)) / 86400);
                update_field('workflow_total_days', $total_days, $workflow_id);
            }
        }
    }
    
   /**
 * AJAX: Cancel workflow
 */
public static function ajax_cancel_workflow() {
    check_ajax_referer('wfs_dashboard_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }
    
    $workflow_id = intval($_POST['workflow_id']);
    $reason = sanitize_textarea_field($_POST['reason']);
    
    if (empty($reason)) {
        wp_send_json_error(array('message' => 'Cancellation reason is required.'));
    }
    
    // Get current user info
    $current_user = wp_get_current_user();
    
    // Update workflow
    update_field('workflow_status', 'cancelled', $workflow_id);
    update_field('workflow_cancellation_reason', $reason, $workflow_id);
   update_field('workflow_cancelled_by', $current_user->ID, $workflow_id);
    update_field('workflow_cancelled_date', current_time('Y-m-d H:i:s'), $workflow_id);
    
    // Cancel all tasks (save their status first)
    $tasks = get_posts(array(
        'post_type' => 'wfs_task',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'task_workflow',
                'value' => $workflow_id
            )
        )
    ));
    
 foreach ($tasks as $task_id) {
    // SAVE current status BEFORE cancelling
    $current_status = get_field('task_status', $task_id);
    update_post_meta($task_id, '_task_status_before_cancel', $current_status);
    
    // SAVE activity log before it gets cleared
    $activity_log = get_field('task_activity_log', $task_id);
    if ($activity_log && is_array($activity_log)) {
        update_post_meta($task_id, '_task_activity_log_backup', json_encode($activity_log));
    }
    
    // SAVE assignment history before it gets cleared (extra safety)
    $assignment_history = get_field('task_assignment_history', $task_id);
    if ($assignment_history && is_array($assignment_history)) {
        update_post_meta($task_id, '_task_assignment_history_backup', json_encode($assignment_history));
    }
    
    // Now cancel the task
    update_field('task_status', 'cancelled', $task_id);
    
    // IMMEDIATELY CHECK if data was cleared and restore it
    $activity_log_after = get_field('task_activity_log', $task_id);
    if (empty($activity_log_after) && $activity_log) {
        update_field('task_activity_log', $activity_log, $task_id);
    }
    
    $assignment_history_after = get_field('task_assignment_history', $task_id);
    if (empty($assignment_history_after) && $assignment_history) {
        update_field('task_assignment_history', $assignment_history, $task_id);
    }
}
    
    wp_send_json_success(array('message' => 'Workflow cancelled successfully.'));
}

/**
 * AJAX: Reopen workflow
 */
public static function ajax_reopen_workflow() {
    check_ajax_referer('wfs_dashboard_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }
    
    $workflow_id = intval($_POST['workflow_id']);
    $reason = sanitize_textarea_field($_POST['reason']);
    
    if (empty($reason)) {
        wp_send_json_error(array('message' => 'Reason for reopening is required.'));
    }
    
    // Check if template-based
    $source_template = get_field('workflow_source_template', $workflow_id);
    if ($source_template) {
        wp_send_json_error(array('message' => 'Template-based workflows cannot be reopened.'));
    }
    
    // Get current user info
    $current_user = wp_get_current_user();
    
    // Update workflow status to active
    update_field('workflow_status', 'active', $workflow_id);
    update_field('workflow_reopened_reason', $reason, $workflow_id);
   update_field('workflow_reopened_by', $current_user->ID, $workflow_id);
    update_field('workflow_reopened_date', current_time('Y-m-d H:i:s'), $workflow_id);
    
    // Clear completion data (but KEEP cancellation history for audit trail)
    delete_field('workflow_completion_date', $workflow_id);
    delete_field('workflow_total_days', $workflow_id);
    
    // DO NOT delete cancellation data - keep it for history:
    // delete_field('workflow_cancellation_reason', $workflow_id);
    // delete_field('workflow_cancelled_by', $workflow_id);
    // delete_field('workflow_cancelled_date', $workflow_id);
    
    // Restore tasks to their previous status
    $tasks = get_posts(array(
        'post_type' => 'wfs_task',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'task_workflow',
                'value' => $workflow_id
            )
        )
    ));
    
  foreach ($tasks as $task_id) {
    // SAVE current activity log and assignment history BEFORE changing status
    $activity_log = get_field('task_activity_log', $task_id);
    if ($activity_log && is_array($activity_log)) {
        update_post_meta($task_id, '_task_activity_log_temp', json_encode($activity_log));
    }
    
    $assignment_history = get_field('task_assignment_history', $task_id);
    if ($assignment_history && is_array($assignment_history)) {
        update_post_meta($task_id, '_task_assignment_history_temp', json_encode($assignment_history));
    }
    
    // Get the saved status from before cancellation
    $saved_status = get_post_meta($task_id, '_task_status_before_cancel', true);
    
    if ($saved_status) {
        // Restore to the saved status
        update_field('task_status', $saved_status, $task_id);
        // Clean up the temporary meta
        delete_post_meta($task_id, '_task_status_before_cancel');
    } else {
        // Fallback if no saved status found
        update_field('task_status', 'assigned', $task_id);
    }
    
    // RESTORE activity log if it got cleared
    $activity_log_after = get_field('task_activity_log', $task_id);
    if (empty($activity_log_after) && $activity_log) {
        update_field('task_activity_log', $activity_log, $task_id);
    }
    delete_post_meta($task_id, '_task_activity_log_temp');
    
    // RESTORE assignment history if it got cleared
    $assignment_history_after = get_field('task_assignment_history', $task_id);
    if (empty($assignment_history_after) && $assignment_history) {
        update_field('task_assignment_history', $assignment_history, $task_id);
    }
    delete_post_meta($task_id, '_task_assignment_history_temp');
}
    
    wp_send_json_success(array('message' => 'Workflow reopened successfully.'));
}
    
    /**
     * AJAX: Get task details
     */
    public static function ajax_get_task_details() {
        check_ajax_referer('wfs_dashboard_nonce', 'nonce');
        
        $task_id = intval($_POST['task_id']);
        $task = get_post($task_id);
        
        if (!$task) {
            wp_send_json_error(array('message' => 'Task not found.'));
        }
        
        wp_send_json_success(array(
            'title' => $task->post_title,
            'content' => $task->post_content,
            'status' => get_field('task_status', $task_id),
            'notes' => get_field('task_activity_log', $task_id)
        ));
    }
    
   /**
 * AJAX: Create workflow from template
 */
public static function ajax_create_workflow_from_template() {
    check_ajax_referer('wfs_dashboard_nonce', 'nonce');
    
    $template_id = intval($_POST['template_id']);
    $client_id = intval($_POST['client_id']);
    
    if (!$template_id || !$client_id) {
        wp_send_json_error(array('message' => 'Missing required fields.'));
    }
    
    $template = get_post($template_id);
    $client = get_post($client_id);
    
    // Create workflow title: [Template Name] - [Client Name] - [Month Year]
    $month_year = date('F Y');
    $workflow_title = $template->post_title . ' - ' . $client->post_title . ' - ' . $month_year;
    
    // Create new workflow
    $new_workflow_id = wp_insert_post(array(
        'post_title' => $workflow_title,
        'post_type' => 'wfs_workflow',
        'post_status' => 'publish'
    ));
    
    if (is_wp_error($new_workflow_id)) {
        wp_send_json_error(array('message' => 'Failed to create workflow.'));
    }
    
    // Set workflow fields
    update_field('client', $client_id, $new_workflow_id);
    update_field('workflow_status', 'active', $new_workflow_id);
    update_field('start_date', date('Y-m-d'), $new_workflow_id);
    update_field('source_template', $template_id, $new_workflow_id);
    
    // Copy tasks from template
    $template_tasks = get_posts(array(
        'post_type' => 'wfs_task',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'task_workflow',
                'value' => $template_id
            )
        )
    ));
    
    foreach ($template_tasks as $template_task) {
        $new_task_id = wp_insert_post(array(
            'post_title' => $template_task->post_title,
            'post_content' => $template_task->post_content,
            'post_type' => 'wfs_task',
            'post_status' => 'publish'
        ));
        
        if (!is_wp_error($new_task_id)) {
            // Copy task fields except assigned_to
            $task_fields = get_fields($template_task->ID);
            if ($task_fields) {
                foreach ($task_fields as $key => $value) {
                    if ($key !== 'assigned_to' && $key !== 'workflow') {
                        update_field($key, $value, $new_task_id);
                    }
                }
            }
            update_field('task_workflow', $new_workflow_id, $new_task_id);
            update_field('task_status', 'assigned', $new_task_id);
        }
    }
    
    // Increment template usage count
    $usage_count = get_post_meta($template_id, '_template_usage_count', true) ?: 0;
    update_post_meta($template_id, '_template_usage_count', $usage_count + 1);
    
    wp_send_json_success(array(
        'message' => 'Workflow created successfully.',
        'workflow_id' => $new_workflow_id
    ));
}

/**
 * AJAX: Get workflows for selected client
 */
public static function ajax_get_client_workflows() {
    check_ajax_referer('wfs_dashboard_nonce', 'nonce');
    
    $client_id = intval($_POST['client_id']);
    
    $workflows = get_posts(array(
        'post_type' => 'wfs_workflow',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'workflow_status',
                'value' => 'active'
            ),
            array(
                'key' => 'workflow_client',
                'value' => $client_id
            )
        )
    ));
    
    $workflow_list = array();
    foreach ($workflows as $workflow) {
        $workflow_list[] = array(
            'id' => $workflow->ID,
            'title' => $workflow->post_title
        );
    }
    
    wp_send_json_success(array('workflows' => $workflow_list));
}



/**
 * AJAX: Get workflow tasks for dependency dropdown
 */
public static function ajax_get_workflow_tasks() {
    check_ajax_referer('wfs_dashboard_nonce', 'nonce');
    
    $workflow_id = intval($_POST['workflow_id']);
    
    $tasks = get_posts(array(
        'post_type' => 'wfs_task',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'task_workflow',
                'value' => $workflow_id
            )
        )
    ));
    
    $task_list = array();
    foreach ($tasks as $task) {
        $task_list[] = array(
            'id' => $task->ID,
            'title' => $task->post_title
        );
    }
    
    wp_send_json_success(array('tasks' => $task_list));
}

/**
 * AJAX: Create new task
 */
public static function ajax_create_new_task() {
    check_ajax_referer('wfs_dashboard_nonce', 'nonce');
    
    $workflow_id = intval($_POST['workflow_id']);
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $priority = sanitize_text_field($_POST['priority']);
    $assigned_to = intval($_POST['assigned_to']);
    $due_date = sanitize_text_field($_POST['due_date']);
    $depends_on = intval($_POST['depends_on']);
    
    if (!$workflow_id || !$title) {
        wp_send_json_error(array('message' => 'Workflow and title are required.'));
    }
    
    // Verify workflow exists and is active
    $workflow = get_post($workflow_id);
    if (!$workflow || get_field('workflow_status', $workflow_id) !== 'active') {
        wp_send_json_error(array('message' => 'Invalid or inactive workflow.'));
    }
    
    // Create task
    $task_id = wp_insert_post(array(
        'post_title' => $title,
        'post_content' => $description,
        'post_type' => 'wfs_task',
        'post_status' => 'publish'
    ));
    
    if (is_wp_error($task_id)) {
        wp_send_json_error(array('message' => 'Failed to create task.'));
    }
    
    // Set task fields
    update_field('task_workflow', $workflow_id, $task_id);
    update_field('task_status', 'assigned', $task_id);
    update_field('task_priority', $priority, $task_id);
    
    if ($assigned_to) {
        update_field('task_assigned_user', $assigned_to, $task_id);
    }
    
    if ($due_date) {
        update_field('task_due_date', $due_date, $task_id);
    }
    
    if ($depends_on) {
        update_field('task_depends_on', $depends_on, $task_id);
    }
    
    // Log creation in activity log
    $activity_log = array(
        array(
            'user' => get_current_user_id(),
            'timestamp' => current_time('Y-m-d H:i:s'),
            'note' => 'Task created',
            'status_change' => 'assigned'
        )
    );
    update_field('task_activity_log', $activity_log, $task_id);
    
    wp_send_json_success(array(
        'message' => 'Task created successfully.',
        'task_id' => $task_id
    ));
}

} // <- Only ONE closing brace for the class