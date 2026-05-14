<?php
// includes/views/settings-page.php

defined( 'ABSPATH' ) || exit;

$settings = OPI_Discord_Settings::get();
$message  = '';

// Handle test send
if ( isset( $_POST['opi_discord_test'] ) && check_admin_referer( 'opi_discord_action' ) ) {
    $posts = get_posts( [ 'numberposts' => 1, 'post_status' => 'publish' ] );
    if ( ! empty( $posts ) ) {
        $result  = OPI_Discord::send( $posts[0] );
        $message = OPI_Tools::notice(
            $result ? 'success' : 'error',
            $result ? 'Sent successfully.' : 'Send failed. Check your webhook URL.'
        );
    } else {
        $message = OPI_Tools::notice( 'warning', 'No published posts found.' );
    }
}

// Handle bulk send queue
if ( isset( $_POST['opi_discord_bulk'] ) && check_admin_referer( 'opi_discord_action' ) ) {
    $queued  = OPI_Discord::queue_all_posts();
    $message = OPI_Tools::notice(
        $queued > 0 ? 'success' : 'warning',
        $queued > 0
            ? 'Queued ' . $queued . ' posts to send. They will post every 2 seconds.'
            : 'No published posts found to queue.'
    );
}

// Handle settings save
if ( isset( $_POST['opi_discord_save'] ) && check_admin_referer( 'opi_discord_action' ) ) {
    $saved   = OPI_Discord_Settings::sanitize( $_POST['opidiscord_settings'] ?? [] );
    OPI_Discord_Settings::update( $saved );
    $settings = OPI_Discord_Settings::get();
    $message  = OPI_Tools::notice( 'success', 'Settings saved.' );
}

$webhook_url = OPI_Discord_Settings::get_webhook_url();
?>
<div class="wrap">
    <h1><?php _e( 'OPI Discord', 'opi-discord' ); ?></h1>

    <?php echo $message; ?>

    <form method="post">
        <?php wp_nonce_field( 'opi_discord_action' ); ?>

        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Webhook', 'opi-discord' ); ?></h2>

            <div class="opi-form-row">
                <label for="opidiscord-webhook"><?php _e( 'Webhook URL', 'opi-discord' ); ?></label>
                <div class="opi-form-control">
                    <input type="password"
                           id="opidiscord-webhook"
                           name="opidiscord_settings[webhook_url]"
                           class="regular-text"
                           value="<?php echo esc_attr( $webhook_url ); ?>">
                    <p class="description"><?php _e( 'Paste your Discord channel webhook URL here.', 'opi-discord' ); ?></p>
                </div>
            </div>
        </div>

        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Embed Options', 'opi-discord' ); ?></h2>

            <div class="opi-form-row">
                <label for="opidiscord-color"><?php _e( 'Embed Color', 'opi-discord' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color"
                               id="opidiscord-color"
                               name="opidiscord_settings[embed_color]"
                               value="<?php echo esc_attr( $settings['embed_color'] ); ?>">
                        <input type="text"
                               value="<?php echo esc_attr( $settings['embed_color'] ); ?>"
                               maxlength="7"
                               placeholder="#7289DA">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Show Excerpt', 'opi-discord' ); ?></label>
                <div class="opi-form-control">
                    <label>
                        <input type="checkbox"
                               name="opidiscord_settings[show_excerpt]"
                               value="1"
                               <?php checked( $settings['show_excerpt'] ); ?>>
                        <?php _e( 'Include post excerpt in Discord embed', 'opi-discord' ); ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Manual Actions', 'opi-discord' ); ?></h2>

            <div class="opi-form-row">
                <label><?php _e( 'Send Latest Post', 'opi-discord' ); ?></label>
                <div class="opi-form-control">
                    <button type="submit" name="opi_discord_test" class="button"
                            onclick="return confirm('<?php esc_attr_e( 'Send the latest published post to Discord?', 'opi-discord' ); ?>')">
                        <?php _e( 'Send Latest', 'opi-discord' ); ?>
                    </button>
                    <p class="description"><?php _e( 'Sends the most recently published post to your Discord channel.', 'opi-discord' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Send All Posts', 'opi-discord' ); ?></label>
                <div class="opi-form-control">
                    <button type="submit" name="opi_discord_bulk" class="button"
                            onclick="return confirm('<?php esc_attr_e( 'This will queue ALL published posts to send to Discord, oldest first. Continue?', 'opi-discord' ); ?>')">
                        <?php _e( 'Send All', 'opi-discord' ); ?>
                    </button>
                    <p class="description"><?php _e( 'Queues every published post and sends them oldest-first with a 2-second delay between each to avoid Discord rate limits.', 'opi-discord' ); ?></p>
                </div>
            </div>
        </div>

        <?php submit_button( __( 'Save Settings', 'opi-discord' ), 'primary', 'opi_discord_save' ); ?>
    </form>
</div>