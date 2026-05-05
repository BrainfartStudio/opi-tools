<?php
// includes/class-opi-admin-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_Admin_Settings {

    public static function get_defaults(): array {
        return [
            'sidebar_bg'        => '#23282d',
            'sidebar_text'      => '#a7aaad',
            'sidebar_highlight' => '#2271b1',
            'topbar_bg'         => '#23282d',
            'topbar_text'       => '#a7aaad',
            'font_family'       => 'inherit',
            'custom_css'        => '',
        ];
    }

    public static function get(): array {
        $saved = get_option( OPI_Admin::OPTION_KEY, [] );
        return wp_parse_args( $saved, self::get_defaults() );
    }

    public static function save( array $input ): void {
        $clean    = [];
        $defaults = self::get_defaults();

        foreach ( [ 'sidebar_bg', 'sidebar_text', 'sidebar_highlight', 'topbar_bg', 'topbar_text' ] as $key ) {
            $clean[ $key ] = sanitize_hex_color( $input[ $key ] ?? '' ) ?? $defaults[ $key ];
        }

        $clean['font_family'] = sanitize_text_field( $input['font_family'] ?? 'inherit' );
        $clean['custom_css']  = wp_strip_all_tags( $input['custom_css'] ?? '' );

        update_option( OPI_Admin::OPTION_KEY, $clean );
    }
}