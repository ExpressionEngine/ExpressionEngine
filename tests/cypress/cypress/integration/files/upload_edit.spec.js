/// <reference types="Cypress" />

import UploadEdit from '../../elements/pages/files/UploadEdit';
const page = new UploadEdit;

context('Upload Destination Create/Edit', () => {


  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()

    const upload_path = '../../images'
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
    page.contains('Attention: Upload directory not saved')
    page.hasError(page.get('name'), page.messages.validation.required_error)
    page.hasError(page.get('url'), url_error)
    page.hasError(page.get('server_path'), page.messages.validation.required_error)

    // AJAX validation
    // Required name
    page.load()
    page.get('name').trigger('blur')
    page.wait_for_error_message_count(1)
    page.hasError(page.get('name'), page.messages.validation.required_error)
    page.hasErrors()

    page.get('name').clear().type('Dir')
    page.get('name').trigger('blur')
    page.wait_for_error_message_count(0)
    page.hasNoError(page.get('name'))
    page.hasNoErrors()

    // Duplicate directory name
    page.get('name').clear().type('Main Upload Directory')
    page.get('name').trigger('blur')
    page.wait_for_error_message_count(1)
    page.hasError(page.get('name'), page.messages.validation.unique)
    page.hasErrors()

    // Multiple errors for URL
    // Error when just submitting "http://"
    page.get('url').trigger('blur')
    page.wait_for_error_message_count(2)
    page.hasError(page.get('url'), url_error)
    page.hasErrors()

    // Resolve that error
    page.get('url').clear().type('http://ee3/')
    page.get('url').trigger('blur')
    page.wait_for_error_message_count(1)
    page.hasNoError(page.get('url'))
    page.hasErrors()

    // Error when left blank
    page.get('url').clear()
    page.get('url').trigger('blur')
    page.wait_for_error_message_count(2)
    page.hasError(page.get('url'), page.messages.validation.required_error)
    page.hasErrors()

    // Server path errors, path must both exist and be writable
    // Required:
    page.get('server_path').trigger('blur')
    page.wait_for_error_message_count(3)
    page.hasError(page.get('server_path'), page.messages.validation.required_error)
    page.hasErrors()

    // Resolve so can break again:
    page.get('server_path').clear().type(upload_path)
    page.get('server_path').trigger('blur')
    page.wait_for_error_message_count(2)
    page.hasNoError(page.get('server_path'))
    page.hasErrors()

    // Invalid path:
    page.get('server_path').clear().type('sdfsdf')
    page.get('server_path').trigger('blur')
    page.wait_for_error_message_count(3)
    page.hasError(page.get('server_path'), page.messages.validation.invalid_path)
    page.hasErrors()

    // Resolve so can break again:
    page.get('server_path').clear().type(upload_path)
    page.get('server_path').trigger('blur')
    page.wait_for_error_message_count(2)
    page.hasNoError(page.get('server_path'))
    page.hasErrors()

    // Not writable path:
    page.get('server_path').clear().type('/')
    page.get('server_path').trigger('blur')
    page.wait_for_error_message_count(3)
    page.hasError(page.get('server_path'), page.messages.validation.not_writable)
    page.hasErrors()

    page.get('max_size').clear().type('sdf')
    page.get('max_size').trigger('blur')
    page.wait_for_error_message_count(4)
    page.hasError(page.get('max_size'), page.messages.validation.numeric)
    page.hasErrors()

    page.get('max_width').clear().type('sdf')
    page.get('max_width').trigger('blur')
    page.wait_for_error_message_count(5)
    page.hasError(page.get('max_width'), page.messages.validation.natural_number)
    page.hasErrors()

    page.get('max_height').clear().type('sdf')
    page.get('max_height').trigger('blur')
    page.wait_for_error_message_count(6)
    page.hasError(page.get('max_height'), page.messages.validation.natural_number)
    page.hasErrors()

    // These fields should not be required
    page.get('max_size').clear()
    page.get('max_size').trigger('blur')
    page.wait_for_error_message_count(5)
    page.hasNoError(page.get('max_size'))

    page.get('max_width').clear()
    page.get('max_width').trigger('blur')
    page.wait_for_error_message_count(4)
    page.hasNoError(page.get('max_width'))

    page.get('max_height').clear()
    page.get('max_height').trigger('blur')
    page.wait_for_error_message_count(3)
    page.hasNoError(page.get('max_height'))
    page.hasErrors()

    // Fix rest of fields
    page.get('name').clear().type('Dir')
    page.get('name').trigger('blur')
    page.wait_for_error_message_count(2)
    page.hasNoError(page.get('name'))
    page.hasErrors()

    page.get('url').clear().type('http://ee3/')
    page.get('url').trigger('blur')
    page.wait_for_error_message_count(1)
    page.hasNoError(page.get('url'))
    page.hasErrors()

    page.get('server_path').clear().type(upload_path)
    page.get('server_path').trigger('blur')
    page.wait_for_error_message_count(0)
    page.hasNoError(page.get('server_path'))
    page.hasNoErrors()

    // Lots of AJAX going on, make sure there are no JS errors
    cy.hasNoErrors()

    page.submit()
    cy.hasNoErrors()
  })
/*
  it('should validate image manipulation data', () => {
    watermark = WatermarkEdit.new
    watermark.load
    watermark.wm_name.set 'Test'
    watermark.submit

    page.load()
    page.should have_text 'No manipulations created'
    page.should have_grid_add_no_results
    page.should have_no_grid_add

    // Should add row
    page.grid_add_no_results.click()
    page.should have_no_text 'No manipulations created'
    page.should have_no_grid_add_no_results
    page.should have_grid_add
    page.grid_rows.size.should == 2 // Includes header

    // Make sure watermarks are available
    within page.watermark_for_row(1) do
        all('option').map(&:value).should == ['0','1']
    }

    // Should remove row and show "no manipulations" message
    page.delete_for_row(1).click()
    page.should have_grid_add_no_results
    page.should have_no_grid_add
    page.grid_rows.size.should == 2 // Header and no results row

    page.grid_add_no_results.click()

    page.get('name').set 'Dir'
    page.get('url').set 'http://ee3/'
    page.get('server_path').clear().type(upload_path)
    page.submit()

    dimension_error = 'A height or width must be entered if no watermark is selected.'

    page.hasErrors()
    page.error_messages.size.should == 3
    grid_should_have_error(page.image_manipulations)
    grid_cell_page.hasError(page.get('name')_for_row(1), $required_error)
    grid_cell_page.hasError(page.width_for_row(1), dimension_error)
    grid_cell_page.hasError(page.height_for_row(1), dimension_error)
    cy.hasNoErrors()

    // Reset for AJAX validation
    page.load()
    page.grid_add_no_results.click()

    // Name cell
    name_cell = page.get('name')_for_row(1)
    name_cell.trigger('blur')
    page.wait_for_error_message_count(1)
    grid_cell_page.hasError(name_cell, $required_error)

    name_cell.set 'some_name'
    name_cell.trigger('blur')
    page.wait_for_error_message_count(0)
    grid_cell_page.hasNoError(name_cell)

    name_cell.set 'some name'
    name_cell.trigger('blur')
    page.wait_for_error_message_count(1)
    grid_cell_page.hasError(name_cell, $alpha_dash)

    name_cell.set 'some_name'
    name_cell.trigger('blur')
    page.wait_for_error_message_count(0)
    grid_cell_page.hasNoError(name_cell)

    // Width cell
    width_cell = page.width_for_row(1)
    width_cell.trigger('blur')
    page.wait_for_error_message_count(1)
    grid_cell_page.hasError(width_cell, dimension_error)

    // Not required when a watermark is selected
    page.watermark_for_row(1).select('Test')
    width_cell.trigger('blur')
    page.wait_for_error_message_count(0)
    grid_cell_page.hasNoError(width_cell)

    page.watermark_for_row(1).select('No watermark')
    width_cell.trigger('blur')
    page.wait_for_error_message_count(1)

    width_cell.set '4'
    width_cell.trigger('blur')
    page.wait_for_error_message_count(0)
    grid_cell_page.hasNoError(width_cell)

    width_cell.set 'ssdfsdsd'
    width_cell.trigger('blur')
    page.wait_for_error_message_count(1)
    grid_cell_page.hasError(width_cell, $natural_number)

    width_cell.set '2'
    width_cell.trigger('blur')
    page.wait_for_error_message_count(0)
    grid_cell_page.hasNoError(width_cell)

    // Height cell
    height_cell = page.height_for_row(1)
    height_cell.set 'ssdfsdsd'
    height_cell.trigger('blur')
    page.wait_for_error_message_count(1)
    grid_cell_page.hasError(height_cell, $natural_number)

    height_cell.set '4'
    height_cell.trigger('blur')
    page.wait_for_error_message_count(0)
    grid_cell_page.hasNoError(height_cell)

    page.grid_add.click()
    page.grid_rows.size.should == 3

    name_cell = page.get('name')_for_row(2)
    name_cell.trigger('blur')
    page.wait_for_error_message_count(1)
    grid_cell_page.hasError(name_cell, $required_error)

    name_cell.set 'some_name2'
    name_cell.trigger('blur')
    page.wait_for_error_message_count(0)
    grid_cell_page.hasNoError(name_cell)

    name_cell.set 'some_name'
    name_cell.trigger('blur')
    page.wait_for_error_message_count(1)
    grid_cell_page.hasError(name_cell, $unique)

    grid_should_have_error(name_cell)

    name_cell.set 'some_name2'
    name_cell.trigger('blur')
    page.wait_for_error_message_count(0)
    grid_cell_page.hasNoError(name_cell)

    grid_should_have_no_error(page.image_manipulations)
    page.hasNoErrors()
  })

  it('should repopulate the form on validation error, and save', () => {
    page.get('url').set 'http://ee3/'
    page.get('server_path').clear().type(upload_path)
    page.allowed_types.choose_radio_option 'all'
    page.get('max_size').set '4'
    page.get('max_width').set '300'
    page.get('max_height').set '200'

    page.grid_add_no_results.click()
    page.get('name')_for_row(1).set 'some_name'
    page.width_for_row(1).set '20'
    page.height_for_row(1).set '30'

    page.grid_add.click()
    page.get('name')_for_row(2).set 'some_other_name'
    page.resize_type_for_row(2).select 'Crop (part of image)'
    page.width_for_row(2).set '50'
    page.height_for_row(2).set '40'

    // Uncheck Members
    page.upload_member_groups[0].set false

    // Check both category groups
    page.cat_group[0].click()
    page.cat_group[1].click()

    // We've set everything but a name, submit the form to see error
    page.submit()
    cy.hasNoErrors()

    page.should have_text 'Attention: Upload directory not saved'
    page.hasError(page.get('name'), $required_error)
    page.hasErrors()

    page.get('server_path').value.should == @upload_path
    page.allowed_types.has_checked_radio('all').should == true
    page.get('max_size').value.should == '4'
    page.get('max_width').value.should == '300'
    page.get('max_height').value.should == '200'

    page.get('name')_for_row(1).value.should == 'some_name'
    page.width_for_row(1).value.should == '20'
    page.height_for_row(1).value.should == '30'

    page.get('name')_for_row(2).value.should == 'some_other_name'
    page.width_for_row(2).value.should == '50'
    page.height_for_row(2).value.should == '40'

    page.upload_member_groups[0].checked?.should == false
    page.cat_group[0].checked?.should == true
    page.cat_group[1].checked?.should == true

    // Fix error and make sure everything submitted ok
    page.get('name').set 'Dir'
    page.get('name').trigger('blur')
    page.wait_for_error_message_count(0)
    page.submit()

    page.should have_text 'Upload directory saved'

    page.get('name').value.should == 'Dir'
    page.get('server_path').value.should == @upload_path + '/'
    page.allowed_types.has_checked_radio('all').should == true
    page.get('max_size').value.should == '4'
    page.get('max_width').value.should == '300'
    page.get('max_height').value.should == '200'

    page.get('name')_for_row(1).value.should == 'some_name'
    page.resize_type_for_row(1).value.should == 'constrain'
    page.width_for_row(1).value.should == '20'
    page.height_for_row(1).value.should == '30'

    page.get('name')_for_row(2).value.should == 'some_other_name'
    page.resize_type_for_row(2).value.should == 'crop'
    page.width_for_row(2).value.should == '50'
    page.height_for_row(2).value.should == '40'

    page.upload_member_groups[0].checked?.should == false
    page.cat_group[0].checked?.should == true
    page.cat_group[1].checked?.should == true
  })

  it('should save a new upload directory', () => {
    page.get('name').set 'Dir'
    page.get('url').set 'http://ee3/'
    page.get('server_path').clear().type(upload_path)
    page.get('max_size').set '4'
    page.get('max_width').set '300'
    page.get('max_height').set '200'

    page.grid_add_no_results.click()
    page.get('name')_for_row(1).set 'some_name'
    page.width_for_row(1).set '20'
    page.height_for_row(1).set '30'

    page.grid_add.click()
    page.get('name')_for_row(2).set 'some_other_name'
    page.resize_type_for_row(2).select 'Crop (part of image)'
    page.width_for_row(2).set '50'
    page.height_for_row(2).set '40'

    // Uncheck Members
    page.upload_member_groups[0].set false

    // Check both category groups
    page.cat_group[0].click()
    page.cat_group[1].click()

    page.submit()
    cy.hasNoErrors()

    page.should have_text 'Upload directory saved'

    page.get('name').value.should == 'Dir'
    page.get('server_path').value.should == @upload_path + '/'
    page.allowed_types.has_checked_radio('img').should == true
    page.get('max_size').value.should == '4'
    page.get('max_width').value.should == '300'
    page.get('max_height').value.should == '200'

    page.get('name')_for_row(1).value.should == 'some_name'
    page.resize_type_for_row(1).value.should == 'constrain'
    page.width_for_row(1).value.should == '20'
    page.height_for_row(1).value.should == '30'

    page.get('name')_for_row(2).value.should == 'some_other_name'
    page.resize_type_for_row(2).value.should == 'crop'
    page.width_for_row(2).value.should == '50'
    page.height_for_row(2).value.should == '40'

    page.upload_member_groups[0].checked?.should == false
    page.cat_group[0].checked?.should == true
    page.cat_group[1].checked?.should == true

    // Make sure we can edit with no validation issues
    page.submit()
    page.should have_text 'Upload directory saved'
    cy.hasNoErrors()

    // Test adding a new image manipulation to an existing directory
    // and that unique name validation works
    page.grid_add.click()
    name_cell = page.get('name')_for_row(3)
    name_cell.set 'some_name'
    name_cell.trigger('blur')
    page.wait_for_error_message_count(1)
    grid_cell_page.hasError(name_cell, $unique)

    name_cell.set 'some_name2'
    name_cell.trigger('blur')
    page.wait_for_error_message_count(0)
    grid_cell_page.hasNoError(name_cell)

    page.width_for_row(3).set '60'
    page.height_for_row(3).set '70'

    page.submit()
    page.should have_text 'Upload directory saved'
    cy.hasNoErrors()

    page.get('name')_for_row(3).value.should == 'some_name2'
    page.resize_type_for_row(3).value.should == 'constrain'
    page.width_for_row(3).value.should == '60'
    page.height_for_row(3).value.should == '70'

    // Test row deletion
    page.delete_for_row(2).click()
    page.grid_rows.size.should == 3 // Header and two rows

    page.submit()
    page.should have_text 'Upload directory saved'
    cy.hasNoErrors()

    page.grid_rows.size.should == 3 // Header and two rows

    page.get('name')_for_row(1).value.should == 'some_name'
    page.get('name')_for_row(2).value.should == 'some_name2'
  })

  it('should edit an existing upload directory', () => {
    page.load_edit_for_dir(1)
    cy.hasNoErrors()

    page.get('name').set 'New name upload dir'
    page.get('server_path').clear().type(upload_path) // Set a path that works for the environment
    page.submit()

    page.should have_text 'Upload directory saved'
    // page.get('name').value.should == 'New name upload dir'
  })

  it('should reject XSS', () => {
    page.get('name').set $xss_vector
    page.get('name').trigger('blur')
    page.wait_for_error_message_count(1)
    page.hasError(page.get('name'), $xss_error)
    page.hasErrors()
  })



  context("Bug #21157 - File Size Between 0 and 1", () => {
	  it('should allow a file size of .1', () => {
	      page.get('max_size').set '.1'
	      page.get('max_size').trigger('blur')
	      page.wait_for_error_message_count(0)
	      page.hasNoError(page.get('max_size'))
	      page.hasNoErrors()
	  })

	  it('should not allow a file size of 0', () => {
	      page.get('max_size').set '0'
	      page.get('max_size').trigger('blur')
	      page.wait_for_error_message_count(1)
	      page.hasError(page.get('max_size'), $greater_than)
	      page.hasErrors()
	  })

	  it('should not allow a file size of -.1', () => {
	      page.get('max_size').set '-1'
	      page.get('max_size').trigger('blur')
	      page.wait_for_error_message_count(1)
	      page.hasError(page.get('max_size'), $greater_than)
	      page.hasErrors()
	  })
  })*/

})
