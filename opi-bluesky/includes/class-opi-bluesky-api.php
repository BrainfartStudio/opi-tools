<?php
// includes/class-opi-bluesky-api.php

defined( 'ABSPATH' ) || exit;

class OPI_Bluesky_API {

    const BSKY_API = 'https://bsky.social/xrpc';

    /**
     * Post an article to Bluesky as a link card embed.
     */
    public static function post_article( \WP_Post $post ): array|\WP_Error {
        $token = OPI_Bluesky_Auth::get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $did  = OPI_Bluesky_Auth::get_did();
        $url  = get_permalink( $post );
        $text = get_the_title( $post ) . "\n\n" . $url;

        $record = [
            '$type'     => 'app.bsky.feed.post',
            'text'      => $text,
            'createdAt' => gmdate( 'Y-m-d\TH:i:s\Z' ),
            'embed'     => self::build_external_embed( $post, $url ),
        ];

        $url_start = strlen( get_the_title( $post ) . "\n\n" );
        $url_end   = $url_start + strlen( $url );

        $record['facets'] = [
            [
                'index'    => [
                    'byteStart' => $url_start,
                    'byteEnd'   => $url_end,
                ],
                'features' => [
                    [
                        '$type' => 'app.bsky.richtext.facet#link',
                        'uri'   => $url,
                    ],
                ],
            ],
        ];

        return self::create_record( $token, $did, $record );
    }

    /**
     * Post arbitrary text to Bluesky.
     */
    public static function post_text( string $text, ?array $reply_ref = null ): array|\WP_Error {
        $token = OPI_Bluesky_Auth::get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $did = OPI_Bluesky_Auth::get_did();

        $record = [
            '$type'     => 'app.bsky.feed.post',
            'text'      => $text,
            'createdAt' => gmdate( 'Y-m-d\TH:i:s\Z' ),
        ];

        if ( $reply_ref ) {
            $record['reply'] = $reply_ref;
        }

        return self::create_record( $token, $did, $record );
    }

    /**
     * Repost an existing Bluesky record by URI + CID.
     */
    public static function repost( string $uri, string $cid ): array|\WP_Error {
        $token = OPI_Bluesky_Auth::get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $did = OPI_Bluesky_Auth::get_did();

        $record = [
            '$type'     => 'app.bsky.feed.repost',
            'subject'   => [
                'uri' => $uri,
                'cid' => $cid,
            ],
            'createdAt' => gmdate( 'Y-m-d\TH:i:s\Z' ),
        ];

        return self::create_record( $token, $did, $record, 'app.bsky.feed.repost' );
    }

    /**
     * Quote post — post text with an embedded reference to another post.
     */
    public static function quote_post( string $text, string $uri, string $cid ): array|\WP_Error {
        $token = OPI_Bluesky_Auth::get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $did = OPI_Bluesky_Auth::get_did();

        $record = [
            '$type'     => 'app.bsky.feed.post',
            'text'      => $text,
            'createdAt' => gmdate( 'Y-m-d\TH:i:s\Z' ),
            'embed'     => [
                '$type'  => 'app.bsky.embed.record',
                'record' => [
                    'uri' => $uri,
                    'cid' => $cid,
                ],
            ],
        ];

        return self::create_record( $token, $did, $record );
    }

    /**
     * Resolve a post URI to get its CID.
     * Returns [ 'uri' => ..., 'cid' => ... ] or WP_Error.
     */
    public static function resolve_post( string $uri ): array|\WP_Error {
        $token = OPI_Bluesky_Auth::get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $response = wp_remote_get( self::BSKY_API . '/app.bsky.feed.getPostThread?' . http_build_query( [
            'uri'   => $uri,
            'depth' => 0,
        ] ), [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $message = $body['message'] ?? 'Failed to resolve post.';
            return new \WP_Error( 'bsky_resolve_failed', $message );
        }

        $post = $body['thread']['post'] ?? null;
        if ( ! $post ) {
            return new \WP_Error( 'bsky_resolve_failed', 'Post not found in thread response.' );
        }

        return [
            'uri' => $post['uri'],
            'cid' => $post['cid'],
        ];
    }

    /**
     * Build reply $ref from a parent post URI.
     */
    public static function build_reply_ref( string $parent_uri ): array|\WP_Error {
        $parent = self::resolve_post( $parent_uri );
        if ( is_wp_error( $parent ) ) {
            return $parent;
        }

        return [
            'root'   => $parent,
            'parent' => $parent,
        ];
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private static function build_external_embed( \WP_Post $post, string $url ): array {
        $description = get_the_excerpt( $post );
        if ( ! $description ) {
            $description = wp_trim_words( $post->post_content, 30 );
        }

        $thumb_blob = null;
        $thumb_id   = get_post_thumbnail_id( $post->ID );
        if ( $thumb_id ) {
            $thumb_path = get_attached_file( $thumb_id );
            if ( $thumb_path && file_exists( $thumb_path ) ) {
                $token      = OPI_Bluesky_Auth::get_access_token();
                $thumb_blob = self::upload_blob( $token, $thumb_path );
            }
        }

        $embed = [
            '$type'    => 'app.bsky.embed.external',
            'external' => [
                'uri'         => $url,
                'title'       => get_the_title( $post ),
                'description' => $description,
            ],
        ];

        if ( ! is_wp_error( $thumb_blob ) && $thumb_blob ) {
            $embed['external']['thumb'] = $thumb_blob;
        }

        return $embed;
    }

    private static function upload_blob( string $token, string $file_path ): array|\WP_Error {
        $mime = mime_content_type( $file_path );
        $data = file_get_contents( $file_path );

        if ( $data === false ) {
            return new \WP_Error( 'bsky_blob_read_failed', 'Could not read thumbnail file.' );
        }

        $response = wp_remote_post( self::BSKY_API . '/com.atproto.repo.uploadBlob', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => $mime,
            ],
            'body'    => $data,
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $body['blob'] ) ) {
            return new \WP_Error( 'bsky_blob_failed', $body['message'] ?? 'Blob upload failed.' );
        }

        return $body['blob'];
    }

    private static function create_record( string $token, string $did, array $record, string $collection = 'app.bsky.feed.post' ): array|\WP_Error {
        $response = wp_remote_post( self::BSKY_API . '/com.atproto.repo.createRecord', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( [
                'repo'       => $did,
                'collection' => $collection,
                'record'     => $record,
            ] ),
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $message = $body['message'] ?? 'Failed to create record.';
            return new \WP_Error( 'bsky_post_failed', $message );
        }

        return $body;
    }
}