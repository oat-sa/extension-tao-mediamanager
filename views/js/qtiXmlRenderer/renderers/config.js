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
 * Copyright (c) 2020 (original work) Open Assessment Technlogies SA;
 *
 */

/**
 * Config of the QTI XML renderer
 *
 * @author Hanna Dzmitryieva <hanna@taotesting.com>
 */
define([
    'taoItems/assets/manager'
], function(assetManagerFactory){
    'use strict';

    //all assets are normalized (relative)
    const assetManager = assetManagerFactory([{
        name : 'nomalize',
        handle: function normalizeStrategy(url){
            if(url){
                return url.toString().replace(/^\.?\//, '');
            }
        }
    }]);

    /**
     * The XML Renderer config
     */
    return {
        name : 'xmlRenderer',
        locations : {
            '_container' : 'taoQtiItem/qtiXmlRenderer/renderers/Container',
            'assessmentItem' : 'taoMediaManager/qtiXmlRenderer/renderers/Item',
            '_tooltip' : 'taoQtiItem/qtiXmlRenderer/renderers/Tooltip',
            'math' : 'taoQtiItem/qtiXmlRenderer/renderers/Math',
            'img' : 'taoQtiItem/qtiXmlRenderer/renderers/Img',
            'object' : 'taoQtiItem/qtiXmlRenderer/renderers/Object',
            'table' : 'taoQtiItem/qtiXmlRenderer/renderers/Table',
        },
        options : {
            assetManager : assetManager
        }
    };
});
