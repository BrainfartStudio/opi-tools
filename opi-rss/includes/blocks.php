<?php
// includes/blocks.php

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'opirss_register_blocks' );

function opirss_register_blocks(): void {
    wp_register_script(
        'opirss-recent-items-editor',
        OPI_RSS_URL . 'assets/blocks/recent-items-editor.js',
        [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor' ],
        OPI_RSS_VERSION,
        true
    );

    register_block_type( 'opi-rss/recent-items', [
        'editor_script'   => 'opirss-recent-items-editor',
        'render_callback' => 'opirss_render_recent_items',
        'attributes'      => [
            'limit' => [
                'type'    => 'number',
                'default' => 20,
            ],
        ],
    ] );

    wp_register_script(
        'opirss-active-sources-editor',
        OPI_RSS_URL . 'assets/blocks/active-sources-editor.js',
        [ 'wp-blocks', 'wp-element', 'wp-block-editor' ],
        OPI_RSS_VERSION,
        true
    );

    register_block_type( 'opi-rss/active-sources', [
        'editor_script'   => 'opirss-active-sources-editor',
        'render_callback' => 'opirss_render_active_sources',
    ] );
}

function opirss_render_recent_items( array $attributes ): string {
    $limit = isset( $attributes['limit'] ) ? intval( $attributes['limit'] ) : 20;
    $items = OPI_RSS_DB::get_recent_items( $limit );

    wp_enqueue_script(
        'opirss-recent-items',
        OPI_RSS_URL . 'assets/recent-items.js',
        [],
        OPI_RSS_VERSION,
        true
    );

    ob_start();
    ?>
    <div class="rss-agg-recent-items">
        <?php if ( empty( $items ) ) : ?>
            <p><?php _e( 'No items found.', 'opi-rss' ); ?></p>
        <?php else : ?>
            <ul style="padding:0;">
                <?php foreach ( $items as $item ) : ?>
                    <li class="wpra-item feed-item" style="margin-bottom:20px;">
                        <a href="<?php echo esc_url( $item->link ); ?>" target="_blank" rel="nofollow">
                            <?php echo esc_html( stripslashes( $item->title ) ); ?>
                        </a>
                        <div class="wprss-feed-meta" style="font-size:0.9em;color:#666;margin-top:5px;">
                            <span class="feed-source">
                                <?php _e( 'Source:', 'opi-rss' ); ?>
                                <a href="<?php echo esc_url( $item->feed_url ); ?>" target="_blank" rel="nofollow">
                                    <?php echo esc_html( stripslashes( $item->feed_name ) ); ?>
                                </a>
                            </span>
                            <span class="time-ago">
                                | <?php printf( __( 'Published %s', 'opi-rss' ), OPI_RSS::time_diff( $item->pub_date ) ); ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function opirss_render_active_sources( array $attributes ): string {
    $sources = OPI_RSS_DB::get_active_sources();

    wp_enqueue_script(
        'opirss-active-sources',
        OPI_RSS_URL . 'assets/active-sources.js',
        [],
        OPI_RSS_VERSION,
        true
    );

    ob_start();
    ?>
    <div class="rss-agg-active-sources" data-wpra-template="sources">
        <?php if ( empty( $sources ) ) : ?>
            <p><?php _e( 'No active sources found.', 'opi-rss' ); ?></p>
        <?php else : ?>
            <ul>
                <?php foreach ( $sources as $source ) : ?>
                    <li class="wpra-item" style="margin-bottom:5px;">
                        <span class="feed-source">
                            <a href="<?php echo esc_url( $source->url ); ?>" target="_blank">
                                <?php echo esc_html( $source->name ); ?>
                            </a>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}