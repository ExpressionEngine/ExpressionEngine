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
    enable_devlog_alerts = ee_config(item: 'enable_devlog_alerts')
    gzip_output = ee_config(item: 'gzip_output')
    force_query_string = ee_config(item: 'force_query_string')
    send_headers = ee_config(item: 'send_headers')

    # This is ridiculous -- testing *each* radio button's status
    @page.debug_2.checked?.should == (debug == '2')
    @page.debug_1.checked?.should == (debug == '1')
    @page.debug_0.checked?.should == (debug == '0')
    @page.show_profiler.value.should == show_profiler
    @page.enable_devlog_alerts.value.should == enable_devlog_alerts
    @page.gzip_output.value.should == gzip_output
    @page.force_query_string.value.should == force_query_string
    @page.send_headers.value.should == send_headers
    @page.redirect_method_checked.value.should == ee_config(item: 'redirect_method')
    @page.cache_driver_checked.value.should == ee_config(item: 'cache_driver')
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
    show_profiler = ee_config(item: 'show_profiler')
    enable_devlog_alerts = ee_config(item: 'enable_devlog_alerts')
    gzip_output = ee_config(item: 'gzip_output')
    force_query_string = ee_config(item: 'force_query_string')
    send_headers = ee_config(item: 'send_headers')

    @page.debug_0.click
    @page.show_profiler_toggle.click
    @page.enable_devlog_alerts_toggle.click
    @page.gzip_output_toggle.click
    @page.force_query_string_toggle.click
    @page.send_headers_toggle.click
    @page.cache_driver_memcached.click
    @page.max_caches.set '300'
    @page.submit

    @page.should have_text 'Preferences updated'
    @page.debug_0.checked?.should == true
    @page.show_profiler.value.should_not == show_profiler
    @page.enable_devlog_alerts.value.should_not == enable_devlog_alerts
    @page.gzip_output.value.should_not == gzip_output
    @page.force_query_string.value.should_not == force_query_string
    @page.send_headers.value.should_not == send_headers
    @page.cache_driver_memcached.checked?.should == true
    @page.max_caches.value.should == '300'

    # Should show a message when the selected caching driver
    # cannot be initialized
    @page.should have_text 'Cannot connect to Memcached, using File driver instead.'

    # Reset debug and cache_driver since they're only stored in config.php
    ee_config(item: 'cache_driver', value: 'file')
    ee_config(item: 'debug', value: '1')
  end
end
