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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */
define([
    'jquery',
    'lodash',
    'taoMediaManager/qtiCreator/helper/creatorRenderer',
    'taoQtiItem/qtiCreator/model/helper/container',
    'taoQtiItem/qtiCreator/editor/gridEditor/content'
], function($, _, creatorRenderer, containerHelper, gridContentHelper){

    // all files that use qtiCreator/helper/creatorRenderer should be copied from taoQtiItem
    // because they should use proper locations that are binded in Renderer
    // qtiCreator/helper/creatorRenderer use qtiCreator/renderers/Renderer
    // qtiCreator/renderers/Renderer return Renderer with binded locations from qtiCreator/renderers/config

    const contentHelper = {};

    /**
     * Create elements from a widget box having the 'data-new=true' attribute
     *  - render the elements at their placeholder in $container
     *  - apply callback on each widget
     * @param {DOMElement} container - element that will contain the new ones
     * @param {JQuery} $container - DOM container where the new elements are created
     * @param {String} data - corresponding HTML (?)
     * @param {function} callback - will be triggered for each new created element
     */
    contentHelper.createElements = function(container, $container, data, callback){

        const $dummy = $('<div>').html(data);

        containerHelper.createElements(container, gridContentHelper.getContent($dummy), function(newElts){

            creatorRenderer.get().load(function(){
                let serial,
                    elt,
                    $placeholder,
                    $widget,
                    widget;

                for(serial in newElts){

                    elt = newElts[serial];
                    $placeholder = $container.find(`.widget-box[data-new][data-qti-class=${elt.qtiClass}]`);

                    elt.setRenderer(this);
                    elt.render($placeholder);

                    //render widget
                    elt.postRender();

                    widget = elt.data('widget');
                    $widget = widget.$original;

                    //inform height modification
                    $widget.trigger('contentChange.gridEdit');

                    if(_.isFunction(callback)){
                        callback(widget);
                    }
                }

            }, this.getUsedClasses());
        });

    };

    contentHelper.changeInnerWidgetState = function _changeInnerWidgetState(outerWidget, state){

        const selector = [];
        _.forEach(['img', 'math', 'object', 'include'], function(qtiClass){
            selector.push(`[data-html-editable] .widget-${qtiClass}`);
        });

        outerWidget.$container.find(selector.join(',')).each(function(){
            const innerWidget = $(this).data('widget');
            if(innerWidget){
                innerWidget.changeState(state);
            }
        });
    };

    return contentHelper;
});
