<?php
// includes/class-opi-bluesky-auto-post.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Auto_Post {

    public static function init(): void {
        add_action( 'transition_post_status', [ __CLASS__, 'handle_publish' ], 10, 3 );
        add_action( 'post_submitbox_misc_actions', [ __CLASS__, 'render_post_meta_box' ] );
        add_action( 'wp_ajax_opibluesky_manual_post', [ __CLASS__, 'ajax_manual_post' ] );
    }

    public static function handle_publish( string $new_status, string $old_status, \WP_Post $post ): void {
        if ( $post->post_type !== 'post' ) {
            return;
        }

        if ( $new_status !== 'publish' || $old_status === 'publish' ) {
            return;
        }

        if ( ! OPI_Bluesky_Settings::auto_post_enabled() ) {
            return;
        }

        if ( ! OPI_Bluesky_Settings::is_configured() ) {
            return;
        }

        if ( get_post_meta( $post->ID, '_opibluesky_posted', true ) ) {
            return;
        }

        self::send( $post );
    }

    public static function send( \WP_Post $post ): array|\WP_Error {
        $result = OPI_Bluesky_API::post_article( $post );

        if ( ! is_wp_error( $result ) ) {
            update_post_meta( $post->ID, '_opibluesky_posted', true );
            update_post_meta( $post->ID, '_opibluesky_posted_at', time() );
            update_post_meta( $post->ID, '_opibluesky_post_uri', $result['uri'] ?? '' );
            update_post_meta( $post->ID, '_opibluesky_post_cid', $result['cid'] ?? '' );
        }

        return $result;
    }

    /**
     * Show Bluesky post status in the publish meta box.
     */
    public static function render_post_meta_box( \WP_Post $post ): void {
        if ( $post->post_type !== 'post' ) {
            return;
        }

        $posted    = get_post_meta( $post->ID, '_opibluesky_posted', true );
        $posted_at = get_post_meta( $post->ID, '_opibluesky_posted_at', true );
        $uri       = get_post_meta( $post->ID, '_opibluesky_post_uri', true );
        ?>
        <div class="misc-pub-section opibluesky-post-status">
            <span class="dashicons dashicons-share" style="color:#0085ff;"></span>
            <strong><?php _e( 'Bluesky:', 'opi-bluesky' ); ?></strong>
            <?php if ( $posted ) : ?>
                <?php if ( $posted_at ) : ?>
                    <?php echo esc_html( date_i18n( get_option( 'date_format' ), $posted_at ) ); ?>
                <?php else : ?>
                    <?php _e( 'Posted', 'opi-bluesky' ); ?>
                <?php endif; ?>
                <?php if ( $uri ) :
                    // Convert at:// URI to bsky.app URL for display.
                    // at://did:plc:xxx/app.bsky.feed.post/rkey → https://bsky.app/profile/did/post/rkey
                    preg_match( '#at://([^/]+)/[^/]+/([^/]+)#', $uri, $m );
                    if ( ! empty( $m[1] ) && ! empty( $m[2] ) ) :
                        $bsky_url = "https://bsky.app/profile/{$m[1]}/post/{$m[2]}";
                        ?>
                        — <a href="<?php echo esc_url( $bsky_url ); ?>" target="_blank"><?php _e( 'View', 'opi-bluesky' ); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else : ?>
                <?php _e( 'Not posted', 'opi-bluesky' ); ?>
                <?php if ( $post->post_status === 'publish' && OPI_Bluesky_Settings::is_configured() ) : ?>
                    — <button type="button" class="button-link" id="opibluesky-manual-post" data-post-id="<?php echo $post->ID; ?>">
                        <?php _e( 'Post now', 'opi-bluesky' ); ?>
                    </button>
                    <span id="opibluesky-manual-result"></span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#opibluesky-manual-post').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Posting...');
                $.post(ajaxurl, {
                    action:  'opibluesky_manual_post',
                    nonce:   '<?php echo wp_create_nonce( 'opibluesky_nonce' ); ?>',
                    post_id: $btn.data('post-id'),
                }, function(response) {
                    if (response.success) {
                        $('#opibluesky-manual-result').css('color','#2271b1').text('Posted!');
                        $btn.hide();
                    } else {
                        $('#opibluesky-manual-result').css('color','#d63638').text('Error: ' + response.data);
                        $btn.prop('disabled', false).text('Post now');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public static function ajax_manual_post(): void {
        check_ajax_referer( 'opibluesky_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $post    = get_post( $post_id );

        if ( ! $post || $post->post_status !== 'publish' ) {
            wp_send_json_error( 'Invalid post.' );
        }

        $result = self::send( $post );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success();
    }
}