<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id      = get_the_ID();
$full_name    = get_post_meta( $post_id, 'full_name',    true ) ?: get_the_title();
$job_title    = get_post_meta( $post_id, 'job_title',    true );
$company      = get_post_meta( $post_id, 'company',      true );
$bio          = get_post_meta( $post_id, 'bio',          true );
$phone        = get_post_meta( $post_id, 'phone',        true );
$email        = get_post_meta( $post_id, 'email',        true );
$website      = get_post_meta( $post_id, 'website',      true );
$address      = get_post_meta( $post_id, 'address',      true );
$avatar_url   = get_post_meta( $post_id, 'avatar_url',   true );
$layout       = get_post_meta( $post_id, 'layout',       true ) ?: 'layout-1';
$bg_color     = get_post_meta( $post_id, 'bg_color',     true ) ?: '#ffffff';
$accent_color = get_post_meta( $post_id, 'accent_color', true ) ?: '#1a6b4a';
$text_color   = get_post_meta( $post_id, 'text_color',   true ) ?: '#141414';
$qr_page_url  = get_post_meta( $post_id, 'qr_page_url',  true );
$qr_vcard_url = get_post_meta( $post_id, 'qr_vcard_url', true );
$vcard_url    = get_post_meta( $post_id, 'vcard_url',    true );
$socials_raw  = get_post_meta( $post_id, 'socials',      true );
$links_raw    = get_post_meta( $post_id, 'links',        true );

$socials = json_decode( $socials_raw ?: '[]', true );
$links   = json_decode( $links_raw   ?: '[]', true );
if ( ! is_array($socials) ) $socials = [];
if ( ! is_array($links)   ) $links   = [];

// Initials fallback
$words    = array_filter( explode(' ', $full_name) );
$initials = implode('', array_map( fn($w) => strtoupper(mb_substr($w,0,1)), array_slice($words,0,2) ));

// vCard download URL
$vcard_download = add_query_arg( 'mvbl_vcard', $post_id, home_url('/') );

// Determine if dark background
$is_dark_bg = mvbl_is_dark_color( $bg_color );
$muted_color = $is_dark_bg ? 'rgba(255,255,255,0.65)' : 'rgba(0,0,0,0.55)';
$border_color = $is_dark_bg ? 'rgba(255,255,255,0.12)' : 'rgba(0,0,0,0.1)';

function mvbl_is_dark_color($hex) {
    $hex = ltrim($hex,'#');
    if(strlen($hex)==3) $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r=hexdec(substr($hex,0,2));$g=hexdec(substr($hex,2,2));$b=hexdec(substr($hex,4,2));
    return (($r*299+$g*587+$b*114)/1000) < 128;
}

$page_title = $full_name . ( $job_title ? ' — ' . $job_title : '' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html($page_title); ?></title>
<meta name="description" content="<?php echo esc_attr( $bio ?: 'Contact ' . $full_name ); ?>">
<meta name="robots" content="noindex, nofollow">
<!-- Open Graph for link sharing -->
<meta property="og:title"       content="<?php echo esc_attr($full_name); ?>">
<meta property="og:description" content="<?php echo esc_attr($bio ?: $job_title); ?>">
<?php if ($avatar_url) : ?><meta property="og:image" content="<?php echo esc_url($avatar_url); ?>"><?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,600;1,400&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
<?php wp_head(); ?>
<style>
:root {
  --bg:     <?php echo esc_attr($bg_color); ?>;
  --accent: <?php echo esc_attr($accent_color); ?>;
  --ink:    <?php echo esc_attr($text_color); ?>;
  --muted:  <?php echo esc_attr($muted_color); ?>;
  --border: <?php echo esc_attr($border_color); ?>;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{min-height:100vh;}
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;}
a{color:inherit;text-decoration:none;}

/* ── Shared ── */
.bl-page{min-height:100vh;padding:2rem 1rem 4rem;display:flex;flex-direction:column;align-items:center;}
.bl-avatar-img{width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid var(--accent);}
.bl-avatar-init{width:90px;height:90px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-size:32px;color:#fff;font-weight:600;flex-shrink:0;}
.bl-name{font-family:'Fraunces',serif;font-size:1.6rem;font-weight:600;line-height:1.2;color:var(--ink);margin-bottom:.2rem;}
.bl-title{font-size:14px;color:var(--muted);margin-bottom:.15rem;}
.bl-company{font-size:13px;color:var(--muted);font-weight:500;}
.bl-bio{font-size:14px;color:var(--muted);line-height:1.6;max-width:400px;}
.bl-socials{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;}
.bl-social-btn{width:38px;height:38px;border-radius:50%;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;transition:opacity .15s;}
.bl-social-btn:hover{opacity:.7;}
.bl-social-btn svg{width:18px;height:18px;stroke:var(--ink);}
.bl-links{display:flex;flex-direction:column;gap:10px;width:100%;}
.bl-link-btn{display:flex;align-items:center;gap:10px;padding:13px 18px;border:1px solid var(--border);border-radius:12px;font-family:'Outfit',sans-serif;font-size:14px;font-weight:500;color:var(--ink);transition:opacity .15s,transform .1s;cursor:pointer;background:transparent;}
.bl-link-btn:hover{opacity:.8;transform:translateY(-1px);}
.bl-link-btn.accent{background:var(--accent);border-color:var(--accent);color:#fff;}
.bl-link-btn svg{width:16px;height:16px;flex-shrink:0;}
.bl-link-label{flex:1;}
.bl-link-arrow{opacity:.5;font-size:12px;}
.bl-contact-row{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;}
.bl-contact-chip{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border:1px solid var(--border);border-radius:100px;font-size:12px;font-weight:500;color:var(--ink);}
.bl-contact-chip svg{width:13px;height:13px;stroke:var(--ink);}
.bl-vcard-section{margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--border);width:100%;text-align:center;}
.bl-vcard-title{font-size:13px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:1rem;}
.bl-vcard-qr-wrap{display:inline-block;background:rgba(255,255,255,0.1);border:1px solid var(--border);border-radius:12px;padding:12px;margin-bottom:1rem;}
.bl-vcard-qr-wrap img{display:block;border-radius:6px;width:140px;height:140px;}
.bl-add-contact-btn{display:inline-flex;align-items:center;gap:7px;padding:12px 24px;background:var(--accent);color:#fff;border-radius:10px;font-family:'Outfit',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:opacity .15s;}
.bl-add-contact-btn:hover{opacity:.85;}
.bl-add-contact-btn svg{width:16px;height:16px;stroke:#fff;}
.bl-safari-note{font-size:11px;color:var(--muted);margin-top:.5rem;}
.bl-mv-credit{margin-top:2.5rem;font-size:11px;color:var(--muted);text-align:center;}
.bl-mv-credit a{color:var(--accent);}

/* ══ LAYOUT 1 — Classic Stack ══ */
.bl-layout-1 .bl-inner{max-width:440px;width:100%;display:flex;flex-direction:column;align-items:center;gap:1rem;text-align:center;}
.bl-layout-1 .bl-name-wrap{display:flex;flex-direction:column;align-items:center;gap:.2rem;}
.bl-layout-1 .bl-links{max-width:400px;}

/* ══ LAYOUT 2 — Sidebar Profile ══ */
.bl-layout-2 .bl-inner{max-width:600px;width:100%;display:flex;gap:1.5rem;align-items:flex-start;}
.bl-layout-2 .bl-sidebar{display:flex;flex-direction:column;align-items:center;gap:.75rem;flex-shrink:0;width:90px;}
.bl-layout-2 .bl-sidebar .bl-socials{flex-direction:column;align-items:center;}
.bl-layout-2 .bl-main{flex:1;display:flex;flex-direction:column;gap:.85rem;}
.bl-layout-2 .bl-name{font-size:1.4rem;}
.bl-layout-2 .bl-vcard-section{border-top:none;padding-top:0;}

/* ══ LAYOUT 3 — Banner Header ══ */
.bl-layout-3{padding:0 0 4rem;}
.bl-layout-3 .bl-banner{width:100%;height:160px;background:var(--accent);position:relative;display:flex;align-items:flex-end;justify-content:center;padding-bottom:0;flex-shrink:0;}
.bl-layout-3 .bl-banner-avatar{position:absolute;bottom:-45px;left:50%;transform:translateX(-50%);width:90px;height:90px;}
.bl-layout-3 .bl-inner{max-width:440px;width:100%;display:flex;flex-direction:column;align-items:center;gap:1rem;text-align:center;padding:3.5rem 1rem 0;}
.bl-layout-3 .bl-links{max-width:400px;}
.bl-layout-3 .bl-avatar-img,.bl-layout-3 .bl-avatar-init{border:4px solid var(--bg);}

/* ══ LAYOUT 4 — Card Grid ══ */
.bl-layout-4 .bl-inner{max-width:560px;width:100%;display:flex;flex-direction:column;gap:1.25rem;}
.bl-layout-4 .bl-profile-row{display:flex;align-items:center;gap:1rem;}
.bl-layout-4 .bl-avatar-img,.bl-layout-4 .bl-avatar-init{width:72px;height:72px;font-size:26px;border-radius:14px;}
.bl-layout-4 .bl-name-block{flex:1;}
.bl-layout-4 .bl-name{font-size:1.3rem;}
.bl-layout-4 .bl-socials{justify-content:flex-start;}
.bl-layout-4 .bl-links{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.bl-layout-4 .bl-link-btn{flex-direction:column;align-items:flex-start;gap:5px;padding:14px;}
.bl-layout-4 .bl-link-btn .bl-link-arrow{display:none;}

@media(max-width:500px){
  .bl-layout-2 .bl-inner{flex-direction:column;align-items:center;}
  .bl-layout-2 .bl-sidebar{flex-direction:row;width:100%;justify-content:center;}
  .bl-layout-2 .bl-sidebar .bl-socials{flex-direction:row;}
  .bl-layout-4 .bl-links{grid-template-columns:1fr;}
}
</style>
</head>
<body class="bl-layout-<?php echo esc_attr( str_replace('layout-','',$layout) ); ?>">

<?php
// ── Avatar HTML helper
function bl_avatar( $avatar_url, $initials, $extra_class='' ) {
    if ($avatar_url) {
        return '<img class="bl-avatar-img ' . esc_attr($extra_class) . '" src="' . esc_url($avatar_url) . '" alt="' . esc_attr($initials) . '">';
    }
    return '<div class="bl-avatar-init ' . esc_attr($extra_class) . '">' . esc_html($initials) . '</div>';
}

// ── Link icon SVG helper
function bl_link_icon($icon) {
    $icons = [
        'link'      => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'phone'     => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'mail'      => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/>',
        'map'       => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'shop'      => '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'video'     => '<polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>',
        'doc'       => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'star'      => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'gift'      => '<polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>',
        'chat'      => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'portfolio' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
    ];
    $path = $icons[$icon] ?? $icons['link'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

// ── Contact chips
function bl_contact_chips($phone, $email, $website, $address) {
    $chips = '';
    if ($phone)   $chips .= '<a class="bl-contact-chip" href="tel:' . esc_attr($phone) . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>' . esc_html($phone) . '</a>';
    if ($email)   $chips .= '<a class="bl-contact-chip" href="mailto:' . esc_attr($email) . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>' . esc_html($email) . '</a>';
    if ($website) $chips .= '<a class="bl-contact-chip" href="' . esc_url($website) . '" target="_blank"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>' . esc_html(preg_replace('#^https?://#','',$website)) . '</a>';
    if ($address) $chips .= '<span class="bl-contact-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' . esc_html($address) . '</span>';
    return $chips ? '<div class="bl-contact-row">' . $chips . '</div>' : '';
}

// ── Social buttons
function bl_socials($socials) {
    if (empty($socials)) return '';
    $icons = [
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>',
        'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'linkedin'  => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
        'youtube'   => '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/>',
        'tiktok'    => '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>',
        'twitter'   => '<path d="M4 4l16 16M4 20L20 4"/>',
    ];
    $html = '<div class="bl-socials">';
    foreach ($socials as $s) {
        if (empty($s['url'])) continue;
        $net  = $s['network'] ?? 'link';
        $path = $icons[$net] ?? '<circle cx="12" cy="12" r="10"/>';
        $html .= '<a class="bl-social-btn" href="' . esc_url($s['url']) . '" target="_blank" rel="noopener" title="' . esc_attr(ucfirst($net)) . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg></a>';
    }
    $html .= '</div>';
    return $html;
}

// ── Links list
function bl_links($links) {
    if (empty($links)) return '';
    $html = '<div class="bl-links">';
    foreach ( array_slice($links,0,10) as $i => $link ) {
        if (empty($link['url'])) continue;
        $cls = $i === 0 ? 'bl-link-btn accent' : 'bl-link-btn';
        $html .= '<a class="' . $cls . '" href="' . esc_url($link['url']) . '" target="_blank" rel="noopener">';
        $html .= bl_link_icon( $link['icon'] ?? 'link' );
        $html .= '<span class="bl-link-label">' . esc_html($link['label']) . '</span>';
        $html .= '<span class="bl-link-arrow">↗</span>';
        $html .= '</a>';
    }
    $html .= '</div>';
    return $html;
}

// ── vCard section
function bl_vcard_section($qr_vcard_url, $vcard_download) {
    echo '<div class="bl-vcard-section">';
    echo '<div class="bl-vcard-title">Save my contact</div>';
    if ($qr_vcard_url) {
        echo '<div class="bl-vcard-qr-wrap"><img src="' . esc_url($qr_vcard_url) . '" width="140" height="140" alt="Scan to save contact"></div><br>';
    }
    echo '<a href="' . esc_url($vcard_download) . '" class="bl-add-contact-btn">';
    echo '<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
    echo 'Add to Contacts</a>';
    echo '<div class="bl-safari-note">iPhone users: tap the button above or scan the QR with your camera.</div>';
    echo '</div>';
}
?>

<?php if ( $layout === 'layout-3' ) : ?>
<!-- ══ LAYOUT 3: BANNER ══ -->
<div class="bl-page bl-layout-3">
  <div class="bl-banner">
    <div class="bl-banner-avatar"><?php echo bl_avatar($avatar_url,$initials); ?></div>
  </div>
  <div class="bl-inner">
    <div>
      <div class="bl-name"><?php echo esc_html($full_name); ?></div>
      <?php if($job_title) echo '<div class="bl-title">' . esc_html($job_title) . '</div>'; ?>
      <?php if($company)   echo '<div class="bl-company">' . esc_html($company) . '</div>'; ?>
    </div>
    <?php if($bio) echo '<p class="bl-bio">' . esc_html($bio) . '</p>'; ?>
    <?php echo bl_socials($socials); ?>
    <?php echo bl_contact_chips($phone,$email,$website,$address); ?>
    <?php echo bl_links($links); ?>
    <?php bl_vcard_section($qr_vcard_url, $vcard_download); ?>
    <div class="bl-mv-credit">Created by <a href="https://www.mediavines.com" target="_blank">Media Vines Corp</a></div>
  </div>
</div>

<?php elseif ( $layout === 'layout-2' ) : ?>
<!-- ══ LAYOUT 2: SIDEBAR ══ -->
<div class="bl-page bl-layout-2" style="justify-content:flex-start;padding-top:3rem;">
  <div class="bl-inner">
    <div class="bl-sidebar">
      <?php echo bl_avatar($avatar_url,$initials); ?>
      <?php echo bl_socials($socials); ?>
    </div>
    <div class="bl-main">
      <div>
        <div class="bl-name"><?php echo esc_html($full_name); ?></div>
        <?php if($job_title) echo '<div class="bl-title">' . esc_html($job_title) . '</div>'; ?>
        <?php if($company)   echo '<div class="bl-company">' . esc_html($company) . '</div>'; ?>
      </div>
      <?php if($bio) echo '<p class="bl-bio">' . esc_html($bio) . '</p>'; ?>
      <?php echo bl_contact_chips($phone,$email,$website,$address); ?>
      <?php echo bl_links($links); ?>
      <?php bl_vcard_section($qr_vcard_url, $vcard_download); ?>
    </div>
  </div>
  <div class="bl-mv-credit">Created by <a href="https://www.mediavines.com" target="_blank">Media Vines Corp</a></div>
</div>

<?php elseif ( $layout === 'layout-4' ) : ?>
<!-- ══ LAYOUT 4: CARD GRID ══ -->
<div class="bl-page bl-layout-4" style="justify-content:flex-start;padding-top:3rem;">
  <div class="bl-inner">
    <div class="bl-profile-row">
      <?php echo bl_avatar($avatar_url,$initials); ?>
      <div class="bl-name-block">
        <div class="bl-name"><?php echo esc_html($full_name); ?></div>
        <?php if($job_title) echo '<div class="bl-title">' . esc_html($job_title) . '</div>'; ?>
        <?php if($company)   echo '<div class="bl-company">' . esc_html($company) . '</div>'; ?>
        <?php echo bl_socials($socials); ?>
      </div>
    </div>
    <?php if($bio) echo '<p class="bl-bio">' . esc_html($bio) . '</p>'; ?>
    <?php echo bl_contact_chips($phone,$email,$website,$address); ?>
    <?php echo bl_links($links); ?>
    <?php bl_vcard_section($qr_vcard_url, $vcard_download); ?>
    <div class="bl-mv-credit">Created by <a href="https://www.mediavines.com" target="_blank">Media Vines Corp</a></div>
  </div>
</div>

<?php else : ?>
<!-- ══ LAYOUT 1: CLASSIC STACK (default) ══ -->
<div class="bl-page bl-layout-1">
  <div class="bl-inner">
    <?php echo bl_avatar($avatar_url,$initials); ?>
    <div class="bl-name-wrap">
      <div class="bl-name"><?php echo esc_html($full_name); ?></div>
      <?php if($job_title) echo '<div class="bl-title">' . esc_html($job_title) . '</div>'; ?>
      <?php if($company)   echo '<div class="bl-company">' . esc_html($company) . '</div>'; ?>
    </div>
    <?php if($bio) echo '<p class="bl-bio">' . esc_html($bio) . '</p>'; ?>
    <?php echo bl_socials($socials); ?>
    <?php echo bl_contact_chips($phone,$email,$website,$address); ?>
    <?php echo bl_links($links); ?>
    <?php bl_vcard_section($qr_vcard_url, $vcard_download); ?>
    <div class="bl-mv-credit">Created by <a href="https://www.mediavines.com" target="_blank">Media Vines Corp</a></div>
  </div>
</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
