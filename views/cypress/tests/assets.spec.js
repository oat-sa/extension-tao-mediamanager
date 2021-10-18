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

describe('Assets', () => {
    const className = 'Asset E2E class';
    const classMovedName = 'Asset E2E class Moved';
    const AssetRenamed = 'Renamed E2E Asset';

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
    describe('Asset creating, authoring and deletion', () => {
        it('can create and rename an Asset', function () {
            cy.selectNode(selectors.root, selectors.assetClassForm, className)
            .addNode(selectors.assetForm, selectors.addAsset)
            .renameSelectedNode(selectors.assetForm, selectors.editAssetUrl, AssetRenamed );
        });
        it('can click passage Authoring & check all blocks present', function () {
            cy.get(selectors.authoringAsset).click();
            cy.get(selectors.assetAuthoringPanel).find('li[data-qti-class="_container"]');
            cy.getSettled(selectors.assetAuthoringCanvas).should('have.length', 1);
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

    describe('Moving and deleting asset class', function () {
        it('can move asset class', function () {
            cy.intercept('POST', `**/${ selectors.editClassLabelUrl }`).as('editClassLabel');

            cy.getSettled(`${selectors.root} a:nth(0)`)
                .click()
                .wait('@editClassLabel')
                .addClass(selectors.assetClassForm, selectors.treeRenderUrl, selectors.addSubClassUrl)
                .renameSelectedClass(selectors.assetClassForm, classMovedName);

            cy.moveClassFromRoot(
                selectors.root,
                selectors.moveClass,
                selectors.moveConfirmSelector,
                className,
                classMovedName,
                selectors.restResourceGetAll
            );
        });

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
