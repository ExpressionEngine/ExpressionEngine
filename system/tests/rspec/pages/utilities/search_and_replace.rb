class SearchAndReplace < ControlPanelPage

	element :search_term, 'textarea[name=search_term]'
	element :replace_term, 'textarea[name=replace_term]'
	element :replace_where, 'select[name=replace_where]'
	element :password_auth, 'input[name=password_auth]'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'Search and Replace'
	end
end