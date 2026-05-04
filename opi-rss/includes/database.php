<?php
// includes/database.php

defined( 'ABSPATH' ) || exit;

function opirss_create_tables(): void {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $feeds_table = $wpdb->prefix . 'opirss_feeds';
    $items_table = $wpdb->prefix . 'opirss_items';

    $sql_feeds = "CREATE TABLE $feeds_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        url varchar(512) NOT NULL,
        status tinyint(1) DEFAULT 1,
        item_limit tinyint(3) DEFAULT 1,
        last_fetch datetime DEFAULT NULL,
        next_fetch datetime DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $sql_items = "CREATE TABLE $items_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        feed_id mediumint(9) NOT NULL,
        title text NOT NULL,
        link varchar(512) NOT NULL,
        pub_date datetime NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY feed_id (feed_id),
        KEY pub_date (pub_date)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_feeds );
    dbDelta( $sql_items );
}