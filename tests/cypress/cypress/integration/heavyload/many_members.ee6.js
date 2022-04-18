/// <reference types="Cypress" />

import MemberGroups from '../../elements/pages/members/MemberGroups';

const page = new MemberGroups

context('Operate the site with many members', () => {


  context('500 members', () => {

    before(function(){
      cy.task('db:seed')
      cy.eeConfig({ item: 'show_profiler', value: 'y' })
      cy.createMembers({n: 500})
    })

    beforeEach(function() {
      cy.auth();
    })

    after(function(){
      cy.eeConfig({ item: 'show_profiler', value: 'n' })
    })

    it('preserves default author in channel settings', () => {
      cy.visit('admin.php?/cp/members/roles/edit/5');
      cy.get('button:contains("Website Access")').click()
      cy.get('[type=checkbox][name=include_in_authorlist]').check()
      cy.get('body').type('{ctrl}', {release: false}).type('s')
  
      cy.visit('admin.php?/cp/channels/edit/1')
      cy.get('button:contains("Settings")').click()
      cy.get('[data-input-value="default_author"] .search-input__input').type('200')
      cy.get('[data-input-value="default_author"] .field-inputs [value="200"]').check()
      cy.get('body').type('{ctrl}', {release: false}).type('s')
  
      cy.get('button:contains("Settings")').click()
      cy.get('[data-input-value="default_author"] .field-inputs [value="200"]').should('be.checked')
      cy.logCPPerformance()
    })
  
    it('preserves author in entry', () => {
  
      cy.visit('admin.php?/cp/publish/edit/entry/1')
      cy.get('button:contains("Options")').click()
      cy.get('[data-input-value="author_id"] .search-input__input').type('200')
      cy.get('[data-input-value="author_id"] .field-inputs [value="200"]').check()
      cy.get('body').type('{ctrl}', {release: false}).type('s')
  
      cy.get('button:contains("Options")').click()
      cy.get('[data-input-value="author_id"] .field-inputs [value="200"]').should('be.checked')
      cy.logCPPerformance()
    })
  })


  context('50k members', () => {


    before(function(){
      cy.task('db:seed')
      cy.eeConfig({item: 'ignore_member_stats', value: 'y'}).then(() => {
          for (let step = 0; step < 25; step++) {
              cy.createMembers({n: 2000})
          }
      })
    })


    beforeEach(function() {
      cy.auth();
    })

    it('loads the Roles page and has correct data', () => {
      cy.eeConfig({item: 'ignore_member_stats', value: 'y'}).then(() => {

        cy.visit('admin.php?/cp/utilities/communicate');
        page.hasAlert('important')
        page.get('alert').contains("The mumber of members for each role might be inaccurate")
        cy.hasNoErrors()
        cy.logCPPerformance()

        cy.visit('admin.php?/cp/utilities/stats')
        cy.hasNoErrors()
        cy.get('.panel-body .app-listing__row:contains("Members")').find('td').eq(1).contains('50007')
        cy.get('.panel-body .app-listing__row:contains("Members")').find('.sync').click()
        page.hasAlert('success')
        page.get('alert').contains("Synchronization Completed")

        cy.visit('admin.php?/cp/members/roles')
        cy.hasNoErrors()
        cy.get('.panel-body .list-item__content:contains("Members")').find('.faded').contains('50002')
        page.hasAlert('important')
        page.get('alert').contains("The mumber of members for each role might be inaccurate")
      })
    })

    it('no warnings if override is not set', () => {
      cy.eeConfig({item: 'ignore_member_stats', value: 'n'}).then(() => {

        cy.visit('admin.php?/cp/utilities/communicate');
        cy.screenshot({capture: 'fullPage'});
        cy.get('.app-notice:visible').its('length').should('eq', 1)
        cy.hasNoErrors()

        cy.visit('admin.php?/cp/members/roles')
        page.get('alert').should('not.exist')
        cy.hasNoErrors()
        cy.logCPPerformance()

        cy.eeConfig({item: 'ignore_member_stats', value: 'y'}).then(() => {

          cy.visit('admin.php?/cp/utilities/communicate');
          cy.get('.app-notice:visible').its('length').should('eq', 2)
          cy.hasNoErrors()

        })
      })
      
    })

  })

  

})
