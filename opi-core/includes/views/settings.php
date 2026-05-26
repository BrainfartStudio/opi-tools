<?php
// includes/views/settings.php

defined( 'ABSPATH' ) || exit;

$view = $_GET['view'] ?? 'settings';

// Only one tab for now; reserved for future shared settings tabs.
$tabs = [
    'settings' => __( 'Appearance', 'opi-core' ),
];

$theme   = OPI_Settings::get();
$message = '';

if ( isset( $_POST['opitools_save'] ) && check_admin_referer( 'opitools_settings_action' ) ) {
    $saved   = OPI_Settings::sanitize( $_POST[ OPI_Settings::OPTION_KEY ] ?? [] );
    OPI_Settings::update( $saved );
    $theme   = OPI_Settings::get();
    $message = OPI_Tools::notice( 'success', __( 'Settings saved.', 'opi-core' ) );
}
?>
<div class="wrap">
    <h1><?php _e( 'OPI Settings', 'opi-core' ); ?></h1>

    <?php echo $message; ?>

    <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
        <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-core&view=' . $tab_key ) ); ?>"
               class="nav-tab <?php echo $view === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html( $tab_label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ( $view === 'settings' ) : ?>

        <form method="post">
            <?php wp_nonce_field( 'opitools_settings_action' ); ?>

            <div class="opi-card">
                <h2 class="opi-section-header"><?php _e( 'Colors', 'opi-core' ); ?></h2>

                <div class="opi-form-row">
                    <label for="opi-accent"><?php _e( 'Accent Color', 'opi-core' ); ?></label>
                    <div class="opi-form-control">
                        <div class="opi-color-pair">
                            <input type="color" id="opi-accent"
                                   name="<?php echo esc_attr( OPI_Settings::OPTION_KEY ); ?>[accent_color]"
                                   value="<?php echo esc_attr( $theme['accent_color'] ); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr( $theme['accent_color'] ); ?>"
                                   maxlength="7" placeholder="#2271b1">
                        </div>
                    </div>
                </div>

                <div class="opi-form-row">
                    <label for="opi-accent-text"><?php _e( 'Accent Text Color', 'opi-core' ); ?></label>
                    <div class="opi-form-control">
                        <div class="opi-color-pair">
                            <input type="color" id="opi-accent-text"
                                   name="<?php echo esc_attr( OPI_Settings::OPTION_KEY ); ?>[accent_text]"
                                   value="<?php echo esc_attr( $theme['accent_text'] ); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr( $theme['accent_text'] ); ?>"
                                   maxlength="7" placeholder="#ffffff">
                        </div>
                    </div>
                </div>

                <div class="opi-form-row">
                    <label for="opi-surface"><?php _e( 'Surface Color', 'opi-core' ); ?></label>
                    <div class="opi-form-control">
                        <div class="opi-color-pair">
                            <input type="color" id="opi-surface"
                                   name="<?php echo esc_attr( OPI_Settings::OPTION_KEY ); ?>[surface_color]"
                                   value="<?php echo esc_attr( $theme['surface_color'] ); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr( $theme['surface_color'] ); ?>"
                                   maxlength="7" placeholder="#ffffff">
                        </div>
                    </div>
                </div>

                <div class="opi-form-row">
                    <label for="opi-surface-text"><?php _e( 'Surface Text Color', 'opi-core' ); ?></label>
                    <div class="opi-form-control">
                        <div class="opi-color-pair">
                            <input type="color" id="opi-surface-text"
                                   name="<?php echo esc_attr( OPI_Settings::OPTION_KEY ); ?>[surface_text]"
                                   value="<?php echo esc_attr( $theme['surface_text'] ); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr( $theme['surface_text'] ); ?>"
                                   maxlength="7" placeholder="#1d2327">
                        </div>
                    </div>
                </div>
            </div>

            <div class="opi-card">
                <h2 class="opi-section-header"><?php _e( 'Typography', 'opi-core' ); ?></h2>

                <div class="opi-form-row">
                    <label><?php _e( 'Font Family', 'opi-core' ); ?></label>
                    <div class="opi-form-control">
                        <?php OPI_Google_Fonts::render_selector(
                            OPI_Settings::OPTION_KEY . '[font_family]',
                            $theme['font_family']
                        ); ?>
                    </div>
                </div>
            </div>

            <?php submit_button( __( 'Save Settings', 'opi-core' ), 'primary', 'opitools_save' ); ?>
        </form>

    <?php endif; ?>
</div>