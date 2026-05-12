<?php
/**
 * Plugin Name: OPI Tools Core
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Core framework for the OPI Tools plugin suite.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-tools
 */

defined( 'ABSPATH' ) || exit;

define( 'OPITOOLS_VERSION', '1.0.0' );
define( 'OPITOOLS_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPITOOLS_URL', plugin_dir_url( __FILE__ ) );

require_once OPITOOLS_PATH . 'vendor/autoload.php';

add_action( 'plugins_loaded', function() {
    require_once OPITOOLS_PATH . 'includes/class-opi-tools.php';
    OPI_Tools::init();
}, 1 );