/**
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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

define([
    'jquery',
    'lodash',
    'taoQtiItem/qtiCommonRenderer/renderers/Item',
    'taoMediaManager/qtiCreator/widgets/item/Widget',
    'tpl!taoQtiItem/qtiCreator/tpl/item',
    'tpl!taoMediaManager/qtiCreator/renderers/tpl/wrap'
], function($, _, CommonRenderer, Widget, tpl, wrapTpl) {
    'use strict';

    const CreatorItem = _.clone(CommonRenderer);

    const _normalizeItemBody = function _normalizeItemBody($itemBody) {

        $itemBody.children().each(function(){
            const $child = $(this);
            //must be a grid-row for editing:
            if (!$child.hasClass('grid-row') && !$child.hasClass('qti-infoControl')) {
                $child.wrap(wrapTpl());
            }
        });

        return $itemBody;
    };

    CreatorItem.template = tpl;

    CreatorItem.render = function render(item, options) {

        const $itemContainer = CommonRenderer.getContainer(item);

        _normalizeItemBody($itemContainer.find('.qti-itemBody'));

        options = options || {};
        options.state = 'active';//the item widget never sleeps ! <- this sounds very scary!
        options.renderer = this;

        return Widget.build(
            item,
            $itemContainer,
            this.getOption('itemOptionForm'),
            options
        );
    };

    return CreatorItem;
});
