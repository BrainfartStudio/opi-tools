<?php
/**
 * Plugin Name: OPI Buffer Queue
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Intelligent post scheduling with buffer queue management. Sub-plugin for OPI Tools Core.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-buffer
 */

defined( 'ABSPATH' ) || exit;

define( 'OPI_BUFFER_VERSION', '1.0.0' );
define( 'OPI_BUFFER_PATH',    plugin_dir_path( __FILE__ ) );
define( 'OPI_BUFFER_URL',     plugin_dir_url( __FILE__ ) );

/**
 * Bail early with a notice if OPI Tools Core is not active.
 */
function opi_buffer_check_core(): void {
    if ( class_exists( 'OPI_Tools' ) ) {
        return;
    }

    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'OPI Buffer Queue requires OPI Tools Core to be installed and active.', 'opi-buffer' );
        echo '</p></div>';
    } );
}
add_action( 'plugins_loaded', 'opi_buffer_check_core', 0 );

/**
 * Load all includes and boot the plugin.
 * Runs at priority 5 on plugins_loaded — after Core (priority 1) but before
 * the opi_tools_register_plugins action fires (priority 20).
 */
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'OPI_Tools' ) ) {
        return;
    }

    // Includes
    require_once OPI_BUFFER_PATH . 'includes/class-opi-buffer-post-status.php';
    require_once OPI_BUFFER_PATH . 'includes/class-opi-buffer-settings.php';
    require_once OPI_BUFFER_PATH . 'includes/class-opi-buffer-manager.php';
    require_once OPI_BUFFER_PATH . 'includes/class-opi-buffer-scheduler.php';

    // Admin
    require_once OPI_BUFFER_PATH . 'admin/class-opi-buffer-list-table.php';
    require_once OPI_BUFFER_PATH . 'admin/class-opi-buffer-admin.php';

    // Boot
    OPI_Buffer_Post_Status::init();
    OPI_Buffer_Manager::init();
    OPI_Buffer_Scheduler::init();
    OPI_Buffer_Admin::init();
}, 5 );

// -------------------------------------------------------------------------
// Activation / Deactivation
// -------------------------------------------------------------------------

register_activation_hook( __FILE__, function() {
    if ( ! get_option( 'opi_buffer_limits' ) ) {
        update_option( 'opi_buffer_limits', [
            'rules' => [
                [
                    'min_posts'     => 0,
                    'interval_days' => 3,
                    'time'          => '08:00',
                ],
            ],
        ] );
    }

    // Cron helper may not be loaded yet at activation time — load it directly.
    if ( ! class_exists( 'OPI_Cron_Helper' ) ) {
        $core_path = WP_PLUGIN_DIR . '/opi-tools/includes/class-opi-cron-helper.php';
        if ( file_exists( $core_path ) ) {
            require_once $core_path;
        }
    }

    if ( class_exists( 'OPI_Cron_Helper' ) ) {
        OPI_Cron_Helper::register_interval( 'opi_buffer_15min', 900, __( 'Every 15 Minutes', 'opi-buffer' ) );
        OPI_Buffer_Scheduler::activate();
    }

    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function() {
    if ( class_exists( 'OPI_Buffer_Scheduler' ) ) {
        OPI_Buffer_Scheduler::deactivate();
    }

    flush_rewrite_rules();
} );