/**
 * OPI Buffer Queue — Admin JS
 * assets/js/opi-buffer-admin.js
 *
 * Depends on: jquery, jquery-ui-sortable, opi-admin (OPI.ajax)
 */

/* global OPI */

( function( $ ) {
    'use strict';

    var $tbody = $( '.wp-list-table tbody' );

    // -------------------------------------------------------------------------
    // Drag-and-drop reorder
    // -------------------------------------------------------------------------

    if ( $tbody.length ) {
        $tbody.sortable( {
            handle:      '.sbq-handle',
            placeholder: 'ui-sortable-placeholder',
            axis:        'y',
            cursor:      'move',

            update: function() {
                var order = [];

                $tbody.find( 'tr' ).each( function() {
                    var id = $( this ).find( 'input[type="checkbox"]' ).val();
                    if ( id ) {
                        order.push( parseInt( id, 10 ) );
                    }
                } );

                syncQueueNumbers();

                OPI.ajax( 'opi_buffer_reorder', { order: order }, function( response ) {
                    if ( response.success ) {
                        location.reload();
                    } else {
                        OPI.notice( 'error', response.data || 'Error reordering buffer.' );
                    }
                } );
            },
        } );
    }

    function syncQueueNumbers() {
        $tbody.find( 'tr' ).each( function( index ) {
            $( this ).find( '.opi-buffer-queue-input' ).val( index );
        } );
    }

    // -------------------------------------------------------------------------
    // Manual queue position input
    // -------------------------------------------------------------------------

    $( document ).on( 'change', '.opi-buffer-queue-input', function() {
        var postId      = $( this ).data( 'post-id' );
        var newPosition = parseInt( $( this ).val(), 10 );

        if ( isNaN( newPosition ) || newPosition < 0 ) {
            OPI.notice( 'error', 'Please enter a valid queue position.' );
            return;
        }

        OPI.ajax( 'opi_buffer_set_position', { post_id: postId, position: newPosition }, function( response ) {
            if ( response.success ) {
                location.reload();
            } else {
                OPI.notice( 'error', response.data || 'Error updating position.' );
            }
        } );
    } );

    // -------------------------------------------------------------------------
    // Remove from buffer
    // -------------------------------------------------------------------------

    $( document ).on( 'click', '.opi-buffer-remove', function() {
        if ( ! confirm( 'Remove this post from the buffer? It will revert to Draft.' ) ) {
            return;
        }

        var $btn   = $( this );
        var postId = $btn.data( 'post-id' );

        $btn.prop( 'disabled', true ).text( 'Removing…' );

        OPI.ajax( 'opi_buffer_remove', { post_id: postId }, function( response ) {
            if ( response.success ) {
                location.reload();
            } else {
                OPI.notice( 'error', response.data || 'Error removing post.' );
                $btn.prop( 'disabled', false ).text( 'Remove from Buffer' );
            }
        } );
    } );

    // -------------------------------------------------------------------------
    // Post editor — "Move to Buffer" button
    // Handles the case where the button is rendered by OPI_Buffer_Post_Status
    // but the status select needs syncing before the page submits.
    // -------------------------------------------------------------------------

    $( document ).on( 'click', '#opi-buffer-move-btn', function() {
        var $select = $( '#post_status' );
        var $display = $( '#post-status-display' );

        if ( $select.find( 'option[value="buffer"]' ).length === 0 ) {
            $select.append( $( '<option>', { value: 'buffer', text: 'Buffer' } ) );
        }

        $select.val( 'buffer' );
        $display.text( 'Buffer' );

        // Let the inline PHP handler in post-status class take it from here —
        // this JS block is a belt-and-suspenders fallback only.
    } );

} )( jQuery );