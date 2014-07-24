require './bootstrap.rb'

feature 'Throttling Log' do

  before(:each) do
    cp_session

    @page = ThrottleLog.new
    @page.generate_data(count: 150)
    @page.generate_data(count: 100, locked_out: true)
  end

  before(:each, :enabled => false) do
    ee_config(item: 'enable_throttling', value: 'n')
    @page.load

    # These should always be true at all times if not something has gone wrong
    @page.displayed?
    @page.title.text.should eq 'Access Throttling Logs'
    @page.should have_phrase_search
    @page.should have_submit_button
    @page.should have_perpage_filter
  end

  before(:each, :enabled => true) do
    ee_config(item: 'enable_throttling', value: 'y')
    @page.load

    # These should always be true at all times if not something has gone wrong
    @page.displayed?
    @page.title.text.should eq 'Access Throttling Logs'
    @page.should have_phrase_search
    @page.should have_submit_button
    @page.should have_perpage_filter
  end

  it '(disabled) shows the Turn Throttling On button', :enabled => false do
    @page.should have_no_results
    @page.should have_selector('a.action', :text => 'TURN THROTTLING ON')
  end

  it '(enabled) shows the Access Throttling Logs page', :enabled => true do
    @page.should have_remove_all
    @page.should have_pagination

    @page.perpage_filter.value.should eq "50"

    @page.should have(6).pages
    @page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

    @page.should have(50).items # Default is 50 per page
  end

  # Confirming phrase search
  it '(enabled) searches by phrases', :enabled => true do
  	our_ip = "172.16.11.42"

  	@page.generate_data(count: 1, timestamp_max: 0, ip_address: our_ip)
  	@page.load

	# Be sane and make sure it's there before we search for it
	@page.should have_text our_ip

	@page.phrase_search.set "172.16"
	@page.submit_button.click

	@page.phrase_search.value.should eq "172.16"
	@page.should have_text our_ip
	@page.should have(1).items
  end

  it '(enabled) can change page size', :enabled => true do
    @page.perpage_filter.select "25 results"
    @page.submit_button.click

    @page.perpage_filter.has_select?('perpage', :selected => "25 results")
    @page.should have(25).items
    @page.should have_pagination
    @page.should have(6).pages
    @page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
  end

  it '(enabled) can combine phrase search with filters', :enabled => true do
	our_ip = "172.16.11.42"

	@page.generate_data(count: 27, timestamp_max: 0, ip_address: our_ip)
	@page.load

	@page.perpage_filter.select "25 results"
	@page.phrase_search.set "172.16.11"
	@page.submit_button.click

	@page.perpage_filter.has_select?('perpage', :selected => "25 results")
	@page.phrase_search.value.should eq "172.16.11"
	@page.should have_text our_ip
	@page.should have(25).items
    @page.should have_pagination
	@page.should have(5).pages
	@page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]
  end

  # Confirming the log deletion action
  # it '(enabled) can remove a single entry', :enabled => true do
  #	  our_action = "Rspec entry to be deleted"
  #
  #	  @page.generate_data(count: 1, timestamp_max: 0, action: our_action)
  #	  @page.load
  #
  #	  log = @page.find('section.item-wrap div.item', :text => our_action)
  #	  log.find('li.remove a').click
  #
  #	  @page.should have_alert
  #	  @page.should have_no_content our_action
  # end

  it '(enabled) can remove all entries', :enabled => true do
    @page.remove_all.click

    @page.should have_alert
    @page.should have_no_results
    @page.should_not have_pagination
  end

  # Confirming Pagination behavior
  it '(enabled) shows the Prev button when on page 2', :enabled => true do
    click_link "Next"

    @page.should have_pagination
    @page.should have(7).pages
    @page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
  end

  it '(enabled) does not show Next on the last page', :enabled => true do
    click_link "Last"

    @page.should have_pagination
    @page.should have(6).pages
    @page.pages.map {|name| name.text}.should == ["First", "Previous", "3", "4", "5", "Last"]
  end

  it '(enabled) does not lose a filter value when paginating', :enabled => true do
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

  it '(enabled) will paginate phrase search results', :enabled => true do
  	@page.generate_data(count: 35, timestamp_max: 0, ip_address: "172.16.11.42")
  	@page.load
	@page.perpage_filter.select "25 results"
	@page.phrase_search.set "172.16.11"
	@page.submit_button.click

	# Page 1
	@page.phrase_search.value.should eq "172.16.11"
	@page.items.should_not have_text "10.0"
	@page.perpage_filter.has_select?('perpage', :selected => "25 results")
	@page.should have(25).items
	@page.should have_pagination
	@page.should have(5).pages
	@page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]

	click_link "Next"

	# Page 2
	@page.phrase_search.value.should eq "172.16.11"
	@page.items.should_not have_text "10.0"
	@page.perpage_filter.has_select?('perpage', :selected => "25 results")
	@page.should have(10).items
	@page.should have_pagination
	@page.should have(5).pages
	@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
  end
end