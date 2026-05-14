<?php
// includes/views/settings-page.php

defined( 'ABSPATH' ) || exit;

$settings = OPI_Bluesky_Settings::get();
$message  = '';

if ( isset( $_POST['opibluesky_save'] ) && check_admin_referer( 'opibluesky_settings_action' ) ) {
    $raw   = $_POST['opibluesky_settings'] ?? [];
    $saved = OPI_Bluesky_Settings::sanitize( $raw );
    OPI_Bluesky_Settings::update( $saved );
    $settings = OPI_Bluesky_Settings::get();

    // Re-authenticate with new credentials.
    OPI_Bluesky_Auth::clear_session();
    $auth_result = OPI_Bluesky_Auth::authenticate(
        $settings['identifier'],
        OPI_Bluesky_Settings::get_app_password()
    );

    if ( is_wp_error( $auth_result ) ) {
        $message = OPI_Tools::notice( 'error',
            __( 'Settings saved, but connection failed: ', 'opi-bluesky' )
            . esc_html( $auth_result->get_error_message() )
        );
    } else {
        $session = OPI_Bluesky_Auth::get_session();
        $message = OPI_Tools::notice( 'success', sprintf(
            __( 'Settings saved. Connected as <strong>@%s</strong>.', 'opi-bluesky' ),
            esc_html( $session['handle'] ?? $settings['identifier'] )
        ) );
    }
}

$session      = OPI_Bluesky_Auth::get_session();
$is_connected = OPI_Bluesky_Auth::is_authenticated();
?>
<div class="wrap">
    <h1><?php _e( 'Bluesky', 'opi-bluesky' ); ?></h1>

    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-bluesky' ) ); ?>"
           class="nav-tab"><?php _e( 'Scheduled Posts', 'opi-bluesky' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-bluesky&view=categories' ) ); ?>"
           class="nav-tab"><?php _e( 'Categories', 'opi-bluesky' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-bluesky&view=settings' ) ); ?>"
           class="nav-tab nav-tab-active"><?php _e( 'Settings', 'opi-bluesky' ); ?></a>
    </nav>

    <?php echo $message; ?>

    <?php if ( $is_connected && empty( $message ) ) : ?>
        <?php echo OPI_Tools::notice( 'success', sprintf(
            __( 'Connected as <strong>@%s</strong>.', 'opi-bluesky' ),
            esc_html( $session['handle'] ?? '' )
        ) ); ?>
    <?php elseif ( ! $is_connected && empty( $message ) ) : ?>
        <?php echo OPI_Tools::notice( 'warning', __( 'Not connected. Enter your credentials and save.', 'opi-bluesky' ) ); ?>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field( 'opibluesky_settings_action' ); ?>

        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Account', 'opi-bluesky' ); ?></h2>

            <div class="opi-form-row">
                <label for="opibluesky_identifier"><?php _e( 'Handle or Email', 'opi-bluesky' ); ?></label>
                <div class="opi-form-control">
                    <input type="text" id="opibluesky_identifier"
                           name="opibluesky_settings[identifier]"
                           value="<?php echo esc_attr( $settings['identifier'] ); ?>"
                           class="regular-text" placeholder="you.bsky.social" autocomplete="off">
                    <p class="description"><?php _e( 'Your Bluesky handle or account email.', 'opi-bluesky' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opibluesky_app_password"><?php _e( 'App Password', 'opi-bluesky' ); ?></label>
                <div class="opi-form-control">
                    <input type="password" id="opibluesky_app_password"
                           name="opibluesky_settings[app_password]"
                           value="" class="regular-text" autocomplete="new-password"
                           placeholder="<?php echo $settings['app_password'] ? esc_attr__( '(stored — leave blank to keep)', 'opi-bluesky' ) : ''; ?>">
                    <p class="description">
                        <?php _e( 'Generate at ', 'opi-bluesky' ); ?>
                        <a href="https://bsky.app/settings/app-passwords" target="_blank">bsky.app/settings/app-passwords</a>.
                    </p>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Auto-Post on Publish', 'opi-bluesky' ); ?></label>
                <div class="opi-form-control">
                    <label>
                        <input type="checkbox" name="opibluesky_settings[auto_post_on_publish]" value="1"
                               <?php checked( $settings['auto_post_on_publish'] ); ?>>
                        <?php _e( 'Automatically post to Bluesky when a WordPress post is published.', 'opi-bluesky' ); ?>
                    </label>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Connection', 'opi-bluesky' ); ?></label>
                <div class="opi-form-control">
                    <button type="button" id="opibluesky-test-connection" class="button">
                        <?php _e( 'Test Connection', 'opi-bluesky' ); ?>
                    </button>
                    <span id="opibluesky-test-result" style="margin-left:10px;"></span>
                </div>
            </div>
        </div>

        <?php submit_button( __( 'Save Settings', 'opi-bluesky' ), 'primary', 'opibluesky_save' ); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#opibluesky-test-connection').on('click', function() {
        var $btn    = $(this);
        var $result = $('#opibluesky-test-result');
        $btn.prop('disabled', true).text('Testing...');
        $result.text('').css('color', '');

        $.post(opiBlueskyAdmin.ajaxUrl, {
            action: 'opibluesky_test_connection',
            nonce:  opiBlueskyAdmin.nonce,
        }, function(response) {
            if (response.success) {
                $result.css('color', '#2271b1').text('Connected as @' + response.data.handle);
            } else {
                $result.css('color', '#d63638').text('Failed: ' + response.data);
            }
        }).fail(function() {
            $result.css('color', '#d63638').text('Server error.');
        }).always(function() {
            $btn.prop('disabled', false).text('Test Connection');
        });
    });
});
</script>