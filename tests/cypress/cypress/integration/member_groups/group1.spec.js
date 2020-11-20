/* This test will be designed to make automated mass member groups in ee5 and then upgrade to 
ee6 and make sure the same permissions are maintained during the transfer.*/

import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;

context('Import File Converter', () => {

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it.skip('verifies member groups page exists', () => {
  	page.get('heading').should('exist')
  	page.get('heading').contains('All Member Groups')
  })

  it.skip('creates Moderators (a member group for conversion testing into ee6 roles)', () => {
    create_moderator_group() //Moderators
    page.load()
    page.get('list.groups').last().contains('Moderators (0)')

  })

  it.skip('adds members to Moderators', () => {
  	 add_members('Moderators',3,true)
  	 page.load()
  	 page.get('list.groups').last().contains('Moderators (3)')
  })

  it.skip('creates Writers (a member group for conversion into ee6 roles)', () => {
    create_writer_group('Writers') //Writers will be just like Moderators but this group will be unable to delete anything this group will be tested once the upgrade to 6 that they still cannot delete things
    page.load()
    page.get('list.groups').last().contains('Writers (0)')
  })

  it.skip('adds members to Writers', () => {
  	add_members('Writers',5,true)
  	page.load()
  	page.get('list.groups').last().contains('Writers (5)')
  })

  it.skip('adds members to Guest', () => {
  	add_members('Guest', 2,false)
  	page.load()
  	page.get('list.groups').contains('Guests (2)')
  })

  it.skip('Creates bulk groups for transfer', () => {
  	var AccessLevel = [Comment];
  	bulk_create("Commenting_Only", AccessLevel,null)
  	page.get('list.groups').contains('Commenting_Only (0)')

  	AccessLevel = [CP];
  	bulk_create("CP_Only", AccessLevel,"Access to CP and default pg. being All entries edit listing")
  	page.get('list.groups').contains('CP_Only (0)')

  	add_members('Commenting_Only',3,false)
    cy.visit('http://localhost:8888/admin.php?/cp/members/groups')
  	page.get('list.groups').contains('Commenting_Only (3)')

  	add_members('CP_Only',2,true)
    cy.visit('http://localhost:8888/admin.php?/cp/members/groups')
  	page.get('list.groups').contains('CP_Only (2)')
  })

})

function bulk_create(Name, AccessLevel,Description){
	page.createNew()
	page.get('edit.name').clear().type(Name)
	if(Description == null){ 
		page.get('edit.description').clear().type(Name + ' description.') //auto generate description
	}else{
		page.get('edit.description').clear().type(Description)
	}
	
	page.get('edit.is_locked_toggle').click() //anything with shield access should be locked

	/*All of these are 'non sheild' items meaning that they can be
  	 assigned to people that you do not trust implicitly*/
  	page.get('edit.can_view_profiles_toggle').click()
    page.get('edit.can_delete_self_toggle').click()
    page.get('edit.mbr_delete_notify_emails').clear().type('team@ellislab.com')
    page.get('edit.include_members_in').each(function(el) {el.click()})
    page.get('edit.can_post_comments_toggle').click()
   

    page.get('edit.can_search_toggle').click()
    page.get('edit.search_flood_control').clear().type('60')
    page.get('edit.can_send_private_messages_toggle').click()
    page.get('edit.prv_msg_send_limit').clear().type('50')
    page.get('edit.prv_msg_storage_limit').clear().type('100')

    page.get('edit.can_attach_in_private_messages_toggle').click()
    page.get('edit.can_send_bulletins_toggle').click()

    //Begin Sheild settings
    let i = 0;
    for(i; i< AccessLevel.length; i++){
    	AccessLevel[i]();
    }

    page.get('edit.submit').click()
}

/*
	This will let the group be allowed to post unmotiored and do anything with comments
*/
function Comment(){
	page.get('edit.exclude_from_moderation_toggle').click()
    page.get('edit.comment_actions_options').each(function(el) {el.click()})
}

function CP(){
	page.get('edit.can_access_cp_toggle').click()
    page.get('edit.cp_homepage').eq(1).click() //Edit listing default
}



//add member(s) to the group passed in
function add_members(group, count, admin_required){
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
	    if(admin_required){
	    	member.get('admin_password').clear().type('password')
	    }
	    member.get('save_and_new_button').click()
	}
}
	function create_writer_group(group_name){
    page.get('new_group').click()
    //page.edit.all_there?.should('eq', true
    page.get('edit.name').should('exist')
    page.get('edit.description').should('exist')
    page.get('edit.is_locked').should('exist')
    page.get('edit.name').clear().type(group_name)
    page.get('edit.description').clear().type(group_name + ' description.')
    page.get('edit.is_locked_toggle').click()
    page.get('edit.website_access').eq(1).click()

    page.get('edit.can_view_profiles_toggle').click()
    page.get('edit.can_delete_self_toggle').click()
    page.get('edit.mbr_delete_notify_emails').clear().type('team@ellislab.com')
    page.get('edit.include_members_in').each(function(el) {el.click()})
    page.get('edit.can_post_comments_toggle').click()
   

    page.get('edit.can_search_toggle').click()
    page.get('edit.search_flood_control').clear().type('60')
    page.get('edit.can_send_private_messages_toggle').click()
    page.get('edit.prv_msg_send_limit').clear().type('50')
    page.get('edit.prv_msg_storage_limit').clear().type('100')

    page.get('edit.can_attach_in_private_messages_toggle').click()
    page.get('edit.can_send_bulletins_toggle').click()
    page.get('edit.can_access_cp_toggle').click()
    page.get('edit.cp_homepage').eq(2).click() //Publish form News  CP homepage = news
    page.get('edit.footer_helper_links_options').each(function(el) {el.click()})
    page.get('edit.can_admin_channels_toggle').click()

    page.get('edit.channel_permissions_options').each(function(el) {el.click()})
    page.get('label.channel_permissions_options').contains('Delete channels').click()
    page.get('edit.channel_category_permissions_options').each(function(el) {el.click()})
    page.get('label.channel_category_permissions_options').contains('Delete categories').click()
    //page.get('label.channel_permissions_options').contains('Delete').click()  
    page.get('edit.channel_entry_actions_options').each(function(el) {el.click()})
    page.get('label.channel_entry_actions_options').contains('Edit entries, by others').click()
    page.get('label.channel_entry_actions_options').contains('Delete entries, by others').click()
    page.get('label.channel_entry_actions_options').contains('Change entry author').click()
    //page.get('edit.can_access_files_toggle').click()
    page.get('edit.can_access_members_toggle').click()
    //page.get('edit.member_actions_options').each(function(el) {el.click()})
    page.get('edit.allowed_channels_options').each(function(el) {el.click()})
    page.get('edit.can_access_design_toggle').click()
    page.get('edit.can_admin_design_toggle').click()
    page.get('edit.template_groups_options').each(function(el) {el.click()})
    page.get('label.template_groups_options').contains('Delete groups').click()

    page.get('edit.allowed_template_groups_options').each(function(el) {el.click()})

    page.get('edit.can_access_addons_toggle').click()
    page.get('edit.can_admin_addons_toggle').click()
    page.get('edit.addons_access_options').each(function(el) {el.click()})

    page.get('edit.can_access_utilities_toggle').click()
    page.get('edit.access_tools_options').each(function(el) {el.click()})
    page.get('edit.can_access_sys_prefs_toggle').click()
    page.get('edit.submit').click()
 }


 function create_moderator_group() {
    page.get('new_group').click()
    //page.edit.all_there?.should('eq', true
    page.get('edit.name').should('exist')
    page.get('edit.description').should('exist')
    page.get('edit.is_locked').should('exist')
    page.get('edit.name').clear().type('Moderators')
    page.get('edit.description').clear().type('Moderators description.')
    page.get('edit.is_locked_toggle').click()
    page.get('edit.website_access').eq(1).click()
    page.get('edit.can_view_profiles_toggle').click()
    page.get('edit.can_delete_self_toggle').click()
    page.get('edit.mbr_delete_notify_emails').clear().type('team@ellislab.com')
    page.get('edit.include_members_in').each(function(el) {el.click()})
    page.get('edit.can_post_comments_toggle').click()
    page.get('edit.exclude_from_moderation_toggle').click()
    page.get('edit.comment_actions_options').each(function(el) {el.click()})
    page.get('edit.can_search_toggle').click()
    page.get('edit.search_flood_control').clear().type('60')
    page.get('edit.can_send_private_messages_toggle').click()
    page.get('edit.prv_msg_send_limit').clear().type('50')
    page.get('edit.prv_msg_storage_limit').clear().type('100')
    page.get('edit.can_attach_in_private_messages_toggle').click()
    page.get('edit.can_send_bulletins_toggle').click()
    page.get('edit.can_access_cp_toggle').click()
    page.get('edit.cp_homepage').eq(0).click()
    page.get('edit.footer_helper_links_options').each(function(el) {el.click()})
    page.get('edit.can_admin_channels_toggle').click()
    page.get('edit.channel_permissions_options').each(function(el) {el.click()})
    page.get('edit.channel_category_permissions_options').each(function(el) {el.click()})
    page.get('edit.channel_entry_actions_options').each(function(el) {el.click()})
    page.get('edit.can_access_files_toggle').click()
    page.get('edit.can_access_members_toggle').click()
    page.get('edit.member_actions_options').each(function(el) {el.click()})
    page.get('edit.allowed_channels_options').each(function(el) {el.click()})
    page.get('edit.can_access_design_toggle').click()
    page.get('edit.can_admin_design_toggle').click()
    page.get('edit.template_groups_options').each(function(el) {el.click()})
    page.get('edit.allowed_template_groups_options').each(function(el) {el.click()})
    page.get('edit.can_access_addons_toggle').click()
    page.get('edit.can_admin_addons_toggle').click()
    page.get('edit.addons_access_options').each(function(el) {el.click()})
    page.get('edit.can_access_utilities_toggle').click()
    page.get('edit.access_tools_options').each(function(el) {el.click()})
    page.get('edit.can_access_sys_prefs_toggle').click()
    page.get('edit.submit').click()
    page.get('list.groups').last().find('li.edit a').click()
    page.get('edit.name').should('exist')
    page.get('edit.description').should('exist')
    page.get('edit.is_locked').should('exist')
  }