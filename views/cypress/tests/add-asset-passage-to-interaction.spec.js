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
import urlsItem from '../../../../taoQtiItem/views/cypress/utils/urls'
import selectors from '../utils/selectors';
import selectorsItem from '../../../../taoQtiItem/views/cypress/utils/selectors';

import { selectUploadAssetToClass } from '../utils/resource-manager';
import {selectUploadSharedStimulus,
        addSharedStimulusToInteraction } from "../../../../taoQtiItem/views/cypress/utils/resource-manager";
import { addAblock } from '../../../../taoQtiItem/views/cypress/utils/authoring-add-interactions';
import { addInteraction } from "../../../../taoQtiItem/views/cypress/utils/authoring-add-interactions";

import paths from '../../../../taoQtiItem/views/cypress/utils/paths';
import { getRandomNumber } from '../../../../tao/views/cypress/utils/helpers';
import { importSelectedAsset } from '../utils/import-selected-asset'
import {checkPassageNotEditable} from "../utils/check-read-only";


const className = `Test E2E class ${getRandomNumber()}`;
const itemName = 'Test E2E passage 1';
const choiceInteraction = 'choice';
const ablockContainerParagraph = '.widget-box[data-qti-class="_container"] p';
const aBlockContainer = '.widget-box[data-qti-class="_container"]';

describe('Passage Authoring', () => {
    /**
     * Log in
     * Visit the page
     * Create test folder
     */
    before(() => {
        cy.setup(
            selectors.treeRenderUrl,
            selectors.editClassLabelUrl,
            urls.assets,
            selectors.root
        );

        cy.addClassToRoot(
            selectors.root,
            selectors.assetClassForm,
            className,
            selectors.editClassLabelUrl,
            selectors.treeRenderUrl,
            selectors.addSubClassUrl
        );
        cy.addNode(selectors.assetForm, selectors.addAsset);
        cy.renameSelectedNode(selectors.assetForm, selectors.editAssetUrl, itemName);
    });
    /**
     * Visit the page
     * Delete test folder
     */
    after(() => {
        cy.log('AFTER')
        // Delete items class
        cy.intercept('POST', '**/edit*').as('editItem');
        cy.visit(urlsItem.items);
        cy.wait('@editItem');

        cy.deleteClassFromRoot(
            selectorsItem.root,
            selectorsItem.itemClassForm,
            selectorsItem.deleteClass,
            selectorsItem.deleteConfirm,
            className,
            selectorsItem.deleteClassUrl,
            true
        );
        //Delete Asset class
        cy.intercept('POST', '**/edit*').as('edit');
        cy.visit(urls.assets);
        cy.wait('@edit');

        cy.deleteClassFromRoot(
            selectors.root,
            selectors.assetClassForm,
            selectors.deleteClass,
            selectors.deleteConfirm,
            className,
            selectors.deleteClassUrl,
            true,
            true
        );
    });

    /**
     * Tests
     */
    describe('Passage authoring', () => {
        it('can open passage & add content', function () {
            cy.get(selectors.authoringAsset).click();
            addAblock();
        });

        it('can add image to passage', () => {
            const imageName = 'img-option.png';
            cy.getSettled(`${aBlockContainer}`).click();
            cy.get('[id="toolbar-top"]')
                .find('[class="cke_button cke_button__taoqtiimage cke_button_off"]')
                .click({ force: true });
            cy.get('.resourcemgr.modal').should('be.visible');
            selectUploadAssetToClass(imageName, `${paths.assetsPath}${imageName}`, className).then(() => {
                cy.log(`${paths.assetsPath}${imageName}`, 'IS ADDED');
                cy.getSettled(`${ablockContainerParagraph}`).click({ force: true });
            });
        });

        it('can save passage with A block & content', () => {
             cy.intercept('PATCH', '**/taoMediaManager/SharedStimulus/patch*').as('savePassage');
             cy.get(selectors.assetAuthoringSaveButton).click({ force: true });
             cy.wait('@savePassage').its('response.body').its('success').should('eq', true);
             cy.get(`${selectors.manageAssets}`).click();
        });
        it('can import asset', function () {
            const sharedStimulusName = 'importablepassagepxml.zip';
            cy.selectNode(selectors.root, selectors.assetClassForm, className);
            importSelectedAsset(
                selectors.importAsset,
    `${paths.assetsPath}/${sharedStimulusName}`,
                selectors.importAssetUrl,
                className);
            cy.log('IMAGE AND ASSET IMPORTED')
        });
     });

    describe('item authoring add shared stimulus', () => {
        it('can create an item ', function () {
            cy.intercept('POST', '**/taoItems/Items/editItem*').as('editItem');
            cy.visit(urlsItem.items);
            cy.wait('@editItem');
            // create folder and item
            cy.addClassToRoot(
                selectorsItem.root,
                selectorsItem.itemClassForm,
                className,
                selectorsItem.editClassLabelUrl,
                selectorsItem.treeRenderUrl,
                selectorsItem.addSubClassUrl
            );
            cy.addNode(selectorsItem.itemForm, selectorsItem.addItem);
            cy.renameSelectedNode(selectorsItem.itemForm, selectorsItem.editItemUrl, itemName);
        });

        it('can add an interaction to item ', function () {
            cy.get(selectorsItem.authoring).click();
            cy.getSettled('.qti-item.item-editor-item.edit-active').should('exist');
            addInteraction(choiceInteraction);
            cy.log('INTERACTION ADDED');
        });

        it('can add created passage to the prompt ', function () {
            const isCreatedAsset = true;
            const isChoice = false;
            addSharedStimulusToInteraction(isChoice)
            selectUploadSharedStimulus(isCreatedAsset);
        });

        it('can add created passage to the choice & save', function () {
            const isCreatedAsset = true;
            const isChoice = true;

            addSharedStimulusToInteraction(isChoice)
            selectUploadSharedStimulus(isCreatedAsset);
            cy.log('IMPORTED ASSET ADDED TO PROMPT');
            cy.saveItem();
            cy.get(`${selectorsItem.manageItems}`).click();
        });
        it('can check that created passage is read-only and cannot be edited', function () {
            cy.get(selectorsItem.authoring).click();
            //check that prompts's paragraph in passage cannot be edited
            checkPassageNotEditable(isChoice)
            //check that choice's paragraph in passage cannot be edited
            let isChoice = true;
            checkPassageNotEditable(isChoice)
            cy.saveItem();
            cy.get(`${selectorsItem.manageItems}`).click();
        });

        it('can add imported asset to the prompt ', function () {
            const isCreatedAsset = false;
            const isChoice = false;
            cy.addNode(selectorsItem.itemForm, selectorsItem.addItem);
            cy.get(selectorsItem.authoring).click();
            addInteraction(choiceInteraction);
            addSharedStimulusToInteraction(isChoice)
            selectUploadSharedStimulus(isCreatedAsset);
            cy.log('IMPORTED ASSET ADDED TO PROMPT');
        });

        it('can add imported asset to the choice ', function () {
            const isCreatedAsset = false;
            const isChoice = true;
            addSharedStimulusToInteraction(isChoice)
            selectUploadSharedStimulus(isCreatedAsset);
            cy.saveItem();
            cy.log('IMPORTED ASSET ADDED TO CHOICE');
            cy.get(`${selectorsItem.manageItems}`).click();
        });

        it('can check that imported passage is read-only and cannot be edited', function () {
            cy.get(selectorsItem.authoring).click();
            //check that prompts's paragraph in passage cannot be edited
             checkPassageNotEditable(isChoice)
            //check that choice's paragraph in passage cannot be edited
            let isChoice = true;
            checkPassageNotEditable(isChoice)
            cy.saveItem();
            cy.get(`${selectorsItem.manageItems}`).click();
        });
    });
});
