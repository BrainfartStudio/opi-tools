/**
 * OPI Tools — Shared Admin JS
 * assets/js/opi-admin.js
 *
 * Provides OPI.ajax(), OPI.media(), OPI.notice().
 * Localized via wp_localize_script as `opiAdmin` with ajaxUrl and nonce.
 */

/* global opiAdmin, wp */

window.OPI = window.OPI || {};

( function( $, OPI ) {
    'use strict';

    /**
     * OPI.ajax( action, data, cb )
     *
     * Wrapper around jQuery.post with automatic nonce injection.
     *
     * @param {string}   action  WordPress AJAX action name.
     * @param {object}   data    Additional POST data.
     * @param {function} cb      Callback( response ) — response.success is bool.
     * @returns {jqXHR}
     */
    OPI.ajax = function( action, data, cb ) {
        var payload = $.extend( {}, data, {
            action: action,
            nonce:  opiAdmin.nonce,
        } );

        return $.post( opiAdmin.ajaxUrl, payload, cb ).fail( function() {
            if ( typeof cb === 'function' ) {
                cb( { success: false, data: 'Server error.' } );
            }
        } );
    };

    /**
     * OPI.media( title, inputId, previewId )
     *
     * Opens the WordPress media picker and writes the selected attachment
     * URL into the input and (optionally) updates an <img> preview.
     *
     * @param {string} title     Modal title.
     * @param {string} inputId   ID of the hidden/text input to populate with attachment ID.
     * @param {string} previewId ID of an <img> element to update (optional).
     */
    OPI.media = function( title, inputId, previewId ) {
        var frame = wp.media( {
            title:    title,
            button:   { text: 'Select' },
            multiple: false,
            library:  { type: 'image' },
        } );

        frame.on( 'select', function() {
            var attachment = frame.state().get( 'selection' ).first().toJSON();
            $( '#' + inputId ).val( attachment.id );
            if ( previewId ) {
                var url = attachment.sizes && attachment.sizes.thumbnail
                    ? attachment.sizes.thumbnail.url
                    : attachment.url;
                $( '#' + previewId ).attr( 'src', url ).show();
            }
        } );

        frame.open();
    };

    /**
     * OPI.notice( type, message )
     *
     * Injects a dismissible WP-style admin notice into the nearest .wrap.
     *
     * @param {string} type    'success' | 'error' | 'warning' | 'info'
     * @param {string} message Notice text (HTML allowed).
     */
    OPI.notice = function( type, message ) {
        var html = $( '<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss">' +
            '<span class="screen-reader-text">Dismiss this notice.</span>' +
            '</button></div>' );

        $( '.wrap' ).first().prepend( html );

        html.find( '.notice-dismiss' ).on( 'click', function() {
            html.fadeOut( 200, function() { html.remove(); } );
        } );
    };

    /**
     * Color pair sync.
     *
     * For any .opi-color-pair, keep the type="color" and type="text"
     * inputs in sync automatically.
     */
    $( document ).on( 'input change', '.opi-color-pair input[type="color"]', function() {
        $( this ).siblings( 'input[type="text"]' ).val( $( this ).val() );
    } );

    $( document ).on( 'input', '.opi-color-pair input[type="text"]', function() {
        var val = $( this ).val();
        if ( /^#[0-9a-fA-F]{6}$/.test( val ) ) {
            $( this ).siblings( 'input[type="color"]' ).val( val );
        }
    } );

} )( jQuery, window.OPI );