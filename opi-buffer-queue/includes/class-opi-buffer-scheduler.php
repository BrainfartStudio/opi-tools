<?php
// includes/class-opi-buffer-scheduler.php

defined( 'ABSPATH' ) || exit;

class OPI_Buffer_Scheduler {

    const CRON_HOOK     = 'opi_buffer_process';
    const CRON_INTERVAL = 'opi_buffer_15min';
    const META_KEY      = '_opi_buffer_estimated_date';

    public static function init(): void {
        OPI_Cron_Helper::register_interval( self::CRON_INTERVAL, 900, __( 'Every 15 Minutes', 'opi-buffer' ) );

        add_action( self::CRON_HOOK,       [ __CLASS__, 'process_buffer' ] );
        add_action( 'opi_buffer_changed',  [ __CLASS__, 'recalculate_all_dates' ] );
        add_action( 'transition_post_status', [ __CLASS__, 'handle_scheduled_post_change' ], 10, 3 );
    }

    public static function activate(): void {
        OPI_Cron_Helper::schedule( self::CRON_HOOK, self::CRON_INTERVAL );
    }

    public static function deactivate(): void {
        OPI_Cron_Helper::unschedule( self::CRON_HOOK );
    }

    // -------------------------------------------------------------------------
    // Interval / threshold helpers
    // -------------------------------------------------------------------------

    public static function get_interval_for_count( int $count ): array {
        $limits = OPI_Buffer_Settings::get_buffer_limits();

        // Sorted ascending by min_posts — walk backwards to find highest match.
        $matched = null;
        foreach ( $limits as $limit ) {
            if ( $count >= $limit['min_posts'] ) {
                $matched = $limit;
            }
        }

        return $matched ?? [ 'interval_days' => 3, 'time' => '08:00' ];
    }

    // -------------------------------------------------------------------------
    // Date calculation
    // -------------------------------------------------------------------------

    public static function get_last_published_date(): int {
        $posts = get_posts( [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        return ! empty( $posts ) ? (int) strtotime( $posts[0]->post_date ) : current_time( 'timestamp' );
    }

    public static function get_scheduled_post_timestamps(): array {
        $posts = get_posts( [
            'post_type'      => 'post',
            'post_status'    => 'future',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ] );

        $timestamps = array_map( fn( $p ) => (int) strtotime( $p->post_date ), $posts );
        sort( $timestamps );

        return $timestamps;
    }

    /**
     * Core anchor-based scheduling algorithm.
     * Returns [ post_id => timestamp ] for every buffer post.
     */
    public static function calculate_buffer_dates(): array {
        $buffer_posts = OPI_Buffer_Manager::get_buffer_posts();

        if ( empty( $buffer_posts ) ) {
            return [];
        }

        $count         = count( $buffer_posts );
        $interval      = self::get_interval_for_count( $count );
        $interval_days = (int) $interval['interval_days'];
        $publish_time  = $interval['time'];

        $last_anchor      = self::get_last_published_date();
        $scheduled_stamps = self::get_scheduled_post_timestamps();

        [ $hour, $minute ] = explode( ':', $publish_time );

        $calculated = [];

        foreach ( $buffer_posts as $post ) {
            $next_date = strtotime( "+{$interval_days} days", $last_anchor );

            // If any scheduled post falls between anchor and next_date, use it as new anchor.
            foreach ( $scheduled_stamps as $scheduled_ts ) {
                if ( $scheduled_ts > $last_anchor && $scheduled_ts <= $next_date ) {
                    $last_anchor = $scheduled_ts;
                    $next_date   = strtotime( "+{$interval_days} days", $last_anchor );
                }
            }

            // Apply configured publish time.
            $next_date = strtotime( date( 'Y-m-d', $next_date ) . " {$hour}:{$minute}:00" );

            $calculated[ $post->ID ] = $next_date;
            $last_anchor             = $next_date;
        }

        return $calculated;
    }

    public static function recalculate_all_dates(): void {
        foreach ( self::calculate_buffer_dates() as $post_id => $timestamp ) {
            update_post_meta( $post_id, self::META_KEY, $timestamp );
        }
    }

    public static function get_estimated_date( int $post_id ): int|false {
        $val = get_post_meta( $post_id, self::META_KEY, true );
        return $val !== '' ? (int) $val : false;
    }

    // -------------------------------------------------------------------------
    // Cron processor
    // -------------------------------------------------------------------------

    public static function process_buffer(): void {
        $buffer_posts = OPI_Buffer_Manager::get_buffer_posts();

        if ( empty( $buffer_posts ) ) {
            return;
        }

        $calculated = self::calculate_buffer_dates();

        foreach ( $calculated as $post_id => $timestamp ) {
            update_post_meta( $post_id, self::META_KEY, $timestamp );
        }

        $first    = $buffer_posts[0];
        $pub_time = $calculated[ $first->ID ] ?? false;

        if ( $pub_time && $pub_time <= current_time( 'timestamp' ) ) {
            wp_update_post( [
                'ID'            => $first->ID,
                'post_status'   => 'publish',
                'post_date'     => date( 'Y-m-d H:i:s', $pub_time ),
                'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $pub_time ),
            ] );

            delete_post_meta( $first->ID, self::META_KEY );
            do_action( 'opi_buffer_changed' );
        }
    }

    // -------------------------------------------------------------------------
    // Hooks
    // -------------------------------------------------------------------------

    public static function handle_scheduled_post_change( string $new_status, string $old_status, \WP_Post $post ): void {
        if ( $post->post_type !== 'post' ) {
            return;
        }

        if ( $new_status === 'future' || $old_status === 'future' ) {
            do_action( 'opi_buffer_changed' );
        }
    }
}