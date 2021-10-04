export default {
    addSubClassUrl: 'taoMediaManager/MediaManager/addSubClass',
    assetClassForm: 'form[action="/taoMediaManager/MediaManager/editClassLabel"]',

    deleteClass: '[data-context="class"][data-action="deleteSharedStimulus"]',
    deleteConfirm: 'button[data-control="ok"]',
    deleteClassUrl: 'taoMediaManager/MediaManager/deleteClass',

    editClassLabelUrl: 'taoMediaManager/MediaManager/editClassLabel',

    moveClass: '[id="media-move-to"][data-context="resource"][data-action="moveTo"]',
    moveConfirmSelector: 'button[data-control="ok"]',

    root: '[data-uri="http://www.tao.lu/Ontologies/TAOMedia.rdf#Media"]',
    restResourceGetAll: 'tao/RestResource/getAll',

    treeRenderUrl: 'taoMediaManager/MediaManager',
};
