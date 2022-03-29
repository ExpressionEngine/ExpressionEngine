/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';

const addon_manager = new AddonManager;

context('Request', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
	  cy.task('filesystem:copy', { from: 'support/templates/default_site/*', to: '../../system/user/templates/' })
    cy.auth();
    addon_manager.load()
    addon_manager.get('first_party_addons').find('.add-on-card:contains("Request") a').click()
    cy.authVisit('admin.php?/cp/design')
  })

  it('check XSS filter', function(){
    cy.visit('index.php/request/index?my-var=<script>alert(%27hello%27)</script>');
    cy.get('#get span').invoke('text').should('contain', "[removed]")
    cy.get('#get span').invoke('text').should('contain', "alert")
    cy.get('#get span').invoke('text').should('contain', "'hello'")
    cy.get('#get span').invoke('text').should('not.contain', "(")
    cy.get('#get span').invoke('text').should('not.contain', "script")

    cy.get('#get_post span').invoke('text').should('contain', "[removed]")
    cy.get('#get_post span').invoke('text').should('contain', "alert")
    cy.get('#get_post span').invoke('text').should('contain', "'hello'")
    cy.get('#get_post span').invoke('text').should('not.contain', "(")
    cy.get('#get_post span').invoke('text').should('not.contain', "script")
  })

  context('check all tags', function(){
    before(function() {
      cy.visit('index.php/request');//extra visit to get the tracker cookie
      cy.visit('index.php/request/index?my-var=I+love+EE');
    })
    it('get', function(){
      cy.get('#get span').invoke('text').should('eq', "I love EE")
    })
    it('get_post', function(){
      cy.get('#get_post span').invoke('text').should('eq', "I love EE")
    })
    it('cookie', function(){
      cy.get('#cookie span').invoke('text').should('contain', "request")
    })
    it('ip', function(){
      cy.get('#ip span').invoke('text').should('be.oneOf', ["127.0.0.1", "::1"])
    })
    it('user_agent', function(){
      cy.get('#user_agent span').invoke('text').should('contain', "Mozilla")
    })
    it('request_header', function(){
      cy.get('#request_header span').invoke('text').should('contain', "text/html")
    })
  })


})
