require './bootstrap.rb'

context('Member Registration', () => {

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
    page.username.clear().type('test'
    page.email.clear().type('test@gmail.com'
    page.password.clear().type('password'
    page.confirm_password.clear().type('password'
    page.save_and_new_button.click()

    cy.hasNoErrors()
    // Save and New is the only action
    page.all_there?.should == true

    page.email.clear().type('t.e.s.t@gmail.com'
    page.email.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_form_errors(page)
    should_have_error_text(
      page.email,
      'This field must contain a unique email address.'
    )
  }
}
