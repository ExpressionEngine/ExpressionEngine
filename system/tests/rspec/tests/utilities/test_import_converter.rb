require './bootstrap.rb'

feature 'Import File Converter' do

  before(:each) do
    # Paths to files to test
    @tab_file = asset_path('import-converter/members-tab.txt')
    @comma_file = asset_path('import-converter/members-comma.txt')
    @pipe_file = asset_path('import-converter/members-pipe.txt')
    @other_file = asset_path('import-converter/members-other.txt')

    # Error messages we'll be checking for
    @field_required = 'This field is required.'
    @file_location_validation = 'The path you submitted is not valid.'
    @min_field_error = 'You must have at least 3 fields: username, screen_name, and email address'
    @assign_fields_title = 'Import File Converter - Assign Fields'

    cp_session
    @page = ImportConverter.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Import File Converter page' do
    @page.should have_text 'Import File Converter'
    @page.should have_text 'File location'
    @page.should have_file_location
    @page.should have_delimiter
    @page.should have_enclosing_char
  end

  it 'should validate the form' do

    # Error messages we'll be checking for
    custom_delimit_validation = 'Alphanumeric delimiters not allowed (a-z / 0-9)'
    custom_delimit_required = 'You must provide a delimiting character with the "Other:" option.'

    ###################
    # Validate via AJAX
    ###################
    
    # No path
    @page.file_location.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.file_location, @field_required)
    should_have_form_errors(@page)
    
    # Bogus path
    @page.file_location.set '/some/bogus/path'
    @page.file_location.trigger 'blur'
    should_have_error_text(@page.file_location, @file_location_validation)
    should_have_form_errors(@page)

    @page.file_location.set @tab_file
    @page.file_location.trigger 'blur'

    @page.wait_for_error_message_count(0)
    @page.should have_no_text @file_location_validation
    should_have_no_form_errors(@page)

    # "Other" selected but no custom delimiter entered
    @page.find('input[value=other]').click

    # Avoid triggering another request so quickly in succession,
    # seems to be causing some intermittent errors
    sleep 1
    @page.delimiter_special.trigger 'blur'

    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.delimiter_special, custom_delimit_required)
    should_have_form_errors(@page)

    # Invalid custom delimiter
    @page.delimiter_special.set 'd'
    @page.delimiter_special.trigger 'blur'
    should_have_error_text(@page.delimiter_special, custom_delimit_validation)
    should_have_form_errors(@page)

    @page.delimiter_special.set '"'
    @page.delimiter_special.trigger 'blur'

    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.delimiter_special)
    should_have_no_form_errors(@page)

    no_php_js_errors

    # Should submit successfully now
    @page.find('input[value=tab]').click
    @page.submit_button.click
    @page.should have_text @assign_fields_title
    no_php_js_errors

    #########################
    # Regular form validation
    #########################
    
    # No file path
    @page.load
    @page.submit_button.click
    @page.should have_text @file_required
    should_have_error_text(@page.file_location, @field_required)
    no_php_js_errors

    # Bogus path entered
    @page.load
    @page.file_location.set '/some/bogus/path'
    @page.submit_button.click
    no_php_js_errors

    @page.should have_text 'Attention: File not converted'
    should_have_error_text(@page.file_location, @file_location_validation)
    should_have_form_errors(@page)

    @page.file_location.set @tab_file
    @page.file_location.trigger 'blur'

    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.file_location)
    should_have_no_form_errors(@page)

    no_php_js_errors

    # Selected wrong delimiter for file
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @tab_file
    @page.find('input[value=tab]').click

    @page.submit_button.click
    @page.should have_text @assign_fields_title
    no_php_js_errors

    # "Other" selected and no custom delimiter entered
    @page.load
    @page.find('input[value=other]').click
    @page.submit_button.click
    should_have_error_text(@page.file_location, @field_required)
    @page.should have_text custom_delimit_required
    no_php_js_errors

    # Test required file and custom delimiter standard validation
    @page.load
    @page.find('input[value=other]').click
    @page.delimiter_special.set 'd'
    @page.submit_button.click
    no_php_js_errors

    @page.should have_text 'Attention: File not converted'
    should_have_error_text(@page.file_location, @field_required)
    @page.should have_text custom_delimit_validation
    should_have_form_errors(@page)
    no_php_js_errors
  end

  it 'should validate the way files are delimited' do
    # Tab file should only work with Tab selected
    @page.file_location.set @tab_file
    @page.file_location.trigger 'blur'
    @page.should have_no_text @field_required
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @tab_file
    @page.find('input[value=pipe]').click
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @tab_file
    @page.find('input[value=other]').click
    @page.delimiter_special.set '*'
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @tab_file
    @page.find('input[value=tab]').click
    @page.submit_button.click
    @page.should have_no_text @min_field_error
    @page.should have_text @assign_fields_title
    no_php_js_errors

    # Comma file should only work with Comma selected
    @page.load
    @page.file_location.set @comma_file
    @page.find('input[value=tab]').click
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @comma_file
    @page.find('input[value=pipe]').click
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @comma_file
    @page.find('input[value=other]').click
    @page.delimiter_special.set '*'
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @comma_file
    @page.find('input[value=comma]').click
    @page.submit_button.click
    @page.should have_no_text @min_field_error
    @page.should have_text @assign_fields_title
    no_php_js_errors

    # Pipe file should only work with Pipe selected
    @page.load
    @page.file_location.set @pipe_file
    @page.find('input[value=comma]').click
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @pipe_file
    @page.find('input[value=tab]').click
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @pipe_file
    @page.find('input[value=other]').click
    @page.delimiter_special.set '*'
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @pipe_file
    @page.find('input[value=pipe]').click
    @page.submit_button.click
    @page.should have_no_text @min_field_error
    @page.should have_text @assign_fields_title
    no_php_js_errors

    # Special delimiter file should only work with Other selected
    @page.load
    @page.file_location.set @other_file
    @page.find('input[value=comma]').click
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @other_file
    @page.find('input[value=tab]').click
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @other_file
    @page.find('input[value=pipe]').click
    @page.submit_button.click
    @page.should have_text @min_field_error
    no_php_js_errors

    @page.file_location.set @other_file
    @page.find('input[value=other]').click
    @page.delimiter_special.set '*'
    @page.submit_button.click
    @page.should have_no_text @min_field_error
    @page.should have_text @assign_fields_title
    no_php_js_errors
  end

  it 'should validate assigned fields' do
    username_error = 'You must assign a field to "username"'
    screenname_error = 'You must assign a field to "screen_name"'
    email_error = 'You must assign a field to "email"'
    duplicate_error = 'Duplicate field assignment: username'
    form_error = 'Attention: File not converted'

    @page.file_location.set @tab_file
    @page.find('input[value=tab]').click
    @page.submit_button.click
    @page.should have_text @assign_fields_title
    @page.should have_text 'member1'
    @page.should have_text 'Member1'
    @page.should have_text 'member1@fake.com'
    no_php_js_errors

    @page.submit_button.click
    @page.should have_text form_error
    @page.should have_text username_error
    @page.should have_text screenname_error
    @page.should have_text email_error
    no_php_js_errors

    @page.field1.select 'username'
    @page.submit_button.click
    @page.should have_text form_error
    @page.should have_no_text username_error
    @page.should have_text screenname_error
    @page.should have_text email_error
    no_php_js_errors

    @page.field2.select 'username'
    @page.submit_button.click
    @page.should have_text form_error
    @page.should have_no_text username_error
    @page.should have_text duplicate_error
    @page.should have_text screenname_error
    @page.should have_text email_error
    no_php_js_errors

    @page.field2.select 'screen_name'
    @page.field3.select 'password'
    @page.submit_button.click
    @page.should have_text form_error
    @page.should have_no_text username_error
    @page.should have_no_text duplicate_error
    @page.should have_no_text screenname_error
    @page.should have_text email_error
    no_php_js_errors

    @page.field4.select 'email'
    @page.submit_button.click
    @page.should have_text 'Confirm Assignments'
    no_php_js_errors
  end

  it 'should generate valid XML for the member importer' do
    @page.file_location.set @tab_file
    @page.find('input[value=tab]').click
    @page.submit_button.click
    @page.field1.select 'username'
    @page.field2.select 'screen_name'
    @page.field3.select 'password'
    @page.field4.select 'email'
    @page.submit_button.click
    @page.should have_text 'Confirm Assignments'
    @page.submit_button.click
    no_php_js_errors

    @page.should have_text 'XML Code'
    @page.xml_code.should have_text capybaraify_string('<members>
    <member>
        <username>member1</username>
        <screen_name>Member1</screen_name>
        <password type="text">password</password>
        <email>member1@fake.com</email>
    </member>
    <member>
        <username>member2</username>
        <screen_name>Member2</screen_name>
        <password type="text">password</password>
        <email>member2@fake.com</email>
    </member>
    <member>
        <username>member3</username>
        <screen_name>Member3</screen_name>
        <password type="text">password</password>
        <email>member3@fake.com</email>
    </member>
    <member>
        <username>member4</username>
        <screen_name>Member4</screen_name>
        <password type="text">password</password>
        <email>member4@fake.com</email>
    </member>
</members>')

    @page.load
    @page.file_location.set @tab_file
    @page.find('input[value=tab]').click
    @page.submit_button.click
    @page.field1.select 'username'
    @page.field2.select 'screen_name'
    @page.field3.select 'password'
    @page.field4.select 'email'
    @page.find('input[value=n]').click
    @page.submit_button.click
    @page.should have_text 'Confirm Assignments'
    @page.submit_button.click
    no_php_js_errors

    @page.should have_text 'XML Code'
    @page.xml_code.should have_text capybaraify_string('<members>
    <member>
        <username>member1</username>
        <screen_name>Member1</screen_name>
        <password>password</password>
        <email>member1@fake.com</email>
    </member>
    <member>
        <username>member2</username>
        <screen_name>Member2</screen_name>
        <password>password</password>
        <email>member2@fake.com</email>
    </member>
    <member>
        <username>member3</username>
        <screen_name>Member3</screen_name>
        <password>password</password>
        <email>member3@fake.com</email>
    </member>
    <member>
        <username>member4</username>
        <screen_name>Member4</screen_name>
        <password>password</password>
        <email>member4@fake.com</email>
    </member>
</members>')

  end

end