require './bootstrap.rb'

feature 'Search Log' do

	before(:each) do
		cp_session

		@page = SearchLog.new
		@page.generate_data(count: 150, timestamp_min: 26)
		@page.generate_data(count: 35, member_id: 2, screen_name: 'johndoe', timestamp_min: 25)
		add_member(username: 'johndoe')
		@page.load

		# These should always be true at all times if not something has gone wrong
		@page.displayed?
		@page.title.text.should eq 'Search Logs'
		@page.should have_phrase_search
		@page.should have_submit_button
		@page.should have_username_filter
		# @page.should have_site_filter # This will not be present if MSM is diabled or we are running Core
		@page.should have_date_filter
		@page.should have_perpage_filter
	end

	it 'shows the Search Logs page' do
		@page.should have_remove_all
		@page.should have_pagination

		@page.perpage_filter.value.should eq "50"

		@page.should have(6).pages
		@page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

		@page.should have(50).items # Default is 50 per page
	end

	# Confirming phrase search
	# @TODO pending phrase search working

	# Confirming individual filter behavior
	it 'filters by username' do
		@page.username_filter.select "johndoe"
		@page.submit_button.click

		@page.username_filter.has_select?('filter_by_username', :selected => "johndoe")
		@page.should have(35).items
		@page.should_not have_pagination
	end

	# @TODO Need data for extra site in order to filter by it
	# it 'filters by site' do
	#		@page.site_filter.select "foobarbaz"
	#		@page.submit_button.click
	#
	#		@page.should have(x).items
	# end

	# Since this logs in a user we should have 1 entry!
	it 'filters by date' do
		@page.generate_data(count: 23, timestamp_max: 22)
		@page.load

		@page.date_filter.select "Last 24 Hours"
		@page.submit_button.click

		@page.date_filter.has_select?('filter_by_date', :selected => "Last 24 Hours")
		@page.should have(23).items
	end

	it 'can change page size' do
		@page.perpage_filter.select "25 results"
		@page.submit_button.click

		@page.perpage_filter.has_select?('perpage', :selected => "25 results")
		@page.should have(25).items
		@page.should have_pagination
		@page.should have(6).pages
		@page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
	end

	# Confirming combining filters work
	it 'can combine username and page size filters' do
		@page.perpage_filter.select "150 results"
		@page.submit_button.click

		# First, confirm we have both 'admin' and 'johndoe' on same page
		@page.perpage_filter.has_select?('perpage', :selected => "150 results")
		@page.should have(150).items
		@page.should have_pagination
		@page.should have_text "johndoe"
		@page.should have_text "admin"

		# Now, combine the filters
		@page.perpage_filter.select "150 results"
		@page.username_filter.select "johndoe"
		@page.submit_button.click

		@page.perpage_filter.has_select?('perpage', :selected => "150 results")
		@page.username_filter.has_select?('filter_by_username', :selected => "johndoe")
		@page.should have(35).items
		@page.should_not have_pagination
		@page.should_not have_text "admin"
	end

	# @TODO pending phrase search working
	# it 'can combine phrase search with filters' do
	# end

	# Confirming the log deletion action
	it 'can remove a single entry' do
		our_terms = "Rspec entry to be deleted"

		@page.generate_data(count: 1, timestamp_max: 0, terms: our_terms)
		@page.load

		log = @page.find('section.item-wrap div.item', :text => our_terms)
		log.find('li.remove a').click

		@page.should have_alert
		@page.should have_no_content our_terms
	end

	it 'can remove all entries' do
		@page.remove_all.click

		@page.should have_alert
		@page.should have_no_results
		@page.should_not have_pagination
	end

	# Confirming Pagination behavior
	it 'shows the Prev button when on page 2' do
		click_link "Next"

		@page.should have_pagination
		@page.should have(7).pages
		@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
	end

	it 'does not show Next on the last page' do
		click_link "Last"

		@page.should have_pagination
		@page.should have(6).pages
		@page.pages.map {|name| name.text}.should == ["First", "Previous", "2", "3", "4", "Last"]
	end

	it 'does not lose a filter value when paginating' do
		@page.perpage_filter.select "25 results"
		@page.submit_button.click

		@page.perpage_filter.has_select?('perpage', :selected => "25 results")
		@page.should have(25).items

		click_link "Next"

		@page.perpage_filter.has_select?('perpage', :selected => "25 results")
		@page.should have(25).items
		@page.should have_pagination
		@page.should have(7).pages
		@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
	end

end