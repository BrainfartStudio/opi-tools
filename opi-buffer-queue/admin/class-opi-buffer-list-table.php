<?php
// admin/class-opi-buffer-list-table.php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class OPI_Buffer_List_Table extends WP_List_Table {

    private int $filter_category = 0;
    private int $filter_tag      = 0;

    public function __construct() {
        parent::__construct( [
            'singular' => 'buffer_post',
            'plural'   => 'buffer_posts',
            'ajax'     => false,
        ] );

        $this->filter_category = isset( $_GET['filter_category'] ) ? absint( $_GET['filter_category'] ) : 0;
        $this->filter_tag      = isset( $_GET['filter_tag'] )      ? absint( $_GET['filter_tag'] )      : 0;
    }

    public function get_columns(): array {
        return [
            'cb'             => '<input type="checkbox" />',
            'handle'         => '',
            'queue'          => __( 'Queue #', 'opi-buffer-queue' ),
            'title'          => __( 'Title', 'opi-buffer-queue' ),
            'categories'     => __( 'Categories', 'opi-buffer-queue' ),
            'tags'           => __( 'Tags', 'opi-buffer-queue' ),
            'estimated_date' => __( 'Estimated Publish', 'opi-buffer-queue' ),
            'actions'        => __( 'Actions', 'opi-buffer-queue' ),
        ];
    }

    public function prepare_items(): void {
        $this->_column_headers = [ $this->get_columns(), [], [] ];

        $args = [];

        if ( $this->filter_category > 0 ) {
            $args['category'] = $this->filter_category;
        }

        if ( $this->filter_tag > 0 ) {
            $args['tag_id'] = $this->filter_tag;
        }

        $this->items = OPI_Buffer_Manager::get_buffer_posts( $args );
    }

    public function column_default( $item, $column_name ): string {
        return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
    }

    public function column_cb( $item ): string {
        return sprintf( '<input type="checkbox" name="post[]" value="%d" />', $item->ID );
    }

    public function column_handle( $item ): string {
        return '<span class="sbq-handle dashicons dashicons-menu" style="cursor:move;color:#999;font-size:18px;"></span>';
    }

    public function column_queue( $item ): string {
        return sprintf(
            '<input type="number" class="opi-buffer-queue-input" data-post-id="%d" value="%d" min="0" style="width:60px;text-align:center;" />',
            $item->ID,
            $item->menu_order
        );
    }

    public function column_title( $item ): string {
        return sprintf(
            '<strong><a href="%s">%s</a></strong>',
            esc_url( get_edit_post_link( $item->ID ) ),
            esc_html( $item->post_title )
        );
    }

    public function column_categories( $item ): string {
        $cats = get_the_category( $item->ID );
        if ( empty( $cats ) ) {
            return '—';
        }
        return esc_html( implode( ', ', array_map( fn( $c ) => $c->name, $cats ) ) );
    }

    public function column_tags( $item ): string {
        $tags = get_the_tags( $item->ID );
        if ( empty( $tags ) ) {
            return '—';
        }
        return esc_html( implode( ', ', array_map( fn( $t ) => $t->name, $tags ) ) );
    }

    public function column_estimated_date( $item ): string {
        $ts = OPI_Buffer_Scheduler::get_estimated_date( $item->ID );

        if ( ! $ts ) {
            return '<em>' . esc_html__( 'Calculating…', 'opi-buffer-queue' ) . '</em>';
        }

        $formatted = date_i18n(
            get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
            $ts
        );

        if ( $ts <= time() ) {
            return '<span class="opi-status-badge opi-status-badge--error">'
                . esc_html__( 'Ready to publish', 'opi-buffer-queue' )
                . '</span><br><small>' . esc_html( $formatted ) . '</small>';
        }

        return esc_html( $formatted );
    }

    public function column_actions( $item ): string {
        return sprintf(
            '<button type="button" class="button opi-buffer-remove" data-post-id="%d">%s</button>',
            $item->ID,
            esc_html__( 'Remove from Buffer', 'opi-buffer-queue' )
        );
    }

    public function extra_tablenav( $which ): void {
        if ( $which !== 'top' ) {
            return;
        }

        $categories = get_categories( [ 'hide_empty' => false ] );
        $tags       = get_tags( [ 'hide_empty' => false ] );
        ?>
        <div class="alignleft actions">
            <select name="filter_category">
                <option value="0"><?php esc_html_e( 'All Categories', 'opi-buffer-queue' ); ?></option>
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo absint( $cat->term_id ); ?>" <?php selected( $this->filter_category, $cat->term_id ); ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filter_tag">
                <option value="0"><?php esc_html_e( 'All Tags', 'opi-buffer-queue' ); ?></option>
                <?php foreach ( $tags as $tag ) : ?>
                    <option value="<?php echo absint( $tag->term_id ); ?>" <?php selected( $this->filter_tag, $tag->term_id ); ?>>
                        <?php echo esc_html( $tag->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="submit" name="filter_action" class="button" value="<?php esc_attr_e( 'Filter', 'opi-buffer-queue' ); ?>">
        </div>
        <?php
    }
}