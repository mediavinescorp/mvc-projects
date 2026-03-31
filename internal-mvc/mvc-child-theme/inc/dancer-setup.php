
<?php
/**
 * Dancer CPT + ACF Field Group Registration
 * Drop in functions.php or a custom plugin file
 */
 
// ── 1. Register CPT ──────────────────────────────────────────────────────────
add_action( 'init', function () {
    register_post_type( 'dancer', [
        'labels' => [
            'name'               => 'Dancers',
            'singular_name'      => 'Dancer',
            'add_new_item'       => 'Add New Dancer',
            'edit_item'          => 'Edit Dancer',
            'view_item'          => 'View Dancer',
            'search_items'       => 'Search Dancers',
            'not_found'          => 'No dancers found',
        ],
        'public'            => true,
        'has_archive'       => false,          // archive handled by custom page
        'show_in_rest'      => true,
        'menu_icon'         => 'dashicons-groups',
        'supports'          => [ 'title', 'thumbnail' ],
        'rewrite'           => [ 'slug' => 'dancers' ],
    ] );
} );
 
 
// ── 2. Register ACF Field Group (JSON-free, code-only) ───────────────────────
add_action( 'acf/init', function () {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;
 
    acf_add_local_field_group( [
        'key'    => 'group_dancer_profile',
        'title'  => 'Dancer Profile',
        'fields' => [
 
            // Photo (separate from featured image — gives editorial control)
            [
                'key'           => 'field_dancer_photo',
                'label'         => 'Profile Photo',
                'name'          => 'dancer_photo',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'instructions'  => 'Square crop recommended. Min 800×800px.',
            ],
 
            // Short tagline shown on index card
            [
                'key'          => 'field_dancer_tagline',
                'label'        => 'Tagline',
                'name'         => 'dancer_tagline',
                'type'         => 'text',
                'placeholder'  => 'e.g. "Rhythm is her language"',
                'maxlength'    => 80,
            ],
 
            // Full bio
            [
                'key'          => 'field_dancer_bio',
                'label'        => 'Bio',
                'name'         => 'dancer_bio',
                'type'         => 'textarea',
                'rows'         => 5,
            ],
 
            // Dance styles (checkbox or text — using text for flexibility)
            [
                'key'         => 'field_dancer_styles',
                'label'       => 'Dance Style(s)',
                'name'        => 'dancer_styles',
                'type'        => 'text',
                'placeholder' => 'e.g. Contemporary, Hip Hop, Ballet',
            ],
 
            // Years of experience
            [
                'key'  => 'field_dancer_years',
                'label'=> 'Years of Experience',
                'name' => 'dancer_years',
                'type' => 'number',
                'min'  => 0,
            ],
 
            // Social links (repeater)
            [
                'key'        => 'field_dancer_socials',
                'label'      => 'Social Links',
                'name'       => 'dancer_socials',
                'type'       => 'repeater',
                'min'        => 0,
                'max'        => 5,
                'button_label' => 'Add Social Link',
                'sub_fields' => [
                    [
                        'key'          => 'field_social_platform',
                        'label'        => 'Platform',
                        'name'         => 'platform',
                        'type'         => 'select',
                        'choices'      => [
                            'instagram' => 'Instagram',
                            'tiktok'    => 'TikTok',
                            'youtube'   => 'YouTube',
                            'facebook'  => 'Facebook',
                            'twitter'   => 'X / Twitter',
                            'website'   => 'Website',
                        ],
                    ],
                    [
                        'key'   => 'field_social_url',
                        'label' => 'URL',
                        'name'  => 'url',
                        'type'  => 'url',
                    ],
                ],
            ],
 
        ],
        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'dancer',
        ] ] ],
        'menu_order'   => 0,
        'label_placement' => 'top',
    ] );
} );
 
