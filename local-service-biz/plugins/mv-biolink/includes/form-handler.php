<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_mvbl_submit',        'mvbl_handle_submission' );
add_action( 'wp_ajax_nopriv_mvbl_submit', 'mvbl_handle_submission' );

function mvbl_handle_submission() {
    if ( ! check_ajax_referer( 'mvbl_submit', 'nonce', false ) ) {
        wp_send_json_error([ 'message' => 'Security check failed. Please refresh and try again.' ]);
    }

    /* ── 1. Sanitize all fields ── */
    $full_name    = sanitize_text_field( $_POST['full_name']    ?? '' );
    $job_title    = sanitize_text_field( $_POST['job_title']    ?? '' );
    $company      = sanitize_text_field( $_POST['company']      ?? '' );
    $bio          = sanitize_textarea_field( $_POST['bio']      ?? '' );
    $phone        = sanitize_text_field( $_POST['phone']        ?? '' );
    $email        = sanitize_email(      $_POST['email']        ?? '' );
    $website      = esc_url_raw( trim(   $_POST['website']      ?? '' ) );
    $address      = sanitize_text_field( $_POST['address']      ?? '' );
    $layout       = sanitize_text_field( $_POST['layout']       ?? 'layout-1' );
    $bg_color     = sanitize_hex_color(  $_POST['bg_color']     ?? '#ffffff' ) ?: '#ffffff';
    $accent_color = sanitize_hex_color(  $_POST['accent_color'] ?? '#1a6b4a' ) ?: '#1a6b4a';
    $text_color   = sanitize_hex_color(  $_POST['text_color']   ?? '#141414' ) ?: '#141414';
    $socials_raw  = sanitize_text_field( $_POST['socials']      ?? '[]' );
    $links_raw    = sanitize_text_field( $_POST['links']        ?? '[]' );

    if ( ! $full_name ) {
        wp_send_json_error([ 'message' => 'Full name is required.' ]);
    }

    /* ── 2. Validate & clean socials JSON ── */
    // JS sends pre-built URLs for all social fields
    $socials = json_decode( wp_unslash( $socials_raw ), true );
    if ( ! is_array( $socials ) ) $socials = [];
    $socials = array_filter( $socials, fn($s) => ! empty($s['url']) );
    $socials = array_values( array_map( function($s) {
        $url = esc_url_raw( $s['url'] );
        // Auto-add https:// if missing
        if ( $url && ! preg_match( '#^https?://#', $url ) ) {
            $url = 'https://' . $url;
        }
        return [
            'network' => sanitize_text_field( $s['network'] ?? 'link' ),
            'url'     => $url,
            'label'   => sanitize_text_field( $s['label'] ?? '' ),
        ];
    }, $socials ) );

    /* ── 3. Validate & clean links JSON ── */
    $links = json_decode( wp_unslash( $links_raw ), true );
    if ( ! is_array( $links ) ) $links = [];
    $links = array_filter( $links, fn($l) => ! empty($l['url']) && ! empty($l['label']) );
    $links = array_slice( array_values( array_map( function($l) {
        return [
            'label' => sanitize_text_field( $l['label'] ),
            'url'   => esc_url_raw( $l['url'] ),
            'icon'  => sanitize_text_field( $l['icon'] ?? 'link' ),
        ];
    }, $links ) ), 0, 10 ); // max 10

    /* ── 4. Generate unique slug ── */
    $base_slug = sanitize_title( $full_name );
    $slug      = mvbl_unique_slug( $base_slug );

    /* ── 5. Create CPT post ── */
    $post_id = wp_insert_post([
        'post_title'  => $full_name,
        'post_name'   => $slug,
        'post_type'   => 'biolink_page',
        'post_status' => 'publish',
        'meta_input'  => [
            'full_name'    => $full_name,
            'job_title'    => $job_title,
            'company'      => $company,
            'bio'          => $bio,
            'phone'        => $phone,
            'email'        => $email,
            'website'      => $website,
            'address'      => $address,
            'layout'       => $layout,
            'bg_color'     => $bg_color,
            'accent_color' => $accent_color,
            'text_color'   => $text_color,
            'socials'      => json_encode( $socials ),
            'links'        => json_encode( $links ),
            'submitted_at' => current_time( 'mysql' ),
        ],
    ], true );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error([ 'message' => 'Could not create bio page: ' . $post_id->get_error_message() ]);
    }

    /* ── 6. Handle avatar upload ── */
    $avatar_url = '';
    if ( ! empty( $_FILES['avatar'] ) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $allowed = [ 'image/png', 'image/jpeg', 'image/gif', 'image/webp' ];
        $mime    = mime_content_type( $_FILES['avatar']['tmp_name'] );
        if ( in_array( $mime, $allowed ) && $_FILES['avatar']['size'] <= 5 * 1024 * 1024 ) {
            $avatar_id = media_handle_upload( 'avatar', $post_id );
            if ( ! is_wp_error( $avatar_id ) ) {
                $avatar_url = wp_get_attachment_url( $avatar_id );
                update_post_meta( $post_id, 'avatar_url', $avatar_url );
                update_post_meta( $post_id, 'avatar_id',  $avatar_id );
                set_post_thumbnail( $post_id, $avatar_id );
            }
        }
    }

    /* ── 7. Generate vCard ── */
    $vcard_result = mvbl_generate_vcard( $post_id, [
        'full_name'  => $full_name,
        'job_title'  => $job_title,
        'company'    => $company,
        'bio'        => $bio,
        'phone'      => $phone,
        'email'      => $email,
        'website'    => $website,
        'address'    => $address,
        'avatar_url' => $avatar_url,
        'socials'    => json_encode( $socials ),
    ]);

    $vcard_url = '';
    if ( ! is_wp_error( $vcard_result ) ) {
        $vcard_url = $vcard_result['url'];
        update_post_meta( $post_id, 'vcard_url',  $vcard_result['url'] );
        update_post_meta( $post_id, 'vcard_path', $vcard_result['path'] );
    }

    /* ── 8. Generate page QR (links to biolink page) ── */
    $page_url  = get_permalink( $post_id );
    if ( ! $page_url ) $page_url = home_url( '/biolink/' . $slug . '/' );

    $qr_page = mvbl_generate_qr( $page_url, 'qr-page-' . $slug . '-' . $post_id );
    if ( ! is_wp_error( $qr_page ) ) {
        update_post_meta( $post_id, 'qr_page_url',  $qr_page['url'] );
        update_post_meta( $post_id, 'qr_page_path', $qr_page['path'] );
    }

    /* ── 9. Generate vCard QR (encodes vCard download URL) ── */
    if ( $vcard_url ) {
        $vcard_download_url = add_query_arg( 'mvbl_vcard', $post_id, home_url('/') );
        $qr_vcard = mvbl_generate_qr( $vcard_download_url, 'qr-vcard-' . $slug . '-' . $post_id );
        if ( ! is_wp_error( $qr_vcard ) ) {
            update_post_meta( $post_id, 'qr_vcard_url',  $qr_vcard['url'] );
            update_post_meta( $post_id, 'qr_vcard_path', $qr_vcard['path'] );
        }
    }

    /* ── 10. Return ── */
    wp_send_json_success([
        'message'  => 'Your bio page is ready!',
        'page_url' => $page_url,
        'post_id'  => $post_id,
    ]);
}

function mvbl_unique_slug( $base ) {
    global $wpdb;
    $slug = $base; $n = 2;
    while ( $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name=%s AND post_type='biolink_page' AND post_status!='trash'",
        $slug
    ))) { $slug = $base . '-' . $n++; }
    return $slug;
}
