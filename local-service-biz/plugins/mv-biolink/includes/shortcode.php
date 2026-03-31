<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'mv_biolink_form', 'mvbl_render_form' );

function mvbl_render_form() {
    ob_start(); ?>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,600;1,400&family=Outfit:wght@400;500;600&display=swap');
    #mvbl-wrap,#mvbl-wrap *{box-sizing:border-box !important;font-family:'Outfit',sans-serif !important;}
    #mvbl-wrap h2,#mvbl-wrap h3,#mvbl-wrap p,#mvbl-wrap ul,#mvbl-wrap ol,#mvbl-wrap li{margin:0 !important;padding:0 !important;line-height:normal !important;}
    #mvbl-wrap{max-width:600px !important;margin:0 auto !important;background:#ffffff !important;border-radius:16px !important;border:1px solid #e4e4e0 !important;overflow:hidden !important;color:#141414 !important;}
    #mvbl-wrap .mvbl-form-header{background:#1a6b4a !important;padding:1.5rem 1.75rem !important;color:#fff !important;}
    #mvbl-wrap .mvbl-form-header h2{font-family:'Fraunces',serif !important;font-size:1.35rem !important;font-weight:600 !important;color:#fff !important;border:none !important;background:none !important;padding:0 !important;margin:0 0 .3rem !important;line-height:1.3 !important;text-transform:none !important;letter-spacing:normal !important;}
    #mvbl-wrap .mvbl-form-header p{font-size:13px !important;opacity:.85 !important;color:#fff !important;margin:0 !important;line-height:1.6 !important;}
    #mvbl-wrap .mvbl-form-body{padding:1.75rem !important;}
    #mvbl-wrap .mvbl-steps{display:flex !important;gap:4px !important;margin-bottom:1.75rem !important;background:#f9f8f5 !important;border-radius:10px !important;padding:4px !important;}
    #mvbl-wrap .mvbl-step{flex:1 !important;display:flex !important;align-items:center !important;justify-content:center !important;gap:7px !important;padding:9px 8px !important;border-radius:8px !important;border:none !important;background:transparent !important;font-size:13px !important;font-weight:500 !important;color:#6b7280 !important;cursor:pointer !important;box-shadow:none !important;text-transform:none !important;letter-spacing:normal !important;width:auto !important;height:auto !important;line-height:normal !important;}
    #mvbl-wrap .mvbl-step span{width:20px !important;height:20px !important;min-width:20px !important;border-radius:50% !important;background:#e4e4e0 !important;color:#6b7280 !important;font-size:11px !important;font-weight:700 !important;display:flex !important;align-items:center !important;justify-content:center !important;flex-shrink:0 !important;padding:0 !important;margin:0 !important;line-height:1 !important;}
    #mvbl-wrap .mvbl-step.active{background:#ffffff !important;color:#141414 !important;box-shadow:0 1px 3px rgba(0,0,0,.08) !important;}
    #mvbl-wrap .mvbl-step.active span,#mvbl-wrap .mvbl-step.done span{background:#1a6b4a !important;color:#fff !important;}
    #mvbl-wrap .mvbl-step-panel{display:none !important;}
    #mvbl-wrap .mvbl-step-panel.active{display:block !important;}
    #mvbl-wrap .mvbl-field{margin-bottom:1rem !important;}
    #mvbl-wrap .mvbl-field label{display:block !important;font-size:13px !important;font-weight:500 !important;color:#141414 !important;margin-bottom:5px !important;padding:0 !important;background:none !important;border:none !important;line-height:normal !important;}
    #mvbl-wrap .mvbl-req{color:#1a6b4a !important;}
    #mvbl-wrap .mvbl-opt{color:#6b7280 !important;font-weight:400 !important;}
    #mvbl-wrap input[type=text],#mvbl-wrap input[type=tel],#mvbl-wrap input[type=email],#mvbl-wrap input[type=url],#mvbl-wrap textarea,#mvbl-wrap select{width:100% !important;padding:10px 13px !important;border:1px solid #e4e4e0 !important;border-radius:10px !important;font-size:14px !important;color:#141414 !important;background:#f9f8f5 !important;outline:none !important;transition:border-color .2s !important;box-shadow:none !important;height:auto !important;line-height:normal !important;}
    #mvbl-wrap input[type=text]:focus,#mvbl-wrap input[type=tel]:focus,#mvbl-wrap input[type=email]:focus,#mvbl-wrap input[type=url]:focus,#mvbl-wrap textarea:focus,#mvbl-wrap select:focus{border-color:#1a6b4a !important;background:#fff !important;box-shadow:0 0 0 3px rgba(26,107,74,.08) !important;}
    #mvbl-wrap textarea{resize:none !important;height:80px !important;}
    #mvbl-wrap .mvbl-two-col{display:grid !important;grid-template-columns:1fr 1fr !important;gap:1rem !important;margin-bottom:.5rem !important;}
    #mvbl-wrap .mvbl-section-title{font-size:11px !important;font-weight:700 !important;text-transform:uppercase !important;letter-spacing:.08em !important;color:#6b7280 !important;margin:1.25rem 0 .75rem !important;background:none !important;border:none !important;padding:0 !important;}
    #mvbl-wrap .mvbl-step-intro{font-size:13px !important;color:#6b7280 !important;margin-bottom:1.25rem !important;line-height:1.6 !important;}
    #mvbl-wrap .mvbl-social-note{font-size:12px !important;color:#6b7280 !important;margin-bottom:.85rem !important;line-height:1.6 !important;background:#ebf3ff !important;border-radius:10px !important;padding:.6rem .9rem !important;}
    #mvbl-wrap .mvbl-social-note strong{color:#1a56db !important;font-weight:600 !important;}
    #mvbl-wrap .mvbl-socials-grid{display:grid !important;grid-template-columns:1fr 1fr !important;gap:8px !important;}
    #mvbl-wrap .mvbl-social-field{display:flex !important;align-items:center !important;gap:8px !important;background:#f9f8f5 !important;border:1px solid #e4e4e0 !important;border-radius:10px !important;padding:8px 10px !important;}
    #mvbl-wrap .mvbl-social-field:focus-within{border-color:#1a6b4a !important;}
    #mvbl-wrap .mvbl-social-icon{width:24px !important;height:24px !important;flex-shrink:0 !important;display:flex !important;align-items:center !important;justify-content:center !important;}
    #mvbl-wrap .mvbl-social-icon svg{width:16px !important;height:16px !important;stroke:#6b7280 !important;}
    #mvbl-wrap .mvbl-social-input-wrap{flex:1 !important;display:flex !important;flex-direction:column !important;gap:2px !important;min-width:0 !important;}
    #mvbl-wrap .mvbl-social-input{border:none !important;background:none !important;padding:0 !important;font-size:13px !important;flex:1 !important;outline:none !important;color:#141414 !important;width:100% !important;border-radius:0 !important;box-shadow:none !important;height:auto !important;}
    #mvbl-wrap .mvbl-social-preview{font-size:10px !important;color:#1a6b4a !important;white-space:nowrap !important;overflow:hidden !important;text-overflow:ellipsis !important;}
    #mvbl-wrap .mvbl-avatar-upload{display:flex !important;align-items:center !important;gap:12px !important;border:2px dashed #e4e4e0 !important;border-radius:10px !important;padding:14px !important;cursor:pointer !important;position:relative !important;background:#f9f8f5 !important;}
    #mvbl-wrap .mvbl-avatar-upload:hover{border-color:#1a6b4a !important;background:#e8f5ef !important;}
    #mvbl-wrap .mvbl-avatar-upload.has-photo{border-style:solid !important;border-color:#1a6b4a !important;background:#e8f5ef !important;}
    #mvbl-wrap .mvbl-avatar-upload input[type=file]{position:absolute !important;inset:0 !important;opacity:0 !important;cursor:pointer !important;width:100% !important;height:100% !important;z-index:2 !important;}
    #mvbl-wrap .mvbl-avatar-preview{width:52px !important;height:52px !important;border-radius:50% !important;background:#e4e4e0 !important;overflow:hidden !important;flex-shrink:0 !important;display:flex !important;align-items:center !important;justify-content:center !important;}
    #mvbl-wrap .mvbl-avatar-preview svg{width:24px !important;height:24px !important;stroke:#6b7280 !important;}
    #mvbl-wrap .mvbl-avatar-preview img{width:100% !important;height:100% !important;object-fit:cover !important;}
    #mvbl-wrap .mvbl-avatar-label{font-size:13px !important;font-weight:500 !important;color:#6b7280 !important;}
    #mvbl-wrap .mvbl-avatar-upload.has-photo .mvbl-avatar-label{color:#1a6b4a !important;}
    #mvbl-wrap .mvbl-avatar-sub{font-size:11px !important;color:#aaa !important;margin-top:2px !important;}
    #mvbl-wrap .mvbl-remove-btn{font-size:12px !important;color:#e53e3e !important;background:none !important;border:none !important;cursor:pointer !important;padding:0 !important;margin-top:5px !important;display:block !important;box-shadow:none !important;height:auto !important;width:auto !important;border-radius:0 !important;text-transform:none !important;line-height:normal !important;}
    #mvbl-wrap .mvbl-explainer{background:#f9f8f5 !important;border:1px solid #e4e4e0 !important;border-radius:10px !important;margin-bottom:1.25rem !important;overflow:hidden !important;}
    #mvbl-wrap .mvbl-explainer-toggle{width:100% !important;display:flex !important;align-items:center !important;gap:8px !important;padding:.85rem 1rem !important;background:none !important;border:none !important;font-size:13px !important;font-weight:500 !important;color:#1a56db !important;cursor:pointer !important;text-align:left !important;box-shadow:none !important;border-radius:0 !important;height:auto !important;line-height:normal !important;text-transform:none !important;letter-spacing:normal !important;}
    #mvbl-wrap .mvbl-explainer-toggle svg{flex-shrink:0 !important;width:14px !important;height:14px !important;stroke:#1a56db !important;}
    #mvbl-wrap .mvbl-explainer-chevron{margin-left:auto !important;flex-shrink:0 !important;transition:transform .2s !important;width:13px !important;height:13px !important;}
    #mvbl-wrap .mvbl-explainer-body{padding:0 1rem 1rem !important;border-top:1px solid #e4e4e0 !important;}
    #mvbl-wrap .mvbl-explainer-two-col{display:grid !important;grid-template-columns:1fr 1fr !important;gap:1.25rem !important;margin-top:1rem !important;}
    #mvbl-wrap .mvbl-explainer-col-title{font-size:11px !important;font-weight:700 !important;text-transform:uppercase !important;letter-spacing:.07em !important;color:#6b7280 !important;margin-bottom:.5rem !important;display:block !important;}
    #mvbl-wrap .mvbl-explainer-col p{font-size:12px !important;color:#6b7280 !important;line-height:1.6 !important;margin-bottom:.75rem !important;}
    #mvbl-wrap .mvbl-explainer-col p strong{color:#141414 !important;font-weight:600 !important;}
    #mvbl-wrap .mvbl-explainer-example{display:flex !important;flex-wrap:wrap !important;gap:5px !important;}
    #mvbl-wrap .mvbl-chip-preview{font-size:11px !important;background:white !important;border:1px solid #e4e4e0 !important;border-radius:100px !important;padding:3px 10px !important;color:#6b7280 !important;display:inline-block !important;}
    #mvbl-wrap .mvbl-explainer-examples{display:flex !important;flex-direction:column !important;gap:5px !important;}
    #mvbl-wrap .mvbl-link-preview-pill{font-size:12px !important;background:white !important;border:1px solid #e4e4e0 !important;border-radius:8px !important;padding:7px 12px !important;color:#141414 !important;font-weight:500 !important;display:block !important;}
    #mvbl-wrap .mvbl-link-row{margin-bottom:8px !important;}
    #mvbl-wrap .mvbl-link-row-inner{display:flex !important;align-items:center !important;gap:6px !important;}
    #mvbl-wrap .mvbl-link-icon-select{width:130px !important;flex-shrink:0 !important;font-size:12px !important;padding:8px 6px !important;height:auto !important;}
    #mvbl-wrap .mvbl-link-label{flex:1 !important;}
    #mvbl-wrap .mvbl-link-url{flex:1.5 !important;}
    #mvbl-wrap .mvbl-remove-link{background:none !important;border:none !important;color:#ccc !important;font-size:16px !important;cursor:pointer !important;padding:0 6px !important;flex-shrink:0 !important;height:auto !important;width:auto !important;border-radius:0 !important;box-shadow:none !important;line-height:1 !important;}
    #mvbl-wrap .mvbl-remove-link:hover{color:#e53e3e !important;background:none !important;}
    #mvbl-wrap .mvbl-links-recommended{font-size:12px !important;color:#6b7280 !important;background:#e8f5ef !important;border-radius:8px !important;padding:.5rem .85rem !important;margin-bottom:.75rem !important;line-height:1.5 !important;}
    #mvbl-wrap .mvbl-links-recommended strong{color:#1a6b4a !important;font-weight:600 !important;}
    #mvbl-wrap .mvbl-add-btn{background:none !important;border:1px dashed #e4e4e0 !important;color:#6b7280 !important;padding:8px 16px !important;border-radius:10px !important;font-size:13px !important;cursor:pointer !important;width:auto !important;height:auto !important;box-shadow:none !important;display:inline-block !important;text-transform:none !important;font-weight:500 !important;line-height:normal !important;}
    #mvbl-wrap .mvbl-add-btn:hover{border-color:#1a6b4a !important;color:#1a6b4a !important;background:#e8f5ef !important;}
    #mvbl-wrap .mvbl-links-count{font-size:11px !important;color:#aaa !important;margin-top:6px !important;display:block !important;}
    #mvbl-wrap .mvbl-layout-grid{display:grid !important;grid-template-columns:repeat(4,1fr) !important;gap:8px !important;}
    #mvbl-wrap .mvbl-layout-option{cursor:pointer !important;border:2px solid #e4e4e0 !important;border-radius:10px !important;padding:10px 8px !important;text-align:center !important;background:#ffffff !important;display:block !important;}
    #mvbl-wrap .mvbl-layout-option input{display:none !important;}
    #mvbl-wrap .mvbl-layout-option:hover{border-color:#1a6b4a !important;}
    #mvbl-wrap .mvbl-layout-option.selected{border-color:#1a6b4a !important;background:#e8f5ef !important;}
    #mvbl-wrap .mvbl-layout-thumb{margin-bottom:6px !important;}
    #mvbl-wrap .mvbl-layout-thumb svg{width:100% !important;height:auto !important;}
    #mvbl-wrap .mvbl-layout-name{font-size:11px !important;font-weight:600 !important;color:#141414 !important;margin-bottom:2px !important;display:block !important;line-height:1.3 !important;}
    #mvbl-wrap .mvbl-layout-desc{font-size:9px !important;color:#6b7280 !important;line-height:1.4 !important;display:block !important;}
    #mvbl-wrap .mvbl-color-row{display:grid !important;grid-template-columns:repeat(3,1fr) !important;gap:1rem !important;}
    #mvbl-wrap .mvbl-color-wrap{display:flex !important;align-items:center !important;gap:8px !important;flex-wrap:wrap !important;}
    #mvbl-wrap input[type=color]{width:36px !important;height:36px !important;border:1px solid #e4e4e0 !important;border-radius:8px !important;padding:2px !important;cursor:pointer !important;background:#f9f8f5 !important;flex-shrink:0 !important;}
    #mvbl-wrap .mvbl-color-presets{display:flex !important;gap:5px !important;flex-wrap:wrap !important;}
    #mvbl-wrap .mvbl-color-presets span{width:18px !important;height:18px !important;border-radius:50% !important;cursor:pointer !important;border:1.5px solid rgba(0,0,0,.12) !important;display:inline-block !important;}
    #mvbl-wrap .mvbl-step-nav{display:flex !important;align-items:center !important;justify-content:space-between !important;margin-top:1.5rem !important;gap:10px !important;}
    #mvbl-wrap .mvbl-next-btn,#mvbl-wrap .mvbl-submit-btn{display:inline-flex !important;align-items:center !important;gap:8px !important;padding:11px 22px !important;background:#1a6b4a !important;color:#ffffff !important;border:none !important;border-radius:10px !important;font-size:14px !important;font-weight:600 !important;cursor:pointer !important;box-shadow:none !important;text-transform:none !important;letter-spacing:normal !important;height:auto !important;line-height:normal !important;width:auto !important;}
    #mvbl-wrap .mvbl-next-btn:hover,#mvbl-wrap .mvbl-submit-btn:hover{background:#2d8c62 !important;color:#fff !important;}
    #mvbl-wrap .mvbl-submit-btn:disabled{opacity:.6 !important;cursor:not-allowed !important;}
    #mvbl-wrap .mvbl-submit-btn svg{width:16px !important;height:16px !important;stroke:#fff !important;flex-shrink:0 !important;}
    #mvbl-wrap .mvbl-back-btn{padding:11px 16px !important;background:#f9f8f5 !important;border:1px solid #e4e4e0 !important;border-radius:10px !important;font-size:14px !important;font-weight:500 !important;color:#6b7280 !important;cursor:pointer !important;box-shadow:none !important;text-transform:none !important;height:auto !important;line-height:normal !important;}
    #mvbl-wrap .mvbl-back-btn:hover{background:#e4e4e0 !important;color:#141414 !important;}
    #mvbl-wrap .mvbl-error{background:#fef2f2 !important;border:1px solid #fecaca !important;border-radius:10px !important;padding:.75rem 1rem !important;font-size:13px !important;color:#991b1b !important;margin-bottom:1rem !important;}
    #mvbl-wrap .mvbl-loading-wrap{padding:4rem 2rem !important;text-align:center !important;}
    #mvbl-wrap .mvbl-spinner{width:40px !important;height:40px !important;border:3px solid #e8f5ef !important;border-top-color:#1a6b4a !important;border-radius:50% !important;animation:mvbl-spin .8s linear infinite !important;margin:0 auto 1.25rem !important;}
    @keyframes mvbl-spin{to{transform:rotate(360deg);}}
    #mvbl-wrap .mvbl-loading-text{font-size:16px !important;font-weight:500 !important;color:#141414 !important;margin-bottom:.3rem !important;display:block !important;}
    #mvbl-wrap .mvbl-loading-sub{font-size:13px !important;color:#6b7280 !important;display:block !important;}
    #mvbl-wrap .mvbl-footer-note{font-size:11px !important;color:#bbb !important;text-align:center !important;margin-top:1.25rem !important;display:block !important;}
    #mvbl-wrap .mvbl-footer-note a{color:#1a6b4a !important;text-decoration:none !important;}
    @media(max-width:520px){
      #mvbl-wrap .mvbl-two-col,#mvbl-wrap .mvbl-socials-grid,#mvbl-wrap .mvbl-color-row{grid-template-columns:1fr !important;}
      #mvbl-wrap .mvbl-layout-grid{grid-template-columns:repeat(2,1fr) !important;}
      #mvbl-wrap .mvbl-link-row-inner{flex-wrap:wrap !important;}
      #mvbl-wrap .mvbl-link-icon-select{width:100% !important;}
      #mvbl-wrap .mvbl-explainer-two-col{grid-template-columns:1fr !important;}
    }
    </style>
    <div class="mvbl-wrap" id="mvbl-wrap">

    <!-- ── FORM STATE ── -->
    <div id="mvbl-form-state">
      <div class="mvbl-form-header">
        <h2>Create Your Bio Link Page</h2>
        <p>Fill in your details and we'll generate a beautiful bio page with a QR code — instantly.</p>
      </div>
      <div class="mvbl-form-body">

        <!-- STEP TABS -->
        <div class="mvbl-steps">
          <button type="button" class="mvbl-step active" data-step="1"><span>1</span> Profile</button>
          <button type="button" class="mvbl-step" data-step="2"><span>2</span> Links</button>
          <button type="button" class="mvbl-step" data-step="3"><span>3</span> Design</button>
        </div>

        <!-- ══ STEP 1: PROFILE ══ -->
        <div class="mvbl-step-panel active" id="mvbl-panel-1">
          <div class="mvbl-two-col">

            <!-- Avatar upload -->
            <div class="mvbl-field">
              <label>Photo / Logo <span class="mvbl-opt">(optional)</span></label>
              <div class="mvbl-avatar-upload" id="mvbl-avatar-area">
                <input type="file" id="mvbl-avatar-file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif">
                <div class="mvbl-avatar-preview" id="mvbl-avatar-preview">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </div>
                <div class="mvbl-avatar-text">
                  <div class="mvbl-avatar-label">Click to upload</div>
                  <div class="mvbl-avatar-sub">PNG, JPG, WebP · Max 5MB</div>
                </div>
              </div>
              <button type="button" id="mvbl-remove-avatar" class="mvbl-remove-btn" style="display:none">Remove photo</button>
            </div>

            <div>
              <div class="mvbl-field">
                <label for="mvbl-full-name">Full Name <span class="mvbl-req">*</span></label>
                <input type="text" id="mvbl-full-name" name="full_name" placeholder="e.g. Maria Rodriguez">
              </div>
              <div class="mvbl-field">
                <label for="mvbl-job-title">Job Title</label>
                <input type="text" id="mvbl-job-title" name="job_title" placeholder="e.g. Owner & Master Plumber">
              </div>
              <div class="mvbl-field">
                <label for="mvbl-company">Company</label>
                <input type="text" id="mvbl-company" name="company" placeholder="e.g. Rodriguez Plumbing LLC">
              </div>
            </div>
          </div>

          <div class="mvbl-field">
            <label for="mvbl-bio">Bio / Tagline</label>
            <textarea id="mvbl-bio" name="bio" rows="3" placeholder="A short intro — what you do, who you help, what makes you different."></textarea>
          </div>

          <div class="mvbl-section-title">Contact Information</div>
          <div class="mvbl-two-col">
            <div class="mvbl-field">
              <label for="mvbl-phone">Phone</label>
              <input type="tel" id="mvbl-phone" name="phone" placeholder="+1 (555) 000-0000">
            </div>
            <div class="mvbl-field">
              <label for="mvbl-email">Email</label>
              <input type="email" id="mvbl-email" name="email" placeholder="you@yourbusiness.com">
            </div>
            <div class="mvbl-field">
              <label for="mvbl-website">Website</label>
              <input type="url" id="mvbl-website" name="website" placeholder="https://yourbusiness.com">
            </div>
            <div class="mvbl-field">
              <label for="mvbl-address">Address <span class="mvbl-opt">(optional)</span></label>
              <input type="text" id="mvbl-address" name="address" placeholder="123 Main St, City, State">
            </div>
          </div>

          <div class="mvbl-section-title">Social Media</div>
          <p class="mvbl-social-note">For Instagram, TikTok, YouTube and X — just enter your <strong>username</strong> (with or without @). For Facebook and LinkedIn, paste your <strong>full profile URL</strong>.</p>
          <div class="mvbl-socials-grid" id="mvbl-socials-grid">

            <!-- Username-based fields -->
            <?php
            $handle_socials = [
              'instagram' => [ 'label' => 'Instagram', 'placeholder' => 'username or @username',        'prefix' => 'https://www.instagram.com/' ],
              'tiktok'    => [ 'label' => 'TikTok',    'placeholder' => 'username or @username',        'prefix' => 'https://www.tiktok.com/@' ],
              'youtube'   => [ 'label' => 'YouTube',   'placeholder' => '@channelname or channel name', 'prefix' => 'https://www.youtube.com/@' ],
              'twitter'   => [ 'label' => 'X / Twitter', 'placeholder' => 'username or @username',     'prefix' => 'https://x.com/' ],
            ];
            foreach ( $handle_socials as $key => $info ) : ?>
            <div class="mvbl-social-field">
              <div class="mvbl-social-icon mvbl-icon-<?php echo $key; ?>"><?php echo mvbl_social_icon_svg( $key ); ?></div>
              <div class="mvbl-social-input-wrap">
                <input
                  type="text"
                  name="social_handle_<?php echo $key; ?>"
                  placeholder="<?php echo esc_attr($info['placeholder']); ?>"
                  data-network="<?php echo $key; ?>"
                  data-prefix="<?php echo esc_attr($info['prefix']); ?>"
                  data-type="handle"
                  class="mvbl-social-input mvbl-handle-input"
                >
                <span class="mvbl-social-preview" id="mvbl-preview-<?php echo $key; ?>"></span>
              </div>
            </div>
            <?php endforeach; ?>

            <!-- Full URL fields -->
            <?php
            $url_socials = [
              'facebook' => [ 'label' => 'Facebook',  'placeholder' => 'https://www.facebook.com/yourpage' ],
              'linkedin' => [ 'label' => 'LinkedIn',  'placeholder' => 'https://www.linkedin.com/in/your-name-xxxxx/' ],
            ];
            foreach ( $url_socials as $key => $info ) : ?>
            <div class="mvbl-social-field">
              <div class="mvbl-social-icon mvbl-icon-<?php echo $key; ?>"><?php echo mvbl_social_icon_svg( $key ); ?></div>
              <div class="mvbl-social-input-wrap">
                <input
                  type="url"
                  name="social_url_<?php echo $key; ?>"
                  placeholder="<?php echo esc_attr($info['placeholder']); ?>"
                  data-network="<?php echo $key; ?>"
                  data-type="url"
                  class="mvbl-social-input mvbl-url-input"
                >
              </div>
            </div>
            <?php endforeach; ?>

          </div>

          <div class="mvbl-step-nav">
            <span></span>
            <button type="button" class="mvbl-next-btn" data-next="2">Continue to Links →</button>
          </div>
        </div>

        <!-- ══ STEP 2: LINKS ══ -->
        <div class="mvbl-step-panel" id="mvbl-panel-2">
          <p class="mvbl-step-intro">Add up to 10 featured links — the big action buttons on your bio page.</p>

          <!-- Explainer note -->
          <div class="mvbl-explainer" id="mvbl-links-explainer">
            <button type="button" class="mvbl-explainer-toggle" id="mvbl-explainer-toggle">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
              What's the difference between these links and my website/social links?
              <svg class="mvbl-explainer-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="mvbl-explainer-body" id="mvbl-explainer-body" style="display:none;">
              <div class="mvbl-explainer-two-col">
                <div class="mvbl-explainer-col">
                  <div class="mvbl-explainer-col-title">Step 1 — Contact &amp; Social</div>
                  <p>Your phone, email, website and social media icons from Step 1 are automatically added to your bio page as <strong>small chips and icons</strong>. You don't need to add them here.</p>
                  <div class="mvbl-explainer-example">
                    <span class="mvbl-chip-preview">📞 (555) 000-0000</span>
                    <span class="mvbl-chip-preview">✉ you@email.com</span>
                    <span class="mvbl-chip-preview">🌐 yourbiz.com</span>
                  </div>
                </div>
                <div class="mvbl-explainer-col">
                  <div class="mvbl-explainer-col-title">Step 2 — Featured Links (this page)</div>
                  <p>These are the <strong>big prominent buttons</strong> in the center of your page — the actions you most want visitors to take. Think of them as your call-to-action links.</p>
                  <div class="mvbl-explainer-examples">
                    <div class="mvbl-link-preview-pill">📅 Book an Appointment</div>
                    <div class="mvbl-link-preview-pill">⭐ Leave Us a Review</div>
                    <div class="mvbl-link-preview-pill">🛍 Shop Our Products</div>
                    <div class="mvbl-link-preview-pill">📄 View Our Menu</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div id="mvbl-links-list">
            <div class="mvbl-link-row">
              <div class="mvbl-link-row-inner">
                <select name="link_icon[]" class="mvbl-link-icon-select">
                  <?php foreach ( mvbl_link_icons() as $val => $lbl ) : ?>
                    <option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($lbl); ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="text"  name="link_label[]" placeholder="Label  e.g. Book Appointment" class="mvbl-link-label">
                <input type="url"   name="link_url[]"   placeholder="URL    e.g. https://..." class="mvbl-link-url">
                <button type="button" class="mvbl-remove-link" title="Remove">✕</button>
              </div>
            </div>
          </div>

          <div class="mvbl-links-recommended">
            <strong>Tip:</strong> We recommend 3 featured links max — your website and social icons already appear separately on the page. Focus on your top actions: booking, reviews, menu, offers.
          </div>
          <button type="button" id="mvbl-add-link" class="mvbl-add-btn">+ Add another link</button>
          <div class="mvbl-links-count" id="mvbl-links-count">3 / 10 links</div>

          <div class="mvbl-step-nav">
            <button type="button" class="mvbl-back-btn" data-back="1">← Back</button>
            <button type="button" class="mvbl-next-btn" data-next="3">Continue to Design →</button>
          </div>
        </div>

        <!-- ══ STEP 3: DESIGN ══ -->
        <div class="mvbl-step-panel" id="mvbl-panel-3">

          <!-- Layout picker -->
          <div class="mvbl-section-title">Choose a Layout</div>
          <div class="mvbl-layout-grid" id="mvbl-layout-grid">
            <?php
            $layouts = [
              'layout-1' => [ 'name' => 'Classic Stack',   'desc' => 'Centered · avatar top · links below' ],
              'layout-2' => [ 'name' => 'Sidebar Profile', 'desc' => 'Avatar + socials left · links right' ],
              'layout-3' => [ 'name' => 'Banner Header',   'desc' => 'Cover banner · avatar overlapping · links below' ],
              'layout-4' => [ 'name' => 'Card Grid',       'desc' => 'Profile top · links in 2 columns' ],
            ];
            foreach ( $layouts as $key => $info ) : ?>
            <label class="mvbl-layout-option <?php echo $key === 'layout-1' ? 'selected' : ''; ?>" data-layout="<?php echo $key; ?>">
              <input type="radio" name="layout" value="<?php echo $key; ?>" <?php checked($key,'layout-1'); ?>>
              <div class="mvbl-layout-thumb mvbl-thumb-<?php echo $key; ?>">
                <?php echo mvbl_layout_thumb_svg( $key ); ?>
              </div>
              <div class="mvbl-layout-name"><?php echo esc_html($info['name']); ?></div>
              <div class="mvbl-layout-desc"><?php echo esc_html($info['desc']); ?></div>
            </label>
            <?php endforeach; ?>
          </div>

          <!-- Colors -->
          <div class="mvbl-section-title" style="margin-top:1.5rem;">Colors</div>
          <div class="mvbl-color-row">
            <div class="mvbl-field">
              <label for="mvbl-bg-color">Background</label>
              <div class="mvbl-color-wrap">
                <input type="color" id="mvbl-bg-color"     name="bg_color"     value="#ffffff">
                <div class="mvbl-color-presets" data-for="mvbl-bg-color">
                  <span style="background:#ffffff" data-color="#ffffff"></span>
                  <span style="background:#f9f8f5" data-color="#f9f8f5"></span>
                  <span style="background:#0f172a" data-color="#0f172a"></span>
                  <span style="background:#1a6b4a" data-color="#1a6b4a"></span>
                  <span style="background:#1a56db" data-color="#1a56db"></span>
                  <span style="background:#7c3aed" data-color="#7c3aed"></span>
                  <span style="background:#be185d" data-color="#be185d"></span>
                  <span style="background:#b45309" data-color="#b45309"></span>
                </div>
              </div>
            </div>
            <div class="mvbl-field">
              <label for="mvbl-accent-color">Accent / Buttons</label>
              <div class="mvbl-color-wrap">
                <input type="color" id="mvbl-accent-color" name="accent_color" value="#1a6b4a">
                <div class="mvbl-color-presets" data-for="mvbl-accent-color">
                  <span style="background:#1a6b4a" data-color="#1a6b4a"></span>
                  <span style="background:#1a56db" data-color="#1a56db"></span>
                  <span style="background:#7c3aed" data-color="#7c3aed"></span>
                  <span style="background:#be185d" data-color="#be185d"></span>
                  <span style="background:#b45309" data-color="#b45309"></span>
                  <span style="background:#dc2626" data-color="#dc2626"></span>
                  <span style="background:#0f172a" data-color="#0f172a"></span>
                  <span style="background:#374151" data-color="#374151"></span>
                </div>
              </div>
            </div>
            <div class="mvbl-field">
              <label for="mvbl-text-color">Text</label>
              <div class="mvbl-color-wrap">
                <input type="color" id="mvbl-text-color"   name="text_color"   value="#141414">
                <div class="mvbl-color-presets" data-for="mvbl-text-color">
                  <span style="background:#141414" data-color="#141414"></span>
                  <span style="background:#374151" data-color="#374151"></span>
                  <span style="background:#ffffff" data-color="#ffffff"></span>
                  <span style="background:#f1f5f9" data-color="#f1f5f9"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Error -->
          <div class="mvbl-error" id="mvbl-error" style="display:none;"></div>

          <div class="mvbl-step-nav">
            <button type="button" class="mvbl-back-btn" data-back="2">← Back</button>
            <button type="button" class="mvbl-submit-btn" id="mvbl-submit-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 3l14 9-14 9V3z"/></svg>
              Generate My Bio Page
            </button>
          </div>
        </div>

        <p class="mvbl-footer-note">Created by <a href="https://www.mediavines.com" target="_blank">Media Vines Corp</a></p>
      </div>
    </div>

    <!-- ── LOADING STATE ── -->
    <div id="mvbl-loading-state" style="display:none;" class="mvbl-loading-wrap">
      <div class="mvbl-spinner"></div>
      <p class="mvbl-loading-text">Building your bio page…</p>
      <p class="mvbl-loading-sub">Generating QR codes and saving your contact info.</p>
    </div>

    </div><!-- .mvbl-wrap -->
    <?php
    return ob_get_clean();
}

/* ── Helper: social icon SVGs ── */
function mvbl_social_icon_svg( $network ) {
    $icons = [
        'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>',
        'facebook'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
        'linkedin'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>',
        'youtube'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>',
        'tiktok'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>',
        'twitter'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 4l16 16M4 20L20 4"/></svg>',
    ];
    return $icons[$network] ?? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/></svg>';
}

/* ── Helper: link icon options ── */
function mvbl_link_icons() {
    return [
        'link'      => '🔗 Link',
        'calendar'  => '📅 Book / Schedule',
        'phone'     => '📞 Call',
        'mail'      => '📧 Email',
        'map'       => '📍 Location',
        'shop'      => '🛍 Shop',
        'video'     => '🎥 Video',
        'doc'       => '📄 Document / Menu',
        'star'      => '⭐ Reviews',
        'gift'      => '🎁 Offer / Promo',
        'chat'      => '💬 Message / Chat',
        'portfolio' => '🖼 Portfolio',
    ];
}

/* ── Helper: layout thumbnail SVGs ── */
function mvbl_layout_thumb_svg( $layout ) {
    $thumbs = [
        'layout-1' => '<svg viewBox="0 0 80 100" xmlns="http://www.w3.org/2000/svg"><circle cx="40" cy="22" r="12" fill="#1a6b4a" opacity=".7"/><rect x="25" y="40" width="30" height="5" rx="2" fill="#333" opacity=".5"/><rect x="30" y="49" width="20" height="4" rx="2" fill="#999" opacity=".5"/><rect x="10" y="60" width="60" height="10" rx="4" fill="#1a6b4a" opacity=".3"/><rect x="10" y="74" width="60" height="10" rx="4" fill="#1a6b4a" opacity=".2"/><rect x="10" y="88" width="60" height="10" rx="4" fill="#1a6b4a" opacity=".15"/></svg>',
        'layout-2' => '<svg viewBox="0 0 80 100" xmlns="http://www.w3.org/2000/svg"><circle cx="18" cy="22" r="10" fill="#1a56db" opacity=".7"/><circle cx="18" cy="38" r="3" fill="#999" opacity=".4"/><circle cx="18" cy="46" r="3" fill="#999" opacity=".4"/><circle cx="18" cy="54" r="3" fill="#999" opacity=".4"/><rect x="34" y="14" width="36" height="6" rx="2" fill="#333" opacity=".5"/><rect x="34" y="24" width="24" height="4" rx="2" fill="#999" opacity=".4"/><rect x="34" y="36" width="36" height="8" rx="3" fill="#1a56db" opacity=".2"/><rect x="34" y="48" width="36" height="8" rx="3" fill="#1a56db" opacity=".2"/><rect x="34" y="60" width="36" height="8" rx="3" fill="#1a56db" opacity=".2"/><rect x="34" y="72" width="36" height="8" rx="3" fill="#1a56db" opacity=".2"/></svg>',
        'layout-3' => '<svg viewBox="0 0 80 100" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="80" height="35" rx="4" fill="#1a6b4a" opacity=".4"/><circle cx="40" cy="35" r="12" fill="white" stroke="#1a6b4a" stroke-width="2"/><circle cx="40" cy="35" r="10" fill="#1a6b4a" opacity=".6"/><rect x="20" y="52" width="40" height="5" rx="2" fill="#333" opacity=".5"/><rect x="25" y="61" width="30" height="4" rx="2" fill="#999" opacity=".4"/><rect x="10" y="72" width="60" height="8" rx="3" fill="#1a6b4a" opacity=".25"/><rect x="10" y="84" width="60" height="8" rx="3" fill="#1a6b4a" opacity=".2"/></svg>',
        'layout-4' => '<svg viewBox="0 0 80 100" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="8" width="18" height="18" rx="4" fill="#d85a30" opacity=".7"/><rect x="28" y="10" width="30" height="5" rx="2" fill="#333" opacity=".5"/><rect x="28" y="19" width="20" height="4" rx="2" fill="#999" opacity=".4"/><rect x="5" y="35" width="33" height="14" rx="4" fill="#d85a30" opacity=".2"/><rect x="42" y="35" width="33" height="14" rx="4" fill="#d85a30" opacity=".2"/><rect x="5" y="54" width="33" height="14" rx="4" fill="#d85a30" opacity=".15"/><rect x="42" y="54" width="33" height="14" rx="4" fill="#d85a30" opacity=".15"/><rect x="5" y="73" width="33" height="14" rx="4" fill="#d85a30" opacity=".1"/><rect x="42" y="73" width="33" height="14" rx="4" fill="#d85a30" opacity=".1"/></svg>',
    ];
    return $thumbs[$layout] ?? '';
}
