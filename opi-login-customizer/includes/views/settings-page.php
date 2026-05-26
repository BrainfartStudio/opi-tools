<?php
// includes/views/settings-page.php

defined( 'ABSPATH' ) || exit;

$settings = OPI_Login_Settings::get();
$message  = '';

if ( isset( $_POST['opilogin_reset'] ) && check_admin_referer( 'opilogin_action' ) ) {
    OPI_Login_Settings::reset();
    $settings = OPI_Login_Settings::get();
    $message  = OPI_Tools::notice( 'success', __( 'Settings reset to defaults.', 'opi-login-customizer' ) );
}

if ( isset( $_POST['opilogin_save'] ) && check_admin_referer( 'opilogin_action' ) ) {
    $input    = $_POST['opilogin'] ?? [];
    $saved    = OPI_Login_Settings::sanitize( $input );
    OPI_Login_Settings::update( $saved );
    $settings = OPI_Login_Settings::get();
    $message  = OPI_Tools::notice( 'success', __( 'Settings saved.', 'opi-login-customizer' ) );
}

$field = static function( string $key ): string {
    return 'opilogin[' . $key . ']';
};
?>

<div class="wrap">
    <h1><?php _e( 'Login Customizer', 'opi-login-customizer' ); ?></h1>

    <?php echo $message; ?>

    <p>
        <a href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank" class="button">
            <?php _e( 'Preview Login Page', 'opi-login-customizer' ); ?>
        </a>
    </p>

    <form method="post">
        <?php wp_nonce_field( 'opilogin_action' ); ?>

        <?php // ── Section 1: Logo ───────────────────────────────────────── ?>
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Logo', 'opi-login-customizer' ); ?></h2>

            <div class="opi-form-row">
                <label><?php _e( 'Logo Image', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <?php if ( $settings['logo_id'] ) : ?>
                        <img id="opilogin-logo-preview"
                             src="<?php echo esc_url( wp_get_attachment_image_url( $settings['logo_id'], 'medium' ) ); ?>"
                             style="max-height:80px;display:block;margin-bottom:8px;">
                    <?php else : ?>
                        <img id="opilogin-logo-preview" src="" style="max-height:80px;display:none;margin-bottom:8px;">
                    <?php endif; ?>
                    <input type="hidden" id="opilogin-logo-id"
                           name="<?php echo esc_attr( $field( 'logo_id' ) ); ?>"
                           value="<?php echo esc_attr( $settings['logo_id'] ); ?>">
                    <button type="button" class="button" id="opilogin-logo-choose">
                        <?php _e( 'Choose Logo', 'opi-login-customizer' ); ?>
                    </button>
                    <button type="button" class="button" id="opilogin-logo-remove">
                        <?php _e( 'Remove', 'opi-login-customizer' ); ?>
                    </button>
                    <p class="description"><?php _e( 'Replaces the WordPress logo on the login page. Takes priority over Header Text.', 'opi-login-customizer' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-logo-bg-text"><?php _e( 'Logo Background Color', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color" id="opilogin-logo-bg"
                               value="<?php echo esc_attr( $settings['logo_bg_color'] === 'transparent' ? '#ffffff' : $settings['logo_bg_color'] ); ?>">
                        <input type="text" id="opilogin-logo-bg-text"
                               name="<?php echo esc_attr( $field( 'logo_bg_color' ) ); ?>"
                               value="<?php echo esc_attr( $settings['logo_bg_color'] ); ?>"
                               maxlength="11" placeholder="transparent">
                    </div>
                    <p class="description"><?php _e( 'Background behind the logo image. Use "transparent" to show none.', 'opi-login-customizer' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Logo Shape', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <?php foreach ( [ 'none' => __( 'None', 'opi-login-customizer' ), 'circle' => __( 'Circle', 'opi-login-customizer' ), 'square' => __( 'Square', 'opi-login-customizer' ) ] as $val => $label ) : ?>
                        <label style="margin-right:16px;">
                            <input type="radio"
                                   name="<?php echo esc_attr( $field( 'logo_shape' ) ); ?>"
                                   value="<?php echo esc_attr( $val ); ?>"
                                   <?php checked( $settings['logo_shape'], $val ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="description"><?php _e( 'Controls the border-radius of the logo container.', 'opi-login-customizer' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-header-text"><?php _e( 'Header Text', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <input type="text" id="opilogin-header-text"
                           name="<?php echo esc_attr( $field( 'header_text' ) ); ?>"
                           class="regular-text"
                           value="<?php echo esc_attr( $settings['header_text'] ); ?>">
                    <p class="description">
                        <?php _e( 'Shown as a heading above the login form when no logo is set. If neither is set, the WordPress logo is displayed.', 'opi-login-customizer' ); ?>
                    </p>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-header-text-color"><?php _e( 'Header Text Color', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color" id="opilogin-header-text-color"
                               name="<?php echo esc_attr( $field( 'header_text_color' ) ); ?>"
                               value="<?php echo esc_attr( $settings['header_text_color'] ); ?>">
                        <input type="text"
                               value="<?php echo esc_attr( $settings['header_text_color'] ); ?>"
                               maxlength="7" placeholder="#ffffff">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Header Text Font', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <?php OPI_Google_Fonts::render_selector(
                        $field( 'header_text_font' ),
                        $settings['header_text_font']
                    ); ?>
                    <p class="description"><?php _e( 'Only applies when Header Text is used (no logo set).', 'opi-login-customizer' ); ?></p>
                </div>
            </div>
        </div>

        <?php // ── Section 2: Background ─────────────────────────────────── ?>
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Background', 'opi-login-customizer' ); ?></h2>

            <div class="opi-form-row">
                <label for="opilogin-bg-color"><?php _e( 'Background Color', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color" id="opilogin-bg-color"
                               name="<?php echo esc_attr( $field( 'bg_color' ) ); ?>"
                               value="<?php echo esc_attr( $settings['bg_color'] ); ?>">
                        <input type="text"
                               value="<?php echo esc_attr( $settings['bg_color'] ); ?>"
                               maxlength="7" placeholder="#f0f0f1">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Background Image', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <?php if ( $settings['bg_image_id'] ) : ?>
                        <img id="opilogin-bg-preview"
                             src="<?php echo esc_url( wp_get_attachment_image_url( $settings['bg_image_id'], 'medium' ) ); ?>"
                             style="max-height:80px;display:block;margin-bottom:8px;">
                    <?php else : ?>
                        <img id="opilogin-bg-preview" src="" style="max-height:80px;display:none;margin-bottom:8px;">
                    <?php endif; ?>
                    <input type="hidden" id="opilogin-bg-id"
                           name="<?php echo esc_attr( $field( 'bg_image_id' ) ); ?>"
                           value="<?php echo esc_attr( $settings['bg_image_id'] ); ?>">
                    <button type="button" class="button" id="opilogin-bg-choose">
                        <?php _e( 'Choose Image', 'opi-login-customizer' ); ?>
                    </button>
                    <button type="button" class="button" id="opilogin-bg-remove">
                        <?php _e( 'Remove', 'opi-login-customizer' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <?php // ── Section 3: Form ───────────────────────────────────────── ?>
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Login Form', 'opi-login-customizer' ); ?></h2>

            <div class="opi-form-row">
                <label for="opilogin-form-bg"><?php _e( 'Form Background Color', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color" id="opilogin-form-bg"
                               name="<?php echo esc_attr( $field( 'form_bg_color' ) ); ?>"
                               value="<?php echo esc_attr( $settings['form_bg_color'] ); ?>">
                        <input type="text"
                               value="<?php echo esc_attr( $settings['form_bg_color'] ); ?>"
                               maxlength="7" placeholder="#ffffff">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-form-text"><?php _e( 'Form Text Color', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color" id="opilogin-form-text"
                               name="<?php echo esc_attr( $field( 'form_text_color' ) ); ?>"
                               value="<?php echo esc_attr( $settings['form_text_color'] ); ?>">
                        <input type="text"
                               value="<?php echo esc_attr( $settings['form_text_color'] ); ?>"
                               maxlength="7" placeholder="#3c434a">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-form-radius"><?php _e( 'Border Radius (px)', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <input type="number" id="opilogin-form-radius"
                           name="<?php echo esc_attr( $field( 'form_radius' ) ); ?>"
                           min="0" max="40" step="1"
                           value="<?php echo esc_attr( $settings['form_radius'] ); ?>">
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-form-width"><?php _e( 'Form Width (px)', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <input type="number" id="opilogin-form-width"
                           name="<?php echo esc_attr( $field( 'form_width' ) ); ?>"
                           min="280" max="600" step="1"
                           value="<?php echo esc_attr( $settings['form_width'] ); ?>">
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-form-shadow"><?php _e( 'Box Shadow', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <label>
                        <input type="checkbox" id="opilogin-form-shadow"
                               name="<?php echo esc_attr( $field( 'form_shadow' ) ); ?>"
                               value="1"
                               <?php checked( $settings['form_shadow'] ); ?>>
                        <?php _e( 'Enable box shadow on login form', 'opi-login-customizer' ); ?>
                    </label>
                </div>
            </div>

            <div id="opilogin-shadow-options" style="<?php echo $settings['form_shadow'] ? '' : 'display:none;'; ?>">
                <div class="opi-form-row">
                    <label for="opilogin-shadow-color"><?php _e( 'Shadow Color', 'opi-login-customizer' ); ?></label>
                    <div class="opi-form-control">
                        <div class="opi-color-pair">
                            <input type="color" id="opilogin-shadow-color"
                                   name="<?php echo esc_attr( $field( 'form_shadow_color' ) ); ?>"
                                   value="<?php echo esc_attr( $settings['form_shadow_color'] ); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr( $settings['form_shadow_color'] ); ?>"
                                   maxlength="7" placeholder="#000000">
                        </div>
                    </div>
                </div>

                <div class="opi-form-row">
                    <label for="opilogin-shadow-blur"><?php _e( 'Shadow Blur (px)', 'opi-login-customizer' ); ?></label>
                    <div class="opi-form-control">
                        <input type="number" id="opilogin-shadow-blur"
                               name="<?php echo esc_attr( $field( 'form_shadow_blur' ) ); ?>"
                               min="0" max="100" step="1"
                               value="<?php echo esc_attr( $settings['form_shadow_blur'] ); ?>">
                    </div>
                </div>

                <div class="opi-form-row">
                    <label for="opilogin-shadow-spread"><?php _e( 'Shadow Spread (px)', 'opi-login-customizer' ); ?></label>
                    <div class="opi-form-control">
                        <input type="number" id="opilogin-shadow-spread"
                               name="<?php echo esc_attr( $field( 'form_shadow_spread' ) ); ?>"
                               min="0" max="50" step="1"
                               value="<?php echo esc_attr( $settings['form_shadow_spread'] ); ?>">
                    </div>
                </div>
            </div>
        </div>

        <?php // ── Section 4: Button ─────────────────────────────────────── ?>
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Button', 'opi-login-customizer' ); ?></h2>

            <div class="opi-form-row">
                <label for="opilogin-btn-color"><?php _e( 'Button Color', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color" id="opilogin-btn-color"
                               name="<?php echo esc_attr( $field( 'button_color' ) ); ?>"
                               value="<?php echo esc_attr( $settings['button_color'] ); ?>">
                        <input type="text"
                               value="<?php echo esc_attr( $settings['button_color'] ); ?>"
                               maxlength="7" placeholder="#2271b1">
                    </div>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-btn-text"><?php _e( 'Button Text Color', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color" id="opilogin-btn-text"
                               name="<?php echo esc_attr( $field( 'button_text' ) ); ?>"
                               value="<?php echo esc_attr( $settings['button_text'] ); ?>">
                        <input type="text"
                               value="<?php echo esc_attr( $settings['button_text'] ); ?>"
                               maxlength="7" placeholder="#ffffff">
                    </div>
                </div>
            </div>
        </div>

        <?php // ── Section 5: Typography ─────────────────────────────────── ?>
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Typography', 'opi-login-customizer' ); ?></h2>

            <div class="opi-form-row">
                <label><?php _e( 'Font Family', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <?php OPI_Google_Fonts::render_selector(
                        $field( 'font_family' ),
                        $settings['font_family']
                    ); ?>
                    <p class="description"><?php _e( 'Applied to all text on the login page.', 'opi-login-customizer' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-link-color"><?php _e( 'Link Color', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color" id="opilogin-link-color"
                               name="<?php echo esc_attr( $field( 'link_color' ) ); ?>"
                               value="<?php echo esc_attr( $settings['link_color'] ); ?>">
                        <input type="text"
                               value="<?php echo esc_attr( $settings['link_color'] ); ?>"
                               maxlength="7" placeholder="#2271b1">
                    </div>
                    <p class="description"><?php _e( 'Color for "Lost your password?" and "Go to site" links.', 'opi-login-customizer' ); ?></p>
                </div>
            </div>
        </div>

        <?php // ── Section 6: Custom CSS ─────────────────────────────────── ?>
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Custom CSS', 'opi-login-customizer' ); ?></h2>

            <div class="opi-form-row">
                <label for="opilogin-custom-css"><?php _e( 'Additional CSS', 'opi-login-customizer' ); ?></label>
                <div class="opi-form-control">
                    <textarea id="opilogin-custom-css"
                              name="<?php echo esc_attr( $field( 'custom_css' ) ); ?>"
                              rows="10" class="large-text code"
                    ><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
                    <p class="description"><?php _e( 'Appended to the login page stylesheet. Targets body.login.', 'opi-login-customizer' ); ?></p>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px;align-items:center;">
            <?php submit_button( __( 'Save Settings', 'opi-login-customizer' ), 'primary', 'opilogin_save', false ); ?>
            <?php submit_button( __( 'Reset to Defaults', 'opi-login-customizer' ), 'secondary', 'opilogin_reset', false, [
                'onclick' => 'return confirm("' . esc_js( __( 'Reset all Login Customizer settings to defaults? This cannot be undone.', 'opi-login-customizer' ) ) . '")',
            ] ); ?>
        </div>

    </form>
</div>

<script>
( function() {
    document.getElementById( 'opilogin-logo-choose' )?.addEventListener( 'click', function() {
        OPI.media(
            '<?php echo esc_js( __( 'Choose Logo', 'opi-login-customizer' ) ); ?>',
            'opilogin-logo-id',
            'opilogin-logo-preview'
        );
    } );

    document.getElementById( 'opilogin-bg-choose' )?.addEventListener( 'click', function() {
        OPI.media(
            '<?php echo esc_js( __( 'Choose Background Image', 'opi-login-customizer' ) ); ?>',
            'opilogin-bg-id',
            'opilogin-bg-preview'
        );
    } );

    document.getElementById( 'opilogin-logo-remove' )?.addEventListener( 'click', function( e ) {
        e.preventDefault();
        document.getElementById( 'opilogin-logo-id' ).value = 0;
        var preview = document.getElementById( 'opilogin-logo-preview' );
        if ( preview ) { preview.src = ''; preview.style.display = 'none'; }
    } );

    document.getElementById( 'opilogin-bg-remove' )?.addEventListener( 'click', function( e ) {
        e.preventDefault();
        document.getElementById( 'opilogin-bg-id' ).value = 0;
        var preview = document.getElementById( 'opilogin-bg-preview' );
        if ( preview ) { preview.src = ''; preview.style.display = 'none'; }
    } );

    var shadowToggle  = document.getElementById( 'opilogin-form-shadow' );
    var shadowOptions = document.getElementById( 'opilogin-shadow-options' );
    if ( shadowToggle && shadowOptions ) {
        shadowToggle.addEventListener( 'change', function() {
            shadowOptions.style.display = this.checked ? '' : 'none';
        } );
    }

    ( function() {
        var colorInput = document.getElementById( 'opilogin-logo-bg' );
        var textInput  = document.getElementById( 'opilogin-logo-bg-text' );
        if ( ! colorInput || ! textInput ) return;

        textInput.addEventListener( 'input', function() {
            if ( /^#[0-9a-fA-F]{6}$/.test( this.value ) ) {
                colorInput.value = this.value;
            }
        } );

        colorInput.addEventListener( 'input', function() {
            textInput.value = this.value;
        } );
    } )();
} )();
</script>