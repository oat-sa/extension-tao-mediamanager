
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

/**
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */
define([
    'jquery',
    'i18n',
    'uri',
    'taoMediaManager/qtiCreator/component/passageAuthoring'
], function($, __, uri, passageAuthoringFactory) {
    'use strict';

    var manageMediaController =  {

        /**
         * Controller entry point
         */
        start : function(){
            const $panel = $('#panel-authoring');
            passageAuthoringFactory($panel, { properties: {
                uri: $panel.attr('data-uri'),
                assetDataUrl: `taoMediaManager/SharedStimulus/get?id=${$panel.attr('data-id')}`,
                // TO DO will be filled later
                baseUrl: '...'
            }});
        }
    };

    return manageMediaController;
});