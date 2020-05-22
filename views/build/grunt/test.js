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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

module.exports = function(grunt) {

    const watch       = grunt.config('watch') || {};
    const qunit       = grunt.config('qunit') || {};
    const testUrl     = 'http://127.0.0.1:' + grunt.option('testPort');
    const root        = grunt.option('root');

    const testRunners = root + '/taoMediaManager/views/js/test/**/test.html';
    const testFiles = root + '/taoMediaManager/views/js/test/**/test.js';

    //extract unit tests
    const extractTests = function extractTests(){
        return grunt.file.expand([testRunners]).map(function(path){
            return path.replace(root, testUrl);
        });
    };

    /**
     * tests to run
     */
    qunit.taomediamanagertest = {
        options : {
            console : true,
            urls : extractTests()
        }
    };


    watch.taomediamanagertest = {
        files : [testRunners, testFiles],
        tasks : ['qunit:taomediamanagertest'],
        options : {
            debounceDelay : 10000
        }
    };

    grunt.config('qunit', qunit);
    grunt.config('watch', watch);

    // bundle task
    grunt.registerTask('taomediamanagertest', ['qunit:taomediamanagertest']);
};
