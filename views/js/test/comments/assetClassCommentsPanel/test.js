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
 * Foundation, Inc., 31 Milk St # 960789 Boston, MA 02196 USA
 *
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */
define(['jquery', 'taoMediaManager/comments/assetClassCommentsPanel'], function ($, assetCommentsPanel) {
    'use strict';

    function createContainer(assetUri) {
        const $container = $([
            '<div class="content-container">',
            '  <div class="main-container flex-container-main-form"></div>',
            '</div>'
        ].join(''));

        if (typeof assetUri === 'string') {
            $container.find('.main-container').attr('data-asset-uri', assetUri);
        }

        $('#qunit-fixture').append($container);

        return $container;
    }

    function createStoreStub(overrides) {
        return Object.assign(
            {
                load: function () {
                    return Promise.resolve(this);
                },
                setResourceUri: function () {
                    return this;
                }
            },
            overrides || {}
        );
    }

    function createPanelStub(overrides) {
        return Object.assign(
            {
                refresh: function () {}
            },
            overrides || {}
        );
    }

    function initWithStubs($container, store, panel) {
        return assetCommentsPanel.init({
            reset: true,
            $container: $container,
            storeFactory: function () {
                return store;
            },
            panelFactory: function () {
                return panel;
            }
        });
    }

    QUnit.module('API');

    QUnit.test('module exposes init', function (assert) {
        assert.expect(2);
        assert.equal(typeof assetCommentsPanel, 'object', 'module is an object');
        assert.equal(typeof assetCommentsPanel.init, 'function', 'init is a function');
    });

    QUnit.test('init returns null without asset URI', function (assert) {
        const $container = createContainer();
        const result = assetCommentsPanel.init({
            reset: true,
            $container: $container,
            storeFactory: function () {
                assert.ok(false, 'store must not be created without asset URI');
            },
            panelFactory: function () {
                assert.ok(false, 'panel must not be created without asset URI');
            }
        });

        assert.expect(2);
        assert.strictEqual(result, null, 'requires asset URI');
        assert.strictEqual($container.find('#asset-comments-side-panel').length, 0, 'does not create panel markup');
    });

    QUnit.test('init returns null without main container', function (assert) {
        const $container = $('<div class="content-container"></div>');
        $('#qunit-fixture').append($container);

        assert.expect(1);
        assert.strictEqual(
            assetCommentsPanel.init({
                reset: true,
                $container: $container
            }),
            null,
            'requires main container'
        );
    });

    QUnit.test('init creates panel and loads asset comments', function (assert) {
        const $container = createContainer('urn:asset:123');
        let storeFactoryConfig = null;
        let panelFactoryConfig = null;
        let loadCalled = false;
        const store = createStoreStub({
            load: function () {
                loadCalled = true;
                return Promise.resolve(this);
            }
        });
        const panel = createPanelStub();

        const component = assetCommentsPanel.init({
            reset: true,
            $container: $container,
            storeFactory: function (config) {
                storeFactoryConfig = config;
                return store;
            },
            panelFactory: function (config) {
                panelFactoryConfig = config;
                return panel;
            }
        });

        assert.expect(8);
        assert.ok(component, 'returns component API');
        assert.strictEqual(component.store, store, 'returns created store');
        assert.strictEqual(component.panel, panel, 'returns created panel');
        assert.strictEqual(storeFactoryConfig.resourceUri, 'urn:asset:123', 'passes asset uri to store');
        assert.strictEqual(storeFactoryConfig.resourceType, 'asset', 'passes ASSET resource type');
        assert.strictEqual(panelFactoryConfig.store, store, 'passes store to panel factory');
        assert.ok(loadCalled, 'starts store load during init');
        assert.strictEqual($container.find('#asset-comments-side-panel').length, 1, 'appends side panel');
    });

    QUnit.test('second init with a different asset URI updates the store', function (assert) {
        const $container = createContainer('urn:asset:first');
        let setResourceUriValue = null;
        let refreshCalls = 0;
        const store = createStoreStub({
            setResourceUri: function (uri) {
                setResourceUriValue = uri;
                return this;
            }
        });
        const panel = createPanelStub({
            refresh: function () {
                refreshCalls += 1;
            }
        });

        const first = initWithStubs($container, store, panel);
        $container.find('.main-container').data('asset-uri', 'urn:asset:second');

        const second = assetCommentsPanel.init({
            $container: $container,
            storeFactory: function () {
                assert.ok(false, 'must reuse the existing store');
            },
            panelFactory: function () {
                assert.ok(false, 'must reuse the existing panel');
            }
        });

        assert.expect(5);
        assert.strictEqual(first.store, store, 'first init creates store');
        assert.strictEqual(second.store, store, 'second init reuses store');
        assert.strictEqual(second.panel, panel, 'second init reuses panel');
        assert.strictEqual(setResourceUriValue, 'urn:asset:second', 'updates store resource URI');
        assert.strictEqual(refreshCalls, 1, 'refreshes existing panel');
    });

    QUnit.test('init recreates panel when the form host is replaced', function (assert) {
        assert.expect(5);
        const $first = createContainer('urn:asset:first');
        const firstStore = createStoreStub();
        const firstPanel = createPanelStub({
            destroy: function () {
                this.destroyed = true;
            }
        });
        const secondStore = createStoreStub();
        const secondPanel = createPanelStub();
        let storeFactoryCalls = 0;

        assetCommentsPanel.init({
            reset: true,
            $container: $first,
            storeFactory: function () {
                storeFactoryCalls += 1;
                return firstStore;
            },
            panelFactory: function () {
                return firstPanel;
            }
        });

        const $second = createContainer('urn:asset:replaced');
        const component = assetCommentsPanel.init({
            $container: $second,
            storeFactory: function (config) {
                storeFactoryCalls += 1;
                assert.strictEqual(config.resourceUri, 'urn:asset:replaced', 'creates store for the new asset');
                return secondStore;
            },
            panelFactory: function () {
                return secondPanel;
            }
        });

        assert.strictEqual(storeFactoryCalls, 2, 'creates a new store for the replaced host');
        assert.strictEqual(component.store, secondStore, 'returns the new store');
        assert.strictEqual(component.panel, secondPanel, 'returns the new panel');
        assert.strictEqual(firstPanel.destroyed, true, 'destroys the detached panel');
    });
});
