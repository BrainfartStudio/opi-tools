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

    /**
     * Map a sidebar_icon_filter setting value to a CSS filter string.
     */
    private static function icon_filter( string $setting ): string {
        switch ( $setting ) {
            case 'dark':
                return 'brightness(0)';
            case 'none':
                return 'none';
            case 'light':
            default:
                return 'brightness(0) invert(1)';
        }
    }

    public static function output_css(): void {
        $s    = OPI_Admin_Settings::get();
        $font = esc_attr( $s['font_family'] ) ?: 'inherit';

        // Enqueue Google Font on every admin page so it applies globally.
        if ( $font !== 'inherit' ) {
            OPI_Google_Fonts::enqueue( $font );
        }

        // Defensive fallbacks for fields added in later commits.
        $icon_color  = $s['sidebar_icon_color']  ?? $s['sidebar_text'];
        $icon_filter = self::icon_filter( $s['sidebar_icon_filter'] ?? 'light' );
        ?>
        <style id="opi-admin-customizer">
            :root {
                --opi-admin-sidebar-bg:          <?php echo esc_attr( $s['sidebar_bg'] ); ?>;
                --opi-admin-sidebar-text:        <?php echo esc_attr( $s['sidebar_text'] ); ?>;
                --opi-admin-sidebar-icon-color:  <?php echo esc_attr( $icon_color ); ?>;
                --opi-admin-sidebar-highlight:   <?php echo esc_attr( $s['sidebar_highlight'] ); ?>;
                --opi-admin-sidebar-submenu-bg:  <?php echo esc_attr( $s['sidebar_submenu_bg'] ); ?>;
                --opi-admin-sidebar-open-bg:     <?php echo esc_attr( $s['sidebar_open_bg'] ); ?>;
                --opi-admin-sidebar-width:       <?php echo absint( $s['sidebar_width'] ); ?>px;
                --opi-admin-sidebar-icon-size:   <?php echo absint( $s['sidebar_icon_size'] ); ?>px;
                --opi-admin-sidebar-font-size:   <?php echo absint( $s['sidebar_font_size'] ); ?>px;
                --opi-admin-sidebar-icon-filter: <?php echo $icon_filter; ?>;
                --opi-admin-topbar-bg:           <?php echo esc_attr( $s['topbar_bg'] ); ?>;
                --opi-admin-topbar-text:         <?php echo esc_attr( $s['topbar_text'] ); ?>;
                --opi-admin-topbar-height:       <?php echo absint( $s['topbar_height'] ); ?>px;
                --opi-admin-topbar-font-size:    <?php echo absint( $s['topbar_font_size'] ); ?>px;
                --opi-admin-font:                <?php echo $font; ?>;
            }

            /* ── Sidebar shell ───────────────────────────────────────────── */

            #adminmenu, #adminmenuback, #adminmenuwrap {
                background: var( --opi-admin-sidebar-bg );
                width: var( --opi-admin-sidebar-width );
            }

            /* Submenu matches sidebar width so it doesn't overflow or underlap */
            #adminmenu .wp-submenu {
                width: var( --opi-admin-sidebar-width ) !important;
                min-width: 0 !important;
                box-sizing: border-box;
            }

            /* ── Content offset ──────────────────────────────────────────── */

            /* Horizontal: push content right to match sidebar width */
            #wpcontent, #wpfooter {
                margin-left: var( --opi-admin-sidebar-width ) !important;
            }

            /* Vertical: push content down to match top bar height */
            #wpcontent {
                padding-top: var( --opi-admin-topbar-height );
            }
            body.wp-toolbar {
                padding-top: var( --opi-admin-topbar-height ) !important;
            }

            /* ── Top bar ─────────────────────────────────────────────────── */

            #wpadminbar {
                background: var( --opi-admin-topbar-bg );
                height: var( --opi-admin-topbar-height ) !important;
                min-height: var( --opi-admin-topbar-height ) !important;
            }
            /* Keep inner items vertically centered as bar height changes */
            #wpadminbar .ab-top-menu > li,
            #wpadminbar .ab-top-menu > li > .ab-item {
                height: var( --opi-admin-topbar-height ) !important;
                line-height: var( --opi-admin-topbar-height ) !important;
            }
            #wpadminbar *,
            #wpadminbar .ab-item {
                color: var( --opi-admin-topbar-text ) !important;
                font-size: var( --opi-admin-topbar-font-size );
            }

            /* ── Sidebar menu items ──────────────────────────────────────── */

            /* Flex layout so icon and label stay side-by-side at any size */
            #adminmenu a.menu-top {
                display: flex !important;
                align-items: center !important;
                height: auto !important;
                padding-top: 6px !important;
                padding-bottom: 6px !important;
            }

            /* Icon column width tracks icon size so it never bleeds into text */
            #adminmenu .wp-menu-image {
                width: calc( var( --opi-admin-sidebar-icon-size ) + 16px ) !important;
                height: auto !important;
                flex-shrink: 0;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            /* Dashicon pseudo-element */
            #adminmenu .wp-menu-image:before {
                color: var( --opi-admin-sidebar-icon-color ) !important;
                font-size: var( --opi-admin-sidebar-icon-size ) !important;
                line-height: 1 !important;
                width: auto !important;
                height: auto !important;
                float: none !important;
            }

            /* Third-party SVG / <img> icons */
            #adminmenu .wp-menu-image img {
                filter: var( --opi-admin-sidebar-icon-filter ) !important;
                width: var( --opi-admin-sidebar-icon-size ) !important;
                height: var( --opi-admin-sidebar-icon-size ) !important;
            }

            /* Menu label text */
            #adminmenu a, #adminmenu .wp-menu-name {
                color: var( --opi-admin-sidebar-text ) !important;
                font-size: var( --opi-admin-sidebar-font-size );
                line-height: 1.4 !important;
            }

            /* ── Active / current item ───────────────────────────────────── */

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

            /* ── Open (expanded) item ────────────────────────────────────── */

            #adminmenu .wp-menu-open > a.menu-top,
            #adminmenu .wp-has-current-submenu > a.menu-top {
                background: var( --opi-admin-sidebar-open-bg ) !important;
            }

            /* ── Submenu ─────────────────────────────────────────────────── */

            #adminmenu .wp-submenu,
            #adminmenu .wp-menu-open .wp-submenu {
                background: var( --opi-admin-sidebar-submenu-bg ) !important;
            }
            #adminmenu .wp-submenu a {
                color: var( --opi-admin-sidebar-text ) !important;
            }
            #adminmenu .wp-submenu a:hover,
            #adminmenu .wp-submenu li.current a {
                color: #fff !important;
            }

            /* ── Font — applied globally ─────────────────────────────────── */

            body, #wpcontent, .wrap {
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