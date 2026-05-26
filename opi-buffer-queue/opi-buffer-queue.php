<?php
/**
 * Plugin Name: OPI Buffer Queue
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Intelligent post scheduling with buffer queue management. Sub-plugin for OPI Tools Core.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-buffer-queue
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
        esc_html_e( 'OPI Buffer Queue requires OPI Tools Core to be installed and active.', 'opi-buffer-queue' );
        echo '</p></div>';
    } );
}
add_action( 'plugins_loaded', 'opi_buffer_check_core', 2 );

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

    // Cron helper may not be loaded at activation time — locate Core relative
    // to this plugin's directory rather than assuming a folder name.
    if ( ! class_exists( 'OPI_Cron_Helper' ) ) {
        $core_path = trailingslashit( dirname( plugin_dir_path( __FILE__ ), 2 ) )
                     . 'opi-tools/includes/class-opi-cron-helper.php';

        if ( ! file_exists( $core_path ) ) {
            // Fallback: scan active plugins for OPI Tools Core bootstrap.
            $active = get_option( 'active_plugins', [] );
            foreach ( $active as $plugin_file ) {
                if ( str_ends_with( $plugin_file, '/opi-core.php' ) ) {
                    $candidate = WP_PLUGIN_DIR . '/' . dirname( $plugin_file )
                                 . '/includes/class-opi-cron-helper.php';
                    if ( file_exists( $candidate ) ) {
                        $core_path = $candidate;
                        break;
                    }
                }
            }
        }

        if ( file_exists( $core_path ) ) {
            require_once $core_path;
        }
    }

    // Load plugin classes needed during activation — they are not yet loaded
    // at this point because plugins_loaded priority 5 hasn't fired yet.
    if ( ! class_exists( 'OPI_Settings_Base' ) ) {
        $base_path = trailingslashit( dirname( plugin_dir_path( __FILE__ ), 2 ) )
                     . 'opi-tools/includes/class-opi-settings-base.php';
        if ( file_exists( $base_path ) ) {
            require_once $base_path;
        }
    }

    if ( ! class_exists( 'OPI_Buffer_Settings' ) ) {
        require_once OPI_BUFFER_PATH . 'includes/class-opi-buffer-settings.php';
    }

    if ( ! class_exists( 'OPI_Buffer_Manager' ) ) {
        require_once OPI_BUFFER_PATH . 'includes/class-opi-buffer-manager.php';
    }

    if ( ! class_exists( 'OPI_Buffer_Scheduler' ) ) {
        require_once OPI_BUFFER_PATH . 'includes/class-opi-buffer-scheduler.php';
    }

    if ( class_exists( 'OPI_Cron_Helper' ) ) {
        OPI_Cron_Helper::register_interval( 'opi_buffer_15min', 900, __( 'Every 15 Minutes', 'opi-buffer-queue' ) );
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