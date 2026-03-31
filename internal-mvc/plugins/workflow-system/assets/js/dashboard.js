/**
 * Workflow Dashboard JavaScript - Phase 2A Part 3
 * Handles tabs, modals, task updates, workflow cancellation/reopening
 */

jQuery(document).ready(function($) {
    
    // Tab Switching
    $('.wfs-tab-button').on('click', function() {
        const targetTab = $(this).data('tab');
        
        // Update button states
        $('.wfs-tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Update panel visibility
        $('.wfs-tab-panel').removeClass('active');
        $('#' + targetTab + '-panel').addClass('active');
    });
    
    // Task Item Expand/Collapse (MY TASKS)
    $('.wfs-task-item').on('click', '.task-expand-btn, .wfs-task-collapsed', function(e) {
        if ($(e.target).is('button') && !$(e.target).hasClass('task-expand-btn')) {
            return; // Don't toggle if clicking other buttons
        }
        
        const $taskItem = $(this).closest('.wfs-task-item');
        const $expanded = $taskItem.find('.wfs-task-expanded');
        
        $taskItem.toggleClass('expanded');
        $expanded.slideToggle(300);
        
        // Rotate arrow
        const $arrow = $taskItem.find('.task-expand-btn');
        if ($taskItem.hasClass('expanded')) {
            $arrow.text('↓');
        } else {
            $arrow.text('→');
        }
    });
    
    // Monitoring Task Expand/Collapse (MONITORING tab - read-only)
    $(document).on('click', '.monitoring-task-collapsed, .monitoring-task-expand-btn', function(e) {
        e.stopPropagation();
        
        const $taskItem = $(this).closest('.monitoring-task-item');
        const $expanded = $taskItem.find('.monitoring-task-expanded');
        
        $taskItem.toggleClass('expanded');
        $expanded.slideToggle(300);
        
        // Rotate arrow
        const $arrow = $taskItem.find('.monitoring-task-expand-btn');
        if ($taskItem.hasClass('expanded')) {
            $arrow.text('↓');
        } else {
            $arrow.text('→');
        }
    });
    
    // Update Task Status
    $('.update-task-status').on('click', function() {
        const $button = $(this);
        const taskId = $button.data('task-id');
        const currentAssignee = $button.data('current-assignee');
        const $taskItem = $button.closest('.wfs-task-item');
        const newStatus = $taskItem.find('.task-status-select').val();
        const newAssignee = $taskItem.find('.task-reassign-select').val();
        const note = $taskItem.find('.task-note-input').val().trim();
        
        // Check if there's a change
        const hasReassignment = newAssignee && newAssignee != currentAssignee;
        
        if (!note && (newStatus || hasReassignment)) {
            alert('Please add a note explaining the change.');
            $taskItem.find('.task-note-input').focus();
            return;
        }
        
        $button.prop('disabled', true).html('<span class="wfs-loading"></span> Updating...');
        
        $.ajax({
            url: wfsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wfs_update_task_status',
                nonce: wfsData.nonce,
                task_id: taskId,
                status: newStatus,
                note: note,
                new_assignee: newAssignee,
                current_assignee: currentAssignee
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotification('Task updated successfully!', 'success');
                    
                    // Reload page after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message || 'Failed to update task.');
                    $button.prop('disabled', false).text('Update Task');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false).text('Update Task');
            }
        });
    });
    
    // Archive Filter
    $('.wfs-archive-filter').on('change', function() {
        const filterValue = $(this).val();
        const $workflows = $('.wfs-archive-workflow-card');
        
        if (filterValue === 'all') {
            $workflows.show();
        } else {
            $workflows.hide();
            $('.wfs-archive-workflow-card[data-status="' + filterValue + '"]').show();
        }
    });
    
    // Archive Task Expand/Collapse (ARCHIVE tab - read-only)
    $(document).on('click', '.archive-task-collapsed, .archive-task-expand-btn', function(e) {
        e.stopPropagation();
        
        const $taskItem = $(this).closest('.archive-task-item');
        const $expanded = $taskItem.find('.archive-task-expanded');
        
        $taskItem.toggleClass('expanded');
        $expanded.slideToggle(300);
        
        // Rotate arrow
        const $arrow = $taskItem.find('.archive-task-expand-btn');
        if ($taskItem.hasClass('expanded')) {
            $arrow.text('↓');
        } else {
            $arrow.text('→');
        }
    });
    
    // Cancel Workflow Button
    let currentWorkflowId = null;
    
    $('.cancel-workflow-btn').on('click', function() {
        currentWorkflowId = $(this).data('workflow-id');
        openModal('#cancel-workflow-modal');
    });
    
    // Confirm Cancel Workflow
    $('.confirm-cancel-workflow').on('click', function() {
        const reason = $('#cancel-reason').val().trim();
        
        if (!reason) {
            alert('Please provide a reason for cancellation.');
            $('#cancel-reason').focus();
            return;
        }
        
        const $button = $(this);
        $button.prop('disabled', true).html('<span class="wfs-loading"></span> Cancelling...');
        
        $.ajax({
            url: wfsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wfs_cancel_workflow',
                nonce: wfsData.nonce,
                workflow_id: currentWorkflowId,
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Workflow cancelled successfully!', 'success');
                    closeModal('#cancel-workflow-modal');
                    
                    // Reload page after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message || 'Failed to cancel workflow.');
                    $button.prop('disabled', false).text('Confirm Cancellation');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false).text('Confirm Cancellation');
            }
        });
    });
    
    // Reopen Workflow Button
    $('.reopen-workflow-btn').on('click', function() {
        currentWorkflowId = $(this).data('workflow-id');
        openModal('#reopen-workflow-modal');
    });
    
    // Confirm Reopen Workflow
    $('.confirm-reopen-workflow').on('click', function() {
        const reason = $('#reopen-reason').val().trim();
        
        if (!reason) {
            alert('Please provide a reason for reopening.');
            $('#reopen-reason').focus();
            return;
        }
        
        const $button = $(this);
        $button.prop('disabled', true).html('<span class="wfs-loading"></span> Reopening...');
        
        $.ajax({
            url: wfsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wfs_reopen_workflow',
                nonce: wfsData.nonce,
                workflow_id: currentWorkflowId,
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Workflow reopened successfully!', 'success');
                    closeModal('#reopen-workflow-modal');
                    
                    // Reload page after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message || 'Failed to reopen workflow.');
                    $button.prop('disabled', false).text('Reopen');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false).text('Reopen');
            }
        });
    });
    
    // Modal Functions
 function openModal(modalId) {
    $(modalId).addClass('active').fadeIn(200);
    $('body').css('overflow', 'hidden');
}
    
  function closeModal(modalId) {
    $(modalId).removeClass('active').fadeOut(200);
    $('body').css('overflow', '');
    
    // Clear form fields
    $(modalId).find('textarea').val('');
    $(modalId).find('input[type="text"]').val('');
    $(modalId).find('input[type="date"]').val('');
    $(modalId).find('select').prop('selectedIndex', 0);
    $(modalId).find('button').prop('disabled', false);
    
    // Hide conditional rows for create task modal
    $('#workflow-row').hide();
    $('#depends-on-row').hide();
    $('#new-task-workflow').html('<option value="">-- Select Client First --</option>').prop('disabled', true);
}
    
    // Close modal on X button or Cancel button
    $('.wfs-modal-close, .wfs-modal-cancel').on('click', function() {
        const $modal = $(this).closest('.wfs-modal');
        closeModal('#' + $modal.attr('id'));
    });
    
    // Close modal on outside click
    $('.wfs-modal').on('click', function(e) {
        if ($(e.target).hasClass('wfs-modal')) {
            closeModal('#' + $(this).attr('id'));
        }
    });
    
    // Close modal on ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.wfs-modal.active').each(function() {
                closeModal('#' + $(this).attr('id'));
            });
        }
    });
    
    // Notification System
    function showNotification(message, type) {
        const $notification = $('<div>')
            .addClass('wfs-notification')
            .addClass('notification-' + type)
            .html(message)
            .css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '16px 24px',
                background: type === 'success' ? '#46b450' : '#dc3232',
                color: '#ffffff',
                borderRadius: '8px',
                fontSize: '14px',
                fontWeight: '600',
                zIndex: '10001',
                boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
                animation: 'slideInRight 0.3s ease'
            });
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Add CSS animation for notification
    if (!$('#wfs-notification-styles').length) {
        $('<style id="wfs-notification-styles">')
            .text('@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }')
            .appendTo('head');
    }
    
});

// ==========================================
// CREATE TASK FUNCTIONALITY
// ==========================================

console.log('Create Task JS loaded');

// Open create task modal
jQuery('#open-create-task-modal').on('click', function(e) {
    console.log('Button clicked!');
    e.preventDefault();
    jQuery('#create-task-modal').addClass('active').fadeIn(200);
    jQuery('body').css('overflow', 'hidden');
});



// When CLIENT is selected, load workflows for that client
jQuery('#new-task-client').on('change', function() {
    var clientId = jQuery(this).val();
    
    if (clientId) {
        jQuery('#workflow-row').show();
        loadClientWorkflows(clientId);
    } else {
        jQuery('#workflow-row').hide();
        jQuery('#depends-on-row').hide();
        jQuery('#new-task-workflow').html('<option value="">-- Select Client First --</option>').prop('disabled', true);
        jQuery('#new-task-depends-on').html('<option value="">-- No Dependency --</option>');
    }
});

// Load workflows for selected client
function loadClientWorkflows(clientId) {
    jQuery('#new-task-workflow').html('<option value="">Loading workflows...</option>').prop('disabled', true);
    
    jQuery.ajax({
        url: wfsData.ajaxUrl,
        type: 'POST',
        data: {
            action: 'wfs_get_client_workflows',
            nonce: wfsData.nonce,
            client_id: clientId
        },
        success: function(response) {
            if (response.success && response.data.workflows.length > 0) {
                var options = '<option value="">-- Select Workflow --</option>';
                response.data.workflows.forEach(function(workflow) {
                    options += '<option value="' + workflow.id + '">' + workflow.title + '</option>';
                });
                jQuery('#new-task-workflow').html(options).prop('disabled', false);
            } else {
                jQuery('#new-task-workflow').html('<option value="">No active workflows for this client</option>').prop('disabled', true);
            }
        },
        error: function() {
            jQuery('#new-task-workflow').html('<option value="">Error loading workflows</option>').prop('disabled', true);
        }
    });
}

// When WORKFLOW is selected, load its tasks for dependencies
jQuery('#new-task-workflow').on('change', function() {
    var workflowId = jQuery(this).val();
    
    if (workflowId) {
        jQuery('#depends-on-row').show();
        loadWorkflowTasks(workflowId);
    } else {
        jQuery('#depends-on-row').hide();
        jQuery('#new-task-depends-on').html('<option value="">-- No Dependency --</option>');
    }
});

// Load tasks from selected workflow for dependency dropdown
function loadWorkflowTasks(workflowId) {
    jQuery.ajax({
        url: wfsData.ajaxUrl,
        type: 'POST',
        data: {
            action: 'wfs_get_workflow_tasks',
            nonce: wfsData.nonce,
            workflow_id: workflowId
        },
        success: function(response) {
            if (response.success) {
                var options = '<option value="">-- No Dependency --</option>';
                response.data.tasks.forEach(function(task) {
                    options += '<option value="' + task.id + '">' + task.title + '</option>';
                });
                jQuery('#new-task-depends-on').html(options);
            }
        }
    });
}

// Create task submit
jQuery('#confirm-create-task').on('click', function() {
    var clientId = jQuery('#new-task-client').val();
    var workflowId = jQuery('#new-task-workflow').val();
    var title = jQuery('#new-task-title').val().trim();
    
    if (!clientId) {
        alert('Please select a client');
        return;
    }
    
    if (!workflowId) {
        alert('Please select a workflow');
        return;
    }
    
    if (!title) {
        alert('Please enter a task title');
        return;
    }
    
    var taskData = {
        action: 'wfs_create_new_task',
        nonce: wfsData.nonce,
        workflow_id: workflowId,
        title: title,
        description: jQuery('#new-task-description').val(),
        priority: jQuery('#new-task-priority').val(),
        assigned_to: jQuery('#new-task-assigned').val(),
        due_date: jQuery('#new-task-due-date').val(),
        depends_on: jQuery('#new-task-depends-on').val()
    };
    
    jQuery('#confirm-create-task').prop('disabled', true).text('Creating...');
    
    jQuery.ajax({
        url: wfsData.ajaxUrl,
        type: 'POST',
        data: taskData,
        success: function(response) {
            if (response.success) {
                alert('Task created successfully!');
                jQuery('#create-task-modal').fadeOut(200);
                // Reset form
                jQuery('#create-task-form')[0].reset();
                jQuery('#workflow-row').hide();
                jQuery('#depends-on-row').hide();
                // Reload page to show new task
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        },
        error: function() {
            alert('An error occurred. Please try again.');
        },
        complete: function() {
            jQuery('#confirm-create-task').prop('disabled', false).text('Create Task');
        }
    });
});