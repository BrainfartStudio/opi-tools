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
            'identifier'           => '',
            'app_password'         => '',
            'auto_post_on_publish' => true,
            'category_slots'       => [],
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

        $app_password = sanitize_text_field( $input['app_password'] ?? '' );

        if ( ! empty( $app_password ) ) {
            $app_password = OPI_Crypto::encrypt( $app_password );
        } else {
            $existing     = self::get();
            $app_password = $existing['app_password'] ?? '';
        }

        return [
            'identifier'           => sanitize_text_field( $input['identifier'] ?? '' ),
            'app_password'         => $app_password,
            'auto_post_on_publish' => ! empty( $input['auto_post_on_publish'] ),
            'category_slots'       => $input['category_slots'] ?? $defaults['category_slots'],
        ];
    }

    public static function get_identifier(): string {
        return self::get()['identifier'] ?? '';
    }

    /**
     * Returns the decrypted app password for use in API calls.
     */
    public static function get_app_password(): string {
        $encrypted = self::get()['app_password'] ?? '';
        return OPI_Crypto::decrypt( $encrypted );
    }

    public static function is_configured(): bool {
        $settings = self::get();
        return ! empty( $settings['identifier'] ) && ! empty( $settings['app_password'] );
    }

    public static function auto_post_enabled(): bool {
        return (bool) ( self::get()['auto_post_on_publish'] ?? true );
    }
}