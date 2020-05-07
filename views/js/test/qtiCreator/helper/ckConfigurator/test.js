define([
    'jquery',
    'taoMediaManager/qtiCreator/helper/ckConfigurator',
    'ckeditor',
    'css!ckeditor/skins/tao/editor'
], function($, ckConfigurator, ckEditor) {
    'use strict';

    var runner;
    var fixtureContainerId = 'item-container-';


    QUnit.module('Visual Test');

    QUnit.test('Display and play', function(assert) {
        var ready = assert.async();
        assert.expect(1);

        var $container = $('#outside-container');

        assert.equal($container.length, 1, 'the item container exists');
        var editor = ckEditor.replace('editor1');
        editor.config = ckConfigurator.getConfig(editor, 'qtiInline', {resize_enabled : false });
        ready();
    });
});
