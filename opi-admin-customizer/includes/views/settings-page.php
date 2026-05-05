<?php
// includes/views/settings-page.php

defined( 'ABSPATH' ) || exit;

$settings = OPI_Admin_Settings::get();
$message  = '';

if ( isset( $_POST['opiadmin_save'] ) && check_admin_referer( 'opiadmin_action' ) ) {
    OPI_Admin_Settings::save( $_POST );
    $settings = OPI_Admin_Settings::get();
    $message  = '<div class="notice notice-success"><p>Settings saved.</p></div>';
}
?>

<div class="wrap">
    <h1>OPI Admin Customizer</h1>

    <?php echo $message; ?>

    <form method="post">
        <?php wp_nonce_field( 'opiadmin_action' ); ?>

        <table class="form-table" role="presentation">

            <tr>
                <th scope="row"><label for="sidebar_bg">Sidebar Background</label></th>
                <td><input type="color" id="sidebar_bg" name="sidebar_bg"
                           value="<?php echo esc_attr( $settings['sidebar_bg'] ); ?>"></td>
            </tr>

            <tr>
                <th scope="row"><label for="sidebar_text">Sidebar Text / Icons</label></th>
                <td><input type="color" id="sidebar_text" name="sidebar_text"
                           value="<?php echo esc_attr( $settings['sidebar_text'] ); ?>"></td>
            </tr>

            <tr>
                <th scope="row"><label for="sidebar_highlight">Sidebar Highlight</label></th>
                <td><input type="color" id="sidebar_highlight" name="sidebar_highlight"
                           value="<?php echo esc_attr( $settings['sidebar_highlight'] ); ?>"></td>
            </tr>

            <tr>
                <th scope="row"><label for="topbar_bg">Top Bar Background</label></th>
                <td><input type="color" id="topbar_bg" name="topbar_bg"
                           value="<?php echo esc_attr( $settings['topbar_bg'] ); ?>"></td>
            </tr>

            <tr>
                <th scope="row"><label for="topbar_text">Top Bar Text / Icons</label></th>
                <td><input type="color" id="topbar_text" name="topbar_text"
                           value="<?php echo esc_attr( $settings['topbar_text'] ); ?>"></td>
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
            <button type="submit" name="opiadmin_save" class="button button-primary">Save Settings</button>
        </p>

    </form>
</div>