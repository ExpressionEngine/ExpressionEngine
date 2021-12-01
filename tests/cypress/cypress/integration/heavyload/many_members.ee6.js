/// <reference types="Cypress" />

import MemberGroups from '../../elements/pages/members/MemberGroups';

const page = new MemberGroups

context('Operate the site with many members', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({item: 'ignore_member_stats', value: 'y'}).then(() => {
        for (let step = 0; step < 100; step++) {
            cy.createMembers({n: 500})
        }
    })
  })


  beforeEach(function() {
    cy.auth();
  })

  it('loads the Roles page and has correct data', () => {
    cy.visit('admin.php?/cp/utilities/communicate');
    cy.hasNoErrors()

    cy.visit('admin.php?/cp/utilities/stats')
    cy.hasNoErrors()
    cy.get('.panel-body .app-listing__row:contains("Members")').find('td').eq(1).contains('50017')
    cy.get('.panel-body .app-listing__row:contains("Members")').find('.sync').click()
    page.hasAlert('success')
    page.get('alert').contains("Synchronization Completed")

    cy.visit('admin.php?/cp/members/roles')
    cy.hasNoErrors()
    cy.get('.panel-body .list-item__content:contains("Members")').find('.faded').contains('50012')
  })

  it('preserves default author in channel settings', () => {
    cy.visit('admin.php?/cp/members/roles/edit/5');
    cy.get('button:contains("Website Access")').click()
    cy.get('[type=checkbox][name=include_in_authorlist]').check()
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    cy.visit('admin.php?/cp/channels/edit/1')
    cy.get('button:contains("Settings")').click()
    cy.get('[data-input-value="default_author"] .search-input__input').type('20000')
    cy.wait(2000)
    cy.get('[data-input-value="default_author"] .field-inputs [value="20000"]').check()
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    cy.get('button:contains("Settings")').click()
    cy.get('[data-input-value="default_author"] .field-inputs [value="20000"]').should('be.checked')
  })

  it('preserves author in entry', () => {

    cy.visit('admin.php?/cp/publish/edit/entry/1')
    cy.get('button:contains("Options")').click()
    cy.get('[data-input-value="author_id"] .search-input__input').type('20000')
    cy.wait(2000)
    cy.get('[data-input-value="author_id"] .field-inputs [value="20000"]').check()
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    cy.get('button:contains("Options")').click()
    cy.get('[data-input-value="author_id"] .field-inputs [value="20000"]').should('be.checked')
  })

})
