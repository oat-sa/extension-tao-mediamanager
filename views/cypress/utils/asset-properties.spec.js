import selectors from "./selectors";

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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA ;
 */

const propertiesWithListValues = [
    'list',
    'multiplenodetree',
    'longlist',
    'multilist',
    'multisearchlist',
    'singlesearchlist'
];

/**
 * Adds new property to Asset class (list with single selection of boolean values)
 * @param {Object} options - Configuration object containing all target variables
 * @param {String} options.className
 * @param {String} options.manageSchemaSelector - css selector for the edit class button
 * @param {String} options.classOptions - css selector for the class options form
 * @param {String} options.propertyName
 * @param {String} options.propertyAlias
 * @param {String} options.propertyEditSelector - css selector for the property edition form
 * @param {String} options.editUrl - url for the editing class POST request
 */


export function addPropertyToAssetClass (options) {
    options.propertyType = options.propertyType || 'list';
    options.propertyListValue = options.propertyListValue || 'Boolean';

    cy.log('COMMAND: addPropertyToAssetClass', options.propertyName);
    cy.intercept('POST', '**/addClassProperty').as('addProperty');

    cy.getSettled(`li [title ="${options.className}"]`)
        .last()
        .click();
    cy.getSettled(options.manageSchema).click();
    cy.getSettled('div.form-content')
        .find('a[class="btn-info property-adder small"]')
        .click();
    cy.wait('@addProperty');

    cy.getSettled('span[class="icon-edit"]')
        .last()
        .click();
    cy.get(options.propertyEditSelector)
        .find('input[data-testid="Label"]')
        .clear()
        .type(options.propertyName);

    if (options.propertyAlias) {
        cy.get(options.propertyEditSelector)
            .find('input[data-testid="Alias"]')
            .clear()
            .type(options.propertyAlias);
    }
    cy.get(options.propertyEditSelector)
        .find('select[class="property-type property"]')
        .select(options.propertyType);

    if (propertiesWithListValues.includes(options.propertyType)) {
        cy.get(options.propertyEditSelector)
            .find('select[class="property-listvalues property"]')
            .select(options.propertyListValue);
    }

    cy.intercept('GET', `**/${options.editUrl}**`).as('editClass');
    cy.get('button[type="submit"]').click();

    cy.wait('@editClass');
}

/**
 * Gives properties to Asset in Class with created property (list with single selection of boolean values)
 * @param {Boolean} isTrue - Determines if we are going to give it the value true or false
 */
export function givePropertiesToAsset(isTrue) {
    if (isTrue) {
        cy.get('.bool-list').find('input[type="radio"]').last().check();
    } else {
        cy.get('.bool-list').find('input[type="radio"]').first().check();
    }
    cy.intercept('POST', `**/${selectors.editAssetUrl}`).as('editAsset');
    cy.get('[data-testid="save"]').click();
    cy.wait('@editAsset');
    cy.get('div.feedback.feedback-info.popup').should('exist');
}