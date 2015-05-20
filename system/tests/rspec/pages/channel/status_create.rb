class StatusCreate < ControlPanelPage

	element :status, 'input[name=status]'
	element :highlight, 'input[name=highlight]'
	elements :status_access, 'input[name="status_access[]"]'

	def load
		self.open_dev_menu
		click_link 'Channel Manager'
		click_link 'Status Groups'
		click_link 'Create New'
	end

	def load_view_for_status_group(number)
		self.open_dev_menu
		click_link 'Channel Manager'
		click_link 'Status Groups'

		find('tbody tr:nth-child('+number.to_s+') li.view a').click
	end

	def load_create_for_status_group(number)
		click_link 'Create New'
	end

	def load_edit_for_status(number)
		find('tbody tr:nth-child('+number.to_s+') li.edit a').click
	end
end