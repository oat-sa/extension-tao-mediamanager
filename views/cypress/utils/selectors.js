export default {
    addSubClassUrl: 'taoMediaManager/MediaManager/addSubClass',
    assetClassForm: 'form[action="/taoMediaManager/MediaManager/editClassLabel"]',
    addAsset: '[data-context="resource"][data-action="newSharedStimulus"]',
    assetForm: 'form[action="/taoMediaManager/MediaManager/editInstance"]',
    authoringAsset: '[data-action= "sharedStimulusAuthoring"]',
    assetAuthoringPanel: 'section[id="sidebar-left-section-inline-interactions"]',
    assetAuthoringCanvas: 'div[id="item-editor-scroll-inner"]',
    assetAuthoringSaveButton: '[data-testid="save-the-asset"]',
    manageAssets: 'li[data-testid="manage-assets"]',

    deleteClass: '[data-context="class"][data-action="deleteSharedStimulus"]',
    deleteConfirm: 'button[data-control="ok"]',
    deleteClassUrl: 'taoMediaManager/MediaManager/deleteClass',
    deleteAsset: '[data-context="instance"][data-action="deleteSharedStimulus"]',
    deleteAssetUrl: 'taoMediaManager/MediaManager/deleteResource',

    editClassLabelUrl: 'taoMediaManager/MediaManager/editClassLabel',
    editAssetUrl: 'taoMediaManager/MediaManager/editInstance',

    moveClass: '[id="media-move-to"][data-context="resource"][data-action="moveTo"]',
    moveConfirmSelector: 'button[data-control="ok"]',

    root: '[data-uri="http://www.tao.lu/Ontologies/TAOMedia.rdf#Media"]',
    restResourceGetAll: 'tao/RestResource/getAll',

    treeRenderUrl: 'taoMediaManager/MediaManager',
    treeMediaManager :'div[id="tree-media_manager"]'
};
