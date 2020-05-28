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
    'taoQtiItem/qtiCreator/model/Item',
    'taoQtiItem/qtiCreator/model/qtiClasses'
], function($, Loader, Item, qtiClasses){


    function generateIdentifier(uri) {
        const pos = uri.lastIndexOf('#');
        return uri.substr(pos + 1);
    };

    const qtiNamespace = 'http://www.imsglobal.org/xsd/imsqti_v2p2';

    const qtiSchemaLocation = {
        'http://www.imsglobal.org/xsd/imsqti_v2p2' : 'http://www.imsglobal.org/xsd/qti/qtiv2p2/imsqti_v2p2.xsd'
    };

    const creatorLoader = {
        loadPassage(config, callback) {

            if (config.id) {
                $.ajax({
                    url : config.assetDataUrl,
                    dataType : 'json',
                    data : {
                        id : config.id
                    }
                }).done(function(response){

                    let loader, itemData, newItem;
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
                    };

                    newItem = new Item().id(_generateIdentifier(config.uri)).attr('title', response.data.name);
                    newItem.data('new', true);
                    newItem.data('dummy', true);

                    itemData = Object.assign({}, newItem);
                    delete itemData.bdy;
                    delete itemData.rootElement;

                    itemData.body = response.data.body.body;
                    if (itemData.body.body.match(/^\n$/)) { // place empty container if body is empty
                        itemData.body.body = '<div class="grid-row"><div class="col-12"><p>Lorem ipsum dolor sit amet, consectetur adipisicing ...</p></div></div>';
                    }
                    itemData.qtiClass = 'assessmentItem';

                    loader = new Loader().setClassesLocation(qtiClasses);
                    loader.loadItemData(itemData, function(loadedItem) {
                        let namespaces;

                        // convert item to current QTI version
                        namespaces = loadedItem.getNamespaces();
                        namespaces[''] = qtiNamespace;
                        loadedItem.setNamespaces(namespaces);
                        loadedItem.setSchemaLocations(qtiSchemaLocation);

                        //add languages list to the item
                        if (response.languagesList) {
                            loadedItem.data('languagesList', response.languagesList);
                        }

                        callback(loadedItem, this.getLoadedClasses());
                    });
                });
            }
        }
    };

    return creatorLoader;
});
