class AddonManager < ControlPanelPage
	set_url_matcher /addons/

	element :title, 'div.box.full.mb form h1'
	element :heading, 'div.box.snap form h1'

	element :phrase_search, 'fieldset.tbl-search input[name=search]'
	element :search_submit_button, 'fieldset.tbl-search input.submit'

	element :status_filter, 'div.filters ul li:first-child'
	element :status_filter_menu, 'div.filters ul li:first-child div.sub-menu ul', visible: false

	element :developer_filter, 'div.filters ul li:nth-child(2)'
	element :developer_filter_menu, 'div.filters ul li:nth-child(2) div.sub-menu ul', visible: false

	element :perpage_filter, 'div.filters ul li:nth-child(3)'
	element :perpage_filter_menu, 'div.filters ul li:nth-child(3) div.sub-menu ul', visible: false
	element :perpage_manual_filter, 'input[name="perpage"]', visible: false

	element :no_results, 'tr.no-results'

	elements :addons, 'div.box.snap form div.tbl-wrap table tr'

	element :addon_name_header, 'div.box.snap form div.tbl-wrap table tr th:first-child'
	element :version_header, 'div.box.snap form div.tbl-wrap table tr th:nth-child(2)'
	element :manage_header, 'div.box.snap form div.tbl-wrap table tr th:nth-child(3)'
	element :checkbox_header, 'div.box.snap form div.tbl-wrap table tr th:nth-child(4)'

	elements :addon_names, 'div.box.snap form div.tbl-wrap table tr td:first-child'
	elements :versions, 'div.box.snap form div.tbl-wrap table tr td:nth-child(2)'

	element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
	element :action_submit_button, 'form fieldset.tbl-bulk-act button.submit'

	def load
		self.open_dev_menu
		click_link 'Add-On Manager'
	end
end