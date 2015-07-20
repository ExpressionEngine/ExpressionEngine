class MemberGroups < ControlPanelPage

	element :member_search, 'input[name=search]'
	element :member_actions, 'select[name=bulk_action]'
	element :member_groups_table, 'table'

	def load
		main_menu.members_btn.click
		find('.sidebar h2 a[href$=cp/members/groups]').click
	end
end
