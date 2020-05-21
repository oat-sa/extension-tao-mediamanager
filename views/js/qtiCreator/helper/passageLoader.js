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
                }).done(function(response) {

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

                    newItem.createResponseProcessing();

                    //set default namespaces
                    newItem.setNamespaces({
                        '' : qtiNamespace,
                        'xsi' : 'http://www.w3.org/2001/XMLSchema-instance',
                        'm' :'http://www.w3.org/1998/Math/MathML'
                    });//note : always add math element : since it has become difficult to know when a math element has been added to the item

                    //set default schema location
                    newItem.setSchemaLocations(qtiSchemaLocation);

                    //tag the item as a new one
                    newItem.data('new', true);
                    newItem.data('dummy', true);

                    //add languages list to the item
                    if (response.data.languagesList) {
                        newItem.data('languagesList', data.languagesList);
                    }

                    callback(newItem);
                });
            }
        }
    };

    return creatorLoader;
});
