define([
    'taoQtiItem/qtiCreator/widgets/states/factory',
    'taoQtiItem/qtiCreator/widgets/static/states/states',
    'taoQtiItem/qtiCreator/widgets/static/text/states/Sleep',
    'taoMediaManager/qtiCreator/widgets/static/text/states/Active'
], function(factory, states){
    'use strict';

    return factory.createBundle(states, arguments);
});