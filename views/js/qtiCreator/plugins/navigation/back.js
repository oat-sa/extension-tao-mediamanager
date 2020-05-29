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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

/**
 * This plugin adds a "back" button that does a History.go(-1)
 * (like your browser's back button).
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
        name : 'back',

        /**
         * Initialize the plugin
         */
        init() {
            const passageCreator = this.getHost();

            passageCreator.on('exit', function(){
                window.history.back();
            });

            this.$element = $(buttonTpl({
                icon: 'left',
                title: __('Back to Manage Assets'),
                text : __('Manage Assets'),
                cssClass: 'back-action'
            })).on('click', function backHandler(e){
                e.preventDefault();
                passageCreator.trigger('exit');
            });
            this.hide();
        },

        /**
         * Called during the passageCreator's render phase
         */
        render() {
            //attach the element to the menu area
            const $container = this.getAreaBroker().getMenuLeftArea();
            $container.append(this.$element);
            this.show();
        },

        /**
         * Called during the passageCreator's destroy phase
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
        hide(){
            hider.hide(this.$element);
        }
    });
});
