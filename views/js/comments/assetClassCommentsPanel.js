define([
    'jquery',
    'lodash',
    'i18n',
    'taoItems/services/itemComments',
    'taoItems/comments/itemCommentsStore',
    'taoItems/comments/commentsPanel'
], function ($, _, __, itemCommentsApi, itemCommentsStoreFactory, commentsPanelFactory) {
    'use strict';

    const PANEL_ID = 'asset-comments-side-panel';
    let commentsPanel = null;
    let commentsStore = null;
    let commentsHostEl = null;

    function moveEntryFormToDedicatedSlot($panel) {
        const $entrySlot = $panel.find('.asset-comments-entry-slot');
        const $entryForm = $panel.find('.item-comments-entry');

        if (!$entrySlot.length || !$entryForm.length) {
            return;
        }

        if (!$entryForm.parent().is($entrySlot)) {
            $entrySlot.append($entryForm);
        }
    }

    function createPanelMarkup() {
        return [
            '<aside id="' + PANEL_ID + '" class="asset-comments-side-panel">',
            '  <header class="section-header asset-comments-header">',
            '    <h2><span class="icon-speech-bubble" aria-hidden="true"></span> ' + __('Comments') + '</h2>',
            '  </header>',
            '  <div class="asset-comments-side-panel-body">',
            '    <p class="asset-comments-guidance">' + __('Comments apply to this asset.') + '</p>',
            '    <div class="asset-comments-content-shell">',
            '      <div class="asset-comments-content-panel"></div>',
            '      <div class="asset-comments-entry-slot"></div>',
            '    </div>',
            '  </div>',
            '</aside>'
        ].join('');
    }

    return {
        /**
         * @param {object} [options]
         * @param {jQuery|HTMLElement} [options.$container]
         * @param {Function} [options.storeFactory]
         * @param {Function} [options.panelFactory]
         * @param {boolean} [options.reset]
         * @returns {{panel: object, store: object}|null}
         */
        init: function init(options) {
            options = options || {};

            if (options.reset) {
                commentsPanel = null;
                commentsStore = null;
                commentsHostEl = null;
            }

            const storeFactory = options.storeFactory || itemCommentsStoreFactory;
            const panelFactory = options.panelFactory || commentsPanelFactory;
            const $root = options.$container ? $(options.$container) : $(document);
            const $mainContainer = $root.find('.main-container.flex-container-main-form');
            const assetUri = ($mainContainer.data('asset-uri') || '').toString();

            if (!$mainContainer.length || !assetUri) {
                return null;
            }

            const $contentContainer = $mainContainer.closest('.content-container');
            if (!$contentContainer.length) {
                return null;
            }

            let $panel = $contentContainer.find('#' + PANEL_ID);
            if (!$panel.length) {
                $panel = $(createPanelMarkup());
                $contentContainer.append($panel);
            }

            $contentContainer.addClass('asset-comments-layout');

            const $contentHost = $panel.find('.asset-comments-content-panel');
            const hostEl = $contentHost.get(0);
            const shouldCreate = !commentsPanel || commentsHostEl !== hostEl;

            if (shouldCreate) {
                if (commentsPanel && typeof commentsPanel.destroy === 'function') {
                    commentsPanel.destroy();
                }

                commentsStore = storeFactory({
                    resourceUri: assetUri,
                    resourceType: itemCommentsApi.RESOURCE_TYPE.ASSET
                });

                commentsPanel = panelFactory({
                    renderTo: $contentHost,
                    store: commentsStore
                });
                commentsHostEl = hostEl;

                moveEntryFormToDedicatedSlot($panel);
                commentsStore.load().catch(_.noop);
            } else {
                commentsStore.setResourceUri(assetUri);
                commentsPanel.refresh();
                moveEntryFormToDedicatedSlot($panel);
            }

            return {
                panel: commentsPanel,
                store: commentsStore
            };
        }
    };
});
