<?php
// includes/views/settings-page.php

defined( 'ABSPATH' ) || exit;

$settings = OPI_Bluesky_Settings::get();
$message  = '';

if ( isset( $_POST['opibluesky_save'] ) && check_admin_referer( 'opibluesky_settings_action' ) ) {
    $raw     = $_POST['opibluesky_settings'] ?? [];
    $saved   = OPI_Bluesky_Settings::sanitize( $raw );
    OPI_Bluesky_Settings::update( $saved );
    $settings = OPI_Bluesky_Settings::get();

    // Re-authenticate with new credentials.
    OPI_Bluesky_Auth::clear_session();
    $auth_result = OPI_Bluesky_Auth::authenticate(
        $settings['identifier'],
        $settings['app_password']
    );

    if ( is_wp_error( $auth_result ) ) {
        $message = '<div class="notice notice-error"><p>'
            . __( 'Settings saved, but connection failed: ', 'opi-bluesky' )
            . esc_html( $auth_result->get_error_message() )
            . '</p></div>';
    } else {
        $session = OPI_Bluesky_Auth::get_session();
        $message = '<div class="notice notice-success"><p>'
            . sprintf(
                __( 'Connected as <strong>@%s</strong>.', 'opi-bluesky' ),
                esc_html( $session['handle'] ?? $settings['identifier'] )
            )
            . '</p></div>';
    }
}

$session       = OPI_Bluesky_Auth::get_session();
$is_connected  = OPI_Bluesky_Auth::is_authenticated();
?>
<div class="wrap">
    <h1><?php _e( 'Bluesky — Settings', 'opi-bluesky' ); ?></h1>

    <?php echo $message; ?>

    <?php if ( $is_connected && empty( $message ) ) : ?>
        <div class="notice notice-success">
            <p><?php printf(
                __( 'Connected as <strong>@%s</strong>.', 'opi-bluesky' ),
                esc_html( $session['handle'] ?? '' )
            ); ?></p>
        </div>
    <?php elseif ( ! $is_connected && empty( $message ) ) : ?>
        <div class="notice notice-warning">
            <p><?php _e( 'Not connected. Enter your credentials and save.', 'opi-bluesky' ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field( 'opibluesky_settings_action' ); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="opibluesky_identifier"><?php _e( 'Bluesky Handle or Email', 'opi-bluesky' ); ?></label>
                </th>
                <td>
                    <input
                        type="text"
                        id="opibluesky_identifier"
                        name="opibluesky_settings[identifier]"
                        value="<?php echo esc_attr( $settings['identifier'] ); ?>"
                        class="regular-text"
                        placeholder="you.bsky.social"
                        autocomplete="off"
                    >
                    <p class="description"><?php _e( 'Your Bluesky handle (e.g. you.bsky.social) or the email on your account.', 'opi-bluesky' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="opibluesky_app_password"><?php _e( 'App Password', 'opi-bluesky' ); ?></label>
                </th>
                <td>
                    <input
                        type="password"
                        id="opibluesky_app_password"
                        name="opibluesky_settings[app_password]"
                        value="<?php echo esc_attr( $settings['app_password'] ); ?>"
                        class="regular-text"
                        autocomplete="new-password"
                    >
                    <p class="description">
                        <?php _e( 'Generate an app password at ', 'opi-bluesky' ); ?>
                        <a href="https://bsky.app/settings/app-passwords" target="_blank">bsky.app/settings/app-passwords</a>.
                        <?php _e( 'Do not use your main account password.', 'opi-bluesky' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e( 'Auto-Post on Publish', 'opi-bluesky' ); ?></th>
                <td>
                    <label>
                        <input
                            type="checkbox"
                            name="opibluesky_settings[auto_post_on_publish]"
                            value="1"
                            <?php checked( $settings['auto_post_on_publish'] ); ?>
                        >
                        <?php _e( 'Automatically post to Bluesky when a WordPress post is published.', 'opi-bluesky' ); ?>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Save & Connect', 'opi-bluesky' ), 'primary', 'opibluesky_save' ); ?>
    </form>
</div>