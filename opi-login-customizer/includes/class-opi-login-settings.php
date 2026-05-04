<?php
// includes/class-opi-login-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_Login_Settings {

    public static function get_defaults(): array {
        return [
            'logo_id'          => 0,
            'header_text'      => '',
            'bg_color'         => '#f0f0f1',
            'bg_image_id'      => 0,
            'form_bg_color'    => '#ffffff',
            'form_radius'      => '4',
            'form_shadow'      => true,
            'button_color'     => '#2271b1',
            'button_text'      => '#ffffff',
            'font_family'      => 'inherit',
            'custom_css'       => '',
        ];
    }

    public static function get(): array {
        $saved = get_option( OPI_Login::OPTION_KEY, [] );
        return wp_parse_args( $saved, self::get_defaults() );
    }

    public static function save( array $input ): void {
        $clean    = [];
        $defaults = self::get_defaults();

        $clean['logo_id']       = absint( $input['logo_id'] ?? 0 );
        $clean['header_text']   = sanitize_text_field( $input['header_text'] ?? '' );
        $clean['bg_color']      = sanitize_hex_color( $input['bg_color'] ?? '' ) ?? $defaults['bg_color'];
        $clean['bg_image_id']   = absint( $input['bg_image_id'] ?? 0 );
        $clean['form_bg_color'] = sanitize_hex_color( $input['form_bg_color'] ?? '' ) ?? $defaults['form_bg_color'];
        $clean['form_radius']   = absint( $input['form_radius'] ?? $defaults['form_radius'] );
        $clean['form_shadow']   = isset( $input['form_shadow'] );
        $clean['button_color']  = sanitize_hex_color( $input['button_color'] ?? '' ) ?? $defaults['button_color'];
        $clean['button_text']   = sanitize_hex_color( $input['button_text'] ?? '' ) ?? $defaults['button_text'];
        $clean['font_family']   = sanitize_text_field( $input['font_family'] ?? 'inherit' );
        $clean['custom_css']    = wp_strip_all_tags( $input['custom_css'] ?? '' );

        update_option( OPI_Login::OPTION_KEY, $clean );
    }
}