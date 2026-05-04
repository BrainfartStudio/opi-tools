<?php
// includes/class-opi-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_Settings {

    const OPTION_KEY = 'opitools_theme';

    public static function init(): void {
        add_action( 'admin_init',       [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_head',       [ __CLASS__, 'output_css_vars' ] );
    }

    public static function get_defaults(): array {
        return [
            'accent_color'    => '#2271b1',
            'accent_text'     => '#ffffff',
            'surface_color'   => '#ffffff',
            'surface_text'    => '#1d2327',
            'font_family'     => 'inherit',
        ];
    }

    public static function get(): array {
        $saved = get_option( self::OPTION_KEY, [] );
        return wp_parse_args( $saved, self::get_defaults() );
    }

    public static function register_settings(): void {
        register_setting(
            'opitools_theme_group',
            self::OPTION_KEY,
            [ 'sanitize_callback' => [ __CLASS__, 'sanitize' ] ]
        );
    }

    public static function sanitize( mixed $input ): array {
        $clean    = [];
        $defaults = self::get_defaults();

        foreach ( $defaults as $key => $default ) {
            if ( $key === 'font_family' ) {
                $clean[ $key ] = isset( $input[ $key ] )
                    ? sanitize_text_field( $input[ $key ] )
                    : $default;
            } else {
                // Expect a hex color
                $val           = $input[ $key ] ?? $default;
                $clean[ $key ] = sanitize_hex_color( $val ) ?? $default;
            }
        }

        return $clean;
    }

    public static function output_css_vars(): void {
        $t = self::get();
        $font = esc_attr( $t['font_family'] );
        ?>
        <style id="opi-tools-theme-vars">
            :root {
                --opi-accent:       <?php echo esc_attr( $t['accent_color'] ); ?>;
                --opi-accent-text:  <?php echo esc_attr( $t['accent_text'] ); ?>;
                --opi-surface:      <?php echo esc_attr( $t['surface_color'] ); ?>;
                --opi-surface-text: <?php echo esc_attr( $t['surface_text'] ); ?>;
                --opi-font:         <?php echo $font ?: 'inherit'; ?>;
            }
        </style>
        <?php
    }
}