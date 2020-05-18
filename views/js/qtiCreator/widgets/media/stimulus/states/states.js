define([
    'taoMediaManager/qtiCreator/widgets/states/factory',
    'taoMediaManager/qtiCreator/widgets/choices/states/states',
    'taoMediaManager/qtiCreator/widgets/choices/inlineChoice/states/Question'
], function(factory, states){
    return factory.createBundle(states, arguments);
});