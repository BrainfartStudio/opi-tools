<?php
// includes/views/feed-list.php

defined( 'ABSPATH' ) || exit;

OPI_RSS::maybe_render_notice();

$allowed_orderby = [ 'name', 'last_fetch', 'latest_article_date' ];
$orderby         = in_array( $_GET['orderby'] ?? '', $allowed_orderby, true )
                   ? $_GET['orderby']
                   : 'latest_article_date';
$order           = ( strtoupper( $_GET['order'] ?? '' ) === 'ASC' ) ? 'ASC' : 'DESC';
$opposite        = $order === 'ASC' ? 'DESC' : 'ASC';

$feeds = OPI_RSS_DB::get_feeds_with_latest( OPI_RSS_DB::STATUS_ACTIVE, $orderby, $order );

/**
 * Build a sortable column header link.
 */
$col_link = function( string $col, string $label ) use ( $orderby, $order, $opposite ): string {
    $is_active  = $orderby === $col;
    $next_order = $is_active ? $opposite : 'DESC';
    $arrow      = $is_active ? ( $order === 'ASC' ? ' ▲' : ' ▼' ) : '';
    $url        = admin_url( 'admin.php?' . http_build_query( [
        'page'    => 'opi-rss',
        'view'    => 'list',
        'orderby' => $col,
        'order'   => $next_order,
    ] ) );
    $style = $is_active ? 'font-weight:700;' : '';
    return sprintf(
        '<a href="%s" style="%s">%s%s</a>',
        esc_url( $url ),
        esc_attr( $style ),
        esc_html( $label ),
        $arrow
    );
};
?>
<div class="wrap">
    <h1><?php _e( 'RSS Aggregator', 'opi-rss' ); ?></h1>

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-rss&view=add' ) ); ?>" class="button button-primary">
            <?php _e( 'Add New Feed', 'opi-rss' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-rss&view=inactive' ) ); ?>" class="button" style="margin-left:8px;">
            <?php _e( 'Inactive / Error Feeds', 'opi-rss' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-rss&view=settings' ) ); ?>" class="button" style="margin-left:8px;">
            <?php _e( 'Settings', 'opi-rss' ); ?>
        </a>
    </p>

    <div class="opi-card" style="padding:0;">
        <table class="wp-list-table widefat fixed striped" style="border:none;">
            <thead>
                <tr>
                    <th style="width:90px;"><?php _e( 'Status', 'opi-rss' ); ?></th>
                    <th style="width:150px;"><?php echo $col_link( 'name', __( 'Name', 'opi-rss' ) ); ?></th>
                    <th><?php _e( 'Latest Article', 'opi-rss' ); ?></th>
                    <th style="width:110px;"><?php echo $col_link( 'latest_article_date', __( 'Last Post', 'opi-rss' ) ); ?></th>
                    <th style="width:110px;"><?php echo $col_link( 'last_fetch', __( 'Last Fetch', 'opi-rss' ) ); ?></th>
                    <th style="width:90px;"><?php _e( 'Next Fetch', 'opi-rss' ); ?></th>
                    <th style="width:200px;"><?php _e( 'Actions', 'opi-rss' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $feeds ) ) : ?>
                    <tr>
                        <td colspan="7">
                            <?php _e( 'No active feeds. ', 'opi-rss' ); ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-rss&view=add' ) ); ?>">
                                <?php _e( 'Add one.', 'opi-rss' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $feeds as $feed ) : ?>
                        <?php
                        $next_fetch = '';
                        if ( $feed->next_fetch ) {
                            $diff = strtotime( $feed->next_fetch ) - current_time( 'timestamp' );
                            if ( $diff > 0 ) {
                                $minutes    = ceil( $diff / 60 );
                                $next_fetch = $minutes . ' ' . _n( 'min', 'mins', $minutes, 'opi-rss' );
                            } else {
                                $next_fetch = __( 'Soon', 'opi-rss' );
                            }
                        } else {
                            $next_fetch = __( 'Soon', 'opi-rss' );
                        }
                        ?>
                        <tr>
                            <td>
                                <span class="opi-status-badge opi-status-badge--ok">
                                    <?php _e( 'Active', 'opi-rss' ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( stripslashes( $feed->name ) ); ?></td>
                            <td>
                                <?php if ( $feed->latest_article ) : ?>
                                    <a href="<?php echo esc_url( $feed->latest_article_link ); ?>" target="_blank">
                                        <?php
                                        $title = stripslashes( $feed->latest_article );
                                        echo esc_html( strlen( $title ) > 60 ? substr( $title, 0, 60 ) . '…' : $title );
                                        ?>
                                    </a>
                                <?php else : ?>
                                    <em><?php _e( 'No articles yet', 'opi-rss' ); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $feed->latest_article_date
                                    ? esc_html( OPI_RSS::time_diff( $feed->latest_article_date ) )
                                    : '—'; ?>
                            </td>
                            <td>
                                <?php echo $feed->last_fetch
                                    ? esc_html( OPI_RSS::time_diff( $feed->last_fetch ) )
                                    : __( 'Never', 'opi-rss' ); ?>
                            </td>
                            <td><?php echo esc_html( $next_fetch ); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'opirss_nonce' ); ?>
                                    <input type="hidden" name="opirss_action" value="fetch_now">
                                    <input type="hidden" name="id" value="<?php echo absint( $feed->id ); ?>">
                                    <button type="submit" class="button button-small">
                                        <?php _e( 'Fetch', 'opi-rss' ); ?>
                                    </button>
                                </form>

                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-rss&view=edit&id=' . absint( $feed->id ) ) ); ?>"
                                   class="button button-small">
                                    <?php _e( 'Edit', 'opi-rss' ); ?>
                                </a>

                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'opirss_nonce' ); ?>
                                    <input type="hidden" name="opirss_action" value="deactivate">
                                    <input type="hidden" name="id" value="<?php echo absint( $feed->id ); ?>">
                                    <button type="submit" class="button button-small">
                                        <?php _e( 'Deactivate', 'opi-rss' ); ?>
                                    </button>
                                </form>

                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'opirss_nonce' ); ?>
                                    <input type="hidden" name="opirss_action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo absint( $feed->id ); ?>">
                                    <button type="submit" class="button button-small"
                                            style="color:#b32d2e;"
                                            onclick="return confirm('<?php esc_attr_e( 'Delete this feed and all its items?', 'opi-rss' ); ?>');">
                                        <?php _e( 'Delete', 'opi-rss' ); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>