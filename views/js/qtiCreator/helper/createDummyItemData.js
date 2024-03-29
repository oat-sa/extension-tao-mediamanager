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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */

define([
    'taoQtiItem/qtiCreator/model/Item'
], function(Item) {
    'use strict';

    const _generateIdentifier = function _generateIdentifier(uri) {
        const pos = uri.lastIndexOf('#');
        return uri.substr(pos + 1);
    };

    const creatorDummyItemData = function(sharedStimulusData) {
        const newItem = new Item().id(_generateIdentifier(sharedStimulusData.id)).attr('title', sharedStimulusData.name);
        newItem.data('new', true);
        newItem.data('dummy', true);

        const itemData = Object.assign({}, newItem);
        delete itemData.bdy;
        delete itemData.rootElement;

        itemData.body = sharedStimulusData.body.body;
        itemData.qtiClass = 'assessmentItem';
        itemData.responseProcessing = {
            attributes: {},
            qtiClass: "responseProcessing",
            responseRules: [],
            serial: `response_${sharedStimulusData.body.serial}`
        };
        itemData.response = {};
        if (sharedStimulusData.body.attributes['xml:lang']) {
            itemData.attributes['xml:lang'] = sharedStimulusData.body.attributes['xml:lang'];
        }
        if (sharedStimulusData.body.attributes.class) {
            itemData.attributes.class = sharedStimulusData.body.attributes.class;
        }

        return itemData;
    };
    return creatorDummyItemData;
});