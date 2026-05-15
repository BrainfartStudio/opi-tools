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

        $did   = OPI_Bluesky_Auth::get_did();
        $url   = get_permalink( $post );
        $title = html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $text  = $title . "\n\n" . $url;

        $record = [
            '$type'     => 'app.bsky.feed.post',
            'text'      => $text,
            'createdAt' => gmdate( 'Y-m-d\TH:i:s\Z' ),
            'embed'     => self::build_external_embed( $post, $url ),
        ];

        $facets = self::build_facets( $text );
        if ( ! empty( $facets ) ) {
            $record['facets'] = $facets;
        }

        return self::create_record( $token, $did, $record );
    }

    /**
     * Post arbitrary text to Bluesky.
     * If $embed_url is provided, attaches a link card embed fetched from that URL's OG tags.
     */
    public static function post_text( string $text, ?array $reply_ref = null, ?string $embed_url = null ): array|\WP_Error {
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

        if ( $embed_url ) {
            $local_post = self::get_local_post_for_url( $embed_url );
            if ( $local_post ) {
                $embed = self::build_external_embed( $local_post, $embed_url );
            } else {
                $embed = self::build_url_embed( $embed_url );
            }
            if ( ! is_wp_error( $embed ) ) {
                $record['embed'] = $embed;
            }
        }

        $facets = self::build_facets( $text );
        if ( ! empty( $facets ) ) {
            $record['facets'] = $facets;
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

    /**
     * Extract the first HTTP(S) URL from a string.
     * Returns the URL string or null if none found.
     */
    public static function extract_first_url( string $text ): ?string {
        $pattern = '/https?:\/\/[^\s\]\[\(\)<>"\']+[^\s\]\[\(\)<>"\'\.,;:!?]/u';
        if ( preg_match( $pattern, $text, $matches ) ) {
            return $matches[0];
        }
        return null;
    }

    /**
     * If $url is a permalink for a post on this site, return that WP_Post.
     * Returns null for external URLs or if no post matches.
     */
    private static function get_local_post_for_url( string $url ): ?\WP_Post {
        $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $url_host  = wp_parse_url( $url, PHP_URL_HOST );

        if ( ! $site_host || ! $url_host || $url_host !== $site_host ) {
            return null;
        }

        $post_id = url_to_postid( $url );
        if ( ! $post_id ) {
            return null;
        }

        $post = get_post( $post_id );
        return ( $post instanceof \WP_Post ) ? $post : null;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Scan $text and return Bluesky facets for all URLs and #hashtags.
     *
     * All offsets are UTF-8 byte positions as required by the AT Protocol.
     * PREG_OFFSET_CAPTURE returns byte offsets by default in PHP, and
     * strlen() returns byte length — both correct for Bluesky facets.
     */
    private static function build_facets( string $text ): array {
        $facets = [];

        // ── URLs ──────────────────────────────────────────────────────────────
        // Matches http:// and https:// URLs, stopping at whitespace or common
        // trailing punctuation unlikely to be part of the URL.
        $url_pattern = '/https?:\/\/[^\s\]\[\(\)<>"\']+[^\s\]\[\(\)<>"\'\.,;:!?]/u';

        if ( preg_match_all( $url_pattern, $text, $url_matches, PREG_OFFSET_CAPTURE ) ) {
            foreach ( $url_matches[0] as [ $url, $byte_start ] ) {
                $facets[] = [
                    'index'    => [
                        'byteStart' => $byte_start,
                        'byteEnd'   => $byte_start + strlen( $url ),
                    ],
                    'features' => [
                        [
                            '$type' => 'app.bsky.richtext.facet#link',
                            'uri'   => $url,
                        ],
                    ],
                ];
            }
        }

        // ── Hashtags ──────────────────────────────────────────────────────────
        // Matches #word — letters, numbers, underscores.
        // Negative lookbehind prevents matching inside URLs (e.g. example.com/#anchor).
        $tag_pattern = '/(?<![\/\w])#(\w+)/u';

        if ( preg_match_all( $tag_pattern, $text, $tag_matches, PREG_OFFSET_CAPTURE ) ) {
            foreach ( $tag_matches[0] as $i => [ $full_match, $byte_start ] ) {
                $facets[] = [
                    'index'    => [
                        'byteStart' => $byte_start,
                        'byteEnd'   => $byte_start + strlen( $full_match ),
                    ],
                    'features' => [
                        [
                            '$type' => 'app.bsky.richtext.facet#tag',
                            'tag'   => $tag_matches[1][ $i ][0],
                        ],
                    ],
                ];
            }
        }

        return $facets;
    }

    /**
     * Build an external embed by fetching OG tags from a URL.
     * Returns a WP_Error on fetch failure; falls back gracefully on missing tags.
     *
     * @param string $url The URL to fetch and build an embed for.
     * @return array|\WP_Error
     */
    private static function build_url_embed( string $url ): array|\WP_Error {
        // Skip fetching URLs on the same domain — servers typically block loopback
        // HTTP requests, which would cause the cron process to hang until timeout
        // and prevent the post from being sent at all.
        $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $url_host  = wp_parse_url( $url, PHP_URL_HOST );
        if ( $site_host && $url_host && $url_host === $site_host ) {
            return new \WP_Error( 'bsky_og_loopback', 'Skipping OG fetch for same-domain URL.' );
        }

        $response = wp_remote_get( $url, [
            'timeout'    => 5,
            'user-agent' => 'Mozilla/5.0 (compatible; OPI-Bluesky-Bot/1.0)',
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return new \WP_Error( 'bsky_og_fetch_failed', "URL returned HTTP {$code}." );
        }

        $html  = wp_remote_retrieve_body( $response );
        $title = self::parse_og_tag( $html, 'og:title' )
                 ?: self::parse_html_title( $html )
                 ?: $url;

        $description = self::parse_og_tag( $html, 'og:description' )
                       ?: self::parse_meta_description( $html )
                       ?: '';

        $thumb_url = self::parse_og_tag( $html, 'og:image' );

        $embed = [
            '$type'    => 'app.bsky.embed.external',
            'external' => [
                'uri'         => $url,
                'title'       => $title,
                'description' => $description,
            ],
        ];

        if ( $thumb_url ) {
            $token      = OPI_Bluesky_Auth::get_access_token();
            $thumb_blob = self::upload_blob_from_url( $token, $thumb_url );
            if ( ! is_wp_error( $thumb_blob ) && $thumb_blob ) {
                $embed['external']['thumb'] = $thumb_blob;
            }
        }

        return $embed;
    }

    /**
     * Extract an OG meta property value from HTML.
     */
    private static function parse_og_tag( string $html, string $property ): string {
        if ( preg_match(
            '/<meta[^>]+property=["\']' . preg_quote( $property, '/' ) . '["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i',
            $html,
            $m
        ) ) {
            return html_entity_decode( trim( $m[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        }

        // Also handle reversed attribute order: content first, then property.
        if ( preg_match(
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']' . preg_quote( $property, '/' ) . '["\'][^>]*>/i',
            $html,
            $m
        ) ) {
            return html_entity_decode( trim( $m[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        }

        return '';
    }

    /**
     * Extract <title> from HTML.
     */
    private static function parse_html_title( string $html ): string {
        if ( preg_match( '/<title[^>]*>([^<]+)<\/title>/i', $html, $m ) ) {
            return html_entity_decode( trim( $m[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        }
        return '';
    }

    /**
     * Extract <meta name="description"> from HTML.
     */
    private static function parse_meta_description( string $html ): string {
        if ( preg_match(
            '/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i',
            $html,
            $m
        ) ) {
            return html_entity_decode( trim( $m[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        }

        if ( preg_match(
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\'][^>]*>/i',
            $html,
            $m
        ) ) {
            return html_entity_decode( trim( $m[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        }

        return '';
    }

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

    /**
     * Download an image from a URL and upload it as a Bluesky blob.
     */
    private static function upload_blob_from_url( string $token, string $image_url ): array|\WP_Error {
        $response = wp_remote_get( $image_url, [
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return new \WP_Error( 'bsky_thumb_fetch_failed', "Thumbnail URL returned HTTP {$code}." );
        }

        $data = wp_remote_retrieve_body( $response );
        $mime = wp_remote_retrieve_header( $response, 'content-type' );

        // Strip charset or boundary suffix if present.
        if ( $mime && str_contains( $mime, ';' ) ) {
            $mime = trim( explode( ';', $mime )[0] );
        }

        if ( ! $mime || ! str_starts_with( $mime, 'image/' ) ) {
            $mime = 'image/jpeg';
        }

        $upload_response = wp_remote_post( self::BSKY_API . '/com.atproto.repo.uploadBlob', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => $mime,
            ],
            'body'    => $data,
            'timeout' => 30,
        ] );

        if ( is_wp_error( $upload_response ) ) {
            return $upload_response;
        }

        $upload_code = wp_remote_retrieve_response_code( $upload_response );
        $upload_body = json_decode( wp_remote_retrieve_body( $upload_response ), true );

        if ( $upload_code !== 200 || empty( $upload_body['blob'] ) ) {
            return new \WP_Error( 'bsky_blob_failed', $upload_body['message'] ?? 'Blob upload failed.' );
        }

        return $upload_body['blob'];
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