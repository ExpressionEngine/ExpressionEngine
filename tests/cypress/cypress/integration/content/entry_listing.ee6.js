
/// <reference types="Cypress" />


context('Entry Listing Page', () => {

    before(function () {
        cy.task('db:seed')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
    })

    it('index and count are correct when paginating', () => {
        cy.visit('/index.php/entries/index')

        cy.hasNoErrors()

        cy.get('.entry').first().find('.count').invoke('text').should('eq', '1')
        cy.get('.entry').first().find('.index').invoke('text').should('eq', '0')
        cy.get('.entry').first().find('.absolute_count').invoke('text').should('eq', '1')
        cy.get('.entry').first().find('.absolute_index').invoke('text').should('eq', '0')

        cy.get('.entry').last().find('.count').invoke('text').should('eq', '5')
        cy.get('.entry').last().find('.index').invoke('text').should('eq', '4')
        cy.get('.entry').last().find('.absolute_count').invoke('text').should('eq', '5')
        cy.get('.entry').last().find('.absolute_index').invoke('text').should('eq', '4')

        cy.get('.pagination .next').click()

        cy.hasNoErrors()

        cy.get('.entry').first().find('.count').invoke('text').should('eq', '1')
        cy.get('.entry').first().find('.index').invoke('text').should('eq', '0')
        cy.get('.entry').first().find('.absolute_count').invoke('text').should('eq', '6')
        cy.get('.entry').first().find('.absolute_index').invoke('text').should('eq', '5')
    })

})
