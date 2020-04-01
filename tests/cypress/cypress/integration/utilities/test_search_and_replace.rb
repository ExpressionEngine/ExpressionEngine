require './bootstrap.rb'

context('Search and Replace', () => {

  beforeEach(function() {
    cy.auth();
    page = SearchAndReplace.new
    page.load()
    cy.hasNoErrors()

    @field_required = 'This field is required.'
  }

  it('shows the Search and Replace page', () => {
    page.get('wrap').contains('Data Search and Replace'
    page.get('wrap').contains('Advanced users only.'
    page.all_there?.should == true
  }

  it('should validate the form', () => {
    page.submit_enabled?.should eq true

    page.search_term.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.search_term, @field_required)
    should_have_form_errors(page)

    page.search_term.clear().type('Text'
    page.search_term.trigger 'blur'
    page.wait_for_error_message_count(0)
    should_have_no_error_text(page.search_term)
    should_have_no_form_errors(page)

    page.replace_where.select('Site Preferences (Choose from the following)'
    page.wait_for_error_message_count(1)
    should_have_no_error_text(page.search_term)
    should_have_error_text(page.replace_where, @field_required)
    should_have_form_errors(page)

    page.password_auth.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_no_error_text(page.search_term)
    should_have_error_text(page.replace_where, @field_required)
    should_have_error_text(page.password_auth, @field_required)
    should_have_form_errors(page)

    page.password_auth.clear().type('password'
    page.password_auth.trigger 'blur'
    page.wait_for_error_message_count(1)

    page.password_auth.clear().type('test'
    page.password_auth.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_no_error_text(page.search_term)
    should_have_error_text(page.replace_where, @field_required)
    should_have_error_text(page.password_auth, 'The password entered is incorrect.')
    should_have_form_errors(page)

    page.password_auth.clear().type('password'
    page.password_auth.trigger 'blur'
    page.wait_for_error_message_count(1)
    page.replace_where.select('Channel Entry Titles'
    page.wait_for_error_message_count(0)

    should_have_no_error_text(page.search_term)
    should_have_no_error_text(page.replace_where)
    should_have_no_error_text(page.replace_term)
    should_have_no_error_text(page.password_auth)
    should_have_no_form_errors(page)

    cy.hasNoErrors()

    page.submit

    cy.hasNoErrors()

    page.get('wrap').contains('Action was a success'
    page.get('wrap').contains('Number of database records in which a replacement occurred: 0'
  }

  it('should fail validation without AJAX too', () => {
    page.submit

    page.get('wrap').contains('Attention: Search and replace not run'
    should_have_error_text(page.search_term, @field_required)
    should_have_error_text(page.replace_where, @field_required)
    should_have_error_text(page.password_auth, @field_required)
    should_have_form_errors(page)

    cy.hasNoErrors()

    page.search_term.clear().type('Text'
    page.search_term.trigger 'blur'
    page.wait_for_error_message_count(2)
    page.replace_where.select('Channel Entry Titles'
    page.wait_for_error_message_count(1)
    page.password_auth.clear().type('password'
    page.password_auth.trigger 'blur'
    page.wait_for_error_message_count(0)

    should_have_no_error_text(page.search_term)
    should_have_no_error_text(page.replace_where)
    should_have_no_error_text(page.replace_term)
    should_have_no_error_text(page.password_auth)
    should_have_no_form_errors(page)

    cy.hasNoErrors()

    page.submit

    cy.hasNoErrors()

    page.get('wrap').contains('Action was a success'
    page.get('wrap').contains('Number of database records in which a replacement occurred: 0'
  }

  it('should search and replace data', () => {

    page.search_term.clear().type('Welcome'
    page.replace_term.clear().type('test'
    page.replace_where.select('Channel Entry Titles'
    page.password_auth.clear().type('password'

    page.submit

    cy.hasNoErrors()

    page.get('wrap').contains('Action was a success'
    page.get('wrap').contains('Number of database records in which a replacement occurred: 1'
  }

}
