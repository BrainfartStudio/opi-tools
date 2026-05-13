<?php
// includes/class-opi-rss-cron.php

defined( 'ABSPATH' ) || exit;

class OPI_RSS_Cron {

    const HOOK = 'opirss_fetch_feeds';

    public static function init(): void {
        // Register all possible intervals so WP knows about them.
        foreach ( OPI_RSS_Settings::get_interval_options() as $key => $opt ) {
            OPI_Cron_Helper::register_interval( $key, $opt['seconds'], $opt['label'] );
        }

        $interval_key = OPI_RSS_Settings::get()['cron_interval'];
        OPI_Cron_Helper::schedule( self::HOOK, $interval_key );
        add_action( self::HOOK, [ __CLASS__, 'do_fetch' ] );
    }

    /**
     * If the interval setting changed, reschedule the cron with the new interval.
     * Call this after saving settings.
     */
    public static function reschedule(): void {
        OPI_Cron_Helper::unschedule( self::HOOK );
        $interval_key = OPI_RSS_Settings::get()['cron_interval'];
        OPI_Cron_Helper::schedule( self::HOOK, $interval_key );
    }

    public static function deactivate(): void {
        OPI_Cron_Helper::unschedule( self::HOOK );
    }

    public static function do_fetch(): void {
        $settings = OPI_RSS_Settings::get();

        $feeds = OPI_RSS_DB::get_feeds( OPI_RSS_DB::STATUS_ACTIVE );

        // Optionally include inactive feeds.
        if ( $settings['cron_inactive'] ) {
            $inactive = OPI_RSS_DB::get_feeds( OPI_RSS_DB::STATUS_INACTIVE );
            $feeds    = array_merge( $feeds, $inactive );
        }

        // Always include error feeds so they get a retry.
        $error_feeds = OPI_RSS_DB::get_feeds( OPI_RSS_DB::STATUS_ERROR );
        $feeds       = array_merge( $feeds, $error_feeds );

        foreach ( $feeds as $feed ) {
            self::fetch_feed( $feed );
        }

        // Auto-deactivate stale feeds if configured.
        if ( $settings['auto_deactivate_days'] > 0 ) {
            self::auto_deactivate_stale( $settings['auto_deactivate_days'] );
        }
    }

    /**
     * Deactivate active feeds whose most recent item is older than $days days.
     */
    private static function auto_deactivate_stale( int $days ): void {
        global $wpdb;
        $feeds_table = $wpdb->prefix . 'opirss_feeds';
        $items_table = $wpdb->prefix . 'opirss_items';
        $cutoff      = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $stale_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT f.id
            FROM $feeds_table f
            LEFT JOIN (
                SELECT feed_id, MAX(pub_date) AS latest
                FROM $items_table
                GROUP BY feed_id
            ) i ON f.id = i.feed_id
            WHERE f.status = %d
              AND ( i.latest IS NULL OR i.latest < %s )",
            OPI_RSS_DB::STATUS_ACTIVE,
            $cutoff
        ) );

        foreach ( $stale_ids as $id ) {
            OPI_RSS_DB::set_status( (int) $id, OPI_RSS_DB::STATUS_INACTIVE );
        }
    }

    /**
     * Fetch a single feed.
     * Sets status=active on success, status=error on failure.
     * Returns true on success, false on failure.
     */
    public static function fetch_feed( object $feed ): bool {
        include_once ABSPATH . WPINC . '/feed.php';

        $rss = fetch_feed( $feed->url );

        if ( is_wp_error( $rss ) ) {
            OPI_RSS_DB::set_status( $feed->id, OPI_RSS_DB::STATUS_ERROR );
            return false;
        }

        $maxitems  = $rss->get_item_quantity( $feed->item_limit );
        $rss_items = $rss->get_items( 0, $maxitems );

        $items = [];
        foreach ( $rss_items as $item ) {
            $items[] = [
                'title'    => $item->get_title(),
                'link'     => $item->get_permalink(),
                'pub_date' => date( 'Y-m-d H:i:s', strtotime( $item->get_date() ) ),
            ];
        }

        OPI_RSS_DB::replace_items( $feed->id, $items );
        OPI_RSS_DB::update_fetch_times( $feed->id );
        OPI_RSS_DB::set_status( $feed->id, OPI_RSS_DB::STATUS_ACTIVE );

        return true;
    }
}