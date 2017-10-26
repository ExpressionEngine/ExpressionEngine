class DebugOutput < ControlPanelPage

  # This is ridiculous
  element :debug_y, 'input[name=debug][value="1"]'
  element :debug_n, 'input[name=debug][value="0"]'
  element :show_profiler_y, 'input[name=show_profiler][value=y]'
  element :show_profiler_n, 'input[name=show_profiler][value=n]'
  element :enable_devlog_alerts_y, 'input[name=enable_devlog_alerts][value=y]'
  element :enable_devlog_alerts_n, 'input[name=enable_devlog_alerts][value=n]'
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
