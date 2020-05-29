
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
    'layout/actions/binder',
    'uri',
    'layout/section',
    'core/request',
    'core/router',
], function($, __, binder, uri, section, request, router) {

    binder.register('newPassage', function instanciate(actionContext) {
        const self = this;
        const classUri = uri.decode(actionContext.id);

        return request({
            url: self.url,
            method: "POST",
            data: { classId: classUri },
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
    binder.register('passageAuthoring', function passageAuthoring(actionContext) {
        section.create({
            id : 'authoring',
            name : __('Authoring'),
            url : this.url,
            content : ' ',
            visible : false
        }).show();
        const $panel = $('#panel-authoring');
        $panel.attr('data-id', actionContext.id);
        $panel.attr('data-uri', actionContext.uri);
        // duplicate behaviour of $doc.ajaxComplete in tao/views/js/controller/backoffice.js
        // as in old way - request html from server
        router.dispatch(`${this.url}?id=${actionContext.id}`);
    });
});
