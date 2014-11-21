require './bootstrap.rb'

feature 'Add-On Manager' do

	before(:each) do
		cp_session

		@page = AddonManager.new
		@page.load

		@page.displayed?
		@page.title.text.should eq 'Add-On Manager'
		@page.should have_phrase_search

		@page.should have_status_filter
		@page.should have_developer_filter
		@page.should have_perpage_filter

		@page.should have_addons

		@page.should have_bulk_action
		@page.should have_action_submit_button
	end

	it 'shows the Add-On Manger' do
		@page.addon_name_header[:class].should eq 'highlight'
	end

end