<?php
/**
 * Plugin Name: OPI RSS Aggregator
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: RSS feed aggregator for the OPI Tools plugin suite.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-rss
 */

defined( 'ABSPATH' ) || exit;

define( 'OPI_RSS_VERSION', '1.0.0' );
define( 'OPI_RSS_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPI_RSS_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, 'opirss_activate' );

function opirss_activate(): void {
    require_once OPI_RSS_PATH . 'includes/class-opi-rss-db.php';
    OPI_RSS_DB::create_tables();
}

register_deactivation_hook( __FILE__, 'opirss_deactivate' );

function opirss_deactivate(): void {
    require_once OPI_RSS_PATH . 'includes/class-opi-rss.php';
    OPI_RSS::deactivate();
}

register_uninstall_hook( __FILE__, 'opirss_uninstall' );

function opirss_uninstall(): void {
    // Tables are intentionally left in place on uninstall to preserve feed data.
    // Remove if you want clean uninstall: global $wpdb; $wpdb->query("DROP TABLE...")
}

add_action( 'plugins_loaded', function() {
    require_once OPI_RSS_PATH . 'includes/class-opi-rss-db.php';
    require_once OPI_RSS_PATH . 'includes/class-opi-rss.php';
    OPI_RSS::init();
}, 5 );