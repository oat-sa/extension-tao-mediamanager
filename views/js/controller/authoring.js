
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
    'core/logger',
], function(__, _, $, uri, sharedStimulusAuthoringFactory, feedback, urlUtil, loggerFactory) {
    'use strict';

    const logger = loggerFactory('taoMediaManager/authoring');

    const manageMediaController =  {

        /**
         * Controller entry point
         */
        start() {
            const $panel = $('#panel-authoring');
            const assetDataUrl = urlUtil.route('get', 'SharedStimulus', 'taoMediaManager');
            sharedStimulusAuthoringFactory($panel, { properties: {
                uri: $panel.attr('data-uri'),
                id: uri.decode($panel.attr('data-id')),
                assetDataUrl,
                itemDataUrl: urlUtil.route('getItemData', 'QtiCreator', 'taoQtiItem'),
                fileUploadUrl : urlUtil.route('upload', 'ItemContent', 'taoItems'),
                fileDeleteUrl : urlUtil.route('delete', 'ItemContent', 'taoItems'),
                fileDownloadUrl : urlUtil.route('download', 'ItemContent', 'taoItems'),
                fileExistsUrl : urlUtil.route('fileExists', 'ItemContent', 'taoItems'),
                getFilesUrl: urlUtil.route('files', 'ItemContent', 'taoItems'),
                mediaSourcesUrl: urlUtil.route('getMediaSources', 'QtiCreator', 'taoQtiItem'),
                previewRenderUrl: urlUtil.route('render', 'QtiPreview', 'taoQtiItem'),
                previewSubmitUrl: urlUtil.route('submitResponses', 'QtiPreview', 'taoQtiItem'),
                previewUrl: urlUtil.route('index', 'QtiPreview', 'taoQtiItem'),
                baseUrl: assetDataUrl,
                lang: 'en-US'
            }})
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
