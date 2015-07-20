class ChannelFieldForm < ControlPanelPage

	element :field_type, 'select[name=field_type]'
	element :field_label, 'input[name=field_label]'
	element :field_name, 'input[name=field_name]'

	def load
		self.open_dev_menu
		click_link 'Channel Manager'
		click_link 'Custom Fields'
		click_link 'Create New'
	end

	def load_edit_for_custom_field(name)
		self.open_dev_menu
		click_link 'Channel Manager'
		click_link 'Custom Fields'

		all('table tbody tr').each do |row|
			cell = row.find('td:nth-child(2)')
			if cell.text == name
				row.find('li.edit a').click
				break
			end
		end
		#find('tbody tr:nth-child('+number.to_s+') li.edit a').click
	end
end
