require './bootstrap.rb'

feature 'Search and Replace', () => {

  beforeEach(function() {
    cy.auth();
    page = SearchAndReplace.new
    page.load()
    cy.hasNoErrors()

    @field_required = 'This field is required.'
  }

  it('shows the Search and Replace page', () => {
    page.should have_text 'Data Search and Replace'
    page.should have_text 'Advanced users only.'
    page.all_there?.should == true
  }

  it('should validate the form', () => {
    page.submit_enabled?.should eq true

    page.search_term.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.search_term, @field_required)
    should_have_form_errors(page)

    page.search_term.set 'Text'
    page.search_term.trigger 'blur'
    page.wait_for_error_message_count(0)
    should_have_no_error_text(page.search_term)
    should_have_no_form_errors(page)

    page.replace_where.select 'Site Preferences (Choose from the following)'
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

    page.password_auth.set 'password'
    page.password_auth.trigger 'blur'
    page.wait_for_error_message_count(1)

    page.password_auth.set 'test'
    page.password_auth.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_no_error_text(page.search_term)
    should_have_error_text(page.replace_where, @field_required)
    should_have_error_text(page.password_auth, 'The password entered is incorrect.')
    should_have_form_errors(page)

    page.password_auth.set 'password'
    page.password_auth.trigger 'blur'
    page.wait_for_error_message_count(1)
    page.replace_where.select 'Channel Entry Titles'
    page.wait_for_error_message_count(0)

    should_have_no_error_text(page.search_term)
    should_have_no_error_text(page.replace_where)
    should_have_no_error_text(page.replace_term)
    should_have_no_error_text(page.password_auth)
    should_have_no_form_errors(page)

    cy.hasNoErrors()

    page.submit

    cy.hasNoErrors()

    page.should have_text 'Action was a success'
    page.should have_text 'Number of database records in which a replacement occurred: 0'
  }

  it('should fail validation without AJAX too', () => {
    page.submit

    page.should have_text 'Attention: Search and replace not run'
    should_have_error_text(page.search_term, @field_required)
    should_have_error_text(page.replace_where, @field_required)
    should_have_error_text(page.password_auth, @field_required)
    should_have_form_errors(page)

    cy.hasNoErrors()

    page.search_term.set 'Text'
    page.search_term.trigger 'blur'
    page.wait_for_error_message_count(2)
    page.replace_where.select 'Channel Entry Titles'
    page.wait_for_error_message_count(1)
    page.password_auth.set 'password'
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

    page.should have_text 'Action was a success'
    page.should have_text 'Number of database records in which a replacement occurred: 0'
  }

  it('should search and replace data', () => {

    page.search_term.set 'Welcome'
    page.replace_term.set 'test'
    page.replace_where.select 'Channel Entry Titles'
    page.password_auth.set 'password'

    page.submit

    cy.hasNoErrors()

    page.should have_text 'Action was a success'
    page.should have_text 'Number of database records in which a replacement occurred: 1'
  }

}
