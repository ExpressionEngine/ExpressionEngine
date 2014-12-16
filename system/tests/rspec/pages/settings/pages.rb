class PagesSettings < ControlPanelPage

	element :nested, 'input[name="homepage_display"][value="nested"]'
	element :not_nested, 'input[name="homepage_display"][value="not_nested"]'
	element :default_channel, 'select[name="default_channel"]'
	elements :channel_default_template, '.scroll-wrap select'

	def load
		settings_btn.click
		within 'div.sidebar' do
			click_link 'Pages Settings'
		end
	end
end