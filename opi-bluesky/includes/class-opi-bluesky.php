<?php
// includes/class-opi-bluesky.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky {

    public static function init(): void {
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-settings.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-auth.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-api.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-auto-post.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-post-type.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-category-scheduler.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-scheduler.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-list-table.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-admin.php';

        OPI_Bluesky_Settings::init();
        OPI_Bluesky_Post_Type::init();
        OPI_Bluesky_Auto_Post::init();
        OPI_Bluesky_Category_Scheduler::init();
        OPI_Bluesky_Scheduler::init();
        OPI_Bluesky_Admin::init();

        add_action( 'opi_tools_register_plugins', [ __CLASS__, 'register_with_core' ] );
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-bluesky',
            'Bluesky',
            OPIBLUESKY_VERSION,
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page(): void {
        $view = sanitize_key( $_GET['view'] ?? 'list' );

        if ( $view === 'settings' ) {
            require_once OPIBLUESKY_PATH . 'includes/views/settings-page.php';
        } else {
            require_once OPIBLUESKY_PATH . 'includes/views/scheduled-posts.php';
        }
    }
}