require './bootstrap.rb'

feature 'File Manager / Crop File' do

	before(:all) do
		# Create backups of these folders so we can restore them after each test
		@upload_dir = File.expand_path('../../images/about/')
		system('mkdir /tmp/about')
		system('cp -r ' + @upload_dir + '/* /tmp/about')
	end

	after(:all) do
    system('rm -rf /tmp/about')
  end

  before(:each) do
    cp_session
    @page = CropFile.new
    @return = FileManager.new
    @file_name = @page.load
    no_php_js_errors

    @page.displayed?

    # Check that the heder data is intact
    @page.should have_title_toolbar
    @page.should have_download_all

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
  end

  before(:each, :tab => 'resize') do
    @page.resize_tab.click

    @page.wait_until_resize_width_input_visible
    @page.wait_until_resize_height_input_visible
    @page.wait_until_resize_image_preview_visible
  end

  after(:each) do
    system('rm -rf ' + @upload_dir)
    system('mkdir ' + @upload_dir)
    system('cp -r /tmp/about/* ' + @upload_dir)
    FileUtils.chmod_R 0777, @upload_dir
  end

  it 'shows the crop form by default' do
    @page.should have_crop_width_input
    @page.should have_crop_height_input
    @page.should have_crop_x_input
    @page.should have_crop_y_input
    @page.should have_crop_image_preview
  end

  it 'requires crop width when cropping' do
    @page.crop_height_input.set 5
    @page.crop_x_input.set 0
    @page.crop_y_input.set 0
    wait_for_ajax
    @page.crop_width_input.set ''
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Crop File"
    @page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
  end

  it 'requires crop height when cropping' do
    @page.crop_width_input.set 5
    @page.crop_x_input.set 0
    @page.crop_y_input.set 0
    wait_for_ajax
    @page.crop_height_input.set ''
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Crop File"
    @page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
  end

  it 'requires crop x when cropping' do
    @page.crop_width_input.set 5
    @page.crop_height_input.set 5
    @page.crop_y_input.set 0
    wait_for_ajax
    @page.crop_x_input.set ''
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Crop File"
    @page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
  end

  it 'requires crop y when cropping' do
    @page.crop_width_input.set 5
    @page.crop_height_input.set 5
    @page.crop_x_input.set 0
    wait_for_ajax
    @page.crop_y_input.set ''
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Crop File"
    @page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
  end

  it 'validates that crop width is a number' do
    @page.crop_height_input.set 5
    @page.crop_x_input.set 0
    @page.crop_y_input.set 0
    wait_for_ajax
    @page.crop_width_input.set 'a'
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Crop File"
    @page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
  end

  it 'validates that crop height is a number' do
    @page.crop_width_input.set 5
    @page.crop_x_input.set 0
    @page.crop_y_input.set 0
    wait_for_ajax
    @page.crop_height_input.set 'a'
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Crop File"
    @page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
  end

  it 'validates that crop x is a number' do
    @page.crop_width_input.set 5
    @page.crop_height_input.set 5
    @page.crop_y_input.set 0
    wait_for_ajax
    @page.crop_x_input.set 'a'
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Crop File"
    @page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
  end

  it 'validates that crop y is a number' do
    @page.crop_width_input.set 5
    @page.crop_height_input.set 5
    @page.crop_x_input.set 0
    wait_for_ajax
    @page.crop_y_input.set 'a'
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Crop File"
    @page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
  end

  it 'validates that crop width is greater than zero' do
    @page.crop_height_input.set 5
    @page.crop_x_input.set 0
    @page.crop_y_input.set 0
    wait_for_ajax
    @page.crop_width_input.set 0
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Crop File"
    @page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
  end

  it 'validates that crop height is greater than zero' do
    @page.crop_width_input.set 5
    @page.crop_x_input.set 0
    @page.crop_y_input.set 0
    wait_for_ajax
    @page.crop_height_input.set 0
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Crop File"
    @page.alert.text.should include "We were unable to crop the file, please review and fix errors below."
  end

  it 'can crop an image' do
    @page.crop_width_input.set 5
    @page.crop_height_input.set 5
    @page.crop_x_input.set 0
    @page.crop_y_input.set 0
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.should_not have_css('.fieldset-invalid')
    @page.alert.text.should include "File Crop Success"
  end

  it 'can display the rotate form', :tab => 'rotate' do
    @page.should have_css('div.tab.t-1.tab-open')
  end

  it 'requires a rotation option when rotating', :tab => 'rotate' do
    skip "cannot figure out how uncheck the default option" do
     end
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Rotate File"
    @page.alert.text.should include "We were unable to rotate the file, please review and fix errors below."
  end

  it 'can rotate right', :tab => 'rotate' do
    @page.rotate_right.click
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.should_not have_css('.fieldset-invalid')
    @page.alert.text.should include "File Rotate Success"
  end

  it 'can rotate left', :tab => 'rotate' do
    @page.rotate_left.click
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.should_not have_css('.fieldset-invalid')
    @page.alert.text.should include "File Rotate Success"
  end

  it 'can flip vertically', :tab => 'rotate' do
    @page.flip_vertical.click
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.should_not have_css('.fieldset-invalid')
    @page.alert.text.should include "File Rotate Success"
  end

  it 'can flip horizontally', :tab => 'rotate' do
    @page.flip_horizontal.click
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.should_not have_css('.fieldset-invalid')
    @page.alert.text.should include "File Rotate Success"
  end

  it 'can display the resize form', :tab => 'resize' do
    @page.should have_css('div.tab.t-2.tab-open')
  end

  it 'width is optional when resizing', :tab => 'resize' do
    @page.resize_width_input.set ''
    @page.resize_height_input.set 5
    @page.save.click
    no_php_js_errors

    @page.should have_alert_success
    @page.should_not have_css('.fieldset-invalid')
    @page.alert.text.should include "File Resize Success"
  end

  it 'height is optional when resizing', :tab => 'resize' do
    @page.resize_width_input.set 5
    @page.resize_height_input.set ''
    @page.save.click
    no_php_js_errors

    @page.should have_alert_success
    @page.should_not have_css('.fieldset-invalid')
    @page.alert.text.should include "File Resize Success"
  end

  it 'validates that resize width is a number', :tab => 'resize' do
    @page.resize_width_input.set 'a'
    @page.resize_height_input.set 5
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Resize File"
    @page.alert.text.should include "We were unable to resize the file, please review and fix errors below."
  end

  it 'validates that resize height is a number', :tab => 'resize' do
    @page.resize_width_input.set 5
    @page.resize_height_input.set 'a'
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_css('.fieldset-invalid')
    @page.alert.text.should include "Cannot Resize File"
    @page.alert.text.should include "We were unable to resize the file, please review and fix errors below."
  end

  it 'can resize an image', :tab => 'resize' do
    @page.resize_width_input.set 5
    @page.resize_height_input.set 5
    @page.save.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.should_not have_css('.fieldset-invalid')
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

  it 'shows an error if the file has no write permissions' do
    FileUtils.chmod 0444, Dir.glob(@upload_dir + '/*.{gif,jpg,png}')
    @page.load
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.alert.text.should include "File Not Writable"
    @page.alert.text.should include "Cannot write to the file"
    @page.alert.text.should include "Check your file permissions on the server"
  end

  it 'shows an error if the file does not exist' do
    FileUtils.rm Dir.glob(@upload_dir + '/*.{gif,jpg,png}')
    @page.load
    no_php_js_errors

    @page.text.should include "404"

    # @page.should have_alert
    # @page.should have_alert_error
    # @page.alert.text.should include "Cannot find the file"
  end

  it 'shows an error if the directory does not exist' do
    FileUtils.rm_rf @upload_dir
    @page.load
    no_php_js_errors

    @page.text.should include "404"

    # @page.should have_alert
    # @page.should have_alert_error
    # @page.alert.text.should include "Cannot find the file"
  end

end
