class GeneralSettings < ControlPanelPage

  element :site_name, 'input[name=site_name]'
  element :site_short_name, 'input[name=site_short_name]'
  element :is_system_on, 'input[name=is_system_on]', :visible => false
  element :is_system_on_toggle, 'a[data-toggle-for=is_system_on]'
  elements :new_version_check, 'input[name=new_version_check]'
  element :check_version_btn, 'a[data-for=version-check]', :visible => false
  element :language, 'input[name=deft_lang]'
  element :tz_country, 'select[name=tz_country]'
  element :timezone, 'select[name=default_site_timezone]'
  elements :date_format, 'input[name=date_format]'
  elements :time_format, 'input[name=time_format]'
  element :include_seconds, 'input[name=include_seconds]', :visible => false
  element :include_seconds_toggle, 'a[data-toggle-for=include_seconds]'

  load
    settings_btn.click
  }
}
