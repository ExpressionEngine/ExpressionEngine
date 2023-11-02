/// <reference types="Cypress" />

import SiteForm from '../../elements/pages/site/SiteForm';
import SiteManager from '../../elements/pages/site/SiteManager';
import UploadEdit from '../../elements/pages/files/UploadEdit';
import FileManager from '../../elements/pages/files/FileManager';
import UploadSync from '../../elements/pages/files/UploadSync';
import EditFile from '../../elements/pages/files/EditFile';
const { _, $ } = Cypress


const editFile = new EditFile;

const managerPage = new FileManager;
const uploadEdit = new UploadEdit;
const siteManager = new SiteManager;


context('Shared Upload Directories', () => {
    before(function () {
        cy.task('db:seed')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.eeConfig({ item: 'multiple_sites_enabled', value: 'y' })
        cy.eeConfig({ item: 'enable_dock', value: 'n' })

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })

        cy.task('filesystem:delete', '../../uploads/programming.gif')
        cy.task('filesystem:delete', '../../uploads/_thumbs/programming.gif')

        siteManager.load();
        cy.dismissLicenseAlert()

        cy.get('.main-nav a').contains('Add Site').first().click()

        const form = new SiteForm
        form.add_site({
            name: 'Second Site',
            short_name: 'second_site'
        })

        siteManager.get('global_menu').click()
        siteManager.get('dropdown').find('a[href*="cp/msm/switch_to/2"]').click()
        cy.authVisit('admin.php?/cp/design')

        cy.hasNoErrors()

        siteManager.get('global_menu').click()
        siteManager.get('dropdown').find('a[href*="cp/msm/switch_to/1"]').click()
    })

    after(function() {
      cy.eeConfig({ item: 'enable_dock', value: 'y' })
    })

    it('should save shared upload directory on site 2', () => {
        siteManager.load();
        siteManager.get('global_menu').click()
        siteManager.get('dropdown').find('a[href*="cp/msm/switch_to/2"]').click()

        uploadEdit.load()
        uploadEdit.get('url').should('not.be.visible')
        cy.get('[data-input-value=adapter] .select__button').click()
        cy.get('[data-input-value=adapter] .select__dropdown .select__dropdown-item').contains('Local').click()
        uploadEdit.get('name').clear().type('Shared Directory')
        uploadEdit.get('url').clear().type(Cypress.config().baseUrl + '/uploads/')
        uploadEdit.get('server_path').clear().type('{base_path}/uploads/', {parseSpecialCharSequences: false})

        uploadEdit.get('grid_add_no_results').click()
        uploadEdit.name_for_row(1).clear().type('cropped')
        uploadEdit.resize_type_for_row(1).select('Crop (part of image)')
        uploadEdit.width_for_row(1).clear().type('100')

        cy.get('#fieldset-share_directory .toggle-btn').click()

        uploadEdit.submit()
        cy.hasNoErrors()

        siteManager.load();
        siteManager.get('global_menu').click()
        siteManager.get('dropdown').find('a[href*="cp/msm/switch_to/1"]').click()

        managerPage.load()
        cy.get('.secondary-sidebar__files').contains('Shared Directory')
    })

    it('upload files on site 1 and check they are visible on site 2', () => {
        cy.auth();
        managerPage.load()
        cy.get('.secondary-sidebar__files .sidebar__link a').contains('Shared Directory').click()
        cy.wait(1000)

        cy.get('.file-upload-widget').then(function(widget) {
            $(widget).removeClass('hidden')
        })
        cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
        cy.intercept('/admin.php?/cp/files/directory/*').as('table')
        managerPage.get('file_input').find('.file-field__dropzone').selectFile('support/file/programming.gif', { action: 'drag-drop' })
        cy.wait('@upload')
        cy.wait('@table')
        cy.hasNoErrors()
    
        editFile.get('selected_file').should('exist')
        editFile.get('selected_file').contains("programming.gif")

        siteManager.load();
        siteManager.get('global_menu').click()
        siteManager.get('dropdown').find('a').contains('Second Site').click()

        managerPage.load()
        cy.get('.app-listing__row').contains("programming.gif")
        cy.get('.secondary-sidebar__files .sidebar__link a').contains('Shared Directory').click()
        cy.get('.app-listing__row').contains("programming.gif")
    })

    it('add files and display them on frontend', () => {
      cy.intercept("**/filepicker/**").as('ajax')
      cy.authVisit('/admin.php?/cp/publish/edit')
      cy.get('a').contains('Getting to Know ExpressionEngine').click()
      cy.wait(2000) //wait for picker on textarea to initiliaze
      cy.get('.textarea-field-filepicker').first().click()
      cy.wait('@ajax')
      cy.get('.modal-file').should('be.visible')
      let lake_id = null;
      cy.get('.modal-file .app-listing__row a').contains('programming.gif').parents('tr').invoke('attr', 'data-id').then((id) => {
        lake_id = id;
      })
      cy.get('.modal-file .app-listing__row a').contains('programming.gif').click()
      cy.get('.modal-file').should('not.be.visible')
      cy.wait(1000)//give JS some extra time
      cy.get('textarea.markItUpEditor').invoke('val').then((val) => {
        expect(val).to.contain('<img src="{file:' + lake_id + ':url}"')
      })

      cy.get('.file-field-filepicker[title=Edit]').click()
      cy.wait('@ajax')
      cy.get('.modal-file').should('be.visible')
      cy.wait(1000)//give JS some extra time
      let ocean_id = null
      cy.get('.modal-file .app-listing__row a').contains('programming.gif').parents('tr').invoke('attr', 'data-id').then((id) => {
        ocean_id = id;
      })
      cy.wait(1000)//give JS some extra tim
      cy.get('.modal-file .app-listing__row a').contains('programming.gif').click()
      cy.get('.modal-file').should('not.be.visible')
      cy.wait(1000)//give JS some extra time
      cy.get('.js-file-input').invoke('val').then((val) => {
        expect(val).to.eq('{file:' + ocean_id + ':url}')
      })
      cy.get('.fields-upload-chosen-name').should('contain', 'programming.gif')

      cy.get('body').type('{ctrl}', {release: false}).type('s')
      cy.get('textarea.markItUpEditor').invoke('val').then((val) => {
        expect(val).to.contain('<img src="{file:' + lake_id + ':url}"')
      })
      cy.get('.fields-upload-chosen-name').should('contain', 'programming.gif')

      cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
      })

      cy.visit('/index.php/entries/files')
      cy.hasNoErrors()
      cy.get('figure.left img').should('be.visible').and(($img) => {
            // "naturalWidth" and "naturalHeight" are set when the image loads
            expect($img[0].naturalWidth).to.be.eq(100)
      })
      cy.get('figure.left img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('programming.gif')
      })
      cy.get('figure.right img').should('be.visible').and(($img) => {
            // "naturalWidth" and "naturalHeight" are set when the image loads
            expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('figure.right img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('programming.gif')
      })
      cy.get('section.w-12 p img').should('be.visible').and(($img) => {
        // "naturalWidth" and "naturalHeight" are set when the image loads
        expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('section.w-12 p img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('programming.gif')
      })

      cy.get(".file_variables .file_name").invoke('text').should('eq', 'programming.gif')
      cy.get(".file_variables .title").invoke('text').should('contain', 'programming.gif')
      cy.get(".file_variables .model_type").invoke('text').should('eq', 'File')
      cy.get(".file_variables .file_type").invoke('text').should('eq', 'img')
      cy.get(".file_variables .mime_type").invoke('text').should('eq', 'image/gif')
      cy.get(".file_variables .upload_location_id").invoke('text').should('eq', '7')
      cy.get(".file_variables .directory_id").invoke('text').should('eq', '7')
      cy.get(".file_variables .folder_id").invoke('text').should('eq', '0')

      //turn on compatibility mode and make sure everything still works
      cy.log('turn on compatibility mode and make sure everything still works')
      cy.eeConfig({ item: 'file_manager_compatibility_mode', value: 'y' })
      cy.wait(1000)
      
      cy.visit('/index.php/entries/files')
      cy.hasNoErrors()
      cy.get('figure.left img').should('be.visible').and(($img) => {
            // "naturalWidth" and "naturalHeight" are set when the image loads
            expect($img[0].naturalWidth).to.be.eq(100)
      })
      cy.get('figure.left img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('programming.gif')
      })
      cy.get('figure.right img').should('be.visible').and(($img) => {
            // "naturalWidth" and "naturalHeight" are set when the image loads
            expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('figure.right img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('programming.gif')
      })
      cy.get('section.w-12 p img').should('be.visible').and(($img) => {
        // "naturalWidth" and "naturalHeight" are set when the image loads
        expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('section.w-12 p img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('programming.gif')
      })


      cy.eeConfig({ item: 'file_manager_compatibility_mode', value: 'n' })
    })


})
