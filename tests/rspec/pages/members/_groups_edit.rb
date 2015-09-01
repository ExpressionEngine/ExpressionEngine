class MemberGroupsEdit < SitePrism::Section
  element :submit, 'form.settings[action*="cp/members/groups"] input[type=submit]'

  # Fields
  element :name, 'input[name="group_title"]'
  element :description, 'textarea[name="group_description"]'
  elements :security_lock, 'input[name="is_locked"]'

  elements :website_access, 'input[name="website_access[]"]'
  elements :can_view_profiles, 'input[name="can_view_profiles"]'
  elements :can_send_email, 'input[name="can_send_email"]'
  elements :can_delete_self, 'input[name="can_delete_self"]'
  element :mbr_delete_notify_emails, 'input[name="mbr_delete_notify_emails"]'
  elements :include_members_in, 'input[name="include_members_in[]"]'

  elements :can_post_comments, 'input[name="can_post_comments"]'
  elements :exclude_from_moderation, 'input[name="exclude_from_moderation"]'
  elements :comment_actions, 'input[name="comment_actions[]"]'

  elements :can_search, 'input[name="can_search"]'
  element :search_flood_control, 'input[name="search_flood_control"]'

  elements :can_send_private_messages, 'input[name="can_send_private_messages"]'
  element :prv_msg_send_limit, 'input[name="prv_msg_send_limit"]'
  element :prv_msg_storage_limit, 'input[name="prv_msg_storage_limit"]'
  elements :can_attach_in_private_messages, 'input[name="can_attach_in_private_messages"]'
  elements :can_send_bulletins, 'input[name="can_send_bulletins"]'

  elements :can_access_cp, 'input[name="can_access_cp"]'
  elements :cp_homepage, 'input[name="cp_homepage"]'
  elements :footer_helper_links, 'input[name="footer_helper_links[]"]'

  elements :can_admin_channels, 'input[name="can_admin_channels"]'
  elements :category_actions, 'input[name="category_actions[]"]'

  elements :channel_entry_actions, 'input[name="channel_entry_actions[]"]'
  elements :allowed_channels, 'input[name="allowed_channels[]"]'

  elements :member_actions, 'input[name="member_actions[]"]'

  elements :can_admin_design, 'input[name="can_admin_design"]'
  elements :can_admin_templates, 'input[name="can_admin_templates"]'

  elements :allowed_template_groups, 'input[name="allowed_template_groups[]"]'

  elements :can_admin_modules, 'input[name="can_admin_modules"]'

  elements :addons_access, 'input[name="addons_access[]"]'

  elements :access_tools, 'input[name="access_tools[]"]'
end
