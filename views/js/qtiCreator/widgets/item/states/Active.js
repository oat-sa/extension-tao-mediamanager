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
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA ;
 *
 */

define([
    'lodash',
    'util/locale',
    'taoQtiItem/qtiCreator/widgets/states/factory',
    'taoMediaManager/qtiCreator/widgets/states/Active',
    'tpl!taoMediaManager/qtiCreator/tpl/forms/item',
    'taoQtiItem/qtiCreator/widgets/helpers/formElement',
    'taoQtiItem/qtiCreator/editor/gridEditor/content',
    'select2'
], function(_, locale, stateFactory, Active, formTpl, formElement, contentHelper){
    'use strict';

    const ItemStateActive = stateFactory.create(Active, function enterActiveState() {
        const _widget = this.widget;
        const item = _widget.element;
        const $form = _widget.$form;
        const rtl = locale.getConfig().rtl || [];
        //build form:
        $form.html(formTpl({
            'xml:lang' : item.attr('xml:lang'),
            languagesList : item.data('languagesList'),
            rtl
        }));

        //init widget
        formElement.initWidget($form);

        //init data validation and binding
        formElement.setChangeCallbacks($form, item, {
            'xml:lang' : function langChange(i, lang){
                item.attr('xml:lang', lang);
                const $itemBody = _widget.$container.find('.qti-itemBody');
                if (rtl.includes(lang)) {
                    item.attr('dir', 'rtl');
                    $itemBody.find('.grid-row').attr('dir', 'rtl');
                } else {
                    item.removeAttr('dir');
                    $itemBody.find('.grid-row').removeAttr('dir');
                }
                //need to update item body
                item.body(contentHelper.getContent($itemBody));
            }
        });

        const $selectBox = $form.find('select');

        $selectBox.select2({
            dropdownAutoWidth: true,
            width: 'resolve',
            minimumResultsForSearch: -1,
            formatSelection: data => {
                if (data.css) {
                    return `<span class="${data.css}">${data.text}</span>`;
                }
                return data.text;
            }
        });

        // set dir='rtl' if 'xml:lang' in rtl array
        if (rtl.includes(item.attr('xml:lang'))) {
            item.attr('dir', 'rtl');
        }

    }, _.noop);

    return ItemStateActive;
});
