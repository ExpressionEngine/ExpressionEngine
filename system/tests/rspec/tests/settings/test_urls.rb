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
end