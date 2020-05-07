/**
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
define([
    'jquery',
    'i18n',
    'module',
    'helpers',
    'layout/actions/binder',
    'uri',
    'ui/previewer'
], function($, __, module, helpers, binder, uri) {
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

            $('#edit-media').off()
                .on('click', function(){
                    var action = {binding : "load", url: helpers._url('editMedia', 'MediaImport', 'taoMediaManager')};
                    binder.exec(action, {classUri : $(this).data('classuri'), id : $(this).data('uri')} || this._resourceContext);
                });

            binder.register('newPassage', function instanciate(actionContext){
                var self = this;
                var classUri = uri.decode(actionContext.classUri);
                var signature = actionContext.signature;
                if (actionContext.type !== 'class') {
                    signature = actionContext.classSignature;
                }
                // Remove when implement
                console.log('Pending to be implemented, URI: ', actionContext.uri);
                if(actionContext.tree){
                    $(actionContext.tree).trigger('addnode.taotree', [{
                        uri       : uri.decode(actionContext.uri),
                        label     : actionContext.label,
                        parent    : uri.decode(actionContext.classUri),
                        cssClass  : 'node-instance'
                    }]);
                }
                // Remove top when implement

                // return request({
                //     url: self.url,
                //     method: "POST",
                //     data: {id: classUri, type: 'instance', signature: signature},
                //     dataType: 'json'
                // })
                //     .then(function(response) {
                //         if (response.success && response.uri) {
                //             //backward compat format for jstree
                //             if(actionContext.tree){
                //                 $(actionContext.tree).trigger('addnode.taotree', [{
                //                     uri       : uri.decode(response.uri),
                //                     label     : response.label,
                //                     parent    : uri.decode(actionContext.classUri),
                //                     cssClass  : 'node-instance'
                //                 }]);
                //             }
                //
                //             //return format (resourceSelector)
                //             return {
                //                 uri       : uri.decode(response.uri),
                //                 label     : response.label,
                //                 classUri  : uri.decode(actionContext.classUri),
                //                 type      : 'instance'
                //             };
                //
                //         } else {
                //             throw new Error(__('Adding the new resource has failed'));
                //         }
                //     });
            });
        }
    };

    return manageMediaController;
});
