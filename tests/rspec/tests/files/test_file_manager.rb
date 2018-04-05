require './bootstrap.rb'

# @TODO Add test coverage for upload destination permissions
#  - Sidebar only lists directories member has access to
#  - "All Files" upload new file menu only lists directories member has access to
#  - Table of files only show files in directories member has access to

feature 'File Manager' do

	before(:all) do
		# Create backups of these folders so we can restore them after each test
		@upload_dir = File.expand_path('../../images/about/')
		@avatar_dir = File.expand_path('../../images/avatars')
		system('mkdir /tmp/about')
		system('mkdir /tmp/avatars')
		system('cp -r ' + @upload_dir + '/* /tmp/about')
		system('cp -r ' + @avatar_dir + '/* /tmp/avatars')
	end

	after(:all) do
    system('rm -rf /tmp/about')
    system('rm -rf /tmp/avatars')
  end

	before(:each) do
		cp_session
		@page = FileManager.new
		@page.load
		no_php_js_errors

		@page.displayed?

		# Check that the heder data is intact
		@page.manager_title.text.should eq 'File Manager'
		@page.should have_title_toolbar
		@page.should have_download_all

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
		@page.should have_upload_new_file_button
		@page.should have_upload_new_file_filter
		@page.should have_files
		@page.should_not have_no_results
	end

	# For tests specific to a particular directory
	before(:each, :all_files => false) do
		click_link "About"
		no_php_js_errors

		@page.should_not have_breadcrumb
		@page.should have_sync_button
		@page.should have_files
	end

	before(:each, :perpage => 50) do
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_filter_menu.click_link "50 results"
		no_php_js_errors
	end

	after(:each) do
		system('rm -rf ' + @upload_dir)
    system('mkdir ' + @upload_dir)
    system('cp -r /tmp/about/* ' + @upload_dir)
		FileUtils.chmod_R 0777, @upload_dir

		system('rm -rf ' + @avatar_dir)
    system('mkdir ' + @avatar_dir)
    system('cp -r /tmp/avatars/* ' + @avatar_dir)
	end

	it 'shows the "All Files" File Manager page', :all_files => true do
		@page.perpage_filter.text.should eq 'show (25)'
		@page.date_added_header[:class].should eq 'highlight'
		@page.should have(11).files
	end

	# General Tests

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
		@page.execute_script("$('div.filters input[type=text]').closest('form').submit()")
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
		@page.execute_script("$('div.filters input[type=text]').closest('form').submit()")
		no_php_js_errors

		click_link "Next"
		no_php_js_errors

		@page.perpage_filter.text.should eq "show (5)"
		@page.should have_pagination
		@page.should have(5).pages
		@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
		@page.should have(6).files
	end

	it 'can reverse sort by title/name', :all_files => true, :perpage => 50 do
		@page.title_name_header.find('a.sort').click
		no_php_js_errors

		a_to_z_titles = @page.title_names.map {|title| title.text}

		@page.title_name_header.find('a.sort').click
		no_php_js_errors

		@page.title_name_header[:class].should eq 'highlight'
		@page.title_names.map {|title| title.text}.should == a_to_z_titles.reverse!
	end

	it 'can sort by file type', :all_files => true, :perpage => 50 do
		file_types = @page.file_types.map {|file_type| file_type.text}

		@page.file_type_header.find('a.sort').click
		no_php_js_errors

		@page.file_type_header[:class].should eq 'highlight'
		sorted_file_types = @page.file_types.map {|file_type| file_type.text}
		sorted_file_types.should_not == file_types
	end

	it 'can reverse sort by file type', :all_files => true, :perpage => 50 do
		@page.file_type_header.find('a.sort').click
		no_php_js_errors

		@page.file_type_header[:class].should eq 'highlight'
		a_to_z_file_types = @page.file_types.map {|file_type| file_type.text}
		@page.file_type_header.find('a.sort').click
		no_php_js_errors

		@page.file_type_header[:class].should eq 'highlight'
		@page.file_types.map {|file_type| file_type.text}.should == a_to_z_file_types.reverse!
	end

	# it 'can sort by date added', :all_files => true, :perpage => 50 do
	# 	dates_added = @page.dates_added.map {|date_added| date_added.text}
	#
	# 	@page.date_added_header.find('a.sort').click
	# 	no_php_js_errors
	#
	# 	@page.date_added_header[:class].should eq 'highlight'
	# 	sorted_dates_added = @page.dates_added.map {|date_added| date_added.text}
	# 	sorted_dates_added.should_not == dates_added
	# end

	it 'can reverse sort by date added', :all_files => true, :perpage => 50 do
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
		@page.manage_actions[0].find('li.view a').click
		@page.wait_until_view_modal_visible
		@page.wait_for_view_modal_header(5)
		@page.view_modal.text.should include @page.title_names[0].find('em').text
	end

	it 'can edit file', :all_files => true do
		@page.manage_actions[0].find('li.edit a').click
		no_php_js_errors

		@page.current_url.should include 'files/file/edit'
		edit_page = EditFile.new
		edit_page.displayed?
	end

	it 'can crop and image', :all_files => true do
		@page.manage_actions[0].find('li.crop a').click
		no_php_js_errors

		@page.current_url.should include 'files/file/crop'
		crop_page = CropFile.new
		crop_page.displayed?
	end

	# The capybara/webkit driver is munging headers.
	# it 'can download a file', :all_files => true do
	# 	@page.manage_actions[0].find('li.download a').click
	# 	no_php_js_errors
	#
	# 	@page.response_headers['Content-Disposition'].should include 'attachment; filename='
	# end

	it 'displays an itemzied modal when attempting to remove 5 or less files' do
		file_name = @page.title_names[0].find('em').text

		@page.files[1].find('input[type="checkbox"]').set true
		@page.wait_until_bulk_action_visible
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click

		@page.wait_until_modal_visible
		@page.modal_title.text.should eq "Confirm Removal"
		@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
		@page.modal.text.should include file_name
		@page.modal.all('.checklist li').length.should eq 1
	end

	it 'displays a bulk confirmation modal when attempting to remove more than 5 files' do
		@page.checkbox_header.click
		@page.wait_until_bulk_action_visible
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click

		@page.wait_until_modal_visible
		@page.modal_title.text.should eq "Confirm Removal"
		@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
		@page.modal.text.should include 'File: 10 Files'
	end

	it 'can remove a single file', :all_files => true do
		file_name = @page.title_names[0].text

		@page.files[1].find('input[type="checkbox"]').set true
		@page.wait_until_bulk_action_visible
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click
		@page.wait_until_modal_visible
		@page.modal_submit_button.click # Submits a form
		no_php_js_errors

		@page.text.should_not include file_name
	end

	it 'can remove multiple files', :all_files => true, :perpage => 50 do
		@page.checkbox_header.click
		@page.wait_until_bulk_action_visible
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click
		@page.wait_until_modal_visible
		@page.modal_submit_button.click # Submits a form
		no_php_js_errors

		@page.should have_no_results
	end

	# The capybara/webkit driver is munging headers.
	# it 'can download a single file via the bulk action', :all_files => true do
	# 	@page.files[1].find('input[type="checkbox"]').set true
	# 	@page.bulk_action.select "Download"
	# 	@page.action_submit_button.click
	# 	no_php_js_errors
	#
	# 	@page.response_headers['Content-Disposition'].should include 'attachment; filename='
	# end

	# The capybara/webkit driver is munging headers.
	# it 'can download multiple files', :all_files => true do
	# 	@page.checkbox_header.click
	# 	@page.bulk_action.select "Download"
	# 	@page.action_submit_button.click
	# 	no_php_js_errors
	#
	# 	@page.response_headers['Content-Disposition'].should include 'attachment; filename='
	# end

	# The capybara/webkit driver is munging headers.
	# it 'can download all files', :all_files => true do
	# 	@page.download_all.click
	# 	no_php_js_errors
	#
	# 	@page.response_headers['Content-Disposition'].should include 'attachment; filename='
	# end

	it 'can add a new directory', :all_files => true do
		@page.new_directory_button.click
		no_php_js_errors

		@page.current_url.should include 'files/uploads/create'
	end

	it 'can view a single directory', :all_files => true do
		click_link "Main Upload Directory"
		no_php_js_errors

		@page.current_url.should include 'files/directory/'
		@page.heading.text.should eq 'Files in Main Upload Directory'
		@page.should have_sync_button
		@page.should have_no_results
	end

	it 'displays an itemized modal when attempting to remove a directory', :all_files => true do
		about_directory_selector = 'div.sidebar .folder-list > li:first-child'
		find(about_directory_selector).hover
		find(about_directory_selector + ' li.remove a').click

		@page.wait_until_remove_directory_modal_visible
		@page.modal_title.text.should eq "Confirm Removal"
		@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
		@page.modal.text.should include "Directory: About"
		@page.modal.all('.checklist li').length.should eq 1
	end

	it 'can remove a directory', :all_files => true do
		about_directory_selector = 'div.sidebar .folder-list > li:first-child'
		find(about_directory_selector).hover
		find(about_directory_selector + ' li.remove a').click

		@page.wait_until_remove_directory_modal_visible
		@page.modal_submit_button.click # Submits a form
		no_php_js_errors

		@page.sidebar.text.should_not include "About"
		@page.should have_alert
		@page.alert.text.should include "Upload directory removed"
		@page.alert.text.should include "The upload directory About has been removed."
	end

	it 'can remove the directory you are viewing', :all_files => true do
		click_link "About"
		no_php_js_errors

		@page.sidebar.find('li.act').text.should eq 'About'

		about_directory_selector = 'div.sidebar .folder-list > li:first-child'
		find(about_directory_selector).hover
		find(about_directory_selector + ' li.remove a').click

		@page.wait_until_remove_directory_modal_visible
		@page.modal_submit_button.click # Submits a form
		no_php_js_errors

		@page.sidebar.text.should_not include "About"
		@page.should have_alert
		@page.alert.text.should include "Upload directory removed"
		@page.alert.text.should include "The upload directory About has been removed."
	end

	# Tests specific to the "All Files" view

	it 'must choose where to upload a new file when viewing All Files', :all_files => true do
		@page.upload_new_file_filter.click
		@page.wait_until_upload_new_file_filter_menu_visible
		@page.upload_new_file_filter_menu_items[0].click
		no_php_js_errors

		@page.current_url.should include 'files/upload'
	end

	it 'can filter the Upload New File menu', :all_files => true do
	end

	# Tests specific to a directory view

	it 'can synchronize a directory', :all_files => false do
		@page.sync_button.click
		no_php_js_errors

		@page.current_url.should include 'files/uploads/sync'
	end

	it 'marks all missing files in index view', :all_files => false do
		FileUtils.rm Dir.glob(@upload_dir + '/*.jpg')
		@page.load
		no_php_js_errors

		@page.should have_alert
		@page.should have_alert_important
		@page.alert.text.should include "Files Not Found"
		@page.alert.text.should include "Highlighted files cannot be found on the server."

		@page.should have_css('tr.missing')
	end

	it 'marks all missing files in directory view', :all_files => true do
		FileUtils.rm Dir.glob(@upload_dir + '/*.jpg')
		@page.load
		no_php_js_errors

		@page.should have_alert
		@page.should have_alert_important
		@page.alert.text.should include "Files Not Found"
		@page.alert.text.should include "Highlighted files cannot be found on the server."

    @page.should have_css('tr.missing')
  end

end
