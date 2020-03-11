class MemberSettings < ControlPanelPage

  element :allow_member_registration_toggle, 'a[data-toggle-for=allow_member_registration]'
  element :allow_member_registration, 'input[name=allow_member_registration]', :visible => false
  elements :req_mbr_activation, 'input[name=req_mbr_activation]'
  element :require_terms_of_service_toggle, 'a[data-toggle-for=require_terms_of_service]'
  element :require_terms_of_service, 'input[name=require_terms_of_service]', :visible => false
  element :allow_member_localization_toggle, 'a[data-toggle-for=allow_member_localization]'
  element :allow_member_localization, 'input[name=allow_member_localization]', :visible => false
  elements :default_member_group, 'input[name=default_member_group]'
  elements :member_theme, 'input[name=member_theme]'
  elements :memberlist_order_by, 'input[name=memberlist_order_by]'
  elements :memberlist_sort_order, 'input[name=memberlist_sort_order]'
  elements :memberlist_row_limit, 'input[name=memberlist_row_limit]'
  element :new_member_notification_toggle, 'a[data-toggle-for=new_member_notification]'
  element :new_member_notification, 'input[name=new_member_notification]', :visible => false
  element :mbr_notification_emails, 'input[name=mbr_notification_emails]'

  load
    settings_btn.click
    within 'div.sidebar' do
      click_link 'Members'
    }
  }
}
