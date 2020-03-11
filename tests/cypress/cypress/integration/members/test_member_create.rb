require './bootstrap.rb'

feature 'Member Registration', () => {

  beforeEach(function() {
    cy.auth();
    page = Member::Create.new
    page.load()
    cy.hasNoErrors()
  }

  it('loads', () => {
    page.all_there?.should == true
  }

  it('prevents duplicate gmail email addresses', () => {
    page.username.set 'test'
    page.email.set 'test@gmail.com'
    page.password.set 'password'
    page.confirm_password.set 'password'
    page.save_and_new_button.click()

    cy.hasNoErrors()
    // Save and New is the only action
    page.all_there?.should == true

    page.email.set 't.e.s.t@gmail.com'
    page.email.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_form_errors(page)
    should_have_error_text(
      page.email,
      'This field must contain a unique email address.'
    )
  }
}
