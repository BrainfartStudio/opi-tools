<?php
// includes/class-opi-bluesky-auto-post.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Auto_Post {

    public static function init(): void {
        add_action( 'transition_post_status', [ __CLASS__, 'handle_publish' ], 10, 3 );
    }

    public static function handle_publish( string $new_status, string $old_status, \WP_Post $post ): void {
        if ( $post->post_type !== 'post' ) {
            return;
        }

        if ( $new_status !== 'publish' || $old_status === 'publish' ) {
            return;
        }

        if ( ! OPI_Bluesky_Settings::auto_post_enabled() ) {
            return;
        }

        if ( ! OPI_Bluesky_Settings::is_configured() ) {
            return;
        }

        // Avoid double-posting if already posted.
        if ( get_post_meta( $post->ID, '_opibluesky_posted', true ) ) {
            return;
        }

        $result = OPI_Bluesky_API::post_article( $post );

        if ( ! is_wp_error( $result ) ) {
            update_post_meta( $post->ID, '_opibluesky_posted', true );
            update_post_meta( $post->ID, '_opibluesky_post_uri', $result['uri'] ?? '' );
            update_post_meta( $post->ID, '_opibluesky_post_cid', $result['cid'] ?? '' );
        }
    }
}