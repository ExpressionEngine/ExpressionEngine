/// <reference types="Cypress" />

import UploadEdit from '../../elements/pages/files/UploadEdit';
import FileManager from '../../elements/pages/files/FileManager';
import UploadSync from '../../elements/pages/files/UploadSync';
const page = new UploadEdit;
const managerPage = new FileManager;
const syncPage = new UploadSync;
const { _, $ } = Cypress

const upload_path = "{base_path}/images"

context('Upload Destination Subfolders', () => {

    before(function() {
        cy.task('db:seed')
            //cleanup folders
        cy.task('filesystem:delete', '../../images/uploads/*')
    })

    beforeEach(function() {
        cy.auth();
        page.load()
        cy.hasNoErrors()

        cy.server()
    })

    after(function() {
        //cleanup folders
        // cy.task('filesystem:delete', '../../images/about/sub*')
    })

    it('Enable Subfolder support', () => {
        page.load_edit_for_dir(2)
        page.get('name').should('exist')
        page.get('toggle_subfolder_support').should('have.class', 'off')
        page.get('toggle_subfolder_support').click()
        page.get('toggle_subfolder_support').should('have.class', 'on')
        page.submit()
        cy.hasNoErrors()
    })

    it('Can create a new folder', () => {
        managerPage.load_for_dir(2)
        managerPage.get('new_folder_button').click()

        cy.get('.modal-new-folder input[name="folder_name"]').type('sub1')
        cy.get('.modal-new-folder button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert('success').contains('Folder created')
    })

    it('Cannot create a folder that already exists', () => {
        managerPage.load_for_dir(2)
        managerPage.get('new_folder_button').click()

        cy.get('.modal-new-folder input[name="folder_name"]').type('sub1')
        cy.get('.modal-new-folder button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert().contains('Could not create folder')
    })

    it('Cannot create a folder with special characters', () => {
        managerPage.load_for_dir(2)
        managerPage.get('new_folder_button').click()

        cy.get('.modal-new-folder input[name="folder_name"]').type('sub1/sub2')
        cy.get('.modal-new-folder button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert().contains('Could not create folder')
    })

    it('Can create a nested folder', () => {
        managerPage.load_for_dir(2)
        managerPage.get('new_folder_button').click()

        cy.get('.modal-new-folder [data-input-value="upload_location"] .js-dropdown-toggle').click()
        cy.get('.modal-new-folder .select__dropdown-item').contains('sub1').click()
        cy.get('.modal-new-folder input[name="folder_name"]').type('sub2')
        cy.get('.modal-new-folder button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert('success').contains('Folder created')
    })

    it('Cannot move a folder into current parent', () => {
        managerPage.load_for_dir(2)

        cy.get('.app-listing__row[title="sub1"] .button-toolbar').click()
        cy.get('.toolbar.has-open-dropdown .move').click({force: true})

        cy.get('.modal-confirm-move-file [data-input-value="upload_location"] .js-dropdown-toggle').click()
        cy.get('.modal-confirm-move-file .select__dropdown-item').contains('Main Upload Directory').click()
        cy.get('.modal-confirm-move-file #fieldset-confirm .toggle-btn').click()
        cy.get('.modal-confirm-move-file button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert().contains('The file is already in target destination')
    })

    it('Can move a folder', () => {
        managerPage.load_for_dir(2)

        cy.get('.app-listing__row[title="sub1"] a').contains('sub1').click()

        cy.get('.app-listing__row[title="sub2"] .button-toolbar').click()
        cy.get('.toolbar.has-open-dropdown .move').click({ force: true })

        cy.get('.modal-confirm-move-file [data-input-value="upload_location"] .js-dropdown-toggle').click()
        cy.get('.modal-confirm-move-file .select__dropdown-item').contains('Main Upload Directory').click()

        cy.get('.modal-confirm-move-file button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert('success').contains('moved')
    })

    it('Cannot rename a folder to an existing folder name', () => {
        managerPage.load_for_dir(2)

        cy.get('.app-listing__row[title="sub2"] .button-toolbar').click()

        cy.get('.toolbar.has-open-dropdown .rename').click({ force: true })

        cy.get('.modal-confirm-rename-file input[name="new_name"]').type('sub1')
        cy.get('.modal-confirm-rename-file button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert().contains('Could not rename')
    })

    it('Can rename a folder', () => {
        managerPage.load_for_dir(2)

        cy.get('.app-listing__row[title="sub2"] .button-toolbar').click()

        cy.get('.toolbar.has-open-dropdown .rename').click({ force: true })

        cy.get('.modal-confirm-rename-file input[name="new_name"]').type('sub3')
        cy.get('.modal-confirm-rename-file button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert('success').contains('renamed')
    })

    it('Can upload a file to a folder', () => {
        managerPage.load_for_dir(2)

        cy.get('.app-listing__row[title="sub1"] a').contains('sub1').click()

        cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
        cy.intercept('/admin.php?/cp/files/directory/*').as('table')
        managerPage.get('file_input').find('.file-field__dropzone').attachFile('../../support/file/README.md', { subjectType: 'drag-n-drop' })

        cy.wait('@upload')
        cy.wait('@table')
        cy.hasNoErrors()

        cy.get('.app-listing__row').contains("README.md")
    })
    
    it('Can move a file to a folder', () => {
        managerPage.load_for_dir(2)
        let filename = 'LICENSE.txt';

        cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
        cy.intercept('/admin.php?/cp/files/directory/*').as('table')
        cy.get('.file-upload-widget').then(function(widget) {
            $(widget).removeClass('hidden')
        })
        managerPage.get('file_input').find('.file-field__dropzone').attachFile(('../../../../LICENSE.txt'), { subjectType: 'drag-n-drop' })

        cy.wait('@upload')
        cy.wait('@table')
        cy.hasNoErrors()
        cy.get('.app-listing__row').should('contain', filename)

        cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .js-dropdown-toggle').click()
        cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .dropdown').should('be.visible')
        cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .dropdown--open .dropdown__link:contains("Move")').click({force: true})
        managerPage.get('modal').should('be.visible')
        managerPage.get('modal').find('.js-dropdown-toggle').click()
        managerPage.get('modal').find('.select--open .select__dropdown-item').contains('sub1').click()
        managerPage.get('modal').find('.button--primary').filter(':visible').first().click()
        cy.hasNoErrors()

        //file is not here anymore
        managerPage.get('files').should('not.contain', filename)
        //but is in subfolder
        cy.get('.app-listing__row[title="sub1"] a').contains('sub1').click()
        managerPage.get('files').should('contain', filename)
        //and is editable
        cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .js-dropdown-toggle').click()
        cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .dropdown').should('be.visible')
        cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .dropdown--open .dropdown__link:contains("Edit")').click({force: true})
        cy.get('.main-nav__title').should('contain', 'Edit File')
        cy.get('.title-bar__title').should('contain', filename)
    })

    it('Can move file between folders', () => {
        managerPage.load_for_dir(2)
        cy.get('.app-listing__row[title="sub1"] a').contains('sub1').click()

        let filename = '';
        managerPage.get('title_names').eq(0).find('a').invoke('text').then((text) => {
            filename = text.trim()

            managerPage.get('files').eq(0).find('.app-listing__cell .js-dropdown-toggle').click()
            managerPage.get('files').eq(0).find('.app-listing__cell .dropdown').should('be.visible')
            managerPage.get('files').eq(0).find(".app-listing__cell .dropdown--open .dropdown__link:contains('Move')").click({force: true})
            managerPage.get('modal').should('be.visible')
            managerPage.get('modal').find('.js-dropdown-toggle').click()
            managerPage.get('modal').find('.select--open .select__dropdown-item').contains('sub3').click()
            managerPage.get('modal').find('.button--primary').filter(':visible').first().click()
            cy.hasNoErrors()

            //file is not here anymore
            cy.get('.ee-main__content form .table-responsive table').should('not.contain', filename)
            //but is in subfolder
            managerPage.load_for_dir(2)
            cy.get('.app-listing__row[title="sub3"] a').contains('sub3').click()
            managerPage.get('files').should('contain', filename)
            //and is editable
            cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .js-dropdown-toggle').click()
            cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .dropdown').should('be.visible')
            cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .dropdown--open .dropdown__link:contains("Edit")').click({force: true})
            cy.get('.main-nav__title').should('contain', 'Edit File')
            cy.get('.title-bar__title').should('contain', filename)
        })
    })

    it('Cannot move file if it already exists', () => {
        managerPage.load_for_dir(2)
        let filename = 'LICENSE.txt';

        cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
        cy.intercept('/admin.php?/cp/files/directory/*').as('table')
        cy.get('.file-upload-widget').then(function(widget) {
            $(widget).removeClass('hidden')
        })
        managerPage.get('file_input').find('.file-field__dropzone').attachFile(('../../../../LICENSE.txt'), { subjectType: 'drag-n-drop' })

        cy.wait('@upload')
        cy.wait('@table')
        cy.get('.app-listing__row').should('contain', filename)
        cy.hasNoErrors()

        cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .js-dropdown-toggle').click()
        cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .dropdown').should('be.visible')
        cy.get('.app-listing__row:contains(' + filename + ') .app-listing__cell .dropdown--open .dropdown__link:contains("Move")').click({force: true})
        managerPage.get('modal').should('be.visible')
        managerPage.get('modal').find('.js-dropdown-toggle').click()
        managerPage.get('modal').find('.select--open .select__dropdown-item').contains('sub3').click()
        managerPage.get('modal').find('.button--primary').filter(':visible').first().click()
        cy.hasNoErrors()

        cy.get('.app-notice---important').contains('Could not move some files')

        //file is still in its place
        managerPage.get('files').should('contain', filename)
    })

    it('Can delete a folder', () => {
        managerPage.load_for_dir(2)

        cy.get('.app-listing__row[title="sub3"] .button-toolbar').click()
        cy.get('.toolbar.has-open-dropdown .delete').click({ force: true })

        cy.get('.modal-confirm-delete-file button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert('success').contains('deleted')
    })

    context('sync the subfolders', () => {
        before(() => {
            cy.task('db:seed')
            cy.task('filesystem:delete', '../../images/uploads/*').then(() => {
                cy.task('filesystem:create', '../../images/uploads/to-be-synced').then(() => {
                    cy.task('filesystem:copy', { from: '../../LICENSE.txt', to: '../../images/uploads/to-be-synced/' })
                })
                cy.task('filesystem:create', '../../images/uploads/_hidden-folder').then(() => {
                    cy.task('filesystem:copy', { from: 'support/file/README.md', to: '../../images/uploads/_hidden-folder/' })
                })
                cy.task('filesystem:create', '../../images/uploads/empty-folder');
            })
        })

        it('adding subfolder on filesystem', function() {

            page.load_edit_for_dir(2)
            page.get('name').should('exist')
            page.get('toggle_subfolder_support').should('have.class', 'off')
            page.get('toggle_subfolder_support').click()
            page.get('toggle_subfolder_support').should('have.class', 'on')
            page.submit()
            cy.hasNoErrors()

            //nothing listed initially
            managerPage.load_for_dir(2)
            cy.hasNoErrors()
            cy.get('.app-listing__row').should('not.exist')

            //sync the files
            syncPage.load_sync_for_dir(2)
            syncPage.get('wrap').contains('3 files and folders')
            syncPage.get('sync_button').click()
            cy.wait(10000)
            syncPage.get('alert').should('exist')
            syncPage.get('alert').contains('Upload directory synchronized')
            cy.hasNoErrors()
    
            //the non-hidden folder is listed
            managerPage.load_for_dir(2)
            cy.get('.app-listing__row').should('contain', 'to-be-synced')
            cy.get('.app-listing__row').should('contain', 'empty-folder')
            cy.get('.app-listing__row').should('not.contain', '_hidden-folder')
    
            //the file in folder is listed
            cy.get('.app-listing__row').contains('to-be-synced').first().click()
            cy.get('.app-listing__row').should('contain', 'LICENSE.txt')
    
            //only the file from non-hidden folder is listed
            cy.visit('/admin.php?/cp/files');
            cy.get('.app-listing__row').should('contain', 'LICENSE.txt')
            cy.get('.app-listing__row').should('not.contain', 'README.md')
    
        })
    })




})
