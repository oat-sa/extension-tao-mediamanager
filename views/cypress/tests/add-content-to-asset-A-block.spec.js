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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA ;
 */




import urls from '../utils/urls';
import selectors from '../utils/selectors';
import {addBlockAndInlineInteractions} from "../../../../taoQtiItem/views/cypress/utils/add-A-block-interaction-asset";
import {selectUploadLocalAsset} from "../../../../taoQtiItem/views/cypress/utils/resource-manager";
import {editText} from "../../../../taoQtiItem/views/cypress/utils/Asset-edit-text-Ablock";
import paths from "../../../../taoQtiItem/views/cypress/utils/paths";

describe('Assets', () => {
    const className = 'Asset E2E class';
    const classMovedName = 'Asset E2E class Moved';
    const AssetRenamed = 'Renamed E2E Asset';
    const ablockContainerParagraph = '.widget-box[data-qti-class="_container"] p';
    const aBlockContainer = '.widget-box[data-qti-class="_container"]';

    /**
     * Log in and wait for render
     * After @treeRender click root class
     */
    before(() => {
        cy.setup(
            selectors.treeRenderUrl,
            selectors.editClassLabelUrl,
            urls.assets,
            selectors.root
        );
        cy.get(selectors.root).then(root => {
            if (root.find(`li[title="${className}"] a`).length) {
                cy.deleteClassFromRoot(
                    selectors.root,
                    selectors.assetClassForm,
                    selectors.deleteClass,
                    selectors.deleteConfirm,
                    className,
                    selectors.deleteClassUrl,
                    false
                );
            }
        });
    });

    /**
     * Assets
     */
    describe('Asset class creation and editing', () => {
        it('can create a new asset class', function () {
            cy.addClassToRoot(
                selectors.root,
                selectors.assetClassForm,
                className,
                selectors.editClassLabelUrl,
                selectors.treeRenderUrl,
                selectors.addSubClassUrl
            );
        });
    });
    describe('Asset creating, adding content and deletion', () => {
        it('can create a new Asset', function () {
            cy.selectNode(selectors.root, selectors.assetClassForm, className)
                .addNode(selectors.assetForm, selectors.addAsset)
                .renameSelectedNode(selectors.assetForm, selectors.editAssetUrl, AssetRenamed);
        });
        it('can add A block to the canvas', function () {
            cy.log('ADD A-BLOCK TO CANVAS');
            cy.get(selectors.authoringAsset).click();
            addBlockAndInlineInteractions();
        });
        it('can edit text (bold, italic sub, sup)', () => {
            editText();
        });
        it('can add image to A-block', () => {
            const imageName = 'img-option.png';
            cy.getSettled(`${aBlockContainer}`).click();
            cy.get('[id="toolbar-top"]')
                .find('[class="cke_button cke_button__taoqtiimage cke_button_off"]')
                .click({force: true});
            selectUploadLocalAsset(imageName, `${paths.assetsPath}${imageName}`).then(() => {
                cy.log(`${paths.assetsPath}${imageName}`, 'IS ADDED');
            });
        });
        it('can add audio to A-block', () => {
            const audioName = 'sampleAudioSmall.mp3';
            cy.getSettled(`${ablockContainerParagraph}`).click({force:true});
            cy.get('[id="toolbar-top"]')
                .find('[class="cke_button cke_button__taoqtimedia cke_button_off"]')
                .click({force: true});
            cy.getSettled(`${ablockContainerParagraph}`).click({force:true});
            selectUploadLocalAsset(audioName, `${paths.assetsPath}${audioName}`).then(() => {
                cy.get('div[class="previewer"]').should('exist');
                cy.log(`${paths.assetsPath}${audioName}`, 'IS ADDED');
                cy.get('a.tlb-button-off.select').last().click();
                //close modal if it's still open
                if (cy.get('button.modal-close#modal-close-btn')){
                    cy.get('button.modal-close#modal-close-btn').click({force:true, multiple: true});
                }
            });
            cy.getSettled(`${ablockContainerParagraph}`).click({force:true});
        });
        it('can add video to A-block', () => {
            const videoName = 'sampleSmall.mp4';
            cy.getSettled(`${ablockContainerParagraph}`).click({force:true});
            cy.get('[id="toolbar-top"]')
                .find('[class="cke_button cke_button__taoqtimedia cke_button_off"]')
                .click({force:true});
            cy.getSettled(`${ablockContainerParagraph}`).click({force:true});
            selectUploadLocalAsset(videoName, `${paths.assetsPath}${videoName}`).then(() => {
                cy.get('div[class="previewer"]').should('exist');
                cy.get('a.tlb-button-off.select').last().click();
                cy.log(`${paths.assetsPath}${videoName}`, 'IS ADDED');
                //close modal if it's still open
                if (cy.get('button.modal-close#modal-close-btn')){
                    cy.get('button.modal-close#modal-close-btn').click({force:true, multiple: true});
                }
            });
            cy.getSettled(`${ablockContainerParagraph}`).click({force:true});
        });
        it('can save asset with A-block in it', function () {
            cy.log('SAVE ASSET WITH A-BLOCK IN IT');
            cy.intercept('PATCH', '**/taoMediaManager/SharedStimulus/patch*').as('saveAsset');
            cy.get('[data-testid="save-the-asset"]').click();
            cy.wait('@saveAsset')
                .its('response.body')
                .its('success')
                .should('eq', true);
        });

        it('can go back', function () {
            cy.get(selectors.manageAssets).click();
            cy.getSettled(selectors.treeMediaManager).find(`li[title="${AssetRenamed}"]`);
        });

        it('can delete passage', function () {
            cy.get(`li[title="${AssetRenamed}"]`)
                .deleteNode(
                    selectors.root,
                    selectors.deleteAsset,
                    selectors.editAssetUrl,
                    AssetRenamed
                );
        });
    });
    describe('Deleting asset class', function () {

        it('can delete asset class', function () {
            cy.deleteClassFromRoot(
                selectors.root,
                selectors.assetClassForm,
                selectors.deleteClass,
                selectors.deleteConfirm,
                classMovedName,
                selectors.deleteClassUrl,
                false
            );
        });
    });
});


