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
 * Copyright (c) 2014-2018 (original work) Open Assessment Technologies SA;
 */

/**
 * configure the extension bundles
 * @param {Object} grunt
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
module.exports = function (grunt) {
    'use strict';

    grunt.config.merge({
        bundle: {
            taomediamanager: {
                options: {
                    extension: 'taoMediaManager',
                    dependencies: ['taoItems', 'taoQtiItem', 'taoQtiTestPreviewer', 'taoTests'],
                    outputDir: 'loader',
                    paths: require('./paths.json'),
                    bundles: [
                        {
                            name: 'taoMediaManager',
                            default: true,
                            babel: true,
                            dependencies: [
                                'taoItems/loader/taoItemsRunner.min',
                                'taoTests/loader/taoTestsRunner.min',
                                'taoQtiItem/loader/taoQtiItemRunner.min',
                                'taoQtiItem/loader/taoQtiItem.min',
                                'taoQtiTestPreviewer/loader/qtiPreviewer.min'
                            ],
                            include: [
                                'taoMediaManager/controller/**/*',
                                'taoMediaManager/previewer/**/*',
                                'taoMediaManager/qtiCreator/**/*',
                                'taoMediaManager/qtiXmlRenderer/**/*'
                            ]
                        },
                        {
                            name: 'xincludeRendererAddStyles',
                            default: true,
                            babel: true,
                            dependencies: [],
                            include: ['taoMediaManager/richPassage/xincludeRendererAddStyles']
                        }
                    ]
                }
            }
        }
    });

    // bundle task
    grunt.registerTask('taomediamanagerbundle', ['bundle:taomediamanager']);
};
