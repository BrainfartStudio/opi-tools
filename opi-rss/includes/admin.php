<?php
// includes/admin.php

defined( 'ABSPATH' ) || exit;

function opirss_render_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // Handle form submissions
    if ( isset( $_POST['opirss_action'] ) ) {
        check_admin_referer( 'opirss_nonce' );

        if ( $_POST['opirss_action'] === 'add' ) {
            $feed_id = opirss_add_feed( $_POST['name'], $_POST['url'], $_POST['limit'] );
            if ( $feed_id ) {
                $feed = opirss_get_feed( $feed_id );
                opirss_fetch_feed( $feed );
                echo '<div class="notice notice-success"><p>Feed added and fetched successfully.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to add feed.</p></div>';
            }
        } elseif ( $_POST['opirss_action'] === 'edit' ) {
            opirss_update_feed( $_POST['id'], $_POST['name'], $_POST['url'], $_POST['status'] ?? 0, $_POST['limit'] );
            echo '<div class="notice notice-success"><p>Feed updated.</p></div>';
        } elseif ( $_POST['opirss_action'] === 'delete' ) {
            opirss_delete_feed( $_POST['id'] );
            echo '<div class="notice notice-success"><p>Feed deleted.</p></div>';
        } elseif ( $_POST['opirss_action'] === 'toggle_status' ) {
            opirss_toggle_status( $_POST['id'], $_POST['new_status'] );
            echo '<div class="notice notice-success"><p>Feed status updated.</p></div>';
        } elseif ( $_POST['opirss_action'] === 'fetch_now' ) {
            $feed = opirss_get_feed( $_POST['id'] );
            if ( $feed ) {
                $result = opirss_fetch_feed( $feed );
                echo $result
                    ? '<div class="notice notice-success"><p>Feed fetched successfully.</p></div>'
                    : '<div class="notice notice-error"><p>Failed to fetch feed. Check the URL.</p></div>';
            }
        }
    }

    $feeds     = opirss_get_feeds_with_latest();
    $edit_feed = isset( $_GET['edit'] ) ? opirss_get_feed( intval( $_GET['edit'] ) ) : null;
    ?>
    <div class="wrap">
        <h1>OPI RSS Aggregator</h1>

        <div style="margin: 20px 0;">
            <h2><?php echo $edit_feed ? 'Edit Feed' : 'Add New Feed'; ?></h2>
            <form method="post" style="max-width: 600px;">
                <?php wp_nonce_field( 'opirss_nonce' ); ?>
                <input type="hidden" name="opirss_action" value="<?php echo $edit_feed ? 'edit' : 'add'; ?>">
                <?php if ( $edit_feed ): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_feed->id; ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label>Name</label></th>
                        <td><input type="text" name="name" class="regular-text" value="<?php echo $edit_feed ? esc_attr( $edit_feed->name ) : ''; ?>" required></td>
                    </tr>
                    <tr>
                        <th><label>Feed URL</label></th>
                        <td><input type="url" name="url" class="regular-text" value="<?php echo $edit_feed ? esc_attr( $edit_feed->url ) : ''; ?>" required></td>
                    </tr>
                    <tr>
                        <th><label>Item Limit</label></th>
                        <td><input type="number" name="limit" value="<?php echo $edit_feed ? $edit_feed->item_limit : 1; ?>" min="1" max="10"></td>
                    </tr>
                    <?php if ( $edit_feed ): ?>
                    <tr>
                        <th><label>Status</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="status" value="1" <?php checked( $edit_feed->status, 1 ); ?>>
                                Active
                            </label>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>

                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php echo $edit_feed ? 'Update Feed' : 'Add Feed'; ?>">
                    <?php if ( $edit_feed ): ?>
                        <a href="<?php echo admin_url( 'admin.php?page=opi-rss' ); ?>" class="button">Cancel</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <h2>Feeds</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;">Status</th>
                    <th style="width: 150px;">Name</th>
                    <th>Latest Article</th>
                    <th>URL</th>
                    <th style="width: 50px;">Limit</th>
                    <th style="width: 100px;">Last Update</th>
                    <th style="width: 90px;">Next Update</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $feeds ) ): ?>
                    <tr><td colspan="8">No feeds added yet.</td></tr>
                <?php else: ?>
                    <?php foreach ( $feeds as $feed ): ?>
                        <tr>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field( 'opirss_nonce' ); ?>
                                    <input type="hidden" name="opirss_action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $feed->id; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo $feed->status ? 0 : 1; ?>">
                                    <button type="submit" class="button button-small">
                                        <?php if ( $feed->status ): ?>
                                            <span style="color: green;">●</span> Active
                                        <?php else: ?>
                                            <span style="color: red;">●</span> Inactive
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </td>
                            <td><?php echo esc_html( stripslashes( $feed->name ) ); ?></td>
                            <td>
                                <?php if ( $feed->latest_article ): ?>
                                    <a href="<?php echo esc_url( $feed->latest_article_link ); ?>" target="_blank" title="<?php echo esc_attr( stripslashes( $feed->latest_article ) ); ?>">
                                        <?php
                                        $article_title = stripslashes( $feed->latest_article );
                                        echo esc_html( strlen( $article_title ) > 50 ? substr( $article_title, 0, 50 ) . '...' : $article_title );
                                        ?>
                                    </a>
                                    <br>
                                    <small style="color: #666;"><?php echo opirss_time_diff( $feed->latest_article_date ); ?></small>
                                <?php else: ?>
                                    <em>No articles yet</em>
                                <?php endif; ?>
                            </td>
                            <td><a href="<?php echo esc_url( $feed->url ); ?>" target="_blank"><?php echo esc_html( $feed->url ); ?></a></td>
                            <td><?php echo $feed->item_limit; ?></td>
                            <td><?php echo $feed->last_fetch ? opirss_time_diff( $feed->last_fetch ) : 'Never'; ?></td>
                            <td>
                                <?php
                                if ( $feed->next_fetch ) {
                                    $diff = strtotime( $feed->next_fetch ) - current_time( 'timestamp' );
                                    if ( $diff > 0 ) {
                                        $minutes = ceil( $diff / 60 );
                                        echo $minutes . ' min' . ( $minutes != 1 ? 's' : '' );
                                    } else {
                                        echo 'Soon';
                                    }
                                } else {
                                    echo 'Soon';
                                }
                                ?>
                            </td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field( 'opirss_nonce' ); ?>
                                    <input type="hidden" name="opirss_action" value="fetch_now">
                                    <input type="hidden" name="id" value="<?php echo $feed->id; ?>">
                                    <button type="submit" class="button button-small">Fetch</button>
                                </form>
                                <a href="<?php echo admin_url( 'admin.php?page=opi-rss&edit=' . $feed->id ); ?>" class="button button-small">Edit</a>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field( 'opirss_nonce' ); ?>
                                    <input type="hidden" name="opirss_action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $feed->id; ?>">
                                    <button type="submit" class="button button-small" style="color: #b32d2e;" onclick="return confirm('Delete this feed?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}