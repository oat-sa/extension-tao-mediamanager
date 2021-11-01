
/*
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
 *
 */

/**
 * Test the blockAdder
 *
 * @author Hanna Dzmitryieva <hanna@taotesting.com>
 */
define([
    'jquery',
    'taoMediaManager/qtiCreator/editor/blockAdder/blockAdder',
    'taoMediaManager/qtiCreator/helper/creatorRenderer',
    'taoQtiItem/test/qtiCreator/mocks/areaBrokerMock'
], function($, blockAdder, creatorRenderer, areaBrokerMock) {
    'use strict';

    QUnit.module('API');

    QUnit.test('factory', function(assert) {

        assert.expect(2);
        assert.equal(typeof blockAdder, 'object', 'The module exposes a object');
        assert.equal(typeof blockAdder.create, 'function', 'The module has an create method');
    });

    QUnit.module('Behaviour');
    QUnit.test('blockAdder adds plus button after block', function(assert) {
        assert.expect(1);
        const mockItem = {
            body(body) {
                this.body = body;
            }
        };
        const $editorPanel = $('#item-editor-panel');
        blockAdder.create(mockItem, $editorPanel);
        assert.equal($editorPanel.find('.add-block-element').length, 1, 'The plus button was added');
    });
    QUnit.test('click on plus button will add new block', function(assert) {
        assert.expect(2);
        const mockItem = {
            body(body) {
                this.body = body;
            }
        };
        const $editorPanel = $('#item-editor-panel');
        $('.qti-item').data('widget', {
            element: {
                getBody() {
                    return {
                        attributes: {},
                        bdy: '<div class="grid-row"><div class="col-12">{{_container_5xs4s7yselqnqnqr8v4vw4}}</div></div>',
                        contentModel: 'itemBody',
                        elements: {'_container_5xs4s7yselqnqnqr8v4vw4': {}},
                        serial: '_container_h9t8c3trxkae0d5xdnc2sj',
                        getRenderer() {
                            return {
                                isRenderer: true,
                                getCreatorContext() {
                                    return {
                                        trigger() {}
                                    };
                                }
                            };
                        },
                        setElements() {},
                        parent() {},
                        getUsedClasses() {
                            return ['_container'];
                        }
                    };
                }
            }
        });
        creatorRenderer.get(true, { properties: {}}, areaBrokerMock());

        blockAdder.create(mockItem, $editorPanel);
        const $plusButton = $editorPanel.find('.add-block-element');
        assert.equal($editorPanel.find('.colrow').length, 0, 'Initially 0 .colrow in item');
        $plusButton.click();
        assert.equal($editorPanel.find('.colrow').length, 2, 'Added new .colrow');
    });
});
