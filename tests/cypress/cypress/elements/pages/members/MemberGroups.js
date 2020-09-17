import ControlPanel from '../ControlPanel'

class MemberGroups extends ControlPanel {
  constructor() {
      super()

      this.elements({

        'heading': 'div.col.w-12 form h1',
        'keyword_search': 'input[name=filter_by_keyword]',
        'new_group': '.sidebar h2 a[href$="cp/members/groups/create"]',
        'perpage_filter': 'div.filters a[data-filter-label^="show"]',

        'list.batch_actions': 'select[name=bulk_action]',
        'list.batch_submit': '.tbl-bulk-act button',
        'list.groups_table': 'table',
        'list.groups': 'form table tbody tr',
        'list.no_results': 'tr.no-results',

        'edit.submit': 'div.form-btns.form-btns-top input[type=submit]',

         // Fields
        'edit.name': 'input[name="group_title"]',
        'edit.description': 'textarea[name="group_description"]',
        'edit.is_locked': 'input[name="is_locked"]', // :visible => false
        'edit.is_locked_toggle': 'a[data-toggle-for=is_locked]',

        'edit.website_access': 'input[name="website_access[]"]',
        'edit.can_view_profiles': 'input[name="can_view_profiles"]', // :visible => false
        'edit.can_view_profiles_toggle': 'a[data-toggle-for=can_view_profiles]',

        'edit.can_delete_self': 'input[name="can_delete_self"]', // :visible => false
        'edit.can_delete_self_toggle': 'a[data-toggle-for=can_delete_self]',

        'edit.mbr_delete_notify_emails': 'input[name="mbr_delete_notify_emails"]',
        'edit.include_members_in': 'input[name="include_members_in[]"]',

        'edit.can_post_comments': 'input[name="can_post_comments"]', // :visible => false
        'edit.can_post_comments_toggle': 'a[data-toggle-for=can_post_comments]',

        'edit.exclude_from_moderation': 'input[name="exclude_from_moderation"]', // :visible => false
        'edit.exclude_from_moderation_toggle': 'a[data-toggle-for=exclude_from_moderation]', // :visible => false

        'edit.comment_actions': 'div[data-input-value="comment_actions"]', // :visible => false
        'edit.comment_actions_options': 'div[data-input-value="comment_actions"] input[type="checkbox"]', // :visible => false

        'edit.can_search': 'input[name="can_search"]', // :visible => false
        'edit.can_search_toggle': 'a[data-toggle-for=can_search]',

        'edit.search_flood_control': 'input[name="search_flood_control"]', // :visible => false

        'edit.can_send_private_messages': 'input[name="can_send_private_messages"]', // :visible => false
        'edit.can_send_private_messages_toggle': 'a[data-toggle-for=can_send_private_messages]',

        'edit.prv_msg_send_limit': 'input[name="prv_msg_send_limit"]', // :visible => false
        'edit.prv_msg_storage_limit': 'input[name="prv_msg_storage_limit"]', // :visible => false
        'edit.can_attach_in_private_messages': 'input[name="can_attach_in_private_messages"]', // :visible => false
        'edit.can_attach_in_private_messages_toggle': 'a[data-toggle-for=can_attach_in_private_messages]',
        'edit.can_send_bulletins': 'input[name="can_send_bulletins"]', // :visible => false
        'edit.can_send_bulletins_toggle': 'a[data-toggle-for=can_send_bulletins]',

        'edit.can_access_cp': 'input[name="can_access_cp"]', // :visible => false
        'edit.can_access_cp_toggle': 'a[data-toggle-for=can_access_cp]',

        'edit.cp_homepage': 'input[name="cp_homepage"]', // :visible => false
        'edit.footer_helper_links': 'div[data-input-value="footer_helper_links"]', // :visible => false
        'edit.footer_helper_links_options': 'div[data-input-value="footer_helper_links"] input[type="checkbox"]', // :visible => false

        'edit.can_view_homepage_news': 'input[name="can_view_homepage_news"]', // :visible => false
        'edit.can_view_homepage_news_toggle': 'a[data-toggle-for=can_view_homepage_news]', // :visible => false

        'edit.can_admin_channels': 'input[name="can_admin_channels"]', // :visible => false
        'edit.can_admin_channels_toggle': 'a[data-toggle-for=can_admin_channels]', // :visible => false

        'edit.channel_permissions': 'div[data-input-value="channel_permissions"]', // :visible => false
        'edit.channel_permissions_options': 'div[data-input-value="channel_permissions"] input[type="checkbox"]', // :visible => false
        'edit.channel_field_permissions': 'div[data-input-value="channel_field_permissions"]', // :visible => false
        'edit.channel_field_permissions_options': 'div[data-input-value="channel_field_permissions"] input[type="checkbox"]', // :visible => false
        'edit.channel_category_permissions': 'div[data-input-value="channel_category_permissions"]', // :visible => false
        'edit.channel_category_permissions_options': 'div[data-input-value="channel_category_permissions"] input[type="checkbox"]', // :visible => false
        'edit.channel_status_permissions': 'div[data-input-value="channel_status_permissions"]', // :visible => false
        'edit.channel_status_permissions_options': 'div[data-input-value="channel_status_permissions"] input[type="checkbox"]', // :visible => false

        'edit.channel_entry_actions': 'div[data-input-value="channel_entry_actions"]', // :visible => false
        'edit.channel_entry_actions_options': 'div[data-input-value="channel_entry_actions"] input[type="checkbox"]', // :visible => false

        'edit.allowed_channels': 'div[data-input-value="allowed_channels"]', // :visible => false
        'edit.allowed_channels_options': 'div[data-input-value="allowed_channels"] input[type="checkbox"]', // :visible => false

        'edit.can_access_files': 'input[name="can_access_files"]', // :visible => false
        'edit.can_access_files_toggle': 'a[data-toggle-for=can_access_files]', // :visible => false
        'edit.file_upload_directories': 'div[data-input-value="file_upload_directories"]', // :visible => false
        'edit.file_upload_directories_options': 'div[data-input-value="file_upload_directories"] input[type="checkbox"]', // :visible => false

        'edit.files': 'div[data-input-value="files"]', // :visible => false
        'edit.files_options': 'div[data-input-value="files"] input[type="checkbox"]', // :visible => false

        'edit.can_access_members': 'input[name="can_access_members"]', // :visible => false
        'edit.can_access_members_toggle': 'a[data-toggle-for=can_access_members]',
        'edit.can_admin_mbr_groups': 'input[name="can_admin_mbr_groups"]', // :visible => false
        'edit.can_admin_mbr_groups_toggle': 'a[data-toggle-for=can_admin_mbr_groups]',

        'edit.member_group_actions': 'div[data-input-value="member_group_actions"]', // :visible => false
        'edit.member_group_actions_options': 'div[data-input-value="member_group_actions"] input[type="checkbox"]', // :visible => false

        'edit.member_actions': 'div[data-input-value="member_actions"]', // :visible => false
        'edit.member_actions_options': 'div[data-input-value="member_actions"] input[type="checkbox"]', // :visible => false


        'edit.can_access_design': 'input[name="can_access_design"]', // :visible => false
        'edit.can_access_design_toggle': 'a[data-toggle-for=can_access_design]', // :visible => false
        'edit.can_admin_design': 'input[name="can_admin_design"]', // :visible => false
        'edit.can_admin_design_toggle': 'a[data-toggle-for=can_admin_design]', // :visible => false

        'edit.template_groups': 'div[data-input-value="template_group_permissions"]', // :visible => false
        'edit.template_groups_options': 'div[data-input-value="template_group_permissions"] input[type="checkbox"]', // :visible => false
        'edit.template_partials': 'div[data-input-value="template_partials"]', // :visible => false
        'edit.template_partials_options': 'div[data-input-value="template_partials"] input[type="checkbox"]', // :visible => false
        'edit.template_variables': 'div[data-input-value="template_variables"]', // :visible => false
        'edit.template_variables_options': 'div[data-input-value="template_variables"] input[type="checkbox"]', // :visible => false

        'edit.template_permissions': 'div[data-input-value="template_permissions"]', // :visible => false
        'edit.template_permissions_options': 'div[data-input-value="template_permissions"] input[type="checkbox"]', // :visible => false
        'edit.allowed_template_groups': 'div[data-input-value="allowed_template_groups"]', // :visible => false
        'edit.allowed_template_groups_options': 'div[data-input-value="allowed_template_groups"] input[type="checkbox"]', // :visible => false

        'edit.can_access_addons': 'input[name="can_access_addons"]', // :visible => false
        'edit.can_access_addons_toggle': 'a[data-toggle-for=can_access_addons]', // :visible => false
        'edit.can_admin_addons': 'input[name="can_admin_addons"]', // :visible => false
        'edit.can_admin_addons_toggle': 'a[data-toggle-for=can_admin_addons]', // :visible => false

        'edit.addons_access': 'div[data-input-value="addons_access"]', // :visible => false
        'edit.addons_access_options': 'div[data-input-value="addons_access"] input[type="checkbox"]', // :visible => false

        'edit.rte_toolsets': 'div[data-input-value="rte_toolsets"]', // :visible => false
        'edit.rte_toolsets_options': 'div[data-input-value="rte_toolsets"] input[type="checkbox"]', // :visible => false

        'edit.can_access_utilities': 'input[name="can_access_utilities"]', // :visible => false
        'edit.can_access_utilities_toggle': 'a[data-toggle-for=can_access_utilities]', // :visible => false
        'edit.access_tools': 'div[data-input-value="access_tools"]', // :visible => false
        'edit.access_tools_options': 'div[data-input-value="access_tools"] input[type="checkbox"]', // :visible => false

        'edit.can_access_logs': 'input[name="can_access_logs"]', // :visible => false
        'edit.can_access_logs_toggle': 'a[data-toggle-for=can_access_logs]', // :visible => false
        'edit.can_access_sys_prefs': 'input[name="can_access_sys_prefs"]', // :visible => false
        'edit.can_access_sys_prefs_toggle': 'a[data-toggle-for=can_access_sys_prefs]', // :visible => false
        'edit.can_access_security_settings': 'input[name="can_access_security_settings"]', // :visible => false
        'edit.can_access_security_settings_toggle': 'a[data-toggle-for=can_access_security_settings]', // :visible => false
      

        'label.channel_permissions_options': 'div[data-input-value="channel_permissions"] label[class = "act"]',
        'label.channel_entry_actions_options': 'div[data-input-value="channel_entry_actions"] label[class="act"]',
        'label.template_groups_options': 'div[data-input-value="template_group_permissions"] label[class = "act"]',
        'label.channel_permissions_options': 'div[data-input-value="channel_permissions"] label[class = "act"]',
        'label.channel_category_permissions_options': 'div[data-input-value="channel_category_permissions"] label[class = "act"]'
      })
  }

  load() {
    this.get('members_btn').click()
    this.get('wrap').find('a:contains("Member Groups")').click()
  }

  createNew(name){
    let description = name + ' description'
    this.load()
    this.get('new_group').click() 
    //new pages only allow group to view online website this is same as Guests
    this.get('edit.name').type(name)
    this.get('edit.description').type(description)
  }
}
export default MemberGroups;