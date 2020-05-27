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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

define([
    'jquery',
    'lodash',
    'taoMediaManager/qtiCreator/renderers/Renderer',
    'taoItems/assets/manager',
    'taoItems/assets/strategies',
    'util/dom'
], function($, _, Renderer, assetManagerFactory, assetStrategies, dom) {

    //configure and instanciate once only:
    let _creatorRenderer = null;

    /**
     * Get a preconfigured renderer singleton
     *
     * @param {Boolean} reset
     * @param {Object} config
     * @param {Object} areaBroker - the QtiCreator area broker
     * @returns {Object} - a configured instance of creatorRenderer
     */
    const get = function(reset, config, areaBroker) {
        let $bodyEltForm;

        config = config || {};
        config.properties = config.properties || {};

        if (!_creatorRenderer || reset) {

            $bodyEltForm = _creatorRenderer ? _creatorRenderer.getOption('bodyElementOptionForm') : null;
            if (reset ||
                !$bodyEltForm ||
                !$bodyEltForm.length ||
                !dom.contains($bodyEltForm)){

                _creatorRenderer = new Renderer({
                    lang : '',
                    uri : '',
                    shuffleChoices : false,
                    itemOptionForm : $('#item-editor-item-property-bar .panel'),
                    bodyElementOptionForm : areaBroker.getElementPropertyPanelArea(),
                    textOptionForm : $('#item-editor-text-property-bar .panel'),
                    mediaManager : {
                        appendContainer : '#mediaManager',
                        browseUrl : config.properties.getFilesUrl,
                        uploadUrl : config.properties.fileUploadUrl,
                        deleteUrl : config.properties.fileDeleteUrl,
                        downloadUrl : config.properties.fileDownloadUrl,
                        fileExistsUrl : config.properties.fileExistsUrl,
                        mediaSourcesUrl : config.properties.mediaSourcesUrl
                    },
                    qtiCreatorContext : config.qtiCreatorContext,
                    locations: config.properties.locations
                });

                //update the resolver baseUrl
                _creatorRenderer.getAssetManager().setData({baseUrl : config.properties.baseUrl || '' });

                _creatorRenderer.setAreaBroker(areaBroker);

                // extend creator renderer to give access to the creator context
                _.assign(_creatorRenderer, {
                    getCreatorContext: function getCreatorContext() {
                        return this.getOption('qtiCreatorContext');
                    }
                });
            }
        }

        return _creatorRenderer;
    };


    return {
        get : get,

        setOption : function(name, value) {
            return get().setOption(name, value);
        },
        setOptions : function(options) {
            return get().setOptions(options);
        },
        load : function(qtiClasses, done){
            return get().load(function(...rest){
                if(_.isFunction(done)){
                    done.apply(this, rest);
                }
            }, qtiClasses);
        }
    };

});
