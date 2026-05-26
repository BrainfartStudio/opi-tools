<?php
/**
 * Plugin Name: OPI Login Customizer
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Customize the WordPress login page appearance.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-login
 */

defined( 'ABSPATH' ) || exit;

define( 'OPILOGIN_VERSION', '1.0.0' );
define( 'OPILOGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPILOGIN_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', function() {
    require_once OPILOGIN_PATH . 'includes/class-opi-login.php';
    OPI_Login::init();
}, 5 );

register_uninstall_hook( __FILE__, 'opilogin_uninstall' );

function opilogin_uninstall(): void {
    delete_option( OPI_Login_Settings::OPTION_KEY );
}