<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'mvbl_register_cpt' );
function mvbl_register_cpt() {
    register_post_type( 'biolink_page', [
        'labels'             => [
            'name'          => 'BioLink Pages',
            'singular_name' => 'BioLink Page',
            'all_items'     => 'All BioLink Pages',
            'menu_name'     => 'BioLink Pages',
        ],
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => [ 'slug' => 'biolink', 'with_front' => false ],
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 26,
        'menu_icon'          => 'dashicons-id-alt',
        'supports'           => [ 'title' ],
        'show_in_rest'       => false,
    ]);
}

/* ── Meta fields ── */
add_action( 'init', 'mvbl_register_meta' );
function mvbl_register_meta() {
    $fields = [
        // Identity
        'full_name'      => 'string',
        'job_title'      => 'string',
        'company'        => 'string',
        'bio'            => 'string',
        // Contact
        'phone'          => 'string',
        'email'          => 'string',
        'website'        => 'string',
        'address'        => 'string',
        // Avatar
        'avatar_url'     => 'string',
        'avatar_id'      => 'integer',
        // Social (stored as JSON array)
        'socials'        => 'string',
        // Custom links (stored as JSON array)
        'links'          => 'string',
        // Design
        'layout'         => 'string',
        'bg_color'       => 'string',
        'accent_color'   => 'string',
        'text_color'     => 'string',
        // QR codes
        'qr_page_url'    => 'string',
        'qr_page_path'   => 'string',
        'qr_vcard_url'   => 'string',
        'qr_vcard_path'  => 'string',
        // vCard download
        'vcard_url'      => 'string',
        'vcard_path'     => 'string',
        // Meta
        'submitted_at'   => 'string',
    ];
    foreach ( $fields as $key => $type ) {
        register_post_meta( 'biolink_page', $key, [
            'single'        => true,
            'type'          => $type,
            'show_in_rest'  => false,
            'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
        ]);
    }
}

/* ── Admin meta box ── */
add_action( 'add_meta_boxes', 'mvbl_add_meta_box' );
function mvbl_add_meta_box() {
    add_meta_box( 'mvbl_details', 'BioLink Details', 'mvbl_render_meta_box', 'biolink_page', 'normal', 'high' );
}

function mvbl_render_meta_box( $post ) {
    $fields = [
        'full_name'   => 'Full Name',
        'job_title'   => 'Job Title',
        'company'     => 'Company',
        'phone'       => 'Phone',
        'email'       => 'Email',
        'website'     => 'Website',
        'layout'      => 'Layout',
        'bg_color'    => 'Background Color',
        'accent_color'=> 'Accent Color',
        'avatar_url'  => 'Avatar URL',
        'qr_page_url' => 'Page QR Code',
        'qr_vcard_url'=> 'vCard QR Code',
        'vcard_url'   => 'vCard Download',
        'submitted_at'=> 'Submitted',
    ];
    echo '<table class="form-table">';
    foreach ( $fields as $key => $label ) {
        $val = get_post_meta( $post->ID, $key, true );
        echo '<tr><th style="width:150px;padding:6px 0">' . esc_html($label) . '</th><td style="padding:6px 0">';
        if ( in_array( $key, ['qr_page_url','qr_vcard_url'] ) && $val ) {
            echo '<img src="' . esc_url($val) . '" style="width:80px;height:80px;border:1px solid #ddd;padding:3px;border-radius:4px">';
        } elseif ( $key === 'vcard_url' && $val ) {
            echo '<a href="' . esc_url($val) . '" download>Download vCard</a>';
        } elseif ( $key === 'avatar_url' && $val ) {
            echo '<img src="' . esc_url($val) . '" style="width:60px;height:60px;object-fit:cover;border-radius:50%;border:1px solid #ddd">';
        } else {
            echo '<span style="color:' . ($val?'#333':'#999') . '">' . esc_html($val ?: '—') . '</span>';
        }
        echo '</td></tr>';
    }
    echo '</table>';
    $url = get_permalink($post->ID);
    if ($url) {
        echo '<hr style="margin:12px 0"><p><strong>Public URL:</strong> <a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a>';
        echo ' <button type="button" onclick="navigator.clipboard.writeText(\'' . esc_js($url) . '\');this.textContent=\'Copied!\';setTimeout(()=>this.textContent=\'Copy\',2000)" class="button button-small">Copy</button></p>';
    }
}
