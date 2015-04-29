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
], function($, __, module, helpers, binder, uri) {
    'use strict';

    var manageMediaController =  {

        /**
         * Controller entry point
         */
        start : function(){

            $('#edit-media').off()
                .on('click', function(){
                    var action = {binding : "load", url: helpers._url('editMedia', 'MediaImport', 'taoMediaManager')};
                    binder.exec(action, {classUri : $(this).data('classuri'), id : $(this).data('uri')} || this._resourceContext);
                });
        }
    };

    return manageMediaController;
});
