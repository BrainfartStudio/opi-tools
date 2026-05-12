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

    $raw_slots = isset( $_POST['opibluesky_slots'] ) && is_array( $_POST['opibluesky_slots'] )
        ? $_POST['opibluesky_slots']
        : [];
    OPI_Bluesky_Category_Scheduler::update_slots(
        OPI_Bluesky_Category_Scheduler::sanitize_slots( $raw_slots )
    );
    $slots = OPI_Bluesky_Category_Scheduler::get_slots();

    OPI_Bluesky_Auth::clear_session();
    $auth_result = OPI_Bluesky_Auth::authenticate(
        $settings['identifier'],
        OPI_Bluesky_Settings::get_app_password()
    );

    if ( is_wp_error( $auth_result ) ) {
        $message = OPI_Tools::notice( 'error',
            __( 'Settings saved, but connection failed: ', 'opi-bluesky' )
            . esc_html( $auth_result->get_error_message() )
        );
    } else {
        $session = OPI_Bluesky_Auth::get_session();
        $message = OPI_Tools::notice( 'success', sprintf(
            __( 'Settings saved. Connected as <strong>@%s</strong>.', 'opi-bluesky' ),
            esc_html( $session['handle'] ?? $settings['identifier'] )
        ) );
    }
}

$session      = OPI_Bluesky_Auth::get_session();
$is_connected = OPI_Bluesky_Auth::is_authenticated();
$categories   = get_terms( [ 'taxonomy' => 'bsky_post_category', 'hide_empty' => false ] );
if ( is_wp_error( $categories ) ) {
    $categories = [];
}
$days_labels = [
    'mon' => __( 'Mon', 'opi-bluesky' ),
    'tue' => __( 'Tue', 'opi-bluesky' ),
    'wed' => __( 'Wed', 'opi-bluesky' ),
    'thu' => __( 'Thu', 'opi-bluesky' ),
    'fri' => __( 'Fri', 'opi-bluesky' ),
    'sat' => __( 'Sat', 'opi-bluesky' ),
    'sun' => __( 'Sun', 'opi-bluesky' ),
];

$back_url = admin_url( 'admin.php?page=opi-bluesky' );
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Bluesky — Settings', 'opi-bluesky' ); ?></h1>
    <a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action"><?php _e( 'Scheduled Posts', 'opi-bluesky' ); ?></a>
    <hr class="wp-header-end">

    <?php echo $message; ?>

    <?php if ( $is_connected && empty( $message ) ) : ?>
        <?php echo OPI_Tools::notice( 'success', sprintf(
            __( 'Connected as <strong>@%s</strong>.', 'opi-bluesky' ),
            esc_html( $session['handle'] ?? '' )
        ) ); ?>
    <?php elseif ( ! $is_connected && empty( $message ) ) : ?>
        <?php echo OPI_Tools::notice( 'warning', __( 'Not connected. Enter your credentials and save.', 'opi-bluesky' ) ); ?>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field( 'opibluesky_settings_action' ); ?>

        <!-- Account -->
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Account', 'opi-bluesky' ); ?></h2>

            <div class="opi-form-row">
                <label for="opibluesky_identifier"><?php _e( 'Handle or Email', 'opi-bluesky' ); ?></label>
                <div class="opi-form-control">
                    <input type="text" id="opibluesky_identifier"
                           name="opibluesky_settings[identifier]"
                           value="<?php echo esc_attr( $settings['identifier'] ); ?>"
                           class="regular-text" placeholder="you.bsky.social" autocomplete="off">
                    <p class="description"><?php _e( 'Your Bluesky handle or account email.', 'opi-bluesky' ); ?></p>
                </div>
            </div>

            <div class="opi-form-row">
                <label for="opibluesky_app_password"><?php _e( 'App Password', 'opi-bluesky' ); ?></label>
                <div class="opi-form-control">
                    <input type="password" id="opibluesky_app_password"
                           name="opibluesky_settings[app_password]"
                           value="" class="regular-text" autocomplete="new-password"
                           placeholder="<?php echo $settings['app_password'] ? esc_attr__( '(stored — leave blank to keep)', 'opi-bluesky' ) : ''; ?>">
                    <p class="description">
                        <?php _e( 'Generate at ', 'opi-bluesky' ); ?>
                        <a href="https://bsky.app/settings/app-passwords" target="_blank">bsky.app/settings/app-passwords</a>.
                    </p>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Auto-Post on Publish', 'opi-bluesky' ); ?></label>
                <div class="opi-form-control">
                    <label>
                        <input type="checkbox" name="opibluesky_settings[auto_post_on_publish]" value="1"
                               <?php checked( $settings['auto_post_on_publish'] ); ?>>
                        <?php _e( 'Automatically post to Bluesky when a WordPress post is published.', 'opi-bluesky' ); ?>
                    </label>
                </div>
            </div>

            <div class="opi-form-row">
                <label><?php _e( 'Connection', 'opi-bluesky' ); ?></label>
                <div class="opi-form-control">
                    <button type="button" id="opibluesky-test-connection" class="button">
                        <?php _e( 'Test Connection', 'opi-bluesky' ); ?>
                    </button>
                    <span id="opibluesky-test-result" style="margin-left:10px;"></span>
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Categories', 'opi-bluesky' ); ?></h2>
            <p class="description" style="margin-bottom:12px;">
                <?php _e( 'Categories group scheduled posts for use in time slots. Posts assigned to a category are sent in order when the slot fires.', 'opi-bluesky' ); ?>
            </p>

            <div id="opibluesky-category-list" style="margin-bottom:12px;">
                <?php if ( empty( $categories ) ) : ?>
                    <p id="opibluesky-no-categories" style="color:#646970;"><?php _e( 'No categories yet.', 'opi-bluesky' ); ?></p>
                <?php else : ?>
                    <?php foreach ( $categories as $cat ) :
                        $count = (int) $cat->count;
                        ?>
                        <div class="opibluesky-category-row" data-term-id="<?php echo $cat->term_id; ?>"
                             style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f0f0f1;">
                            <span class="opibluesky-cat-name" style="flex:1;font-weight:500;">
                                <?php echo esc_html( $cat->name ); ?>
                            </span>
                            <span style="color:#646970;font-size:12px;">
                                <?php printf( _n( '%d post', '%d posts', $count, 'opi-bluesky' ), $count ); ?>
                            </span>
                            <button type="button" class="button button-small opibluesky-rename-category">
                                <?php _e( 'Rename', 'opi-bluesky' ); ?>
                            </button>
                            <button type="button" class="button button-small opibluesky-delete-category"
                                    data-confirm="<?php esc_attr_e( 'Delete this category? Assigned posts will become uncategorized.', 'opi-bluesky' ); ?>">
                                <?php _e( 'Delete', 'opi-bluesky' ); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="display:flex;gap:8px;align-items:center;">
                <input type="text" id="opibluesky-new-category-name" class="regular-text"
                       placeholder="<?php esc_attr_e( 'New category name', 'opi-bluesky' ); ?>">
                <button type="button" id="opibluesky-add-category" class="button">
                    <?php _e( 'Add Category', 'opi-bluesky' ); ?>
                </button>
                <span id="opibluesky-category-msg" style="font-size:13px;"></span>
            </div>
        </div>

        <!-- Category Scheduler -->
        <div class="opi-card">
            <h2 class="opi-section-header"><?php _e( 'Category Scheduler', 'opi-bluesky' ); ?></h2>
            <p class="description" style="margin-bottom:15px;">
                <?php _e( 'Define time slots to automatically fire the next pending post from a category. Each slot fires once per matching day/time.', 'opi-bluesky' ); ?>
            </p>

            <div id="opibluesky-slots">
                <?php foreach ( $slots as $i => $slot ) :
                    opibluesky_render_slot_row( $i, $slot, $categories, $days_labels );
                endforeach; ?>
            </div>

            <p>
                <button type="button" id="opibluesky-add-slot" class="button">
                    <?php _e( 'Add Time Slot', 'opi-bluesky' ); ?>
                </button>
            </p>
            <p class="description">
                <?php _e( 'If the category has no pending posts, the slot is silently skipped.', 'opi-bluesky' ); ?>
            </p>
        </div>

        <?php submit_button( __( 'Save Settings', 'opi-bluesky' ), 'primary', 'opibluesky_save' ); ?>
    </form>
</div>

<?php
function opibluesky_render_slot_row( int $i, array $slot, array $categories, array $days_labels ): void {
    $all_days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];

    // Pending post count for this category.
    $pending_count = 0;
    if ( ! empty( $slot['category_id'] ) ) {
        $pending_posts = get_posts( [
            'post_type'      => OPI_Bluesky_Post_Type::CPT,
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'fields'         => 'ids',
            'tax_query'      => [ [
                'taxonomy' => 'bsky_post_category',
                'field'    => 'term_id',
                'terms'    => (int) $slot['category_id'],
            ] ],
            'meta_query'     => [ [
                'key'     => '_bsky_sent',
                'value'   => '0',
                'compare' => '=',
            ] ],
        ] );
        $pending_count = count( $pending_posts );
    }
    ?>
    <div class="opibluesky-slot-row opi-card" style="margin-bottom:10px;padding:12px 16px;">
        <div style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap;">
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
                <?php if ( $pending_count < 3 ) : ?>
                    <span style="font-size:11px;color:<?php echo $pending_count === 0 ? '#d63638' : '#8a6d00'; ?>;">
                        <?php printf(
                            _n( '%d post pending', '%d posts pending', $pending_count, 'opi-bluesky' ),
                            $pending_count
                        ); ?>
                    </span>
                <?php else : ?>
                    <span style="font-size:11px;color:#1a7431;">
                        <?php printf(
                            _n( '%d post pending', '%d posts pending', $pending_count, 'opi-bluesky' ),
                            $pending_count
                        ); ?>
                    </span>
                <?php endif; ?>
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
    </div>
    <?php
}
?>

<script>
jQuery(document).ready(function($) {
    var slotIndex  = <?php echo count( $slots ); ?>;
    var allDays    = ['mon','tue','wed','thu','fri','sat','sun'];
    var dayLabels  = <?php echo wp_json_encode( $days_labels ); ?>;
    var categories = <?php echo wp_json_encode( array_map(
        fn( $c ) => [ 'id' => $c->term_id, 'name' => $c->name ],
        $categories
    ) ); ?>;

    // ── Slot add/remove ───────────────────────────────────────────────────────

    function buildCatOptions() {
        if ( ! categories.length ) return '<option value="">— No categories yet —</option>';
        return categories.map( c => '<option value="' + c.id + '">' + c.name + '</option>' ).join('');
    }

    $('#opibluesky-add-slot').on('click', function() {
        var dayCheckboxes = allDays.map( d =>
            '<label style="display:flex;align-items:center;gap:3px;">' +
            '<input type="checkbox" name="opibluesky_slots[' + slotIndex + '][days][]" value="' + d + '" checked> ' +
            dayLabels[d] + '</label>'
        ).join('');

        var html =
            '<div class="opibluesky-slot-row opi-card" style="margin-bottom:10px;padding:12px 16px;">' +
            '<div style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap;">' +
            '<label style="display:flex;flex-direction:column;gap:4px;"><span><?php esc_js( _e( 'Time', 'opi-bluesky' ) ); ?></span>' +
            '<input type="time" name="opibluesky_slots[' + slotIndex + '][time]" value="08:00" style="width:120px;"></label>' +
            '<label style="display:flex;flex-direction:column;gap:4px;"><span><?php esc_js( _e( 'Category', 'opi-bluesky' ) ); ?></span>' +
            '<select name="opibluesky_slots[' + slotIndex + '][category_id]">' + buildCatOptions() + '</select></label>' +
            '<div style="display:flex;flex-direction:column;gap:4px;"><span><?php esc_js( _e( 'Days', 'opi-bluesky' ) ); ?></span>' +
            '<div style="display:flex;gap:8px;flex-wrap:wrap;">' + dayCheckboxes + '</div></div>' +
            '<div style="display:flex;align-items:flex-end;">' +
            '<button type="button" class="button opibluesky-remove-slot"><?php esc_js( _e( 'Remove', 'opi-bluesky' ) ); ?></button></div>' +
            '</div></div>';

        $('#opibluesky-slots').append(html);
        slotIndex++;
    });

    $(document).on('click', '.opibluesky-remove-slot', function() {
        $(this).closest('.opibluesky-slot-row').remove();
    });

    // ── Test connection ───────────────────────────────────────────────────────

    $('#opibluesky-test-connection').on('click', function() {
        var $btn    = $(this);
        var $result = $('#opibluesky-test-result');
        $btn.prop('disabled', true).text('<?php esc_js( _e( 'Testing...', 'opi-bluesky' ) ); ?>');
        $result.text('').css('color', '');

        $.post(opiBlueskyAdmin.ajaxUrl, {
            action: 'opibluesky_test_connection',
            nonce:  opiBlueskyAdmin.nonce,
        }, function(response) {
            if (response.success) {
                $result.css('color', '#2271b1').text('<?php esc_js( _e( 'Connected as @', 'opi-bluesky' ) ); ?>' + response.data.handle);
            } else {
                $result.css('color', '#d63638').text('<?php esc_js( _e( 'Failed: ', 'opi-bluesky' ) ); ?>' + response.data);
            }
        }).fail(function() {
            $result.css('color', '#d63638').text('Server error.');
        }).always(function() {
            $btn.prop('disabled', false).text('<?php esc_js( _e( 'Test Connection', 'opi-bluesky' ) ); ?>');
        });
    });

    // ── Category management ───────────────────────────────────────────────────

    function categoryRowHtml(termId, name) {
        return '<div class="opibluesky-category-row" data-term-id="' + termId + '" ' +
            'style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f0f0f1;">' +
            '<span class="opibluesky-cat-name" style="flex:1;font-weight:500;">' + name + '</span>' +
            '<span style="color:#646970;font-size:12px;">0 posts</span>' +
            '<button type="button" class="button button-small opibluesky-rename-category"><?php esc_js( _e( 'Rename', 'opi-bluesky' ) ); ?></button>' +
            '<button type="button" class="button button-small opibluesky-delete-category" ' +
            'data-confirm="<?php echo esc_js( __( 'Delete this category? Assigned posts will become uncategorized.', 'opi-bluesky' ) ); ?>">' +
            '<?php esc_js( _e( 'Delete', 'opi-bluesky' ) ); ?></button>' +
            '</div>';
    }

    // Add category
    $('#opibluesky-add-category').on('click', function() {
        var name = $('#opibluesky-new-category-name').val().trim();
        if ( ! name ) return;

        var $btn = $(this);
        var $msg = $('#opibluesky-category-msg');
        $btn.prop('disabled', true);
        $msg.text('').css('color', '');

        OPI.ajax('opibluesky_add_category', { name: name }, function(response) {
            if (response.success) {
                var term = response.data;
                categories.push({ id: term.term_id, name: term.name });
                $('#opibluesky-no-categories').remove();
                $('#opibluesky-category-list').append(categoryRowHtml(term.term_id, term.name));
                $('#opibluesky-new-category-name').val('');
                $msg.css('color', '#1a7431').text('<?php esc_js( _e( 'Category added.', 'opi-bluesky' ) ); ?>');
            } else {
                $msg.css('color', '#d63638').text(response.data);
            }
            $btn.prop('disabled', false);
        });
    });

    // Delete category
    $(document).on('click', '.opibluesky-delete-category', function() {
        var $row   = $(this).closest('.opibluesky-category-row');
        var termId = $row.data('term-id');
        var msg    = $(this).data('confirm') || '<?php echo esc_js( __( 'Delete this category?', 'opi-bluesky' ) ); ?>';

        if ( ! confirm(msg) ) return;

        OPI.ajax('opibluesky_delete_category', { term_id: termId }, function(response) {
            if (response.success) {
                $row.remove();
                categories = categories.filter( c => c.id !== termId );
                if ( ! $('#opibluesky-category-list .opibluesky-category-row').length ) {
                    $('#opibluesky-category-list').append(
                        '<p id="opibluesky-no-categories" style="color:#646970;"><?php esc_js( _e( 'No categories yet.', 'opi-bluesky' ) ); ?></p>'
                    );
                }
            } else {
                alert(response.data);
            }
        });
    });

    // Rename category
    $(document).on('click', '.opibluesky-rename-category', function() {
        var $row     = $(this).closest('.opibluesky-category-row');
        var termId   = $row.data('term-id');
        var $nameEl  = $row.find('.opibluesky-cat-name');
        var current  = $nameEl.text().trim();
        var newName  = prompt('<?php echo esc_js( __( 'New name:', 'opi-bluesky' ) ); ?>', current);

        if ( ! newName || newName === current ) return;

        OPI.ajax('opibluesky_rename_category', { term_id: termId, name: newName }, function(response) {
            if (response.success) {
                $nameEl.text(newName);
                categories = categories.map( c => c.id === termId ? { id: c.id, name: newName } : c );
            } else {
                alert(response.data);
            }
        });
    });
});
</script>