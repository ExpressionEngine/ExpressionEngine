/// <reference types="Cypress" />

import UploadEdit from '../../elements/pages/files/UploadEdit';
import WatermarkEdit from '../../elements/pages/files/WatermarkEdit';
const page = new UploadEdit;
const watermark = new WatermarkEdit;
const upload_path = "{base_path}/images"

context('Upload Destination Create/Edit', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()

    cy.server()
  })

  it('shows the Upload Destination Create/Edit page', () => {
    page.get('name').should('exist')
    page.get('url').should('exist')
    page.get('server_path').should('exist')
    page.get('allowed_types').should('exist')
    page.get('max_size').should('exist')
    page.get('max_width').should('exist')
    page.get('max_height').should('exist')
    page.get('image_manipulations').should('exist')
    page.get('upload_member_groups').should('exist')
    page.get('cat_group').should('exist')
  })

  it('should validate regular fields', () => {
    const url_error = 'This field must contain a valid URL.'
    page.submit()
    cy.hasNoErrors()
    page.hasErrors()
    page.get('wrap').contains('Attention: Upload directory not saved')
    page.hasError(page.get('name'), page.messages.validation.required)
    //page.hasError(page.get('url'), url_error)
    //page.hasError(page.get('server_path'), page.messages.validation.required)

    // AJAX validation
    page.load()


    cy.route("POST", "**/files/uploads/**").as("ajax1");
    page.get('name').trigger('blur')
    cy.wait("@ajax1");
    page.hasError(page.get('name'), page.messages.validation.required)
    page.hasErrors()

    cy.route("POST", "**/files/uploads/**").as("ajax2");
    page.get('name').clear().type('Dir')
    page.get('name').trigger('blur')
    cy.wait("@ajax2");
    page.hasNoError(page.get('name'))
    page.hasNoErrors()

    // Duplicate directory name
    cy.route("POST", "**/files/uploads/**").as("ajax3");
    page.get('name').clear().type('Main Upload Directory')
    page.get('name').trigger('blur')
    cy.wait("@ajax3");
    page.hasErrorsCount(1)
    page.hasError(page.get('name'), page.messages.validation.unique)
    page.hasErrors()

    // Multiple errors for URL
    // Error when just submitting "http://"
    cy.route("POST", "**/files/uploads/**").as("ajax4");
    page.get('url').clear().type('http://').trigger('blur')
    cy.wait("@ajax4");
    page.hasErrorsCount(2)
    page.hasError(page.get('url'), url_error)
    page.hasErrors()

    // Resolve that error
    cy.route("POST", "**/files/uploads/**").as("ajax5");
    page.get('url').clear().type('http://ee3/')
    page.get('url').trigger('blur')
    cy.wait("@ajax5");
    page.hasErrorsCount(1)
    page.hasNoError(page.get('url'))
    page.hasErrors()

    // Error when left blank
    cy.route("POST", "**/files/uploads/**").as("ajax6");
    page.get('url').clear().trigger('blur')
    cy.wait("@ajax6");
    page.hasErrorsCount(2)
    page.hasError(page.get('url'), page.messages.validation.required)
    page.hasErrors()

    // Server path errors, path must both exist and be writable
    // Required:
    cy.route("POST", "**/files/uploads/**").as("ajax7");
    page.get('server_path').clear().trigger('blur')
    cy.wait("@ajax7");
    page.hasErrorsCount(3)
    page.hasError(page.get('server_path'), page.messages.validation.required)
    page.hasErrors()

    // Resolve so can break again:
    cy.route("POST", "**/files/uploads/**").as("ajax8");
    page.get('server_path').clear().type(upload_path, {parseSpecialCharSequences: false}).trigger('blur')
    cy.wait("@ajax8");
    page.hasErrorsCount(2)
    page.hasNoError(page.get('server_path'))
    page.hasErrors()

    // Invalid path:
    cy.route("POST", "**/files/uploads/**").as("ajax9");
    page.get('server_path').clear().type('sdfsdf').trigger('blur')
    cy.wait("@ajax9");
    page.hasErrorsCount(3)
    page.hasError(page.get('server_path'), page.messages.validation.invalid_path)
    page.hasErrors()

    // Resolve so can break again:
    cy.route("POST", "**/files/uploads/**").as("ajax10");
    page.get('server_path').clear().type(upload_path, {parseSpecialCharSequences: false})
    page.get('server_path').trigger('blur')
    cy.wait("@ajax10");
    page.hasErrorsCount(2)
    page.hasNoError(page.get('server_path'))
    page.hasErrors()

    // Not writable path:
    cy.route("POST", "**/files/uploads/**").as("ajax11");
    page.get('server_path').clear().type('/')
    page.get('server_path').trigger('blur')
    cy.wait("@ajax11");
    page.hasErrorsCount(3)
    page.hasError(page.get('server_path'), page.messages.validation.not_writable)
    page.hasErrors()

    cy.route("POST", "**/files/uploads/**").as("ajax12");
    page.get('max_size').clear().type('sdf')
    page.get('max_size').trigger('blur')
    cy.wait("@ajax12");
    page.hasErrorsCount(4)
    page.hasError(page.get('max_size'), page.messages.validation.numeric)
    page.hasErrors()

    cy.route("POST", "**/files/uploads/**").as("ajax13");
    page.get('max_width').clear().type('sdf')
    page.get('max_width').trigger('blur')
    cy.wait("@ajax13");
    page.hasErrorsCount(5)
    page.hasError(page.get('max_width'), page.messages.validation.natural_number)
    page.hasErrors()

    cy.route("POST", "**/files/uploads/**").as("ajax14");
    page.get('max_height').clear().type('sdf')
    page.get('max_height').trigger('blur')
    cy.wait("@ajax14");
    page.hasErrorsCount(6)
    page.hasError(page.get('max_height'), page.messages.validation.natural_number)
    page.hasErrors()

    // These fields should not be required
    cy.route("POST", "**/files/uploads/**").as("ajax15");
    page.get('max_size').clear()
    page.get('max_size').trigger('blur')
    cy.wait("@ajax15");
    page.hasErrorsCount(5)
    page.hasNoError(page.get('max_size'))

    cy.route("POST", "**/files/uploads/**").as("ajax16");
    page.get('max_width').clear()
    page.get('max_width').trigger('blur')
    cy.wait("@ajax16");
    page.hasErrorsCount(4)
    page.hasNoError(page.get('max_width'))

    cy.route("POST", "**/files/uploads/**").as("ajax17");
    page.get('max_height').clear()
    page.get('max_height').trigger('blur')
    cy.wait("@ajax17");
    page.hasErrorsCount(3)
    page.hasNoError(page.get('max_height'))
    page.hasErrors()

    // Fix rest of fields
    cy.route("POST", "**/files/uploads/**").as("ajax18");
    page.get('name').clear().type('Dir2')
    page.get('name').trigger('blur')
    cy.wait("@ajax18");
    page.hasErrorsCount(2)
    page.hasNoError(page.get('name'))
    page.hasErrors()

    cy.route("POST", "**/files/uploads/**").as("ajax19");
    page.get('url').clear().type('http://ee3/')
    page.get('url').trigger('blur')
    cy.wait("@ajax19");
    page.hasErrorsCount(1)
    page.hasNoError(page.get('url'))
    page.hasErrors()

    cy.route("POST", "**/files/uploads/**").as("ajax20");
    page.get('server_path').clear().type(upload_path, {parseSpecialCharSequences: false})
    page.get('server_path').trigger('blur')
    cy.wait("@ajax20");
    page.hasNoError(page.get('server_path'))
    page.hasNoErrors()

    // Lots of AJAX going on, make sure there are no JS errors
    cy.hasNoErrors()

    page.submit()
    cy.hasNoErrors()
  })

  it('should validate image manipulation data', () => {

    watermark.load()
    watermark.get('wm_name').clear().type('Test')
    watermark.submit()

    page.load()
    page.get('wrap').contains('No manipulations created')
    page.get('grid_add_no_results').should('exist').should('be.visible')
    page.get('grid_add').should('not.be.visible')

    // Should add row
    page.get('grid_add_no_results').click()
    page.get('wrap').should('not.have.text','No manipulations created')
    page.get('grid_add_no_results').should('not.be.visible')
    page.get('grid_add').should('exist').should('be.visible')
    page.get('grid_rows').should('have.length', 2) // Includes header

    // Make sure watermarks are available
    const options = ["0", "1"]
		page.watermark_for_row(1).find('option').each(function(el, i){
			expect(el).value(options[i])
		})

    cy.log('Should remove row and show "no manipulations" message')
    page.delete_for_row(1).click()
    page.get('grid_add_no_results').should('exist')
    page.get('grid_add').should('not.be.visible')
    page.get('grid_rows').should('have.length', 2) // Header and no results row

    page.get('grid_add_no_results').click()

    page.get('name').clear().type('Dir')
    page.get('url').clear().type('http://ee3/')
    page.get('server_path').clear().type(upload_path, {parseSpecialCharSequences: false}).blur()
    page.submit()

    const dimension_error = 'A height or width must be entered if no watermark is selected.'

    page.hasErrors()
    page.get('error_messages').should('have.length', 3)
    page.get('image_manipulations').find('td.invalid').should('exist')
    page.name_for_row(1).parent().find('em.ee-form-error-message').should('exist').contains(page.messages.validation.required)
    page.width_for_row(1).parent().find('em.ee-form-error-message').should('exist').contains(dimension_error)
    page.height_for_row(1).parent().find('em.ee-form-error-message').should('exist').contains(dimension_error)
    cy.hasNoErrors()

    // Reset for AJAX validation
    cy.log('Reset for AJAX validation')
    page.load()
    page.get('grid_add_no_results').click()

    // Name cell
    cy.log('Name cell')
    cy.route("POST", "**/files/uploads/**").as("ajax21");
    page.name_for_row(1).trigger('blur')
    cy.wait("@ajax21");
    page.hasErrorsCount(1)
    page.name_for_row(1).parent().find('em.ee-form-error-message').should('exist').contains(page.messages.validation.required)

    cy.route("POST", "**/files/uploads/**").as("ajax22");
    page.name_for_row(1).clear().type('some_name')
    page.name_for_row(1).trigger('blur')
    cy.wait("@ajax22");
    page.hasNoErrors()
    page.hasNoGridErrors(page.name_for_row(1))
    page.name_for_row(1).parent().find('em.ee-form-error-message').should('not.exist')

    cy.route("POST", "**/files/uploads/**").as("ajax23");
    page.name_for_row(1).clear().type('some name')
    page.name_for_row(1).trigger('blur')
    cy.wait("@ajax23");
    page.hasErrorsCount(1)
    page.name_for_row(1).parent().find('em.ee-form-error-message').should('exist').contains(page.messages.validation.alpha_dash)

    cy.route("POST", "**/files/uploads/**").as("ajax24");
    page.name_for_row(1).clear().type('some_name')
    page.name_for_row(1).trigger('blur')
    cy.wait("@ajax24");
    page.hasNoErrors()
    page.hasNoGridErrors(page.name_for_row(1))
    page.name_for_row(1).parent().find('em.ee-form-error-message').should('not.exist')

    // Width cell
    cy.log('Width cell')
    cy.route("POST", "**/files/uploads/**").as("ajax25");
    page.width_for_row(1).trigger('blur')
    cy.wait("@ajax25");
    page.hasErrorsCount(1)
    page.width_for_row(1).parent().find('em.ee-form-error-message').should('exist').contains(dimension_error)

    // Not required when a watermark is selected
    cy.log('Not required when a watermark is selected')
    cy.route("POST", "**/files/uploads/**").as("ajax26");
    page.watermark_for_row(1).select('Test')
    page.width_for_row(1).trigger('blur')
    cy.wait("@ajax26");
    page.hasNoErrors()
    page.hasNoGridErrors(page.width_for_row(1))
    page.width_for_row(1).parent().find('em.ee-form-error-message').should('not.exist')

    cy.route("POST", "**/files/uploads/**").as("ajax27");
    page.watermark_for_row(1).select('No watermark')
    page.width_for_row(1).trigger('blur')
    cy.wait("@ajax27");
    page.hasErrorsCount(1)

    cy.route("POST", "**/files/uploads/**").as("ajax28");
    page.width_for_row(1).clear().type('4')
    page.width_for_row(1).trigger('blur')
    cy.wait("@ajax28");
    page.hasNoErrors()
    page.hasNoGridErrors(page.width_for_row(1))
    page.width_for_row(1).parent().find('em.ee-form-error-message').should('not.exist')

    cy.route("POST", "**/files/uploads/**").as("ajax29");
    page.width_for_row(1).clear().type('ssdfsdsd')
    page.width_for_row(1).trigger('blur')
    cy.wait("@ajax29");
    page.hasErrorsCount(1)
    page.width_for_row(1).parent().find('em.ee-form-error-message').should('exist').contains(page.messages.validation.natural_number)

    cy.route("POST", "**/files/uploads/**").as("ajax30");
    page.width_for_row(1).clear().type('2')
    page.width_for_row(1).trigger('blur')
    cy.wait("@ajax30");
    page.hasNoErrors()
    page.hasNoGridErrors(page.width_for_row(1))
    page.width_for_row(1).parent().find('em.ee-form-error-message').should('not.exist')

    // Height cell
    cy.log('Height cell')
    cy.route("POST", "**/files/uploads/**").as("ajax31");
    page.height_for_row(1).clear().type('ssdfsdsd')
    page.height_for_row(1).trigger('blur')
    cy.wait("@ajax31");
    page.hasErrorsCount(1)
    page.height_for_row(1).parent().find('em.ee-form-error-message').should('exist').contains(page.messages.validation.natural_number)

    cy.route("POST", "**/files/uploads/**").as("ajax32");
    page.height_for_row(1).clear().type('4')
    page.height_for_row(1).trigger('blur')
    cy.wait("@ajax32");
    page.hasNoErrors()
    page.hasNoGridErrors(page.height_for_row(1))
    page.height_for_row(1).parent().find('em.ee-form-error-message').should('not.exist')

    page.get('grid_add').click()
    page.get('grid_rows').should('have.length', 3)

    cy.route("POST", "**/files/uploads/**").as("ajax33");
    page.name_for_row(2).trigger('blur')
    cy.wait("@ajax33");
    page.hasErrorsCount(1)
    page.name_for_row(2).parent().find('em.ee-form-error-message').should('exist').contains(page.messages.validation.required)

    cy.route("POST", "**/files/uploads/**").as("ajax34");
    page.name_for_row(2).clear().type('some_name2')
    page.name_for_row(2).trigger('blur')
    cy.wait("@ajax34");
    page.hasNoErrors()
    page.hasNoGridErrors(page.name_for_row(2))
    page.name_for_row(2).parent().find('em.ee-form-error-message').should('not.exist')

    page.name_for_row(2).clear().type('some_name')
    cy.route("POST", "**/files/uploads/**").as("ajax35");
    page.name_for_row(2).trigger('blur')
    cy.wait("@ajax35");
    page.hasErrorsCount(1)
    page.name_for_row(2).parent().find('em.ee-form-error-message').should('exist').contains(page.messages.validation.unique)

    page.name_for_row(2).parent().should('have.class', 'invalid')

    cy.route("POST", "**/files/uploads/**").as("ajax36");
    page.name_for_row(2).clear().type('some_name2')
    page.name_for_row(2).trigger('blur')
    cy.wait("@ajax36");
    page.hasNoErrors()
    page.hasNoGridErrors(page.name_for_row(2))

    page.get('image_manipulations').find('td.invalid').should('not.exist')
    page.hasNoErrors()
  })

  it('should repopulate the form on validation error, and save', () => {
    page.get('url').clear().type('http://ee3/')
    page.get('server_path').clear().type(upload_path, {parseSpecialCharSequences: false})
    page.get('allowed_types').check('all')
    page.get('max_size').clear().type('4')
    page.get('max_width').clear().type('300')
    page.get('max_height').clear().type('200')

    page.get('grid_add_no_results').click()
    page.name_for_row(1).clear().type('some_name')
    page.width_for_row(1).clear().type('20')
    page.height_for_row(1).clear().type('30')

    page.get('grid_add').click()
    page.name_for_row(2).clear().type('some_other_name')
    page.resize_type_for_row(2).select('Crop (part of image)')
    page.width_for_row(2).clear().type('50')
    page.height_for_row(2).clear().type('40')

    // Uncheck Members
    page.get('upload_member_groups').first().uncheck()

    // Check both category groups
    page.get('cat_group').eq(0).click()
    page.get('cat_group').eq(1).click()

    // We've set everything but a name, submit the form to see error
    page.submit()
    cy.hasNoErrors()

    page.get('wrap').contains('Attention: Upload directory not saved')
    page.hasError(page.get('name'), page.messages.validation.required)
    page.hasErrors()

    page.get('server_path').invoke('val').then((text) => {
      expect(text).equal(upload_path)
    })

    //page.get('allowed_types').is('[value=all]').should('be.checked')
    page.get('wrap').find('input[type!=hidden][name=allowed_types][value=all]').should('be.checked')

    page.get('max_size').invoke('val').then((text) => {
      expect(text).equal('4')
    })
    page.get('max_width').invoke('val').then((text) => {
      expect(text).equal('300')
    })
    page.get('max_height').invoke('val').then((text) => {
      expect(text).equal('200')
    })

    page.name_for_row(1).invoke('val').then((text) => {
      expect(text).equal('some_name')
    })
    page.width_for_row(1).invoke('val').then((text) => {
      expect(text).equal('20')
    })
    page.height_for_row(1).invoke('val').then((text) => {
      expect(text).equal('30')
    })

    page.name_for_row(2).invoke('val').then((text) => {
      expect(text).equal('some_other_name')
    })
    page.width_for_row(2).invoke('val').then((text) => {
      expect(text).equal('50')
    })
    page.height_for_row(2).invoke('val').then((text) => {
      expect(text).equal('40')
    })

    page.get('upload_member_groups').first().should('not.be.checked')
    page.get('cat_group').eq(0).should('be.checked')
    page.get('cat_group').eq(1).should('be.checked')

    // Fix error and make sure everything submitted ok
    cy.route("POST", "**/files/uploads/**").as("ajax37");
    page.get('name').clear().type('Dir1')
    page.get('name').trigger('blur')
    cy.wait("@ajax37");
    page.hasNoErrors()
    page.submit()

    page.get('wrap').contains('Upload directory saved')

    page.get('name').invoke('val').then((text) => {
      expect(text).equal('Dir1')
    })
    page.get('server_path').invoke('val').then((text) => {
      expect(text).equal(upload_path + '/')
    })
    //page.get('allowed_types').is('[value=all]').should('be.checked')
    page.get('wrap').find('input[type!=hidden][name=allowed_types][value=all]').should('be.checked')
    page.get('max_size').invoke('val').then((text) => {
      expect(text).equal('4')
    })
    page.get('max_width').invoke('val').then((text) => {
      expect(text).equal('300')
    })
    page.get('max_height').invoke('val').then((text) => {
      expect(text).equal('200')
    })

    page.name_for_row(1).invoke('val').then((text) => {
      expect(text).equal('some_name')
    })
    page.resize_type_for_row(1).invoke('val').then((text) => {
      expect(text).equal('constrain')
    })
    page.width_for_row(1).invoke('val').then((text) => {
      expect(text).equal('20')
    })
    page.height_for_row(1).invoke('val').then((text) => {
      expect(text).equal('30')
    })

    page.name_for_row(2).invoke('val').then((text) => {
      expect(text).equal('some_other_name')
    })
    page.resize_type_for_row(2).invoke('val').then((text) => {
      expect(text).equal('crop')
    })
    page.width_for_row(2).invoke('val').then((text) => {
      expect(text).equal('50')
    })
    page.height_for_row(2).invoke('val').then((text) => {
      expect(text).equal('40')
    })

    page.get('upload_member_groups').first().should('not.be.checked')
    page.get('cat_group').eq(0).should('be.checked')
    page.get('cat_group').eq(1).should('be.checked')

  })

  it('should save a new upload directory', () => {
    page.get('name').clear().type('Dir 2')
    page.get('url').clear().type('http://ee3/')
    page.get('server_path').clear().type(upload_path, {parseSpecialCharSequences: false})
    page.get('max_size').clear().type('4')
    page.get('max_width').clear().type('300')
    page.get('max_height').clear().type('200')

    page.get('grid_add_no_results').click()
    page.name_for_row(1).clear().type('some_name')
    page.width_for_row(1).clear().type('20')
    page.height_for_row(1).clear().type('30')

    page.get('grid_add').click()
    page.name_for_row(2).clear().type('some_other_name')
    page.resize_type_for_row(2).select('Crop (part of image)')
    page.width_for_row(2).clear().type('50')
    page.height_for_row(2).clear().type('40')

    // Uncheck Members
    page.get('upload_member_groups').first().uncheck()

    // Check both category groups
    page.get('cat_group').eq(0).click()
    page.get('cat_group').eq(1).click()

    page.submit()
    cy.hasNoErrors()

    page.get('wrap').contains('Upload directory saved')

    page.get('name').invoke('val').then((text) => {
      expect(text).equal('Dir 2')
    })
    page.get('server_path').invoke('val').then((text) => {
      expect(text).equal(upload_path + '/')
    })
    //page.get('allowed_types').is('[value=img]').should('be.checked')
    page.get('wrap').find('input[type!=hidden][name=allowed_types][value=img]').should('be.checked')
    page.get('max_size').invoke('val').then((text) => {
      expect(text).equal('4')
    })
    page.get('max_width').invoke('val').then((text) => {
      expect(text).equal('300')
    })
    page.get('max_height').invoke('val').then((text) => {
      expect(text).equal('200')
    })

    page.name_for_row(1).invoke('val').then((text) => {
      expect(text).equal('some_name')
    })
    page.resize_type_for_row(1).invoke('val').then((text) => {
      expect(text).equal('constrain')
    })
    page.width_for_row(1).invoke('val').then((text) => {
      expect(text).equal('20')
    })
    page.height_for_row(1).invoke('val').then((text) => {
      expect(text).equal('30')
    })

    page.name_for_row(2).invoke('val').then((text) => {
      expect(text).equal('some_other_name')
    })
    page.resize_type_for_row(2).invoke('val').then((text) => {
      expect(text).equal('crop')
    })
    page.width_for_row(2).invoke('val').then((text) => {
      expect(text).equal('50')
    })
    page.height_for_row(2).invoke('val').then((text) => {
      expect(text).equal('40')
    })

    page.get('upload_member_groups').first().should('not.be.checked')
    page.get('cat_group').eq(0).should('be.checked')
    page.get('cat_group').eq(1).should('be.checked')

    // Make sure we can edit with no validation issues
    page.submit()
    page.get('wrap').contains('Upload directory saved')
    cy.hasNoErrors()

    // Test adding a new image manipulation to an existing directory
    // and that unique name validation works
    page.get('grid_add').click()

    cy.route("POST", "**/files/uploads/**").as("ajax38");
    page.name_for_row(3).clear().type('some_name')
    page.name_for_row(3).trigger('blur')
    cy.wait("@ajax38");
    page.hasErrorsCount(1)
    page.name_for_row(3).parent().find('em.ee-form-error-message').should('exist').contains(page.messages.validation.unique)

    page.name_for_row(3).clear().type('some_name2')
    page.name_for_row(3).trigger('blur')
    page.hasNoErrors()
    page.hasNoGridErrors(page.name_for_row(3))

    page.width_for_row(3).clear().type('60')
    page.height_for_row(3).clear().type('70')

    page.submit()
    page.get('wrap').contains('Upload directory saved')
    cy.hasNoErrors()

    page.name_for_row(3).invoke('val').then((text) => {
      expect(text).equal('some_name2')
    })
    page.resize_type_for_row(3).invoke('val').then((text) => {
      expect(text).equal('constrain')
    })
    page.width_for_row(3).invoke('val').then((text) => {
      expect(text).equal('60')
    })
    page.height_for_row(3).invoke('val').then((text) => {
      expect(text).equal('70')
    })

    // Test row deletion
    page.delete_for_row(2).click()
    page.get('grid_rows').should('have.length', 3) // Header and two rows

    page.submit()
    page.get('wrap').contains('Upload directory saved')
    cy.hasNoErrors()

    page.get('grid_rows').should('have.length', 3) // Header and two rows

    page.name_for_row(1).invoke('val').then((text) => {
      expect(text).equal('some_name')
    })
    page.name_for_row(2).invoke('val').then((text) => {
      expect(text).equal('some_name2')
    })
  })

  it('should edit an existing upload directory', () => {
    page.load_edit_for_dir(1)
    cy.hasNoErrors()

    page.get('name').clear().type('New name upload dir')
    page.get('server_path').clear().type(upload_path, {parseSpecialCharSequences: false}) // Set a path that works for the environment
    page.submit()

    page.get('wrap').contains('Upload directory saved')
    // page.get('name').invoke('val').then((val) => { expect(val).to.be.equal('New name upload dir'
  })

  it('should reject XSS', () => {

    page.get('name').clear().type(page.messages.xss_vector)
    cy.route("POST", "**/files/uploads/**").as("ajax39");
    page.get('name').trigger('blur')
    cy.wait("@ajax39");
    page.hasErrorsCount(1)
    page.hasError(page.get('name'), page.messages.validation.xss)
    page.hasErrors()
  })



  context("Bug #21157 - File Size Between 0 and 1", () => {
	  it('should allow a file size of .1', () => {

      page.get('max_size').clear().type('.1')
      cy.route("POST", "**/files/uploads/**").as("ajax40");
      page.get('max_size').trigger('blur')
      cy.wait("@ajax40");
      page.hasNoError(page.get('max_size'))
      page.hasNoErrors()
	  })

	  it('should not allow a file size of 0', () => {

      page.get('max_size').clear().type('0')
      cy.route("POST", "**/files/uploads/**").as("ajax41");
      page.get('max_size').trigger('blur')
      cy.wait("@ajax41");
      page.hasErrorsCount(1)
      page.hasError(page.get('max_size'), page.messages.validation.greater_than)
      page.hasErrors()
	  })

	  it('should not allow a file size of -.1', () => {

      page.get('max_size').clear().type('-1')
      cy.route("POST", "**/files/uploads/**").as("ajax42");
      page.get('max_size').trigger('blur')
      cy.wait("@ajax42");
      page.hasErrorsCount(1)
      page.hasError(page.get('max_size'), page.messages.validation.greater_than)
      page.hasErrors()
	  })
  })

})
