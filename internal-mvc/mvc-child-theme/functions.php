<?php
/**
 * MVC Child Theme Functions
 * 
 * Enqueues parent theme styles and child theme customizations
 */

// Enqueue parent and child theme styles
function mvc_child_enqueue_styles() {
    // Load parent theme stylesheet
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    
    // Load child theme stylesheet (this will override parent styles)
    wp_enqueue_style('child-style', 
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style'),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'mvc_child_enqueue_styles');

// Hide page title on intranet template pages
function mvc_hide_intranet_title() {
    if (is_page_template('page-intranet.php')) {
        ?>
        <style>
            /* Hide page title and entry header for intranet pages */
            .entry-header,
            .entry-title,
            .wp-block-post-title,
            h1.entry-title,
            .page-title {
                display: none !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'mvc_hide_intranet_title');

// Add custom footer to ALL pages site-wide
function mvc_custom_site_footer() {
    ?>
    <style>
        /* Hide default Twenty Twenty-Five footer */
        footer.wp-block-template-part,
        .wp-site-blocks > footer,
        .site-footer {
            display: none !important;
        }
        
        /* Custom Footer Styles */
        .mvc-custom-site-footer {
            background: #000;
            padding: 20px;
            text-align: center;
            border-top: 2px solid #D4AF37;
            margin-top: 0;
            width: 100%;
            position: relative;
            z-index: 999;
        }

        .mvc-custom-site-footer p {
            margin: 0;
            color: #D4AF37;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mvc-custom-site-footer {
                padding: 15px;
            }
            
            .mvc-custom-site-footer p {
                font-size: 12px;
            }
        }
    </style>
    
    <footer class="mvc-custom-site-footer">
        <p>&copy; <?php echo date('Y'); ?> Media Vines Corp. All rights reserved.</p>
    </footer>
    <?php
}
add_action('wp_footer', 'mvc_custom_site_footer', 999);



add_action( 'wp_enqueue_scripts', function() {
    if ( is_page( 'sop-image-checklist' ) ) {
        wp_enqueue_script( 'sop-checklist', get_stylesheet_directory_uri() . '/js/sop-checklist.js', array(), '1.0', true );
    }
});



require_once get_theme_file_path( 'inc/dancer-setup.php' );