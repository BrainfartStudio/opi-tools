/**
 * OPI Admin Customizer — Live Preview
 * assets/js/opi-admin-preview.js
 *
 * Applies CSS variable updates in real time as settings are changed.
 * Depends on opi-admin (Core JS) for color pair sync.
 */

/* global document */

( function() {
    'use strict';

    var customCssTimer = null;
    var fontLinkId     = 'opiadmin-preview-font';
    var customCssId    = 'opiadmin-preview-css';

    /**
     * Set a CSS custom property on :root.
     */
    function setCssVar( name, value ) {
        document.documentElement.style.setProperty( name, value );
    }

    /**
     * Inject or update a Google Fonts <link> for the given family.
     */
    function loadGoogleFont( family ) {
        if ( ! family || family === 'inherit' ) {
            var existing = document.getElementById( fontLinkId );
            if ( existing ) {
                existing.parentNode.removeChild( existing );
            }
            return;
        }

        var url  = 'https://fonts.googleapis.com/css2?family='
                 + encodeURIComponent( family ).replace( /%20/g, '+' )
                 + ':wght@400;500;600;700&display=swap';

        var link = document.getElementById( fontLinkId );
        if ( ! link ) {
            link     = document.createElement( 'link' );
            link.id  = fontLinkId;
            link.rel = 'stylesheet';
            document.head.appendChild( link );
        }
        link.href = url;
    }

    /**
     * Inject or update the custom CSS preview <style> block.
     */
    function applyCustomCss( css ) {
        var style = document.getElementById( customCssId );
        if ( ! style ) {
            style    = document.createElement( 'style' );
            style.id = customCssId;
            document.head.appendChild( style );
        }
        style.textContent = css;
    }

    /**
     * Handle numeric and color inputs with data-css-var.
     */
    document.addEventListener( 'input', function( e ) {
        var el      = e.target;
        var cssVar  = el.getAttribute( 'data-css-var' );
        var cssUnit = el.getAttribute( 'data-css-unit' ) || '';

        if ( cssVar ) {
            setCssVar( cssVar, el.value + cssUnit );
        }
    } );

    document.addEventListener( 'change', function( e ) {
        var el      = e.target;
        var cssVar  = el.getAttribute( 'data-css-var' );
        var cssUnit = el.getAttribute( 'data-css-unit' ) || '';

        if ( cssVar ) {
            setCssVar( cssVar, el.value + cssUnit );
        }
    } );

    /**
     * Hex text input sync.
     *
     * When a valid hex is typed into the text half of an .opi-color-pair,
     * update the sibling color input AND fire the CSS var update immediately.
     * Core JS (opi-admin.js) handles color→text sync; this handles text→CSS var.
     */
    document.addEventListener( 'input', function( e ) {
        var el = e.target;

        if ( ! el.closest( '.opi-color-pair' ) ) {
            return;
        }
        if ( el.type !== 'text' ) {
            return;
        }

        var val = el.value.trim();
        if ( ! /^#[0-9a-fA-F]{6}$/.test( val ) ) {
            return;
        }

        // Sync sibling color input value.
        var colorInput = el.closest( '.opi-color-pair' ).querySelector( 'input[type="color"]' );
        if ( colorInput ) {
            colorInput.value = val;

            // Apply CSS var if the color input has one.
            var cssVar = colorInput.getAttribute( 'data-css-var' );
            if ( cssVar ) {
                setCssVar( cssVar, val );
            }
        }
    } );

    /**
     * Handle font family select.
     * The select rendered by OPI_Google_Fonts::render_selector() has class opi-font-select.
     */
    document.addEventListener( 'change', function( e ) {
        var el = e.target;

        if ( ! el.classList.contains( 'opi-font-select' ) ) {
            return;
        }

        var family = el.value === '__custom__' ? '' : el.value;
        if ( family && family !== 'inherit' ) {
            loadGoogleFont( family );
            setCssVar( '--opi-admin-font', "'" + family + "', sans-serif" );
        } else {
            loadGoogleFont( null );
            setCssVar( '--opi-admin-font', 'inherit' );
        }
    } );

    /**
     * Handle custom font text input.
     */
    document.addEventListener( 'input', function( e ) {
        var el = e.target;

        if ( ! el.classList.contains( 'opi-font-custom' ) ) {
            return;
        }

        var family = el.value.trim();
        if ( family ) {
            loadGoogleFont( family );
            setCssVar( '--opi-admin-font', "'" + family + "', sans-serif" );
        }
    } );

    /**
     * Handle custom CSS textarea — debounced.
     */
    var customCssEl = document.getElementById( 'opiadmin-custom-css' );
    if ( customCssEl ) {
        customCssEl.addEventListener( 'input', function() {
            clearTimeout( customCssTimer );
            customCssTimer = setTimeout( function() {
                applyCustomCss( customCssEl.value );
            }, 300 );
        } );
    }

} )();