
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
    'lodash',
    'jquery',
    'i18n',
    'layout/actions/binder',
    'uri',
    'layout/section',
    'core/dataProvider/request',
    'core/router',
    'core/logger',
    'ui/feedback',
], function(_, $, __, binder, uri, section, request, router, loggerFactory, feedback) {

    const logger = loggerFactory('taoMediaManager/manageMedia');

    binder.register('newPassage', function instanciate(actionContext) {
        const self = this;
        const classUri = uri.decode(actionContext.id);

        return request(self.url, { classId: classUri }, "POST")
            .then(function(data) {
                //backward compat format for jstree
                if(actionContext.tree){
                    $(actionContext.tree).trigger('addnode.taotree', [{
                        uri       : uri.decode(data.id),
                        label     : data.name,
                        parent    : uri.decode(actionContext.classUri),
                        cssClass  : 'node-instance'
                    }]);
                }

                //return format (resourceSelector)
                return {
                    uri       : uri.decode(data.id),
                    label     : data.name,
                    classUri  : uri.decode(actionContext.classUri),
                    type      : 'instance'
                };
            })
            .catch(err => {
                if (!_.isUndefined(err.message)) {
                    feedback().error(err.message);
                }
                logger.error(err);
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
