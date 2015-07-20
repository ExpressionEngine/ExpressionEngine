class MemberFields < ControlPanelPage

	element :member_actions, 'select[name=bulk_action]'
	element :member_fields_table, 'table'
	element :member_fields_create, '.tbl-search a'

	def load
		main_menu.members_btn.click
		find('.sidebar li a[href$=cp/members/fields]').click
	end
end
