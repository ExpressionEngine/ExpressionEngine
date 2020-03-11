require './bootstrap.rb'

feature 'Pending Member List', () => {
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
    page.keyword_search.set 'pending1'
    page.keyword_search.send_keys(:enter)
    cy.hasNoErrors()

    page.heading.text.should eq 'Search Results we found 1 results for "pending1"'
    page.keyword_search.value.should eq 'pending1'
    page.should have_text 'pending1'
    page.should have(1).members
  }

  it('shows no results on a failed search'  do
    page.keyword_search.set 'admin'
    page.keyword_search.send_keys(:enter)

    page.heading.text.should eq 'Search Results we found 0 results for "admin"'
    page.keyword_search.value.should eq 'admin'
    page.should have_no_results
    page.should_not have_pagination
  }

   it('displays an itemzied modal when attempting to decline 1 member', () => {
    member_name = page.usernames[0].text

    page.members[0].find('input[type="checkbox"]').set true
    page.wait_until_bulk_action_visible
    page.bulk_action.select "Decline"
    page.action_submit_button.click()

    page.wait_until_modal_visible
    page.modal_title.text.should eq "Confirm Decline"
    page.modal.text.should include "You are attempting to decline the following members. This will remove them, please confirm this action."
    page.modal.text.should include member_name
    page.modal.all('.checklist li').length.should eq 1
  }

  it('can decline a single pending member', () => {
    member_name = page.usernames[0].text

    page.members[0].find('input[type="checkbox"]').set true
    page.wait_until_bulk_action_visible
    page.bulk_action.select "Decline"
    page.action_submit_button.click()
    page.wait_until_modal_visible
    page.modal_submit_button.click() // Submits a form
    cy.hasNoErrors()

    page.should have_alert
    page.alert.text.should include "Member Declined"
    page.alert.text.should include "The member "+member_name+" has been declined."
  }
}
