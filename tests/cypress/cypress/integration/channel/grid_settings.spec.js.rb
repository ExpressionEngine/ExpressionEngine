/// <reference types="Cypress" />

import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';
const page = new ChannelFieldForm;
const { _, $ } = Cypress

context('Grid Field Settings', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

#
// This tests the various form interactions with Grid to make
// sure settings are saved and loaded properly, as well as form
// validations fail and preserve existing data
#
context('Grid Field Settings', () => {

  // Before each test, take us to the Field Group settings page
  // and start creating a new Grid field
  beforeEach(function() {
    cy.auth();
    page = ChannelFieldForm.new
    page.load()
    cy.hasNoErrors()

    page.field_label.clear().type('Test Grid'

    page.select_field_type 'Grid'
  }

  it('shows the Grid field settings', () => {
    page.field_name.invoke('val').then((val) => { expect(val).to.be.equal('test_grid'
    page.should have_text('Grid Fields')
  }

  it('should autopopulate the column name', () => {
    column = GridSettings::column(1)
    column.label.clear().type('Test Column'
    column.name.invoke('val').then((val) => { expect(val).to.be.equal('test_column'

    page.submit
    cy.hasNoErrors()
    page.load_edit_for_custom_field('Test Grid')

    // Column label shouldn't update automatically on existing columns
    column = GridSettings::column(1)
    column.label.clear().type('News column label'
    column.name.invoke('val').then((val) => { expect(val).to.be.equal('test_column'

    // Ensure column name generation works in new and cloned columns
    GridSettings::add_column
    column2 = GridSettings::column(2)
    column2.label.clear().type('New column'
    column2.name.invoke('val').then((val) => { expect(val).to.be.equal('new_column'

    column2 = GridSettings::clone_column(1)
    column2.label.clear().type('New column 2'
    column2.name.invoke('val').then((val) => { expect(val).to.be.equal('new_column_2'
  }

  it('should validate column names and labels', () => {
    // No column label
    column = GridSettings::column(1)
    column.name.clear().type('test_column'
    cy.hasNoErrors()
    page.submit
    column = GridSettings::column(1)
    page.hasError(column.label, $required_error)
    cy.hasNoErrors()

    // No column label and duplicate column label
    column = GridSettings::add_column
    column.label.clear().type('Test column'
    column.name.invoke('val').then((val) => { expect(val).to.be.equal('test_column'
    column.name.click()
    column.label.click() // Blur, .trigger('blur') isn't working
    page.hasErrorsCount(2)
    page.hasError(column.name, 'Column field names must be unique.')

    // No column name, duplicate column label, and no column name
    column = GridSettings::add_column
    column.label.clear().type('Test column no name'
    column.name.clear()
    column.name.click()
    column.label.click()
    page.hasErrorsCount(3)
    page.hasError(column.name, $required_error)
  }

  it('should only duplicate columns once', () => {
    column1 = GridSettings::column(1)
    column1.name.clear().type('test_column'
    column2 = GridSettings::clone_column(1)
    column3 = GridSettings::clone_column(2)
    lambda { GridSettings::column(4) }.should raise_error(Capybara::ElementNotFound)
  }

  it('should save column settings', () => {
    GridSettings::populate_grid_settings
    cy.hasNoErrors()

    // Save!
    page.submit
    cy.hasNoErrors()
    page.load_edit_for_custom_field('Test Grid')
    cy.hasNoErrors()

    grid_test_data = GridSettings::test_data

    // Validate each column to make sure they retained data
    grid_test_data.each_with_index do |column_data, index|
      column = GridSettings::column(index + 1)
      column.validate(column_data[1])
    }
  }

  it('should fail validation and retain data', () => {
    GridSettings::populate_grid_settings

    // Sabbotage a column to make sure data is retained on validation error
    column = GridSettings::column(1)
    column.name.clear()
    page.submit
    column = GridSettings::column(1)
    page.hasError(column.name, $required_error)
    cy.hasNoErrors()

    // Put back the column name for validation
    column = GridSettings::column(1)
    column.name.clear().type('date'

    grid_test_data = GridSettings::test_data

    // Validate each column to make sure they retained data
    grid_test_data.each_with_index do |column_data, index|
      column = GridSettings::column(index + 1)
      column.validate(column_data[1])
    }
  }

  it('should delete a column', () => {
    GridSettings::populate_grid_settings

    page.submit
    cy.hasNoErrors()
    page.load_edit_for_custom_field('Test Grid')
    cy.hasNoErrors()

    // Delete a column, make sure it's gone
    column = GridSettings::column(1)
    column.delete
    cy.hasNoErrors()
    page.submit
    cy.hasNoErrors()
    page.load_edit_for_custom_field('Test Grid')
    cy.hasNoErrors()

    grid_test_data = GridSettings::test_data

    // Validate each column to make sure they retained data
    grid_test_data.each_with_index do |column_data, index|
      if index == 0 then
        next
      }
      column = GridSettings::column(index)
      column.validate(column_data[1])
    }
  }
}
