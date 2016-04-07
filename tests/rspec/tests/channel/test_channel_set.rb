require './bootstrap.rb'
require 'zip'
require 'base64'
require 'php_serialize'

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

  # Import a given channel set
  #
  # @param [String] name The channel set's zip filename, no extension
  # @param [Boolean] failure = false Set to true to check for failure
  # @return [void]
  def import_channel_set(name, method: 'success')
    @page.import.click
    @page.attach_file(
      'set_file',
      File.expand_path("./channel_sets/#{name}.zip")
    )
    @page.submit

    if block_given?
      no_php_js_errors
      @page.alert[:class].should include 'issue'
      yield
    else
      send('check_' + method)
    end
  end

  # Check to make sure the import was successful
  #
  # @return [void]
  def check_success
    no_php_js_errors
    @page.alert[:class].should include 'success'
    @page.alert.text.should include 'Channel Imported'
    @page.alert.text.should include 'The channel was successfully imported.'
    @page.all_there?.should == true
  end

  # Check to make sure the import was **not** successful
  #
  # @return [void]
  def check_issue_duplicate
    no_php_js_errors
    @page.alert[:class].should include 'issue'
    @page.alert.text.should include 'Import Creates Duplicates'
    @page.alert.text.should include 'This channel set uses names that already exist on your site. Please rename the following items.'
  end

  context 'when exporting' do
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
      news_image['settings']['num_existing'].should == 50
      news_image['settings']['show_existing'].should == 'y'
      news_image['settings']['field_content_type'].should == 'image'
      news_image['settings']['allowed_directories'].should == 'all'

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

    it 'exports fieldtypes with custom settings' do
      channel_fields = ChannelFieldForm.new
      channel_fields.create_field(
        group_id: 1,
        type: 'Checkboxes',
        label: 'Checkboxes',
        fields: {
          field_list_items: "Yes\nNo\nMaybe"
        }
      )
      channel_fields.create_field(
        group_id: 1,
        type: 'Radio Buttons',
        label: 'Radio Buttons',
        fields: {
          field_list_items: "Left\nCenter\nRight"
        }
      )
      channel_fields.create_field(
        group_id: 1,
        type: 'Multi Select',
        label: 'Multi Select',
        fields: {
          field_list_items: "Red\nGreen\nBlue"
        }
      )
      channel_fields.create_field(
        group_id: 1,
        type: 'Select Dropdown',
        label: 'Select Dropdown',
        fields: {
          field_list_items: "Mac\nWindows\nLinux"
        }
      )
      channel_fields.create_field(
        group_id: 1,
        type: 'Select Dropdown',
        label: 'Prepopulated',
        fields: {
          field_pre_populate: 'y'
        }
      )
      channel_fields.create_field(
        group_id: 1,
        type: 'Rich Text Editor',
        label: 'Rich Text Editor',
        fields: {
          field_ta_rows: 20,
          field_text_direction: 'Right to left'
        }
      )
      channel_fields.create_field(
        group_id: 1,
        type: 'Toggle',
        label: 'Toggle'
      )
      channel_fields.create_field(
        group_id: 1,
        type: 'Text Input',
        label: 'Text Input',
        fields: {
          field_maxl: 100,
          field_fmt: 'None',
          field_show_fmt: 'y',
          field_text_direction: 'Right to left',
          field_content_type: 'Decimal',
          field_show_smileys: 'y',
          field_show_file_selector: 'y'
        }
      )
      channel_fields.create_field(
        group_id: 1,
        type: 'Textarea',
        label: 'Textarea',
        fields: {
          field_ta_rows: 20,
          field_fmt: 'None',
          field_show_fmt: 'y',
          field_text_direction: 'Right to left',
          field_show_formatting_btns: 'y',
          field_show_smileys: 'y',
          field_show_file_selector: 'y'
        }
      )
      channel_fields.create_field(
        group_id: 1,
        type: 'URL',
        label: 'URL Field',
        fields: {
          url_scheme_placeholder: '// (Protocol Relative URL)'
        }
      ) do |page|
        page.all('input[name="allowed_url_schemes[]"]').each do |element|
          element.click unless element.checked?
        end
      end

      @page.load
      download_channel_set(1)

      # Check to see if the file exists
      name = @page.channel_names[0].text
      path = File.expand_path("../../system/user/cache/cset/#{name}.zip")
      File.exist?(path).should == true
      no_php_js_errors

      found_files = []
      Zip::File.open(path) do |zipfile|
        zipfile.each do |file|
          found_files << file
        end
      end

      checkboxes = JSON.parse(found_files[3].get_input_stream.read)
      checkboxes['label'].should == 'Checkboxes'
      checkboxes['list_items'].should == ['Yes', 'No', 'Maybe']

      radio_buttons = JSON.parse(found_files[4].get_input_stream.read)
      radio_buttons['label'].should == 'Radio Buttons'
      radio_buttons['list_items'].should == ['Left', 'Center', 'Right']

      multi_select = JSON.parse(found_files[5].get_input_stream.read)
      multi_select['label'].should == 'Multi Select'
      multi_select['list_items'].should == ['Red', 'Green', 'Blue']

      select_dropdown = JSON.parse(found_files[6].get_input_stream.read)
      select_dropdown['label'].should == 'Select Dropdown'
      select_dropdown['list_items'].should == ['Mac', 'Windows', 'Linux']

      prepopulated = JSON.parse(found_files[7].get_input_stream.read)
      prepopulated['label'].should == 'Prepopulated'
      prepopulated['field_pre_populate'].should == 'y'
      prepopulated['field_pre_channel_id'].should == 2
      prepopulated['field_pre_field_id'].should == 7

      rte = JSON.parse(found_files[8].get_input_stream.read)
      rte['label'].should == 'Rich Text Editor'
      rte['field_ta_rows'].should == 20
      rte['field_text_direction'].should == 'rtl'

      toggle = JSON.parse(found_files[9].get_input_stream.read)
      toggle['label'].should == 'Toggle'
      toggle['field_default_value'].to_i.should == 0

      text_input = JSON.parse(found_files[10].get_input_stream.read)
      text_input['label'].should == 'Text Input'
      text_input['field_maxl'].to_i.should == 100
      text_input['fmt'].should == 'none'
      text_input['field_text_direction'].should == 'rtl'
      text_input['content_type'].should == 'decimal'
      text_input['field_show_smileys'].should == 'y'
      text_input['field_show_file_selector'].should == 'y'

      textarea = JSON.parse(found_files[11].get_input_stream.read)
      textarea['label'].should == 'Textarea'
      textarea['fmt'].should == 'none'
      textarea['field_ta_rows'].should == 20
      textarea['field_text_direction'].should == 'rtl'
      textarea['field_show_formatting_btns'].should == 'y'
      textarea['field_show_smileys'].should == 'y'
      textarea['field_show_file_selector'].should == 'y'

      url = JSON.parse(found_files[12].get_input_stream.read)
      url['label'].should == 'URL Field'
      url['url_scheme_placeholder'].should == '//'
      url['allowed_url_schemes'].should == ["http://", "https://", "//", "ftp://", "sftp://", "ssh://"]
    end

    it 'properly exports a specified upload destination'

    context 'with grid fields' do
      it 'exports without a relationship column' do
        import_channel_set 'grid-no-relationships'

        name = "board_games"
        channel_id = @page.get_channel_id_from_name(name)
        download_channel_set(channel_id)

        # Check to see if the file exists
        path = File.expand_path("../../system/user/cache/cset/#{name}.zip")
        File.exist?(path).should == true

        expected_files = [
          '/custom_fields/Board Games/editions.grid',
          '/custom_fields/Board Games/duration.text',
          '/custom_fields/Board Games/number_of_players.text',
          'channel_set.json'
        ]
        found_files = []
        Zip::File.open(path) do |zipfile|
          zipfile.each do |file|
            found_files << file
          end
        end

        expected_files.sort.should == found_files.sort.map(&:name)
      end

      it 'exports with a relatioship column' do
        import_channel_set 'grid-with-relationship'

        name = "game_sessions"
        channel_id = @page.get_channel_id_from_name(name)
        download_channel_set(channel_id)

        # Check to see if the file exists
        path = File.expand_path("../../system/user/cache/cset/#{name}.zip")
        File.exist?(path).should == true

        expected_files = [
          '/custom_fields/Board Games/editions.grid',
          '/custom_fields/Board Games/duration.text',
          '/custom_fields/Board Games/number_of_players.text',
          '/custom_fields/Game Sessions/game_day.date',
          '/custom_fields/Game Sessions/games_played.grid',
          'channel_set.json'
        ]
        found_files = []
        Zip::File.open(path) do |zipfile|
          zipfile.each do |file|
            found_files << file
          end
        end

        expected_files.sort.should == found_files.sort.map(&:name)
      end
    end
  end

  it 'exports multiple channel sets'

  context 'when importing channel sets' do
    it 'imports a channel set' do
      import_channel_set 'simple'
    end

    it 'imports a channel set with duplicate names' do
      import_channel_set 'simple-duplicate', method: 'issue_duplicate'

      @page.find('input[name="ee:Channel[news][channel_title]"]').set 'Event'
      @page.find('input[name="ee:Channel[news][channel_name]"]').set 'event'
      @page.find('input[name="ee:ChannelFieldGroup[News][group_name]"]').set 'Event'
      @page.find('input[name="ee:ChannelField[news_body][field_name]"]').set 'event_body'
      @page.find('input[name="ee:ChannelField[news_extended][field_name]"]').set 'event_extended'
      @page.find('input[name="ee:ChannelField[news_image][field_name]"]').set 'event_image'
      @page.submit

      check_success
    end

    it 'shows errors when the channel set cannot be imported' do
      import_channel_set('no-json') do
        @page.alert.text.should include 'Cannot Import Channel'
        @page.alert.text.should include 'Missing channel_set.json file.'
      end
    end

    context 'when importing Default statuses' do
      # Import a channel set with default statuses and check that the new status
      # exist and that the correct number of statuses exists
      #
      # @param [String] name The channel set's zip filename, no extension
      # @param [String] status_name The name of the status being added
      # @param [Integer] status_count The number of statuses that shoul exist
      # @return [void]
      def import_default_statuses(channel_set, status_name, status_count)
        import_channel_set channel_set

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
      it 'imports with a specified directory' do
        import_channel_set 'file-specified-directory', method: 'issue_duplicate'

        @page.find('input[name="ee:UploadDestination[Main Upload Directory][name]"]').set 'Uploads'
        @page.find('input[name="ee:UploadDestination[Main Upload Directory][server_path]"]').set '../images/uploads'
        @page.find('input[name="ee:UploadDestination[Main Upload Directory][url]"]').set '/images/uploads'
        @page.submit

        check_success

        upload_dir_id = 0
        $db.query("SELECT id, count(*) AS count FROM exp_upload_prefs WHERE name = 'Uploads'").each do |row|
          upload_dir_id = row['id']
          new_upload_directory_count = row['count']
          new_upload_directory_count.should == 1
        end

        $db.query('SELECT field_settings FROM exp_channel_fields WHERE field_name = "blog_image"').each do |row|
          settings = PHP.unserialize(Base64.decode64(row['field_settings']))
          settings['allowed_directories'].to_i.should == upload_dir_id.to_i
        end
      end

      it 'imports with no existing upload directories' do
        $db.query('TRUNCATE TABLE exp_upload_prefs')
        import_channel_set 'simple'
      end
    end
    context 'with grid fields' do
      it 'imports without a relationship column' do
        import_channel_set 'grid-no-relationships'

        # Assure we have imported the right Channel, Field Group, Fields, and Grid Columns
        $db.query("SELECT count(*) AS count FROM exp_channels WHERE channel_name = 'board_games' AND channel_title = 'Board Games'").each do |row|
          number_of_channels = row['count']
          number_of_channels.should == 1
        end

        $db.query("SELECT count(*) AS count FROM exp_field_groups WHERE group_name = 'Board Games'").each do |row|
          number_of_field_groups = row['count']
          number_of_field_groups.should == 1
        end

        $db.query("SELECT count(*) AS count FROM exp_channel_fields WHERE (field_name = 'duration' AND field_label = 'Duration') OR (field_name = 'editions' AND field_label = 'Editions') OR (field_name = 'number_of_players' AND field_label = 'Number of Players')").each do |row|
          number_of_fields = row['count']
          number_of_fields.should == 3
        end

        $db.query("SELECT count(*) AS count FROM exp_grid_columns WHERE (col_name = 'edition_name' AND col_label = 'Edition Name') OR (col_name = 'edition_number' AND col_label = 'Edition Number')").each do |row|
          number_of_columns = row['count']
          number_of_columns.should == 2
        end
      end

      it 'imports with a relationship column' do
        import_channel_set 'grid-with-relationship'

        # Assure we have imported the right Channel, Field Group, Fields, and Grid Columns
        $db.query("SELECT count(*) AS count FROM exp_channels WHERE (channel_name = 'board_games' AND channel_title = 'Board Games') OR (channel_name = 'game_sessions' AND channel_title = 'Game Sessions')").each do |row|
          number_of_channels = row['count']
          number_of_channels.should == 2
        end

        $db.query("SELECT count(*) AS count FROM exp_field_groups WHERE group_name = 'Board Games' OR group_name = 'Game Sessions'").each do |row|
          number_of_field_groups = row['count']
          number_of_field_groups.should == 2
        end

        $db.query("SELECT count(*) AS count FROM exp_channel_fields WHERE (field_name = 'game_day' AND field_label = 'Game Day') OR (field_name = 'games_played' AND field_label = 'Games Played') OR (field_name = 'duration' AND field_label = 'Duration') OR (field_name = 'editions' AND field_label = 'Editions') OR (field_name = 'number_of_players' AND field_label = 'Number of Players')").each do |row|
          number_of_fields = row['count']
          number_of_fields.should == 5
        end

        $db.query("SELECT count(*) AS count FROM exp_grid_columns WHERE (col_name = 'edition_name' AND col_label = 'Edition Name') OR (col_name = 'edition_number' AND col_label = 'Edition Number') OR (col_name = 'game' AND col_label = 'Game') OR (col_name = 'number_of_plays' AND col_label = 'Number of Plays')").each do |row|
          number_of_columns = row['count']
          number_of_columns.should == 4
        end
      end
    end
    context 'with relationship fields' do
      it 'imports'
    end
    it 'imports a two channel set'
  end
end
