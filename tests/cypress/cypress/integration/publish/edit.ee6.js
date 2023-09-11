/// <reference types="Cypress" />

import Edit from '../../elements/pages/publish/Edit';

const page = new Edit;


context('Publish Page - Edit', () => {
  before(function(){
    cy.task('db:seed')
    cy.eeConfig({ item: 'show_profiler', value: 'y' })
  })

  beforeEach(function(){
    cy.auth();
    cy.hasNoErrors()
  })

  after(function(){
    cy.eeConfig({ item: 'show_profiler', value: 'n' })
  })

  it('shows a 404 with no given entry_id', () => {
    page.load()
    cy.contains("404")
  })

  context('Autosaving', () => {
    it('Autosaves the changes', () => {
      cy.visit('admin.php?/cp/publish/edit/entry/1')
      cy.get('input[name=title]').clear().type('Auto Saved Title');
      cy.wait(65000);// 60 sec before the ajax + 5 sec to finish
      cy.get('.panel-heading .title-bar h3 span').contains('Auto Saved');

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

      cy.logCPPerformance()
    })

    it('Prevent navigating away with unsaved changes', () => {
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

  it('Save relationship field', () => {
    cy.visit('admin.php?/cp/publish/edit/entry/1')
    cy.get('button:contains("Relate Entry")').first().click()
    cy.get('a.dropdown__link:contains("Welcome to the Example Site!")').first().click();
    cy.get('a.dropdown__link:contains("Band Title")').first().click();
    cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
    cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
    cy.get('body').type('{ctrl}', {release: false}).type('s')
    cy.get('.app-notice---success').contains('Entry Updated');
    cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
    cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
    cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
    //cy.get('button:contains("Relate Entry")').should('not.be.visible')
    cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
    cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')

    cy.logCPPerformance()
  })

  
})
