/// <reference types="Cypress" />

import SiteForm from '../../elements/pages/site/SiteForm';
import UploadEdit from '../../elements/pages/files/UploadEdit';
import UploadFile from '../../elements/pages/files/UploadFile';
import FileManager from '../../elements/pages/files/FileManager';
const managerPage = new FileManager;
const uploadEdit = new UploadEdit;
const uploadFile = new UploadFile;
const upload_path = "{base_path}/uploads/"
const { _, $ } = Cypress

context('Upload Destinations on MSM sites', () => {

  before(function(){
    cy.task('db:seed')

    cy.auth();
    // use Jump menu to enable MSM
    cy.get('.jump-to:visible').focus().type('msm')
    cy.get('#jump-menu').should('be.visible')
    cy.get('.jump-menu__items a.jump-menu__link').should('have.length', 1).should('contain', 'Enable Site Manager').click()
    cy.get('[data-toggle-for="multiple_sites_enabled"]').click()
    cy.get('body').type('{ctrl}', {release: false}).type('s')
    cy.eeConfig({item: 'multiple_sites_enabled'}) .then((config) => {
        expect(config.trim()).to.be.equal('y')
    })

    // create a new site
    cy.visit('admin.php?/cp/msm');
    cy.dismissLicenseAlert()
    cy.get('.main-nav a').contains('Add Site').first().click()
    const form = new SiteForm
        form.add_site({
        name: 'Site 2',
        short_name: 'site_2',
    })
    uploadEdit.get('alert').should('exist')
    uploadEdit.get('alert').contains('Site Created')

  })

  beforeEach(function() {
    cy.auth();
    cy.visit('admin.php?cp/msm/switch_to/2')
  })

  it('Create upload directory', () => {
    uploadEdit.load()
    cy.hasNoErrors()
    uploadEdit.get('url').should('not.be.visible')
    cy.get('[data-input-value=adapter] .select__button').click()
    cy.get('[data-input-value=adapter] .select__dropdown .select__dropdown-item').contains('Local').click()
    uploadEdit.get('name').clear().type('Dir 2')
    uploadEdit.get('url').clear().type(Cypress.config().baseUrl + '/uploads/')
    uploadEdit.get('server_path').clear().type(upload_path, {parseSpecialCharSequences: false})
    cy.get('[data-toggle-for="allow_subfolders"]').click()

    uploadEdit.submit()
    cy.hasNoErrors()

    uploadEdit.get('wrap').contains('Upload directory saved')

    uploadEdit.get('name').invoke('val').then((text) => {
      expect(text).equal('Dir 2')
    })
    uploadEdit.get('server_path').invoke('val').then((text) => {
      expect(text).equal(upload_path)
    })
  })

  it('Upload an image', () => {
	// Cleaning up before we upload
    cy.task('filesystem:delete', '../../uploads/programming.gif')
    cy.task('filesystem:delete', '../../uploads/_thumbs/programming.gif')
	
    cy.authVisit('admin.php?/cp/files')
    cy.get('.sidebar').contains('Dir 2').click()

    cy.get('.file-upload-widget').then(function(widget) {
      $(widget).removeClass('hidden')
    })
    cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
    cy.intercept('/admin.php?/cp/files/directory/*').as('table')
    uploadFile.get('file_input').find('.file-field__dropzone').selectFile('support/file/programming.gif', { action: 'drag-drop' })
    cy.wait('@upload')
    cy.wait('@table')
    cy.hasNoErrors()

    uploadFile.get('selected_file').should('exist')
    uploadFile.get('selected_file').contains("programming.gif")

    // Cleaning up after myself
    cy.task('filesystem:delete', '../../uploads/programming.gif')
    cy.task('filesystem:delete', '../../uploads/_thumbs/programming.gif')
  })

  it('Can create a new folder', () => {
    cy.authVisit('admin.php?/cp/files')
    cy.get('.sidebar').contains('Dir 2').click()
    managerPage.get('new_folder_button').click()

    cy.get('.modal-new-folder input[name="folder_name"]').type('sub1')
    cy.get('.modal-new-folder button[type="submit"]').click()

    cy.hasNoErrors()

    managerPage.hasAlert('success').contains('Folder created')
    cy.get('.app-listing__row').should('contain', 'sub1')

    cy.task('filesystem:delete', '../../uploads/sub1')
  })



})
