require './bootstrap.rb'

feature 'General Settings' do

  before(:each) do
    cp_session
    @page = GeneralSettings.new
    @page.load
    no_php_js_errors
  end

  after(:each) do
    # Reset is_system_on value in config
    ee_config(item: 'is_system_on', value: 'y')
  end

  it 'shows the General Settings page' do
    @page.should have_text 'General Settings'
    @page.should have_text 'Website name'
    @page.should have_site_name
    @page.should have_is_system_on_y
    @page.should have_is_system_on_n
    @page.should have_new_version_check_y
    @page.should have_new_version_check_n
    @page.should have_cp_theme
    @page.should have_language
    @page.should have_tz_country
    @page.should have_timezone
    @page.should have_date_format
    @page.should have_time_format
    @page.should have_no_check_version_btn
  end

  it 'should validate the form' do
    error_text = 'The "Website name" field is required.'

    # Set other random things to make sure they're repopulated
    @page.is_system_on_n.click
    @page.new_version_check_n.click
    @page.should have_check_version_btn
    @page.date_format.select 'yyyy-mm-dd'
    @page.time_format.select '24-hour'

    # Only field that's required, will be our test case
    @page.site_name.set ''

    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'An error occurred'
    @page.should have_text error_text
    @page.is_system_on_n.checked?.should == true
    @page.new_version_check_n.checked?.should == true
    @page.date_format.value.should == '%Y-%m-%d'
    @page.time_format.value.should == '24'

    # AJAX validation
    @page.load
    # Make sure old values didn't save after validation error
    should_have_no_form_errors(@page)
    @page.should have_no_text error_text
    @page.is_system_on_y.checked?.should == true
    @page.new_version_check_y.checked?.should == true
    @page.date_format.value.should == '%n/%j/%y'
    @page.time_format.value.should == '12'

    @page.site_name.set ''
    @page.site_name.trigger 'blur'

    sleep 0.5 # Race condition :(
    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text error_text

    @page.site_name.set 'EE2'
    @page.site_name.trigger 'blur'

    sleep 0.5
    no_php_js_errors
    should_have_no_form_errors(@page)
    @page.should have_no_text error_text

    @page.submit
    should_have_no_form_errors(@page)
    @page.should have_text 'Preferences Updated'
  end

  it 'should load and save the settings' do
    # Save new settings
    @page.site_name.set 'My sweet site'
    @page.is_system_on_n.click
    @page.new_version_check_n.click
    @page.should have_check_version_btn
    @page.date_format.select 'yyyy-mm-dd'
    @page.time_format.select '24-hour'
    @page.submit

    # Make sure they stuck, also test Check Now button visibility
    no_php_js_errors
    should_have_no_form_errors(@page)
    @page.should have_text 'Preferences Updated'
    @page.site_name.value.should == 'My sweet site'
    @page.is_system_on_n.checked?.should == true
    @page.new_version_check_n.checked?.should == true
    @page.should have_check_version_btn
    @page.date_format.value.should == '%Y-%m-%d'
    @page.time_format.value.should == '24'
  end 

end