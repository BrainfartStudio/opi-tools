<?php
// includes/cron.php

defined( 'ABSPATH' ) || exit;

add_action( 'opirss_fetch_feeds', 'opirss_do_fetch' );

function opirss_do_fetch(): void {
    $feeds = opirss_get_feeds();

    foreach ( $feeds as $feed ) {
        if ( $feed->status != 1 ) continue;
        opirss_fetch_feed( $feed );
    }
}

function opirss_fetch_feed( object $feed ): bool {
    global $wpdb;
    $items_table = $wpdb->prefix . 'opirss_items';
    $feeds_table = $wpdb->prefix . 'opirss_feeds';

    include_once ABSPATH . WPINC . '/feed.php';

    $rss = fetch_feed( $feed->url );

    if ( is_wp_error( $rss ) ) {
        return false;
    }

    $maxitems  = $rss->get_item_quantity( $feed->item_limit );
    $rss_items = $rss->get_items( 0, $maxitems );

    $wpdb->delete( $items_table, [ 'feed_id' => $feed->id ] );

    foreach ( $rss_items as $item ) {
        $wpdb->insert( $items_table, [
            'feed_id'  => $feed->id,
            'title'    => $item->get_title(),
            'link'     => $item->get_permalink(),
            'pub_date' => date( 'Y-m-d H:i:s', strtotime( $item->get_date() ) ),
        ] );
    }

    $wpdb->update( $feeds_table, [
        'last_fetch' => current_time( 'mysql' ),
        'next_fetch' => date( 'Y-m-d H:i:s', time() + 1800 ),
    ], [ 'id' => $feed->id ] );

    return true;
}