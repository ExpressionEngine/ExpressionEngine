/// <reference types="Cypress" />
const { _, $ } = Cypress


context('Front-end login', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.eeConfig({ item: 'require_captcha', value: 'n' })

        //copy templates
		cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
    })


    it('login on front-end', function() {
        cy.clearCookies()
        cy.visit('index.php/members/login');
        cy.get('.sidebar').should('contain', 'Logged out')
        cy.logFrontendPerformance()
        cy.intercept("**?ACT=**").as('ajax')
        cy.get('input[name=username]').clear().type('admin');
        cy.get('input[name=password]').clear().type('password');
        cy.get('input[name=submit]').click();
        cy.hasNoErrors();
        cy.get('body').should('not.contain', 'errors were encountered')

        cy.get('.sidebar').should('not.contain', 'Logged out')
        cy.get('.sidebar a').contains('Logout').click()
        cy.get('button').contains('Logout').click()
        cy.hasNoErrors();
        cy.get('body').should('not.contain', 'errors were encountered')
        cy.get('.sidebar').should('contain', 'Logged out')
    })

    it('login on front-end using email', function() {
        cy.clearCookies()
        cy.visit('index.php/members/login');
        cy.get('.sidebar').should('contain', 'Logged out')
        cy.logFrontendPerformance()
        cy.intercept("**?ACT=**").as('ajax')
        cy.get('input[name=username]').clear().type('cypress@expressionengine.com');
        cy.get('input[name=password]').clear().type('password');
        cy.get('input[name=submit]').click();
        cy.hasNoErrors();
        cy.get('body').should('not.contain', 'errors were encountered')

        cy.get('.sidebar').should('not.contain', 'Logged out')
        cy.get('.sidebar a').contains('Logout').click()
        cy.get('button').contains('Logout').click()
        cy.hasNoErrors();
        cy.get('body').should('not.contain', 'errors were encountered')
        cy.get('.sidebar').should('contain', 'Logged out')
    })

})
