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
 * Copyright (c) 2021 Open Assessment Technologies SA ;
 */

/**
 * check that the selected asset is read only
 * @param {Boolean} isChoice specifies if the selector is choice (vs prompt)
 *
 */


export function checkPassageNotEditable(isChoice = false){
    cy.get('[contenteditable="false"]').should('exist');
    cy.getSettled('#item-editor-scoll-container').click();
    cy.get('.qti-prompt-container').click();
    if (isChoice){
        cy.get('.qti-prompt-container').click();
    } else {
        cy.get('.choice-area ').find('[data-identifier="choice_2"]').click({force:true});
    }
    cy.get('.qti-include').last().click({force: true});
    cy.getSettled('#toolbar-top').should('have.css','display', 'none');
    cy.log('CONFIRMED ASSET NOT EDITABLE');
}
