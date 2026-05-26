<?php
// includes/class-opi-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_Settings extends OPI_Settings_Base {

    const OPTION_KEY = 'opitools_theme';

    public static function init(): void {
        add_action( 'admin_head', [ __CLASS__, 'output_css_vars' ] );
    }

    protected static function get_option_key(): string {
        return self::OPTION_KEY;
    }

    public static function get_defaults(): array {
        return [
            'accent_color'  => '#2271b1',
            'accent_text'   => '#ffffff',
            'surface_color' => '#ffffff',
            'surface_text'  => '#1d2327',
            'font_family'   => 'inherit',
        ];
    }

    public static function output_css_vars(): void {
        $t    = self::get();
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