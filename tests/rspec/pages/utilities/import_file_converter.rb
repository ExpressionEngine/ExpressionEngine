class ImportConverter < ControlPanelPage

  element :file_location, 'input[name=member_file]'
  element :delimiter, 'input[name=delimiter]'
  element :delimiter_special, 'input[name=delimiter_special]'
  element :enclosing_char, 'input[name=enclosure]'

  # Assign fields page
  element :field1, 'select[name=field_0]'
  element :field2, 'select[name=field_1]'
  element :field3, 'select[name=field_2]'
  element :field4, 'select[name=field_3]'

  # XML Code page
  element :xml_code, 'textarea.template-edit'

  def load
    self.open_dev_menu
    click_link 'Utilities'
    click_link 'File Converter'
  end
end
