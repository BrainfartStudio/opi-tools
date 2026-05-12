<?php
// includes/class-opi-rss-cron.php

defined( 'ABSPATH' ) || exit;

class OPI_RSS_Cron {

    const HOOK         = 'opirss_fetch_feeds';
    const INTERVAL_KEY = 'opirss_30min';

    public static function init(): void {
        OPI_Cron_Helper::register_interval( self::INTERVAL_KEY, 1800, __( 'Every 30 Minutes', 'opi-rss' ) );
        OPI_Cron_Helper::schedule( self::HOOK, self::INTERVAL_KEY );
        add_action( self::HOOK, [ __CLASS__, 'do_fetch' ] );
    }

    public static function deactivate(): void {
        OPI_Cron_Helper::unschedule( self::HOOK );
    }

    public static function do_fetch(): void {
        $feeds = OPI_RSS_DB::get_feeds( OPI_RSS_DB::STATUS_ACTIVE );

        foreach ( $feeds as $feed ) {
            self::fetch_feed( $feed );
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