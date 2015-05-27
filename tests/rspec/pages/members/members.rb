class Members < ControlPanelPage

	element :member_search, 'input[name=search]'
	element :member_actions, 'select[name=bulk_action]'
	element :member_table, 'table'

	def load
		main_menu.members_btn.click
	end
end
