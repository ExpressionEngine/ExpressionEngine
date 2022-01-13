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
    cy.eeConfig({ item: 'password_security_policy', value: 'none' })
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

  it('cannot create with password that is too weak', () => {
    cy.eeConfig({ item: 'password_security_policy', value: 'strong' })
    page.get('username').clear().type('test_pass')
    page.get('email').clear().type('test_pass@gmail.com')
    page.get('password').clear().type('password').blur()
    page.get('confirm_password').clear().type('password')

    cy.get('[name=password]').parents('fieldset').should('have.class', 'fieldset-invalid').should('contain', 'The chosen password is not secure enough.')
    cy.get('[name=password]').parents('fieldset').find('.status-tag').should('contain', 'weak')
    cy.get('.title-bar__extra-tools .button--primary').first().should('have.attr', 'disabled')

    page.get('password').clear().type('1Password').blur()
    page.get('confirm_password').clear().type('1Password')

    cy.get('[name=password]').parents('fieldset').should('have.class', 'fieldset-invalid').should('contain', 'The chosen password is not secure enough.')
    cy.get('[name=password]').parents('fieldset').find('.status-tag').should('contain', 'good')
    cy.get('.title-bar__extra-tools .button--primary').first().should('have.attr', 'disabled')

    page.get('password').clear().type('p@Ssw0rd1er6_kk.').blur()
    page.get('confirm_password').clear().type('p@Ssw0rd1er6_kk.')

    cy.get('[name=password]').parents('fieldset').should('not.have.class', 'fieldset-invalid').should('not.contain', 'The chosen password is not secure enough.')
    cy.get('[name=password]').parents('fieldset').find('.status-tag').should('contain', 'very strong')
    cy.get('.title-bar__extra-tools .button--primary').first().should('not.have.attr', 'disabled')

  })

})
