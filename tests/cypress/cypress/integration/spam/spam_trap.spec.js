/// <reference types="Cypress" />

import SpamTrap from '../../elements/pages/addons/SpamTrap';
import AddonManager from '../../elements/pages/addons/AddonManager';

const page = new SpamTrap
const addon_manager = new AddonManager;

context('Spam Module', () => {

  beforeEach(function(){
    cy.task('db:seed')
  })

  context('Installation', () => {

    it('can install from addon manager', () => {
      cy.auth();
      addon_manager.load()
      const spam_row = addon_manager.get('wrap').find('div.tbl-wrap table tr:contains("Spam")')
      spam_row.find('a:contains("Install")').click()

      cy.hasNoErrors()
    })
  })


  context('Spam Trap Table', () => {

    beforeEach(function() {
      cy.auth();

      // preload the spam trap
      cy.task('db:load', '../../support/spam/spam.sql')

      page.load()

      //page.displayed?
      page.get('heading').contains('All SPAM')
      page.get('keyword_search').should('exist')
    })

    it('can search by phrases', () => {
      page.get('keyword_search').clear().type('about')
      page.get('keyword_search').type('{enter}')
      cy.hasNoErrors()

      page.get('heading').contains('we found 5 results for "about"')
      page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal('about') })
      page.get('wrap').contains('about')
    })

    it('can mark as ham', () => {
      page.get('wrap').find('.check-ctrl input[type="checkbox"]').check()
      page.get('bulk_action').should('be.visible')
      page.get('bulk_action').select("approve")
      page.get('action_submit_button').click()
      cy.hasNoErrors()
    })

    it('can mark as spam', () => {
      page.get('wrap').find('.check-ctrl input[type="checkbox"]').check()
      page.get('bulk_action').should('be.visible')
      page.get('bulk_action').select("approve")
      page.get('action_submit_button').click()
      cy.hasNoErrors()
    })

  })

})
