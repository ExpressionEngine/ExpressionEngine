class BansMembers < MemberManagerPage

  element :banned_ips, 'textarea[name=banned_ips]'
  element :banned_emails, 'textarea[name=banned_emails]'
  element :banned_usernames, 'textarea[name=banned_usernames]'
  element :banned_screen_names, 'textarea[name=banned_screen_names]'
  element :ban_action, 'div[data-input-value="ban_action"]'
  elements :ban_action_options, 'div[data-input-value="ban_action"] input[type="radio"]'
  element :ban_message, 'textarea[name=ban_message]'
  element :ban_destination, 'input[name=ban_destination]'

  def load
    main_menu.members_btn.click
    click_link 'Ban Management'
  end
end
