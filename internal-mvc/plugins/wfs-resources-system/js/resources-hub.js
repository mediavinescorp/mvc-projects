jQuery(document).ready(function($) {
    'use strict';
    
    // State management
    const state = {
        resources: [],
        filteredResources: [],
        clients: [],
        categories: [],
        currentSort: { column: 'date', order: 'desc' },
        currentResourceId: 0,
        currentView: 'list'
    };
    
    // Initialize
    init();
    
    function init() {
        loadClients();
        loadCategories();
        loadResources();
        bindEvents();
    }
    
    // Event Bindings
    function bindEvents() {
        // Navigation buttons
        $('#wfs-create-resource-btn').on('click', showCreateForm);
        $('#wfs-back-to-list, #wfs-cancel-form').on('click', showListView);
        $('#wfs-back-to-list-from-view').on('click', showListView);
        
        // Form submission
        $('#wfs-resource-form').on('submit', saveResource);
        
        // Delete resource
        $('#wfs-delete-resource').on('click', showDeleteModal);
        $('#wfs-confirm-delete').on('click', deleteResource);
        $('#wfs-cancel-delete, .wfs-modal-close').on('click', hideDeleteModal);
        
        // Search and filters
        $('#wfs-resource-search').on('input', debounce(applyFilters, 300));
        $('#wfs-filter-client, #wfs-filter-category').on('change', applyFilters);
        $('#wfs-clear-filters').on('click', clearFilters);
        
        // Table sorting
        $('.wfs-table thead th.sortable').on('click', function() {
            const column = $(this).data('sort');
            handleSort(column);
        });
        
        // Edit from view
        $('#wfs-edit-current-resource').on('click', function() {
            showEditForm(state.currentResourceId);
        });
        
        // Modal close on background click
        $('#wfs-delete-modal').on('click', function(e) {
            if ($(e.target).is('#wfs-delete-modal')) {
                hideDeleteModal();
            }
        });
    }
    
    // Load Data
    function loadClients() {
        $.ajax({
            url: wfsResources.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_get_clients',
                nonce: wfsResources.nonce
            },
            success: function(response) {
                if (response.success) {
                    state.clients = response.data;
                    populateClientSelects();
                }
            }
        });
    }
    
    function loadCategories() {
        $.ajax({
            url: wfsResources.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_get_categories',
                nonce: wfsResources.nonce
            },
            success: function(response) {
                if (response.success) {
                    state.categories = response.data;
                    populateCategorySelects();
                }
            }
        });
    }
    
    function loadResources() {
        $.ajax({
            url: wfsResources.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_get_resources',
                nonce: wfsResources.nonce,
                orderby: state.currentSort.column,
                order: state.currentSort.order
            },
            success: function(response) {
                if (response.success) {
                    state.resources = response.data;
                    state.filteredResources = response.data;
                    renderResourcesTable();
                }
            }
        });
    }
    
    // Populate Selects
    function populateClientSelects() {
        const filterSelect = $('#wfs-filter-client');
        const formSelect = $('#wfs-resource-clients');
        
        // Filter dropdown
        filterSelect.empty().append('<option value="">All Clients</option>');
        state.clients.forEach(client => {
            filterSelect.append(`<option value="${client.id}">${client.name}</option>`);
        });
        
        // Form multi-select
        formSelect.empty();
        state.clients.forEach(client => {
            formSelect.append(`<option value="${client.id}">${client.name}</option>`);
        });
    }
    
    function populateCategorySelects() {
        const filterSelect = $('#wfs-filter-category');
        const formSelect = $('#wfs-resource-categories');
        
        // Filter dropdown
        filterSelect.empty().append('<option value="">All Categories</option>');
        state.categories.forEach(category => {
            filterSelect.append(`<option value="${category.id}">${category.name}</option>`);
        });
        
        // Form multi-select
        formSelect.empty();
        state.categories.forEach(category => {
            formSelect.append(`<option value="${category.id}">${category.name}</option>`);
        });
    }
    
    // Render Table
    function renderResourcesTable() {
        const tbody = $('#wfs-resources-tbody');
        const noResults = $('#wfs-no-resources');
        
        if (state.filteredResources.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center">No resources found</td></tr>');
            noResults.show();
            return;
        }
        
        noResults.hide();
        tbody.empty();
        
        state.filteredResources.forEach(resource => {
            const row = $('<tr>');
            
            // Title
            row.append(`
                <td class="resource-title" data-id="${resource.id}">
                    ${escapeHtml(resource.title)}
                </td>
            `);
            
            // Clients
            const clientsHtml = resource.clients.length > 0
                ? resource.clients.map(c => `<span class="wfs-badge">${escapeHtml(c)}</span>`).join('')
                : '<span class="wfs-badge">None</span>';
            row.append(`<td>${clientsHtml}</td>`);
            
            // Categories
            const categoriesHtml = resource.categories.length > 0
                ? resource.categories.map(c => `<span class="wfs-badge">${escapeHtml(c)}</span>`).join('')
                : '<span class="wfs-badge">None</span>';
            row.append(`<td>${categoriesHtml}</td>`);
            
            // Author
            row.append(`<td>${escapeHtml(resource.author)}</td>`);
            
            // Date Posted
            row.append(`<td>${formatDate(resource.date_created)}</td>`);
            
            // Last Modified
            const modifiedText = resource.modified_by 
                ? `${formatDate(resource.date_modified)}<br><small>by ${escapeHtml(resource.modified_by)}</small>`
                : formatDate(resource.date_modified);
            row.append(`<td>${modifiedText}</td>`);
            
            // Actions
            row.append(`
                <td>
                    <a href="#" class="wfs-action-link wfs-edit-resource" data-id="${resource.id}">Edit</a>
                    <a href="#" class="wfs-action-link wfs-view-resource" data-id="${resource.id}">View</a>
                </td>
            `);
            
            tbody.append(row);
        });
        
        // Bind row click events
        tbody.find('.resource-title').on('click', function() {
            const id = $(this).data('id');
            showResourceView(id);
        });
        
        tbody.find('.wfs-edit-resource').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            showEditForm(id);
        });
        
        tbody.find('.wfs-view-resource').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            showResourceView(id);
        });
    }
    
    // Filtering
    function applyFilters() {
        const searchTerm = $('#wfs-resource-search').val().toLowerCase();
        const clientFilter = parseInt($('#wfs-filter-client').val()) || 0;
        const categoryFilter = parseInt($('#wfs-filter-category').val()) || 0;
        
        state.filteredResources = state.resources.filter(resource => {
            // Search filter
            if (searchTerm) {
                const searchable = [
                    resource.title,
                    resource.clients.join(' '),
                    resource.categories.join(' '),
                    resource.author
                ].join(' ').toLowerCase();
                
                if (!searchable.includes(searchTerm)) {
                    return false;
                }
            }
            
            // Client filter
            if (clientFilter > 0) {
                if (!resource.client_ids || !resource.client_ids.includes(clientFilter)) {
                    return false;
                }
            }
            
            // Category filter
            if (categoryFilter > 0) {
                const categoryMatch = state.categories.find(c => c.id === categoryFilter);
                if (!categoryMatch || !resource.categories.includes(categoryMatch.name)) {
                    return false;
                }
            }
            
            return true;
        });
        
        sortResources();
        renderResourcesTable();
    }
    
    function clearFilters() {
        $('#wfs-resource-search').val('');
        $('#wfs-filter-client, #wfs-filter-category').val('');
        applyFilters();
    }
    
    // Sorting
    function handleSort(column) {
        if (state.currentSort.column === column) {
            // Toggle order
            state.currentSort.order = state.currentSort.order === 'asc' ? 'desc' : 'asc';
        } else {
            // New column
            state.currentSort.column = column;
            state.currentSort.order = 'asc';
        }
        
        // Update UI
        $('.wfs-table thead th').removeClass('sorted-asc sorted-desc');
        const th = $(`.wfs-table thead th[data-sort="${column}"]`);
        th.addClass('sorted-' + state.currentSort.order);
        
        sortResources();
        renderResourcesTable();
    }
    
    function sortResources() {
        state.filteredResources.sort((a, b) => {
            let aVal, bVal;
            
            switch(state.currentSort.column) {
                case 'title':
                    aVal = a.title.toLowerCase();
                    bVal = b.title.toLowerCase();
                    break;
                case 'author':
                    aVal = a.author.toLowerCase();
                    bVal = b.author.toLowerCase();
                    break;
                case 'date':
                    aVal = new Date(a.date_created);
                    bVal = new Date(b.date_created);
                    break;
                case 'modified':
                    aVal = new Date(a.date_modified);
                    bVal = new Date(b.date_modified);
                    break;
                default:
                    return 0;
            }
            
            if (aVal < bVal) return state.currentSort.order === 'asc' ? -1 : 1;
            if (aVal > bVal) return state.currentSort.order === 'asc' ? 1 : -1;
            return 0;
        });
    }
    
    // View Management
    function showListView() {
        $('.wfs-view').removeClass('active');
        $('#wfs-resources-list-view').addClass('active');
        state.currentView = 'list';
        resetForm();
    }
    
    function showCreateForm() {
        $('.wfs-view').removeClass('active');
        $('#wfs-resource-form-view').addClass('active');
        state.currentView = 'form';
        resetForm();
        $('#wfs-form-title').text('Create New Resource');
        $('#wfs-delete-resource').hide();
        $('#wfs-resource-meta').hide();
    }
    
    function showEditForm(resourceId) {
        $.ajax({
            url: wfsResources.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_get_resource',
                nonce: wfsResources.nonce,
                resource_id: resourceId
            },
            success: function(response) {
                if (response.success) {
                    const resource = response.data;
                    
                    $('.wfs-view').removeClass('active');
                    $('#wfs-resource-form-view').addClass('active');
                    state.currentView = 'form';
                    state.currentResourceId = resourceId;
                    
                    $('#wfs-form-title').text('Edit Resource');
                    $('#wfs-resource-id').val(resource.id);
                    $('#wfs-resource-title').val(resource.title);
                    
                    // Set content in TinyMCE
                    if (typeof tinymce !== 'undefined') {
                        const editor = tinymce.get('wfs_resource_content');
                        if (editor) {
                            editor.setContent(resource.content);
                        }
                    }
                    $('#wfs_resource_content').val(resource.content);
                    
                    // Select clients
                    $('#wfs-resource-clients').val(resource.client_ids);
                    
                    // Select categories
                    $('#wfs-resource-categories').val(resource.category_ids);
                    
                    // Show meta info
                    const author = state.resources.find(r => r.id === resourceId);
                    if (author) {
                        $('#wfs-meta-author').text(author.author);
                        $('#wfs-meta-date-created').text(formatDate(author.date_created));
                        $('#wfs-meta-date-modified').text(formatDate(author.date_modified));
                        $('#wfs-meta-modified-by').text(author.modified_by || 'N/A');
                        $('#wfs-resource-meta').show();
                    }
                    
                    $('#wfs-delete-resource').show();
                }
            }
        });
    }
    
    function showResourceView(resourceId) {
        const resource = state.resources.find(r => r.id === resourceId);
        if (!resource) return;
        
        state.currentResourceId = resourceId;
        
        $('.wfs-view').removeClass('active');
        $('#wfs-resource-view').addClass('active');
        state.currentView = 'view';
        
        $('#wfs-view-title').text(resource.title);
        $('#wfs-view-clients').html(
            resource.clients.length > 0
                ? resource.clients.map(c => `<span class="wfs-badge">${escapeHtml(c)}</span>`).join(' ')
                : 'None'
        );
        $('#wfs-view-categories').html(
            resource.categories.length > 0
                ? resource.categories.map(c => `<span class="wfs-badge">${escapeHtml(c)}</span>`).join(' ')
                : 'None'
        );
        $('#wfs-view-author').text(resource.author);
        $('#wfs-view-date-created').text(formatDate(resource.date_created));
        $('#wfs-view-date-modified').text(formatDate(resource.date_modified));
        $('#wfs-view-modified-by').text(resource.modified_by || 'N/A');
        $('#wfs-view-content').html(resource.content);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
    // Form Operations
    function saveResource(e) {
        e.preventDefault();
        
        const resourceId = parseInt($('#wfs-resource-id').val()) || 0;
        const title = $('#wfs-resource-title').val().trim();
        
        if (!title) {
            alert('Please enter a title for the resource.');
            return;
        }
        
        // Get content from TinyMCE
        let content = '';
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get('wfs_resource_content');
            if (editor) {
                content = editor.getContent();
            }
        } else {
            content = $('#wfs_resource_content').val();
        }
        
        const clientIds = $('#wfs-resource-clients').val() || [];
        const categoryIds = $('#wfs-resource-categories').val() || [];
        
        const submitBtn = $('#wfs-resource-form button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="wfs-spinner"></span> Saving...');
        
        $.ajax({
            url: wfsResources.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_save_resource',
                nonce: wfsResources.nonce,
                resource_id: resourceId,
                title: title,
                content: content,
                client_ids: clientIds,
                category_ids: categoryIds
            },
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalText);
                
                if (response.success) {
                    alert(response.data.message);
                    loadResources();
                    showListView();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                submitBtn.prop('disabled', false).html(originalText);
                alert('An error occurred while saving the resource.');
            }
        });
    }
    
    function resetForm() {
        $('#wfs-resource-form')[0].reset();
        $('#wfs-resource-id').val('0');
        
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get('wfs_resource_content');
            if (editor) {
                editor.setContent('');
            }
        }
        
        state.currentResourceId = 0;
    }
    
    // Delete Operations
    function showDeleteModal() {
        $('#wfs-delete-modal').fadeIn(200);
    }
    
    function hideDeleteModal() {
        $('#wfs-delete-modal').fadeOut(200);
    }
    
    function deleteResource() {
        const resourceId = state.currentResourceId;
        
        if (!resourceId) return;
        
        const confirmBtn = $('#wfs-confirm-delete');
        const originalText = confirmBtn.html();
        confirmBtn.prop('disabled', true).html('<span class="wfs-spinner"></span> Deleting...');
        
        $.ajax({
            url: wfsResources.ajaxurl,
            type: 'POST',
            data: {
                action: 'wfs_delete_resource',
                nonce: wfsResources.nonce,
                resource_id: resourceId
            },
            success: function(response) {
                confirmBtn.prop('disabled', false).html(originalText);
                hideDeleteModal();
                
                if (response.success) {
                    alert('Resource deleted successfully');
                    loadResources();
                    showListView();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                confirmBtn.prop('disabled', false).html(originalText);
                hideDeleteModal();
                alert('An error occurred while deleting the resource.');
            }
        });
    }
    
    // Utility Functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
