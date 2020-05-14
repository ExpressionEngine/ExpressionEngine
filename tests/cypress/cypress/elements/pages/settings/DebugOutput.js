import ControlPanel from '../ControlPanel'

class DebugOutput extends ControlPanel {
  constructor() {
      super()

      this.elements({
        // This is ridiculous
        'debug': 'input[type!=hidden][name=debug]',
        'show_profiler_toggle': '[data-toggle-for=show_profiler]',
        'show_profiler': 'input[type!=hidden][name=show_profiler]', //visible => false
        'enable_devlog_alerts_toggle': '[data-toggle-for=enable_devlog_alerts]',
        'enable_devlog_alerts': 'input[type!=hidden][name=enable_devlog_alerts]', //visible => false
        'gzip_output_toggle': '[data-toggle-for=gzip_output]',
        'gzip_output': 'input[type!=hidden][name=gzip_output]', //visible => false
        'force_query_string_toggle': '[data-toggle-for=force_query_string]',
        'force_query_string': 'input[type!=hidden][name=force_query_string]', //visible => false
        'send_headers_toggle': '[data-toggle-for=send_headers]',
        'send_headers': 'input[type!=hidden][name=send_headers]', //visible => false
        'redirect_method': 'input[type!=hidden][name=redirect_method]',
        'cache_driver': 'input[type!=hidden][name=cache_driver]',
        'max_caches': 'input[type!=hidden][name=max_caches]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Debugging & Output")').click()
  }
}
export default DebugOutput;