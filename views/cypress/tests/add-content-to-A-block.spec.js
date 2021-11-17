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

import { selectUploadAssetToClass } from '../utils/resource-manager';
import { addAblock } from '../../../../taoQtiItem/views/cypress/utils/authoring-add-interactions';
import { editText } from '../../../../taoQtiItem/views/cypress/utils/edit-text-Ablock';

import paths from '../../../../taoQtiItem/views/cypress/utils/paths';
import { getRandomNumber } from '../../../../tao/views/cypress/utils/helpers';

describe('Passage Authoring', () => {
    const className = `Test E2E class ${getRandomNumber()}`;
    const itemName = 'Test E2E passage 1';
    const ablockContainerParagraph = '.widget-box[data-qti-class="_container"] p';
    const aBlockContainer = '.widget-box[data-qti-class="_container"]';

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
            false
        );
    });

    /**
     * Tests
     */
    describe('Passage authoring', () => {
        it('can open passage authoring', function () {
            cy.get(selectors.authoringAsset).click();
            cy.location().should(loc => {
                expect(`${loc.pathname}${loc.search}`).to.eq(urls.assetsAuthoring);
            });
        });

        it('can add A block and edit text (bold, italic sub, sup)', () => {
            addAblock();
            editText();
        });

        it('can add image to A-block', () => {
            const imageName = 'img-option.png';
            cy.getSettled(`${aBlockContainer}`).click();
            cy.get('[id="toolbar-top"]')
                .find('[class="cke_button cke_button__taoqtiimage cke_button_off"]')
                .click({ force: true });
            cy.get('.resourcemgr.modal').should('be.visible');
            selectUploadAssetToClass(imageName, `${paths.assetsPath}${imageName}`, className).then(() => {
                cy.log(`${paths.assetsPath}${imageName}`, 'IS ADDED');
            });
        });

        it('can add audio to A-block', () => {
            const audioName = 'sampleAudioSmall.mp3';
            cy.getSettled(`${ablockContainerParagraph}`).click({ force: true });
            cy.get('[id="toolbar-top"]')
                .find('[class="cke_button cke_button__taoqtimedia cke_button_off"]')
                .click({ force: true });
            cy.get('.resourcemgr.modal').should('be.visible');
            selectUploadAssetToClass(audioName, `${paths.assetsPath}${audioName}`, className).then(() => {
                cy.getSettled('.qti-object-container.previewer  div[data-type="audio/mpeg"]').should('have.length', 1);
                cy.log(`${paths.assetsPath}${audioName}`, 'IS ADDED');
            });
            cy.getSettled(`${ablockContainerParagraph}`).click({ force: true });
        });

        it('can add video to A-block', () => {
            const videoName = 'sampleSmall.mp4';
            cy.getSettled(`${ablockContainerParagraph}`).click({ force: true });
            cy.get('[id="toolbar-top"]')
                .find('[class="cke_button cke_button__taoqtimedia cke_button_off"]')
                .click({ force: true });
            cy.get('.resourcemgr.modal').should('be.visible');
            selectUploadAssetToClass(videoName, `${paths.assetsPath}${videoName}`, className).then(() => {
                cy.log(`${paths.assetsPath}${videoName}`, 'IS ADDED');
                cy.getSettled('.qti-object-container.previewer  div[data-type="video/mp4"]').should('have.length', 1);
            });
            cy.getSettled(`${ablockContainerParagraph}`).click({ force: true });
        });

        it('can save passage with A block & content', () => {
            cy.intercept('PATCH', '**/taoMediaManager/SharedStimulus/patch*').as('savePassage');
            cy.get(selectors.assetAuthoringSaveButton).click({ force: true });
            cy.wait('@savePassage').its('response.body').its('success').should('eq', true);
        });
    });
});
