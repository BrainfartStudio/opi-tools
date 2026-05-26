<?php
/**
 * Plugin Name: OPI Discord
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Sends Discord notifications when posts are published.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-discord
 */

defined( 'ABSPATH' ) || exit;

define( 'OPIDISCORD_VERSION', '1.0.0' );
define( 'OPIDISCORD_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPIDISCORD_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', function() {
    require_once OPIDISCORD_PATH . 'includes/class-opi-discord-settings.php';
    require_once OPIDISCORD_PATH . 'includes/class-opi-discord.php';
    OPI_Discord::init();
}, 5 );