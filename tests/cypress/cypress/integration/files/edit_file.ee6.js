/// <reference types="Cypress" />

import EditFile from '../../elements/pages/files/EditFile';
import FileManager from '../../elements/pages/files/FileManager';
const { _, $ } = Cypress
const page = new EditFile;
const filemanager = new FileManager;

var url = '';

context('File Manager / Edit File', () => {

  before(function() {
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.hasNoErrors()

    // // Check that the heder data is intact
    // page.get('page_title').invoke('text').then((text) => {
    //   expect(text.trim()).equal('Files')
    // })
   

  })

  context('editing image', function() {

    before(function() {
      cy.auth();
      page.load()
      cy.get('a').contains('testband300.jpg').filter(':visible').first().click()
      cy.url().then(edit_url => {
        url = edit_url;
      })

    })

    beforeEach(function() {
      cy.authVisit(url);
      cy.hasNoErrors()
    })

    it('has the correct meta info', () => {
      cy.get('.f_meta-info-dimentions').should('not.be.empty')
      cy.get('.f_meta-info-file_type').should('contain', 'Image');
    })

    it('can edit the title', () => {

      page.get('title_input').clear().type("Rspec was here")
      page.get('form_submit_button').click()
      cy.hasNoErrors()

      filemanager.get('alert').contains("The data for the file Rspec was here has been updated.")
    })

    it('can edit the description', () => {

      page.get('description_input').clear().type("Rspec was here")
      page.get('form_submit_button').click()
      cy.hasNoErrors()

      filemanager.get('alert').contains("The data for the file")
      filemanager.get('alert').contains("has been updated.")
    })

    it('can edit the credit', () => {

      page.get('credit_input').type("Rspec was here")
      page.get('form_submit_button').click()
      cy.hasNoErrors()

      filemanager.get('alert').contains("The data for the file")
      filemanager.get('alert').contains("has been updated.")
    })

    it('can edit the location', () => {

      page.get('location_input').clear().type("Rspec was here")
      page.get('form_submit_button').click()
      cy.hasNoErrors()

      filemanager.get('alert').contains("The data for the file")
      filemanager.get('alert').contains("has been updated.")
    })
  })

  context.only('editing non-image', function() {

    before(function() {
      cy.auth();
      page.load()
      
      cy.get('button').contains('Upload').first().click()
      
      const fileName = '../../../../CONTRIBUTING.md'
      cy.get('.file-upload-widget').then(function(widget) {
        $(widget).removeClass('hidden')
      })
      cy.get('.file-upload-widget .js-dropdown-toggle').click();
      cy.get('.file-upload-widget .dropdown__link').contains('Main Upload Directory').filter(':visible').first().click()
      cy.get('.file-upload-widget .file-field__dropzone').attachFile(fileName, { subjectType: 'drag-n-drop' })
      
      cy.hasNoErrors()

    })

    beforeEach(function() {
      cy.auth();
      page.load()
      cy.get('a').contains('CONTRIBUTING.md').filter(':visible').first().click()
      cy.hasNoErrors()
    })

    it('has the correct meta info', () => {
      cy.get('.f_meta-info-dimentions').should('not.exist')
      cy.get('.f_meta-info-file_type').should('contain', 'Document');
    })

    it('can edit the title', () => {

      page.get('title_input').clear().type("Rspec was here")
      page.get('form_submit_button').click()
      cy.hasNoErrors()

      filemanager.get('alert').contains("The data for the file Rspec was here has been updated.")
    })

    after(function() {
      cy.task('filesystem:delete', '../../images/uploads/*')
    })
  
  })

})
