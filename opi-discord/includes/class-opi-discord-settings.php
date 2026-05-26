<?php
// includes/class-opi-discord-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_Discord_Settings extends OPI_Settings_Base {

    const OPTION_KEY = 'opidiscord_settings';

    protected static function get_option_key(): string {
        return self::OPTION_KEY;
    }

    public static function get_defaults(): array {
        return [
            'webhook_url'  => '',
            'embed_color'  => '#7289DA',
            'show_excerpt' => true,
        ];
    }

    public static function sanitize( array $input ): array {
        $clean = static::sanitize_base( $input );

        if ( ! empty( $clean['webhook_url'] ) ) {
            $clean['webhook_url'] = OPI_Crypto::encrypt( esc_url_raw( $clean['webhook_url'] ) );
        }

        return $clean;
    }

    public static function get_webhook_url(): string {
        $settings = static::get();
        return OPI_Crypto::decrypt( $settings['webhook_url'] );
    }
}