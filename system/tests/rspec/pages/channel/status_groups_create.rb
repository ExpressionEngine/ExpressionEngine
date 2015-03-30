class StatusGroupCreate < ControlPanelPage

	element :group_name, 'input[name=group_name]'

	def load
		self.open_dev_menu
		click_link 'Channel Manager'
		click_link 'Status Groups'
		click_link 'Create New'
	end
end