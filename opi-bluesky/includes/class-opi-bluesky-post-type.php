<?php
// includes/class-opi-bluesky-post-type.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Post_Type {

    const CPT = 'bsky_post';

    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'register_cpt' ] );
        add_action( 'init', [ __CLASS__, 'register_taxonomy' ] );
    }

    public static function register_cpt(): void {
        register_post_type( self::CPT, [
            'label'               => __( 'Bluesky Posts', 'opi-bluesky' ),
            'public'              => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_rest'        => false,
            'supports'            => [ 'title', 'editor', 'custom-fields' ],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ] );
    }

    /**
     * Taxonomy for grouping scheduled posts into categories.
     * Groundwork for the category scheduler feature.
     */
    public static function register_taxonomy(): void {
        register_taxonomy( 'bsky_post_category', self::CPT, [
            'label'             => __( 'Post Categories', 'opi-bluesky' ),
            'public'            => false,
            'show_ui'           => false,
            'show_in_rest'      => false,
            'hierarchical'      => false,
            'rewrite'           => false,
        ] );
    }

    /**
     * Create a scheduled post record.
     *
     * @param string   $content      The text content of the Bluesky post.
     * @param int      $scheduled_at Unix timestamp.
     * @param string   $type         'post' | 'repost' | 'reply'
     * @param string   $ref_uri      For repost/reply: the target AT URI.
     * @param string   $ref_cid      For repost: the target CID.
     * @param int[]    $category_ids bsky_post_category term IDs.
     */
    public static function create(
        string $content,
        int $scheduled_at,
        string $type = 'post',
        string $ref_uri = '',
        string $ref_cid = '',
        array $category_ids = []
    ): int|\WP_Error {
        $post_id = wp_insert_post( [
            'post_type'   => self::CPT,
            'post_title'  => wp_trim_words( $content, 10 ) ?: __( 'Bluesky Post', 'opi-bluesky' ),
            'post_content'=> $content,
            'post_status' => 'publish',
        ], true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        update_post_meta( $post_id, '_bsky_scheduled_at', $scheduled_at );
        update_post_meta( $post_id, '_bsky_type',         $type );
        update_post_meta( $post_id, '_bsky_ref_uri',      $ref_uri );
        update_post_meta( $post_id, '_bsky_ref_cid',      $ref_cid );
        update_post_meta( $post_id, '_bsky_sent',         false );

        if ( ! empty( $category_ids ) ) {
            wp_set_object_terms( $post_id, $category_ids, 'bsky_post_category' );
        }

        return $post_id;
    }

    /**
     * Get all pending (unsent) scheduled posts due on or before $before.
     */
    public static function get_due( int $before ): array {
        return get_posts( [
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                'relation' => 'AND',
                [
                    'key'     => '_bsky_scheduled_at',
                    'value'   => $before,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => '_bsky_sent',
                    'value'   => '0',
                    'compare' => '=',
                ],
            ],
            'orderby'     => 'meta_value_num',
            'meta_key'    => '_bsky_scheduled_at',
            'order'       => 'ASC',
        ] );
    }

    /**
     * Get all pending scheduled posts.
     */
    public static function get_pending(): array {
        return get_posts( [
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'     => '_bsky_sent',
                    'value'   => '0',
                    'compare' => '=',
                ],
            ],
            'orderby'     => 'meta_value_num',
            'meta_key'    => '_bsky_scheduled_at',
            'order'       => 'ASC',
        ] );
    }

    public static function mark_sent( int $post_id, string $uri = '', string $cid = '' ): void {
        update_post_meta( $post_id, '_bsky_sent',    true );
        update_post_meta( $post_id, '_bsky_sent_at', time() );
        update_post_meta( $post_id, '_bsky_uri',     $uri );
        update_post_meta( $post_id, '_bsky_cid',     $cid );
    }

    public static function mark_failed( int $post_id, string $error ): void {
        update_post_meta( $post_id, '_bsky_failed',       true );
        update_post_meta( $post_id, '_bsky_failed_at',    time() );
        update_post_meta( $post_id, '_bsky_failed_error', $error );
    }

    public static function delete( int $post_id ): void {
        wp_delete_post( $post_id, true );
    }
}