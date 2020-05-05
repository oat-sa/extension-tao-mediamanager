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
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */
define([
    'taoMediaManager/qtiCreator/component/itemAuthoring'
], function (itemAuthoringFactory) {
    'use strict';

    /**
     * List of required plugins that should be loaded in order to make the item creator work properly
     * @type {Object[]}
     */
    const defaultPlugins = [
        {
            module: 'taoMediaManager/qtiCreator/plugins/content/title',
            bundle: 'taoMediaManager/loader/taoMediaManager.min',
            category: 'content'
        },
        // {
        //     module: 'taoMediaManager/qtiCreator/plugins/content/changeTracker',
        //     bundle: 'taoMediaManager/loader/taoMediaManager.min',
        //     category: 'content'
        // },
        // {
        //     module: 'taoMediaManager/qtiCreator/plugins/panel/outcomeEditor',
        //     bundle: 'taoMediaManager/loader/taoMediaManager.min',
        //     category: 'panel'
        // }
    ];

/**
 * Embeds the item creator UI in a component
 *
 * @example
 *  const container = $();
 *  const config = {
 *      plugins: [],
 *      properties: {
 *          uri: 'http://item#rdf-123',
 *          label: 'Item',
 *          baseUrl: 'http://foo/bar/',
 *          // ...
 *      }
 *  };
 *  const component = itemAuthoringFactory(container, config)
 *      .on('ready', function onComponentReady() {
 *          // ...
 *      });
 *
 * @param {HTMLElement|String} container
 * @param {Object} config - The setup for the item creator
 * @param {Object} config.properties - The list of properties expected by the item creator
 * @param {Object} config.properties.uri - The URI of the item to author
 * @param {Object} config.properties.label - The displayed label
 * @param {Object} config.properties.baseUrl - The base URL to retrieve the assets
 * @param {Object[]} [config.plugins] - Additional plugins to load
 * @param {Object[]} [config.contextPlugins] - Some context plugins
 * @returns {component}
 * @fires ready - When the component is ready to work
 */
function itemAuthoringWrapperFactory(container, config = {}) {
    const contextPlugins = Array.isArray(config.contextPlugins) ? config.contextPlugins : [];
    const plugins = Array.isArray(config.plugins) ? config.plugins : [];
    config.plugins = [...defaultPlugins, ...plugins, ...contextPlugins];
    return itemAuthoringFactory(container, config);
}

    return itemAuthoringWrapperFactory;
});