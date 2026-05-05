<?php
// includes/class-opi-bluesky-admin.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Admin {

    public static function init(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'register_settings_page' ], 20 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
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
}