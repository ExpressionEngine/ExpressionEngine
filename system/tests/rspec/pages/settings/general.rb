class GeneralSettings < ControlPanelPage

	element :site_name, 'input[name=site_label]'
	element :is_system_on_y, 'input[name=is_system_on][value=y]'
	element :is_system_on_n, 'input[name=is_system_on][value=n]'
	element :new_version_check_y, 'input[name=new_version_check][value=y]'
	element :new_version_check_n, 'input[name=new_version_check][value=n]'
	element :check_version_btn, 'a.version-check'
	element :cp_theme, 'select[name=cp_theme]'
	element :language, 'select[name=deft_lang]'
	element :tz_country, 'select[name=tz_country]'
	element :timezone, 'select[name=default_site_timezone]'
	element :date_format, 'select[name=date_format]'
	element :time_format, 'select[name=time_format]'
	element :include_seconds_y, 'input[name=include_seconds][value=y]'
	element :include_seconds_n, 'input[name=include_seconds][value=n]'

	def load
		settings_btn.click
	end
end
