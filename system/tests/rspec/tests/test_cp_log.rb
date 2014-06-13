require './bootstrap.rb'

feature 'CP Log' do

  before(:each) do
    cp_session

    @page = CpLog.new
    @page.load

    # These should always be true at all times if not something has gone wrong
    @page.displayed?
    @page.should have_text 'Control Panel Access Logs'
    @page.should have_phrase_search
    @page.should have_submit_button
    @page.should have_username_filter
    @page.should have_site_filter
    @page.should have_date_filter
    @page.should have_perpage_filter
  end

  it 'shows the Control Panel Access Logs page' do
    @page.should have_remove_all
    @page.should have_pagination

    @page.perpage_filter.value.should eq "50"

    @page.should have(6).pages # First, 1, 2, 3, Next, Last
    @page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

    @page.should have(50).items # Default is 50 per page
  end

end