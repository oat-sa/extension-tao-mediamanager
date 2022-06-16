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
 * Test runner proxy for the QTI item previewer
 *
 * @author Hanna Dzmitryieva <hanna@taotesting.com>
 */
define([
    'jquery',
    'lodash',
    'i18n',
    'core/promiseQueue',
    'core/dataProvider/request',
    'util/url',
    'taoMediaManager/qtiCreator/helper/createDummyItemData',
    'taoMediaManager/qtiCreator/helper/formatStyles'
], function ($, _, __, promiseQueue, request, urlUtil, creatorDummyItemData, formatStyles) {
    'use strict';

    const serviceController = 'SharedStimulus';
    const serviceExtension = 'taoMediaManager';
    /**
     * QTI proxy definition
     * Related to remote services calls
     * @type {Object}
     */
    return {
        name: 'qtiSharedStimulusItemProxy',

        /**
         * Installs the proxy
         */
        install: function install() {
            /**
             * A promise queue to ensure requests run sequentially
             */
            this.queue = promiseQueue();
        },
        /**
         * Initializes the proxy
         * @returns {Promise} - Returns a promise. The proxy will be fully initialized on resolve.
         *                      Any error will be provided if rejected.
         */
        init: function init() {
            // the method must return a promise with Object
            return Promise.resolve({});
        },

        /**
         * Uninstalls the proxy
         * @returns {Promise} - Returns a promise. The proxy will be fully uninstalled on resolve.
         *                      Any error will be provided if rejected.
         */
        destroy: function destroy() {
            // no request, just a resources cleaning
            this.queue = null;

            // the method must return a promise
            return Promise.resolve();
        },

        /**
         * Gets an item definition by its identifier, also gets its current state
         * @param {String} identifier - The identifier of the sharedStimulus to get
         * @returns {Promise} - Returns a promise. The item data will be provided on resolve.
         *                      Any error will be provided if rejected.
         */
        getItem: function getItem(identifier) {
            return Promise.all([
                request(urlUtil.route('get', serviceController, serviceExtension), { id: identifier }, 'GET'),
                request(urlUtil.route('getStylesheets', 'SharedStimulusStyling', serviceExtension), {
                    uri: identifier
                })
            ]).then(([data, styles]) => {
                const itemData = creatorDummyItemData(data);
                data.baseUrl = urlUtil.route('getFile', 'MediaManager', 'taoMediaManager', { uri: '' });
                data.content = {
                    type: 'qti',
                    data: itemData
                };
                const assetStyles = $('link[data-serial*="preview"]');
                if (assetStyles.length) {
                    assetStyles.remove();
                }

                styles.children.forEach((stylesheet, index) => {
                    const serial = `preview_${index}`;
                    const link = urlUtil.route('loadStylesheet', 'SharedStimulusStyling', 'taoMediaManager', {
                        uri: identifier,
                        stylesheet: stylesheet.name
                    });
                    let cssFile = Object.values(document.styleSheets).find(sheet => typeof sheet.href === 'string' && sheet.href === link);
                    if (!cssFile) {
                        // avoid adding the CSS file on Preview list everytime Asset is clicked
                        data.content.data.stylesheets[serial] = {
                            qtiClass: 'stylesheet',
                            attributes: {
                                href: link,
                                media: 'all',
                                title: '',
                                type: 'text/css'
                            },
                            serial,
                            getComposingElements: () => ({})
                        };
                    }
                });
                return data;
            });
        }
    };
});
