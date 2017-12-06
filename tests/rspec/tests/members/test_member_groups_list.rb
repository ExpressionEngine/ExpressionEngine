require './bootstrap.rb'

feature 'Member Group List' do
  before(:each) do
    cp_session
    @page = MemberGroups.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Member Group List page' do
    @page.all_there?.should == true
    @page.list.should have_groups_table
    @page.list.should have_groups
  end

  # Confirming phrase search
  it 'searches by phrases' do
    # Be sane and make sure it's there before we search for it
    @page.should have_text 'Super Admin'

    @page.keyword_search.set "Super Admin"
    @page.keyword_search.send_keys(:enter)

    @page.heading.text.should eq 'Search Results we found 1 results for "Super Admin"'
    @page.keyword_search.value.should eq "Super Admin"
    @page.should have_text 'Super Admin'
    @page.list.should have(1).groups
  end

  it 'shows no results on a failed search' do
    our_action = 'BadMemberGroup'
    @page.keyword_search.set our_action
    @page.keyword_search.send_keys(:enter)

    @page.heading.text.should eq 'Search Results we found 0 results for "' + our_action + '"'
    @page.keyword_search.value.should eq our_action
    @page.should have_text our_action

    @page.list.should have_no_results
    @page.should have_keyword_search
    @page.should have_perpage_filter

    @page.should_not have_pagination
  end

  context 'when creating a member group' do
    before :each do
      create_member_group
    end

    it 'creates a group successfully' do
      @page.edit.name.value.should == 'Moderators'
      @page.edit.description.value.should == 'Moderators description.'
      @page.edit.is_locked.value.should == 'y'
      @page.edit.website_access.each { |e| e.checked?.should == true }
      @page.edit.can_view_profiles.value.should == 'y'
      @page.edit.can_delete_self.value.should == 'y'
      @page.edit.mbr_delete_notify_emails.value.should == 'team@ellislab.com'
      @page.edit.include_members_in.each { |e| e.checked?.should == true }
      @page.edit.can_post_comments.value.should == 'y'
      @page.edit.exclude_from_moderation.value.should == 'y'
      @page.edit.comment_actions_options.each { |e| e.checked?.should == true }
      @page.edit.can_search.value.should == 'y'
      @page.edit.search_flood_control.value.should == '60'
      @page.edit.can_send_private_messages.value.should == 'y'
      @page.edit.prv_msg_send_limit.value.should == '50'
      @page.edit.prv_msg_storage_limit.value.should == '100'
      @page.edit.can_attach_in_private_messages.value.should == 'y'
      @page.edit.can_send_bulletins.value.should == 'y'
      @page.edit.can_access_cp.value.should == 'y'
      @page.edit.cp_homepage[1].checked?.should == true
      @page.edit.footer_helper_links_options.each { |e| e.checked?.should == true }
      @page.edit.channel_entry_actions_options.each { |e| e.checked?.should == true }
      @page.edit.member_actions_options.each { |e| e.checked?.should == true }
      @page.edit.allowed_channels_options.each { |e| e.checked?.should == true }
      @page.edit.can_admin_design.value.should == 'y'
      @page.edit.allowed_template_groups_options.each { |e| e.checked?.should == true }
      @page.edit.can_admin_addons.value.should == 'y'
      @page.edit.addons_access_options.each { |e| e.checked?.should == true }
      @page.edit.access_tools_options.each { |e| e.checked?.should == true }
    end
  end

  context 'when editing a member group' do
    before :each do
      create_member_group

      @page.edit.name.set 'Editors'
      @page.edit.description.set 'Editors description.'
      @page.edit.is_locked_toggle.click

      submit_form

      @page.edit.name.value.should == 'Editors'
      @page.edit.description.value.should == 'Editors description.'
      @page.edit.is_locked.value.should == 'n'
    end

    it 'toggles member group checkbox permissions' do
      skip "nested group permission visibility is not working properly, revisit when fixed" do
      end

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
        @page.edit.send(permission_name).each_with_index do |permission, index|
          toggle_state[permission_name][index] = permission.checked?
          permission.click
        end
      end

      submit_form

      checkboxes.each do |permission_name|
        @page.edit.send(permission_name).each_with_index do |permission, index|
          toggle_state[permission_name][index].should_not == permission.checked?
          permission.click
        end
      end

      submit_form

      checkboxes.each do |permission_name|
        @page.edit.send(permission_name).each_with_index do |permission, index|
          toggle_state[permission_name][index].should == permission.checked?
        end
      end

    end

    it 'toggles toggle permissions successfully' do
      skip "nested group permission visibility is not working properly, revisit when fixed" do
      end

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

      # CP actions on this group are already visible
      #@page.edit.can_access_cp_toggle.click
      toggles.each do |toggle|
        if toggle.is_a? Array
          # first element controls group visibility, and should already be enabled
          toggle.each_with_index do |r, idx|
            if idx != 0
              @page.edit.send(r + '_toggle').click
            end
          end
        else
          @page.edit.send(toggle + '_toggle').click
        end
      end
      submit_form

      @page.edit.can_access_cp.value.should == 'y'
      toggles.each do |toggle|
        if toggle.is_a? Array
          toggle.each { |r| @page.edit.send(r).value.should == 'y' }
          @page.edit.send(r + '_toggle').click
        else
          @page.edit.send(toggle).value.should == 'y'
          @page.edit.send(toggle + '_toggle').click
        end
      end

      submit_form

      toggles.each do |toggle|
        if toggle.is_a? Array
          toggle.each { |r| @page.edit.send(r).value.should == 'n' }
        else
          @page.edit.send(toggle).value.should == 'n'
        end
      end
    end
  end

  context 'when using MSM' do
    before :each do
      create_msm_site
      create_member_group
    end

    it 'creates member groups for other sites' do
      $db.query('SELECT count(group_id) AS count FROM exp_member_groups WHERE group_id=6').each do |row|
        row['count'].should == 2
      end
    end

    it 'edits the preferences for specific sites' do
      edit_member_group

      rows = []
      fields = 'group_title, group_description, is_locked, can_create_template_groups,
        can_edit_template_groups, can_delete_template_groups,
        can_access_comm, can_access_translate, can_access_data, can_access_logs'
      $db.query("SELECT #{fields} FROM exp_member_groups WHERE group_id=6").each do |row|
        rows << row
      end

      # These two fields should change among all groups
      rows[0]['group_title'].should == rows[1]['group_title']
      rows[0]['group_description'].should == rows[1]['group_description']
      rows[0]['is_locked'].should == rows[1]['is_locked']

      # These fields should *not* change among all groups
      rows[0]['can_edit_template_groups'].should == rows[1]['can_edit_template_groups']
      rows[0]['can_access_comm'].should_not == rows[1]['can_access_comm']
      rows[0]['can_access_translate'].should_not == rows[1]['can_access_translate']

      # These fields were not changed and should remain the same
      rows[0]['can_access_data'].should == rows[1]['can_access_data']
      rows[0]['can_access_logs'].should == rows[1]['can_access_logs']
    end

    it 'deletes all member group records when deleting a member group' do
      @page.load
      @page.list.groups.last.find('input[type="checkbox"]').click
      @page.list.batch_actions.set 'remove'
      @page.list.batch_submit.click

      sleep 1

      find('form[action$="cp/members/groups/delete"] input[type="submit"]').click

      @page.list.should have_groups_table
      @page.list.should have_groups
      @page.list.groups.size.should == 5
      @page.alert.text.should_not match(/[a-z]_[a-z]/)

      $db.query('SELECT count(group_id) AS count FROM exp_member_groups WHERE group_id=6').each do |row|
        row['count'].should == 0
      end
    end
  end

  def create_member_group
    @page.new_group.click

    @page.edit.all_there?.should == true
    @page.edit.should have_name
    @page.edit.should have_description
    @page.edit.should have_is_locked

    @page.edit.name.set 'Moderators'
    @page.edit.description.set 'Moderators description.'
    @page.edit.is_locked_toggle.click
    @page.edit.website_access[1].click
    @page.edit.can_view_profiles_toggle.click
    @page.edit.can_delete_self_toggle.click
    @page.edit.mbr_delete_notify_emails.set 'team@ellislab.com'
    @page.edit.include_members_in.each(&:click)
    @page.edit.can_post_comments_toggle.click
    @page.edit.exclude_from_moderation_toggle.click
    @page.edit.comment_actions_options.each(&:click)
    @page.edit.can_search_toggle.click
    @page.edit.search_flood_control.set '60'
    @page.edit.can_send_private_messages_toggle.click
    @page.edit.prv_msg_send_limit.set '50'
    @page.edit.prv_msg_storage_limit.set '100'
    @page.edit.can_attach_in_private_messages_toggle.click
    @page.edit.can_send_bulletins_toggle.click
    @page.edit.can_access_cp_toggle.click
    @page.edit.cp_homepage[1].click
    @page.edit.footer_helper_links_options.each(&:click)
    @page.edit.can_admin_channels_toggle.click
    @page.edit.channel_permissions_options.each(&:click)
    @page.edit.channel_category_permissions_options.each(&:click)
    @page.edit.channel_entry_actions_options.each(&:click)
    @page.edit.can_access_files_toggle.click
    @page.edit.can_access_members_toggle.click
    @page.edit.member_actions_options.each(&:click)
    @page.edit.allowed_channels_options.each(&:click)
    @page.edit.can_access_design_toggle.click
    @page.edit.can_admin_design_toggle.click
    @page.edit.template_groups_options.each(&:click)
    @page.edit.allowed_template_groups_options.each(&:click)
    @page.edit.can_access_addons_toggle.click
    @page.edit.can_admin_addons_toggle.click
    @page.edit.addons_access_options.each(&:click)
    @page.edit.can_access_utilities_toggle.click
    @page.edit.access_tools_options.each(&:click)
    @page.edit.can_access_sys_prefs_toggle.click
    @page.edit.submit.click

    @page.list.groups.last.find('li.edit a').click

    @page.list.all_there?.should == false
    @page.edit.should have_name
    @page.edit.should have_description
    @page.edit.should have_is_locked
  end

  def edit_member_group
    @page.edit.name.set 'Editors'
    @page.edit.description.set 'Editors description.'
    @page.edit.is_locked_toggle.click
    @page.edit.template_groups_options.each(&:click)
    @page.edit.allowed_template_groups_options[1].click
    @page.edit.access_tools_options[0].click
    @page.edit.access_tools_options[3].click
    @page.edit.submit.click

    @page.list.groups.last.find('li.edit a').click

    @page.list.all_there?.should == false
    @page.edit.should have_name
    @page.edit.should have_description
    @page.edit.should have_is_locked

    @page.edit.name.value.should == 'Editors'
    @page.edit.description.value.should == 'Editors description.'
    @page.edit.is_locked.value.should == 'n'
    @page.edit.template_groups_options.each { |e| e.checked?.should == false }
    @page.edit.allowed_template_groups_options[1].checked?.should == false
    @page.edit.access_tools_options[0].checked?.should == false
    @page.edit.access_tools_options[3].checked?.should == false
    @page.edit.access_tools_options[4].checked?.should == true
    @page.edit.access_tools_options[5].checked?.should == true
  end

  def submit_form
    @page.edit.submit.click

    @page.list.groups.last.find('li.edit a').click

    @page.list.all_there?.should == false
    @page.edit.should have_name
    @page.edit.should have_description
    @page.edit.should have_is_locked
  end

  def create_msm_site
    @page.main_menu.dev_menu.click

    # Enable MSM if it's not enabled
    unless @page.has_content?('Site Manager')
      @page.settings_btn.click
      find('input[name="multiple_sites_enabled"]', :visible => false).set 'y'
      find('form[action$="cp/settings/general"] div.form-btns.form-btns-top input[type="submit"]').click
      @page.main_menu.dev_menu.click
    end

    click_link 'Site Manager'
    find('a[href$="cp/msm/create"]').click

    find('input[name="site_label"]').set 'Second Site'
    find('input[name="site_name"]').set 'second_site'
    find('form[action$="cp/msm/create"] div.form-btns.form-btns-top input[type="submit"]').click

    @page.load
  end
end
