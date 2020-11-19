/// <reference types="Cypress" />

import Homepage from '../../elements/pages/Homepage';
const page = new Homepage;

context('Homepage', () => {

    before(() => {
        cy.task('db:seed')
    })

    beforeEach(() => {
        cy.authVisit(page.url);
    })

    afterEach(() => {
        cy.hasNoErrors();
    })

    describe('when spam module is not installed', function() {
        beforeEach(function() {
            page.toggleSpam('off')
        })

        it('does not show flagged comments', function() {
            page.get('dashboard').find('.dashboard__item').contains("flagged as spam").should('not.exist')
        })
    })

    describe('when spam module is installed', function() {
        beforeEach(function() {
            page.toggleSpam('on')
        })

        it('shows flagged comments', function() {
            page.get('dashboard').find('.dashboard__item').contains("flagged as spam")
        })
    })
})