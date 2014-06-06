class MemberImport < ControlPanelPage

	element :file_location, 'input[name=xml_file]'
	element :member_group, 'select[name=group_id]'
	element :language, 'select[name=language]'
	element :tz_country, 'select[name=tz_country]'
	element :timezone, 'select[name=timezones]'
	element :date_format, 'select[name=date_format]'
	element :time_format, 'select[name=time_format]'
	element :custom_yes, 'input[name=auto_custom_field][value=y]'
	element :custom_no, 'input[name=auto_custom_field][value=n]'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'Member Import'
	end
end