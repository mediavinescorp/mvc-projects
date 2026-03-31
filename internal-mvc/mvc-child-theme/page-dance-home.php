<?php
/**
 * Template Name: Dance Home
 */

$dancers = get_posts([
    'post_type'      => 'dancer',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
]);

$hero_bg    = get_field('hero_background_image') ?: '';
$intro_text = get_field('intro_text') ?: 'We are a collective of artists who live and breathe movement. Each dancer brings a unique story to the stage.';
$company    = get_bloginfo('name');

get_header(); ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap');

:root {
    --ink:     #0a0a0f;
    --cream:   #f5f0e8;
    --gold:    #c9a84c;
    --gold-lt: #e8d49a;
    --muted:   #6b6456;
    --ff-display: 'Cormorant Garamond', Georgia, serif;
    --ff-body:    'DM Sans', system-ui, sans-serif;
    --transition: 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

/* ── HERO ── */
.dh-hero {
    position: relative;
    min-height: 92vh;
    display: flex;
    align-items: flex-end;
    padding: clamp(3rem, 8vw, 7rem) clamp(1.5rem, 6vw, 5rem);
    overflow: hidden;
    background-color: var(--ink);
}
.dh-hero__bg {
    position: absolute;
    inset: 0;
    background-image: <?php echo $hero_bg ? 'url(' . esc_url( $hero_bg ) . ')' : 'linear-gradient(135deg, #0a0a0f 0%, #1a1420 50%, #0d1018 100%)'; ?>;
    background-size: cover;
    background-position: center top;
    opacity: 0.55;
}
.dh-hero__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(10,10,15,0.92) 0%, rgba(10,10,15,0.1) 60%);
}
.dh-hero__lines {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}
.dh-hero__lines::before,
.dh-hero__lines::after {
    content: '';
    position: absolute;
    top: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, rgba(201,168,76,0.4), transparent);
}
.dh-hero__lines::before { left: clamp(1.5rem, 6vw, 5rem); }
.dh-hero__lines::after  { right: clamp(1.5rem, 6vw, 5rem); }

.dh-hero__content {
    position: relative;
    z-index: 2;
    max-width: 800px;
}
.dh-hero__eyebrow {
    display: inline-block;
    font-family: var(--ff-body);
    font-size: 0.7rem;
    font-weight: 500;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 1.25rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gold);
}
.dh-hero__title {
    font-family: var(--ff-display);
    font-weight: 300;
    font-size: clamp(3.5rem, 9vw, 8rem);
    line-height: 0.95;
    color: var(--cream);
    margin-bottom: 2rem;
}
.dh-hero__title em {
    font-style: italic;
    color: var(--gold-lt);
}
.dh-hero__scroll {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: rgba(245,240,232,0.5);
    font-size: 0.7rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    animation: pulse-scroll 2.5s ease-in-out infinite;
}
.dh-hero__scroll::after {
    content: '';
    display: block;
    width: 40px;
    height: 1px;
    background: currentColor;
}
@keyframes pulse-scroll {
    0%, 100% { opacity: 0.5; transform: translateY(0); }
    50%       { opacity: 0.9; transform: translateY(4px); }
}

/* ── INTRO ── */
.dh-intro {
    padding: clamp(5rem, 10vw, 9rem) clamp(1.5rem, 6vw, 5rem);
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 4rem;
    align-items: start;
    max-width: 1200px;
    margin: 0 auto;
}
@media (max-width: 700px) {
    .dh-intro { grid-template-columns: 1fr; gap: 2rem; }
}
.dh-intro__label {
    font-size: 0.68rem;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 500;
    padding-top: 0.3rem;
}
.dh-intro__text {
    font-family: var(--ff-display);
    font-size: clamp(1.4rem, 2.5vw, 2rem);
    font-weight: 300;
    line-height: 1.55;
    color: var(--ink);
}

/* ── DIVIDER ── */
.dh-divider {
    width: calc(100% - clamp(3rem, 12vw, 10rem));
    margin: 0 auto;
    height: 1px;
    background: linear-gradient(to right, transparent, var(--gold), transparent);
    opacity: 0.4;
}

/* ── GRID HEADER ── */
.dh-grid-header {
    padding: clamp(4rem, 7vw, 6rem) clamp(1.5rem, 6vw, 5rem) 2.5rem;
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.dh-grid-header__title {
    font-family: var(--ff-display);
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 300;
}
.dh-grid-header__count {
    font-size: 0.7rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--muted);
}

/* ── DANCER GRID ── */
.dh-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 2px;
    padding: 0 clamp(1.5rem, 6vw, 5rem) clamp(5rem, 10vw, 9rem);
    max-width: 1200px;
    margin: 0 auto;
}

/* ── DANCER CARD ── */
.dh-card {
    position: relative;
    aspect-ratio: 3 / 4;
    overflow: hidden;
    cursor: pointer;
    background: var(--ink);
    opacity: 0;
    animation: fadeUp 0.6s ease forwards;
    text-decoration: none;
}
.dh-card__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94), filter 0.7s ease;
    filter: grayscale(20%);
}
.dh-card:hover .dh-card__img {
    transform: scale(1.07);
    filter: grayscale(0%);
}
.dh-card__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(10,10,15,0.88) 0%, transparent 55%);
    transition: opacity var(--transition);
}
.dh-card:hover .dh-card__overlay { opacity: 0.75; }
.dh-card__body {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    padding: 1.75rem 1.5rem;
}
.dh-card__name {
    font-family: var(--ff-display);
    font-size: 1.55rem;
    font-weight: 400;
    color: var(--cream);
    line-height: 1.15;
    margin-bottom: 0.35rem;
}
.dh-card__style {
    font-size: 0.68rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--gold);
    opacity: 0;
    transform: translateY(6px);
    transition: opacity var(--transition), transform var(--transition);
}
.dh-card:hover .dh-card__style {
    opacity: 1;
    transform: translateY(0);
}
.dh-card__arrow {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    width: 36px;
    height: 36px;
    border: 1px solid rgba(201,168,76,0.5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: translateY(-6px);
    transition: opacity var(--transition), transform var(--transition), background var(--transition);
}
.dh-card:hover .dh-card__arrow {
    opacity: 1;
    transform: translateY(0);
    background: var(--gold);
    border-color: var(--gold);
}
.dh-card__arrow svg {
    width: 14px; height: 14px;
    fill: none; stroke: var(--cream);
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
}
.dh-card--no-photo {
    background: linear-gradient(135deg, #1a1420, #0d1018);
    display: flex;
    align-items: center;
    justify-content: center;
}
.dh-card__placeholder {
    font-family: var(--ff-display);
    font-size: 5rem;
    font-weight: 300;
    color: rgba(245,240,232,0.15);
    pointer-events: none;
    user-select: none;
}

/* ── ANIMATIONS ── */
.dh-fade {
    opacity: 0;
    transform: translateY(24px);
    animation: fadeUp 0.7s var(--transition) forwards;
}

<?php for ( $i = 0; $i < count( $dancers ); $i++ ) : ?>
.dh-card:nth-child(<?php echo $i + 1; ?>) { animation-delay: <?php echo $i * 0.07; ?>s; }
<?php endfor; ?>

@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- ══ HERO ══════════════════════════════════════════════════════════════════ -->
<section class="dh-hero">
    <div class="dh-hero__bg"></div>
    <div class="dh-hero__overlay"></div>
    <div class="dh-hero__lines"></div>
    <div class="dh-hero__content dh-fade">
        <span class="dh-hero__eyebrow"><?php echo esc_html( $company ); ?></span>
        <h1 class="dh-hero__title">
            Meet<br><em>Our Dancers</em>
        </h1>
        <div class="dh-hero__scroll">Scroll</div>
    </div>
</section>

<!-- ══ INTRO ═════════════════════════════════════════════════════════════════ -->
<section class="dh-intro">
    <div class="dh-intro__label">About Us</div>
    <p class="dh-intro__text"><?php echo esc_html( $intro_text ); ?></p>
</section>

<div class="dh-divider"></div>

<!-- ══ GRID HEADER ════════════════════════════════════════════════════════════ -->
<div class="dh-grid-header">
    <h2 class="dh-grid-header__title">The Company</h2>
    <span class="dh-grid-header__count"><?php echo count( $dancers ); ?> Dancers</span>
</div>

<!-- ══ DANCER GRID ════════════════════════════════════════════════════════════ -->
<div class="dh-grid">
<?php foreach ( $dancers as $dancer ) :
    $photo   = get_field( 'dancer_photo', $dancer->ID );
    $styles  = get_field( 'dancer_styles', $dancer->ID );
    $url     = get_permalink( $dancer->ID );
    $name    = get_the_title( $dancer->ID );
    $initial = mb_strtoupper( mb_substr( $name, 0, 1 ) );
    $img_url = $photo ? esc_url( $photo['url'] ) : '';
    $img_alt = $photo ? esc_attr( $photo['alt'] ?: $name ) : '';
?>
    <a href="<?php echo esc_url( $url ); ?>" class="dh-card<?php echo $img_url ? '' : ' dh-card--no-photo'; ?>">

        <?php if ( $img_url ) : ?>
            <img class="dh-card__img" src="<?php echo $img_url; ?>" alt="<?php echo $img_alt; ?>" loading="lazy">
        <?php else : ?>
            <span class="dh-card__placeholder"><?php echo esc_html( $initial ); ?></span>
        <?php endif; ?>

        <div class="dh-card__overlay"></div>

        <div class="dh-card__arrow">
            <svg viewBox="0 0 24 24"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
        </div>

        <div class="dh-card__body">
            <div class="dh-card__name"><?php echo esc_html( $name ); ?></div>
            <?php if ( $styles ) : ?>
                <div class="dh-card__style"><?php echo esc_html( $styles ); ?></div>
            <?php endif; ?>
        </div>
    </a>
<?php endforeach; ?>
</div>

<?php get_footer(); ?>