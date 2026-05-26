<?php
/**
 * Plugin Name: OPI Tools Core
 * Plugin URI:  https://github.com/BrainfartStudio/opi-tools
 * Description: Core framework for the OPI Tools plugin suite.
 * Version:     1.0.0
 * Author:      Mitchell Opitz
 * License:     GPL-2.0-or-later
 * Text Domain: opi-core
 */

defined( 'ABSPATH' ) || exit;

define( 'OPI_CORE_VERSION', '1.0.0' );
define( 'OPI_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPI_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once OPI_CORE_PATH . 'vendor/autoload.php';

add_action( 'plugins_loaded', function() {
    require_once OPI_CORE_PATH . 'includes/class-opi-tools.php';
    OPI_Tools::init();
}, 1 );

add_action( 'plugins_loaded', function() {
    do_action( 'opi_tools_register_plugins' );
}, 20 );