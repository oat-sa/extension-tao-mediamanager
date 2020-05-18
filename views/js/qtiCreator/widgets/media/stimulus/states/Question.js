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

define([
    'jquery',
    'taoMediaManager/qtiCreator/widgets/states/factory',
    'taoMediaManager/qtiCreator/widgets/interactions/states/Question',
    'taoMediaManager/qtiCreator/editor/ckEditor/htmlEditor',
    'taoMediaManager/qtiCreator/editor/gridEditor/content',
    'i18n'
], function($, stateFactory, Question, htmlEditor, contentHelper, __){
    'use strict';

    var StimulusStateQuestion = stateFactory.extend(Question, function(){

        this.buildPromptEditor();

    }, function(){

        this.destroyPromptEditor();
    });

    StimulusStateQuestion.prototype.buildPromptEditor = function(){

        var _widget = this.widget,
            $editableContainer = _widget.$container.find('.qti-prompt-container'),
            $editable = $editableContainer.find('.qti-prompt'),
            container = _widget.element.prompt.getBody();

        //@todo set them in the tpl
        $editableContainer.attr('data-html-editable-container', true);
        $editable.attr('data-html-editable', true);

        if(!htmlEditor.hasEditor($editableContainer)){
            htmlEditor.buildEditor($editableContainer, {
                placeholder : __('define prompt'),
                change : contentHelper.getChangeCallback(container),
                data : {
                    container : container,
                    widget : _widget
                }
            });
        }
    };

    StimulusStateQuestion.prototype.destroyPromptEditor = function(){
        var $editableContainer = this.widget.$container.find('.qti-prompt-container');
        htmlEditor.destroyEditor($editableContainer);
    };

    return StimulusStateQuestion;
});
