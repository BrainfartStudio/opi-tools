<?php
// includes/class-opi-bluesky-api.php
// Stub — full implementation in next commit.

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_API {

    public static function post_article( \WP_Post $post ): array|\WP_Error {
        return new \WP_Error( 'not_implemented', 'API not yet implemented.' );
    }
}