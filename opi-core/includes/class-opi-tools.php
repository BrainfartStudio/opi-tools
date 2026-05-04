<?php
// includes/class-opi-tools.php

defined( 'ABSPATH' ) || exit;

class OPI_Tools {

    private static array $plugins = [];

    public static function init(): void {
    require_once OPITOOLS_PATH . 'includes/class-opi-settings.php';
    OPI_Settings::init();

    add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
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

    add_submenu_page(
        'opi-tools',
        'Theme Settings',
        'Theme Settings',
        'manage_options',
        'opi-tools-theme',
        fn() => require_once OPITOOLS_PATH . 'includes/views/settings-theme.php'
    );

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