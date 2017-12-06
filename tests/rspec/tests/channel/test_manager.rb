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
    @page.should have_text 'Channel Manager'
  end

  def get_channel_titles
    channels = []
    $db.query('SELECT channel_title FROM exp_channels ORDER BY channel_title ASC').each(:as => :array) do |row|
      channels << row[0]
    end
    clear_db_result

    return channels
  end

  it 'should list the channels' do
    channels = get_channel_titles

    @page.channels.map {|source| source.text}.should == channels
    @page.should have(channels.count).channels
  end

  it 'should delete a channel' do
    channels = get_channel_titles

    @page.channels_checkboxes[1].click
    @page.wait_for_bulk_action

    @page.has_bulk_action?.should == true
    @page.has_action_submit_button?.should == true

    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible

    @page.modal.should have_text 'Channel: ' + channels[1]
    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.alert[:class].should include 'success'
    @page.channels.should have(channels.count - 1).items
  end

  it 'should bulk delete channels' do
    channels = get_channel_titles

    @page.select_all.click
    @page.wait_for_bulk_action

    @page.has_bulk_action?.should == true
    @page.has_action_submit_button?.should == true

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
    @page.channels.count.should == 0

    @page.should have_text 'No Channels found'
  end
end
