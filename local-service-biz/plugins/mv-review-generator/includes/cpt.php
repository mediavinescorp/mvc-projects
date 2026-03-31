<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Register Custom Post Type ── */
add_action( 'init', 'mvrg_register_cpt' );
function mvrg_register_cpt() {
    $labels = [
        'name'               => 'Review Pages',
        'singular_name'      => 'Review Page',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Review Page',
        'edit_item'          => 'Edit Review Page',
        'view_item'          => 'View Review Page',
        'all_items'          => 'All Review Pages',
        'search_items'       => 'Search Review Pages',
        'not_found'          => 'No review pages found.',
        'menu_name'          => 'Review Pages',
    ];

    register_post_type( 'review_page', [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => [ 'slug' => 'review', 'with_front' => false ],
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 25,
        'menu_icon'          => 'dashicons-star-filled',
        'supports'           => [ 'title', 'thumbnail' ],
        'show_in_rest'       => false,
    ]);
}

/* ── Register meta fields ── */
add_action( 'init', 'mvrg_register_meta' );
function mvrg_register_meta() {
    $fields = [
        'review_link'  => 'string',
        'tagline'      => 'string',
        'industry'     => 'string',
        'logo_url'     => 'string',
        'logo_id'      => 'integer',
        'qr_image_url' => 'string',
        'qr_image_path'=> 'string',
        'submitted_ip' => 'string',
        'submitted_at' => 'string',
    ];
    foreach ( $fields as $key => $type ) {
        register_post_meta( 'review_page', $key, [
            'single'       => true,
            'type'         => $type,
            'show_in_rest' => false,
            'auth_callback'=> function() { return current_user_can( 'edit_posts' ); },
        ]);
    }
}

/* ── Admin meta box to display all fields ── */
add_action( 'add_meta_boxes', 'mvrg_add_meta_box' );
function mvrg_add_meta_box() {
    add_meta_box(
        'mvrg_details',
        'Review Page Details',
        'mvrg_render_meta_box',
        'review_page',
        'normal',
        'high'
    );
}

function mvrg_render_meta_box( $post ) {
    $fields = [
        'review_link'   => 'Google Review Link',
        'tagline'       => 'Tagline',
        'industry'      => 'Industry',
        'logo_url'      => 'Logo URL',
        'qr_image_url'  => 'QR Code Image URL',
        'submitted_at'  => 'Submitted At',
    ];
    echo '<table class="form-table" style="width:100%">';
    foreach ( $fields as $key => $label ) {
        $value = get_post_meta( $post->ID, $key, true );
        echo '<tr>';
        echo '<th style="width:160px;padding:8px 0"><label>' . esc_html( $label ) . '</label></th>';
        echo '<td style="padding:8px 0">';
        if ( $key === 'qr_image_url' && $value ) {
            echo '<img src="' . esc_url( $value ) . '" style="width:120px;height:120px;border:1px solid #ddd;padding:4px;border-radius:4px"><br>';
            echo '<small style="color:#666">' . esc_html( $value ) . '</small>';
        } elseif ( $key === 'review_link' && $value ) {
            echo '<a href="' . esc_url( $value ) . '" target="_blank">' . esc_html( $value ) . '</a>';
        } else {
            echo '<span style="color:' . ( $value ? '#333' : '#999' ) . '">' . esc_html( $value ?: '—' ) . '</span>';
        }
        echo '</td></tr>';
    }
    echo '</table>';

    // Page URL
    $page_url = get_permalink( $post->ID );
    if ( $page_url ) {
        echo '<hr style="margin:12px 0">';
        echo '<p><strong>Public Page URL:</strong> ';
        echo '<a href="' . esc_url( $page_url ) . '" target="_blank">' . esc_html( $page_url ) . '</a>';
        echo ' &nbsp;<button type="button" onclick="navigator.clipboard.writeText(\'' . esc_js( $page_url ) . '\');this.textContent=\'Copied!\';setTimeout(()=>this.textContent=\'Copy URL\',2000)" class="button button-small">Copy URL</button>';
        echo '</p>';
    }
}
