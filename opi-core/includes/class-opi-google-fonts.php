<?php
// includes/class-opi-google-fonts.php

defined( 'ABSPATH' ) || exit;

class OPI_Google_Fonts {

    /**
     * Curated list of ~30 common Google Fonts.
     * Hard-coded to avoid API key requirements and per-load latency.
     */
    public static function get_popular_fonts(): array {
        return [
            'Inter',
            'Roboto',
            'Open Sans',
            'Lato',
            'Montserrat',
            'Oswald',
            'Raleway',
            'Poppins',
            'Merriweather',
            'Playfair Display',
            'Source Sans Pro',
            'Nunito',
            'Ubuntu',
            'PT Sans',
            'PT Serif',
            'Noto Sans',
            'Noto Serif',
            'Fira Sans',
            'Work Sans',
            'Mulish',
            'Quicksand',
            'Josefin Sans',
            'Karla',
            'Cabin',
            'Barlow',
            'DM Sans',
            'Space Grotesk',
            'Libre Baskerville',
            'Crimson Text',
            'Lora',
        ];
    }

    /**
     * Enqueue a Google Fonts <link> for the given font family.
     */
    public static function enqueue( string $font_family ): void {
        if ( empty( $font_family ) || $font_family === 'inherit' ) {
            return;
        }

        $handle = 'opi-gfont-' . sanitize_title( $font_family );

        if ( wp_style_is( $handle, 'enqueued' ) ) {
            return;
        }

        $url = add_query_arg( [
            'family'  => str_replace( ' ', '+', $font_family ) . ':wght@400;500;600;700',
            'display' => 'swap',
        ], 'https://fonts.googleapis.com/css2' );

        wp_enqueue_style( $handle, $url, [], null );
    }

    /**
     * Output a font selector: a <select> of curated fonts plus a freetext <input>.
     */
    public static function render_selector( string $field_name, string $current_value ): void {
        $fonts          = self::get_popular_fonts();
        $is_custom      = ! empty( $current_value )
                          && $current_value !== 'inherit'
                          && ! in_array( $current_value, $fonts, true );
        $select_value   = $is_custom ? '__custom__' : $current_value;
        ?>
        <select name="<?php echo esc_attr( $field_name ); ?>"
                id="<?php echo esc_attr( $field_name ); ?>"
                class="opi-font-select">
            <option value="inherit" <?php selected( $select_value, 'inherit' ); ?>>
                — System Default —
            </option>
            <?php foreach ( $fonts as $font ) : ?>
                <option value="<?php echo esc_attr( $font ); ?>" <?php selected( $select_value, $font ); ?>>
                    <?php echo esc_html( $font ); ?>
                </option>
            <?php endforeach; ?>
            <option value="__custom__" <?php selected( $is_custom, true ); ?>>
                — Custom —
            </option>
        </select>

        <input type="text"
               id="<?php echo esc_attr( $field_name ); ?>_custom"
               class="opi-font-custom regular-text"
               value="<?php echo esc_attr( $is_custom ? $current_value : '' ); ?>"
               placeholder="e.g. 'Inter', sans-serif"
               style="margin-top:6px;<?php echo $is_custom ? '' : 'display:none;'; ?>">

        <p class="description">
            <?php _e( 'Choose a font or enter a custom Google Font name.', 'opi-tools' ); ?>
        </p>

        <script>
        ( function() {
            var sel    = document.getElementById( '<?php echo esc_js( $field_name ); ?>' );
            var custom = document.getElementById( '<?php echo esc_js( $field_name ); ?>_custom' );
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

            // On form submit, write custom value back into the select so it posts correctly.
            sel.closest( 'form' ).addEventListener( 'submit', function() {
                if ( sel.value === '__custom__' && custom.value.trim() ) {
                    var opt = document.createElement( 'option' );
                    opt.value    = custom.value.trim();
                    opt.selected = true;
                    sel.appendChild( opt );
                    sel.value = custom.value.trim();
                }
            } );
        } )();
        </script>
        <?php
    }
}