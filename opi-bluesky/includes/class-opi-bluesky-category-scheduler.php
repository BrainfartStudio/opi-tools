<?php
// includes/class-opi-bluesky-category-scheduler.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Category_Scheduler {

    const OPTION_KEY = 'opibluesky_category_slots';

    public static function init(): void {
        OPI_Cron_Helper::register_interval( 'opi_bluesky_daily', DAY_IN_SECONDS, __( 'Once Daily', 'opi-bluesky' ) );

        add_action( 'opi_bluesky_category_process',          [ __CLASS__, 'process_slot' ] );
        add_action( 'update_option_' . self::OPTION_KEY,     [ __CLASS__, 'reschedule_cron' ] );
    }

    // ── Settings helpers ─────────────────────────────────────────────────────

    public static function get_slots(): array {
        return get_option( self::OPTION_KEY, [] );
    }

    public static function update_slots( array $slots ): bool {
        return update_option( self::OPTION_KEY, $slots );
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

    // ── Cron ─────────────────────────────────────────────────────────────────

    /**
     * Called every minute via opi_bluesky_process.
     * Checks if any slot's time has just passed and fires it.
     */
    public static function process_slot(): void {
        if ( ! OPI_Bluesky_Settings::is_configured() ) {
            return;
        }

        $slots   = self::get_slots();
        $now     = current_time( 'timestamp' );
        $day_key = strtolower( date( 'D', $now ) );
        $hhmm    = date( 'H:i', $now );

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
     * Fire the next pending post in a category.
     */
    private static function fire_slot( int $category_id ): void {
        $posts = get_posts( [
            'post_type'   => OPI_Bluesky_Post_Type::CPT,
            'post_status' => 'publish',
            'numberposts' => 1,
            'tax_query'   => [
                [
                    'taxonomy' => 'bsky_post_category',
                    'field'    => 'term_id',
                    'terms'    => $category_id,
                ],
            ],
            'meta_query'  => [
                [
                    'key'     => '_bsky_sent',
                    'value'   => '0',
                    'compare' => '=',
                ],
            ],
            'orderby'     => 'date',
            'order'       => 'ASC',
        ] );

        if ( empty( $posts ) ) {
            return;
        }

        $post   = $posts[0];
        $type   = get_post_meta( $post->ID, '_bsky_type', true ) ?: 'post';
        $result = match ( $type ) {
            'repost' => self::dispatch_repost( $post ),
            'reply'  => self::dispatch_reply( $post ),
            default  => OPI_Bluesky_API::post_text( $post->post_content ),
        };

        if ( is_wp_error( $result ) ) {
            OPI_Bluesky_Post_Type::mark_failed( $post->ID, $result->get_error_message() );
        } else {
            OPI_Bluesky_Post_Type::mark_sent( $post->ID, $result['uri'] ?? '', $result['cid'] ?? '' );
        }
    }

    private static function dispatch_repost( \WP_Post $post ): array|\WP_Error {
        $ref_uri = OPI_Bluesky_Scheduler::normalize_uri( get_post_meta( $post->ID, '_bsky_ref_uri', true ) );
        $ref_cid = get_post_meta( $post->ID, '_bsky_ref_cid', true );

        if ( ! $ref_cid ) {
            $resolved = OPI_Bluesky_API::resolve_post( $ref_uri );
            if ( is_wp_error( $resolved ) ) {
                return $resolved;
            }
            $ref_uri = $resolved['uri'];
            $ref_cid = $resolved['cid'];
        }

        return OPI_Bluesky_API::repost( $ref_uri, $ref_cid );
    }

    private static function dispatch_reply( \WP_Post $post ): array|\WP_Error {
        $ref_uri   = OPI_Bluesky_Scheduler::normalize_uri( get_post_meta( $post->ID, '_bsky_ref_uri', true ) );
        $reply_ref = OPI_Bluesky_API::build_reply_ref( $ref_uri );

        if ( is_wp_error( $reply_ref ) ) {
            return $reply_ref;
        }

        return OPI_Bluesky_API::post_text( $post->post_content, $reply_ref );
    }

    /**
     * Hook: when slots option is updated, unschedule category cron if no slots remain.
     * Category process piggybacks on opi_bluesky_process — no separate cron needed.
     */
    public static function reschedule_cron(): void {
        if ( empty( self::get_slots() ) ) {
            OPI_Cron_Helper::unschedule( 'opi_bluesky_category_process' );
        }
    }
}