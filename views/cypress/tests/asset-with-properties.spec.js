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

import { getRandomNumber } from '../../../../tao/views/cypress/utils/helpers';
import {addPropertyToAssetClass, givePropertiesToAsset} from "../utils/asset-properties.spec";

describe('Passage Authoring properties', () => {
    const className = `Test E2E class ${getRandomNumber()}`;
    const itemName = 'Test E2E passage 1';
    const item2Name = 'Test E2E passage 2';
    const newPropertyName = 'I am a new property in testing, hello!';
    const newPropertyAlias = 'testing_property_alias';
    const options = {
        nodeName: selectors.root,
        className: className,
        propertyName: newPropertyName,
        propertyAlias: newPropertyAlias,
        nodePropertiesForm: selectors.assetClassForm,
        manageSchema: selectors.manageSchema,
        classOptions: selectors.classAssetOptions,
        editUrl: selectors.editClassUrl,
        propertyEditSelector: selectors.propertyEdit
    };
    const propertiesWithListValues = [
        'list',
        'multiplenodetree',
        'longlist',
        'multilist',
        'multisearchlist',
        'singlesearchlist'
    ];


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
    });
    /**
     * Visit the page
     * Delete test folder
     */
    after(() => {
        cy.log('after');
        cy.deleteClassFromRoot(
            selectors.root,
            selectors.assetClassForm,
            selectors.deleteClass,
            selectors.deleteConfirm,
            className,
            selectors.deleteClassUrl
        );
    });

    /**
     * Tests
     */
    describe('Passage authoring properties', () => {
        it('can create class ', () => {
            cy.addClassToRoot(
                selectors.root,
                selectors.assetClassForm,
                className,
                selectors.editClassLabelUrl,
                selectors.treeRenderUrl,
                selectors.addSubClassUrl
            );

        })
        it('can edit and add properties to the to class', function () {
            addPropertyToAssetClass(options);
        });

        it('can create a new asset &  give it a property false', () => {
            let isTrue = false;
           cy.addNode(selectors.assetForm, selectors.addAsset);
           cy.renameSelectedNode(selectors.assetForm, selectors.editAssetUrl, itemName);
           cy.get('.bool-list label.form_desc').should('have.text',newPropertyName);
            givePropertiesToAsset(isTrue)
        });

        it('can create another asset and give it different value to property true', () => {
            let isTrue = true;
            cy.addNode(selectors.assetForm, selectors.addAsset);
            cy.getSettled(selectors.assetForm).should('be.visible');
            cy.renameSelectedNode(selectors.assetForm, selectors.editAssetUrl, item2Name);
            cy.get('.bool-list label.form_desc').should('have.text',newPropertyName);
            givePropertiesToAsset(isTrue)
        });
        it('can check that both assets have saved given property', () => {
            cy.log('Checking given properties are present in assets');
            cy.selectNode(selectors.root, '.xhtml_form', itemName);
            cy.get('.form_radlst').children('input').first().should('be.checked');
            cy.selectNode(selectors.root, '.xhtml_form', item2Name);
            cy.get('.form_radlst').children('input').last().should('be.checked');

        });

    });
});
