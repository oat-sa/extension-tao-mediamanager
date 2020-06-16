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
    'util/url'
], function($, Loader, qtiClasses, creatorDummyItemData, request, urlUtil) {
    'use strict';

    const qtiNamespace = 'http://www.imsglobal.org/xsd/imsqti_v2p2';

    const qtiSchemaLocation = {
        'http://www.imsglobal.org/xsd/imsqti_v2p2' : 'http://www.imsglobal.org/xsd/qti/qtiv2p2/imsqti_v2p2.xsd'
    };

    const languagesUrl = urlUtil.route('index', 'Languages', 'tao');

    const creatorLoader = {
        loadSharedStimulus(config, callback) {

            if (config.id) {
                const languagesList = request(languagesUrl);
                const assetData = request(config.assetDataUrl, { id : config.id })
                Promise.all([languagesList, assetData]).then((values) => {
                    let loader, itemData;

                    itemData = creatorDummyItemData(values[1]);

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
                            loadedItem.data('languagesList', values[0]);
                        }

                        callback(loadedItem, this.getLoadedClasses());
                    });
                });
            }
        }
    };

    return creatorLoader;
});
