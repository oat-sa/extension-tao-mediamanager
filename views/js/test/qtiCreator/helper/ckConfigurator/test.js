define([
    'jquery',
    'taoMediaManager/qtiCreator/helper/ckConfigurator',
    'ckeditor',
    'css!ckeditor/skins/tao/editor'
], function($, ckConfigurator, ckEditor) {
    'use strict';

    const editor = ckEditor.replace('editor1');
    editor.config = ckConfigurator.getConfig(editor);

    QUnit.module('API');

    QUnit.test('module', assert => {
        const ready = assert.async();
        assert.expect(2);
        assert.equal(typeof editor.config, 'object', 'ckConfigurator.getConfig generate an object');
        assert.equal(editor.config.removePlugins, 'taoqtiinclude', 'plugin taoqtiinclude was removed');
        ready();
    });

    QUnit.module('Visual Test');

    QUnit.test('Display and play', assert => {
        const ready = assert.async();
        assert.expect(1);

        assert.ok(true);
        
        ready();
    });
});
