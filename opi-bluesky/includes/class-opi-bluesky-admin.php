<?php
// includes/class-opi-bluesky-admin.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Admin {

    public static function init(): void {
        add_action( 'admin_enqueue_scripts',               [ __CLASS__, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_opibluesky_test_connection',  [ __CLASS__, 'ajax_test_connection' ] );
        add_action( 'wp_ajax_opibluesky_delete_post',      [ __CLASS__, 'ajax_delete_post' ] );
        add_action( 'wp_ajax_opibluesky_add_category',     [ __CLASS__, 'ajax_add_category' ] );
        add_action( 'wp_ajax_opibluesky_delete_category',  [ __CLASS__, 'ajax_delete_category' ] );
        add_action( 'wp_ajax_opibluesky_rename_category',  [ __CLASS__, 'ajax_rename_category' ] );
    }

    public static function enqueue_scripts( string $hook ): void {
        if ( $hook !== 'toplevel_page_opi-bluesky' ) {
            return;
        }

        wp_enqueue_script(
            'opibluesky-admin',
            OPIBLUESKY_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            OPIBLUESKY_VERSION,
            true
        );

        wp_localize_script( 'opibluesky-admin', 'opiBlueskyAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'opibluesky_nonce' ),
        ] );

        wp_enqueue_style(
            'opibluesky-admin',
            OPIBLUESKY_URL . 'assets/css/admin.css',
            [],
            OPIBLUESKY_VERSION
        );
    }

    public static function ajax_test_connection(): void {
        check_ajax_referer( 'opibluesky_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        if ( ! OPI_Bluesky_Settings::is_configured() ) {
            wp_send_json_error( 'Credentials not configured.' );
        }

        OPI_Bluesky_Auth::clear_session();

        $result = OPI_Bluesky_Auth::authenticate(
            OPI_Bluesky_Settings::get_identifier(),
            OPI_Bluesky_Settings::get_app_password()
        );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        $session = OPI_Bluesky_Auth::get_session();
        wp_send_json_success( [
            'handle' => $session['handle'] ?? OPI_Bluesky_Settings::get_identifier(),
        ] );
    }

    public static function ajax_delete_post(): void {
        check_ajax_referer( 'opibluesky_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID.' );
        }

        OPI_Bluesky_Post_Type::delete( $post_id );
        wp_send_json_success();
    }

    public static function ajax_add_category(): void {
        check_ajax_referer( 'opi_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $name = sanitize_text_field( $_POST['name'] ?? '' );
        if ( ! $name ) {
            wp_send_json_error( 'Name is required.' );
        }

        $result = wp_insert_term( $name, 'bsky_post_category' );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        $term = get_term( $result['term_id'], 'bsky_post_category' );
        wp_send_json_success( [
            'term_id' => $term->term_id,
            'name'    => $term->name,
        ] );
    }

    public static function ajax_delete_category(): void {
        check_ajax_referer( 'opi_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $term_id = absint( $_POST['term_id'] ?? 0 );
        if ( ! $term_id ) {
            wp_send_json_error( 'Invalid term ID.' );
        }

        $result = wp_delete_term( $term_id, 'bsky_post_category' );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success();
    }

    public static function ajax_rename_category(): void {
        check_ajax_referer( 'opi_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $term_id = absint( $_POST['term_id'] ?? 0 );
        $name    = sanitize_text_field( $_POST['name'] ?? '' );

        if ( ! $term_id || ! $name ) {
            wp_send_json_error( 'Term ID and name are required.' );
        }

        $result = wp_update_term( $term_id, 'bsky_post_category', [ 'name' => $name ] );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success();
    }
}