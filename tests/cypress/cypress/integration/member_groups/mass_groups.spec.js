/* This test will be designed to make automated mass member groups in ee5 and then upgrade to 
ee6 and make sure the same permissions are maintained during the transfer.*/
/*
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
      

*/






import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;



context('Create lots of groups to be transfered', () => {

	beforeEach(function() {
	    cy.auth();
	    page.load()
	    cy.hasNoErrors()
  	})

  it('verifies member groups page exists', () => {
  	page.get('heading').should('exist')
  	page.get('heading').contains('All Member Groups')
  })

  // it('', () => {

  // })

  //GENERAL ACCESS

  it('makes a locked group', () => {
  	let name = 'SecurityLock'
  	page.createNew(name)
  	page.get('edit.is_locked_toggle').click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Offline access', () => {
  	let name = 'OfflineAccess'
  	page.createNew(name)
  	page.get('edit.website_access').eq(1).click() //eq 1 is for the offline so they now have both online and offline access
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Access Public Profiles', () => {
  	let name = 'AccessProfiles'
  	page.createNew(name)
  	page.get('edit.can_view_profiles_toggle').click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })


  it('Can Delete Account', () => {
  	let name = 'DeleteAccount'
  	page.createNew(name)
  	page.get('edit.can_delete_self_toggle').click()
  	page.get('edit.mbr_delete_notify_emails').clear().type('test@test.com')
  	save()
  	add_members(name,1)
  	//verify(name)
  })


  it('Include in Authors', () => {
  	let name = 'AuthorsList'
  	page.createNew(name)
  	page.get('edit.include_members_in').eq(0).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Include in Members', () => {
  	let name = 'MembersList'
  	page.createNew(name)
  	page.get('edit.include_members_in').eq(1).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Include in Both Lists', () => {
  	let name = 'BothLists'
  	page.createNew(name)
  	page.get('edit.include_members_in').eq(0).click()
  	page.get('edit.include_members_in').eq(1).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })


  //COMMENTING


  it('Can Submit Comments', () => {
  	let name = 'SubmitComments'
  	page.createNew(name)
  	page.get('edit.can_post_comments_toggle').click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })


  it('Can Bypass Comment Moderation', () => {
  	let name = 'CommentModerationBypass'
  	page.createNew(name)
  	page.get('edit.can_post_comments_toggle').click()
  	cy.wait(500)//wait for the bypass toggle to show up (only appears if can comment is clicked)
  	page.get('edit.exclude_from_moderation_toggle').click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Moderate Comments', () => {
  	let name = 'ModerateComments'
  	page.createNew(name)
  	page.get('edit.can_post_comments_toggle').click()
  	page.get('edit.comment_actions_options').eq(0).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Edit Own Comments', () => {
  	let name = 'EditOwnComments'
  	page.createNew(name)
  	page.get('edit.can_post_comments_toggle').click()
  	page.get('edit.comment_actions_options').eq(1).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Delete Own Comments', () => {
  	let name = 'DeleteOwnComments'
  	page.createNew(name)
  	page.get('edit.can_post_comments_toggle').click()
  	page.get('edit.comment_actions_options').eq(2).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Edit Other Comments', () => {
  	let name = 'EditOtherComments'
  	page.createNew(name)
  	page.get('edit.can_post_comments_toggle').click()
  	page.get('edit.comment_actions_options').eq(3).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Delete Other Comments', () => {
  	let name = 'DeleteOtherComments'
  	page.createNew(name)
  	page.get('edit.can_post_comments_toggle').click()
  	page.get('edit.comment_actions_options').eq(4).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

//SEARCH

  it('Access Search Util Default Time', () => {
  	let name = 'AccessSearch'
  	page.createNew(name)
  	page.get('edit.can_search_toggle').click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Access Search Util Default Time Limit 5', () => {
  	let name = 'AccessSearchTimeLimit'
  	page.createNew(name)
  	page.get('edit.can_search_toggle').click()
  	cy.wait(500)
  	page.get('edit.search_flood_control').type('5')
  	save()
  	add_members(name,1)
  	//verify(name)
  })

//Personal Messages
  it('Access Personal Messages Default', () => {
  	let name = 'AccessMessagesDefault'
  	page.createNew(name)
  	page.get('edit.can_send_private_messages_toggle').click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Access Personal Messages Send Limit 5 Store Limit 5', () => {
  	let name = 'AccessMessagesLimits'
  	page.createNew(name)
  	page.get('edit.can_send_private_messages_toggle').click()
  	cy.wait(500)
  	page.get('edit.prv_msg_send_limit').type('5')
  	page.get('edit.prv_msg_storage_limit').type('5')
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Can Attach in Messages',() => {
  	let name = 'AttachMessages'
  	page.createNew(name)
  	page.get('edit.can_send_private_messages_toggle').click()
  	cy.wait(500)
  	page.get('edit.can_attach_in_private_messages_toggle').click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Bullitens in Messages',() => {
  	let name = 'SentBullitens'
  	page.createNew(name)
  	page.get('edit.can_send_private_messages_toggle').click()
  	cy.wait(500)
  	page.get('edit.can_send_bulletins_toggle').click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

// CHANNEL ENTRIES

  it('Create News Entries',() => {
  	let name = 'CreateNews'
  	page.createNew(name)
  	page.get('edit.allowed_channels_options').eq(0).click() //News
  	page.get('edit.channel_entry_actions_options').eq(0).click() //Create
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Edit Own News Entries',() => {
  	let name = 'EditOwnNews'
  	page.createNew(name)
  	page.get('edit.allowed_channels_options').eq(0).click() //News
  	page.get('edit.channel_entry_actions_options').eq(1).click() //Edit own
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Delete Own News Entries',() => {
  	let name = 'DeleteOwnNews'
  	page.createNew(name)
  	page.get('edit.allowed_channels_options').eq(0).click() //News
  	page.get('edit.channel_entry_actions_options').eq(2).click() //Create
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Edit Others News Entries',() => {
  	let name = 'EditOthersNews'
  	page.createNew(name)
  	page.get('edit.allowed_channels_options').eq(0).click() //News
  	page.get('edit.channel_entry_actions_options').eq(3).click() //Create
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Delete Others News Entries',() => {
  	let name = 'DeleteOthersNews'
  	page.createNew(name)
  	page.get('edit.allowed_channels_options').eq(0).click() //News
  	page.get('edit.channel_entry_actions_options').eq(4).click() //Create
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Change Authros News Entries',() => {
  	let name = 'ChangeAuthorNews'
  	page.createNew(name)
  	page.get('edit.allowed_channels_options').eq(0).click() //News
  	page.get('edit.channel_entry_actions_options').eq(5).click() //Create
  	save()
  	add_members(name,1)
  	//verify(name)
  })

// CP

  it('Can Access CP Only',() => {
  	let name = 'CPOnly'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Can Access CP All Footers',() => {
  	let name = 'CPAllFooters'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.footer_helper_links_options').eq(0).click()
  	page.get('edit.footer_helper_links_options').eq(1).click()
  	page.get('edit.footer_helper_links_options').eq(2).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  

  it('Can Access CP News',() => {
  	let name = 'CPNews'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	page.get('edit.can_view_homepage_news_toggle').click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  //CP -- Channels
  it('Can Access All Channels',() => {
  	let name = 'CPChannels'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_admin_channels_toggle').click()
  	cy.wait(500)
  	page.get('edit.channel_permissions_options').eq(0).click()
  	page.get('edit.channel_permissions_options').eq(1).click()
  	
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Can Access All Fields',() => {
  	let name = 'CPFields'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_admin_channels_toggle').click()
  	cy.wait(500)
  	page.get('edit.channel_field_permissions_options').eq(0).click()

  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Can Access All Categories',() => {
  	let name = 'CPCategories'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_admin_channels_toggle').click()
  	cy.wait(500)
  	page.get('edit.channel_category_permissions_options').eq(0).click()
  	page.get('edit.channel_category_permissions_options').eq(1).click()
  	page.get('edit.channel_category_permissions_options').eq(2).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })





  it('Can Access All Status',() => {
  	let name = 'CPStatus'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_admin_channels_toggle').click()
  	cy.wait(500)
  	page.get('edit.channel_status_permissions_options').eq(0).click()
  	page.get('edit.channel_status_permissions_options').eq(1).click()
  	page.get('edit.channel_status_permissions_options').eq(2).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  //CP FILES (After files all are in back order)

  it('Can Upload Directories',() => {
  	let name = 'AllDirectories'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_files_toggle').click()
  	cy.wait(500)
  	page.get('edit.file_upload_directories_options').eq(0).click()
  	page.get('edit.file_upload_directories_options').eq(1).click()
  	page.get('edit.file_upload_directories_options').eq(2).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

  it('Can Upload Files',() => {
  	let name = 'AllFiles'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_files_toggle').click()
  	cy.wait(500)
  	page.get('edit.files_options').eq(0).click()
  	page.get('edit.files_options').eq(1).click()
  	page.get('edit.files_options').eq(2).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

//CP MEMBERS
it('Can Access Members Groups/Roles',() => {
  	let name = 'GroupAccess'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_members_toggle').click()
  	page.get('edit.can_admin_mbr_groups_toggle').click()
  	cy.wait(500)
  	page.get('edit.member_group_actions_options').eq(0).click()
  	page.get('edit.member_group_actions_options').eq(1).click()
  	page.get('edit.member_group_actions_options').eq(2).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

it('Can Access Members',() => {
  	let name = 'MemberAccess'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_members_toggle').click()
  	cy.wait(500)
  	page.get('edit.member_actions_options').eq(0).click()
  	page.get('edit.member_actions_options').eq(1).click()
  	page.get('edit.member_actions_options').eq(2).click()

  	page.get('edit.member_actions_options').eq(3).click()
  	page.get('edit.member_actions_options').eq(4).click()
  	page.get('edit.member_actions_options').eq(5).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

it('Has all access to members/groups', () => {
	let name = 'MemberAndGroup'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_members_toggle').click()
  	page.get('edit.can_admin_mbr_groups_toggle').click()
  	cy.wait(500)
  	page.get('edit.member_actions_options').eq(0).click()
  	page.get('edit.member_actions_options').eq(1).click()
  	page.get('edit.member_actions_options').eq(2).click()

  	page.get('edit.member_actions_options').eq(3).click()
  	page.get('edit.member_actions_options').eq(4).click()
  	page.get('edit.member_actions_options').eq(5).click()

  	page.get('edit.member_group_actions_options').eq(0).click()
  	page.get('edit.member_group_actions_options').eq(1).click()
  	page.get('edit.member_group_actions_options').eq(2).click()
  	save()
  	add_members(name,1)
  	//verify(name)
})


//CP TEMPLATES
it('Has access to all Templates', () => {
	let name = 'AllTemplates'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	cy.get(':nth-child(50) > .field-control > .toggle-btn').click()
  	cy.get(':nth-child(51) > .field-control > .toggle-btn').click()
  	page.get('edit.template_groups_options').eq(0).click()
  	page.get('edit.template_groups_options').eq(1).click()
  	page.get('edit.template_groups_options').eq(2).click()

  	page.get('edit.template_partials_options').eq(0).click()
  	page.get('edit.template_partials_options').eq(1).click()
  	page.get('edit.template_partials_options').eq(2).click()

  	page.get('edit.template_variables_options').eq(0).click()
  	page.get('edit.template_variables_options').eq(1).click()
  	page.get('edit.template_variables_options').eq(2).click()

  	page.get('edit.template_permissions_options').eq(0).click()
  	page.get('edit.template_permissions_options').eq(1).click()
  	page.get('edit.template_permissions_options').eq(2).click()

  	page.get('edit.allowed_template_groups_options').eq(0).click()
  	page.get('edit.allowed_template_groups_options').eq(1).click()
  	page.get('edit.allowed_template_groups_options').eq(2).click()
  	page.get('edit.allowed_template_groups_options').eq(3).click()


  	save()
  	add_members(name,1)
  	//verify(name)
})

it('Has access to all but only the news Templates', () => {
	let name = 'OnlyNewsTemplates'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	cy.get(':nth-child(50) > .field-control > .toggle-btn').click()
  	cy.get(':nth-child(51) > .field-control > .toggle-btn').click()
  	page.get('edit.template_groups_options').eq(0).click()
  	page.get('edit.template_groups_options').eq(1).click()
  	page.get('edit.template_groups_options').eq(2).click()

  	page.get('edit.template_partials_options').eq(0).click()
  	page.get('edit.template_partials_options').eq(1).click()
  	page.get('edit.template_partials_options').eq(2).click()

  	page.get('edit.template_variables_options').eq(0).click()
  	page.get('edit.template_variables_options').eq(1).click()
  	page.get('edit.template_variables_options').eq(2).click()

  	page.get('edit.template_permissions_options').eq(0).click()
  	page.get('edit.template_permissions_options').eq(1).click()
  	page.get('edit.template_permissions_options').eq(2).click()

  	
  	page.get('edit.allowed_template_groups_options').eq(2).click()


 
  	save()
  	add_members(name,1)
  	//verify(name)
})

//CP Addons

it('Can Access addons',() => {
  	let name = 'BasicAddons'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_addons_toggle').click()//turn on addons
  	save()
  	add_members(name,1)
  	//verify(name)
  })

it('Can In/Un stall Access addons',() => {
  	let name = 'AddonsInstaller'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_addons_toggle').click()//turn on addons
  	cy.wait(500)
  	cy.get(':nth-child(59) > .field-control > .toggle-btn').click() //turn on in/unstall
  	save()
  	add_members(name,1)
  	//verify(name)
  })

it('Can Get All Addons with in/un',() => {
  	let name = 'AddonsInstallerALL'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_addons_toggle').click()//turn on addons
  	cy.wait(500)
  	cy.get(':nth-child(59) > .field-control > .toggle-btn').click() //turn on in/unstall
  	page.get('edit.addons_access_options').eq(0).click()
  	page.get('edit.addons_access_options').eq(1).click()
  	page.get('edit.addons_access_options').eq(2).click()
  	page.get('edit.addons_access_options').eq(3).click()
  	page.get('edit.addons_access_options').eq(4).click()
  

  	save()
  	add_members(name,1)
  	//verify(name)
  })

it('Addons all access',() => {
  	let name = 'allAddons'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_addons_toggle').click()//turn on addons
  	cy.wait(500)
  	cy.get(':nth-child(59) > .field-control > .toggle-btn').click() //turn on in/unstall
  	page.get('edit.addons_access_options').eq(0).click()
  	page.get('edit.addons_access_options').eq(1).click()
  	page.get('edit.addons_access_options').eq(2).click()
  	page.get('edit.addons_access_options').eq(3).click()
  	page.get('edit.addons_access_options').eq(4).click()
  	

  	page.get('edit.rte_toolsets_options').eq(0).click()
  	page.get('edit.rte_toolsets_options').eq(1).click()
  	page.get('edit.rte_toolsets_options').eq(2).click()

  	save()
  	add_members(name,1)
  	//verify(name)
  })




//CP Utils

it('Can Access Communication',() => {
  	let name = 'CommunicationUtils'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_utilities_toggle').click()
  	cy.wait(500)
  	page.get('edit.access_tools_options').eq(1).click()
  	page.get('edit.access_tools_options').eq(2).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

it('Can Access Translation',() => {
  	let name = 'TranslationUtils'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_utilities_toggle').click()
  	cy.wait(500)
  	page.get('edit.access_tools_options').eq(3).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

it('Can Access Import',() => {
  	let name = 'ImportUtils'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_utilities_toggle').click()
  	cy.wait(500)
  	page.get('edit.access_tools_options').eq(4).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

it('Can Access SQL Management',() => {
  	let name = 'SQLManUtils'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_utilities_toggle').click()
  	cy.wait(500)
  	page.get('edit.access_tools_options').eq(5).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })

it('Can Access Data Operations',() => {
  	let name = 'DataOperUtils'
  	page.createNew(name)
  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
  	cy.wait(500)
  	page.get('edit.can_access_utilities_toggle').click()
  	cy.wait(500)
  	page.get('edit.access_tools_options').eq(6).click()
  	save()
  	add_members(name,1)
  	//verify(name)
  })



//CP Logs
	it('Can Access All Logs',() => {
	  	let name = 'AllLogs'
	  	page.createNew(name)
	  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
	  	cy.wait(500)
	  	page.get('edit.can_access_logs_toggle').click()
	  	save()
	  	add_members(name,1)
	  	//verify(name)
	 })
	

//CP Settings

	it('Can Access Settings',() => {
	  	let name = 'BasicSettings'
	  	page.createNew(name)
	  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
	  	cy.wait(500)
	  	cy.get(':nth-child(68) > .field-control > .toggle-btn').click()
	  	save()
	  	add_members(name,1)
	  	//verify(name)
	 })

	it('Can Access Security and Basics',() => {
	  	let name = 'SecuritySettings'
	  	page.createNew(name)
	  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
	  	cy.wait(500)
	  	cy.get(':nth-child(68) > .field-control > .toggle-btn').click()
	  	page.get('edit.can_access_security_settings_toggle').click()
	  	save()
	  	add_members(name,1)
	  	//verify(name)
	 })

	it('Can Access All',() => {
	  	let name = 'AllSettings'
	  	page.createNew(name)
	  	page.get('edit.can_access_cp_toggle').click() //turn on CP access
	  	cy.wait(500)
	  	cy.get(':nth-child(68) > .field-control > .toggle-btn').click()
	  	page.get('edit.can_access_security_settings_toggle').click()
	  	cy.get(':nth-child(70) > .field-control > .toggle-btn').click()
	  	save()
	  	add_members(name,1)
	  	//verify(name)
	 })







})//context

function save(){
	page.get('edit.submit').click()
}

function verify(name){
	let expect = name + ' (1)'
	page.load()
	cy.get('.has-sub').click()
	cy.get('.sub-menu > ul > :nth-child(6) > a').click() //click show all instead of show 25
	cy.get('.w-12').contains(expect)
}


function add_members(group, count){
	let i = 1;
	for(i ; i <= count; i++){
		member.load() //goes to member creation url
		cy.get('label').contains(group).find('input').click()
		let email = group;
		email += i.toString();
		email += "@test.com";
		let username = group + i.toString();
		member.get('username').clear().type(username)
	    member.get('email').clear().type(email)
	    member.get('password').clear().type('password')
	    member.get('confirm_password').clear().type('password')
	    
	    cy.get(".form-btns-auth > .fieldset-required > .field-control > input").then($button => {
		  if ($button.is(':visible')){
		    cy.get(".form-btns-auth > .fieldset-required > .field-control > input").type('password');
		  }
		}) 
	    member.get('save_and_new_button').click()
	}
}