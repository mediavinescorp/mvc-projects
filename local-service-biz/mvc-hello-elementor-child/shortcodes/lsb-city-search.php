<?php
/**
 * Shortcode: City + Industry Search → Redirect
 * File:      shortcodes/lsb-city-search.php
 * Usage:     [lsb_city_search] or [lsb_city_search results_url="/business-results/"]
 *
 * Behavior:
 *   - Both selected  → /cities/{city-slug}/businesses/?industry={industry-slug}
 *   - City only      → /cities/{city-slug}/businesses/
 *   - Industry only  → /industries/{industry-slug}/
 *   - Neither        → validation message, no redirect
 */

if ( ! function_exists( 'lsb_city_search_shortcode' ) ) :

function lsb_city_search_shortcode( $atts ) {

    $atts = shortcode_atts( [
        'results_url' => '', // unused now but kept for backward compat
    ], $atts, 'lsb_city_search' );

    // ── 1. Fetch city terms (name + slug) ─────────────────────────────────
    $city_terms = get_terms( [
        'taxonomy'   => 'city_cat',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'fields'     => 'all',
        'parent'     => 0,
    ] );

    if ( is_wp_error( $city_terms ) ) {
        $city_terms = [];
    }

    // ── 2. Fetch industry terms dynamically from industry_cat ─────────────
    $industry_terms = get_terms( [
        'taxonomy'   => 'industry_cat',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'fields'     => 'all',
        'parent'     => 0,
    ] );

    if ( is_wp_error( $industry_terms ) ) {
        $industry_terms = [];
    }

    $base_url = esc_js( trailingslashit( home_url() ) );

    ob_start();
    ?>

    <div class="lsb-cs-wrap">

        <div class="lsb-cs-fields">

            <!-- Industry dropdown — dynamically pulled from industry_cat taxonomy -->
            <div class="lsb-cs-field">
                <label class="lsb-cs-label" for="lsb-cs-industry">Industry</label>
                <div class="lsb-cs-select-wrap">
                    <select id="lsb-cs-industry" class="lsb-cs-select">
                        <option value="">All Industries</option>
                        <?php foreach ( $industry_terms as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="lsb-cs-chevron" width="12" height="8" viewBox="0 0 12 8" fill="none">
                        <path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <!-- City dropdown — dynamically pulled from city_cat taxonomy -->
            <div class="lsb-cs-field">
                <label class="lsb-cs-label" for="lsb-cs-city">City</label>
                <div class="lsb-cs-select-wrap">
                    <select id="lsb-cs-city" class="lsb-cs-select">
                        <option value="">All Cities</option>
                        <?php foreach ( $city_terms as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="lsb-cs-chevron" width="12" height="8" viewBox="0 0 12 8" fill="none">
                        <path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <!-- Search button -->
            <button id="lsb-cs-btn" class="lsb-cs-btn" type="button">
                Search →
            </button>

        </div>

        <!-- Validation message -->
        <p class="lsb-cs-error" id="lsb-cs-error" style="display:none;">
            Please select at least a city or an industry to search.
        </p>

    </div>

    <style>
    .lsb-cs-wrap {
        width: 100%;
    }
    .lsb-cs-fields {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .lsb-cs-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 160px;
    }
    .lsb-cs-label {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.45);
    }
    .lsb-cs-select-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .lsb-cs-select {
        width: 100%;
        background: rgba(255,255,255,0.06) !important;
        border: 1px solid rgba(255,255,255,0.12) !important;
        border-radius: 8px !important;
        color: #ffffff !important;
        font-family: 'DM Sans', sans-serif !important;
        font-size: 0.92rem !important;
        padding: 13px 36px 13px 16px !important;
        outline: none !important;
        cursor: pointer !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        transition: border-color 0.2s !important;
    }
    .lsb-cs-select:focus {
        border-color: #00C9A7 !important;
        background: rgba(255,255,255,0.09) !important;
    }
    .lsb-cs-select option {
        background: #1B2F45;
        color: #ffffff;
    }
    .lsb-cs-chevron {
        position: absolute;
        right: 12px;
        pointer-events: none;
        color: #00C9A7;
        flex-shrink: 0;
    }
    .lsb-cs-btn {
        background: #00C9A7 !important;
        color: #0D1B2A !important;
        font-family: 'Syne', sans-serif !important;
        font-weight: 700 !important;
        font-size: 0.92rem !important;
        padding: 13px 28px !important;
        border: none !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        letter-spacing: 0.02em !important;
        white-space: nowrap !important;
        transition: background 0.2s, transform 0.15s !important;
        align-self: flex-end;
    }
    .lsb-cs-btn:hover {
        background: #00A88C !important;
        transform: translateY(-1px) !important;
    }
    .lsb-cs-error {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.82rem;
        color: #F4C542;
        margin-top: 10px !important;
        padding-left: 2px;
    }
    @media (max-width: 640px) {
        .lsb-cs-fields   { flex-direction: column; }
        .lsb-cs-field     { min-width: 100%; }
        .lsb-cs-btn       { width: 100% !important; }
    }
    </style>

    <script>
    (function () {

        var btn      = document.getElementById( 'lsb-cs-btn' );
        var selCity  = document.getElementById( 'lsb-cs-city' );
        var selInd   = document.getElementById( 'lsb-cs-industry' );
        var errMsg   = document.getElementById( 'lsb-cs-error' );
        var baseUrl  = '<?php echo $base_url; ?>';

        btn.addEventListener( 'click', function () {

            var citySlug     = selCity.value.trim();
            var industrySlug = selInd.value.trim();

            // Neither selected — show validation
            if ( ! citySlug && ! industrySlug ) {
                errMsg.style.display = 'block';
                return;
            }

            errMsg.style.display = 'none';

            var url = '';

            if ( citySlug && industrySlug ) {
                // Both → /cities/{city}/businesses/?industry={industry}
                url = baseUrl + 'cities/' + citySlug + '/businesses/?industry=' + industrySlug;

            } else if ( citySlug ) {
                // City only → /cities/{city}/businesses/
                url = baseUrl + 'cities/' + citySlug + '/businesses/';

            } else {
                // Industry only → /industries/{industry}/
                url = baseUrl + 'industries/' + industrySlug + '/';
            }

            window.location.href = url;
        } );

        // Hide error on any change
        selCity.addEventListener( 'change', function () { errMsg.style.display = 'none'; } );
        selInd.addEventListener(  'change', function () { errMsg.style.display = 'none'; } );

    }());
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode( 'lsb_city_search', 'lsb_city_search_shortcode' );

endif;