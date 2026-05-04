<?php
// includes/functions.php

defined( 'ABSPATH' ) || exit;

function opirss_get_feeds(): array {
    global $wpdb;
    $table = $wpdb->prefix . 'opirss_feeds';
    return $wpdb->get_results( "SELECT * FROM $table ORDER BY name ASC" );
}

function opirss_get_feeds_with_latest(): array {
    global $wpdb;
    $feeds_table = $wpdb->prefix . 'opirss_feeds';
    $items_table = $wpdb->prefix . 'opirss_items';

    return $wpdb->get_results( "
        SELECT f.*,
               i.title as latest_article,
               i.link as latest_article_link,
               i.pub_date as latest_article_date
        FROM $feeds_table f
        LEFT JOIN (
            SELECT feed_id, title, link, pub_date
            FROM $items_table i1
            WHERE pub_date = (
                SELECT MAX(pub_date)
                FROM $items_table i2
                WHERE i2.feed_id = i1.feed_id
            )
            GROUP BY feed_id
        ) i ON f.id = i.feed_id
        ORDER BY f.name ASC
    " );
}

function opirss_get_feed( int $id ): ?object {
    global $wpdb;
    $table = $wpdb->prefix . 'opirss_feeds';
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
}

function opirss_add_feed( string $name, string $url, int $limit = 1 ): int|false {
    global $wpdb;
    $table  = $wpdb->prefix . 'opirss_feeds';
    $result = $wpdb->insert( $table, [
        'name'       => wp_unslash( sanitize_text_field( $name ) ),
        'url'        => esc_url_raw( $url ),
        'item_limit' => $limit,
        'status'     => 1,
    ] );

    return $result ? $wpdb->insert_id : false;
}

function opirss_update_feed( int $id, string $name, string $url, int $status, int $limit ): int|false {
    global $wpdb;
    $table = $wpdb->prefix . 'opirss_feeds';
    return $wpdb->update( $table, [
        'name'       => wp_unslash( sanitize_text_field( $name ) ),
        'url'        => esc_url_raw( $url ),
        'status'     => $status,
        'item_limit' => $limit,
    ], [ 'id' => $id ] );
}

function opirss_toggle_status( int $id, int $new_status ): int|false {
    global $wpdb;
    $table = $wpdb->prefix . 'opirss_feeds';
    return $wpdb->update( $table, [ 'status' => $new_status ], [ 'id' => $id ] );
}

function opirss_delete_feed( int $id ): void {
    global $wpdb;
    $feeds_table = $wpdb->prefix . 'opirss_feeds';
    $items_table = $wpdb->prefix . 'opirss_items';
    $wpdb->delete( $items_table, [ 'feed_id' => $id ] );
    $wpdb->delete( $feeds_table, [ 'id' => $id ] );
}

function opirss_get_recent_items( int $limit = 20 ): array {
    global $wpdb;
    $items_table = $wpdb->prefix . 'opirss_items';
    $feeds_table = $wpdb->prefix . 'opirss_feeds';

    return $wpdb->get_results( $wpdb->prepare(
        "SELECT i.*, f.name as feed_name, f.url as feed_url
        FROM $items_table i
        JOIN $feeds_table f ON i.feed_id = f.id
        WHERE f.status = 1
        ORDER BY i.pub_date DESC
        LIMIT %d",
        $limit
    ) );
}

function opirss_get_active_sources(): array {
    global $wpdb;
    $items_table  = $wpdb->prefix . 'opirss_items';
    $feeds_table  = $wpdb->prefix . 'opirss_feeds';
    $one_year_ago = date( 'Y-m-d H:i:s', strtotime( '-1 year' ) );

    return $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT f.id, f.name, f.url
        FROM $feeds_table f
        JOIN $items_table i ON f.id = i.feed_id
        WHERE f.status = 1 AND i.pub_date > %s
        ORDER BY f.name ASC",
        $one_year_ago
    ) );
}

function opirss_time_diff( string $datetime ): string {
    $now  = current_time( 'timestamp' );
    $time = strtotime( $datetime );
    $diff = $now - $time;

    $minute = 60;
    $hour   = 3600;
    $day    = 86400;

    if ( $diff < $minute ) {
        return 'just now';
    } elseif ( $diff < $hour ) {
        $mins = floor( $diff / $minute );
        return $mins . ' minute' . ( $mins > 1 ? 's' : '' ) . ' ago';
    } elseif ( $diff < $day ) {
        $hours = floor( $diff / $hour );
        return $hours . ' hour' . ( $hours > 1 ? 's' : '' ) . ' ago';
    } else {
        $days = floor( $diff / $day );
        return $days . ' day' . ( $days > 1 ? 's' : '' ) . ' ago';
    }
}