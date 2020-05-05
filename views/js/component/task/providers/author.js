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
 * Copyright (c) 2020 Open Assessment Technologies SA ;
 */
/**
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */
define([
    'lodash',
    'i18n',
    'ui/form/widget/definitions',
    'taoMediaManager/component/authoring/itemAuthoring',
    // 'taoMediaManager/component/previewer/itemPreviewer'
], function (
    _,
    __,
    widgetDefinitions,
    itemAuthoringFactory,
    // itemPreviewerFactory
) {
    'use strict';

    return {
        init(config) {
            if (!config.title) {
                config.title = __('Author item');
            }
            return config;
        },
        getViewsConfig() {
            return {
                active: 'authoring',
                views: [{
                    id: 'authoring',
                    label: __('Author'),
                    factory: itemAuthoringFactory,
                    config: this.getConfig().authoring
                }, {
                    id: 'preview',
                    label: __('Preview'),
                    factory: itemPreviewerFactory,
                    config: Object.assign({volatile: true}, this.getConfig().preview)
                }]
            };
        },
        getFormConfig() {
            return {
                actionText: __('Submit item for review'),
                submitText: __('Submit Item for Review'),
                widgets: [{
                    widget: widgetDefinitions.TEXTAREA,
                    uri: 'comment',
                    label: __('Review comment'),
                    required: true
                }]
            };
        }
    };
});