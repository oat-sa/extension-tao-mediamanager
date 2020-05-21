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
