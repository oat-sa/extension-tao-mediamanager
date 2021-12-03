/**
 * Imports resource in class (class should already be selected before running this command)
 * @param {String} importSelector - css selector for the import button
 * @param {String} importFilePath - path to the file to import
 * @param {String} importUrl - url for the resource import POST request
 * @param {String} className
 */
export function importSelectedAsset(importSelector, importFilePath, importUrl, className) {
    cy.log('COMMAND: import', importUrl);
    cy.get(importSelector).click();

    cy.readFile(importFilePath, 'binary')
        .then(fileContent => {
            cy.get('input[type="file"][name="content"]')
                .attachFile({
                        fileContent,
                        filePath: importFilePath,
                        encoding: 'binary',
                        lastModified: new Date().getTime()
                    }
                );
            cy.get('.progressbar.success').should('exist');
            cy.intercept('POST', `**/${importUrl}**`).as('import').get('.form-toolbar button')
                .click()
            cy.get('.actions [data-trigger="continue"]').click();

            return cy.isElementPresent('.task-report-container')
                .then(isTaskStatus => {
                    if (isTaskStatus) {
                        cy.get('.feedback-success.hierarchical').should('exist');
                    } else {
                        // task was moved to the task queue (background)
                        cy.get('.badge-component').click();
                        cy.get('.task-element.completed').first().contains(className);
                        // close the task manager
                        cy.get('.badge-component').click();
                  }
             })
        });
        cy.log('Asset imported');
}
