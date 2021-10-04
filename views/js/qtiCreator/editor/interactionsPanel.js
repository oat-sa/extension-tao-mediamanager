/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA ;
 *
 */

/**
 * This module let's you set up the interaction panel
 *
 * @author Hanna Dzmitryieva <hanna@taotesting.com>
 */
define([
    'lodash',
    'i18n',
    'taoQtiItem/qtiCreator/editor/interactionsToolbar',
    'taoQtiItem/qtiCreator/helper/panel'
], function (_, __, interactionsToolbar, panel) {
    'use strict';

    /**
     * Set up the interaction selection panel
     * @param {jQueryElement} $container - the panel container
     */
    return function setUpInteractionPanel($container) {
        const tagTitles = {
            inlineInteractions: __('Inline Interactions')
        };
        const interactions = {
            _container: {
                label: __('Text Block'),
                icon: 'icon-font',
                description: __(
                    'Block contains the content (stimulus) of the item such as text or image. It is also required for Inline Interactions.'
                ),
                short: __('Block'),
                qtiClass: '_container',
                tags: [tagTitles.inlineInteractions, 'text'],
                group:  __('inline-interactions')
            }
        };

        // create toolbar
        interactionsToolbar.create($container, interactions);

        // init accordions
        panel.initSidebarAccordion($container);
        // init special subgroup
        panel.toggleInlineInteractionGroup();
    };
});
