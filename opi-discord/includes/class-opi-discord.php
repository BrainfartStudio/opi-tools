<?php
// includes/class-opi-discord.php

defined( 'ABSPATH' ) || exit;

class OPI_Discord {

    public static function init(): void {
        // Register with Core if it's active
        if ( function_exists( 'do_action' ) ) {
            add_action( 'opi_tools_register_plugins', [ __CLASS__, 'register_with_core' ] );
        }
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-discord',
            'Discord',
            OPIDISCORD_VERSION,
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page(): void {
        echo '<div class="wrap"><h1>OPI Discord</h1><p>Settings coming soon.</p></div>';
    }
}