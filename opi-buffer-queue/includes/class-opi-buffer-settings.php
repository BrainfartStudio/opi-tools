<?php
// includes/class-opi-buffer-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_Buffer_Settings extends OPI_Settings_Base {

    const OPTION_KEY = 'opi_buffer_limits';

    protected static function get_option_key(): string {
        return self::OPTION_KEY;
    }

    /**
     * Default: one rule — 0+ posts, every 3 days at 08:00.
     * The rules array is not a flat key/value map so sanitize() is fully overridden.
     * get_defaults() returns the default ruleset wrapped to satisfy the base contract.
     */
    public static function get_defaults(): array {
        return [
            'rules' => [
                [
                    'min_posts'     => 0,
                    'interval_days' => 3,
                    'time'          => '08:00',
                ],
            ],
        ];
    }

    /**
     * Fully override sanitize() — the rules array structure cannot be handled
     * by sanitize_base()'s type-map approach.
     */
    public static function sanitize( array $input ): array {
        $rules = $input['rules'] ?? $input; // accept both wrapped and bare arrays

        if ( ! is_array( $rules ) ) {
            return static::get_defaults();
        }

        $clean = [];

        foreach ( $rules as $rule ) {
            if ( ! isset( $rule['min_posts'], $rule['interval_days'], $rule['time'] ) ) {
                continue;
            }

            $interval = absint( $rule['interval_days'] );
            if ( $interval < 1 ) {
                $interval = 1;
            }

            // Validate HH:MM format.
            $time = sanitize_text_field( $rule['time'] );
            if ( ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
                $time = '08:00';
            }

            $clean[] = [
                'min_posts'     => absint( $rule['min_posts'] ),
                'interval_days' => $interval,
                'time'          => $time,
            ];
        }

        if ( empty( $clean ) ) {
            return static::get_defaults();
        }

        // Sort ascending by min_posts so threshold logic works correctly.
        usort( $clean, fn( $a, $b ) => $a['min_posts'] - $b['min_posts'] );

        return [ 'rules' => $clean ];
    }

    /**
     * Return the rules array directly (unwrapped) for convenience.
     */
    public static function get_buffer_limits(): array {
        $saved = static::get();
        return $saved['rules'] ?? static::get_defaults()['rules'];
    }

    /**
     * Persist a rules array (unwrapped).
     */
    public static function update_buffer_limits( array $rules ): bool {
        $sanitized = static::sanitize( [ 'rules' => $rules ] );
        return static::update( $sanitized );
    }
}