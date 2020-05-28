/*
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
* Copyright (c) 2015-2020 (original work) Open Assessment Technologies SA;
*/

module.exports = function(grunt) {

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = `${grunt.option('root')  }/taoMediaManager/views/`;

    // Override include paths
    sass.taomediamanager = {
        options : {},
        files : {}
    };

    //files goes heres
    sass.taomediamanager.files[`${root  }css/media.css`] = `${root  }scss/media.scss`;
    sass.taomediamanager.files[`${root  }css/passage-creator.css`] = `${root  }scss/passage-creator.scss`;

    watch.taomediamanagersass = {
        files : [`${root  }scss/**/*.scss`],
        tasks : ['sass:taomediamanager', 'notify:taomediamanagersass'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taomediamanagersass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taomediamanagersass', ['sass:taomediamanager']);
};
