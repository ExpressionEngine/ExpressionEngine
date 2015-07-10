require './bootstrap.rb'

feature 'Upload Destination Create/Edit' do

  before(:each) do
    cp_session
    @page = UploadEdit.new
    @page.load
    no_php_js_errors

    @upload_path = File.expand_path('../../images')
  end

  it 'shows the Upload Destination Create/Edit page' do
    @page.should have_name
    @page.should have_url
    @page.should have_server_path
    @page.should have_allowed_types
    @page.should have_max_size
    @page.should have_max_width
    @page.should have_max_height
    @page.should have_image_manipulations
    @page.should have_upload_member_groups
    @page.should have_cat_group
  end

  it 'should validate regular fields' do
    url_error = 'This field must contain a valid URL.'

    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Upload directory not saved'
    should_have_error_text(@page.name, $required_error)
    should_have_error_text(@page.url, url_error)
    should_have_error_text(@page.server_path, $required_error)

    # AJAX validation
    # Required name
    @page.load
    @page.name.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.name, $required_error)
    should_have_form_errors(@page)

    @page.name.set 'Dir'
    @page.name.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.name)
    should_have_no_form_errors(@page)

    # Duplicate directory name
    @page.name.set 'Main Upload Directory'
    @page.name.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.name, $unique)
    should_have_form_errors(@page)

    # Multiple errors for URL
    # Error when just submitting "http://"
    @page.url.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.url, url_error)
    should_have_form_errors(@page)

    # Resolve that error
    @page.url.set 'http://ee3/'
    @page.url.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_no_error_text(@page.url)
    should_have_form_errors(@page)

    # Error when left blank
    @page.url.set ''
    @page.url.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.url, $required_error)
    should_have_form_errors(@page)

    # Server path errors, path must both exist and be writable
    # Required:
    @page.server_path.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_error_text(@page.server_path, $required_error)
    should_have_form_errors(@page)

    # Resolve so can break again:
    @page.server_path.set @upload_path
    @page.server_path.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_no_error_text(@page.server_path)
    should_have_form_errors(@page)

    # Invalid path:
    @page.server_path.set 'sdfsdf'
    @page.server_path.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_error_text(@page.server_path, $invalid_path)
    should_have_form_errors(@page)

    # Resolve so can break again:
    @page.server_path.set @upload_path
    @page.server_path.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_no_error_text(@page.server_path)
    should_have_form_errors(@page)

    # Not writable path:
    @page.server_path.set '/'
    @page.server_path.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_error_text(@page.server_path, $not_writable)
    should_have_form_errors(@page)

    @page.max_size.set 'sdf'
    @page.max_size.trigger 'blur'
    @page.wait_for_error_message_count(4)
    should_have_error_text(@page.max_size, $natural_number)
    should_have_form_errors(@page)

    @page.max_width.set 'sdf'
    @page.max_width.trigger 'blur'
    @page.wait_for_error_message_count(5)
    should_have_error_text(@page.max_width, $natural_number)
    should_have_form_errors(@page)

    @page.max_height.set 'sdf'
    @page.max_height.trigger 'blur'
    @page.wait_for_error_message_count(6)
    should_have_error_text(@page.max_height, $natural_number)
    should_have_form_errors(@page)

    # These fields should not be required
    @page.max_size.set ''
    @page.max_size.trigger 'blur'
    @page.wait_for_error_message_count(5)
    should_have_no_error_text(@page.max_size)

    @page.max_width.set ''
    @page.max_width.trigger 'blur'
    @page.wait_for_error_message_count(4)
    should_have_no_error_text(@page.max_width)

    @page.max_height.set ''
    @page.max_height.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_no_error_text(@page.max_height)
    should_have_form_errors(@page)

    # Fix rest of fields
    @page.name.set 'Dir'
    @page.name.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_no_error_text(@page.name)
    should_have_form_errors(@page)

    @page.url.set 'http://ee3/'
    @page.url.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_no_error_text(@page.url)
    should_have_form_errors(@page)

    @page.server_path.set @upload_path
    @page.server_path.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.server_path)
    should_have_no_form_errors(@page)

    # Lots of AJAX going on, make sure there are no JS errors
    no_php_js_errors

    @page.submit
    no_php_js_errors
    @page.name.value.should == 'Dir'
    @page.url.value.should == 'http://ee3/'
    @page.server_path.value.should == @upload_path + '/'
  end

  it 'should validate image manipulation data' do
    watermark = WatermarkEdit.new
    watermark.load
    watermark.wm_name.set 'Test'
    watermark.submit

    @page.load
    @page.should have_text 'No manipulations created'
    @page.should have_grid_add_no_results
    @page.should have_no_grid_add

    # Should add row
    @page.grid_add_no_results.click
    @page.should have_no_text 'No manipulations created'
    @page.should have_no_grid_add_no_results
    @page.should have_grid_add
    @page.grid_rows.size.should == 2 # Includes header

    # Make sure watermarks are available
    within @page.watermark_for_row(1) do
        all('option').map(&:value).should == ['0','1']
    end

    # Should remove row and show "no manipulations" message
    @page.delete_for_row(1).click
    @page.should have_grid_add_no_results
    @page.should have_no_grid_add
    @page.grid_rows.size.should == 2 # Header and no results row

    @page.grid_add_no_results.click

    @page.name.set 'Dir'
    @page.url.set 'http://ee3/'
    @page.server_path.set @upload_path
    @page.submit

    should_have_form_errors(@page)
    @page.error_messages.size.should == 3
    grid_should_have_error(@page.image_manipulations)
    grid_cell_should_have_error_text(@page.name_for_row(1), $required_error)
    grid_cell_should_have_error_text(@page.width_for_row(1), $required_error)
    grid_cell_should_have_error_text(@page.height_for_row(1), $required_error)
    no_php_js_errors

    # Reset for AJAX validation
    @page.load
    @page.grid_add_no_results.click

    # Name cell
    name_cell = @page.name_for_row(1)
    name_cell.trigger 'blur'
    @page.wait_for_error_message_count(1)
    grid_cell_should_have_error_text(name_cell, $required_error)

    name_cell.set 'some_name'
    name_cell.trigger 'blur'
    @page.wait_for_error_message_count(0)
    grid_cell_should_have_no_error_text(name_cell)

    name_cell.set 'some name'
    name_cell.trigger 'blur'
    @page.wait_for_error_message_count(1)
    grid_cell_should_have_error_text(name_cell, $alpha_dash)

    name_cell.set 'some_name'
    name_cell.trigger 'blur'
    @page.wait_for_error_message_count(0)
    grid_cell_should_have_no_error_text(name_cell)

    # Width cell
    width_cell = @page.width_for_row(1)
    width_cell.trigger 'blur'
    @page.wait_for_error_message_count(1)
    grid_cell_should_have_error_text(width_cell, $required_error)

    # Not required when a watermark is selected
    @page.watermark_for_row(1).select('Test')
    width_cell.trigger 'blur'
    @page.wait_for_error_message_count(0)
    grid_cell_should_have_no_error_text(width_cell)

    @page.watermark_for_row(1).select('No watermark')
    width_cell.trigger 'blur'
    @page.wait_for_error_message_count(1)

    width_cell.set '4'
    width_cell.trigger 'blur'
    @page.wait_for_error_message_count(0)
    grid_cell_should_have_no_error_text(width_cell)

    width_cell.set 'ssdfsdsd'
    width_cell.trigger 'blur'
    @page.wait_for_error_message_count(1)
    grid_cell_should_have_error_text(width_cell, $natural_number)

    # Height cell
    height_cell = @page.height_for_row(1)
    height_cell.trigger 'blur'
    @page.wait_for_error_message_count(2)
    grid_cell_should_have_error_text(height_cell, $required_error)

    # Not required when a watermark is selected
    @page.watermark_for_row(1).select('Test')
    height_cell.trigger 'blur'
    @page.wait_for_error_message_count(1)
    grid_cell_should_have_no_error_text(height_cell)

    @page.watermark_for_row(1).select('No watermark')
    height_cell.trigger 'blur'
    @page.wait_for_error_message_count(2)

    height_cell.set '4'
    height_cell.trigger 'blur'
    @page.wait_for_error_message_count(1)
    grid_cell_should_have_no_error_text(height_cell)

    height_cell.set 'ssdfsdsd'
    height_cell.trigger 'blur'
    @page.wait_for_error_message_count(2)
    grid_cell_should_have_error_text(height_cell, $natural_number)

    @page.grid_add.click
    @page.grid_rows.size.should == 3

    name_cell = @page.name_for_row(2)
    name_cell.trigger 'blur'
    @page.wait_for_error_message_count(3)
    grid_cell_should_have_error_text(name_cell, $required_error)

    name_cell.set 'some_name2'
    name_cell.trigger 'blur'
    @page.wait_for_error_message_count(2)
    grid_cell_should_have_no_error_text(name_cell)

    name_cell.set 'some_name'
    name_cell.trigger 'blur'
    @page.wait_for_error_message_count(3)
    grid_cell_should_have_error_text(name_cell, $unique)

    grid_should_have_error(name_cell)

    name_cell.set 'some_name2'
    name_cell.trigger 'blur'
    @page.wait_for_error_message_count(2)
    grid_cell_should_have_no_error_text(name_cell)

    width_cell.set '4'
    width_cell.trigger 'blur'
    @page.wait_for_error_message_count(1)
    grid_cell_should_have_no_error_text(width_cell)

    height_cell.set '4'
    height_cell.trigger 'blur'
    @page.wait_for_error_message_count(0)
    grid_cell_should_have_no_error_text(height_cell)

    grid_should_have_no_error(@page.image_manipulations)
    should_have_no_form_errors(@page)
  end

  it 'should repopulate the form on validation error, and save' do
    @page.url.set 'http://ee3/'
    @page.server_path.set @upload_path
    @page.allowed_types.select 'All file types'
    @page.max_size.set '4'
    @page.max_width.set '300'
    @page.max_height.set '200'

    @page.grid_add_no_results.click
    @page.name_for_row(1).set 'some_name'
    @page.width_for_row(1).set '20'
    @page.height_for_row(1).set '30'

    @page.grid_add.click
    @page.name_for_row(2).set 'some_other_name'
    @page.resize_type_for_row(2).select 'Crop (part of image)'
    @page.width_for_row(2).set '50'
    @page.height_for_row(2).set '40'

    # Uncheck Members
    @page.upload_member_groups[0].set false

    # Check both category groups
    @page.cat_group[0].click
    @page.cat_group[1].click

    # We've set everything but a name, submit the form to see error
    @page.submit
    no_php_js_errors

    @page.should have_text 'Attention: Upload directory not saved'
    should_have_error_text(@page.name, $required_error)
    should_have_form_errors(@page)

    @page.server_path.value.should == @upload_path
    @page.allowed_types.value.should == 'all'
    @page.max_size.value.should == '4'
    @page.max_width.value.should == '300'
    @page.max_height.value.should == '200'

    @page.name_for_row(1).value.should == 'some_name'
    @page.width_for_row(1).value.should == '20'
    @page.height_for_row(1).value.should == '30'

    @page.name_for_row(2).value.should == 'some_other_name'
    @page.width_for_row(2).value.should == '50'
    @page.height_for_row(2).value.should == '40'

    @page.upload_member_groups[0].checked?.should == false
    @page.cat_group[0].checked?.should == true
    @page.cat_group[1].checked?.should == true

    # Fix error and make sure everything submitted ok
    @page.name.set 'Dir'
    @page.name.trigger 'blur'
    @page.wait_for_error_message_count(0)
    @page.submit

    @page.should have_text 'Upload directory saved'
    @page.name.value.should == 'Dir'
    @page.server_path.value.should == @upload_path + '/'
    @page.allowed_types.value.should == 'all'
    @page.max_size.value.should == '4'
    @page.max_width.value.should == '300'
    @page.max_height.value.should == '200'

    @page.name_for_row(1).value.should == 'some_name'
    @page.resize_type_for_row(1).value.should == 'constrain'
    @page.width_for_row(1).value.should == '20'
    @page.height_for_row(1).value.should == '30'

    @page.name_for_row(2).value.should == 'some_other_name'
    @page.resize_type_for_row(2).value.should == 'crop'
    @page.width_for_row(2).value.should == '50'
    @page.height_for_row(2).value.should == '40'

    @page.upload_member_groups[0].checked?.should == false
    @page.cat_group[0].checked?.should == true
    @page.cat_group[1].checked?.should == true
  end

  it 'should save a new upload directory' do
    @page.name.set 'Dir'
    @page.url.set 'http://ee3/'
    @page.server_path.set @upload_path
    @page.max_size.set '4'
    @page.max_width.set '300'
    @page.max_height.set '200'

    @page.grid_add_no_results.click
    @page.name_for_row(1).set 'some_name'
    @page.width_for_row(1).set '20'
    @page.height_for_row(1).set '30'

    @page.grid_add.click
    @page.name_for_row(2).set 'some_other_name'
    @page.resize_type_for_row(2).select 'Crop (part of image)'
    @page.width_for_row(2).set '50'
    @page.height_for_row(2).set '40'

    # Uncheck Members
    @page.upload_member_groups[0].set false

    # Check both category groups
    @page.cat_group[0].click
    @page.cat_group[1].click

    # We've set everything but a name, submit the form to see error
    @page.submit
    no_php_js_errors

    @page.should have_text 'Upload directory saved'
    @page.name.value.should == 'Dir'
    @page.server_path.value.should == @upload_path + '/'
    @page.allowed_types.value.should == 'img'
    @page.max_size.value.should == '4'
    @page.max_width.value.should == '300'
    @page.max_height.value.should == '200'

    @page.name_for_row(1).value.should == 'some_name'
    @page.resize_type_for_row(1).value.should == 'constrain'
    @page.width_for_row(1).value.should == '20'
    @page.height_for_row(1).value.should == '30'

    @page.name_for_row(2).value.should == 'some_other_name'
    @page.resize_type_for_row(2).value.should == 'crop'
    @page.width_for_row(2).value.should == '50'
    @page.height_for_row(2).value.should == '40'

    @page.upload_member_groups[0].checked?.should == false
    @page.cat_group[0].checked?.should == true
    @page.cat_group[1].checked?.should == true

    # Make sure we can edit with no validation issues
    @page.submit
    @page.should have_text 'Upload directory saved'
    no_php_js_errors

    # Test adding a new image manipulation to an existing directory
    # and that unique name validation works
    @page.grid_add.click
    name_cell = @page.name_for_row(3)
    name_cell.set 'some_name'
    name_cell.trigger 'blur'
    @page.wait_for_error_message_count(1)
    grid_cell_should_have_error_text(name_cell, $unique)

    name_cell.set 'some_name2'
    name_cell.trigger 'blur'
    @page.wait_for_error_message_count(0)
    grid_cell_should_have_no_error_text(name_cell)

    @page.width_for_row(3).set '60'
    @page.height_for_row(3).set '70'

    @page.submit
    @page.should have_text 'Upload directory saved'
    no_php_js_errors

    @page.name_for_row(3).value.should == 'some_name2'
    @page.resize_type_for_row(3).value.should == 'constrain'
    @page.width_for_row(3).value.should == '60'
    @page.height_for_row(3).value.should == '70'

    # Test row deletion
    @page.delete_for_row(2).click
    @page.grid_rows.size.should == 3 # Header and two rows

    @page.submit
    @page.should have_text 'Upload directory saved'
    no_php_js_errors

    @page.grid_rows.size.should == 3 # Header and two rows

    @page.name_for_row(1).value.should == 'some_name'
    @page.name_for_row(2).value.should == 'some_name2'
  end

  it 'should edit an existing upload directory' do
    @page.load_edit_for_dir(1)
    no_php_js_errors

    @page.name.set 'New name upload dir'
    @page.server_path.set @upload_path # Set a path that works for the environment
    @page.submit

    @page.should have_text 'Upload directory saved'
    @page.name.value.should == 'New name upload dir'
  end

  it 'should reject XSS' do
    @page.name.set $xss_vector
    @page.name.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.name, $xss_error)
    should_have_form_errors(@page)
  end
end
