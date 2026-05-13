<?php
// includes/class-opi-rss-db.php

defined( 'ABSPATH' ) || exit;

class OPI_RSS_DB {

    // Status constants
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE   = 1;
    const STATUS_ERROR    = 2;
    const STATUS_PENDING  = 3;

    public static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $feeds_table = $wpdb->prefix . 'opirss_feeds';
        $items_table = $wpdb->prefix . 'opirss_items';

        // status: 0=inactive, 1=active, 2=error, 3=pending
        $sql_feeds = "CREATE TABLE $feeds_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url varchar(512) NOT NULL,
            status tinyint(1) DEFAULT 3,
            item_limit tinyint(3) DEFAULT 1,
            last_fetch datetime DEFAULT NULL,
            next_fetch datetime DEFAULT NULL,
            last_error text DEFAULT NULL,
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

    /**
     * Get feeds, optionally filtered by status.
     * Pass null to get all feeds.
     */
    public static function get_feeds( ?int $status = null ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'opirss_feeds';

        if ( $status !== null ) {
            return $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM $table WHERE status = %d ORDER BY name ASC", $status )
            );
        }

        return $wpdb->get_results( "SELECT * FROM $table ORDER BY name ASC" );
    }

    /**
     * Get feeds with latest article joined, optionally filtered by status.
     *
     * @param int|null $status   Filter by status, or null for all.
     * @param string   $orderby  Column: 'name' | 'last_fetch' | 'latest_article_date'
     * @param string   $order    'ASC' | 'DESC'
     */
    public static function get_feeds_with_latest(
        ?int $status = null,
        string $orderby = 'name',
        string $order = 'ASC'
    ): array {
        global $wpdb;
        $feeds_table = $wpdb->prefix . 'opirss_feeds';
        $items_table = $wpdb->prefix . 'opirss_items';

        $allowed_orderby = [ 'name', 'last_fetch', 'latest_article_date' ];
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'name';
        }
        $order = strtoupper( $order ) === 'DESC' ? 'DESC' : 'ASC';

        $order_clause = $orderby === 'latest_article_date'
            ? "ORDER BY i.pub_date $order, f.name ASC"
            : "ORDER BY f.$orderby $order";

        $where = $status !== null
            ? $wpdb->prepare( 'WHERE f.status = %d', $status )
            : '';

        return $wpdb->get_results( "
            SELECT f.*,
                   i.title    AS latest_article,
                   i.link     AS latest_article_link,
                   i.pub_date AS latest_article_date
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
            $where
            $order_clause
        " );
    }

    /**
     * Get feeds where status is NOT active (inactive, error, pending).
     */
    public static function get_inactive_feeds(): array {
        global $wpdb;
        $feeds_table = $wpdb->prefix . 'opirss_feeds';
        $items_table = $wpdb->prefix . 'opirss_items';

        return $wpdb->get_results( "
            SELECT f.*,
                   i.title    AS latest_article,
                   i.link     AS latest_article_link,
                   i.pub_date AS latest_article_date
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
            WHERE f.status != " . self::STATUS_ACTIVE . "
            ORDER BY f.name ASC
        " );
    }

    /**
     * Get feeds matching one or more status values.
     */
    public static function get_feeds_by_statuses( array $statuses ): array {
        global $wpdb;
        $feeds_table = $wpdb->prefix . 'opirss_feeds';
        $items_table = $wpdb->prefix . 'opirss_items';

        $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%d' ) );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT f.*,
                   i.title    AS latest_article,
                   i.link     AS latest_article_link,
                   i.pub_date AS latest_article_date
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
            WHERE f.status IN ($placeholders)
            ORDER BY f.name ASC",
            ...$statuses
        ) );
    }

    public static function get_feed( int $id ): ?object {
        global $wpdb;
        $table = $wpdb->prefix . 'opirss_feeds';
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
    }

    /**
     * Add a new feed. New feeds start as STATUS_PENDING.
     */
    public static function add_feed( string $name, string $url, int $limit = 1 ): int|false {
        global $wpdb;
        $table  = $wpdb->prefix . 'opirss_feeds';
        $result = $wpdb->insert( $table, [
            'name'       => wp_unslash( sanitize_text_field( $name ) ),
            'url'        => esc_url_raw( $url ),
            'item_limit' => $limit,
            'status'     => self::STATUS_PENDING,
        ] );

        return $result ? $wpdb->insert_id : false;
    }

    public static function update_feed( int $id, string $name, string $url, int $status, int $limit ): int|false {
        global $wpdb;
        $table = $wpdb->prefix . 'opirss_feeds';
        return $wpdb->update( $table, [
            'name'       => wp_unslash( sanitize_text_field( $name ) ),
            'url'        => esc_url_raw( $url ),
            'status'     => $status,
            'item_limit' => $limit,
        ], [ 'id' => $id ] );
    }

    public static function set_status( int $id, int $status ): int|false {
        global $wpdb;
        $table = $wpdb->prefix . 'opirss_feeds';
        return $wpdb->update( $table, [ 'status' => $status ], [ 'id' => $id ] );
    }

    /**
     * Set status=error and store the error message.
     * Pass null to clear the error (on success).
     */
    public static function set_error( int $id, ?string $message ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'opirss_feeds';
        if ( $message !== null ) {
            $wpdb->update( $table, [ 'status' => self::STATUS_ERROR, 'last_error' => $message ], [ 'id' => $id ] );
        } else {
            $wpdb->update( $table, [ 'last_error' => null ], [ 'id' => $id ] );
        }
    }

    public static function delete_feed( int $id ): void {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'opirss_items', [ 'feed_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'opirss_feeds', [ 'id' => $id ] );
    }

    public static function update_fetch_times( int $id ): void {
        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'opirss_feeds', [
            'last_fetch' => current_time( 'mysql' ),
            'next_fetch' => date( 'Y-m-d H:i:s', time() + 1800 ),
        ], [ 'id' => $id ] );
    }

    public static function replace_items( int $feed_id, array $items ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'opirss_items';
        $wpdb->delete( $table, [ 'feed_id' => $feed_id ] );

        foreach ( $items as $item ) {
            $wpdb->insert( $table, [
                'feed_id'  => $feed_id,
                'title'    => $item['title'],
                'link'     => $item['link'],
                'pub_date' => $item['pub_date'],
            ] );
        }
    }

    public static function get_recent_items( int $limit = 20 ): array {
        global $wpdb;
        $items_table = $wpdb->prefix . 'opirss_items';
        $feeds_table = $wpdb->prefix . 'opirss_feeds';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, f.name AS feed_name, f.url AS feed_url
            FROM $items_table i
            JOIN $feeds_table f ON i.feed_id = f.id
            WHERE f.status = %d
            ORDER BY i.pub_date DESC
            LIMIT %d",
            self::STATUS_ACTIVE,
            $limit
        ) );
    }

    public static function get_active_sources(): array {
        global $wpdb;
        $items_table  = $wpdb->prefix . 'opirss_items';
        $feeds_table  = $wpdb->prefix . 'opirss_feeds';
        $one_year_ago = date( 'Y-m-d H:i:s', strtotime( '-1 year' ) );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT f.id, f.name, f.url
            FROM $feeds_table f
            JOIN $items_table i ON f.id = i.feed_id
            WHERE f.status = %d AND i.pub_date > %s
            ORDER BY f.name ASC",
            self::STATUS_ACTIVE,
            $one_year_ago
        ) );
    }

    /**
     * Returns counts used by the dashboard widget.
     * active_count: feeds with status=1
     * error_count:  feeds with status=2
     * stale_count:  active feeds with last_fetch older than 48h
     * today_count:  items ingested today across all active feeds
     */
    public static function get_widget_counts(): object {
        global $wpdb;
        $feeds_table = $wpdb->prefix . 'opirss_feeds';
        $items_table = $wpdb->prefix . 'opirss_items';
        $stale_cutoff = date( 'Y-m-d H:i:s', strtotime( '-48 hours' ) );
        $today_start  = date( 'Y-m-d 00:00:00' );

        $active_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $feeds_table WHERE status = %d", self::STATUS_ACTIVE
        ) );

        $error_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $feeds_table WHERE status = %d", self::STATUS_ERROR
        ) );

        $stale_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $feeds_table WHERE status = %d AND (last_fetch IS NULL OR last_fetch < %s)",
            self::STATUS_ACTIVE,
            $stale_cutoff
        ) );

        $today_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $items_table i
            JOIN $feeds_table f ON i.feed_id = f.id
            WHERE f.status = %d AND i.pub_date >= %s",
            self::STATUS_ACTIVE,
            $today_start
        ) );

        return (object) compact( 'active_count', 'error_count', 'stale_count', 'today_count' );
    }
}