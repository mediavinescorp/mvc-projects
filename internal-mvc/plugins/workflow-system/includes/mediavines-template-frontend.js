/**
 * Media Vines Template System - Frontend JavaScript
 * Adds template functionality to New Task modal
 */

(function($) {
    'use strict';
    
    // Template system state
    const TemplateSystem = {
        templates: {},
        selectedTemplate: null,
        templateTasks: [],
        
        /**
         * Initialize template system
         */
        init: function() {
            this.bindEvents();
            this.loadTemplates();
        },
        
        /**
         * Bind template-related events
         */
        bindEvents: function() {
            // Template selection toggle
            $(document).on('click', '#use-template-toggle', this.toggleTemplateView.bind(this));
            
            // Template category selection
            $(document).on('change', '#template-category-select', this.handleCategoryChange.bind(this));
            
            // Template workflow selection
            $(document).on('change', '#template-workflow-select', this.loadTemplateTasks.bind(this));
            
            // Back to manual entry
            $(document).on('click', '#back-to-manual', this.backToManual.bind(this));
            
            // Clear template when modal closes
            $(document).on('click', '.close-modal, #cancel-task', this.clearTemplate.bind(this));
        },
        
        /**
         * Load available templates
         */
        loadTemplates: function() {
            $.ajax({
                url: mediavinesWorkflow.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_workflow_templates',
                    nonce: mediavinesWorkflow.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.templates = response.data.templates;
                        this.renderTemplateUI();
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Failed to load templates:', error);
                }
            });
        },
        
        /**
         * Render template UI in modal
         */
        renderTemplateUI: function() {
            const $modal = $('#new-task-modal');
            const $manualForm = $('#manual-task-form');
            
            // Create template selection section
            const templateHTML = `
                <div id="template-section" style="display: none;">
                    <div class="template-selection-header">
                        <h3 style="color: #D4AF37; margin: 0 0 15px 0;">
                            ⭐ Use Workflow Template
                        </h3>
                        <p style="color: #666; margin: 0 0 20px 0;">
                            Select a template to quickly create multiple tasks at once
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label for="template-category-select">Template Category</label>
                        <select id="template-category-select" class="form-control">
                            <option value="">Select a category...</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="template-workflow-group" style="display: none;">
                        <label for="template-workflow-select">Select Template</label>
                        <select id="template-workflow-select" class="form-control">
                            <option value="">Select a template...</option>
                        </select>
                    </div>
                    
                    <div id="template-tasks-preview" style="display: none;">
                        <h4 style="margin: 20px 0 10px 0;">Template Tasks</h4>
                        <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                            Assign team members to each task (optional)
                        </p>
                        <div id="template-tasks-list"></div>
                    </div>
                    
                    <div class="modal-actions" style="margin-top: 20px;">
                        <button type="button" id="back-to-manual" class="btn-secondary">
                            ← Back to Manual Entry
                        </button>
                        <button type="button" id="create-from-template" class="btn-primary" style="display: none;">
                            Create Tasks from Template
                        </button>
                    </div>
                </div>
            `;
            
            // Insert template section after manual form
            $manualForm.after(templateHTML);
            
            // Add toggle button before manual form
            const toggleHTML = `
                <div id="entry-mode-toggle" style="text-align: center; margin-bottom: 20px;">
                    <button type="button" id="use-template-toggle" class="btn-link" style="color: #D4AF37;">
                        ⭐ Use a Template Instead
                    </button>
                </div>
            `;
            $manualForm.before(toggleHTML);
            
            // Populate category dropdown
            this.populateCategoryDropdown();
        },
        
        /**
         * Populate category dropdown
         */
        populateCategoryDropdown: function() {
            const $select = $('#template-category-select');
            $select.find('option:not(:first)').remove();
            
            const categories = Object.keys(this.templates);
            categories.forEach(function(category) {
                const count = this.templates[category].length;
                $select.append(`
                    <option value="${category}">
                        ${category} (${count} template${count !== 1 ? 's' : ''})
                    </option>
                `);
            }.bind(this));
        },
        
        /**
         * Toggle between template and manual view
         */
        toggleTemplateView: function(e) {
            e.preventDefault();
            
            const $manualForm = $('#manual-task-form');
            const $templateSection = $('#template-section');
            const $toggle = $('#entry-mode-toggle');
            
            if ($templateSection.is(':visible')) {
                // Switch to manual
                $templateSection.hide();
                $manualForm.show();
                $toggle.find('button').html('⭐ Use a Template Instead');
            } else {
                // Switch to template
                $manualForm.hide();
                $templateSection.show();
                $toggle.find('button').html('← Manual Entry');
            }
        },
        
        /**
         * Handle category selection
         */
        handleCategoryChange: function(e) {
            const category = $(e.target).val();
            const $workflowGroup = $('#template-workflow-group');
            const $workflowSelect = $('#template-workflow-select');
            
            if (!category) {
                $workflowGroup.hide();
                this.clearTemplateTasks();
                return;
            }
            
            // Populate workflow dropdown
            $workflowSelect.find('option:not(:first)').remove();
            
            const templates = this.templates[category] || [];
            templates.forEach(function(template) {
                $workflowSelect.append(`
                    <option value="${template.id}" data-task-count="${template.task_count}">
                        ${template.title} (${template.task_count} task${template.task_count !== 1 ? 's' : ''})
                    </option>
                `);
            });
            
            $workflowGroup.show();
            this.clearTemplateTasks();
        },
        
        /**
         * Load template tasks
         */
        loadTemplateTasks: function(e) {
            const templateId = $(e.target).val();
            
            if (!templateId) {
                this.clearTemplateTasks();
                return;
            }
            
            this.selectedTemplate = parseInt(templateId);
            
            // Show loading
            $('#template-tasks-preview').show();
            $('#template-tasks-list').html('<p style="text-align: center; color: #666;">Loading tasks...</p>');
            
            $.ajax({
                url: mediavinesWorkflow.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_template_tasks',
                    nonce: mediavinesWorkflow.nonce,
                    template_id: templateId
                },
                success: function(response) {
                    if (response.success) {
                        this.templateTasks = response.data.tasks;
                        this.renderTemplateTasks(response.data.tasks);
                    } else {
                        this.showError('Failed to load template tasks');
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Failed to load template tasks:', error);
                    this.showError('Failed to load template tasks');
                }.bind(this)
            });
        },
        
        /**
         * Render template tasks with assignment options
         */
        renderTemplateTasks: function(tasks) {
            const $list = $('#template-tasks-list');
            $list.empty();
            
            if (tasks.length === 0) {
                $list.html('<p style="color: #999;">This template has no tasks.</p>');
                $('#create-from-template').hide();
                return;
            }
            
            // Get available users for assignment
            const users = this.getAvailableUsers();
            
            tasks.forEach(function(task, index) {
                const taskHTML = `
                    <div class="template-task-item" style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; background: #f9f9f9;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <strong>${index + 1}. ${task.title}</strong>
                                ${task.description ? `<p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">${task.description}</p>` : ''}
                            </div>
                            <div style="margin-left: 15px; min-width: 200px;">
                                <select class="template-task-assignment form-control" data-task-id="${task.id}" style="font-size: 13px;">
                                    <option value="">Assign later...</option>
                                    ${users.map(u => `<option value="${u.id}">${u.name}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                `;
                $list.append(taskHTML);
            });
            
            $('#create-from-template').show();
            this.bindCreateFromTemplate();
        },
        
        /**
         * Get available users for assignment
         */
        getAvailableUsers: function() {
            // Get users from existing dropdown in manual form
            const users = [];
            $('#assigned-to option').each(function() {
                const value = $(this).val();
                if (value) {
                    users.push({
                        id: value,
                        name: $(this).text()
                    });
                }
            });
            return users;
        },
        
        /**
         * Bind create from template button
         */
        bindCreateFromTemplate: function() {
            $('#create-from-template').off('click').on('click', function(e) {
                e.preventDefault();
                this.createTasksFromTemplate();
            }.bind(this));
        },
        
        /**
         * Create tasks from template
         */
        createTasksFromTemplate: function() {
            const clientId = $('#client-id').val();
            const workflowId = $('#workflow-id').val();
            
            if (!clientId || !workflowId) {
                this.showError('Please select a client and workflow first');
                return;
            }
            
            // Collect task assignments
            const assignments = {};
            $('.template-task-assignment').each(function() {
                const taskId = $(this).data('task-id');
                const assignedTo = $(this).val();
                if (assignedTo) {
                    assignments[taskId] = assignedTo;
                }
            });
            
            // Disable button and show loading
            const $btn = $('#create-from-template');
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Creating tasks...');
            
            $.ajax({
                url: mediavinesWorkflow.ajaxurl,
                type: 'POST',
                data: {
                    action: 'use_template',
                    nonce: mediavinesWorkflow.nonce,
                    template_id: this.selectedTemplate,
                    workflow_id: workflowId,
                    task_assignments: assignments
                },
                success: function(response) {
                    if (response.success) {
                        this.showSuccess(response.data.message);
                        
                        // Close modal and refresh
                        setTimeout(function() {
                            $('#new-task-modal').removeClass('active');
                            location.reload();
                        }, 1500);
                    } else {
                        this.showError(response.data || 'Failed to create tasks');
                        $btn.prop('disabled', false).text(originalText);
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Failed to create tasks from template:', error);
                    this.showError('Failed to create tasks from template');
                    $btn.prop('disabled', false).text(originalText);
                }.bind(this)
            });
        },
        
        /**
         * Clear template tasks
         */
        clearTemplateTasks: function() {
            $('#template-tasks-preview').hide();
            $('#template-tasks-list').empty();
            $('#create-from-template').hide();
            this.selectedTemplate = null;
            this.templateTasks = [];
        },
        
        /**
         * Back to manual entry
         */
        backToManual: function(e) {
            e.preventDefault();
            
            // Reset template section
            $('#template-category-select').val('');
            $('#template-workflow-select').val('').closest('.form-group').hide();
            this.clearTemplateTasks();
            
            // Show manual form
            $('#template-section').hide();
            $('#manual-task-form').show();
            $('#entry-mode-toggle button').html('⭐ Use a Template Instead');
        },
        
        /**
         * Clear template when modal closes
         */
        clearTemplate: function() {
            this.backToManual({preventDefault: function(){}});
        },
        
        /**
         * Show success message
         */
        showSuccess: function(message) {
            // Use existing notification system if available
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, 'success');
            } else {
                alert(message);
            }
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            // Use existing notification system if available
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, 'error');
            } else {
                alert(message);
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        // Wait a bit to ensure modal is loaded
        setTimeout(function() {
            if ($('#new-task-modal').length) {
                TemplateSystem.init();
            }
        }, 500);
    });
    
})(jQuery);
