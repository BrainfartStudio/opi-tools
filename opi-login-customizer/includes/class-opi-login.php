<?php
// includes/class-opi-login.php

defined( 'ABSPATH' ) || exit;

class OPI_Login {

    const OPTION_KEY = 'opilogin_settings';

    public static function init(): void {
        require_once OPILOGIN_PATH . 'includes/class-opi-login-settings.php';

        add_action( 'opi_tools_register_plugins', [ __CLASS__, 'register_with_core' ] );
        add_action( 'login_enqueue_scripts',       [ __CLASS__, 'enqueue_styles' ] );
        add_filter( 'login_headerurl',             [ __CLASS__, 'header_url' ] );
        add_filter( 'login_headertext',            [ __CLASS__, 'header_text' ] );
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-login',
            'Login Customizer',
            OPILOGIN_VERSION,
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function enqueue_styles(): void {
        $s = OPI_Login_Settings::get();

        $logo_url  = $s['logo_id']     ? wp_get_attachment_image_url( $s['logo_id'], 'full' )     : '';
        $bg_url    = $s['bg_image_id'] ? wp_get_attachment_image_url( $s['bg_image_id'], 'full' )  : '';
        $radius    = absint( $s['form_radius'] ) . 'px';
        $shadow    = $s['form_shadow'] ? '0 4px 24px rgba(0,0,0,0.12)' : 'none';
        $font      = esc_attr( $s['font_family'] ) ?: 'inherit';

        $css = "
            body.login {
                background-color: {$s['bg_color']};
                font-family: {$font};
                " . ( $bg_url ? "background-image: url('{$bg_url}'); background-size: cover; background-position: center;" : '' ) . "
            }
            body.login #loginform,
            body.login #lostpasswordform,
            body.login #registerform {
                background: {$s['form_bg_color']};
                border-radius: {$radius};
                box-shadow: {$shadow};
            }
            body.login .button-primary {
                background: {$s['button_color']} !important;
                border-color: {$s['button_color']} !important;
                color: {$s['button_text']} !important;
            }
            body.login .button-primary:hover {
                opacity: 0.9;
            }
            " . ( $logo_url ? "
            body.login h1 a {
                background-image: url('{$logo_url}') !important;
                background-size: contain !important;
                width: 100% !important;
                height: 80px !important;
            }" : '' ) . "
            {$s['custom_css']}
        ";

        wp_register_style( 'opilogin', false );
        wp_enqueue_style( 'opilogin' );
        wp_add_inline_style( 'opilogin', $css );
    }

    public static function header_url(): string {
        return home_url();
    }

    public static function header_text(): string {
        $s = OPI_Login_Settings::get();
        return esc_html( $s['header_text'] ) ?: get_bloginfo( 'name' );
    }

    public static function render_page(): void {
        require_once OPILOGIN_PATH . 'includes/views/settings-page.php';
    }
}