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
	element :include_seconds_y, 'input[name=include_seconds][value=y]'
	element :include_seconds_n, 'input[name=include_seconds][value=n]'

	element :table, 'table'
	elements :options, 'table tr td:first-child'
	elements :values, 'table tr td:nth-child(2)'

	# Custom field creation
	element :select_all, 'input[name=select_all]'
	element :custom_field_1, 'input[name="create_ids[0]"]'
	element :custom_field_2, 'input[name="create_ids[1]"]'
	element :custom_field_1_name, 'input[name="m_field_name[0]"]'
	element :custom_field_2_name, 'input[name="m_field_name[1]"]'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'Member Import'
	end
end