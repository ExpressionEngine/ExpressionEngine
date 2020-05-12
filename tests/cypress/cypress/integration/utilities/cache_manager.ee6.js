/// <reference types="Cypress" />

import SpamTrap from '../../elements/pages/utilities/CacheManager';

const page = new SpamTrap

context('Cache Manager', () => {

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the Cache Manager page', () => {
    page.get('wrap').contains('Cache Manager')
    page.get('wrap').contains('Caches to clear')
    page.get('wrap').find('input[name=cache_type][value=all]').should('be.checked')
    page.get('wrap').should('not.contain', 'An error occurred')
  })

  it('should successfully submit with one cache type selected', () => {
    page.get('wrap').find('div.form-btns input.btn[type="submit"]').click()
    cy.hasNoErrors()

    page.get('wrap').contains('Caches cleared')
  })
})
