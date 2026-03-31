<?php
/**
 * Plugin Name: WFS Unified Login Protection
 * Description: Requires login for all WFS system pages (Workflow Dashboard and Resources Hub)
 * Version: 1.1 - Fixed Mobile Login Display
 * Author: Media Vines Corp
 */

if (!defined('ABSPATH')) {
    exit;
}

class WFS_Unified_Login_Protection {
    
    private static $instance = null;
    
    // Add page IDs or slugs that require login protection
    private $protected_pages = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into template redirect - this runs before any page content is displayed
        add_action('template_redirect', array($this, 'check_login_requirement'));
        
        // Add settings page to configure protected pages
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Load protected pages from options
        $this->protected_pages = get_option('wfs_protected_pages', array());
    }
    
    /**
     * Check if current page requires login and redirect if necessary
     */
    public function check_login_requirement() {
        // Skip if user is already logged in
        if (is_user_logged_in()) {
            return;
        }
        
        // Skip if not a page
        if (!is_page()) {
            return;
        }
        
        global $post;
        
        // Check if current page is protected
        $is_protected = false;
        
        // Method 1: Check by page ID
        if (in_array($post->ID, $this->protected_pages)) {
            $is_protected = true;
        }
        
        // Method 2: Check by page slug
        if (in_array($post->post_name, $this->protected_pages)) {
            $is_protected = true;
        }
        
        // Method 3: Check if page contains workflow or resources shortcodes
        $auto_protect = get_option('wfs_auto_protect_shortcodes', 'yes');
        if ($auto_protect === 'yes') {
            if (has_shortcode($post->post_content, 'wfs_workflow_dashboard') || 
                has_shortcode($post->post_content, 'wfs_resources_hub')) {
                $is_protected = true;
            }
        }
        
        // If page is protected and user not logged in, show branded login screen
        if ($is_protected) {
            $redirect_to = get_permalink($post->ID);
            $login_url = wp_login_url($redirect_to);
            
            // Display branded login screen
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Required - Media Vines Corp</title>
    <?php wp_head(); ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; width: 100%; }
        body {
            background: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-wrapper {
            width: 100%;
            max-width: 500px;
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            border: 2px solid #D4AF37;
            border-radius: 8px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .logo-section { margin-bottom: 30px; }
        .logo-img {
            max-width: 200px;
            width: 100%;
            height: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 0 auto 15px;
            display: block;
        }
        .company-name {
            color: #D4AF37;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .login-title {
            color: #fff;
            font-size: 24px;
            margin: 20px 0 10px;
        }
        .login-message {
            color: #ccc;
            font-size: 15px;
            margin-bottom: 30px;
        }
        .login-btn {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            padding: 14px 40px;
            background: #D4AF37;
            color: #000;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
        }
        .login-btn:hover {
            background: #c49d2f;
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            body { padding: 15px; }
            .login-wrapper { padding: 30px 25px; max-width: 100%; }
            .logo-img { max-width: 150px; }
            .company-name { font-size: 18px; }
            .login-title { font-size: 22px; }
            .login-message { font-size: 14px; }
            .login-btn { max-width: 100%; }
        }
        @media (max-width: 480px) {
            .login-wrapper { padding: 25px 20px; }
            .logo-img { max-width: 120px; }
            .company-name { font-size: 16px; }
            .login-title { font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="logo-section">
            <img src="https://o9x4z6hft7.wpdns.site/wp-content/uploads/2025/11/mvc-new-black-logo.png" alt="Media Vines Corp" class="logo-img">
            <div class="company-name">MEDIA VINES CORP</div>
        </div>
        <h2 class="login-title">Login Required</h2>
        <p class="login-message">You must be logged in to access this page.</p>
        <a href="<?php echo esc_url($login_url); ?>" class="login-btn">Login Now</a>
    </div>
</body>
</html>
<?php
exit;
        }
    }
    
    /**
     * Add settings page for configuration
     */
    public function add_settings_page() {
        add_submenu_page(
            'options-general.php',
            'WFS Login Protection',
            'WFS Login Protection',
            'manage_options',
            'wfs-login-protection',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wfs_login_protection', 'wfs_protected_pages');
        register_setting('wfs_login_protection', 'wfs_auto_protect_shortcodes');
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $protected_pages = get_option('wfs_protected_pages', array());
        $auto_protect = get_option('wfs_auto_protect_shortcodes', 'yes');
        
        // Get all pages
        $pages = get_pages(array('post_status' => 'publish'));
        
        ?>
        <div class="wrap">
            <h1>WFS Login Protection Settings</h1>
            <p>Configure which pages require users to be logged in to access.</p>
            
            <form method="post" action="options.php">
                <?php settings_fields('wfs_login_protection'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label>Automatic Protection</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wfs_auto_protect_shortcodes" value="yes" 
                                    <?php checked($auto_protect, 'yes'); ?>>
                                Automatically protect pages containing workflow or resources shortcodes
                            </label>
                            <p class="description">
                                Recommended: This will automatically protect any page that has 
                                [wfs_workflow_dashboard] or [wfs_resources_hub] shortcodes.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label>Protected Pages</label>
                        </th>
                        <td>
                            <fieldset>
                                <?php if (!empty($pages)): ?>
                                    <?php foreach ($pages as $page): ?>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="checkbox" 
                                                   name="wfs_protected_pages[]" 
                                                   value="<?php echo esc_attr($page->ID); ?>"
                                                   <?php checked(in_array($page->ID, $protected_pages)); ?>>
                                            <?php echo esc_html($page->post_title); ?>
                                            <span style="color: #666; font-size: 12px;">
                                                (<?php echo esc_html($page->post_name); ?>)
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No pages found.</p>
                                <?php endif; ?>
                            </fieldset>
                            <p class="description">
                                Select pages that should require login. Pages with WFS shortcodes are 
                                automatically protected if automatic protection is enabled above.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <hr>
            
            <h2>Currently Protected Pages</h2>
            <?php
            $protected_count = 0;
            echo '<ul>';
            
            // Show explicitly protected pages
            if (!empty($protected_pages)) {
                foreach ($protected_pages as $page_id) {
                    $page = get_post($page_id);
                    if ($page) {
                        echo '<li><strong>' . esc_html($page->post_title) . '</strong> - Explicitly protected</li>';
                        $protected_count++;
                    }
                }
            }
            
            // Show auto-protected pages (with shortcodes)
            if ($auto_protect === 'yes') {
                foreach ($pages as $page) {
                    if (has_shortcode($page->post_content, 'wfs_workflow_dashboard') || 
                        has_shortcode($page->post_content, 'wfs_resources_hub')) {
                        if (!in_array($page->ID, $protected_pages)) {
                            echo '<li><strong>' . esc_html($page->post_title) . '</strong> - Auto-protected (contains WFS shortcode)</li>';
                            $protected_count++;
                        }
                    }
                }
            }
            
            if ($protected_count === 0) {
                echo '<li>No pages are currently protected.</li>';
            }
            
            echo '</ul>';
            ?>
            
            <h2>How It Works</h2>
            <ol>
                <li><strong>Automatic Protection:</strong> Any page with [wfs_workflow_dashboard] or [wfs_resources_hub] is automatically protected.</li>
                <li><strong>Manual Protection:</strong> You can also manually select specific pages to protect.</li>
                <li><strong>Redirect Behavior:</strong> Non-logged-in users are redirected to the WordPress login page.</li>
                <li><strong>After Login:</strong> Users are automatically redirected back to the page they were trying to access.</li>
            </ol>
            
            <h2>Testing</h2>
            <p>To test if login protection is working:</p>
            <ol>
                <li>Open an <strong>Incognito/Private browser window</strong></li>
                <li>Try to access your Workflow Dashboard or Resources Hub page</li>
                <li>You should be redirected to the WordPress login page</li>
                <li>After logging in, you should be redirected back to the page</li>
            </ol>
        </div>
        <?php
    }
}

// Initialize the plugin
WFS_Unified_Login_Protection::get_instance();