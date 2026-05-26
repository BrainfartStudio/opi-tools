<?php
/**
 * Plugin Name: OPI Admin Customizer
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Customize the WordPress admin panel appearance.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-admin-customizer
 */

defined( 'ABSPATH' ) || exit;

define( 'OPI_ADMIN_VERSION', '1.0.0' );
define( 'OPI_ADMIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPI_ADMIN_URL', plugin_dir_url( __FILE__ ) );

function opi_admin_check_core(): void {
    if ( class_exists( 'OPI_Tools' ) ) {
        return;
    }

    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'OPI Admin Customizer requires OPI Tools Core to be installed and active.', 'opi-admin-customizer' );
        echo '</p></div>';
    } );
}
add_action( 'plugins_loaded', 'opi_admin_check_core', 2 );

add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'OPI_Tools' ) ) {
        return;
    }

    require_once OPI_ADMIN_PATH . 'includes/class-opi-admin.php';
    OPI_Admin::init();
}, 5 );

register_uninstall_hook( __FILE__, 'opiadmin_uninstall' );

function opiadmin_uninstall(): void {
    delete_option( 'opiadmin_settings' );
}