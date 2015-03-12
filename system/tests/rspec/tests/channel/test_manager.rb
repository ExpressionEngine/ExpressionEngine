require './bootstrap.rb'

feature 'Channel Manager' do

  before(:each) do
    cp_session
    @page = ChannelManager.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Channel Manager page' do
    @page.all_there?.should == true
    @page.should have_text 'Manage Channels'
  end

  def get_channel_titles
    channels = []
    $db.query('SELECT channel_title FROM exp_channels ORDER BY channel_title ASC').each(:as => :array) do |row|
      channels << row[0]
    end
    clear_db_result

    return channels
  end

  def get_channel_names
    channels = []
    $db.query('SELECT channel_name FROM exp_channels ORDER BY channel_title ASC').each(:as => :array) do |row|
      channels << row[0]
    end
    clear_db_result

    return channels
  end

  it 'should list the channels' do
    channels = get_channel_titles
    channel_short_names = get_channel_names

    @page.channel_titles.map {|source| source.text}.should == channels
    @page.channel_names.map {|source| source.text}.should == channel_short_names
    @page.should have(channels.count).channel_titles
  end

  it 'should sort the list of channels' do
    @page.sort_col.text.should eq 'Channel'
    @page.sort_links[0].click
    no_php_js_errors

    channels = get_channel_titles
    channel_short_names = get_channel_names

    # Sort reverse alphabetically
    @page.channel_titles.map {|source| source.text}.should == channels.reverse
    @page.should have(channels.count).channel_titles
    @page.sort_col.text.should eq 'Channel'

    # Sort by short name alphabetically
    @page.sort_links[1].click
    no_php_js_errors
    @page.channel_names.map {|source| source.text}.should == channel_short_names.sort
    @page.sort_col.text.should eq 'Short name'

    # Sort by short name reverse alphabetically
    @page.sort_links[1].click
    no_php_js_errors
    @page.channel_names.map {|source| source.text}.should == channel_short_names.sort.reverse
    @page.sort_col.text.should eq 'Short name'
  end

  it 'should delete a channel' do
    channels = get_channel_titles
    channel_short_names = get_channel_names

    # Also set a sort state to make sure it's maintained
    @page.sort_links[1].click
    no_php_js_errors
    @page.sort_col.text.should eq 'Short name'

    @page.channels[2].find('input[type="checkbox"]').set true
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible
    @page.modal.should have_text 'Channel: ' + channels.sort[1]
    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.alert[:class].should include 'success'
    @page.alert.text.should include 'Channels removed'
    @page.alert.text.should include '1 channels were removed.'
    @page.channel_names.count.should == channels.count - 1

    # Check that it maintained sort state
    @page.sort_col.text.should eq 'Short name'
  end

  it 'should bulk delete channels' do
    channels = get_channel_titles
    @page.select_all.click
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible

    if channels.count <= 5
      for channel_title in channels
        @page.modal.should have_text 'Channel: ' + channel_title
      end
    end

    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.alert[:class].should include 'success'
    @page.alert.text.should include 'Channels removed'
    @page.alert.text.should include channels.count.to_s + ' channels were removed.'
    @page.channel_names.count.should == 0

    @page.table.should have_text 'No Channels'
    @page.table.should have_text 'CREATE CHANNEL'
  end
end