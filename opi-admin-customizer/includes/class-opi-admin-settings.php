<?php
// includes/class-opi-admin-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_Admin_Settings extends OPI_Settings_Base {

    const OPTION_KEY = 'opiadmin_settings';

    protected static function get_option_key(): string {
        return self::OPTION_KEY;
    }

    public static function get_defaults(): array {
        return [
            'sidebar_bg'         => '#23282d',
            'sidebar_text'       => '#a7aaad',
            'sidebar_icon_color' => '#a7aaad',
            'sidebar_highlight'  => '#2271b1',
            'sidebar_submenu_bg' => '#32373c',
            'sidebar_open_bg'    => '#191e23',
            'sidebar_icon_filter'=> 'light',
            'sidebar_width'      => 160,
            'sidebar_icon_size'  => 20,
            'sidebar_font_size'  => 13,
            'topbar_bg'          => '#23282d',
            'topbar_text'        => '#a7aaad',
            'topbar_height'      => 32,
            'topbar_font_size'   => 13,
            'font_family'        => 'inherit',
            'custom_css'         => '',
        ];
    }

    public static function sanitize( array $input ): array {
        $clean                        = static::sanitize_base( $input );
        $clean['custom_css']          = wp_strip_all_tags( $input['custom_css'] ?? '' );
        $allowed_filters              = [ 'light', 'dark', 'none' ];
        $clean['sidebar_icon_filter'] = in_array( $input['sidebar_icon_filter'] ?? '', $allowed_filters, true )
            ? $input['sidebar_icon_filter']
            : 'light';
        return $clean;
    }
}