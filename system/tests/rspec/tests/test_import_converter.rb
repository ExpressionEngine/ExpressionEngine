require './bootstrap.rb'

feature 'Import File Converter' do

  before(:each) do
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
    file_required = 'The "File location" field is required.'
    file_location_validation = 'The path you submitted is not valid.'
    custom_delimit_validation = 'Alphanumeric delimiters not allowed (a-z / 0-9)'
    custom_delimit_required = 'You must provide a delimiting character with the "Other:" option.'
    min_field_error = 'You must have at least 3 fields: username, screen_name, and email address'

    ###################
    # Validate via AJAX
    ###################
    
    # Bogus path
    @page.file_location.set '/some/bogus/path'
    @page.file_location.trigger 'blur'

    @page.should have_text file_location_validation
    @page.submit_enabled?.should eq false
    @page.has_errors?.should eq true

    @page.file_location.set 'tests/rspec/support/import-converter/members.txt'
    @page.file_location.trigger 'blur'

    @page.should have_no_text file_location_validation
    @page.submit_enabled?.should eq true
    @page.has_errors?.should eq false

    # "Other" selected but no custom delimiter entered
    @page.find('input[value=other]').click
    @page.delimiter_special.trigger 'blur'

    @page.should have_text custom_delimit_required
    @page.submit_enabled?.should eq false
    @page.has_errors?.should eq true

    # Invalid custom delimiter
    @page.delimiter_special.set 'd'
    @page.delimiter_special.trigger 'blur'
    @page.should have_no_text custom_delimit_required
    @page.should have_text custom_delimit_validation

    @page.submit_enabled?.should eq false
    @page.has_errors?.should eq true

    @page.delimiter_special.set '"'
    @page.delimiter_special.trigger 'blur'

    @page.should have_no_text custom_delimit_validation
    @page.submit_enabled?.should eq true
    @page.has_errors?.should eq false

    #########################
    # Regular form validation
    #########################
    
    # No file path
    @page.load
    @page.submit_button.click
    @page.should have_text file_required

    # Bogus path entered
    @page.load
    @page.file_location.set '/some/bogus/path'
    @page.submit_button.click

    @page.should have_text 'An error occurred'
    @page.should have_text file_location_validation
    @page.submit_enabled?.should eq false
    @page.has_errors?.should eq true

    @page.file_location.set 'tests/rspec/support/import-converter/members.txt'
    @page.file_location.trigger 'blur'

    @page.should have_no_text file_location_validation
    @page.submit_enabled?.should eq true
    @page.has_errors?.should eq false

    # Selected wrong delimiter for file
    @page.submit_button.click
    @page.should have_text min_field_error

    @page.file_location.set 'tests/rspec/support/import-converter/members.txt'
    @page.find('input[value=tab]').click

    @page.submit_button.click
    @page.should have_text "Import File Converter - Assign Fields"

    # "Other" selected and no custom delimiter entered
    @page.load
    @page.find('input[value=other]').click
    @page.submit_button.click
    @page.should have_text file_required
    @page.should have_text custom_delimit_required

    # Test required file and custom delimiter standard validation
    @page.load
    @page.find('input[value=other]').click
    @page.delimiter_special.set 'd'
    @page.submit_button.click

    @page.should have_text 'An error occurred'
    @page.should have_text file_required
    @page.should have_text custom_delimit_validation
    @page.submit_enabled?.should eq false
    @page.has_errors?.should eq true
  end

end