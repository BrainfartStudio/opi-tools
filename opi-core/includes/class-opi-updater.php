<?php
// includes/class-opi-updater.php

defined( 'ABSPATH' ) || exit;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

class OPI_Updater {

    /**
     * GitHub repo in format 'username/repo-name'
     */
    const GITHUB_REPO = 'YOUR_USERNAME/opi-tools';

    /**
     * Personal access token for private repo access.
     * Store this in wp-config.php as: define( 'OPI_GITHUB_TOKEN', 'your_token_here' );
     */
    const TOKEN_CONSTANT = 'OPI_GITHUB_TOKEN';

    public static function init(): void {
        self::register( 'opi-core', OPITOOLS_PATH . 'functions.php', 'opi-core' );
    }

    /**
     * Register an update checker for a plugin.
     *
     * @param string $tag_prefix  Tag prefix on GitHub, e.g. 'opi-core' matches 'opi-core-1.0.0'
     * @param string $plugin_file Absolute path to the plugin's main PHP file
     * @param string $slug        Plugin slug
     */
    public static function register( string $tag_prefix, string $plugin_file, string $slug ): void {
        $checker = PucFactory::buildUpdateChecker(
            'https://github.com/' . self::GITHUB_REPO,
            $plugin_file,
            $slug
        );

        $checker->setBranch( 'main' );

        $checker->setAuthentication(
            defined( self::TOKEN_CONSTANT ) ? constant( self::TOKEN_CONSTANT ) : ''
        );

        // Match only tags for this specific plugin, e.g. opi-core-1.0.0
        $checker->getVcsApi()->enableReleaseAssets(
            '/' . preg_quote( $tag_prefix, '/' ) . '-[\d.]+\.zip/'
        );
    }
}