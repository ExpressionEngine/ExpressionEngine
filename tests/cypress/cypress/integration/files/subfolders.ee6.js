/// <reference types="Cypress" />

import UploadEdit from '../../elements/pages/files/UploadEdit';
import WatermarkEdit from '../../elements/pages/files/WatermarkEdit';
import FileManager from '../../elements/pages/files/FileManager';
const page = new UploadEdit;
const managerPage = new FileManager;
const watermark = new WatermarkEdit;
const upload_path = "{base_path}/images"

context('Upload Destination Subfolders', () => {

    before(function() {
        cy.task('db:seed')
            //cleanup folders
        cy.task('filesystem:delete', '../../images/about/sub*')
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
        page.load_edit_for_dir(1)
        page.get('name').should('exist')
        page.get('toggle_subfolder_support').should('have.class', 'off')
        page.get('toggle_subfolder_support').click()
        page.get('toggle_subfolder_support').should('have.class', 'on')
        page.submit()
        cy.hasNoErrors()
    })

    it('Can create a new folder', () => {
        managerPage.load_for_dir(1)
        managerPage.get('new_folder_button').click()

        cy.get('.modal-new-folder input[name="folder_name"]').type('sub1')
        cy.get('.modal-new-folder button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert('success').contains('Folder created')
    })

    it('Cannot create a folder that already exists', () => {
        managerPage.load_for_dir(1)
        managerPage.get('new_folder_button').click()

        cy.get('.modal-new-folder input[name="folder_name"]').type('sub1')
        cy.get('.modal-new-folder button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert().contains('Could not create folder')
    })

    it('Cannot create a folder with special characters', () => {
        managerPage.load_for_dir(1)
        managerPage.get('new_folder_button').click()

        cy.get('.modal-new-folder input[name="folder_name"]').type('sub1/sub2')
        cy.get('.modal-new-folder button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert().contains('Could not create folder')
    })

    it('Can create a nested folder', () => {
        managerPage.load_for_dir(1)
        managerPage.get('new_folder_button').click()

        cy.get('.modal-new-folder [data-input-value="upload_location"] .js-dropdown-toggle').click()
        cy.get('.modal-new-folder .select__dropdown-item').contains('sub1').click()
        cy.get('.modal-new-folder input[name="folder_name"]').type('sub2')
        cy.get('.modal-new-folder button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert('success').contains('Folder created')
    })

    it('Cannot move a folder into current parent', () => {
        managerPage.load_for_dir(1)

        cy.get('.app-listing__row[title="sub1"] .button-toolbar').click()
        cy.get('.toolbar.has-open-dropdown .move').click()

        cy.get('.modal-confirm-move-file [data-input-value="upload_location"] .js-dropdown-toggle').click()
        cy.get('.modal-confirm-move-file .select__dropdown-item').contains('About').click()
        cy.get('.modal-confirm-move-file #fieldset-confirm .toggle-btn').click()
        cy.get('.modal-confirm-move-file button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert().contains('The file is already in target destination')
    })

    it('Can move a folder', () => {
        managerPage.load_for_dir(1)

        cy.get('.app-listing__row[title="sub1"] a').contains('sub1').click()

        cy.get('.app-listing__row[title="sub2"] .button-toolbar').click()
        cy.get('.toolbar.has-open-dropdown .move').click({ force: true })

        cy.get('.modal-confirm-move-file [data-input-value="upload_location"] .js-dropdown-toggle').click()
        cy.get('.modal-confirm-move-file .select__dropdown-item').contains('About').click()

        cy.get('.modal-confirm-move-file button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert('success').contains('moved')
    })

    it('Cannot rename a folder to an existing folder name', () => {
        managerPage.load_for_dir(1)

        cy.get('.app-listing__row[title="sub2"] .button-toolbar').click()

        cy.get('.toolbar.has-open-dropdown .rename').click({ force: true })

        cy.get('.modal-confirm-rename-file input[name="new_name"]').type('sub1')
        cy.get('.modal-confirm-rename-file button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert().contains('Could not rename')
    })

    it('Can rename a folder', () => {
        managerPage.load_for_dir(1)

        cy.get('.app-listing__row[title="sub2"] .button-toolbar').click()

        cy.get('.toolbar.has-open-dropdown .rename').click({ force: true })

        cy.get('.modal-confirm-rename-file input[name="new_name"]').type('sub3')
        cy.get('.modal-confirm-rename-file button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert('success').contains('renamed')
    })

    it('Can upload a file to a folder', () => {
        managerPage.load_for_dir(1)

        cy.get('.app-listing__row[title="sub1"] a').contains('sub1').click()

        cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
        cy.intercept('/admin.php?/cp/files/directory/*').as('table')
        managerPage.get('file_input').find('.file-field__dropzone').attachFile('../../support/file/programming.gif', { subjectType: 'drag-n-drop' })

        cy.wait('@upload')
        cy.wait('@table')
        cy.hasNoErrors()

        cy.get('..app-listing__row').contains("programming.gif")
    })

    it('Can delete a folder', () => {
        managerPage.load_for_dir(1)

        cy.get('.app-listing__row[title="sub3"] .button-toolbar').click()
        cy.get('.toolbar.has-open-dropdown .delete').click({ force: true })

        cy.get('.modal-confirm-delete-file button[type="submit"]').click()

        cy.hasNoErrors()

        managerPage.hasAlert('success').contains('deleted')
    })


})