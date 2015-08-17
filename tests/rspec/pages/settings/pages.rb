class PagesSettings < ControlPanelPage

	set_url 'admin.php?/cp/addons/settings/pages/settings'
	element :nested, 'input[name="homepage_display"][value="nested"]'
	element :not_nested, 'input[name="homepage_display"][value="not_nested"]'
	element :default_channel, 'select[name="default_channel"]'
	elements :channel_default_template, '.scroll-wrap select'

end
