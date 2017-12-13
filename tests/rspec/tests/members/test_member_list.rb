require './bootstrap.rb'

feature 'Member List' do
  before(:each) do
    cp_session
    @page = Members.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Member List page' do
    @page.should have_keyword_search
    @page.should have_member_table
  end

  # Confirming phrase search
  it 'searches by phrases' do
    @page.keyword_search.set 'banned1'
    @page.keyword_search.send_keys(:enter)
    no_php_js_errors

    @page.heading.text.should eq 'Search Results we found 1 results for "banned1"'
    @page.keyword_search.value.should eq 'banned1'
    @page.should have_text 'banned1'
    @page.should have(1).members
  end

  it 'shows no results on a failed search'  do
    @page.keyword_search.set 'Bigfoot'
    @page.keyword_search.send_keys(:enter)

    @page.heading.text.should eq 'Search Results we found 0 results for "Bigfoot"'
    @page.keyword_search.value.should eq 'Bigfoot'
    @page.should have_no_results
    @page.should_not have_pagination
  end

   it 'displays an itemized modal when attempting to remove 1 member' do
    member_name = @page.usernames[0].text

    @page.members[0].find('input[type="checkbox"]').set true
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
