<?php
// includes/views/categories.php

defined( 'ABSPATH' ) || exit;

$categories  = get_terms( [ 'taxonomy' => 'bsky_post_category', 'hide_empty' => false ] );
if ( is_wp_error( $categories ) ) {
    $categories = [];
}

$slots      = OPI_Bluesky_Category_Scheduler::get_slots();
$days_labels = [
    'mon' => __( 'Mon', 'opi-bluesky' ),
    'tue' => __( 'Tue', 'opi-bluesky' ),
    'wed' => __( 'Wed', 'opi-bluesky' ),
    'thu' => __( 'Thu', 'opi-bluesky' ),
    'fri' => __( 'Fri', 'opi-bluesky' ),
    'sat' => __( 'Sat', 'opi-bluesky' ),
    'sun' => __( 'Sun', 'opi-bluesky' ),
];
$message = '';

if ( isset( $_POST['opibluesky_save_slots'] ) && check_admin_referer( 'opibluesky_slots_action' ) ) {
    $raw_slots = isset( $_POST['opibluesky_slots'] ) && is_array( $_POST['opibluesky_slots'] )
        ? $_POST['opibluesky_slots']
        : [];
    OPI_Bluesky_Category_Scheduler::update_slots(
        OPI_Bluesky_Category_Scheduler::sanitize_slots( $raw_slots )
    );
    $slots   = OPI_Bluesky_Category_Scheduler::get_slots();
    $message = OPI_Tools::notice( 'success', __( 'Schedule saved.', 'opi-bluesky' ) );
}
?>
<div class="wrap">
    <h1><?php _e( 'Bluesky', 'opi-bluesky' ); ?></h1>

    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-bluesky' ) ); ?>"
           class="nav-tab"><?php _e( 'Scheduled Posts', 'opi-bluesky' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-bluesky&view=categories' ) ); ?>"
           class="nav-tab nav-tab-active"><?php _e( 'Categories', 'opi-bluesky' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=opi-bluesky&view=settings' ) ); ?>"
           class="nav-tab"><?php _e( 'Settings', 'opi-bluesky' ); ?></a>
    </nav>

    <?php echo $message; ?>

    <?php // ── Category Management ──────────────────────────────────────────── ?>

    <div class="opi-card">
        <h2 class="opi-section-header"><?php _e( 'Categories', 'opi-bluesky' ); ?></h2>

        <?php if ( empty( $categories ) ) : ?>
            <p><?php _e( 'No categories yet. Add one below.', 'opi-bluesky' ); ?></p>
        <?php else : ?>
            <table class="widefat striped" style="margin-bottom:16px;">
                <thead>
                    <tr>
                        <th><?php _e( 'Name', 'opi-bluesky' ); ?></th>
                        <th><?php _e( 'Posts', 'opi-bluesky' ); ?></th>
                        <th><?php _e( 'Actions', 'opi-bluesky' ); ?></th>
                    </tr>
                </thead>
                <tbody id="opibluesky-category-list">
                    <?php foreach ( $categories as $cat ) :
                        $count = get_posts( [
                            'post_type'      => 'bsky_post',
                            'post_status'    => 'publish',
                            'numberposts'    => -1,
                            'fields'         => 'ids',
                            'tax_query'      => [ [ 'taxonomy' => 'bsky_post_category', 'field' => 'term_id', 'terms' => $cat->term_id ] ],
                            'meta_query'     => [ [ 'key' => '_bsky_sent', 'value' => '0', 'compare' => '=' ] ],
                        ] );
                    ?>
                        <tr data-term-id="<?php echo esc_attr( $cat->term_id ); ?>">
                            <td>
                                <span class="opibluesky-cat-name"><?php echo esc_html( $cat->name ); ?></span>
                                <input type="text" class="opibluesky-cat-rename-input regular-text"
                                       value="<?php echo esc_attr( $cat->name ); ?>"
                                       style="display:none;">
                            </td>
                            <td><?php echo count( $count ); ?> <?php _e( 'pending', 'opi-bluesky' ); ?></td>
                            <td>
                                <button type="button" class="button button-small opibluesky-rename-btn"><?php _e( 'Rename', 'opi-bluesky' ); ?></button>
                                <button type="button" class="button button-small opibluesky-rename-save" style="display:none;"><?php _e( 'Save', 'opi-bluesky' ); ?></button>
                                <button type="button" class="button button-small opibluesky-rename-cancel" style="display:none;"><?php _e( 'Cancel', 'opi-bluesky' ); ?></button>
                                <button type="button" class="button button-small opibluesky-delete-cat" style="margin-left:4px;"><?php _e( 'Delete', 'opi-bluesky' ); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" id="opibluesky-new-cat-name" class="regular-text"
                   placeholder="<?php esc_attr_e( 'New category name', 'opi-bluesky' ); ?>">
            <button type="button" id="opibluesky-add-cat" class="button button-primary">
                <?php _e( 'Add Category', 'opi-bluesky' ); ?>
            </button>
            <span id="opibluesky-add-cat-result" style="margin-left:8px;"></span>
        </div>
    </div>

    <?php // ── Category Scheduler ───────────────────────────────────────────── ?>

    <div class="opi-card">
        <h2 class="opi-section-header"><?php _e( 'Category Scheduler', 'opi-bluesky' ); ?></h2>
        <p class="description" style="margin-bottom:16px;">
            <?php _e( 'Define time slots to automatically fire the next pending post from a category.', 'opi-bluesky' ); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field( 'opibluesky_slots_action' ); ?>

            <div id="opibluesky-slots">
                <?php foreach ( $slots as $i => $slot ) :
                    opibluesky_render_slot_row( $i, $slot, $categories, $days_labels );
                endforeach; ?>
            </div>

            <p style="margin-bottom:16px;">
                <button type="button" id="opibluesky-add-slot" class="button">
                    <?php _e( 'Add Time Slot', 'opi-bluesky' ); ?>
                </button>
            </p>
            <p class="description" style="margin-bottom:16px;">
                <?php _e( 'Each slot fires once per matching day/time. If the category has no pending posts, it is silently skipped.', 'opi-bluesky' ); ?>
            </p>

            <?php submit_button( __( 'Save Schedule', 'opi-bluesky' ), 'primary', 'opibluesky_save_slots' ); ?>
        </form>
    </div>
</div>

<?php
function opibluesky_render_slot_row( int $i, array $slot, array $categories, array $days_labels ): void {
    $all_days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];
    ?>
    <div class="opibluesky-slot-row" style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap;padding:12px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;margin-bottom:10px;">
        <label style="display:flex;flex-direction:column;gap:4px;">
            <span><?php _e( 'Time', 'opi-bluesky' ); ?></span>
            <input type="time" name="opibluesky_slots[<?php echo $i; ?>][time]"
                   value="<?php echo esc_attr( $slot['time'] ?? '08:00' ); ?>" style="width:120px;">
        </label>

        <label style="display:flex;flex-direction:column;gap:4px;">
            <span><?php _e( 'Category', 'opi-bluesky' ); ?></span>
            <select name="opibluesky_slots[<?php echo $i; ?>][category_id]">
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->term_id ); ?>"
                        <?php selected( (int) ( $slot['category_id'] ?? 0 ), $cat->term_id ); ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div style="display:flex;flex-direction:column;gap:4px;">
            <span><?php _e( 'Days', 'opi-bluesky' ); ?></span>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php foreach ( $all_days as $day ) : ?>
                    <label style="display:flex;align-items:center;gap:3px;">
                        <input type="checkbox"
                               name="opibluesky_slots[<?php echo $i; ?>][days][]"
                               value="<?php echo esc_attr( $day ); ?>"
                               <?php checked( in_array( $day, $slot['days'] ?? [], true ) ); ?>>
                        <?php echo esc_html( $days_labels[ $day ] ); ?>
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

    // ── Add category ─────────────────────────────────────────────────────────

    $('#opibluesky-add-cat').on('click', function() {
        var name    = $('#opibluesky-new-cat-name').val().trim();
        var $result = $('#opibluesky-add-cat-result');
        if ( ! name ) return;

        $.post(opiBlueskyAdmin.ajaxUrl, {
            action: 'opibluesky_add_category',
            nonce:  opiBlueskyAdmin.nonce,
            name:   name,
        }, function(response) {
            if ( response.success ) {
                var termId = response.data.term_id;
                var catName = response.data.name;

                // Add row to table.
                var row = '<tr data-term-id="' + termId + '">' +
                    '<td><span class="opibluesky-cat-name">' + $('<div>').text(catName).html() + '</span>' +
                    '<input type="text" class="opibluesky-cat-rename-input regular-text" value="' + $('<div>').text(catName).html() + '" style="display:none;"></td>' +
                    '<td>0 <?php echo esc_js( __( 'pending', 'opi-bluesky' ) ); ?></td>' +
                    '<td>' +
                    '<button type="button" class="button button-small opibluesky-rename-btn"><?php echo esc_js( __( 'Rename', 'opi-bluesky' ) ); ?></button> ' +
                    '<button type="button" class="button button-small opibluesky-rename-save" style="display:none;"><?php echo esc_js( __( 'Save', 'opi-bluesky' ) ); ?></button> ' +
                    '<button type="button" class="button button-small opibluesky-rename-cancel" style="display:none;"><?php echo esc_js( __( 'Cancel', 'opi-bluesky' ) ); ?></button> ' +
                    '<button type="button" class="button button-small opibluesky-delete-cat" style="margin-left:4px;"><?php echo esc_js( __( 'Delete', 'opi-bluesky' ) ); ?></button>' +
                    '</td></tr>';

                if ( $('#opibluesky-category-list').length ) {
                    $('#opibluesky-category-list').append(row);
                } else {
                    location.reload();
                    return;
                }

                // Add option to all slot selects.
                $('select[name*="[category_id]"]').append(
                    $('<option>').val(termId).text(catName)
                );

                $('#opibluesky-new-cat-name').val('');
                $result.css('color', '#2271b1').text('<?php echo esc_js( __( 'Added.', 'opi-bluesky' ) ); ?>');
                setTimeout(function() { $result.text(''); }, 2000);
            } else {
                $result.css('color', '#d63638').text(response.data);
            }
        });
    });

    // ── Category rename ──────────────────────────────────────────────────────

    $(document).on('click', '.opibluesky-rename-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.opibluesky-cat-name').hide();
        $row.find('.opibluesky-cat-rename-input').show().focus();
        $(this).hide();
        $row.find('.opibluesky-rename-save, .opibluesky-rename-cancel').show();
    });

    $(document).on('click', '.opibluesky-rename-cancel', function() {
        var $row = $(this).closest('tr');
        $row.find('.opibluesky-cat-name').show();
        $row.find('.opibluesky-cat-rename-input').hide();
        $row.find('.opibluesky-rename-btn').show();
        $(this).hide();
        $row.find('.opibluesky-rename-save').hide();
    });

    $(document).on('click', '.opibluesky-rename-save', function() {
        var $row   = $(this).closest('tr');
        var termId = $row.data('term-id');
        var name   = $row.find('.opibluesky-cat-rename-input').val().trim();
        if ( ! name ) return;

        $.post(opiBlueskyAdmin.ajaxUrl, {
            action:   'opibluesky_rename_category',
            nonce:    opiBlueskyAdmin.nonce,
            term_id:  termId,
            name:     name,
        }, function(response) {
            if ( response.success ) {
                $row.find('.opibluesky-cat-name').text(name).show();
                $row.find('.opibluesky-cat-rename-input').val(name).hide();
                $row.find('.opibluesky-rename-btn').show();
                $row.find('.opibluesky-rename-save, .opibluesky-rename-cancel').hide();
                $('select[name*="[category_id]"] option[value="' + termId + '"]').text(name);
            } else {
                alert(response.data);
            }
        });
    });

    // ── Category delete ──────────────────────────────────────────────────────

    $(document).on('click', '.opibluesky-delete-cat', function() {
        if ( ! confirm('<?php echo esc_js( __( 'Delete this category? Posts assigned to it will become uncategorized.', 'opi-bluesky' ) ); ?>') ) {
            return;
        }
        var $row   = $(this).closest('tr');
        var termId = $row.data('term-id');

        $.post(opiBlueskyAdmin.ajaxUrl, {
            action:  'opibluesky_delete_category',
            nonce:   opiBlueskyAdmin.nonce,
            term_id: termId,
        }, function(response) {
            if ( response.success ) {
                $row.fadeOut(200, function() { $(this).remove(); });
                $('select[name*="[category_id]"] option[value="' + termId + '"]').remove();
            } else {
                alert(response.data);
            }
        });
    });

    // ── Slot add / remove ────────────────────────────────────────────────────

    var slotIndex = <?php echo count( $slots ); ?>;
    var allDays   = ['mon','tue','wed','thu','fri','sat','sun'];
    var dayLabels = <?php echo wp_json_encode( $days_labels ); ?>;
    var categories = <?php echo wp_json_encode(
        array_map( fn( $c ) => [ 'id' => $c->term_id, 'name' => $c->name ], $categories )
    ); ?>;

    $('#opibluesky-add-slot').on('click', function() {
        var catOptions = categories.map(function(c) {
            return '<option value="' + c.id + '">' + c.name + '</option>';
        }).join('');
        if ( ! catOptions ) catOptions = '<option value=""><?php echo esc_js( __( '— No categories yet —', 'opi-bluesky' ) ); ?></option>';

        var dayCheckboxes = allDays.map(function(d) {
            return '<label style="display:flex;align-items:center;gap:3px;">' +
                '<input type="checkbox" name="opibluesky_slots[' + slotIndex + '][days][]" value="' + d + '" checked> ' +
                dayLabels[d] + '</label>';
        }).join('');

        var html = '<div class="opibluesky-slot-row" style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap;padding:12px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;margin-bottom:10px;">' +
            '<label style="display:flex;flex-direction:column;gap:4px;"><span><?php echo esc_js( __( 'Time', 'opi-bluesky' ) ); ?></span>' +
            '<input type="time" name="opibluesky_slots[' + slotIndex + '][time]" value="08:00" style="width:120px;"></label>' +
            '<label style="display:flex;flex-direction:column;gap:4px;"><span><?php echo esc_js( __( 'Category', 'opi-bluesky' ) ); ?></span>' +
            '<select name="opibluesky_slots[' + slotIndex + '][category_id]">' + catOptions + '</select></label>' +
            '<div style="display:flex;flex-direction:column;gap:4px;"><span><?php echo esc_js( __( 'Days', 'opi-bluesky' ) ); ?></span>' +
            '<div style="display:flex;gap:8px;flex-wrap:wrap;">' + dayCheckboxes + '</div></div>' +
            '<div style="display:flex;align-items:flex-end;">' +
            '<button type="button" class="button opibluesky-remove-slot"><?php echo esc_js( __( 'Remove', 'opi-bluesky' ) ); ?></button></div>' +
            '</div>';

        $('#opibluesky-slots').append(html);
        slotIndex++;
    });

    $(document).on('click', '.opibluesky-remove-slot', function() {
        $(this).closest('.opibluesky-slot-row').remove();
    });

});
</script>