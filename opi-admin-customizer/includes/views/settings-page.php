<?php
// includes/views/settings-page.php

defined( 'ABSPATH' ) || exit;

$settings = OPI_Admin_Settings::get();
$message  = '';

if ( isset( $_GET['saved'] ) ) {
    $message = OPI_Tools::notice( 'success', __( 'Settings saved.', 'opi-admin' ) );
}

if ( isset( $_GET['reset'] ) ) {
    $message = OPI_Tools::notice( 'success', __( 'Settings reset to defaults.', 'opi-admin' ) );
}
?>

<div class="wrap">
    <h1><?php _e( 'Admin Customizer', 'opi-admin' ); ?></h1>

    <?php echo $message; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'opiadmin_action' ); ?>
        <input type="hidden" name="action" value="opiadmin_save">

        <!-- Sidebar -->
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Sidebar', 'opi-admin' ); ?></h2>

            <div class="opi-form-row">
                <label><?php _e( 'Width (px)', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <input type="number" min="100" max="400"
                           name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[sidebar_width]"
                           value="<?php echo absint( $settings['sidebar_width'] ); ?>"
                           data-css-var="--opi-admin-sidebar-width"
                           data-css-unit="px"
                           class="small-text">
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Background Color', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color"
                               name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[sidebar_bg]"
                               value="<?php echo esc_attr( $settings['sidebar_bg'] ); ?>"
                               data-css-var="--opi-admin-sidebar-bg">
                        <input type="text" maxlength="7"
                               value="<?php echo esc_attr( $settings['sidebar_bg'] ); ?>"
                               placeholder="#23282d">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Text Color', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color"
                               name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[sidebar_text]"
                               value="<?php echo esc_attr( $settings['sidebar_text'] ); ?>"
                               data-css-var="--opi-admin-sidebar-text">
                        <input type="text" maxlength="7"
                               value="<?php echo esc_attr( $settings['sidebar_text'] ); ?>"
                               placeholder="#a7aaad">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Icon Color', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color"
                               name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[sidebar_icon_color]"
                               value="<?php echo esc_attr( $settings['sidebar_icon_color'] ); ?>"
                               data-css-var="--opi-admin-sidebar-icon-color">
                        <input type="text" maxlength="7"
                               value="<?php echo esc_attr( $settings['sidebar_icon_color'] ); ?>"
                               placeholder="#a7aaad">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Highlight Color', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color"
                               name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[sidebar_highlight]"
                               value="<?php echo esc_attr( $settings['sidebar_highlight'] ); ?>"
                               data-css-var="--opi-admin-sidebar-highlight">
                        <input type="text" maxlength="7"
                               value="<?php echo esc_attr( $settings['sidebar_highlight'] ); ?>"
                               placeholder="#2271b1">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Submenu Background', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color"
                               name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[sidebar_submenu_bg]"
                               value="<?php echo esc_attr( $settings['sidebar_submenu_bg'] ); ?>"
                               data-css-var="--opi-admin-sidebar-submenu-bg">
                        <input type="text" maxlength="7"
                               value="<?php echo esc_attr( $settings['sidebar_submenu_bg'] ); ?>"
                               placeholder="#32373c">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Open Item Background', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color"
                               name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[sidebar_open_bg]"
                               value="<?php echo esc_attr( $settings['sidebar_open_bg'] ); ?>"
                               data-css-var="--opi-admin-sidebar-open-bg">
                        <input type="text" maxlength="7"
                               value="<?php echo esc_attr( $settings['sidebar_open_bg'] ); ?>"
                               placeholder="#191e23">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Third-party Icon Style', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <select name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[sidebar_icon_filter]"
                            id="opiadmin-icon-filter">
                        <option value="light" <?php selected( $settings['sidebar_icon_filter'], 'light' ); ?>>
                            <?php _e( 'Light (white)', 'opi-admin' ); ?>
                        </option>
                        <option value="dark" <?php selected( $settings['sidebar_icon_filter'], 'dark' ); ?>>
                            <?php _e( 'Dark (black)', 'opi-admin' ); ?>
                        </option>
                        <option value="none" <?php selected( $settings['sidebar_icon_filter'], 'none' ); ?>>
                            <?php _e( 'None (original colors)', 'opi-admin' ); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e( 'Controls the color of SVG/image icons from third-party plugins.', 'opi-admin' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Font Size (px)', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <input type="number" min="10" max="24"
                           name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[sidebar_font_size]"
                           value="<?php echo absint( $settings['sidebar_font_size'] ); ?>"
                           data-css-var="--opi-admin-sidebar-font-size"
                           data-css-unit="px"
                           class="small-text">
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Icon Size (px)', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <input type="number" min="10" max="32"
                           name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[sidebar_icon_size]"
                           value="<?php echo absint( $settings['sidebar_icon_size'] ); ?>"
                           data-css-var="--opi-admin-sidebar-icon-size"
                           data-css-unit="px"
                           class="small-text">
                </div>
            </div>
        </div>

        <!-- Top Bar -->
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Top Bar', 'opi-admin' ); ?></h2>

            <div class="opi-form-row">
                <label><?php _e( 'Background Color', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color"
                               name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[topbar_bg]"
                               value="<?php echo esc_attr( $settings['topbar_bg'] ); ?>"
                               data-css-var="--opi-admin-topbar-bg">
                        <input type="text" maxlength="7"
                               value="<?php echo esc_attr( $settings['topbar_bg'] ); ?>"
                               placeholder="#23282d">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Text / Icon Color', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color"
                               name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[topbar_text]"
                               value="<?php echo esc_attr( $settings['topbar_text'] ); ?>"
                               data-css-var="--opi-admin-topbar-text">
                        <input type="text" maxlength="7"
                               value="<?php echo esc_attr( $settings['topbar_text'] ); ?>"
                               placeholder="#a7aaad">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Height (px)', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <input type="number" min="28" max="80"
                           name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[topbar_height]"
                           value="<?php echo absint( $settings['topbar_height'] ); ?>"
                           data-css-var="--opi-admin-topbar-height"
                           data-css-unit="px"
                           class="small-text">
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Font Size (px)', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <input type="number" min="10" max="24"
                           name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[topbar_font_size]"
                           value="<?php echo absint( $settings['topbar_font_size'] ); ?>"
                           data-css-var="--opi-admin-topbar-font-size"
                           data-css-unit="px"
                           class="small-text">
                </div>
            </div>
        </div>

        <!-- Typography -->
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Typography', 'opi-admin' ); ?></h2>

            <div class="opi-form-row">
                <label><?php _e( 'Font Family', 'opi-admin' ); ?></label>
                <div class="opi-form-control">
                    <?php OPI_Google_Fonts::render_selector(
                        OPI_Admin_Settings::OPTION_KEY . '[font_family]',
                        $settings['font_family']
                    ); ?>
                </div>
            </div>
        </div>

        <!-- Custom CSS -->
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Custom CSS', 'opi-admin' ); ?></h2>

            <div class="opi-form-row">
                <div class="opi-form-control" style="width:100%;">
                    <textarea name="<?php echo OPI_Admin_Settings::OPTION_KEY; ?>[custom_css]"
                              id="opiadmin-custom-css"
                              rows="10"
                              class="large-text code"
                              style="font-family:monospace;"
                              ><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
                </div>
            </div>
        </div>

        <?php submit_button( __( 'Save Settings', 'opi-admin' ), 'primary', 'opiadmin_save' ); ?>

    </form>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 8px;">
        <?php wp_nonce_field( 'opiadmin_reset_action' ); ?>
        <input type="hidden" name="action" value="opiadmin_reset">
        <?php submit_button( __( 'Reset to Defaults', 'opi-admin' ), 'secondary', 'opiadmin_reset', false ); ?>
    </form>

</div>