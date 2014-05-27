class ImportConverter < ControlPanelPage

	element :file_location, 'input[name=member_file]'
	element :delimiter, 'input[name=delimiter]'
	element :delimiter_special, 'input[name=delimiter_special]'
	element :enclosing_char, 'input[name=enclosure]'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'File Converter'
	end
end