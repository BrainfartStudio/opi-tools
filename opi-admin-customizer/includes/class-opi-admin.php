<?php
// includes/class-opi-admin.php

defined( 'ABSPATH' ) || exit;

class OPI_Admin {

    public static function init(): void {
        require_once OPIADMIN_PATH . 'includes/class-opi-admin-settings.php';

        add_action( 'admin_head',                  [ __CLASS__, 'output_css' ] );
        add_action( 'admin_enqueue_scripts',       [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'opi_tools_register_plugins',  [ __CLASS__, 'register_with_core' ] );
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-admin',
            'Admin Customizer',
            OPIADMIN_VERSION,
            [ __CLASS__, 'render_page' ],
            'manage_options',
            [ __CLASS__, 'widget_data' ],
            [ __CLASS__, 'health_data' ]
        );
    }

    public static function render_page(): void {
        require_once OPIADMIN_PATH . 'includes/views/settings-page.php';
    }

    public static function enqueue_assets( string $hook ): void {
        if ( $hook !== 'toplevel_page_opi-admin' ) {
            return;
        }

        wp_enqueue_script(
            'opi-admin-preview',
            OPIADMIN_URL . 'assets/js/opi-admin-preview.js',
            [ 'opi-admin' ],
            OPIADMIN_VERSION,
            true
        );
    }

    public static function output_css(): void {
        $s    = OPI_Admin_Settings::get();
        $font = esc_attr( $s['font_family'] ) ?: 'inherit';
        ?>
        <style id="opi-admin-customizer">
            :root {
                --opi-admin-sidebar-bg:        <?php echo esc_attr( $s['sidebar_bg'] ); ?>;
                --opi-admin-sidebar-text:      <?php echo esc_attr( $s['sidebar_text'] ); ?>;
                --opi-admin-sidebar-highlight: <?php echo esc_attr( $s['sidebar_highlight'] ); ?>;
                --opi-admin-sidebar-width:     <?php echo absint( $s['sidebar_width'] ); ?>px;
                --opi-admin-sidebar-icon-size: <?php echo absint( $s['sidebar_icon_size'] ); ?>px;
                --opi-admin-sidebar-font-size: <?php echo absint( $s['sidebar_font_size'] ); ?>px;
                --opi-admin-topbar-bg:         <?php echo esc_attr( $s['topbar_bg'] ); ?>;
                --opi-admin-topbar-text:       <?php echo esc_attr( $s['topbar_text'] ); ?>;
                --opi-admin-topbar-height:     <?php echo absint( $s['topbar_height'] ); ?>px;
                --opi-admin-topbar-font-size:  <?php echo absint( $s['topbar_font_size'] ); ?>px;
                --opi-admin-font:              <?php echo $font; ?>;
            }

            #adminmenu, #adminmenuback, #adminmenuwrap {
                background: var( --opi-admin-sidebar-bg );
                width: var( --opi-admin-sidebar-width );
            }
            #adminmenu a, #adminmenu .wp-menu-name {
                color: var( --opi-admin-sidebar-text ) !important;
                font-size: var( --opi-admin-sidebar-font-size );
            }
            #adminmenu .wp-menu-image:before {
                color: var( --opi-admin-sidebar-text ) !important;
                font-size: var( --opi-admin-sidebar-icon-size ) !important;
            }
            #adminmenu .current a.menu-top,
            #adminmenu .wp-has-current-submenu .wp-submenu-head,
            #adminmenu a.menu-top:hover {
                background: var( --opi-admin-sidebar-highlight ) !important;
                color: #fff !important;
            }
            #adminmenu .current .wp-menu-image:before,
            #adminmenu a.menu-top:hover .wp-menu-image:before {
                color: #fff !important;
            }
            #wpadminbar {
                background: var( --opi-admin-topbar-bg );
                min-height: var( --opi-admin-topbar-height );
            }
            #wpadminbar *,
            #wpadminbar .ab-item {
                color: var( --opi-admin-topbar-text ) !important;
                font-size: var( --opi-admin-topbar-font-size );
            }
            #wpadminmain, body, .wrap {
                font-family: var( --opi-admin-font );
            }
            <?php echo wp_strip_all_tags( $s['custom_css'] ); ?>
        </style>
        <?php
    }

    public static function widget_data(): array {
        return [
            'status' => 'ok',
            'label'  => 'Admin Customizer',
            'value'  => 'Active',
        ];
    }

    public static function health_data(): array {
        return [ 'severity' => 'ok' ];
    }
}