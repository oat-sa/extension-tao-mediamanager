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
    'jquery',
    'taoQtiItem/qtiItem/core/Loader',
    'taoQtiItem/qtiCreator/model/qtiClasses',
    'taoMediaManager/qtiCreator/helper/createDummyItemData',
    'core/dataProvider/request',
    'core/request',
    'util/url'
], function($, Loader, qtiClasses, creatorDummyItemData, request, request2, urlUtil) {
    'use strict';

    const qtiNamespace = 'http://www.imsglobal.org/xsd/imsqti_v2p2';

    const qtiSchemaLocation = {
        'http://www.imsglobal.org/xsd/imsqti_v2p2' : 'http://www.imsglobal.org/xsd/qti/qtiv2p2/imsqti_v2p2.xsd'
    };

    const languagesUrl = urlUtil.route('index', 'Languages', 'tao');

    const creatorLoader = {
        loadSharedStimulus(config, callback) {

            if (config.id) {
                request2({
                    url: languagesUrl,
                    method: 'GET'
                })
                .then(function(languages){
                    request(config.assetDataUrl, { id : config.id })
                        .then(function(data){

                            let loader, itemData;
                            const languagesList = {
                                'da-DK': 'Danish',
                                'de-DE': 'German',
                                'el-GR': 'Greek',
                                'en-GB': 'British English',
                                'en-US': 'English',
                                'es-ES': 'Spanish',
                                'es-MX': 'Mexican Spanish',
                                'fr-CA': 'French Canadian',
                                'fr-FR': 'French',
                                'is-IS': 'Icelandic',
                                'it-IT': 'Italian',
                                'ja-JP': 'Japanese',
                                'lt-LT': 'Lithuanian',
                                'nl-BE': 'Flemish',
                                'nl-NL': 'Dutch',
                                'pt-PT': 'Portuguese',
                                'ru-RU': 'Russian',
                                'sv-SE': 'Swedish',
                                'uk-UA': 'Ukrainian',
                                'zh-CN': 'Simplified Chinese from China',
                                'zh-TW': 'Traditional Chinese from Taiwan'
                            };

                            itemData = creatorDummyItemData(data);

                            loader = new Loader().setClassesLocation(qtiClasses);
                            loader.loadItemData(itemData, function(loadedItem) {
                                let namespaces;

                                // convert item to current QTI version
                                namespaces = loadedItem.getNamespaces();
                                namespaces[''] = qtiNamespace;
                                loadedItem.setNamespaces(namespaces);
                                loadedItem.setSchemaLocations(qtiSchemaLocation);

                                //add languages list to the item
                                if (languagesList) {
                                    loadedItem.data('languagesList', languagesList);
                                }

                                callback(loadedItem, this.getLoadedClasses());
                            });
                        });
                });
            }
        }
    };

    return creatorLoader;
});
