define(['jquery', 'ui', 'ui/uploader', 'ui/feedback'], function($, ui, uploader, feedback){
    var container = $('#upload-container');
    var altForm = $('#alt-form');


    $('select',altForm).on('change', function(){
        container.uploader('options', {
            uploadUrl : container.data('url')+'?'+altForm.serialize()
        });
    });

    container.uploader({
        uploadUrl   : container.data('url')+'?'+altForm.serialize()
    });

    container.on('upload.uploader', function(e, file, interactionHook){
        feedback().success(interactionHook.success);
    });

    container.on('fail.uploader', function(e, file, interactionHook){
        feedback().error(interactionHook.error);
    });
});
