require './bootstrap.rb'

feature 'Template Settings' do

  before(:each) do
    skip "waiting on es6 solution for Capybara" do
    end
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

    @page.strict_urls.value.should = strict_urls
    @page.site_404.value.should == ee_config(item: 'site_404')
    @page.save_tmpl_revisions.value.should = save_tmpl_revisions
  end

  it 'should validate the form' do
    @page.max_tmpl_revisions.set 'sdfsdfsd'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    @page.should have_text $integer_error
    @page.should have_no_text $invalid_path

    # AJAX validation
    @page.load
    @page.max_tmpl_revisions.set 'sdfsdfsd'
    @page.max_tmpl_revisions.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    @page.should have_text $integer_error

    @page.max_tmpl_revisions.set '100'
    @page.max_tmpl_revisions.trigger 'blur'
    @page.wait_for_error_message_count(0)
  end

  it 'should save and load the settings' do
    @page.strict_urls_toggle.click
    @page.site_404.select 'search/index'
    @page.save_tmpl_revisions_toggle.click
    @page.max_tmpl_revisions.set '300'
    @page.submit

    @page.should have_text 'Preferences Updated'
    @page.strict_urls.value.should == 'n'
    @page.site_404.value.should == 'search/index'
    @page.save_tmpl_revisions.value.should == 'y'
    @page.max_tmpl_revisions.value.should == '300'
  end
end
