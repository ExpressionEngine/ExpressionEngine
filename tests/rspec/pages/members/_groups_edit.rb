class MemberGroupsEdit < SitePrism::Section
  element :submit, 'form.settings[action*="cp/members/groups"] input[type=submit]'

  # Fields
  element :name, 'input[name="group_title"]'
  element :description, 'textarea[name="group_description"]'
  elements :security_lock, 'input[name="is_locked"]'

  elements :website_access, 'input[name="website_access[]"]'
  elements :can_view_profiles, 'input[name="can_view_profiles"]'
  elements :can_delete_self, 'input[name="can_delete_self"]'
  element :mbr_delete_notify_emails, 'input[name="mbr_delete_notify_emails"]'
  elements :include_members_in, 'input[name="include_members_in[]"]'

  elements :can_post_comments, 'input[name="can_post_comments"]'
  elements :exclude_from_moderation, 'input[name="exclude_from_moderation"]', visible: false
  elements :comment_actions, 'input[name="comment_actions[]"]'

  elements :can_search, 'input[name="can_search"]'
  element :search_flood_control, 'input[name="search_flood_control"]', visible: false

  elements :can_send_private_messages, 'input[name="can_send_private_messages"]'
  element :prv_msg_send_limit, 'input[name="prv_msg_send_limit"]', visible: false
  element :prv_msg_storage_limit, 'input[name="prv_msg_storage_limit"]', visible: false
  elements :can_attach_in_private_messages, 'input[name="can_attach_in_private_messages"]', visible: false
  elements :can_send_bulletins, 'input[name="can_send_bulletins"]', visible: false

  elements :can_access_cp, 'input[name="can_access_cp"]'
  elements :cp_homepage, 'input[name="cp_homepage"]', visible: false
  elements :footer_helper_links, 'input[name="footer_helper_links[]"]', visible: false

  elements :can_admin_channels, 'input[name="can_admin_channels"]', visible: false
  elements :channel_permissions, 'input[name="channel_permissions[]"]', visible: false
  elements :channel_field_permissions, 'input[name="channel_field_permissions[]"]', visible: false
  elements :channel_category_permissions, 'input[name="channel_category_permissions[]"]', visible: false
  elements :channel_status_permissions, 'input[name="channel_status_permissions[]"]', visible: false

  elements :channel_entry_actions, 'input[name="channel_entry_actions[]"]'
  elements :allowed_channels, 'input[name="allowed_channels[]"]'

  elements :can_access_files, 'input[name="can_access_files"]', visible: false
  elements :file_upload_directories, 'input[name="file_upload_directories[]"]', visible: false
  elements :files, 'input[name="files[]"]', visible: false

  elements :can_access_members, 'input[name="can_access_members"]', visible: false
  elements :can_admin_mbr_groups, 'input[name="can_admin_mbr_groups"]', visible: false
  elements :member_group_actions, 'input[name="member_group_actions[]"]', visible: false
  elements :member_actions, 'input[name="member_actions[]"]', visible: false

  elements :can_access_design, 'input[name="can_access_design"]', visible: false
  elements :can_admin_design, 'input[name="can_admin_design"]', visible: false
  elements :template_groups, 'input[name="template_group_permissions[]"]', visible: false
  elements :template_partials, 'input[name="template_partials[]"]', visible: false
  elements :template_variables, 'input[name="template_variables[]"]', visible: false

  elements :template_permissions, 'input[name="template_permissions[]"]', visible: false
  elements :allowed_template_groups, 'input[name="allowed_template_groups[]"]', visible: false

  elements :can_access_addons, 'input[name="can_access_addons"]', visible: false
  elements :can_admin_addons, 'input[name="can_admin_addons"]', visible: false
  elements :addons_access, 'input[name="addons_access[]"]', visible: false
  elements :rte_toolsets, 'input[name="rte_toolsets[]"]', visible: false

  elements :can_access_utilities, 'input[name="can_access_utilities"]', visible: false
  elements :access_tools, 'input[name="access_tools[]"]', visible: false
  elements :can_access_logs, 'input[name="can_access_logs"]', visible: false
  elements :can_access_sys_prefs, 'input[name="can_access_sys_prefs"]', visible: false
  elements :can_access_security_settings, 'input[name="can_access_security_settings"]', visible: false
end
