
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
    'lodash',
    'jquery',
    'i18n',
    'taoMediaManager/qtiCreator/component/passageAuthoring',
    'ui/feedback',
    'util/url',
    'core/logger',
], function(_, $, __, passageAuthoringFactory, feedback, urlUtil, loggerFactory) {

    const logger = loggerFactory('taoMediaManager/authoring');

    const manageMediaController =  {

        /**
         * Controller entry point
         */
        start() {
            const $panel = $('#panel-authoring');
            const assetDataUrl = urlUtil.route('get', 'SharedStimulus', 'taoMediaManager');
            passageAuthoringFactory($panel, { properties: {
                uri: $panel.attr('data-uri'),
                id: $panel.attr('data-id'),
                assetDataUrl,
                // TO DO will be filled later
                baseUrl: assetDataUrl,
                lang: "en-US"
            }})
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
