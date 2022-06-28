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
    'taoMediaManager/qtiCreator/editor/styleEditor/styleEditor'
], function (styleEditor) {
    'use strict';

    function getStyles (stylesheetPrefix, duplicated = false) {
        const assetStyles = $(`link[data-serial*=${stylesheetPrefix}]`);
        assetStyles.each((i, style) => {
            if (style) {
                let assets = '';
                let assetClassName = '';

                // styles duplicates on Preview inside Authoring editor
                if (duplicated) {
                    const assetHref = $(`link[href="${style.href}"]`);
                    if (assetHref && assetHref.length > 1) {
                        assetHref.each((j, styleNone) => {
                            if (styleNone.attributes['data-serial'].value.match(/[\w-]*stylesheet_[\w-]*/g)) {
                                // item preview inside editor reload CSS files again. Reuse CSS format from editor.
                                styleNone.disabled = true;
                            }
                        })
                    }
                    assets = $('.preview-content .qti-include');
                } else {
                    assets = $('.qti-itemBody');
                }

                if (assets.length) {
                    assets.each((h, asset) => {
                        const hasClass = asset.className.match(/[\w-]*tao-[\w-]*/g);
                        if (!!hasClass && hasClass.length) {
                            assetClassName = hasClass[0];
                        } else {
                            // in case Passage has no className and it is preview outside editor
                            assetClassName = styleEditor.generateMainClass();
                            $(asset).addClass(assetClassName);
                        }

                        if (style.sheet) {
                            const stylesheetName = style.href.split('&stylesheet=');
                            if (stylesheetName && stylesheetName[1] !== 'tao-user-styles.css') {
                                // check rdf matches to apply the attached CSS file to the passage
                                const rdf_styles = stylesheetName[0].split('%23').reverse()[0];
                                const rdf_asset = asset.dataset.href.split('_').reverse()[0];
                                if (rdf_styles === rdf_asset) {
                                    formatStyles(style.sheet, assetClassName);
                                }
                            }
                        } else {
                            // in case Passage has no className and it is preview inside editor
                            const renderLayout = $('#item-editor-panel .qti-itemBody .qti-include > div');
                            if (renderLayout.length) {
                                const renderHasClass = renderLayout[h].className.match(/[\w-]*tao-[\w-]*/g);
                                if (!hasClass && renderHasClass && renderHasClass.length) {
                                    assetClassName = renderHasClass[0];
                                    $(asset).addClass(assetClassName);
                                }
                            }
                            // styles duplicated means preview on edit, already has a class from editor
                            if (duplicated) {
                                assetClassName = '';
                            }

                            const assetHref2 = $(`link[href="${style.href}"]:not([disabled])`);
                            const stylesheetName = style.href.split('stylesheet=');
                            if (stylesheetName && stylesheetName[1] !== 'tao-user-styles.css' && assetHref2.length) {
                                formatStyles(assetHref2[0].sheet, assetClassName);
                            }
                        }
                    })
                }
            }
        })
    }

    function formatStyles (linkTag, className) {
        const { cssRules } = linkTag && linkTag.sheet || linkTag || {};
        const CSSStyleSheet = linkTag && linkTag.sheet || linkTag || {};
        const classNameFormated = className && className.length ? `.${className}` : '';
        // prefix rules
        const scopedCssRules = _scopeStyles(cssRules, classNameFormated, ['body html *']);

        if (cssRules) {
            Object.values(cssRules).map((index, rule) => {
                CSSStyleSheet.deleteRule(index);
            })
            const newRules = scopedCssRules.split('\n');
            Object.values(newRules).map(rule => {
                CSSStyleSheet.insertRule(rule);
            })
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
        getStyles
    };
});
