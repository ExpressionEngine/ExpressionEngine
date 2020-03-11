require './bootstrap.rb'

feature 'Profile - Username and Password', () => {
  beforeEach(function() {
    cy.auth();
    page = Profile::UsernamePassword.new
    page.load()
    cy.hasNoErrors()
  }

  it('should load', () => {
    page.all_there?.should == true
  }

  it('should submit with no changes', () => {
    page.current_password.set 'password'
    page.profile_form.submit

    cy.hasNoErrors()
    page.all_there?.should == true
  }

  it('should submit with a password change', () => {
    page.password.set 'password'
    page.confirm_password.set 'password'
    page.current_password.set 'password'
    page.profile_form.submit

    cy.hasNoErrors()
    page.all_there?.should == true
  }

  it('should not submit with a password that is too long', () => {
    // Password is 80 characters long, 72 is the max
    page.execute_script("$('input[maxlength=72]').prop('maxlength', 80);")
    page.password.set '12345678901234567890123456789012345678901234567890123456789012345678901234567890'
    page.confirm_password.set '12345678901234567890123456789012345678901234567890123456789012345678901234567890'
    page.current_password.set 'password'
    page.profile_form.submit

    cy.hasNoErrors()
    page.should have_text 'Your password cannot be over 72 characters in length'
  }
}
