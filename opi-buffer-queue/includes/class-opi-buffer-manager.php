<?php
// includes/class-opi-buffer-manager.php

defined( 'ABSPATH' ) || exit;

class OPI_Buffer_Manager {

    public static function init(): void {
        add_action( 'transition_post_status',          [ __CLASS__, 'handle_buffer_transition' ], 10, 3 );
        add_action( 'wp_ajax_opi_buffer_reorder',      [ __CLASS__, 'ajax_reorder' ] );
        add_action( 'wp_ajax_opi_buffer_set_position', [ __CLASS__, 'ajax_set_position' ] );
        add_action( 'wp_ajax_opi_buffer_remove',       [ __CLASS__, 'ajax_remove' ] );
    }

    /**
     * Return all buffer posts ordered by menu_order ASC.
     */
    public static function get_buffer_posts( array $args = [] ): array {
        $defaults = [
            'post_type'      => 'post',
            'post_status'    => 'buffer',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ];

        return get_posts( wp_parse_args( $args, $defaults ) );
    }

    public static function get_buffer_count( array $args = [] ): int {
        return count( self::get_buffer_posts( $args ) );
    }

    /**
     * Append a post to the end of the buffer queue.
     */
    public static function add_to_buffer( int $post_id ): void {
        $posts     = self::get_buffer_posts();
        $max_order = 0;

        foreach ( $posts as $post ) {
            if ( $post->menu_order > $max_order ) {
                $max_order = $post->menu_order;
            }
        }

        wp_update_post( [
            'ID'         => $post_id,
            'post_status' => 'buffer',
            'menu_order'  => $max_order + 1,
        ] );
    }

    /**
     * Remove a post from the buffer — reverts to draft.
     */
    public static function remove_from_buffer( int $post_id ): void {
        wp_update_post( [
            'ID'          => $post_id,
            'post_status' => 'draft',
        ] );

        delete_post_meta( $post_id, '_opi_buffer_estimated_date' );

        do_action( 'opi_buffer_changed' );
    }

    /**
     * Persist a new queue order from an array of post IDs.
     */
    public static function reorder_buffer( array $ordered_ids ): void {
        foreach ( $ordered_ids as $index => $post_id ) {
            wp_update_post( [
                'ID'         => (int) $post_id,
                'menu_order' => $index,
            ] );
        }

        do_action( 'opi_buffer_changed' );
    }

    /**
     * Move a single post to a specific position within the queue.
     */
    public static function set_position( int $post_id, int $new_position ): void {
        $posts      = self::get_buffer_posts();
        $ordered_ids = array_map( fn( $p ) => $p->ID, $posts );

        $current = array_search( $post_id, $ordered_ids, true );
        if ( $current !== false ) {
            array_splice( $ordered_ids, $current, 1 );
        }

        array_splice( $ordered_ids, $new_position, 0, [ $post_id ] );

        self::reorder_buffer( $ordered_ids );
    }

    // -------------------------------------------------------------------------
    // Hooks
    // -------------------------------------------------------------------------

    public static function handle_buffer_transition( string $new_status, string $old_status, \WP_Post $post ): void {
        if ( $post->post_type !== 'post' ) {
            return;
        }

        if ( $new_status === 'buffer' && $old_status !== 'buffer' ) {
            self::add_to_buffer( $post->ID );
        }

        if ( $new_status === 'buffer' || $old_status === 'buffer' ) {
            do_action( 'opi_buffer_changed' );
        }
    }

    // -------------------------------------------------------------------------
    // AJAX handlers
    // -------------------------------------------------------------------------

    public static function ajax_reorder(): void {
        check_ajax_referer( 'opi_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $order = isset( $_POST['order'] ) ? array_map( 'intval', (array) $_POST['order'] ) : [];

        if ( empty( $order ) ) {
            wp_send_json_error( 'No order provided.' );
        }

        self::reorder_buffer( $order );
        wp_send_json_success();
    }

    public static function ajax_set_position(): void {
        check_ajax_referer( 'opi_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $post_id      = isset( $_POST['post_id'] )  ? absint( $_POST['post_id'] )  : 0;
        $new_position = isset( $_POST['position'] ) ? absint( $_POST['position'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID.' );
        }

        self::set_position( $post_id, $new_position );
        wp_send_json_success();
    }

    public static function ajax_remove(): void {
        check_ajax_referer( 'opi_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID.' );
        }

        self::remove_from_buffer( $post_id );
        wp_send_json_success();
    }
}