/// <reference types="Cypress" />

import MemberFields from '../../elements/pages/members/MemberFields';

const page = new MemberFields

context('Member Field List', () => {

  beforeEach(function() {
    cy.auth();

    page.load()
    cy.hasNoErrors()
  })

  it('shows the Member Field List page', () => {
    //page.all_there?.should('eq', true
  })
})
