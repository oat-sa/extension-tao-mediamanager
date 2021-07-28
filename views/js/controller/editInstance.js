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
    'jquery',
    'lodash',
    'layout/actions/binder',
    'ui/previewer',
    'util/url',
    'taoMediaManager/previewer/component/qtiSharedStimulusItem',
    'core/logger',
    'ui/feedback',
], function($, _, binder, previewer, urlUtil, qtiItemPreviewerFactory, loggerFactory, feedback) {
    'use strict';

    const logger = loggerFactory('taoMediaManager/editInstance');

    const manageMediaController =  {

        /**
         * Controller entry point
         */
        start() {

            const $previewer = $('.previewer');
            let file = {};
            file.url = $previewer.data('url');
            file.mime = $previewer.data('type');

            const isPreviewEnabled = $previewer.data('enabled');
            const isPassage = file.mime === 'application/qti+xml';

            if (isPreviewEnabled) {
                if (!isPassage) {
                    // to hide the loading icon, inherited from the .previewer
                    file.containerClass = 'no-background';
                    $previewer.previewer(file);
                } else {
                    qtiItemPreviewerFactory($previewer, {itemUri:  $('#edit-media').data('uri')})
                        .on('error', function (err) {
                            if (!_.isUndefined(err.message)) {
                                feedback().error(err.message);
                            }
                            logger.error(err);
                        });
                }
            }

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
