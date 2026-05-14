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
            'label'           => __( 'Bluesky Posts', 'opi-bluesky' ),
            'public'          => false,
            'show_ui'         => false,
            'show_in_menu'    => false,
            'show_in_rest'    => false,
            'supports'        => [ 'title', 'editor', 'custom-fields' ],
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        ] );
    }

    public static function register_taxonomy(): void {
        register_taxonomy( 'bsky_post_category', self::CPT, [
            'label'        => __( 'Post Categories', 'opi-bluesky' ),
            'public'       => false,
            'show_ui'      => false,
            'show_in_rest' => false,
            'hierarchical' => false,
            'rewrite'      => false,
        ] );
    }

    /**
     * Create a scheduled or queued post record.
     *
     * Exactly one of $scheduled_at or $category_id must be provided.
     *
     * @param string $content      The text content of the Bluesky post.
     * @param string $type         'post' | 'repost' | 'reply'
     * @param string $ref_uri      For repost/reply: the target AT URI.
     * @param string $ref_cid      For repost: the target CID.
     * @param int    $scheduled_at Unix timestamp (UTC). 0 if queued.
     * @param int    $category_id  bsky_post_category term ID. 0 if scheduled.
     */
    public static function create(
        string $content,
        string $type = 'post',
        string $ref_uri = '',
        string $ref_cid = '',
        int $scheduled_at = 0,
        int $category_id = 0
    ): int|\WP_Error {
        $post_id = wp_insert_post( [
            'post_type'    => self::CPT,
            'post_title'   => wp_trim_words( $content, 10 ) ?: __( 'Bluesky Post', 'opi-bluesky' ),
            'post_content' => $content,
            'post_status'  => 'publish',
        ], true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        update_post_meta( $post_id, '_bsky_type',    $type );
        update_post_meta( $post_id, '_bsky_ref_uri', $ref_uri );
        update_post_meta( $post_id, '_bsky_ref_cid', $ref_cid );
        update_post_meta( $post_id, '_bsky_sent',    '0' );
        update_post_meta( $post_id, '_bsky_failed',  '0' );

        if ( $scheduled_at ) {
            // Scheduled mode: store exact send time, no category, no queue order.
            update_post_meta( $post_id, '_bsky_scheduled_at', $scheduled_at );
            update_post_meta( $post_id, '_bsky_mode',         'scheduled' );
        } else {
            // Queued mode: assign category, append to end of that category's queue.
            update_post_meta( $post_id, '_bsky_scheduled_at', 0 );
            update_post_meta( $post_id, '_bsky_mode',         'queued' );

            if ( $category_id ) {
                wp_set_object_terms( $post_id, [ $category_id ], 'bsky_post_category' );
                update_post_meta( $post_id, '_bsky_queue_order', self::next_queue_position( $category_id ) );
            }
        }

        return $post_id;
    }

    /**
     * Return the next available queue position for a category.
     * Positions are 0-indexed integers. Gaps are fine — display sorts by value.
     */
    public static function next_queue_position( int $category_id ): int {
        $posts = get_posts( [
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'numberposts' => 1,
            'tax_query'   => [ [
                'taxonomy' => 'bsky_post_category',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ] ],
            'meta_query'  => [ [
                'key'     => '_bsky_sent',
                'value'   => '0',
                'compare' => '=',
            ] ],
            'meta_key'    => '_bsky_queue_order',
            'orderby'     => 'meta_value_num',
            'order'       => 'DESC',
        ] );

        if ( empty( $posts ) ) {
            return 0;
        }

        return (int) get_post_meta( $posts[0]->ID, '_bsky_queue_order', true ) + 1;
    }

    /**
     * Get all unsent datetime-scheduled posts due on or before $before.
     */
    public static function get_due( int $before ): array {
        return get_posts( [
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                'relation' => 'AND',
                [ 'key' => '_bsky_mode',         'value' => 'scheduled', 'compare' => '=' ],
                [ 'key' => '_bsky_sent',          'value' => '0',         'compare' => '=' ],
                [ 'key' => '_bsky_scheduled_at',  'value' => $before,     'compare' => '<=', 'type' => 'NUMERIC' ],
            ],
            'meta_key' => '_bsky_scheduled_at',
            'orderby'  => 'meta_value_num',
            'order'    => 'ASC',
        ] );
    }

    /**
     * Get all unsent datetime-scheduled posts, ordered by send time.
     */
    public static function get_scheduled(): array {
        return get_posts( [
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                'relation' => 'AND',
                [ 'key' => '_bsky_mode', 'value' => 'scheduled', 'compare' => '=' ],
                [ 'key' => '_bsky_sent', 'value' => '0',         'compare' => '=' ],
            ],
            'meta_key' => '_bsky_scheduled_at',
            'orderby'  => 'meta_value_num',
            'order'    => 'ASC',
        ] );
    }

    /**
     * Get all unsent queued posts for a specific category, ordered by queue position.
     * Pass 0 to get queued posts across all categories (ordered by queue position).
     */
    public static function get_queued( int $category_id = 0 ): array {
        $args = [
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                'relation' => 'AND',
                [ 'key' => '_bsky_mode', 'value' => 'queued', 'compare' => '=' ],
                [ 'key' => '_bsky_sent', 'value' => '0',      'compare' => '=' ],
            ],
            'meta_key' => '_bsky_queue_order',
            'orderby'  => 'meta_value_num',
            'order'    => 'ASC',
        ];

        if ( $category_id ) {
            $args['tax_query'] = [ [
                'taxonomy' => 'bsky_post_category',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ] ];
        }

        return get_posts( $args );
    }

    /**
     * Get all upcoming posts (scheduled + queued), merged and sorted by send time.
     *
     * Queued posts are assigned estimated send times by the caller before merging,
     * so this method just fetches both sets and returns them separately for the
     * view to combine after estimated times are calculated.
     *
     * Returns [ 'scheduled' => WP_Post[], 'queued' => WP_Post[] ]
     */
    public static function get_all_upcoming(): array {
        return [
            'scheduled' => self::get_scheduled(),
            'queued'    => self::get_queued(),
        ];
    }

    /**
     * Reorder queued posts for a category.
     *
     * @param int   $category_id
     * @param int[] $ordered_post_ids Post IDs in desired order, first to last.
     */
    public static function reorder_queue( int $category_id, array $ordered_post_ids ): void {
        foreach ( $ordered_post_ids as $position => $post_id ) {
            $post_id = absint( $post_id );
            if ( ! $post_id ) {
                continue;
            }
            // Verify this post actually belongs to this category before writing.
            $terms = wp_get_object_terms( $post_id, 'bsky_post_category', [ 'fields' => 'ids' ] );
            if ( ! is_wp_error( $terms ) && in_array( $category_id, $terms, true ) ) {
                update_post_meta( $post_id, '_bsky_queue_order', $position );
            }
        }
    }

    public static function mark_sent( int $post_id, string $uri = '', string $cid = '' ): void {
        update_post_meta( $post_id, '_bsky_sent',    '1' );
        update_post_meta( $post_id, '_bsky_sent_at', time() );
        update_post_meta( $post_id, '_bsky_uri',     $uri );
        update_post_meta( $post_id, '_bsky_cid',     $cid );
    }

    public static function mark_failed( int $post_id, string $error ): void {
        update_post_meta( $post_id, '_bsky_sent',         '0' );
        update_post_meta( $post_id, '_bsky_failed',       '1' );
        update_post_meta( $post_id, '_bsky_failed_at',    time() );
        update_post_meta( $post_id, '_bsky_failed_error', $error );
    }

    public static function delete( int $post_id ): void {
        wp_delete_post( $post_id, true );
    }
}