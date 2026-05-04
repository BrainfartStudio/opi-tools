<?php
// includes/views/settings-theme.php

defined( 'ABSPATH' ) || exit;

$theme = OPI_Settings::get();
?>
<div class="wrap">
    <h1>OPI Tools — Theme Settings</h1>
    <form method="post" action="options.php">
        <?php settings_fields( 'opitools_theme_group' ); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="opi-accent">Accent Color</label></th>
                <td><input type="color" id="opi-accent"
                           name="<?php echo OPI_Settings::OPTION_KEY; ?>[accent_color]"
                           value="<?php echo esc_attr( $theme['accent_color'] ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="opi-accent-text">Accent Text Color</label></th>
                <td><input type="color" id="opi-accent-text"
                           name="<?php echo OPI_Settings::OPTION_KEY; ?>[accent_text]"
                           value="<?php echo esc_attr( $theme['accent_text'] ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="opi-surface">Surface Color</label></th>
                <td><input type="color" id="opi-surface"
                           name="<?php echo OPI_Settings::OPTION_KEY; ?>[surface_color]"
                           value="<?php echo esc_attr( $theme['surface_color'] ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="opi-surface-text">Surface Text Color</label></th>
                <td><input type="color" id="opi-surface-text"
                           name="<?php echo OPI_Settings::OPTION_KEY; ?>[surface_text]"
                           value="<?php echo esc_attr( $theme['surface_text'] ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="opi-font">Font Family</label></th>
                <td>
                    <input type="text" id="opi-font" class="regular-text"
                           name="<?php echo OPI_Settings::OPTION_KEY; ?>[font_family]"
                           value="<?php echo esc_attr( $theme['font_family'] ); ?>"
                           placeholder="inherit">
                    <p class="description">System font stack or Google Font name. e.g. <code>'Inter', sans-serif</code></p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>