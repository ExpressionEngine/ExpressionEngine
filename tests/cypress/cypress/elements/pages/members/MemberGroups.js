import ControlPanel from '../ControlPanel'

class MemberGroups extends ControlPanel {
  constructor() {
      super()

      this.elements({

        'heading': 'div.col.w-12 form h1',
        'keyword_search': 'input[type!=hidden][name=filter_by_keyword]',
        'new_group': '.main-nav__toolbar a[href$="cp/members/roles/create"]',
        'perpage_filter': '.filter-bar [data-filter-label^="show"]',

        'list.batch_actions': 'select[name=bulk_action]',
        'list.batch_submit': '.bulk-action-bar button',
        'list.groups_table': '.list-group',
        'list.groups': '.list-group .list-item',
        'list.no_results': 'tr.no-results',

        'edit.save_dropdown': '.title-bar__extra-tools .js-dropdown-toggle',
        'edit.submit': '.title-bar__extra-tools button[type=submit][name=submit]',

         // Fields
        'edit.name': 'input[type!=hidden][name="name"]',
        'edit.description': 'textarea[name="description"]',
        'edit.is_locked': 'input[name="is_locked"]', // :visible => false
        'edit.is_locked_toggle': '[data-toggle-for=is_locked]',

        'edit.website_access': 'input[type!=hidden][name="website_access[]"]',
        'edit.can_view_profiles': 'input[name="can_view_profiles"]', // :visible => false
        'edit.can_view_profiles_toggle': '[data-toggle-for=can_view_profiles]',

        'edit.can_delete_self': 'input[name="can_delete_self"]', // :visible => false
        'edit.can_delete_self_toggle': '[data-toggle-for=can_delete_self]',

        'edit.mbr_delete_notify_emails': 'input[type!=hidden][name="mbr_delete_notify_emails"]',
        'edit.include_in_authorlist': 'input[type!=hidden][name="include_in_authorlist"]',
        'edit.include_in_memberlist': 'input[type!=hidden][name="include_in_memberlist"]',

        'edit.can_post_comments': 'input[name="can_post_comments"]', // :visible => false
        'edit.can_post_comments_toggle': '[data-toggle-for=can_post_comments]',

        'edit.exclude_from_moderation': 'input[name="exclude_from_moderation"]', // :visible => false
        'edit.exclude_from_moderation_toggle': '[data-toggle-for=exclude_from_moderation]', // :visible => false

        'edit.comment_actions': 'div[data-input-value="comment_actions"]', // :visible => false
        'edit.comment_actions_options': 'div[data-input-value="comment_actions"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.can_search': 'input[name="can_search"]', // :visible => false
        'edit.can_search_toggle': '[data-toggle-for=can_search]',

        'edit.search_flood_control': 'input[name="search_flood_control"]', // :visible => false

        'edit.can_send_private_messages': 'input[name="can_send_private_messages"]', // :visible => false
        'edit.can_send_private_messages_toggle': '[data-toggle-for=can_send_private_messages]',

        'edit.prv_msg_send_limit': 'input[name="prv_msg_send_limit"]', // :visible => false
        'edit.prv_msg_storage_limit': 'input[name="prv_msg_storage_limit"]', // :visible => false
        'edit.can_attach_in_private_messages': 'input[name="can_attach_in_private_messages"]', // :visible => false
        'edit.can_attach_in_private_messages_toggle': '[data-toggle-for=can_attach_in_private_messages]',
        'edit.can_send_bulletins': 'input[name="can_send_bulletins"]', // :visible => false
        'edit.can_send_bulletins_toggle': '[data-toggle-for=can_send_bulletins]',

        'edit.can_access_cp': 'input[name="can_access_cp"]', // :visible => false
        'edit.can_access_cp_toggle': '[data-toggle-for=can_access_cp]',

        'edit.cp_homepage': 'input[name="cp_homepage"]', // :visible => false
        'edit.footer_helper_links': 'div[data-input-value="footer_helper_links"]', // :visible => false
        'edit.footer_helper_links_options': 'div[data-input-value="footer_helper_links"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.can_view_homepage_news': 'input[name="can_view_homepage_news"]', // :visible => false
        'edit.can_view_homepage_news_toggle': '[data-toggle-for=can_view_homepage_news]', // :visible => false

        'edit.can_admin_channels': 'input[name="can_admin_channels"]', // :visible => false
        'edit.can_admin_channels_toggle': '[data-toggle-for=can_admin_channels]', // :visible => false

        'edit.channel_permissions': 'div[data-input-value="channel_permissions"]', // :visible => false
        'edit.channel_permissions_options': 'div[data-input-value="channel_permissions"] .field-inputs input[type="checkbox"]', // :visible => false
        'edit.channel_field_permissions': 'div[data-input-value="channel_field_permissions"]', // :visible => false
        'edit.channel_field_permissions_options': 'div[data-input-value="channel_field_permissions"] .field-inputs input[type="checkbox"]', // :visible => false
        'edit.channel_category_permissions': 'div[data-input-value="channel_category_permissions"]', // :visible => false
        'edit.channel_category_permissions_options': 'div[data-input-value="channel_category_permissions"] .field-inputs input[type="checkbox"]', // :visible => false
        'edit.channel_status_permissions': 'div[data-input-value="channel_status_permissions"]', // :visible => false
        'edit.channel_status_permissions_options': 'div[data-input-value="channel_status_permissions"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.channel_entry_actions': 'div[data-input-value="channel_access"]', // :visible => false
        'edit.channel_entry_actions_options': 'div[data-input-value="channel_access"] .field-inputs input[type="checkbox"]', // :visible => false

        //'edit.allowed_channels': 'div[data-input-value="allowed_channels"]', // :visible => false
        //'edit.allowed_channels_options': 'div[data-input-value="allowed_channels"] input[type="checkbox"]', // :visible => false

        'edit.can_access_files': 'input[name="can_access_files"]', // :visible => false
        'edit.can_access_files_toggle': '[data-toggle-for=can_access_files]', // :visible => false
        'edit.file_upload_directories': 'div[data-input-value="file_upload_directories"]', // :visible => false
        'edit.file_upload_directories_options': 'div[data-input-value="file_upload_directories"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.files': 'div[data-input-value="files"]', // :visible => false
        'edit.files_options': 'div[data-input-value="files"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.can_access_members': 'input[name="can_access_members"]', // :visible => false
        'edit.can_access_members_toggle': '[data-toggle-for=can_access_members]',
        'edit.can_admin_mbr_groups': 'input[name="can_admin_mbr_groups"]', // :visible => false
        'edit.can_admin_mbr_groups_toggle': '[data-toggle-for=can_admin_mbr_groups]',

        'edit.member_group_actions': 'div[data-input-value="member_group_actions"]', // :visible => false
        'edit.member_group_actions_options': 'div[data-input-value="member_group_actions"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.member_actions': 'div[data-input-value="member_actions"]', // :visible => false
        'edit.member_actions_options': 'div[data-input-value="member_actions"] .field-inputs input[type="checkbox"]', // :visible => false


        'edit.can_access_design': 'input[name="can_access_design"]', // :visible => false
        'edit.can_access_design_toggle': '[data-toggle-for=can_access_design]', // :visible => false
        'edit.can_admin_design': 'input[name="can_admin_design"]', // :visible => false
        'edit.can_admin_design_toggle': '[data-toggle-for=can_admin_design]', // :visible => false

        'edit.template_groups': 'div[data-input-value="template_group_permissions"]', // :visible => false
        'edit.template_groups_options': 'div[data-input-value="template_group_permissions"] .field-inputs input[type="checkbox"]', // :visible => false
        'edit.template_partials': 'div[data-input-value="template_partials"]', // :visible => false
        'edit.template_partials_options': 'div[data-input-value="template_partials"] .field-inputs input[type="checkbox"]', // :visible => false
        'edit.template_variables': 'div[data-input-value="template_variables"]', // :visible => false
        'edit.template_variables_options': 'div[data-input-value="template_variables"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.template_permissions': 'div[data-input-value="template_permissions"]', // :visible => false
        'edit.template_permissions_options': 'div[data-input-value="template_permissions"] .field-inputs input[type="checkbox"]', // :visible => false
        'edit.allowed_template_groups': 'div[data-input-value="template_group_access"]', // :visible => false
        'edit.allowed_template_groups_options': 'div[data-input-value="template_group_access"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.can_access_addons': 'input[name="can_access_addons"]', // :visible => false
        'edit.can_access_addons_toggle': '[data-toggle-for=can_access_addons]', // :visible => false
        'edit.can_admin_addons': 'input[name="can_admin_addons"]', // :visible => false
        'edit.can_admin_addons_toggle': '[data-toggle-for=can_admin_addons]', // :visible => false

        'edit.addons_access': 'div[data-input-value="addons_access"]', // :visible => false
        'edit.addons_access_options': 'div[data-input-value="addons_access"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.rte_toolsets': 'div[data-input-value="rte_toolsets"]', // :visible => false
        'edit.rte_toolsets_options': 'div[data-input-value="rte_toolsets"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.can_access_utilities': 'input[name="can_access_utilities"]', // :visible => false
        'edit.can_access_utilities_toggle': '[data-toggle-for=can_access_utilities]', // :visible => false
        'edit.access_tools': 'div[data-input-value="access_tools"]', // :visible => false
        'edit.access_tools_options': 'div[data-input-value="access_tools"] .field-inputs input[type="checkbox"]', // :visible => false

        'edit.can_access_logs': 'input[name="can_access_logs"]', // :visible => false
        'edit.can_access_logs_toggle': '[data-toggle-for=can_access_logs]', // :visible => false
        'edit.can_access_sys_prefs': 'input[name="can_access_sys_prefs"]', // :visible => false
        'edit.can_access_sys_prefs_toggle': '[data-toggle-for=can_access_sys_prefs]', // :visible => false
        'edit.can_access_security_settings': 'input[name="can_access_security_settings"]', // :visible => false
        'edit.can_access_security_settings_toggle': '[data-toggle-for=can_access_security_settings]', // :visible => false
      })
  }

  load() {
    this.get('members_btn').click()
    this.get('wrap').find('a:contains("Member Roles")').click()
  }
}
export default MemberGroups;
