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
import { addBlockAndInlineInteractions } from '../utils/add-A-block-interaction-asset';

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
    describe('Asset creating and deletion', () => {
        it('can create and rename an Asset', function () {
            cy.selectNode(selectors.root, selectors.assetClassForm, className)
            .addNode(selectors.assetForm, selectors.addAsset)
            .renameSelectedNode(selectors.assetForm, selectors.editAssetUrl, AssetRenamed );
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
                selectors.resourceGetAllUrl,
                selectors.moveClassUrl
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
