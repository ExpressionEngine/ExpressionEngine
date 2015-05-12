require './bootstrap.rb'

feature 'File Manger / Crop File' do

	before(:each) do
		cp_session
		@page = CropFile.new
		@return = FileManager.new
		@file_name = @page.load
		no_php_js_errors

		@page.displayed?

		# Check that the heder data is intact
		@page.should_not have_title_toolbar
		@page.should_not have_download_all
		@page.should_not have_phrase_search
		@page.should_not have_search_submit_button

		# Check that we do not have a sidebar
		@page.should_not have_sidebar

		@page.should have_breadcrumb
		@page.breadcrumb.text.should eq 'File ManagerEdit "' + @file_name +'"Crop, Rotate & Resize "' + @file_name +'"'
		@page.heading.text.should eq 'Crop, Rotate & Resize "' + @file_name +'"'

		@page.should have_crop_tab
		@page.should have_rotate_tab
		@page.should have_resize_tab
	end

	before(:each, :tab => 'rotate') do
		@page.rotate_tab.click

		@page.wait_until_rotate_right_visible
		@page.wait_until_rotate_left_visible
		@page.wait_until_flip_vertical_visible
		@page.wait_until_flip_horizontal_visible
		@page.wait_until_rotate_image_preview_visible
		@page.wait_until_rotate_submit_button_visible
	end

	before(:each, :tab => 'resize') do
		@page.resize_tab.click

		@page.wait_until_resize_width_input_visible
		@page.wait_until_resize_height_input_visible
		@page.wait_until_resize_image_preview_visible
		@page.wait_until_resize_submit_button_visible
	end

	it 'shows the crop form by default' do
		@page.should have_crop_width_input
		@page.should have_crop_height_input
		@page.should have_crop_x_input
		@page.should have_crop_y_input
		@page.should have_crop_image_preview
		@page.should have_crop_submit_button
	end

	it 'requires crop width when cropping' do
		@page.crop_width_input.set ''
		@page.crop_height_input.set 5
		@page.crop_x_input.set 0
		@page.crop_y_input.set 0
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Crop File"
		@page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
	end

	it 'requires crop height when cropping' do
		@page.crop_width_input.set 5
		@page.crop_height_input.set ''
		@page.crop_x_input.set 0
		@page.crop_y_input.set 0
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Crop File"
		@page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
	end

	it 'requires crop x when cropping' do
		@page.crop_width_input.set 5
		@page.crop_height_input.set 5
		@page.crop_x_input.set ''
		@page.crop_y_input.set 0
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Crop File"
		@page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
	end

	it 'requires crop y when cropping' do
		@page.crop_width_input.set 5
		@page.crop_height_input.set 5
		@page.crop_x_input.set 0
		@page.crop_y_input.set ''
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Crop File"
		@page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
	end

	it 'validates that crop width is a number' do
		@page.crop_width_input.set 'a'
		@page.crop_height_input.set 5
		@page.crop_x_input.set 0
		@page.crop_y_input.set 0
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Crop File"
		@page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
	end

	it 'validates that crop height is a number' do
		@page.crop_width_input.set 5
		@page.crop_height_input.set 'a'
		@page.crop_x_input.set 0
		@page.crop_y_input.set 0
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Crop File"
		@page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
	end

	it 'validates that crop x is a number' do
		@page.crop_width_input.set 5
		@page.crop_height_input.set 5
		@page.crop_x_input.set 'a'
		@page.crop_y_input.set 0
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Crop File"
		@page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
	end

	it 'validates that crop y is a number' do
		@page.crop_width_input.set 5
		@page.crop_height_input.set 5
		@page.crop_x_input.set 0
		@page.crop_y_input.set 'a'
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Crop File"
		@page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
	end

	it 'validates that crop width is greater than zero' do
		@page.crop_width_input.set 0
		@page.crop_height_input.set 5
		@page.crop_x_input.set 0
		@page.crop_y_input.set 0
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Crop File"
		@page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
	end

	it 'validates that crop height is greater than zero' do
		@page.crop_width_input.set 5
		@page.crop_height_input.set 0
		@page.crop_x_input.set 0
		@page.crop_y_input.set 0
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Crop File"
		@page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
	end

	it 'can crop an image' do
		@page.crop_width_input.set 5
		@page.crop_height_input.set 5
		@page.crop_x_input.set 0
		@page.crop_y_input.set 0
		@page.crop_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.success')
		@page.should_not have_css('div.invlaid')
		@page.alert.text.should include "File Crop Success"
	end

	it 'can display the rotate form', :tab => 'rotate' do
		@page.should have_css('div.tab.t-1.tab-open')
	end

	it 'requires a rotation option when rotating', :tab => 'rotate' do
		@page.rotate_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Rotate File"
		@page.alert.text.should include "We were unable to rotate the file, please review and fix errors below."
	end

	it 'can rotate right', :tab => 'rotate' do
		@page.rotate_right.click
		@page.rotate_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.success')
		@page.should_not have_css('div.invlaid')
		@page.alert.text.should include "File Rotate Success"
	end

	it 'can rotate left', :tab => 'rotate' do
		@page.rotate_left.click
		@page.rotate_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.success')
		@page.should_not have_css('div.invlaid')
		@page.alert.text.should include "File Rotate Success"
	end

	it 'can flip vertically', :tab => 'rotate' do
		@page.flip_vertical.click
		@page.rotate_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.success')
		@page.should_not have_css('div.invlaid')
		@page.alert.text.should include "File Rotate Success"
	end

	it 'can flip horizontally', :tab => 'rotate' do
		@page.flip_horizontal.click
		@page.rotate_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.success')
		@page.should_not have_css('div.invlaid')
		@page.alert.text.should include "File Rotate Success"
	end

	it 'can display the resize form', :tab => 'resize' do
		@page.should have_css('div.tab.t-2.tab-open')
	end

	it 'requires resize width when resizing', :tab => 'resize' do
		@page.resize_width_input.set ''
		@page.resize_height_input.set 5
		@page.resize_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Resize File"
		@page.alert.text.should include "We were unable to resize the file, please review and fix errors below."
	end

	it 'requires resize height when resizing', :tab => 'resize' do
		@page.resize_width_input.set 5
		@page.resize_height_input.set ''
		@page.resize_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Resize File"
		@page.alert.text.should include "We were unable to resize the file, please review and fix errors below."
	end

	it 'validates that resize width is a number', :tab => 'resize' do
		@page.resize_width_input.set 'a'
		@page.resize_height_input.set 5
		@page.resize_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Resize File"
		@page.alert.text.should include "We were unable to resize the file, please review and fix errors below."
	end

	it 'validates that resize height is a number', :tab => 'resize' do
		@page.resize_width_input.set 5
		@page.resize_height_input.set 'a'
		@page.resize_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Resize File"
		@page.alert.text.should include "We were unable to resize the file, please review and fix errors below."
	end

	it 'validates that resize height is greater than zero', :tab => 'resize' do
		@page.resize_width_input.set 0
		@page.resize_height_input.set 5
		@page.resize_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Resize File"
		@page.alert.text.should include "We were unable to resize the file, please review and fix errors below."
	end

	it 'validates that resize width is greater than zero', :tab => 'resize' do
		@page.resize_width_input.set 5
		@page.resize_height_input.set 0
		@page.resize_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.issue')
		@page.should have_css('div.invalid')
		@page.alert.text.should include "Cannot Resize File"
		@page.alert.text.should include "We were unable to resize the file, please review and fix errors below."
	end

	it 'can resize an image', :tab => 'resize' do
		@page.resize_width_input.set 5
		@page.resize_height_input.set 5
		@page.resize_submit_button.click
		no_php_js_errors

		@page.should have_alert
		@page.should have_css('div.alert.success')
		@page.should_not have_css('div.invlaid')
		@page.alert.text.should include "File Resize Success"
	end

	it 'can navigate back to the filemanger' do
		click_link "File Manager"
		no_php_js_errors

		file_manager = FileManager.new
		file_manager.displayed?
	end

	it 'can navigate to the edit action' do
		@page.breadcrumb.all('a')[1].click
		no_php_js_errors

		edit_file = EditFile.new
		edit_file.displayed?
	end

end