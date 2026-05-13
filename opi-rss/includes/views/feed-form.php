<?php
// includes/views/feed-form.php

defined( 'ABSPATH' ) || exit;

OPI_RSS::maybe_render_notice();

$edit_id   = absint( $_GET['id'] ?? 0 );
$edit_feed = $edit_id ? OPI_RSS_DB::get_feed( $edit_id ) : null;
$is_edit   = (bool) $edit_feed;
?>
<div class="wrap">
    <h1><?php echo $is_edit ? __( 'Edit Feed', 'opi-rss' ) : __( 'Add New Feed', 'opi-rss' ); ?></h1>

    <?php OPI_RSS::render_nav( $is_edit ? '' : 'add' ); ?>

    <form method="post">
        <?php wp_nonce_field( 'opirss_nonce' ); ?>
        <input type="hidden" name="opirss_action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">
        <?php if ( $is_edit ) : ?>
            <input type="hidden" name="id" value="<?php echo absint( $edit_feed->id ); ?>">
        <?php endif; ?>

        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Feed Details', 'opi-rss' ); ?></h2>

            <div class="opi-form-row">
                <label for="opirss-name"><?php _e( 'Name', 'opi-rss' ); ?></label>
                <div class="opi-form-control">
                    <input type="text"
                           id="opirss-name"
                           name="name"
                           class="regular-text"
                           value="<?php echo $is_edit ? esc_attr( stripslashes( $edit_feed->name ) ) : ''; ?>"
                           required>
                    <p class="description"><?php _e( 'A friendly label for this feed.', 'opi-rss' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opirss-url"><?php _e( 'Feed URL', 'opi-rss' ); ?></label>
                <div class="opi-form-control">
                    <input type="url"
                           id="opirss-url"
                           name="url"
                           class="regular-text"
                           value="<?php echo $is_edit ? esc_attr( $edit_feed->url ) : ''; ?>"
                           required>
                    <p class="description"><?php _e( 'The RSS or Atom feed URL.', 'opi-rss' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opirss-limit"><?php _e( 'Item Limit', 'opi-rss' ); ?></label>
                <div class="opi-form-control">
                    <input type="number"
                           id="opirss-limit"
                           name="limit"
                           value="<?php echo $is_edit ? absint( $edit_feed->item_limit ) : 1; ?>"
                           min="1"
                           max="10"
                           style="width:70px;">
                    <p class="description"><?php _e( 'Maximum items to store per fetch (1–10).', 'opi-rss' ); ?></p>
                </div>
            </div>

            <?php if ( $is_edit ) : ?>
                <div class="opi-form-row">
                    <label for="opirss-status"><?php _e( 'Status', 'opi-rss' ); ?></label>
                    <div class="opi-form-control">
                        <select id="opirss-status" name="status">
                            <option value="<?php echo OPI_RSS_DB::STATUS_ACTIVE; ?>"
                                <?php selected( $edit_feed->status, OPI_RSS_DB::STATUS_ACTIVE ); ?>>
                                <?php _e( 'Active', 'opi-rss' ); ?>
                            </option>
                            <option value="<?php echo OPI_RSS_DB::STATUS_INACTIVE; ?>"
                                <?php selected( $edit_feed->status, OPI_RSS_DB::STATUS_INACTIVE ); ?>>
                                <?php _e( 'Inactive', 'opi-rss' ); ?>
                            </option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php submit_button(
            $is_edit ? __( 'Update Feed', 'opi-rss' ) : __( 'Add Feed', 'opi-rss' ),
            'primary',
            'opirss_save'
        ); ?>
    </form>
</div>