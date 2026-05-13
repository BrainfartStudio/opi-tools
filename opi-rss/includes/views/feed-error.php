<?php
// includes/views/feed-error.php

defined( 'ABSPATH' ) || exit;

OPI_RSS::maybe_render_notice();

$feeds = OPI_RSS_DB::get_feeds_by_statuses( [ OPI_RSS_DB::STATUS_ERROR ] );
?>
<div class="wrap">
    <h1><?php _e( 'RSS Aggregator', 'opi-rss' ); ?></h1>

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-rss&view=list' ) ); ?>" class="button">
            &larr; <?php _e( 'Active Feeds', 'opi-rss' ); ?>
        </a>
    </p>

    <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-rss&view=inactive' ) ); ?>"
           class="nav-tab">
            <?php _e( 'Inactive', 'opi-rss' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-rss&view=error' ) ); ?>"
           class="nav-tab nav-tab-active">
            <?php _e( 'Errors', 'opi-rss' ); ?>
        </a>
    </nav>

    <div class="opi-card" style="padding:0;">
        <table class="wp-list-table widefat fixed striped" style="border:none;">
            <thead>
                <tr>
                    <th style="width:180px;"><?php _e( 'Name', 'opi-rss' ); ?></th>
                    <th style="width:200px;"><?php _e( 'URL', 'opi-rss' ); ?></th>
                    <th><?php _e( 'Reason', 'opi-rss' ); ?></th>
                    <th style="width:130px;"><?php _e( 'Last Fetch', 'opi-rss' ); ?></th>
                    <th style="width:220px;"><?php _e( 'Actions', 'opi-rss' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $feeds ) ) : ?>
                    <tr>
                        <td colspan="5"><?php _e( 'No feeds in an error state.', 'opi-rss' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $feeds as $feed ) : ?>
                        <tr>
                            <td><?php echo esc_html( stripslashes( $feed->name ) ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( $feed->url ); ?>" target="_blank">
                                    <?php echo esc_html( $feed->url ); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ( ! empty( $feed->last_error ) ) : ?>
                                    <span style="color:#8a0000;font-size:12px;">
                                        <?php echo esc_html( $feed->last_error ); ?>
                                    </span>
                                <?php else : ?>
                                    <em style="color:#646970;"><?php _e( 'Unknown', 'opi-rss' ); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $feed->last_fetch
                                    ? esc_html( OPI_RSS::time_diff( $feed->last_fetch ) )
                                    : __( 'Never', 'opi-rss' ); ?>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'opirss_nonce' ); ?>
                                    <input type="hidden" name="opirss_action" value="fetch_now">
                                    <input type="hidden" name="_referer_view" value="error">
                                    <input type="hidden" name="id" value="<?php echo absint( $feed->id ); ?>">
                                    <button type="submit" class="button button-small button-primary">
                                        <?php _e( 'Retry Fetch', 'opi-rss' ); ?>
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