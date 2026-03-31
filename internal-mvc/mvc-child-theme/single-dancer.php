<?php
/**
 * Single Dancer Profile Template
 * Save as: single-dancer.php  (WordPress auto-loads this for dancer CPT)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $post;

// ACF fields
$photo   = get_field( 'dancer_photo' );
$tagline = get_field( 'dancer_tagline' );
$bio     = get_field( 'dancer_bio' );
$styles  = get_field( 'dancer_styles' );
$years   = get_field( 'dancer_years' );
$socials = get_field( 'dancer_socials' );

$name    = get_the_title();
$img_url = $photo ? esc_url( $photo['url'] ) : '';
$img_alt = $photo ? esc_attr( $photo['alt'] ?: $name ) : '';
$back    = home_url( '/rosette/' ); // swap to your dance home page URL if different
$company = get_bloginfo( 'name' );

// Social icon SVGs
$icons = [
    'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="0.8" fill="currentColor" stroke="none"/></svg>',
    'tiktok'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.93a8.16 8.16 0 004.77 1.52V7.01a4.85 4.85 0 01-1-.32z"/></svg>',
    'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 7s-.3-1.9-1.1-2.7c-1.1-1.1-2.3-1.1-2.8-1.2C16.2 3 12 3 12 3s-4.2 0-7.1.1C4.4 3.2 3.2 3.2 2.1 4.3 1.3 5.1 1 7 1 7S.7 9.2.7 11.5v2.1c0 2.2.3 4.5.3 4.5s.3 1.9 1.1 2.7c1.1 1.1 2.5 1 3.1 1.1C7.2 22 12 22 12 22s4.2 0 7.1-.2c.5-.1 1.7-.1 2.8-1.2.8-.8 1.1-2.7 1.1-2.7s.3-2.2.3-4.5v-2.1C23.3 9.2 23 7 23 7zM9.7 15.5V8.4l7.6 3.6-7.6 3.5z"/></svg>',
    'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>',
    'twitter'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
    'website'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg>',
];

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $name ); ?> | <?php echo esc_html( $company ); ?></title>
<?php wp_head(); ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap');

:root {
    --ink:     #0a0a0f;
    --cream:   #f5f0e8;
    --gold:    #c9a84c;
    --gold-lt: #e8d49a;
    --muted:   #6b6456;
    --line:    rgba(10,10,15,0.12);
    --ff-display: 'Cormorant Garamond', Georgia, serif;
    --ff-body:    'DM Sans', system-ui, sans-serif;
    --t: 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
    background: var(--cream);
    color: var(--ink);
    font-family: var(--ff-body);
    font-size: 16px;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}
img { display: block; max-width: 100%; }
a { text-decoration: none; color: inherit; }

/* ── NAV BAR ── */
.dp-nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem clamp(1.5rem, 5vw, 4rem);
    background: rgba(245,240,232,0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--line);
}
.dp-nav__back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.72rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--muted);
    transition: color var(--t), gap var(--t);
}
.dp-nav__back:hover { color: var(--ink); gap: 0.75rem; }
.dp-nav__back svg {
    width: 14px; height: 14px;
    fill: none; stroke: currentColor;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
    transform: rotate(180deg);
}
.dp-nav__brand {
    font-family: var(--ff-display);
    font-size: 1rem;
    font-weight: 400;
    letter-spacing: 0.05em;
}

/* ── HERO SPLIT ── */
.dp-hero {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 100vh;
    padding-top: 60px; /* nav height */
}
@media (max-width: 780px) {
    .dp-hero { grid-template-columns: 1fr; min-height: auto; }
}

.dp-hero__photo {
    position: relative;
    overflow: hidden;
    background: var(--ink);
    min-height: 60vw;
}
@media (max-width: 780px) { .dp-hero__photo { min-height: 80vw; } }

.dp-hero__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
    animation: scaleIn 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
}
@keyframes scaleIn {
    from { transform: scale(1.08); opacity: 0; }
    to   { transform: scale(1);    opacity: 1; }
}
.dp-hero__photo-placeholder {
    width: 100%;
    height: 100%;
    min-height: 60vw;
    background: linear-gradient(135deg, #1a1420, #0d1018);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--ff-display);
    font-size: 12rem;
    font-weight: 300;
    color: rgba(245,240,232,0.08);
    user-select: none;
}

.dp-hero__info {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: clamp(3rem, 8vw, 6rem) clamp(2rem, 6vw, 5rem);
    background: var(--cream);
}
.dp-hero__eyebrow {
    font-size: 0.68rem;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 500;
    margin-bottom: 1.25rem;
    opacity: 0;
    animation: fadeUp 0.7s 0.4s ease forwards;
}
.dp-hero__name {
    font-family: var(--ff-display);
    font-size: clamp(3rem, 6vw, 5.5rem);
    font-weight: 300;
    line-height: 1;
    margin-bottom: 1rem;
    opacity: 0;
    animation: fadeUp 0.7s 0.55s ease forwards;
}
.dp-hero__tagline {
    font-family: var(--ff-display);
    font-style: italic;
    font-size: clamp(1.1rem, 2vw, 1.4rem);
    font-weight: 300;
    color: var(--muted);
    margin-bottom: 2.5rem;
    opacity: 0;
    animation: fadeUp 0.7s 0.7s ease forwards;
}
.dp-hero__meta {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    opacity: 0;
    animation: fadeUp 0.7s 0.85s ease forwards;
}
.dp-hero__meta-item {
    display: flex;
    align-items: baseline;
    gap: 1rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid var(--line);
}
.dp-hero__meta-label {
    font-size: 0.65rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--muted);
    min-width: 5rem;
    flex-shrink: 0;
}
.dp-hero__meta-value {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--ink);
}

/* ── BIO SECTION ── */
.dp-bio {
    max-width: 860px;
    margin: 0 auto;
    padding: clamp(5rem, 10vw, 9rem) clamp(1.5rem, 6vw, 5rem);
}
.dp-bio__label {
    font-size: 0.68rem;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 500;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(201,168,76,0.3);
    display: inline-block;
}
.dp-bio__text {
    font-family: var(--ff-display);
    font-size: clamp(1.2rem, 2vw, 1.5rem);
    font-weight: 300;
    line-height: 1.7;
    color: var(--ink);
}

/* ── SOCIAL ── */
.dp-social {
    padding: 0 clamp(1.5rem, 6vw, 5rem) clamp(5rem, 10vw, 9rem);
    max-width: 860px;
    margin: 0 auto;
}
.dp-social__label {
    font-size: 0.68rem;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 1.25rem;
}
.dp-social__links {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.dp-social__link {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.65rem 1.25rem;
    border: 1px solid var(--line);
    border-radius: 2rem;
    font-size: 0.78rem;
    letter-spacing: 0.1em;
    text-transform: capitalize;
    color: var(--ink);
    transition: background var(--t), border-color var(--t), color var(--t);
}
.dp-social__link:hover {
    background: var(--ink);
    border-color: var(--ink);
    color: var(--cream);
}
.dp-social__link svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    transition: color var(--t);
}

/* ── FOOTER ── */
.dp-footer {
    background: var(--ink);
    color: rgba(245,240,232,0.3);
    text-align: center;
    padding: 2rem;
    font-size: 0.72rem;
    letter-spacing: 0.1em;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>

<!-- ══ NAV ════════════════════════════════════════════════════════════════════ -->
<nav class="dp-nav">
    <a href="<?php echo esc_url( $back ); ?>" class="dp-nav__back">
        <svg viewBox="0 0 24 24"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
        All Dancers
    </a>
    <span class="dp-nav__brand"><?php echo esc_html( $company ); ?></span>
</nav>

<!-- ══ HERO SPLIT ═════════════════════════════════════════════════════════════ -->
<section class="dp-hero">
    <div class="dp-hero__photo">
        <?php if ( $img_url ) : ?>
            <img class="dp-hero__img" src="<?php echo $img_url; ?>" alt="<?php echo $img_alt; ?>">
        <?php else : ?>
            <div class="dp-hero__photo-placeholder"><?php echo esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ); ?></div>
        <?php endif; ?>
    </div>

    <div class="dp-hero__info">
        <div class="dp-hero__eyebrow">Dancer Profile</div>
        <h1 class="dp-hero__name"><?php echo esc_html( $name ); ?></h1>

        <?php if ( $tagline ) : ?>
            <p class="dp-hero__tagline"><?php echo esc_html( $tagline ); ?></p>
        <?php endif; ?>

        <div class="dp-hero__meta">
            <?php if ( $styles ) : ?>
            <div class="dp-hero__meta-item">
                <span class="dp-hero__meta-label">Style</span>
                <span class="dp-hero__meta-value"><?php echo esc_html( $styles ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $years ) : ?>
            <div class="dp-hero__meta-item">
                <span class="dp-hero__meta-label">Experience</span>
                <span class="dp-hero__meta-value"><?php echo esc_html( $years ); ?> years</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ══ BIO ════════════════════════════════════════════════════════════════════ -->
<?php if ( $bio ) : ?>
<section class="dp-bio">
    <span class="dp-bio__label">Biography</span>
    <div class="dp-bio__text"><?php echo wp_kses_post( nl2br( $bio ) ); ?></div>
</section>
<?php endif; ?>

<!-- ══ SOCIAL LINKS ══════════════════════════════════════════════════════════ -->
<?php if ( $socials ) : ?>
<div class="dp-social">
    <div class="dp-social__label">Follow</div>
    <div class="dp-social__links">
        <?php foreach ( $socials as $s ) :
            $platform = $s['platform'] ?? '';
            $url      = $s['url'] ?? '';
            if ( ! $url ) continue;
            $icon = $icons[ $platform ] ?? $icons['website'];
        ?>
        <a href="<?php echo esc_url( $url ); ?>" class="dp-social__link" target="_blank" rel="noopener noreferrer">
            <?php echo $icon; ?>
            <?php echo esc_html( $platform === 'twitter' ? 'X / Twitter' : ucfirst( $platform ) ); ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ══ FOOTER ════════════════════════════════════════════════════════════════ -->
<footer class="dp-footer">
    &copy; <?php echo date('Y'); ?> <?php echo esc_html( $company ); ?>
</footer>

<?php wp_footer(); ?>
</body>
</html>
