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
 * Copyright (c) 2020 Open Assessment Technologies SA ;
 */
/**
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */
define([
    'jquery',
    'lodash',
    'i18n',
    'module',
    'core/logger',
    'ui/feedback',
    'layout/loading-bar',
    'util/url',
    // 'taoMediaManager/controller/app',
    // 'taoMediaManager/component/task/action',
    // 'taoMediaManager/component/task/providers/review',
    // 'taoMediaManager/component/task/providers/evaluate',
    // 'taoMediaManager/component/task/providers/author'
], function (
    $,
    _,
    __,
    module,
    loggerFactory,
    feedback,
    loadingBar,
    urlHelper,
    // appController,
    // taskActionFactory,
    // reviewTaskActionProvider,
    // evaluateTaskActionProvider,
    // authorTaskActionProvider
) {
    'use strict';

    /**
     * Default controller's config
     * @type {Object}
     */
    const defaults = {
        returnUrl: urlHelper.route('index', 'TaoMediaManager', 'taoMediaManager')
    };

    // @todo load dynamically the providers
    // taskActionFactory.registerProvider('review', reviewTaskActionProvider);
    // taskActionFactory.registerProvider('evaluate', evaluateTaskActionProvider);
    // taskActionFactory.registerProvider('author', authorTaskActionProvider);

    /**
     * Controls a task the current user is processing
     */
    return {
        start() {
            const options = _.defaults({}, appController.options, module.config(), defaults);

            loadingBar.start();

            try {
                taskActionFactory(appController.content.getElement(), options)
                    .on('viewcreate', () => loadingBar.start())
                    .on('viewrender', (name, component) => component.on('success', message => feedback().success(message)))
                    .on('ready viewrender', () => loadingBar.stop())
                    .on('submit', function(values) {
                        loadingBar.start();

                        // @todo send the values to the backend
                        // dataProvider('something').submit(values).then(() => this.close()).catch()

                        // then close the page and go back to the main page
                        this.close();
                    })
                    .on('close', () => appController.redirect(options.returnUrl))
                    .on('error', appController.onError);
            } catch(err) {
                appController.onError(err);
            }
        }
    };
});