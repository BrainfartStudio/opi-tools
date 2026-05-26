<?php
// includes/views/dashboard.php

defined( 'ABSPATH' ) || exit;

$plugins = OPI_Tools::get_plugins();
?>
<div class="wrap">
    <h1><?php _e( 'OPI Tools', 'opi-core' ); ?></h1>

    <?php
    // Collect and display any health alerts first.
    $alerts = [];
    foreach ( $plugins as $slug => $plugin ) {
        if ( ! is_callable( $plugin['health_cb'] ) ) {
            continue;
        }
        $result = call_user_func( $plugin['health_cb'] );
        if ( empty( $result['severity'] ) || $result['severity'] === 'ok' ) {
            continue;
        }
        $alerts[] = array_merge( $result, [ 'label' => $plugin['label'] ] );
    }

    if ( ! empty( $alerts ) ) : ?>
        <h2><?php _e( 'Health Alerts', 'opi-core' ); ?></h2>
        <?php foreach ( $alerts as $alert ) : ?>
            <div class="opi-health-alert opi-health-alert--<?php echo esc_attr( $alert['severity'] ); ?>">
                <strong><?php echo esc_html( $alert['label'] ); ?>:</strong>
                <?php echo esc_html( $alert['message'] ?? '' ); ?>
                <?php if ( ! empty( $alert['action_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $alert['action_url'] ); ?>" class="button button-small opi-health-action">
                        <?php _e( 'Fix this', 'opi-core' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2><?php _e( 'Registered Plugins', 'opi-core' ); ?></h2>

    <?php if ( empty( $plugins ) ) : ?>
        <p><?php _e( 'No sub-plugins registered yet.', 'opi-core' ); ?></p>
    <?php else : ?>
        <div class="opi-dashboard-grid">
            <?php foreach ( $plugins as $slug => $plugin ) :
                $widget = is_callable( $plugin['widget_cb'] )
                    ? call_user_func( $plugin['widget_cb'] )
                    : null;

                $status = $widget['status'] ?? 'ok';
                $value  = $widget['value']  ?? '—';
            ?>
                <div class="opi-dashboard-plugin-card">
                    <h3><?php echo esc_html( $plugin['label'] ); ?></h3>
                    <div class="opi-plugin-version">v<?php echo esc_html( $plugin['version'] ); ?></div>
                    <span class="opi-status-badge opi-status-badge--<?php echo esc_attr( $status ); ?>">
                        <?php echo esc_html( ucfirst( $status ) ); ?>
                    </span>
                    <?php if ( $widget ) : ?>
                        <p style="margin:10px 0 0;"><?php echo esc_html( $value ); ?></p>
                    <?php endif; ?>
                    <p style="margin:10px 0 0;">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>" class="button button-small">
                            <?php _e( 'Manage', 'opi-core' ); ?>
                        </a>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2><?php _e( 'OPI Settings', 'opi-core' ); ?></h2>
    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-tools&view=settings' ) ); ?>" class="button">
            <?php _e( 'Appearance & Settings', 'opi-core' ); ?>
        </a>
    </p>
</div>