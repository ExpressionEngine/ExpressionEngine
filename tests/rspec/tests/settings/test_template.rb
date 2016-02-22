require './bootstrap.rb'

feature 'Template Settings' do

  before(:each) do
    cp_session
    @page = TemplateSettings.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Template Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    strict_urls = ee_config(item: 'strict_urls')
    save_tmpl_revisions = ee_config(item: 'save_tmpl_revisions')
    save_tmpl_files = ee_config(item: 'save_tmpl_files')

    @page.strict_urls_y.checked?.should == (strict_urls == 'y')
    @page.strict_urls_n.checked?.should == (strict_urls == 'n')
    @page.site_404.value.should == ee_config(item: 'site_404')
    @page.save_tmpl_revisions_y.checked?.should == (save_tmpl_revisions == 'y')
    @page.save_tmpl_revisions_n.checked?.should == (save_tmpl_revisions == 'n')
    @page.max_tmpl_revisions.value.should == ee_config(item: 'max_tmpl_revisions')
    @page.save_tmpl_files_y.checked?.should == (save_tmpl_files == 'y')
    @page.save_tmpl_files_n.checked?.should == (save_tmpl_files == 'n')
  end

  it 'should validate the form' do
    max_revs_error = 'This field must contain an integer.'
    invalid_path = 'The path you submitted is not valid.'

    @page.max_tmpl_revisions.set 'sdfsdfsd'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    @page.should have_text max_revs_error
    @page.should have_no_text invalid_path

    # AJAX validation
    @page.load
    @page.max_tmpl_revisions.set 'sdfsdfsd'
    @page.max_tmpl_revisions.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    @page.should have_text max_revs_error

    @page.max_tmpl_revisions.set '100'
    @page.max_tmpl_revisions.trigger 'blur'
    @page.wait_for_error_message_count(1)
  end

  it 'should save and load the settings' do
    @page.strict_urls_n.click
    @page.site_404.select 'search/index'
    @page.save_tmpl_revisions_y.click
    @page.max_tmpl_revisions.set '300'
    @page.save_tmpl_files_y.click
    @page.submit

    @page.should have_text 'Preferences Updated'
    @page.strict_urls_n.checked?.should == true
    @page.site_404.value.should == 'search/index'
    @page.save_tmpl_revisions_y.checked?.should == true
    @page.max_tmpl_revisions.value.should == '300'
    @page.save_tmpl_files_y.checked?.should == true
  end
end
