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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA ;
 */
define([
    'jquery'
], function ($) {
    'use strict';

    function handleStylesheetLoad(e, stylesheet) {
        if (stylesheet && stylesheet.name === 'tao-user-styles.css') {
            return false;
        }

        // get cssRules from owner link tag, referenced in load event
        const path = e && e.composedPath && e.composedPath();
        const linkTag = path[0];

        if (linkTag) {

            const stylesheetUrl = linkTag.href.split('&stylesheet=');
            const rdf_styles = stylesheetUrl[0].split('%23').reverse()[0];

            // Asset Preview
            const assets = $(`[data-identifier='${rdf_styles}']`);
            if (assets.length) {
                assets.each((h, asset) => {
                    const assetClassName = asset.children[0].className.match(/[\w-]*tao-[\w-]*/g)[0];
                    if (assetClassName) {
                        formatStyles(linkTag.sheet, assetClassName);
                    }
                });
                return;
            }

            // Passage on Item
            const stylesheetSerial = linkTag.dataset.serial;
            const passagesItem = $(`div[data-serial*='${stylesheetSerial}']`);
            if (passagesItem.length) {
                passagesItem.each((h, passages) => {
                    const passageWrapper = $(passages).find('.qti-include > div');
                    const passageItemClassName = passageWrapper[0] && passageWrapper[0].className && passageWrapper[0].className.match(/[\w-]*tao-[\w-]*/g)[0];
                    if (passageItemClassName) {
                        formatStyles(linkTag.sheet, passageItemClassName);
                    }
                });
                return;
            }

            // Item Preview
            const passagesPreview = $(`[data-href*='${rdf_styles}']`);
            if (passagesPreview.length) {
                passagesPreview.each((h, passages) => {
                    const passagePreviewClassName = passages.className.match(/[\w-]*tao-[\w-]*/g)[0];
                    if (passagePreviewClassName) {
                        formatStyles(linkTag.sheet, passagePreviewClassName);
                    }
                });
                return;
            }
        }
    }

    function formatStyles(linkTag, className) {
        /**
         * @type {CSSRuleList}
         * @see https://developer.mozilla.org/en-US/docs/Web/API/CSSRuleList
         */
        const { cssRules } = linkTag || {};
        const CSSStyleSheet = linkTag || {};
        const classNameFormated = className && className.length ? `.${className}` : '';

        // prefix rules
        const scopedCssRules = _scopeStyles(cssRules, classNameFormated, ['body html *']);

        if (cssRules && scopedCssRules) {
            Object.values(cssRules).map((index, rule) => {
                CSSStyleSheet.deleteRule(index);
            });
            const newRules = scopedCssRules.split('\n');
            Object.values(newRules).map(rule => {
                CSSStyleSheet.insertRule(rule);
            });
        }

        return;
    };

    /**
     * Apply selector replacement and scope prefixing to a set of style rules
     * @param {CSSRuleList} cssRules
     * @param {String} scopeSelector - applied as prefix; also used as replacementSelector if none specified
     * @param {String[]} toReplace - list of selectors to be replaced by replacementSelector
     * @param {String} replacementSelector
     * @returns {String} styles, with scopeSelector prefix applied
     */
    function _scopeStyles (cssRules, scopeSelector, toReplace, replacementSelector) {
        if (!cssRules) {
            return '';
        }

        if (!replacementSelector) {
            replacementSelector = scopeSelector;
        }

        const scopedStyles = Object.values(cssRules).map(rule => {
            // avoid @import, @media, @keyframes etc

            if (!rule.selectorText) {
                return '';
            }

            /**
             * Need to split selectorList apart from rules, before splitting it by comma
             * @example CSS:
             *   selector1, selector2 { rules; }
             */
            const rulesInBrackets = rule.cssText.substr(rule.selectorText.length).trim();
            const selectors = rule.selectorText.split(/\s*,\s*/);

            const scopedSelectors = [];

            for (let singleSelectorText of selectors) {
                // avoid the most obvious top level single selectors that won't work even if scoped
                if (['html', 'body', '*'].includes(singleSelectorText)) {
                    continue;
                }

                // make the replacements
                if (scopeSelector && toReplace) {
                    for (let toReplaceSelector of toReplace) {
                        if (singleSelectorText.includes(toReplaceSelector)) {
                            singleSelectorText = singleSelectorText.replace(
                                new RegExp(toReplaceSelector, 'ig'),
                                replacementSelector
                            );
                        }
                    }
                }

                // has desired scoping been applied to the rule?
                const containsScopeSelector =
                    singleSelectorText.includes(`${scopeSelector} `) ||
                    singleSelectorText.startsWith(scopeSelector) ||
                    singleSelectorText.endsWith(scopeSelector);

                // scope unscoped rule by the scope selector
                if (scopeSelector && !containsScopeSelector) {
                    scopedSelectors.push(`${scopeSelector} ${singleSelectorText}`);
                } else {
                    scopedSelectors.push(singleSelectorText);
                }
            }

            if (scopedSelectors.length) {
                return `${scopedSelectors.join(',')} ${rulesInBrackets}`;
            }
            return '';
        });

        return scopedStyles.filter(str => str.length > 0).join('\n');
    }

    return {
        formatStyles,
        handleStylesheetLoad
    };
});
