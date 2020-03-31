require './bootstrap.rb'

context('Member List', () => {
  beforeEach(function() {
    cy.auth();
    page = Members.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Member List page', () => {
    page.should have_keyword_search
    page.should have_member_table
  }

  // Confirming phrase search
  it('searches by phrases', () => {
    page.keyword_search.clear().type('banned1'
    page.keyword_search.send_keys(:enter)
    cy.hasNoErrors()

    page.heading.text.should eq 'Search Results we found 1 results for "banned1"'
    page.keyword_search.value.should eq 'banned1'
    page.get('wrap').contains('banned1'
    page.should have(1).members
  }

  it('shows no results on a failed search'  do
    page.keyword_search.clear().type('Bigfoot'
    page.keyword_search.send_keys(:enter)

    page.heading.text.should eq 'Search Results we found 0 results for "Bigfoot"'
    page.keyword_search.value.should eq 'Bigfoot'
    page.get('no_results').should('exist')
    page.should_not have_pagination
  }

   it('displays an itemized modal when attempting to remove 1 member', () => {
    member_name = page.usernames[0].text

    page.members[0].find('input[type="checkbox"]').set true
    page.wait_until_bulk_action_visible
    page.bulk_action.select "Remove"
    page.action_submit_button.click()

    page.wait_until_modal_visible
    page.modal_title.text.should eq "Confirm Removal"
    page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
    page.modal.text.should include member_name
    page.modal.all('.checklist li').length.should eq 1
  }
}
