<?php
// admin/views/queue.php

defined( 'ABSPATH' ) || exit;

$list_table     = new OPI_Buffer_List_Table();
$list_table->prepare_items();

$count    = OPI_Buffer_Manager::get_buffer_count();
$interval = OPI_Buffer_Scheduler::get_interval_for_count( $count );
$limits   = OPI_Buffer_Settings::get_buffer_limits();
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Buffer Queue', 'opi-buffer' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add New', 'opi-buffer' ); ?>
    </a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-buffer&view=settings' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Settings', 'opi-buffer' ); ?>
    </a>
    <hr class="wp-header-end">

    <div class="opi-card">
        <h2 class="opi-section-header"><?php esc_html_e( 'Buffer Status', 'opi-buffer' ); ?></h2>
        <div class="opi-two-col">
            <div>
                <p>
                    <strong><?php esc_html_e( 'Posts in queue:', 'opi-buffer' ); ?></strong>
                    <?php echo $count; ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Current interval:', 'opi-buffer' ); ?></strong>
                    <?php printf(
                        esc_html__( 'Every %d %s at %s', 'opi-buffer' ),
                        $interval['interval_days'],
                        $interval['interval_days'] === 1
                            ? esc_html__( 'day', 'opi-buffer' )
                            : esc_html__( 'days', 'opi-buffer' ),
                        esc_html( $interval['time'] )
                    ); ?>
                </p>
            </div>

            <?php if ( count( $limits ) > 1 ) : ?>
            <div>
                <strong><?php esc_html_e( 'Thresholds:', 'opi-buffer' ); ?></strong><br>
                <?php foreach ( $limits as $limit ) :
                    $active = $count >= $limit['min_posts'];
                ?>
                    <span style="<?php echo $active ? 'color:var(--opi-accent,#2271b1);font-weight:600;' : 'color:#646970;'; ?>">
                        <?php printf(
                            esc_html__( '%d+ posts → every %d days', 'opi-buffer' ),
                            $limit['min_posts'],
                            $limit['interval_days']
                        ); ?>
                    </span><br>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( $count === 0 ) : ?>
        <?php echo OPI_Tools::notice( 'info', __( 'No posts in buffer. Set a post\'s status to "Buffer" to add it to the queue.', 'opi-buffer' ) ); ?>
    <?php else : ?>
        <form method="get">
            <input type="hidden" name="page" value="opi-buffer">
            <?php $list_table->display(); ?>
        </form>
    <?php endif; ?>
</div>