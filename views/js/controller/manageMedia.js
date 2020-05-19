
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
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */
define([
    'jquery',
    'i18n',
    'module',
    'helpers',
    'layout/actions/binder',
    'uri',
    'ui/previewer',
    'layout/section',
    'taoMediaManager/qtiCreator/component/passageAuthoring',
    'core/request'
], function($, __, module, helpers, binder, uri, previewer, section, passageAuthoringFactory, request) {
    'use strict';

    var manageMediaController =  {

        /**
         * Controller entry point
         */
        start : function(){
            binder.register('newPassage', function instanciate(actionContext){
                var self = this;
                var classUri = uri.decode(actionContext.classUri);
                var signature = actionContext.signature;
                if (actionContext.type !== 'class') {
                    signature = actionContext.classSignature;
                }

                return request({
                    url: self.url,
                    method: "POST",
                    data: { classId: classUri, type: 'instance', signature: signature },
                    dataType: 'json'
                })
                .then(function(response) {
                    if (response.success && response.data) {
                        //backward compat format for jstree
                        if(actionContext.tree){
                            $(actionContext.tree).trigger('addnode.taotree', [{
                                uri       : uri.decode(response.data.id),
                                label     : response.data.name,
                                parent    : uri.decode(actionContext.classUri),
                                cssClass  : 'node-instance'
                            }]);
                        }

                        //return format (resourceSelector)
                        return {
                            uri       : uri.decode(response.data.id),
                            label     : response.data.name,
                            classUri  : uri.decode(actionContext.classUri),
                            type      : 'instance'
                        };

                    } else {
                        throw new Error(__('Adding the new resource has failed'));
                    }
                });
            });
        }
    };

    return manageMediaController;
});
