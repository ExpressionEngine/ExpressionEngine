require './bootstrap.rb'

feature 'Member Settings' do

  before(:each) do
    cp_session
    @page = MemberSettings.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Member Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    allow_member_registration = ee_config(item: 'allow_member_registration')
    require_terms_of_service = ee_config(item: 'require_terms_of_service')
    allow_member_localization = ee_config(item: 'allow_member_localization')
    new_member_notification = ee_config(item: 'new_member_notification')

    @page.allow_member_registration_y.checked?.should == (allow_member_registration == 'y')
    @page.allow_member_registration_n.checked?.should == (allow_member_registration == 'n')
    @page.req_mbr_activation.value.should == ee_config(item: 'req_mbr_activation')
    @page.require_terms_of_service_y.checked?.should == (require_terms_of_service == 'y')
    @page.require_terms_of_service_n.checked?.should == (require_terms_of_service == 'n')
    @page.allow_member_localization_y.checked?.should == (allow_member_localization == 'y')
    @page.allow_member_localization_n.checked?.should == (allow_member_localization == 'n')
    @page.default_member_group.value.should == ee_config(item: 'default_member_group')
    @page.member_theme.value.should == ee_config(item: 'member_theme')
    @page.memberlist_order_by.value.should == ee_config(item: 'memberlist_order_by')
    @page.memberlist_sort_order.value.should == ee_config(item: 'memberlist_sort_order')
    @page.memberlist_row_limit.value.should == ee_config(item: 'memberlist_row_limit')
    @page.new_member_notification_y.checked?.should == (new_member_notification == 'y')
    @page.new_member_notification_n.checked?.should == (new_member_notification == 'n')
    @page.mbr_notification_emails.value.should == ee_config(item: 'mbr_notification_emails')
  end

  it 'should validate the form' do
    emails_error = 'This field must contain all valid email addresses.'

    @page.mbr_notification_emails.set 'sdfsdfsd'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    should_have_error_text(@page.mbr_notification_emails, emails_error)

    # AJAX validation
    @page.load
    @page.mbr_notification_emails.set 'sdfsdfsd'
    @page.mbr_notification_emails.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.mbr_notification_emails, emails_error)
    should_have_form_errors(@page)

    @page.mbr_notification_emails.set 'trey@trey.com, test@test.com'
    @page.mbr_notification_emails.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.mbr_notification_emails)
    should_have_no_form_errors(@page)
  end

  it 'should save and load the settings' do
    @page.allow_member_registration_y.click
    @page.req_mbr_activation.select 'No activation required'
    @page.require_terms_of_service_n.click
    @page.allow_member_localization_n.click
    @page.default_member_group.select 'Super Admin'
    @page.member_theme.select 'Default'
    @page.memberlist_order_by.select 'Total entries'
    @page.memberlist_sort_order.select 'Ascending (A-Z)'
    @page.memberlist_row_limit.select '50'
    @page.new_member_notification_y.click
    @page.mbr_notification_emails.set 'test@test.com'
    @page.submit

    @page.should have_text 'Preferences Updated'
    @page.allow_member_registration_y.checked?.should == true
    @page.req_mbr_activation.value.should == 'none'
    @page.require_terms_of_service_n.checked?.should == true
    @page.allow_member_localization_n.checked?.should == true
    @page.default_member_group.value.should == '1'
    @page.member_theme.value.should == 'default'
    @page.memberlist_order_by.value.should == 'total_entries'
    @page.memberlist_sort_order.value.should == 'asc'
    @page.memberlist_row_limit.value.should == '50'
    @page.new_member_notification_y.checked?.should == true
    @page.mbr_notification_emails.value.should == 'test@test.com'
  end
end
