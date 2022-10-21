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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 *
 */

define([
    'jquery',
    'lodash',
    'json!taoQtiItem/qtiCreator/editor/resources/font-stacks.json',
    'taoMediaManager/qtiCreator/editor/styleEditor/styleEditor',
    'i18n',
    'select2'
], function ($, _, fontStacks, styleEditor, __) {
    'use strict';

    /**
     * Populate a select box with a list of fonts to select from.
     * On change apply the selected font to the specified target.
     *
     * @example
     * The expected mark-up must be like this:
     * <select
     *   data-target="selector-of-targeted-element"
     *   data-not-selected="Select a font
     *   data-selected="Reset to default">
     * <option value=""></option>
     *
     * The function is called like this:
     * fontSelector();
     *
     * @param {JQuery} $container
     */
    const fontSelector = function ($container) {
        const selector = 'select#item-editor-font-selector',
            $selector = $container.find(selector);
        let target = styleEditor.replaceHashClass($selector.data('target'));
        let normalize = function (font) {
                return font.replace(/"/g, "'").replace(/, /g, ',');
            },
            clean = function (font) {
                return font.substring(0, font.indexOf(',')).replace(/'/g, '').replace(/"/g, '');
            },
            resetButton = $selector.parent().find('[data-role="font-selector-reset"]'),
            toLabel = function (font) {
                font = font.replace(/-/g, ' ');
                return font.replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function ($1) {
                    return $1.toUpperCase();
                });
            },
            format = function (state) {
                const originalOption = state.element;
                if (!state.id) {
                    return state.text;
                }
                return `<span style="font-size: 12px;${$(originalOption).attr('style')}">${state.text}</span>`;
            },
            reset = function () {
                styleEditor.apply(target, 'font-family');
                $selector.select2('val', '');
            };
        let applyToStylesEditor = true;

        $selector.empty();
        $selector.append(`<option value="">${__('Default')}</option>`);

        // initiate font family for Block
        const styles = styleEditor.getStyle() || {};
        const selectedFontFamily = styles[target] && styles[target]['font-family'] && clean(styles[target]['font-family']);

        _.forEach(fontStacks, (value, key) => {
            const optGroup = $('<optgroup>', { label: toLabel(key) });
            _.forEach(value, font => {
                const normalizeFont = normalize(font);
                const option = $('<option>', {
                    value: normalizeFont,
                    text: clean(normalizeFont)
                }).css({
                    fontFamily: normalizeFont
                });
                if (clean(normalizeFont) === selectedFontFamily) {
                    option.attr('selected', true);
                }
                optGroup.append(option);
            });
            $selector.append(optGroup);
        });

        resetButton.off('click').on('click', reset);

        $selector.select2({
            formatResult: format,
            formatSelection: format,
            width: 'resolve'
        });

        $selector.off('change').on('change', function () {
            if (applyToStylesEditor) {
                styleEditor.apply(target, 'font-family', $(this).val());
            } else {
                applyToStylesEditor = true;
            }
        });

        /**
         * style loaded from style sheet
         */
        $(document).on('customcssloaded.styleeditor', function (e, style) {
            if (style[target] && style[target]['font-family']) {
                $selector.select2('val', style[target]['font-family'].replace(' !important', ''));
                $(`${selector} option:selected`).first().attr('selected', 'selected');
            }
            applyToStylesEditor = false;
            $selector.trigger('change');
        });
    };

    return fontSelector;
});
