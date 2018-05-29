require './bootstrap.rb'

feature 'Statistics' do

  before(:each) do
    cp_session
    @page = Stats.new
    @page.load

    @page.should be_displayed
    @page.heading.text.should eq 'Manage Statistics'
    @page.should have_content_table
  end

  it "shows the Manage Statistics page" do
    @page.should have(4).rows # 3 rows + header
    @page.sources.map {|source| source.text}.should == ["Channel Entries", "Members", "Sites"]
    @page.counts.map {|count| count.text}.should == ["10", "7", "1"]
  end

  it "can sort by source" do
    @page.all('a.sort')[0].click
    @page.sources.map {|source| source.text}.should == ["Sites", "Members", "Channel Entries"]
    @page.content_table.find('th.highlight').text.should eq 'Source'

    @page.all('a.sort')[0].click
    @page.sources.map {|source| source.text}.should == ["Channel Entries", "Members", "Sites"]
    @page.content_table.find('th.highlight').text.should eq 'Source'
  end

  it "can sort by count" do
    @page.all('a.sort')[1].click
    @page.counts.map {|count| count.text}.should == ["1", "7", "10"]
    @page.content_table.find('th.highlight').text.should eq 'Record Count'

    @page.all('a.sort')[1].click
    @page.counts.map {|count| count.text}.should == ["10", "7", "1"]
    @page.content_table.find('th.highlight').text.should eq 'Record Count'
  end

  it "reports accurate record count after adding a member" do
    add_member(username: 'johndoe')
    @page.load

    @page.should have(4).rows # 3 rows + header
    @page.sources.map {|source| source.text}.should == ["Channel Entries", "Members", "Sites"]
    @page.counts.map {|count| count.text}.should == ["10", "8", "1"]
  end

  it "can sync one source" do
    @page.content_table.find('tr:nth-child(2) li.sync a').click

    @page.should have_alert
    @page.should have_alert_success
  end

  it "can sync multiple sources" do
    @page.find('input[type="checkbox"][title="select all"]').set(true)
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Sync"
    @page.action_submit_button.click

    @page.should have_alert
    @page.should have_alert_success
  end

end
