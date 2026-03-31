<?php
/**
 * Login Page Customization
 * Styles the WordPress login page with Media Vines Corp branding
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customize login page styles
 */
add_action('login_enqueue_scripts', function() {
    ?>
    <style type="text/css">
        /* Media Vines Corp Login Styling - Gold (#D4AF37) and Black */
        
        body.login {
            background-color: #000;
        }
        
        body.login div#login h1 a {
            background-image: none;
            background-color: #D4AF37;
            width: 84px;
            height: 84px;
            border-radius: 50%;
            position: relative;
        }
        
        body.login div#login h1 a::before {
            content: "WFS";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            font-weight: bold;
            color: #000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .login form {
            background: #fff;
            border: 3px solid #D4AF37;
            box-shadow: 0 4px 8px rgba(212, 175, 55, 0.3);
        }
        
        .login label {
            color: #000;
            font-weight: 600;
        }
        
        .login input[type="text"],
        .login input[type="password"] {
            border: 2px solid #ddd;
            transition: border-color 0.3s;
        }
        
        .login input[type="text"]:focus,
        .login input[type="password"]:focus {
            border-color: #D4AF37;
            box-shadow: 0 0 0 1px #D4AF37;
        }
        
        .login .button-primary {
            background: #D4AF37;
            border-color: #D4AF37;
            color: #000;
            font-weight: 600;
            text-shadow: none;
            box-shadow: none;
            transition: all 0.3s;
        }
        
        .login .button-primary:hover,
        .login .button-primary:focus {
            background: #000;
            border-color: #000;
            color: #D4AF37;
        }
        
        .login #nav a,
        .login #backtoblog a {
            color: #D4AF37;
            transition: color 0.3s;
        }
        
        .login #nav a:hover,
        .login #backtoblog a:hover {
            color: #fff;
        }
        
        .login .message,
        .login .success {
            border-left-color: #D4AF37;
        }
        
        .login #login_error {
            border-left-color: #dc3545;
        }
        
        /* Checkbox styling */
        .login input[type="checkbox"]:checked::before {
            color: #D4AF37;
        }
        
        /* Link underlines */
        .login a:focus {
            box-shadow: 0 0 0 2px #D4AF37;
        }
    </style>
    <?php
});

/**
 * Change login logo URL
 */
add_filter('login_headerurl', function() {
    return home_url();
});

/**
 * Change login logo title
 */
add_filter('login_headertext', function() {
    return 'Media Vines Corp - Workflow System';
});

/**
 * Redirect to workflow dashboard after login
 */
add_filter('login_redirect', function($redirect_to, $request, $user) {
    // Only redirect if user has workflow access
    if (isset($user->roles) && is_array($user->roles)) {
        $workflow_roles = array('wfs_team_member', 'wfs_supervisor', 'wfs_admin', 'administrator');
        
        foreach ($workflow_roles as $role) {
            if (in_array($role, $user->roles)) {
                // Check if coming from workflow dashboard
                if (strpos($request, 'workflow-dashboard') !== false) {
                    return home_url('/workflow-dashboard/');
                }
                // Default redirect to dashboard for workflow users
                return home_url('/workflow-dashboard/');
            }
        }
    }
    
    return $redirect_to;
}, 10, 3);
