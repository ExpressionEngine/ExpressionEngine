import ControlPanel from '../ControlPanel'

class GeneralSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'site_name': 'input[name=site_name]',
        'site_short_name': 'input[name=site_short_name]',
        'is_system_on': 'input[name=is_system_on]',//: :visible => false
        'is_system_on_toggle': 'a[data-toggle-for=is_system_on]',
        'new_version_check': 'input[name=new_version_check]',
        'check_version_btn': 'a[data-for=version-check]',//: :visible => false
        'language': 'input[name=deft_lang]',
        'tz_country': 'select[name=tz_country]',
        'timezone': 'select[name=default_site_timezone]',
        'date_format': 'input[name=date_format]',
        'time_format': 'input[name=time_format]',
        'include_seconds': 'input[name=include_seconds]',//: :visible => false
        'include_seconds_toggle': 'a[data-toggle-for=include_seconds]'
      })
  }

  load() {
    this.get('settings_btn').click()
  }
}
export default GeneralSettings;