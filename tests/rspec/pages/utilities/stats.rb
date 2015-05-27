class Stats < ControlPanelPage
	set_url_matcher /utilities\/stats/

	element :heading, 'div.box form h1'

	element :alert, 'div.alert'

	element :content_table, 'div.box form table'
	elements :rows, 'div.box form table tr'
	elements :sources, 'div.box form table tr td:first-child'
	elements :counts, 'div.box form table tr td:nth-child(2)'

	element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
	element :action_submit_button, 'form fieldset.tbl-bulk-act input.submit'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'Statistics'
	end
end