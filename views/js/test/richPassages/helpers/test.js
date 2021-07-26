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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA ;
 */

/**
 * @author Hanna Dzmitryieva <hanna@taotesting.com>
 */
define([
    'jquery',
    'lodash',
    'taoMediaManager/richPassage/helpers',
    'json!taoMediaManager/test/richPassages/helpers/mock-itemData.json',
    'lib/jquery.mockjax/jquery.mockjax'
], function($, _, richPassages, itemData) {
    'use strict';

    // prevent the AJAX mocks to pollute the logs
    $.mockjaxSettings.logger = null;
    $.mockjaxSettings.responseTime = 1;

    // restore AJAX method after each test
    QUnit.testDone(function() {
        $.mockjax.clear();
    });

    const choices = itemData.content.data.body.elements.interaction_choiceinteraction_60f00a0734737232574522.choices;
    const choicesWithoutPassage = choices.choice_simplechoice_60f00a073b983946652886;
    const choicesWithPassage = choices.choice_simplechoice_60f00a0738a23321795333;
    const passage = choices.choice_simplechoice_60f00a0738a23321795333.body.elements;

    QUnit.module('API');

    QUnit.test('module', function(assert) {
        assert.expect(1);
        assert.equal(typeof richPassages, 'object', 'The module exposes an object');
    });

    QUnit.cases.init([
        {title: 'getPassagesFromElement'},
        {title: 'getPassagesFromItemData'},
        {title: 'injectPassagesStylesInItemData'},
        {title: 'checkAndInjectStylesInItemData'}
    ]).test('helper is defined ', function(data, assert) {
        assert.expect(1);
        assert.equal(typeof richPassages[data.title], 'function', `The helper ${data.title} is defined`);
    });

    QUnit.module('Behavior');

    QUnit.test('getPassagesFromElement', function(assert) {
        assert.expect(3);
        assert.deepEqual(richPassages.getPassagesFromElement(), {}, 'Without parameter, the helper returns an empty object');
        assert.deepEqual(richPassages.getPassagesFromElement(choicesWithoutPassage), {}, 'For element without passage return empty object');
        assert.deepEqual(richPassages.getPassagesFromElement(choicesWithPassage), choicesWithPassage.body.elements, 'For element with passage return include element');
    });

    QUnit.test('getPassagesFromItemData', function(assert) {
        assert.expect(2);
        assert.deepEqual(richPassages.getPassagesFromItemData(), {}, 'Without parameter, the helper returns an empty object');
        const passages = richPassages.getPassagesFromItemData(itemData);
        assert.equal(_.size(passages), 2, 'Found 2 passages');
    });

    QUnit.test('injectPassagesStylesInItemData', function(assert) {
        var ready = assert.async();
        assert.expect(3);

        // mock the AJAX request handling + response:
        $.mockjax({
            url: '*/getStylesheets',
            response: function(request) {
                assert.deepEqual(request.data, {uri: 'https://tao.docker.localhost/ontologies/tao.rdf#i60efee99dc8d4248f60586bdcf2dc3f5'}, 'The provider has sent the request');
                this.responseText = '{"success":true,"data":["tao-user-styles.css"]}';
            }
        });

        assert.ok(richPassages.injectPassagesStylesInItemData() instanceof Promise, 'The promise is provided');
        const itemDataClone = _.clone(itemData);
        richPassages.injectPassagesStylesInItemData(passage, itemDataClone)
            .then(data => {
                assert.equal(_.size(data.content.data.stylesheets), 2, 'Injected 1 stylesheet');
                ready();
            });
    });

    QUnit.test('checkAndInjectStylesInItemData', function(assert) {
        var ready = assert.async();
        assert.expect(2);

        // mock the AJAX request handling + response:
        $.mockjax({
            url: '*/getStylesheets',
            response: function() {
                this.responseText = '{"success":true,"data":["tao-user-styles.css"]}';
            }
        });
        assert.ok(richPassages.checkAndInjectStylesInItemData() instanceof Promise, 'The promise is provided');
        const itemDataClone = _.clone(itemData);
        richPassages.checkAndInjectStylesInItemData(itemDataClone)
            .then(data => {
                assert.equal(_.size(data.content.data.stylesheets), 3, 'Injected 2 stylesheets');
                ready();
            });
    });

});
