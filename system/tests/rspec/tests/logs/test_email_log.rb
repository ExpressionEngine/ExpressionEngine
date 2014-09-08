require './bootstrap.rb'

feature 'Email Log' do

  before(:each) do
  	cp_session

  	@page = EmailLog.new
  	add_member(username: 'johndoe')
  end

  before(:each, :pregen => true) do
	@page.generate_data(count: 150, timestamp_min: 26)
	@page.generate_data(count: 15, member_id: 2, member_name: 'johndoe', timestamp_min: 25)
	@page.load

	# These should always be true at all times if not something has gone wrong
	@page.displayed?
	@page.heading.text.should eq 'e-mail Logs'
	@page.should have_phrase_search
	@page.should have_submit_button
	@page.should have_username_filter
	@page.should have_date_filter
	@page.should have_perpage_filter
  end

  it 'shows the e-mail Logs page', :pregen => true do
	@page.should have_remove_all
	@page.should have_pagination

	@page.perpage_filter.text.should eq "show (20)"

	@page.should have(6).pages
	@page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

	@page.should have(20).items # Default is 20 per page
  end

  it 'does not show filters at 10 items', :pregen => false do
  	@page.generate_data(count: 10)
  	@page.load

	@page.displayed?
	@page.heading.text.should eq 'e-mail Logs'
	@page.should have_phrase_search
	@page.should have_submit_button
  	@page.should_not have_username_filter
  	@page.should_not have_date_filter
  	@page.should_not have_perpage_filter
	@page.should_not have_pagination
  end

  # Confirming phrase search
  it 'searches by phrases', :pregen => true do
  	our_subject = "Rspec entry for search"

  	@page.generate_data(count: 1, timestamp_max: 0, subject: our_subject)
  	@page.load

	# Be sane and make sure it's there before we search for it
	@page.should have_text our_subject

	@page.phrase_search.set "Rspec"
	@page.submit_button.click
	no_php_js_errors

	@page.heading.text.should eq 'Search Results we found 1 results for "Rspec"'
	@page.phrase_search.value.should eq "Rspec"
	@page.should have_text our_subject
	@page.should have(1).items
  end

  # Confirming individual filter behavior
  it 'filters by username', :pregen => true do
	@page.username_filter.click
	@page.wait_until_username_filter_menu_visible
	@page.username_filter_menu.click_link "johndoe"
	no_php_js_errors

	@page.username_filter.text.should eq "username (johndoe)"
	@page.should have(15).items
	@page.should_not have_pagination
  end

  it 'filters by custom username', :pregen => true do
	@page.username_filter.click
	@page.wait_until_username_manual_filter_visible
	@page.username_manual_filter.set "johndoe"
	@page.submit_button.click
	no_php_js_errors

	@page.username_filter.text.should eq "username (johndoe)"
	@page.should have(15).items
	@page.should_not have_pagination
  end

  it 'filters by date', :pregen => true do
	@page.generate_data(count: 19, timestamp_max: 22)
	@page.load

	@page.date_filter.click
	@page.wait_until_date_filter_menu_visible
	@page.date_filter_menu.click_link "Last 24 Hours"
	no_php_js_errors

	@page.date_filter.text.should eq "date (Last 24 Hours)"
	@page.should have(19).items
  end

  it 'can change page size', :pregen => true do
	@page.perpage_filter.click
	@page.wait_until_perpage_filter_menu_visible
	@page.perpage_filter_menu.click_link "25"
	no_php_js_errors

	@page.perpage_filter.text.should eq "show (25)"
	@page.should have(25).items
	@page.should have_pagination
	@page.should have(6).pages
	@page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
  end

  it 'can set a custom limit', :pregen => true do
	@page.perpage_filter.click
	@page.wait_until_perpage_manual_filter_visible
	@page.perpage_manual_filter.set "42"
	@page.submit_button.click
	no_php_js_errors

	@page.perpage_filter.text.should eq "show (42)"
	@page.should have(42).items
	@page.should have_pagination
	@page.should have(6).pages
	@page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
  end

  # Confirming combining filters work
  it 'can combine username and page size filters', :pregen => true do
	@page.perpage_filter.click
	@page.wait_until_perpage_filter_menu_visible
	@page.perpage_filter_menu.click_link "150"
	no_php_js_errors

	# First, confirm we have both 'admin' and 'johndoe' on same page
	@page.perpage_filter.has_select?('perpage', :selected => "150 results")
	@page.should have(150).items
	@page.should have_pagination
	@page.should have_text "johndoe"
	@page.should have_text "admin"

	# Now, combine the filters
	@page.username_filter.click
	@page.wait_until_username_filter_menu_visible
	@page.username_filter_menu.click_link "johndoe"
	no_php_js_errors

	@page.perpage_filter.text.should eq "show (150)"
	@page.username_filter.text.should eq "username (johndoe)"
	@page.should have(15).items
	@page.should_not have_pagination
	@page.items.should_not have_text "admin"
  end

  it 'can combine phrase search with filters', :pregen => true do
	@page.perpage_filter.click
	@page.wait_until_perpage_filter_menu_visible
	@page.perpage_filter_menu.click_link "150"
	no_php_js_errors

  	# First, confirm we have both 'admin' and 'johndoe' on same page
	@page.perpage_filter.text.should eq "show (150)"
  	@page.should have(150).items
  	@page.should have_pagination
  	@page.should have_text "johndoe"
  	@page.should have_text "admin"

	# Now, combine the filters
	@page.phrase_search.set "johndoe"
	@page.submit_button.click
	no_php_js_errors

	@page.perpage_filter.text.should eq "show (150)"
	@page.heading.text.should eq 'Search Results we found 15 results for "johndoe"'
	@page.phrase_search.value.should eq "johndoe"
	@page.should have(15).items
	@page.should_not have_pagination
	@page.items.should_not have_text "admin"
  end

  # Confirming the log deletion action
  it 'can remove a single entry', :pregen => true do
	our_subject = "Rspec entry to be deleted"

	@page.generate_data(count: 1, timestamp_max: 0, subject: our_subject)
	@page.load

	log = @page.find('section.item-wrap div.item', :text => our_subject)
	log.find('li.remove a').click
	no_php_js_errors

	@page.should have_alert
	@page.should have_no_content our_subject
  end

  it 'can remove all entries', :pregen => true do
	@page.remove_all.click
	no_php_js_errors

	@page.should have_alert
	@page.should have_no_results
	@page.should_not have_pagination
  end

  it 'can display a single email', :pregen => true do
  	our_subject = "Rspec entry to be displayed"

  	@page.generate_data(count: 1, timestamp_max: 0, subject: our_subject)
  	@page.load

  	log = @page.find('section.item-wrap div.item', :text => our_subject)
	log.find('div.message p a').click
	no_php_js_errors

	@page.should have_selector('ul.breadcrumb')
	@page.heading.text.should eq 'e-mail: ' + our_subject
  end

  # Confirming Pagination behavior
  it 'shows the Prev button when on page 2', :pregen => true do
	click_link "Next"
	no_php_js_errors

	@page.should have_pagination
	@page.should have(7).pages
	@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
  end

  it 'does not show Next on the last page', :pregen => true do
	click_link "Last"
	no_php_js_errors

	@page.should have_pagination
	@page.should have(6).pages
	@page.pages.map {|name| name.text}.should == ["First", "Previous", "7", "8", "9", "Last"]
  end

  it 'does not lose a filter value when paginating', :pregen => true do
	@page.perpage_filter.click
	@page.wait_until_perpage_filter_menu_visible
	@page.perpage_filter_menu.click_link "25"
	no_php_js_errors

	@page.perpage_filter.text.should eq "show (25)"
	@page.should have(25).items

	click_link "Next"
	no_php_js_errors

	@page.perpage_filter.text.should eq "show (25)"
	@page.should have(25).items
	@page.should have_pagination
	@page.should have(7).pages
	@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
  end

  it 'will paginate phrase search results', :pregen => true do
  	@page.generate_data(count: 20, member_id: 2, member_name: 'johndoe', timestamp_min: 25)

	@page.perpage_filter.click
	@page.wait_until_perpage_filter_menu_visible
	@page.perpage_filter_menu.click_link "25"
	no_php_js_errors

  	@page.phrase_search.set "johndoe"
  	@page.submit_button.click
	no_php_js_errors

  	# Page 1
	@page.heading.text.should eq 'Search Results we found 35 results for "johndoe"'
  	@page.phrase_search.value.should eq "johndoe"
  	@page.items.should_not have_text "admin"
	@page.perpage_filter.text.should eq "show (25)"
  	@page.should have(25).items
  	@page.should have_pagination
  	@page.should have(5).pages
  	@page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]

  	click_link "Next"
	no_php_js_errors

  	# Page 2
	@page.heading.text.should eq 'Search Results we found 35 results for "johndoe"'
  	@page.phrase_search.value.should eq "johndoe"
  	@page.items.should_not have_text "admin"
	@page.perpage_filter.text.should eq "show (25)"
  	@page.should have(10).items
  	@page.should have_pagination
  	@page.should have(5).pages
  	@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
  end
end