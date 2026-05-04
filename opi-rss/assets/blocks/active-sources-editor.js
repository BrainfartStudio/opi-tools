(function() {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps } = wp.blockEditor;
    const { createElement: el } = wp.element;

    registerBlockType('opi-rss/active-sources', {
        title: 'Active Sources',
        icon: 'list-view',
        category: 'widgets',
        description: 'Display list of active RSS sources',
        edit: function(props) {
            const blockProps = useBlockProps();

            return el('div', blockProps,
                el('div', {
                    style: {
                        padding: '20px',
                        background: '#f0f0f0',
                        border: '1px dashed #999',
                        borderRadius: '4px'
                    }
                },
                    el('p', { style: { margin: '0 0 10px 0', fontWeight: 'bold' } }, 'Active RSS Sources'),
                    el('p', { style: { margin: 0 } }, 'List of blogs that posted in the last year')
                )
            );
        },
        save: function() {
            return null;
        }
    });
})();