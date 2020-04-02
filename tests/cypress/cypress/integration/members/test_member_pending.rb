require './bootstrap.rb'

context('Pending Member List', () => {
  beforeEach(function() {
    cy.auth();
    page = PendingMembers.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Pending Member List page', () => {
    page.should have_keyword_search
    page.should have_member_table
    page.should have(2).members
  }

  // Confirming phrase search
  it('searches by phrases', () => {
    page.get('keyword_search').clear().type('pending1'
    page.get('keyword_search').send_keys(:enter)
    cy.hasNoErrors()

    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 1 results for "pending1"'
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal('pending1'
    page.get('wrap').contains('pending1'
    page.get('members').should('have.length', 1)
  }

  it('shows no results on a failed search'  do
    page.get('keyword_search').clear().type('admin'
    page.get('keyword_search').send_keys(:enter)

    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 0 results for "admin"'
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal('admin'
    page.get('no_results').should('exist')
    page.should_not have_pagination
  }

   it('displays an itemzied modal when attempting to decline 1 member', () => {
    member_name = page.usernames[0].text

    page.members[0].find('input[type="checkbox"]').check()
    page.get('bulk_action').should('be.visible')
    page.bulk_action.select "Decline"
    page.get('action_submit_button').click()

    page.get('modal').should('be.visible')
    page.get('modal_title').invoke('text').then((text) => { expect(text).to.be.equal("Confirm Decline"
    page.get('modal').contains("You are attempting to decline the following members. This will remove them, please confirm this action."
    page.get('modal').contains(member_name
    page.get('modal').find('.checklist li').should('have.length', 1)
  }

  it('can decline a single pending member', () => {
    member_name = page.usernames[0].text

    page.members[0].find('input[type="checkbox"]').check()
    page.get('bulk_action').should('be.visible')
    page.bulk_action.select "Decline"
    page.get('action_submit_button').click()
    page.get('modal').should('be.visible')
    page.get('modal_submit_button').click() // Submits a form
    cy.hasNoErrors()

    page.should have_alert
    page.get('alert').contains("Member Declined"
    page.get('alert').contains("The member "+member_name+" has been declined."
  }
}
