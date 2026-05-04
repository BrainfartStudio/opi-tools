( function() {
    let logoFrame, bgFrame;

    function mediaFrame( title, onSelect, inputId, previewId ) {
        const frame = wp.media({ title, multiple: false, library: { type: 'image' } });

        frame.on( 'select', function() {
            const attachment = frame.state().get( 'selection' ).first().toJSON();
            document.getElementById( inputId ).value = attachment.id;

            let preview = document.getElementById( previewId );
            if ( ! preview ) {
                preview = document.createElement( 'img' );
                preview.id = previewId;
                preview.style.cssText = 'max-height:80px;display:block;margin-bottom:8px;';
                document.getElementById( inputId ).insertAdjacentElement( 'beforebegin', preview );
            }
            preview.src = attachment.url;
        });

        return frame;
    }

    document.getElementById( 'opilogin-logo-pick' )?.addEventListener( 'click', function(e) {
        e.preventDefault();
        if ( ! logoFrame ) {
            logoFrame = mediaFrame( 'Choose Logo', null, 'logo_id', 'opilogin-logo-preview' );
        }
        logoFrame.open();
    });

    document.getElementById( 'opilogin-logo-remove' )?.addEventListener( 'click', function(e) {
        e.preventDefault();
        document.getElementById( 'logo_id' ).value = 0;
        document.getElementById( 'opilogin-logo-preview' )?.remove();
    });

    document.getElementById( 'opilogin-bg-pick' )?.addEventListener( 'click', function(e) {
        e.preventDefault();
        if ( ! bgFrame ) {
            bgFrame = mediaFrame( 'Choose Background Image', null, 'bg_image_id', 'opilogin-bg-preview' );
        }
        bgFrame.open();
    });

    document.getElementById( 'opilogin-bg-remove' )?.addEventListener( 'click', function(e) {
        e.preventDefault();
        document.getElementById( 'bg_image_id' ).value = 0;
        document.getElementById( 'opilogin-bg-preview' )?.remove();
    });
} )();