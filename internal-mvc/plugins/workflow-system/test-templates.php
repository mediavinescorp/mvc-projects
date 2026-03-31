<?php
/**
 * Template System Test
 * Upload this to your plugin root directory and access it via browser
 * URL: https://yoursite.com/wp-content/plugins/workflow-system/test-templates.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if we're admin
if (!current_user_can('manage_options')) {
    die('You must be an administrator to view this page.');
}

echo '<h1>Template System Debug Test</h1>';
echo '<hr>';

// Test 1: Check constants
echo '<h2>1. WordPress Constants</h2>';
echo 'WFS_PLUGIN_DIR: ' . (defined('WFS_PLUGIN_DIR') ? WFS_PLUGIN_DIR . ' ✅' : 'NOT DEFINED ❌') . '<br>';
echo 'WFS_PLUGIN_URL: ' . (defined('WFS_PLUGIN_URL') ? WFS_PLUGIN_URL . ' ✅' : 'NOT DEFINED ❌') . '<br>';
echo '<hr>';

// Test 2: Check files exist
echo '<h2>2. File Existence</h2>';
$files = array(
    'Integration' => WFS_PLUGIN_DIR . 'includes/mediavines-template-integration.php',
    'Templates Backend' => WFS_PLUGIN_DIR . 'includes/mediavines-workflow-templates.php',
    'AJAX Handler' => WFS_PLUGIN_DIR . 'includes/mediavines-template-ajax.php',
    'Frontend JS' => WFS_PLUGIN_DIR . 'includes/mediavines-template-frontend.js',
    'Frontend CSS' => WFS_PLUGIN_DIR . 'includes/mediavines-template-styles.css'
);

foreach ($files as $name => $path) {
    $exists = file_exists($path);
    echo $name . ': ' . ($exists ? '✅ EXISTS' : '❌ NOT FOUND') . '<br>';
    if (!$exists) {
        echo '&nbsp;&nbsp;&nbsp;Expected at: ' . $path . '<br>';
    }
}
echo '<hr>';

// Test 3: Try to include files manually
echo '<h2>3. Manual File Loading</h2>';

try {
    require_once WFS_PLUGIN_DIR . 'includes/mediavines-workflow-templates.php';
    echo 'mediavines-workflow-templates.php: ✅ LOADED<br>';
} catch (Exception $e) {
    echo 'mediavines-workflow-templates.php: ❌ ERROR - ' . $e->getMessage() . '<br>';
}

try {
    require_once WFS_PLUGIN_DIR . 'includes/mediavines-template-ajax.php';
    echo 'mediavines-template-ajax.php: ✅ LOADED<br>';
} catch (Exception $e) {
    echo 'mediavines-template-ajax.php: ❌ ERROR - ' . $e->getMessage() . '<br>';
}

echo '<hr>';

// Test 4: Check if classes exist
echo '<h2>4. Class Existence</h2>';
echo 'MediaVines_Workflow_Templates: ' . (class_exists('MediaVines_Workflow_Templates') ? '✅ EXISTS' : '❌ NOT FOUND') . '<br>';
echo 'MediaVines_Template_AJAX: ' . (class_exists('MediaVines_Template_AJAX') ? '✅ EXISTS' : '❌ NOT FOUND') . '<br>';
echo 'MediaVines_Template_System_Integration: ' . (class_exists('MediaVines_Template_System_Integration') ? '✅ EXISTS' : '❌ NOT FOUND') . '<br>';

echo '<hr>';
echo '<p><strong>Test complete!</strong> Share these results.</p>';
