
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
 * This plugin displays add the preview button and launch it.
 * It also provides a mechanism that ask to save
 * the item before the preview (if the item has changed - should).
 *
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */

define([
    'jquery',
    'i18n',
    'core/plugin',
    'ui/hider',
    'taoMediaManager/previewer/adapter/item/qtiSharedStimulusItem',
    'tpl!taoQtiItem/qtiCreator/plugins/button',
], function($, __, pluginFactory, hider, previewerFactory, buttonTpl){


    /**
     * Returns the configured plugin
     * @returns {Function} the plugin
     */
    return pluginFactory({
        name : 'preview',

        /**
         * Initialize the plugin (called during itemCreator's init)
         * @fires {itemCreator#preview}
         */
        init() {
            const self = this;
            const sharedStimulusCreator = this.getHost();

            /**
             * Preview an item
             * @event sharedStimulusCreator#preview
             * @param {String} uri - the uri of this item to preview
             */
            sharedStimulusCreator.on('preview', function(uri) {
                const type = 'qtiItem';

                // TO DO should be created empty item with shared stimulus inside
                previewerFactory(type, uri, { }, {
                    readOnly: false,
                    fullPage: true
                });
            });

            //creates the preview button
            this.$element = $(buttonTpl({
                icon: 'preview',
                title: __('Preview the item'),
                text : __('Preview'),
                cssClass: 'preview-trigger'
            })).on('click', function previewHandler(e){
                $(document).trigger('open-preview.qti-item');

                e.preventDefault();

                self.disable();

                sharedStimulusCreator.trigger('preview', SharedStimulusCreator.getSharedStimulusId());

                self.enable();
            });
        },

        /**
         * Initialize the plugin (called during SharedStimulusCreator's render)
         */
        render() {
            //attach the element to the menu area
            const $container = this.getAreaBroker().getMenuArea();
            $container.append(this.$element);
        },

        /**
         * Called during the SharedStimulusCreator's destroy phase
         */
        destroy() {
            this.$element.remove();
        },

        /**
         * Enable the button
         */
        enable() {
            this.$element
                .removeProp('disabled')
                .removeClass('disabled');
        },

        /**
         * Disable the button
         */
        disable() {
            this.$element
                .prop('disabled', true)
                .addClass('disabled');
        },

        /**
         * Show the button
         */
        show() {
            hider.show(this.$element);
        },

        /**
         * Hide the button
         */
        hide() {
            hider.hide(this.$element);
        }
    });
});
