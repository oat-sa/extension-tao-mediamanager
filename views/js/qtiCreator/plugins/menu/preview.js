
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
    'ui/hider',
    'taoItems/previewer/factory',
    'tpl!taoMediaManager/qtiCreator/plugins/button',
], function($, __, hider, previewerFactory, buttonTpl){
    'use strict';
    var $container;

    /**
     * Returns the configured plugin
     * @returns {Function} the plugin
     */
    return {
        name : 'preview',

        /**
         * Initialize the plugin (called during itemCreator's init)
         * @fires {itemCreator#preview}
         */
        init : function init(areaBroker, url) {
            var self = this;
            $container = areaBroker.getMenuArea();

            /**
             * Preview an item
             * @event itemCreator#preview
             * @param {String} uri - the uri of this item to preview
             */
            //creates the preview button
            this.$element = $(buttonTpl({
                icon: 'preview',
                title: __('Preview the asset'),
                text : __('Preview'),
                cssClass: 'preview-trigger'
            })).on('click', function previewHandler(e){
                $(document).trigger('open-preview.qti-item');
                e.preventDefault();
                self.disable();
                // itemCreator.trigger('preview', itemCreator.getItem().data('uri'));
                var type = 'qtiItem';
                previewerFactory(type, url, { }, {
                    readOnly: false,
                    fullPage: true
                });
                self.enable();
            });
        },

        /**
         * Initialize the plugin (called during itemCreator's render)
         */
        render : function render() {
            //attach the element to the menu area
            $container.append(this.$element);
        },

        /**
         * Called during the itemCreator's destroy phase
         */
        destroy : function destroy() {
            this.$element.remove();
        },

        /**
         * Enable the button
         */
        enable : function enable() {
            this.$element
                .removeProp('disabled')
                .removeClass('disabled');
        },

        /**
         * Disable the button
         */
        disable : function disable() {
            this.$element
                .prop('disabled', true)
                .addClass('disabled');
        },

        /**
         * Show the button
         */
        show: function show() {
            hider.show(this.$element);
        },

        /**
         * Hide the button
         */
        hide: function hide() {
            hider.hide(this.$element);
        }
    };
});
