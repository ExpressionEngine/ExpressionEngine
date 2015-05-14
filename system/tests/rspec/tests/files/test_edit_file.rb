require './bootstrap.rb'

feature 'File Manger / Edit File' do

	before(:each) do
		cp_session
		@page = EditFile.new
		@return = FileManager.new
		@page.load
		no_php_js_errors

		@page.displayed?

		# Check that the heder data is intact
		@page.title.text.should eq 'File Manager'
		@page.should have_title_toolbar
		@page.should have_download_all
		@page.should have_phrase_search
		@page.should have_search_submit_button

		# Check that we have a sidebar
		@page.should have_sidebar
		@page.upload_directories_header.text.should include 'Upload Directories'
		@page.should have_new_directory_button
		@page.watermarks_header.text.should include 'Watermarks'
		@page.should have_new_watermark_button

		@page.should have_breadcrumb
		@page.breadcrumb.text.should include "File Manager"
		@page.breadcrumb.text.should include "Meta Data"
		@page.heading.text.should include "Meta Data"
		@page.should have_crop_button
		@page.should have_title_input
		@page.should have_description_input
		@page.should have_credit_input
		@page.should have_location_input
		@page.should have_form_submit_button
	end

	after(:each) do
		system('git checkout -- ../../../themes/ee/site_themes/agile_records/images/uploads/')
		system('chmod -R 777 ../../../themes/ee/site_themes/agile_records/images/uploads')
	end

	it 'shows the Edit Meta Data form' do
		@page.breadcrumb.text.should include @page.title_input.value
		@page.heading.text.should include @page.title_input.value
	end

	it 'can edit the title' do
		@page.title_input.set "Rspec was here"
		@page.form_submit_button.click
		no_php_js_errors

		@return.displayed?
		@return.alert.text.should include "The meta data for the file Rspec was here has been updated."
	end

	it 'can edit the description' do
		@page.description_input.set "Rspec was here"
		@page.form_submit_button.click
		no_php_js_errors

		@return.displayed?
		@return.alert.text.should include "The meta data for the file"
		@return.alert.text.should include "has been updated."
	end

	it 'can edit the credit' do
		@page.credit_input.set "Rspec was here"
		@page.form_submit_button.click
		no_php_js_errors

		@return.displayed?
		@return.alert.text.should include "The meta data for the file"
		@return.alert.text.should include "has been updated."
	end

	it 'can edit the location' do
		@page.location_input.set "Rspec was here"
		@page.form_submit_button.click
		no_php_js_errors

		@return.displayed?
		@return.alert.text.should include "The meta data for the file"
		@return.alert.text.should include "has been updated."
	end

	it 'can search files' do
		@page.phrase_search.set 'map'
		@page.search_submit_button.click
		no_php_js_errors

		@return.displayed?
		@return.heading.text.should eq 'Search Results we found 2 results for "map"'
		@return.phrase_search.value.should eq 'map'
		@return.should have_text 'map'
		@return.should have(3).files
	end

	it 'can navigate to the crop form' do
		@page.crop_button.click
		no_php_js_errors

		crop_page = CropFile.new
		crop_page.displayed?
	end

	it 'can navigate back to the filemanger' do
		click_link "File Manager"
		no_php_js_errors

		@return.displayed?
	end

end