<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'mv_tools_directory', 'mvtd_render_directory' );

function mvtd_render_directory( $atts ) {
    $atts = shortcode_atts([
        'title'    => 'Tools for Local Service Businesses',
        'subtitle' => 'Free tools built by Media Vines Corp to help local businesses grow their online presence, collect more reviews, and get found by more customers.',
        'columns'  => '3',
    ], $atts );

    $tools      = mvtd_get_tools();
    $categories = mvtd_get_active_categories();
    $total      = count( $tools );
    $active     = count( array_filter( $tools, fn($t) => ( $t['status'] ?? 'active' ) === 'active' ) );

    ob_start();
    ?>
    <div class="mvtd-wrap" id="mvtd-wrap">

        <div class="mvtd-hero">
            <div class="mvtd-hero-tag">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                <?php echo esc_html( $active ); ?> tool<?php echo $active !== 1 ? 's' : ''; ?> available
            </div>
            <h1 class="mvtd-hero-title"><?php echo esc_html( $atts['title'] ); ?></h1>
            <p class="mvtd-hero-sub"><?php echo esc_html( $atts['subtitle'] ); ?></p>
        </div>

        <?php if ( count( $categories ) > 2 ) : ?>
        <div class="mvtd-filters">
            <?php foreach ( $categories as $key => $label ) : ?>
                <button class="mvtd-filter-btn <?php echo $key === 'all' ? 'active' : ''; ?>" data-category="<?php echo esc_attr( $key ); ?>">
                    <?php echo esc_html( $label ); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="mvtd-grid mvtd-cols-<?php echo esc_attr( $atts['columns'] ); ?>" id="mvtd-grid">
            <?php foreach ( $tools as $tool ) :
                $is_coming = ( $tool['status'] ?? 'active' ) === 'coming_soon';
                $badge     = $tool['badge'] ?? '';
                $badge_cls = 'mvtd-badge-' . ( $tool['badge_color'] ?? 'green' );
                $features  = $tool['features'] ?? [];
            ?>
            <div class="mvtd-card <?php echo $is_coming ? 'mvtd-coming-soon' : ''; ?>" data-category="<?php echo esc_attr( $tool['category'] ?? 'other' ); ?>">
                <div class="mvtd-card-top">
                    <div class="mvtd-card-icon-wrap">
                        <?php echo mvtd_get_icon_svg( $tool['icon'] ?? 'tool' ); ?>
                    </div>
                    <div class="mvtd-card-meta">
                        <?php if ( $badge ) : ?>
                            <span class="mvtd-badge <?php echo esc_attr( $badge_cls ); ?>"><?php echo esc_html( $badge ); ?></span>
                        <?php endif; ?>
                        <?php if ( $is_coming ) : ?>
                            <span class="mvtd-badge mvtd-badge-amber">Coming Soon</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mvtd-card-content">
                    <h3 class="mvtd-card-title"><?php echo esc_html( $tool['name'] ); ?></h3>
                    <p class="mvtd-card-tagline"><?php echo esc_html( $tool['tagline'] ); ?></p>
                </div>

                <div class="mvtd-card-details" id="mvtd-details-<?php echo esc_attr( $tool['id'] ); ?>">
                    <?php if ( ! empty( $tool['description'] ) ) : ?>
                        <p class="mvtd-card-desc"><?php echo esc_html( $tool['description'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $features ) ) : ?>
                    <ul class="mvtd-features">
                        <?php foreach ( $features as $f ) : ?>
                        <li>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php echo esc_html( $f ); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

                <div class="mvtd-card-footer">
                    <?php if ( ! empty( $tool['description'] ) || ! empty( $features ) ) : ?>
                    <button class="mvtd-expand-btn" data-id="<?php echo esc_attr( $tool['id'] ); ?>" aria-expanded="false">
                        <span class="mvtd-expand-label">Learn more</span>
                        <svg class="mvtd-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <?php else : ?>
                    <span></span>
                    <?php endif; ?>

                    <?php if ( ! $is_coming ) : ?>
                        <a href="<?php echo esc_url( $tool['url'] ); ?>" class="mvtd-launch-btn" target="_blank" rel="noopener">
                            Launch Tool
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    <?php else : ?>
                        <span class="mvtd-coming-label">Coming Soon</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mvtd-empty" id="mvtd-empty" style="display:none;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <p>No tools in this category yet.</p>
        </div>

        <div class="mvtd-footer">
            <p>Built by <a href="https://www.mediavines.com" target="_blank" rel="noopener">Media Vines Corp</a> &nbsp;·&nbsp; Tools for local service businesses</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
