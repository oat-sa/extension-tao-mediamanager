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
     * Visit the page
     */
    beforeEach(() => {
        cy.visit(urls.assets);
    });

    /**
     * Log in
     */
    before(() => {
        cy.loginAsAdmin();
    });

    /**
     * Assets
     */
    describe('Asset creation, editing and deletion', () => {
        it('can create a new asset class', function () {
            cy.addClassToRoot(selectors.root, selectors.assetClassForm, className);
        });

        it('can delete empty asset class', function () {
            cy.addClassToRoot(selectors.root, selectors.assetClassForm, className);
            cy.deleteClassFromRoot(
                selectors.root,
                selectors.assetClassForm,
                selectors.deleteClass,
                selectors.deleteConfirm,
                className
            );
        });

        it('can move asset class', function () {
            cy.moveClassFromRoot(
                selectors.root,
                selectors.assetClassForm,
                selectors.moveClass,
                selectors.moveConfirmSelector,
                className,
                classMovedName
            );
            cy.deleteClassFromRoot(
                selectors.root,
                selectors.assetClassForm,
                selectors.deleteClass,
                selectors.deleteConfirm,
                classMovedName
            );
        });
    });
});
