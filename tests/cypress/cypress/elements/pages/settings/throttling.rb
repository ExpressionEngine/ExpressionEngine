class ThrottlingSettings < ControlPanelPage

  element :enable_throttling, 'input[name=enable_throttling]', :visible => false
  element :enable_throttling_toggle, 'a[data-toggle-for=enable_throttling]'
  element :banish_masked_ips, 'input[name=banish_masked_ips]', :visible => false
  element :banish_masked_ips_toggle, 'a[data-toggle-for=banish_masked_ips]'
  element :lockout_time, 'input[name=lockout_time]'
  element :max_page_loads, 'input[name=max_page_loads]'
  element :time_interval, 'input[name=time_interval]'
  elements :banishment_type, 'input[name=banishment_type]'
  element :banishment_url, 'input[name=banishment_url]'
  element :banishment_message, 'textarea[name=banishment_message]'

  def load
    settings_btn.click
    within 'div.sidebar' do
      click_link 'Access Throttling'
    end
  end
end
