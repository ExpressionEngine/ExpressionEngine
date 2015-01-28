class CategoryGroups < ControlPanelPage

	element :table, 'table'
	elements :category_groups, 'table tr'
	elements :group_names, 'table tr td:nth-child(2)'

	def load
		self.open_dev_menu
		click_link 'Channel Manager'
		click_link 'Category Groups'
	end
end