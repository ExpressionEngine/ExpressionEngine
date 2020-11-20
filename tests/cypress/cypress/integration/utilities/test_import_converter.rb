require './bootstrap.rb'

context('Import File Converter', () => {

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
    page.get('wrap').contains('Import File Converter'
    page.get('wrap').contains('Member file'
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
    cy.wait(1000)
    page.delimiter_special.blur()

    page.hasErrorsCount(1)
    page.hasError(page.delimiter_special, custom_delimit_required)
    page.hasErrors()
//should_have_form_errors(page)

    page.delimiter_special.clear().type('"'
    page.delimiter_special.blur()
    page.hasErrorsCount(0)

    // Invalid custom delimiter
    page.delimiter_special.clear().type('d'
    page.delimiter_special.blur()
    page.hasErrorsCount(1)
    page.hasError(page.delimiter_special, custom_delimit_validation)
    page.hasErrors()
//should_have_form_errors(page)

    page.delimiter_special.clear().type('"'
    page.delimiter_special.blur()

    page.hasErrorsCount(0)
    should_have_no_error_text(page.delimiter_special)
    should_have_no_form_errors(page)

    cy.hasNoErrors()

    // Should submit successfully now
    page.find('input[value=tab]').click()
    page.submit
    page.get('wrap').contains(@assign_fields_title
    cy.hasNoErrors()

    #########################
    // Regular form validation
    #########################

    // Don't upload a file
    page.load()
    page.submit
    page.get('wrap').contains(@field_required
    cy.hasNoErrors()

    // Selected wrong delimiter for file
    page.load()
    page.attach_file('member_file', @tab_file)
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.load()
    page.attach_file('member_file', @tab_file)
    page.find('input[value=tab]').click()
    page.submit
    page.get('wrap').contains(@assign_fields_title
    cy.hasNoErrors()

    // "Other" selected and no custom delimiter entered
    page.load()
    page.find('input[value=other]').click()
    page.submit
    page.get('wrap').contains(custom_delimit_required
    cy.hasNoErrors()

    // Test required file and custom delimiter standard validation
    page.load()
    page.find('input[value=other]').click()
    page.delimiter_special.clear().type('d'
    page.submit
    cy.hasNoErrors()

    page.get('wrap').contains('Attention: File not converted'
    page.get('wrap').contains(custom_delimit_validation
    page.hasErrors()
//should_have_form_errors(page)
    cy.hasNoErrors()
  }

  it('should validate the way files are delimited', () => {
    // Tab file should only work with Tab selected
    page.attach_file('member_file', @tab_file)
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @tab_file)
    page.find('input[value=pipe]').click()
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @tab_file)
    page.find('input[value=other]').click()
    page.delimiter_special.clear().type('*'
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @tab_file)
    page.find('input[value=tab]').click()
    page.submit
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( @min_field_error
    page.get('wrap').contains(@assign_fields_title
    cy.hasNoErrors()

    // Comma file should only work with Comma selected
    page.load()
    page.attach_file('member_file', @comma_file)
    page.find('input[value=tab]').click()
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @comma_file)
    page.find('input[value=pipe]').click()
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @comma_file)
    page.find('input[value=other]').click()
    page.delimiter_special.clear().type('*'
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @comma_file)
    page.find('input[value=comma]').click()
    page.submit
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( @min_field_error
    page.get('wrap').contains(@assign_fields_title
    cy.hasNoErrors()

    // Pipe file should only work with Pipe selected
    page.load()
    page.attach_file('member_file', @pipe_file)
    page.find('input[value=comma]').click()
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @pipe_file)
    page.find('input[value=tab]').click()
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @pipe_file)
    page.find('input[value=other]').click()
    page.delimiter_special.clear().type('*'
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @pipe_file)
    page.find('input[value=pipe]').click()
    page.submit
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( @min_field_error
    page.get('wrap').contains(@assign_fields_title
    cy.hasNoErrors()

    // Special delimiter file should only work with Other selected
    page.load()
    page.attach_file('member_file', @other_file)
    page.find('input[value=comma]').click()
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @other_file)
    page.find('input[value=tab]').click()
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @other_file)
    page.find('input[value=pipe]').click()
    page.submit
    page.get('wrap').contains(@min_field_error
    cy.hasNoErrors()

    page.attach_file('member_file', @other_file)
    page.find('input[value=other]').click()
    page.delimiter_special.clear().type('*'
    page.submit
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( @min_field_error
    page.get('wrap').contains(@assign_fields_title
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
    page.get('wrap').contains(@assign_fields_title
    page.get('wrap').contains('member1'
    page.get('wrap').contains('Member1'
    page.get('wrap').contains('member1@fake.com'
    cy.hasNoErrors()

    page.submit
    page.get('wrap').contains(form_error
    page.get('wrap').contains(username_error
    page.get('wrap').contains(screenname_error
    page.get('wrap').contains(email_error
    cy.hasNoErrors()

    page.field1.select('username'
    page.submit
    page.get('wrap').contains(form_error
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( username_error
    page.get('wrap').contains(screenname_error
    page.get('wrap').contains(email_error
    cy.hasNoErrors()

    page.field2.select('username'
    page.submit
    page.get('wrap').contains(form_error
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( username_error
    page.get('wrap').contains(duplicate_error
    page.get('wrap').contains(screenname_error
    page.get('wrap').contains(email_error
    cy.hasNoErrors()

    page.field2.select('screen_name'
    page.field3.select('password'
    page.submit
    page.get('wrap').contains(form_error
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( username_error
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( duplicate_error
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( screenname_error
    page.get('wrap').contains(email_error
    cy.hasNoErrors()

    page.field4.select('email'
    page.submit
    page.get('wrap').contains('Confirm Assignments'
    cy.hasNoErrors()
  }

  it('should generate valid XML for the member importer', () => {
    page.attach_file('member_file', @tab_file)
    page.find('input[value=tab]').click()
    page.submit
    page.field1.select('username'
    page.field2.select('screen_name'
    page.field3.select('password'
    page.field4.select('email'
    page.submit
    page.get('wrap').contains('Confirm Assignments'
    page.submit
    cy.hasNoErrors()

    page.get('wrap').contains('XML Code'
    page.xml_code.contains(capybaraify_string('<members>
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
    page.field1.select('username'
    page.field2.select('screen_name'
    page.field3.select('password'
    page.field4.select('email'
    page.find('input[value=n]').click()
    page.submit
    page.get('wrap').contains('Confirm Assignments'
    page.submit
    cy.hasNoErrors()

    page.get('wrap').contains('XML Code'
    page.xml_code.contains(capybaraify_string('<members>
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
