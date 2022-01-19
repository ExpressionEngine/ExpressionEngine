/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';

const addon_manager = new AddonManager;

context('Search', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
    cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/default_site/' })
    cy.authVisit('admin.php?/cp/design')
  })

  it('search and get results', function(){
    cy.visit('index.php/search/simple_form');
    cy.get('#keywords').clear().type('ExpressionEngine')
    cy.get('.submit').first().click()
    cy.get('h3:contains(Results)').should('exist')
    cy.get('body').should('contain', 'Getting to Know ExpressionEngine')
  })

  it('search and get no results', function(){
    cy.authVisit('index.php/search/simple_form');
    cy.get('#keywords').clear().type('WordPress')
    cy.get('.submit').first().click()
    cy.get('h3:contains(Results)').should('not.exist')
    cy.get('body').should('not.contain', 'Getting to Know ExpressionEngine')
    cy.get('body').should('contain', 'Nothing found')
  })

})
