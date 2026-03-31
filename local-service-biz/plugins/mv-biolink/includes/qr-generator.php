<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Generate a QR code PNG, save to uploads, return URL + path.
 *
 * @param  string $content   The text/URL to encode
 * @param  string $filename  Filename without extension
 * @return array|WP_Error
 */
function mvbl_generate_qr( $content, $filename ) {
    $upload   = wp_upload_dir();
    $dir      = $upload['basedir'] . '/' . MVBL_QR_DIR;
    $url_base = $upload['baseurl']  . '/' . MVBL_QR_DIR;

    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
        file_put_contents( $dir . '/index.php', '<?php // Silence is golden.' );
    }

    $file     = sanitize_file_name( $filename ) . '.png';
    $filepath = $dir . '/' . $file;
    $fileurl  = $url_base . '/' . $file;

    if ( file_exists( $filepath ) ) {
        return [ 'url' => $fileurl, 'path' => $filepath ];
    }

    $api_url = add_query_arg([
        'size' => '300x300',
        'ecc'  => 'H',
        'data' => rawurlencode( $content ),
    ], 'https://api.qrserver.com/v1/create-qr-code/' );

    $response = wp_remote_get( $api_url, [ 'timeout' => 15 ] );
    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'mvbl_qr_fetch', $response->get_error_message() );
    }
    if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return new WP_Error( 'mvbl_qr_api', 'QR API error.' );
    }
    $body = wp_remote_retrieve_body( $response );
    if ( substr( $body, 0, 8 ) !== "\x89PNG\r\n\x1a\n" ) {
        return new WP_Error( 'mvbl_qr_invalid', 'Invalid PNG returned.' );
    }

    file_put_contents( $filepath, $body );
    return [ 'url' => $fileurl, 'path' => $filepath ];
}

/**
 * Delete QR files and vCard when post is deleted.
 */
add_action( 'before_delete_post', 'mvbl_cleanup_files' );
function mvbl_cleanup_files( $post_id ) {
    if ( get_post_type( $post_id ) !== 'biolink_page' ) return;
    foreach ( [ 'qr_page_path', 'qr_vcard_path', 'vcard_path' ] as $key ) {
        $path = get_post_meta( $post_id, $key, true );
        if ( $path && file_exists( $path ) ) @unlink( $path );
    }
}
