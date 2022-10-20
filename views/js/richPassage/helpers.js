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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

define([
    'jquery',
    'lodash',
    'uri',
    'util/url',
    'core/dataProvider/request',
    'taoMediaManager/qtiCreator/helper/formatStyles'
], function ($, _, uri, urlUtil, request, formatStyles) {
    'use strict';

    /**
     * Get all passage elements qtiClass: 'include' presents in Element
     * @param {Object} element
     * @returns {Array} array of include elements
     */
    function getPassagesFromElement(element = {}) {
        let includes = {};
        _.forEach(['elements', 'choices'], elementCollection => {
            if (elementCollection === 'choices' && _.isArray(element[elementCollection])) {
                // in MatchInterection choices is Array of match sets
                _.forEach(element[elementCollection], choiceMatch => {
                    _.forEach(choiceMatch, choice => {
                        includes = _.extend(includes, getPassagesFromElement(choice));
                    });
                });
            } else {
                _.forEach(element[elementCollection], childElement => {
                    if (childElement.qtiClass === 'include') {
                        includes[childElement.serial] = childElement;
                    } else {
                        includes = _.extend(includes, getPassagesFromElement(childElement));
                    }
                });
            }
        });
        if (element.body) {
            includes = _.extend(includes, getPassagesFromElement(element.body));
        }
        if (element.prompt) {
            includes = _.extend(includes, getPassagesFromElement(element.prompt));
        }
        return includes;
    }

    /**
     * Get all passage elements qtiClass: 'include' presents in item
     * @param {Object} itemData
     * @returns {Array} array of include elements
     */
    function getPassagesFromItemData(itemData = {}) {
        let includes = {};
        if (itemData.content && itemData.content.data && itemData.content.data.body) {
            includes = _.extend(includes, getPassagesFromElement(itemData.content.data.body));
        }
        return includes;
    }

    /**
     * Check all passage elements and inject passage styles in itemData with absolute href
     * @param {Object | Array} elements
     * @param {Object} itemData
     * @returns {Promise}
     */
    function injectPassagesStylesInItemData(elements = {}, itemData = {}) {
        const requests = [];
        const passageUris = [];
        _.forEach(elements, (elem, id) => {
            const passageHref = elem.attributes.href;
            if (/taomedia:\/\/mediamanager\//.test(passageHref)) {
                // only rich passages from Assets
                const passageUri = uri.decode(passageHref.replace('taomedia://mediamanager/', ''));
                if (!passageUris.includes(passageUri)) {
                    passageUris.push(passageUri);
                    requests.push(
                        request(urlUtil.route('getStylesheets', 'SharedStimulusStyling', 'taoMediaManager'), {
                            uri: passageUri
                        })
                            .then(response => {
                                response.children.forEach((stylesheet, index) => {
                                    const stylesheetHref = urlUtil.route('loadStylesheet', 'SharedStimulusStyling', 'taoMediaManager', {
                                        uri: passageUri,
                                        stylesheet: stylesheet.name
                                    });
                                    if ($(`link[href*='${stylesheetHref}']`).length) {
                                        //avoid load repeated CSS files on Item authoring Preview
                                        return false;
                                    }
                                    const serial = `stylesheet_${id}_${index}`;
                                    itemData.content.data.stylesheets[serial] = {
                                        qtiClass: 'stylesheet',
                                        attributes: {
                                            href: stylesheetHref,
                                            media: 'all',
                                            title: '',
                                            type: 'text/css',
                                            onload: (e => formatStyles.handleStylesheetLoad(e, stylesheet))
                                        },
                                        serial
                                    };
                                    itemData.content.data.stylesheets[serial].attributes.includeHref = passageHref;
                                    itemData.content.data.stylesheets[serial].attributes.includeSerial = elem.serial;
                                    elem.stylesheets = {[serial]: stylesheetHref};
                                });
                            })
                            .catch()
                    );
                }
            }
        });
        return Promise.all(requests).then(() => itemData);
    }

    /**
     * Check all passage elements and inject rich passage styles in itemData
     * @param {Object} itemData
     * @returns {Promise}
     */
    function checkAndInjectStylesInItemData(itemData = {}) {
        const itemDataString = JSON.stringify(itemData);
        if (/"qtiClass":"include"/.test(itemDataString)) {
            const elements = getPassagesFromItemData(itemData);
            return injectPassagesStylesInItemData(elements, itemData);
        }
        return Promise.resolve(itemData);
    }

    return {
        getPassagesFromElement,
        getPassagesFromItemData,
        injectPassagesStylesInItemData,
        checkAndInjectStylesInItemData
    };
});
