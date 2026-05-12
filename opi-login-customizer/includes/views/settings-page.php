<?php
// includes/views/settings-page.php

defined( 'ABSPATH' ) || exit;

$settings = OPI_Login_Settings::get();
$message  = '';

if ( isset( $_POST['opilogin_reset'] ) && check_admin_referer( 'opilogin_action' ) ) {
    OPI_Login_Settings::reset();
    wp_safe_redirect( admin_url( 'admin.php?page=opi-login&reset=1' ) );
    exit;
}

if ( isset( $_GET['reset'] ) ) {
    $settings = OPI_Login_Settings::get();
    $message  = OPI_Tools::notice( 'success', __( 'Settings reset to defaults.', 'opi-login' ) );
}

if ( isset( $_POST['opilogin_save'] ) && check_admin_referer( 'opilogin_action' ) ) {
    $input    = $_POST['opilogin'] ?? [];
    $saved    = OPI_Login_Settings::sanitize( $input );
    OPI_Login_Settings::update( $saved );
    $settings = OPI_Login_Settings::get();
    $message  = OPI_Tools::notice( 'success', __( 'Settings saved.', 'opi-login' ) );
}

$field = static function( string $key ): string {
    return 'opilogin[' . $key . ']';
};
?>

<div class="wrap">
    <h1><?php _e( 'Login Customizer', 'opi-login' ); ?></h1>

    <?php echo $message; ?>

    <p>
        <a href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank" class="button">
            <?php _e( 'Preview Login Page', 'opi-login' ); ?>
        </a>
    </p>

    <form method="post">
        <?php wp_nonce_field( 'opilogin_action' ); ?>

        <?php // ── Section 1: Logo ───────────────────────────────────────── ?>
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Logo', 'opi-login' ); ?></h2>

            <div class="opi-form-row">
                <label><?php _e( 'Logo Image', 'opi-login' ); ?></label>
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
                        <?php _e( 'Choose Logo', 'opi-login' ); ?>
                    </button>
                    <button type="button" class="button" id="opilogin-logo-remove">
                        <?php _e( 'Remove', 'opi-login' ); ?>
                    </button>
                    <p class="description"><?php _e( 'Replaces the WordPress logo on the login page.', 'opi-login' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-logo-bg-text"><?php _e( 'Logo Background Color', 'opi-login' ); ?></label>
                <div class="opi-form-control">
                    <div class="opi-color-pair">
                        <input type="color" id="opilogin-logo-bg"
                               value="<?php echo esc_attr( $settings['logo_bg_color'] === 'transparent' ? '#ffffff' : $settings['logo_bg_color'] ); ?>">
                        <input type="text" id="opilogin-logo-bg-text"
                               name="<?php echo esc_attr( $field( 'logo_bg_color' ) ); ?>"
                               value="<?php echo esc_attr( $settings['logo_bg_color'] ); ?>"
                               maxlength="11" placeholder="transparent">
                    </div>
                    <p class="description"><?php _e( 'Background behind the logo image. Use "transparent" to show none.', 'opi-login' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Logo Shape', 'opi-login' ); ?></label>
                <div class="opi-form-control">
                    <?php foreach ( [ 'none' => __( 'None', 'opi-login' ), 'circle' => __( 'Circle', 'opi-login' ), 'square' => __( 'Square', 'opi-login' ) ] as $val => $label ) : ?>
                        <label style="margin-right:16px;">
                            <input type="radio"
                                   name="<?php echo esc_attr( $field( 'logo_shape' ) ); ?>"
                                   value="<?php echo esc_attr( $val ); ?>"
                                   <?php checked( $settings['logo_shape'], $val ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="description"><?php _e( 'Controls the border-radius of the logo container.', 'opi-login' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-header-text"><?php _e( 'Header Text', 'opi-login' ); ?></label>
                <div class="opi-form-control">
                    <input type="text" id="opilogin-header-text"
                           name="<?php echo esc_attr( $field( 'header_text' ) ); ?>"
                           class="regular-text"
                           value="<?php echo esc_attr( $settings['header_text'] ); ?>">
                    <p class="description"><?php _e( 'Shown as the logo link title text. Defaults to site name if empty.', 'opi-login' ); ?></p>
                </div>
            </div>
        </div>

        <?php // ── Section 2: Background ─────────────────────────────────── ?>
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Background', 'opi-login' ); ?></h2>

            <div class="opi-form-row">
                <label for="opilogin-bg-color"><?php _e( 'Background Color', 'opi-login' ); ?></label>
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
                <label><?php _e( 'Background Image', 'opi-login' ); ?></label>
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
                        <?php _e( 'Choose Image', 'opi-login' ); ?>
                    </button>
                    <button type="button" class="button" id="opilogin-bg-remove">
                        <?php _e( 'Remove', 'opi-login' ); ?>
                    </button>
                    <p class="description"><?php _e( 'Displayed as a full-cover background. Overrides background color.', 'opi-login' ); ?></p>
                </div>
            </div>
        </div>

        <?php // ── Section 3: Form ───────────────────────────────────────── ?>
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Form', 'opi-login' ); ?></h2>

            <div class="opi-form-row">
                <label for="opilogin-form-bg"><?php _e( 'Form Background', 'opi-login' ); ?></label>
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
                <label for="opilogin-form-radius"><?php _e( 'Border Radius (px)', 'opi-login' ); ?></label>
                <div class="opi-form-control">
                    <input type="number" id="opilogin-form-radius"
                           name="<?php echo esc_attr( $field( 'form_radius' ) ); ?>"
                           min="0" max="40" step="1"
                           value="<?php echo esc_attr( $settings['form_radius'] ); ?>">
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opilogin-form-width"><?php _e( 'Form Width (px)', 'opi-login' ); ?></label>
                <div class="opi-form-control">
                    <input type="number" id="opilogin-form-width"
                           name="<?php echo esc_attr( $field( 'form_width' ) ); ?>"
                           min="200" max="600" step="10"
                           value="<?php echo esc_attr( $settings['form_width'] ); ?>">
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Form Shadow', 'opi-login' ); ?></label>
                <div class="opi-form-control">
                    <label>
                        <input type="checkbox" id="opilogin-form-shadow"
                               name="<?php echo esc_attr( $field( 'form_shadow' ) ); ?>"
                               value="1"
                               <?php checked( $settings['form_shadow'] ); ?>>
                        <?php _e( 'Show box shadow on login form', 'opi-login' ); ?>
                    </label>
                </div>
            </div>

            <div id="opilogin-shadow-options" <?php echo $settings['form_shadow'] ? '' : 'style="display:none;"'; ?>>
                <div class="opi-form-row">
                    <label for="opilogin-shadow-color"><?php _e( 'Shadow Color', 'opi-login' ); ?></label>
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
                    <label for="opilogin-shadow-blur"><?php _e( 'Shadow Blur (px)', 'opi-login' ); ?></label>
                    <div class="opi-form-control">
                        <input type="number" id="opilogin-shadow-blur"
                               name="<?php echo esc_attr( $field( 'form_shadow_blur' ) ); ?>"
                               min="0" max="100" step="1"
                               value="<?php echo esc_attr( $settings['form_shadow_blur'] ); ?>">
                    </div>
                </div>

                <div class="opi-form-row">
                    <label for="opilogin-shadow-spread"><?php _e( 'Shadow Spread (px)', 'opi-login' ); ?></label>
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
            <h2 class="opi-section-header"><?php _e( 'Button', 'opi-login' ); ?></h2>

            <div class="opi-form-row">
                <label for="opilogin-btn-color"><?php _e( 'Button Color', 'opi-login' ); ?></label>
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
                <label for="opilogin-btn-text"><?php _e( 'Button Text Color', 'opi-login' ); ?></label>
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
            <h2 class="opi-section-header"><?php _e( 'Typography', 'opi-login' ); ?></h2>

            <div class="opi-form-row">
                <label><?php _e( 'Font Family', 'opi-login' ); ?></label>
                <div class="opi-form-control">
                    <?php OPI_Google_Fonts::render_selector(
                        $field( 'font_family' ),
                        $settings['font_family']
                    ); ?>
                </div>
            </div>
        </div>

        <?php // ── Section 6: Custom CSS ─────────────────────────────────── ?>
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Custom CSS', 'opi-login' ); ?></h2>

            <div class="opi-form-row">
                <label for="opilogin-custom-css"><?php _e( 'Additional CSS', 'opi-login' ); ?></label>
                <div class="opi-form-control">
                    <textarea id="opilogin-custom-css"
                              name="<?php echo esc_attr( $field( 'custom_css' ) ); ?>"
                              rows="10" class="large-text code"
                    ><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
                    <p class="description"><?php _e( 'Appended to the login page stylesheet. Targets body.login.', 'opi-login' ); ?></p>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px;align-items:center;">
            <?php submit_button( __( 'Save Settings', 'opi-login' ), 'primary', 'opilogin_save', false ); ?>
            <?php submit_button( __( 'Reset to Defaults', 'opi-login' ), 'secondary', 'opilogin_reset', false, [
                'onclick' => 'return confirm("' . esc_js( __( 'Reset all Login Customizer settings to defaults? This cannot be undone.', 'opi-login' ) ) . '")',
            ] ); ?>
        </div>

    </form>
</div>

<script>
( function() {
    // Media picker buttons — bound here so OPI is guaranteed defined by opi-admin.js
    document.getElementById( 'opilogin-logo-choose' )?.addEventListener( 'click', function() {
        OPI.media(
            '<?php echo esc_js( __( 'Choose Logo', 'opi-login' ) ); ?>',
            'opilogin-logo-id',
            'opilogin-logo-preview'
        );
    } );

    document.getElementById( 'opilogin-bg-choose' )?.addEventListener( 'click', function() {
        OPI.media(
            '<?php echo esc_js( __( 'Choose Background Image', 'opi-login' ) ); ?>',
            'opilogin-bg-id',
            'opilogin-bg-preview'
        );
    } );

    // Remove logo
    document.getElementById( 'opilogin-logo-remove' )?.addEventListener( 'click', function( e ) {
        e.preventDefault();
        document.getElementById( 'opilogin-logo-id' ).value = 0;
        var preview = document.getElementById( 'opilogin-logo-preview' );
        if ( preview ) { preview.src = ''; preview.style.display = 'none'; }
    } );

    // Remove background image
    document.getElementById( 'opilogin-bg-remove' )?.addEventListener( 'click', function( e ) {
        e.preventDefault();
        document.getElementById( 'opilogin-bg-id' ).value = 0;
        var preview = document.getElementById( 'opilogin-bg-preview' );
        if ( preview ) { preview.src = ''; preview.style.display = 'none'; }
    } );

    // Toggle shadow options visibility
    var shadowToggle  = document.getElementById( 'opilogin-form-shadow' );
    var shadowOptions = document.getElementById( 'opilogin-shadow-options' );
    if ( shadowToggle && shadowOptions ) {
        shadowToggle.addEventListener( 'change', function() {
            shadowOptions.style.display = this.checked ? '' : 'none';
        } );
    }

    // logo_bg_color: text input is the POST field; keep color picker in sync.
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