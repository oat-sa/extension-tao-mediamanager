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

import paths from '../../../../taoQtiItem/views/cypress/utils/paths';
import { getRandomNumber } from '../../../../tao/views/cypress/utils/helpers';

describe('Passage Authoring Preview', () => {
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
    describe('Passage authoring Preview', () => {
        it('can create new asset', () => {
            cy.addNode(selectors.assetForm, selectors.addAsset);
            cy.renameSelectedNode(selectors.assetForm, selectors.editAssetUrl, itemName);
        })
        it('can open passage authoring', function () {
            cy.get(selectors.authoringAsset).click();
            cy.location().should(loc => {
                expect(`${loc.pathname}${loc.search}`).to.eq(urls.assetsAuthoring);
            });
        });

        it('can create an asse and add an A block', () => {
            addAblock();
        });

        it('can add some content to A-block', () => {
            const imageName = 'img-option.png';
            cy.getSettled(`${aBlockContainer}`).click();
            cy.get('[id="toolbar-top"]')
                .find('[class="cke_button cke_button__taoqtiimage cke_button_off"]')
                .click({ force: true });
            cy.get('.resourcemgr.modal').should('be.visible');
            selectUploadAssetToClass(imageName, `${paths.assetsPath}${imageName}`, className).then(() => {
                cy.log(`${paths.assetsPath}${imageName}`, 'IS ADDED');
                cy.getSettled('div.file-wrapper .previewer img').should('exist');
            });
            cy.getSettled(`${ablockContainerParagraph}`).click({ force: true });
        });

        it('can save passage with A block & content', () => {
            cy.intercept('PATCH', '**/taoMediaManager/SharedStimulus/patch*').as('savePassage');
            cy.get(selectors.assetAuthoringSaveButton).click({ force: true });
            cy.wait('@savePassage').its('response.body').its('success').should('eq', true);
        });
        it('can click on preview, preview is rendered', () => {
            cy.getSettled(selectors.assetAuthoringPreviewButton).should('not.be.disabled');
            cy.get(selectors.assetAuthoringPreviewButton).click();
            cy.intercept('GET', '**taoMediaManager/SharedStimulus/get*').as('previewPassage');
            cy.wait('@previewPassage');
            cy.get('div.preview-content').should('exist');

        });
        it('can check that passage with content is displayed', () => {
            cy.get('div.custom-text-box p').should(($div) => {
                const text = $div.text()
                expect(text).to.include('Lorem ipsum dolor sit amet, consectetur adipisicing ...');
                }
            )
            cy.get('img[alt="img-option.png"]').should('exist');
        });
    });
});
