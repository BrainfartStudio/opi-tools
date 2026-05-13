<?php
// includes/views/feed-settings.php

defined( 'ABSPATH' ) || exit;

OPI_RSS::maybe_render_notice();

$settings = OPI_RSS_Settings::get();
$intervals = OPI_RSS_Settings::get_interval_options();
?>
<div class="wrap">
    <h1><?php _e( 'RSS Aggregator', 'opi-rss' ); ?></h1>

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-rss&view=list' ) ); ?>" class="button">
            &larr; <?php _e( 'Back to Feeds', 'opi-rss' ); ?>
        </a>
    </p>

    <form method="post">
        <?php wp_nonce_field( 'opirss_nonce' ); ?>
        <input type="hidden" name="opirss_action" value="save_settings">

        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Feed Defaults', 'opi-rss' ); ?></h2>

            <div class="opi-form-row">
                <label for="opirss-default-item-limit"><?php _e( 'Default Item Limit', 'opi-rss' ); ?></label>
                <div class="opi-form-control">
                    <input type="number"
                           id="opirss-default-item-limit"
                           name="opirss_settings[default_item_limit]"
                           value="<?php echo absint( $settings['default_item_limit'] ); ?>"
                           min="1" max="10"
                           style="width:70px;">
                    <p class="description"><?php _e( 'Pre-fills the item limit when adding a new feed (1–10).', 'opi-rss' ); ?></p>
                </div>
            </div>
        </div>

        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Auto-Deactivation', 'opi-rss' ); ?></h2>

            <div class="opi-form-row">
                <label for="opirss-auto-deactivate"><?php _e( 'Deactivate After', 'opi-rss' ); ?></label>
                <div class="opi-form-control">
                    <input type="number"
                           id="opirss-auto-deactivate"
                           name="opirss_settings[auto_deactivate_days]"
                           value="<?php echo absint( $settings['auto_deactivate_days'] ); ?>"
                           min="0"
                           style="width:70px;">
                    <?php _e( 'days without a new post', 'opi-rss' ); ?>
                    <p class="description"><?php _e( 'Feeds with no new posts for this many days are automatically moved to Inactive. Set to 0 to disable.', 'opi-rss' ); ?></p>
                </div>
            </div>
        </div>

        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Cron Scheduling', 'opi-rss' ); ?></h2>

            <div class="opi-form-row">
                <label for="opirss-cron-interval"><?php _e( 'Fetch Interval', 'opi-rss' ); ?></label>
                <div class="opi-form-control">
                    <select id="opirss-cron-interval" name="opirss_settings[cron_interval]">
                        <?php foreach ( $intervals as $key => $opt ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>"
                                <?php selected( $settings['cron_interval'], $key ); ?>>
                                <?php echo esc_html( $opt['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e( 'How often the cron job runs to fetch new items. Changes take effect on the next scheduled run.', 'opi-rss' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Fetch Inactive Feeds', 'opi-rss' ); ?></label>
                <div class="opi-form-control">
                    <label>
                        <input type="checkbox"
                               name="opirss_settings[cron_inactive]"
                               value="1"
                               <?php checked( $settings['cron_inactive'] ); ?>>
                        <?php _e( 'Run cron on inactive feeds', 'opi-rss' ); ?>
                    </label>
                    <p class="description"><?php _e( 'If checked, the cron job will also attempt to fetch feeds marked as Inactive. Feeds in Error state are always retried by the cron regardless of this setting.', 'opi-rss' ); ?></p>
                </div>
            </div>
        </div>

        <?php submit_button( __( 'Save Settings', 'opi-rss' ), 'primary', 'opirss_save' ); ?>
    </form>
</div>