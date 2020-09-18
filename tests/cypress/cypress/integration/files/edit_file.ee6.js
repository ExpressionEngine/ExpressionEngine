/// <reference types="Cypress" />

import EditFile from '../../elements/pages/files/EditFile';
import FileManager from '../../elements/pages/files/FileManager';
const page = new EditFile;
const filemanager = new FileManager;

context('File Manager / Edit File', () => {


  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()

    // // Check that the heder data is intact
    // page.get('page_title').invoke('text').then((text) => {
    //   expect(text.trim()).equal('Files')
    // })
   

  })

  it('can add a picture', () => {
    cy.get('button').contains('Upload').first().click()
    cy.get('a[class="dropdown__link"]').contains('About').filter(':visible').first().click()
    const fileName = 'pictureUpload.png'
    page.submit(fileName, 'image/png', 'input[name="file"]') 
    cy.get('input[value="Upload File"]').filter(':visible').first().click()
    cy.hasNoErrors()

  })

  it('can edit the title', () => {
    
    cy.get('a').contains('.jpg').filter(':visible').first().click()

    page.get('title_input').clear().type("Rspec was here")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    filemanager.get('alert').contains("The meta data for the file Rspec was here has been updated.")
  })

  it('can edit the description', () => {

    cy.get('a').contains('.jpg').filter(':visible').first().click()

    page.get('description_input').clear().type("Rspec was here")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    filemanager.get('alert').contains("The meta data for the file")
    filemanager.get('alert').contains("has been updated.")
  })

  it('can edit the credit', () => {
    
    cy.get('a').contains('.jpg').filter(':visible').first().click()

    page.get('credit_input').type("Rspec was here")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    filemanager.get('alert').contains("The meta data for the file")
    filemanager.get('alert').contains("has been updated.")
  })

  it('can edit the location', () => {

     
    cy.get('a').contains('.jpg').filter(':visible').first().click()
    page.get('location_input').clear().type("Rspec was here")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    filemanager.get('alert').contains("The meta data for the file")
    filemanager.get('alert').contains("has been updated.")
  })

})
