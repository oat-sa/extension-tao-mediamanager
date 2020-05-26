
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
    'core/request',
    'core/router',
    'uikitLoader'
], function($, __, module, helpers, binder, uri, previewer, section, passageAuthoringFactory, request, router, uikitLoader) {
    'use strict';

    var manageMediaController =  {

        /**
         * Controller entry point
         */
        start : function(){

            var $previewer = $('.previewer');
            var file = {};
            file.url = $previewer.data('url');
            file.mime = $previewer.data('type');

            if(!$previewer.data('xml')){
                $previewer.previewer(file);
            }
            else{
                $.ajax({
                    url: file.url,
                    data: {xml:true},
                    method: "POST",
                }).success(function(response){
                    file.xml = response;
                    $previewer.previewer(file);
                });
            }

            if (file.mime !== 'application/qti+xml') {
                $('#media-authoring').hide();
            } else {
                $('#media-authoring').show();
            }

            $('#edit-media').off()
                .on('click', function(){
                    var action = {binding : "load", url: helpers._url('editMedia', 'MediaImport', 'taoMediaManager')};
                    binder.exec(action, {classUri : $(this).data('classuri'), id : $(this).data('uri')} || this._resourceContext);
                });

            binder.register('passageAuthoring', function passageAuthoring(actionContext){
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
                // diplicate behaviour of $doc.ajaxComplete in tao/views/js/controller/backoffice.js
                // as in old way - request html from server
                router.dispatch(`${this.url}?id=${actionContext.id}`);
            });
        }
    };

    return manageMediaController;
});
