require './bootstrap.rb'

feature 'Debugging & Output Settings' do

  before(:each) do
    cp_session
    @page = DebugOutput.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Debugging & Output Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    debug = ee_config(item: 'debug')
    show_profiler = ee_config(item: 'show_profiler')
    template_debugging = ee_config(item: 'template_debugging')
    gzip_output = ee_config(item: 'gzip_output')
    force_query_string = ee_config(item: 'force_query_string')
    send_headers = ee_config(item: 'send_headers')

    # This is ridiculous
    @page.debug_y.checked?.should == (debug == '1')
    @page.debug_n.checked?.should == (debug == '0')
    @page.show_profiler_y.checked?.should == (show_profiler == 'y')
    @page.show_profiler_n.checked?.should == (show_profiler == 'n')
    @page.template_debugging_y.checked?.should == (template_debugging == 'y')
    @page.template_debugging_n.checked?.should == (template_debugging == 'n')
    @page.gzip_output_y.checked?.should == (gzip_output == 'y')
    @page.gzip_output_n.checked?.should == (gzip_output == 'n')
    @page.force_query_string_y.checked?.should == (force_query_string == 'y')
    @page.force_query_string_n.checked?.should == (force_query_string == 'n')
    @page.send_headers_y.checked?.should == (send_headers == 'y')
    @page.send_headers_n.checked?.should == (send_headers == 'n')

    @page.redirect_method.value.should == ee_config(item: 'redirect_method')
    @page.max_caches.value.should == ee_config(item: 'max_caches')
  end

  it 'should validate the form' do
    max_caches_error = 'This field must contain an integer.'

    @page.max_caches.set 'sdfsdfsd'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    @page.should have_text max_caches_error

    # AJAX validation
    @page.load
    @page.max_caches.set 'sdfsdfsd'
    @page.max_caches.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    @page.should have_text max_caches_error

    @page.max_caches.set '100'
    @page.max_caches.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
  end

  it 'should save and load the settings' do
    @page.debug_n.click
    @page.show_profiler_y.click
    @page.template_debugging_y.click
    @page.gzip_output_y.click
    @page.force_query_string_y.click
    @page.send_headers_y.click
    @page.max_caches.set '300'
    @page.submit

    @page.should have_text 'Preferences Updated'
    @page.debug_n.checked?.should == true
    @page.show_profiler_y.checked?.should == true
    @page.template_debugging_y.checked?.should == true
    @page.gzip_output_y.checked?.should == true
    @page.force_query_string_y.checked?.should == true
    @page.send_headers_y.checked?.should == true
    @page.max_caches.value.should == '300'

    # Reset debug since it's only stored in config.php
    ee_config(item: 'debug', value: '1')
  end
end