require './bootstrap.rb'

feature 'Pending Member List' do
  before(:each) do
    cp_session
    @page = PendingMembers.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Pending Member List page' do
    @page.should have_member_search
    @page.should have_member_table
  end

  # Confirming phrase search
  it 'searches by phrases' do
    @page.phrase_search.set 'pending1'
    @page.search_submit_button.click
    no_php_js_errors

    @page.first_party_heading.text.should eq 'Search Results we found 1 results for "pending1"'
    @page.phrase_search.value.should eq 'pending1'
    @page.should have_text 'pending1'
    @page.should have(1).members
  end

  it 'shows no results on a failed search'  do
    @page.phrase_search.set 'admin'
    @page.search_submit_button.click

    @page.first_party_heading.text.should eq 'Search Results we found 0 results for "admin"'
    @page.phrase_search.value.should eq 'admin'
    @page.should have_no_results
    @page.should_not have_pagination
  end

  it 'can decline a single pending member', :all_files => true do
    member_name = @page.title_names[0].text

    @page.pending[1].find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Decline"
    @page.action_submit_button.click
    @page.wait_until_modal_visible
    @page.modal_submit_button.click # Submits a form
    no_php_js_errors

    @page.text.should_not include member_name
  end
end
