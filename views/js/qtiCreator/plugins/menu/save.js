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
 * This plugin adds a save button to the menu.
 * Could be useful, or not (Save is so 90's, we want auto-saving with undo/redo capabilities)
 *
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */

define([
    'jquery',
    'i18n',
    'core/plugin',
    'ui/hider',
    'tpl!taoQtiItem/qtiCreator/plugins/button'
], function($, __, pluginFactory, hider, buttonTpl){


    /**
     * Returns the configured plugin
     * @returns {Function} the plugin
     */
    return pluginFactory({
        name : 'save',

        /**
         * Initialize the plugin (called during itemCreator's init)
         */
        init() {
            const self = this;
            const sharedStimulusCreator = this.getHost();

            this.$element = $(buttonTpl({
                icon: 'save',
                title: __('Save the asset'),
                text : __('Save'),
                cssClass: 'save-trigger'
            })).on('click', function saveHandler(e){
                e.preventDefault();
                self.disable();
                sharedStimulusCreator.trigger('save');
            });

            this.hide();
            this.disable();

            sharedStimulusCreator.on('ready saved error', function(){
                self.enable();
            });
        },

        /**
         * Called during the sharedStimulusCreator's render phase
         */
        render() {
            //attach the element to the menu area
            const $container = this.getAreaBroker().getMenuArea();
            $container.append(this.$element);
            this.show();
        },

        /**
         * Called during the sharedStimulusCreator's destroy phase
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
