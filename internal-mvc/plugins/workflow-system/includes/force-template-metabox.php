<?php
/**
 * NUCLEAR OPTION - Force Meta Box
 * This bypasses everything and directly hooks meta boxes
 */

// Add meta box on EVERY possible hook with HIGHEST priority
add_action('add_meta_boxes', 'force_template_metabox', PHP_INT_MAX);
add_action('add_meta_boxes_workflow', 'force_template_metabox', PHP_INT_MAX);
add_action('do_meta_boxes', 'force_template_metabox', 1);

function force_template_metabox() {
    // Remove any conflicting boxes
    remove_meta_box('workflow_template_settings', 'workflow', 'side');
    remove_meta_box('workflow_template_settings', 'workflow', 'normal');
    remove_meta_box('workflow_template_settings', 'workflow', 'advanced');
    
    // Add it fresh - SIDE position
    add_meta_box(
        'workflow_template_settings',
        '⭐ TEMPLATE SETTINGS',
        'render_forced_template_metabox',
        'workflow',
        'side',
        'high'
    );
    
    // Also add to NORMAL position as backup
    add_meta_box(
        'workflow_template_settings_normal',
        '⭐ TEMPLATE SETTINGS (Backup)',
        'render_forced_template_metabox',
        'workflow',
        'normal',
        'high'
    );
}

function render_forced_template_metabox($post) {
    wp_nonce_field('workflow_template_meta', 'workflow_template_nonce');
    
    $is_template = get_post_meta($post->ID, '_is_template', true);
    $usage_count = get_post_meta($post->ID, '_template_usage_count', true);
    
    ?>
    <div style="padding: 15px; background: #fff3cd; border: 2px solid #d4af37; border-radius: 5px;">
        <p style="margin: 0 0 15px 0; font-weight: bold; color: #d4af37;">
            🎉 SUCCESS! Template system is working!
        </p>
        
        <label style="display: block; margin-bottom: 15px; cursor: pointer;">
            <input type="checkbox" 
                   name="is_template" 
                   id="is_template_checkbox"
                   value="1" 
                   <?php checked($is_template, '1'); ?>
                   style="margin-right: 8px;">
            <strong>This is a template workflow</strong>
        </label>
        
        <p style="margin: 0 0 15px 0; font-size: 12px; color: #666;">
            Templates can be used in the dashboard to quickly create multiple tasks at once.
        </p>
        
        <?php if ($is_template && $usage_count): ?>
        <p style="margin: 0 0 15px 0; padding: 10px; background: #f0f0f0; border-radius: 3px;">
            <strong>📊 Used:</strong> <?php echo intval($usage_count); ?> times
        </p>
        <?php endif; ?>
        
        <div id="template-category-section" style="<?php echo $is_template ? '' : 'display: none;'; ?>">
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">
            
            <p style="margin: 0 0 8px 0;">
                <strong>📂 Template Category:</strong>
            </p>
            
            <?php
            $terms = wp_get_post_terms($post->ID, 'template_category');
            $selected = !empty($terms) && !is_wp_error($terms) ? $terms[0]->term_id : '';
            
            wp_dropdown_categories(array(
                'taxonomy' => 'template_category',
                'name' => 'template_category',
                'id' => 'template_category_select',
                'selected' => $selected,
                'hide_empty' => false,
                'show_option_none' => '— Select Category —',
                'option_none_value' => '',
                'class' => 'widefat'
            ));
            ?>
            
            <p style="margin: 10px 0 0 0; font-size: 11px; color: #666;">
                Categories help organize templates in the dashboard.
            </p>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#is_template_checkbox').on('change', function() {
            if ($(this).is(':checked')) {
                $('#template-category-section').slideDown(200);
            } else {
                $('#template-category-section').slideUp(200);
            }
        });
    });
    </script>
    <?php
}

// Save handler
add_action('save_post_workflow', 'save_forced_template_meta', 10, 2);
add_action('save_post', 'save_forced_template_meta_backup', 10, 2);

function save_forced_template_meta($post_id, $post) {
    save_forced_template_meta_backup($post_id, $post);
}

function save_forced_template_meta_backup($post_id, $post) {
    // Only for workflows
    if (!isset($post->post_type) || $post->post_type !== 'workflow') {
        return;
    }
    
    // Check nonce
    if (!isset($_POST['workflow_template_nonce']) || 
        !wp_verify_nonce($_POST['workflow_template_nonce'], 'workflow_template_meta')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save template flag
    $is_template = isset($_POST['is_template']) ? '1' : '0';
    update_post_meta($post_id, '_is_template', $is_template);
    
    // Initialize usage count
    if ($is_template === '1' && !get_post_meta($post_id, '_template_usage_count', true)) {
        update_post_meta($post_id, '_template_usage_count', 0);
    }
    
    // Save category
    if ($is_template === '1' && isset($_POST['template_category']) && !empty($_POST['template_category'])) {
        wp_set_post_terms($post_id, array(intval($_POST['template_category'])), 'template_category');
    } else {
        wp_set_post_terms($post_id, array(), 'template_category');
    }
}

// Show admin notice
add_action('admin_notices', function() {
    global $post_type, $pagenow;
    if ($post_type === 'workflow' && ($pagenow === 'post.php' || $pagenow === 'post-new.php')) {
        echo '<div class="notice notice-success">';
        echo '<p><strong>✅ NUCLEAR OPTION LOADED</strong> - Template meta boxes should appear in sidebar AND below editor!</p>';
        echo '</div>';
    }
});
