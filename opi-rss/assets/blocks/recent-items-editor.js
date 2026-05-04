(function() {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, RangeControl } = wp.components;
    const { createElement: el } = wp.element;

    registerBlockType('opi-rss/recent-items', {
        title: 'Recent Items',
        icon: 'rss',
        category: 'widgets',
        description: 'Display recent RSS items',
        attributes: {
            limit: {
                type: 'number',
                default: 20
            }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            return el('div', blockProps,
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Settings' },
                        el(RangeControl, {
                            label: 'Number of items',
                            value: attributes.limit,
                            onChange: function(value) {
                                setAttributes({ limit: value });
                            },
                            min: 1,
                            max: 50
                        })
                    )
                ),
                el('div', {
                    style: {
                        padding: '20px',
                        background: '#f0f0f0',
                        border: '1px dashed #999',
                        borderRadius: '4px'
                    }
                },
                    el('p', { style: { margin: '0 0 10px 0', fontWeight: 'bold' } }, 'Recent RSS Items'),
                    el('p', { style: { margin: 0 } }, 'Showing ' + attributes.limit + ' most recent items')
                )
            );
        },
        save: function() {
            return null;
        }
    });
})();