/// <reference types="Cypress" />

import PersonalSettings from '../../elements/pages/members/profile/PersonalSettings';

const page = new PersonalSettings

context('Profile - Personal Settings', () => {
  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('should load', () => {
    //page.all_there?.should('eq', true
  })
})
