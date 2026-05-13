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
            [ __CLASS__, 'widget_cb' ],
            [ __CLASS__, 'health_cb' ]
        );
    }

    public static function widget_cb(): array {
        if ( ! OPI_Bluesky_Auth::is_authenticated() ) {
            return [
                'status' => 'error',
                'label'  => 'Bluesky',
                'value'  => 'Not connected',
            ];
        }

        $pending = get_posts( [
            'post_type'   => OPI_Bluesky_Post_Type::CPT,
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'     => '_bsky_sent',
                    'value'   => '0',
                    'compare' => '=',
                ],
            ],
        ] );

        $count  = count( $pending );
        $status = 'ok';

        $slots = OPI_Bluesky_Category_Scheduler::get_slots();
        if ( ! empty( $slots ) ) {
            $category_ids = array_unique( array_column( $slots, 'category_id' ) );
            foreach ( $category_ids as $cat_id ) {
                $cat_posts = get_posts( [
                    'post_type'   => OPI_Bluesky_Post_Type::CPT,
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'tax_query'   => [
                        [
                            'taxonomy' => 'bsky_post_category',
                            'field'    => 'term_id',
                            'terms'    => $cat_id,
                        ],
                    ],
                    'meta_query'  => [
                        [
                            'key'     => '_bsky_sent',
                            'value'   => '0',
                            'compare' => '=',
                        ],
                    ],
                ] );
                if ( count( $cat_posts ) < 3 ) {
                    $status = 'warn';
                    break;
                }
            }
        }

        return [
            'status' => $status,
            'label'  => 'Bluesky',
            'value'  => $count . ' post' . ( $count !== 1 ? 's' : '' ) . ' scheduled',
        ];
    }

    public static function health_cb(): array {
        if ( ! OPI_Bluesky_Auth::is_authenticated() ) {
            return [
                'severity'   => 'error',
                'message'    => 'Bluesky is not connected. Check your credentials.',
                'action_url' => admin_url( 'admin.php?page=opi-bluesky&view=settings' ),
            ];
        }

        $slots = OPI_Bluesky_Category_Scheduler::get_slots();
        if ( ! empty( $slots ) ) {
            $category_ids = array_unique( array_column( $slots, 'category_id' ) );
            foreach ( $category_ids as $cat_id ) {
                $cat_posts = get_posts( [
                    'post_type'   => OPI_Bluesky_Post_Type::CPT,
                    'post_status' => 'publish',
                    'numberposts' => 3,
                    'tax_query'   => [
                        [
                            'taxonomy' => 'bsky_post_category',
                            'field'    => 'term_id',
                            'terms'    => $cat_id,
                        ],
                    ],
                    'meta_query'  => [
                        [
                            'key'     => '_bsky_sent',
                            'value'   => '0',
                            'compare' => '=',
                        ],
                    ],
                ] );
                if ( count( $cat_posts ) < 3 ) {
                    $term = get_term( $cat_id, 'bsky_post_category' );
                    $name = ! is_wp_error( $term ) && $term ? $term->name : 'Unknown';
                    return [
                        'severity'   => 'warn',
                        'message'    => "Bluesky category \"{$name}\" has fewer than 3 posts remaining.",
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