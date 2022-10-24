/// <reference types="Cypress" />

import CropFile from '../../elements/pages/files/CropFile';

const page = new CropFile;
const { _, $ } = Cypress
const uploadDirectory = '../../images/about/'

context('File Manager / Crop File', () => {

    before(function() {
        cy.task('db:seed')
        cy.task('filesystem:delete',  Cypress.env("TEMP_DIR")+'/about')
            // Create backups of these folders so we can restore them after each test
        cy.task('filesystem:create',  Cypress.env("TEMP_DIR")+'/about')
        cy.task('filesystem:copy', { from: `${uploadDirectory}*`, to:  Cypress.env("TEMP_DIR")+'/about' })
    })

    after(function() {
        cy.task('filesystem:delete', Cypress.env("TEMP_DIR")+'/about')
    })

    beforeEach(function() {

        cy.auth();
        cy.contains('Files').click()
        cy.get('.sidebar__link').contains('About').click()
        cy.get('.ee-main__content form .table-responsive table tr:nth-child(2) td:nth-child(4)').invoke('text').as('file_name')
        //cy.get('.ee-main__content form .table-responsive table tr:nth-child(2) td:nth-child(4) ul.toolbar li.crop').click()
        // cy.get('a[title="Crop"]').first().click({force: true})
        cy.get('.ee-main__content form .table-responsive table tr:nth-child(2) .toolbar-wrap .js-dropdown-toggle').click()
        cy.get('a[title="Edit"]').filter(':visible').first().click()
        page.get('crop_tab').click()
        // page = CropFile.new
        // @return = FileManager.new
        // @file_name = page.load
        cy.hasNoErrors()



        //page.get('breadcrumb').should('exist')
        //page.get('breadcrumb').contains('File ManagerEdit "' + file_name + '"Crop, Rotate & Resize "' + file_name + '"')
        cy.get('@file_name').then((filename) => {
            //page.get('heading').contains('Crop, Rotate & Resize "' + filename + '"')
            page.get('heading').contains(filename.replace('File Name', ''))
            page.get('crop_tab').should('have.class', 'active')
        })

        page.get('crop_tab').should('exist')
        page.get('rotate_tab').should('exist')
        page.get('resize_tab').should('exist')
    })

    afterEach(function() {
        cy.task('filesystem:delete', `${uploadDirectory}`)
        cy.task('filesystem:create', `${uploadDirectory}`);
        cy.task('filesystem:copy', { from: Cypress.env("TEMP_DIR")+'/about/*', to: `${uploadDirectory}` })
        //FileUtils.chmod_R 0777, @upload_dir
    })


    it('shows the crop form by default', function() {
        page.get('crop_width_input').should('exist')
        page.get('crop_height_input').should('exist')
        page.get('crop_x_input').should('exist')
        page.get('crop_y_input').should('exist')
        page.get('crop_image_preview').should('exist')
    })

    it('requires crop width when cropping', function() {
        page.get('crop_height_input').clear().type(5)
        page.get('crop_x_input').clear().type(0)
        page.get('crop_y_input').clear().type(0)
        //wait_for_ajax
        page.get('crop_width_input').clear()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('requires crop height when cropping', function() {
        page.get('crop_width_input').clear().type(5)
        page.get('crop_x_input').clear().type(0)
        page.get('crop_y_input').clear().type(0)
            // wait_for_ajax
        page.get('crop_height_input').clear()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('requires crop x when cropping', function() {
        page.get('crop_width_input').clear().type(5)
        page.get('crop_height_input').clear().type(5)
        page.get('crop_y_input').clear().type(0)
            // wait_for_ajax
        page.get('crop_x_input').clear()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('requires crop y when cropping', function() {
        page.get('crop_width_input').clear().type(5)
        page.get('crop_height_input').clear().type(5)
        page.get('crop_x_input').clear().type(0)
            // wait_for_ajax
        page.get('crop_y_input').clear()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop width is a number', function() {
        page.get('crop_height_input').clear().type(5)
        page.get('crop_x_input').clear().type(0)
        page.get('crop_y_input').clear().type(0)
            // wait_for_ajax
        page.get('crop_width_input').clear().type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop height is a number', function() {
        page.get('crop_width_input').clear().type(5)
        page.get('crop_x_input').clear().type(0)
        page.get('crop_y_input').clear().type(0)
            // wait_for_ajax
        page.get('crop_height_input').clear().type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop x is a number', function() {
        page.get('crop_width_input').clear().type(5)
        page.get('crop_height_input').clear().type(5)
        page.get('crop_y_input').clear().type(0)
            // wait_for_ajax
        page.get('crop_x_input').clear().type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop y is a number', function() {
        page.get('crop_width_input').clear().type(5)
        page.get('crop_height_input').clear().type(5)
        page.get('crop_x_input').clear().type(0)
            // wait_for_ajax
        page.get('crop_y_input').clear().type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop width is greater than zero', function() {
        page.get('crop_height_input').clear().type(5)
        page.get('crop_x_input').clear().type(0)
        page.get('crop_y_input').clear().type(0)
            // wait_for_ajax
        page.get('crop_width_input').clear().type(0)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop height is greater than zero', function() {
        page.get('crop_width_input').clear().type(5)
        page.get('crop_x_input').clear().type(0)
        page.get('crop_y_input').clear().type(0)
            // wait_for_ajax
        page.get('crop_height_input').clear().type(0)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('can crop an image', function() {
        page.get('crop_width_input').clear().type(5)
        page.get('crop_height_input').clear().type(5)
        page.get('crop_x_input').clear().type(0)
        page.get('crop_y_input').clear().type(0)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        // cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Crop Success")
    })

    it('can display the rotate form', function() {
        page.get('rotate_tab').click()
        cy.get('div.tab.t-rotate.tab-open').should('exist')
    })

    /*it('requires a rotation option when rotating', function() {
        // skip "cannot figure out how uncheck the default option"
        page.get('rotate_tab').click()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Rotate File")
        page.get('alert').contains("We were unable to rotate the file, please review and fix errors below.")
    })**/

    it('can rotate right', function() {
        page.get('rotate_tab').click()
        page.get('rotate_right').click()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        // cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Rotate Success")
    })

    it('can rotate left', function() {
        page.get('rotate_tab').click()
        page.get('rotate_left').click()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        // cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Rotate Success")
    })

    it('can flip vertically', function() {
        page.get('rotate_tab').click()
        page.get('flip_vertical').click()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        // cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Rotate Success")
    })

    it('can flip horizontally', function() {
        page.get('rotate_tab').click()
        page.get('flip_horizontal').click()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        // cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Rotate Success")
    })

    it('can display the resize form', function() {
        page.get('resize_tab').click()
        cy.get('div.tab.t-resize.tab-open').should('exist')
    })

    it('width is optional when resizing', function() {
        page.get('resize_tab').click()
        page.get('resize_width_input').clear()
        page.get('resize_height_input').clear().type(5)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        // cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Resize Success")
    })

    it('height is optional when resizing', function() {
        page.get('resize_tab').click()
        page.get('resize_width_input').clear().type(5)
        page.get('resize_height_input').clear()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        // cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Resize Success")
    })

    it('validates that resize width is a number', function() {
        page.get('resize_tab').click()
        page.get('resize_height_input').clear().type(5)
        page.get('resize_width_input').clear().type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Resize File")
        page.get('alert').contains("We were unable to resize the file, please review and fix errors below.")
    })

    it('validates that resize height is a number', function() {
        page.get('resize_tab').click()
        page.get('resize_width_input').clear().type(5)
        page.get('resize_height_input').clear().type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        // cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Resize File")
        page.get('alert').contains("We were unable to resize the file, please review and fix errors below.")
    })

    it('can resize an image', function() {
        page.get('resize_tab').click()
        page.get('resize_width_input').clear().type(5)
        page.get('resize_height_input').clear().type(5)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        // cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Resize Success")
    })

    it('can navigate back to the filemanger', function() {
        cy.get('.ee-sidebar__items a:contains("Files")').click()
        cy.hasNoErrors()

        page.get('heading').contains('Files')

        // file_manager = FileManager.new
        // file_manager.displayed?
    })

    /*it('can navigate to the edit action', function() {
        page.get('breadcrumb').find('a').eq(1).click()
        cy.hasNoErrors()

        // edit_file = EditFile.new
        // edit_file.displayed?
    })*/

    it('shows an error if the file has no write permissions', function() {

        if (Cypress.platform === 'win32')
        {
            cy.log('skipped because of Windows platform')
        }
        else
        {
            cy.exec(`chmod 444 ${uploadDirectory}*.gif`)
            cy.exec(`chmod 444 ${uploadDirectory}*.jpg`)
            cy.exec(`chmod 444 ${uploadDirectory}*.png`)
            cy.reload()
                // page.load
            cy.hasNoErrors()

            page.hasAlert('error')
            page.get('alert').contains("File Not Writable")
            page.get('alert').contains("Cannot write to the file")
            page.get('alert').contains("Check your file permissions on the server")
        }
    })

    it('shows an error if the file does not exist', function() {

        cy.task('filesystem:delete', uploadDirectory+'\*')

        cy.reload()
        cy.hasNoErrors()

        cy.contains("404")

        // page.hasAlert('error')
        // page.get('alert').contains("Cannot find the file")
    })

    it('shows an error if the directory does not exist', function() {
        cy.task('filesystem:delete', uploadDirectory)
        cy.reload()
        cy.hasNoErrors()

        cy.contains("404")

        // page.hasAlert('error')
        // page.get('alert').contains("Cannot find the file")
    })

})
