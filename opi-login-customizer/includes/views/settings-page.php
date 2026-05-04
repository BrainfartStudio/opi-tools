<?php
// includes/views/settings-page.php

defined( 'ABSPATH' ) || exit;

$settings = OPI_Login_Settings::get();
$message  = '';

if ( isset( $_POST['opilogin_save'] ) && check_admin_referer( 'opilogin_action' ) ) {
    OPI_Login_Settings::save( $_POST );
    $settings = OPI_Login_Settings::get();
    $message  = '<div class="notice notice-success"><p>Settings saved.</p></div>';
}
?>

<div class="wrap">
    <h1>OPI Login Customizer</h1>

    <?php echo $message; ?>

    <form method="post">
        <?php wp_nonce_field( 'opilogin_action' ); ?>

        <table class="form-table" role="presentation">

            <tr>
                <th scope="row"><label for="logo_id">Logo</label></th>
                <td>
                    <?php if ( $settings['logo_id'] ) : ?>
                        <img src="<?php echo esc_url( wp_get_attachment_image_url( $settings['logo_id'], 'medium' ) ); ?>"
                             style="max-height:80px;display:block;margin-bottom:8px;">
                    <?php endif; ?>
                    <input type="hidden" id="logo_id" name="logo_id"
                           value="<?php echo esc_attr( $settings['logo_id'] ); ?>">
                    <button type="button" class="button" id="opilogin-logo-pick">Choose Logo</button>
                    <?php if ( $settings['logo_id'] ) : ?>
                        <button type="button" class="button" id="opilogin-logo-remove">Remove</button>
                    <?php endif; ?>
                    <p class="description">Replaces the WordPress logo on the login page.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="header_text">Header Text</label></th>
                <td>
                    <input type="text" id="header_text" name="header_text"
                           class="regular-text"
                           value="<?php echo esc_attr( $settings['header_text'] ); ?>">
                    <p class="description">Shown above the login form if no logo is set, or alongside it.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="bg_color">Background Color</label></th>
                <td>
                    <input type="color" id="bg_color" name="bg_color"
                           value="<?php echo esc_attr( $settings['bg_color'] ); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="bg_image_id">Background Image</label></th>
                <td>
                    <?php if ( $settings['bg_image_id'] ) : ?>
                        <img src="<?php echo esc_url( wp_get_attachment_image_url( $settings['bg_image_id'], 'medium' ) ); ?>"
                             style="max-height:80px;display:block;margin-bottom:8px;">
                    <?php endif; ?>
                    <input type="hidden" id="bg_image_id" name="bg_image_id"
                           value="<?php echo esc_attr( $settings['bg_image_id'] ); ?>">
                    <button type="button" class="button" id="opilogin-bg-pick">Choose Image</button>
                    <?php if ( $settings['bg_image_id'] ) : ?>
                        <button type="button" class="button" id="opilogin-bg-remove">Remove</button>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="form_bg_color">Form Background</label></th>
                <td>
                    <input type="color" id="form_bg_color" name="form_bg_color"
                           value="<?php echo esc_attr( $settings['form_bg_color'] ); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="form_radius">Form Border Radius (px)</label></th>
                <td>
                    <input type="number" id="form_radius" name="form_radius"
                           min="0" max="40" step="1"
                           value="<?php echo esc_attr( $settings['form_radius'] ); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row">Form Shadow</th>
                <td>
                    <label>
                        <input type="checkbox" name="form_shadow" value="1"
                            <?php checked( $settings['form_shadow'] ); ?>>
                        Show box shadow on login form
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="button_color">Button Color</label></th>
                <td>
                    <input type="color" id="button_color" name="button_color"
                           value="<?php echo esc_attr( $settings['button_color'] ); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="button_text">Button Text Color</label></th>
                <td>
                    <input type="color" id="button_text" name="button_text"
                           value="<?php echo esc_attr( $settings['button_text'] ); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="font_family">Font Family</label></th>
                <td>
                    <input type="text" id="font_family" name="font_family"
                           class="regular-text"
                           value="<?php echo esc_attr( $settings['font_family'] ); ?>"
                           placeholder="inherit">
                    <p class="description">e.g. <code>'Inter', sans-serif</code></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="custom_css">Custom CSS</label></th>
                <td>
                    <textarea id="custom_css" name="custom_css" rows="8"
                              class="large-text code"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
                </td>
            </tr>

        </table>

        <p class="submit">
            <button type="submit" name="opilogin_save" class="button button-primary">Save Settings</button>
        </p>

    </form>
</div>