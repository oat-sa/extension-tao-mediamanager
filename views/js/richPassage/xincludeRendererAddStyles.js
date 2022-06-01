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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA ;
 */
define(['jquery', 'uri', 'util/url', 'core/dataProvider/request', 'taoMediaManager/qtiCreator/helper/formatStyles'], function ($, uri, urlUtil, request, formatStyles) {
    'use strict';

    return function xincludeRendererAddStyles(passageHref, passageClassName, head = $('head')) {
        if (/taomedia:\/\/mediamanager\//.test(passageHref)) {
            // check rich passage styles and inject them to item
            const passageUri = uri.decode(passageHref.replace('taomedia://mediamanager/', ''));
            request(urlUtil.route('getStylesheets', 'SharedStimulusStyling', 'taoMediaManager'), {
                uri: passageUri
            })
                .then(response => {
                    response.forEach(element => {
                        // check different names of elements
                        const link = urlUtil.route('loadStylesheet', 'SharedStimulusStyling', 'taoMediaManager', {
                            uri: passageUri,
                            stylesheet: element
                        });
                        const styleElem = $('<link>', {
                            rel: 'stylesheet',
                            type: 'text/css',
                            href: link,
                            'data-serial': passageUri,
                            disabled: 'disabled'
                        });
                        head.append(styleElem);
                        if (document.styleSheets.length && element !== 'tao-user-styles.css') {
                            setTimeout(
                                function () {
                                    const cssFile = Object.values(document.styleSheets).find(sheet => sheet.href === link);
                                    if (cssFile) {
                                        formatStyles(cssFile, passageClassName);
                                    }
                                }, 100
                            );
                        }

                    });
                })
                .catch();
        }
    };
});
