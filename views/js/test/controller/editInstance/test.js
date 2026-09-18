/* global requirejs */

define(['jquery'], function ($) {
    'use strict';

    const controllerModuleId = 'taoMediaManager/controller/editInstance';
    const contextModuleId = 'context';
    const binderModuleId = 'layout/actions/binder';
    const urlUtilModuleId = 'util/url';
    const inlinePreviewModuleId = 'taoItems/preview/inlinePropertiesPreview';
    const loggerModuleId = 'core/logger';
    const feedbackModuleId = 'ui/feedback';
    const loadingBarModuleId = 'layout/loading-bar';
    const legacyPreviewerModuleId = 'taoMediaManager/previewer/component/qtiSharedStimulusItem';
    const previewerModuleId = 'ui/previewer';

    let binderCalls;
    let contextValue;
    let inlinePreviewCalls;
    let legacyPreviewCalls;
    let feedbackErrors;
    let loadingBarCalls;
    let loggerErrors;
    let loggerNames;
    let previewerCalls;
    let originalLegacyPreviewerFactory;
    let originalUnderscore;

    function buildLegacyPreviewerFactory() {
        return function ($element, config) {
            const previewCall = {
                elementClass: $element.attr('class'),
                config,
                handlers: {}
            };
            legacyPreviewCalls.push(previewCall);

            return {
                on(eventName, handler) {
                    previewCall.handlers[eventName] = handler;
                    return this;
                }
            };
        };
    }

    function installDom(options) {
        const settings = Object.assign({
            includePreviewer: true,
            previewEnabled: true,
            mimeType: 'image/png',
            previewUrl: '/media/file.png',
            assetUri: 'asset-1'
        }, options || {});

        const previewerHtml = settings.includePreviewer
            ? `<div class="data-container-wrapper">
                    <div class="previewer" data-enabled="${settings.previewEnabled}" data-url="${settings.previewUrl}" data-type="${settings.mimeType}"></div>
               </div>`
            : '';

        $('#qunit-fixture').html(`
            <div class="main-container" data-mime-type="${settings.mimeType}"></div>
            ${previewerHtml}
            <div id="media-authoring" style="display:none"></div>
            <button id="edit-media" data-uri="${settings.assetUri}" data-classuri="class-1"></button>
        `);
    }

    function loadController() {
        requirejs.undef(controllerModuleId);
        requirejs.undef(contextModuleId);
        requirejs.undef(binderModuleId);
        requirejs.undef(urlUtilModuleId);
        requirejs.undef(inlinePreviewModuleId);
        requirejs.undef(loggerModuleId);
        requirejs.undef(feedbackModuleId);
        requirejs.undef(loadingBarModuleId);
        requirejs.undef(legacyPreviewerModuleId);
        requirejs.undef(previewerModuleId);

        define(contextModuleId, [], function () {
            return contextValue;
        });

        define(binderModuleId, [], function () {
            return {
                exec(action, context) {
                    binderCalls.push({ action, context });
                }
            };
        });

        define(urlUtilModuleId, [], function () {
            return {
                route(action, controller, extension) {
                    return `${extension}/${controller}/${action}`;
                }
            };
        });

        define(inlinePreviewModuleId, [], function () {
            return {
                init(config) {
                    inlinePreviewCalls.push(config);
                }
            };
        });

        define(loggerModuleId, [], function () {
            return function (name) {
                loggerNames.push(name);

                return {
                    error(err) {
                        loggerErrors.push(err);
                    }
                };
            };
        });

        define(feedbackModuleId, [], function () {
            return function () {
                return {
                    error(message) {
                        feedbackErrors.push(message);
                    }
                };
            };
        });

        define(loadingBarModuleId, [], function () {
            return {
                start() {
                    loadingBarCalls.push('start');
                },
                stop() {
                    loadingBarCalls.push('stop');
                }
            };
        });

        define(legacyPreviewerModuleId, [], function () {
            return window.qtiItemPreviewerFactory;
        });

        define(previewerModuleId, ['jquery'], function (jquery) {
            jquery.fn.previewer = function (file) {
                previewerCalls.push({
                    elementClass: this.attr('class'),
                    file
                });

                return this;
            };

            return function () {};
        });

        return new Promise((resolve, reject) => {
            require([controllerModuleId], resolve, reject);
        });
    }

    QUnit.module('taoMediaManager/controller/editInstance', {
        beforeEach() {
            binderCalls = [];
            contextValue = {
                previewerExternalFeUrl: 'https://previewer.example.com',
                featureFlags: {
                    FEATURE_FLAG_TAO_ADVANCE_EXTERNAL_ITEM_PREVIEWER: true,
                    FEATURE_FLAG_TAO_CG_ONLY: false
                }
            };
            feedbackErrors = [];
            inlinePreviewCalls = [];
            legacyPreviewCalls = [];
            loadingBarCalls = [];
            loggerErrors = [];
            loggerNames = [];
            previewerCalls = [];
            originalLegacyPreviewerFactory = window.qtiItemPreviewerFactory;
            originalUnderscore = window._;
            window.qtiItemPreviewerFactory = buildLegacyPreviewerFactory();
            window._ = {
                isUndefined(value) {
                    return typeof value === 'undefined';
                }
            };
            delete $.fn.previewer;
        },
        afterEach() {
            $('#qunit-fixture').empty();
            delete $.fn.previewer;
            window.qtiItemPreviewerFactory = originalLegacyPreviewerFactory;
            window._ = originalUnderscore;
            requirejs.undef(controllerModuleId);
            requirejs.undef(contextModuleId);
            requirejs.undef(binderModuleId);
            requirejs.undef(urlUtilModuleId);
            requirejs.undef(inlinePreviewModuleId);
            requirejs.undef(loggerModuleId);
            requirejs.undef(feedbackModuleId);
            requirejs.undef(loadingBarModuleId);
            requirejs.undef(legacyPreviewerModuleId);
            requirejs.undef(previewerModuleId);
        }
    });

    QUnit.test('passage without preview markup does not initialize preview and still shows authoring', function (assert) {
        const done = assert.async();

        installDom({
            includePreviewer: false,
            mimeType: 'application/qti+xml'
        });

        loadController()
            .then(controller => {
                controller.start();

                assert.deepEqual(inlinePreviewCalls, [], 'Inline preview is skipped when the preview div is absent');
                assert.deepEqual(previewerCalls, [], 'Media preview plugin is not called when the preview div is absent');
                assert.notStrictEqual($('#media-authoring').css('display'), 'none', 'Passage authoring remains visible');

                $('#edit-media').trigger('click');
                assert.deepEqual(binderCalls, [{
                    action: {
                        binding: 'load',
                        url: 'taoMediaManager/MediaImport/editMedia'
                    },
                    context: {
                        classUri: 'class-1',
                        id: 'asset-1'
                    }
                }], 'Edit button remains wired when preview markup is absent');
                done();
            })
            .catch(err => {
                assert.pushResult({
                    result: false,
                    actual: err,
                    expected: 'no error',
                    message: err && err.message ? err.message : 'The controller should not throw'
                });
                done();
            });
    });

    QUnit.test('passage with preview markup initializes inline preview', function (assert) {
        const done = assert.async();

        installDom({
            includePreviewer: true,
            mimeType: 'application/qti+xml',
            previewEnabled: true,
            assetUri: 'passage-1'
        });

        loadController()
            .then(controller => {
                controller.start();

                assert.deepEqual(inlinePreviewCalls, [{
                    isPreviewEnabled: true,
                    itemUri: 'passage-1'
                }], 'Inline preview receives the passage uri');
                assert.deepEqual(previewerCalls, [], 'Media preview plugin is not used for passages');
                assert.strictEqual($('.previewer').attr('id'), 'item-properties-preview', 'Passage preview gets the inline preview container id');
                assert.strictEqual($('.data-container-wrapper').attr('id'), 'item-properties-preview-column', 'Passage preview column gets the inline preview column id');
                done();
            })
            .catch(err => {
                assert.pushResult({
                    result: false,
                    actual: err,
                    expected: 'no error',
                    message: err && err.message ? err.message : 'The controller should not throw'
                });
                done();
            });
    });

    QUnit.test('passage with external preview disabled falls back to the legacy previewer', function (assert) {
        const done = assert.async();

        contextValue = {
            previewerExternalFeUrl: 'https://previewer.example.com',
            featureFlags: {
                FEATURE_FLAG_TAO_ADVANCE_EXTERNAL_ITEM_PREVIEWER: false,
                FEATURE_FLAG_TAO_CG_ONLY: false
            }
        };

        installDom({
            includePreviewer: true,
            mimeType: 'application/qti+xml',
            previewEnabled: true,
            assetUri: 'passage-1'
        });

        loadController()
            .then(controller => {
                controller.start();

                assert.deepEqual(inlinePreviewCalls, [], 'Inline preview is skipped when the external previewer is disabled');
                assert.deepEqual(previewerCalls, [], 'Media preview plugin is not used for passages');
                assert.strictEqual(legacyPreviewCalls.length, 1, 'Legacy passage preview is initialized');
                assert.deepEqual(loggerNames, ['taoMediaManager/editInstance'], 'Logger factory is initialized for this controller');
                assert.deepEqual(legacyPreviewCalls[0].config, {
                    itemUri: 'passage-1'
                }, 'Legacy preview receives the passage uri');
                assert.strictEqual(legacyPreviewCalls[0].elementClass, 'previewer', 'Legacy preview uses the preview container');
                assert.deepEqual(loadingBarCalls, ['start'], 'Loading bar starts while the legacy preview loads');
                assert.notOk($('.previewer').attr('id'), 'Passage preview keeps its original container id');
                assert.notOk($('.data-container-wrapper').attr('id'), 'Passage preview column keeps its original id');
                assert.notStrictEqual($('#media-authoring').css('display'), 'none', 'Passage authoring remains visible');

                legacyPreviewCalls[0].handlers.error({ message: 'Preview failed' });
                assert.deepEqual(feedbackErrors, ['Preview failed'], 'Preview errors are shown to the user');
                assert.deepEqual(loggerErrors, [{ message: 'Preview failed' }], 'Preview errors are logged');

                legacyPreviewCalls[0].handlers['preview-loaded']();
                assert.deepEqual(loadingBarCalls, ['start', 'stop'], 'Loading bar stops when the legacy preview finishes');
                done();
            })
            .catch(err => {
                assert.pushResult({
                    result: false,
                    actual: err,
                    expected: 'no error',
                    message: err && err.message ? err.message : 'The controller should not throw'
                });
                done();
            });
    });

    QUnit.test('non-passage preview uses the media preview plugin', function (assert) {
        const done = assert.async();

        installDom({
            includePreviewer: true,
            mimeType: 'image/png',
            previewEnabled: true,
            previewUrl: '/media/image.png'
        });

        loadController()
            .then(controller => {
                controller.start();

                assert.deepEqual(inlinePreviewCalls, [], 'Inline preview is not used for non-passages');
                assert.deepEqual(previewerCalls, [{
                    elementClass: 'previewer',
                    file: {
                        url: '/media/image.png',
                        mime: 'image/png',
                        containerClass: 'no-background'
                    }
                }], 'Media preview plugin receives the expected preview configuration');
                assert.strictEqual($('#media-authoring').css('display'), 'none', 'Authoring stays hidden for non-passages');
                done();
            })
            .catch(err => {
                assert.pushResult({
                    result: false,
                    actual: err,
                    expected: 'no error',
                    message: err && err.message ? err.message : 'The controller should not throw'
                });
                done();
            });
    });
});
