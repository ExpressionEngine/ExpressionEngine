require './bootstrap.rb'

#
# This tests the various form interactions with Grid to make
# sure settings are saved and loaded properly, as well as form
# validations fail and preserve existing data
#
feature 'Grid Field Settings' do

	# Before each test, take us to the Field Group settings page
	# and start creating a new Grid field
	before(:each) do
		cp_session
    	@page = ChannelFieldForm.new
    	@page.load
    	no_php_js_errors

    	@page.field_label.set 'Test Grid'

		@page.field_type.select 'Grid'
	end

	def populate_grid_settings
		GridSettings::test_data.each_with_index do |column_data, index|
			# First column is already there, so call add_column for
			# subsequent columns after index 0
			column = index == 0 ? GridSettings::column(1) : GridSettings::add_column
			column.fill_data(column_data[1])
		end

		no_php_js_errors
	end

	it 'shows the Grid field settings' do
		@page.field_name.value.should eq 'test_grid'
		@page.should have_text('Grid Fields')
	end

	it 'should autopopulate the column name' do
		column = GridSettings::column(1)
		column.label.set 'Test Column'
		column.name.value.should eq 'test_column'

		@page.submit
		no_php_js_errors
		@page.load_edit_for_custom_field('Test Grid')

		# Column label shouldn't update automatically on existing columns
		column = GridSettings::column(1)
		column.label.set 'News column label'
		column.name.value.should eq 'test_column'

		# Ensure column name generation works in new and cloned columns
		GridSettings::add_column
		column2 = GridSettings::column(2)
		column2.label.set 'New column'
		column2.name.value.should eq 'new_column'

		column2 = GridSettings::clone_column(1)
		column2.label.set 'New column 2'
		column2.name.value.should eq 'new_column_2'
	end

#	it 'should validate column names and labels' do
#		# No column label
#		column = GridSettings::column(1)
#		column.name.set 'test_column'
#		no_php_js_errors
#		click_button 'Submit'
#		page.should have_text('There are one or more columns without a column label.')
#		no_php_js_errors
#
#		# No column label and duplicate column label
#		column = GridSettings::add_column
#		column.label.set 'Test column'
#		column.name.value.should eq 'test_column'
#		click_button 'Submit'
#		page.should have_text('There are one or more columns without a column label.')
#		page.should have_text('Column field names must be unique.')
#
#		# No column name, duplicate column label, and no column name
#		column = GridSettings::add_column
#		column.label.set 'Test column no name'
#		column.name.set ''
#		click_button 'Submit'
#		page.should have_text('There are one or more columns without a column label.')
#		page.should have_text('Column field names must be unique.')
#		page.should have_text('There are one or more columns without a column name.')
#	end

	it 'should save column settings' do
		populate_grid_settings
		no_php_js_errors

		# Save!
		@page.submit
		no_php_js_errors
		@page.load_edit_for_custom_field('Test Grid')
		no_php_js_errors

		grid_test_data = GridSettings::test_data

		# Validate each column to make sure they retained data
		grid_test_data.each_with_index do |column_data, index|
			column = GridSettings::column(index + 1)
			column.validate(column_data[1])
		end
	end

#	it 'should fail validation and retain data' do
#		populate_grid_settings
#
#		# Sabbotage a column to make sure data is retained on validation error
#		column = GridSettings::column(1)
#		column.name.set ''
#		click_button 'Submit'
#		no_php_js_errors
#		page.should have_text('There are one or more columns without a column name.')
#
#		# Put back the column name for validation
#		column = GridSettings::column(1)
#		column.name.set 'date'
#
#		grid_test_data = GridSettings::test_data
#
#		# Validate each column to make sure they retained data
#		grid_test_data.each_with_index do |column_data, index|
#			column = GridSettings::column(index + 1)
#			column.validate(column_data[1])
#		end
#	end

	it 'should delete a column' do
		populate_grid_settings

		@page.submit
		no_php_js_errors
		@page.load_edit_for_custom_field('Test Grid')
		no_php_js_errors

		# Delete a column, make sure it's gone
		column = GridSettings::column(1)
		column.delete
		no_php_js_errors
		@page.submit
		no_php_js_errors
		@page.load_edit_for_custom_field('Test Grid')
		no_php_js_errors

		grid_test_data = GridSettings::test_data

		# Validate each column to make sure they retained data
		grid_test_data.each_with_index do |column_data, index|
			if index == 0 then
				next
			end
			column = GridSettings::column(index)
			column.validate(column_data[1])
		end
	end
end
