<?php
// includes/class-opi-discord.php

defined( 'ABSPATH' ) || exit;

class OPI_Discord {

    public static function init(): void {
        add_action( 'opi_tools_register_plugins', [ __CLASS__, 'register_with_core' ] );
        add_action( 'transition_post_status',     [ __CLASS__, 'on_publish' ], 10, 3 );

        require_once OPIDISCORD_PATH . 'includes/class-opi-discord-queue.php';
        OPI_Discord_Queue::init();
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-discord',
            'Discord',
            OPIDISCORD_VERSION,
            [ __CLASS__, 'render_page' ],
            'manage_options',
            [ __CLASS__, 'get_widget_data' ],
            [ __CLASS__, 'get_health_data' ]
        );
    }

    /**
     * Convert a hex color string to an integer for the Discord API.
     */
    private static function hex_to_int( string $hex ): int {
        return (int) hexdec( ltrim( $hex, '#' ) );
    }

    public static function send( \WP_Post $post ): bool {
        $webhook = OPI_Discord_Settings::get_webhook_url();

        if ( empty( $webhook ) ) {
            return false;
        }

        $settings = OPI_Discord_Settings::get();

        $payload = [
            'embeds' => [[
                'title'       => html_entity_decode( get_the_title( $post ), ENT_QUOTES ),
                'url'         => get_permalink( $post ),
                'description' => $settings['show_excerpt']
                    ? html_entity_decode( get_the_excerpt( $post ), ENT_QUOTES )
                    : '',
                'color'       => self::hex_to_int( $settings['embed_color'] ),
            ]]
        ];

        $response = wp_remote_post( $webhook, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $payload ),
        ]);

        return ! is_wp_error( $response );
    }

    public static function on_publish( string $new_status, string $old_status, \WP_Post $post ): void {
        if ( $new_status !== 'publish' || $old_status === 'publish' ) return;
        if ( $post->post_type !== 'post' ) return;

        self::send( $post );
    }

    public static function queue_all_posts(): int {
        if ( empty( OPI_Discord_Settings::get_webhook_url() ) ) return 0;

        $posts = get_posts([
            'numberposts' => -1,
            'post_status' => 'publish',
            'post_type'   => 'post',
            'orderby'     => 'date',
            'order'       => 'ASC',
        ]);

        if ( empty( $posts ) ) return 0;

        OPI_Discord_Queue::enqueue( array_column( $posts, 'ID' ) );

        return count( $posts );
    }

    public static function get_widget_data(): array {
        $has_webhook = ! empty( OPI_Discord_Settings::get_webhook_url() );

        return [
            'status' => $has_webhook ? 'ok' : 'error',
            'label'  => 'Discord',
            'value'  => $has_webhook ? 'Connected' : 'Webhook not configured',
        ];
    }

    public static function get_health_data(): array {
        if ( empty( OPI_Discord_Settings::get_webhook_url() ) ) {
            return [
                'severity'   => 'error',
                'message'    => 'No webhook URL configured.',
                'action_url' => admin_url( 'admin.php?page=opi-discord' ),
            ];
        }

        return [ 'severity' => 'ok' ];
    }

    public static function render_page(): void {
        require_once OPIDISCORD_PATH . 'includes/views/settings-page.php';
    }
}