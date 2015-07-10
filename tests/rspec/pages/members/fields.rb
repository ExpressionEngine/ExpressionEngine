class MemberFields < ControlPanelPage

	element :member_actions, 'select[name=bulk_action]'
	element :member_fields_table, 'table'
	element :member_fields_create, '.tbl-search a'

	def load
		main_menu.members_btn.click
		find('.sidebar h2:last-child a:last-child').click
	end
end
