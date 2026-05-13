<?php
// includes/class-opi-rss-settings.php

defined( 'ABSPATH' ) || exit;

class OPI_RSS_Settings extends OPI_Settings_Base {

    const OPTION_KEY = 'opirss_settings';

    protected static function get_option_key(): string {
        return self::OPTION_KEY;
    }

    public static function get_defaults(): array {
        return [
            'default_item_limit'    => 1,      // int: default items per feed on add
            'auto_deactivate_days'  => 0,      // int: days since last post before auto-deactivate (0 = disabled)
            'cron_inactive'         => false,  // bool: run cron on inactive feeds
            'cron_interval'         => 'opirss_30min', // string: interval key
        ];
    }

    /**
     * Available cron intervals exposed to the settings UI.
     */
    public static function get_interval_options(): array {
        return [
            'opirss_15min' => [ 'seconds' => 900,   'label' => __( 'Every 15 Minutes', 'opi-rss' ) ],
            'opirss_30min' => [ 'seconds' => 1800,  'label' => __( 'Every 30 Minutes', 'opi-rss' ) ],
            'opirss_1hr'   => [ 'seconds' => 3600,  'label' => __( 'Every Hour',        'opi-rss' ) ],
            'opirss_6hr'   => [ 'seconds' => 21600, 'label' => __( 'Every 6 Hours',     'opi-rss' ) ],
            'opirss_12hr'  => [ 'seconds' => 43200, 'label' => __( 'Every 12 Hours',    'opi-rss' ) ],
            'opirss_24hr'  => [ 'seconds' => 86400, 'label' => __( 'Every 24 Hours',    'opi-rss' ) ],
        ];
    }

    public static function sanitize( array $input ): array {
        $clean = static::sanitize_base( $input );

        // Validate interval key against allowed options.
        $valid_intervals = array_keys( self::get_interval_options() );
        if ( ! in_array( $clean['cron_interval'], $valid_intervals, true ) ) {
            $clean['cron_interval'] = 'opirss_30min';
        }

        return $clean;
    }
}