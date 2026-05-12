<?php
// includes/class-opi-login-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_Login_Settings extends OPI_Settings_Base {

    const OPTION_KEY = 'opilogin_settings';

    protected static function get_option_key(): string {
        return self::OPTION_KEY;
    }

    public static function get_defaults(): array {
        return [
            'logo_id'        => 0,
            'logo_bg_color'  => '#ffffff',
            'logo_shape'     => 'none',
            'header_text'    => '',
            'bg_color'       => '#f0f0f1',
            'bg_image_id'    => 0,
            'form_bg_color'  => '#ffffff',
            'form_radius'    => 4,
            'form_width'     => 320,
            'form_shadow'    => true,
            'button_color'   => '#2271b1',
            'button_text'    => '#ffffff',
            'font_family'    => 'inherit',
            'custom_css'     => '',
        ];
    }

    /**
     * Override sanitize() to handle fields sanitize_base() cannot infer from type alone.
     */
    public static function sanitize( array $input ): array {
        $clean = static::sanitize_base( $input );

        // attachment IDs — absint, not text
        $clean['logo_id']     = absint( $input['logo_id']     ?? 0 );
        $clean['bg_image_id'] = absint( $input['bg_image_id'] ?? 0 );

        // logo_bg_color may be 'transparent' — sanitize_hex_color() would reject it
        $logo_bg = sanitize_text_field( $input['logo_bg_color'] ?? '' );
        $clean['logo_bg_color'] = ( $logo_bg === 'transparent' || preg_match( '/^#[0-9a-fA-F]{6}$/', $logo_bg ) )
            ? $logo_bg
            : static::get_defaults()['logo_bg_color'];

        // logo_shape whitelist
        $allowed_shapes = [ 'none', 'circle', 'square' ];
        $clean['logo_shape'] = in_array( $input['logo_shape'] ?? '', $allowed_shapes, true )
            ? $input['logo_shape']
            : 'none';

        // form_shadow arrives as checkbox — absent means false
        $clean['form_shadow'] = ! empty( $input['form_shadow'] );

        // custom_css — strip tags, not sanitize_text_field (preserves newlines/braces)
        $clean['custom_css'] = wp_strip_all_tags( $input['custom_css'] ?? '' );

        return $clean;
    }
}