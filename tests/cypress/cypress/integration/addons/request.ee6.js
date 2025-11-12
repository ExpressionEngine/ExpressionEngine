/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';

const addon_manager = new AddonManager;

context('Request add-on', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
	  cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' })
    cy.auth();
    addon_manager.load()
    addon_manager.get('first_party_addons').find('.add-on-card:contains("Request") a').click()
    cy.authVisit('admin.php?/cp/design')
  })

  it('XSS filtering is applied', function(){
    cy.authVisit('index.php/request/index?my-var=<script>alert(%27hello%27)</script>');
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

    cy.logFrontendPerformance()
  })

  it('Check template tags', function(){

      cy.visit('index.php/request');//extra visit to get the tracker cookie
      cy.visit('index.php/request/index?my-var=I+love+EE');

      cy.log('exp:request:get')
      cy.get('#get span').invoke('text').should('eq', "I love EE")
      cy.log('exp:request:get_post')
      cy.get('#get_post span').invoke('text').should('eq', "I love EE")
      cy.log('exp:request:cookie')
      cy.get('#cookie span').invoke('text').should('contain', "request")
      cy.log('exp:request:ip')
      cy.get('#ip span').invoke('text').should('be.oneOf', ["127.0.0.1", "::1"])
      cy.log('exp:request:user_agent')
      cy.get('#user_agent span').invoke('text').should('contain', "Mozilla")
      cy.log('exp:request:request_header')
      cy.get('#request_header span').invoke('text').should('contain', "text/html")

      cy.logFrontendPerformance()
    })
  })