<?php
// includes/class-opi-settings-base.php

defined( 'ABSPATH' ) || exit;

abstract class OPI_Settings_Base {

    /**
     * The wp_options key for this plugin's settings.
     */
    abstract protected static function get_option_key(): string;

    /**
     * Default values. Type of each value determines sanitization strategy:
     *   string starting with '#' → sanitize_hex_color()
     *   bool                     → (bool) cast
     *   int                      → absint()
     *   string                   → sanitize_text_field()
     */
    abstract public static function get_defaults(): array;

    /**
     * Return saved settings merged with defaults.
     */
    public static function get(): array {
        return wp_parse_args(
            get_option( static::get_option_key(), [] ),
            static::get_defaults()
        );
    }

    /**
     * Persist settings to the database.
     */
    public static function update( array $settings ): bool {
        return update_option( static::get_option_key(), $settings );
    }

    /**
     * Sanitize an input array against the defaults type map.
     * Subclasses should call this and then apply domain-specific logic on top.
     */
    public static function sanitize_base( array $input ): array {
        $clean    = [];
        $defaults = static::get_defaults();

        foreach ( $defaults as $key => $default ) {
            $value = $input[ $key ] ?? $default;

            if ( is_bool( $default ) ) {
                $clean[ $key ] = (bool) $value;
            } elseif ( is_int( $default ) ) {
                $clean[ $key ] = absint( $value );
            } elseif ( is_string( $default ) && str_starts_with( $default, '#' ) ) {
                $clean[ $key ] = sanitize_hex_color( $value ) ?? $default;
            } else {
                $clean[ $key ] = sanitize_text_field( $value );
            }
        }

        return $clean;
    }

    /**
     * Subclasses override this for domain-specific sanitization.
     * Default implementation delegates to sanitize_base().
     */
    public static function sanitize( array $input ): array {
        return static::sanitize_base( $input );
    }
}