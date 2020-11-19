/// <reference types="Cypress" />

import MemberCreate from '../../elements/pages/members/MemberCreate';

const page = new MemberCreate

context('Member Registration', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  /*it('loads', () => {
    page.all_there?.should('eq', true
  }*/

  it('prevents duplicate gmail email addresses', () => {
    page.get('username').clear().type('test')
    page.get('email').clear().type('test@gmail.com')
    page.get('password').clear().type('password')
    page.get('confirm_password').clear().type('password')

    //page.get('save_and_new_button').click() AJ
    cy.get('.form-btns-top .saving-options').click()
    cy.get('button').contains('Save & New').click()

    cy.hasNoErrors()
    // Save and New is the only action
    //page.all_there?.should('eq', true

    page.get('email').clear().type('t.e.s.t@gmail.com')
    page.get('email').blur()
    page.hasError(page.get('email'), 'This field must contain a unique email address.')

  })
})
