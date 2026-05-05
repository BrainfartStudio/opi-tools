<?php
/**
 * Plugin Name: OPI Admin Customizer
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Customize the WordPress admin panel appearance.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-admin
 */

defined( 'ABSPATH' ) || exit;

define( 'OPIADMIN_VERSION', '1.0.0' );
define( 'OPIADMIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPIADMIN_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', function() {
    require_once OPIADMIN_PATH . 'includes/class-opi-admin.php';
    OPI_Admin::init();
}, 5 );

register_uninstall_hook( __FILE__, 'opiadmin_uninstall' );

function opiadmin_uninstall(): void {
    delete_option( OPI_Admin::OPTION_KEY );
}