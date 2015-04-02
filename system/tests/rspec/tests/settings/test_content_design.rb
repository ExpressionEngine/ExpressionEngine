require './bootstrap.rb'

feature 'Content & Design Settings' do

  before(:each) do
    cp_session
    @page = ContentDesign.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Content & Design Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    new_posts_clear_caches = ee_config(item: 'new_posts_clear_caches')
    enable_sql_caching = ee_config(item: 'enable_sql_caching')
    auto_assign_cat_parents = ee_config(item: 'auto_assign_cat_parents')
    enable_emoticons = ee_config(item: 'enable_emoticons')

    @page.new_posts_clear_caches_y.checked?.should == (new_posts_clear_caches == 'y')
    @page.new_posts_clear_caches_n.checked?.should == (new_posts_clear_caches == 'n')
    @page.enable_sql_caching_y.checked?.should == (enable_sql_caching == 'y')
    @page.enable_sql_caching_n.checked?.should == (enable_sql_caching == 'n')
    @page.auto_assign_cat_parents_y.checked?.should == (auto_assign_cat_parents == 'y')
    @page.auto_assign_cat_parents_n.checked?.should == (auto_assign_cat_parents == 'n')
    @page.image_resize_protocol.value.should == ee_config(item: 'image_resize_protocol')
    @page.image_library_path.value.should == ee_config(item: 'image_library_path')
    @page.thumbnail_suffix.value.should == ee_config(item: 'thumbnail_prefix')
    @page.enable_emoticons_y.checked?.should == (enable_emoticons == 'y')
    @page.enable_emoticons_n.checked?.should == (enable_emoticons == 'n')
    @page.emoticon_url.value.should == ee_config(item: 'emoticon_url')
  end

  it 'should validate the form' do
    image_library_path_error = 'This field must contain a valid path to an image processing library if ImageMagick or NetPBM is the selected protocol.'

    # AJAX validation
    # Should only show an error for image library path if ImageMagick or NetPBM are selected
    @page.image_resize_protocol.select 'ImageMagick'
    @page.image_library_path.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.image_library_path, image_library_path_error)

    @page.image_library_path.set '/'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    should_have_no_error_text(@page.image_library_path)

    @page.image_resize_protocol.select 'NetPBM'
    @page.image_library_path.set ''
    @page.image_library_path.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.image_library_path, image_library_path_error)

    @page.image_library_path.set '/'
    @page.image_library_path.trigger 'blur'
    @page.wait_for_error_message_count(0)

    invalid_path = 'The path you submitted is not valid.'

    @page.image_library_path.set 'dfsdf'
    @page.image_library_path.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.image_library_path, invalid_path)

    @page.image_resize_protocol.select 'GD'
    @page.image_library_path.set ''
    @page.image_library_path.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    should_have_no_error_text(@page.image_library_path)
  end

  it 'should reject XSS' do
    @page.image_library_path.set $xss_vector
    @page.image_library_path.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.image_library_path, $xss_error)
    should_have_form_errors(@page)

    @page.thumbnail_suffix.set $xss_vector
    @page.thumbnail_suffix.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.thumbnail_suffix, $xss_error)
    should_have_form_errors(@page)

    @page.emoticon_url.set $xss_vector
    @page.emoticon_url.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_error_text(@page.emoticon_url, $xss_error)
    should_have_form_errors(@page)
  end

  it 'should save and load the settings' do
    @page.new_posts_clear_caches_n.click
    @page.enable_sql_caching_y.click
    @page.auto_assign_cat_parents_n.click
    @page.submit

    @page.should have_text 'Preferences updated'
    @page.new_posts_clear_caches_n.checked?.should == true
    @page.enable_sql_caching_y.checked?.should == true
    @page.auto_assign_cat_parents_n.checked?.should == true
  end
end