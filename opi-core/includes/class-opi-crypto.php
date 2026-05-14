<?php
// includes/class-opi-crypto.php

defined( 'ABSPATH' ) || exit;

class OPI_Crypto {

    /**
     * Derive a site-specific encryption key from AUTH_KEY + SECURE_AUTH_KEY.
     */
    private static function get_key(): string {
        return hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true );
    }

    /**
     * Encrypt a string. Returns base64-encoded IV + ciphertext.
     */
    public static function encrypt( string $plaintext ): string {
        if ( empty( $plaintext ) ) {
            return '';
        }

        $iv         = random_bytes( 16 );
        $ciphertext = openssl_encrypt( $plaintext, 'AES-256-CBC', self::get_key(), OPENSSL_RAW_DATA, $iv );

        if ( $ciphertext === false ) {
            return '';
        }

        return base64_encode( $iv . $ciphertext );
    }

    /**
     * Decrypt a base64-encoded IV + ciphertext string.
     */
    public static function decrypt( string $encrypted ): string {
        if ( empty( $encrypted ) ) {
            return '';
        }

        $raw = base64_decode( $encrypted, true );
        if ( $raw === false || strlen( $raw ) < 17 ) {
            return '';
        }

        $iv         = substr( $raw, 0, 16 );
        $ciphertext = substr( $raw, 16 );
        $plaintext  = openssl_decrypt( $ciphertext, 'AES-256-CBC', self::get_key(), OPENSSL_RAW_DATA, $iv );

        return $plaintext !== false ? $plaintext : '';
    }
}