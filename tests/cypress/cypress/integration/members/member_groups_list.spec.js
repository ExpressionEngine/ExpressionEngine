/// <reference types="Cypress" />

import MemberGroups from '../../elements/pages/members/MemberGroups';
import SiteForm from '../../elements/pages/site/SiteForm';

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

    page.get('page_heading').contains('we found 1 results for "Super Admin"')
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal("Super Admin")})
    page.get('wrap').contains('Super Admin')
    page.get('list.groups').should('have.length', 1)
  })

  it('shows no results on a failed search', () => {
    const our_action = 'BadMemberGroup'
    page.get('keyword_search').type(our_action)
    page.get('keyword_search').type('{enter}')

    page.get('page_heading').contains('we found 0 results for "' + our_action + '"')
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal(our_action)})
    page.get('wrap').contains(our_action)

    page.get('list.no_results').should('exist')
    page.get('keyword_search').should('exist')
    page.get('perpage_filter').should('exist')

    page.get('pagination').should('not.exist')
  })

  context('when creating a member group', () => {
    beforeEach(function(){
      create_member_group()
    })

    it('creates a group successfully', () => {
      page.get('edit.name').invoke('val').then((val) => { expect(val).to.be.equal('Moderators')})
      page.get('edit.description').invoke('val').then((val) => { expect(val).to.be.equal('Moderators description.')})
      page.get('edit.is_locked').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.website_access').each(function(el){ expect(el).to.be.checked })
      page.get('edit.can_view_profiles').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.can_delete_self').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.mbr_delete_notify_emails').invoke('val').then((val) => { expect(val).to.be.equal('team@expressionengine.com')})
      page.get('edit.include_members_in').each(function(el){ expect(el).to.be.checked})
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
      page.get('edit.can_access_cp').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.cp_homepage').eq(1).should('be.checked')
      page.get('edit.footer_helper_links_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.channel_entry_actions_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.member_actions_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.allowed_channels_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.can_admin_design').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.allowed_template_groups_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.can_admin_addons').invoke('val').then((val) => { expect(val).to.be.equal('y')})
      page.get('edit.addons_access_options').each(function(el){ expect(el).to.be.checked })
      page.get('edit.access_tools_options').each(function(el){ expect(el).to.be.checked })
    })
  })

  context('when editing a member group', () => {
    beforeEach(function(){
      create_member_group()

      page.get('edit.name').clear().type('Editors')
      page.get('edit.description').clear().type('Editors description.')
      page.get('edit.is_locked_toggle').click()

      submit_form()

      page.get('edit.name').invoke('val').then((val) => { expect(val).to.be.equal('Editors')})
      page.get('edit.description').invoke('val').then((val) => { expect(val).to.be.equal('Editors description.')})
      page.get('edit.is_locked').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    })
  })

    /*it('toggles member group checkbox permissions', () => {
      skip "nested group permission visibility is not working properly, revisit when fixed" do
      }

      toggle_state = {}

      checkboxes = %w(
        website_access
        include_members_in
        comment_actions_options
        footer_helper_links_options
        channel_permissions_options
        channel_field_permissions_options
        channel_category_permissions_options
        channel_status_permissions_options
        channel_entry_actions_options
        allowed_channels_options
        file_upload_directories_options
        files_options
        member_group_actions_options
        member_actions_options
        template_groups_options
        template_partials_options
        template_variables_options
        template_permissions_options
        allowed_template_groups_options
        addons_access_options
        rte_toolsets_options
        access_tools_options
      )

      checkboxes.each do |permission_name|
        toggle_state[permission_name] = {}
        page.edit.send(permission_name).each_with_index do |permission, index|
          toggle_state[permission_name][index] = permission.checked?
          permission.click()
        }
      }

      submit_form

      checkboxes.each do |permission_name|
        page.edit.send(permission_name).each_with_index do |permission, index|
          toggle_state[permission_name][index].should('not.eq', permission.checked?
          permission.click()
        }
      }

      submit_form

      checkboxes.each do |permission_name|
        page.edit.send(permission_name).each_with_index do |permission, index|
          toggle_state[permission_name][index].should('eq', permission.checked?
        }
      }

    }*/

    /*it('toggles toggle permissions successfully', () => {
      skip "nested group permission visibility is not working properly, revisit when fixed" do
      }

      toggles = [
        'can_view_profiles',
        'can_delete_self',
        ['can_post_comments', 'exclude_from_moderation'],
        'can_search',
        ['can_send_private_messages', 'can_attach_in_private_messages', 'can_send_bulletins'],
        'can_admin_channels',
        'can_access_files',
        ['can_access_members', 'can_admin_mbr_groups'],
        ['can_access_design', 'can_admin_design'],
        ['can_access_addons', 'can_admin_addons'],
        'can_access_utilities',
        'can_access_logs',
        ['can_access_sys_prefs', 'can_access_security_settings']
      ]

      // CP actions on this group are already visible
      #page.get('edit.can_access_cp_toggle').click()
      toggles.each do |toggle|
        if toggle.is_a? Array
          // first element controls group visibility, and should already be enabled
          toggle.each_with_index do |r, idx|
            if idx != 0
              page.edit.send(r + '_toggle').click()
            }
          }
        else
          page.edit.send(toggle + '_toggle').click()
        }
      }
      submit_form

      page.get('edit.can_access_cp').invoke('val').then((val) => { expect(val).to.be.equal('y'
      toggles.each do |toggle|
        if toggle.is_a? Array
          toggle.each { |r| page.edit.send(r).invoke('val').then((val) => { expect(val).to.be.equal('y' }
          page.edit.send(r + '_toggle').click()
        else
          page.edit.send(toggle).invoke('val').then((val) => { expect(val).to.be.equal('y'
          page.edit.send(toggle + '_toggle').click()
        }
      }

      submit_form

      toggles.each do |toggle|
        if toggle.is_a? Array
          toggle.each { |r| page.edit.send(r).invoke('val').then((val) => { expect(val).to.be.equal('n' }
        else
          page.edit.send(toggle).invoke('val').then((val) => { expect(val).to.be.equal('n'
        }
      }
    }
  }*/

  context('when using MSM', () => {

    before(function(){

    })

    beforeEach(function(){
      cy.task('db:seed')
      cy.auth();
      page.load()
      cy.hasNoErrors()
      create_msm_site()
      create_member_group()
    })

    it('creates member groups for other sites', () => {
      cy.task('db:query', 'SELECT count(group_id) AS count FROM exp_member_groups WHERE group_id=6').then(([result, fields]) => {
        result.forEach(function(row){
          expect(row.count).to.be.equal(2)
        });
      })
    })

    it('edits the preferences for specific sites', () => {
      edit_member_group()

      let rows = []
      let fields = 'group_title, group_description, is_locked, can_create_template_groups, can_edit_template_groups, can_delete_template_groups, can_access_comm, can_access_translate, can_access_data, can_access_logs'
      cy.task('db:query', 'SELECT '+fields+' FROM exp_member_groups WHERE group_id=6').then(([result, fields]) => {
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
    page.get('edit.website_access').eq(1).click()
    page.get('edit.can_view_profiles_toggle').click()
    page.get('edit.can_delete_self_toggle').click()
    page.get('edit.mbr_delete_notify_emails').clear().type('team@expressionengine.com')
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
    page.get('edit.cp_homepage').eq(1).click()
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

    //page.get('list').all_there?.should('eq', false
    page.get('edit.name').should('exist')
    page.get('edit.description').should('exist')
    page.get('edit.is_locked').should('exist')
  }

  function edit_member_group() {
    page.get('edit.name').clear().type('Editors')
    page.get('edit.description').clear().type('Editors description.')
    page.get('edit.is_locked_toggle').click()
    page.get('edit.template_groups_options').each(function(el) {el.click()})
    page.get('edit.allowed_template_groups_options').eq(1).click()
    page.get('edit.access_tools_options').eq(0).click()
    page.get('edit.access_tools_options').eq(3).click()
    page.get('edit.submit').click()

    page.get('list.groups').last().find('li.edit a').click()

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

  function submit_form() {
    page.get('edit.submit').click()

    page.get('list.groups').last().find('li.edit a').click()

    //page.get('list').all_there?.should('eq', false
    page.get('edit.name').should('exist')
    page.get('edit.description').should('exist')
    page.get('edit.is_locked').should('exist')
  }

  function create_msm_site() {
    page.get('dev_menu').click()

    // Enable MSM if it's not enabled
    //unless page.has_content?('Site Manager')
    if (Cypress.$('.nav-sub-menu a:contains("Site Manager")').length ==0)
    {
      cy.eeConfig({ item: 'multiple_sites_enabled', value: 'y' })
    }

    const form = new SiteForm
    form.add_site({
      name: 'Second Site',
      short_name: 'second_site'
    })

    page.load()
  }
})
