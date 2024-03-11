/// <reference types="Cypress" />

import Members from '../../elements/pages/members/Members';

const page = new Members

context('Member List in CP', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
    cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
      cy.authVisit('admin.php?/cp/design')
    })
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
  it('search members by keyword', () => {
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
    cy.get('.ee-main__content form .table-responsive table tr td:nth-child(2) div > div > span').first().invoke('text').then((member_name) => {
      page.get('members').first().find('input[type="checkbox"]').check()
      page.get('bulk_action').should('be.visible')
      page.get('bulk_action').select("Delete")
      page.get('action_submit_button').click()

      page.get('modal').should('be.visible')
      page.get('modal_title').invoke('text').then((text) => { expect(text).to.be.equal("Are You Sure?")})
      page.get('modal').contains("You are attempting to delete the following items")
      page.get('modal').contains(member_name)
      page.get('modal').find('.checklist li').should('have.length', 1)
      cy.get('.js-modal-close:visible').first().click()
    })
  })
})

context('Member List frontend', () => {
  before(function() {
    cy.wait(1000)
    cy.task('db:seed')
    //copy templates
    cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
      cy.authVisit('admin.php?/cp/design')
    })
    cy.logout()
  })

  it('check access memberlist permissions', () => {
    cy.visit('index.php/members/memberlist', {failOnStatusCode: false});
    cy.hasNoErrors()
    cy.get('body').should('contain', 'You are not allowed to view member profiles')

    cy.authVisit('admin.php?/cp/members/roles/edit/3');
    cy.get('button:contains("Website Access")').click()
    cy.get('[data-toggle-for="can_view_profiles"]').click()
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    cy.visit('index.php/members/memberlist');
    cy.get('h1').should('contain', 'Member Listing')
    cy.get('tbody tr').its('length').should('eq', 4)
    cy.get('.result').should('not.contain', '{')
    cy.logFrontendPerformance()
    
  })

  it('respects the options', () => {

    cy.visit('index.php/members/memberlist');
    cy.get('[name=role_id]').select("Members")
    cy.get('.submit').click();

    cy.get('tbody tr').its('length').should('eq', 2)
    cy.get('tbody tr').should('not.contain', 'Super Admin')
    cy.logFrontendPerformance()
  })

  it('Check backspace parameter for member_rows', () => {
    cy.auth()
    cy.visit('index.php/members/memberlist-backspace');
    cy.get('div').contains("Admin").should('not.contain', 'Admin**')
  })

  it('the paths are correct', () => {

    cy.visit('index.php/members/memberlist');
    cy.get('[name=role_id]').select("Super Admin")
    cy.get('[name=sort_order]').select('Ascending')
    cy.get('.submit').click();

    cy.get('tbody tr').its('length').should('eq', 2)
    cy.get('tbody tr').eq(1).find('img').should('exist')
    cy.get('tbody tr').eq(1).find('img').invoke('attr', 'src').then((src) => {
      expect(src).to.contain('8bit_kevin.png')
    })
    cy.get('tbody tr').should('not.contain', 'Member')
    cy.logFrontendPerformance()
  })

})
