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
    @page.list.all_there?.should == true
  end

  context 'when creating a member group' do
    before :each do
      create_member_group
    end

    it 'creates a group successfully' do
      @page.edit.name.value.should == 'Moderators'
      @page.edit.description.value.should == 'Moderators description.'
      @page.edit.security_lock[0].checked?.should == true
      @page.edit.website_access.each { |e| e.checked?.should == true }
      @page.edit.can_view_profiles[0].checked?.should == true
      @page.edit.can_send_email[0].checked?.should == true
      @page.edit.can_delete_self[0].checked?.should == true
      @page.edit.mbr_delete_notify_emails.value.should == 'team@ellislab.com'
      @page.edit.include_members_in.each { |e| e.checked?.should == true }
      @page.edit.can_post_comments[0].checked?.should == true
      @page.edit.exclude_from_moderation[0].checked?.should == true
      @page.edit.comment_actions.each { |e| e.checked?.should == true }
      @page.edit.can_search[0].checked?.should == true
      @page.edit.search_flood_control.value.should == '60'
      @page.edit.can_send_private_messages[0].checked?.should == true
      @page.edit.prv_msg_send_limit.value.should == '50'
      @page.edit.prv_msg_storage_limit.value.should == '100'
      @page.edit.can_attach_in_private_messages[0].checked?.should == true
      @page.edit.can_send_bulletins[0].checked?.should == true
      @page.edit.can_access_cp[0].checked?.should == true
      @page.edit.cp_homepage[1].checked?.should == true
      @page.edit.footer_helper_links.each { |e| e.checked?.should == true }
      @page.edit.can_admin_channels[0].checked?.should == true
      @page.edit.category_actions.each { |e| e.checked?.should == true }
      @page.edit.channel_entry_actions.each { |e| e.checked?.should == true }
      @page.edit.member_actions.each { |e| e.checked?.should == true }
      @page.edit.allowed_channels.each { |e| e.checked?.should == true }
      @page.edit.can_admin_design[0].checked?.should == true
      @page.edit.can_admin_templates[0].checked?.should == true
      @page.edit.allowed_template_groups.each { |e| e.checked?.should == true }
      @page.edit.can_admin_modules[0].checked?.should == true
      @page.edit.addons_access.each { |e| e.checked?.should == true }
      @page.edit.access_tools.each { |e| e.checked?.should == true }
    end

    it 'edits a group successfully' do
      edit_member_group
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
      fields = 'group_title, group_description, is_locked, can_admin_templates,
        can_access_comm, can_access_utilities, can_access_data, can_access_logs'
      $db.query("SELECT #{fields} FROM exp_member_groups WHERE group_id=6").each do |row|
        rows << row
      end

      # These two fields should change among all groups
      rows[0]['group_title'].should == rows[1]['group_title']
      rows[0]['group_description'].should == rows[1]['group_description']
      rows[0]['is_locked'].should == rows[1]['is_locked']

      # These fields should *not* change among all groups
      rows[0]['can_admin_templates'].should_not == rows[1]['can_admin_templates']
      rows[0]['can_access_comm'].should_not == rows[1]['can_access_comm']
      rows[0]['can_access_utilities'].should_not == rows[1]['can_access_utilities']

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

      @page.list.all_there?.should == true
      @page.list.groups.size.should == 5
      @page.alert.text.should_not match(/[a-z]_[a-z]/)

      $db.query('SELECT count(group_id) AS count FROM exp_member_groups WHERE group_id=6').each do |row|
        row['count'].should == 0
      end
    end
  end

  def create_member_group
    @page.new_group.click

    @page.all_there?.should == true
    @page.edit.all_there?.should == true

    @page.edit.name.set 'Moderators'
    @page.edit.description.set 'Moderators description.'
    @page.edit.security_lock[0].click
    @page.edit.website_access.each(&:click)
    @page.edit.can_view_profiles[0].click
    @page.edit.can_send_email[0].click
    @page.edit.can_delete_self[0].click
    @page.edit.mbr_delete_notify_emails.set 'team@ellislab.com'
    @page.edit.include_members_in.each(&:click)
    @page.edit.can_post_comments[0].click
    @page.edit.exclude_from_moderation[0].click
    @page.edit.comment_actions.each(&:click)
    @page.edit.can_search[0].click
    @page.edit.search_flood_control.set '60'
    @page.edit.can_send_private_messages[0].click
    @page.edit.prv_msg_send_limit.set '50'
    @page.edit.prv_msg_storage_limit.set '100'
    @page.edit.can_attach_in_private_messages[0].click
    @page.edit.can_send_bulletins[0].click
    @page.edit.can_access_cp[0].click
    @page.edit.cp_homepage[1].click
    @page.edit.footer_helper_links.each(&:click)
    @page.edit.can_admin_channels[0].click
    @page.edit.category_actions.each(&:click)
    @page.edit.channel_entry_actions.each(&:click)
    @page.edit.member_actions.each(&:click)
    @page.edit.allowed_channels.each(&:click)
    @page.edit.can_admin_design[0].click
    @page.edit.can_admin_templates[0].click
    @page.edit.allowed_template_groups.each(&:click)
    @page.edit.can_admin_modules[0].click
    @page.edit.addons_access.each(&:click)
    @page.edit.access_tools.each(&:click)
    @page.edit.submit.click

    @page.list.groups.last.find('li.edit a').click

    @page.list.all_there?.should == false
    @page.edit.all_there?.should == true
  end

  def edit_member_group
    @page.edit.name.set 'Editors'
    @page.edit.description.set 'Editors description.'
    @page.edit.security_lock[1].click
    @page.edit.can_admin_templates[1].click
    @page.edit.allowed_template_groups.each(&:click)
    @page.edit.access_tools[0].click
    @page.edit.access_tools[1].click
    @page.edit.submit.click

    @page.list.groups.last.find('li.edit a').click

    @page.list.all_there?.should == false
    @page.edit.all_there?.should == true

    @page.edit.name.value.should == 'Editors'
    @page.edit.description.value.should == 'Editors description.'
    @page.edit.security_lock[0].checked?.should == false
    @page.edit.security_lock[1].checked?.should == true
    @page.edit.can_admin_templates[0].checked?.should == false
    @page.edit.can_admin_templates[1].checked?.should == true
    @page.edit.allowed_template_groups.each { |e| e.checked?.should == false }
    @page.edit.access_tools[0].checked?.should == false
    @page.edit.access_tools[1].checked?.should == false
    @page.edit.access_tools[2].checked?.should == true
    @page.edit.access_tools[3].checked?.should == true
  end

  def create_msm_site
    visit '/system/index.php?/cp/msm'
    find('.sidebar a[href$="cp/msm/create"]').click
    find('input[name="site_label"]').set 'Second Site'
    find('input[name="site_name"]').set 'second_site'
    find('form[action$="cp/msm/create"] input[type="submit"]').click
    @page.load
  end
end
