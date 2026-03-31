<div id="wfs-resources-hub" class="wfs-resources-container">
    
    <!-- Hub View: List of resources with action buttons -->
    <div id="wfs-resources-list-view" class="wfs-view active">
        <div class="wfs-resources-header">
            <h1>Internal Resources Hub</h1>
            <div class="wfs-action-buttons">
                <button id="wfs-create-resource-btn" class="wfs-btn wfs-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span> Create New Post
                </button>
            </div>
        </div>
        
        <!-- Search and Filters -->
        <div class="wfs-resources-filters">
            <div class="wfs-filter-row">
                <div class="wfs-search-box">
                    <input type="text" id="wfs-resource-search" placeholder="Search resources...">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <div class="wfs-filter-group">
                    <select id="wfs-filter-client">
                        <option value="">All Clients</option>
                    </select>
                    <select id="wfs-filter-category">
                        <option value="">All Categories</option>
                    </select>
                    <button id="wfs-clear-filters" class="wfs-btn wfs-btn-secondary">Clear Filters</button>
                </div>
            </div>
        </div>
        
        <!-- Resources Table -->
        <div class="wfs-resources-table-wrapper">
            <table id="wfs-resources-table" class="wfs-table">
                <thead>
                    <tr>
                        <th data-sort="title" class="sortable">
                            Title <span class="sort-indicator"></span>
                        </th>
                        <th data-sort="client">
                            Client(s)
                        </th>
                        <th data-sort="category">
                            Category
                        </th>
                        <th data-sort="author" class="sortable">
                            Author <span class="sort-indicator"></span>
                        </th>
                        <th data-sort="date" class="sortable sorted-desc">
                            Date Posted <span class="sort-indicator">▼</span>
                        </th>
                        <th data-sort="modified" class="sortable">
                            Last Modified <span class="sort-indicator"></span>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="wfs-resources-tbody">
                    <tr class="wfs-loading-row">
                        <td colspan="7" class="text-center">
                            <div class="wfs-spinner"></div>
                            Loading resources...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div id="wfs-no-resources" class="wfs-no-results" style="display: none;">
            <p>No resources found. Create your first resource to get started!</p>
        </div>
    </div>
    
    <!-- Create/Edit Resource Form View -->
    <div id="wfs-resource-form-view" class="wfs-view">
        <div class="wfs-resources-header">
            <h1 id="wfs-form-title">Create New Resource</h1>
            <button id="wfs-back-to-list" class="wfs-btn wfs-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt2"></span> Back to Resources
            </button>
        </div>
        
        <form id="wfs-resource-form" class="wfs-resource-form">
            <input type="hidden" id="wfs-resource-id" value="0">
            
            <div class="wfs-form-group">
                <label for="wfs-resource-title">Title <span class="required">*</span></label>
                <input type="text" id="wfs-resource-title" class="wfs-input" required>
            </div>
            
            <div class="wfs-form-row">
                <div class="wfs-form-group wfs-form-half">
                    <label for="wfs-resource-clients">Associated Client(s)</label>
                    <select id="wfs-resource-clients" class="wfs-select" multiple size="5">
                        <option value="">Loading clients...</option>
                    </select>
                    <small>Hold Ctrl (Cmd on Mac) to select multiple clients</small>
                </div>
                
                <div class="wfs-form-group wfs-form-half">
                    <label for="wfs-resource-categories">Categories</label>
                    <select id="wfs-resource-categories" class="wfs-select" multiple size="5">
                        <option value="">Loading categories...</option>
                    </select>
                    <small>Hold Ctrl (Cmd on Mac) to select multiple categories</small>
                </div>
            </div>
            
            <div class="wfs-form-group">
                <label for="wfs-resource-content">Content</label>
                <?php 
                wp_editor('', 'wfs_resource_content', array(
                    'textarea_name' => 'wfs_resource_content',
                    'textarea_rows' => 15,
                    'media_buttons' => true,
                    'teeny' => false,
                    'tinymce' => array(
                        'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                        'toolbar2' => 'styleselect,forecolor,backcolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
                    ),
                    'quicktags' => true
                ));
                ?>
            </div>
            
            <div class="wfs-form-meta" id="wfs-resource-meta" style="display: none;">
                <div class="wfs-meta-row">
                    <div class="wfs-meta-item">
                        <strong>Created by:</strong> <span id="wfs-meta-author"></span>
                    </div>
                    <div class="wfs-meta-item">
                        <strong>Date Posted:</strong> <span id="wfs-meta-date-created"></span>
                    </div>
                </div>
                <div class="wfs-meta-row">
                    <div class="wfs-meta-item">
                        <strong>Last Modified:</strong> <span id="wfs-meta-date-modified"></span>
                    </div>
                    <div class="wfs-meta-item">
                        <strong>Modified by:</strong> <span id="wfs-meta-modified-by"></span>
                    </div>
                </div>
            </div>
            
            <div class="wfs-form-actions">
                <button type="submit" class="wfs-btn wfs-btn-primary wfs-btn-large">
                    <span class="dashicons dashicons-saved"></span> Save Resource
                </button>
                <button type="button" id="wfs-cancel-form" class="wfs-btn wfs-btn-secondary wfs-btn-large">
                    Cancel
                </button>
                <button type="button" id="wfs-delete-resource" class="wfs-btn wfs-btn-danger wfs-btn-large" style="display: none;">
                    <span class="dashicons dashicons-trash"></span> Delete Resource
                </button>
            </div>
        </form>
    </div>
    
    <!-- Individual Resource View -->
    <div id="wfs-resource-view" class="wfs-view">
        <div class="wfs-resources-header">
            <h1 id="wfs-view-title">Resource Title</h1>
            <div class="wfs-action-buttons">
                <button id="wfs-edit-current-resource" class="wfs-btn wfs-btn-primary">
                    <span class="dashicons dashicons-edit"></span> Edit
                </button>
                <button id="wfs-back-to-list-from-view" class="wfs-btn wfs-btn-secondary">
                    <span class="dashicons dashicons-arrow-left-alt2"></span> Back to Resources
                </button>
            </div>
        </div>
        
        <div class="wfs-resource-content-wrapper">
            <div class="wfs-resource-meta-box">
                <div class="wfs-meta-grid">
                    <div class="wfs-meta-item">
                        <strong>Client(s):</strong>
                        <span id="wfs-view-clients"></span>
                    </div>
                    <div class="wfs-meta-item">
                        <strong>Categories:</strong>
                        <span id="wfs-view-categories"></span>
                    </div>
                    <div class="wfs-meta-item">
                        <strong>Author:</strong>
                        <span id="wfs-view-author"></span>
                    </div>
                    <div class="wfs-meta-item">
                        <strong>Date Posted:</strong>
                        <span id="wfs-view-date-created"></span>
                    </div>
                    <div class="wfs-meta-item">
                        <strong>Last Modified:</strong>
                        <span id="wfs-view-date-modified"></span>
                    </div>
                    <div class="wfs-meta-item">
                        <strong>Modified By:</strong>
                        <span id="wfs-view-modified-by"></span>
                    </div>
                </div>
            </div>
            
            <div class="wfs-resource-content" id="wfs-view-content">
                <!-- Resource content will be loaded here -->
            </div>
        </div>
    </div>
    
</div>

<!-- Delete Confirmation Modal -->
<div id="wfs-delete-modal" class="wfs-modal" style="display: none;">
    <div class="wfs-modal-content">
        <div class="wfs-modal-header">
            <h2>Confirm Delete</h2>
            <button class="wfs-modal-close">&times;</button>
        </div>
        <div class="wfs-modal-body">
            <p>Are you sure you want to delete this resource? This action cannot be undone.</p>
        </div>
        <div class="wfs-modal-footer">
            <button id="wfs-confirm-delete" class="wfs-btn wfs-btn-danger">Delete</button>
            <button id="wfs-cancel-delete" class="wfs-btn wfs-btn-secondary">Cancel</button>
        </div>
    </div>
</div>
