<?php
// includes/views/feed-inactive.php

defined( 'ABSPATH' ) || exit;

OPI_RSS::maybe_render_notice();

$feeds = OPI_RSS_DB::get_feeds_by_statuses( [ OPI_RSS_DB::STATUS_INACTIVE, OPI_RSS_DB::STATUS_PENDING ] );

$badge_map = [
    OPI_RSS_DB::STATUS_INACTIVE => [ 'class' => '',      'label' => __( 'Inactive', 'opi-rss' ) ],
    OPI_RSS_DB::STATUS_PENDING  => [ 'class' => '--warn', 'label' => __( 'Pending',  'opi-rss' ) ],
];
?>
<div class="wrap">
    <h1><?php _e( 'RSS Aggregator', 'opi-rss' ); ?></h1>

    <?php OPI_RSS::render_nav( 'inactive' ); ?>

    <div class="opi-card" style="padding:0;">
        <table class="wp-list-table widefat striped" style="border:none;table-layout:auto;">
            <thead>
                <tr>
                    <th style="width:80px;"><?php _e( 'Status', 'opi-rss' ); ?></th>
                    <th style="width:160px;"><?php _e( 'Name', 'opi-rss' ); ?></th>
                    <th style="width:35%;"><?php _e( 'Latest Article', 'opi-rss' ); ?></th>
                    <th style="width:100px;"><?php _e( 'Last Post', 'opi-rss' ); ?></th>
                    <th style="width:100px;"><?php _e( 'Last Fetch', 'opi-rss' ); ?></th>
                    <th style="width:180px;"><?php _e( 'Actions', 'opi-rss' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $feeds ) ) : ?>
                    <tr>
                        <td colspan="6"><?php _e( 'No inactive or pending feeds.', 'opi-rss' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $feeds as $feed ) :
                        $badge = $badge_map[ $feed->status ] ?? $badge_map[ OPI_RSS_DB::STATUS_INACTIVE ];
                    ?>
                        <tr>
                            <td>
                                <?php if ( $badge['class'] ) : ?>
                                    <span class="opi-status-badge opi-status-badge<?php echo esc_attr( $badge['class'] ); ?>">
                                        <?php echo esc_html( $badge['label'] ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="opi-status-badge" style="background:#f0f0f1;color:#50575e;">
                                        <?php echo esc_html( $badge['label'] ); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( stripslashes( $feed->name ) ); ?></td>
                            <td>
                                <?php if ( ! empty( $feed->latest_article ) ) : ?>
                                    <a href="<?php echo esc_url( $feed->latest_article_link ); ?>" target="_blank">
                                        <?php
                                        $title = stripslashes( $feed->latest_article );
                                        echo esc_html( strlen( $title ) > 80 ? substr( $title, 0, 80 ) . '…' : $title );
                                        ?>
                                    </a>
                                <?php else : ?>
                                    <em><?php _e( 'No articles found', 'opi-rss' ); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo ! empty( $feed->latest_article_date )
                                    ? esc_html( OPI_RSS::time_diff( $feed->latest_article_date ) )
                                    : '—'; ?>
                            </td>
                            <td>
                                <?php echo $feed->last_fetch
                                    ? esc_html( OPI_RSS::time_diff( $feed->last_fetch ) )
                                    : __( 'Never', 'opi-rss' ); ?>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'opirss_nonce' ); ?>
                                    <input type="hidden" name="opirss_action" value="activate">
                                    <input type="hidden" name="id" value="<?php echo absint( $feed->id ); ?>">
                                    <button type="submit" class="button button-small button-primary"><?php _e( 'Activate', 'opi-rss' ); ?></button>
                                </form>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-rss&view=edit&id=' . absint( $feed->id ) ) ); ?>"
                                   class="button button-small"><?php _e( 'Edit', 'opi-rss' ); ?></a>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'opirss_nonce' ); ?>
                                    <input type="hidden" name="opirss_action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo absint( $feed->id ); ?>">
                                    <button type="submit" class="button button-small" style="color:#b32d2e;"
                                            onclick="return confirm('<?php esc_attr_e( 'Delete this feed and all its items?', 'opi-rss' ); ?>');">
                                        <?php _e( 'Delete', 'opi-rss' ); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>