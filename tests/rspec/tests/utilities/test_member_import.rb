require './bootstrap.rb'

feature 'Member Import' do

  before(:each) do
    # Paths to files to test
    @members_xml = File.expand_path('support/member-import/members.xml')
    @members_xml_duplicate = File.expand_path('support/member-import/members-duplicate.xml')
    @members_xml_invalid = File.expand_path('support/member-import/members-invalid.xml')
    @members_xml_custom = File.expand_path('support/member-import/members-custom.xml')

    @field_required = 'This field is required.'

    cp_session
    @page = MemberImport.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Member Import page' do
    @page.should have_text 'Member Import'
    @page.should have_text 'Member XML file'
    @page.should have_role
    @page.should have_language
    @page.should have_tz_country
    @page.should have_timezone
    @page.should have_date_format
    @page.should have_time_format
    @page.should have_auto_custom_field
    @page.should have_include_seconds
  end




  it 'should show the confirm import screen' do
    @page.attach_file('member_xml_file', @members_xml)
    @page.role.choose_radio_option('5')
    @page.language.choose_radio_option('english')
    @page.tz_country.select 'United States'
    @page.timezone.select 'New York'
    @page.date_format.choose_radio_option('%Y-%m-%d')
    @page.time_format.choose_radio_option('24')
    @page.include_seconds_toggle.click
    @page.auto_custom_field_toggle.click
    @page.submit

    @page.options.map {|option| option.text}.should ==
        ['Role', 'Language', 'Timezone',
            'Date & time format', 'Show seconds?', 'Create custom fields?']
    @page.values.map {|value| value.text}.should ==
        ['Members', 'English',
            'America/New_York', 'yyyy-mm-dd, 24-hour', 'Yes', 'No']
  end

  it 'should import basic member import file' do
    @page.attach_file('member_xml_file', @members_xml)
    @page.role.choose_radio_option('5')
    @page.language.choose_radio_option('english')
    @page.tz_country.select 'United States'
    @page.timezone.select 'New York'
    @page.date_format.choose_radio_option('%Y-%m-%d')
    @page.time_format.choose_radio_option('24')
    @page.auto_custom_field_toggle.click
    @page.submit
    no_php_js_errors

    # Confirm the import
    @page.submit

    @page.should have_text 'Members Imported Successfully'
    @page.should have_text 'Total of 3 members imported.'
  end

  it 'should fail to import duplicate data' do
    @page.attach_file('member_xml_file', @members_xml_duplicate)
    @page.role.choose_radio_option '5'
    @page.submit

    # Confirm the import
    @page.submit

    @page.should have_text 'Confirm Import'
    @page.should have_text "The username you chose is not available (Username: 'admin' - within user record 'admin')"
    @page.should have_text "The email you submitted is not valid (Email: 'robertexample.com' - within user record 'robr')"
    @page.should have_text "Duplicate username: robr"
  end

  it 'should fail to import invalid XML' do
    @page.attach_file('member_xml_file', @members_xml_invalid)
    @page.role.choose_radio_option '5'
    @page.submit

    # Confirm the import
    @page.submit

    @page.should have_text 'Confirm Import'
    @page.should have_text 'Unable to parse XML'
    @page.should have_text 'Check the XML file for any incorrect syntax.'
  end

  it 'should bypass custom field creation in some cases' do
    # If our XML does not contain any extra fields but Yes is selected
    # for custom field creation:
    @page.attach_file('member_xml_file', @members_xml)
    @page.role.choose_radio_option '5'
    @page.submit

    @page.should have_text 'Confirm Import'
    no_php_js_errors

    # If our XML contains extra field but we elect not to bother:
    @page.load
   @page.attach_file('member_xml_file', @members_xml_custom)
    @page.role.choose_radio_option '5'
    @page.auto_custom_field_toggle.click
    @page.submit

    @page.should have_text 'Confirm Import'
  end

  it 'should create custom fields' do
    @page.attach_file('member_xml_file', @members_xml_custom)
    @page.role.choose_radio_option('5')
    @page.language.choose_radio_option('english')
    @page.tz_country.select 'United States'
    @page.timezone.select 'New York'
    @page.date_format.choose_radio_option('%Y-%m-%d')
    @page.time_format.choose_radio_option('24')
    @page.submit

    no_php_js_errors
    @page.should have_text 'Map Custom Fields'
    @page.custom_field_1_name.value.should eq 'phone'
    @page.custom_field_2_name.value.should eq 'address'

    @page.select_all.click
    @page.submit

    no_php_js_errors
    @page.should have_text 'The following custom member fields were successfully added:'
    @page.should have_text 'phone address'
    @page.submit

    @page.should have_text 'Members Imported Successfully'
    @page.should have_text 'Total of 3 members imported.'
  end
end
