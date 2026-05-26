<?php
/**
 * Plugin Name: OPI Login Customizer
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Customize the WordPress login page appearance.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-login-customizer
 */

defined( 'ABSPATH' ) || exit;

define( 'OPI_LOGIN_VERSION', '1.0.0' );
define( 'OPI_LOGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPI_LOGIN_URL', plugin_dir_url( __FILE__ ) );

function opi_login_check_core(): void {
    if ( class_exists( 'OPI_Tools' ) ) {
        return;
    }

    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'OPI Login Customizer requires OPI Tools Core to be installed and active.', 'opi-login-customizer' );
        echo '</p></div>';
    } );
}
add_action( 'plugins_loaded', 'opi_login_check_core', 2 );

add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'OPI_Tools' ) ) {
        return;
    }

    require_once OPI_LOGIN_PATH . 'includes/class-opi-login.php';
    OPI_Login::init();
}, 5 );

register_uninstall_hook( __FILE__, 'opilogin_uninstall' );

function opilogin_uninstall(): void {
    delete_option( 'opilogin_settings' );
}