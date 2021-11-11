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
import { addABlock } from '../utils/add-A-block-interaction-asset';

describe('Assets', () => {
    const className = 'Asset E2E class';
    const AssetRenamed = 'Renamed E2E Asset';

    /**
     * Log in and wait for render
     * After @treeRender click root class
     */
    before(() => {
        cy.setup(selectors.treeRenderUrl, selectors.editClassLabelUrl, urls.assets, selectors.root);
        cy.get(selectors.root).then(root => {
            if (!root.find(`li[title="${className}"] a`).length) {
                cy.addClassToRoot(
                    selectors.root,
                    selectors.assetClassForm,
                    className,
                    selectors.editClassLabelUrl,
                    selectors.treeRenderUrl,
                    selectors.addSubClassUrl
                );
            }
            cy.selectNode(selectors.root, selectors.assetClassForm, className);
            cy.addNode(selectors.assetForm, selectors.addAsset);
            cy.renameSelectedNode(selectors.assetForm, selectors.editAssetUrl, AssetRenamed);
        });
    });
    /**
     * Remove e2e class
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
     * Assets
     */
    describe('Asset authoring and deletion', () => {
        it('can click passage Authoring & check all blocks present', function () {
            cy.get(selectors.authoringAsset).click();
            cy.get(selectors.assetAuthoringPanel).find('li[data-qti-class="_container"]');
            cy.getSettled(selectors.assetAuthoringCanvas).should('have.length', 1);
        });

        it('can add A block to the canvas', function () {
            cy.log('ADD A-BLOCK TO CANVAS');
            addABlock();
        });

        it('CK Editor is present when added A-block is highlighted', () => {
            cy.get('.widget-box.widget-block.widget-textBlock').click();
            cy.getSettled('#toolbar-top .cke').should('be.visible');
            cy.getSettled('#toolbar-top .cke .cke_toolbar').should('have.length', 5);
        });

        it('can save asset with A-block in it', function () {
            cy.log('SAVE ASSET WITH A-BLOCK IN IT');
            cy.intercept('PATCH', '**/taoMediaManager/SharedStimulus/patch*').as('saveAsset');
            cy.get('[data-testid="save-the-asset"]').click();
            cy.wait('@saveAsset').its('response.body').its('success').should('eq', true);
            cy.get('[data-testid="save-the-asset"]').click();
        });

        it('should be enabled "Preview Item" button', () => {
            cy.get('[data-testid="preview-the-asset"]').should('not.have.class', 'disabled');
        });

        it('can go back', function () {
            cy.get(selectors.manageAssets).click();
            cy.getSettled(selectors.treeMediaManager).find(`li[title="${AssetRenamed}"]`);
        });

        it('can re-open the asset and validates that added block is present', function () {
            cy.log('VALIDATES THAT A-BLOCK PRESENT WHEN RE-OPENED');
            cy.get(selectors.authoringAsset).click();
            cy.get(selectors.assetAuthoringPanel).find('li[data-qti-class="_container"]');
            cy.getSettled(selectors.assetAuthoringCanvas).should('have.length', 1);
            cy.get('.widget-box.widget-block.widget-textBlock').should('exist');
        });
    });
});
