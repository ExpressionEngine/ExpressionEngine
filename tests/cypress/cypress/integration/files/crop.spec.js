/// <reference types="Cypress" />

import CategoryGroup from '../../elements/pages/channel/CategoryGroup';
// page = CropFile.new
// @return = FileManager.new
const page = new CategoryGroup;
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

        cy.authVisit(page.url);

        // page = CropFile.new
        // @return = FileManager.new
        // @file_name = page.load
        cy.hasNoErrors()

        // Check that the heder data is intact
        page.get('title_toolbar').should('exist')
        page.get('download_all').should('exist')

        // Check that we do not have a sidebar
        page.get('sidebar').should('not.exist')

        page.get('breadcrumb').should('exist')
        page.get('breadcrumb').contains('File ManagerEdit "' + file_name + '"Crop, Rotate & Resize "' + file_name + '"')
        page.get('heading').contains('Crop, Rotate & Resize "' + file_name + '"')

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
        page.get('crop_height_input').type(5)
        page.get('crop_x_input').type(0)
        page.get('crop_y_input').type(0)
        wait_for_ajax
        page.get('crop_width_input')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('requires crop height when cropping', function() {
        page.get('crop_width_input').type(5)
        page.get('crop_x_input').type(0)
        page.get('crop_y_input').type(0)
            // wait_for_ajax
        page.get('crop_height_input')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('requires crop x when cropping', function() {
        page.get('crop_width_input').type(5)
        page.get('crop_height_input').type(5)
        page.get('crop_y_input').type(0)
            // wait_for_ajax
        page.get('crop_x_input')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('requires crop y when cropping', function() {
        page.get('crop_width_input').type(5)
        page.get('crop_height_input').type(5)
        page.get('crop_x_input').type(0)
            // wait_for_ajax
        page.get('crop_y_input')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop width is a number', function() {
        page.get('crop_height_input').type(5)
        page.get('crop_x_input').type(0)
        page.get('crop_y_input').type(0)
            // wait_for_ajax
        page.get('crop_width_input').type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop height is a number', function() {
        page.get('crop_width_input').type(5)
        page.get('crop_x_input').type(0)
        page.get('crop_y_input').type(0)
            // wait_for_ajax
        page.get('crop_height_input').type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop x is a number', function() {
        page.get('crop_width_input').type(5)
        page.get('crop_height_input').type(5)
        page.get('crop_y_input').type(0)
            // wait_for_ajax
        page.get('crop_x_input').type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop y is a number', function() {
        page.get('crop_width_input').type(5)
        page.get('crop_height_input').type(5)
        page.get('crop_x_input').type(0)
            // wait_for_ajax
        page.get('crop_y_input').type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop width is greater than zero', function() {
        page.get('crop_height_input').type(5)
        page.get('crop_x_input').type(0)
        page.get('crop_y_input').type(0)
            // wait_for_ajax
        page.get('crop_width_input').type(0)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('validates that crop height is greater than zero', function() {
        page.get('crop_width_input').type(5)
        page.get('crop_x_input').type(0)
        page.get('crop_y_input').type(0)
            // wait_for_ajax
        page.get('crop_height_input').type(0)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Crop File")
        page.get('alert').contains("We were unable to crop the file, please review and fix errors below.")
    })

    it('can crop an image', function() {
        page.get('crop_width_input').type(5)
        page.get('crop_height_input').type(5)
        page.get('crop_x_input').type(0)
        page.get('crop_y_input').type(0)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Crop Success")
    })

    it('can display the rotate form', function() {
        page.get('rotate_tab').click()
        cy.get('div.tab.t-1.tab-open').should('exist')
    })

    it.skip('requires a rotation option when rotating', function() {
        // skip "cannot figure out how uncheck the default option"
        page.get('rotate_tab').click()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Rotate File")
        page.get('alert').contains("We were unable to rotate the file, please review and fix errors below.")
    })

    it('can rotate right', function() {
        page.get('rotate_tab').click()
        page.get('rotate_right').click()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Rotate Success")
    })

    it('can rotate left', function() {
        page.get('rotate_tab').click()
        page.get('rotate_left').click()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Rotate Success")
    })

    it('can flip vertically', function() {
        page.get('rotate_tab').click()
        page.get('flip_vertical').click()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Rotate Success")
    })

    it('can flip horizontally', function() {
        page.get('rotate_tab').click()
        page.get('flip_horizontal').click()
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Rotate Success")
    })

    it('can display the resize form', function() {
        page.get('resize_tab').click()
        cy.get('div.tab.t-2.tab-open').should('exist')
    })

    it('width is optional when resizing', function() {
        page.get('resize_tab').click()
        page.get('resize_width_input')
        page.get('resize_height_input').type(5)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Resize Success")
    })

    it('height is optional when resizing', function() {
        page.get('resize_tab').click()
        page.get('resize_width_input').type(5)
        page.get('resize_height_input')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Resize Success")
    })

    it('validates that resize width is a number', function() {
        page.get('resize_tab').click()
        page.get('resize_width_input').type('a')
        page.get('resize_height_input').type(5)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Resize File")
        page.get('alert').contains("We were unable to resize the file, please review and fix errors below.")
    })

    it('validates that resize height is a number', function() {
        page.get('resize_tab').click()
        page.get('resize_width_input').type(5)
        page.get('resize_height_input').type('a')
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('error')
        cy.get('.fieldset-invalid').should('exist')
        page.get('alert').contains("Cannot Resize File")
        page.get('alert').contains("We were unable to resize the file, please review and fix errors below.")
    })

    it('can resize an image', function() {
        page.get('resize_tab').click()
        page.get('resize_width_input').type(5)
        page.get('resize_height_input').type(5)
        page.get('save').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        cy.get('.fieldset-invalid').should('not.exist')
        page.get('alert').contains("File Resize Success")
    })

    it('can navigate back to the filemanger', function() {
        cy.contains("File Manager").click()
        cy.hasNoErrors()

        // file_manager = FileManager.new
        // file_manager.displayed?
    })

    it('can navigate to the edit action', function() {
        page.get('breadcrumb').find('a').eq(1).click()
        cy.hasNoErrors()

        // edit_file = EditFile.new
        // edit_file.displayed?
    })

    it('shows an error if the file has no write permissions', function() {
        cy.exec(`chmod 444 ${uploadDirectory}*.{gif,jpg,png}`)
            // page.load
        cy.hasNoErrors()

        page.hasAlert('error')
        page.get('alert').contains("File Not Writable")
        page.get('alert').contains("Cannot write to the file")
        page.get('alert').contains("Check your file permissions on the server")
    })

    it('shows an error if the file does not exist', function() {
        cy.exec(`rm ${uploadDirectory}/*.{gif,jpg,png}`)
            // page.load
        cy.hasNoErrors()

        cy.contains("404")

        // page.hasAlert('error')
        // page.get('alert').contains("Cannot find the file")
    })

    it('shows an error if the directory does not exist', function() {
        cy.task('filesystem:delete', uploadDirectory)
            // page.load
        cy.hasNoErrors()

        cy.contains("404")

        // page.hasAlert('error')
        // page.get('alert').contains("Cannot find the file")
    })

})
