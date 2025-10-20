(function() {
    'use strict';

    const { registerBlockType } = wp.blocks;
    const { RangeControl, SelectControl, ToggleControl, PanelBody } = wp.components;
    const { InspectorControls } = wp.blockEditor;
    const { createElement: el } = wp.element;

    registerBlockType('sct-events/list', {
        title: 'Events List',
        description: 'Display a list of upcoming events on your page',
        icon: 'calendar-alt',
        category: 'widgets',
        
        attributes: {
            numberOfEvents: {
                type: 'number',
                default: 10,
            },
            sortBy: {
                type: 'string',
                default: 'date_asc',
            },
            showDescription: {
                type: 'boolean',
                default: true,
            },
            showLocation: {
                type: 'boolean',
                default: true,
            },
            showDate: {
                type: 'boolean',
                default: true,
            },
            columns: {
                type: 'number',
                default: 1,
            },
        },

        edit({ attributes, setAttributes }) {
            const {
                numberOfEvents,
                sortBy,
                showDescription,
                showLocation,
                showDate,
                columns,
            } = attributes;

            return el(
                'div',
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: 'Events List Settings', initialOpen: true },
                        el(RangeControl, {
                            label: 'Number of Events',
                            value: numberOfEvents,
                            onChange: (value) => setAttributes({ numberOfEvents: value }),
                            min: 1,
                            max: 50,
                        }),
                        el(RangeControl, {
                            label: 'Columns',
                            value: columns,
                            onChange: (value) => setAttributes({ columns: value }),
                            min: 1,
                            max: 4,
                        }),
                        el(SelectControl, {
                            label: 'Sort By',
                            value: sortBy,
                            onChange: (value) => setAttributes({ sortBy: value }),
                            options: [
                                { label: 'Event Date (Oldest First)', value: 'date_asc' },
                                { label: 'Event Date (Newest First)', value: 'date_desc' },
                                { label: 'Event Name (A-Z)', value: 'name_asc' },
                                { label: 'Event Name (Z-A)', value: 'name_desc' },
                            ],
                        })
                    ),
                    el(
                        PanelBody,
                        { title: 'Display Options' },
                        el(ToggleControl, {
                            label: 'Show Event Date & Time',
                            checked: showDate,
                            onChange: (value) => setAttributes({ showDate: value }),
                        }),
                        el(ToggleControl, {
                            label: 'Show Location',
                            checked: showLocation,
                            onChange: (value) => setAttributes({ showLocation: value }),
                        }),
                        el(ToggleControl, {
                            label: 'Show Description',
                            checked: showDescription,
                            onChange: (value) => setAttributes({ showDescription: value }),
                        })
                    )
                ),
                el(
                    'div',
                    { className: 'sct-events-list-block sct-events-columns-' + columns },
                    el(
                        'div',
                        { className: 'sct-events-grid wp-block-latest-posts' },
                        el(
                            'div',
                            { className: 'sct-event-item' },
                            el('div', { className: 'sct-event-thumbnail', style: { background: '#e0e0e0', height: '150px' } }, ''),
                            el(
                                'div',
                                { className: 'sct-event-content' },
                                el('h3', { className: 'sct-event-title' }, 'Event Title'),
                                showDate && el('div', { className: 'sct-event-meta' }, 'Date & Time'),
                                showLocation && el('div', { className: 'sct-event-location' }, 'Location'),
                                showDescription && el('div', { className: 'sct-event-description' }, 'Event description preview...')
                            ),
                            el('div', { className: 'sct-event-footer' }, 
                                el('a', { href: '#', className: 'sct-register-button sct-register-internal', onClick: (e) => e.preventDefault() }, 'Register')
                            )
                        )
                    )
                )
            );
        },

        save() {
            // Rendered via PHP callback
            return null;
        },
    });
})();
