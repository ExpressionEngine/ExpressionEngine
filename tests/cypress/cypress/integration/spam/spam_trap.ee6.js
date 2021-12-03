/// <reference types="Cypress" />

import SpamTrap from '../../elements/pages/addons/SpamTrap';
import AddonManager from '../../elements/pages/addons/AddonManager';

const page = new SpamTrap
const addon_manager = new AddonManager;

context('Spam Module', () => {

  before(function(){
    cy.task('db:seed')
  })

  context('Installation', () => {

    it('can install from addon manager', () => {
      cy.auth();
      addon_manager.load()
      cy.intercept('https://updates.expressionengine.com/check').as('check')
      cy.intercept('**/license/handleAccessResponse').as('license')
      addon_manager.get('wrap').find('a[data-post-url*="cp/addons/install/spam"]').click()
      cy.wait('@check')
      cy.wait('@license')
      cy.hasNoErrors()
    })
  })


  context('Spam Trap Table', () => {

    before(function() {
      // preload the spam trap
      cy.task('db:load', '../../support/spam/spam.sql');
    })

    beforeEach(function() {

      cy.auth();
      page.load()

      //page.displayed?
      page.get('page_heading').contains('All SPAM')
      page.get('keyword_search').should('exist')

    })

    it('can search by phrases', () => {
      page.get('keyword_search').clear().type('about')
      page.get('keyword_search').type('{enter}')
      cy.hasNoErrors()

      page.get('page_heading').contains('Found 5 results for "about"')
      page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal('about') })
      page.get('wrap').contains('about')
    })

    it('can mark as spam', () => {
      page.get('wrap').find('input[type="checkbox"]').eq(0).check()
      page.get('wrap').find('input[type="checkbox"]').eq(1).check()
      page.get('bulk_action').should('be.visible')
      page.get('bulk_action').select("approve")
      page.get('action_submit_button').click()
      cy.hasNoErrors()
    })

    

  })

})
