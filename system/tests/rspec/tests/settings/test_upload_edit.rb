require './bootstrap.rb'

feature 'Upload Destination Create/Edit' do

  before(:each) do
    cp_session
    @page = UploadEdit.new
    @page.load
    no_php_js_errors
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
    url_error = 'You must submit the URL to your upload directory.'
    upload_path = File.expand_path('../../../images')
    
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
    should_have_error_text(@page.name, 'The name of your directory is already taken.')
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
    @page.server_path.set upload_path
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
    @page.server_path.set upload_path
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
    should_have_error_text(@page.max_size, $integer_error)
    should_have_form_errors(@page)

    @page.max_width.set 'sdf'
    @page.max_width.trigger 'blur'
    @page.wait_for_error_message_count(5)
    should_have_error_text(@page.max_width, $integer_error)
    should_have_form_errors(@page)

    @page.max_height.set 'sdf'
    @page.max_height.trigger 'blur'
    @page.wait_for_error_message_count(6)
    should_have_error_text(@page.max_height, $integer_error)
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

    @page.server_path.set upload_path
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
    @page.server_path.value.should == upload_path + '/'
  end

  it 'should validate and save image manipulation data' do
    @page.should have_text 'No manipulations created'
    @page.should have_grid_add_no_results
    @page.should have_no_grid_add

    # Should add row
    @page.grid_add_no_results.click
    @page.should have_no_text 'No manipulations created'
    @page.should have_no_grid_add_no_results
    @page.should have_grid_add
    @page.grid_rows.size.should == 2 # Includes header

    # Should remove row and show "no manipulations" message
    @page.delete_for_row(1).click
    @page.should have_grid_add_no_results
    @page.should have_no_grid_add
    @page.grid_rows.size.should == 2 # Header and no results row
  end

end