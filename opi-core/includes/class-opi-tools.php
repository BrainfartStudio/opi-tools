<?php
// includes/class-opi-tools.php

defined( 'ABSPATH' ) || exit;

class OPI_Tools {

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
    }

    public static function register_menu(): void {
        add_menu_page(
            'OPI Tools',
            'OPI Tools',
            'manage_options',
            'opi-tools',
            [ __CLASS__, 'render_dashboard' ],
            'dashicons-admin-tools',
            30
        );
    }

    public static function render_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        echo '<div class="wrap"><h1>OPI Tools</h1><p>Dashboard coming soon.</p></div>';
    }
}