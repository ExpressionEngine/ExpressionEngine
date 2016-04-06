require './bootstrap.rb'
require 'zip'

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

    expected_files = %w(
      /custom_fields/News/news_body.textarea
      /custom_fields/News/news_extended.textarea
      /custom_fields/News/news_image.file
      channel_set.json
    )
    found_files = []
    Zip::File.open(path) do |zipfile|
      zipfile.each do |file|
        found_files << file
      end
    end

    news_body = JSON.parse(found_files[0].get_input_stream.read)
    news_body['label'].should == 'Body'

    news_extended = JSON.parse(found_files[1].get_input_stream.read)
    news_extended['label'].should == 'Extended text'

    news_image = JSON.parse(found_files[2].get_input_stream.read)
    news_image['label'].should == 'News Image'

    channel_set = JSON.parse(found_files[3].get_input_stream.read)
    channel_set['channels'].size.should == 1
    channel_set['channels'][0]['channel_title'].should == 'News'
    channel_set['channels'][0]['field_group'].should == 'News'
    channel_set['channels'][0]['cat_groups'][0].should == 'News Categories'
    channel_set['status_groups'].size.should == 1
    channel_set['status_groups'][0]['name'].should == 'Default'
    channel_set['status_groups'][0]['statuses'].size.should == 1
    channel_set['status_groups'][0]['statuses'][0]['name'].should == 'Featured'
    channel_set['category_groups'].size.should == 1
    channel_set['category_groups'][0]['name'].should == 'News Categories'
    channel_set['category_groups'][0]['sort_order'].should == 'a'
    channel_set['category_groups'][0]['categories'].should == %w(News Bands)
    channel_set['upload_destinations'].size.should == 0

    expected_files.sort.should == found_files.sort.map(&:name)
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

    context 'when importing Default statuses' do
      def import_default_statuses(channel_set, status_name, status_count)
        @page.import.click
        @page.attach_file(
          'set_file',
          File.expand_path("./channel_sets/#{channel_set}.zip")
        )
        @page.submit

        no_php_js_errors
        @page.alert[:class].should include 'success'
        @page.alert.text.should include 'Channel Imported'
        @page.alert.text.should include 'The channel was successfully imported.'
        @page.all_there?.should == true

        # Assure there's still only one default status group
        $db.query('SELECT count(*) AS count FROM exp_status_groups WHERE group_name = "Default"').each do |row|
          number_of_status_groups = row['count']
          number_of_status_groups.should == 1
        end

        # Assure there's now THREE statuses in that group and one of them is the new status_name
        $db.query('SELECT count(*) AS count FROM exp_statuses WHERE group_id = 1').each do |row|
          number_of_default_statuses = row['count']
          number_of_default_statuses.should == status_count
        end
        $db.query("SELECT count(*) AS count FROM exp_statuses WHERE status = '#{status_name}'").each do |row|
          number_of_new_statuses = row['count']
          number_of_new_statuses.should == 1
        end
      end

      it 'imports additional Default statuses' do
        import_default_statuses('default-statuses', 'Draft', 4)
      end

      it 'does not import duplicate statuses' do
        import_default_statuses('default-statuses-duplicate', 'Featured', 3)
      end
    end

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
