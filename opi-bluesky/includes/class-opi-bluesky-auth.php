<?php
// includes/class-opi-bluesky-auth.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_Auth {

    const TOKEN_OPTION = 'opibluesky_session';

    public static function get_session(): array {
        return get_option( self::TOKEN_OPTION, [] );
    }

    public static function clear_session(): void {
        delete_option( self::TOKEN_OPTION );
    }

    /**
     * Authenticate with Bluesky via app password.
     * Returns true on success, WP_Error on failure.
     */
    public static function authenticate( string $identifier, string $app_password ): true|\WP_Error {
        $response = wp_remote_post( 'https://bsky.social/xrpc/com.atproto.server.createSession', [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'identifier' => $identifier,
                'password'   => $app_password,
            ] ),
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $body['accessJwt'] ) ) {
            $message = $body['message'] ?? 'Authentication failed.';
            return new \WP_Error( 'bsky_auth_failed', $message );
        }

        update_option( self::TOKEN_OPTION, [
            'accessJwt'  => $body['accessJwt'],
            'refreshJwt' => $body['refreshJwt'],
            'did'        => $body['did'],
            'handle'     => $body['handle'],
            'expires_at' => time() + ( 60 * 60 * 2 ), // ~2hr estimate
        ] );

        return true;
    }

    /**
     * Refresh the access token using the stored refresh JWT.
     */
    public static function refresh(): true|\WP_Error {
        $session = self::get_session();

        if ( empty( $session['refreshJwt'] ) ) {
            return new \WP_Error( 'bsky_no_refresh_token', 'No refresh token stored.' );
        }

        $response = wp_remote_post( 'https://bsky.social/xrpc/com.atproto.server.refreshSession', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $session['refreshJwt'],
            ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $body['accessJwt'] ) ) {
            self::clear_session();
            $message = $body['message'] ?? 'Token refresh failed.';
            return new \WP_Error( 'bsky_refresh_failed', $message );
        }

        update_option( self::TOKEN_OPTION, [
            'accessJwt'  => $body['accessJwt'],
            'refreshJwt' => $body['refreshJwt'],
            'did'        => $body['did'],
            'handle'     => $body['handle'],
            'expires_at' => time() + ( 60 * 60 * 2 ),
        ] );

        return true;
    }

    /**
     * Return a valid access token, refreshing if needed.
     * Returns WP_Error if unable to provide a token.
     */
    public static function get_access_token(): string|\WP_Error {
        $session = self::get_session();

        if ( empty( $session['accessJwt'] ) ) {
            // No session — re-authenticate from stored credentials.
            if ( ! OPI_Bluesky_Settings::is_configured() ) {
                return new \WP_Error( 'bsky_not_configured', 'Bluesky credentials not configured.' );
            }

            $result = self::authenticate(
                OPI_Bluesky_Settings::get_identifier(),
                OPI_Bluesky_Settings::get_app_password()
            );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            $session = self::get_session();
        }

        // Refresh if within 5 minutes of expiry.
        if ( ! empty( $session['expires_at'] ) && $session['expires_at'] - time() < 300 ) {
            $result = self::refresh();
            if ( is_wp_error( $result ) ) {
                return $result;
            }
            $session = self::get_session();
        }

        return $session['accessJwt'];
    }

    public static function get_did(): string {
        return self::get_session()['did'] ?? '';
    }

    public static function is_authenticated(): bool {
        $session = self::get_session();
        return ! empty( $session['accessJwt'] );
    }
}