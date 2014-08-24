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
    @doc_url = ee_config(item: 'doc_url')
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
    @page.theme_folder_path.value.should == @theme_folder_path
    @page.doc_url.value.should == @doc_url
    @page.profile_trigger.value.should == @profile_trigger
    @page.category_segment_trigger.value.should == @reserved_category_word
    @page.category_url.value.should == @use_category_name
    @page.url_title_separator.value.should == @word_separator
  end

  it 'should validate the form' do
    site_url_required = 'The "Website root directory" field is required.'
    cp_url_required = 'The "Control panel directory" field is required.'
    theme_url_required = 'The "Themes directory" field is required.'
    theme_path_required = 'The "Themes path" field is required.'
    profile_trigger_required = 'The "Profile URL segment" field is required.'
    theme_path_invalid = 'The path you submitted is not valid.'

    @page.site_url.set ''
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'An error occurred'
    @page.should have_text site_url_required

    # AJAX validation
    @page.site_index.set ''
    @page.site_index.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    @page.should have_text site_url_required

    # Field not required, shouldn't do anything
    @page.site_url.set ''
    @page.site_url.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    @page.should have_text site_url_required

    @page.cp_url.set ''
    @page.cp_url.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_form_errors(@page)
    @page.should have_text site_url_required
    @page.should have_text cp_url_required

    @page.theme_folder_url.set ''
    @page.theme_folder_url.trigger 'blur'
    @page.wait_for_error_message_count(3)

    @page.theme_folder_path.set ''
    @page.theme_folder_path.trigger 'blur'
    @page.wait_for_error_message_count(4)

    @page.profile_trigger.set ''
    @page.profile_trigger.trigger 'blur'
    @page.wait_for_error_message_count(5)

    should_have_form_errors(@page)
    @page.should have_text site_url_required
    @page.should have_text cp_url_required
    @page.should have_text theme_url_required
    @page.should have_text theme_path_required
    @page.should have_text profile_trigger_required

    @page.theme_folder_path.set '/'
    @page.theme_folder_path.trigger 'blur'
    @page.wait_for_error_message_count(4)

    # Invalid theme path
    @page.theme_folder_path.set '/dfsdfsdfd'
    @page.theme_folder_path.trigger 'blur'
    @page.wait_for_error_message_count(5)

    should_have_form_errors(@page)
    @page.should have_text site_url_required
    @page.should have_text cp_url_required
    @page.should have_text theme_url_required
    @page.should have_no_text theme_path_required
    @page.should have_text theme_path_invalid
    @page.should have_text profile_trigger_required
  end

  it 'should save and load the settings' do
    # We'll test one value for now to make sure the form is saving,
    # don't want to be changing values that could break the site
    # after submission
    @page.site_index.set 'hello.php'
    @page.submit

    @page.should have_text 'Preferences Updated'
    @page.site_index.value.should eq 'hello.php'

    # Since this is in config.php, reset the value
    ee_config(item: 'index_page', value: 'index.php')
  end
end