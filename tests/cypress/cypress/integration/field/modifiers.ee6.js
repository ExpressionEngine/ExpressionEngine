/// <reference types="Cypress" />

import Category from '../../elements/pages/channel/Category';
const page = new Category;
const { _, $ } = Cypress

context('Categories', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.task('filesystem:delete', '../../system/user/config/stopwords.php')

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/default_site/' })

        cy.authVisit('admin.php?/cp/design')
    })

    after(function() {
        cy.task('filesystem:delete', '../../system/user/templates/default_site/modifiers.group')
        cy.task('filesystem:delete', '../../system/user/config/stopwords.php')
    })

    it('trim modifier in templates', function() {
        cy.visit('index.php/modifiers/limit')

        cy.get('.no-trim').invoke('text').should('eq', '		Hello, world!	')

        cy.get('.on-trim').invoke('text').should('eq', 'Hello, world!');

		cy.get('.on-trim--characters').invoke('text').should('eq', 'o Wor');
    })

})
