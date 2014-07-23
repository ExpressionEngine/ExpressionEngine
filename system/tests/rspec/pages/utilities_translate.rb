class Translate < ControlPanelPage
	set_url_matcher /utilities\/translate/

	element :title, 'div.box form h1'

	element :phrase_search, 'form fieldset.tbl-search input[name=filter_by_phrase]'
	element :search_submit_button, 'form fieldset.tbl-search input.submit'

	element :table, 'div.box form table'
	elements :rows, 'div.box form table tr'

	element :pagination, 'div.paginate'
	elements :pages, 'div.paginate ul li a'

	element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
	element :action_submit_button, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'English'
	end
end