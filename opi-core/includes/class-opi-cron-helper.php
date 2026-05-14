<?php
// includes/class-opi-cron-helper.php

defined( 'ABSPATH' ) || exit;

class OPI_Cron_Helper {

    /**
     * Register a custom cron interval.
     * Hook this to 'cron_schedules' before calling schedule().
     */
    public static function register_interval( string $key, int $seconds, string $label ): void {
        add_filter( 'cron_schedules', function( array $schedules ) use ( $key, $seconds, $label ): array {
            if ( ! isset( $schedules[ $key ] ) ) {
                $schedules[ $key ] = [
                    'interval' => $seconds,
                    'display'  => $label,
                ];
            }
            return $schedules;
        } );
    }

    /**
     * Schedule a recurring event if not already scheduled.
     */
    public static function schedule( string $hook, string $interval_key ): void {
        if ( ! wp_next_scheduled( $hook ) ) {
            wp_schedule_event( time(), $interval_key, $hook );
        }
    }

    /**
     * Unschedule a recurring event.
     */
    public static function unschedule( string $hook ): void {
        $timestamp = wp_next_scheduled( $hook );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, $hook );
        }
    }

    /**
     * Check whether a hook is currently scheduled.
     */
    public static function is_scheduled( string $hook ): bool {
        return (bool) wp_next_scheduled( $hook );
    }
}