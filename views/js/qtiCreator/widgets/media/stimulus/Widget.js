define([
    'taoMediaManager/qtiCreator/widgets/media/Widget',
    'taoMediaManager/qtiCreator/widgets/media/stimulus/states/states'
], function(Widget, states){

    var StimulusWidget = Widget.clone();

    StimulusWidget.initCreator = function(){

        Widget.initCreator.call(this);

        this.registerStates(states);
    };

    return StimulusWidget;
});