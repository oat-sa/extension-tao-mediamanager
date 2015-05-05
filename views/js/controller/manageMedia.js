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
            $('#edit-media').off()
                .on('click', function(){
                    var action = {binding : "load", url: helpers._url('editMedia', 'MediaImport', 'taoMediaManager')};
                    binder.exec(action, {classUri : $(this).data('classuri'), id : $(this).data('uri')} || this._resourceContext);
                });

            var file = {};
            file.url = $previewer.data('url');
            file.mime = $previewer.data('type');

            $.ajax({
                url: file.url,
                method: "POST",
                datatype: "xml"
            }).success(function(response){
                if(response instanceof Node){
                    file.xml = new XMLSerializer().serializeToString(response);
                }
                $previewer.previewer(file);
            });
        }
    };

    return manageMediaController;
});
