require './bootstrap.rb'

feature 'Member Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = MemberSettings.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Member Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    allow_member_registration = ee_config(item: 'allow_member_registration')
    require_terms_of_service = ee_config(item: 'require_terms_of_service')
    allow_member_localization = ee_config(item: 'allow_member_localization')
    new_member_notification = ee_config(item: 'new_member_notification')

    page.allow_member_registration.value.should == allow_member_registration
    page.require_terms_of_service.value.should == require_terms_of_service
    page.allow_member_localization.value.should == allow_member_localization
    page.req_mbr_activation.has_checked_radio(ee_config(item: 'req_mbr_activation')).should == true
    page.default_member_group.has_checked_radio(ee_config(item: 'default_member_group')).should == true
    page.member_theme.has_checked_radio(ee_config(item: 'member_theme')).should == true
    page.memberlist_order_by.has_checked_radio(ee_config(item: 'memberlist_order_by')).should == true
    page.memberlist_sort_order.has_checked_radio(ee_config(item: 'memberlist_sort_order')).should == true
    page.memberlist_row_limit.has_checked_radio(ee_config(item: 'memberlist_row_limit')).should == true
    page.new_member_notification.value.should == new_member_notification
    page.mbr_notification_emails.value.should == ee_config(item: 'mbr_notification_emails')
  }

  it('should validate the form', () => {
    emails_error = 'This field must contain all valid email addresses.'

    page.mbr_notification_emails.set 'sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    should_have_form_errors(page)
    page.should have_text 'Attention: Settings not saved'
    should_have_error_text(page.mbr_notification_emails, emails_error)

    // AJAX validation
    page.load()
    page.mbr_notification_emails.set 'sdfsdfsd'
    page.mbr_notification_emails.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.mbr_notification_emails, emails_error)
    should_have_form_errors(page)

    page.mbr_notification_emails.set 'trey@trey.com, test@test.com'
    page.mbr_notification_emails.trigger 'blur'
    page.wait_for_error_message_count(0)
    should_have_no_error_text(page.mbr_notification_emails)
    should_have_no_form_errors(page)
  }

  it('should save and load the settings', () => {
    allow_member_registration = ee_config(item: 'allow_member_registration')
    require_terms_of_service = ee_config(item: 'require_terms_of_service')
    allow_member_localization = ee_config(item: 'allow_member_localization')
    new_member_notification = ee_config(item: 'new_member_notification')

    page.allow_member_registration_toggle.click()
    page.req_mbr_activation.choose_radio_option('none')
    page.require_terms_of_service_toggle.click()
    page.allow_member_localization_toggle.click()
    page.default_member_group.choose_radio_option('1')
    page.member_theme.choose_radio_option('default')
    page.memberlist_order_by.choose_radio_option('dates')
    page.memberlist_sort_order.choose_radio_option('asc')
    page.memberlist_row_limit.choose_radio_option('50')
    page.new_member_notification_toggle.click()
    page.mbr_notification_emails.set 'test@test.com'
    page.submit

    page.should have_text 'Preferences Updated'
    page.allow_member_registration.value.should_not == allow_member_registration
    page.req_mbr_activation.has_checked_radio('none').should == true
    page.require_terms_of_service.value.should_not == require_terms_of_service
    page.allow_member_localization.value.should_not == allow_member_localization
    page.default_member_group.has_checked_radio('1').should == true
    page.member_theme.has_checked_radio('default').should == true
    page.memberlist_order_by.has_checked_radio('dates').should == true
    page.memberlist_sort_order.has_checked_radio('asc').should == true
    page.memberlist_row_limit.has_checked_radio('50').should == true
    page.new_member_notification.value.should_not == new_member_notification
    page.mbr_notification_emails.value.should == 'test@test.com'
  }
}
