class GeneralSettings < ControlPanelPage

  element :site_name, 'input[name=site_name]'
  element :site_short_name, 'input[name=site_short_name]'
  element :is_system_on_y, 'input[name=is_system_on][value=y]'
  element :is_system_on_n, 'input[name=is_system_on][value=n]'
  element :new_version_check_y, 'input[name=new_version_check][value=y]'
  element :new_version_check_n, 'input[name=new_version_check][value=n]'
  element :check_version_btn, 'a[data-for=version-check]'
  element :language, 'input[name=deft_lang]'
  element :tz_country, 'select[name=tz_country]'
  element :timezone, 'select[name=default_site_timezone]'
  element :date_format, 'input[name=date_format]'
  element :date_format_mm_dd_yyyy, 'input[name=date_format][value="%n/%j/%Y"]'
  element :date_format_yyyy_mm_dd, 'input[name=date_format][value="%Y-%m-%d"]'
  element :time_format, 'input[name=time_format]'
  element :time_format_24_hr, 'input[name=time_format][value="24"]'
  element :time_format_12_hr, 'input[name=time_format][value="12"]'
  element :include_seconds, 'input[name=include_seconds]', :visible => false
  element :include_seconds_toggle, 'a[data-toggle-for=include_seconds]'

  def load
    settings_btn.click
  end
end
