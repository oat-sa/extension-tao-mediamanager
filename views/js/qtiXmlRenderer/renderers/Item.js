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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */

define(['lodash', 'tpl!taoMediaManager/qtiXmlRenderer/tpl/item'], function(_, tpl){
    'use strict';

    return {
        qtiClass : 'assessmentItem',
        template : tpl,
        getData : function getData(item, data){
            const defaultData = {
                'class' : data.attributes.class || '',
                namespaces : item.getNamespaces(),
                schemaLocations : '',
                xsi: 'xsi:',//the standard namespace prefix for xml schema
                empty : item.isEmpty(),
            };

            _.forIn(item.getSchemaLocations(), (url, uri) => {
                defaultData.schemaLocations += `${uri} ${url} `;
            });
            defaultData.schemaLocations = defaultData.schemaLocations.trim();

            data = _.merge({}, data || {}, defaultData);
            delete data.attributes.class;
            delete data.attributes.title;
            delete data.attributes.adaptive;
            delete data.attributes.timeDependent;
            delete data.attributes.identifier;

            data.attributes = _.mapValues(data.attributes, function (val) {
                return _.isString(val) ? _.escape(val) : val;
            });

            return data;
        }
    };
});
