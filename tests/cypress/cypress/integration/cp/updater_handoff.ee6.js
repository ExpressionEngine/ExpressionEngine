/// <reference types="Cypress" />

// This belongs in the regular CP suite: it checks the web handoff without downloading an update.
context('Updater handoff', () => {
    beforeEach(() => {
        cy.task('db:seed');
    });

    afterEach(() => {
        cy.task('updater:restore_handoff');
    });

    ['c', 'cs', 's'].forEach(sessionType => {
        [false, true].forEach(csrfDisabled => {
            it(`starts with ${sessionType} sessions and CSRF ${csrfDisabled ? 'disabled' : 'enabled'}`, () => {
                cy.task('updater:configure_handoff', { sessionType, csrfDisabled });

                cy.auth();
                cy.get('.ee-sidebar').should('be.visible');
                cy.window().then(win => {
                    expect(win.EE.BASE).to.be.a('string');
                    expect(Boolean(win.EE.CSRF_TOKEN)).to.equal(!csrfDisabled);
                    if (sessionType !== 'c') {
                        expect(win.EE.BASE).to.include('S=');
                    }
                });

                if (sessionType === 's') {
                    // Guest login needs a CSRF cookie; the authenticated handoff must work without any cookies.
                    cy.clearAllCookies();
                    cy.intercept({ url: `${Cypress.config('baseUrl')}**`, middleware: true }, request => {
                        delete request.headers.cookie;
                        request.on('before:response', response => {
                            delete response.headers['set-cookie'];
                        });
                    });
                    cy.getAllCookies().should('be.empty');
                }

                cy.task('updater:install_handoff');
                requestStep().then(response => {
                    expect(response.status, JSON.stringify(response.body)).to.equal(200);
                    expect(response.body.nextStep).to.equal('updateFiles');
                });
                cy.task('filesystem:exists', '../../system/ee/updater/.cypress-step').should('equal', false);

                // The second browser request must acknowledge the prepared credential and reach the runner.
                requestStep().then(response => {
                    expect(response.status, JSON.stringify(response.body)).to.equal(200);
                    expect(response.body).to.deep.equal({
                        messageType: 'success',
                        message: 'Updater handoff verified',
                        nextStep: false
                    });
                });
                cy.readFile('../../system/ee/updater/.cypress-step').should('equal', 'updateFiles');

                if (sessionType === 's') {
                    cy.getAllCookies().should('be.empty');
                }
            });
        });
    });
});

function requestStep() {
    // Use a browser XHR with the existing updater client's URL and CSRF header, not cy.request's cookie jar.
    return cy.window().then(win => new Cypress.Promise(resolve => {
        win.jQuery.ajax({
            type: 'POST',
            url: `${win.EE.BASE}&C=updater&M=run&step=updateFiles`,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': win.EE.CSRF_TOKEN },
            // Assert failed responses below instead of invoking the CP's default throwing error handler.
            error: () => {},
            complete: xhr => resolve({ status: xhr.status, body: xhr.responseJSON || xhr.responseText })
        });
    }));
}
