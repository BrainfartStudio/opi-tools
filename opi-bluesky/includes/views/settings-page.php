<?php
// includes/views/settings-page.php

defined( 'ABSPATH' ) || exit;

$settings = OPI_Bluesky_Settings::get();
$slots    = OPI_Bluesky_Category_Scheduler::get_slots();
$message  = '';

if ( isset( $_POST['opibluesky_save'] ) && check_admin_referer( 'opibluesky_settings_action' ) ) {
    $raw   = $_POST['opibluesky_settings'] ?? [];
    $saved = OPI_Bluesky_Settings::sanitize( $raw );
    OPI_Bluesky_Settings::update( $saved );
    $settings = OPI_Bluesky_Settings::get();

    // Save category slots.
    $raw_slots = isset( $_POST['opibluesky_slots'] ) && is_array( $_POST['opibluesky_slots'] )
        ? $_POST['opibluesky_slots']
        : [];
    OPI_Bluesky_Category_Scheduler::update_slots(
        OPI_Bluesky_Category_Scheduler::sanitize_slots( $raw_slots )
    );
    $slots = OPI_Bluesky_Category_Scheduler::get_slots();

    // Re-authenticate with new credentials.
    OPI_Bluesky_Auth::clear_session();
    $auth_result = OPI_Bluesky_Auth::authenticate(
        $settings['identifier'],
        OPI_Bluesky_Settings::get_app_password()
    );

    if ( is_wp_error( $auth_result ) ) {
        $message = '<div class="notice notice-error"><p>'
            . __( 'Settings saved, but connection failed: ', 'opi-bluesky' )
            . esc_html( $auth_result->get_error_message() )
            . '</p></div>';
    } else {
        $session = OPI_Bluesky_Auth::get_session();
        $message = '<div class="notice notice-success"><p>'
            . sprintf(
                __( 'Settings saved. Connected as <strong>@%s</strong>.', 'opi-bluesky' ),
                esc_html( $session['handle'] ?? $settings['identifier'] )
            )
            . '</p></div>';
    }
}

$session      = OPI_Bluesky_Auth::get_session();
$is_connected = OPI_Bluesky_Auth::is_authenticated();
$categories   = get_terms( [ 'taxonomy' => 'bsky_post_category', 'hide_empty' => false ] );
$days_labels  = [
    'mon' => __( 'Mon', 'opi-bluesky' ),
    'tue' => __( 'Tue', 'opi-bluesky' ),
    'wed' => __( 'Wed', 'opi-bluesky' ),
    'thu' => __( 'Thu', 'opi-bluesky' ),
    'fri' => __( 'Fri', 'opi-bluesky' ),
    'sat' => __( 'Sat', 'opi-bluesky' ),
    'sun' => __( 'Sun', 'opi-bluesky' ),
];
?>
<div class="wrap">
    <h1><?php _e( 'Bluesky — Settings', 'opi-bluesky' ); ?></h1>

    <?php echo $message; ?>

    <?php if ( $is_connected && empty( $message ) ) : ?>
        <div class="notice notice-success">
            <p><?php printf( __( 'Connected as <strong>@%s</strong>.', 'opi-bluesky' ), esc_html( $session['handle'] ?? '' ) ); ?></p>
        </div>
    <?php elseif ( ! $is_connected && empty( $message ) ) : ?>
        <div class="notice notice-warning">
            <p><?php _e( 'Not connected. Enter your credentials and save.', 'opi-bluesky' ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field( 'opibluesky_settings_action' ); ?>

        <h2><?php _e( 'Account', 'opi-bluesky' ); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="opibluesky_identifier"><?php _e( 'Bluesky Handle or Email', 'opi-bluesky' ); ?></label></th>
                <td>
                    <input type="text" id="opibluesky_identifier" name="opibluesky_settings[identifier]"
                           value="<?php echo esc_attr( $settings['identifier'] ); ?>"
                           class="regular-text" placeholder="you.bsky.social" autocomplete="off">
                    <p class="description"><?php _e( 'Your Bluesky handle or account email.', 'opi-bluesky' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="opibluesky_app_password"><?php _e( 'App Password', 'opi-bluesky' ); ?></label></th>
                <td>
                    <input type="password" id="opibluesky_app_password" name="opibluesky_settings[app_password]"
                           value="" class="regular-text" autocomplete="new-password"
                           placeholder="<?php echo $settings['app_password'] ? __( '(stored — leave blank to keep)', 'opi-bluesky' ) : ''; ?>">
                    <p class="description">
                        <?php _e( 'Generate at ', 'opi-bluesky' ); ?>
                        <a href="https://bsky.app/settings/app-passwords" target="_blank">bsky.app/settings/app-passwords</a>.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e( 'Auto-Post on Publish', 'opi-bluesky' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="opibluesky_settings[auto_post_on_publish]" value="1"
                               <?php checked( $settings['auto_post_on_publish'] ); ?>>
                        <?php _e( 'Automatically post to Bluesky when a WordPress post is published.', 'opi-bluesky' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e( 'Connection', 'opi-bluesky' ); ?></th>
                <td>
                    <button type="button" id="opibluesky-test-connection" class="button"><?php _e( 'Test Connection', 'opi-bluesky' ); ?></button>
                    <span id="opibluesky-test-result" style="margin-left:10px;"></span>
                </td>
            </tr>
        </table>

        <h2><?php _e( 'Category Scheduler', 'opi-bluesky' ); ?></h2>
        <p class="description" style="margin-bottom:15px;">
            <?php _e( 'Define time slots to automatically fire the next pending post from a category. Useful for maintaining a consistent daily posting schedule.', 'opi-bluesky' ); ?>
        </p>

        <div id="opibluesky-slots">
            <?php foreach ( $slots as $i => $slot ) : ?>
                <div class="opibluesky-slot-row">
                    <?php self_render_slot_row( $i, $slot, $categories, $days_labels ); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <p><button type="button" id="opibluesky-add-slot" class="button"><?php _e( 'Add Time Slot', 'opi-bluesky' ); ?></button></p>
        <p class="description"><?php _e( 'Each slot fires once per matching day/time. If the category has no pending posts, it is silently skipped.', 'opi-bluesky' ); ?></p>

        <?php submit_button( __( 'Save Settings', 'opi-bluesky' ), 'primary', 'opibluesky_save' ); ?>
    </form>
</div>

<?php
// Inline helper — renders a single slot row.
function self_render_slot_row( int $i, array $slot, array $categories, array $days_labels ): void {
    $all_days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];
    ?>
    <div style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap;padding:12px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;margin-bottom:10px;">
        <label style="display:flex;flex-direction:column;gap:4px;">
            <span><?php _e( 'Time', 'opi-bluesky' ); ?></span>
            <input type="time" name="opibluesky_slots[<?php echo $i; ?>][time]"
                   value="<?php echo esc_attr( $slot['time'] ?? '08:00' ); ?>" style="width:120px;">
        </label>

        <label style="display:flex;flex-direction:column;gap:4px;">
            <span><?php _e( 'Category', 'opi-bluesky' ); ?></span>
            <select name="opibluesky_slots[<?php echo $i; ?>][category_id]">
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo $cat->term_id; ?>"
                        <?php selected( (int) ( $slot['category_id'] ?? 0 ), $cat->term_id ); ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </option>
                <?php endforeach; ?>
                <?php if ( empty( $categories ) ) : ?>
                    <option value=""><?php _e( '— No categories yet —', 'opi-bluesky' ); ?></option>
                <?php endif; ?>
            </select>
        </label>

        <div style="display:flex;flex-direction:column;gap:4px;">
            <span><?php _e( 'Days', 'opi-bluesky' ); ?></span>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php foreach ( $days_labels as $key => $label ) : ?>
                    <label style="display:flex;align-items:center;gap:3px;">
                        <input type="checkbox"
                               name="opibluesky_slots[<?php echo $i; ?>][days][]"
                               value="<?php echo $key; ?>"
                               <?php checked( in_array( $key, $slot['days'] ?? $all_days, true ) ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display:flex;align-items:flex-end;">
            <button type="button" class="button opibluesky-remove-slot"><?php _e( 'Remove', 'opi-bluesky' ); ?></button>
        </div>
    </div>
    <?php
}
?>

<script>
jQuery(document).ready(function($) {
    var slotIndex = <?php echo count( $slots ); ?>;
    var allDays   = ['mon','tue','wed','thu','fri','sat','sun'];
    var dayLabels = <?php echo wp_json_encode( $days_labels ); ?>;
    var categories = <?php echo wp_json_encode( array_map( fn($c) => [ 'id' => $c->term_id, 'name' => $c->name ], is_array( $categories ) ? $categories : [] ) ); ?>;

    $('#opibluesky-add-slot').on('click', function() {
        var catOptions = categories.map(function(c) {
            return '<option value="' + c.id + '">' + c.name + '</option>';
        }).join('');
        if (!catOptions) catOptions = '<option value="">— No categories yet —</option>';

        var dayCheckboxes = allDays.map(function(d) {
            return '<label style="display:flex;align-items:center;gap:3px;">' +
                '<input type="checkbox" name="opibluesky_slots[' + slotIndex + '][days][]" value="' + d + '" checked> ' +
                dayLabels[d] + '</label>';
        }).join('');

        var html = '<div style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap;padding:12px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;margin-bottom:10px;">' +
            '<label style="display:flex;flex-direction:column;gap:4px;"><span>Time</span>' +
            '<input type="time" name="opibluesky_slots[' + slotIndex + '][time]" value="08:00" style="width:120px;"></label>' +
            '<label style="display:flex;flex-direction:column;gap:4px;"><span>Category</span>' +
            '<select name="opibluesky_slots[' + slotIndex + '][category_id]">' + catOptions + '</select></label>' +
            '<div style="display:flex;flex-direction:column;gap:4px;"><span>Days</span>' +
            '<div style="display:flex;gap:8px;flex-wrap:wrap;">' + dayCheckboxes + '</div></div>' +
            '<div style="display:flex;align-items:flex-end;">' +
            '<button type="button" class="button opibluesky-remove-slot">Remove</button></div>' +
            '</div>';

        $('#opibluesky-slots').append(html);
        slotIndex++;
    });

    $(document).on('click', '.opibluesky-remove-slot', function() {
        $(this).closest('div[style]').remove();
    });

    $('#opibluesky-test-connection').on('click', function() {
        var $btn    = $(this);
        var $result = $('#opibluesky-test-result');
        $btn.prop('disabled', true).text('Testing...');
        $result.text('').css('color', '');

        $.post(opiBlueskyAdmin.ajaxUrl, {
            action: 'opibluesky_test_connection',
            nonce:  opiBlueskyAdmin.nonce,
        }, function(response) {
            if (response.success) {
                $result.css('color', '#2271b1').text('Connected as @' + response.data.handle);
            } else {
                $result.css('color', '#d63638').text('Failed: ' + response.data);
            }
        }).fail(function() {
            $result.css('color', '#d63638').text('Server error.');
        }).always(function() {
            $btn.prop('disabled', false).text('Test Connection');
        });
    });
});
</script>