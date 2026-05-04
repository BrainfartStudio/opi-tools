<?php
// includes/views/settings-page.php

defined( 'ABSPATH' ) || exit;

$settings = OPI_Discord::get_settings();
$message  = '';

// Handle test send
if ( isset( $_POST['opi_discord_test'] ) && check_admin_referer( 'opi_discord_action' ) ) {
    $posts = get_posts( [ 'numberposts' => 1, 'post_status' => 'publish' ] );
    if ( ! empty( $posts ) ) {
        $result  = OPI_Discord::send( $posts[0] );
        $message = $result
            ? '<div class="notice notice-success"><p>Sent successfully.</p></div>'
            : '<div class="notice notice-error"><p>Send failed. Check your webhook URL.</p></div>';
    } else {
        $message = '<div class="notice notice-warning"><p>No published posts found.</p></div>';
    }
}

// Handle bulk send queue
if ( isset( $_POST['opi_discord_bulk'] ) && check_admin_referer( 'opi_discord_action' ) ) {
    $queued = OPI_Discord::queue_all_posts();
    $message = $queued > 0
        ? '<div class="notice notice-success"><p>Queued ' . $queued . ' posts to send. They will post every 2 seconds.</p></div>'
        : '<div class="notice notice-warning"><p>No published posts found to queue.</p></div>';
}

// Handle settings save
if ( isset( $_POST['opi_discord_save'] ) && check_admin_referer( 'opi_discord_action' ) ) {
    $saved = [
        'webhook_url'  => sanitize_text_field( $_POST['webhook_url'] ?? '' ),
        'show_excerpt' => isset( $_POST['show_excerpt'] ),
    ];
    update_option( OPI_Discord::OPTION_KEY, $saved );
    $settings = OPI_Discord::get_settings();
    $message  = '<div class="notice notice-success"><p>Settings saved.</p></div>';
}
?>

<div class="wrap">
    <h1>OPI Discord</h1>

    <?php echo $message; ?>

    <form method="post">
        <?php wp_nonce_field( 'opi_discord_action' ); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="webhook_url">Webhook URL</label></th>
                <td>
                    <input type="text" id="webhook_url" name="webhook_url"
                           class="regular-text"
                           value="<?php echo esc_attr( $settings['webhook_url'] ); ?>">
                    <p class="description">Paste your Discord channel webhook URL here.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Show Excerpt</th>
                <td>
                    <label>
                        <input type="checkbox" name="show_excerpt" value="1"
                            <?php checked( $settings['show_excerpt'] ); ?>>
                        Include post excerpt in Discord embed
                    </label>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="opi_discord_save" class="button button-primary">Save Settings</button>
        </p>

        <hr>

        <h2>Manual Actions</h2>
        <p>
            <button type="submit" name="opi_discord_test" class="button"
                onclick="return confirm('Send the latest published post to Discord?')">
                Send Latest Post
            </button>
            &nbsp;
            <button type="submit" name="opi_discord_bulk" class="button"
                onclick="return confirm('This will queue ALL published posts to send to Discord, oldest first. Continue?')">
                Send All Posts
            </button>
        </p>
        <p class="description">
            <strong>Send All Posts</strong> queues every published post and sends them oldest-first with a 2-second delay between each to avoid Discord rate limits.
        </p>

    </form>
</div>