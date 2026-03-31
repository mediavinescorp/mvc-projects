<?php
/**
 * DIRECT META BOX TEST
 * This file tests meta box registration directly
 * Add this to your workflow-system.php load_dependencies() temporarily
 */

// Force meta box registration
add_action('add_meta_boxes', function() {
    add_meta_box(
        'test_template_box',
        '🧪 TEST - Template System',
        function($post) {
            echo '<div style="padding: 15px; background: #d4af37; color: white; font-weight: bold; text-align: center;">';
            echo 'SUCCESS! Meta boxes ARE working!<br>';
            echo 'The template system should work.';
            echo '</div>';
        },
        'workflow',
        'side',
        'high'
    );
}, 1);

// Also show admin notice
add_action('admin_notices', function() {
    global $post_type;
    if ($post_type === 'workflow') {
        echo '<div class="notice notice-success" style="border-left: 4px solid #d4af37;">';
        echo '<p><strong>🧪 TEST FILE IS LOADED</strong></p>';
        echo '<p>If you see a gold box in the sidebar titled "TEST - Template System", meta boxes work!</p>';
        echo '</div>';
    }
});
