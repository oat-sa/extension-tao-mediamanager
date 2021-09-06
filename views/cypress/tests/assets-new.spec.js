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

/**
 * PoC for attempting to use direct request through endpoints to the BE instead of using cypress UI commands to set up state before test-cases.
 * This has proven to be time-consuming, non-reusable (due to a lack of standard in the requests for BE) and quite complex.
 */

import urls from '../utils/urls';
import selectors from '../utils/selectors';

function waitPageLoad() {
        cy.visit(urls.assets);
        cy.wait('@treeRender', { requestTimeout: 10000 });
        cy.get(`${selectors.root} a`)
            .first()
            .click();
        cy.wait('@editClassLabel', { requestTimeout: 10000 });
}

function getCSRFTokenByHTML(htmlContent) {
    return getTokenByHTML(
        /<input( +)type='hidden'( +)name='X-CSRF-Token'( +)id='X-CSRF-Token'( +)value=".*"( +)\/>/gm,
        htmlContent
    );
}

function getSignatureByHTML(htmlContent) {
    return getTokenByHTML(
        /<input( +)type='hidden'( +)name='signature'( +)id='signature'( +)value=".*"( +)\/>/gm,
        htmlContent
    );
}

function getTokenByHTML(regex, htmlContent) {
    let match;

    while ((match = regex.exec(htmlContent)) !== null) {
        // This is necessary to avoid infinite loops with zero-width matches
        if (match.index === regex.lastIndex) {
            regex.lastIndex++;
        }

        const htmlElement = match[0];
        const subRegex = /value=".*"/gm;
        const occurrences = subRegex.exec(htmlElement);

        if (occurrences !== null) {
            let token = occurrences[0].replace('value="', '');

            token = token.substr(0, token.indexOf('"'));

            return token;
        }
    }

    return null;
}

describe('Independent Assets test', () => {
    const className = 'Asset E2E class';
    const classMovedName = 'Asset E2E class Moved';
    let csrf = '';
    let tokenHandler;
    let signature;
    let classUri;

    before(() => {
        cy.loginAsAdmin();
    });

    beforeEach(() => {
        cy.intercept('GET', `**/${ selectors.treeRenderUrl }/getOntologyData**`).as('treeRender');
        cy.intercept('POST', `**/${ selectors.editClassLabelUrl }`).as('editClassLabel');

        waitPageLoad();

        cy.window()
        .then(async (win) => {
            /**
             * This approach requires to expose the tokenHandler built in TAO from request.js file into window.__tokenHandler__
             */
            tokenHandler = win.__tokenHandler__;

            if (Cypress.$(`${selectors.root} a:contains(${className})`).length) {
                csrf = await tokenHandler.getToken();
                let existingClass = Cypress.$(`${selectors.root} a:contains(${className})`).parent().data();

                cy.request({
                    method: 'POST',
                    url: 'https://bosa.docker.localhost/taoMediaManager/MediaManager/deleteClass',
                    form: true,
                    headers: {
                        'x-requested-with': 'XMLHttpRequest',
                        'x-csrf-token': csrf,
                        'content-type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: {
                        classUri: existingClass.uri,
                        id: existingClass.uri,
                        signature: existingClass.signature,
                    }
                })

                waitPageLoad();
            }
        });
    })

    describe('Asset creation', () => {
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

    describe('Asset moving', () => {
        beforeEach(() => {
            cy.server();
            cy.window()
            .then(async (win) => {
                tokenHandler = win.__tokenHandler__;
                csrf = await tokenHandler.getToken();
                let rootClass = Cypress.$(`${selectors.root} a`).first().parent().data();

                cy.request({
                    method: 'POST',
                    url: 'https://bosa.docker.localhost/taoMediaManager/MediaManager/addSubClass',
                    form: true,
                    headers: {
                        'x-requested-with': 'XMLHttpRequest',
                        'x-csrf-token': csrf,
                        'content-type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: {
                        type: 'class',
                        id: rootClass.uri,
                        signature: rootClass.signature,
                    }
                }).then((response) => {
                    classUri = response.body.uri

                    waitPageLoad();
                    cy.route({
                        method: 'POST',
                        url: `**/${ selectors.editClassLabelUrl }`,
                        body: {
                            classUri: classUri,
                        },
                    }).as('newClass');

                    cy.getSettled(`${selectors.root} #${classUri} a`)
                    .first()
                    .click();

                    cy.wait('@newClass').then((interception) => {
                        signature = getSignatureByHTML(interception.response.body)
                        csrf = getCSRFTokenByHTML(interception.response.body)

                        cy.request({
                            method: 'POST',
                            url: 'https://bosa.docker.localhost/taoMediaManager/MediaManager/editClassLabel',
                            form: true,
                            headers: {
                                'x-requested-with': 'XMLHttpRequest',
                                'content-type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            body: {
                                'form_1_sent': 1,
                                'http_2_www_0_w3_0_org_1_2000_1_01_1_rdf-schema_3_label': 'Asset E2E class',
                                'classUri': classUri,
                                'signature': signature,
                                'X-CSRF-Token': csrf,
                                'Save': 'Save'
                            }
                        })
                    });

                    waitPageLoad()
                })
            })
        })

        it('empty test, it just creates and renames a class', function () {

        });
    })
});