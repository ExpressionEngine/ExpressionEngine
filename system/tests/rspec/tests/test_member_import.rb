require './bootstrap.rb'

feature 'Member Import' do

  before(:each) do
    # Paths to files to test
    @members_xml = asset_path('member-import/members.xml')

    @file_required_error = 'The "XML file location" field is required.'
    @invalid_path_error = 'The path you submitted is not valid.'

    cp_session
    @page = MemberImport.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Member Import page' do
    @page.should have_text 'Member Import Utility'
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
    @page.submit_button.click

    no_php_js_errors
    @page.should have_text @file_required_error
    @page.should have_text 'An error occurred'
    should_have_form_errors(@page)

    @page.load
    @page.file_location.set '/some/bogus/path'
    @page.submit_button.click

    no_php_js_errors
    @page.should have_no_text @file_required_error
    @page.should have_text @invalid_path_error
    @page.should have_text 'An error occurred'
    should_have_form_errors(@page)

    @page.file_location.set @members_xml
    @page.file_location.trigger 'blur'
    @page.should have_no_text @file_required_error
    @page.should have_no_text @invalid_path_error
    should_have_no_form_errors(@page)

    # Reset for AJAX validation
    @page.load
    @page.file_location.trigger 'blur'
    @page.should have_text @file_required_error
    @page.should have_no_text @invalid_path_error
    should_have_form_errors(@page)

    @page.file_location.set '/some/bogus/path'
    @page.file_location.trigger 'blur'
    @page.should have_no_text @file_required_error
    @page.should have_text @invalid_path_error
    should_have_form_errors(@page)

    @page.file_location.set @members_xml
    @page.file_location.trigger 'blur'
    @page.should have_no_text @file_required_error
    @page.should have_no_text @invalid_path_error
    should_have_no_form_errors(@page)
  end

end