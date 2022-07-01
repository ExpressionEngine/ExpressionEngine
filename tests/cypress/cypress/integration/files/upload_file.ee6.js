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
    cy.task('filesystem:delete', upload_dir+'/\*')
    cy.task('filesystem:delete', '../../system/user/config/mimes.php')
  })

  beforeEach(function() {
    cy.auth();

    cy.visit('admin.php?/cp/files/directory/1')
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
  after(function() {
    cy.task('filesystem:delete', upload_dir+'/\*')
    cy.task('filesystem:delete', '../../system/user/config/mimes.php')
  })

  function dragAndDropUpload(file) {
    cy.get('.file-upload-widget').then(function(widget) {
      $(widget).removeClass('hidden')
    })
    cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
    cy.intercept('/admin.php?/cp/files/directory/*').as('table')
    page.get('file_input').find('.file-field__dropzone').attachFile(file, { subjectType: 'drag-n-drop' })
    cy.wait('@upload')
  }

  it('can upload a Markdown file', () => {
    dragAndDropUpload(md_file)
    cy.get('.file-upload-widget').should('not.be.visible')
    cy.wait('@table')
    cy.hasNoErrors()

    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("README.md")
    returnPage.get('selected_file').should('contain', 'Document')
  })

  it('asked to resolve when uploading file with the same name', () => {
    dragAndDropUpload(md_file)

    cy.get('.file-upload-widget').should('be.visible')
    cy.get('.file-upload-widget').should('contain', 'File already exists')
    cy.get('.file-upload-widget [rel="modal-file"]').should('contain', 'Resolve Conflict')
    cy.hasNoErrors()

    returnPage.get('selected_file').should('not.exist')
  })

  it('cannot upload a file when mime type is not registered', () => {
    dragAndDropUpload('../../support/file/ubuntu-22.04-live-server-amd64-iso.torrent')

    cy.get('.file-upload-widget').should('be.visible')
    cy.get('.file-upload-widget').should('contain', 'File not allowed')
    cy.get('.file-upload-widget a').should('contain', 'Dismiss')
    cy.hasNoErrors()

    returnPage.get('selected_file').should('not.exist')

    //reload the page, make sure the file is not listed
    cy.visit('admin.php?/cp/files/directory/1')
    cy.get('.ee-main__content form .table-responsive table').should('not.contain', 'ubuntu-22');

  })

  it('uploads the file correctly after error', () => {
    dragAndDropUpload('../../support/file/ubuntu-22.04-live-server-amd64-iso.torrent')

    cy.get('.file-upload-widget').should('be.visible')
    cy.get('.file-upload-widget').should('contain', 'File not allowed')
    cy.get('.file-upload-widget a').should('contain', 'Dismiss')
    cy.get('.file-upload-widget a').contains('Dismiss').click()
    cy.hasNoErrors()

    dragAndDropUpload('../../../../LICENSE.txt')
    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("LICENSE.txt")
    returnPage.get('selected_file').should('contain', 'Document')

    cy.get('.ee-main__content form .table-responsive table tr:contains(LICENSE.txt)').its('length').should('eq', 1)

  })

  it('can upload a file when mime type is whitelisted in config', () => {
    cy.task('filesystem:copy', { from: 'support/config/mimes.php', to: '../../system/user/config/' })
    dragAndDropUpload('../../support/file/ubuntu-22.04-live-server-amd64-iso.torrent')
    cy.get('.file-upload-widget').should('not.be.visible')
    cy.wait('@table')
    cy.hasNoErrors()

    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("ubuntu-22.04-live-server-amd64-iso_.torrent")
    returnPage.get('selected_file').should('contain', 'Other')
  })

  it('can upload a SQL file and get some response', () => {
    cy.get('.file-upload-widget').then(function(widget) {
      $(widget).removeClass('hidden')
    })
    cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
    cy.intercept('/admin.php?/cp/files/directory/*').as('table')
    page.get('file_input').find('.file-field__dropzone').attachFile('../../support/sql/database_6.0.0.sql', { subjectType: 'drag-n-drop' })
    cy.wait('@upload')
    cy.wait('@table')
    cy.hasNoErrors()

    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("database_6.0.0.sql")
  })

  it('cannot upload a shell script', () => {
    cy.get('.file-upload-widget').then(function(widget) {
      $(widget).removeClass('hidden')
    })
    cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
    page.get('file_input').find('.file-field__dropzone').attachFile(script_file, { subjectType: 'drag-n-drop' })
    cy.wait('@upload')
    cy.hasNoErrors()

    returnPage.get('selected_file').should('not.exist')
    page.get('file_input').contains("File not allowed.")
  })

  it('can upload a image when the directory is restricted to images', () => {
    cy.get('.sidebar').contains('About').click()

    cy.get('.file-upload-widget').then(function(widget) {
      $(widget).removeClass('hidden')
    })
    cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
    cy.intercept('/admin.php?/cp/files/directory/*').as('table')
    page.get('file_input').find('.file-field__dropzone').attachFile(image_file, { subjectType: 'drag-n-drop' })
    cy.wait('@upload')
    cy.wait('@table')
    cy.hasNoErrors()

    returnPage.get('selected_file').should('exist')
    returnPage.get('selected_file').contains("programming.gif")

    // Cleaning up after myself
    cy.task('filesystem:delete', '../../images/about/programming.gif')
    cy.task('filesystem:delete', '../../images/about/_thumbs/programming.gif')
  })

  it('cannot upload a non-image when the directory is restricted to images', () => {
    cy.get('.sidebar').contains('About').click()

    dragAndDropUpload(md_file)
    cy.wait('@upload')
    cy.hasNoErrors()

    returnPage.get('selected_file').should('not.exist')
    page.get('file_input').contains("File not allowed.")
  })

  it('file uploaded only once in case of previous error', () => {
    cy.get('.sidebar').contains('About').click()

    dragAndDropUpload(md_file)

    page.get('file_input').find('.file-field__dropzone').invoke('show')
    dragAndDropUpload('../../../../themes/ee/asset/fonts/fontawesome-webfont.eot')

    cy.get('.file-upload-widget:contains(fontawesome-webfont.eot)').its('length').should('eq', 1)
  })

  it('cannot upload a PHP script masquerading as an image', () => {
    cy.get('.sidebar').contains('About').click()

    cy.get('.file-upload-widget').then(function(widget) {
      $(widget).removeClass('hidden')
    })
    cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
    page.get('file_input').find('.file-field__dropzone').attachFile(php_file, { subjectType: 'drag-n-drop' })
    cy.wait('@upload')
    cy.hasNoErrors()

    returnPage.get('selected_file').should('not.exist')
    page.get('file_input').contains("File not allowed.")
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

      cy.contains("Cannot find the directory")

      // page.get('alert').should('be.visible')
      // page.get('alert_error').should('be.visible')
      // page.get('alert').contains("Cannot find the directory"
      cy.task('filesystem:rename', {from: upload_dir + '.rspec', to: upload_dir})
    })
  })

})
