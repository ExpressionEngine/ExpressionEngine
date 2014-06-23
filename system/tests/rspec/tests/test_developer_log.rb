require './bootstrap.rb'

def confirm (page)
  page.displayed?
  page.title.text.should eq 'Developer Logs'
  page.should have_phrase_search
  page.should have_submit_button
  page.should have_date_filter
  page.should have_perpage_filter
end

feature 'Developer Log' do

  before(:each) do
	cp_session

	@page = DeveloperLog.new
  end

  it 'shows the Developer Logs page' do
	@page.generate_data
	@page.load
	confirm @page

	@page.should have_remove_all
	@page.should have_pagination

	@page.perpage_filter.value.should eq "50"

	@page.should have(6).pages
	@page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

	@page.should have(50).items # Default is 50 per page
  end

  # Confirming phrase search
  # @TODO pending phrase search working

  it 'filters by date' do
	@page.generate_data(count: 23, timestamp_max: 22)
	@page.generate_data(count: 42, timestamp_min: 36, timestamp_max: 60)
	@page.load
	confirm @page

	@page.should have(50).items # Default is 50 per page

	@page.date_filter.select "Last 24 Hours"
	@page.submit_button.click

	@page.date_filter.has_select?('filter_by_date', :selected => "Last 24 Hours")
	@page.should have(23).items
	@page.should_not have_pagination
  end

  it 'can change page size' do
	@page.generate_data
	@page.load
	confirm @page

	@page.perpage_filter.select "25 results"
	@page.submit_button.click

	@page.perpage_filter.has_select?('perpage', :selected => "25 results")
	@page.should have(25).items
	@page.should have_pagination
	@page.should have(6).pages
	@page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
  end

  # Confirming combining filters work
  it 'can combine date and page size filters' do
	@page.generate_data(count: 23, timestamp_max: 22)
	@page.generate_data(count: 42, timestamp_min: 36, timestamp_max: 60)
	@page.load
	confirm @page

	@page.perpage_filter.select "25 results"
	@page.submit_button.click

	@page.perpage_filter.has_select?('perpage', :selected => "25 results")
	@page.should have(25).items
	@page.should have_pagination

	@page.perpage_filter.select "25 results"
	@page.date_filter.select "Last 24 Hours"
	@page.submit_button.click

	@page.perpage_filter.has_select?('perpage', :selected => "25 results")
	@page.date_filter.has_select?('filter_by_date', :selected => "Last 24 Hours")
	@page.should have(23).items
	@page.should_not have_pagination
  end

  # @TODO pending phrase search working
  # it 'can combine phrase search with filters' do
  # end

  # Confirming the log deletion action
  it 'can remove a single entry' do
	our_desc = "Rspec entry to be deleted"

	@page.generate_data
	@page.generate_data(count: 1, timestamp_max: 0, description: our_desc)
	@page.load
	confirm @page

	log = @page.find('section.item-wrap div.item', :text => our_desc)
	log.find('li.remove a').click

	@page.should have_alert
	@page.should have_no_content our_desc
  end

  it 'can remove all entries' do
	@page.generate_data
	@page.load
	confirm @page

	@page.remove_all.click

	@page.should have_alert
	@page.should have_no_results
	@page.should_not have_pagination
  end

  # Confirming Pagination behavior
  it 'shows the Prev button when on page 2' do
	@page.generate_data
	@page.load
	confirm @page

	click_link "Next"

	@page.should have_pagination
	@page.should have(7).pages
	@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
  end

  it 'does not show Next on the last page' do
	@page.generate_data
	@page.load
	confirm @page

	click_link "Last"

	@page.should have_pagination
	@page.should have(6).pages
	@page.pages.map {|name| name.text}.should == ["First", "Previous", "3", "4", "5", "Last"]
  end

  it 'does not lose a filter value when paginating' do
	@page.generate_data
	@page.load
	confirm @page

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