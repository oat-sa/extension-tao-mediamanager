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
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */
define([
    'jquery',
    'context',
    'taoQtiTestPreviewer/previewer/runner',
    'taoMediaManager/qtiCreator/helper/formatStyles',
    'taoMediaManager/qtiCreator/editor/styleEditor/styleEditor',
    'css!taoQtiTestPreviewer/previewer/provider/item/css/item'
], function ($, context, previewerFactory, formatStyles, styleEditor) {
    'use strict';

    /**
     * Builds a test runner to preview test item
     * @param {jQuery|HTMLElement|String} container - The container in which renders the component
     * @param {Object} [config] - The testRunner options
     * @param {String} [config.itemUri] - The ID of the SharedStimulus (for consisten with other providers)
     * @param {Object[]} [config.plugins] - Additional plugins to load
     * @param {String} [config.fullPage] - Force the previewer to occupy the full window.
     * @param {String} [config.readOnly] - Do not allow to modify the previewed item.
     * @param {Function} [template] - An optional template for the component
     * @returns {previewer}
     */
    return function qtiItemPreviewerFactory(container, config = {}, template = null) {
        const testRunnerConfig = {
            testDefinition: 'test-container',
            serviceCallId: 'previewer',
            providers: {
                runner: {
                    id: 'qtiItemPreviewer',
                    module: 'taoQtiTestPreviewer/previewer/provider/item/item',
                    bundle: 'taoQtiTestPreviewer/loader/qtiPreviewer.min',
                    category: 'runner'
                },
                proxy: {
                    id: 'qtiSharedStimulusItemProxy',
                    module: 'taoMediaManager/previewer/proxy/qtiSharedStimulusItem',
                    bundle: 'taoMediaManager/loader/taoMediaManager.min',
                    category: 'proxy'
                },
                communicator: {
                    id: 'request',
                    module: 'core/communicator/request',
                    bundle: 'loader/vendor.min',
                    category: 'communicator'
                },
                plugins: config.plugins || []
            },
            options: {
                view: config.view,
                readOnly: config.readOnly,
                fullPage: config.fullPage,
                plugins: config.pluginsOptions,
                hideActionBars: config.hideActionBars
            },
            proxyProvider: 'qtiSharedStimulusItemProxy'
        };

        //extra context config
        testRunnerConfig.loadFromBundle = !!context.bundle;

        return previewerFactory(container, testRunnerConfig, template).on('ready', function(runner) {
            runner.on('renderitem', () => {
                if (config.itemState) {
                    runner.itemRunner.setState(config.itemState);
                }
                this.trigger('preview-loaded');
                $('link[data-serial*="preview"').each((i, style) => {
                    if (style) {
                        const asset = $('.qti-itemBody');
                        let assetClassName = '';
                        if (asset.length) {
                            const hasClass = asset[0].className.match(/[\w-]*tao-[\w-]*/g);
                            if (!!hasClass && hasClass.length) {
                                assetClassName = hasClass[0];
                            } else {
                                assetClassName = styleEditor.generateMainClass();
                                asset.addClass(assetClassName);
                            }

                            if (style.sheet) {
                                const stylesheetName = style.href.split('stylesheet=');
                                if (stylesheetName && stylesheetName[1] !== 'tao-user-styles.css') {
                                    formatStyles(style.sheet, assetClassName);
                                }
                            } else {
                                style.onload = () => {
                                    const stylesheetName = style.href.split('stylesheet=');
                                    if (stylesheetName && stylesheetName[1] !== 'tao-user-styles.css') {
                                        formatStyles(style.sheet, assetClassName);
                                    }
                                };
                            }
                        }
                    }
                });
            });
            if (config.itemUri) {
                return runner.loadItem(config.itemUri);
            }
        });
    };
});
