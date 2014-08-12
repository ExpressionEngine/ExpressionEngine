require './bootstrap.rb'

feature 'Communicate > Sent' do

	before(:each) do
		cp_session
		@page = CommunicateSent.new
		@page.load

		@page.should be_displayed
		@page.title.text.should eq 'Sent e-mails'
		@page.should have_phrase_search
		@page.should have_search_submit_button
		@page.should have_email_table
		@page.should have_bulk_action
		@page.should have_action_submit_button
	end

	it 'shows the sent e-mails page (with no results)' do
		@page.should have_no_results
	end

end