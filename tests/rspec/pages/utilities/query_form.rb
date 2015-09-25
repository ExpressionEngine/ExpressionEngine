class QueryForm < ControlPanelPage

	element :query_form, 'textarea[name=thequery]'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'Query Form'
	end
end
