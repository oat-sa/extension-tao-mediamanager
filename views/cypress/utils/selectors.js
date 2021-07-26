export default {
    deleteAsset: '[data-context="instance"][data-action="deleteItem"]',
    deleteClass: '[data-context="class"][data-action="deleteItemClass"]',
    moveClass: '[id="media-move-to"][data-context="resource"][data-action="moveTo"]',
    moveConfirmSelector: 'button[data-control="ok"]',
    addAsset: '[data-context="resource"][data-action="instanciate"]',
    assetForm: 'form[action="/taoMediaManager/MediaManager/editInstance"]',
    assetClassForm: 'form[action="/taoMediaManager/MediaManager/editClassLabel"]',
    deleteConfirm: '[data-control="delete"]',
    root: '[data-uri="http://www.tao.lu/Ontologies/TAOItem.rdf#Media"]'
};
