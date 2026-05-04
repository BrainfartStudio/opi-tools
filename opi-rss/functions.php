<?php
/**
 * Plugin Name: OPI RSS Aggregator
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: RSS feed aggregator for OPI Tools.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-rss
 */

defined( 'ABSPATH' ) || exit;

define( 'OPIRSS_VERSION', '1.0.0' );
define( 'OPIRSS_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPIRSS_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', function() {
    require_once OPIRSS_PATH . 'includes/database.php';
    require_once OPIRSS_PATH . 'includes/functions.php';
    require_once OPIRSS_PATH . 'includes/cron.php';
    require_once OPIRSS_PATH . 'includes/admin.php';
    require_once OPIRSS_PATH . 'includes/blocks.php';

    add_action( 'opi_tools_register_plugins', 'opirss_register_with_core' );
}, 5 );

function opirss_register_with_core(): void {
    OPI_Tools::register_plugin(
        'opi-rss',
        'RSS Aggregator',
        OPIRSS_VERSION,
        'opirss_render_page'
    );
}

register_activation_hook( __FILE__, 'opirss_activate' );
register_deactivation_hook( __FILE__, 'opirss_deactivate' );

function opirss_activate(): void {
    opirss_create_tables();
    if ( ! wp_next_scheduled( 'opirss_fetch_feeds' ) ) {
        wp_schedule_event( time(), 'thirty_minutes', 'opirss_fetch_feeds' );
    }
}

function opirss_deactivate(): void {
    wp_clear_scheduled_hook( 'opirss_fetch_feeds' );
}

add_filter( 'cron_schedules', 'opirss_cron_schedule' );
function opirss_cron_schedule( array $schedules ): array {
    $schedules['thirty_minutes'] = [
        'interval' => 1800,
        'display'  => __( 'Every 30 Minutes', 'opi-rss' ),
    ];
    return $schedules;
}