<?php
// includes/class-opi-rss.php

defined( 'ABSPATH' ) || exit;

class OPI_RSS {

    public static function init(): void {
        require_once OPIRSS_PATH . 'includes/class-opi-rss-db.php';
        require_once OPIRSS_PATH . 'includes/class-opi-rss-settings.php';
        require_once OPIRSS_PATH . 'includes/class-opi-rss-cron.php';
        require_once OPIRSS_PATH . 'includes/blocks.php';

        OPI_RSS_Cron::init();

        add_action( 'opi_tools_register_plugins', [ __CLASS__, 'register_with_core' ] );
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-rss',
            __( 'RSS Aggregator', 'opi-rss' ),
            OPIRSS_VERSION,
            [ __CLASS__, 'render_page' ],
            'manage_options',
            [ __CLASS__, 'get_widget_data' ],
            [ __CLASS__, 'get_health_data' ]
        );
    }

    public static function deactivate(): void {
        OPI_RSS_Cron::deactivate();
    }

    /**
     * Route admin views.
     */
    public static function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        self::handle_actions();

        $view = sanitize_key( $_GET['view'] ?? 'list' );

        switch ( $view ) {
            case 'inactive':
                require_once OPIRSS_PATH . 'includes/views/feed-inactive.php';
                break;
            case 'error':
                require_once OPIRSS_PATH . 'includes/views/feed-error.php';
                break;
            case 'settings':
                require_once OPIRSS_PATH . 'includes/views/feed-settings.php';
                break;
            case 'add':
            case 'edit':
                require_once OPIRSS_PATH . 'includes/views/feed-form.php';
                break;
            default:
                require_once OPIRSS_PATH . 'includes/views/feed-list.php';
                break;
        }
    }

    /**
     * Handle POST actions before any view is rendered.
     */
    private static function handle_actions(): void {
        if ( empty( $_POST['opirss_action'] ) ) {
            return;
        }

        check_admin_referer( 'opirss_nonce' );

        $action = sanitize_key( $_POST['opirss_action'] );

        switch ( $action ) {

            case 'add':
                $feed_id = OPI_RSS_DB::add_feed(
                    $_POST['name'] ?? '',
                    $_POST['url']  ?? '',
                    absint( $_POST['limit'] ?? 1 )
                );
                if ( $feed_id ) {
                    $feed    = OPI_RSS_DB::get_feed( $feed_id );
                    $fetched = OPI_RSS_Cron::fetch_feed( $feed );
                    $type    = $fetched ? 'success' : 'error';
                    $msg     = $fetched
                        ? __( 'Feed added and fetched successfully.', 'opi-rss' )
                        : __( 'Feed added but could not be fetched. Check the URL.', 'opi-rss' );
                } else {
                    $type = 'error';
                    $msg  = __( 'Failed to add feed.', 'opi-rss' );
                }
                self::redirect_with_notice( 'list', $type, $msg );
                break;

            case 'edit':
                OPI_RSS_DB::update_feed(
                    absint( $_POST['id'] ),
                    $_POST['name']   ?? '',
                    $_POST['url']    ?? '',
                    absint( $_POST['status'] ?? OPI_RSS_DB::STATUS_INACTIVE ),
                    absint( $_POST['limit']  ?? 1 )
                );
                self::redirect_with_notice( 'list', 'success', __( 'Feed updated.', 'opi-rss' ) );
                break;

            case 'delete':
                OPI_RSS_DB::delete_feed( absint( $_POST['id'] ) );
                self::redirect_with_notice( 'list', 'success', __( 'Feed deleted.', 'opi-rss' ) );
                break;

            case 'activate':
                OPI_RSS_DB::set_status( absint( $_POST['id'] ), OPI_RSS_DB::STATUS_ACTIVE );
                self::redirect_with_notice( 'inactive', 'success', __( 'Feed activated.', 'opi-rss' ) );
                break;

            case 'deactivate':
                OPI_RSS_DB::set_status( absint( $_POST['id'] ), OPI_RSS_DB::STATUS_INACTIVE );
                self::redirect_with_notice( 'list', 'success', __( 'Feed deactivated.', 'opi-rss' ) );
                break;

            case 'fetch_now':
                $feed        = OPI_RSS_DB::get_feed( absint( $_POST['id'] ) );
                $result      = $feed ? OPI_RSS_Cron::fetch_feed( $feed ) : false;
                $from_view   = sanitize_key( $_POST['_referer_view'] ?? 'list' );
                $type        = $result ? 'success' : 'error';
                $msg         = $result
                    ? __( 'Feed fetched successfully.', 'opi-rss' )
                    : __( 'Failed to fetch feed. Check the URL.', 'opi-rss' );
                // On success the feed is now active — always land on the active list.
                // On failure stay on whichever view triggered the retry.
                $redirect_view = $result ? 'list' : $from_view;
                self::redirect_with_notice( $redirect_view, $type, $msg );
                break;

            case 'save_settings':
                $input = $_POST['opirss_settings'] ?? [];
                // Checkboxes are absent from POST when unchecked — normalize before sanitize.
                $input['cron_inactive'] = ! empty( $input['cron_inactive'] );
                OPI_RSS_Settings::update( OPI_RSS_Settings::sanitize( $input ) );
                OPI_RSS_Cron::reschedule();
                self::redirect_with_notice( 'settings', 'success', __( 'Settings saved.', 'opi-rss' ) );
                break;
        }
    }

    /**
     * PRG redirect after any POST action.
     */
    private static function redirect_with_notice( string $view, string $type, string $message ): void {
        $url = add_query_arg( [
            'page'         => 'opi-rss',
            'view'         => $view,
            'opi_notice'   => $type,
            'opi_message'  => urlencode( $message ),
        ], admin_url( 'admin.php' ) );

        wp_safe_redirect( $url );
        exit;
    }

    /**
     * Render a notice from query params (set by redirect_with_notice).
     */
    public static function maybe_render_notice(): void {
        if ( empty( $_GET['opi_notice'] ) ) {
            return;
        }

        $type    = sanitize_key( $_GET['opi_notice'] );
        $message = sanitize_text_field( urldecode( $_GET['opi_message'] ?? '' ) );

        if ( $message ) {
            echo OPI_Tools::notice( $type, $message );
        }
    }

    /**
     * Format a datetime string as a human-readable time diff.
     */
    public static function time_diff( string $datetime ): string {
        $now  = current_time( 'timestamp' );
        $time = strtotime( $datetime );
        $diff = $now - $time;

        $minute = 60;
        $hour   = 3600;
        $day    = 86400;

        if ( $diff < $minute ) {
            return __( 'just now', 'opi-rss' );
        } elseif ( $diff < $hour ) {
            $mins = floor( $diff / $minute );
            return sprintf( _n( '%d minute ago', '%d minutes ago', $mins, 'opi-rss' ), $mins );
        } elseif ( $diff < $day ) {
            $hours = floor( $diff / $hour );
            return sprintf( _n( '%d hour ago', '%d hours ago', $hours, 'opi-rss' ), $hours );
        } else {
            $days = floor( $diff / $day );
            return sprintf( _n( '%d day ago', '%d days ago', $days, 'opi-rss' ), $days );
        }
    }

    // ── Dashboard callbacks ───────────────────────────────────────────────

    public static function get_widget_data(): array {
        $counts = OPI_RSS_DB::get_widget_counts();

        if ( $counts->error_count > 0 ) {
            $status = 'error';
        } elseif ( $counts->stale_count > 0 ) {
            $status = 'warn';
        } else {
            $status = 'ok';
        }

        return [
            'status' => $status,
            'label'  => __( 'RSS Aggregator', 'opi-rss' ),
            'value'  => sprintf(
                __( '%d active feed%s, %d item%s today', 'opi-rss' ),
                $counts->active_count,
                $counts->active_count !== 1 ? 's' : '',
                $counts->today_count,
                $counts->today_count !== 1 ? 's' : ''
            ),
        ];
    }

    public static function get_health_data(): array {
        $counts = OPI_RSS_DB::get_widget_counts();

        if ( $counts->error_count > 0 ) {
            return [
                'severity'   => 'error',
                'message'    => sprintf(
                    _n( '%d feed is failing to fetch.', '%d feeds are failing to fetch.', $counts->error_count, 'opi-rss' ),
                    $counts->error_count
                ),
                'action_url' => admin_url( 'admin.php?page=opi-rss&view=inactive' ),
            ];
        }

        if ( $counts->stale_count > 0 ) {
            return [
                'severity'   => 'warn',
                'message'    => sprintf(
                    _n( '%d active feed has not fetched in 48 hours.', '%d active feeds have not fetched in 48 hours.', $counts->stale_count, 'opi-rss' ),
                    $counts->stale_count
                ),
                'action_url' => admin_url( 'admin.php?page=opi-rss&view=list' ),
            ];
        }

        return [ 'severity' => 'ok' ];
    }
}