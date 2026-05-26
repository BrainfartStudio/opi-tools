<?php
// includes/class-opi-buffer-post-status.php

defined( 'ABSPATH' ) || exit;

class OPI_Buffer_Post_Status {

    public static function init(): void {
        add_action( 'init',                        [ __CLASS__, 'register_status' ] );
        add_action( 'post_submitbox_misc_actions', [ __CLASS__, 'add_submitbox_button' ] );
        add_action( 'admin_footer-post.php',       [ __CLASS__, 'add_to_status_dropdown' ] );
        add_action( 'admin_footer-post-new.php',   [ __CLASS__, 'add_to_status_dropdown' ] );
        add_action( 'admin_footer-edit.php',       [ __CLASS__, 'add_to_quick_edit' ] );
        add_filter( 'display_post_states',         [ __CLASS__, 'display_state' ], 10, 2 );
    }

    public static function register_status(): void {
        register_post_status( 'buffer', [
            'label'                     => _x( 'Buffer', 'post status', 'opi-buffer-queue' ),
            'public'                    => false,
            'internal'                  => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Buffer <span class="count">(%s)</span>',
                'Buffer <span class="count">(%s)</span>',
                'opi-buffer-queue'
            ),
        ] );
    }

    /**
     * Add a "Move to Buffer" button inside the Publish metabox.
     * Clicking it sets the status select to 'buffer' and submits the form.
     * This is the primary way to buffer a post from the editor.
     */
    public static function add_submitbox_button( \WP_Post $post ): void {
        if ( $post->post_type !== 'post' ) {
            return;
        }

        $is_buffered = $post->post_status === 'buffer';
        ?>
        <div class="misc-pub-section misc-pub-buffer">
            <?php if ( $is_buffered ) : ?>
                <span class="dashicons dashicons-clock" style="color:#2271b1;"></span>
                <strong><?php esc_html_e( 'Status: Buffer', 'opi-buffer-queue' ); ?></strong>
            <?php else : ?>
                <button type="button"
                        id="opi-buffer-move-btn"
                        class="button"
                        style="width:100%;margin-top:4px;">
                    <?php esc_html_e( 'Move to Buffer', 'opi-buffer-queue' ); ?>
                </button>
            <?php endif; ?>
        </div>
        <script>
        ( function() {
            var btn = document.getElementById( 'opi-buffer-move-btn' );
            if ( ! btn ) return;

            btn.addEventListener( 'click', function() {
                var statusSelect = document.getElementById( 'post_status' );
                var displaySpan  = document.getElementById( 'post-status-display' );

                // Ensure the buffer option exists in the select.
                if ( statusSelect && ! statusSelect.querySelector( 'option[value="buffer"]' ) ) {
                    var opt    = document.createElement( 'option' );
                    opt.value  = 'buffer';
                    opt.text   = '<?php esc_html_e( 'Buffer', 'opi-buffer-queue' ); ?>';
                    statusSelect.appendChild( opt );
                }

                if ( statusSelect ) {
                    statusSelect.value = 'buffer';
                }

                if ( displaySpan ) {
                    displaySpan.textContent = '<?php esc_html_e( 'Buffer', 'opi-buffer-queue' ); ?>';
                }

                // Submit the form to save.
                var form = document.getElementById( 'post' );
                if ( form ) {
                    // Trigger via the publish button so WP processes it correctly.
                    var publishBtn = document.getElementById( 'publish' );
                    if ( publishBtn ) {
                        publishBtn.click();
                    } else {
                        form.submit();
                    }
                }
            } );
        } )();
        </script>
        <?php
    }

    /**
     * Ensure 'buffer' appears in the status <select> on the post editor screen
     * so that posts already in buffer status display correctly.
     */
    public static function add_to_status_dropdown(): void {
        global $post;

        if ( ! $post || $post->post_type !== 'post' ) {
            return;
        }

        $selected = $post->post_status === 'buffer' ? ' selected="selected"' : '';
        ?>
        <script>
        ( function() {
            var sel = document.getElementById( 'post_status' );
            if ( ! sel ) return;
            if ( sel.querySelector( 'option[value="buffer"]' ) ) return;

            var opt    = document.createElement( 'option' );
            opt.value  = 'buffer';
            opt.text   = '<?php esc_html_e( 'Buffer', 'opi-buffer-queue' ); ?>';
            <?php if ( $selected ) : ?>
            opt.selected = true;
            document.getElementById( 'post-status-display' ).textContent =
                '<?php esc_html_e( 'Buffer', 'opi-buffer-queue' ); ?>';
            <?php endif; ?>
            sel.appendChild( opt );
        } )();
        </script>
        <?php
    }

    /**
     * Add Buffer option to the Quick Edit status dropdown.
     */
    public static function add_to_quick_edit(): void {
        global $post_type;

        if ( $post_type !== 'post' ) {
            return;
        }
        ?>
        <script>
        ( function() {
            var sel = document.querySelector( 'select[name="_status"]' );
            if ( ! sel ) return;
            if ( sel.querySelector( 'option[value="buffer"]' ) ) return;

            var opt   = document.createElement( 'option' );
            opt.value = 'buffer';
            opt.text  = '<?php esc_html_e( 'Buffer', 'opi-buffer-queue' ); ?>';
            sel.appendChild( opt );
        } )();
        </script>
        <?php
    }

    /**
     * Show "Buffer" label in the All Posts list.
     */
    public static function display_state( array $post_states, \WP_Post $post ): array {
        if ( $post->post_status === 'buffer' ) {
            $post_states['buffer'] = __( 'Buffer', 'opi-buffer-queue' );
        }
        return $post_states;
    }
}