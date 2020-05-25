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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 */
define([
    'jquery',
    'taoQtiItem/qtiItem/core/Loader',
    'taoQtiItem/qtiCreator/model/Item',
    'taoQtiItem/qtiCreator/model/qtiClasses'
], function($, Loader, Item, qtiClasses){
    "use strict";
    var _generateIdentifier = function _generateIdentifier(uri){
        var pos = uri.lastIndexOf('#');
        return uri.substr(pos + 1);
    };

    var qtiNamespace = 'http://www.imsglobal.org/xsd/imsqti_v2p2';

    var qtiSchemaLocation = {
        'http://www.imsglobal.org/xsd/imsqti_v2p2' : 'http://www.imsglobal.org/xsd/qti/qtiv2p2/imsqti_v2p2.xsd'
    };

    var creatorLoader = {
        loadPassage : function loadPassage(config, callback){

            if(config.id){
                $.ajax({
                    url : config.assetDataUrl,
                    dataType : 'json',
                    data : {
                        id : config.id
                    }
                }).done(function(response){

                    var loader, itemData, newItem;
                    response.languagesList = {
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
                    }

                    newItem = new Item().id(_generateIdentifier(config.uri)).attr('title', response.data.name);
                    newItem.data('new', true);
                    newItem.data('dummy', true);

                    itemData = Object.assign({}, newItem);
                    delete itemData.bdy;
                    delete itemData.rootElement;
                    itemData.body = response.data.body.body;
                    itemData.qtiClass = 'assessmentItem';

                    loader = new Loader().setClassesLocation(qtiClasses);
                    loader.loadItemData(itemData, function(loadedItem){
                        var namespaces;

                        //hack to fix #2652
                        if(loadedItem.isEmpty()){
                            loadedItem.body('');
                        }

                        // convert item to current QTI version
                        namespaces = loadedItem.getNamespaces();
                        namespaces[''] = qtiNamespace;
                        loadedItem.setNamespaces(namespaces);
                        loadedItem.setSchemaLocations(qtiSchemaLocation);

                        //add languages list to the item
                        if (response.data.languagesList) {
                            newItem.data('languagesList', data.languagesList);
                        }

                        callback(loadedItem, this.getLoadedClasses());
                    });
                });
            }
        }
    };

    return creatorLoader;
});
