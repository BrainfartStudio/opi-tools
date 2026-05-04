<?php
// includes/class-opi-discord-queue.php

defined( 'ABSPATH' ) || exit;

class OPI_Discord_Queue {

    const HOOK      = 'opi_discord_send_queued_post';
    const QUEUE_KEY = 'opidiscord_bulk_queue';

    public static function init(): void {
        add_action( self::HOOK, [ __CLASS__, 'process_next' ] );
    }

    public static function enqueue( array $post_ids ): void {
        update_option( self::QUEUE_KEY, $post_ids, false );
        self::schedule_next();
    }

    public static function process_next(): void {
        $queue = get_option( self::QUEUE_KEY, [] );

        if ( empty( $queue ) ) return;

        $post_id = array_shift( $queue );
        $post    = get_post( $post_id );

        if ( $post ) {
            OPI_Discord::send( $post );
        }

        if ( ! empty( $queue ) ) {
            update_option( self::QUEUE_KEY, $queue, false );
            self::schedule_next();
        } else {
            delete_option( self::QUEUE_KEY );
        }
    }

    private static function schedule_next(): void {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_single_event( time() + 2, self::HOOK );
        }
    }
}