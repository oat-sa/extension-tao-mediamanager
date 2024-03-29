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
 * @author Hanna Dzmitryieva <hanna@taotesting.com>
 */
define(['lodash', 'ui/ckeditor/ckConfigurator', 'mathJax'], function(_, ckConfigurator, mathJax) {
    'use strict';

    const _defaults = {
        qtiImage : true,
        qtiMedia : true,
        qtiInclude : false,
        underline : true,
        mathJax : !!mathJax,
        removePlugins: 'taoqtiinclude',
        horizontalRule: true,
        furiganaPlugin: true
    };

    /**
     * Generate a configuration object for CKEDITOR
     *
     * @param {object} editor instance of ckeditor
     * @param {String} toolbarType block | inline | flow | qtiBlock | qtiInline | qtiFlow | reset to get back to normal
     * @param {Object} [options] - is based on the CKEDITOR config object with some additional sugar
     *        Note that it's here you need to add parameters for the resource manager.
     *        Some options are not covered in http://docs.ckeditor.com/#!/api/CKEDITOR.config
     * @param {String} [options.dtdOverrides] - @see dtdOverrides which pre-defines them
     * @param {Object} [options.positionedPlugins] - @see ckConfig.positionedPlugins
     * @param {Boolean} [options.qtiImage] - enables the qtiImage plugin
     * @param {Boolean} [options.qtiInclude] - enables the qtiInclude plugin
     * @param {Boolean} [options.underline] - enables the underline plugin
     * @param {Boolean} [options.mathJax] - enables the mathJax plugin
     * @returns {Function} - a function for get the config
     *
     * @see http://docs.ckeditor.com/#!/api/CKEDITOR.config
     */
    const getConfig = function(editor, toolbarType = 'qtiInline', options) {
        return ckConfigurator.getConfig(editor, toolbarType, _.defaults(options || {}, _defaults));
    };

    return {
        getConfig : getConfig
    };
});
