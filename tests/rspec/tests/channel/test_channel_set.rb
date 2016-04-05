require './bootstrap.rb'

feature 'Channel Sets' do
  before :each do
    cp_session
    @page = ChannelManager.new
    @page.load
    no_php_js_errors
  end

  # Download a channel set using Ajax
  #
  # @param Integer id The ID of the channel to download
  # @return Boolean TRUE if the download was successful
  def download_channel_set(id)
    @page.execute_script("window.downloadCSVXHR = function(){ var url = window.location.protocol + '//' + window.location.host + '//system/index.php?/cp/channels/sets/export/#{id}'; return getFile(url); }")
    @page.execute_script('window.getFile = function(url) { var xhr = new XMLHttpRequest();  xhr.open("GET", url, false);  xhr.send(null); return xhr.responseText; }')
    data = @page.evaluate_script('downloadCSVXHR()')
    data.should start_with('PK')
  end

  it 'downloads the zip file when exporting channel sets' do
    download_channel_set(1)

    # Check to see if the file exists
    name = @page.channel_names[0].text
    path = File.expand_path("../../system/user/cache/cset/#{name}.zip")
    File.exist?(path).should == true
    no_php_js_errors

    # TODO: Check to see if the file matches expectations
  end

  it 'exports multiple channel sets'

  context 'when importing channel sets' do
    it 'imports a channel set' do
      @page.import.click
      @page.attach_file(
        'set_file',
        File.expand_path('./channel_sets/simple.zip')
      )
      @page.submit

      no_php_js_errors
      @page.alert[:class].should include 'success'
      @page.alert.text.should include 'Channel Imported'
      @page.alert.text.should include 'The channel was successfully imported.'
      @page.all_there?.should == true
    end

    it 'imports a channel set with duplicate names' do
      @page.import.click
      @page.attach_file(
        'set_file',
        File.expand_path('./channel_sets/simple-duplicate.zip')
      )
      @page.submit

      no_php_js_errors
      @page.alert[:class].should include 'issue'
      @page.alert.text.should include 'Import Creates Duplicates'
      @page.alert.text.should include 'This channel set uses names that already exist on your site. Please rename the following items.'

      @page.find('input[name="ee:Channel[news][channel_title]"]').set 'Event'
      @page.find('input[name="ee:Channel[news][channel_name]"]').set 'event'
      @page.find('input[name="ee:ChannelFieldGroup[News][group_name]"]').set 'Event'
      @page.find('input[name="ee:ChannelField[news_body][field_name]"]').set 'event_body'
      @page.find('input[name="ee:ChannelField[news_extended][field_name]"]').set 'event_extended'
      @page.find('input[name="ee:ChannelField[news_image][field_name]"]').set 'event_image'
      @page.submit

      no_php_js_errors
      @page.alert[:class].should include 'success'
      @page.alert.text.should include 'Channel Imported'
      @page.alert.text.should include 'The channel was successfully imported.'
      @page.all_there?.should == true
    end

    it 'imports a channel set with additional Default statuses'
    context 'with file fields' do
      it 'imports without a specified directory'
      it 'imports with a specified directory'
    end
    context 'with grid fields' do
      it 'imports without a relationship column'
      it 'imports with a relationship column'
    end
    context 'with relationship fields' do
      it 'imports'
    end
    it 'imports a two channel set'
  end
end
