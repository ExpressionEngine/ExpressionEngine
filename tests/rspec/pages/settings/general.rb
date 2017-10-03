class GeneralSettings < ControlPanelPage

  element :site_name, 'input[name=site_name]'
  element :site_short_name, 'input[name=site_short_name]'
  element :is_system_on_y, 'input[name=is_system_on][value=y]'
  element :is_system_on_n, 'input[name=is_system_on][value=n]'
  element :new_version_check_y, 'input[name=new_version_check][value=y]'
  element :new_version_check_n, 'input[name=new_version_check][value=n]'
  element :check_version_btn, 'a.version-check'
  element :language, 'select[name=deft_lang]'
  element :tz_country, 'select[name=tz_country]'
  element :timezone, 'select[name=default_site_timezone]'
  element :date_format, 'select[name=date_format]'
  element :time_format, 'select[name=time_format]'
  element :include_seconds, 'input[name=include_seconds]', :visible => false
  element :include_seconds_toggle, 'a[data-toggle-for=include_seconds]'

  def load
    settings_btn.click
  end
end
