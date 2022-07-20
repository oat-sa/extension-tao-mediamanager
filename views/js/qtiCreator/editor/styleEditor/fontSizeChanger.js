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
 * Copyright (c) 2021-2022 (original work) Open Assessment Technologies SA ;
 *
 */

/**
 *
 * @author dieter <dieter@taotesting.com>
 * @author Hanna Dzmitryieva <hanna@taotesting.com>
 */
define(['jquery', 'lodash', 'taoMediaManager/qtiCreator/editor/styleEditor/styleEditor'], function ($, _, styleEditor) {
    'use strict';

    /**
     * Changes the font size in the Style Editor
     * @param {JQuery} $container
     */
    const fontSizeChanger = function ($container) {
        const $fontSizeChanger = $container.find('#item-editor-font-size-changer');
        let itemSelector = styleEditor.replaceMainClass($fontSizeChanger.data('target'));
        itemSelector = styleEditor.replaceHashClass(itemSelector);
        const figcaptionSelector = `${itemSelector} figure figcaption`;
        const $resetBtn = $fontSizeChanger.parents('.reset-group').find('[data-role="font-size-reset"]');
        const $input = $container.find('.item-editor-font-size-text');
        let itemFontSize = parseInt($(itemSelector).css('font-size'), 10);

        // initiate font-size for Block
        const styles = styleEditor.getStyle() || {};
        if (styles[itemSelector] && styles[itemSelector]['font-size']) {
            itemFontSize = parseInt(styles[itemSelector]['font-size'], 10);
            $input.val(itemFontSize);
        } else {
            $input.val('');
        }
        /**
         * Writes new font size to virtual style sheet
         */
        const resizeFont = function () {
            styleEditor.apply(itemSelector, 'font-size', `${itemFontSize.toString()}px`);
            const figcaptionSize = itemFontSize > 14 ? (itemFontSize -2).toString() : Math.min(itemFontSize, 12).toString()
            styleEditor.apply(figcaptionSelector, 'font-size', `${figcaptionSize}px`);
        };

        /**
         * Handle input field
         */
        $fontSizeChanger
            .find('button')
            .off('click')
            .on('click', function (e) {
                e.preventDefault();
                if ($(this).data('action') === 'reduce') {
                    if (itemFontSize <= 10) {
                        return;
                    }
                    itemFontSize--;
                } else {
                    itemFontSize++;
                }
                resizeFont();
                $input.val(itemFontSize);
                $(this).parent().blur();
            });

        /**
         * Apply font size on blur
         */
        $input.off('blur').on('blur', function () {
            if (this.value) {
                itemFontSize = parseInt(this.value, 10);
                resizeFont();
            } else {
                styleEditor.apply(itemSelector, 'font-size');
                styleEditor.apply(figcaptionSelector, 'font-size');
            }
        });

        /**
         * Apply font size on enter
         * Disallows invalid characters
         */
        $input.off('keydown').on('keydown', function (e) {
            var c = e.keyCode;
            if (c === 13) {
                $input.trigger('blur');
            }
            return _.contains([8, 37, 39, 46], c) || (c >= 48 && c <= 57) || (c >= 96 && c <= 105);
        });

        /**
         * Remove font size from virtual style sheet
         */
        $resetBtn.off('click').on('click', function () {
            styleEditor.apply(itemSelector, 'font-size');
            styleEditor.apply(figcaptionSelector, 'font-size');
            $input.val('');
            itemFontSize = parseInt($(itemSelector).css('font-size'), 10);
        });

        /**
         * style loaded from style sheet
         */
        $(document).on('customcssloaded.styleeditor', function (e, style) {
            if (style[itemSelector] && style[itemSelector]['font-size']) {
                itemFontSize = parseInt(style[itemSelector]['font-size'], 10);
                $input.val(itemFontSize);
            } else {
                $input.val('');
            }
        });
    };

    return fontSizeChanger;
});
