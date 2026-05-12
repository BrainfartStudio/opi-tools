<?php
/**
 * Plugin Name: OPI Bluesky
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Bluesky integration — auto-post on publish, scheduled posts, reposts, and replies.
 * Version:     0.1.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-bluesky
 */

defined( 'ABSPATH' ) || exit;

define( 'OPIBLUESKY_VERSION', '0.1.0' );
define( 'OPIBLUESKY_PATH',    plugin_dir_path( __FILE__ ) );
define( 'OPIBLUESKY_URL',     plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', function() {
    require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky.php';
    OPI_Bluesky::init();
}, 5 );

register_activation_hook( __FILE__,   'opibluesky_activate' );
register_deactivation_hook( __FILE__, 'opibluesky_deactivate' );

function opibluesky_activate(): void {
    OPI_Cron_Helper::schedule( 'opi_bluesky_process', 'opi_bluesky_1min' );
}

function opibluesky_deactivate(): void {
    OPI_Cron_Helper::unschedule( 'opi_bluesky_process' );
    OPI_Cron_Helper::unschedule( 'opi_bluesky_category_process' );
}