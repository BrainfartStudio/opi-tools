<?php
// includes/class-opi-login.php

defined( 'ABSPATH' ) || exit;

class OPI_Login {

    public static function init(): void {
        require_once OPI_LOGIN_PATH . 'includes/class-opi-login-settings.php';

        add_action( 'admin_enqueue_scripts',      [ __CLASS__, 'enqueue_admin_scripts' ] );
        add_action( 'opi_tools_register_plugins', [ __CLASS__, 'register_with_core' ] );
        add_action( 'login_enqueue_scripts',      [ __CLASS__, 'enqueue_styles' ] );
        add_filter( 'login_headerurl',            [ __CLASS__, 'header_url' ] );
        add_filter( 'login_headertext',           [ __CLASS__, 'header_text' ] );
        add_filter( 'login_message',              [ __CLASS__, 'inject_header_text' ] );
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-login',
            'Login Customizer',
            OPI_LOGIN_VERSION,
            [ __CLASS__, 'render_page' ],
            'manage_options',
            [ __CLASS__, 'widget' ],
            [ __CLASS__, 'health_check' ]
        );
    }

    public static function widget(): array {
        $s      = OPI_Login_Settings::get();
        $health = self::health_check();

        $value = ! empty( $s['logo_id'] )
            ? 'Logo set'
            : ( ! empty( $s['header_text'] ) ? 'Header text set' : 'No logo or header text' );

        return [
            'status' => $health['severity'],
            'label'  => 'Login Customizer',
            'value'  => $value,
        ];
    }

    public static function health_check(): array {
        $s = OPI_Login_Settings::get();

        if ( empty( $s['logo_id'] ) && empty( $s['header_text'] ) ) {
            return [
                'severity'   => 'warn',
                'message'    => 'No logo or header text set.',
                'action_url' => admin_url( 'admin.php?page=opi-login' ),
            ];
        }

        return [ 'severity' => 'ok', 'message' => '' ];
    }

    public static function enqueue_styles(): void {
        $s = OPI_Login_Settings::get();

        $logo_url = $s['logo_id']     ? wp_get_attachment_image_url( $s['logo_id'], 'full' )    : '';
        $bg_url   = $s['bg_image_id'] ? wp_get_attachment_image_url( $s['bg_image_id'], 'full' ) : '';
        $radius   = absint( $s['form_radius'] ) . 'px';
        $width    = absint( $s['form_width'] ) . 'px';
        $font     = esc_attr( $s['font_family'] ) ?: 'inherit';

        if ( $s['form_shadow'] ) {
            $shadow_color  = esc_attr( $s['form_shadow_color'] );
            $shadow_blur   = absint( $s['form_shadow_blur'] ) . 'px';
            $shadow_spread = absint( $s['form_shadow_spread'] ) . 'px';
            $shadow        = "0 4px {$shadow_blur} {$shadow_spread} {$shadow_color}";
        } else {
            $shadow = 'none';
        }

        $logo_shape_radius = match( $s['logo_shape'] ) {
            'circle' => '50%',
            'square' => '0%',
            default  => '',
        };

        $bg_color      = esc_attr( $s['bg_color'] );
        $form_bg_color = esc_attr( $s['form_bg_color'] );
        $form_text     = esc_attr( $s['form_text_color'] );
        $link          = esc_attr( $s['link_color'] );
        $button_color  = esc_attr( $s['button_color'] );
        $button_text   = esc_attr( $s['button_text'] );
        $logo_bg_color = esc_attr( $s['logo_bg_color'] );

        $bg_image_css = $bg_url
            ? "background-image: url('" . esc_url( $bg_url ) . "'); background-size: cover; background-position: center;"
            : '';

        $css = "
            body.login {
                background-color: {$bg_color};
                font-family: {$font};
                {$bg_image_css}
            }

            body.login #login {
                width: {$width};
                margin-left: auto;
                margin-right: auto;
            }

            body.login #loginform,
            body.login #lostpasswordform,
            body.login #registerform {
                background: {$form_bg_color};
                border-radius: {$radius};
                box-shadow: {$shadow};
                width: 100%;
                box-sizing: border-box;
            }

            body.login #loginform label,
            body.login #lostpasswordform label,
            body.login #registerform label {
                color: {$form_text};
            }

            body.login #loginform input[type='text'],
            body.login #loginform input[type='password'],
            body.login #lostpasswordform input[type='text'],
            body.login #registerform input[type='text'],
            body.login #registerform input[type='email'] {
                color: {$form_text};
            }

            body.login #nav a,
            body.login #backtoblog a {
                color: {$link};
            }

            body.login #nav a:hover,
            body.login #backtoblog a:hover {
                color: {$link};
                opacity: 0.8;
            }

            body.login .button-primary {
                background: {$button_color} !important;
                border-color: {$button_color} !important;
                color: {$button_text} !important;
            }

            body.login .button-primary:hover {
                opacity: 0.9;
            }
        ";

        if ( $logo_url ) {
            $logo_url_escaped = esc_url( $logo_url );
            $css .= "
            body.login h1 a {
                background-image: url('{$logo_url_escaped}') !important;
                background-size: contain !important;
                background-color: {$logo_bg_color};
                width: 100% !important;
                height: 80px !important;
                " . ( $logo_shape_radius ? "border-radius: {$logo_shape_radius};" : '' ) . "
            }";
        } elseif ( ! empty( $s['header_text'] ) ) {
            $header_font  = esc_attr( $s['header_text_font'] ) ?: 'inherit';
            $header_color = esc_attr( $s['header_text_color'] );

            $css .= "
            body.login h1 { display: none; }
            body.login .opilogin-header-text {
                display: block;
                text-align: center;
                font-size: 24px;
                font-weight: 700;
                color: {$header_color};
                font-family: {$header_font};
                margin: 0 0 20px;
                text-shadow: 0 1px 3px rgba(0,0,0,0.3);
            }";

            if ( ! empty( $s['header_text_font'] ) && $s['header_text_font'] !== 'inherit' ) {
                OPI_Google_Fonts::enqueue( $s['header_text_font'] );
            }
        }

        $css .= "\n" . wp_strip_all_tags( $s['custom_css'] );

        wp_register_style( 'opilogin', false );
        wp_enqueue_style( 'opilogin' );
        wp_add_inline_style( 'opilogin', $css );

        if ( ! empty( $s['font_family'] ) && $s['font_family'] !== 'inherit' ) {
            OPI_Google_Fonts::enqueue( $s['font_family'] );
        }
    }

    public static function inject_header_text( string $message ): string {
        $s = OPI_Login_Settings::get();

        if ( empty( $s['logo_id'] ) && ! empty( $s['header_text'] ) ) {
            $message = '<h2 class="opilogin-header-text">' . esc_html( $s['header_text'] ) . '</h2>' . $message;
        }

        return $message;
    }

    public static function header_url(): string {
        return home_url();
    }

    public static function header_text(): string {
        $s = OPI_Login_Settings::get();
        $text = $s['header_text'];
        return $text !== '' ? esc_html( $text ) : esc_html( get_bloginfo( 'name' ) );
    }

    public static function render_page(): void {
        require_once OPI_LOGIN_PATH . 'includes/views/settings-page.php';
    }

    public static function enqueue_admin_scripts( string $hook ): void {
        if ( $hook !== 'opi-tools_page_opi-login' ) {
            return;
        }

        wp_enqueue_media();
    }
}