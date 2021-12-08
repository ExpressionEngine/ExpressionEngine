/// <reference types="Cypress" />

import MemberGroups from '../../elements/pages/members/MemberGroups';
import SiteForm from '../../elements/pages/site/SiteForm';
import SiteManager from '../../elements/pages/site/SiteManager';

const page = new MemberGroups

context('Member Group List', () => {

  before(function(){
    cy.task('db:seed')
  })


  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the Member Group List page', () => {
    //page.all_there?.should('eq', true
    page.get('list.groups_table').should('exist')
    page.get('list.groups').should('exist')
  })

  // Confirming phrase search
  it('searches by phrases', () => {
    // Be sane and make sure it's there before we search for it
    page.get('wrap').contains('Super Admin')

    page.get('keyword_search').clear().type("Super Admin")
    page.get('keyword_search').type('{enter}')

    //page.get('page_heading').contains('we found 1 results for "Super Admin"')
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal("Super Admin")})
    page.get('wrap').contains('Super Admin')
    page.get('list.groups').should('have.length', 1)
  })

  it('shows no results on a failed search', () => {
    const our_action = 'BadMemberGroup'
    page.get('keyword_search').type(our_action)
    page.get('keyword_search').type('{enter}')

    page.get('wrap').contains('No Roles found')
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal(our_action)})

    page.get('keyword_search').should('exist')
    page.get('perpage_filter').should('exist')

    page.get('pagination').should('not.exist')
  })

  it('cannot delete built-in groups', () => {
    let opts = ['Super Admin', 'Banned', 'Guests', 'Pending', 'Members']
    opts.forEach(function(group) {
      page.get('list.groups').contains(group).parent().find('input[type="checkbox"]').check();
      page.get('list.batch_actions').select('Delete')
      page.get('list.batch_submit').click();
      cy.get('.modal-confirm-delete').should('be.visible')
      cy.get('.modal-confirm-delete .button--primary').click()
      page.get('alert_important').contains('Cannot Delete Roles');
      page.get('alert_important').contains('The following roles could not be deleted')
      page.get('alert_important').contains(group)
    })
    
  }) 


    it('creates a group successfully', () => {
      create_member_group()
      
      page.get('edit.name').invoke('val').then((val) => { expect(val).to.be.equal('Moderators')})
      page.get('edit.description').invoke('val').then((val) => { expect(val).to.be.equal('Moderators description.')})
      page.get('edit.is_locked').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      cy.get('button[rel=t-1]').click()
      page.get('edit.website_access').each(function(el){ expect(el).to.be.checked })
      page.get('edit.can_view_profiles').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.can_delete_self').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.mbr_delete_notify_emails').invoke('val').then((val) => { expect(val).to.be.equal('team@expressionengine.com')})
      page.get('edit.include_in_authorlist').each(function(el){ expect(el).to.be.checked})
      page.get('edit.include_in_memberlist').each(function(el){ expect(el).to.be.checked})
      page.get('edit.can_post_comments').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.exclude_from_moderation').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.comment_actions_options').each(function(el){ expect(el).to.be.checked})
      page.get('edit.can_search').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.search_flood_control').invoke('val').then((val) => { expect(val).to.be.equal('60')})
      page.get('edit.can_send_private_messages').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.prv_msg_send_limit').invoke('val').then((val) => { expect(val).to.be.equal('50')})
      page.get('edit.prv_msg_storage_limit').invoke('val').then((val) => { expect(val).to.be.equal('100')})
      page.get('edit.can_attach_in_private_messages').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.can_send_bulletins').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      cy.get('button[rel=t-2]').click()
      page.get('edit.can_access_cp').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.cp_homepage').eq(1).should('be.checked')
      page.get('edit.footer_helper_links_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.channel_entry_actions_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.member_actions_options').each(function(el){ expect(el).to.be.checked })
      //page.get('edit.allowed_channels_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.can_admin_design').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.allowed_template_groups_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.can_admin_addons').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.addons_access_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.access_tools_options').each(function(el){ expect(el).to.be.checked })
    })

    it('can delete new member group', () => {
      
      cy.addMembers('Moderators', 1)

      page.load()
      
      page.get('list.groups').contains('Moderators').parent().find('input[type="checkbox"]').check();
      page.get('list.batch_actions').select('Delete')
      page.get('list.batch_submit').click();
      cy.get('.modal-confirm-delete').should('be.visible')
      cy.wait(1000)
      cy.get('.modal-confirm-delete').find('select[name=replacement]').select('5')
      cy.get('.modal-confirm-delete .button--primary').click()
      page.get('alert_success').contains('Success');
      page.get('alert_success').contains('The following roles were deleted')
      page.get('alert_success').contains('Moderators');
      
    }) 

  it('when editing a member group', () => {

      create_member_group()

      page.get('edit.name').clear().type('Editors')
      page.get('edit.description').clear().type('Editors description.')
      page.get('edit.is_locked_toggle').click()

      page.get('edit.save_dropdown').click()
      cy.wait(1000)
      page.get('edit.submit').last().click({waitForAnimations: false})

      page.get('list.groups').find('a:contains("Editors")').click()

      //page.get('list').all_there?.should('eq', false
      page.get('edit.name').should('exist')
      page.get('edit.description').should('exist')
      page.get('edit.is_locked').should('exist')

      page.get('edit.name').invoke('val').then((val) => { expect(val).to.be.equal('Editors')})
      page.get('edit.description').invoke('val').then((val) => { expect(val).to.be.equal('Editors description.')})
      page.get('edit.is_locked').invoke('val').then((val) => { expect(val).to.be.equal('n')})

  })


  context.skip('when using MSM', () => {
    //this is different with member roles, so need to be updated. Skipping for now
    before(function(){
        cy.task('db:seed')
        cy.eeConfig({item: 'multiple_sites_enabled', value: 'y'})
        cy.auth();
        page.load()
        cy.hasNoErrors()
        create_msm_site()
        create_member_group()
    })

    beforeEach(function(){
      
      
      
    })

    it('creates member groups for other sites', () => {
      cy.task('db:query', 'SELECT count(role_id) AS count FROM exp_roles WHERE role_id=6').then(([result, fields]) => {
        result.forEach(function(row){
          expect(row.count).to.be.equal(1)
        });
      })
    })

    it('edits the preferences for specific sites', () => {
      edit_member_group()

      let rows = []
      let fields = 'name, group_description, is_locked, can_create_template_groups, can_edit_template_groups, can_delete_template_groups, can_access_comm, can_access_translate, can_access_data, can_access_logs'
      cy.task('db:query', 'SELECT '+fields+' FROM exp_roles WHERE role_id=6').then(([result, fields]) => {
        result.forEach(function(row){
          rows.push(row);
        });

        // These two fields should change among all groups
        cy.log("These two fields should change among all groups")
        expect(rows[0].group_title).eq(rows[1].group_title)
        expect(rows[0].group_description).eq(rows[1].group_description)
        expect(rows[0].is_locked).eq(rows[1].is_locked)

        // These fields should *not* change among all groups
        cy.log("These fields should *not* change among all groups")
        expect(rows[0].can_edit_template_groups).not.eq(rows[1].can_edit_template_groups)
        expect(rows[0].can_access_comm).not.eq(rows[1].can_access_comm)
        expect(rows[0].can_access_translate).not.eq(rows[1].can_access_translate)

        // These fields were not changed and should remain the same
        cy.log("These fields should *not* change among all groups")
        expect(rows[0].can_access_data).eq(rows[1].can_access_data)
        expect(rows[0].can_access_logs).eq(rows[1].can_access_logs)

      })
    })

    it('deletes all member group records when deleting a member group', () => {
      page.load()
      page.get('list.groups').last().find('input[type="checkbox"]').click()
      page.get('list.batch_actions').select('Delete')
      page.get('list.batch_submit').click()

      cy.wait(1000)

      page.get('modal').find('form[action$="cp/members/groups/delete"] [type="submit"]').click()

      page.get('list.groups_table').should('exist')
      page.get('list.groups').should('exist')
      page.get('list.groups').should('have.length', 5)
      page.get('alert').invoke('text').then((text) => { expect(text).to.not.match(/[a-z]_[a-z]/) })

      cy.task('db:query', 'SELECT count(group_id) AS count FROM exp_member_groups WHERE group_id=6').then(([result, fields]) => {
        result.forEach(function(row){
          expect(row.count).to.be.equal(0)
        });
      })

    })
  })

  function create_member_group() {
    page.get('new_group').click()

    //page.edit.all_there?.should('eq', true
    page.get('edit.name').should('exist')
    page.get('edit.description').should('exist')
    page.get('edit.is_locked').should('exist')

    page.get('edit.name').clear().type('Moderators')
    page.get('edit.description').clear().type('Moderators description.')
    page.get('edit.is_locked_toggle').click()
    cy.get('button[rel=t-1]').click()
    page.get('edit.website_access').eq(1).click()
    page.get('edit.can_view_profiles_toggle').click()
    page.get('edit.can_delete_self_toggle').click()
    page.get('edit.mbr_delete_notify_emails').clear().type('team@expressionengine.com')
    page.get('edit.include_in_authorlist').check()
    page.get('edit.include_in_memberlist').check()
    page.get('edit.can_post_comments_toggle').click()
    page.get('edit.exclude_from_moderation_toggle').click()
    page.get('edit.comment_actions_options').check()
    page.get('edit.can_search_toggle').click()
    page.get('edit.search_flood_control').clear().type('60')
    page.get('edit.can_send_private_messages_toggle').click()
    page.get('edit.prv_msg_send_limit').clear().type('50')
    page.get('edit.prv_msg_storage_limit').clear().type('100')
    page.get('edit.can_attach_in_private_messages_toggle').click()
    page.get('edit.can_send_bulletins_toggle').click()
    cy.get('button[rel=t-2]').click()
    page.get('edit.can_access_cp_toggle').click()
    page.get('edit.cp_homepage').eq(1).click()
    page.get('edit.footer_helper_links_options').check()
    page.get('edit.can_admin_channels_toggle').click()
    page.get('edit.channel_permissions_options').check()
    page.get('edit.channel_category_permissions_options').check()
    page.get('edit.channel_entry_actions_options').check()
    page.get('edit.can_access_files_toggle').click()
    page.get('edit.can_access_members_toggle').click()
    page.get('edit.member_actions_options').check()
    //page.get('edit.allowed_channels_options').each(function(el) {el.click()})
    page.get('edit.can_access_design_toggle').click()
    page.get('edit.can_admin_design_toggle').click()
    page.get('edit.template_groups_options').check()
    page.get('edit.allowed_template_groups_options').check({force: true})
    page.get('edit.can_access_addons_toggle').click()
    page.get('edit.can_admin_addons_toggle').click()
    page.get('edit.addons_access_options').check()
    page.get('edit.can_access_utilities_toggle').click()
    page.get('edit.access_tools_options').check()
    page.get('edit.can_access_sys_prefs_toggle').click()
    page.get('edit.save_dropdown').click()
    cy.wait(1000)
    page.get('edit.submit').last().click({waitForAnimations: false})

    page.get('list.groups').find('a:contains("Moderators")').click()

    //page.get('list').all_there?.should('eq', false
    page.get('edit.name').should('exist')
    page.get('edit.description').should('exist')
    page.get('edit.is_locked').should('exist')
  }

  function edit_member_group() {
    page.get('edit.name').clear().type('Editors')
    page.get('edit.description').clear().type('Editors description.')
    page.get('edit.is_locked_toggle').click()
    page.get('edit.template_groups_options').check({force: true})
    page.get('edit.allowed_template_groups_options').eq(1).click()
    page.get('edit.access_tools_options').eq(0).click()
    page.get('edit.access_tools_options').eq(3).click()
    page.get('edit.submit').first().click({waitForAnimations: false})

    page.get('list.groups').find('a:contains("Editors")').click()

    //page.get('list').all_there?.should('eq', false
    page.get('edit.name').should('exist')
    page.get('edit.description').should('exist')
    page.get('edit.is_locked').should('exist')

    page.get('edit.name').invoke('val').then((val) => { expect(val).to.be.equal('Editors')})
    page.get('edit.description').invoke('val').then((val) => { expect(val).to.be.equal('Editors description.')})
    page.get('edit.is_locked').invoke('val').then((val) => { expect(val).to.be.equal('n') })
    page.get('edit.template_groups_options').each(function(el) { expect(el).not.to.be.checked })
    page.get('edit.allowed_template_groups_options').eq(1).should('not.be.checked')
    page.get('edit.access_tools_options').eq(0).should('not.be.checked')
    page.get('edit.access_tools_options').eq(3).should('not.be.checked')
    page.get('edit.access_tools_options').eq(4).should('be.checked')
    page.get('edit.access_tools_options').eq(5).should('be.checked')
  }

  function create_msm_site() {
    const siteManager = new SiteManager;
    siteManager.load();

    cy.get('.main-nav a').contains('Add Site').first().click()

    const form = new SiteForm
    form.add_site({
      name: 'Second Site',
      short_name: 'second_site'
    })

    page.load()
  }
})
