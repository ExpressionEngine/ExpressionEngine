/// <reference types="Cypress" />

import Edit from '../../elements/pages/publish/Edit';

const page = new Edit;


context('Publish Page - Edit', () => {
  beforeEach(function(){
    cy.auth();
    cy.hasNoErrors()
  })

  it('shows a 404 with no given entry_id', () => {
    page.load()
    cy.contains("404")
  })
})
