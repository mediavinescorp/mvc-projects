<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── AJAX handler (logged-in and non-logged-in users) ── */
add_action( 'wp_ajax_mvrg_submit',        'mvrg_handle_submission' );
add_action( 'wp_ajax_nopriv_mvrg_submit', 'mvrg_handle_submission' );

function mvrg_handle_submission() {

    /* 1. Verify nonce */
    if ( ! check_ajax_referer( 'mvrg_submit', 'nonce', false ) ) {
        wp_send_json_error([ 'message' => 'Security check failed. Please refresh the page and try again.' ]);
    }

    /* 2. Sanitize & validate required fields */
    $biz_name    = sanitize_text_field( $_POST['biz_name']    ?? '' );
    $review_link = esc_url_raw( trim( $_POST['review_link']  ?? '' ) );
    $tagline     = sanitize_textarea_field( $_POST['tagline'] ?? '' );
    $industry    = sanitize_text_field( $_POST['industry']    ?? '' );

    $errors = [];
    if ( empty( $biz_name ) ) {
        $errors[] = 'Business name is required.';
    }
    if ( empty( $review_link ) || ! filter_var( $review_link, FILTER_VALIDATE_URL ) ) {
        $errors[] = 'A valid Google Review URL is required.';
    }
    if ( ! empty( $errors ) ) {
        wp_send_json_error([ 'message' => implode( ' ', $errors ) ]);
    }

    /* 3. Build unique slug from business name */
    $base_slug = sanitize_title( $biz_name );
    $slug      = mvrg_unique_slug( $base_slug );

    /* 4. Create the CPT post */
    $post_id = wp_insert_post([
        'post_title'  => $biz_name,
        'post_name'   => $slug,
        'post_type'   => 'review_page',
        'post_status' => 'publish',
        'meta_input'  => [
            'review_link'  => $review_link,
            'tagline'      => $tagline,
            'industry'     => $industry,
            'submitted_ip' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
            'submitted_at' => current_time( 'mysql' ),
        ],
    ], true );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error([ 'message' => 'Could not create review page: ' . $post_id->get_error_message() ]);
    }

    /* 5. Handle logo upload */
    $logo_url = '';
    $logo_id  = 0;
    if ( ! empty( $_FILES['logo'] ) && $_FILES['logo']['error'] === UPLOAD_ERR_OK ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Validate file type
        $allowed_types = [ 'image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml' ];
        $file_type     = mime_content_type( $_FILES['logo']['tmp_name'] );
        if ( ! in_array( $file_type, $allowed_types, true ) ) {
            // Non-fatal — just skip logo
        } elseif ( $_FILES['logo']['size'] > 5 * 1024 * 1024 ) {
            // File too large — skip logo
        } else {
            $logo_id = media_handle_upload( 'logo', $post_id );
            if ( ! is_wp_error( $logo_id ) ) {
                $logo_url = wp_get_attachment_url( $logo_id );
                update_post_meta( $post_id, 'logo_url', $logo_url );
                update_post_meta( $post_id, 'logo_id',  $logo_id );
                set_post_thumbnail( $post_id, $logo_id );
            }
        }
    }

    /* 6. Generate & save QR code */
    $qr_result = mvrg_generate_qr( $review_link, $post_id, $slug );
    if ( ! is_wp_error( $qr_result ) ) {
        update_post_meta( $post_id, 'qr_image_url',  $qr_result['url'] );
        update_post_meta( $post_id, 'qr_image_path', $qr_result['path'] );
    }
    // If QR generation fails, the template falls back gracefully

    /* 7. Return the new page URL */
  $page_url = get_permalink( $post_id );


    wp_send_json_success([
        'message'  => 'Your review page is ready!',
        'page_url' => $page_url,
        'post_id'  => $post_id,
    ]);
}

/**
 * Ensure the slug is unique among review_page posts.
 */
function mvrg_unique_slug( $base_slug ) {
    global $wpdb;
    $slug    = $base_slug;
    $suffix  = 2;
    while ( $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'review_page' AND post_status != 'trash'",
        $slug
    ))) {
        $slug = $base_slug . '-' . $suffix;
        $suffix++;
    }
    return $slug;
}
