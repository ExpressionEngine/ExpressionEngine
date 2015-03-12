class ThrottlingSettings < ControlPanelPage

	element :enable_throttling_y, 'input[name=enable_throttling][value=y]'
	element :enable_throttling_n, 'input[name=enable_throttling][value=n]'
	element :banish_masked_ips_y, 'input[name=banish_masked_ips][value=y]'
	element :banish_masked_ips_n, 'input[name=banish_masked_ips][value=n]'
	element :lockout_time, 'input[name=lockout_time]'
	element :max_page_loads, 'input[name=max_page_loads]'
	element :time_interval, 'input[name=time_interval]'
	element :banishment_type, 'select[name=banishment_type]'
	element :banishment_url, 'input[name=banishment_url]'
	element :banishment_message, 'textarea[name=banishment_message]'

	def load
		settings_btn.click
		within 'div.sidebar' do
			click_link 'Access Throttling'
		end
	end
end