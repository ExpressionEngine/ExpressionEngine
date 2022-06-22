/// <reference types="Cypress" />

import UploadFile from '../../elements/pages/files/UploadFile';
import FileManager from '../../elements/pages/files/FileManager';
import EditFile from '../../elements/pages/files/EditFile';
const page = new UploadFile;
const returnPage = new EditFile;
const filePage = new EditFile;
const { _, $ } = Cypress

const md_file = '../../support/file/README.md'
const script_file = '../../support/file/script.sh'
const image_file = '../../support/file/programming.gif'
const php_file = '../../support/file/clever.php.png'

const upload_dir = '../../images/uploads'



context('File Manager / Upload File', () => {

  before(function() {
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();

    page.load()
    cy.hasNoErrors()

    //page.displayed?

    // Check that the heder data is intact
    page.get('manager_title').invoke('text').then((text) => { expect(text.trim()).to.be.equal('Files') })
    page.get('download_all').should('exist')

    // Check that we have a sidebar
    page.get('sidebar').should('exist')
    page.get('upload_directories_header').contains('Upload Directories')
    page.get('new_directory_button').should('exist')
    page.get('watermarks_header').contains('Watermarks')
    //page.get('new_watermark_button').should('exist')
    page.get('sidebar').find('.sidebar__link.active a').first().invoke('text').then((text) => { expect(text.trim()).to.be.equal('Main Upload Directory') })

    page.get('breadcrumb').should('exist')

    page.get('file_input').should('exist')
  })

  // Restore the images/uploads directory
  afterEach(function() {
    cy.task('filesystem:delete', upload_dir+'/\*')
  })

  it('can upload a Markdown file', () => {
    page.get('file_input').find('.file-field__dropzone').attachFile(md_file, { subjectType: 'drag-n-drop' })
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    //returnPage.displayed?
    returnPage.get('alert').should('exist')
    returnPage.get('alert_success').should('exist')
    returnPage.get('alert').contains("File Upload Success")
    returnPage.get('alert').contains("The file README.md was uploaded successfully.")
    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("README.md")
  })

  it('can upload a Markdown file and set the title', () => {
    cy.get('input[name="file"]').attachFile(md_file)
    page.get('title_input').clear().type("RSpec README")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    //returnPage.displayed?
    returnPage.get('alert').should('exist')
    returnPage.get('alert_success').should('exist')
    returnPage.get('alert').contains("File Upload Success")
    returnPage.get('alert').contains("The file RSpec README was uploaded successfully.")
    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("README.md")
    returnPage.get('selected_file').contains("RSpec README")
  })

  it('can upload a Markdown file and set the description', () => {
    cy.get('input[name="file"]').attachFile(md_file)
    page.get('description_input').clear().type("RSpec README")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    //returnPage.displayed?
    returnPage.get('alert').should('exist')
    returnPage.get('alert_success').should('exist')
    returnPage.get('alert').contains("File Upload Success")
    returnPage.get('alert').contains("The file README.md was uploaded successfully.")
    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("README.md")

    returnPage.get('selected_file').find('a.edit').click()
    cy.hasNoErrors()

    //filePage.displayed?
    filePage.get('description_input').invoke('val').then((val) => { expect(val).to.be.equal("RSpec README") })
  })

  it('can upload a Markdown file and set the credit', () => {
    cy.get('input[name="file"]').attachFile(md_file)
    page.get('credit_input').clear().type("RSpec README")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    //returnPage.displayed?
    returnPage.get('alert').should('exist')
    returnPage.get('alert_success').should('exist')
    returnPage.get('alert').contains("File Upload Success")
    returnPage.get('alert').contains("The file README.md was uploaded successfully.")
    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("README.md")

    returnPage.get('selected_file').find('a.edit').click()
    cy.hasNoErrors()

    //filePage.displayed?
    filePage.get('credit_input').invoke('val').then((val) => { expect(val).to.be.equal("RSpec README") })
  })

  it('can upload a Markdown file and set the location', () => {
    cy.get('input[name="file"]').attachFile(md_file)
    page.get('location_input').clear().type("RSpec README")
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    //returnPage.displayed?
    returnPage.get('alert').should('exist')
    returnPage.get('alert_success').should('exist')
    returnPage.get('alert').contains("File Upload Success")
    returnPage.get('alert').contains("The file README.md was uploaded successfully.")
    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("README.md")

    returnPage.get('selected_file').find('a.edit').click()
    cy.hasNoErrors()

    //filePage.displayed?
    filePage.get('location_input').invoke('val').then((val) => { expect(val).to.be.equal("RSpec README") })
  })

  it('cannot upload a shell script', () => {
    cy.get('input[name="file"]').attachFile(script_file)
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    page.get('alert').should('be.visible')
    page.get('alert_error').should('be.visible')
    page.get('alert').contains("Cannot Upload File")
    page.get('alert').contains("File not allowed.")
  })

  it('can upload a image when the directory is restricted to images', () => {
    cy.get('.button--primary').contains('Upload').click()
    cy.get('.dropdown--open .dropdown__link').contains('About').click()

    cy.get('input[name="file"]').attachFile(image_file)
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    //returnPage.displayed?
    returnPage.get('alert').should('exist')
    returnPage.get('alert_success').should('exist')
    returnPage.get('alert').contains("File Upload Success")
    returnPage.get('alert').contains("The file programming.gif was uploaded successfully.")
    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("programming.gif")

    // Cleaning up after myself
    cy.task('filesystem:delete', '../../images/about/programming.gif')
    cy.task('filesystem:delete', '../../images/about/_thumbs/programming.gif')
  })

  it('cannot upload a non-image when the directory is restricted to images', () => {
    cy.get('.button--primary').contains('Upload').click()
    cy.get('.dropdown--open .dropdown__link').contains('About').click()

    cy.get('input[name="file"]').attachFile(md_file)
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    page.get('alert').should('be.visible')
    page.get('alert_error').should('be.visible')
    page.get('alert').contains("Cannot Upload File")
    page.get('alert').contains("File not allowed.")
  })

  it('cannot upload a PHP script masquerading as an image', () => {
    cy.get('.button--primary').contains('Upload').click()
    cy.get('.dropdown--open .dropdown__link').contains('About').click()

    cy.get('input[name="file"]').attachFile(php_file)
    page.get('form_submit_button').click()
    cy.hasNoErrors()

    page.get('alert').should('be.visible')
    page.get('alert_error').should('be.visible')
    page.get('alert').contains("Cannot Upload File")
    page.get('alert').contains("File not allowed.")
  })

  it('shows an error if the directory upload path has no write permissions', () => {
    if (Cypress.platform === 'win32')
    {
        cy.log('skipped because of Windows platform')
    }
    else
    {
      cy.exec(`chmod 555 ` + upload_dir)
      page.load()
      cy.hasNoErrors()

      page.get('alert').should('be.visible')
      page.get('alert_error').should('be.visible')
      page.get('alert').contains("Directory Not Writable")
      page.get('alert').contains("Cannot write to the directory")
      page.get('alert').contains("Check your file permissions on the server")
      cy.exec(`chmod 777 ` + upload_dir)
    }
  })

  it('shows an error if the directory upload path does not exist', () => {
    cy.task('filesystem:rename', {from: upload_dir, to: upload_dir + '.rspec'}).then(() => {
      page.load()
      cy.hasNoErrors()

      cy.contains("404")

      // page.get('alert').should('be.visible')
      // page.get('alert_error').should('be.visible')
      // page.get('alert').contains("Cannot find the directory"
      cy.task('filesystem:rename', {from: upload_dir + '.rspec', to: upload_dir})
    })
  })

})
