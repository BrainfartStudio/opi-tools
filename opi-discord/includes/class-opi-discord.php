<?php
// includes/class-opi-discord.php

defined( 'ABSPATH' ) || exit;

class OPI_Discord {

    const OPTION_KEY = 'opidiscord_settings';

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
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function get_settings(): array {
        return wp_parse_args( get_option( self::OPTION_KEY, [] ), [
            'webhook_url'  => '',
            'accent_color' => '#7506394',
            'show_excerpt' => true,
        ]);
    }

    public static function send( \WP_Post $post ): bool {
        $settings = self::get_settings();

        if ( empty( $settings['webhook_url'] ) ) {
            return false;
        }

        $payload = [
            'embeds' => [[
                'title'       => html_entity_decode( get_the_title( $post ), ENT_QUOTES ),
                'url'         => get_permalink( $post ),
                'description' => $settings['show_excerpt']
                    ? html_entity_decode( get_the_excerpt( $post ), ENT_QUOTES )
                    : '',
                'color'       => 7506394,
            ]]
        ];

        $response = wp_remote_post( $settings['webhook_url'], [
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
        $posts = get_posts([
            'numberposts' => -1,
            'post_status' => 'publish',
            'post_type'   => 'post',
            'orderby'     => 'date',
            'order'       => 'ASC',
        ]);

        if ( empty( $posts ) ) return 0;

        $settings = self::get_settings();
        if ( empty( $settings['webhook_url'] ) ) return 0;

        foreach ( $posts as $post ) {
            $payload = [
                'embeds' => [[
                    'title'       => html_entity_decode( get_the_title( $post ), ENT_QUOTES ),
                    'url'         => get_permalink( $post ),
                    'description' => $settings['show_excerpt']
                        ? html_entity_decode( get_the_excerpt( $post ), ENT_QUOTES )
                        : '',
                    'color'       => 16752293,
                ]]
            ];

            wp_remote_post( $settings['webhook_url'], [
                'headers'  => [ 'Content-Type' => 'application/json' ],
                'body'     => wp_json_encode( $payload ),
                'blocking' => false,
            ]);
        }

        return count( $posts );
    }

    public static function render_page(): void {
        require_once OPIDISCORD_PATH . 'includes/views/settings-page.php';
    }
}