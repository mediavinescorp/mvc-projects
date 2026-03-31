/**
 * Workflow Template Manager JavaScript
 * Handles template creation, editing, and task management
 */

(function($) {
    'use strict';
    
    let currentTemplateId = null;
    let taskCounter = 0;
    let templates = [];
    
    $(document).ready(function() {
        initializeTemplateManager();
    });
    
    /**
     * Initialize template manager
     */
    function initializeTemplateManager() {
        loadTemplates();
        bindEvents();
    }
    
    /**
     * Bind all event handlers
     */
    function bindEvents() {
        // Create new template buttons
        $('#create-new-template, #welcome-create-template').on('click', createNewTemplate);
        
        // Form submission
        $('#template-form').on('submit', saveTemplate);
        
        // Cancel editing
        $('#cancel-edit').on('click', cancelEdit);
        
        // Delete template
        $('#delete-template').on('click', deleteTemplate);
        
        // Add task
        $('#add-template-task').on('click', addTask);
        
        // Task removal (delegated)
        $(document).on('click', '.task-remove', removeTask);
        
        // Execution mode change
        $('input[name="execution_mode"]').on('change', handleExecutionModeChange);
        
        // Template search
        $('#template-search').on('keyup', filterTemplates);
        
        // Template selection (delegated)
        $(document).on('click', '.template-list-item', selectTemplate);
        
        // Make tasks sortable
        initializeSortable();
    }
    
    /**
     * Load all templates
     */
    function loadTemplates() {
        $.ajax({
            url: wfsTemplateManager.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_get_all_templates',
                nonce: wfsTemplateManager.nonce
            },
            success: function(response) {
                if (response.success) {
                    templates = response.data;
                    renderTemplateList(templates);
                }
            },
            error: function() {
                showNotice('Failed to load templates', 'error');
            }
        });
    }
    
    /**
     * Render template list
     */
    function renderTemplateList(templatesArray) {
        const $list = $('#template-list');
        $list.empty();
        
        if (templatesArray.length === 0) {
            $list.html('<div class="no-templates"><p>No templates found. Create your first template!</p></div>');
            return;
        }
        
        templatesArray.forEach(function(template) {
            const activeClass = template.active ? 'active' : 'inactive';
            const activeLabel = template.active ? 'ACTIVE' : 'INACTIVE';
            
            const $item = $('<div>')
                .addClass('template-list-item ' + activeClass)
                .attr('data-template-id', template.id)
                .html(`
                    <div class="template-item-header">
                        <span class="template-item-icon dashicons dashicons-category"></span>
                        <span class="template-item-name">${escapeHtml(template.name)}</span>
                        <span class="template-item-badge ${activeClass}">${activeLabel}</span>
                    </div>
                    <div class="template-item-meta">
                        <span>
                            <span class="dashicons dashicons-list-view"></span>
                            ${template.task_count} tasks
                        </span>
                        <span>
                            <span class="dashicons dashicons-admin-users"></span>
                            Used ${template.usage_count} times
                        </span>
                    </div>
                `);
            
            $list.append($item);
        });
    }
    
    /**
     * Filter templates based on search
     */
    function filterTemplates() {
        const searchTerm = $('#template-search').val().toLowerCase();
        
        const filtered = templates.filter(function(template) {
            return template.name.toLowerCase().indexOf(searchTerm) !== -1 ||
                   (template.description && template.description.toLowerCase().indexOf(searchTerm) !== -1);
        });
        
        renderTemplateList(filtered);
    }
    
    /**
     * Select a template
     */
    function selectTemplate(e) {
        e.preventDefault();
        
        const templateId = $(this).attr('data-template-id');
        
        $('.template-list-item').removeClass('active');
        $(this).addClass('active');
        
        loadTemplate(templateId);
    }
    
    /**
     * Load a template for editing
     */
    function loadTemplate(templateId) {
        $.ajax({
            url: wfsTemplateManager.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_get_template',
                nonce: wfsTemplateManager.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success) {
                    populateForm(response.data);
                    showEditor();
                }
            },
            error: function() {
                showNotice('Failed to load template', 'error');
            }
        });
    }
    
    /**
     * Populate form with template data
     */
    function populateForm(template) {
        currentTemplateId = template.id;
        taskCounter = 0;
        
        $('#template-id').val(template.id);
        $('#template-name').val(template.name);
        $('#template-description').val(template.description);
        $('#template-category').val(template.category);
        $('#template-active').prop('checked', template.active);
        $('input[name="execution_mode"][value="' + template.execution_mode + '"]').prop('checked', true);
        
        // Clear tasks
        $('#template-tasks-container').empty();
        
        // Add tasks
        if (template.tasks && template.tasks.length > 0) {
            template.tasks.forEach(function(task) {
                addTask(task);
            });
            handleExecutionModeChange();
        } else {
            showNoTasksMessage();
        }
        
        // Show/hide delete button
        $('#delete-template').show();
    }
    
    /**
     * Create new template
     */
    function createNewTemplate() {
        currentTemplateId = null;
        taskCounter = 0;
        
        $('#template-form')[0].reset();
        $('#template-id').val('');
        $('#template-tasks-container').empty();
        showNoTasksMessage();
        
        // Default to sequential
        $('input[name="execution_mode"][value="sequential"]').prop('checked', true);
        
        // Hide delete button
        $('#delete-template').hide();
        
        showEditor();
    }
    
    /**
     * Show editor, hide welcome
     */
    function showEditor() {
        $('#editor-welcome').hide();
        $('#editor-content').show();
    }
    
    /**
     * Cancel editing
     */
    function cancelEdit() {
        $('#editor-content').hide();
        $('#editor-welcome').show();
        $('.template-list-item').removeClass('active');
        currentTemplateId = null;
    }
    
    /**
     * Add task to template
     */
    function addTask(taskData) {
        taskData = taskData || {};
        
        const taskIndex = taskCounter++;
        const taskNumber = taskIndex + 1;
        
        const template = $('#task-item-template').html();
        const $taskItem = $(template
            .replace(/\{\{index\}\}/g, taskIndex)
            .replace(/\{\{number\}\}/g, taskNumber)
            .replace(/\{\{title\}\}/g, taskData.title || '')
            .replace(/\{\{description\}\}/g, taskData.description || '')
        );
        
        // Set selected values
        if (taskData.status) {
            $taskItem.find('.task-status').val(taskData.status);
        }
        if (taskData.priority) {
            $taskItem.find('.task-priority').val(taskData.priority);
        }
        
        $('#template-tasks-container').append($taskItem);
        
        // Hide no tasks message
        $('.no-tasks-message').remove();
        
        // Update task numbers
        updateTaskNumbers();
        
        // Update dependency dropdowns
        updateDependencyDropdowns();
        
        // Reinitialize sortable
        initializeSortable();
        
        // Scroll to new task
        $taskItem[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    /**
     * Remove task
     */
    function removeTask(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to remove this task?')) {
            return;
        }
        
        $(this).closest('.task-item').remove();
        
        // Update task numbers
        updateTaskNumbers();
        
        // Update dependency dropdowns
        updateDependencyDropdowns();
        
        // Show no tasks message if empty
        if ($('.task-item').length === 0) {
            showNoTasksMessage();
        }
    }
    
    /**
     * Update task numbers
     */
    function updateTaskNumbers() {
        $('.task-item').each(function(index) {
            $(this).find('.task-number').text(index + 1);
            $(this).attr('data-task-index', index);
            
            // Update input names
            $(this).find('input, select, textarea').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/tasks\[\d+\]/, 'tasks[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }
    
    /**
     * Update dependency dropdowns
     */
    function updateDependencyDropdowns() {
        const tasks = [];
        
        $('.task-item').each(function(index) {
            tasks.push({
                index: index,
                title: $(this).find('.task-title').val() || 'Task ' + (index + 1)
            });
        });
        
        $('.task-dependency').each(function(index) {
            const $select = $(this);
            const currentValue = $select.val();
            
            $select.empty();
            $select.append('<option value="">-- No Dependency --</option>');
            
            // Add options for previous tasks only
            tasks.forEach(function(task) {
                if (task.index < index) {
                    $select.append(
                        $('<option>')
                            .val(task.index)
                            .text('Task ' + (task.index + 1) + ': ' + task.title)
                    );
                }
            });
            
            // Restore previous value if valid
            if (currentValue && currentValue < index) {
                $select.val(currentValue);
            }
        });
    }
    
    /**
     * Handle execution mode change
     */
    function handleExecutionModeChange() {
        const mode = $('input[name="execution_mode"]:checked').val();
        
        $('.dependency-field').hide();
        
        if (mode === 'sequential') {
            // Set automatic dependencies for sequential mode
            $('.task-item').each(function(index) {
                if (index > 0) {
                    $(this).find('.task-dependency').val(index - 1);
                }
            });
        } else if (mode === 'custom') {
            // Show dependency fields for custom mode
            $('.dependency-field').show();
        } else {
            // Parallel mode - clear all dependencies
            $('.task-dependency').val('');
        }
    }
    
    /**
     * Initialize sortable for tasks
     */
    function initializeSortable() {
        $('#template-tasks-container').sortable({
            handle: '.task-handle',
            placeholder: 'task-item-placeholder',
            axis: 'y',
            cursor: 'move',
            tolerance: 'pointer',
            update: function() {
                updateTaskNumbers();
                updateDependencyDropdowns();
                handleExecutionModeChange();
            }
        });
    }
    
    /**
     * Save template
     */
    function saveTemplate(e) {
        e.preventDefault();
        
        // Validate
        if (!$('#template-name').val()) {
            showNotice('Please enter a template name', 'error');
            return;
        }
        
        if (!$('#template-category').val()) {
            showNotice('Please select a category', 'error');
            return;
        }
        
        if ($('.task-item').length === 0) {
            showNotice('Please add at least one task', 'error');
            return;
        }
        
        // Validate all tasks have titles
        let valid = true;
        $('.task-title').each(function() {
            if (!$(this).val()) {
                $(this).css('border-color', '#dc3232');
                valid = false;
            } else {
                $(this).css('border-color', '#ddd');
            }
        });
        
        if (!valid) {
            showNotice('Please fill in all task titles', 'error');
            return;
        }
        
        const $saveBtn = $('#save-template');
        $saveBtn.prop('disabled', true).text('Saving...');
        
        const formData = $('#template-form').serialize();
        
        $.ajax({
            url: wfsTemplateManager.ajaxurl,
            type: 'POST',
            data: formData + '&action=wfs_save_template&nonce=' + wfsTemplateManager.nonce,
            success: function(response) {
                if (response.success) {
                    showNotice('Template saved successfully!', 'success');
                    currentTemplateId = response.data.template_id;
                    $('#template-id').val(currentTemplateId);
                    loadTemplates();
                } else {
                    showNotice(response.data || 'Failed to save template', 'error');
                }
            },
            error: function() {
                showNotice('Failed to save template', 'error');
            },
            complete: function() {
                $saveBtn.prop('disabled', false).text('Save Template');
            }
        });
    }
    
    /**
     * Delete template
     */
    function deleteTemplate() {
        if (!currentTemplateId) {
            return;
        }
        
        if (!confirm('Are you sure you want to delete this template? This cannot be undone.')) {
            return;
        }
        
        const $deleteBtn = $('#delete-template');
        $deleteBtn.prop('disabled', true);
        
        $.ajax({
            url: wfsTemplateManager.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_delete_template',
                nonce: wfsTemplateManager.nonce,
                template_id: currentTemplateId
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Template deleted successfully', 'success');
                    cancelEdit();
                    loadTemplates();
                } else {
                    showNotice(response.data || 'Failed to delete template', 'error');
                }
            },
            error: function() {
                showNotice('Failed to delete template', 'error');
            },
            complete: function() {
                $deleteBtn.prop('disabled', false);
            }
        });
    }
    
    /**
     * Show no tasks message
     */
    function showNoTasksMessage() {
        $('#template-tasks-container').html(`
            <div class="no-tasks-message">
                <span class="dashicons dashicons-info"></span>
                <p>No tasks added yet. Click "Add Task" to create your first task.</p>
            </div>
        `);
    }
    
    /**
     * Show notification
     */
    function showNotice(message, type) {
        type = type || 'success';
        
        const $notice = $('<div>')
            .addClass('notice notice-' + type)
            .html('<p>' + message + '</p>')
            .prependTo('.wfs-template-editor');
        
        // Scroll to top
        $('.wfs-template-editor').scrollTop(0);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
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
    
})(jQuery);
