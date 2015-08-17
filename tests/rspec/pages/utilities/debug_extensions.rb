class DebugExtensions < ControlPanelPage
	set_url_matcher /utilities\/extensions/

	element :heading, 'div.w-12 div.box h1'

	element :no_results, 'div.box div.tbl-ctrls form div.tbl-wrap table tr.no-results'

	element :addon_name_header, 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:first-child'
	element :status_header, 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(2)'
	element :manage_header, 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(3)'
	element :checkbox_header, 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(4)'

	elements :addons, 'div.box div.tbl-ctrls form div.tbl-wrap table tr'
	elements :addon_names, 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:first-child'
	elements :statuses, 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(2)'

	element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]', visible: false
	element :action_submit_button, 'form fieldset.tbl-bulk-act input.submit'

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'Debug Extensions'
	end
end
