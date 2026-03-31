/**
 * Dashboard Template Integration JavaScript
 * Handles template preview and workflow creation from templates
 */

(function($) {
    'use strict';
    
    let currentTemplateId = null;
    
    $(document).ready(function() {
        initializeTemplateIntegration();
    });
    
    /**
     * Initialize template integration
     */
    function initializeTemplateIntegration() {
        bindEvents();
    }
    
    /**
     * Bind event handlers
     */
    function bindEvents() {
        // View template tasks
        $(document).on('click', '.view-template-tasks', viewTemplateTasks);
        
        // Use template directly
        $(document).on('click', '.use-template', useTemplateDirectly);
        
        // Use template from preview modal
        $(document).on('click', '.use-this-template', useTemplateFromPreview);
        
        // Close preview modal
        $(document).on('click', '.close-preview, #template-preview-modal .wfs-modal-close', closePreviewModal);
        
        // Close modal on overlay click
        $(document).on('click', '#template-preview-modal .wfs-modal-overlay', closePreviewModal);
    }
    
    /**
     * View template tasks in preview modal
     */
    function viewTemplateTasks(e) {
        e.preventDefault();
        
        const templateId = $(this).data('template-id');
        currentTemplateId = templateId;
        
        // Show loading state
        $('#template-preview-modal').show();
        $('#template-preview-details').html('<div class="template-loading"><span class="spinner is-active"></span><p>Loading template...</p></div>');
        
        // Load template data
        $.ajax({
            url: wfsDashboardTemplates.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_get_template_preview',
                nonce: wfsDashboardTemplates.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success) {
                    renderTemplatePreview(response.data);
                } else {
                    showError('Failed to load template preview');
                }
            },
            error: function() {
                showError('Failed to load template preview');
            }
        });
    }
    
    /**
     * Render template preview
     */
    function renderTemplatePreview(template) {
        $('#template-preview-title').text(template.name);
        
        let html = '';
        
        // Description
        html += '<div class="preview-section">';
        html += '<h3>Description</h3>';
        html += '<p>' + (template.description || 'No description provided') + '</p>';
        html += '</div>';
        
        // Execution Mode
        html += '<div class="preview-section">';
        html += '<h3>Execution Mode</h3>';
        html += renderExecutionMode(template.execution_mode);
        html += '</div>';
        
        // Tasks
        html += '<div class="preview-section">';
        html += '<h3>Tasks (' + template.tasks.length + ')</h3>';
        html += '<div class="preview-tasks-list">';
        
        template.tasks.forEach(function(task, index) {
            html += renderPreviewTask(task, index, template.tasks);
        });
        
        html += '</div>';
        html += '</div>';
        
        $('#template-preview-details').html(html);
    }
    
    /**
     * Render execution mode display
     */
    function renderExecutionMode(mode) {
        const modes = {
            'sequential': {
                icon: 'dashicons-arrow-right-alt2',
                title: 'Sequential Execution',
                description: 'Tasks must be completed in order. Each task is locked until the previous one is done.'
            },
            'parallel': {
                icon: 'dashicons-editor-justify',
                title: 'Parallel Execution',
                description: 'All tasks can be worked on simultaneously. No dependencies enforced.'
            },
            'custom': {
                icon: 'dashicons-networking',
                title: 'Custom Dependencies',
                description: 'Specific dependencies are set for each task. Mix of sequential and parallel execution.'
            }
        };
        
        const modeData = modes[mode] || modes['sequential'];
        
        return `
            <div class="execution-mode-display">
                <div class="execution-mode-icon">
                    <span class="dashicons ${modeData.icon}"></span>
                </div>
                <div class="execution-mode-info">
                    <h4>${modeData.title}</h4>
                    <p>${modeData.description}</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Render preview task
     */
    function renderPreviewTask(task, index, allTasks) {
        let html = '<div class="preview-task-item">';
        
        // Task number
        html += '<div class="preview-task-number">' + (index + 1) + '</div>';
        
        // Task content
        html += '<div class="preview-task-content">';
        html += '<h4 class="preview-task-title">' + escapeHtml(task.title) + '</h4>';
        
        if (task.description) {
            html += '<p class="preview-task-description">' + escapeHtml(task.description) + '</p>';
        }
        
        // Task meta
        html += '<div class="preview-task-meta">';
        
        // Priority badge
        html += '<span class="preview-task-badge priority-' + task.priority + '">' + 
                task.priority.toUpperCase() + '</span>';
        
        // Status badge
        html += '<span class="preview-task-badge">' + formatStatus(task.status) + '</span>';
        
        // Dependency badge
        if (task.depends_on !== null && task.depends_on !== '' && task.depends_on >= 0) {
            const dependencyTask = allTasks[task.depends_on];
            if (dependencyTask) {
                html += '<span class="preview-task-badge has-dependency">';
                html += '<span class="dashicons dashicons-arrow-left-alt"></span> ';
                html += 'Depends on: Task ' + (parseInt(task.depends_on) + 1);
                html += '</span>';
            }
        }
        
        html += '</div>'; // End task-meta
        html += '</div>'; // End task-content
        html += '</div>'; // End task-item
        
        return html;
    }
    
    /**
     * Use template directly (without preview)
     */
    function useTemplateDirectly(e) {
        e.preventDefault();
        
        const templateId = $(this).data('template-id');
        const templateName = $(this).closest('.template-card').find('h4').text();
        
        showCreateFromTemplateDialog(templateId, templateName);
    }
    
    /**
     * Use template from preview modal
     */
    function useTemplateFromPreview(e) {
        e.preventDefault();
        
        if (!currentTemplateId) {
            return;
        }
        
        const templateName = $('#template-preview-title').text();
        
        closePreviewModal();
        showCreateFromTemplateDialog(currentTemplateId, templateName);
    }
    
    /**
     * Show create from template dialog
     */
    function showCreateFromTemplateDialog(templateId, templateName) {
        // Get client selection
       const clientId = $('#new-task-client').val();
        
        if (!clientId) {
            alert('Please select a client first');
            return;
        }
        
        const workflowName = prompt('Enter a name for this workflow:', templateName);
        
        if (!workflowName) {
            return;
        }
        
        createWorkflowFromTemplate(templateId, clientId, workflowName);
    }
    
    /**
     * Create workflow from template
     */
    function createWorkflowFromTemplate(templateId, clientId, workflowName) {
        // Show loading
        const $loadingMsg = $('<div class="wfs-loading-overlay">')
            .html('<div class="loading-content"><span class="spinner is-active"></span><p>Creating workflow from template...</p></div>')
            .appendTo('body');
        
        $.ajax({
            url: wfsDashboardTemplates.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_create_workflow_from_template',
                nonce: wfsDashboardTemplates.nonce,
                template_id: templateId,
                client_id: clientId,
                workflow_name: workflowName
            },
            success: function(response) {
                $loadingMsg.remove();
                
                if (response.success) {
                    showSuccess('Workflow created successfully! ' + response.data.task_count + ' tasks added.');
                    
                    // Close modal and refresh if needed
                    $('#create-task-modal').hide();
                    
                    // Reload page to show new tasks
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showError(response.data || 'Failed to create workflow');
                }
            },
            error: function() {
                $loadingMsg.remove();
                showError('Failed to create workflow from template');
            }
        });
    }
    
    /**
     * Close preview modal
     */
    function closePreviewModal() {
        $('#template-preview-modal').hide();
        currentTemplateId = null;
    }
    
    /**
     * Format status text
     */
    function formatStatus(status) {
        return status.replace(/-/g, ' ')
                    .replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        const $notice = $('<div class="wfs-notice wfs-notice-success">')
            .text(message)
            .appendTo('body')
            .fadeIn();
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 4000);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        const $notice = $('<div class="wfs-notice wfs-notice-error">')
            .text(message)
            .appendTo('body')
            .fadeIn();
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 4000);
    }
    
})(jQuery);

/* Additional styles for loading overlay and notices */
jQuery(document).ready(function($) {
    $('head').append(`
        <style>
            .wfs-loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                z-index: 999999;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .loading-content {
                background: #fff;
                padding: 40px;
                border-radius: 8px;
                text-align: center;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            }
            
            .loading-content p {
                margin: 15px 0 0 0;
                font-size: 16px;
                color: #000;
            }
            
            .wfs-notice {
                position: fixed;
                top: 50px;
                right: 50px;
                padding: 15px 25px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 600;
                z-index: 1000000;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                display: none;
            }
            
            .wfs-notice-success {
                background: #46b450;
                color: #fff;
            }
            
            .wfs-notice-error {
                background: #dc3232;
                color: #fff;
            }
            
            @media (max-width: 768px) {
                .wfs-notice {
                    right: 20px;
                    left: 20px;
                    top: 20px;
                }
            }
        </style>
    `);
});
