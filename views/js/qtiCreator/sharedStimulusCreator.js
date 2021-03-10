/*
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
 *
 */

/**
 * The Shared Stimulus creator factory let's you create Shared Stimulus
 * The Shared Stimulus is wrapped in Item that allow reuse taoQtiItem modules
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 * @author Hanna Dzmitryieva <hanna@taotesting.com>
 */
define([
    'jquery',
    'lodash',
    'i18n',
    'core/eventifier',
    'taoQtiItem/qtiCreator/context/qtiCreatorContext',
    'taoMediaManager/qtiCreator/helper/sharedStimulusLoader',
    'taoMediaManager/qtiCreator/helper/creatorRenderer',
    'taoQtiItem/qtiCreator/helper/commonRenderer', //for read-only element : preview + xinclude
    'taoMediaManager/qtiCreator/editor/propertiesPanel',
    'taoQtiItem/qtiCreator/model/helper/event',
    'taoMediaManager/qtiCreator/helper/xmlRenderer',
    'taoQtiItem/qtiItem/helper/xmlNsHandler',
    'core/request',
    'util/url',
    'taoMediaManager/qtiCreator/editor/interactionsPanel',
    'taoMediaManager/qtiCreator/editor/styleEditor/styleEditor'
], function (
    $,
    _,
    __,
    eventifier,
    qtiCreatorContextFactory,
    sharedStimulusLoader,
    creatorRenderer,
    commonRenderer,
    propertiesPanel,
    eventHelper,
    xmlRenderer,
    xmlNsHandler,
    request,
    urlUtil,
    interactionPanel,
    styleEditor
) {
    'use strict';

    /**
     * Load an Shared Stimulus
     * @param {String} id - the Shared Stimulus ID
     * @param {String} uri - the Shared Stimulus URI
     * @param {String} assetDataUrl - the data url
     *
     * @returns {Promise} that resolve with the loaded item model
     */
    const loadSharedStimulus = function loadSharedStimulus(id, uri, assetDataUrl) {
        return new Promise(function (resolve, reject) {
            sharedStimulusLoader.loadSharedStimulus({ id, uri, assetDataUrl }, function (item) {
                if (!item) {
                    reject(new Error('Unable to load the Shared Stimulus'));
                }

                //set useful data :
                item.data('uri', uri);
                resolve(item);
            });
        });
    };

    /**
     * Build a new Shared Stimulus Creator
     * @param {Object} config - the creator's configuration
     * @param {String} config.properties.uri - the URI of the Shared Stimulus to load (properties structure is kept as legacy)
     * @param {String} config.properties.label - the label of the Shared Stimulus to load (properties structure is kept as legacy)
     * @param {String} config.properties.baseUrl - the base URL used to resolve assets
     * @param {String[]} [config.interactions] - the list of additional interactions to load (PCI)
     * @param {String[]} [config.infoControls] - the list of info controls to load (PIC)
     * @param {areaBroker} areaBroker - a mapped areaBroker
     * @param {Function[]} pluginFactories - the plugin's factory, ready to be instantiated
     * @returns {sharedStimulusCreator} an event emitter object, with the usual lifecycle
     * @throws {TypeError}
     */
    const sharedStimulusCreatorFactory = function sharedStimulusCreatorFactory(config, areaBroker, pluginFactories) {
        let sharedStimulusCreator;
        const qtiCreatorContext = qtiCreatorContextFactory();
        const plugins = {};

        /**
         * Run a method in all plugins
         *
         * @param {String} method - the method to run
         * @returns {Promise} once that resolve when all plugins are done
         */
        const pluginRun = function pluginRun(method) {
            const execStack = [];

            _.forEach(plugins, function (plugin) {
                if (_.isFunction(plugin[method])) {
                    execStack.push(plugin[method]());
                }
            });

            return Promise.all(execStack);
        };

        //validate parameters
        if (!_.isPlainObject(config)) {
            throw new TypeError('The shared stimulus creator configuration is required');
        }
        if (!config.properties || _.isEmpty(config.properties.uri) || _.isEmpty(config.properties.baseUrl)) {
            throw new TypeError(
                'The creator configuration must contains the required properties triples: uri, label and baseUrl'
            );
        }
        if (!areaBroker) {
            throw new TypeError('Without an areaBroker there are no chance to see something you know');
        }

        //factor the new sharedStimulusCreator
        sharedStimulusCreator = eventifier({
            //lifecycle

            /**
             * Initialize the Shared Stimulus creator:
             *  - set up the registries for portable elements
             *  - load the Shared Stimulus
             *  - instantiate and initialize the plugins
             *
             * @returns {sharedStimulusCreator} chains
             * @fires sharedStimulusCreator#init - once initialized
             * @fires sharedStimulusCreator#error - if something went wrong
             */
            init() {
                //instantiate the plugins first
                _.forEach(pluginFactories, pluginFactory => {
                    const plugin = pluginFactory(this, areaBroker);
                    plugins[plugin.getName()] = plugin;
                });

                // quick-fix: clear all ghost events listeners
                // prevent ghosting of Shared Stimulus states and other properties
                $(document).off('.qti-widget');

                /**
                 * Save the Shared Stimulus on "save" event
                 * @event sharedStimulusCreator#save
                 * @param {Boolean} [silent] - true to not trigger the success feedback
                 * @fires sharedStimulusCreator#saved once the save is done
                 * @fires sharedStimulusCreator#error
                 */
                this.on('save', silent => {
                    const item = this.getItem();
                    const styles = styleEditor.getStyle();
                    if (_.size(styles) && !item.attributes.class) {
                        item.attributes.class = styleEditor.getHashClass();
                    }

                    const xml = xmlNsHandler.restoreNs(xmlRenderer.render(item), item.getNamespaces());

                    //do the save
                    Promise.all([
                        request({
                            url: urlUtil.route('patch', 'SharedStimulus', 'taoMediaManager', { id: config.properties.id }),
                            type: 'POST',
                            contentType: 'application/json',
                            dataType: 'json',
                            data: JSON.stringify({ body: xml }),
                            method: 'PATCH',
                            noToken: true
                        }),
                        styleEditor.save()
                    ])
                    .then(() => {
                        if (!silent) {
                            this.trigger('success');
                        }

                        this.trigger('saved');
                    })
                    .catch(err => {
                        this.trigger('error', err);
                    });
                });

                this.on('exit', function () {
                    $('.item-editor-item', areaBroker.getItemPanelArea()).empty();
                    this.destroy();
                });

                loadSharedStimulus(config.properties.id, config.properties.uri, config.properties.assetDataUrl)
                    .then(item => {
                        if (!_.isObject(item)) {
                            this.trigger('error', new Error(`Unable to load the item ${config.properties.label}`));
                            return;
                        }

                        this.item = item;
                    })
                    .then(() => pluginRun('init'))
                    .then(() => {
                        /**
                         * @event sharedStimulusCreator#init the initialization is done
                         * @param {Object} item - the loaded item
                         */
                        this.trigger('init', this.item);
                    })
                    .then(() => {
                        // forward context error
                        qtiCreatorContext.on('error', err => {
                            this.trigger('error', err);
                        });
                        return qtiCreatorContext.init();
                    })
                    .catch(err => {
                        this.trigger('error', err);
                    });

                return this;
            },

            /**
             * Renders the creator
             * Because of the actual structure, it also intialize some components (panels, toolbars, etc.).
             *
             * @returns {sharedStimulusCreator} chains
             * @fires sharedStimulusCreator#render - once everything is in place
             * @fires sharedStimulusCreator#ready - once the creator's components' are ready (not yet reliable)
             * @fires sharedStimulusCreator#error - if something went wrong
             */
            render() {
                const self = this;
                const item = this.getItem();

                if (!item || !_.isFunction(item.getUsedClasses)) {
                    return this.trigger('error', new Error('We need an item to render.'));
                }

                //configure commonRenderer for the preview and initial qti element rendering
                commonRenderer.setContext(areaBroker.getItemPanelArea());
                commonRenderer.get(true, config).setOption('baseUrl', config.properties.baseUrl);

                interactionPanel(areaBroker.getInteractionPanelArea());

                //the renderers' widgets do not handle async yet, so we rely on this event
                //TODO ready should be triggered once every renderer's widget is done (ie. promisify everything)
                $(document).on('ready.qti-widget', (e, elt) => {
                    if (elt.element.qtiClass === 'assessmentItem') {
                        this.trigger('ready');
                    }
                });

                // pass an context reference to the renderer
                config.qtiCreatorContext = qtiCreatorContext;

                creatorRenderer
                    .get(true, config, areaBroker)
                    .setOptions(config.properties)
                    .load(function () {
                        let widget;

                        //set renderer
                        item.setRenderer(this);

                        //render item (body only) into the "drop-area"
                        areaBroker.getItemPanelArea().append(item.render());

                        //"post-render it" to initialize the widget
                        Promise.all(item.postRender(_.clone(config.properties)))
                            .then(function () {
                                //set reference to item widget object
                                areaBroker.getContainer().data('widget', item);

                                widget = item.data('widget');

                                item.attributes.class ? styleEditor.setHashClass(item.attributes.class) : styleEditor.generateHashClass();
                                // set class on container for style editor
                                widget.$container.addClass(styleEditor.getHashClass());
                                propertiesPanel(areaBroker.getPropertyPanelArea(), widget, config.properties);

                                //init event listeners:
                                eventHelper.initElementToWidgetListeners();

                                return pluginRun('render').then(function () {
                                    self.trigger('render');
                                });
                            })
                            .catch(function (err) {
                                self.trigger('error', err);
                            });
                    }, item.getUsedClasses());

                return this;
            },

            /**
             * Clean up everything and destroy the sharedStimulusCreator
             *
             * @returns {sharedStimulusCreator} chains
             */
            destroy() {
                $(document).off('.qti-widget');

                pluginRun('destroy')
                    .then(() => qtiCreatorContext.destroy())
                    .then(() => {
                        this.trigger('destroy');
                    })
                    .catch(err => {
                        this.trigger('error', err);
                    });
                return this;
            },

            //accessors

            /**
             * Give an access to the loaded item
             * @returns {Object} the item
             */
            getItem() {
                return this.item;
            },

            getSharedStimulusId() {
                return config.properties.id;
            },

            /**
             * Give an access to the config
             * @returns {Object} the config
             */
            getConfig() {
                return config;
            }
        });

        return sharedStimulusCreator;
    };

    return sharedStimulusCreatorFactory;
});
