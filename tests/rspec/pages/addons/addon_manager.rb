class AddonManager < ControlPanelPage
	set_url_matcher /addons/

	element :title, 'div.box.full.mb form h1'

	element :phrase_search, 'fieldset.tbl-search input[name=search]'
	element :search_submit_button, 'fieldset.tbl-search input.submit'

	# First Party Add-Ons
	first_party_prefix = 'body section.wrap div.col-group + div.col-group div.col.w-16.last div.box:first-child '

	element :first_party_section, first_party_prefix
	element :first_party_heading, first_party_prefix + 'form h1'

	element :first_party_status_filter, first_party_prefix + 'div.filters ul li:first-child'
	element :first_party_status_filter_menu, first_party_prefix + 'div.filters ul li:first-child div.sub-menu ul', visible: false

	element :first_party_alert, first_party_prefix + 'div.alert'

	element :first_party_no_results, first_party_prefix + 'tr.no-results'

	elements :first_party_addons, first_party_prefix + 'form div.tbl-wrap table tbody tr'

	element :first_party_addon_name_header, first_party_prefix + 'form div.tbl-wrap table thead tr th:first-child'
	element :first_party_version_header, first_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(2)'
	element :first_party_manage_header, first_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(3)'
	element :first_party_checkbox_header, first_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(4)'

	elements :first_party_addon_names, first_party_prefix + 'form div.tbl-wrap table tbody tr td:first-child'
	elements :first_party_versions, first_party_prefix + 'form div.tbl-wrap table tbody tr td:nth-child(2)'

	element :first_party_pagination, first_party_prefix + 'div.paginate'
	elements :first_party_pages, first_party_prefix + 'div.paginate ul li a'

	element :first_party_bulk_action, first_party_prefix + 'form fieldset.tbl-bulk-act select[name="bulk_action"]', visible: false
	element :first_party_action_submit_button, first_party_prefix + 'form fieldset.tbl-bulk-act button.submit'

	# Third Party Add-Ons
	third_party_prefix = 'body section.wrap div.col-group + div.col-group div.col.w-16.last div.box:nth-child(2) '

	element :third_party_section, third_party_prefix
	element :third_party_heading, third_party_prefix + 'form h1'

	element :third_party_status_filter, third_party_prefix + 'div.filters ul li:first-child'
	element :third_party_status_filter_menu, third_party_prefix + 'div.filters ul li:first-child div.sub-menu ul', visible: false

	element :third_party_developer_filter, third_party_prefix + 'div.filters ul li:nth-child(2)'
	element :third_party_developer_filter_menu, third_party_prefix + 'div.filters ul li:nth-child(2) div.sub-menu ul', visible: false

	element :third_party_alert, third_party_prefix + 'div.alert'

	element :third_party_no_results, third_party_prefix + 'tr.no-results'

	elements :third_party_addons, third_party_prefix + 'form div.tbl-wrap table tbody tr'

	element :third_party_addon_name_header, third_party_prefix + 'form div.tbl-wrap table thead tr th:first-child'
	element :third_party_version_header, third_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(2)'
	element :third_party_manage_header, third_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(3)'
	element :third_party_checkbox_header, third_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(4)'

	elements :third_party_addon_names, third_party_prefix + 'form div.tbl-wrap table tbody tr td:first-child'
	elements :third_party_versions, third_party_prefix + 'form div.tbl-wrap table tbody tr td:nth-child(2)'

	element :third_party_pagination, third_party_prefix + 'div.paginate'
	elements :third_party_pages, third_party_prefix + 'div.paginate ul li a'

	element :third_party_bulk_action, third_party_prefix + 'form fieldset.tbl-bulk-act select[name="bulk_action"]', visible: false
	element :third_party_action_submit_button, third_party_prefix + 'form fieldset.tbl-bulk-act button.submit'

	def load
		self.open_dev_menu
		click_link 'Add-On Manager'
	end
end
