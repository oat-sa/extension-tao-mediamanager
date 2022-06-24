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
    'lodash',
    'uri',
    'util/url',
    'core/dataProvider/request',
    'taoMediaManager/qtiCreator/helper/formatStyles',
    'taoMediaManager/qtiCreator/editor/styleEditor/styleEditor',
], function (_, uri, urlUtil, request, formatStyles, styleEditor) {
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
                                response.children.forEach((element, index) => {
                                    const serial = `stylesheet_${id}_${index}`;
                                    const link = urlUtil.route('loadStylesheet', 'SharedStimulusStyling', 'taoMediaManager', {
                                        uri: passageUri,
                                        stylesheet: element.name
                                    });
                                    itemData.content.data.stylesheets[serial] = {
                                        qtiClass: 'stylesheet',
                                        attributes: {
                                            href: link,
                                            media: 'all',
                                            title: '',
                                            type: 'text/css'
                                        },
                                        serial
                                    };
                                });
                                setTimeout(() => {
                                    const assetStyles = $('link[data-serial*="stylesheet"]');
                                    assetStyles.each((i, style) => {
                                        // styles duplicates on Authoring editor and Preview inside editor
                                        const assetHref = $(`link[href="${style.href}"]`);
                                        if (assetHref && assetHref.length > 1) {
                                            assetHref.each((j, styleNone) => {
                                                if (styleNone.attributes['data-serial'].value.match(/[\w-]*stylesheet_[\w-]*/g)) {
                                                    styleNone.disabled = true;
                                                }
                                            })
                                        }
                                        if (style) {
                                            const asset = $('.preview-content .qti-include');
                                            let assetClassName = '';
                                            const hasClass = asset[0].className.match(/[\w-]*tao-[\w-]*/g);
                                            if (hasClass && hasClass.length) {
                                                assetClassName = hasClass[0];
                                            } else {
                                                // in case Passage has no className and it is preview outside editor
                                                assetClassName = styleEditor.generateMainClass();
                                                asset.addClass(assetClassName);
                                            }
                                            if (style.sheet) {
                                                const stylesheetName = style.href.split('stylesheet=');
                                                if (stylesheetName && stylesheetName[1] !== 'tao-user-styles.css') {
                                                    formatStyles(style.sheet, assetClassName);
                                                }
                                            } else {
                                                // in case Passage has no className and it is preview inside editor
                                                const renderLayout = $('.qti-itemBody .qti-include > div');
                                                const renderHasClass = renderLayout[0].className.match(/[\w-]*tao-[\w-]*/g);
                                                if (renderHasClass && renderHasClass.length) {
                                                    assetClassName = renderHasClass[0];
                                                    asset.addClass(assetClassName);
                                                }
                                                const assetHref2 = $(`link[href="${style.href}"]:not([disabled])`);
                                                const stylesheetName = style.href.split('stylesheet=');
                                                if (stylesheetName && stylesheetName[1] !== 'tao-user-styles.css' && assetHref2[0]) {
                                                    formatStyles(assetHref2[0].sheet);
                                                }
                                            }
                                        }
                                    })
                                }, 1000);
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
