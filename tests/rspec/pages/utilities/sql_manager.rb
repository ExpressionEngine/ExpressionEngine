class SqlManager < ControlPanelPage

	element :table, 'table'
	elements :tables, 'table tr td:first-child'
	elements :manage_links, 'td li.view a'
	element :search_field, 'input[name=search]'
	element :search_btn, 'input[name=search_form]'
	element :op_select, 'select[name=table_action]'
	element :op_submit, '.tbl-bulk-act input[type=submit]'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'SQL Manager'
	end
end