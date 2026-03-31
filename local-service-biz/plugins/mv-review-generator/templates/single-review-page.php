<?php
/**
 * Template for single review_page CPT.
 * Loaded automatically by the plugin for all review_page posts.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Pull all meta
$post_id      = get_the_ID();
$biz_name     = get_the_title();
$review_link  = get_post_meta( $post_id, 'review_link',  true );
$tagline      = get_post_meta( $post_id, 'tagline',      true );
$logo_url     = get_post_meta( $post_id, 'logo_url',     true );
$qr_image_url = get_post_meta( $post_id, 'qr_image_url', true );
$industry     = get_post_meta( $post_id, 'industry',     true );

// Fallback tagline
if ( empty( $tagline ) ) {
    $tagline = 'A quick Google review helps our business grow and lets others know they can trust us.';
}

// Build initials from business name
$words    = array_filter( explode( ' ', $biz_name ) );
$initials = '';
foreach ( array_slice( $words, 0, 2 ) as $word ) {
    $initials .= strtoupper( mb_substr( $word, 0, 1 ) );
}

// Page title
$page_title = sprintf( 'Leave Us a Review – %s', $biz_name );

// Share URLs
$encoded_link = rawurlencode( $review_link );
$email_subject = rawurlencode( "We'd love your review – {$biz_name}" );
$email_body    = rawurlencode( "Hi there,\n\nThank you so much for choosing {$biz_name}!\n\nWe'd love if you could leave us a quick Google review — it really helps us grow.\n\nLeave your review here:\n{$review_link}\n\nThank you!\n{$biz_name}" );
$sms_body      = rawurlencode( "Hi! Thank you for choosing {$biz_name}. We'd love a quick Google review: {$review_link}" );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( $page_title ); ?></title>
<meta name="description" content="Leave a Google review for <?php echo esc_attr( $biz_name ); ?>">
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,600;1,400&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
<?php wp_head(); ?>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Outfit', sans-serif;
    background: #f9f8f5;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    color: #141414;
  }
  .rv-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 3rem 2.5rem;
    max-width: 480px;
    width: 100%;
    text-align: center;
    border: 1px solid #e4e4e0;
  }
  /* Logo / Initials */
  .rv-logo-img {
    width: 80px; height: 56px;
    object-fit: contain;
    border-radius: 10px;
    display: block;
    margin: 0 auto 0.6rem;
  }
  .rv-initials {
    width: 56px; height: 56px;
    background: #141414;
    border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 0.6rem;
    font-family: 'Fraunces', serif;
    font-size: 22px; color: white; font-weight: 600;
  }
  .rv-biz-name {
    font-size: 11px; font-weight: 600;
    letter-spacing: .1em; text-transform: uppercase;
    color: #6b7280; margin-bottom: 1.2rem;
  }
  /* Google badge */
  .rv-gbadge {
    display: inline-flex; align-items: center; gap: 7px;
    background: #f9f8f5; border-radius: 100px;
    padding: 5px 14px; margin-bottom: 1rem;
  }
  .rv-gbadge span { font-size: 12px; font-weight: 500; color: #555; }
  /* Stars */
  .rv-stars { display: flex; justify-content: center; gap: 5px; margin-bottom: .9rem; }
  .rv-stars svg { width: 19px; height: 19px; fill: #FBBC04; }
  /* Headline */
  .rv-headline {
    font-family: 'Fraunces', serif;
    font-size: 1.7rem; font-weight: 600;
    color: #141414; line-height: 1.2; margin-bottom: .5rem;
  }
  .rv-sub {
    font-size: 14px; color: #6b7280;
    line-height: 1.6; margin-bottom: 1.75rem;
  }
  /* QR */
  .rv-qr-wrap {
    background: #f9f8f5; border-radius: 14px;
    padding: 1.25rem; display: inline-block;
    margin-bottom: 1.75rem;
  }
  .rv-qr-wrap img { display: block; border-radius: 6px; }
  .rv-qr-label { font-size: 11px; color: #aaa; margin-top: 8px; letter-spacing: .04em; }
  /* Divider */
  .rv-divider { display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem; }
  .rv-divider::before, .rv-divider::after { content: ''; flex: 1; height: 1px; background: #e4e4e0; }
  .rv-divider span { font-size: 11px; color: #aaa; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; }
  /* Steps */
  .rv-steps { text-align: left; display: flex; flex-direction: column; gap: .9rem; margin-bottom: 1.75rem; }
  .rv-step { display: flex; align-items: flex-start; gap: 12px; }
  .rv-step-num {
    width: 24px; height: 24px; border-radius: 50%;
    background: #141414; color: #fff;
    font-size: 11px; font-weight: 600;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 1px;
  }
  .rv-step-text { font-size: 13px; color: #555; line-height: 1.55; }
  .rv-step-text strong { color: #141414; font-weight: 500; }
  /* CTA button */
  .rv-cta {
    display: block; width: 100%; padding: 13px;
    background: #141414; color: #fff;
    font-family: 'Outfit', sans-serif;
    font-size: 14px; font-weight: 600;
    border: none; border-radius: 11px;
    text-decoration: none; cursor: pointer;
    transition: background .2s; margin-bottom: 1rem;
  }
  .rv-cta:hover { background: #333; color: #fff; }
  /* Share buttons */
  .rv-share-row { display: flex; gap: 8px; margin-bottom: 1.25rem; }
  .rv-share-btn {
    flex: 1; padding: 10px 8px;
    border: 1px solid #e4e4e0; border-radius: 10px;
    background: #fff; color: #141414;
    font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 500;
    cursor: pointer; text-decoration: none; text-align: center;
    transition: border-color .2s, color .2s; display: flex;
    align-items: center; justify-content: center; gap: 6px;
  }
  .rv-share-btn:hover { border-color: #1a6b4a; color: #1a6b4a; }
  .rv-share-btn svg { width: 14px; height: 14px; flex-shrink: 0; }
  /* Footer */
  .rv-footer { font-size: 11px; color: #ccc; margin-bottom: .5rem; }
  .rv-mv-credit { font-size: 11px; color: #bbb; }
  .rv-mv-credit a { color: #1a6b4a; text-decoration: none; }
  @media(max-width:520px) {
    .rv-card { padding: 2rem 1.25rem; }
    .rv-share-row { flex-direction: column; }
  }
</style>
</head>
<body>

<article class="rv-card">

  <!-- Logo or initials -->
  <?php if ( $logo_url ) : ?>
    <img class="rv-logo-img" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $biz_name ); ?> logo">
  <?php else : ?>
    <div class="rv-initials"><?php echo esc_html( $initials ); ?></div>
  <?php endif; ?>

  <div class="rv-biz-name"><?php echo esc_html( $biz_name ); ?></div>

  <!-- Google badge -->
  <div class="rv-gbadge">
    <svg width="14" height="14" viewBox="0 0 24 24">
      <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
      <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
      <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
      <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
    </svg>
    <span>Google Review</span>
  </div>

  <!-- Stars -->
  <div class="rv-stars">
    <?php for ( $i = 0; $i < 5; $i++ ) : ?>
      <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    <?php endfor; ?>
  </div>

  <h1 class="rv-headline">Happy with our work?</h1>
  <p class="rv-sub"><?php echo esc_html( $tagline ); ?></p>

  <!-- QR Code -->
  <div class="rv-qr-wrap">
    <?php if ( $qr_image_url ) : ?>
      <img src="<?php echo esc_url( $qr_image_url ); ?>" width="200" height="200" alt="QR code to leave a Google review for <?php echo esc_attr( $biz_name ); ?>">
    <?php else : ?>
      <!-- Fallback: load QR inline via API if saved image not available -->
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&ecc=H&data=<?php echo $encoded_link; ?>" width="200" height="200" alt="QR code">
    <?php endif; ?>
    <div class="rv-qr-label">Scan with your phone camera</div>
  </div>

  <!-- Step divider -->
  <div class="rv-divider"><span>Step-by-step for mobile</span></div>

  <!-- Steps -->
  <div class="rv-steps">
    <div class="rv-step">
      <div class="rv-step-num">1</div>
      <div class="rv-step-text"><strong>Open your camera app</strong> — no extra app needed.</div>
    </div>
    <div class="rv-step">
      <div class="rv-step-num">2</div>
      <div class="rv-step-text"><strong>Point it at the QR code</strong> above and hold steady for a moment.</div>
    </div>
    <div class="rv-step">
      <div class="rv-step-num">3</div>
      <div class="rv-step-text"><strong>Tap the banner</strong> that pops up at the top of your screen.</div>
    </div>
    <div class="rv-step">
      <div class="rv-step-num">4</div>
      <div class="rv-step-text"><strong>Choose your star rating</strong> and share a few words about your visit.</div>
    </div>
    <div class="rv-step">
      <div class="rv-step-num">5</div>
      <div class="rv-step-text"><strong>Tap "Post"</strong> — done! We truly appreciate it.</div>
    </div>
  </div>

  <!-- CTA button -->
  <a href="<?php echo esc_url( $review_link ); ?>" class="rv-cta" target="_blank" rel="noopener">
    Tap here to leave a review ↗
  </a>

  <!-- Share row -->
  <div class="rv-share-row">
    <a class="rv-share-btn" href="mailto:?subject=<?php echo $email_subject; ?>&body=<?php echo $email_body; ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
      Share via Email
    </a>
    <a class="rv-share-btn" href="sms:?body=<?php echo $sms_body; ?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Share via Text
    </a>
  </div>

  <div class="rv-footer"><?php echo esc_html( $biz_name ); ?> &middot; Thank you for choosing us</div>
  <div class="rv-mv-credit">Created by <a href="https://www.mediavines.com" target="_blank">Media Vines Corp</a></div>

</article>

<?php wp_footer(); ?>
</body>
</html>
