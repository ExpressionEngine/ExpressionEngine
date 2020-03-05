require './bootstrap.rb'

feature 'URL and Path Settings' do

  before(:each) do
    cp_session
    @page = UrlsSettings.new
    @page.load
    no_php_js_errors

    @site_index = ee_config(item: 'site_index')
    @site_url = ee_config(item: 'site_url')
    @cp_url = ee_config(item: 'cp_url')
    @theme_folder_url = ee_config(item: 'theme_folder_url')
    @theme_folder_path = ee_config(item: 'theme_folder_path')
    @profile_trigger = ee_config(item: 'profile_trigger')
    @reserved_category_word = ee_config(item: 'reserved_category_word')
    @use_category_name = ee_config(item: 'use_category_name')
    @word_separator = ee_config(item: 'word_separator')
  end

  it 'shows the URL and Path Settings page' do
    @page.should have_text 'URL and Path Settings'
    @page.should have_text 'Website index page'
    @page.all_there?.should == true
  end

  it 'should load current path settings into form fields' do
    @page.site_index.value.should == @site_index
    @page.site_url.value.should == @site_url
    @page.cp_url.value.should == @cp_url
    @page.theme_folder_url.value.should == @theme_folder_url
    @page.theme_folder_path.value.should == '{base_path}/themes/'
    @page.profile_trigger.value.should == @profile_trigger
    @page.category_segment_trigger.value.should == @reserved_category_word
    @page.use_category_name.has_checked_radio(@use_category_name).should == true
    @page.url_title_separator.has_checked_radio(@word_separator).should == true
  end

  it 'should validate the form' do
    field_required = "This field is required."

    @page.site_url.set ''
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    should_have_error_text(@page.site_url, field_required)

    # AJAX validation
    # Field not required, shouldn't do anything
    @page.load
    @page.site_index.set ''
    @page.site_index.trigger 'blur'
    should_have_no_form_errors(@page)

    @page.site_url.set ''
    @page.site_url.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.site_url, field_required)
    should_have_form_errors(@page)

    @page.cp_url.set ''
    @page.cp_url.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_form_errors(@page)
    should_have_error_text(@page.site_url, field_required)
    should_have_error_text(@page.cp_url, field_required)

    @page.theme_folder_url.set ''
    @page.theme_folder_url.trigger 'blur'
    @page.wait_for_error_message_count(3)

    @page.theme_folder_path.set ''
    @page.theme_folder_path.trigger 'blur'
    @page.wait_for_error_message_count(4)

    should_have_form_errors(@page)
    should_have_error_text(@page.site_url, field_required)
    should_have_error_text(@page.cp_url, field_required)
    should_have_error_text(@page.theme_folder_url, field_required)
    should_have_error_text(@page.theme_folder_path, field_required)

    @page.theme_folder_path.set '/'
    # When a text field is invalid, shouldn't need to blur
    # @page.theme_folder_path.trigger 'blur'
    @page.wait_for_error_message_count(3)
    # Make sure validation timer is still bound to field
    @page.theme_folder_path.set ''
    @page.wait_for_error_message_count(4)
    @page.theme_folder_path.set '/'
    @page.wait_for_error_message_count(3)
    # Timer should be unbound on blur
    @page.theme_folder_path.trigger 'blur'

    # Invalid theme path
    @page.theme_folder_path.set '/dfsdfsdfd'
    @page.theme_folder_path.trigger 'blur'
    @page.wait_for_error_message_count(4)

    should_have_form_errors(@page)
    should_have_error_text(@page.site_url, field_required)
    should_have_error_text(@page.cp_url, field_required)
    should_have_error_text(@page.theme_folder_url, field_required)
    # TODO: Uncomment when this stops fluking out
    #should_have_error_text(@page.theme_folder_path, $invalid_path)
  end

  it 'should reject XSS' do
    @page.site_index.set $xss_vector
    @page.site_index.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.site_index, $xss_error)
    should_have_form_errors(@page)

    @page.site_url.set $xss_vector
    @page.site_url.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.site_url, $xss_error)
    should_have_form_errors(@page)

    @page.cp_url.set $xss_vector
    @page.cp_url.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_form_errors(@page)
    should_have_error_text(@page.site_url, $xss_error)
    should_have_error_text(@page.cp_url, $xss_error)

    @page.theme_folder_url.set $xss_vector
    @page.theme_folder_url.trigger 'blur'
    @page.wait_for_error_message_count(4)

    @page.theme_folder_path.set $xss_vector
    @page.theme_folder_path.trigger 'blur'
    @page.wait_for_error_message_count(5)

    should_have_form_errors(@page)
    should_have_error_text(@page.site_url, $xss_error)
    should_have_error_text(@page.cp_url, $xss_error)
    should_have_error_text(@page.theme_folder_url, $xss_error)
    should_have_error_text(@page.theme_folder_path, $xss_error)
  end

  it 'should save and load the settings' do
    # We'll test one value for now to make sure the form is saving,
    # don't want to be changing values that could break the site
    # after submission
    @page.site_index.set 'hello.php'
    @page.submit

    @page.should have_text 'Preferences updated'
    @page.site_index.value.should eq 'hello.php'

    # Since this is in config.php, reset the value
    ee_config(item: 'index_page', value: 'index.php')
  end
end
