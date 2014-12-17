class RTESettings < ControlPanelPage
	set_url_matcher /addons\/settings\/rte/

	elements :headings, 'div.box h1'
	element :breadcrumb, 'ul.breadcrumb'

	element :phrase_search, 'fieldset.tbl-search input[name=search]'
	element :search_submit_button, 'fieldset.tbl-search input.submit'

	# Settings View
	element :enable_switch, 'input[name="rte_enabled"][value="y"]'
	element :disable_switch, 'input[name="rte_enabled"][value="n"]'
	element :default_tool_set, 'select[name="rte_default_toolset_id"]'
	element :selected_default_tool_set, 'select[name="rte_default_toolset_id"] option[selected="selected"]'
	element :save_settings_button, 'form.settings fieldset.form-ctrls input.btn[type="submit"]'

	element :create_new_button, 'div.tbl-ctrls form fieldset.tbl-search a.btn.action'
	elements :tool_sets, 'div.tbl-ctrls form div.tbl-wrap table tr'
	element :tool_set_name_header, 'div.tbl-ctrls form div.tbl-wrap table tr th:first-child'
	element :status_header, 'div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(2)'
	element :manage_header, 'div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(3)'
	element :checkbox_header, 'div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(4)'

	elements :tool_set_names, 'div.tbl-ctrls form div.tbl-wrap table tr td:first-child'
	elements :statuses, 'div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(2)'

	element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
	element :action_submit_button, 'form fieldset.tbl-bulk-act input.submit'

	# Tool Set View
	element :tool_set_name, 'input.required[name="toolset_name"]'
	elements :choose_tools, 'form fieldset.col-group div.setting-field label.choice.block input'
	element :tool_set_submit_button, 'form.settings fieldset.form-ctrls input.btn[type="submit"]'

	def load
		self.open_dev_menu
		click_link 'Add-On Manager'
		self.find('fieldset.tbl-search input[name=search]').set 'Rich Text Editor'
		self.find('fieldset.tbl-search input.submit').click
		self.find('ul.toolbar li.settings a').click
	end
end