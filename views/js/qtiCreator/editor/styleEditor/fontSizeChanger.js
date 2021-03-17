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
 *
 * @author dieter <dieter@taotesting.com>
 * @author Hanna Dzmitryieva <hanna@taotesting.com>
 */
define(['jquery', 'lodash', 'taoMediaManager/qtiCreator/editor/styleEditor/styleEditor'], function ($, _, styleEditor) {
    'use strict';

    /**
     * Changes the font size in the Style Editor
     */
    const fontSizeChanger = function () {
        const $fontSizeChanger = $('#item-editor-font-size-changer'),
            itemSelector = styleEditor.replaceHashClass($fontSizeChanger.data('target')),
            styleSelector = `${itemSelector} *`,
            $item = $(itemSelector),
            $resetBtn = $fontSizeChanger.parents('.reset-group').find('[data-role="font-size-reset"]'),
            $input = $('#item-editor-font-size-text');
        let itemFontSize = parseInt($item.css('font-size'), 10);

        /**
         * Writes new font size to virtual style sheet
         */
        const resizeFont = function () {
            styleEditor.apply(styleSelector, 'font-size', `${itemFontSize.toString()}px`);
        };

        /**
         * Handle input field
         */
        $fontSizeChanger.find('a').on('click', function (e) {
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
         * Disallows invalid characters
         */
        $input.on('keydown', function (e) {
            var c = e.keyCode;
            return _.contains([8, 37, 39, 46], c) || (c >= 48 && c <= 57) || (c >= 96 && c <= 105);
        });

        /**
         * Apply font size on blur
         */
        $input.on('blur', function () {
            if (this.value) {
                itemFontSize = parseInt(this.value, 10);
            } else {
                styleEditor.apply(`${itemSelector} *`, 'font-size');
                itemFontSize = parseInt($item.css('font-size'), 10);
            }
            resizeFont();
        });

        /**
         * Apply font size on enter
         */
        $input.on('keydown', function (e) {
            var c = e.keyCode;
            if (c === 13) {
                $input.trigger('blur');
            }
        });

        /**
         * Remove font size from virtual style sheet
         */
        $resetBtn.on('click', function () {
            $input.val('');
            styleEditor.apply(`${itemSelector} *`, 'font-size');
            itemFontSize = parseInt($item.css('font-size'), 10);
        });

        /**
         * style loaded from style sheet
         */
        $(document).on('customcssloaded.styleeditor', function (e, style) {
            if (style[styleSelector] && style[styleSelector]['font-size']) {
                $input.val(parseInt(style[styleSelector]['font-size'], 10));
                itemFontSize = parseInt(style[styleSelector]['font-size'], 10);
            } else {
                $input.val(parseInt($item.css('font-size'), 10));
            }
        });
    };

    return fontSizeChanger;
});