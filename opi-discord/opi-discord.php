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

define( 'OPI_DISCORD_VERSION', '1.0.0' );
define( 'OPI_DISCORD_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPI_DISCORD_URL', plugin_dir_url( __FILE__ ) );

function opi_discord_check_core(): void {
    if ( class_exists( 'OPI_Tools' ) ) {
        return;
    }

    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'OPI Discord requires OPI Tools Core to be installed and active.', 'opi-discord' );
        echo '</p></div>';
    } );
}
add_action( 'plugins_loaded', 'opi_discord_check_core', 2 );

add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'OPI_Tools' ) ) {
        return;
    }

    require_once OPI_DISCORD_PATH . 'includes/class-opi-discord-settings.php';
    require_once OPI_DISCORD_PATH . 'includes/class-opi-discord.php';
    OPI_Discord::init();
}, 5 );