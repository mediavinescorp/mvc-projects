<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    
    <style>
        /* Hide the page title for intranet template */
        .page-template-page-intranet .entry-header,
        .page-template-page-intranet .entry-title,
        .page-template-page-intranet .wp-block-post-title {
            display: none !important;
        }
        
        /* Custom Intranet Header Styles */
        .mvc-intranet-header {
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            padding: 20px 0;
            border-bottom: 3px solid #D4AF37;
        }
        
        .mvc-header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .mvc-header-logo {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .mvc-header-logo img {
            height: 60px;
            width: auto;
            background: white;
            padding: 10px;
            border-radius: 8px;
        }
        
        .mvc-site-title {
            color: #D4AF37;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
            margin: 0;
        }
        
        .mvc-header-nav {
            display: flex;
            gap: 30px;
        }
        
        .mvc-header-nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .mvc-header-nav a:hover {
            color: #D4AF37;
        }
        
        .mvc-user-info {
            color: #ccc;
            font-size: 14px;
        }
        
        .mvc-user-info strong {
            color: #D4AF37;
        }
        
        .mvc-logout-link {
            color: #D4AF37;
            text-decoration: none;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .mvc-header-container {
                flex-direction: column;
                gap: 20px;
            }
            
            .mvc-header-nav {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="mvc-intranet-header">
    <div class="mvc-header-container">
        <div class="mvc-header-logo">
            <img src="https://o9x4z6hft7.wpdns.site/wp-content/uploads/2025/11/mvc-new-black-logo.png" alt="Media Vines Corp">
            <h1 class="mvc-site-title">MEDIA VINES CORP</h1>
        </div>
        
        <nav class="mvc-header-nav">
            <a href="<?php echo home_url('/'); ?>">Home</a>
            <a href="<?php echo home_url('/workflow-dashboard'); ?>">Workflow Dashboard</a>
            <a href="<?php echo home_url('/internal-resources'); ?>">Internal Resources</a>
        </nav>
        
        <?php if (is_user_logged_in()) : ?>
            <div class="mvc-user-info">
                Welcome, <strong><?php echo wp_get_current_user()->display_name; ?></strong>
                <a href="<?php echo wp_logout_url(home_url('/')); ?>" class="mvc-logout-link">Logout</a>
            </div>
        <?php endif; ?>
    </div>
</header>