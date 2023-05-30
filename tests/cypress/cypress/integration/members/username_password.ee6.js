/// <reference types="Cypress" />

import UsernamePassword from '../../elements/pages/members/profile/UsernamePassword';

const page = new UsernamePassword

context('Profile - Username and Password', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.eeConfig({ item: 'password_security_policy', value: 'none' })
    cy.auth();

    page.load()
    cy.hasNoErrors()
  })

  /*it('should load', () => {
    page.all_there?.should('eq', true
  }*/

  it('should submit with no changes', () => {
    page.get('current_password').clear().type('password')
    page.get('profile_form').submit()

    cy.hasNoErrors()
    //page.all_there?.should('eq', true
  })

  it('should submit with a password change', () => {
    page.get('password').clear().type('password')
    page.get('confirm_password').clear().type('password')
    page.get('current_password').clear().type('password')
    page.get('profile_form').submit()

    cy.hasNoErrors()
    //page.all_there?.should('eq', true
  })

  it('should not submit with a password that is too long', () => {
    // Password is 80 characters long, 72 is the max
    cy.window().then((win) => {
      win.$('input[maxlength=72]').prop('maxlength', 80);
    })
    page.get('password').clear().type('12345678901234567890123456789012345678901234567890123456789012345678901234567890')
    page.get('confirm_password').clear().type('12345678901234567890123456789012345678901234567890123456789012345678901234567890')
    page.get('current_password').clear().type('password')
    page.get('profile_form').submit()

    cy.hasNoErrors()
    page.get('wrap').contains('Your password cannot be over 72 characters in length')
  })
})
