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
 * Copyright (c) 2020-2021 (original work) Open Assessment Technologies SA;
 *
 */

define([
    'jquery',
    'taoQtiItem/qtiCreator/helper/panel',
    'taoMediaManager/qtiCreator/editor/styleEditor/styleEditor',
    'taoMediaManager/qtiCreator/editor/styleEditor/styleSheetToggler',
    'taoMediaManager/qtiCreator/editor/styleEditor/fontSelector',
    'taoMediaManager/qtiCreator/editor/styleEditor/colorSelector',
    'taoMediaManager/qtiCreator/editor/styleEditor/fontSizeChanger'
], function ($, panel, styleEditor, styleSheetToggler, fontSelector, colorSelector, fontSizeChanger) {
    'use strict';

    /**
     * Set up the properties panel, including the style editor
     * @param {jQueryElement} $container - the panel container
     * @param {Object} widget - item Widget
     * @param {Object} config - sharedStimulusCreator config
     */
    return function setUpInteractionPanel($container, widget, config) {
        panel.initSidebarAccordion($container);
        panel.initFormVisibilityListener();

        const elementClass = { elementClass: styleEditor.getMainClass() };
        config = Object.assign(config, elementClass);
        styleEditor.init(widget.element, config);
        styleSheetToggler.init(config);

        const $passageEditor = $('#item-editor-item-property-bar');
        fontSelector($passageEditor);
        colorSelector($passageEditor);
        fontSizeChanger($passageEditor);
    };
});
