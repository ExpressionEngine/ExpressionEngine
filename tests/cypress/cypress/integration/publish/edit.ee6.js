/// <reference types="Cypress" />

import Edit from '../../elements/pages/publish/Edit';

const page = new Edit;


context('Publish Page - Edit', () => {
  beforeEach(function(){
    //cy.task('db:seed')
    cy.auth();
    cy.hasNoErrors()
  })

  it('shows a 404 with no given entry_id', () => {
    page.load()
    cy.contains("404")
  })

  context('autosaving', () => {
    it('autosaves the changes', () => {
      cy.visit('admin.php?/cp/publish/edit/entry/1')
      cy.get('input[name=title]').clear().type('Auto Saved Title');
      cy.wait(65000);// 60 sec before the ajax + 5 sec to finish
      cy.get('.main-nav__title h1 span').contains('Auto Saved');

      cy.visit('admin.php?/cp/publish/edit')
      cy.get('tbody tr:last-child').should('have.class', 'auto-saved')
      cy.get('tbody tr:last-child span.auto-save').should('exist')

      cy.get('tbody tr:last-child a').first().click();
      cy.get('input[name=title]').invoke('val').then((val) => { expect(val).to.be.equal("Getting to Know ExpressionEngine") })

      cy.get('[rel=t-autosaves]').click();
      cy.get('a[title=View]').click()
      cy.get('input[name=title]').invoke('val').then((val) => { expect(val).to.be.equal("Auto Saved Title") })

      cy.get('[rel=t-autosaves]').click();
      cy.get('a').contains('Current').click()
      cy.get('input[name=title]').invoke('val').then((val) => { expect(val).to.be.equal("Getting to Know ExpressionEngine") })

      page.get('save').click()
      cy.visit('admin.php?/cp/publish/edit/entry/1')
      cy.get('[rel=t-autosaves]').should('not.exist');

      cy.visit('admin.php?/cp/publish/edit')
      cy.get('tbody tr:last-child').should('not.have.class', 'auto-saved')
      cy.get('tbody tr:last-child span.auto-save').should('not.exist')

      
    })

    it('prevent navigating away', () => {
      cy.visit('admin.php?/cp/publish/edit/entry/1')
      var alerted = false;
      cy.get('input[name=title]').clear().type('Auto Saved Title');
      cy.on('window:confirm', (str) => {
        expect(str).to.equal('When you leave, any data entered will be lost. Are you sure you want to leave?')
        alerted = true;
      })
      cy.get('a').contains('Overview').click().then(() => {
        expect(alerted).to.eq(true)
      })
      

      
    })
  })
})
