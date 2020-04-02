require './bootstrap.rb'

context('Debugging & Output Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = DebugOutput.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Debugging & Output Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    debug = eeConfig({item: 'debug')
    show_profiler = eeConfig({item: 'show_profiler')
    enable_devlog_alerts = eeConfig({item: 'enable_devlog_alerts')
    gzip_output = eeConfig({item: 'gzip_output')
    force_query_string = eeConfig({item: 'force_query_string')
    send_headers = eeConfig({item: 'send_headers')

    // This is ridiculous -- testing *each* radio button's status
    page.debug.has_checked_radio(debug)
    page.show_profiler.value.should == show_profiler
    page.enable_devlog_alerts.value.should == enable_devlog_alerts
    page.gzip_output.value.should == gzip_output
    page.force_query_string.value.should == force_query_string
    page.send_headers.value.should == send_headers
    page.redirect_method.has_checked_radio(eeConfig({item: 'redirect_method'))
    page.cache_driver.has_checked_radio(eeConfig({item: 'cache_driver'))
    page.max_caches.value.should == eeConfig({item: 'max_caches')
  }

  it('should validate the form', () => {
    max_caches_error = 'This field must contain an integer.'

    page.max_caches.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.get('wrap').contains(max_caches_error

    // AJAX validation
    page.load()
    page.max_caches.clear().type('sdfsdfsd'
    page.max_caches.blur()
    page.wait_for_error_message_count(1)
    should_have_form_errors(page)
    page.get('wrap').contains(max_caches_error

    page.max_caches.clear().type('100'
    page.max_caches.blur()
    page.wait_for_error_message_count(0)
    should_have_no_form_errors(page)
  }

  it('should save and load the settings', () => {
    show_profiler = eeConfig({item: 'show_profiler')
    enable_devlog_alerts = eeConfig({item: 'enable_devlog_alerts')
    gzip_output = eeConfig({item: 'gzip_output')
    force_query_string = eeConfig({item: 'force_query_string')
    send_headers = eeConfig({item: 'send_headers')

    page.debug.choose_radio_option('0')
    page.show_profiler_toggle.click()
    page.enable_devlog_alerts_toggle.click()
    page.gzip_output_toggle.click()
    page.force_query_string_toggle.click()
    page.send_headers_toggle.click()
    page.cache_driver.choose_radio_option('memcached')
    page.max_caches.clear().type('300'
    page.submit

    page.get('wrap').contains('Preferences updated'
    page.debug.has_checked_radio('0')
    page.show_profiler.value.should_not == show_profiler
    page.enable_devlog_alerts.value.should_not == enable_devlog_alerts
    page.gzip_output.value.should_not == gzip_output
    page.force_query_string.value.should_not == force_query_string
    page.send_headers.value.should_not == send_headers
    page.cache_driver.has_checked_radio('memcached')
    page.max_caches.value.should == '300'

    // Should show a message when the selected caching driver
    // cannot be initialized
    page.get('wrap').contains('Cannot connect to Memcached, using File driver instead.'

    // Reset debug and cache_driver since they're only stored in config.php
    eeConfig({item: 'cache_driver', value: 'file')
    eeConfig({item: 'debug', value: '1')
  }
}
