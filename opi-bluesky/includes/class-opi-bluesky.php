<?php
// includes/class-opi-bluesky.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky {

    public static function init(): void {
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-settings.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-auth.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-api.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-auto-post.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-post-type.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-category-scheduler.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-scheduler.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-list-table.php';
        require_once OPIBLUESKY_PATH . 'includes/class-opi-bluesky-admin.php';

        OPI_Bluesky_Settings::init();
        OPI_Bluesky_Post_Type::init();
        OPI_Bluesky_Auto_Post::init();
        OPI_Bluesky_Category_Scheduler::init();
        OPI_Bluesky_Scheduler::init();
        OPI_Bluesky_Admin::init();

        add_action( 'opi_tools_register_plugins', [ __CLASS__, 'register_with_core' ] );
    }

    public static function activate(): void {
        OPI_Cron_Helper::register_interval( 'opi_bluesky_1min', 60, __( 'Every Minute', 'opi-bluesky' ) );
        OPI_Cron_Helper::schedule( 'opi_bluesky_process', 'opi_bluesky_1min' );
    }

    public static function deactivate(): void {
        OPI_Cron_Helper::unschedule( 'opi_bluesky_process' );
        OPI_Cron_Helper::unschedule( 'opi_bluesky_category_process' );
    }

    public static function register_with_core(): void {
        OPI_Tools::register_plugin(
            'opi-bluesky',
            'Bluesky',
            OPIBLUESKY_VERSION,
            [ __CLASS__, 'render_page' ],
            'manage_options',
            [ __CLASS__, 'widget_data' ],
            [ __CLASS__, 'health_data' ]
        );
    }

    public static function widget_data(): array {
        $today_start = mktime( 0, 0, 0 );

        $sent_today = (int) ( new WP_Query( [
            'post_type'      => OPI_Bluesky_Post_Type::CPT,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                [ 'key' => '_bsky_sent',    'value' => '1',          'compare' => '=' ],
                [ 'key' => '_bsky_sent_at', 'value' => $today_start, 'compare' => '>=', 'type' => 'NUMERIC' ],
            ],
        ] ) )->post_count;

        $scheduled = (int) ( new WP_Query( [
            'post_type'     => OPI_Bluesky_Post_Type::CPT,
            'post_status'   => 'publish',
            'fields'        => 'ids',
            'no_found_rows' => true,
            'meta_query'    => [
                [ 'key' => '_bsky_sent', 'value' => '0', 'compare' => '=' ],
            ],
        ] ) )->post_count;

        $categories = get_terms( [ 'taxonomy' => 'bsky_post_category', 'hide_empty' => false ] );
        $cat_lines  = [];
        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            foreach ( $categories as $cat ) {
                $count = (int) ( new WP_Query( [
                    'post_type'     => OPI_Bluesky_Post_Type::CPT,
                    'post_status'   => 'publish',
                    'fields'        => 'ids',
                    'no_found_rows' => true,
                    'tax_query'     => [ [ 'taxonomy' => 'bsky_post_category', 'field' => 'term_id', 'terms' => $cat->term_id ] ],
                    'meta_query'    => [ [ 'key' => '_bsky_sent', 'value' => '0', 'compare' => '=' ] ],
                ] ) )->post_count;
                $cat_lines[] = $cat->name . ': ' . $count;
            }
        }

        $value = sprintf(
            __( '%d scheduled, %d sent today', 'opi-bluesky' ),
            $scheduled,
            $sent_today
        );

        if ( ! empty( $cat_lines ) ) {
            $value .= ' (' . implode( ', ', $cat_lines ) . ')';
        }

        return [
            'status' => 'ok',
            'label'  => 'Bluesky',
            'value'  => $value,
        ];
    }

    public static function health_data(): array {
        if ( ! OPI_Bluesky_Auth::is_authenticated() ) {
            return [
                'severity'   => 'error',
                'message'    => __( 'Not connected to Bluesky. Enter credentials in Settings.', 'opi-bluesky' ),
                'action_url' => admin_url( 'admin.php?page=opi-bluesky&view=settings' ),
            ];
        }

        $categories = get_terms( [ 'taxonomy' => 'bsky_post_category', 'hide_empty' => false ] );
        if ( ! is_wp_error( $categories ) ) {
            foreach ( $categories as $cat ) {
                $count = (int) ( new WP_Query( [
                    'post_type'     => OPI_Bluesky_Post_Type::CPT,
                    'post_status'   => 'publish',
                    'fields'        => 'ids',
                    'no_found_rows' => true,
                    'tax_query'     => [ [ 'taxonomy' => 'bsky_post_category', 'field' => 'term_id', 'terms' => $cat->term_id ] ],
                    'meta_query'    => [ [ 'key' => '_bsky_sent', 'value' => '0', 'compare' => '=' ] ],
                ] ) )->post_count;

                if ( $count < 3 ) {
                    return [
                        'severity'   => 'warn',
                        'message'    => sprintf(
                            __( 'Category "%s" has fewer than 3 posts remaining.', 'opi-bluesky' ),
                            $cat->name
                        ),
                        'action_url' => admin_url( 'admin.php?page=opi-bluesky&view=categories' ),
                    ];
                }
            }
        }

        return [ 'severity' => 'ok' ];
    }

    public static function render_page(): void {
        $view = sanitize_key( $_GET['view'] ?? 'posts' );

        switch ( $view ) {
            case 'settings':
                require_once OPIBLUESKY_PATH . 'includes/views/settings-page.php';
                break;
            case 'categories':
                require_once OPIBLUESKY_PATH . 'includes/views/categories.php';
                break;
            default:
                require_once OPIBLUESKY_PATH . 'includes/views/scheduled-posts.php';
                break;
        }
    }
}