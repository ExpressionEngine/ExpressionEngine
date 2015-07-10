class QueryForm < ControlPanelPage

	element :query_form, 'textarea[name=thequery]'
	element :password, 'input[name="password_auth"]'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'Query Form'
	end
end
