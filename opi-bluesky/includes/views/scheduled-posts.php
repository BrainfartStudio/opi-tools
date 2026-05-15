<?php
// includes/views/scheduled-posts.php

defined( 'ABSPATH' ) || exit;

$action  = $_GET['action'] ?? 'list';
$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
$message = '';

// ── Handle form saves ────────────────────────────────────────────────────────

if ( isset( $_POST['opibluesky_save_post'] ) && check_admin_referer( 'opibluesky_post_action' ) ) {
    $content     = sanitize_textarea_field( $_POST['bsky_content'] ?? '' );
    $type        = sanitize_key( $_POST['bsky_type'] ?? 'post' );
    $ref_uri     = sanitize_text_field( $_POST['bsky_ref_uri'] ?? '' );
    $ref_cid     = sanitize_text_field( $_POST['bsky_ref_cid'] ?? '' );
    $edit_id     = intval( $_POST['bsky_post_id'] ?? 0 );
    $post_mode   = sanitize_key( $_POST['bsky_post_mode'] ?? '' ); // 'scheduled' | 'queued'
    $category_id = absint( $_POST['bsky_category_id'] ?? 0 );

    $scheduled_at = 0;
    if ( $post_mode === 'scheduled' ) {
        $raw_dt = sanitize_text_field( $_POST['bsky_scheduled_at'] ?? '' );
        if ( $raw_dt ) {
            try {
                $scheduled_at = ( new DateTime( $raw_dt, wp_timezone() ) )->getTimestamp();
            } catch ( Exception $e ) {
                $scheduled_at = 0;
            }
        }
    }

    // Validation.
    $content_required = ( $type !== 'repost' );
    $error            = '';

    if ( ! $post_mode ) {
        $error = __( 'Please choose Schedule or Queue.', 'opi-bluesky' );
    } elseif ( $post_mode === 'scheduled' && ! $scheduled_at ) {
        $error = __( 'A date and time is required for scheduled posts.', 'opi-bluesky' );
    } elseif ( $post_mode === 'queued' && ! $category_id ) {
        $error = __( 'A category is required for queued posts.', 'opi-bluesky' );
    } elseif ( $content_required && ! $content ) {
        $error = __( 'Content is required for posts and replies.', 'opi-bluesky' );
    }

    if ( $error ) {
        $message = OPI_Tools::notice( 'error', $error );
        $action  = $edit_id ? 'edit' : 'new';
    } else {
        if ( $edit_id ) {
            wp_update_post( [ 'ID' => $edit_id, 'post_content' => $content ] );
            update_post_meta( $edit_id, '_bsky_type',    $type );
            update_post_meta( $edit_id, '_bsky_ref_uri', $ref_uri );
            update_post_meta( $edit_id, '_bsky_ref_cid', $ref_cid );
            update_post_meta( $edit_id, '_bsky_mode',    $post_mode );

            if ( $post_mode === 'scheduled' ) {
                update_post_meta( $edit_id, '_bsky_scheduled_at', $scheduled_at );
                wp_set_object_terms( $edit_id, [], 'bsky_post_category' );
                delete_post_meta( $edit_id, '_bsky_queue_order' );
            } else {
                update_post_meta( $edit_id, '_bsky_scheduled_at', 0 );
                wp_set_object_terms( $edit_id, [ $category_id ], 'bsky_post_category' );
                // Preserve existing queue order on edit; only set if missing.
                if ( '' === get_post_meta( $edit_id, '_bsky_queue_order', true ) ) {
                    update_post_meta( $edit_id, '_bsky_queue_order', OPI_Bluesky_Post_Type::next_queue_position( $category_id ) );
                }
            }

            $message = OPI_Tools::notice( 'success', __( 'Post updated.', 'opi-bluesky' ) );
        } else {
            OPI_Bluesky_Post_Type::create(
                $content,
                $type,
                $ref_uri,
                $ref_cid,
                $post_mode === 'scheduled' ? $scheduled_at : 0,
                $post_mode === 'queued'    ? $category_id  : 0
            );
            $message = OPI_Tools::notice( 'success', __( 'Post saved.', 'opi-bluesky' ) );
        }
        $action = 'list';
    }
}

// ── Delete via GET ───────────────────────────────────────────────────────────

if ( $action === 'delete' && $post_id && check_admin_referer( 'opibluesky_delete_' . $post_id ) ) {
    OPI_Bluesky_Post_Type::delete( $post_id );
    $message = OPI_Tools::notice( 'success', __( 'Post deleted.', 'opi-bluesky' ) );
    $action  = 'list';
}

// ── Routing ──────────────────────────────────────────────────────────────────

$editing_post = null;
if ( $action === 'edit' && $post_id ) {
    $editing_post = get_post( $post_id );
    if ( ! $editing_post ) {
        $action = 'list';
    }
}

$base_url = admin_url( 'admin.php?page=opi-bluesky' );

?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Bluesky', 'opi-bluesky' ); ?></h1>

    <?php if ( in_array( $action, [ 'list' ], true ) ) : ?>
        <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'opi-bluesky', 'action' => 'new' ], admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">
            <?php _e( 'Add New Post', 'opi-bluesky' ); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
        <a href="<?php echo esc_url( $base_url ); ?>"
           class="nav-tab <?php echo $action === 'list' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Posts', 'opi-bluesky' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-bluesky&view=categories' ) ); ?>"
           class="nav-tab"><?php _e( 'Categories', 'opi-bluesky' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-bluesky&view=settings' ) ); ?>"
           class="nav-tab"><?php _e( 'Settings', 'opi-bluesky' ); ?></a>
    </nav>

    <?php echo $message; ?>

    <?php if ( $action === 'list' ) :

        // ── Fetch data ───────────────────────────────────────────────────────
        $tab = sanitize_key( $_GET['tab'] ?? 'all' );

        $scheduled_posts = OPI_Bluesky_Post_Type::get_scheduled();
        $queued_posts    = OPI_Bluesky_Post_Type::get_queued();

        // Build estimated send times for every queued post.
        // Group queued posts by category, then assign timestamps from get_next_send_times().
        $queued_by_cat = [];
        foreach ( $queued_posts as $qp ) {
            $terms = wp_get_object_terms( $qp->ID, 'bsky_post_category', [ 'fields' => 'ids' ] );
            $cat   = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? (int) $terms[0] : 0;
            $queued_by_cat[ $cat ][] = $qp;
        }

        $estimated_times = []; // post_id => timestamp
        foreach ( $queued_by_cat as $cat_id => $cat_posts ) {
            $count  = count( $cat_posts );
            $times  = $cat_id ? OPI_Bluesky_Category_Scheduler::get_next_send_times( $cat_id, $count ) : [];
            foreach ( $cat_posts as $i => $qp ) {
                $estimated_times[ $qp->ID ] = $times[ $i ] ?? 0;
            }
        }

        // Build combined list for All tab, sorted by send time (scheduled exact, queued estimated).
        $all_posts = [];
        foreach ( $scheduled_posts as $sp ) {
            $all_posts[] = [
                'post'      => $sp,
                'mode'      => 'scheduled',
                'send_time' => (int) get_post_meta( $sp->ID, '_bsky_scheduled_at', true ),
                'category'  => null,
            ];
        }
        foreach ( $queued_posts as $qp ) {
            $terms = wp_get_object_terms( $qp->ID, 'bsky_post_category', [ 'fields' => 'names' ] );
            $all_posts[] = [
                'post'      => $qp,
                'mode'      => 'queued',
                'send_time' => $estimated_times[ $qp->ID ] ?? 0,
                'category'  => ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0] : '—',
            ];
        }
        usort( $all_posts, fn( $a, $b ) => $a['send_time'] <=> $b['send_time'] );

        $dt_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

        // Collect all categories for the Queued tab filter.
        $all_categories    = get_terms( [ 'taxonomy' => 'bsky_post_category', 'hide_empty' => false ] );
        if ( is_wp_error( $all_categories ) ) $all_categories = [];
        $filter_cat_id     = absint( $_GET['cat'] ?? 0 );
        ?>

        <nav class="nav-tab-wrapper" style="margin:0 0 20px;">
            <a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'all' ], $base_url ) ); ?>"
               class="nav-tab <?php echo $tab === 'all' ? 'nav-tab-active' : ''; ?>">
                <?php _e( 'All', 'opi-bluesky' ); ?>
                <span class="count">(<?php echo count( $all_posts ); ?>)</span>
            </a>
            <a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'scheduled' ], $base_url ) ); ?>"
               class="nav-tab <?php echo $tab === 'scheduled' ? 'nav-tab-active' : ''; ?>">
                <?php _e( 'Scheduled', 'opi-bluesky' ); ?>
                <span class="count">(<?php echo count( $scheduled_posts ); ?>)</span>
            </a>
            <a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'queued' ], $base_url ) ); ?>"
               class="nav-tab <?php echo $tab === 'queued' ? 'nav-tab-active' : ''; ?>">
                <?php _e( 'Queued', 'opi-bluesky' ); ?>
                <span class="count">(<?php echo count( $queued_posts ); ?>)</span>
            </a>
        </nav>

        <?php // ── ALL TAB ──────────────────────────────────────────────────
        if ( $tab === 'all' ) : ?>

            <?php if ( empty( $all_posts ) ) : ?>
                <p><?php _e( 'No upcoming posts.', 'opi-bluesky' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Content', 'opi-bluesky' ); ?></th>
                            <th style="width:90px;"><?php _e( 'Type', 'opi-bluesky' ); ?></th>
                            <th style="width:100px;"><?php _e( 'Mode', 'opi-bluesky' ); ?></th>
                            <th style="width:130px;"><?php _e( 'Category', 'opi-bluesky' ); ?></th>
                            <th style="width:160px;"><?php _e( 'Send Time', 'opi-bluesky' ); ?></th>
                            <th style="width:80px;"><?php _e( 'Actions', 'opi-bluesky' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $all_posts as $row ) :
                            $p        = $row['post'];
                            $bsky_type = get_post_meta( $p->ID, '_bsky_type', true ) ?: 'post';
                            $type_labels = [ 'post' => __( 'Post', 'opi-bluesky' ), 'repost' => __( 'Repost', 'opi-bluesky' ), 'reply' => __( 'Reply', 'opi-bluesky' ) ];
                            $send_label  = $row['send_time'] ? wp_date( $dt_format, $row['send_time'] ) : '—';
                            $edit_url    = add_query_arg( [ 'action' => 'edit', 'post_id' => $p->ID ], $base_url );
                        ?>
                            <tr>
                                <td><?php echo esc_html( wp_trim_words( $p->post_content, 15, '…' ) ); ?></td>
                                <td><?php echo esc_html( $type_labels[ $bsky_type ] ?? $bsky_type ); ?></td>
                                <td>
                                    <span class="opi-status-badge opi-status-badge--<?php echo $row['mode'] === 'scheduled' ? 'ok' : 'warn'; ?>">
                                        <?php echo $row['mode'] === 'scheduled' ? esc_html__( 'Scheduled', 'opi-bluesky' ) : esc_html__( 'Queued', 'opi-bluesky' ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $row['category'] ?? '—' ); ?></td>
                                <td><?php echo esc_html( $send_label ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php _e( 'Edit', 'opi-bluesky' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php // ── SCHEDULED TAB ────────────────────────────────────────────
        elseif ( $tab === 'scheduled' ) : ?>

            <?php if ( empty( $scheduled_posts ) ) : ?>
                <p><?php _e( 'No scheduled posts.', 'opi-bluesky' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Content', 'opi-bluesky' ); ?></th>
                            <th style="width:90px;"><?php _e( 'Type', 'opi-bluesky' ); ?></th>
                            <th style="width:180px;"><?php _e( 'Send Time', 'opi-bluesky' ); ?></th>
                            <th style="width:100px;"><?php _e( 'Actions', 'opi-bluesky' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $scheduled_posts as $p ) :
                            $bsky_type   = get_post_meta( $p->ID, '_bsky_type', true ) ?: 'post';
                            $type_labels = [ 'post' => __( 'Post', 'opi-bluesky' ), 'repost' => __( 'Repost', 'opi-bluesky' ), 'reply' => __( 'Reply', 'opi-bluesky' ) ];
                            $ts          = (int) get_post_meta( $p->ID, '_bsky_scheduled_at', true );
                            $edit_url    = add_query_arg( [ 'action' => 'edit', 'post_id' => $p->ID ], $base_url );
                        ?>
                            <tr>
                                <td><?php echo esc_html( wp_trim_words( $p->post_content, 15, '…' ) ); ?></td>
                                <td><?php echo esc_html( $type_labels[ $bsky_type ] ?? $bsky_type ); ?></td>
                                <td><?php echo $ts ? esc_html( wp_date( $dt_format, $ts ) ) : '—'; ?></td>
                                <td style="display:flex;align-items:center;gap:4px;">
                                    <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php _e( 'Edit', 'opi-bluesky' ); ?></a>
                                    <button type="button" class="button button-small opibluesky-delete-post" data-post-id="<?php echo $p->ID; ?>">
                                        <?php _e( 'Delete', 'opi-bluesky' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php // ── QUEUED TAB ───────────────────────────────────────────────
        elseif ( $tab === 'queued' ) : ?>

            <?php if ( ! empty( $all_categories ) ) : ?>
                <div style="margin-bottom:16px;">
                    <label for="bsky_queue_cat_filter"><strong><?php _e( 'Category:', 'opi-bluesky' ); ?></strong></label>
                    <select id="bsky_queue_cat_filter" style="margin-left:8px;">
                        <option value="0"><?php _e( '— All categories —', 'opi-bluesky' ); ?></option>
                        <?php foreach ( $all_categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->term_id ); ?>"
                                    <?php selected( $filter_cat_id, $cat->term_id ); ?>>
                                <?php echo esc_html( $cat->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php
            // Group queued posts by category for display.
            // If a category filter is active, only show that category.
            $display_cats = [];
            foreach ( $all_categories as $cat ) {
                if ( $filter_cat_id && $cat->term_id !== $filter_cat_id ) continue;
                $cat_queue = OPI_Bluesky_Post_Type::get_queued( $cat->term_id );
                if ( empty( $cat_queue ) ) continue;
                $cat_times = OPI_Bluesky_Category_Scheduler::get_next_send_times( $cat->term_id, count( $cat_queue ) );
                $display_cats[] = [ 'term' => $cat, 'posts' => $cat_queue, 'times' => $cat_times ];
            }

            // Posts with no category.
            $uncategorized = OPI_Bluesky_Post_Type::get_queued( 0 );
            $uncategorized = array_filter( $uncategorized, function( $qp ) {
                $terms = wp_get_object_terms( $qp->ID, 'bsky_post_category', [ 'fields' => 'ids' ] );
                return is_wp_error( $terms ) || empty( $terms );
            } );
            ?>

            <?php if ( empty( $display_cats ) && empty( $uncategorized ) ) : ?>
                <p><?php _e( 'No queued posts.', 'opi-bluesky' ); ?></p>
            <?php endif; ?>

            <?php foreach ( $display_cats as $dc ) :
                $slots_configured = ! empty( OPI_Bluesky_Category_Scheduler::get_slots_for_category( $dc['term']->term_id ) );
            ?>
                <div class="opi-card" style="margin-bottom:20px;">
                    <h3 class="opi-section-header" style="display:flex;align-items:center;gap:12px;">
                        <?php echo esc_html( $dc['term']->name ); ?>
                        <?php if ( ! $slots_configured ) : ?>
                            <span class="opi-status-badge opi-status-badge--warn" style="font-size:11px;">
                                <?php _e( 'No slot configured', 'opi-bluesky' ); ?>
                            </span>
                        <?php endif; ?>
                    </h3>

                    <table class="wp-list-table widefat fixed"
                           id="bsky-queue-<?php echo esc_attr( $dc['term']->term_id ); ?>"
                           data-category-id="<?php echo esc_attr( $dc['term']->term_id ); ?>">
                        <thead>
                            <tr>
                                <th style="width:32px;"></th>
                                <th><?php _e( 'Content', 'opi-bluesky' ); ?></th>
                                <th style="width:90px;"><?php _e( 'Type', 'opi-bluesky' ); ?></th>
                                <th style="width:180px;"><?php _e( 'Est. Send Time', 'opi-bluesky' ); ?></th>
                                <th style="width:100px;"><?php _e( 'Actions', 'opi-bluesky' ); ?></th>
                            </tr>
                        </thead>
                        <tbody class="bsky-sortable">
                            <?php foreach ( $dc['posts'] as $i => $p ) :
                                $bsky_type   = get_post_meta( $p->ID, '_bsky_type', true ) ?: 'post';
                                $type_labels = [ 'post' => __( 'Post', 'opi-bluesky' ), 'repost' => __( 'Repost', 'opi-bluesky' ), 'reply' => __( 'Reply', 'opi-bluesky' ) ];
                                $est_time    = $dc['times'][ $i ] ?? 0;
                                $edit_url    = add_query_arg( [ 'action' => 'edit', 'post_id' => $p->ID ], $base_url );
                            ?>
                                <tr data-post-id="<?php echo $p->ID; ?>">
                                    <td class="bsky-drag-handle" style="cursor:grab;text-align:center;color:#aaa;" title="<?php esc_attr_e( 'Drag to reorder', 'opi-bluesky' ); ?>">
                                        ⠿
                                    </td>
                                    <td><?php echo esc_html( wp_trim_words( $p->post_content, 15, '…' ) ); ?></td>
                                    <td><?php echo esc_html( $type_labels[ $bsky_type ] ?? $bsky_type ); ?></td>
                                    <td><?php echo $est_time ? esc_html( wp_date( $dt_format, $est_time ) ) : '<em>' . esc_html__( 'No slot', 'opi-bluesky' ) . '</em>'; ?></td>
                                    <td style="display:flex;align-items:center;gap:4px;">
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php _e( 'Edit', 'opi-bluesky' ); ?></a>
                                        <button type="button" class="button button-small opibluesky-delete-post" data-post-id="<?php echo $p->ID; ?>">
                                            <?php _e( 'Delete', 'opi-bluesky' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

            <?php if ( ! empty( $uncategorized ) ) : ?>
                <div class="opi-card">
                    <h3 class="opi-section-header">
                        <?php _e( 'Uncategorized', 'opi-bluesky' ); ?>
                        <span class="opi-status-badge opi-status-badge--warn" style="font-size:11px;margin-left:10px;">
                            <?php _e( 'No category assigned', 'opi-bluesky' ); ?>
                        </span>
                    </h3>
                    <table class="wp-list-table widefat fixed">
                        <thead>
                            <tr>
                                <th><?php _e( 'Content', 'opi-bluesky' ); ?></th>
                                <th style="width:90px;"><?php _e( 'Type', 'opi-bluesky' ); ?></th>
                                <th style="width:100px;"><?php _e( 'Actions', 'opi-bluesky' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $uncategorized as $p ) :
                                $bsky_type   = get_post_meta( $p->ID, '_bsky_type', true ) ?: 'post';
                                $type_labels = [ 'post' => __( 'Post', 'opi-bluesky' ), 'repost' => __( 'Repost', 'opi-bluesky' ), 'reply' => __( 'Reply', 'opi-bluesky' ) ];
                                $edit_url    = add_query_arg( [ 'action' => 'edit', 'post_id' => $p->ID ], $base_url );
                            ?>
                                <tr>
                                    <td><?php echo esc_html( wp_trim_words( $p->post_content, 15, '…' ) ); ?></td>
                                    <td><?php echo esc_html( $type_labels[ $bsky_type ] ?? $bsky_type ); ?></td>
                                    <td style="display:flex;align-items:center;gap:4px;">
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php _e( 'Edit', 'opi-bluesky' ); ?></a>
                                        <button type="button" class="button button-small opibluesky-delete-post" data-post-id="<?php echo $p->ID; ?>">
                                            <?php _e( 'Delete', 'opi-bluesky' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php endif; // tab ?>

        <script>
        jQuery( document ).ready( function( $ ) {

            // ── Delete post ───────────────────────────────────────────────
            $( document ).on( 'click', '.opibluesky-delete-post', function() {
                if ( ! confirm( '<?php echo esc_js( __( 'Delete this post?', 'opi-bluesky' ) ); ?>' ) ) return;
                var $btn   = $( this );
                var postId = $btn.data( 'post-id' );
                $btn.prop( 'disabled', true );
                $.post( opiBlueskyAdmin.ajaxUrl, {
                    action:  'opibluesky_delete_post',
                    nonce:   opiBlueskyAdmin.nonce,
                    post_id: postId,
                }, function( response ) {
                    if ( response.success ) {
                        $btn.closest( 'tr' ).fadeOut( 200, function() { $( this ).remove(); } );
                    } else {
                        alert( 'Delete failed: ' + response.data );
                        $btn.prop( 'disabled', false );
                    }
                } ).fail( function() {
                    alert( 'Server error.' );
                    $btn.prop( 'disabled', false );
                } );
            } );

            // ── Category filter (Queued tab) ──────────────────────────────
            $( '#bsky_queue_cat_filter' ).on( 'change', function() {
                var catId = $( this ).val();
                var url   = new URL( window.location.href );
                url.searchParams.set( 'tab', 'queued' );
                if ( catId && catId !== '0' ) {
                    url.searchParams.set( 'cat', catId );
                } else {
                    url.searchParams.delete( 'cat' );
                }
                window.location.href = url.toString();
            } );

            // ── Drag-and-drop reorder (Queued tab) ────────────────────────
            $( '.bsky-sortable' ).sortable( {
                handle:      '.bsky-drag-handle',
                axis:        'y',
                cursor:      'grabbing',
                placeholder: 'ui-state-highlight',
                update: function( event, ui ) {
                    var $tbody     = $( this );
                    var $table     = $tbody.closest( 'table' );
                    var categoryId = $table.data( 'category-id' );
                    var postIds    = [];

                    $tbody.find( 'tr' ).each( function() {
                        postIds.push( $( this ).data( 'post-id' ) );
                    } );

                    $.post( opiBlueskyAdmin.ajaxUrl, {
                        action:      'opibluesky_reorder_queue',
                        nonce:       opiBlueskyAdmin.nonce,
                        category_id: categoryId,
                        post_ids:    postIds,
                    }, function( response ) {
                        if ( ! response.success ) {
                            alert( 'Reorder failed: ' + response.data );
                            return;
                        }

                        // Refresh estimated send times for this category.
                        $.post( opiBlueskyAdmin.ajaxUrl, {
                            action:      'opibluesky_get_estimated_times',
                            nonce:       opiBlueskyAdmin.nonce,
                            category_id: categoryId,
                        }, function( res ) {
                            if ( ! res.success ) return;

                            // Build a lookup: post_id => estimated_time string.
                            var timeMap = {};
                            $.each( res.data, function( i, item ) {
                                timeMap[ item.post_id ] = item.estimated_time;
                            } );

                            // Update each row's Est. Send Time cell (4th td, index 3).
                            $tbody.find( 'tr' ).each( function() {
                                var pid  = $( this ).data( 'post-id' );
                                var $td  = $( this ).find( 'td' ).eq( 3 );
                                var time = timeMap[ pid ];
                                $td.html( time
                                    ? $( '<span>' ).text( time )
                                    : '<em><?php echo esc_js( __( 'No slot', 'opi-bluesky' ) ); ?></em>'
                                );
                            } );
                        } );
                    } ).fail( function() {
                        alert( 'Server error saving order.' );
                    } );
                },
            } );

        } );
        </script>

    <?php endif; // list ?>

    <?php if ( in_array( $action, [ 'new', 'edit' ], true ) ) : ?>

        <?php
        $is_edit = $action === 'edit' && $editing_post;

        // Determine current mode for edit pre-population.
        $cur_mode        = $is_edit ? ( get_post_meta( $editing_post->ID, '_bsky_mode', true ) ?: 'scheduled' ) : 'scheduled';
        $content         = $is_edit ? $editing_post->post_content : '';
        $type            = $is_edit ? ( get_post_meta( $editing_post->ID, '_bsky_type', true ) ?: 'post' ) : 'post';
        $ref_uri         = $is_edit ? get_post_meta( $editing_post->ID, '_bsky_ref_uri', true ) : '';
        $ref_cid         = $is_edit ? get_post_meta( $editing_post->ID, '_bsky_ref_cid', true ) : '';
        $scheduled_at    = $is_edit ? (int) get_post_meta( $editing_post->ID, '_bsky_scheduled_at', true ) : 0;
        $scheduled_val   = $scheduled_at ? wp_date( 'Y-m-d\TH:i', $scheduled_at ) : '';

        $assigned_terms  = $is_edit
            ? wp_get_object_terms( $editing_post->ID, 'bsky_post_category', [ 'fields' => 'ids' ] )
            : [];
        if ( is_wp_error( $assigned_terms ) ) $assigned_terms = [];
        $cur_category_id = ! empty( $assigned_terms ) ? (int) $assigned_terms[0] : 0;

        $all_categories  = get_terms( [ 'taxonomy' => 'bsky_post_category', 'hide_empty' => false ] );
        if ( is_wp_error( $all_categories ) ) $all_categories = [];
        ?>

        <div class="opi-card" style="max-width:800px;">
            <h2 class="opi-section-header">
                <?php echo $is_edit ? esc_html__( 'Edit Post', 'opi-bluesky' ) : esc_html__( 'Add New Post', 'opi-bluesky' ); ?>
            </h2>

            <form method="post">
                <?php wp_nonce_field( 'opibluesky_post_action' ); ?>
                <input type="hidden" name="bsky_post_id" value="<?php echo $is_edit ? $editing_post->ID : 0; ?>">

                <?php /* ── Post type (post / repost / reply) ── */ ?>
                <div class="opi-form-row">
                    <label for="bsky_type"><?php _e( 'Type', 'opi-bluesky' ); ?></label>
                    <div class="opi-form-control">
                        <select id="bsky_type" name="bsky_type">
                            <option value="post"   <?php selected( $type, 'post' ); ?>><?php _e( 'Post', 'opi-bluesky' ); ?></option>
                            <option value="repost" <?php selected( $type, 'repost' ); ?>><?php _e( 'Repost / Quote Post', 'opi-bluesky' ); ?></option>
                            <option value="reply"  <?php selected( $type, 'reply' ); ?>><?php _e( 'Reply', 'opi-bluesky' ); ?></option>
                        </select>
                    </div>
                </div>

                <?php /* ── Ref URI (repost / reply only) ── */ ?>
                <div class="opi-form-row" id="bsky_ref_row" <?php echo $type === 'post' ? 'style="display:none;"' : ''; ?>>
                    <label for="bsky_ref_uri"><?php _e( 'Post URL or URI', 'opi-bluesky' ); ?></label>
                    <div class="opi-form-control">
                        <input type="text" id="bsky_ref_uri" name="bsky_ref_uri"
                               value="<?php echo esc_attr( $ref_uri ); ?>"
                               class="large-text"
                               placeholder="https://bsky.app/profile/user.bsky.social/post/abc123">
                        <p class="description" id="bsky_ref_desc_repost" <?php echo $type !== 'repost' ? 'style="display:none;"' : ''; ?>>
                            <?php _e( 'Leave content blank for a plain repost, or add text to quote post.', 'opi-bluesky' ); ?>
                        </p>
                        <p class="description" id="bsky_ref_desc_reply" <?php echo $type !== 'reply' ? 'style="display:none;"' : ''; ?>>
                            <?php _e( 'The post you are replying to.', 'opi-bluesky' ); ?>
                        </p>
                    </div>
                </div>

                <?php /* ── Content ── */ ?>
                <div class="opi-form-row">
                    <label for="bsky_content"><?php _e( 'Content', 'opi-bluesky' ); ?></label>
                    <div class="opi-form-control">
                        <textarea id="bsky_content" name="bsky_content"
                                  rows="5" class="large-text" maxlength="300"><?php echo esc_textarea( $content ); ?></textarea>
                        <p class="description">
                            <span id="bsky_char_count">0</span>/300 <?php _e( 'characters', 'opi-bluesky' ); ?>
                        </p>
                        <p class="description" id="bsky_content_desc_repost" <?php echo $type !== 'repost' ? 'style="display:none;"' : ''; ?>>
                            <?php _e( 'Optional for reposts — leave blank for a plain repost.', 'opi-bluesky' ); ?>
                        </p>
                    </div>
                </div>

                <?php /* ── Schedule / Queue mode toggle ── */ ?>
                <div class="opi-form-row">
                    <label><?php _e( 'Delivery', 'opi-bluesky' ); ?></label>
                    <div class="opi-form-control">
                        <label style="display:inline-flex;align-items:center;gap:6px;margin-right:20px;">
                            <input type="radio" name="bsky_post_mode" value="scheduled"
                                   id="bsky_mode_scheduled"
                                   <?php checked( $cur_mode, 'scheduled' ); ?>>
                            <?php _e( 'Schedule for specific time', 'opi-bluesky' ); ?>
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:6px;">
                            <input type="radio" name="bsky_post_mode" value="queued"
                                   id="bsky_mode_queued"
                                   <?php checked( $cur_mode, 'queued' ); ?>>
                            <?php _e( 'Add to category queue', 'opi-bluesky' ); ?>
                        </label>
                    </div>
                </div>

                <?php /* ── Datetime (shown when mode = scheduled) ── */ ?>
                <div class="opi-form-row" id="bsky_row_scheduled" <?php echo $cur_mode !== 'scheduled' ? 'style="display:none;"' : ''; ?>>
                    <label for="bsky_scheduled_at"><?php _e( 'Date & Time', 'opi-bluesky' ); ?></label>
                    <div class="opi-form-control">
                        <input type="datetime-local" id="bsky_scheduled_at" name="bsky_scheduled_at"
                               value="<?php echo esc_attr( $scheduled_val ); ?>">
                        <p class="description">
                            <?php printf(
                                /* translators: %s: timezone string */
                                __( 'Site timezone: %s', 'opi-bluesky' ),
                                esc_html( wp_timezone_string() )
                            ); ?>
                        </p>
                    </div>
                </div>

                <?php /* ── Category dropdown (shown when mode = queued) ── */ ?>
                <div class="opi-form-row" id="bsky_row_queued" <?php echo $cur_mode !== 'queued' ? 'style="display:none;"' : ''; ?>>
                    <label for="bsky_category_id"><?php _e( 'Category', 'opi-bluesky' ); ?></label>
                    <div class="opi-form-control">
                        <?php if ( empty( $all_categories ) ) : ?>
                            <p class="description">
                                <?php _e( 'No categories yet.', 'opi-bluesky' ); ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-bluesky&view=categories' ) ); ?>">
                                    <?php _e( 'Create one', 'opi-bluesky' ); ?>
                                </a>
                            </p>
                        <?php else : ?>
                            <select id="bsky_category_id" name="bsky_category_id">
                                <option value=""><?php _e( '— Select category —', 'opi-bluesky' ); ?></option>
                                <?php foreach ( $all_categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->term_id ); ?>"
                                            <?php selected( $cur_category_id, $cat->term_id ); ?>>
                                        <?php echo esc_html( $cat->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Post will be added to the end of this category\'s queue.', 'opi-bluesky' ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <?php submit_button(
                        $is_edit ? __( 'Update Post', 'opi-bluesky' ) : __( 'Save Post', 'opi-bluesky' ),
                        'primary',
                        'opibluesky_save_post',
                        false
                    ); ?>
                    <a href="<?php echo esc_url( $base_url ); ?>" class="button" style="margin-left:8px;">
                        <?php _e( 'Cancel', 'opi-bluesky' ); ?>
                    </a>
                </div>
            </form>
        </div>

        <script>
        jQuery( document ).ready( function( $ ) {

            // ── Post type UI ──────────────────────────────────────────────
            var $type = $( '#bsky_type' );

            function updateTypeUI() {
                var val = $type.val();
                $( '#bsky_ref_row' ).toggle( val !== 'post' );
                $( '#bsky_ref_desc_repost' ).toggle( val === 'repost' );
                $( '#bsky_ref_desc_reply' ).toggle( val === 'reply' );
                $( '#bsky_content_desc_repost' ).toggle( val === 'repost' );
            }

            $type.on( 'change', updateTypeUI );
            updateTypeUI();

            // ── Char counter ──────────────────────────────────────────────
            var $content = $( '#bsky_content' );
            var $count   = $( '#bsky_char_count' );

            function updateCount() {
                $count.text( $content.val().length );
            }

            $content.on( 'input', updateCount );
            updateCount();

            // ── Schedule / Queue toggle ───────────────────────────────────
            function updateModeUI() {
                var mode = $( 'input[name="bsky_post_mode"]:checked' ).val();
                $( '#bsky_row_scheduled' ).toggle( mode === 'scheduled' );
                $( '#bsky_row_queued' ).toggle( mode === 'queued' );
            }

            $( 'input[name="bsky_post_mode"]' ).on( 'change', updateModeUI );
            updateModeUI();
        } );
        </script>

    <?php endif; // new / edit ?>

</div>