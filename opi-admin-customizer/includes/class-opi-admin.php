<?php
// includes/class-opi-admin.php

defined( 'ABSPATH' ) || exit;

class OPI_Admin {

    const OPTION_KEY = 'opiadmin_settings';

    public static function init(): void {
        require_once OPIADMIN_PATH . 'includes/class-opi-admin-settings.php';

        add_action( 'admin_head',                  [ __CLASS__, 'output_css' ] );
        add_action( 'opi_tools_register_plugins',  [ __CLASS__, 'register_with_core' ] );
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-admin',
            'Admin Customizer',
            OPIADMIN_VERSION,
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page(): void {
        require_once OPIADMIN_PATH . 'includes/views/settings-page.php';
    }

    public static function output_css(): void {
        $s    = OPI_Admin_Settings::get();
        $font = esc_attr( $s['font_family'] ) ?: 'inherit';
        ?>
        <style id="opi-admin-customizer">
            #adminmenu, #adminmenuback, #adminmenuwrap {
                background: <?php echo esc_attr( $s['sidebar_bg'] ); ?>;
            }
            #adminmenu a, #adminmenu .wp-menu-name {
                color: <?php echo esc_attr( $s['sidebar_text'] ); ?> !important;
            }
            #adminmenu .wp-menu-image:before {
                color: <?php echo esc_attr( $s['sidebar_text'] ); ?> !important;
            }
            #adminmenu .current a.menu-top,
            #adminmenu .wp-has-current-submenu .wp-submenu-head,
            #adminmenu a.menu-top:hover {
                background: <?php echo esc_attr( $s['sidebar_highlight'] ); ?> !important;
                color: #fff !important;
            }
            #adminmenu .current .wp-menu-image:before,
            #adminmenu a.menu-top:hover .wp-menu-image:before {
                color: #fff !important;
            }
            #wpadminbar {
                background: <?php echo esc_attr( $s['topbar_bg'] ); ?>;
            }
            #wpadminbar *,
            #wpadminbar .ab-item {
                color: <?php echo esc_attr( $s['topbar_text'] ); ?> !important;
            }
            #wpadminmain, body, .wrap {
                font-family: <?php echo $font; ?>;
            }
            <?php echo wp_strip_all_tags( $s['custom_css'] ); ?>
        </style>
        <?php
    }
}