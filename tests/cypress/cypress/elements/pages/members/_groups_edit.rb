class MemberGroupsEdit < SitePrism::Section
  element :submit, 'div.form-btns.form-btns-top input[type=submit]'

  # Fields
  element :name, 'input[name="group_title"]'
  element :description, 'textarea[name="group_description"]'
  element :is_locked, 'input[name="is_locked"]', :visible => false
  element :is_locked_toggle, 'a[data-toggle-for=is_locked]'

  elements :website_access, 'input[name="website_access[]"]'
  element :can_view_profiles, 'input[name="can_view_profiles"]', :visible => false
  element :can_view_profiles_toggle, 'a[data-toggle-for=can_view_profiles]'

  element :can_delete_self, 'input[name="can_delete_self"]', :visible => false
  element :can_delete_self_toggle, 'a[data-toggle-for=can_delete_self]'

  element :mbr_delete_notify_emails, 'input[name="mbr_delete_notify_emails"]'
  elements :include_members_in, 'input[name="include_members_in[]"]'

  element :can_post_comments, 'input[name="can_post_comments"]', :visible => false
  element :can_post_comments_toggle, 'a[data-toggle-for=can_post_comments]'

  element :exclude_from_moderation, 'input[name="exclude_from_moderation"]', :visible => false
  element :exclude_from_moderation_toggle, 'a[data-toggle-for=exclude_from_moderation]', :visible => false

  element :comment_actions, 'div[data-input-value="comment_actions"]', :visible => false
  elements :comment_actions_options, 'div[data-input-value="comment_actions"] input[type="checkbox"]', :visible => false

  element :can_search, 'input[name="can_search"]', :visible => false
  element :can_search_toggle, 'a[data-toggle-for=can_search]'

  element :search_flood_control, 'input[name="search_flood_control"]', :visible => false

  element :can_send_private_messages, 'input[name="can_send_private_messages"]', :visible => false
  element :can_send_private_messages_toggle, 'a[data-toggle-for=can_send_private_messages]'

  element :prv_msg_send_limit, 'input[name="prv_msg_send_limit"]', :visible => false
  element :prv_msg_storage_limit, 'input[name="prv_msg_storage_limit"]', :visible => false
  element :can_attach_in_private_messages, 'input[name="can_attach_in_private_messages"]', :visible => false
  element :can_attach_in_private_messages_toggle, 'a[data-toggle-for=can_attach_in_private_messages]'
  element :can_send_bulletins, 'input[name="can_send_bulletins"]', :visible => false
  element :can_send_bulletins_toggle, 'a[data-toggle-for=can_send_bulletins]'

  element :can_access_cp, 'input[name="can_access_cp"]', :visible => false
  element :can_access_cp_toggle, 'a[data-toggle-for=can_access_cp]'

  elements :cp_homepage, 'input[name="cp_homepage"]', :visible => false
  element :footer_helper_links, 'div[data-input-value="footer_helper_links"]', :visible => false
  elements :footer_helper_links_options, 'div[data-input-value="footer_helper_links"] input[type="checkbox"]', :visible => false

  element :can_view_homepage_news, 'input[name="can_view_homepage_news"]', :visible => false
  element :can_view_homepage_news_toggle, 'a[data-toggle-for=can_view_homepage_news]', :visible => false

  element :can_admin_channels, 'input[name="can_admin_channels"]', :visible => false
  element :can_admin_channels_toggle, 'a[data-toggle-for=can_admin_channels]', :visible => false

  element :channel_permissions, 'div[data-input-value="channel_permissions"]', :visible => false
  elements :channel_permissions_options, 'div[data-input-value="channel_permissions"] input[type="checkbox"]', :visible => false
  element :channel_field_permissions, 'div[data-input-value="channel_field_permissions"]', :visible => false
  elements :channel_field_permissions_options, 'div[data-input-value="channel_field_permissions"] input[type="checkbox"]', :visible => false
  element :channel_category_permissions, 'div[data-input-value="channel_category_permissions"]', :visible => false
  elements :channel_category_permissions_options, 'div[data-input-value="channel_category_permissions"] input[type="checkbox"]', :visible => false
  element :channel_status_permissions, 'div[data-input-value="channel_status_permissions"]', :visible => false
  elements :channel_status_permissions_options, 'div[data-input-value="channel_status_permissions"] input[type="checkbox"]', :visible => false

  element :channel_entry_actions, 'div[data-input-value="channel_entry_actions"]', :visible => false
  elements :channel_entry_actions_options, 'div[data-input-value="channel_entry_actions"] input[type="checkbox"]', :visible => false

  element :allowed_channels, 'div[data-input-value="allowed_channels"]', :visible => false
  elements :allowed_channels_options, 'div[data-input-value="allowed_channels"] input[type="checkbox"]', :visible => false

  element :can_access_files, 'input[name="can_access_files"]', :visible => false
  element :can_access_files_toggle, 'a[data-toggle-for=can_access_files]', :visible => false
  element :file_upload_directories, 'div[data-input-value="file_upload_directories"]', :visible => false
  elements :file_upload_directories_options, 'div[data-input-value="file_upload_directories"] input[type="checkbox"]', :visible => false

  element :files, 'div[data-input-value="files"]', :visible => false
  elements :files_options, 'div[data-input-value="files"] input[type="checkbox"]', :visible => false

  element :can_access_members, 'input[name="can_access_members"]', :visible => false
  element :can_access_members_toggle, 'a[data-toggle-for=can_access_members]'
  element :can_admin_mbr_groups, 'input[name="can_admin_mbr_groups"]', :visible => false
  element :can_admin_mbr_groups_toggle, 'a[data-toggle-for=can_admin_mbr_groups]'

  element :member_group_actions, 'div[data-input-value="member_group_actions"]', :visible => false
  elements :member_group_actions_options, 'div[data-input-value="member_group_actions"] input[type="checkbox"]', :visible => false

  element :member_actions, 'div[data-input-value="member_actions"]', :visible => false
  elements :member_actions_options, 'div[data-input-value="member_actions"] input[type="checkbox"]', :visible => false


  element :can_access_design, 'input[name="can_access_design"]', :visible => false
  element :can_access_design_toggle, 'a[data-toggle-for=can_access_design]', :visible => false
  element :can_admin_design, 'input[name="can_admin_design"]', :visible => false
  element :can_admin_design_toggle, 'a[data-toggle-for=can_admin_design]', :visible => false

  element :template_groups, 'div[data-input-value="template_groups"]', :visible => false
  elements :template_groups_options, 'div[data-input-value="template_groups"] input[type="checkbox"]', :visible => false
  element :template_partials, 'div[data-input-value="template_partials"]', :visible => false
  elements :template_partials_options, 'div[data-input-value="template_partials"] input[type="checkbox"]', :visible => false
  element :template_variables, 'div[data-input-value="template_variables"]', :visible => false
  elements :template_variables_options, 'div[data-input-value="template_variables"] input[type="checkbox"]', :visible => false

  element :template_permissions, 'div[data-input-value="template_permissions"]', :visible => false
  elements :template_permissions_options, 'div[data-input-value="template_permissions"] input[type="checkbox"]', :visible => false
  element :allowed_template_groups, 'div[data-input-value="allowed_template_groups"]', :visible => false
  elements :allowed_template_groups_options, 'div[data-input-value="allowed_template_groups"] input[type="checkbox"]', :visible => false

  element :can_access_addons, 'input[name="can_access_addons"]', :visible => false
  element :can_access_addons_toggle, 'a[data-toggle-for=can_access_addons]', :visible => false
  element :can_admin_addons, 'input[name="can_admin_addons"]', :visible => false
  element :can_admin_addons_toggle, 'a[data-toggle-for=can_admin_addons]', :visible => false

  element :addons_access, 'div[data-input-value="addons_access"]', :visible => false
  elements :addons_access_options, 'div[data-input-value="addons_access"] input[type="checkbox"]', :visible => false

  element :rte_toolsets, 'div[data-input-value="rte_toolsets"]', :visible => false
  elements :rte_toolsets_options, 'div[data-input-value="rte_toolsets"] input[type="checkbox"]', :visible => false

  element :can_access_utilities, 'input[name="can_access_utilities"]', :visible => false
  element :can_access_utilities_toggle, 'a[data-toggle-for=can_access_utilities]', :visible => false
  element :access_tools, 'div[data-input-value="access_tools"]', :visible => false
  elements :access_tools_options, 'div[data-input-value="access_tools"] input[type="checkbox"]', :visible => false

  element :can_access_logs, 'input[name="can_access_logs"]', :visible => false
  element :can_access_logs_toggle, 'a[data-toggle-for=can_access_logs]', :visible => false
  element :can_access_sys_prefs, 'input[name="can_access_sys_prefs"]', :visible => false
  element :can_access_sys_prefs_toggle, 'a[data-toggle-for=can_access_sys_prefs]', :visible => false
  element :can_access_security_settings, 'input[name="can_access_security_settings"]', :visible => false
  element :can_access_security_settings_toggle, 'a[data-toggle-for=can_access_security_settings]', :visible => false
}
