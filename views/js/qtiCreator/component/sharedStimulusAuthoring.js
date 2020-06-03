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
 * Copyright (c) 2020 Open Assessment Technologies SA ;
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */
define([
    'lodash',
    'ui/component',
    'core/pluginLoader',
    'taoMediaManager/qtiCreator/sharedStimulusCreator',
    'taoQtiItem/qtiCreator/editor/areaBroker',
    'tpl!taoMediaManager/qtiCreator/component/tpl/sharedStimulusAuthoring',
    'css!taoQtiItemCss/qti-runner.css',
    'css!taoQtiItemCss/themes/default.css',
    'css!taoQtiItemCss/item-creator.css'
], function (
    _,
    componentFactory,
    pluginLoaderFactory,
    sharedStimulusCreatorFactory,
    areaBrokerFactory,
    componentTpl
) {
    'use strict';

    const defaultPlugins = [{
        module: 'taoQtiItem/qtiCreator/plugins/content/title',
        bundle: 'taoQtiItem/loader/taoQtiItem.min',
        category: 'content'
    }, {
        module: 'taoMediaManager/qtiCreator/plugins/navigation/back',
        bundle: 'taoMediaManager/loader/taoMediaManager.min',
        category: 'panel'
    }, {
        module: 'taoMediaManager/qtiCreator/plugins/menu/save',
        bundle: 'taoMediaManager/loader/taoMediaManager.min',
        category: 'panel'
    }, {
        module: 'taoMediaManager/qtiCreator/plugins/menu/preview',
        bundle: 'taoMediaManager/loader/taoMediaManager.min',
        category: 'panel'
    }];
    /**
     * Embeds the assets creator UI in a component
     *
     * @example
     *  const container = $();
     *  const config = {
     *      plugins: [],
     *      properties: {
     *          uri: 'http://item#rdf-123',
     *          baseUrl: 'http://foo/bar/',
     *          // ...
     *      }
     *  };
     *  const component = sharedStimulusAuthoringFactory(container, config)
     *      .on('ready', function onComponentReady() {
     *          // ...
     *      });
     *
     * @param {HTMLElement|String} container
     * @param {Object} config - The setup for the sharedStimulus creator
     * @param {Object} config.properties - The list of properties expected by the sharedStimulus creator
     * @param {Object} config.properties.uri - The URI of the sharedStimulus to author
     * @param {Object} config.properties.id - The ID of the sharedStimulus to author
     * @param {Object} config.properties.baseUrl - The base URL to retrieve the assets
     * @param {String} config.properties.sharedStimulusDataUrl - URL for getting sharedStimulus data (passed through to sharedStimulusCreator)
     * @param {Object[]} [config.plugins] - Additional plugins to load
     * @returns {component}
     * @fires ready - When the component is ready to work
     */
    function sharedStimulusAuthoringFactory(container, config = {}) {
        let areaBroker;
        let sharedStimulusCreator;

        const plugins = Array.isArray(config.plugins) ? config.plugins : [];
        config.plugins = [...defaultPlugins, ...plugins];

        const pluginLoader = pluginLoaderFactory();

        const api = {
            /**
             * Gets access to the area broker
             * @returns {areaBroker}
             */
            getAreaBroker() {
                return areaBroker;
            }
        };

        const sharedStimulusAuthoring = componentFactory(api)
            // set the component's layout
            .setTemplate(componentTpl)

            // auto render on init
            .on('init', function () {
                // load plugins dynamically
                _.forEach(this.getConfig().plugins, plugin => {
                    if (plugin && plugin.module) {
                        if (plugin.exclude) {
                            pluginLoader.remove(plugin.module);
                        } else {
                            pluginLoader.add(plugin);
                        }
                    }
                });

                // load the plugins, then render the sharedStimulus creator
                pluginLoader.load()
                    .then(() => this.render(container))
                    .catch(err => this.trigger('error', err));
            })

            // renders the component
            .on('render', function () {
                const $container = this.getElement();
                areaBroker = areaBrokerFactory($container, {
                    'menu': $container.find('.menu'),
                    'menuLeft': $container.find('.menu-left'),
                    'menuRight': $container.find('.menu-right'),
                    'contentCreatorPanel': $container.find('#item-editor-panel'),
                    'editorBar': $container.find('#item-editor-panel .item-editor-bar'),
                    'title': $container.find('#item-editor-panel .item-editor-bar h1'),
                    'toolbar': $container.find('#item-editor-panel .item-editor-bar #toolbar-top'),
                    'interactionPanel': $container.find('#item-editor-interaction-bar'),
                    'propertyPanel': $container.find('#item-editor-item-widget-bar'),
                    'itemPanel': $container.find('#item-editor-scroll-inner'),
                    'itemPropertyPanel': $container.find('#sidebar-right-item-properties'),
                    'itemStylePanel': $container.find('#item-style-editor-bar'),
                    'modalContainer': $container.find('#modal-container'),
                    'elementPropertyPanel': $container.find('#item-editor-body-element-property-bar .panel')
                });

                sharedStimulusCreator = sharedStimulusCreatorFactory(this.getConfig(), areaBroker, pluginLoader.getPlugins())
                    .spread(this, 'error success ready')
                    .on('init', function () {
                        this.render();
                    })
                    .init();
            })

            .on('destroy', function () {
                if (sharedStimulusCreator) {
                    sharedStimulusCreator.destroy();
                }
                sharedStimulusCreator = null;
                areaBroker = null;
            });

        // initialize the component with the provided config
        // defer the call to allow to listen to the init event
        _.defer(() => sharedStimulusAuthoring.init(config));

        return sharedStimulusAuthoring;
    }

    return sharedStimulusAuthoringFactory;
});