<?php
// includes/class-opi-tools.php

defined( 'ABSPATH' ) || exit;

class OPI_Tools {

    private static array $plugins = [];

    public static function init(): void {
        require_once OPITOOLS_PATH . 'includes/class-opi-settings-base.php';
        require_once OPITOOLS_PATH . 'includes/class-opi-crypto.php';
        require_once OPITOOLS_PATH . 'includes/class-opi-cron-helper.php';
        require_once OPITOOLS_PATH . 'includes/class-opi-google-fonts.php';
        require_once OPITOOLS_PATH . 'includes/class-opi-settings.php';
        require_once OPITOOLS_PATH . 'includes/class-opi-updater.php';

        OPI_Settings::init();
        OPI_Updater::init();

        add_action( 'admin_menu',            [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

        do_action( 'opi_tools_register_plugins' );
    }

    /**
     * Sub-plugin registration.
     *
     * @param string        $slug        Unique slug, e.g. 'opi-discord'
     * @param string        $label       Display name, e.g. 'Discord'
     * @param string        $version     Plugin version string
     * @param callable      $page_cb     Renders the plugin's main admin page
     * @param string        $capability  Required capability. Default: 'manage_options'
     * @param callable|null $widget_cb   Returns dashboard widget data array
     * @param callable|null $health_cb   Returns health check data array
     */
    public static function register_plugin(
        string $slug,
        string $label,
        string $version,
        callable $page_cb,
        string $capability = 'manage_options',
        ?callable $widget_cb = null,
        ?callable $health_cb = null
    ): void {
        self::$plugins[ $slug ] = [
            'label'      => $label,
            'version'    => $version,
            'page_cb'    => $page_cb,
            'capability' => $capability,
            'widget_cb'  => $widget_cb,
            'health_cb'  => $health_cb,
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

        foreach ( self::$plugins as $slug => $plugin ) {
            add_menu_page(
                $plugin['label'],
                $plugin['label'],
                $plugin['capability'],
                $slug,
                $plugin['page_cb'],
                'dashicons-admin-generic',
                31
            );
        }
    }

    /**
     * Enqueue shared CSS and JS on all OPI admin pages.
     */
    public static function enqueue_assets( string $hook ): void {
        $opi_pages = [ 'toplevel_page_opi-tools' ];

        foreach ( array_keys( self::$plugins ) as $slug ) {
            $opi_pages[] = 'toplevel_page_' . $slug;
        }

        if ( ! in_array( $hook, $opi_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'opi-admin',
            OPITOOLS_URL . 'assets/css/opi-admin.css',
            [],
            OPITOOLS_VERSION
        );

        wp_enqueue_script(
            'opi-admin',
            OPITOOLS_URL . 'assets/js/opi-admin.js',
            [ 'jquery' ],
            OPITOOLS_VERSION,
            true
        );

        wp_localize_script( 'opi-admin', 'opiAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'opi_admin_nonce' ),
        ] );
    }

    /**
     * Return a rendered admin notice string.
     *
     * @param string $type    'success' | 'error' | 'warning' | 'info'
     * @param string $message Notice text.
     */
    public static function notice( string $type, string $message ): string {
        return sprintf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr( $type ),
            wp_kses_post( $message )
        );
    }

    public static function render_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        require_once OPITOOLS_PATH . 'includes/views/dashboard.php';
    }

    /**
     * Expose registered plugins to the dashboard view.
     */
    public static function get_plugins(): array {
        return self::$plugins;
    }
}