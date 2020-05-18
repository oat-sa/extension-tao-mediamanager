
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
 * This plugin displays the item label (from
 *
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */

define([
    'jquery',
    'i18n',
    'core/plugin'
], function($, __, pluginFactory) {
    'use strict';

    /**
     * Returns the configured plugin
     * @returns {Function} the plugin
     */
    return pluginFactory({
        name : 'title',

        /**
         * Get the title and area to render
         */
        init : function init() {
            var config = this.getHost().getConfig();
            var passage   = this.getHost().getPassage();

            if(passage && !_.isEmpty(passage.attr('title'))){
                this.title = passage.attr('title');
            }
            else if(config && config.properties && config.properties.label){
                this.title = config.properties.label;
            }
        },

        /**
         * Hook to the host's render
         */
        render : function render() {
            if(this.title){
                //attach the element to the title area
                this.getAreaBroker()
                    .getTitleArea()
                    .text(this.title);
            }
        }
    });
});
