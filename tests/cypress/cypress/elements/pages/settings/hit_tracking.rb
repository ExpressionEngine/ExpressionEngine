class HitTracking < ControlPanelPage
  element :enable_online_user_tracking_toggle, 'a[data-toggle-for=enable_online_user_tracking]'
  element :enable_online_user_tracking, 'input[name=enable_online_user_tracking]', :visible => false
  element :enable_hit_tracking_toggle, 'a[data-toggle-for=enable_hit_tracking]'
  element :enable_hit_tracking, 'input[name=enable_hit_tracking]', :visible => false
  element :enable_entry_view_tracking_toggle, 'a[data-toggle-for=enable_entry_view_tracking]'
  element :enable_entry_view_tracking, 'input[name=enable_entry_view_tracking]', :visible => false
  element :dynamic_tracking_disabling, 'input[name=dynamic_tracking_disabling]'

  load
    settings_btn.click
    within 'div.sidebar' do
      click_link 'Hit Tracking'
    }
  }
}
