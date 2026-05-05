<?php
// includes/class-opi-bluesky-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Settings {

    const OPTION_KEY = 'opibluesky_settings';

    public static function init(): void {
        // Saves handled directly in settings view.
    }

    public static function get_defaults(): array {
        return [
            'identifier'          => '',
            'app_password'        => '',
            'auto_post_on_publish' => true,
            'category_slots'      => [], // groundwork for category scheduler
        ];
    }

    public static function get(): array {
        return wp_parse_args( get_option( self::OPTION_KEY, [] ), self::get_defaults() );
    }

    public static function update( array $settings ): bool {
        return update_option( self::OPTION_KEY, $settings );
    }

    public static function sanitize( array $input ): array {
        $defaults = self::get_defaults();

        return [
            'identifier'           => sanitize_text_field( $input['identifier'] ?? '' ),
            'app_password'         => sanitize_text_field( $input['app_password'] ?? '' ),
            'auto_post_on_publish' => ! empty( $input['auto_post_on_publish'] ),
            'category_slots'       => $input['category_slots'] ?? $defaults['category_slots'],
        ];
    }

    public static function get_identifier(): string {
        return self::get()['identifier'] ?? '';
    }

    public static function get_app_password(): string {
        return self::get()['app_password'] ?? '';
    }

    public static function is_configured(): bool {
        $settings = self::get();
        return ! empty( $settings['identifier'] ) && ! empty( $settings['app_password'] );
    }

    public static function auto_post_enabled(): bool {
        return (bool) ( self::get()['auto_post_on_publish'] ?? true );
    }
}