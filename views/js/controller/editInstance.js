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
 * Copyright (c) 2020-2026 (original work) Open Assessment Technologies SA ;
 */

/**
 * Side-effect import: registers $.fn.previewer
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */
define([
    'jquery',
    'context',
    'layout/actions/binder',
    'util/url',
    'taoItems/preview/inlinePropertiesPreview',
    'taoMediaManager/previewer/component/qtiSharedStimulusItem',
    'core/logger',
    'ui/feedback',
    'layout/loading-bar',
    'ui/previewer'
], function($, context, binder, urlUtil, inlinePropertiesPreview, qtiItemPreviewerFactory, loggerFactory, feedback, loadingBar) {
    'use strict';

    const logger = loggerFactory('taoMediaManager/editInstance');

    const isExternalPreviewerAvailable = () => !!(
        context &&
        context.previewerExternalFeUrl &&
        context.featureFlags &&
        context.featureFlags.FEATURE_FLAG_TAO_ADVANCE_EXTERNAL_ITEM_PREVIEWER &&
        !context.featureFlags.FEATURE_FLAG_TAO_CG_ONLY
    );

    const initPreview = isPassage => {
        const $previewer = $('.previewer');
        const isPreviewEnabled = $previewer.data('enabled');
        if (!isPreviewEnabled) return;

        if (!isPassage) {
            const file = {};
            file.url = $previewer.data('url');
            file.mime = $previewer.data('type');
            // to hide the loading icon, inherited from the .previewer
            file.containerClass = 'no-background';
            $previewer.previewer(file);
            return;
        }

        if (isExternalPreviewerAvailable()) {
            $previewer.attr('id', 'item-properties-preview');
            $previewer.closest('.data-container-wrapper').attr('id', 'item-properties-preview-column');

            inlinePropertiesPreview.init({
                isPreviewEnabled,
                itemUri: $('#edit-media').data('uri')
            });
            return;
        }

        loadingBar.start();
        qtiItemPreviewerFactory($previewer, {itemUri: $('#edit-media').data('uri')})
            .on('error', function (err) {
                if (typeof err.message !== 'undefined') {
                    feedback().error(err.message);
                }
                logger.error(err);
            })
            .on('preview-loaded', loadingBar.stop);
    };

    const manageMediaController =  {

        /**
         * Controller entry point
         */
        start() {
            const mimeType = $('.main-container').data('mimeType');
            const isPassage = mimeType === 'application/qti+xml';
            initPreview(isPassage);

            if (isPassage) {
                $('#media-authoring').show();
            } else {
                $('#media-authoring').hide();
            }

            $('#edit-media').off()
                .on('click', function() {
                    const action = { binding : 'load' , url: urlUtil.route('editMedia', 'MediaImport', 'taoMediaManager') };
                    binder.exec(action, { classUri : this.dataset.classuri , id : this.dataset.uri } || this._resourceContext);
                });
        }
    };

    return manageMediaController;
});
