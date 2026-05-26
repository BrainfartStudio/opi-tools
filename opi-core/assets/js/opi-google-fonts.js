( function() {
    'use strict';

    document.querySelectorAll( '.opi-font-select' ).forEach( function( sel ) {
        var fieldName = sel.id;
        var custom    = document.getElementById( fieldName + '_custom' );
        if ( ! sel || ! custom ) return;

        sel.addEventListener( 'change', function() {
            if ( this.value === '__custom__' ) {
                custom.style.display = '';
                custom.focus();
            } else {
                custom.style.display = 'none';
                custom.value = '';
            }
        } );

        sel.closest( 'form' ).addEventListener( 'submit', function() {
            if ( sel.value === '__custom__' && custom.value.trim() ) {
                var opt      = document.createElement( 'option' );
                opt.value    = custom.value.trim();
                opt.selected = true;
                sel.appendChild( opt );
                sel.value = custom.value.trim();
            }
        } );
    } );
} )();