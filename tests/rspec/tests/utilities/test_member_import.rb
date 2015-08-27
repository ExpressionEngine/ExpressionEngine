require './bootstrap.rb'

feature 'Member Import' do

  before(:each) do
    # Paths to files to test
    @members_xml = asset_path('member-import/members.xml')
    @members_xml_duplicate = asset_path('member-import/members-duplicate.xml')
    @members_xml_invalid = asset_path('member-import/members-invalid.xml')
    @members_xml_custom = asset_path('member-import/members-custom.xml')

    @field_required = 'This field is required.'
    @invalid_path_error = 'The path you submitted is not valid.'

    cp_session
    @page = MemberImport.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Member Import page' do
    @page.should have_text 'Member Import'
    @page.should have_text 'XML file location'
    @page.should have_member_group
    @page.should have_language
    @page.should have_tz_country
    @page.should have_timezone
    @page.should have_date_format
    @page.should have_time_format
    @page.should have_custom_yes
    @page.should have_custom_no
  end

  it 'should validate the file location' do
    @page.submit

    no_php_js_errors
    should_have_error_text(@page.file_location, @field_required)
    @page.should have_text 'Attention: Import not completed'
    should_have_form_errors(@page)

    @page.load
    @page.file_location.set '/some/bogus/path'
    @page.submit

    no_php_js_errors
    should_have_error_text(@page.file_location, @invalid_path_error)
    @page.should have_text 'Attention: Import not completed'
    should_have_form_errors(@page)

    @page.file_location.set @members_xml
    @page.file_location.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.file_location)
    should_have_no_form_errors(@page)

    # Reset for AJAX validation
    @page.load
    @page.file_location.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.file_location, @field_required)
    should_have_form_errors(@page)

    @page.file_location.set '/some/bogus/path'
    @page.file_location.trigger 'blur'
    should_have_error_text(@page.file_location, @invalid_path_error)
    should_have_form_errors(@page)

    @page.file_location.set @members_xml
    @page.file_location.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.file_location)
    should_have_no_form_errors(@page)
  end

  it 'should show the confirm import screen' do
    @page.file_location.set @members_xml
    @page.member_group.select 'Members'
    @page.language.select 'English'
    @page.tz_country.select 'United States'
    @page.timezone.select 'New York'
    @page.date_format.select 'yyyy-mm-dd'
    @page.time_format.select '24-hour'
    @page.include_seconds_y.click
    @page.custom_no.click
    @page.submit

    @page.options.map {|option| option.text}.should ==
        ['XML file location', 'Member group', 'Language', 'Timezone',
            'Date & time format', 'Show seconds?', 'Create custom fields?']
    @page.values.map {|value| value.text}.should ==
        [@members_xml, 'Members', 'English',
            'America/New_York', 'yyyy-mm-dd, 24-hour', 'yes', 'no']
  end

  it 'should import basic member import file' do
    @page.file_location.set @members_xml
    @page.member_group.select 'Members'
    @page.language.select 'English'
    @page.tz_country.select 'United States'
    @page.timezone.select 'New York'
    @page.date_format.select 'yyyy-mm-dd'
    @page.time_format.select '24-hour'
    @page.custom_no.click
    @page.submit
    no_php_js_errors

    # Confirm the import
    @page.submit

    @page.should have_text 'Members Imported Successfully'
    @page.should have_text 'Total of 3 members imported.'
  end

  it 'should fail to import duplicate data' do
    @page.file_location.set @members_xml_duplicate
    @page.submit

    # Confirm the import
    @page.submit

    @page.should have_text 'Confirm Import'
    @page.should have_text "The username you chose is not available (Username: 'admin' - within user record 'admin')"
    @page.should have_text "The email you submitted is not valid (Email: 'robertexample.com' - within user record 'robr')"
    @page.should have_text "Duplicate username: robr"
  end

  it 'should fail to import invalid XML' do
    @page.file_location.set @members_xml_invalid
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
    @page.file_location.set @members_xml
    @page.custom_yes.click
    @page.submit

    @page.should have_text 'Confirm Import'
    no_php_js_errors

    # If our XML contains extra field but we elect not to bother:
    @page.load
    @page.file_location.set @members_xml_custom
    @page.custom_no.click
    @page.submit

    @page.should have_text 'Confirm Import'
  end

  it 'should create custom fields' do
    @page.file_location.set @members_xml_custom
    @page.member_group.select 'Members'
    @page.language.select 'English'
    @page.tz_country.select 'United States'
    @page.timezone.select 'New York'
    @page.date_format.select 'yyyy-mm-dd'
    @page.time_format.select '24-hour'
    @page.custom_yes.click
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