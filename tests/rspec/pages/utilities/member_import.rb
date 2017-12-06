class MemberImport < ControlPanelPage

  element :file_location, 'input[name=xml_file]'
  elements :member_group, 'input[name=group_id]'
  elements :language, 'input[name=language]'
  element :tz_country, 'select[name=tz_country]'
  element :timezone, 'select[name=timezones]'
  elements :date_format, 'input[name=date_format]'
  elements :time_format, 'input[name=time_format]'
  element :auto_custom_field, 'input[name=auto_custom_field]', :visible => false
  element :auto_custom_field_toggle, 'a[data-toggle-for=auto_custom_field]'
  element :include_seconds, 'input[name=include_seconds]', :visible => false
  element :include_seconds_toggle, 'a[data-toggle-for=include_seconds]'

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
