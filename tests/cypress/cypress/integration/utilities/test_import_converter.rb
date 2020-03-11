require './bootstrap.rb'

feature 'Import File Converter', () => {

  beforeEach(function() {
    // Paths to files to test
    @tab_file = File.expand_path('support/import-converter/members-tab.txt')
    @comma_file = File.expand_path('support/import-converter/members-comma.txt')
    @pipe_file = File.expand_path('support/import-converter/members-pipe.txt')
    @other_file = File.expand_path('support/import-converter/members-other.txt')

    // Error messages we'll be checking for
    @field_required = 'This field is required.'
    @min_field_error = 'You must have at least 3 fields: username, screen_name, and email address'
    @assign_fields_title = 'Import File Converter - Assign Fields'

    cy.auth();
    page = ImportConverter.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Import File Converter page', () => {
    page.should have_text 'Import File Converter'
    page.should have_text 'Member file'
    page.should have_delimiter
    page.should_not have_delimiter_special
    page.should have_enclosing_char
  }

  it('should validate the form', () => {

    page.attach_file('member_file', @tab_file)

    // Error messages we'll be checking for
    custom_delimit_validation = 'Alphanumeric delimiters not allowed (a-z / 0-9)'
    custom_delimit_required = 'You must provide a delimiting character with the "Other:" option.'

    ###################
    // Validate via AJAX
    ###################

    // "Other" selected but no custom delimiter entered
    page.find('input[value=other]').click()

    // Avoid triggering another request so quickly in succession,
    // seems to be causing some intermittent errors
    sleep 1
    page.delimiter_special.trigger 'blur'

    page.wait_for_error_message_count(1)
    should_have_error_text(page.delimiter_special, custom_delimit_required)
    should_have_form_errors(page)

    page.delimiter_special.set '"'
    page.delimiter_special.trigger 'blur'
    page.wait_for_error_message_count(0)

    // Invalid custom delimiter
    page.delimiter_special.set 'd'
    page.delimiter_special.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.delimiter_special, custom_delimit_validation)
    should_have_form_errors(page)

    page.delimiter_special.set '"'
    page.delimiter_special.trigger 'blur'

    page.wait_for_error_message_count(0)
    should_have_no_error_text(page.delimiter_special)
    should_have_no_form_errors(page)

    cy.hasNoErrors()

    // Should submit successfully now
    page.find('input[value=tab]').click()
    page.submit
    page.should have_text @assign_fields_title
    cy.hasNoErrors()

    #########################
    // Regular form validation
    #########################

    // Don't upload a file
    page.load()
    page.submit
    page.should have_text @field_required
    cy.hasNoErrors()

    // Selected wrong delimiter for file
    page.load()
    page.attach_file('member_file', @tab_file)
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.load()
    page.attach_file('member_file', @tab_file)
    page.find('input[value=tab]').click()
    page.submit
    page.should have_text @assign_fields_title
    cy.hasNoErrors()

    // "Other" selected and no custom delimiter entered
    page.load()
    page.find('input[value=other]').click()
    page.submit
    page.should have_text custom_delimit_required
    cy.hasNoErrors()

    // Test required file and custom delimiter standard validation
    page.load()
    page.find('input[value=other]').click()
    page.delimiter_special.set 'd'
    page.submit
    cy.hasNoErrors()

    page.should have_text 'Attention: File not converted'
    page.should have_text custom_delimit_validation
    should_have_form_errors(page)
    cy.hasNoErrors()
  }

  it('should validate the way files are delimited', () => {
    // Tab file should only work with Tab selected
    page.attach_file('member_file', @tab_file)
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @tab_file)
    page.find('input[value=pipe]').click()
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @tab_file)
    page.find('input[value=other]').click()
    page.delimiter_special.set '*'
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @tab_file)
    page.find('input[value=tab]').click()
    page.submit
    page.should have_no_text @min_field_error
    page.should have_text @assign_fields_title
    cy.hasNoErrors()

    // Comma file should only work with Comma selected
    page.load()
    page.attach_file('member_file', @comma_file)
    page.find('input[value=tab]').click()
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @comma_file)
    page.find('input[value=pipe]').click()
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @comma_file)
    page.find('input[value=other]').click()
    page.delimiter_special.set '*'
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @comma_file)
    page.find('input[value=comma]').click()
    page.submit
    page.should have_no_text @min_field_error
    page.should have_text @assign_fields_title
    cy.hasNoErrors()

    // Pipe file should only work with Pipe selected
    page.load()
    page.attach_file('member_file', @pipe_file)
    page.find('input[value=comma]').click()
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @pipe_file)
    page.find('input[value=tab]').click()
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @pipe_file)
    page.find('input[value=other]').click()
    page.delimiter_special.set '*'
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @pipe_file)
    page.find('input[value=pipe]').click()
    page.submit
    page.should have_no_text @min_field_error
    page.should have_text @assign_fields_title
    cy.hasNoErrors()

    // Special delimiter file should only work with Other selected
    page.load()
    page.attach_file('member_file', @other_file)
    page.find('input[value=comma]').click()
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @other_file)
    page.find('input[value=tab]').click()
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @other_file)
    page.find('input[value=pipe]').click()
    page.submit
    page.should have_text @min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @other_file)
    page.find('input[value=other]').click()
    page.delimiter_special.set '*'
    page.submit
    page.should have_no_text @min_field_error
    page.should have_text @assign_fields_title
    cy.hasNoErrors()
  }

  it('should validate assigned fields', () => {
    username_error = 'You must assign a field to "username"'
    screenname_error = 'You must assign a field to "screen_name"'
    email_error = 'You must assign a field to "email"'
    duplicate_error = 'Duplicate field assignment: username'
    form_error = 'Attention: File not converted'


    page.attach_file('member_file', @tab_file)
    page.find('input[value=tab]').click()
    page.submit
    page.should have_text @assign_fields_title
    page.should have_text 'member1'
    page.should have_text 'Member1'
    page.should have_text 'member1@fake.com'
    cy.hasNoErrors()

    page.submit
    page.should have_text form_error
    page.should have_text username_error
    page.should have_text screenname_error
    page.should have_text email_error
    cy.hasNoErrors()

    page.field1.select 'username'
    page.submit
    page.should have_text form_error
    page.should have_no_text username_error
    page.should have_text screenname_error
    page.should have_text email_error
    cy.hasNoErrors()

    page.field2.select 'username'
    page.submit
    page.should have_text form_error
    page.should have_no_text username_error
    page.should have_text duplicate_error
    page.should have_text screenname_error
    page.should have_text email_error
    cy.hasNoErrors()

    page.field2.select 'screen_name'
    page.field3.select 'password'
    page.submit
    page.should have_text form_error
    page.should have_no_text username_error
    page.should have_no_text duplicate_error
    page.should have_no_text screenname_error
    page.should have_text email_error
    cy.hasNoErrors()

    page.field4.select 'email'
    page.submit
    page.should have_text 'Confirm Assignments'
    cy.hasNoErrors()
  }

  it('should generate valid XML for the member importer', () => {
    page.attach_file('member_file', @tab_file)
    page.find('input[value=tab]').click()
    page.submit
    page.field1.select 'username'
    page.field2.select 'screen_name'
    page.field3.select 'password'
    page.field4.select 'email'
    page.submit
    page.should have_text 'Confirm Assignments'
    page.submit
    cy.hasNoErrors()

    page.should have_text 'XML Code'
    page.xml_code.should have_text capybaraify_string('<members>
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

    page.load()
    page.attach_file('member_file', @tab_file)
    page.find('input[value=tab]').click()
    page.submit
    page.field1.select 'username'
    page.field2.select 'screen_name'
    page.field3.select 'password'
    page.field4.select 'email'
    page.find('input[value=n]').click()
    page.submit
    page.should have_text 'Confirm Assignments'
    page.submit
    cy.hasNoErrors()

    page.should have_text 'XML Code'
    page.xml_code.should have_text capybaraify_string('<members>
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

  }

}
