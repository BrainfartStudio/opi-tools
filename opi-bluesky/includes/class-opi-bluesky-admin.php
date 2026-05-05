<?php
// includes/class-opi-bluesky-admin.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Admin {

    public static function init(): void {
        add_action( 'admin_menu',                      [ __CLASS__, 'register_settings_page' ], 20 );
        add_action( 'admin_enqueue_scripts',           [ __CLASS__, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_opibluesky_test_connection', [ __CLASS__, 'ajax_test_connection' ] );
    }

    public static function register_settings_page(): void {
        add_submenu_page(
            'opi-tools',
            __( 'Bluesky Settings', 'opi-bluesky' ),
            __( 'Bluesky Settings', 'opi-bluesky' ),
            'manage_options',
            'opi-bluesky-settings',
            [ 'OPI_Bluesky', 'render_settings_page' ]
        );
    }

    public static function enqueue_scripts( string $hook ): void {
        if ( ! in_array( $hook, [
            'opi-tools_page_opi-bluesky',
            'opi-tools_page_opi-bluesky-settings',
        ], true ) ) {
            return;
        }

        wp_enqueue_script(
            'opibluesky-admin',
            OPIBLUESKY_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            OPIBLUESKY_VERSION,
            true
        );

        wp_localize_script( 'opibluesky-admin', 'opiBlueskyAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'opibluesky_nonce' ),
        ] );

        wp_enqueue_style(
            'opibluesky-admin',
            OPIBLUESKY_URL . 'assets/css/admin.css',
            [],
            OPIBLUESKY_VERSION
        );
    }

    public static function ajax_test_connection(): void {
        check_ajax_referer( 'opibluesky_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        if ( ! OPI_Bluesky_Settings::is_configured() ) {
            wp_send_json_error( 'Credentials not configured.' );
        }

        OPI_Bluesky_Auth::clear_session();

        $result = OPI_Bluesky_Auth::authenticate(
            OPI_Bluesky_Settings::get_identifier(),
            OPI_Bluesky_Settings::get_app_password()
        );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        $session = OPI_Bluesky_Auth::get_session();
        wp_send_json_success( [
            'handle' => $session['handle'] ?? OPI_Bluesky_Settings::get_identifier(),
        ] );
    }
}