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

	# Can reverse sort by Add-On name
	# Can sort by Version
	# Can reverse sort by Version
	# Can filter by status
	# Can filter by developer
	# Can adjust the perpage
	# Pagination displays
	# Pagination > Next works
	# Can search
	# "Bad" search returns "no results"
	# Can install a single add-on
	# Can bulk-install add-ons
	# Removing an add-on triggers a modal (and removes, and have a "not-installed" class)
	# The settings buttons "work" (200 response)
	# The guide buttons "work" (200 response)

end