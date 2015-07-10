class ChannelMangerPage < ControlPanelPage

	# Title/header box elements
	element :manager_title, 'div.box.full.mb form h1'
	element :title_toolbar, 'div.box.full.mb form h1 ul.toolbar'
	element :channel_manager_settings, 'div.box.full.mb form h1 ul.toolbar li.settings'
	element :phrase_search, 'fieldset.tbl-search input[name=search]'
	element :search_submit_button, 'fieldset.tbl-search input.submit'

	# Sidebar elements
	element :sidebar_channels, 'div.sidebar h2:first-child a:first-child'
	element :sidebar_new_channels_button, 'div.sidebar h2:first-child a.btn.action'
	element :sidebar_custom_fields, 'div.sidebar h2:nth-child(2) a:first-child'
	element :sidebar_new_custom_fileds_button, 'div.sidebar h2:nth-child(2) a.btn.action'
	element :sidebar_field_groups, 'div.sidebar ul li:first-child a'
	element :sidebar_category_groups, 'div.sidebar h2:nth-child(4) a:first-child'
	element :sidebar_new_category_groups_button, 'div.sidebar h2:nth-child(4) a.btn.action'
	element :sidebar_status_grouops, 'div.sidebar h2:nth-child(5) a:first-child'
	element :sidebar_new_status_grouops_button, 'div.sidebar h2:nth-child(5) a.btn.action'

	# Main box elements
	element :heading, 'div.col.w-12 div.box .tbl-ctrls h1'

end
