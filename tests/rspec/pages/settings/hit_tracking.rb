class HitTracking < ControlPanelPage
  elements :enable_online_user_tracking, 'input[name=enable_online_user_tracking]'
  elements :enable_hit_tracking, 'input[name=enable_hit_tracking]'
  elements :enable_entry_view_tracking, 'input[name=enable_entry_view_tracking]'
  element :dynamic_tracking_disabling, 'input[name=dynamic_tracking_disabling]'

  def load
    settings_btn.click
    within 'div.sidebar' do
      click_link 'Hit Tracking'
    end
  end
end
