<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'mv_review_form', 'mvrg_render_form' );

function mvrg_render_form( $atts ) {
    ob_start();
    ?>
    <div class="mvrg-form-wrap" id="mvrg-form-wrap">

        <!-- FORM STATE -->
        <div id="mvrg-form-state">
            <div class="mvrg-form-header">
                <div class="mvrg-form-header-inner">
                    <h2>Get Your Free Review Page</h2>
                    <p>Fill in your business details and we'll generate a beautiful, scannable Google review page — instantly.</p>
                </div>
            </div>

            <div class="mvrg-form-body">

                <!-- Logo Upload -->
                <div class="mvrg-field">
                    <label>Business Logo <span class="mvrg-opt">(optional)</span></label>
                    <div class="mvrg-upload-area" id="mvrg-upload-area">
                        <input type="file" id="mvrg-logo-file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml">
                        <div class="mvrg-upload-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </div>
                        <div class="mvrg-upload-text">Click to upload your logo</div>
                        <div class="mvrg-upload-sub">PNG, JPG, SVG, WebP · Max 5MB</div>
                    </div>
                    <div class="mvrg-preview-row" id="mvrg-preview-row">
                        <img id="mvrg-preview-img" src="" alt="Logo preview">
                        <div>
                            <div class="mvrg-preview-label">Logo selected ✓</div>
                            <button type="button" class="mvrg-remove-logo" id="mvrg-remove-logo">Remove</button>
                        </div>
                    </div>
                </div>

                <!-- Business Name -->
                <div class="mvrg-field">
                    <label for="mvrg-biz-name">Business Name <span class="mvrg-req">*</span></label>
                    <input type="text" id="mvrg-biz-name" name="biz_name" placeholder="e.g. Value Max Quality Builders" autocomplete="organization">
                </div>

                <!-- Review Link -->
                <div class="mvrg-field">
                    <label for="mvrg-review-link">
                        Google Review Link <span class="mvrg-req">*</span>
                        <button type="button" class="mvrg-help-toggle" id="mvrg-help-toggle">How to get it?</button>
                    </label>
                    <input type="url" id="mvrg-review-link" name="review_link" placeholder="https://g.page/r/...">
                    <div class="mvrg-field-hint">Must be from <strong>Google Business Profile → Ask for reviews</strong>.</div>

                    <!-- Inline how-to instructions -->
                    <div class="mvrg-howto" id="mvrg-howto" style="display:none;">
                        <div class="mvrg-howto-warn">
                            <strong>⚠ Don't use the Share button on Google Maps</strong> — that link only goes to your profile page, not the review form.
                        </div>
                        <div class="mvrg-howto-steps">
                            <div class="mvrg-howto-step"><span class="mvrg-howto-num">1</span><span>Go to <strong>business.google.com</strong> and sign in.</span></div>
                            <div class="mvrg-howto-step"><span class="mvrg-howto-num">2</span><span>Select your business from the dashboard.</span></div>
                            <div class="mvrg-howto-step"><span class="mvrg-howto-num">3</span><span>Click <strong>"Ask for reviews"</strong> (or "Get more reviews").</span></div>
                            <div class="mvrg-howto-step"><span class="mvrg-howto-num">4</span><span>Copy the link — it looks like <code>https://g.page/r/XXXX/review</code></span></div>
                            <div class="mvrg-howto-step"><span class="mvrg-howto-num">5</span><span>Paste it in the field above and generate your page!</span></div>
                        </div>
                    </div>
                </div>

                <!-- Tagline -->
                <div class="mvrg-field">
                    <label for="mvrg-tagline">Thank-you Message <span class="mvrg-opt">(optional)</span></label>
                    <textarea id="mvrg-tagline" name="tagline" placeholder="e.g. Your review helps our small business grow and lets others find us!"></textarea>
                </div>

                <!-- Industry -->
                <div class="mvrg-field">
                    <label for="mvrg-industry">Industry <span class="mvrg-opt">(optional)</span></label>
                    <select id="mvrg-industry" name="industry">
                        <option value="">Select your industry</option>
                        <option value="home-services">Home Services / Construction</option>
                        <option value="restaurant">Restaurant / Food</option>
                        <option value="health-beauty">Health &amp; Beauty</option>
                        <option value="retail">Retail / Shop</option>
                        <option value="professional">Professional Services</option>
                        <option value="auto">Auto Services</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <!-- Error message -->
                <div class="mvrg-error" id="mvrg-error" style="display:none;"></div>

                <!-- Submit -->
                <button type="button" class="mvrg-submit-btn" id="mvrg-submit-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 3l14 9-14 9V3z"/></svg>
                    Generate My Review Page
                </button>

                <p class="mvrg-footer-note">Created by <a href="https://www.mediavines.com" target="_blank">Media Vines Corp</a></p>
            </div>
        </div>

        <!-- LOADING STATE -->
        <div id="mvrg-loading-state" style="display:none;" class="mvrg-loading-wrap">
            <div class="mvrg-spinner"></div>
            <p class="mvrg-loading-text">Building your review page…</p>
            <p class="mvrg-loading-sub">Generating QR code and saving everything.</p>
        </div>

        <!-- SUCCESS STATE -->
        <div id="mvrg-success-state" style="display:none;" class="mvrg-success-wrap">
            <div class="mvrg-success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h3>Your review page is live!</h3>
            <p>Share this link with your customers:</p>
            <div class="mvrg-result-url-box">
                <span id="mvrg-result-url"></span>
                <button type="button" id="mvrg-copy-btn" class="mvrg-copy-btn">Copy</button>
            </div>
            <div class="mvrg-success-actions">
                <a id="mvrg-view-btn" href="#" target="_blank" class="mvrg-btn-primary">View Page →</a>
                <button type="button" id="mvrg-share-email-btn" class="mvrg-btn-secondary">Share via Email</button>
                <button type="button" id="mvrg-share-sms-btn" class="mvrg-btn-secondary">Share via Text</button>
            </div>
            <button type="button" id="mvrg-new-btn" class="mvrg-new-btn">Create another page</button>
            <p class="mvrg-footer-note">Created by <a href="https://mediavines.com" target="_blank">Media Vines Corp</a></p>
        </div>

    </div><!-- .mvrg-form-wrap -->
    <?php
    return ob_get_clean();
}
