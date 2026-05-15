<?php
// includes/class-opi-bluesky-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Settings extends OPI_Settings_Base {

    const OPTION_KEY = 'opibluesky_settings';

    public static function init(): void {
        // Saves handled directly in settings view.
    }

    protected static function get_option_key(): string {
        return self::OPTION_KEY;
    }

    public static function get_defaults(): array {
        return [
            'identifier'           => '',
            'app_password'         => '',
            'auto_post_on_publish' => true,
        ];
    }

    /**
     * Domain-specific sanitize: encrypts app password, preserves existing if blank.
     */
    public static function sanitize( array $input ): array {
        $app_password = sanitize_text_field( $input['app_password'] ?? '' );

        if ( ! empty( $app_password ) ) {
            $app_password = OPI_Crypto::encrypt( $app_password );
        } else {
            $existing     = static::get();
            $app_password = $existing['app_password'] ?? '';
        }

        return [
            'identifier'           => sanitize_text_field( $input['identifier'] ?? '' ),
            'app_password'         => $app_password,
            'auto_post_on_publish' => ! empty( $input['auto_post_on_publish'] ),
        ];
    }

    public static function get_identifier(): string {
        return static::get()['identifier'] ?? '';
    }

    public static function get_app_password(): string {
        $encrypted = static::get()['app_password'] ?? '';
        return OPI_Crypto::decrypt( $encrypted );
    }

    public static function is_configured(): bool {
        $settings = static::get();
        return ! empty( $settings['identifier'] ) && ! empty( $settings['app_password'] );
    }

    public static function auto_post_enabled(): bool {
        return (bool) ( static::get()['auto_post_on_publish'] ?? true );
    }
}