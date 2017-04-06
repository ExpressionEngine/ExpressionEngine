require './bootstrap.rb'

feature 'Banned Member List' do
  before(:each) do
    cp_session
    @page = BansMembers.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Banned Member List page' do
    @page.should have_member_search
    @page.should have_member_table
  end

  # Confirming phrase search
  it 'searches by phrases' do
    @page.phrase_search.set 'banned1'
    @page.search_submit_button.click
    no_php_js_errors

    @page.heading.text.should eq 'Search Results we found 1 results for "banned1"'
    @page.phrase_search.value.should eq 'banned1'
    @page.should have_text 'banned1'
    @page.should have(1).members
  end

  it 'shows no results on a failed search'  do
    @page.phrase_search.set 'admin'
    @page.search_submit_button.click

    @page.heading.text.should eq 'Search Results we found 0 results for "admin"'
    @page.phrase_search.value.should eq 'admin'
    @page.should have_no_results
    @page.should_not have_pagination
  end

   it 'displays an itemzied modal when attempting to remove 1 member' do
    member_name = @page.usernames[0].text

    @page.members[1].find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Remove"
    @page.action_submit_button.click

    @page.wait_until_modal_visible
    @page.modal_title.text.should eq "Confirm Removal"
    @page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
    @page.modal.text.should include member_name
    @page.modal.all('.checklist li').length.should eq 1
  end
end
