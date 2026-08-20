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
        init: function init() {
            const $mainContainer = $('.main-container.flex-container-main-form');
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
            if (!commentsPanel) {
                commentsStore = itemCommentsStoreFactory({
                    resourceUri: assetUri,
                    resourceType: itemCommentsApi.RESOURCE_TYPE.ASSET
                });

                commentsPanel = commentsPanelFactory({
                    renderTo: $contentHost,
                    store: commentsStore
                });

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
