<?php
// includes/class-opi-tools.php

defined( 'ABSPATH' ) || exit;

class OPI_Tools {

    private static array $plugins = [];

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );

        /**
         * Sub-plugins register themselves on 'opi_tools_register_plugins'.
         * They call OPI_Tools::register_plugin() from their hook callback.
         * Priority 5 so registration happens before the menu is built.
         */
        do_action( 'opi_tools_register_plugins' );
    }

    /**
     * Sub-plugin registration.
     *
     * @param string   $slug        Unique slug, e.g. 'opi-discord'
     * @param string   $label       Display name, e.g. 'Discord'
     * @param string   $version     Plugin version string
     * @param callable $page_cb     Callback that renders the settings page
     * @param string   $menu_parent 'opi-tools' to appear under OPI Tools, or a native WP menu slug
     */
    public static function register_plugin(
        string $slug,
        string $label,
        string $version,
        callable $page_cb,
        string $menu_parent = 'opi-tools'
    ): void {
        self::$plugins[ $slug ] = [
            'label'       => $label,
            'version'     => $version,
            'page_cb'     => $page_cb,
            'menu_parent' => $menu_parent,
        ];
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

        // Register a submenu page for each sub-plugin.
        foreach ( self::$plugins as $slug => $plugin ) {
            add_submenu_page(
                $plugin['menu_parent'],
                $plugin['label'],
                $plugin['label'],
                'manage_options',
                $slug,
                $plugin['page_cb']
            );
        }
    }

    public static function render_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        echo '<div class="wrap"><h1>OPI Tools</h1><p>Dashboard coming soon.</p></div>';
    }
}