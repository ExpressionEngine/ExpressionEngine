/// <reference types="Cypress" />

context('Math add-on', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
    cy.eeConfig({ item: 'enable_dock', value: 'n' })
    cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' })
    cy.authVisit('admin.php?/cp/design')

    cy.visit('admin.php?/cp/publish/edit/entry/3');
    cy.get('input[name="field_id_6"]').clear().type('3');
    cy.get('body').type('{ctrl}', {release: false}).type('s')
  })


  it('Check template tags', function(){

      cy.visit('index.php/math');

      cy.hasNoErrors()

      cy.get('#layout-math').invoke('text').should('eq', "10.6")
      cy.get('#variable-math').invoke('text').should('eq', "6.00")
      cy.get('#variable-math-invalid').invoke('text').should('eq', "3.00")
      cy.get('#tag-math').invoke('text').should('eq', "1060000")
      cy.get('#tag-pair-math').invoke('text').should('eq', "-11.000")

      cy.logFrontendPerformance()
    })

    after(function() {
        cy.eeConfig({ item: 'enable_dock', value: 'y' })
    })
  })