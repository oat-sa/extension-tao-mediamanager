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
 * Select file in resource manager or upload it
 * @param {String} fileName
 * @param {String} pathToFile
 */
export function selectUploadAssetToClass(fileName, pathToFile, className) {
    cy.log('SELECT OR UPLOAD LOCAL ASSET', fileName, pathToFile);
    return cy.get('.resourcemgr.modal')
        .last()
        .then(resourcemgr => {
            const resourcemgrId = resourcemgr[0].id;
            cy.getSettled(`#${resourcemgrId} .file-browser .mediamanager .root-folder`).should('exist');
            cy.get(`#${resourcemgrId} .file-browser .mediamanager .folders`).contains(className).click();
            cy.get(`#${resourcemgrId} .file-selector .files`).then(root => {
                if (root.find(`li[data-alt="${fileName}"]`).length === 0) {
                    cy.getSettled(`#${resourcemgrId} .file-selector .upload-switcher .upload`).click();
                    cy.fileUpload(
                        `#${resourcemgrId} .file-upload-container input[type="file"][name="content"]`,
                        pathToFile
                    );
                    cy.getSettled(`#${resourcemgrId} .file-upload-container .btn-upload`).click();
                    cy.getSettled(`#${resourcemgrId} .file-upload-container .progressbar.success`, {timeout: 100000}).should('exist');
                }
            });
            cy.getSettled(`#${resourcemgrId} li[data-alt="${fileName}"] .actions a.select`).last().click();
        });
}

/**
 * Add/upload shared stimulus to Item interaction
 * * @param {boolean} isCreatedAsset determines whether the asset is
 * the one previously created (vs imported)
 */
export function selectUploadSharedStimulusToItem(isCreatedAsset, dataAlt, className) {
    cy.log('SELECT OR UPLOAD SHARED STIMULUS',);
    return cy.get('.resourcemgr.modal')
        .last()
        .then(resourcemgr => {
            const resourcemgrId = resourcemgr[0].id;
            cy.getSettled(`#${resourcemgrId} .file-browser .root-folder`).should('exist');
            cy.getSettled(`.mediamanager .folders .root`).should('exist');
            cy.get(`#${resourcemgrId} .file-browser .mediamanager .folders`).contains(className).click();
            cy.getSettled(`.file-selector .files  [data-alt="${dataAlt}"]`).should('exist');
            if(isCreatedAsset){
                cy.getSettled(`#${resourcemgrId} ul > li[data-type="html"]`)
                    .first()
                    .click();
                cy.get(`#${resourcemgrId} li > .actions a.select`)
                    .first()
                    .click();
            } else {
                cy.getSettled(`#${resourcemgrId} ul > li[data-type="html"]`)
                    .last()
                    .click();
                cy.get(`#${resourcemgrId} li > .actions a.select`)
                    .last()
                    .click();
            }
            cy.getSettled('[class="qti-include"] div').should('exist');
        });
}
