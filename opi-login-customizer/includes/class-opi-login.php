<?php
// includes/class-opi-login.php

defined( 'ABSPATH' ) || exit;

class OPI_Login {

    const OPTION_KEY = 'opilogin_settings';

    public static function init(): void {
        add_action( 'opi_tools_register_plugins', [ __CLASS__, 'register_with_core' ] );
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-login',
            'Login Customizer',
            OPILOGIN_VERSION,
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page(): void {
        require_once OPILOGIN_PATH . 'includes/views/settings-page.php';
    }
}