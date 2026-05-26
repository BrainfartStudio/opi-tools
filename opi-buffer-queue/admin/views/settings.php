<?php
// admin/views/settings.php

defined( 'ABSPATH' ) || exit;

$message = '';

if ( isset( $_POST['opi_buffer_save'] ) && check_admin_referer( 'opi_buffer_settings' ) ) {
    $raw_rules = $_POST['opi_buffer_rules'] ?? [];
    OPI_Buffer_Settings::update_buffer_limits( $raw_rules );
    do_action( 'opi_buffer_changed' );
    $message = OPI_Tools::notice( 'success', __( 'Settings saved.', 'opi-buffer-queue' ) );
}

$limits = OPI_Buffer_Settings::get_buffer_limits();
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Buffer Settings', 'opi-buffer-queue' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-buffer-queue' ) ); ?>" class="page-title-action">
        &larr; <?php esc_html_e( 'Back to Queue', 'opi-buffer-queue' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php echo $message; ?>

    <form method="post">
        <?php wp_nonce_field( 'opi_buffer_settings' ); ?>

        <div class="opi-card">
            <h2 class="opi-section-header"><?php esc_html_e( 'Publishing Rules', 'opi-buffer-queue' ); ?></h2>

            <p class="description" style="margin-bottom:16px;">
                <?php esc_html_e( 'Define how often posts publish based on queue size. The highest threshold you\'ve crossed wins. Rules are sorted automatically.', 'opi-buffer-queue' ); ?>
            </p>

            <div id="opi-buffer-rules">
                <?php foreach ( $limits as $i => $limit ) : ?>
                <div class="opi-buffer-rule opi-card" style="margin-bottom:12px;">
                    <div class="opi-form-row">
                        <label><?php esc_html_e( 'When queue has', 'opi-buffer-queue' ); ?></label>
                        <div class="opi-form-control">
                            <input type="number"
                                   name="opi_buffer_rules[<?php echo $i; ?>][min_posts]"
                                   value="<?php echo esc_attr( $limit['min_posts'] ); ?>"
                                   min="0" style="width:80px;">
                            <?php esc_html_e( 'or more posts', 'opi-buffer-queue' ); ?>
                        </div>
                    </div>
                    <div class="opi-form-row">
                        <label><?php esc_html_e( 'Publish every', 'opi-buffer-queue' ); ?></label>
                        <div class="opi-form-control">
                            <input type="number"
                                   name="opi_buffer_rules[<?php echo $i; ?>][interval_days]"
                                   value="<?php echo esc_attr( $limit['interval_days'] ); ?>"
                                   min="1" style="width:80px;">
                            <?php esc_html_e( 'days at', 'opi-buffer-queue' ); ?>
                            <input type="time"
                                   name="opi_buffer_rules[<?php echo $i; ?>][time]"
                                   value="<?php echo esc_attr( $limit['time'] ); ?>"
                                   style="width:120px;">
                        </div>
                    </div>
                    <?php if ( $i > 0 ) : ?>
                        <button type="button" class="button opi-buffer-remove-rule">
                            <?php esc_html_e( 'Remove', 'opi-buffer-queue' ); ?>
                        </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="opi-buffer-add-rule" class="button">
                <?php esc_html_e( '+ Add Rule', 'opi-buffer-queue' ); ?>
            </button>
        </div>

        <?php submit_button( __( 'Save Settings', 'opi-buffer-queue' ), 'primary', 'opi_buffer_save' ); ?>
    </form>
</div>

<script>
( function( $ ) {
    var ruleIndex = <?php echo count( $limits ); ?>;

    $( '#opi-buffer-add-rule' ).on( 'click', function() {
        var html =
            '<div class="opi-buffer-rule opi-card" style="margin-bottom:12px;">' +
                '<div class="opi-form-row">' +
                    '<label><?php echo esc_js( __( 'When queue has', 'opi-buffer-queue' ) ); ?></label>' +
                    '<div class="opi-form-control">' +
                        '<input type="number" name="opi_buffer_rules[' + ruleIndex + '][min_posts]" value="10" min="0" style="width:80px;"> ' +
                        '<?php echo esc_js( __( 'or more posts', 'opi-buffer-queue' ) ); ?>' +
                    '</div>' +
                '</div>' +
                '<div class="opi-form-row">' +
                    '<label><?php echo esc_js( __( 'Publish every', 'opi-buffer-queue' ) ); ?></label>' +
                    '<div class="opi-form-control">' +
                        '<input type="number" name="opi_buffer_rules[' + ruleIndex + '][interval_days]" value="2" min="1" style="width:80px;"> ' +
                        '<?php echo esc_js( __( 'days at', 'opi-buffer-queue' ) ); ?> ' +
                        '<input type="time" name="opi_buffer_rules[' + ruleIndex + '][time]" value="08:00" style="width:120px;">' +
                    '</div>' +
                '</div>' +
                '<button type="button" class="button opi-buffer-remove-rule"><?php echo esc_js( __( 'Remove', 'opi-buffer-queue' ) ); ?></button>' +
            '</div>';

        $( '#opi-buffer-rules' ).append( html );
        ruleIndex++;
    } );

    $( document ).on( 'click', '.opi-buffer-remove-rule', function() {
        $( this ).closest( '.opi-buffer-rule' ).remove();
    } );
} )( jQuery );
</script>