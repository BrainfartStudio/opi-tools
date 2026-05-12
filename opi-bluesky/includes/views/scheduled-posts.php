<?php
// includes/views/scheduled-posts.php

defined( 'ABSPATH' ) || exit;

$action  = $_GET['action'] ?? 'list';
$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
$message = '';

// ── Handle form saves ────────────────────────────────────────────────────────

if ( isset( $_POST['opibluesky_save_post'] ) && check_admin_referer( 'opibluesky_post_action' ) ) {
    $content      = sanitize_textarea_field( $_POST['bsky_content'] ?? '' );
    $raw_dt       = sanitize_text_field( $_POST['bsky_scheduled_at'] ?? '' );
    $scheduled_at = 0;
    if ( $raw_dt ) {
        try {
            $scheduled_at = ( new DateTime( $raw_dt, wp_timezone() ) )->getTimestamp();
        } catch ( Exception $e ) {
            $scheduled_at = 0;
        }
    }
    $type    = sanitize_key( $_POST['bsky_type'] ?? 'post' );
    $ref_uri = sanitize_text_field( $_POST['bsky_ref_uri'] ?? '' );
    $ref_cid = sanitize_text_field( $_POST['bsky_ref_cid'] ?? '' );
    $edit_id = intval( $_POST['bsky_post_id'] ?? 0 );

    // Content is required except for reposts (which can be plain reposts with no text).
    $content_required = ( $type !== 'repost' );
    $invalid = ! $scheduled_at || ( $content_required && ! $content );

    if ( $invalid ) {
        $message = '<div class="notice notice-error"><p>' . __( 'Scheduled date is required. Content is required for posts and replies.', 'opi-bluesky' ) . '</p></div>';
        $action  = isset( $_POST['bsky_post_id'] ) && intval( $_POST['bsky_post_id'] ) ? 'edit' : 'new';
    } else {
        if ( $edit_id ) {
            wp_update_post( [ 'ID' => $edit_id, 'post_content' => $content ] );
            update_post_meta( $edit_id, '_bsky_scheduled_at', $scheduled_at );
            update_post_meta( $edit_id, '_bsky_type',         $type );
            update_post_meta( $edit_id, '_bsky_ref_uri',      $ref_uri );
            update_post_meta( $edit_id, '_bsky_ref_cid',      $ref_cid );
            $message = '<div class="notice notice-success"><p>' . __( 'Post updated.', 'opi-bluesky' ) . '</p></div>';
        } else {
            OPI_Bluesky_Post_Type::create( $content, $scheduled_at, $type, $ref_uri, $ref_cid );
            $message = '<div class="notice notice-success"><p>' . __( 'Post scheduled.', 'opi-bluesky' ) . '</p></div>';
        }
        $action = 'list';
    }
}

// ── Delete via GET ───────────────────────────────────────────────────────────

if ( $action === 'delete' && $post_id && check_admin_referer( 'opibluesky_delete_' . $post_id ) ) {
    OPI_Bluesky_Post_Type::delete( $post_id );
    $message = '<div class="notice notice-success"><p>' . __( 'Post deleted.', 'opi-bluesky' ) . '</p></div>';
    $action  = 'list';
}

// ── Routing ──────────────────────────────────────────────────────────────────

$editing_post = null;
if ( $action === 'edit' && $post_id ) {
    $editing_post = get_post( $post_id );
}

$base_url    = admin_url( 'admin.php?page=opi-bluesky' );
$settings_url = add_query_arg( 'view', 'settings', $base_url );

?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Bluesky — Scheduled Posts', 'opi-bluesky' ); ?></h1>

    <?php if ( $action === 'list' ) : ?>
        <a href="<?php echo add_query_arg( [ 'page' => 'opi-bluesky', 'action' => 'new' ], admin_url( 'admin.php' ) ); ?>" class="page-title-action">
            <?php _e( 'Schedule New Post', 'opi-bluesky' ); ?>
        </a>
        <a href="<?php echo esc_url( $settings_url ); ?>" class="page-title-action">
            <?php _e( 'Settings', 'opi-bluesky' ); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php echo $message; ?>

    <?php if ( $action === 'list' ) : ?>

        <?php
        $list_table = new OPI_Bluesky_List_Table();
        $list_table->prepare_items();
        ?>

        <?php if ( empty( $list_table->items ) ) : ?>
            <div class="notice notice-info">
                <p><?php _e( 'No scheduled posts. Click "Schedule New Post" to create one.', 'opi-bluesky' ); ?></p>
            </div>
        <?php else : ?>
            <form method="get">
                <input type="hidden" name="page" value="opi-bluesky">
                <?php $list_table->display(); ?>
            </form>
        <?php endif; ?>

        <script>
        jQuery(document).ready(function($) {
            $(document).on('click', '.opibluesky-delete-post', function() {
                if ( ! confirm('<?php echo esc_js( __( 'Delete this scheduled post?', 'opi-bluesky' ) ); ?>') ) {
                    return;
                }
                var $btn   = $(this);
                var postId = $btn.data('post-id');
                $btn.prop('disabled', true);
                $.post(opiBlueskyAdmin.ajaxUrl, {
                    action:  'opibluesky_delete_post',
                    nonce:   opiBlueskyAdmin.nonce,
                    post_id: postId,
                }, function(response) {
                    if (response.success) {
                        $btn.closest('tr').fadeOut(300, function() { $(this).remove(); });
                    } else {
                        alert('Delete failed: ' + response.data);
                        $btn.prop('disabled', false);
                    }
                }).fail(function() {
                    alert('Server error.');
                    $btn.prop('disabled', false);
                });
            });
        });
        </script>

    <?php elseif ( in_array( $action, [ 'new', 'edit' ], true ) ) : ?>

        <?php
        $is_edit      = $action === 'edit' && $editing_post;
        $content      = $is_edit ? $editing_post->post_content : '';
        $scheduled_at = $is_edit ? get_post_meta( $editing_post->ID, '_bsky_scheduled_at', true ) : '';
        $type         = $is_edit ? ( get_post_meta( $editing_post->ID, '_bsky_type', true ) ?: 'post' ) : 'post';
        $ref_uri      = $is_edit ? get_post_meta( $editing_post->ID, '_bsky_ref_uri', true ) : '';
        $ref_cid      = $is_edit ? get_post_meta( $editing_post->ID, '_bsky_ref_cid', true ) : '';
        $scheduled_val = $scheduled_at ? date( 'Y-m-d\TH:i', (int) $scheduled_at ) : '';
        ?>

        <h2><?php echo $is_edit ? __( 'Edit Scheduled Post', 'opi-bluesky' ) : __( 'Schedule New Post', 'opi-bluesky' ); ?></h2>

        <form method="post">
            <?php wp_nonce_field( 'opibluesky_post_action' ); ?>
            <input type="hidden" name="bsky_post_id" value="<?php echo $is_edit ? $editing_post->ID : 0; ?>">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="bsky_type"><?php _e( 'Post Type', 'opi-bluesky' ); ?></label></th>
                    <td>
                        <select id="bsky_type" name="bsky_type">
                            <option value="post"   <?php selected( $type, 'post' ); ?>><?php _e( 'Post', 'opi-bluesky' ); ?></option>
                            <option value="repost" <?php selected( $type, 'repost' ); ?>><?php _e( 'Repost / Quote Post', 'opi-bluesky' ); ?></option>
                            <option value="reply"  <?php selected( $type, 'reply' ); ?>><?php _e( 'Reply', 'opi-bluesky' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr id="bsky_ref_row" <?php echo $type === 'post' ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><label for="bsky_ref_uri"><?php _e( 'Bluesky Post URL or URI', 'opi-bluesky' ); ?></label></th>
                    <td>
                        <input type="text" id="bsky_ref_uri" name="bsky_ref_uri"
                               value="<?php echo esc_attr( $ref_uri ); ?>"
                               class="large-text"
                               placeholder="https://bsky.app/profile/user.bsky.social/post/abc123">
                        <p class="description" id="bsky_ref_desc_repost" <?php echo $type !== 'repost' ? 'style="display:none;"' : ''; ?>>
                            <?php _e( 'Paste the Bluesky post URL to repost. Leave content blank for a plain repost, or add text to quote post.', 'opi-bluesky' ); ?>
                        </p>
                        <p class="description" id="bsky_ref_desc_reply" <?php echo $type !== 'reply' ? 'style="display:none;"' : ''; ?>>
                            <?php _e( 'Paste the Bluesky post URL you are replying to.', 'opi-bluesky' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bsky_content"><?php _e( 'Content', 'opi-bluesky' ); ?></label></th>
                    <td>
                        <textarea id="bsky_content" name="bsky_content" rows="5" class="large-text" maxlength="300"><?php echo esc_textarea( $content ); ?></textarea>
                        <p class="description">
                            <span id="bsky_char_count">0</span>/300 <?php _e( 'characters', 'opi-bluesky' ); ?>
                        </p>
                        <p class="description" id="bsky_content_desc_repost" <?php echo $type !== 'repost' ? 'style="display:none;"' : ''; ?>>
                            <?php _e( 'Optional — leave blank for a plain repost, or add your comment to quote post.', 'opi-bluesky' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bsky_scheduled_at"><?php _e( 'Schedule Date & Time', 'opi-bluesky' ); ?></label></th>
                    <td>
                        <input type="datetime-local" id="bsky_scheduled_at" name="bsky_scheduled_at"
                               value="<?php echo esc_attr( $scheduled_val ); ?>">
                        <p class="description"><?php printf( __( 'Times are in site timezone: %s', 'opi-bluesky' ), esc_html( wp_timezone_string() ) ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(
                $is_edit ? __( 'Update Post', 'opi-bluesky' ) : __( 'Schedule Post', 'opi-bluesky' ),
                'primary',
                'opibluesky_save_post'
            ); ?>

            <a href="<?php echo add_query_arg( [ 'page' => 'opi-bluesky' ], admin_url( 'admin.php' ) ); ?>" class="button">
                <?php _e( 'Cancel', 'opi-bluesky' ); ?>
            </a>
        </form>

        <script>
        jQuery(document).ready(function($) {
            var $type    = $('#bsky_type');
            var $refRow  = $('#bsky_ref_row');
            var $content = $('#bsky_content');
            var $count   = $('#bsky_char_count');

            function updateCount() {
                $count.text($content.val().length);
            }

            function updateTypeUI() {
                var val = $type.val();
                $refRow.toggle(val !== 'post');
                $('#bsky_ref_desc_repost').toggle(val === 'repost');
                $('#bsky_ref_desc_reply').toggle(val === 'reply');
                $('#bsky_content_desc_repost').toggle(val === 'repost');
            }

            $type.on('change', updateTypeUI);
            $content.on('input', updateCount);

            updateCount();
            updateTypeUI();
        });
        </script>

    <?php endif; ?>
</div>