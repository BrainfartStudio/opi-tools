<?php
/**
 * Plugin Name: OPI Bluesky
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Bluesky integration — auto-post on publish, scheduled posts, reposts, and replies.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-bluesky
 */

defined( 'ABSPATH' ) || exit;

define( 'OPI_BLUESKY_VERSION', '1.0.0' );
define( 'OPI_BLUESKY_PATH',    plugin_dir_path( __FILE__ ) );
define( 'OPI_BLUESKY_URL',     plugin_dir_url( __FILE__ ) );

function opi_bluesky_check_core(): void {
    if ( class_exists( 'OPI_Tools' ) ) {
        return;
    }

    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'OPI Bluesky requires OPI Tools Core to be installed and active.', 'opi-bluesky' );
        echo '</p></div>';
    } );
}
add_action( 'plugins_loaded', 'opi_bluesky_check_core', 2 );

add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'OPI_Tools' ) ) {
        return;
    }

    require_once OPI_BLUESKY_PATH . 'includes/class-opi-bluesky.php';
    OPI_Bluesky::init();
}, 5 );

register_activation_hook( __FILE__,   [ 'OPI_Bluesky', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'OPI_Bluesky', 'deactivate' ] );