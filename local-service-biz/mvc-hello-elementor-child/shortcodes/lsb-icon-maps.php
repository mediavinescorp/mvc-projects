<?php
/**
 * LSB Icon Maps — Centralized icon definitions for LocalServiceBiz
 *
 * File: /shortcodes/lsb-icon-maps.php
 * Included in functions.php:
 *   require_once get_stylesheet_directory() . '/shortcodes/lsb-icon-maps.php';
 *
 * Functions:
 *   lsb_get_industry_icon( $slug )           → emoji for an industry_cat slug
 *   lsb_get_service_icon( $slug )            → emoji for a service CPT slug
 *   lsb_resolve_tip_icon( $text, $index )    → emoji resolved from free-text keyword
 *
 * To update any icon, edit only this file.
 * All shortcodes and templates call these functions — no other files need touching.
 */

// ============================================================
// 1. INDUSTRY ICONS
//    Keyed by exact industry_cat taxonomy slug.
//    Add new industries here as the site grows.
// ============================================================

if ( ! function_exists( 'lsb_get_industry_icon' ) ) :
function lsb_get_industry_icon( $slug ) {

    $map = [
        'hvac'               => '❄️',
        'roofing'            => '🏠',
        'plumbing'           => '🔧',
        'auto-body-shop'     => '🚗',
        'locksmith'          => '🔑',
        'restoration'        => '🛠️',
        'catering'           => '🍽️',
        'realtors'           => '🏡',
        'public-adjusting'   => '📋',
        'scrap-metals'       => '♻️',
        'dental-broker'      => '🦷',
        'general-contractor' => '🏗️',
    ];

    return $map[ sanitize_title( $slug ) ] ?? '🔧';
}
endif;


// ============================================================
// 2. SERVICE ICONS
//    Keyed by service CPT post slug.
//    Grouped by industry for readability.
//    Add new services here as CPT posts are created.
// ============================================================

if ( ! function_exists( 'lsb_get_service_icon' ) ) :
function lsb_get_service_icon( $slug ) {

    $map = [

        // ── HVAC ──────────────────────────────────────────
        'ac-installation'            => '❄️',
        'ac-repair'                  => '❄️',
        'ac-maintenance'             => '❄️',
        'air-conditioning'           => '❄️',
        'heating-repair'             => '🌡️',
        'heating-installation'       => '🌡️',
        'furnace-repair'             => '🌡️',
        'furnace-installation'       => '🌡️',
        'boiler-repair'              => '🌡️',
        'heat-pump-installation'     => '🌡️',
        'heat-pump-repair'           => '🌡️',
        'duct-cleaning'              => '💨',
        'duct-installation'          => '💨',
        'hvac-maintenance'           => '🔧',
        'hvac-inspection'            => '🔍',
        'thermostat-installation'    => '🌡️',
        'ventilation'                => '💨',
        'air-quality'                => '💨',
        'mini-split-installation'    => '❄️',
        'commercial-hvac'            => '🏢',

        // ── ROOFING ───────────────────────────────────────
        'roof-installation'          => '🏠',
        'roof-repair'                => '🏠',
        'roof-replacement'           => '🏠',
        'roof-inspection'            => '🔍',
        'roof-maintenance'           => '🏠',
        'shingle-repair'             => '🏠',
        'tile-roof'                  => '🏠',
        'flat-roof'                  => '🏠',
        'metal-roof'                 => '🏠',
        'gutter-installation'        => '🏠',
        'gutter-repair'              => '🏠',
        'gutter-cleaning'            => '🏠',
        'skylight-installation'      => '🪟',
        'emergency-roof-repair'      => '🚨',
        'storm-damage-roofing'       => '⛈️',
        'solar-roofing'              => '☀️',
        'attic-insulation'           => '🏠',

        // ── PLUMBING ──────────────────────────────────────
        'drain-cleaning'             => '🔧',
        'pipe-repair'                => '🔧',
        'pipe-installation'          => '🔧',
        'leak-detection'             => '💧',
        'leak-repair'                => '💧',
        'water-heater-installation'  => '🚿',
        'water-heater-repair'        => '🚿',
        'toilet-repair'              => '🚽',
        'toilet-installation'        => '🚽',
        'faucet-repair'              => '🔧',
        'faucet-installation'        => '🔧',
        'sewer-repair'               => '🔧',
        'sewer-cleaning'             => '🔧',
        'water-line-repair'          => '💧',
        'gas-line-repair'            => '⚠️',
        'hydro-jetting'              => '💧',
        'emergency-plumbing'         => '🚨',
        'repiping'                   => '🔧',
        'water-filtration'           => '💧',
        'sump-pump'                  => '💧',

        // ── AUTO BODY ─────────────────────────────────────
        'collision-repair'           => '🚗',
        'dent-repair'                => '🚗',
        'paintless-dent-repair'      => '🚗',
        'auto-painting'              => '🎨',
        'bumper-repair'              => '🚗',
        'fender-repair'              => '🚗',
        'frame-repair'               => '🚗',
        'windshield-repair'          => '🪟',
        'windshield-replacement'     => '🪟',
        'auto-detailing'             => '✨',
        'scratch-repair'             => '🚗',
        'insurance-claim-repair'     => '📋',
        'classic-car-restoration'    => '🚗',

        // ── LOCKSMITH ─────────────────────────────────────
        'lockout-service'            => '🔑',
        'lock-installation'          => '🔒',
        'lock-repair'                => '🔒',
        'lock-rekey'                 => '🔑',
        'key-duplication'            => '🔑',
        'deadbolt-installation'      => '🔒',
        'smart-lock-installation'    => '🔒',
        'safe-installation'          => '🔒',
        'safe-opening'               => '🔒',
        'car-lockout'                => '🚗',
        'ignition-repair'            => '🚗',
        'master-key-system'          => '🔑',
        'access-control'             => '🔒',
        'emergency-locksmith'        => '🚨',

        // ── RESTORATION ───────────────────────────────────
        'water-damage-restoration'   => '💧',
        'fire-damage-restoration'    => '🔥',
        'smoke-damage-restoration'   => '🔥',
        'mold-remediation'           => '🦠',
        'mold-inspection'            => '🔍',
        'flood-cleanup'              => '🌊',
        'sewage-cleanup'             => '🔧',
        'storm-damage-restoration'   => '⛈️',
        'biohazard-cleanup'          => '⚠️',
        'asbestos-removal'           => '⚠️',
        'odor-removal'               => '💨',
        'content-restoration'        => '🛠️',
        'emergency-restoration'      => '🚨',
        'board-up-services'          => '🏚️',
        'debris-removal'             => '🗑️',

        // ── CATERING ──────────────────────────────────────
        'wedding-catering'           => '💍',
        'corporate-catering'         => '🏢',
        'private-event-catering'     => '🎉',
        'birthday-catering'          => '🎂',
        'buffet-catering'            => '🍽️',
        'food-truck'                 => '🚚',
        'meal-prep'                  => '🍱',
        'drop-off-catering'          => '🍽️',
        'full-service-catering'      => '🍽️',
        'bbq-catering'               => '🍖',
        'bar-service'                => '🍹',
        'dessert-catering'           => '🎂',

        // ── REALTORS ──────────────────────────────────────
        'home-buying'                => '🏡',
        'home-selling'               => '🏷️',
        'property-listing'           => '🏷️',
        'buyer-representation'       => '🤝',
        'seller-representation'      => '🤝',
        'property-valuation'         => '📊',
        'investment-property'        => '💰',
        'commercial-real-estate'     => '🏢',
        'rental-property'            => '🔑',
        'relocation-services'        => '📦',
        'first-time-buyer'           => '🏠',
        'luxury-real-estate'         => '🏰',
        'foreclosure'                => '📋',
        'short-sale'                 => '📋',

        // ── PUBLIC ADJUSTING ──────────────────────────────
        'insurance-claim-filing'     => '📋',
        'claim-negotiation'          => '⚖️',
        'property-damage-claim'      => '🏚️',
        'fire-damage-claim'          => '🔥',
        'water-damage-claim'         => '💧',
        'storm-damage-claim'         => '⛈️',
        'roof-damage-claim'          => '🏠',
        'mold-claim'                 => '🦠',
        'business-interruption'      => '🏢',
        'claim-review'               => '🔍',
        'denied-claim'               => '⚠️',
        'underpaid-claim'            => '💰',
        'flood-insurance-claim'      => '🌊',

        // ── SCRAP METALS ──────────────────────────────────
        'scrap-metal-pickup'         => '🚛',
        'copper-recycling'           => '⚙️',
        'aluminum-recycling'         => '⚙️',
        'steel-recycling'            => '⚙️',
        'iron-recycling'             => '⚙️',
        'catalytic-converter'        => '🚗',
        'appliance-removal'          => '🗑️',
        'e-waste-recycling'          => '♻️',
        'junk-car-removal'           => '🚗',
        'metal-hauling'              => '🚛',
        'commercial-scrap'           => '🏭',
        'wire-recycling'             => '⚙️',

        // ── DENTAL BROKER ─────────────────────────────────
        'dental-practice-sales'      => '🦷',
        'dental-practice-purchase'   => '🦷',
        'dental-office-valuation'    => '📊',
        'practice-transition'        => '🤝',
        'dental-equipment-sales'     => '🦷',
        'associate-placement'        => '👷',
        'dental-consulting'          => '🎓',
        'partnership-agreements'     => '📝',
        'dental-lease-negotiation'   => '📋',

        // ── GENERAL CONTRACTOR ────────────────────────────
        'home-renovation'            => '🏗️',
        'kitchen-remodel'            => '🏗️',
        'bathroom-remodel'           => '🏗️',
        'room-addition'              => '🏗️',
        'basement-finishing'         => '🏗️',
        'flooring-installation'      => '🏗️',
        'drywall-installation'       => '🏗️',
        'painting'                   => '🎨',
        'deck-construction'          => '🏗️',
        'fence-installation'         => '🏗️',
        'concrete-work'              => '🏗️',
        'commercial-construction'    => '🏢',
    ];

    return $map[ sanitize_title( $slug ) ] ?? '🔧';
}
endif;


// ============================================================
// 3. TIP ICON RESOLVER
//    Takes free-text from the tip_icon ACF sub-field and maps
//    it to an emoji via keyword matching.
//    Order matters — more specific keywords listed first.
//
//    ACF field instruction to show editors:
//    "Enter a keyword describing the tip, e.g: hvac, plumbing,
//     roofing, locksmith, fire, water, insurance, car, food,
//     key, damage, mold, claim, paint, solar, warranty, etc."
// ============================================================

if ( ! function_exists( 'lsb_resolve_tip_icon' ) ) :
function lsb_resolve_tip_icon( $text, $index = 0 ) {

    $defaults = [ '📍', '💡', '🏙️', '🌟', '🔑', '🛠️', '📋', '🤝', '✅', '🗺️' ];

    if ( empty( $text ) ) {
        return $defaults[ $index % count( $defaults ) ];
    }

    $raw = trim( $text );

    // Already an emoji / non-ASCII symbol — use as-is
    if ( mb_strlen( $raw ) <= 4 && preg_match( '/[^\x00-\x7F]/', $raw ) ) {
        return $raw;
    }

    $lower = strtolower( $raw );

    $map = [

        // ── Industry names (exact) ─────────────────────────
        'hvac'               => '❄️',
        'roofing'            => '🏠',
        'plumbing'           => '🔧',
        'auto body'          => '🚗',
        'auto-body'          => '🚗',
        'locksmith'          => '🔑',
        'restoration'        => '🛠️',
        'catering'           => '🍽️',
        'realtors'           => '🏡',
        'realtor'            => '🏡',
        'public adjusting'   => '📋',
        'public-adjusting'   => '📋',
        'scrap metals'       => '♻️',
        'scrap-metals'       => '♻️',
        'dental broker'      => '🦷',
        'dental-broker'      => '🦷',
        'general contractor' => '🏗️',

        // ── Location / navigation ──────────────────────────
        'location'           => '📍',
        'map'                => '🗺️',
        'navigate'           => '🧭',
        'direction'          => '🧭',
        'address'            => '📍',
        'neighborhood'       => '🏘️',
        'area'               => '📍',
        'district'           => '🏙️',
        'city'               => '🏙️',
        'zone'               => '📍',

        // ── Weather / environment ──────────────────────────
        'weather'            => '🌤️',
        'heat'               => '🌡️',
        'hot'                => '🌡️',
        'cold'               => '❄️',
        'rain'               => '🌧️',
        'flood'              => '🌊',
        'fire'               => '🔥',
        'wind'               => '💨',
        'earthquake'         => '🏚️',
        'storm'              => '⛈️',
        'disaster'           => '🚨',
        'humidity'           => '💧',
        'drought'            => '🌡️',
        'snow'               => '❄️',

        // ── Water / plumbing / restoration ────────────────
        'water'              => '💧',
        'leak'               => '💧',
        'mold'               => '🦠',
        'moisture'           => '💧',
        'sewage'             => '🔧',
        'drain'              => '🔧',
        'sewer'              => '🔧',
        'pipe'               => '🔧',
        'flood damage'       => '🌊',
        'water damage'       => '💧',

        // ── Home / property ────────────────────────────────
        'home'               => '🏠',
        'house'              => '🏠',
        'property'           => '🏡',
        'roof'               => '🏠',
        'attic'              => '🏠',
        'gutter'             => '🏠',
        'shingle'            => '🏠',
        'insulation'         => '🏠',
        'window'             => '🪟',
        'door'               => '🚪',
        'garage'             => '🏠',
        'foundation'         => '🏗️',
        'basement'           => '🏠',
        'remodel'            => '🏗️',
        'renovation'         => '🏗️',
        'construction'       => '🏗️',

        // ── HVAC specifics ─────────────────────────────────
        'ac'                 => '❄️',
        'air conditioning'   => '❄️',
        'cooling'            => '❄️',
        'heating'            => '🌡️',
        'furnace'            => '🌡️',
        'boiler'             => '🌡️',
        'heat pump'          => '🌡️',
        'thermostat'         => '🌡️',
        'duct'               => '💨',
        'filter'             => '💨',
        'ventilation'        => '💨',
        'air quality'        => '💨',
        'solar'              => '☀️',
        'panel'              => '☀️',

        // ── Auto body specifics ────────────────────────────
        'car'                => '🚗',
        'auto'               => '🚗',
        'vehicle'            => '🚗',
        'dent'               => '🚗',
        'collision'          => '🚗',
        'bumper'             => '🚗',
        'fender'             => '🚗',
        'scratch'            => '🚗',
        'paint'              => '🎨',
        'repaint'            => '🎨',
        'detail'             => '✨',
        'detailing'          => '✨',
        'traffic'            => '🚦',
        'parking'            => '🅿️',
        'drive'              => '🚗',

        // ── Locksmith specifics ────────────────────────────
        'lock'               => '🔒',
        'key'                => '🔑',
        'deadbolt'           => '🔒',
        'lockout'            => '🔑',
        'rekey'              => '🔑',
        'access'             => '🔒',
        'safe'               => '🔒',

        // ── Restoration specifics ──────────────────────────
        'smoke'              => '🔥',
        'soot'               => '🔥',
        'biohazard'          => '⚠️',
        'asbestos'           => '⚠️',
        'odor'               => '💨',
        'debris'             => '🗑️',
        'board up'           => '🏚️',
        'damage'             => '🏚️',

        // ── Catering specifics ─────────────────────────────
        'food'               => '🍽️',
        'restaurant'         => '🍽️',
        'meal'               => '🍽️',
        'menu'               => '🍽️',
        'event'              => '🎉',
        'party'              => '🎉',
        'wedding'            => '💍',
        'corporate'          => '🏢',
        'bbq'                => '🍖',
        'buffet'             => '🍽️',
        'bar'                => '🍹',
        'dessert'            => '🎂',

        // ── Real estate specifics ──────────────────────────
        'real estate'        => '🏡',
        'sell'               => '🏷️',
        'buy'                => '🏡',
        'rent'               => '🔑',
        'mortgage'           => '🏦',
        'loan'               => '🏦',
        'bank'               => '🏦',
        'closing'            => '📝',
        'escrow'             => '📝',
        'title'              => '📄',
        'listing'            => '🏷️',
        'open house'         => '🏡',
        'agent'              => '🤝',
        'broker'             => '🤝',
        'appraise'           => '📊',
        'appraisal'          => '📊',
        'investment'         => '💰',
        'luxury'             => '🏰',
        'relocation'         => '📦',
        'moving'             => '📦',

        // ── Public adjusting specifics ─────────────────────
        'claim'              => '📋',
        'adjuster'           => '📋',
        'adjusting'          => '📋',
        'settlement'         => '💰',
        'dispute'            => '⚖️',
        'policy'             => '📄',
        'coverage'           => '📄',
        'deductible'         => '💵',
        'payout'             => '💰',
        'denial'             => '⚠️',
        'denied'             => '⚠️',
        'underpaid'          => '💰',
        'negotiat'           => '⚖️',

        // ── Scrap metals specifics ─────────────────────────
        'scrap'              => '♻️',
        'metal'              => '⚙️',
        'recycle'            => '♻️',
        'copper'             => '⚙️',
        'aluminum'           => '⚙️',
        'steel'              => '⚙️',
        'iron'               => '⚙️',
        'wire'               => '⚙️',
        'junk'               => '🗑️',
        'haul'               => '🚛',
        'pickup'             => '🚛',
        'e-waste'            => '♻️',
        'appliance'          => '🗑️',

        // ── Dental broker specifics ────────────────────────
        'dental'             => '🦷',
        'tooth'              => '🦷',
        'dentist'            => '🦷',
        'oral'               => '🦷',
        'practice'           => '🏥',
        'clinic'             => '🏥',
        'office'             => '🏥',

        // ── Business / money ───────────────────────────────
        'price'              => '💰',
        'cost'               => '💰',
        'budget'             => '💵',
        'money'              => '💰',
        'pay'                => '💳',
        'payment'            => '💳',
        'insurance'          => '📋',
        'license'            => '📄',
        'permit'             => '📄',
        'contract'           => '📝',
        'quote'              => '💬',
        'estimate'           => '📊',
        'warranty'           => '📄',
        'guarantee'          => '✅',
        'discount'           => '💰',
        'deal'               => '💰',
        'promo'              => '🏷️',
        'offer'              => '🏷️',
        'free'               => '🎁',
        'seasonal'           => '📅',
        'spring'             => '🌱',
        'summer'             => '☀️',
        'fall'               => '🍂',
        'winter'             => '❄️',

        // ── People / service quality ───────────────────────
        'professional'       => '👷',
        'contractor'         => '👷',
        'technician'         => '🔧',
        'expert'             => '🎓',
        'certified'          => '✅',
        'verify'             => '✅',
        'trust'              => '🤝',
        'review'             => '⭐',
        'rating'             => '⭐',
        'star'               => '⭐',
        'recommend'          => '👍',
        'crew'               => '👷',
        'team'               => '👷',
        'staff'              => '👷',
        'worker'             => '👷',
        'experience'         => '🎓',
        'credential'         => '📄',
        'background'         => '🔍',
        'inspection'         => '🔍',
        'search'             => '🔍',
        'research'           => '🔍',
        'compare'            => '📊',

        // ── Tips / advice ──────────────────────────────────
        'tip'                => '💡',
        'advice'             => '💡',
        'hint'               => '💡',
        'idea'               => '💡',
        'note'               => '📌',
        'warning'            => '⚠️',
        'alert'              => '🚨',
        'emergency'          => '🚨',
        'urgent'             => '🚨',
        'question'           => '❓',
        'ask'                => '❓',
        'info'               => 'ℹ️',
        'guide'              => '📖',
        'list'               => '📋',
        'check'              => '✅',
        'safety'             => '🦺',
        'health'             => '💊',

        // ── Contact / scheduling ───────────────────────────
        'phone'              => '📞',
        'call'               => '📞',
        'contact'            => '📞',
        'schedule'           => '📅',
        'appointment'        => '📅',
        'time'               => '⏰',
        'hour'               => '⏰',
        'electric'           => '⚡',
        'electrical'         => '⚡',
        'repair'             => '🔧',
    ];

    // 1. Exact match
    if ( isset( $map[ $lower ] ) ) {
        return $map[ $lower ];
    }

    // 2. Partial keyword scan — first hit wins
    foreach ( $map as $keyword => $emoji ) {
        if ( strpos( $lower, $keyword ) !== false ) {
            return $emoji;
        }
    }

    // 3. Positional default
    return $defaults[ $index % count( $defaults ) ];
}
endif;