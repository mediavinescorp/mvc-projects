<?php
/**
 * Template Name: MVC Intranet Homepage
 * Template Post Type: page
 * Description: Full-width template for Media Vines Corp Intranet Portal
 */

// Use custom intranet header instead of default theme header
get_header('intranet'); ?>

<style>
    /* Hide page title and entry header */
    .entry-header,
    .entry-title,
    .wp-block-post-title,
    h1.entry-title {
        display: none !important;
    }
    
    /* Remove default page padding/margins */
    .site-content,
    .content-area,
    article {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }

    .mvc-intranet-page * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .mvc-intranet-page {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
        line-height: 1.6;
        color: #333;
    }

    .mvc-hero {
        background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
        color: white;
        padding: 80px 20px;
        text-align: center;
        border-bottom: 4px solid #D4AF37;
    }

    .mvc-hero-logo {
        max-width: 250px;
        height: auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        margin: 0 auto 30px;
        box-shadow: 0 4px 20px rgba(212, 175, 55, 0.3);
    }

    .mvc-hero-title {
        font-size: 42px;
        margin-bottom: 10px;
        color: #D4AF37;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .mvc-hero-subtitle {
        font-size: 20px;
        color: #ccc;
        margin-bottom: 40px;
        font-weight: 300;
    }

    .mvc-hero-description {
        max-width: 800px;
        margin: 0 auto 40px;
        font-size: 16px;
        line-height: 1.8;
        color: #ddd;
    }

    .mvc-login-button {
        display: inline-block;
        padding: 16px 50px;
        background: #D4AF37;
        color: #000 !important;
        text-decoration: none !important;
        border-radius: 6px;
        font-weight: 600;
        font-size: 18px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        margin-top: 20px;
    }

    .mvc-login-button:hover {
        background: #c4a030;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
    }

    .mvc-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 20px;
    }

    .mvc-section-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .mvc-section-header h2 {
        font-size: 36px;
        color: #000;
        margin-bottom: 15px;
        position: relative;
        display: inline-block;
    }

    .mvc-section-header h2:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: #D4AF37;
    }

    .mvc-section-header p {
        color: #666;
        font-size: 16px;
        max-width: 700px;
        margin: 20px auto 0;
    }

    .mvc-systems-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 40px;
        margin-top: 50px;
    }

    .mvc-system-card {
        background: white;
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-top: 4px solid #D4AF37;
    }

    .mvc-system-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }

    .mvc-system-icon {
        font-size: 48px;
        margin-bottom: 20px;
        display: block;
    }

    .mvc-system-card h3 {
        font-size: 26px;
        color: #000;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .mvc-tagline {
        color: #D4AF37;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 15px;
    }

    .mvc-system-card p {
        color: #666;
        line-height: 1.8;
        margin-bottom: 20px;
    }

    .mvc-feature-list {
        list-style: none;
        padding: 0;
        margin-top: 20px;
    }

    .mvc-feature-list li {
        padding: 10px 0;
        color: #555;
        position: relative;
        padding-left: 30px;
    }

    .mvc-feature-list li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #D4AF37;
        font-weight: bold;
        font-size: 18px;
    }

    .mvc-benefits {
        background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
        color: white;
        padding: 80px 20px;
    }

    .mvc-benefits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
        max-width: 1200px;
        margin: 50px auto 0;
    }

    .mvc-benefit-item {
        text-align: center;
        padding: 30px;
    }

    .mvc-benefit-icon {
        font-size: 48px;
        margin-bottom: 20px;
        display: block;
    }

    .mvc-benefit-item h4 {
        color: #D4AF37;
        font-size: 20px;
        margin-bottom: 10px;
    }

    .mvc-benefit-item p {
        color: #ccc;
        font-size: 15px;
    }

    .mvc-cta {
        background: white;
        padding: 80px 20px;
        text-align: center;
    }

    .mvc-cta h2 {
        font-size: 36px;
        color: #000;
        margin-bottom: 20px;
    }

    .mvc-cta p {
        font-size: 18px;
        color: #666;
        margin-bottom: 40px;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }

    .mvc-security-badge {
        display: inline-block;
        background: rgba(212, 175, 55, 0.15);
        border: 2px solid #D4AF37;
        border-radius: 50px;
        padding: 10px 25px;
        margin-top: 20px;
        font-size: 15px;
        color: #D4AF37;
        font-weight: 600;
    }

    /* Security button link */
    .mvc-security-button {
        display: inline-block;
        padding: 12px 30px;
        background: rgba(212, 175, 55, 0.15);
        border: 2px solid #D4AF37;
        border-radius: 50px;
        margin-top: 20px;
        font-size: 15px;
        color: #D4AF37 !important;
        text-decoration: none !important;
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .mvc-security-button:hover {
        background: rgba(212, 175, 55, 0.25);
        border-color: #e5c047;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    }

    @media (max-width: 768px) {
        .mvc-hero-title { font-size: 32px; }
        .mvc-hero-subtitle { font-size: 18px; }
        .mvc-systems-grid { grid-template-columns: 1fr; }
        .mvc-section-header h2 { font-size: 28px; }
        .mvc-system-card { padding: 30px 20px; }
    }
</style>

<div class="mvc-intranet-page">
    <section class="mvc-hero">
        <img src="https://o9x4z6hft7.wpdns.site/wp-content/uploads/2025/11/mvc-new-black-logo.png" alt="Media Vines Corp" class="mvc-hero-logo">
        <h1 class="mvc-hero-title">MEDIA VINES CORP INTRANET</h1>
        <p class="mvc-hero-subtitle">Your Centralized Hub for Workflow Management & Internal Resources</p>
        <p class="mvc-hero-description">
            Welcome to the Media Vines Corp Intranet Portal—a comprehensive platform designed to streamline your daily operations, 
            enhance team collaboration, and provide instant access to critical company resources.
        </p>
        <?php if (is_user_logged_in()) : ?>
            <div class="mvc-security-badge">
                ✓ You are logged in as <?php echo wp_get_current_user()->display_name; ?>
            </div>
        <?php else : ?>
            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="mvc-security-button">
                🔒 Secure Team Access Required - Click to Login
            </a>
        <?php endif; ?>
    </section>

    <div class="mvc-container">
        <div class="mvc-section-header">
            <h2>Our Systems</h2>
            <p>Two powerful tools working together to support your success</p>
        </div>

        <div class="mvc-systems-grid">
            <div class="mvc-system-card">
                <span class="mvc-system-icon">📊</span>
                <div class="mvc-tagline">Task Management</div>
                <h3>Workflow Dashboard</h3>
                <p>A comprehensive workflow management system that brings clarity and accountability to every project.</p>
                <ul class="mvc-feature-list">
                    <li><strong>Centralized Task Tracking:</strong> View all assigned tasks in one dashboard</li>
                    <li><strong>Multi-Task Workflows:</strong> Manage complex projects with dependent task chains</li>
                    <li><strong>Real-Time Status Updates:</strong> Track progress from assigned to completed</li>
                    <li><strong>Client Management:</strong> Associate workflows with specific clients</li>
                    <li><strong>Team Communication:</strong> Threaded notes and status documentation</li>
                    <li><strong>Role-Based Access:</strong> Team members, supervisors, and admins</li>
                    <li><strong>Activity Logging:</strong> Complete audit trail of all activities</li>
                    <li><strong>Template System:</strong> Reusable workflow templates</li>
                </ul>
            </div>

            <div class="mvc-system-card">
                <span class="mvc-system-icon">📚</span>
                <div class="mvc-tagline">Knowledge Management</div>
                <h3>Internal Resources</h3>
                <p>Your centralized knowledge repository for procedures, templates, training materials, and reference documents.</p>
                <ul class="mvc-feature-list">
                    <li><strong>Searchable Resource Library:</strong> Find what you need instantly</li>
                    <li><strong>Multi-Client Organization:</strong> Resources tagged to relevant clients</li>
                    <li><strong>Category System:</strong> Organized by procedures, templates, training</li>
                    <li><strong>Rich Content Editor:</strong> Full formatting, images, links support</li>
                    <li><strong>Version Tracking:</strong> See who created and modified each resource</li>
                    <li><strong>Easy Collaboration:</strong> Team members can create and edit</li>
                    <li><strong>Quick Reference:</strong> Access from any device, anywhere</li>
                    <li><strong>Mobile Friendly:</strong> View resources on phones and tablets</li>
                </ul>
            </div>
        </div>
    </div>

    <section class="mvc-benefits">
        <div class="mvc-container">
            <div class="mvc-section-header">
                <h2 style="color: #D4AF37;">Why Our Intranet Works</h2>
                <p style="color: #ccc;">Built specifically for Media Vines Corp's workflow and needs</p>
            </div>

            <div class="mvc-benefits-grid">
                <div class="mvc-benefit-item">
                    <span class="mvc-benefit-icon">⚡</span>
                    <h4>Increased Efficiency</h4>
                    <p>Reduce time spent searching for information</p>
                </div>
                <div class="mvc-benefit-item">
                    <span class="mvc-benefit-icon">🎯</span>
                    <h4>Clear Accountability</h4>
                    <p>Know exactly who's responsible for what</p>
                </div>
                <div class="mvc-benefit-item">
                    <span class="mvc-benefit-icon">💬</span>
                    <h4>Better Communication</h4>
                    <p>Built-in notes eliminate communication gaps</p>
                </div>
                <div class="mvc-benefit-item">
                    <span class="mvc-benefit-icon">📱</span>
                    <h4>Access Anywhere</h4>
                    <p>Works seamlessly on all devices</p>
                </div>
                <div class="mvc-benefit-item">
                    <span class="mvc-benefit-icon">🔒</span>
                    <h4>Secure & Private</h4>
                    <p>Login-protected access keeps data secure</p>
                </div>
                <div class="mvc-benefit-item">
                    <span class="mvc-benefit-icon">📈</span>
                    <h4>Scalable Growth</h4>
                    <p>System grows with your team</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mvc-cta">
        <h2>Ready to Get Started?</h2>
        <p>Log in to access your personalized dashboard, view assigned tasks, search resources, and collaborate with your team.</p>
        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="mvc-login-button">Access Intranet Portal →</a>
        <p style="margin-top: 30px; color: #999; font-size: 14px;">
            🔐 Secure login required • Media Vines Corp team members only
        </p>
    </section>
</div>

<?php get_footer('intranet'); ?>