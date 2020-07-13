/// <reference types="Cypress" />

import EditFile from '../../elements/pages/files/EditFile';
import FileManager from '../../elements/pages/files/FileManager';
const page = new EditFile;
const filemanager = new FileManager;

context('File Manager / Edit File', () => {

  before(function() {
    // Create backups of these folders so we can restore them after each test
    cy.task('filesystem:create', Cypress.env("TEMP_DIR")+'/about');
		cy.task('filesystem:copy', { from: '../../images/about/*', to: Cypress.env("TEMP_DIR")+'/about' })
	})

  after(function() {
    cy.task('filesystem:delete', Cypress.env("TEMP_DIR")+'/about')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()

    // Check that the heder data is intact
    page.get('page_title').invoke('text').then((text) => {
      expect(text.trim()).equal('Files')
    })
   
    

  })

  afterEach(function() {
    cy.task('filesystem:delete', '../../images/about/')
		cy.task('filesystem:create', Cypress.env("TEMP_DIR")+'/about');
		cy.task('filesystem:copy', { from: Cypress.env("TEMP_DIR")+'/about/*', to: '../../images/about' })
    //FileUtils.chmod_R 0777, @upload_dir
  })

  /*it('shows the Edit Meta Data form', () => {
		page.get('title_input').invoke('val').then((text) => {
      page.get('breadcrumb').contains(text)
      page.get('heading').contains(text)
		})
  })*/

  it('can edit the title', () => {
    cy.get('a').contains('Main Upload').first().click({force:true})
    cy.get('a').contains('.jpg').filter(':visible').first().click()

    page.get('title_input').clear().type("Rspec was here")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    filemanager.get('alert').contains("The meta data for the file Rspec was here has been updated.")
  })

  it('can edit the description', () => {
     cy.get('a').contains('Main Upload').first().click({force:true})
    cy.get('a').contains('.jpg').filter(':visible').first().click()

    page.get('description_input').clear().type("Rspec was here")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    filemanager.get('alert').contains("The meta data for the file")
    filemanager.get('alert').contains("has been updated.")
  })

  it('can edit the credit', () => {
     cy.get('a').contains('Main Upload').first().click({force:true})
    cy.get('a').contains('.jpg').filter(':visible').first().click()

    page.get('credit_input').type("Rspec was here")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    filemanager.get('alert').contains("The meta data for the file")
    filemanager.get('alert').contains("has been updated.")
  })

  it('can edit the location', () => {

     cy.get('a').contains('Main Upload').first().click({force:true})
    cy.get('a').contains('.jpg').filter(':visible').first().click()
    page.get('location_input').clear().type("Rspec was here")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    filemanager.get('alert').contains("The meta data for the file")
    filemanager.get('alert').contains("has been updated.")
  })

})
