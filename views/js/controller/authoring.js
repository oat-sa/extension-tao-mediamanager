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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */

/**
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */
define([
    'i18n',
    'lodash',
    'jquery',
    'uri',
    'taoMediaManager/qtiCreator/component/sharedStimulusAuthoring',
    'ui/feedback',
    'util/url',
    'core/dataProvider/request',
    'core/logger'
], function (__, _, $, uri, sharedStimulusAuthoringFactory, feedback, urlUtil, request, loggerFactory) {
    'use strict';

    const logger = loggerFactory('taoMediaManager/authoring');

    const manageMediaController = {
        /**
         * Controller entry point
         */
        start() {
            const $panel = $('#panel-authoring');
            const assetDataUrl = urlUtil.route('get', 'SharedStimulus', 'taoMediaManager');
            const assetId = uri.decode($panel.attr('data-id'));
            let previewEnabled = false;

            request(assetDataUrl, { id : assetId })
                .then(response => {
                    if (response.permissions == 'READ') {
                        previewEnabled = true;
                    }
                });

            sharedStimulusAuthoringFactory($panel, {
                properties: {
                    uri: $panel.attr('data-uri'),
                    id: assetId,
                    assetDataUrl,
                    fileUploadUrl: urlUtil.route('upload', 'ItemContent', 'taoItems'),
                    fileDeleteUrl: urlUtil.route('delete', 'ItemContent', 'taoItems'),
                    fileDownloadUrl: urlUtil.route('download', 'ItemContent', 'taoItems'),
                    fileExistsUrl: urlUtil.route('fileExists', 'ItemContent', 'taoItems'),
                    getFilesUrl: urlUtil.route('files', 'ItemContent', 'taoItems'),
                    baseUrl: urlUtil.route('getFile', 'MediaManager', 'taoMediaManager', { uri: '' }),
                    path: 'taomedia://mediamanager/',
                    root: 'mediamanager',
                    lang: 'en-US',
                    previewEnabled: previewEnabled
                }
            })
                .on('success', () => {
                    feedback().success(__('Your passage is saved'));
                })
                .on('error', err => {
                    if (!_.isUndefined(err.message)) {
                        feedback().error(err.message);
                    }
                    logger.error(err);
                });
        }
    };

    return manageMediaController;
});
