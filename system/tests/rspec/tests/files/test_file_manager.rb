require './bootstrap.rb'

# @TODO Add test coverage for upload destination permissions
#  - Sidebar only lists directories member has access to
#  - "All Files" upload new file menu only lists directories member has access to
#  - Table of files only show files in directories member has access to

feature 'File Manager', :all_files => true do

	before(:each) do
		cp_session
		@page = FileManager.new
		@page.load
		no_php_js_errors

		@page.displayed?

		# Check that the heder data is intact
		@page.title.text.should eq 'File Manager'
		@page.should have_title_toolbar
		@page.should have_export_all
		@page.should have_phrase_search
		@page.should have_search_submit_button

		# Check that we have a sidebar
		@page.should have_sidebar
		@page.upload_directories_header.text.should include 'Upload Directories'
		@page.should have_new_directory_button
		@page.watermarks_header.text.should include 'Watermarks'
		@page.should have_new_watermark_button
	end

	# For general and "All Files" specific tests
	before(:each, :all_files => true) do
		@page.should_not have_breadcrumb
		@page.should_not have_sync_button
		@page.heading.text.should eq 'All Files'
		@page.should_not have_upload_new_file_button
		@page.should have_upload_new_file_filter
		@page.should have_files
		@page.should have_bulk_action
		@page.should have_action_submit_button
		@page.should_not have_no_results
	end

	# For tests specific to a particular directory
	before(:each, :all_files => false) do
		click_link "About"
		no_php_js_errors

		@page.should_not have_breadcrumb
		@page.should have_sync_button
		@page.should have_upload_new_file_button
		@page.should_not have_upload_new_file_filter
		@page.should have_files
		@page.should have_bulk_action
		@page.should have_action_submit_button
		@page.should_not have_no_results
	end

	it 'shows the "All Files" File Manager page', :all_files => true do
		@page.perpage_filter.text.should eq 'show (20)'
		@page.title_name_header[:class].should eq 'highlight'
		@page.should have(11).files
	end

	# General Tests

	it 'can search files by phrase', :all_files => true do
		@page.phrase_search.set 'map'
		@page.search_submit_button.click
		no_php_js_errors

		@page.heading.text.should eq 'Search Results we found 2 results for "map"'
		@page.phrase_search.value.should eq 'map'
		@page.should have_text 'map'
		@page.should have(3).files
	end

	it 'can change the page size using the menu', :all_files => true do
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_filter_menu.click_link "50 results"
		no_php_js_errors

		@page.perpage_filter.text.should eq "show (50)"
		@page.should_not have_pagination
		@page.should have(11).files
end

	it 'can change the page size manually', :all_files => true do
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_manual_filter.set '5'
		@page.action_submit_button.click
		no_php_js_errors

		@page.perpage_filter.text.should eq "show (5)"
		@page.should have_pagination
		@page.should have(5).pages
		@page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]
		@page.should have(6).files
	end

	it 'can change pages', :all_files => true do
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_manual_filter.set '5'
		@page.action_submit_button.click
		no_php_js_errors

		click_link "Next"
		no_php_js_errors

		@page.perpage_filter.text.should eq "show (5)"
		@page.should have_pagination
		@page.should have(5).pages
		@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
		@page.should have(6).files
	end

	it 'can reverse sort by title/name', :all_files => true do
		a_to_z_titles = @page.title_names.map {|title| title.text}

		@page.title_name_header.find('a.sort').click
		no_php_js_errors

		@page.title_name_header[:class].should eq 'highlight'
		@page.title_names.map {|title| title.text}.should == a_to_z_titles.reverse!
	end

	it 'can sort by file type', :all_files => true do
		file_types = @page.file_types.map {|file_type| file_type.text}

		@page.file_type_header.find('a.sort').click
		no_php_js_errors

		@page.file_type_header[:class].should eq 'highlight'
		sorted_file_types = @page.file_types.map {|file_type| file_type.text}
		sorted_file_types.should_not == file_types
	end

	it 'can reverse sort by file type', :all_files => true do
		@page.file_type_header.find('a.sort').click
		no_php_js_errors

		@page.file_type_header[:class].should eq 'highlight'
		a_to_z_file_types = @page.file_types.map {|file_type| file_type.text}
		@page.file_type_header.find('a.sort').click
		no_php_js_errors

		@page.file_type_header[:class].should eq 'highlight'
		@page.file_types.map {|file_type| file_type.text}.should == a_to_z_file_types.reverse!
	end

	it 'can sort by date added', :all_files => true do
		dates_added = @page.dates_added.map {|date_added| date_added.text}

		@page.date_added_header.find('a.sort').click
		no_php_js_errors

		@page.date_added_header[:class].should eq 'highlight'
		sorted_dates_added = @page.dates_added.map {|date_added| date_added.text}
		sorted_dates_added.should_not == dates_added
	end

	it 'can reverse sort by date added', :all_files => true do
		@page.date_added_header.find('a.sort').click
		no_php_js_errors

		@page.date_added_header[:class].should eq 'highlight'
		a_to_z_dates_added = @page.dates_added.map {|date_added| date_added.text}
		@page.date_added_header.find('a.sort').click
		no_php_js_errors

		@page.date_added_header[:class].should eq 'highlight'
		@page.dates_added.map {|date_added| date_added.text}.should == a_to_z_dates_added.reverse!
	end

	it 'can view an image', :all_files => true do
	end

	it 'can edit file', :all_files => true do
	end

	it 'can crop and image', :all_files => true do
	end

	it 'can download a file', :all_files => true do
	end

	it 'can remove a single file', :all_files => true do
	end

	it 'can remove multiple files', :all_files => true do
	end

	it 'can download a single file via the bulk action', :all_files => true do
	end

	it 'can download multiple files', :all_files => true do
	end

	it 'can download all files', :all_files => true do
	end

	it 'can add a new directory', :all_files => true do
	end

	it 'can view a single directory', :all_files => true do
	end

	# Tests specific to the "All Files" view

	it 'must choose where to upload a new file when viewing All Files', :all_files => true do
	end

	it 'can filter the Upload New File menu', :all_files => true do
	end

	# Tests specific to a directory view

	it 'can synchronize a directory', :all_files => false do
	end

	it 'can upload a new file into the currently displayed directory', :all_files => false do
	end

end