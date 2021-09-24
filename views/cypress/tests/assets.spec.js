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

    /**
     * Log in and wait for render
     * After @treeRender click root class
     */
    before(() => {
        cy.loginAsAdmin();
        cy.intercept('GET', `**/${ selectors.treeRenderUrl }/getOntologyData**`).as('treeRender');
        cy.intercept('POST', `**/${ selectors.editClassLabelUrl }`).as('editClassLabel');
        cy.visit(urls.assets);
        cy.wait('@treeRender');
        cy.get(`${selectors.root} a`)
            .first()
            .click();
        cy.wait('@editClassLabel');
    });

    /**
     * Assets
     */
    describe('Asset creation, editing and deletion', () => {
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
