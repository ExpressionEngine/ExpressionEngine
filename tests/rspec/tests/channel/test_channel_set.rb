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

  # Download a channel set
  #
  # @param Integer id The ID of the channel to download
  def download_channel_set(id)
    @page.execute_script("window.downloadCSVXHR = function(){ var url = window.location.protocol + '//' + window.location.host + '//admin.php?/cp/channels/sets/export/#{id}'; return getFile(url); }")
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
    #@page.import.click
    # Capybara-webkit isn't happy about the file field being hidden
    @page.execute_script('$("input[name=set_file]").parent().show()')
    @page.attach_file(
      'set_file',
      File.expand_path("./channel_sets/#{name}.zip"),
      visible: false
    )

    if block_given?
      no_php_js_errors
      @page.should have_alert_error
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
    @page.should have_alert_success
    @page.alert.text.should include 'Channel Imported'
    @page.alert.text.should include 'The channel was successfully imported.'
    @page.all_there?.should == true
  end

  # Check to make sure the import was **not** successful
  #
  # @return [void]
  def check_issue_duplicate
    no_php_js_errors
    @page.should have_alert_error
    @page.alert.text.should include 'Import Creates Duplicates'
    @page.alert.text.should include 'This channel set uses names that already exist on your site. Please rename the following items.'
  end

	def field_groups_created(field_groups)
		$db.query("SELECT count(*) AS count FROM exp_field_groups WHERE group_name IN ('" + field_groups.join("','") + "')").each do |row|
			field_group_count = row['count']
			field_group_count.should == field_groups.count
		end
	end

	def fields_created(fields)
		$db.query("SELECT count(*) AS count FROM exp_channel_fields WHERE field_name IN ('" + fields.join("','") + "')").each do |row|
			fields_count = row['count']
			fields_count.should == fields.count
		end
	end

	def fields_assinged_to_group(group, fields)
		group_id = ''
		field_ids = []

		$db.query("SELECT group_id FROM exp_field_groups WHERE group_name = '" + group + "'").each do |row|
			group_id = row['group_id']
		end

		$db.query("SELECT field_id FROM exp_channel_fields WHERE field_name IN ('" + fields.join("','") + "')").each do |row|
			field_ids.push row['field_id']
		end

		$db.query("SELECT count(*) AS count FROM exp_channel_field_groups_fields WHERE group_id = '" + group_id.to_s + "' AND field_id IN ('" + field_ids.join("','") + "')").each do |row|
			fields_count = row['count']
			fields_count.should == fields.count
		end
	end

	def field_groups_assigned_to_channel(channel_id, count)
		$db.query("SELECT count(*) AS count FROM exp_channels_channel_field_groups WHERE channel_id = " + channel_id.to_s).each do |row|
			field_groups = row['count']
			field_groups.should == count
		end
	end

	def fields_assigned_to_channel(channel_id, count)
		$db.query("SELECT count(*) AS count FROM exp_channels_channel_fields WHERE channel_id = " + channel_id.to_s).each do |row|
			fields = row['count']
			fields.should == count
		end
	end

  context 'when exporting' do
    before :each do
      # Set debug to false to create fieldtypes from scratch
      @importer = ChannelSets::Importer.new(@page, debug: false)
    end

    it 'downloads the zip file when exporting channel sets' do
      download_channel_set(1)

      # Check to see if the file exists
      path = File.expand_path("../../system/user/cache/cset/news.zip")
      File.exist?(path).should == true
      no_php_js_errors

      expected_files = %w(
        /custom_fields/news_body.textarea
        /custom_fields/news_extended.textarea
        /custom_fields/news_image.file
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
      channel_set['channels'][0]['field_groups'][0].should == 'News'
      channel_set['channels'][0]['cat_groups'][0].should == 'News Categories'
      channel_set['statuses'].size.should == 1
      channel_set['statuses'][0]['name'].should == 'Featured'
      channel_set['category_groups'].size.should == 1
      channel_set['category_groups'][0]['name'].should == 'News Categories'
      channel_set['category_groups'][0]['sort_order'].should == 'a'
      channel_set['category_groups'][0]['categories'][0]['cat_name'].should == 'News'
      channel_set['category_groups'][0]['categories'][0]['cat_url_title'].should == 'news'
      channel_set['category_groups'][0]['categories'][0]['cat_description'].should == ''
      channel_set['category_groups'][0]['categories'][0]['cat_order'].should == 2
      channel_set['category_groups'][0]['categories'][1]['cat_name'].should == 'Bands'
      channel_set['category_groups'][0]['categories'][1]['cat_url_title'].should == 'bands'
      channel_set['category_groups'][0]['categories'][1]['cat_description'].should == ''
      channel_set['category_groups'][0]['categories'][1]['cat_order'].should == 3
      channel_set['upload_destinations'].size.should == 0

      expected_files.sort.should == found_files.sort.map(&:name)
    end

    it 'exports all category groups for the channel' do
      # First add a second category to the News channel
      @channel = Channel.new
      @channel.load_edit_for_channel(2)
      @channel.click_link 'Categories'
      @channel.cat_group[0].click
      @channel.submit

      @page.load
      download_channel_set(1)

      # Check to see if the file exists
      path = File.expand_path("../../system/user/cache/cset/news.zip")
      File.exist?(path).should == true
      no_php_js_errors

      found_files = []
      Zip::File.open(path) do |zipfile|
        zipfile.each do |file|
          found_files << file
        end
      end

      channel_set = JSON.parse(found_files[3].get_input_stream.read)
      channel_set['category_groups'].size.should == 2
      channel_set['category_groups'][1]['name'].should == 'About'
      channel_set['category_groups'][0]['name'].should == 'News Categories'
    end

    context 'with relationships' do
      it 'exports relationship fields with selected channels' do
        @importer.relationships_specified_channels
        download_channel_set(1)

        # Check to see if the file exists
        path = File.expand_path("../../system/user/cache/cset/news.zip")
        File.exist?(path).should == true
        no_php_js_errors

        found_files = []
        Zip::File.open(path) do |zipfile|
          zipfile.each do |file|
            found_files << file
          end
        end

        relationship = JSON.parse(found_files[7].get_input_stream.read)
        relationship['label'].should == 'Relationships'
        relationship['settings']['future'].should == 'y'
        relationship['settings']['allow_multiple'].should == 'n'
        relationship['settings']['limit'].to_i.should == 25
        relationship['settings']['order_field'].should == 'entry_date'
        relationship['settings']['order_dir'].should == 'desc'
        relationship['settings']['channels'].should == ['Information Pages']

        channel_set = JSON.parse(found_files[8].get_input_stream.read)
        channel_set['channels'].size.should == 2
      end

      it 'exports relationship fields with all channels' do
        @importer.relationships_all_channels
        download_channel_set(1)

        # Check to see if the file exists
        path = File.expand_path("../../system/user/cache/cset/news.zip")
        File.exist?(path).should == true
        no_php_js_errors

        found_files = []
        Zip::File.open(path) do |zipfile|
          zipfile.each do |file|
            found_files << file
          end
        end

        relationship = JSON.parse(found_files[3].get_input_stream.read)
        relationship['label'].should == 'Relationships'
        relationship['settings']['future'].should == 'y'
        relationship['settings']['allow_multiple'].should == 'n'
        relationship['settings']['limit'].to_i.should == 25
        relationship['settings']['order_field'].should == 'entry_date'
        relationship['settings']['order_dir'].should == 'desc'
        relationship['settings']['channels'].should == []

        channel_set = JSON.parse(found_files[4].get_input_stream.read)
        channel_set['channels'].size.should == 1
      end
    end

    it 'exports fieldtypes with custom settings' do
      @importer.custom_fields
      download_channel_set(1)

      # Check to see if the file exists
      path = File.expand_path("../../system/user/cache/cset/news.zip")
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
      prepopulated['pre_populate'].should == 'y'
      prepopulated['pre_channel_id'].should == 2
      prepopulated['pre_field_id'].should == 7

      rte = JSON.parse(found_files[8].get_input_stream.read)
      rte['label'].should == 'Rich Text Editor'
      rte['ta_rows'].should == 20
      rte['text_direction'].should == 'rtl'

      toggle = JSON.parse(found_files[9].get_input_stream.read)
      toggle['label'].should == 'Toggle'
      toggle['field_default_value'].to_i.should == 0

      text_input = JSON.parse(found_files[10].get_input_stream.read)
      text_input['label'].should == 'Text Input'
      text_input['maxl'].to_i.should == 100
      text_input['fmt'].should == 'none'
      text_input['text_direction'].should == 'rtl'
      text_input['content_type'].should == 'decimal'
      text_input['settings']['field_show_smileys'].should == 'y'
      text_input['settings']['field_show_file_selector'].should == 'y'

      textarea = JSON.parse(found_files[11].get_input_stream.read)
      textarea['label'].should == 'Textarea'
      textarea['fmt'].should == 'none'
      textarea['ta_rows'].should == 20
      textarea['text_direction'].should == 'rtl'
      textarea['settings']['field_show_formatting_btns'].should == 'y'
      textarea['settings']['field_show_smileys'].should == 'y'
      textarea['settings']['field_show_file_selector'].should == 'y'

      url = JSON.parse(found_files[12].get_input_stream.read)
      url['label'].should == 'URL Field'
      url['settings']['url_scheme_placeholder'].should == '//'
      url['settings']['allowed_url_schemes'].should == ["http://", "https://", "//", "ftp://", "sftp://", "ssh://"]
    end

    it 'exports a channel with a fluid field' do
      @importer.fluid_field
      download_channel_set(3)

      # Check to see if the file exists
      @page.load
      path = File.expand_path("../../system/user/cache/cset/news.zip")
      File.exist?(path).should == true
      no_php_js_errors

      expected_files = %w(
        /custom_fields/a_date.date
        /custom_fields/checkboxes.checkboxes
        /custom_fields/corpse.fluid_field
        /custom_fields/electronic_mail_address.email_address
        /custom_fields/home_page.url
        /custom_fields/image.file
        /custom_fields/middle_class_text.rte
        /custom_fields/multi_select.multi_select
        /custom_fields/radio.radio
        /custom_fields/rel_item.relationship
        /custom_fields/selection.select
        /custom_fields/stupid_grid.grid
        /custom_fields/text.textarea
        /custom_fields/truth_or_dare.toggle
        /custom_fields/youtube_url.text
        channel_set.json
      )
      found_files = []
      Zip::File.open(path) do |zipfile|
        zipfile.each do |file|
          found_files << file
        end
      end

      found_files.sort.map(&:name) == expected_files.sort.should
    end

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
          '/custom_fields/editions.grid',
          '/custom_fields/duration.text',
          '/custom_fields/number_of_players.text',
          'channel_set.json'
        ]
        found_files = []
        Zip::File.open(path) do |zipfile|
          zipfile.each do |file|
            found_files << file
          end
        end

        found_files.sort.map(&:name) == expected_files.sort.should
      end

      it 'exports with a relationship column' do
        import_channel_set 'grid-with-relationship'

        name = "game_sessions"
        channel_id = @page.get_channel_id_from_name(name)
        download_channel_set(channel_id)

        # Check to see if the file exists
        path = File.expand_path("../../system/user/cache/cset/#{name}.zip")
        File.exist?(path).should == true

        expected_files = [
          '/custom_fields/editions.grid',
          '/custom_fields/duration.text',
          '/custom_fields/number_of_players.text',
          '/custom_fields/game_day.date',
          '/custom_fields/games_played.grid',
          'channel_set.json'
        ]
        found_files = []
        Zip::File.open(path) do |zipfile|
          zipfile.each do |file|
            found_files << file
          end
        end

        found_files.sort.map(&:name) == expected_files.sort.should
      end

      it 'exports grid colums with settings' do
        channel_fields = ChannelFieldForm.new
        channel_fields.load
        channel_fields.field_label.set 'Zen'
        channel_fields.select_field_type 'Grid'
        GridSettings::populate_grid_settings
        channel_fields.submit

        # Create a "Gridlocked" group
        field_group_form = FieldGroupForm.new
        field_group_form.load
        field_group_form.name.set 'Gridlocked'
        field_group_form.all('.field-inputs label').each do |field|
          if field.text.include? 'Zen'
            field.find('input[type=checkbox]').click
            break
          end
        end
        field_group_form.submit[0].click

        # Create the "Big Grid" channel
        page = Channel.new
        page.load
        page.channel_title.set 'Big Grid'
        page.click_link 'Fields'
        page.field_groups[0].click
        page.field_groups[1].click
        page.field_groups[2].click
        page.title_field_label.set '¯\_(ツ)_/¯'
        page.submit

        @page.load
        name = "big_grid"
        channel_id = @page.get_channel_id_from_name(name)
        download_channel_set(channel_id)

        # Check to see if the file exists
        path = File.expand_path("../../system/user/cache/cset/#{name}.zip")
        File.exist?(path).should == true

        expected_files = [
			'/custom_fields/news_body.textarea',
			'/custom_fields/news_extended.textarea',
			'/custom_fields/news_image.file',
			'/custom_fields/about_body.textarea',
			'/custom_fields/about_image.file',
			'/custom_fields/about_staff_title.text',
			'/custom_fields/about_extended.textarea',
			'/custom_fields/zen.grid',
			'channel_set.json'
        ]
        found_files = []
        Zip::File.open(path) do |zipfile|
          zipfile.each do |file|
            found_files << file
          end
        end

        found_files.sort.map(&:name) == expected_files.sort.should

        # Check that we exported the title field label
        channel_set = JSON.parse(found_files.sort.last.get_input_stream.read)
        channel_set['channels'][0]['channel_title'].should eq 'Big Grid'
        channel_set['channels'][0]['title_field_label'].should eq '¯\_(ツ)_/¯'

        grid = JSON.parse(found_files.sort[7].get_input_stream.read)

        data = GridSettings::test_data

        grid['columns'].each do |column|
          key = column['type'] + '_col'
          compare = @importer.prepare_test_data(data[key.to_sym])

          column['type'].should == compare[:type][1]
          column['label'].should == compare[:label]
          column['name'].should == compare[:name]
          column['instructions'].should == compare[:instructions]
          column['required'].should == (compare[:required] ? 'y' : 'n')
          column['search'].should == (compare[:searchable] ? 'y' : 'n')
          column['width'].should == compare[:width].to_i

          column['settings'].each do |key, value|
            if compare.has_key? key.to_sym then
              if compare[key.to_sym].is_a?(TrueClass) then
                ['y', '1', 1, true].should include value
              elsif compare[key.to_sym].is_a?(FalseClass) then
                ['n', '0', 0, false].should include value
              else
                value.should == compare[key.to_sym]
              end
            end
          end
        end
      end
    end
  end

  context 'when importing channel sets' do
    it 'imports a channel set' do
      import_channel_set 'simple'

      $db.query("SELECT count(*) AS count FROM exp_channels WHERE channel_title = 'Blog' AND title_field_label = 'Blog title'").each do |row|
        channel_title_field_label = row['count']
        channel_title_field_label.should == 1
      end
    end

    it 'imports a 3.3.x channel set' do
      import_channel_set 'simple-3.3'

      $db.query("SELECT count(*) AS count FROM exp_channels WHERE channel_title = 'Blog' AND title_field_label = 'Blog title'").each do |row|
        channel_title_field_label = row['count']
        channel_title_field_label.should == 1
      end
    end

    it 'imports a channel set with 2 category groups' do
      import_channel_set 'two-cat-groups'

      $db.query("SELECT cat_group FROM exp_channels WHERE channel_title = 'Test'").each do |row|
        channel_title_field_label = row['cat_group']
        channel_title_field_label.should == '3|4'
      end
    end

    it 'imports a channel set with duplicate names' do
      import_channel_set 'simple-duplicate', method: 'issue_duplicate'

      @page.find('input[name="ee:Channel[news][channel_title]"]').set 'Event'
      @page.find('input[name="ee:Channel[news][channel_name]"]').set 'event'
      @page.find('input[name="ee:ChannelField[news_body][field_name]"]').set 'event_body'
      @page.find('input[name="ee:ChannelField[news_extended][field_name]"]').set 'event_extended'
      @page.find('input[name="ee:ChannelField[news_image][field_name]"]').set 'event_image'
      @page.find('input[name="ee:ChannelField[news_image][field_name]"]').trigger 'blur'
      @page.find('input[name="ee:ChannelFieldGroup[News][group_name]"]').set 'Event'
      @page.find('input[name="ee:ChannelFieldGroup[News][group_name]"]').trigger 'blur'
      @page.submit

      check_success
    end

	context 'v4 channel sets' do
		it 'imports a channel with 2 field groups' do
			import_channel_set 'channel-with-two-field-groups'

			check_success
			channel_id = @page.get_channel_id_from_name('channel_with_two_field_groups')

			fields_assigned_to_channel(channel_id, 0)
			field_groups_assigned_to_channel(channel_id, 2)
			fields_created ['checkboxes', 'electronic_mail_address']
			field_groups_created ['FG One', 'FG Two']

			fields_assinged_to_group('FG One', ['checkboxes'])
			fields_assinged_to_group('FG Two', ['electronic_mail_address'])
		end

		it 'imports a channel with fields but no field group' do
			import_channel_set 'channel-with-fields'

			check_success
			channel_id = @page.get_channel_id_from_name('channel_with_fields')

			fields_assigned_to_channel(channel_id, 2)
			field_groups_assigned_to_channel(channel_id, 0)
			fields_created ['checkboxes', 'electronic_mail_address']
		end

		it 'imports a channel with fields and field groups' do
			import_channel_set 'channel-with-field-groups-and-fields'

			check_success
			channel_id = @page.get_channel_id_from_name('channel_with_field_groups_and_fields')

			fields_assigned_to_channel(channel_id, 2)
			field_groups_assigned_to_channel(channel_id, 2)
			fields_created ['checkboxes', 'electronic_mail_address', 'youtube_url', 'text']
			field_groups_created ['FG One', 'FG Two']

			fields_assinged_to_group('FG One', ['checkboxes'])
			fields_assinged_to_group('FG Two', ['electronic_mail_address'])
		end

		it 'imports a channel with a field in two field groups' do
			import_channel_set 'channel-with-field-in-two-groups'

			check_success
			channel_id = @page.get_channel_id_from_name('channel_with_field_in_two_groups')

			fields_assigned_to_channel(channel_id, 0)
			field_groups_assigned_to_channel(channel_id, 3)
			fields_created ['checkboxes', 'electronic_mail_address', 'a_date']
			field_groups_created ['FG One', 'FG Two', 'FG Three']

			fields_assinged_to_group('FG One', ['checkboxes'])
			fields_assinged_to_group('FG Two', ['electronic_mail_address'])
			fields_assinged_to_group('FG Three', ['checkboxes', 'a_date'])
		end

		it 'imports a channel with a field already in an assigned field group' do
			import_channel_set 'channel-with-field-in-a-group'

			check_success
			channel_id = @page.get_channel_id_from_name('channel_with_field_in_a_group')

			fields_assigned_to_channel(channel_id, 1)
			field_groups_assigned_to_channel(channel_id, 2)
			fields_created ['checkboxes', 'electronic_mail_address']
			field_groups_created ['FG One', 'FG Two']

			fields_assinged_to_group('FG One', ['checkboxes'])
			fields_assinged_to_group('FG Two', ['electronic_mail_address'])
		end

        it 'imports a channel with a fluid field' do
			import_channel_set 'channel-with-fluid-field', method: 'issue_duplicate'

            @page.find('input[name="ee:UploadDestination[Images][server_path]"]').set '{base_path}/images/uploads'
            @page.find('input[name="ee:UploadDestination[Images][url]"]').set '/images/uploads'
            @page.find('input[name="ee:UploadDestination[Images][url]"]').trigger 'blur'
            @page.submit

			check_success
			channel_id = @page.get_channel_id_from_name('fluid_fields')

			fields_assigned_to_channel(channel_id, 2)
			field_groups_assigned_to_channel(channel_id, 0)
			fields_created [
                'a_date',
                'checkboxes',
                'corpse',
                'electronic_mail_address',
                'home_page',
                'image',
                'middle_class_text',
                'multi_select',
                'radio',
                'rel_item',
                'selection',
                'stupid_grid',
                'text',
                'truth_or_dare',
                'youtube_url',
			]
        end
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

        # Assure there's now THREE statuses in that group and one of them is the new status_name
        $db.query('SELECT count(*) AS count FROM exp_statuses').each do |row|
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

    context 'with custom fields' do
      it 'imports a relationship field with all channels' do
        import_channel_set 'relationships-all-channels'

        $db.query('SELECT * FROM exp_channel_fields WHERE field_name = "relationships"').each do |row|
          row['field_name'].should == 'relationships'
          field_settings = PHP.unserialize(Base64.decode64(row['field_settings']))
          field_settings['expired'].should == 0
          field_settings['future'].should == 1
          field_settings['allow_multiple'].should == false
          field_settings['limit'].to_i.should == 25
          field_settings['order_field'].should == 'entry_date'
          field_settings['order_dir'].should == 'desc'
          field_settings['channels'].should == []
        end
      end

      it 'imports a relationship field with a specified channel' do
        import_channel_set 'relationships-specified-channels'

        $db.query('SELECT * FROM exp_channel_fields WHERE field_name = "relationships"').each do |row|
          row['field_name'].should == 'relationships'
          field_settings = PHP.unserialize(Base64.decode64(row['field_settings']))
          field_settings['expired'].should == 0
          field_settings['future'].should == 1
          field_settings['allow_multiple'].should == false
          field_settings['limit'].to_i.should == 25
          field_settings['order_field'].should == 'entry_date'
          field_settings['order_dir'].should == 'desc'
          field_settings['channels'].should == {'Event' => "4"}
        end
      end

      it 'imports all other first-party custom fields' do
        import_channel_set 'custom-fields'

        fields = {
          'checkboxes' => {
            'field_list_items' => "Yes\nNo\nMaybe"
          },
          'multi_select' => {
            'field_list_items' => "Red\nGreen\nBlue"
          },
          'radio_buttons' => {
            'field_list_items' => "Left\nCenter\nRight"
          },
          'select_dropdown' => {
            'field_list_items' => "Mac\nWindows\nLinux"
          },
          'prepopulated' => {
            'field_pre_populate' => 'y',
            'field_pre_channel_id' => 2,
            'field_pre_field_id' => 7
          },
          'rich_text_editor' => {
            'field_ta_rows' => 20,
            'field_text_direction' => 'rtl'
          },
          'text_input' => {
            'field_maxl' => 100,
            'field_fmt' => 'none',
            'field_show_fmt' => 'y',
            'field_text_direction' => 'rtl',
            'field_content_type' => 'decimal',
            'field_settings' => {
              'field_show_smileys' => 'y',
              'field_show_file_selector' => 'y'
            }
          },
          'textarea' => {
            'field_ta_rows' => 20,
            'field_fmt' => 'none',
            'field_show_fmt' => 'y',
            'field_text_direction' => 'rtl',
            'field_settings' => {
              'field_show_formatting_btns' => 'y',
              'field_show_smileys' => 'y',
              'field_show_file_selector' => 'y'
            }
          },
          'toggle' => {
            'field_settings' => {
              'field_default_value' => '0'
            }
          },
          'url_field' => {
            'field_settings' => {
              'url_scheme_placeholder' => '//',
              'allowed_url_schemes' => [
                'http://',
                'https://',
                '//',
                'ftp://',
                'sftp://',
                'ssh://'
              ]
            }
          }
        }

        $db.query('SELECT * FROM exp_channel_fields').each do |row|
          if fields.has_key? row['field_name']
            fields[row['field_name']].each do |key, assumed_value|
              if key == 'field_settings'
                field_settings = PHP.unserialize(Base64.decode64(row['field_settings']))
                fields[row['field_name']]['field_settings'].each do |key, value|
                  field_settings[key].should == value
                end
              else
                row[key].should == assumed_value
              end
            end
          end
        end
      end
    end

    context 'with file fields' do
      it 'imports with a specified directory' do
        import_channel_set 'file-specified-directory', method: 'issue_duplicate'

        @page.find('input[name="ee:UploadDestination[Main Upload Directory][name]"]').set 'Uploads'
        @page.find('input[name="ee:UploadDestination[Main Upload Directory][server_path]"]').set '{base_path}/images/uploads'
        @page.find('input[name="ee:UploadDestination[Main Upload Directory][url]"]').set '/images/uploads'
        @page.find('input[name="ee:UploadDestination[Main Upload Directory][url]"]').trigger 'blur'
        @page.submit

        check_success

        upload_dir_id = 0
        $db.query("SELECT id, count(id) AS count FROM exp_upload_prefs WHERE name = 'Uploads' GROUP BY id").each do |row|
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

      it 'imports a grid with all native fields' do
        import_channel_set 'grid-with-everything'

        # Assure we have imported the right Channel, Field Group, Fields, and Grid Columns
        $db.query("SELECT count(*) AS count FROM exp_channels WHERE (channel_name = 'big_grid' AND channel_title = 'Big Grid')").each do |row|
          number_of_channels = row['count']
          number_of_channels.should == 1
        end

        $db.query("SELECT count(*) AS count FROM exp_field_groups WHERE group_name = 'Gridlocked'").each do |row|
          number_of_field_groups = row['count']
          number_of_field_groups.should == 1
        end

        $db.query("SELECT count(*) AS count FROM exp_channel_fields WHERE (field_name = 'zen' AND field_label = 'Zen')").each do |row|
          number_of_fields = row['count']
          number_of_fields.should == 1
        end

        $db.query("SELECT count(*) AS count FROM exp_grid_columns WHERE (col_name = 'checkboxes' AND col_label = 'Checkboxes') OR (col_name = 'date' AND col_label = 'Date') OR (col_name = 'email_address' AND col_label = 'Email Address') OR (col_name = 'file' AND col_label = 'File') OR (col_name = 'multi_select' AND col_label = 'Multi Select') OR (col_name = 'radio_buttons' AND col_label = 'Radio Buttons') OR (col_name = 'relationships' AND col_label = 'Relationships') OR (col_name = 'rich_text_editor' AND col_label = 'Rich Text Editor') OR (col_name = 'select_dropdown' AND col_label = 'Select Dropdown') OR (col_name = 'text_input' AND col_label = 'Text Input') OR (col_name = 'textarea' AND col_label = 'Textarea') OR (col_name = 'toggle' AND col_label = 'Toggle') OR (col_name = 'url' AND col_label = 'URL')").each do |row|
          number_of_columns = row['count']
          number_of_columns.should == 13
        end
      end
    end
  end
end
