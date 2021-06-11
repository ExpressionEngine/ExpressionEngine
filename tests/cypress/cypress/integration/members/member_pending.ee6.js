/// <reference types="Cypress" />

import PendingMembers from '../../elements/pages/members/PendingMembers';

const page = new PendingMembers

context('Pending Member List', () => {
  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();

    page.load()
    cy.hasNoErrors()
  })

  it('shows the Pending Member List page', () => {
    page.get('keyword_search').should('exist')
    page.get('member_table').should('exist')
    page.get('members').should('have.length', 2)
  })

  // Confirming phrase search
  it('searches by phrases', () => {
    page.get('keyword_search').clear().type('pending1')
    page.get('keyword_search').type('{enter}')
    cy.hasNoErrors()

    //page.get('heading').contains('we found 1 results for "pending1"')
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal('pending1')})
    page.get('wrap').contains('pending1')
    page.get('members').should('have.length', 1)
  })

  it('shows no results on a failed search', () => {
    page.get('keyword_search').clear().type('admin')
    page.get('keyword_search').type('{enter}')

    //page.get('heading').contains('we found 0 results for "admin"')
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal('admin')})
    page.get('no_results').should('exist')
    page.get('pagination').should('not.exist')
  })

   it('displays an itemzied modal when attempting to decline 1 member', () => {
    page.get('usernames').first().invoke('text').then((member_name) => {

      page.get('members').first().find('input[type="checkbox"]').check()
      page.get('bulk_action').should('be.visible')
      page.get('bulk_action').select("Decline")
      page.get('action_submit_button').click()

      page.get('modal').should('be.visible')
      page.get('modal_title').invoke('text').then((text) => { expect(text.trim()).to.be.equal("Confirm Decline")})
      page.get('modal').contains("You are attempting to decline the following members. This will remove them, please confirm this action.")
      page.get('modal').contains(member_name)
      page.get('modal').find('.checklist li').should('have.length', 1)
    })
  })

  it('can decline a single pending member', () => {
    page.get('usernames').first().invoke('text').then((member_name) => {

      page.get('members').first().find('input[type="checkbox"]').check()
      page.get('bulk_action').should('be.visible')
      page.get('bulk_action').select("Decline")
      page.get('action_submit_button').click()
      page.get('modal').should('be.visible')
      //page.get('modal_submit_button').click() // Submits a form AJ
      cy.get('[value="Confirm, and Decline"]').click()
      cy.hasNoErrors()

      page.get('alert').should('be.visible')
      page.get('alert').contains("Member Declined")
      page.get('alert').contains("The member "+member_name+" has been declined.")
    })
  })
})
