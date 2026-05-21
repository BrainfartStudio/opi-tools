<?php
// admin/class-opi-buffer-admin.php

defined( 'ABSPATH' ) || exit;

class OPI_Buffer_Admin {

    public static function init(): void {
        add_action( 'opi_tools_register_plugins', [ __CLASS__, 'register_with_core' ] );
        add_action( 'admin_enqueue_scripts',      [ __CLASS__, 'enqueue_scripts' ] );
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-buffer',
            'Buffer Queue',
            OPI_BUFFER_VERSION,
            [ __CLASS__, 'render_page' ],
            'edit_posts',
            [ __CLASS__, 'widget_cb' ],
            [ __CLASS__, 'health_cb' ]
        );
    }

    public static function render_page(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $view = sanitize_key( $_GET['view'] ?? 'queue' );

        if ( $view === 'settings' ) {
            require_once OPI_BUFFER_PATH . 'admin/views/settings.php';
        } else {
            require_once OPI_BUFFER_PATH . 'admin/views/queue.php';
        }
    }

    public static function enqueue_scripts( string $hook ): void {
        if ( $hook !== 'opi-tools_page_opi-buffer' ) {
            return;
        }

        wp_enqueue_script(
            'opi-buffer-admin',
            OPI_BUFFER_URL . 'assets/js/opi-buffer-admin.js',
            [ 'jquery', 'jquery-ui-sortable', 'opi-admin' ],
            OPI_BUFFER_VERSION,
            true
        );
    }

    public static function widget_cb(): array {
        $count    = OPI_Buffer_Manager::get_buffer_count();
        $interval = OPI_Buffer_Scheduler::get_interval_for_count( $count );

        return [
            'status' => 'ok',
            'label'  => 'Buffer Queue',
            'value'  => sprintf(
                '%d %s in queue — publishing every %d %s at %s',
                $count,
                $count === 1 ? 'post' : 'posts',
                $interval['interval_days'],
                $interval['interval_days'] === 1 ? 'day' : 'days',
                $interval['time']
            ),
        ];
    }

    public static function health_cb(): array {
        $count = OPI_Buffer_Manager::get_buffer_count();

        if ( $count === 0 ) {
            return [
                'severity'   => 'warn',
                'message'    => 'Buffer queue is empty.',
                'action_url' => admin_url( 'post-new.php' ),
            ];
        }

        if ( ! OPI_Cron_Helper::is_scheduled( OPI_Buffer_Scheduler::CRON_HOOK ) ) {
            return [
                'severity'   => 'error',
                'message'    => 'Buffer cron is not scheduled.',
                'action_url' => admin_url( 'admin.php?page=opi-buffer&view=settings' ),
            ];
        }

        return [ 'severity' => 'ok' ];
    }
}