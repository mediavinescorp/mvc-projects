<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Build and save a vCard (.vcf) file for a biolink post.
 * iPhone-compatible: photo compressed to <50KB, clean formatting.
 *
 * @param  int   $post_id
 * @param  array $data     Sanitized post data
 * @return array|WP_Error  ['url'=>..., 'path'=>...]
 */
function mvbl_generate_vcard( $post_id, $data ) {
    $upload    = wp_upload_dir();
    $dir       = $upload['basedir'] . '/' . MVBL_QR_DIR;
    $slug      = sanitize_title( $data['full_name'] ?: 'contact' );
    $filename  = 'vcard-' . $slug . '-' . $post_id . '.vcf';
    $filepath  = $dir . '/' . $filename;
    $fileurl   = $upload['baseurl'] . '/' . MVBL_QR_DIR . '/' . $filename;

    // Build vCard 3.0 (best iPhone compatibility)
    $lines = [];
    $lines[] = 'BEGIN:VCARD';
    $lines[] = 'VERSION:3.0';

    // Name
    $full  = sanitize_text_field( $data['full_name'] ?? '' );
    $parts = explode( ' ', $full, 2 );
    $first = $parts[0] ?? '';
    $last  = $parts[1] ?? '';
    $lines[] = 'N:' . mvbl_vcard_escape($last) . ';' . mvbl_vcard_escape($first) . ';;;';
    $lines[] = 'FN:' . mvbl_vcard_escape($full);

    // Organization / Title
    if ( ! empty( $data['company'] ) )   $lines[] = 'ORG:'   . mvbl_vcard_escape( $data['company'] );
    if ( ! empty( $data['job_title'] ) ) $lines[] = 'TITLE:' . mvbl_vcard_escape( $data['job_title'] );

    // Contact
    if ( ! empty( $data['phone'] ) )   $lines[] = 'TEL;TYPE=CELL:'  . preg_replace('/[^\d+\-\(\) ]/', '', $data['phone']);
    if ( ! empty( $data['email'] ) )   $lines[] = 'EMAIL;TYPE=WORK:' . sanitize_email( $data['email'] );
    if ( ! empty( $data['website'] ) ) $lines[] = 'URL:'             . esc_url_raw( $data['website'] );
    if ( ! empty( $data['address'] ) ) $lines[] = 'ADR;TYPE=WORK:;;' . mvbl_vcard_escape( $data['address'] ) . ';;;;';

    // Bio / note
    if ( ! empty( $data['bio'] ) ) $lines[] = 'NOTE:' . mvbl_vcard_escape( $data['bio'] );

    // Social links — stored as plain URLs in NOTE or X- fields
    $socials = json_decode( $data['socials'] ?? '[]', true );
    if ( is_array( $socials ) ) {
        foreach ( $socials as $s ) {
            if ( empty($s['url']) ) continue;
            $network = strtoupper( sanitize_text_field( $s['network'] ?? 'social' ) );
            $lines[] = 'X-' . $network . ';TYPE=profile:' . esc_url_raw( $s['url'] );
        }
    }

    // Photo — embed only if avatar exists and we can compress it under 50KB
    if ( ! empty( $data['avatar_url'] ) ) {
        $photo_b64 = mvbl_get_photo_base64( $data['avatar_url'] );
        if ( $photo_b64 ) {
            // Fold long lines per vCard spec (max 75 chars per line, continuation with space)
            $photo_line = 'PHOTO;ENCODING=b;TYPE=JPEG:' . $photo_b64;
            $lines[] = mvbl_vcard_fold( $photo_line );
        }
    }

    $lines[] = 'END:VCARD';

    $content = implode( "\r\n", $lines ) . "\r\n";
    $saved   = file_put_contents( $filepath, $content );

    if ( $saved === false ) {
        return new WP_Error( 'mvbl_vcard', 'Could not save vCard file.' );
    }

    return [ 'url' => $fileurl, 'path' => $filepath ];
}

/**
 * Fetch avatar image, resize to max 200x200, compress to JPEG under 50KB.
 * Returns base64 string or false.
 */
function mvbl_get_photo_base64( $avatar_url ) {
    if ( ! function_exists( 'imagecreatefromstring' ) ) return false;

    $response = wp_remote_get( $avatar_url, [ 'timeout' => 10 ] );
    if ( is_wp_error( $response ) ) return false;

    $body = wp_remote_retrieve_body( $response );
    if ( empty( $body ) ) return false;

    $img = @imagecreatefromstring( $body );
    if ( ! $img ) return false;

    // Resize to max 200x200
    $orig_w = imagesx( $img );
    $orig_h = imagesy( $img );
    $max    = 200;
    if ( $orig_w > $max || $orig_h > $max ) {
        $ratio  = min( $max / $orig_w, $max / $orig_h );
        $new_w  = (int) round( $orig_w * $ratio );
        $new_h  = (int) round( $orig_h * $ratio );
        $resized = imagecreatetruecolor( $new_w, $new_h );
        imagecopyresampled( $resized, $img, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h );
        imagedestroy( $img );
        $img = $resized;
    }

    // Compress JPEG, reduce quality until under 50KB
    $quality = 85;
    do {
        ob_start();
        imagejpeg( $img, null, $quality );
        $jpeg = ob_get_clean();
        $quality -= 10;
    } while ( strlen( $jpeg ) > 50000 && $quality > 20 );

    imagedestroy( $img );

    if ( strlen( $jpeg ) > 50000 ) return false; // still too large, skip photo

    return base64_encode( $jpeg );
}

/**
 * Escape special vCard characters.
 */
function mvbl_vcard_escape( $str ) {
    return str_replace(
        [ '\\', "\n", ',', ';' ],
        [ '\\\\', '\n', '\,', '\;' ],
        (string) $str
    );
}

/**
 * Fold long vCard lines (RFC 6350: max 75 octets, continue with space).
 */
function mvbl_vcard_fold( $line ) {
    if ( strlen( $line ) <= 75 ) return $line;
    $folded = '';
    while ( strlen( $line ) > 75 ) {
        $folded .= substr( $line, 0, 75 ) . "\r\n ";
        $line    = substr( $line, 75 );
    }
    return $folded . $line;
}

/**
 * Serve vCard file as download.
 * Hooked to ?mvbl_vcard=POST_ID query var.
 */
add_action( 'init', 'mvbl_maybe_serve_vcard' );
function mvbl_maybe_serve_vcard() {
    $post_id = intval( $_GET['mvbl_vcard'] ?? 0 );
    if ( ! $post_id ) return;

    $path = get_post_meta( $post_id, 'vcard_path', true );
    $name = sanitize_title( get_the_title( $post_id ) );

    if ( $path && file_exists( $path ) ) {
        header( 'Content-Type: text/vcard; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $name . '.vcf"' );
        header( 'Content-Length: ' . filesize( $path ) );
        readfile( $path );
        exit;
    }
    wp_die( 'vCard not found.' );
}
