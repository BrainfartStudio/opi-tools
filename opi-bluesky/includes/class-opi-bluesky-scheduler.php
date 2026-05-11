<?php
// includes/class-opi-bluesky-scheduler.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Scheduler {

    public static function init(): void {
        add_action( 'opi_bluesky_process',  [ __CLASS__, 'process_due_posts' ] );
        add_action( 'opi_bluesky_process',  [ 'OPI_Bluesky_Category_Scheduler', 'process_slot' ] );
        add_filter( 'cron_schedules',       [ __CLASS__, 'add_cron_interval' ] );
    }

    public static function add_cron_interval( array $schedules ): array {
        $schedules['opi_bluesky_1min'] = [
            'interval' => 60,
            'display'  => __( 'Every Minute', 'opi-bluesky' ),
        ];
        return $schedules;
    }

    public static function process_due_posts(): void {
        if ( ! OPI_Bluesky_Settings::is_configured() ) {
            return;
        }

        $due = OPI_Bluesky_Post_Type::get_due( time() );

        if ( empty( $due ) ) {
            return;
        }

        foreach ( $due as $post ) {
            self::dispatch( $post );
        }
    }

    private static function dispatch( \WP_Post $post ): void {
        $type    = get_post_meta( $post->ID, '_bsky_type', true ) ?: 'post';
        $ref_uri = get_post_meta( $post->ID, '_bsky_ref_uri', true );
        $ref_cid = get_post_meta( $post->ID, '_bsky_ref_cid', true );
        $content = $post->post_content;

        $result = match ( $type ) {
            'repost' => self::handle_repost( $ref_uri, $ref_cid, $content ),
            'reply'  => self::handle_reply( $ref_uri, $content ),
            default  => OPI_Bluesky_API::post_text( $content ),
        };

        if ( is_wp_error( $result ) ) {
            OPI_Bluesky_Post_Type::mark_failed( $post->ID, $result->get_error_message() );
        } else {
            OPI_Bluesky_Post_Type::mark_sent(
                $post->ID,
                $result['uri'] ?? '',
                $result['cid'] ?? ''
            );
        }
    }

    private static function handle_repost( string $ref_uri, string $ref_cid, string $content ): array|\WP_Error {
        if ( ! $ref_cid && $ref_uri ) {
            $resolved = OPI_Bluesky_API::resolve_post( self::normalize_uri( $ref_uri ) );
            if ( is_wp_error( $resolved ) ) {
                return $resolved;
            }
            $ref_uri = $resolved['uri'];
            $ref_cid = $resolved['cid'];
        }

        return OPI_Bluesky_API::repost( $ref_uri, $ref_cid );
    }

    private static function handle_reply( string $ref_uri, string $content ): array|\WP_Error {
        $uri = self::normalize_uri( $ref_uri );

        $reply_ref = OPI_Bluesky_API::build_reply_ref( $uri );
        if ( is_wp_error( $reply_ref ) ) {
            return $reply_ref;
        }

        return OPI_Bluesky_API::post_text( $content, $reply_ref );
    }

    /**
     * Convert a bsky.app URL to an at:// URI if needed.
     */
    public static function normalize_uri( string $input ): string {
        if ( str_starts_with( $input, 'at://' ) ) {
            return $input;
        }

        if ( preg_match( '!bsky\.app/profile/([^/]+)/post/([^/?]+)!', $input, $m ) ) {
            return "at://{$m[1]}/app.bsky.feed.post/{$m[2]}";
        }

        return $input;
    }
}