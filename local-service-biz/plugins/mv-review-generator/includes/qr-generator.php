<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Generate a QR code for a given URL and save it permanently
 * to the WordPress uploads directory.
 *
 * Strategy: call the free QR API once at form-submit time,
 * download the PNG, save it to wp-content/uploads/mv-qrcodes/,
 * store the local URL in post meta. After this point the page
 * is 100% self-hosted — no ongoing external dependency.
 *
 * @param  string $review_link  The Google review URL to encode.
 * @param  int    $post_id      The CPT post ID (used in filename).
 * @param  string $biz_slug     Sanitized business name slug.
 * @return array|WP_Error       ['url' => ..., 'path' => ...] or WP_Error
 */
function mvrg_generate_qr( $review_link, $post_id, $biz_slug ) {

    $upload     = wp_upload_dir();
    $qr_dir     = $upload['basedir'] . '/' . MVRG_QR_DIR;
    $qr_url_base = $upload['baseurl'] . '/' . MVRG_QR_DIR;

    // Ensure directory exists
    if ( ! file_exists( $qr_dir ) ) {
        if ( ! wp_mkdir_p( $qr_dir ) ) {
            return new WP_Error( 'mvrg_dir', 'Could not create QR directory.' );
        }
        // Drop an index.php for security
        file_put_contents( $qr_dir . '/index.php', '<?php // Silence is golden.' );
    }

    // Unique filename: qr-{slug}-{post_id}.png
    $filename = 'qr-' . sanitize_file_name( $biz_slug ) . '-' . intval( $post_id ) . '.png';
    $filepath = $qr_dir . '/' . $filename;
    $fileurl  = $qr_url_base . '/' . $filename;

    // If already generated (e.g. re-save), skip re-download
    if ( file_exists( $filepath ) ) {
        return [ 'url' => $fileurl, 'path' => $filepath ];
    }

    // Build the QR API URL — 300x300 px, high error correction
    $api_url = add_query_arg([
        'size' => '300x300',
        'ecc'  => 'H',
        'data' => rawurlencode( $review_link ),
    ], 'https://api.qrserver.com/v1/create-qr-code/' );

    // Fetch the QR image using WordPress HTTP API
    $response = wp_remote_get( $api_url, [
        'timeout'  => 15,
        'headers'  => [ 'Accept' => 'image/png' ],
    ]);

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'mvrg_fetch', 'QR API request failed: ' . $response->get_error_message() );
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        return new WP_Error( 'mvrg_api', 'QR API returned HTTP ' . $code );
    }

    $body = wp_remote_retrieve_body( $response );
    if ( empty( $body ) ) {
        return new WP_Error( 'mvrg_empty', 'QR API returned empty response.' );
    }

    // Verify it looks like a PNG
    if ( substr( $body, 0, 8 ) !== "\x89PNG\r\n\x1a\n" ) {
        return new WP_Error( 'mvrg_invalid', 'QR API did not return a valid PNG.' );
    }

    // Save to disk
    $saved = file_put_contents( $filepath, $body );
    if ( $saved === false ) {
        return new WP_Error( 'mvrg_save', 'Could not save QR image to disk.' );
    }

    return [ 'url' => $fileurl, 'path' => $filepath ];
}

/**
 * Delete the QR image file when a review_page post is deleted.
 */
add_action( 'before_delete_post', 'mvrg_delete_qr_on_post_delete' );
function mvrg_delete_qr_on_post_delete( $post_id ) {
    if ( get_post_type( $post_id ) !== 'review_page' ) return;
    $path = get_post_meta( $post_id, 'qr_image_path', true );
    if ( $path && file_exists( $path ) ) {
        @unlink( $path );
    }
}
