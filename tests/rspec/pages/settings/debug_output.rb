class DebugOutput < ControlPanelPage

  # This is ridiculous
  element :debug_2, 'input[name=debug][value="2"]'
  element :debug_1, 'input[name=debug][value="1"]'
  element :debug_0, 'input[name=debug][value="0"]'
  element :show_profiler_toggle, 'a[data-toggle-for=show_profiler]'
  element :show_profiler, 'input[name=show_profiler]', :visible => false
  element :enable_devlog_alerts_toggle, 'a[data-toggle-for=enable_devlog_alerts]'
  element :enable_devlog_alerts, 'input[name=enable_devlog_alerts]', :visible => false
  element :gzip_output_toggle, 'a[data-toggle-for=gzip_output]'
  element :gzip_output, 'input[name=gzip_output]', :visible => false
  element :force_query_string_toggle, 'a[data-toggle-for=force_query_string]'
  element :force_query_string, 'input[name=force_query_string]', :visible => false
  element :send_headers_toggle, 'a[data-toggle-for=send_headers]'
  element :send_headers, 'input[name=send_headers]', :visible => false
  elements :redirect_method, 'input[name=redirect_method]'
  element :redirect_method_checked, 'input[name=redirect_method][checked="checked"]'
  elements :cache_driver, 'input[name=cache_driver]'
  element :cache_driver_checked, 'input[name=cache_driver][checked="checked"]'
  element :cache_driver_memcached, 'input[name=cache_driver][value="memcached"]'
  element :max_caches, 'input[name=max_caches]'

  def load
    settings_btn.click
    click_link 'Debugging & Output'
  end
end
