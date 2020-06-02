define(['taoQtiItem/qtiRunner/core/Renderer', 'taoMediaManager/qtiXmlRenderer/renderers/config'], function(Renderer, config){
    'use strict';

    return Renderer.build(config.locations, config.name, config.options);
});
