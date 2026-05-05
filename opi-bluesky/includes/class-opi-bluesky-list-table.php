<?php
// includes/class-opi-bluesky-list-table.php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class OPI_Bluesky_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'bsky_post',
            'plural'   => 'bsky_posts',
            'ajax'     => false,
        ] );
    }

    public function get_columns(): array {
        return [
            'cb'           => '<input type="checkbox" />',
            'content'      => __( 'Content', 'opi-bluesky' ),
            'type'         => __( 'Type', 'opi-bluesky' ),
            'category'     => __( 'Category', 'opi-bluesky' ),
            'scheduled_at' => __( 'Scheduled', 'opi-bluesky' ),
            'status'       => __( 'Status', 'opi-bluesky' ),
            'actions'      => __( 'Actions', 'opi-bluesky' ),
        ];
    }

    public function prepare_items(): void {
        $this->_column_headers = [ $this->get_columns(), [], [] ];
        $this->items           = OPI_Bluesky_Post_Type::get_pending();
    }

    public function column_default( $item, $column_name ): string {
        return '';
    }

    public function column_cb( $item ): string {
        return sprintf( '<input type="checkbox" name="bsky_post[]" value="%d" />', $item->ID );
    }

    public function column_content( $item ): string {
        return esc_html( wp_trim_words( $item->post_content, 20, '…' ) );
    }

    public function column_type( $item ): string {
        $type  = get_post_meta( $item->ID, '_bsky_type', true ) ?: 'post';
        $label = [
            'post'   => __( 'Post', 'opi-bluesky' ),
            'repost' => __( 'Repost', 'opi-bluesky' ),
            'reply'  => __( 'Reply', 'opi-bluesky' ),
        ];
        return esc_html( $label[ $type ] ?? $type );
    }

    public function column_category( $item ): string {
        $terms = wp_get_object_terms( $item->ID, 'bsky_post_category', [ 'fields' => 'names' ] );
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return '—';
        }
        return esc_html( implode( ', ', $terms ) );
    }

    public function column_scheduled_at( $item ): string {
        $ts = (int) get_post_meta( $item->ID, '_bsky_scheduled_at', true );
        if ( ! $ts ) {
            return '—';
        }

        $date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts );

        if ( $ts <= current_time( 'timestamp' ) ) {
            return '<strong style="color:#d63638;">' . $date . '</strong><br><em>' . __( 'Sending soon…', 'opi-bluesky' ) . '</em>';
        }

        return esc_html( $date );
    }

    public function column_status( $item ): string {
        $failed = get_post_meta( $item->ID, '_bsky_failed', true );
        if ( $failed ) {
            $error = get_post_meta( $item->ID, '_bsky_failed_error', true );
            return '<span style="color:#d63638;">'
                . __( 'Failed', 'opi-bluesky' )
                . '</span><br><small>' . esc_html( $error ) . '</small>';
        }
        return '<span style="color:#2271b1;">' . __( 'Pending', 'opi-bluesky' ) . '</span>';
    }

    public function column_actions( $item ): string {
        $edit_url = add_query_arg( [
            'page'    => 'opi-bluesky',
            'action'  => 'edit',
            'post_id' => $item->ID,
        ], admin_url( 'admin.php' ) );

        return sprintf(
            '<a href="%s" class="button button-small">%s</a> ' .
            '<button type="button" class="button button-small opibluesky-delete-post" data-post-id="%d">%s</button>',
            esc_url( $edit_url ),
            __( 'Edit', 'opi-bluesky' ),
            $item->ID,
            __( 'Delete', 'opi-bluesky' )
        );
    }
}