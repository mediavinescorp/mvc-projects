<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Register admin menu ── */
add_action( 'admin_menu', 'mvtd_admin_menu' );
function mvtd_admin_menu() {
    add_menu_page(
        'Tools Directory',
        'Tools Directory',
        'manage_options',
        'mv-tools-directory',
        'mvtd_admin_main_page',
        'dashicons-grid-view',
        30
    );
    add_submenu_page(
        'mv-tools-directory',
        'All Tools',
        'All Tools',
        'manage_options',
        'mv-tools-directory',
        'mvtd_admin_main_page'
    );
    add_submenu_page(
        'mv-tools-directory',
        'Add New Tool',
        'Add New Tool',
        'manage_options',
        'mv-tools-add',
        'mvtd_admin_add_page'
    );
}

/* ════════════════════════════════════════════
   MAIN PAGE — list all tools
   ════════════════════════════════════════════ */
function mvtd_admin_main_page() {
    $tools = mvtd_get_tools();
    $notice = '';
    if ( isset( $_GET['deleted'] ) ) $notice = '<div class="notice notice-success is-dismissible"><p>Tool deleted.</p></div>';
    if ( isset( $_GET['saved'] ) )   $notice = '<div class="notice notice-success is-dismissible"><p>Tool saved successfully.</p></div>';
    if ( isset( $_GET['reordered'] ) ) $notice = '<div class="notice notice-success is-dismissible"><p>Order updated.</p></div>';
    ?>
    <div class="wrap mvtd-admin-wrap">
        <div class="mvtd-admin-header">
            <div>
                <h1>Tools Directory</h1>
                <p class="mvtd-admin-sub">Manage the tools shown on your directory page.</p>
            </div>
            <a href="<?php echo admin_url('admin.php?page=mv-tools-add'); ?>" class="mvtd-btn-primary">
                + Add New Tool
            </a>
        </div>

        <?php echo $notice; ?>

        <?php if ( empty( $tools ) ) : ?>
            <div class="mvtd-empty-state">
                <p>No tools yet. <a href="<?php echo admin_url('admin.php?page=mv-tools-add'); ?>">Add your first tool →</a></p>
            </div>
        <?php else : ?>

        <div class="mvtd-shortcode-info">
            <strong>Shortcode:</strong>
            <code>[mv_tools_directory]</code>
            — paste this on any WordPress page to display your tools directory.
        </div>

        <table class="mvtd-tools-table">
            <thead>
                <tr>
                    <th class="col-order">Order</th>
                    <th class="col-icon">Icon</th>
                    <th class="col-name">Tool Name</th>
                    <th class="col-cat">Category</th>
                    <th class="col-status">Status</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody id="mvtd-sortable">
                <?php
                $cats = mvtd_get_all_categories();
                foreach ( $tools as $i => $tool ) :
                    $is_coming = ( $tool['status'] ?? 'active' ) === 'coming_soon';
                    $cat_label = $cats[ $tool['category'] ?? 'other' ] ?? 'Other';
                    $edit_url  = admin_url( 'admin.php?page=mv-tools-add&edit=' . urlencode( $tool['id'] ) );
                    $del_url   = wp_nonce_url(
                        admin_url( 'admin.php?page=mv-tools-directory&action=delete&id=' . urlencode( $tool['id'] ) ),
                        'mvtd_delete_' . $tool['id']
                    );
                ?>
                <tr data-id="<?php echo esc_attr( $tool['id'] ); ?>">
                    <td class="col-order">
                        <span class="mvtd-drag-handle" title="Drag to reorder">⠿</span>
                        <span class="mvtd-order-num"><?php echo esc_html( $tool['order'] ?? ($i+1) ); ?></span>
                    </td>
                    <td class="col-icon">
                        <div class="mvtd-icon-preview">
                            <?php echo mvtd_get_icon_svg( $tool['icon'] ?? 'tool' ); ?>
                        </div>
                    </td>
                    <td class="col-name">
                        <strong><?php echo esc_html( $tool['name'] ); ?></strong>
                        <div class="mvtd-row-tagline"><?php echo esc_html( $tool['tagline'] ); ?></div>
                    </td>
                    <td class="col-cat">
                        <span class="mvtd-cat-pill"><?php echo esc_html( $cat_label ); ?></span>
                    </td>
                    <td class="col-status">
                        <span class="mvtd-status-pill <?php echo $is_coming ? 'coming' : 'active'; ?>">
                            <?php echo $is_coming ? 'Coming Soon' : 'Active'; ?>
                        </span>
                    </td>
                    <td class="col-actions">
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="mvtd-action-btn edit">Edit</a>
                        <?php if ( ! $is_coming && ! empty( $tool['url'] ) ) : ?>
                            <a href="<?php echo esc_url( $tool['url'] ); ?>" target="_blank" class="mvtd-action-btn view">View ↗</a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( $del_url ); ?>" class="mvtd-action-btn delete" onclick="return confirm('Delete this tool? This cannot be undone.')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="mvtd-reorder-hint">Drag rows to reorder how tools appear on the directory page.</p>
        <?php endif; ?>
    </div>
    <?php
}

/* ════════════════════════════════════════════
   ADD / EDIT PAGE
   ════════════════════════════════════════════ */
function mvtd_admin_add_page() {
    $editing  = false;
    $tool     = [];
    $edit_id  = sanitize_text_field( $_GET['edit'] ?? '' );

    if ( $edit_id ) {
        $tool = mvtd_get_tool_by_id( $edit_id );
        if ( $tool ) $editing = true;
    }

    $categories  = mvtd_get_all_categories();
    $icon_groups = mvtd_get_icon_groups();
    $icons_flat  = mvtd_get_icons_flat();
    $current_icon = $tool['icon'] ?? 'star';
    $features    = $tool['features'] ?? [''];
    ?>
    <div class="wrap mvtd-admin-wrap">
        <div class="mvtd-admin-header">
            <div>
                <h1><?php echo $editing ? 'Edit Tool' : 'Add New Tool'; ?></h1>
                <p class="mvtd-admin-sub">
                    <a href="<?php echo admin_url('admin.php?page=mv-tools-directory'); ?>">← Back to all tools</a>
                </p>
            </div>
        </div>

        <div id="mvtd-save-notice" class="notice notice-success is-dismissible" style="display:none;"><p>Tool saved successfully!</p></div>
        <div id="mvtd-error-notice" class="notice notice-error is-dismissible" style="display:none;"><p id="mvtd-error-msg"></p></div>

        <form id="mvtd-tool-form" class="mvtd-form">
            <input type="hidden" name="action" value="mvtd_save_tool">
            <input type="hidden" name="nonce"  value="<?php echo wp_create_nonce('mvtd_admin'); ?>">
            <input type="hidden" name="original_id" value="<?php echo esc_attr( $tool['id'] ?? '' ); ?>">

            <div class="mvtd-form-grid">

                <!-- LEFT COLUMN -->
                <div class="mvtd-form-main">

                    <!-- Basic info -->
                    <div class="mvtd-form-section">
                        <h2>Basic Information</h2>

                        <div class="mvtd-field">
                            <label for="f-name">Tool Name <span class="req">*</span></label>
                            <input type="text" id="f-name" name="name" value="<?php echo esc_attr( $tool['name'] ?? '' ); ?>" placeholder="e.g. Google Review Page Generator" required>
                        </div>

                        <div class="mvtd-field">
                            <label for="f-tagline">Tagline <span class="req">*</span></label>
                            <input type="text" id="f-tagline" name="tagline" value="<?php echo esc_attr( $tool['tagline'] ?? '' ); ?>" placeholder="One-line description shown on the card" required>
                            <span class="mvtd-field-hint">Keep it under 100 characters</span>
                        </div>

                        <div class="mvtd-field">
                            <label for="f-desc">Full Description</label>
                            <textarea id="f-desc" name="description" rows="4" placeholder="2-3 sentences shown when visitor clicks 'Learn more' on the card."><?php echo esc_textarea( $tool['description'] ?? '' ); ?></textarea>
                        </div>

                        <div class="mvtd-field">
                            <label for="f-url">Tool URL <span class="req">*</span></label>
                            <input type="url" id="f-url" name="url" value="<?php echo esc_url( $tool['url'] ?? '' ); ?>" placeholder="https://yourdomain.com/tool-page/" required>
                        </div>
                    </div>

                    <!-- Features list -->
                    <div class="mvtd-form-section">
                        <h2>Feature Bullets <span class="mvtd-optional">(optional)</span></h2>
                        <p class="mvtd-section-hint">Short bullet points shown in the expanded card view.</p>
                        <div id="mvtd-features-list">
                            <?php foreach ( $features as $f ) : ?>
                            <div class="mvtd-feature-row">
                                <input type="text" name="features[]" value="<?php echo esc_attr( $f ); ?>" placeholder="e.g. Generates a unique QR code for every business">
                                <button type="button" class="mvtd-remove-feature" title="Remove">✕</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="mvtd-add-feature" class="mvtd-btn-ghost">+ Add feature</button>
                    </div>

                </div>

                <!-- RIGHT COLUMN -->
                <div class="mvtd-form-side">

                    <!-- Publish box -->
                    <div class="mvtd-form-section mvtd-publish-box">
                        <h2>Publish</h2>
                        <div class="mvtd-field">
                            <label for="f-status">Status</label>
                            <select id="f-status" name="status">
                                <option value="active"      <?php selected( $tool['status'] ?? 'active', 'active' ); ?>>Active — visible on directory</option>
                                <option value="coming_soon" <?php selected( $tool['status'] ?? '', 'coming_soon' ); ?>>Coming Soon — shown as preview</option>
                            </select>
                        </div>
                        <div class="mvtd-field">
                            <label for="f-order">Display Order</label>
                            <input type="number" id="f-order" name="order" value="<?php echo esc_attr( $tool['order'] ?? 99 ); ?>" min="1" max="999">
                            <span class="mvtd-field-hint">Lower number = shows first</span>
                        </div>
                        <button type="submit" class="mvtd-btn-publish" id="mvtd-save-btn">
                            <?php echo $editing ? 'Update Tool' : 'Publish Tool'; ?>
                        </button>
                        <?php if ( $editing ) : ?>
                            <a href="<?php echo admin_url('admin.php?page=mv-tools-directory'); ?>" class="mvtd-cancel-link">Cancel</a>
                        <?php endif; ?>
                    </div>

                    <!-- Category -->
                    <div class="mvtd-form-section">
                        <h2>Category</h2>
                        <p class="mvtd-section-hint">Used to filter tools on the directory page.</p>
                        <div class="mvtd-cat-grid">
                            <?php foreach ( $categories as $key => $label ) :
                                $checked = ( ( $tool['category'] ?? 'reviews' ) === $key ) ? 'checked' : '';
                            ?>
                            <label class="mvtd-cat-option <?php echo $checked ? 'selected' : ''; ?>">
                                <input type="radio" name="category" value="<?php echo esc_attr( $key ); ?>" <?php echo $checked; ?>>
                                <?php echo esc_html( $label ); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Badge -->
                    <div class="mvtd-form-section">
                        <h2>Badge <span class="mvtd-optional">(optional)</span></h2>
                        <div class="mvtd-field">
                            <label for="f-badge">Badge text</label>
                            <input type="text" id="f-badge" name="badge" value="<?php echo esc_attr( $tool['badge'] ?? '' ); ?>" placeholder="e.g. Free, New, Popular, Beta">
                        </div>
                        <div class="mvtd-field">
                            <label>Badge color</label>
                            <div class="mvtd-badge-colors">
                                <?php foreach ( ['green'=>'Green','blue'=>'Blue','amber'=>'Amber','red'=>'Red'] as $val => $lbl ) :
                                    $checked = ( ( $tool['badge_color'] ?? 'green' ) === $val ) ? 'checked' : '';
                                ?>
                                <label class="mvtd-color-option <?php echo 'bc-'.$val; ?> <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="radio" name="badge_color" value="<?php echo $val; ?>" <?php echo $checked; ?>>
                                    <?php echo $lbl; ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Icon picker -->
                    <div class="mvtd-form-section">
                        <h2>Icon</h2>
                        <input type="hidden" name="icon" id="f-icon" value="<?php echo esc_attr( $current_icon ); ?>">
                        <div class="mvtd-icon-search-wrap">
                            <input type="text" id="mvtd-icon-search" placeholder="Search icons..." class="mvtd-icon-search">
                        </div>
                        <div class="mvtd-icon-picker" id="mvtd-icon-picker">
                            <?php foreach ( $icon_groups as $group => $items ) : ?>
                                <div class="mvtd-icon-group" data-group="<?php echo esc_attr( $group ); ?>">
                                    <div class="mvtd-icon-group-label"><?php echo esc_html( $group ); ?></div>
                                    <div class="mvtd-icon-grid">
                                        <?php foreach ( $items as $icon_key => $icon_label ) : ?>
                                        <div
                                            class="mvtd-icon-option <?php echo ( $current_icon === $icon_key ) ? 'selected' : ''; ?>"
                                            data-icon="<?php echo esc_attr( $icon_key ); ?>"
                                            data-label="<?php echo esc_attr( $icon_label ); ?>"
                                            title="<?php echo esc_attr( $icon_label ); ?>"
                                        >
                                            <?php echo mvtd_get_icon_svg( $icon_key ); ?>
                                            <span><?php echo esc_html( $icon_label ); ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mvtd-icon-selected-label">
                            Selected: <strong id="mvtd-selected-icon-name">
                                <?php echo esc_html( $icons_flat[ $current_icon ]['label'] ?? ucfirst( $current_icon ) ); ?>
                            </strong>
                        </div>
                    </div>

                </div><!-- end side -->
            </div><!-- end grid -->
        </form>
    </div>
    <?php
}

/* ════════════════════════════════════════════
   AJAX: SAVE TOOL
   ════════════════════════════════════════════ */
add_action( 'wp_ajax_mvtd_save_tool', 'mvtd_ajax_save_tool' );
function mvtd_ajax_save_tool() {
    check_ajax_referer( 'mvtd_admin', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error([ 'message' => 'Unauthorised.' ]);

    $name     = sanitize_text_field( $_POST['name']    ?? '' );
    $tagline  = sanitize_text_field( $_POST['tagline'] ?? '' );
    $url      = esc_url_raw( trim( $_POST['url'] ?? '' ) );
    $orig_id  = sanitize_text_field( $_POST['original_id'] ?? '' );

    if ( ! $name )    wp_send_json_error([ 'message' => 'Tool name is required.' ]);
    if ( ! $tagline ) wp_send_json_error([ 'message' => 'Tagline is required.' ]);
    if ( ! $url )     wp_send_json_error([ 'message' => 'Tool URL is required.' ]);

    // Build features array
    $raw_features = $_POST['features'] ?? [];
    $features = array_values( array_filter( array_map( 'sanitize_text_field', (array) $raw_features ) ) );

    $tool = [
        'id'          => $orig_id ?: mvtd_generate_id( $name ),
        'name'        => $name,
        'tagline'     => $tagline,
        'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
        'url'         => $url,
        'icon'        => sanitize_text_field( $_POST['icon'] ?? 'star' ),
        'category'    => sanitize_text_field( $_POST['category'] ?? 'other' ),
        'badge'       => sanitize_text_field( $_POST['badge'] ?? '' ),
        'badge_color' => sanitize_text_field( $_POST['badge_color'] ?? 'green' ),
        'status'      => sanitize_text_field( $_POST['status'] ?? 'active' ),
        'features'    => $features,
        'order'       => intval( $_POST['order'] ?? 99 ),
    ];

    $tools = mvtd_get_tools();

    if ( $orig_id ) {
        // Update existing
        $found = false;
        foreach ( $tools as &$t ) {
            if ( $t['id'] === $orig_id ) { $t = $tool; $found = true; break; }
        }
        if ( ! $found ) $tools[] = $tool;
    } else {
        // New tool — check for duplicate ID
        $ids = array_column( $tools, 'id' );
        if ( in_array( $tool['id'], $ids ) ) {
            $tool['id'] .= '-' . time();
        }
        $tools[] = $tool;
    }

    mvtd_save_tools( $tools );
    wp_send_json_success([ 'message' => 'Saved!', 'id' => $tool['id'] ]);
}

/* ════════════════════════════════════════════
   AJAX: REORDER TOOLS
   ════════════════════════════════════════════ */
add_action( 'wp_ajax_mvtd_reorder_tools', 'mvtd_ajax_reorder' );
function mvtd_ajax_reorder() {
    check_ajax_referer( 'mvtd_admin', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

    $order = array_map( 'sanitize_text_field', (array) ( $_POST['order'] ?? [] ) );
    $tools = mvtd_get_tools();
    $indexed = [];
    foreach ( $tools as $t ) $indexed[ $t['id'] ] = $t;

    $reordered = [];
    foreach ( $order as $i => $id ) {
        if ( isset( $indexed[ $id ] ) ) {
            $indexed[ $id ]['order'] = $i + 1;
            $reordered[] = $indexed[ $id ];
        }
    }
    mvtd_save_tools( $reordered );
    wp_send_json_success();
}

/* ════════════════════════════════════════════
   DELETE TOOL (non-AJAX, via GET link)
   ════════════════════════════════════════════ */
add_action( 'admin_init', 'mvtd_handle_delete' );
function mvtd_handle_delete() {
    if (
        isset( $_GET['page'], $_GET['action'], $_GET['id'] ) &&
        $_GET['page'] === 'mv-tools-directory' &&
        $_GET['action'] === 'delete' &&
        current_user_can( 'manage_options' )
    ) {
        $id = sanitize_text_field( $_GET['id'] );
        check_admin_referer( 'mvtd_delete_' . $id );
        $tools = array_filter( mvtd_get_tools(), fn( $t ) => $t['id'] !== $id );
        mvtd_save_tools( array_values( $tools ) );
        wp_redirect( admin_url( 'admin.php?page=mv-tools-directory&deleted=1' ) );
        exit;
    }
}
