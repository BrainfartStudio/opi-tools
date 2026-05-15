<?php
// includes/class-opi-bluesky-category-scheduler.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Category_Scheduler {

    const OPTION_KEY = 'opibluesky_category_slots';

    public static function init(): void {
        add_action( 'opi_bluesky_category_process', [ __CLASS__, 'process_slot' ] );
    }

    // ── Settings helpers ─────────────────────────────────────────────────────

    public static function get_slots(): array {
        return get_option( self::OPTION_KEY, [] );
    }

    public static function update_slots( array $slots ): bool {
        return update_option( self::OPTION_KEY, $slots );
    }

    /**
     * Get slots for a specific category only.
     */
    public static function get_slots_for_category( int $category_id ): array {
        return array_values( array_filter(
            self::get_slots(),
            fn( $slot ) => (int) $slot['category_id'] === $category_id
        ) );
    }

    /**
     * Sanitize slot input from the settings form.
     * Each slot: [ 'time' => '19:00', 'category_id' => 5, 'days' => ['mon','wed'] ]
     */
    public static function sanitize_slots( array $input ): array {
        $sanitized = [];

        foreach ( $input as $slot ) {
            if ( empty( $slot['time'] ) || empty( $slot['category_id'] ) ) {
                continue;
            }

            $days = isset( $slot['days'] ) && is_array( $slot['days'] )
                ? array_map( 'sanitize_key', $slot['days'] )
                : [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];

            $sanitized[] = [
                'time'        => sanitize_text_field( $slot['time'] ),
                'category_id' => absint( $slot['category_id'] ),
                'days'        => $days,
            ];
        }

        usort( $sanitized, fn( $a, $b ) => strcmp( $a['time'], $b['time'] ) );

        return $sanitized;
    }

    // ── Estimated send times ─────────────────────────────────────────────────

    /**
     * Return an array of the next $count slot fire timestamps for a category.
     *
     * Walks forward from now, day by day, checking each slot's time and days.
     * Returns timestamps in ascending order — one per queue position.
     *
     * Example: category posts daily at 23:00. $count = 3 returns
     * [ tonight_at_23:00, tomorrow_at_23:00, day_after_at_23:00 ]
     * (skipping today if 23:00 has already passed).
     *
     * @param int $category_id
     * @param int $count Number of upcoming fire times to return.
     * @return int[]     Unix timestamps, length <= $count (may be shorter if no slots configured).
     */
    public static function get_next_send_times( int $category_id, int $count ): array {
        if ( $count <= 0 ) {
            return [];
        }

        $slots = self::get_slots_for_category( $category_id );
        if ( empty( $slots ) ) {
            return [];
        }

        $times    = [];
        $now      = current_datetime();
        $tz       = wp_timezone();
        $cursor   = clone $now;

        // Walk up to 365 days forward to find $count firing times.
        // In practice this terminates in $count / slots_per_day days.
        $max_days = 365;
        $day      = 0;

        while ( count( $times ) < $count && $day < $max_days ) {
            $day_key = strtolower( $cursor->format( 'D' ) );

            foreach ( $slots as $slot ) {
                if ( ! in_array( $day_key, $slot['days'], true ) ) {
                    continue;
                }

                [ $hour, $minute ] = explode( ':', $slot['time'] );

                $candidate = DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i:s',
                    $cursor->format( 'Y-m-d' ) . ' ' . sprintf( '%02d:%02d:00', (int) $hour, (int) $minute ),
                    $tz
                );

                if ( ! $candidate ) {
                    continue;
                }

                // Skip times that have already passed today.
                if ( $candidate->getTimestamp() <= $now->getTimestamp() ) {
                    continue;
                }

                $times[] = $candidate->getTimestamp();

                if ( count( $times ) >= $count ) {
                    break;
                }
            }

            // Advance to next day at midnight.
            $cursor = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $cursor->format( 'Y-m-d' ) . ' 00:00:00',
                $tz
            )->modify( '+1 day' );

            $day++;
        }

        sort( $times );

        return array_slice( $times, 0, $count );
    }

    // ── Cron ─────────────────────────────────────────────────────────────────

    /**
     * Called every minute via opi_bluesky_process.
     * Checks if any slot's time matches now and fires it.
     */
    public static function process_slot(): void {
        if ( ! OPI_Bluesky_Settings::is_configured() ) {
            return;
        }

        $slots   = self::get_slots();
        $now     = current_datetime();
        $day_key = strtolower( $now->format( 'D' ) );
        $hhmm    = $now->format( 'H:i' );

        foreach ( $slots as $slot ) {
            if ( ! in_array( $day_key, $slot['days'], true ) ) {
                continue;
            }

            if ( $slot['time'] !== $hhmm ) {
                continue;
            }

            $lock_key = 'opibluesky_slot_fired_' . md5( $slot['time'] . $slot['category_id'] );
            if ( get_transient( $lock_key ) ) {
                continue;
            }
            set_transient( $lock_key, 1, 90 );

            self::fire_slot( (int) $slot['category_id'] );
        }
    }

    /**
     * Fire the next pending post in a category, respecting queue order.
     */
    private static function fire_slot( int $category_id ): void {
        // get_queued() returns posts sorted by _bsky_queue_order ASC — first in queue is index 0.
        $posts = OPI_Bluesky_Post_Type::get_queued( $category_id );

        if ( empty( $posts ) ) {
            return;
        }

        $post   = $posts[0];
        $type   = get_post_meta( $post->ID, '_bsky_type', true ) ?: 'post';
        $result = match ( $type ) {
            'repost' => self::dispatch_repost( $post ),
            'reply'  => self::dispatch_reply( $post ),
            default  => self::dispatch_post( $post ),
        };

        if ( is_wp_error( $result ) ) {
            OPI_Bluesky_Post_Type::mark_failed( $post->ID, $result->get_error_message() );
        } else {
            OPI_Bluesky_Post_Type::mark_sent( $post->ID, $result['uri'] ?? '', $result['cid'] ?? '' );
        }
    }

    /**
     * Post plain text, attaching a link card embed if the content contains a URL.
     */
    private static function dispatch_post( \WP_Post $post ): array|\WP_Error {
        $content = $post->post_content;
        $url     = OPI_Bluesky_API::extract_first_url( $content );
        return OPI_Bluesky_API::post_text( $content, null, $url ?: null );
    }

    private static function dispatch_repost( \WP_Post $post ): array|\WP_Error {
        $ref_uri = OPI_Bluesky_Scheduler::normalize_uri( get_post_meta( $post->ID, '_bsky_ref_uri', true ) );
        $ref_cid = get_post_meta( $post->ID, '_bsky_ref_cid', true );
        $content = $post->post_content;

        if ( ! $ref_cid ) {
            $resolved = OPI_Bluesky_API::resolve_post( $ref_uri );
            if ( is_wp_error( $resolved ) ) {
                return $resolved;
            }
            $ref_uri = $resolved['uri'];
            $ref_cid = $resolved['cid'];
        }

        if ( ! empty( trim( $content ) ) ) {
            return OPI_Bluesky_API::quote_post( $content, $ref_uri, $ref_cid );
        }

        return OPI_Bluesky_API::repost( $ref_uri, $ref_cid );
    }

    private static function dispatch_reply( \WP_Post $post ): array|\WP_Error {
        $ref_uri   = OPI_Bluesky_Scheduler::normalize_uri( get_post_meta( $post->ID, '_bsky_ref_uri', true ) );
        $reply_ref = OPI_Bluesky_API::build_reply_ref( $ref_uri );

        if ( is_wp_error( $reply_ref ) ) {
            return $reply_ref;
        }

        $content = $post->post_content;
        $url     = OPI_Bluesky_API::extract_first_url( $content );

        return OPI_Bluesky_API::post_text( $content, $reply_ref, $url ?: null );
    }
}