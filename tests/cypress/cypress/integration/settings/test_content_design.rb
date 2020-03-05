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

    @page.new_posts_clear_caches.value.should == new_posts_clear_caches
    @page.enable_sql_caching.value.should == enable_sql_caching
    @page.auto_assign_cat_parents.value.should == auto_assign_cat_parents
    @page.image_resize_protocol.has_checked_radio(ee_config(item: 'image_resize_protocol')).should == true
    @page.image_library_path.value.should == ee_config(item: 'image_library_path')
    @page.thumbnail_suffix.value.should == ee_config(item: 'thumbnail_prefix')
    @page.enable_emoticons.value.should == enable_emoticons
    @page.emoticon_url.value.should == ee_config(item: 'emoticon_url')
  end

  context 'when validating the form' do
    let(:image_library_path_error) { 'This field must contain a valid path to an image processing library if ImageMagick or NetPBM is the selected protocol.' }

    it 'validates image resize protocol when using ImageMagick' do
      # Should only show an error for image library path if ImageMagick or NetPBM are selected
      @page.image_resize_protocol.choose_radio_option('imagemagick')
      @page.image_library_path.set ''
      @page.image_library_path.trigger 'blur'
      @page.wait_for_error_message_count(1)
      should_have_form_errors(@page)
      should_have_error_text(@page.image_library_path, image_library_path_error)
    end

    it 'validates image resize protocol when using NetPBM' do
      @page.image_resize_protocol.choose_radio_option('netpbm')
      @page.image_library_path.set ''
      @page.image_library_path.trigger 'blur'
      @page.wait_for_error_message_count(1, 10)
      should_have_form_errors(@page)
      should_have_error_text(@page.image_library_path, image_library_path_error)
    end

    it 'validates a nonsense image library path' do
      @page.image_resize_protocol.choose_radio_option('netpbm')
      @page.image_library_path.set 'dfsdf'
      @page.image_library_path.trigger 'blur'
      @page.wait_for_error_message_count(1)
      should_have_form_errors(@page)
      should_have_error_text(@page.image_library_path, $invalid_path)
    end

    it 'validates a valid set of library and path' do
      @page.image_resize_protocol.choose_radio_option('gd')
      @page.image_library_path.set ''
      @page.image_library_path.trigger 'blur'
      @page.wait_for_error_message_count(0)
      should_have_no_form_errors(@page)
      should_have_no_error_text(@page.image_library_path)
    end
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
    new_posts_clear_caches = ee_config(item: 'new_posts_clear_caches')
    enable_sql_caching = ee_config(item: 'enable_sql_caching')
    auto_assign_cat_parents = ee_config(item: 'auto_assign_cat_parents')
    enable_emoticons = ee_config(item: 'enable_emoticons')

    @page.new_posts_clear_caches_toggle.click
    @page.enable_sql_caching_toggle.click
    @page.auto_assign_cat_parents_toggle.click
    @page.image_resize_protocol.choose_radio_option('imagemagick')
    @page.image_library_path.set '/'
    @page.thumbnail_suffix.set 'mysuffix'
    @page.enable_emoticons_toggle.click
    # Don't test this, we manually override this path in config.php for the tests
    #@page.emoticon_url.set 'http://myemoticons/'
    @page.submit

    @page.should have_text 'Preferences updated'
    @page.new_posts_clear_caches.value.should_not == new_posts_clear_caches
    @page.enable_sql_caching.value.should_not == enable_sql_caching
    @page.auto_assign_cat_parents.value.should_not == auto_assign_cat_parents
    @page.image_resize_protocol.has_checked_radio('imagemagick').should == true
    @page.image_library_path.value.should == '/'
    @page.thumbnail_suffix.value.should == 'mysuffix'
    @page.enable_emoticons.value.should_not == enable_emoticons
    #@page.emoticon_url.value.should == 'http://myemoticons/'
  end
end
