/// <reference types="Cypress" />

import Members from '../../elements/pages/members/Members';

const page = new Members

context('Member List', () => {

  before(function(){
    cy.task('db:seed')
  })
  
  beforeEach(function() {
    cy.auth();

    page.load()
    cy.hasNoErrors()
  })

  it('shows the Member List page', () => {
    page.get('keyword_search').should('exist')
    page.get('member_table').should('exist')
  })

  // Confirming phrase search
  it('searches by phrases', () => {
    page.get('keyword_search').clear().type('banned1{enter}')
    cy.hasNoErrors()

    //page.get('heading').contains('we found 1 results for "banned1"')
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal('banned1')})
    page.get('wrap').contains('banned1')
    page.get('members').should('have.length', 1)
  })

  it('shows no results on a failed search', () => {
    page.get('keyword_search').clear().type('Bigfoot{enter}')

    //page.get('heading').contains('we found 0 results for "Bigfoot"')
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal('Bigfoot')})
    page.get('no_results').should('exist')
    page.get('pagination').should('not.exist')
  })

   it('displays an itemized modal when attempting to remove 1 member', () => {
    page.get('usernames').first().invoke('text').then((member_name) => {
      page.get('members').first().find('input[type="checkbox"]').check()
      page.get('bulk_action').should('be.visible')
      page.get('bulk_action').select("Delete")
      page.get('action_submit_button').click()

      page.get('modal').should('be.visible')
      page.get('modal_title').invoke('text').then((text) => { expect(text).to.be.equal("Are You Sure?")})
      page.get('modal').contains("You are attempting to delete the following items")
      page.get('modal').contains(member_name)
      page.get('modal').find('.checklist li').should('have.length', 1)
    })
  })
})
